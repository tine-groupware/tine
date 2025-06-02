<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

interface Tinebase_Model_FileLocation_Interface
{
    public function exists(): bool;
    public function isFile(): bool;
    public function isDirectory(): bool;
    public function canReadData(): bool;
    public function canWriteData(): bool;
    public function canGetChild(): bool;
    public function canListChildren(): bool;
    public function canGetParent(): bool;
    public function getName(): string;
    public function getContent(): string;
    public function getStream(): \Psr\Http\Message\StreamInterface;
    public function writeContent(string $data): int|false;
    public function writeStream(\Psr\Http\Message\StreamInterface $stream): int|false;
    public function getChild(string $name): self;

    /**
     * @return string[]
     */
    public function listChildren(): array;
    public function getParent(): self;
}