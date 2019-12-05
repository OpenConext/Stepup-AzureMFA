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

final class MockConfiguration
{
    private $identityProviderEntityId;
    private $serviceProviderEntityId;
    private $publicKeyCertData;
    private $privateKeyPem;

    public function __construct(
        string $identityProviderEntityId,
        string $serviceProviderEntityId,
        string $privateKeyPath,
        string $publicCertPath
    ) {

        if (!is_file($privateKeyPath)) {
            throw new \InvalidArgumentException('Unable to find private key: '. $privateKeyPath);
        }
        if (!is_file($publicCertPath)) {
            throw new \InvalidArgumentException('Unable to find private key: '. $publicCertPath);
        }

        $this->identityProviderEntityId = $identityProviderEntityId;
        $this->serviceProviderEntityId = $serviceProviderEntityId;
        $this->privateKeyPem = file_get_contents($privateKeyPath);
        $this->publicKeyCertData = file_get_contents($publicCertPath);
    }

    /**
     * @return string
     */
    public function getIdentityProviderEntityId()
    {
        return $this->identityProviderEntityId;
    }

    /**
     * @return string
     */
    public function getIdentityProviderPublicKeyCertData()
    {
        return $this->publicKeyCertData;
    }

    /**
     * @return string
     */
    public function getIdentityProviderGetPrivateKeyPem()
    {
        return $this->privateKeyPem;
    }

    /**
     * @return string
     */
    public function getServiceProviderEntityId()
    {
        return $this->serviceProviderEntityId;
    }

}
