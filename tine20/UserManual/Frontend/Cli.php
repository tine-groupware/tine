<?php
/**
 * Tine 2.0
 *
 * @package     UserManual
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * cli frontend for UserManual
 *
 * This class handles cli requests for the UserManual
 *
 * @package     UserManual
 * @subpackage  Frontend
 */
class UserManual_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * @var string
     */
    protected $_applicationName = 'UserManual';

    protected $_help = array(
        'importHandbookBuild' => array(
            'description'   => 'Import Manual Pages from file or url',
            'params' => array(
                'filename' => 'path / url to handbook build'
            )
        ),
    );

    /**
     * import handbook build
     *
     * USAGE:
     *
     * @param Zend_Console_Getopt $opts
     * @return int
     */
    public function importHandbookBuild(Zend_Console_Getopt $opts)
    {
        if (! $this->_checkAdminRight()) {
            return 2;
        }

        $args = $this->_parseArgs($opts, array(), 'filename');

        if (! isset($args['filename'])) {
            echo "Filename required\n";
            return 2;
        } else {
            $filename = $args['filename'][0];
        }

        // unzip file and import pages and context
        $localFile = Tinebase_Helper::getFilename($filename);

        $result = UserManual_Controller_ManualPage::getInstance()->import($localFile, true);
        if ($result) {
            $result = UserManual_Controller_ManualContext::getInstance()->import($localFile);
        }

        if (! $result) {
            return 2;
        } else {
            Tinebase_Application::getInstance()->setApplicationState('UserManual', UserManual_Setup_Initialize::USERMANUAL_STATE,
                json_encode([
                    'url' => $filename,
                    'date' => Tinebase_DateTime::now()->toString(),
                    // TODO allow to pass release / version as cli params
                    'release' => 'CLI',
                    'version' => '',
                ]));
        }

        return 0;
    }
}
