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

namespace Surfnet\AzureMfa\Infrastructure\Twig;

use RuntimeException;
use Surfnet\AzureMfa\Infrastructure\Service\ErrorPageHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GlobalViewParameters
{

    /**
     * @param string[] $supportUrl
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ErrorPageHelper $errorPageHelper,
        private readonly RequestStack $request,
        private readonly array $supportUrl,
        private readonly ?string $supportEmail = null
    ) {
    }

    public function getSupportUrl(): string
    {
        return $this->supportUrl[$this->translator->getLocale()];
    }

    /**
     * @return array<string, string|null>
     */
    public function getRequestInformation(): array
    {
        $currentRequest = $this->request->getCurrentRequest();
        if (is_null($currentRequest)) {
            throw new RuntimeException('Unable to get request information, as no request is present in the RequestStack');
        }
        $metadata = $this->errorPageHelper->generateMetadata($currentRequest);
        return [
            'supportEmail' => $this->supportEmail,
            'hostname' => $metadata['hostname'],
            'ipAddress' => $metadata['ip_address'],
            'requestId' => $metadata['request_id'],
            'sari' => $metadata['sari'],
            'userAgent' => $metadata['user_agent'],
        ];
    }
}
