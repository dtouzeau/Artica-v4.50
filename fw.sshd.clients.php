<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_POST["ipaddr"])){Save();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["enable"])){EnableRule();exit;}
if(isset($_GET["delete"])){DeleteRule();exit;}
if(isset($_GET["js"])){js();exit;}
SearchBlock();

function SearchBlock():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px;'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}
function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ipaddr=$_GET["js"];
    $function=$_GET["function"];
    $ipaddrenc=urlencode($ipaddr);
    if(strlen($ipaddr)<3){
        $ipaddr="{new_rule}";
    }
    return $tpl->js_dialog1($ipaddr,"$page?popup=$ipaddrenc&function=$function",750);
}
function popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ipaddr=$_GET["popup"];
    $function=$_GET["function"];
    $btn="{apply}";

    if(strlen($ipaddr)<3){
        $form[]=$tpl->field_hidden("new","yes");
        $form[]=$tpl->field_text("ipaddr","{network2}","",true);
        $ligne["PasswordAuthentication"]=1;
        $ligne["PermitRootLogin"]=0;
        $btn="{add}";
    }else{
        $form[]=$tpl->field_hidden("ipaddr",$ipaddr);
        $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM sshd_client WHERE Ipaddr='$ipaddr'");
    }

    $form[]=$tpl->field_checkbox("PasswordAuthentication","{PasswordAuthentication}",$ligne["PasswordAuthentication"],false,"{PasswordAuthentication_text}");
    $form[]=$tpl->field_checkbox("PermitRootLogin","{PermitRootLogin}",$ligne["PermitRootLogin"],false,"{PermitRootLogin_text}");

    $html[]=$tpl->form_outside($ipaddr,$form,null,$btn,
        "$function();dialogInstance1.close();","AsDebianSystem",false);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function EnableRule(){
    $tpl=new template_admin();
    $ipaddr=$_GET["enable"];
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM sshd_client WHERE Ipaddr='$ipaddr'");
    if($ligne["enabled"]==1){
        $q->QUERY_SQL("UPDATE sshd_client SET enabled=0 WHERE Ipaddr='$ipaddr'");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
        return admin_tracks_post("Disable sshd client rule - $ipaddr");
    }
    $q->QUERY_SQL("UPDATE sshd_client SET enabled=1 WHERE Ipaddr='$ipaddr'");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks_post("Enable sshd client rule - $ipaddr");
}
function DeleteRule(){
    $tpl=new template_admin();
    $ipaddr=$_GET["delete"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $q->QUERY_SQL("DELETE FROM sshd_client WHERE Ipaddr='$ipaddr'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks_post("Delete sshd client rule - $ipaddr");
}
function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $page=CurrentPageName();
    $ipaddr=$_POST["ipaddr"];
    $Password=intval($_POST["PasswordAuthentication"]);
      $Root=intval($_POST["PermitRootLogin"]);
    if(isset($_POST["new"])){

        $IP=new IP();
        if(!$IP->IsACDIROrIsValid($ipaddr)){
            echo $tpl->post_error("Invalid IP address [$ipaddr] should be (1.2.3.4 or 1.2.3.0/24)");
            return false;
        }
        $sql="INSERT INTO sshd_client (Ipaddr,PasswordAuthentication,PermitRootLogin) 
        VALUES('$ipaddr',$Password,$Root);";


    }else{
        $sql="UPDATE sshd_client SET 
                        PasswordAuthentication=$Password,
                        PermitRootLogin=$Root
                        WHERE Ipaddr='$ipaddr';";
    }
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks_post("Add/edit sshd client rule - $ipaddr");

}


function search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $search=$_GET["search"];
    $t=time();
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $sql="SELECT * FROM sshd_client ORDER BY Ipaddr";
    if($search<>null){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $sql="SELECT * FROM sshd_client WHERE Ipaddr LIKE '$search' ORDER BY Ipaddr";

    }

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);return false;}
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" style='margin-top:10px' data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{password}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>Root</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{enabled}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS="";
    $ico_networks="<i class='".ico_networks."'></i>";

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $PasswordAuthenticationICO="<i class='fa-solid fa-circle-xmark'></i>";
        $PermitRootLoginICO="<i class='fa-solid fa-circle-xmark'></i>";
        $md=md5(serialize($ligne));
        $ipaddr=$ligne["Ipaddr"];
        $PermitRootLogin=intval($ligne["PermitRootLogin"]);
        $ipaddrEnc=urlencode($ipaddr);
        $ipaddrF=$tpl->td_href($ipaddr,"","Loadjs('$page?js=$ipaddrEnc&function=$function')");
        $PasswordAuthentication=$ligne["PasswordAuthentication"];
        if($PasswordAuthentication==1){
            $PasswordAuthenticationICO="<span class='fas fa-check'></span>";
        }
        if($PermitRootLogin==1){
            $PermitRootLoginICO="<span class='fas fa-check'></span>";
        }
        $enable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable=$ipaddrEnc')","AsSystemAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?delete=$ipaddrEnc&md=$md')","AsSystemAdministrator");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td>$ico_networks&nbsp;$ipaddrF</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$PasswordAuthenticationICO</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$PermitRootLoginICO</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$enable</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$delete</td>";
        $html[]="</tr>";


    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $DisableSSHConfig       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSSHConfig"));

    if($DisableSSHConfig==0) {
        $topbuttons[] = array("Loadjs('$page?js=&function=$function')", ico_add_user, "{new_rule}");

    }

    $OPENSSH_VER=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENSSH_VER");
    $TINY_ARRAY["TITLE"]="{APP_OPENSSH} v$OPENSSH_VER";
    $TINY_ARRAY["ICO"]=ico_terminal;
    $TINY_ARRAY["EXPL"]="{OPENSSH_EXPLAIN}";
    $TINY_ARRAY["URL"]="sshd";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { 	\"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": false },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

    return true;
}
function js_tiny_build(){
    $tpl=new template_admin();
    $DisableSSHConfig       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSSHConfig"));
    $page=CurrentPageName();
    $topbuttons=array();

    if($DisableSSHConfig==0) {
        $topbuttons[] = array("Loadjs('$page?public-key-js=yes')", ico_certificate, "{certificates}");
        $topbuttons[] = array("Loadjs('$page?limit-countries-js=yes')", "far fa-globe-europe", "{deny_countries}");
        $topbuttons[] = array("Loadjs('$page?AuthorizedKeys-js=yes')", "fas fa-key", "{AuthorizedKeys}");
        $topbuttons[] = array("Loadjs('$page?config-file-js=yes')", "fa fa-file-code", "{config_file}");
    }
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/conf/islock"));
    if(property_exists($data,"Locked")){
        if (!$data->Locked) {
            $topbuttons[] = array("Loadjs('$page?lock-file-js=1')", ico_unlock, "{lock}");
        } else {
            $topbuttons[] = array("Loadjs('$page?lock-file-js=0')", ico_lock, "{unlock}");
        }
    }



    $OPENSSH_VER=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENSSH_VER");
    $TINY_ARRAY["TITLE"]="{APP_OPENSSH} v$OPENSSH_VER";
    $TINY_ARRAY["ICO"]=ico_terminal;
    $TINY_ARRAY["EXPL"]="{OPENSSH_EXPLAIN}";
    $TINY_ARRAY["URL"]="sshd";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    header("content-type: application/x-javascript");
    echo $jstiny;
}