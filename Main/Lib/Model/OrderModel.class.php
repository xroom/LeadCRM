<?php
Class OrderModel extends AdvModel{

	public function _after_update(&$result,$option){
		
		//更新后，执行日志操作
		$data = $result;
		$data['sql_run_time'] = mktime();
		$data['sql_run_username'] = $_SESSION['username'];
		$data['sql_run_ip']	= get_client_ip();

		$cache = S('Order_'.$result['id']);
		$cache[] = $data;
		$res = S('Order_'.$result['id'],$cache);
		
		
	}

	public function _after_insert(&$result,$option){
		$data = $result;
		$data['sql_run_time'] = mktime();
		$data['sql_run_username'] = $_SESSION['username'];
		$data['sql_run_ip']	= get_client_ip();

		$cache = S('Order_'.$result['id']);
		$cache[] = $data;
		$res = S('Order_'.$result['id'],$cache);

		
	}
	

}

?>