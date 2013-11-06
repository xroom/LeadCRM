<?php

class SmsAction extends PublicAction {
	public function __construct(){
		$this->assign('active_sms','active');
		$this->assign('title_h1','短信管理');

		$this->addBreadcrumbs(array(
				'name'=>'短信管理'
		));
		$this->menuAccess();
	}
    public function import(){
		
		$this->assign('title_h2','导入');
		$this->addBreadcrumbs(array(
				'name' => '导入'
		));
		$this->assign('active_sms_import','active');


		$this->display();
    }
    public function importData(){
    	$uploadInfo = $this->upload();
    	$Model = D('Order');
    	$count_success = 0;
    	$count_error = 0;
    	$count_ig = 0;
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
					die('aa');
					continue;
				}
				

				//找到当前该手机号的所有订单
				
				$map['mobile'] = trim($value[0]);
				$list = $Model->where($map)->select();
				if(empty($list)){
				$count_ig++;
				$importResult[] = array(
					'id'=>'',
					'status' => '没有找到该订单，手机号为:'.$map['mobile']
					);
				}
				foreach ($list as $key1 => $item) {
					if($item['status'] >5 && $item['status'] != 15){ //如果是确认以上状态，则不处理该订单
						$count_ig++;
						$importResult[] = array(
							'id'=>$item['id'],
							'status' => '状态不符合，忽略'
							);
						continue;
					}
					//查询一下这个手机号的订单
					$data = array();
					$sms = trim($value[2]);
					//判断回复内容
					switch($value[1]){ //A类
						case 'A':
							if(strtoupper($sms) == "'Y'" || $sms == "'Ｙ'" || strtoupper($sms)  == 'Y'){
								$data['status'] = 7;
							}else{
								$data['status'] = 1; 
							}

							break;
						case 'B':

							//查看当前这个手机号的订单，是否存在 电子邮件不合法的情况，如果有，则标为信息有误
							$tmpEmail = $Model->query('select * from __TABLE__ where email REGEXP "^[0-9]+$" and mobile='.$value[0]);
							if(!empty($tmpEmail)){

								$data['status'] = 4;
							}else{
								if($sms == 1 || $sms == "'1'" ){
									//查看当前这个手机号的订单，是否存在 电子邮件不合法的情况，如果有，则标为信息有误
									$data['status'] = 6; //需要注册
								}else if(strlen($sms) == 9 && is_numeric($sms)){
									//如果有数字，并且为９位，则标记为信息确认
									$data['status'] = 7; 
								}else{
									$data['status'] = 4; //信息有误
								}

							}

							
							break;
					}

					//更新到数据库中
					$data['id'] = $item['id'];
					$Model->create($data);
					$resUpdate = $Model->save($data);
					//echo $Model->getLastSql();
					//累加手机号，为了判断是否有重复
					$countNum[$value[0]][] =  $value[2]; 

					if($resUpdate){
							$count_success++;
							$importResult[] = array(
									'id'=>$data['id'],
									'status' => '更新成功,更新到'.getOrderStatus($data['status'])
							);
					}else{
						$count_error ++;
							$importResult[] = array(
									'id'=>$data['id'],
									'status' => '不需要更新,更新到'.getOrderStatus($data['status'])
							);
					}
				
					//echo 'Update Success, set to '.$data['status'].'  <a href="'.__APP__.'/Order/edit/id/'.$data['id'].'">'.$data['id']."</a><br />";

				}
			}
			foreach ($countNum as $key => $value) {
				if(count($value)>1) 	{
					//更新状态为信息无效
					$data = array();
					$data['status'] = 15;

					$list = $Model->where(array('mobile'=>$key))->select();

					foreach ($list as $key => $item) {
						if($item['status'] <= 5 || $item['status'] == 15){
							$data['id'] = $item['id'];
							$Model->create($data);
							$Model->save($data);

							//echo 'Update Success, set to '.$data['status'].'  <a href="'.__APP__.'/Order/edit/id/'.$data['id'].'">'.$data['id']."</a><br />";
							$importResult[] = array(
							'id'=>$item['id'],
							'status' => '找到重复手机号，更改状态'.getOrderStatus($data['status'])
							);
						}
					}
					
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
			
		}else{
			$this->error('上传失败');
		}
    }
}