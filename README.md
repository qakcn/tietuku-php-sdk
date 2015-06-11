tietuku-php-sdk
===============

贴图库的PHP SDK。

对全部API进行重写，另外也添加了兼容层以保持对旧版的兼容。


下载
----

<a href="https://github.com/qakcn/tietuku-php-sdk/raw/master/release/tietuku-php-sdk.php" download>点此下载</a> 新版的贴图库SDK

<a href="https://github.com/qakcn/tietuku-php-sdk/raw/master/release/tietuku_sdk.php" download>点此下载</a> 兼容旧版的贴图库SDK


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

仅兼容旧版的`TTKClient`类，`TieTuKuToken`类并无实现。`TTKClient`类没有旧版的`post`方法。

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

新版SDK使用`TietukuResult`类来返回结果，能更好地支持错误信息。  
同时结果能直接获取为关联数组，也可获取原始的JSON数据，更加方便使用。


### Tietuku类的使用

#### 创建对象

以贴图库提供的Access Key作为第一个参数，Secret Key作为第二个参数即可。Key请到贴图库开放平台的管理中心获取。本程序只能检查Key是否符合格式，而无法检查Key是够正确，如果在后面的操作中无法成功，请自行检查Key是否正确。

    $ttk = new Tietuku('00112233445566778899aabbccddeeff00112233','ffeeddccbbaa99887766554433221100ffeedd');    //两个Key都是40位的十六进制数的字符串表示

#### 设置属性

本程序有两个属性可以设置，`timeout`Token延时（生命周期）和`useragent`用户代理。`timeout`单位为秒，默认为60，请不要设置过小以免后续操作失败。有关**用户代理**请查阅维基百科。

    $ttk->timeout = 120;    //将Token生命周期设置为120秒
    $ttk->useragent = 'Mozilla/5.0 (IE 11.0 ;Windows NT 6.3; Trident/7.0; .NET4.0E; .NET4.0C; rv11.0) Like Gecko';    //将用户代理伪装为IE 11

#### 方法说明

下面为各个方法说明及对应的API文档链接。标注**缺省值**的参数可省略，但若后面的参数要设置值则前面的参数不能省略，可以填入缺省值来达到和省略一样的效果。

如：

    $ttk->getAlbums();    //省略所有参数
    $ttk->getAlbums(2);    //省略第二个参数
    $ttk->getAlbums(1,10);    //因设置了第二个参数，第一个参数不能省略，可填入缺省值1

##### 上传接口

文档：[ttp://open.tietuku.com/doc#upload]()

* `uploadFile($aid, $file, $filename)`

  从文件系统上传文件

  * `$aid` 整数，要上传文件到的相册ID
  * `$file` File对象，文件
  * `$filename` 字符串，要重设的文件名（用于想上传的文件名不是File对象中的文件名，比如临时文件）

  **注**：File类已包含在本SDK附带的`PHPHttpRequest`类里，创建对象时传入文件路径即可，如：
      $file = new File('/path/to/file');

* `uploadFromWeb($aid, $url)`

  从网络上传文件

  * `$aid` 整数，要上传文件到的相册ID
  * `$url` 字符串，合法的网络图片URL

##### 相册接口

* `getAlbums($page_no, $uid)`

  获取所有相册的列表，文档：[http://open.tietuku.com/doc#album-get]()

  * `$page_no` 整数，页码，缺省值`1`
  * `$uid` 整数，用户ID，缺省值`null`（表示获取当前用户的相册）

* `createAlbums($albumname)`

  创建相册，文档：[http://open.tietuku.com/doc#album-create]()

  * `$albumname` 字符串，相册名称

* `editAlbum($aid, $albumname)`

  编辑相册，文档：[http://open.tietuku.com/doc#album-editalbum]()

  * `$aid` 整数，相册ID
  * `$albumname` 字符串，要修改为的相册名称

* `deleteAlbum($aid)`

  删除相册，文档：[http://open.tietuku.com/doc#album-delalbum]()

  * `$aid` 整数，相册ID

##### 列表接口

* `getRandRecPics($cid)`

  获取30张随机推荐图片，文档：[http://open.tietuku.com/doc#list-getrandrec]()

  * `$cid` 整数，分类ID（使用`getCatalog`来查询），缺省值`null`（表示获取所有分类）

* `getAllPics($page_no, $cid)`

  获取全部图片，文档：[http://open.tietuku.com/doc#list-getnewpic]()

  * `$page_no` 整数，页码，缺省值`1`
  * `$cid` 整数，分类ID（使用`getCatalog`来查询），缺省值`null`（表示获取所有分类）

* `getPicsByAlbum($aid, $page_no)`

  获取相册内的图片，文档：[http://open.tietuku.com/doc#list-album]()

  * `$aid` 整数，相册ID
  * `$page_no` 整数，页码，缺省值`1`

* `getPicsByIds($ids)`

  通过一组ID来获取图片，文档：[http://open.tietuku.com/doc#list-getpicbyids]()

  * `$ids` 字符串，以半角逗号（`,`）分隔的整数图片ID

##### 图片接口

* `getPicIndo($id_findurl, $findurl)`

  通过ID或findurl来获取图片信息，文档：[http://open.tietuku.com/doc#pic-getonepic]()

  * `$id_findurl` 整数或字符串，图片ID或findurl，具体类型由后一个参数确定
  * `$findurl` 布尔型，如果为`true`则前一个参数为findurl，为`false`则前一个参数为图片ID，缺省值`false`

* `deletePic($pid)`

  删除图片（注意此删除并非从贴图库删除，仅为从用户相册中移除），文档：[http://open.tietuku.com/doc#pic-delpic]()

  * `$pid` 整数，图片ID

* `editPic($pid)`

  修改图片名称，文档：[http://open.tietuku.com/doc#pic-updatepicname]()

  * `$pid` 整数，图片ID

##### 喜欢接口

* `getLovePic($page_no)`

  获取喜欢的图片列表，文档：[http://open.tietuku.com/doc#collect-getlovepic]()

  * `$page_no` 整数，页码，缺省值`1`

* `lovePic($id)`

  喜欢一张图片，文档：[http://open.tietuku.com/doc#collect-addcollect]()

  * `$id` 整数，图片ID

* `unlovePic($id)`

  取消喜欢一张图片，文档：[http://open.tietuku.com/doc#collect-delcollect]()

  * `$id` 整数，图片ID

##### 分类接口

* `getCatalog()`

  查询分类列表，文档：[http://open.tietuku.com/doc#catalog-getall]()

##### 私有云上传接口

开通私有云的可以使用私有云上传接口，用法与上述不带**Private**的上传接口一致。

文档：[http://open.tietuku.com/doc#uppsc]()

* `uploadPrivateFile($aid, $file, $filename)` 上传文件
* `uploadPrivateFromWeb($aid, $url)` 上传网络文件

##### 私有云接口

开通私有云的可以使用私有云接口，用法与上述不带**Private**的对应方法一致。

* `getPrivatePicsByAlbum($aid, $page_no)` 通过相册获取图片，文档：[http://open.tietuku.com/doc#pcloud-piclist]()
* `getPrivatePicInfo($pid_findurl, $findurl)` 获取图片信息，文档：[http://open.tietuku.com/doc#pcloud-getpicpdetail]()
* `editPrivatePic($pid, $pname)` 编辑图片，文档：[http://open.tietuku.com/doc#pcloud-modifypicname]()

##### 只生成Token

如果只想生成Token而不进行实际的请求，只需要在上述方法后面加上**Token**即可。

参数除了上传接口的Token方法可以省略`$file`或`$url`参数之外，其他Token方法的参数说明不变。

如：

    $ttk->uploadFileToken(12);    //获取上传文件的Token，可以省略$file
    $ttk->getAlbumsToken();    //获取相册列表的Token

捐助
----

本程序开发者并非贴图库员工，与贴图库无利益关系。

您可以自愿捐款来支持本程序更好地开发。

支付宝账户：qakcn@hotmail.com
