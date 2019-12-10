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

namespace Surfnet\AzureMfa\Infrastructure\Repository;

use Surfnet\AzureMfa\Application\Repository\UserRepositoryInterface;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\UserNotFoundException;
use Surfnet\AzureMfa\Domain\UserId;
use Surfnet\AzureMfa\Domain\User;
use Surfnet\AzureMfa\Domain\UserStatus;

class UserFileRepository implements UserRepositoryInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var
     */
    private $users;

    public function __construct(string $userRepositoryPath)
    {
        $this->path = $userRepositoryPath;
        if (!is_file($this->path)) {
            file_put_contents($this->path, '{}');
        }
        $this->users = json_decode(file_get_contents($this->path), true);
    }

    public function save(User $user)
    {
        $this->users[$user->getUserId()->getUserId()] = $this->serialize($user);

        $content = json_encode($this->users, JSON_PRETTY_PRINT);
        file_put_contents($this->path, $content);
    }

    public function exists(UserId $userId): bool
    {
        return array_key_exists($userId->getUserId(), $this->users);
    }

    public function load(UserId $userId): User
    {
        if (!$this->exists($userId)) {
            throw new UserNotFoundException(sprintf("User with UserId '%s' not found", $userId->getUserId()));
        }

        return $this->deserialize($this->users[$userId->getUserId()]);
    }

    private function serialize(User $user): string
    {
        return json_encode([
            'id' => $user->getUserId()->getUserId(),
            'email' => $user->getEmailAddress()->getEmailAddress(),
            'status' => $user->getStatus()->getStatus(),
        ]);
    }

    private function deserialize(string $data): User
    {
        $object = json_decode($data, true);
        return new User(
            new UserId($object['id']),
            new EmailAddress($object['email']),
            new UserStatus($object['status'])
        );
    }
}
