<?php
/**
* 公共设置存储类
*/
class YaloginGlobal
{
	//权限组
	public $jurisdictionArr = array("封禁","root","主管理员","管理员","用户","来宾");
	
	//错误代码
	public $erroridArr = array(
		'-2' => "你暂时没有访问这个页面的权限。",
		'-1' => "未知错误。",
		'0' => "错误判断程序出现问题。",
		'10201' => "输入参数不正确。", 
		'10301' => "用户名不能为空。", 
		'10302' => "用户名长度不正确。", 
		'10303' => "用户名已经被占用。",
		'10401' => "昵称不能为空。",
		'10402' => "昵称长度不正确。",
		'10501' => "电子邮件地址不能为空。",
		'10502' => "电子邮件地址长度不正确。",
		'10503' => "不是有效的电子邮件地址。",
		'10601' => "密码不能为空。",
		'10603' => "无效的密码哈希值。",
		'10703' => "无效的二级密码哈希值。",
		'10801' => "密码提示问题1太长。",
		'10802' => "密码提示问题1答案太长。",
		'10803' => "密码提示问题2太长。",
		'10804' => "密码提示问题2答案太长。",
		'10805' => "密码提示问题3太长。",
		'10806' => "密码提示问题3答案太长。",
		'10901' => "性别不能为空。",
		'10902' => "性别长度不正确。",
		'11001' => "生日不能为空。",
		'11002' => "生日日期格式不正确。",
		'11101' => "用户上次登录APP不能为空。",
		'11102' => "用户上次登录APP长度不正确。",
		'11201' => "验证码会话丢失。",
		'11202' => "验证码不正确或超时。",
		'11203' => "需要验证码。",
		'90100' => "数据库连接程序错误。",
		'90101' => "数据库连接失败。",
		'90102' => "数据库查询返回了空白内容。",
		'90103' => "数据库查询错误。",
		'90103' => "数据库查询解析失败。",
		'1001' => "用户注册成功。"
		);
		
	//性别
	public $sexArr = array("保密(Secrecy)","男(Male)","女(Female)","其他(Other)","伪娘(男の娘)","男变女(Male to Female or MTF)","女变男(Female to Male or FTM)","变性人(Transsexual Person)","变性男(Transsexual Male)","变性男-性征(Transsexual Man)","变性女(Transsexual Female)","变性女-性征(Transsexual Woman)","跨性人(Trans Person or Trans* Person)","跨性女(Trans Female or Trans* Female)","跨性女-性征(Trans Woman or Trans* Woman)","跨性女-表观(Transfeminine)","跨性男(Trans Male or Trans* Male)","跨性男-性征(Trans Man or Trans* Man)","跨性男-表现(Transmasculine)","顺性男(Cis Male Cisgender Male)","顺性男-性征(Cis Man Cisgender Man)","顺性女(Cis Female Cisgender Female)","顺性女-性征(Cis Woman Cisgender Woman)","间性人(Intersex)","双性人(Bigender)","流性人(Gender Fluid)","泛性别(Pangender)","非二元(Non-binary)","非常规(Gender Nonconforming)","变体(Gender Variant)","酷儿(Genderqueer)","存疑(Gender Questioning)","无性(Agender)","都不是(Neither)");
}
?>