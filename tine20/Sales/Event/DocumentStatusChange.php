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

class Sales_Event_DocumentStatusChange extends Tinebase_Event_Abstract
{
    public function __construct(
        protected Sales_Model_Document_Abstract $document,
        protected ?Sales_Model_Document_Abstract $orgDocument = null
    )
    {
        parent::__construct([]); // use default value to avoid calling default constructor in case of signature change
    }

    public function getUpdatedDocument(): Sales_Model_Document_Abstract
    {
        return $this->document;
    }

    public function getOriginalDocument(): ?Sales_Model_Document_Abstract
    {
        return $this->orgDocument;
    }
}