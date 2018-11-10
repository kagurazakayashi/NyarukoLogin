-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2019-04-02 18:32:40
-- 服务器版本： 5.7.23-log
-- PHP 版本： 7.3.1

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
-- 表的结构 `business`
--

CREATE TABLE `business` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `business_name` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '业务唯一名称',
  `business_business` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '业务描述',
  `level_list` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '等级列表',
  `level_list_number` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '等级对应的分数列表'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='业务表';

-- --------------------------------------------------------

--
-- 表的结构 `change`
--

CREATE TABLE `change` (
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
-- 表的结构 `external_app`
--

CREATE TABLE `external_app` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `app_id` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'APP唯一名称',
  `app_secret` varchar(32) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'APP密钥',
  `app_allback_secretc` varchar(32) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'APP回调密钥'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='外部程序表';

-- --------------------------------------------------------

--
-- 表的结构 `integral`
--

CREATE TABLE `integral` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `hash` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `business_name` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '业务唯一名称',
  `integral_number` bigint(20) NOT NULL DEFAULT '0' COMMENT '积分数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='积分表';

-- --------------------------------------------------------

--
-- 表的结构 `ip_address`
--

CREATE TABLE `ip_address` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `ip_addresscol_category` tinyint(2) NOT NULL COMMENT 'IP地址类别（IPv4,IPv6,其它）',
  `ip_address` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'IP地址',
  `proxy_ip_address` text COLLATE utf8mb4_unicode_520_ci COMMENT '代理IP地址',
  `position` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '归属地（可选）',
  `closing_time` datetime DEFAULT NULL COMMENT '到此时间前封禁'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='IP地址表';

-- --------------------------------------------------------

--
-- 表的结构 `jurisdiction`
--

CREATE TABLE `jurisdiction` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `jurisdiction_name` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '权限唯一名称',
  `jurisdiction_business` text COLLATE utf8mb4_unicode_520_ci COMMENT '权限描述',
  `including_othe_jurisdiction` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '包含其他的权限'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='权限表';

-- --------------------------------------------------------

--
-- 表的结构 `password_protection`
--

CREATE TABLE `password_protection` (
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
-- 表的结构 `session_token`
--

CREATE TABLE `session_token` (
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
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `hash` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `name` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户名',
  `mail` text COLLATE utf8mb4_unicode_520_ci COMMENT '邮箱',
  `phone_number` int(11) DEFAULT NULL COMMENT '电话号码',
  `password_deadline` datetime NOT NULL COMMENT '密码有效期',
  `two_step_validation` varchar(32) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '两步验证信息',
  `login_failed_number` int(11) NOT NULL COMMENT '登录失败次数',
  `session_token` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '会话令牌',
  `registration_time` datetime NOT NULL COMMENT '账户注册时间',
  `closing_time` datetime DEFAULT NULL COMMENT '账户到此时间前封禁',
  `account_error_code` int(11) DEFAULT NULL COMMENT '账户异常状态提示信息ID',
  `user_group` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '用户组'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户表';

-- --------------------------------------------------------

--
-- 表的结构 `users_information`
--

CREATE TABLE `users_information` (
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
-- 表的结构 `user_group`
--

CREATE TABLE `user_group` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `user_group_name` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户组唯一名称',
  `user_group_describe` text COLLATE utf8mb4_unicode_520_ci COMMENT '用户组描述',
  `jurisdiction` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '该用户组所具有的权限'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户组表';

-- --------------------------------------------------------

--
-- 表的结构 `verification_sending_log`
--

CREATE TABLE `verification_sending_log` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `hash` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `verification_category` tinyint(2) DEFAULT NULL COMMENT '发送信息类别',
  `verification_time` datetime DEFAULT NULL COMMENT '发送时间',
  `recipient` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '收件人',
  `verification_message` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '发送内容',
  `api_return_result` text COLLATE utf8mb4_unicode_520_ci COMMENT 'API接口返回结果'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='验证信息发送日志';

--
-- 转储表的索引
--

--
-- 表的索引 `business`
--
ALTER TABLE `business`
  ADD PRIMARY KEY (`id`,`business_name`);

--
-- 表的索引 `change`
--
ALTER TABLE `change`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `external_app`
--
ALTER TABLE `external_app`
  ADD PRIMARY KEY (`id`,`app_id`);

--
-- 表的索引 `integral`
--
ALTER TABLE `integral`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ip_address`
--
ALTER TABLE `ip_address`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `jurisdiction`
--
ALTER TABLE `jurisdiction`
  ADD PRIMARY KEY (`id`,`jurisdiction_name`);

--
-- 表的索引 `password_protection`
--
ALTER TABLE `password_protection`
  ADD PRIMARY KEY (`id`,`hash`);

--
-- 表的索引 `session_token`
--
ALTER TABLE `session_token`
  ADD PRIMARY KEY (`id`,`session_token`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`,`hash`);

--
-- 表的索引 `users_information`
--
ALTER TABLE `users_information`
  ADD PRIMARY KEY (`id`,`hash`);

--
-- 表的索引 `user_group`
--
ALTER TABLE `user_group`
  ADD PRIMARY KEY (`id`,`user_group_name`);

--
-- 表的索引 `verification_sending_log`
--
ALTER TABLE `verification_sending_log`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `business`
--
ALTER TABLE `business`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `change`
--
ALTER TABLE `change`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `external_app`
--
ALTER TABLE `external_app`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `integral`
--
ALTER TABLE `integral`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `ip_address`
--
ALTER TABLE `ip_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `jurisdiction`
--
ALTER TABLE `jurisdiction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `password_protection`
--
ALTER TABLE `password_protection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `session_token`
--
ALTER TABLE `session_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `users_information`
--
ALTER TABLE `users_information`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `verification_sending_log`
--
ALTER TABLE `verification_sending_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
