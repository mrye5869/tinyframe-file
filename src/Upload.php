<?php
// +----------------------------------------------------------------------
// | zibi [ WE CAN DO IT MORE SIMPLE]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://xmzibi.com All rights reserved.
// +----------------------------------------------------------------------
// | Author：MrYe       <email：55585190@qq.com>
// +----------------------------------------------------------------------

namespace og\file;

use og\helper\Arr;
use og\http\filesystem\UploadedFile;
use og\error\ToolException;
use og\helper\Str;

class Upload
{
    /**
     * 禁止上传的后缀
     * @var array
     */
    protected $harmType = ['asp', 'php', 'jsp', 'js', 'css', 'php3', 'php4', 'php5', 'ashx', 'aspx', 'exe', 'cgi'];

    /**
     * 上传根路径
     * @var null|string
     */
    protected $rootPath;

    /**
     * 文件对象
     * @var UploadedFile
     */
    protected $uploadedFile;


    /**
     * 初始化
     * Upload constructor.
     * @param string $rootPath
     * @param array $harmType
     */
    public function __construct($rootPath = '')
    {
        //设置上传规则
        $harmType = config('app.upload.harm_type');
        $this->setHarmType($harmType);

        if(!is_dir($rootPath)) {
            //获取上传默认路径
            $default = env('root_path').'attachment/{type}/{uniacid}/{module_name}/';
            $rootPath = config('app.upload.root_path', $default);
        }

        $this->setRootPath($rootPath);

    }

    /**
     * 上传文件
     * @param string $path
     * @param string|array|UploadedFile $uploadedFile
     * @param string $name
     * @return \og\http\filesystem\File
     * @throws ToolException
     */
    public function putFile($path, $uploadedFile, $name = 'md5')
    {
        if($uploadedFile instanceof UploadedFile) {
            //对象
            $this->uploadedFile = $uploadedFile;

        } else if(is_array($uploadedFile)) {
            //数组
            $this->uploadedFile = new UploadedFile($uploadedFile['tmp_name'], $uploadedFile['name'], $uploadedFile['type'], $uploadedFile['error']);

        } else {
            //字符串
            $this->uploadedFile = request()->file($uploadedFile);

            if(is_array($this->uploadedFile)) {
                //多文件
                throw new ToolException('Please use putFiles function to upload multiple files');

            }

        }

        if(!$this->uploadedFile) {
            //找不到上传的文件
            throw new ToolException('Please select the uploaded file');
        }

        if(in_array($this->uploadedFile->extension(), $this->harmType)) {
            //禁止上传的文件
            throw new ToolException('Files forbidden to upload');
        }

        $pathReplace = $this->getPathReplace();
        $directory = $this->rootPath.$path;
        //替换
        $directory = str_replace(array_keys($pathReplace), array_values($pathReplace), $directory);

        return $this->uploadedFile->move($directory, $this->uploadedFile->hashName($name));
    }

    /**
     * 上传多文件
     * @param string $path
     * @param string|array|UploadedFile $uploadedFiles
     * @param string $name
     * @return array
     * @throws ToolException
     */
    public function putFiles($path, $uploadedFiles, $name = 'md5')
    {

        if($uploadedFiles instanceof UploadedFile) {
            //对象
            $uploadedFiles = [$uploadedFiles];

        } else if(is_array($uploadedFiles)){
            //数组
            $dimension = Arr::dimension($uploadedFiles);
            $uploadedFiles = $dimension == 1 ? [$uploadedFiles] : $uploadedFiles;

        } else {
            //非数组
            $uploadedFiles = request()->file($uploadedFiles);
        }

        if(!$uploadedFiles) {
            //找不到上传的文件
            throw new ToolException('Please select the uploaded files');
        }

        $result = [];
        $number = 1;
        foreach ($uploadedFiles as $uploadedFile) {
            //捕获异常
            try {
                //存入结果集
                $result[] = $this->putFile($path, $uploadedFile, $name);

            } catch (\Exception $exception) {
                //继续抛出异常
                throw new ToolException($name.'st file upload:'.$exception->getMessage());
            }

            $number ++;
        }

        return !empty($result) ? $result : false;
    }

    /**
     * 设置上传根路径
     * @param $rootPath
     * @return null|string
     */
    public function setRootPath($rootPath)
    {
        $this->rootPath = Str::endsWith($rootPath, '/') ? $rootPath : $rootPath.'/';

        return $this;
    }

    /**
     * 设置禁止上传的文件类型
     * @param $harmType
     * @return $this
     */
    public function setHarmType($harmType)
    {
        if(!empty($harmType) && is_array($harmType)) {
            //合并
            $this->config = array_merge($this->harmType, $harmType);
        }

        return $this;
    }

    /**
     * 获取目录替换
     * @param $name
     * @return array
     */
    protected function getPathReplace()
    {

        return [
            '{type}'        => $this->getFileType().'s',
            '{time}'        => time(),
            '{md5_time}'    => md5(time()),
            '{date}'        => date('Y-m-d', time()),
            '{uniacid}'     => W('uniacid'),
            '{module_name}' => env('module_name'),
        ];
    }

    /**
     * 获取文件类型
     * @return mixed
     */
    protected function getFileType()
    {
        $mime = $this->uploadedFile->getOriginalMime();
        $mimeArr = explode('/', $mime);
        list($type) = $mimeArr;

        return $type;
    }


}