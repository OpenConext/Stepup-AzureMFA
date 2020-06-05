Feature: When an user needs to authenticate
  As a service provider
  I need to send an AuthnRequest with a nameID to the Azure MFA GSSP IdP

  Scenario: The user authenticates successfully
    Given I send an authentication request to "https://azure-mfa.stepup.example.com/saml/sso" with NameID "q2b27d-0000|user@stepup.example.com"
    And the received AuthNRequest should have the ForceAuthn attribute
    Given the login with Azure MFA succeeds and the following attributes are released:
      | name                                                       | value                   |
      | urn:mace:dir:attribute-def:mail                            | user@stepup.example.com |
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:Success"
    And the SAML Response should contain element "NameID" with value "q2b27d-0000|user@stepup.example.com"

  Scenario: Authentication fails on the Azure MFA side
    Given I send an authentication request to "https://azure-mfa.stepup.example.com/saml/sso" with NameID "q2b27d-0000|user@stepup.example.com"
    And the login with Azure MFA gets cancelled
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"

  Scenario: Authentication fails because of unknown user on the Azure MFA side
    Given I send an authentication request to "https://azure-mfa.stepup.example.com/saml/sso" with NameID "q2b27d-0000|user@stepup.example.com"
    And the login with Azure MFA fails
    Then I should be on "https://azure-mfa.stepup.example.com/saml/sso_return"
    And the SAML Response should contain element "StatusCode" with attribute "Value" with attribute value "urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"
