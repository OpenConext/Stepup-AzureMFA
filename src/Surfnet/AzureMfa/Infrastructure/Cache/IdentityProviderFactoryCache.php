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

namespace Surfnet\AzureMfa\Infrastructure\Cache;

use Surfnet\AzureMfa\Domain\Institution\Factory\IdentityProviderFactoryInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\IdentityProviderInterface;
use Surfnet\AzureMfa\Domain\Institution\ValueObject\InstitutionName;
use Surfnet\AzureMfa\Infrastructure\Factory\IdentityProviderFactory;

class IdentityProviderFactoryCache implements IdentityProviderFactoryInterface
{

    private IdentityProviderFactory $providerFactory;
    private IdentityProviderCache $identityProviderCache;

    public function __construct(IdentityProviderFactory $providerFactory, IdentityProviderCache $identityProviderCache)
    {
        $this->providerFactory = $providerFactory;
        $this->identityProviderCache = $identityProviderCache;
    }

    public function build(
        InstitutionName $institutionName,
    ): IdentityProviderInterface {

        $identityProvider = $this->identityProviderCache->get($institutionName);
        if ($identityProvider !== null) {
            return $identityProvider;
        }

        $identityProvider = $this->providerFactory->build($institutionName);

        $this->identityProviderCache->set($institutionName, $identityProvider);

        return $identityProvider;
    }

    public function rebuild(
        InstitutionName $institutionName,
    ): IdentityProviderInterface {
        $identityProvider = $this->providerFactory->build($institutionName);

        $this->identityProviderCache->set($institutionName, $identityProvider);

        return $identityProvider;
    }
}
