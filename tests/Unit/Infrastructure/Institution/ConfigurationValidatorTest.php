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

namespace Surfnet\AzureMfa\Test\Unit\Infrastructure\Institution;

use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Infrastructure\Institution\ConfigurationValidator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;

/**
 * Integration test for the ConfigurationValidator
 */
class ConfigurationValidatorTest extends TestCase
{
    /**
     * @var ConfigurationValidator
     */
    private $validator;

    /**
     * @var string
     */
    private $fixtureDirectory;

    protected function setUp() : void
    {
        $this->fixtureDirectory = dirname(__DIR__, 3) . '/fixtures/';
    }

    public function test_happy_flow() : void
    {
        $data = $this->setUpValidator('vanilla.yaml');
        $processedData = $this->validator->process();
        // $data is reset because the root node is chopped of in the validation output.
        $this->assertEquals(reset($data), $processedData);
    }

    /**
     * @dataProvider provideInvalidConfigurations
     */
    public function test_configuration_failures($fileName, $expectedExceptionMessage) : void
    {
        $this->setUpValidator($fileName);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->validator->process();
    }

    /**
     * Not the most elegant of methods, tasked with:
     *  - Setting up the validator
     *  - Returning the array parsed from the specified Yaml config file
     */
    private function setUpValidator(string $input) : array
    {
        $configuration = file_get_contents(
            $this->fixtureDirectory.sprintf('institution_configurations/%s', $input)
        );
        $data = Yaml::parse($configuration);
        $configurationDefinition = new ConfigurationDefinition();
        $this->validator = new ConfigurationValidator($configurationDefinition, $data);

        return $data;
    }

    public function provideInvalidConfigurations() : array
    {
        return [
            'invalid destination' => [
                'invalid_destination.yaml',
                'Unrecognized option "destinatio" under "institution_configuration.institutions.institution-a.example.com". Did you mean "destination"?'
            ],
            'invalid email domains' => [
                'invalid_email_domains.yaml',
                'Unrecognized option "mail_domain" under "institution_configuration.institutions.institution-a.example.com". Did you mean "email_domains"?'
            ],
            'invalid second entry' => [
                'invalid_second_entry.yaml',
                'Unrecognized options "hostname, username, password" under "institution_configuration.institutions.institution-b.example.com". Available options are "destination", "email_domains".'
            ],
            'missing destination' => [
                'missing_destination.yaml',
                'The child node "destination" at path "institution_configuration.institutions.institution-a.example.com" must be configured.'
            ],
            'missing email domains' => [
                'missing_email_domains.yaml',
                'The child node "email_domains" at path "institution_configuration.institutions.institution-a.example.com" must be configured.'
            ],
        ];
    }
}
