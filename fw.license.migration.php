<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["start-migration"])){start_migration();exit;}
if(isset($_GET["start-migration-js"])){start_migration_js();exit;}
js();


function js(){
    $page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{license_migration_title}", "$page?popup=yes",600);
	
	
}
function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$sock = new sockets();

	$uuid = $sock->GET_INFO("SYSTEMID");
	$html="
    <p style='font-size:16px'>{license_migration_explain}</p>
	<H2>{insert_token}:</H2>
	<input type='text' name='token' placeholder='{insert_token}' id='token' class='form-control' value=\"\">
	<div style='text-align:left;margin-top:20px'>
	<label class=\"btn btn btn-primary\" OnClick=\"GetToken$t()\"><i class='far fa-key'></i> 1) {get_private_key} </label>
	<label class=\"btn btn btn-primary\" OnClick=\"StartLicenseMigration$t()\"><i class='fal fa-play'></i> 2) {start_migration} </label>

	</diV>
	
<script>
	var xHide$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	RTMMailHide();
}

function StartLicenseMigration$t(){
	let token = $('#token').val();
	$.ajax({

		type: 'POST',
		url: 'https://licensing.artica.center/api/get/key',
		data: {'X-API-KEY': token,'uuid':'$uuid'},
		cache: false, 
		success: function (bucket) {
		  if (bucket.status == false) {
			  alert(bucket.message)
		  }
		  if (bucket.status == true) {
			Loadjs('$page?start-migration-js=yes&token='+token+'&data='+bucket.message)
		  }
		},
		error:function(bucket) {alert(bucket.responseText); }
	  });  

}

function GetToken$t(){
	window.open(
		'https://licensing.artica.center/dashboard/endpoint/create/token/$uuid',
		'MsgWindow', 'width=800,height=500,top=80'
	  );
}

function Hide$t(){
var XHR = new XHRConnection();
XHR.appendData('HIDE', 1);
XHR.sendAndLoad('$page', 'POST',xHide$t);
}
</script>
";



echo $tpl->_ENGINE_parse_body($html);

}

function start_migration_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$data = $_GET['data'];
	$token=$_GET["token"];
	$tpl->js_dialog5("{license_migration_title}", "$page?start-migration=yes&token=$token&data=$data");
}

function start_migration() {
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
    $token=$_GET["token"];

    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $LicenseInfos["X-API-KEY"]=$token;
    $NewLicenseInfos=base64_encode(serialize($LicenseInfos));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LicenseInfos",$NewLicenseInfos);

    $WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));

    $WizardSavedSettings["X-API-KEY"]=$token;
    $NewWizardSavedSettings=base64_encode(serialize($WizardSavedSettings));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WizardSavedSettings",$NewWizardSavedSettings);




	$sock->SaveConfigFile($_GET['data'],'TokenRequest');
	$sock->SET_INFO("NewLicServer", 1);
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/artica.license.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/artica_license.txt";
	$ARRAY["CMD"]="services.php?license-migration=yes";
	$ARRAY["TITLE"]="{license_migration_title}";
	$ARRAY["AFTER"]="location.reload(true);";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=migration-progress')";
	$html="<div id='migration-progress'></div>
	<script>$jsrestart</script>";
	echo $html;
}

function HIDE(){
	$sock=new sockets();
	//$sock->SET_INFO("DidYouKnowMobieApp", 1);
	
}