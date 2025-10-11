<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbguard-tools.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
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

    $tpl = new template_admin();
    $page = CurrentPageName();
    $domain = $_GET["urlsrc"];
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

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("QueryTest"));
    $tpl = new template_admin();


    $tpl->table_form_section("{DNS_checking}");
    if($json->domain_test){
        $tpl->table_form_field_text("{public_ip_addr}",$json->domain_record,ico_nic);
    }else{
        $tpl->table_form_field_text("{public_ip_addr}","{none}",ico_nic,true);
    }
    if(count($json->proxy_dns)>0){
        foreach ($json->proxy_dns as $dns_record){
            if(!$dns_record->dns_success){
                $tpl->table_form_field_text("Proxy DNS $dns_record->dnsname","$dns_record->dns_error ",ico_nic,true);
            }else{
                $tpl->table_form_field_text("Proxy DNS $dns_record->dnsname","$dns_record->dns_result ",ico_nic);
            }
        }
    }


    if($json->telnet){
        $tpl->table_form_field_text("Telnet",$json->proto."&nbsp;<i class='".ico_arrow_right."'></i>&nbsp;".$json->port,ico_nic);
    }else{
        $tpl->table_form_field_text("Telnet",$json->telnet_error,ico_nic,true);
    }

    $tpl->table_form_section("{direct_to_internet}",$json->query_url);
    if($json->direct_http){
        $tpl->table_form_field_text("{success}","{error_code}: $json->direct_http_err_code",ico_proto,false);
        if (count($json->direct_http_response_headers)>0){
            foreach ($json->direct_http_response_headers as $http_response_header){
                if( preg_match("#^(.+?):\s+(.+)$#", $http_response_header,$tb) ){
                    $content=htmlspecialchars($tb[2]);
                    $content=wordwrap($content,50,"<br>",true);
                $tpl->table_form_field_text(htmlspecialchars($tb[1]),"<small>$content</small>");
                }
            }

        }
    }else{
        $tpl->table_form_field_text("{failed}","{error_code}: $json->direct_http_err_code",ico_proto,true);
    }

    if($json->proxy_enable){
        $tpl->table_form_section("{UseProxy}",$json->proxy_url ."&nbsp;<i class='".ico_arrow_right."'></i>&nbsp;".$json->query_url);
        if($json->proxy_error){
            $tpl->table_form_field_text("{failed}",$json->proxy_error_str,ico_server,true);
        }else{
            $tpl->table_form_field_text("{success}","{error_code}: $json->proxy_response_code",ico_proto,false);
            if (count($json->proxy_response_headers)>0){
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
    $q->QUERY_SQL("INSERT OR IGNORE INTO `deny_websites` (`items`) VALUES ('$domain')");
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

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM acls_whitelist WHERE pattern='$domain' AND ztype='$ztype'");
    if(intval($ligne["ID"])>0){
        echo "dialogInstance1.close();\n";
        return false;
    }

    $f="('$zDate','$ztype','$domain',1,'$description')";
    $q->QUERY_SQL("INSERT OR IGNORE INTO acls_whitelist (zdate,ztype,pattern,enabled,description) VALUES $f");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}

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
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $IP=new IP();
    $ztype=0;
    if($IP->isIPAddressOrRange($www)){$ztype=1;}

    if(!$q->FIELD_EXISTS("deny_cache_domains","ztype")){
        $q->QUERY_SQL("ALTER TABLE deny_cache_domains ADD ztype INTEGER NOT NULL DEFAULT 0");
    }

    $q->QUERY_SQL("INSERT OR IGNORE INTO deny_cache_domains (items,ztype) VALUES ('{$www}','$ztype')");
    if(!$q->ok){echo $q->mysql_error;return;}

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

    if($familysite<>$domain){
        $html[]="<H1>$familysite_title</H1>";
        $html[]="<table class='table table-hover'><thead></thead><tbody>";

        if(!$from) {
                if ($category_id == 0) {
                    $category_text = "{unknown}";
                }
                $catz = new mysql_catz(true);
                $categories_descriptions = $catz->categories_descriptions();
                if (!isset($categories_descriptions[$category_id]["categoryname"])) {
                    $category_text = "{unknown}";
                }
                if ($category_text == null) {
                    $category_text = $categories_descriptions[$category_id]["categoryname"];
                }
                $smain["website"] = $familysite;
                $smain["category-id"] = $category_id;
                $xmain = base64_encode(serialize($smain));
                $js = "s_PopUpFull('http://articatech.net/categorization.php?report=$xmain',1024,768,'$familysite');";
                $html[] = "<tr>";
                $html[] = "<td><strong>$category_text</strong><br><small>{miscategory_explain}</small></td>";
                $html[] = "<td width=1% nowrap>" . $tpl->button_autnonome("{report_category}", $js,
                        "fa fa-question", "AsProxyMonitor", 180, "btn-primary") . "</td>";
                $html[] = "</tr>";


        }

        if($SQUIDACLsEnabled==1){
            $html[]="<tr>";
            $html[]="<td><strong>{WAF_LEFT}</strong><br><small>{add_to_acl_object}</small></td>";
            $html[]="<td width=1% nowrap>".$tpl->button_autnonome("{select}", "Loadjs('fw.proxy.acl.add.php?type=$acl_type&value=$familysite_encode')",
                    "fa fa-shield","AsProxyMonitor",180,"btn-warning")."</td>";
            $html[]="</tr>";
        }


        $html[]="<tr>";
        $html[]="<td><strong>{categorize}</strong><br><small>{add_to_personal_category}</small></td>";
        $html[]="<td width=1% nowrap>".$tpl->button_autnonome("{select}", "Loadjs('fw.proxy.category.add.php?type=$acl_type&value=$familysite_encode')",
                    "fa fa-shield","AsProxyMonitor",180,"btn-warning")."</td>";
        $html[]="</tr>";




        $blacktitle="{blacklist_this_website}";
        $whitetitle="{whitelist_this_website}";
        if($ip->isValid($domain)){
            $blacktitle="{blacklist_this_address}";
            $whitetitle="{whitelist_this_address}";
        }

        $html[]="<tr>";
        $html[]="<td><strong>$whitetitle</strong><br><small>{whitelist_this_website_explain}</small></td>";
        $html[]="<td width=1% nowrap>".$tpl->button_autnonome("{apply}",
                "Loadjs('$page?wbl=$familysite_encode$extension')", "fas fa-thumbs-up","AsProxyMonitor",180)."</td>";
        $html[]="</tr>";

        $html[]="<tr>";
        $html[]="<td><strong>$blacktitle</strong><br><small>{blacklist_this_website_explain}</small></td>";
        $html[]="<td width=1% nowrap>".$tpl->button_autnonome("{apply}", "Loadjs('$page?black=$familysite_encode$extension')",
                "fas fa-thumbs-down","AsProxyMonitor",180,"btn-danger")."</td>";
        $html[]="</tr>";


         $html[]="<tr>";
         $html[]="<td><strong>{do_not_cache}</strong><br><small>{do_not_cache_this_web_site_explain}</small></td>";
            $html[]="<td width=1% nowrap>".$tpl->button_autnonome("{apply}", "Loadjs('$page?cache=$familysite_encode')",
                    "fas fa-database","AsProxyMonitor",180,"btn-warning")."</td>";
            $html[]="</tr>";




        $html[]="</tbody></table><hr>";

    }


    $html[]="<H1>$domain_title</H1>";
    $category_text=null;
    $html[]="<table class='table table-hover'><thead></thead><tbody>";

    $blacktitle="{blacklist_this_website}";
    $whitetitle="{whitelist_this_website}";
    if($ip->isValid($domain)){
        $blacktitle="{blacklist_this_address}";
        $whitetitle="{whitelist_this_address}";
    }

    if($EnableUfdbGuard==1){
        $bypass_webfilter_explain=$tpl->_ENGINE_parse_body("{bypass_webfilter_explain}");
        $bypass_webfilter_explain=str_replace("%s",$domain,$bypass_webfilter_explain);
        $html[]="<tr>";
        $html[]="<td><strong>{bypass_webfilter}</strong><br><small>$bypass_webfilter_explain</small></td>";
        $html[]="<td width=1% nowrap>".$tpl->button_autnonome("{select}", "Loadjs('$page?bypasswebf=$domain_encode')",
                "fa fa-shield","AsProxyMonitor",180,"btn-warning")."</td>";
        $html[]="</tr>";

    }

    if(!$from) {
            if ($category_id == 0) {
                $category_text = "{unknown}";
            }
            $catz = new mysql_catz(true);
            $categories_descriptions = $catz->categories_descriptions();
            if (!isset($categories_descriptions[$category_id]["categoryname"])) {
                $category_text = "{unknown}";
            }
            if ($category_text == null) {
                $category_text = $categories_descriptions[$category_id]["categoryname"];
            }
            $smain["website"] = $domain;
            $smain["category-id"] = $category_id;
            $xmain = base64_encode(serialize($smain));
            $js = "s_PopUpFull('http://articatech.net/categorization.php?report=$xmain',1024,768,'$domain');";
            $html[] = "<tr>";
            $html[] = "<td><strong>$category_text</strong><br><small>{miscategory_explain}</small></td>";
            $html[] = "<td width=1% nowrap>" . $tpl->button_autnonome("{report_category}", $js,
                    "fa fa-question", "AsProxyMonitor", 180, "btn-primary") . "</td>";
            $html[] = "</tr>";


    }
    if($SQUIDACLsEnabled==1){
        $html[]="<tr>";
        $html[]="<td><strong>{WAF_LEFT}</strong><br><small>{add_to_acl_object}</small></td>";
        $html[]="<td width=1% nowrap>".$tpl->button_autnonome("{select}", "Loadjs('fw.proxy.acl.add.php?type=$acl_type&value=$domain_encode')",
                "fa fa-shield","AsProxyMonitor",180,"btn-warning")."</td>";
        $html[]="</tr>";
    }

    if($EnablePersonalCategories==1){
        $html[]="<tr>";
        $html[]="<td><strong>{categorize}</strong><br><small>{add_to_personal_category}</small></td>";
        $html[]="<td width=1% nowrap>".$tpl->button_autnonome("{select}", "Loadjs('fw.proxy.category.add.php?type=$acl_type&value=$domain_encode')",
                "fa fa-shield","AsProxyMonitor",180,"btn-warning")."</td>";
        $html[]="</tr>";


    }


    $html[]="<tr>";
    $html[]="<td><strong>$whitetitle</strong><br><small>{whitelist_this_website_explain}</small></td>";
    $html[]="<td width=1% nowrap>".$tpl->button_autnonome("{apply}",
            "Loadjs('$page?wbl=$domain_encode$extension')", "fas fa-thumbs-up","AsProxyMonitor",180)."</td>";
    $html[]="</tr>";

    $html[]="<tr>";
    $html[]="<td><strong>$blacktitle</strong><br><small>{blacklist_this_website_explain}</small></td>";
    $html[]="<td width=1% nowrap>".$tpl->button_autnonome("{apply}", "Loadjs('$page?black=$domain_encode$extension')",
            "fas fa-thumbs-down","AsProxyMonitor",180,"btn-danger")."</td>";
    $html[]="</tr>";

    if(!$from) {
        if ($SquidCachesProxyEnabled == 1) {
            $html[] = "<tr>";
            $html[] = "<td><strong>{do_not_cache}</strong><br><small>{do_not_cache_this_web_site_explain}</small></td>";
            $html[] = "<td width=1% nowrap>" . $tpl->button_autnonome("{apply}", "Loadjs('$page?cache=$domain_encode')",
                    "fas fa-database", "AsProxyMonitor", 180, "btn-warning") . "</td>";
            $html[] = "</tr>";


        }
    }
    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

