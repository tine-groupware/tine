<?php

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Calendar_Model_WeekdayFilter extends Tinebase_Model_Filter_Text
{
    protected $_operators = [
        'startson',
        'endson',
    ];
}