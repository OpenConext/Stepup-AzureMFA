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

use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Application\Factory\IdentityProviderFactoryInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Destination;

class IdentityProviderFactoryTest extends TestCase
{
    public function test_build()
    {
        $factory = new IdentityProviderFactory();
        $this->assertInstanceOf(IdentityProviderFactoryInterface::class, $factory);

        $identityProvider = $factory->build(new Destination('https://foobar.example.com/sso'));
        $this->assertEquals('https://foobar.example.com/sso', $identityProvider->getSsoUrl());
        $this->assertEquals('https://unknown-at-this-moment.example.com/saml/metadata', $identityProvider->getEntityId());
    }
}
