<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class CrewScheduling_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    public const MANAGE_SCHEDULING_ROLES = 'manage_scheduling_roles';

    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        return array_merge(parent::getAllApplicationRights(), [
            self::MANAGE_SCHEDULING_ROLES,
        ]);
    }

    /**
     * get translated right descriptions
     *
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation(CrewScheduling_Config::APP_NAME);

        $rightDescriptions = [
            self::MANAGE_SCHEDULING_ROLES => [
                'text'          => $translate->_('Manage Scheduling Roles'),
                'description'   => $translate->_('Manage Scheduling Roles'),
            ],
        ];

        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }
}
