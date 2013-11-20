<?php

class TsAction extends PublicAction {
	public function __construct(){
		$this->assign('active_ts','active');
		$this->assign('title_h1','马尼拉管理');

		$this->addBreadcrumbs(array(
				'name'=>'马尼拉管理'
		));
		$this->menuAccess(
	}
    public function import(){
		$this->error('暂未开放');
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
							'id'=>$value[0],
							'status' => '主ID为空，忽略',
							'data' => print_r($value,true)
							);
					continue;
				}
				
				$loadingStatus = trim($value[4]);
				if($loadingStatus == 'y' || $loadingStatus == 'Y'){
					$data['status'] = 20; //注入成功
				}


				$data['id'] = $value[0];
				$data['type'] = 'import_manila_update';

				$Model->create($data);
				$res = $Model->save($data);
				//echo $Model->getLastSql();
				if($res){
					$count_success++;
					$importResult[] = array(
							'id'=>$data['id'],
							'status' => '更新成功,更新到'.getOrderStatus($data['status']),
							'data' => print_r($value,true)
					);
					//echo 'Update Success, set to '.getOrderStatus($data['status']).'  <a href="'.__APP__.'/Order/edit/id/'.$data['id'].'">'.$data['id']."</a><br />";
				}else{
					$count_error ++;
					$importResult[] = array(
							'id'=>$data['id'],
							'status' => '不需要更新，状态为：'.getOrderStatus($data['status']),
							'data' => print_r($value,true)
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