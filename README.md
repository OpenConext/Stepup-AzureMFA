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
From your OpenConext-devconf checkout, start the `stepup` dev-env with your local AzureMFA code mounted in the
`azuremfa` container (in dev mode).

For example, when OpenConext-devconf and Stepup-AzureMFA are checked out in the same directory:

```bash
cd OpenConext-devconf/stepup
./start-dev-env.sh azuremfa:../../Stepup-AzureMFA
```

The path after `azuremfa:` is the path to your Stepup-AzureMFA checkout, relative to the `stepup` directory or absolute.

**Building frontend assets:**

Run `composer frontend-install` and optionally after that: `yarn encore watch` for a development build 


If everything goes as planned you can go to:

[https://azuremfa.dev.openconext.local](https://azuremfa.dev.openconext.local/)


Configuring institutions using Azure MFA 
----------

The institutions that can use the AzureMFA GSSP are configured in `config/institutions.yaml`. For each institution you
configure the Azure MFA IdP (EntraID or ADFS) to send users to, and the email domains of its users.
During registration and authentication, the domain of the user's email address selects the institution.
So it's the domain of the user's email address that selects the EntraID tenant or ADFS IdP that AzureMFA GSSP uses.
The schacHomeOrganization of the user (i.e. how the rest of stepup identifies the user's institution) is NOT used in
the AzureMFA GSSP.

In `config/openconext/institutions.yaml.dist` you will find a sample configuration. This configuration should be copied to
`config/openconext/institutions.yaml` and be configured to fit your use case.

The dist file goes into details about the different configuration options.

Debugging
-------------------
Xdebug is installed and enabled in the `stepup-azuremfa-1` container when it runs with your local code mounted (see
[Bring up the container in dev-mode](#install)). It is configured with:

- `xdebug.mode=debug` and `xdebug.start_with_request=default` (trigger mode): a debug session is only started when
  requested, e.g. by an `XDEBUG_SESSION` cookie (set by an Xdebug browser extension) or by an `XDEBUG_SESSION`
  environment variable for CLI commands
- `xdebug.client_host=host.docker.internal` and `xdebug.client_port=9003`: Xdebug connects to the IDE on your host
- `xdebug.idekey=PHPSTORM`

In your IDE, listen for incoming debug connections on port 9003 and map your local Stepup-AzureMFA directory to
`/var/www/html` in the container. In PhpStorm, configure the path mapping on a server named
`azuremfa.dev.openconext.local` (Settings > PHP > Servers). To debug a CLI command, like a test, start it with the
trigger set and tell PhpStorm which server mapping to use, e.g.:

```bash
docker exec -it -e XDEBUG_SESSION=PHPSTORM -e PHP_IDE_CONFIG=serverName=azuremfa.dev.openconext.local stepup-azuremfa-1 composer behat
```

Tests and metrics
=================

The tests (PHPUnit and Behat) and the QA tools must be run in the `stepup-azuremfa-1` container of the
OpenConext-devconf `stepup` environment, with your local Stepup-AzureMFA code mounted in it. The tests depend on that
container and the configuration for OpenConext-devconf that it brings in:
- The SAML key and certificates are read from `/config/...` (see `config/openconext/parameters.yaml`). In the
  container the `stepup` directory of OpenConext-devconf is mounted on `/config`.
- The tests use the dev/test-only mock Azure MFA IdP of this application (`https://azuremfa.dev.openconext.local/mock/...`),
  so the institutions in `config/openconext/institutions.yaml` must match the ones the tests expect.
  The tests do not use the EntraID mock IdP of the OpenConext-devconf `stepup` environment.

Setting up the container
------------------------

1. From the `stepup` directory of OpenConext-devconf, start the environment with your local AzureMFA code mounted
   in the `azuremfa` container:

   ```bash
   ./start-dev-env.sh azuremfa:~/path/to/Stepup-AzureMFA
   ```

   This runs the container with `APP_ENV=dev` and your code mounted on `/var/www/html`.

2. Install the dependencies and build the frontend assets in the container:

   ```bash
   docker exec -it stepup-azuremfa-1 composer install
   docker exec -it stepup-azuremfa-1 composer frontend-install
   ```
   
   This will install the dependencies and build the frontend assets in the container.
   The `institutions.yaml` and `parameters.yaml` are created from their .dist files if they do not exist.

Running the tests
-----------------

Run the tests from your host using `docker exec`: 

```
docker exec -it stepup-azuremfa-1 bash
```

The test scripts select the Symfony `test` environment themselves, independent of the `APP_ENV` of the container.

```bash
# PHPUnit: all tests, only the unit tests, or only the web tests
docker exec -it stepup-azuremfa-1 composer test
docker exec -it stepup-azuremfa-1 composer unit-tests
docker exec -it stepup-azuremfa-1 composer web-tests

# Behat tests (tests/Functional/Features)
docker exec -it stepup-azuremfa-1 composer behat


# All QA checks (composer validate, docheader, lint, phpcs, phpmd, phpstan, rector, unit and web tests)
docker exec -it stepup-azuremfa-1 composer check
```

To run a single Behat feature, pass its path to the behat script, e.g.
`docker exec -it stepup-azuremfa-1 ./ci/qa/behat tests/Functional/Features/authentication.feature`.

Every part can be run separately. Check "scripts" section of the composer.json file for the different options.

Release instructions
=====================

Please read: https://github.com/OpenConext/Stepup-Deploy/wiki/Release-Management for more information on the release strategy used in Stepup projects.

Other resources
======================

 - [Developer documentation](docs/index.md)
 - [Issue tracker](https://www.pivotaltracker.com/n/projects/1163646)
 - [License](LICENSE)
