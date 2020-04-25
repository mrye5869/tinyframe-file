<?php
// +----------------------------------------------------------------------
// | zibi [ WE CAN DO IT MORE SIMPLE]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2020 http://xmzibi.com/ All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: MrYe    <email：55585190@qq.com>
// +----------------------------------------------------------------------
namespace og\file\image;

use og\error\ToolException;

class Thumb extends Images
{
    /**
     *
     */
    const CROPTOP = 1;
    const CROPCENTRE = 2;
    const CROPCENTER = 2;
    const CROPBOTTOM = 3;
    const CROPLEFT = 4;
    const CROPRIGHT = 5;
    const CROPTOPCENTER = 6;
    const IMG_FLIP_HORIZONTAL = 0;
    const IMG_FLIP_VERTICAL = 1;
    const IMG_FLIP_BOTH = 2;

    public $qualityJpg = 85;
    public $qualityWebp = 85;
    public $qualityPng = 6;
    public $qualityTruecolor = true;
    public $gammaCorrect = true;

    public $interlace = 1;

    protected $sourceInfo;
    protected $fileSuffix = 'thumb';
    protected $sourceImage;

    protected $destx = 0;
    protected $desty = 0;

    protected $sourcex;
    protected $sourcey;

    protected $destw;
    protected $desth;

    protected $sourcew;
    protected $sourceh;

    protected $filters = [];



    /**
     * 打开文件
     *
     * @param $filename
     * @return $this
     */
    public static function open($filename)
    {
        if ($filename === null || empty($filename) ||  !is_file($filename)) {
            throw new ToolException('File does not exist');
        }

        return new self($filename);
    }
    

    /**
     * 添加过滤功能，以便在将图像保存到文件之前使用
     *
     * @param callable $filter
     * @return $this
     */
    public function addFilter(callable $filter)
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * 应用筛选器
     *
     * @param $image resource an image resource identifier
     * @param $filterType filter type and default value is IMG_FILTER_NEGATE
     */
    protected function applyFilter($image, $filterType = IMG_FILTER_NEGATE)
    {
        foreach ($this->filters as $function) {
            $function($image, $filterType);
        }
    }

    /**
     * 将图像源及其属性加载到实例化对象
     *
     * @param string $filename
     * @return ImageResize
     * @throws ToolException
     */
    public function __construct($filename)
    {
        parent::__construct($filename);

        switch ($this->sourceType) {
            case IMAGETYPE_GIF:
                $this->sourceImage = imagecreatefromgif($this->originalFilename);
                break;

            case IMAGETYPE_JPEG:
                $this->sourceImage = $this->imageCreateJpegfromExif($this->originalFilename);

                // set new width and height for image, maybe it has changed
                $this->originalw = imagesx($this->sourceImage);
                $this->originalh = imagesy($this->sourceImage);

                break;

            case IMAGETYPE_PNG:
                $this->sourceImage = imagecreatefrompng($this->originalFilename);
                break;
            default:
                throw new ToolException('Unsupported image type');
        }

        if (!$this->sourceImage) {
            throw new ToolException('Failed to create image resources');
        }

        return $this->resize($this->getOriginalw(), $this->getOriginalh());
    }


    protected function imageCreateJpegfromExif($filename)
    {
        $img = imagecreatefromjpeg($filename);

        if (!function_exists('exif_read_data') || !isset($this->sourceInfo['APP1'])  || strpos($this->sourceInfo['APP1'], 'Exif') !== 0) {
            return $img;
        }

        try {
            $exif = @exif_read_data($filename);
        } catch (\Exception $e) {
            $exif = null;
        }

        if (!$exif || !isset($exif['Orientation'])) {
            return $img;
        }

        $orientation = $exif['Orientation'];

        if ($orientation === 6 || $orientation === 5) {
            $img = imagerotate($img, 270, null);
        } elseif ($orientation === 3 || $orientation === 4) {
            $img = imagerotate($img, 180, null);
        } elseif ($orientation === 8 || $orientation === 7) {
            $img = imagerotate($img, 90, null);
        }

        if ($orientation === 5 || $orientation === 4 || $orientation === 7) {
            if(function_exists('imageflip')) {
                imageflip($img, IMG_FLIP_HORIZONTAL);
            } else {
                $this->imageFlip($img, IMG_FLIP_HORIZONTAL);
            }
        }

        return $img;
    }

    /**
     * 保存图片
     *
     * @param string $filename
     * @param string $image_type
     * @param integer $quality
     * @param integer $permissions
     * @param boolean $exact_size
     * @return static
     */
    public function save($filename = '', $image_type = null, $quality = null, $permissions = null, $exact_size = false)
    {
        $image_type = $image_type ?: $this->sourceType;
        $quality = is_numeric($quality) ? (int) abs($quality) : null;

        switch ($image_type) {
            case IMAGETYPE_GIF:
                if( !empty($exact_size) && is_array($exact_size) ){
                    $dest_image = imagecreatetruecolor($exact_size[0], $exact_size[1]);
                } else{
                    $dest_image = imagecreatetruecolor($this->getDestWidth(), $this->getDestHeight());
                }

                $background = imagecolorallocatealpha($dest_image, 255, 255, 255, 1);
                imagecolortransparent($dest_image, $background);
                imagefill($dest_image, 0, 0, $background);
                imagesavealpha($dest_image, true);
                break;

            case IMAGETYPE_JPEG:
                if( !empty($exact_size) && is_array($exact_size) ){
                    $dest_image = imagecreatetruecolor($exact_size[0], $exact_size[1]);
                    $background = imagecolorallocate($dest_image, 255, 255, 255);
                    imagefilledrectangle($dest_image, 0, 0, $exact_size[0], $exact_size[1], $background);
                } else{
                    $dest_image = imagecreatetruecolor($this->getDestWidth(), $this->getDestHeight());
                    $background = imagecolorallocate($dest_image, 255, 255, 255);
                    imagefilledrectangle($dest_image, 0, 0, $this->getDestWidth(), $this->getDestHeight(), $background);
                }
                break;
            case IMAGETYPE_PNG:
                if (!$this->qualityTruecolor && !imageistruecolor($this->sourceImage)) {
                    if( !empty($exact_size) && is_array($exact_size) ){
                        $dest_image = imagecreate($exact_size[0], $exact_size[1]);
                    } else{
                        $dest_image = imagecreate($this->getDestWidth(), $this->getDestHeight());
                    }
                } else {
                    if( !empty($exact_size) && is_array($exact_size) ){
                        $dest_image = imagecreatetruecolor($exact_size[0], $exact_size[1]);
                    } else{
                        $dest_image = imagecreatetruecolor($this->getDestWidth(), $this->getDestHeight());
                    }
                }

                imagealphablending($dest_image, false);
                imagesavealpha($dest_image, true);

                $background = imagecolorallocatealpha($dest_image, 255, 255, 255, 127);
                imagecolortransparent($dest_image, $background);
                imagefill($dest_image, 0, 0, $background);
                break;
        }

        imageinterlace($dest_image, $this->interlace);

        if ($this->gammaCorrect) {
            imagegammacorrect($this->sourceImage, 2.2, 1.0);
        }

        if( !empty($exact_size) && is_array($exact_size) ) {
            if ($this->getOriginalh() < $this->getOriginalw()) {
                $this->destx = 0;
                $this->desty = ($exact_size[1] - $this->getDestHeight()) / 2;
            }
            if ($this->getOriginalh() > $this->getOriginalw()) {
                $this->destx = ($exact_size[0] - $this->getDestWidth()) / 2;
                $this->desty = 0;
            }
        }

        imagecopyresampled(
            $dest_image,
            $this->sourceImage,
            $this->destx,
            $this->desty,
            $this->sourcex,
            $this->sourcey,
            $this->getDestWidth(),
            $this->getDestHeight(),
            $this->sourcew,
            $this->sourceh
        );

        if ($this->gammaCorrect) {
            imagegammacorrect($dest_image, 1.0, 2.2);
        }


        $this->applyFilter($dest_image);

        $filename = $this->getFilename($filename);

        switch ($image_type) {
            case IMAGETYPE_GIF:
                imagegif($dest_image, $filename);
                break;

            case IMAGETYPE_JPEG:
                if ($quality === null || $quality > 100) {
                    $quality = $this->qualityJpg;
                }

                imagejpeg($dest_image, $filename, $quality);
                break;
            case IMAGETYPE_PNG:
                if ($quality === null || $quality > 9) {
                    $quality = $this->qualityPng;
                }

                imagepng($dest_image, $filename, $quality);
                break;
        }

        if ($permissions) {
            chmod($filename, $permissions);
        }

        imagedestroy($dest_image);

        return $this;
    }

    /**
     * 将图像转换为字符串
     *
     * @param int $image_type
     * @param int $quality
     * @return string
     */
    public function getImageAsString($image_type = null, $quality = null)
    {
        $string_temp = tempnam(sys_get_temp_dir(), '');

        $this->save($string_temp, $image_type, $quality);

        $string = file_get_contents($string_temp);

        unlink($string_temp);

        return $string;
    }

    /**
     * 使用当前设置将图像转换为字符串
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getImageAsString();
    }

    /**
     * 将图像输出到浏览器
     * @param string $image_type
     * @param integer $quality
     */
    public function output($image_type = null, $quality = null)
    {
        $image_type = $image_type ?: $this->sourceType;

        header('Content-Type: ' . image_type_to_mime_type($image_type));

        $this->save(null, $image_type, $quality);
    }

    /**
     * 根据的短边（短边成比例）调整图像大小
     *
     * @param integer $max_short
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resizeToShortSide($max_short, $allow_enlarge = false)
    {
        if ($this->getOriginalh() < $this->getOriginalw()) {
            $ratio = $max_short / $this->getOriginalh();
            $long = $this->getOriginalw() * $ratio;

            $this->resize($long, $max_short, $allow_enlarge);
        } else {
            $ratio = $max_short / $this->getOriginalw();
            $long = $this->getOriginalh() * $ratio;

            $this->resize($max_short, $long, $allow_enlarge);
        }

        return $this;
    }

    /**
     * 根据的长边调整图像大小（短边成比例）
     *
     * @param integer $max_long
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resizeToLongSide($max_long, $allow_enlarge = false)
    {
        if ($this->getOriginalh() > $this->getOriginalw()) {
            $ratio = $max_long / $this->getOriginalh();
            $short = $this->getOriginalw() * $ratio;

            $this->resize($short, $max_long, $allow_enlarge);
        } else {
            $ratio = $max_long / $this->getOriginalw();
            $short = $this->getOriginalh() * $ratio;

            $this->resize($max_long, $short, $allow_enlarge);
        }

        return $this;
    }

    /**
     * 根据的高度调整图像大小（宽度成比例）
     *
     * @param integer $height
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resizeToHeight($height, $allow_enlarge = false)
    {
        $ratio = $height / $this->getOriginalh();
        $width = $this->getOriginalw() * $ratio;

        $this->resize($width, $height, $allow_enlarge);

        return $this;
    }

    /**
     * 根据的宽度调整图像大小（高度成比例）
     *
     * @param integer $width
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resizeToWidth($width, $allow_enlarge = false)
    {
        $ratio  = $width / $this->getOriginalw();
        $height = $this->getOriginalh() * $ratio;

        $this->resize($width, $height, $allow_enlarge);

        return $this;
    }

    /**
     * 调整图像的大小以使其在给定的尺寸内最适合
     *
     * @param integer $max_width
     * @param integer $max_height
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resizeToBestFit($max_width, $max_height, $allow_enlarge = false)
    {
        if ($this->getOriginalw() <= $max_width && $this->getOriginalh() <= $max_height && $allow_enlarge === false) {
            return $this;
        }

        $ratio  = $this->getOriginalh() / $this->getOriginalw();
        $width = $max_width;
        $height = $width * $ratio;

        if ($height > $max_height) {
            $height = $max_height;
            $width = $height / $ratio;
        }

        return $this->resize($width, $height, $allow_enlarge);
    }

    /**
     * 根据给定比例调整图像大小（按比例）
     *
     * @param integer|float $scale
     * @return static
     */
    public function scale($scale)
    {
        $width  = $this->getOriginalw() * $scale / 100;
        $height = $this->getOriginalh() * $scale / 100;

        $this->resize($width, $height, true);

        return $this;
    }

    /**
     * 根据的宽度和高度调整图像大小
     *
     * @param integer $width
     * @param integer $height
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resize($width, $height, $allow_enlarge = false)
    {
        if (!$allow_enlarge) {
            // if the user hasn't explicitly allowed enlarging,
            // but either of the dimensions are larger then the original,
            // then just use original dimensions - this logic may need rethinking

            if ($width > $this->getOriginalw() || $height > $this->getOriginalh()) {
                $width  = $this->getOriginalw();
                $height = $this->getOriginalh();
            }
        }

        $this->sourcex = 0;
        $this->sourcey = 0;

        $this->destw = $width;
        $this->desth = $height;

        $this->sourcew = $this->getOriginalw();
        $this->sourceh = $this->getOriginalh();

        return $this;
    }

    /**
     * 根据宽度、高度和裁剪位置裁剪图像
     *
     * @param integer $width
     * @param integer $height
     * @param boolean $allow_enlarge
     * @param integer $position
     * @return static
     */
    public function crop($width, $height, $allow_enlarge = false, $position = self::CROPCENTER)
    {
        if (!$allow_enlarge) {
            // this logic is slightly different to resize(),
            // it will only reset dimensions to the original
            // if that particular dimenstion is larger

            if ($width > $this->getOriginalw()) {
                $width  = $this->getOriginalw();
            }

            if ($height > $this->getOriginalh()) {
                $height = $this->getOriginalh();
            }
        }

        $ratio_source = $this->getOriginalw() / $this->getOriginalh();
        $ratio_dest = $width / $height;

        if ($ratio_dest < $ratio_source) {
            $this->resizeToHeight($height, $allow_enlarge);

            $excess_width = ($this->getDestWidth() - $width) / $this->getDestWidth() * $this->getOriginalw();

            $this->sourcew = $this->getOriginalw() - $excess_width;
            $this->sourcex = $this->getCropPosition($excess_width, $position);

            $this->destw = $width;
        } else {
            $this->resizeToWidth($width, $allow_enlarge);

            $excess_height = ($this->getDestHeight() - $height) / $this->getDestHeight() * $this->getOriginalh();

            $this->sourceh = $this->getOriginalh() - $excess_height;
            $this->sourcey = $this->getCropPosition($excess_height, $position);

            $this->desth = $height;
        }

        return $this;
    }

    /**
     * 根据的宽度、高度、x和y裁剪图像
     *
     * @param integer $width
     * @param integer $height
     * @param integer $x
     * @param integer $y
     * @return static
     */
    public function freecrop($width, $height, $x = false, $y = false)
    {
        if ($x === false || $y === false) {
            return $this->crop($width, $height);
        }
        $this->sourcex = $x;
        $this->sourcey = $y;
        if ($width > $this->getOriginalw() - $x) {
            $this->sourcew = $this->getOriginalw() - $x;
        } else {
            $this->sourcew = $width;
        }

        if ($height > $this->getOriginalh() - $y) {
            $this->sourceh = $this->getOriginalh() - $y;
        } else {
            $this->sourceh = $height;
        }

        $this->destw = $width;
        $this->desth = $height;

        return $this;
    }

    /**
     * Gets width of the destination image
     *
     * @return integer
     */
    public function getDestWidth()
    {
        return $this->destw;
    }

    /**
     * 获取目标图像的高度
     * @return integer
     */
    public function getDestHeight()
    {
        return $this->desth;
    }

    /**
     * 根据给定位置获取裁剪位置（X或Y）
     *
     * @param integer $expectedSize
     * @param integer $position
     * @return float|integer
     */
    protected function getCropPosition($expectedSize, $position = self::CROPCENTER)
    {
        $size = 0;
        switch ($position) {
            case self::CROPBOTTOM:
            case self::CROPRIGHT:
                $size = $expectedSize;
                break;
            case self::CROPCENTER:
            case self::CROPCENTRE:
                $size = $expectedSize / 2;
                break;
            case self::CROPTOPCENTER:
                $size = $expectedSize / 4;
                break;
        }
        return $size;
    }

    /**
     *  如果PHP版本低于5.5，则使用给定模式翻转图像
     *
     * @param  resource $image
     * @param  integer  $mode
     * @return null
     */
    protected function imageFlip($image, $mode)
    {
        switch($mode) {
            case self::IMG_FLIP_HORIZONTAL: {
                $max_x = imagesx($image) - 1;
                $half_x = $max_x / 2;
                $sy = imagesy($image);
                $temp_image = imageistruecolor($image)? imagecreatetruecolor(1, $sy): imagecreate(1, $sy);
                for ($x = 0; $x < $half_x; ++$x) {
                    imagecopy($temp_image, $image, 0, 0, $x, 0, 1, $sy);
                    imagecopy($image, $image, $x, 0, $max_x - $x, 0, 1, $sy);
                    imagecopy($image, $temp_image, $max_x - $x, 0, 0, 0, 1, $sy);
                }
                break;
            }
            case self::IMG_FLIP_VERTICAL: {
                $sx = imagesx($image);
                $max_y = imagesy($image) - 1;
                $half_y = $max_y / 2;
                $temp_image = imageistruecolor($image)? imagecreatetruecolor($sx, 1): imagecreate($sx, 1);
                for ($y = 0; $y < $half_y; ++$y) {
                    imagecopy($temp_image, $image, 0, 0, 0, $y, $sx, 1);
                    imagecopy($image, $image, 0, $y, 0, $max_y - $y, $sx, 1);
                    imagecopy($image, $temp_image, 0, $max_y - $y, 0, 0, $sx, 1);
                }
                break;
            }
            case self::IMG_FLIP_BOTH: {
                $sx = imagesx($image);
                $sy = imagesy($image);
                $temp_image = imagerotate($image, 180, 0);
                imagecopy($image, $temp_image, 0, 0, 0, 0, $sx, $sy);
                break;
            }
            default:
                return null;
        }
        imagedestroy($temp_image);
    }

    /**
     * 启用或不启用图像上的gamma颜色校正，默认情况下启用
     *
     * @param bool $enable
     * @return static
     */
    protected function gamma($enable = true)
    {
        $this->gammaCorrect = $enable;

        return $this;
    }


}