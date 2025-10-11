<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.modsectools.inc');
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsWebSecurity){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["zoom-ip-js"])){zoom_ip_js();exit;}
if(isset($_GET["zoom-ip-popup"])){zoom_ip_popup();exit;}
if(isset($_GET["zoom-ip-popup2"])){zoom_ip_popup2();exit;}
if(isset($_GET["zoom-ip-info"])){zoom_ip_info();exit;}
if(isset($_GET["zoom-ip-fw"])){zoom_ip_fw();exit;}
if(isset($_GET["zoom-ip-reputation"])){zoom_ip_reputation();exit;}
if(isset($_GET["filltable-js"])){filltable_js();exit;}

if(isset($_GET["filter-src-list-search"])){filter_src_list2();exit;}
if(isset($_GET["jstiny"])){jstiny();exit;}
if(isset($_GET["filter-rules"])){filter_rule_js();exit;}
if(isset($_GET["filter-rules-popup"])){filter_rule_popup();exit;}
if(isset($_GET["filter-rules-list"])){filter_rule_list();exit;}
if(isset($_GET["filter-rules-selected"])){filter_rule_selected();exit;}
if(isset($_GET["filter-rules-reset"])){filter_rule_reset();exit;}

if(isset($_GET["filter-level"])){filter_level_js();exit;}
if(isset($_GET["filter-level-popup"])){filter_level_popup();exit;}
if(isset($_GET["filter-level-selected"])){filter_level_selected();exit;}

if(isset($_GET["filter-date"])){filter_date_js();exit;}
if(isset($_GET["filter-date-popup"])){filter_date_popup();exit;}
if(isset($_GET["filter-date-selected"])){filter_date_selected();exit;}

if(isset($_GET["filter-src"])){filter_src_js();exit;}
if(isset($_GET["filter-src-popup"])){filter_src_popup();exit;}
if(isset($_GET["filter-src-list"])){filter_src_list();exit;}
if(isset($_GET["filter-src-selected"])){filter_src_selected();exit;}
if(isset($_GET["filter-src-reset"])){filter_src_reset();exit;}

if(isset($_GET["filter-dst"])){filter_dst_js();exit;}
if(isset($_GET["filter-dst-popup"])){filter_dst_popup();exit;}
if(isset($_GET["filter-dst-list"])){filter_dst_list();exit;}
if(isset($_GET["filter-dst-selected"])){filter_dst_selected();exit;}
if(isset($_GET["filter-dst-reset"])){filter_dst_reset();exit;}


if(isset($_GET["global-white"])){whitelist_global();exit;}
if(isset($_GET["global-unwhite"])){unwhite_global();exit;}
if(isset($_GET["serviceid-white"])){whitelist_serviceid();exit;}
if(isset($_GET["serviceid-unwhite"])){unwhite_serviceid();exit;}
if(isset($_GET["path-white"])){whitelist_path();exit;}
if(isset($_GET["path-unwhite"])){unwhite_path();exit;}




if(isset($_GET["search"])){search();exit;}
if(isset($_GET["graphline-ruleid"])){graphline_ruleid();exit;}
if(isset($_GET["graphline-ruleidsite"])){graphline_ruleidsite();exit;}
if(isset($_GET["graphline-ipaddr"])){graphline_ipaddr();exit;}


if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-tab"])){rule_tab();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-popup2"])){rule_popup2();exit;}
if(isset($_GET["rule-white"])){rule_sitewhite();exit;}
if(isset($_GET["rule-white2"])){rule_sitewhite2();exit;}
if(isset($_GET["rule-clients"])){rule_clients();exit;}


if(isset($_GET["wp-in-firewall"])){wp_in_firewall();exit;}
if(isset($_GET["wp-out-firewall"])){wp_out_firewall();exit;}

if(isset($_GET["zoom-report-js"])){zoom_report_js();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
if(isset($_GET["zoom-tab"])){zoom_tab();exit;}
if(isset($_GET["active"])){active_js();exit;}
if(isset($_GET["active-popup"])){active_popup();exit;}
if(isset($_GET["active-tabs"])){active_tabs();exit;}
if(isset($_GET["active-list"])){active_list();exit;}
if(isset($_POST["ruleid"])){active_save();exit;}
if(isset($_GET["active-remove"])){active_remove();exit;}
if(isset($_GET["delete-by-rule"])){delete_by_rule_js();exit;}
if(isset($_POST["delbyrule"])){delete_by_rule_perform();exit;}
if(isset($_GET["clean-table"])){clean_table();exit;}
if(isset($_POST["clean-table"])){clean_table_perform();exit;}
if(isset($_GET["zoom-report"])){zoom_report();exit;}

page();

function unwhite_global():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $unique_id=intval($_GET["global-unwhite"]);
    $modtools=new modesctools();
    if(!$modtools->unwhite_global($unique_id)){
        return $tpl->js_mysql_alert($modtools->mysql_error);
    }
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    echo "LoadAjax('rulestatus-$unique_id','$page?rule-popup2=$unique_id&function=$function');";
    return true;
}
function unwhite_serviceid():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $unique_id=intval($_GET["serviceid-unwhite"]);
    $serviceid=intval($_GET["serviceid"]);
    $eventid=intval($_GET["eventid"]);
    $modtools=new modesctools();
    if(!$modtools->unwhite_serviceid($unique_id,$serviceid)){
        return $tpl->js_mysql_alert($modtools->mysql_error);
    }
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    echo "LoadAjax('rulewhite-$eventid','$page?rule-white2=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid')";
    return true;
}
function zoom_ip_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ip=$_GET["zoom-ip-js"];
    return $tpl->js_dialog12("{info}:$ip", "$page?zoom-ip-popup=$ip",950);
}

function whitelist_global():bool{
    $tpl=new template_admin();
    $unique_id=intval($_GET["global-white"]);
    $modtools=new modesctools();
    if(!$modtools->whitelist_global_rule($unique_id)){
        return $tpl->js_mysql_alert($modtools->mysql_error);
    }
    $page=CurrentPageName();
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    echo "LoadAjax('rulestatus-$unique_id','$page?rule-popup2=$unique_id&function=$function');";
    return true;
}

function rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $unique_id=$_GET["rule-js"];
    $function=$_GET["function"];
    $eventid=intval($_GET["eventid"]);
    $serviceid=intval($_GET["serviceid"]);
    return $tpl->js_dialog1("{rule}: #$unique_id","$page?rule-tab=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid");
}
function filter_rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog2("{filter}: {rules}","$page?filter-rules-popup=yes&function=$function");
}
function filter_src_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog2("{filter}: {src}","$page?filter-src-popup=yes&function=$function");
}
function filter_dst_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog2("{filter}: {webservice}","$page?filter-dst-popup=yes&function=$function");
}
function filter_level_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog2("{filter}: {level}","$page?filter-level-popup=yes&function=$function");
}
function filter_date_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog2("{filter}: {date}","$page?filter-date-popup=yes&function=$function");
}
function filter_level_selected():bool{
    $function=$_GET["function"];
    $_SESSION["NGINX_AUDIT_LEVEL"]=$_GET["filter-level-selected"];
    echo "$function();\n";
    echo "dialogInstance2.close();";
    return true;
}
function filter_date_selected():bool{
    $function=$_GET["function"];
    $_SESSION["NGINX_AUDIT_DATE"]=$_GET["filter-date-selected"];
    echo "$function();\n";
    echo "dialogInstance2.close();";
    return true;
}
function filter_rule_popup():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    echo "<div id='filter-by-rules'></div>";
    echo "<script>LoadAjax('filter-by-rules','$page?filter-rules-list=yes&function=$function');</script>";
    return true;
}
function filter_src_popup():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    echo "<div id='filter-by-src'></div>";
    echo "<script>LoadAjax('filter-by-src','$page?filter-src-list=yes&function=$function');</script>";
    return true;
}
function filter_dst_popup():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    echo "<div id='filter-by-dst'></div>";
    echo "<script>LoadAjax('filter-by-dst','$page?filter-dst-list=yes&function=$function');</script>";
    return true;
}
function filter_date_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $page=CurrentPageName();
    if(!isset($_SESSION["NGINX_AUDIT_DATE"])){
        $curlevel=0;
    }else{
        $curlevel=$_SESSION["NGINX_AUDIT_DATE"];
    }
    $Date[0]="{all}";
    $Date[1] = "{today}";
    $Date[2] = "{yesterday}";
    $Date[3] = "{this_week}";
    $Date[4] = "{this_month}";
    $Date[5] = "{this_hour}";

    $TRCLASS=null;
    $html[]="<table id='table-mskdvlsdv' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    foreach ($Date as $index=>$title){
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $lb="label-default";
        if($curlevel==$index){
            $lb="label-primary";
        }

        $val=$tpl->td_href("<span class='label $lb'>$title</span>",null,"Loadjs('$page?filter-date-selected=$index&function=$function')");

        $html[] = "<tr class='$TRCLASS' id=''>";
        $html[] = "<td style='width:1%' nowrap>$val</td>";
        $html[] = "</tr>";
    }
    $html[] = "</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function filter_level_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $page=CurrentPageName();
    if(!isset($_SESSION["NGINX_AUDIT_LEVEL"])){
        $curlevel=-1;
    }else{
        $curlevel=$_SESSION["NGINX_AUDIT_LEVEL"];
    }
    $severity_icon[-1]="{all}";
    $severity_icon[0]="Info";
    $severity_icon[2]="{critic}";
    $severity_icon[3]="{error}";
    $severity_icon[4]="{warning}";
    $severity_icon[5]="{notice}";
    $TRCLASS=null;
    $html[]="<table id='table-mskdvlsdv' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
   foreach ($severity_icon as $index=>$title){
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $lb="label-default";
        if($curlevel==$index){
            $lb="label-primary";
        }

        $val=$tpl->td_href("<span class='label $lb'>$title</span>",null,"Loadjs('$page?filter-level-selected=$index&function=$function')");

        $html[] = "<tr class='$TRCLASS' id=''>";
        $html[] = "<td style='width:1%' nowrap>$val</td>";
        $html[] = "</tr>";
    }
    $html[] = "</table>";
   echo $tpl->_ENGINE_parse_body($html);
   return true;
}
function filter_dst_list():bool{
    $t=time();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $function=$_GET["function"];
    $page=CurrentPageName();
    $ANDT="";
    $xtime=search_filters_date();
    if (strlen($xtime)>5){
        $xtime=str_replace("AND ","",$xtime);
        $ANDT="WHERE $xtime";
    }

    $sql="SELECT count(*) as tcount, serviceid from modsecurity_audit $ANDT GROUP by serviceid ORDER BY tcount DESC";

    $TRCLASS=null;
    $results=$q->QUERY_SQL($sql);

    $topbuttons[]=array("Loadjs('$page?filter-dst-reset=yes&function=$function');", ico_trash,"{reset}");
    $html[]=$tpl->_ENGINE_parse_body( $tpl->th_buttons($topbuttons));
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{webservice}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }

    while($ligne=@pg_fetch_assoc($results)){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id=''>";

        $enabled=0;

        $tcount=$ligne["tcount"];
        $serviceid=$ligne["serviceid"];
        $servicename=get_servicename($serviceid);

        $html[]="<td style='width:50%' nowrap><strong>$servicename</strong> (".$tpl->FormatNumber($tcount)." {threats})</td>";

        if(isset($_SESSION["NGINX_AUDIT_DST"][$serviceid])){
            $enabled=1;
        }
        $enable=$tpl->icon_check($enabled,"Loadjs('$page?filter-dst-selected=$serviceid&function=$function')");
        $html[]="<td style='width:1%' nowrap>$enable</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";

    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function filter_src_list():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $html[]="<div style='margin-top:10px'>";
    $urltoadd="&filter-src-list-search=yes&function2=$function";
    $html[]=$tpl->search_block($page,null,null,null,$urltoadd);
    $html[]="</div>";
    echo @implode("\n",$html);
    return true;
}
function filter_src_list2():bool{
    $t=time();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $function=$_GET["function2"];
    $page=CurrentPageName();
    $ANDB="";
    $WHERE="";
    $ANDT="";
    $xtime=search_filters_date();
    if (strlen($xtime)>5){
        $WHERE="WHERE";
        $xtime=str_replace("AND ","",$xtime);
        $ANDT="$WHERE $xtime";
        $WHERE="AND";
    }
    $ANDD=search_filters_dst();
    if (strlen($ANDD)>5){
        if($WHERE==""){
            $WHERE="WHERE";
        }
        $xtime=str_replace("AND ","",$ANDD);
        $ANDD="$WHERE $xtime";
        $WHERE="AND";
    }
    $ipclass=new IP();
    $search=$_GET["search"];
    if($search<>null){
        if($WHERE==""){$WHERE="WHERE";}
        if(strpos($search,"/")>0) {
            $ANDB = "$WHERE '$search' >> ipaddr";
        }
        if($ipclass->IsValid($search)){
            $ANDB = "$WHERE ipaddr = '$search'";
        }

        if(strpos($search,"*")>0){
            $search=str_replace("*","%",$search);
            $ANDB = "$WHERE ipaddr::text LIKE '$search'";
        }

        if($ANDB==""){
            $tb=explode(".",$search);

            if(count($tb)==1){
                $ANDB = "$WHERE '$tb[0].0.0.0/8' >> ipaddr";
            }

            if(count($tb)==2){
                $ANDB = "$WHERE '$tb[0].$tb[1].0.0/16' >> ipaddr";
            }
            if(count($tb)==3){
                $ANDB = "$WHERE '$tb[0].$tb[1].$tb[2].0/24' >> ipaddr";
            }

        }
        
    }

    $sql="SELECT count(*) as tcount, ipaddr from modsecurity_audit $ANDT $ANDD $ANDB GROUP by ipaddr ORDER BY tcount DESC";
    $modtools=new modesctools();
    $TRCLASS=null;
    $results=$q->QUERY_SQL($sql);

    $topbuttons[]=array("Loadjs('$page?filter-src-reset=yes&function=$function');", ico_trash,"{reset}");
    $html[]=$tpl->_ENGINE_parse_body( $tpl->th_buttons($topbuttons));
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' ></th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{src}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error."<hr>".$sql);
    }

    $mod=new modesctools();
    //($_SESSION["NGINX_AUDIT_RULES"]
    while($ligne=@pg_fetch_assoc($results)){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id=''>";

        $enabled=0;

        $tcount=$ligne["tcount"];
        $ipaddr=$ligne["ipaddr"];
        $mod->hostinfo($ipaddr);
        $hostname=$mod->hostname;
        $flag=$mod->flag;
        $html[]="<td style='width:1%' nowrap><img src='img/$flag' alt=''></td>";
        $html[]="<td style='width:50%' nowrap><strong>$ipaddr - $hostname</strong> (".$tpl->FormatNumber($tcount)." {threats})</td>";

        if(isset($_SESSION["NGINX_AUDIT_SRC"][$ipaddr])){
            $enabled=1;
        }
        $enable=$tpl->icon_check($enabled,"Loadjs('$page?filter-src-selected=$ipaddr&function=$function')");
        $html[]="<td style='width:1%' nowrap>$enable</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";

    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function filter_rule_list():bool{
    $t=time();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $function=$_GET["function"];
    $page=CurrentPageName();

    $ANDT="";
    $xtime=search_filters_date();
    if (strlen($xtime)>5){
        $xtime=str_replace("AND ","",$xtime);
        if(strlen($xtime)>1) {
            $ANDT = "WHERE $xtime";
        }
    }
    $ANDD=search_filters_dst();
    if($ANDT==null){
        $xtime=str_replace("AND ","",$ANDD);
        if(strlen($xtime)>1) {
            $ANDD = "WHERE $xtime";
        }
    }

    $sql="SELECT count(*) as tcount, ruleid,msgid from modsecurity_audit $ANDT $ANDD GROUP by ruleid,msgid ORDER BY tcount DESC";
    $modtools=new modesctools();
    $TRCLASS=null;
    $results=$q->QUERY_SQL($sql);

    $topbuttons[]=array("Loadjs('$page?filter-rules-reset=yes&function=$function');", ico_trash,"{reset}");
    $html[]=$tpl->_ENGINE_parse_body( $tpl->th_buttons($topbuttons));
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{rule}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{threats}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    if(!$q->ok OR !$results){
        echo $tpl->div_error($q->mysql_error."<hr>$sql");
        return true;
    }

    //($_SESSION["NGINX_AUDIT_RULES"]
    while($ligne=@pg_fetch_assoc($results)){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ruleid=$ligne["ruleid"];
        $desc=trim($modtools->modsecurity_titles($ruleid));
        $html[]="<tr class='$TRCLASS' id=''>";
        $html[]="<td style='width:1%' nowrap>$ruleid</td>";
        $enabled=0;


        $tcount=$ligne["tcount"];
        $msgid=$ligne["msgid"];
        $ligne2=$q->mysqli_fetch_array("SELECT * FROM modsecurity_messages WHERE id=$msgid");
        $explain=$ligne2["explain"];
        $html[]="<td nowrap style='width:1%;text-align:right'>".$tpl->FormatNumber($tcount)."</td>";
        $html[]="<td style='width:50%' nowrap><strong>$desc $explain</td>";

        if(isset($_SESSION["NGINX_AUDIT_RULES"][$ruleid])){
            $enabled=1;
        }
        $enable=$tpl->icon_check($enabled,"Loadjs('$page?filter-rules-selected=$ruleid&function=$function')");
        $html[]="<td style='width:1%' nowrap>$enable</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";

    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function filter_rule_selected():bool{
    $function=$_GET["function"];
    $ruleid=intval($_GET["filter-rules-selected"]);
    if(isset($_SESSION["NGINX_AUDIT_RULES"][$ruleid])){
        unset($_SESSION["NGINX_AUDIT_RULES"][$ruleid]);
    }else{
        $_SESSION["NGINX_AUDIT_RULES"][$ruleid]=true;
    }

    echo "$function();";
    return true;
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
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
function filter_rule_reset():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    unset($_SESSION["NGINX_AUDIT_RULES"]);
    echo "$function();\n";
    echo "LoadAjax('filter-by-rules','$page?filter-rules-list=yes&function=$function');";
    return  true;
}
function filter_src_selected():bool{
    $function=$_GET["function"];
    $ruleid=$_GET["filter-src-selected"];
    if(isset($_SESSION["NGINX_AUDIT_SRC"][$ruleid])){
        unset($_SESSION["NGINX_AUDIT_SRC"][$ruleid]);
    }else{
        $_SESSION["NGINX_AUDIT_SRC"][$ruleid]=true;
    }

    echo "$function();";
    return true;
}
function filter_dst_selected():bool{
    $function=$_GET["function"];
    $ruleid=$_GET["filter-dst-selected"];
    if(isset($_SESSION["NGINX_AUDIT_DST"][$ruleid])){
        unset($_SESSION["NGINX_AUDIT_DST"][$ruleid]);
    }else{
        $_SESSION["NGINX_AUDIT_DST"][$ruleid]=true;
    }

    echo "$function();";
    return true;
}
function filter_src_reset():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    unset($_SESSION["NGINX_AUDIT_SRC"]);
    echo "LoadAjax('filter-by-src','$page?filter-src-list=yes&function=$function');\n";
    echo "$function();";
    return true;
}
function filter_dst_reset():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    unset($_SESSION["NGINX_AUDIT_DST"]);
    echo "LoadAjax('filter-by-dst','$page?filter-dst-list=yes&function=$function');\n";
    echo "$function();";
    return true;
}

function zoom_report_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $reportid=intval($_GET["zoom-report-js"]);
    $function=$_GET["function"];
    return $tpl->js_dialog1("{report}: #$reportid","$page?zoom-report=$reportid&function=$function");

}
function zoom_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $unique_id=$_GET["zoom-js"];
    $function=$_GET["function"];
    $reportid=intval($_GET["reportid"]);
    return $tpl->js_dialog1("{event}: #$unique_id","$page?zoom-tab=$unique_id&reportid=$reportid&function=$function");
}
function rule_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $unique_id=intval($_GET["rule-tab"]);
    $serviceid=intval($_GET["serviceid"]);
    $eventid=intval($_GET["eventid"]);
    $array["{rule} #$unique_id"]="$page?rule-popup=$unique_id&function=$function";
    if($serviceid>0){
        $modtools=new modesctools();
        $servicename=$modtools->get_servicename($serviceid);
        $array[$servicename. " {whitelist}"]="$page?rule-white=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid";

    }
    $array["{clients}"]="$page?rule-clients=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid";

    echo $tpl->tabs_default($array);
    return true;
}

function rule_clients():bool{
    $unique_id=intval($_GET["rule-clients"]);
    $function=$_GET["function"];
    $q=new postgres_sql();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $sql="SELECT count(*) as tcount, ipaddr from modsecurity_audit WHERE ruleid=$unique_id  GROUP by ipaddr ORDER BY tcount DESC";

    $TRCLASS=null;
    $results=$q->QUERY_SQL($sql);

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' ></th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{src}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{filter}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }

    $mod=new modesctools();

    while($ligne=@pg_fetch_assoc($results)){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id=''>";

        $enabled=0;

        $tcount=$ligne["tcount"];
        $ipaddr=$ligne["ipaddr"];
        $mod->hostinfo($ipaddr);
        $hostname=$mod->hostname;
        $flag=$mod->flag;
        $html[]="<td style='width:1%' nowrap><img src='img/$flag' alt=''></td>";
        $html[]="<td style='width:50%' nowrap><strong>$ipaddr - $hostname</strong> (".$tpl->FormatNumber($tcount)." {threats})</td>";

        if(isset($_SESSION["NGINX_AUDIT_SRC"][$ipaddr])){
            $enabled=1;
        }
        $enable=$tpl->icon_check($enabled,"Loadjs('$page?filter-src-selected=$ipaddr&function=$function')");
        $html[]="<td style='width:1%' nowrap>$enable</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";

    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function rule_popup():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $unique_id=intval($_GET["rule-popup"]);

    echo "<div id='rulestatus-$unique_id'><div>
        <script>LoadAjax('rulestatus-$unique_id','$page?rule-popup2=$unique_id&function=$function')</script>";
    return true;
}
function rule_sitewhite():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $unique_id=intval($_GET["rule-white"]);
    $serviceid=intval($_GET["serviceid"]);
    $eventid=intval($_GET["eventid"]);
    echo "<div id='rulewhite-$eventid'><div>
        <script>LoadAjax('rulewhite-$eventid','$page?rule-white2=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid')</script>";
    return true;
}
function rule_sitewhite2():bool{
    $tpl=new template_admin();
    $modtools=new modesctools();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $unique_id=intval($_GET["rule-white2"]);
    $serviceid=intval($_GET["serviceid"]);
    $eventid=intval($_GET["eventid"]);
    $servicename=$modtools->get_servicename($serviceid);
    $fleche="&nbsp;<i class='".ico_arrow_right."'></i>&nbsp;";
    $tpl->table_form_field_text("{sitename}",$servicename."$fleche{rule} $unique_id",ico_earth);
    $Occurences=$modtools->CountOfOccurenceSite($unique_id,$serviceid);
    $tpl->table_form_field_text("{detected}",$tpl->FormatNumber($Occurences). "&nbsp;{times}",ico_shield);
    $isWhite=$modtools->rule_is_global_white($unique_id);
    if($isWhite){
        $tpl->table_form_field_text("{status}","{disable_for_all_sites}",ico_disabled);
    }else{
        $isWhite=$modtools->rule_is_serviceid_white($unique_id,$serviceid);


        if(!$isWhite) {
            $array["BUTTON"]["VALUE"] = "{active2}";
            $array["BUTTON"]["LABEL"] = "{disable_for_this_site}";
            $array["BUTTON"]["JS"] = "Loadjs('$page?serviceid-white=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid')";
            $tpl->table_form_field_text("{status}", $array, ico_check);
            $uri=$modtools->PathFromEventID($eventid);
            if($uri<>null){
                $path=base64_encode($uri);
                $path_html="<span style='text-transform:initial'>$uri</span>&nbsp;";
                $isWhite=$modtools->rule_is_path_white($unique_id,$serviceid,$uri);
                if(!$isWhite) {
                    $array["BUTTON"]["VALUE"] = "$path_html ({active2})";
                    $array["BUTTON"]["LABEL"] = "{disable_for_this_path}";
                    $array["BUTTON"]["JS"] = "Loadjs('$page?path-white=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid&path=$path')";
                }else{
                    $array["BUTTON"]["VALUE"] = "$path_html ({inactive})";
                    $array["BUTTON"]["LABEL"] = "{enable_for_this_path}";
                    $array["BUTTON"]["BT_CLASS"]="btn-default";
                    $array["BUTTON"]["JS"] = "Loadjs('$page?path-unwhite=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid&path=$path')";
                }
                $tpl->table_form_field_text("{status}", $array, ico_check);
            }
        }else{
            $array["BUTTON"]["VALUE"] = "{inactive}";
            $array["BUTTON"]["LABEL"] = "{activate}";
            $array["BUTTON"]["BT_CLASS"]="btn-default";
            $array["BUTTON"]["JS"] = "Loadjs('$page?serviceid-unwhite=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid')";
            $tpl->table_form_field_text("{status}", $array, ico_check);
        }
    }
    $html[]=$tpl->table_form_compile();
    $html[]="<div id='graphline-ruleidsite-$unique_id'></div>";
    $html[]="<script>";
    $html[]="Loadjs('$page?graphline-ruleidsite=$unique_id&serviceid=$serviceid&id=graphline-ruleidsite-$unique_id');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function whitelist_serviceid():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $unique_id=intval($_GET["serviceid-white"]);
    $eventid=intval($_GET["eventid"]);
    $serviceid=intval($_GET["serviceid"]);
    $function=$_GET["function"];
    $modtools=new modesctools();
    if(!$modtools->whitelist_serviceid_rule($unique_id,$serviceid)){
        return $tpl->js_mysql_alert($modtools->mysql_error);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('rulewhite-$eventid','$page?rule-white2=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid')";
    return true;
}
function whitelist_path():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $unique_id=intval($_GET["path-white"]);
    $eventid=intval($_GET["eventid"]);
    $serviceid=intval($_GET["serviceid"]);
    $function=$_GET["function"];
    $path=base64_decode($_GET["path"]);
    $modtools=new modesctools();
    if(!$modtools->whitelist_path_rule($unique_id,$serviceid,$path)){
        return $tpl->js_mysql_alert($modtools->mysql_error);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('rulewhite-$eventid','$page?rule-white2=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid')";
    return true;

}
function unwhite_path():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $unique_id=intval($_GET["path-unwhite"]);
    $eventid=intval($_GET["eventid"]);
    $serviceid=intval($_GET["serviceid"]);
    $function=$_GET["function"];
    $path=base64_decode($_GET["path"]);
    $modtools=new modesctools();
    if(!$modtools->unwhite_path($unique_id,$serviceid,$path)){
        return $tpl->js_mysql_alert($modtools->mysql_error);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('rulewhite-$eventid','$page?rule-white2=$unique_id&serviceid=$serviceid&function=$function&eventid=$eventid')";
    return true;
}

function rule_popup2():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $unique_id=intval($_GET["rule-popup2"]);
    $function=$_GET["function"];
    if($unique_id==0){
        echo $tpl->div_error("Oups! id =0 ??");
        return false;
    }
    $modtools=new modesctools();
    $title=$modtools->modsecurity_titles($unique_id);
    $Occurences=$modtools->CountOfOccurence($unique_id);
    $tpl->table_form_field_text($unique_id,$title,ico_infoi);
    $tpl->table_form_field_text("{detected}",$tpl->FormatNumber($Occurences). "&nbsp;{times}",ico_shield);

    $isWhite=$modtools->rule_is_global_white($unique_id);
    if(!$isWhite){
        $array["BUTTON"]["VALUE"]="{active2}";
        $array["BUTTON"]["LABEL"]="{disable_for_all_sites}";
        $array["BUTTON"]["JS"]="Loadjs('$page?global-white=$unique_id&function=$function')";
        $tpl->table_form_field_text("{status}",$array,ico_check);
    }else{
        $array["BUTTON"]["VALUE"]="{inactive}";
        $array["BUTTON"]["LABEL"]="{enable_for_all_sites}";
        $array["BUTTON"]["JS"]="Loadjs('$page?global-unwhite=$unique_id&function=$function')";
        $array["BUTTON"]["BT_CLASS"]="btn-default";
        $tpl->table_form_field_text("{status}",$array,ico_disabled);
    }


    $html[]=$tpl->table_form_compile();
    $html[]="<div id='graphline-ruleid-$unique_id'></div>";
    $html[]="<script>";
    $html[]="Loadjs('$page?graphline-ruleid=$unique_id&id=graphline-ruleid-$unique_id');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function graphline_ruleidsite():bool{
    $ruleid=$_GET["graphline-ruleidsite"];
    $serviceid=intval($_GET["serviceid"]);
    $q=new postgres_sql();
    $sql="SELECT to_char(date_trunc('hour', created), 'MM-DD HH24') AS hour_formatted,
    COUNT(*) AS event_count FROM modsecurity_audit WHERE ruleid=$ruleid and serviceid=$serviceid GROUP BY 1 ORDER BY 1;";
    $ydata=array();
    $xdata=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $xdata[]=$ligne["hour_formatted"];
        $ydata[]=$ligne["event_count"];
    }
    $modsec=new modesctools();
    $title="{threats} {rule} #$ruleid - ".$modsec->get_servicename($serviceid);
    $timetext="{hour}";
    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{detections}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{hits}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{threats}"=>$ydata);
    echo $highcharts->BuildChart();
    return true;

}

function graphline_ipaddr():bool{
    $divid=$_GET["id"];
    $EnableModSecurityIngix = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));

    if($EnableModSecurityIngix==0){
        return false;
    }

    $ipaddr=$_GET["graphline-ipaddr"];
    $q=new postgres_sql();
    $sql="SELECT to_char(date_trunc('hour', created), 'MM-DD HH24') AS hour_formatted,
    COUNT(*) AS event_count FROM modsecurity_audit WHERE ipaddr='$ipaddr' GROUP BY 1 ORDER BY 1;";
    $ydata=array();
    $xdata=array();
    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        $tpl=new template_admin();
        $tpl->js_mysql_alert($q->mysql_error);
        return false;
    }

    while($ligne=@pg_fetch_assoc($results)){
        $xdata[]=$ligne["hour_formatted"];
        $ydata[]=$ligne["event_count"];
    }

    if(count($xdata)==0){
        return false;
    }


    if(count($xdata)==1){
        $tpl=new template_admin();
        $title="{threats} {ipaddr} $ipaddr ".$xdata[0] . " ". $ydata[0]." {events}";
        $html=$tpl->div_warning("<strong>$title</strong>");
        $enc=base64_encode($tpl->_ENGINE_parse_body($html));
        echo "document.getElementById('$divid').innerHTML=base64_decode('$enc');";
        return true;
    }

    $title="{threats} {ipaddr} $ipaddr ({by_hour})";
    $timetext="{hour}";
    $highcharts=new highcharts();
    $highcharts->container=$divid;
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{detections}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{hits}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{threats}"=>$ydata);
    echo $highcharts->BuildChart();
    return true;

}
function graphline_ruleid():bool{
    $ruleid=$_GET["graphline-ruleid"];

    $q=new postgres_sql();
    $sql="SELECT to_char(date_trunc('hour', created), 'MM-DD HH24') AS hour_formatted,
    COUNT(*) AS event_count FROM modsecurity_audit WHERE ruleid=$ruleid GROUP BY 1 ORDER BY 1;";
    $ydata=array();
    $xdata=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $xdata[]=$ligne["hour_formatted"];
        $ydata[]=$ligne["event_count"];
    }

    $title="{threats} {rule} #$ruleid";
    $timetext="{hour}";
    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{detections}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{hits}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{threats}"=>$ydata);
    echo $highcharts->BuildChart();
    return true;
}


function zoom_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $unique_id=intval($_GET["zoom-tab"]);
    $reportid=intval($_GET["reportid"]);
    if($reportid==0){
        $_GET["zoom-popup"]=$unique_id;
        return zoom_popup();
    }
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT report FROM modsecurity_reports where id=$reportid");
    if(strlen(strval($ligne["report"]))<10){
        $_GET["zoom-popup"]=$unique_id;
        return zoom_popup();
    }

    $array["{event}"]="$page?zoom-popup=$unique_id&function=$function";
    $array["{report}"]="$page?zoom-report=$reportid&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}


function zoom_report():bool{
    $reportid=intval($_GET["zoom-report"]);
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT report FROM modsecurity_reports where id=$reportid");
    $report=base64_decode(strval($ligne["report"]));

    echo "<textarea id='report-$reportid'>$report</textarea>
<script>
    var textarea = document.getElementById('report-$reportid');
    textarea.style.marginTop=\"10px\";
    textarea.style.width = \"100%\";
    textarea.style.padding = \"10px\";
    textarea.style.fontSize = \"12px\";
    textarea.style.lineHeight = \"1.3\";
    textarea.style.borderRadius = \"8px\";
    textarea.style.border = \"1px solid #d1d1d1\";
    textarea.style.boxShadow = \"0 4px 6px rgba(0, 0, 0, 0.05)\";
    textarea.style.transition = \"border-color 0.3s, box-shadow 0.3s\";
    textarea.style.outline = \"none\";
    textarea.style.resize = \"vertical\";
    textarea.style.height = \"450px\";
    textarea.style.fontFamily=\"Courier\";

    // Add event listener for focus and blur to change styles
    textarea.addEventListener('focus', function() {
        textarea.style.borderColor = \"#005447\";
        textarea.style.boxShadow = \"0 4px 6px rgba(90, 158, 252, 0.2)\";
    });

    textarea.addEventListener('blur', function() {
        textarea.style.borderColor = \"#d1d1d1\";
        textarea.style.boxShadow = \"0 4px 6px rgba(0, 0, 0, 0.05)\";
    });

</script>

    ";


return true;
}

function delete_by_rule_js():bool{
    $tpl=new template_admin();
    $ruleid=intval($_GET["delete-by-rule"]);
    $function=$_GET["function"];
    return $tpl->js_confirm_delete("{all_records_from_this_rule} #$ruleid","delbyrule",$ruleid,"dialogInstance1.close();$function()");

}

function delete_by_rule_perform():bool{
    $ruleid=intval($_POST["delbyrule"]);

    $sql="SELECT modsecurity_events.unique_id,modsecurity_events.ruleid 
    FROM modsecurity_events WHERE modsecurity_events.ruleid=$ruleid ORDER BY modsecurity_events.unique_id DESC";
    $UNIQS=array();
    $q=new postgres_sql();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)) {
        $unique_id = $ligne["unique_id"];
        $UNIQS[] = $unique_id;
    }

    $q->QUERY_SQL("DELETE FROM modsecurity_events WHERE ruleid=$ruleid");
    foreach ($UNIQS as $uniq_id){
        $q->QUERY_SQL("DELETE FROM modsecurity_reports WHERE unique_id=$uniq_id");
    }
    admin_tracks(count($UNIQS)." events removed from Web application firewall threats list");
    return true;



}
function zoom_popup():bool{
    $tpl=new template_admin();
    $unique_id=intval($_GET["zoom-popup"]);
    $q=new postgres_sql();

    $ligne=$q->mysqli_fetch_array("SELECT * FROM modsecurity_audit WHERE id=$unique_id");
    $modtools=new modesctools();
    $severity_icon[0]="<span class='label label-danger'>{emergency}</span>";
    $severity_icon[1]="<span class='label label-danger'>{alert}</span>";
    $severity_icon[2]="<span class='label label-danger'>{critic}</span>";
    $severity_icon[3]="<span class='label label-danger'>{error}</span>";
    $severity_icon[4]="<span class='label label-warning'>{warning}</span>";
    $severity_icon[5]="<span class='label label-info'>{notice}</span>";
    $severity_icon[6]="<span class='label label'>Info</span>";

    $title=$modtools->modsecurity_titles($ligne["ruleid"])."&nbsp;&nbsp;{$severity_icon[$ligne["severity"]]}";

    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->div_explain($title."||".$modtools->modsecurity_explains($ligne["explainid"]));


    $ipaddr=$ligne["ipaddr"];
    $mainurl=$ligne["mainurl"];

    $tpl->table_form_field_text("{date}",$ligne["created"],ico_clock);
    $tpl->table_form_field_text("{rule}",$ligne["ruleid"],ico_shield);
    $tpl->table_form_field_text("{client}",$ipaddr,ico_computer);
    $tpl->table_form_field_text("{web_service}",$modtools->get_servicename($ligne["serviceid"]),ico_earth);
    $tpl->table_form_field_text("{url}","<span style='text-transform:initial'>$mainurl</span>",ico_link);



    $html[]=$tpl->table_form_compile();
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function active_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleid=$_GET["active"];
    $www=$_GET["www"];
    $path=$_GET["path"];
    $function=$_GET["function"];
    $pathencode=urlencode($path);
    $www_encode=urlencode($www);
    $tpl->js_dialog1("#$ruleid - $www","$page?active-tabs=$ruleid&www=$www_encode&path=$pathencode&function=$function");
    return true;
}
function active_tabs():bool{
    $ruleid=$_GET["active-tabs"];
    $www=$_GET["www"];
    $path=$_GET["path"];
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $pathencode=urlencode($path);
    $www_encode=urlencode($www);
    $array["{disable}"]="$page?active-popup=$ruleid&www=$www_encode&path=$pathencode&function=$function";
    $array["{whitelists}"]="$page?active-list=$ruleid&www=$www_encode&path=$pathencode&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}
function active_popup():bool{
    $tpl=new template_admin();
    $ruleid=$_GET["active-popup"];
    $www=$_GET["www"];
    $path=$_GET["path"];
    $path=str_replace("//","/",$path);
    $function=$_GET["function"];
    $reconfigure_js= reconfigure_js();
    $html[]="<div style='margin-top:10px'>&nbsp;</div>";
    $tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_choose_websites("hostname","{hostname}",$www);
    $form[]=$tpl->field_text("path","{path}",$path);
	$html[]=$tpl->form_outside("#$ruleid", @implode("\n", $form),"","{disable} #$ruleid","$function();$reconfigure_js","AsWebSecurity",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function get_disabled_ruleid($ruleid,$serviceid,$path){
    $ss=array();

    if($serviceid>0) {
        $ss[] = "AND serviceid=$serviceid";
    }
    if($path<>null) {
        $ss[] = "AND spath='$path'";
    }

    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $sql="SELECT ID FROM `modsecurity_whitelist` WHERE wfrule=$ruleid ". @implode(" ",$ss);
    $ligne=$q->mysqli_fetch_array($sql);
    return intval($ligne["ID"]);

}
function active_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=$_POST["ruleid"];
    $serviceid=intval($_POST["hostname"]);
    $path=$_POST["path"];
    $ID=get_disabled_ruleid($ruleid,$serviceid,$path);
    if($ID>0){$tpl->post_error("{alreadyexists}");return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $q->QUERY_SQL("INSERT INTO modsecurity_whitelist (wfrule,serviceid,spath) VALUES($ruleid,$serviceid,'$path')");
    if(!$q->ok){$tpl->post_error("$q->mysql_error");return false;}
    admin_tracks("Add WAF rule $ruleid with service[$serviceid] and path=$path To global whitelist");

    return true;

}
function reconfigure_js($serviceid=0):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$serviceid')";

}

function active_remove():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["active-remove"]);
    $md=$_GET["md"];
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");

    $ligne=$q->QUERY_SQL("SELECT * FROM modsecurity_whitelist WHERE ID=$ID");
    $serviceid=$ligne["serviceid"];


    $q->QUERY_SQL("DELETE FROM modsecurity_whitelist WHERE ID=$ID");
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
    admin_tracks("DELETE WAF white-list Number $ID");
    echo "$('#$md').remove();\n$function();";
    echo reconfigure_js($serviceid);
    return true;
}

function active_list() {
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $TRCLASS=null;
    $ruleid=intval($_GET["active-list"]);
    $t=time();
    $function=$_GET["function"];
    $results=$q->QUERY_SQL("SELECT * FROM modsecurity_whitelist WHERE wfrule=$ruleid");
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" style='margin-top:10px' data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>WAF {active2}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{web_service}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{path}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $serviceid=$ligne["serviceid"];
        $path=$ligne["spath"];
        $md=md5(serialize($ligne));
        $www="{all_websites}";
        if($path==null){$path="{all_directories}";}
        $ID=$ligne["ID"];
        $WAF="<span class='label label-primary' id='$index'>{all}</span>";
        if($serviceid>0){
            $sql = "SELECT servicename FROM nginx_services WHERE ID=$serviceid";
            $WebService=$q->mysqli_fetch_array($sql);
            $www=$WebService["servicename"];
            $sockngix=new socksngix($ID);
            $EnableModSecurity=intval($sockngix->GET_INFO("EnableModSecurity"));
            if($EnableModSecurity==1){
                $WAF="<span class='label label-primary'>{active2}</span>";
            }else{
                $WAF="<span class='label label'>{inactive}</span>";
            }

        }
        $delete=$tpl->icon_delete("Loadjs('$page?active-remove=$ID&md=$md&function=$function')","AsWebSecurity");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>
$WAF</td>";
        $html[]="<td style='width:50%' nowrap>$www</td>";
        $html[]="<td style='width:50%' nowrap>$path</td>";
        $html[]="<td style='width:1%' nowrap>$delete</td>";
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
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": false },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);


}

function page(){
    $tpl=new template_admin();

    $html=$tpl->page_header("WAF Audit",ico_audit, "{search}",null,
        "threats-waf","progress-firehol-restart",true,"waf-threats-table");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Web Application firewall Audi",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}
function clean_table():bool{
    $tpl=new template_admin();
    $id=$_GET["function"];
    return $tpl->js_confirm_execute("{empty_waf_explain}","clean-table", "yes","$id()");
}

function clean_table_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new postgres_sql();
    $q->QUERY_SQL("TRUNCATE TABLE modsecurity_events");
    $q->QUERY_SQL("TRUNCATE TABLE modsecurity_reports");
    $q->QUERY_SQL("TRUNCATE TABLE modsecurity_audit");
    return admin_tracks("Remove all Web Application Firewall data");

}

function search_filters_rules():string{
    $t=array();
    $ff=array();
    if(isset($_SESSION["NGINX_AUDIT_RULES"])){
        if(count($_SESSION["NGINX_AUDIT_RULES"])>0) {
            foreach ($_SESSION["NGINX_AUDIT_RULES"] as $ruleid=>$none){
                if(intval($ruleid)>0) {
                    $t[] = $ruleid;
                }
            }
        }
    }

    if(count($t)==0){
        return "";
    }

    foreach ($t as $ruleid){
        $ff[]="modsecurity_audit.ruleid=$ruleid";
    }

    return "AND (".@implode(" OR ",$ff).")";
}



function search_filters_src():string{
    $ff=array();
    $t=array();
    $NGINX_AUDIT_SRC=array();
    $IP=new IP();
    if(isset($_SESSION["NGINX_AUDIT_SRC"])){
        $NGINX_AUDIT_SRC=$_SESSION["NGINX_AUDIT_SRC"];
    }
    if($IP->isValid($_GET["search"])){
        $t[]=$_GET["search"];
        $_GET["search"]="";
    }
        if(count($NGINX_AUDIT_SRC)>0) {
            foreach ($NGINX_AUDIT_SRC as $ruleid=>$none){
                if(intval($ruleid)>0) {
                    $t[] = $ruleid;
                }
            }
        }

    if(count($t)==0){return "";}
    foreach ($t as $ruleid){$ff[]="modsecurity_audit.ipaddr='$ruleid'";}
    return "AND (".@implode(" OR ",$ff).")";
}
function search_filters_dst():string{
    $t=array();
    $ff=array();
    if(isset($_SESSION["NGINX_AUDIT_DST"])){
        if(count($_SESSION["NGINX_AUDIT_DST"])>0) {
            foreach ($_SESSION["NGINX_AUDIT_DST"] as $ruleid=>$none){
                if(intval($ruleid)>-1) {
                    $t[] = $ruleid;
                }
            }
        }
    }
    if(count($t)==0){return "";}
    foreach ($t as $ruleid){$ff[]="modsecurity_audit.serviceid=$ruleid";}
    return "AND (".@implode(" OR ",$ff).")";
}

function search_filters_date():string{

    if(!isset($_SESSION["NGINX_AUDIT_DATE"])){return "";}
    if($_SESSION["NGINX_AUDIT_DATE"]==0){return "";}

   /* $Date[1] = "{today}";
    $Date[2] = "{yesterday}";
    $Date[3] = "{this_week}";
    $Date[4] = "{this_month}";
    $Date[5] = "{this_hour}";
*/
    if($_SESSION["NGINX_AUDIT_DATE"]==1) {
        $monday_start = date('Y-m-d 00:00:00');
        return "AND created>'$monday_start'";

    }

    if($_SESSION["NGINX_AUDIT_DATE"]==2) {
        $monday_start = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $monday_end = date('Y-m-d 23:59:59', strtotime('-1 day'));
        return "AND created>'$monday_start' AND created < '$monday_end'";

    }
    if($_SESSION["NGINX_AUDIT_DATE"]==3) {
        $date = new DateTime();
        $date->modify('this week');
        $monday = $date->format('Y-m-d') . " 00:00:00";
        return "AND created>'$monday'";
    }
    if($_SESSION["NGINX_AUDIT_DATE"]==4) {
        $monday=date("Y-m-")."01 00:00:00";
        return "AND created>'$monday'";
    }
    if($_SESSION["NGINX_AUDIT_DATE"]==5) {
        $monday=date("Y-m-d H").":00:00";
        return "AND created>'$monday'";
    }

    return "";

}


function filltable_js(){
    $modtools=new modesctools();
    $tpl=new template_admin();
    $data=explode(",",$_SESSION["fhostidLine"]);
    $page=CurrentPageName();
    $f=array();
    $MAIN=array();
    foreach ($data as $line){
        $tb=explode("|",$line);
        $md=$tb[0];
        $hostid=intval($tb[1]);
        $ipaddr=$tb[2];

        if(!isset($MAIN[$ipaddr])){
            if($hostid==0){
                $modtools->hostinfo($ipaddr);
                $MAIN[$ipaddr]["flag"]=$modtools->flag;
                $MAIN[$ipaddr]["country"]=$modtools->country;
                $MAIN[$ipaddr]["country_name"]=$modtools->country_name;

            }else{
                $modtools->hostinfo($ipaddr);
                $MAIN[$ipaddr]["flag"]=$modtools->flag;
                $MAIN[$ipaddr]["country"]=$modtools->country;
                $MAIN[$ipaddr]["country_name"]=$modtools->country_name;
            }
        }

        $f[]="// $ipaddr: {$MAIN[$ipaddr]["country"]}/{$MAIN[$ipaddr]["country_name"]}";

        if(strlen( $MAIN[$ipaddr]["flag"])>0){
            $flag=base64_encode("<img src='/img/".$MAIN[$ipaddr]["flag"]."'>&nbsp;{$MAIN[$ipaddr]["country_name"]}");
            $f[]="if(document.getElementById('$md-flag') ){";
            $f[]="\tdocument.getElementById('$md-flag').innerHTML=base64_decode('$flag');";
            $f[]="}";
        }
        $f[]="";
        $f[]="if(document.getElementById('$md-ipaddr')){";
        $ipaddrEnc=base64_encode($tpl->td_href($ipaddr,$MAIN[$ipaddr]["country_name"],"Loadjs('$page?zoom-ip-js=$ipaddr')"));
        $f[]="\tdocument.getElementById('$md-ipaddr').innerHTML=base64_decode('$ipaddrEnc');";
        $f[]="}";
        $f[]="";

    }
    header('Content-Type: application/json; charset=utf-8');
    echo @implode("\n",$f);
}

function search():bool{
    $t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $function=$_GET["function"];
    $ANDR="";
    $ANDS="";
    $ANDL="";
    $ANDD="";
    $ANDT="";
    if(!$q->FIELD_EXISTS("modsecurity_audit","hostid")){
        $q->QUERY_SQL("ALTER TABLE modsecurity_audit ADD hostid BIGINT NOT NULL DEFAULT 0");
    }
    $AND_COUNT=0;
    $rules=search_filters_rules();
    if (strlen($rules)>5){
        $AND_COUNT++;
        $ANDR=$rules;
    }
    $src=search_filters_src();
    if (strlen($src)>5){
        $AND_COUNT++;
        $ANDS=$src;
    }

    $dst=search_filters_dst();
    if (strlen($dst)>5){
        $AND_COUNT++;
        $ANDD=$dst;
    }
    $xtime=search_filters_date();
    if (strlen($xtime)>5){
        $AND_COUNT++;
        $ANDT=$xtime;
    }

    if(isset($_SESSION["NGINX_AUDIT_LEVEL"])) {
        if ($_SESSION["NGINX_AUDIT_LEVEL"] > -1) {
            $AND_COUNT++;
            $ANDL = " AND severity={$_SESSION["NGINX_AUDIT_LEVEL"]}";
        }
    }
    $ANDK="";
    if($_GET["search"]<>null){
        $search=$_GET["search"];
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $ANDK=" AND mainurl LIKE '$search'";
    }

    $sql="SELECT 
    modsecurity_audit.id,
    modsecurity_audit.ruleid,
    modsecurity_audit.mainurl,
    modsecurity_audit.created,
    modsecurity_messages.explain as subject,
    modsecurity_audit.explainid,
    modsecurity_audit.serviceid,
    modsecurity_audit.hostid,
    modsecurity_audit.severity,
    modsecurity_audit.ipaddr,
    modsecurity_audit.reportid
    FROM modsecurity_audit,modsecurity_messages
    WHERE modsecurity_audit.msgid=modsecurity_messages.id $ANDR $ANDS $ANDL $ANDD $ANDT $ANDK
    ORDER BY created DESC LIMIT 250";




    $results=$q->QUERY_SQL($sql);

    writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error."<hr>$sql");
    }

    if($GLOBALS["VERBOSE"]){
        echo "<H1>$sql</H1>\n";
        echo "<H1>".pg_num_rows($results)." rows</H1>\n";
    }

    if(pg_num_rows($results)==0){
        $modsecurity_events_num=$q->COUNT_ROWS_LOW("modsecurity_audit");
        $error_no_events_table=$tpl->_ENGINE_parse_body("{error_no_events_table}");
        $error_no_events_table=str_replace("%a",$modsecurity_events_num,$error_no_events_table);
        echo $tpl->div_error($error_no_events_table);
        return false;
    }




    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $html[]="<div id='modsecurity-compile-progress'></div>";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{rule}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{severity}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{website}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{client}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{subject}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{path}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$severity_icon[0]="<span class='label label'>Info</span>";
    $severity_icon[2]="<span class='label label-danger'>{critic}</span>";
    $severity_icon[3]="<span class='label label-danger'>{error}</span>";
    $severity_icon[4]="<span class='label label-warning'>{warning}</span>";
    $severity_icon[5]="<span class='label label-info'>{notice}</span>";

    $modtools=new modesctools();
    $Whitelists=$modtools->LoadAllWhitelists();
    $fhostid=array();


    $TRCLASS=null;
    while($ligne=@pg_fetch_assoc($results)){

        $md=md5(serialize($ligne));
        $id=$ligne["id"];
        $created=$ligne["created"];
        $reportid=intval($ligne["reportid"]);
        $ruleid=intval($ligne["ruleid"]);
        $severity=$ligne["severity"];
        $ipaddr=$ligne["ipaddr"];
        $serviceid=$ligne["serviceid"];
        $www=$modtools->get_servicename($serviceid);
        $ico=$severity_icon[$severity];
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $created_time=strtotime($created);
        $zdate=$tpl->time_to_date($created_time,true);
        $hostid=$ligne["hostid"];
        $subject=$ligne["subject"];
        $uri=$ligne["mainurl"];
        $uri=htmlspecialchars($uri);
        if(strlen($subject)>80){$subject=substr($subject,0,77)."...";}
        $rule_text=$tpl->td_href($ruleid,null,"Loadjs('$page?rule-js=$ruleid&serviceid=$serviceid&function=$function&eventid=$id')");
        $whiteLabel=null;

        if(isset($Whitelists[$ruleid][0])){
            $whiteLabel="<span class='label label-primary'>{whitelisted}</span>&nbsp;";
        }

        if(!is_null($whiteLabel)) {
            if (strlen($whiteLabel) == 0) {
                if (isset($Whitelists[$ruleid][$serviceid])) {
                    $whiteLabel = "<span class='label label-primary'>{whitelisted}</span>&nbsp;";
                }
            }
        }





        $loupe=$tpl->icon_loupe(true,"Loadjs('$page?zoom-js=$id&reportid=$reportid&function=$function');");
        $report=$tpl->icon_attach("Loadjs('$page?zoom-report-js=$reportid&function=$function')","AsWebSecurity");
        $ligne2=$q->mysqli_fetch_array("SELECT report FROM modsecurity_reports where id=$reportid");
        if(strlen(strval($ligne2["report"]))<10){
            $report=$tpl->icon_attach();
        }


        $fhostid[]="$md|$hostid|$ipaddr";


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$loupe</td>";
        $html[]="<td style='width:1%' nowrap>$report</td>";
        $html[]="<td style='width:1%' nowrap>$zdate</td>";
        $html[]="<td style='width:1%' nowrap>$rule_text</td>";
        $html[]="<td style='width:1%' nowrap>$ico</td>";
        $html[]="<td style='width:1%' nowrap>$www</td>";
        $html[]="<td style='width:1%' nowrap><span id='$md-flag'></span></td>";
        $html[]="<td style='width:1%' nowrap><span id='$md-ipaddr'>$ipaddr</span></td>";
        $html[]="<td>$whiteLabel$subject</td>";
        $html[]="<td>$uri</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?jstiny=yes&function=$function');";
    if(count($fhostid)>0){
        $_SESSION["fhostidLine"]=@implode(",",$fhostid);
        $html[]="setTimeout(\"Loadjs('$page?filltable-js=yes')\",1000);";
    }

    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
$html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

    return true;

}

function jstiny():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $search_audit=$tpl->_ENGINE_parse_body("{search_audit}");


    $l="{toutesles}";
    $r="{allrules}";
    $c="{alllevels}";
    $i="{alladdresses}";
    $w="{all}";
    $p="{all}";
    $d="{all}";

    if(isset($_SESSION["NGINX_AUDIT_RULES"])){
        if(count($_SESSION["NGINX_AUDIT_RULES"])>0) {
            $t=array();
            foreach ($_SESSION["NGINX_AUDIT_RULES"] as $ruleid=>$none){
                if(intval($ruleid)>0) {
                    $t[] = $ruleid;
                }
            }
            if(count($t)>0) {
                $r = "{rules}:<strong> " . @implode(" {or} ", $t) . "</strong>";
            }
        }
    }
    if(isset($_SESSION["NGINX_AUDIT_LEVEL"])){
        if($_SESSION["NGINX_AUDIT_LEVEL"]>-1) {
            $severity_icon[0] = "<span class='label label'>Info</span>";
            $severity_icon[2] = "<span class='label label-danger'>{critic}</span>";
            $severity_icon[3] = "<span class='label label-danger'>{error}</span>";
            $severity_icon[4] = "<span class='label label-warning'>{warning}</span>";
            $severity_icon[5] = "<span class='label label-info'>{notice}</span>";
            $c = $severity_icon[$_SESSION["NGINX_AUDIT_LEVEL"]];
        }
    }
    if(isset($_SESSION["NGINX_AUDIT_DATE"])){
        if($_SESSION["NGINX_AUDIT_DATE"]>0) {
            $Date[1] = "{today}";
            $Date[2] = "{yesterday}";
            $Date[3] = "{this_week}";
            $Date[4] = "{this_month}";
            $Date[5] = "{this_hour}";
            $d = $Date[$_SESSION["NGINX_AUDIT_DATE"]];
        }
    }

    if(isset($_SESSION["NGINX_AUDIT_SRC"])){
        if(count($_SESSION["NGINX_AUDIT_SRC"])>0) {
            $t=array();
            foreach ($_SESSION["NGINX_AUDIT_SRC"] as $ruleid=>$none){
                if(intval($ruleid)>0) {
                    $t[] = $ruleid;
                }
            }
            if(count($t)>0) {
                $i = "{src}:<strong> " . @implode(" {or} ", $t) . "</strong>";
            }
        }
    }
    if(isset($_SESSION["NGINX_AUDIT_DST"])){
        if(count($_SESSION["NGINX_AUDIT_DST"])>0) {
            $t=array();
            foreach ($_SESSION["NGINX_AUDIT_DST"] as $ruleid=>$none){
                if(intval($ruleid)>0) {
                    $t[] = get_servicename($ruleid);
                }
            }
            if(count($t)>0) {
                $w = "<strong> " . @implode(" {or} ", $t) . "</strong>";
            }
        }
    }


    $r=$tpl->td_href($r,null,"Loadjs('$page?filter-rules=yes&function=$function')");
    $c=$tpl->td_href($c,null,"Loadjs('$page?filter-level=yes&function=$function')");
    $i=$tpl->td_href($i,null,"Loadjs('$page?filter-src=yes&function=$function')");
    $w=$tpl->td_href($w,null,"Loadjs('$page?filter-dst=yes&function=$function')");
    $d=$tpl->td_href($d,null,"Loadjs('$page?filter-date=yes&function=$function')");

    $search_audit=str_replace("%l",$l,$search_audit);
    $search_audit=str_replace("%r",$r,$search_audit);
    $search_audit=str_replace("%c",$c,$search_audit);
    $search_audit=str_replace("%i",$i,$search_audit);
    $search_audit=str_replace("%w",$w,$search_audit);
    $search_audit=str_replace("%p",$p,$search_audit);
    $search_audit=str_replace("%t",$d,$search_audit);

    $reconfigure_js= reconfigure_js();
    $topbuttons[] = array("Loadjs('$page?clean-table=yes&function=$function')", ico_trash, "{empty_database}");
    $topbuttons[] = array("$reconfigure_js", ico_save, "{reconfigure_service}");

    $TINY_ARRAY["TITLE"]="WAF Audit";
    $TINY_ARRAY["ICO"]=ico_audit;
    $TINY_ARRAY["EXPL"]="<span style='font-size:16px'>$search_audit</span>";
    $TINY_ARRAY["URL"]=null;
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    header("content-type: application/x-javascript");
    echo $jstiny;
    return true;
}
function zoom_ip_popup():bool{
    $ipaddr=$_GET["zoom-ip-popup"];
    $page=CurrentPageName();
    $ipaddrencoded=urlencode($ipaddr);
    $md5=md5($ipaddr);
    echo "<div id='$md5'></div>";
    echo "<script>LoadAjax('$md5','$page?zoom-ip-popup2=$ipaddrencoded');</script>";
    return true;
}
function zoom_ip_popup2():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ipaddr=$_GET["zoom-ip-popup2"];
    $modtools=new modesctools();
    $modtools->hostinfo($ipaddr);



    $ipaddrmd=md5($ipaddr);
    $html[]="<table style='width:100%'>";
    $html[]="<td style='width:550px'>";
    $html[]="<div id='ip-graph-$ipaddrmd'>".GetFlags256($modtools->country_name)."</div>";
    $js[]="Loadjs('$page?graphline-ipaddr=$ipaddr&id=ip-graph-$ipaddrmd');";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top'>";
    $html[]="<div id='firwall-btn-$ipaddrmd' style='margin-left: 10px'></div>";
    $html[]="<div id='reputation-btn-$ipaddrmd' style='margin-left: 10px'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan=2><div id='ip-info-$ipaddrmd'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('ip-info-$ipaddrmd','$page?zoom-ip-info=$ipaddr');";
    $html[]="LoadAjax('firwall-btn-$ipaddrmd','$page?zoom-ip-fw=$ipaddr&md=firwall-btn-$ipaddrmd');";
    $html[]="LoadAjax('reputation-btn-$ipaddrmd','$page?zoom-ip-reputation=$ipaddr&md=reputation-btn-$ipaddrmd');";

    $html[]=@implode("\n", $js);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function zoom_ip_info(){
    $ipaddr=$_GET["zoom-ip-info"];
    $tpl=new template_admin();
    $hostid=ip2long($ipaddr);
    VERBOSE("Delete $hostid from cache",$hostid,__LINE__);
    $mem = new lib_memcached();
    $mem->Delkey($hostid);

    $modtools=new modesctools();
    $modtools->hostinfo($ipaddr);

    $c=0;
    if(strlen($modtools->hostname)>3) {
        $c++;
        $tpl->table_form_field_text("{hostname}", $modtools->hostname, ico_computer);
    }
    if(strlen($modtools->city)>2) {
        $c++;
        $tpl->table_form_field_text("{city}", $modtools->city, ico_city);
    }
    if(strlen($modtools->country_name)>2) {
        $c++;
        $tpl->table_form_field_text("{country}", $modtools->country_name, ico_location);
    }
    if(strlen($modtools->continent)>1) {
        $c++;
        $tpl->table_form_field_text("{continent}", $modtools->continent, ico_earth);
    }
    if(strlen($modtools->isp)>1) {
        $c++;
        $tpl->table_form_field_text("{isp}", $modtools->isp . " AS:$modtools->asn_number", ico_networks);
    }

    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT fingerprint FROM fingerprints_events WHERE ipaddr='$ipaddr' GROUP BY fingerprint");
    $FFPRINT=array();
    while($ligne=@pg_fetch_array($results)) {
        $url=$tpl->td_href($ligne['fingerprint'],null,"Loadjs('fw.nginx.statistics.fingerprints.php?popup-js={$ligne['fingerprint']}')");
        $FFPRINT[]=$url;
    }
    if(count($FFPRINT)>0){
        $tpl->table_form_field_text("{fingerprints}","<small>".@implode(", ",$FFPRINT)."</small>", "fas fa-fingerprint");
    }

    if($c==0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ipinfo/$hostid");
    }

    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());

}

function zoom_ip_fw():bool{
    $tpl=new template_admin();
    $ipaddr=$_GET["zoom-ip-fw"];
    $EnableNginxFW=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxFW"));

    if($EnableNginxFW==0){
        $btn=array();
        $widget_h = $tpl->widget_h("gray", ico_firewall, "{disabled}", "Web Firewall $ipaddr", $btn);
        echo $tpl->_ENGINE_parse_body($widget_h);
        return true;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $page=CurrentPageName();
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM wp_infirewall WHERE address='$ipaddr' AND enabled=1");
    if(!isset($ligne["ID"])){
        $ligne["ID"]=0;
    }
    $ID=intval($ligne["ID"]);
    VERBOSE("ID = $ID",__LINE__);

    if($ID==0){
        $jsbut="Loadjs('$page?wp-in-firewall=$ipaddr')";
        $button["name"]="{deny_access}";
        $button["js"]=$jsbut;
        $button["ico"]=ico_plus;
        $widget_h = $tpl->widget_h("green", ico_firewall, "{allowed}", "Web Firewall $ipaddr", $button);
        echo $tpl->_ENGINE_parse_body($widget_h);
        return true;
    }

    $jsbut="Loadjs('$page?wp-out-firewall=$ID&ipaddr=$ipaddr')";
    $button["name"]="{allow_access}";
    $button["js"]=$jsbut;
    $button["ico"]=ico_plus;
    $widget_h = $tpl->widget_h("red", ico_firewall, "{deny}", "Web Firewall $ipaddr", $button);
    echo $tpl->_ENGINE_parse_body($widget_h);
    return true;

}
function wp_in_firewall():bool{
    $page=CurrentPageName();
    $ipaddr=$_GET["wp-in-firewall"];
    $ipaddrmd=md5($ipaddr);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $port="80";
    $description="Added From IP Zoom";
    $q->QUERY_SQL("INSERT INTO wp_infirewall (address,port,description) VALUES('$ipaddr','$port','$description')");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('firwall-btn-$ipaddrmd','$page?zoom-ip-fw=$ipaddr&md=firwall-btn-$ipaddrmd');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reverse-proxy/firewall/ipsets");
    return true;
}
function wp_out_firewall():bool{
    $page=CurrentPageName();
    $ID=$_GET["wp-out-firewall"];
    $ipaddr=$_GET["ipaddr"];
    $ipaddrmd=md5($ipaddr);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $q->QUERY_SQL("DELETE FROM wp_infirewall WHERE ID=$ID");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('firwall-btn-$ipaddrmd','$page?zoom-ip-fw=$ipaddr&md=firwall-btn-$ipaddrmd');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reverse-proxy/firewall/ipsets");
    return true;
}

function zoom_ip_reputation(){



}

