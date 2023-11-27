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
            'label' => 'Data intended purposes' // _('Data intended purposes')
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

    /**
     * @param \FastRoute\RouteCollector $routeCollector
     * @return null
     */
    public static function addFastRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $routeCollector->addGroup('/GDPR', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/view/manageConsent[/{contactId}]', (new Tinebase_Expressive_RouteHandler(
                GDPR_Controller_DataIntendedPurposeRecord::class, 'publicApiMainScreen', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->get('/search/manageConsent[/{email}]', (new Tinebase_Expressive_RouteHandler(
                GDPR_Controller_DataIntendedPurposeRecord::class, 'publicApiSearchManageConsent', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->get('/manageConsent/{contactId}', (new Tinebase_Expressive_RouteHandler(
                GDPR_Controller_DataIntendedPurposeRecord::class, 'publicApiGetManageConsent', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->post('/manageConsent/{contactId}', (new Tinebase_Expressive_RouteHandler(
            GDPR_Controller_DataIntendedPurposeRecord::class, 'publicApiPostManageConsent', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
        });
        return null;
    }
}
