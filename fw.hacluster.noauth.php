<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.hacluster.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){main();exit;}
if(isset($_GET["newitem-js"])){newitem_js();exit;}
if(isset($_GET["new-item-popup"])){newitem_popup();exit;}
if(isset($_POST["newitem"])){newitem_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){newitem_delete();exit;}




page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header(
        "{ActiveDirectory}&nbsp;&raquo;&nbsp;{authentication}&nbsp;&raquo;&nbsp;{whitelists}",
        "fa fa-align-justify",
        "{AUTHWHITELIST_EXPLAIN}",
        "$page?start=yes",
        "hacluster-white",
        "progress-ntlmwhite-restart",false,
        "table-ntlmwhite-rules"

    );




    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{ActiveDirectory} {authentication} {whitelists}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);

}

function noauth_function():string{
    // liste blanche : $function part dans OnClick / $jsafter sans echappement (skill §7b)
    return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
}

// rafraichissement apres une mutation : la fonction de search_block (conserve le filtre), sinon le tableau
function noauth_refresh_js(string $function):string{
    $page=CurrentPageName();
    if($function!=""){ return "if(typeof $function==='function'){ $function(); }else{ LoadAjax('table-ntlmwhite-rules','$page?start=yes'); }"; }
    return "LoadAjax('table-ntlmwhite-rules','$page?start=yes');";
}

function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->search_block($page,"","","","");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function newitem_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $function=noauth_function();
	$title=$tpl->javascript_parse_text("{new_item}");
	return $tpl->js_dialog3($title, "$page?new-item-popup=yes&function=$function");
	
}


function delete_js():bool{
	$tpl=new template_admin();
	$item=(string)($_GET["delete-js"] ?? "");
	$function=noauth_function();
	if(hacluster_meta_row("noauth",$item)){return hacluster_meta_refuse_js("{hacluster_meta_row_locked}");}
	return $tpl->js_confirm_delete($item,"delete",$item,noauth_refresh_js($function));
}

function newitem_popup():bool{
    $function=noauth_function();
    $tpl=new template_admin();
    $tt[]="dialogInstance3.close();";
    $tt[]="$function();";

    $js=@implode("",$tt);
    $form[]=$tpl->field_text("newitem", "{network_item}", null);
	$html[]=$tpl->form_outside(null,  $form,"{pdns_network_item_add}","{add}", $js,"AsProxyMonitor");
	echo $tpl->_ENGINE_parse_body( $html);
    return true;
}
function enable_js():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $item=(string)($_GET["enable-js"] ?? "");
    $function=noauth_function();
    if(hacluster_meta_row("noauth",$item)){return hacluster_meta_refuse_js("{hacluster_meta_row_locked}");}
    $rows=$q->QUERY_SQL_PARAMS("SELECT enabled FROM hacluster_noauth WHERE address=?",array($item));
    if(!$q->ok){ return $tpl->js_error($q->mysql_error); }
    $enabled=is_array($rows) && isset($rows[0]["enabled"]) ? intval($rows[0]["enabled"]) : 0;
    $new=($enabled==0)?1:0;
    $q->QUERY_SQL_PARAMS("UPDATE hacluster_noauth SET enabled=? WHERE address=?",array($new,$item));
    if(!$q->ok){ return $tpl->js_error($q->mysql_error); }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/build");
    admin_tracks("Set $item to ".($new==1?"active":"inactive")." from hacluster whitelist authentication");
    echo noauth_refresh_js($function);
    return true;
}
function newitem_save():bool{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$item=url_decode_special_tool($_POST["newitem"]);
    $item=$tpl->CLEAN_BAD_CHARSNET($item);

	if($item==null){
		echo $tpl->post_error("No posted data");
		return false;
	}
	if(hacluster_meta_row("noauth",$item)){echo $tpl->post_error("{hacluster_meta_address_held}");return false;}
	$q->QUERY_SQL_PARAMS("INSERT OR IGNORE INTO hacluster_noauth (`address`) VALUES(?)",array($item));
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/build");
    return admin_tracks_post("Adding Hacluster whitelist address from authentication");
}
function newitem_delete():bool{
    $tpl=new template_admin();
	$item=url_decode_special_tool($_POST["delete"]);
    if(hacluster_meta_row("noauth",$item)){return hacluster_meta_refuse_text("{hacluster_meta_row_locked}");}
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$q->QUERY_SQL_PARAMS("DELETE FROM hacluster_noauth WHERE address=?",array($item));
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/build");
    return admin_tracks("Remove $item from Hacluster whitelist address from authentication");

}
// repondeur ?search= : possede le tableau ET le TINY (skill §7d, §13b)
function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $function=noauth_function();
    $search=trim((string)($_GET["search"] ?? ""));

    $results = $q->QUERY_SQL("SELECT * FROM hacluster_noauth ORDER BY address");
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error)."<script>NoSpinner();</script>";
        return false;
    }
    if(!is_array($results)){ $results=array(); }

    $rows=array();
    foreach ($results as $ligne){
        $item=(string)$ligne["address"];
        if($search!=="" && stripos($item,$search)===false){ continue; }
        $rows[]=noauth_row($tpl,$page,$function,$item,intval($ligne["enabled"]));
    }

    $html=array();
    $html[]=hacluster_meta_banner($tpl,"noauth","{hacluster_meta_noauth_explain}");
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{items}</th>";
    $html[]="<th class='center'>{enabled}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='awc-rows'>";
    if(count($rows)==0){ $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>"; }
    else { $html[]=implode("\n",$rows); }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($rows)." / ".count($results)." {items}</span><span class='awc-pg' id='awc-pager'></span></div>";
    $html[]="</div></div>";

    // TINY : titre + boutons, emis en DERNIER, apres le tableau (skill §2, §7d)
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?newitem-js=yes&function=$function')", ico_plus, "{new_item}");
    $topbuttons[] = array("LoadAjax('table-ntlmwhite-rules','$page?start=yes')", ico_refresh, "{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{ActiveDirectory}&nbsp;&raquo;&nbsp;{authentication}&nbsp;&raquo;&nbsp;{whitelists}";
    $TINY_ARRAY["ICO"]="fa fa-align-justify";
    $TINY_ARRAY["EXPL"]="{AUTHWHITELIST_EXPLAIN}";
    $TINY_ARRAY["URL"]="hacluster-white";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // CSS, scripts d'icones et pager concatenes AUTOUR du parse, jamais dedans (skill §17 regle 1)
    $js="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny</script>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js.awc_pager_js(25);
}

// une ligne du tableau (skill §13b)
function noauth_row(template_admin $tpl,string $page,string $function,string $item,int $enabled):string{
    $enc=urlencode($item);
    $isMeta=hacluster_meta_row("noauth",$item);
    $toggle=$tpl->icon_check($enabled,"Loadjs('$page?enable-js=$enc&function=$function')",null,hacluster_meta_sec($isMeta,null));
    $del_js="Loadjs('$page?delete-js=$enc&function=$function')";
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico".($enabled?"":" awc-ico-off")."'><i class='".ico_computer."'></i></span>";
    $h.="<span><span class='awc-name".($enabled?"":" awc-name-off")."'>".htmlspecialchars($item).hacluster_meta_badge("noauth",$item)."</span>";
    $h.="<div class='awc-meta'>".($enabled?"{active2}":"{inactive2}")."</div>";
    $h.="</span></div></td>";
    $h.="<td class='center'>$toggle</td>";
    $h.="<td><div class='awc-acts'>";
    if(!$isMeta){$h.="<a class='awc-act awc-act-danger' title='{delete}' href=\"javascript:blur();\" onclick=\"$del_js\"><i class='fas fa-trash-can'></i></a>";}
    $h.="</div></td>";
    $h.="</tr>";
    return $h;
}
