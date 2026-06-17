<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * virtual felamimail message pipe config model
 *
 * @package     Felamimail
 * @subpackage  Model
 *
 * @property string                                                         classname
 * @property Tinebase_Record_Interface|Tinebase_BL_ElementConfigInterface   configRecord
 */
class Felamimail_Model_MessagePipeConfig extends Tinebase_Model_BLConfig
{
    const MODEL_NAME_PART = 'MessagePipeConfig';
    const USER_RATING_SPAM = 'spam';
    const USER_RATING_HAM = 'ham';

    public static function factory(array $options) {

        if (!isset($options['config'])) {
            throw new Exception("strategy config is not set");
        }

        switch ($options['strategy']) {
            case 'copy':
                return new Felamimail_Model_MessagePipeCopy($options['config']);
                /* $options['config'] = ['target' => [array data]] */
                break;

            case 'move':
                return new Felamimail_Model_MessagePipeMove($options['config']);
                /* $options['config'] = ['target' => [array data]] */
                break;

            case 'rewrite_subject':
                return new Felamimail_Model_MessagePipeRewriteSubject($options['config']);
                /* $options['config'] = ['pattern' => '/SPAM\? \(.+\) \*\*\* /',
                                         'replacement => ''] */
                break;
            case 'remove_header':
                return new Felamimail_Model_MessagePipeRemoveHeader($options['config']);
                /* $options['config'] = [
                    'header' => 'x-spam'
                    'value'  => 'yes'
                ] */
                break;
            default :
                throw new Exception('the strategy is not supported');
                break;
        }
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static function inheritModelConfigHook(array &$_defintion)
    {
            $_defintion[self::APP_NAME] = Felamimail_Config::APPLICATION_NAME;
        $_defintion[self::MODEL_NAME] = self::MODEL_NAME_PART;
        if (!isset($_defintion[self::FIELDS][self::FLDS_CLASSNAME][self::CONFIG])) {
            $_defintion[self::FIELDS][self::FLDS_CLASSNAME][self::CONFIG] = [];
        }
        $_defintion[self::FIELDS][self::FLDS_CLASSNAME][self::CONFIG][self::AVAILABLE_MODELS] = [
            Felamimail_Model_MessagePipeCopy::class,
            Felamimail_Model_MessagePipeMove::class,
            Felamimail_Model_MessagePipeRewriteSubject::class,
            Felamimail_Model_MessagePipeRemoveHeader::class
        ];
    }

    /**
     * @param Felamimail_Model_Account $_account
     * @param string $_targetFolder
     * @return Felamimail_Model_Folder
     * @throws Felamimail_Exception_IMAPServiceUnavailable
     * @throws Tinebase_Exception_SystemGeneric
     */
    public static function getTargetFolder($_account, $_targetFolder)
    {
        if(!$_account) {
            throw new Exception("account is not set");
        }

        if ( !is_string($_targetFolder)) {
            throw new Exception("target folder needs to be a string");
        }

        if (empty($_targetFolder)) {
            throw new Exception("config target folder is not set");
        }

        if ($_targetFolder[0] == '#') {
            $folderName = strtolower(substr($_targetFolder, 1));
            $propertyName = "{$folderName}_folder";
            if (!$_account->has($propertyName)) {
                throw new Exception("config target folder is not set");
            }
            $_targetFolder = $_account->{$propertyName};
        }

        try {
            $_targetFolder = str_replace('/', $_account->delimiter, $_targetFolder);
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' looking for folder ' . $_targetFolder);
            $folder = Felamimail_Controller_Folder::getInstance()
                ->getByBackendAndGlobalName($_account->getId(), $_targetFolder);
        } catch (Tinebase_Exception_NotFound $e) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Folder not found: ' . $_targetFolder);
            $splitFolderName = Felamimail_Model_Folder::extractLocalnameAndParent($_targetFolder, $_account->delimiter);

            $parentSubs = Felamimail_Controller_Cache_Folder::getInstance()
                ->update($_account, $splitFolderName['parent'], TRUE);
            $folder = $parentSubs->filter('globalname', $_targetFolder)->getFirstRecord();

            if ($folder === NULL) {
                $folder = Felamimail_Controller_Folder::getInstance()
                    ->create($_account->getId(), $splitFolderName['localname'], $splitFolderName['parent']);
            }
        }

        return $folder;
    }
}