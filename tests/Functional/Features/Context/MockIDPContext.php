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

namespace Surfnet\AzureMfa\Test\Features\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;

class MockIDPCOntext implements Context
{
    /**
     * @var MinkContext
     */
    protected $minkContext;

    /**
     * Fetch the required contexts.
     *
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->minkContext = $environment->getContext(MinkContext::class);
    }

    /**
     * @Then /^the login with Azure MFA succeeds and the email addresses "(?P<emailAddresses>(?:[^"]|\\")*)" are released$/
     */
    public function theLoginToAzureMFASucceeds($emailAddresses)
    {
        $data = '[]';

        if ($emailAddresses) {
            $emailAddresses = explode(',', $emailAddresses);
            $jsonMail = json_encode($emailAddresses);
            $data = sprintf('[{"name":"urn:mace:dir:attribute-def:mail","value":%s}]', $jsonMail);
        }

        $this->minkContext->fillField('attributes', $data);

        $this->minkContext->pressButton('success');
        $this->minkContext->pressButton('Post');
    }

    /**
     * @Then /^the login with Azure MFA gets cancelled$/
     */
    public function theLoginToAzureMFAGetsCancelled(){

        $this->minkContext->pressButton('user-cancel');
        $this->minkContext->pressButton('Post');
    }

    /**
     * @Then /^the login with Azure MFA fails$/
     */
    public function theLoginToAzureMFAFails(){

        $this->minkContext->pressButton('unknown');
        $this->minkContext->pressButton('Post');
    }

}
