<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");


// Pentest 2026-11-04 : md5 etait recopie sans encodage dans la page (XSS).
if(isset($_GET["md5"])){$_GET["md5"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["md5"]);}
if(isset($_GET["download-content"])){content_download();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_POST["iscontent"])){content_save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded_js();exit;}
if(isset($_GET["delete-content"])){content_remove();exit;}
rule_js();
function suffix_RQ():string{
    $directory_id=intval($_GET["directory_id"]);
    $serviceid=intval($_GET["serviceid"]);
    $md5=$_GET["md5"];
    $f[]="directory_id=$directory_id";
    $f[]="serviceid=$serviceid";
    $f[]="md5=$md5";
    if(isset($_GET["refreshjs"])){
        $f[]="refreshjs=".urlencode($_GET["refreshjs"]);
    }

    return @implode("&",$f);
}
function rule_js():bool{

    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $suffix=suffix_RQ();
    $directory_id=intval($_GET["directory_id"]);
    $serviceid=intval($_GET["serviceid"]);
    $dir=get_directoryname($directory_id);
    $page=CurrentPageName();
    return $tpl->js_dialog4("{content}: $dir","$page?popup-main=$serviceid&$suffix");
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}


function get_directoryname($ID):string{
    $ligne=fetch_ngx_directory_row($ID);
    return isset($ligne["directory"]) ? strval($ligne["directory"]) : "";
}
function content_remove(){
    // Was silently broken:
    //   1. set iscontent="" — empty string is not a valid INTEGER value.
    //      SQLite stored it as text; the Go daemon then failed to Scan
    //      iscontent into int, which aborts the whole GetDirectories loop
    //      at that row (subsequent directories vanish from the build).
    //   2. filecontent was never cleared, so the popup re-rendered with the
    //      same byte length and delete icon — visually nothing changed,
    //      hence "no error but doesn't work".
    //   3. content_save guards against directory_id=0 (silent zero-row
    //      UPDATE returns Status=true); content_remove didn't.
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $serviceid=intval($_GET["serviceid"]);
    $suffix=suffix_RQ();
    $directory_id=intval($_POST["directory_id"] ?? $_GET["directory_id"] ?? 0);
    if($directory_id<1){
        return $tpl->js_error("content_remove: directory_id missing");
    }
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec",
        array("action"=>"update","table"=>"ngx_directories",
            "set"=>array("iscontent"=>"0","filecontent"=>""),
            "where"=>array("ID"=>"$directory_id")));

    $data=json_decode($resp,true);
    if(!is_array($data) || empty($data["Status"])){
        // Surface daemon errors via BOTH js_error popup AND console — Loadjs
        // sometimes swallows js_error responses in delete-icon callbacks,
        // leaving the operator with no signal that anything failed.
        $val= (is_array($data) && isset($data["Error"]) && strlen($data["Error"])>0
                ? $data["Error"]
                : (is_array($data) && isset($data["error"]) ? $data["error"] : "ngx_directories update failed"));
        header("content-type: application/x-javascript");
        $jsval=addslashes($val);
        echo "console.error('content_remove: $jsval');".$tpl->js_error($val);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "if(!document.getElementById('main-dircontent-$serviceid-$directory_id')){";
    echo "alert('main-dircontent-$serviceid-$directory_id no such div');";
    echo "}";
    echo "LoadAjaxSilent('main-dircontent-$serviceid-$directory_id','$page?popup-table=yes&$suffix')";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");

    $dirname=get_directoryname($directory_id);
    $serv=get_servicename($serviceid);
    return admin_tracks("Remove content of directory $dirname for reverse-proxy service $serv");
}

function content_save():bool{
    // BigCircleCheckbox JS posts to CurrentPageName() with NO query string,
    // so directory_id arrives in $_POST, not $_GET. Reading $_GET here
    // silently produced directory_id=0 → WHERE ID=0 matched zero rows →
    // daemon returned Status=true (UPDATE succeeded affecting 0 rows) →
    // PHP thought the save worked but nothing changed.
    $directory_id=intval($_POST["directory_id"] ?? $_GET["directory_id"] ?? 0);
    $iscontent=intval($_POST["iscontent"]);
    if($directory_id<1){
        echo "content_save: directory_id missing (POST/GET both empty)\n";
        return false;
    }
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec",
        array("action"=>"update","table"=>"ngx_directories",
            "set"=>array("iscontent"=>"$iscontent"),"where"=>array("ID"=>"$directory_id")));

    $data=json_decode($resp,true);
    if(!is_array($data) || empty($data["Status"])){
        echo (is_array($data) && isset($data["error"]) ? $data["error"] : "ngx_directories update failed")."\n";
        return false;
    }
    $serviceid  = intval($_GET["serviceid"]);

    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    $serv=get_servicename($serviceid);
    $dir=get_directoryname($directory_id);
    return admin_tracks("Set $dir to a content enabled=$iscontent for reverse-proxy service $serv");
}


function service_js():bool{
    $suffix = suffix_RQ();
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    return $tpl->js_dialog4("{content}","$page?popup-main=yes&$suffix");
}

function popup_main():bool{
    $directory_id=intval($_GET["directory_id"]);
    $serviceid  = intval($_GET["serviceid"]);
    $page       = CurrentPageName();
    $suffix=suffix_RQ();
    $js="LoadAjax('main-dircontent-$serviceid-$directory_id','$page?popup-table=yes&$suffix')";
    $jsenc=base64_encode($js);

    echo "<div id='main-dircontent-$serviceid-$directory_id'></div>
    <script>LoadAjaxSilent('main-dircontent-$serviceid-$directory_id','$page?popup-table=yes&$suffix&refreshjs=$jsenc')</script>";
    return true;
}
function file_uploaded_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    if(strlen($filename)<2){
        return $tpl->js_error("No file given");
    }
    $suffix=suffix_RQ();
    $function=$_GET["function"];
    $filename=urlencode($filename);
    $serviceid  = intval($_GET["serviceid"]);
    $directory_id=intval($_GET["directory_id"]);
    VERBOSE("/directory/import/$directory_id/$filename",__LINE__);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/directory/import/$directory_id/$filename"),true);
    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["Status"]){
        // Pentest 2026-11-04 : file-uploaded pouvait revenir sans encodage dans la page (XSS) via le message d'erreur.
        return $tpl->js_error(req_html($json["Error"]));
    }
    // Storing content implies it must be served: auto-enable iscontent=1.
    // The daemon import (/directory/import) writes ONLY filecontent, so without
    // this the row keeps iscontent=0 → Locations.Build proxies the path to the
    // backend instead of aliasing the stored file → the just-uploaded content
    // is never served (backend 404 / "Please wait" fallback, e.g.
    // /inpi/js/default.js). Symmetric with content_remove() (iscontent=0 on
    // delete). The BigCircleCheckbox still lets the operator toggle it off.
    if($directory_id>0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec",
            array("action"=>"update","table"=>"ngx_directories",
                "set"=>array("iscontent"=>"1"),"where"=>array("ID"=>"$directory_id")));
    }
    $dirname=get_directoryname($directory_id);
    if(strlen($function)>1){
        echo "$function();";
    }
    echo "LoadAjaxSilent('main-dircontent-$serviceid-$directory_id','$page?popup-table=yes&$suffix');";
    $serv=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Uploaded {$_GET["file-uploaded"]} reverse-proxy rule $serv directory $dirname");

}

function popup_table():bool{
    $suffix=suffix_RQ();
    $serviceid  = intval($_GET["serviceid"]);
    $directory_id=intval($_GET["directory_id"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();


    $ligne=fetch_ngx_directory($directory_id);
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->BigCircleCheckbox("directory_id:$directory_id|serviceid:$serviceid|iscontent",
        "{display_content}","{nginx_path_tofile}",$ligne["iscontent"],
    "LoadAjax('main-dircontent-$serviceid-$directory_id','$page?popup-table=yes&$suffix')");

    $html[]="<div style='margin:30px' class='center'>";
    $html[]=$tpl->button_upload("{upload_a_file}",$page,null,"&$suffix");
    $html[]="</div>";
    $length=strlen($ligne["filecontent"]);
    $html[]="<!-- filecontent len == $length bytes -->";

    if($length>10){
        $iconDelete=$tpl->icon_delete("Loadjs('$page?delete-content=$directory_id&$suffix')");
        $iconDownload=$tpl->icon_download("document.location.href='$page?download-content=$directory_id&$suffix'");
        $lentext="$length bytes";
        if($length>1024){
            $lentext=FormatBytes($length/1024);
        }
        $html[]="<div style='margin:10px;font-size:22px' class='center'>$lentext&nbsp;&nbsp;$iconDownload&nbsp;&nbsp;$iconDelete</div>";
        if(preg_match("#\.(svg|png|jpeg|jpg|gif|bmp)$#i",$ligne["directory"],$re)){
            $img=gzipBase64ToImageDataUrlAutoMime($ligne["filecontent"]);
            $html[]="<div style='margin:10px' class='center'>";
            $html[]="<img src=\"$img\">";
            $html[]="</div>";
        }
    }else{
        $html[]=$tpl->div_info("{content}||{no_data}");
    }


    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function content_download(){
    $directory_id=$_GET["directory_id"];
    $ligne=fetch_ngx_directory($directory_id);
    $content=base64_decode($ligne["filecontent"]);
    $dirname=get_directoryname($directory_id);
    $basename=basename($dirname);
    $fsize=strlen($content);
    header("Content-Length: ".$fsize);
    header('Content-type: application/gzip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$basename.gz\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $content;

}


function gzipBase64ToImageDataUrlAutoMime(string $gzipBase64): string
{
    // Decode Base64
    $compressedData = base64_decode($gzipBase64, true);
    if ($compressedData === false) {
        return 'Invalid Base64 data';
    }

    // Decompress gzip
    $rawImageData = gzdecode($compressedData);
    if ($rawImageData === false) {
        return 'Invalid gzip data';
    }

    // Auto-detect MIME type from raw image bytes
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($rawImageData);

    if ($mimeType === false || strpos($mimeType, 'image/') !== 0) {
        return 'Decoded content is not a valid image';
    }

    // Encode raw image bytes again as Base64
    $imageBase64 = base64_encode($rawImageData);

    return 'data:' . $mimeType . ';base64,' . $imageBase64;
}