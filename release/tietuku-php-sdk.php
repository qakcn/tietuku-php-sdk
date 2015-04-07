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
 * @version     0.1
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
     * 私有云上传文件（对应API：hhttp://open.tietuku.com/doc#uppsc）
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
     * @param integer $id_findurl 图片ID或findurl
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


/**
 * TietukuResult
 *
 * 用于返回结果的类。
 *
 * @package     tietuku-php-sdk
 * @subpackage  TietukuResult
 * @author      qakcn <qakcnyn@gmail.com>
 * @copyright   2015 qakcn
 * @version     0.1
 * @license     http://mozilla.org/MPL/2.0/
 * @link        https://github.com/qakcn/tietuku-php-sdk
 */



class TietukuResult {

    private $response;

    /* 构造函数 */
    public function __construct(PHPHttpResponse $response) {
        $this->response = $response;
    }

    /**
     * getter，获取私有属性值时调用
     *
     * @param string $name 属性名
     * @return mixed 属性值
     */
    public function __get($name) {
        switch($name) {
            case 'status':
                return $this->response->status;
                break;
        }
    }

    /**
     * 获取错误信息
     * 
     * @return string 对应错误代码的错误信息
     */
    public function getError() {
        switch($this->response->status) {
            case '200':
                return 'OK';
                break;
            case '207':
                return '已经喜欢过该图片';
                break;
            case '401':
                return 'Token错误';
                break;
            case '460':
                return '相册ID无效或该用户无权限操作该相册';
                break;
            case '461':
                return '相册名称参数为空';
                break;
            case '463':
                return '只有一个相册时，无法删除';
                break;
            case '464':
                return '该相册内有照片，无法删除';
                break;
            case '465':
                return '相册名称长度';
                break;
            case '467':
                return '不允许删除私有相册';
                break;
            case '471':
                return '缺失图片ID';
                break;
            case '473':
                return '图片不存在';
                break;
            case '475':
                return '图片数量不可多于30';
                break;
            case '476':
                return '权限不足';
                break;
            case '482':
                return '域名绑定未开启或审核未通过';
                break;
            case '599':
                return '服务器操作失败';
                break;
        }
    }

    /* 将结果获取为关联数组 */
    public function getAssoc() {
        return json_decode($this->response->data, true);
    }

    /* 将结果获取为对象 */
    public function getObject() {
        return json_decode($this->response->data);
    }

    /* 将结果获取为JSON字符串 */
    public function getJSON() {
        return $this->response->data;
    }
}


/**
 * PHPHttpRequest
 *
 * 用于发送HTTP请求的类。包含四个类：PHPHttpRequest、PHPHttpResponse、File和FormData。
 *
 */



/**
 * PHPHttpRequest API
 *
 * Methods are like JavaScript XMLHttpRequest's
 * Response store in private property $response as PHPHttpResponse instance
 *
 * @package     PHPHttpRequest
 * @subpackage  PHPHttpRequest
 * @author      qakcn <qakcnyn@gmail.com>
 * @copyright   2015 qakcn
 * @version     0.1
 * @license     http://mozilla.org/MPL/2.0/
 * @link        https://github.com/qakcn/PHPHttpRequest
 */

class PHPHttpRequest {

    private $method;
    private $scheme;
    private $host;
    private $port;
    private $user;
    private $password;
    private $path;
    private $query;
    private $response;
    private $headers;
    private $cookies;

    const HTTP_EOL = "\r\n";


    /**
     * generate HTTP header string
     * 
     * @return string generated header
     * @access private
     */
    private function genHeader() {
        $header = $this->method . ' ' . $this->path . (isset($this->query) ? '?' . $this->query : '') . ' HTTP/1.1' . PHPHttpRequest::HTTP_EOL;
        $header .= 'Host: ' . $this->host . PHPHttpRequest::HTTP_EOL;
        foreach($this->headers as $hn => $hv) {
            $header .= $hn . ': ' . $hv . PHPHttpRequest::HTTP_EOL;
        }
        if(count($this->cookies)>0) {
            $header .= 'Cookie: ';
            foreach($this->cookies as $cn => $cv) {
                $header .= $cn . '=' . $cv . '; ';
            }
            $header = substr($header, 0, -2) . PHPHttpRequest::HTTP_EOL;
        }
        $header .= PHPHttpRequest::HTTP_EOL;
        return $header;
    }


    /**
     * reset to default properties, make PHPHttpRequest instance again to use
     * 
     * @access private
     */
    private function reset() {
        unset($this->method);
        unset($this->scheme);
        unset($this->host);
        unset($this->port);
        unset($this->user);
        unset($this->password);
        unset($this->path);
        unset($this->query);
        $this->headers = array(
            'User-Agent' => 'PHPHttpRequest/0.1',
            'Accept' => '*/*',
            'Connection' => 'close',
            'Cache-Control' => 'no-cache'
        );
        $this->cookies = array();
    }


    /**
     * send request
     * 
     * @param string $data data ready for send
     * @return false if unable to establish connection
     * @access private
     */
    private function sendRequest($data) {
        $host = ($this->scheme=='http' ? '' : 'tls://').$this->host;
        $fp = @fsockopen($host, $this->port);
        if($fp !== false) {
            fwrite($fp, $this->genHeader() . $data);
            $result = '';
            while(!feof($fp)) {
                $result .= fgets($fp);
            }
            fclose($fp);
            $this->response = new PHPHttpResponse($result);
            $this->reset();
            return true;
        }
        return false;
    }


    /**
     * constructor, do some initiation
     * 
     * @access public
     */
    public function __construct() {
        $this->reset();
    }


    /**
     * set request header
     * 
     * @param string $name header name
     * @param string $value header value
     * @access public
     */
    public function setRequestHeader($name, $value) {
        if(strtolower(trim($name))!='cookie') {
            $this->headers[trim($name)] = trim($value);
        }
    }


    /**
     * set request cookie
     * 
     * @param string $name cookie name
     * @param string $value cookie value
     * @access public
     */
    public function setCookie($name, $value) {
        $this->cookies[$name] = $value;
    }

    public function __get($name) {
        $valid = array('response');
        if(in_array($name, $valid)) {
            return $this->$name;
        }
    }


    /**
     * open link stage
     * 
     * @param string $method HTTP method, now support HEAD, GET, PUT, POST and DELETE
     * @param string $url URL to send request, must be absolute path
     * @return boolean false if method not support or URL not conform format
     * @access public
     */
    public function open($method, $url) {
        $method = strtoupper($method);
        $valid_method = array('HEAD', 'GET', 'PUT', 'POST', 'DELETE');
        if(in_array($method, $valid_method)) {
            $this->method = $method;
            $url = parse_url($url);
            if(isset($url['scheme']) && isset($url['host']) && ($url['scheme'] == 'http' || $url['scheme'] == 'https')) {
                $this->scheme = $url['scheme'];
                $this->host = $url['host'];
                $this->port = isset($url['port']) ? $url['port'] : ($url['scheme']=='http' ? 80 : 443);
                $this->path = isset($url['path']) ? $url['path'] : '/';
                isset($url['user']) ? $this->user = $url['user'] : '';
                isset($url['pass']) ? $this->password = $url['pass'] : '';
                isset($url['query']) ? $this->query = $url['query'] : '';
                return true;
            }
        }
        return false;
    }


    /**
     * send request
     * 
     * @param string/FormData/File $data data ready for send
     * @return boolean true if send successfully
     * @access public
     */
    public function send($data='') {
        if(isset($this->scheme)) {
            $postdata = '';
            if($this->method != 'GET' && $this->method != 'HEAD' && $this->method != 'DELETE') {
                if(is_a($data, 'FormData')) {
                    if($data->hasFile || $data->multipart) {
                        srand((double)microtime()*1000000);
                        $boundary = '---------------------------'.substr(md5(rand(0,32000)),0,10);
                        $this->setRequestHeader('Content-Type', 'multipart/form-data; boundary='.$boundary);
                        $postdata .= '--' . $boundary;
                        while($m = $data->shift()) {
                            $postdata .= PHPHttpRequest::HTTP_EOL;
                            if(is_a($m['value'], 'File')) {
                                $file = $m['value'];
                                $filename = isset($m['filename'])? $m['filename'] : $file->filename;
                                $postdata .= 'Content-Disposition: form-data; name="' . $m['name'] . '"; filename="' . $filename . '"' . PHPHttpRequest::HTTP_EOL;
                                $postdata .= 'Content-Type: ' . $file->mimetype . PHPHttpRequest::HTTP_EOL;
                                $postdata .= PHPHttpRequest::HTTP_EOL;
                                $postdata .= $file->readAsString() . PHPHttpRequest::HTTP_EOL;
                                $postdata .= '--' . $boundary;
                            }else {
                                $postdata .= 'Content-Disposition: form-data; name="'.$m['name'].'"' . PHPHttpRequest::HTTP_EOL.PHPHttpRequest::HTTP_EOL;
                                $postdata .= $m['value'] . PHPHttpRequest::HTTP_EOL;
                                $postdata .= '--' . $boundary;
                            }
                        }
                        $postdata .= '--' . PHPHttpRequest::HTTP_EOL;
                    }else {
                        $this->setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        while($m = $data->shift()) {
                            $postdata .= rawurlencode($m['name']) . '=' . rawurlencode($m['value']) . '&';
                        }
                        $postdata = substr($postdata, 0, -1);
                    }
                }else if(is_a($data, 'File')) {
                    $this->setRequestHeader('Content-Type', $data->mimetype);
                    $postdata .= $data->readAsString();
                }else {
                    $postdata .= $data;
                }
                $this->setRequestHeader('Content-Length', strlen($postdata));
            }
            return $this->sendRequest($postdata);
        }
        return false;
    }
}



/**
 * HttpResponse
 *
 * Parse the response of HTTP request
 *
 * @package     PHPHttpRequest
 * @subpackage  PHPHttpResponse
 * @author      qakcn <qakcnyn@gmail.com>
 * @copyright   2015 qakcn
 * @version     0.1
 * @license     http://mozilla.org/MPL/2.0/
 * @link        https://github.com/qakcn/PHPHttpRequest
 */

class PHPHttpResponse {

    private $status;
    private $data;
    private $headers = array();
    private $cookies = array();


    /**
     * parse "Set-Cookie" header
     * 
     * @param string #cookiestr "Set-Cookie" value
     * @access private
     */
    private function parseCookie($cookiestr) {
        $c=array();
        $options = explode(';', trim($cookiestr));
        foreach($options as $o) {
            if(trim($o) == '') {
                continue;
            }else if(strtolower(trim($o)) == 'secure') {
                $c['secure'] = true;
            }else if(strtolower(trim($o)) == 'httponly') {
                $c['httponly'] = true;
            }else {
                list($on, $ov) = explode('=', trim($o));
                if(strtolower($on) == 'expires') {
                    $time=DateTime::createFromFormat('D, d-M-Y H:i:s e', trim($ov));
                    $c['expires']=$time->getTimestamp();
                }else if(in_array(strtolower($on), array('path', 'domain'))) {
                    $c[strtolower($on)] = trim($ov);
                }else {
                    $c['name'] = trim($on);
                    $c['value'] = trim($ov);
                }
            }
        }
        array_push($this->cookies, $c);
    }


    /**
     * constructor, parse HTTP headers and body
     * 
     * @param string $res response data
     * @access public
     */
    public function __construct($res) {
        $pos = strpos($res, "\r\n\r\n");
        $headers = substr($res, 0, $pos);

        $headers = explode("\r\n", $headers);
        foreach($headers as $h) {
            if(preg_match('/^HTTP\/.+ ([1-5][0-9]{2}) .*$/', $h, $match)) {
                $this->status = $match[1];
            }else {
                list($hn, $hv) = explode(':', $h, 2);
                if(strtolower(trim($hn)) == 'set-cookie') {
                    $this->parseCookie($hv);
                }else {
                    array_push($this->headers, array('name'=>trim($hn), 'value' => trim($hv)));
                }
            }
        }

        $transencode = $this->getHeader('Transfer-Encoding');
        if(strtolower($transencode[0]['value']) == 'chunked') {
            $data = substr($res, $pos+4);
            list($shiftdata, $data) = explode("\r\n", $data, 2);
            $this->data = '';
            $chunk_size = (integer)hexdec($shiftdata);
            while($chunk_size > 0) {
                $this->data .= substr($data, 0, $chunk_size);
                $data = substr($data, $chunk_size+2);
                list($shiftdata, $data) = explode("\r\n", $data, 2);
                $chunk_size = (integer)hexdec($shiftdata);
            }
        }else {
            $this->data = substr($res, $pos+4);
        }
    }


    /**
     * get headers of response
     * 
     * @param string $name header name, empty for all headers
     * @return array/boolean false for no match
     * @access public
     */
    public function getHeader($name='') {
        if(empty($name)) {
            return $this->headers;
        }
        $res = array();
        foreach($this->headers as $h) {
            if(strtolower($name) == strtolower($h['name'])) {
                array_push($res, $h);
            }
        }
        if(count($res)==0){
            return false;
        }else {
            return $res;
        }
    }


    /**
     * get cookies of response
     * 
     * @param string $name cookie name, empty for all cookies
     * @return array/boolean false for no match
     * @access public
     */
    public function getCookie($name='') {
        if(empty($name)) {
            return $this->cookies;
        }
        $res = array();
        foreach($this->cookies as $c) {
            if(strtolower($name) == strtolower($c['name'])) {
                array_push($res, $c);
            }
        }
        if(count($res)==0){
            return false;
        }else {
            return $res;
        }
    }


    /**
     * getter, called when tring to get value of private properties
     * 
     * @param string $name private property name, should be one of $valid
     * @return mixed value of the property
     * @access public
     */
    public function __get($name) {
        $valid = array('status', 'data');
        if(in_array($name, $valid)) {
            return $this->$name;
        }
    }
}



/**
 * File API
 *
 * @package     PHPHttpRequest
 * @subpackage  File
 * @author      qakcn <qakcnyn@gmail.com>
 * @copyright   2015 qakcn
 * @version     0.1
 * @license     http://mozilla.org/MPL/2.0/
 * @link        https://github.com/qakcn/PHPHttpRequest
 */

class File {

    private $filepath;
    private $mimetype;
    private $filesize;
    private $filename;


    /**
     * get MIME type of file, store in $mimetype
     * 
     * @return boolean true if successfully get
     * @access private
     */
    private function getMimeType() {
        if($finfo = finfo_open(FILEINFO_MIME_TYPE)) {
            $this->mimetype = finfo_file($finfo, $this->filepath);
            finfo_close($finfo);
            return true;
        }
        return false;
    }


    /**
     * get size of file, store in $filesize
     * 
     * @return boolean true if successfully get
     * @access private
     */
    private function getFileSize() {
        if(false !== ($filesize = filesize($this->filepath))) {
            $this->filesize = $filesize;
            return true;
        }
        return false;
    }


    /**
     * constructor
     * 
     * @param string $filepath the path of a file
     * @return boolean false if $filepath is empty or is not exist or is not a file
     * @access public
     */
    public function __construct($filepath) {
        if(!empty($filepath) && file_exists($filepath) & is_file($filepath)) {
            $this->filepath = $filepath;
            $this->getMimeType();
            $this->getFileSize();
            $this->filename = basename($filepath);
            return true;
        }
        return false;
    }


    /**
     * getter, called when tring to get value of private properties
     * 
     * @param string $name private property name, should be one of $valid
     * @return mixed value of the property
     * @access public
     */
    public function __get($name) {
        $name = strtolower($name);
        $valid = array('mimetype', 'filesize', 'filename', 'filepath');
        if(in_array($name, $valid)) {
            return $this->$name;
        }
        return false;
    }


    /**
     * get file content as string
     * 
     * @param integer $offset start position of a file in byte, defalut -1
     * @param integer $maxlen max length of content in byte, default NULL
     * @return string content of the file
     * @access public
     */
    public function readAsString($offset = -1, $maxlen = null) {
        if(!is_int($offset) || $offset < -1) {
            $offset = -1;
        }
        if(is_int($maxlen) && $maxlen > 0) {
            return file_get_contents($this->filepath, false, null, $offset, $maxlen);
        }else {
            return file_get_contents($this->filepath, false, null, $offset);
        }
    }


    /**
     * get file content as data URI string
     * 
     * @return string base64 encoded data URI
     * @access public
     */
    public function readAsDataURI() {
        return 'data:' . $this->mimetype . ';base64,' . base64_encode( $this->readAsString() );
    }
}



/**
 * FormData API
 *
 * Simulate HTML Form
 *
 * @package     PHPHttpRequest
 * @subpackage  FormData
 * @author      qakcn <qakcnyn@gmail.com>
 * @copyright   2015 qakcn
 * @version     0.1
 * @license     http://mozilla.org/MPL/2.0/
 * @link        https://github.com/qakcn/PHPHttpRequest
 */

class FormData {
    private $member = array();
    private $member_s = array();
    private $member_p = array();
    private $hasFile = false;
    private $multipart = false;


    /**
     * append form member
     * 
     * @param string $name "name" attribute
     * @param string/File $value "value" attribute or File instance
     * @param string $filename override File instance filename
     * @return boolean false if $name is empty
     * @access public
     */
    public function append($name, $value, $filename = null) {
        if(empty($name)) {
            return false;
        }
        $m['name'] = $name;
        if(is_a($value, 'File')) {
            if(!empty((string)$filename)) {
                $m['filename'] = (string)$filename;
            }
            $m['value'] = $value;
            $this->hasFile = true;
        }else {
            $m['value'] = (string)$value;
        }
        array_push($this->member, $m);
        return true;
    }


    /**
     * getter, called when tring to get value of private properties
     * 
     * @param string $name private property name, should be one of $valid
     * @return mixed value of the property
     * @access public
     */
    public function __get($name) {
        $valid = array('hasFile', 'multipart');
        if(in_array($name, $valid)) {
            return $this->$name;
        }
        return false;
    }


    /**
     * setter, called when tring to set value of private properties
     * 
     * @param string $name private property name, only 'multipart' allowed
     * @param mixed $value value of the property, only boolean allowed for 'multipart'
     * @access public
     */
    public function __set($name, $value) {
        if($name=='multipart') {
            $this->$name = (bool)$value;
        }
    }


    /**
     * get first member of FormData and move it to buffer
     * 
     * @return array first member of FormData
     * @access public
     */
    public function shift() {
        if($m = array_shift($this->member)) {
            array_push($this->member_s, $m);
            return $m;
        }
        return false;
    }


    /**
     * get last member of FormData and move it to buffer
     * 
     * @return array last member of FormData
     * @access public
     */
    public function pop() {
        if($m = array_pop($this->member)) {
            array_unshift($this->member_p, $m);
            return $m;
        }
        return false;
    }


    /**
     * reset the members as never have used shift() or pop()
     * 
     * @access public
     */
    public function reset() {
        $this->member = array_merge($this->member_s, $this->member, $this->member_p);
        $this->member_s = $this->member_p = array();
    }
}
