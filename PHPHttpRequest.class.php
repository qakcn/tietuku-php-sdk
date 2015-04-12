<?php

/**
 * PHPHttpRequest
 *
 * 用于发送HTTP请求的类。包含四个类：PHPHttpRequest、PHPHttpResponse、File和FormData。
 *
 */

//namespace tietuku-php-sdk;    //取消注释使用命名空间来避免冲突。

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
                        $data->reset();
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
