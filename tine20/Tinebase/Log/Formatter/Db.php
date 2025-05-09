<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Class Tinebase_Log_Formatter_Json
 *
 * @todo support timelog
 */
class Tinebase_Log_Formatter_Db extends Tinebase_Log_Formatter
{
    protected const NOUSERID = 'no_user_id';
    protected const MAX_MESSAGE_SIZE = 10000;

    /**
     * Formats data into a array compatible to database
     *
     * @param array $event event data
     * @return array
     * @throws Exception
     */
    public function format($event)
    {
        $data = $this->getLogData($event);

        $data['id'] = Tinebase_Record_Abstract::generateUID();
        $data['user'] = is_object(Tinebase_Core::getUser()) ? Tinebase_Core::getUser()->getId() : self::NOUSERID;

        $timestamp = new Tinebase_DateTime($data['timestamp']);
        $data['timestamp'] = $timestamp->toString();
        if (mb_strlen((string) $data['message']) > self::MAX_MESSAGE_SIZE) {
            $data['message'] = mb_substr((string) $event['message'], 0, self::MAX_MESSAGE_SIZE) . ' ...';
        }

        return $data;
    }
}
