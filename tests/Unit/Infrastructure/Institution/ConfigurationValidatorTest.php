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
use Surfnet\AzureMfa\Infrastructure\Institution\ConfigurationDefinition;
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

    public function test_happy_flow_fixed_cert() : void
    {
        $data = $this->setUpValidator('vanilla_fixed.yaml');
        $processedData = $this->validator->process();
        // $data is reset because the root node is chopped of in the validation output.
        $this->assertEquals(reset($data), $processedData);
    }

    public function test_happy_flow_metadata_cert() : void
    {
        $data = $this->setUpValidator('vanilla_metadata_url.yaml');
        $processedData = $this->validator->process();
        // $data is reset because the root node is chopped of in the validation output.


        $expect = [
            'institutions' =>
                [
                    'institution-a.example.com' =>
                        [
                            'entity_id' => 'https://adfs.stepup.example.com/',
                            'email_domains' =>
                                [
                                    'stepup.example.com',
                                    '*.example.com',
                                ],
                            'is_azure_ad' => false,
                            'metadata_url' => 'https://adfs.stepup.example.com/metadata',
                            'certificates' => [],
                        ],
                    'harting-college.nl' =>
                        [
                            'entity_id' => 'https://adfs.harting-college.nl/',
                            'email_domains' =>
                                [
                                    'harting-college.nl',
                                ],
                            'is_azure_ad' => false,
                            'metadata_url' => 'https://adfs.harting-college.nl//metadata',
                            'certificates' => [],
                        ],
                ],
        ];

        $this->assertEquals($expect, $processedData);
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
                'invalid_sso_location.yaml',
                'Unrecognized option "sso_locatio" under "institution_configuration.institutions.institution-a.example.com". Did you mean "sso_location"?'
            ],
            'invalid email domains' => [
                'invalid_email_domains.yaml',
                'Unrecognized option "mail_domain" under "institution_configuration.institutions.institution-a.example.com". Did you mean "email_domains"?'
            ],
            'invalid second entry' => [
                'invalid_second_entry.yaml',
                'Unrecognized options "hostname, username, password" under "institution_configuration.institutions.institution-b.example.com". Available options are "certificates", "email_domains", "entity_id", "is_azure_ad", "metadata_url", "sso_location".'
            ],
            'invalid entity id' => [
                'invalid_entity_id.yaml',
                'Invalid type for path "institution_configuration.institutions.institution-a.example.com.entity_id". Expected "scalar", but got "array".'
            ],
            'invalid certificates' => [
                'invalid_certificates.yaml',
                'Invalid type for path "institution_configuration.institutions.institution-a.example.com.certificates". Expected "array", but got "string"'
            ],
            'missing email domains' => [
                'missing_email_domains.yaml',
                'The child config "email_domains" under "institution_configuration.institutions.institution-a.example.com" must be configured.'
            ],
        ];
    }
}
