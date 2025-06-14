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

namespace Dev\Mock;

use DateInterval;
use DateTime;
use Exception;
use LogicException;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RuntimeException;
use SAML2\Assertion;
use SAML2\AuthnRequest as SAML2AuthnRequest;
use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use SAML2\Message;
use SAML2\Response;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\XML\saml\SubjectConfirmationData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MockGateway
{
    final public const PARAMETER_REQUEST = 'SAMLRequest';
    final public const PARAMETER_RELAY_STATE = 'RelayState';
    final public const PARAMETER_SIGNATURE = 'Signature';
    final public const PARAMETER_SIGNATURE_ALGORITHM = 'SigAlg';

    private readonly \DateTime $currentTime;

    /**
     * @throws Exception
     */
    public function __construct(
        private readonly MockConfiguration $gatewayConfiguration
    ) {
        $this->currentTime = new DateTime();
    }

    /**
     * @param string $fullRequestUri
     * @return Response
     * @throws Exception
     */
    public function handleSsoSuccess(Request $request, $fullRequestUri, array $attributes)
    {
        // parse the authnRequest
        $authnRequest = $this->parseRequest($request, $fullRequestUri);

        // get parameters from authnRequest
        $nameId = $authnRequest->getNameId() !== null ? $authnRequest->getNameId()->getValue() : '';
        $destination = $authnRequest->getAssertionConsumerServiceURL();
        $authnContextClassRef = current($authnRequest->getRequestedAuthnContext()['AuthnContextClassRef']);
        $requestId = $authnRequest->getId();

        // handle success
        return $this->createSecondFactorOnlyResponse(
            $nameId,
            $destination,
            $authnContextClassRef,
            $requestId,
            $attributes
        );
    }

    public function handleSsoFailure(
        Request $request,
        string $fullRequestUri,
        string $status,
        string $subStatus,
        string $message = ''
    ): Response {
        // parse the authnRequest
        $authnRequest = $this->parseRequest($request, $fullRequestUri);

        // get parameters from authnRequest
        $destination = $authnRequest->getAssertionConsumerServiceURL();
        $requestId = $authnRequest->getId();

        return $this->createFailureResponse($destination, $requestId, $status, $subStatus, $message);
    }


    /**
     * @param string $nameId
     * @param string $destination The ACS location
     * @param string|null $authnContextClassRef The loa level
     * @param string $requestId The requestId
     * @param array $attributes All new attributes, as an associative array.
     * @return Response
     */
    private function createSecondFactorOnlyResponse($nameId, $destination, $authnContextClassRef, $requestId, array $attributes)
    {
        $assertion = $this->createNewAssertion(
            $nameId,
            $authnContextClassRef,
            $destination,
            $requestId
        );

        $assertion->setAttributes($attributes);

        return $this->createNewAuthnResponse(
            $assertion,
            $destination,
            $requestId
        );
    }

    /**
     * @param string $samlRequest
     * @param string $fullRequestUri
     * @return SAML2AuthnRequest
     * @throws Exception
     */
    private function parseRequest(Request $request, $fullRequestUri)
    {
        // the GET parameter is already urldecoded by Symfony, so we should not do it again.
        $requestData = $request->get(self::PARAMETER_REQUEST);

        if (empty($requestData)) {
            throw new BadRequestHttpException('Missing a request, did not receive a request or request was empty');
        }

        $samlRequest = base64_decode((string) $requestData, true);
        if ($samlRequest === false) {
            throw new BadRequestHttpException('Failed decoding the request, did not receive a valid base64 string');
        }

        // Catch any errors gzinflate triggers
        $errorNo = $errorMessage = null;
        set_error_handler(function ($number, $message) use (&$errorNo, &$errorMessage): void {
            $errorNo      = $number;
            $errorMessage = $message;
        });
        $samlRequest = gzinflate($samlRequest);
        restore_error_handler();

        if ($samlRequest === false) {
            throw new BadRequestHttpException(sprintf(
                'Failed inflating the request; error "%d": "%s"',
                $errorNo,
                $errorMessage
            ));
        }

        // 1. Parse to xml object
        $document = DOMDocumentFactory::fromString($samlRequest);

        try {
        // 2. Parse saml request
        $authnRequest = Message::fromXML($document->firstChild);
        } catch (Exception) {
            throw new RuntimeException('The received request is not an AuthnRequest');
        }

        // 3. Validate destination
        if (!$authnRequest->getDestination() === $fullRequestUri) {
            throw new BadRequestHttpException(sprintf(
                'Actual Destination "%s" does not match the AuthnRequest Destination "%s"',
                $fullRequestUri,
                $authnRequest->getDestination()
            ));
        }

        // 4. Validate issuer
        if (!$this->gatewayConfiguration->getServiceProviderEntityId() === $authnRequest->getIssuer()) {
            throw new BadRequestHttpException(sprintf(
                'Actual issuer "%s" does not match the AuthnRequest Issuer "%s"',
                $this->gatewayConfiguration->getServiceProviderEntityId(),
                $authnRequest->getIssuer()
            ));
        }

        // 5. Validate key
        // Note: $authnRequest->validate throws an Exception when the signature does not match.
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
        $key->loadKey($this->gatewayConfiguration->getIdentityProviderPublicKeyCertData());

        // The query string to validate needs to be urlencoded again because Symfony has already decoded this for us
        $query = self::PARAMETER_REQUEST . '=' . urlencode((string) $requestData);
        $query .= '&' . self::PARAMETER_SIGNATURE_ALGORITHM . '=' . urlencode((string) $request->get(self::PARAMETER_SIGNATURE_ALGORITHM));

        $signature = base64_decode((string) $request->get(self::PARAMETER_SIGNATURE));

        $isVerified = $key->verifySignature($query, $signature);
        if ($isVerified === false || $isVerified < 1) {
            throw new BadRequestHttpException(
                'Validation of the signature in the AuthnRequest failed'
            );
        }

        return $authnRequest;
    }

    /**
     * @return string
     */
    public function parsePostResponse(Response $response)
    {
        return $response->toUnsignedXML()->ownerDocument->saveXML();
    }

    /**
     * @param string $destination The ACS location
     * @param string $requestId The requestId
     * @param string $status The response status (see \SAML2\Constants)
     * @param string|null $subStatus An optional substatus (see \SAML2\Constants)
     * @param string|null $message The textual message
     * @return Response
     */
    private function createFailureResponse(string $destination, string $requestId, string $status, $subStatus = null, $message = null): Response
    {
        $response = new Response();
        $response->setDestination($destination);
        $issuer = new Issuer();
        $issuer->setValue($this->gatewayConfiguration->getIdentityProviderEntityId());
        $response->setIssuer($issuer);
        $response->setIssueInstant($this->getTimestamp());
        $response->setInResponseTo($requestId);


        if (!$this->isValidResponseStatus($status)) {
            throw new LogicException('Trying to set invalid Response Status');
        }

        if ($subStatus && !$this->isValidResponseSubStatus($subStatus)) {
            throw new LogicException('Trying to set invalid Response SubStatus');
        }

        $status = ['Code' => $status];
        if ($subStatus) {
            $status['SubCode'] = $subStatus;
        }
        if ($message) {
            $status['Message'] = $message;
        }

        $response->setStatus($status);

        return $response;
    }

    private function createNewAuthnResponse(Assertion $newAssertion, string $destination, string $requestId): Response
    {
        $issuer = new Issuer();
        $issuer->setValue($this->gatewayConfiguration->getIdentityProviderEntityId());
        $response = new Response();
        $response->setAssertions([$newAssertion]);
        $response->setIssuer($issuer);
        $response->setIssueInstant($this->getTimestamp());
        $response->setDestination($destination);
        $response->setInResponseTo($requestId);

        return $response;
    }

    /**
     * @param string|null $nameId
     * @param string $emailAddress
     * @param string $authnContextClassRef
     * @param string $destination The ACS location
     * @param string $requestId The requestId
     * @return Assertion
     */
    private function createNewAssertion(
        string $nameId,
        string $authnContextClassRef,
        string $destination,
        string $requestId
    ): Assertion {
        $issuer = new Issuer();
        $issuer->setValue($this->gatewayConfiguration->getIdentityProviderEntityId());
        $newAssertion = new Assertion();
        $newAssertion->setNotBefore($this->currentTime->getTimestamp());
        $newAssertion->setNotOnOrAfter($this->getTimestamp('PT5M'));
        $newAssertion->setIssuer($issuer);
        $newAssertion->setIssueInstant($this->getTimestamp());
        $this->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $destination, $requestId);
        if (!is_null($nameId)) {
            $newNameId = new NameID();
            $newNameId->setFormat(Constants::NAMEID_UNSPECIFIED);
            $newNameId->setValue($nameId);
            $newAssertion->setNameId($newNameId);
        }
        $newAssertion->setValidAudiences([$this->gatewayConfiguration->getServiceProviderEntityId()]);
        $this->addAuthenticationStatementTo($newAssertion, $authnContextClassRef);

        return $newAssertion;
    }

    /**
     * @param string $destination The ACS location
     * @param string $requestId The requestId
     */
    private function addSubjectConfirmationFor(Assertion $newAssertion, $destination, $requestId): void
    {
        $confirmation  = new SubjectConfirmation();
        $confirmation->setMethod(Constants::CM_BEARER);
        $confirmationData = new SubjectConfirmationData();
        $confirmationData->setInResponseTo($requestId);
        $confirmationData->setRecipient($destination);
        $confirmationData->setNotOnOrAfter($newAssertion->getNotOnOrAfter());
        $confirmation->setSubjectConfirmationData($confirmationData);
        $newAssertion->setSubjectConfirmation([$confirmation]);
    }

    private function addAuthenticationStatementTo(Assertion $assertion, string $authnContextClassRef): void
    {
        $assertion->setAuthnInstant($this->getTimestamp());
        $assertion->setAuthnContextClassRef($authnContextClassRef);
        $assertion->setAuthenticatingAuthority([$this->gatewayConfiguration->getIdentityProviderEntityId()]);
    }

    /**
     * @param ?string $interval a DateInterval compatible interval to skew the time with
     */
    private function getTimestamp(?string $interval = null): int
    {
        $time = clone $this->currentTime;

        if ($interval) {
            $time->add(new DateInterval($interval));
        }

        return $time->getTimestamp();
    }

    private function signAssertion(Assertion $assertion): Assertion
    {
        $assertion->setSignatureKey($this->loadPrivateKey());
        $assertion->setCertificates([$this->getPublicCertificate()]);

        return $assertion;
    }

    private function loadPrivateKey(): XMLSecurityKey
    {
        $xmlSecurityKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $xmlSecurityKey->loadKey($this->gatewayConfiguration->getIdentityProviderGetPrivateKeyPem());

        return $xmlSecurityKey;
    }

    public function getPublicCertificate(): string|bool
    {
        return $this->gatewayConfiguration->getIdentityProviderPublicKeyCertData();
    }

    private function isValidResponseStatus(string $status): bool
    {
        return in_array($status, [
            Constants::STATUS_SUCCESS,            // weeee!
            Constants::STATUS_REQUESTER,          // Something is wrong with the AuthnRequest
            Constants::STATUS_RESPONDER,          // Something went wrong with the Response
            Constants::STATUS_VERSION_MISMATCH,   // The version of the request message was incorrect
        ]);
    }

    private function isValidResponseSubStatus($subStatus): bool
    {
        return in_array($subStatus, [
            Constants::STATUS_AUTHN_FAILED,               // failed authentication
            Constants::STATUS_INVALID_ATTR,
            Constants::STATUS_INVALID_NAMEID_POLICY,
            Constants::STATUS_NO_AUTHN_CONTEXT,           // insufficient Loa or Loa cannot be met
            Constants::STATUS_NO_AVAILABLE_IDP,
            Constants::STATUS_NO_PASSIVE,
            Constants::STATUS_NO_SUPPORTED_IDP,
            Constants::STATUS_PARTIAL_LOGOUT,
            Constants::STATUS_PROXY_COUNT_EXCEEDED,
            Constants::STATUS_REQUEST_DENIED,
            Constants::STATUS_REQUEST_UNSUPPORTED,
            Constants::STATUS_REQUEST_VERSION_DEPRECATED,
            Constants::STATUS_REQUEST_VERSION_TOO_HIGH,
            Constants::STATUS_REQUEST_VERSION_TOO_LOW,
            Constants::STATUS_RESOURCE_NOT_RECOGNIZED,
            Constants::STATUS_TOO_MANY_RESPONSES,
            Constants::STATUS_UNKNOWN_ATTR_PROFILE,
            Constants::STATUS_UNKNOWN_PRINCIPAL,
            Constants::STATUS_UNSUPPORTED_BINDING,
        ]);
    }
}
