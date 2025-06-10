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

namespace Surfnet\AzureMfa\Infrastructure\Factory;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Surfnet\AzureMfa\Application\Service\Metadata\MetadataIdentityProviderService;
use Surfnet\AzureMfa\Domain\Institution\Collection\CertificateCollection;
use Surfnet\AzureMfa\Domain\Institution\Factory\ConfigurationFactory;
use Surfnet\AzureMfa\Domain\Institution\Factory\IdentityProviderFactoryInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Certificate;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Destination;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EntityId;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionConfigurationData;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionName;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\MetadataUrl;
use Surfnet\AzureMfa\Infrastructure\Entity\AzureMfaIdentityProvider;

class IdentityProviderFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_build_from_config()
    {
        $expectCert =
            'MIICzjCCAjegAwIBAgIUInXYmn/hxq0qmy5NJlxukNZ4qQowDQYJKoZIhvcNAQEL' .
            'BQAweTELMAkGA1UEBhMCRkIxDzANBgNVBAgMBkZvb2JhcjEMMAoGA1UEBwwDQmFy' .
            'MQwwCgYDVQQKDANGb28xDzANBgNVBAsMBkZvb2JhcjEMMAoGA1UEAwwDRm9vMR4w' .
            'HAYJKoZIhvcNAQkBFg9mb29AZXhhbXBsZS5jb20wHhcNMTkxMjEyMTIzMzMyWhcN' .
            'MjQxMjEwMTIzMzMyWjB5MQswCQYDVQQGEwJGQjEPMA0GA1UECAwGRm9vYmFyMQww' .
            'CgYDVQQHDANCYXIxDDAKBgNVBAoMA0ZvbzEPMA0GA1UECwwGRm9vYmFyMQwwCgYD' .
            'VQQDDANGb28xHjAcBgkqhkiG9w0BCQEWD2Zvb0BleGFtcGxlLmNvbTCBnzANBgkq' .
            'hkiG9w0BAQEFAAOBjQAwgYkCgYEA9Of3o788Pp1VfOOoZrTXs2gg+b3GEBw6VgTz' .
            'XA1xtr1oKAygJliS74UaK01k/e1bNwZvNZPAV26hKU5UD3g78tRlOGV2W11aWh8X' .
            'anfnhno2GH18wHeaOTHehgpVpkB4a9R2CztoRC0mjp6Z7ya4aZYbFLijxLsc1Z5P' .
            'kAD+Bi8CAwEAAaNTMFEwHQYDVR0OBBYEFLXFn8qQTnp2qbE0Bq5nvkspgFnbMB8G' .
            'A1UdIwQYMBaAFLXFn8qQTnp2qbE0Bq5nvkspgFnbMA8GA1UdEwEB/wQFMAMBAf8w' .
            'DQYJKoZIhvcNAQELBQADgYEAKXRNgkz0DUuS+EDhzX3VtUGi6YR75hFESYk+BdGU' .
            '4TlAI+UjVi8XOQeeCV6XwDKdeQla3t0JMBZqdor9vbo3BLNq7Xd7R36PnGNspNgZ' .
            'mXmePwbNrp+8JXae3AULMa7uR9Ai/eLESFcmIM79duCOrgmm5Nj11kIfHvA2qrfd' .
            'BYY=';

        $certData = Certificate::toPem($expectCert);

        $configFactory = m::mock(ConfigurationFactory::class);
        $metadataIdentityProviderService = m::mock(MetadataIdentityProviderService::class);

        $logger = new NullLogger();
        $factory = new IdentityProviderFactory($configFactory, $metadataIdentityProviderService, $logger);
        $this->assertInstanceOf(IdentityProviderFactoryInterface::class, $factory);

        $institutionName = new InstitutionName('institutionName');

        $entityId = m::mock(EntityId::class);
        $entityId
            ->shouldReceive('getEntityId')
            ->andReturn('entityId');

        $ssoLocation = m::mock(Destination::class);
        $ssoLocation
            ->shouldReceive('getUrl')
            ->andReturn('https://sso-location.example.com');

        $certCollection = m::mock(CertificateCollection::class);
        $certCollection
            ->shouldReceive('first->getCertData')
            ->andReturn('certData');

        $configFactory->shouldReceive('getEntity')
            ->with($institutionName)
            ->andReturn(new InstitutionConfigurationData(
                $entityId->getEntityId(),
                $ssoLocation->getUrl(),
                [$certData],
                false,
                '',
            ));

        $identityProvider = $factory->build($institutionName);
        $this->assertEquals('entityId', $identityProvider->getEntityId());
        $this->assertEquals('https://sso-location.example.com', $identityProvider->getSsoLocation()->getUrl());
        $this->assertEquals($expectCert, $identityProvider->getCertificates()->first()->getCertData());
        $this->assertEquals(false, $identityProvider->isAzureAD());

    }

    public function test_build_from_metadata_url()
    {
        $expectCert =
            'MIICzjCCAjegAwIBAgIUInXYmn/hxq0qmy5NJlxukNZ4qQowDQYJKoZIhvcNAQEL' .
            'BQAweTELMAkGA1UEBhMCRkIxDzANBgNVBAgMBkZvb2JhcjEMMAoGA1UEBwwDQmFy' .
            'MQwwCgYDVQQKDANGb28xDzANBgNVBAsMBkZvb2JhcjEMMAoGA1UEAwwDRm9vMR4w' .
            'HAYJKoZIhvcNAQkBFg9mb29AZXhhbXBsZS5jb20wHhcNMTkxMjEyMTIzMzMyWhcN' .
            'MjQxMjEwMTIzMzMyWjB5MQswCQYDVQQGEwJGQjEPMA0GA1UECAwGRm9vYmFyMQww' .
            'CgYDVQQHDANCYXIxDDAKBgNVBAoMA0ZvbzEPMA0GA1UECwwGRm9vYmFyMQwwCgYD' .
            'VQQDDANGb28xHjAcBgkqhkiG9w0BCQEWD2Zvb0BleGFtcGxlLmNvbTCBnzANBgkq' .
            'hkiG9w0BAQEFAAOBjQAwgYkCgYEA9Of3o788Pp1VfOOoZrTXs2gg+b3GEBw6VgTz' .
            'XA1xtr1oKAygJliS74UaK01k/e1bNwZvNZPAV26hKU5UD3g78tRlOGV2W11aWh8X' .
            'anfnhno2GH18wHeaOTHehgpVpkB4a9R2CztoRC0mjp6Z7ya4aZYbFLijxLsc1Z5P' .
            'kAD+Bi8CAwEAAaNTMFEwHQYDVR0OBBYEFLXFn8qQTnp2qbE0Bq5nvkspgFnbMB8G' .
            'A1UdIwQYMBaAFLXFn8qQTnp2qbE0Bq5nvkspgFnbMA8GA1UdEwEB/wQFMAMBAf8w' .
            'DQYJKoZIhvcNAQELBQADgYEAKXRNgkz0DUuS+EDhzX3VtUGi6YR75hFESYk+BdGU' .
            '4TlAI+UjVi8XOQeeCV6XwDKdeQla3t0JMBZqdor9vbo3BLNq7Xd7R36PnGNspNgZ' .
            'mXmePwbNrp+8JXae3AULMa7uR9Ai/eLESFcmIM79duCOrgmm5Nj11kIfHvA2qrfd' .
            'BYY=';

        $certData = Certificate::toPem($expectCert);

        $configFactory = m::mock(ConfigurationFactory::class);
        $metadataIdentityProviderService = m::mock(MetadataIdentityProviderService::class);

        $logger = new NullLogger();
        $factory = new IdentityProviderFactory($configFactory, $metadataIdentityProviderService, $logger);
        $this->assertInstanceOf(IdentityProviderFactoryInterface::class, $factory);

        $institutionName = new InstitutionName('institutionName');

        $metadataUrl = m::mock(MetadataUrl::class);
        $metadataUrl
            ->shouldReceive('getUrl')
            ->andReturn('https://metadata-url.example.com');

        $entityId = m::mock(EntityId::class);
        $entityId
            ->shouldReceive('getEntityId')
            ->andReturn('entityId');

        $ssoLocation = m::mock(Destination::class);
        $ssoLocation
            ->shouldReceive('getUrl')
            ->andReturn('https://sso-location.example.com');

        $certCollection = m::mock(CertificateCollection::class);
        $certCollection
            ->shouldReceive('first->getCertData')
            ->andReturn('certData');

        $configData = new InstitutionConfigurationData(
            'entityId',
            '',
            [],
            false,
            $metadataUrl->getUrl(),
        );

        $configFactory->shouldReceive('getEntity')
            ->with($institutionName)
            ->andReturn($configData);

        $metadataIdentityProviderService->shouldReceive('fetch')
            ->with($configData)
            ->andReturn(new AzureMfaIdentityProvider(
                $entityId,
                $ssoLocation,
                CertificateCollection::fromStringArray([$certData]),
                false
            ));

        $identityProvider = $factory->build($institutionName);
        $this->assertEquals('entityId', $identityProvider->getEntityId());
        $this->assertEquals('https://sso-location.example.com', $identityProvider->getSsoLocation()->getUrl());
        $this->assertEquals($expectCert, $identityProvider->getCertificates()->first()->getCertData());
        $this->assertEquals(false, $identityProvider->isAzureAD());
    }
}
