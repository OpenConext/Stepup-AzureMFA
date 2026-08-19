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
            array_values(array_map(static fn(Certificate $cert) => $cert->getCertData(), $identityProvider->getCertificates()->getCertificates())),
            $identityProvider->isAzureAD(),
        );
    }

    public static function fromString(string $data): self
    {
        $object = json_decode($data, true, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);

        if (!is_array($object)) {
            throw new RuntimeException("invalid idp cache data encountered");
        }

        if (!$object['updated']) {
            $object['updated'] = '';
        }

        return new self(
            self::requireString($object['updated'], 'updated'),
            self::requireString($object['entity_id'] ?? '', 'entity_id'),
            self::requireString($object['sso_location'] ?? '', 'sso_location'),
            self::requireStringArray($object['certificates'] ?? [], 'certificates'),
            self::requireBool($object['is_azure_ad'] ?? false, 'is_azure_ad'),
        );
    }

    private static function requireString(mixed $value, string $field): string
    {
        if (!is_string($value)) {
            throw new RuntimeException(sprintf("'%s' must be a string", $field));
        }
        return $value;
    }

    private static function requireBool(mixed $value, string $field): bool
    {
        if (!is_bool($value)) {
            throw new RuntimeException(sprintf("'%s' must be a boolean", $field));
        }
        return $value;
    }

    /**
     * @return string[]
     */
    private static function requireStringArray(mixed $value, string $field): array
    {
        if (!is_array($value)) {
            throw new RuntimeException(sprintf("'%s' must be an array", $field));
        }
        return array_map(
            static function ($item) use ($field): string {
                if (!is_string($item)) {
                    throw new RuntimeException(sprintf("'%s' must be an array of strings", $field));
                }
                return $item;
            },
            $value,
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

        return json_encode($object, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
