<?php

/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class Sales_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Sales', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        $adbController = Addressbook_Controller_Contact::getInstance();
        $filter = new Tinebase_Model_RelationFilter(array(
            array('field' => 'related_model', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::class),
            array('field' => 'own_model', 'operator' => 'equals', 'value' => Sales_Model_Address::class),
            array('field' => 'type', 'operator' => 'equals', 'value' => 'CONTACTADDRESS'),
        ), 'AND');

        $existingRelations = Tinebase_Relations::getInstance()->search($filter);

        foreach ($existingRelations as $relation) {
            $contact = $adbController->get($relation->related_id);
            if ($contact->email) {
                $adbController->update($contact);
            }
        }

        $this->addApplicationUpdate('Sales', '16.1', self::RELEASE016_UPDATE001);
    }
}
