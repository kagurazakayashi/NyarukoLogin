<?php 
    require 'yaloginSQLSetting.php';

    class yaloginSQLC {
        public $sqlset;

        function init() {
            $this->sqlset = new YaloginSQLSetting();
        }

        //执行SQL连接
        function sqlc($sqlcmd,$voidiserr = false,$onedata = false) {
            $con=mysqli_connect($this->sqlset->db_host,$this->sqlset->db_user,$this->sqlset->db_password,$this->sqlset->db_name,$this->sqlset->db_port);
            $sqlerrno = mysqli_connect_errno($con);
            if ($sqlerrno) {
                //die($sqlerrno);
                return 90000;
                //echo "Failed to connect to MySQL: " . mysqli_connect_error();
            }
            $result = mysqli_query($con,$sqlcmd);
            mysqli_close($con);
            if ($result) {
                if(@mysqli_num_rows($result)) {
                    $result_array = array();
                    if ($onedata == true) {
                        $result_array = mysqli_fetch_array($result);
                    } else {
                        $rowi = 0;
                        while ($row = mysqli_fetch_array($result)) {
                            $result_array[$rowi] = $row;
                            $rowi++;
                        }
                    }
                    if($result_array) {
                        if (count($result_array) > 0) {
                            return $result_array;
                        } else {
                            return 90104;
                        }
                        //foreach($result_array as $result_row) {
                            //print_r($result_array);
                        //}
                    } else {
                        return 90103;
                    }
                } else {
                    if ($voidiserr == true) {
                        return 90102;
                    }
                    return 0;
                }
            } else {
                //die($sqlcmd);
                return 90101;
            }
            return 90100;
        }

        //记录日志
        function savereg($userlogininfoid = 0,$hash = "",$datetime = "",$ip = "",$modeid = 0) {
            $sqlcmd = "insert `".$this->sqlset->db_name."`.`".$this->sqlset->db_loginhistory_table."`(`userhash`,`userlogintime`,`userloginip`,`userloginapp`,`userlogininfo`,`mode`) values('".$hash."','".$datetime."','".$ip."','".$this->sqlset->db_app."',".$userlogininfoid.",".$modeid.");";
            $result_array = $this->sqlc($sqlcmd);
            if (is_int($result_array)) {
                return 0;
            } else {
                //print_r($result_array);
                $userrep = $result_array["count(0)"];
                if ($userrep > 0) {
                    return 1;
                }
                return 0;
            }
        }
    }
?>