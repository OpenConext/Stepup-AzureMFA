<?php declare(strict_types = 1);

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

namespace Surfnet\AzureMfa\Test\Unit\Infrastructure\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Surfnet\AzureMfa\Domain\Institution\Collection\CertificateCollection;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Destination;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EntityId;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\IdentityProviderInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionName;
use Surfnet\AzureMfa\Infrastructure\Cache\IdentityProviderCache;
use Surfnet\AzureMfa\Infrastructure\Cache\IdentityProviderFactoryCache;
use Surfnet\AzureMfa\Infrastructure\Entity\AzureMfaIdentityProvider;
use Surfnet\AzureMfa\Infrastructure\Factory\IdentityProviderFactory;

class IdentityProviderFactoryCacheTest extends TestCase
{
    private string $cacheFile = '';
    private IdentityProviderFactoryCache $factoryCache;
    private IdentityProviderFactory|m\MockInterface $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $cacheDir = __DIR__;
        $this->cacheFile = $cacheDir.'/institution-name.cache';
        @unlink($this->cacheFile);

        $this->assertFileDoesNotExist($this->cacheFile);

        $logger = new NullLogger();
        $this->factory = m::mock(IdentityProviderFactory::class);
        $cache = new IdentityProviderCache($cacheDir, $logger);
        $this->factoryCache = new IdentityProviderFactoryCache($this->factory, $cache);
    }


    protected function tearDown(): void
    {
        m::close();
        @unlink($this->cacheFile);
    }

    public function test_cache_init()
    {
        $institutionName = new InstitutionName('institution-name');
        $identityProvider = $this->createIdentityProvider();

        $this->factory->shouldReceive('build')
            ->with($institutionName)
            ->once()
            ->andReturn($identityProvider);

        $result = $this->factoryCache->build($institutionName);

        $this->assertFileExists($this->cacheFile);

        $this->assertInstanceOf(IdentityProviderInterface::class, $identityProvider);
        $this->assertEquals($identityProvider->getEntityId(), $result->getEntityId());
        $this->assertEquals($identityProvider->getSsoLocation(), $result->getSsoLocation());
        $this->assertEquals($identityProvider->getCertificates(), $result->getCertificates());
        $this->assertEquals($identityProvider->isAzureAD(), $result->isAzureAD());
    }


    public function test_cache_hit()
    {
        file_put_contents($this->cacheFile, <<<EOF
{
    "updated": "2025-06-05T13:16:50+02:00",
    "entity_id": "https:\/\/stepup.example.com\/entityId",
    "sso_location": "https:\/\/test.example.com\/adfs",
    "certificates": {
        "5e12a02a08a65c97535adf9792fb6c2bd16cb286529a15efc97cf9ddd3db67c0": "MIICzjCCAjegAwIBAgIUInXYmn\/hxq0qmy5NJlxukNZ4qQowDQYJKoZIhvcNAQELBQAweTELMAkGA1UEBhMCRkIxDzANBgNVBAgMBkZvb2JhcjEMMAoGA1UEBwwDQmFyMQwwCgYDVQQKDANGb28xDzANBgNVBAsMBkZvb2JhcjEMMAoGA1UEAwwDRm9vMR4wHAYJKoZIhvcNAQkBFg9mb29AZXhhbXBsZS5jb20wHhcNMTkxMjEyMTIzMzMyWhcNMjQxMjEwMTIzMzMyWjB5MQswCQYDVQQGEwJGQjEPMA0GA1UECAwGRm9vYmFyMQwwCgYDVQQHDANCYXIxDDAKBgNVBAoMA0ZvbzEPMA0GA1UECwwGRm9vYmFyMQwwCgYDVQQDDANGb28xHjAcBgkqhkiG9w0BCQEWD2Zvb0BleGFtcGxlLmNvbTCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEA9Of3o788Pp1VfOOoZrTXs2gg+b3GEBw6VgTzXA1xtr1oKAygJliS74UaK01k\/e1bNwZvNZPAV26hKU5UD3g78tRlOGV2W11aWh8Xanfnhno2GH18wHeaOTHehgpVpkB4a9R2CztoRC0mjp6Z7ya4aZYbFLijxLsc1Z5PkAD+Bi8CAwEAAaNTMFEwHQYDVR0OBBYEFLXFn8qQTnp2qbE0Bq5nvkspgFnbMB8GA1UdIwQYMBaAFLXFn8qQTnp2qbE0Bq5nvkspgFnbMA8GA1UdEwEB\/wQFMAMBAf8wDQYJKoZIhvcNAQELBQADgYEAKXRNgkz0DUuS+EDhzX3VtUGi6YR75hFESYk+BdGU4TlAI+UjVi8XOQeeCV6XwDKdeQla3t0JMBZqdor9vbo3BLNq7Xd7R36PnGNspNgZmXmePwbNrp+8JXae3AULMa7uR9Ai\/eLESFcmIM79duCOrgmm5Nj11kIfHvA2qrfdBYY="
    },
    "is_azure_ad": false
}
EOF
);

        $institutionName = new InstitutionName('institution-name');
        $identityProvider = $this->createIdentityProvider();

        $this->factory->expects('build')
            ->with($institutionName)
            ->times(0);

        $result = $this->factoryCache->build($institutionName);

        $this->assertFileExists($this->cacheFile);

        $this->assertInstanceOf(IdentityProviderInterface::class, $identityProvider);
        $this->assertEquals($identityProvider->getEntityId(), $result->getEntityId());
        $this->assertEquals($identityProvider->getSsoLocation(), $result->getSsoLocation());
        $this->assertEquals($identityProvider->getCertificates(), $result->getCertificates());
        $this->assertEquals($identityProvider->isAzureAD(), $result->isAzureAD());
    }


    private function createIdentityProvider(): IdentityProviderInterface
    {
        $entityId = new EntityId('https://stepup.example.com/entityId');
        $ssoLocation = new Destination('https://test.example.com/adfs');
        $certificates = CertificateCollection::fromStringArray([
            '-----BEGIN CERTIFICATE-----
MIICzjCCAjegAwIBAgIUInXYmn/hxq0qmy5NJlxukNZ4qQowDQYJKoZIhvcNAQEL
BQAweTELMAkGA1UEBhMCRkIxDzANBgNVBAgMBkZvb2JhcjEMMAoGA1UEBwwDQmFy
MQwwCgYDVQQKDANGb28xDzANBgNVBAsMBkZvb2JhcjEMMAoGA1UEAwwDRm9vMR4w
HAYJKoZIhvcNAQkBFg9mb29AZXhhbXBsZS5jb20wHhcNMTkxMjEyMTIzMzMyWhcN
MjQxMjEwMTIzMzMyWjB5MQswCQYDVQQGEwJGQjEPMA0GA1UECAwGRm9vYmFyMQww
CgYDVQQHDANCYXIxDDAKBgNVBAoMA0ZvbzEPMA0GA1UECwwGRm9vYmFyMQwwCgYD
VQQDDANGb28xHjAcBgkqhkiG9w0BCQEWD2Zvb0BleGFtcGxlLmNvbTCBnzANBgkq
hkiG9w0BAQEFAAOBjQAwgYkCgYEA9Of3o788Pp1VfOOoZrTXs2gg+b3GEBw6VgTz
XA1xtr1oKAygJliS74UaK01k/e1bNwZvNZPAV26hKU5UD3g78tRlOGV2W11aWh8X
anfnhno2GH18wHeaOTHehgpVpkB4a9R2CztoRC0mjp6Z7ya4aZYbFLijxLsc1Z5P
kAD+Bi8CAwEAAaNTMFEwHQYDVR0OBBYEFLXFn8qQTnp2qbE0Bq5nvkspgFnbMB8G
A1UdIwQYMBaAFLXFn8qQTnp2qbE0Bq5nvkspgFnbMA8GA1UdEwEB/wQFMAMBAf8w
DQYJKoZIhvcNAQELBQADgYEAKXRNgkz0DUuS+EDhzX3VtUGi6YR75hFESYk+BdGU
4TlAI+UjVi8XOQeeCV6XwDKdeQla3t0JMBZqdor9vbo3BLNq7Xd7R36PnGNspNgZ
mXmePwbNrp+8JXae3AULMa7uR9Ai/eLESFcmIM79duCOrgmm5Nj11kIfHvA2qrfd
BYY=
-----END CERTIFICATE-----
'
        ]);
        $isAzureAD = false;

        return new AzureMfaIdentityProvider(
            $entityId,
            $ssoLocation,
            $certificates,
            $isAzureAD
        );
    }

}