<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/5/26
 * Time: 20:06
 */

namespace Api\Controller;
use Think\Controller;

class TestController extends Controller{
    public function __construct(){
        parent::__construct();
        echo crop_secret('Data/111.jpg');
        die;
    }
    protected function staticTest(){
        static $i=0;
        echo $i."\n";
        $i=0;
        echo $i."\n";
        $i++;
        return $i;
    }
    public function test1(){
        echo $this->staticTest();
        echo $this->staticTest();
    }
    protected function changeVideo($video){
        $first=substr($video,0,1);
        if($first=="/"){
            $video=substr($video,1);
        }
        set_time_limit(0);
        ini_set ('memory_limit', '600M');
        if(!function_exists("exec")){
            return false;
        }
//        $home=str_replace("Application/Api/Controller","",__DIR__);

        $new_video=$this->newVideo($video);
        $new_str="ffmpeg -i $video -vcodec libx264 -vpre fast -vpre baseline $new_video";
        exec($new_str);
        return "/".$new_video;
    }
    protected function newVideo($path){
        $new_video=substr($path,0,strrpos($path,"/")+1).time().".mp4";
        if(is_file($new_video)){
            $this->newVideo($path);
        }
        return $new_video;
    }
    public function index(){
        $this->display();
        die;
        set_time_limit(0);
        ini_set ('memory_limit', '600M');
        if(!function_exists("exec")){
            echo "exec not exists";
            return false;
        }
        $home=str_replace("Application/Api/Controller","",__DIR__);
        $home="";
        $video=$home."Uploads/Download/2015-09-12/55f3b74aa03b7.mp4";
        $name=time();
        $img="Uploads/Download/2015-09-12/$name.jpg";
        $img_path=$home.$img;
//        echo $img_path;
//        $str="ffmpeg -i $video -y -f image2 -ss 00:00:03 -s 352x240 $img_path";
//        echo exec($str);
//        if(is_file($img)){
//            echo '<img src="/'.$img.'" />';
//        }
        $new_video=$home.$this->newVideo($video);
        $new_str="ffmpeg -i $video -vcodec libx264 -vpre fast -vpre baseline $new_video";
        echo $new_str;
//        echo exec($new_str);

    }
    protected function newVideo1($path){
        $new_video=substr($path,0,strrpos($path,"/")+1).time().".mp4";
//        $new_video="Uploads/Download/2015-09-12/".time().".mp4";
        if(is_file($new_video)){
            $this->newVideo($path);
        }
        return $new_video;
    }
    public function test(){
        $list=M('user_album')->select();
        foreach($list as $key=>$v){
            if($v['pic']){
                $arr['secret_pic']=crop_secret($v['pic']);
                $arr['id']=$v['id'];
                M('user_album')->save($arr);
            }
        }
        echo 'over';
    }
} 