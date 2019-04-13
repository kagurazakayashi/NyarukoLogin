<?php
class nyauser {
    /**
     * @description: 检查登录凭据是邮箱还是手机号
     * @param String loginstr 要检查的登录凭据字符串
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @return Int 0:直接将错误返回给客户端 0:邮箱 1:手机号
     */
    function logintype($loginstr,$totpsecret=null) {
        global $nlcore;
        if ($nlcore->safe->isPhoneNumCN($loginstr)) {
            return 1;
        } else if ($nlcore->safe->isEmail($loginstr)) {
            return 0;
        } else {
            $nlcore->msg->stopmsg(2020206,$totpsecret);
            return -1;
        }
    }
    /**
     * @description: 检查指定信息地址是否已经存在于数据库
     * @param Int logintype 要检查的凭据类型 0:邮箱 1:手机号 2:哈希
     * @param String loginstr 要检查的登录凭据字符串
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @return Bool 是否已经存在。如果出现多个结果则直接将错误返回客户端
     */
    function isalreadyexists($logintype,$loginstr,$totpsecret=null) {
        global $nlcore;
        $logintypearr = ["mail","tel","hash"];
        $logintypestr = $logintypearr[$logintype];
        $whereDic = [$logintypearr[$logintype] => $loginstr];
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["users"],$whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040100,$totpsecret);
        $datacount = $result[2][0][0];
        if ($datacount == 0) {
            return false;
        } else if ($datacount == 1) {
            // $nlcore->msg->stopmsg(2040102,$totpsecret);
            return true;
        } else {
            $nlcore->msg->stopmsg(2040101,$totpsecret);
        }
    }
    /**
     * @description: 检查该用户是否已存在
     * @param String mergename 昵称#四位代码
     * 或使用：
     * @param String name 昵称
     * @param String nameid 四位代码
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @return Bool 是否有此用户
     */
    function useralreadyexists($mergename=null,$name=null,$nameid=null,$totpsecret=null) {
        global $nlcore;
        if ($mergename) {
            $namearr = explode("#", $mergename);
            $nameid = end($namearr);
            if (count($namearr) > 2) {
                array_pop($namearr);
                $name = implode("#", $namearr);
            } else {
                $name = $namearr[0];
            }
        }
        $whereDic = [
            "name" => $name,
            "nameid" => $nameid
        ];
        $result = $nlcore->db->scount($nlcore->cfg->db->tables["info"],$whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->stopmsg(2040200,$totpsecret);
        $datacount = $result[2][0][0];
        if ($datacount > 0) return true;
        return false;
    }
}
?>
