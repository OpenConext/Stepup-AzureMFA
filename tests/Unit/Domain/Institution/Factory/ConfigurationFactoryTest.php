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

namespace Surfnet\AzureMfa\Test\Unit\Domain\Institution\Factory;

use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Domain\Institution\Collection\EmailDomainCollection;
use Surfnet\AzureMfa\Domain\Institution\Collection\InstitutionCollection;
use Surfnet\AzureMfa\Domain\Institution\Factory\ConfigurationFactory;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Destination;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Institution;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionConfiguration;
use Surfnet\AzureMfa\Infrastructure\Institution\ConfigurationDefinition;
use Surfnet\AzureMfa\Infrastructure\Institution\ConfigurationValidator;
use Symfony\Component\Yaml\Yaml;

/**
 * Integration test for the ConfigurationFactory
 */
class ConfigurationFactoryTest extends TestCase
{
    private $fixtureDirectory;

    /**
     * @var ConfigurationValidator
     */
    private $validator;

    protected function setUp() : void
    {
        $this->fixtureDirectory = dirname(__DIR__, 4) . '/fixtures/';
    }

    public function test_happy_flow() : void
    {
        $this->setUpValidator('factory.yaml');
        $factory = new ConfigurationFactory($this->validator);

        $configuration = $factory->build();

        $this->assertInstanceOf(InstitutionConfiguration::class, $configuration);
        $this->assertInstanceOf(InstitutionCollection::class, $configuration->getInstitutions());

        $institutions = $configuration->getInstitutions();
        $this->assertInstanceOf(Institution::class, $institutions->getByName('institution-a.example.com'));
        $this->assertInstanceOf(Institution::class, $institutions->getByName('harting-college.nl'));

        $institutionA = $institutions->getByName('institution-a.example.com');
        $harting = $institutions->getByName('harting-college.nl');

        $this->assertEquals('institution-a.example.com', $institutionA->getName());
        $this->assertInstanceOf(Destination::class, $institutionA->getDestination());
        $this->assertEquals('https://adfs.stepup.example.com/adfs/ls/', $institutionA->getDestination()->getUrl());

        // Todo: at this point we do not know how we want to work with this collection. Update test coverage once this becomes clear!
        $this->assertInstanceOf(EmailDomainCollection::class, $institutionA->getEmailDomainCollection());

        $this->assertEquals('harting-college.nl', $harting->getName());
        $this->assertInstanceOf(Destination::class, $harting->getDestination());
        $this->assertEquals('https://adfs.harting-college.nl/adfs/ls/', $harting->getDestination()->getUrl());
        $this->assertInstanceOf(EmailDomainCollection::class, $harting->getEmailDomainCollection());
    }

    private function setUpValidator(string $input)
    {
        $configuration = file_get_contents(
            $this->fixtureDirectory.sprintf('institution_configurations/%s', $input)
        );
        $data = Yaml::parse($configuration);
        $configurationDefinition = new ConfigurationDefinition();
        $this->validator = new ConfigurationValidator($configurationDefinition, $data);
    }
}
