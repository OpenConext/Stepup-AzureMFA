@sp
Feature: When an user needs to authenticate
  As a service provider with only a email address
  I need to send an AuthnRequest with a nameID without the identifier to the identity provider

  Scenario: Whe an user needs to authenticate a token without registration

    # The user clicks on authenticate button from the SP
    And I am on "https://azuremfa.dev.openconext.local/demo/sp"
    Then I should see "Demo service provider"
    And I fill in "Subject NameID" with "user@dev.openconext.local"
    Given I press "Authenticate user"

    # The mock MFA client
    Then I should be on "https://azuremfa.dev.openconext.local/mock/sso"
    Given the login with Azure MFA succeeds and the following attributes are released:
      | name                                                       | value                     |
      | urn:mace:dir:attribute-def:mail                            | user@dev.openconext.local |
      | http://schemas.microsoft.com/claims/authnmethodsreferences | http://schemas.microsoft.com/claims/multipleauthn |

    # The MFA acs page.
    Then I should be on "https://azuremfa.dev.openconext.local/saml/sso_return"
    And I press "Submit"

    # Back at the SP.
    Then I should be on "https://azuremfa.dev.openconext.local/demo/sp/acs"
    And I should see "Demo Service provider ConsumerAssertionService endpoint"
    And I should see "user@dev.openconext.local"
