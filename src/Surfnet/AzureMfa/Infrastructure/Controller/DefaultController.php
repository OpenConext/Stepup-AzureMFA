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

namespace Surfnet\AzureMfa\Infrastructure\Controller;

use Exception;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Configuration\PrivateKey;
use Surfnet\AzureMfa\Application\Institution\Service\EmailDomainMatchingService;
use Surfnet\AzureMfa\Infrastructure\Form\EmailAddressDto;
use Surfnet\AzureMfa\Infrastructure\Form\EmailAddressType;
use Surfnet\GsspBundle\Service\AuthenticationService;
use Surfnet\GsspBundle\Service\RegistrationService;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\Exception\AuthnFailedSamlResponseException;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    private $authenticationService;
    private $registrationService;
    private $domainMatchingService;
    /**
     * @var PostBinding
     */
    private $postBinding;
    /**
     * @var string
     */
    private $publicKey;
    /**
     * @var string
     */
    private $privateKey;

    public function __construct(
        AuthenticationService $authenticationService,
        RegistrationService $registrationService,
        EmailDomainMatchingService $domainMatchingService,
        PostBinding $postBinding
    )
    {
        $this->authenticationService = $authenticationService;
        $this->registrationService = $registrationService;
        $this->domainMatchingService = $domainMatchingService;
        $this->postBinding = $postBinding;

        // Todo: make keys configurable
        $this->publicKey = __DIR__ . '/../../../../../vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_publickey.cer';
        $this->privateKey = __DIR__ . '/../../../../../vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_privatekey.pem';
    }

    /**
     * Replace this example code with whatever you need/
     *
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * Replace this example code with whatever you need.
     *
     * See @see RegistrationService for a more clean example.
     *
     * @Route("/registration", name="app_identity_registration")
     */
    public function registrationAction(Request $request)
    {
        if ($request->get('action') === 'error') {
            $this->registrationService->reject($request->get('message'));
            return $this->registrationService->replyToServiceProvider();
        }

        $requiresRegistration = $this->registrationService->registrationRequired();
        $response = new Response(null, $requiresRegistration ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

        $emailAddress = new EmailAddressDto();
        $form = $this->createForm(EmailAddressType::class, $emailAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->registrationService->register($emailAddress->getEmailAddress());

            return $this->handleAuthnRequestToAdfs($emailAddress->getEmailAddress(), $this->createServiceProvider(), $this->createIdentityProvider());
        }

        return $this->render('default/registration.html.twig', [
            'requiresRegistration' => $requiresRegistration,
            'form' => $form->createView()
        ], $response);
    }

    /**
     * Replace this example code with whatever you need.
     *
     * See @see AuthenticationService for a more clean example.
     *
     * @Route("/authentication", name="app_identity_authentication")
     */
    public function authenticationAction(Request $request)
    {
        $nameId = $this->authenticationService->getNameId();

        if ($request->get('action') === 'error') {
            $this->authenticationService->reject($request->get('message'));
            return $this->authenticationService->replyToServiceProvider();
        }

        if ($request->get('action') === 'authenticate') {
            // The application should very if the user matches the nameId.
            $this->authenticationService->authenticate();

            return $this->handleAuthnRequestToAdfs($nameId, $this->createServiceProvider(), $this->createIdentityProvider());
        }

        $requiresAuthentication = $this->authenticationService->authenticationRequired();
        $response = new Response(null, $requiresAuthentication ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

        return $this->render('default/authentication.html.twig', [
            'requiresAuthentication' => $requiresAuthentication,
            'NameID' => $nameId ?: 'unknown',
        ], $response);
    }

    /**
     * @Route("/acs", name="app_identity_acs")
     */
    public function acsAction(Request $request)
    {

        $xmlResponse = $request->request->get('SAMLResponse');
        $xml = base64_decode($xmlResponse);
        try {
            /** @var Assertion $response */
            $response = $this->postBinding->processResponse($request, $this->createIdentityProvider(), $this->createServiceProvider());

            //TODO: do we need additional validation?

        } catch (AuthnFailedSamlResponseException $e) {
            $this->registrationService->reject($request->get('message'));
        } catch (\Exception $e) {
            $this->registrationService->reject($request->get('message'));
        }

        return $this->registrationService->replyToServiceProvider();
    }

    /**
     * @param string $nameId
     * @param ServiceProvider $serviceProvider
     * @param IdentityProvider $identityProvider
     * @return RedirectResponse
     * @throws Exception
     */
    private function handleAuthnRequestToAdfs(string $nameId, ServiceProvider $serviceProvider, IdentityProvider $identityProvider)
    {
        $authnRequest = AuthnRequestFactory::createNewRequest($serviceProvider, $identityProvider);

        // Use emailaddress as subject
        $authnRequest->setSubject($nameId);

        // Set authnContextClassRef to force MFA
        $authnRequest->setAuthenticationContextClassRef('http://schemas.microsoft.com/claims/multipleauthn');

        // Build request query parameters.
        $requestAsXml = $authnRequest->getUnsignedXML();
        $encodedRequest = base64_encode(gzdeflate($requestAsXml));
        $queryParams = [AuthnRequest::PARAMETER_REQUEST => $encodedRequest];

        // Create redirect response.
        $query = $this->signRequestQuery($queryParams);
        $url = sprintf('%s?%s', $identityProvider->getSsoUrl(), $query);

        return new RedirectResponse($url);
    }

    /**
     * Sign AuthnRequest query parameters.
     *
     * @param array $queryParams
     * @return string
     *
     * @throws Exception
     */
    private function signRequestQuery(array $queryParams)
    {
        /** @var  $securityKey */
        $securityKey = $this->loadServiceProviderPrivateKey();
        $queryParams[AuthnRequest::PARAMETER_SIGNATURE_ALGORITHM] = $securityKey->type;
        $toSign = http_build_query($queryParams);
        $signature = $securityKey->signData($toSign);

        return $toSign . '&Signature=' . urlencode(base64_encode($signature));
    }

    /**
     * Loads the private key from the service provider.
     *
     * @return XMLSecurityKey
     *
     * @throws Exception
     */
    private function loadServiceProviderPrivateKey()
    {
        $keyLoader = new PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey(
            new PrivateKey(
                $this->privateKey,
                'default'
            )
        );
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        return $key;
    }


    private function createServiceProvider(): ServiceProvider
    {
        // TODO: make configurable
        $samlBundle = '';
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
                'sharedKey' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
            ]
        );
    }

    private function createIdentityProvider(): IdentityProvider
    {
        // TODO: make configurable
        $samlBundle = '';
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
                'sharedKey' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
            ]
        );
    }

}
