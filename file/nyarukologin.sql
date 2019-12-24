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
  `name` char(64) COLLATE ascii_bin NOT NULL COMMENT 'APP唯一名称',
  `secret` char(64) COLLATE ascii_bin NOT NULL COMMENT 'APP密钥',
  `callback` char(64) COLLATE ascii_bin DEFAULT NULL COMMENT 'APP回调密钥'
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='外部程序表';

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
-- 表的结构 `u1_device`
--

CREATE TABLE `u1_device` (
  `id` int(11) NOT NULL COMMENT '序号',
  `type` enum('phone','phone_emu','pad','pad_emu','pc','web','debug','other') CHARACTER SET ascii NOT NULL DEFAULT 'phone' COMMENT '设备类型',
  `os` enum('ios','android','windows','linux','harmony','emu','other') CHARACTER SET ascii NOT NULL DEFAULT 'other' COMMENT '操作系统',
  `device` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '硬件名称',
  `osver` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '系统版本',
  `info` text COLLATE utf8mb4_unicode_520_ci COMMENT '其他设备信息'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='设备型号表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_group`
--

CREATE TABLE `u1_group` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `name` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '用户组唯一名称',
  `describe` text COLLATE utf8mb4_unicode_520_ci COMMENT '用户组描述',
  `jurisdiction` text CHARACTER SET ascii COLLATE ascii_bin COMMENT '该用户组所具有的权限'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户组表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_history`
--

CREATE TABLE `u1_history` (
  `id` bigint(20) NOT NULL COMMENT '序号',
  `userhash` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '用户哈希',
  `apptoken` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT 'APP密钥',
  `session` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '会话代码',
  `ipid` int(11) NOT NULL COMMENT 'IP表id',
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发生时间',
  `operation` enum('USER_SIGN_UP','USER_SIGN_IN','') CHARACTER SET ascii DEFAULT NULL COMMENT '执行操作',
  `sender` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '发送者',
  `receiver` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '接收者',
  `process` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '过程',
  `result` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '结果',
  `auditadmin` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '审核员哈希',
  `audittime` datetime DEFAULT NULL COMMENT '审核时间',
  `auditresult` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '审核意见'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- 表的结构 `u1_info`
--

CREATE TABLE `u1_info` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `userhash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '哈希',
  `infotype` enum('main','additional') CHARACTER SET ascii NOT NULL DEFAULT 'main' COMMENT '资料类型',
  `name` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '昵称',
  `nameid` smallint(4) UNSIGNED ZEROFILL NOT NULL COMMENT '昵称唯一码',
  `gender` enum('male','female','other','androgynos','androgyne','bigender','boi','cisgender','cross_dresser','gender_bender','gender_neutrality','non_binary','postgenderism','gender_variance','pangender','transgender','trans_man','trans_woman','transfeminine','transsexual','trigender','') CHARACTER SET ascii DEFAULT NULL,
  `pronoun` enum('she','he','it') CHARACTER SET ascii DEFAULT 'it' COMMENT '人称代词',
  `address` text COLLATE utf8mb4_unicode_520_ci COMMENT '地址',
  `profile` text COLLATE utf8mb4_unicode_520_ci COMMENT '签名',
  `description` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '个人介绍',
  `image` text COLLATE utf8mb4_unicode_520_ci COMMENT '头像文件路径',
  `background` text COLLATE utf8mb4_unicode_520_ci COMMENT '横幅图片文件路径'
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
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `type` enum('other','other_local','ipv4','ipv4_local','ipv6','ipv6_local') CHARACTER SET ascii DEFAULT NULL COMMENT 'IP地址类别',
  `ip` tinytext CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'IP地址',
  `proxy` tinytext CHARACTER SET ascii COLLATE ascii_bin COMMENT '代理IP地址',
  `position` longtext COLLATE utf8mb4_unicode_520_ci COMMENT '归属地',
  `enabletime` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '到此时间前封禁'
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
  `userhash` char(64) CHARACTER SET armscii8 COLLATE armscii8_bin NOT NULL COMMENT '哈希',
  `realname` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '真实姓名',
  `idtype` tinyint(3) UNSIGNED DEFAULT '0' COMMENT '身份证明类别',
  `idok` datetime DEFAULT NULL COMMENT '实名认证通过日期',
  `idnumber` varchar(18) CHARACTER SET ascii DEFAULT NULL COMMENT '身份证号',
  `mailvcode` char(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '邮箱验证码',
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
  `totp` char(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT 'TOTP验证码',
  `recovery1` char(25) CHARACTER SET ascii DEFAULT NULL COMMENT '恢复代码1',
  `recovery2` char(25) CHARACTER SET ascii DEFAULT NULL COMMENT '恢复代码2',
  `recovery3` char(25) CHARACTER SET ascii DEFAULT NULL COMMENT '恢复代码3',
  `recovery4` char(25) CHARACTER SET ascii DEFAULT NULL COMMENT '恢复代码4',
  `recovery5` char(25) CHARACTER SET ascii DEFAULT NULL COMMENT '恢复代码5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='密保表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_session`
--

CREATE TABLE `u1_session` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `token` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '会话令牌',
  `apptoken` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'APP钥匙访问代码',
  `userhash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '用户哈希',
  `ipid` int(11) UNSIGNED NOT NULL COMMENT 'IP地址ID',
  `devid` int(11) DEFAULT NULL COMMENT '设备表ID',
  `devtype` enum('phone','phone_emu','pad','pad_emu','pc','web','debug','other') CHARACTER SET ascii NOT NULL DEFAULT 'phone' COMMENT '设备类型',
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '令牌生成时间',
  `endtime` datetime DEFAULT NULL COMMENT '令牌失效时间',
  `ua` text COLLATE utf8mb4_unicode_520_ci COMMENT '会话环境信息'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='会话令牌表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_totp`
--

CREATE TABLE `u1_totp` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `secret` char(64) COLLATE ascii_bin NOT NULL COMMENT '钥匙内容',
  `apptoken` char(64) COLLATE ascii_bin NOT NULL COMMENT '钥匙访问代码',
  `ipid` int(11) NOT NULL COMMENT 'IP地址ID',
  `appid` int(11) UNSIGNED NOT NULL COMMENT '已注册应用ID',
  `devid` int(10) UNSIGNED DEFAULT NULL COMMENT '设备表ID',
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '生成时间',
  `c_code` char(6) CHARACTER SET armscii8 DEFAULT NULL COMMENT '验证码',
  `c_time` datetime DEFAULT NULL COMMENT '验证码生成时间',
  `c_img` text CHARACTER SET armscii8 COLLATE armscii8_bin COMMENT '验证码网址'
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- --------------------------------------------------------

--
-- 表的结构 `u1_usergroup`
--

CREATE TABLE `u1_usergroup` (
  `id` bigint(20) NOT NULL,
  `userhash` char(64) COLLATE ascii_bin NOT NULL,
  `groupid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='用户组成员表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_users`
--

CREATE TABLE `u1_users` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'ID',
  `hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '哈希',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `mail` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '邮箱',
  `telarea` smallint(4) UNSIGNED DEFAULT NULL COMMENT '电话国别码',
  `tel` bigint(11) UNSIGNED DEFAULT NULL COMMENT '电话号码',
  `pwd` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '密码',
  `pwdend` datetime NOT NULL COMMENT '密码有效期至',
  `2fa` tinytext CHARACTER SET ascii COLLATE ascii_bin COMMENT '两步验证信息',
  `fail` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录失败次数',
  `regtime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '账户注册时间',
  `enabletime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '账户到此时间前封禁',
  `errorcode` mediumint(7) NOT NULL DEFAULT '0' COMMENT '账户异常状态提示信息ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户表';

-- --------------------------------------------------------

--
-- 表的结构 `z1_keyword`
--

CREATE TABLE `z1_keyword` (
  `id` bigint(20) NOT NULL COMMENT '序号',
  `hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '关键词哈希',
  `word` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '关键词描述',
  `topost` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '目标贴文哈希',
  `isai` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为后台词汇'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- 表的结构 `z1_posts`
--

CREATE TABLE `z1_posts` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '序号',
  `post` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '文章哈希值',
  `parent` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '评论自',
  `user` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '用户哈希值',
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发表日期',
  `modified` datetime DEFAULT NULL COMMENT '修改日期',
  `title` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '文章标题',
  `type` enum('POST','TEXT','IMAGE','VIDEO','VOTE','FORWARD') CHARACTER SET ascii NOT NULL COMMENT '贴文类型',
  `content` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '正文',
  `files` text COLLATE utf8mb4_unicode_520_ci COMMENT '附件路径',
  `share` enum('PUBLIC','CIRCLE','GROUP','USER','PRIVACY') CHARACTER SET ascii NOT NULL DEFAULT 'PUBLIC' COMMENT '分享范围',
  `mention` text COLLATE utf8mb4_unicode_520_ci COMMENT '提及的人员哈希',
  `cancomment` tinyint(1) NOT NULL DEFAULT '1' COMMENT '允许评论',
  `canforward` tinyint(1) NOT NULL DEFAULT '1' COMMENT '允许转发'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `u1_app`
--
ALTER TABLE `u1_app`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `name` (`name`);

--
-- 表的索引 `u1_business`
--
ALTER TABLE `u1_business`
  ADD PRIMARY KEY (`id`,`business_name`);

--
-- 表的索引 `u1_device`
--
ALTER TABLE `u1_device`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_group`
--
ALTER TABLE `u1_group`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `name` (`name`);

--
-- 表的索引 `u1_history`
--
ALTER TABLE `u1_history`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_info`
--
ALTER TABLE `u1_info`
  ADD PRIMARY KEY (`id`) USING BTREE;

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
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `userhash` (`userhash`),
  ADD UNIQUE KEY `idnumber` (`idnumber`),
  ADD UNIQUE KEY `recovery1` (`recovery1`,`recovery2`,`recovery3`,`recovery4`,`recovery5`);

--
-- 表的索引 `u1_session`
--
ALTER TABLE `u1_session`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `token` (`token`);

--
-- 表的索引 `u1_totp`
--
ALTER TABLE `u1_totp`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `secret` (`secret`),
  ADD UNIQUE KEY `apptoken` (`apptoken`);

--
-- 表的索引 `u1_usergroup`
--
ALTER TABLE `u1_usergroup`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `u1_users`
--
ALTER TABLE `u1_users`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `hash` (`hash`),
  ADD UNIQUE KEY `tel` (`tel`),
  ADD UNIQUE KEY `mail` (`mail`);

--
-- 表的索引 `z1_posts`
--
ALTER TABLE `z1_posts`
  ADD PRIMARY KEY (`id`);

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
-- 使用表AUTO_INCREMENT `u1_device`
--
ALTER TABLE `u1_device`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '序号';

--
-- 使用表AUTO_INCREMENT `u1_history`
--
ALTER TABLE `u1_history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '序号';

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
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

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
