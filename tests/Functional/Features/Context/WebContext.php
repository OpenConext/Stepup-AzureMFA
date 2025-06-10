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

namespace Surfnet\AzureMfa\Test\Features\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext;
use DOMNode;
use DOMNodeList;
use Exception;
use PhpParser\Node\Name;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\AuthnRequest;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Compat\ContainerSingleton;
use SAML2\Configuration\PrivateKey;
use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use SAML2\Message;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\SAML2\AuthnRequest as Saml2AuthnRequest;
use Surfnet\SamlBundle\SAML2\BridgeContainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use function parse_str;

class WebContext implements Context
{
    /**
     * @var MinkContext
     */
    protected $minkContext;

    /**
     * @var string
     */
    protected $previousMinkSession;

    public function __construct(
        private readonly KernelInterface $kernel,
        BridgeContainer $bridgeContainer
    ) {
        ContainerSingleton::setContainer($bridgeContainer);
    }

    /**
     * Fetch the required contexts.
     *
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->minkContext = $environment->getContext(MinkContext::class);
    }

    /**
     * Init the cache dir.
     *
     * @BeforeScenario
     */
    public function initCacheDir(BeforeScenarioScope $scope)
    {
        // Ensure the cache directory exists
        $cacheDir = __DIR__ . '/../../../../var/cache/test/federation-metadata';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
    }

    /**
     * Set mink driver to goutte
     *
     * @BeforeScenario @remote
     */
    public function setGoutteDriver()
    {
        $this->previousMinkSession = $this->minkContext->getMink()->getDefaultSessionName();
        $this->minkContext->getMink()->setDefaultSessionName('goutte');
    }

    /**
     * Set mink driver to goutte
     *
     * @AfterScenario @remote
     */
    public function resetGoutteDriver()
    {
        $this->minkContext->getMink()->setDefaultSessionName($this->previousMinkSession);
    }

    /**
     * Create AuthnRequest from demo IdP.
     *
     * @When the service provider send the AuthnRequest with HTTP-Redirect binding
     *
     * @throws \Surfnet\SamlBundle\Exception\NotFound
     */
    public function callIdentityProviderSSOActionWithAuthnRequest()
    {
        $this->minkContext->visit('https://pieter.aai.surfnet.nl/simplesamlphp/sp.php?sp=default-sp');
        $this->minkContext->selectOption('idp', 'https://azuremfa.dev.openconext.local/saml/metadata');
        $this->minkContext->pressButton('Login');
    }

    /**
     * @return \Surfnet\SamlBundle\Entity\ServiceProvider
     *
     * @throws \Surfnet\SamlBundle\Exception\NotFound
     */
    public function getServiceProvider()
    {
        $serviceProviders = $this->kernel->getContainer()->get('surfnet_saml.remote.service_providers');
        return $serviceProviders->getServiceProvider(
            'https://pieter.aai.surfnet.nl/simplesamlphp/module.php/saml/sp/metadata.php/default-sp'
        );
    }

    /**
     * @Given /^a normal SAML 2.0 AuthnRequest from an unknown service provider$/
     *
     * @throws \Exception
     */
    public function aNormalSAMLAuthnRequestFromAnUnknownServiceProvider()
    {
        $authnRequest = new AuthnRequest();
        $authnRequest->setAssertionConsumerServiceURL('https://unkown_service_provider/saml/acs');
        $authnRequest->setDestination('/saml/sso');
        $issuer = new Issuer();
        $issuer->setValue('https://unkown_service_provider/saml/metadata');
        $authnRequest->setIssuer($issuer);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);

        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/config/azuremfa/azuremfa_idp.key', 'default'))
        );

        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();
        $this->minkContext->visitPath('/saml/sso?' . $query);
    }

    /**
     * @param PrivateKey $key
     * @return XMLSecurityKey
     * @throws \Exception
     */
    private static function loadPrivateKey(PrivateKey $key)
    {
        $keyLoader = new PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey($key);

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        return $key;
    }

    /**
     * @Given /^I send a registration request to "(?P<destination>(?:[^"]|\\")*)"$/
     */
    public function iSendARegistrationRequestRequestTo($destination)
    {
        $authnRequest = new AuthnRequest();
        $authnRequest->setAssertionConsumerServiceURL('https://azuremfa.dev.openconext.local/saml/acs');
        $authnRequest->setDestination($destination);
        $issuer = new Issuer();
        $issuer->setValue('https://azuremfa.dev.openconext.local/saml/metadata');
        $authnRequest->setIssuer($issuer);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);

        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/config/azuremfa/azuremfa_idp.key', 'default'))
        );

        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();
        $this->minkContext->visitPath($destination.'?' . $query);
    }

    /**
     * @Given I send an authentication request to :destination with NameID :nameId
     */
    public function iSendAnAuthenticationRequestRequestToWithNameID($destination, $nameId = "")
    {
        $authnRequest = new AuthnRequest();
        $authnRequest->setAssertionConsumerServiceURL('https://azuremfa.dev.openconext.local/saml/acs');
        $authnRequest->setDestination($destination);
        $issuer = new Issuer();
        $issuer->setValue('https://azuremfa.dev.openconext.local/saml/metadata');
        $nameIdVo = new NameID();
        $nameIdVo->setValue($nameId);
        $authnRequest->setIssuer($issuer);
        $authnRequest->setNameId($nameIdVo);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
        $authnRequest->setRequesterID(['https://azuremfa.dev.openconext.local/saml/metadata']);

        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/config/azuremfa/azuremfa_idp.key', 'default'))
        );

        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();
        $this->minkContext->visitPath($destination.'?' . $query);
    }

    /**
     * @Given /^I have no cached identity provider for "([^"]*)"$/
     */
    public function iHaveNoCertificateDataForInstitution($institution)
    {
        @unlink(__DIR__ . '/../../../../federation-metadata/' . $institution . '.cache');
    }

    /**
     * @Given /^I have an invalid cached identity provider for "([^"]*)"$/
     */
    public function iHaveOldCertificateDataForInstitution($institution) {
        file_put_contents(__DIR__ . '/../../../../federation-metadata/' . $institution . '.cache',
        '{
            "updated": "2025-06-05T15:49:55+02:00",
            "entity_id": "https:\/\/institution-c.example.com\/",
            "sso_location": "https:\/\/azuremfa.dev.openconext.local\/mock\/sso",
            "certificates": [
                "MIIEEzCCAnsCFEow2E90q1t\/\/LDuqkgF2zo7VNo4MA0GCSqGSIb3DQEBCwUAMEYxGzAZBgNVBAMMEkF6dXJlLU1GQSBHU1NQIElkUDEnMCUGA1UECgweRGV2ZWxvcG1lbnQgRG9ja2VyIGVudmlyb25tZW50MB4XDTIzMDUyNTA5MzMyM1oXDTI4MDUyMzA5MzMyM1owRjEbMBkGA1UEAwwSQXp1cmUtTUZBIEdTU1AgSWRQMScwJQYDVQQKDB5EZXZlbG9wbWVudCBEb2NrZXIgZW52aXJvbm1lbnQwggGiMA0GCSqGSIb3DQEBAQUAA4IBjwAwggGKAoIBgQCWaoXdTdU3N0RL2jK\/88PEN3jwyyz7AFJX64Rfx48CtCsI3Hze+0i+0KQgILsVU91kKujllFBM6N4V5PKQ+9Z5zafJeuhT80zQ9jcHVxyQoKi30438fBGzlAKD9hGojG7DwjKopK+96Eawvu90KCxf8q7STh50n8dO6hnxWtE8RGk5a9R2cMDxEuOlvrW2B8Ih+EVCT3OmOsCQdp31TuTt5x3xLxmY\/04mGGPpQi9PBV38O2uTd4G2mbqGqNGx6S6iPAMgh6u4NVmg03iqBKkFJgQvNRCdif+gMQTKEW0mJwr62PrEQrPBoBphgCpJNF9pnEy\/+mdWiKCo8lvVxiPGQaaKyoNvZEt1IROwp8Ga2gLEoFjtcMcodnLgudusDOCH6Idp0CtuTkrf3hLIxKjQMOFTCiCmOCtMlJZa9+l7LbhzEGcJUcHH0i1k+ufqUhOSBrrfKoiohixAnW+bayqymef+Zy32YoT+\/LDjoP\/vyMrNnRwpwqguPMwBF+HWgwUCAwEAATANBgkqhkiG9w0BAQsFAAOCAYEAReFJH\/X+PyA8cFe6RdCgyTbuRuq2rTgadKpqfhhbXlwcOTh8rEpevqFf8tequegCj7fFZgz+hIL075ZsEcZwk2N8F8m32cVjmYHar2rLsYEkqhEc\/yCUjyGffqUeZBVmdUnUM6ggGsIHqcjTvrNhmFrh3ManebvZkjvDyJCkrwUOGYvCpbFjXa4CW1Rp+I0+e7HnQeyFW3p+3T0SAmdo3eJEZLhRsMm\/YLcyCW7IRTVvpTvGoxhbvQU1k6EtkhLcahA+MWVzNbgiIdHP\/otSQnaLW243sxoxYm7EiuAihnQ0iRaNEzsFrx\/W06G0e5rmTbWPGc4LZj6YDKd7531SGIwqOOC1wrzrZ36iuwPm5PrZReCWH3ptR6bSszQerbQsx6wkumYN7iDZg9EK9ADHRzfovbqOPad2s+N5iVWAOfEXGqItZcrLdW53vUOqbfXXuFt7szhtdvTWRWWQQJryrg61UmLgJcLb3xMMdZZ+D6mcXqa3v2cSzGdfO9123456"
            ],
            "is_azure_ad": true
        }');
    }

    /**
     * @Given I send an authentication request to :destination without NameID
     */
    public function iSendAnAuthenticationRequestRequestToWithoutNameID($destination, $nameId = "")
    {
        $this->iSendAnAuthenticationRequestRequestToWithNameID($destination);
    }

    /**
     * @Then the SAML Response should contain element :elementName with value :value
     */
    public function theSamlResponseShouldContainElementWithValue($elementName, $value)
    {
        $responseXml = $this->receiveResponse();
        $elementSearchResult = $this->getElementByName($responseXml, $elementName);

        if ($value == $elementSearchResult->nodeValue) {
            return;
        }
        throw new Exception(
            sprintf(
                'The value of element %s did not match expected value "%s", actual value: "%s"',
                $elementName,
                $value,
                $elementSearchResult->nodeValue
            )
        );
    }

    /**
     * @Given the SAML Response should contain element :elementName with attribute :attributeName with attribute value :expectedAttributeValue
     */
    public function theSAMLResponseShouldContainElementWithAttributeWithAttributeValue(
        $elementName,
        $attributeName,
        $expectedAttributeValue
    ) {
        $responseXml = $this->receiveResponse();
        $elementSearchResults = $this->getElementsByName($responseXml, $elementName);

        foreach ($elementSearchResults as $elementSearchResult) {
            $attribute = $elementSearchResult->attributes->getNamedItem($attributeName);
            $actualAttributeValue = $attribute->nodeValue;

            if ($expectedAttributeValue == $actualAttributeValue) {
                return;
            }
        }

        throw new Exception(
            sprintf(
                'The value of element %s did not match expected value "%s", actual value: "%s"',
                $elementName,
                $expectedAttributeValue,
                $actualAttributeValue
            )
        );
    }

    /**
     * @Given the SAML Response should contain element :elementName with value containing :value
     */
    public function theSamlResponseShouldContainElementWithValueContaining($elementName, $value) {
        $responseXml = $this->receiveResponse();
        $elementSearchResult = $this->getElementByName($responseXml, $elementName);

        if (strstr($elementSearchResult->nodeValue, $value) !== false) {
            return;
        }
        throw new Exception(
            sprintf(
                'The value of element %s did not contain expected value "%s", actual value: "%s"',
                $elementName,
                $value,
                $elementSearchResult->nodeValue
            )
        );
    }

    /**
     * @Given /^I should see a NameID with email address "(?P<emailAddress>(?:[^"]|\\")*)"$/
     */
    public function iShouldSeeANameIDWithEmailAddress($emailAddress){

        $quotedEmailAddress = preg_quote($emailAddress);
        $regex = '/[a-z0-9]{1,6}-[a-z0-9]{1,4}\|' . $quotedEmailAddress . '/';

        $actual = $this->minkContext->getSession()->getPage()->getContent();
        $message = sprintf('The MFA NameId "%s" was not found anywhere in the HTML response of the current page.', $emailAddress);

        $match = preg_match($regex, $actual);
        if ($match !== 1) {
            throw new ExpectationException($message, $this->minkContext->getSession()->getDriver());
        }
    }

    /**
     * @Given I have :language set as my stepup-locale cookie value
     */
    public function iHaveSetAsMyPreference($language)
    {
        $this->minkContext->getSession()->setCookie('stepup_locale', $language);
    }

    private function receiveResponse()
    {
        $samlResponse = $this->minkContext->getSession()->getPage()->find('css', 'input[name="SAMLResponse"]');
        if (!$samlResponse instanceof NodeElement) {
            throw new Exception('The SAMLResponse does not appear on the current page');
        }
        $responseValue = $samlResponse->getValue();
        $response = base64_decode($responseValue);
        $responseXml = DOMDocumentFactory::fromString($response);

        return $responseXml;
    }

    private function getElementsByName(\DOMDocument $responseXml, $elementName): DOMNodeList
    {
        $elementSearchResults = $responseXml->getElementsByTagName($elementName);
        if ($elementSearchResults->count() === 0) {
            throw new Exception(
                sprintf('Element named: %s was not found in the SAML Response', $elementName)
            );
        }
        return $elementSearchResults;
    }

    private function getElementByName(\DOMDocument $responseXml, $elementName): DOMNode
    {
        $elementSearchResults = $responseXml->getElementsByTagName($elementName);
        if ($elementSearchResults->count() === 0) {
            throw new Exception(
                sprintf('Element named: %s was not found in the SAML Response', $elementName)
            );
        }
        if ($elementSearchResults->count() > 1) {
            throw new Exception(
                sprintf('Element named: %s was found more than once in the SAML Response', $elementName)
            );
        }

        return $elementSearchResults->item(0);
    }

    /**
     * @Given /^the received AuthNRequest should have the ForceAuthn attribute$/
     */
    public function theReceivedAuthNRequestShouldHaveTheForceAuthnAttribute()
    {
        // Get the AuthNRequest from the current URL, so only redirect binding is supported here!
        $currentUrl = $this->minkContext->getSession()->getCurrentUrl();
        $parsed = parse_url($currentUrl);
        if (!isset($parsed['query'])) {
            throw new Exception('No query parameters found in request');
        }

        parse_str($parsed['query'], $parsedQueryString);
        if (!isset($parsedQueryString['SAMLRequest'])) {
            throw new Exception('No SAMLRequest parameter found in the query string');
        }

        $authNRequest = base64_decode($parsedQueryString['SAMLRequest'], true);

        // Catch any errors gzinflate triggers
        $errorNo = $errorMessage = null;
        set_error_handler(function ($number, $message) use (&$errorNo, &$errorMessage) {
            $errorNo      = $number;
            $errorMessage = $message;
        });
        $authNRequest = gzinflate($authNRequest);
        restore_error_handler();

        if ($authNRequest === false) {
            throw new Exception(sprintf(
                'Failed inflating the request; error "%d": "%s"',
                $errorNo,
                $errorMessage
            ));
        }

        // Parse an XML document from the inflated authnrequest
        $document = DOMDocumentFactory::fromString($authNRequest);

        // Next create a SAML2 VO
        $authnRequest = Message::fromXML($document->firstChild);

        if ($authnRequest->getForceAuthn() === false) {
            throw new Exception('ForceAuthn was not set on the request');
        }
    }
}
