# Crm_Frontend_Cli  

Cli frontend for Crm

This class handles cli requests for the Crm  



## Extend:

Tinebase_Frontend_Cli_Abstract

## Methods

| Name | Description |
|------|-------------|
|[migrateProjectsToLeads](#crm_frontend_climigrateprojectstoleads)|usage: tine20-cli --method=Crm.migrateProjectsToLeads [-d] [-v] -- container_id=abcde124345 [source_container=12345abcd]|

## Inherited methods

| Name | Description |
|------|-------------|
|createContainer|add container|
|createDemoData|create demo data|
|getHelp|echos usage information|
|importegw14|import from egroupware|
|setContainerGrants|set container grants|
|setContainerGrantsReadOnly|setContainerGrantsReadOnly|
|updateImportExportDefinition|update or create import/export definition|



### Crm_Frontend_Cli::migrateProjectsToLeads  

**Description**

```php
public migrateProjectsToLeads (\Zend_Console_Getopt $opts)
```

usage: tine20-cli --method=Crm.migrateProjectsToLeads [-d] [-v] -- container_id=abcde124345 [source_container=12345abcd] 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




<hr />

