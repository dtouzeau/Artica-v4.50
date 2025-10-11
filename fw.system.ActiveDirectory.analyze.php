<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["test-nettestjoin"])){test_testjoin();exit;}
if(isset($_GET["test-netadsinfo"])){test_netadsinfo();exit;}
if(isset($_GET["test-netrpcinfo"])){test_netrpcinfo();exit;}
if(isset($_GET["test-wbinfoalldom"])){test_wbinfoalldom();exit();}
if(isset($_GET["test-wbinfomoinst"])){test_wbinfomoinst();exit;}
if(isset($_GET["test-wbinfomoinsa"])){test_wbinfomoinsa();exit;}

js();

function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog1("{analyze}", "$page?popup=yes");
}

function popup(){
	$t=time();
	$page=CurrentPageName();
	echo "<div id='1-$t' style='margin-top:20px'>
	<script>LoadAjax('1-$t','$page?test-nettestjoin=yes');</script>";
}

function test_testjoin(){
	if(!isset($_GET["time"])){$_GET["time"]=time();}
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
    $WindowsActiveDirectoryKerberos=intval($sock->GET_INFO("WindowsActiveDirectoryKerberos"));

	if($WindowsActiveDirectoryKerberos==0){
		$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netrpctestjoin=yes")));
		$test_results=test_results($datas);
	}else{
		$datas[]="succeeded Native kerberos used";
		$test_results=array($datas);
	}
	$class="navy-bg";
	if($test_results[1]){$class="red-bg";}
	$t=time();
	$html="<div class='widget $class p-xl'>
	<h2>{is_connected}?</H2>
	
	<p>".@implode("\n", $test_results[0])."</p>
	</div>
	<div id='$t' style='margin-top:20px'>
	<script>
	LoadAjaxTiny('$t','$page?test-netadsinfo=yes&time={$_GET["time"]}');
	</script>";

	echo $tpl->_ENGINE_parse_body($html);

}
function test_netadsinfo(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
    $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
    if($UseNativeKerberosAuth==1){$WindowsActiveDirectoryKerberos=1;}

	if($WindowsActiveDirectoryKerberos==0){
		$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netadsinfo=yes")));
		$test_results=test_results($datas);
	
	}else{
		$sock=new sockets();
		$sock->getFrameWork("squid2.php?cached-kerberos-tickets=yes");
		$dataZ=unserialize(@file_get_contents(PROGRESS_DIR."/kerberos-tickets-squid"));

		if(count($dataZ)<2){
			$datas[]="Kerberos tickets {failed}";
			$test_results=test_results($datas);
		}else{
            foreach ($dataZ as $DB){
			$date=strtotime($DB["DATE"]);;
			$zdate=$tpl->time_to_date($date,true);
			$KVNO=$DB["NUM"];
			$TICKET=$DB["ticket"];
			$datas[]="{success}: Ticket:$zdate $KVNO $TICKET";
			}
		$test_results=test_results($datas);

		}
	}
	
	$class="navy-bg";
	if($test_results[1]){$class="red-bg";}
	$t=time();
	$html="<div class='widget $class p-xl'>
	<h2>Active Directory Infos:</H2>
	
	<p>".@implode("\n", $test_results[0])."</p>
	</div>
	<div id='$t-netrpcinfo' style='margin-top:20px;color:white'>
	<script>
	LoadAjaxTiny('$t-netrpcinfo','$page?test-netrpcinfo=yes&time={$_GET["time"]}');
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
}
function test_netrpcinfo(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
	$array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
	$KerbAuthSMBV2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthSMBV2"));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];



	$cmdline=base64_encode(serialize($AR));

	if($WindowsActiveDirectoryKerberos==0){
		$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netrpcinfo=yes&auth=$cmdline")));
	}else{
		$datas[]="succeeded Native kerberos used";
	}

	$test_results=test_results($datas);
	$class="navy-bg";
	if($test_results[1]){$class="red-bg";}
	$t=time();
	$html="<div class='widget $class p-xl'>
	<h2>RPC Infos:</H2>
	<p style='color:white'>".@implode("\n", $test_results[0])."</p>
	</div>
	<div id='$t-wbinfoalldom' style='margin-top:20px'>
	<script>
	LoadAjaxTiny('$t-wbinfoalldom','$page?test-wbinfoalldom=yes&time={$_GET["time"]}');
	</script>";
	
		echo $tpl->_ENGINE_parse_body($html);

}
function test_wbinfoalldom(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];



	$cmdline=base64_encode(serialize($AR));
	$html="<hr><div style='font-size:18px'>";
	if($WindowsActiveDirectoryKerberos==0){
		$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfoalldom=yes&auth=$cmdline")));
	}else{
		$datas[]="succeeded Native kerberos used";
	}
	
	$test_results=test_results($datas);
	$class="navy-bg";
	if($test_results[1]){$class="red-bg";}

	$t=time();
	$html="<div class='widget $class p-xl'>
	<h2>Domains:</H2>
	<p>".@implode("\n", $test_results[0])."</p>
	</div>
	<div id='$t-wbinfomoinst' style='margin-top:20px'>
	<script>
	LoadAjaxTiny('$t-wbinfomoinst','$page?test-wbinfomoinst=yes&time={$_GET["time"]}');
	</script>";

	echo $tpl->_ENGINE_parse_body($html);

}
function test_wbinfomoinst(){
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}

	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	$cmdline=base64_encode(serialize($AR));
	$html="<hr><div style='font-size:18px'>";
	if($WindowsActiveDirectoryKerberos==0){
		$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfomoinst=yes&auth=$cmdline")));
	}else{
		$datas[]="succeeded Native kerberos used";
	}
	
	$test_results=test_results($datas);
	$class="navy-bg";
	if($test_results[1]){$class="red-bg";}
	
	$t=time();
	$html="<div class='widget $class p-xl'>
	<h2>Check shared secret:</H2>
	<p>".@implode("\n", $test_results[0])."</p>
	</div>
	<div id='$t-wbinfomoinsa' style='margin-top:20px'>
	<script>
	LoadAjaxTiny('$t-wbinfomoinsa','$page?test-wbinfomoinsa=yes&time={$_GET["time"]}');
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);

}
function test_wbinfomoinsa(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));

	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];

	$cmdline=base64_encode(serialize($AR));
	$html="<hr><div style='font-size:18px'>";
	if($WindowsActiveDirectoryKerberos==0){
		$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfomoinsa=yes&auth=$cmdline$viaSmamba")));
	}else{
		$datas[]="succeeded Native kerberos used";
	}
	$test_results=test_results($datas);
	$class="navy-bg";
	if($test_results[1]){$class="red-bg";}
	
	$t=time();
	$html="<div class='widget $class p-xl'>
	<h2>NTLM Auth:</H2>
	<p>".@implode("\n", $test_results[0])."</p>
	</div>
	<script>
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);

}



function test_results($array){
	$tpl=new templates();
	$html=null;
	$ERROR=false;
	foreach ($array as $num=>$ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$color="black";
		if(preg_match("#No logon servers#", $ligne)){$ERROR=true;}
		if(preg_match("#invalid permissions#", $ligne)){$ERROR=true;}
		if(preg_match("#No logon#", $ligne)){$ERROR=true;$ligne=$ligne.$tpl->_ENGINE_parse_body("<br> {should_change_ad_dns}");}
		if(preg_match("#No trusted SAM#i", $ligne)){$ERROR=true;}
		if(preg_match("#is not valid#i", $ligne)){$ERROR=true;}
		if(preg_match("#Improperly#i", $ligne)){$ERROR=true;}
		if(preg_match("#(UNSUCCESSFUL|FAILURE|NO_TRUST)#i", $ligne)){$ERROR=true;}
		if(preg_match("#(invalid credential|not correct)#i", $ligne)){$ERROR=true;}
		if(preg_match("#Could not authenticate user\s+.+?\%(.+?)\s+with plaintext#i",$ligne,$re)){$ligne=str_replace($re[1], "*****", $ligne);$ERROR=true;}
		if(preg_match("#Could not#i", $ligne)){$ERROR=true;}
		if(preg_match("#failed#i", $ligne)){$ERROR=true;}
		if(preg_match("#_CANT_#i", $ligne)){$ERROR=true;}
		if(preg_match("#no realm or workgroup#i", $ligne)){$ERROR=true;}
		
		if($color=="black"){
			if(preg_match("#^(.+?):\s+(.+)#", $ligne,$re)){$ligne="{$re[1]}:&nbsp;<span style='font-weight:bold'>{$re[2]}</span>";}
		}
		$html[]="<div>$ligne</div>";
	}
	return array($html,$ERROR);
}