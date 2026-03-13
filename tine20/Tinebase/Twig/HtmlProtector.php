<?php

/**
 * Tinebase Twig THtmlProtector
 *
 * @package     Tinebase
 * @subpackage  Twig
 */
/** @noinspection PhpDeprecationInspection */

class Tinebase_Twig_HtmlProtector
{
    // Standard tokens for masking Twig tags
    private const TOKENS = [
        'VAR_OPEN' => '___TWIG_VAR_OPEN___',
        'VAR_CLOSE' => '___TWIG_VAR_CLOSE___',
        'STMT_OPEN' => '___TWIG_STMT_OPEN___',
        'STMT_CLOSE' => '___TWIG_STMT_CLOSE___',
        'COMMENT_OPEN' => '___TWIG_COMMENT_OPEN___',
        'COMMENT_CLOSE' => '___TWIG_COMMENT_CLOSE___',
        'SPACE' => '___TWIG_SPACE___',
    ];

    /**
     * Protects Twig tags in specific attributes from being HTML-encoded.
     *
     * @param string $html HTML string
     * @param array $attributes List of attributes to protect, e.g. ['href','src']
     * @return DOMDocument HTML with protected Twig tags
     */
    public static function protectAttributes(string $html, array $attributes = ['href', 'src']): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        \libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        \libxml_clear_errors();

        foreach ($dom->getElementsByTagName('*') as $element) {
            foreach ($attributes as $attr) {
                if ($element->hasAttribute($attr)) {
                    $value = $element->getAttribute($attr);
                    $value = self::maskTwig($value);
                    $element->setAttribute($attr, $value);
                }
            }
        }

        return $dom;
    }

    /**
     * Masks Twig tags ({{ }}, {% %}, {# #}) and whitespace inside them
     */
    private static function maskTwig(string $value): string
    {
        $patterns = [
            '/{{(.*?)}}/s' => ['maskCallback', self::TOKENS['VAR_OPEN'], self::TOKENS['VAR_CLOSE']],
            '/{%(.*?)%}/s' => ['maskCallback', self::TOKENS['STMT_OPEN'], self::TOKENS['STMT_CLOSE']],
            '/{#(.*?)#}/s' => ['maskCallback', self::TOKENS['COMMENT_OPEN'], self::TOKENS['COMMENT_CLOSE']],
        ];

        foreach ($patterns as $pattern => $params) {
            [$callbackMethod, $openToken, $closeToken] = $params;
            $value = preg_replace_callback($pattern, function ($matches) use ($callbackMethod, $openToken, $closeToken) {
                return self::$callbackMethod($matches, $openToken, $closeToken);
            }, $value);
        }

        return $value;
    }

    /**
     * Callback for preg_replace_callback to replace whitespace and wrap Twig tags with tokens
     */
    private static function maskCallback(array $matches, string $openToken, string $closeToken): string
    {
        $inner = str_replace(' ', self::TOKENS['SPACE'], $matches[1]);
        return $openToken . $inner . $closeToken;
    }

    /**
     * Restores the masked tokens back to original Twig tags
     */
    public static function unmaskTwig(string $html): string
    {
        $replacements = [
            self::TOKENS['VAR_OPEN'] => '{{',
            self::TOKENS['VAR_CLOSE'] => '}}',
            self::TOKENS['STMT_OPEN'] => '{%',
            self::TOKENS['STMT_CLOSE'] => '%}',
            self::TOKENS['COMMENT_OPEN'] => '{#',
            self::TOKENS['COMMENT_CLOSE'] => '#}',
            self::TOKENS['SPACE'] => ' ',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }
}