<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Admin csv import class
 * 
 * @package     Admin
 * @subpackage  Import
 * 
 */
class Admin_Import_User_Csv extends Tinebase_Import_Csv_Abstract
{
    protected $_createdPasswords = [];
    protected $_createdAccounts = [];
    /**
     * @var Admin_Controller_User
     */
    protected $_controller;

    /**
     * additional config options
     * 
     * @var array
     */
    protected $_additionalOptions = array(
        'group_id'                      => '',
        'password'                      => '',
        'accountLoginNamePrefix'        => '',
        'accountHomeDirectoryPrefix'    => '',
        'accountEmailDomain'            => '',
        'samba'                         => '',
        'accountLoginShell'             => '',
        'userNameSchema'                => 1,
        'afterAccountLoginName'         => null,
    );

    /**
     * init import result data
     */
    protected function _initImportResult()
    {
        parent::_initImportResult();
        $this->_createdPasswords = [];
        $this->_createdAccounts = [];
    }

    public function getCreatedPasswords(): array
    {
        return $this->_createdPasswords;
    }

    public function getCreatedAccounts(): array
    {
        return $this->_createdAccounts;
    }

    /**
     * set controller
     *
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setController()
    {
        switch($this->_options['model']) {
            case 'Tinebase_Model_FullUser':
                $this->_controller = Admin_Controller_User::getInstance();
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument(get_class($this) . ' needs correct model in config.');
        }

        if (empty($this->_options['accountEmailDomain'])) {
            $config = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP)->toArray();
            if (isset($config['primarydomain'])) {
                $this->_options['accountEmailDomain'] = $config['primarydomain'];
            }
        }
    }
    
    /**
     * import single record (create password if in data)
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $_resolveStrategy
     * @param array $_recordData
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _importRecord($_record, $_resolveStrategy = NULL, $_recordData = array())
    {
        if ($_record instanceof Tinebase_Model_FullUser && $this->_controller instanceof Admin_Controller_User) {

            $this->_resolveGroups($_record);

            $record = $_record;

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' record Data' . print_r($_recordData, true));
            if (isset($_recordData['smtpUser'])) {
                $record->smtpUser = new Tinebase_Model_EmailUser($_recordData['smtpUser']);
            }
            if (isset($_recordData['imapUser'])) {
                $record->imapUser = new Tinebase_Model_EmailUser($_recordData['imapUser']);
            }
            if (isset($_recordData['samba']) && (!isset($this->_options['samba']) || empty($this->_options['samba']))) {
                $this->_options['samba'] = $_recordData['samba'];
            }
            if (isset($_recordData['accountHomeDirectoryPrefix'])) {
                $this->_options['accountHomeDirectoryPrefix'] = $_recordData['accountHomeDirectoryPrefix'];
            }

            $record->applyTwigTemplates();
            $password = $record->applyOptionsAndGeneratePassword($this->_options, (isset($_recordData['password'])) ? $_recordData['password'] : NULL);
            Tinebase_Event::fireEvent(new Admin_Event_BeforeImportUser($record, $this->_options));
            
            // try to create record with password
            if ($record->isValid()) {
                if (!$this->_options['dryrun']) {
                    $record = $this->_controller->create($record, $password, $password);
                    $this->_createdPasswords[$record->getId()] = $password;
                    $this->_createdAccounts[$record->getId()] = $record;
                } else {
                    $this->_importResult['results']->addRecord($record);
                }
                $this->_importResult['totalcount']++;
            } else {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Record invalid: ' . print_r($record->getValidationErrors(), TRUE));
                throw new Tinebase_Exception_Record_Validation('Imported record is invalid.');
            }
        } else {
            $record = parent::_importRecord($_record, $_resolveStrategy, $_recordData);
        }
        return $record;
    }
    
    protected function _doMapping($_data)
    {
        $result = parent::_doMapping($_data);
            $result['smtpUser'] = array(
                'emailForwardOnly' => isset($result['emailForwardOnly']) ? $result['emailForwardOnly'] : true,
                'emailForwards'    => isset($result['emailForwards']) && !empty($result['emailForwards']) ? explode(' ', trim($result['emailForwards'])) : array(),
                'emailAliases'     => isset($result['emailAliases']) ? explode(' ', trim($result['emailAliases'])) : array()
                            );
            $result['groups'] = !empty($result['groups']) ? array_map('trim',explode(",",$result['groups'])) : array();
            
            $result['samba'] = array(
                'homePath'      => (isset($result['homePath'])) ? stripslashes($result['homePath']) : '',
                'homeDrive'     => (isset($result['homeDrive'])) ? stripslashes($result['homeDrive']) : '',
                'logonScript'   => (isset($result['logonScript'])) ? $result['logonScript'] : '',
                'profilePath'   => (isset($result['profilePath'])) ? stripslashes($result['profilePath']) : '',
                'pwdCanChange'  => isset($result['pwdCanChange'])  ? new Tinebase_DateTime($result['pwdCanChange']) : '',
                'pwdMustChange' => isset($result['pwdMustChange']) ? new Tinebase_DateTime($result['pwdMustChange']) : ''
                            );
        return $result;
    }

    /**
     * resolve / create import user groups (might be given as name)
     *
     * @param Tinebase_Model_FullUser $_record
     * @return void
     */
    protected function _resolveGroups(Tinebase_Model_FullUser$_record)
    {
        $_record->accountPrimaryGroup = $this->_resolveGroup($_record->accountPrimaryGroup);
        if (is_array($_record->groups)) {
            $groups = [];
            foreach ($_record->groups as $group) {
                $groups[] = $this->_resolveGroup($group);
            }
            $_record->groups = $groups;
        }
    }

    protected static $_resolvedGroups = [];
    public static function resetResolvedGroups(): void
    {
        static::$_resolvedGroups = [];
    }

    protected function _resolveGroup(?string $groupNameOrId): ?string
    {
        if (! $groupNameOrId) {
            return $groupNameOrId;
        } elseif (!isset(static::$_resolvedGroups[$groupNameOrId])) {
            /** @var Tinebase_Group_Sql $groupController */
            $groupController = Tinebase_Group::getInstance();
            try {
                $group = $groupController->getGroupById($groupNameOrId);
            } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                try {
                    $group = $groupController->getGroupByName($groupNameOrId);
                } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                    // create group on the fly
                    $group = $groupController->create(new Tinebase_Model_Group([
                        'name' => $groupNameOrId,
                    ]));
                }
            }
            return static::$_resolvedGroups[$groupNameOrId] = $group->getId();
        }

        return static::$_resolvedGroups[$groupNameOrId];
    }
}
