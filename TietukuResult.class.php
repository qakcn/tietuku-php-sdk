<?php

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

//namespace tietuku-php-sdk;    //取消注释使用命名空间来避免冲突。

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
