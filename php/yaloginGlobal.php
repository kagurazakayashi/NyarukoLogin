<?php
/**
* 公共常量存储类
*/
class YaloginGlobal
{
	//权限组
	public $jurisdictionArr = array("封禁","root","主管理员","管理员","用户","来宾");
	
	//输入过滤规则
	public $inputmatch = "/^[^\/\'\\\"#$%&\^\*]+$/";
	
	//错误代码
	public $erroridArr = array(
		'-2' => "你暂时没有访问这个页面的权限。", //通用
		'-1' => "未知错误。",
		'0' => "错误判断程序出现问题。",
		'10201' => "参数格式不正确。", //用户注册
		'10301' => "用户名不能为空。", 
		'10302' => "用户名长度不正确。", 
		'10303' => "用户名已经被占用。",
		'10304' => "用户名包含不允许的特殊字符。",
		'10401' => "昵称不能为空。",
		'10402' => "昵称长度不正确。",
		'10404' => "昵称包含不允许的特殊字符。",
		'10501' => "电子邮件地址不能为空。",
		'10502' => "电子邮件地址长度不正确。",
		'10503' => "不是有效的电子邮件地址。",
		'10504' => "电子邮件地址包含不允许的特殊字符。",
		'10601' => "密码不能为空。",
		'10603' => "无效的密码哈希值。",
		'10703' => "无效的二级密码哈希值。",
		'10801' => "密码提示问题1包含不允许的特殊字符。",
		'10802' => "密码提示问题1太长。",
		'10803' => "密码提示问题1答案包含不允许的特殊字符。",
		'10804' => "密码提示问题1答案太长。",
		'10805' => "密码提示问题2包含不允许的特殊字符。",
		'10806' => "密码提示问题2太长。",
		'10807' => "密码提示问题2答案包含不允许的特殊字符。",
		'10808' => "密码提示问题2答案太长。",
		'10809' => "密码提示问题3答案包含不允许的特殊字符。",
		'10810' => "密码提示问题3太长。",
		'10811' => "密码提示问题3答案包含不允许的特殊字符。",
		'10812' => "密码提示问题3答案太长。",
		'10901' => "性别不能为空。",
		'10902' => "性别长度不正确。",
		'10904' => "性别包含不允许的特殊字符。",
		'11001' => "生日不能为空。",
		'11002' => "生日日期格式不正确。",
		'11004' => "生日日期包含不允许的特殊字符。",
		'11101' => "用户上次登录APP不能为空。",
		'11102' => "用户上次登录APP长度不正确。",
		'11104' => "用户上次登录APP包含不允许的特殊字符。",
		'11201' => "验证码会话丢失。",
		'11202' => "验证码不正确或超时。",
		'11203' => "需要验证码。",
		'11204' => "验证码包含不允许的特殊字符。",
		'11301' => "参数格式不正确。", //验证激活码
		'11302' => "激活码不能为空。",
		'11303' => "激活码长度不正确。",
		'11304' => "激活码格式不正确。",
		'11401' => "激活码无效。",
		'11402' => "用户系统内部错误11402，请反馈该错误。",
		'11403' => "这个账户已经被激活过了。",
		'11404' => "激活码已经过期。",
		'11405' => "因服务器内部错误用户激活失败。",
		'11406' => "验证码对应用户哈希值失败。",
		'90100' => "数据库连接程序错误。", //数据库处理
		'90101' => "数据库连接失败。",
		'90102' => "数据库查询返回了空白内容。",
		'90103' => "数据库查询错误。",
		'90104' => "数据库查询解析失败。",
		'90200' => "字符串中包含不允许的字元。", //字符串处理
		'90201' => "字符串中包含修饰符。", //trim($data)
		'90201' => "字符串中包含反斜线。", //stripslashes($edata)
		'90202' => "字符串中包含HTML关键字。", //htmlspecialchars($edata)
		'90203' => "字符串中包含特殊符号。",
		'90210' => "非法字符串。",
		'90233' => "字符串中包含敏感字符。",
		'90300' => "电子邮件发送失败。", //邮件处理
		'90301' => "邮件模块内部错误：没有找到SMTP命令。", //Called command without being connected
		'90302' => "邮件模块内部错误：SMTP命令中断。", //Command 'command' contained line breaks
		'90303' => "邮件模块内部错误：SMTP命令执行失败。", //command command failed
		'90304' => "SMTP已连接到服务器。", //Already connected to a server
		'90305' => "邮件模块内部错误：SMTP无法连接到服务器。", //Failed to connect to server
		'90306' => "邮件模块内部错误：SMTP身份验证失败。", //Authentication is not allowed before HELO/EHLO
		'90307' => "邮件模块内部错误：无法进行SMTP身份验证。", //Authentication is not allowed at this stage
		'90308' => "邮件模块内部错误：SMTP身份验证方式没有找到。", //No supported authentication methods found
		'90309' => "邮件模块内部错误：SMTP身份验证方式不支持。", //Authentication method \"authtype\" is not supported
		'90310' => "邮件模块内部错误：SMTP命令未实现。", //The SMTP TURN command is not implemented
		'90311' => "邮件模块内部错误：SMTP相关指令未能发送。", //No HELO/EHLO was sent
		'90312' => "使用HELO握手。", //HELO handshake was used. Client knows nothing about server extensions
		'90313' => "邮件模块内部错误：SMTP服务器不支持所请求的验证方法。", //The requested authentication method \"authtype\" is not supported by the server
		'1001' => "用户注册成功。", //成功
		'1002' => "用户注册成功。向您发送了一封激活电子邮件，请按电子邮件中说明的步骤激活。如果没有收到，请检查输入的邮箱是否正确，或是否在垃圾邮件中。",
		'1003' => "用户激活成功。"
		);
		
	//性别
	public $sexArr = array("保密(Secrecy)","男(Male)","女(Female)","其他(Other)","伪娘(男の娘)","男变女(Male to Female or MTF)","女变男(Female to Male or FTM)","变性人(Transsexual Person)","变性男(Transsexual Male)","变性男-性征(Transsexual Man)","变性女(Transsexual Female)","变性女-性征(Transsexual Woman)","跨性人(Trans Person or Trans* Person)","跨性女(Trans Female or Trans* Female)","跨性女-性征(Trans Woman or Trans* Woman)","跨性女-表观(Transfeminine)","跨性男(Trans Male or Trans* Male)","跨性男-性征(Trans Man or Trans* Man)","跨性男-表现(Transmasculine)","顺性男(Cis Male Cisgender Male)","顺性男-性征(Cis Man Cisgender Man)","顺性女(Cis Female Cisgender Female)","顺性女-性征(Cis Woman Cisgender Woman)","间性人(Intersex)","双性人(Bigender)","流性人(Gender Fluid)","泛性别(Pangender)","非二元(Non-binary)","非常规(Gender Nonconforming)","变体(Gender Variant)","酷儿(Genderqueer)","存疑(Gender Questioning)","无性(Agender)","都不是(Neither)");
}
?>