<?php
define("td1prc" ,  "widht=1% style='vertical-align:middle' nowrap");
$GLOBALS["NAT_TYPE"][0]="{destination} NAT";
$GLOBALS["NAT_TYPE"][1]="{source} NAT";
$GLOBALS["NAT_TYPE"][2]="{redirect_nat}";
$GLOBALS["NAT_TYPE"][3]="{route_to}";

define(td1prc ,  "style='vertical-align:middle' nowrap");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.firehol.inc");
include_once(dirname(__FILE__)."/ressources/class.iptables.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search-form"])){search_form();exit;}
if(isset($_POST["SquidFirewallOutTCP"])){squid_outgoing_save();exit;}
if(isset($_POST["SquidFirewallInTCP"])){squid_outgoing_save();exit;}
if(isset($_GET["SQUIDEnableFirewall"])){squid_enable_save();exit;}

function squid_enable_save(){
    $function=$_GET["function"];
    $SQUIDEnableFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnableFirewall"));
    if($SQUIDEnableFirewall==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnableFirewall",1);
        echo "$function();";
        return admin_tracks("Enable Firewall protection for the proxy service");
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnableFirewall",0);
    echo "$function();";
    return admin_tracks("Disable Firewall protection for the proxy service");

}


if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["enable-ruleid"])){rule_enable();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_GET["delete-confirm"])){delete_confirm();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"]);exit;}
if(isset($_GET["fill"])){fill_rule();exit;}
if(isset($_GET["fillup"])){fill_up();exit;}
if(isset($_GET["search-interface"])){js_search_interfaces();exit;}
if(isset($_GET["search-js"])){js_search();exit;}
if(isset($_GET["squid-outgoing-js"])){squid_outgoing_js();exit;}
if(isset($_GET["squid-outgoing-popup"])){squid_outgoing_popup();exit;}
if(isset($_GET["squid-incoming-js"])){squid_incoming_js();exit;}
if(isset($_GET["squid-incoming-popup"])){squid_incoming_popup();exit;}
page();


function page():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $html=$tpl->page_header("{administrate_your_firewall}",ico_firewall, "<strong>{firewall_rules}</strong>: <i>{firewall_about}</i>","$page?search-form=yes",
        "firewall","progress-firehol-restart",true,"search-firewall-function");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Web Application firewall {status}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function search_form():bool{
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $BTS2       = array();
    $BTS3       = array();
    $BTS4       = array();
    $BTS5       = array();
    $NICS=LOAD_NICS();

    foreach ($NICS as $yinter=>$line){
        $BTS2[]="<li><a href=\"#\" OnClick=\"Loadjs('$page?search-interface=$yinter')\">$line</a></li>";
    }

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    if($q->TABLE_EXISTS("pnic_bridges")){
        $sql="SELECT * FROM `pnic_bridges` WHERE `enabled`=1 AND NoFirewall=0";
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne){
            $ID             =intval($ligne["ID"]);
            $NoFirewall     = intval($ligne["NoFirewall"]);
            if($NoFirewall==1){continue;}
            $nic_from       = $ligne["nic_from"];
            $nic_to         = $ligne["nic_to"];
            if($nic_from==null){continue;}
            $title="{connector}: $nic_from ".$tpl->icon_arrow_right()." $nic_to";
            $BTS3[]="<li><a href=\"#\" OnClick=\"Loadjs('$page?search-interface=router:$ID');\">$title</a></li>";
        }
    }

    $results=$q->QUERY_SQL("SELECT * FROM pnic_nat WHERE enabled=1 ORDER BY ID DESC");
    $sNAT_TYPE[0]="DNAT";
    $sNAT_TYPE[1]="SNAT";
    $sNAT_TYPE[2]="RNAT";
    $sNAT_TYPE[3]="XNAT";

    foreach ($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $nic = $NICS[$ligne["nic"]];
        $NAT_TYPE = intval($ligne["NAT_TYPE"]);
        $dstaddr = $ligne["dstaddr"];
        $dstaddrport = intval($ligne["dstaddrport"]);
        if($NAT_TYPE==3){$dstaddrport=0;}
        if ($dstaddrport > 0) {
            $dstaddr = "$dstaddr:$dstaddrport";
        }
        $title = "$nic {$sNAT_TYPE[$NAT_TYPE]} " . $tpl->icon_arrow_right() . " $dstaddr";
        $BTS4[] = "<li><a href=\"#\" OnClick=\"Loadjs('$page?search-interface=NAT:$ID');\">$title</a></li>";
    }

    $results=$q->QUERY_SQL("SELECT * FROM firehol_masquerade WHERE enabled='1' ORDER BY nic");
    if(!$q->ok){echo "<H1>$q->mysql_error</H1>";}
    foreach ($results as $index=>$ligne){
        $ifname=$ligne["nic"];
        $ifname_id=intval($ligne["ID"]);
        $znic=new system_nic($ifname);
        if($znic->enabled==0){continue;}
        $title = "MASQUERADE $znic->NICNAME ($ifname)";
        $BTS5[] = "<li><a href=\"#\" OnClick=\"Loadjs('$page?search-interface=MASQ:$ifname_id');\">$title</a></li>";

    }

    $FINAL=@implode("\n",$BTS2);
	$html[]="
    <div class='row' style='margin-top:-10px'>
                <div class=\"input-group\">
                    <input type='hidden' id='search-fw-interface' value=''>
                    <input type=\"text\" class=\"form-control\" 
                            placeholder='{search}' 
                            onkeypress=\"SearchInFirewallCheck(event);\"
                            id='search-fw-field'>
                        <div class=\"input-group-btn\">
                            <button tabindex=\"-1\" class=\"btn btn-white\" 
                            OnClick=\"Loadjs('$page?search-js=yes');\"
                            type=\"button\" id='fw-btn-title'>{search}: {all_interfaces}</button>
                            <button data-toggle=\"dropdown\" class=\"btn btn-white dropdown-toggle\" type=\"button\"><span class=\"caret\"></span></button>
                            <ul class=\"dropdown-menu pull-right\">
                              
                                
                                $FINAL";
    if(count($BTS5)>0) {
        $html[] = "                <li class=\"divider\"></li>";
        $html[] = @implode("\n", $BTS5);
    }

    if(count($BTS4)>0) {
        $html[] = "                <li class=\"divider\"></li>";
        $html[] = @implode("\n", $BTS4);
    }

	if(count($BTS3)>0) {
        $html[] = "                <li class=\"divider\"></li>";
        $html[] = @implode("\n", $BTS3);
    }
	$all=$tpl->_ENGINE_parse_body("{all_interfaces}");
    $html[]="<li class=\"divider\"></li><li><a href=\"#\" OnClick=\"Loadjs('$page?search-interface=');\">$all</a></li>";
        $html[]="</ul>
                        </div>
                </div>
     <div id='search-firewall-table' style='min-height:450px' class='ibox-content white-bg'></div>       
    </div>
			


<script>
    function SearchInFirewallCheck(e){
        if(!checkEnter(e)){return;}
        SearchInFirewall();
    }

    function SearchInFirewall(){
        Loadjs('$page?search-js=yes');
    }
	Loadjs('$page?search-js=yes');
</script>";


	

	echo $tpl->_ENGINE_parse_body($html);
	return true;
}
function squid_outgoing_js():bool{
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    return $tpl->js_dialog2("{APP_SQUID}: {outgoing}","$page?squid-outgoing-popup=yes&function=$function");
}
function squid_incoming_js():bool{
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    return $tpl->js_dialog2("{APP_SQUID}: {incoming}","$page?squid-incoming-popup=yes&function=$function");
}

function squid_outgoing_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(isset($_POST["SquidFirewallOutTCP"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidFirewallOutTCP",base64_encode($_POST["SquidFirewallOutTCP"]));
    }

    if(isset($_POST["SquidFirewallOutUDP"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidFirewallOutUDP",base64_encode($_POST["SquidFirewallOutUDP"]));
    }

    if(isset($_POST["SquidFirewallInTCP"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidFirewallInTCP",base64_encode($_POST["SquidFirewallInTCP"]));
    }
    return admin_tracks_post("Save dedicated rules for proxy service");
}

function squid_outgoing_datas():array{
    $SquidFirewallOutUDP=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFirewallOutUDP"));
    $SquidFirewallOutTCP=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFirewallOutTCP"));
    $OutUDP=explode("\n",$SquidFirewallOutUDP);
    $OutTCP=explode("\n",$SquidFirewallOutTCP);
    $OutUDPArray=array();
    $OutTCPArray=array();
    foreach ($OutUDP as $line){
        $line=trim($line);
        if(!preg_match("#^(.+?):([0-9]+)#",$line,$m)){
            continue;
        }
        $OutUDPArray[]=$line;
    }
    if(count($OutUDPArray)==0){
        $OutUDPArray[]="*:53";
    }

    foreach ($OutTCP as $line){
        $line=trim($line);
        if(!preg_match("#^(.+?):([0-9]+)#",$line,$m)){
            continue;
        }
        $OutTCPArray[]=$line;
    }
    if(count($OutTCPArray)==0){
        $OutTCPArray[]="*:80";
        $OutTCPArray[]="*:443";
    }
    return array($OutUDPArray,$OutTCPArray);
}
function squid_outgoing_popup():bool{
    $tpl        = new template_admin();
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}

    if(strlen($function)>2){
        $function="$function()";
    }
    list($OutUDPArray,$OutTCPArray)=squid_outgoing_datas();

    $form[]=$tpl->field_textareacode("SquidFirewallOutTCP","TCP {allow}",@implode("\n",$OutTCPArray));
    $form[]=$tpl->field_textareacode("SquidFirewallOutUDP","UDP {allow}",@implode("\n",$OutUDPArray));
    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance2.close();$function");
    return true;
}

function squid_incoming_data():array{
    $SquidFirewallInTCP=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFirewallInTCP"));
    $InTCP=explode("\n",$SquidFirewallInTCP);
    $InTCPArray=array();
    $IP=new IP();
    foreach ($InTCP as $line){
        $line=trim($line);
        if(!$IP->IsACDIROrIsValid($line)){
            continue;
        }
        $InTCPArray[]=$line;
    }
    if(count($InTCPArray)==0){
        $InTCPArray[] = "192.168.0.0/16";
        $InTCPArray[] = "10.0.0.0/8";
        $InTCPArray[] = "172.16.0.0/12";
    }
    return $InTCPArray;
}

function squid_incoming_popup():bool{
    $tpl        = new template_admin();
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $InTCPArray=squid_incoming_data();
    if(strlen($function)>2){
        $function="$function()";
    }
    $form[]=$tpl->field_textareacode("SquidFirewallInTCP","TCP {allow}",@implode("\n",$InTCPArray));

    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance2.close();$function");
    return true;
}


function js_search_interfaces():bool{
    $interface  = $_GET["search-interface"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $search     = $tpl->_ENGINE_parse_body("{search}:");
    header("content-type: application/x-javascript");

    if($interface==null){
        $title="{all_interfaces}";
        $title=$tpl->_ENGINE_parse_body($title);
        $title=str_replace("'","\'",$title);
        echo "document.getElementById('search-fw-big-title').innerHTML='- $title';\n";
        echo "document.getElementById('fw-btn-title').innerHTML='$search$title';\n";
        echo "document.getElementById('search-fw-interface').value='';\n";
        echo "Loadjs('$page?search-js=yes');\n";
        return true;
    }
    if(preg_match("#router:([0-9]+)#",$interface,$re)){
            $ID     = $re[1];
            $sql    = "SELECT * FROM `pnic_bridges` WHERE `ID`=$ID";
            $ligne  = $q->mysqli_fetch_array($sql);
            $nic_from       = $ligne["nic_from"];
            $nic_to         = $ligne["nic_to"];
            $RouterName="{$nic_from}2{$nic_to}";
            $znic=new system_nic($nic_from);
            $NAME_FROM=$znic->NICNAME;
            $znic=new system_nic($nic_to);
            $NAME_TO=$znic->NICNAME;
            $title="$NAME_FROM ($nic_from) ".$tpl->icon_arrow_right()." $NAME_TO ($nic_to)";
            $BigTitle="$nic_from ".$tpl->icon_arrow_right()." $nic_to";
            $title=$tpl=$tpl->_ENGINE_parse_body($title);
            $title=str_replace("'","\'",$title);
            $BigTitle=str_replace("'","\'",$BigTitle);
            echo "document.getElementById('search-fw-big-title').innerHTML='- $BigTitle'\n";
            echo "document.getElementById('search-fw-interface').value='$RouterName';\n";
            echo "document.getElementById('fw-btn-title').innerHTML='$search$title'\n";
            echo "Loadjs('$page?search-js=yes');\n";
            return true;
    }

    $NICS=LOAD_NICS();

    if(preg_match("#MASQ:([0-9]+)#",$interface,$re)){
        $ID=$re[1];
        $ligne=$q->mysqli_fetch_array("SELECT * FROM firehol_masquerade WHERE ID='$ID'");
        $ifname=$ligne["nic"];
        echo "document.getElementById('search-fw-big-title').innerHTML='- MASQUERADE $ifname'\n";
        echo "document.getElementById('search-fw-interface').value='MASQ:$ID';\n";
        echo "document.getElementById('fw-btn-title').innerHTML='{$search}MASQUERADE $ifname'\n";
        echo "Loadjs('$page?search-js=yes');\n";
        return true;
    }

    if(preg_match("#NAT:([0-9]+)#",$interface,$re)){
        $sNAT_TYPE[0]="DNAT";
        $sNAT_TYPE[1]="SNAT";
        $sNAT_TYPE[2]="RNAT";
        $sNAT_TYPE[3]="XNAT";
        $NAT_TYPE=$GLOBALS["NAT_TYPE"];

        $ID     = $re[1];
        $sql    = "SELECT * FROM `pnic_nat` WHERE `ID`=$ID";
        $ligne  = $q->mysqli_fetch_array($sql);
        $nic=$NICS[$ligne["nic"]];
        $NAT_TYPE_TEXT=$NAT_TYPE[$ligne["NAT_TYPE"]];
        $dstaddr=$ligne["dstaddr"];
        $dstaddrport=intval($ligne["dstaddrport"]);
        if($ligne["NAT_TYPE"]==3){$dstaddrport=0;}
        if(intval($dstaddrport)>0){$dstaddr="$dstaddr:$dstaddrport";}
        $BigTitle=" $NAT_TYPE_TEXT $nic".$tpl->icon_arrow_right()." $dstaddr";
        $title = "$nic {$sNAT_TYPE[$NAT_TYPE]} " . $tpl->icon_arrow_right() . " $dstaddr";
        $title=str_replace("'","\'",$title);
        $BigTitle=str_replace("'","\'",$BigTitle);
        echo "document.getElementById('search-fw-big-title').innerHTML='- $BigTitle'\n";
        echo "document.getElementById('search-fw-interface').value='{$sNAT_TYPE[$ligne["NAT_TYPE"]]}:$ID';\n";
        echo "document.getElementById('fw-btn-title').innerHTML='$search$title'\n";
        echo "Loadjs('$page?search-js=yes');\n";
        return true;
    }

    $title="$interface";
    $title=$tpl=$tpl->_ENGINE_parse_body($title);
    $title=str_replace("'","\'",$title);
    echo "document.getElementById('search-fw-big-title').innerHTML='- $title'\n";
    echo "document.getElementById('search-fw-interface').value='$interface';\n";
    echo "document.getElementById('fw-btn-title').innerHTML='$search$title'\n";
    echo "Loadjs('$page?search-js=yes');\n";
    return true;



}
function js_search(){
    header("content-type: application/x-javascript");
    $page   = CurrentPageName();
    $f[]="var StringToSearch=encodeURIComponent(document.getElementById('search-fw-field').value);";
    $f[]="var InTerfaceToSearch=document.getElementById('search-fw-interface').value;";
    $f[]="LoadAjax('search-firewall-table','$page?function=SearchInFirewall&search='+StringToSearch+'&eth='+InTerfaceToSearch)";
    echo @implode("\n",$f);
}


function fill_rule(){
    header("content-type: application/x-javascript");
    $ID     = intval($_GET["fill"]);
    $page   = CurrentPageName();
    $q      = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne  = urlencode(base64_encode(serialize($q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ID'"))));
    $f[]="if(document.getElementById('fw-$ID-rname')){";
    $f[]="  LoadAjaxSilent('fw-$ID-rname','$page?fillup=$ID&section=name&ligne=$ligne');";
    $f[]="}";
    $f[]="if(document.getElementById('fw-$ID-expl')){";
    $f[]="  LoadAjaxSilent('fw-$ID-expl','$page?fillup=$ID&section=expl&ligne=$ligne');";
    $f[]="}";
    $f[]="if(document.getElementById('fw-$ID-nic')){";
    $f[]="  LoadAjaxSilent('fw-$ID-nic','$page?fillup=$ID&section=nic&ligne=$ligne');";
    $f[]="}";
    $f[]="if(document.getElementById('fw-$ID-jact')){";
    $f[]="  LoadAjaxSilent('fw-$ID-jact','$page?fillup=$ID&section=act&ligne=$ligne');";
    $f[]="}";


    echo @implode("\n",$f);

}
function fill_up(){
    $ID     = intval($_GET["fillup"]);
    $section= $_GET["section"];
    $tpl    = new template_admin();
    $ligne  = unserialize(base64_decode($_GET["ligne"]));

    if($section=="name"){
        echo $ligne["rulename"];
        return;
    }
    if($section=="nic"){
        $NICS               = LOAD_NICS(true);
        $NICS_TEXT          = $NICS[$ligne["eth"]];
        echo $NICS_TEXT;
        return;
    }


    if($section=="expl"){
        echo $tpl->_ENGINE_parse_body(EXPLAIN_THIS_RULE($ID));
        return;
    }
    if($section=="act"){
        echo rule_action_status($ligne);
    }
}

function rule_enable(){
	$ID     = $_GET["enable-ruleid"];
	$q      = new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$tpl    = new template_admin();
	$ligne  = $q->mysqli_fetch_array("SELECT enabled FROM iptables_main WHERE ID='$ID'");

	if(intval($ligne["enabled"])==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE iptables_main SET enabled='$enabled' WHERE ID='$ID'");
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);}
		
}
function rule_move_js(){
	header("content-type: application/x-javascript");
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$tpl=new template_admin();
	$dir=$_GET["dir"];
	$ID=intval($_GET["id"]);
	$eth=$_GET["eth"];
	$results=$q->QUERY_SQL("SELECT zOrder FROM iptables_main WHERE ID='$ID'");
	$ligne=$results[0];
	$zorder=intval($ligne["zOrder"]);
	echo "// Current order = $zorder\n";

	if($dir=="up"){
		$zorder=$zorder-1;
		if($zorder<0){$zorder=0;}
	}
	else{
		$zorder=$zorder+1;
	}
	echo "// New order = $zorder\n";
	$q->QUERY_SQL("UPDATE iptables_main SET zOrder='$zorder' WHERE ID='$ID'");
	if(!$q->ok){$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "alert('$q->mysql_error');";return;}

	$c=0;
	$results=$q->QUERY_SQL("SELECT ID FROM iptables_main WHERE eth='$eth' ORDER BY zOrder");
	foreach($results as $indedx=>$ligne){
		$ID=$ligne["ID"];
		echo "// $ID New order = $c";
		$q->QUERY_SQL("UPDATE iptables_main SET zOrder='$c' WHERE ID='$ID'");
		$c++;
	}
}

function delete_js(){
	$ID=$_GET["delete-rule-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
    $md=$_GET["md"];
	$ligne=$q->mysqli_fetch_array("SELECT rulename FROM iptables_main WHERE ID='$ID'");
	$tpl->js_dialog_confirm("{delete} {$ligne["rulename"]}", "$page?delete-confirm=$ID&md=$md");
}

function delete_confirm(){
    $md=$_GET["md"];
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$t=time();
	$ID=$_GET["delete-confirm"];
	$ligne=$q->mysqli_fetch_array("SELECT rulename FROM iptables_main WHERE ID='$ID'");
	$delete_firewall_rule_ask=$tpl->_ENGINE_parse_body("{delete_firewall_rule_ask}");
	$delete_firewall_rule_ask=str_replace("%RULE", $ligne["rulename"], $delete_firewall_rule_ask);
	
	$html[]="
<div class=row>
	<div class=\"alert alert-danger\">$delete_firewall_rule_ask</div>
	<div style='text-align:right;margin-top:20px'><button class='btn btn-danger btn-lg' type='button' 
	OnClick=\"Remove$t()\">{yes_delete_it}</button></div>
</div>
<script>";
	$html[]="var xPost$t= function (obj) {";
    $html[]="var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	DialogConfirm.close();
	$('#$md').remove();
}

function Remove$t(){
	var XHR = new XHRConnection();
	XHR.appendData('delete-remove', '$ID');
	XHR.sendAndLoad('$page', 'POST',xPost$t);
}
</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function delete_remove(){
	$ID=$_POST["delete-remove"];
    $iptables=new iptables();
    $iptables->delete_rule($ID);
	
	
}

function rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-popup"]);
	patch_firewall_tables();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$EnablenDPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablenDPI"));
    if($ID>0) {
        $ligne = $q->mysqli_fetch_array("SELECT rulename,accepttype,isClient FROM iptables_main WHERE ID='$ID'");
        if(preg_match("#<br><small>(.+?)<\/small>#",$ligne["rulename"],$re)){
            $ligne["rulename"]=str_replace("<br><small>{$re[1]}</small>","",$ligne["rulename"]);
        }

        $rulename=$ligne["rulename"];
        if(strlen($rulename)>60) {
            $rulename = substr($rulename, 0, 59)."...";
        }
        $title="{rule}: $rulename";
    }
    $function=$_GET["function"];

    if($ID==0){
        $title="{new_rule} {$_GET["eth"]}";
        if(preg_match("#^([a-z]+[0-9]+)2([a-z]+[0-9]+)$#",$_GET["eth"],$re)) {
            $title = "{new_rule}: {$re[1]} " . $tpl->icon_arrow_right() . " {$re[2]}";
        }

        if(preg_match("#NAT:([0-9]+)#",$_GET["eth"],$re)){
            $NAT_TYPE=$GLOBALS["NAT_TYPE"];
            $ruleid=$re[1];
            $ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$ruleid'");
            $NAT_TYPE_TEXT=$NAT_TYPE[$ligne["NAT_TYPE"]];
            $title = "{new_rule}: $NAT_TYPE_TEXT";
        }
        if(preg_match("#MASQ:([0-9]+)#",$_GET["eth"],$re)){
            $ruleid=$re[1];
            $ligne=$q->mysqli_fetch_array("SELECT * FROM firehol_masquerade WHERE ID='$ruleid'");
            $interface=$ligne["nic"];
            $title = "{new_rule}: Masquerade $interface";
        }

    }
	
	$array[$title]="$page?rule-settings=$ID&eth={$_GET["eth"]}&function=$function";
	if($ID>0){
		
		$array["{firewall_services}"]="fw.rules.services.php?rule-id=$ID&direction=0&eth={$_GET["eth"]}&function=$function";
		if($ligne["isClient"]==0){$array["{inbound_object}"]="fw.rules.objects.php?rule-id=$ID&direction=0&eth={$_GET["eth"]}&function=$function";}
		$array["{outbound_object}"]="fw.rules.objects.php?rule-id=$ID&direction=1&eth={$_GET["eth"]}&function=$function";

		if($EnablenDPI==1){
            $array["{APP_NDPI}"]="fw.rules.ndpi.php?rule-id=$ID&eth={$_GET["eth"]}&function=$function";
        }
		$array["{time_restriction}"]="fw.rules.time.php?rule-id=$ID&eth={$_GET["eth"]}&function=$function";
		$array["{options}"]="fw.rules.options.php?rule-id=$ID&eth={$_GET["eth"]}&function=$function";
		
	}
	
	if($ligne["accepttype"]=="MARK"){unset($array["{firewall_services}"]);}
	
	echo $tpl->tabs_default($array);
	
}
function rule_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
	$ID=intval($_POST["rule-save"]);
	$_POST["rulename"]=mysql_escape_string2(url_decode_special_tool($_POST["rulename"]));
	if(intval($_POST["MARK"])>0){if($_POST["MARK"]>64){$_POST["MARK"]=64;}}
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

	if($ID==0){
        $_POST["MOD"]="None";
    }

	if(isset($_POST["rulename"])){$FADD_FIELDS[]="`rulename`";}
	$FADD_FIELDS[]="`service`";
    if(isset($_POST["accepttype"])){$FADD_FIELDS[]="`accepttype`";}
	$FADD_FIELDS[]="`enabled`";
    if(isset($_POST["eth"])){$FADD_FIELDS[]="`eth`";}
	$FADD_FIELDS[]="`zOrder`";
    if(isset($_POST["jlog"])){$FADD_FIELDS[]="`jlog`";}
	$FADD_FIELDS[]="`application`";
    if(isset($_POST["isClient"])){$FADD_FIELDS[]="`isClient`";}
    if(isset($_POST["MOD"])){$FADD_FIELDS[]="`MOD`";}
	$FADD_FIELDS[]="`proto`";
	$FADD_FIELDS[]="`destport_group`";
	$FADD_FIELDS[]="`dest_group`";
	$FADD_FIELDS[]="`source_group`";
    if(isset($_POST["rulename"])){$FADD_VALS[]=$_POST["rulename"];}
	$FADD_VALS[]=$_POST["service"];
    if(isset($_POST["accepttype"])){$FADD_VALS[]=$_POST["accepttype"];}
	$FADD_VALS[]=$_POST["enabled"];
    if(isset($_POST["eth"])){$FADD_VALS[]=$_POST["eth"];}
	$FADD_VALS[]=$_POST["zOrder"];
    if(isset($_POST["jlog"])){$FADD_VALS[]=$_POST["jlog"];}
	$FADD_VALS[]=$_POST["application"];
    if(isset($_POST["isClient"])){$FADD_VALS[]=$_POST["isClient"];}
    if(isset($_POST["MOD"])){$FADD_VALS[]=$_POST["MOD"];}

	$FADD_VALS[]="tcp";
	$FADD_VALS[]="0";
	$FADD_VALS[]="0";
	$FADD_VALS[]="0";


    if(isset($_POST["ForwardTo"])){
        $FADD_FIELDS[]="`ForwardTo`";
        $FADD_VALS[]=$_POST["ForwardTo"];
    }


    foreach ($FADD_FIELDS as $num=>$field){
		$EDIT_VALS[]="$field ='".$FADD_VALS[$num]."'";
	}

	reset($FADD_VALS);
	foreach ($FADD_VALS as $field){
		$ITEMSADD[]="'$field'";
	}


	if(!$q->FIELD_EXISTS("iptables_main","MARK_BALANCE")){
	    $q->QUERY_SQL("ALTER TABLE iptables_main ADD MARK_BALANCE INTEGER NOT NULL DEFAULT 0");
    }



	if($ID==0){
		$sql="INSERT INTO iptables_main ( ". @implode(",", $FADD_FIELDS).") VALUES (".@implode(",", $ITEMSADD).")";

	}else{
		$sql="UPDATE iptables_main SET  ". @implode(",", $EDIT_VALS)." WHERE ID='$ID'";

	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";}
}

function rule_settings(){
	$tpl=new template_admin();
	$ID=intval($_GET["rule-settings"]);	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $EnableLinkBalancer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));

    $CheckActions["DROP"]="{drop}";
    $CheckActions["ACCEPT"]="{accept}";
    $CheckActions["TPROXY"]="{tproxy_method}";
    $CheckActions["MARK"]="{MARK_ITEM}";
    $CheckActions["NEXTHOPE"]="{forward_traffic}";
    $CheckActions["MIRROR"]="{duplicate_traffic}";


	$btname="{add}";
	$FisClient=true;
	$NoAction=false;
    $NoRuleName=false;
    $NoJlog=false;
	$MOD                        = null;
    $function=$_GET["function"];
    $outgoing_rule_fw_explain="{outgoing_rule_fw_explain}";
    if($function==null){$function="blur";}
    $eth=$_GET["eth"];
	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ID'");
		$eth=$ligne["eth"];
        if(preg_match("#<br><small>(.+?)<\/small>#",$ligne["rulename"],$re)){
            $outgoing_rule_fw_explain=$re[1];
            $ligne["rulename"]=str_replace("<br><small>{$re[1]}</small>","",$ligne["rulename"]);
            $MOD=$ligne["MOD"];
        }

		$title="{rule} $ID) $eth::{$ligne["rulename"]}";
		$btname="{apply}";
		$_GET["eth"]=null;
	}

    $NICS=LOAD_NICS(true);
	ksort($NICS);
	$sql="SELECT server_port,service FROM firehol_services_def WHERE enabled=1 ORDER by service";
	$results = $q->QUERY_SQL($sql);
    $SERVICES["all"]="{all}";
    $SERVICES["RustDesk"]="RustDesk";
	
	foreach ($results as $index=>$ligne2){
		$ligne2["server_port"]=str_replace(" ", ",", $ligne2["server_port"]);
		$lenght=strlen($ligne2["server_port"]);
		if($lenght>50){$ligne2["server_port"]=substr($ligne2["server_port"], 0,47)."...";}
		$SERVICES[$ligne2["service"]]=$ligne2["service"]." ".$ligne2["server_port"];
	}
	
	if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}


	if($ID==0){
	    $BootstrapDialog="dialogInstance1.close();$function();";
	}else{
        $BootstrapDialog="Loadjs('fw.rules.php?fill=$ID');";
    }
	
	$tpl->field_hidden("rule-save", $ID);
    if(preg_match("#^([a-z]+[0-9]+)2([a-z]+[0-9]+)$#",$eth,$re)){
        $FisClient=false;
        $outgoing_rule_fw_explain=null;
        $title="{$re[1]} ".$tpl->icon_arrow_right()." {$re[2]}";
    }

    if(preg_match("#NAT:([0-9]+)#",$eth,$re)){
        $FisClient=false;
        $outgoing_rule_fw_explain=null;
        $CheckActions=array();
        $CheckActions["DROP"]="{drop}";
        $CheckActions["ACCEPT"]="{accept}";

        $ruleid=$re[1];
        $NAT_TYPE=$GLOBALS["NAT_TYPE"];
        $ligne2=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$ruleid'");
        $NAT_TYPE_TEXT=$NAT_TYPE[$ligne2["NAT_TYPE"]];
        $dstaddr=$ligne2["dstaddr"];
        $dstaddrport=intval($ligne2["dstaddrport"]);
        if($ligne2["NAT_TYPE"]==3){$dstaddrport=0;}
        if(intval($dstaddrport)>0){$dstaddr="$dstaddr:$dstaddrport";}
        if($NAT_TYPE==2){
            $dstaddr="0.0.0.0:$dstaddrport";
        }
        if($NAT_TYPE==1){
            if($dstaddrport>0){
                $dstaddr="$dstaddr:$dstaddrport";
            }
        }

        $title="$NAT_TYPE_TEXT ".$tpl->icon_arrow_right()." $dstaddr";
    }

    if(preg_match("#MASQ:([0-9]+)#",$eth,$re)){
        $FisClient=false;
        $outgoing_rule_fw_explain=null;
        $NoAction=true;
        $ruleid=$re[1];
        $ligne2=$q->mysqli_fetch_array("SELECT * FROM firehol_masquerade WHERE ID='$ruleid'");
        $Ifname=$NICS[$ligne2["nic"]];
        $title="$Ifname ".$tpl->icon_arrow_right()." Masquerade";

    }

    if($MOD=="IPFEED"){
        $FisClient=false;
        $NoRuleName=true;
        $NoAction=true;
        $NoJlog=true;
    }

    if($FisClient){$form[]=$tpl->field_checkbox("isClient","{outgoing_rule}",$ligne["isClient"]);}
	if(!$NoRuleName){$form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"]);}

    if($ID>0){
        if($ligne["accepttype"]=="MIRROR"){
            $form[]=$tpl->field_ipaddr("ForwardTo","{redirect_nat}",$ligne["ForwardTo"]);
        }
    }

    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_numeric("zOrder","{order}",$ligne["zOrder"]);

    if($_GET["eth"]==null) {
        $form[] = $tpl->field_array_hash($NICS, "eth", "{interface}", $ligne["eth"], false);
    }else{
        $form[] = $tpl->field_hidden("eth", $_GET["eth"]);
    }
	$form[]=$tpl->field_array_hash($SERVICES,"service","{service2}",$ligne["service"]);

    if($EnableLinkBalancer==1) {$CheckActions["lINK"] = "{APP_LINK_BALANCER}";}

	if(!$NoAction) {
        $form[] = $tpl->field_array_hash($CheckActions, "accepttype",
            "{action}",$ligne["accepttype"], true);
    }
	if(!$NoJlog){$form[]=$tpl->field_checkbox("jlog","{log_all_events}",$ligne["jlog"]);}

    if($EnableLinkBalancer==1) {
        $form[] = $tpl->field_section("{APP_LINK_BALANCER}", "{redirect_to_the_specified_link}");
        $sql="SELECT ID,Interface FROM link_balance WHERE enabled=1 ORDER by Interface";
        $resultsMAN = $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error_html(true);}
        $LINK_BALANCE=array();
        foreach ($resultsMAN as $index1=>$MARZ){
            $Interface=$MARZ["Interface"];
            $nic=new system_nic($Interface);
            $LINK_BALANCE[$MARZ["ID"]]="$Interface $nic->NICNAME [$nic->IPADDR]";

        }
        $form[]=$tpl->field_array_hash($LINK_BALANCE,"MARK_BALANCE",
            "{network}",$ligne["MARK_BALANCE"],true);

    }

	
	echo $tpl->form_outside($title,@implode("\n", $form),$outgoing_rule_fw_explain,$btname,"$BootstrapDialog","AsFirewallManager");
	
	
}

function rule_js(){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$tpl=new template_admin();
    $function=$_GET["function"];
	$ruleid=intval($_GET["ruleid-js"]);
    if(!isset($_GET["eth"])){$_GET["eth"]="";}
	if($ruleid==0){
		$ligne["rulename"]="{new_rule}";
	}else{
		$ligne=$q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ruleid'");
        if(preg_match("#<br><small>(.+?)<\/small>#",$ligne["rulename"],$re)){
            $ligne["rulename"]=str_replace("<br><small>{$re[1]}</small>","",$ligne["rulename"]);
        }
	}
	$tpl->js_dialog1("{rule}: $ruleid {$ligne["rulename"]} {$ligne["accepttype"]}","$page?rule-popup=$ruleid&eth={$_GET["eth"]}&function=$function",1100);
}

function rule_action_status($ligne){
    $jlog               = null;
    $ACTION             = null;
    $ID                 = $ligne["ID"];
    $eth                = $ligne["eth"];
    $IPTABLES_RRULES_STATUS = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_RRULES_STATUS"));

    if(!isset($IPTABLES_RRULES_STATUS[$ID])){
        $ligne["enabled"]=0;
    }

    $Outgoing           = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";

    if(preg_match("#NAT:([0-9]+)#",$eth,$re)){
        $ligne["isClient"]=0;
        $Outgoing           = "<span class='label'>&nbsp;&nbsp;NAT&nbsp;&nbsp;</span>&nbsp;";
    }

    if(preg_match("#MASQ:([0-9]+)#",$eth,$re)){
        $ligne["isClient"]=0;
        $Outgoing           = "<span class='label'>&nbsp;&nbsp;MASQ.&nbsp;&nbsp;</span>&nbsp;";
    }
    if(preg_match("#router:([0-9]+)#",$eth,$re)){
        $ligne["isClient"]=0;
        $Outgoing           = "<span class='label'>&nbsp;&nbsp;FORWARD&nbsp;&nbsp;</span>&nbsp;";
    }
    if(preg_match("#[a-z]+[0-9]+2[a-z]+[0-9]+#",$eth,$re)){
        $ligne["isClient"]=0;
        $Outgoing           = "<span class='label'>&nbsp;&nbsp;FORWARD&nbsp;&nbsp;</span>&nbsp;";
    }


    if($ligne["isClient"]==1){
        $Outgoing           = "<span class='label'>OUT</span>&nbsp;";
    }

    if($ligne["jlog"]==1){
        $IPTABLES_RRULES_STATUS = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_RRULES_STATUS"));
        if(isset($IPTABLES_RRULES_STATUS["LOG"][$ID])) {
            $jlog = "<span class='label label-info'>LOG</span>&nbsp;";
        }else{
            $jlog = "<span class='label'>LOG</span>&nbsp;";
        }
    }

    if($ligne["accepttype"]=="DROP"){
        $ACTION="<span class='label label-danger'>DENY</span>";
        if($ligne["enabled"]==0){
            $ACTION="<span class='label'>NONE</span>";
        }
    }

    if($ligne["accepttype"]=="RETURN"){
        $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    }

    if($ligne["accepttype"]=="ACCEPT"){
        $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    }

    if($ligne["accepttype"]=="LOG"){
        $ACTION="<span class='label label-info'>INFO</span>";
    }
    if($ligne["accepttype"]=="MARK"){
        $ACTION="<span class='label label-info'>MARK</span>";
    }
    if ($ligne["accepttype"] == "lINK") {
        $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    }
    if ($ligne["accepttype"] == "TPROXY") {
        $Outgoing="<span class='label label-primary'>FORWARD</span>&nbsp;";
        $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
        if($ligne["enabled"]==0){
            $Outgoing="<span class='label'>FORWARD</span>&nbsp;";
            $ACTION="<span class='label'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
        }

    }
    if ($ligne["accepttype"] == "MIRROR") {
        $ACTION="<span class='label label-primary'>MIRROR</span>";
    }


    if($ligne["enabled"]==0){
        if($ligne["jlog"]==1){$jlog="<span class='label'>LOG</span>&nbsp;";}
        $ACTION="<span class='label'>NONE</span>";

    }

    return "$Outgoing$jlog$ACTION";

}

function LOAD_NICS($routers=false){

    if(isset($GLOBALS["LOAD_NICS"][$routers])) {
        return $GLOBALS["LOAD_NICS"][$routers];
    }

    $nic        = new networking();
    $tpl        = new template_admin();
    $nicZ       = $nic->Local_interfaces();
    $NICS       = array();
    $NICS[null]="{all_interfaces}";
    foreach ($nicZ as $yinter=>$line){
        if($yinter=="lo"){continue;}
        $znic=new system_nic($yinter);
        if(preg_match("#^dummy#", $yinter)){continue;}
        if(preg_match("#-ifb$#", $yinter)){continue;}
        if($znic->Bridged==1){continue;}
        if($znic->enabled==0){continue;}
        $NICS[$yinter]="$znic->NICNAME ($yinter)";
   }

   if(!$routers){
       $GLOBALS["LOAD_NICS"][$routers]=$NICS;
       return $NICS;
   }
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    if($q->TABLE_EXISTS("pnic_bridges")){
        $sql="SELECT * FROM `pnic_bridges` WHERE `enabled`=1 AND NoFirewall=0";
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne2){
            $nic_from=$ligne2["nic_from"];
            $nic_to=$ligne2["nic_to"];
            $RouterName="{$nic_from}2{$nic_to}";
            $RouterExpl="$nic_from {to} $nic_to";
            if(strlen($ligne2["rulename"])>2){
                $RouterExpl=$ligne2["rulename"];
            }
            $NICS[$RouterName]=$tpl->javascript_parse_text("{connector}: $RouterExpl");
        }
    }

    $results=$q->QUERY_SQL("SELECT * FROM pnic_nat ORDER BY ID DESC");
    $NAT_TYPE=$GLOBALS["NAT_TYPE"];

    $sNAT_TYPE[0]="DNAT";
    $sNAT_TYPE[1]="SNAT";
    $sNAT_TYPE[2]="RNAT";
    $sNAT_TYPE[3]="XNAT";

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $type=$ligne["NAT_TYPE"];
        $NAT_TYPE_TEXT  =$NAT_TYPE[$type];
        $dstaddr=$ligne["dstaddr"];
        $nic=$ligne["nic"];
        $dstaddrport=intval($ligne["dstaddrport"]);
        $rulename=$ligne["rulename"];

        if($type==3){$dstaddrport=0;}
        if(intval($dstaddrport)>0){$dstaddr="$dstaddr:$dstaddrport";}
        if($NAT_TYPE==2){$dstaddr="0.0.0.0:$dstaddrport";}
        $TDef="$nic - $NAT_TYPE_TEXT<br><small>&nbsp;$dstaddr</small>";
        if(strlen($rulename)>1){
            $TDef="$rulename <small>($nic - $NAT_TYPE_TEXT)</small>";
        }
        $NICS["{$sNAT_TYPE[$type]}:$ID"]=$TDef;
    }
    $results=$q->QUERY_SQL("SELECT * FROM firehol_masquerade WHERE enabled='1' ORDER BY nic");

    foreach ($results as $index=>$ligne){
        $ifname=$ligne["nic"];
        $ifname_id=intval($ligne["ID"]);
        $znic=new system_nic($ifname);
        if($znic->enabled==0){continue;}
        $NICS["MASQ:$ifname_id"]="MASQUERADE $znic->NICNAME ($ifname)";
    }


    $GLOBALS["LOAD_NICS"][$routers]=$NICS;
    return $NICS;
}





function table_fw_NAT($eth,$TRCLASS){

    if(preg_match("#([A-Z])NAT:([0-9]+)#",$eth,$re)) { $NatID=$re[2];}
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$NatID'");
    $NAT_TYPE   = $ligne["NAT_TYPE"];
    $interface  = $ligne["nic"];
    $dstaddr    = $ligne["dstaddr"];
    $dstaddrport= $ligne["dstaddrport"];
    $jlog       = $ligne["jlog"];
    $STATUS     = "<span class='label label-white'>{unknown}</span>";
    $NICS       = LOAD_NICS(false);
    $Outgoing   = "<span class='label'>OUT</span>&nbsp;";
    $tpl        = new template_admin();
    $tdfree     = $tpl->table_tdfree();
    $td1prc     = $tpl->table_td1prc();
    $pks        = $tpl->icon_nothing();
    $size       = $tpl->icon_nothing();
    $ACTION     = "<span class='label label-primary'>NAT</span>";
    $sNAT_TYPE=$GLOBALS["NAT_TYPE"];
    $IPTABLES_RRULES_STATUS = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_RRULES_STATUS"));
    $html=array();
    if($NAT_TYPE==3){
        $ACTION     = "<span class='label label-primary'>FORWARD</span>";
        $STATUS     ="<span class='label label-primary'>{default}</span>";
        $dstaddrport=0;}
    if($dstaddrport>0){
        $dstaddr="$dstaddr:$dstaddrport";
    }

    if($jlog==1){
        $jlog=" {and} {log_all_events}";
        $jlogico = "&nbsp;<span class='label label-info'>LOG</span>&nbsp;";
    }

    if(isset($IPTABLES_RRULES_STATUS["NAT"][$NatID])){
        $MAIN=$IPTABLES_RRULES_STATUS["NAT"][$NatID];
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($MAIN["pkts"]));
        $size               = intval($MAIN["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }

    $html[] = "<tr class='$TRCLASS'>";
    $html[] = "<td $td1prc>".$tpl->icon_nothing()."</td>";


    $html[] = "<td $tdfree><strong>{$sNAT_TYPE[$NAT_TYPE]} &nbsp;-&nbsp; $dstaddr{$jlog}</strong></td>";
    $html[] = "<td $td1prc>{$NICS[$interface]}</span></td>";
    $html[] = "<td $td1prc>$STATUS</td>";
    $html[] = "<td style='text-align:right' nowrap>$pks</td>";
    $html[] = "<td $td1prc>$size</td>";
    $html[] = "<td $td1prc>$Outgoing{$jlogico}$ACTION</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "</tr>";


    return @implode("\n",$html);

}
function table_fw_policy($interface,$TRCLASS){
    if($interface==null){return;}

    if(preg_match("#NAT:[0-9]+#",$_GET["eth"],$re)){return table_fw_NAT($_GET["eth"],$TRCLASS);}
    if(preg_match("#([a-z]+[0-9]+)2([a-z]+[0-9])$#",$_GET["eth"],$re)){return;}
   // echo "<H1>table_fw_policy $interface</H1>";
    $tpl        = new template_admin();
    $znic       = new system_nic($interface);
    $default    = "{policy}: {default}";
    $td1prc     = $tpl->table_td1prc();
    $NICS       = LOAD_NICS();
    $STATUS          = "<span class='label label-white'>{default}</span>";
    $IPTABLES_RRULES_STATUS = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_RRULES_STATUS"));
    $text_class = null;
    $html[] = "<tr class='$TRCLASS'>";
    $html[] = "<td class=\"center\"><span class=''>-</span></td>";
    if (($znic->firewall_policy == 'accept') OR ($znic->firewall_policy == null)) {
        $Outgoing = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
        $ACTION = "<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
        $html[] = "<td class='' style='vertical-align:middle'><strong>$default</strong><br>" . $tpl->_ENGINE_parse_body("{finally_allow_all}") . "</td>";
        }

        if ($znic->firewall_policy == 'reject') {
            $Outgoing = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
            $ACTION = "<span class='label label-danger'>DENY</span>";
            $html[] = "<td class='$text_class' style='vertical-align:middle'><span class='$text_class'><strong>$default</strong><br>" . $tpl->_ENGINE_parse_body("{finally_deny_all}") . "</td>";
        }

    if(isset($IPTABLES_RRULES_STATUS[$interface])){
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS[$interface]["pkts"]));
        $size               = intval($IPTABLES_RRULES_STATUS[$interface]["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }


    $html[]="<td style='text-align:left'>{$NICS[$interface]}</td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc>$Outgoing$ACTION</td>";
    $html[]="<td $td1prc'>".$tpl->icon_nothing()."</td>";
    $html[]="<td $td1prc>".$tpl->icon_nothing()."</td>";
    $html[]="<td $td1prc>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";

    return @implode("\n",$html);

}

function table_fw_itself($TRCLASS,$eth){
    if($eth<>null) {
        if (!preg_match("#^([eth|wan])#", $eth)) {
            return null;
        }
    }
    if(preg_match("#[a-z]+[0-9]+2[a-z]+[0-9]#",$_GET["eth"])){return null;}

    $IPTABLES_RRULES_STATUS = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_RRULES_STATUS"));

    $text_class = null;
    $ID         = "1000395";
    $color      = null;
    $tpl        = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $null_ico   = $tpl->icon_nothing();
    $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    $tdfree = "class='$text_class' style='vertical-align:middle'";
    $td1prc = "widht=1% class='$text_class center' style='vertical-align:middle' nowrap";
    $STATUS = "<span class='label label-white'>{default}</span>";
    $Outgoing = "<span class='label'>&nbsp;&nbsp;OUT&nbsp;&nbsp;</span>&nbsp;";
    $ethName  = "{all_interfaces}";
    $STATUS             = "<span class='label'>{inactive}</span>";
    $pks                = $tpl->icon_nothing();
    $size               = $tpl->icon_nothing();
    $subrulename        = null;


    if(isset($IPTABLES_RRULES_STATUS["1000395"])){
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS[$ID]["pkts"]));
        $size               = intval($IPTABLES_RRULES_STATUS[$ID]["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }


    $md=md5(time());
    if($eth<>null) {
        $LOAD_NICS = LOAD_NICS();
        $ethName = $LOAD_NICS[$eth];
    }
    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";
    $items=FormatNumber($q->COUNT_ROWS("firehol_itself"));
    $EXPLAIN=$tpl->_ENGINE_parse_body("{firewall_itself_explain}");
    $EXPLAIN=str_replace("%s",$items,$EXPLAIN);
    $js="Loadjs('fw.rules.itself.php');";
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>0/0</span></td>";
    $html[]="<td $tdfree><span class='$text_class' style='font-weight:bold;$color'>".$tpl->td_href("<span id='fw-$ID-rname' class='text-success'>{default}</span>",null,$js)."<span id='fw-$ID-expl' style='color:black;font-weight:normal'><br>$EXPLAIN</span></td>";
    $html[]="<td>$iconNet<span class='$text_class' id='fw-$ID-nic'>$ethName</span></td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc><span id='fw-$ID-jact'>$Outgoing$ACTION</span></td>";
    $html[]="<td $td1prc>".$tpl->icon_check(1)."</td>";
    $html[]="<td $td1prc><br>$null_ico</td>";
    $html[]="<td $td1prc><br>$null_ico</td>";
    $html[]="</tr>";

    return @implode("\n",$html);
}

function table_fw_router($interface,$interface2,$TRCLASS){
    $log=null;$jlogico=null;
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_bridges 
    WHERE nic_from='$interface' AND nic_to='$interface2'");


    if($ligne["jlog"]==1){
        $log=" {and} {log_all_events}";
        $jlogico = "&nbsp;<span class='label label-info'>LOG</span>&nbsp;";
    }

    $BEHA[1]="{finally_deny_all}";
    $BEHA[0]="{finally_allow_all}";
    $ID         = $ligne["ID"];
    $policy     = $ligne["policy"];
    $tpl        = new template_admin();
    $default    = "{connector}: {packets_from} {$interface} {should_be_forwarded_to} $interface2";
    $td1prc     = $tpl->table_td1prc();
    $NICS       = LOAD_NICS();
    $STATUS          = "<span class='label label-white'>{unknown}</span>";
    $IPTABLES_RRULES_STATUS = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_RRULES_STATUS"));
    $text_class = "black";
    $html[] = "<tr class='$TRCLASS'>";
    $html[] = "<td class=\"center\"><span class=''>-</span></td>";
    $Outgoing = "<span class='label'>&nbsp;&nbsp;FORWARD&nbsp;&nbsp;</span>&nbsp;";
    if($policy==0) {
        $ACTION = "<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
        $html[] = "<td class='' style='vertical-align:middle'><strong>$default</strong><br>" . $tpl->_ENGINE_parse_body("{finally_allow_all}") . "$log</td>";
    }

    if($policy==1){
        $Outgoing = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
        $ACTION = "<span class='label label-danger'>DENY</span>";
        $html[] = "<td class='$text_class' style='vertical-align:middle;color:black'><span class='$text_class'><strong>$default</strong><br>" . $tpl->_ENGINE_parse_body("{finally_deny_all}") . "$log</td>";


    }


    if(isset($IPTABLES_RRULES_STATUS["ROUTER"][$ID])){
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS["ROUTER"][$ID]["pkts"]));
        $size               = intval($IPTABLES_RRULES_STATUS["ROUTER"][$ID]["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }


    $html[]="<td $td1prc>{$NICS[$interface]}</td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc>$Outgoing$jlogico$ACTION</td>";
    $html[]="<td $td1prc'>".$tpl->icon_nothing()."</td>";
    $html[]="<td $td1prc>".$tpl->icon_nothing()."</td>";
    $html[]="<td $td1prc>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";

    return @implode("\n",$html);

}

function table_fw_artica($interface,$TRCLASS){
    $tpl             = new template_admin();
    $znic            = new system_nic($interface);
    $NICS            = LOAD_NICS();
    $accept_artica_w = $tpl->_ENGINE_parse_body("{accept_artica_w} {from_trusted_networks}");
    $ACTION          = "<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    $pks             = $tpl->icon_nothing();
    $size            = $tpl->icon_nothing();
    $text_class      = null;
    if($znic->Bridged==1){return;}
    if($znic->enabled==0){return;}
    if($znic->isFW==0){return;}
    if($_GET["search"]<>null) {return;}
    if($znic->firewall_artica == 0){return;}
    $ArticaHttpsPort                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));

    $tdfree = "class='$text_class' style='vertical-align:middle'";
    $td1prc = $tpl->table_td1prc();
    $STATUS = "<span class='label label-white'>{default}</span>";
    $Outgoing = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $pks=$tpl->FormatNumber($pks);

            $html[] = "<tr class='$TRCLASS'>";
            $html[] = "<td $td1prc><span class='$text_class'>-</span></td>";
            $html[] = "<td $tdfree><span class='$text_class'><strong>$accept_artica_w</strong><br>TCP/$ArticaHttpsPort</td>";
            $html[] = "<td $td1prc><br>{$NICS[$interface]}</span></td>";
            $html[] = "<td $td1prc>$STATUS</td>";
            $html[] = "<td style='text-align:right' nowrap>$pks</td>";
            $html[] = "<td $td1prc>$size</td>";
            $html[] = "<td $td1prc>$Outgoing$ACTION</td>";
            $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
            $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
            $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
            $html[] = "</tr>";

            return @implode("\n",$html);
}

function iptables_status(){

    $sock=new sockets();
    $data=json_decode($sock->REST_API("/system/network/iptables/stats"));
    return $data->Data;
}

function table_fw_trusted_networks($TRCLASS){
    $tpl             = new template_admin();
    $pks             = $tpl->icon_nothing();
    $size            = $tpl->icon_nothing();


    $status=iptables_status();
    if(property_exists($status,"Family")) {
        if(property_exists($status->Family,"TRUSTEDNETS")) {
            $pks=$tpl->FormatNumber($status->Family->TRUSTEDNETS->{0}->pkts);
            $size=FormatBytes($status->Family->TRUSTEDNETS->{0}->bytes/1024);
        }
    }
    $accept_artica_w = $tpl->_ENGINE_parse_body("{accept} {all} {from_trusted_networks}");
    $ACTION          = "<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    $text_class      = null;


    $tdfree = "class='$text_class' style='vertical-align:middle'";
    $td1prc = $tpl->table_td1prc();
    $STATUS = "<span class='label label-default'>{default}</span>";
    $Outgoing = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";
    $html[] = "<tr class='$TRCLASS'>";
    $html[] = "<td $td1prc><span class='$text_class'>-</span></td>";
    $html[] = "<td $tdfree><span class='$text_class'><strong style='color:black'>$accept_artica_w</strong></td>";
    $html[] = "<td $tdfree>$iconNet{all_interfaces}</td>";
    $html[] = "<td $td1prc>$STATUS</td>";
    $html[] = "<td style='text-align:right' nowrap>$pks</td>";
    $html[] = "<td $td1prc>$size</td>";
    $html[] = "<td $td1prc>$Outgoing$ACTION</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "</tr>";

    return array($TRCLASS,@implode("\n",$html));
}

function getEnabledInterfaces():array{
    $MAIN=array();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT Interface,isFW FROM nics");
    foreach ($results as $index=>$ligne) {
        $MAIN[$ligne["Interface"]]=$ligne["isFW"];
    }
    return $MAIN;
}

function table(){
	$tpl    = new template_admin();
	$page   = CurrentPageName();
    $q      = new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$eth_sql=null;
	$token=null;
    $TRCLASS=null;
	$order=$tpl->_ENGINE_parse_body("{order}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
    $EnableLinkBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));
	$type=$tpl->_ENGINE_parse_body("{type}");
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
	$function=$_GET["function"];
    $NICS=LOAD_NICS(true);

	if($_GET["eth"]<>null){$eth_sql=" WHERE eth='{$_GET["eth"]}'";$token="&eth={$_GET["eth"]}";}
	

	$nic=new networking();
	$nicZ=$nic->Local_interfaces();
	$interface=$tpl->_ENGINE_parse_body("{interface}");
    $add="Loadjs('$page?ruleid-js=0$token&function=$function',true);";
	


    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure","firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-firehol-restart",
        "$function()");



    $topbuttons[] = array($add, ico_plus, "{new_rule}");
    $topbuttons[] = array($jsrestart, ico_refresh, "{apply_firewall_rules}");
	


	$th_center="data-sortable=true class='text-capitalize center' data-type='text' width='1%'";
	$html[]="<table id='table-webfilter-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' nowrap>$order/id</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rulename</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{$interface}</th>";
    $html[]="<th $th_center>{status}</th>";
    $html[]="<th $th_center>{packets}</th>";
    $html[]="<th $th_center>{size}</th>";
	$html[]="<th $th_center>$type</th>";
	$html[]="<th data-sortable=false>{enabled}</th>";
	$html[]="<th data-sortable=false>{order}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-firewall-rules','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/iptables/stats");
    $json=json_decode($data);
    $JSON_STATUS=$json->Data;


	if($_GET["search"]==null){

        list($TRCLASS,$ipfeeds)=table_cybercrime($TRCLASS,$JSON_STATUS);
        if($ipfeeds<>null){
            $html[]=$ipfeeds;
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]=table_fw_itself($TRCLASS,$_GET["eth"]);
        list($TRCLASS,$table)=table_fw_trusted_networks($TRCLASS);
        $html[]=$table;
    }



	$subquery=null;
	if($_GET["search"]<>null){
			$MULTIPLEACLS=array();
			$_GET["search"]="*{$_GET["search"]}*";
			$_GET["search"]=str_replace("**", "*", $_GET["search"]);
			$_GET["search"]=str_replace("**", "*", $_GET["search"]);
			$_GET["search"]=str_replace("*", "%", $_GET["search"]);
			
			
			$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
			$sql="SELECT gpid from webfilters_sqitems WHERE `pattern` LIKE '{$_GET["search"]}'";
			$results=$q->QUERY_SQL($sql);
			foreach ($results as $index=>$ligne){
				$gpid=$ligne["gpid"];
				$ligne2=$q->mysqli_fetch_array("SELECT aclid FROM 
                            firewallfilter_sqacllinks WHERE gpid='$gpid'");
				if(intval($ligne2["aclid"])>0){$MULTIPLEACLS[$ligne2["aclid"]]=$ligne2["aclid"];}
			}
			
			$sql="SELECT ID from webfilters_sqgroups WHERE `GroupName` LIKE '{$_GET["search"]}'";
			$results=$q->QUERY_SQL($sql);
			foreach ($results as $index=>$ligne){
				$gpid=$ligne["ID"];
				$ligne2=$q->mysqli_fetch_array("SELECT aclid FROM 
                                                firewallfilter_sqacllinks WHERE gpid='$gpid'");
				if(intval($ligne2["aclid"])>0){$MULTIPLEACLS[$ligne2["aclid"]]=$ligne2["aclid"];}
			}
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $sql="SELECT ID FROM iptables_main WHERE rulename LIKE '{$_GET["search"]}'";
        $results=$q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne){$MULTIPLEACLS[$ligne["ID"]]=$ligne["ID"];}
	
		$sql="SELECT * FROM iptables_main";
		$results=$q->QUERY_SQL($sql);
		foreach ($results as $index=>$ligne){
			$service=$ligne["service"];
			$services_containers=unserialize(base64_decode($ligne["services_container"]));
			if($service<>null){$services_containers[$service]=true;}
			$_GET["search"]=str_replace("%", ".*?", $_GET["search"]);
			foreach ($services_containers as $service=>$none){
				if(preg_match("#{$_GET["search"]}#i", $service)){
					$MULTIPLEACLS[$ligne["ID"]]=$ligne["ID"];
				}
			}
		}
	
		$sbquery=array();
		if($_GET["eth"]==null){$WHERE="WHERE";}else{$WHERE="AND";}
		foreach ($MULTIPLEACLS as $aclid=>$none){$sbquery[]="(ID=$aclid)";}
		if(count($sbquery)>0){$subquery=" $WHERE ".@implode(" ", $sbquery)."";}
		if(count($sbquery)>1){$subquery=" $WHERE (".@implode(" OR ", $sbquery).")";}
			

	}



	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("firehol.php?rules-status=yes");
	$IPTABLES_RRULES_STATUS = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_RRULES_STATUS"));
	$sqlsrc                 = "SELECT * FROM iptables_main$eth_sql$subquery ORDER BY zOrder";
	$results                = $q->QUERY_SQL($sqlsrc);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error."<hr>$sqlsrc");}

    /*
    list($TRCLASS,$ipfeeds)=table_crowdsec($TRCLASS,$IPTABLES_RRULES_STATUS);
    if($ipfeeds<>null){
        $html[]=$ipfeeds;
    }
    */



    $getEnabledInterfaces=getEnabledInterfaces();

	foreach ($results as $index=>$ligne){
		$disabled_explain   = null;
		$text_class         = null;
		$color              = null;
		$md                 = md5(serialize($ligne));
        $ID                 = intval($ligne["ID"]);
        $STATUS             = "<span class='label'>{inactive}</span>";
        $pks                = $tpl->icon_nothing();
        $size               = $tpl->icon_nothing();
        $subrulename        = null;
        $MOD                = $ligne["MOD"];
        $textBadInterface   = "";

        //echo "<H1>{$ligne["eth"]}</H1>";



        if(isset($IPTABLES_RRULES_STATUS[$ID])){
            $STATUS             ="<span class='label label-primary'>{active2}</span>";
            $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS[$ID]["pkts"]));
            $size               = intval($IPTABLES_RRULES_STATUS[$ID]["bytes"]);
            if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
        }
		
		if($ligne["enabled"]==0) {
            if ($ligne["accepttype"] <> "lINK") {
                $color = "color:#8a8a8a;text-decoration:underline";
            }
        }

		if($EnableLinkBalancer==0){
            if ($ligne["accepttype"] == "lINK") {$color = "color:#8a8a8a;text-decoration:underline";}
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

		if($ligne["enabled"]==0){
			$text_class=" text-muted";
		}
		
		
		if($ligne["rulename"]==null){$ligne["rulename"]="No name";}

		if(preg_match("#<br><small>(.+?)<\/small>#",$ligne["rulename"],$re)){
            $subrulename="<br><small>{$re[1]}</small>";
            $ligne["rulename"]=str_replace("<br><small>{$re[1]}</small>","",$ligne["rulename"]);
        }

        $NICS_TEXT = $NICS[$ligne["eth"]];
        if(isset($getEnabledInterfaces[$ligne["eth"]])){
            if($getEnabledInterfaces[$ligne["eth"]]==0){
                $textBadInterface="<span class='label label-warning'>{$ligne["eth"]} {inactive2}</span>&nbsp;&nbsp;";
                $text_class=" text-muted";
                $ligne["enabled"]=0;
            }
        }


        if($EnableLinkBalancer==0){
            if ($ligne["accepttype"] == "lINK") {
                $NICS_TEXT = $ligne["eth"] . " - {disabled}";}
        }



        if(!preg_match("#NAT:[0-9]+#",$ligne["eth"])) {
            if ($ligne["accepttype"] <> "lINK") {
                if (!isset($NICS[$ligne["eth"]])) {
                    $NICS_TEXT = $ligne["eth"] . " - {disabled}";
                    $text_class = " text-muted";
                    $disabled_explain = "yes";
                }
            }
        }

		$up=$tpl->icon_up("Loadjs('$page?move-js=yes&id=$ID&dir=up&eth={$ligne["eth"]}')","AsFirewallManager");
		$down=$tpl->icon_down("Loadjs('$page?move-js=yes&id=$ID&dir=down&eth={$ligne["eth"]}')","AsFirewallManager");
		
		if($ligne["zOrder"]==0){$up=null;}
		$js         = "Loadjs('$page?ruleid-js=$ID&function=$function',true);";
		$delete     = $tpl->icon_delete("Loadjs('$page?delete-rule-js=$ID&md=$md')","AsFirewallManager");
		$EXPLAIN    = EXPLAIN_THIS_RULE($ligne,$disabled_explain);
		$tdfree     = $tpl->table_tdfree($text_class);
		$td1prc     = $tpl->table_td1prc($text_class);
		$td_enable  = $tpl->icon_check($ligne["enabled"],
                    "Loadjs('$page?enable-ruleid={$ligne["ID"]}')","AsFirewallManager");

		$rule_action= rule_action_status($ligne);
		if($MOD=="IPFEED"){$delete=$tpl->icon_nothing();}

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td $td1prc><span class='$text_class'>{$ligne["zOrder"]}/{$ligne["ID"]}</span></td>";
		$html[]="<td $tdfree><span class='$text_class' style='font-weight:bold;$color'>$textBadInterface".
            $tpl->td_href("<span id='fw-$ID-rname' class='text-success'>{$ligne["rulename"]}</span>",null,$js)."{$subrulename}</span><span id='fw-$ID-expl'>$EXPLAIN</span>
        </td>";
        $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";
		$html[]="<td $tdfree nowrap>
                    <span class='$text_class' id='fw-$ID-nic' style='text-align: left'>$iconNet$NICS_TEXT</span>
                </td>";
        $html[]="<td $td1prc>$STATUS</td>";
        $html[]="<td style='text-align:right' nowrap>$pks</td>";
        $html[]="<td $td1prc>$size</td>";
		$html[]="<td $td1prc><span id='fw-$ID-jact'>$rule_action</span></td>";
		$html[]="<td $td1prc>$td_enable</td>";
		$html[]="<td $td1prc>$up&nbsp;&nbsp;$down</td>";
		$html[]="<td $td1prc>$delete</td>";
		$html[]="</tr>";
	
	}
	reset($nicZ);



    list($TRCLASS,$ipfeeds)=table_nginx($TRCLASS,$IPTABLES_RRULES_STATUS);
    if($ipfeeds<>null){
        $html[]=$ipfeeds;
    }
    list($TRCLASS,$ipfeeds,$tpl)=table_proxy($TRCLASS,$JSON_STATUS,$tpl);
    if($ipfeeds<>null){
        $html[]=$ipfeeds;
    }


    list($TRCLASS,$ipfeeds)=table_all_in($TRCLASS,$JSON_STATUS);
    if($ipfeeds<>null){
        $html[]=$ipfeeds;
    }


	if(count($nicZ)==0){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td colspan=10><strong>Interface as no array ?</strong></td>";
		$html[]="</tr>";
		
	}
	
	$nicZ["lo"]="{interface} {loopback}";
    foreach ($nicZ as $yinter=>$line){
		if($_GET["eth"]<>null){
			if($yinter<>$_GET["eth"]){continue;}
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $html[]=table_fw_artica($_GET["eth"],$TRCLASS);
		}else {
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $html[]=table_fw_artica($_GET["eth"],$TRCLASS);
        }
    }

    if($_GET["eth"]<>null){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]=table_fw_policy($_GET["eth"],$TRCLASS);
    }
    if(preg_match("#([a-z]+[0-9]+)2([a-z]+[0-9])$#",$_GET["eth"],$re)){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]=table_fw_router($re[1],$re[2],$TRCLASS);
    }


    $TINY_ARRAY["TITLE"]="{administrate_your_firewall}";
    $TINY_ARRAY["ICO"]=ico_firewall;
    $TINY_ARRAY["EXPL"]="<strong>{firewall_rules}</strong>: <i>{firewall_about}</i>";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='10'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<script>";
    $html[]=$headsjs;
    $html[]="
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-webfilter-rules').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); }); 
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
return true;
}

function table_crowdsec_enabled():bool{
    $EnableCrowdSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdSec"));
    if($EnableCrowdSec==0){return false;}

    return true;
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}

function table_cybercrime($TRCLASS,$JSONSTATUS):array{
    $text_class = null;
    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";

    if(!property_exists($JSONSTATUS,"Family")){
        return array($TRCLASS,"");
    }
    $Family=$JSONSTATUS->Family;


    if(!property_exists($Family,"NFQUEUE_WHITELIST")){
        return array($TRCLASS,"");
    }

    $md=md5(__FUNCTION__);
    $tpl=new template_admin();
    $text_class="text-muted";
    $Outgoing   = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $rule_action="<span class='label label-primary'>ALLOW</span>";
    $tdfree     = $tpl->table_tdfree($text_class);
    $td1prc     = $tpl->table_td1prc($text_class);
    $STATUS     ="<span class='label label-default'>{inactive2}</span>";

    $EnableArticaNFQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue"));
    if($EnableArticaNFQueue==0){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $td1prc><span class='$text_class'>*/*</span></td>";
        $html[]="<td $tdfree><span class='$text_class'><strong class='text-muted'>{CybercrimeIPFeeds}</strong></td>";
        $html[]="<td $tdfree>$iconNet{all_interfaces}</td>";
        $html[]="<td $td1prc>$STATUS</td>";
        $html[]="<td $td1prc>-</td>";
        $html[]="<td $td1prc>-</td>";
        $html[]="<td $td1prc>$Outgoing$rule_action</td>";
        $html[]="<td $td1prc>".$tpl->icon_check(1)."</td>";
        $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
        $html[]="<td $td1prc>-</td>";
        $html[]="</tr>";
        return array($TRCLASS,@implode("\n",$html));
    }
    $text_class="";
    $STATUS     ="<span class='label label-primary'>{active2}</span>";

    $pks_white=0;
    $pks_black=0;
    $size_white=0;
    $size_black=0;
    $STATUS_WHITE     ="<span class='label label-default'>{inactive2}</span>";
    $STATUS_BLACK     ="<span class='label label-default'>{inactive2}</span>";

        if(property_exists($JSONSTATUS->Family,"NFQUEUE_WHITELIST")) {
            $STATUS_WHITE     ="<span class='label label-primary'>{active2}</span>";
            $pks_white=$JSONSTATUS->Family->NFQUEUE_WHITELIST->{"0"}->pkts;
            $size_white=$JSONSTATUS->Family->NFQUEUE_WHITELIST->{"0"}->bytes;
            $size_white=FormatBytes($size_white/1024);
        }
        if(property_exists($JSONSTATUS->Family,"NFQUEUE_BLACKLIST")) {
            $STATUS_BLACK     ="<span class='label label-primary'>{active2}</span>";
            $pks_black=$JSONSTATUS->Family->NFQUEUE_BLACKLIST->{"0"}->pkts;
            $size_black=$JSONSTATUS->Family->NFQUEUE_BLACKLIST->{"0"}->bytes;
            $size_black=FormatBytes($size_black/1024);
        }
    $pks_white=$tpl->FormatNumber($pks_white);
    $pks_black=$tpl->FormatNumber($pks_black);

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>*/*</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{CybercrimeIPFeeds} {legitimate_traffic}</strong></td>";
    $html[]="<td $tdfree>$iconNet{all_interfaces}</td>";
    $html[]="<td $td1prc>$STATUS_WHITE</td>";
    $html[]="<td style='text-align:right' nowrap>$pks_white</td>";
    $html[]="<td $td1prc>$size_white</td>";
    $html[]="<td $td1prc>$Outgoing$rule_action</td>";
    $html[]="<td $td1prc>".$tpl->icon_check(1)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";

    $rule_action="<span class='label label-danger'>DENY</span>";
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>*/*</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{CybercrimeIPFeeds} {legitimate_traffic2}</strong></td>";
    $html[]="<td $tdfree>$iconNet{all_interfaces}</td>";
    $html[]="<td $td1prc>$STATUS_BLACK</td>";
    $html[]="<td style='text-align:right' nowrap>$pks_black</td>";
    $html[]="<td $td1prc>$size_black</td>";
    $html[]="<td $td1prc>$Outgoing$rule_action</td>";
    $html[]="<td $td1prc>".$tpl->icon_check(1)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";
    return array($TRCLASS,@implode("\n",$html));

}

function table_all_in($TRCLASS,$JSON_STATUS):array{
    $text_class = null;
    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";

    if(!property_exists($JSON_STATUS,"Family")){
        return array($TRCLASS,"");
    }
    $Family=$JSON_STATUS->Family;


    if(!property_exists($Family,"INALL")){
        return array($TRCLASS,"");
    }

    $md=md5(__FUNCTION__);
    $tpl=new template_admin();

    $Outgoing   = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $rule_action="<span class='label label-primary'>ALLOW</span>";
    $tdfree     = $tpl->table_tdfree($text_class);
    $td1prc     = $tpl->table_td1prc($text_class);
    $STATUS     ="<span class='label label-primary'>{active2}</span>";



    if(!property_exists($Family->INALL,"0")){
        return array($TRCLASS,"");
    }

    $pks                = FormatNumber($Family->INALL->{0}->pkts);
    $size               = $Family->INALL->{0}->bytes;
    if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}


    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>*/*</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{default} {inbound}</strong></td>";
    $html[]="<td $tdfree>$iconNet{all_interfaces}</td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc>$Outgoing$rule_action</td>";
    $html[]="<td $td1prc>".$tpl->icon_check(1)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";

    $Outgoing   = "<span class='label'>OUT</span>&nbsp;";
    $pks                = FormatNumber($Family->OUTALL->{0}->pkts);
    $size               = $Family->OUTALL->{0}->bytes;
    if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>*/*</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{default} {outbound}</strong></td>";
    $html[]="<td $tdfree>$iconNet{all_interfaces}</td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc><span id='fw-ipfeeds-jact'>$Outgoing$rule_action</span></td>";
    $html[]="<td $td1prc>".$tpl->icon_check(1)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";


    return array($TRCLASS,@implode("\n",$html));

}

function table_proxy($TRCLASS,$JSONSTATUS,$tpl):array{
    $rule_action="<span class='label label-danger'>DENY</span>";
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $page=CurrentPageName();
    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){
        return array($TRCLASS,"",$tpl);
    }

    $STATUS_OUTGOING="<span class='label label-default'>{disabled}</span>";
    $pks_in=0;
    $pks_out=0;
    $size_out=0;
    $size_in=0;
    if(property_exists($JSONSTATUS,"Family")){
        if(property_exists($JSONSTATUS->Family,"PROXYRULES_IN")) {
            $STATUS_INCOMING     ="<span class='label label-primary'>{active2}</span>";
            $pks_in=$JSONSTATUS->Family->PROXYRULES_IN->{"0"}->pkts;
            $size_in=$JSONSTATUS->Family->PROXYRULES_IN->{"0"}->bytes;
            $size_in=FormatBytes($size_in/1024);
        }
        if(property_exists($JSONSTATUS->Family,"PROXYRULES_OUT")) {
            $STATUS_OUTGOING     ="<span class='label label-primary'>{active2}</span>";
            $pks_out=$JSONSTATUS->Family->PROXYRULES_OUT->{"0"}->pkts;
            $size_out=$JSONSTATUS->Family->PROXYRULES_OUT->{"0"}->bytes;
            $size_out=FormatBytes($size_out/1024);
        }

    }



    $SQUIDEnableFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnableFirewall"));

    $text_class="";
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $md         = md5(time());
    $tdfree     = $tpl->table_tdfree($text_class);
    $td1prc     = $tpl->table_td1prc($text_class);
    $Outgoing           = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";

    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";

    $APP_SQUID_OUTGOING=$tpl->td_href("{APP_SQUID}","","Loadjs('$page?squid-outgoing-js=yes&function=$function')");
    $APP_SQUID_INCOMING=$tpl->td_href("{APP_SQUID}","","Loadjs('$page?squid-incoming-js=yes&function=$function')");

    $InTCP=squid_incoming_data();
    $CountOfInTCP=count($InTCP);
    if($CountOfInTCP==0){
        $CountOfInTCP=3;
    }
    $explain_incoming=$tpl->_ENGINE_parse_body("{squid_fw_incoming_explain}");
    $explain_incoming=str_replace("%s","<strong>$CountOfInTCP</strong>",$explain_incoming);
    list($OutUDPArray,$OutTCPArray)=squid_outgoing_datas();
    $CountOfOut=count($OutUDPArray)+count($OutTCPArray);
    $explain_outgoing=$tpl->_ENGINE_parse_body("{squid_fw_outgoing_explain}");
    $explain_outgoing=str_replace("%s","<strong>$CountOfOut</strong>",$explain_outgoing);

    if($SQUIDEnableFirewall==0){
        $STATUS_INCOMING     ="<span class='label label-default'>{disabled}</span>";
        $STATUS_OUTGOING="<span class='label label-default'>{disabled}</span>";
        $rule_action="<span class='label label-default'>DENY</span>";
    }

    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>0/0</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>$APP_SQUID_INCOMING </strong> ({incoming2})<br><span>$explain_incoming</span></td>";
    $html[]="<td $tdfree><span class='$text_class'>$iconNet{all_interfaces}</span></td>";
    $html[]="<td $td1prc>$STATUS_INCOMING</td>";
    $html[]="<td style='text-align:right' nowrap>$pks_in</td>";
    $html[]="<td $td1prc>$size_in</td>";
    $html[]="<td $td1prc><span id='fw-ipfeeds-jact'>$Outgoing$rule_action</span></td>";
    $html[]="<td $td1prc>".$tpl->icon_check($SQUIDEnableFirewall,"Loadjs('$page?SQUIDEnableFirewall=yes&function=$function')")."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";


    $Outgoing           = "<span class='label'>OUT</span>&nbsp;";
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>0/0</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>$APP_SQUID_OUTGOING</strong> ({outgoing})<br>$explain_outgoing</td>";
    $html[]="<td $tdfree><span class='$text_class'>$iconNet{all_interfaces}</span></td>";
    $html[]="<td $td1prc>$STATUS_OUTGOING</td>";
    $html[]="<td style='text-align:right' nowrap>$pks_out</td>";
    $html[]="<td $td1prc>$size_out</td>";
    $html[]="<td $td1prc><span id='fw-ipfeeds-jact'>$Outgoing$rule_action</span></td>";
    $html[]="<td $td1prc>".$tpl->icon_check($SQUIDEnableFirewall,"Loadjs('$page?SQUIDEnableFirewall=yes&function=$function')")."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";
    return array($TRCLASS,@implode("\n",$html),$tpl);
}

function table_nginx($TRCLASS,$IPTABLES_RRULES_STATUS):array{
    $tpl=new template_admin();
    $EnableNginx = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==0){
        return array($TRCLASS,"");
    }
    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT `interface`,`port` FROM stream_ports");

    foreach ($results as $index=>$ligne){

        $interface=$ligne["interface"];
        $port=intval($ligne["port"]);
        $INT[$interface]=true;
        $ports[$port]=true;
    }
    $ifnames=array();
    foreach ($INT as $interface=>$pl){
        $ifnames[]=$interface;
    }
    $ifports=array();
    foreach ($ports as $port=>$pt){
        $ifports[]=$port;
    }
    if(count($ifports)==0){
        return array($TRCLASS,"");
    }
    $firewall_rulenginx=$tpl->_ENGINE_parse_body("{firewall_rulenginx}");
    $firewall_rulenginx=str_replace("%ifports","<strong>".@implode(",",$ifports)."</strong>",$firewall_rulenginx);
    $firewall_rulenginx=str_replace("%if","<strong>".@implode(",",$ifnames)."</strong>",$firewall_rulenginx);


    $text_class=null;
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $md         = md5($firewall_rulenginx);
    $tdfree     = $tpl->table_tdfree($text_class);
    $td1prc     = $tpl->table_td1prc($text_class);

    if(isset($IPTABLES_RRULES_STATUS["REVERSEPROXY"])){
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS["REVERSEPROXY"]["pkts"]));
        $size               = intval($IPTABLES_RRULES_STATUS["REVERSEPROXY"]["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }
    $Outgoing           = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $rule_action="<span class='label label-primary'>ALLOW</span>";

    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>0/0</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{APP_NGINX}</strong><br><span id='fw-ipfeeds-expl'>$firewall_rulenginx</span></td>";
    $html[]="<td $td1prc><span class='$text_class' id='fw-ipfeeds-nic'>-</span></td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc><span id='fw-ipfeeds-jact'>$Outgoing$rule_action</span></td>";
    $html[]="<td $td1prc>".$tpl->icon_check(1)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";
    return array($TRCLASS,@implode("\n",$html));
}

function table_crowdsec($TRCLASS,$IPTABLES_RRULES_STATUS):array{
    $text_class = null;
    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";
    $tpl=new template_admin();
    $STATUS             = "<span class='label'>{inactive}</span>";
    $tfile=PROGRESS_DIR."/crowdsec-blacklists.status";
    if(!table_crowdsec_enabled()){
        return array($TRCLASS,"");
    }
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("crowdsec.php?ipset-status=yes");
    $results=explode("\n",@file_get_contents($tfile));
    $entries=0;
   foreach ($results as $line){
        if(preg_match("#Number of entries:\s+([0-9]+)#",$line,$re)){
            $entries=$tpl->FormatNumber($re[1]);
        }

   }

    if(isset($IPTABLES_RRULES_STATUS["CROWDSEC"])){
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS["CROWDSEC"]["pkts"]));
        $size               = intval($IPTABLES_RRULES_STATUS["CROWDSEC"]["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }
    $Outgoing           = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $rule_action="<span class='label label-danger'>DENY</span>";


    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $md="none";
    $tdfree     = $tpl->table_tdfree($text_class);
    $td1prc     = $tpl->table_td1prc($text_class);

    $APP_CROWDSEC_EXPLAIN=$tpl->_ENGINE_parse_body("{APP_CROWDSEC_EXPLAIN}");
    $APP_CROWDSEC_EXPLAIN=substr($APP_CROWDSEC_EXPLAIN,0,120)."...";
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>0/0</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{APP_CROWDSEC}</strong> (<strong style='color:black'>$entries {records}</strong>)<br><span id='fw-ipfeeds-expl'>$APP_CROWDSEC_EXPLAIN</span></td>";
    $html[]="<td $td1prc><span class='$text_class' id='fw-ipfeeds-nic'>$iconNet{all_interfaces}</span></td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc><span id='fw-ipfeeds-jact'>$Outgoing$rule_action</span></td>";
    $html[]="<td $td1prc>".$tpl->icon_check(1)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";
    return array($TRCLASS,@implode("\n",$html));

}


function EXPLAIN_THIS_RULE($ligne,$disabled=null){
    $function=$_GET["function"];
    $tpl                    = new template_admin();
    $APP_XTABLES_INSTALLED  = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_XTABLES_INSTALLED");

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    if(is_numeric($ligne)){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID=$ligne");
    }


    $EnableLinkBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));
	$red_color="#d32d2d";
	$color="black";
	$log=null;
	if($ligne["enabled"]==0) {
        if ($ligne["accepttype"] <> "lINK") {
            $red_color = "#8a8a8a";
            $color = "#8a8a8a";
        }
    }

	if($disabled=="yes"){$red_color="#8a8a8a";$color="#8a8a8a";}
	$service_text="service2";
	$service=$ligne["service"];
	$MARK=intval($ligne["MARK"]);
	$eth=$ligne["eth"];
    $EnablenDPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablenDPI"));
	
	$ForwardTo=$ligne["ForwardTo"];
	$ForwardNIC=$ligne["ForwardNIC"];
	$xt_ratelimit=intval($ligne["xt_ratelimit"]);
	$xt_ratelimit_dir=trim($ligne["xt_ratelimit_dir"]);
	
	$services_containers=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["services_container"]);
	if($service<>null){$services_containers[$service]=true;}

    $tservices=array();
	foreach ($services_containers as $service=>$bb){
		if(trim($service)==null){continue;}
        if($service=="RustDesk"){
            $tservices[]="&laquo;<span style='font-weight:bold;color:$color;'>$service</span>&raquo;";
        }
		$ligneS=$q->mysqli_fetch_array("SELECT * FROM firehol_services_def WHERE service='$service'");
        if(!isset($ligneS["service"])){$ligneS["service"]=0;}
		if($ligneS["service"]==null){continue;}
		if($ligneS["enabled"]==0){continue;}
		$tservices[]="&laquo;<a href=\"javascript:blur();\" OnClick=\"Loadjs('fw.services.php?service-js=$service')\" style='font-weight:bold;color:$color;text-decoration:underline'>$service</a>&raquo;";
	}
	if(count($tservices)>1){$service_text="services";}
	if(count($tservices)>0){$service="<br>{{$service_text}} ".@implode(", ", $tservices);}

	if($ligne["jlog"]==1){
		$log=" {and} {log_all_events}";
	}

    if($EnablenDPI==1){
        $mDPI=mDPI($ligne["ID"]);
        if($mDPI<>null) {
            if($service<>null){$service="$service {and}";}
            $service = "$service<br>{application} &laquo;$mDPI&raquo;";
        }
	}

	if($ligne["accepttype"]=="ACCEPT"){
        $action="{then} {accept}$log $eth";
	    if(preg_match("#XNAT:([0-9]+)#",$eth,$re)){
	        $NAT_ID=$re[1];
            $ligne_nat=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$NAT_ID'");
            $NAT_TYPE_TEXT=$GLOBALS["NAT_TYPE"][$ligne_nat["NAT_TYPE"]];
            $rulename=$ligne["rulename"]." ";
            $action="{then} $rulename$NAT_TYPE_TEXT {$ligne_nat["dstaddr"]}";
        }
        if(preg_match("#DNAT:([0-9]+)#",$eth,$re)){
            $NAT_ID=$re[1];
            $ligne_nat=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$NAT_ID'");
            $NAT_TYPE_TEXT=$GLOBALS["NAT_TYPE"][$ligne_nat["NAT_TYPE"]];
            $rulename=$ligne["rulename"]." ";
            $action="{then} ".$tpl->td_href("$rulename$NAT_TYPE_TEXT","","Loadjs('fw.nat.php?ruleid-js=1&function=$function')")." {$ligne_nat["dstaddr"]}";
        }
    }
    if($ligne["accepttype"]=="TPROXY"){
        if($ligne["ForwardToPort"]==0){$ligne["ForwardToPort"]=8080;}
        $address="{$ligne["ForwardTo"]}:{$ligne["ForwardToPort"]}";
        if(!preg_match("#^[0-9\.]+:[1-9][0-9]+#",$address)){
            $action="<strong class='text-danger'>{Corrupted}</strong>";
        }else{
            $action="{then} {forward_to_proxy} &laquo;$address&raquo;$log";
        }
    }
	if($ligne["accepttype"]=="MARK"){
		
		$action="{then} {mark_packets_with} &laquo;$MARK&raquo;";
		if($ForwardNIC<>null){$action=$action." {using_the_interface} $ForwardNIC";}
		if($ForwardTo<>null){$action=$action." {and_forward_network_packets_to_the_gateway} $ForwardTo";}
		$action=$action.$log;
	}
    if($ligne["accepttype"]=="lINK") {
        $MARK_BALANCE=intval($ligne["MARK_BALANCE"]);
        if($MARK_BALANCE>0) {
            $ligne_mark = $q->mysqli_fetch_array("SELECT Interface FROM link_balance WHERE ID='$MARK_BALANCE'");
            $Interface = trim($ligne_mark["Interface"]);
            $nic = new system_nic($Interface);

            $action = "{then} {redirect_to_the_specified_link}  $Interface $nic->NICNAME";
            if ($ForwardNIC <> null) {
                $action = $action . " {using_the_interface} $ForwardNIC";
            }
        }else{
            $action ="<span class='text-danger'>{then} {redirect_to_the_specified_link} <i class=\"fas fa-engine-warning\"></i>&nbsp;{interface} {not_selected}!</span>";
        }

        if($EnableLinkBalancer==0){
            $action="<span class='text-danger'>$action <i class=\"fas fa-engine-warning\"></i>&nbsp;{APP_LINK_BALANCER} {feature}:{disabled}</span>";
        }

	}
	if($ligne["accepttype"]=="DROP"){
	    $action="{then} <strong style='color:$red_color'>{deny_access}</strong>$log";

        if(preg_match("#XNAT:([0-9]+)#",$eth,$re)){
            $NAT_ID=$re[1];
            $ligne_nat=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$NAT_ID'");
            if($ligne_nat["NAT_TYPE"]==3) {
                $action = "{then} <strong style='color:$red_color'>{continue}</strong>";
            }
        }


	    if($APP_XTABLES_INSTALLED==1){
            if($xt_ratelimit==1){

                $sttficshap["src"]="{from_x_net_elements}";
                $sttficshap["dst"]="{to_x_net_elements}";

                $elem=$tpl->_ENGINE_parse_body($sttficshap[$xt_ratelimit_dir]);
                $ligne_elements=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM traffic_shaping 
                WHERE ruleid={$ligne["ID"]} AND enabled=1");
                $selmnt=intval($ligne_elements["tcount"]);
                $elem=str_replace("%s",FormatNumber($selmnt),$elem);
                $elem=$tpl->td_href($elem,null,"Loadjs('fw.traffic.shaping.php?ruleid={$ligne["ID"]}')");
                $action="{and} $elem {then} <strong class='text-warning'>{limit_traffic}</strong>$log";

            }

        }

	}
    if($ligne["accepttype"]=="MIRROR"){
        $destIP="&nbsp;<strong style='color:$red_color'>{err_no_destination_addr}</strong>";
        if(strlen($ligne["ForwardTo"])>3){
            $destIP=" {to} {$ligne["ForwardTo"]}";
        }
        $action="{then} <strong style='color:black'>{duplicate_traffic}$destIP</strong>$log";
    }



	$inboud=EXPLAIN_LIST_OBJECTS($ligne["ID"],0,$color);
	if($inboud<>null){
		$inbound_text="{for_inbound_objects} $inboud {and} ";
	}else{
		$inbound_text="{for_all_nodes} {and} ";
	}
	
	if($ligne["isClient"]==1){
		$inbound_text="{from_the_firewall_itself} {and}";
	}

	$outbound=EXPLAIN_LIST_OBJECTS($ligne["ID"],1,$color);
	if($outbound<>null){
		$outbound_text="{to} $outbound {and} ";
	}else{
		$outbound_text="{to_everything} {and} ";
	}

	$ExplainThisTime=ExplainThisTime($ligne);
	if($ExplainThisTime<>null){$ExplainThisTime=" $ExplainThisTime";}

	$intro="<br><span style='color:$color'>$inbound_text $outbound_text$service $action $ExplainThisTime";

	$f[]=$intro;
	$tpl=new template_admin();
	$page=CurrentPageName();
    $FINAL=@implode("<br>", $f);
    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($FINAL);
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-{$ligne["ID"]}' data='$page?explain-this-rule={$ligne["ID"]}'>$FINAL</span>");


}
function mDPI($ruleid){

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
    $results=$q->QUERY_SQL("SELECT * FROM firehol_ndpi WHERE ruleid=$ruleid");
    if(count($results)==0){return null;}
    $MAIN=array();
    foreach ($results as $index=>$ligne){
        $ndpiname=trim($ligne["ndpiname"]);
        if($ndpiname==null){continue;}
        $MAIN[$ndpiname]=$ndpiname;
    }

    if(count($MAIN)==0){return null;}
    $TT=array();
    foreach ($MAIN as $ndpiname=>$nothing){
        $TT[]=$ndpiname;
    }
    if(count($TT)==0){return null;}
    return @implode(",",$TT);

}
function ExplainThisTime($ligne){
	if($ligne["enablet"]==0){return "{all_times}";}
	$f=array();
	$array_days=array(1=>"monday",2=>"tuesday",3=>"wednesday",4=>"thursday",5=>"friday",6=>"saturday",7=>"sunday");

	$TTIME=unserialize($ligne["time_restriction"]);

	$DDS=array();

	foreach ($array_days as $num=>$maks){
		if($TTIME["D{$num}"]==1){$DDS[]=$num;}
		$DAYS[]="{{$array_days[$num]}}";

	}

	if(count($DDS)>0){
		$f[]=@implode(", ", $DAYS);
	}

	if( (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ftime"])) AND  (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ttime"]))  ){
		$f[]="{from_time} {$TTIME["ftime"]} {to_time} {$TTIME["ttime"]}";
	}

	if(count($f)>0){
		return @implode("<br>", $f);
	}


}
function EXPLAIN_LIST_OBJECTS($ID,$dir,$color){
    $GPS=array();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne2 = $q->mysqli_fetch_array("SELECT MOD FROM iptables_main WHERE ID='$ID'");
    if(!$q->ok){echo $q->mysql_error_html(true);}
    $MOD=$ligne2["MOD"];

    if($dir==0) {

        $q=new postgres_sql();
        $FireholIPSetsWEntries=FormatNumber($q->COUNT_ROWS_LOW("ipset_whitelists"));
        $wljs=$tpl->td_href("{whitelists}",null,"Loadjs('fw.ipfeeds.white.php')");
        if ($MOD == "IPFEED") {
            $FireholIPSetsEntries = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireholIPSetsEntries"));
            $FireholIPSetsEntries = FormatNumber($FireholIPSetsEntries);
            $GPS[] = "<strong>{CybercrimeIPFeeds}</strong> ( $FireholIPSetsEntries {elements} )<br>{and} {notfor} <strong>$wljs</strong> ( $FireholIPSetsWEntries {elements} )";
        }

    }


	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$table="SELECT firewallfilter_sqacllinks.gpid,firewallfilter_sqacllinks.negation,
	firewallfilter_sqacllinks.zOrder,firewallfilter_sqacllinks.zmd5 as mkey,
	webfilters_sqgroups.GroupName,
	webfilters_sqgroups.ID as gpid,
	webfilters_sqgroups.GroupType FROM firewallfilter_sqacllinks,webfilters_sqgroups
	WHERE firewallfilter_sqacllinks.gpid=webfilters_sqgroups.ID
	AND firewallfilter_sqacllinks.aclid=$ID
	AND firewallfilter_sqacllinks.direction='$dir'
	AND webfilters_sqgroups.enabled='1'
	ORDER BY firewallfilter_sqacllinks.zOrder";


	$results=$q->QUERY_SQL($table);
	if(!$q->ok){return $q->mysql_error;}

    $GLOBALS["jsAfterEnc"]=base64_encode("Loadjs('$page?fill=$ID');");

	foreach ($results as $index=>$ligne){
        $text_is="{is}";
        $negation=intval($ligne["negation"]);
		$GroupName=utf8_encode($ligne["GroupName"]);
		$GroupType=$ligne["GroupType"];
		$js_group_final=null;
		$ID=$ligne["gpid"];
        if($negation==1){$text_is="{is_not}";}
        $js_group="Loadjs('fw.rules.items.php?groupid=$ID&js-after={$GLOBALS["jsAfterEnc"]}')";
		$js_group_final=$tpl->td_href($GroupName,null,$js_group);

		if($GroupType=="all"){$js_group_final=null;}

        if($GroupType=="localnet"){

            $qNet=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
            $ligneNet=$qNet->mysqli_fetch_array("SELECT count(*) as tcount FROM networks_infos WHERE enabled=1");
            $items=intval($ligneNet["tcount"]);
            $GPS[]="<strong style='color:$color'>{$text_is}&nbsp;$js_group_final ( $items {elements} )</strong>";
            continue;
        }


        if($GroupType=="fwgeo"){
            $results_countries=$q->QUERY_SQL("SELECT pattern FROM 
                    webfilters_sqitems WHERE gpid='$ID'");
            $C=0;
            $qP=new postgres_sql();

            foreach ($results_countries as $indexC=>$ligne_countries){

                $Country=$ligne_countries["pattern"];
                $ligne_sum=$qP->mysqli_fetch_array("SELECT items FROM ipdeny_countgeo 
                                                    WHERE country='$Country'");
                if(!$qP->ok){echo $qP->mysql_error_html(true);}
                $C=$C+intval($ligne_sum["items"]);

            }
            $items=FormatNumber($C);
            $GPS[]="<strong style='color:$color'>{$text_is}&nbsp;$js_group_final ( $items {elements} )</strong>";
            continue;
        }


		$ligne2=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='$ID'");
		$items=FormatNumber($ligne2["tcount"]);
		$GPS[]="<strong style='color:$color'>{$text_is}&nbsp;$js_group_final ( $items {elements} )</strong>";

	}
	if(count($GPS)==0){return null;}
	return @implode("<br> {or} ", $GPS);"<br>";
}

function LoadTrustedNetworks():array{
    $q          =new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results    = $q->QUERY_SQL("SELECT * FROM networks_infos WHERE trusted=1 AND enabled=1");
    $TrustedNetworks=array();
    if(count($results)==0){
        $TrustedNetworks[]="10.0.0.0/8";
        $TrustedNetworks[]="172.16.0.0/12";
        $TrustedNetworks[]="192.168.0.0/16";
    }else {
        foreach ($results as $index => $ligne) {
            $TrustedNetworks[] = $ligne["ipaddr"];
        }
    }

	return $TrustedNetworks;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

function SMTP_INTERFACES(){
	$PostfixBinInterfaces=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixBinInterfaces"));
	if($PostfixBinInterfaces<>null){
		$Interfaces=explode(",",$PostfixBinInterfaces);
		foreach ($Interfaces as $nic){
			$POSTFIX_INTERFACES[$nic]=true;
		}
		return $POSTFIX_INTERFACES;
	}

	


}

