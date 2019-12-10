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

namespace Surfnet\AzureMfa\Application\Factory;

use Surfnet\AzureMfa\Domain\Institution\ValueObject\Destination;
use Surfnet\SamlBundle\Entity\IdentityProvider;

interface IdentityProviderFactoryInterface
{
    /**
     * Create an IdP instance from the SAML bundle, that is set with the
     *  destination that is passed in the $destination parameter.
     */
    public function build(Destination $destination): IdentityProvider;
}
