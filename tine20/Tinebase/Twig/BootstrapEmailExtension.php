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
            // Log error or handle as needed
            error_log('Bootstrap Email compilation error: ' . $e->getMessage());
            return $html; // Return original HTML on error
        }
    }
}