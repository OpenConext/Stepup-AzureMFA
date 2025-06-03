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

namespace Surfnet\AzureMfa\Domain\Institution\ValueObject;

use Surfnet\AzureMfa\Domain\Exception\InvalidMetadataUrlException;

class MetadataUrl
{
    private readonly string $url;

    public function __construct(string $url)
    {
        if ($url === '') {
            throw new InvalidMetadataUrlException('The metadata URL can not be an empty string.');
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidMetadataUrlException('Please provide a valid URL for the Azure MFA metadata url endpoint.');
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme !== 'https') {
            throw new InvalidMetadataUrlException('The Azure MFA metadata URL must use the HTTPS scheme');
        }

        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
