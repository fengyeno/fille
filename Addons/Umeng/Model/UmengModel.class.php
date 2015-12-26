<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Addons\Umeng\Model;

use Think\Model;

/**
 * 分类模型
 */
class UmengModel extends Model
{
    protected $tableName="umeng_list";

    /*  展示数据  */
    public function umengList($param=array())
    {
        if (isset($param)) {
            $map=$param;
            $map['status'] = 1;
        }
        $data=$this->where($map)->order('createtime desc')->select();
        return $data;
    }

    /* 获取编辑数据 */
    public function detail($id)
    {
        $data = $this->find($id);
//        $data['create_time'] =intval($data['create_time'])!=0? date('Y-m-d H:i', $data['create_time']):'';
        return $data;
    }

    /* 删除 */
    public function del($id)
    {
        return $this->delete($id);
    }
    /**
     * 新增或更新一个文档
     * @return boolean fasle 失败 ， int  成功 返回完整的数据
     */
    public function update($arr)
    {
        /* 获取数据对象 */
        $data = $this->create($arr);

        if (empty($data)) {
            return false;
        }
        /* 添加或新增基础内容 */
        if (empty($data['id'])) { //新增数据

            $id = $this->add($data); //添加基础内容
            if (!$id) {
                $this->error = '新增出错！';
                return false;
            }
        } else { //更新数据
            $status = $this->save($data); //更新基础内容
            if (false === $status) {
                $this->error = '更新出错！';
                return false;
            }
        }

        //内容添加或更新完成
        return $data;

    }

    /* 时间处理规则 */
    protected function getCreateTime()
    {
        $create_time = I('post.createtime');
        return intval($create_time)!=0 ? strtotime($create_time) : 0;
    }

}