<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Felamimail
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Felamimail
 * @subpackage  Convert
 */
class Felamimail_Convert_Message_Json extends Tinebase_Convert_Json
{
    /**
     * @param Tinebase_Record_RecordSet|NULL $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return array|array[]|mixed
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records = null, $_filter = null, $_pagination = null)
    {
        $this->_dehydrateFileLocations($_records);
        
        foreach ($_records as $record) {
            $flags = array_diff($record->flags, Felamimail_Controller_Message_Flags::getInstance()->getSupportedFlags(false));
            $tags = Tinebase_Tags::getInstance()->getTagsById($flags);
            $record->tags = $tags;
        }
        
        return parent::fromTine20RecordSet($_records, $_filter, $_pagination);
    }

    /**
     * converts Tinebase_Record_Interface to external format
     *
     * @param  Tinebase_Record_Interface  $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        $this->_dehydrateFileLocations([$_record]);

        $this->_resolveAddress($_record);

        // remove message structure - client does not need it, and it might contain bad chars that can't be encoded
        // with \Zend_Json::encode
        unset($_record->structure);

        return parent::fromTine20Model($_record);
    }

    /**
     * get fileLocations and add them to records
     *
     * @param $_records
     */
    protected function _dehydrateFileLocations($_records)
    {
        foreach ($_records as $record) {
            $record->fileLocations = Felamimail_Controller_MessageFileLocation::getInstance()->getLocationsForMessage($record);
        }
    }

    /**
     * resolve address
     *
     * @param Tinebase_Record_Interface $record
     * @return Tinebase_Record_Interface
     * @throws Felamimail_Exception_IMAPMessageNotFound
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _resolveAddress(Tinebase_Record_Interface &$record)
    {
        // resolve all recipient fields to same format
        $record['from'] = [$record['from_name'] . ' <' . $record['from_email'] . '>'];
        
        foreach (['to', 'cc', 'bcc', 'from'] as $type) {
            if (empty($record[$type])) {
                continue;
            }
            
            $addressList = [];
            $resolvedAddressList = [];
            // parsed addresses
            if (is_array($record[$type])) {
                foreach ($record[$type] as $addressData) {
                    if (is_array($addressData)) {
                        $resolvedAddressList[] = $addressData;
                        continue;
                    }
                    
                    $list = Tinebase_Mail::parseAdresslist($addressData);

                    foreach ($list as $data) {
                        if (!empty($data['address'])) {
                            $addressList[] = $data;
                        }
                    }
                }
            } else {
                $addressList = Tinebase_Mail::parseAdresslist($record[$type]);
            }
            
            // parse header as address too , it might have more information
            if ($record->folder_id) {
                $headers = Felamimail_Controller_Message::getInstance()->getMessageHeaders($record, null, true);
                
                if (! empty($headers[$type])) {
                    if (is_string($headers[$type])) {
                        $addressList = Tinebase_Mail::parseAdresslist($headers[$type]);
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . 'Can not resolve recipient header with type : ' . $type . ' . skip : ' . print_r($headers[$type], true));
                    }
                }
            }

            $emails = array_map(function($address) {return $address['address'];},  $addressList);
            $names = array_map(function($address)  {return $address['name'];},  $addressList);
            
            if (count($addressList) > 0) {
                $tokens = Addressbook_Controller_Contact::getInstance()->searchRecipientTokensByEmailArrays($emails, $names);
                // add parsed address data to addressList
                foreach ($addressList as $parsedEmail) {
                    $address = [
                        "email" => $parsedEmail["address"] ?? '',
                        "name" => $parsedEmail["name"] ?? '',
                        "type" =>  '',
                        "n_fileas" => '',
                        "email_type_field" =>  '',
                        "contact_record" => ''
                    ];

                    $existingToken = array_filter($tokens, fn($token) =>
                       $parsedEmail['address'] === $token['email'] && $parsedEmail['name'] === $token['n_fileas']
                    );
                    if (count($existingToken) > 0) {
                        $address = array_pop($existingToken);
                    }
                    $resolvedAddressList[] = $address;
                }
            }
            
            $record[$type] = $resolvedAddressList;

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Resolved addresses :  ' . print_r($record[$type], true));
        }
        
        return $record;
    }
}
