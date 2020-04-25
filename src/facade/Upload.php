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

namespace og\file\facade;

use og\http\Facade;

/**
 * @see \og\file\Upload
 * @mixin \og\file\Upload
 */
class Upload extends Facade
{
    /**
     * 重新初始化
     * @var bool
     */
    protected static $alwaysNewInstance = true;

    protected static function getFacadeClass()
    {
        return 'og\file\Upload';
    }

}