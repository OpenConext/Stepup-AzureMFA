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

namespace Surfnet\AzureMfa\Infrastructure\Twig;

use RuntimeException;
use Surfnet\SamlBundle\Entity\HostedEntities;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class GsspExtension extends AbstractExtension
{
    public function __construct(private readonly HostedEntities $hostedEntities)
    {
    }

    public function getFunctions(): array
    {
        return array(
            new TwigFunction('demoSpUrl', array($this, 'generateDemoSPUrl')),
        );
    }

    public function generateDemoSPUrl(): string
    {
        $idp = $this->hostedEntities->getIdentityProvider();
        if (is_null($idp) || is_null($idp->getEntityId())) {
            throw new RuntimeException('No Hosted IdP was configured, this is required to generate the Demo SP entity id');
        }
        return sprintf(
            'https://pieter.aai.surfnet.nl/simplesamlphp/sp.php?idp=%s',
            urlencode($idp->getEntityId())
        );
    }
}
