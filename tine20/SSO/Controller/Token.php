<?php declare(strict_types=1);
/**
 * Token controller for SSO application
 *
 * @package     SSO
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Token controller class for SSO application
 *
 * @package     SSO
 * @subpackage  Controller
 *
 * @extends Tinebase_Controller_Record_Abstract<SSO_Model_Token>
 */
class SSO_Controller_Token extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = SSO_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => SSO_Model_Token::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => SSO_Model_Token::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = SSO_Model_Token::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        parent::checkFilterACL($_filter, $_action);

        if (!$this->_doContainerACLChecks) {
            return;
        }

        $_filter->addFilter(new Tinebase_Model_Filter_DateTime(
            [TMFA::FIELD => SSO_Model_Token::FLD_TTL, TMFA::OPERATOR => 'notnull', TMFA::VALUE => true]));
        $_filter->addFilter(new Tinebase_Model_Filter_DateTime(
            [TMFA::FIELD => SSO_Model_Token::FLD_TTL, TMFA::OPERATOR => 'after', TMFA::VALUE => Tinebase_DateTime::now()]));
    }

    public function deleteExpiredTokens(): bool
    {
        $raii = new Tinebase_RAII($this->assertPublicUsage());

        $this->deleteByFilter(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
            [TMFA::FIELD => SSO_Model_Token::FLD_TTL, TMFA::OPERATOR => 'before', TMFA::VALUE => Tinebase_DateTime::now()->subHour(1)],
        ]));

        unset($raii);
        return true;
    }
}
