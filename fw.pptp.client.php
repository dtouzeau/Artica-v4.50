<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["connection-id"])){connection_js();exit;}
if(isset($_GET["connection-delete"])){connection_delete();exit;}
if(isset($_POST["connection-delete"])){connection_delete_perform();exit;}
if(isset($_GET["connection-popup"])){connection_popup();exit;}
if(isset($_GET["connection-popup1"])){connection_popup1();exit;}
if(isset($_POST["connexion_name"])){connection_save();exit;}
if(isset($_GET["build-status"])){build_status();exit;}
if(isset($_GET["refresh"])){refesh_js();exit;}
if(isset($_GET["refresh-tr"])){refresh_tr();exit;}
if(isset($_GET["start-vpn"])){start_vpn();exit;}
if(isset($_GET["stop-vpn"])){stop_vpn_ask();exit;}
if(isset($_POST["stop-vpn"])){stop_vpn_perform();exit;}
if(isset($_GET["connection-enable"])){connection_enable();exit;}
if(isset($_GET["searchlogs"])){searchlogs();exit;}
if(isset($_GET["routing-start"])){routing_start();exit;}
if(isset($_POST["routes"])){routing_save();exit;}

page();

function connection_delete(){
    $ID=intval($_GET["connection-delete"]);
    $name=$_GET["name"];
    $md=$_GET["md"];
    $tpl=new template_admin();

    $tpl->js_confirm_delete($name,"connection-delete",$ID,"$('#$md').remove()");
}
function connection_enable(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["connection-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM connections WHERE ID=$ID");
    $enabled=$ligne["enabled"];
    $cur=$tpl->_ENGINE_parse_body("<span class=\"label label-warning\">{processing}</span>");
    header("content-type: application/x-javascript");
    echo "$('#pptp-$ID-0').html('$cur');";

    if($enabled==1){
        $sock=new sockets();
        $sock->getFrameWork("pptp.client.php?remove-vpn=$ID");
        $q->QUERY_SQL("UPDATE connections SET enabled=0 WHERE ID=$ID");

    }else{
        $sock=new sockets();
        $sock->getFrameWork("pptp.client.php?add-vpn=$ID");
        $q->QUERY_SQL("UPDATE connections SET enabled=1 WHERE ID=$ID");
    }

    echo "setTimeout(\"Loadjs('$page?refresh=$ID')\",3000);\n";
}

function stop_vpn_ask(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["stop-vpn"]);
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM connections WHERE ID=$ID");
    $connexion_name=$ligne["connexion_name"];
    $cur=$tpl->_ENGINE_parse_body("<span class=\"label label-warning\">{processing}</span>");


    $tpl->js_dialog_confirm_action("{stop} $connexion_name","stop-vpn",$ID,"$('#pptp-$ID-0').html('$cur');;setTimeout(\"Loadjs('$page?refresh=$ID')\",3000);","$connexion_name");

}

function stop_vpn_perform(){
    $ID=$_POST["stop-vpn"];
    $sock=new sockets();
    $sock->getFrameWork("pptp.client.php?stop-vpn=$ID");

}

function connection_delete_perform(){
    $ID=intval($_POST["connection-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $q->QUERY_SQL("DELETE FROM connections WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return;}
    $sock=new sockets();
    $sock->getFrameWork("pptp.client.php?remove-vpn=$ID");
    return true;
}

function connection_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["connection-id"]);
    if(isset($_GET["name"])){$title=$_GET["name"];}
    if($ID==0){$title="{new_connection}";}
    $tpl->js_dialog1($title, "$page?connection-popup=$ID",650);

}

function start_vpn(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["start-vpn"];
    $sock=new sockets();
    $sock->getFrameWork("pptp.client.php?start-vpn=$ID");
    header("content-type: application/x-javascript");
    $cur=$tpl->_ENGINE_parse_body("<span class=\"label label-warning\">{processing}</span>");
    echo "$('#pptp-$ID-0').html('$cur');\n";
    echo "setTimeout(\"Loadjs('$page?refresh=$ID')\",3000);";
}

function connection_popup(){
    $page=CurrentPageName();
    $ID=intval($_GET["connection-popup"]);
    if($ID==0){connection_popup1();return true;}

    $array["{settings}"]="$page?connection-popup1=$ID";
    $array["{routing}"]="$page?routing-start=$ID";

    $tpl=new template_admin();
    echo $tpl->tabs_default($array);


}
function routing_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["routing-id"]);
    $ip=new IP();
    $r=explode("\n",$_POST["routes"]);
    foreach ($r as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!$ip->IsACDIROrIsValid($line)){continue;}
        $ROUTES[$line]=true;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $ligne=$q->mysqli_fetch_array("SELECT routes FROM connections WHERE ID=$ID");
    $md51=md5($ligne["routes"]);

    $final=base64_encode(serialize($ROUTES));
    $md52=md5($final);
    if($md52==$md51){return;}

    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $q->QUERY_SQL("UPDATE connections SET routes='$final' WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return;}


    $sock=new sockets();
    $sock->getFrameWork("pptp.client.php?restart-vpn=$ID");

}

function routing_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["routing-start"]);
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $ligne=$q->mysqli_fetch_array("SELECT routes FROM connections WHERE ID=$ID");
    $routes=unserializeb64($ligne["routes"]);
    foreach ($routes as $ip=>$none){
        $data[]=$ip;
    }

    $tpl->field_hidden("routing-id",$ID);
    $form[]=$tpl->field_textarea("routes","{routing}",@implode("\n",$data));
    echo $tpl->form_outside("{vpn_add_routes}", @implode("\n", $form),null,"{apply}","Loadjs('$page?refresh=$ID')","AsVPNManager");

}

function connection_popup1(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["connection-popup1"]);
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM connections WHERE ID=$ID");

    $AUTH[1]="MS-CHAP v2";
   // $AUTH[2]="PAP";

    $MPPE[1]="{required}";
    $MPPE[2]="{optional}";
    $MPPE[0]="{disabled}";

    $title=$ligne["connexion_name"];
    $bt="{apply}";
    $js="Loadjs('$page?refresh=$ID');";

    if($ID==0){
        $title="{new_connection}";
        $bt="{add}";
        $ligne["authentication"]=1;
        $ligne["mppe"]=2;
        $js="dialogInstance1.close();LoadAjax('pptp-vpn','$page?table=yes')";
    }

    $form[]=$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_section("{connection}");
    $form[]=$tpl->field_text("connexion_name","{connection_name}",$ligne["connexion_name"],true);
    $form[]=$tpl->field_text("servername","{remote_vpn_server}",$ligne["servername"],true);
    $form[]=$tpl->field_array_hash($AUTH, "authentication", "{authentication}", $ligne["authentication"]);
    $form[]=$tpl->field_array_hash($MPPE, "mppe", "{encryption} MPPE", $ligne["mppe"]);
    $form[]=$tpl->field_interfaces("interface","nooloopNoDef:{outgoing_interface}",$ligne["interface"]);

    $form[]=$tpl->field_section("{credentials}");
    $form[]=$tpl->field_text("username","{username}",$ligne["username"]);
    $form[]=$tpl->field_password2("password","{password}",$ligne["password"]);
    echo $tpl->form_outside(null, @implode("\n", $form),null,"$bt",$js,"AsVPNManager");
}



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $APP_PPTP_CLIENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PPTP_CLIENT_VERSION");
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
        <div class=\"col-sm-12\">
            <h1 class=ng-binding>{APP_PPTP_CLIENT} v$APP_PPTP_CLIENT_VERSION</h1>
            <p>{APP_PPTP_CLIENT_EXPLAIN}</p>
        </div>
	</div>
	
    <div class='row'><div id='progress-pptp-client-restart'></div>
        <div class='ibox-content'>
            <div id='table-loader-pptp-client'></div>
        </div>
	</div>
    <script>
	$.address.state('/');
	$.address.value('/vpn-pptp');
	LoadAjax('table-loader-pptp-client','$page?tabs=yes');

	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }


	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{connections}"]="$page?table-start=yes";
    $array["{events}"]="fw.pptp.client.events.php";
    echo $tpl->tabs_default($array);
}

function table_start(){
    $page=CurrentPageName();
    echo "<div id='pptp-vpn' style='margin-top:20px'></div><script>LoadAjax('pptp-vpn','$page?table=yes')</script>";
}

function table(){
    $tpl=new template_admin();
	$page=CurrentPageName();
	$t=time();

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pptp.client.php?status=yes");




    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px;margin-bottom:10px'>";
    $html[]=$tpl->button_label_table("{new_connection}",
        "Loadjs('$page?connection-id=0')",
        "fas fa-plus","AsVPNManager");

    $html[]=$tpl->button_label_table("{refresh}",
        "LoadAjax('pptp-vpn','$page?table=yes')",
        "fad fa-sync");

    $td1="style=\"width=1%\" nowrap";
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'  width='1%'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'  width='1%'>{ID}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'  width='1%'>{connection}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{vpn_server}</th>";
    $html[]="<th data-sortable=false width='1%'>{action}</th>";
    $html[]="<th data-sortable=false width='1%'>{active2}</th>";
    $html[]="<th data-sortable=false  width='1%'>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $sql="CREATE TABLE IF NOT EXISTS `connections` (
					  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
					  `servername` TEXT,
					  `username` TEXT,
					  `password` TEXT,
					  `connexion_name` TEXT,
					  `interface` TEXT,
					  `routes` TEXT,
					  `enabled` INTEGER,
					  `mppe` INTEGER NOT NULL DEFAULT 1,
					  `authentication` INTEGER NOT NULL DEFAULT 1,
					  `md5` TEXT
            ) ";

    if(!$q->FIELD_EXISTS("connections","interface")){
        $q->QUERY_SQL("ALTER TABLE connections ADD interface TEXT");
    }

    $q->QUERY_SQL($sql);
    if(!$q->FIELD_EXISTS("connections","connexion_name")){
        $q->QUERY_SQL("ALTER TABLE connections ADD connexion_name TEXT");
        if(!$q->ok){echo $tpl->div_error("ALTER TABLE:".$q->mysql_error);}
    }



    $results=$q->QUERY_SQL("SELECT * FROM `connections` ORDER BY connexion_name");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $TRCLASS=null;

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $TR=build_tr($ligne);
        $IDText=$TR["IDTEXT"];
        $connexion_name=$TR["CNX"];
        $content=$TR["content"];
        $cur=$TR["CUR"];
        $check=$TR["check"];
        $act=$TR["ACT"];
        $connexion_name_en=urlencode($ligne["connexion_name"]);
        $tpl->ICON_SCRIPTS[]=$TR["JS"];
        $text_class=$TR["class"];

        $cls="class='$text_class'";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $td1><span $cls id='pptp-$ID-0'>$cur</span>";
        $html[]="<td $td1><span $cls id='pptp-$ID-5'>$IDText</span></td>";
        $html[]="<td $td1><span $cls id='pptp-$ID-1'>$connexion_name</span></td>";
        $html[]="<td style='width:98%' nowrap><span $cls id='pptp-$ID-2' >$content</span></td>";
        $html[]="<td $td1><span $cls id='pptp-$ID-4'>$act</span></td>";
        $html[]="<td $td1><span $cls id='pptp-$ID-3'>$check</span></td>";
        $html[]="<td $td1>". $tpl->icon_delete(
                "Loadjs('$page?connection-delete=$ID&name=$connexion_name_en&md=$md')",
                "AsVPNManager")."</td>";


        $html[]="</tr>";
    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='7'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n";
    $html[]="$(document).ready(function() { $('#table-$t').footable( {";
    $html[]="\"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true";
    $html[]="},\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

}

function refesh_js(){
    header("content-type: application/x-javascript");
    $ID=$_GET["refresh"];
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pptp.client.php?status=yes");
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM connections WHERE ID=$ID");
    $TR=build_tr($ligne);
    foreach ($TR as $key=>$bvale){
        $TR[$key]=str_replace("\n","",$bvale);
    }
    $tpl=new template_admin();
    $text_class=$TR["class"];
    $IDText=$TR["IDTEXT"];
    $connexion_name=$TR["CNX"];
    $content=$TR["content"];
    $cur=$tpl->_ENGINE_parse_body($TR["CUR"]);
    $act=$TR["ACT"];
    $check=$TR["check"];
    $check=str_replace("\n","",$check."<script>".$TR["JS"]."</script>");
    $connexion_name=str_replace("'","\'",$connexion_name);
    $content=str_replace("'","\'",$content);
    $check=str_replace("'","\'",$check);
    $act=str_replace("'","\'",$act);
    $class=$TR["class"];



    echo "$('#pptp-$ID-0').html('$cur');\n";
    echo "$('#pptp-$ID-1').html('$connexion_name');\n";
    echo "$('#pptp-$ID-2').html('$content');\n";
    echo "$('#pptp-$ID-3').html('$check');\n";
    echo "$('#pptp-$ID-4').html('$act');\n";

    echo "$('#pptp-$ID-0').removeClass();\n";
    echo "$('#pptp-$ID-1').removeClass();\n";
    echo "$('#pptp-$ID-2').removeClass();\n";
    echo "$('#pptp-$ID-3').removeClass();\n";
    echo "$('#pptp-$ID-4').removeClass();\n";
    echo "$('#pptp-$ID-5').removeClass();\n";

    echo "$('#pptp-$ID-0').toggleClass(\"$class\");\n";
    echo "$('#pptp-$ID-1').toggleClass(\"$class\");\n";
    echo "$('#pptp-$ID-2').toggleClass(\"$class\");\n";
    echo "$('#pptp-$ID-3').toggleClass(\"$class\");\n";
    echo "$('#pptp-$ID-4').toggleClass(\"$class\");\n";
    echo "$('#pptp-$ID-5').toggleClass(\"$class\");\n";

}


function build_tr($ligne){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $connexion_name=$ligne["connexion_name"];
    $enabled=$ligne["enabled"];
    $text_class=null;
    $ID=$ligne["ID"];
    $status=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/pptp.status"));

    if(!isset($status[$ID])){
        $cur="<span class=\"label label-danger\">{stopped}</span>";
        $act=$tpl->icon_run("Loadjs('$page?start-vpn=$ID')");
        $class="text-danger";
    }else{
        $cur="<span class=\"label label-primary\">{connected}</span>";
        $act=$tpl->icon_stop("Loadjs('$page?stop-vpn=$ID')");
        $class="text-primary";
    }

    if($enabled==0){
        $cur="<span class=\"label label\">{disabled}</span>";
        $act=null;
        $class="text-muted";
    }

    $servername=$ligne["servername"];
    $connexion_name_en=urlencode($connexion_name);
    $interface=$ligne["interface"];

    $connexion_name=$tpl->td_href($connexion_name,null,"Loadjs('$page?connection-id=$ID&name=$connexion_name_en')");

    $IDText=$tpl->td_href($ID,null,"Loadjs('$page?refresh=$ID')");

    return array(
        "CUR"=>$cur,
        "ACT"=>$act,
        "IDTEXT"=>$IDText,
        "CNX"=>$connexion_name,
        "content"=>"{$interface}&nbsp;&nbsp;&raquo;&raquo;&nbsp;&nbsp;$servername:1723 <small>(GRE 47)</small>",
        "check"=>$tpl->icon_check($enabled,"Loadjs('$page?connection-enable=$ID')"),
        "JS"=>@implode(" ",$tpl->ICON_SCRIPTS),
        "class"=>$class


    );


}

function connection_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
	foreach ($_POST as $key=>$val){
        $val=$q->sqlite_escape_string2($val);
	    $fields[]="$key";
	    $add[]="'$val'";
	    $upd[]="$key='$val'";
    }

	if($ID==0){
	    $md5=md5(@implode("",$upd).time());
        $fields[]="enabled";
        $fields[]="md5";
        $add[]="'1'";
        $add[]="'$md5'";
        $sql="INSERT INTO connections (".@implode(",",$fields).") VALUES (".@implode(",",$add).")";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "jserror:$q->mysql_error{$sql}";return true;}
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM connections WHERE `md5`='$md5'");
        $ID=$ligne["ID"];
        $sock=new sockets();
        $sock->getFrameWork("pptp.client.php?add-vpn=$ID");
        return true;
    }

	$q->QUERY_SQL("UPDATE connections SET ".@implode(",",$upd)." WHERE ID=$ID");
    if(!$q->ok){echo "jserror:$q->mysql_error";return true;}
    return true;

	
	
}