<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
error_reporting(E_ALL ^ E_NOTICE);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * ResponseInterface 转  \think\Response
 * @param ResponseInterface $psrResponse
 * @return \think\Response
 */
function convertResponsePsr2Tp(ResponseInterface $psrResponse): \think\Response
{
    $tpResponse = new \think\Response($psrResponse->getBody(), $psrResponse->getStatusCode());
    foreach ($psrResponse->getHeaders() as $name => $values) {
        $tpResponse->header($name, implode(', ', $values));
    }
    return $tpResponse;
}
