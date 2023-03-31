<?php

namespace app\api\controller;


use app\common\controller\Api;

use think\Controller;
use think\Db;
use think\Log;
use think\Request;
use think\Validate;
use function Sodium\compare;

class Room extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //
    }

    /**
     * 新建房间
     *
     * @return \think\Response
     */
    public function create(Request $request)
    {
        $p = $request->post();
        $rule = [
            'name' => 'require',
            'sk'   => 'require',
        ];
        $msg = [
        ];
        //参数校验
        $validate = new Validate($rule, $msg);
        $result = $validate->check($p);
        if (!$result) {
            $this->error('请求失败', $validate->getError());
        }

        $servers = Db::table('wd_server')->where(['status' => 1])->select();
        if (empty($servers)) {
            $this->error('服务器为空');
        }

        //重复房间
        $count = Db::table('wd_room')->where(['name' => $p['name'], 'delete_time' => 0])->count();
        if ($count) {
            $this->error('名字重复');
        }

        //选个服务器
        $server = $servers[0];
        $ip_int = $server['ip'];
        $ip = long2ip($ip_int);
        $port = $server['port'];

        $id = Db::table('wd_room')->insertGetId([
                'wd_server_id' => $server['id'],
                'name'         => $p['name'],
                'sk'           => $p['sk'],
                'client_ip'    => ip2long($_SERVER['REMOTE_ADDR']),
                'create_time'  => time()
            ]
        );
        if ($id == false) {
            $this->error('增加失败');
        }
        $this->success('success', compact('ip', 'port', 'id'));
    }

    /**
     * 保存新建的资源
     *
     * @param \think\Request $request
     * @return \think\Response
     */
    public function server(Request $request)
    {
        $p = $request->post();
        $rule = [
            'name' => 'require',
            'sk'   => 'require',
        ];
        $msg = [
        ];
        //参数校验
        $validate = new Validate($rule, $msg);
        $result = $validate->check($p);
        if (!$result) {
            $this->error('请求失败', $validate->getError());
        }

//        $servers = Db::table('wd_server')->where(['status' => 1])->select();
//        if (empty($servers)) {
//            $this->error('服务器为空');
//        }

        //重复房间
        $room = Db::table('wd_room')->where(['name' => $p['name'], 'sk' => $p['sk'], 'delete_time' => 0])->find();
        if (!$room) {
            $this->error('无此房间');
        }

        $server = Db::table('wd_server')->where(['id' => $room['wd_server_id']])->find();
        if (empty($server)) {
            $this->error('服务器为空');
        }
        if ($server['status'] != 1) {
            $this->error('服务器已关闭');
        }


        //选个服务器
        $ip_int = $server['ip'];
        $ip = long2ip($ip_int);
        $port = $server['port'];
//
//        $wd_room_id = Db::table('wd_room')->insertGetId([
//                'wd_server_id' => $server['id'],
//                'name'         => $p['name'],
//                'sk'           => $p['sk'],
//                'client_ip'    => ip2long($_SERVER['REMOTE_ADDR']),
//                'create_time'  => time()
//            ]
//        );
//        if ($wd_room_id == false) {
//            $this->error('增加失败');
//        }
        $this->success('success', compact('ip', 'port'));
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
        $room = Db::table('wd_room')->where(['id' => $id])->find();
        if (!$room) {
            $this->error('非法房间id');
        }
        $ret = Db::table('wd_room')->where(['id' => $id])->update(['delete_time' => time()]);
        if ($ret === false) {
            $this->error('服务器为空');
        }
        $this->success('succuss');
    }


}
