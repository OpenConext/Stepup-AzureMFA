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

use Generator;
use Mockery as m;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Application\Institution\Service\EmailDomainMatchingService;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Institution;
use Symfony\Component\Validator\Constraints\Isbn;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EmailDomainInConfigurationValidatorTest extends TestCase
{
    /**
     * @var EmailDomainInConfigurationValidator
     */
    private $validator;

    /**
     * @var EmailDomainMatchingService&Mock
     */
    private $service;

    /**
     * @var m\MockInterface&ExecutionContextInterface
     */
    private $context;

    protected function setUp(): void
    {
        $this->service = m::mock(EmailDomainMatchingService::class);
        $this->validator = new EmailDomainInConfigurationValidator($this->service);
        $this->context = m::mock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_happy_flow(): void
    {
        $this->service
            ->shouldReceive('findInstitutionByEmail')
            ->andReturn(m::mock(Institution::class));
        $this->assertNull($this->validator->validate('foobar@example.com', new EmailDomainInConfiguration()));
    }

    public function test_validate_empty_address(): void
    {
        $this->service
            ->shouldReceive('findInstitutionByEmail')
            ->andReturn(m::mock(Institution::class));
        $this->assertNull($this->validator->validate('', new EmailDomainInConfiguration()));
    }

    public function test_validate_domain_not_matched_to_institution(): void
    {
        $this->service
            ->shouldReceive('findInstitutionByEmail')
            ->andReturn(null);
        $this->context
            ->shouldReceive('buildViolation->addViolation');
        $this->assertNull($this->validator->validate('foobar@stepup.example.com', new EmailDomainInConfiguration()));
    }

    /**
     * @dataProvider provideInvalidInput
     */
    public function test_invalid_input($invalidValue, $constraint): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate($invalidValue, $constraint);
    }

    public function provideInvalidInput(): Generator
    {
        yield ['foobar@inval$d', new EmailDomainInConfiguration()];
        yield ['foobar@example.com', new Isbn()];
    }
}
