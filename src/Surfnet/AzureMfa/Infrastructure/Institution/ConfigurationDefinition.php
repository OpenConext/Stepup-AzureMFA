<?php

declare(strict_types = 1);

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

namespace Surfnet\AzureMfa\Infrastructure\Institution;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigurationDefinition implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('institution_configuration');

        $this->addInstitutionDefinitions($treeBuilder->getRootNode());

        return $treeBuilder;
    }

    private function addInstitutionDefinitions(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('institutions')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('entity_id')->end()
                            ->scalarNode('sso_location')->end()
                            ->arrayNode('certificates')
                                ->scalarPrototype()->end()
                            ->end()
                            ->scalarNode('metadata_url')->end()
                            ->arrayNode('email_domains')
                                ->isRequired()
                                ->scalarPrototype()->end()
                            ->end()
                            ->booleanNode('is_azure_ad')
                                ->defaultFalse()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
