# Migration Path
As there are done breaking changes from time to time to the tine core or any applications, it is recommended
(and supported) to only update from a given major version (latest minor release) to the next one
(again the latest minor release). You can still find the tarball downloads of the last minor releases of older 
version (not older than 2017) in the [github releases](https://github.com/tine20/tine20/releases).
Business Edition packages can be found [here](https://packages.tine20.com/maintenance/source/)

## Community Edition

So currently (July 2024) we have the following (possible) update path (beginning 2017):

2017.02.5 2017.08.11 2018.02.7 2018.08.9 2019.02.8 2019.08.4 2019.12.5 2020.03.4 2020.08.8 2021.02.4
2021.12.1 2022.12.1 2023.12.1 2023.11.12 (or weekly-2024.XX)

You could also update to the corresponding Business Edition (XXXX.11)

Another possible way (for example if you have a very old version) is to download/checkout the source 
(from the repo/release tag in the [old github repo](https://github.com/tine20/tine20) and do the following:

run "composer install" (see development setup for more information)
run "php setup.php --update"

Johannes also has written an [article about the Community Edition migration path](https://www.nohl.eu/tine-20/legacy-migration-path/)
