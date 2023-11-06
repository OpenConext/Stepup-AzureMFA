<?php declare(strict_types = 1);
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

namespace Dev\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Annotation\Route;
use DOMDocument;
use SAML2\Assertion;
use SAML2\Configuration\PrivateKey;
use SAML2\DOMDocumentFactory;
use SAML2\Message;
use SAML2\Response;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\Exception\AuthnFailedSamlResponseException;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

final class SPController extends AbstractController
{
    private readonly ServiceProvider $serviceProvider;

    public function __construct(
        private readonly IdentityProvider $identityProvider,
        private readonly PostBinding $postBinding
    ) {
        $baseDir = dirname(__DIR__, 2);
        $this->serviceProvider = new ServiceProvider(
            [
                'entityId' => 'https://azuremfa.stepup.example.com/saml/metadata',
                'assertionConsumerUrl' => 'https://azuremfa.stepup.example.com/demo/sp/acs',
                'certificateFile' => $baseDir . '/vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_publickey.cer',
                'privateKeys' => [
                    new PrivateKey(
                        $baseDir . '/vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_privatekey.pem',
                        'default'
                    ),
                ],
            ]
        );
    }

    #[Route(path: '/demo/sp', name: 'sp_demo')]
    public function demoSp(Request $request): SymfonyResponse
    {
        if (!$request->isMethod(Request::METHOD_POST)) {
            return $this->render('dev/sp.html.twig');
        }
        $authnRequest = AuthnRequestFactory::createNewRequest($this->serviceProvider, $this->identityProvider);

        // Set nameId when we want to authenticate.
        if ($request->get('action') === 'authenticate') {
            $authnRequest->setSubject($request->get('NameID'));
        }

        // Create redirect response.
        $query = $authnRequest->buildRequestQuery();
        $url = sprintf('%s?%s', $this->identityProvider->getSsoUrl(), $query);
        $response = new RedirectResponse($url);

        // Set Stepup request id header.
        $stepupRequestId = $request->get('X-Stepup-Request-Id');
        if (!empty($stepupRequestId)) {
            $response->headers->set('X-Stepup-Request-Id', $stepupRequestId);
        }

        return $response;
    }

    #[Route(path: '/demo/sp/acs', name: 'sp_demo_acs')]
    public function assertionConsumerService(Request $request): SymfonyResponse
    {
        $xmlResponse = $request->request->get('SAMLResponse');
        $xml = base64_decode($xmlResponse);
        try {
            $response = $this->postBinding->processResponse($request, $this->identityProvider, $this->serviceProvider);

            $nameID = $response->getNameId();

            return $this->render('dev/acs.html.twig', [
                'requestId' => $response->getId(),
                'nameId' => $nameID !== null ? [
                    'value' => $nameID->getValue(),
                    'format' => $nameID->getFormat(),
                ] : [],
                'issuer' => $response->getIssuer(),
                'relayState' => $request->get(AuthnRequest::PARAMETER_RELAY_STATE, ''),
                'authenticatingAuthority' => $response->getAuthenticatingAuthority(),
                'xml' => $this->toFormattedXml($xml),
            ]);
        } catch (AuthnFailedSamlResponseException $e) {
            $samlResponse = $this->toUnsignedErrorResponse($xml);

            return $this->render('dev/acs-error-response.html.twig', [
                'error' => $e->getMessage(),
                'status' => $samlResponse->getStatus(),
                'requestId' => $samlResponse->getId(),
                'issuer' => $samlResponse->getIssuer(),
                'relayState' => $request->get(AuthnRequest::PARAMETER_RELAY_STATE, ''),
                'xml' => $this->toFormattedXml($xml),
            ]);
        }
    }

    /**
     * Formats xml.
     *
     *
     * @return string
     */
    private function toFormattedXml(string|bool $xml): string|bool
    {
        $domXml = new DOMDocument('1.0');
        $domXml->preserveWhiteSpace = false;
        $domXml->formatOutput = true;
        $domXml->loadXML($xml);

        return $domXml->saveXML();
    }

    /**
     * @throws Exception
     */
    private function toUnsignedErrorResponse(string $xml): Message
    {
        $asXml = DOMDocumentFactory::fromString($xml);
        return Response::fromXML($asXml->documentElement);
    }
}
