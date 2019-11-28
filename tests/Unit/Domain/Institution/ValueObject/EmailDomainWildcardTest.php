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

namespace Surfnet\AzureMfa\Test\Unit\Domain\Institution\ValueObject;

use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\InvalidEmailDomainException;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EmailDomainWildcard;

class EmailDomainWildcardTest extends TestCase
{
    public function test_happy_flow() : void
    {
        $domain = new EmailDomainWildcard('*.example.com');
        $this->assertInstanceOf(EmailDomainWildcard::class, $domain);
    }

    /**
     * @dataProvider provideInvalidDomains
     */
    public function test_misuse(string $input, string $expectedMessage) : void
    {
        $this->expectException(InvalidEmailDomainException::class);
        $this->expectExceptionMessage($expectedMessage);
        new EmailDomainWildcard($input);
    }

    /**
     * @dataProvider provideDomainMatches
     */
    public function test_domain_matches($configuredDomain, $testedEmail)
    {
        $domain = new EmailDomainWildcard($configuredDomain);
        $email = new EmailAddress($testedEmail);
        $this->assertTrue($domain->domainMatches($email));
    }

    /**
     * @dataProvider provideDomainMismatches
     */
    public function test_domain_mismatches($configuredDomain, $testedEmail)
    {
        $domain = new EmailDomainWildcard($configuredDomain);
        $email = new EmailAddress($testedEmail);
        $this->assertFalse($domain->domainMatches($email));
    }

    public function provideInvalidDomains() : array
    {
        return [
            'empty domain' => ['', 'The email domain can not be an empty string.'],
            'invalid wildcard character' => ['%.example.com', 'No wildcard character was specified, please use "*" as wildcard.'],
            'invalid wildcard position' => ['stepup.*.com', 'The email domain must start with the wildcard character.'],
            'more than a single wildcard character must be configured' => ['*', 'Please specify more than just the wildcard character.'],
        ];
    }

    public function provideDomainMatches()
    {
        return [
            ['*.example.com', 'stybar@stepup.example.com'],
            ['*.example.com', 'stybar@openconext.example.com'],
            ['*.example.com', 'stybar@openconext.stepup.example.com'],
            ['*.example.com', 'StYbAr@sTepUp.eXaMpLe.cOm'],
            ['*.stepup.example.com', 'stybar@openconext.stepup.example.com'],
        ];
    }

    public function provideDomainMismatches()
    {
        return [
            ['*.example.com', 'zdenek.stybar@example.com'],
            ['*.example.com', 'zdenek.stybar@example.coms'],
            ['*.example.com', 'zdenek.stybar@example.com.example.co.uk'],
            ['*.example.com', 'stybar@foobar.example.co.uk'],
            ['*.example.com', 'wout@van.aeart.be'],
            ['*.stepup.example.com', 'stybar@stepup.openconext.example.com'],
        ];
    }
}
