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

use DateTime;
use Exception;
use SAML2\Response\Exception\InvalidResponseException;
use SAML2\Response\Exception\PreconditionNotMetException;
use Surfnet\SamlBundle\Http\Exception\AuthnFailedSamlResponseException;
use Surfnet\SamlBundle\Http\Exception\SignatureValidationFailedException;
use Surfnet\SamlBundle\Http\Exception\UnknownServiceProviderException;
use Surfnet\SamlBundle\Http\Exception\UnsignedRequestException;
use Surfnet\SamlBundle\Http\Exception\UnsupportedSignatureException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Translation\TranslatorInterface;

final class ExceptionController
{
    public function showAction(Request $request, Exception $exception)
    {
        $statusCode = $this->getStatusCode($exception);

        if ($statusCode == 404) {
            $template = 'SurfnetStepupBundle:Exception:error404.html.twig';
        } else {
            $template = 'SurfnetStepupBundle:Exception:error.html.twig';
        }

        $response = new Response('', $statusCode);

        $timestamp = (new DateTime)->format(DateTime::ISO8601);
        $hostname  = $request->getHost();
        $requestId = $this->get('surfnet_stepup.request.request_id');
        $errorCode = 'TODO';
        $userAgent = $request->headers->get('User-Agent');
        $ipAddress = $request->getClientIp();

        return $this->render(
            $template,
            [
                'timestamp'   => $timestamp,
                'hostname'    => $hostname,
                'request_id'  => $requestId->get(),
                'error_code'  => $errorCode,
                'user_agent'  => $userAgent,
                'ip_address'  => $ipAddress,
            ] + $this->getPageTitleAndDescription($exception),
            $response
        );
    }

    /**
     * @param Exception $exception
     * @return int HTTP status code
     */
    protected function getStatusCode(Exception $exception)
    {
        if ($exception instanceof AuthenticationException ||
            $exception instanceof InvalidResponseException) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if ($exception instanceof AccessDeniedException ||
            $exception instanceof PreconditionNotMetException) {
            return Response::HTTP_FORBIDDEN;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        // Unknown exceptions are server errors!
        return 500;
    }

    /**
     * @param Exception $exception
     * @return array View parameters 'title' and 'description'
     */
    protected function getPageTitleAndDescription(Exception $exception)
    {
        $translator = $this->getTranslator();

        if ($exception instanceof SignatureValidationFailedException) {
            $title = $translator->trans('stepup.error.signature_validation_failed.title');
            $description = $translator->trans('stepup.error.signature_validation_failed.description');

        } elseif ($exception instanceof UnsignedRequestException) {
            $title = $translator->trans('stepup.error.unsigned_request.title');
            $description = $translator->trans('stepup.error.unsigned_request.description');

        } elseif ($exception instanceof UnsupportedSignatureException) {
            $title = $translator->trans('stepup.error.unsupported_signature.title');
            $description = $translator->trans('stepup.error.unsupported_signature.description');

        } elseif ($exception instanceof UnknownServiceProviderException) {
            $title = $translator->trans('stepup.error.unknown_service_provider.title');
            $description = $exception->getMessage();

        } elseif ($exception instanceof AuthnFailedSamlResponseException) {
            $title = $translator->trans('stepup.error.authn_failed.title');
            $description = $translator->trans('stepup.error.authn_failed.description');

        } elseif ($exception instanceof PreconditionNotMetException) {
            $title = $translator->trans('stepup.error.precondition_not_met.title');
            $description = $translator->trans('stepup.error.precondition_not_met.description');

        } elseif ($exception instanceof InvalidResponseException) {
            $title = $translator->trans('stepup.error.authentication_error.title');
            $description = $translator->trans('stepup.error.authentication_error.description');
        } elseif ($exception instanceof AuthenticationException) {
            $title = $translator->trans('stepup.error.authentication_error.title');
            $description = $translator->trans('stepup.error.authentication_error.description');
        } else {
            $title = $translator->trans('stepup.error.generic_error.title');
            $description = $translator->trans('stepup.error.generic_error.description');
        }

        return [
            'title' => $title,
            'description' => $description,
        ];
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }
}
