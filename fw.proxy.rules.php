<?php
$GLOBALS["OBJTYPES"][0]="{src}";
$GLOBALS["OBJTYPES"][1]="{mac_address}";
$GLOBALS["OBJTYPES"][2]="{srcdomain}";
$GLOBALS["OBJTYPES"][3]="{member}";
$GLOBALS["OBJTYPES"][4]="{dstdomain}";
$GLOBALS["OBJTYPES"][5]="{categories}";
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["rule-fill"])){rule_fill();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["tiny-js"])){tiny_page();exit;}
if(isset($_GET["start"])){start();exit;}

if(isset($_GET["notify-message"])){notify_message();exit;}
if(isset($_POST["notify-message"])){notify_message_save();exit;}

if(isset($_GET["rules-search"])){search();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_GET["rule-settings"])){rule_settings_js();exit;}
if(isset($_POST["rule-settings"])){rule_settings_save();exit;}
if(isset($_GET["rule-settings-tab"])){rule_settings_tab();exit;}
if(isset($_GET["rule-settings-popup"])){rule_settings_popup();exit;}
if(isset($_GET["rule-settings-move"])){rule_settings_move();exit;}
if(isset($_GET["rule-settings-enable"])){rule_settings_enable();exit;}
if(isset($_GET["rule-settings-delete"])){rule_settings_delete();exit;}
if(isset($_POST["rule-settings-delete"])){rule_settings_delete_confirm();exit;}
if(isset($_GET["reconfigure-js"])){reconfigure_js();exit;}

if(isset($_GET["rule-object"])){rule_object_search();exit;}
if(isset($_GET["rule-object-search"])){rule_object_table();exit;}
if(isset($_GET["rule-object-enable"])){rule_object_enable();exit;}
if(isset($_GET["rule-object-move"])){rule_object_move();exit;}
if(isset($_GET["rule-object-delete"])){rule_object_delete();exit;}



if(isset($_GET["object-button"])){object_button();exit;}

if(isset($_GET["add-categories-js"])){categories_js();exit;}
if(isset($_GET["add-categories-popup"])){categories_popup();exit;}
if(isset($_GET["category-post-js"])){category_post_js();exit;}

if(isset($_GET["add-domain-js"])){domain_js();exit;}
if(isset($_GET["add-domain-popup"])){domain_popup();exit;}
if(isset($_POST["add-domain"])){domain_save();exit;}

if(isset($_GET["add-src-js"])){src_js();exit;}
if(isset($_GET["add-src-popup"])){src_popup();exit;}
if(isset($_POST["add-src"])){src_save();exit;}

if(isset($_GET["add-mac-js"])){mac_js();exit;}
if(isset($_GET["add-mac-popup"])){mac_popup();exit;}
if(isset($_POST["add-mac"])){mac_save();exit;}

if(isset($_GET["add-srcdomain-js"])){srcdomain_js();exit;}
if(isset($_GET["add-srcdomain-popup"])){srcdomain_popup();exit;}
if(isset($_POST["add-srcdomain"])){srcdomain_save();exit;}


page();

function reconfigure_js():bool{
    $page       = CurrentPageName();
    $function   = $_GET["function"];
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    header("content-type: application/x-javascript");
    echo "$function()\n";
    return true;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{access_rules}","fad fa-shield-alt","{PROXY_ACLS_EXPLAIN}<div id='proxy-acls-simple'></div>","$page?start=yes","proxy-rules","progress-standard-rules-restart",false,"table-standard-rules");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{access_rules}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}

function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&rules-search=yes");
    echo "</div>";
    return true;
}
function rule_fill():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-fill"]);
    $ligne=acls_api_get("/proxy/simple-acls/$ID");
    if(!is_array($ligne)){
        header("content-type: application/x-javascript");
        echo "console.error('rule_fill: could not load rule $ID');";
        return false;
    }
    $aclname=base64_encode($tpl->td_href($ligne["aclName"],"","Loadjs('$page?rule-settings=$ID')"));

    $zexplain=base64_encode($ligne["zExplain"] ?? "");
    $array_accesses=array_accesses();
    $ruleaction=intval($ligne["ruleAction"] ?? 0);

    $fl=ico_arrow_right;
    $Cin=CountOfIN($ID);
    $Cout=CountOfOut($ID);
    if($Cin==0 && $Cout==0){
        $ruleaction=999;
    }

    $recs=base64_encode($tpl->_ENGINE_parse_body("$Cin&nbsp;{records}&nbsp;<i class='$fl'></i>&nbsp;$Cout&nbsp;{records}"));


    $access=base64_encode($tpl->_ENGINE_parse_body($array_accesses[$ruleaction]));

    $PortDirectionS=method_list();
    $aclport=intval($ligne["aclPort"] ?? 0);
    if(!isset($PortDirectionS[$aclport])){
        $PortDirectionS[$aclport]="{all}";
    }
    $port=base64_encode($tpl->_ENGINE_parse_body($PortDirectionS[$aclport]));
    $did="document.getElementById";
    $bs64="innerHTML=base64_decode";
    $f[]="if($did('acls-$ID-aclname')){ $did('acls-$ID-aclname').$bs64('$aclname');}";
    $f[]="if($did('acls-$ID-zexplain')){ $did('acls-$ID-zexplain').$bs64('$zexplain');}";
    $f[]="if($did('acls-$ID-access')){ $did('acls-$ID-access').$bs64('$access');}";
    $f[]="if($did('acls-$ID-port')){ $did('acls-$ID-port').$bs64('$port');}";
    $f[]="if($did('acls-$ID-recs')){ $did('acls-$ID-recs').$bs64('$recs');}";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
    return true;
}
function search():bool{
    $users=new usersMenus();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $function=$_GET["function"];
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));

    if($Go_Shield_Server_Enable==0){

        $jsinstall=$tpl->framework_buildjs("/goshield/install",
            "go.shield.server.progress",
            "go.shield.server.log",
            "progress-standard-rules-restart","window.location.href='/proxy-rules'");

        $btn=$tpl->button_autnonome("{install} {APP_GO_SHIELD_SERVER}", $jsinstall, ico_cd, "AsSystemAdministrator",350);
        echo $tpl->div_error("{ERROR_GO_SHIELD_SERVER_DISABLED}<div style='margin:15px;text-align:right'>$btn</div>");
        return false;
    }


    $limit=50;
    $data1="class='text-capitalize' style='width:1%;text-align:center'";

    $search=trim($_GET["search"] ?? "");
    if(strlen($search)>1) {
        if(preg_match("#(rows|max|limit)=([0-9]+)#i",$search,$re)){
            $limit=intval($re[2]);
            $search=str_replace("$re[1]=$re[2]","",$search);
        }
    }
    if($limit==0){$limit=30;}

    $results=acls_api_get("/proxy/simple-acls");
    if(!is_array($results)){
        echo $tpl->div_error("Could not load simple ACL rules");
        return true;
    }

    // Client-side filter by name if search term provided
    if(strlen(trim($search))>0){
        $needle=strtolower(trim($search));
        $results=array_values(array_filter($results, function($r) use($needle){
            return stripos($r["aclName"] ?? "", $needle)!==false;
        }));
    }
    // Limit results
    if(count($results)>$limit){ $results=array_slice($results,0,$limit); }

    writelogs("simple-acls: ".count($results)." rows",__FUNCTION__,__FILE__,__LINE__);

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th class='center'>{order}</th>";
    $html[]="<th>{rulename}</th>";
    $html[]="<th>{method}</th>";
    $html[]="<th class='center'>IN/OUT</th>";
    $html[]="<th class='center'>{status}</th>";
    $html[]="<th class='center'>{enabled}</th>";
    $html[]="<th class='center'>{actions}</th>";
    $html[]="</tr></thead><tbody id='pxrl-rows'>";


    $PortDirectionS=method_list();
    $array_accesses=array_accesses();


    foreach ($results as $index=>$ligne){

        $ID=intval($ligne["id"] ?? 0);
        $aclname=$ligne["aclName"] ?? "";
        $enabled=intval($ligne["enabled"] ?? 0);
        $xORDER=intval($ligne["xOrder"] ?? 0);
        $aclport=intval($ligne["aclPort"] ?? 0);
        $zExplain=$ligne["zExplain"] ?? "";
        $ruleaction=intval($ligne["ruleAction"] ?? 0);
        $aclname=$tpl->td_href($aclname,"","Loadjs('$page?rule-settings=$ID')");
        $md=md5(serialize($ligne));
        if(!isset($PortDirectionS[$aclport])){
            $PortDirectionS[$aclport]="{all}";
        }
        if(!$users->AsDansGuardianAdministrator){
            writelogs("AsDansGuardianAdministrator == False",__FUNCTION__,__FILE__,__LINE__);
            if(count($users->SIMPLE_ACLS)>0){
                if(!isset($users->SIMPLE_ACLS[$ID])){
                    VERBOSE("($index) $aclname in_array=False");
                    continue;
                }
            }
        }
        $priv="AsDansGuardianAdministrator";
        $enable=$tpl->icon_check($enabled,"Loadjs('$page?rule-settings-enable=$ID')","",$priv);
        $up=$tpl->icon_up("Loadjs('$page?rule-settings-move=$ID&dir=1');");
        $down=$tpl->icon_down("Loadjs('$page?rule-settings-move=$ID&dir=0');");
        $delete=$tpl->icon_delete("Loadjs('$page?rule-settings-delete=$ID&md=$md');",$priv);

        if(!$users->AsDansGuardianAdministrator){
            $up=$tpl->icon_up("");
            $down=$tpl->icon_down("");
        }

        $fl=ico_arrow_right;
        $Cin=CountOfIN($ID);
        $Cout=CountOfOut($ID);
        if($Cin==0 && $Cout==0){
            $ruleaction=999;
        }

        // $array_accesses ne definit que 0, 1, 2 et 999: toute autre valeur
        // produisait un avertissement de cle indefinie.
        $access=isset($array_accesses[$ruleaction])
            ?$array_accesses[$ruleaction]
            :"<span class='awc-badge awc-badge-off'>{unknown}</span>";

        $h ="<tr id='$md'>";
        $h.="<td class='center awc-mono'>$xORDER</td>";
        $h.="<td><div class='awc-ident'>";
        $h.="<span class='".($enabled==1?"awc-ico":"awc-ico awc-ico-off")."'><i class='fas fa-shield-halved'></i></span>";
        $h.="<span><span class='awc-name' id='acls-$ID-aclname'>$aclname</span>";
        $h.="<div class='awc-meta' id='acls-$ID-zexplain'>$zExplain</div>";
        $h.="</span></div></td>";
        $h.="<td><span id='acls-$ID-port'>{$PortDirectionS[$aclport]}</span></td>";
        $h.="<td class='center awc-nowrap'><span id='acls-$ID-recs'>"
            ."$Cin&nbsp;{records}&nbsp;<i class='$fl'></i>&nbsp;$Cout&nbsp;{records}</span></td>";
        $h.="<td class='center'><span id='acls-$ID-access'>$access</span></td>";
        $h.="<td class='center'>$enable</td>";
        // La cellule de suppression portait un "</center>" SANS ouvrant.
        $h.="<td class='center'><div class='awc-acts'>$up$down$delete</div></td>";
        $h.="</tr>";
        $html[]=$h;
    }


    $toTiny="Loadjs('$page?tiny-js=yes&function=$function')";

    // Le pied annoncait HUIT colonnes pour neuf.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} ".count($results)."</span>";
    $html[]="<span class='awc-pg' id='pxrl-pager'></span>";
    $html[]="</div></div></div>";

    // Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    $script="<script>$toTiny\nNoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n"
        ."LoadAjax('proxy-acls-bugs','$page?proxy-acls-bugs=yes');\n</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(implode("\n",$html))
        .$script
        .awc_pager_js(50,"pxrl-rows","pxrl-pager");
    return true;

}
function CountOfIN($ID):int{
    $rows=acls_api_get("/proxy/simple-acls/$ID/objects?direction=0");
    return is_array($rows) ? count($rows) : 0;
}
function CountOfOut($ID):int{
    $rows=acls_api_get("/proxy/simple-acls/$ID/objects?direction=1");
    return is_array($rows) ? count($rows) : 0;
}

function array_accesses():array{

    $array_access[0]="<span class='label label-danger'>{deny_access}</span>";
    $array_access[1]="<span class='label label-primary'>{allow_access}</span>";
    $array_access[2]="<span class='label label-info'>{acl_object}</span>";
    $array_access[999]="<span class='label label-default'>{inactive2}</span>";
    return $array_access;
}

function tiny_page():string{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $function       = $_GET["function"];

    $add="Loadjs('$page?newrule-js=yes&function=$function');";
    $reconfigure="Loadjs('$page?reconfigure-js=yes&function=$function');";
    $users=new usersMenus();
    $topbuttons=array();
    if($users->AsDansGuardianAdministrator) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
    }
    $topbuttons[] = array($reconfigure, ico_retweet, "{reconfigure}");

    $js="s_PopUp('https://wiki.articatech.com/proxy-service/simple-acls','1024','800')";
    $topbuttons[] = array($js, ico_support, "Wiki");

    $TINY_ARRAY["TITLE"]="{access_rules}";
    $TINY_ARRAY["ICO"]="fad fa-shield-alt";
    $TINY_ARRAY["EXPL"]="{PROXY_ACLS_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->th_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    header("content-type: application/x-javascript");
    echo $jstiny;
    return "";
}
function rule_settings_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["rule-settings"]);
    $ligne      = acls_api_get("/proxy/simple-acls/$ID");
    $function   = $_GET["function"];
    $title      = is_array($ligne) ? ($ligne["aclName"] ?? "") : "";
    return $tpl->js_dialog($title,"$page?rule-settings-tab=$ID&function=$function");
}
function rule_settings_tab():bool{
    $users=new usersMenus();
    $page       = CurrentPageName();
    $ID         = intval($_GET["rule-settings-tab"]);
    $ligne      = acls_api_get("/proxy/simple-acls/$ID");
    $title      = is_array($ligne) ? ($ligne["aclName"] ?? "") : "";
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleaction=intval(is_array($ligne) ? ($ligne["ruleAction"] ?? 0) : 0);
    if($users->AsDansGuardianAdministrator) {
        $array[$title] = "$page?rule-settings-popup=$ID&function=$function";
    }
    $array["{incoming_criteria}"] = "$page?rule-object=$ID&function=$function&dir=0";
    $array["{outgoing_criteria}"] = "$page?rule-object=$ID&function=$function&dir=1";
    if($users->AsDansGuardianAdministrator) {
        if($ruleaction==0){
            $array["{NotifyMessage}"] = "$page?notify-message=$ID";
        }
    }


    echo $tpl->tabs_default($array);
    return true;
}
function rule_settings_move():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["rule-settings-move"]);
    $dir=intval($_GET["dir"]);
    [$ok,$err]=acls_api_send("PUT","/proxy/simple-acls/$ID/move",["dir"=>$dir]);
    if(!$ok){echo "alert('".$tpl->javascript_parse_text($err)."');";return false;}
    return true;
}
function rule_object_table():bool{
    $page       = CurrentPageName();
    $tpl=new template_admin();
    $aclid         = intval($_GET["rule-object-search"]);
    $dir        = intval($_GET["dir"]);
    $function   = $_GET["function"];
    $search=trim($_GET["search"] ?? "");
    $limit=30;
    if(strlen($search)>1) {
        if(preg_match("#(rows|max|limit)=([0-9]+)#i",$search,$re)){
            $limit=intval($re[2]);
            $search=str_replace("$re[1]=$re[2]","",$search);
        }
    }
    if($limit==0){$limit=30;}

    $results=acls_api_get("/proxy/simple-acls/$aclid/objects?direction=$dir");
    if(!is_array($results)){
        echo $tpl->div_error("Could not load objects for rule $aclid");
        return true;
    }

    // Client-side filter by pattern if search term provided
    if(strlen(trim($search))>0){
        $needle=strtolower(trim($search));
        $results=array_values(array_filter($results, function($r) use($needle){
            return stripos($r["pattern"] ?? "", $needle)!==false;
        }));
    }
    if(count($results)>$limit){ $results=array_slice($results,0,$limit); }

    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th class='center'>{order}</th>";
    $html[]="<th>{pattern}</th>";
    $html[]="<th>{type}</th>";
    $html[]="<th class='center'>{enabled}</th>";
    $html[]="<th class='center'>{actions}</th>";
    $html[]="</tr></thead><tbody id='pxro-$aclid-$dir-rows'>";

    $users=new usersMenus();
    $priv="AsDansGuardianAdministrator";
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$aclid])){
            $priv="";
        }
    }

    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["id"] ?? 0);
        $enabled=intval($ligne["enabled"] ?? 0);
        $xORDER=intval($ligne["xOrder"] ?? 0);
        $pattern=$ligne["pattern"] ?? "";
        $description=$ligne["description"] ?? "";
        $objecttype=intval($ligne["objectType"] ?? 0);
        if($objecttype==5){
            $catz=new mysql_catz();
            $pattern=$catz->CategoryIntToStr(intval($pattern));

        }
        $md=md5("acl-object-$aclid-$dir-$index");
        if($pattern=="*"){
            $pattern="{all}";
        }
        $pattern="<strong>$pattern</strong>";




        $enable=$tpl->icon_check($enabled,"Loadjs('$page?rule-object-enable=$ID&aclid=$aclid')","",$priv);
        $up=$tpl->icon_up("Loadjs('$page?rule-object-move=$ID&dir=1&aclid=$aclid');");
        $down=$tpl->icon_down("Loadjs('$page?rule-object-move=$ID&dir=0&aclid=$aclid');");
        $delete=$tpl->icon_delete("Loadjs('$page?rule-object-delete=$ID&aclid=$aclid&md=$md');",$priv);

        // OBJTYPES peut ne pas couvrir un type ajoute cote daemon.
        $typeLabel=isset($GLOBALS["OBJTYPES"][$objecttype])
            ?$GLOBALS["OBJTYPES"][$objecttype]
            :"<span class='awc-none'>&mdash;</span>";

        $h ="<tr id='$md'>";
        $h.="<td class='center awc-mono'>$xORDER</td>";
        $h.="<td><div class='awc-ident'>";
        $h.="<span class='".($enabled==1?"awc-ico":"awc-ico awc-ico-off")."'><i class='fas fa-filter'></i></span>";
        $h.="<span><span class='awc-name'>$pattern</span>";
        if(trim((string)$description)!==""){$h.="<div class='awc-meta'>$description</div>";}
        $h.="</span></div></td>";
        $h.="<td><span class='awc-tag'>$typeLabel</span></td>";
        $h.="<td class='center'>$enable</td>";
        // Les trois dernieres cellules portaient chacune un "</center>" SANS
        // ouvrant, soit trois balises orphelines par ligne.
        $h.="<td class='center'><div class='awc-acts'>$up$down$delete</div></td>";
        $h.="</tr>";
        $html[]=$h;
    }


    $buttons="LoadAjaxTiny('proxy-acls-simple-object-$aclid-$dir','$page?object-button=$aclid&function=$function&dir=$dir')";

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} ".count($results)."</span>";
    $html[]="<span class='awc-pg' id='pxro-$aclid-$dir-pager'></span>";
    $html[]="</div></div>";

    $script="<script>$buttons\nNoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(implode("\n",$html))
        .$script
        .awc_pager_js(50,"pxro-$aclid-$dir-rows","pxro-$aclid-$dir-pager");

    return true;
}
function rule_object_delete():bool{
    $tpl=new template_admin();
    $ID         = intval($_GET["rule-object-delete"]);
    $md         = $_GET["md"];
    [$ok,$err]=acls_api_send("DELETE","/proxy/simple-acls/objects/$ID");
    if(!$ok){echo "alert('".$tpl->javascript_parse_text($err)."');";return false;}
    echo "$('#$md').remove();\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return true;
}
function rule_object_move():bool{
    $tpl=new template_admin();
    $ID  = intval($_GET["rule-object-move"] ?? 0);
    $dir = intval($_GET["dir"] ?? 0);
    if($ID <= 0){ return false; }
    [$ok,$err]=acls_api_send("PUT","/proxy/simple-acls/objects/$ID/move",["dir"=>$dir]);
    if(!$ok){echo "alert('".$tpl->javascript_parse_text($err)."');";return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return true;
}
function rule_settings_enable():bool{
    $tpl=new template_admin();
    $ID         = intval($_GET["rule-settings-enable"]);
    $ligne      = acls_api_get("/proxy/simple-acls/$ID");
    if(!is_array($ligne)){echo $tpl->js_error("Could not load rule $ID");return false;}
    $enabled    = intval($ligne["enabled"] ?? 0);
    $enabled    = ($enabled==1) ? 0 : 1;
    [$ok,$err]=acls_api_send("PUT","/proxy/simple-acls/$ID",["enabled"=>$enabled]);
    if(!$ok){echo $tpl->js_error($err);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Enable/Disable=$enabled for Proxy simple ACL rule $ID");
}
function rule_settings_delete():bool{
    $tpl=new template_admin();
    $md=$_GET["md"];
    $ID         = intval($_GET["rule-settings-delete"]);
    $ligne      = acls_api_get("/proxy/simple-acls/$ID");
    $aclname    = is_array($ligne) ? ($ligne["aclName"] ?? "rule $ID") : "rule $ID";
    return $tpl->js_confirm_delete("$aclname","rule-settings-delete",$ID,"$('#$md').remove();");;
}
function rule_settings_delete_confirm():bool{
    $tpl=new template_admin();
    $ID         = intval($_POST["rule-settings-delete"]);
    $ligne      = acls_api_get("/proxy/simple-acls/$ID");
    $aclname    = is_array($ligne) ? ($ligne["aclName"] ?? "rule $ID") : "rule $ID";
    [$ok,$err]=acls_api_send("DELETE","/proxy/simple-acls/$ID");
    if(!$ok){echo $tpl->js_error($err);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Delete Proxy simple ACL rule $aclname");
}
function rule_object_enable():bool{
    $tpl=new template_admin();
    $ID         = intval($_GET["rule-object-enable"]);
    $ligne      = acls_api_get("/proxy/simple-acls/objects/$ID");
    if(!is_array($ligne)){echo $tpl->js_error("Could not load object $ID");return false;}
    $enabled    = intval($ligne["enabled"] ?? 0);
    $enabled    = ($enabled==1) ? 0 : 1;
    [$ok,$err]=acls_api_send("PUT","/proxy/simple-acls/objects/$ID",["enabled"=>$enabled]);
    if(!$ok){echo $tpl->js_error($err);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Enable/Disable=$enabled for Proxy simple ACL object $ID");
}
function rule_object_search():bool{
    $page       = CurrentPageName();
    $tpl=new template_admin();
    $ID         = intval($_GET["rule-object"]);
    $dir        = intval($_GET["dir"]);
    $function   = $_GET["function"];
    $html[]="<div id='proxy-acls-simple-object-$ID-$dir' style='margin-top:10px;margin-bottom:10px'></div>";
    $html[]=$tpl->search_block($page,"","","","&rule-object-search=$ID&dir=$dir&function2=$function");
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}
function object_button(){
    $page       = CurrentPageName();
    $ID         = intval($_GET["object-button"]);
    $dir        = intval($_GET["dir"]);
    $function   = $_GET["function"];
    $tpl=new template_admin();
    $topbuttons=array();
    if($dir==1){
        $topbuttons[] = array("Loadjs('$page?add-categories-js=$ID&function=$function')", ico_books, "{add_category}");
        $topbuttons[] = array("Loadjs('$page?add-domain-js=$ID&function=$function')", ico_earth, "{add_domain}");

    }else{
        $topbuttons[] = array("Loadjs('$page?add-src-js=$ID&function=$function')", ico_networks, "{src}");
        $topbuttons[] = array("Loadjs('$page?add-mac-js=$ID&function=$function')", ico_nic, "{ComputerMacAddress}");
        $topbuttons[] = array("Loadjs('$page?add-srcdomain-js=$ID&function=$function')", ico_computer, "{srcdomain}");
    }
    echo $tpl->th_buttons($topbuttons);
}

function rule_settings_popup():bool{
    $tpl=new template_admin();
    $page       = CurrentPageName();
    $ID         = intval($_GET["rule-settings-popup"]);
    $ligne      = acls_api_get("/proxy/simple-acls/$ID");
    if(!is_array($ligne)){ $ligne=[]; }
    $function=$_GET["function"];

    $jsafter[]="BootstrapDialog1.close();";
    $jsafter[]="Loadjs('$page?rule-fill=$ID&function=$function');";

    if($function<>null){
        $jsafter[]="$function()";
    }
    $array_access[1]="{allow_access}";
    $array_access[0]="{deny_access}";
    $array_access[2]="{acl_object}";

    $form[]=$tpl->field_hidden("rule-settings", $ID);
    $form[]=$tpl->field_text("aclname", "{rule_name}", $ligne["aclName"] ?? "",true);
    $form[]=$tpl->field_array_hash($array_access, "access", "{type}", intval($ligne["ruleAction"] ?? 0));
    $form[]=$tpl->field_text("description", "{description}", $ligne["zExplain"] ?? "");
    $form[]=$tpl->field_array_hash(method_list(), "PortDirection", "{method}", intval($ligne["aclPort"] ?? 0));

    $html=$tpl->form_outside("", @implode("\n", $form),"","{apply}",
        @implode(";",$jsafter),"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_settings_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST_XSS();
    $ID         = intval($_POST["rule-settings"]);
    $aclname    = trim($_POST["aclname"] ?? "");
    $description= trim($_POST["description"] ?? "");
    $access     = intval($_POST["access"]);
    $PortDirection = intval($_POST["PortDirection"]);

    [$ok,$err]=acls_api_send("PUT","/proxy/simple-acls/$ID",[
        "aclName"        => $aclname,
        "zExplain"       => $description,
        "ruleAction"     => $access,
        "portDirection"  => $PortDirection,
    ]);
    if(!$ok){echo $tpl->post_error($err);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Update Proxy simple ACL rule $aclname");
}
function method_list():array{
    $localport=array();
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $HaClusterProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyPort"));
    $HaClusterNoAuthPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterNoAuthPort"));
    if($HaClusterNoAuthPort==0){
        $HaClusterNoAuthPort=8091;
    }

    if($HaClusterClient==1){
        $localport[$HaClusterNoAuthPort]="HaCluster NoAuth";
        $localport[$HaClusterProxyPort]="HaCluster LB";
    }

    $resultsPorts=acls_api_get("/proxy/ports/list");
    if(is_array($resultsPorts)){
        foreach ($resultsPorts as $lignePorts) {
            $port = intval($lignePorts["port"] ?? 0);
            if($port==0){continue;}
            if(!intval($lignePorts["enabled"] ?? 0)){continue;}
            $PortName=$lignePorts["portName"] ?? "";
            $PortName="{listen_port} $port $PortName";
            $localport[$port]=trim($PortName);
        }
    }
    return $localport;
}

function new_rule_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $function   = $_GET["function"];
    $title="{new_rule}";
    return $tpl->js_dialog($title,"$page?newrule-popup=yes&function=$function");
}

function new_rule_popup():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $jsafter    = array();
    $array_access[1]="{allow_access}";
    $array_access[0]="{deny_access}";
    $explain=$tpl->_ENGINE_parse_body("{new_acls_rule_explain}");
    $function=$_GET["function"];
    $jsafter[]="BootstrapDialog1.close();";

    if($function<>null){
        $jsafter[]="$function()";
    }
    $form[]=$tpl->field_hidden("newrule", "yes");
    $form[]=$tpl->field_text("aclname", "{rule_name}", null,true);
    $form[]=$tpl->field_array_hash($array_access, "access", "{type}", 0);
    $form[]=$tpl->field_text("description", "{description}", null);
    $form[]=$tpl->field_array_hash(method_list(), "PortDirection", "{method}","");
    $html=$tpl->form_outside("", @implode("\n", $form),$explain,"{add}",
        @implode(";",$jsafter),"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
return true;
}
function new_rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST_XSS();
    $aclport    = intval($_POST["PortDirection"]);
    $aclname    = trim($_POST["aclname"] ?? "");
    $description= trim($_POST["description"] ?? "");
    $access     = intval($_POST["access"]);

    [$ok,$data,$err]=array_pad((array)acls_api_send("POST","/proxy/simple-acls",[
        "aclName"      => $aclname,
        "portDirection"=> $aclport,
        "ruleAction"   => $access,
        "aclGroup"     => 0,
        "zExplain"     => $description,
    ]),3,null);
    if(!$ok){echo $tpl->post_error(is_string($data) ? $data : ($err ?? "unknown error"));return false;}

    $LASTID = is_array($data) ? intval($data["id"] ?? 0) : 0;
    writelogs("Create a new Proxy simple ACL $aclname #$LASTID",__FUNCTION__,__FILE__,__LINE__);

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Create a new Proxy simple ACL $aclname");
}
function categories_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["add-categories-js"]);
    $function   = $_GET["function"];
    $tpl->js_dialog8("{add_category}","$page?add-categories-popup=$ruleid&function=$function");
}
function domain_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["add-domain-js"]);
    $function   = $_GET["function"];
    $tpl->js_dialog8("{add_domain}","$page?add-domain-popup=$ruleid&function=$function");
}
function src_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["add-src-js"]);
    $function   = $_GET["function"];
    return $tpl->js_dialog8("{src}","$page?add-src-popup=$ruleid&function=$function");
}
function mac_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["add-mac-js"]);
    $function   = $_GET["function"];
    return $tpl->js_dialog8("{ComputerMacAddress}","$page?add-mac-popup=$ruleid&function=$function");
}
function srcdomain_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function   = $_GET["function"];
    $ruleid=intval($_GET["add-srcdomain-js"]);
    return $tpl->js_dialog8("{srcdomain}","$page?add-srcdomain-popup=$ruleid&function=$function");
}
function isaregex($pattern):bool{
    $pattern=" $pattern";
    if(strpos($pattern, "*")>0){return true;}
    if(strpos($pattern, "[")>0){return true;}
    if(strpos($pattern, "]")>0){return true;}
    if(strpos($pattern, "(")>0){return true;}
    if(strpos($pattern, ")")>0){return true;}
    if(strpos($pattern, "?")>0){return true;}
    return false;
}
function cleanDomain($www):string{
    if(trim($www)==null){return "";}
    if( isaregex($www) ){
        return $www;
    }
    if(preg_match("#^(http|ftp):#", $www)){
        $array=parse_url($www);
        $www=$array["host"];
        if(strpos($www, ":")>0){$t=explode(":", $www);$www=$t[0];}
    }

    $www=str_replace("^","",$www);
    if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
    if(preg_match("#^(.+?)\?#", $www,$re)){$www=$re[1];}
    if(preg_match("#^(.+?)\/#", $www,$re)){$www=$re[1];}
    if(strpos(" $www", "/")>0){return "";}
    if(strpos(" $www", ";")>0){return "";}
    if(strpos(" $www", ",")>0){return "";}
    if(strpos(" $www", "%")>0){return "";}
    if(strpos(" $www", "!")>0){return "";}
    if(strpos(" $www", "<")>0){return "";}
    if(strpos(" $www", ">")>0){return "";}
    if(strpos(" $www", "[")>0){return "";}
    if(strpos(" $www", "]")>0){return "";}
    if(strpos(" $www", "(")>0){return "";}
    if(strpos(" $www", ")")>0){return "";}
    if(strpos(" $www", "+")>0){return "";}
    if(strpos(" $www", "?")>0){return "";}
    $www=trim(strtolower($www));
    if(function_exists("idn_to_ascii")){$www = @idn_to_ascii($www, "UTF-8");}
    return $www;
}
function domain_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=intval($_POST["add-domain"]);
    $uid=$_SESSION["uid"];
    if($_SESSION["uid"]==-100){
        $uid="Manager";
    }
    $description=date("Y-m-d H:i:s").$tpl->javascript_parse_text(" {domain} {added} by $uid");

    $tb=explode("\n",$_POST["domain"] ?? "");
    foreach ($tb as $index=>$domain) {
        $domain=trim($domain);
        if($domain=="*"){
            [$ok,$data]=acls_api_send("POST","/proxy/simple-acls/objects",[
                "aclid"       => $ruleid,
                "direction"   => 1,
                "objecttype"  => 4,
                "pattern"     => "*",
                "description" => $description,
            ]);
            if(!$ok){ echo $tpl->post_error("$index.[*] - $data"); return false; }
            continue;
        }

        $www=cleanDomain($domain);
        if(strlen($www)<3){
            if(strlen(trim($domain))>0){
                echo $tpl->post_error("{domain} $index.[$domain] - {domain_too_short}");
                return false;
            }
            continue;
        }
        [$ok,$data]=acls_api_send("POST","/proxy/simple-acls/objects",[
            "aclid"       => $ruleid,
            "direction"   => 1,
            "objecttype"  => 4,
            "pattern"     => $www,
            "description" => $description,
        ]);
        if(!$ok){ echo $tpl->post_error("$index.[$www] - $data"); return false; }
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks_post("Add domain(s) to Proxy simple ACL $ruleid");
}

function srcdomain_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=intval($_POST["add-srcdomain"]);
    if($ruleid==0){
        echo $tpl->post_error("{no_rule_selected}");
        return false;
    }
    $uid=$_SESSION["uid"];
    if($_SESSION["uid"]==-100){
        $uid="Manager";
    }
    $description=date("Y-m-d H:i:s").$tpl->javascript_parse_text(" {srcdomain} {added} by $uid");

    $tb=explode("\n",$_POST["srcdomain"] ?? "");
    foreach ($tb as $index=>$domain) {
        $domain=trim($domain);
        if(strlen($domain)<3){continue;}
        [$ok,$data]=acls_api_send("POST","/proxy/simple-acls/objects",[
            "aclid"       => $ruleid,
            "direction"   => 0,
            "objecttype"  => 2,
            "pattern"     => $domain,
            "description" => $description,
        ]);
        if(!$ok){echo $tpl->post_error("$index.[$domain] - $data");return false;}
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks_post("Add Client Hostname to Proxy simple ACL $ruleid");
}
function mac_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=intval($_POST["add-mac"]);
    if($ruleid==0){
        echo $tpl->post_error("{no_rule_selected}");
        return false;
    }
    $uid=$_SESSION["uid"];
    if($_SESSION["uid"]==-100){
        $uid="Manager";
    }
    $description=date("Y-m-d H:i:s").$tpl->javascript_parse_text(" {mac} {added} by $uid");

    $tb=explode("\n",$_POST["mac"] ?? "");
    foreach ($tb as $index=>$domain) {
        $domain=trim($domain);
        if(strlen($domain)<3){continue;}
        [$ok,$data]=acls_api_send("POST","/proxy/simple-acls/objects",[
            "aclid"       => $ruleid,
            "direction"   => 0,
            "objecttype"  => 1,
            "pattern"     => $domain,
            "description" => $description,
        ]);
        if(!$ok){echo $tpl->post_error("$index.[$domain] - $data");return false;}
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks_post("Add MAC(s) to Proxy simple ACL $ruleid");
}
function src_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=intval($_POST["add-src"]);
    if($ruleid==0){
        echo $tpl->post_error("{no_rule_selected}");
        return false;
    }
    $uid=$_SESSION["uid"];
    if($_SESSION["uid"]==-100){
        $uid="Manager";
    }
    $description=date("Y-m-d H:i:s").$tpl->javascript_parse_text(" {src} {added} by $uid");

    $tb=explode("\n",$_POST["src"] ?? "");
    foreach ($tb as $index=>$domain) {
        $domain=trim($domain);
        if(strlen($domain)<3){continue;}
        [$ok,$data]=acls_api_send("POST","/proxy/simple-acls/objects",[
            "aclid"       => $ruleid,
            "direction"   => 0,
            "objecttype"  => 0,
            "pattern"     => $domain,
            "description" => $description,
        ]);
        if(!$ok){echo $tpl->post_error("$index.[$domain] - $data");return false;}
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks_post("Add source(s) to Proxy simple ACL $ruleid");
}
function domain_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleid=intval($_GET["add-domain-popup"]);
    $form[]=$tpl->field_hidden("add-domain", $ruleid);
    $form[]=$tpl->field_textareacode("domain", "", null,true);

    $users=new usersMenus();
    $priv="AsDansGuardianAdministrator";
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$ruleid])){
            $priv="";
        }
    }

    $jsafter[]="dialogInstance8.close();";
    if(strlen($function)>0){
        $jsafter[]= "$function();";
    }

    $html=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        @implode(";",$jsafter),$priv);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function src_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleid=intval($_GET["add-src-popup"]);
    $form[]=$tpl->field_hidden("add-src", $ruleid);
    $form[]=$tpl->field_textareacode("src", "", null,true);

    $jsafter[]="dialogInstance8.close();";
    if(strlen($function)>0){
        $jsafter[]= "$function();";
    }
    $users=new usersMenus();
    $priv="AsDansGuardianAdministrator";
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$ruleid])){
            $priv="";
        }
    }

    $html=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        @implode(";",$jsafter),$priv);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function mac_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleid=intval($_GET["add-mac-popup"]);
    $form[]=$tpl->field_hidden("add-mac", $ruleid);
    $form[]=$tpl->field_textareacode("mac", "", null,true);

    $users=new usersMenus();
    $priv="AsDansGuardianAdministrator";
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$ruleid])){
            $priv="";
        }
    }

    $jsafter[]="dialogInstance8.close();";
    if(strlen($function)>0){
        $jsafter[]= "$function();";
    }

    $html=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        @implode(";",$jsafter),$priv);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function srcdomain_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleid=intval($_GET["add-srcdomain-popup"]);
    $form[]=$tpl->field_hidden("add-srcdomain", $ruleid);
    $form[]=$tpl->field_textareacode("srcdomain", "", null,true);

    $jsafter[]="dialogInstance8.close();";
    if(strlen($function)>0){
        $jsafter[]= "$function();";
    }
    $users=new usersMenus();
    $priv="AsDansGuardianAdministrator";
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$ruleid])){
            $priv="";
        }
    }

    $html=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        @implode(";",$jsafter),$priv);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function categories_popup(){
    include_once(dirname(__FILE__)."/ressources/class.categories.inc");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["add-categories-popup"]);
    $function   = $_GET["function"];
    $Ccategories=new categories();
    $EnableNRDS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNRDS"));
    if($ruleid==0){
        echo $tpl->div_error("{no_rule_selected}");
        return true;
    }
    $users=new usersMenus();
    $priv=false;
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$ruleid])){
            $priv=true;
        }
    }else{
        $priv=true;
    }

    // Build set of already-assigned category IDs (objecttype=5, direction=1)
    $ALREADY=[];
    $existingObjs=acls_api_get("/proxy/simple-acls/$ruleid/objects?direction=1");
    if(is_array($existingObjs)){
        foreach($existingObjs as $obj){
            if(intval($obj["objectType"] ?? 0)==5){
                $ALREADY[intval($obj["pattern"] ?? 0)]=true;
            }
        }
    }

    $qPos=new postgres_sql();
    $Ccategories->patches_categories();
    $dans=new dansguardian_rules();
    if($qPos->COUNT_ROWS("personal_categories")==0){$dans->CategoriesTableCache();}

    if(!$qPos->TABLE_EXISTS("personal_categories")){
        $Ccategories->initialize();
    }

    $sql="SELECT *  FROM personal_categories ORDER BY categoryname ASC";
    $results = $qPos->QUERY_SQL($sql);
    if(!$qPos->ok){echo $qPos->mysql_error_html(true);}
    // Le <div class="btn-group"> ouvert ici n'etait JAMAIS referme.
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{category}</th>";
    $html[]="<th class='center'>{select}</th>";
    $html[]="</tr></thead><tbody id='pxrc-rows'>";
    while ($ligne = pg_fetch_assoc($results)) {
        if(preg_match("#^reserved#",$ligne['categoryname'])){continue;}
        if($ligne["category_icon"]==null){$ligne["category_icon"]="img/20-categories-personnal.png";}
        if(isset($ALREADY[$ligne["category_id"]])){continue;}
        $meta=intval($ligne["meta"]);
        $license=null;$remote_explain=null;
        $img="{$ligne["category_icon"]}";
        $button="";
        $md=md5(serialize($ligne));
        $ligne['category_description']=$tpl->utf8_encode($ligne['category_description']);
        $category_id=$ligne["category_id"];
        $remotecatz=intval($ligne["remotecatz"]);
        if($remotecatz>0){
            $remote_explain="&nbsp;<small>({use_remote_categories_services})</small>&nbsp;";

        }
        if($meta==1){
            $remote_explain="&nbsp;<small>({use_remote_categories_services})</small>&nbsp;";

        }

        $styleText=null;
        $js="Loadjs('$page?category-post-js=$category_id&ruleid=$ruleid&md=$md&function=$function')";


        if($EnableNRDS==0){
            if($category_id==238){
                $button="<button class='btn btn-default btn-xs' OnClick=\"Blur()\">{disabled}</button>";
                $styleText="style='color:#CCCCCC'";
            }
        }

        if($priv) {
            $button = "<button class='btn btn-primary btn-xs' OnClick=\"$js\">{select}</button>";
        }

        $html[]=pxrules_category_row(
            "<img src='".htmlspecialchars($img)."' alt='' style='width:20px;height:20px'>",
            $tpl->_ENGINE_parse_body("{$ligne['categoryname']}"),
            $tpl->_ENGINE_parse_body("{$ligne['category_description']}$remote_explain$license"),
            $tpl->_ENGINE_parse_body($button),$md,$styleText);
    }
    $TheShieldsCguard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsCguard"));
    if($TheShieldsCguard==1) {
        $cguardico = "<i class='fa-solid fa-shield-quartered'></i>";
        $CGuard_categories = $Ccategories->CGuard_categories();
        foreach ($CGuard_categories as $category_id => $categoryname) {
            if (isset($ALREADY[$category_id])) {
                continue;
            }

            $md = md5("$category_id$categoryname");
            $js = "Loadjs('$page?category-post-js=$category_id&ruleid=$ruleid&md=$md&function=$function')";
            $button = "<button class='btn btn-primary btn-xs' OnClick=\"$js\">{select}</button>";
            // Le nom etait rendu DEUX fois, dans la colonne "categorie" et dans
            // la colonne "description".
            $html[]=pxrules_category_row($cguardico,htmlspecialchars((string)$categoryname),"",
                $tpl->_ENGINE_parse_body($button),$md,null);
        }
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span class='awc-pg' id='pxrc-pager'></span>";
    $html[]="</div></div>";

    // Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"pxrc-rows","pxrc-pager");



}
/**
 * Une categorie proposee a la selection.
 *
 * Le tableau portait QUATRE colonnes dont deux sans libelle, et le bloc
 * CGuard rendait le nom deux fois de suite (colonne "categorie" et colonne
 * "description"). L'icone devient la pastille d'identite et la description
 * passe en sous-titre.
 */
function pxrules_category_row($icon,$name,$description,$button,$md,$styleText):string{
    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'>$icon</span>";
    $h.="<span><span class='awc-name' $styleText>$name</span>";
    if(trim((string)$description)!==""){$h.="<div class='awc-meta' $styleText>$description</div>";}
    $h.="</span></div></td>";
    $h.="<td class='center'>$button</td>";
    $h.="</tr>";
    return $h;
}

function category_post_js():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $category=intval($_GET["category-post-js"]);
    $ruleid=intval($_GET["ruleid"]);
    $md=$_GET["md"];
    $function   = $_GET["function"];
    $uid=$_SESSION["uid"];
    if($_SESSION["uid"]==-100){
        $uid="Manager";
    }
    $description=date("Y-m-d H:i:s").$tpl->javascript_parse_text(" {category} {added} by $uid");

    // Remove any existing entry for this category in this rule (idempotent add)
    $existingObjs=acls_api_get("/proxy/simple-acls/$ruleid/objects?direction=1");
    if(is_array($existingObjs)){
        foreach($existingObjs as $obj){
            if(intval($obj["objectType"] ?? 0)==5 && intval($obj["pattern"] ?? -1)==$category){
                acls_api_send("DELETE","/proxy/simple-acls/objects/".intval($obj["id"] ?? 0));
            }
        }
    }

    [$ok,$data]=acls_api_send("POST","/proxy/simple-acls/objects",[
        "aclid"       => $ruleid,
        "direction"   => 1,
        "objecttype"  => 5,
        "pattern"     => (string)$category,
        "description" => $description,
    ]);
    if(!$ok){ echo "alert('".$tpl->javascript_parse_text($data)."')"; return false; }

    echo "$('#$md').remove();\n";
    if(strlen($function)>0){
        echo "$function();\n";
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return true;
}
function notify_message():bool{
    $page=CurrentPageName();
    $ID         = intval($_GET["notify-message"]);
    $tpl        = new template_admin();

    $ligne=acls_api_get("/proxy/simple-acls/$ID");
    if(!is_array($ligne)){echo $tpl->div_error("Could not load rule $ID");return false;}

    $zTemplate=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["zTemplate"] ?? "");


    if(!isset($zTemplate["ENABLE"])){$zTemplate["ENABLE"]=0;}
    if(!isset($zTemplate["BODY"])){$zTemplate["BODY"]=null;}
    if(!isset($zTemplate["TITLE"])){$zTemplate["TITLE"]=null;}
    if(!isset($zTemplate["TEMPLATE_ID"])){$zTemplate["TEMPLATE_ID"]=0;}
    if($zTemplate["BODY"]==null) {
        $zTemplate["BODY"] = "<div id=\"titles\">\n<h1>ERROR</h1>\n<h2>Cache Access Denied.</h2>\n</div>\n<hr>\n\n<div id=\"content\">\n<p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>\n\n<blockquote id=\"error\">\n<p><b>Cache Access Denied.</b></p>\n</blockquote>\n\n<p>Sorry, you are not currently allowed to request %U from this cache until you have authenticated yourself.</p>\n\n<p>Please contact the <a href=\"mailto:%w%W\">cache administrator</a> if you have difficulties authenticating yourself.</p>\n\n<br>\n</div>\n\n<hr> \n<div id=\"footer\">\n<p>Generated %T by %h (%s)</p>\n<!-- %c -->\n</div>";
    }
    if($zTemplate["TITLE"]==null) {$zTemplate["TITLE"]="ERROR: Internet Access Denied";}
    if(intval($zTemplate["TEMPLATE_ID"])==0){$zTemplate["TEMPLATE_ID"]=1;}

    $form[]=$tpl->field_hidden("notify-message",$ID);
    $form[]=$tpl->field_checkbox("ENABLE","{enable}",$zTemplate["ENABLE"],true);
    $form[]=$tpl->field_templates("TEMPLATE_ID","{template}",$zTemplate["TEMPLATE_ID"]);
    $form[]=$tpl->field_text("TITLE", "{subject}", $tpl->utf8_decode($zTemplate["TITLE"]));
    $form[]=$tpl->field_textareacode("BODY","{content}",$tpl->utf8_decode($zTemplate["BODY"]));
    $tpl->form_add_button("{help}", "Loadjs('fw.proxy.templates.error.squid.php?help-js')");
    $jsafter="Loadjs('$page?rule-fill=$ID');";
    echo $tpl->form_outside("($ID): {NotifyMessage}", $form,null,"{apply}",$jsafter,"AsDansGuardianAdministrator",false);
    return true;
}
function notify_message_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();

    if(($_POST["ENABLE"] ?? 0)==1){
        $_POST["ENABLE_ERROR_PAGE"]=0;
    }

    $ID=intval($_POST["notify-message"]);
    if($ID==0){
        echo $tpl->post_error("ID === 0 !!");
        return false;
    }
    $ligne=acls_api_get("/proxy/simple-acls/$ID");
    if(!is_array($ligne)){echo $tpl->post_error("Could not load rule $ID");return false;}
    $aclname=$ligne["aclName"] ?? "";
    $zTemplate=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["zTemplate"] ?? "");
    foreach ($_POST as $key=>$value){
        $zTemplate[$key]=$tpl->utf8_encode($value);
    }

    $zTemplateSer=serialize($zTemplate);
    $zTemplateNew=base64_encode($zTemplateSer);
    [$ok,$err]=acls_api_send("PUT","/proxy/simple-acls/$ID",["zTemplate"=>$zTemplateNew]);
    if(!$ok){echo $tpl->post_error($err);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Modify notification page of $aclname rule");
}