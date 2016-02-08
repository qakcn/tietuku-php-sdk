<?php

/**
 * Tietuku SDK
 *
 * 贴图库SDK，支持全部API。
 *
 * @package     tietuku-php-sdk
 * @subpackage  Tietuku
 * @author      qakcn <qakcnyn@gmail.com>
 * @copyright   2015 qakcn
 * @version     0.2
 * @license     http://mozilla.org/MPL/2.0/
 * @link        https://github.com/qakcn/tietuku-php-sdk
 */

//namespace tietuku-php-sdk;    //取消注释使用命名空间来避免冲突。

class Tietuku {

    /* API URL */
    private $url = array(
        'upload' => 'http://up.tietuku.com',
        'upload_private' => 'http://uppsc.tietuku.com',
        'prefix' => 'http://api.tietuku.com/v1/',
        'suffix' => array(
            'album' => 'Album',
            'list' => 'List',
            'pic' => 'Pic',
            'collect' => 'Collect',
            'catalog' => 'Catalog',
            'private' => 'Psc'
        )
    );

    /* 一些action的参数 */
    private $valid_actions = array(
        'uploadFile' => array('api'=>'upload', 'from'=>'file', 'valid' => array('aid')),
        'uploadFromWeb' => array('api'=>'upload', 'from'=>'web', 'valid' => array('aid')),

        'uploadPrivateFile' => array('api'=>'upload_private', 'from'=>'file', 'valid' => array('aid')),
        'uploadPrivateFromWeb' => array('api'=>'upload_private', 'from'=>'web', 'valid' => array('aid')),

        'getAlbums' => array('api'=>'album', 'action'=>'get', 'valid' => array('uid', 'page_no')),
        'createAlbum' => array('api'=>'album', 'action'=>'create', 'valid' => array('albumname')),
        'editAlbum' => array('api'=>'album', 'action'=>'editalbum', 'valid' => array('aid', 'albumname')),
        'deleteAlbum' => array('api'=>'album', 'action'=>'delalbum', 'valid' => array('aid')),
        'getRandRecPics' => array('api'=>'list', 'action'=>'getrandrec', 'valid' => array('cid')),
        'getAllPics' => array('api'=>'list', 'action'=>'getnewpic', 'valid' => array('page_no', 'cid')),
        'getPicsByAlbum' => array('api'=>'list', 'action'=>'album', 'valid' => array('aid', 'page_no')),
        'getPicsByIds' => array('api'=>'list', 'action'=>'getpicbyids', 'valid' => array('ids')),
        'getPicInfo' => array('api'=>'pic', 'action'=>'getonepic', 'valid' => array('id', 'findurl')),
        'deletePic' => array('api'=>'pic', 'action'=>'delpic', 'valid' => array('pid')),
        'editPic' => array('api'=>'pic', 'action'=>'updatepicname', 'valid' => array('pid', 'pname')),
        'getLovePic' => array('api'=>'collect', 'action'=>'getlovepic', 'valid' => array('page_no')),
        'lovePic' => array('api'=>'collect', 'action'=>'addcollect', 'valid' => array('id')),
        'unlovePic' => array('api'=>'collect', 'action'=>'delcollect', 'valid' => array('id')),
        'getCatalog' => array('api'=>'catalog', 'action'=>'getall', 'valid' => array()),

        'getPrivatePicsByAlbum' => array('api'=>'private', 'action'=>'piclist', 'valid' => array('aid', 'page_no')),
        'getPrivatePicInfo' => array('api'=>'private', 'action'=>'getpicpdetail', 'valid' => array('pid', 'findurl')),
        'editPrivatePic' => array('api'=>'private', 'action'=>'modifypicname', 'valid' => array('pid', 'pname')),
    );

    private $accesskey;
    private $secretkey;
    private $timeout = 60;
    private $useragent = 'tietuku-php-sdk/0.1 PHPHttpRequest/0.1';

    private function getURL($api) {
        if($api == 'upload' || $api == 'upload_private') {
            return $this->url[$api];
        }else {
            return $this->url['prefix'] . $this->url['suffix'][$api];
        }
    }

    /* 检查key是否符合格式 */
    private function checkKey($key) {
        return preg_match('/^[0-9a-fA-F]{40}$/', $key);
    }

    /* URL安全的Base64编码 */
    private function URLSafeBase64Encode($str){
        return str_replace(array('+', '/'), array('-', '_'), base64_encode($str));
    }

    /**
     * 对参数签名
     *
     * 来自旧版贴图库PHP SDK
     *
     * @package TieTuKu
     * @author Tears <i@ltteam.cn>
    */
    private function signEncode($str, $key){
        $hmac_sha1_str = "";
        if (function_exists('hash_hmac')){
            $hmac_sha1_str = hash_hmac("sha1", $str, $key, true);
        } else {
            $blocksize = 64;
            $hashfunc  = 'sha1';
            if (strlen($key) > $blocksize){
                $key = pack('H*', $hashfunc($key));
            }
            $key            = str_pad($key, $blocksize, chr(0x00));
            $ipad           = str_repeat(chr(0x36), $blocksize);
            $opad           = str_repeat(chr(0x5c), $blocksize);
            $hmac_sha1_str  = pack('H*', $hashfunc(($key ^ $opad) . pack('H*', $hashfunc(($key ^ $ipad) . $str))));
        }
        return $hmac_sha1_str;
    }

    /* 生成Token */
    private function genToken(array $params) {
        $param = $this->URLSafeBase64Encode(json_encode($params));
        $sign = $this->URLSafeBase64Encode($this->signEncode($param, $this->secretkey));
        return $this->accesskey . ':' . $sign . ':' . $param;
    }

    /**
     * 处理所有动作
     * One method to do them all!
     *
     * @param string $action 动作名
     * @param array $params 参数
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean $gettoken为true时返回Token，为false时返回TietukuResult对象，出错时返回false
     */
    private function doAction($action, array $params, $gettoken=false) {
        if(array_key_exists($action, $this->valid_actions)) {
            $api = $this->valid_actions[$action]['api'];
            $sendparam = array(
                'deadline' => time()+$this->timeout
            );
            $gettoken ? '' : $fd = new FormData();
            if($api == 'upload' || $api == 'upload_private') {
                $sendparam['from'] = $this->valid_actions[$action]['from'];
                if(!$gettoken) {
                    if($sendparam['from']=='file') {
                        $filename = isset($params['filename']) ? $params['filename'] : null;
                        $fd->append('file', $params['file'], $filename);
                    }else {
                        $fd->append('fileurl', $params['fileurl']);
                    }
                }
            }else {
                $sendparam['action'] = $this->valid_actions[$action]['action'];
            }
            foreach($params as $key => $value) {
                if(in_array($key, $this->valid_actions[$action]['valid'])) {
                    $sendparam[$key] = $value;
                }
            }
            $token = $this->genToken($sendparam);
            if($gettoken) {
                return $token;
            }else {
                $url = $this->getURL($api);
                $fd->append('Token',$token);
                //$fd->multipart = true;
                $phr = new PHPHttpRequest();
                $phr->open('post',$url);
                $phr->setRequestHeader('User-Agent',$this->useragent);
                if($phr->send($fd)) {
                    return new TietukuResult($phr->response);
                }
            }
        }
        return false;
    }

    /**
     * 构造函数
     *
     * @param string $accesskey 贴图库的AccessKey
     * @param string $secretkey 贴图库的SecretKey
     * @return boolean 如果Key的格式不正确则返回false
    */
    public function __construct($accesskey, $secretkey) {
        if($this->checkKey($accesskey) && $this->checkKey($secretkey)) {
            $this->accesskey = strtolower($accesskey);
            $this->secretkey = strtolower($secretkey);
        }else {
            return false;
        }
    }

    /**
     * getter，用于获取私有属性的值
     *
     * @param string $name 属性名
     * @return mixed 属性值
     */
    public function __get($name) {
        $valid = array('accesskey', 'secretkey', 'timeout', 'useragent');
        if(in_array($name, $valid)) {
            return $this->$name;
        }
    }

    /**
     * setter，用于设置私有属性的值
     *
     * @param string $name 属性名
     * @param mixed $value 属性值
     */
    public function __set($name, $value) {
        $valid = array('timeout', 'useragent');
        if(in_array($name, $valid)) {
            $this->$name = $value;
        }
    }

    /**
     * 上传文件（对应API：http://open.tietuku.com/doc#upload）
     *
     * @param integer $aid 相册ID
     * @param File $file 要上传的文件，为空时返回Token
     * @param string $filename 要重设为的文件名
     * @return string/TietukuResult/boolean $file为空则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function uploadFile($aid, File $file = null, $filename = null) {
        $params=array(
            'aid' => $aid,
        );
        if(!empty($file)) {
            $params['file'] = $file;
            if(!empty($filename)) {
                $params['filename'] = $filename;
            }
        }
        return $this->doAction('uploadFile', $params, empty($file));
    }

    /**
     * 以URL上传文件（对应API：http://open.tietuku.com/doc#upload）
     *
     * @param integer $aid 相册ID
     * @param string $url 要上传的文件的URL，为空时返回Token
     * @return string/TietukuResult/boolean $url为空则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function uploadFromWeb($aid, $url = null) {
        $params=array(
            'aid' => $aid,
        );
        if(!empty($url)) {
            $params['fileurl'] = $url;
        }
        return $this->doAction('uploadFromWeb', $params, empty($url));
    }

    /**
     * 私有云上传文件（对应API：http://open.tietuku.com/doc#uppsc）
     *
     * @param integer $aid 相册ID
     * @param File $file 要上传的文件对象，为空时返回Token
     * @param string $filename 要重设为的文件名
     * @return string/TietukuResult/boolean $file为空则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function uploadPrivateFile($aid, File $file = null, $filename = null) {
        $params=array(
            'aid' => $aid,
        );
        if(!empty($file)) {
            $params['file'] = $file;
            if(!empty($filename)) {
                $params['filename'] = $filename;
            }
        }
        return $this->doAction('uploadPrivateFile', $params, empty($file));
    }

    /**
     * 私有云以URL上传文件（对应API：http://open.tietuku.com/doc#uppsc）
     *
     * @param integer $aid 相册ID
     * @param string $url 要上传的文件的URL，为空时返回Token
     * @return string/TietukuResult/boolean $url为空则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function uploadPrivateFromWeb($aid, $url = null) {
        $params=array(
            'aid' => $aid,
        );
        if(!empty($url)) {
            $params['fileurl'] = $url;
        }
        return $this->doAction('uploadPrivateFromWeb', $params, empty($url));
    }

    /**
     * 获取相册列表（对应API：http://open.tietuku.com/doc#album-get）
     *
     * @param integer $page_no 页码
     * @param integer $uid 用户ID
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function getAlbums($page_no = 1, $uid = null, $gettoken = false) {
        $params=array(
            'page_no' => $page_no,
        );
        if(!empty($uid)) {
            $params['uid'] = $uid;
        }
        return $this->doAction('getAlbums', $params, $gettoken);
    }

    /**
     * 创建相册（对应API：http://open.tietuku.com/doc#album-create）
     *
     * @param string $albumname 相册名
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function createAlbum($albumname, $gettoken = false) {
        $params=array(
            'albumname' => $albumname,
        );
        return $this->doAction('createAlbum', $params, $gettoken);
    }

    /**
     * 编辑相册（对应API：http://open.tietuku.com/doc#album-editalbum）
     *
     * @param integer $aid 相册ID
     * @param string $albumname 要重设为的相册名
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function editAlbum($aid, $albumname, $gettoken = false) {
        $params=array(
            'aid' => $aid,
            'albumname' => $albumname,
        );
        return $this->doAction('editAlbum', $params, $gettoken);
    }

    /**
     * 删除相册（对应API：http://open.tietuku.com/doc#album-delalbum）
     *
     * @param integer $aid 相册ID
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function deleteAlbum($aid, $gettoken = false) {
        $params=array(
            'aid' => $aid,
        );
        return $this->doAction('deleteAlbum', $params, $gettoken);
    }

    /**
     * 随机获取30张推荐图片（对应API：http://open.tietuku.com/doc#list-getrandrec）
     *
     * @param integer $cid 分类ID，可用分类接口获取
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function getRandRecPics($cid = null, $gettoken = false) {
        $params=array();
        if(!empty($cid)) {
            $params['cid'] = $cid;
        }
        return $this->doAction('getRandRecPics', $params, $gettoken);
    }

    /**
     * 获取全部图片（对应API：http://open.tietuku.com/doc#list-getnewpic）
     *
     * @param integer $page_no 页码
     * @param integer $cid 分类ID，可用分类接口获取
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function getAllPics($page_no = 1, $cid = null, $gettoken = false) {
        $params=array(
            'page_no' => $page_no,
        );
        if(!empty($cid)) {
            $params['cid'] = $cid;
        }
        return $this->doAction('getAllPics', $params, $gettoken);
    }

    /**
     * 通过相册获取图片（对应API：http://open.tietuku.com/doc#list-album）
     *
     * @param integer $aid 相册ID
     * @param integer $page_no 页码
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function getPicsByAlbum($aid, $page_no = 1, $gettoken = false) {
        $params=array(
            'aid' => $aid,
            'page_no' => $page_no,
        );
        return $this->doAction('getPicsByAlbum', $params, $gettoken);
    }

    /**
     * 通过一组ID获取图片（对应API：http://open.tietuku.com/doc#list-getpicbyids）
     *
     * @param string $ids 由逗号分隔的图片ID
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function getPicsByIds($ids, $gettoken = false) {
        $params=array(
            'ids' => $ids,
        );
        return $this->doAction('getPicsByIds', $params, $gettoken);
    }

    /**
     * 获取图片信息（对应API：http://open.tietuku.com/doc#pic-getonepic）
     *
     * @param integer/string $id_findurl 图片ID或findurl
     * @param boolean $findurl 为true时上一个参数是finurl，为false时是图片ID
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function getPicInfo($id_findurl, $findurl = false, $gettoken = false) {
        if($findurl) {
            $params=array(
                'findurl' => $id_findurl,
            );
        }else {
            $params=array(
                'id' => $id_findurl,
            );
        }
        return $this->doAction('getPicInfo', $params, $gettoken);
    }

    /**
     * 删除图片（对应API：http://open.tietuku.com/doc#pic-delpic）
     *
     * @param integer $pid 图片ID
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function deletePic($pid, $gettoken = false) {
        $params=array(
            'pid' => $pid,
        );
        return $this->doAction('deletePic', $params, $gettoken);
    }

    /**
     * 编辑图片（对应API：http://open.tietuku.com/doc#pic-updatepicname）
     *
     * @param integer $pid 图片ID
     * @param string $pname 要重设为的图片名称
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function editPic($pid, $pname, $gettoken = false) {
        $params=array(
            'pid' => $pid,
            'pname' => $pname,
        );
        return $this->doAction('editPic', $params, $gettoken);
    }

    /**
     * 获取喜欢的图片（对应API：http://open.tietuku.com/doc#collect-getlovepic）
     *
     * @param integer $page_no 页码
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function getLovePic($page_no = 1, $gettoken = false) {
        $params=array(
            'page_no' => $page_no,
        );
        return $this->doAction('getLovePic', $params, $gettoken);
    }

    /**
     * 喜欢图片（对应API：http://open.tietuku.com/doc#collect-addcollect）
     *
     * @param integer $id 图片ID
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function lovePic($id, $gettoken = false) {
        $params=array(
            'id' => $id,
        );
        return $this->doAction('lovePic', $params, $gettoken);
    }

    /**
     * 取消喜欢图片（对应API：http://open.tietuku.com/doc#collect-delcollect）
     *
     * @param integer $id 图片ID
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function unlovePic($id, $gettoken = false) {
        $params=array(
            'id' => $id,
        );
        return $this->doAction('unlovePic', $params, $gettoken);
    }

    /**
     * 查询所有分类（对应API：http://open.tietuku.com/doc#catalog-getall）
     *
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function getCatalog($gettoken = false) {
        $params=array();
        return $this->doAction('getCatalog', $params, $gettoken);
    }

    /**
     * 私有云通过相册获取图片（对应API：http://open.tietuku.com/doc#pcloud-piclist）
     *
     * @param integer $aid 相册ID
     * @param integer $page_no 页码
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function getPrivatePicsByAlbum($aid, $page_no = 1, $gettoken = false) {
        $params=array(
            'aid' => $aid,
            'page_no' => $page_no,
        );
        return $this->doAction('getPrivatePicsByAlbum', $params, $gettoken);
    }

    /**
     * 私有云获取图片信息（对应API：http://open.tietuku.com/doc#pcloud-getpicpdetail）
     *
     * @param integer $pid_findurl 图片ID或findurl
     * @param boolean $findurl 为true时上一个参数是finurl，为false时是图片ID
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function getPrivatePicInfo($pid_findurl, $findurl = false, $gettoken = false) {
        if($findurl) {
            $params=array(
                'findurl' => $pid_findurl,
            );
        }else {
            $params=array(
                'pid' => $pid_findurl,
            );
        }
        return $this->doAction('getPrivatePicInfo', $params, $gettoken);
    }

    /**
     * 私有云编辑图片（对应API：http://open.tietuku.com/doc#pcloud-modifypicname）
     *
     * @param integer $pid 图片ID
     * @param string $pname 要重设为的图片名称
     * @param boolean $gettoken 是否只返回Token
     * @return string/TietukuResult/boolean 设置$gettoken=true则返回Token，成功则返回TietukuResult对象，失败则返回false
     */
    public function editPrivatePic($pid, $pname, $gettoken = false) {
        $params=array(
            'pid' => $pid,
            'pname' => $pname,
        );
        return $this->doAction('editPrivatePic', $params, $gettoken);
    }


    /**
     * 用于获取Token的函数别名
     * 为对应方法的名称后加上Token，参数说明见上述对应方法，无须设置$gettoken=true
     */
    public function uploadFileToken($aid) {
        return $this->uploadFile($aid);
    }
    public function uploadFromWebToken($aid) {
        return $this->uploadFromWeb($aid);
    }
    public function uploadPrivateFileToken($aid) {
        return $this->uploadPrivateFile($aid);
    }
    public function uploadPrivateFromWebToken($aid) {
        return $this->uploadPrivateFromWeb($aid);
    }
    public function getAlbumsToken($page_no = 1, $uid = null) {
        return $this->getAlbums($page_no, $uid, true);
    }
    public function createAlbumToken($albumname) {
        return $this->createAlbum($albumname, true);
    }
    public function editAlbumToken($aid, $albumname) {
        return $this->editAlbum($aid, $albumname, true);
    }
    public function deleteAlbumToken($aid) {
        return $this->deleteAlbum($aid, true);
    }
    public function getRandRecPicsToken($cid = 1) {
        return $this->getRandRecPics($cid, true);
    }
    public function getAllPicsToken($page_no = 1, $cid = 1) {
        return $this->getAllPics($page_no, $cid, true);
    }
    public function getPicsByAlbumToken($aid, $page_no = 1) {
        return $this->getPicsByAlbum($aid, $page_no, true);
    }
    public function getPicsByIdsToken($ids) {
        return $this->getPicsByIds($ids, true);
    }
    public function getPicInfoToken($id_findurl, $findurl = false) {
        return $this->getPicInfo($id_findurl, $findurl, true);
    }
    public function deletePicToken($pid) {
        return $this->deletePic($pid, true);
    }
    public function editPicToken($pid, $pname) {
        return $this->editPic($pid, $pname, true);
    }
    public function getLovePicToken($page_no = 1) {
        return $this->getLovePic($page_no, true);
    }
    public function lovePicToken($id) {
        return $this->lovePic($id, true);
    }
    public function unlovePicToken($id) {
        return $this->unlovePic($id, true);
    }
    public function getCatalogToken() {
        return $this->getCatalog(true);
    }
    public function getPrivatePicsByAlbumToken($aid, $page_no = 1) {
        return $this->getPrivatePicsByAlbum($aid, $page_no, true);
    }
    public function getPrivatePicInfoToken($pid_findurl, $findurl = false) {
        return $this->getPrivatePicInfo($pid_findurl, $findurl, true);
    }
    public function editPrivatePicToken($pid, $pname) {
        return $this->editPrivatePic($pid, $pname, true);
    }
}
