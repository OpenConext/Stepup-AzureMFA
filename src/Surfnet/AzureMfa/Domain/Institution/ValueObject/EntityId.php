<?php declare(strict_types = 1);

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

use Surfnet\AzureMfa\Domain\Exception\InvalidCertificateException;
use Surfnet\AzureMfa\Domain\Exception\InvalidEntityIdException;
use function openssl_x509_fingerprint;
use function openssl_x509_parse;

class EntityId
{
    private $entityId;

    public function __construct(string $entityId)
    {
        if (empty($entityId)) {
            throw new InvalidEntityIdException('The IdP EntityId can not be an empty string.');
        }

        $this->entityId = $entityId;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }
}
