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

namespace Surfnet\AzureMfa\Test\Unit\Application\Institution\Service;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Application\Institution\Service\EmailDomainMatchingService;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\InstitutionNotFoundException;
use Surfnet\AzureMfa\Domain\Institution\Collection\InstitutionCollection;
use Surfnet\AzureMfa\Domain\Institution\Factory\ConfigurationFactory;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Institution;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionConfiguration;

class EmailDomainMatchingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_happy_flow()
    {
        $email = m::mock(EmailAddress::class);

        $factory = $this->buildConfigurationFactory($email);
        $matcher = new EmailDomainMatchingService($factory);

        $institution = $matcher->findInstitutionByEmail($email);

        $this->assertInstanceOf(Institution::class, $institution);
    }

    public function test_not_found()
    {
        $email = m::mock(EmailAddress::class);
        $institutionConfig = m::mock(InstitutionConfiguration::class);

        $institutionCollection = m::mock(InstitutionCollection::class);

        $institutionCollection
            ->shouldReceive('getByEmailDomain')
            ->with($email)
            ->once()
            ->andThrow(InstitutionNotFoundException::class);

        $institutionConfig
            ->shouldReceive('getInstitutions')
            ->andReturn($institutionCollection);

        $factory = m::mock(ConfigurationFactory::class);
        $factory
            ->shouldReceive('build')
            ->andReturn($institutionConfig);

        $matcher = new EmailDomainMatchingService($factory);

        $this->assertNull($matcher->findInstitutionByEmail($email));
    }

    private function buildConfigurationFactory($email) : ConfigurationFactory
    {
        $institutionConfig = m::mock(InstitutionConfiguration::class);

        $institutionCollection = m::mock(InstitutionCollection::class);
        $institution = m::mock(Institution::class);

        $institutionCollection
            ->shouldReceive('getByEmailDomain')
            ->with($email)
            ->once()
            ->andReturn($institution);

        $institutionConfig
            ->shouldReceive('getInstitutions')
            ->andReturn($institutionCollection);

        $factory = m::mock(ConfigurationFactory::class);
        $factory
            ->shouldReceive('build')
            ->andReturn($institutionConfig);

        return $factory;
    }
}