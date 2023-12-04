Un-delete users
=

This can be done via SQL:

~~~sql
update tine20_accounts set is_deleted=0, deleted_time='1970-01-01 00:00:00' where email like 'deleteduser@tine.net';
~~~
