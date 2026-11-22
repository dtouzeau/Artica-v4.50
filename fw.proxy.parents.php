<?php
// stamp 01/11/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_main_save();exit;}
if(isset($_GET["proxies"])){proxies();exit;}
if(isset($_GET["proxies-list"])){proxies_list();exit;}
if(isset($_GET["fiche-proxy-js"])){proxy_fiche_js();exit;}
if(isset($_GET["fiche-proxy"])){proxy_fiche();exit;}
if(isset($_GET["delete-proxy-js"])){proxy_delete_js();exit;}
if(isset($_POST["proxy-aclid"])){proxy_fiche_save();exit;}
if(isset($_POST["delete-proxy"])){proxy_delete();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
page();

function page():bool{
	$page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{parent_proxies}","fa fa-sitemap",
        "{parent_proxies_explain}","$page?table=yes","proxy-parents-rules",
        "progress-firehol-restart",null,"table-loader-proxy-parents");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{parent_proxies}",$html);
        echo $tpl->build_firewall();
        return true;
    }


	echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function enabled_js():bool{
	$aclid=intval($_GET["enabled-js"]);

    $row = acls_api_get("/proxy/parents/acls/$aclid");
    if(!is_array($row)){
        echo "alert('rule $aclid not found');";
        return false;
    }
    $enabled = intval($row["enabled"] ?? 1) == 0 ? 1 : 0;
    [$ok, $err] = acls_api_send("PUT", "/proxy/parents/acls/$aclid", ["enabled" => $enabled]);
    if(!$ok){ echo "alert('".addslashes($err)."');"; return false; }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile");
    return admin_tracks("Proxy Parent, activate rule $aclid");
}

function rule_delete_js():bool{
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
    return true;
}

function rule_delete():bool{
	$aclid = intval($_POST["delete-rule"]);

    $row = acls_api_get("/proxy/parents/acls/$aclid");
    $rulename = is_array($row) ? ($row["ruleName"] ?? $aclid) : $aclid;

    [$ok, $err] = acls_api_send("DELETE", "/proxy/parents/acls/$aclid");
    if(!$ok){ echo $err; return false; }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile");
    return admin_tracks("DELETE PROXY Parent rule $rulename");

}


function proxy_fiche_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$proxname=urlencode($_GET["proxname"]);
	$title=$proxname;
	// Pentest 2026-11-04 : aclid etait recopie sans encodage dans la page (XSS).
	$aclid=intval($_GET["aclid"]);
	if($proxname==null){$title=$tpl->javascript_parse_text("{new_proxy}");}
	return $tpl->js_dialog1($title,"$page?fiche-proxy=$proxname&aclid=$aclid");
}

function proxy_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// Pentest 2026-11-04 : delete-proxy-js/aclid etait recopie sans encodage dans la page (XSS).
	$proxname=req_js($_GET["delete-proxy-js"]);
	$aclid=intval($_GET["aclid"]);
    $md=$_GET["md"];
	header("content-type: application/x-javascript");

	$delete_personal_cat_ask=$tpl->javascript_parse_text("{delete} $proxname ?");
	$t=time();
	$html="

var xDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#$md').remove();
	if(document.getElementById('table-loader-proxy-parents') ){
	    LoadAjax('table-loader-proxy-parents','fw.proxy.parents.php?table=yes');
	}
}

function Action$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-proxy','$proxname');
	XHR.appendData('aclid','$aclid');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}

Action$t();";
echo $html;
}

function rule_id_js():bool{
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $users      = new usersMenus();

    if(!$users->AsSquidAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS2}");
        return false;
    }

	$id=intval($_GET["ruleid-js"]);
	$title="{new_rule}";

	if($id>0){
        $row = acls_api_get("/proxy/parents/acls/$id");
        if(is_array($row)){
            $rulename = $row["ruleName"] ?? "";
            $title="{rule}: $id $rulename";
        }
	}
	$title=$tpl->javascript_parse_text($title);
	$tpl->js_dialog($title,"$page?rule-popup=$id");
	return true;
}

function proxy_delete():bool{
	$aclid=intval($_POST["aclid"]);

    $row = acls_api_get("/proxy/parents/acls/$aclid");
    if(!is_array($row)){ echo "rule $aclid not found"; return false; }

    $MAIN = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($row["proxies"] ?? "");
    if(!is_array($MAIN)){ $MAIN = []; }
    unset($MAIN[$_POST["delete-proxy"]]);
    $MAIN_FINAL = base64_encode(serialize($MAIN));

    [$ok, $err] = acls_api_send("PUT", "/proxy/parents/acls/$aclid", ["proxies" => $MAIN_FINAL]);
    if(!$ok){ echo $err; return false; }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile");
    return admin_tracks("Delete proxy parent rule #$aclid");

}
function proxy_fiche(){
	$proxname=$_GET["fiche-proxy"];
	$title=$proxname;
	$btname="{apply}";
	$aclid=intval($_GET["aclid"]);
	$BootstrapDialog=null;
	$page=CurrentPageName();
	$tpl=new template_admin();

    $row = acls_api_get("/proxy/parents/acls/$aclid");
    if(!is_array($row)){
        $row = ["proxies" => base64_encode(serialize([])), "useLb" => 0];
    }

    $MAIN = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($row["proxies"] ?? "");
    if(!is_array($MAIN)){ $MAIN = []; }
    $useLB = intval($row["useLb"] ?? 0);

	if($proxname==null){
		$title="{new_proxy}";
		$btname="{add}";
		$BootstrapDialog="dialogInstance1.close();";
	}
	$ARRAY=$MAIN[$proxname];
	$ST=explode(":",$proxname);

	if(!is_numeric($ST[1])){$ST[1]="3128";}
	$tpl->field_hidden("proxy-aclid", $aclid);
	$form[]=$tpl->field_text("hostname","{hostname}",$ST[0],true);
	$form[]=$tpl->field_numeric("port","{listen_port}",$ST[1]);
    $form[]=$tpl->field_hidden('verification_token',$ST[0].':'.$ST[1]);


        $options["tls"] = "tls";
        $options["proxy-only"] = "proxy-only";
        $options["Weight"] = "Weight=nn";
        $options["ttl"] = "ttl=nn";
        $options["no-query"] = "no-query";
        $options["default"] = "default";
        $options["round-robin"] = "round-robin";
        $options["multicast-responder"] = "multicast-responder";
        $options["closest-only"] = "closest-only";
        $options["no-digest"] = "no-digest";
        $options["no-tproxy"] = "no-tproxy";
        $options["no-netdb-exchange"] = "no-netdb-exchange";
        $options["no-delay"] = "no-delay";
        $options["userpassword"] = "login=user:password";
        $options["login_PASSTHRU"] = "login=PASSTHRU";
        $options["login_PASS"] = "login=PASS";
        $options["connect-timeout"] = "connect-timeout=nn";

    $explain["tls"] ="{UseSSL}";
	$explain["proxy-only"] ="{parent_options_proxy_only}";
	$explain["Weight"] ="{parent_options_proxy_weight}";
	$explain["ttl"] ="{parent_options_proxy_ttl}";
	$explain["no-query"] ="{parent_options_proxy_no_query}";
	$explain["default"] ="{parent_options_proxy_default}";
	$explain["round-robin"] ="{parent_options_proxy_round_robin}";
	$explain["multicast-responder"] ="{parent_options_proxy_multicast_responder}";
	$explain["closest-only"] ="{parent_options_proxy_closest_only}";
	//$explain["no-digest"] ="{parent_options_proxy_no_digest}";
	$explain["no-netdb-exchange"] ="{parent_options_proxy_no_netdb_exchange}";
	$explain["no-delay"] ="{parent_options_proxy_no_delay}";
	$explain["userpassword"] ="{parent_options_proxy_login}";
	$explain["login_PASSTHRU"] ="{parent_options_login_passthru}";
	$explain["login_PASS"] ="{parent_options_login_pass}";
	$explain["connect-timeout=nn"] ="{parent_options_proxy_connect_timeout}";
	$explain["digest-url"] ="{parent_options_proxy_digest_url}";
	$explain["no-tproxy"] ="{parent_options_no_tproxy}";
	$explain["connect-timeout"] ="{parent_options_connect_timeout}";

    // Pentest 2026-11-04 : fiche-proxy etait recopie sans encodage dans la page (XSS).
    $explain_form="{proxy}:".req_html($proxname);

    if($proxname==null){$explain_form=null;}

	foreach ($options as $key=>$val){
		if($key=="connect-timeout"){
            if(!isset($ARRAY["OPT_".$key])){
                $ARRAY["OPT_".$key]["VALUE"]=5;
                $ARRAY["OPT_".$key]["ENABLED"]=1;

            }
        }
        if(!isset($ARRAY["OPT_".$key])){$ARRAY["OPT_".$key]["VALUE"]=null;}
        $default_value=$ARRAY["OPT_".$key]["VALUE"];
        if(!isset($ARRAY["OPT_".$key]["ENABLED"])){
            $ARRAY["OPT_".$key]["ENABLED"]=0;
        }
		$default_enabled=intval($ARRAY["OPT_".$key]["ENABLED"]);
        if($useLB==1){
            $tpl->field_hidden("OPT_".$key,$default_enabled);
            continue;
        }
        $field_checkbox=$tpl->field_checkbox("OPT_".$key,$key,$default_enabled,false,$explain[$key]);

		if(preg_match("#=nn#", $val)){
			$form[]=$tpl->field_checkbox("OPT_".$key,$key,$default_enabled,"VAL_$key",$explain[$key]);
			$form[]=$tpl->field_numeric("VAL_$key","{value} $key",$default_value);
			continue;
		}
		if(preg_match("#user:password#", $val)){
            $zExplain="";
            if(isset($explain[$key])){
                $zExplain=$explain[$key];
            }
			$form[]=$tpl->field_checkbox("OPT_".$key,$key,$default_enabled,"USR_$key,PASS_$key",$zExplain);
			$default_value1=explode(":",$default_value);
			$form[]=$tpl->field_text("USR_$key","{username}",$default_value1[0]);
			$form[]=$tpl->field_password("PASS_$key","{password}",$default_value1[1]);
			continue;
		}


		$form[]=$field_checkbox;
	}

	echo $tpl->form_outside($title,@implode("\n", $form),
        $explain_form,$btname,
			"LoadAjax('proxies-list-$aclid','$page?proxies=$aclid');LoadAjax('table-loader-proxy-parents','$page?table=yes');$BootstrapDialog");

}

function proxy_fiche_save():bool{

	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return false;}

	$aclid=intval($_POST["proxy-aclid"]);

    $row = acls_api_get("/proxy/parents/acls/$aclid");
    if(!is_array($row)){ echo "rule $aclid not found"; return false; }

    $MAIN = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($row["proxies"] ?? "");
    if(!is_array($MAIN)){ $MAIN = []; }

	$hostname=url_decode_special_tool($_POST["hostname"]).":".$_POST["port"];
	$hostname=strtolower($hostname);
    $verification_token=url_decode_special_tool($_POST['verification_token']);
	unset($MAIN[$hostname]);
	unset($_POST["proxy-aclid"]);
	unset($_POST["hostname"]);
	unset($_POST["port"]);

	$MAIN[$hostname]["SAVED"]=time();

    foreach ($_POST as $key=>$val){
		$val=url_decode_special_tool($val);
		if(!preg_match("#^OPT_(.+)#", $key,$re)){continue;}
		if(intval($val)==0){continue;}
		$key=$re[1];
		$MAIN[$hostname]["OPT_".$key]["ENABLED"]=1;
		if($_POST["VAL_$key"]<>null){$MAIN[$hostname]["OPT_".$key]["VALUE"]=url_decode_special_tool($_POST["VAL_$key"]); }
		if($_POST["USR_$key"]<>null){$MAIN[$hostname]["OPT_".$key]["VALUE"]=url_decode_special_tool($_POST["USR_$key"]).":".url_decode_special_tool($_POST["PASS_$key"]);}
	}

	if($verification_token!==$hostname){
	    UNSET($MAIN[$verification_token]);
    }

	$MAIN_FINAL=base64_encode(serialize($MAIN));

    [$ok, $err] = acls_api_send("PUT", "/proxy/parents/acls/$aclid", ["proxies" => $MAIN_FINAL]);
    if(!$ok){ echo $err; return false; }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile");
    return admin_tracks_post("Save parent proxy $hostname");


}

function rule_settings(){
	$aclid=intval($_GET["rule-settings"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ligne=["enabled"=>1,"zorder"=>1,"ruleName"=>"","neverDirect"=>0,"alwaysDirect"=>0,"proxyProxy"=>0,"useLb"=>0];
	$btname="{add}";
	$title="{parent_proxies_rule}: {new_rule}";
	$BootstrapDialog="BootstrapDialog1.close();LoadAjax('table-loader-proxy-parents','$page?table=yes');";

    // Check if any OTHER rule already uses LB
    $allRules = acls_api_get("/proxy/parents/acls") ?? [];
    $VIEWLB = true;
    foreach($allRules as $r){
        if(intval($r["enabled"] ?? 0)==1 && intval($r["useLb"] ?? 0)==1){
            $oldid = intval($r["id"] ?? 0);
            if($oldid > 0 && $oldid != $aclid){ $VIEWLB = false; break; }
        }
    }

	if($aclid>0){
        $row = acls_api_get("/proxy/parents/acls/$aclid");
        if(is_array($row)){
            $ligne = [
                "enabled"      => intval($row["enabled"]      ?? 1),
                "zorder"       => intval($row["xOrder"]       ?? 1),
                "rulename"     => $row["ruleName"]             ?? "",
                "ruleName"     => $row["ruleName"]             ?? "",
                "neverDirect"  => intval($row["neverDirect"]  ?? 0),
                "alwaysDirect" => intval($row["alwaysDirect"] ?? 0),
                "proxyproxy"   => intval($row["proxyProxy"]   ?? 0),
                "use_lb"       => intval($row["useLb"]        ?? 0),
            ];
        }
		$btname="{apply}";
		$title=$ligne["ruleName"] ?? $ligne["rulename"] ?? "";
		$BootstrapDialog=null;
        $add="Loadjs('$page?fiche-proxy-js=yes&proxname=&aclid=$aclid')";
        $form[]=$tpl->form_add_button("{new_proxy}",$add);
	}


	$tpl->field_hidden("rule-save", $aclid);
	$form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"] ?? $ligne["ruleName"] ?? "",true);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_checkbox("always_direct","{do_not_use_parent_proxy}",$ligne["alwaysDirect"] ?? $ligne["always_direct"] ?? 0,false,"{do_not_use_parent_proxy}");
	$form[]=$tpl->field_checkbox("never_direct","{never_direct}",$ligne["neverDirect"] ?? $ligne["never_direct"] ?? 0,false,"{never_direct_explain}");
    $form[]=$tpl->field_checkbox("proxyproxy","{AuthParentPort}",$ligne["proxyproxy"] ?? $ligne["proxyProxy"] ?? 0,false,"{proxyproxy_explain}");

    if($VIEWLB) {
        $form[] = $tpl->field_checkbox("use_lb", "{use_lb_system}", $ligne["use_lb"] ?? $ligne["useLb"] ?? 0, false, "{use_lb_system_explain}");
    }else{
        $tpl->field_hidden("use_lb",0);
    }


	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"] ?? $ligne["xOrder"] ?? 1);
	echo $tpl->form_outside($title,@implode("\n", $form),"",$btname,"LoadAjax('table-loader-proxy-parents','$page?table=yes');$BootstrapDialog");
}

function rule_main_save():bool{
	$tpl = new template_admin();

    if($_POST["always_direct"]==1){$_POST["never_direct"]=0;}
	$aclid=intval($_POST["rule-save"]);
	$rulename=url_decode_special_tool($_POST["rulename"]);

	if($aclid==0){
        // CREATE
        $body = [
            "ruleName"     => $rulename,
            "neverDirect"  => intval($_POST["never_direct"]  ?? 0),
            "alwaysDirect" => intval($_POST["always_direct"] ?? 0),
            "proxyProxy"   => intval($_POST["proxyproxy"]    ?? 0),
            "useLb"        => intval($_POST["use_lb"]        ?? 0),
        ];
        [$ok, $data] = acls_api_send("POST", "/proxy/parents/acls", $body);
        if(!$ok){ echo $data; return false; }
	}else{
        // UPDATE
        $body = [
            "ruleName"     => $rulename,
            "enabled"      => intval($_POST["enabled"]       ?? 1),
            "neverDirect"  => intval($_POST["never_direct"]  ?? 0),
            "alwaysDirect" => intval($_POST["always_direct"] ?? 0),
            "proxyProxy"   => intval($_POST["proxyproxy"]    ?? 0),
            "useLb"        => intval($_POST["use_lb"]        ?? 0),
        ];
        [$ok, $err] = acls_api_send("PUT", "/proxy/parents/acls/$aclid", $body);
        if(!$ok){ echo $err; return false; }
	}

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile");
    return admin_tracks("INSERT/UPDATE parent rule $rulename never_direct={$_POST["never_direct"]} always_direct={$_POST["always_direct"]}");

}

function rule_tab(){

	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["rule-popup"]);
	$ppage="fw.proxy.objects.php";
	$rulename="{new_rule}";
	$always_direct=0;

	if($aclid>0){
        $row = acls_api_get("/proxy/parents/acls/$aclid");
        if(is_array($row)){
            $rulename      = $row["ruleName"]     ?? "{new_rule}";
            $always_direct = intval($row["alwaysDirect"] ?? 0);
        }
    }

	$array[$rulename]="$page?rule-settings=$aclid";
	if($aclid>0){

		if($always_direct==0) {
            $array["{parent_proxies}"]      = "$page?proxies=$aclid";
            $array["{objects} {use_proxy}"] = "$ppage?aclid=$aclid&main-table=parents_sqacllinks&fast-acls=1";
            $array["{objects} {exclude}"]   = "$ppage?aclid=$aclid&main-table=parents_white_sqacllinks&fast-acls=1";
        }else{
            $array["{objects} {exclude}"]   = "$ppage?aclid=$aclid&main-table=parents_sqacllinks&fast-acls=1";
        }

	}
	echo $tpl->tabs_default($array);


}
function proxies(){
	$page=CurrentPageName();
	$aclid=intval($_GET["proxies"]);
	echo "<div id='proxies-list-$aclid' style='margin-top:20px'></div>
	<script>LoadAjax('proxies-list-$aclid','$page?proxies-list=$aclid');</script>
	";

}
function proxies_list(){
	$aclid=intval($_GET["proxies-list"]);
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	$t=time();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");

    $row = acls_api_get("/proxy/parents/acls/$aclid");
    if(!is_array($row)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("rule $aclid not found"));
        return;
    }
    $proxies = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($row["proxies"] ?? "");
    if(!is_array($proxies)){ $proxies = []; }

	$add="Loadjs('$page?fiche-proxy-js=yes&proxname=&aclid=$aclid')";



    $topbuttons[] = array($add, ico_plus, "{new_proxy}");


	$html[]=$tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>$hostname</th>";
    $html[]="<th class='center'>{actions}</th>";
    $html[]="</tr></thead><tbody id='pl-rows'>";

    $c=0;
	foreach ($proxies as $proxyhost => $array){
		// $hostname servait A LA FOIS de libelle d'en-tete traduit et de
		// variable de boucle: la seconde ecrasait le premier.
		$c++;
		$rulename=urlencode($proxyhost);
		$md=md5($proxyhost.serialize($array));
		$js="Loadjs('$page?fiche-proxy-js=yes&proxname=$rulename&aclid=$aclid')";
		$delete=$tpl->icon_delete("Loadjs('$page?delete-proxy-js=$rulename&aclid=$aclid&md=$md')");
		if(!$users->AsSquidAdministrator){$delete=$tpl->icon_delete("blur()");}
		$html[]=parents_proxy_row($md,$proxyhost,$js,$delete);

	}
	if($c==0){
		$html[]="<tr><td colspan='2' class='awc-empty'>{no_data}</td></tr>";
	}

	// L'ancienne version placait le pied A L'INTERIEUR du corps du tableau.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c</span>";
	$html[]="<span class='awc-pg' id='pl-pager'></span>";
	$html[]="</div></div></div>";

	// Feuille de style et pager concatenes APRES l'analyse: la regex #{(.+?)}#
	// de _ENGINE_parse_body prend chaque corps d'accolade CSS ou JavaScript
	// pour un {token}.
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n",$html))
	    .awc_pager_js(50,"pl-rows","pl-pager");
}

function rule_move_js():bool{

    $users = new usersMenus();
    if(!$users->AsSquidAdministrator){
        $tpl = new template_admin();
        $tpl->js_error("{ERROR_NO_PRIVS2}");
        return false;
    }

    header("content-type: application/x-javascript");
	$tpl   = new template_admin();
    $dir   = $_GET["dir"];
	$aclid = intval($_GET["aclid"]);

    // API: dir=1 means move up (decrease zorder), dir=0 means move down
    $apiDir = ($dir == "up") ? 1 : 0;

    [$ok, $err] = acls_api_send("PUT", "/proxy/parents/acls/$aclid/move", ["dir" => $apiDir]);
    if(!$ok){
        $msg = $tpl->javascript_parse_text($err);
        echo "alert('$msg');";
        return false;
    }

    echo "LoadAjax('table-loader-proxy-parents','fw.proxy.parents.php?table=yes');";
	return true;

}

function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$add="Loadjs('$page?ruleid-js=0',true);";

    $jsrestart=$tpl->framework_buildjs("/proxy/parents/compile",
        "squid.access.center.progress","squid.access.center.progress.log",
        "progress-firehol-restart","LoadAjax('table-loader-proxy-parents','$page?table=yes');");


    $topbuttons[] = array($add, ico_plus, "{new_rule}");
    $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");

    // Six colonnes dont deux sans libelle et deux d'une icone chacune. Le nom
    // de la regle et son explication tiennent dans la colonne d'identite;
    // edition, deplacement et suppression dans une colonne d'actions.
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>$rulename</th>";
    $html[]="<th class='center'>{status}</th>";
    $html[]="<th class='center'>{enabled}</th>";
    $html[]="<th class='center'>{actions}</th>";
    $html[]="</tr></thead><tbody id='pr-rows'>";
	// $GLOBALS["jsAfterEnc"] etait construit ici a partir de $_GET["eth"] et
	// n'etait relu NULLE PART -- ni dans ce fichier, ni dans les classes.
	$isRights=isRights();

    $results = acls_api_get("/proxy/parents/acls") ?? [];
	$ROWS=0;

    // Rules really written in /etc/squid3/acls_peer.conf, keyed by rule id.
    $peer_status=array();
    $peer_json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/rules/active"));
    if(is_object($peer_json) && !empty($peer_json->Status) && isset($peer_json->Rules) && is_object($peer_json->Rules)){
        foreach($peer_json->Rules as $peer_id=>$peer_on){
            if($peer_on){$peer_status[intval($peer_id)]=true;}
        }
    }

	foreach($results as $ligne) {
		$pproxy=array();
		$ROWS++;
            $use_lb_txt     = null;
			$rulename       = $ligne["ruleName"]     ?? "";
			$rulenameenc    = urlencode($rulename);
			$aclid          = intval($ligne["id"]    ?? 0);
			$enabled        = intval($ligne["enabled"]      ?? 1);
			$never_direct   = intval($ligne["neverDirect"]  ?? 0);
			$always_direct  = intval($ligne["alwaysDirect"] ?? 0);
            $use_lb         = intval($ligne["useLb"]        ?? 0);
			$MAIN           = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["proxies"] ?? "");
            if(!is_array($MAIN)){ $MAIN = []; }
            $up             = $tpl->icon_up("Loadjs('$page?move-js=yes&aclid=$aclid&dir=up')");
            $down           = $tpl->icon_down("Loadjs('$page?move-js=yes&aclid=$aclid&dir=down')");
            $pproxy_text    = null;
            $white          = null;
            $returndef      = true;
			foreach ($MAIN as $num=>$val){$pproxy[]="<strong>$num</strong>";}

            $array_help["TITLE"]="{inactive2}";
            $array_help["content"]="{inactive_acl_why}";
            $array_help["ico"]="fa fa-question";
            $scontent=base64_encode(serialize($array_help));

            // Les <span class=label> Bootstrap sans color:#fff rendaient un
            // texte gris sur fond sombre.
            $status=$tpl->td_href("<span class='awc-badge awc-badge-off'>{inactive2}</span>","{explain}","LoadAjax('artica-modal-dialog','fw.popup.php?array=$scontent')");
            if(isset($peer_status[$aclid])){
                $status="<span class='awc-badge awc-badge-on'>{active2}</span>";
            }

            if($use_lb==1){
                $use_lb_txt=" {using_a_local_lb}";
            }

			if(count($pproxy)>0){$pproxy_text="<br>{then_use_parent_proxies}$use_lb_txt ".@implode(" {or} ", $pproxy);}
			else{
			    if($always_direct==0) {
                    $pproxy_text = "{inactive_rule}";
                    $enabled = 0;
                }
			}

			$check=$tpl->icon_check($enabled,"Loadjs('$page?enabled-js=$aclid')");
			if($always_direct==1){$never_direct=0;}

			if($enabled==1){
				if($never_direct==0){
					$pproxy_text=$pproxy_text." <small>{never_direct_explain_no}</small>";
				}else{
					$pproxy_text=$pproxy_text." <small>{never_direct_label}</small>";
				}
			}


			$js="Loadjs('$page?ruleid-js=$aclid',true);";
			// Ce bouton occupait une colonne entiere sans en-tete.
			$edit=$tpl->icon_edit_field($js,"AsSquidAdministrator");
			$delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js=$aclid&name=$rulenameenc')");
            if($never_direct==0){$white=proxy_objects($aclid,"parents_white_sqacllinks");}
			if($white<>null){$white="<br>{and} <strong>{notfor}</strong>: $white";}
			if($always_direct==1){$returndef=false;}

			$objects=proxy_objects($aclid,"parents_sqacllinks",$returndef);
            $explain=$tpl->_ENGINE_parse_body("{for_objects} $objects$white$pproxy_text");

            if($always_direct==1){
			    if($objects==null){
                    $explain="{inactive_rule}";
                }else{
                    $explain=$tpl->_ENGINE_parse_body("{for_objects} $objects {then} <strong class='text-navy'>{do_not_use_parent_proxy}</strong>");
                }

            }


			if(!$isRights){
				$up=null;
				$down=null;
				$delete=null;
				$edit=null;
			}

			// Le nom de la regle vient de la base et partait BRUT dans le HTML.
			$html[]=parents_rule_row(array(
				"id"      =>"row-parent-$aclid",
				"name"    =>"<a href=\"javascript:blur();\" OnClick=\"$js\">".htmlspecialchars($rulename)."</a>",
				"meta"    =>$explain,
				"status"  =>$status,
				"enabled" =>$check,
				"actions" =>"$edit$up$down$delete"));

	}
	if($ROWS==0){
		$html[]="<tr><td colspan='4' class='awc-empty'>{no_data}</td></tr>";
	}

	// L'ancienne version placait le pied A L'INTERIEUR du corps du tableau, avec
	// un colspan de 5 pour 6 colonnes.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $ROWS</span>";
	$html[]="<span class='awc-pg' id='pr-pager'></span>";
	$html[]="</div></div></div>";

    $TINY_ARRAY["TITLE"]="{parent_proxies} &raquo;&raquo; {rules}";
    $TINY_ARRAY["ICO"]="fa fa-sitemap";
    $TINY_ARRAY["EXPL"]="{parent_proxies_status_explain}";
    $TINY_ARRAY["URL"]="proxy-parents-rules";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n$jstiny</script>"
	    .awc_pager_js(50,"pr-rows","pr-pager");
    return true;
}
function isRights():bool{
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
    return false;
}

function proxy_objects($aclid,$tablelink="parents_sqacllinks",$returndef=true):string{

    $tt         = array();
	$qProxy     = new mysql_squid_builder(true);

    $links = acls_api_get("/proxy/acls/sqlite/links?table=$tablelink&aclid=$aclid");
    if(!is_array($links)){ return $returndef ? "{all}" : ""; }

	foreach($links as $ligne) {
		$gpid=intval($ligne["gpid"] ?? 0);
        if($gpid == 0){ continue; }
        $group = acls_api_get("/proxy/acls/sqlite/group/$gpid");
        if(!is_array($group)){ continue; }
		$js="Loadjs('fw.proxy.objects.php?object-js=yes&gpid=$gpid')";
		$neg_text="{is}";
		if(intval($ligne["negation"] ?? 0)==1){$neg_text="{is_not}";}
		$GroupName=htmlspecialchars($group["GroupName"] ?? "");
        $GroupType=$group["GroupType"] ?? "";
		$tt[]=$neg_text." <a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$GroupName</a> <small>(".$qProxy->acl_GroupType[$GroupType].")</small>";
	}

	if(count($tt)>0){
		return @implode("<br>{and} ", $tt);

	}

    if(!$returndef){return "";}
	return "{all}";



}

/**
 * Un proxy parent d'une regle.
 * L'ancienne version ouvrait un <a> et le refermait par un </span>, et rendait
 * le nom d'hote -- qui vient de la configuration -- BRUT dans le HTML. Le nom
 * etait par ailleurs affiche en font-size:25px, ce qui faisait deborder la
 * ligne des qu'il etait long.
 */
function parents_proxy_row($md,$host,$js,$delete):string{
    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-server'></i></span>";
    $h.="<span class='awc-name'><a href=\"javascript:blur();\" OnClick=\"$js\">"
       .htmlspecialchars($host)."</a></span>";
    $h.="</div></td>";
    $h.="<td><div class='awc-acts'>$delete</div></td>";
    $h.="</tr>";
    return $h;
}

/**
 * Une regle de proxy parent.
 * L'ancienne version refermait le lien du nom par un </span> orphelin.
 * $meta contient l'explication deja construite et traduite par l'appelant
 * (objets, exceptions, proxies retenus): elle ne peut pas etre echappee ici.
 */
function parents_rule_row($r):string{
    $meta=trim((string)$r["meta"]);
    $h ="<tr id='{$r["id"]}'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-sitemap'></i></span>";
    $h.="<span><span class='awc-name'>{$r["name"]}</span>";
    if($meta<>""){$h.="<div class='awc-meta'>$meta</div>";}
    $h.="</span></div></td>";
    $h.="<td class='center awc-nowrap'>{$r["status"]}</td>";
    $h.="<td class='center'>{$r["enabled"]}</td>";
    $h.="<td><div class='awc-acts'>{$r["actions"]}</div></td>";
    $h.="</tr>";
    return $h;
}
