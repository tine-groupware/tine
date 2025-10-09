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

class Tinebase_Model_FileLocation_TempFile extends Tinebase_Record_NewAbstract implements Tinebase_Model_FileLocation_Interface
{
    use Tinebase_Model_FileLocation_NoChgAfterInitTrait;

    public const MODEL_NAME_PART = 'FileLocation_TempFile';

    public const FLD_TEMP_FILE_ID = 'temp_file_id';
    public const FLD_NAME = 'name';
    public const FLD_TYPE = 'type';


    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'File Location', // ngettext('File Location', 'File Locations', n)
        self::RECORDS_NAME                  => 'File Locations', // gettext('GENDER_File Location')

        self::FIELDS                        => [
            self::FLD_TEMP_FILE_ID              => [
                self::TYPE                          => self::TYPE_STRING,
            ],
            self::FLD_NAME                      => [
                self::TYPE                          => self::TYPE_STRING,
            ],
            self::FLD_TYPE                      => [
                self::TYPE                          => self::TYPE_STRING,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function exists(): bool
    {
        $this->_init();
        return $this->tempFile ? file_exists($this->tempFile->path) : false;
    }

    public function isFile(): bool
    {
        $this->_init();
        return true;
    }

    public function isDirectory(): bool
    {
        $this->_init();
        return false;
    }

    public function canReadData(): bool
    {
        $this->_init();
        return $this->tempFile ? file_exists($this->tempFile->path) : false;
    }

    public function canWriteData(): bool
    {
        $this->_init();
        return true;
    }

    public function canGetChild(): bool
    {
        $this->_init();
        return false;
    }

    public function canGetParent(): bool
    {
        $this->_init();
        return false;
    }

    public function getName(): string
    {
        $this->_init();
        return $this->tempFile?->name ?: ($this->{self::FLD_NAME} ?: 'tempfile.tmp');
    }

    public function getContent(): string
    {
        if (!$this->canReadData()) {
            // should not happen, canReadData() needs to be checked first!
            throw new Tinebase_Exception('called getContent on ' . static::class . ' without checking canReadData first');
        }
        return file_get_contents($this->tempFile->path);
    }

    public function getStream(): \Psr\Http\Message\StreamInterface
    {
        if (!$this->canReadData()) {
            // should not happen, canReadData() needs to be checked first!
            throw new Tinebase_Exception('called getStream on ' . static::class . ' without checking canReadData first');
        }
        $handle = fopen($this->tempFile->path, 'r');
        if (! $handle) {
            throw new Tinebase_Exception('Could not get contents of path ' . $this->tempFile->path);
        }

        return new \Laminas\Diactoros\Stream($handle);
    }

    public function writeContent(string $data): int|false
    {
        if (!$this->canWriteData()) {
            // should not happen, canWriteData() needs to be checked first!
            throw new Tinebase_Exception('called writeContent on ' . static::class . ' without checking canWriteData first');
        }

        if (!$this->tempFile) {
            $path = Tinebase_TempFile::getTempPath();
            file_put_contents($path, $data);
            $this->tempFile = Tinebase_TempFile::getInstance()->createTempFile($path, $this->getName(),
                $this->{self::FLD_TYPE} ?: 'unknown');
            $this->_data[self::FLD_TEMP_FILE_ID] = $this->tempFile->getId();
        }

        return file_put_contents($this->tempFile->path, $data);
    }

    public function writeStream(\Psr\Http\Message\StreamInterface $stream): int|false
    {
        return $this->writeContent($stream->getContents());
    }

    public function getChild(string $name): Tinebase_Model_FileLocation_Interface
    {
        $this->_init();
        throw new Tinebase_Exception('called getChild on ' . static::class . ' without checking canGetChild first');
    }

    public function getParent(): Tinebase_Model_FileLocation_Interface
    {
        $this->_init();
        throw new Tinebase_Exception('called getParent on ' . static::class . ' without checking canGetParent first');
    }

    public function canListChildren(): bool
    {
        $this->_init();
        return false;
    }

    public function listChildren(): array
    {
        $this->_init();
        throw new Tinebase_Exception('called listChildren on ' . static::class . ' without checking canListChildren first');
    }

    protected function _init(): void
    {
        if ($this->_init) {
            return;
        }

        if ($this->{self::FLD_TEMP_FILE_ID}) {
            if (null === ($this->tempFile = Tinebase_TempFile::getInstance()->getTempFile($this->{self::FLD_TEMP_FILE_ID}))) {
                throw new Tinebase_Exception_NotFound('Could not find temp file "' . $this->{self::FLD_TEMP_FILE_ID} . '"');
            }
        }

        $this->_init = true;
    }

    protected ?Tinebase_Model_TempFile $tempFile = null;
}