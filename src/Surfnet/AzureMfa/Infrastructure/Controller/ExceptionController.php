<?php

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

declare(strict_types = 1);

namespace Surfnet\AzureMfa\Infrastructure\Controller;

use Exception;
use Surfnet\AzureMfa\Infrastructure\Service\ErrorPageHelper;
use Surfnet\StepupBundle\Controller\ExceptionController as BaseExceptionController;
use Surfnet\StepupBundle\Exception\Art;
use Surfnet\StepupBundle\Request\RequestId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ExceptionController extends BaseExceptionController
{
    public function __construct(
        private readonly ErrorPageHelper $errorPageHelper,
        TranslatorInterface $translator,
        RequestId $requestId
    ) {
        parent::__construct($translator, $requestId);
    }

    public function show(Request $request, Exception $exception): Response
    {
        $statusCode = $this->getStatusCode($exception);

        $template = '@default/bundles/TwigBundle/Exception/error.html.twig';
        if ($statusCode == 404) {
            $template = '@default/bundles/TwigBundle/Exception/error404.html.twig';
        }

        $response = new Response('', $statusCode);

        $errorCode = Art::forException($exception);

        $params = $this->errorPageHelper->generateMetadata($request) +
            ['error_code' => $errorCode] +
            $this->getPageTitleAndDescription($exception);

        return $this->render(
            $template,
            $params,
            $response
        );
    }

    /**
     * @return array<string, string>
     */
    protected function getPageTitleAndDescription(Exception $exception): array
    {
        return parent::getPageTitleAndDescription($exception);
    }

    protected function getStatusCode(Exception $exception): int
    {
        return parent::getStatusCode($exception);
    }
}
