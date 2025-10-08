<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Felamimail_Model_AttachmentCache_FileLocation extends Tinebase_Record_NewAbstract implements Tinebase_Model_FileLocation_Interface
{
    use Tinebase_Model_FileLocation_NoChgAfterInitTrait;
    use Tinebase_Model_FileLocation_DelegatorTrait;

    public const MODEL_NAME_PART = 'AttachmentCache_FileLocation';
    public const FLD_CACHE_ID = 'cache_id';


    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'File Location', // ngettext('File Location', 'File Locations', n)
        self::RECORDS_NAME                  => 'File Locations', // gettext('GENDER_File Location')

        self::FIELDS                        => [
            self::FLD_CACHE_ID                  => [
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

    protected function _init(): void
    {
        if ($this->_init) {
            return;
        }

        $attachment = Felamimail_Controller_AttachmentCache::getInstance()->get($this->{self::FLD_CACHE_ID});
        $attachment = $attachment->attachments->getFirstRecord();

        $this->delegator = new Tinebase_Model_FileLocation_RecordAttachment([
            Tinebase_Model_FileLocation_RecordAttachment::FLD_MODEL => Felamimail_Model_AttachmentCache::class,
            Tinebase_Model_FileLocation_RecordAttachment::FLD_RECORD_ID => $this->{self::FLD_CACHE_ID},
            Tinebase_Model_FileLocation_RecordAttachment::FLD_NAME => $attachment->name,
        ]);

        $this->_init = true;
    }

    public function canGetParent(): bool
    {
        $this->_init();
        return false;
    }

    public function canWriteData(): bool
    {
        $this->_init();
        return false;
    }

    public function getParent(): Tinebase_Model_FileLocation_Interface
    {
        $this->_init();
        throw new Tinebase_Exception('called getParent on ' . static::class . ' without checking canGetParent first');
    }

    public function writeContent(string $data): int|false
    {
        $this->_init();
        throw new Tinebase_Exception('called writeContent on ' . static::class . ' without checking canWriteData first');
    }

    public function writeStream(\Psr\Http\Message\StreamInterface $stream): int|false
    {
        $this->_init();
        throw new Tinebase_Exception('called writeStream on ' . static::class . ' without checking canWriteData first');
    }
}