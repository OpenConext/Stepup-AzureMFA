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

use Generator;
use PHPUnit\Framework\TestCase;
use Surfnet\AzureMfa\Domain\Exception\InvalidCertificateException;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Certificate;

class CertificateTest extends TestCase
{
    public function test_happy_flow() : void
    {
        $certData = $this->getCertificateData();
        $certificate = new Certificate($certData);
        $this->assertEquals($this->getCertificateDataWithoutHeaderFooter(), $certificate->getCertData());
    }

    public function test_get_fingerprint()
    {
        $cert = new Certificate($this->getCertificateData());
        // Verify the sha256 fingerprint matches the expected:
        // $ openssl x509 -noout -fingerprint -sha256 -inform pem -in ./publickey.cer
        // SHA256 Fingerprint=5E:12:A0:2A:08:A6:5C:97:53:5A:DF:97:92:FB:6C:2B:D1:6C:B2:86:52:9A:15:EF:C9:7C:F9:DD:D3:DB:67:C0

        $this->assertEquals('5e12a02a08a65c97535adf9792fb6c2bd16cb286529a15efc97cf9ddd3db67c0', $cert->getFingerprint());
    }

    /**
     * @dataProvider provideInvalidCertificates
     */
    public function test_misuse(string $input, string $expectedMessage) : void
    {
        $this->expectException(InvalidCertificateException::class);
        $this->expectExceptionMessage($expectedMessage);
        new Certificate($input);
    }

    public function provideInvalidCertificates() : Generator
    {
        yield ['', 'The IdP certificate can not be an empty string.'];
        yield ['foobar', 'Invalid X509 certificate, please configure the certificate in PEM formatting (with header and footer)'];
        yield ['MIICzjCCAjegAwIBAgIUInXYmn/hxq0qmy5NJlxukNZ4qQowDQYJKoZIhvcNAQELBQAweTELMAkGA1UEBhMCRkIxDzANBgNVBAgMBkZvb2JhcjEMMAoGA1UEBwwDQmFyMQwwCgYDVQQKDANGb28xDzANBgNVBAsMBkZvb2JhcjEMMAoGA1UEAwwDRm9vMR4wHAYJKoZIhvcNAQkBFg9mb29AZXhhbXBsZS5jb20wHhcNMTkxMjEyMTIzMzMyWhcNMjQxMjEwMTIzMzMyWjB5MQswCQYDVQQGEwJGQjEPMA0GA1UECAwGRm9vYmFyMQwwCgYDVQQHDANCYXIxDDAKBgNVBAoMA0ZvbzEPMA0GA1UECwwGRm9vYmFyMQwwCgYDVQQDDANGb28xHjAcBgkqhkiG9w0BCQEWD2Zvb0BleGFtcGxlLmNvbTCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEA9Of3o788Pp1VfOOoZrTXs2gg+b3GEBw6VgTzXA1xtr1oKAygJliS74UaK01k/e1bNwZvNZPAV26hKU5UD3g78tRlOGV2W11aWh8Xanfnhno2GH18wHeaOTHehgpVpkB4a9R2CztoRC0mjp6Z7ya4aZYbFLijxLsc1Z5PkAD+Bi8CAwEAAaNTMFEwHQYDVR0OBBYEFLXFn8qQTnp2qbE0Bq5nvkspgFnbMB8GA1UdIwQYMBaAFLXFn8qQTnp2qbE0Bq5nvkspgFnbMA8GA1UdEwEB/wQFMAMBAf8wDQYJKoZIhvcNAQELBQADgYEAKXRNgkz0DUuS+EDhzX3VtUGi6YR75hFESYk+BdGU4TlAI+UjVi8XOQeeCV6XwDKdeQla3t0JMBZqdor9vbo3BLNq7Xd7R36PnGNspNgZmXmePwbNrp+8JXae3AULMa7uR9Ai/eLESFcmIM79duCOrgmm5Nj11kIfHvA2qrfdBYY=', 'Invalid X509 certificate, please configure the certificate in PEM formatting (with header and footer)'];
    }

    private function getCertificateData() : string
    {
        return '-----BEGIN CERTIFICATE-----
MIICzjCCAjegAwIBAgIUInXYmn/hxq0qmy5NJlxukNZ4qQowDQYJKoZIhvcNAQEL
BQAweTELMAkGA1UEBhMCRkIxDzANBgNVBAgMBkZvb2JhcjEMMAoGA1UEBwwDQmFy
MQwwCgYDVQQKDANGb28xDzANBgNVBAsMBkZvb2JhcjEMMAoGA1UEAwwDRm9vMR4w
HAYJKoZIhvcNAQkBFg9mb29AZXhhbXBsZS5jb20wHhcNMTkxMjEyMTIzMzMyWhcN
MjQxMjEwMTIzMzMyWjB5MQswCQYDVQQGEwJGQjEPMA0GA1UECAwGRm9vYmFyMQww
CgYDVQQHDANCYXIxDDAKBgNVBAoMA0ZvbzEPMA0GA1UECwwGRm9vYmFyMQwwCgYD
VQQDDANGb28xHjAcBgkqhkiG9w0BCQEWD2Zvb0BleGFtcGxlLmNvbTCBnzANBgkq
hkiG9w0BAQEFAAOBjQAwgYkCgYEA9Of3o788Pp1VfOOoZrTXs2gg+b3GEBw6VgTz
XA1xtr1oKAygJliS74UaK01k/e1bNwZvNZPAV26hKU5UD3g78tRlOGV2W11aWh8X
anfnhno2GH18wHeaOTHehgpVpkB4a9R2CztoRC0mjp6Z7ya4aZYbFLijxLsc1Z5P
kAD+Bi8CAwEAAaNTMFEwHQYDVR0OBBYEFLXFn8qQTnp2qbE0Bq5nvkspgFnbMB8G
A1UdIwQYMBaAFLXFn8qQTnp2qbE0Bq5nvkspgFnbMA8GA1UdEwEB/wQFMAMBAf8w
DQYJKoZIhvcNAQELBQADgYEAKXRNgkz0DUuS+EDhzX3VtUGi6YR75hFESYk+BdGU
4TlAI+UjVi8XOQeeCV6XwDKdeQla3t0JMBZqdor9vbo3BLNq7Xd7R36PnGNspNgZ
mXmePwbNrp+8JXae3AULMa7uR9Ai/eLESFcmIM79duCOrgmm5Nj11kIfHvA2qrfd
BYY=
-----END CERTIFICATE-----';
    }

    private function getCertificateDataWithoutHeaderFooter() : string
    {
        return str_replace(PHP_EOL, '', 'MIICzjCCAjegAwIBAgIUInXYmn/hxq0qmy5NJlxukNZ4qQowDQYJKoZIhvcNAQEL
BQAweTELMAkGA1UEBhMCRkIxDzANBgNVBAgMBkZvb2JhcjEMMAoGA1UEBwwDQmFy
MQwwCgYDVQQKDANGb28xDzANBgNVBAsMBkZvb2JhcjEMMAoGA1UEAwwDRm9vMR4w
HAYJKoZIhvcNAQkBFg9mb29AZXhhbXBsZS5jb20wHhcNMTkxMjEyMTIzMzMyWhcN
MjQxMjEwMTIzMzMyWjB5MQswCQYDVQQGEwJGQjEPMA0GA1UECAwGRm9vYmFyMQww
CgYDVQQHDANCYXIxDDAKBgNVBAoMA0ZvbzEPMA0GA1UECwwGRm9vYmFyMQwwCgYD
VQQDDANGb28xHjAcBgkqhkiG9w0BCQEWD2Zvb0BleGFtcGxlLmNvbTCBnzANBgkq
hkiG9w0BAQEFAAOBjQAwgYkCgYEA9Of3o788Pp1VfOOoZrTXs2gg+b3GEBw6VgTz
XA1xtr1oKAygJliS74UaK01k/e1bNwZvNZPAV26hKU5UD3g78tRlOGV2W11aWh8X
anfnhno2GH18wHeaOTHehgpVpkB4a9R2CztoRC0mjp6Z7ya4aZYbFLijxLsc1Z5P
kAD+Bi8CAwEAAaNTMFEwHQYDVR0OBBYEFLXFn8qQTnp2qbE0Bq5nvkspgFnbMB8G
A1UdIwQYMBaAFLXFn8qQTnp2qbE0Bq5nvkspgFnbMA8GA1UdEwEB/wQFMAMBAf8w
DQYJKoZIhvcNAQELBQADgYEAKXRNgkz0DUuS+EDhzX3VtUGi6YR75hFESYk+BdGU
4TlAI+UjVi8XOQeeCV6XwDKdeQla3t0JMBZqdor9vbo3BLNq7Xd7R36PnGNspNgZ
mXmePwbNrp+8JXae3AULMa7uR9Ai/eLESFcmIM79duCOrgmm5Nj11kIfHvA2qrfd
BYY=');
    }
}
