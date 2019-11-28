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

namespace Surfnet\AzureMfa\Test\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\InvalidEmailAddressException;

class EmailAddressTest extends TestCase
{
    public function test_happy_flow() : void
    {
        $email = new EmailAddress('test@example.com');
        $this->assertInstanceOf(EmailAddress::class, $email);
    }

    public function test_it_rejects_empty_address() : void
    {
        $this->expectException(InvalidEmailAddressException::class);
        $this->expectExceptionMessage('An empty email address was specified');
        new EmailAddress('');
    }

    /**
     * @dataProvider provideInvalidEmailAddresses
     */
    public function test_it_rejects_invalid_email_addresses($invalidAddress) : void
    {
        $this->expectException(InvalidEmailAddressException::class);
        $this->expectExceptionMessage('The provided email address is invalid');
        new EmailAddress($invalidAddress);
    }

    /**
     * @dataProvider provideDomainTestAddresses
     */
    public function test_get_domain($emailAddress, $expectedDomain) : void
    {
        $email = new EmailAddress($emailAddress);
        $this->assertEquals($expectedDomain, $email->getDomain());
    }
    
    /**
     * Thanks: cjaoude https://gist.github.com/cjaoude/fd9910626629b53c4d25#file-gistfile1-txt
     * @return array
     */
    public function provideInvalidEmailAddresses() : array
    {
        return [
            ["plainaddress"],
            ["#@%^%#$@#$@#.com"],
            ["@example.com",],
            ["Joe Smith <email@example.com>"],
            ["email.example.com"],
            ["email@example@example.com"],
            [".email@example.com"],
            ["email.@example.com"],
            ["email..email@example.com"],
            ["あいうえお@example.com"],
            ["email@example.com (Joe Smith)"],
            ["email@example"],
            ["email@-example.com"],
            ["email@111.222.333.44444"],
            ["email@example..com"],
            ["Abc..123@example.com"],
            ["A@b@c@example.com"],
            ["1234567890123456789012345678901234567890123456789012345678901234+x@example.com"]
        ];
    }

    public function provideDomainTestAddresses() : array
    {
        return [
            ['stybar@example.com', 'example.com'],
            ['stybar@EXAMPLE.com', 'example.com'],
            ['stybar@example.COM', 'example.com'],
            ['stybar@foobar.example.COM', 'foobar.example.com'],
        ];
    }
}