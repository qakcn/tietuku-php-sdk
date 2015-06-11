<?php
    if(php_sapi_name()!='cli') {
        die('This can only run in command line. Open terminal or command prompt and run "php build.php".');
    }
?>
Tietuku SDK Builder
version 0.1

Deleting old file.<?php

file_exists('release/tietuku-php-sdk.php') && unlink('release/tietuku-php-sdk.php');
file_exists('release/tietuku-php-sdk.php') && unlink('release/tietuku_sdk.php');

?>............Finished.
Read file.<?php

($main = file_get_contents('Tietuku.class.php')) || die('....................Read failed.');
($result = file_get_contents('TietukuResult.class.php')) || die('....................Read failed.');
($phr = file_get_contents('PHPHttpRequest.class.php')) || die('....................Read failed.');
($comp = file_get_contents('TietukuCompatible.class.php')) || die('....................Read failed.');

function cleanfile($file) {
    return str_replace(array('<?php','//namespace tietuku-php-sdk;    //取消注释使用命名空间来避免冲突。'), '', $file);
}

$result = cleanfile($result);
$phr = cleanfile($phr);

?>....................Finished.
Building SDK file.<?php

$sep = <<<SEP




/******************************************************************************
 ******************************************************************************
 ******************************************************************************
 ***************以下为新版SDK，如果仅使用旧版，则无须继续查看******************
 ******************************************************************************
 ******************************************************************************
 ***********************请不要对以下代码做任何修改！***************************
 ******************************************************************************
 ******************************************************************************
 ******************************************************************************/




SEP;


    file_put_contents('release/tietuku-php-sdk.php', $main . $result . $phr) &&
    file_put_contents('release/tietuku_sdk.php', $comp . $sep . cleanfile($main) . $result . $phr) ||
    die('............Build failed.');

?>............Finished.
Build complete!

