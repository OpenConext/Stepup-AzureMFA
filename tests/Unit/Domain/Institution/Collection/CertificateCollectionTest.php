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

namespace Surfnet\AzureMfa\Test\Unit\Domain\Institution\Collection;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Domain\Exception\InvalidCertificateException;
use Surfnet\AzureMfa\Domain\Institution\Collection\CertificateCollection;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Certificate;

class CertificateCollectionTest extends TestCase
{
    public function test_happy_flow()
    {
        $collection = new CertificateCollection();
        $collection->add($this->buildCertificateMock('mock1'));
        $collection->add($this->buildCertificateMock('mock2'));
        $collection->add($this->buildCertificateMock('mock3'));

        $this->assertInstanceOf(CertificateCollection::class, $collection);
    }

    public function test_not_allowed_to_add_certificate_twice()
    {
        $collection = new CertificateCollection();
        $collection->add($this->buildCertificateMock('mock1'));
        $this->expectException(InvalidCertificateException::class);
        $this->expectExceptionMessage('This certificate was already added to the collection');
        $collection->add($this->buildCertificateMock('mock1'));
    }

    public function test_first()
    {
        $first = $this->buildCertificateMock('mock1');
        $second = $this->buildCertificateMock('mock2');
        $last = $this->buildCertificateMock('mock3');

        $collection = new CertificateCollection();
        $collection->add($first);
        $collection->add($second);
        $collection->add($last);

        $this->assertEquals($first, $collection->first());
        // Verify it leaves the collection intact.
        $this->assertNotEquals($second, $collection->first());
        $this->assertNotEquals($last, $collection->first());
        $this->assertEquals($first, $collection->first());
    }

    private function buildCertificateMock(string $certificate) : Certificate
    {
        $certificateMock = m::mock(Certificate::class);
        $certificateMock
            ->shouldReceive('getFingerprint')
            ->andReturn(hash('sha256', $certificate))
            ->once();

        $certificateMock
            ->shouldReceive('getCertdata')
            ->andReturn($certificate)
            ->once();

        return $certificateMock;
    }
}
