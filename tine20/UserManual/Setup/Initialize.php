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
    protected const CONTENT_URL = 'https://packages.tine20.com/maintenance/manual/tine20-handbook_html_chunked_2023-11_commit-9c3b2a239ad28dcaae97c429f0ffa0b3f48d920f.zip';
    protected const CONTENT_RELEASE = 'BE';
    protected const CONTENT_VERSION = '2023.11.14';
    public const USERMANUAL_STATE = 'usermanual_content_import';
    protected const MIN_MAX_ALLOWED_PACKET = 200 * 1024 * 1024; // 200 MB

    protected function _initializeContent()
    {
//        self::importManualContent();
    }

    /**
     * imports the file tine20/UserManual/Setup/files/bk01-toc.html into the database usermanual_manualpage
     *  where file = bk01-toc.html
     *
     * @return void
     * @throws Zend_Db_Adapter_Exception
     */
    public static function importUpdatedToc()
    {
        $filename = 'bk01-toc.html';
        $path = dirname(__FILE__) . '/files/' . $filename;

        if (file_exists($path)) {
            $content = file_get_contents($path);

            $db = Tinebase_Core::getDb();
            $db->update(SQL_TABLE_PREFIX . 'usermanual_manualpage', [
                    'content' => $content
                ], $db->quoteInto($db->quoteIdentifier('file') . ' = ?', $filename)
            );
        }
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
            try {
                Tinebase_Core::getDb()->query('SET SESSION max_allowed_packet=209715210;');
            } catch (Zend_Db_Statement_Exception $zdse) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage()
                );
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not import manual content: max_allowed_packet needs to be bigger than ' . self::MIN_MAX_ALLOWED_PACKET
                    . ' / you have: ' . $maxPacketSize
                );
                return;
            }
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

            self::importUpdatedToc();

        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not import manual content: ' . $e->getMessage()
            );
        }
    }
}
