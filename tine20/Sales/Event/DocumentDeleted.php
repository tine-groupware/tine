<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Sales_Event_DocumentDeleted extends Tinebase_Event_Abstract
{
    public function __construct(
        protected Sales_Model_Document_Abstract $document,
    )
    {
        parent::__construct([]); // use default value to avoid calling default constructor in case of signature change
    }

    public function getDeletedDocument(): Sales_Model_Document_Abstract
    {
        return $this->document;
    }
}