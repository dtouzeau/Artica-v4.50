<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_search_bar() / awc_pager_js() / awc_filter_js(): le
// kit des tableaux d'entites. Include au PREMIER NIVEAU -- glisse dans le
// bootstrap "if(!class_exists('sockets'))" il ne s'executerait jamais et la
// page mourrait sur "Call to undefined function awc_table_style()".
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();
if(!isReader($users)){die();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : eth etait recopie sans encodage dans la page (XSS).
if(isset($_GET["eth"])){$_GET["eth"]=preg_replace('/[^A-Za-z0-9_.:@-]/','',(string)$_GET["eth"]);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_main_save();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["example-js"])){example_js();exit;}


page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{http_reply_access}",ico_shield,
        "{http_reply_access_text}","$page?table=yes","proxy-reply-access","progress-reply-access-restart",
    false,"table-loader-proxy-reply-access");




    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {http_reply_access}",$html);
        echo $tpl->build_firewall();
        return;
    }

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

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
function enabled_js(){
    $tpl   = new template_admin();
    $aclid = intval($_GET["enabled-js"]);
    $row   = acls_api_get("/proxy/reply-access/$aclid");
    if($row === null){ echo $tpl->js_mysql_alert("Unable to fetch rule $aclid"); return; }
    $enabled = intval($row["enabled"]);
    $enabled = ($enabled == 1) ? 0 : 1;
    [$ok, $err] = acls_api_send("PUT", "/proxy/reply-access/$aclid", ["enabled" => $enabled]);
    if(!$ok){ echo $tpl->js_mysql_alert($err); }
}


function example_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    // --- Step 1: ensure the http_reply_access rule exists ---
    $aclid = 0;
    $allRules = acls_api_get("/proxy/reply-access");
    if(is_array($allRules)){
        foreach($allRules as $r){
            if(($r["config"] ?? "") === "ARTICA_EXAMPLE"){
                $aclid = intval($r["id"]);
                break;
            }
        }
    }
    if($aclid == 0){
        [$ok, $data] = acls_api_send("POST", "/proxy/reply-access", [
            "ruleName" => "Deny executables",
            "allow"    => 1,
            "config"   => "ARTICA_EXAMPLE",
        ]);
        if(!$ok){ echo $tpl->js_mysql_alert($data); return; }
        $aclid = intval($data["id"] ?? 0);
        // enable the newly created rule
        if($aclid > 0){
            acls_api_send("PUT", "/proxy/reply-access/$aclid", ["enabled" => 1]);
        }
    }
    if($aclid == 0){ echo $tpl->js_mysql_alert("Unable to found main ID"); return; }

    // --- Step 2: ensure the webfilters_sqgroups group exists ---
    $gpid = 0;
    $existingGroup = acls_api_get("/proxy/acls/sqlite/group/find?params=HTTP_REPLY_ACCESS_EXAMPLE");
    if(is_array($existingGroup) && !empty($existingGroup["ID"])){
        $gpid = intval($existingGroup["ID"]);
    }
    if($gpid == 0){
        [$ok2, $gdata] = acls_api_send("POST", "/proxy/acls/sqlite/group", [
            "GroupName" => "Mime: Executables",
            "GroupType" => "rep_mime_type",
            "enabled"   => 1,
            "tplreset"  => 0,
            "params"    => "HTTP_REPLY_ACCESS_EXAMPLE",
        ]);
        if(!$ok2){ echo $tpl->js_mysql_alert($gdata); return; }
        $gpid = intval($gdata["ID"] ?? $gdata["id"] ?? 0);
    }
    if($gpid == 0){ echo $tpl->js_mysql_alert("Unable to found Group ID"); return; }

    // --- Step 3: link aclid → gpid (INSERT OR IGNORE equivalent) ---
    acls_api_send("POST", "/proxy/acls/sqlite/link?table=http_reply_access_links", [
        "zmd5"   => md5($aclid.$gpid),
        "aclid"  => $aclid,
        "gpid"   => $gpid,
        "zOrder" => 0,
    ]);

    // --- Step 4: clear and repopulate webfilters_sqitems ---
    acls_api_send("DELETE", "/proxy/acls/sqlite/items?gpid=$gpid");

    $tt = [
        "application/x-msdownload", "application/octet-stream",
        "application/exe", "application/x-exe", "application/dos-exe",
        "vms/exe", "application/x-winexe", "application/msdos-windows",
        "application/x-msdos-program", "application/x-msi",
        "text/vbscript", "text/vbs", "application/gzip",
        "application/x-compress", "application/x-gtar", "application/x-tar",
        "application/x-rar-compressed", "application/x-7z-compressed",
        "application/x-bittorrent",
    ];
    $uid   = $_SESSION["uid"] ?? "";
    if($uid == -100){ $uid = "Manager"; }
    $zdate = date("Y-m-d H:i:s");
    foreach($tt as $mime){
        [$ok3, $err3] = acls_api_send("POST", "/proxy/acls/sqlite/items", [
            "pattern" => $mime,
            "gpid"    => $gpid,
            "enabled" => 1,
            "other"   => "$mime (EXE) mime type",
            "zdate"   => $zdate,
            "uid"     => $uid,
        ]);
        if(!$ok3){ echo "// item error: $err3\n"; }
    }

    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader-proxy-reply-access','$page?table=yes');";
}

function rule_delete_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $aclid=intval($_GET["delete-rule-js"]);
    header("content-type: application/x-javascript");

    // Pentest 2026-11-04 : name etait recopie sans encodage dans la page (XSS).
    $delete_personal_cat_ask=$tpl->javascript_parse_text("{delete} ".req_js($_GET["name"] ?? "")." ?");
    $t=time();
    $html="
	
	var xDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#row-parent-$aclid').remove();
	}
	
function Action$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-rule','$aclid');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
	
	Action$t();";
    echo $html;

}

function rule_delete(){
    $aclid = intval($_POST["delete-rule"]);
    [$ok, $err] = acls_api_send("DELETE", "/proxy/reply-access/$aclid");
    if(!$ok){ echo $err; return; }
    admin_tracks("Delete Proxy reply access rule  $aclid ");
}




function rule_id_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["ruleid-js"]);
    $title="{new_rule}";

    if($id>0){
        $row = acls_api_get("/proxy/reply-access/$id");
        if(is_array($row)){
            $title="{rule}: $id ".($row["ruleName"] ?? "");
        }
    }
    $title=$tpl->javascript_parse_text($title);
    $tpl->js_dialog($title,"$page?rule-popup=$id");
}



function rule_settings(){
    $aclid=intval($_GET["rule-settings"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ligne=["enabled"=>1,"zorder"=>1,"rulename"=>"","allow"=>0];
    $btname="{add}";
    $title="{new_rule}";
    $BootstrapDialog="BootstrapDialog1.close();";
    VERBOSE("ID = $aclid");
    if($aclid>0){
        $btname="{apply}";
        $row = acls_api_get("/proxy/reply-access/$aclid");
        if(is_array($row)){
            $ligne["rulename"] = $row["ruleName"] ?? "";
            $ligne["enabled"]  = intval($row["enabled"] ?? 1);
            $ligne["zorder"]   = intval($row["xOrder"] ?? 0);
            $ligne["allow"]    = intval($row["allow"] ?? 0);
        }
        $title=$ligne["rulename"];
        $BootstrapDialog=null;
    }

   $allow[0]="{allow}";
   $allow[1]="{deny}";

    $tpl->field_hidden("rule-save", $aclid);
    $form[]=$tpl->field_text("rulename","{rule_name}",$ligne["rulename"],true,null,false);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);

    $form[]=$tpl->field_array_hash($allow, "allow", "{access}", $ligne["allow"],true,null,false);
    $form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"]);
    echo $tpl->form_outside($title,@implode("\n", $form),"{http_reply_access_explain}",$btname,"LoadAjax('table-loader-proxy-reply-access','$page?table=yes');$BootstrapDialog","AsDansGuardianAdministrator");
}




function rule_main_save(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsDansGuardianAdministrator){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return;}

    $aclid=intval($_POST["rule-save"]);
    $tpl->CLEAN_POST_XSS();
    $rulename=$tpl->CLEAN_BAD_XSS($_POST["rulename"]);

    $body = [
        "ruleName" => $rulename,
        "enabled"  => intval($_POST["enabled"] ?? 1),
        "xOrder"   => intval($_POST["zorder"] ?? 0),
        "allow"    => intval($_POST["allow"] ?? 0),
    ];

    if($aclid == 0){
        [$ok, $err] = acls_api_send("POST", "/proxy/reply-access", $body);
    }else{
        [$ok, $err] = acls_api_send("PUT", "/proxy/reply-access/$aclid", $body);
    }
    if(!$ok){ echo htmlspecialchars($err); }
}

function rule_tab(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $aclid=intval($_GET["rule-popup"]);

    $array["{rule}"]="$page?rule-settings=$aclid";
    if($aclid>0){
        $array["{objects}"]="fw.proxy.objects.php?aclid=$aclid&main-table=http_reply_access_links&fast-acls=0";

    }
    echo $tpl->tabs_default($array);


}



function rule_move_js(){
    header("content-type: application/x-javascript");
    $tpl   = new template_admin();
    $dir   = ($_GET["dir"] === "up") ? 0 : 1;
    $aclid = intval($_GET["aclid"]);
    [$ok, $err] = acls_api_send("PUT", "/proxy/reply-access/$aclid/move", ["dir" => $dir]);
    if(!$ok){
        $msg = $tpl->javascript_parse_text($err);
        echo "alert('".addslashes($msg)."');";
    }
}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();

    $rulename=$tpl->_ENGINE_parse_body("{rulename}");
    $ERROR_NO_PRIVS2=$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
    // $t=$_GET["t"] lisait une clef qui n'existe pas ("Undefined array key" a
    // chaque rendu), etait ecrase par time() a la ligne suivante, et n'etait
    // utilise nulle part. Les trois lignes sont parties.
    $add="Loadjs('$page?ruleid-js=0',true);";
    if(!isReader($users)){$add="alert('$ERROR_NO_PRIVS2')";}



    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    $ARRAY["CMD"]="/proxy/reply-access/apply";
    $ARRAY["TITLE"]="{GLOBAL_ACCESS_CENTER}";
    $ARRAY["REFRESH-MENU"]=1;
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-reply-access-restart')";

    $jsexample="Loadjs('$page?example-js=yes')";


    if(isWritter($users)) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
        $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");
        $topbuttons[] = array($jsexample, ico_plus, "{example}");
    }

    // Recherche cote navigateur. Pas de tri en revanche: l'ordre des regles est
    // SIGNIFIANT (squid applique la premiere qui correspond, d'ou les fleches
    // haut/bas), le reordonner a l'ecran mentirait sur ce que fait le proxy.
    $html[]=awc_search_bar("hra-search","{search}");

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{rulename}</th>";
    $html[]="<th class='awc-nowrap'>{action}</th>";
    $html[]="<th class='awc-nowrap'>{enabled}</th>";
    $html[]="<th class='awc-nowrap'>{order}</th>";
    $html[]="<th class='awc-nowrap'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='hra-rows'>";

    $jsAfter="LoadAjax('table-loader-proxy-reply-access','$page?table=yes&eth={$_GET["eth"]}');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

    $allows[0]="{allow}";
    $allows[1]="{reject}";
    // La couleur portait l'information "autoriser / rejeter" sur un bouton qui
    // faisait doublon avec le lien du nom. Elle passe sur un badge dedie.
    $badges[0]="awc-badge-on";
    $badges[1]="awc-badge-alert";

    $isRights=isRights();
    $results = acls_api_get("/proxy/reply-access");
    if(!is_array($results)){ $results = []; }
    $ROWS=0;
    foreach($results as $index=>$ligne) {
        $ROWS++;
        $rulename=htmlspecialchars($ligne["ruleName"] ?? "");
        $rulenameenc=urlencode($rulename);
        $aclid=intval($ligne["id"] ?? 0);
        $allow=intval($ligne["allow"] ?? 0);
        if(!isset($allows[$allow])){$allow=0;}
        $check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$aclid')");

        $up=$tpl->icon_up("Loadjs('$page?move-js=yes&aclid=$aclid&dir=up')","AsDansGuardianAdministrator");
        $down=$tpl->icon_down("Loadjs('$page?move-js=yes&aclid=$aclid&dir=down')","AsDansGuardianAdministrator");
        $js="Loadjs('$page?ruleid-js=$aclid',true);";
        $delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js={$aclid}&name=$rulenameenc')","AsDansGuardianAdministrator");

        $explain="{for_objects} ".proxy_objects($aclid)." {then} <strong>{$allows[$allow]}</strong> {web_server_reponse}";
        if(!$isRights){
            $up=null;
            $down=null;
            $delete=null;
        }

        // .awc-ident est display:flex -- il va sur un <div> INTERNE, jamais sur
        // le <td>, dont il detruirait le display:table-cell.
        $html[]="<tr id='row-parent-$aclid'>";
        $html[]="<td><div class='awc-ident'>"
               ."<span class='awc-ico'><i class='fa fa-paste'></i></span>"
               ."<div><div class='awc-name'>"
               ."<a href=\"javascript:blur();\" OnClick=\"$js\">$rulename</a></div>"
               ."<div class='awc-meta'>$explain</div></div></div></td>";
        $html[]="<td class='awc-nowrap'><span class='awc-badge {$badges[$allow]}'>{$allows[$allow]}</span></td>";
        $html[]="<td class='awc-nowrap'>$check</td>";
        $html[]="<td class='awc-nowrap'><div class='awc-acts'>$up$down</div></td>";
        $html[]="<td class='awc-nowrap'><div class='awc-acts'>$delete</div></td>";
        $html[]="</tr>";

    }

    // Ligne de repli, toujours en dernier: elle decrit ce que fait squid quand
    // aucune regle ci-dessus ne correspond. Son id etait 'row-parent-$aclid',
    // donc un DOUBLON de la derniere regle -- et une "Undefined variable" quand
    // la liste est vide.
    $html[]="<tr id='row-parent-default'>";
    $html[]="<td><div class='awc-ident'>"
           ."<span class='awc-ico awc-ico-off'><i class='fa fa-paste'></i></span>"
           ."<div><div class='awc-name awc-name-off'>{default}</div>"
           ."<div class='awc-meta'>{for_objects} {all} {then} {do_nothing} {after_webserver_reply}</div>"
           ."</div></div></td>";
    $html[]="<td class='awc-nowrap'><span class='awc-badge awc-badge-off'>{do_nothing}</span></td>";
    $html[]="<td class='awc-nowrap'>".$tpl->icon_nothing()."</td>";
    $html[]="<td class='awc-nowrap'>&nbsp;</td>";
    $html[]="<td class='awc-nowrap'>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";

    $html[]="</tbody>";
    $html[]="</table></div>";
    $html[]="<div class='awc-foot'><span>".$tpl->FormatNumber($ROWS)." {rules}</span>"
           ."<span class='awc-pg' id='hra-pager'></span></div>";
    $html[]="</div></div>";

    $TINY_ARRAY["TITLE"]="{http_reply_access}";
    $TINY_ARRAY["ICO"]=ico_shield;
    $TINY_ARRAY["EXPL"]="{http_reply_access_text}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // Le <script> sort du tableau: il traversait $html, qui n'etait PAS analyse
    // -- desormais il l'est, et la regex #{(.+?)}# de _ENGINE_parse_body()
    // prendrait chaque corps de fonction JavaScript pour un jeton.
    // ICON_SCRIPTS_COMPILE() et non $tpl->ICON_SCRIPTS: il vide aussi la pile
    // globale que icon_check() alimente en parallele, seule garantie que les
    // fonctions BtSwitch<id> des cases a cocher soient bien declarees.
    $script="<script>\n$jstiny\nNoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n</script>";

    // CSS et JS se concatenent AUTOUR de _ENGINE_parse_body(), et le filtre
    // APRES le pager, qui lui passe la main par tb.__awcRedraw.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"hra-rows","hra-pager")
        .awc_filter_js("hra-search","hra-rows","hra-pager");

}
function isRights():bool{
    $users=new usersMenus();
    if($users->AsDansGuardianAdministrator){return true;}
    if($users->AsDansGuardianAdministrator){return true;}
    return false;
}

function proxy_objects($aclid):string{
    $qProxy=new mysql_squid_builder(true);
    $tt=array();

    // Fetch links for this acl rule
    $links = acls_api_get("/proxy/acls/sqlite/links?table=http_reply_access_links&aclid=$aclid");
    if(!is_array($links)){ return "{all}"; }

    foreach($links as $ligne) {
        $gpid=intval($ligne["gpid"] ?? 0);
        if($gpid == 0){ continue; }
        // Fetch group metadata
        $group = acls_api_get("/proxy/acls/sqlite/group/$gpid");
        if(!is_array($group)){ continue; }
        $js="Loadjs('fw.proxy.objects.php?object-js=yes&gpid=$gpid')";
        $neg_text="{is}";
        if(intval($ligne["negation"] ?? 0)==1){$neg_text="{is_not}";}
        $GroupName=htmlspecialchars($group["GroupName"] ?? "");
        $GroupType=$group["GroupType"] ?? "";
        $tt[]=$neg_text." <a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$GroupName</a> (".$qProxy->acl_GroupType[$GroupType].")";
    }

    if(count($tt)>0){
        return @implode("<br>{and} ", $tt);
    }else{
        return "{all}";
    }
}