<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.modsectools.inc');
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
// awc_table_style()/awc_pager_js(): kit des tableaux d'entites. Include au
// PREMIER NIVEAU obligatoire: place dans le bloc if(!class_exists("sockets"))
// ci-dessous il ne s'executerait jamais, et la page mourrait a l'affichage sur
// « Call to undefined function awc_table_style() » -- un spinner sans fin.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsWebSecurity){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Zoom IP : appele aussi par 13 autres pages de la console, ne pas renommer.
if(isset($_GET["zoom-ip-js"])){zoom_ip_js();exit;}
if(isset($_GET["zoom-ip-popup"])){zoom_ip_popup();exit;}
if(isset($_GET["zoom-ip-popup2"])){zoom_ip_popup2();exit;}
if(isset($_GET["zoom-ip-info"])){zoom_ip_info();exit;}
if(isset($_GET["zoom-ip-fw"])){zoom_ip_fw();exit;}
if(isset($_GET["zoom-ip-reputation"])){zoom_ip_reputation();exit;}
if(isset($_GET["graphline-ipaddr"])){graphline_ipaddr();exit;}
if(isset($_GET["wp-in-firewall"])){wp_in_firewall();exit;}
if(isset($_GET["wp-out-firewall"])){wp_out_firewall();exit;}

// Selecteurs de la barre de filtres (Rules / Websites / Source IP).
if(isset($_GET["filter-src-list-search"])){filter_src_list2();exit;}
if(isset($_GET["filter-rules"])){filter_rule_js();exit;}
if(isset($_GET["filter-rules-popup"])){filter_rule_popup();exit;}
if(isset($_GET["filter-rules-list"])){filter_rule_list();exit;}
if(isset($_GET["filter-rules-selected"])){filter_rule_selected();exit;}
if(isset($_GET["filter-rules-reset"])){filter_rule_reset();exit;}
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

if(isset($_GET["clean-table"])){clean_table();exit;}
if(isset($_POST["clean-table"])){clean_table_perform();exit;}

// Page (maquette stitch_waf_audit).
if(isset($_GET["wafa-build"])){wafa_build();exit;}
if(isset($_GET["wafa-content"])){wafa_content();exit;}
if(isset($_GET["wafa-fill"])){wafa_fill_js();exit;}
if(isset($_GET["wafa-set"])){wafa_set();exit;}
if(isset($_GET["wafa-event"])){wafa_event();exit;}
if(isset($_GET["wafa-wl"])){wafa_wl();exit;}

wafa_page();



function zoom_ip_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ip=$_GET["zoom-ip-js"];
    return $tpl->js_dialog12("{info}:$ip", "$page?zoom-ip-popup=$ip",950);
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

    $sql="SELECT count(*) as tcount, serviceid from (SELECT * FROM modsecurity_audit WHERE rule_index=0) modsecurity_audit $ANDT GROUP by serviceid ORDER BY tcount DESC";

    $TRCLASS=null;
    $results=$q->QUERY_SQL($sql);

    $topbuttons[]=array("Loadjs('$page?filter-dst-reset=yes&function=$function');", ico_trash,"{reset}");
    $html[]=$tpl->_ENGINE_parse_body( $tpl->th_buttons($topbuttons));
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
           ."<table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{webservice}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='mstdst-rows'>";

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }

    while($ligne=@pg_fetch_assoc($results)){
        $TRCLASS=null;
        $html[]="<tr class='$TRCLASS' id=''>";

        $enabled=0;

        $tcount=$ligne["tcount"];
        $serviceid=$ligne["serviceid"];
        $servicename=get_servicename($serviceid);

        // Pentest 2026-11-04 : nom du service (donnee stockee) affiche sans encodage (XSS stockee).
        $html[]="<td style='width:50%' nowrap><strong>".req_html($servicename)."</strong> (".$tpl->FormatNumber($tcount)." {threats})</td>";

        if(isset($_SESSION["NGINX_AUDIT_DST"][$serviceid])){
            $enabled=1;
        }
        $enable=$tpl->icon_check($enabled,"Loadjs('$page?filter-dst-selected=$serviceid&function=$function')");
        $html[]="<td style='width:1%' nowrap>$enable</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='mstdst-pager'></span></div></div></div>";
    $html[]="<script>";

    $html[]="NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."";
    $html[]="</script>";
    // Pagination reprise de FooTable. Concatenee APRES le parse: la regex
    // #{(.+?)}# du moteur lirait les blocs {...} de son JS comme des {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"mstdst-rows","mstdst-pager");
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
// Condition SQL (sans WHERE) pour la recherche d'adresse : IPv4 et IPv6.
// Aucune entrée brute n'entre dans le SQL : chaque branche valide sa forme
// (filter_var, regex) avant de l'insérer ; une entrée non reconnue ne filtre pas ("").
function threats_ip_search_condition($search){
    $search=trim(strval($search));
    if($search==""){return "";}
    if(filter_var($search,FILTER_VALIDATE_IP)){
        return "ipaddr = '".strtolower($search)."'::inet";
    }
    if(strpos($search,"/")!==false){
        $parts=explode("/",$search,2);
        $net=trim($parts[0]);
        $len=trim($parts[1]);
        if(filter_var($net,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
            if(preg_match('#^(3[0-2]|[12]?[0-9])$#',$len)){
                return "'$net/$len'::inet >>= ipaddr";
            }
            return "";
        }
        if(filter_var($net,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)){
            if(preg_match('#^(12[0-8]|1[01][0-9]|[1-9]?[0-9])$#',$len)){
                return "'".strtolower($net)."/$len'::inet >>= ipaddr";
            }
        }
        return "";
    }
    if(strpos($search,"*")!==false){
        if(!preg_match('#^[0-9a-fA-F:.*]+$#',$search)){return "";}
        return "ipaddr::text LIKE '".strtolower(str_replace("*","%",$search))."'";
    }
    if(strpos($search,":")!==false){
        // Recherche partielle IPv6 : préfixe textuel
        if(!preg_match('#^[0-9a-fA-F:.]+$#',$search)){return "";}
        return "ipaddr::text LIKE '".strtolower($search)."%'";
    }
    // Préfixe IPv4 par octets (1 à 3 entiers 0-255)
    $tb=explode(".",rtrim($search,"."));
    if(count($tb)<=3){
        $octets=true;
        foreach($tb as $o){
            if(!preg_match('#^[0-9]{1,3}$#',$o) || intval($o)>255){$octets=false;break;}
        }
        if($octets){
            $o=array_map('intval',$tb);
            if(count($o)==1){return "'$o[0].0.0.0/8'::inet >>= ipaddr";}
            if(count($o)==2){return "'$o[0].$o[1].0.0/16'::inet >>= ipaddr";}
            return "'$o[0].$o[1].$o[2].0/24'::inet >>= ipaddr";
        }
    }
    // Fragment IPv6 sans « : » (fd00, 2a01)
    if(preg_match('#^[0-9a-f]{1,4}$#i',$search)){
        return "ipaddr::text LIKE '".strtolower($search)."%'";
    }
    return "";
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
        $cond=threats_ip_search_condition($search);
        if($cond<>""){$ANDB="$WHERE $cond";}
    }

    $sql="SELECT count(*) as tcount, ipaddr from (SELECT * FROM modsecurity_audit WHERE rule_index=0) modsecurity_audit $ANDT $ANDD $ANDB GROUP by ipaddr ORDER BY tcount DESC";
    $modtools=new modesctools();
    $TRCLASS=null;
    $results=$q->QUERY_SQL($sql);

    $topbuttons[]=array("Loadjs('$page?filter-src-reset=yes&function=$function');", ico_trash,"{reset}");
    $html[]=$tpl->_ENGINE_parse_body( $tpl->th_buttons($topbuttons));
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
           ."<table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' ></th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{src}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='mstsrc-rows'>";

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error."<hr>".$sql);
    }

    $mod=new modesctools();
    //($_SESSION["NGINX_AUDIT_RULES"]
    while($ligne=@pg_fetch_assoc($results)){
        $TRCLASS=null;
        $html[]="<tr class='$TRCLASS' id=''>";

        $enabled=0;

        $tcount=$ligne["tcount"];
        $ipaddr=$ligne["ipaddr"];
        $mod->hostinfo($ipaddr);
        $hostname=$mod->hostname;
        $flag=$mod->flag;
        $html[]="<td style='width:1%' nowrap><img src='img/$flag' alt=''></td>";
        // Pentest 2026-11-04 : nom d'hote inverse (donnee externe) affiche sans encodage (XSS stockee).
        $html[]="<td style='width:50%' nowrap><strong>".req_html($ipaddr)." - ".req_html($hostname)."</strong> (".$tpl->FormatNumber($tcount)." {threats})</td>";

        if(isset($_SESSION["NGINX_AUDIT_SRC"][$ipaddr])){
            $enabled=1;
        }
        $enable=$tpl->icon_check($enabled,"Loadjs('$page?filter-src-selected=$ipaddr&function=$function')");
        $html[]="<td style='width:1%' nowrap>$enable</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='mstsrc-pager'></span></div></div></div>";
    $html[]="<script>";

    $html[]="NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."";
    $html[]="</script>";
    // Pagination reprise de FooTable. Concatenee APRES le parse: la regex
    // #{(.+?)}# du moteur lirait les blocs {...} de son JS comme des {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"mstsrc-rows","mstsrc-pager");
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
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
           ."<table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{rule}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{threats}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='mstrul-rows'>";

    if(!$q->ok OR !$results){
        echo $tpl->div_error($q->mysql_error."<hr>$sql");
        return true;
    }

    //($_SESSION["NGINX_AUDIT_RULES"]
    while($ligne=@pg_fetch_assoc($results)){
        $TRCLASS=null;
        $ruleid=$ligne["ruleid"];
        $desc=trim($modtools->modsecurity_titles($ruleid));
        $html[]="<tr class='$TRCLASS' id=''>";
        // Pentest 2026-11-04 : regle/message ModSecurity (donnee stockee) affiche sans encodage (XSS stockee).
        $html[]="<td style='width:1%' nowrap>".req_html($ruleid)."</td>";
        $enabled=0;


        $tcount=$ligne["tcount"];
        $msgid=$ligne["msgid"];
        $ligne2=$q->mysqli_fetch_array("SELECT * FROM modsecurity_messages WHERE id=$msgid");
        $explain=$ligne2["explain"];
        $html[]="<td nowrap style='width:1%;text-align:right'>".$tpl->FormatNumber($tcount)."</td>";
        $html[]="<td style='width:50%' nowrap><strong>".req_html($desc)." ".req_html($explain)."</td>";

        if(isset($_SESSION["NGINX_AUDIT_RULES"][$ruleid])){
            $enabled=1;
        }
        $enable=$tpl->icon_check($enabled,"Loadjs('$page?filter-rules-selected=$ruleid&function=$function')");
        $html[]="<td style='width:1%' nowrap>$enable</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='mstrul-pager'></span></div></div></div>";
    $html[]="<script>";

    $html[]="NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."";
    $html[]="</script>";
    // Pagination reprise de FooTable. Concatenee APRES le parse: la regex
    // #{(.+?)}# du moteur lirait les blocs {...} de son JS comme des {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"mstrul-rows","mstrul-pager");
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
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
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

















function graphline_ipaddr():bool{
    $divid=$_GET["id"];
    $EnableModSecurityIngix = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));

    if($EnableModSecurityIngix==0){
        return false;
    }

    $ipaddr=$_GET["graphline-ipaddr"];
    $q=new postgres_sql();
    $sql="SELECT to_char(date_trunc('hour', created), 'MM-DD HH24') AS hour_formatted,
    COUNT(*) AS event_count FROM modsecurity_audit WHERE ipaddr=$1 AND rule_index=0 GROUP BY 1 ORDER BY 1;";
    $ydata=array();
    $xdata=array();
    // Pentest 2026-11-04 : graphline-ipaddr etait concatene dans la requete.
    $results=$q->QUERY_SQL_PARAMS($sql,array($ipaddr));

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


















function reconfigure_js($serviceid=0):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$serviceid')";

}








function clean_table():bool{
    $tpl=new template_admin();
    $id=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);
    if($id==""){$id="WafaRefresh";}
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




function search_filters_dst():string{
    $t=array();
    $ff=array();
    if(isset($_SESSION["NGINX_AUDIT_DST"])){
        if(count($_SESSION["NGINX_AUDIT_DST"])>0) {
            foreach ($_SESSION["NGINX_AUDIT_DST"] as $ruleid=>$none){
                // Pentest 2026-11-04 : la cle de session (issue de la requete) etait concatenee dans la requete.
                if(intval($ruleid)>-1) {
                    $t[] = intval($ruleid);
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
    if(!filter_var($ipaddr,FILTER_VALIDATE_IP)){return false;}
    if(strpos($ipaddr,":")!==false){
        // IPv6 : la cle est l'adresse, hton n'existe pas
        $ipaddr=inet_ntop(inet_pton($ipaddr));
        $hostid=$ipaddr;
        unset($GLOBALS["hton"]["ipinfo-addr:".$ipaddr]);
    }else{
        $hostid=ip2long($ipaddr);
        VERBOSE("Delete $hostid from cache",$hostid,__LINE__);
        $mem = new lib_memcached();
        $mem->Delkey($hostid);
    }

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
        $url=$tpl->td_href($ligne['fingerprint'],null,"Loadjs('fw.nginx.statistics.fingerprints.php?popup-js=".req_url($ligne['fingerprint'])."')");
        $FFPRINT[]=$url;
    }
    if(count($FFPRINT)>0){
        $tpl->table_form_field_text("{fingerprints}","<small>".@implode(", ",$FFPRINT)."</small>", "fas fa-fingerprint");
    }

    if($c==0){
        if(strpos($ipaddr,":")!==false){
            // IPv6 : /ipinfo/{hton} n'accepte qu'un entier, on passe par ip2host (API + table hotstinfos)
            include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
            $ip2host=new ip2host();
            $ip2host->ipinfoApi($ipaddr);
        }else{
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/ipinfo/$hostid");
        }
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
    // Pentest 2026-11-04 : zoom-ip-fw etait concatene dans la requete.
    $zres=$q->QUERY_SQL_PARAMS("SELECT ID FROM wp_infirewall WHERE address=? AND enabled=1",array((string)$ipaddr));
    $ligne=(is_array($zres) && isset($zres[0]))?$zres[0]:array();
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
    // Pentest 2026-11-04 : wp-in-firewall etait concatene dans la requete.
    $q->QUERY_SQL_PARAMS("INSERT INTO wp_infirewall (address,port,description) VALUES(?,?,?)",array((string)$ipaddr,$port,$description));
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('firwall-btn-$ipaddrmd','$page?zoom-ip-fw=$ipaddr&md=firwall-btn-$ipaddrmd');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/firewall/ipsets");
    return true;
}
function wp_out_firewall():bool{
    $page=CurrentPageName();
    // Pentest 2026-11-04 : wp-out-firewall etait concatene dans la requete.
    $ID=intval($_GET["wp-out-firewall"]);
    $ipaddr=$_GET["ipaddr"];
    $ipaddrmd=md5($ipaddr);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $q->QUERY_SQL("DELETE FROM wp_infirewall WHERE ID=$ID");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('firwall-btn-$ipaddrmd','$page?zoom-ip-fw=$ipaddr&md=firwall-btn-$ipaddrmd');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/firewall/ipsets");
    return true;
}

function zoom_ip_reputation(){



}


// =====================================================================================
// WAF Audit -- maquette stitch_waf_audit. Un widget = une fonction, prefixe wafa_.
// Donnees : PostgreSQL modsecurity_audit (+ messages, titles, explains, reports),
// liste blanche via /modsec/whitelist (modesctools), geolocalisation modesctools.
// Les filtres vivent en session (NGINX_AUDIT_*), partages avec les selecteurs
// Rules / Websites / Source IP plus haut.
// =====================================================================================

function wafa_page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{wafa_title}",ico_audit,"{wafa_explain}","$page?wafa-build=yes",
        "threats-waf","progress-firehol-restart",false,"waf-threats-table");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("WAF Audit",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// Hote de la page : le contenu (rechargeable), le panneau lateral (hors du contenu
// pour survivre aux rechargements), le JS et le TINY.
function wafa_build(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();
    $html[]="<div class='wafa ak'>";
    $html[]="<div id='wafa-root'></div>";
    $html[]=wafa_drawer_shell();
    $html[]="</div>";
    echo wafa_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).wafa_js().wafa_tiny_js();
}

function wafa_tiny_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    $topbuttons[]=array("WafaRefresh();",ico_refresh,"{refresh}");
    $topbuttons[]=array(reconfigure_js(),ico_save,"{reconfigure_service}");
    $topbuttons[]=array("Loadjs('$page?clean-table=yes&function=WafaRefresh')",ico_trash,"{empty_database}","","danger");
    $TINY=array();
    $TINY["TITLE"]="{wafa_title}";
    $TINY["ICO"]=ico_audit;
    $TINY["EXPL"]="{wafa_explain}";
    $TINY["URL"]="threats-waf";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
}

// ---------- filtres ----------

function wafa_filters():array{
    $f=array("rules"=>array(),"sites"=>array(),"ips"=>array(),"date"=>0,"level"=>-1,"search"=>"");
    if(isset($_SESSION["NGINX_AUDIT_RULES"]) && is_array($_SESSION["NGINX_AUDIT_RULES"])){
        foreach($_SESSION["NGINX_AUDIT_RULES"] as $k=>$none){if(intval($k)>0){$f["rules"][]=intval($k);}}
    }
    if(isset($_SESSION["NGINX_AUDIT_DST"]) && is_array($_SESSION["NGINX_AUDIT_DST"])){
        foreach($_SESSION["NGINX_AUDIT_DST"] as $k=>$none){if(intval($k)>-1){$f["sites"][]=intval($k);}}
    }
    if(isset($_SESSION["NGINX_AUDIT_SRC"]) && is_array($_SESSION["NGINX_AUDIT_SRC"])){
        foreach($_SESSION["NGINX_AUDIT_SRC"] as $k=>$none){if(filter_var($k,FILTER_VALIDATE_IP)){$f["ips"][]=$k;}}
    }
    if(isset($_SESSION["NGINX_AUDIT_DATE"])){$f["date"]=intval($_SESSION["NGINX_AUDIT_DATE"]);}
    if(isset($_SESSION["NGINX_AUDIT_LEVEL"])){$f["level"]=intval($_SESSION["NGINX_AUDIT_LEVEL"]);}
    if(isset($_SESSION["NGINX_AUDIT_SEARCH"])){$f["search"]=strval($_SESSION["NGINX_AUDIT_SEARCH"]);}
    if($f["date"]<0 || $f["date"]>5){$f["date"]=0;}
    if($f["level"]<-1 || $f["level"]>7){$f["level"]=-1;}
    return $f;
}

function wafa_has_filters(array $f):bool{
    return count($f["rules"])+count($f["sites"])+count($f["ips"])>0 || $f["date"]>0 || $f["level"]>-1 || $f["search"]<>"";
}

// Clause " AND ..." commune aux tuiles et au tableau. $withPeriod=false pour les
// barres par heure, qui couvrent toujours les 24 dernieres heures.
function wafa_where(array $f,$withPeriod=true):string{
    $w=array();
    // Une ligne par requete, sauf filtre par regle (rule_index=0 cacherait les regles secondaires).
    if(count($f["rules"])==0){$w[]="AND modsecurity_audit.rule_index=0";}
    $r=search_filters_rules(); if(strlen($r)>5){$w[]=$r;}
    $d=search_filters_dst();   if(strlen($d)>5){$w[]=$d;}
    if(count($f["ips"])>0){
        $ff=array();
        foreach($f["ips"] as $ip){$ff[]="modsecurity_audit.ipaddr='$ip'";}
        $w[]="AND (".@implode(" OR ",$ff).")";
    }
    if($withPeriod){$t=search_filters_date(); if(strlen($t)>5){$w[]=$t;}}
    if($f["level"]>-1){$w[]="AND modsecurity_audit.severity=".intval($f["level"]);}
    if($f["search"]<>""){
        if(filter_var($f["search"],FILTER_VALIDATE_IP)){
            $w[]="AND modsecurity_audit.ipaddr='".$f["search"]."'";
        }else{
            $w[]="AND modsecurity_audit.mainurl LIKE '%".pg_escape_string2(str_replace(array("%","_"),array("\\%","\\_"),$f["search"]))."%'";
        }
    }
    return " ".@implode(" ",$w);
}

function wafa_rows($sql):array{
    $q=new postgres_sql();
    $res=$q->QUERY_SQL($sql);
    $rows=array();
    if(!$q->ok){$GLOBALS["WAFA_ERR"]=$q->mysql_error;return $rows;}
    if(!$res){return $rows;}
    while($l=@pg_fetch_assoc($res)){$rows[]=$l;}
    return $rows;
}

// 24 cases horaires, calculees par PostgreSQL (meme fuseau que created).
function wafa_hourly_sql($where):string{
    return "SELECT to_char(g,'HH24') AS h, COALESCE(x.c,0) AS c FROM generate_series(date_trunc('hour',now())-interval '23 hours',date_trunc('hour',now()),interval '1 hour') g
    LEFT JOIN (SELECT date_trunc('hour',created) AS hh, count(*) AS c FROM modsecurity_audit WHERE created>=date_trunc('hour',now())-interval '23 hours' $where GROUP BY 1) x ON x.hh=g ORDER BY g";
}

// Modele calcule une fois, lu par tous les widgets.
function wafa_model():array{
    $f=wafa_filters();
    $W=wafa_where($f);
    $m=array("f"=>$f,"total"=>0,"sev"=>array(),"rules"=>array(),"ips"=>array(),"hourly"=>array(),
        "events"=>array(),"reports"=>array(),"white"=>array(),"any"=>true,"engine"=>1,"err"=>"");
    $m["engine"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    $r=wafa_rows("SELECT count(*) AS c FROM modsecurity_audit WHERE 1=1 $W");
    if(isset($r[0]["c"])){$m["total"]=intval($r[0]["c"]);}
    foreach(wafa_rows("SELECT severity, count(*) AS c FROM modsecurity_audit WHERE 1=1 $W GROUP BY severity ORDER BY severity") as $l){
        $m["sev"][intval($l["severity"])]=intval($l["c"]);
    }
    $m["rules"]=wafa_rows("SELECT ruleid, count(*) AS c FROM modsecurity_audit WHERE 1=1 $W GROUP BY ruleid ORDER BY c DESC LIMIT 3");
    $m["ips"]=wafa_rows("SELECT host(ipaddr) AS ipaddr, count(*) AS c FROM modsecurity_audit WHERE 1=1 $W GROUP BY ipaddr ORDER BY c DESC LIMIT 3");
    $m["hourly"]=wafa_rows(wafa_hourly_sql(wafa_where($f,false)));
    $m["events"]=wafa_rows("SELECT modsecurity_audit.id, modsecurity_audit.ruleid, modsecurity_audit.mainurl, modsecurity_audit.created,
        modsecurity_audit.serviceid, modsecurity_audit.severity, host(modsecurity_audit.ipaddr) AS ipaddr, modsecurity_audit.reportid,
        modsecurity_messages.explain AS subject
        FROM modsecurity_audit LEFT JOIN modsecurity_messages ON modsecurity_messages.id=modsecurity_audit.msgid
        WHERE 1=1 $W ORDER BY modsecurity_audit.created DESC LIMIT 250");
    // Une requete pour savoir quels rapports existent (au lieu d'une par ligne).
    $ids=array();
    foreach($m["events"] as $e){if(intval($e["reportid"])>0){$ids[intval($e["reportid"])]=true;}}
    if(count($ids)>0){
        foreach(wafa_rows("SELECT id FROM modsecurity_reports WHERE id IN (".@implode(",",array_keys($ids)).") AND length(report)>=10") as $l){
            $m["reports"][intval($l["id"])]=true;
        }
    }
    if(count($m["events"])>0){
        $modtools=new modesctools();
        $m["white"]=$modtools->LoadAllWhitelists();
    }
    if($m["total"]==0 && !wafa_has_filters($f)){$m["any"]=false;}
    if(isset($GLOBALS["WAFA_ERR"])){$m["err"]=$GLOBALS["WAFA_ERR"];}
    return $m;
}

// ---------- petits utilitaires d'affichage ----------

function wafa_sev_tone($s):string{
    $s=intval($s);
    if($s<=2){return "crit";}
    if($s==3){return "err";}
    if($s==4){return "warn";}
    if($s==5){return "info";}
    return "neu";
}
function wafa_sev_icon($s):string{
    $s=intval($s);
    if($s<=2){return "fas fa-circle-exclamation";}
    if($s==3){return "fas fa-circle-xmark";}
    if($s==4){return "fas fa-triangle-exclamation";}
    return "fas fa-circle-info";
}
// Bareme ModSecurity : 0 emergency ... 7 debug (l'ancien tableau affichait 0 comme Info).
function wafa_sev_pill($s):string{
    $s=intval($s);
    if($s<0 || $s>7){return "<span class=\"ak-pill wafa-neu\">{wafa_unknown}</span>";}
    return "<span class=\"ak-pill wafa-".wafa_sev_tone($s)."\"><span class=\"wafa-pi\"><i class=\"".wafa_sev_icon($s)."\" aria-hidden=\"true\"></i></span>{wafa_sev_$s}</span>";
}
function wafa_num($n):string{return number_format(intval($n),0,".",",");}
function wafa_tr($token,$value):string{
    $tpl=new template_admin();
    return str_replace("%s",$value,$tpl->_ENGINE_parse_body("{".$token."}"));
}
function wafa_ago($ts):string{
    $d=time()-intval($ts);
    if($d<0){$d=0;}
    if($d<60){return wafa_tr("wafa_ago_s",$d);}
    if($d<3600){return wafa_tr("wafa_ago_m",intval($d/60));}
    if($d<86400){return wafa_tr("wafa_ago_h",intval($d/3600));}
    return wafa_tr("wafa_ago_d",intval($d/86400));
}
function wafa_path_of($url):string{
    $u=strval($url);
    if(!preg_match("#^[a-z]+://#i",$u)){$u="http://$u";}
    $p=parse_url($u);
    if(!is_array($p) || !isset($p["path"])){return "/";}
    $path=$p["path"];
    if(isset($p["query"])){$path.="?".$p["query"];}
    return $path;
}
function wafa_rule_title($ruleid,$fallback=""):string{
    $modtools=new modesctools();
    $t=strval($modtools->modsecurity_titles(intval($ruleid)));
    if($t==""){$t=strval($fallback);}
    return $t;
}

// ---------- contenu ----------

function wafa_content(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=wafa_model();
    $html=array();
    if($m["engine"]==0){$html[]=wafa_engine_banner();}
    if($m["err"]<>""){$html[]=$tpl->div_error(req_html($m["err"]));}
    $html[]=wafa_filterbar($m);
    $html[]=wafa_summary($m);
    $html[]=wafa_events($m);
    $js="<script>WafaAfter();".wafa_fill_trigger($m)."</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).$js
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"wafa-rows","wafa-pager");
}

function wafa_engine_banner():string{
    return "<div class=\"ak-banner ak-warn wafa-banner\" role=\"status\"><span class=\"wafa-bi\"><i class=\"fas fa-power-off\" aria-hidden=\"true\"></i></span><span>{wafa_engine_off}</span></div>";
}

function wafa_filterbar(array $m):string{
    $f=$m["f"];
    $h=array();
    $h[]="<section class=\"ak-card wafa-fbar\" aria-label=\"{wafa_filters}\">";
    $h[]="<div class=\"wafa-frow\">";
    $h[]=wafa_period_segment($f);
    $h[]="<span class=\"wafa-sep\" aria-hidden=\"true\"></span>";
    $h[]=wafa_severity_select($f);
    $h[]=wafa_picker_btn("filter-rules","fas fa-code","{wafa_rules}",count($f["rules"]));
    $h[]=wafa_picker_btn("filter-dst","fas fa-globe","{wafa_websites}",count($f["sites"]));
    $h[]=wafa_picker_btn("filter-src","fas fa-network-wired","{wafa_source_ip}",count($f["ips"]));
    $h[]=wafa_search_input($f);
    if(wafa_has_filters($f)){
        $h[]="<button type=\"button\" class=\"ak-link\" onclick=\"WafaSet('reset','')\"><span class=\"wafa-bi\"><i class=\"fas fa-filter-circle-xmark\" aria-hidden=\"true\"></i></span>{wafa_reset}</button>";
    }
    $h[]="</div>";
    $h[]=wafa_chips($m);
    $h[]="</section>";
    return @implode("",$h);
}

function wafa_period_segment(array $f):string{
    $order=array(0=>"wafa_p_all",5=>"wafa_p_hour",1=>"wafa_p_today",2=>"wafa_p_yesterday",3=>"wafa_p_week",4=>"wafa_p_month");
    $h=array("<div class=\"ak-seg\" role=\"group\" aria-label=\"{wafa_period}\">");
    foreach($order as $v=>$tk){
        $on=($f["date"]==$v);
        $h[]="<button type=\"button\" class=\"ak-seg-b".($on?" on":"")."\" aria-pressed=\"".($on?"true":"false")."\" onclick=\"WafaSet('date','$v')\">{".$tk."}</button>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

function wafa_severity_select(array $f):string{
    $h=array("<select class=\"wafa-sel\" aria-label=\"{wafa_col_sev}\" onchange=\"WafaSet('level',this.value)\">");
    $h[]="<option value=\"-1\"".($f["level"]==-1?" selected":"").">{wafa_all_sev}</option>";
    for($i=0;$i<=7;$i++){
        $h[]="<option value=\"$i\"".($f["level"]==$i?" selected":"").">{wafa_sev_$i}</option>";
    }
    $h[]="</select>";
    return @implode("",$h);
}

function wafa_picker_btn($key,$ico,$label,$count):string{
    $page=CurrentPageName();
    $badge=$count>0?"<span class=\"wafa-badge\">$count</span>":"";
    return "<button type=\"button\" class=\"wafa-pick".($count>0?" on":"")."\" onclick=\"Loadjs('$page?$key=yes&function=WafaRefresh')\"><span class=\"wafa-bi\"><i class=\"$ico\" aria-hidden=\"true\"></i></span>$label$badge<span class=\"wafa-bi wafa-car\"><i class=\"fas fa-chevron-down\" aria-hidden=\"true\"></i></span></button>";
}

function wafa_search_input(array $f):string{
    $v=req_html($f["search"]);
    return "<label class=\"ak-search wafa-search\"><span class=\"wafa-bi\"><i class=\"fas fa-magnifying-glass\" aria-hidden=\"true\"></i></span><input type=\"text\" id=\"wafa-q\" value=\"$v\" placeholder=\"{wafa_search_url}\" aria-label=\"{wafa_search_url}\" maxlength=\"200\" onkeydown=\"WafaSearchKey(event)\"></label>";
}

// $value est du HTML deja encode, $plain le meme texte sans balise (pour aria-label).
function wafa_chip($label,$value,$plain,$k,$v):string{
    return "<span class=\"wafa-chip\"><span>$label: <strong>$value</strong></span><button type=\"button\" aria-label=\"{wafa_remove_filter} $plain\" onclick=\"WafaSet('$k','$v')\"><i class=\"fas fa-xmark\" aria-hidden=\"true\"></i></button></span>";
}

function wafa_chips(array $m):string{
    $f=$m["f"];
    $modtools=new modesctools();
    $c=array();
    foreach($f["rules"] as $r){$c[]=wafa_chip("{wafa_chip_rule}","<span class=\"ak-mono\">$r</span>",$r,"rule-del",$r);}
    foreach($f["sites"] as $s){$n=req_html($modtools->get_servicename($s));$c[]=wafa_chip("{wafa_chip_site}",$n,$n,"site-del",$s);}
    foreach($f["ips"] as $ip){$c[]=wafa_chip("{wafa_chip_ip}","<span class=\"ak-mono\">".req_html($ip)."</span>",req_html($ip),"ip-del",req_html($ip));}
    if($f["search"]<>""){$c[]=wafa_chip("{wafa_chip_url}",req_html($f["search"]),req_html($f["search"]),"search","");}
    $count="<span class=\"ak-muted\">".wafa_tr("wafa_matching",wafa_num($m["total"]))."</span>";
    if(count($c)==0){return "<div class=\"wafa-chips\">$count</div>";}
    return "<div class=\"wafa-chips\"><span class=\"wafa-lbl\">{wafa_active}</span>".@implode("",$c).$count."</div>";
}

// ---------- tuiles ----------

function wafa_summary(array $m):string{
    return "<section class=\"wafa-tiles\" aria-label=\"{wafa_summary}\">".wafa_tile_detections($m).wafa_tile_severity($m).wafa_tile_rules($m).wafa_tile_sources($m)."</section>";
}

function wafa_period_label(array $f):string{
    $t=array(0=>"wafa_p_all",5=>"wafa_p_hour",1=>"wafa_p_today",2=>"wafa_p_yesterday",3=>"wafa_p_week",4=>"wafa_p_month");
    return "{".$t[$f["date"]]."}";
}

function wafa_tile_detections(array $m):string{
    $vals=array();$sum=0;
    foreach($m["hourly"] as $l){$vals[]=intval($l["c"]);$sum+=intval($l["c"]);}
    $avg=count($vals)>0?round($sum/24,1):0;
    $spark="";
    if(count($vals)>0){
        $spark="<span class=\"wafa-spark\" data-h=\"34\">".@implode(",",$vals)."</span>";
    }
    return "<article class=\"ak-card wafa-tile\"><div class=\"wafa-th\"><span class=\"wafa-tt\">{wafa_detections}</span><span class=\"ak-tag ak-accent\">".wafa_period_label($m["f"])."</span></div>"
        ."<div class=\"wafa-big\">".wafa_num($m["total"])."</div>"
        ."<div class=\"wafa-sub\"><span>{wafa_last24}</span><span class=\"ak-mono\">".wafa_tr("wafa_avg_h",$avg)."</span></div>"
        ."<div class=\"wafa-sparkbox\" title=\"{wafa_last24}\">$spark</div></article>";
}

function wafa_tile_severity(array $m):string{
    $sum=array_sum($m["sev"]);
    $bar=array();$leg=array();
    ksort($m["sev"]);
    foreach($m["sev"] as $s=>$c){
        if($c<=0){continue;}
        $pct=$sum>0?round($c*100/$sum,2):0;
        $tone=($s>=0 && $s<=7)?wafa_sev_tone($s):"neu";
        $name=($s>=0 && $s<=7)?"{wafa_sev_$s}":"{wafa_unknown}";
        $bar[]="<span class=\"wafa-seg-$tone\" style=\"width:$pct%\" title=\"$name: ".wafa_num($c)."\"></span>";
        $ico=($s>=0 && $s<=7)?wafa_sev_icon($s):"fas fa-circle-info";
        $leg[]="<span class=\"wafa-leg wafa-$tone\"><span class=\"wafa-pi\"><i class=\"$ico\" aria-hidden=\"true\"></i></span>$name <strong>".wafa_num($c)."</strong></span>";
    }
    $body=count($bar)>0?"<div class=\"wafa-stack\" role=\"img\" aria-label=\"{wafa_by_sev}\">".@implode("",$bar)."</div><div class=\"wafa-legend\">".@implode("",$leg)."</div>"
        :"<div class=\"ak-muted wafa-none\">{wafa_none}</div>";
    return "<article class=\"ak-card wafa-tile\"><div class=\"wafa-th\"><span class=\"wafa-tt\">{wafa_by_sev}</span><span class=\"ak-muted\">".(count($bar)==1?"{wafa_level_one}":wafa_tr("wafa_levels",count($bar)))."</span></div>$body</article>";
}

function wafa_tile_rules(array $m):string{
    $rows=array();
    foreach($m["rules"] as $l){
        $r=intval($l["ruleid"]);
        $title=req_html(wafa_rule_title($r));
        $rows[]="<button type=\"button\" class=\"wafa-trow\" onclick=\"WafaSet('rule-add','$r')\" title=\"{wafa_filter}\"><span class=\"ak-mono wafa-acc\">$r</span><span class=\"wafa-ell\">$title</span><span class=\"ak-mono wafa-cnt\">".wafa_num($l["c"])."</span></button>";
    }
    if(count($rows)==0){$rows[]="<div class=\"ak-muted wafa-none\">{wafa_none}</div>";}
    return "<article class=\"ak-card wafa-tile\"><div class=\"wafa-th\"><span class=\"wafa-tt\">{wafa_top_rules}</span></div>".@implode("",$rows)."</article>";
}

function wafa_tile_sources(array $m):string{
    $rows=array();
    $modtools=new modesctools();
    foreach($m["ips"] as $l){
        $ip=strval($l["ipaddr"]);
        if(!filter_var($ip,FILTER_VALIDATE_IP)){continue;}
        $modtools->flag="";$modtools->country_name="";
        $modtools->hostinfo($ip);
        $flag=strlen(strval($modtools->flag))>0?"<img src=\"/img/".req_html($modtools->flag)."\" alt=\"\">":"";
        $cty=req_html(strval($modtools->country_name));
        $rows[]="<button type=\"button\" class=\"wafa-trow\" onclick=\"WafaSet('ip-add','".req_html($ip)."')\" title=\"{wafa_filter}\"><span class=\"wafa-flag\">$flag</span><span class=\"ak-mono\" title=\"".req_html($ip)."\">".req_html($ip)."</span><span class=\"wafa-ell ak-muted\">$cty</span><span class=\"ak-mono wafa-cnt\">".wafa_num($l["c"])."</span></button>";
    }
    if(count($rows)==0){$rows[]="<div class=\"ak-muted wafa-none\">{wafa_none}</div>";}
    return "<article class=\"ak-card wafa-tile\"><div class=\"wafa-th\"><span class=\"wafa-tt\">{wafa_top_sources}</span></div>".@implode("",$rows)."</article>";
}

// ---------- tableau ----------

function wafa_events(array $m):string{
    $h=array();
    $h[]="<section class=\"ak-card wafa-evs\">";
    $h[]="<div class=\"wafa-evh\"><div><h2>{wafa_events}</h2><span class=\"ak-muted\">{wafa_limit}</span></div><span class=\"awc-pg\" id=\"wafa-pager\"></span></div>";
    if(count($m["events"])==0){
        $h[]=wafa_empty($m);
        $h[]="</section>";
        return @implode("",$h);
    }
    $h[]="<div class=\"wafa-scroll\"><table class=\"wafa-tbl\"><thead><tr>";
    foreach(array("wafa_col_date","wafa_col_sev","wafa_col_rule","wafa_col_site","wafa_col_client","wafa_col_path") as $c){$h[]="<th scope=\"col\">{".$c."}</th>";}
    $h[]="<th scope=\"col\" class=\"wafa-r\">{wafa_col_actions}</th></tr></thead><tbody id=\"wafa-rows\">";
    $modtools=new modesctools();
    $fill=array();
    foreach($m["events"] as $e){
        $h[]=wafa_event_row($e,$m,$modtools,$fill);
    }
    $h[]="</tbody></table></div></section>";
    $_SESSION["WAFA_FILL"]=$fill;
    return @implode("",$h);
}

function wafa_event_row(array $e,array $m,$modtools,array &$fill):string{
    $id=intval($e["id"]);
    $ruleid=intval($e["ruleid"]);
    $svc=intval($e["serviceid"]);
    $ip=strval($e["ipaddr"]);
    $ts=strtotime(strval($e["created"]));
    $abs=$ts?date("Y-m-d H:i:s",$ts):req_html($e["created"]);
    $ago=$ts?wafa_ago($ts):"";
    $subject=strval($e["subject"]);
    if($subject=="" || $subject=="none"){$subject=wafa_rule_title($ruleid);}
    $path=wafa_path_of($e["mainurl"]);
    $md="wafa-c".$id;
    if(filter_var($ip,FILTER_VALIDATE_IP)){$fill[$md]=$ip;}
    $wl="";
    if(isset($m["white"][$ruleid][0]) && $m["white"][$ruleid][0]===true){$wl="<span class=\"ak-pill wafa-ok wafa-sm\"><span class=\"wafa-pi\"><i class=\"fas fa-circle-check\" aria-hidden=\"true\"></i></span>{wafa_whitelisted}</span>";}
    elseif($svc>0 && isset($m["white"][$ruleid][$svc])){
        $v=$m["white"][$ruleid][$svc];
        if($v===true){$wl="<span class=\"ak-pill wafa-ok wafa-sm\"><span class=\"wafa-pi\"><i class=\"fas fa-circle-check\" aria-hidden=\"true\"></i></span>{wafa_whitelisted}</span>";}
        elseif(is_string($v) && $v<>"" && strpos($path,$v)===0){$wl="<span class=\"ak-pill wafa-ok wafa-sm\"><span class=\"wafa-pi\"><i class=\"fas fa-circle-check\" aria-hidden=\"true\"></i></span>{wafa_wl_path}</span>";}
    }
    $hasReport=isset($m["reports"][intval($e["reportid"])]);
    $raw=$hasReport
        ?"<button type=\"button\" class=\"ak-act\" aria-label=\"{wafa_view_raw}\" title=\"{wafa_view_raw}\" onclick=\"event.stopPropagation();WafaEvent($id,'raw')\"><i class=\"fas fa-file-lines\" aria-hidden=\"true\"></i></button>"
        :"<button type=\"button\" class=\"ak-act\" disabled aria-label=\"{wafa_no_report}\" title=\"{wafa_no_report}\"><i class=\"fas fa-file-lines\" aria-hidden=\"true\"></i></button>";
    $page=CurrentPageName();
    $ipEsc=req_html($ip);
    $ipJs=urlencode($ip);
    $h=array();
    $h[]="<tr tabindex=\"0\" onclick=\"WafaEvent($id)\" onkeydown=\"event.key==='Enter'&&WafaEvent($id)\">";
    $h[]="<td class=\"ak-nowrap\"><div>$ago</div><div class=\"ak-muted ak-mono wafa-xs\">$abs</div></td>";
    $h[]="<td class=\"ak-nowrap\">".wafa_sev_pill($e["severity"])."</td>";
    $h[]="<td class=\"wafa-rule\"><div><span class=\"ak-mono wafa-acc\">$ruleid</span>$wl</div><div class=\"wafa-ell ak-muted\" title=\"".req_html($subject)."\">".req_html($subject)."</div></td>";
    $h[]="<td class=\"wafa-site wafa-ell\">".req_html($modtools->get_servicename($svc))."</td>";
    $h[]="<td class=\"ak-nowrap\"><div class=\"wafa-cli\"><span class=\"wafa-flag\" id=\"$md-flag\"></span><a href=\"#\" class=\"ak-mono\" title=\"$ipEsc\" onclick=\"event.stopPropagation();Loadjs('$page?zoom-ip-js=$ipJs');return false;\">$ipEsc</a></div><div class=\"ak-muted wafa-xs\" id=\"$md-cty\">&nbsp;</div></td>";
    $h[]="<td class=\"wafa-path\"><span class=\"ak-mono wafa-ell\" title=\"".req_html($path)."\">".req_html($path)."</span></td>";
    $h[]="<td class=\"wafa-r ak-nowrap\"><button type=\"button\" class=\"ak-act\" aria-label=\"{wafa_view_event}\" title=\"{wafa_view_event}\" onclick=\"event.stopPropagation();WafaEvent($id)\"><i class=\"fas fa-eye\" aria-hidden=\"true\"></i></button>$raw</td>";
    $h[]="</tr>";
    return @implode("",$h);
}

function wafa_empty(array $m):string{
    if(!$m["any"]){
        return "<div class=\"ak-empty-c\"><span class=\"ak-ico\"><i class=\"fas fa-shield-check\" aria-hidden=\"true\"></i></span><strong>{wafa_empty_none}</strong></div>";
    }
    return "<div class=\"ak-empty-c\"><span class=\"ak-ico\"><i class=\"fas fa-filter\" aria-hidden=\"true\"></i></span><strong>{wafa_empty_filter}</strong>"
        ."<button type=\"button\" class=\"ak-btn\" onclick=\"WafaSet('reset','')\">{wafa_reset}</button></div>";
}

// Drapeau et pays remplis apres coup : la geolocalisation peut etre lente.
function wafa_fill_trigger(array $m):string{
    if(count($m["events"])==0){return "";}
    $page=CurrentPageName();
    return "setTimeout(function(){Loadjs('$page?wafa-fill=yes');},300);";
}

function wafa_fill_js(){
    header("content-type: application/x-javascript");
    $data=isset($_SESSION["WAFA_FILL"]) && is_array($_SESSION["WAFA_FILL"])?$_SESSION["WAFA_FILL"]:array();
    $modtools=new modesctools();
    $cache=array();
    foreach($data as $md=>$ip){
        if(!preg_match('#^wafa-c[0-9]+$#',$md) || !filter_var($ip,FILTER_VALIDATE_IP)){continue;}
        if(!isset($cache[$ip])){
            $modtools->flag="";$modtools->country_name="";
            $modtools->hostinfo($ip);
            $cache[$ip]=array(strval($modtools->flag),strval($modtools->country_name));
        }
        list($flag,$cty)=$cache[$ip];
        if($flag<>""){
            $img=json_encode("<img src=\"/img/".req_html($flag)."\" alt=\"\">");
            echo "var e=document.getElementById('$md-flag');if(e){e.innerHTML=$img;}\n";
        }
        if($cty<>""){
            echo "var e=document.getElementById('$md-cty');if(e){e.textContent=".json_encode($cty).";}\n";
        }
    }
}

// ---------- reglage des filtres ----------

function wafa_set(){
    header("content-type: application/x-javascript");
    $k=strval($_GET["wafa-set"]);
    $v=isset($_GET["v"])?strval($_GET["v"]):"";
    switch($k){
        case "date":
            $d=intval($v); if($d<0||$d>5){$d=0;}
            $_SESSION["NGINX_AUDIT_DATE"]=$d; break;
        case "level":
            $l=intval($v); if($l<-1||$l>7){$l=-1;}
            $_SESSION["NGINX_AUDIT_LEVEL"]=$l; break;
        case "search":
            $s=trim(mb_substr($v,0,200));
            $_SESSION["NGINX_AUDIT_SEARCH"]=$s; break;
        case "rule-add":
            if(intval($v)>0){$_SESSION["NGINX_AUDIT_RULES"][intval($v)]=true;} break;
        case "rule-del":
            unset($_SESSION["NGINX_AUDIT_RULES"][intval($v)]); break;
        case "site-del":
            unset($_SESSION["NGINX_AUDIT_DST"][intval($v)]); break;
        case "ip-add":
            if(filter_var($v,FILTER_VALIDATE_IP)){$_SESSION["NGINX_AUDIT_SRC"][$v]=true;} break;
        case "ip-del":
            if(filter_var($v,FILTER_VALIDATE_IP)){unset($_SESSION["NGINX_AUDIT_SRC"][$v]);} break;
        case "reset":
            foreach(array("NGINX_AUDIT_RULES","NGINX_AUDIT_DST","NGINX_AUDIT_SRC","NGINX_AUDIT_DATE","NGINX_AUDIT_LEVEL","NGINX_AUDIT_SEARCH") as $s){unset($_SESSION[$s]);}
            break;
        default:
            return;
    }
    echo "WafaRefresh();";
}

// ---------- panneau lateral d'un evenement ----------

function wafa_drawer_shell():string{
    return "<div id=\"wafa-drawer\" class=\"wafa-dw\" hidden><div class=\"wafa-dw-bg\" onclick=\"WafaClose()\"></div>"
        ."<aside class=\"wafa-dw-panel\" role=\"dialog\" aria-modal=\"true\" aria-labelledby=\"wafa-dtitle\" tabindex=\"-1\"><div id=\"wafa-dbody\"></div></aside></div>";
}

function wafa_event_model($id):array{
    $r=wafa_rows("SELECT modsecurity_audit.*, host(modsecurity_audit.ipaddr) AS ip, modsecurity_messages.explain AS subject
        FROM modsecurity_audit LEFT JOIN modsecurity_messages ON modsecurity_messages.id=modsecurity_audit.msgid WHERE modsecurity_audit.id=".intval($id));
    if(!isset($r[0])){return array();}
    $e=$r[0];
    $modtools=new modesctools();
    $ruleid=intval($e["ruleid"]);
    $svc=intval($e["serviceid"]);
    $e["title"]=wafa_rule_title($ruleid,$e["subject"]);
    $e["site"]=$modtools->get_servicename($svc);
    $e["explain"]=intval($e["explainid"])>0?strval($modtools->modsecurity_explains(intval($e["explainid"]))):"";
    $e["path"]=$modtools->PathFromEventID(intval($id));
    $e["count_all"]=$modtools->CountOfOccurence($ruleid);
    $e["count_site"]=$svc>0?$modtools->CountOfOccurenceSite($ruleid,$svc):0;
    $e["w_global"]=$modtools->rule_is_global_white($ruleid);
    $e["w_site"]=$svc>0?$modtools->rule_is_serviceid_white($ruleid,$svc):false;
    $e["w_path"]=($svc>0 && $e["path"]<>"")?(bool)$modtools->rule_is_path_white($ruleid,$svc,$e["path"]):false;
    $e["hourly"]=wafa_rows(wafa_hourly_sql("AND ruleid=$ruleid"));
    $e["clients"]=wafa_rows("SELECT host(ipaddr) AS ipaddr, count(*) AS c FROM modsecurity_audit WHERE ruleid=$ruleid GROUP BY ipaddr ORDER BY c DESC LIMIT 5");
    $e["report"]="";
    if(intval($e["reportid"])>0){
        $rr=wafa_rows("SELECT report FROM modsecurity_reports WHERE id=".intval($e["reportid"]));
        if(isset($rr[0]["report"]) && strlen(strval($rr[0]["report"]))>=10){$e["report"]=strval(base64_decode(strval($rr[0]["report"])));}
    }
    $modtools->flag="";$modtools->country_name="";
    $modtools->hostinfo(strval($e["ip"]));
    $e["flag"]=strval($modtools->flag);
    $e["country"]=strval($modtools->country_name);
    return $e;
}

function wafa_event(){
    $tpl=new template_admin();
    $id=intval($_GET["wafa-event"]);
    $e=wafa_event_model($id);
    if(count($e)==0){
        echo $tpl->_ENGINE_parse_body(wafa_dw_head_empty());
        return;
    }
    $h=array();
    $h[]=wafa_dw_head($e);
    $h[]="<div class=\"wafa-dw-scroll\">";
    $h[]=wafa_dw_request($e);
    $h[]=wafa_dw_reason($e);
    $h[]=wafa_dw_status($e);
    $h[]=wafa_dw_frequency($e);
    $h[]=wafa_dw_clients($e);
    $h[]=wafa_dw_raw($e);
    $h[]="</div>";
    $h[]="<div class=\"wafa-dw-foot\"><button type=\"button\" class=\"ak-btn\" onclick=\"WafaClose()\">{wafa_close}</button></div>";
    $anchor=isset($_GET["anchor"]) && $_GET["anchor"]=="raw";
    echo $tpl->_ENGINE_parse_body(@implode("",$h))
        ."<script>WafaAfter();".($anchor?"var x=document.getElementById('wafa-raw');if(x){x.scrollIntoView();}":"")."</script>";
}

function wafa_dw_head_empty():string{
    return "<div class=\"wafa-dw-head\"><h3 id=\"wafa-dtitle\">{wafa_no_event}</h3><button type=\"button\" class=\"ak-act wafa-x\" aria-label=\"{wafa_close}\" onclick=\"WafaClose()\"><i class=\"fas fa-xmark\" aria-hidden=\"true\"></i></button></div>";
}

function wafa_dw_head(array $e):string{
    $r=intval($e["ruleid"]);
    return "<div class=\"wafa-dw-head\"><div><div class=\"wafa-dw-meta\">".wafa_sev_pill($e["severity"])."<span class=\"ak-mono ak-muted\">".wafa_tr("wafa_rule_n",$r)."</span></div>"
        ."<h3 id=\"wafa-dtitle\">".req_html($e["title"])."</h3><div class=\"ak-mono ak-muted wafa-xs\">ID: #".intval($e["id"])."</div></div>"
        ."<button type=\"button\" class=\"ak-act wafa-x\" aria-label=\"{wafa_close}\" onclick=\"WafaClose()\"><i class=\"fas fa-xmark\" aria-hidden=\"true\"></i></button></div>";
}

function wafa_dw_request(array $e):string{
    $ts=strtotime(strval($e["created"]));
    $date=$ts?date("Y-m-d H:i:s",$ts):req_html($e["created"]);
    $flag=$e["flag"]<>""?"<img src=\"/img/".req_html($e["flag"])."\" alt=\"\"> ":"";
    $cty=$e["country"]<>""?" <span class=\"ak-muted\">(".req_html($e["country"]).")</span>":"";
    $page=CurrentPageName();
    $ip=req_html($e["ip"]);
    $url=req_html($e["mainurl"]);
    return "<section class=\"wafa-dsec\"><h4>{wafa_req}</h4><div class=\"wafa-box\"><dl class=\"wafa-dl\">"
        ."<dt>{wafa_timestamp}</dt><dd class=\"ak-mono\">$date</dd>"
        ."<dt>{wafa_target}</dt><dd>".req_html($e["site"])."</dd>"
        ."<dt>{wafa_client_ip}</dt><dd>$flag<a href=\"#\" class=\"ak-mono\" onclick=\"Loadjs('$page?zoom-ip-js=".urlencode(strval($e["ip"]))."');return false;\">$ip</a>$cty</dd>"
        ."</dl><div class=\"wafa-urlh\"><span>{wafa_url}</span><button type=\"button\" class=\"ak-link\" onclick=\"WafaCopy('wafa-url',this)\"><span class=\"wafa-bi\"><i class=\"fas fa-copy\" aria-hidden=\"true\"></i></span>{wafa_copy}</button></div>"
        ."<div class=\"wafa-url ak-mono\" id=\"wafa-url\">$url</div></div></section>";
}

function wafa_dw_reason(array $e):string{
    $subject=strval($e["subject"]);
    $lines=array();
    if($subject<>"" && $subject<>"none"){$lines[]="<p>".req_html($subject)."</p>";}
    // modsecurity_explains() rend deja du texte encode (htmlentities).
    if($e["explain"]<>""){$lines[]="<p class=\"ak-mono wafa-xs\">".$e["explain"]."</p>";}
    if(count($lines)==0){return "";}
    return "<section class=\"wafa-dsec\"><h4>{wafa_reason}</h4><div class=\"wafa-reason\"><span class=\"wafa-bi\"><i class=\"fas fa-triangle-exclamation\" aria-hidden=\"true\"></i></span><div>".@implode("",$lines)."</div></div></section>";
}

function wafa_scope_row($name,$active,$sub,$btnLabel,$js,$disabled=false):string{
    $pill=$active?"<span class=\"ak-pill wafa-ok wafa-sm\">{wafa_st_active}</span>":"<span class=\"ak-pill wafa-neu wafa-sm\">{wafa_st_disabled}</span>";
    $btn=$disabled?"<button type=\"button\" class=\"ak-btn\" disabled>$btnLabel</button>"
        :"<button type=\"button\" class=\"ak-btn\" onclick=\"$js\">$btnLabel</button>";
    return "<div class=\"wafa-scope\"><div><div class=\"wafa-sn\"><span>$name</span>$pill</div><div class=\"ak-muted wafa-xs\">$sub</div></div>$btn</div>";
}

function wafa_dw_status(array $e):string{
    $page=CurrentPageName();
    $r=intval($e["ruleid"]);$svc=intval($e["serviceid"]);$id=intval($e["id"]);
    $base="Loadjs('$page?wafa-wl=%s&rule=$r&svc=$svc&ev=$id')";
    $rows=array();
    $g=$e["w_global"];
    $rows[]=wafa_scope_row("{wafa_scope_all}",!$g,wafa_tr("wafa_det_all",wafa_num($e["count_all"])),
        $g?"{wafa_en_all}":"{wafa_dis_all}",sprintf($base,$g?"gdel":"gadd"));
    if($svc>0){
        $s=$e["w_site"];
        $rows[]=wafa_scope_row(req_html($e["site"]),!$s && !$g,$g?"{wafa_covered}":wafa_tr("wafa_det_site",wafa_num($e["count_site"])),
            $s?"{wafa_en_site}":"{wafa_dis_site}",sprintf($base,$s?"sdel":"sadd"),$g);
        if($e["path"]<>""){
            $p=$e["w_path"];
            $rows[]=wafa_scope_row("<span class=\"ak-mono\">".req_html($e["path"])."</span>",!$p && !$g && !$s,($g||$s)?"{wafa_covered}":"{wafa_path_hint}",
                $p?"{wafa_en_path}":"{wafa_dis_path}",sprintf($base,$p?"pdel":"padd"),$g||$s);
        }
    }
    return "<section class=\"wafa-dsec\"><div class=\"wafa-dsh\"><h4>{wafa_status}</h4><span class=\"ak-mono ak-muted wafa-xs\">#$r</span></div><div class=\"wafa-box wafa-scopes\">".@implode("",$rows)."</div></section>";
}

function wafa_dw_frequency(array $e):string{
    $vals=array();$sum=0;
    foreach($e["hourly"] as $l){$vals[]=intval($l["c"]);$sum+=intval($l["c"]);}
    if(count($vals)==0){return "";}
    return "<section class=\"wafa-dsec\"><div class=\"wafa-dsh\"><span class=\"ak-muted\">{wafa_freq}</span><span class=\"ak-mono\">".wafa_tr("wafa_n_det",wafa_num($sum))."</span></div>"
        ."<div class=\"wafa-sparkbox\"><span class=\"wafa-spark\" data-h=\"30\">".@implode(",",$vals)."</span></div></section>";
}

function wafa_dw_clients(array $e):string{
    $f=wafa_filters();
    $rows=array();
    $modtools=new modesctools();
    foreach($e["clients"] as $l){
        $ip=strval($l["ipaddr"]);
        if(!filter_var($ip,FILTER_VALIDATE_IP)){continue;}
        $modtools->flag="";$modtools->country_name="";
        $modtools->hostinfo($ip);
        $flag=strlen(strval($modtools->flag))>0?"<img src=\"/img/".req_html($modtools->flag)."\" alt=\"\">":"";
        $cty=$modtools->country_name<>""?"<span class=\"ak-muted\">(".req_html($modtools->country_name).")</span>":"";
        $on=in_array($ip,$f["ips"]);
        $btn=$on?"<span class=\"ak-muted wafa-xs\">{wafa_filtered}</span>"
            :"<button type=\"button\" class=\"ak-link\" onclick=\"WafaSet('ip-add','".req_html($ip)."')\">{wafa_filter}</button>";
        $rows[]="<div class=\"wafa-crow\"><span class=\"wafa-flag\">$flag</span><span class=\"ak-mono\">".req_html($ip)."</span>$cty<span class=\"ak-mono wafa-cnt\">".wafa_tr("wafa_hits",wafa_num($l["c"]))."</span>$btn</div>";
    }
    if(count($rows)==0){return "";}
    return "<section class=\"wafa-dsec\"><h4>{wafa_top_clients}</h4><div class=\"wafa-box\">".@implode("",$rows)."</div></section>";
}

function wafa_dw_raw(array $e):string{
    if($e["report"]==""){return "";}
    return "<section class=\"wafa-dsec\" id=\"wafa-raw\"><div class=\"wafa-dsh\"><h4>{wafa_raw}</h4><button type=\"button\" class=\"ak-link\" onclick=\"WafaCopy('wafa-rawpre',this)\"><span class=\"wafa-bi\"><i class=\"fas fa-copy\" aria-hidden=\"true\"></i></span>{wafa_copy_raw}</button></div>"
        ."<pre class=\"wafa-pre\" id=\"wafa-rawpre\">".req_html($e["report"])."</pre></section>";
}

// Activer / desactiver une regle a un des trois niveaux. Le chemin est relu cote
// serveur depuis l'evenement, jamais pris dans la requete.
function wafa_wl(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $op=strval($_GET["wafa-wl"]);
    $rule=intval($_GET["rule"]);
    $svc=intval($_GET["svc"]);
    $ev=intval($_GET["ev"]);
    $modtools=new modesctools();
    $ok=false;
    switch($op){
        case "gadd": $ok=$modtools->whitelist_global_rule($rule); break;
        case "gdel": $ok=$modtools->unwhite_global($rule); break;
        case "sadd": $ok=$modtools->whitelist_serviceid_rule($rule,$svc); break;
        case "sdel": $ok=$modtools->unwhite_serviceid($rule,$svc); break;
        case "padd": $ok=$modtools->whitelist_path_rule($rule,$svc,$modtools->PathFromEventID($ev)); break;
        case "pdel": $ok=$modtools->unwhite_path($rule,$svc,$modtools->PathFromEventID($ev)); break;
        default: return;
    }
    if(!$ok){
        echo "alert(".json_encode(strval($modtools->mysql_error)).");";
        return;
    }
    echo "WafaEvent($ev);WafaRefresh();";
}

function wafa_accents():array{
    $a=array("accent"=>"#0f766e","accent_dark"=>"#0d9488","accent_soft"=>"rgba(15,118,110,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        $a["accent"]=$b["accent_dark"];$a["accent_dark"]=$b["accent"];$a["accent_soft"]=$b["accent_soft"];
    }
    return $a;
}

// Feuille de la page : concatenee HORS de _ENGINE_parse_body().
function wafa_style():string{
    $a=wafa_accents();$ac=$a["accent"];$soft=$a["accent_soft"];
    $f=array();
    $f[]="<style>";
    $f[]=".wafa{--wb:#e4e7ec;--wt:#101828;--wm:#475467;--wbg:#f5f7fa;--wac:$ac;--was:$soft;color:var(--wt);font-size:13px}";
        $f[]=".wafa-xs{font-size:12px}.wafa-acc{color:var(--wac);font-weight:600}";
    $f[]=".wafa-ell{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}.wafa-r{text-align:right}";
    $f[]=".wafa-bi{display:inline-flex;width:14px;height:14px;align-items:center;justify-content:center;flex:0 0 14px}";
    $f[]=".wafa-pi{display:inline-flex;width:12px;height:12px;align-items:center;justify-content:center;flex:0 0 12px}";
    $f[]=".wafa button:focus-visible,.wafa a:focus-visible,.wafa select:focus-visible,.wafa input:focus-visible,.wafa tr:focus-visible,.wafa-dw-panel:focus-visible{outline:2px solid var(--wac);outline-offset:2px}";
    // bandeau moteur arrete
    $f[]=".wafa-banner{padding:12px 16px;margin-bottom:16px;font-weight:500}";
    // barre de filtres
    $f[]=".wafa-fbar{padding:12px 16px;margin-bottom:16px}";
    $f[]=".wafa-frow{display:flex;flex-wrap:wrap;gap:8px;align-items:center}";
    $f[]=".wafa-sep{width:1px;height:24px;background:var(--wb);margin:0 4px}";
    $f[]=".wafa-sel,.wafa-pick{height:34px;border:1px solid var(--wb);border-radius:6px;background:#fff;color:var(--wt);font-size:13px;padding:0 10px}";
    $f[]=".wafa-pick{display:inline-flex;gap:6px;align-items:center;cursor:pointer;font-weight:500}";
    $f[]=".wafa-pick.on{border-color:var(--wac);background:var(--was)}";
    $f[]=".wafa-car{color:var(--wm)}";
    $f[]=".wafa-badge{display:inline-flex;min-width:18px;height:18px;padding:0 5px;border-radius:9px;background:var(--wac);color:#fff;font-size:11px;font-weight:600;align-items:center;justify-content:center}";
    $f[]=".wafa-search{height:34px;flex:1 1 180px;max-width:280px}.wafa-search input{font-size:13px;font-family:'JetBrains Mono',ui-monospace,monospace}";
    $f[]=".wafa-chips{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #f2f4f7}";
    $f[]=".wafa-lbl{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.02em;color:var(--wm)}";
    $f[]=".wafa-chip{display:inline-flex;align-items:center;gap:6px;background:var(--was);border:1px solid var(--wb);border-radius:999px;padding:3px 6px 3px 10px;font-size:12px}";
    $f[]=".wafa-chip button{border:0;background:transparent;color:var(--wm);cursor:pointer;width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%}";
    $f[]=".wafa-chip button:hover{background:rgba(16,24,40,.08)}";
    // tuiles
    $f[]=".wafa-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:16px}";
    $f[]="@media (max-width:1200px){.wafa-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}";
    $f[]=".wafa-tile{padding:14px 16px;min-height:128px;display:flex;flex-direction:column;gap:6px;min-width:0}";
    $f[]=".wafa-th{display:flex;justify-content:space-between;align-items:center;gap:8px}";
    $f[]=".wafa-tt{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.02em;color:var(--wm)}";
    $f[]=".wafa-big{font-size:28px;font-weight:600;line-height:1.1}";
    $f[]=".wafa-sub{display:flex;justify-content:space-between;font-size:12px;color:var(--wm)}";
    $f[]=".wafa-sparkbox{margin-top:auto;min-height:30px;width:100%}.wafa-sparkbox svg{display:block;width:100%}";
    $f[]=".wafa-stack{display:flex;height:10px;border-radius:5px;overflow:hidden;background:#f2f4f7;margin:8px 0 6px}";
    $f[]=".wafa-stack span{display:block;height:100%}";
    $f[]=".wafa-seg-crit{background:#b42318}.wafa-seg-err{background:#d92d20}.wafa-seg-warn{background:#dc6803}.wafa-seg-info{background:#1570ef}.wafa-seg-neu{background:#667085}";
    $f[]=".wafa-legend{display:flex;flex-wrap:wrap;gap:6px 12px}";
    $f[]=".wafa-leg{display:inline-flex;gap:5px;align-items:center;font-size:12px;background:transparent!important;border:0!important;padding:0!important}";
    $f[]=".wafa-trow{display:flex;gap:8px;align-items:center;width:100%;border:0;background:transparent;padding:4px 6px;margin:0 -6px;border-radius:6px;text-align:left;cursor:pointer;font-size:13px;color:var(--wt);width:calc(100% + 12px)}";
    $f[]=".wafa-trow:hover{background:#f9fafb}";
    $f[]=".wafa-cnt{margin-left:auto;font-weight:600;padding-left:8px}";
    $f[]=".wafa-flag{display:inline-flex;width:20px;flex:0 0 20px;align-items:center}.wafa-flag img{width:18px;height:auto}";
    $f[]=".wafa-none{margin-top:8px}";
    // pastilles
    $f[]=".ak-pill.wafa-sm{padding:1px 7px;font-size:12px;margin-left:6px;font-weight:600}";
    $f[]=".wafa-crit{background:#fef3f2;color:#b42318}.wafa-err{background:#fef3f2;color:#d92d20}.wafa-warn{background:#fffaeb;color:#b54708}";
    $f[]=".wafa-info{background:#eff8ff;color:#175cd3}.wafa-neu{background:#f2f4f7;color:#475467}.wafa-ok{background:#ecfdf3;color:#027a48}";
    // tableau
    $f[]=".wafa-evs{overflow:hidden}";
    $f[]=".wafa-evh{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid var(--wb)}";
    $f[]=".wafa-evh h2{display:inline;font-size:16px;font-weight:600;margin:0 10px 0 0}";
    $f[]=".wafa-scroll{overflow-x:auto}";
    $f[]=".wafa-tbl{width:100%;border-collapse:collapse;table-layout:fixed}";
    $f[]=".wafa-tbl th{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.02em;color:var(--wm);text-align:left;padding:10px 12px;background:#f9fafb;border-bottom:1px solid var(--wb)}";
    $f[]=".wafa-tbl th:nth-child(1){width:150px}.wafa-tbl th:nth-child(2){width:120px}.wafa-tbl th:nth-child(4){width:16%}.wafa-tbl th:nth-child(5){width:170px}.wafa-tbl th:nth-child(7){width:92px}";
    $f[]=".wafa-tbl td{padding:8px 12px;border-bottom:1px solid #f2f4f7;vertical-align:middle;color:var(--wt)}";
    $f[]=".wafa-tbl tbody tr{cursor:pointer;height:52px}.wafa-tbl tbody tr:hover{background:#f9fafb}";
    $f[]=".wafa-rule>div:first-child{display:flex;align-items:center}";
    $f[]=".wafa-rule .wafa-ell,.wafa-path .wafa-ell{display:block}";
    $f[]=".wafa-tbl td{overflow:hidden}";
    $f[]=".wafa-cli{display:flex;align-items:center;gap:4px;min-width:0}.wafa-cli a{color:var(--wac);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}";
    $f[]=".wafa-trow .ak-mono{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;flex:0 1 auto}.wafa-trow .wafa-cnt,.wafa-trow .wafa-acc{flex:0 0 auto}";
    $f[]=".wafa-dl a,.wafa-crow a{color:var(--wac)}";
    // panneau lateral
    $f[]=".wafa-dw{position:fixed;inset:0;z-index:1039}";
    $f[]=".wafa-dw-bg{position:absolute;inset:0;background:rgba(16,24,40,.35)}";
    $f[]=".wafa-dw-panel{position:absolute;top:0;right:0;bottom:0;width:560px;max-width:100%;background:#fff;display:flex;flex-direction:column;box-shadow:-8px 0 24px rgba(16,24,40,.18);outline:0;animation:wafaIn .18s ease-out;color:var(--wt);font-size:13px}";
    $f[]="@keyframes wafaIn{from{transform:translateX(24px);opacity:.4}to{transform:none;opacity:1}}";
    $f[]="@media (prefers-reduced-motion:reduce){.wafa-dw-panel{animation:none}}";
    $f[]="#wafa-dbody{display:flex;flex-direction:column;min-height:0;flex:1}";
    $f[]=".wafa-dw-load{height:4px;background:var(--was)}";
    $f[]=".wafa-dw-head{display:flex;justify-content:space-between;gap:12px;padding:20px 24px 16px;border-bottom:1px solid var(--wb);background:#fcfcfd}";
    $f[]=".wafa-dw-head h3{font-size:20px;font-weight:600;margin:8px 0 4px;line-height:1.25}";
    $f[]=".wafa-dw-meta{display:flex;gap:10px;align-items:center}";
    $f[]=".wafa-x{flex:0 0 32px}";
    $f[]=".wafa-dw-scroll{flex:1;overflow-y:auto;padding:8px 24px 24px}";
    $f[]=".wafa-dsec{margin-top:18px}";
    $f[]=".wafa-dsec h4{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--wt);margin:0 0 8px}";
    $f[]=".wafa-dsh{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px}.wafa-dsh h4{margin:0}";
    $f[]=".wafa-box{border:1px solid var(--wb);border-radius:10px;background:#fcfcfd;padding:12px 16px}";
    $f[]=".wafa-dl{display:grid;grid-template-columns:150px 1fr;gap:8px 12px;margin:0}.wafa-dl dt{font-weight:400;color:var(--wm)}.wafa-dl dd{margin:0;word-break:break-all}";
    $f[]=".wafa-dl img{width:18px;vertical-align:-2px}";
    $f[]=".wafa-urlh{display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:10px;border-top:1px solid var(--wb);color:var(--wm)}";
    $f[]=".wafa-url{margin-top:6px;padding:8px 10px;border:1px solid var(--wb);border-radius:6px;background:#fff;word-break:break-all;white-space:pre-wrap}";
    $f[]=".wafa-reason{display:flex;gap:10px;padding:12px 14px;border:1px solid #fedf89;background:#fffaeb;border-radius:10px;color:#7a2e0e}";
    $f[]=".wafa-reason p{margin:0 0 6px}.wafa-reason p:last-child{margin:0}.wafa-reason .wafa-bi{color:#dc6803;margin-top:2px}";
    $f[]=".wafa-scopes{padding:0}";
    $f[]=".wafa-scope{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f2f4f7}.wafa-scope:last-child{border-bottom:0}";
    $f[]=".wafa-sn{display:flex;align-items:center;gap:4px;font-weight:500;word-break:break-all}";
    $f[]=".wafa-crow{display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f2f4f7}.wafa-crow:last-child{border-bottom:0}";
    $f[]=".wafa-pre{background:#101828;color:#e4e7ec;border-radius:10px;padding:14px;font-size:12px;max-height:420px;overflow:auto;white-space:pre-wrap;word-break:break-all;border:0;margin:0}";
    $f[]=".wafa-dw-foot{display:flex;justify-content:flex-end;padding:12px 24px;border-top:1px solid var(--wb);background:#fcfcfd}";
    $f[]="</style>";
    return @implode("\n",$f);
}

function wafa_js():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $a=wafa_accents();
    $copied=$tpl->javascript_parse_text("{wafa_copied}");
    $ac=$a["accent"];
    return "<script src='js/jquery.peity.min.js'></script>
<script>
(function(){
var P='$page', ACC='$ac', COPIED='$copied';
window.WafaRefresh=function(){ if(!document.getElementById('wafa-root')){return;} LoadAjaxSilent('wafa-root',P+'?wafa-content=yes'); };
window.WafaSet=function(k,v){ Loadjs(P+'?wafa-set='+encodeURIComponent(k)+'&v='+encodeURIComponent(v)); };
window.WafaSearchKey=function(ev){ if(ev.key==='Enter'){ ev.preventDefault(); WafaSet('search',ev.target.value); } };
window.WafaPeity=function(){
    if(!window.jQuery || !jQuery.fn.peity){ return; }
    jQuery('.wafa-spark').each(function(){
        var e=jQuery(this); if(e.data('wafaDone')){ return; } e.data('wafaDone',1);
        var h=parseInt(e.attr('data-h'),10)||30, w=Math.max(120,Math.floor(e.parent().width()||240));
        e.peity('bar',{width:w,height:h,padding:0.2,fill:function(v){ return v>0?ACC:'#e4e7ec'; }});
    });
};
window.WafaAfter=function(){ if(typeof NoSpinner==='function'){ NoSpinner(); } WafaPeity(); };
window.WafaEvent=function(id,anchor){
    var d=document.getElementById('wafa-drawer'); if(!d){ return; }
    if(d.hidden){ window.WafaLastFocus=document.activeElement; d.hidden=false; }
    document.getElementById('wafa-dbody').innerHTML='<div class=\"wafa-dw-load\"></div>';
    LoadAjaxSilent('wafa-dbody',P+'?wafa-event='+parseInt(id,10)+(anchor?'&anchor='+anchor:''));
    setTimeout(function(){ var p=d.querySelector('.wafa-dw-panel'); if(p){ p.focus(); } },60);
};
window.WafaClose=function(){
    var d=document.getElementById('wafa-drawer'); if(!d || d.hidden){ return; }
    d.hidden=true; document.getElementById('wafa-dbody').innerHTML='';
    if(window.WafaLastFocus && window.WafaLastFocus.focus){ try{ window.WafaLastFocus.focus(); }catch(e){} }
};
window.WafaCopy=function(id,btn){
    var el=document.getElementById(id); if(!el){ return; }
    var t=el.textContent;
    var done=function(){ if(!btn){ return; } var o=btn.getAttribute('data-o')||btn.innerHTML; btn.setAttribute('data-o',o); btn.textContent=COPIED; setTimeout(function(){ btn.innerHTML=o; },1500); };
    var fallback=function(){ var ta=document.createElement('textarea'); ta.value=t; ta.style.position='fixed'; ta.style.opacity='0'; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); done(); }catch(e){} document.body.removeChild(ta); };
    if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(t).then(done,fallback); }else{ fallback(); }
};
if(!window.WafaKeys){
    window.WafaKeys=1;
    document.addEventListener('keydown',function(ev){
        if(ev.key!=='Escape'){ return; }
        if(document.querySelector('.modal.in,.modal.show')){ return; }
        if(window.WafaClose){ WafaClose(); }
    });
}
WafaRefresh();
})();
</script>";
}
