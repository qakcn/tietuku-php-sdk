<?php

/**
 * Tietuku Compatible SDK
 *
 * 用于旧版SDK的兼容层。
 *
 * @package     tietuku-php-sdk
 * @subpackage  TietukuCompatible
 * @author      qakcn <qakcnyn@gmail.com>
 * @copyright   2015 qakcn
 * @version     0.1
 * @license     http://mozilla.org/MPL/2.0/
 * @link        https://github.com/qakcn/tietuku-php-sdk
 */

//namespace tietuku-php-sdk;    //取消注释使用命名空间来避免冲突。

class TTKClient {

    private $tietuku;

    public function __construct($accesskey, $secretkey){
        $this->tietuku = new Tietuku($accesskey, $secretkey);
    }

    /**
     * 查询随机30张推荐的图片
     *
     * 对应API：{@link http://open.tietuku.com/doc#list-getrandrec}
     *
     * @access public
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function getRandRec($createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getRandRecPics()->getJSON();
        }else {
            $result = $this->tietuku->getRandRecPicsToken();
        }
        return $result;
    }

    /**
     * 根据类型ID查询随机30张推荐的图片
     *
     * 对应API：{@link http://open.tietuku.com/doc#list-getrandrec}
     *
     * @access public
     * @param int $cid 类型ID。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function getRandRecByCid($cid,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getRandRecPics($cid)->getJSON();
        }else {
            $result = $this->tietuku->getRandRecPicsToken($cid);
        }
        return $result;
    }

    /**
     * 根据 图片ID 查询相应的图片详细信息
     *
     * 对应API：{@link http://open.tietuku.com/doc#pic-getonepic}
     *
     * @access public
     * @param int $id 图片ID。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function getOnePicById($id,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getPicInfo($id)->getJSON();
        }else {
            $result = $this->tietuku->getPicInfoToken($id);
        }
        return $result;
    }

    /**
     * 根据 图片find_url 查询相应的图片详细信息
     *
     * 对应API：{@link http://open.tietuku.com/doc#pic-getonepic}
     *
     * @access public
     * @param string $find_url 图片find_url
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function getOnePicByFind_url($find_url,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getPicInfo($find_url, true)->getJSON();
        }else {
            $result = $this->tietuku->getPicInfoToken($find_url, true);
        }
        return $result;
    }

    /**
     * 分页查询全部图片列表 每页30张图片
     *
     * 对应API：{@link http://open.tietuku.com/doc#list-getnewpic}
     *
     * @access public
     * @param int $page_no 页数，默认为1。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function getNewPic($page_no=1,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getAllPics($page_no)->getJSON();
        }else {
            $result = $this->tietuku->getAllPicsToken($page_no);
        }
        return $result;
    }

    /**
     * 通过类型ID分页查询全部图片列表 每页30张图片
     *
     * 对应API：{@link http://open.tietuku.com/doc#list-getnewpic}
     *
     * @access public
     * @param int $cid 类型ID。
     * @param int $page_no 页数，默认为1。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function getNewPicByCid($cid,$page_no=1,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getAllPics($page_no, $cid)->getJSON();
        }else {
            $result = $this->tietuku->getAllPicsToken($page_no, $cid);
        }
        return $result;
    }

    /**
     * 根据用户ID查询用户相册列表 每页30个相册
     *
     * 对应API：{@link http://open.tietuku.com/doc#album-get}
     *
     * @access public
     * @param int $uid 用户ID
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @param int $page_no 页数，默认为1。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function getAlbumByUid($uid=null,$createToken=false,$page_no=1){
        if(!$createToken) {
            $result = $this->tietuku->getAlbums($page_no, $uid)->getJSON();
        }else {
            $result = $this->tietuku->getAlbumsToken($page_no, $uid);
        }
        return $result;
    }

    /**
     * 查询自己收藏的图片列表
     *
     * 对应API：{@link http://open.tietuku.com/doc#collect-getlovepic}
     *
     * @access public
     * @param int $page_no 页数，默认为1。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function getLovePic($page_no=1,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getLovePic($page_no)->getJSON();
        }else {
            $result = $this->tietuku->getLovePicToken($page_no);
        }
        return $result;
    }

    /**
     * 通过图片ID喜欢(收藏)图片
     *
     * 对应API：{@link http://open.tietuku.com/doc#collect-addcollect}
     *
     * @access public
     * @param int $id 图片ID。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function addCollect($id,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->lovePic($id)->getJSON();
        }else {
            $result = $this->tietuku->lovePicToken($id);
        }
        return $result;
    }

    /**
     * 通过图片ID取消喜欢(取消收藏)图片
     *
     * 对应API：{@link http://open.tietuku.com/doc#collect-delcollect}
     *
     * @access public
     * @param int $id 图片ID。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function delCollect($id,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->unlovePic($id)->getJSON();
        }else {
            $result = $this->tietuku->unlovePicToken($id);
        }
        return $result;
    }

    /**
     * 通过相册ID分页查询相册中的图片 每页30张图片
     *
     * 对应API：{@link http://open.tietuku.com/doc#list-album}
     *
     * @access public
     * @param int $aid 相册ID。
     * @param int $page_no 页数，默认为1。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function getAlbumPicByAid($aid,$page_no=1,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getPicsByAlbum($aid,$page_no)->getJSON();
        }else {
            $result = $this->tietuku->getPicsByAlbumToken($aid,$page_no);
        }
        return $result;
    }

    /**
     * 查询所有的分类
     *
     * 对应API：{@link http://open.tietuku.com/doc#catalog-getall}
     *
     * @access public
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function getCatalog($createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getCatalog()->getJSON();
        }else {
            $result = $this->tietuku->getCatalogToken();
        }
        return $result;
    }

    /**
     * 创建相册
     *
     * 对应API：{@link http://open.tietuku.com/doc#album-create}
     *
     * @access public
     * @param string $albumname 相册名称。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function createAlbum($albumname,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->createAlbum($albumname)->getJSON();
        }else {
            $result = $this->tietuku->createAlbumToken($albumname);
        }
        return $result;
    }

    /**
     * 编辑相册
     *
     * 对应API：{@link http://open.tietuku.com/doc#album-editalbum}
     *
     * @access public
     * @param int $aid 相册ID。
     * @param string $albumname 相册名称。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function editAlbum($aid,$albumname,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->editAlbum($aid,$albumname)->getJSON();
        }else {
            $result = $this->tietuku->editAlbumToken($aid,$albumname);
        }
        return $result;
    }

    /**
     * 通过相册ID删除相册(只能删除自己的相册 如果只有一个相册，不能删除)
     *
     * 对应API：{@link http://open.tietuku.com/doc#album-delalbum}
     *
     * @access public
     * @param int $aid 相册ID。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function delAlbum($aid,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->deleteAlbum($aid)->getJSON();
        }else {
            $result = $this->tietuku->deleteAlbumToken($aid);
        }
        return $result;
    }

    /**
     * 通过一组图片ID 查询图片信息
     *
     * 对应API：{@link http://open.tietuku.com/doc#list-getpicbyids}
     *
     * @access public
     * @param mix $ids 图片ID数组。(1.多个ID用逗号隔开 2.传入数组)
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function getPicByIds($ids,$createToken=false){
        if(is_array($ids)) {
            $ids = implode(',', $ids);
        }
        if(!$createToken) {
            $result = $this->tietuku->getPicsByIds($ids)->getJSON();
        }else {
            $result = $this->tietuku->getPicsByIdsToken($ids);
        }
        return $result;
    }

    /**
     * 上传单个文件到贴图库
     *
     * 对应API：{@link http://open.tietuku.com/doc#upload}
     *
     * @access public
     * @param int $aid 相册ID
     * @param array $file 上传的文件。
     * @return string 如果$file!=null 返回请求接口的json数据否则只返回Token
     */
    public function uploadFile($aid,$file=null,$filename=null){
        if(!empty($file)) {
            $fileobj = new File($file);
            $result = $this->tietuku->uploadFile($aid, $fileobj, $filename)->getJSON();
        }else {
            $result = $this->tietuku->uploadFileToken($aid);
        }
        return $result;
    }

    /**
     * 上传多个文件到贴图库
     *
     * 对应API：{@link http://open.tietuku.com/doc#upload}
     *
     * @access public
     * @param int $aid 相册ID
     * @param string $filename 文件域名字
     * @return mixed 返回请求接口的json 如果文件域不存在文件则返回NULL
     */
    public function curlUpFile($aid,$filename){
        if(is_array($_FILES[$filename]['tmp_name'])){
            foreach ($_FILES[$filename]['tmp_name'] as $k => $v) {
                if(!empty($v)){
                    $userfile=$_FILES[$filename]['name'][$k];
                    $res[]=json_decode($this->uploadFile($aid,$v,$userfile));
                }
            }
        }else{
            $res=json_decode($this->uploadFile($aid,$_FILES[$filename]['tmp_name'],$_FILES[$filename]['name']));
        }
        return json_encode($res);
    }

    /**
     * 上传网络文件到贴图库 (只支持单个连接)
     *
     * 对应API：{@link http://open.tietuku.com/doc#upload-url}
     *
     * @access public
     * @param int $aid 相册ID
     * @param string $fileurl 网络图片地址
     * @return string 如果$fileurl!=null 返回请求接口的json数据否则只返回Token
     */
    public function uploadFromWeb($aid,$fileurl=null){
        if(!empty($fileurl)) {
            $result = $this->tietuku->uploadFromWeb($aid,$fileurl)->getJSON();
        }else {
            $result = $this->tietuku->uploadFromWebToken($aid);
        }
        return $result;
    }

    /**
     * 通过图片ID删除图片（并非从贴图库删除，仅从用户相册中移除）
     *
     * 对应API：{@link http://open.tietuku.com/doc#pic-delpic}
     *
     * @access public
     * @param int $pid 图片ID。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function delPic($pid,$createToken=false) {
        if(!$createToken) {
            $result = $this->tietuku->deletePic($pid)->getJSON();
        }else {
            $result = $this->tietuku->deletePicToken($pid);
        }
        return $result;
    }

    /**
     * 修改图片名称
     *
     * 对应API：{@link http://open.tietuku.com/doc#pic-updatepicname}
     *
     * @access public
     * @param int $pid 图片ID。
     * @param string $pname 图片名称。
     * @param boolean $createToken 是否只返回Token，默认为false。
     * @return string 如果$createToken=true 返回请求接口的json数据否则只返回Token
     */
    public function updatePicName($pid,$pname,$createToken=false) {
        if(!$createToken) {
            $result = $this->tietuku->editPic($pid,$pname)->getJSON();
        }else {
            $result = $this->tietuku->editPicToken($pid,$pname);
        }
        return $result;
    }

    /**
     * 以下为私有云API，使用方法与参数说明和上述不带Psc的对应方法一致
     */
    public function uploadFilePsc($aid,$file=null,$filename=null){
        if(!empty($file)) {
            $filobj = new File($file);
            $result = $this->tietuku->uploadPrivateFile($aid, $fileobj, $filaname)->getJSON();
        }else {
            $result = $this->tietuku->uploadPrivateFileToken($aid);
        }
        return $result;
    }

    public function curlUpFilePsc($aid,$filename){
        if(is_array($_FILES[$filename]['tmp_name'])){
            foreach ($_FILES[$filename]['tmp_name'] as $k => $v) {
                if(!empty($v)){
                    $userfile=$_FILES[$filename]['name'][$k];
                    $res[]=json_decode($this->uploadFilePsc($aid,$v,$userfile));
                }
            }
        }else{
            $res=json_decode($this->uploadFilePsc($aid,$_FILES[$filename]['tmp_name'],$_FILES[$filename]['name']));
        }
        return json_encode($res);
    }

    public function uploadFromWebPsc($aid,$fileurl=null){
        if(!empty($fileurl)) {
            $result = $this->tietuku->uploadPrivateFromWeb($aid,$fileurl)->getJSON();
        }else {
            $result = $this->tietuku->uploadPrivateFromWebToken($aid);
        }
        return $result;
    }

    public function getAlbumPicByAidPsc($aid,$page_no=1,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getPrivatePicsByAlbum($aid,$page_no)->getJSON();
        }else {
            $result = $this->tietuku->getPrivatePicsByAlbumToken($aid,$page_no);
        }
        return $result;
    }

    //兼容糟糕的官方SDK
    public function getPscPicList($aid,$page_no=1) {
        return $this->getAlbumPicByAidPsc($aid,$page_no);
    }

    public function getOnePicByIdPsc($id,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getPrivatePicInfo($id)->getJSON();
        }else {
            $result = $this->tietuku->getPrivatePicInfoToken($id);
        }
        return $result;
    }

    //兼容糟糕的官方SDK
    public function getpicpdetail($pid) {
        return $this=>getOnePicByIdPsc($pid);
    }

    public function getOnePicByFind_urlPsc($find_url,$createToken=false){
        if(!$createToken) {
            $result = $this->tietuku->getPrivatePicInfo($find_url, true)->getJSON();
        }else {
            $result = $this->tietuku->getPrivatePicInfoToken($find_url, true);
        }
        return $result;
    }

    public function updatePicNamePsc($pid,$pname,$createToken=false) {
        if(!$createToken) {
            $result = $this->tietuku->editPrivatePic($pid,$pname)->getJSON();
        }else {
            $result = $this->tietuku->editPrivatePicToken($pid,$pname);
        }
        return $result;
    }

    //兼容糟糕的官方SDK，are you kidding me?
    public function modifyPicName($pid,$pname) {
        return $this->updatePicNamePsc($pid,$pname);
    }
}
