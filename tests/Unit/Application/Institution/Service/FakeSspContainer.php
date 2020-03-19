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

namespace Surfnet\AzureMfa\Test\Unit\Application\Institution\Service;

use SAML2\Compat\AbstractContainer;

class FakeSspContainer extends AbstractContainer
{

    /**
     * @inheritDoc
     */
    public function getLogger()
    {
        // TODO: Implement getLogger() method.
    }

    /**
     * @inheritDoc
     */
    public function generateId()
    {
        return 'abcdefghijklmnopqrstuvwxyz';
    }

    /**
     * @inheritDoc
     */
    public function debugMessage($message, $type)
    {
        // TODO: Implement debugMessage() method.
    }

    /**
     * @inheritDoc
     */
    public function redirect($url, $data = [])
    {
        // TODO: Implement redirect() method.
    }

    /**
     * @inheritDoc
     */
    public function postRedirect($url, $data = [])
    {
        // TODO: Implement postRedirect() method.
    }
}