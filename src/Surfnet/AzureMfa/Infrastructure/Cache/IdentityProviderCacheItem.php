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

namespace Surfnet\AzureMfa\Infrastructure\Cache;

use Surfnet\AzureMfa\Domain\Institution\Collection\CertificateCollection;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Certificate;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Destination;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EntityId;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\IdentityProviderInterface;
use Surfnet\AzureMfa\Infrastructure\Entity\AzureMfaIdentityProvider;
use InvalidArgumentException;
use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;

class IdentityProviderCacheItem
{
    private string $updated;
    private string $entityId;
    private string $ssoLocation;
    /** @var string[] */
    private array $certificates;
    private bool $isAzureAd;

    /**
     * @param string[] $certificates
     */
    private function __construct(string $updated, string $entityId, string $ssoLocation, array $certificates, bool $isAzureAd)
    {
        $this->updated = $updated;
        $this->entityId = $entityId;
        $this->ssoLocation = $ssoLocation;
        $this->certificates = $certificates;
        $this->isAzureAd = $isAzureAd;
    }


    public static function fromIdentityProvider(DateTimeImmutable $updated, IdentityProviderInterface $identityProvider): self
    {
        return new self(
            $updated->format(DateTimeInterface::ATOM),
            (string)$identityProvider->getEntityId(),
            $identityProvider->getSsoLocation()->getUrl(),
            array_values(array_map(fn(Certificate $cert) => $cert->getCertData(), $identityProvider->getCertificates()->getCertificates())),
            $identityProvider->isAzureAD(),
        );
    }

    public static function fromString(string $data): self
    {
        $object = json_decode($data, true, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);

        if (!is_array($object)) {
            throw new RuntimeException("invalid idp cache data encountered");
        }

        return new self(
            $object['updated'] ?? '',
            $object['entity_id'] ?? '',
            $object['sso_location'] ?? '',
            $object['certificates'] ?? [],
            $object['is_azure_ad'] ?? false,
        );
    }


    public function toIdentityProvider(): IdentityProviderInterface
    {
        return new AzureMfaIdentityProvider(
            new EntityId($this->entityId),
            new Destination($this->ssoLocation),
            CertificateCollection::fromStringArray(
                array_map(
                    fn(string $certData) => Certificate::toPem($certData),
                    $this->certificates,
                )
            ),
            $this->isAzureAd,
        );
    }

    public function toString(): string
    {
        $object = [
            'updated' => $this->updated,
            'entity_id' => $this->entityId,
            'sso_location' => $this->ssoLocation,
            'certificates' => $this->certificates,
            'is_azure_ad' => $this->isAzureAd,
        ];

        $data = json_encode($object, JSON_PRETTY_PRINT, JSON_THROW_ON_ERROR);
        if ($data === false) {
            throw new RuntimeException('Failed to encode IdentityProviderCacheItem to JSON: ' . json_last_error_msg());
        }
        return $data;
    }
}
