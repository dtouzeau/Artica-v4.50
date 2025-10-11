<?php
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if(isset($_GET["pulse-js"])){pulse_js();exit;}
if(isset($_POST["newwebsite-save"])){newwebsite_save();exit;}
if(isset($_GET["newwebsite-js"])){newwebsite_js();exit;}
if(isset($_GET["newwebsite-popup"])){newwebsite_popup();exit;}
if(isset($_GET["domainGroups-search"])){domainGroups_search();exit;}
if(isset($_GET["domainGroups"])){domainGroups();exit;}
if(isset($_GET["domainGroups-tabs"])){domainGroups_tabs();exit;}
if(isset($_GET["domainGroups-popup"])){domainGroups_popup();exit;}

if(isset($_POST["websiteid"])){website_save();exit;}
if(isset($_GET["website-delete"])){website_delete();exit;}
if(isset($_POST["website-delete"])){website_delete_perform();exit;}
if(isset($_GET["website-enabled"])){website_enable();exit;}
if(isset($_GET["website-popup"])){website_popup();exit;}
if(isset($_GET["website-js"])){website_js();exit;}
if(isset($_GET["websites-popup"])){websites_popup();exit;}
if(isset($_GET["websites-search"])){websites_search();exit;}
if(isset($_GET["websites-buttons"])){websites_buttons();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["backend-js"])){backend_js();exit;}
if(isset($_GET["backend-tab"])){backend_tab();exit;}
if(isset($_GET["backend-popup"])){backend_popup();exit;}
if(isset($_GET["backend-delete"])){backend_delete();exit;}
if(isset($_POST["backend-delete"])){backend_delete_perform();exit;}
if(isset($_GET["connectors-popup"])){connectors_popup();exit;}
if(isset($_GET["frontend-enable"])){frontend_enable();exit;}


if(isset($_POST["ID"])){backend_save();exit;}
page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    $html=$tpl->page_header("PulseReverse {websites}",
        ico_server,
        "{APP_PULSE_REVERSE_WEBSITES}",
        "$page?table=yes",
        "pulsereverse-websites",
        "progress-pulsereverse-backends");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("PulseReverse v$version",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function backend_delete():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["backend-delete"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $ligne = $q->mysqli_fetch_array("SELECT backendname FROM backends WHERE ID=$ID");
    $backendname = $ligne["backendname"];
    return $tpl->js_confirm_delete($backendname,"backend-delete",$ID,"$('#$md').remove()");
}
function website_delete():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["website-delete"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $ligne = $q->mysqli_fetch_array("SELECT sitename FROM domains WHERE ID=$ID");
    $backendname = $ligne["sitename"];
    return $tpl->js_confirm_delete($backendname,"website-delete",$ID,"$('#$md').remove()");
}
function website_delete_perform():bool{
    $ID=intval($_POST["website-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $ligne = $q->mysqli_fetch_array("DELETE FROM domains WHERE ID=$ID");
    $backendname = $ligne["sitename"];
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/pulsereverse/reconfigure");
    return admin_tracks("Delete ReversePulse domain $backendname");
}

function backend_delete_perform():bool{
    $ID=intval($_POST["backend-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $ligne = $q->mysqli_fetch_array("SELECT backendname FROM backends WHERE ID=$ID");
    $backendname = $ligne["backendname"];
    $q->QUERY_SQL("DELETE FROM backends WHERE ID=$ID");
    if(!$q->ok){
        echo $q->mysql_error;
        return false;
    }
    $q->QUERY_SQL("DELETE FROM connectors_backends WHERE backendid=$ID");
    $q->QUERY_SQL("DELETE FROM domains WHERE backendid=$ID");

    return admin_tracks("Removed PulseReverse backend $backendname");
}
function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page);
    return true;
}
function website_js():bool{
    $page=CurrentPageName();
    $function   = $_GET["function"];
    $groupid=intval($_GET["groupid"]);
    $tpl=new template_admin();
    $sitename=$tpl->_ENGINE_parse_body("{new_website}");
    $ID=intval($_GET["website-js"]);
    if($ID>0){
        $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
        $ligne=$q->mysqli_fetch_array("SELECT sitename FROM domains WHERE ID=$ID");
        $sitename=$ligne["sitename"];
    }

    return $tpl->js_dialog4($sitename, "$page?website-popup=$ID&function=$function&groupid=$groupid",650);
}
function newwebsite_js():bool{
    $page=CurrentPageName();
    $function   = $_GET["function"];
    $tpl=new template_admin();
    $GroupName=$tpl->_ENGINE_parse_body("{new_website}");
    return $tpl->js_dialog3($GroupName, "$page?newwebsite-popup=yes&function=$function",550);
}

function newwebsite_popup():bool{
    $page=CurrentPageName();
    $function   = $_GET["function"];

    $tpl=new template_admin();
    $buttonname="{add}";
    $jsadd="dialogInstance3.close();";
    $jsrestart="$jsadd;$function()";
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");

    $tpl->field_hidden("newwebsite-save","yes");
    $form[]=$tpl->field_text("sitename", "{sitename}", "",true);
    $results=$q->QUERY_SQL("SELECT ID,GroupName FROM domgroups ORDER BY GroupName");
    $gprs=array();
    foreach ($results as $index=>$ligne) {
        $ID=$ligne["ID"];
        $GroupName=$ligne["GroupName"];
        $gprs[$ID]=$GroupName;
    }
    $form[]=$tpl->field_array_hash($gprs,"groupid","{groupname}");
    $html=$tpl->form_outside("", $form,null, $buttonname,$jsrestart,"AsSquidAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function newwebsite_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $groupid=intval($_POST["groupid"]);
    $sitename=trim($_POST["sitename"]);

    if($groupid==0){
        echo $tpl->post_error($tpl->_ENGINE_parse_body("{no_group_affected}"));;
        return false;
    }

    if(preg_match("#^(http|https|ftp|ftps):#i",$sitename)){
        $urls=parse_url($sitename);
        $sitename=$urls["host"];
    }


    $websiteid=0;

    $zmd5=md5("$sitename$websiteid$groupid".time());
    $q->QUERY_SQL("INSERT INTO domains (sitename,zmd5,rootdir,enabled) VALUES ('$sitename','$zmd5','',1)");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM domains WHERE zmd5='$zmd5'");
    $websiteid=intval($ligne["ID"]);
    if($websiteid==0){
        echo $tpl->post_error("Error inserting new website $sitename");
        return false;
    }

    $zmd5=md5("$websiteid$groupid");
    $ligneGrp=$q->mysqli_fetch_array("SELECT ID FROM linkgroups WHERE zmd5='$zmd5'");
    $LinkID=$ligneGrp["ID"];
    if($LinkID==0){
        $q->QUERY_SQL("INSERT INTO linkgroups (zmd5,groupid,domainid) VALUES ('$zmd5','$groupid','$websiteid')");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error."\nINSERT INTO linkgroups (zmd5,groupid,domainid) VALUES ('$zmd5','$groupid','$websiteid')");
            return false;
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/pulsereverse/reconfigure");
    return admin_tracks_post("Adding new PulseReverse domain $sitename");

}


function  website_popup():bool{
    $page=CurrentPageName();
    $function   = $_GET["function"];
    $groupid=intval($_GET["groupid"]);
    $tpl=new template_admin();
    $ID=intval($_GET["website-popup"]);
    $ligne=array();
    $ligne["enabled"]=1;
    $buttonname="{add}";
    $ligne["sitename"]="";

    $jsadd="";
    if ($ID>0) {
        $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM domains WHERE ID=$ID");
        $buttonname="{apply}";
    }

    if($ID>0) {
        $jsrestart = "$function()";
    }else{
        $jsadd="dialogInstance4.close();";
        $jsrestart="$jsadd;$function()";
    }

    $tpl->field_hidden("websiteid",$ID);
    $tpl->field_hidden("websiteid-groupid",$groupid);
    $form[]=$tpl->field_checkbox("enabled","{enabled}", $ligne["enabled"],true);
    $form[]=$tpl->field_text("sitename", "{sitename}", $ligne["sitename"],true);
    $form[]=$tpl->field_text("rootdir", "{TargetRemotePath}", $ligne["rootdir"]);
    $form[]=$tpl->field_certificate("certificate", "{certificate}", $ligne["certificate"]);

    $html=$tpl->form_outside("", $form,null, $buttonname,$jsrestart,"AsSquidAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function website_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $enabled=intval($_POST["enabled"]);
    $sitename=$_POST["sitename"];
    $rootdir=$_POST["rootdir"];
    $groupid=intval($_POST["websiteid-groupid"]);
    $websiteid=intval($_POST["websiteid"]);
    $certificate=$_POST["certificate"];

    if(preg_match("#^(http|https|ftp|ftps):#i",$sitename)){
        $urls=parse_url($sitename);
        $host=$urls["host"];
        $port="";
        if(isset($urls["port"])){$port=":{$urls["port"]}";}
        $sitename="$host$port";
    }
    if(preg_match("#^(.+?)/#i",$sitename,$matches)){
        $sitename=$matches[1];
    }
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $sql="UPDATE domains SET sitename='$sitename',enabled=$enabled,
                   rootdir='$rootdir', 
                   certificate='$certificate' WHERE ID=$websiteid";

    if($websiteid>0){
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }

        if($groupid>0){
            $zmd5=md5("$websiteid$groupid");
            $ligneGrp=$q->mysqli_fetch_array("SELECT ID FROM linkgroups WHERE zmd5='$zmd5'");
            $LinkID=$ligneGrp["ID"];
            if($LinkID==0){
                $q->QUERY_SQL("INSERT INTO linkgroups (zmd5,groupid,domainid) VALUES ('$zmd5','$groupid','$websiteid')");

                if(!$q->ok){
                    echo $tpl->post_error($q->mysql_error."\nINSERT INTO linkgroups (zmd5,groupid,domainid) VALUES ('$zmd5','$groupid','$websiteid')");
                    return false;
                }
            }
        }
        return admin_tracks_post("Edit PulseReverse domain $sitename");
    }

    $zmd5=md5("$sitename$websiteid$groupid$rootdir");
    $sql="INSERT INTO domains (sitename,zmd5,rootdir,enabled) VALUES ('$sitename','$zmd5','$rootdir',$enabled)";

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $ID=$q->last_id;

    $zmd5=md5("$ID$groupid");
    if($groupid>0){
        $q->QUERY_SQL("INSERT INTO linkgroups (zmd5,groupid,domainid) VALUES ('$zmd5','$groupid','$ID')");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
    }


    return admin_tracks_post("Added PulseReverse domain $sitename");

}

function backend_js():bool{
    $page=CurrentPageName();
    $function   = $_GET["function"];
    $tpl=new template_admin();
    $backendname=$tpl->_ENGINE_parse_body("{new_backend}");
    $ID=intval($_GET["backend-js"]);
    if($ID>0){
        $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
        $ligne=$q->mysqli_fetch_array("SELECT backendname FROM backends WHERE ID=$ID");
        $backendname=$ligne["backendname"];
    }

    return $tpl->js_dialog2($backendname, "$page?backend-tab=$ID&function=$function");
}
function backend_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function   = $_GET["function"];
    $ID=intval($_GET["backend-tab"]);
    $backendname=$tpl->_ENGINE_parse_body("{new_backend}");
    $q=new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    if($ID>0) {
        $ligne = $q->mysqli_fetch_array("SELECT backendname FROM backends WHERE ID=$ID");
        $backendname = $ligne["backendname"];
    }

    $array[$backendname]="$page?backend-popup=$ID&function=$function";
    $array["{connectors}"]="$page?connectors-popup=$ID&function=$function";
    $array["{domainGroups}"]="$page?domainGroups=$ID&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}
function connectors_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function   = $_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $backendid=intval($_GET["connectors-popup"]);
    $results=$q->QUERY_SQL("SELECT connectorid,zmd5 FROM connectors_backends WHERE backendid=$backendid");
    $CONNECTORS=array();
    foreach($results as $index=>$ligne){
        $CONNECTORS[$ligne["connectorid"]]=$ligne["zmd5"];
    }

    $results=$q->QUERY_SQL("SELECT ID,servicename,port FROM connectors");

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{connector}</th>
        	<th nowrap>{active2}</th>
        </tr>
  	</thead>
	<tbody>
";
    $icoSmoke="<i class='fas fa-smoke'></i>&nbsp;";
    foreach($results as $index=>$ligne){
        $servicename=$ligne["servicename"];
        $port=$ligne["port"];
        $ID=$ligne["ID"];
        $enabled=0;
        $width1="style='width:1%' nowrap";
        $width99="style='width:99%'";
        if(isset($CONNECTORS[$ID])){
            $enabled=1;
        }
        $icoEnabled=$tpl->icon_check($enabled,"Loadjs('$page?frontend-enable=$ID&backendid=$backendid')");
        $color="text-default";
        $html[]="<tr>
				<td $width99 class='$color'>$icoSmoke$servicename ($port)</td>
				<td $width1 class='$color'>$icoEnabled</td>
				</tr>";
    }
    $html[]="</tbody></table>";
    $html[]="<script>";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function frontend_enable():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $frontendID=intval($_GET["frontend-enable"]);
    $backendID=intval($_GET["backendid"]);
    $ligne = $q->mysqli_fetch_array("SELECT backendname FROM backends WHERE ID=$backendID");
    $BackendName=$ligne["backendname"];

    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM connectors WHERE ID=$backendID");
    $FrontendName=$ligne["servicename"];

    $zmd5=md5("$frontendID$backendID");
    $q=new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM connectors_backends WHERE zmd5='$zmd5'");
    if(!isset($ligne["ID"])){$ligne["ID"]=0;}
    $ligneID=$ligne["ID"];
    if($ligneID==0){
        $q->QUERY_SQL("INSERT INTO connectors_backends (connectorid,backendid,zmd5) VALUES ('$frontendID','$backendID','$zmd5')");
        if(!$q->ok){
            echo $q->mysql_error;
            return false;
        }
        return admin_tracks("link PulseReverse backend $BackendName to $FrontendName");
    }
    $q->QUERY_SQL("DELETE FROM connectors_backends WHERE ID=$ligneID");
    if(!$q->ok){
        echo $q->mysql_error;
        return false;
    }
    return admin_tracks("Unlink PulseReverse backend $BackendName from $FrontendName");
}

function backend_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function   = $_GET["function"];
    $ID=intval($_GET["backend-popup"]);
    $ligne=array();
    $title="";
    $jsadd=null;

    if($ID>0) {
        $q=new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM backends WHERE ID=$ID");
        if(!$q->ok){
            echo $tpl->div_error($q->mysql_error);
        }
        $buttonname="{apply}";
    }else{
        $ligne["backendname"]="proxy-".time();
        $jsadd="dialogInstance2.close();";
        $ligne["listen_port"]=80;
        $ligne["bweight"]=1;
        $buttonname="{add}";
        $ligne["enabled"]=1;
        $ligne["ssl"]=0;
        $ligne["h2"]=0;
    }


    if($ID>0) {
        $jsrestart = $jsadd;

    }else{
        $jsrestart="$jsadd;$function()";
    }

    $tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}", $ligne["enabled"],true);
    $form[]=$tpl->field_checkbox("isDisconnected","{disonnect_from_farm}", $ligne["isDisconnected"]);
    $form[]=$tpl->field_text("backendname", "{backendname}", $ligne["backendname"],true);
    $form[]=$tpl->field_ipaddr("listen_ip", "{destination_address}", $ligne["listen_ip"]);
    $form[]=$tpl->field_numeric("listen_port","{destination_port}", $ligne["listen_port"]);
    $form[]=$tpl->field_numeric("bweight","{weight}", $ligne["bweight"]);
    $form[]=$tpl->field_checkbox("ssl","{destination_use_ssl}", $ligne["ssl"]);
    $form[]=$tpl->field_checkbox("h2","HTTP2", $ligne["h2"]);
    $form[]=$tpl->field_checkbox("proxyproto","{proxy_protocol}", $ligne["proxyproto"],false,"{proxy_protocol_explain}");

    $html=$tpl->form_outside("", $form,null,
            $buttonname,$jsrestart,"AsSquidAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function backend_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $enabled=intval($_POST["enabled"]);
    $backendname=$_POST["backendname"];
    $isDisconnected=intval($_POST["isDisconnected"]);
    $listen_port=intval($_POST["listen_port"]);
    $bweight=intval($_POST["bweight"]);
    $proxyproto=intval($_POST["proxyproto"]);
    $ssl=intval($_POST["ssl"]);
    $h2=intval($_POST["h2"]);
    $listen_ip=$_POST["listen_ip"];
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $sql="UPDATE backends SET 
                    enabled=$enabled,
                    backendname='$backendname',
                    isDisconnected=$isDisconnected,
                    listen_port=$listen_port,
                    bweight=$bweight,
                    ssl=$ssl,
                    h2=$h2,
                    proxyproto=$proxyproto,
                    listen_ip='$listen_ip'
                    WHERE ID=$ID";
    if($ID==0){
        $sql="INSERT INTO backends (enabled,backendname,isDisconnected,listen_port,bweight,proxyproto,listen_ip,ssl,h2) VALUES($enabled,'$backendname',$isDisconnected,$listen_port,$bweight,$proxyproto,'$listen_ip',$ssl,$h2)";
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/pulsereverse/reconfigure");
    return admin_tracks_post("Add/update PulseReverse Backend");

}
function search():bool{
    $tpl        = new template_admin();
    $search     = $_GET["search"];
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $LIMIT      = 250;
    $page       = CurrentPageName();
    $function   = $_GET["function"];

    $sql="SELECT ID,sitename,certificate FROM domains ORDER BY sitename LIMIT $LIMIT";
    if($search<>null) {
        $search = "*$search*";
        $search = str_replace("**", "*", $search);
        $search = str_replace("**", "*", $search);
        $search = str_replace("*","%",$search);
        $sql="SELECT ID,sitename,certificate FROM domains WHERE sitename LIKE '%$search%' ORDER BY sitename LIMIT $LIMIT";
    }
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error."<br>".$sql);}
    $t=time();
    $html[]="<table class=\"table table-hover\" id='ReversePulse$t'>
	<thead>
    	<tr>
        	<th nowrap>{domains}</th>
        	<th nowrap>{certificate}</th>
        	<th nowrap>{groups}</th>
        	<th nowrap>{backends}</th>
        	<th nowrap>{connectors}</th>
        	<th nowrap>Del</th>
        </tr>
  	</thead>
	<tbody>
";
    $ids=array();
    $width1="style='width:1%' nowrap";
    $width99="style='width:99%'";
    $function=$_GET["function"];



   foreach($results as $index=>$ligne) {
        $ID=intval($ligne["ID"]);
        $md=md5(serialize($ligne));
        $color      = "text-default";
        $del=$tpl->icon_delete("Loadjs('$page?website-delete=$ID&md=$md')","AsSquidAdministrator");
        $sitename=$ligne["sitename"];
        $ID=intval($ligne["ID"]);
        $certificate=GetCertificateLink($ligne["certificate"]);
        $sitename=$tpl->td_href($sitename,"","Loadjs('fw.pulsereverse.backends.php?website-js=$ID&function=$function')");
        $Groups=GetGroups($ID);
        $Backends=GetBackends($ID);
        $Connectors=ArrayConnectors($ID);

        $ids[]=$ID;
        $html[]="<tr id='$md'>
				<td $width1 class='$color'><span id='WpulseS-$ID'><strong>$sitename</strong></span></td>
				<td $width99 class='$color'><span id='WpulseB-$ID'>$certificate</span></td>
				<td $width1 class='$color'><span id='WpulseI-$ID'>$Groups</span></td>
				<td $width1 class='$color'><span id='WpulseC-$ID'>$Backends</span></td>
				<td $width1 class='$color'><span id='WpulseF-$ID'>$Connectors</span><span id='WpulseFxt-$ID'></span></td>
				<td $width1 class='$color'>$del</td>
				</tr>";

    }

    $topbuttons[]=array("Loadjs('$page?newwebsite-js=yes&function=$function')", ico_plus,"{new_webserver}");

    $TINY_ARRAY["TITLE"]="PulseReverse {websites}";
    $TINY_ARRAY["ICO"]=ico_earth;
    $TINY_ARRAY["EXPL"]="{APP_PULSE_REVERSE_WEBSITES}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $idss=implode(",",$ids);
    $pinger=$tpl->RefreshInterval_Loadjs("ReversePulse$t",$page,"pulse-js=$idss&function=$function");

    $html[]="</tbody></table>";
    $html[]="<script>";
    $html[]="$headsjs";
   // $html[]=$pinger;
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function ArrayConnectors($domainid):string{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $Z=ArrayBackends($domainid);
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    VERBOSE("Backends:".count($Z),__LINE__);
    $Y=array();
    foreach($Z as $BackendID=>$NONE) {
        VERBOSE("Backend:$BackendID",__LINE__);
        $results=$q->QUERY_SQL("SELECT connectors.ID,connectors.servicename FROM connectors,connectors_backends WHERE connectors_backends.connectorid=connectors.ID AND connectors_backends.backendid=$BackendID");
        foreach($results as $index=>$ligne) {
            $ID = $ligne["ID"];
            $servicename=$ligne["servicename"];
            VERBOSE("Connector:$ID ($servicename)",__LINE__);
            $Y[$ID]=$servicename;
        }
    }
    $html=array();
    if(count($Y)==0){return "";}
    $ico="fas fa-code-branch";
    foreach ($Y as $ID=>$connectorname) {
        $js="Loadjs('fw.pulsereverse.connectors.php?frontend-js=$ID&function=$function')";
        $html[]=$tpl->td_href("<i class='$ico'></i>&nbsp;".$connectorname,"",$js);;
    }
    return implode("<br>",$html);
}

function ArrayBackends($domainid):array{
    if(isset($GLOBALS["T"][$domainid])){
        return $GLOBALS["T"][$domainid];
    }
    $Z=ArrayGroups($domainid);
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $GLOBALS["T"][$domainid]=array();
    foreach($Z as $ID=>$GroupName){
        $results=$q->QUERY_SQL("SELECT backends.ID,backends.backendname FROM backends,linkbackends WHERE linkbackends.backendid=backends.ID AND linkbackends.groupid=$ID");
        foreach($results as $index=>$ligne) {
            $ID = $ligne["ID"];
            $backendname=$ligne["backendname"];
            $GLOBALS["T"][$domainid][$ID]=$backendname;
        }
    }
    return $GLOBALS["T"][$domainid];
}

function GetBackends($domainid):string{
    $function =$_GET["function"];
    $tpl=new template_admin();
    $ico=ico_server;
    $Z=ArrayBackends($domainid);
    $html=array();
    foreach ($Z as $ID=>$backendname) {
        $js="Loadjs('fw.pulsereverse.backends.php?backend-js=$ID&function=$function')";
        $html[]=$tpl->td_href("<i class='$ico'></i>&nbsp;".$backendname,"",$js);;
    }
    return implode("<br>",$html);
}

function ArrayGroups($domainid):array{

    if(isset($GLOBALS["Z"][$domainid])){
        return $GLOBALS["Z"][$domainid];
    }
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $results=$q->QUERY_SQL("SELECT domgroups.ID,domgroups.GroupName FROM domgroups,linkgroups WHERE domgroups.ID=linkgroups.groupid AND linkgroups.domainid=$domainid");
    $Z=array();
    if(!$q->ok){
        $GLOBALS["Z"][$domainid]=array();
        return array();
    }
    foreach($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $GroupName=$ligne["GroupName"];
        $GLOBALS["Z"][$domainid][$ID]=$GroupName;
        $Z[$ID]=$GroupName;
    }
    return $Z;
}
function GetGroups($domainid):string{
    $function =$_GET["function"];
    $tpl=new template_admin();
    $ico=ico_folder;
    $Z=ArrayGroups($domainid);
    $html=array();
    foreach ($Z as $ID=>$GroupName) {
        $js="Loadjs('fw.pulsereverse.backends.php?domainGroups-js=$ID&function=$function')";
        $html[]=$tpl->td_href("<i class='$ico'></i>&nbsp;".$GroupName,"",$js);;
    }
    if(count($html)==0){
        return "";
    }
    return implode("<br>",$html);
}

function GetCertificateLink($CommonName):string{
    if(strlen($CommonName)<2){
        return "";
    }
    $qCert=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $tpl=new template_admin();
    $ico=ico_certificate;

    if(preg_match("#SUB:([0-9]+)#",$CommonName,$re)){
        $Ligne=$qCert->mysqli_fetch_array("SELECT ID,commonName FROM subcertificates WHERE ID='$re[1]'");
        $commonName=$Ligne["commonName"];
        return $tpl->td_href("<i class='$ico'></i>&nbsp;$commonName","","Loadjs('fw.certificates-center.php?show-server-certificate=$re[1]')");
    }



    $Ligne=$qCert->mysqli_fetch_array("SELECT ID,CommonName FROM sslcertificates WHERE CommonName='$CommonName'");
    $CertName=$Ligne["CommonName"];
    $CertNameEnc=urlencode($CertName);
    $certificateid=intval($Ligne["ID"]);
    return $tpl->td_href("<i class='$ico'></i>&nbsp;$CommonName","","Loadjs('fw.certificates-center.php?certificate-js=$CertNameEnc&ID=$certificateid')");

}


function pulse_js():bool{
    $list=explode(",",$_GET["pulse-js"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $GlobalStatus=BackendsStatus();

    foreach ($list as $ID) {
        $ligne=$q->mysqli_fetch_array("SELECT * FROM backends WHERE ID=$ID");
        $td_backendname=base64_encode(td_backendname($ligne));
        $td_frontendname=base64_encode(td_frontendname($ligne));
        $td_domains=base64_encode(td_domains($ligne));
        $td_status=base64_encode(td_status($ligne,$GlobalStatus));
        $td_in_out=base64_encode(td_in_out($ligne,$GlobalStatus));
        $td_cnx=base64_encode(td_cnx($ligne,$GlobalStatus));
        $f[]="if(document.getElementById('WpulseF-$ID')){";
        $f[]="document.getElementById('WpulseF-$ID').innerHTML=base64_decode('$td_frontendname');";
        $f[]="}";
        $f[]="if(document.getElementById('WpulseB-$ID')){";
        $f[]="document.getElementById('WpulseB-$ID').innerHTML=base64_decode('$td_backendname');";
        $f[]="}";
        $f[]="if(document.getElementById('WpulseD-$ID')){";
        $f[]="document.getElementById('WpulseD-$ID').innerHTML=base64_decode('$td_domains');";
        $f[]="}";
        $f[]="if(document.getElementById('WpulseS-$ID')){";
        $f[]="document.getElementById('WpulseS-$ID').innerHTML=base64_decode('$td_status');";
        $f[]="}";
        $f[]="if(document.getElementById('WpulseI-$ID')){";
        $f[]="document.getElementById('WpulseI-$ID').innerHTML=base64_decode('$td_in_out');";
        $f[]="}";
        $f[]="if(document.getElementById('WpulseC-$ID')){";
        $f[]="document.getElementById('WpulseC-$ID').innerHTML=base64_decode('$td_cnx');";
        $f[]="}";
    }
    header("content-type: application/x-javascript");
    echo implode("\n",$f);
    return true;
}

function td_frontendname($ligne):string {
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($ligne["ID"]);
    $sql="SELECT connectors.ID,connectors.servicename FROM connectors,connectors_backends
    WHERE connectors.ID=connectors_backends.connectorid
    AND connectors_backends.backendid=$ID";
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $results=$q->QUERY_SQL($sql);
    $ico="fas fa-code-branch";
    $f=array();
    foreach ($results as $index=>$ligne) {
        $servicename=$ligne["servicename"];
        $f[]="<div><i class='$ico'></i>&nbsp;$servicename</div>";
    }
    if(count($f)==0){
        return $tpl->_ENGINE_parse_body("<span class='label label-warning'>{no_connector}</span>");
    }

    return $tpl->_ENGINE_parse_body($f);

}
function td_domains($ligne){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($ligne["ID"]);
    $sql="SELECT domgroups.ID,domgroups.GroupName,domgroups.enabled  FROM linkbackends,domgroups
            WHERE
                linkbackends.groupid=domgroups.ID AND
                linkbackends.backendid=$ID
            ORDER BY domgroups.GroupName";
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $results=$q->QUERY_SQL($sql);
    $ico=ico_folder;
    $f=array();
    foreach ($results as $index=>$ligne) {
        $GroupName=$ligne["GroupName"];
        $CountOfDomains=CountOfDomains(intval($ligne["ID"]));
        $f[]="<div><i class='$ico'></i>&nbsp;$GroupName ($CountOfDomains {domains})</div>";
    }
    if(count($f)==0){
        return $tpl->_ENGINE_parse_body("<span class='label label-warning'>{no_group_defined}</span>");
    }

    return $tpl->_ENGINE_parse_body($f);
}
function CountOfDomains($groupid):int {
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $sql="SELECT domains.enabled FROM domains,linkgroups 
            WHERE linkgroups.groupid=$groupid AND
            linkgroups.domainid=domains.ID";
    $results=$q->QUERY_SQL($sql);

    $c=0;
    foreach ($results as $index=>$ligne) {
        $enabled=intval($ligne["enabled"]);
        if($enabled==0){continue;}
        $c++;
    }
    return $c;
}
function domainGroups_domains($groupid):array{
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $sql="SELECT sitename FROM domains,linkgroups 
            WHERE linkgroups.groupid=$groupid AND
            linkgroups.domainid=domains.ID";
    $results=$q->QUERY_SQL($sql);
    $c=0;
    $f=array();
    foreach ($results as $index=>$ligne) {
        $f[]=$ligne["sitename"];

    }
    return $f;


}



function td_backendname($ligne):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($ligne["ID"]);
    $function=$_GET["function"];
    $ico_srv=ico_server;
    $backendname   = $ligne["backendname"];
    $proxyproto = intval($ligne["proxyproto"]);
    $proxyprotoIco="";
    if($proxyproto==1){
        $proxyprotoIco="&nbsp;<span class='label label-success'>{protocol}:Proxy</span>";
    }
    $err="";
    $PULSEREVERSE_BACKENDS_STATUS=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PULSEREVERSE_BACKENDS_STATUS"));
    if (json_last_error() == JSON_ERROR_NONE) {
       if(property_exists($PULSEREVERSE_BACKENDS_STATUS,$ID)){
           $status=$PULSEREVERSE_BACKENDS_STATUS->$ID;
           if(!$status->Status){
               $err="<div style='margin-top: 5px'><div class='text-danger'>{error}:$status->Error</div>";
           }
       }
    }


    $ipaddr     = $ligne["listen_ip"];
    $Port       = $ligne["listen_port"];
    $backendname=$tpl->td_href($backendname,"","Loadjs('$page?backend-js=$ID&function=$function')");
    $icoSrv="<i class='$ico_srv'></i>&nbsp;";
    return $tpl->_ENGINE_parse_body("$icoSrv$backendname ($ipaddr:$Port)$proxyprotoIco$err");
}

function domainGroups():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $backendid=intval($_GET["domainGroups"]);
    $html[]="<div id='domainGroups-popup-$backendid' style='margin-top:10px;margin-bottom:10px'></div>";
    $html[]=$tpl->search_block($page,"","","","&domainGroups-search=$backendid");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function domainGroups_search(){
    $tpl=new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $page=CurrentPageName();
    $backendid=intval($_GET["domainGroups-search"]);
    $search=$_GET["search"];
    $function=$_GET["function"];
    $LIMIT=250;

    $html[]="<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{groups}</th>
        	<th nowrap>{domains}</th>
        	<th nowrap>{active2}</th>
        	<th nowrap>Del</th>
        </tr>
  	</thead>
	<tbody>
";


    $sql="SELECT domgroups.ID,domgroups.GroupName,domgroups.enabled  FROM linkbackends,domgroups
            WHERE
                linkbackends.groupid=domgroups.ID AND
                linkbackends.backendid=$backendid
            ORDER BY domgroups.GroupName";

    if($search<>null) {
        $search = "*$search*";
        $search = str_replace("**", "*", $search);
        $search = str_replace("**", "*", $search);
        $search = str_replace("*","%",$search);

        $sql="SELECT domgroups.ID,domgroups.GroupName,domgroups.enabled 
            FROM linkbackends,domgroups
            WHERE
                linkbackends.groupid=domgroups.ID AND
                linkbackends.backendid=$backendid
                AND domgroups.GroupName LIKE '$search'
            ORDER BY domgroups.GroupName";
    }
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    $width1="style='width:1%' nowrap";
    $width99="style='width:99%'";
    $earch=ico_folder;
    foreach($results as $index=>$ligne) {
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));

        $status     = "<span class='label label-default'>{inactive2}</span>";
        $color      = "text-default text-bold";
        $GroupName=$ligne["GroupName"];
        $enabled=intval($ligne["enabled"]);
        if($enabled==0){
            $color="text-muted";
        }
        $del=$tpl->icon_delete("Loadjs('$page?group-delete=$ID&md=$md')","AsSquidAdministrator");
        $GroupName=$tpl->td_href($GroupName,"","Loadjs('$page?domainGroups-js=$ID&backendid=$backendid&function=$function')");

        $enable=$tpl->icon_check($enabled,"Loadjs('$page?group-enabled=$ID')");
        $domains=domainGroups_domains($ID);

        $html[]="<tr id='$md'>
				<td $width99 class='$color' nowrap><i class='$earch'></i>&nbsp;$GroupName</td>
				<td $width1 class='$color' style='width:99%'>".@implode(", ",$domains)."</td>
				<td $width1 class='$color'>$enable</td>
				<td $width1 class='$color'>$del</td>
				</tr>";

    }
    $html[]="</tbody></table>";
    $html[]="<script>";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="LoadAjaxSilent('domainGroups-popup-$backendid','$page?domainGroups-buttons=$backendid&function=$function')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function websites_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $groupid=intval($_GET["websites-popup"]);
    $html[]="<div id='websites-popup-$groupid' style='margin-top:10px;margin-bottom:10px'></div>";
    $html[]=$tpl->search_block($page,"","","","&websites-search=$groupid");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function websites_search():bool{
    $tpl=new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $page=CurrentPageName();
    $groupid=intval($_GET["websites-search"]);
    $search=$_GET["search"];
    $function=$_GET["function"];
    $LIMIT=250;

    $html[]="<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{websites}</th>
        	<th nowrap>{active2}</th>
        	<th nowrap>Del</th>
        </tr>
  	</thead>
	<tbody>
";
    $ANDS="";
    if($search<>null) {
        $search = "*$search*";
        $search = str_replace("**", "*", $search);
        $search = str_replace("**", "*", $search);
        $search = str_replace("*","%",$search);
        $ANDS="AND (domains.sitename LIKE '$search%' OR domains.rootdir LIKE '$search%')";
    }

    $sql="SELECT domains.ID,domains.rootdir,
            domains.sitename,domains.enabled,domains.certificateid FROM domains,linkgroups 
            WHERE linkgroups.groupid=$groupid AND
            linkgroups.domainid=domains.ID $ANDS
                        ORDER BY sitename LIMIT $LIMIT";


    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    $width1="style='width:1%' nowrap";
    $width99="style='width:99%'";
    $earch=ico_earth;
    foreach($results as $index=>$ligne) {
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));

        $status     = "<span class='label label-default'>{inactive2}</span>";
        $color      = "text-default text-bold";
        $sitename=$ligne["sitename"];
        $enabled=intval($ligne["enabled"]);
        $certificateid=intval($ligne["certificateid"]);
        if($enabled==0){
            $color="text-muted";
        }
        $del=$tpl->icon_delete("Loadjs('$page?website-delete=$ID&md=$md')","AsSquidAdministrator");
        $sitename=$tpl->td_href($sitename,"","Loadjs('$page?website-js=$ID&groupid=$groupid&function=$function')");

        $enable=$tpl->icon_check($enabled,"Loadjs('$page?website-enabled=$ID')");

        $html[]="<tr id='$md'>
				<td $width99 class='$color'><i class='$earch'></i>&nbsp;$sitename</td>
				<td $width1 class='$color'>$enable</td>
				<td $width1 class='$color'>$del</td>
				</tr>";

    }
    $html[]="</tbody></table>";
    $html[]="<script>";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="LoadAjaxSilent('websites-popup-$groupid','$page?websites-buttons=$groupid&function=$function')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function website_enable():bool{
    $tpl=new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $ID=intval($_GET["website-enabled"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM domains WHERE ID=$ID");
    $enabled=intval($ligne["enabled"]);
    $sitname=$ligne["sitename"];
    if($enabled==0){
        $q->QUERY_SQL("UPDATE domains SET enabled=1 WHERE ID=$ID");
        return admin_tracks("Enable ReversePulse domain $sitname");
    }
    $q->QUERY_SQL("UPDATE domains SET enabled=0 WHERE ID=$ID");
    return admin_tracks("Disable ReversePulse domain $sitname");
}
function websites_buttons():bool{
    $tpl=new template_admin();
    $groupid=intval($_GET["websites-buttons"]);
    $function=$_GET["function"];
    $page=CurrentPageName();
    $topbuttons[]=array("Loadjs('$page?website-js=0&function=$function&groupid=$groupid')", ico_plus,"{new_website}");
    echo $tpl->th_buttons($topbuttons);
    return true;
}


function domainGroups_buttons():bool{
    $tpl=new template_admin();
    $backendid=intval($_GET["domainGroups-buttons"]);
    $function=$_GET["function"];
    $page=CurrentPageName();
    $topbuttons[]=array("Loadjs('$page?domainGroups-js=0&function=$function&backendid=$backendid')", ico_plus,"{new_group}");
    echo $tpl->th_buttons($topbuttons);
    return true;
}

function td_in_out($ligne,$GlobalStatus):string{
    $ID=$ligne["ID"];
    if(!isset($GlobalStatus[$ID])){
        return "0&nbsp;/&nbsp;0";
    }
    if(!isset($GlobalStatus[$ID]["IN"])){
        return "0&nbsp;/&nbsp;0";
    }
    $in=FormatBytes($GlobalStatus[$ID]["IN"]/1024);
    $out=FormatBytes($GlobalStatus[$ID]["OUT"]/1024);
    return "$in&nbsp;/&nbsp;$out";

}
function td_cnx($ligne,$GlobalStatus):string{
    $ID=$ligne["ID"];
    if(!isset($GlobalStatus[$ID])){
        return "0";
    }
    if(!isset($GlobalStatus[$ID]["CNX"])){
        return "0";
    }
    $tpl=new template_admin();
    return $tpl->FormatNumber($GlobalStatus[$ID]["CNX"]);
}
function td_status($ligne,$GlobalStatus):string{
    $tpl=new template_admin();
    $ID=$ligne["ID"];
    if(!isset($GlobalStatus[$ID])){
        return $tpl->_ENGINE_parse_body("<span class='label label-default'>{inactive2}</span>");
    }
    if(!isset($GlobalStatus[$ID]["status"])){
        return $tpl->_ENGINE_parse_body("<span class='label label-default'>{inactive2}</span>");
    }
    $Status=$GlobalStatus[$ID]["status"];
    VERBOSE("td_status: $ID: $Status");
    switch ($Status) {
        case "UP":
            return $tpl->_ENGINE_parse_body("<span class='label label-primary'>{in_production}</span>");
        case "DOWN":
            return $tpl->_ENGINE_parse_body("<span class='label label-danger'>{stopped}</span>");

        case "MAINT":
            return $tpl->_ENGINE_parse_body("<span class='label label-warning'>{maintenance}</span>");

        default:
            return $tpl->_ENGINE_parse_body("<span class='label label-default'>$Status</span>");
    }

}

function BackendsStatus():array{

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/pulsereverse/info"));
    if(!$data->Status){
        return array();
    }
    if(!property_exists($data,"Info")){
        return array();
    }
    $Main=$data->Info;

    if(!property_exists($Main,"Backends")){
        return array();
    }
    foreach ($Main->Backends as $backend=>$class) {

        if(!preg_match("#Backend([0-9]+)#",$backend,$m)){
            continue;
        }
        $ID=$m[1];
        $srv_op_state=$class->srv_op_state;
        VERBOSE("$backend srv_op_state: $srv_op_state",__LINE__);
        switch (intval($srv_op_state)) {
            case 1:
                $MyStats[$ID]["status"]="MAINT";
                break;
            case 2:
                $MyStats[$ID]["status"]="UP";
                break;
            case 0:
                $MyStats[$ID]["status"]="DOWN";
                break;
            case 3:
                $MyStats[$ID]["status"]="MAINT";
                break;
            default:
                $MyStats[$ID]["status"]="CODE $srv_op_state?";
        }
    }



    if(property_exists($Main,"AllStats")){
        foreach ($Main->AllStats as $index=>$class) {
            $svname=$class->svname;
            $bin=$class->bin;
            $bout=$class->bout;
            $stot=$class->stot;
            if(!preg_match("#Backend([0-9]+)#",$svname,$m)){
                continue;

            }
            $ID=$m[1];
            $MyStats[$ID]["IN"]=$bin;
            $MyStats[$ID]["OUT"]=$bout;
            $MyStats[$ID]["CNX"]=$stot;
        }
    }
    VERBOSE("FINAL STATUS $ID: {$MyStats[$ID]["status"]}","__LINE__");
    return $MyStats;
}