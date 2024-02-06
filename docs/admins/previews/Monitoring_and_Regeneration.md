Preview / Docservice Monitoring
=

Use this CLI function (it does not work if return code != 0):

~~~
php tine20.php --method=Tinebase.monitoringCheckPreviewService
~~~

Preview (Re-)Generation
=

Run this CLI function to re-generate missing previews (also deletes previews of files that no longer exist):

~~~
php tine20.php --method=Tinebase.fileSystemCheckPreviews
~~~

### Regenerate preview of single nodes

~~~
php tine20.php --method=Tinebase.fileSystemCheckPreviews -- ids=NODE_ID1,NODE_ID2
~~~


Preview Status Report
=

Run this CLI function to get a status report of all previews:

~~~
php tine20.php --method=Tinebase.reportPreviewStatus
~~~
