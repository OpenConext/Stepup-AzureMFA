<?php declare(strict_types=1);

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

namespace Surfnet\AzureMfa\Infrastructure\Validator\Constraint;

use Surfnet\AzureMfa\Application\Institution\Service\EmailDomainMatchingService;
use Surfnet\AzureMfa\Domain\EmailAddress;
use Surfnet\AzureMfa\Domain\Exception\InvalidEmailAddressException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EmailDomainInConfigurationValidator extends ConstraintValidator
{

    public function __construct(private readonly EmailDomainMatchingService $matchingService)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailDomainInConfiguration) {
            throw new UnexpectedTypeException($constraint, EmailDomainInConfiguration::class);
        }

        // Custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that.
        if (null === $value || '' === $value) {
            return;
        }

        try {
            $email = new EmailAddress($value);
        } catch (InvalidEmailAddressException $e) {
            throw new UnexpectedTypeException($constraint, EmailDomainInConfiguration::class);
        }

        if (is_null($this->matchingService->findInstitutionByEmail($email))) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
