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
  `id` int NOT NULL COMMENT 'ID',
  `name` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'APP唯一名称',
  `secret` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'APP密钥',
  `callback` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT 'APP回调密钥'
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='应用令牌表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_business`
--

CREATE TABLE `u1_business` (
  `id` int NOT NULL COMMENT 'ID',
  `business_name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '业务唯一名称',
  `business_business` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '业务描述',
  `level_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '等级列表',
  `level_list_number` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '等级对应的分数列表'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='业务表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_device`
--

CREATE TABLE `u1_device` (
  `id` int NOT NULL COMMENT '序号',
  `type` enum('phone','phone_emu','pad','pad_emu','pc','web','debug','other') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'phone' COMMENT '设备类型',
  `os` enum('ios','android','windows','linux','harmony','emu','other') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'other' COMMENT '操作系统',
  `device` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '硬件名称',
  `osver` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '系统版本',
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '其他设备信息'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='设备型号表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_gender`
--

CREATE TABLE `u1_gender` (
  `id` tinyint NOT NULL COMMENT '序号',
  `gender` tinytext CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '名称',
  `localization` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '本地化名称',
  `description` tinytext COLLATE utf8mb4_unicode_520_ci COMMENT '介绍',
  `person` tinyint(1) NOT NULL DEFAULT '0' COMMENT '推荐人称代词(0它1他2她)',
  `list` tinyint(1) NOT NULL DEFAULT '0' COMMENT '列表组'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 转存表中的数据 `u1_gender`
--

INSERT INTO `u1_gender` (`id`, `gender`, `localization`, `description`, `person`, `list`) VALUES
(1, 'Male', '男', NULL, 1, 0),
(2, 'Female', '女', NULL, 2, 0),
(3, 'Other', '隐藏/其它', NULL, 0, 0),
(4, 'Cis Male', '顺性别男', '出生时生物性别是男性，自己也觉得自己是男性。', 1, 1),
(5, 'Cis Female', '顺性别女', '出生时生物性别是女性，自己也觉得自己是女性。', 2, 1),
(6, 'Trans Male', '跨性别男', '出生时是女性，但现在自我认同男性。', 1, 1),
(7, 'Trans Female', '跨性别女', '出生时是男性，但现在自我认同女性。', 2, 1),
(8, 'Pangender', '泛性别', '认为自己是各种性别特质的混合体，每样都有一点儿。', 0, 1),
(9, 'Agender', '无性别', '没有发育性别、或者没有感觉到自己有任何强烈性别归属的人。', 0, 1),
(10, 'Intersex', '间性别', '认为自己是介于男性和女性之间的性别，既不想要接近典型的男性，也不想要接近典型的女性。', 0, 1),
(11, 'Genderqueer', '酷儿性别', '非二元性别。', 0, 1),
(12, 'Androgyne', '双性别', '拥有混合特征或者两种特征都很强烈的人。', 0, 1),
(13, 'Gender Fluid', '流性人', '在不同时间经历性别认知改变的人。', 0, 1),
(14, 'Gender Questioning', '性别存疑', '对自己的性别归属不完全确定、还没有找到最适合自己的性别认同标签的人。', 0, 1),
(15, 'Other', '其它', NULL, 0, 1),
(16, 'Agender', '无性别', '没有发育性别、或者没有感觉到自己有任何强烈性别归属的人。他们不见得认为自己没有性别，但可能觉得性别不是自己的核心特质。', 0, 2),
(17, 'Androgyne', '两性人', '（名词）拥有混合特征或者两种特征都很强烈的人。更强调对内的自我认同。', 0, 2),
(18, 'Androgynous', '两性人', '（形容词）拥有混合特征或者两种特征都很强烈的人。更强调对外的表现。', 0, 2),
(19, 'Bigender', '双性人', '自我性别认定可以在两种之间切换的人。两种性别未必是男和女，可以是这里提到的许多种其它非传统性别。', 0, 2),
(20, 'Cisgender', '顺性人', '自我性别认定和出生时的生物性别相同的人。大部分人归于此类。', 0, 2),
(21, 'Cisgender Female', '顺性女', '出生时生物性别是女性，自己也觉得自己是女性。', 2, 2),
(22, 'Cisgender Woman', '顺性女', '出生时生物性别是女性，自己也觉得自己是女性。略微更强调性征。', 2, 2),
(23, 'Cisgender Male', '顺性男', '出生时生物性别是男性，自己也觉得自己是男性。', 1, 2),
(24, 'Cisgender Man', '顺性男', '出生时生物性别是男性，自己也觉得自己是男性。略微更强调性征。', 1, 2),
(25, 'Female to Male', '女变男', '出生时被归属为女性，但是已经完成或正在进行向男性自我认同的转变的人。', 1, 2),
(26, 'Gender Fluid', '流性人', '在不同时间经历性别认知改变的人。', 0, 2),
(27, 'Gender Nonconforming', '非常规性别', '拒绝接受传统性别二元区分的人。', 0, 2),
(28, 'Gender Questioning', '性别存疑', '对自己的性别归属不完全确定、还没有找到最适合自己的性别认同标签的人。', 0, 2),
(29, 'Gender Variant', '变体性别', '和非常规性别类似。', 0, 2),
(30, 'Genderqueer', '酷儿性别', '和非常规性别类似。', 0, 2),
(31, 'Intersex', '间性人', '由于染色体或发育异常而拥有男女双方性征的人。', 0, 2),
(32, 'Male to Female', '男变女', '出生时被归属为男性，但是已经完成或正在进行向女性自我认同的转变的人。', 2, 2),
(33, 'Neither', '男女皆非', '参见非常规性别，但并不强调拒绝含义。通常是那些知道自己不属于传统二元男女、但是不熟悉相关术语的人。', 0, 2),
(34, 'Neutrois', '无性别', '和无性别类似。', 0, 2),
(35, 'Non-binary', '非二元', '和非常规性别类似。', 0, 2),
(36, 'Pangender', '泛性别', '认为自己是各种性别特质的混合体，每样都有一点儿。', 0, 2),
(37, 'Trans', '跨性别', '和顺性别相对，自我性别认定和出生时生物性别不同。', 0, 2),
(38, 'Trans Female', '跨性女', '出生时是男性，但现在自我认同女性。', 2, 2),
(39, 'Trans Male', '跨性男', '出生时是女性，但现在自我认同男性。', 1, 2),
(40, 'Trans Man', '跨性男', '参见顺性的相关讨论。', 1, 2),
(41, 'Trans Person', '跨性人', '不愿明确指出自己从哪跨到哪的人。', 0, 2),
(42, 'Trans Woman', '跨性女', '参见顺性的相关讨论。', 2, 2),
(43, 'Trans*', '广义跨性别', '', 0, 2),
(44, 'Trans* Female', '广义跨性女', '', 2, 2),
(45, 'Trans* Male', '广义跨性男', '', 1, 2),
(46, 'Trans* Man', '广义跨性男', '', 1, 2),
(47, 'Trans* Person', '广义跨性人', '', 0, 2),
(48, 'Trans* Woman', '广义跨性女', '', 2, 2),
(49, 'Transfeminine', '跨性女', '（形容词）参见Androgyne和Androgynous的区别。较之Transwoman，Transfeminine更强调对外的跨性表现。', 2, 2),
(50, 'Transgender', '跨性别', '和Trans基本意思相同。参见Cis和cisgender的相关讨论。下同。', 0, 2),
(51, 'Transgender Female', '跨性女', '', 2, 2),
(52, 'Transgender Male', '跨性男', '', 1, 2),
(53, 'Transgender Man', '跨性男', '', 1, 2),
(54, 'Transgender Person', '跨性人', '', 0, 2),
(55, 'Transgender Woman', '跨性女', '', 2, 2),
(56, 'Transmasculine', '跨性男', '（形容词）参见跨性女（形容词）。', 1, 2),
(57, 'Transsexual', '变性别', '不但自我认同性别与出生性别不同，还采取了医学措施、改变了自己的生理和解剖特征的人。', 0, 2),
(58, 'Transsexual Female', '变性女', '', 2, 2),
(59, 'Transsexual Male', '变性男', '', 1, 2),
(60, 'Transsexual Man', '变性男', '', 1, 2),
(61, 'Transsexual Person', '变性人', '', 0, 2),
(62, 'Transsexual Woman', '变性女', '', 2, 2),
(63, 'Two-spirit', '两魂人', '体内同时含有男人和女人灵魂的人。', 0, 2);

-- --------------------------------------------------------

--
-- 表的结构 `u1_group`
--

CREATE TABLE `u1_group` (
  `id` int NOT NULL COMMENT 'ID',
  `name` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '用户组唯一名称',
  `describe` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '用户组描述',
  `jurisdiction` text CHARACTER SET ascii COLLATE ascii_bin COMMENT '该用户组所具有的权限'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户组表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_history`
--

CREATE TABLE `u1_history` (
  `id` bigint NOT NULL COMMENT '序号',
  `userhash` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '用户哈希',
  `apptoken` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT 'APP密钥',
  `session` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '会话代码',
  `ipid` int NOT NULL COMMENT 'IP表id',
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发生时间',
  `operation` enum('USER_SIGN_UP','USER_SIGN_IN','USER_SUB_SIGN_UP') CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL COMMENT '执行操作',
  `sender` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '发送者',
  `receiver` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '接收者',
  `process` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '过程',
  `result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '结果',
  `auditadmin` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '审核员哈希',
  `audittime` datetime DEFAULT NULL COMMENT '审核时间',
  `auditresult` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '审核意见'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='操作记录表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_info`
--

CREATE TABLE `u1_info` (
  `id` int NOT NULL COMMENT 'ID',
  `userhash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '哈希',
  `belong` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '本账户是谁的子账户',
  `infotype` enum('main','additional') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'main' COMMENT '资料类型',
  `name` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '昵称',
  `nameid` smallint(4) UNSIGNED ZEROFILL NOT NULL COMMENT '昵称唯一码',
  `gender` tinyint NOT NULL DEFAULT '3' COMMENT '性别',
  `pronoun` tinyint(1) NOT NULL DEFAULT '0' COMMENT '人称代词(0它1他2她)',
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '地址',
  `profile` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '签名',
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '个人介绍',
  `image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '头像文件路径',
  `background` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '横幅图片文件路径'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户信息表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_integral`
--

CREATE TABLE `u1_integral` (
  `id` int NOT NULL COMMENT 'ID',
  `hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '哈希',
  `business_name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '业务唯一名称',
  `integral_number` bigint NOT NULL DEFAULT '0' COMMENT '积分数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='积分表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_ip`
--

CREATE TABLE `u1_ip` (
  `id` int UNSIGNED NOT NULL COMMENT 'ID',
  `type` enum('other','other_local','ipv4','ipv4_local','ipv6','ipv6_local') CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL COMMENT 'IP地址类别',
  `ip` tinytext CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'IP地址',
  `proxy` tinytext CHARACTER SET ascii COLLATE ascii_bin COMMENT '代理IP地址',
  `position` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '归属地',
  `enabletime` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '到此时间前封禁'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='IP地址表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_jurisdiction`
--

CREATE TABLE `u1_jurisdiction` (
  `id` int NOT NULL COMMENT 'ID',
  `jurisdiction_name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '权限唯一名称',
  `jurisdiction_business` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '权限描述',
  `including_othe_jurisdiction` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '包含其他的权限'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='权限表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_protection`
--

CREATE TABLE `u1_protection` (
  `id` int NOT NULL COMMENT 'ID',
  `userhash` char(64) CHARACTER SET armscii8 COLLATE armscii8_bin NOT NULL COMMENT '哈希',
  `realname` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '真实姓名',
  `idtype` tinyint UNSIGNED DEFAULT '0' COMMENT '身份证明类别',
  `idok` datetime DEFAULT NULL COMMENT '实名认证通过日期',
  `idnumber` varchar(18) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL COMMENT '身份证号',
  `mailvcode` char(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '邮箱验证码',
  `mailvcodeend` datetime DEFAULT NULL COMMENT '邮箱验证码审核有效期',
  `mailvcodeok` datetime DEFAULT NULL COMMENT '邮箱验证成功日期',
  `smsvcode` int(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT '手机验证码',
  `smsvcodeend` datetime DEFAULT NULL COMMENT '手机验证码审核有效期',
  `smsvcodeok` datetime DEFAULT NULL COMMENT '手机验证成功日期',
  `question1` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护问题1',
  `question2` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护问题2',
  `question3` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护问题3',
  `answer1` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护答案1',
  `answer2` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护答案2',
  `answer3` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '密码保护答案3',
  `totp` char(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT 'TOTP验证码',
  `recovery1` char(25) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL COMMENT '恢复代码1',
  `recovery2` char(25) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL COMMENT '恢复代码2',
  `recovery3` char(25) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL COMMENT '恢复代码3',
  `recovery4` char(25) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL COMMENT '恢复代码4',
  `recovery5` char(25) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL COMMENT '恢复代码5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='密保表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_session`
--

CREATE TABLE `u1_session` (
  `id` int NOT NULL COMMENT 'ID',
  `token` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '会话令牌',
  `apptoken` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'APP钥匙访问代码',
  `userhash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '用户哈希',
  `ipid` int UNSIGNED NOT NULL COMMENT 'IP地址ID',
  `devid` int DEFAULT NULL COMMENT '设备表ID',
  `devtype` enum('phone','phone_emu','pad','pad_emu','pc','web','debug','other') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'phone' COMMENT '设备类型',
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '令牌生成时间',
  `endtime` datetime DEFAULT NULL COMMENT '令牌失效时间',
  `ua` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '会话环境信息'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='会话令牌表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_totp`
--

CREATE TABLE `u1_totp` (
  `id` int NOT NULL COMMENT 'ID',
  `secret` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '钥匙内容',
  `apptoken` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '钥匙访问代码',
  `ipid` int NOT NULL COMMENT 'IP地址ID',
  `appid` int UNSIGNED NOT NULL COMMENT '已注册应用ID',
  `devid` int UNSIGNED DEFAULT NULL COMMENT '设备表ID',
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '生成时间',
  `c_code` char(6) CHARACTER SET armscii8 COLLATE armscii8_general_ci DEFAULT NULL COMMENT '验证码',
  `c_time` datetime DEFAULT NULL COMMENT '验证码生成时间',
  `c_img` text CHARACTER SET armscii8 COLLATE armscii8_bin COMMENT '验证码网址'
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='设备令牌表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_usergroup`
--

CREATE TABLE `u1_usergroup` (
  `id` bigint NOT NULL,
  `userhash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `groupid` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='用户组成员表';

-- --------------------------------------------------------

--
-- 表的结构 `u1_users`
--

CREATE TABLE `u1_users` (
  `id` bigint UNSIGNED NOT NULL COMMENT 'ID',
  `hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '用户哈希',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `mail` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '邮箱',
  `telarea` smallint UNSIGNED DEFAULT NULL COMMENT '电话国别码',
  `tel` bigint UNSIGNED DEFAULT NULL COMMENT '电话号码',
  `pwd` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '密码',
  `pwdend` datetime DEFAULT NULL COMMENT '密码有效期至',
  `2fa` tinytext CHARACTER SET ascii COLLATE ascii_bin COMMENT '两步验证信息',
  `fail` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录失败次数',
  `regtime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '账户注册时间',
  `enabletime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '账户到此时间前封禁',
  `errorcode` mediumint NOT NULL DEFAULT '0' COMMENT '账户异常状态提示信息ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='用户表';

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
-- 表的索引 `u1_gender`
--
ALTER TABLE `u1_gender`
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
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `u1_app`
--
ALTER TABLE `u1_app`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_business`
--
ALTER TABLE `u1_business`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_device`
--
ALTER TABLE `u1_device`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT '序号';

--
-- 使用表AUTO_INCREMENT `u1_gender`
--
ALTER TABLE `u1_gender`
  MODIFY `id` tinyint NOT NULL AUTO_INCREMENT COMMENT '序号', AUTO_INCREMENT=64;

--
-- 使用表AUTO_INCREMENT `u1_history`
--
ALTER TABLE `u1_history`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT COMMENT '序号';

--
-- 使用表AUTO_INCREMENT `u1_info`
--
ALTER TABLE `u1_info`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_integral`
--
ALTER TABLE `u1_integral`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_ip`
--
ALTER TABLE `u1_ip`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_jurisdiction`
--
ALTER TABLE `u1_jurisdiction`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_protection`
--
ALTER TABLE `u1_protection`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_session`
--
ALTER TABLE `u1_session`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_totp`
--
ALTER TABLE `u1_totp`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `u1_usergroup`
--
ALTER TABLE `u1_usergroup`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `u1_users`
--
ALTER TABLE `u1_users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

