<?php

namespace app\index\controller;

use think\Config;
use think\Controller;
use think\Request;

class Server extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $storage = new \OAuth2\Storage\Pdo(array('dsn' => Config::get('database.dsn'), 'username' => Config::get('database.username'), 'password' => Config::get('database.password')));
        $server = new \OAuth2\Server($storage);
        $server->addGrantType(new \OAuth2\GrantType\AuthorizationCode($storage)); // or any grant type you like!
        $server->handleTokenRequest(\OAuth2\Request::createFromGlobals())->send();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function token()
    {
        $publicKey = file_get_contents(ROOT_PATH . '/public.key');
        $privateKey = file_get_contents(ROOT_PATH . '/private.key');

        $storage = new \OAuth2\Storage\Memory(array(
            'keys'               => array(
                'public_key'  => $publicKey,
                'private_key' => $privateKey,
                'encryption_algorithm'  => 'HS256', // "RS256" is the default
            ),
            // add a Client ID for testing
            'client_credentials' => array(
                'CLIENT_ID' => array('client_secret' => 'CLIENT_SECRET')
            ),
        ));

        $server = new \OAuth2\Server($storage, array(
            'use_jwt_access_tokens' => false,
        ));
        $server->addGrantType(new \OAuth2\GrantType\ClientCredentials($storage)); // minimum config

// send the response
        $server->handleTokenRequest(\OAuth2\Request::createFromGlobals())->send();
    }

    /**
     * 保存新建的资源
     *
     * @param \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
