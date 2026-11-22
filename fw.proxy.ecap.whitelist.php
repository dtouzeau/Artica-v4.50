<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
$GLOBALS["GroupType"]["src"]="{src_addr}";
$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
$GLOBALS["GroupType"]["dst"]="{dst_addr}";
$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
$GLOBALS["GroupType"]["dstdom_regex"]="{dstdomain_regex}";


// Pentest 2026-11-04 M-01 : la route renvoyait le HTML recu ; elle n'est
// appelee qu'a vide (pour effacer la zone), elle ne renvoie donc plus rien.
if(isset($_GET["btns-white"])){exit;}
if(isset($_GET["search-form"])){rules_search_block();exit;}
if(isset($_GET["search"])){rules_list_search();exit;}
if(isset($_GET["kpis"])){ecap_kpis();exit;}
if(isset($_GET["popup-js"])){popup_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["GroupType"])){ecapExplainThis();exit;}
if(isset($_GET["switch-js"])){eCapSwitchPerform();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_POST["ID"])){save();exit;}
if(isset($_GET["config-popup-js"])){config_popup_js();exit;}
if(isset($_GET["config-popup"])){config_popup();exit;}
if(isset($_GET["byjs"])){page_js();exit;}
if(isset($_GET["byjs-popup"])){page_js_popup();exit;}
if(isset($_GET["SquidDisableWhiteLists"])){SquidDisableWhiteLists();exit;}



rules_search_block();
function SquidDisableWhiteLists():bool{
    $SquidDisableWhiteLists = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableWhiteLists"));
    if($SquidDisableWhiteLists==1){
        $SquidDisableWhiteLists=0;
    }else{
        $SquidDisableWhiteLists=1;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDisableWhiteLists",$SquidDisableWhiteLists);
    $function=ecap_function();
    if($function!=""){echo "$function();\n";}
    echo restart_js();
    return true;
}

function delete_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-js"]);
    // md5 repart dans un selecteur jQuery: hexadecimal uniquement.
    $md5=preg_replace('/[^a-f0-9]/','',strtolower((string)($_GET["md5"] ?? "")));
    $ligne=acls_api_get("/proxy/ecap-whitelist/$ID");
    $pattern=is_array($ligne) ? ($ligne["pattern"] ?? $ID) : $ID;
    $page=CurrentPageName();
    $tpl->js_confirm_delete($pattern,"delete",$ID,"$('#$md5').remove();if(document.getElementById('ecapwl-kpis')){LoadAjaxSilent('ecapwl-kpis','$page?kpis=yes');}");
}

function delete(){
    $ID=intval($_POST["delete"]);
    $ligne=acls_api_get("/proxy/ecap-whitelist/$ID");
    $pattern=is_array($ligne) ? ($ligne["pattern"] ?? $ID) : $ID;

    list($ok,$err)=acls_api_send("DELETE","/proxy/ecap-whitelist/$ID");
    if(!$ok){echo (new template_admin())->post_error($err);return;}
    admin_tracks("Removed $pattern from the antivirus exclusions");

}

function page_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{global_whitelists}","$page?byjs-popup=yes");
}
function page_js_popup(){
    $page=CurrentPageName();
    echo "<div id='table-loader-global-whitelist'></div>
	<script>
	LoadAjax('table-loader-global-whitelist','$page?table=yes');
	</script>";
}

function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    include_once('ressources/class.tcpip.inc');
    $ID=intval($_POST["ID"]);
    $pattern=trim($_POST["pattern"]);
    $description=$_POST["description"];
    $ztype=$_POST["ztype"];
    $enabled=intval($_POST["enabled"]);
    $admtrck=array();

    $patternz=array();

    if($ID==0){
        if(strpos(" $pattern", "\n")>0){
            $patternz=explode("\n",$pattern);
        }else{
            $patternz[]=$pattern;
        }

        $ip=new IP();

        foreach ($patternz as $item){
            $item=trim(strtolower($item));
            if(preg_match("#^\.(.+)$#",$item,$re)){$item=$re[1];}

            if($ztype=="dstdomain"){
                if(preg_match("#(http|ftp).*?:\/\/#",$item)){
                    $url_extract=parse_url($item);
                    $item=$url_extract["host"];
                }

            }



            if($item==null){continue;}
            $admtrck[]=$item;

            if( ($ztype=="src") OR ($ztype=="dst")){
                if(!$ip->isIPAddressOrRange($item)){continue;}
            }
            if( $ztype=="arp"){
                $item=str_replace("-", ":", $item);
                if(!$ip->IsvalidMAC($item)){continue;}
            }

            list($ok,$err)=acls_api_send("POST","/proxy/ecap-whitelist",["pattern"=>$item,"description"=>$description,"ztype"=>$ztype,"enabled"=>$enabled]);
            if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($err));return false;}

        }
        return admin_tracks("Added to the antivirus exclusions ".@implode(",",$admtrck));


    }


    $pattern=trim(strtolower($pattern));

    if($ztype=="dstdomain"){
        if(preg_match("#(http|ftp).*?:\/\/#",$pattern)){
            $url_extract=parse_url($pattern);
            $pattern=$url_extract["host"];
        }

    }


    $ip=new IP();
    if( ($ztype=="src") OR ($ztype=="dst")){
        if(!$ip->isIPAddressOrRange($pattern)){return false;}
    }
    if( $ztype=="arp"){
        $pattern=str_replace("-", ":", $pattern);
        if(!$ip->IsvalidMAC($pattern)){return false;}
    }

    list($ok,$err)=acls_api_send("PUT","/proxy/ecap-whitelist/$ID",["pattern"=>$pattern,"description"=>$description,"enabled"=>$enabled]);
    if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($err));return false;}

    return admin_tracks("Edited the antivirus exclusion $pattern");



}
function config_popup(){

    $t=time();
    $data[]="# These items allow the proxy to :";
    $data[]="# - Bypass credentials (LDAP/Active Directory)";
    $data[]="# - Bypass ACLs";
    $data[]="# - Bypass Web-Filtering rules";
    $data[]="# - Bypass the Antivirus for Proxy server or Any ICAP server";
    $data[]="#";
    $data[]="#";
    $data[]="# White-list based on domains";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/ecap_whitelist.dstdomain.conf");
    $data[]="";
    $data[]="# White-list based on domains using regular expressions ( REGEX )";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/ecap_whitelist.dstdom_regex.conf");
    $data[]="";
    $data[]="# White-list based destination networks";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/ecap_whitelist.dst.conf");
    $data[]="";
    $data[]="# White-list based source networks";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/ecap_whitelist.src.conf");
    $data[]="";


    echo "<textarea style='margin-top:5px;font-family:\"Courier New\",serif;
	font-weight:bold;width:98%;height:746px;border:5px solid #8E8E8E;
	overflow:auto;font-size:18px !important' id='text-$t'>".@implode("\n", $data)."</textarea>";
}


function eCapSwitchPerform(){
    $tpl=new template_admin();
    $ID=intval($_GET["switch-js"]);
    $ligne=acls_api_get("/proxy/ecap-whitelist/$ID");
    if(!is_array($ligne)){$tpl->js_error("{error}: /proxy/ecap-whitelist/$ID");return;}
    $newEnabled=(intval($ligne["enabled"])==1)?0:1;
    list($ok,$err)=acls_api_send("PUT","/proxy/ecap-whitelist/$ID",["enabled"=>$newEnabled]);
    if(!$ok){
        $tpl->js_error($err);
        // L'interrupteur a deja bascule a l'ecran: on recharge le tableau pour montrer l'etat reel.
        $function=ecap_function();
        if($function!=""){echo "$function();";}
    }
}

function config_popup_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{configuration}", "$page?config-popup=yes");

}

function popup_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID"]);
    $function=ecap_function();
    $title=$tpl->javascript_parse_text("{new_item}");
    if($ID>0){
        $ligne=acls_api_get("/proxy/ecap-whitelist/$ID");
        if(is_array($ligne)){
            $ztype=$GLOBALS["GroupType"][$ligne["zType"]] ?? $ligne["zType"];
            $pattern=$ligne["pattern"];
            $title="$ztype: $pattern";
        }
    }

    return $tpl->js_dialog1($title, "$page?popup=yes&ID=$ID&function=$function");
}
function popup(){
    $jsafter="";
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID"]);
    $t=time();
    $buttonname="{add}";
    $function=ecap_function();
    $title=$tpl->javascript_parse_text("{new_item}");
    $ligne["enabled"]=1;
    // Ouverte par js_dialog1 : se ferme par dialogInstance1, en ajout comme en modification.
    $close="dialogInstance1.close();";
    $idtemp="";
    $js="";

    if($ID>0){
        $buttonname="{apply}";
        $ligne=acls_api_get("/proxy/ecap-whitelist/$ID");
        if(is_array($ligne)){
            $ztype=$GLOBALS["GroupType"][$ligne["zType"]] ?? $ligne["zType"];
            $pattern=$ligne["pattern"];
            $title="$ztype: $pattern";
        }
    }


    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
    if($ID==0){
        $form[]=$tpl->field_array_hash($GLOBALS["GroupType"], "ztype", "{type}",null, true,null,"ChangeExplain$t");
        $idtemp=$tpl->id_temp;
    }else{
        // En modification, l'aide du type est deja passee a form_outside (ecapExplainThis plus bas).
        $form[]=$tpl->field_hidden("ztype", $ligne["zType"] ?? "");
    }
    if($ID>0){
        $form[]=$tpl->field_text("pattern", "{pattern}", $ligne["pattern"],true);
    }else{
        $form[]=$tpl->field_textareacode("pattern", "{pattern}", null);
    }
    $form[]=$tpl->field_text("description", "{description}", $ligne["description"] ?? "");

    if(strlen($function)>5){
        $jsafter="$function()";
    }


    // Aide du type choisi (ajout): ChangeExplain la charge dans ce bloc. Elle visait
    // un #form-explain que form_outside n'emet pas: l'aide ne s'affichait jamais.
    if($ID==0){
        $html[]="<div id='ecapwl-explain-$t'></div>";
    }
    $html[]=$tpl->form_outside($title,@implode("\n",$form), ecapExplainThis($ligne["zType"] ?? null),$buttonname,"$jsafter;$close");



    $html[]="<script>";
    $html[]="
function ChangeExplain$t(){
	
	if(!document.getElementById('$idtemp')){alert('$idtemp!!');return;}
	var GroupType=document.getElementById('$idtemp').value;
	if(GroupType.length==0){return;}
	LoadAjaxSilent('ecapwl-explain-$t','$page?GroupType='+encodeURIComponent(GroupType));
	
}";
    $html[]="$js";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function restart_js():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/proxy/ecap/install","squid.ecap.progress","squid.ecap.progress.log","progress-squid-ecap-restart");
}
function rules_search_block():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Cartes d'indicateurs : rechargees par chaque rendu du tableau (rules_list_search),
    // donc toujours a jour apres un ajout, une bascule ou une suppression.
    echo "<div id='ecapwl-kpis' style='margin-top:10px'></div>";
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&table-final=yes");
    echo "</div>";
    return true;
}

// Feuille de style propre a la page (maquette stitch_proxy_antivirus_exclusions_manager).
// Emise HORS _ENGINE_parse_body : ses blocs {...} seraient lus comme des jetons.
function ecap_style():string{
    if(isset($GLOBALS["ECAPWL_STYLE"])){return "";}
    $GLOBALS["ECAPWL_STYLE"]=true;
    return "<style>
.ecapwl-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.ecapwl-kpi{background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:18px 20px;display:flex;flex-direction:column;gap:10px;min-height:150px}
.ecapwl-kpi-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.ecapwl-kpi-lbl{font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#3d4947;line-height:16px}
.ecapwl-kpi-ico{font-size:20px;color:#006a62;line-height:1}
.ecapwl-kpi-val{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}
.ecapwl-kpi-num{font-size:36px;font-weight:700;line-height:40px;color:#111d23}
.ecapwl-kpi-unit{font-size:14px;color:#3d4947}
.ecapwl-kpi-sub{font-size:13px;color:#6d7a77;margin-top:auto}
.ecapwl-bar{height:6px;border-radius:3px;background:#e3f0f8;overflow:hidden;margin-top:auto}
.ecapwl-bar>span{display:block;height:100%;background:#006a62;border-radius:3px}
.ecapwl-dot{display:inline-block;width:10px;height:10px;border-radius:50%;background:#26a69a;box-shadow:0 0 0 4px rgba(38,166,154,.2);margin-right:8px;vertical-align:middle}
.ecapwl-state{font-size:20px;font-weight:700;color:#111d23}
.ecapwl-pill{font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;background:#a0f1e7;color:#00504a;white-space:nowrap}
.ecapwl-kpi-off{background:#263238;border-color:#263238}
.ecapwl-kpi-off .ecapwl-kpi-lbl,.ecapwl-kpi-off .ecapwl-kpi-sub{color:#cfdce4}
.ecapwl-kpi-off .ecapwl-state{color:#fff}
.ecapwl-kpi-off .ecapwl-dot{background:#feb965;box-shadow:0 0 0 4px rgba(254,185,101,.25)}
.ecapwl-kpi-off .ecapwl-pill{background:#ffddb9;color:#663e00}
.ecapwl-chips{display:flex;flex-wrap:wrap;gap:10px;margin:4px 0 14px}
.ecapwl-chip{display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border:1px solid #bcc9c6;border-radius:999px;background:#fff;color:#3d4947;font-size:13px;font-weight:600;text-decoration:none!important;cursor:pointer}
.ecapwl-chip:hover{border-color:#006a62;color:#006a62}
.ecapwl-chip-on{background:#a0f1e7;border-color:#84d5cb;color:#00504a}
.ecapwl-chip-n{font-weight:400;opacity:.75}
.ecapwl-type{display:inline-block;font-size:12px;font-weight:600;padding:3px 10px;border-radius:999px;white-space:nowrap;border:1px solid transparent}
.ecapwl-t-dstdomain{background:#d7e4ec;color:#3d4947;border-color:#bcc9c6}
.ecapwl-t-dstdom_regex{background:#ffddb9;color:#663e00;border-color:#feb965}
.ecapwl-t-src{background:#a0f1e7;color:#00504a}
.ecapwl-t-dst{background:#84f5e8;color:#005049}
.ecapwl-t-arp{background:#e3f0f8;color:#3e4947;border-color:#bcc9c6}
.ecapwl-off td{color:#6d7a77}
.ecapwl-off .ecapwl-type{opacity:.55}
.ecapwl-off .awc-name a{text-decoration:line-through}
.ecapwl-date{font-size:13px;color:#3d4947;white-space:nowrap}
.ecapwl-desc{font-size:14px;color:#3d4947;min-width:180px}
.ecapwl-empty{padding:48px 20px;text-align:center}
.ecapwl-empty-ico{font-size:44px;color:#006a62;width:88px;height:88px;border-radius:24px;background:rgba(0,106,98,.1);display:inline-flex;align-items:center;justify-content:center;margin-bottom:18px}
.ecapwl-empty-t{font-size:20px;font-weight:700;color:#111d23;margin-bottom:8px}
.ecapwl-empty-x{font-size:14px;color:#3d4947;line-height:22px;margin-bottom:20px}
@media(max-width:1200px){.ecapwl-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.ecapwl-kpis{grid-template-columns:1fr}}
</style>";
}

// Libelle du type d'entree (jeton), commun aux pastilles, aux lignes et aux compteurs.
function ecap_type_label($ztype):string{
    return (string)($GLOBALS["GroupType"][$ztype] ?? $ztype);
}

// Liste complete des exclusions (null si l'API ne repond pas).
function ecap_all():?array{
    $all=acls_api_get("/proxy/ecap-whitelist");
    return is_array($all)?$all:null;
}

// Cartes d'indicateurs (?kpis=yes). Chiffres calcules sur la liste complete, sans filtre.
function ecap_kpis():bool{
    $tpl=new template_admin();
    $all=ecap_all();
    if($all===null){echo "";return false;}
    $SquidDisableWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableWhiteLists"));
    $total=count($all); $active=0; $n=array("dstdomain"=>0,"dstdom_regex"=>0,"src"=>0,"dst"=>0,"arp"=>0);
    foreach($all as $r){
        if(intval($r["enabled"] ?? 0)==1){$active++;}
        $z=(string)($r["zType"] ?? "");
        if(isset($n[$z])){$n[$z]++;}
    }
    $pct=$total>0?intval(round($active*100/$total)):0;
    $ips=$n["src"]+$n["dst"];

    $h=array();
    $h[]="<div class='ecapwl-kpis'>";
    $h[]="<div class='ecapwl-kpi'><div class='ecapwl-kpi-top'><span class='ecapwl-kpi-lbl'>{ecapwl_active_exclusions}</span>"
        ."<span class='ecapwl-kpi-ico'><i class='fas fa-shield-halved'></i></span></div>"
        ."<div class='ecapwl-kpi-val'><span class='ecapwl-kpi-num'>$active</span><span class='ecapwl-kpi-unit'>{ecapwl_out_of} $total</span></div>"
        ."<div class='ecapwl-bar'><span style='width:$pct%'></span></div></div>";
    $h[]="<div class='ecapwl-kpi'><div class='ecapwl-kpi-top'><span class='ecapwl-kpi-lbl'>{ecapwl_trusted_domains}</span>"
        ."<span class='ecapwl-kpi-ico'><i class='fas fa-globe'></i></span></div>"
        ."<div class='ecapwl-kpi-val'><span class='ecapwl-kpi-num'>{$n["dstdomain"]}</span></div>"
        ."<div class='ecapwl-kpi-sub'>{$n["dstdom_regex"]} {ecapwl_regex_lc}</div></div>";
    $h[]="<div class='ecapwl-kpi'><div class='ecapwl-kpi-top'><span class='ecapwl-kpi-lbl'>{ecapwl_networks_hosts}</span>"
        ."<span class='ecapwl-kpi-ico'><i class='fas fa-network-wired'></i></span></div>"
        ."<div class='ecapwl-kpi-val'><span class='ecapwl-kpi-num'>".($ips+$n["arp"])."</span><span class='ecapwl-kpi-unit'>{ecapwl_entities_exempted}</span></div>"
        ."<div class='ecapwl-kpi-sub'>$ips {ecapwl_ip_addresses} &middot; {$n["arp"]} {ecapwl_mac_addresses}</div></div>";
    if($SquidDisableWhiteLists==0){
        $h[]="<div class='ecapwl-kpi'><div class='ecapwl-kpi-top'><span class='ecapwl-kpi-lbl'>{ecapwl_global_state}</span>"
            ."<span class='ecapwl-pill'>{active2}</span></div>"
            ."<div class='ecapwl-state'><span class='ecapwl-dot'></span>{ecapwl_list_active}</div>"
            ."<div class='ecapwl-kpi-sub'>{ecapwl_applied_proxy}</div></div>";
    }else{
        $h[]="<div class='ecapwl-kpi ecapwl-kpi-off'><div class='ecapwl-kpi-top'><span class='ecapwl-kpi-lbl'>{ecapwl_global_state}</span>"
            ."<span class='ecapwl-pill'>{inactive2}</span></div>"
            ."<div class='ecapwl-state'><span class='ecapwl-dot'></span>{ecapwl_list_suspended}</div>"
            ."<div class='ecapwl-kpi-sub'>{ecapwl_all_scanned}</div></div>";
    }
    $h[]="</div>";
    echo ecap_style().$tpl->_ENGINE_parse_body(@implode("",$h));
    return true;
}

// Icone de la colonne d'identite selon le type d'entree (meme jeu que fw.proxy.whitelist.php).
function ecap_type_icon($ztype):string{
    if($ztype=="src"){return "fas fa-arrow-right-from-bracket";}
    if($ztype=="dst"){return "fas fa-arrow-right-to-bracket";}
    if($ztype=="arp"){return "fas fa-ethernet";}
    if($ztype=="dstdomain"){return "fas fa-globe";}
    if($ztype=="dstdom_regex"){return "fas fa-asterisk";}
    return "fas fa-shield-virus";
}

// Cle de tri de la colonne date: zDate est du texte "Y-m-d H:i:s"; illisible = 0.
function ecap_date_sortkey($zDate):int{
    $zDate=trim((string)$zDate);
    if($zDate==""){return 0;}
    $ts=@strtotime($zDate);
    return ($ts===false)?0:intval($ts);
}

// SECURITE: `function` est le nom de rafraichissement genere par search_block ("ss<time>").
// Il repart dans des OnClick et des $jsafter: liste blanche au site de lecture.
function ecap_function():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
}

// Filtre de type: uniquement une cle connue de GroupType, sinon aucun filtre.
function ecap_ztype_filter():string{
    $z=(string)($_GET["ztype"] ?? "");
    return isset($GLOBALS["GroupType"][$z])?$z:"";
}

// Une exclusion antivirus.
function ecap_row($tpl,$page,$ligne,$greyed,$function):string{
    $ID=intval($ligne["id"] ?? 0);
    $ztypeRaw=(string)($ligne["zType"] ?? "");
    $pattern=(string)($ligne["pattern"] ?? "");
    $md5=md5(serialize($ligne));
    $js="Loadjs('$page?popup-js=yes&ID=$ID&function=$function')";

    $ztypeLabel=ecap_type_label($ztypeRaw);   // jeton, resolu par l'analyse du tableau
    $tclass=preg_replace('/[^a-z_]/','',$ztypeRaw);
    $enabled=intval($ligne["enabled"] ?? 0);
    $off=($greyed || $enabled!=1);

    $ts=ecap_date_sortkey($ligne["zDate"] ?? "");
    $date=$ts>0?$tpl->time_to_date($ts):"-";

    $check=$tpl->icon_check($enabled,"Loadjs('$page?switch-js=$ID&function=$function')");
    $delete="<a class='awc-act awc-act-danger' title='{delete}' href=\"javascript:blur();\" "
           ."OnClick=\"Loadjs('$page?delete-js=$ID&md5=$md5')\"><i class='fas fa-trash-can'></i></a>";

    $h ="<tr id='$md5'".($off?" class='ecapwl-off'":"").">";
    $h.="<td class='ecapwl-date' title='".htmlspecialchars((string)($ligne["zDate"] ?? ""),ENT_QUOTES)."'>".htmlspecialchars($date)."</td>";
    $h.="<td class='awc-nowrap'><span class='ecapwl-type ecapwl-t-$tclass'>".htmlspecialchars($ztypeLabel)."</span></td>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico".($off?" awc-ico-off":"")."'><i class='".ecap_type_icon($ztypeRaw)."'></i></span>";
    $h.="<span><span class='awc-name".($off?" awc-name-off":"")."'>";
    $h.="<a href=\"javascript:blur();\" OnClick=\"$js\">".htmlspecialchars($pattern)."</a></span>";
    $h.="<div class='awc-meta'>".htmlspecialchars($ztypeRaw).($enabled!=1?" &middot; {disabled}":"")."</div>";
    $h.="</span></div></td>";
    $h.="<td class='ecapwl-desc'>".htmlspecialchars((string)($ligne["description"] ?? ""))."</td>";
    $h.="<td class='center awc-nowrap'>$check</td>";
    $h.="<td><div class='awc-acts'>$delete</div></td>";
    $h.="</tr>";
    return $h;
}

function rules_list_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=ecap_function();
    $add="Loadjs('$page?popup-js=yes&ID=0&function=$function');";
    $jsrestart=restart_js();
    $SquidDisableWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableWhiteLists"));

    $topbuttons[] = array($add, ico_plus, "{new_rule}");
    $topbuttons[] = array("Loadjs('$page?config-popup-js=yes')", ico_script, "{configuration}");
    // Interrupteur global de la liste (perdu lors de la refonte du tableau).
    if($SquidDisableWhiteLists==0) {
        $topbuttons[] = array("Loadjs('$page?SquidDisableWhiteLists=yes&function=$function')", ico_stop, "{disable_feature}");
    }else{
        $topbuttons[] = array("Loadjs('$page?SquidDisableWhiteLists=yes&function=$function')", ico_run, "{enable_feature}");
    }
    $topbuttons[] = array($jsrestart, ico_save, "{apply}");

    $TINY_ARRAY["TITLE"]="{av_exclusions}";
    $TINY_ARRAY["ICO"]="fa fa-thumbs-up";
    $TINY_ARRAY["EXPL"]="{avexlusion_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    // Les cartes d'indicateurs suivent chaque rendu du tableau.
    $jskpi="if(document.getElementById('ecapwl-kpis')){LoadAjaxSilent('ecapwl-kpis','$page?kpis=yes');}";

    $allResults=ecap_all();
    if($allResults===null){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}: /proxy/ecap-whitelist"))
            ."<script>NoSpinner();$jstiny</script>";
        return false;
    }
    $total=count($allResults);

    $search=trim($_GET["search"] ?? "");
    if(strlen($search)>1){
        $searchPat=str_replace("*","",$search);
        $allResults=array_values(array_filter($allResults,function($r) use($searchPat){
            return (stripos((string)($r["pattern"] ?? ""),$searchPat)!==false
                || stripos((string)($r["description"] ?? ""),$searchPat)!==false);
        }));
    }

    // Compteurs des pastilles: sur le resultat de la recherche, AVANT le filtre de type.
    $counts=array();
    foreach($allResults as $r){
        $z=(string)($r["zType"] ?? "");
        $counts[$z]=($counts[$z] ?? 0)+1;
    }
    $searched=count($allResults);
    $ztype=ecap_ztype_filter();
    if($ztype!=""){
        $allResults=array_values(array_filter($allResults,function($r) use($ztype){
            return (string)($r["zType"] ?? "")==$ztype;
        }));
    }

    // Tri puis decoupe COTE SERVEUR (meme raison que fw.proxy.whitelist.php: icon_check()
    // emet ses fonctions par ligne, un fragment de milliers de lignes fige le navigateur).
    $dir=(strtolower((string)($_GET["dir"] ?? "desc"))=="asc")?"asc":"desc";
    $sens=($dir=="asc")?1:-1;
    usort($allResults,function($a,$b) use($sens){
        $x=ecap_date_sortkey($a["zDate"] ?? ""); $y=ecap_date_sortkey($b["zDate"] ?? "");
        if($x==$y){return 0;}
        return ($x<$y?-1:1)*$sens;
    });
    $psize=intval($GLOBALS["FOOTABLE_PSIZE"]); if($psize<10){$psize=100;}
    $found=count($allResults);
    $pages=intval(ceil($found/$psize)); if($pages<1){$pages=1;}
    $pageNo=intval($_GET["page"] ?? 1);
    if($pageNo<1){$pageNo=1;} if($pageNo>$pages){$pageNo=$pages;}
    $allResults=array_slice($allResults,($pageNo-1)*$psize,$psize);

    $rows=array();
    foreach($allResults as $ligne){
        $rows[]=ecap_row($tpl,$page,$ligne,$SquidDisableWhiteLists==1,$function);
    }

    // Les pastilles, l'en-tete de date et le pager rechargent CE fragment (div fourni par search_block).
    $divid=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["div-id"] ?? ""));
    $base0="$page?search=".urlencode($search)."&function=$function&div-id=$divid";
    $baseurl="$base0&ztype=$ztype";
    $fleche=($dir=="asc")?"&#9650;":"&#9660;";
    $dirNext=($dir=="asc")?"desc":"asc";

    $html=array();

    // Pastilles de type (masquees tant que la liste est vide: rien a filtrer).
    if($total>0){
        $chips=array(""=>array("{all}",$searched,"fas fa-layer-group"));
        foreach(array("dstdomain","dstdom_regex","src","dst","arp") as $z){
            $chips[$z]=array(ecap_type_label($z),intval($counts[$z] ?? 0),ecap_type_icon($z));
        }
        $html[]="<div class='ecapwl-chips'>";
        foreach($chips as $z=>$c){
            $on=($z==$ztype)?" ecapwl-chip-on":"";
            $html[]="<a class='ecapwl-chip ecapwl-chip-a$on' href=\"javascript:blur();\" data-z='$z'>"
                   ."<span style='font-size:13px'><i class='{$c[2]}'></i></span>{$c[0]}"
                   ."<span class='ecapwl-chip-n'>({$c[1]})</span></a>";
        }
        $html[]="</div>";
    }

    $html[]="<div class='awc-wrap' style='padding:0;margin:0'><div class='awc-card'>";
    if($total==0){
        // Etat vide: une invitation, pas un tableau vide.
        $html[]="<div class='ecapwl-empty'>";
        $html[]="<div class='ecapwl-empty-ico'><i class='fas fa-shield-halved'></i></div>";
        $html[]="<div class='ecapwl-empty-t'>{ecapwl_empty_title}</div>";
        $html[]="<div class='ecapwl-empty-x'>{ecapwl_empty_text}</div>";
        $html[]="<a class='awc-btn' href=\"javascript:blur();\" OnClick=\"$add\"><i class='fas fa-plus'></i> {ecapwl_first_exclusion}</a>";
        $html[]="</div></div></div>";
    }else{
        $html[]="<div class='awc-scroll'>";
        $html[]="<table class='awc-tbl'><thead><tr>";
        // Fleche en <span>: un <i class='fa'> deviendrait un <svg> (Font Awesome en mode SVG).
        $html[]="<th class='awc-nowrap'><a id='ecapwl-th-date' href=\"javascript:blur();\" "
               ."data-dir='$dirNext' title='{click_to_sort}' style='color:inherit;text-decoration:none'>{date}"
               ."<span style='margin-left:6px;opacity:.55;font-size:10px'>$fleche</span></a></th>";
        $html[]="<th class='awc-nowrap'>{type}</th>";
        $html[]="<th>{ecapwl_excluded_item}</th>";
        $html[]="<th>{description}</th>";
        $html[]="<th class='center awc-nowrap'>{enabled}</th>";
        $html[]="<th style='text-align:right'>{actions}</th>";
        $html[]="</tr></thead><tbody id='ecapwhitelist-rows'>";
        if(count($rows)==0){
            $html[]="<tr><td colspan='6'><div class='awc-empty'>{no_data}</div></td></tr>";
        }else{
            $html[]=@implode("\n",$rows);
        }
        $html[]="</tbody></table></div>";
        $compte="{showing} <strong>".count($rows)."</strong> / <strong>$found</strong> {exclusions}";
        if($found!=$total){$compte.=" ($total)";}
        $html[]="<div class='awc-foot'><span>$compte</span>"
               ."<span class='awc-pg'>".awc_pager_server($pageNo,$pages,"ecapwl-pg-a")."</span></div>";
        $html[]="</div></div>";
    }

    $js="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny\n$jskpi\n</script>";

    $nav ="<script>\n(function(){\n";
    $nav.=" var base=".json_encode($baseurl).", base0=".json_encode($base0).", div=".json_encode($divid).";\n";
    $nav.=" if(div==\"\"){return;}\n";
    $nav.=" function go(u){ LoadAjaxTiny(div,u); }\n";
    $nav.=" var th=document.getElementById(\"ecapwl-th-date\");\n";
    $nav.=" if(th){ th.onclick=function(){ go(base+\"&dir=\"+this.getAttribute(\"data-dir\")); return false; }; }\n";
    $nav.=" var as=document.querySelectorAll(\".ecapwl-pg-a\");\n";
    $nav.=" for(var i=0;i<as.length;i++){\n";
    $nav.="   as[i].onclick=function(){ go(base+\"&dir=".$dir."&page=\"+this.getAttribute(\"data-p\")); return false; };\n";
    $nav.=" }\n";
    $nav.=" var cs=document.querySelectorAll(\".ecapwl-chip-a\");\n";
    $nav.=" for(var j=0;j<cs.length;j++){\n";
    $nav.="   cs[j].onclick=function(){ go(base0+\"&dir=".$dir."&ztype=\"+encodeURIComponent(this.getAttribute(\"data-z\"))); return false; };\n";
    $nav.=" }\n})();\n</script>";

    // CSS et JS concatenes AUTOUR de l'analyse: _ENGINE_parse_body lirait leurs {...} comme des jetons.
    echo awc_table_style().ecap_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js.$nav;
    return true;
}
function ecapExplainThis($tata=null):string{
    if($tata<>null){$_GET["GroupType"]=$tata;}
    $GroupType=$_GET["GroupType"];
    $explain="";
    // Aides propres a l'antivirus: les jetons generiques des ACL disaient a tort
    // que "www.domain.tld" n'est pas accepte, et dstdom_regex n'avait aucune aide
    // alors que sa syntaxe (jokers *, prefixe regex:) est celle de StringToRegexSquid.
    if($GroupType=="src"){$explain="{ecapav_wl_explain_src}";}
    if($GroupType=="dst"){$explain="{ecapav_wl_explain_dst}";}
    if($GroupType=="arp"){$explain="{ecapav_wl_explain_arp}";}
    if($GroupType=="dstdomain"){$explain="{ecapav_wl_explain_dstdomain}";}
    if($GroupType=="dstdom_regex"){$explain="{ecapav_wl_explain_dstdom_regex}";}
    if($GroupType=="maxconn"){$explain="{squid_aclmax_connections_explain}";}
    if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
    if($GroupType=="ext_user"){$explain="{acl_squid_ext_user_explain}";}
    if($GroupType=="req_mime_type"){$explain="{req_mime_type_explain}";}
    if($GroupType=="rep_mime_type"){$explain="{rep_mime_type_explain}";}
    if($GroupType=="referer_regex"){$explain="{acl_squid_referer_regex_explain}";}
    if($GroupType=="srcdomain"){$explain="{acl_squid_srcdomain_explain}";}
    if($GroupType=="url_regex_extensions"){$explain="{url_regex_extensions_explain}";}
    if($GroupType=="max_user_ip"){$explain="<b>{acl_max_user_ip_title}</b><br>{acl_max_user_ip_text}";}
   // if($GroupType=="quota_time"){$explain="{acl_quota_time_text}";}
   // if($GroupType=="quota_size"){$explain="{acl_quota_size_text}";}
    if($GroupType=="ssl_sni"){$explain="{acl_ssl_sni_text}";}
    if($GroupType=="myportname"){$explain="{acl_myportname_text}";}
    if($GroupType=="rep_header_filename"){$explain="{rep_header_filename_explain}";}
    if($GroupType=="browser"){$explain="{acl_squid_browser_explain}";}

    if($tata<>null){return $explain;}
    if($explain==null){return "";}
    $html="<div class='alert alert-info'>$explain</div>";
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return "";

}