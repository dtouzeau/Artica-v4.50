<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();

if(!isReader($users)){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    exit();
}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.proxypac.meta.inc");
include_once(dirname(__FILE__)."/ressources/class.proxyacls.meta.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

foreach ($_POST as $key=>$value){
    writelogs("$key = > $value","-",__FILE__,__LINE__);
}
// Pentest 2026-11-04 : TableLink/hacluster/ruleid/delete-confirm/zmd5 etaient recopies sans encodage dans la page (XSS).
foreach(array("TableLink") as $xssk){
    if(isset($_GET[$xssk])){$_GET[$xssk]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET[$xssk]);}
    if(isset($_POST[$xssk])){$_POST[$xssk]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_POST[$xssk]);}
}
foreach(array("hacluster","ruleid","delete-confirm") as $xssk){if(isset($_GET[$xssk])){$_GET[$xssk]=intval($_GET[$xssk]);}}
foreach(array("delete-js","mkey","popup-order-js","enabled-link-js","negation-js") as $xssk){if(isset($_GET[$xssk])){$_GET[$xssk]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET[$xssk]);}}
// Politique Meta : liens des regles PAC tenus ("rules") et objets proxy tenus (metaid).
pxao_meta_guard();
pxao_write_guard();
if(isset($_GET["pxao-new-js"])){pxao_new_js();exit;}
if(isset($_GET["pxao-link-js"])){pxao_link_js();exit;}
if(isset($_GET["pxao-group-js"])){pxao_group_js();exit;}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_POST["object-group-save"])){save_object_group();exit;}
if(isset($_POST["object-save"])){save_object();exit;}
if(isset($_GET["search-table"])){search_table();exit;}
if(isset($_GET["new-object"])){new_object();exit;}
if(isset($_GET["new-object-js"])){new_object_js();exit;}
if(isset($_GET["new-object-group"])){new_object_group();exit;}

if(isset($_GET["link-object"])){link_object();exit;}
if(isset($_POST["object-link"])){save_link_object();exit;}
if(isset($_GET["enabled-link-js"])){enable_link_object();exit;}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["delete-confirm"])){delete_confirm();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["move-js"])){move_js();exit;}
if(isset($_GET["negation-js"])){negation_js();exit;}
if(isset($_GET["popup-order-js"])){popup_order_js();exit;}
if(isset($_POST["zorder"])){popup_order_save();exit;}
if(isset($_GET["fill-orders"])){fill_orders();exit;}
build_page();




function top_buttons():bool{
    $users=new usersMenus();
    if(!isWritter($users)){return false;}
    if(pxao_pac_locked()){
        echo proxypac_meta_banner(new template_admin(),proxypac_meta_rules());
        return false;
    }
    if(pxao_acl_rule_meta(intval($_GET["top-buttons"]))){
        // Regle d'une politique Artica Meta "ACL proxy" : liens en lecture seule.
        echo proxyacls_meta_rule_banner(new template_admin());
        return false;
    }
    $ID=intval($_GET["top-buttons"]);
    $_GET["ID"]=$ID;
    $Suffix=build_suffix();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ProxyPac=intval($_GET["ProxyPac"] ?? 0);
    $fastacls=intval($_GET["fastacls"] ?? 0);
    $AS_PROXY=!($ProxyPac==1 || $fastacls==1);

    $f=array();
    $f[]="<div class='pxao-tools'>";
    $f[]="<button type='button' class='ak-btn ak-btn-primary' title='{pxao_new_help}' OnClick=\"Loadjs('$page?pxao-new-js=$ID&$Suffix')\"><i class='fa-solid fa-plus'></i> {new_object}</button>";
    $f[]="<button type='button' class='ak-btn' title='{pxao_link_help}' OnClick=\"Loadjs('$page?pxao-link-js=$ID&$Suffix')\"><i class='fa-solid fa-link'></i> {link_object}</button>";
    if($AS_PROXY){
        $f[]="<button type='button' class='ak-btn' title='{pxao_group_help}' OnClick=\"Loadjs('$page?pxao-group-js=$ID&$Suffix')\"><i class='fa-solid fa-layer-group'></i> {new_group_of_objects}</button>";
    }
    $f[]="</div>";
    echo $tpl->_ENGINE_parse_body(implode("",$f));
    return true;
}
function build_page()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $ID = intval($_GET["rule-id"]);
    if ($ID == 0) {
        return false;
    }
    foreach(array("direction"=>null,"fastacls"=>0,"RefreshTable"=>null,"ProxyPac"=>0,"IDS"=>0,"hacluster"=>0) as $k=>$v){
        if(!isset($_GET[$k])){$_GET[$k]=$v;}
    }
    $suffix = build_suffix();
    $html=array();
    $html[]="<div class='pxao'>";
    $html[]=pxao_intro();
    $html[]="<div id='pxao-sum-$ID'></div>";
    $html[]="<div id='div-proxy-object-id'></div>";
    $html[]="<div id='div-proxy-button-$ID'></div>";
    $html[]="<div id='fw-objects-table'></div>";
    $html[]="<div style='margin-top:10px'>".$tpl->search_block($page, null, null, null, "&search-table=yes&ID=$ID&$suffix")."</div>";
    $html[]="</div>";
    echo pxao_style().$tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}
function new_object_js(){
    if(!isset($_GET["firewall"])){$_GET["firewall"]=0;}
    $ID=intval($_GET["rule-id"]);
    $direction=intval($_GET["direction"]);
    $TableLink=$_GET["TableLink"];
    $RefreshTable=jsafter_token($_GET["RefreshTable"]);
    $ProxyPac=intval($_GET["ProxyPac"]);
    $FireWall=intval($_GET["firewall"]);
    $fastacls=intval($_GET["fastacls"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog3("{new_object}","$page?new-object=yes&ID=$ID&firewall=$FireWall&direction=$direction&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&Dialog=3&fastacls=$fastacls");


}

function delete_js(){
    $TableLink    = $_GET["TableLink"] ?? "webfilters_sqacllinks";
    $RefreshTable = jsafter_token($_GET["RefreshTable"]);
    $backjs       = "LoadAjax('table-acls-rules','fw.proxy.acls.php?table=yes&TableLink=$TableLink');";
    if($RefreshTable){ $backjs = jsafter_sanitize(base64_decode($RefreshTable)).";$backjs"; }
    header("content-type: application/x-javascript");
    $mkey = $_GET["delete-js"];
    list($ok,$data) = acls_api_send("DELETE",
        "/proxy/acls/sqlite/link?table=".urlencode($TableLink)."&zmd5=".urlencode($mkey));
    if(!$ok){
        $tpl = new template_admin();
        echo "alert('".$tpl->javascript_parse_text($data)."');";
        return;
    }
    admin_tracks("Unlink ACL object link $mkey ($TableLink)");
    echo "$('#$mkey').remove();";
    echo $backjs;
    $fn = pxao_function();
    if($fn!=""){ echo "if(typeof $fn=='function'){ $fn(); }"; }
}

function move_js(){
    $TableLink = $_GET["TableLink"] ?? "webfilters_sqacllinks";
    $mkey      = $_GET["mkey"];
    $direction = $_GET["move-js"];
    $aclid     = intval($_GET["aclid"]);
    $dir       = ($direction === "up") ? 1 : 0;
    list($ok,$data) = acls_api_send("PUT",
        "/proxy/acls/sqlite/link/move?table=".urlencode($TableLink)."&zmd5=".urlencode($mkey),
        ["dir"=>$dir]);
    if(!$ok){
        $tpl = new template_admin();
        echo $tpl->js_error($data);
        return;
    }
    $lib = new lib_memcached();
    $lib->Delkey("DNSFWOBJS");
    $page = CurrentPageName();
    echo "Loadjs('fw.proxy.acls.php?fill=$aclid');\n";
    echo "Loadjs('$page?fill-orders=$TableLink&aclid=$aclid');\n";
    // La refonte trie les lignes par ordre et grise Monter/Descendre aux extremites : on recharge.
    $fn = pxao_function();
    if($fn!=""){ echo "if(typeof $fn=='function'){ $fn(); }\n"; }
}
function popup_order_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $TableLink = $_GET["TableLink"] ?? "webfilters_sqacllinks";
    $zkey   = $_GET["popup-order-js"];
    $aclid  = intval($_GET["aclid"]);
    $link   = acls_api_get("/proxy/acls/sqlite/link?table=".urlencode($TableLink)."&zmd5=".urlencode($zkey));
    $zOrder = $link ? intval($link["xOrder"]) : 0;
    echo $tpl->js_prompt("{order}",null,"fa-solid fa-arrow-up-1-9",$page,
        "zorder","Loadjs('$page?fill-orders=$TableLink&aclid=$aclid')",$zOrder,"$zkey:$TableLink:$aclid");
    return true;
}
function popup_order_save():bool{
    $tb = explode(":", $_POST["KeyID"]);
    $zkey = $tb[0];
    $TableLink = $tb[1];
    $aclid = intval($tb[2]);
    $newvalue = intval($_POST["zorder"]);
    list($ok,$data) = acls_api_send("PUT",
        "/proxy/acls/sqlite/link/order?table=".urlencode($TableLink)."&zmd5=".urlencode($zkey)."&aclid=$aclid",
        ["xOrder"=>$newvalue]);
    if(!$ok){ echo $data; return false; }
    return true;
}

function negation_js(){
    $function  = pxao_function();
    $TableLink = $_GET["TableLink"] ?? "webfilters_sqacllinks";
    header("content-type: application/x-javascript");
    $mkey = $_GET["negation-js"];
    list($ok,$data) = acls_api_send("PUT",
        "/proxy/acls/sqlite/link/negation?table=".urlencode($TableLink)."&zmd5=".urlencode($mkey));
    if(!$ok){
        $tpl = new template_admin();
        echo "alert('".$tpl->javascript_parse_text($data)."');";
        return;
    }
    $ID = intval($data["aclid"] ?? 0);
    $lib = new lib_memcached();
    $lib->Delkey("DNSFWOBJS");
    echo "Loadjs('fw.proxy.acls.php?fill=$ID');\n";
    if($function){ echo "$function();\n"; }
    echo "LoadAjax('table-acls-rules','fw.proxy.acls.php?table=yes');\n";
    echo "Loadjs('fw.proxy.acls.bugs.php?refresh=yes');\n";
}

function delete_confirm(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$groupid=$_GET["delete-confirm"];
	$jsAfter=jsafter_sanitize(base64_decode($_GET["js-after"]));
	$t=time();
	$group_unlink_delete_explain=$tpl->_ENGINE_parse_body("{group_unlink_delete_explain}");
	$g=acls_api_get("/proxy/acls/sqlite/group/".intval($groupid));
	$GroupName=is_array($g) ? ($g["groupName"] ?? "") : "";
	$group_unlink_delete_explain=str_replace('%GPNAME', $GroupName, $group_unlink_delete_explain);
	$html="<div class=row>
		
		<div class=\"alert alert-danger\">$group_unlink_delete_explain</div>
		
		<div style='display:flex;flex-wrap:wrap;gap:10px;justify-content:center'>
			<button class='btn btn-danger btn-lg' type='button' OnClick=\"Remove$t()\">{delete}</button>
			<button class='btn btn-danger btn-lg' type='button' OnClick=\"Disconnect$t()\">{unlink}</button>
		</div>
		</div>
<script>
		
		var xPost$t= function (obj) {
			var res=obj.responseText;
			if(res.length>3){alert(res);return;}
			DialogConfirm.close();
			$jsAfter
			Loadjs('fw.proxy.acls.php?fill={$_GET["ruleid"]}');
		}
		function Disconnect$t(){
			var XHR = new XHRConnection();
		    XHR.appendData('delete-unlink', '$groupid');
		    XHR.appendData('ruleid', '{$_GET["ruleid"]}');
		    XHR.sendAndLoad('$page', 'POST',xPost$t);  			
		}
		function Remove$t(){
			var XHR = new XHRConnection();
		    XHR.appendData('delete-remove', '$groupid');
		    XHR.appendData('ruleid', '{$_GET["ruleid"]}');
		    XHR.sendAndLoad('$page', 'POST',xPost$t);  			
		}		
</script>	
";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}


function delete_remove(){
	$gpid=$_POST["delete-remove"];
	$acls=new squid_acls();
	$acls->delete_group($gpid);
	$lib=new lib_memcached();
    $lib->Delkey("DNSFWOBJS");
}
function enabled_js(){
    $TableLink    = $_GET["TableLink"] ?? "webfilters_sqacllinks";
    $RefreshTable = jsafter_token($_GET["RefreshTable"]);
    $fastacls     = intval($_GET["fastacls"]);
    $backjs = "LoadAjax('table-acls-rules','fw.proxy.acls.php?table=yes&TableLink=$TableLink&fastacls=$fastacls');";
    if($RefreshTable){ $backjs = jsafter_sanitize(base64_decode($RefreshTable)).";$backjs"; }
    header("content-type: application/x-javascript");
    $gpid = intval($_GET["enabled-js"]);

    $g = acls_api_get("/proxy/acls/sqlite/group/$gpid");
    if(!$g){ echo "alert('group $gpid not found');"; return false; }
    $new = intval($g["enabled"])==1 ? 0 : 1;
    list($ok,$data) = acls_api_send("PUT", "/proxy/acls/sqlite/group/$gpid/enable", ["enabled"=>$new]);
    if(!$ok){ echo "alert('".addslashes($data)."');"; return false; }

    echo "$backjs\n";
    admin_tracks("Set ACL group ".$g["groupName"]." to enable: $new");
    $lib = new lib_memcached(); $lib->Delkey("DNSFWOBJS");
    return true;
}

function enable_link_object(){
    $tpl       = new template_admin();
    $zmd5      = $_GET["enabled-link-js"];
    $TableLink = $_GET["TableLink"] ?? "webfilters_sqacllinks";
    $link = acls_api_get("/proxy/acls/sqlite/link?table=".urlencode($TableLink)."&zmd5=".urlencode($zmd5));
    if(!$link){ echo $tpl->js_error("link not found"); return false; }
    $new = intval($link["enabled"])==1 ? 0 : 1;
    list($ok,$data) = acls_api_send("PUT",
        "/proxy/acls/sqlite/link/enable?table=".urlencode($TableLink)."&zmd5=".urlencode($zmd5),
        ["enabled"=>$new]);
    if(!$ok){ echo $tpl->js_error($data); return false; }
    return true;
}


function link_object(){
    $page     = CurrentPageName();
    $tpl      = new template_admin();
    $ProxyPac = intval($_GET["ProxyPac"] ?? 0);
    $qProxy   = new mysql_squid_builder(true);
    $TableLink= $_GET["TableLink"] ?? "webfilters_sqacllinks";
    if($TableLink==""){$TableLink="webfilters_sqacllinks";}
    $DnsDist  = intval($_GET["DnsDist"] ?? 0);
    if($TableLink=="dnsdist_sqacllinks"){$DnsDist=1;}
    if($TableLink=="wpad_sources_link"){$ProxyPac=1;}
    $ID = intval($_GET["ID"]);
    if($ID==0){echo $tpl->_ENGINE_parse_body($tpl->div_error("{pxao_no_rule}"));return;}

    $groups = acls_api_get("/proxy/acls/sqlite/groups/all");
    if(!is_array($groups)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{pxao_groups_unavailable}"));
        return;
    }
    // Objets deja lies a la regle : affiches, mais non selectionnables.
    $linked=array();
    $links=acls_api_get("/proxy/acls/sqlite/links?table=".urlencode($TableLink)."&aclid=$ID&search=&pacpxy=0");
    if(is_array($links)){foreach($links as $l){$linked[intval($l["groupID"] ?? 0)]=true;}}

    // Libelles des types : la meme table que table_object() (colonne Type de l'onglet).
    $labels=array();
    foreach(array("acl_GroupType","acl_GroupType_DNSFW","acl_GroupType_DNSDIST") as $prop){
        if(!isset($qProxy->$prop) || !is_array($qProxy->$prop)){continue;}
        foreach($qProxy->$prop as $k=>$v){$labels[$k]=$v;}          // le dernier gagne, comme table_object()
    }
    if(isset($qProxy->acl_GroupType_SMTP) && is_array($qProxy->acl_GroupType_SMTP)){
        foreach($qProxy->acl_GroupType_SMTP as $k=>$v){if(!isset($labels[$k])){$labels[$k]=$v;}}
    }
    if($DnsDist==0){$labels["dst"]="{dst}";}
    $labels["geoip"]="{geoip_location}";
    $byType=array();$seen=array();
    foreach($groups as $g){
        $gid=intval($g["id"] ?? 0);
        if($gid==0 || isset($seen[$gid])){continue;}   // /groups/all peut servir un id plusieurs fois
        $seen[$gid]=true;
        $gtype=(string)($g["groupType"] ?? "");
        if($ProxyPac==1 && !isset($qProxy->acl_GroupType_WPAD[$gtype])){continue;}
        if($DnsDist==1 && !isset($qProxy->acl_GroupType_DNSDIST[$gtype])){continue;}
        $tlabel=$tpl->_ENGINE_parse_body(isset($labels[$gtype])?$labels[$gtype]:$qProxy->GroupTypeToString($gtype));
        if($tlabel=="" || $tlabel=="{".$gtype."}"){$tlabel=$gtype;}
        $byType[$tlabel][]=array("id"=>$gid,"name"=>$tpl->utf8_encode((string)($g["groupName"] ?? "")),
            "type"=>$gtype,"linked"=>isset($linked[$gid]));
    }
    ksort($byType,SORT_NATURAL|SORT_FLAG_CASE);

    $t=time();
    $after=implode(";",pxao_after_js());
    $html=array();
    $html[]="<div class='pxao'>";
    $html[]=$tpl->div_explain("{link_object}||{pxao_link_explain}");
    $html[]="<label class='ak-search' style='width:100%'><i class='fas fa-magnifying-glass'></i><input type='text' id='pxao-pick-q-$t' placeholder='{pxao_search_objects}' aria-label='{pxao_search_objects}' OnInput=\"PxaoPickFilter$t()\"></label>";
    $html[]="<div class='pxao-pick-list' id='pxao-pick-$t' role='listbox'>";
    $n=0;
    foreach($byType as $tlabel=>$items){
        usort($items,function($a,$b){return strcasecmp($a["name"],$b["name"]);});
        $html[]="<div class='pxao-pick-grp' data-grp='1'>".htmlspecialchars($tlabel)."</div>";
        foreach($items as $it){
            $ico=isset($qProxy->acl_GroupTypeIcon[$it["type"]])?$qProxy->acl_GroupTypeIcon[$it["type"]]:"fas fa-cube";
            $nm=htmlspecialchars($it["name"]);
            $q=htmlspecialchars(strtolower($it["name"]." ".$tlabel),ENT_QUOTES);
            if($it["linked"]){
                $html[]="<div class='pxao-pick-it dis' data-q=\"$q\" aria-disabled='true'><i class='$ico'></i><span>$nm</span><span class='ak-note'>{pxao_already_linked}</span></div>";
            }else{
                $html[]="<div class='pxao-pick-it' role='option' tabindex='0' data-q=\"$q\" data-id='{$it["id"]}' OnClick=\"PxaoPick$t(this)\"><i class='$ico'></i><span>$nm</span></div>";
            }
            $n++;
        }
    }
    if($n==0){$html[]="<div class='ak-empty'>{pxao_no_object_to_link}</div>";}
    $html[]="</div>";
    $html[]="<div id='pxao-pick-err-$t' style='margin-top:8px'></div>";
    $html[]="<div class='ak-actions' style='margin-top:12px;justify-content:flex-end'><button type='button' class='ak-btn ak-btn-primary' id='pxao-pick-go-$t' disabled OnClick=\"PxaoPickGo$t()\"><i class='fa-solid fa-link'></i> {link_object}</button></div>";
    $html[]="</div>";

    $tl=htmlspecialchars($TableLink,ENT_QUOTES);
    $js="<script>
var PxaoSel$t=0;
function PxaoPick$t(el){
  var l=document.getElementById('pxao-pick-$t').querySelectorAll('.pxao-pick-it.sel');
  for(var i=0;i<l.length;i++){l[i].className=l[i].className.replace(' sel','');l[i].setAttribute('aria-selected','false');}
  el.className+=' sel';el.setAttribute('aria-selected','true');
  PxaoSel$t=parseInt(el.getAttribute('data-id'),10)||0;
  document.getElementById('pxao-pick-go-$t').disabled=(PxaoSel$t==0);
}
function PxaoPickFilter$t(){
  var v=(document.getElementById('pxao-pick-q-$t').value||'').toLowerCase();
  var box=document.getElementById('pxao-pick-$t'),c=box.children,grp=null,shown=0;
  for(var i=0;i<c.length;i++){
    var e=c[i];
    if(e.getAttribute('data-grp')){ if(grp){grp.style.display=shown?'':'none';} grp=e;shown=0;continue; }
    var q=e.getAttribute('data-q');if(q===null){continue;}
    var ok=(v===''||q.indexOf(v)>=0);e.style.display=ok?'':'none';if(ok){shown++;}
  }
  if(grp){grp.style.display=shown?'':'none';}
}
var xPxaoPickGo$t=function(obj){
  var r=obj.responseText||'';
  if(r.length>3){document.getElementById('pxao-pick-err-$t').innerHTML='<div class=\"ak-banner ak-err\"><span class=\"ak-banner-m\"></span></div>';
    document.getElementById('pxao-pick-err-$t').querySelector('.ak-banner-m').textContent=r.replace(/^jserror:/,'');return;}
  $after
};
function PxaoPickGo$t(){
  if(PxaoSel$t==0){return;}
  var XHR=new XHRConnection();
  XHR.appendData('object-link','$ID');XHR.appendData('gpid',PxaoSel$t);XHR.appendData('TableLink','$tl');
  XHR.sendAndLoad('$page','POST',xPxaoPickGo$t);
}
</script>";
    echo pxao_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).$js;
}
function new_object_group():bool{
    $TableLink = $_GET["TableLink"] ?? "";
    if($TableLink==""){$TableLink="webfilters_sqacllinks";}
    $ID=intval($_GET["ID"]);
    $tpl=new template_admin();
    $tpl->field_hidden("object-group-save", $ID);
    $tpl->field_hidden("TableLink", $TableLink);
    $form[]=$tpl->field_text("GroupName","{groupname}","",true,"{pxao_group_name_help}");
    $html=array();
    $html[]=$tpl->div_explain("{new_group_of_objects}||{pxao_group_explain}");
    $html[]=$tpl->form_outside("",@implode("\n", $form),null,"{add}",implode(";",pxao_after_js()));
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function new_object():bool{
	$page=CurrentPageName();
    $TableLink="";
    $RefreshFunction="";
	$RefreshTable   = jsafter_token($_GET["RefreshTable"]);
    $backjs         = null;
    if(isset($_GET["TableLink"])) {
        $TableLink = $_GET["TableLink"];
    }
    $function="";
    $Dialog=0;
    $ID=intval($_GET["ID"]);
    if(isset($_GET["RefreshFunction"])) {
        // Pentest 2026-11-04 : RefreshFunction etait recopie sans validation dans la page (XSS).
        $RefreshFunction = jsafter_sanitize(base64_decode($_GET["RefreshFunction"]));
    }
    if(isset($_GET["function"])){$function=$_GET["function"];}
    if(isset($_GET["Dialog"])){$Dialog=intval($_GET["Dialog"]);}

    if($TableLink==null){$TableLink="webfilters_sqacllinks";}
    VERBOSE("TableLink = [$TableLink]",__LINE__);

    if(preg_match("#dnsdist_sqacllinks#i",$TableLink)){
        VERBOSE("DnsDist = [1]",__LINE__);
        $TableLink="dnsdist_sqacllinks";
        $_GET["DnsDist"]=1;
    }
    if($TableLink=="dnsfw_acls_link"){
        $_GET["dnsfw"]=1;
    }
    if(!isset($_GET["dnsfw"])){$dnsfw=0;}
    $jsAfter=array();
    if($Dialog>0){
        $jsAfter[]="dialogInstance$Dialog.close();";
    }
    if(strlen($function)>3){
        $jsAfter[]="$function()";
    }
    if(strlen($RefreshFunction)>3){
        $jsAfter[]="$RefreshFunction";
    }
    if(strlen($RefreshTable)>3){
        $jsAfter[]=jsafter_sanitize(base64_decode($RefreshTable));
    }
    $jsAfter[]="if( document.getElementById('fw-objects-table') ){ document.getElementById('fw-objects-table').innerHTML='';}";

	$tpl=new template_admin();
	$title="{new_object}";
	$btname="{add}";
    $backjs="document.getElementById('fw-objects-table').innerHTML=''";

	
	$tpl->field_hidden("object-save", $ID);
	$tpl->field_hidden("TableLink", $TableLink);
	$form[]=$tpl->field_text("GroupName","{object_name}","",true,"{pxao_object_name_help}");
	$form[]=$tpl->field_acls_groups("GroupType","{type}",null,true,"{pxao_object_type_help}");
    $jsAfter=pxao_after_js();
    if($Dialog==0){$jsAfter[]="if( document.getElementById('fw-objects-table') ){ document.getElementById('fw-objects-table').innerHTML='';}";}
	$html=$tpl->_ENGINE_parse_body($tpl->div_explain("$title||{pxao_new_explain}"))
        .$tpl->form_outside("",$form,null,$btname,@implode(";",$jsAfter));
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__." bytes: ".strlen($html)."<br>\n";}
	echo $html;
    return true;
}
function save_link_object(){
    $gpid      = intval($_POST["gpid"]);
    $aclid     = intval($_POST["object-link"]);
    $TableLink = $_POST["TableLink"] ?? "webfilters_sqacllinks";
    list($ok,$data) = acls_api_send("POST", "/proxy/acls/sqlite/link", [
        "table"=>$TableLink, "aclid"=>$aclid, "gpid"=>$gpid,
    ]);
    if(!$ok){ echo (new template_admin())->post_error($data); return false; }
    admin_tracks("Link ACL object $gpid to rule $aclid ($TableLink)");
    $lib = new lib_memcached();
    $lib->Delkey("DNSFWOBJS");
    return true;
}

function save_object_group(){
    $ID        = intval($_POST["object-group-save"]);
    $tpl       = new template_admin();
    $TableLink = $_POST["TableLink"] ?? "webfilters_sqacllinks";
    // Same UTF-8 trap as save_object() — utf8_decode would corrupt the
    // payload and json_encode would return false, sending an empty body.
    $GroupName = url_decode_special_tool($_POST["GroupName"]);
    $payload = ["name"=>$GroupName, "type"=>"AclsGroup"];
    if($ID > 0){ $payload["linkAclid"] = $ID; $payload["linkTable"] = $TableLink; }
    list($ok,$data) = acls_api_send("POST", "/proxy/acls/sqlite/group", $payload);
    if(!$ok){ echo $data; return false; }
    admin_tracks("Create a new ACL group $GroupName (".intval($data["id"]).") type: AclsGroup ");
    $lib = new lib_memcached(); $lib->Delkey("DNSFWOBJS");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}

function save_object(){
    include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
    $ID        = intval($_POST["object-save"]);
    $tpl       = new template_admin();
    $TableLink = $_POST["TableLink"] ?? "webfilters_sqacllinks";
    // No utf8_decode here: it converts to ISO-8859-1, which then breaks
    // json_encode() (which only accepts valid UTF-8) — silently producing an
    // empty POST body and the "invalid json: unexpected end of JSON input"
    // error on the Go side. Browsers submit form data as UTF-8; articarest
    // expects UTF-8; pass it through untouched.
    $GroupName = url_decode_special_tool(trim($_POST["GroupName"]));
    $GroupType = $_POST["GroupType"];

    $payload = ["name"=>$GroupName, "type"=>$GroupType];
    if($ID > 0){ $payload["linkAclid"] = $ID; $payload["linkTable"] = $TableLink; }

    if(($GroupType=="src" || $GroupType=="dst")){
        $IP = new IP();
        if($IP->isIPAddressOrRange($GroupName)){
            $payload["ipItem"] = $GroupName;
        }
    }

    list($ok,$data) = acls_api_send("POST", "/proxy/acls/sqlite/group", $payload);
    if(!$ok){ echo $data; return false; }
    admin_tracks("Create a new ACL group $GroupName (".intval($data["id"]).") type: $GroupType ");
    $lib = new lib_memcached(); $lib->Delkey("DNSFWOBJS");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}

function build_suffix():string{

    $Blacklist=array("table"=>true,"top-buttons"=>true,"search-table"=>true,"jQueryLjs"=>true,"_"=>true,"search"=>true,"DnsDist"=>true);
    if(!isset($_GET["DnsDist"])){$_GET["DnsDist"]=0;}

    $f=array();
    foreach ($_GET as $key=>$val){
        if(isset($Blacklist[$key])) {continue;}
        // Pentest 2026-11-04 : les cles/valeurs de $_GET etaient recopiees sans encodage dans la page (XSS).
        $f[]=req_url($key)."=".req_url($val);
    }
    return @implode("&",$f);
}

function search_table():bool{
    foreach(array("ProxyPac"=>0,"firewall"=>0,"RefreshTable"=>null,"IDS"=>0) as $k=>$v){
        if(!isset($_GET[$k])){$_GET[$k]=$v;}
    }
    $tpl          = new template_admin();
    $page         = CurrentPageName();
    $DnsDist      = intval($_GET["DnsDist"] ?? 0);
    $IDS          = intval($_GET["IDS"]);
    $TableLink    = $_GET["TableLink"] ?? "webfilters_sqacllinks";
    if($TableLink==""){$TableLink="webfilters_sqacllinks";}
    $ID           = intval($_GET["ID"]);
    $function     = pxao_function();
    $firewall     = intval($_GET["firewall"]);
    $RefreshTable = jsafter_token($_GET["RefreshTable"]);
    $ProxyPac     = intval($_GET["ProxyPac"]);
    $fastacls     = intval($_GET["fastacls"] ?? 0);
    // Pentest 2026-11-04 : RefreshFunction etait recopie sans validation dans la page (XSS).
    $RefreshFunctionGet = isset($_GET["RefreshFunction"]) ? jsafter_sanitize(base64_decode($_GET["RefreshFunction"])) : "";
    $RefreshFunction    = trim(($function!=""?"$function();":"").$RefreshFunctionGet);

    // Le moteur Go entoure le fragment de %..% : on envoie le texte nu.
    $search=str_replace("*","",trim((string)($_GET["search"] ?? "")));

    $m=pxao_model($ID,$TableLink,$search);
    if(!$m["ok"]){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{pxao_links_unavailable}"));
        return false;
    }

    // jsAfter des fiches objet (fw.rules.items.php) : rafraichit la liste des regles et cet onglet.
    $RefreshTabledeced = jsafter_sanitize(base64_decode((string)$_GET["RefreshTable"]));
    $jsAfter = base64_encode("$RefreshTabledeced;LoadAjax('table-acls-rules','fw.proxy.acls.php?table=yes&TableLink=$TableLink');$RefreshFunction");

    $ctxBase=array(
        "page"=>$page,"ID"=>$ID,"TableLink"=>$TableLink,"function"=>$function,"jsAfter"=>$jsAfter,
        "ProxyPac"=>$ProxyPac,"fastacls"=>$fastacls,"suffix"=>build_suffix(),
        "q"=>"&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&firewall=$firewall&fastacls=$fastacls",
        "locked"=>$m["locked"],"writer"=>$m["writer"],
    );

    $empty="{no_data}";
    if($search!=""){$empty="{pxao_no_match}";}
    elseif($TableLink=="wpad_white_link"){$empty="{bypass_proxy_internal}<br><i>{bypass_proxy2}</i>";}

    $html=array();
    $html[]="<div id='pxao-sum-src-$ID' style='display:none'>".pxao_summary($m,$search!="")."</div>";
    $html[]=pxao_table($m,$ctxBase,$empty);

    // Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    $js=array();
    if(isset($_GET["acl-build"])){$js[]="Loadjs('fw.proxy.acls.php?fill=$ID');";}
    $js[]="(function(){var s=document.getElementById('pxao-sum-src-$ID'),d=document.getElementById('pxao-sum-$ID');"
        ."if(s&&d){d.innerHTML=s.innerHTML;}if(s&&s.parentNode){s.parentNode.removeChild(s);}})();";
    $js[]="LoadAjaxSilent('div-proxy-button-$ID','$page?top-buttons=$ID&function=$function&{$ctxBase["suffix"]}');";
    $js[]="NoSpinner();";

    echo awc_table_style().pxao_style()
        .$tpl->_ENGINE_parse_body(implode("\n",$html))
        .pxao_js($tpl)
        ."<script>".implode("\n",$js)."</script>"
        .awc_pager_js(50,"pxao-rows","pxao-pager");
    return true;
}
function fill_orders():bool{
    $aclid     = intval($_GET["aclid"]);
    $TableLink = $_GET["fill-orders"];
    $orders    = acls_api_get("/proxy/acls/sqlite/links/orders?table=".urlencode($TableLink)."&aclid=$aclid");
    if(!is_array($orders)){ return false; }
    $f = [];
    foreach($orders as $row){
        $zmd5 = $row["mkey"];
        $zo   = intval($row["xOrder"]);
        $f[]  = "if(document.getElementById('groupacls-order-$zmd5')){document.getElementById('groupacls-order-$zmd5').innerHTML='$zo';}";
    }
    echo @implode("\n", $f);
    return true;
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

// ---------------------------------------------------------------------------------------
// Politique Meta (class.proxypac.meta.inc). Le demon refuse deja (423) ; ces gardes evitent
// l'appel et affichent un message traduit au lieu du texte brut.
// ---------------------------------------------------------------------------------------
function pxao_meta_table():string{
    $t=(string)($_GET["TableLink"] ?? ($_POST["TableLink"] ?? ""));
    if(isset($_POST["KeyID"])){
        $tb=explode(":",(string)$_POST["KeyID"]);
        if(isset($tb[1])){$t=$tb[1];}
    }
    return preg_replace('/[^A-Za-z0-9_]/','',$t);
}
// Table de liens d'une regle PAC dont les regles sont tenues par une politique Meta.
function pxao_pac_locked($table=null):bool{
    if($table===null){$table=pxao_meta_table();}
    if(!in_array($table,array("wpad_sources_link","wpad_white_link","wpad_black_link"),true)){return false;}
    return proxypac_meta_rules_held();
}
// Regle d'ACL proxy venue d'une politique Artica Meta (table de liens des regles proxy seulement).
function pxao_acl_rule_meta($id):bool{
    $t=pxao_meta_table();
    if($t!=="" && $t!=="webfilters_sqacllinks"){return false;}
    return proxyacls_meta_rule_id($id);
}
function pxao_meta_guard(){
    // Les actions qui portent l'identifiant de la regle sont refusees ici ; delete/negation/enable d'un
    // lien ne portent que sa cle : le demon les refuse (423) et la console affiche son message.
    if(isset($_GET["move-js"]) && pxao_acl_rule_meta(intval($_GET["aclid"] ?? 0))){proxyacls_meta_refuse_js();exit;}
    if(isset($_GET["popup-order-js"]) && pxao_acl_rule_meta(intval($_GET["aclid"] ?? 0))){proxyacls_meta_refuse_js();exit;}
    foreach(array("object-link","object-save","object-group-save") as $k){
        if(isset($_POST[$k]) && pxao_acl_rule_meta(intval($_POST[$k]))){echo proxyacls_meta_refuse_post();exit;}
    }
    if(pxao_pac_locked()){
        foreach(array("delete-js","move-js","negation-js","enabled-link-js","popup-order-js") as $k){
            if(isset($_GET[$k])){proxypac_meta_refuse_rules_js();exit;}
        }
        if(isset($_POST["zorder"])){proxypac_meta_refuse_rules_text();exit;}
        foreach(array("object-link","object-save","object-group-save") as $k){
            if(isset($_POST[$k])){echo proxypac_meta_refuse_rules_post();exit;}
        }
    }
    if(isset($_GET["enabled-js"]) && proxyobject_meta_refuse_js($_GET["enabled-js"])){exit;}
    if(isset($_POST["delete-remove"]) && proxyobject_meta_refuse_text($_POST["delete-remove"])){exit;}
}

// ---------------------------------------------------------------------------------------
// Refonte 2026-11-17 (maquette stitch_proxy_acl_objects_redesign) : onglet "Proxy objects"
// d'une regle. Un modele (pxao_model) lu une fois par le repondeur ?search-table= ; chaque
// widget est sa propre fonction. Formulaires en modales js_dialog6 (libre dans toute la
// chaine : regle = js_dialog/1/2/5, fiche objet fw.rules.items.php = 7/8/9).
// ---------------------------------------------------------------------------------------

// Nom de la fonction JS de rafraichissement : liste blanche (elle part dans OnClick/$jsafter).
function pxao_function():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
}

// Ecritures : le droit est verifie ici, cacher le bouton ne suffit pas (AsProxyMonitor lit).
function pxao_write_guard(){
    $users=new usersMenus();
    if(isWritter($users)){return;}
    $tpl=new template_admin();
    foreach(array("object-group-save","object-save","object-link","delete-remove","zorder") as $k){
        if(isset($_POST[$k])){echo $tpl->post_error("{pxao_no_privs}");exit;}
    }
    foreach(array("delete-js","move-js","negation-js","enabled-js","enabled-link-js","popup-order-js",
                "pxao-new-js","pxao-link-js","pxao-group-js","new-object","link-object","new-object-group") as $k){
        if(isset($_GET[$k])){$tpl->js_no_privileges();exit;}
    }
}

// Texte deja traduit, pour une chaine JSON / textContent (jamais javascript_parse_text).
function pxao_txt($tpl,$token):string{
    $s=$tpl->_ENGINE_parse_body($token);
    $s=str_replace(array("<br>","<br/>","<br />")," ",$s);
    return trim(preg_replace('/\s+/',' ',html_entity_decode($s,ENT_QUOTES,"UTF-8")));
}

// Feuille de la page (le reste vient du kit ak- d'angular.css.php et d'awc_table_style()).
function pxao_style():string{
    if(isset($GLOBALS["PXAO_STYLE"])){return "";}
    $GLOBALS["PXAO_STYLE"]=true;
    $soft="rgba(16,185,129,.12)";$accent="#006a62";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_dark"])){$accent=$a["accent_dark"];}
    }
    return "<style>
.pxao{color:#111d23;font-size:14px;margin-top:8px}
.pxao *{box-sizing:border-box}
.pxao-intro{display:flex;gap:14px;align-items:flex-start;background:#f7f9fb;border:1px solid #e3e8ee;border-radius:8px;padding:14px 16px;margin-bottom:12px}
.pxao-intro-ico{width:40px;height:40px;border-radius:8px;background:$soft;color:$accent;display:inline-flex;align-items:center;justify-content:center;font-size:18px;flex:0 0 auto}
.pxao-intro-t{font-size:15px;font-weight:600;line-height:20px;display:flex;align-items:center;gap:8px}
.pxao-intro-x{font-size:13px;line-height:19px;color:#3d4947;margin-top:2px}
.pxao-and{font-size:13px;font-weight:700;letter-spacing:.06em;color:#6d7a77;text-transform:uppercase}
.pxao-sum{display:flex;flex-wrap:wrap;align-items:center;gap:8px;border:1px solid #e3e8ee;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px}
.pxao-sum-l{font-weight:600;color:#3d4947;display:inline-flex;align-items:center;gap:6px;margin-right:4px}
.pxao-pill{display:inline-flex;align-items:center;gap:6px;height:26px;padding:0 10px;border-radius:13px;border:1px solid #bcc9c6;background:#fff;color:#111d23;font-size:13px;white-space:nowrap;max-width:260px}
.pxao-pill>span:last-child{overflow:hidden;text-overflow:ellipsis}
.pxao-pill-k{font-size:13px;font-weight:700;letter-spacing:.04em;color:$accent}
.pxao-pill-not{border:1px dashed #d97706;background:#fff8ee}
.pxao-pill-not .pxao-pill-k{color:#865300}
.pxao-pill-off{color:#6d7a77;text-decoration:line-through;background:#f1f5f9}
.pxao-pill-off .pxao-pill-k{color:#6d7a77}
.pxao-tools{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:4px}
.pxao-tools .ak-btn{height:36px}
.pxao .awc-wrap{padding:0;margin:8px 0 0 0;background:transparent}
.pxao .awc-tbl tbody td{padding:12px 14px}
.pxao .awc-tbl thead th{padding:12px 14px;font-size:13px}
.pxao-ord{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:28px;padding:0 6px;border:1px solid #bcc9c6;border-radius:6px;background:#fff;color:#111d23;font-size:13px;font-weight:600;text-decoration:none!important}
a.pxao-ord:hover{border-color:$accent;color:$accent}
.pxao-seg .ak-seg-b{padding:5px 9px}
.pxao-seg .ak-seg-b[disabled]{cursor:default}
.pxao-seg .ak-seg-b.on.pxao-not{background:#d97706;color:#fff}
.pxao-cond-fixed{font-weight:600;font-size:13px}
.pxao-name-row{display:flex;flex-wrap:wrap;align-items:center;gap:6px}
.pxao-desc{font-size:13px;line-height:18px;color:#3d4947;margin-top:2px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.pxao-err{display:flex;gap:6px;align-items:flex-start;font-size:13px;line-height:18px;color:#ba1a1a;margin-top:4px}
.pxao-pac{font-size:13px;color:#3d4947;margin-top:2px}
.pxao-num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.pxao .ak-act[disabled]{opacity:.35;cursor:not-allowed}
.pxao .awc-acts{opacity:1;gap:2px}
.pxao-pick-list{max-height:380px;overflow-y:auto;border:1px solid #e3e8ee;border-radius:8px;margin-top:8px}
.pxao-pick-grp{position:sticky;top:0;background:#f7f9fb;font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#3d4947;padding:6px 12px;border-bottom:1px solid #e3e8ee}
.pxao-pick-it{display:flex;align-items:center;gap:10px;padding:8px 12px;border-bottom:1px solid #f1f5f9;cursor:pointer;font-size:14px}
.pxao-pick-it:hover{background:#fbfeff}
.pxao-pick-it.sel{background:$soft}
.pxao-pick-it.dis{color:#6d7a77;cursor:not-allowed}
.pxao-pick-it .ak-note{margin:0 0 0 auto}
@media(max-width:820px){.pxao-col-type{display:none}}
</style>";
}

// ------------------------------- modele --------------------------------------------------
function pxao_model($ID,$TableLink,$search):array{
    $tpl=new template_admin();
    $users=new usersMenus();
    $m=array("ok"=>false,"rows"=>array(),"locked"=>false,"writer"=>isWritter($users));
    $pacpxy=($TableLink==="wpad_sources_link" || $TableLink==="wpad_black_link")?1:0;
    $endpoint="/proxy/acls/sqlite/links?table=".urlencode($TableLink)
             ."&aclid=$ID&search=".urlencode($search)."&pacpxy=$pacpxy";
    VERBOSE($endpoint,__LINE__);
    $results=acls_api_get($endpoint);
    if(!is_array($results)){return $m;}
    $m["ok"]=true;
    $m["locked"]=pxao_pac_locked($TableLink) || ($TableLink==="webfilters_sqacllinks" && pxao_acl_rule_meta($ID));
    $q=new mysql_squid_builder();
    foreach($results as $l){
        if(!is_array($l)){continue;}
        $gid=intval($l["groupID"] ?? 0);
        $gtype=(string)($l["groupType"] ?? "");
        $MAIN=$tpl->table_object($gid,$TableLink);
        $ico=(string)($MAIN["ICO"] ?? "");
        if($ico=="" && isset($q->acl_GroupTypeIcon[$gtype])){$ico=$q->acl_GroupTypeIcon[$gtype];}
        if($ico==""){$ico="fas fa-cube";}
        $proxies=array();
        if($pacpxy==1 && !empty($l["pacpxy"])){
            $arr=unserializeb64($l["pacpxy"]);
            if(is_array($arr)){
                foreach($arr as $p){
                    $h=(string)($p["hostname"] ?? "");
                    if($h=="0.0.0.0"){$proxies=array("{direct_to_internet}");break;}
                    if($h==""){continue;}
                    $proxies[]="$h:".intval($p["port"] ?? 0);
                }
            }
        }
        $m["rows"][]=array(
            "mkey"=>(string)($l["mkey"] ?? ""),
            "id"=>$gid,
            "name"=>(string)($MAIN["GROUPNAME"] ?? ($l["groupName"] ?? "")),
            "type"=>$gtype,
            "type_label"=>(string)($MAIN["TYPE"] ?? $gtype),
            "ico"=>$ico,
            "order"=>intval($l["xOrder"] ?? 0),
            "negation"=>intval($l["negation"] ?? 0),
            "enabled"=>intval($l["enabled"] ?? 1),
            "description"=>trim((string)($l["description"] ?? "")),
            "items"=>intval($MAIN["ITEMS"] ?? 0),
            "noitems"=>!empty($MAIN["NOITEMS"]),
            "error"=>(string)($MAIN["ERROR"] ?? ""),
            "proxies"=>$proxies,
            "held"=>proxyobject_meta_held($gid),
        );
    }
    return $m;
}

// ------------------------------- widgets -------------------------------------------------
function pxao_intro():string{
    return "<div class='pxao-intro'><span class='pxao-intro-ico'><i class='fa-solid fa-diagram-project'></i></span>"
        ."<div><div class='pxao-intro-t'>{proxy_objects} <span class='ak-tag ak-off pxao-and'>{pxao_and_logic}</span></div>"
        ."<div class='pxao-intro-x'>{pxao_intro}</div></div></div>";
}

function pxao_summary_pill($r):string{
    $name=htmlspecialchars(strip_tags($r["name"]));
    $ico="<i class='".htmlspecialchars($r["ico"],ENT_QUOTES)."'></i>";
    if($r["enabled"]==0){
        return "<span class='pxao-pill pxao-pill-off' title='{pxao_disabled_help}'><i class='fa-solid fa-ban'></i><span>$name ({pxao_disabled_short})</span></span>";
    }
    if($r["negation"]==1){
        return "<span class='pxao-pill pxao-pill-not'><span class='pxao-pill-k'>{pxao_not}</span>$ico<span>$name</span></span>";
    }
    return "<span class='pxao-pill'><span class='pxao-pill-k'>{pxao_is}</span>$ico<span>$name</span></span>";
}

function pxao_summary($m,$searching):string{
    if($searching){return "";}   // un filtre ne montre qu'une partie de la regle : pas de phrase fausse
    if(count($m["rows"])==0){
        return "<div class='ak-banner ak-warn'><i class='fa-solid fa-triangle-exclamation'></i><span class='ak-banner-m'>{pxao_no_object}</span></div>";
    }
    // Au-dela de 12 objets la phrase ne se lit plus : on la coupe et on dit combien il en reste.
    $f=array();$max=12;$n=count($m["rows"]);
    foreach($m["rows"] as $i=>$r){
        if($i>=$max){break;}
        if($i>0){$f[]="<span class='pxao-and'>{pxao_and}</span>";}
        $f[]=pxao_summary_pill($r);
    }
    if($n>$max){$f[]="<span class='pxao-and'>{pxao_and}</span><span class='ak-count'>+".($n-$max)." {pxao_more_objects}</span>";}
    return "<div class='pxao-sum'><span class='pxao-sum-l'><i class='fa-solid fa-code-branch'></i>{pxao_applies_when}</span>".implode("",$f)."</div>";
}

function pxao_cond_cell($r,$ctx):string{
    // Le ET entre objets est dit par la phrase de synthese : le selecteur reste court.
    $is="{is}";$isnot="{is_not}";
    if(in_array($r["type"],array("spf","dmarc","spamc"))){
        return "<span class='pxao-cond-fixed'>".($r["negation"]==1?$isnot:$is)."</span>";
    }
    $dis=($ctx["locked"] || !$ctx["writer"])?" disabled":"";
    $name=htmlspecialchars(strip_tags($r["name"]),ENT_QUOTES);
    $url=htmlspecialchars($ctx["negurl"],ENT_QUOTES);
    $onIs=$r["negation"]==0;
    $b1="<button type='button' class='ak-seg-b".($onIs?" on":"")."' aria-pressed='".($onIs?"true":"false")."'$dis"
        .($onIs?"":" data-name=\"$name\" data-url=\"$url\" data-to='is' OnClick=\"PxaoInvert(this)\"").">$is</button>";
    $b2="<button type='button' class='ak-seg-b pxao-not".(!$onIs?" on":"")."' aria-pressed='".(!$onIs?"true":"false")."'$dis"
        .(!$onIs?"":" data-name=\"$name\" data-url=\"$url\" data-to='not' OnClick=\"PxaoInvert(this)\"").">{is_not}</button>";
    return "<div class='ak-seg pxao-seg' role='group' aria-label='{pxao_condition}'>$b1$b2</div>";
}

function pxao_error_text($err):string{
    $map=array("{license_error}"=>"{pxao_err_license}","{emergency_mode}"=>"{pxao_err_emergency}",
               "{external_module_disabled}"=>"{pxao_err_module}");
    return isset($map[$err])?$map[$err]:$err;
}

function pxao_object_cell($r,$ctx):string{
    $tile=$r["enabled"]==1&&$r["error"]==""?"awc-ico":"awc-ico awc-ico-off";
    $ico=$r["error"]!=""?"fa-solid fa-circle-exclamation":$r["ico"];
    $nameCls=$r["enabled"]==1?"awc-name":"awc-name awc-name-off";
    $h="<div class='awc-ident'><span class='$tile'><i class='".htmlspecialchars($ico,ENT_QUOTES)."'></i></span><div style='min-width:0'>";
    $h.="<div class='pxao-name-row'><span class='$nameCls' style='font-size:14px'><a href=\"javascript:blur();\" OnClick=\"{$ctx["editjs"]}\">".$r["name"]."</a></span>";
    if($r["enabled"]==0){$h.="<span class='ak-tag ak-off' title='{pxao_disabled_help}'><i class='fa-solid fa-ban'></i> {pxao_disabled_short}</span>";}
    if($r["held"]){$h.="<span class='ak-tag ak-info' title='{proxyobject_meta_managed_short}'><i class='fa-solid fa-lock'></i> {proxyobject_meta_badge}</span>";}
    $h.="</div>";
    if($r["description"]!=""){$h.="<div class='pxao-desc'>".htmlspecialchars($r["description"])."</div>";}
    if(count($r["proxies"])>0){
        $h.="<div class='pxao-pac'>{pxao_proxies}: <span class='ak-mono'>".htmlspecialchars(implode(", ",$r["proxies"]))."</span></div>";
    }
    if($r["error"]!=""){
        $h.="<div class='pxao-err'><i class='fa-solid fa-circle-exclamation'></i><span>".pxao_error_text($r["error"])."</span></div>";
    }
    return $h."</div></div>";
}

function pxao_items_cell($r):string{
    if($r["noitems"] && $r["items"]==0){return "<span class='awc-muted' title='{pxao_no_items_help}'>&mdash;</span>";}
    $tpl=new template_admin();
    return $tpl->FormatNumber($r["items"]);
}

function pxao_act($ico,$label,$js,$disabled,$danger=false):string{
    $cls="ak-act".($danger?" ak-danger":"");
    if($disabled){return "<button type='button' class='$cls' aria-label='$label' title='$label' disabled><i class='$ico'></i></button>";}
    return "<button type='button' class='$cls' aria-label='$label' title='$label' OnClick=\"$js\"><i class='$ico'></i></button>";
}

function pxao_actions_cell($r,$ctx):string{
    $ro=$ctx["locked"] || !$ctx["writer"];
    $f=array();
    $canAdd=!$r["noitems"] && !in_array($r["type"],array("categories","spf","dmarc","spamc"));
    if($r["type"]=="AclsGroup"){$canAdd=true;}
    if($canAdd){
        $f[]=pxao_act("fa-solid fa-plus","{pxao_add_items}",$ctx["addjs"],!$ctx["writer"] || $r["held"]);
    }else{
        $f[]="<span class='ak-act' aria-hidden='true' style='visibility:hidden'></span>";
    }
    $f[]=pxao_act("fa-solid fa-gear","{parameters}",$ctx["editjs"],false);
    $f[]=pxao_act("fa-solid fa-arrow-up","{pxao_move_up}",$ctx["upjs"],$ro || $ctx["index"]==0);
    $f[]=pxao_act("fa-solid fa-arrow-down","{pxao_move_down}",$ctx["downjs"],$ro || $ctx["index"]>=$ctx["count"]-1);
    $name=htmlspecialchars(strip_tags($r["name"]),ENT_QUOTES);
    $url=htmlspecialchars($ctx["unlinkurl"],ENT_QUOTES);
    if($ro){
        $f[]=pxao_act("fa-solid fa-link-slash","{pxao_unlink}","",true,true);
    }else{
        $f[]="<button type='button' class='ak-act ak-danger' aria-label='{pxao_unlink}' title='{pxao_unlink}' data-name=\"$name\" data-url=\"$url\" OnClick=\"PxaoUnlink(this)\"><i class='fa-solid fa-link-slash'></i></button>";
    }
    return "<div class='awc-acts'>".implode("",$f)."</div>";
}

function pxao_order_cell($r,$ctx):string{
    $n="<span id='groupacls-order-{$r["mkey"]}'>{$r["order"]}</span>";
    if($ctx["locked"] || !$ctx["writer"]){return "<span class='pxao-ord'>$n</span>";}
    return "<a class='pxao-ord' href=\"javascript:blur();\" title='{pxao_order_help}' OnClick=\"{$ctx["orderjs"]}\">$n</a>";
}

function pxao_row($r,$ctx):string{
    $h="<tr id='{$r["mkey"]}'>";
    $h.="<td class='center' style='width:1%'>".pxao_order_cell($r,$ctx)."</td>";
    $h.="<td style='width:1%' nowrap>".pxao_cond_cell($r,$ctx)."</td>";
    $h.="<td>".pxao_object_cell($r,$ctx)."</td>";
    $h.="<td class='pxao-col-type' style='width:1%' nowrap><span class='ak-tag ak-off'>".$r["type_label"]."</span></td>";
    $h.="<td class='pxao-num' style='width:1%'>".pxao_items_cell($r)."</td>";
    $h.="<td style='width:1%' nowrap>".pxao_actions_cell($r,$ctx)."</td>";
    return $h."</tr>";
}

function pxao_table($m,$ctxBase,$emptyHtml):string{
    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th class='center'>{pxao_col_order}</th><th>{pxao_condition}</th><th>{pxao_col_object}</th>";
    $html[]="<th class='pxao-col-type'>{pxao_col_type}</th><th style='text-align:right'>{pxao_col_items}</th><th style='text-align:right'>{pxao_col_actions}</th>";
    $html[]="</tr></thead><tbody id='pxao-rows'>";
    $count=count($m["rows"]);
    foreach($m["rows"] as $i=>$r){
        $ctx=pxao_row_ctx($r,$ctxBase);
        $ctx["index"]=$i;$ctx["count"]=$count;
        $html[]=pxao_row($r,$ctx);
    }
    if($count==0){$html[]="<tr><td colspan='6'><div class='awc-empty'>$emptyHtml</div></td></tr>";}
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{pxao_showing_objects}: $count</span><span class='awc-pg' id='pxao-pager'></span></div>";
    $html[]="</div></div>";
    return implode("\n",$html);
}

// Actions d'une ligne : memes URL que l'ancienne page (handlers inchanges).
function pxao_row_ctx($r,$b):array{
    $page=$b["page"];$q=$b["q"];$id=$r["id"];$mkey=$r["mkey"];$ID=$b["ID"];$fn=$b["function"];
    $ctx=$b;
    $ctx["editjs"]="Loadjs('fw.rules.items.php?groupid=$id&js-after={$b["jsAfter"]}&TableLink={$b["TableLink"]}$q&function=$fn')";
    if($r["type"]=="AclsGroup"){
        $ctx["addjs"]="Loadjs('fw.rules.items.objects.php?js=yes&groupid=$id&{$b["suffix"]}&TableLink={$b["TableLink"]}')";
    }else{
        $ctx["addjs"]="Loadjs('fw.rules.items.php?new-item-js=$id&js-after={$b["jsAfter"]}&ProxyPac={$b["ProxyPac"]}&fastacls={$b["fastacls"]}&TableLink={$b["TableLink"]}')";
    }
    $ctx["upjs"]="Loadjs('$page?move-js=up&aclid=$ID&objectid=$id&mkey=$mkey&TableLink={$b["TableLink"]}$q&function=$fn')";
    $ctx["downjs"]="Loadjs('$page?move-js=down&aclid=$ID&objectid=$id&mkey=$mkey&TableLink={$b["TableLink"]}$q&function=$fn')";
    $ctx["orderjs"]="Loadjs('$page?popup-order-js=$mkey&TableLink={$b["TableLink"]}&aclid=$ID&function=$fn')";
    $ctx["negurl"]="$page?negation-js=$mkey&TableLink={$b["TableLink"]}$q&function=$fn";
    $ctx["unlinkurl"]="$page?delete-js=$mkey&TableLink={$b["TableLink"]}$q&function=$fn";
    return $ctx;
}

// Confirmations (SweetAlert) : le nom voyage en data-name, jamais dans une chaine JS.
function pxao_js($tpl):string{
    $T=array(
        "inv_t"=>pxao_txt($tpl,"{pxao_invert_title}"),
        "inv_not"=>pxao_txt($tpl,"{pxao_invert_to_not}"),
        "inv_is"=>pxao_txt($tpl,"{pxao_invert_to_is}"),
        "inv_b"=>pxao_txt($tpl,"{pxao_invert}"),
        "unl_t"=>pxao_txt($tpl,"{pxao_unlink_title}"),
        "unl_x"=>pxao_txt($tpl,"{pxao_unlink_text}"),
        "unl_b"=>pxao_txt($tpl,"{pxao_unlink}"),
        "cancel"=>pxao_txt($tpl,"{pxao_cancel}"),
    );
    $json=json_encode($T,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
    return "<script>
window.PxaoT=$json;
function PxaoInvert(b){
  var n=b.getAttribute('data-name')||'',u=b.getAttribute('data-url');
  var x=(b.getAttribute('data-to')==='not'?PxaoT.inv_not:PxaoT.inv_is).split('%s').join(n);
  swal({title:PxaoT.inv_t,text:x,type:'warning',showCancelButton:true,confirmButtonColor:'#d97706',
    confirmButtonText:PxaoT.inv_b,cancelButtonText:PxaoT.cancel},function(ok){if(ok){Loadjs(u);}});
}
function PxaoUnlink(b){
  var n=b.getAttribute('data-name')||'',u=b.getAttribute('data-url');
  swal({title:PxaoT.unl_t,text:PxaoT.unl_x.split('%s').join(n),type:'warning',showCancelButton:true,
    confirmButtonColor:'#ba1a1a',confirmButtonText:PxaoT.unl_b,cancelButtonText:PxaoT.cancel},function(ok){if(ok){Loadjs(u);}});
}
</script>";
}

// JS a executer apres un enregistrement dans une modale de la page.
function pxao_after_js():array{
    $f=array();
    $Dialog=intval($_GET["Dialog"] ?? 0);
    if($Dialog>0){$f[]="if(typeof dialogInstance$Dialog=='object'){dialogInstance$Dialog.close();}";}
    $fn=pxao_function();
    if(strlen($fn)>1){$f[]="if(typeof $fn=='function'){{$fn}();}";}
    if(isset($_GET["RefreshFunction"])){
        $rf=jsafter_sanitize(base64_decode((string)$_GET["RefreshFunction"]));
        if(strlen($rf)>3){$f[]=$rf;}
    }
    $rt=jsafter_token($_GET["RefreshTable"] ?? "");
    if(strlen($rt)>3){$f[]=jsafter_sanitize(base64_decode($rt));}
    return $f;
}

// Ouvreurs des trois modales (barre d'outils).
function pxao_dialog_url($what):string{
    $page=CurrentPageName();
    $suffix=build_suffix();
    return "$page?$what=yes&$suffix&Dialog=6";
}
function pxao_new_js():bool{
    $tpl=new template_admin();
    $_GET["ID"]=intval($_GET["pxao-new-js"]);
    unset($_GET["pxao-new-js"]);
    return $tpl->js_dialog6("{new_object}",pxao_dialog_url("new-object"),560);
}
function pxao_link_js():bool{
    $tpl=new template_admin();
    $_GET["ID"]=intval($_GET["pxao-link-js"]);
    unset($_GET["pxao-link-js"]);
    return $tpl->js_dialog6("{link_object}",pxao_dialog_url("link-object"),620);
}
function pxao_group_js():bool{
    $tpl=new template_admin();
    $_GET["ID"]=intval($_GET["pxao-group-js"]);
    unset($_GET["pxao-group-js"]);
    return $tpl->js_dialog6("{new_group_of_objects}",pxao_dialog_url("new-object-group"),560);
}
