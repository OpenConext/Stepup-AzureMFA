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

use Surfnet\AzureMfa\Application\Institution\Service\EmailDomainMatchingService;
use Surfnet\AzureMfa\Application\Service\AzureMfaService;
use Surfnet\AzureMfa\Infrastructure\Form\EmailAddressDto;
use Surfnet\AzureMfa\Infrastructure\Form\EmailAddressType;
use Surfnet\GsspBundle\Service\AuthenticationService;
use Surfnet\GsspBundle\Service\RegistrationService;
use Surfnet\SamlBundle\Http\Exception\AuthnFailedSamlResponseException;
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
     * @var AzureMfaService
     */
    private $azureMfaService;

    public function __construct(
        AuthenticationService $authenticationService,
        RegistrationService $registrationService,
        EmailDomainMatchingService $domainMatchingService,
        AzureMfaService $azureMfaService
    ) {
        $this->authenticationService = $authenticationService;
        $this->registrationService = $registrationService;
        $this->domainMatchingService = $domainMatchingService;
        $this->azureMfaService = $azureMfaService;
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
     * @Route("/registration", name="azure_mfa_registration")
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

            return new RedirectResponse($this->azureMfaService->createAuthnRequest($emailAddress->getEmailAddress()));
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
     * @Route("/authentication", name="azure_mfa_authentication")
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

            return new RedirectResponse($this->azureMfaService->createAuthnRequest($nameId));
        }

        $requiresAuthentication = $this->authenticationService->authenticationRequired();
        $response = new Response(null, $requiresAuthentication ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

        return $this->render('default/authentication.html.twig', [
            'requiresAuthentication' => $requiresAuthentication,
            'NameID' => $nameId ?: 'unknown',
        ], $response);
    }

    /**
     * @Route("/saml/acs", name="azure_mfa_acs")
     */
    public function acsAction(Request $request)
    {
        try {
            $this->azureMfaService->handleResponse($request);
        } catch (AuthnFailedSamlResponseException $e) {
            $this->registrationService->reject($request->get('message'));
        }

        // Todo: find out if we do need to handle different exceptions / responses ?

        return $this->registrationService->replyToServiceProvider();
    }
}
