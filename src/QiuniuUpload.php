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

namespace og\file;

use og\error\ToolException;
use og\helper\Str;

class QiuniuUpload
{

    /**
     * 配置数组
     * @var array
     */
    protected $config = [
        'access'    => 'xxx',
        'secret'    => 'xxx',
        'bucket'    => 'xxx',
        'domain'    => 'http://qiniu.mrye.xin',
    ];

    /**
     * 七牛云token
     * @var string
     */
    protected $token;

    /**
     * 七牛文件名称
     * @var string
     */
    protected $fileName;

    /**
     * 资源路径
     * @var string
     */
    protected $src;


    /**
     * QiuniuUpload constructor.
     * @param array $config
     * @param string $sdkPath
     * @throws ToolException
     */
    public function __construct($config = [], $sdkPath = '')
    {
        $this->setConfig($config);

        $this->setSdkPath($sdkPath);
    }

    /**
     * 获取上传token
     * @return string|null
     */
    protected function getToken()
    {
        if($this->token == null) {
            
            $auth = new \Qiniu\Auth($this->config['access'], $this->config['secret']);
            //获取上传token
            $this->token = $auth->uploadToken($this->config['bucket'], null, 3600);
        }

        return $this->token;
    }

    /**
     * 多文件上传到七牛云
     * @param $paths
     * @param bool $isUnlink
     * @return array
     * @throws ToolException
     */
    public function uploads($paths, $isUnlink = false)
    {
        if(!is_array($paths)) {
            //不是数组时抛出异常

            throw new ToolException('Parameter one must be an array');
        }

        $result = [];
        foreach ($paths as $key => $path) {
            $item = [
                'path'  => $path,
                'error' => '',
                'src'   => '',
            ];
            //捕获
            try {

                $this->upload($path, '', $isUnlink);
                //src赋值
                $item['src'] = $this->getSrcname();

            } catch (\Exception $exception) {
                //发生错误
                $item['error'] = $exception->getMessage();
            }
            
            $result[] = $item;
        }

        return $result;
    }

    /**
     * 七牛云上传
     *
     * @param $path
     * @param string $fileName
     * @param bool $isUnlink
     * @return $this
     * @throws ToolException
     */
    public function upload($path, $fileName = '', $isUnlink = false)
    {
        if(!is_file($path)) {
            //抛出异常
            throw new ToolException('file does not exist:'.$path);
        }

        if(empty($fileName)) {
            //自动获取上传文件名称
            $fileInfo = pathinfo($path);
            $fileName = $fileInfo['basename'];
        }
        $uploadMgr = new \Qiniu\Storage\UploadManager();
        list($result, $error) = $uploadMgr->putFile($this->getToken(), $fileName, $path);

        if(!empty($error)) {
            //抛出异常，上传时发送错误
            throw new ToolException($error->getError());
        }

        $this->fileName = $result['key'];

        if(Str::endsWith($this->config['domain'], '/')) {
            //域名末尾有/
            $this->src = $this->config['domain'].$this->fileName;

        } else {
            //域名末尾未有/
            $this->src = $this->config['domain'].'/'.$this->fileName;
        }

        if($isUnlink) {
            //删除本地文件
            @unlink($path);
        }

        return $this;
    }

    /**
     * 获取文件名称
     * @return string
     */
    public function getFilename()
    {
        return $this->fileName;
    }

    /**
     * 获取访问路径
     * @return string
     */
    public function getSrcname()
    {
        return $this->src;
    }
    
    /**
     * 设置七牛配置信息
     * @param $config
     * @return $this
     */
    public function setConfig($config)
    {
        if(!empty($config) && is_array($config)) {
            //合并
            $this->config = array_merge($this->config, $config);
        }

        return $this;
    }

    /**
     * 设置skd路径
     * @param $sdkPath
     * @return $this
     */
    public function setSdkPath($sdkPath)
    {
        if($sdkPath) {
            //加载自定义sdk
            if(!is_file($sdkPath)) {
                //sdk文件不存在，抛出异常

                throw new ToolException('qiuniu sdk file does not exist:'.$sdkPath);
            }

            include $sdkPath;

        } else {
            //加载微擎内置七牛
            load()->library('qiniu');
        }

        if(!class_exists('Qiniu\Auth') || !class_exists('Qiniu\Storage\UploadManager')) {
            //类不存在，抛出异常

            throw new ToolException('Qiniu\Auth or Qiniu\Storage\UploadManager class does not exist');
        }

        return $this;
    }
}