Installation Guide
===================

This guide describes how to install tine with a MySQL/MariaDB database (including account and authentication backend).

Check the [System Requirements] to find out, which other databases are supported and adapt the configuration to your needs.

[System Requirements]: ./System_Requirements/

This guide covers the installation of tine from tarball packages. For the Docker-based setup, see [DOCKER-QUICKSTART].

[DOCKER-QUICKSTART]: ../docker/DOCKER-QUICKSTART/

Install packages
------

### Download from GitHub or packages.tine20.com

Download the latest version of tine from [GitHub releases] or [packages.tine20.com]
and unzip the archive(s) into your document root (or a subfolder) of your webserver.

[GitHub releases]: https://github.com/tine20/tine20/releases
[packages.tine20.com]: https://packages.tine20.com/maintenance/source/

Webserver Configuration
------

The [NGINX]-Webserver is recommended. Check the tine repository for the recommended configuration: [NGINX config]

[NGINX]: https://www.nginx.com/
[NGINX config]: https://github.com/tine20/tine20/tree/main/etc/nginx

Installation
------

### Base configuration file config.inc.php

tine requires a minimal configuration file, that contains at least information about how to connect to the database and an user account for the setup.

You can copy and adapt the sample file (config.inc.php.dist) or create your own config.inc.php with the following content:


```
<?php
   return array(
     'database' => array(
       'host'        => '{Database hostname}',
       'dbname'      => '{Database name}',
       'username'    => '{Database username}',
       'password'    => '{Database password}',
       'port'        => '3306',
       'adapter'     => 'pdo_mysql',
       'tableprefix' => 'tine20_',
     ),
     'setupuser' => array(
       'username'    => 'tine20setup',
       'password'    => 'setup'
     ),
   );
```

The user you specify here is just for entering the setup. For administrating you will create an initial admin user during the setup process.

### Preparing the database

Connect to your MySQL database server using your favorite client. Create a database with UTF8 charset and connect user with the data you had entered into the 'database' array of your 'config.inc.php':
~~~
mysql> CREATE DATABASE tine20db DEFAULT CHARACTER SET 'UTF8';
mysql> CREATE USER 'tine20user'@'localhost' IDENTIFIED BY 'tine20pw';
mysql> GRANT ALL PRIVILEGES ON tine20db.* TO 'tine20user'@'localhost';
~~~
The first command creates the database 'tine20db'. The second one adds an user 'tine20user' with password 'tine20pw',
and the third grants all privileges on the previously created database to the 'tine20user'. Connections of that user are allowed only from localhost.

### Start the setup GUI

Open your favorite web browser and go to http://your_webserver/path_to_tine/setup.php

Log in with the username/password you filled into the 'setupuser' array of your config.inc.php.


### Terms and conditions

After you have read the license and privacy policy, accept both by checking the two boxes below them and clicking the 'Accept' button.


### Setup Checks

If all requirements are met, you see a green check mark behind them and you can continue.

If one or more checks failed, you have to fix these problems first. See the [System Requirements] and [FAQ] for first point of help and consult your PHP and database server documentation.

> TODO: add [FAQ] link!

### Config Manager

The 'setup authentication' and 'database' related fields should already been filled correctly by the information you had entered in config.inc.php.


Optional:

Logging: Specify a logfile for debugging purposes.
Caching: Folder for tine related caching files.
Temporary files: Folder for temporary files created by tine.
Session files: Folder for tine session files (if not specified, the folder defined in the variable 'session.save_path' of your php.ini is used).

The folders you specify must already exist and be writeable by the webserver user. For security reasons, no other user should have access to them! Also for security reasons this files/folders should never be below your webservers documentroot!


To save your settings, click the 'save config' button. If your config.inc.php is not writeable by your webserver user, you have to download and replace the file of your installation.

Even if you haven't changed anything, you should re-save the file, because the password of your setup user is encrypted and replaced in your configuration file.


### Authentication/Accounts

Authentication provider: This is the system tine authenticate against. Leave the backend at 'SQL' and enter an initial admin login name and password. This account you can use later for administrating your installation. Leave all other fields at their default.

Accounts storage: This is the place where tine saves it's account information. Leave the backend at 'SQL' and change the other values to your needs.


Optional:

Redirect settings: Configure redirecting to a different page then the login screen.


If you have finished filling this dialog, click to 'Save config and install'. This could take a short while. Setup creates the initial database and configuration.


### Email

If you plan to use FeLaMiMail, you have to configure your Imap and Smtp server here. If you need notifcation (e. g. from calendar events), you only need to fill the Stmp section. Otherwise you can leave all untouched here.

If you make any changes, save them by clicking to 'Save config'.


### Application Manager

This menu allows you to install/uninstall different modules of tine. Right-click and choose 'Install application' creates the neccessary requirements in your database and allow users to access this applications.

There are three applications preinstalled (Tinebase, Admin, Addressbook). They are neccessary for tine. Uninstalling will damage your installation!

### Securing your tine installation

The folder containing your tine installation, needs just to be readable by the user account your webserver is executed as.

If you want your config.inc.php to be updated via GUI, then this is the only file that has to be writeable for the webserver account. If you want this file also to be read-only, you can download the changed file during the GUI setup process.



Administrating tine (Optional)
-----

> TODO: move this to Admin docs!

After finishing the setup process, go to the tine login page http://your_webserver/path_to_tine/ and login with the initial admin account you had created. Click to the tab 'tine' and choose the 'Admin' module.


### User

Here you can add/modify/remove/enable/disable users to/of your installation. Every user has to be member of at least one (primary) group.


### Groups

Here you can add/modify/remove groups to/of your installation. Users can be member in any number of groups.


### Roles

Roles allow you to specify privileges based on groups.

Example: If you want to allow/disallow a group to access the calendar, you create roles with the corresponding privileges and add the groups to them.


### Computers

If you manage Samba with your tine installation, you can add machine accounts here.


### Applications

Allows you to (temporary) enable/disable installed applications and configure them (if settings are available) by right-clicking.


### Access Log

tine Logfile (inside the database).

How to purge old entries, see the [FAQ].

### Shared Tags

Here you can create tags that are available for all users in specified/all applications.

### Importing

> TODO add User Import HowTo to docs

If you plan to import users from a CSV-File, see the [User Import Howto].
