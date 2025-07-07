<?php
/**
 * GDPR Controller
 *
 * @package      GDPR
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * GDPR Controller
 *
 * @package      GDPR
 * @subpackage   Controller
 */
class GDPR_Controller extends Tinebase_Controller_Event implements
    Felamimail_Controller_MassMailingPluginInterface
{
    // TODO really?
    protected static $_defaultModel = GDPR_Model_DataProvenance::class;

    private function __construct()
    {
        $this->_applicationName = GDPR_Config::APP_NAME;
    }

    private function __clone()
    {
    }

    private static $_instance = null;

    /**
     * singleton
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * get core data for this application
     *
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getCoreDataForApplication()
    {
        $result = parent::getCoreDataForApplication();

        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);

        $result->addRecord(new CoreData_Model_CoreData([
            'id' => GDPR_Model_DataProvenance::class,
            'application_id' => $application,
            'model' => GDPR_Model_DataProvenance::class,
            'label' => 'Data provenances' // _('Data provenances')
        ]));
        $result->addRecord(new CoreData_Model_CoreData([
            'id' => GDPR_Model_DataIntendedPurpose::class,
            'application_id' => $application,
            'model' => GDPR_Model_DataIntendedPurpose::class,
            'label' => 'Purpose of processing' // _('Purpose of processing')
        ]));

        return $result;
    }

    /**
     * @param Felamimail_Model_Message $_message
     * @return null
     * @throws Tinebase_Exception_Backend_Database
     */
    public function prepareMassMailingMessage(Felamimail_Model_Message $_message, Tinebase_Twig $_twig)
    {
        GDPR_Controller_DataIntendedPurposeRecord::getInstance()->prepareMassMailingMessage($_message, $_twig);
        return ;
    }

    public static function addFastRoutes(\FastRoute\RouteCollector $routeCollector): void
    {
        $routeCollector->addGroup('/GDPR', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/view[/{path:.+}]', (new Tinebase_Expressive_RouteHandler(
                GDPR_Controller_DataIntendedPurposeRecord::class, 'publicApiMainScreen', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->get('/manageConsent[/{contactId}]', (new Tinebase_Expressive_RouteHandler(
                GDPR_Controller_DataIntendedPurposeRecord::class, 'publicApiGetManageConsentByContactId', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->post('/manageConsent/{contactId}', (new Tinebase_Expressive_RouteHandler(
            GDPR_Controller_DataIntendedPurposeRecord::class, 'publicApiPostManageConsentByContactId', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->get('/register/for[/{dipId}]', (new Tinebase_Expressive_RouteHandler(
                GDPR_Controller_DataIntendedPurposeRecord::class, 'publicApiGetRegisterForDataIntendedPurpose', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->post('/register/for[/{dipId}]', (new Tinebase_Expressive_RouteHandler(
                GDPR_Controller_DataIntendedPurposeRecord::class, 'publicApiPostRegisterForDataIntendedPurpose', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->get('/register/{token}', (new Tinebase_Expressive_RouteHandler(
                GDPR_Controller_DataIntendedPurposeRecord::class, 'publicApiGetRegisterFromToken', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->post('/register/{token}', (new Tinebase_Expressive_RouteHandler(
                GDPR_Controller_DataIntendedPurposeRecord::class, 'publicApiPostRegisterFromToken', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
        });
    }
}
