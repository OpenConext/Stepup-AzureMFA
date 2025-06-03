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

namespace Surfnet\AzureMfa\Test\Unit\Domain\Institution\ValueObject;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Domain\Institution\Collection\EmailDomainCollection;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Institution;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionName;

class InstitutionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_happy_flow()
    {
        $emailDomainCollection = m::mock(EmailDomainCollection::class);

        $institutionName = new InstitutionName('stepup.example.com');
        $institution = new Institution($institutionName, $emailDomainCollection, true);

        $this->assertInstanceOf(Institution::class, $institution);
        $this->assertEquals($institutionName, $institution->getName());
        $this->assertEquals($emailDomainCollection, $institution->getEmailDomainCollection());
        $this->assertEquals(true, $institution->supportsMetadataUpdate());
    }

}