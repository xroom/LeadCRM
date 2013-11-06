<?php

class IndexAction extends PublicAction {
	public function __construct(){
		$this->menuAccess();
	}
 
    public function index(){
    	if(empty($_SESSION['username'])){
    		header('location:'.__APP__.'/Public/login');
    		exit;
    	}
		$this->assign('title_h1','首页');


		$Model=D('Order');
		$count = $Model->query("select sum(count) as count from __TABLE__");
		$this->assign('count_total',$count[0]['count']);


		//相关数据
 		$data = $Model->query("select DATE_FORMAT(tmall_create_time,'%Y%m%d') days,sum(count) count from __TABLE__ group by days;  ");
 		
 		foreach ($data as $key => $value) {
 			$googleChart[] = "


		  ['$value[days]',  $value[count]]
		";
 		}

 		$this->assign('googleChart',implode(',',$googleChart));
 		
		$this->display();
    }
}	