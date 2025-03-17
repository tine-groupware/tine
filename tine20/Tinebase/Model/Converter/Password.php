<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_ModelConfiguration_Const as TMCC;

class Tinebase_Model_Converter_Password implements Tinebase_Model_Converter_Interface
{
    public function convertToRecord($record, $key, $blob)
    {
        return $blob;
    }

    /**
     * @param Tinebase_Record_Interface $record
     * @param string $key
     * @param mixed $fieldValue
     * @return null
     */
    public function convertToData($record, $key, $fieldValue)
    {
        if (is_string($fieldValue) && strlen($fieldValue) > 0) {
            $cc = Tinebase_Auth_CredentialCache::getInstance();
            $adapter = explode('_', $cc->getCacheAdapter()::class);
            $adapter = end($adapter);
            try {
                $cc->setCacheAdapter('Shared');

                $refIdProperty = $record::getConfiguration()->getFields()[$key][TMCC::CONFIG][TMCC::REF_ID_FIELD];
                $oldCCId = $record->{$refIdProperty};

                $sharedCredentials = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials(null,
                    $fieldValue, null, true /* save in DB */, Tinebase_DateTime::now()->addYear(100));

                $record->{$refIdProperty} = $sharedCredentials->getId();
                if ($oldCCId) {
                    $cc->delete($oldCCId);
                }
            } finally {
                $cc->setCacheAdapter($adapter);
            }
        }
        return null;
    }
}