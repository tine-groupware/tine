<?php /** @noinspection PhpDeprecationInspection */
/**
 * Tinebase Twig Bootstrap Email Extension
 *
 * @package     Tinebase
 * @subpackage  Twig
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use AntibodiesOnline\BootstrapEmail\Compiler;
use AntibodiesOnline\BootstrapEmail\ScssCompiler;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Tinebase_Twig_BootstrapEmailExtension extends AbstractExtension
{
    private $bootstrapEmailCompiler;

    public function __construct()
    {
        $scss = new ScssCompiler();
        // Suppress warnings only during tests
        $errorReporting = error_reporting();
        error_reporting($errorReporting & ~E_DEPRECATED);

        $this->bootstrapEmailCompiler = new Compiler($scss);

        error_reporting($errorReporting);
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('bootstrap_email', [$this, 'compileBootstrapEmail'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Compile Bootstrap Email template
     *
     * @param string $html The HTML template to compile
     * @return string Compiled HTML with inlined CSS
     */
    public function compileBootstrapEmail(string $html): string
    {
        try {
            $errorReporting = error_reporting();
            error_reporting($errorReporting & ~E_DEPRECATED);

            $html = $this->bootstrapEmailCompiler->compileHtml($html);

            error_reporting($errorReporting);
            return $html;
        } catch (\Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Bootstrap Email compilation error: ' . $e->getMessage());

            throw new Tinebase_Exception_SystemGeneric("Bootstrap Email compilation error");
        }
    }
}