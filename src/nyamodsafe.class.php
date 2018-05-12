<?php
class nyamodsafe {
    var $hash; //(text64) *用户哈希
    var $qa; //(text) 密码提示问题和答案 (JSON二维数组)
    var $spwd; //(text64) 二级密码哈希 (密码哈希前半部分哈希+密码哈希后半部分哈希)
}
?>