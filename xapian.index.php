<?php
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.xapian.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.crypt.php');
	if(isset($_GET["xapian-file"])){dowloadfile();exit;}
	if(isset($_GET["logon-js"])){login_js();exit;}
	if(isset($_GET["login-popup"])){login_popup();exit;}	
	if(isset($_POST["username"])){login_validate();exit;}
	if(isset($_GET["loggoff-js"])){logoff_js();exit;}
	if(isset($_REQUEST["xapsearch"])){xapsearch();exit;}
	if(isset($_GET["css"])){css();exit;}

	
	
page();




function login_js(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$login=$tpl->_ENGINE_parse_body("{login}");
	$html="YahooWin('550','$page?login-popup=yes','$login')";
	echo $html;
	
}

function logoff_js(){
	$page=CurrentPageName();
	$t=time();
	echo "
		function Logoff$t(){
			Delete_Cookie('uid', '/', '');
			document.location.href='$page'
		}
	
	
	Logoff$t();";
	
}


function login_popup(){
	$tpl=new templates();
	$page=CurrentPageName();		
	
	$html="
	<center style='margin-top:50px;margin-bottom:50px'>
	
	<table style='width:70%' class=form>
	<tr><td colspan=2><p>&nbsp;</p></td></tr>
	<tr>
		<td style='font-size:18px;' width=1% nowrap align='right'>{username}:</td>
		<td><input type='text' name='user-xapian' id='user-xapian'
		style='width:250px;border:3px solid #CCCCCC;padding:5px;font-size:22px;font-weight:bold;border-radius: 5px 5px 5px 5px;'
		OnKeyPress=\"javascript:SubMitLogon(event);\">
		</td>
	</tr>
	<tr>
		<td style='font-size:18px;' width=1% nowrap align='right'>{password}:</td>
		<td><input type='password' name='password-xapian' id='password-xapian'
		style='width:250px;border:3px solid #CCCCCC;padding:5px;font-size:22px;font-weight:bold;border-radius: 5px 5px 5px 5px;'
		OnKeyPress=\"javascript:SubMitLogon(event);\">
		</td>
	</tr>	
	<tr><td colspan=2><p>&nbsp;</p></td></tr>
	</table>
	<script>
		function SubMitLogon(e){
			if(checkEnter(e)){EnterLogon();}
		}
		
		var x_EnterLogon= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		Set_Cookie('uid', document.getElementById('user-xapian').value, '3600', '/', '', '');
		Set_Cookie('XAPIAN-LANG', document.getElementById('lang').value, '3600', '/', '', '');
		
		YahooWinHide();
		document.location.href='$page'
		}		
		
		function EnterLogon(){
			var password=MD5(document.getElementById('password-xapian').value);
			var XHR = new XHRConnection();
			XHR.appendData('username',document.getElementById('user-xapian').value);
			XHR.appendData('password',password);
			XHR.sendAndLoad('$page', 'POST',x_EnterLogon);	
		}
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function login_validate(){
	
	$ct=new user($_POST["username"]);
	if(md5($ct->password)<>$_POST["password"]){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{failed}");
	}
	
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
	$tpl=new templates();
	$sock=new sockets();
	$XapianSearchTitle=$sock->GET_INFO("XapianSearchTitle");
	if($XapianSearchTitle==null){$XapianSearchTitle="Xapian Desktop {search}";}
	$XapianRemoveLangage=$sock->GET_INFO("XapianRemoveLangage");
	$XapianRemoveLogon=$sock->GET_INFO("XapianRemoveLogon");	
	

	
	if(!is_numeric($XapianRemoveLangage)){$XapianRemoveLangage=0;}
	if(!is_numeric($XapianRemoveLogon)){$XapianRemoveLogon=0;}	
	
	$XapianSearchTitle=$tpl->_ENGINE_parse_body("$XapianSearchTitle");
	$p=new pagebuilder();
	$page=CurrentPageName();
	$search=$tpl->_ENGINE_parse_body("{search}");
	$languageF=$tpl->_ENGINE_parse_body("{language}");
	$js=$p->jsArtica();
	$info=null;
	$cssJq=$p->JqueryUiCss("artica-theme");
	$Yahoo=$p->YahooBody();
	if (!extension_loaded('xapian')) {
		$s=php_ini_path();
		echo $tpl->_ENGINE_parse_body("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en-gb\" lang=\"en-gb\">
		<head>
			<title>$XapianSearchTitle</title>
			<link rel=\"stylesheet\" type=\"text/css\" rel=\"styleSheet\"  href=\"/ressources/templates/default/blurps.css\" />
			<link rel=\"stylesheet\" type=\"text/css\" href=\"$page?css=yes\" />
			<link href='http://fonts.googleapis.com/css?family=Questrial' rel='stylesheet' type='text/css'>
			
		</head>
		<body >
		
			<center style='margin:90px'>
				<table style='width:99%' class=form>
				<tr>
					<td width=1% valign='top'><img src='img/error-128.png'></td>
					<td valign='top' style='font-size:18px;font-family:Arial,Tahoma,serif'>{ERROR_XAPIAN_NOEXTSION}
					</td>
				</tr>
				</table>
				<div style='width:620px;margin-top:20px' class=form>$s</div>
			</center>
			
		</center>
		</body>
		</html>
		");
		return;
		
	}
	
	$l["none"]="none";
	$l["danish"]="danish";
	$l["dutch"]="dutch";
	$l["english"]="english";
	$l["finnish"]="finnish";
	$l["french"]="french";
	$l["german"]="german";
	$l["german2"]="german2";
	$l["hungarian"]="hungarian";
	$l["italian"]="italian";
	$l["kraaij_pohlmann"]="kraaij_pohlmann";
	$l["lovins"]="lovins";
	$l["norwegian"]="norwegian";
	$l["porter"]="porter";
	$l["portuguese"]="portuguese";
	$l["romanian"]="romanian";
	$l["russian"]="russian";
	$lang="english";
	$language=Field_array_Hash($l,"lang",$_COOKIE["XAPIAN-LANG"],null,null,0,"font-size:14px");
	$login=$tpl->_ENGINE_parse_body("{login}");
	$logged_as=$tpl->_ENGINE_parse_body("{logged_as}");
	$disconnect=$tpl->_ENGINE_parse_body("{disconnect}");
	if($_COOKIE["uid"]==null){
		$logon="<a href=\"javascript:Blurz();\" OnClick=\"Loadjs('$page?logon-js=yes')\" style='text-decoration:underline;font-weight:bold'>$login</a>";
		
	}else{
		$logon="<span style='font-weight:bold'>$logged_as: {$_COOKIE["uid"]}</span>&nbsp;<a href=\"javascript:Blurz();\" OnClick=\"Loadjs('$page?loggoff-js=yes')\" style='text-decoration:underline;font-weight:bold'>$disconnect</a>";
		
		if($_COOKIE["uid"]<>null){
			if(!is_dir("/usr/share/artica-postfix/LocalDatabases/xapian-{$_COOKIE["uid"]}")){
				$info=$tpl->_ENGINE_parse_body("&nbsp;<i style='font-size:11px'>{your_home_dir_seems_not_indexed}:$HomDirectory</i>");
			}	
		}		
		
	}
	
	$langs[]="<td width=1% nowrap>$languageF:</td>";
	$langs[]="<td width=1% nowrap>$language</td>";
	
	if($XapianRemoveLangage==1){$langs=array();}
	$infos="<td width=1% nowrap>$logon$info</td>";
	if($XapianRemoveLogon==1){$infos=null;$scriptfel="Delete_Cookie('uid', '/', '');";}
	
	
	$html="
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en-gb\" lang=\"en-gb\">
	<head>
		<title>$XapianSearchTitle</title>
		<link rel=\"stylesheet\" type=\"text/css\" rel=\"styleSheet\"  href=\"/ressources/templates/default/blurps.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"$cssJq\" />
		
		<link rel=\"stylesheet\" type=\"text/css\" href=\"$page?css=yes\" />
		<link href='http://fonts.googleapis.com/css?family=Questrial' rel='stylesheet' type='text/css'>
				
		$js
	</head>
	<body style='background-color:transparent'>
	$Yahoo
	<center style='margin-top:10px'>
	<table class=form style=' width: 99%;padding:50px'>
	<tr>
		<td style='font-size:22px;font-weight:bold;padding-top:15px' nowrap width=1% valign='top'>$XapianSearchTitle:</td>
		<td width=99% align='left'><input type='text' id='xapsearch' value='{$_COOKIE["XAP-SEARCH"]}' 
		style='font-size:18px;font-weight:bold;border:2px solid #CCCCCC;width:550px;padding:8px'
		OnKeyPress=\"javascript:InstantSearchQueryPress(event);\"
		>
		<table style='width:5%'>
		<tr>
			". @implode("\n", $langs)."
			$infos
		</tr>
		</table>
	</td>
	
</tr>
</table>
<div id='xapresults'></div>
	<script>
	var x_InstantSearchSave= function (obj) {
		var res=obj.responseText;
		document.getElementById('xapresults').innerHTML=res;
	}		
	
function InstantSearchQuery(){
		var xapsearch=document.getElementById('xapsearch').value;
		if(xapsearch.length<2){return;}
		var pp=encodeURIComponent(xapsearch);
		var XHR = new XHRConnection();
		document.getElementById('xapresults').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
		XHR.appendData('xapsearch',pp);
		if(document.getElementById('lang')){
			XHR.appendData('language',document.getElementById('lang').value);
			Set_Cookie('XAPIAN-LANG', document.getElementById('lang').value, '3600', '/', '', '');
		}
		Set_Cookie('XAP-SEARCH', document.getElementById('xapsearch').value, '3600', '/', '', '');
		XHR.sendAndLoad('$page', 'POST',x_InstantSearchSave);
		 		
	}
	
	
	
	function InstantSearchQueryPress(e){
		if(checkEnter(e)){InstantSearchQuery();}
	}
	
	InstantSearchQuery();
	$scriptfel	
	</script>	
	
</html>";

	
echo $html;
	
}

function status_xapiandb(){
	
	
	$html="<center style='margin-top:20px'><table style='width:50%' class=form>";
	
	foreach (glob("/usr/share/artica-postfix/LocalDatabases/samba.*.db",GLOB_ONLYDIR) as $directory) {
		$size=DIRSIZE_BYTES($directory);
		$size=FormatBytes($size/1024);
		$html=$html."
		<tr>
			<td style='font-size:16px' width=1% nowrap>". basename($directory).":</td>
			<td style='font-size:16px;font-weight:bold'>$size</td>
			
		</tr>
		";
	}

	foreach (glob("/usr/share/artica-postfix/LocalDatabases/xapian-*",GLOB_ONLYDIR) as $directory) {
		$size=DIRSIZE_BYTES($directory);
		$size=FormatBytes($size/1024);
		$html=$html."
		<tr>
			<td style='font-size:16px' width=1% nowrap>". basename($directory).":</td>
			<td style='font-size:16px;font-weight:bold'  align='left'>$size</td>
			
		</tr>
		";
	}

	echo $html."</table></center>";
	
}



function DIRSIZE_BYTES($directory){
		
		$cmd="du -s -b $directory 2>&1";
		
		
		exec($cmd,$datas);
		foreach ($datas as $a=>$b){
			if(preg_match("#^([0-9]+)\s+\/#",$b,$re)){
			return trim($re[1]);
			}
		}
		return 0;
	}

function xapsearch(){
	$tpl=new templates();
//$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	if(!isset($_POST["xapsearch"])){if(isset($_GET["xapsearch"])){$_POST["xapsearch"]=$_GET["xapsearch"];}}
	$xapsearch=url_decode_special_tool($_POST["xapsearch"]);
	$ldap=new clladp();
	$sock=new sockets();
	$XapianDisableAdm=$sock->GET_INFO("XapianDisableAdm");
	if(!is_numeric($XapianDisableAdm)){$XapianDisableAdm=0;}
	
	if($XapianDisableAdm==0){
		if(preg_match("#phpinfo:(.*?):(.*)#", $xapsearch,$re)){if(  strtolower($ldap->ldap_admin)==strtolower($re[1])  ){if(trim($re[2])==trim($ldap->ldap_password)){$s=php_ini_path();echo "<div style='width:620px;margin-top:20px' class=form>$s</div>";}}echo $tpl->_ENGINE_parse_body("<strong style='color:#d32d2d'>{failed}</strong>");return;}
		if(preg_match("#status xapiandb\s+(.*?):(.*)#", $xapsearch,$re)){if(  strtolower($ldap->ldap_admin)==strtolower($re[1])  ){if(trim($re[2])==trim($ldap->ldap_password)){status_xapiandb();}}return;}
		if(preg_match("#chtitle\s+\"(.*?)\"\s+(.*?):(.*)#", trim($xapsearch),$re)){
			if(  strtolower($ldap->ldap_admin)==strtolower($re[2])  ){
				if(trim($re[3])==trim($ldap->ldap_password)){
					$sock=new sockets();
					$sock->SET_INFO("XapianSearchTitle",$re[1]);
					echo $tpl->_ENGINE_parse_body("<center style='margin-top:20px'>
					<strong style='font-size:22px;color:#d32d2d'>{you_have_to_reload_webpage}</strong></center>");
				}
				return;
			}
		}
	}
	
	$userid=$_COOKIE["uid"];
	$ct=new user($userid);
	$q=new mysql();
	unset($_SESSION["xapian_folders"]);
	if(!isset($_SESSION["xapian_folders"])){
		$sql="SELECT * FROM xapian_folders";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysqli_fetch_assoc($results)) {
			$directory=string_to_regex($ligne["directory"]);
			$_SESSION["xapian_folders"][$directory]["DOWN"]=$ligne["AllowDownload"];
			$_SESSION["xapian_folders"][$directory]["FULLP"]=$ligne["DiplayFullPath"];
		}	
	}	
	
	if($userid<>null){
		$samba=new samba();
		$samba_folders=$samba->GetUsrsRights($userid);
	}
	
	$users=new usersMenus();
	
	writelogs("$userid ->AllowXapianDownload \"$users->AllowXapianDownload\"",__FUNCTION__,__FILE__);;
	
	
	
	$xapian=new XapianSearch($_POST["lang"]);
	
	
	if($_COOKIE["uid"]<>null){
		if(is_dir("/usr/share/artica-postfix/LocalDatabases/xapian-{$_COOKIE["uid"]}")){
			$xapian->add_database("/usr/share/artica-postfix/LocalDatabases/xapian-{$_COOKIE["uid"]}");
		}	
	}	
	
	foreach (glob("/usr/share/artica-postfix/LocalDatabases/samba.*.db",GLOB_ONLYDIR) as $directory) {
		$xapian->add_database($directory);
	}
	
	
	$current=$_GET["p"];
	if($current==null){$current=0;}

	
	$xapian->terms=$xapsearch;
	$xapian->start=$current;
	if(count($xapian->databases)>0){
		writelogs("Added ".count($xapian->databases)." databases...",__FUNCTION__,__FILE__,__LINE__);
		$array=$xapian->search();
	}

	$maxdocs=$array["ESTIMATED"];
	$ssery_results=$array["DESCRIPTION"];

	if($maxdocs>10){
		$tabs=generateTabs($maxdocs,$current);
	}	
	if($ct->password==null){$ct->password=$ldap->ldap_password;}
	
	
	
	if(is_array($array["RESULTS"])){
		while (list ($num, $arr) = each ($array["RESULTS"]) ){
			$tr[]=FormatResponse($arr,$users,$ct->password);
		}
	}
	
	$database=$tpl->_ENGINE_parse_body("{database}");
	if(count($xapian->ParsedDatabases)>1){$database=$tpl->_ENGINE_parse_body("{databases}");}
	$html="<div style='font-size:16px;font-weight:bold;margin:5px;text-align:right'>
	{found} {$array["ESTIMATED"]} {documents} {in} <strong>" . count($xapian->ParsedDatabases)." $database</strong></div>";
	
	$tpl=new templates();
	if(is_array($tr)){echo $tpl->_ENGINE_parse_body($html.$tabs.implode("\n",$tr));}else{
		if($_GET["tmpmax"]>0){
			$tabs=generateTabs($maxdocs,$current);
		}
		
		echo $tpl->_ENGINE_parse_body($html.$tabs."<p style=\"font-size:16px;font-weight:bold;margin:10px\">{ERR_NO_DOC_FOUND} &laquo;$ssery_results&raquo;</p>"); 
	
		}
	
	
}

function generateTabs($max,$current){
	$page=CurrentPageName();
	$start=0;
	$nb_pages=round($max/10,0);
	$xapsearch=urlencode($_COOKIE["XAP-SEARCH"]);
	
if($nb_pages>10){
	if(isset($_GET["next"])){$start=$_GET["next"];}
	if(isset($_GET["forward"])){$start=$_GET["forward"];}	
	
	
	$next_link=$_GET["next"]+10;
	$next="<li><a href=\"javascript:LoadAjax('xapresults','$page?p=$next_link&xapsearch=$xapsearch&tmpmax=$max&next=$next_link&nbpages=$nb_pages')\" $class>&raquo;&raquo;</a></li>";

	$forward_link=$_GET["forward"]-10;
	if($forward_link<0){$forward_link=0;}
	$reverse="<li><a href=\"javascript:LoadAjax('xapresults','$page?p=$forward_link&xapsearch=$xapsearch&tmpmax=$max&forward=$forward_link&nbpages=$nb_pages')\" $class>&laquo;&laquo;</a></li>";
	
}
$count=0;

for($i=$start;$i<($start+9);$i++){
	$p=$i+1;
	$arr[$i]="{page} ".$p;
}

if($next_link>$nb_pages){$next_link=null;}
if($next_link<20){$reverse=null;}
$nextp=$_GET["next"];
	$html=$reverse;
    foreach ($arr as $num=>$ligne){
		if($current==$num){$class="id=tab_current";}else{$class=null;}
		$html=$html . "<li><a href=\"javascript:LoadAjax('xapresults','$page?p=$num&xapsearch=$xapsearch&tmpmax=$max&next=$nextp')\" $class>$ligne</a></li>\n";
			
		}
		$html=$html . $next;
	return "<div id=tablist>$html</div>";		
	
}

function AllowDownload($path){
	reset($_SESSION["xapian_folders"]);
	while (list ($pattern, $array) = each ($_SESSION["xapian_folders"]) ){
		
		if(preg_match("#^$pattern#i", $path)){
			return $array["DOWN"];
		}
	}
	
	return 0;
	
}
function AllowFullPath($path){
	reset($_SESSION["xapian_folders"]);
	while (list ($pattern, $array) = each ($_SESSION["xapian_folders"]) ){
		if(preg_match("#^$pattern#i", $path)){
			//echo "<H1>M $pattern {$array["FULLP"]}</H1>";
			return $array["FULLP"];
		}
	}
	
	return 0;
	
}
function FormatResponse($ligne,$users,$pass){
$page=CurrentPageName();	
$explainPath=null;
	$ligne["PATH"]=trim(urldecode($ligne["PATH"]));
	$f=new filesClass();
	writelogs("Crypt: with $pass",__FUNCTION__,__FILE__,__LINE__);
	$crypt=new SimpleCrypt($pass);
	$uri1="<a href=\"$page?xapian-file=".base64_encode($crypt->encrypt($ligne["PATH"]))."\">";
	$uri=$uri1;
	$text_deco1="color:#0000CC;text-decoration:underline";
	$text_deco=$text_deco1;
	
	if(preg_match("#^file.*#", $ligne["PATH"])){
		$uriE=$ligne["PATH"];
		$uriE=str_replace("file://///", "file://", $uriE);
		$uri="<a href=\"$uriE\" target=_new>";
		$uri1=$uri;
		if(preg_match("#file:\/\/\/\/\/(.+?)\/(.+?)\/(.*?)$#",$ligne["PATH"],$re)){
			$host=$re[1];
			$SharedDir=$re[2];
			$path=dirname($re[3]);
			$explainPath="&nbsp;|&nbsp;<strong style='color:black'>{server}: &laquo;$host&raquo; {folder}: &laquo;$SharedDir&raquo; {path}: &laquo;$path&raquo;</strong>";
		}
	}	
	
	if($_COOKIE["uid"]==null){
		$uri=null;
		$text_deco="font-weight:bold";
	}
	
	
	$ligne["PATH"]=str_replace("'",'`',$ligne["PATH"]);
	$title=$ligne["DATA"];
	if(strlen($title)>200){
		$title=substr($ligne["DATA"],0,200)."...";
	}
	$pourcent="<span style='font-size:28px;text-decoration:none;font-weight:bold;color:black'>{$ligne["PERCENT"]}%&nbsp;</span>";
	
	$body=$ligne["DATA"];
	
	
	$img="img/ext/unknown_small.gif";
	
	$PATH=$ligne["PATH"];
	
	
	$AllowFullPath=AllowFullPath(dirname($PATH));
	$AllowDownload=AllowDownload(dirname($PATH));
	
	
	$file=$PATH;
	if($AllowFullPath==0){
		$file=basename($ligne["PATH"]);
	}
	
	if($AllowDownload==1){
		$text_deco=$text_deco1;$uri=$uri1;
	}
	
	$ext=$f->Get_extension(strtolower($file));
	if(is_file("img/ext/{$ext}_small.gif")){
			$img="img/ext/{$ext}_small.gif";
		}
		
	if(preg_match("#^http.*?#", $ligne["PATH"])){
		$uri="<a href=\"{$ligne["PATH"]}\" target=_new>";
		$text_deco=$text_deco1;
		$img="img/icon-link.png";
	}
	
	$title="$uri<span style='$text_deco;font-size:18px'>$title</span></a>";
	$html="
	
	<table style='width:99%;margin-top:6px'>
	<tr>
		<td>
		<table style='width:100%'>
		<tr>
			
			<td valign='top' width=1%><div style='width:85px'>$pourcent</div></td>
			<td valign='top' width=1% style='background-color:#CCCCCC'>
			<div style='width:30px'>
				<center style='background-color:white;margin-top:3px'><img src='$img' style='margin:5px' align='center'></center>
			</div>
			</td>
			<td valign='top' align='left'>$title
				<div><span style='font-size:small;color:#676767;'>&laquo&nbsp;<strong>{$file}&nbsp;-&nbsp;{size}:{$ligne["SIZE"]}$explainPath</strong>&nbsp;&raquo;&nbsp;-&nbsp;{$ligne["TIME"]}</span></div>
				<div style='font-size:13.5px'>$body</div>
				<div style='font-size:small;color:green;' align='left'>{$ligne["TYPE"]} ({$ligne["SIZE"]})</div>
		</tr>
	
		</table>
	</tr>
	</table>
	";
	
	return $html;	
	
	
	
}


function tests(){
include_once("ressources/class.xapian.inc");
// Open the database for searching.
try {
    $database = new XapianDatabase("/home/dtouzeau/Documents/doc1.db");
    $database1=new XapianDatabase("/home/dtouzeau/Documents/doc1.db");
    
	$database->add_database($database1);
    // Start an enquire session.
    $enquire = new XapianEnquire($database);

    // Combine the rest of the command line arguments with spaces between
    // them, so that simple queries don't have to be quoted at the shell
    // level.
    $query_string = "david";

    $qp = new XapianQueryParser();
    $stemmer = new XapianStem("english");
    $qp->set_stemmer($stemmer);
    $qp->set_database($database);
    $qp->set_stemming_strategy(XapianQueryParser::STEM_SOME);
    $query = $qp->parse_query($query_string);
    print "Parsed query is: {$query->get_description()}\n";

    // Find the top 10 results for the query.
    $enquire->set_query($query);
    $matches = $enquire->get_mset(0, 10);

    // Display the results.
    print "{$matches->get_matches_estimated()} results found:\n";

    $i = $matches->begin();
    while (!$i->equals($matches->end())) {
	$n = $i->get_rank() + 1;
	$data = $i->get_document()->get_data();
	print "$n: {$i->get_percent()}% docid={$i->get_docid()} [$data]\n\n";
	$i->next();
    }
} catch (Exception $e) {
    print $e->getMessage() . "\n";
    exit(1);
}

}

function dowloadfile(){
	
if(!isset($_GET["xapian-file"])){return;}

	if(($_COOKIE["uid"]==null) OR ($_COOKIE["uid"]==-100)){
		$ldap=new clladp();
		$pass=$ldap->ldap_password;
	}else{	
		$ct=new user($_COOKIE["uid"]);
		$pass=$ct->password;
	}
	
	
	
	$cr=new SimpleCrypt($pass);
	$crypted=base64_decode($_GET["xapian-file"]);
	
	$path=$cr->decrypt(base64_decode($_GET["xapian-file"]));
	writelogs("Receive crypted file: $path ",__FUNCTION__,__FILE__,__LINE__);
	if(!is_file($path)){die("DIE " .__FILE__." Line: ".__LINE__);}
	if(strpos($path,'..')>0){die('HACK: ..');}
	$file=basename($path);
	$sock=new sockets();
	
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".base64_encode($path)));
	header('Content-type: '.$content_type);
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");	
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé	
	$fsize = filesize($path); 
	header("Content-Length: ".$fsize); 
	ob_clean();
	flush();
	readfile($path);	
	
}

function css(){
	include_once("ressources/class.browser.detection.inc");
	$browser= browser_detection("browser");
	
	$form=".form{
background: -moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
border: 1px solid #DDDDDD;
border-radius: 5px 5px 5px 5px;
box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
margin: 5px;
padding: 5px;}";
	
	if($browser=="ie"){
	$form="
	.form{
		padding: 8px;
		margin: 5px;
		padding: 5px;		
	}";		
		
	}
	
	
header('Content-Type: text/css; charset=UTF-8');
$f[]="body {";
$f[]="     margin: 0;";
$f[]="    font-family:'Questrial',Tahoma,Arial";
$f[]="}";
$f[]="
$form

td.e{
	font-weight:bold;
	text-transform:capitalize;
	text-align:right;
	white-space: nowrap;
	font-size:12px;
}
td.v{
	font-weight:normal;
	text-align:left;
	font-size:12px;
}

tr.v{
	font-weight:normal;
	text-align:left;
	font-size:8px;
}
tr.h{
	font-weight:bold;
	text-transform:capitalize;
	text-align:left;
	white-space: nowrap;
	font-size:14px;
	background-color:#CCCCCC;
}
h1.p{
	font-weight:bold;
	font-size:16px;
}
.center h1{
	font-weight:bold;
	text-align:left;
	font-size:16px;
}
.center h2{
	font-weight:bold;
	text-align:left;
	font-size:14px;
}
.center hr{
	padding:0px;margin:0px;border:0px;
}

.center th{
	font-weight:bold;
	text-align:left;
	font-size:11px;
	background-color:#F0EDED;
}
#tablist{
border-bottom:1px solid #DEDEDE;
font-size:16px;
font-size-adjust:none;
font-stretch:normal;
font-style:normal;
font-variant:normal;
font-weight:normal;
line-height:normal;
margin-left:0px;
padding:3px 0px;
}
#tablist li {
background-image:none;
display:inline;
list-style-type:none;
margin:0px;
padding:0px;
}
#tablist li a {
background:#F0F0F0 none repeat scroll 0%;
border:1px solid #DEDEDE;
margin-left:3px;
padding:3px 0.5em;
text-decoration:none;
}
#tablist li a:link {
color:#444488;
}
#tablist li a:visited {
color:#666677;
}
#tablist li a:hover {
background:#FFFFFF none repeat scroll 0%;
border-color:#DEDEDE;
color:#000000;
}
#tablist li a#tab_current {
background:white none repeat scroll 0%;
border-bottom:1px solid white;
}";


echo @implode("", $f);
}




?>