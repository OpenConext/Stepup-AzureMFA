<?php

declare(strict_types = 1);

/**
 * Copyright 2025 SURFnet B.V.
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

namespace Surfnet\AzureMfa\Application\Service\Metadata;

use Surfnet\AzureMfa\Infrastructure\Kernel;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MockMetadataClientCallback
{
    public function __construct(private readonly Kernel $kernel)
    {
    }

    public function __invoke(string $method, string $url, array $options = []): ResponseInterface
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $url]);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return new MockResponse($response->getContent());
    }
}
