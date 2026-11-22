<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// Politique Meta : badge et lecture seule des objets tenus (metaid).
include_once(dirname(__FILE__)."/ressources/class.proxypac.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete"])){rule_delete_perform();exit;}
if(isset($_GET["countItems"])){countItems();exit;}
if(isset($_GET["remove-rules-js"])){remove_rules_js();exit;}
if(isset($_POST["remove-rules-perform"])){remove_rules_perform();exit;}
if(isset($_GET["notify-lb-js"])){notify_lb_js();exit;}
page();


function countItems(){
    $tpl                =new template_admin();
    $gpid=intval($_GET["countItems"]);
    $rows=acls_api_get("/proxy/acls/sqlite/items?gpid=$gpid");
    $items=is_array($rows)?count($rows):0;
    echo $tpl->FormatNumber($items);
}
function notify_lb_js():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/lb/sqlite"),true);

    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    return $tpl->js_ok("{success}");
}

function remove_rules_perform():bool{
    // Politique Meta : la remise a zero est refusee (423) des qu'un objet est tenu.
    if(proxyobject_meta_any_held()){
        echo (new template_admin())->javascript_parse_text("{proxyobject_meta_some_held}");
        return false;
    }
    // Factory reset of every ACL table. The (verbatim) table list now lives
    // server-side in articarest; see POST /proxy/acls/sqlite/reset. Audit entry
    // stays here.
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/reset");
    if(!$ok){ return false; }
    return admin_tracks("As deleted all acls!");
}
function remove_rules_js():bool{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl =new template_admin();
    if(proxyobject_meta_any_held()){
        header("content-type: application/x-javascript");
        return $tpl->js_error("{proxyobject_meta_some_held}");
    }
    return $tpl->js_confirm_delete("{acl_warn_remove_all}","remove-rules-perform","yes","$function()");
}
function start(){
    $tpl                =new template_admin();
    $page               = CurrentPageName();
    $title              = $_GET["title"];
    $explain            = $_GET["explain"];
    $links              = base64_encode($_GET["links"] ?? "");
    $titleenc              = base64_encode($title);
    $Explainenc         = base64_encode($explain);
    $firewall_query     = intval($_GET["firewall_query"] ?? 0);
    $ProxyPac        = intval($_GET["ProxyPac"] ?? 0);
    $HaCluster= intval($_GET["hacluster"] ?? 0);
    $addons  = array();
    $addons[]="title=".urlencode($titleenc);
    $addons[]="explain=".urlencode($Explainenc);
    $addons[]="links=".urlencode($links);
    $addons[]="ProxyPac=$ProxyPac";
    $addons[]="hacluster=$HaCluster";

    if($firewall_query==1){
        $addons[]="firewall=yes";
    }
    $saddon=@implode("&",$addons);

    // Tête du tableau : familles (remplies par le répondeur, elles dépendent des
    // objets présents), recherche, bouton. Seul l'hôte est rechargé, la saisie
    // garde donc le focus.
    $html=array();
    $html[]="<div class='ak-tools fwo-bar'>";
    $html[]="<div class='fwo-segs' id='fwo-segs'></div>";
    $html[]="<span class='ak-grow'></span>";
    $html[]="<div class='fwo-q'><span class='fwo-qico'><i class='fas fa-magnifying-glass'></i></span>";
    $html[]="<input type='text' id='fwo-q' placeholder=\"{fwo_search_placeholder}\" OnKeyPress=\"if(checkEnter(event)){FwoReload();}\"></div>";
    $html[]="<button type='button' class='ak-btn ak-btn-lg' OnClick='FwoReload()'>{fwo_filter}</button>";
    $html[]="</div>";
    $html[]="<div id='fwo-host'></div>";
    $html[]="<div class='fwo-tip'><span class='ic'><i class='fas fa-circle-info'></i></span><div>{fwo_tip}</div></div>";

    $script="<script>
window.__fwoType='';
function FwoReload(){
    var e=document.getElementById('fwo-q');
    LoadAjaxSilent('fwo-host','$page?search='+encodeURIComponent(e?e.value:'')+'&type='+encodeURIComponent(window.__fwoType)+'&function=FwoReload&$saddon');
}
function FwoType(t){window.__fwoType=t;FwoReload();}
FwoReload();
</script>";
    echo fwo_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
}

// ── Kit fwo-* (maquette stitch_firewall_rules_objects) ───────────────────────
function fwo_accents():array{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

// Famille d'affichage d'un type d'objet : couleur de l'étiquette et de la vignette.
function fwo_family(string $type):string{
    $map=array(
        "ip"=>array("src","dst","arp","localnet","rgexsrc","rgexdst","maxconn","max_user_ip","srcproxy","dstproxy"),
        "geo"=>array("geoip"),
        "dns"=>array("dstdomain","dnsdynamic","srcdomain","dstdom_regex","ssl_sni","ssl_sni_regex","url_regex","urlpath_regex","referer_regex"),
        "port"=>array("port","dst_port","myportname","method","FTP"),
        "user"=>array("proxy_auth","proxy_auth_authenticated","proxy_auth_ads","proxy_auth_adou","proxy_auth_statad","proxy_auth_extad","proxy_auth_tagad","proxy_auth_multiad","ldap_group","proxy_auth_ldap","ext_user","radius_auth","ad_auth","ldap_auth"),
        "feed"=>array("reputation","the_shields","categories","url_db"),
        "group"=>array("AclsGroup"),
    );
    foreach($map as $fam=>$types){
        if(in_array($type,$types,true)){return $fam;}
    }
    return "other";
}

function fwo_style():string{
    if(isset($GLOBALS["FWO_STYLE"])){return "";}
    $GLOBALS["FWO_STYLE"]=true;
    $ac=fwo_accents();
    $a=$ac["accent"];$ad=$ac["accent_dark"];$as=$ac["accent_soft"];$al=$ac["accent_line"];
    $c=array();
    $c[]=".fwo-bar,.fwo-bar *,.fwo-skin,.fwo-skin *,.fwo-tip{box-sizing:border-box}";
    $c[]=".fwo-bar{margin-top:10px}";
    $c[]=".fwo-segs{display:flex;gap:4px;flex-wrap:wrap;min-height:38px;align-items:center}";
    $c[]=".fwo-seg{height:38px;padding:0 14px;border:0;border-radius:10px;background:transparent;color:#334155;font-size:14px;font-weight:500;cursor:pointer;white-space:nowrap}";
    $c[]=".fwo-seg span{color:#94a3b8;margin-left:4px}";
    $c[]=".fwo-seg:hover{background:#f1f5f9}";
    $c[]=".fwo-seg.cur{background:#0f172a;color:#fff;font-weight:700}.fwo-seg.cur span{color:#cbd5e1}";
    $c[]=".fwo-q{position:relative;flex:0 1 380px;min-width:200px}";
    $c[]=".fwo-qico{position:absolute;left:12px;top:0;bottom:0;display:flex;align-items:center;color:#94a3b8;font-size:14px;pointer-events:none}";
    $c[]=".fwo-q input{width:100%;height:40px;border:1px solid #e2e8f0;border-radius:10px;padding:0 12px 0 36px;font-size:14px;color:#1e293b;background:#f8fafc;outline:none}";
    $c[]=".fwo-q input:focus{border-color:$a;box-shadow:0 0 0 3px $as;background:#fff}";
    $c[]=".fwo-skin .awc-card{border-radius:12px;border-color:#e2e8f0}";
    $c[]=".fwo-skin .awc-tbl td{vertical-align:middle;padding-top:16px;padding-bottom:16px}";
    $c[]=".fwo-id{font-family:'JetBrains Mono',ui-monospace,monospace;font-size:13px;font-weight:600;color:#64748b;white-space:nowrap}";
    $c[]=".fwo-obj{display:flex;align-items:center;gap:14px;min-width:240px}";
    $c[]=".fwo-tile{background:#f1f5f9;color:#64748b}";
    $c[]=".fwo-name{font-size:15px;font-weight:700;color:#0f172a;cursor:pointer}.fwo-name:hover{text-decoration:underline}";
    $c[]=".fwo-name.off{color:#94a3b8}";
    $c[]=".fwo-desc{font-size:13px;color:#94a3b8;margin-top:2px;line-height:1.45}";
    $c[]=".fwo-members{font-size:12px;color:#64748b;margin-top:4px}";
    $c[]=".fwo-type{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:6px;font-size:13px;font-weight:500;border:1px solid #e2e8f0;background:#f8fafc;color:#475569;white-space:nowrap}";
    $c[]=".fwo-type:before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;flex:0 0 6px}";
    // Familles : étiquette + vignette
    $fam=array("ip"=>array($as,$al,$ad),"geo"=>array("#e6eeed","#bfd4d1","#005447"),"dns"=>array("#eff6ff","#bfdbfe","#1d4ed8"),
        "port"=>array("#fffbeb","#fde68a","#b45309"),"user"=>array("#f2f6f6","#bfd4d1","#005447"),"feed"=>array("#fff7ed","#fed7aa","#c2410c"),
        "group"=>array("#f1f5f9","#cbd5e1","#334155"));
    foreach($fam as $k=>$v){
        $c[]=".fwo-type.f-$k{background:{$v[0]};border-color:{$v[1]};color:{$v[2]}}";
        $c[]=".fwo-tile.f-$k{background:{$v[0]};color:{$v[2]}}";
    }
    $c[]=".fwo-rules{font-size:13.5px;color:#0f172a;line-height:1.9}";
    $c[]=".fwo-rules svg.fa-circle-1,.fwo-rules svg[class*='fa-circle-']{color:#0f172a}";
    $c[]=".fwo-none{color:#cbd5e1}";
    $c[]=".ak-sw.fwo-ro{cursor:default;opacity:.6}";
    $c[]=".fwo-tip{display:flex;gap:14px;align-items:flex-start;margin-top:16px;padding:16px 20px;border-radius:12px;background:$as;border:1px solid $al;color:#334155;font-size:14px;line-height:1.55}";
    $c[]=".fwo-tip .ic{flex:0 0 32px;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fff;color:$ad;font-size:15px}";
    return "<style>".@implode("\n",$c)."</style>";
}

function page(){
    $tpl                =new template_admin();
    $page               = CurrentPageName();
    $links              = "proxy-objects";
    $title              = "{proxy_objects}";
    $Explain            = "{proxy_objects_explain}";
    $firewall_query     = 0;
    $addons=null;

    if(isset($_GET["firewall"])){
        $title="{firewall_objects}";
        $Explain="{firewall_objects_explain}";
        $links="firewall-objects";
        $addons="&firewall=yes";
        $firewall_query=1;
    }
    if(isset($_GET["ids"])){
        $title="{firewall_objects}";
        $Explain="{firewall_objects_explain}";
        $links="ids-objects";
        $addons="&firewall=yes";
        $firewall_query=1;
    }
    if (!isset($_GET["hacluster"])) {
        $_GET["hacluster"] = 0;
    }
    // Pentest 2026-11-04 : hacluster etait recopie sans encodage dans la page (XSS).
    $hacluster="&hacluster=".intval($_GET["hacluster"]);

    $titleenc=urlencode($title);
    $Explainenc=urlencode($Explain);
    $error=null;
    if($hacluster==1){
        $links="hacluster-objects";
    }


    $html=$tpl->page_header($title,"fas fa-cubes",
        $Explain,"$page?start=yes&title=$titleenc&explain=$Explainenc&links=$links&firewall_query=$firewall_query$hacluster",
        $links,"progress-pobjects-restart",false,"div-acls_objects-list");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{proxy_objects}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function rule_delete_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-rule-js"]);
    if(proxyobject_meta_refuse_js($ID)){return;}
    $md=$_GET["md"];
    $group=acls_api_get("/proxy/acls/sqlite/group/$ID");
    $GroupName=is_array($group)?($group["groupName"]??''):'';
    $tpl->js_confirm_delete(utf8_encode($GroupName), "delete", $ID,"$('#$md').remove()");
}
function rule_delete_perform(){
    $ID=$_POST["delete"];
    if(proxyobject_meta_refuse_text($ID)){return;}
    $acls=new squid_acls();
    $acls->delete_group($ID);

}

function enable_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $ID=intval($_GET["enable-js"]);
    $revert="var _sw=document.getElementById('fwo-sw-$ID');if(_sw){_sw.classList.toggle('on');}";
    $users=new usersMenus();
    if(!$users->AsDansGuardianAdministrator){echo $revert;$tpl->js_error("{ERROR_NO_PRIVS}");return;}
    if(proxyobject_meta_held($ID)){echo $revert;$tpl->js_error("{proxyobject_meta_locked}");return;}
    $group=acls_api_get("/proxy/acls/sqlite/group/$ID");
    if(!is_array($group)){echo $revert;$tpl->js_error("{protocol_error}");return;}
    $newVal=intval($group["enabled"]??1)==1?0:1;
    list($ok,$data)=acls_api_send("PUT","/proxy/acls/sqlite/group/$ID",["enabled"=>$newVal]);
    if(!$ok){echo $revert;$tpl->js_error("{protocol_error}");return;}
    admin_tracks("Object Group$ID ".($newVal==1?"enabled":"disabled"));
}

function IntelligentSearch($search):array{
    $search=trim($search);
    if(preg_match("#^group([0-9]+)$#i",$search,$re)){
        $search=$re[1];
    }

    if(is_numeric($search)){
        $ID=intval($search);
        $group=acls_api_get("/proxy/acls/sqlite/group/$ID");
        if(!is_array($group)){return array();}
        // Normalise to legacy field-name keys expected by table()
        return array(array(
            "ID"        => $group["id"],
            "GroupName" => $group["groupName"],
            "GroupType" => $group["groupType"],
            "enabled"   => $group["enabled"]??1,
        ));
    }
    if($search==''){return array();}
    // Recherche dans le CONTENU des objets (pattern / description des items),
    // comme la requete SQL d'origine (jointure webfilters_sqgroups x
    // webfilters_sqitems). La recherche par NOM est deja faite par table() via
    // groups/all?search= : la migration API l'avait recopiee ici a la place de
    // celle-ci, si bien qu'un item (un poste, une IP, un domaine) ne faisait
    // plus remonter l'objet qui le contient.
    $out=array();
    // groups/by-item renvoie directement les objets distincts, enabled compris.
    $hits=acls_api_get("/proxy/acls/sqlite/groups/by-item?search=".urlencode($search));
    if(is_array($hits)){
        foreach($hits as $g){
            if(!is_array($g)){continue;}
            $out[]=array(
                "ID"        => $g["id"],
                "GroupName" => $g["groupName"],
                "GroupType" => $g["groupType"],
                "enabled"   => $g["enabled"]??1,
            );
        }
        return $out;
    }
    // Repli pour un articarest qui ne connait pas encore groups/by-item (null
    // = route absente, un resultat vide y est un tableau vide) : items/search
    // plafonne a 20 ITEMS, un objet riche en correspondances peut donc masquer
    // les autres.
    $items=acls_api_get("/proxy/acls/sqlite/items/search?search=".urlencode($search));
    if(!is_array($items)){return array();}
    $seen=array();
    foreach($items as $it){
        if(!is_array($it)){continue;}
        $gpid=intval($it["gpid"] ?? 0);
        if($gpid<1 || isset($seen[$gpid])){continue;}
        $seen[$gpid]=true;
        // Item orphelin (groupe supprime) : group/<id> ne repond rien, on l'ignore.
        $group=acls_api_get("/proxy/acls/sqlite/group/$gpid");
        if(!is_array($group)){continue;}
        $out[]=array(
            "ID"        => $group["id"],
            "GroupName" => $group["groupName"],
            "GroupType" => $group["groupType"],
            "enabled"   => $group["enabled"]??1,
        );
    }
    return $out;
}

function table(){
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $qProxy         = new mysql_squid_builder();
    $acls           = new squid_acls();
    $function       = preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $jsafter        = base64_encode("$function()");
    $ProxyPac       = intval($_GET["ProxyPac"] ?? 0);
    $hacluster       = intval($_GET["hacluster"] ?? 0);
    $qProxy->acl_GroupType["geoip"] = "{geoip_location}";
    $qProxy->acl_GroupType["dnsdynamic"]="{dnsdynamic}";
    $qProxy->acl_GroupType["AclsGroup"]="{group_of_objects}";
    $typeFilter=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["type"] ?? ""));
    $users=new usersMenus();
    $canWrite=(bool)$users->AsDansGuardianAdministrator;

    $acls->LoadGroupsInMemory(true);

    $_GET["search"]=$tpl->CLEAN_BAD_XSS($_GET["search"]??'');
    $searchTerm=trim($_GET["search"]??'');

    // Liste principale (recherche sur le nom) puis objets dont le CONTENU correspond.
    $apiRows=acls_api_get("/proxy/acls/sqlite/groups/all?search=".urlencode($searchTerm));
    $results=array();
    if(is_array($apiRows)){
        foreach($apiRows as $g){
            $results[]=array("ID"=>$g["id"],"GroupName"=>$g["groupName"],"GroupType"=>$g["groupType"]);
        }
    }
    if(strlen($searchTerm)>0) {
        $results2 = IntelligentSearch($searchTerm);
        if (count($results2) > 0) {
            $results = array_merge($results, $results2);
        }
    }

    // Dédoublonnage + compteurs par type (le bandeau suit la recherche, pas le type choisi).
    $unique=array();$typeCount=array();
    foreach($results as $ligne){
        $ID=intval($ligne["ID"]);
        if($ID<1 || isset($unique[$ID])){continue;}
        $unique[$ID]=$ligne;
        $t=(string)$ligne["GroupType"];
        $typeCount[$t]=($typeCount[$t] ?? 0)+1;
    }
    arsort($typeCount);
    $topTypes=array_slice(array_keys($typeCount),0,5);
    $otherCount=0;
    foreach($typeCount as $t=>$n){if(!in_array($t,$topTypes,true)){$otherCount+=$n;}}

    $segs=array();
    $segs[]=array("","{fwo_all_types}",count($unique));
    foreach($topTypes as $t){
        $segs[]=array($t,$qProxy->acl_GroupType[$t] ?? $t,$typeCount[$t]);
    }
    if($otherCount>0){$segs[]=array("__other","{fwo_other_types}",$otherCount);}
    if($typeFilter!="" && $typeFilter!="__other" && !in_array($typeFilter,$topTypes,true)){$typeFilter="";}
    $segHtml=array();
    foreach($segs as $sg){
        $cur=$sg[0]===$typeFilter?" cur":"";
        $segHtml[]="<button type='button' class='fwo-seg$cur' OnClick=\"FwoType('{$sg[0]}')\">{$sg[1]}<span>({$sg[2]})</span></button>";
    }

    $html=array();
    $html[]="<div id='fwo-segs-src' style='display:none'>".@implode("",$segHtml)."</div>";
    $html[]="<div class='awc-wrap fwo-skin'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{status}</th><th>ID</th><th>{object_name}</th><th>{type}</th>";
    $html[]="<th>{fwo_linked_rules}</th><th style='text-align:center'>{items}</th><th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='objectslist-rows'>";

    $shown=0;
    foreach ($unique as $ID=>$ligne){
        $type=(string)$ligne["GroupType"];
        if($typeFilter=="__other" && in_array($type,$topTypes,true)){continue;}
        if($typeFilter!="" && $typeFilter!="__other" && $type!==$typeFilter){continue;}
        $html[]=fwo_row($ID,$ligne,$qProxy,$jsafter,$ProxyPac,$canWrite);
        $shown++;
    }
    if($shown==0){
        $html[]="<tr><td colspan='7' class='awc-empty'>{no_data}</td></tr>";
    }

    $firewall_query=0;
    // Pentest 2026-11-04 : title/explain/links etaient recopies sans encodage dans la page (XSS).
    // Ils ne portent que des jetons de traduction ({proxy_objects}...) et un nom de rubrique.
    $title=preg_replace('/[^A-Za-z0-9_{} -]/','',base64_decode($_GET["title"] ?? ""));
    $explain=preg_replace('/[^A-Za-z0-9_{} -]/','',base64_decode($_GET["explain"] ?? ""));
    $function=base64_encode($function."();");
    $links=preg_replace('/[^A-Za-z0-9_-]/','',base64_decode($_GET["links"] ?? ""));
    if(isset($_GET["firewall_query"])) {
        $firewall_query = intval($_GET["firewall_query"]);
    }
    if(isset($_GET["firewall"])){
        if($_GET["firewall"]=="yes"){
            $firewall_query=1;
        }
    }

    $new_object="Loadjs('fw.proxy.acls.objects.php?new-object-js=yes&ID=0&direction=0&TableLink=&RefreshTable=$function&ProxyPac=0&firewall=$firewall_query&hacluster=$hacluster')";
    $import3x="Loadjs('fw.proxy.acls.objects.import3x.php?func=$function')";
    $topbuttons = array();
    if(isWritter($users)) {
        if ($users->AsDansGuardianAdministrator) {
            $topbuttons[] = array($new_object, ico_plus, "{new_object}");
        }
        if ($users->AsSquidAdministrator) {
            if ($hacluster == 0) {
                $topbuttons[] = array($import3x, ico_download, "{import} 3.x");
            }
        }
        $topbuttons[]= array("Loadjs('fw.proxy.acls.backups.php?function=$function')", ico_file_zip, "{backups}");
        // Politique Meta : pas de remise a zero tant qu'un objet est tenu.
        if(!proxyobject_meta_any_held()) {
            $topbuttons[] = array("Loadjs('$page?remove-rules-js=yes&function=".preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""))."')", ico_trash, "{delete_all}");
        }

        if ($hacluster == 1) {
            $links = "hacluster-objects";
        }
        $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if ($HaClusterClient == 1) {
            $PowerDNSEnableClusterMaster = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
            if ($PowerDNSEnableClusterMaster == 1) {
                $topbuttons[] = array("Loadjs('$page?notify-lb-js=yes')", ico_refresh, "{notify}: {load_balancer}");
            }
        }
    }

    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]="fas fa-cubes";
    $TINY_ARRAY["EXPL"]=$explain;
    $TINY_ARRAY["URL"]=$links;
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} <strong>$shown</strong> / <strong>".count($unique)."</strong> {objects}</span>"
           ."<span class='awc-pg' id='objectslist-pager'></span></div>";
    $html[]="</div></div>";

    // Les familles partent dans le bandeau statique (hors de l'hôte rechargé).
    $script="<script>
NoSpinner();
(function(){var s=document.getElementById('fwo-segs-src'),d=document.getElementById('fwo-segs');if(s&&d){d.innerHTML=s.innerHTML;}if(s){s.parentNode.removeChild(s);}})();
$jstiny
</script>";

    echo awc_table_style().fwo_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .$script
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"objectslist-rows","objectslist-pager");
}

// Une ligne = un objet. Une fonction par ligne : la vue reste lisible et diffable.
function fwo_row(int $ID,array $ligne,$qProxy,string $jsafter,int $ProxyPac,bool $canWrite):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $md=md5(serialize($ligne));
    $type=(string)$ligne["GroupType"];
    $fam=fwo_family($type);
    $typeLabel=$qProxy->acl_GroupType[$type] ?? $type;
    $ico=$qProxy->acl_GroupTypeIcon[$type] ?? "fa-duotone fa-layer-group";
    if($type=="AclsGroup"){$ico="fa-duotone fa-layer-group";}

    // Détail : état et description (la liste ne porte que id/nom/type).
    $detail=acls_api_get("/proxy/acls/sqlite/group/$ID");
    $enabled=is_array($detail)?intval($detail["enabled"]??1):1;
    $desc=is_array($detail)?trim((string)($detail["description"] ?? "")):"";

    $edit="Loadjs('fw.rules.items.php?groupid=$ID&js-after=&TableLink=&RefreshTable=$jsafter&ProxyPac=$ProxyPac&firewall=0&RefreshFunction=$jsafter')";

    $members="";
    if(isset($qProxy->acl_ARRAY_NO_ITEM[$type])){
        $items="-";
    }elseif($type=="AclsGroup"){
        $graph=ACLS_GROUPS_GRAPH();
        $n=0;$names=array();
        if(isset($graph["children"][$ID])){
            foreach ($graph["children"][$ID] as $childID=>$linkEnabled){
                $n++;
                $childName=htmlspecialchars((string)($graph["names"][$childID] ?? ""));
                $jsSlave="Loadjs('fw.rules.items.php?groupid=$childID&js-after=&TableLink=&RefreshTable=$jsafter&ProxyPac=$ProxyPac&firewall=0&RefreshFunction=$jsafter')";
                $names[]=$tpl->td_href($childName,null,$jsSlave);
            }
        }
        $items="$n {objects}";
        if(count($names)>0){$members="<div class='fwo-members'>".@implode(" &middot; ",$names)."</div>";}
    }else{
        $itemRows=acls_api_get("/proxy/acls/sqlite/items?gpid=$ID");
        $items=$tpl->FormatNumber(is_array($itemRows)?count($itemRows):0)." {items}";
    }

    $rules=WHICH_RULE_OBJECT($ID);
    if($rules==""){$rules="<span class='fwo-none'>&mdash;</span>";}

    $name=$tpl->utf8_encode((string)$ligne["GroupName"]);
    $nameHtml=htmlspecialchars($name);
    $descHtml=$desc!=""?"<div class='fwo-desc'>".str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars($desc))."</div>":"";

    // Politique Meta : objet tenu = badge, ni bascule ni suppression.
    $held=proxyobject_meta_held($ID);
    if($held){$canWrite=false;}
    $sw=$canWrite
        ?"<button type='button' class='ak-sw".($enabled==1?" on":"")."' id='fwo-sw-$ID' OnClick=\"this.classList.toggle('on');Loadjs('$page?enable-js=$ID')\"></button>"
        :"<span class='ak-sw fwo-ro".($enabled==1?" on":"")."'></span>";
    $del=$canWrite?"<a class='ak-act ak-danger' title='{delete}' OnClick=\"Loadjs('$page?delete-rule-js=$ID&md=$md')\"><i class='".ico_trash."'></i></a>":"";

    $h=array();
    $h[]="<tr id='$md'>";
    $h[]="<td style='width:1%'>$sw</td>";
    $h[]="<td style='width:1%'><span class='fwo-id'>Group$ID</span></td>";
    $h[]="<td><div class='fwo-obj'><span class='ak-ico ak-ico-sq fwo-tile f-$fam'><i class='$ico'></i></span><div>";
    $h[]="<span class='fwo-name".($enabled==1?"":" off")."' OnClick=\"$edit\">$nameHtml</span>".proxyobject_meta_badge($ID)."$descHtml$members</div></div></td>";
    $h[]="<td><span class='fwo-type f-$fam'>$typeLabel</span></td>";
    $h[]="<td><div class='fwo-rules'>$rules</div></td>";
    $h[]="<td style='text-align:center'><span class='ak-pill ak-off'>$items</span></td>";
    $h[]="<td style='text-align:right;white-space:nowrap'><a class='ak-act' title='{fwsvc_edit}' OnClick=\"$edit\"><i class='fas fa-pen'></i></a>$del</td>";
    $h[]="</tr>";
    return @implode("",$h);
}

/**
 * Graphe complet des groupes d'objets, construit UNE seule fois par requete.
 *
 * webfilters_gpslink porte l'appartenance d'un objet a un "groupe d'objets"
 * (GroupType = AclsGroup): groupid = le groupe conteneur, gpid = le membre.
 * Un AclsGroup peut lui-meme etre membre d'un autre AclsGroup, et la table
 * peut contenir un CYCLE -- le demon s'en premunit explicitement
 * (articarest squidacls/GlobalAccess.go: "the changed flag also terminates on
 * a gpslink cycle"), le parcours de ACLS_OBJECT_ANCESTORS() aussi.
 *
 * Chaque arete garde son propre drapeau enabled: un lien desactive laisse le
 * membre dans la liste affichee du groupe, mais ne le fait PLUS entrer dans
 * les regles -- loadGpslink() cote demon ignore enabled=0 lors de la
 * generation de squid.conf. Les deux lectures sont donc distinctes.
 *
 * Rend array(
 *   "names"    => array(ID => GroupName),
 *   "types"    => array(ID => GroupType),
 *   "children" => array(parentID => array(childID  => enabled)),
 *   "parents"  => array(childID  => array(parentID => enabled)),
 * )
 */
function ACLS_GROUPS_GRAPH():array{
    if(isset($GLOBALS["ACLS_GROUPS_GRAPH"])){return $GLOBALS["ACLS_GROUPS_GRAPH"];}
    $graph=array("names"=>array(),"types"=>array(),"children"=>array(),"parents"=>array());

    // Sans "search": le graphe doit couvrir TOUS les groupes, pas seulement les
    // lignes filtrees a l'ecran -- un objet affiche peut appartenir a un
    // AclsGroup que la recherche courante ne fait pas remonter.
    $all=acls_api_get("/proxy/acls/sqlite/groups/all");
    if(is_array($all)){
        foreach($all as $g){
            if(!is_array($g)){continue;}
            $gid=intval($g["id"] ?? 0);
            if($gid<1){continue;}
            $graph["names"][$gid]=(string)($g["groupName"] ?? "");
            $graph["types"][$gid]=(string)($g["groupType"] ?? "");
        }
    }

    foreach($graph["types"] as $gid=>$GroupType){
        if($GroupType!="AclsGroup"){continue;}
        $links=acls_api_get("/proxy/acls/sqlite/gpslinks?groupid=$gid");
        if(!is_array($links)){continue;}
        foreach($links as $link){
            if(!is_array($link)){continue;}
            $childID=intval($link["gpid"] ?? 0);
            if($childID<1){continue;}
            if($childID==$gid){continue;}
            $enabled=intval($link["enabled"] ?? 1);
            $graph["children"][$gid][$childID]=$enabled;
            $graph["parents"][$childID][$gid]=$enabled;
        }
    }

    $GLOBALS["ACLS_GROUPS_GRAPH"]=$graph;
    return $graph;
}

/**
 * Tous les AclsGroup qui contiennent l'objet $ID, directement ou par
 * imbrication. Parcours en largeur: le premier chemin atteint est le plus
 * court, et le jeu $seen -- amorce sur $ID lui-meme -- rend le parcours
 * insensible aux cycles de gpslink.
 *
 * Seules les aretes ACTIVES sont suivies: une appartenance desactivee ne fait
 * pas entrer l'objet dans les regles du groupe, exactement comme cote demon.
 *
 * Rend array(ancestorID => "GroupeA &rsaquo; GroupeB"), le chemin parcouru
 * depuis l'objet jusqu'a cet ancetre.
 */
function ACLS_OBJECT_ANCESTORS($ID):array{
    $ID=intval($ID);
    $graph=ACLS_GROUPS_GRAPH();
    $ancestors=array();
    $seen=array($ID=>true);
    $queue=array(array($ID,array()));

    while(count($queue)>0){
        $current=array_shift($queue);
        $node=$current[0];
        $path=$current[1];
        if(!isset($graph["parents"][$node])){continue;}
        foreach($graph["parents"][$node] as $parentID=>$enabled){
            if($enabled==0){continue;}
            if(isset($seen[$parentID])){continue;}
            $seen[$parentID]=true;
            $name=isset($graph["names"][$parentID])?trim($graph["names"][$parentID]):"";
            if($name==""){$name="Group$parentID";}
            $newpath=$path;
            $newpath[]=$name;
            $ancestors[$parentID]=@implode(" &rsaquo; ",$newpath);
            $queue[]=array($parentID,$newpath);
        }
    }
    return $ancestors;
}

/**
 * Ajoute une regle au jeu, dedoublonnee sur "table:id".
 * $via vaut null pour un usage direct, sinon le chemin de groupes traverse.
 * L'usage direct prime: une regle atteinte des DEUX facons n'est listee qu'une
 * fois, sans mention de groupe -- le lien direct est l'information utile.
 */
function WHICH_RULE_OBJECT_ADD(&$RULES,$key,$html,$via){
    if(!isset($RULES[$key])){
        $RULES[$key]=array("html"=>$html,"via"=>array(),"direct"=>false);
    }
    if($via===null){
        $RULES[$key]["direct"]=true;
        $RULES[$key]["via"]=array();
        return;
    }
    if($RULES[$key]["direct"]){return;}
    if(in_array($via,$RULES[$key]["via"])){return;}
    $RULES[$key]["via"][]=$via;
}

/**
 * Ramasse toutes les regles qui designent le groupe $ID -- regles d'ACL
 * (LoadRulesInMemory), Proxy.PAC et IDS. $ID n'est pas forcement l'objet
 * affiche: c'est aussi l'un des AclsGroup qui le contiennent, et $via porte
 * alors le chemin suivi pour l'atteindre.
 */
function WHICH_RULE_OBJECT_COLLECT($ID,$via,&$RULES){
    $tpl=new template_admin();

    if(isset($GLOBALS["LoadRulesInMemory"][$ID])) {
        foreach ($GLOBALS["LoadRulesInMemory"][$ID] as $array) {
            $RuleID=$array[1];
            $RuleTable=$array[2];
            $Type=null;
            $rule_unknown=null;
            $js=null;
            $Link=$tpl->td_href($array[0],$RuleTable);
            if($RuleTable=="webfilters_sqacls"){
                $Type="{access_rules}";
                $js="Loadjs('fw.proxy.acls.php?rule-id-js=$RuleID')";
                $Link=$tpl->td_href($array[0],$Type,$js);
            }

            if($RuleTable=="squid_url_rewrite_acls"){
                $Type=$tpl->_ENGINE_parse_body("{web_filter_policies}");
                $js="Loadjs('fw.proxy.urlrewrite.php?rule-id-js=$RuleID')";
                $Link=$tpl->td_href($array[0],$Type,$js);
            }

            if($js==null){
                $rule_unknown="($RuleTable)";
            }

            WHICH_RULE_OBJECT_ADD($RULES,"$RuleTable:$RuleID","$Link&nbsp;$rule_unknown",$via);
        }
    }

    if(isset($GLOBALS["WHICH_RULE_OBJECT_WPAD"][$ID])){
        foreach ($GLOBALS["WHICH_RULE_OBJECT_WPAD"][$ID] as $RuleID=>$ruletext){
            WHICH_RULE_OBJECT_ADD($RULES,"wpad:$RuleID",$ruletext,$via);
        }
    }

    if(isset($GLOBALS["WHICH_RULE_OBJECT_SURICATA"][$ID])) {
        foreach ($GLOBALS["WHICH_RULE_OBJECT_SURICATA"][$ID] as $RuleID=>$ruletext){
            WHICH_RULE_OBJECT_ADD($RULES,"suricata:$RuleID",$ruletext,$via);
        }
    }

    if(isset($GLOBALS["WHICH_RULE_OBJECT_DNSFW"][$ID])) {
        foreach ($GLOBALS["WHICH_RULE_OBJECT_DNSFW"][$ID] as $RuleID=>$ruletext){
            WHICH_RULE_OBJECT_ADD($RULES,"dnsfw:$RuleID",$ruletext,$via);
        }
    }

    if(isset($GLOBALS["WHICH_RULE_OBJECT_FIREWALL"][$ID])) {
        foreach ($GLOBALS["WHICH_RULE_OBJECT_FIREWALL"][$ID] as $RuleID=>$ruletext){
            WHICH_RULE_OBJECT_ADD($RULES,"firewall:$RuleID",$ruletext,$via);
        }
    }

    if(isset($GLOBALS["WHICH_RULE_OBJECT_DNSDIST"][$ID])) {
        foreach ($GLOBALS["WHICH_RULE_OBJECT_DNSDIST"][$ID] as $RuleID=>$ruletext){
            WHICH_RULE_OBJECT_ADD($RULES,"dnsdist:$RuleID",$ruletext,$via);
        }
    }
}

function WHICH_RULE_OBJECT($ID):string{
    if(!isset($GLOBALS["WHICH_RULE_OBJECT_WPAD"])){$GLOBALS["WHICH_RULE_OBJECT_WPAD"]=WHICH_RULE_OBJECT_WPAD();}

    if(!isset($GLOBALS["WHICH_RULE_OBJECT_SURICATA"])){
        $GLOBALS["WHICH_RULE_OBJECT_SURICATA"]=WHICH_RULE_OBJECT_SURICATA();
    }

    if(!isset($GLOBALS["WHICH_RULE_OBJECT_DNSFW"])){
        $GLOBALS["WHICH_RULE_OBJECT_DNSFW"]=WHICH_RULE_OBJECT_DNSFW();
    }

    if(!isset($GLOBALS["WHICH_RULE_OBJECT_FIREWALL"])){
        $GLOBALS["WHICH_RULE_OBJECT_FIREWALL"]=WHICH_RULE_OBJECT_FIREWALL();
    }

    if(!isset($GLOBALS["WHICH_RULE_OBJECT_DNSDIST"])){
        $GLOBALS["WHICH_RULE_OBJECT_DNSDIST"]=WHICH_RULE_OBJECT_DNSDIST();
    }

    $RULES=array();
    WHICH_RULE_OBJECT_COLLECT($ID,null,$RULES);

    // Usage INDIRECT: l'objet n'est pas designe par la regle, il est membre
    // d'un groupe d'objets (AclsGroup) qui, lui, l'est. La regle s'applique
    // pourtant bien a l'objet -- la colonne restait vide sans ceci.
    foreach(ACLS_OBJECT_ANCESTORS($ID) as $ancestorID=>$path){
        WHICH_RULE_OBJECT_COLLECT($ancestorID,$path,$RULES);
    }

    if(count($RULES)==0){
        VERBOSE("$ID ! WHICH_RULE_OBJECT = NO RULE",__LINE__);
        return "";
    }

    $i=0;
    $HTML=array();
    foreach($RULES as $rule){
        $i++;
        if($i>9){$i=1;}
        $via=null;
        if(count($rule["via"])>0){
            $via="&nbsp;<small style='opacity:.75'>"
                ."<i class='fa-duotone fa-layer-group'></i>&nbsp;{through_group}:&nbsp;"
                .@implode(",&nbsp;",$rule["via"])."</small>";
        }
        $HTML[]="<i class='fa-solid fa-circle-$i'></i>&nbsp;".$rule["html"].$via;
    }

    VERBOSE("$ID ! ".count($HTML)." rules",__LINE__);

    return @implode("<br>",$HTML);
}
function WHICH_RULE_OBJECT_WPAD():array{
    $array=array();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $rules=acls_api_get("/proxypac/wpad-rules");
    if(!is_array($rules)){ return $array; }
    foreach($rules as $r){
        $rid=intval($r["id"]);
        foreach(["wpad_sources_link","wpad_white_link"] as $table){
            $links=acls_api_get("/proxy/acls/sqlite/links?table=$table&aclid=$rid");
            if(!is_array($links)){ continue; }
            foreach($links as $l){
                $gpid=intval($l["gpid"]);
                $js="Loadjs('fw.proxypac.rules.php?ruleid-js=$rid&function=$function')";
                $array[$gpid][$rid]=$tpl->td_href("Proxy.PAC: {$r["ruleName"]}",null,$js);
            }
        }
    }
    return $array;
}
/**
 * Regles du pare-feu DNS (dnsfw_acls) rattachees a un objet, indexees
 * array(gpid => array(ruleID => lien HTML)) comme les deux fonctions voisines.
 *
 * Le passage par /dnsfw/acls puis /proxy/acls/sqlite/links est delibere:
 * la jointure generique rules-with-gpid REFUSE cette table -- dnsfw_acls
 * n'est pas dans allowedAclTables cote demon (articarest
 * squid/acls_sqlite_queries.go), elle repondrait 400 "invalid aclTable".
 * dnsfw_acls_link, lui, est bien dans allowedLinkTables.
 *
 * Les regles desactivees sont conservees, comme pour les regles d'ACL
 * (LoadGroupsInMemory(true)), Proxy.PAC et IDS: la colonne repond a "ou cet
 * objet est-il reference", pas a "qu'est-ce qui tourne en ce moment".
 */
function WHICH_RULE_OBJECT_DNSFW():array{
    $array=array();
    $tpl=new template_admin();
    $rules=acls_api_get("/dnsfw/acls");
    if(!is_array($rules)){ return $array; }
    foreach($rules as $r){
        if(!is_array($r)){ continue; }
        $rid=intval($r["id"] ?? 0);
        if($rid<1){ continue; }
        $links=acls_api_get("/proxy/acls/sqlite/links?table=dnsfw_acls_link&aclid=$rid");
        if(!is_array($links)){ continue; }
        $ruleName=(string)($r["ruleName"] ?? "");
        $js="Loadjs('fw.dnsfw.rules.php?rule-id-js=$rid')";
        foreach($links as $l){
            if(!is_array($l)){ continue; }
            $gpid=intval($l["gpid"] ?? 0);
            if($gpid<1){ continue; }
            // Infobulle = la page ouverte: {APP_DNS_FIREWALL} et {APP_DNSDIST}
            // s'affichent tous deux "DNS Firewall" (cf. WHICH_RULE_OBJECT_DNSDIST).
            $array[$gpid][$rid]=$tpl->td_href("{APP_DNS_FIREWALL}: $ruleName","fw.dnsfw.rules.php",$js);
        }
    }
    return $array;
}
/**
 * Regles du pare-feu (firewall.db::iptables_main) rattachees a un objet,
 * indexees array(gpid => array(ruleID => lien HTML)) comme ses voisines.
 *
 * La table de liens firewallfilter_sqacllinks porte une colonne "direction"
 * (0 = source, 1 = destination). On ne la filtre PAS: la question posee est
 * "ou cet objet est-il reference", des deux cotes de la regle. Un objet cite
 * a la fois en source et en destination d'une meme regle n'apparait qu'une
 * fois -- l'index est deja clef par ruleID.
 *
 * L'etat "enabled" du groupe n'est pas filtre non plus (contrairement a
 * fw.dns.dnsdist.rules.php::FireWallObjects(), qui construit un ipset et doit
 * donc ignorer les groupes eteints): ici la ligne du tableau porte deja son
 * propre interrupteur, masquer la regle rendrait cet interrupteur incomprehensible.
 */
function WHICH_RULE_OBJECT_FIREWALL():array{
    $array=array();
    $tpl=new template_admin();
    // Liste blanche: $function part dans un litteral JS (OnClick de td_href).
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $rules=acls_api_get("/firewall/iptables-rules");
    if(!is_array($rules)){ return $array; }
    foreach($rules as $r){
        if(!is_array($r)){ continue; }
        $rid=intval($r["id"] ?? 0);
        if($rid<1){ continue; }
        $links=acls_api_get("/proxy/acls/sqlite/links?table=firewallfilter_sqacllinks&aclid=$rid");
        if(!is_array($links)){ continue; }
        // rulename porte parfois un sous-titre HTML "<br><small>...</small>";
        // fw.rules.php::rule_js() le retire de la meme facon avant affichage.
        // Dans une cellule de tableau il couperait la ligne en deux.
        $ruleName=trim((string)($r["ruleName"] ?? ""));
        if(preg_match("#<br><small>(.+?)</small>#",$ruleName,$re)){
            $ruleName=trim(str_replace("<br><small>{$re[1]}</small>","",$ruleName));
        }
        if($ruleName==""){ $ruleName="#$rid"; }
        $js="Loadjs('fw.rules.php?ruleid-js=$rid&function=$function')";
        foreach($links as $l){
            if(!is_array($l)){ continue; }
            $gpid=intval($l["gpid"] ?? 0);
            if($gpid<1){ continue; }
            $array[$gpid][$rid]=$tpl->td_href("{firewall_rule}: $ruleName",null,$js);
        }
    }
    return $array;
}
/**
 * Regles du pare-feu DNS de dnsdist (page fw.dns.dnsdist.rules.php, entree de
 * menu {firewall_rules} du bloc DNS), indexees array(gpid => array(ruleID =>
 * lien HTML)).
 *
 * ATTENTION, il existe DEUX familles distinctes qui s'appellent toutes deux
 * "DNS Firewall" a l'ecran, et ce sont bien deux tables differentes:
 *   - celle-ci: /dns/dnsdist/rules + dnsdist_sqacllinks, page
 *     fw.dns.dnsdist.rules.php -- la seule presente dans le menu de gauche;
 *   - l'autre: /dnsfw/acls + dnsfw_acls_link, page fw.dnsfw.rules.php, qui
 *     n'est reliee a aucun menu mais dont la table reste alimentee depuis
 *     fw.proxy.acls.objects.php (cf. WHICH_RULE_OBJECT_DNSFW()).
 * Les deux sont donc collectees, et l'infobulle de chaque lien nomme la page
 * d'origine pour qu'on sache laquelle on ouvre.
 */
function WHICH_RULE_OBJECT_DNSDIST():array{
    $array=array();
    $tpl=new template_admin();
    // Liste blanche: $function part dans un litteral JS (OnClick de td_href).
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $rules=acls_api_get("/dns/dnsdist/rules");
    if(!is_array($rules)){ return $array; }
    foreach($rules as $r){
        if(!is_array($r)){ continue; }
        $rid=intval($r["id"] ?? 0);
        if($rid<1){ continue; }
        $links=acls_api_get("/proxy/acls/sqlite/links?table=dnsdist_sqacllinks&aclid=$rid");
        if(!is_array($links)){ continue; }
        $ruleName=trim((string)($r["ruleName"] ?? ""));
        if($ruleName==""){ $ruleName="#$rid"; }
        $js="Loadjs('fw.dns.dnsdist.rules.php?rule-id-js=$rid&function=$function')";
        foreach($links as $l){
            if(!is_array($l)){ continue; }
            $gpid=intval($l["gpid"] ?? 0);
            if($gpid<1){ continue; }
            $array[$gpid][$rid]=$tpl->td_href("{APP_DNSDIST}: $ruleName","fw.dns.dnsdist.rules.php",$js);
        }
    }
    return $array;
}
function WHICH_RULE_OBJECT_SURICATA():array{
    $array=array();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $rules=acls_api_get("/suricata/acls");
    if(!is_array($rules)){ return $array; }
    foreach($rules as $r){
        $rid=intval($r["id"]);
        $links=acls_api_get("/proxy/acls/sqlite/links?table=suricata_sqacllinks&aclid=$rid");
        if(!is_array($links)){ continue; }
        foreach($links as $l){
            $gpid=intval($l["gpid"]);
            $js="Loadjs('fw.suricata.acls.php?ruleid-js=$rid&function=$function')";
            $array[$gpid][$rid]=$tpl->td_href("IDS: {$r["aclname"]}",null,$js);
        }
    }
    return $array;
}
function isReader($users){
    if($users->AsDansGuardianAdministrator){
        return true;
    }
    if($users->AsProxyMonitor){
        return true;
    }
    return false;
}
function isWritter($users){
    if($users->AsDansGuardianAdministrator){
        return true;
    }

    return false;
}