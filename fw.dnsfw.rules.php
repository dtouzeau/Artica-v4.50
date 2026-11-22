<?php
$GLOBALS["DNS_ACTIONS"]=array(0=>"{REFUSED}",1=>"{NXDOMAIN}",3=>"{SERVFAIL}",4=>"{NOERROR}",
    5=>"{query_dnssrv}",6=>"{do_nothing_but_logs}");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["DNSFireWallVerbose"])){options_save();exit;}
if(isset($_POST["RULE_CHECKER_DOMAIN"])){rule_checker_save();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}

if(isset($_GET["js-example"])){js_example();exit;}
if(isset($_GET["js-example-create"])){js_example_create();exit;}

if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_POST["none"])){exit;}
if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"]);exit;}
if(isset($_GET["js-example"])){js_example();exit;}
if(isset($_GET["rule-checker-js"])){rule_checker_js();exit;}
if(isset($_GET["rule-checker-popup"])){rule_checker_popup();exit;}
if(isset($_GET["rule-checker-results"])){rule_checker_results();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["options-js"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}
if(isset($_GET["dashboard"])){dashboard();exit;}
if(isset($_GET["dashboard-top"])){dashboard_top();exit;}
if(isset($_GET["dashboard-left"])){dashboard_left();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $DNSFW_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFW_VERSION");
    $html=$tpl->page_header("{APP_DNS_FIREWALL} v$DNSFW_VERSION","fa-solid fa-block-brick-fire","{APP_DNS_FIREWALL_ABOUT}","$page?tabs=yes","dnsfw","progress-dnsfw-restart",false,"table-dnsfw-rules-tabs");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_DNS_FIREWALL}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function dashboard(){
    $page=CurrentPageName();
    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td colspan='2' style='vertical-align: top'><div id='top-dashboard'></div></td>";
    $html[]="</tr>";
    // La seconde <tr> n'etait jamais ouverte: les deux cellules suivantes
    // flottaient hors de toute ligne.
    $html[]="<tr>";
    $html[]="<td style='width:50%;vertical-align:top'>";
    $html[]="<div id='left-dashboard'></div>";
    $html[]="</td>";
    $html[]="<td style='width:50%;vertical-align:top'>";
    $html[]="<div id='right-dashboard'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('top-dashboard','$page?dashboard-top=yes')";
    $html[]="</script>";
    echo @implode("\n",$html);
}
// ARTICASTATISTICS_DISABLED
function dashboard_top(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $results=$GLOBALS["CLASS_SOCKETS"]->ksrn_sockets("STATS");
    $MAIN=unserialize($results["RESPONSE"]);
    $CORP_LICENSE=intval($MAIN["CORP_LICENSE"]);
    $KSRN_LICENSE=intval($MAIN["KSRN_LICENSE"]);
    $KSRNEnable=intval($MAIN["KSRNEnable"]);
    $EnableUfdbGuard=intval($MAIN["EnableUfdbGuard"]);
    $VERSION=intval($MAIN["VERSION"]);

    $button=null;
    $KSRN_WIDGET= $tpl->widget_h("green", "fas fa-shield", "{active2}", "{KSRN} v.$VERSION",$button);
    $WEBFILTER_WIDGET= $tpl->widget_h("green", "fas fa-shield-virus", "{active2}", "{web_filtering}",$button);

    if($KSRN_LICENSE==0){
        $KSRN_WIDGET= $tpl->widget_h("yellow", "fas fa-shield", "{license_error}", "{KSRN} v.$VERSION",$button);
    }
    if($KSRNEnable==0){
        $KSRN_WIDGET= $tpl->widget_h("grey", "fas fa-shield", "{inactive2}", "{KSRN} v.$VERSION",$button);
    }
    if($CORP_LICENSE==0){
        $WEBFILTER_WIDGET= $tpl->widget_h("yellow", "fas fa-shield-virus", "Community Edition", "{web_filtering}",$button);
    }
    if($EnableUfdbGuard==0){
        $WEBFILTER_WIDGET= $tpl->widget_h("grey", "fas fa-shield-virus", "{inactive2}", "{web_filtering}",$button);
    }

    $cnt = acls_api_get("/dnsfw/acls/enabled-count");
    $Rules = is_array($cnt) ? intval($cnt["count"]) : 0;
    $RULES_WIDGET=$tpl->widget_h("green", "fa-solid fa-block-brick-fire", "$Rules {current_rules}", "{APP_DNS_FIREWALL}",$button);
    if($Rules==0){
        $RULES_WIDGET=$tpl->widget_h("grey", "fa-solid fa-block-brick-fire", "{no_rules}", "{APP_DNS_FIREWALL}",$button);
    }

    $html[]="<table style=width:100%>";
    $html[]="<tr>";
    $html[]="<td style='width:33%;padding:5px'>$KSRN_WIDGET</td>";
    $html[]="<td style='width:33%;padding:5px'>$WEBFILTER_WIDGET</td>";
    $html[]="<td style='width:33%;padding:5px'>$RULES_WIDGET</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<center style='margin:10px'><h2>{dansguardian_statistics}</h2></center>";
    $html[]="<script>";
    $html[]="LoadAjax('left-dashboard','$page?dashboard-left=yes')";
    $html[]="</script>";



    echo $tpl->_ENGINE_parse_body($html);
}

function dashboard_left(){


}


function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $array["{status}"]="$page?dashboard=yes";
    $array["{statistics}"]="$page?status=yes";
    $array["{firewall_rules}"]="$page?table-start=yes";
    $array["{firewall_events}"]="fw.dnsfw.events.php";
    echo $tpl->tabs_default($array);
}

function table_start(){
    $page=CurrentPageName();
    echo "<div id='table-dnsfw-rules'></div>
    <script>LoadAjax('table-dnsfw-rules','$page?table=yes');</script>";
}


// Masquer une icone ne protege rien: le repartiteur en tete de fichier repond a
// toute session authentifiee. Les mutations se gardent cote serveur.
function dnsfw_require($tpl,$flag="AsDnsAdministrator"):bool{
    $users=new usersMenus();
    if($users->$flag){return true;}
    $tpl->js_no_privileges();
    return false;
}

function rule_delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	if(!dnsfw_require($tpl)){return;}
	$ID=intval($_GET["rule-delete-js"]);
	if($ID<1){return;}
	if(!rule_delete($ID)){return;}
	// La ligne s'identifie par acl-<ID>, comme celle que rule_enable() vise.
	// $_GET["md"] -- un md5 recopie tel quel dans $('#...').remove() -- n'est
	// plus lu du tout.
	echo "$('#acl-$ID').remove();";
}

function js_example(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_prompt("{example}","{example_create_ask}","fas fa-hat-wizard",$page,null,
        "Loadjs('$page?js-example-create')");
}

function check_tables(){
    // Schema migrations are handled by articarest::EnsureAclsSqliteSchema
    // at daemon startup. This function is a no-op now.
    return true;
}

function js_example_create(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    header("content-type: application/x-javascript");

    // Step 1: skip if any rules already exist.
    $existingRules = acls_api_get("/dnsfw/acls");
    if(is_array($existingRules) && count($existingRules) > 0){
        echo "LoadAjax('table-dnsfw-rules','$page?table=yes');";
        return;
    }

    // Step 2: create 6 example rules via POST /dnsfw/acls. Capture each new ID.
    $exampleRules = [
        1 => ["ruleName"=>"Suspicious TLD domains",     "description"=>"Block tld that are mostly non-professional",
              "action"=>0, "reply"=>"",           "ttl"=>1800, "redirector"=>""],
        2 => ["ruleName"=>"Google forwarders",          "description"=>"Redirects Google domains to Google redirectors",
              "action"=>5, "reply"=>"",           "ttl"=>1800, "redirector"=>"8.8.8.8,8.8.4.4"],
        3 => ["ruleName"=>"DNS Filter",                 "description"=>"Block suspicious domains (license required)",
              "action"=>4, "reply"=>"127.0.0.1",  "ttl"=>1800, "redirector"=>""],
        4 => ["ruleName"=>"Who Query MX",               "description"=>"If there is something that query MX ?",
              "action"=>6, "reply"=>"",           "ttl"=>1800, "redirector"=>""],
        5 => ["ruleName"=>"To Active Directory",        "description"=>"domain.tld, redirects to Active Directory",
              "action"=>5, "reply"=>"",           "ttl"=>1800, "redirector"=>"10.10.1.254"],
        6 => ["ruleName"=>"0-day reputation service",   "description"=>"Use the Shields object as reputation service (license required)",
              "action"=>1, "reply"=>"",           "ttl"=>1800, "redirector"=>""],
    ];
    $newRuleIDs = [];
    foreach($exampleRules as $idx=>$er){
        list($ok,$data) = acls_api_send("POST","/dnsfw/acls",$er);
        // alert() imprime son argument tel quel et n'a pas la meme allure que
        // les autres dialogues de la console: js_error() resout les {tokens}.
        if(!$ok){ $tpl->js_error("{failed}: {$er['ruleName']} - $data"); return; }
        $newRuleIDs[$idx] = intval($data["id"] ?? 0);
    }

    // Step 3: look up or create the 8 wizard groups; search by exact GroupName.
    // The API search uses LIKE %term%, so we fetch the full list and match exactly.
    $allGroups = acls_api_get("/proxy/acls/sqlite/groups/all");
    if(!is_array($allGroups)){ $allGroups=[]; }
    $groupByName = [];
    foreach($allGroups as $g){
        $groupByName[$g["groupName"]] = intval($g["id"]);
    }

    // Helper: get existing gpid by name or create and return new id.
    $ensureGroup = function($name, $type) use (&$groupByName, $tpl) {
        if(isset($groupByName[$name]) && $groupByName[$name] > 0){
            return $groupByName[$name];
        }
        list($ok,$data) = acls_api_send("POST","/proxy/acls/sqlite/group",[
            "name"=>$name, "type"=>$type,
        ]);
        if(!$ok){ return 0; }
        $gpid = intval($data["id"] ?? 0);
        $groupByName[$name] = $gpid;
        return $gpid;
    };

    // Groups: name => type (verbatim from legacy INSERTs)
    $querty_default_gpid  = $ensureGroup("Common DNS Queries",      "dnsquerytype");
    $querty_mx_gpid       = $ensureGroup("MX DNS Query",            "dnsquerytype");
    $querty_catz_gpid     = $ensureGroup("Bad categories",          "categories");
    $querty_google_gpid   = $ensureGroup("Google Domains",          "dstdom_regex");
    $querty_tld_gpid      = $ensureGroup("Suspicious TLD domains",  "dstdom_regex");
    $querty_ad_gpid       = $ensureGroup("AD domain.tld",           "dstdomain");
    $querty_dhsields_gpid = $ensureGroup("O-Day reputation",        "the_shields");
    $querty_localnet_gpid = $ensureGroup("Local network",           "src");

    if($querty_localnet_gpid == 0){
        $tpl->js_error("{failed}: Local network");
        return;
    }

    // Step 4: link each rule to its groups (mirroring exact legacy linkrule calls).
    $r1 = $newRuleIDs[1]; $r2 = $newRuleIDs[2]; $r3 = $newRuleIDs[3];
    $r4 = $newRuleIDs[4]; $r5 = $newRuleIDs[5]; $r6 = $newRuleIDs[6];

    linkrule($r1, $querty_localnet_gpid);
    linkrule($r1, $querty_default_gpid);
    linkrule($r1, $querty_tld_gpid);

    linkrule($r2, $querty_default_gpid);
    linkrule($r2, $querty_google_gpid);

    linkrule($r3, $querty_localnet_gpid);
    linkrule($r3, $querty_default_gpid);
    linkrule($r3, $querty_catz_gpid);

    linkrule($r4, $querty_localnet_gpid);
    linkrule($r4, $querty_mx_gpid);

    // Note: legacy had a duplicate linkrule(4,...) followed by linkrule(5,...);
    // the second call with r4 was a copy-paste bug — rule 5 only needs ad_gpid.
    linkrule($r5, $querty_ad_gpid);

    linkrule($r6, $querty_localnet_gpid);
    linkrule($r6, $querty_default_gpid);
    linkrule($r6, $querty_dhsields_gpid);

    // Step 5: wipe then re-seed items for each group via bulk API.
    // categories group: IDs 92,105,135,111,64,149
    acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$querty_catz_gpid");
    acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$querty_catz_gpid,
        "items"=>[
            ["pattern"=>"92"], ["pattern"=>"105"], ["pattern"=>"135"],
            ["pattern"=>"111"],["pattern"=>"64"], ["pattern"=>"149"],
        ],
        "mode"=>"replace_all",
    ]);

    // MX query type group
    acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$querty_mx_gpid");
    acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$querty_mx_gpid,
        "items"=>[["pattern"=>"MX"]],
        "mode"=>"replace_all",
    ]);

    // Active Directory domain placeholder
    acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$querty_ad_gpid");
    acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$querty_ad_gpid,
        "items"=>[["pattern"=>"domain.tld"]],
        "mode"=>"replace_all",
    ]);

    // Google domains regex
    acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$querty_google_gpid");
    acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$querty_google_gpid,
        "items"=>[["pattern"=>'(^|\.)(google|googlevideo|googleapis|googleusercontent)\.[a-z]+$']],
        "mode"=>"replace_all",
    ]);

    // Common DNS query types: A and NS
    acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$querty_default_gpid");
    acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$querty_default_gpid,
        "items"=>[["pattern"=>"A"],["pattern"=>"NS"]],
        "mode"=>"replace_all",
    ]);

    // Suspicious TLD domains (stored as \.tld$ regex patterns)
    acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$querty_tld_gpid");
    acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$querty_tld_gpid,
        "items"=>[
            ["pattern"=>'\.club$'],  ["pattern"=>'\.xxx$'],       ["pattern"=>'\.xyz$'],
            ["pattern"=>'\.bid$'],   ["pattern"=>'\.live$'],       ["pattern"=>'\.date$'],
            ["pattern"=>'\.name$'],  ["pattern"=>'\.top$'],        ["pattern"=>'\.link$'],
            ["pattern"=>'(co|web)\.ve$'],
        ],
        "mode"=>"replace_all",
    ]);

    // Local network CIDR ranges
    acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$querty_localnet_gpid");
    acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$querty_localnet_gpid,
        "items"=>[
            ["pattern"=>"10.0.0.0/8"],
            ["pattern"=>"172.16.0.0/12"],
            ["pattern"=>"192.168.0.0/16"],
        ],
        "mode"=>"replace_all",
    ]);

    // O-Day reputation group has no seed items — it gets populated by the shields feed.

    admin_tracks("Seeded DNS firewall example rules + groups");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    echo "LoadAjax('table-dnsfw-rules','$page?table=yes');";
}

function linkrule($aclid,$gpid){
    acls_api_send("POST","/proxy/acls/sqlite/link",[
        "table"=>"dnsfw_acls_link",
        "aclid"=>intval($aclid),
        "gpid"=>intval($gpid),
    ]);
}

function rule_enable(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	if(!dnsfw_require($tpl)){return;}
	$ID=intval($_GET["enable-js"]);
	$r = acls_api_get("/dnsfw/acls/$ID");
	// alert() imprime son argument tel quel: un jeton renvoye par le daemon s'y
	// affichait litteralement. js_error() resout les {tokens} et garde une seule
	// famille de dialogue pour les deux issues.
	if(!$r){$tpl->js_error("{failed}");return;}
	$enabled = intval($r["enabled"])==1 ? 0 : 1;
	// La ligne portait id='<md5>', pas id='acl-<ID>': ces deux lignes visaient un
	// element inexistant et l'etat grise ne suivait jamais la bascule. Le
	// tableau pose desormais les deux identifiants.
	if($enabled==1){
		$js="$( \"#acl-$ID\" ).removeClass( \"text-muted\" );";
	} else {
		$js="$( \"#acl-$ID\" ).addClass( \"text-muted\" );";
	}
	list($ok,$data) = acls_api_send("PUT","/dnsfw/acls/$ID",["enabled"=>$enabled]);
	if(!$ok){$tpl->js_error($data);return;}
    admin_tracks("Set a DNS Firewall ACL rule ID $ID to enable=$enabled");
	$libmem=new lib_memcached();
	$libmem->Delkey("DNSFWOBJS");
	echo $js;
}

function rule_js(){
    //LoadAjax('table-dnsfw-rules','$page?table=yes');
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval(trim($_GET["rule-id-js"]));
	$r = acls_api_get("/dnsfw/acls/$ID");
	$ruleName = is_array($r) ? $r["ruleName"] : "";
	$tpl->js_dialog5("{rule}: $ID $ruleName","$page?rule-tabs=$ID");
}

function rule_checker_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{rules_checker}","$page?rule-checker-popup=yes",950);
}
function rule_checker_popup(){

    $QUERY["A"]="[A] RFC 1035 (Address Record)";
    $QUERY["NS"]="[NS] RFC 1035 (Name Server Record)";
    $QUERY["CNAME"]="[CNAME] RFC 1035 (Canonical Name Record (Alias))";
    $QUERY["SOA"]="[SOA] RFC 1035 (Start of Authority Record)";
    $QUERY["PTR"]="[PTR] RFC 1035 (Pointer Record)";
    $QUERY["MX"]="[MX] RFC 1035 (Mail eXchanger Record)";
    $QUERY["TXT"]="[TXT] RFC 1035 (Text Record)";
    $QUERY["RP"]="[RP] RFC 1183 (Responsible Person)";
    $QUERY["AFSDB"]="[AFSDB] RFC 1183 (AFS Database Record)";
    $QUERY["SIG"]="[SIG] RFC 2535";
    $QUERY["KEY"]="[KEY] RFC 2535 & RFC 2930 ";
    $QUERY["AAAA"]="[AAAA] RFC 3596 (IPv6 Address)";
    $QUERY["LOC"]="[LOC] RFC 1876 (Geographic Location)";
    $QUERY["SRV"]="[SRV] RFC 2782 (Service Locator)";
    $QUERY["NAPTR"]="[NAPTR] RFC 3403 (Naming Authority Pointer)";
    $QUERY["KX"]="[KX] RFC 2230 (Key eXchanger)";
    $QUERY["CERT"]="[CERT] RFC 4398 (Certificate Record, PGP etc)";
    $QUERY["DNAME"]="[DNAME] RFC 2672 (Delegation Name Record, wildcard alias)";
    $QUERY["APL"]="[APL] RFC 3123 (Address Prefix List (Experimental)";
    $QUERY["DS"]="[DS] RFC 4034 (Delegation Signer (DNSSEC)";
    $QUERY["SSHFP"]="[SSHFP] RFC 4255 (SSH Public Key Fingerprint)";
    $QUERY["IPSECKEY"]="[IPSECKEY] RFC 4025 (IPSEC Key)";
    $QUERY["RRSIG"]="[RRSIG] RFC 4034 (DNSSEC Signature)";
    $QUERY["NSEC"]="[NSEC] RFC 4034 (Next-secure Record (DNSSEC))";
    $QUERY["DNSKEY"]="[DNSKEY] RFC 4034 (DNS Key Record (DNSSEC))";
    $QUERY["DHCID"]="[DHCID] RFC 4701 (DHCP Identifier)";
    $QUERY["NSEC3"]="[NSEC3] RFC 5155 (NSEC Record v3 (DNSSEC Extension))";
    $QUERY["NSEC3PARAM"]="[NSEC3PARAM] RFC 5155 (NSEC3 Parameters (DNSSEC Extension))";
    $QUERY["HIP"]="[HIP] RFC 5205 (Host Identity Protocol)";
    $QUERY["SPF"]="[SPF] RFC 4408 (Sender Policy Framework)";
    $QUERY["TKEY"]="[TKEY] RFC 2930 (Secret Key)";
    $QUERY["TSIG"]="[TSIG] RFC 2845 (Transaction Signature)";
    $QUERY["IXFR"]="[IXFR] RFC 1995 (Incremental Zone Transfer)";
    $QUERY["AXFR"]="[AXFR] RFC 1035 (Authoritative Zone Transfer)";
    $QUERY["ANY"]="[ANY] RFC 1035 AKA "*" (Pseudo Record)";
    $QUERY["TA"]="[TA] (DNSSEC Trusted Authorities)";
    $QUERY["DLV"]="[DLV] RFC 4431 (DNSSEC Lookaside Validation)";

    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();

    if(!isset($_SESSION["RULE_CHECKER_DOMAIN"])){$_SESSION["RULE_CHECKER_DOMAIN"]="www.ibm.com";}
    if(!isset($_SESSION["RULE_CHECKER_SRC"])){$_SESSION["RULE_CHECKER_SRC"]=$_SERVER["REMOTE_ADDR"];}
    if(!isset($_SESSION["RULE_CHECKER_QTYPE"])){$_SESSION["RULE_CHECKER_QTYPE"]="A";}
    if($_SESSION["RULE_CHECKER_SRC"]==null){$_SESSION["RULE_CHECKER_SRC"]=$_SERVER["REMOTE_ADDR"];}
    $tpl->field_hidden("t",$t);
    $form[]=$tpl->field_text("RULE_CHECKER_DOMAIN","{domain}",$_SESSION["RULE_CHECKER_DOMAIN"],true);
    $form[]=$tpl->field_ipv4("RULE_CHECKER_SRC","{src}",$_SESSION["RULE_CHECKER_SRC"],true);
    $form[]=$tpl->field_array_hash($QUERY,"RULE_CHECKER_QTYPE","{type}",$_SESSION["RULE_CHECKER_QTYPE"]);
    $jsafter="LoadAjax('query-$t','$page?rule-checker-results=yes&t=$t')";
    $html[]=$tpl->form_outside("{your_query}", @implode("\n", $form),null,"{verify}",$jsafter,"AsDnsAdministrator");
    $html[]="<div id='query-$t' style='margin:10px'></div>";
    echo $tpl->_ENGINE_parse_body($html);

}
function rule_checker_save(){
    // La saisie est seulement memorisee : rule_checker_results() la soumet a
    // articarest, qui rend le resultat directement (plus de fichier relu).
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    foreach (array("RULE_CHECKER_DOMAIN","RULE_CHECKER_SRC","RULE_CHECKER_QTYPE") as $key){
        if(isset($_POST[$key])){$_SESSION[$key]=trim((string)$_POST[$key]);}
    }
}
function rule_checker_results(){
    // POST /unbound/dnsfw/check : articarest valide la source, le domaine et le
    // type, lance dnsfw-debug.py sans shell et rend son HTML. Remplace
    // framework/unbound.php?dnsfw-checker.
    $tpl=new template_admin();
    $req=array(
        "source"=>isset($_SESSION["RULE_CHECKER_SRC"])?(string)$_SESSION["RULE_CHECKER_SRC"]:"",
        "domain"=>isset($_SESSION["RULE_CHECKER_DOMAIN"])?(string)$_SESSION["RULE_CHECKER_DOMAIN"]:"",
        "qtype"=>isset($_SESSION["RULE_CHECKER_QTYPE"])?(string)$_SESSION["RULE_CHECKER_QTYPE"]:"A",
    );
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/unbound/dnsfw/check",$req,40));
    if(!is_object($json) || empty($json->Status)){
        $err="articarest: no answer";
        if(is_object($json) && isset($json->Error)){$err=(string)$json->Error;}
        elseif(is_object($json) && isset($json->error)){$err=(string)$json->error;}
        echo $tpl->div_error(htmlspecialchars($err,ENT_QUOTES,"UTF-8"));
        return false;
    }
    echo $tpl->_ENGINE_parse_body((string)$json->Html);
    return true;
}



function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);



	$array["{rule}"]="$page?rule-settings=$ID";
	$RefreshTable=base64_encode("LoadAjax('table-dnsfw-rules','$page?table=yes');");
	$array["{objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=dnsfw_acls_link&RefreshTable=$RefreshTable";
	echo $tpl->tabs_default($array);
}




function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-settings"]);
	$tpl->CLUSTER_CLI=true;
	if($ID==0){echo $tpl->FATAL_ERROR_SHOW_128("WRONG ID NUMBER");return;}
	$r = acls_api_get("/dnsfw/acls/$ID");
	if(!$r){echo $tpl->FATAL_ERROR_SHOW_128("Failed to fetch rule $ID");return;}
    $form[]=$tpl->field_hidden("rule-save", "$ID");
    $form[]=$tpl->field_text("rulename", "{rule_name}", $r["ruleName"],true);
    $form[]=$tpl->field_text("description", "{description}", $r["description"],false);
    $form[]=$tpl->field_array_hash($GLOBALS["DNS_ACTIONS"],"action","{action}",$r["action"]);
    $form[]=$tpl->field_numeric("ttl","{ttl} <small>({seconds})</small>",$r["ttl"]);
    $form[]=$tpl->field_text("redirector","{dns_server}",$r["redirector"],null);
    $form[]=$tpl->field_text("reply","{reply}",$r["reply"],null);

	$jsafter="LoadAjax('table-dnsfw-rules','$page?table=yes');";

	$html=$tpl->form_outside(utf8_encode($r["ruleName"]), @implode("\n", $form),"{form_dnsfirewalr_explain}","{apply}",$jsafter,"AsDnsAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}

function rule_save(){
	$tpl=new template_admin();
	if(!dnsfw_require($tpl)){return;}
	$tpl->CLEAN_POST_XSS();

	$ID=intval($_POST["rule-save"]);
	$rulename=utf8_decode($_POST["rulename"] ?? "");

	list($ok,$data) = acls_api_send("PUT","/dnsfw/acls/$ID",[
		"ruleName"   => $rulename,
		"description"=> $_POST["description"] ?? "",
		"action"     => intval($_POST["action"] ?? 0),
		"redirector" => $_POST["redirector"] ?? "",
		"reply"      => $_POST["reply"] ?? "",
		"ttl"        => intval($_POST["ttl"] ?? 1800),
	]);
	if(!$ok){echo "<div class='alert alert-danger'>".htmlspecialchars($data)."</div>";return;}
    admin_tracks("update $rulename DNS Firewall ACL rule settings");
    $libmem=new lib_memcached();
    $libmem->Delkey("DNSFWOBJS");
}

function new_rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function HEADERS_ARRAY(){
	$HEADERSZ=[];
	$results = acls_api_get("/http-headers");
	if(is_array($results)){
		foreach ($results as $index=>$ligneHeaders){
			$name=$ligneHeaders["HeaderName"] ?? $ligneHeaders["headerName"] ?? "";
			if($name!=""){ $HEADERSZ[$name]=$name; }
		}
	}
	return $HEADERSZ;
}

function new_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("rulename", "{rule_name}", null,true);
    $form[]=$tpl->field_text("description", "{description}", null,false);
	$form[]=$tpl->field_array_hash($GLOBALS["DNS_ACTIONS"],"action","{action}",0);
    $form[]=$tpl->field_numeric("ttl","{ttl} <small>({seconds})</small>",1800);
    $form[]=$tpl->field_text("redirector","{dns_server}",null,null);
    $form[]=$tpl->field_text("reply","{reply}",null,null);
    $jsafter="LoadAjax('table-dnsfw-rules','$page?table=yes');BootstrapDialog1.close();";
	
	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),"{form_dnsfirewalr_explain}","{add}",$jsafter,"AsDnsAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

	
}



function rule_move(){
	$tpl=new template_admin();
	if(!dnsfw_require($tpl)){return;}
	$ID=intval($_GET["acl-rule-move"]);
	$dir=intval($_GET["acl-rule-dir"]);  // 1=up, 0=down
	list($ok,$data) = acls_api_send("PUT","/dnsfw/acls/$ID/move",["dir"=>$dir]);
	if(!$ok){$tpl->js_error($data);return;}
    $libmem=new lib_memcached();
    $libmem->Delkey("DNSFWOBJS");
}


// --- Lignes du tableau: une ligne = une fonction ----------------------------
function dnsfw_row_rule($tpl,$page,$ligne,$slave):string{
    $ID=intval($ligne["id"] ?? 0);
    // ATTENTION: utf8_encode() est conserve. Il forme une PAIRE avec les
    // utf8_decode() de rule_save()/new_rule_save(): le nom est stocke en
    // latin1 et reconverti a l'affichage. Retirer une seule des deux moities
    // casse les accents des regles deja enregistrees -- les deux ne peuvent
    // partir qu'avec une migration des donnees.
    $ruleName=$tpl->utf8_encode($ligne["ruleName"] ?? "");
    $MUTED=(intval($ligne["enabled"] ?? 0)==0)?" text-muted":"";

    // rule_enable() bascule la classe text-muted sur $("#acl-<ID>"): la ligne
    // portait id='<md5>' et cet identifiant n'existait nulle part, la bascule
    // ne changeait donc rien a l'ecran. C'est desormais l'id de la ligne, et le
    // rule_delete_js() vise donc le meme identifiant pour retirer la ligne.
    $h="<tr class='$MUTED' id='acl-$ID'>";
    $h.="<td class='center' style='width:1%' nowrap><span class='awc-mono'>".intval($ligne["xOrder"] ?? 0)."</span></td>";
    $h.="<td style='vertical-align:middle;width:1%' nowrap>"
        .$tpl->td_href(htmlspecialchars((string)$ruleName),"{click_to_edit}","Loadjs('$page?rule-id-js=$ID')")."</td>";
    $h.="<td style='vertical-align:middle'>".EXPLAIN_THIS_RULE($ID)."</td>";
    if($slave==0){
        $h.="<td class='center' style='width:1%' nowrap>"
            .$tpl->icon_check($ligne["enabled"] ?? 0,"Loadjs('$page?enable-js=$ID')",null,"AsDnsAdministrator")."</td>";
        $h.="<td class='center awc-nowrap' style='width:1%'>"
            .$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');")."&nbsp;"
            .$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');")."</td>";
        $h.="<td class='center' style='vertical-align:middle;width:1%' nowrap>"
            .$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')","AsDnsAdministrator")."</td>";
    }
    return $h."</tr>";
}

// La regle implicite de fin de chaine. Elle emettait SIX cellules meme quand le
// tableau n'en comptait que trois (mode esclave de cluster), et l'une portait un
// class='center'nowrap sans espace, donc un attribut malforme.
function dnsfw_row_default($tpl,$page,$slave):string{
    $h="<tr id='dnsfw-default-rule'>";
    $h.="<td class='center' style='width:1%' nowrap>-</td>";
    $h.="<td style='vertical-align:middle;width:1%' nowrap>"
        .$tpl->td_href("{default}","{click_to_edit}","Loadjs('$page?options-js=yes')")."</td>";
    $h.="<td style='vertical-align:middle'>{dnsfw_explain_theshields}</td>";
    if($slave==0){
        $h.="<td class='center' style='width:1%'>&nbsp;</td>";
        $h.="<td class='center' style='width:1%'>&nbsp;</td>";
        $h.="<td class='center' style='width:1%'>&nbsp;</td>";
    }
    return $h."</tr>";
}

function dnsfw_topbuttons($tpl,$page,$slave,$allRules):string{
    $jsexample="Loadjs('$page?js-example=yes');";
    $jsapply=$tpl->framework_buildjs("/unbound/restart",
        "unbound.restart.progress","unbound.restart.log","progress-dnsfw-restart",
        "LoadAjax('table-dnsfw-rules','$page?table=yes');");
    $jshelp="s_PopUpFull('https://wiki.articatech.com/dns-firewall',1024,768,'Bandwidth Limiting')";

    $h=array();
    $h[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top: 15px'>";
    if($slave==0){
        $h[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?newrule-js=yes',true);\"><i class='fa fa-plus'></i> {new_rule} </label>";
        // L'assistant d'exemples n'a de sens que si aucune regle n'est nommee.
        $rule1=null;
        foreach($allRules as $rr){ if(intval($rr["id"] ?? 0)==1){ $rule1=$rr; break; } }
        if(strlen(trim($rule1["ruleName"] ?? ""))==0){
            $h[]="<label class=\"btn btn btn-warning\" OnClick=\"$jsexample\"><i class='fas fa-hat-wizard'></i> {example} </label>";
        }
    }
    $h[]="<label class=\"btn btn btn-info\" OnClick=\"$jsapply\"><i class='fas fa-file-check'></i> {apply_configuration}</label>";
    $h[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-checker-js=yes',true);\"><i class='fas fa-search'></i> {rules_checker} </label>";
    $h[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?options-js=yes')\"><i class='fas fas fa-cogs'></i> {advanced_options} </label>";
    $h[]="<label class=\"btn btn btn-warning\" OnClick=\"$jshelp\"><i class='fas fa-question-circle'></i> Wiki </label>";
    $h[]="</div>";
    return @implode("\n",$h);
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$order=$tpl->_ENGINE_parse_body("{order}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$slave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

	$allRules = acls_api_get("/dnsfw/acls");
	if(!is_array($allRules)){ $allRules=array(); }

	$rows=array();
	foreach($allRules as $ligne) {
		$rows[]=dnsfw_row_rule($tpl,$page,$ligne,$slave);
	}
	$c=count($rows);
	// La regle par defaut ferme toujours la liste: elle n'entre pas dans le
	// compte des regles configurees.
	$rows[]=dnsfw_row_default($tpl,$page,$slave);

	$COLS=($slave==0)?6:3;

	$html=array();
	$html[]=dnsfw_topbuttons($tpl,$page,$slave,$allRules);
	$html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr>";
	$html[]="<th class='center' style='width:1%'>$order</th>";
	$html[]="<th nowrap>{rulename}</th>";
	$html[]="<th>{description}</th>";
	if($slave==0) {
		$html[]="<th class='center' nowrap>$enabled</th>";
		$html[]="<th class='center' style='width:1%'>&nbsp;</th>";
		$html[]="<th class='center' style='width:1%'>&nbsp;</th>";
	}
	$html[]="</tr></thead><tbody id='dnsfw-rows'>";
	$html[]=@implode("\n",$rows);
	// Le <tfoot> annoncait colspan='7' pour six colonnes au plus (trois en mode
	// esclave), et son <ul class='pagination'> n'etait jamais alimente: l'init
	// footable ciblait $('#table-<time>'), un identifiant qu'AUCUN element de la
	// page ne portait -- ni pagination, ni tri, ni filtrage.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='dnsfw-pager'></span>";
	$html[]="</div></div></div>";

	// $jsAfter/$GLOBALS["jsAfterEnc"] visaient 'table-proxyheader-rules', le
	// conteneur de la page des en-tetes de proxy, et n'etaient relus nulle part
	// dans ce fichier: copie oubliee.

	// ICON_SCRIPTS_COMPILE() et non $tpl->ICON_SCRIPTS: icon_check() alimente en
	// parallele $GLOBALS["TPL_ICON_SCRIPTS"], et seule la version COMPILE draine
	// les deux piles. Le script reste hors de _ENGINE_parse_body().
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(50,"dnsfw-rows","dnsfw-pager");
}

function new_rule_save(){
	$tpl=new template_admin();
	if(!dnsfw_require($tpl)){return;}
	$tpl->CLEAN_POST();

	$rulename=utf8_decode($_POST["rulename"] ?? "");
	$description=$_POST["description"] ?? null;
	if($description==null){
        $description="{added}". date("Y d l f H:i");
    }

	list($ok,$data) = acls_api_send("POST","/dnsfw/acls",[
		"ruleName"   => $rulename,
		"description"=> $description,
		"action"     => intval($_POST["action"] ?? 0),
		"reply"      => $_POST["reply"] ?? "",
		"ttl"        => intval($_POST["ttl"] ?? 1800),
		"redirector" => $_POST["redirector"] ?? "",
	]);
	if(!$ok){echo "<div class='alert alert-danger'>".htmlspecialchars($data)."</div>";return;}
	admin_tracks("Create a new $rulename DNS Firewall ACL rule ");
    admin_tracks("Created/Updated DNS Firewall rule $rulename");
}

function rule_delete($ID){
	$r = acls_api_get("/dnsfw/acls/$ID");
	$rulename = is_array($r) ? $r["ruleName"] : "";
	list($ok,$data) = acls_api_send("DELETE","/dnsfw/acls/$ID");
	if(!$ok){$tpl=new template_admin();$tpl->js_error($data);return false;}
    admin_tracks("Delete a DNS Firewall ACL rule ID $ID");
    $libmem=new lib_memcached();
    $libmem->Delkey("DNSFWOBJS");
    admin_tracks("Removed DNS Firewall Rule ID $ID $rulename");
    return true;
}
function EXPLAIN_THIS_RULE($ID){
    $tpl=new templates();
    $UnBoundCacheMinTTL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMinTTL"));
    $UnBoundCacheMAXTTL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMAXTTL"));

    if($UnBoundCacheMAXTTL==0){$UnBoundCacheMAXTTL=172800;}

	$page=CurrentPageName();
	$acls=new squid_acls_groups();
	$r = acls_api_get("/dnsfw/acls/$ID");
	if(!$r){ return ""; }
    $objects=$acls->getobjectsNameFromAclrule(intval($r["id"]),"black","dnsfw_acls_link");
    $description=$r["description"];
    if(count($objects)==0){
        $THEN_TEXT="{error_acl_no_object}";

        return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$THEN_TEXT</span>");

        if(isset($_GET["explain-this-rule"])){
            return $tpl->_ENGINE_parse_body($THEN_TEXT);
        }

    }
    if(count($objects)>0){$THEN[]="{for_objects} ". @implode(", {and} ", $objects)." {then}";}


    $action=intval($r["action"]);
	$reply=trim($r["reply"]);
	$ttl=intval($r["ttl"]);
	$redirector=$r["redirector"];

	if($action<>4 || $action<>5 ){$THEN[]=$GLOBALS["DNS_ACTIONS"][$action];}
	if($action==4){
        $THEN[]="{then} {NOERROR} {with} <strong>$reply (TTL:{$ttl}s)</strong>";
        if($ttl<$UnBoundCacheMinTTL){
            $THEN[]="<br><span class='text-danger font-bold'>{notice}: {cache-ttl} (Min) {$UnBoundCacheMinTTL}s</span>";
        }
        if($ttl>$UnBoundCacheMAXTTL){
            $THEN[]="<br><span class='text-danger font-bold'>{notice}: {cache-ttl} (Max) {$UnBoundCacheMAXTTL}s</span>";
        }
    }
    if($action==5){$THEN[]="{then} {query_dnssrv} {with} <strong>$redirector</strong>";}
    if($description<>null){$THEN[]="<br><small>($description)</small>";}
    $THEN_TEXT=@implode(" ",$THEN);

    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($THEN_TEXT);
    }


    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$THEN_TEXT</span>");



}

function status(){
    $tpl=new template_admin();
    $time=time();

    if(!is_file("img/squid/dnsfw_cnx-day.png")){

        $html=$tpl->div_error("{no_data}||{error_graphic_is_not_generated}");
        echo  $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $html[]="<center style='margin:30px'>
    <a href='javascript:blur()' OnClick=\"javascript:Loadjs('fw.rrd.php?img=dnsfw_cnx');\">
    <img src='img/squid/dnsfw_cnx-day.png?t=$time'></a>
    </center>";
    $html[]="<center style='margin:30px'>
<a href='javascript:blur()' OnClick=\"javascript:Loadjs('fw.rrd.php?img=dnsfw_users');\">
<img src='img/squid/dnsfw_users-day.png?t=$time'></a></center>";



    echo  $tpl->_ENGINE_parse_body($html);
    return true;
}

function options_popup(){
    $tpl=new template_admin();
    $DNSFireWallVerbose=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFireWallVerbose")));
    $DNSFireWallDefaultReply=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFireWallDefaultReply"));
    $DNSFireWallDefaultTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFireWallDefaultTTL"));
    if($DNSFireWallDefaultReply==null){$DNSFireWallDefaultReply="0.0.0.0";}
    if($DNSFireWallDefaultTTL==0){$DNSFireWallDefaultTTL=1800;}
    $DNSFireWallStatsRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFireWallStatsRetention"));
    if($DNSFireWallStatsRetention==0){$DNSFireWallStatsRetention=5;}


    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $DNSFireWallStatsRetention=5;
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{statistics_retention_parameters}||{retention_time_limited_license}"));
    }

    $form[]=$tpl->field_checkbox("DNSFireWallVerbose","{verbose_mode}",$DNSFireWallVerbose,false);

    $form[]=$tpl->field_ipv4("DNSFireWallDefaultReply","{NOERROR} ({default})",$DNSFireWallDefaultReply);
    $form[]=$tpl->field_numeric("DNSFireWallDefaultTTL","{ttl} {seconds} ({default})",$DNSFireWallDefaultTTL);
    $form[]=$tpl->field_numeric("DNSFireWallStatsRetention","{retention_days} ({statistics})",$DNSFireWallStatsRetention,"{SuricataPurges}");




    $hml[]=$tpl->form_outside("{options}", @implode("\n", $form),null,"{apply}","blur();","AsSystemAdministrator");
    echo @implode("\n", $hml);
}

function options_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{advanced_options}";
    $tpl->js_dialog($title,"$page?options-popup=yes");

}

function options_save(){
    $tpl=new template_admin();
    // Le formulaire est garde par "AsSystemAdministrator": la meme exigence
    // cote serveur, sinon le repartiteur repond a toute session authentifiee.
    if(!dnsfw_require($tpl,"AsSystemAdministrator")){return;}
    $tpl->CLEAN_POST();
    // SAVE_POSTs() sans argument ecrit TOUTE cle postee dans les reglages: le
    // formulaire n'en compte que quatre.
    $KEYS=array("DNSFireWallVerbose","DNSFireWallDefaultReply","DNSFireWallDefaultTTL","DNSFireWallStatsRetention");
    $corp=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
    foreach($KEYS as $k){
        if(!isset($_POST[$k])){continue;}
        if(is_array($_POST[$k])){continue;}
        $v=(string)$_POST[$k];
        // La fenetre plafonne deja la retention a 5 jours sans licence
        // corporate; la borne n'existait QUE dans l'affichage.
        if($k=="DNSFireWallStatsRetention" && !$corp && intval($v)>5){$v="5";}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($k,$v);
    }
    admin_tracks("DNS Firewall: saved advanced options");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/reconfigure");
}

