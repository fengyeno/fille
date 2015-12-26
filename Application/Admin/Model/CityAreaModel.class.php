<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Model;
use Think\Model;

/**
 * 地区
 * @author tang
 */

class CityAreaModel extends Model {

    /*
     * 获取省市列表
     * @author tang
     * */
    public function lists($topno=0, $status = 1, $order = 'no', $field = array('no','areaname')){
        $map = array('status' => $status,'topno'=>$topno);
        return $this->field($field)->where($map)->order($order)->select();
    }
    /*
     * 获取详情
     * @author tang
     * */
    public function getInfo($no){
        $map = array('status' => 1,'no'=>$no);
        return $this->where($map)->find();
    }

}
