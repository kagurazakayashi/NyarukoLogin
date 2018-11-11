<?php
class nyamodactivity {
    var $hash; //(text64) *用户哈希
    var $app; //(text32) *应用程序名称
    var $timeset; //(datetime) *令牌生成时间
    var $timeend; //(datetime) *令牌自动失效时间
    var $ip; //(text39) 绑定IP地址
    var $token; //(text64) *访问令牌 (用户哈希+当前时间的哈希)
    var $btoken; //(text64) *客户端令牌 (用户哈希+当前时间的哈希)
}