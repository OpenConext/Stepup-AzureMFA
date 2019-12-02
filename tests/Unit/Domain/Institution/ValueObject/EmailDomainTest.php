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
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EmailDomain;

class EmailDomainTest extends TestCase
{
    public function test_happy_flow() : void
    {
        $domain = new EmailDomain('test.example.com');
        $this->assertInstanceOf(EmailDomain::class, $domain);
        $this->assertEquals('test.example.com', $domain->getEmailDomain());
    }

    /**
     * @dataProvider provideInvalidDomains
     */
    public function test_misuse(string $input, string $expectedMessage) : void
    {
        $this->expectException(InvalidEmailDomainException::class);
        $this->expectExceptionMessage($expectedMessage);
        new EmailDomain($input);
    }

    /**
     * @dataProvider provideDomainMatches
     */
    public function test_domain_matches($configuredDomain, $testedEmail)
    {
        $domain = new EmailDomain($configuredDomain);
        $email = new EmailAddress($testedEmail);
        $this->assertTrue($domain->domainMatches($email));
    }

    /**
     * @dataProvider provideDomainMismatches
     */
    public function test_domain_mismatches($configuredDomain, $testedEmail)
    {
        $domain = new EmailDomain($configuredDomain);
        $email = new EmailAddress($testedEmail);
        $this->assertFalse($domain->domainMatches($email));
    }

    public function provideInvalidDomains() : array
    {
        return [
            'empty domain' => ['', 'The email domain can not be an empty string.'],
            'invalid domain (too long)' => ['exampleexampleexampleexampleexampleexampleexampleexampleexampleexampleexampleexampleexampleexampleexampleexampleexample.foobar', 'The provided email domain did not pass domain validation'],
            'invalid domain (invalid characters)' => ['あいうえお.com', 'The provided email domain did not pass domain validation'],
            'invalid domain (invalid characters 2)' => ['Abc..123com', 'The provided email domain did not pass domain validation'],
            'invalid domain (invalid characters 3)' => ['ab$s*23.example.com', 'The provided email domain did not pass domain validation'],
            'invalid domain (invalid characters 4)' => ['example@.com', 'The provided email domain did not pass domain validation'],
        ];
    }

    public function provideDomainMatches() : array
    {
        return [
            ['example.com', 'stybar@example.com'],
            ['example.com', 'zdenek.stybar@example.com'],
            ['stepup.example.com', 'stybar@stepup.example.com'],
            ['stepup.example.com', 'zdenek.stybar@stepup.example.com'],
            ['stepup.example.com', 'StYbAr@sTepUp.eXaMpLe.cOm'],
        ];
    }

    public function provideDomainMismatches() : array
    {
        return [
            ['example.com', 'wout@van.aeart.be'],
            ['example.com', 'stybar@foobar.example.com'],
            ['example.com', 'zdenek.stybar@foobar.example.com'],
            ['stepup.example.com', 'stybar@example.com'],
        ];
    }
}
