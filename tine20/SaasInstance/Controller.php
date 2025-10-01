<?php

/**
 * Tine 2.0
 *
 * MAIN controller for Saas, does event handling
 *
 * @package     SaasInstance
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * main controller for SaasInstance
 *
 * @package     SaasInstance
 * @subpackage  Controller
 */
class SaasInstance_Controller extends Tinebase_Controller_Event
{
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'SaasInstance_Model_Instance';

    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'SaasInstance';

    /**
     * holds the instance of the singleton
     *
     * @var SaasInstance_Controller
     */
    private static $_instance = NULL;

    /**
     * constructor (get current user)
     */
    private function __construct() {
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return SaasInstance_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new SaasInstance_Controller;
        }

        return self::$_instance;
    }

    /**
     * event handler function
     *
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     * @throws Tinebase_Exception_Confirmation
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if (!Tinebase_Application::getInstance()->isInstalled('SaasInstance', true)) {
            return;
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()
            ->debug(__METHOD__ . '::' . __LINE__ . ' Handle event of type ' . get_class($_eventObject));

        switch (get_class($_eventObject)) {
            case Admin_Event_BeforeAddAccount::class:
                $this->_handleUserConfirmationException($_eventObject);
                break;
            case Admin_Event_BeforeUpdateAccount::class:
                $this->_handleChangeUserTypeException($_eventObject);
                break;
            case Admin_Event_UpdateQuota::class:
                $this->_handleQuotaConfirmationException($_eventObject);
                break;
            case Tinebase_Event_Notification::class:
                $this->_handleQuotaNotification($_eventObject);
                break;
        }
    }

    /**
     * @param Admin_Event_UpdateQuota $_eventObject
     * @throws Tinebase_Exception_Confirmation
     */
    protected function _handleQuotaConfirmationException(Admin_Event_UpdateQuota $_eventObject)
    {
        $context = Admin_Controller_Quota::getInstance()->getRequestContext();
        if ($this->_hasConfirmContextHeader($context)) {
            Tinebase_Controller_ActionLog::getInstance()->addActionLogConfirmationEvent($_eventObject);
            return;
        }

        $application = $_eventObject->application;
        $additionalData = $_eventObject->additionalData;
        $recordData = $_eventObject->recordData;

        $translate = Tinebase_Translation::getTranslation('SaasInstance');
        $message = $translate->_("Do you want to change your {0} Quota?");
        $message = str_replace(['{0}'], [$application], $message);

        $exception = new Tinebase_Exception_Confirmation($message);
        $userType = 'Customer';
        $pricePerUser = SaasInstance_Config::getInstance()->get(SaasInstance_Config::PRICE_PER_USER);
        $pricePerGB = SaasInstance_Config::getInstance()->get(SaasInstance_Config::PRICE_PER_GIGABYTE);
        $infoTemplate = SaasInstance_Config::getInstance()->get(SaasInstance_Config::PACKAGE_STORAGE_INFO_TEMPLATE);

        $throwException = false;
        $quota = 0;
        
        if ($application === 'Tinebase') {
            // check allow total quota management config first
            $quotaConfig = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA};
            $totalQuota = $quotaConfig->{Tinebase_Config::QUOTA_TOTALINMB};

            if (($additionalData['totalInByte'] / 1024 / 1024) > $totalQuota) {
                $totalQuota = $totalQuota / 1024;
                $quota = $totalQuota;
                $throwException = true;
            }
        }

        $isPersonalNode = $additionalData['isPersonalNode'] ?? false;

        if ($isPersonalNode && $application === 'Felamimail') {
            $currentEmailQuota = isset($recordData->email_imap_user) ? round($recordData->email_imap_user['emailMailQuota'] / 1024 / 1024 / 1024.4,2) : 0;
            if ($additionalData['emailMailQuota'] > $recordData->email_imap_user['emailMailQuota']) {
                $quota = $currentEmailQuota;
                $throwException = true;
            }
        }

        if ($isPersonalNode && $application === 'Filemanager') {
            $user = Admin_Controller_User::getInstance()->get($additionalData['accountId']);
            $userType = $user->type ?? $userType;
            if ($userType === Tinebase_Model_FullUser::USER_TYPE_VOLUNTEER) {
                $pricePerUser = SaasInstance_Config::getInstance()->get(SaasInstance_Config::PRICE_PER_USER_VOLUNTEER);
            }
            $userFSQuota = isset($user->xprops()[Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA]) ? $user->xprops()[Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA] : 0;
            
            if ($recordData['quota'] > $userFSQuota) {
                $userFSQuota = round($userFSQuota / 1024 / 1024 / 1024.4,2);
                $quota = $userFSQuota;
                $throwException = true;
            }
        }

        if ($throwException && !empty($infoTemplate)) {
            $info = str_replace(
                ['{0}', '{1}', '{2}', '{3}'],
                [$quota, $pricePerUser, $userType, $pricePerGB],
                $infoTemplate);
            $exception->setInfo($info);
            throw $exception;
        }
    }

    /**
     * @param Admin_Event_BeforeAddAccount $_eventObject
     * @throws Tinebase_Exception_Confirmation
     */
    protected function _handleUserConfirmationException(Admin_Event_BeforeAddAccount $_eventObject)
    {
        $context = Admin_Controller_User::getInstance()->getRequestContext();
        if ($this->_hasConfirmContextHeader($context)) {
            Tinebase_Controller_ActionLog::getInstance()->addActionLogConfirmationEvent($_eventObject);
            return;
        }

        $translate = Tinebase_Translation::getTranslation('SaasInstance');
        $message = $translate->_("Do you want to upgrade your user limit?");
        $exception = new Tinebase_Exception_Confirmation($message);

        $pricePerUser = SaasInstance_Config::getInstance()->get(SaasInstance_Config::PRICE_PER_USER);
        $userLimit = SaasInstance_Config::getInstance()->get(SaasInstance_Config::NUMBER_OF_INCLUDED_USERS);
        $infoTemplate = SaasInstance_Config::getInstance()->get(SaasInstance_Config::PACKAGE_USER_INFO_TEMPLATE);
        
        $newUser = $_eventObject->account;
        $userType = $newUser->type ?? 'Customer';
        if ($userType === Tinebase_Model_FullUser::USER_TYPE_VOLUNTEER) {
            $pricePerUser = SaasInstance_Config::getInstance()->get(SaasInstance_Config::PRICE_PER_USER_VOLUNTEER);
        }
        
        $noneSystemUserCount = Tinebase_User::getInstance()->countNonSystemUsers();
        $currentUserCount = Tinebase_User::getInstance()->getUsersCount();
        
        if ($noneSystemUserCount + 1 > $userLimit && $pricePerUser > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice
            (__METHOD__ . '::' . __LINE__ . 'Current total user count : "' . $currentUserCount . 
                ' , User limit : ' . $userLimit . PHP_EOL);
                
            if (!empty($infoTemplate)) {
                $info = str_replace(
                    ['{0}', '{1}', '{2}'],
                    [$userLimit, $pricePerUser, $userType],
                    $infoTemplate);
                $exception->setInfo($info);
            }
            throw $exception;
        }
    }

    /**
     * @param Admin_Event_BeforeUpdateAccount $_eventObject
     * @throws Tinebase_Exception_Confirmation
     */
    protected function _handleChangeUserTypeException(Admin_Event_BeforeUpdateAccount $_eventObject)
    {
        $oldUser = $_eventObject->oldAccount;
        $updatedUser = $_eventObject->newAccount;
        
        if ($updatedUser->type === null || $oldUser->type === null || $updatedUser->type === $oldUser->type) {
            return;
        }
            
        $context = Admin_Controller_User::getInstance()->getRequestContext();
        if ($this->_hasConfirmContextHeader($context)) {
            Tinebase_Controller_ActionLog::getInstance()->addActionLogConfirmationEvent($_eventObject);
            return;
        }

        $translate = Tinebase_Translation::getTranslation('SaasInstance');
        $message = $translate->_("Do you want to change the user type?");
        $exception = new Tinebase_Exception_Confirmation($message);

        $pricePerUser = SaasInstance_Config::getInstance()->get(SaasInstance_Config::PRICE_PER_USER);
        $infoTemplate = SaasInstance_Config::getInstance()->get(SaasInstance_Config::PACKAGE_CHANGE_USER_TYPE_INFO_TEMPLATE);

        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice
        (__METHOD__ . '::' . __LINE__ . 'Change User type to : ' . $updatedUser->type . PHP_EOL);
        
        if ($updatedUser->type === Tinebase_Model_FullUser::USER_TYPE_VOLUNTEER) {
            $pricePerUser = SaasInstance_Config::getInstance()->get(SaasInstance_Config::PRICE_PER_USER_VOLUNTEER);
        }
        $userType = Tinebase_Config::getInstance()->get(Tinebase_Config::USER_TYPES)->getTranslatedValue($updatedUser->type);
        if (!empty($infoTemplate)) {
            $info = str_replace(
                ['{0}', '{1}',],
                [$userType, $pricePerUser],
                $infoTemplate);
            $exception->setInfo($info);
        }
        throw $exception;
    }
    
    protected function _hasConfirmContextHeader($context)
    {
        if (!$context || !is_array($context)) {
            return false;
        }
        
        return array_key_exists('clientData', $context) && array_key_exists('confirm', $context['clientData'])
        || array_key_exists('confirm', $context);
    }

    /**
     * @param Tinebase_Event_Notification $_eventObject
     */
    protected function _handleQuotaNotification(Tinebase_Event_Notification $_eventObject)
    {
        $updater = $_eventObject->updater ?? 'unknown updater';
        $recipients = $_eventObject->recipients ?? 'unknown recipients';
        $subject = $_eventObject->subject ?? 'unknown subject';
        $messagePlain = $_eventObject->messagePlain ?? 'unknown message';

        $plain = "\n\n" . 'Subject: ' . $subject;
        $plain .= "\n" . 'Message: ' . $messagePlain;
        $plain .= "\n" . 'Updater: ' . $updater;
        $plain .= "\n" . 'Recipients: ' . "\n" . print_r($recipients, true);
        
        Tinebase_Controller_ActionLog::getInstance()->addActionLogQuotaNotification($plain);
    }

    /**
     * get application metrics
     *
     * @return array
     */
    public function metrics(): array
    {
        $data = [];
        try {
            $volunteerUserCount = Tinebase_User::getInstance()->search(new Tinebase_Model_FullUserFilter([
                ['field' => 'type', 'operator'  => 'equals', 'value' => Tinebase_Model_FullUser::USER_TYPE_VOLUNTEER]
            ]))->count();
            $data[SaasInstance_Config::APP_NAME] = [
                SaasInstance_Config::PRICE_PER_USER => SaasInstance_Config::getInstance()->get(SaasInstance_Config::PRICE_PER_USER),
                SaasInstance_Config::PRICE_PER_USER_VOLUNTEER => SaasInstance_Config::getInstance()->get(SaasInstance_Config::PRICE_PER_USER_VOLUNTEER),
                SaasInstance_Config::PRICE_PER_GIGABYTE => SaasInstance_Config::getInstance()->get(SaasInstance_Config::PRICE_PER_GIGABYTE),
                SaasInstance_Config::NUMBER_OF_INCLUDED_USERS => SaasInstance_Config::getInstance()->get(SaasInstance_Config::NUMBER_OF_INCLUDED_USERS),
                'numberOfReducedPriceUsers' => $volunteerUserCount
            ];
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
        }
        return $data;
    }
}
