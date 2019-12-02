Feature: When an user needs to register for a new token
  To register an user for a new token
  As a service provider
  I need to send an AuthnRequest to the identity provider

  Scenario: When a user is registering a new token
    Given I send a registration request request to "https://azure-mfa.stepup.example.com/saml/sso"
    And I fill in "Subject NameID" with "test-user@institution-a.example.com"
    Then I should see "Registration"
    When I press "Register user"
    And I press "Submit"
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"

  Scenario: Registration fails when an invalid email address is provided by the user
    Given I send a registration request request to "https://azure-mfa.stepup.example.com/registration"
    # Fill an email address that does not match any of the configured email domains
    And I fill in "Subject NameID" with "test-user@institution-x.example.com"
    Then I should see "Registration"
    When I press "Register user"
    Then I should be on "https://azure-mfa.stepup.example.com/registration"
    And I should see "test-user@institution-x.example.com is not known"

  Scenario: When the user is redirected from an unknown service provider he should see an error page
    Given a normal SAML 2.0 AuthnRequest form a unknown service provider
    Then the response status code should be 500
    And I should see "AuthnRequest received from ServiceProvider with an unknown EntityId: \"https://service_provider_unkown/saml/metadata\""

  Scenario: When an user request the sso endpoint without AuthnRequest the request should be denied
    When I am on "/saml/sso"
    Then the response status code should be 500
    And I should see "Could not receive AuthnRequest from HTTP Request: expected query parameters, none found"
