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
use og\file\image\extend\Gif;

class Water extends Images
{
    const WATER_NORTHWEST = 1; //常量，标识左上角水印
    const WATER_NORTH = 2; //常量，标识上居中水印
    const WATER_NORTHEAST = 3; //常量，标识右上角水印
    const WATER_WEST = 4; //常量，标识左居中水印
    const WATER_CENTER = 5; //常量，标识居中水印
    const WATER_EAST = 6; //常量，标识右居中水印
    const WATER_SOUTHWEST = 7; //常量，标识左下角水印
    const WATER_SOUTH = 8; //常量，标识下居中水印
    const WATER_SOUTHEAST = 9; //常量，标识右下角水印

    /**
     * 文件后缀
     * @var string
     */
    protected $fileSuffix = 'water';

    /**
     * 资源
     * @var mixed
     */
    protected $source;

    /**
     * gif资源
     * @var Gif
     */
    protected $gifSource;

    /**
     * 字体路径
     * @var string
     */
    protected $fontPath;

    /**
     * 初始化
     * Water constructor.
     * @param $filename
     * @throws ToolException
     */
    public function __construct($filename)
    {
        parent::__construct($filename);

        if ('gif' == $this->getSourceType()) {
            $this->gifSource = new Gif($this->originalFilename);
            $this->source = @imagecreatefromstring($this->gifSource->image());
        } else {
            $fun = "imagecreatefrom" . $this->getSourceType();
            $this->source = call_user_func($fun, $this->originalFilename);
        }

        if (empty($this->source)) {
            throw new ToolException('Failed to create image resources!');
        }
        //字体文件
        $fontPath = dirname(dirname(dirname(dirname(__DIR__)))).'/imagefont/source/font.ttc';
        $this->setFontPath($fontPath);
    }

    /**
     * 打开图像
     * @param $filename
     * @return Water
     * @throws ToolException
     */
    public static function open($filename)
    {
        if ($filename === null || empty($filename) ||  !is_file($filename)) {
            throw new ToolException('File does not exist');
        }

        return new self($filename);
    }

    /**
     * 保存图像
     * @param string $filename 图像保存路径名称
     * @param null|string $type 图像类型
     * @param int $quality 图像质量
     * @return $this
     */
    public function save($filename = '', $image_type = null, $quality = 80)
    {
        //自动获取图像类型
        if (is_null($image_type)) {
            $image_type = $this->getSourceType();
        } else {
            $image_type = strtolower($image_type);
        }

        $filename = $this->getFilename($filename);

        //保存图像
        if ('jpeg' == $image_type || 'jpg' == $image_type) {
            //JPEG图像
            imageinterlace($this->source);
            imagejpeg($this->source, $filename, $quality);

        } elseif ('gif' == $image_type && !empty($this->gifSource)) {

            $this->gifSource->save($filename);

        } elseif ('png' == $image_type) {

            //设定保存完整的 alpha 通道信息
            imagesavealpha($this->source, true);
            //ImagePNG生成图像的质量范围从0到9的
            imagepng($this->source, $filename, min((int)($quality / 10), 9));

        } else {

            $fun = 'image' . $image_type;
            $fun($this->source, $filename);
        }

        return $this;
    }

    /**
     * 添加水印
     *
     * @param  string $source 水印图片路径
     * @param int $locate 水印位置
     * @param int $alpha 透明度
     * @return $this
     */
    public function water($source, $locate = self::WATER_SOUTHEAST, $alpha = 100)
    {
        if (!is_file($source)) {
            throw new ToolException('水印图像不存在');
        }
        //获取水印图像信息
        $info = getimagesize($source);
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new ToolException('非法水印文件');
        }
        //创建水印图像资源
        $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $water = $fun($source);
        //设定水印图像的混色模式
        imagealphablending($water, true);
        /* 设定水印位置 */
        switch ($locate) {
            /* 右下角水印 */
            case self::WATER_SOUTHEAST:
                $x = $this->originalw - $info[0];
                $y = $this->originalh - $info[1];
                break;
            /* 左下角水印 */
            case self::WATER_SOUTHWEST:
                $x = 0;
                $y = $this->originalh - $info[1];
                break;
            /* 左上角水印 */
            case self::WATER_NORTHWEST:
                $x = $y = 0;
                break;
            /* 右上角水印 */
            case self::WATER_NORTHEAST:
                $x = $this->originalw - $info[0];
                $y = 0;
                break;
            /* 居中水印 */
            case self::WATER_CENTER:
                $x = ($this->originalw - $info[0]) / 2;
                $y = ($this->originalh - $info[1]) / 2;
                break;
            /* 下居中水印 */
            case self::WATER_SOUTH:
                $x = ($this->originalw - $info[0]) / 2;
                $y = $this->originalh - $info[1];
                break;
            /* 右居中水印 */
            case self::WATER_EAST:
                $x = $this->originalw - $info[0];
                $y = ($this->originalh - $info[1]) / 2;
                break;
            /* 上居中水印 */
            case self::WATER_NORTH:
                $x = ($this->originalw - $info[0]) / 2;
                $y = 0;
                break;
            /* 左居中水印 */
            case self::WATER_WEST:
                $x = 0;
                $y = ($this->originalh - $info[1]) / 2;
                break;
            default:
                /* 自定义水印坐标 */
                if (is_array($locate)) {
                    list($x, $y) = $locate;
                } else {
                    throw new ToolException('不支持的水印位置类型');
                }
        }
        do {
            //添加水印
            $src = imagecreatetruecolor($info[0], $info[1]);
            // 调整默认颜色
            $color = imagecolorallocate($src, 255, 255, 255);
            imagefill($src, 0, 0, $color);
            imagecopy($src, $this->source, 0, 0, $x, $y, $info[0], $info[1]);
            imagecopy($src, $water, 0, 0, 0, 0, $info[0], $info[1]);
            imagecopymerge($this->source, $src, $x, $y, 0, 0, $info[0], $info[1], $alpha);
            //销毁零时图片资源
            imagedestroy($src);
        } while (!empty($this->gifSource) && $this->gifNext());
        //销毁水印资源
        imagedestroy($water);

        return $this;
    }

    /**
     * 设置字体文件路径
     * @param $fontPath
     * @return $this
     * @throws ToolException
     */
    public function setFontPath($fontPath)
    {
        if(!is_file($fontPath)) {
            throw new ToolException("不存在的字体文件：{$fontPath}");
        }

        $this->fontPath = $fontPath;

        return $this;
    }
    
    /**
     * 图像添加文字
     *
     * @param  string $text 添加的文字
     * @param  integer $size 字号
     * @param  string $color 文字颜色
     * @param int $locate 文字写入位置
     * @param  integer $offset 文字相对当前位置的偏移量
     * @param  integer $angle 文字倾斜角度
     *
     * @return $this
     * @throws ToolException
     */
    public function text($text, $size, $color = '#00000000',
                         $locate = self::WATER_SOUTHEAST, $offset = 0, $angle = 0)
    {

        //获取文字信息
        $info = imagettfbbox($size, $angle, $this->fontPath, $text);
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);
        /* 计算文字初始坐标和尺寸 */
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;
        /* 设定文字位置 */
        switch ($locate) {
            /* 右下角文字 */
            case self::WATER_SOUTHEAST:
                $x += $this->originalw - $w;
                $y += $this->originalh - $h;
                break;
            /* 左下角文字 */
            case self::WATER_SOUTHWEST:
                $y += $this->originalh - $h;
                break;
            /* 左上角文字 */
            case self::WATER_NORTHWEST:
                // 起始坐标即为左上角坐标，无需调整
                break;
            /* 右上角文字 */
            case self::WATER_NORTHEAST:
                $x += $this->originalw - $w;
                break;
            /* 居中文字 */
            case self::WATER_CENTER:
                $x += ($this->originalw - $w) / 2;
                $y += ($this->originalh - $h) / 2;
                break;
            /* 下居中文字 */
            case self::WATER_SOUTH:
                $x += ($this->originalw - $w) / 2;
                $y += $this->originalh - $h;
                break;
            /* 右居中文字 */
            case self::WATER_EAST:
                $x += $this->originalw - $w;
                $y += ($this->originalh - $h) / 2;
                break;
            /* 上居中文字 */
            case self::WATER_NORTH:
                $x += ($this->originalw - $w) / 2;
                break;
            /* 左居中文字 */
            case self::WATER_WEST:
                $y += ($this->originalh - $h) / 2;
                break;
            default:
                /* 自定义文字坐标 */
                if (is_array($locate)) {
                    list($posx, $posy) = $locate;
                    $x += $posx;
                    $y += $posy;
                } else {
                    throw new ToolException('不支持的文字位置类型');
                }
        }
        /* 设置偏移量 */
        if (is_array($offset)) {
            $offset = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox = $oy = $offset;
        }
        /* 设置颜色 */
        if (is_string($color) && 0 === strpos($color, '#')) {
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } elseif (!is_array($color)) {
            throw new ToolException('错误的颜色值');
        }
        do {
            /* 写入文字 */
            $col = imagecolorallocatealpha($this->source, $color[0], $color[1], $color[2], $color[3]);
            imagettftext($this->source, $size, $angle, $x + $ox, $y + $oy, $col, $this->fontPath, $text);
        } while (!empty($this->gifSource) && $this->gifNext());


        return $this;
    }

    /**
     * 切换到GIF的下一帧并保存当前帧
     */
    protected function gifNext()
    {
        ob_start();
        ob_implicit_flush(0);
        imagegif($this->source);
        $img = ob_get_clean();
        $this->gifSource->image($img);
        $next = $this->gifSource->nextImage();
        if ($next) {
            imagedestroy($this->source);
            $this->source = imagecreatefromstring($next);

            return $next;
        } else {
            imagedestroy($this->source);
            $this->source = imagecreatefromstring($this->gifSource->image());

            return false;
        }
    }

    /**
     * 析构方法，用于销毁图像资源
     */
    public function __destruct()
    {
        empty($this->source) || imagedestroy($this->source);
    }
}