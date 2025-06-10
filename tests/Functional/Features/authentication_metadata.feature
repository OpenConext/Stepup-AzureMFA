Feature: When an user needs to authenticate
  As a service provider
  I need to send an AuthnRequest with a nameID to the Azure MFA GSSP IdP

  Scenario: The user authenticates successfully if IdP is not cached
    Given I have no cached identity provider for "institution-c.example.com"
      And I send an authentication request to "https://azuremfa.dev.openconext.local/saml/sso" with NameID "q2b27d-0000|user@institution-c.example.com"
      And the login with Azure MFA succeeds and the following attributes are released:
      | name                                                       | value                     |
      | urn:mace:dir:attribute-def:mail                            | user@institution-c.example.com |
      | http://schemas.microsoft.com/claims/authnmethodsreferences | http://schemas.microsoft.com/claims/multipleauthn |
    Then I should be on "https://azuremfa.dev.openconext.local/saml/sso_return"
      And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:Success"
      And the SAML Response should contain element "NameID" with value "q2b27d-0000|user@institution-c.example.com"

  Scenario: Authentication fails on the Azure MFA side
    Given I have no cached identity provider for "institution-c.example.com"
      And I send an authentication request to "https://azuremfa.dev.openconext.local/saml/sso" with NameID "q2b27d-0000|user@institution-c.example.com"
      And the login with Azure MFA gets cancelled
    Then I should be on "https://azuremfa.dev.openconext.local/saml/sso_return"
      And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"

  Scenario: Authentication fails because of unknown user on the Azure MFA side
    Given I have no cached identity provider for "institution-c.example.com"
      And I send an authentication request to "https://azuremfa.dev.openconext.local/saml/sso" with NameID "q2b27d-0000|user@institution-c.example.com"
      And the login with Azure MFA fails
    Then I should be on "https://azuremfa.dev.openconext.local/saml/sso_return"
      And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"

  Scenario: The user authenticates successfully when the cached IdP cert is invalid
    Given I have an invalid cached identity provider for "institution-c.example.com"
      And I send an authentication request to "https://azuremfa.dev.openconext.local/saml/sso" with NameID "q2b27d-0000|user@institution-c.example.com"
      And the login with Azure MFA succeeds and the following attributes are released:
        | name                                                       | value                     |
        | urn:mace:dir:attribute-def:mail                            | user@institution-c.example.com |
        | http://schemas.microsoft.com/claims/authnmethodsreferences | http://schemas.microsoft.com/claims/multipleauthn |
      Then I should be on "https://azuremfa.dev.openconext.local/saml/sso_return"
        And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:Success"
        And the SAML Response should contain element "NameID" with value "q2b27d-0000|user@institution-c.example.com"