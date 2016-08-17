<?php
namespace app\controller;

use think\Request;
use app\lib\Proxy;
use app\responser;

/**
 * 主题页面控制器
 * @package app\controller
 */
class Read extends Base
{
    /**
     * 展示主题页面
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $fpage = input('fpage/d', 0);
        $response = Proxy::get('read.php', $request->param());
        $read = new responser\Read($response);
        $this->assign($read->index(['fpage' => $fpage]));
        return $this->fetch('Read/index');
    }

    /**
     * 屏蔽回帖
     * @param Request $request
     * @return mixed
     */
    public function blockFloor(Request $request)
    {
        $response = Proxy::get('kf_fw_0ladmin.php', $request->param());
        new responser\Responser($response);
        return error('屏蔽回帖失败');
    }
}
