<?php
/**
 * EventManager Controller
 *
 * @package      EventManager
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Philipp Schüle <p.schuele@metaways.de>
 * @copyright    Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * EventManager Controller
 *
 * @package      EventManager
 * @subpackage   Controller
 */
class EventManager_Controller extends Tinebase_Controller_Event
{
    use Tinebase_Controller_SingletonTrait;

    protected static $_defaultModel = EventManager_Model_Event::class;

    protected function __construct()
    {
        $this->_applicationName = EventManager_Config::APP_NAME;
    }

    public static function addFastRoutes(\FastRoute\RouteCollector $routeCollector): void
    {
        $routeCollector->addGroup('/EventManager', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/getFile/{nodeId}', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Registration::class,
                'publicApiGetFile',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/view', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiMainScreen',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/view/search/event', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiSearchEvents',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/view/event/{eventId}', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiGetEvent',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/get/contact/{token}/{eventId}', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiGetEventContactDetails',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->post('/register/{eventId}', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Registration::class,
                'publicApiPostRegistration',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->post('/files/{eventId}/{optionId}/{registrationId}', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Registration::class,
                'publicApiPostFileToFileManager',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->post('/registration/doubleOptIn/{eventId}', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Registration::class,
                'publicApiPostDoubleOptIn',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
        });
    }

    public static function checkFileType(Tinebase_Model_TempFile $tempFile): bool
    {
        $fileType = '.' . pathinfo($tempFile->name, PATHINFO_EXTENSION);
        return in_array($fileType, EventManager_Config::getInstance()
            ->get(EventManager_Config::ALLOWED_FILE_TYPE));
    }

    public static function processFileUpload($tempFileId, $fileName, $eventId, array $folderPath, $updateCallback = null)
    {
        try {
            $tempFile = Tinebase_TempFile::getInstance()->getTempFile($tempFileId);

            if ($tempFile) {
                if (!EventManager_Controller::checkFileType($tempFile)) {
                    return false;
                }

                $event = EventManager_Controller_Event::getInstance()->get($eventId);
                $eventName = $event->{EventManager_Model_Event::FLD_NAME};
                $basePath = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_FOLDER_FILEMANAGER_PATH);
                $nodeController = Filemanager_Controller_Node::getInstance();
                $prefix = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager') . '/folders/';

                // Ensure base event folder exists
                if (!Tinebase_FileSystem::getInstance()->isDir($prefix . $basePath)) {
                    EventManager_Setup_Initialize::createEventFolder();
                }

                $completePath = $basePath;
                $allFolders = array_merge(["/$eventName"], $folderPath);

                foreach ($allFolders as $folder) {
                    $completePath = $completePath . $folder;
                    if (!Tinebase_FileSystem::getInstance()->isDir($prefix . $completePath)) {
                        $nodeController->createNodes(
                            [$completePath],
                            [Tinebase_Model_Tree_FileObject::TYPE_FOLDER]
                        );
                    }
                }

                $fullFileName = $completePath . "/" . $fileName;

                if (!Tinebase_FileSystem::getInstance()->fileExists($prefix . $fullFileName)) {
                    $node = $nodeController->createNodes(
                        [$fullFileName],
                        [Tinebase_Model_Tree_FileObject::TYPE_FILE],
                        [$tempFile->getId()]
                    )->getFirstRecord();

                    if ($updateCallback && is_callable($updateCallback)) {
                        $updateCallback($node);
                    }
                    return $node;
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' File already exists: ' . $prefix . $fullFileName);
                    }
                    return false;
                }
            }
        } catch (Tinebase_Exception_NotFound $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Exception: ' . $e->getMessage());
            }
            return false;
        }
        return false;
    }
}
