@sp
Feature: When an user needs to register for a new token
  To register an user for a new token
  As a service provider
  I need to send an AuthnRequest to the identity provider

  Scenario: When an user needs to register for a new token
    # The user request a registration from the service provider
    Given I am on "https://azuremfa.dev.openconext.local/demo/sp"
    Then I should see "Demo service provider"
    When I press "Register user"

    # The user register himself at the IdP
    Then I should be on "https://azuremfa.dev.openconext.local/registration"
    And I should see "Registration"

    # GSSP assigns a subject name id to the user
    Given I fill in "email_address_emailAddress" with "user@dev.openconext.local"
    When I press "email_address_submit"

    # The MFA SSO page
    Then I should be on "https://azuremfa.dev.openconext.local/mock/sso"
    Given the login with Azure MFA succeeds and the following attributes are released:
      | name                                                       | value                     |
      | urn:mace:dir:attribute-def:mail                            | user@dev.openconext.local |
      | http://schemas.microsoft.com/claims/authnmethodsreferences | http://schemas.microsoft.com/claims/multipleauthn |

    # The GSSP acs page.
    Then I should be on "https://azuremfa.dev.openconext.local/saml/sso_return"
    And I press "Submit"

    # Back at the SP.
    Then I should be on "https://azuremfa.dev.openconext.local/demo/sp/acs"
    And I should see "Demo Service provider ConsumerAssertionService endpoint"
    And I should see "urn:oasis:names:tc:SAML:2.0:status:Succes"
    And I should see a NameID with email address "user@dev.openconext.local"
