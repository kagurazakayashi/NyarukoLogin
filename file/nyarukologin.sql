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
-- 表的结构 `stopword`
--

CREATE TABLE `stopword` (
  `id` int(11) NOT NULL COMMENT '序号',
  `word` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '敏感词',
  `time` date NOT NULL COMMENT '添加日期',
  `enable` tinyint(1) NOT NULL COMMENT '是否启用'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='敏感词表';

-- --------------------------------------------------------

--
-- 表的结构 `test`
--

CREATE TABLE `test` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `t_name` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '名称',
  `t_value` int(11) DEFAULT '0' COMMENT '值',
  `t_text` text COLLATE utf8mb4_unicode_520_ci COMMENT '描述',
  `t_time` datetime DEFAULT NULL COMMENT '时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='测试';

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
-- 表的结构 `u1_change`
--

CREATE TABLE `u1_change` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `hash` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `modification_date` datetime NOT NULL COMMENT '修改日期',
  `modified_information_table` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户修改了哪条信息（表名）',
  `modified_information_column` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户修改了哪条信息（列名）',
  `pre_revision_content` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '修改前的内容',
  `assessor_hash` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '审核者哈希',
  `assessor_date` datetime NOT NULL COMMENT '审核通过日期'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='信息和积分变更日志';

-- --------------------------------------------------------

--
-- 表的结构 `u1_external_app`
--

CREATE TABLE `u1_external_app` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `app_id` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'APP唯一名称',
  `app_secret` varchar(32) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'APP密钥',
  `app_allback_secretc` varchar(32) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'APP回调密钥'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='外部程序表';

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
-- 表的结构 `u1_ip_address`
--

CREATE TABLE `u1_ip_address` (
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
-- 表的结构 `u1_password_protection`
--

CREATE TABLE `u1_password_protection` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `hash` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `mail` text COLLATE utf8mb4_unicode_520_ci COMMENT '邮箱',
  `mail_verification_code` varchar(32) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '邮箱验证码',
  `mail_verification_code_term_of_validity` datetime DEFAULT NULL COMMENT '邮箱验证码审核有效期',
  `mail_verification_date_of_success` datetime DEFAULT NULL COMMENT '邮箱验证成功日期',
  `phone_number` int(11) DEFAULT NULL COMMENT '手机号码',
  `phone_numbe_verification_code` int(6) DEFAULT NULL COMMENT '手机验证码',
  `phone_numbe_verification_code_term_of_validity` datetime DEFAULT NULL COMMENT '手机验证码审核有效期',
  `phone_number_verification_date_of_success` datetime DEFAULT NULL COMMENT '手机验证成功日期',
  `password` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '密码',
  `password_protectioncol_problem` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护问题',
  `password_protectioncol_answer` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护回答',
  `dynamic_password_token` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '动态密码Token',
  `dynamic_password_verification_code_term_of_validity` datetime DEFAULT NULL COMMENT '动态密码审核有效期',
  `dynamic_password_verification_date_of_success` datetime DEFAULT NULL COMMENT '动态密码成功日期',
  `recovery_code1` varchar(32) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `recovery_code2` varchar(32) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `recovery_code3` varchar(32) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='密保表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_session_token`
--

CREATE TABLE `u1_session_token` (
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
-- 表的结构 `u1_session_totp`
--

CREATE TABLE `u1_session_totp` (
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
-- 表的结构 `u1_users`
--

CREATE TABLE `u1_users` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'ID',
  `hash` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `name` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户名',
  `nameid` tinyint(4) UNSIGNED ZEROFILL NOT NULL COMMENT '昵称唯一码',
  `mail` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '邮箱',
  `tel` bigint(11) UNSIGNED DEFAULT NULL COMMENT '电话号码',
  `password_expiration` datetime NOT NULL COMMENT '密码有效期至',
  `2fa` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '两步验证信息',
  `login_failed_number` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录失败次数',
  `registration_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '账户注册时间',
  `closing_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '账户到此时间前封禁',
  `account_error_code` mediumint(7) NOT NULL DEFAULT '0' COMMENT '账户异常状态提示信息ID',
  `user_group` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户组'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_users_information`
--

CREATE TABLE `u1_users_information` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `hash` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `nickname` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '昵称',
  `genders` tinyint(2) NOT NULL DEFAULT '0' COMMENT '性别',
  `address` text COLLATE utf8mb4_unicode_520_ci COMMENT '地址',
  `brief_introduction` text COLLATE utf8mb4_unicode_520_ci COMMENT '简介',
  `introduction` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '介绍',
  `image_file` text COLLATE utf8mb4_unicode_520_ci COMMENT '头像文件路径',
  `background_file` text COLLATE utf8mb4_unicode_520_ci COMMENT '横幅图片文件路径'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户信息表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_user_group`
--

CREATE TABLE `u1_user_group` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `user_group_name` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户组唯一名称',
  `user_group_describe` text COLLATE utf8mb4_unicode_520_ci COMMENT '用户组描述',
  `jurisdiction` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '该用户组所具有的权限'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户组表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_verification_sending_log`
--

CREATE TABLE `u1_verification_sending_log` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `hash` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `verification_category` tinyint(2) DEFAULT NULL COMMENT '发送信息类别',
  `verification_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发送时间',
  `recipient` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '收件人',
  `verification_message` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '发送内容',
  `api_return_result` text COLLATE utf8mb4_unicode_520_ci COMMENT 'API接口返回结果'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='验证信息发送日志';

--
-- 转储表的索引
--

--
-- 表的索引 `stopword`
--
ALTER TABLE `stopword`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `test`
--
ALTER TABLE `test`
  ADD PRIMARY KEY (`id`,`t_name`);

--
-- 表的索引 `u1_business`
--
ALTER TABLE `u1_business`
  ADD PRIMARY KEY (`id`,`business_name`);

--
-- 表的索引 `u1_change`
--
ALTER TABLE `u1_change`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_external_app`
--
ALTER TABLE `u1_external_app`
  ADD PRIMARY KEY (`id`,`app_id`);

--
-- 表的索引 `u1_integral`
--
ALTER TABLE `u1_integral`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_ip_address`
--
ALTER TABLE `u1_ip_address`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_jurisdiction`
--
ALTER TABLE `u1_jurisdiction`
  ADD PRIMARY KEY (`id`,`jurisdiction_name`);

--
-- 表的索引 `u1_password_protection`
--
ALTER TABLE `u1_password_protection`
  ADD PRIMARY KEY (`id`,`hash`);

--
-- 表的索引 `u1_session_token`
--
ALTER TABLE `u1_session_token`
  ADD PRIMARY KEY (`id`,`session_token`);

--
-- 表的索引 `u1_session_totp`
--
ALTER TABLE `u1_session_totp`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_users`
--
ALTER TABLE `u1_users`
  ADD PRIMARY KEY (`id`,`hash`);

--
-- 表的索引 `u1_users_information`
--
ALTER TABLE `u1_users_information`
  ADD PRIMARY KEY (`id`,`hash`);

--
-- 表的索引 `u1_user_group`
--
ALTER TABLE `u1_user_group`
  ADD PRIMARY KEY (`id`,`user_group_name`);

--
-- 表的索引 `u1_verification_sending_log`
--
ALTER TABLE `u1_verification_sending_log`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `test`
--
ALTER TABLE `test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_business`
--
ALTER TABLE `u1_business`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_change`
--
ALTER TABLE `u1_change`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_external_app`
--
ALTER TABLE `u1_external_app`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_integral`
--
ALTER TABLE `u1_integral`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_ip_address`
--
ALTER TABLE `u1_ip_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_jurisdiction`
--
ALTER TABLE `u1_jurisdiction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_password_protection`
--
ALTER TABLE `u1_password_protection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_session_token`
--
ALTER TABLE `u1_session_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_session_totp`
--
ALTER TABLE `u1_session_totp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_users`
--
ALTER TABLE `u1_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_users_information`
--
ALTER TABLE `u1_users_information`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_verification_sending_log`
--
ALTER TABLE `u1_verification_sending_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
