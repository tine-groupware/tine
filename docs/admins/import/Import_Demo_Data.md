Tine Admin HowTo: Import Demo Data
=================

Version: Pino 2022.11

## Import of Demo Data from a set

The following command creates the demo data defined by the file Admin.yml:

~~~
php tine20.php --method=Admin.createDemoData --username=admin --password=xyz -- demodata=set set=Admin.yml
~~~

``` yml title="Admin.yml"
--8<-- "tine20/Admin/Setup/DemoData/Admin.yml"
```
