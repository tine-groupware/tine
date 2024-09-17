<?php
/**
 * Tinebase Doc/Docx template processor class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase Doc/Docx template processor class
 *
 * @package     Tinebase
 * @subpackage    Export
 */


class Tinebase_Export_Richtext_TemplateProcessor extends \PhpOffice\PhpWord\TemplateProcessor
{
    const TYPE_STANDARD = 'standard';
    const TYPE_DATASOURCE = 'datasource';
    const TYPE_GROUP = 'group';
    const TYPE_SUBGROUP = 'subgroup';
    const TYPE_RECORD = 'record';
    const TYPE_SUBRECORD = 'subrecord';

    const NEW_LINE_PLACEHOLDER = 'WORD_NEWLINE';

    /**
     * Content of document rels (in XML format) of the temporary document.
     *
     * @var string
     */
    protected $_temporaryDocumentRels = null;

    protected $_tempHeaderRels = array();

    protected $_tempFooterRels = array();

    protected $_type = null;

    protected $_parent = null;

    protected $_config = array();

    protected $_twigName = '';

    protected $_postPCallMap = [];

    protected $_twigTemplateSrc = null;

    /**
     * @param string $documentTemplate The fully qualified template filename.
     * @param bool   $inMemory
     * @param string $type
     * @param Tinebase_Export_Richtext_TemplateProcessor|null $parent
     *
     * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
     * @throws \PhpOffice\PhpWord\Exception\CopyFileException
     */
    public function __construct($documentTemplate, $inMemory = false, $type = self::TYPE_STANDARD, Tinebase_Export_Richtext_TemplateProcessor $parent = null, $name = '')
    {
        $this->_type = $type;
        $this->_parent = $parent;
        if (null !== $parent) {
             $this->_twigName = $parent->getTwigName();
        }
        $this->_twigName .= $type . $name;

        if (true === $inMemory) {
            $this->tempDocumentMainPart = $documentTemplate;
            return;
        }

        parent::__construct($documentTemplate);

        $index = 1;
        while (false !== $this->zipClass->locateName($this->getHeaderName($index))) {
            $fileName = 'word/_rels/header' . $index . '.xml.rels';
            if (false !== $this->zipClass->locateName($fileName)) {
                $this->_tempHeaderRels[$index] = $this->fixBrokenMacros(
                    $this->zipClass->getFromName($fileName)
                );
            }
            $index++;
        }
        $index = 1;
        while (false !== $this->zipClass->locateName($this->getFooterName($index))) {
            $fileName = 'word/_rels/footer' . $index . '.xml.rels';
            if (false !== $this->zipClass->locateName($fileName)) {
                $this->_tempFooterRels[$index] = $this->fixBrokenMacros(
                    $this->zipClass->getFromName($fileName)
                );
            }
            $index++;
        }

        if (false !== $this->zipClass->locateName('word/_rels/document.xml.rels')) {
            $this->_temporaryDocumentRels = $this->fixBrokenMacros(
                $this->zipClass->getFromName('word/_rels/document.xml.rels'));
        }

        $this->_postPCallMap = [
            'TC' => [
                'FILL' => function(&$data, $offset, array $params) {
                    $tc1Pos = false;
                    if (false === ($tcPos = strrpos($data, '<w:tc>', $offset - strlen($data))) &&
                            false === ($tc1Pos = strrpos($data, '<w:tc ', $offset - strlen($data)))) {
                        throw new Tinebase_Exception('could not find <w:tc in tc_fill');
                    }
                    if (false === $tcPos || (false !== $tc1Pos && $tc1Pos > $tcPos)) $tcPos = $tc1Pos;
                    if (false === ($tcPrPos = strpos($data, '<w:tcPr', $tcPos)) || $tcPrPos > $offset) {
                        if (false === ($gtPos = strpos($data, '>', $tcPos))) {
                            throw new Tinebase_Exception('could not find > for <w:tc in tc_fill');
                        }
                        $data = substr($data, 0, $gtPos + 1) . '<w:tcPr><w:shd w:fill="' . $params[0] . '"/></w:tcPr>' .
                            substr($data, $gtPos + 1);
                    } elseif (false === ($shdPos = strpos($data, '<w:shd ', $tcPrPos)) || $shdPos > $offset) {
                        if (false === ($gtPos = strpos($data, '>', $tcPrPos))) {
                            throw new Tinebase_Exception('could not find > for <w:tcPr in tc_fill');
                        }
                        $data = substr($data, 0, $gtPos + 1) . '<w:shd w:fill="' . $params[0] . '"/>' .
                            substr($data, $gtPos + 1);
                    } elseif (false === ($fillPos = strpos($data, 'w:fill="', $shdPos)) || $fillPos > $offset) {
                        if (false === ($gtPos = strpos($data, '/>', $shdPos))) {
                            throw new Tinebase_Exception('could not find /> for <w:shd in tc_fill');
                        }
                        $data = substr($data, 0, $gtPos + 1) . 'w:fill="' . $params[0] . '"' .
                            substr($data, $gtPos + 1);
                    } else {
                        $fillPos += 8;
                        if (false === ($quotePos = strpos($data, '"', $fillPos))) {
                            throw new Tinebase_Exception('could not find " for w:fill=" in tc_fill');
                        }
                        $data = substr($data, 0, $fillPos) . $params[0] . substr($data, $quotePos);
                    }
                },
            ],
            /*'TR' => [
                'FILL' => function(&$data, $offset, array $params) {
                    $tr1Pos = false;
                    if (false === ($trPos = strrpos($data, '<w:tr>', $offset - strlen($data))) &&
                            false === ($tr1Pos = strrpos($data, '<w:tr ', $offset - strlen($data)))) {
                        throw new Tinebase_Exception('could not find <w:tr in tr_fill');
                    }
                    if (false === $trPos || (false !== $tr1Pos && $tr1Pos > $trPos)) $trPos = $tr1Pos;
                    if (false === ($trPrPos = strpos($data, '<w:trPr', $trPos)) || $trPrPos > $offset) {
                        if (false === ($gtPos = strpos($data, '>', $trPos))) {
                            throw new Tinebase_Exception('could not find > for <w:tr in tr_fill');
                        }
                        $data = substr($data, 0, $gtPos + 1) . '<w:trPr><w:shd w:fill="' . $params[0] . '"/></w:trPr>' .
                            substr($data, $gtPos + 1);
                    } else {
                        if (false === ($offset1 = strpos($data, '</w:trPr>', $trPos)) || $offset1 > $offset) {
                            throw new Tinebase_Exception(('could not find </w:trPr> in tr_fill'));
                        }
                        $offset = $offset1;
                        if (false === ($shdPos = strpos($data, '<w:shd ', $trPrPos)) || $shdPos > $offset) {
                            if (false === ($gtPos = strpos($data, '>', $trPrPos))) {
                                throw new Tinebase_Exception('could not find > for <w:trPr in tr_fill');
                            }
                            $data = substr($data, 0, $gtPos + 1) . '<w:shd w:fill="' . $params[0] . '"/>' .
                                substr($data, $gtPos + 1);
                        } elseif (false === ($fillPos = strpos($data, 'w:fill="', $shdPos)) || $fillPos > $offset) {
                            if (false === ($gtPos = strpos($data, '/>', $shdPos))) {
                                throw new Tinebase_Exception('could not find /> for <w:shd in tr_fill');
                            }
                            $data = substr($data, 0, $gtPos + 1) . 'w:fill="' . $params[0] . '"' .
                                substr($data, $gtPos + 1);
                        } else {
                            $fillPos += 8;
                            if (false === ($quotePos = strpos($data, '"', $fillPos))) {
                                throw new Tinebase_Exception('could not find " for w:fill=" in tr_fill');
                            }
                            $data = substr($data, 0, $fillPos) . $params[0] . substr($data, $quotePos);
                        }
                    }
                },
            ],*/
        ];

        $this->forEachDocument(function(&$xml) {
            $xml = str_replace(["\n", "\r"], '', $xml);
        });
    }

    public function replaceTwigTemplate()
    {
        $this->_twigTemplateSrc = $this->findBlock('TWIG_TEMPLATE', '${TWIG_TEMPLATE}');
        if (null !== $this->_twigTemplateSrc) {
            $this->_twigTemplateSrc = $this->fixBrokenTwigMacros($this->_twigTemplateSrc);
        }
    }

    public function unsetTwigSource()
    {
        $this->_twigTemplateSrc = null;
    }

    public function getTwigSource()
    {
        return $this->_twigTemplateSrc;
    }

    /**
     * @return string
     */
    public function getTwigName()
    {
        return $this->_twigName;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @return Tinebase_Export_Richtext_TemplateProcessor|null
     */
    public function getParent()
    {
        return $this->_parent;
    }

    public function postProcessMarkers()
    {
        while (preg_match('/(POSTP_\w+)\~\!\§/', $this->tempDocumentMainPart, $m, PREG_OFFSET_CAPTURE)) {
            $this->tempDocumentMainPart = substr($this->tempDocumentMainPart, 0, $m[0][1]) .
                substr($this->tempDocumentMainPart, $m[0][1] + strlen($m[0][0]));

            $callStack = explode('_', $m[1][0]);
            array_shift($callStack);
            $this->callPostProcessor($this->tempDocumentMainPart, $m[0][1], $callStack, $this->_postPCallMap);
        }
    }

    protected function callPostProcessor(&$data, $offset, &$callStack, $callMap)
    {
        $key = array_shift($callStack);
        if (!isset($callMap[$key])) {
            throw new Exception('did not find ' . $key . ' in post processor call map');
        }
        if (is_callable($callMap[$key])) {
            $callMap[$key]($data, $offset, $callStack);
        } else {
            $this->callPostProcessor($data, $offset, $callStack, $callMap[$key]);
        }
    }

    public function replaceTine20ImagePaths()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' replacing images...');

        if (null !== $this->_temporaryDocumentRels) {
            $this->_replaceTine20ImagePaths($this->tempDocumentMainPart, $this->_temporaryDocumentRels);
        }
        foreach($this->_tempHeaderRels as $index => $data) {
            $this->_replaceTine20ImagePaths($this->tempDocumentHeaders[$index], $data);
        }
        foreach($this->_tempFooterRels as $index => $data) {
            $this->_replaceTine20ImagePaths($this->tempDocumentFooters[$index], $data);
        }
    }

    protected function _replaceTine20ImagePaths(&$xmlData, $relData)
    {
        $replacements = [];
        $offset = 0;

        do {
            if (false === ($newOffset = strpos($xmlData, 'descr="tine20://', $offset)) &&
                    false === ($newOffset = strpos($xmlData, 'descr="file://', $offset))) {
                break;
            }
            $offset = $newOffset;
            if (false === ($drawOffset = strrpos($xmlData, '<w:drawing', 0 - (strlen($xmlData) - $offset)))) {
                break;
            }
            if (false === ($drawEndOffset = strpos($xmlData, '</w:drawing>', $offset))) {
                break;
            }

            $drawingStr = substr($xmlData, $drawOffset, $drawEndOffset - $drawOffset + 12);
            if (preg_match(
                    '#<w:drawing[^>]*>.*<wp:docPr[^>]+descr="(\w+://[^"]+)".+r:embed="([^"]+)".+</w:drawing>#is',
                    $drawingStr, $match)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                    Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' found url: ' . $match[1]);

                $imgFormats = ['jpg', 'jpeg', 'png', 'gif', 'tif', 'tiff'];
                if (!in_array(mb_strtolower(pathinfo($match[1], PATHINFO_EXTENSION)), $imgFormats)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                        Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__ .
                            ' unsupported file extension: ' . $match[1]);
                    continue;
                }
                if (preg_match('#Relationship Id="' . $match[2] . '"[^>]+Target="(media/[^"]+)"#', $relData, $relMatch)) {
                    // if file was not found try all (other) supported file extensions
                    // i.e. file might be defined as jpg but a tiff with this name is in the filemanager
                    if (!is_file($match[1])) {
                        $pInfo = pathinfo($match[1]);
                        $ext = strtolower($pInfo['extension']);
                        $path = $pInfo['dirname'] . '/' . $pInfo['filename'] . '.';
                        foreach ($imgFormats as $format) {
                            if ($ext === $format) {
                                continue;
                            }
                            if (is_file($path . $format)) {
                                $match[1] = $path . $format;
                                break;
                            }
                        }
                    }
                    $fileContent = @file_get_contents($match[1]);
                    if (!empty($fileContent)) {
                        $this->zipClass->deleteName('word/' . $relMatch[1]);
                        $this->zipClass->addFromString('word/' . $relMatch[1], $fileContent);

                        $message = '';
                        try {
                            $imageSize = getimagesize($match[1]);
                        } catch (Throwable $exception) {
                            $imageSize = false;
                            $message = $exception->getMessage();
                        }

                        if (!$imageSize) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                                Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__
                                    . ' Could not get image size: ' . $match[1] . '/  error: ' . $message);
                            continue;
                        }

                        $replaceStr = $match[0];
                        $replaced = false;
                        $width = $imageSize[0] * 914400 / 96;
                        $height = $imageSize[1] * 914400 / 96;
                        if (preg_match_all('#<a:ext[^>]*c(.)="(\d+)"[^>]*c(.)="(\d+)"[^>]*>#', $match[0],
                            $submatches, PREG_SET_ORDER)) {
                            if (count($submatches) > 1) {
                                Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__ . ' found '
                                    . count($submatches) . ' <a:ext cx= cy= ' . $match[1]);
                            }
                            foreach ($submatches as $submatch) {
                                $this->_replaceSubmatch($submatch, $replaceStr, $width, $height);
                            }
                            $replaced = true;
                        } else {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__
                                . ' could not find <a:ext cx= cy= ' . $match[1]);
                        }

                        if (preg_match_all('#<wp:extent[^>]*c(.)="(\d+)"[^>]*c(.)="(\d+)"[^>]*>#', $match[0],
                            $submatches, PREG_SET_ORDER)) {
                            if (count($submatches) > 1) {
                                Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__ . ' found '
                                    . count($submatches) . ' <wp:extent cx= cy= ' . $match[1]);
                            }
                            foreach ($submatches as $submatch) {
                                $this->_replaceSubmatch($submatch, $replaceStr, $width, $height);
                            }
                            $replaced = true;
                        } else {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__
                                . ' could not find <wp:extent cx= cy= ' . $match[1]);
                        }

                        if ($replaced) {
                            $replacements[] = [
                                $match[0],
                                $replaceStr
                            ];
                        }
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                            Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__
                                . ' could not get file content: ' . $match[1]);
                    }
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                        Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                            . ' could not find relation matching found url: ' . $match[1]);
                }
            }
        } while (++$offset);

        foreach ($replacements as $rep) {
            $xmlData = str_replace($rep[0], $rep[1], $xmlData);
        }
    }

    /**
     * @param array $submatch
     * @param string $replaceStr
     * @param float $width
     * @param float $height
     */
    protected function _replaceSubmatch($submatch, &$replaceStr, $width, $height)
    {
        if ($width <= $submatch[2] && $height <= $submatch[4]) {
            if ($submatch[1] === 'x') {
                $var1 = $width;
                $var2 = $height;
            } else {
                $var1 = $height;
                $var2 = $width;
            }
            $replaceStr = str_replace($submatch[0], str_replace([
                'c' . $submatch[1] . '="' . $submatch[2] . '"',
                'c' . $submatch[3] . '="' . $submatch[4] . '"'
            ], [
                'c' . $submatch[1] . '="' . $var1 . '"',
                'c' . $submatch[3] . '="' . $var2 . '"'
            ], $submatch[0]), $replaceStr);
        } else {
            $oldRatio = $submatch[2] / $submatch[4];

            $newRatio = $width / $height;

            if ($newRatio >= $oldRatio) {
                $newWidth = $submatch[2];
                $newHeight = (int)($height * $submatch[2] / $width);
            } else {
                $newHeight = $submatch[4];
                $newWidth = (int)($width * $submatch[4] / $height);
            }
            $replaceStr = str_replace($submatch[0], str_replace([
                'c' . $submatch[1] . '="' . $submatch[2] . '"',
                'c' . $submatch[3] . '="' . $submatch[4] . '"'
            ], [
                'c' . $submatch[1] . '="' . $newWidth . '"',
                'c' . $submatch[3] . '="' . $newHeight . '"'
            ], $submatch[0]), $replaceStr);
        }
    }

    /**
     * Saves the result document.
     *
     * @return string
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function save()
    {
        if (null !== $this->_temporaryDocumentRels) {
            $this->zipClass->addFromString('word/_rels/document.xml.rels', $this->_temporaryDocumentRels);
        }

        foreach($this->_tempHeaderRels as $index => $data) {
            $this->zipClass->addFromString('word/_rels/header' . $index . '.xml.rels', $data);
        }

        foreach($this->_tempFooterRels as $index => $data) {
            $this->zipClass->addFromString('word/_rels/footer' . $index . '.xml.rels', $data);
        }

        return parent::save();
    }

    /**
     * executes a function on each xml document
     * use a reference parameter if you want to change the document
     */
    public function forEachDocument($closure)
    {
        foreach ($this->tempDocumentHeaders as &$xml) {
            $closure($xml);
        }
        $closure($this->tempDocumentMainPart);
        foreach ($this->tempDocumentFooters as &$xml) {
            $closure($xml);
        }
    }

    /**
     * @param string $data
     */
    public function setMainPart($data)
    {
        $this->tempDocumentMainPart = $data;
    }

    /**
     * @return string
     */
    public function getMainPart()
    {
        return $this->tempDocumentMainPart;
    }

    /**
     * replace a table row in a template document and return replaced row
     *
     * @param string $search
     * @param string $replacement
     *
     * @return string
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function replaceRow($search, $replacement)
    {
        $tagPos = strpos($this->tempDocumentMainPart, $search);
        if (!$tagPos) {
            throw new \PhpOffice\PhpWord\Exception\Exception("Can not clone row, template variable not found or variable contains markup.");
        }

        $rowStart = $this->findRowStart($tagPos);
        $rowEnd = $this->findRowEnd($tagPos);
        $xmlRow = $this->getSlice($rowStart, $rowEnd);

        $result = $this->getSlice(0, $rowStart) . $replacement;
        $result .= $this->getSlice($rowEnd);

        $this->tempDocumentMainPart = $result;

        return $xmlRow;
    }

    /**
     * @param $data
     */
    public function append($data)
    {
        $this->tempDocumentMainPart .= $data;
    }

    /**
     * Find the start position of the nearest table row before $offset.
     *
     * @param integer $offset
     * @return integer
     */
    protected function findRowStart($offset)
    {
        return $this->findTag('<w:tr', $offset, false);
    }

    /**
     * @param string $tag
     * @param int $offset
     * @param bool $forward
     * @return int
     * @throws Tinebase_Exception_NotFound
     */
    protected function findTag($tag, $offset, $forward = true)
    {
        if (true === $forward) {
            $strpos = 'strpos';
            $minmax = 'min';
        } else {
            $strpos = 'strrpos';
            $minmax = 'max';
            $offset = (strlen($this->tempDocumentMainPart) - $offset) * -1;
        }

        $result1 = $strpos($this->tempDocumentMainPart, $tag . ' ', $offset);
        $result2 = $strpos($this->tempDocumentMainPart, $tag . '>', $offset);

        if (false === $result1) {
            if (false === $result2) {
                throw new Tinebase_Exception_NotFound('Can not find the start position of the tag: ' . $tag);
            }
            return (int)$result2;
        }
        if (false === $result2) {
            return (int)$result1;
        }

        return (int)$minmax($result1, $result2);
    }

    /**
     * Find a block (optionally replace it)
     *
     * @param string $blockName
     * @param string $replacement
     *
     * @return string|null
     */
    public function findBlock($blockName, $replacement = null)
    {
        $openBlock = '${' . $blockName . '}';
        if (false === ($openBlockPos = strpos($this->tempDocumentMainPart, $openBlock))) {
            return null;
        }
        $openBlockPos = $this->findTag('<w:p', $openBlockPos, false);
        if (false === ($endOpenBlockPos = strpos($this->tempDocumentMainPart, '</w:p>', $openBlockPos))) {
            return null;
        }
        $endOpenBlockPos += 6;

        $closeBlock = '${/' . $blockName . '}';
        if (false === ($closeBlockPos = strpos($this->tempDocumentMainPart, $closeBlock, $endOpenBlockPos))) {
            return null;
        }
        $closeBlockPos = $this->findTag('<w:p', $closeBlockPos, false);
        if (false === ($endCloseBlockPos = strpos($this->tempDocumentMainPart, '</w:p>', $closeBlockPos))) {
            return null;
        }
        $endCloseBlockPos += 6;


        $xmlBlock = substr($this->tempDocumentMainPart, $endOpenBlockPos, $closeBlockPos - $endOpenBlockPos);

        if (null !== $replacement) {
            $openBlockContent = substr($this->tempDocumentMainPart, $openBlockPos, $endOpenBlockPos - $openBlockPos);
            $clsBlockContent = substr($this->tempDocumentMainPart, $closeBlockPos, $endCloseBlockPos - $closeBlockPos);

            if (strip_tags($openBlockContent) !== $openBlock) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                    Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__ . ' tag paragraph contains content: '
                        . strip_tags($openBlockContent));
                $openBlockContent = str_replace($openBlock, '', $openBlockContent);
                $replacement = $openBlockContent . $replacement;
            }
            if (strip_tags($clsBlockContent) !== $closeBlock) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                    Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__ . ' tag paragraph contains content: '
                        . strip_tags($clsBlockContent));
                $clsBlockContent = str_replace($closeBlock, '', $clsBlockContent);
                $replacement .= $clsBlockContent;
            }

            $this->tempDocumentMainPart = substr($this->tempDocumentMainPart, 0, $openBlockPos) . $replacement .
                substr($this->tempDocumentMainPart, $endCloseBlockPos);
        }

        return $xmlBlock;
    }

    /**
     * Clone a block.
     *
     * @param string $blockname
     * @param integer $clones
     * @param boolean $replace
     * @return string|null
     * @throws Tinebase_Exception_NotImplemented
     */
    public function cloneBlock($blockname, $clones = 1, $replace = true, $indexVariables = false, $variableReplacements = null)
    {
        throw new Tinebase_Exception_NotImplemented('do not use this function! ' . __METHOD__);
    }

    /**
     * Replace a block.
     *
     * @param string $blockname
     * @param string $replacement
     * @throws Tinebase_Exception_NotImplemented
     */
    public function replaceBlock($blockname, $replacement)
    {
        throw new Tinebase_Exception_NotImplemented('do not use this function! ' . __METHOD__);
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->_config = $config;
        if (Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD === $this->_type &&
                isset($this->_config['recordXml'])) {

        }
    }

    /**
     * @param $key
     * @return bool
     */
    public function hasConfig($key)
    {
        return isset($this->_config[$key]);
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getConfig($key = null)
    {
        if (null === $key) {
            return $this->_config;
        }
        return $this->_config[$key];
    }

    /**
     * Returns array of all variables in template.
     *
     * @return string[]
     */
    public function getVariables()
    {
        $result = parent::getVariables();

        switch($this->_type) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case self::TYPE_DATASOURCE:
                if (isset($this->_config['group'])) {
                    $result = array_merge($result, $this->_config['group']->getVariables());
                }
            case self::TYPE_GROUP:
                if (isset($this->_config['record'])) {
                    $result = array_merge($result, $this->_config['record']->getVariables());
                }
                if (isset($this->_config['recordRow']) && isset($this->_config['recordRow']['recordRowProcessor'])) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $result = array_merge($result, $this->_config['recordRow']['recordRowProcessor']->getVariables());
                }
                // DO NOT return variables of sub groups or sub records
                break;
            case self::TYPE_SUBGROUP:
            case self::TYPE_SUBRECORD:
                if (isset($this->_config['recordXml'])) {
                    $result = array_merge($result, $this->getVariablesForPart($this->_config['recordXml']));
                }
                break;
        }

        return array_unique($result);
    }

    /**
     * Finds parts of broken macros and sticks them together.
     * Macros, while being edited, could be implicitly broken by some of the word processors.
     *
     * @param string $documentPart The document part in XML representation.
     *
     * @return string
     */
    protected function fixBrokenMacros($documentPart)
    {
        $fixedDocumentPart = parent::fixBrokenMacros($documentPart);

        $fixedDocumentPart = preg_replace_callback(
            '|\{[^{}%]*\{[^}]*\}[^}]*\}|U',
            function ($match) {
                return strip_tags($match[0]);
            },
            $fixedDocumentPart
        );

        $fixedDocumentPart = preg_replace_callback(
            '|\{[^{}%]*%[^}]*%[^}]*\}|U',
            function ($match) {
                return strip_tags($match[0]);
            },
            $fixedDocumentPart
        );

        return $fixedDocumentPart;
    }

    /**
     * Finds parts of broken macros and sticks them together.
     * Macros, while being edited, could be implicitly broken by some of the word processors.
     *
     * @param string $documentPart The document part in XML representation.
     *
     * @return string
     */
    public function fixBrokenTwigMacros($documentPart)
    {
        $fixedDocumentPart = preg_replace_callback(
            '|\{[^}{%]*\{[^}]*\}[^}]*\}|U',
            function ($match) {
                return htmlspecialchars_decode(strip_tags($match[0]), ENT_QUOTES | ENT_XML1);
            },
            $documentPart
        );

        $fixedDocumentPart = preg_replace_callback(
            '|\{[^}{%]*%[^}]*\}|U',
            function ($match) {
                return htmlspecialchars_decode(strip_tags($match[0]), ENT_QUOTES | ENT_XML1);
            },
            $fixedDocumentPart
        );

        return $fixedDocumentPart;
    }

    public function addWaterMark($text, $headerIndex = 1)
    {
        if (null === $headerIndex) {
            foreach (array_keys($this->tempDocumentHeaders) as $hIdx) {
                $this->addWaterMark($text, $hIdx);
            }
            return;
        }
        if (!isset($this->tempDocumentHeaders[$headerIndex])) {
            throw new Exception('header idnex ' . $headerIndex . ' doesn\'t exist');
            /*
            if (!preg_match('/^(.*\<w:sectPr[^>]+>)(.*)/m', $this->tempDocumentMainPart, $match)) {
                return;
            }
            $this->tempDocumentMainPart = $match[1] . '<w:headerReference w:type="default" r:id="rId666"/>' . $match[2];
            $this->tempDocumentHeaders[$headerIndex] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:hdr mc:Ignorable="w14 w15 wp14"></w:hdr>';*/
        }
        if (!preg_match('/^(.*\<w:hdr[^>]+>)(.*)/m', $this->tempDocumentHeaders[$headerIndex], $match)) {
            return;
        }
        $watermark = str_replace('PROFORMA', htmlspecialchars($text, ENT_XML1|ENT_DISALLOWED|ENT_NOQUOTES),
            '<w:sdt><w:sdtPr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w15:appearance xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" w15:val="boundingBox"/><w:placeholder><w:docPart w:val="DefaultPlaceholder_TEXT"/></w:placeholder><w:docPartObj><w:docPartGallery w:val="Watermarks"/><w:docPartUnique w:val="true"/></w:docPartObj><w:rPr><w:rFonts w:cs="Arial" w:eastAsia="Arial"/></w:rPr></w:sdtPr><w:sdtContent xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:pPr><w:rPr><w:rFonts w:cs="Arial" w:eastAsia="Arial"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:cs="Arial" w:eastAsia="Arial"/></w:rPr><mc:AlternateContent xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"><mc:Choice Requires="wpg"><w:drawing><wp:anchor xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" distT="0" distB="0" distL="115200" distR="115200" simplePos="0" relativeHeight="11264" behindDoc="1" locked="0" layoutInCell="1" allowOverlap="1"><wp:simplePos x="0" y="0"/><wp:positionH relativeFrom="margin"><wp:align>center</wp:align></wp:positionH><wp:positionV relativeFrom="margin"><wp:align>center</wp:align></wp:positionV><wp:extent cx="7560548" cy="1497471"/><wp:effectExtent l="0" t="0" r="0" b="0"/><wp:wrapNone/><wp:docPr id="6" name="" hidden="0"/><wp:cNvGraphicFramePr/><a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">		<a:graphicData uri="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"><wps:wsp xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"><wps:cNvPr id="0" name=""/><wps:cNvSpPr/><wps:spPr bwMode="auto"><a:xfrm rot="18899975"><a:off x="0" y="0"/><a:ext cx="7560547" cy="1497471"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></wps:spPr><wps:txbx><w:txbxContent><w:p><w:pPr><w:ind w:left="0" w:right="0" w:firstLine="0"/><w:jc w:val="center"/><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial" w:eastAsia="Arial" w:hint="default"/><w:b w:val="0"/><w:i w:val="0"/><w:strike w:val="false"/><w:sz w:val="204"/><w:u w:val="none"/><w:lang w:val="de-DE"/><w14:textFill xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w14:solidFill><w14:schemeClr w14:val="bg1"><w14:wordShade w14:val="166"/><w14:alpha w14:val="500"/></w14:schemeClr></w14:solidFill></w14:textFill></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial" w:eastAsia="Arial" w:hint="default"/><w:b w:val="0"/><w:i w:val="0"/><w:strike w:val="false"/><w:sz w:val="204"/><w:u w:val="none"/><w:lang w:val="de-DE"/><w14:textFill xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w14:solidFill><w14:schemeClr w14:val="bg1"><w14:wordShade w14:val="166"/><w14:alpha w14:val="500"/></w14:schemeClr></w14:solidFill></w14:textFill></w:rPr></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial" w:eastAsia="Arial" w:hint="default"/><w:b w:val="0"/><w:i w:val="0"/><w:strike w:val="false"/><w:sz w:val="204"/><w:u w:val="none"/><w:lang w:val="de-DE"/><w14:textFill xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w14:solidFill><w14:schemeClr w14:val="bg1"><w14:wordShade w14:val="166"/><w14:alpha w14:val="500"/></w14:schemeClr></w14:solidFill></w14:textFill></w:rPr><w:t xml:space="preserve">PROFORMA</w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial" w:eastAsia="Arial" w:hint="default"/><w:b w:val="0"/><w:i w:val="0"/><w:strike w:val="false"/><w:sz w:val="204"/><w:u w:val="none"/><w:lang w:val="de-DE"/><w14:textFill xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w14:solidFill><w14:schemeClr w14:val="bg1"><w14:wordShade w14:val="166"/><w14:alpha w14:val="500"/></w14:schemeClr></w14:solidFill></w14:textFill></w:rPr></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial" w:eastAsia="Arial" w:hint="default"/><w:b w:val="0"/><w:i w:val="0"/><w:strike w:val="false"/><w:sz w:val="204"/><w:u w:val="none"/><w:lang w:val="de-DE"/><w14:textFill xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w14:solidFill><w14:schemeClr w14:val="bg1"><w14:wordShade w14:val="166"/><w14:alpha w14:val="500"/></w14:schemeClr></w14:solidFill></w14:textFill></w:rPr></w:r></w:p></w:txbxContent></wps:txbx><wps:bodyPr rot="0" spcFirstLastPara="0" vertOverflow="overflow" horzOverflow="clip" vert="horz" wrap="square" lIns="0" tIns="0" rIns="0" bIns="0" numCol="1" spcCol="0" rtlCol="0" fromWordArt="0" anchor="ctr" anchorCtr="0" forceAA="0" upright="0" compatLnSpc="1"/></wps:wsp></a:graphicData></a:graphic></wp:anchor></w:drawing></mc:Choice><mc:Fallback><w:pict><v:shape xmlns:v="urn:schemas-microsoft-com:vml" id="shape 5" o:spid="_x0000_s5" xmlns:o="urn:schemas-microsoft-com:office:office" o:spt="1" style="position:absolute;mso-wrap-distance-left:9.1pt;mso-wrap-distance-top:0.0pt;mso-wrap-distance-right:9.1pt;mso-wrap-distance-bottom:0.0pt;z-index:-11264;o:allowoverlap:true;o:allowincell:true;mso-position-horizontal-relative:margin;mso-position-horizontal:center;mso-position-vertical-relative:margin;mso-position-vertical:center;width:595.3pt;height:117.9pt;rotation:314;v-text-anchor:middle;" coordsize="100000,100000" path="" filled="f" stroked="f"><v:path textboxrect="0,0,0,0"/><v:textbox><w:txbxContent><w:p><w:pPr><w:ind w:left="0" w:right="0" w:firstLine="0"/><w:jc w:val="center"/><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial" w:eastAsia="Arial" w:hint="default"/><w:b w:val="0"/><w:i w:val="0"/><w:strike w:val="false"/><w:sz w:val="204"/><w:u w:val="none"/><w:lang w:val="de-DE"/><w14:textFill xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w14:solidFill><w14:schemeClr w14:val="bg1"><w14:wordShade w14:val="166"/><w14:alpha w14:val="500"/></w14:schemeClr></w14:solidFill></w14:textFill></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial" w:eastAsia="Arial" w:hint="default"/><w:b w:val="0"/><w:i w:val="0"/><w:strike w:val="false"/><w:sz w:val="204"/><w:u w:val="none"/><w:lang w:val="de-DE"/><w14:textFill xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w14:solidFill><w14:schemeClr w14:val="bg1"><w14:wordShade w14:val="166"/><w14:alpha w14:val="500"/></w14:schemeClr></w14:solidFill></w14:textFill></w:rPr></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial" w:eastAsia="Arial" w:hint="default"/><w:b w:val="0"/><w:i w:val="0"/><w:strike w:val="false"/><w:sz w:val="204"/><w:u w:val="none"/><w:lang w:val="de-DE"/><w14:textFill xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w14:solidFill><w14:schemeClr w14:val="bg1"><w14:wordShade w14:val="166"/><w14:alpha w14:val="500"/></w14:schemeClr></w14:solidFill></w14:textFill></w:rPr><w:t xml:space="preserve">PROFORMA</w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial" w:eastAsia="Arial" w:hint="default"/><w:b w:val="0"/><w:i w:val="0"/><w:strike w:val="false"/><w:sz w:val="204"/><w:u w:val="none"/><w:lang w:val="de-DE"/><w14:textFill xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w14:solidFill><w14:schemeClr w14:val="bg1"><w14:wordShade w14:val="166"/><w14:alpha w14:val="500"/></w14:schemeClr></w14:solidFill></w14:textFill></w:rPr></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial" w:eastAsia="Arial" w:hint="default"/><w:b w:val="0"/><w:i w:val="0"/><w:strike w:val="false"/><w:sz w:val="204"/><w:u w:val="none"/><w:lang w:val="de-DE"/><w14:textFill xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w14:solidFill><w14:schemeClr w14:val="bg1"><w14:wordShade w14:val="166"/><w14:alpha w14:val="500"/></w14:schemeClr></w14:solidFill></w14:textFill></w:rPr></w:r></w:p></w:txbxContent></v:textbox></v:shape></w:pict></mc:Fallback></mc:AlternateContent></w:r><w:r><w:rPr><w:rFonts w:cs="Arial" w:eastAsia="Arial"/></w:rPr></w:r></w:p></w:sdtContent></w:sdt>'
        );

        $this->tempDocumentHeaders[$headerIndex] = $match[1] . $watermark . $match[2];
    }
}
