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

namespace Surfnet\AzureMfa\Domain\Institution\Factory;

use Surfnet\AzureMfa\Domain\Institution\Collection\EmailDomainCollection;
use Surfnet\AzureMfa\Domain\Institution\Configuration\ConfigurationValidatorInterface;
use Surfnet\AzureMfa\Domain\Institution\Configuration\InstitutionConfigurationInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EmailDomain;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EmailDomainInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EmailDomainWildcard;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EntityId;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Institution;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionConfiguration;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionConfigurationData;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionName;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) - This factory, by design has high coupling. Coupling could be
 * reduced by introducing additional factories, but this would only complicate the simple creation logic that is now
 * used
 */
class ConfigurationFactory
{
    private readonly array $configurationData;

    /**
     * @var InstitutionConfigurationData[]
     */
    private array $entities;

    public function __construct(
        ConfigurationValidatorInterface $validator,
    ) {
        $this->configurationData = $validator->process()['institutions'];
        $this->entities = [];
    }

    public function build(): InstitutionConfigurationInterface
    {
        $institutions = [];
        foreach ($this->configurationData as $institutionName => $institutionData) {
            $institutionName = new InstitutionName($institutionName);

            $emailDomains = $this->buildEmailDomains($institutionData['email_domains']);

            $institutions[$institutionName->getInstitutionName()] = new Institution(
                $institutionName,
                $emailDomains,
                !empty($institutionData['metadata_url']),
            );

            $this->entities[$institutionName->getInstitutionName()] = new InstitutionConfigurationData(
                $institutionData['entity_id'] ?? '',
                $institutionData['sso_location'] ?? '',
                $institutionData['certificates'] ?? [],
                $institutionData['is_azure_ad'] ?? false,
                $institutionData['metadata_url'] ?? '',
            );
        }

        return new InstitutionConfiguration($institutions);
    }

    /**
     * @param array<string> $emailDomains
     */
    private function buildEmailDomains(array $emailDomains): EmailDomainCollection
    {
        $domainCollection = [];
        foreach ($emailDomains as $domain) {
            $domainCollection[] = $this->buildEmailDomain($domain);
        }
        return new EmailDomainCollection($domainCollection);
    }

    private function buildEmailDomain(string $domain): EmailDomainInterface
    {
        if (substr($domain, 0, 1) === EmailDomainWildcard::WILDCARD_CHARACTER) {
            return new EmailDomainWildcard($domain);
        }
        return new EmailDomain($domain);
    }

    /**
     * @return InstitutionConfigurationData|null
     */
    public function getEntity(InstitutionName $institutionName): ?InstitutionConfigurationData
    {
        return $this->entities[$institutionName->getInstitutionName()] ?? null;
    }
}
