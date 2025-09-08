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

namespace Dev\Controller;

use Dev\Mock\MockGateway;
use Exception;
use SAML2\Constants;
use SAML2\Response as SamlResponse;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\Certificate;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twig\Environment;

class MockAzureMfaController extends AbstractController
{
    public function __construct(
        private readonly MockGateway $mockStepupGateway,
        private readonly Environment $twig
    ) {
    }

    /**
     * This is the sso action used to mock a GSSP callout
     */
    #[Route(path: '/mock/sso', name: 'mock_sso')]
    public function sso(Request $request): SymfonyResponse
    {
        if (!in_array($this->getParameter('kernel.environment'), ['test', 'dev', 'smoketest'])) {
            throw new Exception('Invalid environment encountered.');
        }

        try {
            $status = $request->get('status');

            // Check binding
            if (!$request->isMethod(Request::METHOD_GET) &&  !$status) {
                throw new BadRequestHttpException(sprintf(
                    'Could not receive AuthnRequest from HTTP Request: expected a GET method, got %s',
                    $request->getMethod()
                ));
            }

            // show possible saml response status to return
            if (!$status) {
                // Present response
                $body = $this->twig->render(
                    'dev/mock-acs.html.twig',
                    [
                        'action' => $request->getUri(),
                        'responses' => [
                            'success',
                            'user-cancelled',
                            'unknown',
                        ],
                    ]
                );
                return new Response($body);
            }

            // Parse available responses
            $response = $this->getSelectedResponse($request, $status);

            // Present response
            $body = $this->twig->render(
                'dev/mock-acs-post.html.twig',
                [
                    'response' => $response,
                ]
            );

            return new Response($body);
        } catch (BadRequestHttpException $e) {
            return new Response($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new Response($e->getMessage(), SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * This is the metadata action used
     */
    #[Route(path: '/mock/metadata', name: 'mock_metadata')]
    public function metadata(Request $request): SymfonyResponse
    {
        $cert = new Certificate($this->mockStepupGateway->getPublicCertificate());
        $body = $this->twig->render(
            'dev/mock-metadata.xml.twig',
            [
                'publickeys' => [
                    $cert->getCertData(),
                ]
            ]
        );
        return new Response($body);
    }

    /**
     * This is the metadata action used
     */
    #[Route(path: '/mock/metadata-rollover', name: 'mock_metadata_rollover')]
    public function metadataRollover(Request $request): SymfonyResponse
    {
        $cert = new Certificate($this->mockStepupGateway->getPublicCertificate());
        $body = $this->twig->render(
            'dev/mock-metadata.xml.twig',
            [
                'publickeys' => [
                    "MIIEJTCCAw2gAwIBAgIJANug+o++1X5IMA0GCSqGSIb3DQEBCwUAMIGoMQswCQYDVQQGEwJOTDEQMA4GA1UECAwHVXRyZWNodDEQMA4GA1UEBwwHVXRyZWNodDEVMBMGA1UECgwMU1VSRm5ldCBCLlYuMRMwEQYDVQQLDApTVVJGY29uZXh0MRwwGgYDVQQDDBNTVVJGbmV0IERldmVsb3BtZW50MSswKQYJKoZIhvcNAQkBFhxzdXJmY29uZXh0LWJlaGVlckBzdXJmbmV0Lm5sMB4XDTE0MTAyMDEyMzkxMVoXDTE0MTExOTEyMzkxMVowgagxCzAJBgNVBAYTAk5MMRAwDgYDVQQIDAdVdHJlY2h0MRAwDgYDVQQHDAdVdHJlY2h0MRUwEwYDVQQKDAxTVVJGbmV0IEIuVi4xEzARBgNVBAsMClNVUkZjb25leHQxHDAaBgNVBAMME1NVUkZuZXQgRGV2ZWxvcG1lbnQxKzApBgkqhkiG9w0BCQEWHHN1cmZjb25leHQtYmVoZWVyQHN1cmZuZXQubmwwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDXuSSBeNJY3d4p060oNRSuAER5nLWT6AIVbv3XrXhcgSwc9m2b8u3ksp14pi8FbaNHAYW3MjlKgnLlopYIylzKD/6Ut/clEx67aO9Hpqsc0HmIP0It6q2bf5yUZ71E4CN2HtQceO5DsEYpe5M7D5i64kS2A7e2NYWVdA5Z01DqUpQGRBc+uMzOwyif6StBiMiLrZH3n2r5q5aVaXU4Vy5EE4VShv3Mp91sgXJj/v155fv0wShgl681v8yf2u2ZMb7NKnQRA4zM2Ng2EUAyy6PQ+Jbn+rALSm1YgiJdVuSlTLhvgwbiHGO2XgBi7bTHhlqSrJFK3Gs4zwIsop/XqQRBAgMBAAGjUDBOMB0GA1UdDgQWBBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAfBgNVHSMEGDAWgBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQBd80GpWKjp1J+Dgp0blVAox1s/WPWQlex9xrx1GEYbc5elp3svS+S82s7dFm2llHrrNOBt1HZVC+TdW4f+MR1xq8O5lOYjDRsosxZc/u9jVsYWYc3M9bQAx8VyJ8VGpcAK+fLqRNabYlqTnj/t9bzX8fS90sp8JsALV4g84Aj0G8RpYJokw+pJUmOpuxsZN5U84MmLPnVfmrnuCVh/HkiLNV2c8Pk8LSomg6q1M1dQUTsz/HVxcOhHLj/owwh3IzXf/KXV/E8vSYW8o4WWCAnruYOWdJMI4Z8NG1Mfv7zvb7U3FL1C/KLV04DqzALXGj+LVmxtDvuxqC042apoIDQV",
                    $cert->getCertData(),
                ],
            ]
        );
        return new Response($body);
    }

    private function getSelectedResponse(Request $request, string $status): array
    {
        switch (true) {
            case ($status == 'success'):
                // Parse successful
                $rawAttributes = $request->get('attributes');
                $attributes = $this->parseAttributes($rawAttributes);

                $samlResponse = $this->mockStepupGateway->handleSsoSuccess($request, $this->getFullRequestUri($request), $attributes);
                return $this->getResponseData($request, $samlResponse);

            case ($status == 'user-cancelled'):
                // Parse user cancelled
                $samlResponse = $this->mockStepupGateway->handleSsoFailure(
                    $request,
                    $this->getFullRequestUri($request),
                    Constants::STATUS_RESPONDER,
                    Constants::STATUS_AUTHN_FAILED,
                    'Authentication cancelled by user'
                );
                return $this->getResponseData($request, $samlResponse);

            case ($status == 'unknown'):
                // Parse unknown
                $samlResponse = $this->mockStepupGateway->handleSsoFailure(
                    $request,
                    $this->getFullRequestUri($request),
                    Constants::STATUS_RESPONDER,
                    Constants::STATUS_AUTHN_FAILED
                );
                return $this->getResponseData($request, $samlResponse);
            default:
                throw new BadRequestHttpException(sprintf(
                    'Could not create a response for status %s',
                    $status
                ));
        }
    }

    private function getResponseData(Request $request, SamlResponse $samlResponse): array
    {
        $rawResponse = $this->mockStepupGateway->parsePostResponse($samlResponse);

        return [
            'acu' => $samlResponse->getDestination(),
            'rawResponse' => $rawResponse,
            'encodedResponse' => base64_encode($rawResponse),
            'relayState' => $request->request->get(MockGateway::PARAMETER_RELAY_STATE),
        ];
    }

    private function getFullRequestUri(Request $request): string
    {
        return $request->getSchemeAndHttpHost() . $request->getBasePath() . $request->getPathInfo();
    }

    private function parseAttributes(string $data): array
    {
        @json_decode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException(sprintf(
                'Could not parse the attributes because no valid json was given %s',
                $data
            ));
        }

        $data = json_decode($data, true);

        $result = [];
        foreach ($data as $attr) {
            if (!array_key_exists('name', $attr)) {
                throw new BadRequestHttpException(sprintf(
                    'Could not parse the attributes because no valid name was given %s',
                    json_encode($data, JSON_THROW_ON_ERROR)
                ));
            }
            if (!array_key_exists('value', $attr)) {
                throw new BadRequestHttpException(sprintf(
                    'Could not parse the attributes because no valid value was given %s',
                    json_encode($data, JSON_THROW_ON_ERROR)
                ));
            }

            if (!is_array($attr['value'])) {
                throw new BadRequestHttpException(sprintf(
                    'Could not parse the attributes because a value should be an array with strings %s',
                    json_encode($data, JSON_THROW_ON_ERROR)
                ));
            }

            foreach ($attr['value'] as $value) {
                if (!is_string($value)) {
                    throw new BadRequestHttpException(sprintf(
                        'Could not parse the attributes because if a value is an array it should consist of strings %s',
                        json_encode($data, JSON_THROW_ON_ERROR)
                    ));
                }
            }

            $result[$attr['name']] = $attr['value'];
        }

        return $result;
    }
}
