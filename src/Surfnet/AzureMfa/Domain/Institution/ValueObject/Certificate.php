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

use Surfnet\AzureMfa\Domain\Exception\InvalidCertificateException;

class Certificate
{
    private string $certData;

    public function __construct(string $certData)
    {
        if (empty($certData)) {
            throw new InvalidCertificateException('The IdP certificate can not be an empty string.');
        }
        if (!openssl_x509_parse($certData)) {
            throw new InvalidCertificateException('Invalid X509 certificate, please configure the certificate in PEM formatting (with header and footer)');
        }

        $this->certData = $certData;
    }

    public function getFingerprint(): string
    {
        $fingerprint = openssl_x509_fingerprint($this->certData, 'sha256');
        if ($fingerprint === false) {
            throw new InvalidCertificateException('Unable to get the fingerprint from the certData using SHA256 digest algorithm');
        }
        return $fingerprint;
    }

    /**
     * Returns the certificate as a string (without certificate header/footer)
     */
    public function getCertData(): string
    {
        $stripFormat = '-----%s CERTIFICATE-----';
        $certData = $this->certData;
        $certData = str_replace(sprintf($stripFormat, 'BEGIN'), '', $certData);
        $certData = str_replace(sprintf($stripFormat, 'END'), '', $certData);
        return str_replace(PHP_EOL, '', $certData);
    }
}
