<?php
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
use Surfnet\AzureMfa\Domain\User;
use Surfnet\AzureMfa\Domain\UserId;
use Surfnet\AzureMfa\Domain\Exception\InvalidUserIdException;

class UserIdTest extends TestCase
{
    public function test_happy_flow() : void
    {
        $userId = new UserId('q2b27d-0000|user@stepup.example.com');
        $this->assertInstanceOf(UserId::class, $userId);

        $userId = new UserId('q2b27d-0000||user@stepup.example.com');
        $this->assertInstanceOf(UserId::class, $userId);
    }

    public function test_generate() : void {
        $emailAddress = new EmailAddress('test@stepup.example.com');
        $userId = UserId::generate($emailAddress);

        $this->assertInstanceOf(UserId::class, $userId);
        $id = $userId->getUserId();

        $userId = new UserId($id);
        $this->assertInstanceOf(UserId::class, $userId);
    }

    public function test_equal() : void {
        $userId1 = new UserId('q2b27d-0000|user@stepup.example.com');
        $userId2 = new UserId('q2b27d-0000|user@stepup.example.com');
        $this->assertTrue($userId1->isEqual($userId2));
    }

    public function test_not_equal() : void {
        $userId1 = new UserId('q2b27d-0001|user@stepup.example.com');
        $userId2 = new UserId('q2b27d-0000|user@stepup.example.com');
        $this->assertFalse($userId1->isEqual($userId2));

        $userId1 = new UserId('q2b27d-0000|user@stepup.example.com1');
        $userId2 = new UserId('q2b27d-0000|user@stepup.example.com2');
        $this->assertFalse($userId1->isEqual($userId2));
    }

    public function test_it_rejects_empty_address() : void
    {
        $this->expectException(InvalidUserIdException::class);
        $this->expectExceptionMessage('An empty id was specified');
        new UserId('');
    }

    /**
     * @dataProvider provideInvalidUserIds
     */
    public function test_it_rejects_invalid_email_ids($invalidAddress) : void
    {
        $this->expectException(InvalidUserIdException::class);
        $this->expectExceptionMessage('An invalid id was specified');
        new UserId($invalidAddress);
    }

    /**
     * Thanks: cjaoude https://gist.github.com/cjaoude/fd9910626629b53c4d25#file-gistfile1-txt
     * @return array
     */
    public function provideInvalidUserIds() : array
    {
        return [
            ["q2b27d-0000|plainaddress"],
            ["q2b27d-0000|#@%^%#$@#$@#.com"],
            ["q2b27d-0000|email.example.com"],
            ["q2b27d-0000|email@example@example.com"],
            ["q2b27d-0000|"],
            ["q2b27d-0000"],
            ["q2b27d-|test@stepup.example.com"],
            ["q2b27d0000|test@stepup.example.com"],
            ["q2b27-|test@stepup.example.com"],
            ["q2b27d-0000-user@stepup.example.com"],
            ["q2b27d-0000+user@stepup.example.com"],
            ["q2b27d-0000 user@stepup.example.com"],
        ];
    }
}
