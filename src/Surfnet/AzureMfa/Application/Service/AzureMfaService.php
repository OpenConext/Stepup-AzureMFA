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

use Exception;
use SAML2\Assertion;
use Surfnet\AzureMfa\Application\Repository\UserRepositoryInterface;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\User;
use Surfnet\AzureMfa\Domain\UserId;
use Surfnet\AzureMfa\Domain\UserStatus;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AzureMfaService
{
    /**
     * @var PostBinding
     */
    private $postBinding;
    /**
     * @var ServiceProvider
     */
    private $serviceProvider;
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(ServiceProvider $serviceProvider, PostBinding $postBinding, UserRepositoryInterface $userRepository, SessionInterface $session)
    {
        $this->serviceProvider = $serviceProvider;
        $this->postBinding = $postBinding;
        $this->userRepository = $userRepository;
        $this->session = $session;
    }

    public function startRegistration(EmailAddress $emailAddress)
    {
        // TODO: test attempts / blocked

        $userId = UserId::generate();

        $this->session->set('userId', $userId);

        $user = new User($userId, $emailAddress, UserStatus::pending());
        $this->userRepository->save($user);
    }

    public function finishRegistration(): UserId
    {
        $userId = $this->session->get('userId');

        $user = $this->userRepository->load($userId);
        $user->setStatus(UserStatus::registered());
        $this->userRepository->save($user);

        return $userId;
    }

    /**
     * @param string $nameId
     * @return RedirectResponse
     * @throws Exception
     */
    public function createAuthnRequest(string $nameId): string
    {
        $authnRequest = AuthnRequestFactory::createNewRequest($this->serviceProvider, $this->getIdentityProvider());

        // Use emailaddress as subject
        $authnRequest->setSubject($nameId);

        // Set authnContextClassRef to force MFA
        $authnRequest->setAuthenticationContextClassRef('http://schemas.microsoft.com/claims/multipleauthn');

        // Create redirect response.
        $query = $authnRequest->buildRequestQuery();
        return sprintf('%s?%s', $this->getIdentityProvider()->getSsoUrl(), $query);
    }

    /**
     * @param Request $request
     */
    public function handleResponse(Request $request)
    {
        /** @var Assertion $response */
        $this->postBinding->processResponse($request, $this->getIdentityProvider(), $this->serviceProvider);
        //TODO: do we need additional validation?
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
