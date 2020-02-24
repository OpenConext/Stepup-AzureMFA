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

namespace Surfnet\AzureMfa\Test\Unit\Application\Institution\Service;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Application\Service\AuthenticationHelper;
use Surfnet\GsspBundle\Service\AuthenticationService;

class AuthenticationHelperTest extends TestCase
{
    private $regex = '@^https://(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]/vetting-procedure/gssf/azuremfa/metadata$@';

    /**
     * @dataProvider provideValidForceAuthnIssuerEntityIds
     * @param string $domainName
     */
    public function test_domain_matching_works_as_intended(string $domainName) : void
    {
        $authenticationService = m::mock(AuthenticationService::class);
        $authenticationService->shouldReceive('getIssuer')
            ->andReturn($domainName);
        $helper = new AuthenticationHelper($this->regex, $authenticationService);

        $this->assertTrue($helper->useForceAuthn());
    }

    /**
     * @dataProvider provideInvalidForceAuthnIssuerEntityIds
     * @param string $domainName
     */
    public function test_domain_matching_works_as_intended_on_invalid_uris(string $domainName) : void
    {
        $authenticationService = m::mock(AuthenticationService::class);
        $authenticationService->shouldReceive('getIssuer')
            ->andReturn($domainName);
        $helper = new AuthenticationHelper($this->regex, $authenticationService);

        $this->assertFalse($helper->useForceAuthn());
    }

    public function provideValidForceAuthnIssuerEntityIds()
    {
        yield ['https://example.com/vetting-procedure/gssf/azuremfa/metadata'];
        yield ['https://foobar.example.com/vetting-procedure/gssf/azuremfa/metadata'];
        yield ['https://foobar.example/vetting-procedure/gssf/azuremfa/metadata'];
        yield ['https://foobar.example.co.uk/vetting-procedure/gssf/azuremfa/metadata'];
        yield ['https://foobar.example.pizza/vetting-procedure/gssf/azuremfa/metadata'];
    }

    public function provideInvalidForceAuthnIssuerEntityIds()
    {
        yield ['https://example.com/vetting-procedure/gssf/azuremfa'];
        yield ['http://example.com/vetting-procedure/gssf/azuremfa/metadata'];
        yield ['https://example.a/vetting-procedure/gssf/azuremfa/metadata'];
        yield ['https://example.com/vetting-procedure/gssf/azuremfa/metadataa'];
        yield ['https://example.it\'s-invalid.com/vetting-procedure/gssf/azuremfa/metadata'];
    }
}
