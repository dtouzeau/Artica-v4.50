<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_POST["nic-settings"])){nic_settings();exit;}
if(isset($_POST["ID"])){edit_save();exit;}
if(isset($_GET["edit"])){edit_js();exit;}
if(isset($_GET["enable"])){balancer_enable();exit;}
if(isset($_GET["popup"])){edit_popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["interfaces"])){interfaces();exit;}
if(isset($_GET["params"])){parameters();exit;}
if(isset($_POST["LinkBalancerSchedule"])){parameters_save();exit;}
page();


function balancer_enable(){
    $tpl=new template_admin();
    $ID=intval($_GET["enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM link_balance WHERE ID='$ID'");
    if(intval($ligne["enabled"])==0){$enabled=1;}else{$enabled=0;}
    $q->QUERY_SQL("UPDATE link_balance SET enabled='$enabled' WHERE ID='$ID'");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
}
function edit_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["edit"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT Interface FROM link_balance WHERE ID='$ID'");
    $Interface=$ligne["Interface"];
    $nic=new system_nic($Interface);
    $tpl->js_dialog1("$Interface: $nic->NICNAME","$page?popup=$ID",650);

}

function parameters(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LinkBalancerSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinkBalancerSchedule"));
    if($LinkBalancerSchedule<1){$LinkBalancerSchedule=10;}
    $schedule[2]="1 {minute}";
    $schedule[2]="2 {minutes}";
    $schedule[5]="5 {minutes}";
    $schedule[10]="10 {minutes}";
    $schedule[15]="15 {minutes}";
    $schedule[20]="20 {minutes}";
    $schedule[30]="30 {minutes}";

    $form[]=$tpl->field_array_hash($schedule,"LinkBalancerSchedule","{check_interval}",$LinkBalancerSchedule);

    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/LinkBalancer.progress.log";
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/LinkBalancer.progress";
    $ARRAY["CMD"]="firehol.php?configure-link-balancer=yes";
    $ARRAY["TITLE"]="{please_wait_building_network}";
    $ARRAY["AFTER"]="";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-lb-restart')";

    $html=$tpl->form_outside(null,$form,null,"{apply}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}

function parameters_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();

    $array["{interfaces}"]="$page?interfaces=yes";
    $array["{options}"]="$page?params=yes";
    echo $tpl->tabs_default($array);
}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$FireholVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireholVersion");

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_LINK_BALANCER} v$FireholVersion</h1></div>
	<p>{APP_LINK_BALANCER_EXPLAIN}</p>
	</div>



	<div class='row'><div id='progress-lb-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/link-balancer');	
	LoadAjax('table-loader','$page?tabs=yes');

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_LINK_BALANCER}",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function edit_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM link_balance WHERE ID='$ID'");
    $Interface=$ligne["Interface"];
    $nic=new system_nic($Interface);

    $array["ping"]="ping";
    $array["traceroute"]="traceroute";
    $array["alwayson"]="{alwayson}";

	$form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_array_hash($array,"checkytype","{check_method}",$ligne["checkytype"]);
    $form[]=$tpl->field_ipaddr("checkaddr","{check_addr}",$ligne["checkaddr"],false,"{link_balance_checkaddr}");
    $form[]=$tpl->field_numeric("mark","{MARK}",$ligne["mark"]);
    $form[]=$tpl->field_numeric("weight","{weight}",$ligne["weight"]);
    $form[]=$tpl->field_numeric("probability","{probability} %",$ligne["probability"]);

	$html[]=$tpl->form_outside("$Interface $nic->NICNAME $nic->IPADDR",
        @implode("\n", $form),
        "{balance_ip_explain_weight}",
        "{apply}",
        "dialogInstance1.close();LoadAjax('table-loader','$page?tabs=yes');",
        "AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function edit_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["ID"];
    if($_POST["weight"]==0){$_POST["weight"]=1;}
    if($_POST["weight"]>254){$_POST["weight"]=254;}
    if($_POST["mark"]>63){$_POST["mark"]=63;}


    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $sql="UPDATE link_balance SET 
    checkytype='{$_POST["checkytype"]}',
    checkaddr='{$_POST["checkaddr"]}',
    probability='{$_POST["probability"]}',
    mark='{$_POST["mark"]}',
    weight='{$_POST["weight"]}' WHERE ID=$ID";

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}
}





function interfaces(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $t=time();



    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/exec.virtuals-ip.php.html";
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/reconfigure-newtork.progress";
    $ARRAY["CMD"]="/system/network/reconfigure-restart";
    $ARRAY["TITLE"]="{please_wait_building_network}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-interfaces','$page?tabs=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-lb-restart')";


    $sql="CREATE TABLE IF NOT EXISTS `link_balance` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`Interface` NOT NULL UNIQUE,
				`checkaddr` TEXT,
				`checkytype` TEXT,
				`mark` INTEGER UNIQUE,
				`weight` INTEGER,
				`probability` INTEGER NOT NULL DEFAULT 50,
				`enabled` INTEGER NOT NULL DEFAULT 1
				)";



    $q->QUERY_SQL($sql);
    if(!$q->FIELD_EXISTS("link_balance","probability")){
        $q->QUERY_SQL("ALTER TABLE link_balance ADD `probability` INTEGER NOT NULL DEFAULT 50");
    }


    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:15px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart\">";
    $html[]="<i class='fa fa-repeat'></i> {apply_network_configuration} </label>";
    $html[]="</div>";


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{interface}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' nowrap >{enabled}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' nowrap>{weight}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' nowrap>%</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' nowrap>{gateway}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
    $TRCLASS=null;
    $datas=TCP_LIST_NICS_W();

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    foreach ($datas as $num=>$Interface){

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $Interface=trim($Interface);
        if($Interface==null){continue;}
        $nic=new system_nic($Interface);
        if($nic->enabled==0 OR $nic->GATEWAY=="0.0.0.0"){
            $html[]="<tr class='$TRCLASS'>";
            $html[]="<td class='text-muted'><i class='fas fa-ethernet text-muted'></i>&nbsp;$Interface $nic->NICNAME $nic->IPADDR</td>";
            $html[]="<td width=1% class='center text-muted' nowrap>".$tpl->icon_nothing()."</td>";
            $html[]="<td width=1% class='center text-muted' nowrap>".$tpl->icon_nothing()."</td>";
            $html[]="<td width=1% class='center text-muted' nowrap>".$tpl->icon_nothing()."</td>";
            $html[]="<td width=1% class='center text-muted' nowrap>".$tpl->icon_nothing()."</td>";
            $html[]="</tr>";
            continue;
        }


        $ligne=$q->mysqli_fetch_array("SELECT * FROM link_balance WHERE Interface='$Interface'");
        $ID=intval($ligne["ID"]);
        if($GLOBALS["VERBOSE"]){echo "Interface='$Interface' ID == $ID<br>";}
         if($ID==0){
            $ligne=$q->mysqli_fetch_array("SELECT mark FROM link_balance ORDER BY mark DESC LIMIT 1");
            $mark=intval($ligne["mark"]);
            if($mark==0){$mark=5;}else{$mark++;}
            $q->QUERY_SQL("INSERT OR IGNORE INTO link_balance (Interface,checkaddr,checkytype,mark,weight,enabled) VALUES ('$Interface','8.8.8.8','ping','$mark','100',0)");
             $ligne=$q->mysqli_fetch_array("SELECT * FROM link_balance WHERE Interface='$Interface'");
             $ID=intval($ligne["ID"]);
            }


        $link=$tpl->td_href("$Interface $nic->NICNAME $nic->IPADDR",
            "{click_to_edit}","Loadjs('$page?edit=$ID')");

         $enable=$tpl->icon_check($ligne["enabled"],
         "Loadjs('$page?enable=$ID')","AsSystemAdministrator");

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class='text-muted'><i class='fas fa-ethernet'></i>&nbsp;$link (MARK: 0x{$ligne["mark"]})</td>";
        $html[]="<td width=1% class='center' nowrap>$enable</td>";
        $html[]="<td width=1% class='center' nowrap>{$ligne["weight"]}</td>";
        $html[]="<td width=1% class='center' nowrap>{$ligne["probability"]}%</td>";
        $html[]="<td width=1% class='center' nowrap>$nic->GATEWAY</td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}