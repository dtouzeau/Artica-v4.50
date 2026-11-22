<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

// Cette page n'avait AUCUN controle de privilege: "AsFirewallManager" n'y
// figurait que comme argument d'affichage de icon_check/icon_delete, ce qui
// masque les boutons mais ne protege pas les points d'entree. N'importe quel
// compte authentifie pouvait donc appeler ?pattern-remove=, ?enableall=, ou
// poster ipaddr= directement. fw.bridges.php, la page appelante, ferme bien
// cet acces — on aligne.
$users=new usersMenus();
if(!$users->AsFirewallManager){
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
    exit;
}

if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-table2"])){popup_table2();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ipaddr"])){rule_save();exit;}
// (?enable-rule-js= appelait enable_feature(), un copier-coller d'une page
// nginx: il ecrivait le reglage "FilterUserAgents" d'un SITE REVERSE-PROXY
// depuis la page des sources de masquerade. Aucun appelant, et sa ligne de
// dispatch n'avait meme pas de exit;. Fonction et dispatch retires.)
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}
if(isset($_GET["OnlyActive"])){OnlyActive();exit;}

/**
 * Nom lisible du connecteur (et non d'un site nginx).
 * get_servicename() instanciait socksngix($ID)->GetServiceName(): pour un
 * identifiant de pont, cela renvoyait le nom d'un site reverse-proxy sans
 * rapport, qui finissait tel quel dans le journal d'audit.
 */
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT nic_from,nic_to,rulename FROM pnic_bridges WHERE ID=$ID");
    if(!is_array($ligne)){return "#$ID";}
    if(strlen((string)$ligne["rulename"])>2){return (string)$ligne["rulename"];}
    return $ligne["nic_from"]." > ".$ligne["nic_to"];
}

function service_js():bool{
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    return $tpl->js_dialog4("{source_network}","$page?popup-main=$serviceid");
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $function2=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function2"] ?? "")); // whitelist: echo en JS
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")); // whitelist: echo en JS
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    return $tpl->js_dialog5("{source_network}: $title","$page?popup-rule=$rule&serviceid=$serviceid&function=$function&function2=$function2",550);
}


function rule_remove():bool{
    // $md finit dans un selecteur jQuery injecte tel quel: c'est un md5.
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $ID=intval($_GET["pattern-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $e=fwapi_error(fwapi_send("DELETE","bridges/$serviceid/sources/$ID"));
    if($e!=""){(new template_admin())->js_error($e);return false;}
    echo "$('#$md').remove();\n";
    echo refresh_global();
    return admin_tracks("Remove source #$ID from masquerading rule ".get_servicename($serviceid));
}

function rule_enable():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["pattern-enable"]);
    $serviceid=intval($_GET["serviceid"]);
    $cur=null;
    $list=fwapi_data(fwapi_get("bridges/$serviceid/sources"));
    if(is_array($list)){
        foreach($list as $s){if(intval($s["id"] ?? 0)==$ID){$cur=$s;}}
    }
    if($cur===null){$tpl->js_error("{fw_rule_not_found}");return false;}
    $new=intval($cur["enabled"])==1?0:1;
    $e=fwapi_error(fwapi_send("POST","bridges/$serviceid/sources/$ID/enable",array("enabled"=>$new)));
    if($e!=""){$tpl->js_error($e);return false;}
    echo refresh_global();
    return admin_tracks(($new==1?"enable":"disable")." {$cur["networks"]} for masquerading rule ".get_servicename($serviceid));
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $bt="{add}";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_text("ipaddr","{source_network}","",true);
    $html[]=$tpl->form_outside(null,$form,null,$bt,refresh_global().";dialogInstance5.close();","AsFirewallManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function refresh_global():string{

    $function2=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function2"] ?? "")); // whitelist: echo en JS
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")); // whitelist: echo en JS
    $f=array();
    if(strlen($function)>2) {
        $f[] = "$function();";
    }
    if(strlen($function2)>2) {
        $f[] = "$function2();";
    }

    return @implode(";",$f);
}




function rule_save():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $ipaddr=trim((string)$_POST["ipaddr"]);
    if($ipaddr===""){return false;}
    $e=fwapi_error(fwapi_send("POST","bridges/$serviceid/sources",array("networks"=>$ipaddr)));
    if($e!=""){echo $tpl->post_error($e);return false;}
    return admin_tracks("Add a new masquerade source for rule ".get_servicename($serviceid));
}

function popup_main():bool{
    $serviceid  = intval($_GET["popup-main"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")); // whitelist: echo en JS
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid&function2=$function')</script>";
    return true;
}



function popup_table():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-table"]);
    $function2=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function2"] ?? "")); // whitelist: echo en JS
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    echo "<div id='masquerade-sources-$serviceid' style='margin-bottom:10px;margin-top:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&popup-table2=$serviceid&function2=$function2");

    return true;
}
function OnlyActive():bool{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")); // whitelist: echo en JS
    $Key=basename(__FILE__)."OnlyActive";
    if(!isset($_SESSION[$Key])){
        $_SESSION[$Key]=true;
    }else{
        unset($_SESSION[$Key]);
    }
    header("content-type: application/x-javascript");
    echo "$function();";
    return true;
}

function top_buttons():bool{
    $serviceid  = intval($_GET["top-buttons"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")); // whitelist: echo en JS
    $function2=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function2"] ?? "")); // whitelist: echo en JS
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    if($serviceid==0){
        echo $tpl->div_error("ServiceID === 0");
        return true;
    }
    $topbuttons[] = array("Loadjs('$page?rule-js=0&serviceid=$serviceid&function=$function&function2=$function2')", ico_plus, "{new_rule}");
    $topbuttons[] = array("Loadjs('$page?OnlyActive=yes&serviceid=$serviceid&function=$function&function2=$function2')", ico_filter, "{OnlyActive}");
    $topbuttons[] = array("Loadjs('$page?disableall=yes&serviceid=$serviceid&function=$function&function2=$function2')", ico_disabled, "{disable_all}");
    $topbuttons[] = array("Loadjs('$page?enableall=yes&serviceid=$serviceid&function=$function&function2=$function2')", ico_check, "{enable_all}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function rule_disable_all():bool{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")); // whitelist: echo en JS
    $serviceid=intval($_GET["serviceid"]);
    $e=fwapi_error(fwapi_send("POST","bridges/$serviceid/sources/enable",array("enabled"=>0)));
    if($e!=""){(new template_admin())->js_error($e);return false;}
    echo refresh_global();
    echo "$function();";
    return admin_tracks("Disable all network sources from masqerading rule ".get_servicename($serviceid));
}

function rule_enable_all():bool{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")); // whitelist: echo en JS
    $serviceid=intval($_GET["serviceid"]);
    $e=fwapi_error(fwapi_send("POST","bridges/$serviceid/sources/enable",array("enabled"=>1)));
    if($e!=""){(new template_admin())->js_error($e);return false;}
    echo refresh_global();
    echo "$function();";
    return admin_tracks("Enable all network sources from masqerading rule ".get_servicename($serviceid));
}

function masqsrc_row($tpl,$md,$pattern,$enabled,$enableIco,$deleteIco):string{
    $icocls=$enabled?"awc-ico":"awc-ico awc-ico-off";
    $namecls=$enabled?"awc-name":"awc-name awc-name-off";
    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='$icocls'><i class='fas fa-network-wired'></i></span>";
    $h.="<span class='$namecls' style='font-family:monospace'>".htmlspecialchars($pattern)."</span>";
    $h.="</div></td>";
    $h.="<td class='center'>$enableIco</td>";
    $h.="<td><div class='awc-acts'>$deleteIco</div></td>";
    $h.="</tr>";
    return $h;
}

function popup_table2():bool{
    $serviceid  = intval($_GET["popup-table2"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $function =preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $function2=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function2"] ?? ""));
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

    $OnlyActive=false;
    $KeyActive=basename(__FILE__)."OnlyActive";
    if(isset($_SESSION[$KeyActive])){
        $OnlyActive=true;
    }

    // Le terme de recherche etait concatene tel quel dans le LIKE (et encadre
    // deux fois de %: '%%terme%%').
    $QS="";
    $search=trim((string)($_GET["search"] ?? ""));
    if($search!==""){
        $like=$q->sqlite_escape_string2(str_replace("*","%",$search));
        $QS=" AND (networks LIKE '%$like%')";
    }

    $results=$q->QUERY_SQL("SELECT * FROM pnic_bridges_src WHERE pnicid=$serviceid$QS");
    if(!is_array($results)){$results=array();}

    $rows=array();
    $total=0;
    $c=0;
    foreach ($results as $index=>$ligne){
        $enabled=intval($ligne["enabled"]);
        if($OnlyActive && $enabled==0){continue;}
        $total++;
        $c++;
        // Plafond d'affichage: on continue a compter pour que le pied indique
        // le nombre reel de reseaux sources.
        if($c>250){continue;}

        $pattern=(string)$ligne["networks"];
        $ID=intval($ligne["ID"]);
        if(strlen($pattern)>128){$pattern=substr($pattern,0,125)."...";}
        // L'identifiant de ligne derive de l'ID et non du contenu: deux reseaux
        // partageant leurs 125 premiers caracteres produisaient le meme md5,
        // donc le meme id DOM — la suppression retirait la mauvaise ligne.
        $md=md5("masqsrc-$ID");

        $rows[]=masqsrc_row($tpl,$md,$pattern,$enabled,
            $tpl->icon_check($enabled,"Loadjs('$page?pattern-enable=$ID&serviceid=$serviceid&function2=$function2')","","AsFirewallManager"),
            $tpl->icon_delete("Loadjs('$page?pattern-remove=$ID&md=$md&serviceid=$serviceid&function2=$function2')","AsFirewallManager"));
    }

    $html=array();
    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{ipsrc}</th>";
    $html[]="<th class='center'>{enable}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='msq-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} ".count($rows)." / $total {source_network}</span>";
    $html[]="<span class='awc-pg' id='msq-pager'></span>";
    $html[]="</div></div></div>";

    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="LoadAjax('masquerade-sources-$serviceid','$page?top-buttons=$serviceid&function=$function&function2=$function2');";
    $html[]="</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js(50,"msq-rows","msq-pager");
    return true;
}
