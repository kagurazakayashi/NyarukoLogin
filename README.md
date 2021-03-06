# ![](icon/icon.png) NyarukoLogin 2

一个通用的用户登录系统，目标是本身作为一个服务，为外部业务系统进行服务，可多开，可负载均衡。

- 注意：这个程序尚未做完，请勿使用。
- 要查看旧版本代码和此处的说明，请前往 v2016_expired 分支。
- 不支持 Windows 。

## 开发进度

随着开发的进程，功能随时会有添加或删除。

- [x] 核心
  - [x] 数据库类：封装数据库的各种查询和连接方式。
  - [x] 安全类：各种加密方式、常用加密解密封装、通用数据格式检查等。
  - [x] 信息类：错误代码和提示库，并封装一些提示方法。
  - [x] 用户设置类：尽可能多的配置文件中提供详细选项。
- [x] 数据库
  - [x] 封装数据库的各种查询和连接方式，实现传入键值数组就能增删改查统。
    - [x] MySQL
    - [x] Redis
  - [x] 支持读写分离，读写两组数据库可单独指定。
    - [x] 数据库类调用时自动选择建立只读数据库还是写入数据库的连接，并自动断开连接。
  - [x] 支持负载均衡，读写可分别指定一组不同的数据库。
    - [x] 使用 Redis 控制数据库负载均衡。
  - [x] 支持读写数据库同时并行连接。
  - [x] 一次连接执行多条 SQL 语句，语句执行完毕后再断开连接。
  - [x] 支持自由指定数据库中每个表的名称。
  - [x] 测试脚本
- [x] 加密传输
  - [x] 30秒 TOTP 动态密码加密：使用 TOTP 作为服务器与客户端的动态密码种子
    - [x] 加密令牌申请接口：客户端第一次连接时将和服务器端商定一个 TOTP 动态密码。
    - [x] 网络延迟兼容性：可配置使用过期代码的尝试次数。
    - [x] 时间戳双向传递
    - [x] 时移加密：使用其他同步时间生成 TOTP 动态密码。
  - [x] XXTEA 加密。
  - [x] base64 编码变种。
  - [x] XXTEA + base64 + TOTP + MD5 + 盐 混合加密收发 JSON 数据。
  - [x] 客户端可自主决定是否进行加密传输。
  - [x] 接收参数和发送参数的安全封装（包括解析、加密解密、验证访问权限等）。
  - [x] 明文传输测试脚本、加密传输测试脚本
- [ ] 访问验证
  - [x] 支持为每个功能设定不同的接口访问频率。
  - [x] 可设置每个 IP 地址的封禁时长。
  - [ ] 自动屏蔽高危操作的 IP 地址。
  - [ ] 记录 IP 地址归属地
- [x] 登录设备数量限制
  - [x] 可设置允许登录的最大会话数量。
  - [x] 可分别设置手机、平板、网页、PC端允许登录的最大设备数量。
  - [x] 超过限制自动登出最早的设备。
  - [x] 为模拟器单独设置限制。
- [x] 敏感词屏蔽
  - [x] 屏蔽敏感词，可处理段落时关键词（起止）
  - [x] 封装接口，在所有用户自定义文本输入的位置应用检查
  - [x] 可以直接回绝输入也可以用*取代。
  - [x] 支持从 json 文件拉取词语表
  - [x] 支持从 Redis 快速拉取词语表
  - [x] 用于导入其他应用中的逐行 txt 词库的转换脚本
  - [x] Redis 词语表导入导出脚本
  - [x] 测试脚本
- [ ] 系统日志：用于进行调试
  - [x] 数据库日志：记录所有执行的 SQL 语句及数据库返回的结果。
  - [x] 参数日志：记录所有收到的参数及传回客户端的参数内容。
  - [x] 可以自定义文件位置，随时开关日志功能
  - [x] 数据库包含历史记录表，记录所有的关键操作
    - [ ] 人工审核后台
- [x] APP式接入功能
  - [x] 必须在数据库中注册的 APP 令牌才能访问。
  - [ ] 与外部的业务系统进行对接
  - [ ] 多个用于与外部系统交互的内置权限
  - [ ] 测试脚本
- [x] 文件功能
  - [x] 限制可以上传的文件类型
    - [x] 单独限制视频和图片对应的扩展名限制
  - [x] 图片上传功能
    - [x] 上传检查
    - [x] 图片转换模块
    - [x] 图片水印
      - [x] GIF压缩和水印
  - [x] 视频上传功能
    - [x] 上传检查
    - [x] 视频转码模块
  - [x] 媒体异步二压
    - [x] 生成多种预设尺寸和压缩比的图片和视频
    - [x] 可自行定义尺寸名称、最大宽高、清晰度
    - [x] 将名称组合应用到文件名
    - [x] 可以为尺寸和压缩比预设创建多个方案
    - [x] 双端比例计算
    - [x] 创建一个单独的二压服务
      - [x] PHP 端生成配置
      - [x] 使用 Redis 双向传输配置
      - [x] GO 端处理配置和启动二压
      - [x] 配置为 Linux 系统服务
  - [x] 媒体文件查询
    - [x] 该媒体文件都有哪些清晰度可以用
    - [x] 该媒体文件都有哪些格式可以用
    - [x] 向前端推荐尺寸和格式
  - [ ] 多媒体审核功能
  - [ ] 阿里云云服务支持
    - [ ] OSS 对象存储 API
    - [ ] VOD 视频点播 API
  - [ ] 测试脚本
- [ ] 用户登录
  - [x] 检查用户是否存在
  - [x] 检查登录失败次数来决定是否需要输入验证码，并自动发放一个新的验证码
  - [x] 检查验证码是否正确，并自动发放一个新的验证码
  - [x] 检查用户名和密码，根据需要自动发放一个新的验证码
  - [x] 检查账户是否异常，支持存储警告信息
  - [x] 同一种类型的设备和全部设备可以设置同时登录上限（例如可以限制为用户手机端只能一台登）
    - [x] 自动顶掉当前种类设备的较旧登录会话
  - [ ] 两步验证
  - [x] 测试脚本
- [x] 用户注册
  - [x] 注册验证
    - [x] 客户端使用图形验证码注册新用户
    - [x] 客户端使用邮件注册新用户
    - [x] 客户端使用短信注册新用户
    - [x] 创建临时令牌
    - [x] 客户端使用临时令牌注册新用户
    - [x] 删除临时令牌
- [x] 用户资料
  - [x] 为昵称生成唯一代码（神楽坂雅詩#5534）
  - [x] 多元性别选项和独立称呼方式选项支持
  - [x] 一个用户可以关联多份资料（例如注册自己的宠物）
  - [x] 昵称，地址，签名，个人介绍 的编辑
  - [x] 头像，背景图 的编辑
  - [x] 资料修改测试脚本
- [x] 子账户支持
  - [x] 查询主账户下有哪些子账户
  - [x] 查询子账户的资料
  - [x] 查询指定子账户是否属于当前主账户
- [ ] 两步验证和密码保护管理
  - [ ] 谷歌验证器
    - [ ] 创建
    - [ ] 验证
    - [ ] 解绑
    - [ ] 二维码生成器
    - [ ] 测试脚本
  - [ ] 密码提示问题
    - [ ] 创建
    - [ ] 验证
    - [ ] 解绑
    - [ ] 测试脚本
  - [ ] 恢复代码
    - [ ] 创建
    - [ ] 验证
    - [ ] 重置
    - [ ] 测试脚本
  - [x] 短信验证码
    - [x] 创建
    - [x] 发送
    - [x] 验证
    - [ ] 手机号变更
    - [x] 测试脚本
  - [x] 邮件验证码
    - [x] 创建
    - [x] 发送
    - [x] 验证
    - [ ] 邮箱变更
    - [x] 测试脚本
  - [ ] 实名认证
    - [ ] 身份证号格式识别
    - [ ] 外部系统校验身份证
    - [ ] 测试脚本
  - [ ] 手机、邮箱、身份证的黑名单
    - [ ] 测试脚本
  - [ ] 设置为两步验证方式
- [ ] 用户权限
  - [ ] 内置权限
  - [ ] 权限的查询和修改接口
  - [ ] 外部自定义权限接口
- [ ] 业务和积分
  - [ ] 业务列表
  - [ ] 为每个业务的每个用户提供积分区域
  - [ ] 为每个业务的每个用户提供积分档位和称号对应
  - [ ] 为外部系统提供积分变更接口
  - [ ] 测试脚本
- [x] 站内信
  - [x] 系统通知
  - [x] 接收和发送用户间站内信
  - [x] 接收来自外部系统提供的站内信（提及、评论、转发、视频完成二压等）
  - [x] 组合相同类型通知
  - [x] 测试脚本
- [ ] 搜索
  - [x] 区间和排序
  - [ ] 模糊搜索用户
    - [x] 快速模糊搜索用户名
  - [ ] 测试脚本
- [ ] 运维相关工具脚本

## 文档

[请转至 Wiki](https://github.com/kagurazakayashi/NyarukoLogin/wiki)

### 通用
- [文件列表](https://github.com/kagurazakayashi/NyarukoLogin/wiki/文件列表)
- [通用的提交和返回值](https://github.com/kagurazakayashi/NyarukoLogin/wiki/通用的提交和返回值)
- [错误代码表](https://github.com/kagurazakayashi/NyarukoLogin/wiki/错误代码表)
- [使用的第三方库](https://github.com/kagurazakayashi/NyarukoLogin/wiki/使用的第三方库)

### 处理流程
- [加密通信处理流程](https://github.com/kagurazakayashi/NyarukoLogin/wiki/加密通信处理流程)
- [用户注册流程](https://github.com/kagurazakayashi/NyarukoLogin/wiki/用户注册流程)
  - [获取图形验证码](https://github.com/kagurazakayashi/NyarukoLogin/wiki/获取图形验证码)
  - [获取短信和邮件验证码](https://github.com/kagurazakayashi/NyarukoLogin/wiki/获取短信和邮件验证码)
  - [验证短信和邮件验证码](https://github.com/kagurazakayashi/NyarukoLogin/wiki/验证短信和邮件验证码)
  - [注册新用户](https://github.com/kagurazakayashi/NyarukoLogin/wiki/注册新用户)
  - [注册新子账户](https://github.com/kagurazakayashi/NyarukoLogin/wiki/注册新子账户)
- [用户登录流程](https://github.com/kagurazakayashi/NyarukoLogin/wiki/用户登录流程)
  - [检查是否已经登录](https://github.com/kagurazakayashi/NyarukoLogin/wiki/检查是否已经登录)
  - [登出](https://github.com/kagurazakayashi/NyarukoLogin/wiki/登出)
- [接口测试流程](https://github.com/kagurazakayashi/NyarukoLogin/wiki/接口测试流程)
  - [功能测试脚本](https://github.com/kagurazakayashi/NyarukoLogin/wiki/功能测试脚本)
- [文件上传](https://github.com/kagurazakayashi/NyarukoLogin/wiki/文件上传)
  - [查询媒体文件可提供的尺寸与格式](https://github.com/kagurazakayashi/NyarukoLogin/wiki/查询媒体文件可提供的尺寸与格式)

### 功能
- 用户信息查询
  - [搜索用户](https://github.com/kagurazakayashi/NyarukoLogin/wiki/搜索用户)
  - [查询用户资料](https://github.com/kagurazakayashi/NyarukoLogin/wiki/查询用户资料)
- 通知和站内信
  - [站内信发送](https://github.com/kagurazakayashi/NyarukoLogin/wiki/站内信发送)
  - [站内信列表获取](https://github.com/kagurazakayashi/NyarukoLogin/wiki/站内信列表获取)
  - [站内信已读标记](https://github.com/kagurazakayashi/NyarukoLogin/wiki/站内信已读标记)

### 后台服务
- [后台服务](https://github.com/kagurazakayashi/NyarukoLogin/wiki/后台服务)

### 附加工具
- [导入和导出关键词库](https://github.com/kagurazakayashi/NyarukoLogin/wiki/导入和导出关键词库)
  - [测试关键词](https://github.com/kagurazakayashi/NyarukoLogin/wiki/功能测试脚本#敏感词模块测试)

### 二次开发
- [二次开发](https://github.com/kagurazakayashi/NyarukoLogin/wiki/二次开发)