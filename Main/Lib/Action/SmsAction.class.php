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
							'status' => '当前状态为['.getOrderStatus($item['status']).']，忽略',
							'data' => print_r($value,true)
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
									$data['card'] = $sms;
								}else{

									//判断内容中是否有一组9位数 数字
									preg_match_all( "/\d+/", $sms , $numList );
									$countNum = 0;
									if(count($numList[0]) == 0){
										$data['status'] = 4;
									}else{
										foreach ($numList[0] as $key1 => $value1) {
											if(strlen($value1) == 9){
												$data['card'] 	= $value1;
												$data['status'] = 7;
												$countNum++;
											}
										}
										//如果没有找到属于9个的，或者两组以上9位的
										if(empty($data['card']) || $countNum > 1){
											$data['status'] = 4;
											unset($data['card']);
										}


									}
									
								}

							}

							
							break;
					}


					//更新到数据库中
					$data['id'] = $item['id'];
					$data['type'] = 'import_sms_update';
					$Model->create($data);
					$resUpdate = $Model->save($data);
					//echo $Model->getLastSql();
					//累加手机号，为了判断是否有重复
					$countNum[$value[0]][] =  $value[2]; 

					if($resUpdate){
							$count_success++;
							$importResult[] = array(
									'id'=>$data['id'],
									'status' => '更新成功,更新到'.getOrderStatus($data['status']),
									'data' => print_r($value,true)
							);
					}else{
						$count_error ++;
							$importResult[] = array(
									'id'=>$data['id'],
									'status' => '不需要更新',
									'data' => print_r($value,true)
							);
					}
				
					//echo 'Update Success, set to '.$data['status'].'  <a href="'.__APP__.'/Order/edit/id/'.$data['id'].'">'.$data['id']."</a><br />";

				}
			}
			/*
			foreach ($countNum as $key => $value1) {
				if(count($value1)>1) 	{
					//更新状态为信息无效
					$data = array();
					$data['status'] = 15;

					$list = $Model->where(array('mobile'=>$key))->select();

					foreach ($list as $key => $item) {
						if($item['status'] <= 5 || $item['status'] == 15){
							$data['id'] = $item['id'];
							$data['type'] = 'import_sms_du_update';

							$Model->create($data);
							$Model->save($data);

							//echo 'Update Success, set to '.$data['status'].'  <a href="'.__APP__.'/Order/edit/id/'.$data['id'].'">'.$data['id']."</a><br />";
							$importResult[] = array(
							'id'=>$item['id'],
							'status' => '找到重复手机号，更改状态'.getOrderStatus($data['status']),
							'data' => print_r($value,true)
							);
						}
					}
					
				}

			}
			*/
			
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

   function batch(){
   			//状态列表
		$this->assign('statusList',getOrderStatus());
   		$this->display();
   }
   function batchSave(){
   		$count_success = 0;
    	$count_error = 0;
    	$count_ig = 0;
    	$hasImport = array();


   		$data['status'] = intval($_POST['status']);
   		$data['type'] = 'import_sms_batch_update';
   		$Model = D('Order');
   		$list = explode("\n",$_POST['mobile']);

   		/**
   		 * 如果导入卡号
   		 */
   		if(isset($_POST['importCard'])){
   			foreach ($list as $key => $value) {
   				if(empty($value)) continue; 
   				$card = explode("\t",$value);
   				if(empty($card[1]) || strlen($card[1]) != 9){ //如果卡号不为11,则忽略
   					$count_ig++;
	   				$importResult[] = array(
					'id'=>'',
					'status' => '卡号不准确 ,忽略',
					'data' => print_r($card,true)
					);
					continue;
   				}
   				$map = array();
   				$map['mobile'] = $card[0];
   				$info = $Model->where($map)->select();

	   			foreach ($info as $row => $item) {

	   				if($hasImport[$item['id']]){
	   					continue;
	   				}else{
	   					$hasImport[$item['id']] = $item['id'];
	   				}

	   				$data['id'] = $item['id'];
	   				$data['card'] = $card[1];
	   				$data['status'] = 7 ; //信息确认
	   				
		   			$Model->create($data);
		   			$res = $Model->save($data);

		   			
		   			if($res){
		   				$count_success++;
			   			$importResult[] = array(
						'id'=>$item['id'],
						'status' => '更新成功 '.getOrderStatus($data['status']),
						'data' => print_r($value,true)
						);
		   			}else{
		   				$count_error++;
		   				$importResult[] = array(
						'id'=>$item['id'],
						'status' => '更新失败 '.getOrderStatus($data['status']),
						'data' => print_r($value,true)
						);
		   			}
	   			}


   			}
   		}else{

   		
	   		foreach ($list as $key => $value) {
	   			if(empty($value)) continue;

	   			
	   			//查找
	   			
	   			$map['mobile'] = trim($value);

	   			$info = $Model->where($map)->select();

	   			foreach ($info as $row => $item) {

	   				if($hasImport[$item['id']]){
	   					continue;
	   				}else{
	   					$hasImport[$item['id']] = $item['id'];
	   				}

	   				$data['id'] = $item['id'];
	   				
		   			$Model->create($data);
		   			$res = $Model->save($data);

		   			//echo $Model->getLastSql();
		   			
		   			if($res){
		   				$count_success++;
			   			$importResult[] = array(
						'id'=>$item['id'],
						'status' => '更新成功 '.getOrderStatus($data['status']),
						'data' => print_r($value,true)
						);
		   			}else{
		   				$count_error++;
		   				$importResult[] = array(
						'id'=>$item['id'],
						'status' => '更新失败 '.getOrderStatus($data['status']),
						'data' => print_r($value,true)
						);
		   			}
	   			}

	   		}
	   	}

   			$this->assign('importResult',$importResult);
			$this->assign('countSuccess',$count_success);
			$this->assign('countIg',$count_ig);
			$this->assign('countError',$count_error);
	    	$this->display('Public:import_result');


   }
   public function test(){


   		$Model = D('Order');
   		$list = $Model->select();
   		$res  = array();
   		foreach ($list as $key => $value) {
   			//转换套餐编码
   			/*
				if(strpos($value['product_name'],'智选假日预付套票1' ) !== false){
					$res[$value['id']]['智选假日预付套票1'] = $value['product_name'];
				}
				if(strpos($value['product_name'],'智选假日预付套票2') !== false){
					$res[$value['id']]['智选假日预付套票2'] = $value['product_name'];

				}
				if(strpos($value['product_name'],'假日酒店预付套票') !== false || strpos($value['product_name'],'假日预付套票') !== false){
					$res[$value['id']]['假日酒店预付套票'] = $value['product_name'];
				}

				if(strpos($value['product_name'],'休闲度假预付套票') !== false || strpos($value['product_name'],'高星休闲度假预付套票') !== false){
					$res[$value['id']]['休闲度假预付套票'] = $value['product_name'];
	
				}
*/
				
				if(strlen($value['product_name']) > 100){
					$importResult[] = array(
					'id'=>$value['id'],
					'status' => $value['product_name'],
					'data' => print_r($res[$value['id']],true)
					);
				}
				if(count($res[$value['id']]) > 1){
					
					$importResult[] = array(
					'id'=>$value['id'],
					'status' => 'ok '.getOrderStatus($data['status']),
					'data' => print_r($res[$value['id']],true)
					);
				}



   		}
				

   		$this->assign('importResult',$importResult);
			$this->assign('countSuccess',$count_success);
			$this->assign('countIg',$count_ig);
			$this->assign('countError',$count_error);
	    	$this->display('Public:import_result');

   }
   /**
    * 拉取手机号重复的前一个状态
    * @return [type] [description]
    */
   public function testStatus(){
   		$Model = D('Order');
   		$list = $Model->where(array('status'=> 5))->select();

   		foreach ($list as $key => $value) {
   			//get last
   			$cache = S('Order_'.$value['id']);
   			if(count($cache)>1 ){
   				$res = array();
   				foreach($cache as $key1 => $c){
	   				
	   				$res[$key1]['id'] = $value['id'];
	   				$res[$key1]['card'] = $c['card'];
	   				$res[$key1]['status'] = getOrderStatus($c['status']);
	   				$res[$key1]['type'] = $c['type'];
	   				$res[$key1]['time'] = date('Y-m-d H:i:s',$c['sql_run_time']);
   				}
   			}else{
   				continue;
   			}

   			//echo "".implode(",",$res)."\n";

   			//continue;
   			$count_success++;
   			$importResult[] = array(
					'id'=>$value['id'],
					'status' => '['.$count_success.']<br />'.getOrderStatus($value['status']),
					'data' => print_r($res,true)
					);
   		}

   		$this->assign('importResult',$importResult);
			$this->assign('countSuccess',$count_success);
			$this->assign('countIg',$count_ig);
			$this->assign('countError',$count_error);
	    	$this->display('Public:import_result');
   }
}