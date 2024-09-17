<?php
/**
 * GDPR Json Frontend
 *
 * @package      GDPR
 * @subpackage   Frontend
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * GDPR Json Frontend
 *
 * @package      GDPR
 * @subpackage   Frontend
 */
class GDPR_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * @see Tinebase_Frontend_Json_Abstract
     * TODO fix this
    protected $_relatableModels = [
        ContractManager_Model_Contract::class
    ];*/

    /**
     * the models handled by this frontend
     * @var array
     */
    protected $_configuredModels = [
        GDPR_Model_DataProvenance::MODEL_NAME_PART,
        GDPR_Model_DataIntendedPurpose::MODEL_NAME_PART,
        GDPR_Model_DataIntendedPurposeRecord::MODEL_NAME_PART,
        GDPR_Model_DataIntendedPurposeLocalization::MODEL_NAME_PART,
    ];

    /**
     * user field   s (created_by, ...) to resolve in _multipleRecordsToJson and _recordToJson
     * TODO fix this
     * @var array
     *
    protected $_resolveUserFields = [
        'ContractManager' => ['created_by', 'last_modified_by']
    ];*/

    public function __construct()
    {
        $this->_applicationName = GDPR_Config::APP_NAME;
    }
    
    public function getRecipientTokensByIntendedPurpose($recordId)
    {
        $contacts = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME, 'operator' => 'definedBy', 'value' => [
                    ['field' => GDPR_Model_DataIntendedPurposeRecord::FLD_INTENDEDPURPOSE, 'operator' => 'equals', 'value' => $recordId],
                ]],
            ]
        ));

        $tokens = [];
        foreach ($contacts as $contact) {
            $tokens = array_merge($tokens, $contact->getRecipientTokens(true));
        }

        return [
            'results'       => $tokens,
            'totalcount'    => count($tokens),
        ];
    }
}
