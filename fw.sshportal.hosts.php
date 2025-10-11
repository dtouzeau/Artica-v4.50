<?php
$GLOBALS["ICON_FAMILY"]="PARAMETERS";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');

include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once('ressources/class.samba.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$usersmenus=new usersMenus();

if($usersmenus->AsArticaAdministrator==false){header('location:users.index.php');exit;}

if(isset($_GET["host-link"])){host_link_js();exit;}
if(isset($_GET["host-link-table"])){host_link_table();exit;}
if(isset($_GET["host-link-perform"])){host_link_perform();exit;}
if(isset($_GET["host-unlink"])){host_unlink();exit;}

if(isset($_GET["server-groups-start"])){server_groups_start();exit;}
if(isset($_GET["server-groups-table"])){server_groups_table();exit;}
if(isset($_GET["group-unlink"])){server_groups_unlink();exit;}
if(isset($_GET["server-groups-link"])){server_groups_link();exit;}
if(isset($_GET["server-groups-link-table"])){server_groups_link_table();exit;}
if(isset($_GET["server-groups-link-perform"])){server_groups_link_perform();exit;}


if(isset($_GET["start"])){table_start();exit;}
if(isset($_GET["server-js"])){server_js();exit;}
if(isset($_GET["server-tab"])){server_tabs();exit;}
if(isset($_GET["add-popup"])){add_popup();exit;}
if(isset($_GET["server-popup"])){server_popup();exit;}
if(isset($_POST["id"])){server_save();exit;}
if(isset($_GET["server-delete"])){server_delete();exit;}
if(isset($_POST["server-delete"])){server_delete_real();exit;}
if(isset($_GET["fill"])){server_fill();exit;}
table();


function host_link_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["host-link"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne      = $q->mysqli_fetch_array("SELECT name FROM user_groups WHERE ID=$ID");
    $title      = $ligne["name"];
    $tpl->js_dialog3($title." {link} {hosts}","$page?host-link-table=$ID",500);
}
function server_groups_link(){
    //$page?server-groups-link=$hostid&groupid=$groupid
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $hostid = intval($_GET["server-groups-link"]);
    $groupid= intval($_GET["groupid"]);
    $q      = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne  = $q->mysqli_fetch_array("SELECT name FROM hosts WHERE ID=$hostid");
    $title  = $ligne["name"];

    $tpl->js_dialog3($title." {link_group}","$page?server-groups-link-table=$hostid&groupid=$groupid",500);
}

function server_groups_link_perform(){
    $page       = CurrentPageName();
    $hostid     = $_GET["hostid"];
    $md         = $_GET["md"];
    $gpid       = $_GET["server-groups-link-perform"];
    $groupid    = $_GET["groupid"];
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $q->QUERY_SQL("INSERT OR IGNORE INTO host_host_groups (host_id,host_group_id) VALUES ($hostid,$gpid)");
    if(!$q->ok){echo $q->mysql_error;return;}

    $updated_at=date("Y-m-d H:i:s");
    $q->QUERY_SQL("UPDATE hosts SET updated_at='$updated_at' WHERE id='$hostid'");

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "LoadAjax('sshportal-host-$hostid','$page?server-groups-table=$hostid&groupid=$groupid');";
    echo "Loadjs('fw.sshportal.hostsgroups.php?fill=$gpid');\n";
}
function host_link_perform(){
    $page       = CurrentPageName();
    $userid     = $_GET["host-link-perform"];
    $md         = $_GET["md"];
    $gpid       = $_GET["gpid"];
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $q->QUERY_SQL("INSERT INTO host_host_groups (host_id,host_group_id) VALUES ($userid,$gpid)");
    if(!$q->ok){echo $q->mysql_error;return;}

    $updated_at=date("Y-m-d H:i:s");
    $q->QUERY_SQL("UPDATE hosts SET updated_at='$updated_at' WHERE id='$userid'");

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "LoadAjax('sshportal-hosts-table','$page?table=yes&groupid=$gpid');";
    echo "Loadjs('fw.sshportal.hostsgroups.php?fill=$gpid');\n";
}

function host_unlink(){
    $userid     = intval($_GET["host-unlink"]);
    $md         = $_GET["md"];
    $gpid       = intval($_GET["gpid"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $q->QUERY_SQL("DELETE FROM host_host_groups WHERE host_id=$userid AND host_group_id=$gpid");
    if(!$q->ok){echo $q->mysql_error;return;}
    $updated_at=date("Y-m-d H:i:s");
    $q->QUERY_SQL("UPDATE hosts SET  updated_at='$updated_at' WHERE id='$userid'");

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "Loadjs('fw.sshportal.hostsgroups.php?fill=$gpid');\n";

}
function server_groups_unlink(){
    $groupid    = intval($_GET["group-unlink"]);
    $md         = $_GET["md"];
    $hostid     = intval($_GET["hostid"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $q->QUERY_SQL("DELETE FROM host_host_groups WHERE host_id=$hostid AND host_group_id=$groupid");
    if(!$q->ok){echo $q->mysql_error;return;}
    $updated_at=date("Y-m-d H:i:s");
    $q->QUERY_SQL("UPDATE hosts SET  updated_at='$updated_at' WHERE id='$hostid'");

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "Loadjs('fw.sshportal.hostsgroups.php?fill=$groupid');\n";

}
function server_groups_link_table(){
    //          server-groups-link-table=$hostid&groupid=$groupid
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $t          = time();
    $hostid     = intval($_GET["server-groups-link-table"]);
    $groupid    = intval($_GET["groupid"]);

    $ALREADY=array();
    $q=new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $results=$q->QUERY_SQL("SELECT * FROM host_host_groups WHERE host_id='$hostid'");
    foreach ($results as $index=>$ligne){
        $userid=$ligne["host_group_id"];
        $ALREADY[$userid]=true;


    }

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groups2}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $results=$q->QUERY_SQL("SELECT * FROM host_groups order by name");
    if(!$q->ok){echo $q->mysql_error_html(true);}

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $id     = $ligne["id"];
        $md     = md5(serialize($ligne));
        $comment= $ligne["comment"];
        $name   = $ligne["name"];
        if(isset( $ALREADY[$id])){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap><i class='fa fa-users'></i>&nbsp;$name <small>$comment</small></td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->button_autnonome("{link}","Loadjs('$page?server-groups-link-perform=$id&md=$md&hostid=$hostid&groupid=$groupid')","fas fa-link","AllowAddUsers",0,"btn-primary","small") ."</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}


function host_link_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $gpid=intval($_GET["host-link-table"]);
    $ALREADY=array();
    $q=new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $results=$q->QUERY_SQL("SELECT * FROM host_host_groups WHERE host_group_id='$gpid'");
    foreach ($results as $index=>$ligne){
        $userid=$ligne["host_id"];
        $ALREADY[$userid]=true;


    }

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hosts}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $results=$q->QUERY_SQL("SELECT * FROM hosts order by name");

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $userid=$ligne["id"];
        if(isset( $ALREADY[$userid])){continue;}
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["id"];
        $username=$ligne["name"];
        $comment=$ligne["comment"];
//<i class="fas fa-link"></i>
        $gps=array();
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap><i class='fa fa-user'></i>&nbsp;$username <small>$comment</small></td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->button_autnonome("{link}","Loadjs('$page?host-link-perform=$ID&md=$md&gpid=$gpid')","fas fa-link","AllowAddUsers",0,"btn-primary","small") ."</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


function table_start(){
    if(!isset($_GET["groupid"])){$_GET["groupid"]=0;}
    $groupid=intval($_GET["groupid"]);
    $page   = CurrentPageName();
    $html   = "<div id='sshportal-hosts-table' style='margin-top: 10px'></div><script>LoadAjax('sshportal-hosts-table','$page?table=yes&groupid=$groupid');</script>";
    echo $html;
}
function server_groups_start(){
    if(!isset($_GET["groupid"])){$_GET["groupid"]=0;}
    $groupid=intval($_GET["groupid"]);
    $hostid=intval($_GET["server-groups-start"]);
    $page   = CurrentPageName();
    $html   = "<div id='sshportal-host-$hostid' style='margin-top: 10px'></div><script>LoadAjax('sshportal-host-$hostid','$page?server-groups-table=$hostid&groupid=$groupid');</script>";
    echo $html;
}

function server_js(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $id     = intval($_GET["server-js"]);
    $groupid= intval($_GET["groupid"]);
    $title  = "{new_target}";
    $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");


    if($id>0){
        $ligne  =   $q->mysqli_fetch_array("SELECT name FROM hosts WHERE id='$id'");
        $title  =   $ligne["name"];
        $tpl->js_dialog4($title,"$page?server-tab=$id&groupid=$groupid",850);
        return;
    }

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $sum = $q->COUNT_ROWS("hosts");
        if ($sum > 3) {
            $tpl->js_error("{license_limited}");
            return;
        }
    }

    $tpl->js_dialog4($title,"$page?server-popup=$id&groupid=$groupid",850);

}

function server_tabs(){
    $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $id     = intval($_GET["server-tab"]);
    $groupid= intval($_GET["groupid"]);
    $ligne  =   $q->mysqli_fetch_array("SELECT name FROM hosts WHERE id='$id'");
    $title  =   $ligne["name"];

    $array[$title]="$page?server-popup=$id&groupid=$groupid";
    $array["{groups}"]="$page?server-groups-start=$id&groupid=$groupid";
    echo $tpl->tabs_default($array);

}

function server_delete(){
    $tpl    = new template_admin();
    $id     = intval($_GET["server-delete"]);
    $md     = $_GET["md"];
    $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne  =   $q->mysqli_fetch_array("SELECT name FROM hosts WHERE id='$id'");
    $title  =   $ligne["name"];

    $tpl->js_confirm_delete($title,"server-delete",$id,"$('#$md').remove()");

}

function server_delete_real(){
    $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $q->QUERY_SQL("DELETE FROM hosts WHERE id='{$_POST["id"]}'");
    $q->QUERY_SQL("DELETE FROM host_host_groups WHERE host_id='{$_POST["id"]}'");
    $q->QUERY_SQL("DELETE FROM sessions WHERE host_id='{$_POST["id"]}'");
    if(!$q->ok){echo $q->mysql_error;}
}

function server_popup(){
    $id     = intval($_GET["server-popup"]);
    $groupid= intval($_GET["groupid"]);
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $q      = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $name   = null;
    $url    = null;
    $comment= null;
    $title  = "{new_target}";
    $btname = "{add}";
    $jsafter= "dialogInstance4.close();LoadAjax('sshportal-hosts-table','$page?table=yes');Loadjs('fw.sshportal.hostsgroups.php?fill=$groupid');";

    if($id>0) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM hosts WHERE id='$id'");
        $name       = $ligne["name"];
        $url        = $ligne["url"];
        $comment    = $ligne["comment"];
        $title      = $name;
        $btname     = "{apply}";
        $jsafter    = "Loadjs('$page?fill=$id');";
    }

    $MAIN=$tpl->sshurl_toarray($url);
    $hostname=$MAIN["HOST"];
    $port=$MAIN["PORT"];
    $username=$MAIN["USER"];
    $password=$MAIN["PASS"];

    $tpl->field_hidden("id",$id);

    if($id==0){

        if($groupid==0) {
            $results = $q->QUERY_SQL("SELECT * FROM host_groups ORDER BY name");
            foreach ($results as $index => $ligne) {
                $ID = $ligne["id"];
                $gname = $ligne["name"];
                $Groups[$ID] = $gname;
            }

            $form[] = $tpl->field_array_hash($Groups, "group", "{group2}");
        }else{
            $tpl->field_hidden("group",$groupid);
        }

    }

    if($name=="localhost"){
        $tpl->field_hidden("name","localhost");
    }else {
        $form[] = $tpl->field_text("name", "{connection_name}", $name, true);
    }
    $form[]=$tpl->field_text("comment","{comment}",$comment,false);
    if($name=="localhost"){
        $tpl->field_hidden("HOST","127.0.0.1");
        $tpl->field_hidden("PORT","884");
        $tpl->field_hidden("USER","root");
    }else {
        $form[] = $tpl->field_text("HOST", "{server_address}", $hostname, true);
        $form[] = $tpl->field_numeric("PORT", "{listen_port}", $port, true);
        $form[] = $tpl->field_text("USER", "{username}", $username);
    }
    $form[]=$tpl->field_password2("PASS","{password}",$password);
    echo $tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,"AsSystemAdministrator");
}

function server_save(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("sshd.php?sshportal-chown=yes");
    $tpl        = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $tpl->CLEAN_POST();
    $comment    = $q->sqlite_escape_string2($_POST["comment"]);
    $name       = $_POST["name"];
    $id         = intval($_POST["id"]);
    $groupid    = intval($_POST["group"]);
    $hostname   = $_POST["HOST"];
    $port       = $_POST["PORT"];
    $username   = $_POST["USER"];
    $password   = $_POST["PASS"];
    $name       = replace_accents($name);
    $name       =str_replace(" ","_",$name);
    $name       =str_replace(".","_",$name);
    $name       =str_replace("/","_",$name);
    $name       =str_replace("\\","_",$name);
    $name       =str_replace("@","_",$name);
    $name       =str_replace(":","_",$name);
    $name       =str_replace("$","",$name);
    $name       =str_replace(",","",$name);
    $name       =str_replace(";","",$name);
    $name       =str_replace('"',"",$name);
    $name       =str_replace("'","",$name);
    $name       =str_replace("(","",$name);
    $name       =str_replace(")","",$name);
    $name       =str_replace("[","",$name);
    $name       =str_replace("]","",$name);
    $name       =str_replace("%","",$name);
    $name       =str_replace("^","",$name);
    $name       =str_replace("&","e",$name);
    $name       =str_replace("{","",$name);
    $name       =str_replace("}","",$name);
    $name       =str_replace("!","",$name);
    $name       =str_replace("*","",$name);
    $updated_at = date("Y-m-d H:i:s");


    $uris[]="ssh://";
    if($username<>null){
         $uris[]="$username:$password@";
    }
    $uris[]=$hostname;
    if($port<>22){
       $uris[]=":$port";
    }

    $url=@implode("",$uris);
    if($id==0){
        $q->QUERY_SQL("INSERT INTO hosts (comment,name,url,created_at,updated_at) VALUES ('$comment','$name','$url','$updated_at','$updated_at')");
        if(!$q->ok){echo $q->mysql_error;}

        if($groupid>0){
            $ligne=$q->mysqli_fetch_array("SELECT id FROM hosts WHERE url='$url'");
            $hostid=intval($ligne["id"]);
            $q->QUERY_SQL("INSERT INTO host_host_groups (host_id,host_group_id) VALUES ($hostid,$groupid)");
        }

        return true;
    }

    $q->QUERY_SQL("UPDATE hosts SET `comment`='$comment',`name`='$name',url='$url',updated_at='$updated_at' WHERE id='$id'");
    if(!$q->ok){echo $q->mysql_error;}

}
function server_fill(){
    $tpl        = new template_admin();
    $id         = $_GET["fill"];
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM hosts WHERE id='$id'");
    $name       = $ligne["name"];
    $url        = $ligne["url"];
    $comment    = $ligne["comment"];
    $hostname   = null;
    $username   = null;

    $infos=$tpl->sshurl_toarray($url);
    $hostname=$infos["HOST"];
    $username=$infos["USER"];
    $comment=str_replace("'","\'",$comment);

    $sshdportalPort     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalPort"));
    $sshdportalInterface= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalInterface"));
    if($sshdportalInterface==null){
        $sshdportalIP=$_SERVER["SERVER_ADDR"];
    }else{
        $nic=new system_nic($sshdportalInterface);
        $sshdportalIP=$nic->IPADDR;
    }

    $cmdline="&quot;C:\Program Files\PuTTY\putty.exe&quot; -ssh -2 -4 -i &quot;C:\MyKey.ppk&quot; $name@$sshdportalIP $sshdportalPort";

    header("content-type: application/x-javascript");
    $f[]="document.getElementById('$id-name').innerHTML='$name';";
    $f[]="document.getElementById('$id-username').innerHTML='$username';";
    $f[]="document.getElementById('$id-hostname').innerHTML='$hostname';";
    $f[]="document.getElementById('$id-cmdline').innerHTML='$cmdline';";
    $f[]="document.getElementById('$id-comment').innerHTML='$comment';";
    echo @implode("\n",$f);

}

function server_groups_table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();
    $groupid    = intval($_GET["groupid"]);
    $hostid     = intval($_GET["server-groups-table"]);
    $row_delete = "{unlink}";
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $TRCLASS    = null;

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px;'>";
    $html[]=$tpl->button_label_table("{link_group}", "Loadjs('$page?server-groups-link=$hostid&groupid=$groupid')", "fas fa-server","AllowAddUsers");
    $html[]="</div>";
    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{comment}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>$row_delete</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $results=$q->QUERY_SQL("SELECT * FROM host_host_groups WHERE host_id=$hostid");
    foreach ($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $md            = md5(serialize($ligne));
        $host_group_id = $ligne["host_group_id"];
        $ligne2        = $q->mysqli_fetch_array("SELECT name,comment FROM host_groups WHERE id=$host_group_id");
        $name          = $ligne2["name"];
        $comment       = $ligne2["comment"];
        $delete        = $tpl->icon_unlink("Loadjs('$page?group-unlink=$host_group_id&md=$md&hostid=$hostid')","AllowAddUsers");

        if($hostid==1) {
            if ($host_group_id == 1) {
                $delete = $tpl->icon_nothing();
            }
        }



        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td class=\"\" width='1%' nowrap><i class='fas fa-server'></i>&nbsp;<strong>$name</strong></td>";
        $html[] = "<td class=\"\">$comment</td>";
        $html[] = "<td class=\"center\" width=1% nowrap>$delete</td>";
        $html[] = "</tr>";

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
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function table(){
    $tpl        = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $t          = time();
    $groupid    = intval($_GET["groupid"]);
    $row_delete = "{delete}";
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");


    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px;'>";
    $html[]=$tpl->button_label_table("{new_target}", "Loadjs('$page?server-js=0&groupid=$groupid')", "fas fa-server","AllowAddUsers");
    if($groupid>0){
        $html[]=$tpl->button_label_table("{link_host}", "Loadjs('$page?host-link=$groupid')", "fas fa-link","AllowAddUsers");
        $row_delete="{unlink}";

    }
    $html[]="</div>";

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{comment}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{address}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' nowrap>{username}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>$row_delete</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $sql="SELECT * FROM hosts ORDER BY name";
    if($groupid>0){
        $sql="SELECT hosts.* FROM hosts,host_host_groups WHERE  host_host_groups.host_id=hosts.id AND host_group_id=$groupid ORDER BY name";

    }
    $results=$q->QUERY_SQL($sql);
    $sshdportalPort     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalPort"));
    $sshdportalInterface= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalInterface"));
    if($sshdportalPort==0){$sshdportalPort=2222;}
    if($sshdportalInterface==null){
        $sshdportalIP=$_SERVER["SERVER_ADDR"];
    }else{
        $nic=new system_nic($sshdportalInterface);
        $sshdportalIP=$nic->IPADDR;
    }

//	print_r($hash_full);
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID         = $ligne["id"];
        $md         = md5(serialize($ligne));
        $name       = $ligne["name"];
        $url        = $ligne["url"];
        $comment    = $ligne["comment"];
        $hostname   = null;
        $username   = null;
        $delete     = $tpl->icon_nothing();

        $infos=$tpl->sshurl_toarray($url);
        $hostname=$infos["HOST"];
        $username=$infos["USER"];

        if($ID>0) {
            $delete     = $tpl->icon_delete("Loadjs('$page?host-unlink=$ID&md=$md&gpid=$groupid')","AllowAddUsers");
        }

         if ($name == "localhost") {
            $delete = $tpl->icon_nothing();
         }


        $cmdline="&quot;C:\Program Files\PuTTY\putty.exe&quot; -ssh -2 -4 -i &quot;C:\MyKey.ppk&quot; $name@$sshdportalIP $sshdportalPort";

        $js="Loadjs('$page?server-js=$ID')";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='1%' nowrap><i class='fas fa-server'></i>&nbsp;".$tpl->td_href("<span id='$ID-name'>$name</span>","{click_to_edit}",$js)."</td>";
        $html[]="<td class=\"\"><span id='$ID-comment'>$comment</span></td>";
        $html[]="<td class=\"\"><span id='$ID-cmdline'>$cmdline</span></td>";
        $html[]="<td class=\"\" width='1%' nowrap>". $tpl->td_href("<span id='$ID-hostname'>$hostname</span>","{click_to_edit}",$js)."</td>";
        $html[]="<td class=\"\" width='1%' nowrap><i class='fa fa-user'></i>&nbsp;<span id='$ID-username'>$username</span></td>";
        $html[]="<td class=\"center\" width=1% nowrap>$delete</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));




}