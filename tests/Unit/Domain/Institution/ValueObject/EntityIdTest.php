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

namespace Surfnet\AzureMfa\Test\Unit\Domain\Institution\ValueObject;

use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Domain\Exception\InvalidEntityIdException;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EntityId;

class EntityIdTest extends TestCase
{
    public function test_happy_flow() : void
    {
        $entityId = new EntityId('https://stepup.example.com/entityId');
        $this->assertEquals('https://stepup.example.com/entityId', $entityId->getEntityId());
    }

    public function test_entity_id_cannot_be_empty() : void
    {
        $this->expectException(InvalidEntityIdException::class);
        $this->expectExceptionMessage('The IdP EntityId can not be an empty string.');
        new EntityId('');
    }
}
