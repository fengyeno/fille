<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/5/16
 * Time: 15:07
 */

namespace Api\Controller;


class CityController extends BaseController{
    /*城市列表*/
    public function lists(){
        $pid=I('get.no');
        $pid=$pid?$pid:0;
        $map['status']=1;
        $map['topno']=$pid;
        $field=array('no','areaname');
        $list=M('city_area')->field($field)->where($map)->order('no')->select();
        if($list){
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未查找到数据');
        }
    }
} 