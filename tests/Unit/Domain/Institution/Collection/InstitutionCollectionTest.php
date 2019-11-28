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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Domain\Exception\InstitutionNotFoundException;
use Surfnet\AzureMfa\Domain\Exception\InvalidInstitutionException;
use Surfnet\AzureMfa\Domain\Institution\Collection\InstitutionCollection;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Institution;

class InstitutionCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_happy_flow()
    {
        $collection = new InstitutionCollection();
        $collection->add($this->buildInstitutionMock('mock1'));
        $collection->add($this->buildInstitutionMock('mock2'));
        $collection->add($this->buildInstitutionMock('mock3'));

        $this->assertInstanceOf(InstitutionCollection::class, $collection);
    }

    public function test_duplicate_institution_names_are_not_allowed()
    {
        $collection = new InstitutionCollection();
        $collection->add($this->buildInstitutionMock('mock1'));
        $collection->add($this->buildInstitutionMock('mock2'));
        $this->expectException(InvalidInstitutionException::class);
        $this->expectExceptionMessage('An institution with this name ("mock1") has already been added to the collection.');
        $collection->add($this->buildInstitutionMock('mock1'));
    }

    public function test_get_by_name()
    {
        $collection = new InstitutionCollection();
        $collection->add($this->buildInstitutionMock('mock1'));
        $collection->add($this->buildInstitutionMock('mock2'));

        $institution1 = $collection->getByName('mock1');
        $this->assertInstanceOf(Institution::class, $institution1);
        $this->assertEquals('mock1', $institution1->getName());
    }

    public function test_rejects_invalid_name_when_using_get_by_name()
    {
        $collection = new InstitutionCollection();
        $collection->add($this->buildInstitutionMock('mock1'));
        $collection->add($this->buildInstitutionMock('mock2'));

        $this->expectException(InstitutionNotFoundException::class);
        $this->expectExceptionMessage('Unable to get the institution identified by "stepup.exampel.com".');
        $collection->getByName('stepup.exampel.com');
    }

    private function buildInstitutionMock(string $name) : Institution
    {
        $institution = m::mock(Institution::class);
        $institution
            ->shouldReceive('getName')
            ->andReturn($name);
        return $institution;
    }
}
