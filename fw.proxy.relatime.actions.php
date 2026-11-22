<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbguard-tools.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
// awc_table_style(): feuille de style partagee des tableaux et cartes d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
if(isset($_GET["check-results"])){check_domain_results();exit;}
if(isset($_GET["check"])){check_domain();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["wbl"])){site_whitelist();exit;}
if(isset($_GET["black"])){site_blacklist();exit;}
if(isset($_GET["cache"])){site_cache();exit;}
if(isset($_GET["domain"])){popup();exit;}
if(isset($_GET["bypasswebf"])){bypasswebf();exit;}


js();

function js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $domain     = $_GET["dom"];
    $urlsrcEncoded="";
    $urlsrc="";
    if(isset($_GET["urlsrc"])){
        $urlsrc=$_GET["urlsrc"];
    }
    if(strlen($urlsrc)>1) {
        $urlsrcEncoded = urlencode($_GET["urlsrc"]);
    }
    $from       = null;
    $category_id = 0;
    if(!isset($_GET["category-id"])) {
        $category_id = intval($_GET["category-id"]);
    }
    if(isset($_GET["from"])){$from="&from=yes";}
    $tpl->js_dialog1("{action}: $domain", "$page?tabs=yes&domain=".urlencode($domain)."&category-id=$category_id$from&urlsrc=$urlsrcEncoded");
}
function bypasswebf(){
    $tpl = new template_admin();
    $domain=$_GET["bypasswebf"];
    $sock=new sockets();
    $data=$sock->REST_API("/proxy/webfilter/bypass/$domain");
    $json=json_decode($data);
    if(!$json->Status){
        return $tpl->popup_error($json->Error);
    }
    return $tpl->js_config_applied($domain);

}
function tabs(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $from="";
    $urlsrcEncoded=urlencode($_GET["urlsrc"]);
    $category_id = 0;
    if(!isset($_GET["category-id"])) {
        $category_id = intval($_GET["category-id"]);
    }
    $domain     = $_GET["domain"];



    if(isset($_GET["from"])){$from="&from=yes";}
    $domainencoded="";
    if(!is_null($domain)){
        $domainencoded=urlencode($domain);
    }
    $array[$domain]="$page?domain=$domainencoded&category-id=$category_id$from";
    if(!isRfc1918($domain)) {
        $array["{troubleshooting}"] = "$page?check=" . urlencode($domain) . "&category-id=$category_id$from&urlsrc=$urlsrcEncoded";
    }
    echo $tpl->tabs_default($array);
}
function isRfc1918(string $ip): bool {
    $ipLong = ip2long($ip);
    if ($ipLong === false) {
        // Invalid IP address.
        return false;
    }

    // Check 10.0.0.0/8
    if (($ipLong & 0xFF000000) === 0x0A000000) {
        return true;
    }

    // Check 172.16.0.0/12
    if (($ipLong & 0xFFF00000) === 0xAC100000) {
        return true;
    }

    // Check 192.168.0.0/16
    if (($ipLong & 0xFFFF0000) === 0xC0A80000) {
        return true;
    }

    return false;
}

function check_domain():bool
{
    if(!fw_progress_page_priv("AsProxyMonitor")){return false;}   // task link signed for this session: built only for whom may run it

    $tpl = new template_admin();
    $page = CurrentPageName();
    // The troubleshooting tab (tabs()) triggers this via ?check=<domain>&urlsrc=<fullurl>.
    // urlsrc carries the FULL url (scheme/path) when the caller has it — parsed below;
    // but the blocked-requests list no longer always passes it, leaving urlsrc empty and
    // producing a request to "/analyze/website/nohup/" with NO domain → the daemon 404s
    // ("did you use the Correct version of the Web API service?"). Fall back to `check`,
    // which reliably holds the host, so troubleshooting works whether or not urlsrc is set.
    $domain = isset($_GET["urlsrc"]) ? trim($_GET["urlsrc"]) : "";
    if(strlen($domain) < 1){ $domain = trim($_GET["check"]); }
    $domainEncoded=urlencode($domain);
    $domainmd = md5($domain);

    if(preg_match("#^(http|https|ftp|ftps):\/\/#", $domain)){
        $urls=parse_url($domain);
       // $sheme=$urls["scheme"];
        $host=$urls["host"];
        $port="";
        if(isset($urls["port"])){$port=":{$urls["port"]}";}
        if(!isset($urls["scheme"])){$urls["scheme"]="http";}
        if(!isset($urls["host"])){$urls["host"]="itchart.mycompany.tld";}
        $domain="$host$port";
        $domainEncoded=urlencode($domain);

    }
    // Placed as one path segment of the route below. The daemon (articarest
    // QueryTests.go:65-68) splits it on ":" into host and port: only a host name or IPv4
    // address with an optional port can work. An IPv6 literal ("[::1]:3128") is refused.
    if(fw_progress_restseg($domain)==="" || !preg_match('/^[A-Za-z0-9._-]{1,253}(:[0-9]{1,5})?$/',$domain)){return fw_progress_bad_value("domain");}

    $js = $tpl->framework_buildjs(
        "/analyze/website/nohup/$domain",
        "domain.query.progress",
        "domain.query.progress.log",
        "dompr-$domainmd",
        "LoadAjax('domre-$domainmd','$page?check-results=$domainEncoded');",
        "LoadAjax('domre-$domainmd','$page?check-results=$domainEncoded');",
    );
    $btn_config = $tpl->button_autnonome("{check} $domain", $js, ico_check, "AsProxyMonitor", 335);
    $html[] = "<div style='margin-top:20px' id='dompr-$domainmd'></div>";
    $html[] = "<div id='domre-$domainmd'></div>";
    $html[] = "<div class='center' style='margin:30px;margin-top:50px'>$btn_config</div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function check_domain_results():bool{

    $tpl = new template_admin();
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("QueryTest");
    $json=json_decode($raw);

    // Le bouton lance l'analyse en nohup PUIS charge ce fragment: tant que le
    // daemon n'a pas ecrit QueryTest, GET_INFO rend une chaine vide et
    // json_decode NULL. Toute la suite lisait alors $json->... sur ce NULL --
    // count($json->proxy_dns) etait une TypeError FATALE en PHP 8, ce qui
    // laissait la fenetre vide sans la moindre explication.
    if(!is_object($json)){
        if(trim($raw)===""){
            echo $tpl->_ENGINE_parse_body($tpl->div_info("{please_wait}"));
        }else{
            echo $tpl->_ENGINE_parse_body($tpl->div_error("{unknown_error}"));
        }
        return false;
    }

    $tpl->table_form_section("{DNS_checking}");
    if(!empty($json->domain_test)){
        $tpl->table_form_field_text("{public_ip_addr}",$json->domain_record ?? "",ico_nic);
    }else{
        $tpl->table_form_field_text("{public_ip_addr}","{none}",ico_nic,true);
    }
    // Chacune des trois listes du rapport est OPTIONNELLE: elle manque tant que
    // l'etape correspondante n'a pas ete franchie. count() sur la cle absente
    // etait fatal; is_array() la traite comme vide.
    if(is_array($json->proxy_dns ?? null)){
        foreach ($json->proxy_dns as $dns_record){
            if(!is_object($dns_record)){continue;}
            if(empty($dns_record->dns_success)){
                $tpl->table_form_field_text("Proxy DNS ".($dns_record->dnsname ?? ""),($dns_record->dns_error ?? "")." ",ico_nic,true);
            }else{
                $tpl->table_form_field_text("Proxy DNS ".($dns_record->dnsname ?? ""),($dns_record->dns_result ?? "")." ",ico_nic);
            }
        }
    }


    if(!empty($json->telnet)){
        $tpl->table_form_field_text("Telnet",($json->proto ?? "")."&nbsp;<i class='".ico_arrow_right."'></i>&nbsp;".($json->port ?? ""),ico_nic);
    }else{
        $tpl->table_form_field_text("Telnet",$json->telnet_error ?? "",ico_nic,true);
    }

    $tpl->table_form_section("{direct_to_internet}",$json->query_url ?? "");
    if(!empty($json->direct_http)){
        $tpl->table_form_field_text("{success}","{error_code}: ".($json->direct_http_err_code ?? ""),ico_proto,false);
        if (is_array($json->direct_http_response_headers ?? null)){
            foreach ($json->direct_http_response_headers as $http_response_header){
                if( preg_match("#^(.+?):\s+(.+)$#", $http_response_header,$tb) ){
                    $content=htmlspecialchars($tb[2]);
                    $content=wordwrap($content,50,"<br>",true);
                $tpl->table_form_field_text(htmlspecialchars($tb[1]),"<small>$content</small>");
                }
            }

        }
    }else{
        $tpl->table_form_field_text("{failed}","{error_code}: ".($json->direct_http_err_code ?? ""),ico_proto,true);
    }

    if(!empty($json->proxy_enable)){
        $tpl->table_form_section("{UseProxy}",($json->proxy_url ?? "")."&nbsp;<i class='".ico_arrow_right."'></i>&nbsp;".($json->query_url ?? ""));
        if(!empty($json->proxy_error)){
            $tpl->table_form_field_text("{failed}",$json->proxy_error_str ?? "",ico_server,true);
        }else{
            $tpl->table_form_field_text("{success}","{error_code}: ".($json->proxy_response_code ?? ""),ico_proto,false);
            if (is_array($json->proxy_response_headers ?? null)){
                foreach ($json->proxy_response_headers as $http_response_header){
                    if( preg_match("#^(.+?):\s+(.+)$#", $http_response_header,$tb) ) {
                        $error=false;
                        if(strtolower($tb[1])=="x-squid-error"){
                            $tb[1]="Proxy Error";
                            if(strlen($tb[2])>1){
                                $error=true;
                            }
                        }
                        $content=htmlspecialchars($tb[2]);
                        $content=wordwrap($content,50,"<br>",true);
                        $tpl->table_form_field_text(htmlspecialchars($tb[1]),"<small>$content</small>", ico_proto,$error);
                    }
                }

            }
        }


    }
    echo $tpl->table_form_compile();
    return true;

}

function site_blacklist(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $domain=$_GET["black"];
    if(isset($_GET["from"])){
        $domain="from:$domain";
    }

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    // Pentest 2026-11-04 : domain etait concatene dans la requete.
    $q->QUERY_SQL_PARAMS("INSERT OR IGNORE INTO `deny_websites` (`items`) VALUES (?)",array($domain));
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    admin_tracks("Added $domain to Global Proxy Blacklist");

    $jsrestart=$tpl->framework_buildjs(
        "/proxy/global/blacklists/compile",
        "squid.wb.progress","squid.wb.txt",
        "accesslog-actions","dialogInstance1.close()");


    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/whitelists/nohupcompile");



    echo $jsrestart;
}

function site_whitelist(){
    header("content-type: application/x-javascript");
    $domain=$_GET["wbl"];
    $ip=new IP();
    $tpl=new template_admin();
    $ztype="dstdomain";
    if($ip->isValid($domain)){$ztype="dst";}
    $user=$_SESSION["uid"];
    if($user==-100){$user="Manager";}
    $description="{saved_by} $user";
    $zDate=date("Y-m-d H:i:s");
    $line=str_replace("^","",$domain);
    if(substr($line,0,1)=="."){$line=substr($line, 1,strlen($line));}
    $lib=new lib_memcached();
    if(isset($_GET["from"])){ $ztype="src";}
    if($ztype<>"src") {
        $lib->saveKey("isWhite:$line", true, 300);
    }

    // Check if the entry already exists
    $wl_list = acls_api_get("/proxy/acls-whitelist");
    if(is_array($wl_list)){
        foreach($wl_list as $wl_item){
            if(($wl_item["pattern"] ?? "") == $domain && ($wl_item["ztype"] ?? "") == $ztype){
                echo "dialogInstance1.close();\n";
                return false;
            }
        }
    }

    [$ok,$data] = acls_api_send("POST","/proxy/acls-whitelist",[
        "pattern"     => $domain,
        "description" => $description,
        "zDate"       => $zDate,
        "ztype"       => $ztype,
    ]);
    if(!$ok){$tpl->js_mysql_alert($data);return false;}

    admin_tracks("Added $ztype:$domain to Global Proxy Whitelist");

    //

    $jsrestart=$tpl->framework_buildjs("/proxy/whitelists/nohupcompile",
    "squid.global.whitelists.progress",
    "squid.global.whitelists.progress.log",
    "accesslog-actions",
    "dialogInstance1.close();");
    echo $jsrestart;

    return true;
}
function site_cache(){
    header("content-type: application/x-javascript");
    $www=$_GET["cache"];
    $tpl=new template_admin();
    $IP=new IP();
    $ztype=0;
    if($IP->isIPAddressOrRange($www)){$ztype=1;}

    [$ok,$data] = acls_api_send("POST","/proxy/deny-cache",["domain"=>$www,"ztype"=>(string)$ztype]);
    if(!$ok){echo htmlspecialchars($data);return;}

    $jsrestart=$tpl->framework_buildjs("/proxy/acls/denycache",
        "squid.nocache.progress",
        "squid.nocache.log",
        "accesslog-actions","dialogInstance1.close();");

    echo $jsrestart;
}

function popup(){
    $from                       = false;
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();
    $SquidCachesProxyEnabled    = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled");
    $SQUIDACLsEnabled           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsEnabled"));
    $EnablePersonalCategories   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePersonalCategories"));
    $category_id                = intval($_GET["category-id"]);
    $extension                  = null;
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));

    if(isset($_GET["from"])){
        $from=true;
        $extension="&from=yes";
    }

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $SquidCachesProxyEnabled=0;
        $SQUIDACLsEnabled=0;

    }
    $ip=new IP();

    $domain=$_GET["domain"];
    VERBOSE("domain:$domain",__LINE__);
    if($domain==null){echo $tpl->FATAL_ERROR_SHOW_128("{no_domain}");exit;}

    if(!$ip->isValid($domain)) {
        VERBOSE("domain:isValid -> FALSE",__LINE__);
        $arrayURI = parse_url($domain);

        if(isset($arrayURI["host"])) {
            $domain = $arrayURI["host"];
        }
        VERBOSE("domain:$domain",__LINE__);
        if (preg_match("#^(.+?):([0-9]+)#", $domain, $re)) {
            $domain = $re[1];
        }
        if (preg_match("#^www\.(.+)#", $domain, $re)) {
            $domain = $re[1];
        }
    }else{
        VERBOSE("domain:$domain - isValid -> TRUE",__LINE__);
    }

    VERBOSE("domain:$domain",__LINE__);
    if(!$ip->isValid($domain)){
        $fam=new squid_familysite();
        $familysite=$fam->GetFamilySites($domain);
    }else{
        $familysite=$domain;
    }

    $familysite_encode=urlencode($familysite);


    $domain_encode=urlencode($domain);
    $html[]="<div id='accesslog-actions'></div>";
    $domain_title=$domain;

    if(!$ip->isValid($domain)){
        if($familysite==$domain){
            $domain_title="*.$domain";
        }else{
            $domain_title="^$domain";
            $domain_encode=urlencode("^$domain");
        }
    }

    $familysite_title="*.$familysite";
    $acl_type="dstdomain";
    if($ip->isValid($domain)){
        $acl_type="dst";
        if($from){$acl_type="src";}
        $tb=explode(".",$domain);
        $familysite=$tb[0].".".$tb[1].".".$tb[2].".0/24";
        $familysite_title=$familysite;
        $familysite_encode=urlencode($familysite);

    }

    // Les deux listes d'actions etaient des <table class='table table-hover'>
    // de MISE EN PAGE: chaque ligne portait un libelle, une explication et un
    // bouton -- aucune donnee tabulaire. Elles sont rendues en cartes.
    // categories_descriptions() etait par ailleurs recharge une fois par liste:
    // deux lectures completes de la table des categories par affichage.
    $category_text=rta_category_label($category_id,$from);

    if($familysite<>$domain){
        $rows=array();

        if(!$from) {
            $rows[]=rta_report_category_row($tpl,$familysite,$category_id,$category_text);
        }

        if($SQUIDACLsEnabled==1){
            $rows[]=rta_acl_object_row($tpl,$acl_type,$familysite_encode);
        }

        $rows[]=rta_categorize_row($tpl,$acl_type,$familysite_encode);
        $rows[]=rta_whitelist_row($tpl,$page,$familysite_encode,$extension,$ip->isValid($domain));
        $rows[]=rta_blacklist_row($tpl,$page,$familysite_encode,$extension,$ip->isValid($domain));
        $rows[]=rta_nocache_row($tpl,$page,$familysite_encode);

        $html[]=rta_actions_card($familysite_title,$rows);

    }


    $rows=array();

    if($EnableUfdbGuard==1){
        $bypass_webfilter_explain=$tpl->_ENGINE_parse_body("{bypass_webfilter_explain}");
        $bypass_webfilter_explain=str_replace("%s",htmlspecialchars($domain),$bypass_webfilter_explain);
        $rows[]=rta_action_row("{bypass_webfilter}",$bypass_webfilter_explain,
            $tpl->button_autnonome("{select}","Loadjs('$page?bypasswebf=$domain_encode')",
                "fa fa-shield","AsProxyMonitor",180,"btn-warning"));
    }

    if(!$from) {
        $rows[]=rta_report_category_row($tpl,$domain,$category_id,$category_text);
    }
    if($SQUIDACLsEnabled==1){
        $rows[]=rta_acl_object_row($tpl,$acl_type,$domain_encode);
    }

    if($EnablePersonalCategories==1){
        $rows[]=rta_categorize_row($tpl,$acl_type,$domain_encode);
    }

    $rows[]=rta_whitelist_row($tpl,$page,$domain_encode,$extension,$ip->isValid($domain));
    $rows[]=rta_blacklist_row($tpl,$page,$domain_encode,$extension,$ip->isValid($domain));

    if(!$from) {
        if ($SquidCachesProxyEnabled == 1) {
            $rows[]=rta_nocache_row($tpl,$page,$domain_encode);
        }
    }
    $html[]=rta_actions_card($domain_title,$rows);
    // La feuille est concatenee AVANT l'analyse mais hors de la chaine analysee:
    // la regex #{(.+?)}# de _ENGINE_parse_body prendrait chaque corps de regle
    // CSS pour un {token}.
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n", $html));

}

/**
 * Une carte de liste d'actions (ancien <table class='table table-hover'>).
 */
function rta_actions_card($title,$rows):string{
    $h ="<h1>".htmlspecialchars($title)."</h1>";
    $h.="<div class='awc-wrap'><div class='awc-card'>";
    $h.=@implode("\n",$rows);
    $h.="</div></div>";
    return $h;
}

/**
 * Une action proposee sur le domaine: libelle, explication, bouton.
 * Onze lignes repetaient exactement ce motif dans deux tableaux.
 */
function rta_action_row($title,$explain,$button):string{
    $h ="<div class='awc-ident' style='justify-content:space-between;padding:14px 20px;"
       ."border-top:1px solid rgba(188,201,198,.3)'>";
    $h.="<span><span class='awc-name'>$title</span>";
    $h.="<div class='awc-meta'>$explain</div></span>";
    $h.="<span class='awc-nowrap'>$button</span>";
    $h.="</div>";
    return $h;
}

/**
 * Libelle de la categorie courante.
 * L'ancienne version testait $category_text avant toute affectation dans le
 * premier des deux blocs (avertissement PHP 8), et rechargeait
 * categories_descriptions() une fois par bloc.
 */
function rta_category_label($category_id,$from):string{
    if($from){return "{unknown}";}
    $category_id=intval($category_id);
    if($category_id==0){return "{unknown}";}
    $catz=new mysql_catz(true);
    $descriptions=$catz->categories_descriptions();
    if(!isset($descriptions[$category_id]["categoryname"])){return "{unknown}";}
    return (string)$descriptions[$category_id]["categoryname"];
}

function rta_report_category_row($tpl,$website,$category_id,$category_text):string{
    $smain=array("website"=>$website,"category-id"=>$category_id);
    $xmain=base64_encode(serialize($smain));
    // Le nom du site partait BRUT dans le titre de la fenetre s_PopUpFull, or
    // il vient de l'URL (?dom=...). Ce texte finit dans une chaine JavaScript,
    // elle-meme dans un attribut OnClick="...".
    //
    // L'ORDRE COMPTE et htmlspecialchars() SEUL NE SUFFIT PAS: le navigateur
    // decode les entites de l'attribut AVANT d'analyser le JavaScript, donc une
    // apostrophe ecrite &#039; redevient une apostrophe et referme la chaine.
    // addslashes() d'abord (l'apostrophe devient \'), htmlspecialchars ensuite
    // pour proteger l'attribut -- en ENT_COMPAT, qui laisse l'apostrophe et
    // donc l'antislash operant.
    $sitejs=htmlspecialchars(addslashes($website),ENT_COMPAT);
    $js="s_PopUpFull('http://articatech.net/categorization.php?report=$xmain',1024,768,'$sitejs');";
    return rta_action_row(htmlspecialchars($category_text),"{miscategory_explain}",
        $tpl->button_autnonome("{report_category}",$js,"fa fa-question","AsProxyMonitor",180,"btn-primary"));
}

function rta_acl_object_row($tpl,$acl_type,$value_encode):string{
    return rta_action_row("{WAF_LEFT}","{add_to_acl_object}",
        $tpl->button_autnonome("{select}","Loadjs('fw.proxy.acl.add.php?type=$acl_type&value=$value_encode')",
            "fa fa-shield","AsProxyMonitor",180,"btn-warning"));
}

function rta_categorize_row($tpl,$acl_type,$value_encode):string{
    return rta_action_row("{categorize}","{add_to_personal_category}",
        $tpl->button_autnonome("{select}","Loadjs('fw.proxy.category.add.php?type=$acl_type&value=$value_encode')",
            "fa fa-shield","AsProxyMonitor",180,"btn-warning"));
}

function rta_whitelist_row($tpl,$page,$value_encode,$extension,$isIp):string{
    return rta_action_row($isIp?"{whitelist_this_address}":"{whitelist_this_website}",
        "{whitelist_this_website_explain}",
        $tpl->button_autnonome("{apply}","Loadjs('$page?wbl=$value_encode$extension')",
            "fas fa-thumbs-up","AsProxyMonitor",180));
}

function rta_blacklist_row($tpl,$page,$value_encode,$extension,$isIp):string{
    return rta_action_row($isIp?"{blacklist_this_address}":"{blacklist_this_website}",
        "{blacklist_this_website_explain}",
        $tpl->button_autnonome("{apply}","Loadjs('$page?black=$value_encode$extension')",
            "fas fa-thumbs-down","AsProxyMonitor",180,"btn-danger"));
}

function rta_nocache_row($tpl,$page,$value_encode):string{
    return rta_action_row("{do_not_cache}","{do_not_cache_this_web_site_explain}",
        $tpl->button_autnonome("{apply}","Loadjs('$page?cache=$value_encode')",
            ico_database,"AsProxyMonitor",180,"btn-warning"));
}
