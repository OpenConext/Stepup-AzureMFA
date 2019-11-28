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

namespace Surfnet\AzureMfa\Domain\Institution\ValueObject;

use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\InvalidEmailDomainException;

/**
 * Domains with a wildcard
 *
 * Usefull when an institution has many subdomains that also have email addresses.
 * Rules regarding using a wildcard in the domain name:
 *
 * Wildcard character: *
 * Allowed position: At the beginning of the domain name.
 * The wildcard character is greedy and will match multiple subdomains if applicable.
 *
 * Examples:
 *
 * Valid:
 *  - *.example.com
 *  - *.stepup.example.com
 *
 * Invalid:
 *  - stepup.*.example.com
 *  - stepup.%.example.com
 *
 * Greedy:
 * `*.example.com` positivly matches (multiple subdomains are matched):
 *  - stepup.example.com
 *  - mfa.stepup.example.com
 */
class EmailDomainWildcard implements EmailDomainInterface
{
    const WILDCARD_CHARACTER = '*';

    private $emailDomain;

    private $regexTemplate = '/.+%s$/';

    public function __construct(string $emailDomain)
    {
        // Domain can not be empty
        if (empty($emailDomain)) {
            throw new InvalidEmailDomainException('The email domain can not be an empty string.');
        }

        // Valid wildcard must be used
        if (strstr($emailDomain, self::WILDCARD_CHARACTER) === false) {
            throw new InvalidEmailDomainException(
                sprintf(
                    'No wildcard character was specified, please use "%s" as wildcard.',
                    self::WILDCARD_CHARACTER
                )
            );
        }

        // Must start with the wildcard
        if (substr($emailDomain, 0, 1) !== self::WILDCARD_CHARACTER) {
            throw new InvalidEmailDomainException('The email domain must start with the wildcard character.');
        }

        if (strlen($emailDomain) < 2) {
            throw new InvalidEmailDomainException('Please specify more than just the wildcard character.');
        }

        $this->emailDomain = $emailDomain;
    }

    public function getEmailDomain() : string
    {
        return $this->emailDomain;
    }

    public function domainMatches(EmailAddress $emailAddress) : bool
    {
        $wildcardStripped = str_replace(self::WILDCARD_CHARACTER, '', $this->emailDomain);
        $escapedDomain = preg_quote($wildcardStripped);
        $pattern = sprintf($this->regexTemplate, $escapedDomain);
        return (bool) preg_match($pattern, $emailAddress->getDomain());
    }
}
