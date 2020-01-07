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

declare(strict_types=1);

namespace Surfnet\AzureMfa\Infrastructure\Controller;

use Exception;
use Surfnet\AzureMfa\Infrastructure\Service\ErrorPageHelper;
use Surfnet\StepupBundle\Controller\ExceptionController as BaseExceptionController;
use Surfnet\StepupBundle\Exception\Art;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ExceptionController extends BaseExceptionController
{
    private $errorPageHelper;

    public function __construct(ErrorPageHelper $errorPageHelper)
    {
        $this->errorPageHelper = $errorPageHelper;
    }

    public function showAction(Request $request, Exception $exception)
    {
        $statusCode = $this->getStatusCode($exception);

        $template = 'Exception\error.html.twig';
        if ($statusCode == 404) {
            $template = 'Exception\error404.html.twig';
        }

        $response = new Response('', $statusCode);

        $errorCode = Art::forException($exception);

        return $this->render(
            $template,
            $this->errorPageHelper->generateMetadata($request) +
            ['error_code' => $errorCode] +
            $this->getPageTitleAndDescription($exception),
            $response
        );
    }

    /**
     * @param Exception $exception
     * @return array View parameters 'title' and 'description'
     */
    protected function getPageTitleAndDescription(Exception $exception)
    {
        if (isset($title) && isset($description)) {
            return [
                'title' => $title,
                'description' => $description,
            ];
        }

        return parent::getPageTitleAndDescription($exception);
    }

    /**
     * @param Exception $exception
     * @return int HTTP status code
     */
    protected function getStatusCode(Exception $exception)
    {
        return parent::getStatusCode($exception);
    }
}
