# Tine Admin HowTo: create admin user

Create admin user via CLI

## Usage

Just call the function, it will ask, if you want to create a new admin user or only reset the password:

~~~
php setup.php --create_admin
~~~

The function makes sure that the user is part of the relevant admin role and group.
