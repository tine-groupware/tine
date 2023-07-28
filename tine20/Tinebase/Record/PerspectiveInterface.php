<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

interface Tinebase_Record_PerspectiveInterface
{
    public function setPerspectiveData(string $property, ?array $data): mixed;
    public function getPerspectiveData(string $property): ?array;

    public function setPerspectiveTo(Tinebase_Record_Interface $perspectiveRec): void;
    public function getPerspectiveRecord(): Tinebase_Record_Interface;

    public function getPerspectiveKey(Tinebase_Record_Interface $record): string;
}