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

use Surfnet\AzureMfa\Domain\Exception\InvalidEmailAddressException;

class EmailAddress
{
    private $emailAddress;

    public function __construct(string $emailAddress)
    {
        if (empty($emailAddress)) {
            throw new InvalidEmailAddressException('An empty email address was specified');
        }
        if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmailAddressException('The provided email address is invalid');
        }

        $this->emailAddress = $emailAddress;
    }

    public function getEmailAddress() : string
    {
        return $this->emailAddress;
    }

    /**
     * Returns the lower cased domain name of the email address
     * @return string
     */
    public function getDomain() : string
    {
        $explosion = explode('@', $this->emailAddress);
        return strtolower($explosion[1]);
    }
}
