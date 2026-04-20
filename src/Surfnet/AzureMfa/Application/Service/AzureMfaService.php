<?php

declare(strict_types = 1);

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
use Surfnet\AzureMfa\Domain\Institution\ValueObject\IdentityProviderInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Institution;
use Surfnet\AzureMfa\Domain\Exception\AzureADException;
use Surfnet\AzureMfa\Domain\Exception\InstitutionNotFoundException;
use Surfnet\AzureMfa\Domain\Exception\MailAttributeMismatchException;
use Surfnet\AzureMfa\Domain\Exception\MissingMailAttributeException;
use Surfnet\AzureMfa\Domain\User;
use Surfnet\AzureMfa\Domain\UserId;
use Surfnet\AzureMfa\Domain\UserStatus;
use Surfnet\AzureMfa\Infrastructure\Cache\IdentityProviderFactoryCache;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use SAML2\Assertion;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Exception;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 */
class AzureMfaService
{
    final public const SAML_EMAIL_ATTRIBUTE = 'urn:mace:dir:attribute-def:mail';

    private readonly SessionInterface $session;

    public function __construct(
        private readonly EmailDomainMatchingService $matchingService,
        private readonly IdentityProviderFactoryCache $identityProviderCache,
        private readonly ServiceProvider $serviceProvider,
        private readonly PostBinding $postBinding,
        RequestStack $requestStack,
        private readonly LoggerInterface $logger
    ) {
        $this->session = $requestStack->getSession();
    }

    public function startRegistration(EmailAddress $emailAddress): User
    {
        // TODO: test attempts / blocked
        $this->logger->info('Generating a new UserId based on the user email address '.$emailAddress->getEmailAddress());
        $userId = UserId::generate($emailAddress);
        $user = new User($userId, $emailAddress, UserStatus::pending());

        $this->logger->info('Updating user session: status pending');
        $this->session->set('user', $user);

        return $user;
    }

    public function finishRegistration(UserId $userId): UserId
    {
        $this->logger->info('Finishing the registration for userId '.$userId->getUserId());
        /** @var User $user */
        $user = $this->session->get('user');

        if (!$userId->isEqual($user->getUserId())) {
            throw new InvalidMfaAuthenticationContextException(
                'Unknown registration context another process is started in the meantime for userId '. $userId->getUserId()
            );
        }
        $this->logger->info('removing user session for '.$userId->getUserId());
        $this->session->remove('user');

        return $userId;
    }

    public function startAuthentication(UserId $userId): User
    {
        $this->logger->info('Starting an authentication based on the provided UserId: '.$userId->getUserId());
        $user = new User($userId, $userId->getEmailAddress(), UserStatus::registered());

        $this->logger->info('Updating user session: status registered for userId: '.$userId->getUserId());
        $this->session->set('user', $user);

        return $user;
    }

    public function finishAuthentication(UserId $userId): UserId
    {
        $this->logger->info('Finishing the authenticationfor userId: '.$userId->getUserId());
        /** @var User $user */
        $user = $this->session->get('user');

        if (!$userId->isEqual($user->getUserId())) {
            throw new InvalidMfaAuthenticationContextException(
                'Unknown authentication context another process is started in the meantime for userId: '.$userId->getUserId()
            );
        }

        $this->logger->info('Removing user session for '.$userId->getUserId());
        $this->session->remove('user');

        return $userId;
    }

    public function createAuthnRequest(User $user, bool $forceAuthn = false): string
    {
        $this->logger->info('Creating a SAML2 AuthnRequest to send to the Azure MFA IdP for user:'.$user->getEmailAddress()->getEmailAddress());

        $this->logger->info('Retrieve the institution for the authenticating/registering user: '.$user->getEmailAddress()->getEmailAddress());
        $institution = $this->matchingService->findInstitutionByEmail($user->getEmailAddress());
        if (null === $institution) {
            $message = sprintf(
                'The provided email address did not match any of our configured email domains. (%s)',
                $user->getEmailAddress()->getEmailAddress()
            );
            $this->logger->info($message);
            throw new InstitutionNotFoundException($message);
        }

        $azureMfaIdentityProvider = $this->identityProviderCache->build($institution->getName());
        $destination = $azureMfaIdentityProvider->getSsoLocation();

        $authnRequest = AuthnRequestFactory::createNewRequest(
            $this->serviceProvider,
            $azureMfaIdentityProvider,
            $forceAuthn
        );

        // Use email address as subject if not sending to an AzureAD IdP
        if (!$azureMfaIdentityProvider->isAzureAD()) {
            $this->logger->info('Setting the users email address '.$user->getEmailAddress()->getEmailAddress().' as the Subject for the request to '.$azureMfaIdentityProvider->getEntityId());
            $authnRequest->setSubject($user->getEmailAddress()->getEmailAddress());
        }

        // Set authnContextClassRef to force MFA
        $this->logger->info(
            'Setting "http://schemas.microsoft.com/claims/multipleauthn" as the authentication context class reference for the request to '.$azureMfaIdentityProvider->getEntityId()
        );
        $authnRequest->setAuthenticationContextClassRef('http://schemas.microsoft.com/claims/multipleauthn');

        // Create redirect response.
        $query = $authnRequest->buildRequestQuery();

        // For Azure, add a "whr" query parameter with the domain of the user we want to authenticate.
        if ($azureMfaIdentityProvider->isAzureAD()) {
            // EntraID does not accept a Subject like ADFS does, and there is no other way to pass the full user ID.
            // The Windows home realm hint (whr) parameter can help bypass the Microsoft Account Picker
            // (Home Realm Discovery) when the user has multiple accounts, thereby improving the user (SSO) experience.

            // Get domain part of the user's email address (UPN) and use that as home realm
            $domain = $user->getEmailAddress()->getDomain();
            $query .= '&whr=' . urlencode($domain);
        }

        return sprintf(
            '%s?%s',
            $destination->getUrl(),
            $query
        );
    }

    public function handleResponse(Request $request): User
    {
        $user = $this->session->get('user');
        assert($user instanceof User);

        // Retrieve its institution and identity provider
        $this->logger->info('Match the user email address '.$user->getEmailAddress()->getEmailAddress().' to one of the registered institutions');
        $institution = $this->matchingService->findInstitutionByEmail($user->getEmailAddress());
        if (is_null($institution)) {
            throw new AzureADException('The Institution could not be found using the users mail address');
        }
        $azureMfaIdentityProvider = $this->identityProviderCache->build($institution->getName());

        $assertion = $this->processSamlResponse($request, $institution, $azureMfaIdentityProvider);
        $attributes = $assertion->getAttributes();

        $this->validateResponseAttributes($attributes, $institution, $azureMfaIdentityProvider, $user);

        $this->logger->info(sprintf(
            'The mail attribute in the response %s matched the email address of the registering/authenticating user: %s',
            implode(', ', $attributes[self::SAML_EMAIL_ATTRIBUTE]),
            $user->getEmailAddress()->getEmailAddress()
        ));

        return $user;
    }

    private function processSamlResponse(
        Request $request,
        Institution $institution,
        IdentityProviderInterface $azureMfaIdentityProvider,
    ): Assertion {
        try {
            $this->logger->info('Process the SAML Response');
            return $this->postBinding->processResponse($request, $azureMfaIdentityProvider, $this->serviceProvider);
        } catch (Exception $e) {
            // If the signature validation fails, we try to rebuild the identity provider
            // This will effectively refresh the metadata for the institution
            // Todo: refactor this to be more robust, e.g. by checking the exception type
            if (!$institution->supportsMetadataUpdate() || $e->getMessage() !== 'Unable to validate Signature') {
                throw $e;
            }
        }

        $this->logger->info(sprintf('Unable to validate signature refreshing metadata for %s', $institution->getName()->getInstitutionName()));
        $azureMfaIdentityProvider = $this->identityProviderCache->rebuild($institution->getName());

        $this->logger->info('Reprocess the SAML Response from '.$institution->getName()->getInstitutionName().' after updating the metadata');
        try {
            return $this->postBinding->processResponse($request, $azureMfaIdentityProvider, $this->serviceProvider);
        } catch (Exception $retryException) {
            $this->logger->error(sprintf(
                'Unable to validate signature after refreshing metadata for %s',
                $institution->getName()->getInstitutionName()
            ));
            throw $retryException;
        }
    }

    /**
     * @param array<string, array<string>> $attributes
     */
    private function validateResponseAttributes(
        array $attributes,
        Institution $institution,
        IdentityProviderInterface $azureMfaIdentityProvider,
        User $user,
    ): void {
        // If the IDP was an AzureAD endpoint (the entityID or Issuer starts with https://login.microsoftonline.com/, or preferably an config parameter in institutions.yaml)
        // the SAML response attribute 'http://schemas.microsoft.com/claims/authnmethodsreferences'
        // should contain 'http://schemas.microsoft.com/claims/multipleauthn'
        if ($azureMfaIdentityProvider->isAzureAD()) {
            $this->logger->info($institution->getName()->getInstitutionName().' is an AzureAD IdP. Validating authnmethodsreferences in the response.');
            if (!isset($attributes['http://schemas.microsoft.com/claims/authnmethodsreferences']) || !in_array('http://schemas.microsoft.com/claims/multipleauthn', $attributes['http://schemas.microsoft.com/claims/authnmethodsreferences'])) {
                throw new AzureADException(
                    'No http://schemas.microsoft.com/claims/multipleauthn in authnmethodsreferences response from '.$institution->getName()->getInstitutionName()
                );
            }
        }

        if (!isset($attributes[self::SAML_EMAIL_ATTRIBUTE])) {
            throw new MissingMailAttributeException(
                'The mail attribute in the Azure MFA assertion from '.$institution->getName()->getInstitutionName().' was missing'
            );
        }

        if (!in_array(strtolower($user->getEmailAddress()->getEmailAddress()), array_map('strtolower', $attributes[self::SAML_EMAIL_ATTRIBUTE]))) {
            throw new MailAttributeMismatchException(sprintf(
                'The mail attribute (%s) from the Azure MFA assertion from %s did not contain the email address provided during registration (%s)',
                implode(', ', $attributes[self::SAML_EMAIL_ATTRIBUTE]),
                $institution->getName()->getInstitutionName(),
                strtolower($user->getEmailAddress()->getEmailAddress())
            ));
        }
    }
}
