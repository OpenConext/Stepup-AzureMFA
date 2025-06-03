<?php

declare(strict_types = 1);

/**
 * Copyright 2025 SURFnet B.V.
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

use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Application\Exception\InvalidMfaMetadataUrlResponseException;
use Surfnet\AzureMfa\Application\Service\Metadata\MetadataIdentityProviderService;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionConfigurationData;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\MetadataUrl;
use Surfnet\AzureMfa\Infrastructure\Entity\AzureMfaIdentityProvider;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class MetadataIdentityProviderServiceTest extends TestCase
{

    protected function setUp() : void
    {
        $this->fixtureDirectory = dirname(__DIR__, 4) . '/fixtures/';
    }

    public function test_metadata_identity_provider() {

        $identityProvider = $this->handleRequest('metadata.xml');

        $this->assertEquals('https://sts.windows.net/8cd24984-0aa3-4fc5-b09b-4d6ecfaa58fb/', $identityProvider->getEntityId());
        $this->assertEquals('https://login.microsoftonline.com/8cd24984-0aa3-4fc5-b09b-4d6ecfaa58fb/saml2', $identityProvider->getSsoUrl());
        $this->assertEquals(false, $identityProvider->isAzureAd());
        $this->assertNotEmpty($identityProvider->getCertificates());
        $this->assertEquals(str_replace("\n", '',
'MIIC8DCCAdigAwIBAgIQSqj0oiRjb5NDX93WGxJT5jANBgkqhkiG9w0BAQsFADA0
MTIwMAYDVQQDEylNaWNyb3NvZnQgQXp1cmUgRmVkZXJhdGVkIFNTTyBDZXJ0aWZp
Y2F0ZTAeFw0yNTA1MjgwODAxNDJaFw0yODA1MjgwODAxMzlaMDQxMjAwBgNVBAMT
KU1pY3Jvc29mdCBBenVyZSBGZWRlcmF0ZWQgU1NPIENlcnRpZmljYXRlMIIBIjAN
BgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA50IxkJnLYuk19C8So2fqedanSY4x
8gNUlI+nd4sBJmI8Shqp8b7YUm2cogZB5yBEIbWKMiUX8XU0WhrBM0NmLI6fYOY8
jNmsHviNkF+mgKgvScV1DmgDiCHIX5qikju2d0SPq9go3ZH0CGMG5j5B6hQGnbdE
QGN5DHdh5PR1/uRRSXIyV7tn6v2c1+CVGZdZdMQaUyS8Z4G1C84goQx68CAd3esh
NAbwU2Bgf715Y7ihhoLV9V8yOKVBEiIzyofCcc8wOdpTq6Wx5NpUfGQB2BeMWFq3
QoI1539Ju/cV5cGnLVEitggdrd8NUwumwc+6Wrjs8yBYTaHmnT5noNz3DQIDAQAB
MA0GCSqGSIb3DQEBCwUAA4IBAQAjgwCboB7cHgISvuZD1FATmEJOu54cfIrIV43V
9DIZqR0vI10GHl0Qd0OSiSWmYTZaZR8Q96jdAR2BAb0gWBgd8hfXQtMEiBT05Xn0
XGpEZoMJOnGPeiwmBQuXOCzDIvU5nZqXUwsp7B8CzxBnWrsn6nkF3mD+8FQ6fWji
ZmxtE5ct40NcR9fnlZgWfKId+QBg4gm/EiFh8HWAs2NP6ilryAKzFGmZLN8EU410
4dVPKlkKFJWqV5sNUIdwvS6uVsoC27OvRQi7iNzPQitkLfPqJnqE5yZFhWtu+iH0
cmN+8BiQ/FXlF8xjuNq5LSLRiawSe8ut81Lhv5tjZgKWwmiR
'), $identityProvider->getCertificates()->first()->getCertData());
    }


    /**
     * @dataProvider provideInvalidMetadata
     */
    public function test_metadata_identity_provider_failures(string $input, string $expectedException, string $expectedMessage) : void
    {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);


        $this->handleRequest($input);
    }

    private function handleRequest(string $fixture): AzureMfaIdentityProvider
    {
        $metadataBody = file_get_contents($this->fixtureDirectory . 'metadata/'. $fixture);

        $responses = [new MockResponse($metadataBody, ['http_code' => 200])];
        $client = new MockHttpClient($responses);

        $service = new MetadataIdentityProviderService($client);

        $institutionConfigurationData = new InstitutionConfigurationData(
            '',
            '',
            [],
            false,
            'https://example.com/metadata',
        );

        $identityProvider = $service->fetch($institutionConfigurationData);

        $this->assertInstanceOf(AzureMfaIdentityProvider::class, $identityProvider);

        return  $identityProvider;
    }

    public function provideInvalidMetadata(): array {
        return [
            'invalid xml' => [
                'fixture' => 'metadata_invalid_xml.xml',
                'expectedException' => InvalidMfaMetadataUrlResponseException::class,
                'expectedMessage' => 'Failed to parse metadata XML.'
            ],
            'missing entity descriptor' => [
                'fixture' => 'metadata_missing_entity_descriptor.xml',
                'expectedException' => InvalidMfaMetadataUrlResponseException::class,
                'expectedMessage' => 'EntityDescriptor not found in metadata.'
            ],
            'missing entity id' => [
                'fixture' => 'metadata_missing_entity_id.xml',
                'expectedException' => InvalidMfaMetadataUrlResponseException::class,
                'expectedMessage' => 'EntityID not found in metadata.'
            ],
            'missing certs' => [
                'fixture' => 'metadata_missing_certs.xml',
                'expectedException' => InvalidMfaMetadataUrlResponseException::class,
                'expectedMessage' => 'Certificates not found in metadata.'
            ],
            'missing sso location' => [
                'fixture' => 'metadata_missing_sso_location.xml',
                'expectedException' => InvalidMfaMetadataUrlResponseException::class,
                'expectedMessage' => 'A valid SingleSignOnService Location with post binding not found in metadata.'
            ],
            'invalid sso_location' => [
                'fixture' => 'metadata_invalid_sso_location.xml',
                'expectedException' => InvalidMfaMetadataUrlResponseException::class,
                'expectedMessage' => 'A valid SingleSignOnService Location with post binding not found in metadata.'
            ],
        ];
    }
}