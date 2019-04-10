SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `nyarukologin`
--

-- --------------------------------------------------------

--
-- 表的结构 `u1_app`
--

CREATE TABLE `u1_app` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `app_id` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'APP唯一名称',
  `app_secret` varchar(32) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'APP密钥',
  `app_allback_secretc` varchar(32) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'APP回调密钥'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='外部程序表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_business`
--

CREATE TABLE `u1_business` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `business_name` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '业务唯一名称',
  `business_business` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '业务描述',
  `level_list` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '等级列表',
  `level_list_number` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '等级对应的分数列表'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='业务表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_group`
--

CREATE TABLE `u1_group` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `user_group_name` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户组唯一名称',
  `user_group_describe` text COLLATE utf8mb4_unicode_520_ci COMMENT '用户组描述',
  `jurisdiction` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '该用户组所具有的权限'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户组表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_history`
--

CREATE TABLE `u1_history` (
  `id` bigint(20) NOT NULL COMMENT '序号',
  `userhash` char(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '用户哈希',
  `apptoken` char(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'APP密钥',
  `session` char(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '会话代码',
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发生时间',
  `operation` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '执行操作',
  `sender` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '发送者',
  `receiver` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '接收者',
  `process` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '过程',
  `result` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '结果',
  `auditadmin` char(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '审核员哈希',
  `audittime` datetime DEFAULT NULL COMMENT '审核时间',
  `auditresult` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '审核意见'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- 表的结构 `u1_info`
--

CREATE TABLE `u1_info` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `userhash` char(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '资料类型',
  `name` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '昵称',
  `nameid` tinyint(4) UNSIGNED ZEROFILL NOT NULL COMMENT '昵称唯一码',
  `genders` tinyint(2) NOT NULL DEFAULT '0' COMMENT '性别',
  `address` text COLLATE utf8mb4_unicode_520_ci COMMENT '地址',
  `profile` text COLLATE utf8mb4_unicode_520_ci COMMENT '签名',
  `description` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '个人介绍',
  `image_file` text COLLATE utf8mb4_unicode_520_ci COMMENT '头像文件路径',
  `background_file` text COLLATE utf8mb4_unicode_520_ci COMMENT '横幅图片文件路径'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户信息表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_integral`
--

CREATE TABLE `u1_integral` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `hash` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `business_name` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '业务唯一名称',
  `integral_number` bigint(20) NOT NULL DEFAULT '0' COMMENT '积分数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='积分表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_ip`
--

CREATE TABLE `u1_ip` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `ip_addresscol_category` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'IP地址类别（IPv4,IPv6,其它）',
  `ip_address` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'IP地址',
  `proxy_ip_address` text COLLATE utf8mb4_unicode_520_ci COMMENT '代理IP地址',
  `position` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '归属地（可选）',
  `closing_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '到此时间前封禁'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='IP地址表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_jurisdiction`
--

CREATE TABLE `u1_jurisdiction` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `jurisdiction_name` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '权限唯一名称',
  `jurisdiction_business` text COLLATE utf8mb4_unicode_520_ci COMMENT '权限描述',
  `including_othe_jurisdiction` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '包含其他的权限'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='权限表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_protection`
--

CREATE TABLE `u1_protection` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `userhash` char(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `realname` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '真实姓名',
  `idtype` tinyint(3) UNSIGNED DEFAULT '0' COMMENT '身份证明类别',
  `idok` datetime DEFAULT NULL COMMENT '实名认证通过日期',
  `idnumber` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '身份证号',
  `mailvcode` char(32) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '邮箱验证码',
  `mailvcodeend` datetime DEFAULT NULL COMMENT '邮箱验证码审核有效期',
  `mailvcodeok` datetime DEFAULT NULL COMMENT '邮箱验证成功日期',
  `smsvcode` int(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT '手机验证码',
  `smsvcodeend` datetime DEFAULT NULL COMMENT '手机验证码审核有效期',
  `smsvcodeok` datetime DEFAULT NULL COMMENT '手机验证成功日期',
  `question1` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护问题1',
  `question2` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护问题2',
  `question3` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护问题3',
  `answer1` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护答案1',
  `answer2` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护答案2',
  `answer3` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护答案3',
  `totp` char(32) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'TOTP验证码',
  `recovery1` char(25) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '恢复代码1',
  `recovery2` char(25) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '恢复代码2',
  `recovery3` char(25) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '恢复代码3',
  `recovery4` char(25) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '恢复代码4',
  `recovery5` char(25) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '恢复代码5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='密保表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_session`
--

CREATE TABLE `u1_session` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `session_token` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '会话令牌',
  `token_purpose_code` tinyint(2) NOT NULL COMMENT '令牌用途代码（临时令牌/正式令牌等）',
  `session_token_create_time` datetime NOT NULL COMMENT '会话令牌创建时间',
  `session_token_term_of_validity` datetime NOT NULL COMMENT '会话令牌有效期至',
  `session_token_check_times` int(11) DEFAULT NULL COMMENT '令牌校验次数',
  `graphic_captcha` varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '图形验证码（可选）',
  `graphic_captcha_check_times` datetime DEFAULT NULL COMMENT '图形验证码有效期',
  `notes_app_name` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '备注：应用名称',
  `notes_device_name` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '备注：设备名称',
  `notes_ip_address` int(11) DEFAULT NULL COMMENT '备注：IP地址',
  `notes_ua` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '备注：UA（可选）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='会话令牌表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_totp`
--

CREATE TABLE `u1_totp` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `secret` char(16) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '钥匙内容',
  `apptoken` char(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '钥匙访问代码',
  `ipid` int(11) NOT NULL COMMENT 'IP地址ID',
  `appid` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '已注册应用ID',
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '生成时间',
  `c_code` char(6) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '验证码',
  `c_time` datetime DEFAULT NULL COMMENT '验证码生成时间',
  `c_img` text COLLATE utf8mb4_unicode_520_ci COMMENT '验证码网址'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- 表的结构 `u1_usergroup`
--

CREATE TABLE `u1_usergroup` (
  `id` bigint(20) NOT NULL,
  `userhash` char(64) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `groupid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户组成员表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_users`
--

CREATE TABLE `u1_users` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'ID',
  `hash` char(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `mail` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '邮箱',
  `tel` bigint(11) UNSIGNED DEFAULT NULL COMMENT '电话号码',
  `pwd` char(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '密码',
  `pwdend` datetime NOT NULL COMMENT '密码有效期至',
  `2fa` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '两步验证信息',
  `fail` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录失败次数',
  `regtime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '账户注册时间',
  `enabletime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '账户到此时间前封禁',
  `errorcode` mediumint(7) NOT NULL DEFAULT '0' COMMENT '账户异常状态提示信息ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户表';

--
-- 转储表的索引
--

--
-- 表的索引 `u1_app`
--
ALTER TABLE `u1_app`
  ADD PRIMARY KEY (`id`,`app_id`);

--
-- 表的索引 `u1_business`
--
ALTER TABLE `u1_business`
  ADD PRIMARY KEY (`id`,`business_name`);

--
-- 表的索引 `u1_group`
--
ALTER TABLE `u1_group`
  ADD PRIMARY KEY (`id`,`user_group_name`);

--
-- 表的索引 `u1_history`
--
ALTER TABLE `u1_history`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_info`
--
ALTER TABLE `u1_info`
  ADD PRIMARY KEY (`id`,`userhash`);

--
-- 表的索引 `u1_integral`
--
ALTER TABLE `u1_integral`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_ip`
--
ALTER TABLE `u1_ip`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_jurisdiction`
--
ALTER TABLE `u1_jurisdiction`
  ADD PRIMARY KEY (`id`,`jurisdiction_name`);

--
-- 表的索引 `u1_protection`
--
ALTER TABLE `u1_protection`
  ADD PRIMARY KEY (`id`,`userhash`);

--
-- 表的索引 `u1_session`
--
ALTER TABLE `u1_session`
  ADD PRIMARY KEY (`id`,`session_token`);

--
-- 表的索引 `u1_totp`
--
ALTER TABLE `u1_totp`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_usergroup`
--
ALTER TABLE `u1_usergroup`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_users`
--
ALTER TABLE `u1_users`
  ADD PRIMARY KEY (`id`,`hash`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `u1_app`
--
ALTER TABLE `u1_app`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_business`
--
ALTER TABLE `u1_business`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_info`
--
ALTER TABLE `u1_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_integral`
--
ALTER TABLE `u1_integral`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_ip`
--
ALTER TABLE `u1_ip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_jurisdiction`
--
ALTER TABLE `u1_jurisdiction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_protection`
--
ALTER TABLE `u1_protection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_session`
--
ALTER TABLE `u1_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_totp`
--
ALTER TABLE `u1_totp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_usergroup`
--
ALTER TABLE `u1_usergroup`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `u1_users`
--
ALTER TABLE `u1_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
