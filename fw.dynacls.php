<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET['app-status'])){app_status();exit;}
if(isset($_GET["flot1"])){flot1();exit;}
if(isset($_GET["flot2"])){flot2();exit;}
if(isset($_GET["flot3"])){flot3();exit;}
if(isset($_GET["flot4"])){flot4();exit;}
if(isset($_GET["flot5"])){flot5();exit;}
xgen();



function xgen(){
$OPENVPN=false;	
$users=new usersMenus();
$page=CurrentPageName();
$t=time();
$tpl=new template_admin();
$dynamic_acls_newbee_explain=str_replace("%s", count($_SESSION["SQUID_DYNAMIC_ACLS"]), $tpl->_ENGINE_parse_body("{dynamic_acls_newbee_explain}"));
$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-8\">
		<h1 class=ng-binding>{dynamic_acls_newbee}</h1>
		<div>$dynamic_acls_newbee_explain</div>
		<div class=\"col-sm-4\"></div>
	</div>
</div>
<div  class='row border-bottom white-bg'>
	<div class='col-lg-12' id='rules-list'></div>

</div>


<script>
	LoadAjaxSilent('rules-list','$page?app-status=yes');
</script>
";

if(isset($_GET["content"])) {
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	return;
}

$tpl=new template_admin("{proxy_rules}",$html);
$tpl->DynaAcls=true;
echo $tpl->build_firewall();

}


function app_status(){
	$q=new mysql_squid_builder();
	while (list ($gpid, $val) = each ($_SESSION["SQUID_DYNAMIC_ACLS"]) ){
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName,params FROM webfilters_sqgroups WHERE ID=$gpid"));
		$params=unserialize(base64_decode($ligne["params"]));
		
		$html[]="
			
				<div class='jumbotron' style='margin:10px'>
                        <h1 class=text-capitalize>{$ligne["GroupName"]}</h1>
                        <p>{$params["dynamic_description"]}</p>
                        <p><a class='btn btn-primary btn-lg' role='button' OnClick=\"javascript:LoadAjaxSilent('MainContent','fw.dynacls.rule.php?gpid=$gpid');\">{manage}</a>
                        </p>
                    </div>";
		}
	
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
		
}






function flot1(){
	
	$data=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/FIREWALL.IPAUDIT.24H"));
	$tpl=new template_admin();
	$tpl->graph_date_line_sizeMB($data["ip1bytes"],$_GET["id"]);
	
			
}
function flot2(){
	$data=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/FIREWALL.IPAUDIT.24H"));
	$tpl=new template_admin();
	$tpl->graph_date_line_sizeMB($data["ip2bytes"],$_GET["id"]);
}

function flot3(){
	$data=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNStatsnClients"));
	$tpl=new template_admin();
	$tpl->graph_date_line_int($data,$_GET["id"]);
}
function flot4(){
	$data=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNStatsBytesIn"));
	$tpl=new template_admin();
	$tpl->graph_date_line_sizeKB($data,$_GET["id"]);
}
function flot5(){
	$data=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNStatsBytesOut"));
	$tpl=new template_admin();
	$tpl->graph_date_line_sizeKB($data,$_GET["id"]);
}



