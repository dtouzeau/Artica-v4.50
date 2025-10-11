<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}


js();

function file_uploaded(){
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/articaweb/chown");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $file=$_GET["file-uploaded"];
    $id=$_GET["id"];
    $id2=$_GET["id2"];
    $path="/usr/share/artica-postfix/ressources/conf/upload/$file";
    list($width, $height, $type, $attr) = getimagesize($path);
    $image_mime = image_type_to_mime_type($type);
    if($image_mime<>"image/jpeg"){
        @unlink($path);
        $tpl->js_error_stop("<strong style=font-size:22px>{corrupted}<hr> ONLY JPEG Accepted</strong>");
        return false;
    }
    $target_file=$file;
    $ext=null;
    if(preg_match("#^(.+?)\.([a-zA-Z]+)$",$file,$re)){
        $target_file=$re[1];
        $ext=$re[2];
    }

    $t=time();
    if(!preg_match("#{$width}x{$height}-#",$file)){
        $target_file="{$width}x{$height}-$target_file";
    }
    $target_file=$target_file.".$ext";
    $nextpath="/usr/share/artica-postfix/img/$target_file";
    if(is_file($nextpath)){@unlink($nextpath);}

    if( !copy($path,$nextpath)){
        $tpl->js_error_stop("Copy failed");
        return false;
    }

    @unlink($path);
    header("content-type: application/x-javascript");

    echo "
    function Final$t(){
        if(!document.getElementById('$id')){
                alert('Missing field id $id !');
                return false;
        }
        document.getElementById('$id').value='/img/$target_file';
        document.getElementById('$id2').value='$target_file';
        dialogInstance8.close();
    }
	Final$t();
	";


}


function js(){
    $id=$_GET["id"];
    $id2=$_GET["id2"];
    $path=$_GET["path"];
    $basename=basename($path);
    $encoded_string=urlencode($path);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog8("{picture} $basename","$page?popup=yes&id=$id&id2=$id2&path=$encoded_string",600);
}

function popup(){
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/articaweb/chown");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["id"];
    $id2=$_GET["id2"];
    $path=$_GET["path"];

    $path="/usr/share/artica-postfix/$path";
    $path=str_replace("//","/",$path);
    $basename=basename($path);
    $tmpapth=PROGRESS_DIR."/thumb-$basename";
    //$path=urlencode($path);

    list($width, $height, $type, $attr) = getimagesize($path);
    $image_mime = image_type_to_mime_type($type);
    $basename=basename($path);

    if(!is_file($tmpapth)){
        if(resizeImage($path,$tmpapth,250,250)){
            $tmpOut="<img src='ressources/logs/web/thumb-$basename'>";
        }
    }else{
        $tmpOut="<img src='ressources/logs/web/thumb-$basename'>";
    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td width='250px' valign='top'>";
    if(resizeImage($path,$tmpapth,250,250)){
        $html[]="<div style='margin-bottom: 20px'>$tmpOut</div>";
    }
    $html[]="<center><strong>$basename<br><small>{$width}px x {$height}px $image_mime</small></strong></center>";
    $html[]="</td>";
    $html[]="<td width='250px' valign='top'>";
    $html[]="<center>";
    $html[]=$tpl->button_upload("{upload} {picture} JPEG",$page,null,"&id=$id&id2=$id2");
    $html[]="</center>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);
}

function resizeImage($sourceImage, $targetImage, $maxWidth, $maxHeight, $quality = 80){
    if (!$image = @imagecreatefromjpeg($sourceImage)) {return false;}
    list($origWidth, $origHeight) = getimagesize($sourceImage);

    if ($maxWidth == 0) {$maxWidth  = $origWidth;}
    if ($maxHeight == 0) {$maxHeight = $origHeight;}
    $widthRatio = $maxWidth / $origWidth;
    $heightRatio = $maxHeight / $origHeight;
    $ratio = min($widthRatio, $heightRatio);
    $newWidth  = (int)$origWidth  * $ratio;
    $newHeight = (int)$origHeight * $ratio;
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
    imagejpeg($newImage, $targetImage, $quality);
    imagedestroy($image);
    imagedestroy($newImage);
    return true;
}