<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Helper class for image operations
 *
 * @package     Tinebase
 */
class Tinebase_ImageHelper
{
    /**
     * preserves ratio and cropes image on the oversize side
     */
    public const RATIOMODE_PRESERVANDCROP = 0;
    /**
     * preserves ratio and does not crop image. Resuling image dimension is less
     * than requested on one dimension as this dim is not filled  
     */
    public const RATIOMODE_PRESERVNOFILL = 1;
    /**
     * max pixels allowed per edge for resize operations
     */
    public const MAX_RESIZE_PX = 2000;
    /**
     * scales given image to given size
     * 
     * @param  Tinebase_Model_Image $_image
     * @param  int    $_width desitination width
     * @param  int    $_height destination height
     * @param  int    $_ratiomode
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function resize(Tinebase_Model_Image $_image, $_width, $_height, $_ratiomode)
    {
        $width = min($_width, self::MAX_RESIZE_PX);
        $height = min($_height, self::MAX_RESIZE_PX);

        $_image->resize($width, $height, $_ratiomode);
    }

    /**
     * returns image metadata
     *
     * @param   string $_blob
     * @return array
     * @throws Tinebase_Exception
     */
    public static function getImageInfoFromBlob($_blob)
    {
        $tmpPath = tempnam(Tinebase_Core::getTempDir(), 'tine20_tmp_gd');
        
        if ($tmpPath === FALSE) {
            throw new Tinebase_Exception('Could not generate temporary file.');
        }
        
        file_put_contents($tmpPath, $_blob);
        
        $imgInfo = @getimagesize($tmpPath);
        unlink($tmpPath);
        if (! $imgInfo || ! in_array($imgInfo['mime'], self::getSupportedImageMimeTypes())) {
            throw new Tinebase_Exception_UnexpectedValue('given blob does not contain valid image data.');
        }
        if (! (isset($imgInfo['channels']) || array_key_exists('channels', $imgInfo))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Image of type ' . $imgInfo['mime']
                    . ' had no channel information. Setting channels to 0.');
            $imgInfo['channels'] = 0;
        }
        return array(
            'width'    => $imgInfo[0],
            'height'   => $imgInfo[1],
            'bits'     => $imgInfo['bits'],
            'channels' => $imgInfo['channels'],
            'mime'     => $imgInfo['mime'],
            'blob'     => $_blob
        );
    }
    /**
     * checks wether given file is an image or not
     * 
     * @param  string $_file image file
     * @return bool
     */
    public static function isImageFile($_file)
    {
        if (! $_file || ! file_exists($_file)) {
            return false;
        }
        try {
            $imgInfo = @getimagesize($_file);
            if ($imgInfo && isset($imgInfo['mime']) && in_array($imgInfo['mime'], self::getSupportedImageMimeTypes())) {
                return true;
            }
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }
        return false;
    }

    /**
     * returns supported image mime types
     *
     * @return array
     */
    public static function getSupportedImageMimeTypes()
    {
        return array('image/png', 'image/jpeg', 'image/gif');
    }

    /**
     * get mime of given file extension
     *
     * @param  string $fileExt
     * @return string
     */
    public static function getMime($fileExt)
    {
        $ext = strtolower(str_replace('/^\./', '', $fileExt));
        return match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            default => '',
        };
    }

    /**
     * parses an image link
     * 
     * @param  string $link
     * @return array
     */
    public static function parseImageLink($link)
    {
        $params = array();
        $link = parse_url((string)$link, PHP_URL_QUERY);
        if (is_string($link)) {
            parse_str($link, $params);
        }
        $params['isNewImage'] = false;
        if (isset($params['application']) && $params['application'] == 'Tinebase') {
            $params['isNewImage'] = true;
        }
        return $params;
    }

    /**
     * returns binary image data from a image identified by a imagelink
     * 
     * @param   array  $imageParams
     * @return  string binary data
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public static function getImageData($imageParams)
    {
        $tempFile = Tinebase_TempFile::getInstance()->getTempFile($imageParams['id']);
        
        if (! Tinebase_ImageHelper::isImageFile($tempFile->path)) {
            throw new Tinebase_Exception_UnexpectedValue('Given file is not an image.');
        }
        
        return file_get_contents($tempFile->path);
    }

    /**
     * get data url from given image path
     *
     * @param string $imagePath
     * @return string
     * @throws Tinebase_Exception
     */
    public static function getDataUrl($imagePath)
    {
        if (str_starts_with($imagePath, 'data:')) {
            return $imagePath;
        }

        $cacheId = md5(self::class . 'getDataUrl' . $imagePath);
        $dataUrl = Tinebase_Core::getCache()->load($cacheId);

        if (! $dataUrl) {
            $blob = Tinebase_Helper::getFileOrUriContents($imagePath);
            $mime = '';

            if (str_ends_with($imagePath, '.ico')) {
                $mime = 'image/x-icon';
            } elseif ($blob) {
                $info = self::getImageInfoFromBlob($blob);
                $mime = $info['mime'];
            }

            $dataUrl = 'data:' . $mime . ';base64,' . base64_encode((string) $blob);
            Tinebase_Core::getCache()->save($dataUrl, $cacheId);
        }

        return $dataUrl;
    }

    /**
     * create watermark
     *
     * @param Tinebase_Model_Image $_image
     * @param string $font
     * @param float $fontsize
     * @param string $watermarktext
     * @param array $configWatermark
     *
     */
    public static function createWatermark(Tinebase_Model_Image $_image, $font, $fontsize, $watermarktext, $configWatermark = null)
    {

        $tmpPath = tempnam(Tinebase_Core::getTempDir(), 'tine20_tmp_gd');
        file_put_contents($tmpPath, $_image->blob);

        switch ($_image->mime) {
            case ('image/png'):
                $img = imagecreatefrompng($tmpPath);
                $imgDumpFunction = 'imagepng';
                break;
            case ('image/jpeg'):
                $img = imagecreatefromjpeg($tmpPath);
                $imgDumpFunction = 'imagejpeg';
                break;
            case ('image/gif'):
                $img = imagecreatefromgif($tmpPath);
                $imgDumpFunction = 'imagegif';
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument("Unsupported image type: " . $_image->mime);
        }

        $color = imagecolorallocate($img, 255, 255, 255);
        $watermarktextCount = strlen($watermarktext);
        $positionX1 = 0;
        $positionY2 = 0;
        if (isset($configWatermark)) {
            if (isset($configWatermark['x'])) {
                $positionX1 = $configWatermark['x'] - ($watermarktextCount * 9);
            }
            if (isset($configWatermark['y'])) {
                $positionY2 = $configWatermark['y'];
            }
        }

        $positionY1 = $positionY2 - 10;
        $positionX2 = $_image->width;

        imagettftext($img, $fontsize, 0, $positionX1, $positionY2, $color, $font, $watermarktext);
        $imgDumpFunction($img, $tmpPath);
        $_image->blob = file_get_contents($tmpPath);
        unlink($tmpPath);
    }
}
