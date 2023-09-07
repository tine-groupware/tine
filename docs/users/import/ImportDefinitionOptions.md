Import Definitions
=================

Version: Ellie 2023.11

Import definitions are configured in xml format. Example:

``` xml title="tasks_import_csv"
--8<-- "tine20/Tasks/Import/definitions/tasks_import_csv.xml"
```

Import Alarms
=================

~~~ xml
<field>
    <source>alarm_minutes_before</source>
    <destination>alarm_minutes_before</destination>
</field>
~~~

=> creates an alarm with X minutes before.


Import DateTime fields
=================

tine imports support the php strtotime() (see https://www.php.net/manual/de/function.strtotime.php) formats:

~~~ php 
echo strtotime("now"), "\n";
echo strtotime("10 September 2000"), "\n";
echo strtotime("+1 day"), "\n";
echo strtotime("+1 week"), "\n";
echo strtotime("+1 week 2 days 4 hours 2 seconds"), "\n";
echo strtotime("next Thursday"), "\n";
echo strtotime("last Monday"), "\n";
~~~

It's also possible to define a special date/time format pattern:

~~~ xml
<field>
    <source>Anschaffung</source>
    <destination>added_date</destination>
    <typecast>datetime</typecast>
    <datetime_pattern>!Y-m-d</datetime_pattern>
</field>
~~~
