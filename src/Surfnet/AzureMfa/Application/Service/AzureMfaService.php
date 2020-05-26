<?php declare(strict_types=1);

/**
 * Copyright 2019 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\AzureMfa\Application\Service;

use Psr\Log\LoggerInterface;
use Surfnet\AzureMfa\Application\Exception\InvalidMfaAuthenticationContextException;
use Surfnet\AzureMfa\Application\Institution\Service\EmailDomainMatchingService;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\MailAttributeMismatchException;
use Surfnet\AzureMfa\Domain\Exception\MissingMailAttributeException;
use Surfnet\AzureMfa\Domain\User;
use Surfnet\AzureMfa\Domain\UserId;
use Surfnet\AzureMfa\Domain\UserStatus;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AzureMfaService
{
    const SAML_EMAIL_ATTRIBUTE = 'urn:mace:dir:attribute-def:mail';

    /**
     * @var EmailDomainMatchingService
     */
    private $matchingService;

    /**
     * @var PostBinding
     */
    private $postBinding;

    /**
     * @var ServiceProvider
     */
    private $serviceProvider;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EmailDomainMatchingService $matchingService,
        ServiceProvider $serviceProvider,
        PostBinding $postBinding,
        SessionInterface $session,
        LoggerInterface $logger
    ) {
        $this->matchingService = $matchingService;
        $this->serviceProvider = $serviceProvider;
        $this->postBinding = $postBinding;
        $this->session = $session;
        $this->logger = $logger;
    }

    public function startRegistration(EmailAddress $emailAddress): User
    {
        // TODO: test attempts / blocked
        $this->logger->info('Generating a new UserId based on the user email address');
        $userId = UserId::generate($emailAddress);
        $user = new User($userId, $emailAddress, UserStatus::pending());

        $this->logger->info('Updating user session: status pending');
        $this->session->set('user', $user);

        return $user;
    }

    public function finishRegistration(UserId $userId): UserId
    {
        $this->logger->info('Finishing the registration');
        $user = $this->session->get('user');

        if (!$userId->isEqual($user->getUserId())) {
            throw new InvalidMfaAuthenticationContextException(
                'Unknown registration context another process is started in the meantime'
            );
        }
        $this->logger->info('Updating user session: removing');
        $this->session->remove('user');

        return $userId;
    }

    public function startAuthentication(UserId $userId): User
    {
        $this->logger->info('Starting an authentication based on the provided UserId');
        $user = new User($userId, $userId->getEmailAddress(), UserStatus::registered());

        $this->logger->info('Updating user session: status registered');
        $this->session->set('user', $user);

        return $user;
    }

    public function finishAuthentication(UserId $userId): UserId
    {
        $this->logger->info('Finishing the authentication');
        $user = $this->session->get('user');

        if (!$userId->isEqual($user->getUserId())) {
            throw new InvalidMfaAuthenticationContextException(
                'Unknown authentication context another process is started in the meantime'
            );
        }

        $this->logger->info('Updating user session: removing');
        $this->session->remove('user');

        return $userId;
    }

    /**
     * @param User $user
     * @param bool $forceAuthn
     * @return RedirectResponse
     */
    public function createAuthnRequest(User $user, bool $forceAuthn = false): string
    {
        $this->logger->info('Creating a SAML2 AuthnRequest to send to the Azure MFA IdP');

        $this->logger->info('Retrieve the institution for the authenticating/registering user');
        $institution = $this->matchingService->findInstitutionByEmail($user->getEmailAddress());
        $azureMfaIdentityProvider = $institution->getIdentityProvider();
        $destination = $azureMfaIdentityProvider->getSsoLocation();

        $authnRequest = AuthnRequestFactory::createNewRequest(
            $this->serviceProvider,
            $azureMfaIdentityProvider,
            $forceAuthn
        );

        // Use email address as subject if not sending to an AzureAD IdP
        if (!(strpos($destination->getUrl(),"https://login.microsoftonline.com/") === 0)) {
          $this->logger->info('Setting the users email address as the Subject');
          $authnRequest->setSubject($user->getEmailAddress()->getEmailAddress());
        }

        // Set authnContextClassRef to force MFA
        $this->logger->info(
            'Setting "http://schemas.microsoft.com/claims/multipleauthn" as the authentication context class reference'
        );
        $authnRequest->setAuthenticationContextClassRef('http://schemas.microsoft.com/claims/multipleauthn');

        // Create redirect response.
        $query = $authnRequest->buildRequestQuery();

        return sprintf(
            '%s?%s',
            $destination->getUrl(),
            $query
        );
    }

    /**
     * @param Request $request
     */
    public function handleResponse(Request $request): User
    {
        // Load the registering/authenticating user
        $user = $this->session->get('user');

        // Retrieve its institution and identity provider
        $this->logger->info('Match the user email address to one of the registered institutions');
        $institution = $this->matchingService->findInstitutionByEmail($user->getEmailAddress());
        $azureMfaIdentityProvider = $institution->getIdentityProvider();

        $this->logger->info('Process the SAML Response');
        $assertion = $this->postBinding->processResponse(
            $request,
            $azureMfaIdentityProvider,
            $this->serviceProvider
        );

        $attributes = $assertion->getAttributes();

        // If the IDP was an AzureAD endpoint (the entityID or Issuer starts with https://login.microsoftonline.com/, or preferably an config parameter in institutions.yaml)
        // the SAML response attribute 'http://schemas.microsoft.com/claims/authnmethodsreferences'
        // should contain 'http://schemas.microsoft.com/claims/multipleauthn'
        if (strpos($azureMfaIdentityProvider->getSsoUrl(),"https://login.microsoftonline.com/") === 0) {
          $this->logger->info('This is an AzureAD IdP. Validating authnmethodsreferences in the response.');
          if (!in_array('http://schemas.microsoft.com/claims/multipleauthn',$attributes['http://schemas.microsoft.com/claims/authnmethodsreferences'])) {
            // TODO: Create a proper AuthnmethodsreferencesMissingException
            throw new Exception(
                'No http://schemas.microsoft.com/claims/multipleauthn in authnmethodsreferences.'
            );
          }
        }

        if (!isset($attributes[self::SAML_EMAIL_ATTRIBUTE])) {
            throw new MissingMailAttributeException(
                'The mail attribute in the Azure MFA assertion was missing'
            );
        }

        if (!in_array($user->getEmailAddress()->getEmailAddress(), $attributes[self::SAML_EMAIL_ATTRIBUTE])) {
            throw new MailAttributeMismatchException(
                'The mail attribute from the Azure MFA assertion did not contain the email address provided during registration'
            );
        }

        $this->logger->info('The mail attribute in the response matched the email address of the registering/authenticating user');
        return $user;
    }
}
