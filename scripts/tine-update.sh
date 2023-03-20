#!/bin/bash
# (c) copyright Metaways Infosystems GmbH 2023
# authors:  Philipp Schüle <p.schuele@metaways.de>
# TODO add more variables?
# TODO add more packages?

TINEPATH="/usr/share/"
TINEUSER="www-data"
TAG=$1

if [ "$TAG" = "" ]
then
  echo "Please enter release tag (for example 2022.11.6):"
  read -r TAG
fi

cd $TINEPATH
mkdir tine_$TAG || exit
cd tine_$TAG
for package in allinone humanresources gdpr inventory usermanual; do wget https://packages.tine20.com/maintenance/source/$TAG/tine20-"$package"_$TAG.tar.bz2; done
for package in allinone humanresources gdpr inventory usermanual; do tar -xjvf tine20-"$package"_$TAG.tar.bz2; done
rm *.bz2
cd $TINEPATH
rm tine20
ln -s tine20_$TAG/ tine20
sudo -u $TINEUSER php $TINEPATH/tine20/setup.php --config=/etc/tine20/config.inc.php --update -v

echo "done."
