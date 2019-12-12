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

namespace Surfnet\AzureMfa\Domain\Institution\Collection;

use Surfnet\AzureMfa\Domain\Exception\InvalidCertificateException;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Certificate;

class CertificateCollection
{
    /**
     * @var Certificate[]
     */
    private $certificates = [];

    public function add(Certificate $certificate)
    {
        if (array_key_exists($certificate->getFingerprint(), $this->certificates)) {
            throw new InvalidCertificateException('This certificate was already added to the collection');
        }
        $this->certificates[$certificate->getFingerprint()] = $certificate;
    }
}
