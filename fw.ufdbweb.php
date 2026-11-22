<?php
// Service Pack 9 2026-10-10
$GLOBALS["redirtypes"][0]="{ngix_rwfl_redirect}";
$GLOBALS["redirtypes"][1]="{ngix_rwfl_permanent}";
$GLOBALS["redirtypes"][2]="{inside_redirection}";
$GLOBALS["redirtypes"][3]="{reset_connection}";

$GLOBALS["protocol"][0]="{all}";
$GLOBALS["protocol"][1]="GET";
$GLOBALS["protocol"][2]="CONNECT";
$GLOBALS["protocol"][3]="POST";



include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : eth etait recopie sans encodage dans la page (XSS).
if(isset($_GET["eth"])){$_GET["eth"]=preg_replace('/[^A-Za-z0-9_.:@-]/','',(string)$_GET["eth"]);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["start-rules"])){start_rules();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["enable-rule-js"])){rule_enable();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_GET["rule-move"])){rule_move();exit;}
if(isset($_POST["rule-delete-confirm"])){rule_delete_confirm();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{banned_page_webservice}","fad fa-page-break",
        "{APP_UFDB_HTTP_EXPLAIN}","$page?tabs=yes","webfiltering-webpages",
        "progress-ufdbweb-restart,progress-weberrorules-restart",false,"table-ufdbweb-tabs");


	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_UFDB_HTTP}",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function start_rules(){
    $page=CurrentPageName();
    echo "<div id='ufdbpages-rules'></div><script>LoadAjax('ufdbpages-rules','$page?table=yes');</script>";
    
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$users=new usersMenus();
	$EnableUfdbGuard=intval($sock->GET_INFO('EnableUfdbGuard'));
    $UfdbUseInternalService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService"));

    $array["{service_status}"]="fw.proxy.errors.page.php?service-status-start=yes";

	if($EnableUfdbGuard==1){
            $array["{redirect_rules}"] = "$page?start-rules=yes";
	}


    if ($users->AsSquidAdministrator or $users->AsDnsAdministrator) {
            $array["{rules}"] = "fw.ufdbweb.rules.php?table2=yes";
        }
    if ($EnableUfdbGuard == 1) {
        if ($users->AsDansGuardianAdministrator or $users->AsProxyMonitor) {
            $array["{unblock_list}"] = "fw.ufdbweb.unblock.php";
            $array["{requests_list}"] = "fw.ufdbweb.requests.php";
        }
        $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
        if ($DisablePostGres == 0) {
            $array["{events}"] = "fw.ufdbweb.users-events.php";
        }
    }

    if($UfdbUseInternalService==1){
        $array["{service_events}"]="fw.proxy.errors.page.php?service-status-events=yes";

    }

	echo $tpl->tabs_default($array);

}
function rule_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-popup"]);
    $title="{new_rule}";
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $ligne=$q->mysqli_fetch_array("SELECT rulename FROM ufdb_errors WHERE ID='$ID'");
        $title="{rule}: {$ligne["rulename"]}";
    }
    $array[$title]="$page?rule-settings=$ID";
    echo $tpl->tabs_default($array);

}
function rule_settings():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=True;
    $ID=intval($_GET["rule-settings"]);
    $title="{new_rule}";
    $but="{wrr_create}";
    $ligne=array("rulename"=>"","enabled"=>1,"protocol"=>0,"webruleid"=>0,"category"=>0,"redirwebserv"=>0,"url"=>"","redirtype"=>0);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    if($ID>0) {
        $r=$q->QUERY_SQL_PARAMS("SELECT * FROM ufdb_errors WHERE ID=?",array($ID));
        if(is_array($r) && isset($r[0])){$ligne=array_merge($ligne,$r[0]);}
        $title = "{rule}: ".htmlspecialchars((string)$ligne["rulename"]);
        $but="{wrr_save}";
    }
    $webruleids[0]="{wrr_everyone}";
    $exit=$ID==0?"dialogInstance1.close();":"";
    $jsafter="LoadAjax('ufdbpages-rules','$page?table=yes');$exit";
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));

    $form=array();
    $form[]=$tpl->field_hidden("rule-save", "$ID");
    $form[]=$tpl->field_text("rulename", "{wrr_f_name}", $ligne["rulename"],true,"{wrr_f_name_help}");
    $form[]=$tpl->field_webfiltering_rules("webruleid","nonull:{wrr_f_policy}",$ligne["webruleid"],"{wrr_f_policy_help}",$webruleids);
    $form[]=$tpl->field_categories_list("category","{wrr_f_category}",intval($ligne["category"]),"{wrr_f_category_help}");
    $form[]=$tpl->field_array_hash(wrr_protocols(), "zprotocol", "nonull:{wrr_f_visit}", intval($ligne["protocol"]),false,"{wrr_f_visit_help}");
    if($EnableNginx==0) {
        $form[] = $tpl->field_checkbox_negative("redirwebserv", "{wrr_f_service}", $ligne["redirwebserv"], "redirtype,url","{wrr_f_service_help}");
    }
    $form[] = $tpl->field_text("url", "{wrr_f_url}", $ligne["url"],false,"{wrr_f_url_help}");
    $form[]=$tpl->field_array_hash(wrr_redirtypes(), "redirtype", "nonull:{wrr_f_how}", intval($ligne["redirtype"]),false,"{wrr_f_how_help}");
    $form[]=$tpl->field_checkbox("enabled","{wrr_f_enabled}",$ligne["enabled"],false,"{wrr_f_enabled_help}");

    $html=$tpl->form_outside($title, @implode("\n", $form),"{wrr_popup_explain}",$but,$jsafter,"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function GetWebErrorPageUri():string{

    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==1){return "";}

    $UfdbUseInternalServiceHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPPort"));
    $UfdbUseInternalServiceHTTPSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPSPort"));

    $UfdbUseInternalServiceHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHostname"));
    if($UfdbUseInternalServiceHostname==null){$UfdbUseInternalServiceHostname=php_uname("n");}
    if($UfdbUseInternalServiceHTTPPort==0){$UfdbUseInternalServiceHTTPPort=9025;}
    if($UfdbUseInternalServiceHTTPSPort==0){$UfdbUseInternalServiceHTTPSPort=9026;}
    $UfdbUseInternalServiceEnableSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceEnableSSL"));
    if($UfdbUseInternalServiceEnableSSL==1){
        return "https://$UfdbUseInternalServiceHostname:$UfdbUseInternalServiceHTTPSPort";
    }
    return "https://$UfdbUseInternalServiceHostname:$UfdbUseInternalServiceHTTPPort";

}



function rule_id_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ruleid-js"]);
    $title="{new_rule}";


    if($ID>0){
         $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
         $ligne=$q->mysqli_fetch_array("SELECT rulename FROM ufdb_errors WHERE ID='$ID'");
         $title="{rule}: {$ligne["rulename"]}";
    }



    $title=$tpl->javascript_parse_text($title);
    return $tpl->js_dialog1($title,"$page?rule-popup=$ID");

}
// ── Regles de redirection des pages bloquees (onglet « Redirect rules ») ─────
// Maquette stitch_artica_redirect_rules. Table ufdb_errors de webfilter.db,
// compilee par articarest (/weberror/rules -> WebErrorPagesCompiled) et
// appliquee par Go Shield (buildErrorPage) : premiere regle qui correspond,
// sinon http://articatech.net/block.html ; avec le service interne de pages
// d'erreur actif, l'adresse de chaque regle est remplacee par la sienne.

function wrr_can_write():bool{
    $users=new usersMenus();
    return $users->AsDansGuardianAdministrator || $users->AsSystemAdministrator;
}

// Numerotation de la console (stockee) : 0 tout, 1 GET, 2 CONNECT, 3 POST.
// articarest la traduit pour Go Shield (weberrorpage.ShieldProtocolID).
function wrr_protocols():array{
    return array(0=>"{wrr_v_any}",2=>"{wrr_v_https}",1=>"{wrr_v_http}",3=>"{wrr_v_post}");
}
function wrr_redirtypes():array{
    return array(0=>"{wrr_h_302}",1=>"{wrr_h_301}",2=>"{wrr_h_inside}",3=>"{wrr_h_artica}");
}

function wrr_t($token,$a="",$b=""):string{
    if(!isset($GLOBALS["WRR_T"])){$GLOBALS["WRR_T"]=array();}
    if(!isset($GLOBALS["WRR_T"][$token])){
        $tpl=new template_admin();
        $GLOBALS["WRR_T"][$token]=$tpl->_ENGINE_parse_body("{"."$token}");
    }
    return str_replace(array("%1","%2"),array($a,$b),$GLOBALS["WRR_T"][$token]);
}

function wrr_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

// Adresse du service interne, construite comme Go Shield (buildErrorPage).
function wrr_service_url():string{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $host=trim((string)$sock->GET_INFO("UfdbUseInternalServiceHostname"));
    if(strlen($host)<3){$host=(string)php_uname("n");}
    if(intval($sock->GET_INFO("EnableNginx"))==1){return "http(s)://$host";}
    $http=intval($sock->GET_INFO("UfdbUseInternalServiceHTTPPort"));
    $https=intval($sock->GET_INFO("UfdbUseInternalServiceHTTPSPort"));
    if($http==0){$http=9025;}
    if($https==0){$https=9026;}
    if(intval($sock->GET_INFO("UfdbUseInternalServiceEnableSSL"))==1){return "https://$host:$https";}
    return "http://$host:$http";
}

function wrr_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array("error"=>"","rules"=>array(),"on"=>0,
        "service"=>intval($sock->GET_INFO("UfdbUseInternalService"))==1,
        "nginx"=>intval($sock->GET_INFO("EnableNginx"))==1);
    $m["service_url"]=wrr_service_url();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $results=$q->QUERY_SQL("SELECT * FROM ufdb_errors ORDER BY zorder,ID");
    if(!$q->ok){$m["error"]=$q->mysql_error;return $m;}
    if(!is_array($results)){$results=array();}
    $policies=array();
    $qs=new mysql_catz();
    foreach($results as $l){
        $webruleid=intval($l["webruleid"]);
        if($webruleid>0 && !isset($policies[$webruleid])){
            $r=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_rules WHERE ID=?",array($webruleid));
            $policies[$webruleid]=(is_array($r) && isset($r[0]["groupname"]) && $r[0]["groupname"]!=="")?(string)$r[0]["groupname"]:"";
        }
        $cat=intval($l["category"]);
        $catName="";
        if($cat>0){$catName=(string)$qs->CategoryIntToStr($cat);}
        $rule=array("id"=>intval($l["ID"]),"name"=>(string)$l["rulename"],"enabled"=>intval($l["enabled"])==1,
            "protocol"=>intval($l["protocol"]),"webruleid"=>$webruleid,
            "policy"=>$webruleid>0?$policies[$webruleid]:"","category"=>$cat,"category_name"=>$catName,
            "useservice"=>!$m["nginx"] && intval($l["redirwebserv"])==1,"url"=>(string)$l["url"],"redirtype"=>intval($l["redirtype"]));
        $m["rules"][]=$rule;
        if($rule["enabled"]){$m["on"]++;}
    }
    return $m;
}

// ── Contenu ─────────────────────────────────────────────────────────────────

function table(){
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=True;
    $page=CurrentPageName();
    $m=wrr_model();
    $h=array();
    $h[]="<div class='wrr'>";
    $h[]=wrr_hero();
    $h[]=wrr_service_notice($m);
    if($m["error"]!==""){
        $h[]="<div class='wrr-alert'><i class='fas fa-circle-exclamation'></i><div>".wrr_h($m["error"])."</div></div>";
    }
    $h[]=wrr_rules_card($m);
    $h[]="</div>";

    $btns=array();
    if(wrr_can_write()){
        $btns[]=array("Loadjs('$page?ruleid-js=0');",ico_plus,"{new_rule}");
    }
    $btns[]=array("LoadAjax('ufdbpages-rules','$page?table=yes');",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{banned_page_webservice}";
    $TINY_ARRAY["ICO"]="fad fa-page-break";
    $TINY_ARRAY["EXPL"]="{APP_UFDB_HTTP_EXPLAIN}";
    $TINY_ARRAY["URL"]="webfiltering-webpages";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo wrr_style().$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>NoSpinner();$jstiny</script>";
}

// ── Widgets (une fonction chacun) ──────────────────────────────────────────

function wrr_hero():string{
    return "<div class='wrr-hero'><div class='wrr-hero-ico'><i class='fas fa-signs-post'></i></div><div class='wrr-hero-body'>"
        ."<h3>{wrr_hero_title}</h3><p>{wrr_hero_text}</p><div class='wrr-chips'>"
        ."<span class='wrr-chip'><i class='fas fa-arrow-down-1-9'></i> {wrr_chip_order}</span>"
        ."<span class='wrr-chip'><i class='fas fa-bolt'></i> {wrr_chip_fast}</span>"
        ."<span class='wrr-chip'><i class='fas fa-ban'></i> {wrr_chip_never}</span>"
        ."<span class='wrr-chip'><i class='fas fa-tags'></i> {wrr_chip_params}</span>"
        ."</div></div></div>";
}

// Le fait decisif que l'ancienne page taisait.
function wrr_service_notice($m):string{
    if(!$m["service"]){return "";}
    return "<div class='wrr-info'><i class='fas fa-circle-info'></i><div class='wrr-info-txt'>"
        .wrr_t("wrr_service_on","<span class='wrr-code'>".wrr_h($m["service_url"])."</span>")."</div>"
        ."<a href='/webfiltering-webpages' class='wrr-link'>{wrr_service_settings} <i class='fas fa-arrow-right'></i></a></div>";
}

function wrr_chip($ico,$label,$value,$muted=false):string{
    return "<span class='wrr-cond'><span class='wrr-cond-l'>$label</span><span class='wrr-tag".($muted?" wrr-tag-muted":"")."'><i class='$ico'></i> $value</span></span>";
}

function wrr_rule_row($r,$i,$n,$m):string{
    $page=CurrentPageName();
    $id=$r["id"];
    $edit="Loadjs('$page?ruleid-js=$id');";
    $can=wrr_can_write();
    $up=$i>0 && $can?"<button type='button' class='wrr-ibtn' aria-label=\"".wrr_h(wrr_t("wrr_move_up"))."\" title=\"".wrr_h(wrr_t("wrr_move_up"))."\" OnClick=\"Loadjs('$page?rule-move=$id&rule-dir=1');\"><i class='fas fa-arrow-up'></i></button>"
        :"<button type='button' class='wrr-ibtn' disabled aria-hidden='true'><i class='fas fa-arrow-up'></i></button>";
    $down=$i<$n-1 && $can?"<button type='button' class='wrr-ibtn' aria-label=\"".wrr_h(wrr_t("wrr_move_down"))."\" title=\"".wrr_h(wrr_t("wrr_move_down"))."\" OnClick=\"Loadjs('$page?rule-move=$id&rule-dir=0');\"><i class='fas fa-arrow-down'></i></button>"
        :"<button type='button' class='wrr-ibtn' disabled aria-hidden='true'><i class='fas fa-arrow-down'></i></button>";
    $policy=$r["webruleid"]==0?wrr_chip("fas fa-user-group","{wrr_c_applies}","{wrr_everyone}",true)
        :wrr_chip("fas fa-user-group","{wrr_c_applies}",$r["policy"]!==""?wrr_h($r["policy"]):"<span class='wrr-err'>{wrr_unknown_policy}</span>");
    $cat=$r["category"]==0?wrr_chip("fas fa-tags","{wrr_c_category}","{wrr_any_category}",true)
        :wrr_chip("fas fa-tags","{wrr_c_category}",$r["category_name"]!==""?wrr_h($r["category_name"]):"#".$r["category"]);
    $protos=wrr_protocols();
    $pl=isset($protos[$r["protocol"]])?$protos[$r["protocol"]]:"{wrr_v_any}";
    $proto=wrr_chip($r["protocol"]==2?"fas fa-lock":"fas fa-globe","{wrr_c_visit}",$pl,$r["protocol"]==0);
    $types=wrr_redirtypes();
    $how=isset($types[$r["redirtype"]])?$types[$r["redirtype"]]:$types[0];
    if($r["redirtype"]==3){
        $dest="<span class='wrr-code'>http://artica.me</span>";
    }elseif($r["useservice"]){
        $dest="<span class='wrr-dest-svc'>{wrr_appliance_page}</span>";
    }else{
        $dest=$r["url"]!==""?"<span class='wrr-code'>".wrr_h($r["url"])."</span>":"<span class='wrr-err'>{wrr_no_address}</span>";
    }
    $note="";
    if($m["service"] && $r["redirtype"]!=3){
        $note="<div class='wrr-note'><i class='fas fa-circle-exclamation'></i> {wrr_replaced}</div>";
    }
    $sw=$r["enabled"]?"true":"false";
    $swl=$r["enabled"]?"{wrr_on}":"{wrr_off}";
    $toggle=$can?"<button type='button' class='wrr-switch' role='switch' aria-checked='$sw' aria-label=\"".wrr_h(wrr_t("wrr_toggle_x",$r["name"]))."\" OnClick=\"Loadjs('$page?enable-rule-js=$id');\"><span class='wrr-knob'></span></button><span class='wrr-swl'>$swl</span>"
        :"<span class='wrr-swl'>$swl</span>";
    $acts="<button type='button' class='wrr-ibtn' aria-label=\"".wrr_h(wrr_t("wrr_edit"))."\" title=\"".wrr_h(wrr_t("wrr_edit"))."\" OnClick=\"$edit\"><i class='fas fa-pen'></i></button>";
    if($can){
        $acts.="<button type='button' class='wrr-ibtn wrr-ibtn-del' aria-label=\"".wrr_h(wrr_t("wrr_delete"))."\" title=\"".wrr_h(wrr_t("wrr_delete"))."\" OnClick=\"Loadjs('$page?delete-rule-js=$id');\"><i class='fas fa-trash'></i></button>";
    }
    $off=$r["enabled"]?"":" wrr-row-off";
    $badge=$r["enabled"]?"":" <span class='wrr-badge-off'>{wrr_off}</span>";
    return "<div class='wrr-row$off'>"
        ."<div class='wrr-pos'><span class='wrr-num' aria-label=\"".wrr_h(wrr_t("wrr_pos_aria",(string)($i+1),(string)$n))."\">".($i+1)."</span><div class='wrr-moves'>$up$down</div></div>"
        ."<div class='wrr-main'><div class='wrr-name'><a href='javascript:void(0)' OnClick=\"$edit\">".wrr_h($r["name"]!==""?$r["name"]:"#$id")."</a>$badge</div>"
        ."<div class='wrr-conds'>$policy$cat$proto</div>"
        ."<div class='wrr-destline'><i class='fas fa-arrow-right'></i> $dest <span class='wrr-how'>$how</span></div>$note</div>"
        ."<div class='wrr-side'><div class='wrr-toggle'>$toggle</div><div class='wrr-acts'>$acts</div></div>"
        ."</div>";
}

function wrr_fallback($m):string{
    $url=$m["service"]?$m["service_url"]:"http://articatech.net/block.html";
    return "<div class='wrr-fallback'><span class='wrr-fb-ico'><i class='fas fa-shield-halved'></i></span><div class='wrr-main'>"
        ."<div class='wrr-name'>{wrr_fb_title} <span class='wrr-badge-off'>{wrr_fb_badge}</span></div>"
        ."<div class='wrr-destline'>".wrr_t("wrr_fb_text","<span class='wrr-code'>".wrr_h($url)."</span>")." <span class='wrr-how'>{wrr_h_302}</span></div>"
        ."<div class='wrr-sub'>{wrr_fb_help}</div></div></div>";
}

function wrr_empty():string{
    $page=CurrentPageName();
    $btn=wrr_can_write()?"<button type='button' class='wrr-btn wrr-btn-primary' OnClick=\"Loadjs('$page?ruleid-js=0');\"><i class='fas fa-plus'></i> {new_rule}</button>":"";
    return "<div class='wrr-empty'><div class='wrr-empty-ico'><i class='fas fa-signs-post'></i></div><h4>{wrr_empty_title}</h4><p>{wrr_empty_text}</p>$btn</div>";
}

function wrr_rules_card($m):string{
    $page=CurrentPageName();
    $n=count($m["rules"]);
    $rows=array();
    foreach($m["rules"] as $i=>$r){$rows[]=wrr_rule_row($r,$i,$n,$m);}
    $btn=wrr_can_write()?"<button type='button' class='wrr-btn wrr-btn-primary' OnClick=\"Loadjs('$page?ruleid-js=0');\"><i class='fas fa-plus'></i> {new_rule}</button>":"";
    $count=$n>0?"<span class='wrr-count'>".wrr_t("wrr_count",number_format($n),number_format($m["on"]))."</span>":"";
    return "<div class='wrr-card'><div class='wrr-card-h'><h3>{wrr_your_rules} $count</h3>$btn</div>"
        .($n==0?wrr_empty():implode("",$rows)).wrr_fallback($m)."</div>";
}

// ── Ecritures ───────────────────────────────────────────────────────────────

function wrr_rule_name($ID):string{
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $r=$q->QUERY_SQL_PARAMS("SELECT rulename FROM ufdb_errors WHERE ID=?",array(intval($ID)));
    return (is_array($r) && isset($r[0]["rulename"]))?(string)$r[0]["rulename"]:"";
}

// Recompile les regles (articarest) ; "" = succes, sinon le message d'erreur.
function wrr_compile():string{
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules"));
    if(!is_object($json)){return "articarest: no answer";}
    if(property_exists($json,"Status") && !$json->Status){return property_exists($json,"Error")?(string)$json->Error:"error";}
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();
    return "";
}

function rule_delete_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["delete-rule-js"]);
    $name=wrr_rule_name($ID);
    $text="<strong style='font-size:16px'>".wrr_h(wrr_t("wrr_delete_title",$name!==""?$name:"#$ID"))."</strong><br><br>".wrr_t("wrr_delete_text");
    $tpl->js_confirm_delete($text,"rule-delete-confirm",$ID,"LoadAjax('ufdbpages-rules','$page?table=yes');",true);
    return true;
}

function rule_delete_confirm():bool{
    $tpl=new template_admin();
    if(!wrr_can_write()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    $ID=intval($_POST["rule-delete-confirm"]);
    $title=wrr_rule_name($ID);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL_PARAMS("DELETE FROM ufdb_errors WHERE ID=?",array($ID));
    if(!$q->ok){echo $q->mysql_error;return false;}
    $err=wrr_compile();
    if($err!==""){echo $err;return false;}
    admin_tracks("Remove Web error page rule $ID - $title");
    return true;
}

function rule_enable():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    if(!wrr_can_write()){echo $tpl->js_error($tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}"));return false;}
    $ID=intval($_GET["enable-rule-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $r=$q->QUERY_SQL_PARAMS("SELECT enabled,rulename FROM ufdb_errors WHERE ID=?",array($ID));
    if(!is_array($r) || !isset($r[0])){echo $tpl->js_error("rule $ID not found");return false;}
    $enabled=intval($r[0]["enabled"])==0?1:0;
    $q->QUERY_SQL_PARAMS("UPDATE ufdb_errors SET enabled=? WHERE ID=?",array($enabled,$ID));
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
    $err=wrr_compile();
    if($err!==""){echo $tpl->js_error($err);return false;}
    echo "LoadAjaxSilent('ufdbpages-rules','$page?table=yes');";
    return admin_tracks("Change web page error rule {$r[0]["rulename"]} to $enabled");
}

// Nouvel ordre apres deplacement : pure (harnais). $ids dans l'ordre courant.
function wrr_reorder(array $ids,$ID,$up):array{
    $pos=array_search($ID,$ids,true);
    if($pos===false){return $ids;}
    $to=$up?$pos-1:$pos+1;
    if($to<0 || $to>=count($ids)){return $ids;}
    $tmp=$ids[$to];$ids[$to]=$ids[$pos];$ids[$pos]=$tmp;
    return $ids;
}

// Avant : lisait un champ xORDER inexistant (toujours 0) et renumerotait avec
// une requete invalide (WHERE ORDER BY) -- l'ordre ne changeait jamais.
function rule_move(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    if(!wrr_can_write()){echo $tpl->js_error($tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}"));return false;}
    $ID=intval($_GET["rule-move"]);
    $up=intval($_GET["rule-dir"] ?? 0)==1;
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $results=$q->QUERY_SQL("SELECT ID FROM ufdb_errors ORDER BY zorder,ID");
    if(!$q->ok || !is_array($results)){echo $tpl->js_error($q->mysql_error);return false;}
    $ids=array();
    foreach($results as $l){$ids[]=intval($l["ID"]);}
    $new=wrr_reorder($ids,$ID,$up);
    foreach($new as $i=>$rid){
        $q->QUERY_SQL_PARAMS("UPDATE ufdb_errors SET zorder=? WHERE ID=?",array($i+1,$rid));
        if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
    }
    $err=wrr_compile();
    if($err!==""){echo $tpl->js_error($err);return false;}
    echo "LoadAjaxSilent('ufdbpages-rules','$page?table=yes');";
    return admin_tracks("Move Web error page rule ".wrr_rule_name($ID)." ".($up?"up":"down"));
}

// Validation des champs (pure, harnais) : [valeurs, erreur].
function wrr_validate($post,$nginx):array{
    $v=array();
    $v["rulename"]=trim((string)($post["rulename"] ?? ""));
    if($v["rulename"]===""){return array(array(),"{wrr_err_name}");}
    $v["enabled"]=intval($post["enabled"] ?? 0)==1?1:0;
    $p=intval($post["zprotocol"] ?? 0);
    $v["protocol"]=in_array($p,array(0,1,2,3),true)?$p:0;
    $v["webruleid"]=max(0,intval($post["webruleid"] ?? 0));
    $v["category"]=max(0,intval($post["category"] ?? 0));
    $t=intval($post["redirtype"] ?? 0);
    $v["redirtype"]=in_array($t,array(0,1,2,3),true)?$t:0;
    $v["redirwebserv"]=(!$nginx && intval($post["redirwebserv"] ?? 0)==1)?1:0;
    $v["url"]=trim((string)($post["url"] ?? ""));
    if($v["redirwebserv"]==0 && $v["redirtype"]!=3){
        if(!preg_match('#^https?://[^\s<>"\']+$#i',$v["url"])){return array(array(),"{wrr_err_url}");}
    }
    return array($v,"");
}

function rule_save(){
    $tpl=new template_admin();
    if(!wrr_can_write()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST();
    $ID=intval($_POST["rule-save"]);
    $nginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"))==1;
    list($v,$err)=wrr_validate($_POST,$nginx);
    if($err!==""){echo $tpl->_ENGINE_parse_body($err);return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $cols=array_keys($v);
    if($ID==0){
        $r=$q->QUERY_SQL("SELECT MAX(zorder) AS m FROM ufdb_errors");
        $v["zorder"]=(is_array($r) && isset($r[0]["m"]))?intval($r[0]["m"])+1:1;
        $cols=array_keys($v);
        $sql="INSERT INTO ufdb_errors (`".implode("`,`",$cols)."`) VALUES (".implode(",",array_fill(0,count($cols),"?")).")";
        $params=array_values($v);
    }else{
        $set=array();
        foreach($cols as $c){$set[]="`$c`=?";}
        $sql="UPDATE ufdb_errors SET ".implode(",",$set)." WHERE ID=?";
        $params=array_values($v);
        $params[]=$ID;
    }
    $q->QUERY_SQL_PARAMS($sql,$params);
    if(!$q->ok){echo $q->mysql_error;return false;}
    $err=wrr_compile();
    if($err!==""){echo $err;return false;}
    admin_tracks("Add/edit web error page rule {$v["rulename"]}");
    return true;
}

// ── Style ───────────────────────────────────────────────────────────────────

function wrr_style():string{
    if(isset($GLOBALS["WRR_STYLE"])){return "";}
    $GLOBALS["WRR_STYLE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.wrr{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px;margin-top:8px}
.wrr>div:empty{display:none}
.wrr-hero{position:relative;display:flex;gap:18px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:22px 24px 20px 28px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.wrr-hero:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:$accent}
.wrr-hero-ico{flex:0 0 48px;height:48px;border-radius:12px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:20px}
.wrr-hero-body{flex:1;min-width:0}
.wrr-hero h3{margin:0 0 6px 0;font-size:18px;font-weight:600}
.wrr-hero p{margin:0;font-size:14px;line-height:1.6;color:#475569;max-width:900px}
.wrr-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.wrr-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid $line;background:$soft;border-radius:999px;padding:3px 11px;font-size:12px;color:#334155}
.wrr-chip i,.wrr-chip svg{color:$accent}
.wrr-info{display:flex;align-items:center;gap:14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 18px;color:#1e3a8a}
.wrr-info>i,.wrr-info>svg{font-size:18px;color:#2563eb}
.wrr-info-txt{flex:1;min-width:0;line-height:1.55;overflow-wrap:anywhere}
.wrr-link{color:#1d4ed8;font-weight:600;white-space:nowrap;text-decoration:none}
.wrr-link:hover{text-decoration:underline}
.wrr-code{display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:1px 7px;font-family:monospace;font-size:13px;color:#0f172a;overflow-wrap:anywhere}
.wrr-info .wrr-code{background:#dbeafe;border-color:#bfdbfe;color:#1e3a8a}
.wrr-alert{display:flex;gap:12px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;color:#b91c1c}
.wrr-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.wrr-card-h{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 22px;border-bottom:1px solid #e2e8f0}
.wrr-card-h h3{margin:0;font-size:17px;font-weight:600;display:flex;align-items:center;gap:10px}
.wrr-count{border:1px solid #e2e8f0;background:#f8fafc;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600;color:#334155;white-space:nowrap}
.wrr-row{display:flex;align-items:center;gap:18px;padding:18px 22px;border-bottom:1px solid #f1f5f9}
.wrr-row-off .wrr-main,.wrr-row-off .wrr-pos{opacity:.6}
.wrr-pos{display:flex;align-items:center;gap:8px;flex:0 0 auto}
.wrr-num{width:32px;height:32px;border-radius:50%;border:1px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:center;font-weight:600;color:#475569}
.wrr-moves{display:flex;flex-direction:column}
.wrr-main{flex:1;min-width:0}
.wrr-name{font-size:16px;font-weight:600;margin-bottom:8px;overflow-wrap:anywhere}
.wrr-name a{color:#0f172a;text-decoration:none}
.wrr-name a:hover{color:$dark}
.wrr-badge-off{display:inline-block;margin-left:8px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:1px 8px;font-size:12px;font-weight:600;color:#64748b;vertical-align:middle}
.wrr-conds{display:flex;flex-wrap:wrap;gap:6px 16px;align-items:center}
.wrr-cond{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#64748b}
.wrr-tag{display:inline-flex;align-items:center;gap:6px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;border-radius:6px;padding:2px 9px;font-size:13px;font-weight:500}
.wrr-tag-muted{background:#f1f5f9;border-color:#e2e8f0;color:#334155}
.wrr-destline{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:10px;font-size:13px;color:#475569}
.wrr-destline>i,.wrr-destline>svg{color:#2563eb}
.wrr-dest-svc{display:inline-block;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:6px;padding:1px 8px;font-weight:600}
.wrr-how{color:#64748b;font-size:12px}
.wrr-note{margin-top:6px;font-size:12px;color:#b45309}
.wrr-err{color:#b91c1c}
.wrr-side{display:flex;align-items:center;gap:18px;flex:0 0 auto}
.wrr-toggle{display:flex;align-items:center;gap:8px;min-width:80px}
.wrr-switch{position:relative;width:46px;height:26px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0;transition:background .15s}
.wrr-switch[aria-checked=true]{background:$accent}
.wrr-knob{position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.wrr-switch[aria-checked=true] .wrr-knob{left:23px}
.wrr-switch:focus-visible,.wrr-ibtn:focus-visible,.wrr-btn:focus-visible{outline:2px solid $accent;outline-offset:2px}
.wrr-swl{font-size:13px;font-weight:600;color:#475569}
.wrr-acts{display:flex;gap:4px;border-left:1px solid #e2e8f0;padding-left:14px}
.wrr-ibtn{border:0;background:transparent;color:#64748b;width:32px;height:30px;border-radius:8px;cursor:pointer}
.wrr-ibtn:hover:not([disabled]){background:#f1f5f9;color:#0f172a}
.wrr-ibtn[disabled]{color:#cbd5e1;cursor:default}
.wrr-ibtn-del:hover:not([disabled]){background:#fef2f2;color:#b91c1c}
.wrr-btn{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:8px 16px;font-size:14px;font-weight:500;color:#334155;cursor:pointer;white-space:nowrap}
.wrr-btn-primary{background:$accent;border-color:$accent;color:#fff;font-weight:600}
.wrr-btn-primary:hover{background:$dark;border-color:$dark}
.wrr-fallback{display:flex;align-items:flex-start;gap:16px;margin:18px 22px 22px 22px;padding:18px 20px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc}
.wrr-fb-ico{flex:0 0 44px;height:44px;border-radius:50%;border:2px dashed #cbd5e1;color:#64748b;display:flex;align-items:center;justify-content:center}
.wrr-sub{margin-top:6px;font-size:12px;color:#94a3b8}
.wrr-empty{display:flex;flex-direction:column;align-items:center;text-align:center;padding:36px 24px 10px 24px;gap:10px}
.wrr-empty-ico{width:68px;height:68px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:26px}
.wrr-empty h4{margin:4px 0 0 0;font-size:17px;font-weight:600}
.wrr-empty p{margin:0;color:#475569;max-width:560px;line-height:1.6}
@media (max-width:1200px){.wrr-row{flex-wrap:wrap}.wrr-side{margin-left:auto}}
@media (prefers-reduced-motion:reduce){.wrr-switch,.wrr-knob{transition:none}}
</style>";
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}