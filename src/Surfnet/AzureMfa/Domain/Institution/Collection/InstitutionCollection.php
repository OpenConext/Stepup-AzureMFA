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

namespace Surfnet\AzureMfa\Domain\Institution\Collection;

use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\InstitutionNotFoundException;
use Surfnet\AzureMfa\Domain\Exception\InvalidInstitutionException;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EmailDomainInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Institution;

class InstitutionCollection
{
    /**
     * @var Institution[]
     */
    private $institutions = [];

    public function add(Institution $institution): void
    {
        if (array_key_exists($institution->getName(), $this->institutions)) {
            throw new InvalidInstitutionException(
                sprintf(
                    'An institution with this name ("%s") has already been added to the collection.',
                    $institution->getName()
                )
            );
        }
        $this->institutions[$institution->getName()] = $institution;
    }

    public function getByName(string $name): Institution
    {
        if (array_key_exists($name, $this->institutions) === false) {
            throw new InstitutionNotFoundException(sprintf('Unable to get the institution identified by "%s".', $name));
        }
        return $this->institutions[$name];
    }

    public function getByEmailDomain(EmailAddress $address): Institution
    {
        foreach ($this->institutions as $institution) {
            $domainCollection = $institution->getEmailDomainCollection();
            /** @var EmailDomainInterface $domain */
            foreach ($domainCollection as $domain) {
                if ($domain->domainMatches($address)) {
                    return $institution;
                }
            }
        }
        throw new InstitutionNotFoundException('Unable to find an institution that matches the provided email address');
    }
}
