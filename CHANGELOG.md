# Next
- Add favicon
- Update dependencies

# 1.4.1
- Disable unused fragments
- Update dependencies
- Add X-UA-Compatible header fix issues with embedded browsers

# 1.4.0
 - Use email address from GSSP saml extension, skip asking the user for his emailaddress

# 1.3.3
 - Fix client-side email validation
 - Add monitoring endpoints /health and /info
 - Update dependencies
 - Update webpack-notifier and node-notifier

# 1.3.2
  - Case insensitive email check

# 1.3.1
 - Add placeholder to email registration
 - Set focus on email-input in registration flow

# 1.3.0
 - Update dependencies
 - Use bootstrap theme

# 1.2.1
 - Add support for component_info in deploy
 - Security updates in dependencies
 
# 1.2.0
 - Support direct connection to AzureAD #33
 
# 1.1.3
 - Do validation on emailaddress and not NameId when saml response is received from Azure MFA. #31
 
 # 1.1.1
 - ForceAuthn feature introduced in #21 was changed to peek into the scoping requester ids for retrieving the issuing SP. #24
 - Installed JS security upgrades #25 

# 1.1.0
 - Session cookies are setup with SameSite=None #22
 - JS dependencies have been updated #23

# 1.0.2
 - Enforce authentication on RA authenications #21

# 1.0.1
 - Improved logging (JSON formatted logging to syslog for prod environment)
 - Addressed a translation & default language issue
 - Security update (node-sass)

# 1.0.0
First release to master

# 0.2.8
# 0.2.7
More small improvements to make the codebase more easy maintainable.

# 0.2.6
Improved error reporting (by utilising Stepup-bundles error reporting feature)

# 0.2.5
Various changes to support Stepup-Build

# 0.2.4
Add composer archive excludes to the composer.json

# 0.2.3
Make sure we can install composer dependencies in prod mode

# 0.2.2
Add the surfnet_saml.yaml configuration to the prod package configuration

# 0.2.1
Improved the test coverage of the locale related features

# 0.2.0
The authentication logic was updated and covered by additional test coverage. 
Several example logic still present in the project was removed.

# 0.1.0
This release supports registrations, authentications should work to, but this has not been verified and tested in detail.

# 0.0.5
Introduce the institutions.yaml configuration and parse it into a value object structure.

# 0.0.4
Mainly changes to get the build to pass

# 0.0.3
Security update for js-yaml

# 0.0.2
Add Azure MFA specific files to the project.

# 0.0.1  
Initial release
