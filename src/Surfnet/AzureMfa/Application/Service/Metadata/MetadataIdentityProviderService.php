<?php

declare(strict_types = 1);

/**
 * Copyright 2025 SURFnet B.V.
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

namespace Surfnet\AzureMfa\Application\Service\Metadata;

use DOMNodeList;
use SAML2\DOMDocumentFactory;
use Surfnet\AzureMfa\Application\Exception\InvalidMfaMetadataUrlResponseException;
use Surfnet\AzureMfa\Domain\Institution\Collection\CertificateCollection;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Certificate;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Destination;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\EntityId;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionConfigurationData;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\MetadataUrl;
use Surfnet\AzureMfa\Infrastructure\Entity\AzureMfaIdentityProvider;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use DOMXPath;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MetadataIdentityProviderService
{
    public function __construct(private HttpClientInterface $client)
    {
    }

    public function fetch(InstitutionConfigurationData $config): AzureMfaIdentityProvider
    {
        $metadataUrl = new MetadataUrl($config->getMetadataUrl());

        $response = $this->doRequest($metadataUrl);

        return $this->doParseMetadata($response, $config);
    }

    private function doRequest(MetadataUrl $metadataUrl): string
    {
        $response = $this->client->request('GET', $metadataUrl->getUrl());

        if ($response->getStatusCode() !== 200) {
            throw new InvalidMfaMetadataUrlResponseException(sprintf('The metadata URL "%s" returned a non-200 status code: %d', $metadataUrl->getUrl(), $response->getStatusCode()));
        }

        return $response->getContent();
    }

    private function doParseMetadata(string $data, InstitutionConfigurationData $config): AzureMfaIdentityProvider
    {
        // Parse XML
        try {
            $doc = DOMDocumentFactory::fromString($data);
        } catch (Throwable $e) {
            throw new InvalidMfaMetadataUrlResponseException('Failed to parse metadata XML.', 0, $e);
        }

        $xpath = new DOMXPath($doc);

        // Register namespaces if needed
        $xpath->registerNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        // Get data from the XML
        $this->doValidateEntityDescriptor($xpath);
        $entityId = $this->doParseEntityId($xpath);
        $ssoLocation = $this->doParseSsoLocation($xpath);
        $certificates = $this->doParseCertificates($xpath);

        // Construct and return the AzureMfaIdentityProvider object
        return new AzureMfaIdentityProvider(
            $entityId,
            $ssoLocation,
            $certificates,
            $config->isAzureAd(),
        );
    }

    private function doValidateEntityDescriptor(DOMXPath $xpath): void
    {
        $entityNodes = $xpath->query('/md:EntityDescriptor');
        if (!($entityNodes instanceof DOMNodeList) || $entityNodes->length !== 1) {
            throw new InvalidMfaMetadataUrlResponseException('EntityDescriptor not found in metadata.');
        }
    }

    private function doParseEntityId(DOMXPath $xpath): EntityId
    {
        // Extract EntityID
        $entityIdNodes = $xpath->query('/md:EntityDescriptor/@entityID');
        if (!($entityIdNodes instanceof DOMNodeList) || $entityIdNodes->length !== 1) {
            throw new InvalidMfaMetadataUrlResponseException('A valid EntityID not found in metadata.');
        }
        $entityId = $entityIdNodes->item(0)->nodeValue ?? '';

        return new EntityId($entityId);
    }

    private function doParseSsoLocation(DOMXPath $xpath): Destination
    {
        $ssoLocationNodes = $xpath->query('/md:EntityDescriptor/md:IDPSSODescriptor/md:SingleSignOnService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"]/@Location');
        if (!($ssoLocationNodes instanceof DOMNodeList) || $ssoLocationNodes->length !== 1) {
            throw new InvalidMfaMetadataUrlResponseException('A valid SingleSignOnService Location with post binding not found in metadata.');
        }
        $ssoLocation = $ssoLocationNodes->item(0)->nodeValue ?? '';

        return new Destination($ssoLocation);
    }

    private function doParseCertificates(DOMXPath $xpath): CertificateCollection
    {
        $certificateNodes = $xpath->query('/md:EntityDescriptor/md:IDPSSODescriptor/md:KeyDescriptor[@use="signing"]/ds:KeyInfo/ds:X509Data/ds:X509Certificate');
        if (!($certificateNodes instanceof DOMNodeList) || $certificateNodes->length === 0) {
            throw new InvalidMfaMetadataUrlResponseException('Certificates not found in metadata.');
        }
        $certificates = [];
        foreach ($certificateNodes as $certificateNode) {
            $certificates[] = Certificate::toPem($certificateNode->nodeValue ?? '');
        }

        return CertificateCollection::fromStringArray($certificates);
    }
}
