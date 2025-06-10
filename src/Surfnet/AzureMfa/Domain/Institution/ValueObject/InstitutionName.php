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

namespace Surfnet\AzureMfa\Domain\Institution\ValueObject;

use Surfnet\AzureMfa\Domain\Exception\InvalidInstitutionNameException;

class InstitutionName
{
    /**
     * Valid characters for an institution name because it is used in filenames for the identity provider cache.
     * It should resemble domain names, because that is how institutions are identified in the Azure MFA
     */
    private const VALID_CHARACTERS = '/^[a-zA-Z0-9&._-]+$/';

    private readonly string $institutionName;

    public function __construct(string $institutionName)
    {
        if ($institutionName === '') {
            throw new InvalidInstitutionNameException('The institution name can not be an empty string.');
        }

        if (!preg_match(self::VALID_CHARACTERS, $institutionName)) {
            throw new InvalidInstitutionNameException('The institution name contains invalid characters');
        }

        $this->institutionName = $institutionName;
    }

    public function getInstitutionName(): string
    {
        return $this->institutionName;
    }
}
