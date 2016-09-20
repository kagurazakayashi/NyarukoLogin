<?php
/**
* 修改安全信息
*/
if (class_exists('yaloginSafe') != true) {
    require 'yaloginSafe.php';
}
class yaloginPasswd {

    private $safe;

    function init() { //__constrct()
        $this->safe = new yaloginSafe();
    }

    /* 输入新密码
    $this->userobj->userpassword
    $this->userobj->userpassword2
    $this->userobj->userpasswordquestion1
    $this->userobj->userpasswordanswer1
    $this->userobj->userpasswordquestion2
    $this->userobj->userpasswordanswer2
    $this->userobj->userpasswordquestion3
    $this->userobj->userpasswordanswer3
    */

    function vaild($userobj) {

        $v = $userobj->userpassword;
        if($v == null || $v == "" || !is_string($v)) {
            return 10601;
        }
        if (!$this->safe->is_md5($v)) {
            return 10603;
        }
        $sqlcmd = "`userpassword`='".$v."'";

        $v = $userobj->userpassword2;
        if ($v != null && $v != "" && strlen($v) > 0) {
            if (!$this->safe->is_md5($v)) {
                return 10703;
            }
            $sqlcmd = ",`userpassword2`='".$v."'";
        }

        $v = $userobj->userpasswordquestion1;
        if ($v != null && $v != "" && strlen($v) > 0) {
            if ($this->safe->containsSpecialCharacters($v) != 0) {
                return 10801;
            }
            if (strlen($v) > 64) {
                return 10802;
            }
            $sqlcmd = ",`userpasswordquestion1`='".$v."'";
        }

        $v = $userobj->userpasswordanswer1;
        if ($v != null && $v != "" && strlen($v) > 0) {
            if ($this->safe->containsSpecialCharacters($v) != 0) {
                return 10803;
            }
            if (strlen($v) > 64) {
                return 10804;
            }
            $sqlcmd = ",`userpasswordanswer1`='".$v."'";
        }

        $v = $userobj->userpasswordquestion2;
        if ($v != null && $v != "" && strlen($v) > 0) {
            if ($this->safe->containsSpecialCharacters($v) != 0) {
                return 10805;
            }
            if (strlen($v) > 64) {
                return 10806;
            }
            $sqlcmd = ",`userpasswordquestion2`='".$v."'";
        }

        $v = $userobj->userpasswordanswer2;
        if ($v != null && $v != "" && strlen($v) > 0) {
            if ($this->safe->containsSpecialCharacters($v) != 0) {
                return 10807;
            }
            if (strlen($v) > 64) {
                return 10808;
            }
            $sqlcmd = ",`userpasswordanswer2`='".$v."'";
        }

        $v = $userobj->userpasswordquestion3;
        if ($v != null && $v != "" && strlen($v) > 0) {
            if ($this->safe->containsSpecialCharacters($v) != 0) {
                return 10809;
            }
            if (strlen($v) > 64) {
                return 10810;
            }
            $sqlcmd = ",`userpasswordquestion3`='".$v."'";
        }

        $v = $userobj->userpasswordanswer3;
        if ($v != null && $v != "" && strlen($v) > 0) {
            if ($this->safe->containsSpecialCharacters($v) != 0) {
                return 10811;
            }
            if (strlen($v) > 64) {
                return 10812;
            }
            $sqlcmd = ",`userpasswordanswer3`='".$v."'";
        }

        return $sqlcmd;
    }
}
?>