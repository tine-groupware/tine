<?php
/**
 * Tine 2.0
 *
 * @package     UserManual
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for UserManual initialization
 *
 * @package     Setup
 */
class UserManual_Setup_Initialize extends Setup_Initialize
{
    protected const CONTENT_URL = 'https://packages.tine20.com/maintenance/manual/tine20-handbook_html_chunked_build-54377_commit-50ea4809fec3230c265b36eb158bd181ad67f7f5.zip';
    protected const CONTENT_RELEASE = 'BE';
    protected const CONTENT_VERSION = '2019.11.54377';
    public const USERMANUAL_STATE = 'usermanual_content_import';
    protected const MIN_MAX_ALLOWED_PACKET = 200 * 1024 * 1024; // 200 MB

    protected function _initializeContent()
    {
        self::importManualContent();
    }

    public static function importManualContent($overwrite = false)
    {
        $state = Tinebase_Application::getInstance()->getApplicationState('UserManual',
            self::USERMANUAL_STATE);
        if (! $overwrite && $state) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' We already found imported content state: ' . $state
            );
            return;
        }

        $maxPacketSize = (int) Tinebase_Core::getDbVariable('max_allowed_packet', Setup_Core::getDb());
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Configured max_allowed_packet: ' . $maxPacketSize
        );
        if ($maxPacketSize < self::MIN_MAX_ALLOWED_PACKET) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not import manual content: max_allowed_packet needs to be bigger than ' . self::MIN_MAX_ALLOWED_PACKET
            );
            return;
        }

        try {
            // import content from url
            $localFile = Tinebase_Helper::getFilename(self::CONTENT_URL);

            $result = UserManual_Controller_ManualPage::getInstance()->import($localFile, true);
            if ($result) {
                UserManual_Controller_ManualContext::getInstance()->import($localFile);
            }

            Tinebase_Application::getInstance()->setApplicationState('UserManual', self::USERMANUAL_STATE,
                json_encode([
                    'url' => self::CONTENT_URL,
                    'release' => self::CONTENT_RELEASE,
                    'version' => self::CONTENT_VERSION,
                    'date' => Tinebase_DateTime::now()->toString(),
                ]));
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not import manual content: ' . $e->getMessage()
            );
        }
    }
}
