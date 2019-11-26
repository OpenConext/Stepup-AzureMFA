#!/bin/sh

# If you would like to do some extra provisioning you may
# add any commands you wish to this file and they will
# be run after the Homestead machine is provisioned.
#
# If you have user-specific configurations you would like
# to apply, you may also create user-customizations.sh,
# which will be run after this script.

sudo apt update
DEBIAN_FRONTEND=noninteractive sudo apt upgrade -y
DEBIAN_FRONTEND=noninteractive sudo apt install plantuml -y

cd /home/vagrant/code

sudo cp /home/vagrant/code/homestead/php.ini /etc/php/7.2/mods-available/custom.ini
sudo phpenmod -v 7.2 custom
COMPOSER_MEMORY_LIMIT=-1 composer install
xon
echo 'cd /home/vagrant/code' >> ~/.profile
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.34.0/install.sh | bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion

nvm install 8.9.1
