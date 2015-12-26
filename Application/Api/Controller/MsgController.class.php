<?php
namespace Api\Controller;

class MsgController extends BaseController{
    public function r_msg(){
        $info=C('REGISTER_MSG');
        $this->assign('info',$info);
        $this->display('msg');
    }
    public function company(){
        $info=C('COMPANY_INFO');
        $this->assign('info',$info);
        $this->display();
    }
    public function knows(){
        $info=C('USER_KNOW');
        $this->assign('info',$info);
        $this->display();
    }
}