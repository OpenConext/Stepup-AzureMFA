Feature: When an user needs switch it's preferred locale
  In order to verify the locale switching works
  As an user
  I test the locale switcher on the user facing pages

  Scenario: The user is presented the page in his preferred language based on stepup-locale cookie NL
    Given I have "nl" set as my stepup-locale cookie value
    And I send a registration request request to "https://azure-mfa.stepup.example.com/saml/sso"
    Then I should see "Registratie"

  Scenario: The user is presented the page in his preferred language based on stepup-locale cookie EN
    Given I have "en" set as my stepup-locale cookie value
    And I send a registration request request to "https://azure-mfa.stepup.example.com/saml/sso"
    Then I should see "Registration"

  Scenario: When a user is registering a new token and switches back and forth from English to Dutch language during registration
    Given I send a registration request request to "https://azure-mfa.stepup.example.com/saml/sso"
    Then I should see "Registration"
    And I follow "NL"
    Then I should see "Registratie"
    And I follow "EN"
    Then I should see "Registration"
    And I fill in "Email address" with "test-user@institution-a.example.com"
    When I press "Submit"
    And I press "Submit-success"
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:Success"
    And the SAML Response should contain element "NameID" with value containing "test-user@institution-a.example.com"