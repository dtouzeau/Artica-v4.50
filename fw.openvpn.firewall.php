<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["delete-js"])){delete_rule_js();exit;}
if(isset($_POST["delete"])){delete_rule_confirm();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_POST["fw"])){rule_save();exit;}
if(isset($_GET["rule-move"])){rule_move();exit;}
//page();
main_start();

function rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $ID=intval($_GET["ruleid-js"]);
    $rulename="{new_rule}";
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
        $ligne=$q->mysqli_fetch_array("SELECT rulename FROM firewall WHERE ID=$ID");
        $rulename=$ligne["rulename"];
    }

	return $tpl->js_dialog($rulename,"$page?rule-popup=0&profile=$profile&function=$function");
}

function main_start():bool{
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $page=CurrentPageName();
    echo "<div id='firewall-profile-$profile'></div>
        <script>LoadAjax('firewall-profile-$profile','$page?main=yes&profile=$profile&function=$function')</script>";

    return true;

}
function delete_rule_js():bool{
    $tpl=new template_admin();
    $ID=$_GET["delete-js"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM firewall WHERE ID=$ID");
    $rulename=$ligne["rulename"];
    return $tpl->js_confirm_delete($rulename,"delete",$ID,"$('#$md').remove()");
}
function delete_rule_confirm():bool{
    $tpl=new template_admin();
    $ID=$_POST["delete"];
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM firewall WHERE ID=$ID");
    $title=$ligne["rulename"];
    $ipaddr=$ligne["ipaddr"]."/".$ligne["netmask"];
    $q->QUERY_SQL("DELETE FROM firewall WHERE ID=$ID");
    return admin_tracks("Deleted route profile $title $ipaddr");
}

function rule_popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $ID=intval($_GET["rule-popup"]);
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $title="{new_route}";
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM firewall WHERE ID=$ID");
    $btn="{add}";
    if($ID>0){
        $title=$ligne["rulename"];
        $btn="{apply}";
    }

    $fs[]="LoadAjax('firewall-profile-$profile','$page?main=yes&profile=$profile&function=$function')";
    $fs[]="BootstrapDialog1.close()";
    $fs[]="$function()";

    $form[]=$tpl->field_hidden("fw",$ID);
    $form[]=$tpl->field_hidden("profile",$profile);
    $form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"]);

    $protocol["all"]="{all}";
    $protocol["tcp"]="TCP/IP";
    $protocol["udp"]="UDP";
    $protocol["icmp"]="ICMP (ping)";

    $action["deny"]="{deny}";
    $action["allow"]="{allow}";

    $direction["in"]="{inbound}";
    $direction["out"]="{outbound}";


    $form[]=$tpl->field_array_hash($direction,"direction","nonull:{direction}",$ligne["direction"]);
    $form[]=$tpl->field_array_hash($action,"action","nonull:{action}",$ligne["action"]);
    $form[]=$tpl->field_array_hash($protocol,"zprotocol","nonull:{protocol}",$ligne["protocol"]);
    $form[]=$tpl->field_text("ipaddr", "{network}", $ligne["ipaddr"]);
    $form[]=$tpl->field_text("ports","{ports}",$ligne["ports"]);
	$html=$tpl->form_outside($title, $form,"{firewall_openvpn_explain}",$btn,implode(";",$fs),"AsVPNManager");
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["fw"]);

    if(strlen($_POST["direction"])<2){
        $_POST["direction"]="all";
    }

    $Main["profileid"]=intval($_POST["profile"]);
    $Main["rulename"]=$_POST["rulename"];
    $Main["ipaddr"]=$_POST["ipaddr"];
    $Main["protocol"]=$_POST["zprotocol"];
    $Main["action"]=$_POST["action"];
    $Main["direction"]=$_POST["direction"];

    $ports=str_replace(" ",",",$_POST["ports"]);
    $ports=str_replace(";",",",$ports);
    $Main["ports"]=$ports;

    foreach ($Main as $key=>$val){
        $ff[]=$key;
        $fd[]="'$val'";
        $fe[]="$key='$val'";
    }
    if($ID==0){
        $sql=sprintf("INSERT INTO firewall (%s) VALUES (%s)",implode(",",$ff),implode(",",$fd));
    }else{
        $sql=sprintf("UPDATE firewall SET %s WHERE ID=%s",implode(",",$fe),$ID);
    }
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks_post("OpenVPN Route ID=$ID");
}
function rule_move():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["rule-move"]);
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $sql="SELECT zorder,profileid FROM firewall WHERE `ID`='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["zorder"]};\n";}
    $xORDER_ORG=intval($ligne["zorder"]);
    $xORDER=$xORDER_ORG;
    $profileid=$ligne["profileid"];
    if($_GET["acl-rule-dir"]==1){
        $xORDER=$xORDER_ORG-1;
    }
    if($_GET["acl-rule-dir"]==0){
        $xORDER=$xORDER_ORG+1;

    }

    $sql="UPDATE firewall SET zorder=$xORDER WHERE `ID`='$ID'";
    $q->QUERY_SQL($sql);
    admin_tracks("Move Firewall rule order of profile $profileid from $xORDER_ORG to $xORDER");

    $sql="UPDATE firewall SET xORDER=$xORDER_ORG WHERE `ID`<>'$ID' AND xORDER=$xORDER AND profileid=$profileid";
    $q->QUERY_SQL($sql);

    $c=1;
    $sql="SELECT ID FROM firewall WHERE profileid=$profileid ORDER BY xORDER";
    $results = $q->QUERY_SQL($sql);

    foreach($results as $index=>$ligne) {
        echo "// ID {$ligne["ID"]} became $c\n";
        $q->QUERY_SQL("UPDATE firewall SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
        $c++;
    }

    return true;
}


function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $topbuttons[] = array("Loadjs('$page?ruleid-js=0&profile=$profile&function=$function');", ico_plus, "{new_rule}");
	
	$html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->th_buttons($topbuttons);
	$html[]="<table id='table-openvpn-sites' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$TRCLASS=null;
    $html[]="<th></th>";
    $html[]="<th>{rulename}</th>";
	$html[]="<th>{direction}</th>";
    $html[]="<th>{network}</th>";
	$html[]="<th>{order}</center></th>";
	$html[]="<th>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $icofw=ico_firewall;

    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS firewall (
        ID INTEGER PRIMARY KEY AUTOINCREMENT,
        profileid INTEGER NOT NULL DEFAULT 0,
        rulename TEXT NOT NULL DEFAULT 'new rule',
        protocol TEXT NOT NULL DEFAULT 'tcp',
        action TEXT NOT NULL DEFAULT 'deny',
        direction TEXT NOT NULL DEFAULT 'out',
        ports TEXT NOT NULL DEFAULT '' ,                                  
		ipaddr TEXT NOT NULL DEFAULT '',
        zorder INTEGER NOT NULL DEFAULT 0
        )");

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }

    $action["deny"]="<span class='label label-danger'><i class='$icofw'></i> {deny}</span>";
    $action["allow"]="<span class='label label-primary'><i class='$icofw'></i> {allow}</span>";

    $results=$q->QUERY_SQL("SELECT * FROM firewall WHERE profileid=$profile ORDER BY zorder LIMIT 500");

    foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
        $ID=$ligne["ID"];
        $rulename=$tpl->td_href($ligne["rulename"],null,"Loadjs('$page?ruleid-js=$ID&profile=$profile&function=$function');","AsVPNManager");
        $direction=td_direction($ligne);
        $network=td_network($ligne);
		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%' nowrap>{$action[$ligne["action"]]}</td>";
		$html[]="<td>$rulename</td>";
        $html[]="<td width='1%' nowrap>$direction</td>";
        $html[]="<td>$network</td>";

        $up=$tpl->icon_up("Loadjs('$page?rule-move=$ID&acl-rule-dir=1');");
        $down=$tpl->icon_down("Loadjs('$page?rule-move=$ID&acl-rule-dir=0');");



        $html[]="<td width='1%' nowrap>$up&nbsp;$down</td>";
		$html[]="<td width='1%' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsVPNManager")."</td>";
		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="</div>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function td_direction($ligne):string{

    $direction["in"]="{inbound}";
    $direction["out"]="{outbound}";
    if($ligne["direction"]=="all"){
        $ligne["direction"]="{all}";
    }
    return $direction[$ligne["direction"]];


}

function td_network($ligne):string{


    $label_port="{destination_port}";

    $protocol=strtoupper($ligne["protocol"]);
    $ports=$ligne["ports"];
    $ipaddr=$ligne["ipaddr"];
    if(strlen($ipaddr)<3){
        $ipaddr="{alladdresses}";
    }
    if($ipaddr=="0.0.0.0"){
        $ipaddr="{alladdresses}";
    }
    if($ipaddr=="0.0.0.0/0"){
        $ipaddr="{alladdresses}";
    }
    if($ipaddr=="0.0.0.0/0.0.0.0"){
        $ipaddr="{alladdresses}";
    }
    if(strlen($ports)<2){
        $ports="{all}";
    }

    if(strpos($ports,",")>0){
        $label_port="{dest_ports}";
    }


    return sprintf("<strong>%s</strong> $ipaddr $label_port $ports",$protocol);

}