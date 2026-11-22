<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.rdpproxy.inc");
include_once(dirname(__FILE__)."/ressources/class.rdpproxy.meta.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["members-start"])){members_start();exit;}
if(isset($_GET["members-table"])){members_table();exit;}
if(isset($_GET["member-ad-js"])){member_ad_js();exit;}
if(isset($_GET["member-ad-popup"])){member_ad_popup();exit;}
if(isset($_GET["member-js"])){member_js2();exit;}
if(isset($_GET["member-popup"])){member_popup();exit;}
if(isset($_GET["member-delete"])){member_delete();exit;}
if(isset($_POST["member-delete"])){member_delete_perform();exit;}
if(isset($_POST["member-id"])){member_save();exit;}
if(isset($_POST["member-ad-id"])){member_ad_save();exit;}
if(isset($_GET["groups-start"])){groups_start();exit;}
if(isset($_GET["groups-table"])){groups_table();exit;}
if(isset($_GET["group-js"])){group_js();exit;}
if(isset($_GET["group-param"])){group_params();exit;}
if(isset($_GET["target-webclip"])){target_webclip();exit;}
if(isset($_GET["target-webclip-set"])){target_webclip_set();exit;}
if(isset($_POST["group-params"])){group_params_save();exit;}
if(isset($_GET["group-enable"])){group_enable();exit;}
if(isset($_GET["group-status"])){echo groups_status($_GET["group-status"]);exit;}
if(isset($_GET["group-delete"])){echo group_delete();exit;}
if(isset($_POST["group-delete"])){echo group_delete_perform();exit;}

if(isset($_GET["group-tabs"])){group_tabs();exit;}
if(isset($_GET["groups-members-start"])){group_members_start();exit;}
if(isset($_GET["group-members-table"])){group_members_table();exit;}

if(isset($_GET["target-status"])){target_status();exit;}
if(isset($_GET["target-enable"])){target_enable();exit;}
if(isset($_GET["target-delete"])){target_delete();exit;}
if(isset($_POST["target-delete"])){target_delete_perform();exit;}
if(isset($_GET["targets-table"])){targets_table();exit;}
if(isset($_GET["targets-start"])){targets_start();exit;}
if(isset($_GET["target-ad-js"])){targets_ad_js();exit;}
if(isset($_GET["target-js"])){targets_js();exit;}
if(isset($_GET["target-popup"])){targets_popup();exit;}
if(isset($_GET["target-ad-popup"])){targets_ad_popup();exit;}
if(isset($_POST["target-id"])){targets_save();exit;}
if(isset($_POST["target-ad-id"])){targets_ad_save();exit;}
if(isset($_GET["target-deploy-js"])){target_deploy_js();exit;}
if(isset($_GET["target-deploy-popup"])){target_deploy_popup();exit;}
if(isset($_POST["target-deploy-id"])){target_deploy_save();exit;}
if(isset($_GET["target-forget-hostkey"])){target_forget_hostkey_ask();exit;}
if(isset($_POST["target-forget-hostkey"])){target_forget_hostkey_perform();exit;}
if(isset($_GET["member-link"])){member_link_js();exit;}
if(isset($_GET["member-link-table"])){member_link_table();exit;}
if(isset($_GET["member-link-perform"])){member_link_perform();exit;}
if(isset($_GET["member-unlink"])){member_unlink();exit;}


if(isset($_GET["groups-targets-start"])){group_targets_start();exit;}
if(isset($_GET["groups-targets-table"])){group_targets_table();exit;}
if(isset($_GET["target-link"])){target_link_js();exit;}
if(isset($_GET["target-link-table"])){target_link_table();exit;}
if(isset($_GET["target-link-perform"])){target_link_perform();exit;}
if(isset($_GET["target-unlink"])){target_unlink();exit;}

if(isset($_GET["groups-network-start"])){group_network_start();exit;}
if(isset($_GET["groups-network-table"])){group_network_table();exit;}
if(isset($_GET["network-unlink"])){group_network_delete();exit;}
if(isset($_GET["network-link"])){group_network_link_js();exit;}
if(isset($_GET["network-link-popup"])){group_network_link_popup();exit;}
if(isset($_POST["network-link"])){group_network_link_save();exit;}

if(isset($_POST["group-time-save"])){group_time_save();exit;}
if(isset($_GET["group-time-start"])){group_time_start();exit;}
if(isset($_GET["group-time-table"])){group_time_table();exit;}
if(isset($_GET["group-time-popup"])){group_time_popup();exit;}
if(isset($_GET["time-link"])){group_time_link_js();exit;}
if(isset($_GET["time-unlink"])){group_time_unlink_js();exit;}
if(isset($_GET["webproxies-start"])){rdpx_webproxies_start();exit;}
if(isset($_GET["webproxies-table"])){rdpx_webproxies_table();exit;}
if(isset($_GET["webproxy-js"])){rdpx_webproxy_js();exit;}
if(isset($_GET["webproxy-popup"])){rdpx_webproxy_popup();exit;}
if(isset($_POST["webproxy-id"])){rdpx_webproxy_save();exit;}
if(isset($_GET["webproxy-delete"])){rdpx_webproxy_delete_ask();exit;}
if(isset($_POST["webproxy-delete"])){rdpx_webproxy_delete_perform();exit;}
if(isset($_GET["webproxy-enable"])){rdpx_webproxy_enable();exit;}
// Fichiers PAC des cibles WEB (onglet des proxys amont)
if(isset($_GET["webpacs-table"])){rdpx_webpacs_table();exit;}
if(isset($_GET["webpac-js"])){rdpx_webpac_js();exit;}
if(isset($_GET["webpac-popup"])){rdpx_webpac_popup();exit;}
if(isset($_POST["webpac-id"])){rdpx_webpac_save();exit;}
if(isset($_GET["webpac-view-js"])){rdpx_webpac_view_js();exit;}
if(isset($_GET["webpac-view-popup"])){rdpx_webpac_view_popup();exit;}
if(isset($_GET["webpac-test-js"])){rdpx_webpac_test_js();exit;}
if(isset($_GET["webpac-test-popup"])){rdpx_webpac_test_popup();exit;}
if(isset($_GET["webpac-test-run"])){rdpx_webpac_test_run();exit;}
if(isset($_GET["webpac-delete"])){rdpx_webpac_delete_ask();exit;}
if(isset($_POST["webpac-delete"])){rdpx_webpac_delete_perform();exit;}
if(isset($_GET["patterns-start"])){rdpx_patterns_start();exit;}
if(isset($_GET["patterns-table"])){rdpx_patterns_table();exit;}
if(isset($_GET["pattern-js"])){rdpx_pattern_js();exit;}
if(isset($_GET["pattern-popup"])){rdpx_pattern_popup();exit;}
if(isset($_POST["pattern-id"])){rdpx_pattern_save();exit;}
if(isset($_GET["pattern-delete"])){rdpx_pattern_delete_ask();exit;}
if(isset($_POST["pattern-delete"])){rdpx_pattern_delete_perform();exit;}
if(isset($_GET["pattern-enable"])){rdpx_pattern_enable();exit;}
if(isset($_GET["pattern-global-js"])){rdpx_pattern_global_js();exit;}

page();


function group_tabs(){
    $ID=intval($_GET["group-tabs"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $p=rdpproxy_policy($ID,$error);
    $title=isset($p["name"])?$p["name"]:"";

    $array["{$title}"]="$page?group-param=$ID";
    $array["{rdpproxy_members}"]="$page?groups-members-start=$ID";
    $array["{targets}"]="$page?groups-targets-start=$ID";
    $array["{networks}"]="$page?groups-network-start=$ID";
    $array["{time}/{schedule}"]="$page?group-time-start=$ID";
    if($ID>0){$array["{rdpproxy_patterns}"]="$page?patterns-start=$ID";}
    echo $tpl->_ENGINE_parse_body(rdpproxy_meta_banner());
    echo $tpl->tabs_default($array);
}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{policies}"]="$page?groups-start=yes";
    $array["{rdpproxy_members}"]="$page?members-start=yes";
    $array["{targets}"]="$page?targets-start=yes";
    $array["{rdpproxy_webproxies}"]="$page?webproxies-start=yes";
    // Agents de tunnel sortants (lot 2) : seulement si authhook >= 1.9.0 les sert.
    if(rdpproxy_tunnel_supported()){$array["{rdpproxy_tunnel_agents}"]="fw.rdpproxy.tunnel.php?tabs=yes";}
    // Verrou Artica Meta : bandeau au-dessus des onglets (les cibles tunnel restent modifiables).
    echo $tpl->_ENGINE_parse_body(rdpproxy_meta_banner());
    echo $tpl->tabs_default($array);
}

// rdpc_html : valeur du referentiel rendue en HTML par jquery-confirm (echappee, accolades neutralisees).
function rdpc_html($v){
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES));
}

function member_delete(){
    $ID=intval($_GET["member-delete"]);
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $page=CurrentPageName();
    $tpl=new template_admin();
    $error="";
    $m=rdpproxy_member($ID,$error);
    $title=isset($m["username"])?rdpc_html(rdpproxy_cn($m["username"])):"";
    $tpl->js_confirm_delete("{delete_user} $title","member-delete",$ID,"$('#$md').remove()");

}
function target_delete(){
    $ID=intval($_GET["target-delete"]);
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $page=CurrentPageName();
    $tpl=new template_admin();
    $error="";
    $tg=rdpproxy_target($ID,$error);
    $target_login=rdpc_html($tg["target_login"] ?? "");
    $proto_dest=rdpc_html($tg["proto_dest"] ?? "");
    $target_host=rdpc_html($tg["target_host"] ?? "");
    $target_device=rdpc_html($tg["target_device"] ?? "");
    $target_port=intval($tg["target_port"] ?? 0);
    $what="{$target_login} $proto_dest/$target_port $target_host $target_device";
    if(strtoupper((string)($tg["proto_dest"] ?? ""))=="WEB"){
        $what=rdpc_html($tg["alias"] ?? "")." WEB ".rdpc_html($tg["web_url"] ?? "");
    }
    $tpl->js_confirm_delete($what,"target-delete",$ID,"$('#$md').remove()");

}
function target_delete_perform(){
    $ID=intval($_POST["target-delete"]);
    $name=rdpc_name("target",$ID);
    $err=rdpproxy_target_delete($ID);
    if($err!=""){echo $err;return;}
    admin_tracks("Deleted RDS Proxy target $name (#$ID)");
}

function member_delete_perform(){
    $ID=intval($_POST["member-delete"]);
    $name=rdpc_name("member",$ID);
    $err=rdpproxy_member_delete($ID);
    if($err!=""){echo $err;return;}
    admin_tracks("Deleted RDS Proxy member $name (#$ID)");
}

// rdpc_name : libelle d'un objet du referentiel pour le journal d'administration
// (lu AVANT une suppression). Jamais de secret : nom, alias ou nom de politique.
function rdpc_name($kind,$ID){
    $error="";
    if($kind=="member"){$o=rdpproxy_member($ID,$error);return isset($o["username"])?rdpproxy_cn($o["username"]):"#$ID";}
    if($kind=="target"){$o=rdpproxy_target($ID,$error);return isset($o["alias"])?rdpproxy_cn($o["alias"]):"#$ID";}
    $o=rdpproxy_policy($ID,$error);
    return isset($o["name"])?$o["name"]:"#$ID";
}

function group_time_link_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["time-link"]);
    $p=rdpproxy_policy($gpid,$error);
    $title=isset($p["name"])?$p["name"]:"";
    $tpl->js_dialog2("$title","$page?group-time-popup=$gpid",650);

}
function group_time_unlink_js(){
    $tpl=new template_admin();
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $gpid=intval($_GET["gpid"]);
    $err=rdpproxy_policy_child_delete($gpid,"schedules",intval($_GET["time-unlink"]));
    header("content-type: application/x-javascript");
    if($err!=""){$tpl->js_error($err);return;}
    admin_tracks("Removed a time slot from RDS Proxy policy ".rdpc_name("policy",$gpid));
    echo "$('#$md').remove();\n";
}

// rdpc_post : valeur d'un champ de formulaire, decodee. Les champs de
// form_outside() partent en escape(encodeURIComponent(v)) (XHRConnection.js) :
// apres le decodage PHP, $_POST porte encore la forme encodeURIComponent, que
// CLEAN_POST/CLEAN_POST_XSS defaisaient par url_decode_special_tool(). Le
// daemon recoit la valeur reelle (placeholders cote Go : aucun echappement SQL).
// $mode : "trim" (CLEAN_POST), "xss" (CLEAN_POST_XSS), "raw" (mot de passe).
function rdpc_post($key,$mode="trim"):string{
    $v=isset($_POST[$key])?url_decode_special_tool((string)$_POST[$key]):"";
    if($mode=="raw"){return $v;}
    if($mode=="xss"){$tpl=new template_admin();return $tpl->CLEAN_BAD_XSS($v);}
    return trim($v);
}

function group_time_save():bool{
    $tpl=new template_admin();
    $gpid=intval($_POST["group-time-save"]);
    $_POST["tday"]=rdpc_post("tday");
    $_POST["thour1"]=rdpc_post("thour1");
    $_POST["thour2"]=rdpc_post("thour2");
    $err=rdpproxy_policy_child_add($gpid,"schedules",array(
        "day"=>$_POST["tday"],"from"=>$_POST["thour1"],"to"=>$_POST["thour2"]));
    if($err!=""){echo $tpl->_ENGINE_parse_body("{wrong_time} ").htmlspecialchars($err,ENT_QUOTES);return false;}
    return admin_tracks_post("Saving Proxy RDS time rule");
}

function group_time_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["group-time-popup"]);

    $tpl->field_hidden("group-time-save",$gpid);
    $array_day_of_week=array(
        "6"=>"{Sunday}",
        "0"=>"{Monday}",
        "1"=>"{Tuesday}",
        "2"=>"{Wednesday}",
        "3"=>"{Thursday}",
        "4"=>"{Friday}",
        "5"=>"{Saturday}");

    $form[]=$tpl->field_array_hash($array_day_of_week,"tday","{day}",0);
    $form[]=$tpl->field_clock("thour1","{from_time}","07:30:00");
    $form[]=$tpl->field_clock("thour2","{to_time}","23:59:00");

    $bt="{add}";
    $js="dialogInstance2.close();LoadAjax('rdpproxy-time-table-$gpid','$page?group-time-table=$gpid');";
    rdpproxy_meta_lock_form($tpl);
    echo $tpl->form_outside("{add_working_period}",$form,null,$bt,$js,"AllowAddUsers");

    return true;

}
function targets_ad_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["target-ad-js"]);
    if($ID==0){
        $title="{new_target} {active_directory_group}";
    }else{
        $tg=rdpproxy_target($ID,$error);
        $title=isset($tg["alias"])?$tg["alias"]:"";
    }

   return $tpl->js_dialog1($title,"$page?target-ad-popup=$ID",750);

}

function targets_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["target-js"]);
    if($ID==0){
        $title="{new_target}";
    }else{
        $tg=rdpproxy_target($ID,$error);
        $title=isset($tg["alias"])?$tg["alias"]:"";
    }

    return $tpl->js_dialog1($title,"$page?target-popup=$ID",750);

}
function group_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["group-js"]);
    if($ID==0) {
        $title = "{new_policy}";
        $tpl->js_dialog1($title, "$page?group-param=$ID", 650);
        return true;
    }

    $p=rdpproxy_policy($ID,$error);
    $title=isset($p["name"])?$p["name"]:"";
   return $tpl->js_dialog1($title,"$page?group-tabs=$ID",900);


}
function member_link_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["member-link"]);
    $p=rdpproxy_policy($ID,$error);
    $title=isset($p["name"])?$p["name"]:"";
    $tpl->js_dialog2($title,"$page?member-link-table=$ID",400);

}
function target_link_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["target-link"]);
    $p=rdpproxy_policy($ID,$error);
    $title=isset($p["name"])?$p["name"]:"";
    $tpl->js_dialog2($title,"$page?target-link-table=$ID",800);
}
function group_network_link_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["network-link"]);
    $p=rdpproxy_policy($ID,$error);
    $title=isset($p["name"])?$p["name"]:"";
    $tpl->js_dialog2($title,"$page?network-link-popup=$ID",550);

}

function group_network_link_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["network-link-popup"]);
    $title = "{new_policy}";
    $bt = "{add}";
    $js = "dialogInstance2.close();LoadAjax('rdpproxy-network-table-$ID','$page?groups-network-table=$ID');";

    $tpl->field_hidden("network-link",$ID);
    $form[]= $tpl->field_text("network","{network}","0.0.0.0/0",true);

    rdpproxy_meta_lock_form($tpl);
    echo $tpl->form_outside($title,$form,"{pdns_network_item_add}",$bt,$js,"AllowAddUsers");

}
function group_network_link_save(){
    $gpid=intval($_POST["network-link"]);
    $err=rdpproxy_policy_child_add($gpid,"networks",array("pattern"=>rdpc_post("network")));
    if($err!=""){echo $err;return;}
    admin_tracks_post("Added a network to RDS Proxy policy ".rdpc_name("policy",$gpid));
}

// Icone et ligne secondaire d'une cible dans les listes de politiques : une cible WEB montre son
// adresse (l'hote et le port deduits ne disent rien de l'application).
function rdpc_target_label($ligne,&$alias):array{
    $alias=(string)($ligne["alias"] ?? "");
    if(strtoupper((string)($ligne["proto_dest"] ?? ""))=="WEB"){
        $url=(string)($ligne["web_url"] ?? "");
        $short=strlen($url)>70?substr($url,0,70)."\u{2026}":$url;
        return array("fas fa-globe",$short);
    }
    $host=(string)($ligne["target_host"] ?? "");
    $ico="fas fa-server";
    if(preg_match("#CN=(.*?),#i",$alias,$re)){
        $alias=$re[1];
        $ico="fa fa-users";
        $host=$alias;
    }
    return array($ico,$host.":".($ligne["target_port"] ?? "")." (".($ligne["target_device"] ?? "").")");
}

function target_link_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=md5(time().rand(0,65595));
    $gpid=intval($_GET["target-link-table"]);
    $ALREADY=array();
    $p=rdpproxy_policy($gpid,$error);
    if($error!=""){echo $tpl->div_error($error);return;}
    $linked=isset($p["targets"])&&is_array($p["targets"])?$p["targets"]:array();
    foreach ($linked as $ligne){
        $ALREADY[intval($ligne["id"])]=true;
    }

    $rows=array();
    $results=rdpproxy_targets($error);
    if($error!=""){echo $tpl->div_error($error);return;}
    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["id"]);
        if(isset($ALREADY[$ID])){continue;}
        $md=md5(serialize($ligne));
        list($ico,$metatxt)=rdpc_target_label($ligne,$alias);
        $meta=htmlspecialchars($metatxt,ENT_QUOTES);
        $r="<tr id='$md'>";
        $r.="<td>".rdpc_ident($ico,htmlspecialchars((string)$alias,ENT_QUOTES),$meta)."</td>";
        $r.="<td class='awc-nowrap'><div class='awc-acts' style='opacity:1'>"
            .rdpc_link_badge("Loadjs('$page?target-link-perform=$ID&md=$md&gpid=$gpid')")."</div></td>";
        $r.="</tr>";
        $rows[]=$r;
    }

    $html[]=awc_search_bar("search-$t");
    $html[]=rdpc_table_open($t,array("<th>{targets}</th>","<th class='awc-right'>&nbsp;</th>"));
    if(count($rows)==0){$html[]=rdpc_empty(2,"{no_item}");}else{$html[]=@implode("\n",$rows);}
    $html[]=rdpc_table_close($t,count($rows));
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).rdpc_scripts($tpl,$t,true);
}

function member_link_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=md5(time().rand(0,65595));
    $gpid=intval($_GET["member-link-table"]);
    $ALREADY=array();
    $p=rdpproxy_policy($gpid,$error);
    if($error!=""){echo $tpl->div_error($error);return;}
    $linked=isset($p["members"])&&is_array($p["members"])?$p["members"]:array();
    foreach ($linked as $ligne){
        $ALREADY[intval($ligne["id"])]=true;
    }

    $rows=array();
    $results=rdpproxy_members($error);
    if($error!=""){echo $tpl->div_error($error);return;}
    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["id"]);
        if(isset($ALREADY[$ID])){continue;}
        $md=md5(serialize($ligne));
        $username=$ligne["username"];
        $ico="fa fa-user";
        if(preg_match("#CN=(.*?),#i",$username,$re)){
            $username=$re[1];
            $ico="fa fa-users";
        }
        $r="<tr id='$md'>";
        $r.="<td>".rdpc_ident($ico,htmlspecialchars((string)$username,ENT_QUOTES))."</td>";
        $r.="<td class='awc-nowrap'><div class='awc-acts' style='opacity:1'>"
            .rdpc_link_badge("Loadjs('$page?member-link-perform=$ID&md=$md&gpid=$gpid')")."</div></td>";
        $r.="</tr>";
        $rows[]=$r;
    }

    $html[]=awc_search_bar("search-$t");
    $html[]=rdpc_table_open($t,array("<th>{rdpproxy_members}</th>","<th class='awc-right'>&nbsp;</th>"));
    if(count($rows)==0){$html[]=rdpc_empty(2,"{no_item}");}else{$html[]=@implode("\n",$rows);}
    $html[]=rdpc_table_close($t,count($rows));
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).rdpc_scripts($tpl,$t,true);
}

function target_link_perform(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $gpid=intval($_GET["gpid"]);
    $tid=intval($_GET["target-link-perform"]);
    $err=rdpproxy_policy_child_add($gpid,"targets",array("id"=>$tid));
    header("content-type: application/x-javascript");
    if($err!=""){$tpl->js_error($err);return;}
    admin_tracks("Linked RDS Proxy target ".rdpc_name("target",$tid)." to policy ".rdpc_name("policy",$gpid));
    echo "$('#$md').remove();\n";
    echo "LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');\n";
    echo "LoadAjax('rdpproxy-targets-table-$gpid','$page?groups-targets-table=$gpid');\n";
}
function member_link_perform(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $gpid=intval($_GET["gpid"]);
    $mid=intval($_GET["member-link-perform"]);
    $err=rdpproxy_policy_child_add($gpid,"members",array("id"=>$mid));
    header("content-type: application/x-javascript");
    if($err!=""){$tpl->js_error($err);return;}
    admin_tracks("Linked RDS Proxy member ".rdpc_name("member",$mid)." to policy ".rdpc_name("policy",$gpid));
    echo "$('#$md').remove();\n";
    echo "LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');\n";
    echo "LoadAjax('rdpproxy-members-table-$gpid','$page?group-members-table=$gpid');";
}
function member_unlink(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $gpid=intval($_GET["gpid"]);
    $mid=intval($_GET["member-unlink"]);
    $err=rdpproxy_policy_child_delete($gpid,"members",$mid);
    header("content-type: application/x-javascript");
    if($err!=""){$tpl->js_error($err);return;}
    admin_tracks("Unlinked RDS Proxy member ".rdpc_name("member",$mid)." from policy ".rdpc_name("policy",$gpid));
    echo "LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');\n";
    echo "$('#$md').remove();\n";
}
function target_unlink(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $gpid=intval($_GET["gpid"]);
    $tid=intval($_GET["target-unlink"]);
    $err=rdpproxy_policy_child_delete($gpid,"targets",$tid);
    header("content-type: application/x-javascript");
    if($err!=""){$tpl->js_error($err);return;}
    admin_tracks("Unlinked RDS Proxy target ".rdpc_name("target",$tid)." from policy ".rdpc_name("policy",$gpid));
    echo "LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');\n";
    echo "$('#$md').remove();\n";
}

function group_params(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["group-param"]);
    $p=array();
    $ligne=array("groupname"=>"","description"=>"","user_rec"=>0,"mfa_required"=>0,"session_time"=>0);
    if($ID>0){
        $p=rdpproxy_policy($ID,$error);
        if($error!=""){echo $tpl->div_error($error);return;}
        $ligne=array("groupname"=>$p["name"],"description"=>$p["description"],
            "user_rec"=>!empty($p["recording"])?1:0,"mfa_required"=>!empty($p["mfa_required"])?1:0,"session_time"=>intval($p["session_time"]));
    }
    if($ID==0) {
        $title = "{new_policy}";
        $bt = "{add}";
        $js = "dialogInstance1.close();LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');";
    }else{
        $title="";
        $bt="{apply}";
        $js="LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');";
    }

    $CACHE_AGES[0]="{never}";
    $CACHE_AGES[5]="5 {minutes}";
    $CACHE_AGES[10]="10 {minutes}";
    $CACHE_AGES[15]="15 {minutes}";
    $CACHE_AGES[30]="30 {minutes}";
    $CACHE_AGES[60]="1 {hour}";
    $CACHE_AGES[120]="2 {hours}";
    $CACHE_AGES[180]="3 {hours}";
    $CACHE_AGES[240]="4 {hours}";
    $CACHE_AGES[360]="6 {hours}";
    $CACHE_AGES[720]="12 {hours}";
    $CACHE_AGES[1440]="24 {hours}";


    $tpl->field_hidden("group-params",$ID);
    $form[]= $tpl->field_text("groupname","{rulename}",$ligne["groupname"],true);
    $form[]= $tpl->field_text("description","{description}",$ligne["description"],true);
    // ReDemPtion sans FFmpeg : la case reste visible (et cochee si elle l'etait) mais grisee.
    $videoOK=rdpproxy_video_supported();
    $form[]= $tpl->field_checkbox("user_rec","{video_recording}",$ligne["user_rec"],false,null,!$videoOK);
    if(!$videoOK){
        $form[]=$tpl->field_section("{rdpproxy_video_unavailable}","{rdpproxy_video_unavailable_explain}",true);
    }
    $sshOK=rdpproxy_ssh_supported();
    if($sshOK){
        // authhook force l'enregistrement de toute session SSH (is_rec), case cochee ou non.
        $form[]=$tpl->field_section("{rdpproxy_ssh_always_recorded}","{rdpproxy_ssh_always_recorded_explain2}");
    }
    $form[]= $tpl->field_checkbox("mfa_required","{require_second_factor}",$ligne["mfa_required"]);
    // authhook < 1.3.0 (ou version inconnue) ignore mfa_required : le dire à côté de la case.
    $hookVer=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPPROXY_AUTH_VERSION"));
    if($hookVer==="" || version_compare($hookVer,"1.3.0","<")){
        $shown=$hookVer!==""?htmlspecialchars($hookVer):$tpl->_ENGINE_parse_body("{unknown}");
        $form[]=rdpproxy_warning_row($tpl,sprintf($tpl->_ENGINE_parse_body("{rdpproxy_mfa_needs_hook}"),$shown));
    }
    // NLA client (port Kerberos) : MFA impose sauf si la politique fait confiance au ticket ET n'est
    // ouverte qu'a des reseaux sources choisis (decision prise par authhook >= 1.12.0, a chaque session).
    $form[]=$tpl->field_checkbox("nla_trust_kerberos","{rdpproxy_nla_trust_kerberos}",!empty($p["nla_trust_kerberos"])?1:0,false,"{rdpproxy_nla_trust_kerberos_explain}");
    if(!empty($p["nla_trust_kerberos"]) && !rdpc_policy_networks_restricted($p)){
        $form[]=rdpproxy_warning_row($tpl,$tpl->_ENGINE_parse_body("{rdpproxy_nla_trust_no_network}"));
    }
    if($hookVer==="" || version_compare($hookVer,"1.12.0","<")){
        $shown=$hookVer!==""?htmlspecialchars($hookVer):$tpl->_ENGINE_parse_body("{unknown}");
        $form[]=rdpproxy_warning_row($tpl,sprintf($tpl->_ENGINE_parse_body("{rdpproxy_nla_trust_needs_hook}"),$shown));
    }
    $form[]=$tpl->field_array_hash($CACHE_AGES,"session_time","{session_time}",$ligne["session_time"]);
    // Confinement reseau des cibles WEB : le Chromium des sessions de cette politique ne voit que
    // l'espace de noms reseau Artica choisi (sa carte, son DNS). 0 = aucun.
    $form[]=rdpc_policy_netns_field($tpl,intval($p["web_netns"] ?? 0));
    // Sortie des cibles WEB : choix exclusif aucun (directe) / proxy amont / fichier PAC, et la
    // regle d'echec du PAC (visible seulement avec un PAC).
    $form[]=rdpc_policy_route_fields($tpl,intval($p["web_proxy"] ?? 0),intval($p["web_pac"] ?? 0),!empty($p["web_pac_fail_direct"]));
    // Droits de session (E1). Nouvelle stratégie : tout autorisé.
    $rights=isset($p["rights"])&&is_array($p["rights"])?$p["rights"]:array();
    // Presse-papiers des applications WEB : sous-section propre, separee des droits RDP (qui ne
    // les commandent pas). Fermes par defaut : cle absente = 0. Moteur sans webclip1 ou authhook
    // < 1.8.1 : avertissement, mais les cases restent modifiables (reglage pret pour la mise a jour).
    $form[]=$tpl->field_section("{rdpproxy_webclip_section}","{rdpproxy_webclip_explain}");
    if(!rdpproxy_web_clipboard_state()["supported"]){
        $form[]=rdpproxy_warning_row($tpl,"{rdpproxy_webclip_unsupported}");
    }
    foreach(rdpproxy_web_clipboard_rights() as $col=>$label){
        $val=!empty($rights[$col])?1:0;
        $form[]=$tpl->field_checkbox($col,$label,$val);
    }
    $form=array_merge($form,rdpc_policy_web_download_fields($tpl,$p,$ID));
    $form=array_merge($form,rdpc_policy_web_keep_fields($tpl,$p));
    // Sans relais SSH, le texte d'origine (« cibles RDP uniquement ») ; avec, une variante qui
    // renvoie a l'encadre « Cibles SSH » affiche sous les droits.
    $form[]=$tpl->field_section("{rdpproxy_session_rights}",$sshOK?"{rdpproxy_session_rights_explain_ssh}":"{rdpproxy_session_rights_explain}");
    foreach(rdpproxy_session_rights() as $col=>$label){
        $val=isset($rights[$col])?(!empty($rights[$col])?1:0):1;
        $form[]=$tpl->field_checkbox($col,$label,$val);
    }
    if($sshOK){
        // Module SSH : seuls les droits du presse-papiers s'appliquent. Les fichiers du
        // presse-papiers n'y ont d'effet qu'avec un moteur qui declare sshfiles1 (lot 3) :
        // depot = copie vers la cible + fichiers, recuperation = copie depuis la cible + fichiers.
        // Trace dans les evenements d'acces promise seulement si authhook >= 1.6.1 les ecrit (R19).
        $filesExplain="{rdpproxy_rights_ssh_explain}";
        if(rdpproxy_ssh_files_supported()){
            $filesExplain=rdpproxy_ssh_file_events()?"{rdpproxy_rights_ssh_files_explain}":"{rdpproxy_rights_ssh_files_explain_archive}";
            // Revue finale m1 (R21) : sous une porte de commande (motif refuse ou kill), le moteur
            // refuse un depot de premier niveau dont le nom commence par un point (.bashrc, .ssh).
            // Le [br] des valeurs n est converti que DANS un jeton : ici un <br> litteral.
            $filesExplain.="<br>{rdpproxy_rights_ssh_dotfiles}";
        }
        $form[]=$tpl->field_section("{rdpproxy_rights_ssh}",$filesExplain);
    }
    if($hookVer==="" || version_compare($hookVer,"1.4.0","<")){
        $shown=$hookVer!==""?htmlspecialchars($hookVer):$tpl->_ENGINE_parse_body("{unknown}");
        $form[]=rdpproxy_warning_row($tpl,sprintf($tpl->_ENGINE_parse_body("{rdpproxy_rights_need_hook}"),$shown));
    }
    rdpproxy_meta_lock_form($tpl);
    echo $tpl->form_outside($title,$form,null,$bt,$js,"AllowAddUsers");
    echo rdpc_policy_route_js($tpl);


}

// Telechargements des cibles WEB (spec 2026-11-11) : case, taille maximale, types autorises.
// Fermes par defaut (daemon : 0, 50 Mo, pdf,csv,xlsx,xls,docx,txt). Enregistres avec la politique
// (form_outside), jamais a la volee. Avertissements : composant trop ancien (cause du daemon),
// ClamAV absent (pas d'analyse), limite d'analyse ClamAV plus basse que la taille choisie,
// enregistrement de la session force par authhook quand la politique n'enregistre pas.
function rdpc_policy_web_download_fields($tpl,$p,$ID):array{
    $allow=!empty($p["allow_web_download"])?1:0;
    $max=intval($p["web_download_max_mb"] ?? 0);
    if($max<=0){$max=50;}
    $ext=array_key_exists("web_download_ext",$p)?(string)$p["web_download_ext"]:"pdf,csv,xlsx,xls,docx,txt";
    if(intval($ID)==0){$ext="pdf,csv,xlsx,xls,docx,txt";}
    $st=rdpproxy_web_download_state();
    $f=array();
    $f[]=$tpl->field_section("{rdpproxy_web_download_section}","{rdpproxy_web_download_explain}");
    if(!$st["supported"]){
        $f[]=rdpproxy_warning_row($tpl,"{rdpproxy_web_download_unsupported_".$st["cause"]."}");
    }
    $f[]=$tpl->field_checkbox("allow_web_download","{rdpproxy_web_download}",$allow);
    $f[]=$tpl->field_numeric("web_download_max_mb","{rdpproxy_web_download_max_mb}",$max);
    $f[]=$tpl->field_text("web_download_ext","{rdpproxy_web_download_ext}",$ext,false,"{rdpproxy_web_download_ext_explain}");
    if(!$st["av"]){
        $f[]=rdpproxy_warning_row($tpl,"{rdpproxy_web_download_no_av}");
    }elseif($st["av_max_mb"]>0 && $st["av_max_mb"]<$max){
        $f[]=rdpproxy_warning_row($tpl,sprintf($tpl->_ENGINE_parse_body("{rdpproxy_web_download_av_cap}"),intval($st["av_max_mb"])));
    }
    if(empty($p["recording"])){
        $f[]=rdpproxy_warning_row($tpl,"{rdpproxy_web_download_recorded}");
    }
    return $f;
}

// Sessions Web gardees apres une deconnexion (spec 2026-11-12) : minutes, 0 = fermeture a la
// deconnexion (defaut). Enregistre avec la politique ; le daemon refuse hors 0-480 (400
// web_keep_minutes). Avertissements : composant trop ancien (cause du daemon), navigateur
// connecte a l'application pendant le delai, politique modifiee = session gardee inutilisable.
function rdpc_policy_web_keep_fields($tpl,$p):array{
    $min=intval($p["web_keep_minutes"] ?? 0);
    if($min<0){$min=0;}
    $st=rdpproxy_web_keep_state();
    $f=array();
    $f[]=$tpl->field_section("{rdpproxy_web_keep_section}","{rdpproxy_web_keep_explain}");
    if(!$st["supported"]){
        $f[]=rdpproxy_warning_row($tpl,"{rdpproxy_web_keep_unsupported_".$st["cause"]."}");
    }
    $f[]=$tpl->field_numeric("web_keep_minutes","{rdpproxy_web_keep_minutes}",$min,"{rdpproxy_web_keep_minutes_explain}");
    $f[]=rdpproxy_warning_row($tpl,"{rdpproxy_web_keep_logged_in}");
    return $f;
}

// Liste « Confinement reseau des cibles web » : Aucun + espaces de noms Artica (GET
// /rdpproxy/webview namespaces). Un espace desactive ou absent reste choisissable mais marque :
// une session dont l'espace n'existe pas a la connexion est refusee par le bastion.
function rdpc_policy_netns_field($tpl,$current):string{
    $hash=array(0=>"{none}");
    $err="";
    $w=rdpproxy_webview($err);
    if($err=="" && isset($w["namespaces"]) && is_array($w["namespaces"])){
        foreach($w["namespaces"] as $ns){
            if(!is_array($ns)){continue;}
            $id=intval($ns["id"] ?? 0);
            if($id<=0){continue;}
            $label=(string)($ns["name"] ?? "");
            $nsn=(string)($ns["ns_name"] ?? "");
            if($label==""){$label=$nsn;}elseif($nsn!=""){$label.=" ($nsn)";}
            if(empty($ns["enabled"])){$label.=" - {disabled}";}
            if(empty($ns["exists"])){$label.=" - {rdpproxy_netns_missing}";}
            $hash[$id]=$label;
        }
    }
    // valeur enregistree absente du catalogue : gardee, sinon la liste enverrait « Aucun »
    if($current>0 && !isset($hash[$current])){$hash[$current]="#$current - {rdpproxy_netns_missing}";}
    return $tpl->field_array_hash($hash,"web_netns","{rdpproxy_policy_web_netns}",$current,false,"{rdpproxy_policy_web_netns_explain}");
}

// Sortie des cibles WEB : UNE liste pour un choix exclusif (le daemon refuse proxy + PAC,
// proxy_and_pac_exclusive) : "0" = sortie directe, "p<id>" = proxy amont, "c<id>" = fichier PAC.
// Un proxy desactive reste choisissable mais marque : une session qui le designe est refusee,
// jamais sortie directe. Libelles venus du daemon : echappes, accolades neutralisees.
function rdpc_policy_route_fields($tpl,$proxy,$pac,$failDirect):string{
    $hash=array("0"=>"{rdpproxy_webproxy_direct}");
    $err="";
    foreach(rdpproxy_webproxies($err) as $px){
        if(!is_array($px)){continue;}
        $id=intval($px["id"] ?? 0);
        if($id<=0){continue;}
        $raw=(string)($px["name"] ?? "")." (".(string)($px["host"] ?? "").":".intval($px["port"] ?? 0).")";
        $hash["p$id"]=rdpc_option_label($tpl,"{rdpproxy_webproxy}: ",$raw,empty($px["enabled"])?" - {disabled}":"");
    }
    $err="";
    foreach(rdpproxy_webpacs($err) as $pc){
        if(!is_array($pc)){continue;}
        $id=intval($pc["id"] ?? 0);
        if($id<=0){continue;}
        // PAC en echec : marque courte, jamais retiree (D3) ; seul le nom est raccourci
        $hash["c$id"]=rdpc_option_label($tpl,"{rdpproxy_webpac}: ",(string)($pc["name"] ?? ""),
            trim((string)($pc["last_error"] ?? ""))!==""?" ⚠ {rdpproxy_webpac_has_error_short}":"");
    }
    $cur="0";
    if($pac>0){$cur="c$pac";}elseif($proxy>0){$cur="p$proxy";}
    // valeur enregistree absente du catalogue : gardee, sinon la liste enverrait « directe »
    if($cur!=="0" && !isset($hash[$cur])){$hash[$cur]=rdpc_option_label($tpl,"","#".($pac>0?$pac:$proxy)," - {rdpproxy_netns_missing}");}
    $hash["0"]=rdpc_option_label($tpl,"","","{rdpproxy_webproxy_direct}");
    // Pas de « nonull: » (la liste simple l'affichait tel quel, M3) : $defnull=false retire
    // l'option vide de la liste « chosen » ; celle de la liste simple garde l'enregistre.
    $s=$tpl->field_array_hash($hash,"web_route","{rdpproxy_policy_web_route}",$cur,false,"{rdpproxy_policy_web_route_explain}","RdpPolicyRouteApply",false);
    $s.=$tpl->field_checkbox("web_pac_fail_direct","{rdpproxy_policy_web_pac_fail_direct}",$failDirect?1:0,false,"{rdpproxy_policy_web_pac_fail_direct_help}");
    // Moteur, artica-webview ou authhook sans PAC (GET /rdpproxy/webview pac_supported) : la
    // session d'une politique a PAC sera refusee (web_engine_outdated). Avertissement montre
    // seulement quand un PAC est choisi (RdpPolicyRouteApply).
    if(!rdpproxy_web_pac_state()["supported"]){
        $wid=md5("web_pac_unsupported".$tpl->suffixid);
        $s.=preg_replace('/^<tr>/',"<tr id='$wid' style='display:none'>",rdpproxy_warning_row($tpl,"{rdpproxy_webpac_unsupported}"),1);
    }
    return $s;
}

// Avertissement d'une fenetre de formulaire : un seul « Avertissement » (titre du bandeau).
// field_section("{Warning}",$texte,true) affichait le titre ET la pastille « Avertissement ».
// La ligne commence par <tr> (RdpPolicyRouteApply la masque par son id).
function rdpproxy_warning_row($tpl,$text):string{
    return "<tr>\n<td colspan='2'>".$tpl->div_warning($text)."</td>\n</tr>";
}

// Politique limitee a des reseaux sources : au moins un reseau, aucun trop large (IPv4 < /8,
// IPv6 < /16, adresse nulle, ligne illisible ; IPv4 mappee ::ffff:a.b.c.d/n jugee en IPv4, n-96).
// Copie de NLATrustRestricted (articarest) et de nlaTrustRestricted (authhook) : seul authhook
// decide, la console ne fait qu'avertir.
function rdpc_policy_networks_restricted($p):bool{
    $nets=isset($p["networks"])&&is_array($p["networks"])?$p["networks"]:array();
    if(count($nets)==0){return false;}
    foreach($nets as $n){
        $pat=trim(is_array($n)?(string)($n["pattern"] ?? ""):(string)$n);
        if($pat===""){return false;}
        if(strpos($pat,"/")===false){
            $bin=@inet_pton($pat);
            if($bin===false){return false;}
            if(strlen($bin)==16 && substr($bin,0,12)===str_repeat("\0",10)."\xff\xff"){$bin=substr($bin,12);}
            if(trim($bin,"\0")===""){return false;}
            continue;
        }
        list($addr,$bits)=explode("/",$pat,2);
        $bin=@inet_pton($addr);
        if($bin===false || !ctype_digit($bits)){return false;}
        $ones=intval($bits);
        if(strlen($bin)==4){
            if($ones>32 || $ones<8){return false;}
            continue;
        }
        if($ones>128){return false;}
        if(substr($bin,0,12)===str_repeat("\0",10)."\xff\xff"){
            if($ones-96<8){return false;}
            continue;
        }
        if($ones<16){return false;}
    }
    return true;
}

// Case « sortie directe en cas d'erreur du PAC » : visible seulement quand un PAC est choisi.
// La fonction lit la liste par son id (la liste « chosen » passe la valeur, la liste simple
// passe l'id de l'element : l'argument est ignore).
function rdpc_policy_route_js($tpl):string{
    $sel=md5("web_route".$tpl->suffixid);
    $cb=md5("web_pac_fail_direct".$tpl->suffixid);
    $wid=md5("web_pac_unsupported".$tpl->suffixid);
    return "<script>
(function(){
 window.RdpPolicyRouteApply=function(){
  var s=document.getElementById('$sel');
  var pac=!!(s && /^c[0-9]+\$/.test(s.value));
  var w=document.getElementById('$wid');
  if(w){w.style.display=pac?'':'none';}
  var c=document.getElementById('$cb');
  if(!c){return;}
  var t=c.closest?c.closest('tr'):null;
  if(t){t.style.display=pac?'':'none';}
 };
 RdpPolicyRouteApply();
 setTimeout(function(){RdpPolicyRouteApply();},1300);
})();
</script>";
}

// ---------------------------------------------------------- Proxys amont des cibles WEB

// Onglet Proxys web : un seul hote (rdpproxy-webproxies) pour tout le tableau de bord, car le
// schema, les indicateurs et l'alerte croisent les proxys ET les fichiers PAC. Chaque action
// (formulaire, bascule, suppression) recharge ce bloc entier : rdpw_reload_js().
function rdpx_webproxies_start(){
    $page=CurrentPageName();
    echo "<div id='rdpproxy-webproxies' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-webproxies','$page?webproxies-table=yes');</script>";
}

function rdpw_reload_js():string{
    $page=CurrentPageName();
    return "LoadAjaxSilent('rdpproxy-webproxies','$page?webproxies-table=yes');";
}

// Ancien point d'entree du tableau des PAC : rend le tableau de bord complet.
function rdpx_webpacs_table(){
    rdpx_webproxies_table();
}

function rdpx_webproxies_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $perr="";$cerr="";
    $proxies=array();$pacs=array();
    foreach(rdpproxy_webproxies($perr) as $l){ if(is_array($l)){$proxies[]=$l;} }
    foreach(rdpproxy_webpacs($cerr) as $l){ if(is_array($l) && intval($l["id"] ?? 0)>0){$pacs[]=$l;} }

    $html=array();
    $html[]="<div class='rdpw ak'>";
    $html[]=rdpw_flow($proxies,$pacs);
    $html[]="<div class='ak-grid-4'>";
    $html[]=rdpw_kpi_proxies($proxies);
    $html[]=rdpw_kpi_routed($proxies);
    $html[]=rdpw_kpi_pacs($pacs);
    $html[]=rdpw_kpi_pac_errors($pacs);
    $html[]="</div>";
    foreach($proxies as $l){ $html[]=rdpw_alert_disabled($l); }
    $html[]=rdpw_proxies_section($proxies,$perr);
    $html[]=rdpw_pacs_section($pacs,$cerr);
    $html[]="</div>";
    echo rdpw_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).rdpw_js();

    $topbuttons=array();
    if(!rdpproxy_meta_locked()){
        $topbuttons[]=array("Loadjs('$page?webproxy-js=0')",ico_plus,"{rdpproxy_webproxy_new}","AllowAddUsers");
        $topbuttons[]=array("Loadjs('$page?webpac-js=0')",ico_plus,"{rdpproxy_webpac_new}","AllowAddUsers");
    }
    $topbuttons[]=array(rdpw_reload_js(),ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{rdpproxy_webproxies}";
    $TINY_ARRAY["ICO"]=ico_earth;
    $TINY_ARRAY["EXPL"]="{rdpw_tiny_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    echo "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

function rdpw_sum_policies(array $list):int{
    $n=0;
    foreach($list as $l){ $n+=max(0,intval($l["policies"] ?? 0)); }
    return $n;
}

/** Schema : session -> proxy de filtrage -> trois sorties possibles. */
function rdpw_flow(array $proxies,array $pacs):string{
    $np=rdpw_sum_policies($proxies);
    $nc=rdpw_sum_policies($pacs);
    $h="<section class='ak-card rdpw-flow' aria-label=\"{rdpw_flow_title}\">";
    $h.="<div class='rdpw-step'><span class='rdpw-step-ico'><i class='fas fa-desktop'></i></span><div><b>{rdpw_flow_session}</b><small>{rdpw_flow_session_sub}</small></div></div>";
    $h.="<span class='rdpw-arrow' aria-hidden='true'><i class='fas fa-arrow-right'></i></span>";
    $h.="<div class='rdpw-step rdpw-step-main'><span class='rdpw-step-ico'><i class='fas fa-shield-halved'></i></span><div><b>{rdpw_flow_filter}</b><small>{rdpw_flow_filter_sub}</small></div></div>";
    $h.="<span class='rdpw-arrow' aria-hidden='true'><i class='fas fa-arrow-right'></i></span>";
    $h.="<ul class='rdpw-exits'>";
    $h.="<li><i class='fas fa-arrow-up-right-from-square'></i><b>{rdpw_exit_direct}</b><span class='ak-pill ak-off'>{rdpw_exit_direct_sub}</span></li>";
    $h.="<li><i class='fas fa-network-wired'></i><b>{rdpproxy_webproxy}</b><span class='ak-pill ak-ok'>$np {rdpw_n_policies}</span></li>";
    $h.="<li><i class='fas fa-file-code'></i><b>{rdpproxy_webpac}</b><span class='ak-pill ak-info'>$nc {rdpw_n_policies}</span></li>";
    $h.="</ul></section>";
    return $h;
}

function rdpw_kpi($label,$value,$sub,$art,$cls=""):string{
    return "<div class='ak-fig $cls'><div class='ak-fig-txt'><span class='ak-fig-l'>$label</span>"
        ."<span class='ak-fig-v'>$value</span><span class='ak-fig-s'>$sub</span></div><div class='rdpw-kpi-art'>$art</div></div>";
}

function rdpw_kpi_proxies(array $proxies):string{
    $on=0;
    foreach($proxies as $l){ if(!empty($l["enabled"])){$on++;} }
    $off=count($proxies)-$on;
    $art="<span class='ak-ico'><i class='fas fa-network-wired'></i></span>";
    if(count($proxies)>0){$art="<span class='rdpw-g-onoff' role='img' aria-label=\"$on {enabled}, $off {disabled}\">$on,$off</span>";}
    return rdpw_kpi("{rdpproxy_webproxies}",(string)count($proxies),"$on {enabled} &middot; $off {disabled}",$art);
}

/** Politiques routees par un proxy amont, barres par proxy. */
function rdpw_kpi_routed(array $proxies):string{
    $vals=array();$used=0;
    foreach($proxies as $l){ $n=max(0,intval($l["policies"] ?? 0)); $vals[]=$n; if($n>0){$used++;} }
    $sum=array_sum($vals);
    $art="<span class='ak-ico'><i class='fas fa-route'></i></span>";
    if($sum>0){
        $w=max(36,min(110,count($vals)*16));
        $art="<span class='rdpw-g-bars' data-w='$w' role='img' aria-label=\"$sum {rdpw_n_policies}\">".implode(",",$vals)."</span>";
    }
    return rdpw_kpi("{rdpw_routed}",(string)$sum,"$used {rdpw_proxies_used}",$art);
}

function rdpw_kpi_pacs(array $pacs):string{
    $url=0;
    foreach($pacs as $l){ if(trim((string)($l["source_url"] ?? ""))!==""){$url++;} }
    $up=count($pacs)-$url;
    $art="<span class='ak-ico'><i class='fas fa-file-code'></i></span>";
    if(count($pacs)>0){$art="<span class='rdpw-g-src' role='img' aria-label=\"$url URL, $up {rdpproxy_webpac_uploaded}\">$url,$up</span>";}
    return rdpw_kpi("{rdpproxy_webpacs}",(string)count($pacs),"$url URL &middot; $up {rdpproxy_webpac_uploaded}",$art);
}

function rdpw_kpi_pac_errors(array $pacs):string{
    $n=0;
    foreach($pacs as $l){ if(trim((string)($l["last_error"] ?? ""))!==""){$n++;} }
    if($n==0){
        return rdpw_kpi("{rdpw_pac_errors}","0","<span class='ak-state ak-ok'><i class='fas fa-circle-check'></i> {rdpw_pac_all_ok}</span>",
            "<span class='ak-ico ak-ok'><i class='fas fa-rotate'></i></span>");
    }
    return rdpw_kpi("{rdpw_pac_errors}","<span class='rdpw-bad-v'>$n</span>","<span class='ak-state ak-err'><i class='fas fa-triangle-exclamation'></i> {rdpw_pac_err_sub}</span>",
        "<span class='ak-ico ak-err'><i class='fas fa-rotate'></i></span>","rdpw-kpi-bad");
}

/** Alerte : un proxy desactive mais utilise fait refuser les sessions de ses politiques. */
function rdpw_alert_disabled(array $l):string{
    $used=intval($l["policies"] ?? 0);
    if(!empty($l["enabled"]) || $used<=0){return "";}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($l["id"] ?? 0);
    $name=rdpc_status_esc((string)($l["name"] ?? "#$ID"));
    $h="<div class='rdpw-alert' role='alert'><span class='ak-ico ak-ico-sq ak-err'><i class='fas fa-triangle-exclamation'></i></span>"
        ."<div><b>{rdpproxy_webproxy} &laquo;&nbsp;$name&nbsp;&raquo; {rdpw_alert_disabled} ($used {rdpw_n_policies})</b>"
        ."<span>{rdpw_alert_refused}</span></div><div class='rdpw-alert-a'>"
        ."<a href='#rdpw-px-$ID' class='ak-link'>{rdpw_view}</a>";
    if($tpl->IsSecurity("AllowAddUsers") && !rdpproxy_meta_locked()){
        $h.="<button type='button' class='ak-btn rdpw-btn-fill' onclick=\"Loadjs('$page?webproxy-enable=$ID')\">{rdpw_enable_proxy}</button>";
    }
    return $h."</div></div>";
}

/** En-tete de section : titre, nombre, phrase, explication depliable, bouton. */
function rdpw_section_head($title,$count,$countTok,$lead,$moreTok,$btnJs,$btnTok,$btnCls):string{
    $tpl=new template_admin();
    $h="<header class='rdpw-sec'><div class='rdpw-sec-t'><div><h3>$title</h3><span class='ak-count'>$count $countTok</span></div><p>$lead</p></div>";
    $h.="<div class='rdpw-sec-a'><details class='rdpw-more'><summary><i class='fas fa-circle-info'></i> {rdpw_learn_more}</summary><div>$moreTok</div></details>";
    if($tpl->IsSecurity("AllowAddUsers") && !rdpproxy_meta_locked()){
        $h.="<button type='button' class='ak-btn ak-btn-primary $btnCls' onclick=\"$btnJs\"><i class='fas fa-plus'></i> $btnTok</button>";
    }
    return $h."</div></header>";
}

function rdpw_proxies_section(array $proxies,$err):string{
    $page=CurrentPageName();
    $h="<section class='rdpw-group'>";
    $h.=rdpw_section_head("{rdpproxy_webproxies}",count($proxies),"{rdpw_configured}","{rdpw_proxies_lead}",
        "{rdpproxy_webproxies_explain}","Loadjs('$page?webproxy-js=0')","{rdpproxy_webproxy_new}","");
    if($err!=""){
        $tpl=new template_admin();
        return $h.$tpl->div_error(rdpc_err_html($err))."</section>";
    }
    if(count($proxies)==0){
        return $h."<div class='ak-card ak-empty-c'><i class='fas fa-arrow-up-right-from-square'></i><span>{rdpproxy_webproxy_empty}</span></div></section>";
    }
    $max=1;
    foreach($proxies as $l){ $max=max($max,intval($l["policies"] ?? 0)); }
    $h.="<div class='rdpw-grid'>";
    foreach($proxies as $l){ $h.=rdpw_proxy_card($l,$max); }
    return $h."</div></section>";
}

/** Carte d'un proxy amont : adresse, interrupteur, compte, politiques, etat, actions. */
function rdpw_proxy_card(array $l,$max):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $can=$tpl->IsSecurity("AllowAddUsers") && !rdpproxy_meta_locked();
    $ID=intval($l["id"] ?? 0);
    $on=!empty($l["enabled"]);
    $used=max(0,intval($l["policies"] ?? 0));
    $name=rdpc_status_esc((string)($l["name"] ?? ""));
    $addr=rdpc_status_esc((string)($l["host"] ?? "").":".intval($l["port"] ?? 0));
    $user=trim((string)($l["username"] ?? ""));
    $edit="Loadjs('$page?webproxy-js=$ID')";
    $refused=!$on && $used>0;
    $cls="ak-card rdpw-pcard".($on?"":" rdpw-off").($refused?" rdpw-refused":"");

    $h="<article class='$cls' id='rdpw-px-$ID'><header class='rdpw-ph'><span class='ak-ico ak-ico-sq'><i class='fas fa-network-wired'></i></span>"
        ."<div class='rdpw-ph-t'><h4>$name</h4><span class='ak-mono'>$addr</span></div>";
    $lbl=$on?"{enabled}":"{disabled}";
    if($can){
        $h.="<span class='rdpw-swl'><button type='button' class='ak-sw' role='switch' aria-checked='".($on?"true":"false")."' aria-labelledby='rdpw-swl-$ID' "
            ."onclick=\"Loadjs('$page?webproxy-enable=$ID')\"></button><span id='rdpw-swl-$ID'>$lbl</span></span>";
    }else{
        $h.="<span class='ak-pill ".($on?"ak-ok":"ak-off")."'>$lbl</span>";
    }
    $h.="</header>";

    $acc=$user==""?"<span class='ak-muted rdpw-it'>{rdpproxy_webproxy_no_auth}</span>"
        :"<span class='ak-mono rdpw-strong'>".rdpc_status_esc($user)."</span>"
            .(!empty($l["password_set"])?"<span class='ak-tag'><i class='fas fa-key'></i> {rdpw_pwd_set}</span>":"");
    $pct=round($used/max(1,$max)*100);
    $h.="<dl class='rdpw-facts'><div><dt>{account}</dt><dd>$acc</dd></div>"
        ."<div><dt>{policies}</dt><dd><span class='rdpw-strong'>$used</span>"
        .($used>0?"<span class='ak-bar rdpw-bar' aria-hidden='true'><span style='width:$pct%'></span></span>":"")."</dd></div></dl>";

    if($refused){
        $h.="<p class='ak-banner rdpw-status ak-err'><i class='fas fa-ban'></i> {rdpw_state_refused}</p>";
    }elseif($used>0){
        $h.="<p class='ak-banner rdpw-status ak-ok'><i class='fas fa-circle-check'></i> {rdpw_state_used}</p>";
    }else{
        $h.="<p class='ak-banner rdpw-status'><i class='far fa-circle-pause'></i> {rdpw_state_unused}</p>";
    }

    $h.="<footer class='rdpw-pf'>";
    if($can){
        $h.="<button type='button' class='ak-btn' onclick=\"$edit\"><i class='fas fa-pen'></i> {rdpw_edit}</button>";
        $md=md5(serialize($l));
        if($used>0){
            $h.="<button type='button' class='ak-btn ak-btn-danger' disabled title=\"{rdpw_delete_linked}\" aria-label=\"{rdpw_delete} : {rdpw_delete_linked}\"><i class='fas fa-trash'></i> {rdpw_delete}</button>";
        }else{
            $h.="<button type='button' class='ak-btn ak-btn-danger' onclick=\"Loadjs('$page?webproxy-delete=$ID&md=$md')\"><i class='fas fa-trash'></i> {rdpw_delete}</button>";
        }
    }
    return $h."</footer></article>";
}

function rdpw_pacs_section(array $pacs,$err):string{
    $page=CurrentPageName();
    $h="<section class='rdpw-group'>";
    $h.=rdpw_section_head("{rdpproxy_webpacs}",count($pacs),"{rdpw_files}","{rdpw_pacs_lead}",
        "{rdpproxy_webpacs_explain}","Loadjs('$page?webpac-js=0')","{rdpproxy_webpac_new}","rdpw-btn-pac");
    if($err!=""){
        $tpl=new template_admin();
        return $h.$tpl->div_error(rdpc_err_html($err))."</section>";
    }
    if(count($pacs)==0){
        return $h."<div class='ak-card ak-empty-c'><i class='fas fa-file-code'></i><span>{rdpproxy_webpac_empty}</span></div></section>";
    }
    $h.="<div class='rdpw-grid rdpw-grid-pac'>";
    foreach($pacs as $l){ $h.=rdpw_pac_card($l); }
    return $h."</div></section>";
}

/** Carte d'un fichier PAC : source, fraicheur, derniere erreur, empreinte, actions. */
function rdpw_pac_card(array $l):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($l["id"] ?? 0);
    $name=rdpc_status_esc((string)($l["name"] ?? ""));
    $size=intval($l["size"] ?? 0);
    $sizeTxt=$size<1024?"$size {bytes}":number_format($size/1024,1)." KB";
    $src=trim((string)($l["source_url"] ?? ""));
    $fetched=intval($l["fetched"] ?? 0);
    $lerr=trim((string)($l["last_error"] ?? ""));
    $used=max(0,intval($l["policies"] ?? 0));
    $sha=strtolower(preg_replace('/[^0-9a-fA-F]/','',(string)($l["sha256"] ?? "")));

    $h="<article class='ak-card rdpw-pcard rdpw-pac".($lerr!=""?" rdpw-pac-err":"")."'>";
    $h.="<header class='rdpw-ph'><span class='ak-ico ak-ico-sq ".($src!==""?"ak-info":"ak-violet")."'><i class='".($src!==""?"fas fa-code":"far fa-file-lines")."'></i></span>"
        ."<div class='rdpw-ph-t'><h4>$name <small>$sizeTxt</small></h4>";
    if($src!==""){
        $h.="<span class='rdpw-src'><span class='ak-tag ak-info'>URL</span><span class='ak-mono rdpw-url' title=\"".rdpc_status_esc($src)."\">".rdpc_status_esc($src)."</span></span>";
    }else{
        $h.="<span class='rdpw-src'><span class='ak-tag ak-violet'>{rdpproxy_webpac_uploaded}</span></span>";
    }
    $h.="</div><span class='ak-pill ak-info'>$used {rdpw_n_policies}</span></header>";

    // Fraicheur : un PAC par URL est retelecharge toutes les heures ; au-dela de 1 h 30 il a du retard.
    if($src===""){
        $h.="<p class='ak-banner rdpw-status'><span class='ak-dot ak-violet'></span>{rdpw_static}</p>";
    }elseif($fetched<=0){
        $h.="<p class='ak-banner rdpw-status'><span class='ak-dot ak-off'></span>{rdpproxy_webpac_fetched} : {never}</p>";
    }else{
        $age=time()-$fetched;
        $dot=$age>5400?"warn":"ok";
        $h.="<p class='ak-banner rdpw-status".($dot=="warn"?" ak-warn":"")."'><span class='ak-dot ak-$dot'></span>{rdpproxy_webpac_fetched} : "
            ."<b class='ak-mono'>".date("Y-m-d H:i",$fetched)."</b> (".rdpw_age($age).")"
            .($age>5400?" &middot; {rdpw_stale}":"")."</p>";
    }
    if($lerr!==""){
        $h.="<div class='rdpw-perr' role='alert'><b><i class='fas fa-rotate'></i> {rdpw_pac_err_title}</b>"
            ."<span class='ak-mono'>".rdpc_status_esc($lerr)."</span><span>{rdpw_pac_err_keep}</span></div>";
    }
    if($sha!==""){
        $h.="<div class='rdpw-sha'><span>SHA-256</span><span class='ak-mono' title='$sha'>".substr($sha,0,16)."&hellip;</span>"
            ."<button type='button' class='ak-link' data-v='$sha' onclick=\"RdpwCopy(this)\" aria-label=\"{rdptd_copy_hash} $name\"><i class='far fa-copy'></i> <span>{copy}</span></button></div>";
    }

    $h.="<footer class='rdpw-pf'><div class='rdpw-pf-l'>";
    if($tpl->IsSecurity("AsSquidAdministrator")){
        $h.="<button type='button' class='ak-btn' onclick=\"Loadjs('$page?webpac-view-js=$ID')\"><i class='fas fa-eye'></i> {rdpproxy_webpac_preview}</button>";
    }
    if($tpl->IsSecurity("AllowAddUsers")){
        $h.="<button type='button' class='ak-btn' onclick=\"Loadjs('$page?webpac-test-js=$ID')\"><i class='fas fa-vial'></i> {rdpw_test}</button>";
        if(!rdpproxy_meta_locked()){
            $h.="<button type='button' class='ak-btn' onclick=\"Loadjs('$page?webpac-js=$ID')\"><i class='fas fa-pen'></i> {rdpw_edit}</button>";
        }
    }
    $h.="</div>";
    if($tpl->IsSecurity("AllowAddUsers") && !rdpproxy_meta_locked()){
        $md=md5(serialize($l));
        if($used>0){
            $h.="<button type='button' class='ak-btn ak-btn-danger' disabled title=\"{rdpw_delete_linked}\" aria-label=\"{rdpw_delete} : {rdpw_delete_linked}\"><i class='fas fa-trash'></i> {rdpw_delete}</button>";
        }else{
            $h.="<button type='button' class='ak-btn ak-btn-danger' onclick=\"Loadjs('$page?webpac-delete=$ID&md=$md')\"><i class='fas fa-trash'></i> {rdpw_delete}</button>";
        }
    }
    return $h."</footer></article>";
}

function rdpw_age($s):string{
    $s=max(0,intval($s));
    if($s<60){return "< 1 min";}
    $d=intdiv($s,86400); $h=intdiv($s%86400,3600); $m=intdiv($s%3600,60);
    if($d>0){return "$d {rdpt_days_short} $h h";}
    if($h>0){return "$h h $m min";}
    return "$m min";
}

function rdpw_accent():string{
    $a="#10b981";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        if(!empty($b["accent"])){$a=$b["accent"];}
    }
    return $a;
}

function rdpw_js():string{
    $tpl=new template_admin();
    $copied=json_encode($tpl->javascript_parse_text("{copied}"),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
    $A=json_encode(rdpw_accent());
    return "<script>
function RdpwCopy(btn){
  var v=btn.getAttribute('data-v')||'', lbl=btn.querySelector('span');
  var done=function(){ if(!lbl){return;} var o=lbl.textContent; lbl.textContent=$copied; setTimeout(function(){lbl.textContent=o;},2000); };
  var legacy=function(){ var ta=document.createElement('textarea'); ta.value=v; document.body.appendChild(ta); ta.select();
    try{document.execCommand('copy');done();}catch(e){} document.body.removeChild(ta); };
  if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(v).then(done,legacy); return; }
  legacy();
}
(function(){
  if(typeof jQuery==='undefined'){return;}
  var run=function(){
    var \$=jQuery;
    \$('.rdpw-g-onoff').peity('donut',{radius:22,innerRadius:15,fill:[$A,'#cbd5e1']});
    \$('.rdpw-g-src').peity('donut',{radius:22,innerRadius:15,fill:['#1d4ed8','#005447']});
    \$('.rdpw-g-bars').each(function(){ \$(this).peity('bar',{height:36,width:parseInt(this.getAttribute('data-w')||'60',10),padding:0.3,fill:[$A]}); });
  };
  if(jQuery.fn.peity){run();return;}
  var sc=document.createElement('script'); sc.src='js/jquery.peity.min.js'; sc.onload=run; document.head.appendChild(sc);
})();
NoSpinner();
</script>";
}

function rdpw_style():string{
    if(isset($GLOBALS["RDPW_STYLE"])){return "";}
    $GLOBALS["RDPW_STYLE"]=true;
    return "<style>
.rdpw{display:flex;flex-direction:column;gap:20px;margin-bottom:20px;color:#111d23}
.rdpw-it{font-style:italic}.rdpw-strong{font-weight:700;color:#111d23}
.rdpw-flow{display:flex;align-items:center;gap:14px;padding:16px 20px;flex-wrap:wrap}
.rdpw-step{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;background:#f1f5f9;flex:1 1 220px;min-width:0}
.rdpw-step b{display:block;font-size:14px;color:#111d23}
.rdpw-step small{display:block;font-size:12px;color:#3d4947;margin-top:2px}
.rdpw-step-ico{width:40px;height:40px;border-radius:10px;background:#fff;color:#334155;display:inline-flex;align-items:center;justify-content:center;font-size:17px;flex:0 0 40px}
.rdpw-step-main{background:#e6f4f2}
.rdpw-step-main .rdpw-step-ico{background:#006a62;color:#fff}
.rdpw-step-main b{color:#00504a}
.rdpw-arrow{color:#94a3b8}
.rdpw-exits{list-style:none;margin:0;padding:8px;border-radius:12px;background:#f1f5f9;display:flex;flex-direction:column;gap:6px;flex:0 1 330px}
.rdpw-exits li{display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:8px;background:#fff;font-size:13px}
.rdpw-exits li i{color:#475569;width:16px;text-align:center}
.rdpw-exits .ak-pill{margin-left:auto}
.rdpw-kpi-bad .ak-fig-l{color:#991b1b}
.rdpw-bad-v{color:#b91c1c}
.rdpw-alert{display:flex;align-items:center;gap:14px;padding:14px 18px;border:1px solid #fca5a5;border-radius:12px;background:#fef2f2;color:#7f1d1d}
.rdpw-alert>div:nth-child(2){flex:1 1 auto;display:flex;flex-direction:column;gap:2px}
.rdpw-alert b{font-size:14px;color:#450a0a}
.rdpw-alert span{font-size:13px}
.rdpw-alert-a{display:flex;align-items:center;gap:12px;flex:0 0 auto}
.rdpw-group{display:flex;flex-direction:column;gap:14px}
.rdpw-sec{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:14px 18px;border-radius:12px;background:#eef4f8;flex-wrap:wrap}
.rdpw-sec-t{flex:1 1 360px;min-width:0}
.rdpw-sec-t>div{display:flex;align-items:center;gap:10px}
.rdpw-sec h3{margin:0;font-size:17px;font-weight:700;color:#111d23}
.rdpw-sec p{margin:4px 0 0;font-size:13px;color:#3d4947}
.rdpw-sec-a{display:flex;align-items:flex-start;gap:12px}
.rdpw-more{position:relative;font-size:13px}
.rdpw-more summary{cursor:pointer;color:#00504a;font-weight:600;padding:7px 4px;list-style:none;white-space:nowrap}
.rdpw-more summary::-webkit-details-marker{display:none}
.rdpw-more[open] summary{text-decoration:underline}
.rdpw-more>div{position:absolute;right:0;top:34px;z-index:5;width:420px;padding:12px 14px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.12);font-size:12px;line-height:1.55;color:#334155}
.rdpw-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:20px}
.rdpw-grid-pac{grid-template-columns:repeat(auto-fill,minmax(420px,1fr))}
.rdpw-pcard{display:flex;flex-direction:column;gap:12px;padding:16px 18px;transition:border-color .15s,box-shadow .15s;animation:rdpwIn .15s ease-out both}
.rdpw-pcard:hover{border-color:#26a69a;box-shadow:0 6px 16px rgba(15,23,42,.08)}
.rdpw-off{border-style:dashed}
.rdpw-off .rdpw-ph-t,.rdpw-off .rdpw-facts{opacity:.75}
.rdpw-refused{border-color:#fca5a5}
.rdpw-refused .ak-ico,.rdpw-refused .ak-bar{--ak-tfg:#b91c1c;--ak-tbg:#fee2e2;--ak-tsolid:#b91c1c}
.rdpw-ph{display:flex;align-items:flex-start;gap:12px}
.rdpw-ph-t{flex:1 1 auto;min-width:0}
.rdpw-ph-t h4{margin:0 0 3px;font-size:15px;font-weight:700;color:#111d23;overflow-wrap:anywhere}
.rdpw-ph-t h4 small{font:500 11px ui-monospace,Menlo,Consolas,monospace;color:#64748b;margin-left:6px}
.rdpw-ph-t>.ak-mono{font-size:12px;color:#334155;overflow-wrap:anywhere}
.rdpw-swl{display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:700;color:#334155;white-space:nowrap}
.rdpw-facts{margin:0;padding:10px 12px;border-radius:10px;background:#f8fafc;display:flex;flex-direction:column;gap:7px}
.rdpw-facts>div{display:flex;align-items:center;gap:10px}
.rdpw-facts dt{width:90px;flex:0 0 90px;font-size:12px;font-weight:600;color:#3d4947}
.rdpw-facts dd{margin:0;display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:13px;min-width:0}
.rdpw-bar{width:90px}
.rdpw-status{margin:0;font-size:13px;flex-wrap:wrap}
.rdpw-pf{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:auto;padding-top:4px}
.rdpw-pf-l{display:flex;gap:6px;flex-wrap:wrap}
.rdpw .ak-btn[disabled]{cursor:not-allowed}
.rdpw-btn-pac,.rdpw-btn-pac:hover{background:#92400e;border-color:#92400e;color:#fff}
.rdpw-btn-fill,.rdpw-btn-fill:hover{background:#b91c1c;border-color:#b91c1c;color:#fff}
.rdpw-alert .ak-link{color:#7f1d1d}
.rdpw-more summary:focus-visible{outline:2px solid #006a62;outline-offset:2px}
.rdpw-src{display:flex;align-items:center;gap:8px;min-width:0}
.rdpw-url{font-size:12px;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.rdpw-perr{display:flex;flex-direction:column;gap:3px;padding:10px 12px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d;font-size:12px}
.rdpw-perr b{font-size:13px;color:#991b1b}
.rdpw-perr .ak-mono{overflow-wrap:anywhere}
.rdpw-sha{display:flex;align-items:center;gap:10px;padding:7px 12px;border-radius:8px;background:#f8fafc;font-size:12px;color:#3d4947}
.rdpw-sha>span:first-child{font-weight:700;letter-spacing:.04em}
.rdpw-sha .ak-mono{color:#111d23}
.rdpw-sha .ak-link{margin-left:auto}
@keyframes rdpwIn{from{opacity:0}to{opacity:1}}
@media (prefers-reduced-motion:reduce){.rdpw-pcard{animation:none}}
</style>";
}

function rdpx_webproxy_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["webproxy-js"]);
    $tpl->js_dialog2($ID==0?"{rdpproxy_webproxy_new}":"{rdpproxy_webproxy}","$page?webproxy-popup=$ID",700);
}

function rdpx_webproxy_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["webproxy-popup"]);
    $l=array("name"=>"","host"=>"","port"=>3128,"username"=>"","password_set"=>false,"enabled"=>true);
    if($ID>0){
        $error="";
        $l=rdpproxy_webproxy($ID,$error);
        if($error!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error(rdpc_err_html($error)));return;}
    }
    $form=array();
    $tpl->field_hidden("webproxy-id",$ID);
    $form[]=$tpl->field_text("wp_name","{name}",(string)($l["name"] ?? ""),true);
    $form[]=$tpl->field_text("wp_host","{hostname}",(string)($l["host"] ?? ""),true,"{rdpproxy_webproxy_host_explain}");
    $form[]=$tpl->field_numeric("wp_port","{port}",intval($l["port"] ?? 3128));
    $form[]=$tpl->field_text("wp_username","{username}",(string)($l["username"] ?? ""),false,"{rdpproxy_webproxy_username_explain}");
    $pwExplain=!empty($l["password_set"])?"{rdpproxy_webproxy_password_keep}":"";
    $form[]=$tpl->field_password("wp_password","{password}","",false,$pwExplain);
    if(!empty($l["password_set"])){
        $form[]=$tpl->field_checkbox("wp_password_clear","{rdpproxy_webproxy_password_clear}",0);
    }
    $form[]=$tpl->field_checkbox("wp_enabled","{enabled}",!empty($l["enabled"])?1:0);
    $html=array();
    rdpproxy_meta_lock_form($tpl);
    $html[]=$tpl->div_explain("{rdpproxy_webproxy}||{rdpproxy_webproxy_form_explain}");
    $html[]=$tpl->form_outside("",$form,null,$ID==0?"{add}":"{apply}",
        "dialogInstance2.close();LoadAjax('rdpproxy-webproxies','$page?webproxies-table=yes');","AllowAddUsers");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

// N'ecrit RIEN en cas de succes. Mot de passe vide = garde (PUT sans champ password).
function rdpx_webproxy_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["webproxy-id"]);
    $fields=array("name"=>rdpc_post("wp_name"),"host"=>rdpc_post("wp_host"),"port"=>strval(intval(rdpc_post("wp_port"))),
        "username"=>rdpc_post("wp_username"),"enabled"=>intval(rdpc_post("wp_enabled"))==1?"1":"0");
    $pw=rdpc_post("wp_password","raw");
    if($pw!==""){$fields["password"]=$pw;}
    if($ID>0 && intval(rdpc_post("wp_password_clear"))==1){$fields["password_clear"]="1";unset($fields["password"]);}
    $err=rdpproxy_webproxy_save($ID,$fields);
    if($err!=""){echo $tpl->post_error($err);return;}
    admin_tracks(($ID==0?"Created":"Updated")." RDS Proxy upstream web proxy ".$fields["name"]." (".$fields["host"].":".$fields["port"].")");
}

function rdpx_webproxy_delete_ask(){
    $tpl=new template_admin();
    $ID=intval($_GET["webproxy-delete"]);
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $error="";
    $l=rdpproxy_webproxy($ID,$error);
    $name=rdpc_status_esc((string)($l["name"] ?? "#$ID"));
    $tpl->js_confirm_delete($name,"webproxy-delete",$ID,rdpw_reload_js());
}

function rdpx_webproxy_delete_perform(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["webproxy-delete"]);
    $e0="";
    $cur=rdpproxy_webproxy($ID,$e0);
    $name=(string)($cur["name"] ?? "#$ID");
    $err=rdpproxy_webproxy_delete($ID);
    if($err!=""){echo $tpl->_ENGINE_parse_body(rdpc_err_html($err));return;}
    admin_tracks("Deleted RDS Proxy upstream web proxy $name (#$ID)");
}

// Bascule : etat COURANT relu, PUT des memes champs (mot de passe absent = garde).
function rdpx_webproxy_enable(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["webproxy-enable"]);
    header("content-type: application/x-javascript");
    if(!$tpl->IsSecurity("AllowAddUsers")){$tpl->js_mysql_alert("{ERROR_NO_PRIVS2}");return;}
    $error="";
    $l=rdpproxy_webproxy($ID,$error);
    if($error!=""){$tpl->js_error($error);return;}
    $on=empty($l["enabled"]);
    $err=rdpproxy_webproxy_save($ID,array("name"=>(string)($l["name"] ?? ""),"host"=>(string)($l["host"] ?? ""),
        "port"=>strval(intval($l["port"] ?? 0)),"username"=>(string)($l["username"] ?? ""),"enabled"=>$on?"1":"0"));
    if($err!=""){$tpl->js_error($err);return;}
    admin_tracks(($on?"Enabled":"Disabled")." RDS Proxy upstream web proxy ".($l["name"] ?? "#$ID"));
    echo "LoadAjax('rdpproxy-webproxies','$page?webproxies-table=yes');";
}

// ---------------------------------------------------------- Fichiers PAC des cibles WEB
// Routes /rdpproxy/web-pac (articarest). Le PAC n'est jamais evalue par PHP : l'apercu montre le
// texte, le test passe par le daemon (artica-webview pac-eval). Toute valeur venue du daemon est
// echappee et ses accolades neutralisees (rdpc_status_esc).

// Message d'erreur rendu en HTML : un jeton seul est laisse au moteur de traduction, tout autre
// texte (validation du daemon, transport) est echappe.
function rdpc_err_html($err):string{
    $err=trim((string)$err);
    if(preg_match('/^\{[A-Za-z0-9_.\-]+\}$/',$err)){return $err;}
    return rdpc_status_esc($err);
}

function rdpx_webpac_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["webpac-js"]);
    $tpl->js_dialog2($ID==0?"{rdpproxy_webpac_new}":"{rdpproxy_webpac}","$page?webpac-popup=$ID",850);
}

// Ajout / remplacement : televersement (fichier lu par le navigateur dans la zone de texte,
// envoye en content_base64) ou URL (recuperee par le daemon, certificat verifie).
function rdpx_webpac_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));return;}
    $ID=intval($_GET["webpac-popup"]);
    $l=array("name"=>"","source_url"=>"");
    if($ID>0){
        $l=rdpc_webpac_row($ID);
        if(count($l)==0){echo $tpl->_ENGINE_parse_body($tpl->div_error("{rdpproxy_webpac_err_invalid}"));return;}
    }
    $src=trim((string)($l["source_url"] ?? ""));
    $mode=$src!=""?"url":"upload";
    $sfx=$tpl->suffixid;
    $idMode=md5("pac_mode".$sfx);
    $idContent=md5("pac_content".$sfx);
    $idUrl=md5("pac_source_url".$sfx);
    $idFile="pacfile-".md5($sfx);
    $form=array();
    $tpl->field_hidden("webpac-id",$ID);
    $form[]=$tpl->field_text("pac_name","{name}",(string)($l["name"] ?? ""),true);
    $MODES=array("upload"=>"{rdpproxy_webpac_mode_upload}","url"=>"{rdpproxy_webpac_mode_url}");
    $form[]=$tpl->field_array_hash($MODES,"pac_mode","{rdpproxy_webpac_source}",$mode,false,null,"RdpPacModeApply",false);
    $form[]="<tr class='rdppac-upload'><td style='width:30%;text-align:right;vertical-align:middle;padding-bottom:10px;' nowrap><span class=labelform>{rdpproxy_webpac_file}:</span></td>"
        ."<td style='padding-bottom:10px;padding-left:19px'><input type='file' id='$idFile' accept='.pac,.js,.dat,.txt'>"
        ."<div class='text-muted' style='font-size:11px;margin-top:4px'>{rdpproxy_webpac_file_explain}</div></td></tr>";
    $form[]=$tpl->field_textarea_normal("pac_content","{rdpproxy_webpac_content}","","100%","220px",
        $ID>0&&$mode=="upload"?"{rdpproxy_webpac_content_keep}":"{rdpproxy_webpac_content_explain}");
    $form[]=$tpl->field_text("pac_source_url","{rdpproxy_webpac_url}",$src,false,"{rdpproxy_webpac_url_explain}");
    $html=array();
    rdpproxy_meta_lock_form($tpl);
    $html[]=$tpl->div_explain("{rdpproxy_webpac}||{rdpproxy_webpac_form_explain}");
    $html[]=$tpl->form_outside("",$form,null,$ID==0?"{add}":"{apply}",
        "dialogInstance2.close();LoadAjaxSilent('rdpproxy-webproxies','$page?webproxies-table=yes');","AllowAddUsers");
    $toobig=$tpl->javascript_parse_text("{rdpproxy_webpac_too_large}");
    $toobig=str_replace(array("\\","'","\n","\r"),array("","`"," "," "),$toobig);
    $js="<script>
(function(){
 function row(id){var e=document.getElementById(id);return e&&e.closest?e.closest('tr'):null;}
 function show(id,on){var t=row(id);if(t){t.style.display=on?'':'none';}}
 window.RdpPacModeApply=function(){
  var m=document.getElementById('$idMode');var url=!!(m&&m.value==='url');
  show('$idContent',!url);show('$idFile',!url);show('$idUrl',url);
 };
 var f=document.getElementById('$idFile');
 if(f){f.onchange=function(){
  if(!f.files||!f.files[0]){return;}
  if(f.files[0].size>262144){alert('$toobig');f.value='';return;}
  var rd=new FileReader();
  rd.onload=function(){var ta=document.getElementById('$idContent');if(ta){ta.value=String(rd.result||'');}};
  rd.readAsText(f.files[0]);
 };}
 RdpPacModeApply();
 setTimeout(function(){RdpPacModeApply();},1300);
})();
</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).$js;
}

// N'ecrit RIEN en cas de succes. Contenu et URL : une seule couche encodeURIComponent (rawurldecode),
// pour que le texte du PAC arrive octet pour octet.
function rdpx_webpac_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["webpac-id"]);
    $name=rdpc_post("pac_name");
    $mode=rdpc_post("pac_mode");
    if($mode!=="url" && $mode!=="upload"){
        // option vide de la liste simple : deduit des champs remplis
        $mode=(trim(rawurldecode((string)($_POST["pac_content"] ?? "")))==="" && trim(rawurldecode((string)($_POST["pac_source_url"] ?? "")))!=="")?"url":"upload";
    }
    $fields=array("name"=>$name);
    if($mode=="url"){
        $u=trim(rawurldecode((string)($_POST["pac_source_url"] ?? "")));
        if($u===""){echo $tpl->post_error("{rdpproxy_webpac_url}: {cannot_be_a_null_value}");return;}
        $fields["source_url"]=$u;
    }else{
        $c=rawurldecode((string)($_POST["pac_content"] ?? ""));
        if(trim($c)===""){
            // Modification sans nouveau texte : le daemon remplace tout, le texte enregistre est
            // donc renvoye. Un PAC venu d'une URL n'a pas de texte a garder : il en faut un.
            $cur=array();
            if($ID>0){
                $e="";
                $cur=rdpproxy_webpac($ID,$e);
                if($e!=""){echo $tpl->post_error($e);return;}
            }
            if($ID==0 || trim((string)($cur["source_url"] ?? ""))!=="" || trim((string)($cur["content"] ?? ""))===""){
                echo $tpl->post_error("{rdpproxy_webpac_content}: {cannot_be_a_null_value}");return;
            }
            $c=(string)$cur["content"];
        }
        if(strlen($c)>262144){echo $tpl->post_error("{rdpproxy_webpac_too_large}");return;}
        $fields["content_base64"]=base64_encode($c);
    }
    $err=rdpproxy_webpac_save($ID,$fields);
    if($err!=""){echo $tpl->post_error($err);return;}
    admin_tracks(($ID==0?"Created":"Updated")." RDS Proxy web PAC file $name (".($mode=="url"?"downloaded from its URL":"uploaded").")");
}

function rdpx_webpac_view_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["webpac-view-js"]);
    // titre sans accent en francais : buildADialog retire les accents des titres (replace_accents)
    $tpl->js_dialog2("{rdpproxy_webpac_view_title}","$page?webpac-view-popup=$ID",900);
}

// Apercu : fiche du PAC puis son texte, echappe, hors du moteur de traduction.
function rdpx_webpac_view_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));return;}
    $ID=intval($_GET["webpac-view-popup"]);
    $error="";
    $l=rdpproxy_webpac($ID,$error);
    if($error!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error(rdpc_err_html($error)));return;}
    $src=trim((string)($l["source_url"] ?? ""));
    $fetched=intval($l["fetched"] ?? 0);
    $lerr=trim((string)($l["last_error"] ?? ""));
    $tpl->table_form_field_js("");
    $tpl->table_form_field_text("{name}",rdpc_status_esc((string)($l["name"] ?? "")),"fas fa-file-code");
    $tpl->table_form_field_text("{rdpproxy_webpac_source}",$src==""?"{rdpproxy_webpac_uploaded}":rdpc_status_esc($src),ico_earth);
    if($src!=""){
        $tpl->table_form_field_text("{rdpproxy_webpac_fetched}",$fetched>0?rdpc_status_esc($tpl->time_to_date($fetched,true)):"{never}","fas fa-clock");
    }
    $tpl->table_form_field_text("SHA-256",rdpc_status_esc((string)($l["sha256"] ?? "")),"fas fa-fingerprint");
    $tpl->table_form_field_text("{policies}",strval(intval($l["policies"] ?? 0)),"fas fa-building-shield");
    if($lerr!=""){
        $tpl->table_form_field_text("{rdpproxy_webpac_last_error}","<span style='color:#ba1a1a'>".rdpc_status_esc($lerr)."</span>","fas fa-triangle-exclamation");
    }
    $card=$tpl->table_form_compile();
    $btn="<div style='margin:10px 0'><a class='btn btn-sm btn-primary' href=\"javascript:blur();\" OnClick=\"dialogInstance2.close();Loadjs('$page?webpac-test-js=$ID')\"><i class='fas fa-vial'></i> {rdpproxy_webpac_test}</a></div>";
    $pre="<pre style='max-height:420px;overflow:auto;font-size:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px;white-space:pre'>"
        .rdpc_status_esc((string)($l["content"] ?? ""))."</pre>";
    echo $tpl->_ENGINE_parse_body($card.$btn).$pre;
}

function rdpx_webpac_test_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["webpac-test-js"]);
    $tpl->js_dialog2("{rdpproxy_webpac_test}","$page?webpac-test-popup=$ID",850);
}

// Test : une URL (et un hote facultatif) ; le resultat se recharge sous le formulaire.
function rdpx_webpac_test_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));return;}
    $ID=intval($_GET["webpac-test-popup"]);
    $l=rdpc_webpac_row($ID);
    if(count($l)==0){echo $tpl->_ENGINE_parse_body($tpl->div_error("{rdpproxy_webpac_err_invalid}"));return;}
    $s=md5($tpl->suffixid.$ID);
    $html=array();
    $html[]=$tpl->div_explain(rdpc_status_esc((string)($l["name"] ?? ""))."||{rdpproxy_webpac_test_explain2}");
    $html[]="<div class='row' style='margin-top:10px'>";
    // Pas de champ « hôte » : un navigateur passe toujours l'hôte de l'URL, et un hôte tapé à part
    // faussait le test (RWE 2026-11-02 : hôte de la cible + URL Entra ID = DIRECT trompeur).
    $html[]="<div class='col-sm-9'><label class='labelform'>URL</label><input type='text' class='form-control' maxlength='2048' id='pt-url-$s' value='https://www.example.com/' OnKeyPress=\"javascript:if(checkEnter(event)){PacTest$s();}\"></div>";
    $html[]="<div class='col-sm-3'><label class='labelform'>&nbsp;</label><br><button type='button' class='btn btn-primary' OnClick=\"javascript:PacTest$s();\"><i class='fas fa-vial'></i> {launch_test}</button></div>";
    $html[]="</div>";
    $html[]="<div id='pt-res-$s' style='margin-top:14px'></div>";
    $js="<script>
function PacTest$s(){
 var u=encodeURIComponent(document.getElementById('pt-url-$s').value);
 LoadAjax('pt-res-$s','$page?webpac-test-run=$ID&url='+u);
}
</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).$js;
}

// FindProxyForURL(url, host) par le daemon. Lecture : rien n'est enregistre.
function rdpx_webpac_test_run(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"))."<script>NoSpinner();</script>";return;}
    $ID=intval($_GET["webpac-test-run"]);
    $url=trim((string)($_GET["url"] ?? ""));
    $error="";
    $r=rdpproxy_webpac_test($ID,$url,"",$error);
    if($error!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error(rdpc_err_html($error)))."<script>NoSpinner();</script>";return;}
    $perr=trim((string)($r["error"] ?? ""));
    if($perr!=""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{rdpproxy_webpac_test_pac_error}||".rdpc_status_esc($perr)))."<script>NoSpinner();</script>";
        return;
    }
    $routes=isset($r["routes"])&&is_array($r["routes"])?$r["routes"]:array();
    $h=array();
    $h[]="<div class='awc-wrap' style='padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'><thead><tr>";
    $h[]="<th style='width:1%'>#</th><th>{rdpproxy_webpac_test_route}</th><th>{rdpproxy_webpac_test_bastion}</th></tr></thead><tbody>";
    if(count($routes)==0){$h[]=rdpc_empty(3,"{rdpproxy_webpac_test_no_route}");}
    $n=0;
    foreach($routes as $rt){
        if(!is_array($rt)){continue;}
        $n++;
        if(!empty($rt["direct"])){
            $what="<strong>DIRECT</strong>";
            $st="<span class='label label-primary'>{rdpproxy_webpac_test_direct}</span>";
        }else{
            $what="<strong>PROXY</strong> <span class='awc-mono'>".rdpc_status_esc((string)($rt["proxy"] ?? ""))."</span>";
            if(empty($rt["declared"])){
                $st="<span class='label label-danger'>{rdpproxy_webpac_test_not_declared}</span>";
            }elseif(empty($rt["proxy_enabled"])){
                $st="<span class='label label-warning'>{rdpproxy_webpac_test_declared_disabled}</span> ".rdpc_status_esc((string)($rt["proxy_name"] ?? ""));
            }else{
                $st="<span class='label label-primary'>{rdpproxy_webpac_test_declared}</span> ".rdpc_status_esc((string)($rt["proxy_name"] ?? ""));
            }
        }
        $h[]="<tr><td class='awc-nowrap'>$n</td><td class='awc-nowrap'>$what</td><td>$st</td></tr>";
    }
    $h[]="</tbody></table></div></div></div>";
    $h[]="<div class='text-muted' style='font-size:12px;margin-top:8px'><i class='fas fa-circle-info'></i> {rdpproxy_webpac_test_order}</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h))."<script>NoSpinner();</script>";
}

function rdpx_webpac_delete_ask(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["webpac-delete"]);
    $l=rdpc_webpac_row($ID);
    $name=rdpc_status_esc((string)($l["name"] ?? "#$ID"));
    $tpl->js_confirm_delete($name,"webpac-delete",$ID,rdpw_reload_js());
}

// N'ecrit RIEN en cas de succes ; un refus (pac_in_use : une politique l'utilise) est affiche
// par la fenetre de confirmation.
function rdpx_webpac_delete_perform(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["webpac-delete"]);
    $cur=rdpc_webpac_row($ID);
    $name=(string)($cur["name"] ?? "#$ID");
    $err=rdpproxy_webpac_delete($ID);
    if($err!=""){echo $tpl->_ENGINE_parse_body(rdpc_err_html($err));return;}
    admin_tracks("Deleted RDS Proxy web PAC file $name (#$ID)");
}

function group_params_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["group-params"]);
    $recording=rdpc_post("user_rec");
    if(!rdpproxy_video_supported()){
        // Case grisee : garder la valeur enregistree, quoi que le formulaire envoie.
        $recording=0;
        if($ID>0){
            $error="";
            $p=rdpproxy_policy($ID,$error);
            $recording=!empty($p["recording"])?1:0;
        }
    }
    $fields=array(
        "name"=>rdpc_post("groupname"),"description"=>rdpc_post("description"),
        "session_time"=>rdpc_post("session_time"),"recording"=>$recording,"mfa_required"=>rdpc_post("mfa_required"));
    // case non postee = cle absente = valeur enregistree gardee par le daemon
    if(isset($_POST["nla_trust_kerberos"])){$fields["nla_trust_kerberos"]=intval($_POST["nla_trust_kerberos"])==1?"1":"0";}
    // Telechargements WEB : champ non poste = cle absente = valeur gardee. Taille et types partent
    // tels que saisis : le daemon les valide (400 web_download_max_mb / web_download_ext, rien
    // d'ecrit) et la fenetre affiche le champ refuse. Types vides = tout refuser.
    if(isset($_POST["allow_web_download"])){$fields["allow_web_download"]=intval($_POST["allow_web_download"])==1?"1":"0";}
    if(isset($_POST["web_download_max_mb"])){$fields["web_download_max_mb"]=rdpc_post("web_download_max_mb");}
    if(isset($_POST["web_download_ext"])){$fields["web_download_ext"]=rdpc_post("web_download_ext");}
    // Sessions Web gardees : meme regle (absent = garde ; le daemon valide 0-480).
    if(isset($_POST["web_keep_minutes"])){$fields["web_keep_minutes"]=rdpc_post("web_keep_minutes");}
    // Droits RDP puis droits du presse-papiers WEB : meme regle, une case non postee = cle absente
    // = valeur enregistree gardee par le daemon (une case decochee poste 0).
    foreach(array_merge(array_keys(rdpproxy_session_rights()),array_keys(rdpproxy_web_clipboard_rights())) as $col){
        if(isset($_POST[$col])){$fields[$col]=intval($_POST[$col])==1?"1":"0";}
    }
    // PUT remplace la politique : web_netns absent du formulaire = valeur enregistree gardee.
    $netns="0";
    if($ID>0){
        $e2="";
        $cur=rdpproxy_policy($ID,$e2);
        $netns=strval(intval($cur["web_netns"] ?? 0));
    }
    $proxy="0";$pac="0";$pacfd="0";
    if($ID>0){
        $proxy=strval(intval($cur["web_proxy"] ?? 0));
        $pac=strval(intval($cur["web_pac"] ?? 0));
        $pacfd=!empty($cur["web_pac_fail_direct"])?"1":"0";
    }
    if(isset($_POST["web_netns"]) && trim((string)$_POST["web_netns"])!==""){$netns=strval(intval($_POST["web_netns"]));}
    // Sortie WEB : "0" directe, "p<id>" proxy, "c<id>" PAC ; valeur vide ou illisible = gardee.
    $route=rdpc_post("web_route");
    if($route==="0"){$proxy="0";$pac="0";}
    elseif(preg_match('/^p([0-9]+)$/',$route,$m)){$proxy=strval(intval($m[1]));$pac="0";}
    elseif(preg_match('/^c([0-9]+)$/',$route,$m)){$pac=strval(intval($m[1]));$proxy="0";}
    if(isset($_POST["web_pac_fail_direct"])){$pacfd=rdpc_post("web_pac_fail_direct")=="1"?"1":"0";}
    $fields["web_netns"]=$netns;
    $fields["web_proxy"]=$proxy;
    $fields["web_pac"]=$pac;
    $fields["web_pac_fail_direct"]=$pacfd;
    $err=rdpproxy_policy_save($ID,$fields);
    if($err!=""){echo $tpl->post_error($err);return;}
    admin_tracks_post(($ID==0?"Created":"Updated")." RDS Proxy policy");
}

function member_js2(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["member-js"]);
    if($ID==0){
        $title="{new_member}";
    }else{
        $m=rdpproxy_member($ID,$error);
        $title=isset($m["username"])?rdpproxy_cn($m["username"]):"";
    }

    $tpl->js_dialog1($title,"$page?member-popup=$ID",750);
}
function member_ad_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["member-ad-js"]);
    if($ID==0){
        $title="{active_directory_group}";
    }else{
        $m=rdpproxy_member($ID,$error);
        $title=isset($m["username"])?rdpproxy_cn($m["username"]):"";
    }

    $tpl->js_dialog1($title,"$page?member-ad-popup=$ID",750);

}

// rdpc_target_current : PUT /rdpproxy/targets/{id} remplace TOUTES les colonnes ;
// une modification part donc de la cible actuelle, sur laquelle on pose les
// champs envoyes par le formulaire. Rend false (et $error) si la lecture echoue.
function rdpc_target_current($ID,&$error){
    $tg=rdpproxy_target($ID,$error);
    if($error!=""){return false;}
    $cur=array();
    foreach(array("alias","designation","proto_dest","target_host","target_device","target_port","target_login","nla_mode") as $k){
        $cur[$k]=isset($tg[$k])?(string)$tg[$k]:"";
    }
    $cur["dont_resolve"]=!empty($tg["dont_resolve"])?"1":"0";
    // Colonnes SSH : le PUT remplace tout. ssh_secret n'est JAMAIS relu (ecriture seule) :
    // absent du PUT = le coffre garde le secret en place.
    $cur["ssh_auth"]=isset($tg["ssh_auth"])?(string)$tg["ssh_auth"]:"";
    $cur["ssh_secret_kind"]=isset($tg["ssh_secret_kind"])?(string)$tg["ssh_secret_kind"]:"";
    $cur["artica_appliance"]=!empty($tg["artica_appliance"])?"1":"0";
    // Colonnes WEB : le PUT remplace tout, elles sont relues comme les autres.
    $cur["web_url"]=isset($tg["web_url"])?(string)$tg["web_url"]:"";
    $cur["web_allow"]=isset($tg["web_allow"])?(string)$tg["web_allow"]:"";
    // Connexion automatique (lot A) : relue pour la fusion. Le mot de passe ne se relit jamais :
    // absent du PUT = gardé par le daemon.
    $cur["web_login_mode"]=isset($tg["web_login_mode"])?(string)$tg["web_login_mode"]:"none";
    $cur["web_login_url"]=isset($tg["web_login_url"])?(string)$tg["web_login_url"]:"";
    $cur["web_login_user"]=isset($tg["web_login_user"])?(string)$tg["web_login_user"]:"";
    $cur["web_login_insecure"]=!empty($tg["web_login_insecure"])?"1":"0";
    return $cur;
}

function targets_ad_save(){
    $tpl=new template_admin();
    $ID=intval($_POST["target-ad-id"]);
    $fields=array(
        "alias"=>rdpc_post("alias","xss"),"proto_dest"=>rdpc_post("proto_dest","xss"),
        "target_port"=>rdpc_post("target_port","xss"),"dont_resolve"=>rdpc_post("DontResolve","xss"));
    if($ID>0){
        $cur=rdpc_target_current($ID,$error);
        if($cur===false){echo $tpl->post_error($tpl->javascript_parse_text($error));return;}
        $fields=array_merge($cur,$fields);
    }
    $err=rdpproxy_target_save($ID,$fields);
    if($err!=""){echo $tpl->post_error($tpl->javascript_parse_text($err));return;}
    admin_tracks_post(($ID==0?"Created":"Updated")." RDS Proxy Active Directory group target");
}

function targets_ad_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["target-ad-popup"]);
    $js="dialogInstance1.close();LoadAjax('rdpproxy-target-table','$page?targets-table=yes');";
    $ligne=array("alias"=>"","proto_dest"=>"","target_port"=>0,"dont_resolve"=>0);
    if($ID==0){
        $title="{active_directory_group}";
        $bt="{add}";

    }else{
        $ligne=rdpproxy_target($ID,$error);
        if($error!=""){echo $tpl->div_error($error);return;}
        $title="{active_directory_group}: {$ligne["alias"]}";
        $bt="{apply}";

    }

    $proto_dest=$ligne["proto_dest"];
    $target_port=intval($ligne["target_port"]);



    if($target_port==0){$target_port=3389;}

    $PRT["RDP"]="RDP";
    $PRT["VNC"]="VNC";

    $tpl->field_hidden("target-ad-id",$ID);
    $form[]= $tpl->field_activedirectorygrp("alias","{active_directory_group}",$ligne["alias"],true,null,false,true);
    if($proto_dest==""){$proto_dest="RDP";}
    $form[]=$tpl->field_array_hash($PRT,"proto_dest","nonull:{protocol}",$proto_dest);
    $form[]=$tpl->field_numeric("target_port","{remote_port}",$target_port);
    $form[]=$tpl->field_checkbox("DontResolve","{DontResolve}",!empty($ligne["dont_resolve"])?1:0);
    rdpproxy_meta_lock_form($tpl);
    echo $tpl->form_outside($title,$form,null,$bt,$js,"AllowAddUsers");
}


function member_ad_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["member-ad-popup"]);
    $js="dialogInstance1.close();LoadAjax('rdpproxy-members-table','$page?members-table=yes');";
    if($ID==0){
        $title="{active_directory_group}";
        $bt="{add}";

    }else{
        $ligne=rdpproxy_member($ID,$error);
        if($error!=""){echo $tpl->div_error($error);return;}
        $title="{active_directory_group}: {$ligne["username"]}";
        $bt="{apply}";

    }
    if(intval($ligne["endoflife"])==0){
        $ligne["endoflife"]=strtotime("2099-01-01 23:59:59");
    }
    $tpl->field_hidden("member-ad-id",$ID);
    $form[]= $tpl->field_activedirectorygrp("username","{active_directory_group}",$ligne["username"],true,null,false,true);
    $form[]= $tpl->field_date("endoflife","{end_of_life}",date("Y-m-d H:i:s",$ligne["endoflife"]));
    rdpproxy_meta_lock_form($tpl);
    echo $tpl->form_outside($title,$form,null,$bt,$js,"AllowAddUsers");
}

function member_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["member-popup"]);
    $js="dialogInstance1.close();LoadAjax('rdpproxy-members-table','$page?members-table=yes');";
    if($ID==0){
        $title="{new_member}";
        $bt="{add}";
        $ligne["password"]=null;
    }else{
        $ligne=rdpproxy_member($ID,$error);
        if($error!=""){echo $tpl->div_error($error);return;}
        $title="{member}: {$ligne["username"]}";
        $bt="{apply}";
        $ligne["password"]="thisisnotapassword";
    }

   if(intval($ligne["endoflife"])==0){
       $ligne["endoflife"]=strtotime("2099-01-01 23:59:59");
   }

   $tpl->field_hidden("member-id",$ID);
   $form[]= $tpl->field_text("username","{username}",$ligne["username"],true);
   $form[]= $tpl->field_date("endoflife","{end_of_life}",date("Y-m-d H:i:s",$ligne["endoflife"]));
   $form[]= $tpl->field_password2("password","{password}",$ligne["password"],true);
   rdpproxy_meta_lock_form($tpl);
   echo $tpl->form_outside($title,$form,null,$bt,$js,"AllowAddUsers");

}
function targets_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["target-popup"]);
    $ssh_supported=rdpproxy_ssh_supported();
    $web_state=rdpproxy_web_state();
    if($ID==0){
        $title="{new_target}";
        $bt="{add}";
        $js="dialogInstance1.close();LoadAjax('rdpproxy-target-table','$page?targets-table=yes');";
        $ligne=array("alias"=>"","designation"=>"","target_password"=>"","target_login"=>"","proto_dest"=>"",
            "target_host"=>"","target_device"=>"","target_port"=>0,"nla_mode"=>1);
    }else{
        $ligne=rdpproxy_target($ID,$error);
        if($error!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error($error));return;}
        $ligne["target_password"]=!empty($ligne["password_set"])?"thisisnotapassword":"";
        $title="{target}: ".htmlspecialchars((string)$ligne["alias"],ENT_QUOTES);
        $bt="{apply}";
        $js="dialogInstance1.close();LoadAjaxTiny('target-status-$ID','$page?target-status=$ID');";

    }
    $alias=$ligne["alias"];
    $designation=$ligne["designation"];
    $target_password=$ligne["target_password"];
    $target_login=$ligne["target_login"];
    $proto_dest=$ligne["proto_dest"];
    $target_host=$ligne["target_host"];
    $target_device=$ligne["target_device"];
    $target_port=intval($ligne["target_port"]);
    $nla_mode=intval($ligne["nla_mode"]);
    $ask_credentials=!empty($ligne["ask_credentials"])?1:0;
    $tls_policy_id=intval($ligne["tls_policy_id"] ?? 0);
    $ssh_auth=(isset($ligne["ssh_auth"])&&$ligne["ssh_auth"]=="vault")?"vault":"prompt";
    $ssh_kind=isset($ligne["ssh_secret_kind"])?(string)$ligne["ssh_secret_kind"]:"";
    if(!in_array($ssh_kind,array("password","private_key","bastion_key"),true)){$ssh_kind="password";}
    $ssh_secret_set=!empty($ligne["ssh_secret_set"]);
    $artica_appliance=!empty($ligne["artica_appliance"])?1:0;
    $hk=isset($ligne["ssh_hostkey_fingerprint"])?(string)$ligne["ssh_hostkey_fingerprint"]:"";
    $hkseen=isset($ligne["ssh_hostkey_seen"])?intval($ligne["ssh_hostkey_seen"]):0;
    if($proto_dest==""){$proto_dest="RDP";}
    $is_ssh=($proto_dest=="SSH");
    $is_web=($proto_dest=="WEB");
    $web_url=isset($ligne["web_url"])?(string)$ligne["web_url"]:"";
    $web_allow=str_replace(",","\n",isset($ligne["web_allow"])?(string)$ligne["web_allow"]:"");
    $tunnel_agent=isset($ligne["tunnel_agent"])?(string)$ligne["tunnel_agent"]:"";

    $tpl->field_hidden("target-id",$ID);
    if($target_port==0){$target_port=$is_ssh?22:3389;}

    $PRT["RDP"]="RDP";
    $PRT["VNC"]="VNC";
    // SSH n'est offert que si le bastion sait le relayer ; une cible SSH deja
    // enregistree reste modifiable (et son option reste choisie).
    $PRT["SSH"]="SSH";
    // Application web (une seule page, rendue par le bastion) : offerte si le bastion sait la
    // servir ; une cible WEB deja enregistree reste modifiable.
    $PRT["WEB"]="{rdpproxy_web_target}";

    $html=array();
    if(!$ssh_supported){
        $html[]=$tpl->div_warning("{rdpproxy_ssh_needs_update}");
    }

    $sfx=$tpl->suffixid;
    $idf=function($name) use ($sfx){return md5($name.$sfx);};

    $form[]=$tpl->field_text("alias","{alias}",$alias,true);
    $form[]=$tpl->field_text("designation","{description}",$designation,true);
    $form[]=$tpl->field_array_hash($PRT,"proto_dest","nonull:{protocol}",$proto_dest,false,null,"RdpSshApply");
    // Cible leurre : aucun hote ni identifiant, jamais proposee par l'authhook ancien. Une fois
    // creee, elle ne redevient pas une cible ordinaire (le daemon refuse) : case figee.
    $is_decoy=!empty($ligne["decoy"]);
    $decoy_enabled=(!isset($ligne["decoy_enabled"]) || !empty($ligne["decoy_enabled"]))?1:0;
    $form[]=$tpl->field_checkbox("decoy","{rdpproxy_decoy}",$is_decoy?1:0,false,"{rdpproxy_decoy_help}",($is_decoy||rdpproxy_meta_locked()));
    $form[]=$tpl->field_checkbox("decoy_enabled","{enabled}",$decoy_enabled,false,null,rdpproxy_meta_locked());
    // Pas obligatoire cote formulaire : masque pour une cible WEB (l'URL donne l'hote). Le daemon
    // l'exige pour RDP, VNC et SSH (validateTarget) et renvoie l'erreur.
    $form[]=$tpl->field_text("target_host","{hostname}",$target_host,false);
    $form[]=$tpl->field_text("target_device","{address}",$target_device,false);
    $form[]=$tpl->field_numeric("target_port","{remote_port}",$target_port);
    // Ni le compte ni le mot de passe ne sont obligatoires cote formulaire : une cible SSH
    // "saisie a la connexion" n'a ni l'un ni l'autre, et un champ masque obligatoire
    // bloquerait l'envoi. targets_save() reimpose les regles RDP/VNC cote serveur.
    $form[]=$tpl->field_text("target_login","{username}",$target_login,false);
    // RDP / VNC : mot de passe de la cible (vide a la modification = garder).
    $form[]=$tpl->field_password2("target_password","{password}",$target_password,false);
    // Un champ vide garde le mot de passe : seule cette case l'efface (RWE 2026-11-02).
    if($ID>0 && !empty($ligne["password_set"])){
        $form[]=$tpl->field_checkbox("target_password_clear","{rdpproxy_target_password_clear}",0,false,"{rdpproxy_target_password_clear_explain}");
    }
    // RDP / VNC : agent de tunnel installe sur le serveur (il se connecte au bastion). Vide = connexion directe.
    $form[]=$tpl->field_text("tunnel_agent","{rdpproxy_tunnel_agent}",$tunnel_agent,false,"{rdpproxy_tunnel_agent_explain}");
    if(!rdpproxy_tunnel_supported()){
        // authhook < 1.9.0 ou inconnu : il ignorerait l'agent (connexion directe), le daemon refuse donc d'en poser un.
        $form[]="<tr class='rdptun-rdp'><td></td><td style='padding:0 0 10px 19px'><div class='text-warning' style='font-size:12px;line-height:1.5'>"
            ."<i class='fas fa-triangle-exclamation'></i> {rdpproxy_tunnel_authhook_old}</div></td></tr>";
    }

    // WEB : adresse de l'application et liste blanche des sites qu'elle peut charger.
    $form[]=$tpl->field_text("web_url","{rdpproxy_web_url}",$web_url,false,"{rdpproxy_web_url_explain}");
    $form[]=$tpl->field_textarea_normal("web_allow","{rdpproxy_web_allow}",$web_allow,"100%","110px","{rdpproxy_web_allow_explain2}");
    // WEB : bandeau du nom de la cible en haut de la session (defaut : affiche ; cle absente = affiche).
    $web_banner=(!isset($ligne["web_banner"]) || !empty($ligne["web_banner"]))?1:0;
    $form[]=$tpl->field_checkbox("web_banner","{rdpproxy_web_banner}",$web_banner,false,"{rdpproxy_web_banner_explain}");
    if(!$web_state["supported"]){
        $form[]="<tr class='rdpweb-web'><td></td><td style='padding:0 0 10px 19px'><div class='text-warning' style='font-size:12px;line-height:1.5'>"
            ."<i class='fas fa-triangle-exclamation'></i> {rdpproxy_web_unavailable_".$web_state["reason"]."}</div></td></tr>";
    }

    // WEB : connexion automatique (lot A). Le mot de passe s'écrit, il ne se relit jamais.
    $login_state=rdpproxy_web_login_state();
    $wl_mode=isset($ligne["web_login_mode"])?(string)$ligne["web_login_mode"]:"none";
    if(!in_array($wl_mode,array("none","http","form"),true)){$wl_mode="none";}
    $wl_url=isset($ligne["web_login_url"])?(string)$ligne["web_login_url"]:"";
    $wl_user=isset($ligne["web_login_user"])?(string)$ligne["web_login_user"]:"";
    $wl_pass_set=!empty($ligne["web_login_pass_set"]);
    $wl_insecure=!empty($ligne["web_login_insecure"])?1:0;
    $wl_off=!$login_state["supported"];
    $form[]="<tr class='rdpweb-web'><td colspan='2' style='padding:14px 0 6px 0'><div style='font-size:14px;font-weight:600;border-bottom:1px solid #e2e8f0;padding-bottom:4px'>"
        ."<i class='fas fa-key'></i>&nbsp;{rdpproxy_weblogin_title}</div>"
        ."<div class='text-muted' style='font-size:12px;line-height:1.5;margin-top:6px'>{rdpproxy_weblogin_explain}</div></td></tr>";
    if($wl_off){
        $form[]="<tr class='rdpweb-web'><td></td><td style='padding:0 0 10px 19px'><div class='text-warning' style='font-size:12px;line-height:1.5'>"
            ."<i class='fas fa-triangle-exclamation'></i> {rdpproxy_weblogin_unavailable}</div></td></tr>";
    }
    $WLM=array("none"=>"{rdpproxy_weblogin_mode_none}","http"=>"{rdpproxy_weblogin_mode_http}","form"=>"{rdpproxy_weblogin_mode_form}");
    $form[]=$tpl->field_array_hash($WLM,"web_login_mode","nonull:{rdpproxy_weblogin_mode}",$wl_mode,false,"{rdpproxy_weblogin_mode_explain}","RdpSshApply");
    $form[]=$tpl->field_text("web_login_url","{rdpproxy_weblogin_url}",$wl_url,false,"{rdpproxy_weblogin_url_explain}",$wl_off);
    $form[]=$tpl->field_text("web_login_user","{username}",$wl_user,false,null,$wl_off);
    $form[]=$tpl->field_password("web_login_pass","{password}","",false,$wl_pass_set?"{rdpproxy_weblogin_pass_keep}":null,$wl_off);
    if($wl_pass_set){
        $form[]=$tpl->field_checkbox("web_login_pass_clear","{rdpproxy_weblogin_pass_clear}",0,false,null,$wl_off);
    }
    $form[]=$tpl->field_checkbox("web_login_insecure","{rdpproxy_weblogin_insecure}",$wl_insecure,false,"{rdpproxy_weblogin_insecure_explain}",$wl_off);
    $form[]="<tr class='rdpweb-web rdpweb-login'><td></td><td style='padding:0 0 10px 19px'><div class='text-muted' style='font-size:12px;line-height:1.5'>"
        ."<i class='fas fa-circle-info'></i> {rdpproxy_weblogin_limits}</div></td></tr>";

    // WEB : copier / coller. Les droits sont ceux des stratégies de la cible (une cible peut
    // en avoir plusieurs) : la section les liste et les modifie, chacun tout de suite.
    $form[]="<tr class='rdpweb-web'><td colspan='2' style='padding:14px 0 6px 0'><div style='font-size:14px;font-weight:600;border-bottom:1px solid #e2e8f0;padding-bottom:4px'>"
        ."<i class='fas fa-paste'></i>&nbsp;{rdpproxy_webclip_target_title}</div>"
        ."<div class='text-muted' style='font-size:12px;line-height:1.5;margin-top:6px'>{rdpproxy_webclip_target_explain}</div>"
        .($ID>0?"<div id='rdpt-webclip-$ID' style='margin-top:8px'></div><script>LoadAjaxSilent('rdpt-webclip-$ID','$page?target-webclip=$ID');</script>"
            :"<div class='text-muted' style='font-size:12px;margin-top:8px'><i class='fas fa-circle-info'></i> {rdpproxy_webclip_target_new}</div>")
        ."</td></tr>";

    // Read by authhook (setNlaMode) and sent to ReDemPtion as mod_rdp:* keys.
    $NLA[0]="{RDP_NLA_NTLM}";
    $NLA[1]="{RDP_NLA_KERBEROS_NTLM}";
    $NLA[2]="{RDP_NLA_KERBEROS}";
    $NLA[3]="{RDP_NLA_TLS}";
    $form[]=$tpl->field_array_hash($NLA,"nla_mode","nonull:{RDP_NLA_MODE}",$nla_mode,false,"{RDP_NLA_MODE_EXPLAIN}<br>{rdpproxy_nla_kerberos_target_help}");
    // Compte de la cible saisi au bastion a chaque connexion (serveurs hors domaine en NLA,
    // authhook >= 1.9.6, RWE 2026-11-02). Le login enregistre preremplit l'ecran.
    $form[]=$tpl->field_checkbox("ask_credentials","{rdpproxy_ask_credentials}",$ask_credentials,false,"{rdpproxy_ask_credentials_explain}");
    // Strategie TLS vers la cible (serveurs anciens, authhook >= 1.9.11, RWE 2026-11-04) :
    // 0 = reglages du bastion. La valeur en place est gardee si la liste ne la contient plus.
    $TLSP=array("0"=>"{rdpproxy_tls_policy_bastion}");
    $perr="";
    foreach(rdpproxy_tls_policies($perr) as $p){$TLSP[(string)intval($p["id"])]=(string)$p["name"];}
    if(!isset($TLSP[(string)$tls_policy_id])){$TLSP[(string)$tls_policy_id]="#$tls_policy_id";}
    $form[]=$tpl->field_array_hash($TLSP,"tls_policy_id","nonull:{rdpproxy_tls_policy}",(string)$tls_policy_id,false,"{rdpproxy_tls_policy_target_explain}");

    // SSH : authentification, secret, appliance Artica, cle d'hote.
    $AUTH=array("prompt"=>"{rdpproxy_ssh_auth_prompt}","vault"=>"{rdpproxy_ssh_auth_vault}");
    $KIND=array("password"=>"{rdpproxy_ssh_kind_password}","private_key"=>"{rdpproxy_ssh_kind_private_key}",
        "bastion_key"=>"{rdpproxy_ssh_kind_bastion_key}");
    $form[]=$tpl->field_array_hash($AUTH,"ssh_auth","nonull:{rdpproxy_ssh_auth}",$ssh_auth,false,"{rdpproxy_ssh_auth_explain}","RdpSshApply");
    $form[]=$tpl->field_array_hash($KIND,"ssh_secret_kind","nonull:{rdpproxy_ssh_secret_kind}",$ssh_kind,false,null,"RdpSshApply");
    // Le secret n'est JAMAIS prerempli : champ vide = garder le secret du coffre.
    $saved=$ssh_secret_set?"{secret_saved_not_displayed}":null;
    $form[]=$tpl->field_password2("ssh_secret_pwd","{password}","",false,$saved);
    $form[]=$tpl->field_textarea_normal("ssh_secret_key","{rdpproxy_ssh_private_key}","","100%","140px",
        $ssh_secret_set?"{secret_saved_not_displayed}":"{rdpproxy_ssh_private_key_explain}");
    $form[]=$tpl->field_checkbox("artica_appliance","{rdpproxy_ssh_artica_appliance}",$artica_appliance,false,"{rdpproxy_ssh_artica_appliance_explain}");

    // Lignes propres au formulaire (id stables pour la bascule JS).
    $hkline="<span class='text-muted'>{rdpproxy_ssh_hostkey_none}</span>";
    $hkbtn="";
    if($hk!=""){
        $hkline="<code>".htmlspecialchars($hk,ENT_QUOTES)."</code>";
        if($hkseen>0){$hkline.=" <small class='text-muted'>({rdpproxy_ssh_hostkey_since} ".htmlspecialchars(date("Y-m-d H:i",$hkseen),ENT_QUOTES).")</small>";}
        $hkbtn=" <a class='btn btn-xs btn-default' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?target-forget-hostkey=$ID')\"><i class='fas fa-eraser'></i> {rdpproxy_ssh_forget_hostkey}</a>";
    }
    $form[]="<tr class='rdpssh-ssh'><td style='width:30%;text-align:right;vertical-align:middle;padding-bottom:10px;' nowrap><span class=labelform>{rdpproxy_ssh_hostkey}:</span></td>"
        ."<td style='padding-bottom:10px;padding-left:19px'>$hkline$hkbtn</td></tr>";
    // Rappel quand le bastion envoie ses evenements a un SIEM : la trace des sessions SSH
    // (commandes, transcription) n'y part pas encore (meme phrase que fw.rdpproxy.siem.php).
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyToSIEM"))==1){
        $form[]="<tr class='rdpssh-ssh'><td></td><td style='padding:0 0 10px 19px'><div class='text-warning' style='font-size:12px;line-height:1.5'>"
            ."<i class='fas fa-triangle-exclamation'></i> {rdpproxy_siem_ssh_not_sent}</div></td></tr>";
    }
    if($ID>0){
        $form[]="<tr class='rdpssh-ssh rdpssh-deploy'><td style='width:30%;text-align:right;vertical-align:middle;padding-bottom:10px;' nowrap><span class=labelform>{rdpproxy_ssh_bastion_key}:</span></td>"
            ."<td style='padding-bottom:10px;padding-left:19px'><a class='btn btn-sm btn-primary' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?target-deploy-js=$ID')\"><i class='fas fa-key'></i> {rdpproxy_ssh_deploy_key}...</a>"
            ."<div class='text-muted' style='font-size:11px;margin-top:4px'>{rdpproxy_ssh_deploy_explain}</div></td></tr>";
    }

    if(rdpproxy_meta_locked()){
        if($ID==0){
            $html[]=$tpl->_ENGINE_parse_body($tpl->div_warning("{rdpproxy_meta_managed_title}||{rdpproxy_meta_tunnel_only}"));
        }elseif(trim($tunnel_agent)==""){
            // Cible ordinaire : lecture seule ; deploiement de cle et oubli de cle d'hote restent actifs.
            rdpproxy_meta_lock_form($tpl);
        }
    }
    $html[]=$tpl->form_outside($title,$form,null,$bt,$js,"AllowAddUsers");

    // Bascule RDP/VNC <-> SSH, saisie/coffre, nature du secret. Les id sont ceux calcules par
    // le template (md5(nom+suffixe)) ; chaque ligne est retrouvee par son <tr>.
    $idProto=$idf("proto_dest");$idAuth=$idf("ssh_auth");$idKind=$idf("ssh_secret_kind");$idPort=$idf("target_port");
    $rows=array(
        // field_password2 rend deux lignes (mot de passe + confirmation) : masquer les deux.
        "rdp"=>array($idf("target_password"),md5("2target_password".$sfx),$idf("target_password_clear"),$idf("nla_mode"),$idf("ask_credentials"),$idf("tls_policy_id"),$idf("tunnel_agent")),
        // lignes masquees pour une cible WEB (l'URL remplace hote, adresse, port et compte)
        "host"=>array($idf("target_host"),$idf("target_device"),$idf("target_port"),$idf("target_login")),
        "web"=>array($idf("web_url"),$idf("web_allow"),$idf("web_banner"),$idf("web_login_mode")),
        // champs du compte : visibles seulement si un mode de connexion est choisi
        "wlogin"=>array($idf("web_login_url"),$idf("web_login_user"),$idf("web_login_pass"),$idf("web_login_pass_clear"),$idf("web_login_insecure")),
        "ssh"=>array($idAuth,$idf("artica_appliance")),
        "vault"=>array($idKind),
        "pwd"=>array($idf("ssh_secret_pwd"),md5("2ssh_secret_pwd".$sfx)),
        "key"=>array($idf("ssh_secret_key")),
        "decoyonly"=>array($idf("decoy_enabled")),
        "decoyweb"=>array($idf("web_url")),
    );
    $idDecoy=$idf("decoy");
    $rowsjs=json_encode($rows);
    $unsupported=(!$ssh_supported && !$is_ssh)?"true":"false";
    $web_unsupported=(!$web_state["supported"] && !$is_web)?"true":"false";
    $idWLM=$idf("web_login_mode");
    // moteur sans webauth1 : seul « aucune » reste choisissable (une cible déjà configurée garde sa valeur)
    $wlogin_off=(!rdpproxy_web_login_state()["supported"])?"true":"false";
    $js_tail="<script>
(function(){
 var R=$rowsjs, P='$idProto', A='$idAuth', K='$idKind', PORT='$idPort';
 function row(id){var e=document.getElementById(id);if(!e)return null;var t=e.closest?e.closest('tr'):null;return t;}
 function show(ids,on){for(var i=0;i<ids.length;i++){var t=row(ids[i]);if(t)t.style.display=on?'':'none';}}
 function val(id){var e=document.getElementById(id);return e?e.value:'';}
 var DC='$idDecoy';
 window.RdpDecoyApply=function(){
  var c=document.getElementById(DC), on=!!(c&&c.checked);
  if(on){
   var all=[].concat(R.rdp,R.host,R.web,R.wlogin,R.ssh,R.vault,R.pwd,R.key);
   show(all,false);
   var cl=['tr.rdpweb-login','tr.rdpweb-web','tr.rdptun-rdp','tr.rdpssh-ssh','tr.rdpssh-deploy'];
   for(var k=0;k<cl.length;k++){var q=document.querySelectorAll(cl[k]);for(var m=0;m<q.length;m++){q[m].style.display='none';}}
   // WEB : seule l'adresse (facultative) reste saisissable, pour rendre l'entree credible
   if(val(P)==='WEB'){show(R.decoyweb,true);}
  }
  show(R.decoyonly,on);
 };
 (function(){var c=document.getElementById(DC);if(c){c.addEventListener('click',function(){window.RdpSshApply();});}})();
 window.RdpSshApply=function(){
  var ssh=(val(P)==='SSH'), web=(val(P)==='WEB'), vault=(val(A)==='vault'), kind=val(K);
  show(R.rdp,!ssh&&!web);
  show(R.host,!web);
  show(R.web,web);
  var wl=web&&(val('$idWLM')==='http'||val('$idWLM')==='form');
  show(R.wlogin,wl);
  var wli=document.querySelectorAll('tr.rdpweb-login');for(var q=0;q<wli.length;q++){wli[q].style.display=wl?'':'none';}
  var wr=document.querySelectorAll('tr.rdpweb-web');for(var w=0;w<wr.length;w++){wr[w].style.display=web?'':'none';}
  var tr=document.querySelectorAll('tr.rdptun-rdp');for(var u=0;u<tr.length;u++){tr[u].style.display=(!ssh&&!web)?'':'none';}
  show(R.ssh,ssh);
  show(R.vault,ssh&&vault);
  show(R.pwd,ssh&&vault&&kind==='password');
  show(R.key,ssh&&vault&&kind==='private_key');
  var rs=document.querySelectorAll('tr.rdpssh-ssh');for(var i=0;i<rs.length;i++){rs[i].style.display=ssh?'':'none';}
  var dp=document.querySelectorAll('tr.rdpssh-deploy');for(var j=0;j<dp.length;j++){dp[j].style.display=ssh?'':'none';}
  if(window.RdpDecoyApply){window.RdpDecoyApply();}
  var pt=document.getElementById(PORT);
  if(pt){if(ssh&&(pt.value===''||pt.value==='3389'||pt.value==='0')){pt.value='22';}
         if(!ssh&&pt.value==='22'){pt.value='3389';}}
 };
 RdpSshApply();
 setTimeout(function(){
  if($unsupported){
   var o=document.querySelector('#'+P+' option[value=\"SSH\"]');
   if(o){o.disabled=true;if(window.jQuery){jQuery('#'+P).trigger('chosen:updated');}}
  }
  if($wlogin_off){
   var wm=document.getElementById('$idWLM');
   if(wm){var opts=wm.options;for(var z=0;z<opts.length;z++){if(opts[z].value!=='none'&&!opts[z].selected){opts[z].disabled=true;}}
    if(window.jQuery){jQuery(wm).trigger('chosen:updated');}}
  }
  if($web_unsupported){
   var ow=document.querySelector('#'+P+' option[value=\"WEB\"]');
   if(ow){ow.disabled=true;if(window.jQuery){jQuery('#'+P).trigger('chosen:updated');}}
  }
 },1300);
})();
</script>";
    echo implode("\n",$html).$js_tail;

}
function target_enable(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["target-enable"]);
    $enabled=rdpproxy_target_toggle($ID,$error);
    header("content-type: application/x-javascript");
    if($error!=""){$tpl->js_mysql_alert($error);return;}
    admin_tracks(($enabled?"Enabled":"Disabled")." RDS Proxy target ".rdpc_name("target",$ID)." (#$ID)");
    echo "LoadAjaxTiny('target-status-$ID','$page?target-status=$ID');";
}

function targets_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["target-id"]);
    $proto=strtoupper(rdpc_post("proto_dest"));
    $fields=array(
        "alias"=>rdpc_post("alias"),"designation"=>rdpc_post("designation"),
        "proto_dest"=>$proto,"target_host"=>rdpc_post("target_host"),
        "target_device"=>rdpc_post("target_device"),"target_port"=>rdpc_post("target_port"),
        "target_login"=>rdpc_post("target_login"),"target_password"=>rdpc_post("target_password"),
        "nla_mode"=>rdpc_post("nla_mode"));
    // RDP seulement (le daemon refuse la case sur un autre protocole) ; absent = garde
    if(rdpc_post("target_password_clear")=="1"){$fields["target_password_clear"]="1";}
    if(isset($_POST["ask_credentials"])){
        $fields["ask_credentials"]=($proto=="RDP" && rdpc_post("ask_credentials")=="1")?"1":"0";
    }
    if(isset($_POST["tls_policy_id"]) && rdpc_post("tls_policy_id")!==""){
        $fields["tls_policy_id"]=$proto=="RDP"?(string)intval(rdpc_post("tls_policy_id")):"0";
    }
    $cur=array();
    if($ID>0){
        $cur=rdpc_target_current($ID,$error);
        if($cur===false){echo $tpl->post_error($error);return;}
    }
    // Leurre : case cochee, ou cible deja leurre (la case figee n'est pas postee). Le daemon
    // refuse tout secret, hote ou tunnel ; la fiche part donc propre, sans fusion avec l'existant.
    $isDecoy=(rdpc_post("decoy")=="1");
    if(!$isDecoy && $ID>0){
        $tgd=rdpproxy_target($ID,$derr);
        if($derr=="" && !empty($tgd["decoy"])){$isDecoy=true;}
    }
    if($isDecoy){
        $dfields=array("alias"=>rdpc_post("alias"),"designation"=>rdpc_post("designation"),
            "proto_dest"=>$proto,"target_host"=>"","target_device"=>"","target_port"=>"0",
            "target_login"=>"","target_password"=>"","nla_mode"=>"0","dont_resolve"=>"0",
            "ssh_auth"=>"","ssh_secret_kind"=>"","artica_appliance"=>"0","web_url"=>($proto=="WEB"?trim(rdpc_post("web_url","raw")):""),"web_allow"=>"",
            "tunnel_agent"=>"","ask_credentials"=>"0","tls_policy_id"=>"0","decoy"=>"1");
        // armement : poste seulement quand la case est rendue (absent = le daemon garde l'etat)
        if(isset($_POST["decoy_enabled"])){$dfields["decoy_enabled"]=(rdpc_post("decoy_enabled")=="1")?"1":"0";}
        elseif($ID==0){$dfields["decoy_enabled"]="1";}
        $err=rdpproxy_target_save($ID,$dfields);
        if($err!=""){echo $tpl->post_error($err);return;}
        admin_tracks_post(($ID==0?"Created":"Updated")." RDS Proxy decoy target");
        return;
    }
    $fields["web_url"]="";$fields["web_allow"]="";
    if($proto=="WEB"){
        // Une cible WEB n'est pas proposee par un bastion qui ne sait pas la servir ; une cible
        // deja WEB reste modifiable. Le daemon valide l'URL et la liste, et deduit hote et port.
        $ws=rdpproxy_web_state();
        if(!$ws["supported"] && (!isset($cur["proto_dest"]) || strtoupper($cur["proto_dest"])!="WEB")){
            echo $tpl->post_error("{rdpproxy_web_unavailable_".$ws["reason"]."}");return;
        }
        $fields["web_url"]=trim(rdpc_post("web_url","raw"));
        $fields["web_allow"]=rdpc_post("web_allow","raw");
        // bandeau du nom de la cible : envoye seulement s'il est poste (absent = garde par le daemon)
        if(isset($_POST["web_banner"])){$fields["web_banner"]=rdpc_post("web_banner")=="1"?"1":"0";}
        if($fields["web_url"]===""){
            echo $tpl->post_error("{rdpproxy_web_url}: {cannot_be_a_null_value}");return;
        }
        // Connexion automatique (lot A) : le daemon valide (origine dans la liste blanche, http,
        // compte complet) et rend un code traduit par rdpproxy_ssh_error().
        $wlm=rdpc_post("web_login_mode");
        if(!in_array($wlm,array("none","http","form"),true)){$wlm="none";}
        $fields["web_login_mode"]=$wlm;
        $fields["web_login_url"]=trim(rdpc_post("web_login_url","raw"));
        $fields["web_login_user"]=trim(rdpc_post("web_login_user","raw"));
        $fields["web_login_insecure"]=rdpc_post("web_login_insecure")=="1"?"1":"0";
        // mot de passe : envoyé seulement s'il est saisi (absent = gardé), effacé sur demande
        $wlp=rdpc_post("web_login_pass","raw");
        if($wlp!==""){$fields["web_login_pass"]=$wlp;}
        if(rdpc_post("web_login_pass_clear")=="1"){$fields["web_login_pass_clear"]="1";}
        if($wlm=="none"){$fields["web_login_url"]="";$fields["web_login_user"]="";$fields["web_login_insecure"]="0";}
        // aucun identifiant de cible RDP pour une page ; hote et port viennent de l'URL
        $fields["target_login"]="";$fields["target_password"]="";$fields["target_host"]="";
        $fields["target_device"]="";$fields["target_port"]="443";$fields["nla_mode"]="0";
        $fields["ssh_auth"]="";$fields["ssh_secret_kind"]="";$fields["artica_appliance"]="0";
        if($ID>0){$fields["tunnel_agent"]="";}
    }elseif($proto=="SSH"){
        // SSH n'est pas propose par un bastion qui ne sait pas le relayer ; une cible deja SSH
        // reste modifiable.
        if(!rdpproxy_ssh_supported() && (!isset($cur["proto_dest"]) || strtoupper($cur["proto_dest"])!="SSH")){
            echo $tpl->post_error("{rdpproxy_ssh_needs_update}");return;
        }
        $auth=rdpc_post("ssh_auth")=="vault"?"vault":"prompt";
        $kind=rdpc_post("ssh_secret_kind");
        if(!in_array($kind,array("password","private_key","bastion_key"),true)){$kind="password";}
        if(intval($fields["target_port"])<1){$fields["target_port"]="22";}
        $fields["ssh_auth"]=$auth;
        $fields["ssh_secret_kind"]=($auth=="vault")?$kind:"";
        $fields["artica_appliance"]=rdpc_post("artica_appliance")=="1"?"1":"0";
        // Secret : champ vide = garder ; la cle ssh_secret n'est envoyee que renseignee.
        $secret="";
        if($auth=="vault"){
            if($kind=="password"){$secret=rdpc_post("ssh_secret_pwd","raw");}
            elseif($kind=="private_key"){$secret=rdpc_post("ssh_secret_key","raw");}
        }
        if(trim($secret)!==""){$fields["ssh_secret"]=$secret;}
        if($auth=="vault" && trim($fields["target_login"])===""){
            echo $tpl->post_error("{username}: {cannot_be_a_null_value}");return;
        }
        // Les champs RDP n'ont pas de sens pour SSH.
        $fields["target_password"]="";
        if($ID>0){$fields["tunnel_agent"]="";}
    }else{
        // RDP / VNC : le champ est rendu, on l'envoie (vide = efface l'agent). Le daemon valide l'id.
        $fields["tunnel_agent"]=trim(rdpc_post("tunnel_agent"));
        // Identifiants facultatifs : vides, authhook reprend ceux saisis au bastion (RDPEngine.go).
        $fields["ssh_auth"]="";$fields["ssh_secret_kind"]="";$fields["artica_appliance"]="0";
    }
    if($ID>0){$fields=array_merge($cur,$fields);}
    $err=rdpproxy_target_save($ID,$fields);
    if($err!=""){echo $tpl->post_error($err);return;}
    // admin_tracks_post() masque les champs *secret*/*pass* (admin_tracks_mask_value).
    admin_tracks_post(($ID==0?"Created":"Updated")." RDS Proxy target");
}

// ---------------------------------------------------------------- cibles SSH : cle du bastion, cle d'hote

function target_deploy_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["target-deploy-js"]);
    $tpl->js_dialog2("{rdpproxy_ssh_deploy_key}","$page?target-deploy-popup=$ID",650);
}

function target_deploy_popup(){
    $tpl=new template_admin();
    $ID=intval($_GET["target-deploy-popup"]);
    $page=CurrentPageName();
    $tg=rdpproxy_target($ID,$error);
    if($error!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error($error));return;}
    // Aucun champ prerempli : le mot de passe est saisi, envoye une fois, jamais relu.
    $tpl->field_hidden("target-deploy-id",$ID);
    $form[]=$tpl->field_text("ssh_deploy_login","{username}",isset($tg["target_login"])?(string)$tg["target_login"]:"",true);
    $form[]=$tpl->field_password("ssh_deploy_password","{password}","",true);
    $js="dialogInstance2.close();dialogInstance1.close();Loadjs('$page?target-js=$ID');";
    echo $tpl->_ENGINE_parse_body($tpl->div_explain("{rdpproxy_ssh_deploy_key}||{rdpproxy_ssh_deploy_explain}"));
    echo $tpl->form_outside("",$form,null,"{rdpproxy_ssh_deploy_key}",$js,"AllowAddUsers");
}

// N'ecrit RIEN en cas de succes. Le mot de passe n'est ni stocke, ni journalise
// (admin_tracks avec un texte fixe, jamais admin_tracks_post), ni renvoye.
function target_deploy_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["target-deploy-id"]);
    $login=rdpc_post("ssh_deploy_login");
    $password=rdpc_post("ssh_deploy_password","raw");
    unset($_POST["ssh_deploy_password"]);
    if($login==="" || $password===""){echo $tpl->post_error("{cannot_be_a_null_value}");return;}
    $err=rdpproxy_target_deploy_key($ID,$login,$password);
    $password="";
    if($err!=""){
        admin_tracks("Failed to deploy the RDS Proxy bastion SSH key on target #$ID");
        echo $tpl->post_error($err);return;
    }
    admin_tracks("Deployed the RDS Proxy bastion SSH key on target #$ID");
}

function target_forget_hostkey_ask(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["target-forget-hostkey"]);
    // Pas d'alias ici : le texte part dans une chaine JS (swal html:true) ou un antislash
    // reconstituerait du HTML actif. L'identifiant numerique suffit.
    $tpl->js_confirm_execute("{rdpproxy_ssh_forget_confirm} #$ID","target-forget-hostkey",$ID,
        "dialogInstance1.close();Loadjs('$page?target-js=$ID');");
}

function target_forget_hostkey_perform(){
    $tpl=new template_admin();
    // js_confirm_execute() affiche la reponse telle quelle dans un swal html:true : echapper.
    if(!$tpl->IsSecurity("AllowAddUsers")){echo htmlspecialchars(strip_tags($tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}")),ENT_QUOTES);return;}
    $ID=intval($_POST["target-forget-hostkey"]);
    $err=rdpproxy_target_forget_hostkey($ID);
    if($err!=""){
        echo htmlspecialchars(strip_tags($tpl->_ENGINE_parse_body($err)),ENT_QUOTES);return;
    }
    admin_tracks("Forgot the SSH host key of RDS Proxy target #$ID");
}

function member_ad_save(){
    $ID=intval($_POST["member-ad-id"]);
    $eol=strtotime(rdpc_post("endoflife","xss"));
    $err=rdpproxy_member_save($ID,array("username"=>rdpc_post("username","xss"),"ad_group"=>"1","endoflife"=>intval($eol)));
    if($err!=""){echo $err;return;}
    admin_tracks_post(($ID==0?"Created":"Updated")." RDS Proxy Active Directory group member");
}

function member_save(){
    $ID=intval($_POST["member-id"]);
    $eol=strtotime(rdpc_post("endoflife","xss"));
    $err=rdpproxy_member_save($ID,array("username"=>rdpc_post("username","xss"),"endoflife"=>intval($eol),"password"=>rdpc_post("password","raw")));
    if($err!=""){echo $err;return;}
    admin_tracks_post(($ID==0?"Created":"Updated")." RDS Proxy member");
}



// ---------------------------------------------------------------------------
// Tableaux awc-* de la page. Un seul gabarit pour les neuf tableaux : carte,
// en-tete, corps identifie (pagination + filtre), pied avec le compte.
// ---------------------------------------------------------------------------

// Ouvre la carte et le tableau. L'id 'table-$t' est conserve tel quel.
function rdpc_table_open($t,$headers):string{
    $h=array();
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table id='table-$t' class='awc-tbl'><thead><tr>";
    foreach($headers as $th){$h[]=$th;}
    $h[]="</tr></thead><tbody id='rows-$t'>";
    return @implode("",$h);
}

// Ferme le tableau ; le compte a gauche, la pagination a droite.
function rdpc_table_close($t,$count):string{
    $count=intval($count);
    return "</tbody></table></div><div class='awc-foot'><span>{items}: $count</span>"
        ."<span class='awc-pg' id='pager-$t'></span></div></div></div>";
}

// Ligne d'etat vide : une phrase, jamais un <tbody> vide.
function rdpc_empty($colspan,$text):string{
    $colspan=intval($colspan);
    return "<tr><td colspan='$colspan'><div class='awc-empty'>$text</div></td></tr>";
}

// Lien d'edition d'une ligne. $text est echappe ici : il vient de la base.
function rdpc_link($text,$js):string{
    $text=htmlspecialchars((string)$text,ENT_QUOTES);
    if($js==""){return $text;}
    return "<a href=\"javascript:blur();\" OnClick=\"$js\">$text</a>";
}

// Bloc d'identite : tuile d'icone, nom (deja mis en forme), metadonnee.
function rdpc_ident($ico,$namehtml,$metahtml="",$off=false):string{
    $icls=$off?"awc-ico awc-ico-off":"awc-ico";
    $ncls=$off?"awc-name awc-name-off":"awc-name";
    $h="<div class='awc-ident'><span class='$icls'><i class='$ico'></i></span><span>";
    $h.="<span class='$ncls'>$namehtml</span>";
    if(strlen(trim((string)$metahtml))>0){$h.="<div class='awc-meta'>$metahtml</div>";}
    $h.="</span></div>";
    return $h;
}

// Bouton d'action icone. Sans le privilege, il n'est pas rendu du tout : la
// verification cote serveur reste celle des handlers existants.
// $metaOpen : action qui reste locale sous le verrou Artica Meta (cible tunnel).
function rdpc_act($js,$ico,$title,$danger=false,$security="AllowAddUsers",$metaOpen=false):string{
    $tpl=new template_admin();
    if(!$tpl->IsSecurity($security)){return "";}
    if(!$metaOpen && rdpproxy_meta_locked()){return "";}
    $cls=$danger?"awc-act awc-act-danger":"awc-act";
    return "<a class='$cls' title=\"$title\" href=\"javascript:blur();\" OnClick=\"$js\"><i class='$ico'></i></a>";
}

// Bouton "lier" des fenetres de selection : une pastille, comme les badges.
function rdpc_link_badge($js,$security="AllowAddUsers"):string{
    $tpl=new template_admin();
    if(!$tpl->IsSecurity($security)){return "";}
    if(rdpproxy_meta_locked()){return "";}
    return "<a class='awc-badge awc-badge-on' href=\"javascript:blur();\" OnClick=\"$js\"><i class='fas fa-link'></i>&nbsp;{link}</a>";
}

// Les scripts, TOUJOURS apres _ENGINE_parse_body() : NoSpinner, les fonctions
// des icon_check, la pagination, puis le filtre s'il y a une barre de recherche.
function rdpc_scripts($tpl,$t,$filter=false,$extra=""):string{
    $s="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$extra</script>";
    $s.=awc_pager_js(25,"rows-$t","pager-$t");
    if($filter){$s.=awc_filter_js("search-$t","rows-$t","pager-$t");}
    return $s;
}

function page():bool{
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");

    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {policies}",
        "fa-solid fa-building-shield","{rdpproxy_policies_explain_web}",
        "$page?tabs=yes","rdpproxy-connections",
        "progress-rdpcnx-restart","","table-rdpcnx-status");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_RDPPROXY}",$html);
        echo $tpl->build_firewall();return true;}
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}

function groups_start():bool{
    $page=CurrentPageName();
    echo "<div id='rdpproxy-groups-table' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');</script>";
    return true;
}

function members_start(){
    $page=CurrentPageName();
    echo "<div id='rdpproxy-members-table' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-members-table','$page?members-table=yes');</script>";
}
function targets_start(){
    $page=CurrentPageName();
    echo "<div id='rdpproxy-target-table' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-target-table','$page?targets-table=yes');</script>";
}
function group_members_start(){
    $page=CurrentPageName();
    $ID=intval($_GET["groups-members-start"]);
    echo "<div id='rdpproxy-members-table-$ID' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-members-table-$ID','$page?group-members-table=$ID');</script>";
}
function group_targets_start(){
    $page=CurrentPageName();
    $ID=intval($_GET["groups-targets-start"]);
    echo "<div id='rdpproxy-targets-table-$ID' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-targets-table-$ID','$page?groups-targets-table=$ID');</script>";
}
function group_network_start(){
    $page=CurrentPageName();
    $ID=intval($_GET["groups-network-start"]);
    echo "<div id='rdpproxy-network-table-$ID' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-network-table-$ID','$page?groups-network-table=$ID');</script>";
}
function group_time_start(){
    $page=CurrentPageName();
    $ID=intval($_GET["group-time-start"]);
    echo "<div id='rdpproxy-time-table-$ID' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-time-table-$ID','$page?group-time-table=$ID');</script>";
}

// Onglet Membres : tuiles de synthese, filtres rapides, tableau. Tout vient des
// routes du daemon (membres, politiques avec leurs cibles, inscriptions TOTP) ;
// rien n'est affiche que le bastion n'applique pas.
function members_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=md5(time().rand(0,65595));
    $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
    $btdisabled=false;
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($EnableActiveDirectoryFeature==0){$btdisabled=true;}
    if(!$CORP_LICENSE){$btdisabled=true;}

    $error="";
    $results=rdpproxy_members($error);
    if($error!=""){echo $tpl->div_error($error);return;}

    // Politiques, avec leurs cibles (un appel par politique : elles sont peu nombreuses).
    $perr="";
    $policies=array();
    foreach(rdpproxy_policies($perr) as $p){
        $derr="";
        $d=rdpproxy_policy(intval($p["id"]),$derr);
        $p["targets"]=($derr==""&&isset($d["targets"])&&is_array($d["targets"]))?$d["targets"]:array();
        $policies[intval($p["id"])]=$p;
    }
    // Inscriptions TOTP, par identite canonique (meme regle qu'authhook).
    $merr="";
    $mfa=array();
    foreach(rdpproxy_mfa_users($merr) as $u){$mfa[rdpm_mfa_key($u["username"])]=$u;}

    $now=time();
    $soon=15*86400;
    $nLocal=0;$nAD=0;$nExpired=0;$nSoon=0;$nNoPolicy=0;
    $rows=array();
    foreach($results as $ligne){
        $m=rdpm_facts($ligne,$policies,$mfa,$now,$soon);
        if($m["ad"]){$nAD++;}else{$nLocal++;}
        if($m["expired"]){$nExpired++;}elseif($m["soon"]){$nSoon++;}
        if(count($m["pols"])==0){$nNoPolicy++;}
        $rows[]=rdpm_row($tpl,$page,$ligne,$m);
    }

    $pTotal=count($policies);$pOn=0;$pRec=0;
    foreach($policies as $p){
        if(empty($p["enabled"])){continue;}
        $pOn++;
        if(!empty($p["recording"])){$pRec++;}
    }
    $total=count($rows);

    $html=array();
    $html[]="<div class='rdpm ak'>";
    $html[]="<div class='ak-grid-4 rdpm-tiles'>";
    $html[]=rdpm_tile("fas fa-users","ak-ok","{rdpproxy_members_total}",$total,
        "<b>$nLocal</b> {rdpproxy_local_accounts} &bull; <b>$nAD</b> {rdpproxy_ad_groups}");
    $html[]=rdpm_tile("fas fa-shield-halved","ak-info","{rdpproxy_active_policies}","$pOn<small>/$pTotal</small>",
        ($pTotal-$pOn)>0?"<b>".($pTotal-$pOn)."</b> {rdpproxy_paused}":"{rdpproxy_all_policies_on}");
    $expCls=($nExpired+$nSoon)>0?"ak-warn rdpm-alert":"ak-off";
    $html[]=rdpm_tile("far fa-clock",$expCls,"{rdpproxy_expirations}",$nExpired+$nSoon,
        "<b>$nExpired</b> {rdpproxy_expired_n} &bull; <b>$nSoon</b> {rdpproxy_expiring_15d}");
    if(rdpproxy_video_supported()){
        $html[]=rdpm_tile("fas fa-video","rdpm-violet","{video_recording}","$pRec<small>/$pOn</small>",
            "{rdpproxy_recording_policies}");
    }else{
        $html[]=rdpm_tile("fas fa-video-slash","ak-off rdpm-muted","{video_recording}","&ndash;",
            "{rdpproxy_video_unavailable}");
    }
    $html[]="</div>";

    // Filtres rapides + recherche : tout se fait dans le navigateur.
    $filters=array(
        "all"=>array("{all}",$total),
        "local"=>array("{rdpproxy_local_accounts_f}",$nLocal),
        "ad"=>array("{active_directory_groups}",$nAD),
        "soon"=>array("{expires_soon}",$nSoon),
        "expired"=>array("{rdpproxy_expired_f}",$nExpired),
        "nopolicy"=>array("{rdpproxy_no_policy}",$nNoPolicy),
    );
    $html[]="<div class='ak-tools'>";
    $html[]="<label class='ak-search rdpm-search'><i class='fas fa-search'></i><input type='text' id='search-$t' autocomplete='off' placeholder=\"{search}\"></label>";
    $html[]="<div class='ak-seg rdpm-filters' id='filters-$t'>";
    foreach($filters as $k=>$f){
        if($k!="all" && $f[1]==0){continue;}
        $cls=$k=="all"?"ak-seg-b on":"ak-seg-b";
        $html[]="<button type='button' class='$cls' data-f='$k'>{$f[0]} <span>{$f[1]}</span></button>";
    }
    $html[]="</div></div>";

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table id='table-$t' class='awc-tbl rdpm-tbl'><thead><tr>";
    $html[]="<th>{rdpproxy_col_member}</th><th>{rdpproxy_col_source}</th><th>{validity}</th>";
    $html[]="<th>{policies}</th><th>{rdpproxy_col_targets}</th><th class='awc-right'>{rdpproxy_col_status}</th>";
    $html[]="</tr></thead><tbody id='rows-$t'>";
    if($total==0){
        $html[]=rdpc_empty(6,"{no_item}");
    }else{
        $html[]=@implode("\n",$rows);
        $html[]="<tr id='nomatch-$t' style='display:none'><td colspan='6'><div class='awc-empty'>{no_data}</div></td></tr>";
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{rdpproxy_members}: <b id='count-$t'>$total</b>".($total>0?" / $total":"")."</span></div>";
    $html[]="</div></div></div>";

    // Boutons dans l'en-tete (TINY). Sans licence ou sans Active Directory, le
    // bouton AD reste visible mais grise : FalseLicense est toujours refuse.
    if(!rdpproxy_meta_locked()){
        $topbuttons[]=array("Loadjs('$page?member-js=0')","far fa-user-plus","{new_member}","AllowAddUsers");
        $topbuttons[]=array("Loadjs('$page?member-ad-js=0')","fas fa-users","{active_directory_group}",$btdisabled?"FalseLicense":"AllowAddUsers");
    }
    $topbuttons[]=array("LoadAjax('rdpproxy-members-table','$page?members-table=yes');",ico_refresh,"{reload}");
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_RDPPROXY} v$PrivoxyVersion {rdpproxy_members}";
    $TINY_ARRAY["ICO"]="fa-solid fa-building-shield";
    $TINY_ARRAY["EXPL"]="{rdpproxy_policies_explain_web}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $js="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny\n".rdpm_filter_js($t)."</script>";
    echo awc_table_style().rdpm_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js;
}

// Identite canonique d'un compte pour mfa_users : minuscules, apres le dernier
// "\", avant le premier "@" (normalizeMFAUser du daemon, MFA.Normalize d'authhook).
function rdpm_mfa_key($name):string{
    $n=strtolower(trim((string)$name));
    $p=strrpos($n,"\\");
    if($p!==false){$n=substr($n,$p+1);}
    $p=strpos($n,"@");
    if($p!==false){$n=substr($n,0,$p);}
    return trim($n);
}

// Ce que la ligne affiche, decide une fois : type, echeance, politiques,
// cibles atteignables, second facteur, statut et categories de filtre.
function rdpm_facts($l,$policies,$mfa,$now,$soon):array{
    $f=array();
    $f["ad"]=!empty($l["ad_group"]);
    $eol=intval($l["endoflife"]);
    $f["eol"]=$eol;
    $f["expired"]=$eol>0 && $now>$eol;
    $f["soon"]=$eol>0 && !$f["expired"] && ($eol-$now)<=$soon;

    $f["pols"]=array();
    $targets=array();
    $mfaRequired=false;
    $anyOn=false;
    $refs=isset($l["policies"])&&is_array($l["policies"])?$l["policies"]:array();
    foreach($refs as $r){
        $gpid=intval($r["id"]);
        $p=isset($policies[$gpid])?$policies[$gpid]:array("id"=>$gpid,"name"=>$r["name"],"enabled"=>true,"targets"=>array());
        $on=!empty($p["enabled"]);
        if($on){$anyOn=true;}
        if($on && !empty($p["mfa_required"])){$mfaRequired=true;}
        $f["pols"][]=$p;
        foreach($p["targets"] as $tg){
            $tid=intval($tg["id"]);
            $reach=$on && !empty($tg["enabled"]);
            if(!isset($targets[$tid])){$targets[$tid]=$tg;$targets[$tid]["reach"]=$reach;}
            elseif($reach){$targets[$tid]["reach"]=true;}
        }
    }
    // Atteignables d'abord.
    uasort($targets,function($a,$b){return intval($b["reach"])-intval($a["reach"]);});
    $f["targets"]=array_values($targets);

    $f["mfa"]="";
    if(!$f["ad"]){
        $k=rdpm_mfa_key($l["username"]);
        $u=isset($mfa[$k])?$mfa[$k]:null;
        if($u!==null && !empty($u["locked"])){$f["mfa"]="locked";}
        elseif($u!==null && !empty($u["exempt"])){$f["mfa"]="exempt";}
        elseif($u!==null && !empty($u["enrolled"])){$f["mfa"]="enrolled";}
        elseif($mfaRequired){$f["mfa"]="pending";}
    }elseif($mfaRequired){
        $f["mfa"]="required";
    }

    if($f["expired"]){$f["status"]="expired";}
    elseif(count($f["pols"])==0){$f["status"]="nopolicy";}
    elseif(!$anyOn){$f["status"]="paused";}
    else{$f["status"]="active";}

    $cats=array($f["ad"]?"ad":"local");
    if($f["expired"]){$cats[]="expired";}
    if($f["soon"]){$cats[]="soon";}
    if(count($f["pols"])==0){$cats[]="nopolicy";}
    $f["cats"]=@implode(" ",$cats);
    return $f;
}

function rdpm_row($tpl,$page,$l,$m):string{
    $ID=intval($l["id"]);
    $md=md5(serialize($l));
    $js="Loadjs('$page?member-js=$ID')";
    $raw=(string)$l["username"];
    $name=$m["ad"]?rdpproxy_cn($raw):$raw;
    $search=array($name,$raw);

    // Membre & identite
    if($m["expired"]){$tone="ak-err";}
    elseif($m["soon"]){$tone="ak-warn";}
    elseif($m["ad"]){$tone="ak-info";}
    else{$tone="ak-ok";}
    $ico=$m["ad"]?"fas fa-users":"fas fa-user";
    $badges=array(
        "required"=>array("rdpm-violet","fas fa-lock","{rdpproxy_mfa_badge_required}"),
        "pending"=>array("ak-warn","fas fa-hourglass-half","{rdpproxy_mfa_badge_pending}"),
        "enrolled"=>array("ak-ok","fas fa-mobile-screen","{rdpproxy_mfa_badge_enrolled}"),
        "exempt"=>array("ak-off","fas fa-user-check","{rdpproxy_mfa_badge_exempt}"),
        "locked"=>array("ak-err","fas fa-user-lock","{rdpproxy_mfa_badge_locked}"),
    );
    $who="<div class='rdpm-id'><span class='ak-ico ak-ico-sm ak-ico-sq $tone'><i class='$ico'></i></span><div class='rdpm-id-t'>";
    $who.="<div class='rdpm-name'>".rdpc_link($name,$js)."</div>";
    if($m["ad"] && $name!=$raw){
        $who.="<div class='rdpm-dn' title=\"".htmlspecialchars($raw,ENT_QUOTES)."\">".htmlspecialchars($raw,ENT_QUOTES)."</div>";
    }
    if(isset($badges[$m["mfa"]])){
        $b=$badges[$m["mfa"]];
        $who.="<span class='ak-tag rdpm-b {$b[0]}'><i class='{$b[1]}'></i>{$b[2]}</span>";
    }
    $who.="</div></div>";

    // Source & type
    if($m["ad"]){
        $src="<span class='rdpm-src'><i class='fab fa-windows'></i>{active_directory_group}</span>"
            ."<div class='rdpm-sub'>{rdpproxy_src_ad}</div>";
    }else{
        $src="<span class='rdpm-src'><i class='fas fa-key'></i>{rdpproxy_local_account}</span>"
            ."<div class='rdpm-sub'>{rdpproxy_src_local}</div>";
    }

    // Validite
    if($m["eol"]==0){
        $val="<div class='rdpm-val'><i class='fas fa-infinity'></i>{permanent}</div>";
    }else{
        $date=htmlspecialchars($tpl->time_to_date($m["eol"]),ENT_QUOTES);
        if($m["expired"]){
            $val="<div class='rdpm-val rdpm-red'><i class='fas fa-circle-xmark'></i>{expired}</div>"
                ."<div class='rdpm-sub'>$date</div>";
        }else{
            $days=intval(ceil(($m["eol"]-time())/86400));
            $cls=$m["soon"]?"rdpm-val rdpm-amber":"rdpm-val";
            $ic=$m["soon"]?"fas fa-triangle-exclamation":"far fa-clock";
            $val="<div class='$cls'><i class='$ic'></i>".str_replace("%d",$days,$tpl->_ENGINE_parse_body("{rdpproxy_in_days}"))."</div>"
                ."<div class='rdpm-sub'>$date</div>";
        }
    }

    // Strategies
    $chips=array();
    foreach($m["pols"] as $p){
        $gpid=intval($p["id"]);
        $pn=(string)$p["name"];
        if(trim($pn)==""){continue;}
        $search[]=$pn;
        $on=!empty($p["enabled"]);
        $ext="";
        if($on && !empty($p["recording"]) && rdpproxy_video_supported()){$ext.="<i class='fas fa-video' title=\"{video_recording}\"></i>";}
        if($on && !empty($p["mfa_required"])){$ext.="<i class='fas fa-lock' title=\"{require_second_factor}\"></i>";}
        $cls=$on?"rdpm-pol":"rdpm-pol rdpm-pol-off";
        $title=$on?"":" title=\"{rdpproxy_policy_paused}\"";
        $chips[]="<span class='$cls'$title><i class='fas fa-shield-halved'></i>"
            .rdpc_link($pn,"Loadjs('$page?group-js=$gpid')").$ext."</span>";
    }
    $pol=count($chips)>0?"<div class='ak-chips'>".@implode("",$chips)."</div>"
        :"<span class='rdpm-warn'><i class='fas fa-triangle-exclamation'></i>{rdpproxy_no_policy}</span>";

    // Cibles atteignables
    $tl=array();$rest=array();
    foreach($m["targets"] as $i=>$tg){
        $alias=rdpproxy_cn($tg["alias"]);
        $host=(string)$tg["target_host"];
        $search[]=$alias;$search[]=$host;
        if($i>=2){$rest[]=$alias;continue;}
        $proto=strtoupper(htmlspecialchars((string)$tg["proto_dest"],ENT_QUOTES));
        $port=intval($tg["target_port"]);
        $cls=$tg["reach"]?"rdpm-tg":"rdpm-tg rdpm-tg-off";
        $tl[]="<div class='$cls' title=\"".htmlspecialchars($host,ENT_QUOTES)."\"><span class='rdpm-tg-n'>"
            .htmlspecialchars($alias,ENT_QUOTES)."</span><span class='ak-tag ak-warn'>$proto".($port>0?" : $port":"")."</span></div>";
    }
    if(count($rest)>0){
        $tl[]="<span class='ak-count' title=\"".htmlspecialchars(@implode(", ",$rest),ENT_QUOTES)."\">+".count($rest)."</span>";
    }
    $tgt=count($tl)>0?@implode("",$tl):"<span class='ak-muted'>&ndash;</span>";

    // Statut & actions
    $st=array(
        "active"=>array("ak-ok","{rdpproxy_status_active}"),
        "expired"=>array("ak-err","{expired}"),
        "nopolicy"=>array("ak-warn","{rdpproxy_no_policy}"),
        "paused"=>array("ak-off","{rdpproxy_policies_paused}"),
    );
    $s=$st[$m["status"]];
    // Le badge hors de awc-acts : ce bloc est estompe tant que la ligne n'est pas survolee.
    $acts="<div class='rdpm-acts'><span class='ak-pill {$s[0]}'>{$s[1]}</span><div class='awc-acts'>"
        .rdpc_act($js,"fas fa-pen","{rdpproxy_edit_member}")
        .rdpc_act("Loadjs('$page?member-delete=$ID&md=$md')",ico_trash,"{delete}",true)."</div></div>";

    $q=htmlspecialchars(strtolower(@implode(" ",$search)),ENT_QUOTES);
    $rowcls=$m["expired"]?" class='rdpm-row-exp'":($m["soon"]?" class='rdpm-row-soon'":"");
    return "<tr id='$md'$rowcls data-cat='{$m["cats"]}' data-q=\"$q\">"
        ."<td>$who</td><td>$src</td><td class='awc-nowrap'>$val</td><td>$pol</td><td>$tgt</td><td>$acts</td></tr>";
}

function rdpm_tile($ico,$cls,$title,$value,$body):string{
    return "<div class='ak-fig $cls'><div class='ak-fig-txt'><span class='ak-fig-l'>$title</span>"
        ."<span class='ak-fig-v'>$value</span><span class='ak-fig-s'>$body</span></div>"
        ."<span class='ak-ico'><i class='$ico'></i></span></div>";
}

// Recherche + filtre rapide combines, sur data-q et data-cat des lignes.
function rdpm_filter_js($t):string{
    return "(function(){var i=document.getElementById('search-$t'),fb=document.getElementById('filters-$t'),body=document.getElementById('rows-$t');
if(!i||!fb||!body){return;}var cat='all',cnt=document.getElementById('count-$t'),nm=document.getElementById('nomatch-$t');
function run(){var q=i.value.toLowerCase().trim(),n=0,rs=body.querySelectorAll('tr[data-cat]');
for(var k=0;k<rs.length;k++){var r=rs[k],ok=(cat==='all'||(' '+r.getAttribute('data-cat')+' ').indexOf(' '+cat+' ')>=0)&&(q===''||(r.getAttribute('data-q')||'').indexOf(q)>=0);
r.style.display=ok?'':'none';if(ok){n++;}}if(cnt){cnt.textContent=n;}if(nm){nm.style.display=n===0?'':'none';}}
i.addEventListener('input',run);
var bs=fb.querySelectorAll('button[data-f]');for(var k=0;k<bs.length;k++){bs[k].addEventListener('click',function(){
for(var j=0;j<bs.length;j++){bs[j].className='ak-seg-b';}this.className='ak-seg-b on';cat=this.getAttribute('data-f');run();});}})();";
}

function rdpm_style():string{
    if(isset($GLOBALS["RDPM_STYLE"])){return "";}
    $GLOBALS["RDPM_STYLE"]=true;
    return "<style>
.rdpm{margin:10px 0;color:#111d23}
.rdpm-tiles{margin-bottom:14px}
.rdpm-tiles .ak-fig-s{color:var(--ak-muted)}.rdpm-tiles .ak-fig-s b{color:#334155}
.rdpm-tiles small{font-size:14px;font-weight:500;color:#94a3b8;margin-left:2px}
.rdpm-violet{--ak-tfg:#005447;--ak-tbg:#f2f6f6}
.rdpm-alert .ak-fig-v{color:#b45309}
.rdpm-muted .ak-fig-v{color:#94a3b8}
.rdpm-search{flex:1;min-width:200px;max-width:420px}
.rdpm-filters{flex-wrap:wrap;margin-left:auto}
.rdpm-filters span{opacity:.7;font-weight:500}
.rdpm-tbl td{vertical-align:top}
.rdpm-row-exp{background:#fef2f2}
.rdpm-row-soon{background:#fffbeb}
.rdpm-id{display:flex;align-items:flex-start;gap:12px;min-width:0}
.rdpm-id-t{min-width:0}
.rdpm-name{font-size:14px;font-weight:700;line-height:20px}
.rdpm-name a{color:#111d23;text-decoration:none}
.rdpm-name a:hover{color:#059669;text-decoration:underline}
.rdpm-dn{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px;color:#64748b;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rdpm-b{display:inline-flex;align-items:center;gap:5px;margin-top:5px}
.rdpm-src{display:inline-flex;align-items:center;gap:6px;padding:3px 9px;border-radius:5px;background:#f1f5f9;border:1px solid #e2e8f0;font-size:12px;font-weight:600;color:#334155;white-space:nowrap}
.rdpm-src .fa-windows,.rdpm-src svg.fa-windows{color:#2563eb}
.rdpm-sub{font-size:11px;color:#94a3b8;margin-top:4px;max-width:200px}
.rdpm-acts{display:flex;align-items:center;justify-content:flex-end;gap:4px}
.rdpm-acts .awc-acts{justify-content:flex-end}
.rdpm-val{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#334155}
.rdpm-val svg,.rdpm-val i{color:#059669}
.rdpm-amber,.rdpm-amber svg,.rdpm-amber i{color:#b45309}
.rdpm-red,.rdpm-red svg,.rdpm-red i{color:#b91c1c}
.rdpm-pol{display:inline-flex;align-items:center;gap:6px;padding:3px 9px;border-radius:6px;background:#ecfdf5;border:1px solid #6ee7b7;color:#065f46;font-size:12px;font-weight:600;white-space:nowrap}
.rdpm-pol a{color:inherit;text-decoration:none}.rdpm-pol a:hover{text-decoration:underline}
.rdpm-pol svg,.rdpm-pol i{font-size:11px;color:#059669}
.rdpm-pol-off{background:#f8fafc;border-color:#cbd5e1;color:#64748b}
.rdpm-pol-off svg,.rdpm-pol-off i{color:#94a3b8}
.rdpm-tg{display:flex;align-items:center;gap:8px;margin-bottom:5px;max-width:240px}
.rdpm-tg-n{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rdpm-tg .ak-tag{flex:0 0 auto}
.rdpm-tg-off{opacity:.5}
.rdpm-warn{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#b45309}
.rdpm-desc{font-size:12px;color:#475569;margin-top:1px}
.rdpm-host{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;font-weight:600}
.rdpm-host a{color:#1d4ed8;text-decoration:none}.rdpm-host a:hover{text-decoration:underline}
.rdpm-wrap{white-space:normal;max-width:260px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.rdpm-acc{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#1e293b}
.rdpm-acc svg,.rdpm-acc i{color:#94a3b8}
.rdpm-row-off td{color:#94a3b8}
.rdpm-row-off .rdpm-name a{color:#64748b}
.rdpm .awc-wrap{padding:0;margin:0}
@media(max-width:700px){.rdpm-filters{margin-left:0}.rdpm-search{max-width:none}}
</style>";
}

function group_network_delete(){
    $tpl=new template_admin();
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $gpid=intval($_GET["gpid"]);
    $err=rdpproxy_policy_child_delete($gpid,"networks",intval($_GET["network-unlink"]));
    header("content-type: application/x-javascript");
    if($err!=""){$tpl->js_error($err);return;}
    admin_tracks("Removed a network from RDS Proxy policy ".rdpc_name("policy",$gpid));
    echo "$('#$md').remove();\n";
}

function group_time_table(){
    $gpid=intval($_GET["group-time-table"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=md5(time().$gpid.rand(0,65595));
    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{add_working_period}", "Loadjs('$page?time-link=$gpid')", "fas fa-clock",rdpproxy_meta_sec("AllowAddUsers"))."
			</div>");

    $p=rdpproxy_policy($gpid,$error);
    if($error!=""){echo $tpl->div_error($error);return;}
    $results=isset($p["schedules"])&&is_array($p["schedules"])?$p["schedules"]:array();

    $array_day_of_week=array(
        "6"=>"{Sunday}",
        "0"=>"{Monday}",
        "1"=>"{Tuesday}",
        "2"=>"{Wednesday}",
        "3"=>"{Thursday}",
        "4"=>"{Friday}",
        "5"=>"{Saturday}");

    $rows=array();
    // Aucune plage = acces permanent : la ligne le dit, elle n'est pas "vide".
    if(count($results)==0){
        $md=time();
        $rows[]="<tr id='$md'><td>".rdpc_ident("fas fa-clock","{every_day_every_time}")."</td><td>&nbsp;</td></tr>";
    }
    foreach ($results as $index=>$ligne){
        $md=md5(serialize($ligne));
        $ID=intval($ligne["id"]);
        $tday=$ligne["day"];
        $day=isset($array_day_of_week[$tday])?$array_day_of_week[$tday]:htmlspecialchars((string)$tday,ENT_QUOTES);
        $pattern=$day." ".htmlspecialchars($ligne["from"]." - ".$ligne["to"],ENT_QUOTES);
        $r="<tr id='$md'>";
        $r.="<td>".rdpc_ident("fas fa-clock",$pattern)."</td>";
        $r.="<td><div class='awc-acts'>".rdpc_act("Loadjs('$page?time-unlink=$ID&gpid=$gpid&md=$md')",ico_trash,"{delete}",true)."</div></td>";
        $r.="</tr>";
        $rows[]=$r;
    }

    $html[]=rdpc_table_open($t,array("<th>{working_period}</th>","<th class='awc-right'>&nbsp;</th>"));
    $html[]=@implode("\n",$rows);
    $html[]=rdpc_table_close($t,count($results));
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).rdpc_scripts($tpl,$t);
}

function group_network_table(){
    $gpid=intval($_GET["groups-network-table"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=md5(time().$gpid.rand(0,65595));
    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{add_network}", "Loadjs('$page?network-link=$gpid')", "fas fa-network-wired",rdpproxy_meta_sec("AllowAddUsers"))."
			</div>");

    $p=rdpproxy_policy($gpid,$error);
    if($error!=""){echo $tpl->div_error($error);return;}
    $results=isset($p["networks"])&&is_array($p["networks"])?$p["networks"]:array();

    $rows=array();
    // Aucun reseau = tout le monde est refuse : c'est un etat, on l'affiche.
    if(count($results)==0){
        $rows[]="<tr id='000'><td>".rdpc_ident("fas fa-network-wired","{refused_from_everyone}","",true)."</td><td>&nbsp;</td></tr>";
    }
    foreach ($results as $index=>$ligne){
        $md=md5(serialize($ligne));
        $ID=intval($ligne["id"]);
        $pattern=htmlspecialchars((string)$ligne["pattern"],ENT_QUOTES);
        $r="<tr id='$md'>";
        $r.="<td>".rdpc_ident("fas fa-network-wired",$pattern)."</td>";
        $r.="<td><div class='awc-acts'>".rdpc_act("Loadjs('$page?network-unlink=$ID&gpid=$gpid&md=$md')",ico_trash,"{delete}",true)."</div></td>";
        $r.="</tr>";
        $rows[]=$r;
    }

    $html[]=rdpc_table_open($t,array("<th>{networks}</th>","<th class='awc-right'>&nbsp;</th>"));
    $html[]=@implode("\n",$rows);
    $html[]=rdpc_table_close($t,count($results));
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).rdpc_scripts($tpl,$t);
}

function group_targets_table(){
    $gpid=intval($_GET["groups-targets-table"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=md5(time().$gpid.rand(0,65595));
    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{link_target}", "Loadjs('$page?target-link=$gpid')", "fas fa-server",rdpproxy_meta_sec("AllowAddUsers"))."
			</div>");

    $p=rdpproxy_policy($gpid,$error);
    if($error!=""){echo $tpl->div_error($error);return;}
    $results=isset($p["targets"])&&is_array($p["targets"])?$p["targets"]:array();

    $rows=array();
    foreach ($results as $index=>$ligne){
        $md=md5(serialize($ligne));
        $ID=intval($ligne["id"]);
        $designation=$ligne["designation"] ?? "";
        list($ico,$metatxt)=rdpc_target_label($ligne,$alias);
        $r="<tr id='$md'>";
        $r.="<td>".rdpc_ident($ico,htmlspecialchars((string)$alias,ENT_QUOTES),htmlspecialchars((string)$designation,ENT_QUOTES))."</td>";
        $r.="<td class='awc-mono'>".htmlspecialchars($metatxt,ENT_QUOTES)."</td>";
        $r.="<td><div class='awc-acts'>".rdpc_act("Loadjs('$page?target-unlink=$ID&gpid=$gpid&md=$md')","fa fa-unlink","{unlink}",true)."</div></td>";
        $r.="</tr>";
        $rows[]=$r;
    }

    $html[]=rdpc_table_open($t,array("<th>{targets}</th>","<th>{hostname}</th>","<th class='awc-right'>&nbsp;</th>"));
    if(count($rows)==0){$html[]=rdpc_empty(3,"{no_item}");}else{$html[]=@implode("\n",$rows);}
    $html[]=rdpc_table_close($t,count($rows));
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).rdpc_scripts($tpl,$t);
}

function target_status(){
    $tpl=new template_admin();
    $ID=intval($_GET["target-status"]);
    $ligne=rdpproxy_target($ID,$error);
    if($error!=""){echo $tpl->div_error($error);return;}
    $ligne["enabled"]=!empty($ligne["enabled"])?1:0;
    echo $tpl->_ENGINE_parse_body(group_target_status($ligne));
}

// Etat d'une cible : desactivee, groupe AD (rien a tester), sinon test decide par le daemon
// (GET /rdpproxy/targets/{id}/reachability) : TCP direct, CONNECT a travers le proxy amont des
// politiques d'une cible WEB, ou etat de l'agent de tunnel. Plus aucun test depuis PHP.
function group_target_status($ligne){
    // Leurre : jamais enabled=1, aucun hote a joindre ; l'etat est celui de l'armement.
    if(!empty($ligne["decoy"])){
        if(!empty($ligne["decoy_enabled"])){
            return "<span class='ak-pill ak-info'><i class='fas fa-user-secret'></i>&nbsp;{rdpproxy_decoy}</span>";
        }
        return "<span class='ak-pill ak-off'><i class='fas fa-power-off'></i>&nbsp;{disabled}</span>";
    }
    if(intval($ligne["enabled"])==0){
        return "<span class='ak-pill ak-off'><i class='fas fa-power-off'></i>&nbsp;{disabled}</span>";
    }
    if(preg_match("#(CN|OU|DC)=#i",(string)$ligne["alias"])){
        return "<span class='ak-pill ak-info'><i class='fas fa-users'></i>&nbsp;{active_directory_group}</span>";
    }
    $error="";
    $r=rdpproxy_target_reachability(intval($ligne["id"]),$error);
    if($error!=""){
        return "<span class='ak-pill ak-err' title=\"".rdpc_status_esc($error)."\"><i class='fas fa-circle-xmark'></i>&nbsp;{error}</span>";
    }
    $mode=(string)($r["mode"]??"");
    $ok=!empty($r["ok"]);
    $detail=rdpc_status_esc($r["detail"]??"");
    $via=rdpc_status_esc($r["via"]??"");
    $addr=rdpc_status_esc($r["address"]??"");
    $ms=floatval($r["latency_ms"]??0);
    $lat=$ms<10?number_format($ms,1):strval(intval(round($ms)));
    // Direct : duree de la connexion TCP. Proxy ou PAC : duree de la requete HTTP a travers
    // le proxy (ou de l'evaluation du PAC puis de la requete), pas une connexion TCP.
    $latlabel=($mode=="proxy" || $mode=="pac")?"{latency}":"{rdpproxy_tcp_connect}";
    $latline="<div class='rdpm-sub'><i class='fas fa-signal'></i> $latlabel $lat ms</div>";

    if($mode=="disabled"){
        return "<span class='ak-pill ak-off'><i class='fas fa-power-off'></i>&nbsp;{disabled}</span>";
    }
    if($mode=="ad_group"){
        return "<span class='ak-pill ak-info'><i class='fas fa-users'></i>&nbsp;{active_directory_group}</span>";
    }
    if($mode=="tunnel"){
        if($ok){
            return "<span class='ak-pill ak-ok' title=\"$detail\"><i class='fas fa-check'></i>&nbsp;{rdpproxy_tunnel_agent} $via {rdpproxy_agent_connected}</span>"
                ."<div class='rdpm-sub'>$addr</div>";
        }
        return "<span class='ak-pill ak-err' title=\"$detail\"><i class='fas fa-circle-xmark'></i>&nbsp;{rdpproxy_tunnel_agent} $via {rdpproxy_agent_offline}</span>"
            ."<div class='rdpm-sub'>$detail</div>";
    }
    if($mode=="proxy"){
        if($ok){
            return "<span class='ak-pill ak-ok' title=\"$addr\"><i class='fas fa-check'></i>&nbsp;{rdpproxy_reachable_via_proxy} $via</span>".$latline;
        }
        return "<span class='ak-pill ak-err' title=\"$addr\"><i class='fas fa-circle-xmark'></i>&nbsp;{rdpproxy_unreachable_via_proxy} $via: $detail</span>"
            ."<div class='rdpm-sub'>$addr</div>";
    }
    if($mode=="pac"){
        // Fichier PAC de la politique : via = « DIRECT » (reponse du PAC), « DIRECT (fail-direct) »
        // (sortie directe permise par la politique apres une erreur du PAC ou sans route
        // utilisable) ou le nom du proxy declare retenu.
        $rawvia=(string)($r["via"]??"");
        if($rawvia=="DIRECT"){$vtxt="{rdpproxy_pac_direct}";}
        elseif($rawvia=="DIRECT (fail-direct)"){$vtxt="{rdpproxy_pac_fail_direct}";}
        elseif($rawvia!=""){$vtxt="{rdpproxy_pac_via_proxy} $via";}
        else{$vtxt="";}
        if($ok){
            return "<span class='ak-pill ak-ok' title=\"$addr\"><i class='fas fa-check'></i>&nbsp;{rdpproxy_reachable_via_pac}".($vtxt!=""?" ($vtxt)":"")."</span>".$latline
                .($rawvia=="DIRECT (fail-direct)"?"<div class='rdpm-sub'>$detail</div>":"");
        }
        return "<span class='ak-pill ak-err' title=\"$addr\"><i class='fas fa-circle-xmark'></i>&nbsp;{rdpproxy_unreachable_via_pac}".($vtxt!=""?" ($vtxt)":"").": $detail</span>"
            ."<div class='rdpm-sub'>$addr</div>";
    }
    if(!$ok){
        return "<span class='ak-pill ak-err' title=\"$detail\"><i class='fas fa-circle-xmark'></i>&nbsp;{rdpproxy_unreachable}</span>"
            ."<div class='rdpm-sub'>$addr</div>";
    }
    return "<span class='ak-pill ak-ok'><i class='fas fa-check'></i>&nbsp;{rdpproxy_reachable}</span>".$latline;
}

// Valeur venue du daemon (nom de proxy, detail, adresse) : echappee, et ses accolades
// neutralisees pour que _ENGINE_parse_body n'y voie jamais un {jeton}.
// Le | est neutralise aussi : div_error()/div_explain() coupent sur « || », et un 3e segment
// y devient l'onclick d'un bouton (revue T7, I2).
function rdpc_status_esc($v){
    return str_replace(array("{","}","|"),array("&#123;","&#125;","&#124;"),htmlspecialchars((string)$v,ENT_QUOTES));
}

// Libelle d'option de liste (I1, M7) : les jetons sont resolus ICI, pour qu'aucune accolade ne
// reste dans le libelle. Sinon la liste simple (StandardDropDown=1) le repasse par
// javascript_parse_text(), qui fait html_entity_decode() et defait l'echappement. La partie
// variable est raccourcie AVANT l'echappement : le libelle final tient dans 50 octets, sous les
// coupes de field_array_hash (70) et field_array_hash_simple (50), qui ne coupent donc jamais
// une entite.
function rdpc_option_label($tpl,$prefixTok,$raw,$suffixTok=""):string{
    $max=50;
    // Un jeton sans traduction garderait ses accolades : elles sont retirees quoi qu'il arrive.
    $prefix=$prefixTok==""?"":str_replace(array("{","}"),"",$tpl->_ENGINE_parse_body($prefixTok));
    $suffix=$suffixTok==""?"":str_replace(array("{","}"),"",$tpl->_ENGINE_parse_body($suffixTok));
    $raw=(string)$raw;
    // D3 : le prefixe et le suffixe (marque d'erreur, etat) sont toujours gardes ; seul le nom
    // est raccourci, jusqu'a disparaitre. Si cela ne suffit pas, le suffixe est reduit a son
    // premier mot (la marque « ⚠ » d'un PAC en echec), puis coupe en octets sur un caractere entier.
    $sufs=array($suffix);
    if($suffix!=""){
        $w=preg_split('/\s+/u',trim($suffix),2);
        $sufs[]=" ".$w[0];
    }
    foreach($sufs as $suf){
        $n=$raw;$cut=false;
        while(true){
            $lab=$prefix.rdpc_status_esc($n).($cut?"...":"").$suf;
            if(strlen($lab)<=$max){return $lab;}
            if($n===""){break;}
            $n=mb_substr($n,0,mb_strlen($n,"UTF-8")-1,"UTF-8");$cut=true;
        }
    }
    return mb_strcut($prefix.end($sufs),0,$max,"UTF-8");
}

// Ligne d'un PAC dans la liste (sans contenu) : evite de relire jusqu'a 256 Kio pour un nom (M4).
function rdpc_webpac_row($ID):array{
    $err="";
    foreach(rdpproxy_webpacs($err) as $l){
        if(is_array($l)&&intval($l["id"] ?? 0)==intval($ID)){return $l;}
    }
    return array();
}

function group_members_table(){
    $gpid=intval($_GET["group-members-table"]);
    $tpl=new template_admin();
    if($gpid==0){echo $tpl->FATAL_ERROR_SHOW_128("Cannot load a rule 0!");return;}

    $page=CurrentPageName();
    $t=md5(time().$gpid.rand(0,65595));
    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{link_member}", "Loadjs('$page?member-link=$gpid')", "far fa-user-plus",rdpproxy_meta_sec("AllowAddUsers"))."
			</div>");

    $p=rdpproxy_policy($gpid,$error);
    if($error!=""){echo $tpl->div_error($error);return;}
    $results=isset($p["members"])&&is_array($p["members"])?$p["members"]:array();

    $rows=array();
    foreach ($results as $index=>$ligne){
        $md=md5(serialize($ligne));
        $ID=intval($ligne["id"]);
        $status="";
        $ligne2=$ligne;
        $username=$ligne2["username"];
        $ico="fa fa-user";
        if(intval($ligne2["endoflife"])>0) {
            if (intval($ligne2["endoflife"]) < time()) {
                $status = "&nbsp;<span class='awc-badge awc-badge-alert'>{expired}</span>";
            }
        }
        if(preg_match("#CN=(.*?),#i",$username,$re)){
            $username=$re[1];
            $ico="fa fa-users";
        }
        $r="<tr id='$md'>";
        $r.="<td>".rdpc_ident($ico,htmlspecialchars((string)$username,ENT_QUOTES).$status)."</td>";
        $r.="<td><div class='awc-acts'>".rdpc_act("Loadjs('$page?member-unlink=$ID&gpid=$gpid&md=$md')","fa fa-unlink","{unlink}",true)."</div></td>";
        $r.="</tr>";
        $rows[]=$r;
    }

    $html[]=rdpc_table_open($t,array("<th>{rdpproxy_members}</th>","<th class='awc-right'>&nbsp;</th>"));
    if(count($rows)==0){$html[]=rdpc_empty(2,"{no_item}");}else{$html[]=@implode("\n",$rows);}
    $html[]=rdpc_table_close($t,count($rows));
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).rdpc_scripts($tpl,$t);
}

function group_enable(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["group-enable"]);
    $enabled=rdpproxy_policy_toggle($ID,$error);
    header("content-type: application/x-javascript");
    if($error!=""){$tpl->js_mysql_alert($error);return;}
    admin_tracks(($enabled?"Enabled":"Disabled")." RDS Proxy policy ".rdpc_name("policy",$ID)." (#$ID)");
    echo "setTimeout(\"LoadAjaxTiny('grp-status-$ID','$page?group-status=$ID')\",800)";
}

function group_delete(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $ID=intval($_GET["group-delete"]);
    $error="";
    $p=rdpproxy_policy($ID,$error);
    $title=rdpc_html($p["name"] ?? "");
    $tpl->js_confirm_delete($title,"group-delete",$ID,"$('#$md').remove()");
}
function group_delete_perform(){
    $ID=intval($_POST["group-delete"]);
    $name=rdpc_name("policy",$ID);
    $err=rdpproxy_policy_delete($ID);
    if($err!=""){echo $err;return;}
    admin_tracks("Deleted RDS Proxy policy $name (#$ID) with its members, targets, networks and time slots links");
}

// groups_status : $p = ligne deja lue (groups_table), sinon une lecture REST.
function groups_status($ID,$p=null){
    $tpl=new template_admin();
    $ID=intval($ID);
    $error="";
    if(!is_array($p)){$p=rdpproxy_policy($ID,$error);}
    if($error!=""){
        return $tpl->_ENGINE_parse_body("<span class='awc-badge awc-badge-alert'>{error}</span>");
    }
    if(empty($p["enabled"])){
        return $tpl->_ENGINE_parse_body("<span class='awc-badge awc-badge-off'>{disabled}</span>");
    }
    $counts=isset($p["counts"])&&is_array($p["counts"])?$p["counts"]:array();

    $CountOfUsers=isset($counts["members"])?intval($counts["members"]):0;
    if($CountOfUsers==0){
        return $tpl->_ENGINE_parse_body("<span class='awc-badge awc-badge-warn'>{inactive}</span>");
    }

    $CountOfTargets=isset($counts["targets"])?intval($counts["targets"]):0;
    if($CountOfTargets==0){
        return $tpl->_ENGINE_parse_body("<span class='awc-badge awc-badge-warn'>{inactive}</span>");
    }

    $CountOfNets=isset($counts["networks"])?intval($counts["networks"]):0;
    if($CountOfNets==0){
        return $tpl->_ENGINE_parse_body("<span class='awc-badge awc-badge-warn'>{inactive}</span>");
    }

    return $tpl->_ENGINE_parse_body("<span class='awc-badge awc-badge-on'>{active2}</span>");
}

// Vue des politiques en cartes (maquette stitch_redemption_rdp_proxy rules) :
// un en-tete (etat, nom, description, actions) puis quatre tuiles qui se lisent
// dans l'ordre ou authhook decide -- qui, quand et d'ou, vers quelle cible,
// avec quelles garanties. Le detail vient de GET /rdpproxy/policies/{id}.
function groups_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $t=md5(time().rand(0,65595));
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $canEdit=$PowerDNSEnableClusterSlave==0 && !rdpproxy_meta_locked();

    $results=rdpproxy_policies($error);
    if($error!=""){
        echo $tpl->div_error($error);
        return false;
    }

    $cards=array();
    foreach ($results as $ligne){
        $ID=intval($ligne["id"]);
        $derr="";
        $d=rdpproxy_policy($ID,$derr);
        if($derr!=""||!is_array($d)){$d=$ligne;}
        $cards[]=rdpp_card($tpl,$page,$ligne,$d,$canEdit);
    }

    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
    if($canEdit) {
        $topbuttons[] = array("Loadjs('$page?group-js=0')", ico_plus, "{new_policy}");
    }
    $topbuttons[] = array("LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');",ico_refresh,"{reload}" );

    $TINY_ARRAY["TITLE"]="{APP_RDPPROXY} v$PrivoxyVersion {policies}";
    $TINY_ARRAY["ICO"]="fa-solid fa-building-shield";
    $TINY_ARRAY["EXPL"]="{rdpproxy_policies_explain_web}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<div class='rdpp ak'>";
    $html[]=rdpp_flow();
    // Recherche discrete : un champ fin, sans bouton, a droite du compte.
    $html[]="<div class='rdpp-bar'><span class='rdpp-count'>{policies}: <b id='count-$t'>".count($cards)."</b></span>";
    if(count($cards)>0){
        $html[]="<label class='ak-search rdpp-search'><i class='fas fa-search'></i><input type='text' id='search-$t' autocomplete='off' placeholder=\"{search}\"></label>";
    }
    $html[]="</div>";
    $html[]="<div id='cards-$t' class='rdpp-list'>";
    if(count($cards)==0){
        $html[]="<div class='ak-empty'>{no_rules}</div>";
    }else{
        $html[]=@implode("\n",$cards);
        $html[]="<div class='ak-empty' id='nomatch-$t' style='display:none'>{no_data}</div>";
    }
    $html[]="</div></div>";

    $js="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny\n".rdpp_filter_js($t)."</script>";
    echo awc_table_style().rdpp_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js;
    return true;
}

// Une carte de politique. $l = ligne de la liste (compteurs), $d = detail.
function rdpp_card($tpl,$page,$l,$d,$canEdit):string{
    $ID=intval($l["id"]);
    $md=md5(serialize($l));
    $enabled=!empty($l["enabled"]);
    $name=htmlspecialchars((string)$l["name"],ENT_QUOTES);
    $desc=htmlspecialchars((string)$l["description"],ENT_QUOTES);
    $js="Loadjs('$page?group-js=$ID')";
    $members=isset($d["members"])&&is_array($d["members"])?$d["members"]:array();
    $targets=isset($d["targets"])&&is_array($d["targets"])?$d["targets"]:array();
    $nets=isset($d["networks"])&&is_array($d["networks"])?$d["networks"]:array();
    $scheds=isset($d["schedules"])&&is_array($d["schedules"])?$d["schedules"]:array();
    $search=array((string)$l["name"],(string)$l["description"]);

    // Qui
    $chips=array();
    foreach($members as $m){
        $mn=rdpproxy_cn($m["username"]);
        $search[]=$mn;
        $ico=!empty($m["ad_group"])?"fas fa-users":"fas fa-user";
        $cls=!empty($m["expired"])?"rdpp-chip rdpp-chip-off":"rdpp-chip";
        $chips[]="<span class='$cls'><i class='$ico'></i>".htmlspecialchars($mn,ENT_QUOTES)."</span>";
    }
    $who=count($chips)>0?"<div class='rdpp-chips'>".@implode("",$chips)."</div>"
        :"<div class='rdpp-warn'><i class='fas fa-triangle-exclamation'></i>{no_members}</div>";

    // Quand et d'ou
    $days=array("6"=>"{Sunday}","0"=>"{Monday}","1"=>"{Tuesday}","2"=>"{Wednesday}",
        "3"=>"{Thursday}","4"=>"{Friday}","5"=>"{Saturday}");
    if(count($scheds)==0){
        $when="<div class='rdpp-value'>{rdpproxy_permanent_access}</div>";
    }else{
        $sl=array();
        foreach($scheds as $s){
            $dn=isset($days[(string)$s["day"]])?$days[(string)$s["day"]]:htmlspecialchars((string)$s["day"],ENT_QUOTES);
            $sl[]="<span class='rdpp-chip rdpp-chip-time'><i class='far fa-clock'></i>$dn ".
                htmlspecialchars((string)$s["from"],ENT_QUOTES)."&ndash;".htmlspecialchars((string)$s["to"],ENT_QUOTES)."</span>";
        }
        $when="<div class='rdpp-chips'>".@implode("",$sl)."</div>";
    }
    $nl=array();
    foreach($nets as $n){
        $search[]=(string)$n["pattern"];
        $nl[]="<span class='rdpp-chip ak-mono'><i class='fas fa-network-wired'></i>".htmlspecialchars((string)$n["pattern"],ENT_QUOTES)."</span>";
    }
    $when.=count($nl)>0?"<div class='rdpp-chips'>".@implode("",$nl)."</div>"
        :"<div class='rdpp-warn'><i class='fas fa-triangle-exclamation'></i>{rulefailed_no_network}</div>";

    // Cibles
    $tl=array();
    foreach($targets as $tg){
        $alias=rdpproxy_cn($tg["alias"]);
        $host=(string)$tg["target_host"];
        $search[]=$alias; $search[]=$host;
        $proto=htmlspecialchars((string)$tg["proto_dest"],ENT_QUOTES);
        $port=intval($tg["target_port"]);
        $off=empty($tg["enabled"]);
        $title=htmlspecialchars($host,ENT_QUOTES);
        $tl[]="<div class='rdpp-target".($off?" rdpp-chip-off":"")."' title=\"$title\">".
            "<span class='rdpp-target-name'>".htmlspecialchars($alias,ENT_QUOTES)."</span>".
            "<span class='ak-tag ak-warn'>$proto".($port>0?" : $port":"")."</span></div>";
    }
    $where=count($tl)>0?@implode("",$tl)
        :"<div class='rdpp-warn'><i class='fas fa-triangle-exclamation'></i>{rdpproxy_no_target}</div>";

    // Securite et audit
    $sec=array();
    if(!rdpproxy_video_supported()){
        $sec[]="<div class='rdpp-off'><i class='fas fa-video-slash'></i>{rdpproxy_video_unavailable}</div>";
    }else{
        $sec[]=!empty($l["recording"])
            ?"<div class='rdpp-on'><i class='fas fa-circle-check'></i>{video_recording}</div>"
            :"<div class='rdpp-off'><i class='fas fa-video-slash'></i>{rdpproxy_no_recording}</div>";
    }
    $sec[]=!empty($l["mfa_required"])
        ?"<div class='rdpp-on'><i class='fas fa-circle-check'></i>{second_factor}</div>"
        :"<div class='rdpp-off'><i class='fas fa-key'></i>{rdpproxy_password_only}</div>";
    $st=intval($l["session_time"]);
    if($st>0){$sec[]="<div class='rdpp-off'><i class='far fa-hourglass-half'></i>{max_session_time}: $st {minutes}</div>";}
    $rg=isset($l["rights"])&&is_array($l["rights"])?$l["rights"]:array();
    $denied=0;
    foreach(rdpproxy_session_rights() as $col=>$label){if(isset($rg[$col]) && empty($rg[$col])){$denied++;}}
    $sec[]=$denied==0
        ?"<div class='rdpp-off'><i class='fas fa-plug'></i>{rdpproxy_rights_all}</div>"
        :"<div class='rdpp-on'><i class='fas fa-plug-circle-xmark'></i>".str_replace("%d",$denied,$tpl->_ENGINE_parse_body("{rdpproxy_rights_denied_n}"))."</div>";
    // Presse-papiers WEB (ferme par defaut) : signale seulement s'il est ouvert dans un sens.
    $wup=!empty($rg["allow_web_clipboard_up"]);$wdown=!empty($rg["allow_web_clipboard_down"]);
    if($wup||$wdown){
        $wl=($wup&&$wdown)?"{rdpproxy_webclip_card_both}":($wup?"{rdpproxy_webclip_card_up}":"{rdpproxy_webclip_card_down}");
        $sec[]="<div class='rdpp-off'><i class='fas fa-paste'></i>$wl</div>";
    }
    $perr="";
    $np=count(rdpproxy_patterns($ID,$perr));
    if($np>0){
        $sec[]="<div class='rdpp-on'><i class='fas fa-filter'></i>".str_replace("%d",$np,$tpl->_ENGINE_parse_body("{rdpproxy_patterns_n}"))."</div>";
    }

    $acts="";
    if($canEdit){
        $acts.="<span class='rdpp-toggle'>".$tpl->icon_check($enabled?1:0,"Loadjs('$page?group-enable=$ID')",null,"AllowAddUsers")."</span>";
        $acts.=rdpc_act($js,"fa-regular fa-pen-to-square","{edit}");
        $acts.=rdpc_act("Loadjs('$page?group-delete=$ID&md=$md')","fa-regular fa-trash-can","{delete}",true);
    }

    $q=htmlspecialchars(mb_strtolower(@implode(" ",$search),"UTF-8"),ENT_QUOTES);
    $h="<article class='ak-card rdpp-card".($enabled?"":" rdpp-card-off")."' id='$md' data-q=\"$q\">";
    $h.="<header class='rdpp-head'><div class='rdpp-ident'>";
    $h.="<span id='grp-status-$ID'>".groups_status($ID,$l)."</span>";
    $h.="<div><div class='rdpp-name'>".rdpc_link($l["name"],$js)."</div>";
    if($desc!=""){$h.="<div class='rdpp-desc'>$desc</div>";}
    $h.="</div></div><div class='rdpp-acts'>$acts</div></header>";
    $h.="<div class='rdpp-grid'>";
    $h.=rdpp_tile("fas fa-users","rdpp-c1","{rdpproxy_who_access}",count($members),$who);
    $h.=rdpp_tile("far fa-clock","rdpp-c2","{rdpproxy_when_where}",-1,$when);
    $h.=rdpp_tile("fas fa-desktop","rdpp-c3","{targets}",count($targets),$where);
    $h.=rdpp_tile("fas fa-shield-halved","rdpp-c4","{rdpproxy_security_audit}",-1,@implode("",$sec));
    $h.="</div></article>";
    return $h;
}

function rdpp_tile($ico,$cls,$title,$count,$body):string{
    $c=$count>=0?" <span class='ak-count'>".intval($count)."</span>":"";
    return "<div class='rdpp-tile'><div class='rdpp-tile-h $cls'><i class='$ico'></i>$title$c</div>$body</div>";
}

// Bandeau d'explication : l'ordre dans lequel authhook evalue une politique.
function rdpp_flow():string{
    $s=array(
        array("{rdpproxy_flow1}","{rdpproxy_flow1_explain}"),
        array("{rdpproxy_flow2}","{rdpproxy_flow2_explain}"),
        array("{rdpproxy_flow3}","{rdpproxy_flow3_explain}"),
        array("{rdpproxy_flow4}","{rdpproxy_flow4_explain}"));
    $h="<section class='rdpp-flow'><div class='rdpp-flow-t'><i class='fas fa-diagram-project'></i>{rdpproxy_flow_title}</div><div class='rdpp-flow-s'>";
    foreach($s as $i=>$x){
        $h.="<div class='rdpp-step'><span class='rdpp-step-n'>".($i+1)."</span><div><b>{$x[0]}</b><span>{$x[1]}</span></div></div>";
    }
    return $h."</div></section>";
}

// Filtre des cartes cote navigateur ; data-q est deja en minuscules.
function rdpp_filter_js($t):string{
    return "(function(){var i=document.getElementById('search-$t');if(!i){return;}
var box=document.getElementById('cards-$t'),cnt=document.getElementById('count-$t'),nm=document.getElementById('nomatch-$t');
i.addEventListener('input',function(){var q=i.value.toLowerCase().trim(),n=0;
var cs=box.querySelectorAll('article.rdpp-card');
for(var k=0;k<cs.length;k++){var ok=q===''||(cs[k].getAttribute('data-q')||'').indexOf(q)>=0;cs[k].style.display=ok?'':'none';if(ok){n++;}}
cnt.textContent=n;if(nm){nm.style.display=n===0?'':'none';}});})();";
}

function rdpp_style():string{
    if(isset($GLOBALS["RDPP_STYLE"])){return "";}
    $GLOBALS["RDPP_STYLE"]=true;
    return "<style>
.rdpp{margin:10px 0;color:#111d23}
.rdpp-flow{display:flex;align-items:center;gap:16px;flex-wrap:wrap;background:#f0fdfa;border:1px solid #99f6e4;border-radius:12px;padding:12px 16px;margin-bottom:16px}
.rdpp-flow-t{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#115e59;display:flex;align-items:center;gap:8px}
.rdpp-flow-t svg,.rdpp-flow-t i{color:#0d9488}
.rdpp-flow-s{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;flex:1;min-width:300px}
.rdpp-step{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid rgba(153,246,228,.7);border-radius:8px;padding:6px 8px}
.rdpp-step-n{flex:0 0 22px;height:22px;border-radius:50%;background:#0d9488;color:#fff;font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
.rdpp-step b{display:block;font-size:11px;color:#111d23}
.rdpp-step div span{font-size:11px;color:#64748b}
.rdpp-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 2px 10px}
.rdpp-count{font-size:12px;color:#64748b}
.rdpp-count b{color:#111d23}
.rdpp-search{height:32px;width:220px;transition:width .15s}.rdpp-search:focus-within{width:280px}
.rdpp-list{display:flex;flex-direction:column;gap:14px}
.rdpp-card{padding:16px 18px;transition:box-shadow .15s}
.rdpp-card:hover{box-shadow:0 4px 12px rgba(15,23,42,.08)}
.rdpp-card-off .rdpp-grid{opacity:.6}
.rdpp-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border-bottom:1px solid #f1f5f9;padding-bottom:12px}
.rdpp-ident{display:flex;align-items:center;gap:12px;min-width:0}
.rdpp-name{font-size:16px;font-weight:700;line-height:22px}
.rdpp-name a{color:#111d23;text-decoration:none}
.rdpp-name a:hover{color:#0d9488;text-decoration:underline}
.rdpp-desc{font-size:12px;color:#64748b;margin-top:1px}
.rdpp-acts{display:flex;align-items:center;gap:4px}
.rdpp-toggle{margin-right:4px}
.rdpp-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:14px}
.rdpp-tile{background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:10px 12px;min-width:0}
.rdpp-tile-h{font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#64748b;margin-bottom:8px;display:flex;align-items:center;gap:6px}
.rdpp-c1 svg{color:#0d9488}.rdpp-c2 svg{color:#2563eb}.rdpp-c3 svg{color:#d97706}.rdpp-c4 svg{color:#005447}
.rdpp-tile-h .ak-count{margin-left:auto}
.rdpp-value{font-size:13px;font-weight:600;color:#111d23;margin-bottom:6px}
.rdpp-chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px}
.rdpp-chip{display:inline-flex;align-items:center;gap:5px;max-width:100%;padding:2px 8px;border-radius:4px;background:#fff;border:1px solid #e2e8f0;font-size:12px;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rdpp-chip svg{color:#94a3b8;font-size:10px}
.rdpp-chip-time{background:#eff6ff;border-color:#bfdbfe;color:#1e40af}
.rdpp-chip-off{opacity:.55;text-decoration:line-through}
.rdpp-target{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;min-width:0}
.rdpp-target-name{font-size:13px;font-weight:600;color:#111d23;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rdpp-target .ak-tag{flex:0 0 auto}
.rdpp-on,.rdpp-off{font-size:12px;display:flex;align-items:center;gap:6px;margin-bottom:5px}
.rdpp-on{color:#047857;font-weight:600}.rdpp-on svg{color:#10b981}
.rdpp-off{color:#475569}.rdpp-off svg{color:#94a3b8}
.rdpp-warn{font-size:12px;color:#b45309;display:flex;align-items:flex-start;gap:6px}
.rdpp-warn svg{margin-top:2px}
@media(max-width:1200px){.rdpp-grid,.rdpp-flow-s{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.rdpp-grid,.rdpp-flow-s{grid-template-columns:1fr}.rdpp-search,.rdpp-search:focus-within{width:100%}}
</style>";
}

// Onglet Cibles : tuiles, filtres rapides, tableau. L'etat de chaque cible
// (test TCP) est charge APRES l'affichage, ligne par ligne : le tester au rendu
// coutait jusqu'a 2 s par cible injoignable avant que l'onglet n'apparaisse.
function targets_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=md5(time().rand(0,65595));
    $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
    $btdisabled=false;
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($EnableActiveDirectoryFeature==0){$btdisabled=true;}
    if(!$CORP_LICENSE){$btdisabled=true;}

    $error="";
    $results=rdpproxy_targets($error);
    if($error!=""){echo $tpl->div_error($error);return;}

    // Strategies qui donnent acces a chaque cible.
    $perr="";
    $byTarget=array();
    foreach(rdpproxy_policies($perr) as $p){
        $derr="";
        $d=rdpproxy_policy(intval($p["id"]),$derr);
        if($derr!=""||!isset($d["targets"])||!is_array($d["targets"])){continue;}
        foreach($d["targets"] as $tg){$byTarget[intval($tg["id"])][]=$p;}
    }
    // Sessions ouvertes.
    $serr="";
    $nSess=0;$users=array();
    foreach(rdpproxy_sessions($serr) as $s){
        if(isset($s["state"])&&$s["state"]!="connected"){continue;}
        $nSess++;
        $u=isset($s["username"])?strtolower(trim((string)$s["username"])):"";
        if($u!=""){$users[$u]=true;}
    }

    $total=0;$nOn=0;$nOrphan=0;$nAD=0;$nDecoy=0;$protos=array();
    $rows=array();$ids=array();
    foreach($results as $ligne){
        $ID=intval($ligne["id"]);
        $pols=isset($byTarget[$ID])?$byTarget[$ID]:array();
        $f=rdpt_facts($ligne,$pols);
        $total++;
        if($f["on"]){$nOn++;}
        if(!$f["covered"]){$nOrphan++;}
        if($f["ad"]){$nAD++;}
        if($f["decoy"]){$nDecoy++;}
        if(!isset($protos[$f["proto"]])){$protos[$f["proto"]]=0;}
        $protos[$f["proto"]]++;
        $rows[]=rdpt_row($tpl,$page,$ligne,$f);
        $ids[]=$ID;
    }
    ksort($protos);

    $html=array();
    $html[]="<div class='rdpm ak'>";
    $html[]="<div class='ak-grid-4 rdpm-tiles'>";
    $pl=array();
    foreach($protos as $pr=>$n){$pl[]="<b>$n</b> ".htmlspecialchars($pr,ENT_QUOTES);}
    $html[]=rdpm_tile("fas fa-server","ak-ok","{rdpproxy_targets_total}",$total,
        count($pl)>0?@implode(" &bull; ",$pl):"&nbsp;");
    $html[]=rdpm_tile("fas fa-power-off","ak-info","{rdpproxy_targets_enabled}","$nOn<small>/$total</small>",
        ($total-$nOn)>0?"<b>".($total-$nOn)."</b> {rdpproxy_disabled_n}":"{rdpproxy_all_targets_on}");
    $html[]=rdpm_tile("fas fa-display","ak-info","{rdpproxy_open_sessions}",$nSess,
        "<b>".count($users)."</b> {rdpproxy_distinct_users}");
    $covCls=$nOrphan>0?"ak-warn rdpm-alert":"ak-ok";
    $html[]=rdpm_tile("fas fa-shield-halved",$covCls,"{rdpproxy_coverage}",($total-$nOrphan)."<small>/$total</small>",
        $nOrphan>0?"<b>$nOrphan</b> {rdpproxy_without_active_policy}":"{rdpproxy_all_targets_covered}");
    $html[]="</div>";

    $filters=array("all"=>array("{all}",$total));
    foreach($protos as $pr=>$n){$filters["p-".strtolower($pr)]=array(htmlspecialchars($pr,ENT_QUOTES),$n);}
    $filters["on"]=array("{rdpproxy_enabled_f}",$nOn);
    $filters["off"]=array("{rdpproxy_disabled_f}",$total-$nOn);
    $filters["orphan"]=array("{rdpproxy_without_active_policy_f}",$nOrphan);
    $filters["ad"]=array("{active_directory_groups}",$nAD);
    $filters["decoy"]=array("{rdpproxy_decoy}",$nDecoy);
    $html[]="<div class='ak-tools'>";
    $html[]="<label class='ak-search rdpm-search'><i class='fas fa-search'></i><input type='text' id='search-$t' autocomplete='off' placeholder=\"{search}\"></label>";
    $html[]="<div class='ak-seg rdpm-filters' id='filters-$t'>";
    foreach($filters as $k=>$f){
        if($k!="all" && $f[1]==0){continue;}
        $cls=$k=="all"?"ak-seg-b on":"ak-seg-b";
        $html[]="<button type='button' class='$cls' data-f='$k'>{$f[0]} <span>{$f[1]}</span></button>";
    }
    $html[]="</div></div>";

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table id='table-$t' class='awc-tbl rdpm-tbl'><thead><tr>";
    $html[]="<th>{status}</th><th>{rdpproxy_col_target}</th><th>{rdpproxy_col_host}</th><th>{rdpproxy_col_account}</th>";
    $html[]="<th>{policies}</th><th class='center'>{enabled}</th><th class='awc-right'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='rows-$t'>";
    if($total==0){
        $html[]=rdpc_empty(7,"{no_item}");
    }else{
        $html[]=@implode("\n",$rows);
        $html[]="<tr id='nomatch-$t' style='display:none'><td colspan='7'><div class='awc-empty'>{no_data}</div></td></tr>";
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{targets}: <b id='count-$t'>$total</b>".($total>0?" / $total":"")."</span></div>";
    $html[]="</div></div></div>";

    if(!rdpproxy_meta_locked() || rdpproxy_tunnel_supported()){
        $topbuttons[]=array("Loadjs('$page?target-js=0')","fas fa-server","{new_target}","AllowAddUsers");
    }
    if(!rdpproxy_meta_locked()){
        $topbuttons[]=array("Loadjs('$page?target-ad-js=0')","fas fa-users","{active_directory_group}",$btdisabled?"FalseLicense":"AllowAddUsers");
    }
    $topbuttons[]=array("LoadAjax('rdpproxy-target-table','$page?targets-table=yes');",ico_refresh,"{reload}");
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_RDPPROXY} v$PrivoxyVersion {targets}";
    $TINY_ARRAY["ICO"]="fa-solid fa-building-shield";
    $TINY_ARRAY["EXPL"]="{rdpproxy_policies_explain_web}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    // Les tests de connexion partent une fois le tableau en place.
    $tests=array();
    foreach($ids as $ID){$tests[]="LoadAjaxTiny('target-status-$ID','$page?target-status=$ID');";}
    $js="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny\n".rdpm_filter_js($t)."\n".@implode("\n",$tests)."</script>";
    echo awc_table_style().rdpm_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js;
}

// Faits d'une cible : protocole, groupe AD, activation, strategies.
function rdpt_facts($l,$pols):array{
    $f=array();
    $f["ad"]=(bool)preg_match("#(CN|DC|OU)=#i",(string)$l["alias"]);
    $f["decoy"]=!empty($l["decoy"]);
    // un leurre n'est jamais "enabled" : son interrupteur est decoy_enabled
    $f["on"]=$f["decoy"]?!empty($l["decoy_enabled"]):!empty($l["enabled"]);
    $pr=strtoupper(trim((string)$l["proto_dest"]));
    $f["proto"]=$pr==""?"RDP":$pr;
    $f["pols"]=$pols;
    $f["covered"]=false;
    foreach($pols as $p){if(!empty($p["enabled"])){$f["covered"]=true;}}
    $cats=array("p-".strtolower($f["proto"]),$f["on"]?"on":"off");
    if(!$f["covered"]){$cats[]="orphan";}
    if($f["ad"]){$cats[]="ad";}
    if($f["decoy"]){$cats[]="decoy";}
    $f["cats"]=@implode(" ",$cats);
    return $f;
}

// rdpt_short_fingerprint : « SHA256:AbCdEfGhIjKl… » (12 caracteres de l'empreinte) ;
// l'empreinte entiere reste dans l'infobulle et dans la fiche de la cible.
function rdpt_short_fingerprint($fp):string{
    $fp=trim((string)$fp);
    $pre="";
    $p=strpos($fp,":");
    if($p!==false && $p<10){$pre=substr($fp,0,$p+1);$fp=substr($fp,$p+1);}
    if(strlen($fp)>12){$fp=substr($fp,0,12)."\u{2026}";}
    return $pre.$fp;
}

function rdpt_row($tpl,$page,$l,$f):string{
    $ID=intval($l["id"]);
    $l["enabled"]=$f["on"]?1:0;
    $md=md5(serialize($l));
    $isDecoy=!empty($f["decoy"]);
    $alias=(string)$l["alias"];
    $designation=(string)$l["designation"];
    $host=(string)$l["target_host"];
    $device=(string)$l["target_device"];
    $port=intval($l["target_port"]);
    $js="Loadjs('$page?target-js=$ID')";
    $search=array($alias,$designation,$host,$device,(string)$port,$f["proto"]);

    $name=$alias;
    $sub=htmlspecialchars($designation,ENT_QUOTES);
    $hostHtml="<div class='rdpm-host'>".rdpc_link($host.($port>0?":$port":""),$js)."</div>";
    $ico="fas fa-server";$tone=$f["on"]?"ak-ok":"ak-off";
    $webUrl=isset($l["web_url"])?(string)$l["web_url"]:"";
    if($f["proto"]=="WEB"){
        // Application web : l'adresse complete, coupee a l'affichage, entiere dans l'infobulle.
        $ico="fas fa-globe";
        $search[]=$webUrl;
        $short=strlen($webUrl)>60?substr($webUrl,0,60)."\u{2026}":$webUrl;
        $hostHtml="<div class='rdpm-host' title=\"".htmlspecialchars($webUrl,ENT_QUOTES)."\">".rdpc_link($short,$js)."</div>";
    }
    if($f["ad"]){
        // Groupe AD : le nom et les ordinateurs membres, lus dans l'annuaire.
        $ad=new external_ad_search();
        $arrayAD=$ad->DNDUMP($alias);
        $js="Loadjs('$page?target-ad-js=$ID')";
        $name=isset($arrayAD["name"][0])?$arrayAD["name"][0]:rdpproxy_cn($alias);
        $CMPS=array();
        $cnt=isset($arrayAD["member"]["count"])?intval($arrayAD["member"]["count"]):0;
        for($i=0;$i<$cnt;$i++){
            $marr=$ad->DNDUMP($arrayAD["member"][$i]);
            if(isset($marr["dnshostname"])){$CMPS[]=$marr["dnshostname"][0];continue;}
            if(isset($marr["samaccountname"][0])){$CMPS[]=str_replace("$","",$marr["samaccountname"][0]);}
        }
        $search=array_merge($search,$CMPS,array($name));
        $ico="fas fa-users";$tone=$f["on"]?"ak-info":"ak-off";
        $list=htmlspecialchars(@implode(", ",$CMPS),ENT_QUOTES);
        $hostHtml="<div class='rdpm-host'>".rdpc_link($name,$js)."</div>"
            .($list!=""?"<div class='rdpm-sub rdpm-wrap' title=\"$list\">$list</div>":"");
    }elseif($device!="" && $device!=$host){
        $hostHtml.="<div class='rdpm-sub'>{rdpproxy_connect_via} <span class='ak-mono'>".htmlspecialchars($device,ENT_QUOTES)."</span></div>";
    }

    // Cible & description
    $nla=array(0=>"{RDP_NLA_NTLM}",1=>"{RDP_NLA_KERBEROS_NTLM}",2=>"{RDP_NLA_KERBEROS}",3=>"{RDP_NLA_TLS}");
    $who="<div class='rdpm-id'><span class='ak-ico ak-ico-sm ak-ico-sq $tone'><i class='$ico'></i></span><div class='rdpm-id-t'>";
    $who.="<div class='rdpm-name'>".rdpc_link($name,$js)." <span class='ak-tag ak-warn'>".htmlspecialchars($f["proto"],ENT_QUOTES)."</span>"
        .($isDecoy?" <span class='ak-tag ak-warn'><i class='fas fa-user-secret'></i> {rdpproxy_decoy}</span>":"")."</div>";
    if($sub!=""){$who.="<div class='rdpm-desc'>$sub</div>";}
    if($f["proto"]=="RDP" && !$isDecoy){
        $nm=intval($l["nla_mode"]);
        $who.="<div class='rdpm-sub'><i class='fas fa-key'></i> ".(isset($nla[$nm])?$nla[$nm]:$nla[0])."</div>";
        if(!empty($l["kerberos_ntlm_only"])){$who.="<div class='rdpm-sub' style='color:#b45309'><i class='fas fa-triangle-exclamation'></i> {rdpproxy_nla_kerberos_ip_warning}</div>";}
    }
    if($f["proto"]=="RDP" || $f["proto"]=="VNC"){
        $ta=trim(isset($l["tunnel_agent"])?(string)$l["tunnel_agent"]:"");
        if($ta!=""){$search[]=$ta;$who.="<div class='rdpm-sub'><i class='fas fa-plug'></i> {rdpproxy_tunnel_agent}: <span class='ak-mono'>".htmlspecialchars($ta,ENT_QUOTES)."</span></div>";}
    }
    if($f["proto"]=="WEB"){
        $allow=trim(isset($l["web_allow"])?(string)$l["web_allow"]:"");
        $hosts=$allow==""?array():explode(",",$allow);
        foreach($hosts as $h){$search[]=$h;}
        $txt=count($hosts)==0?"{rdpproxy_web_allow_url_host}":htmlspecialchars(@implode(", ",array_slice($hosts,0,3)),ENT_QUOTES).(count($hosts)>3?" +".(count($hosts)-3):"");
        $who.="<div class='rdpm-sub' title=\"".htmlspecialchars(@implode(", ",$hosts),ENT_QUOTES)."\"><i class='fas fa-shield-halved'></i> $txt</div>";
    }elseif($f["proto"]=="SSH"){
        $vault=(isset($l["ssh_auth"])&&$l["ssh_auth"]=="vault");
        $kinds=array("password"=>"{rdpproxy_ssh_kind_password}","private_key"=>"{rdpproxy_ssh_kind_private_key}","bastion_key"=>"{rdpproxy_ssh_kind_bastion_key}");
        $kd=isset($l["ssh_secret_kind"],$kinds[$l["ssh_secret_kind"]])?$kinds[$l["ssh_secret_kind"]]:"";
        $who.="<div class='rdpm-sub'><i class='fas fa-key'></i> ".($vault?"{rdpproxy_ssh_auth_vault}".($kd!=""?" &bull; $kd":""):"{rdpproxy_ssh_auth_prompt}")."</div>";
        $fp=trim(isset($l["ssh_hostkey_fingerprint"])?(string)$l["ssh_hostkey_fingerprint"]:"");
        if($fp!=""){
            $search[]=$fp;
            $who.="<div class='rdpm-sub' title=\"".htmlspecialchars($fp,ENT_QUOTES)."\"><i class='fas fa-fingerprint'></i> <span class='ak-mono'>".htmlspecialchars(rdpt_short_fingerprint($fp),ENT_QUOTES)."</span></div>";
        }else{
            $who.="<div class='rdpm-sub'><i class='fas fa-fingerprint'></i> {rdpproxy_ssh_hostkey_pending}</div>";
        }
    }
    $who.="</div></div>";

    // Compte
    $login=trim((string)$l["target_login"]);
    if($isDecoy){
        $hostHtml="<span class='ak-muted'>&ndash;</span>";
        $acc="<span class='ak-muted'>&ndash;</span>";
    }elseif($f["ad"]){
        $acc="<span class='ak-muted'>&ndash;</span>";
    }elseif($f["proto"]=="WEB"){
        // aucun identifiant n'est transmis a une application web
        $acc="<span class='rdpm-sub'>{rdpproxy_web_no_credentials}</span>";
        // connexion automatique (lot A) : le compte injecté par le bastion, jamais le mot de passe
        $wlm=isset($l["web_login_mode"])?(string)$l["web_login_mode"]:"none";
        if($wlm=="http"||$wlm=="form"){
            $wlu=isset($l["web_login_user"])?(string)$l["web_login_user"]:"";
            $search[]=$wlu;
            $acc="<span class='rdpm-sub'><i class='fas fa-key'></i> {rdpproxy_weblogin_row}: "
                .htmlspecialchars($wlu,ENT_QUOTES).($wlm=="http"?" <span class='ak-tag ak-warn'>http</span>":"")."</span>";
        }
    }elseif($login==""){
        $acc="<span class='rdpm-sub'>{rdpproxy_user_credentials}</span>";
    }else{
        $search[]=$login;
        $acc="<div class='rdpm-acc'><i class='far fa-user'></i>".htmlspecialchars($login,ENT_QUOTES)."</div>";
        if(!empty($l["password_set"])||($f["proto"]=="SSH"&&!empty($l["ssh_secret_set"]))){$acc.="<div class='rdpm-sub'>{rdpproxy_password_stored}</div>";}
    }

    // Strategies
    $chips=array();
    foreach($f["pols"] as $p){
        $gpid=intval($p["id"]);
        $pn=(string)$p["name"];
        if(trim($pn)==""){continue;}
        $search[]=$pn;
        $on=!empty($p["enabled"]);
        $cls=$on?"rdpm-pol":"rdpm-pol rdpm-pol-off";
        $title=$on?"":" title=\"{rdpproxy_policy_paused}\"";
        $chips[]="<span class='$cls'$title><i class='fas fa-shield-halved'></i>".rdpc_link($pn,"Loadjs('$page?group-js=$gpid')")."</span>";
    }
    $pol=count($chips)>0?"<div class='ak-chips'>".@implode("",$chips)."</div>"
        :"<span class='rdpm-warn'><i class='fas fa-triangle-exclamation'></i>{rdpproxy_no_policy}</span>";

    // Actions : le test relance target-status ; il ne modifie rien.
    // Cible tunnel : reste modifiable sous le verrou Artica Meta (spec 8.2).
    $tunnel=trim(isset($l["tunnel_agent"])?(string)$l["tunnel_agent"]:"")!="";
    $test="LoadAjaxTiny('target-status-$ID','$page?target-status=$ID');";
    $acts="<div class='awc-acts'>"
        ."<a class='awc-act' title=\"{rdpproxy_test_connection}\" href=\"javascript:blur();\" OnClick=\"$test\"><i class='fas fa-bolt'></i></a>"
        .rdpc_act($js,"fas fa-pen","{rdpproxy_edit_target}",false,"AllowAddUsers",$tunnel)
        .rdpc_act("Loadjs('$page?target-delete=$ID&md=$md')",ico_trash,"{delete}",true,"AllowAddUsers",$tunnel)."</div>";

    $q=htmlspecialchars(strtolower(@implode(" ",$search)),ENT_QUOTES);
    $rowcls=$f["on"]?"":" class='rdpm-row-off'";
    return "<tr id='$md'$rowcls data-cat='{$f["cats"]}' data-q=\"$q\">"
        ."<td class='awc-nowrap'><span id='target-status-$ID'><span class='ak-pill ak-off'><i class='fas fa-spinner fa-spin'></i>&nbsp;{rdpproxy_testing}</span></span></td>"
        ."<td>$who</td><td>$hostHtml</td><td>$acc</td><td>$pol</td>"
        ."<td class='center awc-nowrap'>".$tpl->icon_check($f["on"]?1:0,"Loadjs('$page?target-enable=$ID')",null,$tunnel?"AllowAddUsers":rdpproxy_meta_sec("AllowAddUsers"))."</td>"
        ."<td>$acts</td></tr>";
}

// ---------------------------------------------------------------- motifs (E1)
// $gpid = 0 : liste globale (ouverte depuis fw.rdpproxy.status.php).

function rdpx_patterns_start(){
    $page=CurrentPageName();
    $gpid=intval($_GET["patterns-start"]);
    echo "<div id='rdpproxy-patterns-$gpid' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-patterns-$gpid','$page?patterns-table=$gpid');</script>";
}

function rdpx_patterns_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=intval($_GET["patterns-table"]);
    $t=md5(time().$gpid.rand(0,65595));
    $error="";
    $list=rdpproxy_patterns($gpid,$error);
    if($error!=""){echo $tpl->div_error($error);return;}
    $html=array();
    // OCR et enregistrement ne concernent que les motifs de TITRE (lus a l'image). Un motif
    // de commande SSH est lu dans le terminal, et une session SSH est toujours enregistree
    // (authhook force is_rec) : ces avertissements ne s'affichent que s'il y a un motif de
    // titre, ou si le bastion ne relaie pas SSH (tout motif y est alors un motif de titre).
    $hasTitle=false;
    foreach($list as $l){
        if(!(isset($l["scope"])&&$l["scope"]=="command")){$hasTitle=true;break;}
    }
    $titleRelevant=$hasTitle || !rdpproxy_ssh_supported();
    // Moteur ppocr d'origine : barres colorees des anciens Windows seulement. Le paquet
    // Artica 12.3.17 ajoute Tesseract (titres de Windows 10/11 et 2016+), allume par
    // RDPProxyOCRTesseract.
    if($titleRelevant){
    switch(rdpproxy_ocr_engine()){
        case "tesseract":
            $html[]=$tpl->div_explain("{rdpproxy_patterns_ocr_enhanced}||{rdpproxy_patterns_ocr_enhanced_explain}");
            break;
        case "available_off":
            $html[]=$tpl->div_warning("{rdpproxy_patterns_ocr_off}||{rdpproxy_patterns_ocr_off_explain}");
            break;
        default:
            // Jetons resolus d'abord : replace_strings() ne convertit le marqueur de saut de ligne qu'a l'interieur d'une valeur.
            $legacy_text=$tpl->_ENGINE_parse_body("{rdpproxy_patterns_ocr_limit_explain}<br>{rdpproxy_patterns_ocr_update}");
            $html[]=$tpl->div_warning("{rdpproxy_patterns_ocr_limit}||".$legacy_text);
    }
    }
    if($gpid>0 && $hasTitle){
        $p=rdpproxy_policy($gpid,$error);
        if($error=="" && empty($p["recording"])){
            $html[]=$tpl->div_warning("{rdpproxy_patterns_need_recording}||{rdpproxy_patterns_need_recording_explain}");
        }
    }
    $html[]=$tpl->_ENGINE_parse_body(rdpproxy_meta_banner());
    $html[]=$tpl->_ENGINE_parse_body("<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{new_pattern}","Loadjs('$page?pattern-js=0&gpid=$gpid')","fas fa-plus",rdpproxy_meta_sec("AllowAddUsers"))."</div>");
    $rows=array();
    foreach($list as $l){
        $ID=intval($l["id"]);
        $md=md5(serialize($l));
        $kill=$l["action"]=="kill";
        $badge=$kill?"<span class='awc-badge awc-badge-alert'>{rdpproxy_pattern_kill}</span>"
                    :"<span class='awc-badge awc-badge-warn'>{rdpproxy_pattern_notify}</span>";
        if($l["action"]=="refuse"){$badge="<span class='awc-badge awc-badge-alert'>{rdpproxy_pattern_refuse}</span>";}
        // Un motif sans portee (daemon ancien) est un motif de titre.
        $isCmd=(isset($l["scope"])&&$l["scope"]=="command");
        $scopeTxt=$isCmd?"{rdpproxy_scope_command}":"{rdpproxy_scope_title}";
        $js="Loadjs('$page?pattern-js=$ID&gpid=$gpid')";
        $r="<tr id='$md'>";
        $r.="<td class='awc-nowrap'>$badge</td>";
        $r.="<td class='awc-nowrap'>$scopeTxt</td>";
        $r.="<td class='awc-mono'>".rdpc_link($l["pattern"],$js)."</td>";
        $r.="<td>".htmlspecialchars((string)$l["description"],ENT_QUOTES)."</td>";
        $r.="<td class='center awc-nowrap'>".$tpl->icon_check(!empty($l["enabled"])?1:0,"Loadjs('$page?pattern-enable=$ID&gpid=$gpid')",null,rdpproxy_meta_sec("AllowAddUsers"))."</td>";
        $r.="<td><div class='awc-acts'>".rdpc_act("Loadjs('$page?pattern-delete=$ID&md=$md')",ico_trash,"{delete}",true)."</div></td>";
        $r.="</tr>";
        $rows[]=$r;
    }
    $html[]=rdpc_table_open($t,array("<th>{action}</th>","<th>{scope}</th>","<th>{pattern}</th>","<th>{description}</th>","<th class='center'>{enabled}</th>","<th class='awc-right'>&nbsp;</th>"));
    if(count($rows)==0){$html[]=rdpc_empty(6,"{no_item}");}else{$html[]=@implode("\n",$rows);}
    $html[]=rdpc_table_close($t,count($rows));
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).rdpc_scripts($tpl,$t);
}

function rdpx_pattern_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["pattern-js"]);
    $gpid=intval($_GET["gpid"]);
    $title=$ID==0?"{new_pattern}":"{pattern}";
    $tpl->js_dialog2($title,"$page?pattern-popup=$ID&gpid=$gpid",650);
}

function rdpx_pattern_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["pattern-popup"]);
    $gpid=intval($_GET["gpid"]);
    $l=array("action"=>"notify","pattern"=>"","description"=>"","scope"=>"title");
    if($ID>0){
        $error="";
        foreach(rdpproxy_patterns($gpid,$error) as $x){if(intval($x["id"])==$ID){$l=$x;}}
    }
    $scope=(isset($l["scope"])&&$l["scope"]=="command")?"command":"title";
    // Un daemon sans SSH ignorerait "scope" et enregistrerait un motif de TITRE (OCR).
    $cmdOk=rdpproxy_ssh_supported()?"true":"false";
    $form=array();
    // « Refuser » n'existe que pour les commandes SSH : le daemon le refuse pour un titre
    // de fenetre, et la liste le grise (RdpPatScope) des qu'on repasse sur « titres ».
    $actions=array("notify"=>"{rdpproxy_pattern_notify}","kill"=>"{rdpproxy_pattern_kill}","refuse"=>"{rdpproxy_pattern_refuse}");
    $scopes=array("title"=>"{rdpproxy_scope_title}","command"=>"{rdpproxy_scope_command}");
    $tpl->field_hidden("pattern-id",$ID);
    $tpl->field_hidden("pattern-gpid",$gpid);
    $idScope=md5("scope".$tpl->suffixid);
    $idAction=md5("action".$tpl->suffixid);
    $form[]=$tpl->field_array_hash($scopes,"scope","nonull:{scope}",$scope,false,"{rdpproxy_scope_explain}","RdpPatScope");
    $form[]=$tpl->field_array_hash($actions,"action","nonull:{action}",$l["action"]);
    $form[]=$tpl->field_text("pattern","{pattern}",$l["pattern"],true,"{rdpproxy_pattern_help}");
    $form[]=$tpl->field_text("description","{description}",$l["description"]);
    $js="dialogInstance2.close();LoadAjax('rdpproxy-patterns-$gpid','$page?patterns-table=$gpid');";
    rdpproxy_meta_lock_form($tpl);
    echo $tpl->form_outside("",$form,null,$ID==0?"{add}":"{apply}",$js,"AllowAddUsers");
    echo "<script>
(function(){
 var S='$idScope', A='$idAction', CMD=$cmdOk;
 function sync(){
  var sel=document.getElementById(A), sc=document.getElementById(S);
  if(!sel||!sc)return;
  var cmd=(sc.value==='command');
  var oc=sc.querySelector('option[value=\"command\"]');
  if(oc){oc.disabled=!CMD;if(window.jQuery){jQuery('#'+S).trigger('chosen:updated');}}
  var o=sel.querySelector('option[value=\"refuse\"]');
  if(o){o.disabled=!cmd;}
  if(!cmd&&sel.value==='refuse'){sel.value='notify';}
  if(window.jQuery){jQuery('#'+A).trigger('chosen:updated');}
 }
 window.RdpPatScope=function(){sync();};
 sync();
 setTimeout(sync,1300);
})();
</script>";
}

// N'écrit RIEN en cas de succès (sinon la modale ne se ferme pas).
function rdpx_pattern_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["pattern-id"]);
    $scope=rdpc_post("scope")=="command"?"command":"title";
    $action=rdpc_post("action");
    if($scope=="command" && !rdpproxy_ssh_supported()){
        echo $tpl->post_error("{rdpproxy_ssh_needs_update}");return;
    }
    // Meme regle que le daemon, dite avant l'envoi : « refuser » ne s'applique qu'aux commandes.
    if($action=="refuse" && $scope!="command"){
        echo $tpl->post_error("{rdpproxy_pattern_refuse_command_only}");return;
    }
    $err=rdpproxy_pattern_save($ID,array("policy_id"=>intval($_POST["pattern-gpid"]),
        "scope"=>$scope,"action"=>$action,"pattern"=>rdpc_post("pattern"),"description"=>rdpc_post("description")));
    if($err!=""){echo $tpl->post_error($err);return;}
    admin_tracks(($ID==0?"Created":"Updated")." RDS Proxy session pattern ".rdpc_post("pattern"));
}

function rdpx_pattern_delete_ask(){
    $tpl=new template_admin();
    $ID=intval($_GET["pattern-delete"]);
    $md=preg_replace('/[^a-f0-9]/','',(string)$_GET["md"]);
    $tpl->js_confirm_delete("{pattern} #$ID","pattern-delete",$ID,"$('#$md').remove()");
}

function rdpx_pattern_delete_perform(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AllowAddUsers")){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["pattern-delete"]);
    $err=rdpproxy_pattern_delete($ID);
    if($err!=""){echo $err;return;}
    admin_tracks("Deleted RDS Proxy session pattern #$ID");
}

function rdpx_pattern_enable(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["pattern-enable"]);
    $gpid=intval($_GET["gpid"]);
    header("content-type: application/x-javascript");
    if(!$tpl->IsSecurity("AllowAddUsers")){$tpl->js_mysql_alert("{ERROR_NO_PRIVS2}");return;}
    // Etat COURANT lu au daemon : l'icone bascule cote client, un "was" fige serait faux au 2e clic.
    $error="";
    $list=rdpproxy_patterns($gpid,$error);
    if($error!=""){$tpl->js_mysql_alert($error);return;}
    $found=false;$cur=false;
    foreach($list as $x){if(intval($x["id"])==$ID){$found=true;$cur=!empty($x["enabled"]);}}
    if(!$found){$tpl->js_mysql_alert("{not_found}");return;}
    $enabled=rdpproxy_pattern_toggle($ID,!$cur,$error);
    if($error!=""){$tpl->js_mysql_alert($error);return;}
    admin_tracks(($enabled?"Enabled":"Disabled")." RDS Proxy session pattern #$ID");
    echo "LoadAjax('rdpproxy-patterns-$gpid','$page?patterns-table=$gpid');";
}

function rdpx_pattern_global_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{rdpproxy_global_patterns}","$page?patterns-start=0",900);
}


// ---- copier / coller d'une cible WEB (droits des stratégies qui y donnent accès) --------

function target_webclip(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["target-webclip"]);
    $error="";
    $list=rdpproxy_target_policies_clipboard($ID,$error);
    if($error!=""){echo $tpl->div_error($error);return;}
    $html=array();
    if(!rdpproxy_web_clipboard_state()["supported"]){
        $html[]="<div class='text-warning' style='font-size:12px;line-height:1.5;margin-bottom:6px'><i class='fas fa-triangle-exclamation'></i> {rdpproxy_webclip_unsupported}</div>";
    }
    if(!is_array($list)||count($list)==0){
        $html[]="<div class='text-muted' style='font-size:12px'><i class='fas fa-circle-info'></i> {rdpproxy_webclip_target_none}</div>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$html));
        return;
    }
    $html[]="<table class='table' style='margin:0;font-size:13px'><thead><tr><th>{rdpproxy_webclip_col_policy}</th>"
        ."<th style='text-align:center'>{rdpproxy_webclip_col_paste}</th><th style='text-align:center'>{rdpproxy_webclip_col_copy}</th>"
        ."<th style='text-align:center'>{rdpproxy_webclip_col_targets}</th></tr></thead><tbody>";
    foreach($list as $p){
        $pid=intval($p["id"]);
        $name=htmlspecialchars((string)$p["name"]);
        if(empty($p["enabled"])){$name.=" <span class='label label-default'>{disabled}</span>";}
        $locked=rdpproxy_meta_locked();
        $cell=function($dir,$on) use($page,$ID,$pid,$locked){
            $icon=$on?"fas fa-toggle-on":"fas fa-toggle-off";
            $color=$on?"#1ab394":"#999";
            if($locked){return "<td style='text-align:center'><span style='font-size:22px;color:#ccc'><i class='$icon'></i></span></td>";}
            $js="Loadjs('$page?target-webclip-set=$pid&target=$ID&dir=$dir&value=".($on?0:1)."')";
            return "<td style='text-align:center'><a href='javascript:void(0)' OnClick=\"$js\" style='font-size:22px;color:$color'><i class='$icon'></i></a></td>";
        };
        $html[]="<tr><td>$name</td>".$cell("up",!empty($p["web_clipboard_up"])).$cell("down",!empty($p["web_clipboard_down"]))
            ."<td style='text-align:center'>".intval($p["targets"])."</td></tr>";
    }
    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}

function target_webclip_set(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){$tpl->js_no_privileges();return;}
    if(rdpproxy_meta_refuse_js()){return;}
    $pid=intval($_GET["target-webclip-set"]);
    $tid=intval($_GET["target"] ?? 0);
    $dir=($_GET["dir"] ?? "")=="down"?"down":"up";
    $value=intval($_GET["value"] ?? 0)==1;
    $err=rdpproxy_policy_web_clipboard_set($pid,$dir,$value);
    if($err!=""){$tpl->js_mysql_alert(rdpproxy_ssh_error($err));return;}
    admin_tracks("RDS Proxy policy ".rdpc_name("policy",$pid)." (#$pid): web clipboard $dir ".($value?"allowed":"denied"));
    echo "LoadAjaxSilent('rdpt-webclip-$tid','$page?target-webclip=$tid');";
}
