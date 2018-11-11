<?php
//用户数据模型
class nyauser {
    var $id; //(int32) *用户ID (主键,自增)
    var $hash; //(text64) *用户哈希 (日期时间32哈希+名称邮箱32哈希)
    var $mail; //(text32) *用户邮箱及验证信息 (可登录)
    var $phone; //(int15) 用户手机号码及验证信息
    var $mailv; //(text32) 邮箱验证码 (用户哈希+当前时间的哈希,空未验证,1已验证)
    var $phonev; //(text32) 手机验证码 (由第三方平台决定的哈希,空未验证,1已验证)
    var $pwd; //(text64) *密码哈希 (密码哈希前半部分哈希+密码哈希后半部分哈希)
    var $name; //(text32) *用户名 (可登录)
    var $nick; //(text32) 昵称
    var $ver; //(int1) *对应用户数据版本 (目前版本为2)
    var $twostep; //(text) 额外登录手续代号 (逗号分隔符,选项:QA,SPWD)
    var $loginfo; //(text) 最近登录信息 (逗号分隔符,TIME/IP/APP)
    var $reginfo; //(text) 注册信息 (TIME/IP/APP)
    var $ban; //(datetime) 封锁到时间 (超时和空可登录)
    var $alert; //(text) 重要警告文本 (非空则显示警告信息,可配合ban)
    var $jur; //(int3) *权限ID (kylogin_jur->id)
    var $fail; //(uint10) 连续操作失败计数
}
?>