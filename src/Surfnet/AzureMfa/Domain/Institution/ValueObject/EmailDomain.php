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

namespace Surfnet\AzureMfa\Domain\Institution\ValueObject;

use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\InvalidEmailDomainException;

class EmailDomain implements EmailDomainInterface
{
    private string $emailDomain;

    public function __construct(string $emailDomain)
    {
        if (empty($emailDomain)) {
            throw new InvalidEmailDomainException('The email domain can not be an empty string.');
        }

        if (filter_var($emailDomain, FILTER_VALIDATE_DOMAIN, ['flags' => FILTER_FLAG_HOSTNAME]) === false) {
            throw new InvalidEmailDomainException('The provided email domain did not pass domain validation');
        }

        $this->emailDomain = $emailDomain;
    }

    public function getEmailDomain(): string
    {
        return $this->emailDomain;
    }

    public function domainMatches(EmailAddress $emailAddress): bool
    {
        return $emailAddress->getDomain() === $this->emailDomain;
    }
}
