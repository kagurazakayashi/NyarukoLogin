<?php
class nyauser {
    /**
     * @description: 检查登录凭据是邮箱还是手机号
     * @param String loginstr 要检查的登录凭据字符串
     * @return Int 0:直接将错误返回给客户端 0:邮箱 1:手机号
     */
    function logintype($loginstr) {
        global $nlcore;
        if ($nlcore->safe->isPhoneNumCN($user)) {
            return 1;
        } else if ($nlcore->safe->isEmail($user)) {
            return 0;
        } else {
            die($nlcore->msg->m(1,2020206));
            return -1;
        }
    }
    /**
     * @description: 检查指定信息地址是否已经存在于数据库
     * @param Int logintype 要检查的凭据类型 0:邮箱 1:手机号
     * @param String loginstr 要检查的登录凭据字符串
     * @return Bool 是否已经存在。如果出现多个结果则直接将错误返回客户端
     */
    function isalreadyexists($logintype,$loginstr) {
        global $nlcore;
        $logintypearr = ["mail","tel"];
        $logintypestr = $logintypearr[$logintype];
        $whereDic = [$logintype => $loginstr];
        $result = $this->scount($nlcore->cfg->db->tables["users"],$whereDic);
        if ($result[0] >= 2000000) $nlcore->msg->http403(2040100);
        $datacount = $result[2][0][0];
        if ($datacount == 0) {
            return false;
        } else if ($datacount == 1) {
            // $nlcore->msg->http403(2040102);
            return true;
        } else {
            $nlcore->msg->http403(2040101);
        }
    }
    /**
     * @description: 检查该用户是否已存在
     * @param String mergename 昵称#四位代码
     * 或使用：
     * @param String name 昵称
     * @param String nameid 四位代码
     * @return Bool 是否有此用户
     */
    function useralreadyexists($mergename=null,$name=null,$nameid=null) {
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
        } else if ($name && $nameid) {
            $whereDic = [
                "name" => $nickname,
                "nameid" => $nameid
            ];
            $result = $this->scount($nlcore->cfg->db->tables["users"],$whereDic);
            if ($result[0] >= 2000000) $nlcore->msg->http403(2040200);
            $datacount = $result[2][0][0];
            if ($datacount > 0) return true;
        }
        return false;
    }
}
?>