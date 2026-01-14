<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

use Jumbojett\OpenIDConnectClient;

/**
 * Json interface to Tinebase
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    public const REQUEST_TYPE = 'JSON-RPC';

    /**
     * the application name
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * All full configured models
     *
     * @var array
     */
    protected $_configuredModels = [
        'BLConfig',
        'ImportExportDefinition',
        'LogEntry',
        'Tree_Node',
        Tinebase_Model_NumberableConfig::MODEL_NAME_PART,
        Tinebase_Model_User::MODEL_NAME_PART,
        Tinebase_Model_MFA_HOTPUserConfig::MODEL_NAME_PART,
        Tinebase_Model_MFA_TOTPUserConfig::MODEL_NAME_PART,
        Tinebase_Model_MFA_UserConfig::MODEL_NAME_PART,
        Tinebase_Model_MFA_PinUserConfig::MODEL_NAME_PART,
        Tinebase_Model_MFA_SmsUserConfig::MODEL_NAME_PART,
        Tinebase_Model_MFA_WebAuthnUserConfig::MODEL_NAME_PART,
        Tinebase_Model_MFA_YubicoOTPUserConfig::MODEL_NAME_PART,
        Tinebase_Model_MunicipalityKey::MODEL_NAME_PART,
        Tinebase_Model_AuthToken::MODEL_NAME_PART,
        Tinebase_Model_DynamicRecordWrapper::MODEL_NAME_PART,
        Tinebase_Model_BankAccount::MODEL_NAME_PART,
        Tinebase_Model_BankHolidayCalendar::MODEL_NAME_PART,
        Tinebase_Model_BankHoliday::MODEL_NAME_PART,
        Tinebase_Model_CloudAccount::MODEL_NAME_PART,
        Tinebase_Model_CloudAccount_CalDAV::MODEL_NAME_PART,
        Tinebase_Model_EvaluationDimension::MODEL_NAME_PART,
        Tinebase_Model_EvaluationDimensionItem::MODEL_NAME_PART,
        Tinebase_Model_Tree_FlySystem::MODEL_NAME_PART,
        Tinebase_Model_Tree_FlySystem_AdapterConfig_Local::MODEL_NAME_PART,
        Tinebase_Model_Tree_FlySystem_AdapterConfig_WebDAV::MODEL_NAME_PART,
        Tinebase_Model_TwigTemplate::MODEL_NAME_PART,
    ];
    
    public function __construct()
    {
        if (!Tinebase_Config::getInstance()->featureEnabled(
            Tinebase_Config::FEATURE_COMMUNITY_IDENT_NR)
        ) {
            $this->_configuredModels = array_diff($this->_configuredModels, [
                Tinebase_Model_MunicipalityKey::MODEL_NAME_PART
            ]);
        }
    }

    /**
     * ping
     *
     * NOTE: auth & outdated client gets checked in server
     */
    public function ping()
    {
        return 'ack';
    }

    /**
     * get list of translated country names
     * 
     * Wrapper for {@see Tinebase_Core::getCountrylist}
     * 
     * @return array list of countrys
     */
    public function getCountryList($locale = null)
    {
        try {
            $result = Tinebase_Translation::getCountryList($locale ? new Zend_Locale($locale) : null);
        } catch (Symfony\Component\Intl\Exception\MissingResourceException $mre) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__
                    . ' Error: "' . $mre->getMessage()
                    . '" -> Switching to default locale');
            }
            $result = Tinebase_Translation::getCountryList();
        }
        return $result;
    }

    /**
     * get list of translated currency names
     **
     * @return array list of currencies
     */
    public function getCurrencyList()
    {
        return Tinebase_Translation::getCurrencyList();
    }
    
    /**
     * returns list of all available translations
     *
     * @return array list of all available translations
     */
    public function getAvailableTranslations()
    {
        $whitelistedLocales = Tinebase_Config::getInstance()->get(Tinebase_Config::AVAILABLE_LANGUAGES);
        $availableTranslations = Tinebase_Translation::getAvailableTranslations();
        foreach ($availableTranslations as $key => &$info) {
            unset($info['path']);
            if (! empty($whitelistedLocales) && ! in_array($info['locale'], $whitelistedLocales)) {
                unset($availableTranslations[$key]);
            }
        }

        return array(
            'results'    => array_values($availableTranslations),
            'totalcount' => count($availableTranslations)
        );
    }
    
    /**
     * sets locale
     *
     * @param  string $localeString
     * @param  bool   $saveaspreference
     * @param  bool   $setcookie
     * @return array
     */
    public function setLocale($localeString, $saveaspreference, $setcookie)
    {
        Tinebase_Core::setupUserLocale($localeString);
        
        if ($saveaspreference && is_object(Tinebase_Core::getUser())) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . " Saving locale: " . $localeString);
            Tinebase_Core::getPreference()->{Tinebase_Preference::LOCALE} = $localeString;
        }
        
        // save in cookie (expires in 365 days)
        if ($setcookie) {
            setcookie('TINE20LOCALE', $localeString, ['expires' => time()+60*60*24*365]);
        }
        
        return array(
            'success'      => TRUE
        );
    }
    
    /**
     * sets timezone
     *
     * @param  string $timezoneString
     * @param  bool   $saveaspreference
     * @return string
     */
    public function setTimezone($timezoneString, $saveaspreference)
    {
        $timezone = Tinebase_Core::setupUserTimezone($timezoneString, $saveaspreference);
        
        return $timezone;
    }
    
    /**
     * get users
     *
     * @param string $filter
     * @param string $sort
     * @param string $dir
     * @param int $start
     * @param int $limit
     * @return array with results array & totalcount (int)
     */
    public function getUsers($filter, $sort, $dir, $start, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Tinebase_User::getInstance()->getUsers($filter, $sort, $dir, $start, $limit)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                //$result['totalcount'] = $backend->getCountByAddressbookId($addressbookId, $filter);
            }
        }
        
        return $result;
    }
    
    /**
     * Search for roles
     *
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchRoles(array $filter, array $paging)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $filter = new Tinebase_Model_RoleFilter($filter);
        
        $paging['sort'] ??= 'name';
        $paging['dir'] ??= 'ASC';
        
        $result['results'] = Tinebase_Acl_Roles::getInstance()->searchRoles($filter, new Tinebase_Model_Pagination($paging))->toArray();
        $result['totalcount'] = Tinebase_Acl_Roles::getInstance()->searchCount($filter);
        
        return $result;
    }
    
    /**
     * change password of user
     *
     * @param  string $oldPassword the old password
     * @param  string $newPassword the new password
     * @return array
     */
    public function changePassword($oldPassword, $newPassword)
    {
        try {
            Tinebase_Controller::getInstance()->changePassword((string) $oldPassword, (string) $newPassword);
        } catch (Tinebase_Exception $e) {
            return [
                'success'      => false,
                'errorMessage' => $e->getMessage()
            ];
        }

        return [
            'success' => true,
        ];
    }

    /**
     * clears state
     *
     * @param  string $name
     * @return array
     */
    public function clearState($name)
    {
        Tinebase_State::getInstance()->clearState($name);

        return ['success' => true];
    }

    public function clearAllStates()
    {
        Tinebase_State::getInstance()->clearAllStates();
    }

    /**
     * retuns all states
     *
     * @return array of name => value
     */
    public function loadState()
    {
        return Tinebase_State::getInstance()->loadStateInfo();
    }
    
    /**
     * set state
     *
     * @param string $name
     * @param string|array $value
     * @return bool[]
     * @throws Exception
     */
    public function setState(string $name, $value): array
    {
        if (is_array($value)) {
            $value = json_encode($value);
            if ($value === false) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                    __METHOD__ . '::' . __LINE__ . ' Could not json_encode value');
                return ['success' => false];
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . " Setting state: {$name} -> {$value}");

        try {
            Tinebase_State::getInstance()->setState($name, $value);
            $success = true;
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . ' Failed to set state: ' . $tead->getMessage());
            $success = false;
        }

        return ['success' => $success];
    }
    
    /**
     * adds a new personal tag
     *
     * @param  array $tag
     * @return array
     */
    public function saveTag($tag)
    {
        $inTag = new Tinebase_Model_Tag($tag);
        
        if (strlen((string)$inTag->getId()) < 40) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' creating tag: ' . print_r($inTag->toArray(), true));
            $outTag = Tinebase_Tags::getInstance()->createTag($inTag);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' updating tag: ' .print_r($inTag->toArray(), true));
            $outTag = Tinebase_Tags::getInstance()->updateTag($inTag);
        }
        
        return $outTag->toArray();
    }

    /**
     * @param string $commencement
     * @param string $termOfContractInMonths
     * @param string $automaticContractExtensionInMonths
     * @param integer $cancellationPeriodInMonths
     * @param Tinebase_DateTime $today
     * @return array
     */
    public function getTerminationDeadline(
        $commencement,
        $termOfContractInMonths,
        $automaticContractExtensionInMonths,
        $cancellationPeriodInMonths,
        $today = null
    ) {
        date_default_timezone_set(Tinebase_Core::getUserTimezone());
        $commencement = new Tinebase_DateTime($commencement);
        $commencement->setTimezone('UTC');
        date_default_timezone_set('UTC');

        $deadline = Tinebase_Helper_Algorithm_TerminationDeadline::getInstance()->getTerminationDeadline(
            $commencement,
            $termOfContractInMonths,
            $automaticContractExtensionInMonths,
            $cancellationPeriodInMonths,
            $today
        );
        $deadline->setTimezone(Tinebase_Core::getUserTimezone());
        $deadline->setTime(0, 0);

        return [
            'terminationDeadline' => $deadline->format(Tinebase_Record_Abstract::ISO8601LONG)
        ];
    }

    /**
     * Used for updating multiple records
     * 
     * @param string $appName
     * @param string $modelName
     * @param array $changes
     * @param array $filter
     */
    public function updateMultipleRecords($appName, $modelName, $changes, $filter)
    {
        // increase execution time to 30 minutes
        Tinebase_Core::setExecutionLifeTime(1800);
        
        $filterModel = $appName . '_Model_' . $modelName . 'Filter';
        $data = array();
        foreach ($changes as $f) {
            $data[preg_replace('/^customfield_/','#', $f['name'])] = $f['value'];
        }
        
        return $this->_updateMultiple($filter, $data, Tinebase_Core::getApplicationInstance($appName, $modelName), $filterModel);
    }

    public function getTwigTemplate(): void
    {
        throw new Tinebase_Exception_NotImplemented('we use searchTwigTemplates with path equals X');
    }

    public function searchTwigTemplates(array $filter, array $paging): array
    {
        $filterModel = Tinebase_Model_TwigTemplate::class . 'Filter';
        $filter = $this->_decodeFilter($filter, $filterModel);

        $pathFilter = $filter->findFilterWithoutOr(Tinebase_Model_TwigTemplate::FLD_PATH);
        $appFilter = $filter->findFilterWithoutOr(Tinebase_Model_TwigTemplate::FLD_APPLICATION_ID);

        if (false === $pathFilter || false === $appFilter) {
            throw new Tinebase_Exception_SystemGeneric('there may only be one path and one application filter, which must not be ORd');
        }

        if (null !== $appFilter) {
            $apps = Tinebase_Application::getInstance()->_getBackend()->search(new Tinebase_Model_ApplicationFilter([
                ['field' => 'id', 'operator' => $appFilter->getOperator(), 'value' => $appFilter->getValue()],
            ]))->filter('status', Tinebase_Application::ENABLED);
        } else {
            $apps = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        }
        $apps = $apps->filter(fn($app) => Tinebase_Core::getUser()->hasRight($app, Tinebase_Acl_Rights_Abstract::TWIG));

        $pathFilterValue = $pathFilter?->getValue();
        $pathFilter = match($pathFilter?->getOperator()) {
            null => fn() => true,
            'equals' => fn($path) => $path === $pathFilterValue,
            'contains' => fn($path) => false !== strpos($path, $pathFilterValue),
            default => throw new Tinebase_Exception_UnexpectedValue('path filter operator ' . $pathFilter?->getOperator() . ' not supported'),
        };

        $result = $this->_search($filter, [], Tinebase_Controller_TwigTemplate::getInstance(), $filterModel);

        $foundPaths = [];
        $tineRoot = realpath(dirname(__DIR__, 2)) . '/';
        foreach ($result['results'] as &$twigTmpl) {
            $twigTmpl[Tinebase_Model_TwigTemplate::FLD_IS_ORIGINAL] = false;
            $templatePath = realpath($tineRoot . $twigTmpl[Tinebase_Model_TwigTemplate::FLD_PATH]);
            if ($templatePath && !str_starts_with($templatePath, $tineRoot)) {
                Tinebase_Exception::log(new Tinebase_Exception('potentially malicious twig template path: ' . $twigTmpl[Tinebase_Model_TwigTemplate::FLD_PATH]));
                $twigTmpl[Tinebase_Model_TwigTemplate::FLD_HAS_ORIGINAL] = false;
            } elseif ($templatePath) {
                $twigTmpl[Tinebase_Model_TwigTemplate::FLD_HAS_ORIGINAL] = true;
                $twigTmpl[Tinebase_Model_TwigTemplate::FLD_ORIGINAL_TWIG] = file_get_contents($templatePath);
            } else {
                $twigTmpl[Tinebase_Model_TwigTemplate::FLD_HAS_ORIGINAL] = false;
            }
            $foundPaths[$twigTmpl[Tinebase_Model_TwigTemplate::FLD_PATH]] = true;
        }

        $iterateDirsFun = function(string $path, string $appId, Closure $fun) use($foundPaths, &$result, $tineRoot, $pathFilter) {
            if (!is_dir($tineRoot . $path)) return;
            foreach (scandir($tineRoot . $path) as $dirItem) {
                if ('.' === $dirItem || '..' === $dirItem) continue;
                $currentPath = $path . '/' . $dirItem;
                if (is_dir($tineRoot . $currentPath)) {
                    $fun($currentPath, $appId, $fun);
                } elseif (!($foundPaths[$currentPath] ?? false) && str_ends_with($currentPath, '.twig') && $pathFilter($currentPath)) {
                    $twigTmpl = file_get_contents($tineRoot . $currentPath);
                    $twigTmpl = new Tinebase_Model_TwigTemplate([
                        Tinebase_Model_TwigTemplate::FLD_APPLICATION_ID => $appId,
                        Tinebase_Model_TwigTemplate::FLD_PATH => $currentPath,
                        Tinebase_Model_TwigTemplate::FLD_TWIG_TEMPLATE => $twigTmpl,
                        Tinebase_Model_TwigTemplate::FLD_IS_ORIGINAL => true,
                        Tinebase_Model_TwigTemplate::FLD_HAS_ORIGINAL => true,
                    ]);
                    $result['results'][] = $twigTmpl->toArray();
                }
            }
        };

        foreach ($apps as $app) {
            $iterateDirsFun($app->name . '/views', $app->getId(), $iterateDirsFun);
        }

        $result['totalcount'] = count($result['results']);

        if ($result['totalcount'] > 0 && ($paging['sort'] ?? false) && is_string($paging['sort']) && isset($result['results'][0][$paging['sort']])) {
            $dir = ($paging['dir'] ?? 'ASC') === 'ASC';
            $sort = $paging['sort'];
            usort($result['results'], fn($a, $b) => $dir ? strcmp((string)$a[$sort], (string)$b[$sort]) : strcmp((string)$b[$sort], (string)$a[$sort]));
        }

        return $result;
    }

    /**
     * search tags
     *
     * @param  array $filter filter array
     * @param  array $paging pagination info
     * @return array
     */
    public function searchTags($filter, $paging)
    {
        $filter = new Tinebase_Model_TagFilter($filter);
        $paging = new Tinebase_Model_Pagination($paging);
        
        return array(
            'results'    => Tinebase_Tags::getInstance()->searchTags($filter, $paging)->toArray(),
            // TODO we normally use 'totalcount' (all lower case) - this should be streamlined
            'totalCount' => Tinebase_Tags::getInstance()->getSearchTagsCount($filter)
        );
    }
    
    /**
    * search tags by foreign filter
    *
    * @param  array $filterData
    * @param  string $filterName
    * @return array
    */
    public function searchTagsByForeignFilter($filterData, $filterName)
    {
        $filter = $this->_getFilterGroup($filterData, $filterName);
        
        $result = Tinebase_Tags::getInstance()->searchTagsByForeignFilter($filter)->toArray();
        return array(
            'results'    => $result,
            'totalCount' => count($result)
        );
    }
    
    /**
     * get filter group defined by filterName and filterData
     *
     * @param array $_filterData
     * @param string $_filterName
     * @return Tinebase_Model_Filter_FilterGroup
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _getFilterGroup($_filterData, $_filterName)
    {
        // NOTE: this function makes a new instance of a class whose name is given by user input.
        //       we need to do some sanitising first!
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$appName, $modelString, $filterGroupName] = explode('_', $_filterName);
        if ($modelString !== 'Model') {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' spoofing attempt detected, affected account: ' . print_r(Tinebase_Core::getUser()->toArray(), TRUE));
            die('go away!');
        }

        if (! Tinebase_Core::getUser()->hasRight($appName, Tinebase_Acl_Rights_Abstract::RUN)) {
            throw new Tinebase_Exception_AccessDenied('No right to access application ' . $appName);
        }

        $filterGroup = Tinebase_Model_Filter_FilterGroup::getFilterForModel($_filterName, $_filterData);

        return $filterGroup;
    }
    
    /**
     * attach tag to multiple records identified by a filter
     *
     * @param array  $filterData
     * @param string $filterName
     * @param mixed  $tag       string|array existing and non-existing tag
     * @return array
     */
    public function attachTagToMultipleRecords($filterData, $filterName, mixed $tag)
    {
        $this->_longRunningRequest();
        $filter = $this->_getFilterGroup($filterData, $filterName);
        
        Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $tag);
        return array('success' => true);
    }
    
    /**
     * attach multiple tags to multiple records identified by a filter
     *
     * @param array  $filterData
     * @param string $filterName
     * @param mixed  $tags         array of existing and non-existing tags
     * @return void
     */
    public function attachMultipleTagsToMultipleRecords($filterData, $filterName, mixed $tags)
    {
        $this->_longRunningRequest();
        $filter = $this->_getFilterGroup($filterData, $filterName);

        foreach ($tags as $tag) {
            Tinebase_Tags::getInstance()->attachTagToMultipleRecords(clone $filter, $tag);
        }

        return array('success' => true);
    }

    /**
     * detach tags to multiple records identified by a filter
     *
     * @param array  $filterData
     * @param string $filterName
     * @param mixed  $tag       string|array existing and non-existing tag
     * @return array
     */
    public function detachTagsFromMultipleRecords($filterData, $filterName, mixed $tag)
    {
        $this->_longRunningRequest();
        $filter = $this->_getFilterGroup($filterData, $filterName);
        
        Tinebase_Tags::getInstance()->detachTagsFromMultipleRecords($filter, $tag);
        return array('success' => true);
    }
    
    /**
     * search / get notes
     * - used by activities grid
     *
     * @param  array $filter filter array
     * @param  array $paging pagination info
     * @return array
     */
    public function searchNotes($filter, $paging)
    {
        $filter = new Tinebase_Model_NoteFilter($filter);
        $paging = new Tinebase_Model_Pagination($paging);
        
        $records = Tinebase_Notes::getInstance()->searchNotes($filter, $paging, /* ignoreACL = */ false);
        $result = $this->_multipleRecordsToJson($records);
        
        return array(
            'results'       => $result,
            'totalcount'    => Tinebase_Notes::getInstance()->searchNotesCount($filter, /* ignoreACL = */ false)
        );
    }
    
    /**
     * deletes tags identified by an array of identifiers
     *
     * @param  array $ids
     * @return array
     */
    public function deleteTags($ids)
    {
        Tinebase_Tags::getInstance()->deleteTags($ids);
        return array('success' => true);
    }

    /**
     * authenticate user by username and password
     *
     * @param  string $username the username
     * @param  string $password the password
     * @return array
     */
    public function authenticate($username, $password)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($username, $password);
        
        if ($authResult->isValid()) {
            $response = array(
                'status'    => 'success',
                'msg'       => 'authentication succeed',
                //'loginUrl'  => 'someurl',
            );
        } else {
            $response = array(
                'status'    => 'fail',
                'msg'       => 'authentication failed',
            );
        }
        
        return $response;
    }

    /**
     * login user with given username and password
     *
     * @param ?string $username the username
     * @param ?string $password the password
     * @param ?string $MFAUserConfigId config for mfa device to use
     * @param ?string $MFAPassword otp from mfa device
     * @return array
     */
    public function login(?string $username = null, ?string $password = null, ?string $MFAUserConfigId = null, ?string $MFAPassword = null): array
    {
        try {
            Tinebase_Core::startCoreSession();
        } catch (Zend_Session_Exception $zse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Could not start session: ' . $zse->getMessage());
            }
            return array(
                'success'      => false,
                'errorMessage' => "Could not start session!",
            );
        }

        if (empty($username)) {
            try {
                if (Tinebase_Application::getInstance()->isInstalled('SSO') &&
                    ($idpId = ($this->_getRequestContextHeaders()['idpid'] ?? null)) &&
                    ($idp = SSO_Controller_ExternalIdp::getInstance()->get($idpId)) &&
                    $idp->{SSO_Model_ExternalIdp::FLD_SHOW_AS_LOGIN_OPTION}) {
                    SSO_Controller::startExternalIdpAuthProcess($idp);
                }
            } catch (Tinebase_Exception_NotFound) {
            }
            if ($MFAPassword) {
                if (null !== $MFAUserConfigId ||
                        null !== ($MFAUserConfigId = Tinebase_Config::getInstance()->{Tinebase_Config::MFA}->records?->find(Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS, Tinebase_Auth_MFA_WebAuthnAdapter::class)?->getId())) {
                    try {
                        $user = Tinebase_Auth_Webauthn::webAuthnAuthenticate(Tinebase_Auth_MFA::getInstance($MFAUserConfigId)->getAdapter()->getConfig(), $MFAPassword);
                    } catch (Throwable) {
                        return $this->_getLoginFailedResponse();
                    }
                    $areaLock = Tinebase_AreaLock::getInstance();
                    if ($areaLock->hasLock(Tinebase_Model_AreaLockConfig::AREA_LOGIN)) {
                        $areaLock->forceUnlock(Tinebase_Model_AreaLockConfig::AREA_LOGIN);
                    }
                    if (Tinebase_Controller::getInstance()->processLoginAuthResult(
                                loginName: $user->accountLoginName,
                                request: Tinebase_Core::get(Tinebase_Core::REQUEST),
                                clientIdString: self::REQUEST_TYPE
                            )) {
                        return $this->_getLoginSuccessResponse($username);
                    }
                }
            }
            return $this->_getLoginFailedResponse();
        }

        $user = null;
        try {
            $user = Tinebase_User::getInstance()->getFullUserByLoginName($username);
        } catch(Tinebase_Exception_NotFound) {}
        if (null !== $user && $user->openid && ($pos = strpos($user->openid, ':')) && Tinebase_Application::getInstance()->isInstalled('SSO')) {
            try {
                if (!SSO_Controller_ExternalIdp::getInstance()->get(substr($user->openid, 0, $pos))->{SSO_Model_ExternalIdp::FLD_ALLOW_LOCAL_LOGIN} &&
                        !SSO_Controller::passwordLessLogin($username)) {
                    return $this->_getLoginFailedResponse();
                }
            } catch(Tinebase_Exception_NotFound) {}
        }

        if (empty($password)) {
            if (Tinebase_Application::getInstance()->isInstalled('SSO')
                && SSO_Controller::passwordLessLogin($username)) {
                return $this->_getLoginSuccessResponse($username);
            }
            if (null !== $user) {
                if (Tinebase_Controller::getInstance()->passwordLessLogin($user, $MFAUserConfigId, $MFAPassword, self::REQUEST_TYPE)) {
                    return $this->_getLoginSuccessResponse($username);
                }
            }
            throw new Tinebase_Exception_Auth_PwdRequired();
        }

        Tinebase_Controller::getInstance()->forceUnlockLoginArea();
        Tinebase_Controller::getInstance()->setRequestContext(array(
            'MFAPassword' => $MFAPassword,
            'MFAId'       => $MFAUserConfigId
        ));

        // try to login user
        $success = Tinebase_Controller::getInstance()->login(
            $username,
            $password,
            Tinebase_Core::get(Tinebase_Core::REQUEST),
            self::REQUEST_TYPE
        );
        
        if ($success === true) {
            return $this->_getLoginSuccessResponse($username);
        } else {
            return $this->_getLoginFailedResponse();
        }
    }
    
    /**
     * create login response
     *
     * @param string $username
     * @return array
     */
    protected function _getLoginSuccessResponse($username)
    {
        $response = array(
            'success' => true,
            'account' => Tinebase_Core::getUser()->getPublicUser()->toArray(),
            'sessionId' => Tinebase_Core::getSessionId(),
            'jsonKey' => Tinebase_Core::get('jsonKey'),
            'welcomeMessage' => "Welcome to Tine 2.0!"
        );

        try {
            $response['assetHash'] = Tinebase_Frontend_Http_SinglePageApplication::getAssetHash();
        } catch (Exception) {}
        
        if (Tinebase_Core::get(Tinebase_Core::SESSION)->encourage_mfa) {
            $response['encourage_mfa'] = true;
        }
        if (Tinebase_Core::get(Tinebase_Core::SESSION)->mustChangePassword) {
            $response['mustChangePassword'] = Tinebase_Core::get(Tinebase_Core::SESSION)->mustChangePassword;
        }
        if (Tinebase_Session::getSessionNamespace()->login) {
            if (Tinebase_Session::getSessionNamespace()->login['afterLoginRedirect'] ?? false) {
                $response['initialData'] = [
                    'afterLoginRedirect' => Tinebase_Session::getSessionNamespace()->login['afterLoginRedirect'],
                ];
            }
            unset(Tinebase_Session::getSessionNamespace()->login);
        }

        if (!headers_sent()) {
            $cookieOptions = Tinebase_Helper::getDefaultCookieSettings();
            if (Tinebase_Config::getInstance()->get(Tinebase_Config::REUSEUSERNAME_SAVEUSERNAME, 0)) {
                // save in cookie (expires in 2 weeks)
                $cookieOptions['expires'] = time() + 60 * 60 * 24 * 14;
                setcookie('TINE20LASTUSERID', $username, $cookieOptions);
            } else {
                setcookie('TINE20LASTUSERID', '', $cookieOptions);
            }

            $this->_setCredentialCacheCookie();
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Could not set cookies - headers already sent');
        }

        return $response;
    }
    
    /**
     *
     * @return array
     */
    public function _getLoginFailedResponse()
    {
        $response = array(
            'success'      => false,
            'errorMessage' => "Wrong username or password!",
        );
        
        Tinebase_Auth_CredentialCache::getInstance()->getCacheAdapter()->resetCache();
        
        return $response;
    }

    /**
     * set credential cache cookie
     *
     * @return boolean
     */
    protected function _setCredentialCacheCookie()
    {
        if (!Tinebase_Core::isRegistered(Tinebase_Core::USERCREDENTIALCACHE)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Something went wrong with the CredentialCache / no CC registered.');
            return false;
        }
        
        Tinebase_Auth_CredentialCache::getInstance()->getCacheAdapter()->setCache(Tinebase_Core::getUserCredentialCache());
        
        return true;
    }

    /**
     * update user credential cache
     *
     * - fires Tinebase_Event_User_ChangeCredentialCache
     *
     * @param string $password
     * @return array
     */
    public function updateCredentialCache($password)
    {
        $oldCredentialCache = Tinebase_Core::getUserCredentialCache();
        $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials(Tinebase_Core::getUser()->accountLoginName, $password);
        Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);
        
        $success = $this->_setCredentialCacheCookie();
        
        if ($success) {
            // close session to allow other requests
            Tinebase_Session::writeClose(true);
            $event = new Tinebase_Event_User_ChangeCredentialCache($oldCredentialCache);
            Tinebase_Event::fireEvent($event);
        }
        
        return array(
            'success'      => $success
        );
    }

    /**
     * destroy session
     *
     * @return array
     */
    public function logout()
    {
        $result = array(
            'success'=> true,
        );

        if (Tinebase_Application::getInstance()->isInstalled('SSO')
            && $data = SSO_Controller::logoutHandler()) {
            $result = array_merge($result, $data);
        }

        Tinebase_Controller::getInstance()->logout();
        
        Tinebase_Auth_CredentialCache::getInstance()->getCacheAdapter()->resetCache();
        
        if (Tinebase_Session::isStarted()) {
            Tinebase_Session::destroyAndRemoveCookie();
        }
        
        return $result;
    }
    
    /**
     * Returns registry data of tinebase.
     * @see Tinebase_Application_Json_Abstract
     *
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $registryData = Tinebase_Core::getCoreRegistryData();
        
        if (Tinebase_Core::isRegistered(Tinebase_Core::USER)) {
            if (Tinebase_AreaLock::getInstance()->hasLock(Tinebase_Model_AreaLockConfig::AREA_LOGIN) &&
                    Tinebase_AreaLock::getInstance()->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN)) {
                $areaConfig = Tinebase_AreaLock::getInstance()->getLastAuthFailedAreaConfig();
                $e = new Tinebase_Exception_AreaLocked('mfa required');
                $e->setArea($areaConfig->{Tinebase_Model_AreaLockConfig::FLD_AREA_NAME});
                $e->setMFAUserConfigs($areaConfig->getUserMFAIntersection(Tinebase_Core::getUser()));
                $e->setUser(Tinebase_Core::getUser());
                $registryData['areaLockedException'] = $e->toArray();
            } else {
                $userRegistryData = $this->_getUserRegistryData();
                $registryData += $userRegistryData;
            }
        }
        
        $importExportContainer = Tinebase_Config::getInstance()->get(Tinebase_Config::IMPORT_EXPORT_DEFAULT_CONTAINER);
        try {
            $registryData['defaultImportExportContainer'] = $importExportContainer ? Tinebase_Container::getInstance()->get($importExportContainer)->toArray() : null;
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . ' Could not get default export container: ' . $tenf->getMessage());
            $registryData['defaultImportExportContainer'] = null;
        }
        
        return $registryData;
    }
    
    /**
     * get user registry
     *
     * @return array
     * @throws Addressbook_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getUserRegistryData()
    {
        $user = Tinebase_Core::getUser();
        $userContactArray = array();
        if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
            try {
                $userContactArray = Addressbook_Controller_Contact::getInstance()->getContactByUserId($user->getId(), TRUE)->toArray();
            } catch (Addressbook_Exception_NotFound) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) /** @noinspection PhpUndefinedMethodInspection */
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' User not found in Addressbook: ' . $user->accountDisplayName);
            }
        }

        try {
            $persistentFilters = Tinebase_Frontend_Json_PersistentFilter::getAllPersistentFilters();
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . " Failed to fetch persistent filters. Exception:" . $tenf);
            $persistentFilters = array();
        } catch (Throwable $t) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . " Failed to fetch persistent filters. Error:" . $t);
            $persistentFilters = array();
        }

        $manageSmtpEmailUser = Tinebase_EmailUser::manages(Tinebase_Config::SMTP);
        $manageImapEmailUser = Tinebase_EmailUser::manages(Tinebase_Config::IMAP);

        $smtpConfig = $manageSmtpEmailUser
            ? Tinebase_EmailUser::getConfig(Tinebase_Config::SMTP, true)
            : [];
        $smsAdapterConfig = Tinebase_Config::getInstance()->{Tinebase_Config::SMS}->{Tinebase_Config::SMS_ADAPTERS}->{Tinebase_Model_Sms_AdapterConfigs::FLD_ADAPTER_CONFIGS} ?? [];
        if ($smsAdapterConfig instanceof Tinebase_Record_RecordSet) {
            $smsAdapterConfig = $smsAdapterConfig->toArray();
        }
        // be license class for setting some license registry data
        $license = Tinebase_License::getInstance();

        $userRegistryData = array(
            'accountBackend' => Tinebase_User::getConfiguredBackend(),
            'areaLocks' => $this->_multipleRecordsToJson(Tinebase_AreaLock::getInstance()->getAllStates()),
            'timeZone' => Tinebase_Core::getUserTimezone(),
            'currentAccount' => $user->toArray(),
            'userContact' => $userContactArray,
            'jsonKey' => Tinebase_Core::get('jsonKey'),
            'userApplications' => $user->getApplications()->toArray(),
            'manageImapEmailUser' => $manageImapEmailUser,
            'manageSmtpEmailUser' => $manageSmtpEmailUser,
            'mustchangepw' => $user->mustChangePassword(),
            'advancedSearch' => Tinebase_Core::getPreference()->getValue(Tinebase_Preference::ADVANCED_SEARCH, 0),
            'persistentFilters' => $persistentFilters,
            'userAccountChanged' => Tinebase_Controller::getInstance()->userAccountChanged(),
            'sessionLifeTime' => Tinebase_Session_Abstract::getSessionLifetime(),
            'licenseExpiredSince'=> $license->getLicenseExpiredSince(),
            'licenseExpire' => $license->getLicenseExpireEstimate(),
            'primarydomain' => $smtpConfig['primarydomain'] ?? '',
            'secondarydomains' => $smtpConfig['secondarydomains'] ?? '',
            'additionalexternaldomains' => $smtpConfig['additionalexternaldomains'] ?? '',
            'allowAnyExternalDomains'   => $smtpConfig['allowAnyExternalDomains'] ?? false,
            'smtpAliasesDispatchFlag' => Tinebase_EmailUser::smtpAliasesDispatchFlag(),
            'hasSmsAdapters'   => count($smsAdapterConfig) > 0,
        );

        if (Tinebase_Core::get(Tinebase_Core::SESSION)->encourage_mfa) {
            $userRegistryData['encourage_mfa'] = true;
        }
        if (Tinebase_Core::get(Tinebase_Core::SESSION)->{SSO_Model_ExternalIdp::SESSION_KEY}) {
            $userRegistryData[SSO_Model_ExternalIdp::SESSION_KEY] = Tinebase_Core::get(Tinebase_Core::SESSION)->{SSO_Model_ExternalIdp::SESSION_KEY};
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' User registry: ' . print_r($userRegistryData, TRUE));

        return $userRegistryData;
    }

    /**
     * Returns registry data of all applications current user has access to
     * @see Tinebase_Application_Json_Abstract
     *
     * @return mixed array 'variable name' => 'data'
     * @throws Tinebase_Exception_AccessDenied
     */
    public function getAllRegistryData()
    {
        $registryData = array();
        
        if (Tinebase_Core::getUser() && (!Tinebase_AreaLock::getInstance()->hasLock(Tinebase_Model_AreaLockConfig::AREA_LOGIN) ||
                !Tinebase_AreaLock::getInstance()->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN))) {
            $userApplications = Tinebase_Core::getUser()->getApplications(/* $_anyRight */ TRUE);
            $clientConfig = Tinebase_Config::getInstance()->getClientRegistryConfig();
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                /** @noinspection PhpUndefinedFieldInspection */
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
               . ' User applications to fetch registry for: [' . implode(',', $userApplications->name) . ']');

            /** @noinspection PhpUndefinedFieldInspection */
            if (! in_array('Tinebase', $userApplications->name)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' User has no permissions to run Tinebase.');
                $this->logout();
                throw new Tinebase_Exception_AccessDenied('User has no permissions to run Tinebase');
            }

            $allImportDefinitions = $this->_getImportDefinitions();

            foreach ($userApplications as $application) {
                $appRegistry = $this->_getAppRegistry($application, $clientConfig, $allImportDefinitions);

                $this->_logRegistrySize($appRegistry, $application->name);

                $registryData[$application->name] = $appRegistry;
            }
        } else {
            $registryData['Tinebase'] = $this->getRegistryData();
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Total registry size: ' . strlen(json_encode($registryData)));
        }

        return $registryData;
    }

    protected function _logRegistrySize($appRegistry, $appName)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            $total = 0;
            foreach ($appRegistry as $key => $value) {
                $size = strlen(json_encode($value));
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' Size of registry key ' . $key . ': ' . $size);
                $total += $size;
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' App ' . $appName . ' total size: ' . $total);
        }

    }

    /**
     * @param Tinebase_Model_Application $application
     * @param Tinebase_Config_Struct $clientConfig
     * @param Tinebase_Record_RecordSet $allImportDefinitions
     * @return array
     */
    protected function _getAppRegistry($application, $clientConfig, $allImportDefinitions)
    {
        $appRegistry = array();
        try {
            $appRegistry['rights'] = Tinebase_Core::getUser()->getRights($application->name);
        } catch (Tinebase_Exception $te) {
            // no rights -> continue + skip app
            Tinebase_Exception::log($te);
            return [];
        }
        $appRegistry['allrights'] = Tinebase_Application::getInstance()->getAllRights($application->getId());
        $appRegistry['config'] = isset($clientConfig[$application->name])
            ? $clientConfig[$application->name]->toArray()
            : array();

        // @todo do this for all apps at once (see import definitions)
        try {
            $exportDefinitions = Tinebase_ImportExportDefinition::getInstance()->getExportDefinitionsForApplication($application);
        } catch (Tinebase_Exception_Backend $teb) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Do not add import/export definition registry data. Exception: ' . $teb);
            $exportDefinitions = new Tinebase_Record_RecordSet(Tinebase_Model_ImportExportDefinition::class);
        }
        $definitionConverter = new Tinebase_Convert_ImportExportDefinition_Json();
        $appRegistry['exportDefinitions'] = array(
            'results'               => $definitionConverter->fromTine20RecordSet($exportDefinitions),
            'totalcount'            => count($exportDefinitions),
        );

        $customfields = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication($application);
        Tinebase_CustomField::getInstance()->resolveConfigGrants($customfields);
        $appRegistry['customfields'] = $customfields->toArray();

        try {
            $prefRegistry = $this->_getAppPreferencesForRegistry($application);
            $appRegistry = array_merge_recursive($appRegistry, $prefRegistry);
            $customAppRegistry = $this->_getCustomAppRegistry($application);
            if (empty($customAppRegistry)) {
                // TODO always get this from app controller (and remove from _getCustomAppRegistry)
                $appController = Tinebase_Core::getApplicationInstance($application->name);
                $models = $appController->getModels();
                $appRegistry['models'] = Tinebase_ModelConfiguration::getFrontendConfigForModels($models);
            } else {
                $appRegistry = array_merge_recursive($appRegistry, $customAppRegistry);
            }
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Do not add app registry data. Exception message: ' . $tead->getMessage());
        }

        if (! isset($appRegistry['importDefinitions'])) {
            $appRegistry = array_merge($appRegistry, $this->_getImportDefinitionRegistryData($allImportDefinitions, $application));
        }

        return $appRegistry;
    }

    /**
     * get app preferences for registry
     *
     * @param Tinebase_Model_Application $application
     * @return array
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getAppPreferencesForRegistry(Tinebase_Model_Application $application)
    {
        $registryData = array();
        $appPrefs = Tinebase_Core::getPreference($application->name);
        if ($appPrefs !== NULL) {
            $allPrefs = $appPrefs->getAllApplicationPreferences();
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' ' . print_r($allPrefs, TRUE));

            foreach ($allPrefs as $pref) {
                try {
                    $registryData['preferences'][$pref] = $appPrefs->{$pref};
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not get ' . $pref . '  preference: ' . $e);
                }
            }
        }

        return $registryData;
    }

    /**
     * get registry data from application frontend json class
     *
     * @param Tinebase_Model_Application $application
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getCustomAppRegistry(Tinebase_Model_Application $application)
    {
        $jsonAppName = $application->name . '_Frontend_Json';
        if (! class_exists($jsonAppName)) {
            return array();
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Getting registry data for app ' . $application->name);
        }

        try {
            $applicationJson = new $jsonAppName();
            $registryData = $applicationJson->getRegistryData();

        } catch (Exception $e) {
            if (!$e instanceof Tinebase_Exception_AccessDenied) {
                Tinebase_Exception::log($e);
            }
            return [];
        }

        // TODO get this from app controller / modelconfig
        foreach ($applicationJson->getRelatableModels() as $relModel) {
            $registryData['relatableModels'][] = $relModel;
        }
        $registryData['models'] = $applicationJson->getModelsConfiguration();

        return $registryData;
    }
    
    /**
     * search / get custom field values
     *
     * @param  array $filter filter array
     * @param  array $paging pagination info
     * @return array
     */
    public function searchCustomFieldValues($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Tinebase_CustomField::getInstance(), 'Tinebase_Model_CustomField_ValueFilter');
        return $result;
    }
    
    /************************ preferences functions ***************************/
    
    /**
     * search preferences
     *
     * @param  string $applicationName
     * @param  array  $filter json encoded
     * @return array
     */
    public function searchPreferencesForApplication($applicationName, $filter)
    {
        $decodedFilter = $this->_prepareParameter($filter);
        
        $filter = new Tinebase_Model_PreferenceFilter();
        if (! empty($decodedFilter)) {
            $filter->setFromArrayInUsersTimezone($decodedFilter);
        }
        $appId = Tinebase_Application::getInstance()->getApplicationByName($applicationName)->getId();
        $filter->addFilter($filter->createFilter(array('field'     => 'application_id',  'operator'  => 'equals', 'value'     => $appId)));
        
        $backend = Tinebase_Core::getPreference($applicationName);
        if ($backend) {
            $records = $backend->search($filter);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Got ' . count($records) . ' preferences for app ' . $applicationName);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' ' . print_r($records->toArray(), TRUE));
            
            $result = $this->_multipleRecordsToJson($records, $filter);
            
            // add translated labels and descriptions
            $translations = $backend->getTranslatedPreferences();
            foreach ($result as $key => $prefArray) {
                if (isset($translations[$prefArray['name']])) {
                    $result[$key] = array_merge($prefArray, $translations[$prefArray['name']]);
                } else {
                    $result[$key] = array_merge($prefArray, array('label' => $prefArray['name']));
                }
            }
            
            // sort prefs by definition
            $allPrefs = (array) $backend->getAllApplicationPreferences();
            usort($result, function($a, $b) use ($allPrefs) {
                $a = (int) array_search($a['name'], $allPrefs);
                $b = (int) array_search($b['name'], $allPrefs);
                return $a <=> $b;
            });
            
        } else {
            $result = array();
        }
        
        return array(
            'results'       => $result,
            'totalcount'    => count($result)
        );
    }

    /**
     * save preferences for application
     * 
     * @todo move saving of user values to preferences controller
     */
    public function savePreferences($data, $accountId = null): array
    {
        $decodedData = $this->_prepareParameter($data);
        $result = array();
        $accountId ??= Tinebase_Core::getUser()->getId();

        if ($accountId !== Tinebase_Core::getUser()->getId() 
            && !Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::MANAGE_ACCOUNTS)) {
            throw new Tinebase_Exception_AccessDenied('No permission to edit other user preferences !');
        }
        
        foreach ($decodedData as $applicationName => $data) {
            
            if ($applicationName == 'Tinebase.UserProfile') {
                $userProfileData = array();
                foreach($data as $fieldName => $valueArray) {
                    $userProfileData[$fieldName] = $valueArray['value'];
                }
                $this->updateUserProfile($userProfileData);
                
            } else {
                $backend = Tinebase_Core::getPreference($applicationName);
                if ($backend !== NULL) {
                    // set user prefs
                    foreach ($data as $name => $value) {
                        try {
                            $name = $value['name'] ?? $name;
                            $backend->doSpecialJsonFrontendActions($this, $name, $value['value'], $applicationName, $accountId);
                            
                            if (!$accountId) {
                                $backend->$name = $value['value'];
                                $result[$applicationName][] = array('name' => $name, 'value' => $backend->$name);
                            } else {
                                $backend->setValueForUser($name, $value['value'], $accountId);
                                $result[$applicationName][] = array('name' => $name, 'value' => $value['value']);
                            }
                        } catch (Exception $e) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' '
                                    . 'Could not save preference '. $name . ' -> ' . $e->getMessage());
                        }
                    }
                }
            }
        }
        
        return array(
            'status'    => 'success',
            'results'   => $result
        );
    }

    /**
     * get profile of current user
     *
     * @param string $userId
     * @return array
     */
    public function getUserProfile($userId)
    {
        // NOTE: $userProfile is a contact where non readable fields are clearad out!
        $userProfile = Tinebase_UserProfile::getInstance()->get($userId);
        
        // NOTE: This hurts! We don't have methods to call in our frontends yet which convert
        //       a record to the json representaion :( Thus image link will be broken!
        $userProfile->setTimezone(Tinebase_Core::getUserTimezone());
        
        return array(
            'userProfile'      => $userProfile->toArray(),
            'readableFields'   => Tinebase_UserProfile::getInstance()->getReadableFields(),
            'updateableFields' => Tinebase_UserProfile::getInstance()->getUpdateableFields(),
        );
    }
    
    /**
     * update user profile
     *
     * @param  array $profileData
     * @return array
     */
    public function updateUserProfile($profileData)
    {
        $contact = new Addressbook_Model_Contact(array(), TRUE);
        $contact->setFromJsonInUsersTimezone($profileData);
        
        // NOTE: $userProfile is a contact where non readable fields are clearad out!
        $userProfile = Tinebase_UserProfile::getInstance()->update($contact);
        
        // NOTE: This hurts! We don't have methods to call in our frontends yet which convert
        //       a record to the json representaion :( Thus image link will be broken!
        $userProfile->setTimezone(Tinebase_Core::getUserTimezone());
        return $userProfile->toArray();
    }

    public function getBatchJobProgress(string $jobId): array
    {
        return Tinebase_Controller_BatchJob::getInstance()->getProgress($jobId);
    }
    
    /**
     * dummy function to measure speed of framework initialization
     */
    public function void()
    {
        return array();
    }
    
    /**
     * gets the userProfile config
     *
     * @return array
     */
    public function getUserProfileConfig()
    {
        return array(
            'possibleFields'   => array_values(Tinebase_UserProfile::getInstance()->getPossibleFields()),
            'readableFields'   => array_values(Tinebase_UserProfile::getInstance()->getReadableFields()),
            'updateableFields' => array_values(Tinebase_UserProfile::getInstance()->getUpdateableFields()),
        );
    }
    
    /**
     * saves userProfile config
     *
     * @param array $configData
     */
    public function setUserProfileConfig($configData)
    {
        Tinebase_UserProfile::getInstance()->setReadableFields($configData['readableFields']);
        Tinebase_UserProfile::getInstance()->setUpdateableFields($configData['updateableFields']);
    }
    
    /**
     * switch to another user's account
     * 
     * @param string $loginName
     * @return array
     */
    public function changeUserAccount($loginName)
    {
        $result = Tinebase_Controller::getInstance()->changeUserAccount($loginName);
        return array(
            'success' => $result
        );
    }
    
    /************************ department functions **************************/
    
    /**
     * search / get departments
     *
     * @param  array $filter filter array
     * @param  array $paging pagination info
     * @return array
     */
    public function searchDepartments($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Tinebase_Department::getInstance(), 'Tinebase_Model_DepartmentFilter');
        return $result;
    }
    
    /************************* relation functions ***************************/
    
    /**
     * get all relations of a given record
     *
     * @param  string       $model         own model to get relations for
     * @param  string       $id            own id to get relations for
     * @param  string       $degree        only return relations of given degree
     * @param  array        $type          only return relations of given type
     * @param  string       $relatedModel  only return relations having this related model
     * @return array
     */
    public function getRelations($model, $id, $degree = NULL, $type = array(), $relatedModel = NULL)
    {
        if (! is_array($type)) {
            $type = array();
        }
        $relations = Tinebase_Relations::getInstance()->getRelations($model, 'Sql', $id, $degree, $type, false, $relatedModel);

        // @TODO we still have no converter for relations :-(
        // -> related records returned here are different to the records returned by the apps itself!
        // -> this problem also applies to to generic json converter!
        if (count($relations) > 0) {
            $relations->setTimezone(Tinebase_Core::getUserTimezone());
            $relations->bypassFilters = true;
            $result = $relations->toArray();
        } else {
            $result = array();
        }
        return array(
            'results'       => array_values($result),
            'totalcount'    => count($result),
        );
    }
    
    /************************ config functions ******************************/
    
    /**
     * get config settings for application
     *
     * @param string $id application name
     * @return array
     */
    public function getConfig($id)
    {
        $controllerName = $id . '_Controller';
        $appController = Tinebase_Controller_Abstract::getController($controllerName);
        
        return array(
            'id'        => $id,
            'settings'  => $appController->getConfigSettings(TRUE),
        );
    }
    
    /**
     * save application config
     *
     * @param array $recordData
     * @return array
     */
    public function saveConfig($recordData)
    {
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recordData, TRUE));
        
        $controllerName = $recordData['id'] . '_Controller';
        $appController = Tinebase_Controller_Abstract::getController($controllerName);
        $appController->saveConfigSettings($recordData['settings']);
        
        return $this->getConfig($recordData['id']);
    }
    
    /************************ tempFile functions ******************************/
    
    /**
     * joins all given tempfiles in given order to a single new tempFile
     *
     * @param array $tempFilesData of tempfiles arrays $tempFiles
     * @return array new tempFile
     */
    public function joinTempFiles($tempFilesData)
    {
        $tempFileRecords = new Tinebase_Record_RecordSet('Tinebase_Model_TempFile');
        foreach($tempFilesData as $tempFileData) {
            $record = new Tinebase_Model_TempFile(array(), TRUE);
            $record->setFromJsonInUsersTimezone($tempFileData);
            $tempFileRecords->addRecord($record);
        }
        
        $joinedTempFile = Tinebase_TempFile::getInstance()->joinTempFiles($tempFileRecords);
        
        return $joinedTempFile->toArray();
    }

    public function testCloudAccountAccess(array $data): bool
    {
        /** @var Tinebase_Model_CloudAccount $record */
        $record = $this->_jsonToRecord($data, Tinebase_Model_CloudAccount::class);
        return $record->testAccess();
    }

    public function getCloudAccountWebDAVCollections(string $cloudAccountId): array
    {
        $cloudAccount = Tinebase_Controller_CloudAccount::getInstance()->get($cloudAccountId);
        if (!$cloudAccount->{Tinebase_Model_CloudAccount::FLD_CONFIG} instanceof Tinebase_Model_CloudAccount_CalDAV) {
            throw new Tinebase_Exception_SystemGeneric(Tinebase_Translation::getTranslation()->_('CloudAccount is not a CalDAV account'));
        }

        /** @var Calendar_Backend_CalDav_Client $calDavClient */
        $calDavClient = $cloudAccount->{Tinebase_Model_CloudAccount::FLD_CONFIG}->getClient();
        if ($result = $calDavClient->findAllCollections()) {
            return $this->_multipleRecordsToJson($result);
        }
        return [];
    }
    
    /************************ protected functions ***************************/
    
    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Interface
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = NULL)
    {
        if (count($_records) == 0) {
            return array();
        }
        
        switch ($_records->getRecordClassName()) {
            case 'Tinebase_Model_Preference':
                $accountFilterArray = $_filter->getFilter('account')->toArray();
                $adminMode = ($accountFilterArray['value']['accountId'] == 0 && $accountFilterArray['value']['accountType'] == Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);
                foreach ($_records as $record) {
                    if (! isset($app) || $record->application_id != $app->getId()) {
                        $app = Tinebase_Application::getInstance()->getApplicationById($record->application_id);
                    }
                    $preference = Tinebase_Core::getPreference($app->name, TRUE);
                    $preference->resolveOptions($record, $accountFilterArray['value']['accountId']);
                    if ($record->type == Tinebase_Model_Preference::TYPE_DEFAULT || ! $adminMode && $record->type == Tinebase_Model_Preference::TYPE_ADMIN) {
                        $record->value = Tinebase_Model_Preference::DEFAULT_VALUE;
                    }
                }
                break;
        }
        
        $result = parent::_multipleRecordsToJson($_records, $_filter, $_pagination);
        return $result;
    }
    
    /**
     * return autocomplete suggestions for a given recordclass, the property and value
     *
     * @param string $appName
     * @param string $modelName
     * @param string $property
     * @param string $startswith
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function autoComplete($appName, $modelName, $property, $startswith)
    {
        $recordClassName = $appName . '_Model_' . $modelName;

        $controller = Tinebase_Core::getApplicationInstance($appName, $modelName);

        if (! method_exists($controller, 'search')) {
            throw new Tinebase_Exception_InvalidArgument('Controller needs search() method');
        }

        if (! class_exists($recordClassName)) {
            throw new Tinebase_Exception_InvalidArgument(
                'A record class for the given appName and modelName does not exist!');
        }
        
        if (! $controller) {
            throw new Tinebase_Exception_InvalidArgument(
                'A controller for the given appName and modelName does not exist!');
        }

        /** @var Tinebase_Model_Filter_FilterGroup $filter */
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($recordClassName);
        $propFilter = $filter->createFilter(['field' => $property, 'operator' => 'startswith', 'value' => $startswith]);
        if (!$propFilter instanceof Tinebase_Model_Filter_Text && !$propFilter instanceof Tinebase_Model_Filter_Query) {
            throw new Tinebase_Exception_UnexpectedValue('bad property name');
        }
        $filter->addFilter($propFilter);

        $paging = new Tinebase_Model_Pagination(array('sort' => $property));

        $values = array_unique(array_map(
            fn($value) => trim((string) $value),
            $controller->search($filter, $paging)->{$property}
        ));

        $result = array(
            'results'   => array(),
            'totalcount' => count($values)
        );
        
        foreach($values as $value) {
            $result['results'][] = array($property => $value);
        }
        
        return $result;
    }

    public function getWebAuthnAuthenticateOptionsForLogin(?string $mfaId = null): array
    {
        if (null === $mfaId) {
            if (null === ($mfaId = Tinebase_Config::getInstance()->{Tinebase_Config::MFA}->records?->find(Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS, Tinebase_Auth_MFA_WebAuthnAdapter::class)?->getId())) {
                throw new Tinebase_Exception_Backend('service not configured');
            }
        }

        if (!Tinebase_Session::isStarted()) {
            Tinebase_Core::startCoreSession();
        }
        return json_decode(Tinebase_Auth_Webauthn::serializePublicKeyCredentialRequestOptions(
            Tinebase_Auth_Webauthn::getWebAuthnRequestOptions(
                Tinebase_Auth_MFA::getInstance($mfaId)->getAdapter()->getConfig(),
                true
        )), true);
    }


/**
     * @param string $accountLoginName
     * @param string $mfaId
     * @return array
     * @throws Tinebase_Exception_Backend
     * @throws Tinebase_Exception_NotFound
     */
    public function getWebAuthnAuthenticateOptionsForMFA(string $accountLoginName, string $mfaId): array
    {
        $account = Tinebase_User::getInstance()->getFullUserByLoginName($accountLoginName);
        
        /** @var Tinebase_Model_MFA_UserConfig $userCfg */
        $userCfg = $account->mfa_configs?->getById($mfaId);
        $configId = $userCfg?->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID};
        if (! $configId) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Account ' . $accountLoginName . ' has no valid MFA config'
                . ' (mfa id: ' . $mfaId . ')');
            throw new Tinebase_Exception_NotFound('User MFA config not found');
        }
        /** @var Tinebase_Model_MFA_WebAuthnConfig $config */
        $config = Tinebase_Auth_MFA::getInstance($configId)->getAdapter()->getConfig();

        return json_decode(Tinebase_Auth_Webauthn::serializePublicKeyCredentialRequestOptions(
            Tinebase_Auth_Webauthn::getWebAuthnRequestOptions($config, true, $account, $mfaId)
        ), true);
    }

    public function getWebAuthnRegisterPublicKeyOptionsForMFA(string $mfaId, ?string $accountId = null): array
    {
        if (null !== $accountId && Tinebase_Core::getUser()->accountId !== $accountId) {
            if (!Tinebase_Core::getUser()->hasRight(Tinebase_Config::APP_NAME, Tinebase_Acl_Rights::ADMIN)) {
                throw new Tinebase_Exception_AccessDenied('user has not right to register webauthn devices for other users');
            }
            $user = Tinebase_User::getInstance()->getFullUserById($accountId);
        } else {
            $user = Tinebase_Core::getUser();
        }

        /** @var Tinebase_Model_MFA_WebAuthnConfig $config */
        $config = Tinebase_Auth_MFA::getInstance($mfaId)->getAdapter()->getConfig();

        return json_decode(Tinebase_Auth_Webauthn::serializePublicKeyCredentialCreationOptions(
            Tinebase_Auth_Webauthn::getWebAuthnCreationOptions(true, $user, $config)
        ), true);
    }

    /**
     * Toggles advanced search preference
     *
     * @param string|integer $state
     * @return true
     *
     * @todo still needed?
     */
    public function toogleAdvancedSearch($state)
    {
        Tinebase_Core::getPreference()->setValue(Tinebase_Preference::ADVANCED_SEARCH, (int)$state);
        return $state == Tinebase_Core::getPreference()->getValue(Tinebase_Preference::ADVANCED_SEARCH, 0);
    }

    public function createTempFile($fileLocation)
    {
        $src = new Tinebase_Model_Tree_FileLocation($fileLocation);

        $transId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        // if $transId is not set to null, rollback. note the & pass-by-ref! otherwise it would not work
        $raii = (new Tinebase_RAII(function() use (&$transId) {
            if (null !== $transId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }))->setReleaseFunc(function () use (&$transId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transId);
            $transId = null;
        });

        $result = Tinebase_TempFile::getInstance()->createTempFileFromNode($src->getNode())->toArray();
        $raii->release();
        return $result;
    }

    public function checkAuthToken($token, $channel)
    {
        /** @var Tinebase_Model_AuthToken $t */
        $t = Tinebase_Controller_AuthToken::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Tinebase_Model_AuthToken::class, [
                ['field' => Tinebase_Model_AuthToken::FLD_AUTH_TOKEN, 'operator' => 'equals', 'value' => $token]
            ]
        ))->getFirstRecord();

        if ($t && in_array($channel, $t->{Tinebase_Model_AuthToken::FLD_CHANNELS})) {
            return $t->toArray();
        }

        throw new Tinebase_Exception_AccessDenied('auth token not valid');
    }

    public function getAuthToken($channels, $ttl = null)
    {
        if (!is_array($channels) || empty($channels)) {
            throw new Tinebase_Exception_UnexpectedValue('channels needs to be a non empty array of channel names');
        }
        sort($channels);

        $token = hash('sha256', uniqid('', true) . hash('sha256', uniqid('', true) . Tinebase_Record_Abstract::generateUID()));

        $maxTtl = Tinebase_Config::getInstance()->{Tinebase_Config::AUTH_TOKEN_DEFAULT_TTL};
        $tokenRecord = new Tinebase_Model_AuthToken([
            Tinebase_Model_AuthToken::FLD_AUTH_TOKEN   => $token,
            Tinebase_Model_AuthToken::FLD_ACCOUNT_ID   => Tinebase_Core::getUser()->getId(),
            Tinebase_Model_AuthToken::FLD_CHANNELS     => $channels,
            Tinebase_Model_AuthToken::FLD_MAX_TTL      => $maxTtl,
        ], true);

        $configuredChannels = Tinebase_Config::getInstance()->{Tinebase_Config::AUTH_TOKEN_CHANNELS};
        foreach ($channels as $channel) {
            if (!($channelCfg = $configuredChannels->records
                    ->find(Tinebase_Model_AuthTokenChannelConfig::FLDS_NAME, $channel))) {
                throw new Tinebase_Exception_UnexpectedValue('unknown channel "' . $channel . '"');
            }
            if (is_callable($channelCfg->{Tinebase_Model_AuthTokenChannelConfig::FLDS_TOKEN_CREATE_HOOK})) {
                call_user_func($channelCfg->{Tinebase_Model_AuthTokenChannelConfig::FLDS_TOKEN_CREATE_HOOK},
                    $tokenRecord);
            }
        }

        $ttl = intval($ttl);
        if ($ttl < 1 || $ttl > $tokenRecord->{Tinebase_Model_AuthToken::FLD_MAX_TTL}) {
            $ttl = $tokenRecord->{Tinebase_Model_AuthToken::FLD_MAX_TTL};
        }
        $tokenRecord->{Tinebase_Model_AuthToken::FLD_VALID_UNTIL} = Tinebase_DateTime::now()->addSecond($ttl);

        $tokenRecord = Tinebase_Controller_AuthToken::getInstance()->create($tokenRecord);

        return $tokenRecord->toArray();
    }

    public function copyNodes(array $sources, array $target, bool $forceOverwrite = false): array
    {
        $sources = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_FileLocation::class, $sources);
        $target = new Tinebase_Model_Tree_FileLocation($target);

        if (!empty($target->{Tinebase_Model_Tree_FileLocation::FLD_TYPE}) &&
                Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE !== $target->{Tinebase_Model_Tree_FileLocation::FLD_TYPE}) {
            throw new Tinebase_Exception_AccessDenied('target file location needs to be a fm node');
        }

        $raii = Tinebase_RAII::getTransactionManagerRAII();
        $targetNode = $target->getNode();
        $fs = Tinebase_FileSystem::getInstance();
        $targetPath = $fs->getPathOfNode($targetNode, true);
        if (!empty($target->{Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME})) {
            $targetPath .= '/' . $target->{Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME};
        }
        $isTargetAFile = Tinebase_Model_Tree_FileObject::TYPE_FOLDER !== $targetNode->type ||
            !empty($target->{Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME});
        if ($isTargetAFile) {
            if ($sources->count() > 1) {
                throw new Tinebase_Exception_UnexpectedValue('can not copy multiple sources into one target file');
            }
            $fs->checkPathACL(Tinebase_Model_Tree_Node_Path::createFromStatPath(dirname((string) $targetPath)), 'add');
        } else {
            $fs->checkPathACL(Tinebase_Model_Tree_Node_Path::createFromStatPath($targetPath), 'add');
        }

        $result = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class);
        /** @var Tinebase_Model_Tree_FileLocation $source */
        foreach ($sources as $source) {
            $sourcePath = $fs->getPathOfNode($source->getNode(), true);
            if ($isTargetAFile) {
                if ($fs->fileExists($targetPath) && $forceOverwrite) {
                    $fs->unlink($targetPath);
                }
            } elseif ($fs->fileExists($targetPath . '/' . basename($sourcePath)) && $forceOverwrite) {
                $fs->unlink($targetPath . '/' . basename($sourcePath));
            }
            $result->addRecord($fs->copy($sourcePath, $targetPath));
        }
        $raii->release();

        return $this->_multipleRecordsToJson($result);
    }

    public function restoreRevision($fileLocationSrc, $fileLocationTrgt = null)
    {
        $src = new Tinebase_Model_Tree_FileLocation($fileLocationSrc);
        $fs = Tinebase_FileSystem::getInstance();

        $transId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        // if $transId is not set to null, rollback. note the & pass-by-ref! otherwise it would not work
        $raii = (new Tinebase_RAII(function() use (&$transId) {
            if (null !== $transId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }))->setReleaseFunc(function () use (&$transId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transId);
            $transId = null;
        });

        $node = $src->getNode();

        if (null === $fileLocationTrgt) {
            if ($node->getHighestRevision() > $node->revision && ($currentNode = $fs->get($node->getId()))->hash !==
                    $node->hash) {
                $currentNode->hash = $node->hash;
                $fs->update($currentNode);
            }
        } else {
            $trgt = new Tinebase_Model_Tree_FileLocation($fileLocationTrgt);
            $trgt->copyNodeTo($node);
        }

        $raii->release();
        return ['success' => true];
    }

    /**
     * returns the replication modification logs
     *
     * @param int $sequence
     * @param int $limit
     * @return array
     * @throws Tinebase_Exception_AccessDenied
     */
    public function getReplicationModificationLogs(int $sequence, int $limit = 100)
    {
        if (! Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::REPLICATION)) {
            throw new Tinebase_Exception_AccessDenied('you do not have access to modlogs');
        }

        $result = [
            'results' => Tinebase_Timemachine_ModificationLog::getInstance()->getReplicationModificationsByInstanceSeq(
                $sequence, $limit)->toArray(),
        ];
        $result['totalcount'] = count($result['results']);
        $result['primaryTinebaseId'] = Tinebase_Core::getTinebaseId();

        return $result;
    }

    /**
     * @return array
     */
    public function reportPresence()
    {
        $success = Tinebase_Presence::getInstance()->reportPresence();

        return array(
            'success' => $success
        );
    }

    /**
     * @param string $filter
     * @param string $pagination
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function searchPaths($filter = null, $pagination = null)
    {
        if (!Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH)) {
            throw new Tinebase_Exception_SystemGeneric('paths are not activated in this installation');
        }
        if (null !== $filter) {
            $tmpFilter = $this->_decodeFilter($filter, Tinebase_Model_PathFilter::class);
            foreach (['path', 'query', 'shadow_path'] as $field) {
                /** @var Tinebase_Model_Filter_Abstract $f */
                foreach ($tmpFilter->getFilter($field, true, true) as $f) {
                    if (empty($f->getValue())) {
                        $tmpFilter->removeFilter($f, true);
                    }
                }
            }

            $filter = $tmpFilter->toArray();
        }
        return $this->_search($filter, $pagination, Tinebase_Record_Path::getInstance(),
            Tinebase_Model_PathFilter::class);
    }

    /**
     * seriously?.... please get rid of this
     *
     * @param array $_communityNumber
     * @return mixed
     */
    public function aggregatePopulation(array $_communityNumber)
    {
        if (isset($_communityNumber['id'])) {
            try {
                return Tinebase_Controller_MunicipalityKey::getInstance()->get($_communityNumber['id'])->toArray();
            } catch (Tinebase_Exception_NotFound) {}
        }
        return $_communityNumber;
    }


    /**
     * @param $bodyContent
     * @return string
     */
    public function purifyHTML($bodyContent)
    {
        $result = Felamimail_Controller_Message::getInstance()->purifyBodyContent($bodyContent);
        return  $result;
    }

    public function getPreviewsFromTempFile($tempFileId)
    {
        $tempFile = Tinebase_TempFile::getInstance()->getTempFile($tempFileId);
        $_node = new Tinebase_Model_Tree_Node([
            'id' => $tempFileId,
            'name'      => $tempFile->name,
            'tempFile'  => $tempFile,
            'hash'  =>  sha1($tempFileId),
            'size'     =>  $tempFile->size,
            'type'  =>  Tinebase_Model_Tree_FileObject::TYPE_FILE
        ], true);

        $result = Tinebase_FileSystem_Previews::getInstance()->createPreviewsFromNode($_node);

        return $_node->preview_count ?? 0;
    }
}
