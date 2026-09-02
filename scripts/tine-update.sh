#!/bin/bash
# (c) copyright Metaways Infosystems GmbH 2023
# authors:  Philipp Schüle <p.schuele@metaways.de>
# TODO add more variables?
# TODO add more packages?
# TODO make this a basic install script, too?

TINEPATH="/usr/share/"
TINEUSER="www-data"
TINEDIR="tine20"
CONFIG="/etc/tine20/config.inc.php"
TAG=$1

set -e

if [ "$TAG" = "" ]
then
  echo "Please enter release tag (for example 2022.11.13):"
  read -r TAG
fi

cd $TINEPATH
mkdir tine_$TAG
cd tine_$TAG
for package in allinone humanresources gdpr inventory usermanual; do wget https://packages.tine20.com/maintenance/source/$TAG/tine20-"$package"_$TAG.tar.bz2; done
for package in allinone humanresources gdpr inventory usermanual; do tar -xjvf tine20-"$package"_$TAG.tar.bz2; done
rm *.bz2
cd $TINEPATH
if [ -f "$TINEDIR/config.inc.php" ]; then
    cp $TINEDIR/config.inc.php tine_$TAG
fi
if [ -d "$TINEDIR" ]; then
    rm $TINEDIR
fi
ln -s tine_$TAG/ $TINEDIR

sudo -u $TINEUSER php $TINEPATH/$TINEDIR/setup.php --config=$CONFIG --update -v

echo "done."
