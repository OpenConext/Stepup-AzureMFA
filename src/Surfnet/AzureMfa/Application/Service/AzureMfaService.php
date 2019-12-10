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

use SAML2\Configuration\PrivateKey;
use Surfnet\AzureMfa\Application\Repository\UserRepositoryInterface;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\InvalidMFANameIdException;
use Surfnet\AzureMfa\Domain\Exception\UserNotFoundException;
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

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AzureMfaService
{
    /**
     * @var PostBinding
     */
    private $postBinding;
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(PostBinding $postBinding, UserRepositoryInterface $userRepository, SessionInterface $session)
    {
        $this->postBinding = $postBinding;
        $this->userRepository = $userRepository;
        $this->session = $session;

        // Todo: make keys configurable
        $this->publicKey = __DIR__ . '/../../../../../vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_publickey.cer';
        $this->privateKey = __DIR__ . '/../../../../../vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_privatekey.pem';
    }

    public function startRegistration(EmailAddress $emailAddress): User
    {
        // TODO: test attempts / blocked

        $userId = UserId::generate();

        $this->session->set('userId', $userId);

        $user = new User($userId, $emailAddress, UserStatus::pending());
        $this->userRepository->save($user);

        return $user;
    }

    public function finishRegistration(): UserId
    {
        $userId = $this->session->get('userId');

        $user = $this->userRepository->load($userId);
        $user->setStatus(UserStatus::registered());
        $this->userRepository->save($user);

        $this->session->remove('userId');

        return $userId;
    }


    public function startAuthentication(UserId $userId): User
    {
        // TODO: test attempts / blocked

        $user = $this->userRepository->load($userId);
        if (!$user->getStatus()->isRegistered()) {
            throw new UserNotFoundException('Unaable to find registered user');
        }

        $this->session->set('userId', $userId);

        return $user;
    }

    public function finishAuthentication(): userId
    {
        $userId = $this->session->get('userId');

        $this->session->remove('userId');

        return $userId;
    }

    /**
     *
     * /**
     * @param User $user
     * @return RedirectResponse
     */
    public function createAuthnRequest(User $user): string
    {
        $authnRequest = AuthnRequestFactory::createNewRequest($this->getServiceProvider(), $this->getIdentityProvider());

        // Use emailaddress as subject
        $authnRequest->setSubject($user->getEmailAddress()->getEmailAddress());

        // Set authnContextClassRef to force MFA
        $authnRequest->setAuthenticationContextClassRef('http://schemas.microsoft.com/claims/multipleauthn');

        // Create redirect response.
        $query = $authnRequest->buildRequestQuery();
        return sprintf('%s?%s', $this->getIdentityProvider()->getSsoUrl(), $query);
    }

    /**
     * @param Request $request
     */
    public function handleResponse(Request $request): User
    {
        $assertion = $this->postBinding->processResponse($request, $this->getIdentityProvider(), $this->getServiceProvider());

        // validate NameID
        $userId = $this->session->get('userId');
        $user = $this->userRepository->load($userId);
        if ($assertion->getNameId()->value !== $user->getEmailAddress()->getEmailAddress()) {
            throw new InvalidMFANameIdException('MFA returned invalid NameId');
        }

        //TODO: do we need additional validation?

        return  $user;
    }

    private function getServiceProvider(): ServiceProvider
    {
        // TODO: make configurable
        return new ServiceProvider(
            [
                'entityId' => 'https://azure-mfa.stepup.example.com/saml/metadata',
                'assertionConsumerUrl' => 'https://azure-mfa.stepup.example.com/saml/acs',
                'certificateFile' => $this->publicKey,
                'privateKeys' => [
                    new PrivateKey(
                        $this->privateKey,
                        'default'
                    ),
                ],
            ]
        );
    }

    private function getIdentityProvider(): IdentityProvider
    {
        // TODO: make configurable
        return new IdentityProvider(
            [
                'entityId' => 'https://azure-mfa.stepup.example.com/mock/idp/metadata',
                'ssoUrl' => 'https://azure-mfa.stepup.example.com/mock/sso',
                'certificateFile' => $this->publicKey,
                'privateKeys' => [
                    new PrivateKey(
                        $this->privateKey,
                        'default'
                    ),
                ],
            ]
        );
    }
}
