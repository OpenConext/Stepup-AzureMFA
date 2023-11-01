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

namespace Surfnet\AzureMfa\Infrastructure\Entity;

use Surfnet\AzureMfa\Domain\Institution\Collection\CertificateCollection;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Destination;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EntityId;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\IdentityProviderInterface;
use Surfnet\SamlBundle\Entity\IdentityProvider;

class AzureMfaIdentityProvider extends IdentityProvider implements IdentityProviderInterface
{
    public function __construct(
        EntityId $entityId,
        Destination $destination,
        private readonly CertificateCollection $certificates,
        bool $isAzureAD)
    {
        $configuration = [
            // The entityId is not configured in the
            'entityId' => $entityId->getEntityId(),
            'ssoUrl' => $destination->getUrl(),
            'certificateData' => $certificates->first()->getCertData(),
            'isAzureAD' => $isAzureAD,
        ];

        parent::__construct($configuration);
    }

    public function getSsoLocation(): Destination
    {
        return new Destination((string) parent::getSsoUrl());
    }

    public function getCertificates(): CertificateCollection
    {
        return $this->certificates;
    }

    public function isAzureAD(): bool
    {
        return (bool) $this->get('isAzureAD', false);
    }
}
