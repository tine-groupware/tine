---
name: phpdocheader
description: >-
  Adjust the PHPDOC header of all changed PHP files to contain the relevant information
license: AGPL-3.0
metadata:
  author: Philipp Schüle <p.schuele@metaways.de>
  version: "1.0"
---

# PHP File Header

The Header of tine-PHP files should contain the following information:

## title and URL

allways: tine Groupware - https://www.tine-groupware.de/

## package

example: @package Tinebase

## subpackage (optional)

example: @subpackage Group

## license

default: @license https://www.gnu.org/licenses/agpl.html

## copyright

example: @copyright   Copyright (c) 2008-2026 Metaways Infosystems GmbH (https://www.metaways.de)

the second year should always be set to the current year (2026 in this example). we keep the first year as the creation year of the file.

## author

example: Philipp Schüle <p.schuele@metaways.de>

name and email address of the (human) creator of the file. we do not change the author when updating the file.

# Example

~~~php
<?php
/**
 * tine Groupware - https://www.tine-groupware.de/
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
~~~
