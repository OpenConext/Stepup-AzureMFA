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

namespace Surfnet\AzureMfa\Domain;

class UserStatus
{
    const USER_REGISTRATION_PENDING = 0;
    const USER_REGISTERED = 1;

    public function __construct(private readonly int $status)
    {
    }

    public static function pending(): UserStatus
    {
        return new UserStatus(self::USER_REGISTRATION_PENDING);
    }

    public static function registered(): UserStatus
    {
        return new UserStatus(self::USER_REGISTERED);
    }

    public function isPending(): bool
    {
        return $this->status == self::USER_REGISTRATION_PENDING;
    }

    public function isRegistered(): bool
    {
        return $this->status == self::USER_REGISTERED;
    }

    public function getStatus(): int
    {
        return  $this->status;
    }
}
