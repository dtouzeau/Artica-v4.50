<?php
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__).'/ressources/class.template-admin.inc');
include_once(dirname(__FILE__).'/ressources/class.progress.sign.inc');
{if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["build-main"])){build_main();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["morris"])){morris();exit;}
if(isset($_GET["morris-failed"])){morris_failed();exit;}
if(isset($_GET["morris-logs"])){morris_logs();exit;}
if(isset($_GET["morris-zoom"])){morris_zoom();exit;}
build_modal();

// Task links signed by fw_progress_args() (class.progress.sign.inc): every step checks
// the signature BEFORE decoding and passes it on to the next one. Returns null (after
// answering the refusal) when the link is unsigned, altered or from another session.
# v10/10/2026
function lock_read_signed($param){
    $b64=fw_progress_b64_normalize(isset($_GET[$param])?$_GET[$param]:"");
    $sig=isset($_GET["sig"])?strtolower(trim((string)$_GET["sig"])):"";
    if($b64==="" || !fw_progress_verify($b64,$sig,"task")){
        fw_progress_refuse($param);
        return null;
    }
    $ARRAY=fw_progress_decode($b64);
    if(!isset($ARRAY["CMD"])){$ARRAY["CMD"]=null;}
    if(!isset($ARRAY["TITLE"])){$ARRAY["TITLE"]=null;}
    if(!isset($ARRAY["AFTER"])){$ARRAY["AFTER"]=null;}
    if(!isset($ARRAY["PROGRESS_FILE"])){$ARRAY["PROGRESS_FILE"]=null;}
    if(!isset($ARRAY["LOG_FILE"])){$ARRAY["LOG_FILE"]=null;}
    $GLOBALS["LOCK_SIGNED"]=urlencode($b64)."&sig=$sig";
    return $ARRAY;
}

// Title carried from step to step in the URL: text only.
function lock_lasttitle(){
    if(!isset($_GET["lasttitle"])){return "";}
    $b64=fw_progress_b64_normalize($_GET["lasttitle"]);
    return base64_encode(strip_tags((string)base64_decode($b64)));
}


function build_modal(){
    $page      = CurrentPageName();
    $tpl       = new template_admin();
    $t         = time();
    // This step runs the REST route: Loadjs() only (X-Requested-With), signed link only.
    if(!isset($_SERVER["HTTP_X_REQUESTED_WITH"]) || strtolower(trim((string)$_SERVER["HTTP_X_REQUESTED_WITH"]))!=="xmlhttprequest"){
        return fw_progress_refuse("build_modal without X-Requested-With");
    }
    $ARRAY     = lock_read_signed("content");
    if($ARRAY===null){return false;}
    $TITLE     = $ARRAY["TITLE"];
    $CMD       = $ARRAY["CMD"];
    $contente  = $GLOBALS["LOCK_SIGNED"];
    if(isset($ARRAY["PRIV"]) && !fw_progress_priv_allowed($ARRAY["PRIV"])){
        error_log("Artica progress: Web console: task refused, privilege missing");
        return $tpl->js_error("{ERROR_NO_PRIVS2}");
    }
    if(!fw_progress_cmd_safe($CMD)){return fw_progress_refuse("build_modal: route outside its prefix");}

    writelogs("$CMD",__FUNCTION__,__FILE__,__LINE__);

    if (substr($CMD, 0, 1) === '/') {
        writelogs("REST_API($CMD)",__FUNCTION__,__FILE__,__LINE__);
        $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($CMD);
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return $tpl->js_error("Decoding data".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}");
        }
        if(!$json->Status){
            writelogs("REST_API($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return $tpl->js_error("Status false<br>$data<br>".$json->Info);
        }

    } else {
        writelogs("getFrameWork($CMD)",__FUNCTION__,__FILE__,__LINE__);
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork($CMD);
    }


    $GLOBALS["CLASS_SOCKETS"]->getFrameWork($CMD);

    $TITLE=$tpl->_ENGINE_parse_body($TITLE);
    $lasttitle=base64_encode($TITLE);
    admin_tracks("Launch task $TITLE ($CMD)");

    $tpl=new template_admin();
    $tpl->js_dialog_modal($TITLE,"$page?build-main=$t&content=$contente&lasttitle=$lasttitle");
}

function build_main(){
    $tt         = time();
    $t          = 0;
    if(isset($_GET["start"])) {
        $t = $_GET["start"];
    }
    if(lock_read_signed("content")===null){return;}
    $contente   = $GLOBALS["LOCK_SIGNED"];
    $page       = CurrentPageName();
    $lasttitle  = lock_lasttitle();
    $t          = fw_progress_safe_id($t);
    echo "<center style='margin-top: 25px'><div id='install-$t'></div></center>";
    echo "<script>LoadAjaxSilent('install-$t','$page?morris=$t&content=$contente&md5=&tt=$tt&lasttitle=$lasttitle')</script>";

}

function start(){
    $t          = fw_progress_safe_id($_GET["start"]);
    if(lock_read_signed("content")===null){return;}
    $tt         = time();
    $contente   = $GLOBALS["LOCK_SIGNED"];
    $page       = CurrentPageName();
    $jsa[]="function JmorrisWaitStart(){";
    $jsa[]="\tLoadjs('$page?morris=$t&content=$contente&md5=&tt=$tt');";
    $jsa[]="}";
    $jsa[]="setTimeout(\"JmorrisWaitStart()\",1000);";
    header("content-type: application/x-javascript");
    echo @implode("\n",$jsa);


}

function morris(){
    $lastprc        = 0;
    $t              = fw_progress_safe_id($_GET["morris"]);
    $tt             = time();
    if(isset($_GET["lastprc"])) {
        $lastprc = intval($_GET["lastprc"]);
    }
    $ARRAY          = lock_read_signed("content");
    if($ARRAY===null){return;}
    $contente       = $GLOBALS["LOCK_SIGNED"];
    $page           = CurrentPageName();
    $PROGRESS_FILE  = $ARRAY["PROGRESS_FILE"];
    $LOG_FILE       = $ARRAY["LOG_FILE"];
    $array          = @unserialize((string)@file_get_contents($PROGRESS_FILE),array("allowed_classes"=>false));
    if(!is_array($array)){$array=array();}
    if(!isset($array["POURC"])){$array["POURC"]=15;}
    if(!isset($array["TEXT"])){$array["TEXT"]=null;}
    $prc            = intval($array["POURC"]);
    $tpl            = new template_admin();
    $AFTER          = $ARRAY["AFTER"];

    if($array["TEXT"]==null){
        if(isset($_GET["lasttitle"])){
            $array["TEXT"]=base64_decode(lock_lasttitle());
        }
    }
    if($array["TEXT"]==null){$array["TEXT"]="{please_wait}";}
    $TITLE=$tpl->_ENGINE_parse_body($array["TEXT"]);
    $lasttitle      = base64_encode($TITLE);
    if($prc==0){
        if($lastprc>0){
            $prc=$lastprc;
        }
    }

    if($prc<100) {
        $jsa[] = $tpl->widget_vert($TITLE,"$prc%","ico:fas fa-compact-disc,far fa-compact-disc,fad fa-compact-disc,fal fa-compact-disc");
        $jsa[] = "<script>";
        $jsa[] = "function JmorrisWaitContinue(){";
        $jsa[] = "\tLoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
        $jsa[]= "\tLoadAjaxSilent('install-$t','$page?morris=$t&content=$contente&md5=&tt=$tt&lastprc=$prc&lasttitle=$lasttitle')";
        $jsa[] = "}";
        $jsa[] = "setTimeout(\"JmorrisWaitContinue()\",1000);\n";
        $jsa[] = "</script>";
        echo $tpl->_ENGINE_parse_body($jsa);
    }

    if($prc==100) {
        $jsa[] =$tpl->widget_vert($TITLE,"{success}");
        @unlink($PROGRESS_FILE);
        @unlink($LOG_FILE);
        $jsa[] = "<script>";
        $jsa[] = "function JmorrisWaitContinue(){";
        $jsa[] = "\tDialogModal.close();";
        $jsa[] = "\t$AFTER;";
        $jsa[] = "}";
        $jsa[] = "setTimeout(\"JmorrisWaitContinue()\",1000);";
        $jsa[] = "</script>";
        echo $tpl->_ENGINE_parse_body($jsa);
    }

    if($prc>100) {
        $jsa[] = "<script>";
        $jsa[] = "\tDialogModal.close();";
        $jsa[]="\tLoadjs('$page?morris-failed=$t&content=$contente&md5=&tt=$tt');";
        $jsa[] = "</script>";
        echo $tpl->_ENGINE_parse_body($jsa);
    }

    echo "<!--  Cache file = $PROGRESS_FILE  -->\n";
	echo "<!--  Log file = $LOG_FILE  -->\n";
    echo "<!--  prc = $prc  -->\n";



 }


 function morris_failed(){

     $page           = CurrentPageName();
     $tpl            = new template_admin();
     $ARRAY          = lock_read_signed("content");
     if($ARRAY===null){return;}
     if(!isset($ARRAY["TEXT"])){$ARRAY["TEXT"]=null;}
     $contente       = $GLOBALS["LOCK_SIGNED"];

     $TITLE2=$tpl->_ENGINE_parse_body($ARRAY["TEXT"]);
     $TITLE=$tpl->_ENGINE_parse_body("{operation_failed}");
     $TITLE=$tpl->_ENGINE_parse_body($TITLE);

     $TITLE=str_replace("'","\'",$TITLE);
     $TITLE2=str_replace("'","\'",$TITLE2);
     admin_tracks("Launch task $TITLE2 failed");

     $jsa[]="$.alert({";
     $jsa[]="theme: 'red-theme',";
     $jsa[]="containerFluid: true,";
     $jsa[]="title: '$TITLE',";
     $jsa[]="content: '$TITLE2',";
     $jsa[]="icon: 'fas fa-exclamation',";
     $jsa[]="buttons: {
         tryAgain: {
             text: 'Logs',
             btnClass: 'btn-red',
             action: function(){
                var self = this;
                this.close();
                Loadjs('$page?morris-logs=$contente');
            }
        } }";
     $jsa[]="});";
     echo @implode("\n",$jsa);

 }

 function morris_logs(){
     if(lock_read_signed("morris-logs")===null){return;}
     $contente       = $GLOBALS["LOCK_SIGNED"];
     $page           = CurrentPageName();
     $tpl            = new template_admin();
     $tpl->js_dialog6("{nmap_logs_text}","$page?morris-zoom=$contente","950");

 }

 function morris_zoom(){
     $tpl               = new template_admin();
     $ARRAY             = lock_read_signed("morris-zoom");
     if($ARRAY===null){return;}
     $TITLE             = $ARRAY["TITLE"];
     $PROGRESS_FILE     = $ARRAY["PROGRESS_FILE"];
     $LOG_FILE          = $ARRAY["LOG_FILE"];
     $array             = @unserialize((string)@file_get_contents($PROGRESS_FILE),array("allowed_classes"=>false));
     if(!is_array($array)){$array=array();}
     if(!isset($array["TEXT"])){$array["TEXT"]="";}

     $html[]="<H1>$TITLE</H1>";
     $html[]="<H2>{$array["TEXT"]}</H2>";
     $html[]="<textarea style='width:100%;height:250px'>".@file_get_contents($LOG_FILE)."</textarea>";
     echo $tpl->_ENGINE_parse_body($html);
 }

