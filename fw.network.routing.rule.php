<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["route-main"])){route_main();exit;}
if(isset($_POST["RouteName"])){route_save();exit;}
if(isset($_POST["toto"])){exit();}
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
	$ID=$_GET["popup"];
	$ligne=$q->mysqli_fetch_array("SELECT `RouteName`,nic FROM routing_rules WHERE ID='$ID'");
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
	

	$ligne=$q->mysqli_fetch_array("SELECT * FROM routing_rules WHERE ID='{$_GET["ID"]}'");
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
	
	$html=$tpl->form_outside($ligne["pattern"], @implode("\n", $form),null,"{apply}","LoadAjax('table-loader-iprule','fw.network.routing.php?table=yes');","AsSystemAdministrator");

	echo $html;
}

function route_save():bool{
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$tpl->CLEAN_POST();
	$RouteName=$_POST["RouteName"];
	$RouteName=str_replace("'", "`", $RouteName);
	if($_POST["ID"]>0){
		$q->QUERY_SQL("UPDATE routing_rules SET `enabled`='{$_POST["enabled"]}',`RouteName`='$RouteName' WHERE ID='{$_POST["ID"]}'");
		if(!$q->ok){echo $q->mysql_error;return false;}
        return admin_tracks_post("Saving routing rule");
	}
	return false;
	
}



function route_dump(){
	$page=CurrentPageName();
	echo "<div id='route-dump-fields'></div>
	<script>
	function RouteDumpFields(){
		LoadAjax('route-dump-fields','$page?route-dump2={$_GET["route-dump"]}');
	}
	RouteDumpFields();
	</script>";
}
function route_dump2(){	
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$tpl=new template_admin();
	$ligne=$q->mysqli_fetch_array("SELECT * FROM routing_rules WHERE ID='{$_GET["route-dump2"]}'");
	if(!$q->ok){echo $q->mysql_error_html();}
	$title=$ligne["RouteName"];


	exec("/sbin/ip rule show |grep $title 2>&1",$results1);

	exec("/sbin/ip route show table $title 2>&1",$results2);

	$route="# Rules *************************************************************************\n".@implode("\n", $results1)."\n\n\n# Table *************************************************************************\n\n".@implode("\n", $results2);

	$html=$tpl->field_textareacode("toto", null, $route);

	echo $tpl->form_outside("{configuration}", $html,null,"{apply_network_configuration}","Loadjs('fw.network.apply.php')","AsSystemAdministrator");

	 


}
	
	