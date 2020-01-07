Stepup-Azure-MFA
===================

<a href="#">
    <img src="https://travis-ci.org/OpenConext/Stepup-Azure-MFA.svg?branch=develop" alt="build:">
</a></br>

GSSP for Microsoft Azure MFA (Multi-factor authentication)

Locale user preference
----------------------

The default locale is based on the user agent. When the user switches its locale the selected preference is stored inside a
browser cookie (stepup_locale). The cookie is set on naked domain of the requested domain (for azure-mfa.stepup.example.com this is example.com).

Authentication and registration flows
-------------------------------------

The application provides internal (SpBundle) and a remote service provider. Instructions for this are given 
on the homepage of this example project [Homepage](https://azure-mfa.stepup.example.com/app_dev.php/).

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

To get started, first setup the development environment. The dev. env. is a virtual machine. Every task described is run
from that machine.  

Requirements
-------------------
- vagrant 2.2.x
    - vagrant-hostsupdater (1.1.1.160, global)
    - vagrant-vbguest (0.19.0, global)
- Virtualbox
- Composer

Other requirements, installed by `homestead/after.sh`

- plantuml *(dev only) For generating plantuml diagrams in readme/markdown files*
- chromium-browser *(dev only) For running Panther web tests*
- php7.2-gmp *Dependency of the Stepup-bundle*

Install
-------------------
**Create a .env file**

1. `$ cp .env.ci .env`
1. Edit the `.env` file with the editor of your choice and: 
    1. Update the `APP_SECRET` to a value of your liking. See [Symfony docs](https://symfony.com/doc/current/reference/configuration/framework.html#secret) for more details about this secret. 
    1. Set the `APP_ENV` to 'dev'

**Copy the parameters.yaml**

`$ cp config/packages/parameters.yaml.dist config/packages/parameters.yaml`

**Bring up Homestead**

```
$ cd homestead
$ composer install
$ cd ..
$ vagrant up
```

If everything goes as intended, you can develop in the virtual machine.

**Upgrade Homestead regularly**

Issues described in the previous section can be prevented if the base box is up to date.

For detailed instructions, please visit the excellent Homestead [documentation pages](https://laravel.com/docs/5.8/homestead#updating-homestead).

**Building frontend assets:**

`$ yarn encore dev` 

or 

`$ yarn encore prod` 

for production 


If everything goes as planned you can go to:

[https://azure-mfa.stepup.example.com](https://azure-mfa.stepup.example.com/app_dev.php)


Configuring institutions using Azure MFA 
----------

The application can be thought to the Azure MFA GSSP via YAML configuration.

In `config/packages/institutions.yaml.dist` you will find a sample configuration. This configuration should be copied to
`config/packages/institutions.yaml` and be configured to fit your use case.

The dist file goes into details about the different configuration options.

Debugging
-------------------
Xdebug is configured when provisioning your development Vagrant box. 
It's configured with auto connect IDE_KEY=phpstorm. 

Tests and metrics
======================

To run all required test you can run the following commands from the dev env:

```bash 
    composer test 
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
