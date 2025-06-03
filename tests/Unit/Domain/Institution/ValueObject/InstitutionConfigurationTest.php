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
use Surfnet\AzureMfa\Domain\Exception\InvalidInstitutionConfigurationException;
use Surfnet\AzureMfa\Domain\Institution\Collection\InstitutionCollection;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Institution;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionConfiguration;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionName;

class InstitutionConfigurationTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_happy_flow() : void
    {
        $institutions = [
            $this->buildInstitutionMock('mock1'),
            $this->buildInstitutionMock('mock2'),
        ];

        $institutionConfiguration = new InstitutionConfiguration($institutions);
        $this->assertInstanceOf(InstitutionConfiguration::class, $institutionConfiguration);
        $this->assertInstanceOf(InstitutionCollection::class, $institutionConfiguration->getInstitutions());
    }

    public function test_rejects_invalid_institution_entries() : void
    {
        $institutions = [
            $this->buildInstitutionMock('mock1'),
            $this->buildInstitutionMock('mock2'),
            m::mock(InstitutionConfiguration::class),
        ];

        $this->expectException(InvalidInstitutionConfigurationException::class);
        $this->expectExceptionMessage('Every entry of the institution array must be of type: Institution');
        new InstitutionConfiguration($institutions);
    }

    private function buildInstitutionMock(string $name) : Institution
    {
        $institution = m::mock(Institution::class);
        $institution
            ->shouldReceive('getName')
            ->andReturn(new InstitutionName($name));
        return $institution;
    }
}