SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `userdb`
--

-- --------------------------------------------------------

--
-- 表的结构 `nyalogin_activity`
--

CREATE TABLE `nyalogin_activity` (
  `hash` text NOT NULL COMMENT '用户哈希',
  `app` text NOT NULL COMMENT '应用程序名称',
  `timeset` datetime NOT NULL COMMENT '令牌生成时间',
  `timeend` datetime NOT NULL COMMENT '令牌自动失效时间',
  `ip` text COMMENT '绑定IP地址',
  `token` text NOT NULL COMMENT '访问令牌'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `nyalogin_jur`
--

CREATE TABLE `nyalogin_jur` (
  `id` int(3) NOT NULL COMMENT '权限ID',
  `jname` text NOT NULL COMMENT '权限组名称',
  `func` text COMMENT '可用功能代号(逗号)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `nyalogin_safe`
--

CREATE TABLE `nyalogin_safe` (
  `hash` text NOT NULL COMMENT '用户哈希',
  `qa` text NOT NULL COMMENT '密码提示问题和答案',
  `spwd` text NOT NULL COMMENT '二级密码哈希'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `nyalogin_user`
--

CREATE TABLE `nyalogin_user` (
  `id` int(32) UNSIGNED NOT NULL COMMENT '用户ID',
  `hash` text NOT NULL COMMENT '用户哈希',
  `mail` text NOT NULL COMMENT '用户邮箱',
  `phone` int(15) DEFAULT NULL COMMENT '手机号码',
  `mailv` text COMMENT '邮箱验证码',
  `phonev` text COMMENT '手机验证码',
  `pwd` text NOT NULL COMMENT '用户密码哈希',
  `name` text NOT NULL COMMENT '用户名或昵称',
  `ver` int(1) UNSIGNED NOT NULL DEFAULT '2' COMMENT '用户数据库版本',
  `twostep` text COMMENT '额外登录手续(逗号)',
  `loginfo` text COMMENT '最近登录信息(逗号)',
  `reginfo` int(11) DEFAULT NULL COMMENT '注册信息',
  `ban` datetime DEFAULT NULL COMMENT '封锁到时间',
  `alert` text COMMENT '重要警告文本',
  `jur` int(3) NOT NULL COMMENT '权限ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `nyalogin_jur`
--
ALTER TABLE `nyalogin_jur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `nyalogin_user`
--
ALTER TABLE `nyalogin_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `nyalogin_jur`
--
ALTER TABLE `nyalogin_jur`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT COMMENT '权限ID';
--
-- 使用表AUTO_INCREMENT `nyalogin_user`
--
ALTER TABLE `nyalogin_user`
  MODIFY `id` int(32) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID';COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
