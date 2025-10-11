<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["remove"])){remove_js();exit;}
if(isset($_POST["remove"])){remove_perform();exit;}
if(isset($_GET["remove-simple-acls"])){remove_simple_acls_js();exit;}
if(isset($_POST["remove-simple-acls"])){remove_simple_acls_perform();exit;}


if(isset($_GET["table"])){table();exit;}

startx();

function privs(){
    $users=new usersMenus();
    if($users->AllowAddGroup OR $users->AllowAddUsers) {return true;}
    return false;
}
function remove_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["remove"]);
    if($ID==0){
        echo $tpl->js_error("ID 0 is unsupported!");
        return false;
    }
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/privileges.db");
    $ligne=$q->mysqli_fetch_array("SELECT DN FROM adgroupsprivs WHERE ID=$ID");
    $DN=$ligne["DN"];
    $tpl->js_confirm_delete("{privileges} &laquo;$DN&raquo; ($ID)","remove",$ID,"$('#$md').remove();");
    return true;
}
function remove_simple_acls_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["remove-simple-acls"]);
    $md=trim($_GET["md"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT dngroup FROM webfilters_simpleacls_privs WHERE ID=$ID");
    $DN=$ligne["DN"];
    if(!$q->ok){
        $tpl->js_error($q->mysql_error);
        return false;
    }
    $js="$('#$md').remove();";
    $tpl->js_confirm_delete("{privileges} &laquo;$DN&raquo; ($ID)","remove-simple-acls",$ID,$js);
    return true;
}
function remove():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["remove"]);
    $md=trim($_GET["md"]);
    $q=new lib_sqlite("/home/artica/SQLITE/privileges.db");
    $ligne=$q->mysqli_fetch_array("SELECT DN FROM adgroupsprivs WHERE ID=$ID");
    $DN=$ligne["DN"];
    if(!$q->ok){
        $tpl->js_error($q->mysql_error);
        return false;
    }
    $js="$('#$md').remove();";
    $tpl->js_confirm_delete("{privileges} &laquo;$DN&raquo; ($ID)","remove",$ID,$js);
    return true;
}

function remove_perform():bool{
    $ID=intval($_POST["remove"]);
    $q=new lib_sqlite("/home/artica/SQLITE/privileges.db");
    $ligne=$q->mysqli_fetch_array("SELECT DN FROM adgroupsprivs WHERE ID=$ID");
    $DN=$ligne["DN"];
    $q->QUERY_SQL("DELETE FROM adgroupsprivs WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks("Remove privileges for $DN");
}
function remove_simple_acls_perform():bool{
    $ID=intval($_POST["remove-simple-acls"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT dngroup FROM webfilters_simpleacls_privs WHERE ID=$ID");
    $DN=$ligne["dngroup"];
    if(!$q->ok){echo $q->mysql_error;return false;}
    $q->QUERY_SQL("DELETE FROM webfilters_simpleacls_privs WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks("Remove Proxy simple acls privileges for $DN");
}

function startx(){
    $page=CurrentPageName();
echo "<div id='listOfPrivileges'></div>
<script>
function listOfPrivileges(){ 
    LoadAjax('listOfPrivileges','$page?table=yes'); 
}
listOfPrivileges();
</script>";

}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    if(!privs()){
        echo $tpl->div_error("{ERROR_NO_PRIVS}||{no_privileges}");
        return false;
    }


    $q=new lib_sqlite("/home/artica/SQLITE/privileges.db");
    $sql="SELECT * FROM adgroupsprivs ORDER BY `DN`";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error("{sql_error}||$q->mysql_error");

    }
    $c=0;
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' width=1%>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{DN}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{privileges}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;

    //<i class="fad fa-users-crown"></i>

    $privs=new privileges();
    foreach ($results as $index=>$ligne){
        $DN=$ligne["DN"];
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));
        $content=base64_decode($ligne["content"]);
        $privs->ParsePrivileges($content);
        $ttz=array();
        foreach ($privs->privs as $Name=>$yes){
            if($yes<>"yes"){continue;}
            $ttz[]="{{$Name}}";
        }

        $delete=$tpl->icon_delete("Loadjs('$page?remove=$ID&md=$md')","AsSystemAdministrator");
        $DNEnc=urlencode($DN);
        $js=$tpl->td_href($DN,null,"Loadjs('fw.groups.ad.php?dn=$DNEnc&function=listOfPrivileges')");

        $privs_text=@implode(", ",$ttz);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"center\"><div><i class='fad fa-users-crown'></i></div></td>";
        $html[]="<td class=\"\">$js</td>";
        $html[]="<td class=\"\" style='width:50%'>$privs_text</td>";
        $html[]="<td class=\"center\" style='width:1%'>$delete</td>";
        $html[]="</tr>";
        $c++;
    }


    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql = "SELECT ID,aclname FROM webfilters_simpleacls";
    $webfilters_simpleacls=array();
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $webfilters_simpleacls[$ligne["ID"]]=$ligne["aclname"];
    }


    $q->QUERY_SQL("DELETE FROM webfilters_simpleacls_privs WHERE dngroup=''");
    $results=$q->QUERY_SQL("SELECT ID,aclid,dngroup FROM webfilters_simpleacls_privs");
    foreach ($results as $index=>$ligne){
        $aclid=$ligne["aclid"];
        $DN=$ligne["dngroup"];
        VERBOSE("$DN --> $aclid",__LINE__);
        $ID=$ligne["ID"];
        if(!isset($webfilters_simpleacls[$aclid])){
            VERBOSE("$webfilters_simpleacls[$aclid] not found in acls.db",__LINE__);
            continue;
        }
        $md=md5(serialize($ligne));
        $privs_text=$webfilters_simpleacls[$aclid];
        $delete=$tpl->icon_delete("Loadjs('$page?remove-simple-acls=$ID&md=$md')","AsSystemAdministrator");
        $DNEnc=urlencode($DN);
        $js=$tpl->td_href($DN,null,"Loadjs('fw.groups.ad.php?dn=$DNEnc&function=listOfPrivileges')");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"center\"><div><i class='fad fa-users-crown'></i></div></td>";
        $html[]="<td class=\"\">$js</td>";
        $html[]="<td class=\"\" style='width:50%'>{access_rules}: $privs_text</td>";
        $html[]="<td class=\"center\" style='width:1%'>$delete</td>";
        $html[]="</tr>";
        $c++;
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


    $TINY_ARRAY["TITLE"]="{privileges}";
    $TINY_ARRAY["ICO"]="fad fa-users-crown";
    $TINY_ARRAY["EXPL"]="{privileges_list_explain}";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    if($c==0){
        echo $tpl->div_warning("{privileges}||{noprivslist}");
        echo "<script>$jstiny</script>";
        return false;
    }


    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
$jstiny
";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}


