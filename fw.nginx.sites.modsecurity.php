<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.modsecurity.tools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["www-tabs"])){www_tabs();exit;}
if(isset($_GET["www-parameters"])){www_parameters();exit;}
if(isset($_GET["www-parameters2"])){www_parameters2();exit;}
if(isset($_POST["serviceid"])){mod_security_save();exit;}
if(isset($_GET["www-whitelists"])){whitelists_start();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["down-backup"])){download_backup();exit;}
if(isset($_GET["widgets"])){widgets();exit;}
if(isset($_GET["EnableModSecurity"])){EnableModSecurity();exit;}
if(isset($_GET["EnableCrowdSec"])){EnableCrowdSec();exit;}

if(isset($_GET["section-general-js"])){section_general_js();exit;}
if(isset($_GET["section-general-popup"])){section_general_popup();exit;}

if(isset($_GET["section-upload-js"])){section_upload_js();exit;}
if(isset($_GET["section-upload-popup"])){section_upload_popup();exit;}

if(isset($_GET["section-exclude-js"])){section_exclude_js();exit;}
if(isset($_GET["section-exclude-popup"])){section_exclude_popup();exit;}




www_js();

function EnableModSecurity():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $EnableModSecurity=intval($_GET["EnableModSecurity"]);
    $serviceid=intval($_GET["serviceid"]);

    if($serviceid==0){
        echo $tpl->js_error("EnableModSecurity Site ID==0");
        return false;
    }

    $servicename=get_servicename($serviceid);
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("EnableModSecurity",$EnableModSecurity);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('modsecurity-parameters-$serviceid','$page?www-parameters2=$serviceid');\n";
    echo "LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');\n";
    echo "Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');\n";
    return admin_tracks("WAF plugin enable=$EnableModSecurity for reverse-proxy service $servicename");
}
function EnableCrowdSec():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $EnableCrowdSec=intval($_GET["EnableCrowdSec"]);
    $serviceid=$_GET["serviceid"];
    $servicename=get_servicename($serviceid);
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("EnableCrowdSec",$EnableCrowdSec);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");


    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/reload"));
    if (json_last_error()> JSON_ERROR_NONE) {
        $tpl->js_mysql_alert(json_last_error_msg());
        $sock->SET_INFO("EnableCrowdSec",0);
        return false;
    }
    if(!$data->Status){
        $tpl->js_mysql_alert($data->Error);
        $sock->SET_INFO("EnableCrowdSec",0);
        return false;
    }
    echo "LoadAjax('modsecurity-parameters-$serviceid','$page?www-parameters2=$serviceid');\n";
    echo "LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');\n";
    echo "Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');\n";
    return admin_tracks("CrowdSec plugin enable=$EnableCrowdSec for reverse-proxy service $servicename");
}
function www_tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-tabs"]);
    if($ID==0){
        echo "<script>alert('Wrong ID == 0');dialogInstance4.close();</script>";
        return false;
    }
    $array["Web Application Firewall"]="$page?www-parameters=$ID";
    $array["{whitelists}"]="$page?www-whitelists=$ID";
    echo $tpl->tabs_default($array);
    return true;
}
function www_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["serviceid"]);
    if($ID==0){
        foreach ($_GET as $key=>$val){
            $ff[]="$key:$val";
        }
        $addon=@implode("<br>",$ff);
        return $tpl->js_error("{corrupted} {website} ID (0) (www_js) $addon");
    }
    $servicename=get_servicename($ID);
    return $tpl->js_dialog4("#$ID - $servicename - Web Application Firewall", "$page?www-tabs=$ID",1380);
}
function section_general_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["serviceid"]);
    if($ID==0){
        return $tpl->js_error("{corrupted} {website} ID (0) section_general_js");
    }
    $servicename=get_servicename($ID);
    return $tpl->js_dialog6("#$ID - $servicename - Web Application Firewall", "$page?section-general-popup=yes&serviceid=$ID",650);
}
function section_upload_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["serviceid"]);
    if($ID==0){
        return $tpl->js_error("{corrupted} {website} ID (0) section_upload_js");
    }
    $servicename=get_servicename($ID);
    return $tpl->js_dialog6("#$ID - $servicename - {file_uploads}", "$page?section-upload-popup=yes&serviceid=$ID",750);
}
function section_exclude_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["serviceid"]);
    if($ID==0){
        return $tpl->js_error("{corrupted} {website} ID (0) section_exclude_js");
    }
    $servicename=get_servicename($ID);
    return $tpl->js_dialog6("#$ID - $servicename - {exclude}", "$page?section-exclude-popup=yes&serviceid=$ID",650);
}



function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return $ligne["servicename"];
}
function whitelists_start():bool{
    $t=time();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $serverid=$_GET["www-whitelists"];

    $html[]="<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
     <div id='table-loader-$t'></div>
    </div>
    
    <script>
    		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader-$t','$page?srvid=$serverid&search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
    
</script>
    ";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rulename($ruleid){
    if(!isset($GLOBALS["frulename"][$ruleid])){return $GLOBALS["frulename"][$ruleid];}
    if($ruleid=="200002"){return "Failed to parse request body.";}
    $q=new lib_sqlite("/home/artica/SQLITE/modsecurity_rules.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rules WHERE ID=$ruleid");
    $GLOBALS["frulename"][$ruleid]=trim($ligne["rulename"]);
    return $GLOBALS["frulename"][$ruleid];
}
function download_backup():bool{
    $sock=new sockets();
    $ID=intval($_GET["down-backup"]);
    $sock->getFrameWork("nginx.php?prepare-modsec-backup=$ID");

    $path=PROGRESS_DIR."/modesecurity-backup-$ID.tar.gz";
    $type=mime_content_type($path);
    header("Content-type: $type");
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"modesecurity-backup-$ID.tar.gz\"");
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = filesize($path);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($path);
    @unlink($path);
return true;
}


function  search(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["srvid"]);
    $t=time();

    $q=new lib_sqlite(NginxGetDB());
    $MAIN=$tpl->format_search_protocol($_GET["search"]);

    if($MAIN["MAX"]>1500){$MAIN["MAX"]=1500;}
    $max=$MAIN["MAX"];
    $query=$MAIN["TERM"];
    $TRCLASS=null;
    if($query<>null){
        if(is_numeric($query)){
            $WHERE=" AND (wfrule=$query)";
        }else{
            $WHERE=" AND (spath LIKE '%$query%')";
        }
    }
    $sql="SELECT * FROM modsecurity_whitelist WHERE ((serviceid=0 OR serviceid=$ID) $WHERE) ORDER BY spath LIMIT $max";
    $results=$q->QUERY_SQL($sql);

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" style='margin-top:10px' data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{rule}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{web_service}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{path}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $serviceid=$ligne["serviceid"];
        $path=$ligne["spath"];
        $md=md5(serialize($ligne));
        $www="{all_websites}";
        $ruleid=$ligne["wfrule"];
        if($path==null){$path="{all_directories}";}
        $ID=$ligne["ID"];
        $uri_rule=null;
        $rulename_text=null;
        $rulename=rulename($ruleid);
        if($rulename<>null) {
            $uri_rule = "Loadjs('fw.modsecurity.defrules.php?ruleid-js=$ruleid')";
            $rulename_text="$rulename";
        }

        if($serviceid>0){
            $sql = "SELECT servicename FROM nginx_services WHERE ID=$serviceid";
            $WebService=$q->mysqli_fetch_array($sql);
            $www=$WebService["servicename"];
        }


        $ruleid_column=$ruleid;

        if($uri_rule<>null) {
            $ruleid_column = $tpl->td_href($ruleid, null, $uri_rule);
        }
        if($ruleid==0){
            $ruleid="*";$rulename_text="{all}";
            $ruleid_column=$ruleid;
        }


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%' nowrap>$ruleid_column</td>";
        $html[]="<td width='5%' nowrap>$www</td>";
        $html[]="<td width='50%' nowrap>$path</td>";
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
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": false },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}
function www_parameters():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["www-parameters"]);

    if($ID==0){
        echo "<script>alert('Wrong ID == 0');dialogInstance4.close();</script>";
        return false;
    }

    echo "<div id='modsecurity-parameters-$ID' style='margin-top:10px'></div>";
    echo "<script>LoadAjax('modsecurity-parameters-$ID','$page?www-parameters2=$ID');</script>";
    return true;
}

function www_parameters2():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-parameters2"]);

    if($ID==0){
        echo "<script>alert('Wrong ID == 0');dialogInstance4.close();</script>";
        return false;
    }
    $EnableClamavDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemon"));
    $sock=new socksngix($ID);
    $ModSecurityDefaultAction=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityAction");
    if($ModSecurityDefaultAction==null){$ModSecurityDefaultAction="auditlog,pass";}
    $ModSecurityScanAvPost=0;

    $inbound_anomaly_score_threshold=intval($sock->GET_INFO("inbound_anomaly_score_threshold"));
    if($inbound_anomaly_score_threshold==0){$inbound_anomaly_score_threshold=5;}

    $sModSecurityAction["DEFAULT"]="{default}";
    $sModSecurityAction["auditlog,pass"]="{alert_and_pass}";
    $sModSecurityAction["auditlog,deny,status:405"]="{alert_and_block}";

    $ModSecurityAction=trim($sock->GET_INFO("ModSecurityAction"));

    $ModSecurityBackupReport=intval($sock->GET_INFO("ModSecurityBackupReport"));
    if($ModSecurityAction==null){$ModSecurityAction=$ModSecurityDefaultAction;}
    $modSecDisabled=modSecDisabled($ID);




    $tpl->table_form_section("Web Application Firewall","{ModSecurityExplain}");
    $tpl->table_form_field_js("Loadjs('$page?section-general-js=yes&serviceid=$ID')");
    $tpl->table_form_field_text("{default_action}",$sModSecurityAction[$ModSecurityAction],ico_shield);
    $tpl->table_form_field_text("{inbound_anomaly_score_threshold}",$inbound_anomaly_score_threshold,ico_timeout);

   // $SecRequestBodyLimit=intval($sock->GET_INFO("SecRequestBodyLimit"));
    //if($SecRequestBodyLimit==0){$SecRequestBodyLimit=12;}
   // $SecRequestBodyNoFilesLimit=intval($sock->GET_INFO("SecRequestBodyNoFilesLimit"));
    //if($SecRequestBodyNoFilesLimit==0){
      //  $SecRequestBodyNoFilesLimit=512;
    //}


    $crs_exclusions_cpanel=intval($sock->GET_INFO("crs_exclusions_cpanel"));
    $crs_exclusions_drupal=intval($sock->GET_INFO("crs_exclusions_drupal"));
    $crs_exclusions_dokuwiki=intval($sock->GET_INFO("crs_exclusions_dokuwiki"));
    $crs_exclusions_nextcloud=intval($sock->GET_INFO("crs_exclusions_nextcloud"));
    $crs_exclusions_wordpress=intval($sock->GET_INFO("crs_exclusions_wordpress"));
    $crs_exclusions_xenforo=intval($sock->GET_INFO("crs_exclusions_xenforo"));
    $ModSecurityExcludeCountries=trim($sock->GET_INFO("ModSecurityExcludeCountries"));

    $excludes=array();
    if($crs_exclusions_wordpress==1){
        $excludes[]="wordpress";
    }
    if($crs_exclusions_xenforo==1){
        $excludes[]="XenForo";
    }
    if($crs_exclusions_cpanel==1){
        $excludes[]="CPanel";
    }
    if($crs_exclusions_drupal==1){
        $excludes[]="Drupal";
    }
    if($crs_exclusions_dokuwiki==1){
        $excludes[]="DokuWiki";
    }
    if($crs_exclusions_nextcloud==1){
        $excludes[]="NextCloud";
    }
    $tpl->table_form_field_js("Loadjs('$page?section-exclude-js=yes&serviceid=$ID')");
    if(count($excludes)==0){
        $tpl->table_form_field_bool("{exclude} {rules}",0,ico_params);
    }else{
        $tpl->table_form_field_text("{exclude} {rules}",@implode(", ",$excludes),ico_params);
    }
    if($EnableClamavDaemon==0){
        $tpl->table_form_field_js("Loadjs('$page?section-upload-js=yes&serviceid=$ID')");
        $tpl->table_form_field_text("{scan_av_on_uploads}","{not_installed}",ico_bug);
    }else{
        $tpl->table_form_field_js("Loadjs('$page?section-upload-js=yes&serviceid=$ID')");
        $tpl->table_form_field_bool("{scan_av_on_uploads}",$ModSecurityScanAvPost,ico_bug);
    }

    //$tpl->table_form_field_text("{request_body_max_size}","{$SecRequestBodyLimit}MB",ico_weight);
    //$tpl->table_form_field_text("{php5PostMaxSize}","{$SecRequestBodyNoFilesLimit}KB",ico_weight);


    $ModSecurityScanAvIMAGES=intval($sock->GET_INFO("ModSecurityScanAvIMAGES"));
    $ModSecurityScanAvZIP=intval($sock->GET_INFO("ModSecurityScanAvZIP"));
    $ModSecurityScanAvZIPENC=intval($sock->GET_INFO("ModSecurityScanAvZIPENC"));
    $ModSecurityScanAvGZIP=intval($sock->GET_INFO("ModSecurityScanAvGZIP"));
    $ModSecurityScanAvRAR=intval($sock->GET_INFO("ModSecurityScanAvRAR"));
    $ModSecurityScanAv7ZIP=intval($sock->GET_INFO("ModSecurityScanAv7ZIP"));
    $ModSecurityScanAvBIN=intval($sock->GET_INFO("ModSecurityScanAvBIN"));
    $ModSecurityScanAvSCRIPT=intval($sock->GET_INFO("ModSecurityScanAvSCRIPT"));
    $ModSecurityScanAvLIBS=intval($sock->GET_INFO("ModSecurityScanAvLIBS"));
    $ModSecurityScanAvOFFICE=intval($sock->GET_INFO("ModSecurityScanAvOFFICE"));
    $ModSecurityScanAvMUSIC=intval($sock->GET_INFO("ModSecurityScanAvMUSIC"));
    $ModSecurityScanAvVIDEO=intval($sock->GET_INFO("ModSecurityScanAvVIDEO"));
    $ModSecurityScanAvISO=intval($sock->GET_INFO("ModSecurityScanAvISO"));
    $ModSecurityScanAvUnZIP=intval($sock->GET_INFO("ModSecurityScanAvUnZIP"));
    $denys=array();
    //deny_uploads
    $Scnner="";
    if($ModSecurityScanAvUnZIP==1){
        $Scnner="({uncompress_zip_files})";
    }
    if($ModSecurityScanAvIMAGES==1){
        $denys[]="{pictures}";
    }
    if($ModSecurityScanAvZIP==1){
           $denys[]="zip";
    }
    if($ModSecurityScanAvZIPENC==1){
            $denys[]="zip+password";
    }
    if($ModSecurityScanAvGZIP==1){
        $denys[]="Gzip";
    }
    if($ModSecurityScanAvRAR==1){
        $denys[]="Rar";
    }
    if($ModSecurityScanAv7ZIP==1){
        $denys[]="7zip";
    }
    if($ModSecurityScanAvISO==1){
        $denys[]="Isos";
    }
    if($ModSecurityScanAvBIN==1){
        $denys[]="Binaries";
    }
    if($ModSecurityScanAvSCRIPT==1){
        $denys[]="Scripts";
    }
    if($ModSecurityScanAvLIBS==1){
        $denys[]="Librairies";
    }
    if($ModSecurityScanAvOFFICE==1){
        $denys[]="Office docs";
    }
    if($ModSecurityScanAvMUSIC==1){
        $denys[]="{musicfiles}";
    }
    if($ModSecurityScanAvVIDEO==1){
        $denys[]="{videofiles}";
    }

    if(count($denys)==0){
        $tpl->table_form_field_js("Loadjs('$page?section-upload-js=yes&serviceid=$ID')");
        $tpl->table_form_field_bool("{deny_uploads}",0,ico_file);
    }else{
        $tpl->table_form_field_js("Loadjs('$page?section-upload-js=yes&serviceid=$ID')");
        $tpl->table_form_field_text("{deny_uploads}",@implode(", ",$denys)." $Scnner",ico_file);
    }

    $form=$tpl->table_form_compile();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:335px;vertical-align:top;padding-top:9px'><div id='widget-$ID'></div></td>";
    $html[]="<td style='padding-left:15px;vertical-align:top;'>";
    $html[]="<div id='modsecurity-compile-$ID'></div>";
    $html[]=$form;
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('widget-$ID','$page?widgets=$ID');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

    return true;

}
function section_js($serviceid):string{
    $page=CurrentPageName();
    $js[]="dialogInstance6.close();";
    $js[]="LoadAjax('modsecurity-parameters-$serviceid','$page?www-parameters2=$serviceid');";
    $js[]="Loadjs('fw.nginx.sites.php?td-row=$serviceid');";
    return @implode("",$js);
}

function section_upload_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["serviceid"]);
    $sock=new socksngix($ID);
    $modSecDisabled=modSecDisabled($ID);
    $js=section_js($ID);


    if($ID==0){
        echo "<script>alert('Wrong ID == 0');dialogInstance6.close();</script>";
        return false;
    }
    $EnableClamavDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemon"));
    $ModSecurityScanAvPost=0;
    $AV=true;
    if($EnableClamavDaemon==1){
        $ModSecurityScanAvPost=intval($sock->GET_INFO("ModSecurityScanAvPost"));
        $AV=false;
    }
/*
    $SecRequestBodyLimit=intval($sock->GET_INFO("SecRequestBodyLimit"));
    if($SecRequestBodyLimit==0){
        $SecRequestBodyLimit=12;
    }
    $SecRequestBodyNoFilesLimit=intval($sock->GET_INFO("SecRequestBodyNoFilesLimit"));
    if($SecRequestBodyNoFilesLimit==0){
        $SecRequestBodyNoFilesLimit=512;
    }

    $form[]=$tpl->field_section("{limits}");
    $form[]=$tpl->field_numeric("SecRequestBodyLimit","{request_body_max_size} (MB)",$SecRequestBodyLimit,"{request_body_max_size_text}");
    $form[]=$tpl->field_numeric("SecRequestBodyNoFilesLimit","{php5PostMaxSize} (KB)",$SecRequestBodyNoFilesLimit,"");
*/
    $ModSecurityScanAvIMAGES=intval($sock->GET_INFO("ModSecurityScanAvIMAGES"));
    $ModSecurityScanAvZIP=intval($sock->GET_INFO("ModSecurityScanAvZIP"));
    $ModSecurityScanAvZIPENC=intval($sock->GET_INFO("ModSecurityScanAvZIPENC"));
    $ModSecurityScanAvGZIP=intval($sock->GET_INFO("ModSecurityScanAvGZIP"));
    $ModSecurityScanAvRAR=intval($sock->GET_INFO("ModSecurityScanAvRAR"));
    $ModSecurityScanAv7ZIP=intval($sock->GET_INFO("ModSecurityScanAv7ZIP"));
    $ModSecurityScanAvBIN=intval($sock->GET_INFO("ModSecurityScanAvBIN"));
    $ModSecurityScanAvSCRIPT=intval($sock->GET_INFO("ModSecurityScanAvSCRIPT"));
    $ModSecurityScanAvLIBS=intval($sock->GET_INFO("ModSecurityScanAvLIBS"));
    $ModSecurityScanAvOFFICE=intval($sock->GET_INFO("ModSecurityScanAvOFFICE"));
    $ModSecurityScanAvMUSIC=intval($sock->GET_INFO("ModSecurityScanAvMUSIC"));
    $ModSecurityScanAvVIDEO=intval($sock->GET_INFO("ModSecurityScanAvVIDEO"));
    $ModSecurityScanAvISO=intval($sock->GET_INFO("ModSecurityScanAvISO"));
    $ModSecurityScanAvUnZIP=intval($sock->GET_INFO("ModSecurityScanAvUnZIP"));

    $form[]=$tpl->field_hidden("serviceid",$ID);

    $form[]=$tpl->field_section("Antivirus");
    $form[]=$tpl->field_checkbox("ModSecurityScanAvPost","{scan_av_on_uploads}",$ModSecurityScanAvPost,false,"",$AV);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvUnZIP","{uncompress_zip_files}",$ModSecurityScanAvUnZIP,false);


    $form[]=$tpl->field_section("{deny_uploads}");
    $form[]=$tpl->field_checkbox("ModSecurityScanAvIMAGES","{deny_uploads} ({pictures})",$ModSecurityScanAvIMAGES);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvZIP","{deny_uploads} (ZIP)",$ModSecurityScanAvZIP);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvZIPENC","{deny_uploads} (ZIP {password})",$ModSecurityScanAvZIPENC);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvGZIP","{deny_uploads} (GZIP)",$ModSecurityScanAvGZIP);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvRAR","{deny_uploads} (RAR)",$ModSecurityScanAvRAR);
    $form[]=$tpl->field_checkbox("ModSecurityScanAv7ZIP","{deny_uploads} (7zip)",$ModSecurityScanAv7ZIP);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvISO","{deny_uploads} (iso,nrg..)",$ModSecurityScanAvISO);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvBIN","{deny_uploads} (Elf/exe)",$ModSecurityScanAvBIN);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvSCRIPT","{deny_uploads} (PowerShell,bat,php,python..)",$ModSecurityScanAvSCRIPT);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvLIBS","{deny_uploads} (dll,so)",$ModSecurityScanAvLIBS);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvOFFICE","{deny_uploads} (MS Office)",$ModSecurityScanAvOFFICE);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvMUSIC","{deny_uploads} {musicfiles}",$ModSecurityScanAvMUSIC);
    $form[]=$tpl->field_checkbox("ModSecurityScanAvVIDEO","{deny_uploads} {videofiles}",$ModSecurityScanAvVIDEO);

    if(!$modSecDisabled){
        VERBOSE("DISABLE FORM = FALSE",__LINE__);
    }
    $html=$tpl->form_outside("", $form,"","{apply}",$js,"AsSystemWebMaster",false,$modSecDisabled);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_exclude_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["serviceid"]);
    $sock=new socksngix($ID);
    $modSecDisabled=modSecDisabled($ID);
    $js=section_js($ID);


    if($ID==0){
        echo "<script>alert('Wrong ID == 0');dialogInstance6.close();</script>";
        return false;
    }


    $form[]=$tpl->field_hidden("serviceid",$ID);
    $crs_exclusions_cpanel=intval($sock->GET_INFO("crs_exclusions_cpanel"));
    $crs_exclusions_drupal=intval($sock->GET_INFO("crs_exclusions_drupal"));
    $crs_exclusions_dokuwiki=intval($sock->GET_INFO("crs_exclusions_dokuwiki"));
    $crs_exclusions_nextcloud=intval($sock->GET_INFO("crs_exclusions_nextcloud"));
    $crs_exclusions_wordpress=intval($sock->GET_INFO("crs_exclusions_wordpress"));
    $crs_exclusions_xenforo=intval($sock->GET_INFO("crs_exclusions_xenforo"));

    $form[]=$tpl->field_checkbox("crs_exclusions_wordpress","WordPress",$crs_exclusions_wordpress);
    $form[]=$tpl->field_checkbox("crs_exclusions_cpanel","CPanel",$crs_exclusions_cpanel,false);
    $form[]=$tpl->field_checkbox("crs_exclusions_drupal","Drupal",$crs_exclusions_drupal,false);
    $form[]=$tpl->field_checkbox("crs_exclusions_dokuwiki","DokuWiki",$crs_exclusions_dokuwiki);
    $form[]=$tpl->field_checkbox("crs_exclusions_nextcloud","NextCloud",$crs_exclusions_nextcloud);
    $form[]=$tpl->field_checkbox("crs_exclusions_xenforo","XenForo",$crs_exclusions_xenforo);

    if(!$modSecDisabled){
        VERBOSE("DISABLE FORM = FALSE",__LINE__);
    }
    $html=$tpl->form_outside("{exclude} {rules}", $form,"","{apply}",$js,"AsSystemWebMaster",false,$modSecDisabled);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_general_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["serviceid"]);
    $modSecDisabled=modSecDisabled($ID);
    if($ID==0){
        echo "<script>alert('Wrong ID == 0');dialogInstance6.close();</script>";
        return false;
    }

    $EnableClamavDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemon"));
    $sock=new socksngix($ID);
    $ModSecurityDefaultAction=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityAction");
    if($ModSecurityDefaultAction==null){$ModSecurityDefaultAction="auditlog,pass";}
    $ModSecurityScanAvPost=0;
    $AV=true;
    if($EnableClamavDaemon==1){
        $ModSecurityScanAvPost=intval($sock->GET_INFO("ModSecurityScanAvPost"));
        $AV=false;
    }
    $inbound_anomaly_score_threshold=intval($sock->GET_INFO("inbound_anomaly_score_threshold"));
    if($inbound_anomaly_score_threshold==0){$inbound_anomaly_score_threshold=5;}

    $ModSecurityBackupReport=intval($sock->GET_INFO("ModSecurityBackupReport"));

    $js=section_js($ID);

    $ModSecurityAction=GetModSecurityAction($ID);
    VERBOSE("modSecDisabled = $modSecDisabled($ID)",__LINE__);
    $form[]=$tpl->field_hidden("serviceid",$ID);
    $form[]=$tpl->field_array_hash(sModSecurityAction,"ModSecurityAction","nonull:{default_action}",$ModSecurityAction);



    //$form[]=$tpl->field_checkbox("ModSecurityBackupReport","{backup_reports}",$ModSecurityBackupReport,false,null,$modSecDisabled);

    $form[]=$tpl->field_numeric("inbound_anomaly_score_threshold","{inbound_anomaly_score_threshold}",$inbound_anomaly_score_threshold,"{inbound_anomaly_score_threshold_explain}");

    if($ModSecurityBackupReport==1){
        $MODESECURITY_BACKUPS_REPORTS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MODESECURITY_BACKUPS_REPORTS"));
        if(isset($MODESECURITY_BACKUPS_REPORTS[$ID])){
            $container_size=$MODESECURITY_BACKUPS_REPORTS[$ID]/1024;
            $value["VALUE"]=FormatBytes($container_size);
            $value["BUTTON"]=true;
            $value["BUTTON_JS"]="document.location.href='$page?down-backup=$ID'";
            $value["BUTTON_CAPTION"]="{download}";
            $form[]=$tpl->field_info("modsecurity_backup_size","{container_size}",$value);

        }
    }
    if(!$modSecDisabled){
     VERBOSE("DISABLE FORM = FALSE",__LINE__);
    }
    $html=$tpl->form_outside("", $form,"","{apply}",$js,"AsSystemWebMaster",false,$modSecDisabled);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function widgets():bool{
    $ID=intval($_GET["widgets"]);
    if($ID==0){
        return false;
    }
    echo widget_mod_security($ID);
    echo widget_crowdsec($ID);

return true;
}
function widget_crowdsec($ID):string{
    if(intval($ID)==0){return "";}
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $EnableCrowdSecGen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdSec"));
    if($EnableCrowdSecGen==0){
        return $tpl->widget_grey("CrowdSec","{not_installed}",ico_disabled);
    }
    $sock=new socksngix($ID);
    $EnableCrowdSec=intval($sock->GET_INFO("EnableCrowdSec"));
    if($EnableCrowdSec==0){

        $btn[0]["js"] = "Loadjs('$page?EnableCrowdSec=1&serviceid=$ID');";
        $btn[0]["name"] = "{activate}";
        $btn[0]["icon"] = "far fa-shield-check";
        return $tpl->widget_grey("CrowdSec","{inactive}",$btn,ico_disabled);
    }
    $btn[0]["js"] = "Loadjs('$page?EnableCrowdSec=0&serviceid=$ID');";
    $btn[0]["name"] = "{disable}";
    $btn[0]["icon"] = ico_shield_disabled;
    return $tpl->widget_vert("CrowdSec","{active2}",$btn,ico_shield);


}
function modSecDisabled($ID):bool{
    $NginxHTTPModSecurity       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPModSecurity"));
    $EnableModSecurityIngix     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    if($NginxHTTPModSecurity==0){
        VERBOSE("modSecDisabled: NginxHTTPModSecurity = 0 -> TRUE",__LINE__);
        return true;
    }
    if($EnableModSecurityIngix==0){
        VERBOSE("modSecDisabled: EnableModSecurityIngix = 0 -> TRUE",__LINE__);
        return true;
    }
    $sock=new socksngix($ID);
    $EnableModSecurity=intval($sock->GET_INFO("EnableModSecurity"));
    if($EnableModSecurity==0){
        VERBOSE("modSecDisabled: EnableModSecurity($ID) = 0 -> TRUE",__LINE__);
        return true;
    }
    VERBOSE("modSecDisabled: EnableModSecurity($ID) = $EnableModSecurity -> FALSE",__LINE__);
    return false;
}

function widget_mod_security($ID):string{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $NginxHTTPModSecurity       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPModSecurity"));
    $EnableModSecurityIngix     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));

    if($NginxHTTPModSecurity==0){
        return $tpl->widget_grey("{WAF_LONG}","{missing_module}",ico_disabled);
    }
    if($EnableModSecurityIngix==0){
        return $tpl->widget_grey("{WAF_LONG}","{feature_disabled} ({global})",ico_disabled);
    }

    $sock=new socksngix($ID);
    $EnableModSecurity=intval($sock->GET_INFO("EnableModSecurity"));
    if($EnableModSecurity==1){
        $btn[0]["js"] = "Loadjs('$page?EnableModSecurity=0&serviceid=$ID');";
        $btn[0]["name"] = "{disable}";
        $btn[0]["icon"] = ico_disabled;
        return $tpl->widget_vert("{WAF_LONG}","{active2}",$btn,ico_shield);

    }
    $btn[0]["js"] = "Loadjs('$page?EnableModSecurity=1&serviceid=$ID');";
    $btn[0]["name"] = "{activate}";
    $btn[0]["icon"] = "far fa-shield-check";
    return $tpl->widget_grey("{WAF_LONG}","{inactive}",$btn,ico_shield_disabled);
}

function mod_security_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $ID=intval($_POST["serviceid"]);
    if($ID==0){echo "????\n";exit;}
    $servicename=get_servicename($ID);



    $trck=array();
    unset($_POST["serviceid"]);
    $sock=new socksngix($ID);
    if(isset($_POST["SecRequestBodyLimit"])) {
        $sock->SET_INFO("SecRequestBodyLimit", $_POST["SecRequestBodyLimit"]);
        unset($_POST["SecRequestBodyLimit"]);
    }
    if(isset($_POST["SecRequestBodyNoFilesLimit"])) {
        $sock->SET_INFO("SecRequestBodyNoFilesLimit", $_POST["SecRequestBodyNoFilesLimit"]);
        unset($_POST["SecRequestBodyNoFilesLimit"]);
    }

    foreach ($_POST as $key=>$val){
        $sock->SET_INFO($key,$val);
        $trck[]="$key:$val";
    }
    if(isset($_POST["ModSecurityBackupReport"])){
        $ModSecurityBackupReport=intval($_POST["ModSecurityBackupReport"]);
        if($ModSecurityBackupReport==0) {
            $sock = new sockets();
            $sock->getFrameWork("nginx.php?delete-modsec-backup=$ID");
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return admin_tracks("Update WAF settings for $servicename ( ".@implode(", ",$trck).")");


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