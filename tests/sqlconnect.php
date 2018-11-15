<?php
require_once "../src/nyacore.class.php";
require_once "../src/nyaconnect.class.php";

class sqltest {
    private $debug = true;
    /**
     * @description: 如果开启debug，则JSON转化回数组以便在浏览器输出
     * @param String jsonstr JSON字符串
     */
    function showjson($jsonstr) {
        if ($this->debug) {
            header('Content-Type:text/html;charset=utf-8');
            print_r(json_decode($jsonstr));
        } else {
            header('Content-Type:application/json;charset=utf-8');
            echo $jsonstr;
        }
    }
    /**
     * @description: 启动测试
     * POST @param String test 测试类型
     */
    function starttest() {
        global $nlcore;
        if (!isset($_POST["test"])) die(showjson($nlcore->msg->m(2000100,true)));
        $test = $_POST["test"];
        $nlcore->db->init(0,$this->debug);

        //数据库连接测试
        switch ($test) {
            case "dblink":
                $this->dblink();
                break;
            case "dbinsert":
                $this->dbinsert();
                break;
            default:
                break;
        }
    }

    /**
     * @description: 测试基本数据库连接，显示数据库版本号
     */
    function dblink() {
        global $nlcore;
        $this->showjson($nlcore->msg->m(1010000,true,$nlcore->db->sqltest()));
    }

    /**
     * @description: 插入新数据
     * POST @param String t_name 名称
     * POST @param String t_value 值
     * POST @param String t_text 描述
     * POST @param String t_time 时间
     */
    function dbinsert() {
        print_r($_POST);
    }
}

$sqltestobj = new sqltest();
$sqltestobj->starttest();
?>