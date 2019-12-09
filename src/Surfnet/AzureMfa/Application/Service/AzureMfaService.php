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
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Configuration\PrivateKey;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AzureMfaService
{

    /**
     * @var string
     */
    private $publicKey;
    /**
     * @var string
     */
    private $privateKey;
    /**
     * @var PostBinding
     */
    private $postBinding;

    public function __construct(
        PostBinding $postBinding
    ) {
        $this->postBinding = $postBinding;

        // Todo: make keys configurable
        $this->publicKey = __DIR__ . '/../../../../../vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_publickey.cer';
        $this->privateKey = __DIR__ . '/../../../../../vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_privatekey.pem';
    }

    /**
     * @param string $nameId
     * @return RedirectResponse
     * @throws Exception
     */
    public function createAuthnRequest(string $nameId): string
    {
        $authnRequest = AuthnRequestFactory::createNewRequest($this->getServiceProvider(), $this->getIdentityProvider());

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
        $this->postBinding->processResponse($request, $this->getIdentityProvider(), $this->getServiceProvider());

        //TODO: do we need additional validation?
    }

    private function getServiceProvider(): ServiceProvider
    {
        // TODO: make configurable
        return new ServiceProvider(
            [
                'entityId' => 'https://azure-mfa.stepup.example.com/saml/metadata',
                'assertionConsumerUrl' => 'https://azure-mfa.stepup.example.com/acs',
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
