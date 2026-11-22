<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["ID"])){dnsrules_save();exit;}
if(isset($_GET["search-form"])){search_squid();exit;}
if(isset($_GET["search-proxy"])){search_proxy();exit();}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["dns-params"])){dns_params();exit;}
if(isset($_GET["dns-rules"])){dnsrules_start();exit;}
if(isset($_GET["dns-rules-list"])){dnsrules_table();exit;}
if(isset($_GET["dns-rules-id"])){dnsrules_js();exit;}
if(isset($_GET["dns-rules-popup"])){dnsrules_popup();exit;}
if(isset($_GET["dns-rules-delete"])){dnsrules_delete_js();exit;}
if(isset($_GET["proxy-dns-hacluster-btns"])){dnsrules_btns();exit;}
if(isset($_POST["dns-rules-delete"])){dnsrules_delete_confirm();exit;}
if(isset($_GET["dns-rules-enabled"])){dnsrules_enable();exit;}
if(isset($_GET["statistics"])){statistics();exit;}
if(isset($_GET["dns-graph-queries"])){statistics_chart_dns();exit;}
if(isset($_GET["dns-graph-queries-table"])){statistics_chart_dns_table();exit;}
page();

function dns_params(){
    $html[]="<div id='dns-used-proxy' style='margin-top:20px'></div>";
    $html[]="<script>LoadAjax('dns-used-proxy','fw.proxy.dns.servers.php');</script>";
    echo @implode("\n",$html);
}

function tabs(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));

    $array["{cache_parameters}"]="fw.proxy.general.php?dns=yes&tiny=yes";

    if($DoNotUseLocalDNSCache==1) {
        $array["{APP_PROXY}: {dns_settings}"] = "$page?dns-params=yes";
        $array["DNS: {rules}"] = "$page?dns-rules=yes";
    }
    $array["{DNS_RECORDS}"] = "$page?search-form=yes";

    echo $tpl->tabs_default($array);
}
function dnsrules_enable():bool{
    $ID=intval($_GET["dns-rules-enabled"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/hacluster/client/dns/backend/rules/$ID/toggle", new stdClass()));
    if(isset($json->data->enabled)){
        $state=intval($json->data->enabled)==1?"enabled":"disabled";
        admin_tracks("Set Proxy DNS rule $ID to $state");
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/dns/backend/restart");
    return true;
}
function dnsrules_delete_js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["dns-rules-delete"]);
    $md=$_GET["md"];
    return $tpl->js_confirm_delete("{rule} #$ID","dns-rules-delete",$ID,"$('#$md').remove();");
}
function dnsrules_delete_confirm():bool{
    $ID=intval($_POST["dns-rules-delete"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/hacluster/client/dns/backend/rules/$ID"));
    if(!isset($json->success) || !$json->success){
        $err=isset($json->error)?$json->error:"unknown error";
        $tpl=new template_admin();
        echo $tpl->post_error($err);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/dns/backend/restart");
    return admin_tracks("Removed Proxy DNS rule $ID");
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{dns_settings}",ico_database,"{dns_settings_proxy_explain}",
        "$page?tabs=yes","proxy-dns-cache","proxydns-progress");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function dnsrules_popup(){
    $ID=intval($_GET["dns-rules-popup"]);
    // Ce nom part directement dans le $jsafter de la modale.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl=new template_admin();
    $jsafter="dialogInstance1.close();$function();";
    if($ID>0){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/dns/backend/rules/$ID"));
        if(isset($json->success) && $json->success){
            $ligne["sitename"]=$json->data->sitename;
            $ligne["dnsservers"]=$json->data->dnsservers;
            $ligne["enabled"]=intval($json->data->enabled);
        }else{
            $ligne["enabled"]=1;
        }
        $title="{rule} $ID";
        $btname="{apply}";
    }else{
        $ligne["enabled"]=1;
        $ligne["sitename"]="";
        $ligne["dnsservers"]="";
        $title="{new_rule}";
        $btname="{add}";
    }
    $form[]=$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],"sitename,dnsservers");
    $form[]=$tpl->field_textareacode("sitename","{domains}",$ligne["sitename"],"100%","100px");
    $form[]=$tpl->field_textareacode("dnsservers","{dns_servers}",$ligne["dnsservers"],"100%","75px");
    echo $tpl->form_outside($title,$form,null,$btname,$jsafter);
}
function dnsrules_save():bool{
    $tpl=new template_admin();

    $tpl->CLEAN_POST();
    $ackdoms=str_replace("\n",", ",$_POST['sitename']);
    $ackdns=str_replace("\n",", ",$_POST['dnsservers']);
    $ID=intval($_POST["ID"]);
    $data=array(
        "sitename"=>$_POST['sitename'],
        "dnsservers"=>$_POST['dnsservers'],
        "enabled"=>intval($_POST['enabled'])
    );
    if($ID==0){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/hacluster/client/dns/backend/rules", $data));
        $atrck="Create a new Proxy DNS rule for $ackdoms using DNS servers $ackdns";
    }else{
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/hacluster/client/dns/backend/rules/$ID", $data));
        $atrck="Update Proxy DNS rule $ID for $ackdoms using DNS servers $ackdns";
    }
    if(!isset($json->success) || !$json->success){
        $err=isset($json->error)?$json->error:"unknown error";
        echo $tpl->post_error($err);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/dns/backend/restart");
    admin_tracks($atrck);
    return true;
}

function dnsrules_js():bool{
    $ID=intval($_GET["dns-rules-id"]);
    // Ce nom est propage dans l'URL de la modale, qui le remet dans un $jsafter.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $page=CurrentPageName();
    $tpl=new template_admin();

    if($ID==0) {
        $tpl->js_dialog1("{new_rule}","$page?dns-rules-popup=$ID&function=$function");
        return true;
    }
    return $tpl->js_dialog1("{rule}: $ID","$page?dns-rules-popup=$ID&function=$function");

}

function dnsrules_start():bool{
    $page=CurrentPageName();
    $addon="";
    if(isset($_GET["hacluster"])){
        $addon="&hacluster=yes";
    }
    $tpl=new template_admin();
    echo "<div style='margin-top:5px;margin-bottom:5px' id='proxy-dns-hacluster-btns'></div>";
    echo $tpl->search_block($page,"","","","&dns-rules-list=yes$addon");

    return true;
}
function dnsrules_btns():bool{
    // Meme durcissement que dnsrules_table(): ce nom part dans un OnClick.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons[]=array("Loadjs('$page?dns-rules-id=0&function=$function');",
        ico_plus,"{new_rule}");
    echo $tpl->th_buttons($topbuttons);
    return true;
}
function dnsrules_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $addon="";
    $hacluster=false;
    if(isset($_GET["hacluster"])){
        $hacluster=true;
        $addon="&hacluster=yes";
    }
    if($hacluster){
        $HaClusterProxyUseBackendDNSDIST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseBackendDNSDIST"));
        $HaClusterProxyUseOwnDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseOwnDNS"));
        if($HaClusterProxyUseOwnDNS==0){
            $HaClusterProxyUseBackendDNSDIST=0;
        }
        if($HaClusterProxyUseBackendDNSDIST==0){
            echo $tpl->_ENGINE_parse_body($tpl->div_warning("{use_local_dns_service}||{dnsrules_only_dnsdist}"));
            return true;
        }
    }

    // Ce nom de fonction JavaScript est reinjecte dans des attributs OnClick.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/dns/backend/rules"));
    $results=array();
    if(isset($json->success) && $json->success && is_array($json->data)){
        $results=$json->data;
    }
    // La saisie servait DIRECTEMENT de motif a preg_match: un "#" rompait le
    // delimiteur (avertissement PHP, aucun filtrage) et une construction comme
    // "(a+)+b" ouvrait un deni de service par retour arriere. preg_quote la
    // ramene a une recherche litterale, qui est ce que l'utilisateur attend.
    $search=preg_quote(trim((string)($_GET["search"] ?? "")),"#");

    $rows=array();
    foreach ($results as $ligne) {
        if(strlen($search)>1){
            if(!preg_match("#$search#i",serialize($ligne))){continue;}
        }
        $rows[]=dnsrules_row($ligne,$page,$addon,$tpl);
    }
    $shown=count($rows);

    $jsafter=$tpl->framework_buildjs(
        "/hacluster/client/dns/backend/restart",
        "HaClusterBackendDNS.progress",
        "HaClusterBackendDNS.log",
        "proxydns-progress");

    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?dns-rules-id=0&function=$function');",
        ico_plus,"{new_rule}");
    $topbuttons[] = array($jsafter, ico_retweet, "{apply config}");

    $TINY_ARRAY["TITLE"]="{dns_forwarding_rules}";
    $TINY_ARRAY["ICO"]="far fa-exchange";
    $TINY_ARRAY["EXPL"]="{forward_rules_dns_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);

    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    if($hacluster){
        $headsjs="LoadAjaxSilent('proxy-dns-hacluster-btns','$page?proxy-dns-hacluster-btns=yes&function=$function');";
    }

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{source}</th>";
    $html[]="<th>{dns_servers}</th>";
    $html[]="<th class='center'>{enabled}</th>";
    $html[]="<th class='center'>{delete}</th>";
    $html[]="</tr></thead><tbody id='pxdns-rules-rows'>";
    if($shown==0){
        $html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $shown</span>";
    $html[]="<span class='awc-pg' id='pxdns-rules-pager'></span>";
    $html[]="</div></div></div>";

    // Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n$headsjs\n</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(50,"pxdns-rules-rows","pxdns-rules-pager");
    return true;
}

/**
 * Une regle de redirection DNS. La premiere colonne ne contenait qu'une icone
 * et son balisage etait casse -- "<i class='fa-server'</td>", sans ">" ni
 * fermeture: le navigateur avalait la cellule suivante. Elle devient la pastille
 * d'identite de la source.
 */
function dnsrules_row($ligne,$page,$addon,$tpl):string{
    $md=md5(json_encode($ligne));
    $ID=intval($ligne->ID);
    // Ces deux champs viennent du daemon et partaient bruts dans le HTML.
    $sitename=htmlspecialchars(str_replace("\n",", ",(string)$ligne->sitename));
    $destination=htmlspecialchars(str_replace("\n",", ",(string)$ligne->dnsservers));

    $enabled=$tpl->icon_check(intval($ligne->enabled),"Loadjs('$page?dns-rules-enabled=$ID$addon')",null,"AsSquidAdministrator");
    $delete=$tpl->icon_delete("Loadjs('$page?dns-rules-delete=$ID&md=$md$addon')","AsSquidAdministrator");
    $link=$tpl->td_href("<span class='awc-name'>$sitename</span>",null,"Loadjs('$page?dns-rules-id=$ID$addon');");

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='".(intval($ligne->enabled)==1?"awc-ico":"awc-ico awc-ico-off")."'><i class='".ico_server."'></i></span>";
    $h.="<span>$link</span>";
    $h.="</div></td>";
    $h.="<td class='awc-mono'>".($destination!==""?$destination:"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="<td class='center'>$enabled</td>";
    $h.="<td class='center'><div class='awc-acts'>$delete</div></td>";
    $h.="</tr>";
    return $h;
}


function search_squid(){
    $page=CurrentPageName();
     if(!isset($_SESSION["SQUID_RECORDS_SEARCH"])){$_SESSION["SQUID_RECORDS_SEARCH"]=null;}

$t=time();
$html[]="
<div class=\"row\" style='margin-top:10px'>
    <div class='ibox-content'>
        <div class=\"input-group\">
            <input type=\"text\" class=\"form-control\" value=\"{$_SESSION["SQUID_RECORDS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
            <span class=\"input-group-btn\">
	       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
	      	</span>
        </div>
    </div>
</div>
<div class='row'><div id='table-squid-records-restart'></div>
    <div class='ibox-content'>
        <div id='table-squid-records-loader'></div>
    </div>
</div>
<script>
";

$html[]="function Search$t(e){";
$html[]="\tif(!checkEnter(e) ){return;}";
$html[]="\tss$t();";
$html[]="}

    function ss$t(){
        var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
        LoadAjax('table-squid-records-loader','$page?search-proxy='+ss+'&function=ss$t');
    }

   ss$t();


</script>";

$tpl=new template_admin();
echo $tpl->_ENGINE_parse_body($html);

}
function search_proxy(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    // Ce nom de fonction JavaScript etait insere BRUT dans le rappel de fin de
    // tache: un lien fabrique executait du code dans la session en cours.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $jsrestart=$tpl->framework_buildjs(
        "/squidclient/ipcache",
        "squid.ipcache.progress",
        "squid.ipcache.log",
        "table-squid-records-restart",
        "$function();document.getElementById('table-squid-records-restart').innerHTML='';"

    );

    $btns[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fas fa-sync'></i> {refresh} </label>
			</div>");

    // La saisie etait transformee en MOTIF d'expression reguliere: seuls "."
    // et "*" etaient traites, si bien qu'une parenthese ou un "#" rompait le
    // motif. On echappe tout, puis on re-autorise le seul joker attendu.
    $searchRaw=trim((string)($_GET["search-proxy"] ?? ""));
    $search=str_replace("\\*",".*?",preg_quote($searchRaw,"#"));

    // Le releve complet du cache DNS de Squid n'est plus reconstruit toutes les
    // 3 minutes par le daemon : la page le demande ici (reconstruit seulement
    // s'il a plus de 2 minutes).
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/squidclient/ipcache?wait=1");
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientIPCache"));
    $Entries=0;$Requests=0;$Misses=0;$Hits=0;
    $rows=array();

    if(is_object($json)) {
        $Entries=intval($json->Entries ?? 0);
        $Requests=intval($json->Requests ?? 0);
        $Misses=intval($json->Misses ?? 0);
        $Hits=intval($json->Hits ?? 0);

        $c=0;
        $records=(isset($json->Records) && (is_array($json->Records) || is_object($json->Records)))?$json->Records:array();
        foreach ($records as $hostname=>$classes) {
            if(strlen($hostname)<3){continue;}
            // Le filtre etait applique sur $class -- la variable de la boucle
            // INTERNE, lue APRES sa fermeture: elle ne portait donc que la
            // derniere entree, et n'existait pas du tout si la liste etait
            // vide. Le filtre porte desormais sur l'ensemble des entrees.
            if(strlen($searchRaw)>1){
                if(!preg_match("#$search#is","$hostname".json_encode($classes))){continue;}
            }
            $c++;
            if($c>250){break;}
            $rows[]=pxdns_cache_row($hostname,$classes);
        }
    }
    $shown=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{hostname}</th>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th class='center'>{TTL}</th>";
    // Le <tbody> ouvrant n'etait jamais emis alors que le </tbody> l'etait,
    // et le pied portait un colspan de 5 pour trois colonnes.
    $html[]="</tr></thead><tbody id='pxdns-cache-rows'>";
    if($shown==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $shown</span>";
    $html[]="<span class='awc-pg' id='pxdns-cache-pager'></span>";
    $html[]="</div></div></div>";

    $exp2=null;
    $DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));
    if($DoNotUseLocalDNSCache==0){
        $exp2="<br><strong>{proxy_use_unbound_text}</strong>";
    }
    $zDNS=array();
    $f=explode("\n",(string)@file_get_contents("/etc/squid3/dns.conf"));
    foreach ($f as $line){
        if(!preg_match("#^dns_nameservers (.+)#",$line,$re)){continue;}
        $nameservers=explode(" ",$re[1]);
        foreach ($nameservers as $dns){
            $dns=trim($dns);
            if($dns==null){continue;}
            $zDNS[]=htmlspecialchars($dns);
        }

    }

    if(count($zDNS)>0){
        $exp2="<br><strong>{proxy_use_its_own_dns}: ".@implode(",&nbsp;",$zDNS)."</strong>";
    }
    $sub="";
    $addTitle="{cached_items}";
    if($Entries>0){
        $addTitle=$tpl->FormatNumber($Entries)." {records}";
    }
    if($Requests>0){
        $sub="<br>".$tpl->FormatNumber($Requests)." {requests}, ".$tpl->FormatNumber($Hits)." {cached}, "
            .$tpl->FormatNumber($Misses)." {not_cached}";
    }

    // Le titre affichait la saisie apres sa transformation en motif; il montre
    // desormais ce que l'utilisateur a reellement tape, echappe.
    $TINY_ARRAY["TITLE"]="{APP_PROXY} $addTitle DNS &laquo;".htmlspecialchars($searchRaw)."&raquo;";
    $TINY_ARRAY["ICO"]="fa-solid fa-cabinet-filing";
    $TINY_ARRAY["EXPL"]="{dns_cache_proxy_find}$exp2$sub";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);

    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n$headsjs\n</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"pxdns-cache-rows","pxdns-cache-pager");
    die();
}

/**
 * Une entree du cache DNS du proxy: le nom resolu, ses adresses et l'etat de
 * son bail. L'etat et le libelle etaient recalcules a chaque adresse et seul
 * le dernier survivait -- c'est conserve, mais rendu explicite.
 */
function pxdns_cache_row($hostname,$classes):string{
    $md=md5(json_encode($classes));
    $status="text-default";
    $flags="<span class='awc-badge awc-badge-off'>{expired}</span>";
    $zIps=array();
    if(is_array($classes) || is_object($classes)){
        foreach ($classes as $class) {
            if(!is_object($class)){continue;}
            $ip=(string)($class->IpAddr ?? "");
            if($ip!==""){$zIps[$ip]=$ip;}
            if(isset($class->Status) && $class->Status){$status="text-success";}
            if(intval($class->TTL ?? 0)==0){
                $flags="<span class='awc-badge awc-badge-off'>{expired}</span>";
                $status="text-default";
            }else{
                $flags="<span class='awc-badge awc-badge-on'>Positive ".intval($class->TTL)."</span>";
            }
        }
    }

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-server'></i></span>";
    $h.="<span class='awc-name'>".htmlspecialchars((string)$hostname)."</span>";
    $h.="</div></td>";
    $h.="<td class='awc-mono'>".(count($zIps)>0
        ?"<strong class='$status'>".htmlspecialchars(@implode(", ",$zIps))."</strong>"
        :"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="<td class='center'>$flags</td>";
    $h.="</tr>";
    return $h;
}

function statistics(){
    $js4="Loadjs('fw.rrd.php?img=squiddnsq')";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $RRD_SQUID_IDNS_QUERIES=$tpl->FormatNumber(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RRD_SQUID_IDNS_QUERIES")));

    // Mise en page en flex: les cellules etaient figees a 70%/30%, ce qui
    // ecrasait le tableau de repartition sur un ecran etroit.
    $html=array();
    $html[]="<div style='display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start;margin-top:20px'>";
    $html[]="<div style='flex:1 1 520px;min-width:0'>";
    $html[]="<h1>{DNS_QUERIES} $RRD_SQUID_IDNS_QUERIES</h1>";
    $html[]="<div id='dns-graph-queries'></div>";
    $html[]="</div>";
    $html[]="<div style='flex:1 1 300px;min-width:0'><div id='dns-graph-queries-table'></div></div>";
    $html[]="</div>";
    // <center> est un element retire du standard: le centrage passe en style.
    $html[]="<div style='text-align:center;margin-top:30px'>";
    $html[]="<a href='#' OnClick=\"javascript:$js4\"><img src='img/squid/squiddnsq-hourly.flat.png' style='max-width:100%'></a>";
    $html[]="</div>";
    $html[]="<script>";
    $html[]="Loadjs('$page?dns-graph-queries=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

/**
 * La repartition des requetes DNS telle que le daemon la publie.
 * unserialize() rend false sur une valeur absente ou tronquee: les deux
 * fonctions bouclaient dessus sans garde ("foreach() argument must be of type
 * array|object" -- fatal sous PHP 8).
 */
function pxdns_stats_details():array{
    $DNS=@unserialize(base64_decode((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RRD_SQUID_IDNS_STATS")));
    if(!is_array($DNS) || !isset($DNS["DETAILS"]) || !is_array($DNS["DETAILS"])){return array();}
    return $DNS["DETAILS"];
}

function statistics_chart_dns(){
    $page=CurrentPageName();
    $PieData=pxdns_stats_details();
    if(count($PieData)==0){return true;}

    $highcharts=new highcharts();
    $highcharts->container="dns-graph-queries";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{DNS_QUERIES}";
    $highcharts->Title=null;
    echo $highcharts->BuildChart();
    echo "LoadAjax('dns-graph-queries-table','$page?dns-graph-queries-table=yes');";
    return true;
}

function statistics_chart_dns_table(){
    $tpl=new template_admin();
    $details=pxdns_stats_details();

    $rows=array();
    foreach ($details as $des=>$rqs){
        $rows[]=pxdns_stat_row($des,$tpl->FormatNumber($rqs));
    }

    $html=array();
    $html[]="<div class='awc-card' style='margin-top:20px'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><tbody>";
    if(count($rows)==0){
        $html[]="<tr><td><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div></div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
}

/** Un type de requete DNS et son compte. */
function pxdns_stat_row($label,$value):string{
    $h ="<tr>";
    $h.="<td>".htmlspecialchars((string)$label)."</td>";
    $h.="<td class='awc-right awc-nowrap'><strong>$value</strong></td>";
    $h.="</tr>";
    return $h;
}
