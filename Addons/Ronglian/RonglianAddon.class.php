<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Addons\Ronglian;
use Common\Controller\Addon;
use Think\Db;

/**
 * 附件插件
 * @author tang
 */
class RonglianAddon extends Addon{

	public $info = array(
		'name'        => 'Ronglian',
		'title'       => '容联',
		'description' => '容联IM',
		'status'      => 1,
		'author'      => 'tang',
		'version'     => '0.1'
	);

    public $addon_path = './Addons/Ronglian/';
    public $menu = array(
        'addons' => 'Ronglian',
        'hook' => 'RonglianAdmin',
        'status'=>1
    );
    public function install()
    {

//        M('menu_bar')->add($this->menu);
//        $AdHooks = array(
//            'name' => 'UmengAdmin',
//            'description' => '友盟推送',
//            'type' => 1,
//            'update_time' => NOW_TIME,
//            'addons' => 'Umeng'
//        );
//        M('hooks')->add($AdHooks);
        return true;
    }
    public function uninstall()
    {

//        M('menu_bar')->where(array('addons'=>'Umeng','hook' => 'UmengAdmin'))->delete();
//        M('hooks')->where(array('addons'=>'Ronglian'))->delete();
        return true;
    }
    public function RonglianAdmin(){
        $addons=I('_addons');
        $current=($addons=='Ronglian'||$addons=='rong_lian'?'current':'');
        echo '<li class="'.$current.'"><a href="'.addons_url("Ronglian://Ronglian/index").'">'.$this->info["title"].'</a></li>';
    }
}
