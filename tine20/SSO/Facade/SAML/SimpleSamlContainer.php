<?php declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * facade for simpleSAMLphp Container class
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_SAML_SimpleSamlContainer extends \SimpleSAML\SAML2\Compat\AbstractContainer
{
    public function getLogger(): LoggerInterface
    {
        return new NullLogger();
    }

    public function debugMessage($message, string $type): void
    {
    }

    public function getTempDir(): string
    {
        return Tinebase_Core::getTempDir();
    }

    public function writeFile(string $filename, string $data, ?int $mode = null): void
    {
        $tmpName = Tinebase_TempFile::getTempPath();
        file_put_contents($tmpName, $data);
        if (null === $mode) {
            $mode = 0600;
        }
        chmod($tmpName, $mode);
        rename($tmpName, $filename);
    }

    public function setBlacklistedAlgorithms(?array $algos): void
    {}

    public function getPOSTRedirectURL(string $url, array $data = []): string
    {
        // TODO: Implement getPOSTRedirectURL() method.
        return '';
    }

    public function getClock(): \Psr\Clock\ClockInterface
    {
        return \Beste\Clock\UTCClock::create();
    }
}
