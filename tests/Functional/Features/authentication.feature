Feature: When an user needs to authenticate
  As a service provider
  I need to send an AuthnRequest with a nameID to the Azure MFA GSSP IdP

  Scenario: The user authenticates successfully
    Given I send an authentication request to "https://azuremfa.dev.openconext.local/saml/sso" with NameID "q2b27d-0000|user@dev.openconext.local"
#    And the received AuthNRequest should have the ForceAuthn attribute
    Given the login with Azure MFA succeeds and the following attributes are released:
      | name                                                       | value                     |
      | urn:mace:dir:attribute-def:mail                            | user@dev.openconext.local |
      | http://schemas.microsoft.com/claims/authnmethodsreferences | http://schemas.microsoft.com/claims/multipleauthn |
    Then I should be on "https://azuremfa.dev.openconext.local/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:Success"
    And the SAML Response should contain element "NameID" with value "q2b27d-0000|user@dev.openconext.local"

  Scenario: Authentication fails on the Azure MFA side
    Given I send an authentication request to "https://azuremfa.dev.openconext.local/saml/sso" with NameID "q2b27d-0000|user@dev.openconext.local"
    And the login with Azure MFA gets cancelled
    Then I should be on "https://azuremfa.dev.openconext.local/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"

  Scenario: Authentication fails because of unknown user on the Azure MFA side
    Given I send an authentication request to "https://azuremfa.dev.openconext.local/saml/sso" with NameID "q2b27d-0000|user@dev.openconext.local"
    And the login with Azure MFA fails
    Then I should be on "https://azuremfa.dev.openconext.local/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"
