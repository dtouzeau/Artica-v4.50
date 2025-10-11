<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_UNIX"]=new unix();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["nic"])){interface_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["nic-deleteGhost-js"])){delete_ghost();exit;}
if(isset($_GET["nic-delete-js"])){delete();exit;}

if(isset($_POST["nic-delete-phys"])){delete_ghost_perform();exit;}
if(isset($_POST["nic-delete"])){delete_perform();exit;}
if(isset($_GET["nic-ipv6-js"])){interface_js();exit;}
if(isset($_GET["nic-ipv6-popup"])){interface_popup();exit;}

table_start();

function delete_ghost():bool{
    $tpl=new template_admin();
    $ipaddr=$_GET["nic-deleteGhost-js"];
    $eth=$_GET["eth"];
    $md=$_GET["md"];
    return $tpl->js_confirm_delete("$ipaddr","nic-delete-phys","$eth;$ipaddr","$('#$md').remove();");
}
function delete():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["nic-delete-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nics_ipv6 WHERE ID=$ID");
    $ipaddr=$ligne["ipaddr"];
    $md=$_GET["md"];
    return $tpl->js_confirm_delete("$ipaddr","nic-delete","$ID","$('#$md').remove();");
}
function delete_perform():bool{
    $ID=$_POST["nic-delete"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nics_ipv6 WHERE ID=$ID");
    $eth=$ligne["nic"];
    if(strlen($eth)<3){
        echo "Interface corrupted\n";
    }
    $ipaddr=$ligne["ipaddr"];
    $cdir=$ligne["cdir"];
    $q->QUERY_SQL("DELETE FROM nics_ipv6 WHERE ID=$ID");
    $sock=new sockets();
    $sock->REST_API("/system/network/remove/ip6/$eth/$ipaddr/$cdir");
    return admin_tracks("Remove ipv6 addr $ipaddr from $eth");
}
function interface_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["eth"];
    $nicid=intval($_GET["nic-ipv6-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nics_ipv6 WHERE ID=$nicid");
    $nicid_text=$ligne["ipaddr"];
    if($nicid==0){$nicid_text="{new_interface}";}
    $tpl->js_dialog3($nicid_text, "$page?nic-ipv6-popup=$nicid&eth=$eth");
}
function delete_ghost_perform():bool{
    $tb=explode(";",$_POST["nic-delete-phys"]);
    $eth=$tb[0];
    $ipaddr=$tb[1];
    $sock=new sockets();
    if(strlen($eth)<3){
        echo "Interface corrupted\n";
    }
    $data=$sock->REST_API("/system/network/remove/ip6/$eth/$ipaddr");
    $json=json_decode($data);
    if(!$json->Status){
        echo $json->Error;
    }
    return true;
}
function table_start(){
    $eth=$_GET["nic"];
    $page=CurrentPageName();
    echo "<div id='ipv6Table$eth'></div>";
    echo "<script>LoadAjax('ipv6Table$eth','$page?table=$eth');</script>";

}
function table():bool{
    $tpl=new template_admin();
    $eth=$_GET["table"];
    $page=CurrentPageName();

    $topbuttons[] = array("Loadjs('$page?nic-ipv6-js=0&eth=$eth');", ico_plus, "{new_interface}");
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    $html[]="</div>";

    $html[]="<table id='table-$eth' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";

    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT * FROM nics_ipv6 WHERE nic='$eth'");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{tcp_address}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{gateway}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{netmask}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'data-type='text'>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;


    foreach ($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $text_class="";
        $ipaddr=$ligne["ipaddr"];
        $cdir=$ligne["cdir"];

        $FF[$ipaddr]=$ligne["ID"];
        $gateway=$ligne["gateway"];
        $id = md5(serialize($ligne));
        $delete=$tpl->icon_delete("Loadjs('$page?nic-delete-js={$ligne["ID"]}&eth={$ligne["nic"]}&md=$id')","AsSystemAdministrator");
        $ipaddr=$tpl->td_href($ipaddr,null,"Loadjs('$page?nic-ipv6-js={$ligne["ID"]}&eth=$eth');");
        $html[]="<tr class='$TRCLASS' id='$id'>";
        $html[]="<td class=\"$text_class\">$ipaddr</td>";
        $html[]="<td class=\"$text_class\">$gateway</td>";
        $html[]="<td class=\"$text_class\">$cdir</a></td>";
        $html[]="<td class=\"$text_class\"><center>{$delete}</center></td>";
        $html[]="</tr>";

    }
    $sock=new sockets();
    $data=$sock->REST_API("/system/network/ip6/$eth");
    $json=json_decode($data);
    foreach ($json as $index=>$ipcl){
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $text_class="";
        $ipaddr=$ipcl->IpAddr;
        $cdir=$ipcl->NetMask;
        if(isset($FF[$ipaddr])){
            continue;
        }
        $ipaddrEnc=urlencode($ipaddr."/$cdir");
        $gateway=$ipcl->Gateway;
        $id = md5(serialize($ipcl));
        $delete=$tpl->icon_delete("Loadjs('$page?nic-deleteGhost-js=$ipaddrEnc&eth=$eth&md=$id')","AsSystemAdministrator");

        $html[]="<tr class='$TRCLASS' id='$id'>";
        $html[]="<td class=\"$text_class\">$ipaddr</td>";
        $html[]="<td class=\"$text_class\">$gateway</td>";
        $html[]="<td class=\"$text_class\">$cdir</a></td>";
        $html[]="<td class=\"$text_class\"><center>{$delete}</center></td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
		<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('#table-$eth').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
		</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}




function interface_popup(){
    $tpl=new template_admin();
    $eth=$_GET["nic"];
    $nic=new system_nic($eth,true);
    $security="AsSystemAdministrator";
    $sock=new sockets();
    $nicid=intval($_GET["nic-ipv6-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $eth=$_GET["eth"];
    $page=CurrentPageName();
    $btn="{add}";
    $gateway="";
    $ipaddr="";
    $cdir=64;
    if($nicid>0){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM nics_ipv6 WHERE ID=$nicid");
        $btn="{apply}";
        $eth=$ligne["eth"];
        $ipaddr=$ligne["ipaddr"];
        $cdir=$ligne["cdir"];
        $gateway=$ligne["gateway"];
    }



    if($ipaddr==null){
        $ipaddr=generateRandomIPv6LastSegment("fd1b:e5b2:5a0f:ea4e:fab1:56ff:fea1:");
        $cdir=64;
    }



    if($gateway==null){$gateway="fd1b:e5b2:5a0f:ea4e:fab1:56ff:fea1:47a1";}

    $js="dialogInstance3.close();LoadAjax('ipv6Table$eth','$page?table=$eth')";

    $form[]=$tpl->field_hidden("ID",$nicid);
    $form[]=$tpl->field_hidden("nic",$eth);
    $form[]=$tpl->field_text("ipaddr","IPv6 {tcp_address}",$ipaddr);
    $form[]=$tpl->field_numeric("cdir","IPv6 {netmask}",$cdir);
    $form[]=$tpl->field_text("gateway","IPv6 {gateway}",$gateway);
    $html[]=$tpl->form_outside(null, $form,null,$btn,$js,$security);
    echo $tpl->_ENGINE_parse_body($html);
}
function convertIPv4toIPv6($ipv4) {
    $ipv6 = '::ffff:' . $ipv4;
    return inet_ntop(inet_pton($ipv6));
}
function generateRandomIPv6LastSegment($baseIPv6) {
    $randomSegment = dechex(mt_rand(0, 65535));
    $randomSegment = str_pad($randomSegment, 4, '0', STR_PAD_LEFT); // Ensure it's 4 digits
    return $baseIPv6 . $randomSegment;
}
function refreshjs($eth=null){
    $page=CurrentPageName();

    $MAINZ["netz-interfaces-status"]="$page?status2=yes";
    $MAINZ["network-interfaces-table"]="$page?table=yes";
    if($eth<>null){$MAINZ["div-works-$eth"]="$page?nic-config2=$eth";}
    foreach ($MAINZ as $div=>$js) {
        $jsa[] = "if( document.getElementById('$div') ){";
        $jsa[] = "LoadAjax('$div','$js');";
        $jsa[] = "}";
    }

    return @implode("",$jsa);

}

function interface_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();


    $ip=new IP();
    if(!$ip->isIPv6($_POST["ipaddr"]."/".$_POST["cdir"])){
        echo $tpl->post_error("{$_POST["ipaddr"]}/{$_POST["cdir"]} Not valid");
        return false;
    }

    $ID=intval($_POST["ID"]);

    if(strpos($_POST["ipaddr"],"/")>0){
        $tb=explode("/",$_POST["ipaddr"]);
        $_POST["ipaddr"]=$tb[0];
        $_POST["cdir"]=$tb[1];
    }
    if(strpos($_POST["gateway"],"/")>0){
        $tb=explode("/",$_POST["gateway"]);
        $_POST["gateway"]=$tb[0];
    }

    $fAdd[]="zmd5";
    $fAdd[]="nic";
    $fAdd[]="ipaddr";
    $fAdd[]="cdir";
    $fAdd[]="gateway";
    $fAdd[]="zone";
    $fAdd[]="metric";


    $vAdd[]=sprintf("'%s'",md5("{$_POST["ipaddr"]}:{$_POST["nic"]}"));
    $vAdd[]=sprintf("'%s'",$_POST["nic"]);
    $vAdd[]=sprintf("'%s'",$_POST["ipaddr"]);
    $vAdd[]=sprintf("'%s'",$_POST["cdir"]);
    $vAdd[]=sprintf("'%s'",$_POST["gateway"]);
    $vAdd[]=sprintf("'%s'",0);
    $vAdd[]=sprintf("'%s'",0);

    $Fed[]=sprintf("%s='%s'","ipaddr",$_POST["ipaddr"]);
    $Fed[]=sprintf("%s='%s'","cdir",$_POST["cdir"]);
    $Fed[]=sprintf("%s='%s'","gateway",$_POST["gateway"]);


    if($ID==0){
        $sql=sprintf("INSERT INTO nics_ipv6 (%s) VALUES (%s)",@implode(",",$fAdd),@implode(",",$vAdd));
    }else{
        $sql=sprintf("UPDATE nics_ipv6 SET %s WHERE ID=%s",@implode(",",$Fed),$ID);
    }

    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");

    return admin_tracks_post("Saving Ipv6 address");
}