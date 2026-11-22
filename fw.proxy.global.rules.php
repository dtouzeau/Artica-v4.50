<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["gr-toggle"])){gr_toggle();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{global_rules}",
        "fa-solid fa-ballot-check","{permanent_authorizations}",
    "$page?table=yes","proxy-global-rules","progress-firehol-restart",false,"table-loader-cache-level");


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{global_rules}",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

// The 15 permanent authorizations, in display order. The key is the setting
// name: it is also the whitelist of what gr_toggle() accepts to write.
function gr_rules():array{
    $R=array();
    $R["AllowWindowsUpdates"]=array("{ProxyDedicateMicrosoftRules}","{ProxyDedicateMicrosoftRules_explain}","fa-brands fa-windows","blue","{gr_tag_updates}","sec");
    $R["SquidAllowSmartPhones"]=array("{AllowSmartphonesRuleText}","{AllowSmartphonesRuleExplain}","fa-solid fa-mobile-screen","slate","{gr_tag_auth}","sec");
    $R["AllowSquidDropBox"]=array("{AllowSquidDropBox}","{AllowSquidDropBox_explain}","fa-brands fa-dropbox","blue","{gr_tag_cloud}","msg");
    $R["AllowSquidWhatsApp"]=array("{AllowSquidWhatsApp}","{AllowSquidWhatsApp_explain}","fa-brands fa-whatsapp","green","{gr_tag_messaging}","msg");
    $R["AllowSquidSkype"]=array("{AllowSquidSkype}","{AllowSquidSkype_explain}","fa-brands fa-skype","sky","{gr_tag_voip}","msg");
    $R["AllowSquidMicrosoft"]=array("{AllowSquidMicrosoft}","{AllowSquidMicrosoft_explain}","fa-brands fa-microsoft","indigo","{gr_tag_web}","sec");
    $R["AllowSquidOffice365"]=array("{AllowSquidOffice365}","{AllowSquidOffice365_explain}","fa-solid fa-cloud","orange","{gr_tag_office}","msg");
    $R["AllowSquidTeamViewer"]=array("{AllowSquidTeamViewer}","{AllowSquidTeamViewer_explain}","fa-solid fa-display","blue","{gr_tag_remote}","msg");
    $R["AllowSquidGoogle"]=array("{AllowSquidGoogle}","{AllowSquidGoogle_explain}","fa-brands fa-google","red","{gr_tag_google}","msg");
    $R["AllowSquidAvVendors"]=array("{AllowSquidAvVendors}","{AllowSquidAvVendors_explain}","fa-solid fa-shield-virus","teal","{gr_tag_av}","sec");
    $R["AllowSquidGoogleHearth"]=array("{AllowSquidGoogleHearth}","{AllowSquidGoogleHearth_explain}","fa-solid fa-earth-americas","green","{gr_tag_maps}","msg");
    $R["AllowSquidCompatibility"]=array("{AllowSquidCompatibility}","{AllowSquidCompatibility_explain}","fa-solid fa-network-wired","teal","{gr_tag_compat}","net");
    $R["AllowSquidOtherProtocols"]=array("{AllowSquidOtherProtocols}","{AllowSquidOtherProtocols_explain}","fa-solid fa-arrows-split-up-and-left","indigo","{gr_tag_http}","net");
    $R["AllowSquidHSTS"]=array("{AllowSquidHSTS}","{AllowSquidHSTS_explain}","fa-solid fa-shield-halved","slate","{gr_tag_http}","net");
    $R["AllowSquidWhitelistsSSL"]=array("{AllowSquidWhitelistsSSL}","{AllowSquidWhitelistsSSL_explain}","fa-solid fa-lock-open","amber","{gr_tag_ssl}","net");
    return $R;
}

function gr_value($key):int{
    $raw=$GLOBALS["CLASS_SOCKETS"]->GET_INFO($key);
    // Antivirus / EDR vendors are allowed until the administrator says otherwise.
    if($key=="AllowSquidAvVendors" && $raw===""){return 1;}
    return intval($raw)==1?1:0;
}

function gr_is_admin():bool{
    $users=new usersMenus();
    return $users->AsSquidAdministrator?true:false;
}

function gr_globally_disabled():bool{
    return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableWhiteLists"))==1;
}

function table(){
	$tpl=new template_admin();
    $page=CurrentPageName();
    $disabled=gr_globally_disabled();
    $admin=gr_is_admin();
    $rules=gr_rules();

    $V=array();
    $C=array("all"=>0,"sec"=>0,"msg"=>0,"net"=>0);
    foreach ($rules as $key=>$r){
        $V[$key]=$disabled?0:gr_value($key);
        $C["all"]++;
        $C[$r[5]]++;
    }

    $html=array();
    if($disabled){$html[]=$tpl->div_warning("{aclgbdisabled}");}
    $html[]=gr_kpis($V);
    $html[]="<div class='gr-grid' id='gr-grid'>";
    foreach ($rules as $key=>$r){
        $html[]=gr_card($key,$r,$V[$key],$admin && !$disabled);
    }
    $html[]="</div>";
    $html[]="<div class='gr-empty' id='gr-empty' style='display:none'>{gr_no_match}</div>";

    $TINY=array();
    $TINY["TITLE"]="{global_rules}";
    $TINY["ICO"]="fa-solid fa-ballot-check";
    $TINY["EXPL"]="{permanent_authorizations}";
    $TINY["URL"]="proxy-global-rules";
    $TINY["BUTTONS"]=siem_tiny_filters(gr_tiny_filters($C),"");
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

    $jsCompile=$tpl->framework_buildjs("/proxy/whitelists/nohupcompile","squid.global.whitelists.progress","squid.global.whitelists.progress.log","progress-firehol-restart","","","","AsSquidAdministrator");

    echo siem_tiny_filters_style().gr_css().$tpl->_ENGINE_parse_body(@implode("\n",$html)).gr_js($page,$jsCompile,count($rules))."<script>$tiny</script>";
}

function gr_tiny_filters($C):array{
    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"gr-f-cat","LABEL"=>"","VALUE"=>"all","JS"=>"GrCat",
        "OPTIONS"=>array(
            "all"=>"{all} ({$C["all"]})",
            "sec"=>"{gr_cat_security} ({$C["sec"]})",
            "msg"=>"{gr_cat_messaging} ({$C["msg"]})",
            "net"=>"{gr_cat_network} ({$C["net"]})"));
    $fields[]=array("TYPE"=>"text","ID"=>"gr-f-search","LABEL"=>"","VALUE"=>"","JS"=>"GrSearch",
        "PLACEHOLDER"=>"{gr_filter_rule}","WIDTH"=>"200px");
    return $fields;
}

function gr_card($key,$r,$value,$editable):string{
    list($title,$explain,$ico,$tone,$tag,$cat)=$r;
    $on=$value==1;
    $cls=$on?"gr-card gr-on":"gr-card";
    $checked=$on?" checked":"";
    $dis=$editable?"":" disabled";
    $state=$on?"{enabled}":"{disabled}";
    return "<article class='$cls' id='gr-card-$key' data-cat='$cat'>
<div class='gr-top'>
  <span class='gr-ico gr-t-$tone'><i class='$ico'></i></span>
  <div class='gr-head'><span class='gr-tag gr-t-$tone'>$tag</span><h3 class='gr-title'>$title</h3></div>
  <label class='gr-sw'><input type='checkbox' id='gr-sw-$key'$checked$dis OnChange=\"GrToggle(this,'$key')\"><span></span></label>
</div>
<div class='gr-desc'>$explain</div>
<div class='gr-foot'><span class='gr-key'>$key</span><span class='gr-state' id='gr-state-$key'>$state</span></div>
</article>";
}

function gr_kpi_tile($id,$label,$value,$ico,$tone):string{
    return "<div class='ak-card gr-kpi'><span class='gr-kpi-ico gr-t-$tone'><i class='$ico'></i></span>"
        ."<div><div class='gr-kpi-l'>$label</div><div class='gr-kpi-v' id='$id'>$value</div></div></div>";
}
function gr_kpi_active($V):string{
    return gr_kpi_tile("gr-kpi-active","{gr_active_rules}",array_sum($V)." / ".count($V),"fa-solid fa-circle-check","green");
}
function gr_kpi_av($V):string{
    return gr_kpi_tile("gr-kpi-av","{gr_kpi_av}",$V["AllowSquidAvVendors"]==1?"{enabled}":"{disabled}","fa-solid fa-shield-virus","teal");
}
function gr_kpi_ssl($V):string{
    return gr_kpi_tile("gr-kpi-ssl","{gr_kpi_ssl}",$V["AllowSquidWhitelistsSSL"]==1?"{gr_ssl_bypass}":"{gr_ssl_decrypted}","fa-solid fa-lock-open","amber");
}
function gr_kpi_compat($V):string{
    return gr_kpi_tile("gr-kpi-compat","{gr_kpi_compat}",$V["AllowSquidCompatibility"]==1?"{enabled}":"{disabled}","fa-solid fa-arrows-split-up-and-left","blue");
}
function gr_kpis($V):string{
    return "<div class='gr-kpis'>".gr_kpi_active($V).gr_kpi_av($V).gr_kpi_ssl($V).gr_kpi_compat($V)."</div>";
}

// Client side: filters, and the toggle that saves one setting then recompiles
// the whitelists. Labels are resolved here because this block is concatenated
// outside _ENGINE_parse_body().
function gr_js($page,$jsCompile,$total):string{
    $tpl=new template_admin();
    $L=array();
    foreach (array("enabled","disabled","gr_ssl_bypass","gr_ssl_decrypted") as $t){
        $L[$t]=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{".$t."}"),ENT_QUOTES,"UTF-8"));
    }
    return "<script>
(function(){
var GR={cat:'all',q:''};
function grApply(){
    var n=0,cards=document.querySelectorAll('#gr-grid .gr-card');
    for(var i=0;i<cards.length;i++){
        var c=cards[i],ok=(GR.cat=='all'||c.getAttribute('data-cat')==GR.cat);
        if(ok && GR.q!=''){ok=(c.textContent||'').toLowerCase().indexOf(GR.q)>-1;}
        c.style.display=ok?'':'none'; if(ok){n++;}
    }
    var e=document.getElementById('gr-empty'); if(e){e.style.display=n?'none':'';}
}
function grKpis(){
    var cards=document.querySelectorAll('#gr-grid .gr-card'),on=0;
    for(var i=0;i<cards.length;i++){ if(cards[i].className.indexOf('gr-on')>-1){on++;} }
    var set=function(id,v){var e=document.getElementById(id); if(e){e.textContent=v;}};
    var is=function(k){var e=document.getElementById('gr-sw-'+k); return e&&e.checked;};
    set('gr-kpi-active',on+' / $total');
    set('gr-kpi-av',is('AllowSquidAvVendors')?{$L["enabled"]}:{$L["disabled"]});
    set('gr-kpi-ssl',is('AllowSquidWhitelistsSSL')?{$L["gr_ssl_bypass"]}:{$L["gr_ssl_decrypted"]});
    set('gr-kpi-compat',is('AllowSquidCompatibility')?{$L["enabled"]}:{$L["disabled"]});
}
function grMark(key,on){
    var c=document.getElementById('gr-card-'+key); if(c){c.className=on?'gr-card gr-on':'gr-card';}
    var s=document.getElementById('gr-state-'+key); if(s){s.textContent=on?{$L["enabled"]}:{$L["disabled"]};}
}
window.GrCat=function(cat){
    GR.cat=cat;
    var a=document.querySelectorAll('#gr-f-cat a');
    for(var i=0;i<a.length;i++){
        var m=(a[i].getAttribute('onclick')||'').indexOf(\"'\"+cat+\"'\")>-1;
        a[i].className=m?'siem-tinyf-s on':'siem-tinyf-s';
    }
    grApply();
};
window.GrSearch=function(v){ GR.q=String(v||'').toLowerCase().trim(); grApply(); };
$(document).off('input.gr').on('input.gr','#gr-f-search',function(){ window.GrSearch(this.value); });
window.GrToggle=function(el,key){
    var on=el.checked;
    el.disabled=true;
    $.post('$page',{'gr-toggle':key,'value':on?1:0},function(resp){
        el.disabled=false;
        resp=String(resp||'').trim();
        if(resp.length>0){ el.checked=!on; alert(resp); return; }
        grMark(key,on); grKpis();
        $jsCompile
    }).fail(function(){ el.disabled=false; el.checked=!on; });
};
})();
</script>";
}

function gr_toggle(){
    $tpl=new template_admin();
    if(!gr_is_admin()){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return;}
    if(gr_globally_disabled()){echo $tpl->javascript_parse_text("{aclgbdisabled}");return;}
    $key=(string)$_POST["gr-toggle"];
    $rules=gr_rules();
    if(!isset($rules[$key])){echo "Unknown rule";return;}
    $value=intval($_POST["value"])==1?1:0;
    $before=gr_value($key);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($key,$value);
    if($key=="AllowSquidWhitelistsSSL" && $before<>$value){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ssl/build");
    }
    admin_tracks("Proxy global rules: $key set to $value");
}

function gr_css():string{
    if(isset($GLOBALS["GR_CSS"])){return "";}
    $GLOBALS["GR_CSS"]=true;
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488","accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){$ac=brand_ui_accents(brand_palette());}
    $a=$ac["accent"];$ad=$ac["accent_dark"];$as=$ac["accent_soft"];$al=$ac["accent_line"];
    return "<style>
.gr-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin:10px 0 20px}
.gr-kpi{display:flex;align-items:center;gap:12px;padding:14px 16px}
.gr-kpi-ico{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex:0 0 auto}
.gr-kpi-l{font-size:12px;font-weight:500;color:#64748b}
.gr-kpi-v{font-size:18px;font-weight:700;color:#1e293b;line-height:1.3}
.gr-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px}
.gr-card{display:flex;flex-direction:column;background:#fff;border:2px solid #e2e8f0;border-radius:16px;padding:18px 20px;transition:box-shadow .15s,border-color .15s}
.gr-card:hover{box-shadow:0 4px 14px rgba(15,23,42,.07)}
.gr-card.gr-on{border-color:$al;background:linear-gradient(180deg,$as 0,#fff 90px)}
.gr-top{display:flex;align-items:flex-start;gap:12px}
.gr-ico{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;flex:0 0 auto}
.gr-head{flex:1 1 auto;min-width:0}
.gr-tag{display:inline-block;padding:2px 8px;border-radius:5px;font-size:10px;font-weight:600;letter-spacing:.06em;text-transform:uppercase}
.gr-title{font-size:14px;font-weight:600;color:#0f172a;margin:4px 0 0;line-height:1.35}
.gr-desc{font-size:13px;color:#475569;line-height:1.55;margin:12px 0 14px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.gr-card:hover .gr-desc{-webkit-line-clamp:unset;overflow:visible}
.gr-foot{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:auto;border-top:1px solid #f1f5f9;padding-top:10px}
.gr-key{font-family:'JetBrains Mono',Consolas,monospace;font-size:11px;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.gr-state{font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#94a3b8;white-space:nowrap}
.gr-on .gr-state{color:$ad}
.gr-on .gr-state:before{content:'';display:inline-block;width:7px;height:7px;border-radius:50%;background:$a;margin-right:6px;vertical-align:1px}
.gr-sw{position:relative;display:inline-block;width:44px;height:24px;flex:0 0 auto;margin:0;cursor:pointer}
.gr-sw input{opacity:0;width:0;height:0;position:absolute}
.gr-sw span{position:absolute;inset:0;background:#e2e8f0;border-radius:24px;transition:background .15s}
.gr-sw span:before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:transform .15s}
.gr-sw input:checked+span{background:$a}
.gr-sw input:checked+span:before{transform:translateX(20px)}
.gr-sw input:disabled+span{opacity:.5;cursor:not-allowed}
.gr-empty{padding:40px;text-align:center;color:#64748b;font-size:14px}
.gr-t-blue{background:#eff6ff;color:#1d4ed8}.gr-t-slate{background:#f1f5f9;color:#334155}
.gr-t-green{background:#ecfdf5;color:#047857}.gr-t-sky{background:#f0f9ff;color:#0369a1}
.gr-t-indigo{background:#e6eeed;color:#005447}.gr-t-orange{background:#fff7ed;color:#c2410c}
.gr-t-red{background:#fef2f2;color:#dc2626}.gr-t-teal{background:#f0fdfa;color:#0f766e}
.gr-t-amber{background:#fffbeb;color:#b45309}
.gr-on .gr-ico{background:$ad;color:#fff}
</style>";
}
