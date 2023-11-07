Feature: When an user needs switch it's preferred locale
  In order to verify the locale switching works
  As an user
  I test the locale switcher on the user facing pages

  Scenario: The user is presented the page in his preferred language based on stepup-locale cookie NL
    Given I have "nl_NL" set as my stepup-locale cookie value
    And I send a registration request to "https://azuremfa.dev.openconext.local/saml/sso"
    Then I should see "Registratie"

  Scenario: The user is presented the page in his preferred language based on stepup-locale cookie EN
    Given I have "en_GB" set as my stepup-locale cookie value
    And I send a registration request to "https://azuremfa.dev.openconext.local/saml/sso"
    Then I should see "Registration"
