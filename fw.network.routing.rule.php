<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.routing.ui.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){rtui_legacy_guard("html");popup();exit;}
if(isset($_GET["route-main"])){rtui_legacy_guard("html");route_main();exit;}
if(isset($_POST["RouteName"])){rtui_legacy_guard("post");route_save();exit;}
if(isset($_POST["toto"])){exit();}
rtui_legacy_guard("js");
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["ID"]);
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    if($ID<>999999) {
        $ligne = $q->mysqli_fetch_array("SELECT `RouteName`,nic FROM routing_rules WHERE ID='$ID'");
    }
    $NetworkAdvancedRouting=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkAdvancedRouting"));
    if($ID==999999){
        $ligne["RouteName"]="{global_rules}";
    }

	$title="{routing_table}: {$ligne["RouteName"]}";

	if($NetworkAdvancedRouting==0){$title="{routing_table}: main {for} {$ligne["nic"]}";}
	$tpl->js_dialog1($title, "$page?popup=$ID");
}

function popup(){
    $NetworkAdvancedRouting=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkAdvancedRouting"));
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$ID=intval($_GET["popup"]);
	$ligne=$q->mysqli_fetch_array("SELECT `RouteName`,nic FROM routing_rules WHERE ID=$ID");
    $title="{routing_table} {$ligne["RouteName"]}";

    if($NetworkAdvancedRouting==0){$title="Table Main {for} {$ligne["nic"]}";}

    if($ID<>999999){$array[$title]="$page?route-main=$ID";}
    $array["{destinations}"]="fw.network.routing.rule.destinations.php?ID=$ID";
    $array["{sources}"]="fw.network.routing.rule.sources.php?ID=$ID";
	echo $tpl->tabs_default($array);
}

function route_main(){
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$_GET["ID"]=$_GET["route-main"];
	
	$net=new networking();
	
	$ETHs=$net->Local_interfaces();
	unset($ETHs["lo"]);
	$ETHZ=array();
	foreach ($ETHs as $int=>$none){
		$ligneVerif=$q->mysqli_fetch_array("SELECT ID FROM routing_rules WHERE nic='$int'");
		if(intval($ligneVerif["ID"])>0){continue;}
		$nic=new system_nic($int);
		if($nic->enabled==0){continue;}
		$ETHZ[$int]="$int - $nic->NICNAME - $nic->IPADDR";
	
	}



	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	if($q->TABLE_EXISTS("nics_switch")) {

        $sql = "SELECT * FROM `nics_switch` ORDER BY nic,port";
        $results = $q->QUERY_SQL($sql);

        foreach ($results as $index => $ligne) {
            $int = "virt{$ligne["ID"]}";
            $ligneVerif = $q->mysqli_fetch_array("SELECT ID FROM routing_rules WHERE nic='$int'");
            if (intval($ligneVerif["ID"]) > 0) {
                continue;
            }
            $ETHZ[$int] = "$int - {from} {$ligne["nic"]} {$ligne["ipaddr"]}";
        }
    }
	

	$ligne=$q->mysqli_fetch_array("SELECT * FROM routing_rules WHERE ID=".intval($_GET["ID"]));
	if(!$q->ok){echo $q->mysql_error_html();}
	$nic=new system_nic($ligne["nic"]);
	if($_GET["lock"]=="yes"){$LOCK=1;}
	$route_name=$tpl->javascript_parse_text("{route_name}");
	$types[1]="{network_nic}";
	$types[2]="{host}";
	
	if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=0;}
	if(!is_numeric($ligne["metric"])){$ligne["metric"]=0;}
    $NetworkAdvancedRouting=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkAdvancedRouting"));
	
	$form[]=$tpl->field_hidden("ID", $_GET["ID"]);
	$form[]=$tpl->field_info("nic", "{nic}", "{$ligne["nic"]} - $nic->NICNAME - $nic->IPADDR");
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	if($NetworkAdvancedRouting==1) {
        $form[] = $tpl->field_text("RouteName", $route_name, $ligne["RouteName"]);
    }else{
	    $tpl->field_hidden("RouteName",$ligne["RouteName"]);
    }
	
	$html=$tpl->form_outside($ligne["pattern"], @implode("\n", $form),null,"{apply}","LoadAjax('table-loader-iprule','fw.network.routing.legacy.php?table=yes');","AsSystemAdministrator");

	echo $html;
}

function route_save():bool{
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$tpl->CLEAN_POST();
	$RouteName=$_POST["RouteName"];
	$RouteName=str_replace("'", "`", $RouteName);
	if(intval($_POST["ID"])>0){
		$q->QUERY_SQL_PARAMS("UPDATE routing_rules SET `enabled`=?,`RouteName`=? WHERE ID=?",array(intval($_POST["enabled"]),$RouteName,intval($_POST["ID"])));
		if(!$q->ok){echo $q->mysql_error;return false;}
        return admin_tracks_post("Saving routing rule");
	}
	return false;
	
}



	
	