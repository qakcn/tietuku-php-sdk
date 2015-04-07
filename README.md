tietuku-php-sdk
===============

贴图库的PHP SDK。

对全部API进行重写，另外也添加了兼容层以保持对旧版的兼容。


下载
----

<a href="https://github.com/qakcn/tietuku-php-sdk/raw/master/release/tietuku-php-sdk.php" download>点此下载</a>新版的贴图库SDK

<a href="https://github.com/qakcn/tietuku-php-sdk/raw/master/release/tietuku_sdk.php" download>点此下载</a>兼容旧版的贴图库SDK


文件说明
--------

* `Tietuku.class.php`  贴图库SDK主类文件
* `TietukuResult.class.php`  贴图库SDK结果类文件
* `TietukuCompatible.class.php`  贴图库SDK旧版兼容类文件
* `PHPHttpRequest.class.php`  [PHPHttpRequest](https://github.com/qakcn/PHPHttpRequest)类文件
* `load.php`  加载上述全部类文件，用于测试
* `build.php` 生成下面两个文件，只能在命令行中运行
* `release/tietuku-php-sdk.php`  贴图库SDK的单文件
* `release/tietuku_sdk.php`  兼容旧版SDK的单文件


旧版SDK兼容说明
---------------

仅兼容旧版的`TTKClient`类，`TieTuKuToken`类并无实现。没有旧版的`post`方法。

除此之外使用旧版SDK的程序基本不用进行修改，只要替换相应的文件即可。

另外根据API的变动引入了如下新内容：

1. `getAlbumByUid`方法最后增加一个参数`$page_no`，表示页码;

2. 新增删除图片的方法`delPic`、修改图片名称的方法`updatePicName`;

3. 新增对应私有云API的方法`uploadFilePsc`、`curlUpFilePsc`、`uploadFromWebPsc`、`getAlbumPicByAidPsc`、`getOnePicByIdPsc`、`getOnePicByFind_urlPsc`、`modifyPicNamePsc`，均与没有最后的`Psc`的原方法用法一致。

具体使用方法请查看文件中的注释。


新版SDK使用说明
---------------

新版SDK使用了`PHPHttpRequest`系列类来进行HTTP请求，避免了旧版SDK对cURL扩展的依赖。  
现在使用的全部函数均是PHP自带函数，只要没有被禁用相关函数，就能够运行，比旧版更加轻量级。

新版SDK对旧版的方法名称进行了调整，但大致使用方法没有改变，可以很快上手。

新版SDK使用`TietukuResult`类来返回结果，能更好地支持错误信息，避免出错却不知错在哪里的问题。  
同时结果能直接获取为关联数组，也可获取原始的JSON数据，更加方便使用。

捐助
----

本程序开发者并非贴图库员工，与贴图库无利益关系。

您可以自愿捐款来支持本程序更好地开发。

支付宝账户：qakcn@hotmail.com
