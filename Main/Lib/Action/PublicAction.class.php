<?php

class PublicAction extends Action {
	var $breadcrumbs;
   
    public function menuAccess(){

        if(empty($_SESSION['username'])){
            $this->error('请登录', __APP__.'/Public/login');
        }

        //权限管理
      switch($_SESSION['username']){
        case 'admin':
          $this->assign('access_admin',1);

          break;
        case 'ob':
          $this->assign('access_ob',1);
          break;
      }
    }
    public function addBreadcrumbs($item){
    	$this->breadcrumbs[] = $item;
    	$this->assign('breadcrumbs',$this->breadcrumbs);
    }
    public function login(){
    	$this->display();
    }
    public function logout(){
      unset($_SESSION['username']);
      $this->success('退出成功',__APP__.'/Public/login');
    }
    public function checkLogin(){
        
    	$_SESSION['username'] = $_POST['username'];

        if($_POST['username'] == 'admin' && $_POST['password'] != '123qwe'){
            $this->error('用户名密码错误');
        }

        if($_POST['username'] == 'ob' && $_POST['password'] != 'ob'){
            $this->error('用户名密码错误');
        }


    	header('location:'.__APP__.'/Index');
    }

    public function upload(){

        import('ORG.Net.UploadFile');
        $upload = new UploadFile();// 实例化上传类
        $upload->maxSize  = 6145728 ;// 设置附件上传大小
        $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg', 'csv','xls','xlsx');// 设置附件上传类型
        $upload->savePath =  './Uploads/';// 设置附件上传目录
         if(!$upload->upload()) {// 上传错误提示错误信息
        $this->error($upload->getErrorMsg());
         }else{// 上传成功 获取上传文件信息
        $info =  $upload->getUploadFileInfo();
         }
       return $info;
    }

    public function getCsv($filename,$fromChartSet,$toChartSet){
        $res = array();

        $file = fopen($filename,"r");
        $count = 0;
        while(! feof($file))
          {
           $tmp = fgetcsv($file);
            
            foreach ($tmp as $key => $value) {
                $res[$count][] = iconv('GBK','UTF-8',$value);
            }
            $count++;
          }

        fclose($file);

        return $res;
    }
    public function exportCSV($filename,$data){
      
        $file = fopen($filename,"w");

        foreach ($data as $line)
          {
          fputcsv($file,$line);
          }

        fclose($file);
    }
    public function getExcel($filename){  
        $res= $this->read($filename, "UTF-8", 'xlsx' );//传参,判断office2007还是office2003  
  
        foreach ( $res as $k => $v ) //循环excel表  
           {  
               $k=$k-1;//addAll方法要求数组必须有0索引  
               $data[$k]['name1'] = $v [0];//创建二维数组  
               $data[$k]['name2'] = $v [1];  
               $data[$k]['name3'] = $v [2];       
               $data[$k]['name4'] = $v [3];  
          }  
         
         print_r($data);

         exit;
  

    }


  public function read($filename,$encode,$file_type){
            Vendor("Excel.PHPExcel");//引入phpexcel类(注意你自己的路径)  
            Vendor("Excel.PHPExcel.IOFactory");       

            if(strtolower ( $file_type )=='xls')//判断excel表类型为2003还是2007
            {
                Vendor("Excel.PHPExcel.Reader.Excel5"); 
                $objReader = PHPExcel_IOFactory::createReader('Excel5');
            }elseif(strtolower ( $file_type )=='xlsx')
            {
                Vendor("Excel.PHPExcel.Reader.Excel2007"); 
                $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            }
            $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($filename);
            $objWorksheet = $objPHPExcel->getActiveSheet();
            $highestRow = $objWorksheet->getHighestRow();
            $highestColumn = $objWorksheet->getHighestColumn();
            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
            $excelData = array();
            for ($row = 1; $row <= $highestRow; $row++) {
                for ($col = 0; $col < $highestColumnIndex; $col++) {
                    $excelData[$row][] =(string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                    }
            }
            return $excelData;
      }
      public function convertPinyin($word){
    $_String = $word;

    //change 
    $_String = str_replace('重庆','CHONGQING',$_String);
    $_String = str_replace('省', 'SHENG', $_String);
    $_String = str_replace('大厦', 'DASHA', $_String);
	  $_String = str_replace('闵行', 'MINHANG', $_String);
    $_String = str_replace('大', 'DA', $_String);
    $_String = str_replace('行', 'HANG', $_String);
    $_String = str_replace('朝阳', 'CHAOYANG', $_String);
    $_String = str_replace('家', 'JIA', $_String);
    $_String = str_replace('乐', 'LE', $_String);
    $_String = str_replace('科', 'KE', $_String);
    $_String = str_replace('会', 'HUI', $_String);
    $_String = str_replace('牙', 'YA', $_String);
    $_String = str_replace('佛', 'FO', $_String);

    $start = mktime();

    //if(empty($this->pinyinData)){
      $data = explode("\n",file_get_contents('./GBKPY.txt'));

      foreach($data as $key =>$value){
        $temp = explode(' ',$value);
        $dirct[$temp[0]]  = $temp[1];
      }
      $this->pinyinData = $dirct;
    //}

    //$_String = iconv('gbk','utf-8',$_GET['word']);

    //echo $_String;
    //echo mb_strlen($_String,'UTF-8');
    //先把字拆开
    $word = array();
     for($i=0; $i<mb_strlen($_String,'UTF-8'); $i++) { 
        $word[] = array('text'=> mb_substr($_String, $i, 1,'UTF-8'));
    } 
    $res = '';
    foreach($dirct as $key=>$value){
      //echo $key;
      //echo $value;
      foreach($word as $key1=>$value1){
        if(!empty($value1['text'])){
          if(strpos($value,$value1['text']) === false || preg_match('/[a-zA-Z0-9]/',$value1['text']) ){
            
          }else{
            //去掉音调
            $key = preg_replace("/[^a-z]*/", '', $key);
            //是否加入后缀
            $prefixList = '省市区县村巷路弄楼厦场室号座';
            if(strpos($prefixList,$value1['text']) === false){
               $word[$key1]['pinyin'] = $key;
            }else{
              $word[$key1]['pinyin'] = $key.' ';
            }
            

            
          }
        }
      }
    }
    for($i=0;$i<count($word);$i++){
      if(isset($word[$i]['pinyin'])){
        $res .= $word[$i]['pinyin'];
      }else{
        $res .= $word[$i]['text'];
      }
    }
    //echo strtoupper($res);
    return $res;
    }  
    public function setReturnUrl(){
      cookie('return_url','http://'.$_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    }
}