<?php
    require_once "kylsetting.php";
    function kylconnect_select($selectArr,$tableStr,$whereStr,$whereIsStr) {
        $sqlcmd = "SELECT `".implode('`,`',$selectArr)."` FROM `".$kylg_db_name."`.`".$tableStr."` WHERE `".$whereStr."` = '".$whereIsStr."';";
        return kylconnect_sqlc($sqlcmd);
    }
    function kylconnect_insert($insertDic,$tableStr) {
        $keys = "";
        $vals = "";
        while(list($key,$val) = each($insertDic)) { 
            $keys += "`".$key."`,";
            $vals += "'".$val."',";
        }
        $sqlcmd = "INSERT INTO `".$kylg_db_name."`.`".$tableStr."`(".substr($keys, 0, -1).") VALUES(".substr($vals, 0, -1).");";
        return kylconnect_sqlc($sqlcmd);
    }
    function kylconnect_update($updateDic,$whereStr,$whereIsStr) {
        $update = "";
        while(list($key,$val) = each($updateDic)) { 
            $update += "`".$key."` = '".$val."', ";
        }
        $sqlcmd = "UPDATE `".$kylg_db_name."`.`".$tableStr."` SET ".substr($update, 0, -2)." WHERE `".$whereStr."` = '".$whereIsStr."';";
        return kylconnect_sqlc($sqlcmd);
    }
    //执行SQL连接
    function kylconnect_sqlc($sqlcmd) {
        $con = mysqli_connect($kylg_db_host,$kylg_db_user,$kylg_db_password,$kylg_db_name,$kylg_db_port);
        $sqlerrno = mysqli_connect_errno($con);
        if ($sqlerrno) {
            header('Content-type:text/json');
            die(json_encode(array("stat"=>2100,"msg"=>"Failed to connect to DB, ".$sqlerrno)));
        }
        $result = mysqli_query($con,$sqlcmd);
        mysqli_close($con);
        if ($result) {
            if(@mysqli_num_rows($result)) {
                $result_array = array();
                $rowi = 0;
                while ($row = mysqli_fetch_array($result)) {
                    $result_array[$rowi] = $row;
                    $rowi++;
                }
                if($result_array) {
                    if (count($result_array) > 0) {
                        return result_array; //1100;
                    } else {
                        header('Content-type:text/json');
                        die(json_encode(array("stat"=>2102,"msg"=>"Error returning data.")));
                    }
                }
            } else {
                return 1101;
            }
        } else {
            header('Content-type:text/json');
            die(json_encode(array("stat"=>2101,"msg"=>"SQL Error.")));
        }
    }
?>