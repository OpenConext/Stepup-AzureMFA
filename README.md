Stepup-AzureMFA
===================

[![Run QA tests (static analysis, lint and unit tests)](https://github.com/OpenConext/Stepup-AzureMFA/actions/workflows/test-integration.yml/badge.svg)](https://github.com/OpenConext/Stepup-AzureMFA/actions/workflows/test-integration.yml)
[![Run acceptance tests (Behat)](https://github.com/OpenConext/Stepup-AzureMFA/actions/workflows/test-acceptance.yml/badge.svg)](https://github.com/OpenConext/Stepup-AzureMFA/actions/workflows/test-acceptance.yml)

GSSP for Microsoft Azure MFA (Multi-factor authentication)

Locale user preference
----------------------

The default locale is based on the user agent. When the user switches its locale the selected preference is stored inside a
browser cookie (stepup_locale). The cookie is set on naked domain of the requested domain (for azuremfa.stepup.example.com this is example.com).

Authentication and registration flows
-------------------------------------

The application provides internal (SpBundle) and a remote service provider. Instructions for this are given 
on the homepage of this example project [Homepage](https://azuremfa.dev.openconext.local/).

![flow](docs/flow.png)
<!---
regenerate docs/flow.png with `plantum1 README.md` or with http://www.plantuml.com/plantuml
@startuml docs/flow
actor User
participant "Service provider" as SP
box "Stepup Azure MFA"
participant "GSSP Bundle" as IdP
participant "SecondFactor implementation" as App
end box
User -> SP: Register/Authenticate
SP -> IdP: Send AuthnRequest
activate IdP
IdP -> App: Redirect to SecondFactor endpoint
App -> App: <Your custom SecondFactor implementation>
App -> IdP: Redirect to SSO Return endpoint
IdP -> SP: AuthnRequest response
deactivate IdP
SP -> User: User registered/Authenticated
@enduml
--->

Development environment
======================

The purpose of the development environment is only for running the different test and metric tools.

To get started, first setup the development environment. The development environment is a docker container. That is
controlled via the [OpenConext-devconf](https://github.com/OpenConext/OpenConext-devconf/) project.

Every task described below should be run from that container.

Setting the desired Symfony application environment
===================================================
There are 2 ways you can influence the desired Symfony application environment.

1. Set the `app_env` parameter in `config/openconext/parameters.yaml` to `dev`, `test` or `prod`
2. Override the `app_env` param by providing an environment variable named `APP_ENV`

- The default value for the application environment will be `prod`
- Do not try to use a .env file to override the `app_env` param. That file will not be evaluated by Symfony as we decided not use the DotEnv component.


Requirements
-------------------
- Docker
- OpenConext-devconf

Install
-------------------
**Copy the parameters.yaml**

`$ cp config/openconext/parameters.yaml.dist config/openconext/parameters.yaml`

**Bring up the container in dev-mode**
From you dev-conf installation start the `stepup` dev-env with AzureMFA in dev mode

For example:

```bash
cd stepup
./start-dev-env.sh azuremfa:../../OpenConext-stepup/Stepup-AzureMFA
```

**Building frontend assets:**

`$ yarn encore dev` 

or 

`$ yarn encore prod` 

for production 


If everything goes as planned you can go to:

[https://azuremfa.dev.stepup.local](https://azuremfa.dev.stepup.local/)


Configuring institutions using Azure MFA 
----------

The application can be thought to the Azure MFA GSSP via YAML configuration.

In `config/openconext/institutions.yaml.dist` you will find a sample configuration. This configuration should be copied to
`config/openconext/institutions.yaml` and be configured to fit your use case.

The dist file goes into details about the different configuration options.

Debugging
-------------------
Xdebug is configured when provisioning your development Vagrant box. 
It's configured with auto connect IDE_KEY=phpstorm. 

Tests and metrics
======================

To run all required test you can run the following commands from the dev env:

```bash 
    composer check 
    # To run the behat tests
    composer behat
```

Every part can be run separately. Check "scripts" section of the composer.json file for the different options.

Release instructions
=====================

Please read: https://github.com/OpenConext/Stepup-Deploy/wiki/Release-Management for more information on the release strategy used in Stepup projects.

Other resources
======================

 - [Developer documentation](docs/index.md)
 - [Issue tracker](https://www.pivotaltracker.com/n/projects/1163646)
 - [License](LICENSE)
