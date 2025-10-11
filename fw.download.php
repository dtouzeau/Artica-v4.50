<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
if(!isset($_GET["fname"])){die();}
$fname=base64_decode($_GET["fname"]);
$fname=str_replace("../","",$fname);
$fname=str_replace("..","",$fname);
$fname=str_replace("./","",$fname);
$basename=basename($fname);



$tfile="/usr/share/artica-postfix/$fname";
if(!is_file($tfile)){die();}
$type=mime_content_type($tfile);
$fsize=@filesize($tfile);
$timestamp =filemtime($tfile);
$etag = md5($tfile . $timestamp);


$tsstring = gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
header("Content-Length: ".$fsize);
header('Content-type: '.$type);
header('Content-Transfer-Encoding: binary');
header("Content-Disposition: attachment; filename=\"$basename\"");
header("Cache-Control: no-cache, must-revalidate");
header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', $timestamp + (60 * 60)));
header("Last-Modified: $tsstring");
header("ETag: \"{$etag}\"");
header("Content-Length: ".$fsize);
ob_clean();
flush();
readfile($tfile);



if(!function_exists('mime_content_type')) {

    function mime_content_type($filename) {

        $mime_types = array(
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $exploded=explode('.',$filename);
        $ext = strtolower(array_pop($exploded));
        if (array_key_exists($ext, $mime_types)) {
        return $mime_types[$ext];
        }
        elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        }
        else {
            return 'application/octet-stream';
        }
    }
}