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

use Surfnet\AzureMfa\Domain\Exception\InvalidUserIdException;

class UserId
{
    /**
     * @var string
     */
    private $userId;

    public function __construct(string $userId)
    {
        if (empty($userId)) {
            throw new InvalidUserIdException('An empty UserId was specified');
        }

        $this->userId = $userId;
    }

    public static function generate(int $length = 4): UserId
    {
        // TODO: copied from Tiqr, is this safe to use?
        $id = base_convert(time(), 10, 36).'-'.base_convert(mt_rand(0, pow(36, $length)), 10, 36);
        return new self($id);
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }
}
