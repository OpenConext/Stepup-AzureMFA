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

use Surfnet\AzureMfa\Application\Exception\InvalidMfaAuthenticationContextException;
use Surfnet\AzureMfa\Application\Institution\Service\EmailDomainMatchingService;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\InvalidMFANameIdException;
use Surfnet\AzureMfa\Domain\User;
use Surfnet\AzureMfa\Domain\UserId;
use Surfnet\AzureMfa\Domain\UserStatus;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AzureMfaService
{
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

    public function __construct(
        EmailDomainMatchingService $matchingService,
        ServiceProvider $serviceProvider,
        PostBinding $postBinding,
        SessionInterface $session
    ) {
        $this->matchingService = $matchingService;
        $this->serviceProvider = $serviceProvider;
        $this->postBinding = $postBinding;
        $this->session = $session;
    }

    public function startRegistration(EmailAddress $emailAddress): User
    {
        // TODO: test attempts / blocked

        $userId = UserId::generate($emailAddress);
        $user = new User($userId, $emailAddress, UserStatus::pending());

        $this->session->set('user', $user);

        return $user;
    }

    public function finishRegistration(UserId $userId): UserId
    {
        $user = $this->session->get('user');

        if (!$userId->isEqual($user->getUserId())) {
            throw new InvalidMfaAuthenticationContextException(
                'Unknown registration context another process is started in the meantime'
            );
        }

        $this->session->remove('user');

        return $userId;
    }


    public function startAuthentication(UserId $userId): User
    {
        $user = new User($userId, $userId->getEmailAddress(), UserStatus::registered());
        $this->session->set('user', $user);

        return $user;
    }

    public function finishAuthentication(UserId $userId): UserId
    {
        $user = $this->session->get('user');

        if (!$userId->isEqual($user->getUserId())) {
            throw new InvalidMfaAuthenticationContextException(
                'Unknown authentication context another process is started in the meantime'
            );
        }

        $this->session->remove('user');

        return $userId;
    }

    /**
     * @param User $user
     * @return RedirectResponse
     */
    public function createAuthnRequest(User $user): string
    {
        $authnRequest = AuthnRequestFactory::createNewRequest($this->serviceProvider, $this->getIdentityProvider());

        // Use emailaddress as subject
        $authnRequest->setSubject($user->getEmailAddress()->getEmailAddress());

        // Set authnContextClassRef to force MFA
        $authnRequest->setAuthenticationContextClassRef('http://schemas.microsoft.com/claims/multipleauthn');

        // Create redirect response.
        $query = $authnRequest->buildRequestQuery();

        return sprintf(
            '%s?%s',
            $this->getIdentityProvider()->getSsoUrl(),
            $query
        );
    }

    /**
     * @param Request $request
     */
    public function handleResponse(Request $request): User
    {
        $assertion = $this->postBinding->processResponse(
            $request,
            $this->getIdentityProvider(),
            $this->getServiceProvider()
        );

        // validate NameID
        $user = $this->session->get('user');
        if ($assertion->getNameId()->value !== $user->getEmailAddress()->getEmailAddress()) {
            throw new InvalidMFANameIdException(
                'The NameId from the Azure MFA assertion did not match the NameId provided during registration'
            );
        }

        //TODO: do we need additional validation?

        // On handling the response:
        // 1. Do we need custom error response handling? Simply returning the error response will probably be best as
        //    gateway will handle this as a failed authentication/registration.

        // Validation possibilities:
        // 1. Verify the email address provided to determine the destination matches the one in the subject name id
        // 2. Did the response come form an IdP that is configured in the institution configuration (requires additional configuration of the idp entity id)
        return $user;
    }

    private function getIdentityProvider(): IdentityProvider
    {
        // TODO: make configurable
        return new IdentityProvider(
            [
                'entityId' => 'https://azure-mfa.stepup.example.com/mock/idp/metadata',
                'ssoUrl' => 'https://azure-mfa.stepup.example.com/mock/sso',
                'certificateFile' => $this->serviceProvider->getCertificateFile(),
                'privateKeys' => [$this->serviceProvider->getPrivateKey('default')],
            ]
        );
    }
}
