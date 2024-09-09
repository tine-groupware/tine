#!/bin/sh

samba-tool domain passwordsettings set --complexity=off
samba-tool domain passwordsettings set --min-pwd-length=0