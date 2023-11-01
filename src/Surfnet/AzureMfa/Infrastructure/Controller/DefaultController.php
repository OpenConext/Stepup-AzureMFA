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

namespace Surfnet\AzureMfa\Infrastructure\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use Surfnet\AzureMfa\Application\Service\AuthenticationHelperInterface;
use Surfnet\AzureMfa\Application\Service\AzureMfaService;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\UserId;
use Surfnet\AzureMfa\Infrastructure\Form\EmailAddressDto;
use Surfnet\AzureMfa\Infrastructure\Form\EmailAddressType;
use Surfnet\GsspBundle\Service\AuthenticationService;
use Surfnet\GsspBundle\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) - A higher level of coupling is favoured over having business
 * logic in the controller
 */
class DefaultController extends AbstractController
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly AuthenticationHelperInterface $authenticationHelper,
        private readonly RegistrationService $registrationService,
        private readonly AzureMfaService $azureMfaService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Replace this example code with whatever you need/
     *
     * @Route("/", name="homepage")
     */
    public function indexAction(): Response
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
    public function registrationAction(Request $request): RedirectResponse|Response
    {
        $this->logger->info('Verifying if there is a pending registration from SP');

        if ($request->get('action') === 'error') {
            $this->logger->error('The registration failed, rejecting the registration request');
            $this->registrationService->reject($request->get('message'));
            return $this->registrationService->replyToServiceProvider();
        }

        $attribs = $this->authenticationService->getGsspUserAttributes();
        if ($attribs && $attribs->getAttributeValue('urn:mace:dir:attribute-def:mail')) {
            $emailAddr = new EmailAddress($attribs->getAttributeValue('urn:mace:dir:attribute-def:mail'));
            $user = $this->azureMfaService->startRegistration($emailAddr);
            return new RedirectResponse($this->azureMfaService->createAuthnRequest($user));
        }

        $requiresRegistration = $this->registrationService->registrationRequired();
        $response = new Response(null, $requiresRegistration ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

        $emailAddress = new EmailAddressDto();
        $form = $this->createForm(EmailAddressType::class, $emailAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info(
                'Matched the user to an institution, continue registration by sending an ' .
                'authentication request to the Azure MFA remote IdP'
            );
            $user = $this->azureMfaService->startRegistration(new EmailAddress($emailAddress->getEmailAddress()));

            return new RedirectResponse($this->azureMfaService->createAuthnRequest($user));
        }

        $this->logger->info('Asking the user for its email address in order to match it to his/her institution');
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
    public function authenticationAction(): RedirectResponse|Response
    {
        $requiresAuthentication = $this->authenticationService->authenticationRequired();
        if (!$requiresAuthentication) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }
        $nameId = $this->authenticationService->getNameId();

        $user = $this->azureMfaService->startAuthentication(new UserId($nameId));

        return new RedirectResponse(
            $this->azureMfaService->createAuthnRequest($user, $this->authenticationHelper->useForceAuthn())
        );
    }

    /**
     * @Route("/saml/acs", name="azure_mfa_acs")
     */
    public function acsAction(Request $request): Response
    {
        $this->logger->info('Receiving response from the Azure MFA remote IdP');

        try {
            $this->logger->info('Load the associated Stepup user from this response');
            $user = $this->azureMfaService->handleResponse($request);

            // Check registration status
            if ($user->getStatus()->isPending()) {
                // Handle registration, this user is already registered
                $this->logger->info('Finishing the registration');
                $userId = $this->azureMfaService->finishRegistration($user->getUserId());
                $this->registrationService->register($userId->getUserId());
            } elseif ($user->getStatus()->isRegistered()) {
                // Handle authentication, this user is already registered
                $this->logger->info('Process the authentication');
                $this->azureMfaService->finishAuthentication($user->getUserId());
                $this->authenticationService->authenticate();
            }
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'The authentication or registration failed. Rejecting the Azure MFA response. Error message: "%s"',
                    $e->getMessage()
                )
            );
            $this->registrationService->reject($request->get('message'));
        }

        $this->logger->info('Sending a SAML response to the SP');
        return $this->registrationService->replyToServiceProvider();
    }
}
