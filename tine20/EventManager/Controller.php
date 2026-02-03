<?php

declare(strict_types=1);

/**
 * EventManager Controller
 *
 * @package      EventManager
 * @subpackage   Controller
 * @license      https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Philipp Schüle <p.schuele@metaways.de> Tonia Wulff <t.leuschel@metaways.de>
 * @copyright    Copyright (c) 2020-2025 Metaways Infosystems GmbH (https://www.metaways.de)
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
            $routeCollector->get('/events/rss', (new Tinebase_Expressive_RouteHandler(
                EventManager_Frontend_RssFeed::class,
                'publicApiGetRssFeed',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/view[/{path:.+}]', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiMainScreen',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/getFile/{node_id}', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Registration::class,
                'publicApiGetFile',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/events', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiEvents',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/contact', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiStatic',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/event/{event_id}[/registration/{token}]', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiGetEvent',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/account/{token}', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiGetAccountDetails',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->post('/register/{event_id}', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Registration::class,
                'publicApiPostRegistration',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->post('/files/{event_id}/{option_id}/{registration_id}', (
                new Tinebase_Expressive_RouteHandler(
                    EventManager_Controller_Registration::class,
                    'publicApiPostFileToFileManager',
                    [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
                )
            )->toArray());
            $routeCollector->post('/registration/doubleOptIn/{event_id}', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Registration::class,
                'publicApiPostDoubleOptIn',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->post(
                '/deregistration/{event_id}/{token}[/{registration_id}]',
                (new Tinebase_Expressive_RouteHandler(
                    EventManager_Controller_Registration::class,
                    'publicApiPostDeregistration',
                    [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
                ))->toArray()
            );
        });
    }

    public static function checkFileType(Tinebase_Model_TempFile $tempFile): bool
    {
        $fileType = '.' . pathinfo($tempFile->name, PATHINFO_EXTENSION);
        return in_array($fileType, EventManager_Config::getInstance()
            ->get(EventManager_Config::ALLOWED_FILE_TYPE));
    }

    public static function processFileUpload(
        $tempFileId,
        $fileName,
        $eventId,
        array $folderPath,
        $updateCallback = null
    ) {
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
                } else {
                    $node = $nodeController->createNodes(
                        [$fullFileName],
                        [Tinebase_Model_Tree_FileObject::TYPE_FILE],
                        [$tempFile->getId()],
                        true
                    )->getFirstRecord();
                }
                if ($updateCallback && is_callable($updateCallback)) {
                    $updateCallback($node);
                }
                return $node;
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
