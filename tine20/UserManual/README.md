UserManual Module for Tine
=============

Documentation
=============

Development
=============

HOWTOs
=============

Download Manual from https://packages.tine20.com/maintenance/manual/

Import manual into database:

    $ php tine20.php --method=UserManual.importHandbookBuild \
      tine20-handbook_html_chunked_build-1071705_commit-91af5aa9edabc31757e77fcd821ae2f71b5da4f1.zip
    
    
Your Database might need this setting for the import (minimum value):

    SET GLOBAL max_allowed_packet=209715210;
