<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["jstiny"])){js_tiny();exit;}
if(isset($_GET["js-example"])){js_example();exit;}
if(isset($_GET["js-example-create"])){js_example_create();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_POST["none"])){exit;}
if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"]);exit;}

page();


function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{bandwidth_rules_mini}","fas fa-filter",
        "{bandwidth_rules_mini_explain}","$page?table=yes","proxy-acls-bandwidth",
    "progress-acls3-restart",false,"table-acls3-rules");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{bandwidth_rules_mini}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function rule_delete_js(){
	header("content-type: application/x-javascript");
	$ID=intval($_GET["rule-delete-js"]);
	// $md lands in a jQuery selector: an md5, nothing else.
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	if(!pbw_admin()){ echo "alert(".json_encode((new template_admin())->javascript_parse_text("{ERROR_NO_PRIVS}")).");"; return; }
	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";
}

function js_example(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_prompt("{example}","{example_create_ask}","fas fa-hat-wizard",$page,null,
        "Loadjs('$page?js-example-create')");
}
function js_example_create(){
    if(!pbw_admin()){ header("content-type: application/x-javascript"); echo "alert(".json_encode((new template_admin())->javascript_parse_text("{ERROR_NO_PRIVS}")).");"; return; }
    $page=CurrentPageName();
    $tpl=new template_admin();

    // Create the bandwidth ACL rule via API (INSERT OR IGNORE equivalent: only create if ID=1 doesn't exist)
    $existing = acls_api_get("/proxy/bandwidth/acls/1");
    if(!is_array($existing) || strlen(trim($existing["ruleName"] ?? ''))==0){
        [$ok, $data] = acls_api_send("POST","/proxy/bandwidth/acls",[
            "ruleName"         => "Limit to 1MBs, heavy sites",
            "limitNetwork"     => 1,
            "networkMaxSize"   => 0,
            "networkBandwidth" => 1024,
            "limitClient"      => 1,
            "clientMaxsize"    => 1,
            "clientBandwidth"  => 512,
            "delayPoolNumber"  => 0,
        ]);
        if(!$ok){ echo $tpl->js_mysql_alert($data); return; }
    }

    $aclid = 1;

    // Find or create the wizard group (identified by name and params sentinel)
    $gpid = 0;
    $allGroups = acls_api_get("/proxy/acls/sqlite/groups/all?search=".urlencode("Websites for bandwidth limit"));
    if(is_array($allGroups)){
        foreach($allGroups as $g){
            if(($g["groupName"] ?? '') === "Websites for bandwidth limit"){
                $gpid = intval($g["id"]);
                break;
            }
        }
    }

    if($gpid==0){
        [$ok, $data] = acls_api_send("POST","/proxy/acls/sqlite/group",[
            "name" => "Websites for bandwidth limit",
            "type" => "dstdomain",
        ]);
        if(!$ok){ echo $tpl->js_mysql_alert($data); return; }
        $gpid = intval($data["id"] ?? 0);
        if($gpid==0){ echo $tpl->js_mysql_alert("Unable to find WIZARDBANDWIDTH_1 group"); return; }
        // Set params sentinel so legacy code can still identify this group
        acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid",["params"=>"WIZARDBANDWIDTH_1"]);
    }

    // Replace all items in the group
    acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$gpid");
    $items = [];
    foreach([
        "ea.com","eamobile.com","microsoft.com","apple.com","playfirst.com",
        "msecnd.net","windowsupdate.com","googlevideo.com","youtube.com",
        "steamcommunity.com","steampowered.com","facebook.com","steamstatic.com",
        "^video.googleusercontent.com","nflxvideo.net","netflix.com",
        "nflximg.net","nflxext.com",
    ] as $pattern){
        $items[] = ["pattern"=>$pattern];
    }
    [$ok, $data] = acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"  => $gpid,
        "items" => $items,
        "mode"  => "upsert",
    ]);
    if(!$ok){ echo $tpl->js_mysql_alert($data); return; }

    // Link the group to the ACL rule
    $md5 = md5($aclid.$gpid);
    acls_api_send("DELETE","/proxy/acls/sqlite/link?table=squid_http_bandwidth_link&zmd5=".urlencode($md5));
    [$ok, $data] = acls_api_send("POST","/proxy/acls/sqlite/link",[
        "table" => "squid_http_bandwidth_link",
        "aclid" => $aclid,
        "gpid"  => $gpid,
    ]);
    if(!$ok){ echo $tpl->js_mysql_alert($data); return; }

    header("content-type: application/x-javascript");
    echo "LoadAjax('table-acls3-rules','$page?table=yes');";

}

function rule_enable(){
	header("content-type: application/x-javascript");
	$ID = intval($_GET["enable-js"]);
	if(!pbw_admin()){ echo "alert(".json_encode((new template_admin())->javascript_parse_text("{ERROR_NO_PRIVS}")).");"; return; }

	$row = acls_api_get("/proxy/bandwidth/acls/$ID");
	if(!is_array($row)){ echo "alert('rule $ID not found');"; return; }

	$enabled = intval($row["enabled"]) == 0 ? 1 : 0;
	if($enabled==1){
		$js="$( \"#acl-$ID\" ).removeClass( \"text-muted\" );";
	} else {
		$js="$( \"#acl-$ID\" ).addClass( \"text-muted\" );";
	}

	[$ok, $err] = acls_api_send("PUT","/proxy/bandwidth/acls/$ID",["enabled"=>$enabled]);
	if(!$ok){ echo "alert('".addslashes($err)."')"; return; }
    admin_tracks("Set a bandwidth ACL rule ID $ID to enable=$enabled");
	echo $js;
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval(trim($_GET["rule-id-js"]));
	$row = acls_api_get("/proxy/bandwidth/acls/$ID");
	$ruleName = $row["ruleName"] ?? '';
	$first=intval($_GET["objects-first"] ?? 0)==1?"&objects-first=1":"";
	$tpl->js_dialog5("{rule}: $ID ".htmlspecialchars((string)$ruleName),"$page?rule-tabs=$ID$first",950);
}



function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$RefreshTable=base64_encode("LoadAjax('table-acls3-rules','$page?table=yes');");
	$objects="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=squid_http_bandwidth_link&RefreshTable=$RefreshTable";
	$array=array();
	// Just created from the "New Rule" modal: what the rule still needs is its objects.
	if(intval($_GET["objects-first"] ?? 0)==1){ $array["{proxy_objects}"]=$objects; }
	$array["{rule}"]="$page?rule-settings=$ID";
	if(!isset($array["{proxy_objects}"])){ $array["{proxy_objects}"]=$objects; }
	echo $tpl->tabs_default($array);

}


function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-settings"]);
	$tpl->CLUSTER_CLI=true;

	if($ID==0){echo $tpl->FATAL_ERROR_SHOW_128("WRONG ID NUMBER");return;}

	$row = acls_api_get("/proxy/bandwidth/acls/$ID");
	if(!is_array($row)){ echo $tpl->FATAL_ERROR_SHOW_128("rule $ID not found"); return; }

    $form[]=$tpl->field_hidden("rule-save", "$ID");
    $form[]=$tpl->field_hidden("network_max_size", 0);
    $form[]=$tpl->field_hidden("client_maxsize", 0);
    $form[]=$tpl->field_text("rulename", "{rulename}",$tpl->utf8_encode($row["ruleName"] ?? ''), true);

    $form[]=$tpl->field_section("{global_limitation}");
    $form[]=$tpl->field_checkbox("limit_network","{limit_network}",$row["limitNetwork"],"network_max_size,network_bandwidth");
    $form[]=$tpl->field_kbps("network_bandwidth","{max_download_rate}",$row["networkBandwidth"]);

    $form[]=$tpl->field_section("{per_client_limit}");
    $form[]=$tpl->field_checkbox("limit_client","{limitb_client}",$row["limitClient"],"client_maxsize,client_bandwidth");
    $form[]=$tpl->field_kbps("client_bandwidth","{max_download_rate}",$row["clientBandwidth"]);
	$jsafter="LoadAjax('table-acls3-rules','$page?table=yes');";

	$html=$tpl->form_outside($tpl->utf8_encode($row["ruleName"] ?? ''), @implode("\n", $form),"{form_bandwidth_explain}","{apply}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}

function rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();

	$ID = intval($_POST["rule-save"]);
	if(!pbw_admin()){ echo $tpl->post_error("{ERROR_NO_PRIVS}"); return; }
	$rulename = $tpl->utf8_decode($_POST["rulename"]);

	$body = [
		"ruleName"         => $rulename,
		"limitNetwork"     => intval($_POST["limit_network"]),
		"networkMaxSize"   => intval($_POST["network_max_size"]),
		"networkBandwidth" => intval($_POST["network_bandwidth"]),
		"limitClient"      => intval($_POST["limit_client"]),
		"clientMaxsize"    => intval($_POST["client_maxsize"]),
		"clientBandwidth"  => intval($_POST["client_bandwidth"]),
	];

	[$ok, $err] = acls_api_send("PUT","/proxy/bandwidth/acls/$ID", $body);
	if(!$ok){ echo $err; return; }
    admin_tracks("update $rulename bandwidth ACL rule settings");
}

function new_rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	return $tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function HEADERS_ARRAY():array{
    $HEADERSZ=array();
	$resultsHeaders = acls_api_get("/http-headers");
	if(is_array($resultsHeaders)){
		foreach ($resultsHeaders as $ligneHeaders){
			$name = $ligneHeaders["HeaderName"] ?? ($ligneHeaders["headerName"] ?? '');
			if($name !== ''){ $HEADERSZ[$name]=$name; }
		}
		ksort($HEADERSZ);
	}
	return $HEADERSZ;
}

// ---- "New Rule" modal (maquette stitch_bandwidth_limit_rule_modal, 2026-11-19) ---------------
// Real data only: the form posts what POST /proxy/bandwidth/acls stores (ruleName, limitNetwork,
// networkBandwidth, limitClient, clientBandwidth; the "after X MB" sizes stay 0, as before).
// The rule is created enabled at the end of the list and applies to NOBODY until proxy objects
// are linked to it (RunAclsBandwidth: no group -> delay_access deny all), so after "Add" the
// rule window opens on its "Proxy objects" tab. One function per widget, CSS pbw_style().
// Left out of the mock-up: the breadcrumb (the dialog has its title), "Cancel" (the dialog has
// its cross), the "Active limit" tag (the switch already says it), per-card reference lines
// with different claims (one neutral reference line is kept).
function new_rule_popup(){
    $tpl=new template_admin();
    $k=time();
    $h=array();
    $h[]="<div class='pbw' id='pbw-$k'>";
    $h[]=pbw_intro();
    if(pbw_cluster_slave()){
        $h[]="<div class='ak-banner ak-info'><i class='fas fa-circle-info'></i><div class='ak-banner-m'>{pbw_cluster_slave}</div></div></div>";
        echo pbw_style().$tpl->_ENGINE_parse_body(@implode("\n",$h));
        return;
    }
    $h[]=pbw_name_field($k);
    $h[]="<div class='pbw-cards'>";
    $h[]=pbw_limit_card($k,"net","fa-users","{global_limitation}","{pbw_global_sub}","{limit_network}","{pbw_global_help}",1024);
    $h[]=pbw_limit_card($k,"cli","fa-laptop","{per_client_limit}","{pbw_client_sub}","{limitb_client}","{pbw_client_help}",512);
    $h[]="</div>";
    $h[]="<p class='pbw-ref'><i class='far fa-lightbulb'></i>{pbw_reference}</p>";
    $h[]=pbw_summary($k);
    $h[]=pbw_next_steps();
    $h[]=pbw_footer($k);
    $h[]="</div>";
    echo pbw_style().$tpl->_ENGINE_parse_body(@implode("\n",$h))."<script>".pbw_js($tpl,$k)."</script>";
}

function pbw_cluster_slave(){
    return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"))==1;
}

function pbw_intro(){
    return "<section class='pbw-intro'><span class='ak-ico ak-ico-sm ak-ok'><i class='fas fa-gauge-high'></i></span><div>".
        "<b>{pbw_intro_title}</b><p>{pbw_intro_text}</p>".
        "<details class='pbw-how'><summary><i class='fas fa-chevron-right ak-chev'></i>{pbw_how_title}</summary><p>{pbw_how_text}</p></details>".
        "</div></section>";
}

function pbw_name_field($k){
    $tpl=new template_admin();
    $ph=docker_like_attr($tpl->javascript_parse_text("{pbw_name_placeholder}"));
    return "<div class='pbw-field'><label for='pbw-name-$k'>{rule_name} <span class='pbw-req' aria-hidden='true'>*</span></label>".
        "<input type='text' id='pbw-name-$k' class='pbw-in' maxlength='128' placeholder=\"$ph\" required aria-required='true' aria-describedby='pbw-name-help-$k'>".
        "<p class='pbw-help' id='pbw-name-help-$k'>{pbw_name_help}</p></div>";
}

// An attribute value from a translated label (no markup, quotes escaped).
function docker_like_attr($t){
    return htmlspecialchars(html_entity_decode(strip_tags((string)$t),ENT_QUOTES,"UTF-8"),ENT_QUOTES,"UTF-8");
}

function pbw_limit_card($k,$id,$ico,$title,$sub,$switch,$help,$default){
    $presets=array(256,512,1024,2048,5120);
    $chips=array();
    foreach($presets as $v){
        $chips[]="<button type='button' class='pbw-chip' data-v='$v' aria-pressed='false' OnClick=\"PbwPreset$k('$id',$v);\">".pbw_rate_label($v)."</button>";
    }
    return "<section class='pbw-card' id='pbw-card-$id-$k'>".
        "<div class='pbw-card-h'><span class='ak-ico ak-ico-sm ak-off'><i class='fas $ico'></i></span>".
        "<div class='pbw-card-t'><b>$title</b><span>$sub</span></div>".
        "<button type='button' role='switch' class='ak-sw' id='pbw-sw-$id-$k' aria-checked='true' aria-label=\"".docker_like_attr($switch)."\" title=\"".docker_like_attr($switch)."\" OnClick=\"PbwSwitch$k('$id');\"></button></div>".
        "<p class='pbw-help'>$help</p>".
        "<div class='pbw-rate'><label for='pbw-v-$id-$k'>{max_download_rate}</label>".
        "<div class='pbw-in-wrap'><input type='number' min='1' step='1' id='pbw-v-$id-$k' class='pbw-in' value='$default' OnInput=\"PbwUpdate$k();\"><span class='pbw-unit'>KB/s</span></div>".
        "<p class='pbw-conv' id='pbw-conv-$id-$k'>".pbw_rate_conv($default)."</p>".
        "<div class='pbw-chips' role='group' aria-label='{pbw_presets}'><span class='pbw-chips-l'>{pbw_presets}</span>".implode("",$chips)."</div>".
        "<p class='pbw-off-note'>{pbw_turn_on}</p></div></section>";
}

// 1024 KB/s = 1 MB/s; link speed in Mbps = KB/s x 8 / 1000 (same rule as field_kbps()).
function pbw_rate_label($v){
    $mbps=round($v/125);
    $main=($v>=1024)?(rtrim(rtrim(number_format($v/1024,2,".",""),"0"),".")." MB/s"):"$v KB/s";
    return "$main &middot; $mbps Mbps";
}
function pbw_rate_conv($v){
    return number_format($v/1024,2,".","")." MB/s &middot; ".round($v/125)." Mbps";
}

function pbw_summary($k){
    return "<div class='pbw-sum' id='pbw-sum-$k' aria-live='polite'><span class='pbw-sum-ok'><i class='fas fa-wand-magic-sparkles'></i></span><span class='pbw-sum-ko'><i class='fas fa-triangle-exclamation'></i></span><span id='pbw-sum-t-$k'></span></div>";
}

function pbw_next_steps(){
    return "<div class='ak-banner ak-info pbw-next'><i class='fas fa-circle-info'></i><div class='ak-banner-m'>{pbw_next_steps}</div></div>";
}

function pbw_footer($k){
    return "<div class='pbw-err' id='pbw-err-$k' role='alert'></div>".
        "<div class='pbw-foot'><span class='pbw-foot-n'><i class='fas fa-circle-check'></i>{pbw_created_enabled}</span>".
        "<button type='button' class='ak-btn ak-btn-primary ak-btn-lg' id='pbw-add-$k' OnClick=\"PbwAdd$k();\"><i class='fas fa-plus'></i>{pbw_add}</button></div>";
}

function pbw_js($tpl,$k){
    $page=CurrentPageName();
    $T=array();
    foreach(array("pbw_sum_both","pbw_sum_global","pbw_sum_client","pbw_sum_none","pbw_sum_client_higher","pbw_err_name","pbw_err_rate","pbw_add") as $tok){
        $T[$tok]=html_entity_decode(str_replace("[br]"," ",$tpl->_ENGINE_parse_body("{".$tok."}")),ENT_QUOTES,"UTF-8");
    }
    $js=<<<'JS'
(function(){
 var K='__K__', T=__T__, PAGE='__PAGE__';
 function $i(id){ return document.getElementById(id); }
 function on(id){ return $i('pbw-sw-'+id+'-'+K).getAttribute('aria-checked')==='true'; }
 function val(id){ var v=parseInt($i('pbw-v-'+id+'-'+K).value,10); return isNaN(v)?0:v; }
 function human(v){ return v>=1024?(Math.round(v/1024*100)/100)+' MB/s':v+' KB/s'; }
 function esc(t){ var d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
 window['PbwSwitch'+K]=function(id){
  var b=$i('pbw-sw-'+id+'-'+K), now=b.getAttribute('aria-checked')!=='true';
  b.setAttribute('aria-checked',now?'true':'false');
  $i('pbw-card-'+id+'-'+K).classList.toggle('pbw-card-off',!now);
  $i('pbw-v-'+id+'-'+K).disabled=!now;
  var cs=$i('pbw-card-'+id+'-'+K).querySelectorAll('.pbw-chip'); for(var i=0;i<cs.length;i++){ cs[i].disabled=!now; }
  window['PbwUpdate'+K]();
 };
 window['PbwPreset'+K]=function(id,v){ $i('pbw-v-'+id+'-'+K).value=v; window['PbwUpdate'+K](); };
 window['PbwUpdate'+K]=function(){
  var ids=['net','cli'];
  for(var n=0;n<ids.length;n++){ var id=ids[n], v=val(id);
   $i('pbw-conv-'+id+'-'+K).innerHTML=v>0?((v/1024).toFixed(2)+' MB/s &middot; '+Math.round(v/125)+' Mbps'):'&mdash;';
   var cs=$i('pbw-card-'+id+'-'+K).querySelectorAll('.pbw-chip');
   for(var i=0;i<cs.length;i++){ var p=(parseInt(cs[i].getAttribute('data-v'),10)===v); cs[i].setAttribute('aria-pressed',p?'true':'false'); }
  }
  var g=on('net'), c=on('cli'), gv=val('net'), cv=val('cli'), box=$i('pbw-sum-'+K), t='', warn=false;
  if(g&&c){ t=T.pbw_sum_both.replace('%g',human(gv)).replace('%c',human(cv)); if(cv>gv&&gv>0){ t+=' '+T.pbw_sum_client_higher; warn=true; } }
  else if(g){ t=T.pbw_sum_global.replace('%g',human(gv)); }
  else if(c){ t=T.pbw_sum_client.replace('%c',human(cv)); }
  else { t=T.pbw_sum_none; warn=true; }
  $i('pbw-sum-t-'+K).textContent=t;
  box.className='pbw-sum'+(warn?' pbw-sum-warn':'');
  var add=$i('pbw-add-'+K), none=(!g&&!c);
  add.disabled=none; add.title=none?T.pbw_sum_none:'';
 };
 window['PbwAdd'+K]=function(){
  var err=$i('pbw-err-'+K), name=$i('pbw-name-'+K).value.trim(), g=on('net'), c=on('cli');
  err.innerHTML='';
  if(name===''){ err.innerHTML=esc(T.pbw_err_name); $i('pbw-name-'+K).focus(); return; }
  if((g&&val('net')<1)||(c&&val('cli')<1)){ err.innerHTML=esc(T.pbw_err_rate); return; }
  var add=$i('pbw-add-'+K); add.disabled=true; add.setAttribute('aria-busy','true');
  $.post(PAGE,{'newrule':'yes','rulename':name,'limit_network':g?1:0,'network_bandwidth':val('net'),'limit_client':c?1:0,'client_bandwidth':val('cli'),'network_max_size':0,'client_maxsize':0},null,'text')
   .done(function(r){
     r=String(r||'').trim(); var j=null; try{ j=JSON.parse(r); }catch(e){}
     if(!j||!j.id){ if(r.indexOf('jserror:')===0){ r=r.substring(8); } err.innerHTML='<i class="fas fa-triangle-exclamation"></i> '+(r!==''?r:esc(T.pbw_err_rate)); add.disabled=false; add.removeAttribute('aria-busy'); return; }
     LoadAjax('table-acls3-rules',PAGE+'?table=yes');
     if(typeof BootstrapDialog1!=='undefined' && BootstrapDialog1){ BootstrapDialog1.close(); }
     Loadjs(PAGE+'?rule-id-js='+j.id+'&objects-first=1');
   })
   .fail(function(x){ err.innerHTML='<i class="fas fa-triangle-exclamation"></i> HTTP '+x.status; add.disabled=false; add.removeAttribute('aria-busy'); });
 };
 window['PbwUpdate'+K]();
})();
JS;
    return str_replace(array("__K__","__T__","__PAGE__"),array($k,json_encode($T),$page),$js);
}

function pbw_style(){
    if(isset($GLOBALS["PBW_STYLE"])){return "";}
    $GLOBALS["PBW_STYLE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.pbw{display:flex;flex-direction:column;gap:14px;padding:4px 2px;font-size:14px;color:#0f172a}
.pbw *{box-sizing:border-box}
.pbw p{margin:0}
.pbw-intro{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border:1px solid #a7f3d0;border-radius:10px;background:$soft}
.pbw-intro b{font-size:15px}
.pbw-intro p{font-size:13px;color:#475569;line-height:1.5;margin-top:4px}
.pbw-how{margin-top:8px;font-size:13px}
.pbw-how summary{cursor:pointer;color:#00504a;font-weight:600;list-style:none;display:inline-flex;align-items:center;gap:6px}
.pbw-how summary::-webkit-details-marker{display:none}
.pbw-how[open] .ak-chev{transform:rotate(90deg)}
.pbw-how p{margin-top:6px}
.pbw-field label,.pbw-rate label{display:block;font-size:13px;font-weight:600;margin-bottom:6px}
.pbw-req{color:#dc2626}
.pbw-in{width:100%;height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px;font-size:14px;color:#0f172a;background:#fff}
.pbw-in:focus{outline:2px solid $accent;outline-offset:1px;border-color:$accent}
.pbw-help{font-size:13px;color:#64748b;line-height:1.45;margin-top:6px!important}
.pbw-cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.pbw-card{border:1px solid #a7f3d0;border-radius:12px;padding:14px 16px;background:#fff;display:flex;flex-direction:column;gap:10px;transition:border-color .15s,opacity .15s}
.pbw-card-off{border-color:#e2e8f0}
.pbw-card-off .pbw-rate>*:not(.pbw-off-note){opacity:.45}
.pbw-card-h{display:flex;align-items:flex-start;gap:10px}
.pbw-card-t{flex:1 1 auto;display:flex;flex-direction:column}
.pbw-card-t b{font-size:15px}
.pbw-card-t span{font-size:13px;color:#64748b}
.pbw-card>.pbw-help{margin-top:0!important;padding-bottom:10px;border-bottom:1px solid #f1f5f9}
.pbw-in-wrap{display:flex}
.pbw-in-wrap .pbw-in{border-radius:8px 0 0 8px}
.pbw-unit{display:inline-flex;align-items:center;padding:0 12px;border:1px solid #cbd5e1;border-left:0;border-radius:0 8px 8px 0;background:#f8fafc;font-size:13px;color:#475569}
.pbw-conv{font-size:13px;color:#047857;margin-top:6px!important}
.pbw-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;align-items:center}
.pbw-chips-l{width:100%;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#64748b}
.pbw-chip{border:1px solid #e2e8f0;background:#fff;border-radius:8px;padding:4px 10px;font-size:13px;color:#334155;cursor:pointer}
.pbw-chip:hover{border-color:$accent}
.pbw-chip[aria-pressed='true']{border-color:$accent;background:$soft;color:#00504a;font-weight:600}
.pbw-chip:disabled{cursor:not-allowed}
.pbw-chip:focus-visible,.pbw .ak-sw:focus-visible,.pbw-how summary:focus-visible{outline:2px solid $accent;outline-offset:2px}
.pbw-off-note{display:none;font-size:13px;color:#64748b;margin-top:8px!important}
.pbw-card-off .pbw-off-note{display:block}
.pbw-ref{font-size:13px;color:#64748b;display:flex;gap:8px;align-items:center}
.pbw-ref svg,.pbw-ref i{color:#d97706}
.pbw-sum{display:flex;gap:10px;align-items:center;padding:12px 14px;border:1px solid #a7f3d0;border-radius:8px;background:#f0fdf4;font-size:14px;color:#064e3b}
.pbw-sum-warn{border-color:#fcd34d;background:#fffbeb;color:#92400e}
.pbw-sum-ko,.pbw-sum-warn .pbw-sum-ok{display:none}
.pbw-sum-warn .pbw-sum-ko{display:inline}
.pbw-next{margin:0}
.pbw-err:empty{display:none}
.pbw-err{padding:10px 12px;border-radius:8px;background:#fef2f2;color:#b91c1c;font-size:13px}
.pbw-foot{display:flex;align-items:center;gap:12px;padding-top:12px;border-top:1px solid #f1f5f9}
.pbw-foot-n{flex:1 1 auto;display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#64748b}
.pbw-foot-n svg,.pbw-foot-n i{color:#059669}
@media(max-width:720px){.pbw-cards{grid-template-columns:1fr}}
@media (prefers-reduced-motion:reduce){.pbw-card{transition:none}}
</style>";
}

function rule_move(){
	$tpl=new template_admin();
	$ID  = intval($_GET["acl-rule-move"]);
	$dir = intval($_GET["acl-rule-dir"]);
	if(!pbw_admin()){ echo "alert(".json_encode($tpl->javascript_parse_text("{ERROR_NO_PRIVS}")).");"; return; }

	[$ok, $err] = acls_api_send("PUT","/proxy/bandwidth/acls/$ID/move",["dir"=>$dir]);
	if(!$ok){ echo "alert('".$tpl->javascript_parse_text($err)."');"; return; }
}


function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$order=$tpl->_ENGINE_parse_body("{order}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

	$results = acls_api_get("/proxy/bandwidth/acls");
	if(!is_array($results)){ $results=[]; }

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>$order</th>";
	$html[]="<th class='awc-nowrap'>{rulename}</th>";
	$html[]="<th>{description}</th>";
	if($PowerDNSEnableClusterSlave==0) {
        $html[] = "<th class='center'>$enabled</th>";
        $html[] = "<th></th>";
        $html[] = "<th></th>";

    }
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='bandwidth-rows'>";

	$jsAfter="LoadAjax('table-proxyheader-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$TRCLASS=null;


	foreach($results as $index=>$ligne) {
		$TRCLASS=null;
		$MUTED=null;
		$md=md5(serialize($ligne));
		$ID=$ligne["id"];
		$ligne['ruleName']=$tpl->utf8_encode($ligne['ruleName'] ?? '');

		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID&md=$md')","AsSquidAdministrator");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if(intval($ligne["enabled"])==0){$MUTED=" text-muted";}


		$explain=EXPLAIN_THIS_RULE($ID);
		$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');","AsSquidAdministrator");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');","AsSquidAdministrator");
		$enable_js="Loadjs('$page?enable-js=$ID')";

		$html[]="<tr class='$TRCLASS{$MUTED}' id='$md'>";
		$html[]="<td id=$index class=\"center\" style='width:1%' nowrap>{$ligne["xOrder"]}</td>";
		$html[]="<td style='vertical-align:middle;;width:1%' nowrap>". $tpl->td_href($ligne["ruleName"],"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
        if($PowerDNSEnableClusterSlave==0) {
            $html[] = "<td class='center' style='width:1%'  nowrap>" . $tpl->icon_check($ligne["enabled"], $enable_js,null,"AsSquidAdministrator") . "</td>";
            $html[] = "<td style='vertical-align:middle;width:1%' class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
            $html[] = "<td style='vertical-align:middle;width:1%' class='center' nowrap>$delete</td>";
        }
		$html[]="</tr>";

	}



	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='bandwidth-pager'></span></div>";
	$html[]="</div></div>";
	$html[]="
<script>
Loadjs('$page?jstiny=yes')
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"bandwidth-rows","bandwidth-pager");
}

function js_tiny(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $add="Loadjs('$page?newrule-js=yes',true);";
    $users=new usersMenus();

    $jsrestart=$tpl->framework_buildjs("/proxy/acls/bandwidth","squid.bandwww.progress","squid.bandwww.txt","progress-acls3-restart","LoadAjax('table-acls3-rules','$page?table=yes');");

    $jshelp="s_PopUpFull('https://wiki.articatech.com/proxy-service/acls/bandwidth-limiting',1024,768,'Bandwidth Limiting')";

    if($PowerDNSEnableClusterSlave==0) {
        if($users->AsSquidAdministrator) {
            $topbuttons[] = array($add, ico_plus, "{new_rule}");
        }
    }
    if($users->AsSquidAdministrator) {
        $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");
    }
    $topbuttons[] = array($jshelp, "fas fa-question-circle", "Wiki");
    $jsexample="Loadjs('$page?js-example=yes');";

    if($PowerDNSEnableClusterSlave==0) {
        $rule1 = acls_api_get("/proxy/bandwidth/acls/1");
        if(!is_array($rule1) || strlen(trim($rule1["ruleName"] ?? ''))==0){
            $topbuttons[] = array($jsexample, "fas fa-hat-wizard", "{example}");
        }
    }
    $TINY_ARRAY["TITLE"]="{bandwidth_rules_mini}";
    $TINY_ARRAY["ICO"]="fas fa-filter";
    $TINY_ARRAY["EXPL"]="{bandwidth_rules_mini_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}


// Answers JSON {"id":N} on success (the modal then opens the rule on its Proxy objects tab),
// otherwise "jserror:<message>". Same body as before; values are now validated server-side.
function new_rule_save(){
    $tpl=new template_admin();
    if(!pbw_admin()){ echo $tpl->post_error("{ERROR_NO_PRIVS}"); return; }
    if(pbw_cluster_slave()){ echo $tpl->post_error("{pbw_cluster_slave}"); return; }
    // Posted by jQuery (UTF-8, encoded once): no CLEAN_POST(), which would decode a second time.
    $rulename=trim(strip_tags((string)($_POST["rulename"] ?? "")));
    $rulename=trim(preg_replace('/\s+/u'," ",$rulename));
    if(function_exists("mb_substr")){ $rulename=mb_substr($rulename,0,128,"UTF-8"); }
    $limitNet=intval($_POST["limit_network"] ?? 0)==1?1:0;
    $limitCli=intval($_POST["limit_client"] ?? 0)==1?1:0;
    $netBw=intval($_POST["network_bandwidth"] ?? 0);
    $cliBw=intval($_POST["client_bandwidth"] ?? 0);
    if($rulename===""){ echo $tpl->post_error("{pbw_err_name}"); return; }
    if($limitNet==0 && $limitCli==0){ echo $tpl->post_error("{pbw_sum_none}"); return; }
    if(($limitNet==1 && $netBw<1) || ($limitCli==1 && $cliBw<1)){ echo $tpl->post_error("{pbw_err_rate}"); return; }

    $body = [
        "ruleName"         => $tpl->utf8_decode($rulename),
        "limitNetwork"     => $limitNet,
        "networkMaxSize"   => 0,
        "networkBandwidth" => $netBw,
        "limitClient"      => $limitCli,
        "clientMaxsize"    => 0,
        "clientBandwidth"  => $cliBw,
    ];
    [$ok, $data] = acls_api_send("POST","/proxy/bandwidth/acls", $body);
    if(!$ok){ echo $tpl->post_error(is_string($data)?$data:"{operation_failed}"); return; }
    admin_tracks("Create a new $rulename bandwidth ACL rule");
    header("Content-Type: application/json");
    echo json_encode(array("id"=>intval(is_array($data)?($data["id"] ?? 0):0)));
}

// Every write of this page is a proxy administrator's (the buttons were only hidden before).
function pbw_admin(){
    $users=new usersMenus();
    return $users->AsSquidAdministrator===true;
}

function rule_delete($ID):bool{
	[$ok, $err] = acls_api_send("DELETE","/proxy/bandwidth/acls/".intval($ID));
	if(!$ok){ echo "alert('".addslashes($err)."');"; return false; }
    admin_tracks("Delete a bandwidth ACL rule ID $ID");
	return true;

}
/**
 * Render a KB/s rate in familiar units: "512 KB/s (0.5 MB/s, 4 Mbps)".
 *
 * Bandwidth values are stored in KB/s (kilobytes per second) — the same unit
 * Squid's delay pools consume after a x1024 conversion to bytes/s. We surface
 * both MB/s (file-transfer speak) and the link speed in Mbps/Gbps (ISP speak):
 * 1024 KB/s = 1 MB/s ~= 8 Mbps.
 */
function bandwidth_human($kbps){
    $kbps=intval($kbps);
    if($kbps<=0){ return "0 KB/s"; }
    $mbs  = round($kbps/1024, 2);   // MB/s
    $mbps = round($kbps/125);       // Mbit/s (link speed)
    if($mbps>1000){
        return "$kbps KB/s (".round($kbps/125000,1)." Gbps)";
    }
    if($mbs>=1){
        return "$kbps KB/s ($mbs MB/s, $mbps Mbps)";
    }
    return "$kbps KB/s ($mbps Mbps)";
}

function EXPLAIN_THIS_RULE($ID){
    $tpl=new templates();
    $THEN=array();

    $acls=new squid_acls_groups();
    $row = acls_api_get("/proxy/bandwidth/acls/".intval($ID));
    if(!is_array($row)){ return ''; }

    // Which objects (domains/IPs) the rule applies to.
    $objects=$acls->getobjectsNameFromAclrule(intval($row['id']),"black","squid_http_bandwidth_link");
    if(count($objects)>0){
        $THEN[]="{for_objects} ". @implode(", {and} ", $objects)." {then}";
    }

    $limit_network    = intval($row["limitNetwork"]);     // global cap on/off
    $network_max_size = intval($row["networkMaxSize"]);   // MB before throttling
    $network_bandwidth= intval($row["networkBandwidth"]); // KB/s
    $limit_client     = intval($row["limitClient"]);      // per-client cap on/off
    $client_maxsize   = intval($row["clientMaxsize"]);    // MB before throttling
    $client_bandwidth = intval($row["clientBandwidth"]);  // KB/s

    // --- Global (shared by all matching clients) limitation ---
    if($limit_network==1){
        if($network_max_size>0){
            $THEN[]="{after_download_of} {$network_max_size}MB";
        }
        if($network_bandwidth>0){
            $THEN[]="{limit_bandwidth} ".bandwidth_human($network_bandwidth);
        }else{
            $THEN[]="{do_not_limit_bandwidth}";
        }
    }

    // --- Per-client limitation ---
    if($limit_network==1 && $limit_client==1){
        $THEN[]="{and}";
    }

    if($limit_client==1){
        if($client_maxsize>0){
            $THEN[]="{after_download_of} {$client_maxsize}MB";
        }
        if($client_bandwidth>0){
            $THEN[]="{limitb_client} ".bandwidth_human($client_bandwidth);
        }
    }

    $THEN_TEXT=@implode(" ",$THEN);

    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($THEN_TEXT);
    }

    $page=CurrentPageName();
    return $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$THEN_TEXT</span>");
}
