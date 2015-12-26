<?php
namespace Api\Controller;
class BeginController extends BaseController{
    /*上传地址获取*/
    public function getUploadUrl(){
//        $url=C('WEB_UPLOAD_IP');
//        $url=$url?$url:'http://'.$_SERVER['HTTP_HOST'].__ROOT__;
        $data['url']=$this->getUpUrl();
        $this->apiSuccess('success',$data);
    }
    /*城市列表*/
    public function cityList(){
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
    /*上传头像*/
    public function uploadHeadimg(){
        $info=$this->upload();
        if($info['path']){
            $data['path']=$info['path'];
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'上传失败');
        }
    }
}
