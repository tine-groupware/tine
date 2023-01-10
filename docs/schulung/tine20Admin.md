tine Admin docs: Admin
=================

Version: Pino 2022.11

HowTo: set passwords for users
=================

1) create a userlist.csv file like this:
~~~
user1
user2
user3
user4
~~~
2) run setPasswords

~~~
method=Admin.setPasswords [-d] [-v] userlist.csv [-- pw=password sendmail=1 pwlist=pws.csv updateaccount=1]
~~~

options:

- sendmail=1 -> sends mail to user with pw
- pwlist=pws.csv -> creates csv file with the users and their new pws
- updateaccount=1 -> also updates user-accounts (for example to create user email accounts)
