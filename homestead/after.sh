#!/bin/sh

# If you would like to do some extra provisioning you may
# add any commands you wish to this file and they will
# be run after the Homestead machine is provisioned.
#
# If you have user-specific configurations you would like
# to apply, you may also create user-customizations.sh,
# which will be run after this script.
echo "########\n# Update APT repositories and install development requirements from the apt repos\n######\n"

# Install the plantuml dependency (used to generate UML diagrams in the markdown files
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get -y install plantuml chromium-browser php7.2-gmp

echo "\n########\n# Update PHP settings\n######\n"
# Set timezone
sudo ln -sf /usr/share/zoneinfo/Europe/Amsterdam /etc/localtime

# Use PHP 7.2 by default in our environment
php72
xon

echo "\n########\n# Update environment settings\n######\n"
# Aplly the following changes in the code directory
cd /home/vagrant/code

# On successive logins to the vagrant box, automatically CD to the code directory
echo 'cd /home/vagrant/code' >> ~/.profile

echo "\n########\n# Install Composer dependencies\n######\n"
# Install composer dependencies
COMPOSER_MEMORY_LIMIT=-1 composer install

echo "\n########\n# Install JavaScript dependencies\n######\n"
# Download and install NVM (ability to switch node/npm versions)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.34.0/install.sh | bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion

nvm install 8.9.1

# Install JavaScript dependencies
yarn install

echo "\n########\n# Compile front-end dependencies\n######\n"
# Finally compile the front-end assets using Webpack Encore
yarn encore dev