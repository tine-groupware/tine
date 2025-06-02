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

/**
 * @method _init()
 */
trait Tinebase_Model_FileLocation_DelegatorTrait
{
    public function exists(): bool
    {
        $this->_init();
        return $this->delegator->exists();
    }

    public function isFile(): bool
    {
        $this->_init();
        return $this->delegator->isFile();
    }

    public function isDirectory(): bool
    {
        $this->_init();
        return $this->delegator->isDirectory();
    }

    public function canReadData(): bool
    {
        $this->_init();
        return $this->delegator->canReadData();
    }

    public function canWriteData(): bool
    {
        $this->_init();
        return $this->delegator->canWriteData();
    }

    public function canGetChild(): bool
    {
        $this->_init();
        return $this->delegator->canGetChild();
    }

    public function canGetParent(): bool
    {
        $this->_init();
        return $this->delegator->canGetParent();
    }

    public function getName(): string
    {
        $this->_init();
        return $this->delegator->getName();
    }

    public function getContent(): string
    {
        $this->_init();
        return $this->delegator->getContent();
    }

    public function getStream(): \Psr\Http\Message\StreamInterface
    {
        $this->_init();
        return $this->delegator->getStream();
    }

    public function writeContent(string $data): int|false
    {
        $this->_init();
        return $this->delegator->writeContent($data);
    }

    public function writeStream(\Psr\Http\Message\StreamInterface $stream): int|false
    {
        $this->_init();
        return $this->delegator->writeStream($stream);
    }

    public function getChild(string $name): Tinebase_Model_FileLocation_Interface
    {
        $this->_init();
        return $this->delegator->getChild($name);
    }

    public function getParent(): Tinebase_Model_FileLocation_Interface
    {
        $this->_init();
        return $this->delegator->getParent();
    }

    public function canListChildren(): bool
    {
        $this->_init();
        return $this->delegator->canListChildren();
    }

    public function listChildren(): array
    {
        $this->_init();
        return $this->delegator->listChildren();
    }

    protected Tinebase_Model_FileLocation_Interface $delegator;
}