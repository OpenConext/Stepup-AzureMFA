<?php declare(strict_types=1);

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

namespace Surfnet\AzureMfa\Application\Service;

use Surfnet\GsspBundle\Service\AuthenticationService;

/**
 * ForceAuthn property is only added to outgoing AuthnNRquests that where initiated by the RA. And only when the
 * authentication was from the vetting procedure. This code is considered proof of concept code. Making the AzureMFA
 * GSSP responsible for this check might not be most optimal.
 *
 * See https://www.pivotaltracker.com/story/show/171101458
 */
class AuthenticationHelper implements AuthenticationHelperInterface
{
    /**
     * @var string
     */
    private $regex;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    public function __construct(string $regex, AuthenticationService $authenticationService)
    {
        $this->regex = $regex;
        $this->authenticationService = $authenticationService;
    }

    public function useForceAuthn() : bool
    {
        $issuer = $this->authenticationService->getIssuer();
        if (preg_match($this->regex, $issuer) == false) {
            return false;
        }
        return true;
    }
}
