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

namespace Surfnet\AzureMfa\Test\Unit\Infrastructure\Repository;


use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Application\Repository\UserRepositoryInterface;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\User;
use Surfnet\AzureMfa\Domain\UserId;
use Surfnet\AzureMfa\Domain\UserStatus;
use Surfnet\AzureMfa\Infrastructure\Repository\UserFileRepository;

class UserFileRepositoryTest extends TestCase
{

    /**
     * @var UserFileRepository
     */
    private $repository;

    protected function setUp() : void
    {
        $testPath = __DIR__ . "/repository.json";
        @unlink($testPath);
        $this->repository = New UserFileRepository($testPath);
    }

    /**
     * These constants are prerequisites for the tests to be able to determine differences in a User's state
     */
    public function testUserRepositoryUserStatesPrerequisites()
    {
        $this->assertSame(0, User::USER_REGISTRATION_PENDING);
        $this->assertSame(1,User::USER_REGISTERED);
    }

    public function testUserRepository() {
        $this->assertUsers($this->repository);
    }

    public function assertUsers(UserRepositoryInterface $repository)
    {
        $userId1 =$this->createUserId('user1');
        $userId2 =$this->createUserId('user2');
        $userId3 =$this->createUserId('user3');

        // Check if users do not exist
        $this->assertFalse($repository->exists($userId1));
        $this->assertFalse($repository->exists($userId2));
        $this->assertFalse($repository->exists($userId3));

        // Add users
        $user1 = $this->createUser('user1', 'user1@stepup.example.com', UserStatus::registered());
        $user2 = $this->createUser('user2', 'user2@stepup.example.com', UserStatus::pending());
        $user3 = $this->createUser('user3', 'user3@stepup.example.com', UserStatus::registered());

        $repository->save($user1);
        $repository->save($user2);
        $repository->save($user3);

        // Check if users do exist
        $this->assertTrue($repository->exists($userId1));
        $this->assertTrue($repository->exists($userId2));
        $this->assertTrue($repository->exists($userId3));

        // Load persisted users
        $user1 = $repository->load($userId1);
        $user2 = $repository->load($userId2);
        $user3 = $repository->load($userId3);

        // Assert persisted users
        $this->assertUser($user1, 'user1', 'user1@stepup.example.com', UserStatus::USER_REGISTERED);
        $this->assertUser($user2, 'user2', 'user2@stepup.example.com', UserStatus::USER_REGISTRATION_PENDING);
        $this->assertUser($user3, 'user3', 'user3@stepup.example.com', UserStatus::USER_REGISTERED);
    }

    private function assertUser(User $user, $expectedId, $expectedEmail, $expectedStatus)
    {
        $this->assertSame($expectedId, $user->getUserId()->getUserId());
        $this->assertSame($expectedEmail, $user->getEmailAddress()->getEmailAddress());
        $this->assertSame($expectedStatus, $user->getStatus()->getStatus());
    }

    private function createUser($nameId, $emailAddress, UserStatus $userStatus): User
    {
        return New User($this->createUserId($nameId), new EmailAddress($emailAddress), $userStatus);
    }

    private function createUserId($nameId): UserId
    {
        return New UserId($nameId);
    }
}