UserManual Module for Tine
=============

Documentation
=============

Development
=============

HOWTOs
=============

Import manual into database:

    $ php tine20.php --method=UserManual.importManualPages \
      UserManual/doc/tine20_handbuch_2017-01-31_base64_2941752.tar.gz
    
Import manual with clearing existing database records first:

    $ php tine20.php --method=UserManual.importManualPages clear=1 \
      UserManual/doc/tine20_handbuch_2017-01-31_base64_2941752.tar.gz
    
