<?php declare(strict_types=1);
/**
 * interface for EDocument dispatches
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

interface Sales_Model_EDocument_Dispatch_Interface
{
    public function dispatch(Sales_Model_Document_Abstract $document, ?string $parentDispatchId = null): bool;
    public function getRequiredDocumentTypes(): array;
    public function getMissingDocumentTypes(Sales_Model_Document_Abstract $document): array;
}