### Windows
#### PHP web-server (pour phpMyAdmin)

php -S localhost:8999 -t d:/phpWebServer

#### phpMyAdmin

http://localhost:8999/phpMyAdmin/index.php

### php.ini sous Linux

Localisé à deux endroits: /etc/php/8.3/
- cli/php.ini
- apache2/php.ini

### Install de msgpack et xdebug

    sudo apt install php-pear
    sudo apt install php-dev
    sudo pecl install msgpack

    sudo pecl install xdebug

Dans php.ini

    extension=mysqli

    extension=msgpack.so
	
    zend_extension=xdebug

    [xdebug]
    xdebug.mode=debug
    xdebug.start_with_request=yes
    xdebug.client_host=127.0.0.1
    xdebug.client_port=9003
    xdebug.log=/var/log/xdebug.log

### Debug PHP dans Vscode

launch.json

    {
      // Use IntelliSense to learn about possible attributes.
      // Hover to view descriptions of existing attributes.
      // For more information, visit: https://go.microsoft.com/fwlink/?linkid=830387
      "version": "0.2.0",
      "configurations": [
        {
          "name": "Listen for Xdebug",
          "type": "php",
          "request": "launch",
          "port": 9003
        },
        {
          "name": "Launch currently open script",
          "type": "php",
          "request": "launch",
          "program": "${file}",
          "cwd": "${fileDirname}",
          "port": 0,
          "runtimeArgs": [
            "-dxdebug.start_with_request=yes"
          ],
          "env": {
            "XDEBUG_MODE": "debug,develop",
            "XDEBUG_CONFIG": "client_port=${port}"
          }
        },
        {
          "name": "Launch Built-in web server",
          "type": "php",
          "request": "launch",
          "runtimeArgs": [
            "-dxdebug.mode=debug",
            "-dxdebug.start_with_request=yes",
            "-S",
            "localhost:8888"
          ],
          "program": "",
          "cwd": "${workspaceRoot}",
          "port": 9003,
          "serverReadyAction": {
            "pattern": "Development Server \\(http://localhost:([0-9]+)\\) started",
            "uriFormat": "http://localhost:%s",
            "action": "openExternally"
          }
        }
      ]
    }

Il faut que le cli/php.ini soit bien configuré.


### log
/var/log/xdebug.log

    sudo chown danie:daniel xdebug.log

### Install apache2 mysql php

sudo apt install apache2 php libapache2-mod-php mysql-server php-mysql

http://localhost >>> it works !

apache2 conf: /etc/apache2/apache2.conf
mysql htm: /var/www/html

### Lancements automatiques
sudo systemctl disable apache2
sudo systemctl disable mysql

sudo systemctl enable apache2
sudo systemctl enable mysql

sudo systemctl start apache2
sudo systemctl start mysql

sudo systemctl stop apache2
sudo systemctl stop mysql

Logs: /var/log/appache2/...

### Création du user daniel sur mysql
sudo mysql
>
CREATE USER 'daniel'@'localhost' IDENTIFIED BY 'Ds3542secret';
GRANT ALL ON *.* TO 'daniel'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
QUIT;

quit;

### Changement password de daniel
mysql -udaniel -pDs3542secret -h localhost mysafe
>
FLUSH PRIVILEGES;
ALTER USER 'daniel'@'localhost' IDENTIFIED BY 'Ds3542mysql';
FLUSH PRIVILEGES;
quit;

### phpmyadmin
http://localhost/phpmyadmin
