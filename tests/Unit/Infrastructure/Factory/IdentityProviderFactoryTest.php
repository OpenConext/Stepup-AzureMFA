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
use Surfnet\AzureMfa\Domain\Institution\Collection\CertificateCollection;
use Surfnet\AzureMfa\Domain\Institution\Factory\IdentityProviderFactoryInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Destination;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EntityId;

class IdentityProviderFactoryTest extends TestCase
{
    public function test_build()
    {
        $factory = new IdentityProviderFactory();
        $this->assertInstanceOf(IdentityProviderFactoryInterface::class, $factory);

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

        $identityProvider = $factory->build($entityId, $ssoLocation, $certCollection, false);
        $this->assertEquals('entityId', $identityProvider->entityId()->getEntityId());
        $this->assertEquals('https://sso-location.example.com', $identityProvider->getSsoLocation()->getUrl());
        $this->assertEquals('certData', $identityProvider->getCertificates()->first()->getCertData());
        $this->assertEquals(false, $identityProvider->isAzureAD());

    }
}
