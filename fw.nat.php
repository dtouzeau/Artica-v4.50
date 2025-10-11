<?php
define("td1prc" ,  "widht=1% style='vertical-align:middle' nowrap");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_GET["delete-confirm"])){delete_confirm();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();

function rule_js(){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$tpl=new template_admin();
	$ruleid=intval($_GET["ruleid-js"]);
	$NAT_TYPE[0]=$tpl->javascript_parse_text("{destination} NAT");
	$NAT_TYPE[1]=$tpl->javascript_parse_text("{source} NAT");
	$NAT_TYPE[2]=$tpl->javascript_parse_text("{redirect_nat}");
    $function=$_GET["function"];

	if($ruleid==0){
        $NAT_TYPE_TEXT="{new_rule}";
	}else{
		$ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$ruleid'");
        $NAT_TYPE_TEXT=$NAT_TYPE[$ligne["NAT_TYPE"]];
	}
	$tpl->js_dialog("{rule}: $NAT_TYPE_TEXT","$page?rule-popup=$ruleid&eth={$_GET["eth"]}&function=$function");
}

function delete_js(){
	$page=CurrentPageName();
	$ID=$_GET["delete-rule-js"];
    $md=$_GET["md"];
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $tpl=new template_admin();

	$results=$q->QUERY_SQL("SELECT rulename FROM iptables_main WHERE `eth` LIKE '%NAT:$ID'");
	$RR=array();
    foreach ($results as $index=>$ligne){
        $RR[]="-----------------------------------------\n{delete_first}:{$ligne["rulename"]}";
    }
    if(count($RR)>0){
        $tpl->js_error_stop("{failed}\n".@implode("\n",$RR));
        return false;
    }

	$q->QUERY_SQL("DELETE FROM pnic_nat WHERE ID=$ID");
	if(!$q->ok){$tpl->js_error_stop($q->mysql_error);
        return;
    }
	echo "$('#$md').remove();\n";
}
function table_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,"","","","&table=yes");
}


function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-popup"]);
    $function=$_GET["function"];
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$btname="{add}";
	$BootstrapDialog=null;
	$NAT_TYPE_TEXT=null;
	$NAT_TYPE[0]=$tpl->javascript_parse_text("{destination} NAT");
	$NAT_TYPE[1]=$tpl->javascript_parse_text("{source} NAT");
	$NAT_TYPE[2]=$tpl->javascript_parse_text("{redirect_nat}");
    $NAT_TYPE[3]=$tpl->javascript_parse_text("{route_to}");
    $enabled=1;
	$title="{new_rule}";
	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$ID'");
		$enabled=$ligne["enabled"];
		$table=$ligne["MOD"];
		$eth=$ligne["eth"];
		$NAT_TYPE_TEXT=$NAT_TYPE[$ligne["NAT_TYPE"]];
		$title="{rule} $ID) $eth::{$NAT_TYPE_TEXT}";
		$btname="{apply}";
		$jlog=intval($ligne["jlog"]);
        $rulename=$ligne["rulename"];

	}

	$nic=new networking();
	$nicZ=$nic->Local_interfaces();
	$interface=$tpl->_ENGINE_parse_body("{interface}");

	foreach ($nicZ as $yinter=>$line){
		$znic=new system_nic($yinter);
		if($znic->Bridged==1){continue;}
		if($znic->enabled==0){continue;}
		$NICS[$yinter]="$interface:$yinter - $znic->NICNAME";
	}

	if($ID==0){$BootstrapDialog="BootstrapDialog1.close();";}

	$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$enabled,true);
    $form[]=$tpl->field_text("rulename","{rulename}",$rulename,true);
	$form[]=$tpl->field_array_hash($NAT_TYPE,"NAT_TYPE","{type}",$ligne["NAT_TYPE"],true);
	$form[]=$tpl->field_array_hash($NICS,"nic","{interface}",$ligne["nic"],true);
	$form[]=$tpl->field_text("dstaddr","{destination_address}",$ligne["dstaddr"]);	
	$form[]=$tpl->field_numeric("dstaddrport","{destination_port}",$ligne["dstaddrport"]);
    $form[]=$tpl->field_checkbox("jlog","{log_all_events}",$jlog);

	echo $tpl->form_outside($title,@implode("\n", $form),null,
            $btname,
			"$function();$BootstrapDialog");


}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{nat_title}","fa fa-arrow-right","{dnat_explain}","$page?table-start=yes","/nat","progress-firehol-restart",
        false,"table-loader");


	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}



	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$token=null;
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$type=$tpl->javascript_parse_text("{type}");
    $function=$_GET["function"];
    $nic=new networking();
	$nicZ=$nic->Local_interfaces();
	$interface=$tpl->_ENGINE_parse_body("{interface}");
    $search=trim($_GET["search"]);
    if($search=="*"){
        $search="";
    }

	foreach ($nicZ as $yinter=>$line){
		$znic=new system_nic($yinter);
		if($znic->Bridged==1){continue;}
		if($znic->enabled==0){continue;}
		$NICS[$yinter]="$znic->NICNAME ($yinter)";
	}

    $sNAT_TYPE[0]="DNAT";
    $sNAT_TYPE[1]="SNAT";
    $sNAT_TYPE[2]="RNAT";
    $sNAT_TYPE[3]="XNAT";

    $topbuttons=array();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");


	$t=time();
	$add="Loadjs('$page?ruleid-js=0$token&function=$function',true);";

    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure",
        "firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-firehol-restart",
        "");

    $users=new usersMenus();
    if($users->AsFirewallManager) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
        $topbuttons[] = array($jsrestart, ico_save, "{apply_firewall_rules}");

    }

    $td1prc=$tpl->table_td1prc();
	$html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize center' >id</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$type</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rule}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{$interface}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{destination}</th>";
    $html[]="<th data-sortable=false class='text-capitalize center'>{enabled}</th>";
    $html[]="<th data-sortable=false class='text-capitalize center'>LOG</th>";
    $html[]="<th data-sortable=false class='text-capitalize center'>DEL</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	
	$all=$tpl->javascript_parse_text("{all}");
	$NAT_TYPE[0]=$tpl->javascript_parse_text("{destination} NAT");
	$NAT_TYPE[1]=$tpl->javascript_parse_text("{source} NAT");
	$NAT_TYPE[2]=$tpl->javascript_parse_text("{redirect_nat}");
    $NAT_TYPE[3]=$tpl->javascript_parse_text("{route_to}");

    $Query="SELECT * FROM pnic_nat ORDER BY ID DESC";
    if(strlen($search)>1){
        if(is_numeric($search)){
            $Query="SELECT * FROM pnic_nat 
         WHERE ( (dstaddrport=$search) OR (ID=$search) )
         ORDER BY ID DESC";
        }else {
            $search="*$search*";
            $search=str_replace("**","*",$search);
            $search=str_replace("**","*",$search);
            $search=str_replace("*","%",$search);
            $Query = "SELECT * FROM pnic_nat 
         WHERE ( (rulename LIKE '$search') OR (dstaddr LIKE '$search') )
         ORDER BY ID DESC";
        }
    }

	$results=$q->QUERY_SQL($Query);
	$TRCLASS=null;
foreach ($results as $index=>$ligne){
	$text_class=null;
	if($ligne["enabled"]==0){$text_class=" text-muted";}
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$nic            = $NICS[$ligne["nic"]];
    $jlog           = intval($ligne["jlog"]);
    $jlog_ico       = $tpl->icon_nothing();
    $enable_ico     = "&nbsp;";
    $md = md5(serialize($ligne));
    $enabled        = $ligne["enabled"];
    $rulename       = $ligne["rulename"];
    $NAT_TYPE_TEXT  =$NAT_TYPE[$ligne["NAT_TYPE"]];
	$up=$tpl->icon_up("RuleGroupUpDown{$t}('{$ligne["ID"]}',0,'{$ligne["eth"]}')");
	$down=$tpl->icon_down("RuleGroupUpDown{$t}('{$ligne["ID"]}',1,'{$ligne["eth"]}')");
	$js="Loadjs('$page?ruleid-js={$ligne["ID"]}&function=$function',true);";
	$delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js={$ligne["ID"]}&md=$md')");
    if($ligne["NAT_TYPE"]==3){$ligne["dstaddrport"]=0;}
    $dstaddr=$ligne["dstaddr"];
    $dstaddrport=intval($ligne["dstaddrport"]);
    if(intval($dstaddrport)>5){$dstaddr="$dstaddr:$dstaddrport";}

    if($ligne["NAT_TYPE"]==2){
        $dstaddr="0.0.0.0:$dstaddrport";
    }
    if($ligne["NAT_TYPE"]==3){
        $dstaddr=$ligne["dstaddr"];
    }

    if($jlog==1){
        $jlog_ico="<i class='fas fa-check'></i>";
    }
    if($enabled==1){
        $enable_ico="<i class='fas fa-check'></i>";
    }

    if(strlen($rulename)<2){
        $rulename="NAT - $dstaddr";
    }
    $rulename=$tpl->td_href($rulename,"",$js);
	$html[]="<tr class='$TRCLASS' id='$md'>";
	$html[]="<td $td1prc class='center'><span class='$text_class'>{$ligne["ID"]}</span></td>";
	$html[]="<td class='$text_class' $td1prc>".$tpl->td_href($NAT_TYPE_TEXT,null,$js)."</td>";
    $html[]="<td class='$text_class' style='vertical-align:middle'>$rulename</td>";
	$html[]="<td $td1prc>$nic</td>";
    $html[]="<td $td1prc>$dstaddr</td>";
    $html[]="<td $td1prc class='center'>$enable_ico</td>";
    $html[]="<td $td1prc class='center'>$jlog_ico</td>";
	$html[]="<td $td1prc class='center'>$delete</td>";
	$html[]="</tr>";

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='8'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";


    $TINY_ARRAY["TITLE"]="{nat_title}";
    $TINY_ARRAY["ICO"]="fa fa-arrow-right";
    $TINY_ARRAY["EXPL"]="{dnat_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
$jstiny
</script>";

    echo $tpl->_ENGINE_parse_body($html);

}
function rule_save(){
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	if(!isset($_POST["dstport"])){$_POST["dstport"]=0;}
	if(!isset($_POST["srcaddr"])){$_POST["srcaddr"]="0.0.0.0";}

	reset($_POST);foreach ($_POST as $key=>$val){
		$EDIT[]="`$key`='$val'";
		$ADDFIELD[]="`$key`";
		$ADDVALS[]="'$val'";

	}

	if($ID==0){
		$zMD5=md5(serialize($_POST));
		$ADDFIELD[]="`zMD5`";
		$ADDVALS[]="'$zMD5'";
		$sql="INSERT INTO pnic_nat (".@implode(",", $ADDFIELD).") VALUES (".@implode(",", $ADDVALS).")";

	}else{
		$sql="UPDATE pnic_nat SET ".@implode(",", $EDIT)." WHERE ID=$ID";

	}

	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true,$sql);}
}