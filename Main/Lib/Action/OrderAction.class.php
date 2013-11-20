<?php

class OrderAction extends PublicAction {
	var $pinyinData ;
	public function __construct(){
		$this->assign('active_order','active');
		$this->assign('title_h1','订单管理');

		$this->addBreadcrumbs(array(
				'name'=>'订单管理'
		));
		$this->menuAccess();
	
	}
    public function index(){
		
		$this->setReturnUrl();
		$this->assign('title_h2','列表');
		$this->addBreadcrumbs(array(
				'name' => '订单列表'
		));
		$this->assign('active_order_index','active');

		if(!empty($_GET['mobile']) && is_numeric($_GET['mobile']) ){
			$map['mobile'] = ($_GET['mobile']);
		}
		if(!empty($_GET['card'])  && is_numeric($_GET['card']) ){
			$map['card'] = ($_GET['card']);

		}
		if(!empty($_GET['id']) && is_numeric($_GET['id'])){
			$map['id'] = $_GET['id'];

		}
		if(!empty($_GET['order_id']) && is_numeric($_GET['order_id'])){
			$map['order_id'] = $_GET['order_id'];

		}
		if(!empty($_GET['status']) && is_numeric($_GET['status'])){
			$map['status']= intval($_GET['status']);

		}

		if(!empty($_GET['name'])){
			$map['name']= ($_GET['name']);

		}

		if(!empty($_GET['tmall_name'])){
			$map['tmall_name']= ($_GET['tmall_name']);

		}

		if(!empty($_GET['pay_status'])){
			$map['pay_status'] = intval($_GET['pay_status']);
		}


		//获取数据
		$Model = D('Order');



		import('ORG.Util.Page');// 导入分页类
		$count      = $Model->where($map)->count();// 查询满足要求的总记录数
		$Page       = new Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数
		$show       = $Page->show();// 分页显示输出
		// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
		$list = $Model->order('tmall_create_time DESC')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
		//echo $Model->getLastSql();
		$this->assign('list',$list);// 赋值数据集
		$this->assign('page',$show);// 赋值分页输出

		//状态列表
		$this->assign('statusList',getOrderStatus());

		$this->display();
    }
    public function edit(){

    	$this->assign('title_h2','详情');
		$this->addBreadcrumbs(array(
				'name' => '订单详情'
		));
		$this->assign('active_order_index','active');

		$Model = D('Order');
		$filter['id'] = intval($_GET['id']);
		$vo = $Model->where($filter)->find();
		//echo $Model->getLastSql();
		$this->assign('vo',$vo);

		//获取下拉列表
		$this->assign('statusList',getOrderStatus());

		//获取客服备注
		$ModelOb = D('Ob');
		$obInfo = $ModelOb->where(array('order_id'=>$filter['id']))->find();
		$this->assign('obInfo',$obInfo);


		//获取卡号信息
		$ModelCard = D('Card');
		$mapCard['order_id'] = $vo['id'];
		$cardList = $ModelCard->where($mapCard)->select();

		if(empty($cardList)){
			$this->error('未找到多卡信息');
			//如果没有分配卡号，则生成
			$data = array();
			$data['order_id'] = $vo['id'];
			$data['card'] = $vo['card'];
			$data['product_id'] = $vo['product_id'];
			$data['name'] = $vo['name'];
			$data['nights'] = $vo['count'] * 5;

			$ModelCard->create($data);
			for($i=0;$i<$vo['count'];$i++){
				$res = $ModelCard->add($data);

				$cardList[$i] = $data;
				$cardList[$i]['id'] = $res ;
			}


		}
		$this->assign('cardList',$cardList);

		//获取更新日志
		$this->assign('log',S('Order_'.$filter['id']));
    	$this->display();
    }
    public function update(){

    	foreach ($_POST['card'] as $key => $value) {
    		if(empty($value)){
    			$this->error('卡号不能为空');
    		}
    	}


   		$Model = D('Order');
   		$ModelCard = D('Card');

   		//更新拼音
   		$_POST['name_py'] = $this->convertPinyin($_POST['name_py']);
   		$_POST['address_py'] = $this->convertPinyin($_POST['address_py']);

   		$_POST['type'] = 'update';
   		$Model->create();
   		$result = $Model->save();

   		//更新卡号
   		foreach ($_POST['card'] as $key => $value) {
   			$data = array();
			//$data['order_id'] = $vo['id'];
			$data['card'] = $value;
			//$data['product_id'] = $vo['product_id'];
			//$data['name'] = $vo['name'];
			//$data['nights'] = $vo['count'] * 5;
			$data['id'] = $key;

			
			$ModelCard->create($data);
			$res = $ModelCard->save($data);

   		}

   		//echo $Model->getLastSql();
   		//exit;
   		if($result || $res ){
   			$this->success('更新成功', cookie('return_url'));
   		}else{
   			$this->error('更新失败');
   		}
    }
    public function import(){
    	$this->assign('title_h2','导入天猫数据');
		$this->addBreadcrumbs(array(
				'name' => '导入天猫数据'
		));
		$this->assign('active_order_import','active');
    	$this->display();
    }
    /**
     * 导入数据
     */
    public function importData(){
    	$this->error('基本导入功能，暂停');
    	exit;
    	set_time_limit(0);
    	header('Content-Type:text/html;charset=utf-8');
    	$uploadInfo = $this->upload();
    	$Model = D('Order');
    	$count_success = 0;
    	$count_error = 0;
    	$count_ig = 0;
    	$importResult = array();
    	if($uploadInfo){
			$list = $this->getCsv('./Uploads/'.$uploadInfo[0]['savename']);
			$result = array();
			foreach ($list as $key => $value) {
				if($key == 0) continue; //过滤掉第一行
				//Array ( [0] => 订单编号 [1] => 买家会员名 [2] => 买家支付宝账号 [3] => 买家应付货款 [4] => 买家应付邮费 [5] => 买家支付积分 [6] => 总金额 [7] => 返点积分 [8] => 买家实际支付金额 [9] => 买家实际支付积分 [10] => 订单状态 [11] => 买家留言 [12] => 收货人姓名 [13] => 收货地址 [14] => 运送方式 [15] => 联系电话 [16] => 联系手机 [17] => 订单创建时间 [18] => 订单付款时间 [19] => 宝贝标题 [20] => 宝贝种类 [21] => 物流单号 [22] => 物流公司 [23] => 订单备注 [24] => 宝贝总数量 [25] => 店铺Id [26] => 店铺名称 [27] => 订单关闭原因 [28] => 卖家服务费 [29] => 买家服务费 [30] => 发票抬头 [31] => 是否手机订单 [32] => 分阶段订单信息 [33] => 定金排名 [34] => 修改后的sku [35] => 修改后的收货地址 [36] => 异常信息 )


				//if(empty($value[11]))continue; //过滤掉没有备注的
				//header('Content-Type: text/html; charset=utf-8');
				//echo '<pre>';
				//print_r($value);
				$data = array();
				$data['order_id'] = $value[0];
				$data['tmall_name'] = $value[1];
				$data['email']	= $value[2];
				$data['tmall_order_status'] = $value[10];
				$data['comment'] = $value[11];	
				$data['comment_ob'] = $value[23];
				$data['name'] = $value[12];
				$data['address'] = $value[13];
				$data['phone'] = str_replace("'",'',$value[15]);
				$data['mobile'] = str_replace("'",'',$value[16]);
				$data['product_name'] = $value[19];
				$data['tmall_create_time'] = $value[17];
				$data['count'] = $value[24];
				$data['status'] = 0;

				//判断订单号是否合法
				
				if(strlen($data['order_id']) < 15 ){
					$count_ig++;
					$importResult[] = array(
							'id'=>$data['order_id'],
							'status' => '订单ID不合法，请检查表格',
							);
						continue;
				};

				//判断订单状态
				if($data['tmall_order_status'] != '买家已付款，等待卖家发货' && $data['tmall_order_status'] != '卖家已发货，等待买家确认'){
					Log::write('未付款，过滤'.$data['id'], Log::DEBUG);
						$count_ig++;
					$importResult[] = array(
							'id'=>$data['order_id'],
							'status' => '未付款，忽略',
							);
						continue;
				}

				//自有处理信息
				$data['backup'] = json_encode($value);
				$data['create_time'] = mktime();

				

				//转换套餐编码
				if(strpos($data['product_name'],'智选假日预付套票1' ) !== false){
					$data['product_id'] = 'TBHIX1';
					$data['deposit_id'] = 'IVTF1';
				}else if(strpos($data['product_name'],'智选假日预付套票2') !== false){
					$data['product_id'] = 'TBHIX2';
					$data['deposit_id'] = 'IVTF2';

				}else if(strpos($data['product_name'],'假日酒店预付套票') !== false || strpos($data['product_name'],'假日预付套票') !== false){
					$data['product_id'] = 'TBHI01';
					$data['deposit_id'] = 'IVTF3';

				}else if(strpos($data['product_name'],'休闲度假预付套票') !== false || strpos($data['product_name'],'高星休闲度假预付套票') !== false){
					$data['product_id'] = 'TBLEIS';
					$data['deposit_id'] = 'IVTF4';
				}else{
					Log::write('宝贝名称不符，过滤'.$data['id'], Log::DEBUG);
					//die('continue');
					$count_ig++;
					$importResult[] = array(
							'id'=>$data['order_id'],
							'status' => '宝贝名称不符，忽略',
							'data'=> $data['product_name'],
							);
					continue;
				}


				

				//提取卡号
				preg_match_all( "/\d+/", $data['comment'], $numList );

				if(count($numList[0]) == 0){

					$data['status'] = 3;
				}else{
					foreach ($numList[0] as $key1 => $value1) {
						if(strlen($value1) == 9){
							$data['card'] 	= $value1;
							$data['status'] = 2;
						}
					}
					if(empty($data['card'])){
						$data['status'] = 1;
					}
				}

				//提取淘宝客服中的卡号，如果这里面有卡号，则覆盖前面的信息
				preg_match_all( "/\d+/", $data['comment_ob'], $numList );
				if(count($numList[0]) > 0){
					foreach ($numList[0] as $key2 => $value2) {
						if(strlen($value2) == 9){
							$data['card'] = $value2;
							$data['status'] = 2;
						}
					}
				}

				//echo 'status';


				//如果支付宝帐号不是电子邮件，则标记为 信息有误
				if(!is_email($data['email'])){
					$data['status'] = 4;
				}


				//如果手机号为空，则标为信息无效
				if(empty($data['mobile'])){
					$data['status'] = 15;
				}else{
					//如果手机号重复,则更新状态为 手机号重复
					$phoneCheck = $Model->where(array('mobile'=>$data['mobile']))->find();

					if(!empty($phoneCheck)){
						$data['status'] = 5;
					}
				}

				

				//转换拼音
				//$data['name_py'] = ucwords($this->convertPinyin($data['name']));
				//$data['address_py'] =  strtoupper($this->convertPinyin($data['address']));

				//echo 'pinyin';



				//检查是否存在该订单号
				$checkMap = array();
				$checkMap['order_id'] = $data['order_id'];
				$checkMap['product_name'] = $data['product_name'];
				$info = $Model->where($checkMap)->find();
			
				if(count($info) > 0){
					
					//如果是更新的订单,并且状态为 手机号重复,则不处理。 过了导入的状态，也不再处理。
					if($data['status'] >= 5 && $data['status'] != 15 ){
						Log::write('状态已经变更，过滤'.$data['id'], Log::DEBUG);
						$count_ig++;
						$importResult[] = array(
							'id'=>$info['id'],
							'status' => '状态已经变更，忽略'
							);
						continue;
						//unset($data['status']);
					}
					$data['id'] = $info['id'];
					//die('update');
					//执行更新
					$data['type'] = 'import_update';
					$Model->create($data);
					$res = $Model->save($data);

					if($res){
						$count_success++;
						Log::write('更新成功：'.$data['id'], Log::DEBUG);
						//echo '更新成功 <a href="'.__APP__.'/Order/edit/id/'.$data['id'].'">'.$data['id']."</a><br />";

							$importResult[] = array(
							'id'=>$info['id'],
							'status' => '更新成功'
							);

					}else{
						$count_error++;
						Log::write('更新失败：'.$Model->getLastSql(), Log::DEBUG);
							$importResult[] = array(
							'id'=>$info['id'],
							'status' => '更新失败'
							);
					}
				}else{
					
					if($data['status'] == 5){
						//更新之前的订单状态 也为手机号重复
						
						$updateMobile = array();
						$updateMobile['id'] = $phoneCheck['id'];
						$updateMobile['status'] = 5;
						$updateMobile['type'] = 'import_du_update';
						$updateModel = D('Order');
						$updateModel->create($updateMobile);
						$updateModel->save($updateMobile);
						
						
					}

					//die('insert'.print_r($data,true));
					$data['type'] = 'import_insert';
					$Model->create($data);
					$res = $Model->add($data);

					if($res){
						Log::write('添加成功：'.$data['id'], Log::DEBUG);
						$count_success++;

						//echo '添加成功 <a href="'.__APP__.'/Order/edit/id/'.$data['id'].'">'.$data['id']."</a><br />";
							$importResult[] = array(
							'id'=>$res,
							'status' => '添加成功'
							);
						
					}else{
						Log::write('添加失败：'.$Model->getLastSql() , Log::DEBUG);
						$count_error++;
						//$this->error('导入失败');
						$importResult[] = array(
							'id'=>$res,
							'status' => '添加失败'
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
		$this->assign('countError',$count_error);
		$this->assign('countIg',$count_ig);

    	$this->display('Public:import_result');
    	//$this->success('导入成功'.$count_success.'条, 失败'.$count_error.'条',__APP__.'/Order/import/&success='.$count_success.'&error='.$count_error);

    }
    public function export(){
    	$this->assign('title_h2','导出指定数据');
		$this->addBreadcrumbs(array(
				'name' => '导出指定数据'
		));
		$this->assign('active_order_export','active');


    	$this->display();
    }
    public function exportData(){
    	$Model = D('Order');
		

		switch ($_GET['status']) {
			case 2:
				$filter['status'] = 2;
				$data = $Model->where($filter)->select();

				$count = 0;
				$res[$count]['mobile'] = '手机号';
				$res[$count]['tmall_name'] = '天猫帐号';
				$res[$count]['card'] = '卡号';
				$res[$count]['comment_ob'] = '客服备注';
				$res[$count]['comment'] = '会员备注';
				$res[$count]['id'] = '订单号';

				foreach ($res[$count] as $key1 => $value) {
						$res[$count][$key1] = iconv('UTF-8','gbk', $value);
				}

				$count++;
				foreach ($data as $key => $vo) {

					$res[$count]['mobile'] = $vo['mobile'];
					$res[$count]['tmall_name'] = iconv('UTF-8','gbk',$vo['tmall_name']);
					$res[$count]['card'] = "\t".$vo['card'];
					$res[$count]['comment_ob'] = iconv('UTF-8','gbk', $vo['comment_ob']);
					$res[$count]['comment'] = iconv('UTF-8','gbk', $vo['comment']);
					$res[$count]['id'] = $vo['id'];

					//$res[$count]['text'] = iconv('UTF-8','gbk', '尊敬的'.$vo['name'].',您已经成功购买'.$vo['product_name'].',根据您的留言,您的IHG忧悦会会员卡号为：'.$vo['card'].',如号码有误,请回复N；如确认无误,则无需回复,感您的订购【洲际酒店集团官方店】');

					$count++;
				}

				break;
			case 3:
			//A类或是B类短信
				$filter['status'] = intval($_GET['status']);
				//$data = $Model->field('mobile')->where($filter)->select();
				$data = $Model->where($filter)->select();

				$count = 0;
				$res[$count]['mobile'] = '手机号';
				$res[$count]['tmall_name'] = '天猫帐号';
				$res[$count]['name'] = '姓名';
				$res[$count]['email'] = '电子邮件';
				$res[$count]['comment'] = '会员备注';
				$res[$count]['id'] = '订单号';

				foreach ($res[$count] as $key1 => $value) {
						$res[$count][$key1] = iconv('UTF-8','gbk', $value);
				}

				$count++;


				foreach ($data as $key => $vo) {

					
					$res[$count]['mobile'] = $vo['mobile'];
					$res[$count]['tmall_name'] = iconv('UTF-8','gbk', $vo['tmall_name']);
					$res[$count]['name'] = iconv('UTF-8','gbk',$vo['name']);
					$res[$count]['email'] = iconv('UTF-8','gbk', $vo['email']);
					$res[$count]['comment'] = iconv('UTF-8','gbk', $vo['comment']);
					$res[$count]['id'] = $vo['id'];
					//$res[$count]['text'] = iconv('UTF-8','gbk', '尊敬的'.$vo['name'].',您已经成功购买'.$vo['product_name'].',你是否愿意加入IHG忧悦会会员,回复1,则表示同意,我们将确认您的个人信息,您的姓名为:'.$vo['name'].',邮箱为：'.$vo['email'].',如果信息有误,请回复2,感谢您的配合【洲际酒店集团官方店】');
					$count++;
				}

				break;
			
			case 6: //需要注册
				$filter['status'] = intval($_GET['status']);
				$data = $Model->where($filter)->select();
				$count = 0;
				//$res[$count] = getFieldName();
				$res[$count]['id'] = '订单ID';
				$res[$count]['name'] = '姓名拼音';
				$res[$count]['name_py'] = '姓名拼音';
				$res[$count]['mobile'] = '手机';
				$res[$count]['email'] = '电子邮件';
				$res[$count]['address'] = '姓名拼音';
				$res[$count]['address_py'] = '地址拼音';
				$res[$count]['new_card'] = '注册的卡号';
				//$res[$count]['reg_success'] = '注册是否成功';
				$res[$count]['800_comment'] = '描述';



				foreach ($res[$count] as $key1 => $value) {
						$res[$count][$key1] = iconv('UTF-8','gbk', $value);
				}

				$count++;


				foreach ($data as $key => $vo) {
					//转换拼音
					
					/*
					foreach ($vo as $key1 => $value) {
						$res[$count][$key1] = iconv('UTF-8','gbk', $value);
					}
					*/
					$res[$count]['id'] = "\t".$vo['id'];
					$res[$count]['name'] = iconv('UTF-8','gbk',$vo['name']);
					$res[$count]['name_py'] = ucwords($this->convertPinyin($vo['name']));
//					$res[$count]['mobile'] = "\t".empty($vo['mobile'])?$vo['phone']:$vo['mobile'];
					if(empty($vo['mobile'])){
						$res[$count]['mobile'] = "\t".$vo['phone'];
					}else{
						$res[$count]['mobile'] = "\t".$vo['mobile'];
					}
					
					$res[$count]['email'] = $vo['email'];

					$res[$count]['address'] = iconv('UTF-8','gbk',$vo['address']);
					$res[$count]['address_py'] = $this->convertHaf(strtoupper($this->convertPinyin($vo['address'])));


					$res[$count]['new_card'] = '';
					//$res[$count]['reg_success'] = '';
					$res[$count]['800_comment'] = '';
					$count++;
				}

				break;
			case 7: //信息确认，准备注入,马尼拉
/*
				$query = 'select order_id,card, product_name, deposit_id,product_id ,count, count*5 as nights
				from ihg_order where 
				(pay_status = 2 or pay_status = 3)
				and card != ""
				and LENGTH(card) = 9 
				and status != 16 
				order by order_id  ';
*/

				$query = 'select *, count*5 as nights 
				from ihg_order where 1 
				and card != "" 
				and (status = 7 or status = 17  ) 
				order by card  ';

				$Model = D('Order');
				$data = $Model->query($query);
				$res = array();
				$res[$count]['id'] = '#ID';
				$res[$count]['card'] = 'Tmall User Confirmed IRC No.';
				$res[$count]['product_id'] = 'Deposit ID ';
				$res[$count]['nights'] = 'Num of Free Night';
				$res[$count]['fn_status'] = 'Loading Success (Y/N)';
				$res[$count]['fm_desc'] = 'Error Description';
				$res[$count]['product_name'] = 'Package Name';
				$res[$count]['count'] = 'Num of Package';
				$res[$count]['loyalty'] = 'Loyalty Offer Code';
				$res[$count]['rate_code'] = 'Redemption Rate Code ';
				$res[$count]['order_id'] = 'Order ID ';

				$count++;

				foreach ($data as $key => $value) {

					$res[$count]['id'] = $value['id'];
					$res[$count]['card'] = $value['card'];
					

					switch(trim($value['product_id'])){
						case 'TBHIX1':
						$res[$count]['deposit_id'] = $value['product_id'];
					$res[$count]['nights'] = $value['nights'];
					$res[$count]['fn_status'] = '';
					$res[$count]['fm_desc'] = '';
							$res[$count]['product_name'] = 'Holiday Inn Express Type 1 Prepaid Package';
							$res[$count]['count'] = $value['count'];
							$res[$count]['loyalty'] = '8590440';
							$res[$count]['rate_code'] = 'IVTF1';
							break;
						case 'TBHIX2':
						$res[$count]['deposit_id'] = $value['product_id'];
					$res[$count]['nights'] = $value['nights'];
					$res[$count]['fn_status'] = '';
					$res[$count]['fm_desc'] = '';
							$res[$count]['product_name'] = 'Holiday Inn Express Type 2 Prepaid Package';
							$res[$count]['count'] = $value['count'];
							$res[$count]['loyalty'] = '8590441';
							$res[$count]['rate_code'] = 'IVTF2';
							break;
						case 'TBHI01':
						$res[$count]['deposit_id'] = $value['product_id'];
					$res[$count]['nights'] = $value['nights'];
					$res[$count]['fn_status'] = '';
					$res[$count]['fm_desc'] = '';
						$res[$count]['product_name'] = 'Holiday Inn Packcage ';
							$res[$count]['count'] = $value['count'];
							$res[$count]['loyalty'] = '8590442';
							$res[$count]['rate_code'] = 'IVTF3';
							break;
						case 'TBLEIS':
						$res[$count]['deposit_id'] = $value['product_id'];
					$res[$count]['nights'] = $value['nights'];
					$res[$count]['fn_status'] = '';
					$res[$count]['fm_desc'] = '';
						$res[$count]['product_name'] = 'Leisure Package ';
							$res[$count]['count'] = $value['count'];
							$res[$count]['loyalty'] = '8590443';
							$res[$count]['rate_code'] = 'IVTF4';
							break;

					}

					$res[$count]['order_id'] = $value['order_id'];

					$count++;
				}

			break;
			case 18:

			$query = 'select *, count*5 as nights 
				from ihg_order where 1 
				and card != "" 
				and (status = 18 ) 
				order by card  ';

				$Model = D('Order');
				$data = $Model->query($query);
				$res = array();
				$res[$count]['id'] = '#ID';
				$res[$count]['card'] = 'Tmall User Confirmed IRC No.';
				$res[$count]['product_id'] = 'Deposit ID ';
				$res[$count]['nights'] = 'Num of Free Night';
				$res[$count]['fn_status'] = 'Loading Success (Y/N)';
				$res[$count]['fm_desc'] = 'Error Description';
				$res[$count]['product_name'] = 'Package Name';
				$res[$count]['count'] = 'Num of Package';
				$res[$count]['loyalty'] = 'Loyalty Offer Code';
				$res[$count]['rate_code'] = 'Redemption Rate Code ';
				$res[$count]['order_id'] = 'Order ID ';

				$count++;

				foreach ($data as $key => $value) {

					for($i=1;$i <= $value['nights']; $i++ ){
						$res[$count]['id'] = $value['id'];
						$res[$count]['card'] = $value['card'];
						

						switch(trim($value['product_id'])){
							case 'TBHIX1':
							$res[$count]['deposit_id'] = $value['product_id'];
						//$res[$count]['nights'] = $value['nights'];
						$res[$count]['nights'] = 1;
						$res[$count]['fn_status'] = '';
						$res[$count]['fm_desc'] = '';
								$res[$count]['product_name'] = 'Holiday Inn Express Type 1 Prepaid Package';
								$res[$count]['count'] = $value['count'];
								$res[$count]['loyalty'] = '8590440';
								$res[$count]['rate_code'] = 'IVTF1';
								break;
							case 'TBHIX2':
							$res[$count]['deposit_id'] = $value['product_id'];
						//$res[$count]['nights'] = $value['nights'];
						$res[$count]['nights'] = 1;
						$res[$count]['fn_status'] = '';
						$res[$count]['fm_desc'] = '';
								$res[$count]['product_name'] = 'Holiday Inn Express Type 2 Prepaid Package';
								$res[$count]['count'] = $value['count'];
								$res[$count]['loyalty'] = '8590441';
								$res[$count]['rate_code'] = 'IVTF2';
								break;
							case 'TBHI01':
							$res[$count]['deposit_id'] = $value['product_id'];
						//$res[$count]['nights'] = $value['nights'];
						$res[$count]['nights'] = 1;
						$res[$count]['fn_status'] = '';
						$res[$count]['fm_desc'] = '';
							$res[$count]['product_name'] = 'Holiday Inn Packcage ';
								$res[$count]['count'] = $value['count'];
								$res[$count]['loyalty'] = '8590442';
								$res[$count]['rate_code'] = 'IVTF3';
								break;
							case 'TBLEIS':
							$res[$count]['deposit_id'] = $value['product_id'];
						//$res[$count]['nights'] = $value['nights'];
						$res[$count]['nights'] = 1;
						$res[$count]['fn_status'] = '';
						$res[$count]['fm_desc'] = '';
							$res[$count]['product_name'] = 'Leisure Package ';
								$res[$count]['count'] = $value['count'];
								$res[$count]['loyalty'] = '8590443';
								$res[$count]['rate_code'] = 'IVTF4';
								break;

						}

						$res[$count]['order_id'] = $value['order_id'];

						$count++;
					}//end for
				}
			break;
			case 30:
				//订单付款状态
				$map = array();
				$map['pay_status'] = intval($_GET['pay_status']);
				$res = $Model->field('order_id, tmall_name, mobile, card,tmall_order_status,tmall_pay_level,tmall_order_close,pay_status')->where($map)->select();
				foreach ($res as $key => $value) {
					foreach ($value as $key1 => $value1) {
						$res[$key][$key1] = iconv('UTF-8', 'gbk', $value1);
					}
				}
				break;
			default:
				$map = array();
				if(isset($_GET['status'])){
					$map['status'] = $_GET['status'];
				}
				
				$data = $Model->where($map)->select();

				$count = 0;
				
				foreach($data[0] as $key=>$value){
					$res[$count][$key] = iconv('UTF-8','gbk',getFieldName($key));
				}
				$count++;
				foreach ($data as $key => $value) {
					foreach($value as $key1 =>$value1 ){
						$res[$count][$key1] = iconv('UTF-8','gbk',$value1);
					}
					$count++;
				}
				break;
		}
		

		//导出逻辑
		if($_SERVER['HTTP_HOST'] != 'localhost'){
		$filename = './Downloads/export_'.getOrderStatus($_GET['status']).'_'.date('Y_m_d_H_s').'.csv';
	}else{
		$filename = './Downloads/export_'.($_GET['status']).'_'.date('Y_m_d_H_s').'.csv';

	}
		$this->exportCSV($filename,$res);

	    Header("HTTP/1.1 303 See Other"); 
	    header('Content-Type: text/html; charset=gbk');
        Header("Location: ".$filename); 
    }
	public function test(){
//echo 		$this->convertHaf('JIANGSUXING WUXISHI JIANGYINSHI (BUYAOXIEWUXISHI ，ZHIJIEJIANGYINSHI )HUANGTUZHEN XIAOHUSHUICHANSHI CHANG 7HAO (213004)');
		$a = 12345000012300;
		if(substr($a, -4) == '0000'){
			die('aa');
		};
	}
	public function convertHaf($word){
		$tags = $word;
		$tags = str_replace('—','-',$tags);
		$tags = iconv('utf-8', 'gbk', $tags); 
		$tags = preg_replace('/\xa3([\xa1-\xfe])/e', 'chr(ord(\1)-0x80)', $tags); 
		$tags = iconv( 'gbk', 'utf-8', $tags); 

		return $tags;
	}
	/**
	 * 拆单
	 * @return [type] [description]
	 */
	public function splitOrder(){
		$Model = D('Order');
		$list = $Model->query('select * from ihg_order  where LENGTH(product_name) > 100 order by count');

		foreach ($list as $key => $value) {
			//拆开产品名称
			$nameList = explode('，',$value['product_name']);



			




			//更新旧的
			$data_old['id'] = $value['id'];
			$data_old['product_name'] = $nameList[0];
			$data_old['count'] 	= 1;
			$data_old['type'] = 'split_order_update';
			$data_old['status'] = $value['status'];

			//转换套餐编码
			if(strpos($data_old['product_name'],'智选假日预付套票1' ) !== false){
				$data_old['product_id'] = 'TBHIX1';
				$data_old['deposit_id'] = 'IVTF1';
			}else if(strpos($data_old['product_name'],'智选假日预付套票2') !== false){
				$data_old['product_id'] = 'TBHIX2';
				$data_old['deposit_id'] = 'IVTF2';

			}else if(strpos($data_old['product_name'],'假日酒店预付套票') !== false || strpos($data_old['product_name'],'假日预付套票') !== false){
				$data_old['product_id'] = 'TBHI01';
				$data_old['deposit_id'] = 'IVTF3';

			}else if(strpos($data_old['product_name'],'休闲度假预付套票') !== false || strpos($data_old['product_name'],'高星休闲度假预付套票') !== false){
				$data_old['product_id'] = 'TBLEIS';
				$data_old['deposit_id'] = 'IVTF4';
			}else{
				die('名称不符');
			}



			$Model->create($data_old);
			$Model->save($data_old);

			//添加新的
			$data_new = $value;
			unset($data_new['id']);

			$data_new['product_name'] = $nameList[1];
			$data_new['count']	 = 1;

			//转换套餐编码
			if(strpos($data_new['product_name'],'智选假日预付套票1' ) !== false){
				$data_new['product_id'] = 'TBHIX1';
				$data_new['deposit_id'] = 'IVTF1';
			}else if(strpos($data_new['product_name'],'智选假日预付套票2') !== false){
				$data_new['product_id'] = 'TBHIX2';
				$data_new['deposit_id'] = 'IVTF2';

			}else if(strpos($data_new['product_name'],'假日酒店预付套票') !== false || strpos($data_new['product_name'],'假日预付套票') !== false){
				$data_new['product_id'] = 'TBHI01';
				$data_new['deposit_id'] = 'IVTF3';

			}else if(strpos($data_new['product_name'],'休闲度假预付套票') !== false || strpos($data_new['product_name'],'高星休闲度假预付套票') !== false){
				$data_new['product_id'] = 'TBLEIS';
				$data_new['deposit_id'] = 'IVTF4';
			}else{
				die('名称不符');
			}

			$data_new['type'] = 'split_order_create';
			$data_new['status'] = $value['status'];

//print_r($data_new);
			$Model->create($data_new);
			$res = $Model->add($data_new);

			echo $res.'<br />';
		}

		

	}
	public function updatePayStatus(){
		set_time_limit(0);
    	header('Content-Type:text/html;charset=utf-8');
    	$uploadInfo = $this->upload();
    	$Model = D('Order');
    	$count_success = 0;
    	$count_error = 0;
    	$count_ig = 0;
    	$importResult = array();

    	$Model = D('Order');
    	if($uploadInfo){
			$list = $this->getCsv('./Uploads/'.$uploadInfo[0]['savename']);
			$result = array();
			$count = 0;
			foreach ($list as $key => $value) {
				if($key == 0) continue; //过滤掉第一行
				//Array ( [0] => 订单编号 [1] => 买家会员名 [2] => 买家支付宝账号 [3] => 买家应付货款 [4] => 买家应付邮费 [5] => 买家支付积分 [6] => 总金额 [7] => 返点积分 [8] => 买家实际支付金额 [9] => 买家实际支付积分 [10] => 订单状态 [11] => 买家留言 [12] => 收货人姓名 [13] => 收货地址 [14] => 运送方式 [15] => 联系电话 [16] => 联系手机 [17] => 订单创建时间 [18] => 订单付款时间 [19] => 宝贝标题 [20] => 宝贝种类 [21] => 物流单号 [22] => 物流公司 [23] => 订单备注 [24] => 宝贝总数量 [25] => 店铺Id [26] => 店铺名称 [27] => 订单关闭原因 [28] => 卖家服务费 [29] => 买家服务费 [30] => 发票抬头 [31] => 是否手机订单 [32] => 分阶段订单信息 [33] => 定金排名 [34] => 修改后的sku [35] => 修改后的收货地址 [36] => 异常信息 )

				//判断订单号是否合法
				$data = array();
				$data['order_id'] = $value[0];
				$data['tmall_name'] = $value[1];
				$data['email']	= $value[2];
				$data['tmall_order_status'] = $value[10];
				$data['comment'] = $value[11];	
				$data['comment_ob'] = $value[23];
				$data['name'] = $value[12];
				$data['address'] = $value[13];
				$data['phone'] = str_replace("'",'',$value[15]);
				$data['mobile'] = str_replace("'",'',$value[16]);
				$data['product_name'] = $value[19];
				$data['tmall_create_time'] = $value[17];
				$data['count'] = $value[24];
				$data['status'] = 0;
				$update['tmall_pay_level'] = $value[32];
				$update['tmall_order_close'] = $value[27];



				if(strlen($data['order_id']) < 15 ){
					$count_ig++;
					die('ID不合法');
				};

				$update['pay_status'] = 0;

				//判断订单付款状态
				//全额
				if(($data['tmall_order_status'] == '买家已付款，等待卖家发货' 
					|| $data['tmall_order_status'] == '卖家已发货，等待买家确认'
					|| $data['tmall_order_status'] == '交易成功')
					&& $update['tmall_pay_level'] == '' 
					&& $update['tmall_order_close'] == '订单未关闭' 
					){
					
					//echo $update['order_id'].' 全款'.' '.$data['tmall_order_status'].$data['tmall_order_close'].$data['tmall_pay_level'].'<br />';
					$count_success++;
					$update['pay_status'] = 3;
				}


				$tmpLevel = explode(';',$update['tmall_pay_level']);
				$update['tmall_pay_level2'] = $tmpLevel[1];

				//付尾款
				if(($data['tmall_order_status'] == '买家已付款，等待卖家发货' 
					|| $data['tmall_order_status'] == '卖家已发货，等待买家确认'
					)
					&& strpos($update['tmall_pay_level2'], '阶段2') !== false 
					&& (strpos($update['tmall_pay_level2'], '交易状态等待卖家操作') !== false  
						|| strpos($update['tmall_pay_level2'],'交易状态等待买家确认') !== false)
					&& $update['tmall_order_close'] == '订单未关闭' 
					){
					
					//echo $data['order_id'].' 付尾款'.' '.$data['tmall_order_status'].$data['tmall_order_close'].$data['tmall_pay_level'].'<br />';
					$count_error++;
					$update['pay_status'] = 2;
				}

				//未付尾款
				if($data['tmall_order_status'] == '交易关闭' 
					&& strpos($update['tmall_pay_level2'], '阶段2') !== false 
					&& (strpos($update['tmall_pay_level2'], '交易状态已关闭') !== false  
						) 
					&& $update['tmall_order_close'] == '退款' 
					){
					
					echo $update['order_id'].' 未付尾款'.' '.$data['tmall_order_status'].$update['tmall_order_close'].$update['tmall_pay_level'].'<br />';
					$count_ig++;

					$update['pay_status'] = 1;
				}

				//print_r($update);
				$update['type'] =  'update_order_pay_status';
				$Model->create($update);
				$Model->where(array('order_id'=>$data['order_id']))->save($update);
				//echo $Model->getLastSql().'<br />';

				//exit;
				
			}
		}

		echo $count_success.' '.$count_error.' '.$count_ig;
		/*
		$this->assign('importResult',$importResult);
		$this->assign('countSuccess',$count_success);
		$this->assign('countError',$count_error);
		$this->assign('countIg',$count_ig);

    	$this->display('Public:import_result');
    	*/
	}
	public function updateComment(){
		set_time_limit(0);
    	header('Content-Type:text/html;charset=utf-8');
    	//$uploadInfo = $this->upload();
    	$Model = D('Order');
    	$count_success = 0;
    	$count_error = 0;
    	$count_ig = 0;
    	$importResult = array();
    	$uploadInfo[0]['savename'] = '52881defa1a3e.csv';
    	$Model = D('Order');
    	if($uploadInfo){
    		
			$list = $this->getCsv('./Uploads/'.$uploadInfo[0]['savename']);

			$result = array();
			$count = 0;
			//var_dump($list);
			//exit;
			foreach ($list as $key => $value) {
				if($key == 0) continue; //过滤掉第一行
$data = array();
				$data['order_id'] = $value[0];
				$data['comment_ob'] = $value[23];

				preg_match_all( "/\d+/", $data['comment_ob'], $numList );

				if(count($numList[0]) == 0){

					$data['status'] = 3;
				}else{
					foreach ($numList[0] as $key1 => $value1) {
						if(strlen($value1) == 9){
							$data['card'] 	= $value1;
							$data['status'] = 2;
						}
					}
					if(empty($data['card'])){
						$data['status'] = 1;
					}
				}

				if($data['status'] != 2){

					continue;
				}
				//$info = $Model->where(array('order_id'=>$data['order_id']))->find();
				//if($info['card'] != '' && $info['card'] != $data['card']){
				//	echo 'update ihg_order set  card = '.$data['card'].', status = 7 , comment_ob = '.$data['comment_ob'].' where order_id = '.$data['order_id'].";\n";
				//}
				//echo implode(',',$data)."\n";
				
				echo 'update ihg_order set   comment_ob = "'.trim($data['comment_ob']).'" where order_id = '.$data['order_id'].";\n";

			}
		}
	}
}