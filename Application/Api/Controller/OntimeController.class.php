<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/5/18
 * Time: 20:09
 */

namespace Api\Controller;


class OntimeController extends BaseController{
    public function on_time_refreezing(){
        $time=date('Y-m-d H:i:s',time()-2*3600);
        $prefix=C('DB_PREFIX');
        $map['f.status']=1;
//        $map['d.status']=array('neq',-1);
        $map['d.date_time']=array('elt',$time);
        $fields=array('f.*');
        $list=M()->field($fields)
            ->table($prefix.'user_date d')
            ->join($prefix.'coin_freezing f on d.id=f.date_id')
            ->where($map)
            ->select();
        if($list){
            $this->coinDoing($list);
        }
    }
    /*约会金币操作*/
    protected function coinDoing($list){
        $per=C('USER_DATE_SXF');
        if(!empty($list)){
            foreach($list as $key=>$v){
                if($v['type']==1){
                    /*约会金币*/
                    if($this->checkOnDateStatus($v['date_id'],$v['uid'])){
                        /*达成约会*/
                        $coin=$v['coin']*(100-$per)/100;
                        if($coin>0){
                            /*扣除手续费并返还*/
                            $this->addCoin($coin,$v['uid'],$v['date_id'],$v['uid'],'refreezing');
                        }
                        /*手续费日志*/
                        $sxf=$v['coin']*$per/100;
                        $arr1['uid']=$v['uid'];
                        $arr1['order_id']=$v['id'];
                        $arr1['foruid']=$v['uid'];
                        $arr1['style']='datesys';
                        $arr1['type']=2;
                        $arr1['status']=1;
                        $arr1['coin']=$sxf;
                        $arr1['create_time']=time();
                        $arr1['update_time']=time();
                        $this->coinLog($arr1);
                    }else{
                        if($v['coin']){
                            $this->addCoin($v['coin'],$v['uid'],$v['date_id'],$v['uid'],'refreezing');
                        }
                    }
                }elseif($v['type']==2){
                    /*赠金*/
//                    $this->addCoin($v['coin'],$v['uid'],$v['id'],$v['uid'],'refreezing');
                }
                $freezing['id']=$v['id'];
                $freezing['status']=-1;
                M('coin_freezing')->save($freezing);
                /*推送*/
//                $this->push2user($uid,'会员响应了你的约会请求，请查看',3,$id);
                $this->push2user($v['uid'],"您的约会保证金已经返还",3,$v['date_id']);
            }
        }
    }
}
?>