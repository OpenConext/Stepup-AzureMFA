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

use Surfnet\AzureMfa\Domain\Exception\InvalidInstitutionException;
use Surfnet\AzureMfa\Domain\Institution\Collection\EmailDomainCollection;

class InstitutionConfigurationData
{
    private string $entityId;
    private string $destination;
    /** @var string[] */
    private array $certificates;
    private bool $isAzureAd;
    private string $metadataUrl;


    /**
     * @param string[] $certificates
     */
    public function __construct(string $entityId, string $destination, array $certificates, bool $isAzureAd, string $metadataUrl)
    {
        // IdP from config only
        if (empty($metadataUrl)) {
            if (empty($entityId)) {
                throw new InvalidInstitutionException('The entity ID cannot be an empty string.');
            }

            if (count($certificates) == 0) {
                throw new InvalidInstitutionException('The institution must have at least one certificate.');
            }

            if (empty($destination)) {
                throw new InvalidInstitutionException('The destination cannot be an empty string when no metadata URL is provided.');
            }

            foreach ($certificates as $certificate) {
                if (!is_string($certificate) || trim($certificate) === '') {
                    throw new InvalidInstitutionException('Each certificate must be a non-empty string.');
                }
            }

            $this->entityId = $entityId;
            $this->destination = $destination;
            $this->certificates = $certificates;
            $this->isAzureAd = $isAzureAd;
            $this->metadataUrl = '';

            return;
        }

        // IdP from metadata URL
        $this->entityId = '';
        $this->destination = '';
        $this->certificates = [];
        $this->isAzureAd = $isAzureAd;
        $this->metadataUrl = $metadataUrl;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @return string[]
     */
    public function getCertificates(): array
    {
        return $this->certificates;
    }

    public function isAzureAd(): bool
    {
        return $this->isAzureAd;
    }

    public function getMetadataUrl(): string
    {
        return $this->metadataUrl;
    }

    public function hasMetadataUrl(): bool
    {
        return !empty($this->metadataUrl);
    }


    public function hasCertificates(): bool
    {
        return count($this->certificates) > 0;
    }
}
