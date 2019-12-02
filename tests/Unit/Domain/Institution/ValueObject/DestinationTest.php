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
use Surfnet\AzureMfa\Domain\Exception\InvalidDestinationException;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Destination;

class DestinationTest extends TestCase
{
    public function test_happy_flow() : void
    {
        $destination = new Destination('https://test.example.com/adfs');
        $this->assertEquals('https://test.example.com/adfs', $destination->getUrl());
    }

    /**
     * @dataProvider provideInvalidDestinations
     */
    public function test_misuse(string $input, string $expectedMessage) : void
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage($expectedMessage);
        new Destination($input);
    }

    public function provideInvalidDestinations() : array
    {
        return [
            'empty destination' => ['', 'The destination URL can not be an empty string.'],
            'invalid url 1' => ['http:www.google.com/adfs', 'Please provide a valid URL for the Azure MFA destination endpoint.'],
            'invalid url 2' => ['adfs.google.com', 'Please provide a valid URL for the Azure MFA destination endpoint.'],
        ];
    }
}
