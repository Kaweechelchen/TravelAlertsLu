# -*- mode: ruby -*-
# vi: set ft=ruby :

VMname = File.basename(Dir.getwd)

Vagrant.configure(2) do |config|

  config.vm.box = "ubuntu/trusty64"
  config.vm.hostname = "#{VMname}"

  config.vm.network "forwarded_port", guest: 8080, host: 8080

  config.vm.synced_folder "./", "/opt/tfl-travelalerts"

  config.vm.provider "virtualbox" do |vb|
      vb.name = "#{VMname}"
      vb.memory = 1024
      vb.cpus = 2
  end

  config.vm.define "#{VMname}" do |vb|
  end

  # Setting locale
  ENV["LC_ALL"] = "en_GB.UTF-8"

  config.vm.provision "shell", privileged: false, inline: <<-SHELL
      sudo apt-get update
      sudo locale-gen en_GB.UTF-8
      curl https://raw.githubusercontent.com/creationix/nvm/v0.31.1/install.sh | bash
      source ~/.nvm/nvm.sh
      nvm install 4.4.5
      nvm use 4.4.5
      sudo ln -s ~/.nvm/versions/node/v4.4.5/bin/node /usr/local/bin/node
      sudo ln -s ~/.nvm/versions/node/v4.4.5/bin/npm /usr/local/bin/npm
      cd /opt/tfl-travelalerts && npm install
  SHELL
end
