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

namespace Surfnet\AzureMfa\Infrastructure\Factory;

use Psr\Log\LoggerInterface;
use Surfnet\AzureMfa\Application\Service\Metadata\MetadataIdentityProviderService;
use Surfnet\AzureMfa\Domain\Exception\InstitutionNotFoundException;
use Surfnet\AzureMfa\Domain\Exception\InvalidCertificateException;
use Surfnet\AzureMfa\Domain\Institution\Collection\CertificateCollection;
use Surfnet\AzureMfa\Domain\Institution\Factory\ConfigurationFactory;
use Surfnet\AzureMfa\Domain\Institution\Factory\IdentityProviderFactoryInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Destination;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EntityId;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\IdentityProviderInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionConfigurationData;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionName;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\MetadataUrl;
use Surfnet\AzureMfa\Infrastructure\Entity\AzureMfaIdentityProvider;
use Throwable;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IdentityProviderFactory implements IdentityProviderFactoryInterface
{

    private ConfigurationFactory $configurationFactory;
    private MetadataIdentityProviderService $metadataIdentityProviderService;
    private LoggerInterface $logger;

    public function __construct(ConfigurationFactory $configurationFactory, MetadataIdentityProviderService $metadataIdentityProviderService, LoggerInterface $logger)
    {
        $this->configurationFactory = $configurationFactory;
        $this->metadataIdentityProviderService = $metadataIdentityProviderService;
        $this->logger = $logger;
    }

    public function build(
        InstitutionName $institutionName,
    ): IdentityProviderInterface {
        $this->logger->info(sprintf('Start updating the identity configuration for: %s', $institutionName->getInstitutionName()));

        $entity = $this->configurationFactory->getEntity($institutionName);

        if (!$entity instanceof InstitutionConfigurationData) {
            throw new InstitutionNotFoundException('The institution with name "' . $institutionName->getInstitutionName() . '" was not found in the configuration.');
        }

        if ($entity->hasMetadataUrl()) {
            $this->logger->info(sprintf('Update configuration for institution with the metadata url: %s', $institutionName->getInstitutionName()));

            try {
                $identityProvider = $this->metadataIdentityProviderService->fetch($entity);
            } catch (Throwable $e) {
                $this->logger->info(sprintf('An error occurred while fetching metadata for institution: %s %s', $institutionName->getInstitutionName(), $e->getMessage()));
                throw $e;
            }

            $this->logger->info(sprintf('Successfully updated with metadata for institution: %s, %d certificates found', $institutionName->getInstitutionName(), count($identityProvider->getCertificates()->getCertificates())));

            return $identityProvider;
        }

        $this->logger->info(sprintf('Update configuration for institution with the application config: %s', $institutionName->getInstitutionName()));

        if (!$entity->hasCertificates()) {
            throw new InvalidCertificateException('The entity provider must have at least one certificate.');
        }

        $entityId = new EntityId($entity->getEntityId());
        $ssoLocation = new Destination($entity->getDestination());
        $certificates = CertificateCollection::fromStringArray($entity->getCertificates());
        $isAzureAD = $entity->isAzureAd();

        $identityProvider = new AzureMfaIdentityProvider($entityId, $ssoLocation, $certificates, $isAzureAD);

        $this->logger->info(sprintf('Successfully updated with config for institution: %s, %d certificates found', $institutionName->getInstitutionName(), count($identityProvider->getCertificates()->getCertificates())));

        return $identityProvider;
    }
}
