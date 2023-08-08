<?php

include_once( '../dbfunctions.php' );
// include_once( '../../class/recievePayment.php' );
require('../../vendor/autoload.php');
use Ramsey\Uuid\Uuid;


class API extends dbobject {
    //    validate Plate

    public function anpr( $data ) {
        // return $data[0]['plate'];
        $uuid = Uuid::uuid4();

        // $folder = "../Logs/".date("Y")."_".date("M")."_".date("D")."_".date("d")."/".date("h")."";
        $folder = '../Logs/' . date('Y') . '/' . date('M') . '/';
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        $counter = 0;
        
        file_put_contents( $folder.'/Day_' . date('d') . '_plate_logs.txt', 'Logged @ ' . date('h:ia') .json_encode( $data, JSON_PRETTY_PRINT ).PHP_EOL, FILE_APPEND );

        // $dataArray = json_decode($data, true);
        foreach($data as $value){
            // $datas[''] = $value['image'];
            $base64Image = $value['image'];

            // Decode base64 to binary image data
            $imageData = base64_decode($base64Image);
            $fileName = $this->generateUniqueFilename();
            ;
            $saveDirectory = "../../image_directory";

            if (!is_dir($saveDirectory)) {
                mkdir($saveDirectory, 0777, true);
            }

            $filePath = $saveDirectory . "/" . $fileName;
            file_put_contents($filePath, $imageData);
            $imagepath = "image_directory/" . $fileName;


            $datas['id'] = substr($uuid->toString(), 0, 8);
            $datas['raw_image'] = $value['image'];
            $datas['plate'] = $value['plate'];
            $datas['bodystyle'] = $value['log_data']['plates'][0]['car']['bodyStyle'][0]['name'];
            $datas['make'] = $value['log_data']['plates'][0]['car']['makeModelYear'][0]['make'];
            $datas['model'] = $value['log_data']['plates'][0]['car']['makeModelYear'][0]['model'];
            $datas['color'] = $value['log_data']['plates'][0]['car']['color'][0]['name'];
            $datas['status'] = $value['status'] = 0;
            $datas['image'] = $imagepath;
            $datas['created'] = date('d-m-y h:i:s');
            $datas['log_data'] = json_encode($value['log_data'], true);
            $datas['batch_id'] = 0;

            $sql = "SELECT * FROM track_tb WHERE number_plate = '$value[plate]'";
            $result = $this->db_query($sql);
            $subject = "Plate Spotted";
            // $count = count($result);

            if (is_array($result)) {
                $res = $result[0];
                $datas['is_stolen'] = 1;
                // $name = $res['business_name'];
                $sql_insert = "INSERT into track_found_tb (track_id,location,ptime) VALUES ('0','FCT WUSE',NOW())";
                $this->db_query($sql_insert, false);
                $mail_data = '
                <html>
                    <div style="background-color:#e1e1e1; width:100%; height:100%; margin:0px; padding:45px;">
                        <div style="width:500px; background:white; margin:0px auto; padding:25px;">
                            <h2>
                                <center>
                                    <img src="cid:logo" style="max-height:75px;">
                                </center>
                            </h2>
                            <p style="background-color:#36b466; color:white; font-size:18px; text-align:center; padding:8px;">ANPR DASHBOARD</p>
                            <h3 style="text-align:center;">' . $value['plate'] . ' Spotted</h3>
                            <p style="text-align:center;">' . $value['plate'] . ' was spotted at "#FCT, WUSE" ' . date('d-m-y h:i:s') . '</p>
                        </div>
                    </div>
                </html>';
                $this->sendMailEmailNotifications($res['track_email'], $subject, $mail_data);

                $formatted_number = "234" . ltrim($res['Officer_mobile_number'], '0');
                $sms_id = rand(0, 5000);
                $username = "anpr_user";
                $password = "@ANPR2206";
                $sms_to = $formatted_number;
                $sms_from = "FCT%20eVREG";
                $captured = date('d-m-y');
                $sms_message = "A stolen vehicle was captured at FCT WUSE with PLATE NUMBER $value[plate] at $captured";

                $sms_message = str_replace(" ", "%20", $sms_message);
                $host = 'http://www.mobbow.com/post_sms.php?username=' . $username . '&password=' . $password . '&sms_id=' . $sms_id . '&sms_to=' . $sms_to . '&sms_from=' . $sms_from . '&sms_message=' . $sms_message . '';
                file_put_contents('host.txt', $host);


                $curl = curl_init();
                curl_setopt_array(
                    $curl,
                    array(
                        CURLOPT_URL => 'http://www.mobbow.com/post_sms.php?username=' . $username . '&password=' . $password . '&sms_id=' . $sms_id . '&sms_to=' . $sms_to . '&sms_from=' . $sms_from . '&sms_message=' . $sms_message . '',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                    )
                );

                $response = curl_exec($curl);

                curl_close($curl);
                // echo $response;
                if ($response == 1) {
                    $stolen = "Message sent successfully";
                } else {
                    $stolen = "Message could not be sent";
                }
            }else{
                $datas['is_stolen'] = 0;
                $stolen = "No stolen vehicle detected";
            }

            $exc = $this->doInsert('plate_no_tbl', $datas, array());
            if($exc > 0)
            {
                file_put_contents($folder . '/Day_' . date('d') . '_inserted_plate.txt', 'Logged @ ' . date('h:ia') .json_encode($value, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);
                $counter++;
            }
            else
            {
                file_put_contents($folder . '/Day_' . date('d') . '_failed_plates.txt', 'Logged @ ' . date('h:ia') .json_encode($value, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);
            }
        }
            if ($counter > 0) 
            {
                return json_encode(array('response_code' => '200', 'message' => 'Success', 'stolenVehicleSmsStatus' => $stolen));
            } 
            else 
            {
                return json_encode(array('response_code' => 0, 'response_message' => 'an error occured recieving plates'));
            }
    }

    // Generate a unique filename using timestamp and random number
function generateUniqueFilename() {
    $microtime = microtime(); // Get the current microtime
    list($seconds, $microSeconds) = explode(' ', $microtime);

    $randomNumber = mt_rand(1000, 9999); // Generate a random 4-digit number

    $uniqueFilename = "image_" . $seconds . "_" . $microSeconds . "_" . $randomNumber . ".jpg";

    return $uniqueFilename;
}


    public function verify_anpr( $data ) {
        // var_dump( $data );
        
        $plate = $data[ 'plate_number' ];
        // echo $plate;
        $sql = "SELECT * FROM  number_plate_data_tb WHERE number_plate = '$plate'";
        $dataRes = $this->db_query( $sql );

        $count = count( $dataRes );
        // echo $count;
        
        if ( $count > 0 ) {
            return json_encode( $dataRes );
        } else {
            return json_encode( array( 'response_code'=>0, 'response_message'=>'an error occured' ) );
        }
        // $res = $this->verify( $data );
    }

    public function fetch_offence( $data )
    {
        $val = isset($data['offence_type'])?$data['offence_type']:"";
        $start = ($data['start'])?$data['start']:"0";
        $end = ($data['end'])?$data['end']:"10";
        $limit = "LIMIT ".$start.", ".$end."";
        // var_dump($limit); exit;
        // if($val==""){
        //     return json_encode(array('response_code'=>202,'response'=>'No offence type recieved. Data is required'));
        // }else{
        $where = (($val=="")?'ORDER BY id ASC':'WHERE offence_type="'.$val.'" ORDER BY id ASC');
        $sql ="SELECT * FROM offenders $where $limit";
        file_put_contents("query.txt", $sql);
        //  var_dump($sql); exit;
        $data = $this->db_query($sql);
        // $datum = 0;
        foreach ( $data as $datas )
        {
            $datum[] = array("Plate"=>$datas['plate_number'], "Offence"=>$datas['offence_type'], "Price"=>$datas['amount'], "Payment Status"=>$datas['payment_status'], "Description"=>$datas['offence_description'], "Created"=>$datas['created']);
        }

        // $count = count( $data );
        $total_record = "SELECT COUNT(id) as counter FROM offenders $where";
        $count = $this->getCount($total_record);
        if ( is_array($data))
        {
            return json_encode(array('total records'=>$count, 'data'=>$datum ));
        }
        else
        {
            return json_encode( array( 'response_code'=>204, 'response_message'=>'Record for "'.$val.'" not found' ) );
        }
     
    }

    

    public function get_count($data)
    {
        $val = isset($data['offence_type']) ? $data['offence_type'] : "";

        $where = (($val == "") ? 'ORDER BY id ASC' : 'WHERE offence_type="' . $val . '" ORDER BY id ASC');

        $total_record = "SELECT COUNT(id) as counter FROM offenders $where";
        $count = $this->getCount($total_record);
        if (is_array($data)) {
            return json_encode(array('total_records' => $count));
        } else {
            return json_encode(array('response_code' => 204, 'response_message' => 'Record for "' . $val . '" not found'));
        }

    }


    public function total_offenders($data)
    {
        $order = 'ORDER BY id DESC';

        $total_record = "SELECT COUNT(id) as counter FROM offenders $order";
        $count = $this->getCount($total_record);
        if ($count > 0) {
            return json_encode(array('total_records' => $count));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }

    }

    public function offendersThisMonthRecord($data)
    {
        $order = 'ORDER BY id DESC';
        $month = DATE("m");
        $year  = DATE("Y");

        $start = ($data['start']) ? $data['start'] : "0";
        $end = ($data['end']) ? $data['end'] : "10";
        $limit = "LIMIT " . $start . ", " . $end . "";

        $total_record = "SELECT * FROM offenders WHERE Month(created) = '$month' AND YEAR(created) = '$year' $order $limit";
        // file_put_contents("insert.txt", $total_record);
        $getRecord = $this->db_query($total_record);
        if (is_array($getRecord)) {
            foreach ($getRecord as $datas) {
                $datum[] = array("Plate" => $datas['plate_number'], "Offence" => $datas['offence_type'], "Price" => $datas['amount'], "Payment Status" => $datas['payment_status'], "Description" => $datas['offence_description'], "Created" => $datas['created']);
            }

            return json_encode(array('total records' => $count = count($getRecord), 'data' => $datum));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }

    }

    public function offenders_this_month($data)
    {
        $order = 'ORDER BY id DESC';
        $month = DATE("m");
        $year = DATE("Y");

        $total_record = "SELECT COUNT(id) as counter FROM offenders WHERE Month(created) = '$month' AND YEAR(created) = '$year' $order";
        $count = $this->getCount($total_record);
        if ($count > 0) {
            return json_encode(array('total_records' => $count));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }

    }

    public function offenders_today($data)
    {
        $order = 'ORDER BY id DESC';
        $month = DATE("m");
        $year = DATE("Y");
        $day = DATE("d");

        $total_record = "SELECT COUNT(id) as counter FROM offenders WHERE Month(created) = '$month' AND YEAR(created) = '$year' AND Day(created) = '$day' $order";
        $count = $this->getCount($total_record);
        if ($count > 0) {
            return json_encode(array('total_records' => $count));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }

    }


    public function offendersToday($data)
    {
        $order = 'ORDER BY id DESC';
        $month = DATE("m");
        $year = DATE("Y");
        $day = DATE("d");

        $start = ($data['start']) ? $data['start'] : "0";
        $end = ($data['end']) ? $data['end'] : "10";
        $limit = "LIMIT " . $start . ", " . $end . "";

        $total_record = "SELECT * FROM offenders WHERE Month(created) = '$month' AND YEAR(created) = '$year' AND Day(created) = '$day' $order $limit";
        // file_put_contents("insert.txt", $total_record);
        $getRecord = $this->db_query($total_record);
        if (is_array($getRecord)) {
            foreach ($getRecord as $datas) {
                $datum[] = array("Plate" => $datas['plate_number'], "Offence" => $datas['offence_type'], "Price" => $datas['amount'], "Payment Status" => $datas['payment_status'], "Description" => $datas['offence_description'], "Created" => $datas['created']);
            }

            return json_encode(array('total_records' => $count = count($getRecord), 'data' => $datum));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }

    }

    public function vehWithExpLicense($data)
    {

        $total_record = "SELECT COUNT(id) as counter FROM offenders WHERE offence_type = 'Invalid vehicle license'";
        $count = $this->getCount($total_record);
        if ($count > 0) {
            return json_encode(array('total_records' => $count));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }
    }


    public function expVehThisMonth($data)
    {
        $month = DATE("m");
        $year = DATE("Y");
        $day = DATE("d");

        $total_record = "SELECT COUNT(id) as counter FROM offenders WHERE offence_type = 'Invalid vehicle license' AND Month(created) = '$month' AND YEAR(created) = '$year'";
        $count = $this->getCount($total_record);
        if ($count > 0) {
            return json_encode(array('total_records' => $count));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }
    }


    public function expVehToday($data)
    {
        $month = DATE("m");
        $year = DATE("Y");
        $day = DATE("d");

        $total_record = "SELECT COUNT(id) as counter FROM offenders WHERE offence_type = 'Invalid vehicle license' AND Month(created) = '$month' AND YEAR(created) = '$year' AND DAY(created) = '$day'";
        $count = $this->getCount($total_record);
        if ($count > 0) {
            return json_encode(array('total_records' => $count));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }
    }


    public function VehNotRoadworthy($data)
    {

        $total_record = "SELECT COUNT(id) as counter FROM offenders WHERE offence_type = 'Not Roadworthy'";
        $count = $this->getCount($total_record);
        if ($count > 0) {
            return json_encode(array('total_records' => $count));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }
    }


    public function notRoadworthyThisMonth($data)
    {
        $month = DATE("m");
        $year = DATE("Y");
        $day = DATE("d");

        $total_record = "SELECT COUNT(id) as counter FROM offenders WHERE offence_type = 'Not Roadworthy' AND Month(created) = '$month' AND YEAR(created) = '$year'";
        $count = $this->getCount($total_record);
        if ($count > 0) {
            return json_encode(array('total_records' => $count));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }
    }

    public function notRoadworthyToday($data)
    {
        $month = DATE("m");
        $year = DATE("Y");
        $day = DATE("d");

        $total_record = "SELECT COUNT(id) as counter FROM offenders WHERE offence_type = 'Not Roadworthy' AND Month(created) = '$month' AND YEAR(created) = '$year' AND DAY(created) = '$day'";
        $count = $this->getCount($total_record);
        if ($count > 0) {
            return json_encode(array('total_records' => $count));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }
    }

    public function expiredVehicleThisMonth($data)
    {
        $order = 'ORDER BY id DESC';
        $month = DATE("m");
        $year = DATE("Y");
        $day = DATE("d");

        $start = ($data['start']) ? $data['start'] : "0";
        $end = ($data['end']) ? $data['end'] : "10";
        $limit = "LIMIT " . $start . ", " . $end . "";

        $total_record = "SELECT * FROM offenders WHERE offence_type ='Invalid vehicle license' AND  Month(created) = '$month' AND YEAR(created) = '$year' $order $limit";
        // file_put_contents("insert.txt", $total_record);
        $getRecord = $this->db_query($total_record);
        if (is_array($getRecord)) {
            foreach ($getRecord as $datas) {
                $datum[] = array("Plate" => $datas['plate_number'], "Offence" => $datas['offence_type'], "Price" => $datas['amount'], "Payment Status" => $datas['payment_status'], "Description" => $datas['offence_description'], "Created" => $datas['created']);
            }

            return json_encode(array('total_records' => $count = count($getRecord), 'data' => $datum));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }

    }


    public function expiredVehicleToday($data)
    {
        $order = 'ORDER BY id DESC';
        $month = DATE("m");
        $year = DATE("Y");
        $day = DATE("d");

        $start = ($data['start']) ? $data['start'] : "0";
        $end = ($data['end']) ? $data['end'] : "10";
        $limit = "LIMIT " . $start . ", " . $end . "";

        $total_record = "SELECT * FROM offenders WHERE offence_type ='Invalid vehicle license' AND  Month(created) = '$month' AND YEAR(created) = '$year' AND DAY(created) = '$day' $order $limit";
        // file_put_contents("insert.txt", $total_record);
        $getRecord = $this->db_query($total_record);
        if (is_array($getRecord)) {
            foreach ($getRecord as $datas) {
                $datum[] = array("Plate" => $datas['plate_number'], "Offence" => $datas['offence_type'], "Price" => $datas['amount'], "Payment Status" => $datas['payment_status'], "Description" => $datas['offence_description'], "Created" => $datas['created']);
            }

            return json_encode(array('total_records' => $count = count($getRecord), 'data' => $datum));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }

    }

    public function notRoadworthyCapturedThisMonth($data)
    {
        $order = 'ORDER BY id DESC';
        $month = DATE("m");
        $year = DATE("Y");
        $day = DATE("d");

        $start = ($data['start']) ? $data['start'] : "0";
        $end = ($data['end']) ? $data['end'] : "10";
        $limit = "LIMIT " . $start . ", " . $end . "";

        $total_record = "SELECT * FROM offenders WHERE offence_type ='Not Roadworthy' AND  Month(created) = '$month' AND YEAR(created) = '$year' $order $limit";
        // file_put_contents("insert.txt", $total_record);
        $getRecord = $this->db_query($total_record);
        if (is_array($getRecord)) {
            foreach ($getRecord as $datas) {
                $datum[] = array("Plate" => $datas['plate_number'], "Offence" => $datas['offence_type'], "Price" => $datas['amount'], "Payment Status" => $datas['payment_status'], "Description" => $datas['offence_description'], "Created" => $datas['created']);
            }

            return json_encode(array('total_records' => $count = count($getRecord), 'data' => $datum));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }

    }

    public function notRoadworthyCapturedToday($data)
    {
        $order = 'ORDER BY id DESC';
        $month = DATE("m");
        $year = DATE("Y");
        $day = DATE("d");

        $start = ($data['start']) ? $data['start'] : "0";
        $end = ($data['end']) ? $data['end'] : "10";
        $limit = "LIMIT " . $start . ", " . $end . "";

        $total_record = "SELECT * FROM offenders WHERE offence_type ='Not Roadworthy' AND  Month(created) = '$month' AND YEAR(created) = '$year' AND DAY(created) = '$day' $order $limit";
        // file_put_contents("insert.txt", $total_record);
        $getRecord = $this->db_query($total_record);
        if (is_array($getRecord)) {
            foreach ($getRecord as $datas) {
                $datum[] = array("Plate" => $datas['plate_number'], "Offence" => $datas['offence_type'], "Price" => $datas['amount'], "Payment Status" => $datas['payment_status'], "Description" => $datas['offence_description'], "Created" => $datas['created']);
            }

            return json_encode(array('total_records' => $count = count($getRecord), 'data' => $datum));
        } else {
            return json_encode(array('total_records' => 0, 'response_code' => 204, 'response_message' => 'No record available'));
        }

    }

}

?>