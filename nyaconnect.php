<?php
    require_once "nyasetting.php";
    //查询数据
    function nyaconnect_select($selectArr,$tableStr,$whereStr,$whereIsStr) {
        $sqlcmd = "SELECT `".implode('`,`',$selectArr)."` FROM `".$nya_db_name."`.`".$tableStr."` WHERE `".$whereStr."` = '".$whereIsStr."';";
        return nyaconnect_sqlc($sqlcmd);
    }
    //插入数据
    function nyaconnect_insert($insertDic,$tableStr) {
        $keys = "";
        $vals = "";
        while(list($key,$val) = each($insertDic)) { 
            $keys += "`".$key."`,";
            $vals += "'".$val."',";
        }
        $sqlcmd = "INSERT INTO `".$nya_db_name."`.`".$tableStr."`(".substr($keys, 0, -1).") VALUES(".substr($vals, 0, -1).");";
        return nyaconnect_sqlc($sqlcmd);
    }
    //更新数据
    function nyaconnect_update($updateDic,$whereStr,$whereIsStr) {
        $update = "";
        while(list($key,$val) = each($updateDic)) { 
            $update += "`".$key."` = '".$val."', ";
        }
        $sqlcmd = "UPDATE `".$nya_db_name."`.`".$tableStr."` SET ".substr($update, 0, -2)." WHERE `".$whereStr."` = '".$whereIsStr."';";
        return nyaconnect_sqlc($sqlcmd);
    }
    //执行SQL连接
    function nyaconnect_sqlc($sqlcmd) {
        $con = mysqli_connect($nya_db_host,$nya_db_user,$nya_db_password,$nya_db_name,$nya_db_port);
        $sqlerrno = mysqli_connect_errno($con);
        if ($sqlerrno) {
            header('Content-type:text/json');
            die(json_encode(array("stat"=>2100,"msg"=>"未能连接到数据库，".$sqlerrno)));
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
                        return [1100,$result_array]; //SQL语句成功执行。
                    } else {
                        header('Content-type:text/json');
                        die(json_encode(array("stat"=>2102,"msg"=>"数据库未能返回正确的数据。")));
                    }
                }
            } else {
                return [1101]; //SQL语句成功执行，返回0值。
            }
        } else {
            header('Content-type:text/json');
            die(json_encode(array("stat"=>2101,"msg"=>"数据库错误。")));
        }
    }
?>