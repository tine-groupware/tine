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

class Tinebase_Model_FileLocation_RecordAttachment extends Tinebase_Model_FileLocation_TreeNode
{
    public const MODEL_NAME_PART = 'FileLocation_RecordAttachment';

    public const FLD_RECORD_ID = 'record_id';
    public const FLD_MODEL = 'model';
    public const FLD_NAME = 'name';


    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'File Location', // ngettext('File Location', 'File Locations', n)
        self::RECORDS_NAME                  => 'File Locations', // gettext('GENDER_File Location')

        self::FIELDS                        => [
            self::FLD_RECORD_ID                 => [
                self::TYPE                          => self::TYPE_STRING,
            ],
            self::FLD_MODEL                     => [
                self::TYPE                          => self::TYPE_STRING,
            ],
            self::FLD_NAME                      => [
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

    public function canGetParent(): bool
    {
        $this->_init();
        return $this->isFile();
    }

    public function writeContent(string $data): int|false
    {
        if (!$this->canWriteData()) {
            // should not happen, canWriteData() needs to be checked first!
            throw new Tinebase_Exception('called writeContent on ' . static::class . ' without checking canWriteData first');
        }

        $path = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath($this->record, true) . '/' . $this->_name;
        $handle = Tinebase_FileSystem::getInstance()->fopen($path, 'w');
        if (! $handle) {
            throw new Tinebase_Exception('Could not open path ' . $path . ' for writing');
        }
        $result = fwrite($handle, $data);
        return Tinebase_FileSystem::getInstance()->fclose($handle) ? $result : false;
    }

    public function getChild(string $name): Tinebase_Model_FileLocation_Interface
    {
        if (!$this->canGetChild()) {
            // should not happen, canGetChild() needs to be checked first!
            throw new Tinebase_Exception('called getChild on ' . static::class . ' without checking canGetChild first');
        }

        $_name = str_replace('/', '', $name);
        if ('' === trim($_name)) {
            throw new Tinebase_Exception('"' . $name . '" not a valid name');
        }

        return new static([
            self::FLD_RECORD_ID => $this->{self::FLD_RECORD_ID},
            self::FLD_MODEL => $this->{self::FLD_MODEL},
            self::FLD_NAME => $_name,
        ]);
    }

    public function getParent(): Tinebase_Model_FileLocation_Interface
    {
        if (!$this->canGetParent()) {
            // should not happen, canGetParent() needs to be checked first!
            throw new Tinebase_Exception('called getParent on ' . static::class . ' without checking canGetParent first');
        }

        return new static([
            self::FLD_RECORD_ID => $this->{self::FLD_RECORD_ID},
            self::FLD_MODEL => $this->{self::FLD_MODEL},
        ]);
    }

    public function canListChildren(): bool
    {
        return $this->isDirectory();
    }

    public function listChildren(): array
    {
        if (!$this->canListChildren()) {
            // should not happen, canListChildren() needs to be checked first!
            throw new Tinebase_Exception('called listChildren on ' . static::class . ' without checking canListChildren first');
        }

        if (null === $this->node) {
            return [];
        }

        // attachment ACL are handled by the record, not the FS
        return Tinebase_FileSystem::getInstance()->searchNodes(new Tinebase_Model_Tree_Node_Filter([
            ['field' => 'parent_id', 'operator' => 'equals', 'value' => $this->node->getId()],
            ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FILE],
        ], _options: [Tinebase_Model_Filter_FilterGroup::IGNORE_ACL => true]))->name;
    }

    protected function _init(): void
    {
        if ($this->_init) {
            return;
        }

        $ctrl = Tinebase_Core::getApplicationInstance($this->{self::FLD_MODEL});
        if (!$ctrl instanceof Tinebase_Controller_Record_Abstract) {
            throw new Tinebase_Exception($this->{self::FLD_MODEL} . ' has no record ctrl');
        }
        // check existance
        $this->record = $ctrl->getBackend()->get($this->{self::FLD_RECORD_ID});
        $ctrl->checkGrant($this->record, Tinebase_Model_Grants::GRANT_READ, _throw: true);

        $_name = str_replace('/', '', (string)$this->{self::FLD_NAME});
        if ('' === trim($_name) && !empty($this->{self::FLD_NAME})) {
            throw new Tinebase_Exception('"' . $this->{self::FLD_NAME} . '" not a valid name');
        }

        if ('' !== trim($_name)) {
            $this->_name = $_name;
            $this->isFile = true;
            $this->canWrite = $ctrl->checkGrant($this->record, Tinebase_Model_Grants::GRANT_EDIT, _throw: false);

            try {
                $this->node = Tinebase_FileSystem::getInstance()->stat(Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath($this->record) . '/' . $this->_name);
                $this->canRead = true;
            } catch (Tinebase_Exception_NotFound) {}
        } else {
            $this->_name = $this->record->getId();
            try {
                $this->node = Tinebase_FileSystem::getInstance()->stat(Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath($this->record));
            } catch (Tinebase_Exception_NotFound) {}
            $this->isDirectory = true;
        }

        $this->_init = true;
    }

    protected Tinebase_Record_Interface $record;
}