<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold addressbook list data
 *
 * @package     Addressbook
 *
 * @property    string      $id
 * @property    string      $container_id
 * @property    string      $name
 * @property    string      $description
 * @property    array       $members
 * @property    array|Tinebase_Record_RecordSet $memberroles
 * @property    string      $email
 * @property    string      $type                 type of list
 * @property    string      $list_type
 */
class Addressbook_Model_List extends Tinebase_Record_Abstract
{
    const MODEL_NAME_PART = 'List';
    
    /**
     * list type: list (user defined lists)
     * 
     * @var string
     */
    const LISTTYPE_LIST = 'list';
    
    /**
     * list type: group (lists matching a system group)
     * 
     * @var string
     */
    const LISTTYPE_GROUP = 'group';

    /**
     * mailinglist xprops
     */
    const XPROP_SIEVE_ALLOW_EXTERNAL = 'sieveAllowExternal';
    const XPROP_SIEVE_ALLOW_ONLY_MEMBERS = 'sieveAllowOnlyMembers';
    const XPROP_SIEVE_FORWARD_ONLY_SYSTEM = 'sieveForwardOnlySystem';
    const XPROP_SIEVE_KEEP_COPY = 'sieveKeepCopy';
    const XPROP_USE_AS_MAILINGLIST = 'useAsMailinglist';
    const XPROP_SIEVE_REPLY_TO = 'sieveReplyTo';

    /**
     * external email user ids (for example in dovecot/postfix sql)
     */
    const XPROP_EMAIL_USERID_IMAP = 'emailUserIdImap';
    const XPROP_EMAIL_USERID_SMTP = 'emailUserIdSmtp';

    /**
     * name of fields which require manage accounts to be updated
     *
     * @var array list of fields which require manage accounts to be updated
     */
    protected static $_manageAccountsFields = array(
        'name',
        'description',
        'email',
        'account_only'
    );

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'Group', // gettext('GENDER_Group')
        'recordsName'       => 'Groups', // ngettext('Group', 'Groups', n)
        'hasRelations'      => true,
        'hasCustomFields'   => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'modlogActive'      => true,
        'hasAttachments'    => false,
        'createModule'      => true,
        self::HAS_XPROPS    => true,

        'containerProperty' => 'container_id',

        'containerName'     => 'Addressbook',
        'containersName'    => 'Addressbooks', // ngettext('Addressbook', 'Addressbooks', n)
        'containerUsesFilter' => true,

        'titleProperty'     => 'name',//array('%s - %s', array('number', 'title')),
        'appName'           => 'Addressbook',
        'modelName'         => 'List',
        'moduleName'        => 'Groups',  // ngettext('Group', 'Groups', n)

        'filterModel'       => array(
            'path'              => array(
                'filter'            => 'Tinebase_Model_Filter_Path',
                'label'             => null,
                'options'           => array()
            ),
            'showHidden'        => array(
                'filter'            => 'Addressbook_Model_ListHiddenFilter',
                'label'             => null,
                'options'           => array()
            ),
            'contact'           => array(
                'filter'            => 'Addressbook_Model_ListMemberFilter',
                'label'             => null,
                'options'           => array()
            ),
            'container_id'      => array(
                'filter'  => Tinebase_Model_Filter_Container::class,
                'options' => array('modelName' => Addressbook_Model_Contact::class),
            ),
            'name_email_query'       => [
                'filter'            => Tinebase_Model_Filter_Query::class,
                'title'             => 'Name/Email', // _('Name/Email')
                'options'           => [
                    'fields'            => [
                        'name',
                        'email',
                    ],
                    'modelName' => self::class,
                ],
            ],
            'email_query'           => [
                'filter'            => Tinebase_Model_Filter_Query::class,
                'title'             => 'Email', // _('Email')
                'options'           => [
                    'fields'            => [
                        'email',
                    ],
                    'modelName' => self::class,
                ],
            ],
        ),

        'fields'            => array(
            'name'              => array(
                'label'             => 'Name', //_('Name')
                'type'              => 'string',
                'queryFilter'       => true,
                'validators'        => array('presence' => 'required'),
            ),
            'description'       => array(
                'label'             => 'Description', //_('Description')
                'type'              => 'text',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter'       => true,
            ),
            'members'           => array(
                'label'             => 'Members', //_('Members')
                'type'              => 'FOO',
                'default'           => array(),
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::SHY           => true
            ),
            'email'             => array(
                'label'             => 'E-Mail', //_('E-Mail')
                'type'              => 'string',
                'queryFilter'       => true,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'type'              => array(
                'label'             => 'Type', //_('Type')
                'type'              => 'string',
                'default'           => self::LISTTYPE_LIST,
                'validators'        => array(
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    array('InArray', array(self::LISTTYPE_LIST, self::LISTTYPE_GROUP)),
                ),
                self::COPY_OMIT => true
            ),
            'list_type'         => array(
                'label'             => 'List type', //_('List type')
                'type'              => 'keyfield',
                'name'              => Addressbook_Config::LIST_TYPE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::COPY_OMIT => true
            ),
            'group_id'          => array(
                'label'             => null, // TODO fill this?
                'type'              => 'string',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::COPY_OMIT => true
            ),
            'account_only'          => array(
                'label'             => null, // TODO fill this?
                'type'              => 'boolean',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'virtual'           => true,
            ),
            'memberroles'       => array(
                'label'             => null, // TODO fill this?
                'type'              => 'virtual',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'paths'             => array(
                'label'             => null, // TODO fill this?
                'type'              => 'FOO',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
        ),
    );

    /**
     * get translated list type
     *
     * @return string
     */
    public function getType($locale = null)
    {
        $translation = Tinebase_Translation::getTranslation('Addressbook', $locale);
        switch ($this->type) {
            case self::LISTTYPE_LIST:
                return $translation->translate('Group');
            case self::LISTTYPE_GROUP:
                return $translation->translate('System Group');
            default:
                return '';
        }
    }

    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format:
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     *
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        Tinebase_Model_User::class => array(
            'created_by',
            'last_modified_by'
        )
    );

    /**
     * @return array
     */
    static public function getManageAccountFields()
    {
        return self::$_manageAccountsFields;
    }

    /**
     * converts a string or Addressbook_Model_List to a list id
     *
     * @param   string|Addressbook_Model_List  $_listId  the contact id to convert
     * 
     * @return  string
     * @throws  UnexpectedValueException  if no list id set 
     */
    static public function convertListIdToInt($_listId)
    {
        if ($_listId instanceof self) {
            if ($_listId->getId() == null) {
                throw new UnexpectedValueException('No identifier set.');
            }
            $id = (string) $_listId->getId();
        } else {
            $id = (string) $_listId;
        }
        
        if (empty($id)) {
            throw new UnexpectedValueException('Identifier can not be empty.');
        }
        
        return $id;
    }

    /**
     * returns an array containing the parent neighbours relation objects or record(s) (ids) in the key 'parents'
     * and containing the children neighbours in the key 'children'
     *
     * @return array
     */
    public function getPathNeighbours()
    {
        $result = parent::getPathNeighbours();

        $members = [];
        if (!empty($this->members)) {
            if ($this->members instanceof Tinebase_Record_RecordSet) {
                $tmp = $this->members;
            } else {
                $tmp = Addressbook_Controller_Contact::getInstance()->getMultiple($this->members, true);
            }
            foreach($tmp as $member) {
                $members[$member->getId()] = $member;
            }
        }

        if (!is_object($this->memberroles)) {
            $this->memberroles = Addressbook_Controller_List::getInstance()->getMemberRoles($this);
        }

        if ($this->memberroles->count() > 0) {

            $pathController = Tinebase_Record_Path::getInstance();
            /** @var Addressbook_Model_ListMemberRole $role */
            foreach($this->memberroles as $role)
            {
                if (isset($members[$role->contact_id])) {
                    unset($members[$role->contact_id]);
                }
                $pathController->addToRebuildQueue($role);
                $members[] = $role;
            }
        }

        $result['children'] = array_merge($result['children'], $members);

        return $result;
    }

    /**
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_AccessDenied
     */
    public function getListMembersWithFunctions()
    {
        // @todo huge crap messed up by currently broken resolving.
        if (is_string($this->members) || empty($this->members)) {
            $members = Addressbook_Controller_List::getInstance()->get($this->getId())->members;
        } else {
            $members = $this->members;
        }
        
        $roles = Addressbook_Controller_List::getInstance()->getMemberRolesBackend()->search(new Addressbook_Model_ListMemberRoleFilter([
            'list_id' => $this->getId()
        ]));
        
        $membersWithRoles = [];
        
        foreach($members as $memberId) {
            if (!($memberRole = $roles->filter('contact_id', $memberId)->getFirstRecord())) {
                $membersWithRoles[] = Addressbook_Controller_Contact::getInstance()->get($memberId)->getTitle();
                continue;
            }

            $membersWithRoles[] = \sprintf(
                '%s (%s)',
                Addressbook_Controller_Contact::getInstance()->get($memberId)->getTitle(),
                Addressbook_Controller_ListRole::getInstance()->get($memberRole->list_role_id)->getTitle()
            );
        }
        
        return implode(', ', $membersWithRoles);
    }
    
    /**
     * @return bool
     */
    public static function generatesPaths()
    {
        return true;
    }

    /**
     * get lists recipient token
     *
     * @return array
     */
    public function getRecipientTokens(): array
    {
        $listMemberEmails = [];

        // always get member contacts
        if (isset($this->members) && count($this->members) > 0) {
            try {
                $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($this->members);
                foreach ($contacts as $contact) {
                    $listMemberEmails = array_merge($listMemberEmails, $contact->getRecipientTokens(true));
                }
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' get members failed : ' . $e->getMessage());
            }
        }

        if (count($listMemberEmails) === 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " List : " . $this->name . " has no member emails found");
            }
            if (empty($this->email)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . " Skipping, no email addresses found in list ...");
                }
                return [];
            }
        }

        $useAsMailinglist = isset($this['xprops'][Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST])
            && $this['xprops'][Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST] == 1;

        return  [[
            "n_fileas" => $this->name ?? '',
            "name" => $this->name ?? '',
            "type" =>  $useAsMailinglist ? 'mailingList' : $this->type,
            "email" => $this->email ?? '',
            "email_type_field" =>  '',
            "contact_record" => $this->toArray(),
            "emails" => $listMemberEmails
        ]];
    }
}
