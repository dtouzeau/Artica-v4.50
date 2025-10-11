<?php
session_start();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__)."/ressources/class.xapian.inc");
include_once(dirname(__FILE__)."/ressources/class.crypt.php");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.xapian.inc");
include_once(dirname(__FILE__)."/ressources/mimestypes.inc");


if(isset($_GET["php-ini"])){php_ini_js();exit;}
if(isset($_GET["phpinfo-popup"])){phpinfo_popup();exit;}
if(isset($_REQUEST["q"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
if(isset($_GET["options-js"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}
if(isset($_POST["SELECT_ONLY"])){options_save();exit;}
if(isset($_GET["download-js"])){download_js();exit;}
if(isset($_GET["download"])){download();exit;}
page();


function php_ini_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="PHP v".PHP_VERSION;
	$tpl->js_dialog2($title, "$page?phpinfo-popup=yes");
	
}
function zoom_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="PHP v".PHP_VERSION;
	
	$line=unserialize(base64_decode($_GET["zoom-js"]));
	$data=$line["DATA"];
	$PATH=$line["PATH"];
	$TYPE=$line["TYPE"];
	$title=urldecode(basename($PATH));
	$tpl->js_dialog2($title, "$page?zoom-popup={$_GET["zoom-js"]}");

}
function options_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{options}";
	$tpl->js_dialog2($title, "$page?options-popup=yes");	
	
}

function zoom_popup(){
	$tpl=new template_admin();
	$line=unserialize(base64_decode($_GET["zoom-popup"]));
	$data=$line["DATA"];
	$PATH=$line["PATH"];
	$TYPE=$line["TYPE"];
	$filename=urldecode(basename($PATH));
	
	$line["LINK"]=str_replace("/", "\\", $line["LINK"]);
	$form[]=$tpl->field_info(time(), "{filename}", $filename);
	$form[]=$tpl->field_info(time()+1, "{size}", $line["SIZE"]);
	$form[]=$tpl->field_info(time()+1, "{date}", $line["TIME"]);
	$form[]=$tpl->field_info(time()+1, "{path}", $line["LINK"]);
	
	echo $tpl->form_outside($filename, @implode("\n", $form),null,null);
	
	
}

function options_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new mysql();
	$sql="SELECT * FROM xapian_folders WHERE enabled=1 ORDER BY zorder";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	
	if(!isset($_SESSION["INSTANSEARCH"]["SELECT_ONLY"])){
		if(!isset($_SESSION["INSTANSEARCH"]["RESSOURCES"])){$_SESSION["INSTANSEARCH"]["SELECT_ONLY"]=0;}
		if(count($_SESSION["INSTANSEARCH"]["RESSOURCES"])==0){$_SESSION["INSTANSEARCH"]["SELECT_ONLY"]=0;}
	}
	
	$form[]=$tpl->field_checkbox("SELECT_ONLY","{ONLY_SELECTED_RESOURCES}",$_SESSION["INSTANSEARCH"]["SELECT_ONLY"],true);
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$id=$ligne["ID"];
		$form[]=$tpl->field_checkbox("CHOOSE_$id",$ligne["ressourcename"],$_SESSION["INSTANSEARCH"]["RESSOURCES"][$id],false,"{$ligne["ressourceexplain"]}");
		
	}
	
	echo $tpl->form_outside("{resources}", @implode("\n", $form),"{INSTANT_SEARCH_EXPLAIN_OPTIONS}","{apply}","document.getElementById('InstantSearchForm').submit();");
	
	
}

function options_save(){
	$_SESSION["INSTANSEARCH"]["SELECT_ONLY"]=$_POST["SELECT_ONLY"];
	unset($_POST["SELECT_ONLY"]);
	
	unset($_SESSION["INSTANSEARCH"]["RESSOURCES"]);
	$_SESSION["INSTANSEARCH"]["RESSOURCES"]=array();
	foreach ($_POST as $num=>$ligne){
		if(preg_match("#^CHOOSE_([0-9]+)#", $num,$re)){
			if($ligne==1){
				$_SESSION["INSTANSEARCH"]["RESSOURCES"][$re[1]]=1;
			}
		}
		
	}
	

	
}

function download_js(){
	$XapianAllowDownloads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianAllowDownloads"));
	if($XapianAllowDownloads==0){
		
	}
	$resource_id=$_GET["download-js"];
	$line=unserialize(base64_decode($_GET["info"]));
	
	$tpl=new template_admin();
	$PATH=urldecode($line["PATH"]);
	$TYPE=$line["TYPE"];
	
	if(preg_match("#\/ID-([0-9]+)\/(.+)#", $PATH,$re)){
		$path=$re[2];
		$resource_id=$re[1];
	}else{
		if($GLOBALS["VERBOSE"]){echo "$PATH -> no match #\/ID=([0-9]+)\/(.+)#<br>\n";}
	}
	
	$basename=basename($path);
	
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM xapian_folders WHERE ID=$resource_id","artica_backup"));
	$hostname=$ligne["hostname"];
	$sfolder=$ligne["sfolder"];
	$tfolder=$ligne["tfolder"];
	$username=$ligne["username"];
	$wks=$ligne["workgroup"];
	$password=$ligne["password"];
	$mountpoint="/usr/share/artica-postfix/ressources/mounts/InstantSearch_$resource_id";
	
	if($GLOBALS["VERBOSE"]){
		echo "<li>/$tfolder/$path</li>";
	}
	
	$FinalPath=$mountpoint."/$tfolder/$path";
	
	if($ligne["ztype"]=="smb"){
		include(dirname(__FILE__)."/ressources/class.mount.inc");
		include(dirname(__FILE__)."/framework/class.unix.inc");
		if($username<>null){if($wks<>null){$username="$username@$wks";}}
			
			
		$array["m"]=$mountpoint;
		$array["h"]=$hostname;
		$array["u"]=$username;
		$array["p"]=$password;
		$array["s"]=$sfolder;
		$sock=new sockets();
		$sock->getFrameWork("xapian.php?smbmount=".base64_encode(serialize($array)));
		if(!is_dir($mountpoint)){
			$tpl->popup_error("$hostname: {unable_to_mount}");
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
		
		$mountpointEnc=urlencode($mountpoint);
		if(!is_file($FinalPath)){
			$sock->getFrameWork("xapian.php?smbunmount=$mountpointEnc");
			if($GLOBALS["VERBOSE"]){echo "$FinalPath\n";}
			$tpl->popup_error("<p style=font-size:10px>$FinalPath</p><hr> {no_such_file}");
			die("DIE " .__FILE__." Line: ".__LINE__);
			
		}
		
		$size=filesize("$FinalPath");
		if($size==0){
			$sock->getFrameWork("xapian.php?smbunmount=$mountpointEnc");
			$tpl->popup_error("$path: 0 bytes!");
			die("DIE " .__FILE__." Line: ".__LINE__);
			
		}
		
		$FINAL["MOUNTPOINT"]=$mountpoint;
		$FINAL["FINAL_PATH"]=$FinalPath;
		$FINAL["TYPE"]=$TYPE;
		$FINAL["SIZE"]=$size;
		$info=base64_encode(serialize($FINAL));
		$page=CurrentPageName();
		
		if($GLOBALS["VERBOSE"]){
			while (list ($ID, $none) = each ($FINAL)){
				echo "<li><strong>$ID:</strong> <code>$none</code></li>\n";
			}
			
		}
		
		if(!$GLOBALS["VERBOSE"]){header("content-type: application/x-javascript");}
		echo "window.location.href = '$page?download=$resource_id&mountpoint=$mountpoint&info=$info';";
		
	}
	
}

function download(){
	$XapianAllowDownloads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianAllowDownloads"));
	if($XapianAllowDownloads==0){die("DIE " .__FILE__." Line: ".__LINE__);}
	$FINAL=unserialize(base64_decode($_GET["info"]));
	$sock=new sockets();
	$mountpoint=$FINAL["MOUNTPOINT"];
	$FinalPath=$FINAL["FINAL_PATH"];;
	$FinalPath=str_replace("//", "/", $FinalPath);
	
	$TYPE=$FINAL["TYPE"];;
	$size=$FINAL["SIZE"];
	$mountpointEnc=urlencode($mountpoint);
	$basename=basename($FinalPath);
	
	
	if(trim($TYPE)==null){
		$ext=Get_extension($basename);
		$TYPE=$GLOBALS["MIME_CONTENT_TYPES_ARRAY"][$ext];
		if($GLOBALS["VERBOSE"]){echo "Extension: ($basename) $ext\n";}
	}
	
		
	if(!$GLOBALS["VERBOSE"]){
		header("Content-Type:  $TYPE");
		header("Content-Disposition: attachment; filename=\"$basename\"");
		header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
		header("Pragma: no-cache"); // HTTP 1.0
		header("Expires: 0"); // Proxies
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
		header("Content-Length: $size");
		ob_clean();
		flush();
		readfile($FinalPath);
	}else{
			echo "Filename: $FinalPath\n<br>";
			echo "Content-Type:  $TYPE\n<br>";
			echo "Content-Length:  ".filesize($FinalPath)."<br>\n";
	}
	$sock->getFrameWork("xapian.php?smbunmount=$mountpointEnc");
	

	
}


function phpinfo_popup(){

	$s=php_ini_path();
	$s=str_replace('<div class="center">', "", $s);
	$s=str_replace('<table>', "<table class=\"table table-striped\">", $s);
	$s=str_replace('<td class="e">', "<td nowrap>", $s);
	$s=str_replace('Winstead,', "Winstead,<br>", $s);
	$s=str_replace('Belski,', "Belski,<br>", $s);
	$s=str_replace('Rethans,', "Rethans,<br>", $s);
	$s=str_replace('Zarkos,', "Zarkos,<br>", $s);
	$s=str_replace('auth_plugin_mysql_clear_password,', "auth_plugin_mysql_clear_password,<br>", $s);
	echo $s;


}
function php_ini_path(){
	ob_start();
	phpinfo();
	$s = ob_get_contents();
	ob_end_clean();
	if(preg_match("#<body>(.*?)</body>#is", $s,$re)){$s=$re[1];}
	return $s;
}
function page(){
	
	$XapianSearchTitle=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchTitle"));
	if($XapianSearchTitle==null){$XapianSearchTitle="Company Search Engine";}
	
	$XapianSearchText=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchText"));
	if($XapianSearchText==null){$XapianSearchText="Find any document stored in the company's network";}
	
	$XapianSearchField=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchField"));
	if($XapianSearchField==null){$XapianSearchField="Search something...";}
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	
	
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT count(*) as tcount FROM xapian_folders WHERE enabled=1","artica_backup"));
	$_SESSION["INSTANSEARCH"]["TCOUNT"]=$ligne["tcount"];
	if($ligne["tcount"]>1){
		$options="<div style='text-align:right;padding-right:50px'><a href=\"javascript:Loadjs('$page?options-js=yes')\">{options}</a></div>";
	}
	
	$f[]=Heads();
	$f[]="";
	$f[]="    <div class=\"passwordBox animated fadeInDown\" style='max-width:1024px'>";
	$f[]="        <div class=\"row\">";
	$f[]="";
	$f[]="            <div class=\"col-md-12\">";
	$f[]="                <div class=\"ibox-content\">";
	$f[]="                    <h2 class=\"font-bold\">$XapianSearchTitle</h2>";
	$f[]="                    <p>$XapianSearchText</p>";
	
	if (!extension_loaded('xapian')) {
		
		$f[]=$tpl->FATAL_ERROR_SHOW_128("{ERROR_XAPIAN_NOEXTSION}");
		$f[]="<center style='margin:30px'>".$tpl->button_autnonome("{php_values}", "Loadjs('$page?php-ini=yes')", "fa-bug")."</center>";
	}
		
	
	$f[]="";
	$f[]="                    <div class=\"row\">";
	$f[]="                        <div class=\"col-lg-12\" style='margin-bottom:50px'>";
	$f[]="                            <form class=\"m-t\" role=\"form\" action=\"$page\" method=\"get\" id='InstantSearchForm'>";
	$f[]="                                <div class=\"input-group\">";
	$f[]="										<input type=\"text\" class=\"form-control\" name=\"q\" placeholder=\"$XapianSearchField\" > <span class=\"input-group-btn\"> <button style=\"text-transform: capitalize;\" class=\"btn btn-primary\" type=\"button\" OnClick=\"javascript:document.getElementById('InstantSearchForm').submit();\">Go!</button> </span>";
	$f[]="                                </div>";
	$f[]="$options";
	$f[]="                            </form>";
	$f[]="                        </div>";
	$f[]="                    </div>";
	$f[]="                </div>";
	$f[]="            </div>";
	$f[]="        </div>";
	$f[]="    </div>";
	$f[]="";
	$f[]="</body>";
	$f[]="";
	$f[]="</html>";
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
	
}

function Heads($titleaddon=null){
    $jqueryToUse=$GLOBALS["CLASS_SOCKETS"]->jQueryToUse();
	$XapianSearchTitle=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchTitle"));
	if($XapianSearchTitle==null){$XapianSearchTitle="Company Search Engine";}
	
	$f[]="<!DOCTYPE html>";
	$f[]="<html>";
	$f[]="";
	$f[]="<head>";
	$f[]="";
	$f[]="\t<meta charset=\"utf-8\">";
	$f[]="\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
	$f[]="";
	$f[]="\t<title>$XapianSearchTitle $titleaddon</title>";
	$f[]="";
	$f[]="\t<link rel=\"icon\" href=\"/ressources/templates/default/favicon.ico\" type=\"image/x-icon\" />";
	$f[]="\t<link rel=\"shortcut icon\" href=\"/ressources/templates/default/favicon.ico\" type=\"image/x-icon\" />";
	$f[]="\t<link href=\"/angular/bootstrap.min.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/font-awesome/css/all.min.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/animate.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/plugins/footable/footable.core.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/plugins/toogle/bootstrap-toggle.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/bootstrap-dialog.min.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/plugins/chosen/bootstrap-chosen.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/plugins/codemirror/ambiance.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/plugins/codemirror/codemirror.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/plugins/clockpicker/clockface.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/plugins/bootstrap-ipaddress/bootstrap-ipaddress.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/js/plugins/datapicker/bootstrap-datepicker.min.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/js/plugins/nouslider/nouislider.min.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/js/plugins/jsTree/style.min.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/js/plugins/toastr/toastr.min.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/js/plugins/sweetalert/sweetalert.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/js/plugins/cron/jquery-gentleSelect.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/style.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/fonts.css\" rel=\"stylesheet\">";
	$f[]="\t<link href=\"/angular/plugins/steps/jquery.steps.css\" rel=\"stylesheet\">";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/js/highcharts.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/XHRConnection.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/mouse.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/jquery/$jqueryToUse\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/jasny/jasny-bootstrap.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/nouslider/nouislider.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/nouslider/wNumb.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/jsTree/jstree.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/toastr/toastr.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/sweetalert/sweetalert.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/cron/jquery-gentleSelect-min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/cron/jquery-cron-min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/validate/jquery.validate.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/steps/jquery.steps.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/cleave.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/file-upload/jquery.ui.widget.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/file-upload/jquery.fileupload.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/file-upload/jquery.iframe-transport.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/bootstrap/bootstrap.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/angular/angular.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/BootstrapDialog/bootstrap-dialog.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/toogle/bootstrap-toggle.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/slimscroll/jquery.slimscroll.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/footable/footable.all.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/metisMenu/jquery.metisMenu.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/chosen/chosen.jquery.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/flot/jquery.flot.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/flot/angular-flot.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/flot/jquery.flot.pie.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/flot/jquery.flot.time.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/flot/curvedLines.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/flot/jquery.flot.resize.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/flot/jquery.flot.tooltip.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/flot/excanvas.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/flot/jquery.flot.spline.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/flot/jquery.flot.symbol.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/codemirror/codemirror.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/codemirror/mode/xml/xml.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/clockpicker/clockface.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/sparkline/jquery.sparkline.min.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/datapicker/bootstrap-datepicker.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/inspinia.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.Wload.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/plugins/bootstrap-ipaddress/bootstrap-ipaddress.js\"></script>";
	$f[]="\t<script type=\"text/javascript\" language=\"javascript\" src=\"/default.js\"></script>";
	$f[]="\t<link href=\"/angular.css.php\" rel=\"stylesheet\">";
	$f[]="";
	$f[]="</head>";
	$f[]="";
	$f[]="<body class=\"gray-bg\">";
	return @implode("\n", $f);
}


function search(){
	//$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$q=new mysql();
	$tpl=new template_admin();
	$SearchString=$_REQUEST["q"];
	$current=0;
	$array=array();
	$page=CurrentPageName();
	$XapianSearchField=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchField"));
	if($XapianSearchField==null){$XapianSearchField="Search something...";}
	$XapianAllowDownloads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianAllowDownloads"));
	
	
	
	

	$xapian=new XapianSearch();
	
	if(!isset($_SESSION["INSTANSEARCH"]["SELECT_ONLY"])){
		if(!isset($_SESSION["INSTANSEARCH"]["RESSOURCES"])){$_SESSION["INSTANSEARCH"]["SELECT_ONLY"]=0;}
		if(count($_SESSION["INSTANSEARCH"]["RESSOURCES"])==0){$_SESSION["INSTANSEARCH"]["SELECT_ONLY"]=0;}
	}
	
	$ressourcename=array();
	if($_SESSION["INSTANSEARCH"]["SELECT_ONLY"]==1){
		while (list ($ID, $none) = each ($_SESSION["INSTANSEARCH"]["RESSOURCES"]) ){
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM xapian_folders WHERE ID=$ID","artica_backup"));
			$ressourcename[]=$ligne["ressourcename"];
			$DatabasePath="/home/omindex-databases/$ID";
			$xapian->add_database($DatabasePath);
			$t=array();
			if($ligne["ztype"]=="smb"){
				$t[]=$ligne["hostname"];
				$t[]=$ligne["sfolder"];
				if($ligne["tfolder"]<>null){$t[]=$ligne["tfolder"];}
				$PREFIX[$ligne["ID"]]="file://///".urlencode(@implode("/", $t));
				$LINK[$ligne["ID"]]="//".@implode("/", $t);
			}
			
		}
		
		
	}else{
		$sql="SELECT * FROM xapian_folders WHERE enabled=1 ORDER BY zorder";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysqli_fetch_assoc($results)) {
			$ID=$ligne["ID"];
			$CONFIG[$ID]["tfolder"]=$ligne["tfolder"];
			$CONFIG[$ID]["sfolder"]=$ligne["sfolder"];
			$CONFIG[$ID]["hostname"]=$ligne["hostname"];
			
			$DatabasePath="/home/omindex-databases/$ID";
			$xapian->add_database($DatabasePath);
			$t=array();
			if($ligne["ztype"]=="smb"){
				$t[]=$ligne["hostname"];
				$t[]=$ligne["sfolder"];
				if($ligne["tfolder"]<>null){$t[]=$ligne["tfolder"];}
				$PREFIX[$ligne["ID"]]="file://///".urlencode(@implode("/", $t));
				$LINK[$ligne["ID"]]="//".@implode("/", $t);
			}
			
		}
	
	}
	
	
	if(count($ressourcename)>0){
		
		$subtitle="{search} {in} ".@implode(", ", $ressourcename);
	}else{
		$subtitle="{search} {in} {all_resources}";
	}
	
	$XapianSearchTitle=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchTitle"));
	if($XapianSearchTitle==null){$XapianSearchTitle="Company Search Engine";}
	$f[]=Heads("$SearchString");
	
	$f[]="<div clas='wrapper wrapper-content animated fadeInRight'>";
	$f[]="<div class='row'>";
	$f[]="<div class='col-lg-12'>";
	$f[]="<div class=\"ibox float-e-margins\">";
	$f[]="<div class=\"ibox-content\" style='padding-left:250px;padding-right:250px'>";
	$f[]="<H2>$XapianSearchTitle</H2>";
	$f[]="<small><a href=\"javascript:Loadjs('$page?options-js=yes')\">$subtitle</a></small>";
	$f[]="<div class=\"search-form\">";
	$f[]="                            <form  role=\"form\" action=\"$page\" method=\"get\" id='InstantSearchForm'>";
	$f[]="                                <div class=\"input-group\">";
	$f[]="										<input type=\"text\" class=\"form-control input-lg\" name=\"q\" placeholder=\"$XapianSearchField\" value=\"$SearchString\"> <span class=\"input-group-btn\"> <button style=\"text-transform: capitalize;\" class=\"btn-lg btn-primary\" type=\"button\" OnClick=\"javascript:document.getElementById('InstantSearchForm').submit();\">Go!</button> </span>";
	$f[]="                                </div>";
	$f[]="";
	$f[]="                            </form>";
	$f[]="</div>";
	
	
	$xapian->terms=$SearchString;
	$xapian->start=$current;
	if(count($xapian->databases)==0){
		$f[]=$tpl->FATAL_ERROR_SHOW_128("{ERROR_XAPIAN_NO_DATABASE}");
		
	}else{
		$array=$xapian->search();
	}
	
	$zFORMAT["xls"]="application/vnd.ms-excel";
	$zFORMAT["xlsx"]="application/vnd.ms-excel";
	$zFORMAT["pdf"]="application/pdf";
	$zFORMAT["doc"]="application/vnd.openxmlformats-officedocument.wordprocessingml.document";
	$zFORMAT["docx"]="application/vnd.openxmlformats-officedocument.wordprocessingml.document";
	$zFORMAT["ppt"]="application/vnd.openxmlformats-officedocument.presentationml.presentation";
	$zFORMAT["pptx"]="application/vnd.openxmlformats-officedocument.presentationml.presentation";
	
	
	$zTYPE["application/vnd.openxmlformats-officedocument.wordprocessingml.document"]="fa-file-word-o";
	$zTYPE["application/vnd.openxmlformats-officedocument.wordprocessingml.document"]="fa-file-word-o";
	$zTYPE["application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"]="fa-file-excel-o";
	$zTYPE["application/vnd.openxmlformats-officedocument.presentationml.presentation"]="fa-file-powerpoint-o";
	$zTYPE["application/vnd.ms-excel"]="fa-file-excel-o";
	$zTYPE["application/pdf"]="fa-file-pdf-o";
	
	
	
	foreach ($array["RESULTS"] as $line){
		$data=$line["DATA"];
		$link=null;$icon="fas fa-file-code";$nid=0;$PATH=null;$TYPE=null;
		if(isset($line["PATH"])){$PATH=$line["PATH"];}
		if(isset($line["NID"])){$nid=$line["NID"];}
		if(isset($line["FORMAT"])){$format=strtolower($line["FORMAT"]);}
		if(isset($line["TYPE"])){$TYPE=$line["TYPE"];}
		if($TYPE==null){ if($format<>null){if(isset($zFORMAT[$format])){$TYPE=$zFORMAT[$format];}} }
		if(isset($zTYPE[$TYPE])){$icon=$zTYPE[$TYPE];$TYPE=null;}
		$time=$tpl->_ENGINE_parse_body($line["TIME"]);
		if($nid>0){
			
		if($GLOBALS["VERBOSE"]){echo "<br><strong>Replace [/home/xapian/mounts/{$CONFIG[$nid]["tfolder"]}/] in $PATH</strong><br>\n";}	
		$PATH=str_replace("/home/xapian/mounts/{$CONFIG[$nid]["tfolder"]}/", "/ID-$nid/", $PATH);$line["PATH"]=$PATH;}
		if($GLOBALS["VERBOSE"]){ while (list ($num, $zligne) = each ($line) ){$data=$data."<li> $num: $zligne</li>";} }
		
		if(preg_match("#^\/ID-([0-9]+)\/(.+)#", urldecode($PATH),$re)){
			$ID=$re[1];
			$paths=$re[2];
			
			unset($line["DATA"]);
			$zlink="{$PREFIX[$ID]}/$paths";
			$line["LINK"]=$LINK[$ID]."/$paths";
			$encoded=base64_encode(serialize($line));
			
			$link="<a href=\"javascript:Loadjs('$page?zoom-js=$encoded');\" style='color:#1A0DAB;text-decoration:underline'>";

			if($XapianAllowDownloads==1){
				$link="<a href=\"javascript:Loadjs('$page?download-js=$ID&info=$encoded');\" style='color:#1A0DAB;text-decoration:underline'>";
			}
			
			if($data==null){$data=$line["LINK"];}
			
			
		}else{
			if($GLOBALS["VERBOSE"]){echo "<br><strong>Unable to match #^\/ID-([0-9]+)\/(.+)# in $PATH</strong>\n";}
		}
		
		
		$f[]="<hr class='hr-line-dashed'>";
		$f[]="<div class=search-result>";
		$f[]="<H3><i class='fa $icon' style='color:black'></i>&nbsp;{$line["PERCENT"]}%&nbsp; $link". urldecode(basename($PATH))."</a></H3>";
		$f[]="<div class='search-link'>{$line["SIZE"]} - $time $TYPE</div>";
		$f[]="<p>$data</p>";		
		
	
		
		$f[]="</div>";
	}
	
	
	$f[]="    </div>";
	$f[]="</div>";
	$f[]="</div>";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="";
	$f[]="</html>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
	
}
