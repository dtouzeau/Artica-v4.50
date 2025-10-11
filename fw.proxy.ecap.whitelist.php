<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
$GLOBALS["GroupType"]["src"]="{src_addr}";
$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
$GLOBALS["GroupType"]["dst"]="{dst_addr}";
$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
$GLOBALS["GroupType"]["dstdom_regex"]="{dstdomain_regex}";


if(isset($_GET["btns-white"])){echo base64_decode($_GET["btns-white"]);exit;}
if(isset($_GET["search-form"])){rules_search_block();exit;}
if(isset($_GET["search"])){rules_list_search();exit;}
if(isset($_GET["popup-js"])){popup_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["GroupType"])){ecapExplainThis();exit;}
if(isset($_GET["switch-js"])){eCapSwitchPerform();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_POST["ID"])){save();exit;}
if(isset($_GET["config-popup-js"])){config_popup_js();exit;}
if(isset($_GET["config-popup"])){config_popup();exit;}
if(isset($_GET["byjs"])){page_js();exit;}
if(isset($_GET["byjs-popup"])){page_js_popup();exit;}
if(isset($_GET["SquidDisableWhiteLists"])){SquidDisableWhiteLists();exit;}



rules_search_block();
function SquidDisableWhiteLists():bool{
    $SquidDisableWhiteLists = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableWhiteLists"));
    if($SquidDisableWhiteLists==1){
        $SquidDisableWhiteLists=0;
    }else{
        $SquidDisableWhiteLists=1;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDisableWhiteLists",$SquidDisableWhiteLists);
    $function=$_GET["function"];
    echo "$function();\n";
    echo restart_js();
    return true;
}

function delete_js(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT pattern FROM ecap_whitelist WHERE ID='{$_GET["delete-js"]}'");
    $md5=$_GET["md5"];
    $tpl->js_confirm_delete($ligne["pattern"],"delete",$_GET["delete-js"],"$('#$md5').remove();");
}

function delete(){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ID=$_POST["delete"];
    $ligne=$q->mysqli_fetch_array("SELECT pattern FROM ecap_whitelist WHERE ID='{$_GET["delete-js"]}'");
    $pattern=$ligne["pattern"];

    $q->QUERY_SQL("DELETE FROM ecap_whitelist WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return;}
    admin_tracks("Removed $pattern from global whitelist");

}

function page_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{global_whitelists}","$page?byjs-popup=yes");
}
function page_js_popup(){
    $page=CurrentPageName();
    echo "<div id='table-loader-global-whitelist'></div>
	<script>
	LoadAjax('table-loader-global-whitelist','$page?table=yes');
	</script>";
}

function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    include_once('ressources/class.tcpip.inc');
    $ID=$_POST["ID"];
    $pattern=trim($_POST["pattern"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $description=$q->sqlite_escape_string2($_POST["description"]);
    $ztype=$_POST["ztype"];
    $enabled=$_POST["enabled"];
    $f=array();
    $admtrck=array();

    $patternz=array();
    $zDate=date("Y-m-d H:i:s");

    if($ID==0){
        if(strpos(" $pattern", "\n")>0){
            $patternz=explode("\n",$pattern);
        }else{
            $patternz[]=$pattern;
        }

        $ip=new IP();

        foreach ($patternz as $item){
            $item=trim(strtolower($item));
            if(preg_match("#^\.(.+)$#",$item,$re)){$item=$re[1];}

            if($ztype=="dstdomain"){
                if(preg_match("#(http|ftp).*?:\/\/#",$item)){
                    $url_extract=parse_url($item);
                    $item=$url_extract["host"];
                }

            }



            if($item==null){continue;}
            $admtrck[]=$item;

            if( ($ztype=="src") OR ($ztype=="dst")){
                if(!$ip->isIPAddressOrRange($item)){continue;}
            }
            if( $ztype=="arp"){
                $item=str_replace("-", ":", $item);
                if(!$ip->IsvalidMAC($item)){continue;}
            }

            $line=str_replace("^","",$item);
            if(substr($line,0,1)=="."){$line=substr($line, 1,strlen($line));}


            $item=$q->sqlite_escape_string2($item);
            $f[]="('$zDate','$ztype','$item',1,'$description')";


        }
        $q->QUERY_SQL("INSERT OR IGNORE INTO ecap_whitelist (zDate,ztype,pattern,enabled,description) VALUES ".@implode(",", $f));
        if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
        return admin_tracks("Added in global whitelist ".@implode(",",$admtrck));


    }


    $pattern=trim(strtolower($pattern));

    if($ztype=="dstdomain"){
        if(preg_match("#(http|ftp).*?:\/\/#",$pattern)){
            $url_extract=parse_url($pattern);
            $pattern=$url_extract["host"];
        }

    }


    $ip=new IP();
    if( ($ztype=="src") OR ($ztype=="dst")){
        if(!$ip->isIPAddressOrRange($pattern)){return false;}
    }
    if( $ztype=="arp"){
        $pattern=str_replace("-", ":", $pattern);
        if(!$ip->IsvalidMAC($pattern)){return false;}
    }




    $pattern=$q->sqlite_escape_string2($pattern);
    $q->QUERY_SQL("UPDATE ecap_whitelist SET pattern='$pattern',enabled='$enabled',description='$description' WHERE ID=$ID");
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}

    return admin_tracks("Edited global whitelist $pattern");



}
function config_popup(){

    $t=time();
    $data[]="# These items allow the proxy to :";
    $data[]="# - Bypass credentials (LDAP/Active Directory)";
    $data[]="# - Bypass ACLs";
    $data[]="# - Bypass Web-Filtering rules";
    $data[]="# - Bypass the Antivirus for Proxy server or Any ICAP server";
    $data[]="#";
    $data[]="#";
    $data[]="# White-list based on domains";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/ecap_whitelist.dstdomain.conf");
    $data[]="";
    $data[]="# White-list based on domains using regular expressions ( REGEX )";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/ecap_whitelist.dstdom_regex.conf");
    $data[]="";
    $data[]="# White-list based destination networks";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/ecap_whitelist.dst.conf");
    $data[]="";
    $data[]="# White-list based source networks";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/ecap_whitelist.src.conf");
    $data[]="";


    echo "<textarea style='margin-top:5px;font-family:\"Courier New\",serif;
	font-weight:bold;width:98%;height:746px;border:5px solid #8E8E8E;
	overflow:auto;font-size:18px !important' id='text-$t'>".@implode("\n", $data)."</textarea>";
}


function eCapSwitchPerform(){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $ID=$_GET["switch-js"];
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM ecap_whitelist WHERE ID='$ID'");
    if($ligne["enabled"]==1){
        $q->QUERY_SQL("UPDATE ecap_whitelist SET enabled=0 WHERE ID=$ID");

    }else{
        $q->QUERY_SQL("UPDATE ecap_whitelist SET enabled=1 WHERE ID=$ID");
    }
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";}
}

function config_popup_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{config}", "$page?config-popup=yes");

}

function popup_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID"]);
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $title=$tpl->javascript_parse_text("{new_item}");
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT ztype,pattern FROM ecap_whitelist WHERE ID='$ID'");
        $ztype=$GLOBALS["GroupType"][$ligne["ztype"]];
        $pattern=$ligne["pattern"];
        $title="$ztype: $pattern";
    }

    return $tpl->js_dialog1($title, "$page?popup=yes&ID=$ID&function=$function");
}
function popup(){
    $jsafter="";
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID"]);
    $t=time();
    $buttonname="{add}";
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $title=$tpl->javascript_parse_text("{new_item}");
    $ligne["enabled"]=1;
    $close="";
    $idtemp="";
    $js="";

    if($ID>0){
        $buttonname="{apply}";
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM ecap_whitelist WHERE ID='$ID'");
        $ztype=$GLOBALS["GroupType"][$ligne["ztype"]];
        $pattern=$ligne["pattern"];
        $title="$ztype: $pattern";
    }


    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
    if($ID==0){
        $close="BootstrapDialog1.close()";
        $form[]=$tpl->field_array_hash($GLOBALS["GroupType"], "ztype", "{type}",null, true,"ChangeExplain$t()");
        $idtemp=$tpl->id_temp;
    }else{
        $form[]=$tpl->field_hidden("ztype", $ligne["ztype"]);
        $js="LoadAjaxSilent('div-description-$t','$page?GroupType={$ligne["ztype"]}');";
    }
    if($ID>0){
        $form[]=$tpl->field_text("pattern", "{pattern}", $ligne["pattern"],true);
    }else{
        $form[]=$tpl->field_textareacode("pattern", "{pattern}", null);
    }
    $form[]=$tpl->field_text("description", "{description}", $ligne["description"]);

    if(strlen($function)>5){
        $jsafter="$function()";
    }


    $html[]=$tpl->form_outside($title,@implode("\n",$form), ecapExplainThis($ligne["ztype"]),$buttonname,"$jsafter;$close");



    $html[]="<script>";
    $html[]="
function ChangeExplain$t(){
	
	if(!document.getElementById('$idtemp')){alert('$idtemp!!');return;}
	var GroupType=document.getElementById('$idtemp').value;
	if(GroupType.length==0){return;}
	LoadAjaxSilent('form-explain','$page?GroupType='+GroupType);
	
}";
    $html[]="$js";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function restart_js():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/proxy/ecap/install","squid.ecap.progress","squid.ecap.progress.log","progress-squid-ecap-restart");
}
function rules_search_block():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&table-final=yes");
    echo "</div>";
    return true;
}

function rules_list_search():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $type=$tpl->_ENGINE_parse_body("{type}");
    $description=$tpl->_ENGINE_parse_body("{description}");
    $items=$tpl->_ENGINE_parse_body("{items}");
    $date=$tpl->javascript_parse_text("{date}");
    $function=$_GET["function"];
    $add="Loadjs('$page?popup-js=yes&ID=0&function=$function');";
    $jsrestart=restart_js();

    $SquidDisableWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableWhiteLists"));

    $topbuttons[] = array($add, ico_plus, "{new_rule}");
    $topbuttons[] = array("Loadjs('$page?config-popup-js=yes')", ico_script, "{config}");

    /*
    if($SquidDisableWhiteLists==0) {
        $topbuttons[] = array("Loadjs('$page?SquidDisableWhiteLists=yes&function=$function')", ico_stop, "{disable_feature}");
    }else{
        $topbuttons[] = array("Loadjs('$page?SquidDisableWhiteLists=yes&function=$function')", ico_run, "{enable_feature}");
    }
*/
    $topbuttons[] = array($jsrestart, ico_save, "{apply}");


    $html[]="<table id='proxywbl-list-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$date</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$type</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$items</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$description</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    $sql="SELECT * FROM ecap_whitelist ORDER by zDate DESC";
    $search=$_GET["search"];
    if(strlen($search)>1) {
        $search = str_replace( "*", "%",$search);
        $sql = "SELECT * FROM ecap_whitelist WHERE (pattern LIKE '$search' OR description LIKE '$search') ORDER by zDate DESC";
    }

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);return false;}


    foreach ($results as $index=>$ligne) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $stylecolor="";
        $ligne["md5"]=md5(serialize($ligne));
        $ligne["ztype"]=$tpl->javascript_parse_text($GLOBALS["GroupType"][$ligne["ztype"]]);
        $ligne["description"]=$tpl->_ENGINE_parse_body($ligne["description"]);
        if($SquidDisableWhiteLists==1){
            $stylecolor=";color:#CCCCCC;";
        }

        $js="Loadjs('$page?popup-js=yes&ID={$ligne["ID"]}&function=$function')";
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?switch-js={$ligne["ID"]}')");
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js={$ligne["ID"]}&md5={$ligne["md5"]}')");
        $html[]="<tr class='$TRCLASS' id='{$ligne["md5"]}'>";
        $html[]="<td style='width:1%$stylecolor' id='$index' nowrap>{$ligne["zDate"]}</td>";
        $html[]="<td style='width:1%$stylecolor' nowrap><a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold;$stylecolor'>{$ligne["ztype"]}</span></td>";
        $html[]="<td style='width:1%$stylecolor' nowrap><a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold;$stylecolor'>{$ligne["pattern"]}</span></td>";
        $html[]="<td style='width:80%$stylecolor' nowrap>{$ligne["description"]}</td>";
        $html[]="<td class='center' style='width:1%'>$enabled</td>";
        $html[]="<td class='center' style='width:1%'>$delete</td>";
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
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#proxywbl-list-rules').footable( { \"filtering\": {";
    $html[]="\"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\":";
    $html[]="{$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";




    $TINY_ARRAY["TITLE"]="{av_exclusions}";
    $TINY_ARRAY["ICO"]="fa fa-thumbs-up";
    $TINY_ARRAY["EXPL"]="{avexlusion_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="$jstiny</script>";
    echo @implode("\n", $html);
    return true;
}
function ecapExplainThis($tata=null):string{
    if($tata<>null){$_GET["GroupType"]=$tata;}
    $GroupType=$_GET["GroupType"];
    $explain="";
    if($GroupType=="src"){$explain="{acl_src_text}";}
    if($GroupType=="dst"){$explain="{acl_dst_text}";}
    if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
    if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
    if($GroupType=="maxconn"){$explain="{squid_aclmax_connections_explain}";}
    if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
    if($GroupType=="ext_user"){$explain="{acl_squid_ext_user_explain}";}
    if($GroupType=="req_mime_type"){$explain="{req_mime_type_explain}";}
    if($GroupType=="rep_mime_type"){$explain="{rep_mime_type_explain}";}
    if($GroupType=="referer_regex"){$explain="{acl_squid_referer_regex_explain}";}
    if($GroupType=="srcdomain"){$explain="{acl_squid_srcdomain_explain}";}
    if($GroupType=="url_regex_extensions"){$explain="{url_regex_extensions_explain}";}
    if($GroupType=="max_user_ip"){$explain="<b>{acl_max_user_ip_title}</b><br>{acl_max_user_ip_text}";}
   // if($GroupType=="quota_time"){$explain="{acl_quota_time_text}";}
   // if($GroupType=="quota_size"){$explain="{acl_quota_size_text}";}
    if($GroupType=="ssl_sni"){$explain="{acl_ssl_sni_text}";}
    if($GroupType=="myportname"){$explain="{acl_myportname_text}";}
    if($GroupType=="rep_header_filename"){$explain="{rep_header_filename_explain}";}
    if($GroupType=="browser"){$explain="{acl_squid_browser_explain}";}

    if($tata<>null){return $explain;}
    if($explain==null){return "";}
    $html="<div class='alert alert-info'>$explain</div>";
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return "";

}