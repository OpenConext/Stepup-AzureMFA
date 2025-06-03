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

use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Certificate;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\IdentityProviderInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionName;

class IdentityProviderCache
{
    private string $cacheDir;
    private LoggerInterface $logger;

    public function __construct(string $identityProviderCacheDir, LoggerInterface $logger)
    {
        $this->logger = $logger;

        $identityProviderCacheDir = realpath($identityProviderCacheDir);

        if (!$identityProviderCacheDir) {
            $this->logger->error(sprintf('The cache directory "%s" does not exist or is not a directory.', $identityProviderCacheDir));
            throw new InvalidArgumentException(sprintf('The cache directory "%s" does not exist or is not a directory.', $identityProviderCacheDir));
        }

        $this->cacheDir = $identityProviderCacheDir;
    }

    public function set(InstitutionName $institutionName, IdentityProviderInterface $identityProvider): void
    {
        $updated = new DateTimeImmutable();

        $object = IdentityProviderCacheItem::fromIdentityProvider($updated, $identityProvider);
        $data = $object->toString();

        $this->logger->info(sprintf(
            'Caching identity provider for institution: %s with values %s, %s, %s',
            $institutionName->getInstitutionName(),
            $identityProvider->getEntityId() ?? '',
            $identityProvider->getSsoLocation()->getUrl(),
            implode(' ', array_map(fn(Certificate $cert) => $cert->getCertData(), $identityProvider->getCertificates()->getCertificates())),
        ));

        $filePath = $this->cacheDir . '/' . $institutionName->getInstitutionName() . '.cache';
        file_put_contents($filePath, $data);
    }

    public function get(InstitutionName $institutionName): ?IdentityProviderInterface
    {
        $filePath = $this->cacheDir . '/' . $institutionName->getInstitutionName() . '.cache';
        if (!is_file($filePath)) {
            $this->logger->error(sprintf('The cache file does not exist for institution: %s', $institutionName->getInstitutionName()));
            return null;
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            return null;
        }

        $object = IdentityProviderCacheItem::fromString($data);
        return  $object->toIdentityProvider();
    }
}
