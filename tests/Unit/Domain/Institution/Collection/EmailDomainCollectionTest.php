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

namespace Surfnet\AzureMfa\Test\Unit\Domain\Institution\Collection;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\InvalidEmailDomainException;
use Surfnet\AzureMfa\Domain\Institution\Collection\EmailDomainCollection;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EmailDomain;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EmailDomainWildcard;

class EmailDomainCollectionTest extends TestCase
{
    public function test_happy_flow()
    {
        $items = [
            m::mock(EmailDomain::class),
            m::mock(EmailDomain::class),
            m::mock(EmailDomainWildcard::class),
            m::mock(EmailDomain::class),
            m::mock(EmailDomainWildcard::class),
        ];

        $collection = new EmailDomainCollection($items);
        $this->assertInstanceOf(EmailDomainCollection::class, $collection);
    }

    public function test_rejects_invalid_collection_items()
    {
        $items = [
            m::mock(EmailDomain::class),
            m::mock(EmailDomain::class),
            m::mock(EmailDomainWildcard::class),
            m::mock(EmailDomain::class),
            m::mock(EmailDomainWildcard::class),
            m::mock(EmailAddress::class),
        ];

        $this->expectException(InvalidEmailDomainException::class);
        $this->expectExceptionMessage('The provided domains contain an invalid Domain that does not implement the EmailDomainInterface');
        new EmailDomainCollection($items);
    }
}
