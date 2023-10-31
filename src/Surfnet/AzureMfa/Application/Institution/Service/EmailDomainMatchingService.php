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

namespace Surfnet\AzureMfa\Application\Institution\Service;

use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\InstitutionNotFoundException;
use Surfnet\AzureMfa\Domain\Institution\Configuration\InstitutionConfigurationInterface;
use Surfnet\AzureMfa\Domain\Institution\Factory\ConfigurationFactory;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Institution;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionConfiguration;

class EmailDomainMatchingService
{
    private InstitutionConfigurationInterface $configuration;

    public function __construct(ConfigurationFactory $factory)
    {
        $this->configuration = $factory->build();
    }

    /**
     * Based on the email domain, this method will find a matching institution
     * @param EmailAddress $emailAddress
     * @return Institution|null
     */
    public function findInstitutionByEmail(EmailAddress $emailAddress) :? Institution
    {
        $institutions = $this->configuration->getInstitutions();
        try {
            return $institutions->getByEmailDomain($emailAddress);
        } catch (InstitutionNotFoundException $e) {
            return null;
        }
    }
}
