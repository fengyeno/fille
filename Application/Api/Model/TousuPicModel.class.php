<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Api\Model;
use Think\Model;

/**
 * 文档基础模型
 */
class TousuPicModel extends Model{

    /* 用户模型自动完成 */
    protected $_auto = array(
        array('create_time', 'time', self::MODEL_INSERT,'function'),
        array('status', 1, self::MODEL_INSERT)
    );
    /*自动验证*/
    protected $_validate = array(
        array('uid','require','用户uid不能为空'),
        array('pic_id','require','图片id不能为空'),
        array('msg','require','理由不能为空'),
    );
    /**
     * 检测投诉
     * @param  integer $uid 用户ID
     * @param  integer $pic_id 图片ID
     * @return boolean      ture-已投诉，false-未投诉
     */
    public function checkTousu($uid,$pic_id){
        $map=array(
            'uid'=>$uid,
            'pic_id'=>$pic_id,
            'status'=>1
        );
        $info=$this->where($map)->find();
        if($info){
            $this->error="已投诉";
            return true;
        }
        return false;
    }

    /**
     * 新增投诉
     * @return void
     */
    public function addTousu($data){
        if($this->checkTousu($data['uid'],$data['pic_id'])){
            return false;
        }
        if(!$this->create($data)){
            return false;
        }
        $res=$this->add();
        if(!$res){
            $this->error="添加失败";
            return false;
        }
        return $res;
    }

}
