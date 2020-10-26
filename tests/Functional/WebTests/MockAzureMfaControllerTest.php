<?php
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

namespace Surfnet\AzureMfa\Test\Functional\WebTests;

use Exception;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Configuration\PrivateKey;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @SuppressWarnings(PHPCPD)
 */
class MockAzureMfaControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\KernelBrowser
     */
    private $client;
    /**
     * @var string
     */
    private $publicKey;
    /**
     * @var string
     */
    private $privateKey;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects(true);
        $this->client->disableReboot();

        $projectDir = self::$kernel->getProjectDir();

        $this->publicKey = $projectDir . '/vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_publickey.cer';
        $this->privateKey = $projectDir . '/vendor/surfnet/stepup-saml-bundle/src/Resources/keys/development_privatekey.pem';
    }

    public function testDecisionPage()
    {
        $emailAddress = 'user@organization.tld';

        $authnRequestUrl = $this->createAuthnRequestUrl($this->createServiceProvider(), $this->createIdentityProvider(), $emailAddress);

        $crawler = $this->client->request('GET', $authnRequestUrl);

        // Test if on decision page
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Select response', $crawler->filter('h2')->text());
    }

    public function testSuccessfulResponse()
    {
        $emailAddress = 'user@organization.tld';
        $releasedEmailAddresses = ['user@organization.tld'];

        $authnRequestUrl = $this->createAuthnRequestUrl($this->createServiceProvider(), $this->createIdentityProvider(), $emailAddress);

        $crawler = $this->client->request('GET', $authnRequestUrl);

        // Test if on decision page
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Select response', $crawler->filter('h2')->text());

        // Post response
        $this->postMockIdpForm($crawler, 'success', $releasedEmailAddresses);

        // Test if on sp acs
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('urn:oasis:names:tc:SAML:2.0:status:Success', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('urn:mace:dir:attribute-def:mail', $crawler->html());
        $this->assertStringContainsString($emailAddress, $this->client->getResponse()->getContent());
    }

    public function testSuccessfulResponseWithoutMailAttribute()
    {
        $emailAddress = 'user@organization.tld';
        $releasedEmailAddresses = null;

        $authnRequestUrl = $this->createAuthnRequestUrl($this->createServiceProvider(), $this->createIdentityProvider(), $emailAddress);

        $crawler = $this->client->request('GET', $authnRequestUrl);

        // Test if on decision page
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Select response', $crawler->filter('h2')->text());

        // Post response
        $crawler = $this->postMockIdpForm($crawler, 'success', $releasedEmailAddresses);

        // Test if on sp acs
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('urn:oasis:names:tc:SAML:2.0:status:Success', $crawler->html());
        $this->assertStringNotContainsString('urn:mace:dir:attribute-def:mail', $crawler->html());
    }

    public function testSuccessfulResponseWithMultipleEmailAddress()
    {
        $emailAddress = 'user@organization.tld';
        $releasedEmailAddresses = ['email1@organization.tld', 'email2@organization.tld'];

        $authnRequestUrl = $this->createAuthnRequestUrl($this->createServiceProvider(), $this->createIdentityProvider(), $emailAddress);

        $crawler = $this->client->request('GET', $authnRequestUrl);

        // Test if on decision page
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Select response', $crawler->filter('h2')->text());

        // Post response
        $crawler = $this->postMockIdpForm($crawler, 'success', $releasedEmailAddresses);

        // Test if on sp acs
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('urn:oasis:names:tc:SAML:2.0:status:Success', $crawler->html());
        $this->assertStringContainsString('email1@organization.tld', $crawler->html());
        $this->assertStringContainsString('email2@organization.tld', $crawler->html());
    }


    public function testUserCancelledResponse()
    {
        $emailAddress = 'user@organization.tld';

        $authnRequestUrl = $this->createAuthnRequestUrl($this->createServiceProvider(), $this->createIdentityProvider(), $emailAddress);

        $crawler = $this->client->request('GET', $authnRequestUrl);

        // Test if on decision page
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Select response', $crawler->filter('h2')->text());

        // Post response
        $crawler = $this->postMockIdpForm($crawler, 'user-cancelled');

        // Test if on sp acs
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Demo Service provider ConsumerAssertionService endpoint', $crawler->filter('h2')->text());
        $this->assertStringContainsString('Error SAMLResponse', $crawler->html());
        $this->assertStringContainsString('Responder/AuthnFailed Authentication cancelled by user', $crawler->html());
    }

    public function testUnsuccessfulResponse()
    {
        $emailAddress = 'user@organization.tld';

        $authnRequestUrl = $this->createAuthnRequestUrl($this->createServiceProvider(), $this->createIdentityProvider(), $emailAddress);

        $crawler = $this->client->request('GET', $authnRequestUrl);

        // Test if on decision page
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Select response', $crawler->filter('h2')->text());

        // Post response
        $crawler = $this->postMockIdpForm($crawler, 'unknown');

        // Test if on sp acs
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Demo Service provider ConsumerAssertionService endpoint', $crawler->filter('h2')->text());
        $this->assertStringContainsString('Error SAMLResponse', $crawler->html());
        $this->assertStringContainsString('Responder/AuthnFailed', $crawler->html());
    }

    /**
     * @param ServiceProvider $serviceProvider
     * @param IdentityProvider $identityProvider
     * @param string $url
     * @param string $emailAddress
     * @return string
     */
    private function createAuthnRequestUrl(ServiceProvider $serviceProvider, IdentityProvider $identityProvider, string $emailAddress)
    {
        $authnRequest = AuthnRequestFactory::createNewRequest($serviceProvider, $identityProvider);

        // Set subject tyo UPN (user email address)
        $authnRequest->setSubject($emailAddress);

        // Set authnContextClassRef to force MFA
        $authnRequest->setAuthenticationContextClassRef('http://schemas.microsoft.com/claims/multipleauthn');

        // Build request query parameters.
        $requestAsXml = $authnRequest->getUnsignedXML();
        $encodedRequest = base64_encode(gzdeflate($requestAsXml));
        $queryParams = [AuthnRequest::PARAMETER_REQUEST => $encodedRequest];

        // Create redirect response.
        $query = $this->signRequestQuery($queryParams, $serviceProvider);
        return sprintf('%s?%s', $identityProvider->getSsoUrl(), $query);
    }

    private function createServiceProvider(): ServiceProvider
    {
        $samlBundle = '';
        return new ServiceProvider(
            [
                'entityId' => 'https://azuremfa.stepup.example.com/saml/metadata',
                'assertionConsumerUrl' => 'https://azuremfa.stepup.example.com/demo/sp/acs',
                'certificateFile' => $this->publicKey,
                'privateKeys' => [
                    new PrivateKey(
                        $this->privateKey,
                        'default'
                    ),
                ],
                'sharedKey' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
            ]
        );
    }

    private function createIdentityProvider(): IdentityProvider
    {
        $samlBundle = '';
        return new IdentityProvider(
            [
                'entityId' => 'https://azuremfa.stepup.example.com/mock/idp/metadata',
                'ssoUrl' => 'https://azuremfa.stepup.example.com/mock/sso',
                'certificateFile' => $this->publicKey,
                'privateKeys' => [
                    new PrivateKey(
                        $this->privateKey,
                        'default'
                    ),
                ],
                'sharedKey' => sprintf('%s/src/Resources/keys/development_publickey.cer', $samlBundle),
            ]
        );
    }

    /**
     * Sign AuthnRequest query parameters.
     *
     * @param array $queryParams
     * @param ServiceProvider $serviceProvider
     * @return string
     *
     * @throws Exception
     */
    private function signRequestQuery(array $queryParams, ServiceProvider $serviceProvider)
    {
        /** @var  $securityKey */
        $securityKey = $this->loadServiceProviderPrivateKey($serviceProvider);
        $queryParams[AuthnRequest::PARAMETER_SIGNATURE_ALGORITHM] = $securityKey->type;
        $toSign = http_build_query($queryParams);
        $signature = $securityKey->signData($toSign);

        return $toSign . '&Signature=' . urlencode(base64_encode($signature));
    }

    /**
     * Loads the private key from the service provider.
     *
     * @param ServiceProvider $serviceProvider
     * @return XMLSecurityKey
     *
     * @throws Exception
     */
    private function loadServiceProviderPrivateKey(ServiceProvider $serviceProvider)
    {
        $keyLoader = new PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey(
            $serviceProvider->getPrivateKey(PrivateKey::NAME_DEFAULT)
        );
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        return $key;
    }

    /**
     * @param Crawler $crawler
     * @param string $state State button to press
     * @param string[]|null $emailAddresses
     * @return Crawler
     */
    private function postMockIdpForm(Crawler $crawler, $state, array $emailAddresses = null)
    {
        $data = '[]';
        if (is_array($emailAddresses)) {
            $jsonMail = json_encode($emailAddresses);
            $data = sprintf('[{"name":"urn:mace:dir:attribute-def:mail","value":%s}]', $jsonMail);
        }

        // Test if on decision page
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Select response', $crawler->filter('h2')->text());

        // Set response attributes and post form
        $form = $crawler->selectButton($state)->form();
        $form->get('attributes')->setValue($data);
        $crawler = $this->client->submit($form);

        // Post response
        $form = $crawler->selectButton('Post')->form();

        return $this->client->submit($form);
    }
}
