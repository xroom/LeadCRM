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
    	$count_ig = 0;
    	$importResult = array();
    	if($uploadInfo){
			$list = $this->getCsv('./Uploads/'.$uploadInfo[0]['savename']);
    		//导入excel 文件 
			$result = array();
			$countNum = array();
			$Model = D('Order');
			foreach ($list as $key => $value) {
				if($key == 0) continue; //过滤掉第一行
				if(empty($value[0])){
					$count_ig++;
					$importResult[] = array(
							'id'=>$value['id'],
							'status' => '主ID为空，忽略'
							);
					continue;
				}
				//如果大于当前状态，则不更新。
				$info = $Model->where(array('id'=>trim($value[0])))->find();
				if(empty($info)){
					$count_ig++;
					$importResult[] = array(
							'id'=>$value[0],
							'status' => '没有找到当前订单'
							);
					continue;
				}
				if($info['status'] > 10 && $info['status'] != 15){
					$count_ig++;
					$importResult[] = array(
							'id'=>$value[0],
							'status' => '状态不符，忽略'
							);
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
				$data['type'] = 'import_ts_update';

				$Model->create($data);
				$res = $Model->save($data);
				//echo $Model->getLastSql();
				if($res){
					$count_success++;
					$importResult[] = array(
							'id'=>$data['id'],
							'status' => '更新成功,更新到'.getOrderStatus($data['status'])
					);
					//echo 'Update Success, set to '.getOrderStatus($data['status']).'  <a href="'.__APP__.'/Order/edit/id/'.$data['id'].'">'.$data['id']."</a><br />";
				}else{
					$count_error ++;
					$importResult[] = array(
							'id'=>$data['id'],
							'status' => '不需要更新，状态为：'.getOrderStatus($data['status'])
					);	
				}
			}
			$this->assign('title_h2','导入成功');
			$this->addBreadcrumbs(array(
					'name' => '导入成功'
			));
			$this->assign('importResult',$importResult);
			$this->assign('countSuccess',$count_success);
			$this->assign('countIg',$count_ig);
			$this->assign('countError',$count_error);
	    	$this->display('Public:import_result');
			//$this->success('导入成功');
		}else{
			$this->error('文件解析失败');
		}
    }
}