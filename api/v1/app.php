<?php
header("Access-Control-Allow-Origin: *");
require('../../libs/dbfunctions.php');
require('../route.php');
error_reporting(0);

$API = new API();
$dbobject   = new dbobject();

$request     = explode('/', trim($_SERVER['REQUEST_URI'],'/'));
$data        = json_decode(file_get_contents("php://input"),true);
$headers		= formatHeader($_SERVER);
$index       = count($request) - 1;
file_put_contents("log.txt", json_encode($data));
$endpoint=  $request[$index];
$expected_token = isset($headers['token'])?$headers['token']:"";

// var_dump($request);
// var_dump($data);
$REMOTE_IP = $headers['REMOTE_ADDR'];
$ip = ["::1","192.168.80.145"];

// if(in_array($REMOTE_IP, $ip)){

   if($endpoint == "anpr"){
      if($_SERVER['REQUEST_METHOD']!=='POST')
      {
         echo json_encode(array('response_code'=>'401', 'response_message'=>'INVALID HTTP METHOD. VALID METHOD IS POST'));
      }
      else
      {

         $res = $API->anpr($data);
         // $status = $API->anpr($data);
         echo $res;
         // print_r($data);
      }
  }else if($endpoint =="anpr_verify"){
   if($_SERVER['REQUEST_METHOD']!=='POST')
   {
      echo json_encode(array('response_code'=>'401', 'response_message'=>'INVALID HTTP METHOD. VALID METHOD IS POST'));
   }
   else
   {

      $res = $API->verify_anpr($data);
      echo $res;
   }
  }else if($endpoint =="getOffences"){
   if($_SERVER['REQUEST_METHOD']!=='POST')
   {
      echo json_encode(array('response_code'=>'401', 'response_message'=>'INVALID HTTP METHOD. VALID METHOD IS POST'));
   }
   else
   {
         if($expected_token == NULL){
            echo json_encode(array('response_code'=>'407', 'response_message'=>'Token authorization is required'));
         }else{
            $sql ="SELECT * FROM api WHERE token = '$expected_token'";
            $datas = $dbobject->db_query($sql);
            if ( is_array($datas))
            {
               $res = $API->fetch_offence($data);
               echo $res;
            }else{
               echo json_encode(array('response_code'=>'409', 'response_message'=>'Invalid token sent'));
            }
         }
   }
  }else if($endpoint =="getOffenceCount"){
   if($_SERVER['REQUEST_METHOD']!=='POST')
   {
      echo json_encode(array('response_code'=>'401', 'response_message'=>'INVALID HTTP METHOD. VALID METHOD IS POST'));
   }
   else
   {
         if($expected_token == NULL){
            echo json_encode(array('response_code'=>'407', 'response_message'=>'Token authorization is required'));
         }else{
            $sql ="SELECT * FROM api WHERE token = '$expected_token'";
            $datas = $dbobject->db_query($sql);
            if ( is_array($datas))
            {
               $res = $API->get_count($data);
               echo $res;
            }else{
               echo json_encode(array('response_code'=>'409', 'response_message'=>'Invalid token sent'));
            }
         }
   }

} else if ($endpoint == "totalOffenders") {
   if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS GET'));
   }else{
      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->total_offenders($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "offendersThisMonth") {
   if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS GET'));
   } else {
      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->offenders_this_month($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "offendersRecordThisMonth") {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS POST'));
   } else {
      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->offendersThisMonthRecord($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
}else if ($endpoint == "offendersRecordToday") {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS POST'));
   } else {
      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->offendersToday($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
}else if($endpoint == "offendersToday"){
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS GET'));
   } else {

      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
      $res = $API->offenders_today($data);
      echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "expiredVehicleThisMonth") {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS POST'));
   } else {

      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->expiredVehicleThisMonth($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "notRoadworthyCapturedThisMonth") {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS POST'));
   } else {

      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->notRoadworthyCapturedThisMonth($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "notRoadworthyCapturedToday") {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS POST'));
   } else {

      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->notRoadworthyCapturedToday($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "expiredVehicleToday") {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS POST'));
   } else {

      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->expiredVehicleToday($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "veh_with_exp_license") {

   if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS GET'));
   } else {

      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->vehWithExpLicense($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "exp_veh_this_month") {


   if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS GET'));
   } else {

      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->expVehThisMonth($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "exp_veh_today") {


   if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS GET'));
   } else {

      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->expVehToday($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "veh_not_roadworthy") {


   if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS GET'));
   } else {

      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->VehNotRoadworthy($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "veh_not_roadworthy_this_month") {


   if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS GET'));
   } else {

      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->notRoadworthyThisMonth($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
} else if ($endpoint == "not_roadworthy_today") {


   if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      echo json_encode(array('response_code' => '401', 'response_message' => 'INVALID HTTP METHOD. VALID METHOD IS GET'));
   } else {

      $sql = "SELECT * FROM api WHERE token = '$expected_token'";
      $datas = $dbobject->db_query($sql);
      if (is_array($datas)) {
         $res = $API->notRoadworthyToday($data);
         echo $res;
      } else {
         echo json_encode(array('response_code' => '409', 'response_message' => 'Invalid token sent'));
      }
   }
}else{
         echo json_encode(array('response_code'=>'409', 'response_message'=>''.$endpoint.' Doesnt Exists!'));
   
   }

// }else{
//    echo json_encode(array('response_code'=>'402', 'response_message'=>'Access Denied'));
// }

function formatHeader($headers)
{
	foreach($headers as $key => $value) 
	{
        if (substr($key, 0, 5) <> 'HTTP_') 
		{
            continue;
        }
        $header = str_replace(' ', '-', strtolower(str_replace('_', ' ', strtolower(substr($key, 5)))));
        $headers[$header] = $value;
    }
    return $headers;	
}

function logInputs($tag,$details,$folder)
{
   $target_dir = 'Logs/'.$folder.'/'.date("Y_m")."/";
   if (!file_exists($target_dir)) 
   {
      mkdir($target_dir, 0777, true);
   }
   $det=is_array($details)?json_encode($details):$details;
   $det .= "\r\nHeader sent : \r\n".json_encode(apache_request_headers());
  file_put_contents($target_dir."response_".date('Ymd').".txt",$tag."	@ ".date('H:i:s')."\r\n".$det."\r\n"."=====================================\r\n".PHP_EOL,FILE_APPEND);

}
?>