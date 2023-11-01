<?php

declare(strict_types = 1);

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

namespace Surfnet\AzureMfa\Domain;

use Exception;
use Surfnet\AzureMfa\Domain\Exception\InvalidUserIdException;

class UserId
{
    const SEPARATOR = '|';
    const VALID_UNIQUE_ID = '/^[a-z0-9]{1,6}-[a-z0-9]{1,4}$/';


    private EmailAddress $emailAddress;

    public function __construct(private readonly string $userId)
    {
        if (empty($userId)) {
            throw new InvalidUserIdException('An empty id was specified');
        }

        $pos = strpos($userId, self::SEPARATOR);
        if ($pos === false) {
            throw new InvalidUserIdException('An invalid id was specified');
        }
        $emailAddress = substr($userId, $pos + 1);
        $uniquePrefix = substr($userId, 0, $pos);

        $match = preg_match(self::VALID_UNIQUE_ID, $uniquePrefix);
        if ($match !== 1) {
            throw new InvalidUserIdException('An invalid id was specified');
        }

        try {
            $this->emailAddress = new EmailAddress($emailAddress);
        } catch (Exception) {
            throw new InvalidUserIdException('An invalid id was specified');
        }
    }

    public static function generate(EmailAddress $emailAddress): UserId
    {
        $length = 4;
        $timeBasedHash =  base_convert((string)time(), 10, 36);
        $uniqueHash = base_convert((string)mt_rand(0, pow(36, $length)), 10, 36);
        $id = $timeBasedHash . '-' . $uniqueHash . self::SEPARATOR . $emailAddress->getEmailAddress();
        return new self($id);
    }

    public function isEqual(UserId $userId): bool
    {
        return ($this->userId === $userId->getUserId());
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }
}
