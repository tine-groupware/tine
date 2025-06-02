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

class Tinebase_Model_FileLocation_TreeNode extends Tinebase_Record_NewAbstract implements Tinebase_Model_FileLocation_Interface
{
    use Tinebase_Model_FileLocation_NoChgAfterInitTrait;

    public const MODEL_NAME_PART = 'FileLocation_TreeNode';

    public const FLD_NODE_ID = 'node_id';
    public const FLD_STAT_PATH = 'stat_path'; // important: paths for folders should end on / to force treatment as a folder. Otherwise they may be treated as a file!


    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'File Location', // ngettext('File Location', 'File Locations', n)
        self::RECORDS_NAME                  => 'File Locations', // gettext('GENDER_File Location')

        self::FIELDS                        => [
            self::FLD_NODE_ID                   => [
                self::TYPE                          => self::TYPE_STRING,
            ],
            self::FLD_STAT_PATH                 => [
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
        return null !== $this->node;
    }

    public function isFile(): bool
    {
        $this->_init();
        return $this->isFile;
    }

    public function isDirectory(): bool
    {
        $this->_init();
        return $this->isDirectory;
    }

    public function canReadData(): bool
    {
        $this->_init();
        return $this->canRead;
    }

    public function canWriteData(): bool
    {
        $this->_init();
        return $this->canWrite;
    }

    public function canGetChild(): bool
    {
        $this->_init();
        return $this->isDirectory();
    }

    public function canGetParent(): bool
    {
        $this->_init();
        return null === $this->node || $this->node->parent_id;
    }

    public function getName(): string
    {
        $this->_init();
        return $this->_name;
    }

    public function getContent(): string
    {
        if (!$this->canReadData()) {
            // should not happen, canReadData() needs to be checked first!
            throw new Tinebase_Exception('called getContent on ' . static::class . ' without checking canReadData first');
        }

        return Tinebase_FileSystem::getInstance()->getNodeContents($this->node);
    }

    public function getStream(): \Psr\Http\Message\StreamInterface
    {
        if (!$this->canReadData()) {
            // should not happen, canReadData() needs to be checked first!
            throw new Tinebase_Exception('called getStream on ' . static::class . ' without checking canReadData first');
        }
        $path = Tinebase_FileSystem::getInstance()->getPathOfNode($this->node, getPathAsString:  true, getFromStatCache: true);
        $handle = Tinebase_FileSystem::getInstance()->fopen($path, 'r');
        if (! $handle) {
            throw new Tinebase_Exception('Could not get contents of path ' . $path);
        }

        return new \Laminas\Diactoros\Stream($handle);
    }

    public function writeContent(string $data): int|false
    {
        if (!$this->canWriteData()) {
            // should not happen, canWriteData() needs to be checked first!
            throw new Tinebase_Exception('called writeContent on ' . static::class . ' without checking canWriteData first');
        }

        if (null !== $this->pathToCreate) {
            Tinebase_FileSystem::getInstance()->mkdir($this->pathToCreate);
        }
        $handle = Tinebase_FileSystem::getInstance()->fopen($this->{self::FLD_STAT_PATH}, 'w');
        if (! $handle) {
            throw new Tinebase_Exception('Could not open path ' . $this->{self::FLD_STAT_PATH} . ' for writing');
        }
        $result = fwrite($handle, $data);
        return Tinebase_FileSystem::getInstance()->fclose($handle) ? $result : false;
    }

    public function writeStream(\Psr\Http\Message\StreamInterface $stream): int|false
    {
        return $this->writeContent($stream->getContents());
    }

    public function getChild(string $name): Tinebase_Model_FileLocation_Interface
    {
        if (!$this->canGetChild()) {
            // should not happen, canGetChild() needs to be checked first!
            throw new Tinebase_Exception('called getChild on ' . static::class . ' without checking canGetChild first');
        }

        $isDirectory = str_ends_with($name, '/');
        $slashPos = strpos($name, '/');
        if (false !== $slashPos && (!$isDirectory || $slashPos + 1 !== strlen($name))) {
            throw new Tinebase_Exception('"' . $name . '" not a valid name');
        }

        $_name = str_replace('/', '', $name);
        if ('' === trim($_name)) {
            throw new Tinebase_Exception('"' . $name . '" not a valid name');
        }

        return new static([
            self::FLD_STAT_PATH => rtrim($this->{self::FLD_STAT_PATH}, '/') . '/' . $_name . ($isDirectory ? '/': ''),
        ]);
    }

    public function getParent(): Tinebase_Model_FileLocation_Interface
    {
        if (!$this->canGetParent()) {
            // should not happen, canGetParent() needs to be checked first!
            throw new Tinebase_Exception('called getParent on ' . static::class . ' without checking canGetParent first');
        }

        if ($this->node) {
            return new static([
                self::FLD_NODE_ID => $this->node->parent_id,
            ]);
        } else {
            $pathParts = explode('/', trim($this->{self::FLD_STAT_PATH}, '/'));
            array_pop($pathParts);
            return new static([
                self::FLD_STAT_PATH =>  '/' . join('/', $pathParts) . '/',
            ]);
        }
    }

    public function canListChildren(): bool
    {
        $this->_init();
        return null !== $this->node && $this->node->type === Tinebase_Model_Tree_FileObject::TYPE_FOLDER;
    }

    public function listChildren(): array
    {
        if (!$this->canListChildren()) {
            // should not happen, canListChildren() needs to be checked first!
            throw new Tinebase_Exception('called listChildren on ' . static::class . ' without checking canListChildren first');
        }

        return Tinebase_FileSystem::getInstance()->searchNodes(new Tinebase_Model_Tree_Node_Filter([
            ['field' => 'parent_id', 'operator' => 'equals', 'value' => $this->node->getId()]
        ]))->name;
    }

    protected function _init(): void
    {
        if ($this->_init) {
            return;
        }

        $node = null;
        if ($this->{self::FLD_NODE_ID}) {
            $node = $this->node = Tinebase_FileSystem::getInstance()->get($this->{self::FLD_NODE_ID});
            $this->{self::FLD_STAT_PATH} = Tinebase_FileSystem::getInstance()->getPathOfNode($node, true, true);
        } else {
            try {
                $node = $this->node = Tinebase_FileSystem::getInstance()->stat($this->{self::FLD_STAT_PATH});
                $this->_name = $node->name;
            } catch (Tinebase_Exception_NotFound) {
                $pathParts = explode('/', $this->{self::FLD_STAT_PATH});
                $lastPart = array_pop($pathParts);
                $pathParts = array_filter($pathParts);

                if (empty($lastPart) && !empty($pathParts)) {
                    $this->isDirectory = true;
                    $this->_name = array_pop($pathParts);
                } else {
                    $this->_name = $lastPart;
                    $this->isFile = true;
                    $this->pathToCreate = join('/', $pathParts);
                }

                while (null === $node && !empty($pathParts)) {
                    try {
                        $node = Tinebase_FileSystem::getInstance()->stat(join('/', $pathParts));
                    } catch (Tinebase_Exception_NotFound) {
                        array_pop($pathParts);
                    }
                }
            }
            if (null === $node) {
                throw new Tinebase_Exception_NotFound($this->{self::FLD_STAT_PATH} . ' not found');
            }
        }

        if ($this->node) {
            $this->canRead = $this->isFile = $this->node->type !== Tinebase_Model_Tree_FileObject::TYPE_FOLDER;
            $this->isDirectory = !$this->isFile;
        }

        Tinebase_FileSystem::getInstance()->checkNodeACL($node);
        $this->canWrite = $this->isFile && Tinebase_FileSystem::getInstance()->checkNodeACL($node, 'add', false, false);
        $this->_init = true;
    }

    protected ?Tinebase_Model_Tree_Node $node = null;
    protected bool $canWrite = false;
    protected bool $isFile = false;
    protected string $_name;
    protected bool $isDirectory = false;
    protected bool $canRead = false;
    protected ?string $pathToCreate = null;
}