<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsDansGuardianAdministrator){exit();}
$GLOBALS["GroupType"]["src"]="{src_addr}";
$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
$GLOBALS["GroupType"]["dst"]="{dst_addr}";
$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
$GLOBALS["GroupType"]["dstdom_regex"]="{dstdomain_regex}";
$GLOBALS["GroupType"]["browser"]="{browser}";
$GLOBALS["GroupType"]["tempwhite"]="{temporary_whitelist}";

// Pentest 2026-11-04 M-01 : la route renvoyait le HTML recu ; elle n'est
// appelee qu'a vide (pour effacer la zone), elle ne renvoie donc plus rien.
if(isset($_GET["btns-white"])){exit;}
if(isset($_GET["search-form"])){rules_search_block();exit;}
if(isset($_GET["search"])){rules_list_search();exit;}
if(isset($_GET["popup-js"])){popup_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["GroupType"])){ExplainThis();exit;}
if(isset($_GET["switch-js"])){switch_perform();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_POST["ID"])){save();exit;}
if(isset($_GET["config-popup-js"])){config_popup_js();exit;}
if(isset($_GET["config-popup"])){config_popup();exit;}
if(isset($_GET["byjs"])){page_js();exit;}
if(isset($_GET["byjs-popup"])){page_js_popup();exit;}
if(isset($_GET["SquidDisableWhiteLists"])){SquidDisableWhiteLists();exit;}



page();
function SquidDisableWhiteLists(){
    $SquidDisableWhiteLists = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableWhiteLists"));
    if($SquidDisableWhiteLists==1){
        $SquidDisableWhiteLists=0;
    }else{
        $SquidDisableWhiteLists=1;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDisableWhiteLists",$SquidDisableWhiteLists);
    $function=$_GET["function"];
    echo "$function();\n";
    echo restart_js();

}

function delete_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-js"]);
    // md5 repart dans le $jsafter de js_confirm_delete, donc dans du JavaScript execute:
    // filtre a l'hexadecimal au site de lecture.
    $md5=preg_replace('/[^a-f0-9]/','',(string)($_GET["md5"] ?? ""));
    $ligne=acls_api_get("/proxy/acls-whitelist/$ID");
    $pattern=is_array($ligne) ? ($ligne["pattern"] ?? $ID) : $ID;
    $tpl->js_confirm_delete($pattern,"delete",$ID,"$('#{$md5}').remove();");
}

function delete(){
    $ID=intval($_POST["delete"]);
    $ligne=acls_api_get("/proxy/acls-whitelist/$ID");
    $pattern=is_array($ligne) ? ($ligne["pattern"] ?? $ID) : $ID;

    list($ok,$err)=acls_api_send("DELETE","/proxy/acls-whitelist/$ID");
    if(!$ok){echo $err;return;}
    admin_tracks("Removed $pattern from global whitelist");

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


    $admtrck=array();

    $patternz=array();


    if($ID==0){
        if(strpos(" $pattern", "\n")>0){
            $patternz=explode("\n",$pattern);
        }else{
            $patternz[]=$pattern;
        }

        $ip=new IP();
        $lib=new lib_memcached();

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

            if($ztype<>"dstdom_regex") {
                $line = str_replace("^", "", $item);
                if(substr($line,0,1)=="."){$line=substr($line, 1,strlen($line));}
            }

            $lib->saveKey("isWhite:$line",true,300);

            list($ok,$err)=acls_api_send("POST","/proxy/acls-whitelist",["pattern"=>$item,"description"=>$description,"ztype"=>$ztype]);
            if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($err));return;}


        }
        return admin_tracks("Added in global whitelist ".@implode(",",$admtrck));


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
        if(!$ip->isIPAddressOrRange($pattern)){return;}
    }
    if( $ztype=="arp"){
        $pattern=str_replace("-", ":", $pattern);
        if(!$ip->IsvalidMAC($pattern)){return;}
    }




    list($ok,$err)=acls_api_send("PUT","/proxy/acls-whitelist/$ID",["pattern"=>$pattern,"description"=>$description]);
    if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($err));return;}

    return admin_tracks("Edited global whitelist $pattern");



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
    $data[]=@file_get_contents("/etc/squid3/acls_whitelist.dstdomain.conf");
    $data[]="";
    $data[]="# White-list based on domains using regular expressions ( REGEX )";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/acls_whitelist.dstdom_regex.conf");
    $data[]="";
    $data[]="# White-list based destination networks";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/acls_whitelist.dst.conf");
    $data[]="";
    $data[]="# White-list based source networks";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/acls_whitelist.src.conf");
    $data[]="";


    echo "<textarea style='margin-top:5px;font-family:\"Courier New\",serif;
	font-weight:bold;width:98%;height:746px;border:5px solid #8E8E8E;
	overflow:auto;font-size:18px !important' id='text-$t'>".@implode("\n", $data)."</textarea>";
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{global_whitelists}","fa fa-thumbs-up",
        "{global_whitelists_proxy_explain}<div id='btns-white'></div>","$page?search-form=yes",
        "proxy-whitelists","progress-firehol-restart",false,"table-loader-global-whitelist");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{global_whitelists}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function switch_perform(){
    $tpl=new template_admin();
    $ID=intval($_GET["switch-js"]);
    $ligne=acls_api_get("/proxy/acls-whitelist/$ID");
    if(!is_array($ligne)){echo "alert('".addslashes("Error fetching record $ID")."');";return;}
    $newEnabled=($ligne["enabled"]==1)?0:1;
    list($ok,$err)=acls_api_send("PUT","/proxy/acls-whitelist/$ID",["enabled"=>$newEnabled]);
    if(!$ok){echo "alert('".$tpl->javascript_parse_text($err)."');";}
}

function config_popup_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{configuration}", "$page?config-popup=yes");

}

function popup_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID"]);
    $title=$tpl->javascript_parse_text("{new_item}");
    if($ID>0){
        $ligne=acls_api_get("/proxy/acls-whitelist/$ID");
        if(is_array($ligne)){
            $ztype=$GLOBALS["GroupType"][$ligne["zType"]] ?? $ligne["zType"];
            $pattern=$ligne["pattern"];
            $title="$ztype: $pattern";
        }
    }

    $tpl->js_dialog1($title, "$page?popup=yes&ID=$ID");
}
function popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID"]);
    $t=time();
    $buttonname="{add}";
    $title=$tpl->javascript_parse_text("{new_item}");
    $ligne["enabled"]=1;
    $close="";
    $idtemp="";
    $js="";

    if($ID>0){
        $buttonname="{apply}";
        $ligne=acls_api_get("/proxy/acls-whitelist/$ID");
        if(is_array($ligne)){
            $ztype=$GLOBALS["GroupType"][$ligne["zType"]] ?? $ligne["zType"];
            $pattern=$ligne["pattern"];
            $title="$ztype: $pattern";
        }
    }


    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],false,null,false,"AsDansGuardianAdministrator");
    if($ID==0){
        $close="BootstrapDialog1.close()";
        $form[]=$tpl->field_array_hash($GLOBALS["GroupType"], "ztype", "{type}",null, true,null,"ChangeExplain$t");
        $idtemp=$tpl->id_temp;
    }else{
        $form[]=$tpl->field_hidden("ztype", $ligne["zType"] ?? "");
        // En modification, l'aide du type est deja passee a form_outside (ExplainThis plus bas).
    }
    if($ID>0){
        $form[]=$tpl->field_text("pattern", "{pattern}", $ligne["pattern"],true);
    }else{
        $form[]=$tpl->field_textareacode("pattern", "{pattern}", null);
    }
    $form[]=$tpl->field_text("description", "{description}", $ligne["description"] ?? "");

    $jsafter="LoadAjax('table-loader-global-whitelist','$page?table=yes');";

    // Aide du type choisi (ajout): ChangeExplain la charge dans ce bloc. Elle visait
    // un #form-explain que form_outside n'emet pas: l'aide ne s'affichait jamais.
    if($ID==0){
        $html[]="<div id='wl-explain-$t'></div>";
    }
    $html[]=$tpl->form_outside($title,@implode("\n",$form), ExplainThis($ligne["zType"] ?? null),$buttonname,"$jsafter;$close");



    $html[]="<script>";
    $html[]="
function ChangeExplain$t(){

	if(!document.getElementById('$idtemp')){alert('$idtemp!!');return;}
	var GroupType=document.getElementById('$idtemp').value;
	if(GroupType.length==0){return;}
	LoadAjaxSilent('wl-explain-$t','$page?GroupType='+encodeURIComponent(GroupType));

}";
    $html[]="$js";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function restart_js():string{
    $tpl=new template_admin();

    return $tpl->framework_buildjs("/proxy/whitelists/nohupcompile","squid.global.whitelists.progress","squid.global.whitelists.progress.log","progress-firehol-restart");
}
function rules_search_block():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page,"","","","&table-final=yes");
    return true;
}

// Pictogramme du type d'entree: il porte a lui seul la distinction reseau / domaine / regex,
// ce que le libelle traduit ne fait qu'en toutes lettres.
function wl_type_icon($ztype):string{
    if($ztype=="src"){return "fas fa-arrow-right-from-bracket";}
    if($ztype=="dst"){return "fas fa-arrow-right-to-bracket";}
    if($ztype=="arp"){return "fas fa-network-wired";}
    if($ztype=="dstdomain"){return "fas fa-globe";}
    if($ztype=="dstdom_regex"){return "fas fa-asterisk";}
    if($ztype=="browser"){return "fas fa-window-maximize";}
    if($ztype=="tempwhite"){return "fas fa-hourglass-half";}
    return "fas fa-thumbs-up";
}

// Cle de tri de la colonne date. zDate est du texte SQLite "Y-m-d H:i:s" (cf. le daemon), mais
// les lignes de liste blanche TEMPORAIRE portent "{never}" quand elles n'expirent jamais: une
// date illisible vaut 0 et se range donc a une extremite, jamais au milieu des vraies dates.
function wl_date_sortkey($zDate):int{
    $zDate=trim((string)$zDate);
    if($zDate==""){return 0;}
    $ts=@strtotime($zDate);
    return ($ts===false)?0:intval($ts);
}

// Une entree de la liste blanche.
function wl_row($tpl,$page,$ligne,$greyed):string{
    $ID=intval($ligne["id"] ?? 0);
    $ztypeRaw=(string)($ligne["zType"] ?? "");
    $pattern=(string)($ligne["pattern"] ?? "");
    $md5=md5(serialize($ligne));

    $js="Loadjs('$page?popup-js=yes&ID=$ID')";
    $label="";
    if($ztypeRaw=="tempwhite"){
        $js="Loadjs('fw.proxy.tempwhite.php?src=".urlencode($pattern)."')";
        $label="&nbsp;<span class='awc-tag'>{temporary_whitelist}</span>";
    }

    $ztypeLabel=$tpl->javascript_parse_text($GLOBALS["GroupType"][$ztypeRaw] ?? $ztypeRaw);
    $enabled=intval($ligne["enabled"] ?? 0);
    $off=($greyed || $enabled!=1);

    $check=$tpl->icon_check($enabled,"Loadjs('$page?switch-js=$ID')");
    $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md5=$md5')","AsDansGuardianAdministrator");

    // Pas de data-sort: le tri est fait par le serveur avant la decoupe en pages.
    $h ="<tr id='$md5'>";
    $h.="<td class='awc-nowrap awc-mono'>".htmlspecialchars((string)($ligne["zDate"] ?? ""))."</td>";
    $h.="<td class='awc-nowrap'><span class='awc-tag'>".htmlspecialchars($ztypeLabel)."</span></td>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico".($off?" awc-ico-off":"")."'><i class='".wl_type_icon($ztypeRaw)."'></i></span>";
    $h.="<span class='awc-name".($off?" awc-name-off":"")."'>";
    $h.="<a href=\"javascript:blur();\" OnClick=\"$js\">".htmlspecialchars($pattern)."</a></span>";
    $h.="</div></td>";
    $h.="<td>".(string)($ligne["description"] ?? "")."$label</td>";
    $h.="<td class='center awc-nowrap'>$check</td>";
    $h.="<td><div class='awc-acts'>$delete</div></td>";
    $h.="</tr>";
    return $h;
}

function rules_list_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $type=$tpl->_ENGINE_parse_body("{type}");
    $description=$tpl->_ENGINE_parse_body("{description}");
    $items=$tpl->_ENGINE_parse_body("{items}");
    $date=$tpl->javascript_parse_text("{date}");
    $add="Loadjs('$page?popup-js=yes&ID=0');";
    $jsrestart=restart_js();
    // SECURITE: `function` est le nom de la fonction de rafraichissement generee par
    // search_block; il repart ici dans des attributs OnClick, donc un ?function= forge
    // injecterait du JavaScript dans la session de l'admin. search_block ne produit que
    // "ss<time>": on filtre au site de lecture.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $SquidDisableWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableWhiteLists"));

    $users=new usersMenus();
    if($users->AsDansGuardianAdministrator) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
    }
    $topbuttons[] = array("Loadjs('$page?config-popup-js=yes')", ico_script, "{configuration}");

    if($users->AsDansGuardianAdministrator) {

        $topbuttons[] = array("Loadjs('fw.proxy.tempwhite.php')", "fa-solid fa-filter-slash", "{temporary_whitelist}");

        if ($SquidDisableWhiteLists == 0) {
            $topbuttons[] = array("Loadjs('$page?SquidDisableWhiteLists=yes&function=$function')", ico_stop, "{disable_feature}");
        } else {
            $topbuttons[] = array("Loadjs('$page?SquidDisableWhiteLists=yes&function=$function')", ico_run, "{enable_feature}");
        }
    }

    $topbuttons[] = array($jsrestart, ico_save, "{apply}");


    $allResults=acls_api_get("/proxy/acls-whitelist");
    if(!is_array($allResults)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}: /proxy/acls-whitelist"));
        return false;
    }
    $allResults=merge_tempdeny($allResults);
    $total=count($allResults);

    $search=trim($_GET["search"] ?? "");
    if(strlen($search)>1){
        $searchPat=str_replace("*","",$search);
        $allResults=array_values(array_filter($allResults,function($r) use($searchPat){
            return (stripos($r["pattern"],$searchPat)!==false || stripos($r["description"],$searchPat)!==false);
        }));
    }

    $rows=array();
    // ---------------------------------------------------------------------------------------
    // Tri ET pagination CoTE SERVEUR.
    //
    // La version precedente envoyait les 1416 entrees d'une appliance de production dans un
    // seul fragment -- 3,6 Mo, dont ~1,35 Mo rien qu'en fonctions BtSwitch<id>() puisque
    // icon_check() en produit une PAR LIGNE. Le serveur repondait en 0,8 s; c'est le rendu qui
    // mourait: jQuery .html() analyse les 3,6 Mo, $.globalEval avale 1416 definitions, puis le
    // pager et le tri client parcourent 1416 <tr> -- Chrome rendait RESULT_CODE_HUNG.
    // Le navigateur ne recoit donc plus qu'une page de lignes, et le tri porte sur l'ensemble
    // des resultats parce qu'il est applique AVANT la decoupe.
    // ---------------------------------------------------------------------------------------
    $dir=(strtolower((string)($_GET["dir"] ?? "desc"))=="asc")?"asc":"desc";
    $sens=($dir=="asc")?1:-1;
    usort($allResults,function($a,$b) use($sens){
        $x=wl_date_sortkey($a["zDate"] ?? ""); $y=wl_date_sortkey($b["zDate"] ?? "");
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
        $rows[]=wl_row($tpl,$page,$ligne,$SquidDisableWhiteLists==1);
    }

    // L'en-tete de date et le pager rechargent CE fragment. div-id est fourni par search_block
    // (il cree <div id='results-<hash>'>), function est son nom de rafraichissement: les deux
    // repartent dans du JavaScript, donc liste blanche au site de lecture.
    $divid=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["div-id"] ?? ""));
    $baseurl="$page?search=".urlencode($search)."&function=$function&div-id=$divid";
    $fleche=($dir=="asc")?"&#9650;":"&#9660;";
    $dirNext=($dir=="asc")?"desc":"asc";

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    // La fleche est un <span>, PAS un <i class='fa ...'>: Font Awesome convertirait ce dernier
    // en <svg>, dont le className n'est pas une chaine -- l'indicateur resterait fige.
    $html[]="<th class='awc-nowrap'><a id='wl-th-date' href=\"javascript:blur();\" "
           ."data-dir='$dirNext' title='{click_to_sort}' style='color:inherit;text-decoration:none'>$date"
           ."<span style='margin-left:6px;opacity:.55;font-size:10px'>$fleche</span></a></th>";
    $html[]="<th class='awc-nowrap'>$type</th>";
    $html[]="<th>$items</th>";
    $html[]="<th>$description</th>";
    $html[]="<th class='center awc-nowrap'>{enabled}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='whitelist-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='6'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    // "affiches / trouves", et le total general seulement quand un filtre reduit l'ensemble.
    $compte="{showing} ".count($rows)." / $found";
    if($found!=$total){$compte.=" ($total)";}
    $html[]="<div class='awc-foot'><span>$compte</span>"
           ."<span class='awc-pg'>".awc_pager_server($pageNo,$pages,"wl-pg-a")."</span></div>";
    $html[]="</div></div>";

    $TINY_ARRAY["TITLE"]="{global_whitelists}";
    $TINY_ARRAY["ICO"]="fa fa-thumbs-up";
    $TINY_ARRAY["EXPL"]="{global_whitelists_proxy_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $js ="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny\n</script>";

    // Plus de awc_pager_js()/awc_sort_js(): ils travaillent sur les <tr> presents dans le DOM,
    // ce qui supposait de tous les y mettre. Un seul gestionnaire recharge le fragment.
    $nav ="<script>\n(function(){\n";
    $nav.=" var base=".json_encode($baseurl).", div=".json_encode($divid).";\n";
    $nav.=" if(div==\"\"){return;}\n";
    $nav.=" function go(u){ LoadAjaxTiny(div,u); }\n";
    $nav.=" var th=document.getElementById(\"wl-th-date\");\n";
    $nav.=" if(th){ th.onclick=function(){ go(base+\"&dir=\"+this.getAttribute(\"data-dir\")); return false; }; }\n";
    $nav.=" var as=document.querySelectorAll(\".wl-pg-a\");\n";
    $nav.=" for(var i=0;i<as.length;i++){\n";
    $nav.="   as[i].onclick=function(){ go(base+\"&dir=".$dir."&page=\"+this.getAttribute(\"data-p\")); return false; };\n";
    $nav.=" }\n})();\n</script>";

    // CSS et JS sont concatenes AUTOUR de l'analyse: la regex #{(.+?)}# de _ENGINE_parse_body
    // prend chaque corps de regle CSS et chaque bloc JavaScript pour un {jeton}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html).$js)
        .$nav;
    return true;
}

function ExplainThis($tata=null):string{
    if($tata<>null){$_GET["GroupType"]=$tata;}
    $GroupType=$_GET["GroupType"];
    $explain="";
    if($GroupType=="src"){$explain="{acl_src_text}";}
    if($GroupType=="dst"){$explain="{acl_dst_text}";}
    if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
    if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
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
    // Ce fragment est l'encart d'explication du formulaire, pas le tableau: il n'emet donc ni
    // la feuille awc ni le pager. Les emettre ici les faisait viser un <tbody> absent de CE
    // fragment -- la pagination du tableau ne s'installait jamais.
    $html="<div class='alert alert-info'>$explain</div>";
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return "";

}
function tempwhite_api($raw){
    $resp=json_decode(strval($raw),true);
    if(!is_array($resp)){return null;}
    return $resp;
}

function merge_tempdeny($allResults=array()):array{
    $resp=tempwhite_api($GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/tempwhite"));
    if($resp===null || empty($resp["Status"])){
        return $allResults;
    }
    $entries=is_array($resp["Entries"] ?? null)?$resp["Entries"]:array();
    if(count($entries)==0){
        return $allResults;
    }
    $c=9999999;
    foreach($entries as $entry) {
        $pattern = strval($entry["pattern"] ?? "");
        if ($pattern === "") {
            continue;
        }
        $distance = "";
        $expire = intval($entry["expire"] ?? 0);
        $expireText = ($expire == 0) ? "{never}" : date("Y-m-d H:i", $expire);
        if ($expire > 0) {
            $distance = "&nbsp;(" . distanceOfTimeInWords(time(), $expire) . ")";
        }

        $c++;
        // pattern est stocke BRUT: wl_row() echappe toutes les lignes de la meme facon, et
        // une valeur pre-echappee ici ressortait avec ses &amp; visibles.
        $allResults[] = array(
            "id" => $c,
            "zType" => "tempwhite",
            "pattern" => $pattern,
            "description" => "{temporary_whitelist} ({expire} $expireText$distance)",
            "zDate" => $expireText,
            "enabled" => 1
        );
    }

    return $allResults;
}
