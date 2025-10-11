<?php

if(isset($_GET["verbose"])){
    ini_set('html_errors',0);
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string','');
    ini_set('error_append_string','');
    ini_set("log_errors", 1);
    ini_set("error_log", "/var/log/php.log");
    $GLOBALS["VERBOSE"]=true;
}
{if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
if(isset($_GET["refresh-menus"])){RefreshMenus();exit;}
if(isset($_GET["tiny-page"])){tiny_page();exit;}
if(isset($_GET["content"])){build_progress();exit;}
if(isset($_GET["startup"])){startup();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
if(isset($_GET["no-privs"])){no_privs();exit;}
if(isset($_GET["no-lic"])){no_lic();exit;}
if(isset($_GET["confirm-before"])){confirm_before();exit;}
if(isset($_POST["Execute"])){admin_tracks("Confirmed {$_POST["Execute"]}");exit;}
if(isset($_GET["badprivs"])){badprivs();exit;}
if(isset($_GET["browser-items"])){browser_items();exit;}

$ff=array();
header("content-type: application/x-javascript");
foreach ($_GET as $key=>$val){
    $ff[]="$key => $val";
}
$content=base64_decode(@implode("\n",$ff));
echo "alert('Not understood'+ base64_decode('$content');\n";

function RefreshMenus(){
    header("content-type: application/x-javascript");
    $f[]="if(document.getElementById('left-barr')){";
    $f[]="  LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes&removeclass=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('top-barr')){";
    $f[]="LoadAjaxSilent('top-barr','fw-top-bar.php');";
    $f[]="}";

    echo @implode("\n",$f);
}
function browser_items():bool{
    VERBOSE($_GET["browser-items"],__LINE__);
    $data=base64_decode($_GET["browser-items"]);
    VERBOSE("Data:$data",__LINE__);
    $BrowserItem=unserialize($data);

    if($GLOBALS["VERBOSE"]){print_r($BrowserItem);}
    if(!is_array($BrowserItem)){$BrowserItem=array();}
    $html=array();
    if(isset($BrowserItem["title"])){
        $title=base64_encode($BrowserItem["title"]);
        $html[]="$.address.title(base64_decode('$title'));";
    }

    if(isset( $BrowserItem["url"])){
        $url=$BrowserItem["url"];
        $HTTP_X_ARTICA_SUBFOLDER=$url;
        $root="/";
        if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
            $root="/{$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]}/";
        }
        $html[] = "$.address.state('$root');";
        $html[] = "$.address.value('$HTTP_X_ARTICA_SUBFOLDER');";
    }

    if(count($html)==0){
        VERBOSE("COUNT === 0",__LINE__);
        return false;
    }

    if($GLOBALS["VERBOSE"]){
        print_r($html);
    }

    header("content-type: application/x-javascript");
    echo @implode("\n",$html);
    return true;
}
function  tiny_page(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $TINY_ARRAY=unserialize(base64_decode($_GET["tiny-page"]));

    $tinyuniq=md5(serialize($TINY_ARRAY).microtime(true).time());
    $issets[]="TITLE";
    $issets[]="ICO";
    $issets[]="EXPL";
    $issets[]="URL";
    $issets[]="BUTTONS";
    $issets[]="JSAFTER";
    foreach ($issets as $key){
        if(!isset($TINY_ARRAY[$key])){$TINY_ARRAY[$key]=null;}
    }
    $addonclass=null;
    $addonclass_explain=null;
    $addonclassend_explain=null;
    $explain=null;
    $title=$TINY_ARRAY["TITLE"];
    $ico=$TINY_ARRAY["ICO"];
    if(isset($TINY_ARRAY["EXPL"])) {
        $explain = $TINY_ARRAY["EXPL"];
    }

    $url=$TINY_ARRAY["URL"];
    $buttons=$TINY_ARRAY["BUTTONS"];
    if(isset($TINY_ARRAY["DANGER"])){
        if($TINY_ARRAY["DANGER"]==true){
            $addonclass="text-danger ";
            $addonclass_explain="<span class=\"text-danger\">";
            $addonclassend_explain="</span>";
        }
    }

    if($title<>null){
        $title=$tpl->_ENGINE_parse_body($title);

        $title=str_replace("'","\'",$title);
        $title=str_replace("\n","\\n",$title);
        $title_text=$tpl->javascript_parse_text($title);


        $title_text=str_replace("'","\'",$title_text);
        $title_text_header=strip_tags($title_text);
        $hostname=php_uname('n');
        $js[]="$.address.title('$hostname: $title_text_header');";
        $js[]="if(document.getElementById('tiny-title')){";
        $js[]="document.getElementById('tiny-title').innerHTML='<span id=\"$tinyuniq\">$addonclass_explain$title$addonclassend_explain</span>'";
        $js[]="}";
    }
    if($ico<>null){
        $js[]="if(document.getElementById('tiny-ico')){";
        $js[]="document.getElementById('tiny-ico').innerHTML='<i class=\"{$addonclass}fa-8x $ico\"></i>'";
        $js[]="}";
    }
    $js[]="if(document.getElementById('tiny-explain')){";
    if($explain<>null){
        $explain=$tpl->_ENGINE_parse_body($explain);
        $explain=str_replace("'","\'",$explain);
        $explain=str_replace("\n","\\n",$explain);
        $js[]="document.getElementById('tiny-explain').innerHTML='$addonclass_explain$explain$addonclassend_explain';";

    }
    $js[]="}";

    //$js[]="if(!document.getElementById('tiny-button')){ alert('tiny button not exists'); }";
    
    $js[]="if(document.getElementById('tiny-button')){";
    if($buttons<>null){
        $buttons=$tpl->_ENGINE_parse_body($buttons);
        $buttons=str_replace("'","\'",$buttons);
        $buttons=str_replace("\n","",$buttons);
        $buttons=str_replace("\r","",$buttons);
        $buttons=str_replace("\t","",$buttons);
        $js[]="document.getElementById('tiny-button').innerHTML='$buttons'";

    }else{
        $js[]="document.getElementById('tiny-button').innerHTML='';";
    }
    $js[]="}";


    $divs[]="tiny-explain:bouncein";
    $divs[]="tiny-ico:flip";
    $divs[]="tiny-title:shake";
    $divs[]="tiny-button:slideInLeft";
    $t=time();
    $js[] = "$('#MainContent').removeClass('animated');";
    $js[] = "function Animated$t(){";
    foreach ($divs as $animatedDiv) {
        $tt=explode(":",$animatedDiv);
        $xdiv=$tt[0];
        $animation=$tt[1];
        $js[] = "if(document.getElementById('$xdiv')){";
        $js[] = "$('#$xdiv').removeAttr('class').attr('class', '');";
        $js[] = "$('#$xdiv').addClass('animated');";
        $js[] = "$('#$xdiv').addClass('$animation');";
        $js[] = "}\n";
    }
    $js[]="}";

    if($url<>null) {
        $HTTP_X_ARTICA_SUBFOLDER="/";
        if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
            $HTTP_X_ARTICA_SUBFOLDER="/$HTTP_X_ARTICA_SUBFOLDER/$url";
        }

        $js[] = "$.address.state('$HTTP_X_ARTICA_SUBFOLDER');";
        $js[] = "$.address.value('$HTTP_X_ARTICA_SUBFOLDER$url');";
    }
    $js[]="Animated$t()";

    if(!isset($TINY_ARRAY["JSAFTER"])){
        $TINY_ARRAY["JSAFTER"]="";
    }
    if(is_null($TINY_ARRAY["JSAFTER"])){
        $TINY_ARRAY["JSAFTER"]="";
    }

    if(strlen($TINY_ARRAY["JSAFTER"])>3) {
        $TINY_ARRAY["JSAFTER"]=str_replace("%s",$tinyuniq,$TINY_ARRAY["JSAFTER"]);
        $js[] = $TINY_ARRAY["JSAFTER"];
    }

echo @implode("\n",$js);
}
function confirm_before():bool{
    $tpl=new template_admin();
    $confirm_before=base64_decode($_GET["confirm-before"]);
    $jscode=base64_decode($_GET["jscode"]);
    return $tpl->js_confirm_execute($confirm_before,"Execute",$confirm_before,$jscode);
}
function no_privs():bool{
	$tpl=new templates();
	echo "<div class=text-danger>".$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}")."</div>";
    return true;
}
function badprivs():bool{
    $tpl=new template_admin();
    return $tpl->js_error("{ERROR_NO_PRIVS2}");
}
function no_lic():bool{
	$tpl=new templates();
	echo "<div class=text-danger>".$tpl->_ENGINE_parse_body("{this_feature_is_disabled_corp_license}")."</div>";
    return true;
}
function build_progress():bool{
	header("content-type: application/x-javascript");
	$tpl        = new templates();
	$page       = CurrentPageName();
    $md5_uri    = null;
    $stylebig   = false;
    $badpath    =PROGRESS_DIR . PROGRESS_DIR;
    if(isset($ARRAY["BIG"])){
        $stylebig=true;
    }
	$content=$_GET["content"];
	if(is_numeric($content)){
	    echo "// Content is numeric\n";
        if(isset($_GET["md5"])){
            echo "// Content is MD5 too\n";
            $md5=$_GET["md5"];
            $md5_uri="&md5=$md5";
        }
    }else{
        echo "// Content is not numeric\n";
        $ARRAY=unserialize(base64_decode($_GET["content"]));
    }

	$id=$_GET["mainid"];
    $myid=md5(microtime());
	$t=time();
    if(!isset($ARRAY["PROGRESS_FILE"])){
        $ARRAY["PROGRESS_FILE"]=null;
    }

    $ARRAY["LOG_FILE"]=trim($ARRAY["LOG_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace("PROGRESS_DIR",PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace($badpath,PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    if(!is_null($ARRAY["PROGRESS_FILE"])) {
        $ARRAY["PROGRESS_FILE"] = str_replace("PROGRESS_DIR",PROGRESS_DIR,$ARRAY["PROGRESS_FILE"]);
        $ARRAY["PROGRESS_FILE"] = str_replace($badpath, PROGRESS_DIR, $ARRAY["PROGRESS_FILE"]);
        $ARRAY["PROGRESS_FILE"] = str_replace("\n","",$ARRAY["PROGRESS_FILE"]);
        $ARRAY["PROGRESS_FILE"] = str_replace(" ","",$ARRAY["PROGRESS_FILE"]);
        echo "// PROGRESS_FILE = <{$ARRAY["PROGRESS_FILE"]}>\n";
    }
    echo "// Framework = {$ARRAY["CMD"]}\n";

	if(is_null($ARRAY["PROGRESS_FILE"])) {
        $line = __LINE__;
        echo "document.getElementById('title-$id').innerHTML='Progress file not set L.$line';
		document.getElementById('barr-$id').style.width='100%';
		document.getElementById('barr-$id').innerHTML='Progress file not set';		
		document.getElementById('barr-$id').className='progress-bar-danger';
		document.getElementById('barr-$id').style.color='#FFFFFF';
		document.getElementById('title-$id').style.color='#ED5565';";
        return true;
    }
	$GLOBALS["PROGRESS_FILE"]=$ARRAY["PROGRESS_FILE"];
	$GLOBALS["LOG_FILE"]=$ARRAY["LOG_FILE"];
	$title=$tpl->javascript_parse_text($ARRAY["TITLE"]);
	$title2=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}");

	$htmlContent="<div><h5 id=\"title-$myid\">$title</h5><div class=\"progress progress-bar-default\" id=\"main-$myid\"><div id=\"barr-$myid\" style=\"width: 2%\" aria-valuemax=\"100\" aria-valuemin=\"0\" aria-valuenow=\"5\" role=\"progressbar\" class=\"progress-bar\">2% $title2</div></div></div>";

    if($stylebig){
        $htmlContent="
        <div style=\"margin-bottom: 20px\"><H1 id=\"title-$myid\">$title</H1></div>
        <div class=\"progress progress-bar-default\" 
        id=\"main-$myid\" style=\"height: 60px\"><div id=\"barr-$myid\" style=\"width: 2%\" aria-valuemax=\"100\" aria-valuemin=\"0\" aria-valuenow=\"5\" role=\"progressbar\" class=\"progress-bar\">2% $title2</div></div>";
    }

    $htmlContent=base64_encode($htmlContent);
	$html="// Line:".__LINE__."
	function f$myid(){
		if(document.getElementById('reconfigure-service-div') ){
			document.getElementById('reconfigure-service-div').style.marginTop='0px';
			document.getElementById('reconfigure-service-div').className='';
			document.getElementById('reconfigure-service-div').innerHTML='';
		}

        if(! document.getElementById('$id') ){
               alert('div layer $id not found');
               return false;
        }
		document.getElementById('$id').innerHTML=base64_decode('$htmlContent');
	    Loadjs('$page?startup={$_GET["content"]}$md5_uri&mainid=$id&myid=$myid&t=$t');
	}
	
	f$myid();";
	
	
	echo $html;
	return true;
}
function  startup():bool{
    $badpath    =PROGRESS_DIR ."/". PROGRESS_DIR;
    if($GLOBALS["VERBOSE"]){
        error_log(__LINE__." OK");
    }

	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$id=$_GET["mainid"];
	$myid=$_GET["myid"];
    $md5_uri=null;
	$t=$_GET["t"];


    $content=$_GET["startup"];
    if(is_numeric($content)){
        echo "// Content is numeric\n";
        if(isset($_GET["md5"])){
            echo "// Content is MD5 too\n";
            $md5=$_GET["md5"];
            $md5_uri="&md5=$md5";
        }

    }else{
        echo "// Content is not numeric\n";
        $ARRAY=unserialize(base64_decode($content));
    }

	$CMD=$ARRAY["CMD"];
	if($CMD<>null){
        $err=RUN_CMD($CMD);
        if(strlen($err)>1){
            return $tpl->js_error($err);
        }
    }
    $ARRAY["PROGRESS_FILE"]=str_replace("PROGRESS_DIR",PROGRESS_DIR,$ARRAY["PROGRESS_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace("PROGRESS_DIR",PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace($badpath,PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    $ARRAY["PROGRESS_FILE"]=str_replace($badpath,PROGRESS_DIR,$ARRAY["PROGRESS_FILE"]);

	$GLOBALS["PROGRESS_FILE"]=$ARRAY["PROGRESS_FILE"];
	$GLOBALS["LOG_FILE"]=$ARRAY["LOG_FILE"];
	

	if($GLOBALS["PROGRESS_FILE"]==null){
		
		echo "document.getElementById('title-$myid').innerHTML='Progress file not set';
		document.getElementById('barr-$myid').style.width='100%';
		document.getElementById('barr-$myid').innerHTML='Progress file not set';		
		document.getElementById('barr-$myid').className='progress-bar-danger';
		document.getElementById('barr-$myid').style.color='#FFFFFF';
		document.getElementById('title-$myid').style.color='#ED5565';";
		return true;
	}
	
	if(strlen($GLOBALS["PROGRESS_FILE"])<5){
		echo "document.getElementById('title-$myid').innerHTML='Progress file {$GLOBALS["PROGRESS_FILE"]} corrupted';
		document.getElementById('barr-$myid').style.width='100%';
		document.getElementById('barr-$myid').innerHTML='Progress file not set';
		document.getElementById('barr-$myid').className='progress-bar-danger';
		document.getElementById('barr-$myid').style.color='#FFFFFF';
		document.getElementById('title-$myid').style.color='#ED5565';";
		return true;
	}
	
	$title=base64_encode($tpl->javascript_parse_text("5% ".$ARRAY["TITLE"]));
    admin_tracks("Launch task $title ($CMD)");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
	
	$html="
//$CMD
function Step1$t(){
	document.getElementById('title-$myid').innerHTML=base64_decode('$title');
	document.getElementById('barr-$myid').style.width='5%';
	document.getElementById('barr-$myid').innerHTML=base64_decode('$title');
	Loadjs('$page?build-js={$_GET["startup"]}&mainid=$id{$md5_uri}&myid=$myid&t=$t');
}
setTimeout(\"Step1$t()\",1000);\n";
	echo $html;
	return true;
}
function RUN_CMD($CMD):string{

    $sock=new sockets();


    if(preg_match("#^firecrack:(.+)$#",$CMD,$match)){
        $CMD=$match[1];
        writelogs("REST_API_FIRECR($CMD)",__FUNCTION__,__FILE__,__LINE__);
        $data=$sock->REST_API_FIRECR($CMD);
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return "Decoding data".json_last_error()."<br>$sock->mysql_error";
        }
        if(!$json->Status){
            writelogs("REST_API_FIRECR($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return "Status false<br>$data<br>".$json->Info;
        }
        return "";
    }
    if(preg_match("#^watch:(.+)$#",$CMD,$match)){
        $CMD=$match[1];
        writelogs("REST_ARTWATCH($CMD)",__FUNCTION__,__FILE__,__LINE__);
        $data=$sock->REST_ARTWATCH($CMD);
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return "Decoding data".json_last_error()."<br>$sock->mysql_error";
        }
        if(!$json->Status){
            writelogs("REST_API($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return "Status false<br>$data<br>".$json->Info;
        }
        return "";
    }

    if(preg_match("#^nginx:(.+)$#",$CMD,$match)){
        $CMD=$match[1];
        writelogs("REST_API_NGINX($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
        $data=$sock->REST_API_NGINX($CMD);
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return "Decoding data".json_last_error()."<br>$sock->mysql_error";
        }
        if(!$json->Status){
            $info="";
            if(property_exists($json,"Info")){
                $info=$json->Info;
            }
            writelogs("REST_API_NGINX($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return "Status false<br>$data<br>$info";
        }
        return "";
    }

    if (substr($CMD, 0, 1) === '/') {
            writelogs("REST_API($CMD)",__FUNCTION__,__FILE__,__LINE__);
            $data=$sock->REST_API($CMD);
            $json = json_decode($data);
            if (json_last_error() > JSON_ERROR_NONE) {
                return "Decoding data".json_last_error()."<br>$sock->mysql_error";
            }
            if(!$json->Status){
                $info="";
                if(property_exists($json,"Info")){
                    $info=$json->Info;
                    }
                writelogs("REST_API($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
                return "Status false<br>$data<br>$info";
            }
        return "";
    }


    writelogs("getFrameWork($CMD)",__FUNCTION__,__FILE__,__LINE__);
    $sock->getFrameWork($CMD);
    return "";

}
function buildjs():bool{
	$time=time();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
    $badpath    =PROGRESS_DIR ."/". PROGRESS_DIR;
	$id=$_GET["mainid"];
	$myid           = $_GET["myid"];
	$REFRESH_MENU   = 0;
	$t              = $_GET["t"];
    $md5_uri        = null;
	$ORDER          = 0;
	$PROGRESS_DIR   = "/usr/share/artica-postfix/ressources/logs/web";

    $content=$_GET["build-js"];
    if(is_numeric($content)){
        if(isset($_GET["md5"])){
            echo "// Content is MD5 too\n";
            $md5=$_GET["md5"];
            $md5_uri="&md5=$md5";
        }
    }else{
        $ARRAY=unserialize(base64_decode($content));
    }
    echo "// $badpath\n";
    $ARRAY["PROGRESS_FILE"]=str_replace("PROGRESS_DIR",$PROGRESS_DIR,$ARRAY["PROGRESS_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace("PROGRESS_DIR",$PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace($badpath,PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    $ARRAY["PROGRESS_FILE"]=str_replace($badpath,PROGRESS_DIR,$ARRAY["PROGRESS_FILE"]);
    if(!isset($ARRAY["BEFORE"])){$ARRAY["BEFORE"]=null;}
    if(!isset($ARRAY["AFTER"])){$ARRAY["AFTER"]=null;}
	$CMD=$ARRAY["CMD"];
	$GLOBALS["PROGRESS_FILE"]=$ARRAY["PROGRESS_FILE"];
	$GLOBALS["LOG_FILE"]=$ARRAY["LOG_FILE"];
	$cachefile=$GLOBALS["PROGRESS_FILE"];
	$logsFile=$GLOBALS["LOG_FILE"];
	$logsFileEncoded=urlencode($logsFile);
	$BEFORE=$ARRAY["BEFORE"];
	$AFTER=$ARRAY["AFTER"];
    $AFTER_FAILED="";
    if(isset($ARRAY["AFTER_FAILED"])){$AFTER_FAILED=$ARRAY["AFTER_FAILED"];}
	if(isset($ARRAY["AFTER-FAILED"])){$AFTER_FAILED=$ARRAY["AFTER-FAILED"];}
	if(isset($ARRAY["REFRESH-MENU"])){$REFRESH_MENU=1;}
    $Details="";
    if( is_file($logsFile)){
        $Details = $tpl->_ENGINE_parse_body("&nbsp;&nbsp;<a href=\"javascript:blur()\" OnClick=\"Zoom$t()\" style=\"text-decoration:underline;color:white\">&laquo;{details}&raquo;</a>");
    }
	
	$title_src=$tpl->javascript_parse_text($ARRAY["TITLE"]);
    $title_src_default=$tpl->javascript_parse_text("{please_wait}");
	$title2=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}");	
	echo "// Array of ".count($ARRAY)." elements\n";
	echo "// Cache file = $cachefile\n";
	echo "// Log file = $logsFile\n";
	echo "// CMD = $CMD\n";
	$array=unserialize(@file_get_contents($cachefile));
    if(!is_array($array)){$array=array();}
    if(!isset($array["POURC"])){
        $array["POURC"]=0;
    }

	$prc=intval($array["POURC"]);
	echo "// prc = $prc\n";

    if(!isset($array["TEXT"])){$array["TEXT"]="None";}
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	$titleEncoded=urlencode($title_src);
    if($title_src==null){$title_src=$title_src_default;}
    if(!isset($_GET["md5file"])){$_GET["md5file"]="";}
	
if($prc==0){
	echo "
	function Start$time(){
			if(!document.getElementById('$id')){return;}
			Loadjs('$page?build-js={$_GET["build-js"]}&mainid=$id{$md5_uri}&myid=$myid&t=$t&md5file={$_GET["md5file"]}');
			LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
	}
	setTimeout(\"Start$time()\",1000);";
	return true;
}

    $md5file="";
    if(is_file($logsFile)) {
        $md5file = md5_file($logsFile);
    }
    $sText=base64_encode("$title_src: $prc% $title");

if($md5file<>$_GET["md5file"]){
	echo "
	var xStart$time= function (obj) {
//		if(!document.getElementById('text-$t')){return;}
//		var res=obj.responseText;
//		if (res.length>3){ document.getElementById('text-$t').value=res; }		
		Loadjs('$page?build-js={$_GET["build-js"]}&mainid=$id{$md5_uri}&myid=$myid&t=$t&md5file=$md5file');
		LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
	}		
	
	function Start$time(){
		if(!document.getElementById('$id')){return;}
		if(document.getElementById('title-$myid')){
		    document.getElementById('title-$myid').innerHTML=base64_decode('$sText');
		}
		if(document.getElementById('barr-$myid')){
		    document.getElementById('barr-$myid').style.width='{$prc}%';
		    document.getElementById('barr-$myid').innerHTML=base64_decode('$sText');
		}
		var XHR = new XHRConnection();
		XHR.appendData('Filllogs', 'yes');
		XHR.appendData('filename','".urlencode($logsFile)."');
		XHR.appendData('t', '$t');
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',xStart$time,false); 
	}
	setTimeout(\"Start$time()\",1000);";
	return true;
}

if($prc>100){
    $sText=base64_encode("$title_src - 100% $title$Details");
    admin_tracks("Launch task $title_src failed");
	echo "
	function Start$time(){
		if(!document.getElementById('$id')){return;}
		document.getElementById('title-$myid').innerHTML=base64_decode('$sText');
		document.getElementById('barr-$myid').style.width='100%';
		document.getElementById('barr-$myid').innerHTML=base64_decode('$sText');		
		document.getElementById('barr-$myid').className='progress-bar-danger';
		document.getElementById('barr-$myid').style.color='#FFFFFF';
		document.getElementById('title-$myid').style.color='#ED5565';
		LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
		
	}
	function jsProgressAfterFailed(){
	     Zoom$t();
	}
	
	function Clean$time(){
	    if(!document.getElementById('$id')){return;}
	    document.getElementById('$id').innerHTML='';
	}
	
	function Zoom$t(){ Loadjs('fw.progress.details.php?logfile=$logsFileEncoded&title=$titleEncoded'); }
	setTimeout(\"Start$time()\",1000);
	$AFTER_FAILED;
    setTimeout(\"Clean$time()\",5000);";
	return true;
	
}
    $JS_CLUSTER=null;
    $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
    if($PowerDNSEnableClusterMaster==1){
        $JS_CLUSTER="Loadjs('fw.system.cluster.master.php?Launch=yes')";
    }


if($prc==100){
    admin_tracks("Launch task $title_src success");
    $sText=base64_encode("$title_src - 100% $title$Details");

	echo "
	function Start$time(){
		var REFRESH_MENU=$REFRESH_MENU;
		if(!document.getElementById('$id')){return;}
		document.getElementById('title-$myid').innerHTML=base64_decode('$sText');
		document.getElementById('barr-$myid').style.width='100%';
		document.getElementById('barr-$myid').innerHTML=base64_decode('$sText');		
		document.getElementById('barr-$myid').className='progress-bar';
		document.getElementById('barr-$myid').style.color='#FFFFFF';
		document.getElementById('title-$myid').style.color='#1AB394';
		if(REFRESH_MENU==1){
			uri=document.getElementById('fw-left-menus-uri').value
			LoadAjaxSilent('left-barr',uri);
			LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
		}
		$AFTER;
		$JS_CLUSTER;
		setTimeout(\"Clean$time()\",5000);
	}
	function Clean$time(){
	    if(!document.getElementById('$id')){return;}
	    document.getElementById('$id').innerHTML='';
	}
	function Zoom$t(){ Loadjs('fw.progress.details.php?logfile=$logsFileEncoded&title=$titleEncoded'); }
	
	setTimeout(\"Start$time()\",1000);
	";	
	return true;
}
$sText=base64_encode("$prc% $title");
echo "	
function Start$time(){
	if(!document.getElementById('$id')){return;}
	document.getElementById('title-$myid').innerHTML=base64_decode('$sText');
	document.getElementById('barr-$myid').style.width='{$prc}%';
	document.getElementById('barr-$myid').innerHTML=base64_decode('$sText');
	Loadjs('$page?build-js={$_GET["build-js"]}&mainid=$id{$md5_uri}&myid=$myid&t=$t&md5file={$_GET["md5file"]}');
	
}
$BEFORE;
setTimeout(\"Start$time()\",1500);
";
return true;
}
function popup():bool{
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$text=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}...");
	
$html="
<center id='title-$t' style='font-size:18px;margin-bottom:20px'>$text</center>
<div id='progress-$t' style='height:50px'></div>
<p>&nbsp;</p>
<textarea style='margin-top:5px;font-family:Courier New,serif;
font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
overflow:auto;font-size:11px' id='text-$t'></textarea>
	
<script>
function Step1$t(){
	$('#progress-$t').progressbar({ value: 1 });
	Loadjs('$page?build-js=yes&t=$t&md5file=0&comand=".urlencode($_GET["comand"])."');
}
$('#progress-$t').progressbar({ value: 1 });
setTimeout(\"Step1$t()\",1000);

</script>
";
echo $html;
return true;
}
function Filllogs():bool{
    if(!isset($GLOBALS["LOG_FILE"])){return false;}
	$logsFile=$GLOBALS["LOG_FILE"];
	$t=explode("\n",@file_get_contents($logsFile));
	krsort($t);
	echo @implode("\n", $t);
    return true;
}