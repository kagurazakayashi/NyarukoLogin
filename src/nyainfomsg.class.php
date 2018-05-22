<?php
class nyainfomsg {
    var $imsg = array(
        -1 => '发生不明错误。',
        // 1xxx : 操作成功执行
        // 11xx : 数据库操作成功
        1100 => 'SQL语句成功执行。',
        // 2xxx : 操作执行失败
        // 20xx : 参数不足
        2000 => '没有参数。',
        2001 => '需要更多参数。',
        2002 => '参数无效。',
        // 21xx : 数据库操作失败
        2100 => '未能连接到数据库。',
        2101 => '数据库错误。',
        2102 => '数据库未能返回正确的数据。',
        // 22xx : 字符串检查异常
        2200 => '无效字符串。',
        2201 => '字符格式不正确。',
        2202 => 'SQL语句不正确。',
        2203 => '不应包含HTML代码。'
    );
    /**
     * @description: 创建异常信息提示JSON
     * @param Int code 错误代码
     * @param Bool showmsg 是否显示错误信息（否则直接无输出）
     * @param String str 附加错误信息
     * @return String 异常信息提示JSON
     */
    function m($code = -1,$showmsg = true,$str = "") {
        if (!$showmsg) return null;
        header('Content-type:text/json');
        return json_encode(array(
            "stat"=>$code,
            "msg"=>$this->imsg[2100],
            "info"=>$str
        ));
    }
}
?>