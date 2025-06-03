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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the cache directory exists
        $cacheDir = __DIR__ . '/../../../var/cache/test/federation-metadata';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
    }

    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'https://azuremfa.dev.openconext.local');
        $this->assertEquals(200, $client->getInternalResponse()->getStatusCode());
        $this->assertStringContainsString('Welcome to the Azure MFA', $crawler->filter('h2')->text());
    }
}
