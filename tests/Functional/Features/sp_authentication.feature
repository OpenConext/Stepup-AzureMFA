@sp
Feature: When an user needs to authenticate
  As a service provider
  I need to send an AuthnRequest with a nameID to the identity provider

  Scenario: When an user needs to register for a new token

    # The user clicks on authenticate button from the SP
    And I am on "https://azure-mfa.stepup.example.com/demo/sp"
    Then I should see "Demo service provider"
    And I fill in "Subject NameID" with "q2b27d-0000|user@stepup.example.com"
    Given I press "Authenticate user"

    # The mock MFA client
    Then I should be on "https://azure-mfa.stepup.example.com/mock/sso"
    Given the login with Azure MFA succeeds and the email addresses "user@stepup.example.com" are released

    # The MFA acs page.
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And I press "Submit"

    # Back at the SP.
    Then I should be on "https://azure-mfa.stepup.example.com/demo/sp/acs"
    And I should see "Demo Service provider ConsumerAssertionService endpoint"
    And I should see "q2b27d-0000|user@stepup.example.com"
