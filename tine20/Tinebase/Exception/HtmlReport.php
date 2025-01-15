<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrere@metaways.de>
 *
 */

class Tinebase_Exception_HtmlReport extends Tinebase_Exception_ProgramFlow
{
    public function __construct(
        protected string $html,
        $message = null, $code = 480, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function toArray(): array
    {
        return ['html' => $this->html];
    }
}

