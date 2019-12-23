Feature: When an user needs to register for a new token
  To register an user for a new token
  As a service provider
  I need to send an AuthnRequest to the identity provider

  Scenario: When a user is registering a new token
    Given I send a registration request request to "https://azure-mfa.stepup.example.com/saml/sso"
    Then I should see "Registration"
    And I fill in "Email address" with "test-user@institution-a.example.com"
    When I press "Submit"
    And I press "Submit-success"
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:Success"
    And the SAML Response should contain element "NameID" with value containing "test-user@institution-a.example.com"

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

  Scenario: When a user is registering a new token, authentication at Azure MFA fails
    Given I send a registration request request to "https://azure-mfa.stepup.example.com/saml/sso"
    Then I should see "Registration"
    And I fill in "Email address" with "test-user@institution-a.example.com"
    When I press "Submit"
    And I press "Submit-user-cancelled"
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"

  Scenario: When a user is registering a new token, authentication at Azure MFA fails
    Given I send a registration request request to "https://azure-mfa.stepup.example.com/saml/sso"
    Then I should see "Registration"
    And I fill in "Email address" with "test-user@institution-a.example.com"
    When I press "Submit"
    And I press "Submit-unknown"
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"

  Scenario: Registration fails when an invalid email address is provided by the user
    Given I send a registration request request to "https://azure-mfa.stepup.example.com/saml/sso"
    # Fill an email address that does not match any of the configured email domains
    Then I should see "Registration"
    And I fill in "Email address" with "test-user@institution-xample.com"
    When I press "Submit"
    When I press "Submit"
    Then I should be on "https://azure-mfa.stepup.example.com/registration"
    And I should see "The provided email address did not match any of our configured email domains."

  Scenario: When the user is redirected from an unknown service provider he should see an error page
    Given a normal SAML 2.0 AuthnRequest form a unknown service provider
    Then the response status code should be 500
    And I should see "Something went wrong. Please try again"

  Scenario: When an user request the sso endpoint without AuthnRequest the request should be denied
    When I am on "/saml/sso"
    Then the response status code should be 500
    And I should see "Something went wrong. Please try again"
