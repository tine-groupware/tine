<?php declare(strict_types=1);

/**
 * @package     SSO
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
interface SSO_RPConfigInterface
{
    public function beforeCreateUpdateHook(): void;
}
