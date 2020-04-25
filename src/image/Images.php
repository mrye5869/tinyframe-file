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

use og\helper\Str;
use og\facade\Env;
use og\error\ToolException;

abstract class Images
{
    /**
     * 原图路径
     * @var string
     */
    protected $originalFilename;

    /**
     * 文件后缀
     * @var string
     */
    protected $fileSuffix;

    /**
     * 新图路径
     * @var string
     */
    protected $fileName;

    /**
     * 宽
     * @var int
     */
    protected $originalw;

    /**
     * 高
     * @var int
     */
    protected $originalh;

    /**
     * 原图大小
     * @var string
     */
    protected $originalSize;

    /**
     * 图片类型
     * @var int
     */
    protected $sourceType;

    /**
     * 图片MIME信息
     * @var string
     */
    protected $sourceMime;


    public function __construct($filename)
    {
        if (!function_exists('finfo_open')) {
            throw new ToolException('FileInfo extension is not enabled');
        }

        $this->originalFilename = $filename;

        $image_info = getimagesize($filename);

        if (!$image_info) {
            throw new ToolException('Could not read file');
        }

        $this->originalSize = filesize($filename);
        list(
            $this->originalw,
            $this->originalh,
            $this->sourceType
            ) = $image_info;
        $this->sourceMime = $image_info['mime'];

    }


    /**
     * 返回图片宽度
     * @return int
     */
    public function getOriginalw()
    {
        return $this->originalw;
    }

    /**
     * 返回图片高度
     * @return int
     */
    public function getOriginalh()
    {
        return $this->originalh;
    }

    /**
     * 返回图片大小，kb计算
     * @return float
     */
    public function getOriginalSize()
    {
        return floor($this->originalSize /  (1024));
    }

    /**
     * 获取Mime
     * @return string
     */
    public function getSourceMime()
    {
        return $this->sourceMime;
    }

    /**
     * 返回图片类型
     * @return string
     */
    public function getSourceType()
    {
        return image_type_to_extension($this->sourceType, false);
    }

    /**
     * 设置文件后缀
     * @param $fileSuffix
     * @return $this
     */
    public function setFileSuffix($fileSuffix)
    {
        $this->fileSuffix = $fileSuffix;

        return $this;
    }

    /**
     * 获取原图src
     * @return string
     */
    public function getOriginalSrc()
    {
        return $this->parseSrc($this->originalFilename);
    }

    /**
     * 获取原图的路径
     * @return mixed
     */
    public function getOriginalFilename()
    {
        return $this->originalFilename;
    }

    /**
     * 获取新图的src
     * @return string
     */
    public function getSrcname()
    {
        return $this->parseSrc($this->fileName);
    }

    /**
     * 获取新图路径
     * @param $filename
     * @return string
     */
    public function getFilename($filename)
    {
        if(empty($this->fileName)) {

            if(Str::startsWith($filename, Env::get('root_path'))) {

                $this->fileName = $filename;

            } elseif(!empty($filename)) {

                $this->fileName = Env::get('root_path').$filename;

            } else {

                $pathinfo = pathinfo($this->originalFilename);
                $patharr = [
                    $pathinfo['dirname'],
                    '/'.$pathinfo['filename'],
                    '.'.$this->fileSuffix,
                    '.'.$pathinfo['extension'],
                ];

                $this->fileName = implode('', $patharr);
            }
        }

        return $this->fileName;
    }

    /**
     * 解析src
     * @param $filename
     * @return string
     */
    protected function parseSrc($filename)
    {
        //解析path
        $pathArr = explode(Env::get('root_path'), $filename);
        list(, $srcName) = $pathArr;

        return '/'.$srcName;
    }
}

