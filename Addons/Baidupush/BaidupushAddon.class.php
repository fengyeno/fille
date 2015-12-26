<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Addons\Baidupush;
use Common\Controller\Addon;
use Think\Db;

/**
 * 附件插件
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class BaidupushAddon extends Addon{

	public $info = array(
		'name'        => 'Baidupush',
		'title'       => '推送',
		'description' => '百度推送',
		'status'      => 1,
		'author'      => 'tang',
		'version'     => '0.1'
	);

    public $addon_path = './Addons/Baidupush/';
    public $menu = array(
        'addons' => 'Baidupush',
        'hook' => 'BaidupushAdmin',
        'status'=>1
    );
    public function install()
    {
        $db_config = array();
        $db_config['DB_TYPE'] = C('DB_TYPE');
        $db_config['DB_HOST'] = C('DB_HOST');
        $db_config['DB_NAME'] = C('DB_NAME');
        $db_config['DB_USER'] = C('DB_USER');
        $db_config['DB_PWD'] = C('DB_PWD');
        $db_config['DB_PORT'] = C('DB_PORT');
        $db_config['DB_PREFIX'] = C('DB_PREFIX');
        $db = Db::getInstance($db_config);
        //读取插件sql文件
        $sqldata = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . '/Addons/' . $this->info['name'] . '/install.sql');
        $sqlFormat = $this->sql_split($sqldata, $db_config['DB_PREFIX']);

        $counts = count($sqlFormat);
        for ($i = 0; $i < $counts; $i++) {
            $sql = trim($sqlFormat[$i]);
            if (strstr($sql, 'CREATE TABLE')) {
                preg_match('/CREATE TABLE `([^ ]*)`/', $sql, $matches);
                mysql_query("DROP TABLE IF EXISTS `$matches[1]");
                $db->execute($sql);
            }
        }
        M('menu_bar')->add($this->menu);
        $AdHooks = array(
            'name' => 'BaidupushAdmin',
            'description' => '百度推送',
            'type' => 1,
            'update_time' => NOW_TIME,
            'addons' => 'Baidupush'
        );
        M('hooks')->add($AdHooks);
        return true;
    }

    public function uninstall()
    {
        $db_config = array();
        $db_config['DB_TYPE'] = C('DB_TYPE');
        $db_config['DB_HOST'] = C('DB_HOST');
        $db_config['DB_NAME'] = C('DB_NAME');
        $db_config['DB_USER'] = C('DB_USER');
        $db_config['DB_PWD'] = C('DB_PWD');
        $db_config['DB_PORT'] = C('DB_PORT');
        $db_config['DB_PREFIX'] = C('DB_PREFIX');
        $db = Db::getInstance($db_config);
        //读取插件sql文件
        $sqldata = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . '/Addons/' . $this->info['name'] . '/uninstall.sql');
        $sqlFormat = $this->sql_split($sqldata, $db_config['DB_PREFIX']);
        $counts = count($sqlFormat);

        for ($i = 0; $i < $counts; $i++) {
            $sql = trim($sqlFormat[$i]);
            $db->execute($sql); //执行语句
        }
        M('menu_bar')->where(array('addons'=>'Baidupush','hook' => 'BaidupushAdmin'))->delete();
        M('hooks')->where(array('addons'=>'Baidupush'))->delete();
        return true;
    }


    public function BaidupushAdmin(){
        $addons=I('_addons');
        $current=($addons=='Baidupush'||$addons=='baidupush'?'current':'');
        echo '<li class="'.$current.'"><a href="'.addons_url("Baidupush://Baidu/config").'">'.$this->info["title"].'</a></li>';
    }

    /**
     * 解析数据库语句函数
     * @param string $sql sql语句   带默认前缀的
     * @param string $tablepre 自己的前缀
     * @return multitype:string 返回最终需要的sql语句
     */
    public function sql_split($sql, $tablepre)
    {

        if ($tablepre != "onethink_")
            $sql = str_replace("onethink_", $tablepre, $sql);
        $sql = preg_replace("/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=utf8", $sql);

        if ($r_tablepre != $s_tablepre)
            $sql = str_replace($s_tablepre, $r_tablepre, $sql);
        $sql = str_replace("\r", "\n", $sql);
        $ret = array();
        $num = 0;
        $queriesarray = explode(";\n", trim($sql));
        unset($sql);
        foreach ($queriesarray as $query) {
            $ret[$num] = '';
            $queries = explode("\n", trim($query));
            $queries = array_filter($queries);
            foreach ($queries as $query) {
                $str1 = substr($query, 0, 1);
                if ($str1 != '#' && $str1 != '-')
                    $ret[$num] .= $query;
            }
            $num++;
        }
        return $ret;
    }



}
