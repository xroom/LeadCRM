<?php 
 function getOrderStatus($str = null){
   $data = array(
    0 =>'导入成功',
    1 =>'卡号有误',
    2 =>'A类短信',
    3=>'B类短信',
    4 =>'信息有误',
    5 =>'手机号重复',
    6 =>'需要注册',
    7 =>'信息确认',
    8 =>'联系失败',
    9 =>'注册成功',
    10 =>'注册失败',
    11 =>'注入成功',
    12 =>'注入失败',
    13 =>'发货完成',
    14 =>'退款',
    15 =>'信息无效',

    );

   if(is_null($str)){
    return $data;
   }else{
     return $data[$str];

   }
}
function getOrderStatusLabel($str = null){
    $data = array(
        0 =>'label-success',
        1 =>'label-warning',
        2 =>'label-padding',
        3=>'label-padding',
        4 =>'label-warning',
        5 =>'label-warning',
        6 =>'label-warning',
        7 =>'label-success',
        8 =>'label-warning',
        9 =>'label-success',
        10 =>'label-warning',
        11 =>'label-success',
        12 =>'label-warning',
        13 =>'label-success',
        14 =>'label-warning'
    );
     if(is_null($str)){
    return $data;
   }else{
     return $data[$str];

   }
}
function getFieldName($str){
    $data = array(
            'id' =>'订单ID', 'tmall_name' =>'天猫用户名', 'email' =>'电子邮件', 'tmall_order_status' =>'天猫订单状态',
             'comment' =>'用户备注', 'name' =>'姓名', 'address' =>'地址', 'phone' =>'固定电话', 'mobile' =>'手机号',
              'product_name' =>'商品名称', 'tmall_create_time' =>'天猫订单时间', 'count' =>'数量', 'status' =>'状态',
               'backup' =>'原始信息', 'create_time' =>'创建时间', 'name_py' =>'姓名拼音', 'address_py' =>'地址拼音', 
               'product_id' =>'商品ID', 'deposit_id' =>'deposit id', 'card' =>'卡号' ,
               'text'=>'短信内容'
            );

     if(is_null($str)){
    return $data;
   }else{
     return $data[$str];

   }
}
function is_email($email){
  return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/", $email);
}
function dateformat($time,$format){
  return date($format,$time);
}
function getTimelineStatus($status = null){
   $data = array(
       'insert'=> '新增',
       'update' => '手工更新',
       'import_update' => '天猫导入更新',
      'import_insert' => '天猫导入新增',
      'import_du_update' => '天猫导入重复信息更新',

      'import_sms_update' => '短信导入更新',
      'import_sms_du_update' => '短信导入重复信息更新',

      'ob_update' => '客服备注更新',
      'ob_insert' => '客服备注添加',
       'import_ts_update' => '800TS导入',
       '' => '未知操作',
    );
     if(is_null($status)){
    return $data;
   }else{
     return $data[$status];

   }
}
?>