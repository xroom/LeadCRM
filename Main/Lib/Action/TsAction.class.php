<?php

class TsAction extends PublicAction {
	public function __construct(){
		$this->assign('active_ts','active');
		$this->assign('title_h1','800Ts管理');

		$this->addBreadcrumbs(array(
				'name'=>'800Ts管理'
		));
		$this->menuAccess();
	}
    public function import(){
		
		$this->assign('title_h2','导入');
		$this->addBreadcrumbs(array(
				'name' => '导入'
		));
		$this->assign('active_ts_import','active');


		$this->display();
    }
    public function importData(){
    	header('Content-Type:text/html;charset=utf-8');

    	$uploadInfo = $this->upload();
    	$Model = D('Order');
    	$count_success = 0;
    	$count_error = 0;
    	if($uploadInfo){
			$list = $this->getCsv('./Uploads/'.$uploadInfo[0]['savename']);
    		//导入excel 文件 
			$result = array();
			$countNum = array();
			$Model = D('Order');
			foreach ($list as $key => $value) {
				if($key == 0) continue; //过滤掉第一行

				if(empty($value[0]))continue;
				
				//如果大于当前状态，则不更新。
				$info = $Model->where(array('id'=>$value[0]))->find();
				if($info['status'] > 10 || $info['status'] == 15){
					continue;
				}
				//如果卡号不为空，则添加
				$card = trim($value[5]);
				if(!is_numeric($card) || strlen($card) != 9){
					$data['status'] = 10;
				}else if(!empty($card)){
					$data['status'] = 9;
					$data['card'] = $card;
				}else{
					$data['status'] = 10; 
				}



				$data['id'] = $info['id'];
				$Model->create($data);
				$res = $Model->save($data);
				//echo $Model->getLastSql();
				if($res){
					echo 'Update Success, set to '.getOrderStatus($data['status']).'  <a href="'.__APP__.'/Order/edit/id/'.$data['id'].'">'.$data['id']."</a><br />";
				}
			}
			echo '操作完成<br />';
			//$this->success('导入成功');
		}
    }
}