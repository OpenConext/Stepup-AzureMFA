Feature: When an user needs to register for a new token
  To register an user for a new token
  As a service provider
  I need to send an AuthnRequest to the identity provider

  Scenario: When a user is registering a new token
    Given I send a registration request to "https://azure-mfa.stepup.example.com/saml/sso"
    Then I should see "Registration"
    And I fill in "email_address_emailAddress" with "test-user@institution-a.example.com"
    When I press "Submit"
    Given the login with Azure MFA succeeds and the following attributes are released:
      | name                                                       | value                               |
      | urn:mace:dir:attribute-def:mail                            | test-user@institution-a.example.com |
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:Success"
    And the SAML Response should contain element "NameID" with value containing "test-user@institution-a.example.com"

  Scenario: When a user is registering a new token, and if an unknown mail address gets released authentication at Azure MFA fails
    Given I send a registration request to "https://azure-mfa.stepup.example.com/saml/sso"
    Then I should see "Registration"
    And I fill in "email_address_emailAddress" with "test-user@institution-a.example.com"
    When I press "Submit"
    Given the login with Azure MFA succeeds and the following attributes are released:
      | name                                                       | value                             |
      | urn:mace:dir:attribute-def:mail                            | unknown@institution-a.example.com |
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"

  Scenario: When a user is registering a new token, and if no mail attribute gets released authentication at Azure MFA fails
    Given I send a registration request to "https://azure-mfa.stepup.example.com/saml/sso"
    Then I should see "Registration"
    And I fill in "email_address_emailAddress" with "test-user@institution-a.example.com"
    When I press "Submit"
    Given the login with Azure MFA succeeds and the following attributes are released:
      | name                                                       | value                             |
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"

  Scenario: When a user is registering a new token, authentication at Azure MFA fails
    Given I send a registration request to "https://azure-mfa.stepup.example.com/saml/sso"
    Then I should see "Registration"
    And I fill in "email_address_emailAddress with "test-user@institution-a.example.com"
    When I press "Submit"
    And the login with Azure MFA gets cancelled
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"

  Scenario: When a user is registering a new token, authentication at Azure MFA fails
    Given I send a registration request to "https://azure-mfa.stepup.example.com/saml/sso"
    Then I should see "Registration"
    And I fill in "email_address_emailAddress" with "test-user@institution-a.example.com"
    When I press "Submit"
    Given the login with Azure MFA fails
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"

  Scenario: Registration fails when an invalid email address is provided by the user
    Given I send a registration request to "https://azure-mfa.stepup.example.com/saml/sso"
    # Fill an email address that does not match any of the configured email domains
    Then I should see "Registration"
    And I fill in "email_address_emailAddress" with "test-user@institution-xample.com"
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
