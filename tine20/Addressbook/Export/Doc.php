<?php
/**
 * Addressbook Doc generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Addressbook Doc generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 *
 */
class Addressbook_Export_Doc extends Tinebase_Export_Doc
{
    protected $_defaultExportname = 'adb_default_doc';

    /**
     * @param array $context
     * @return array
     */
    protected function _getTwigContext(array $context)
    {
        return parent::_getTwigContext($context + [
                'address' => $this->getAddress($context['record'])
            ]
        );
    }

    /**
     * @param Addressbook_Model_Contact $record
     * @return array
     */
    protected function getAddress($record)
    {
        if (!($record instanceof Addressbook_Model_Contact)) {
            return;
        }

        $address = [
            'firstname' => $record->n_given,
            'lastname' => $record->n_family,
        ];
        if ($adr = $record->getPreferredAddressObject()) {
            $address['street'] = $adr->{Addressbook_Model_ContactProperties_Address::FLD_STREET};
            $address['postalcode'] = $adr->{Addressbook_Model_ContactProperties_Address::FLD_POSTALCODE};
            $address['locality'] = $adr->{Addressbook_Model_ContactProperties_Address::FLD_LOCALITY};
        }
        if ('adr_one' === $record->preferred_address) {
            $address['company'] = $record->org_name;
        }

        return $address;
    }
}
