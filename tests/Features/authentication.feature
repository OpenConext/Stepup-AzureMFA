Feature: When an user needs to authenticate
  As a service provider
  I need to send an AuthnRequest with a nameID to the identity provider

  @remote
  Scenario: When an user needs to register for a new token
    Given I am on "https://pieter.aai.surfnet.nl/simplesamlphp/sp.php?sp=default-sp"
    And I select "https://azure-mfa.stepup.example.com/saml/metadata" from "idp"
    And I fill in "subject" with "test-name-id-1234"
    When I press "Login"
    Then I should see "Authenticate"
    And I should be on "https://gssp.stepup.example.com/authentication"

    When I press "Authenticate user"
    Then I press "Submit"
    And I should see "You are logged in to SP:default-sp"
    And I should see "IdP EnitytID:https://azure-mfa.stepup.example.com/saml/metadata"
    And I should see "test-name-id-1234"
