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

namespace Surfnet\AzureMfa\Domain\Institution\Collection;

use ArrayIterator;
use IteratorAggregate;
use Surfnet\AzureMfa\Domain\Exception\InvalidEmailDomainException;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EmailDomainInterface;

class EmailDomainCollection implements IteratorAggregate
{
    /**
     * @var EmailDomainInterface[]
     */
    private $emailDomains = [];

    public function __construct(array $domains)
    {
        foreach ($domains as $domain) {
            if (!$domain instanceof EmailDomainInterface) {
                throw new InvalidEmailDomainException(
                    'The provided domains contain an invalid Domain that does not implement the EmailDomainInterface'
                );
            }
        }
        $this->emailDomains = $domains;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->emailDomains);
    }
}
