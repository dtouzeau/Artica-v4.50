<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["generate-ipv6"])){generate_ipv6();exit;}
if(isset($_GET["ipv6-wizard-js"])){wizard_js();exit;}
if(isset($_GET["ipv6-wizard-popup"])){wizard_popup();exit;}
if(isset($_POST["eth"])){interface_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["nic-deleteGhost-js"])){delete_ghost();exit;}
if(isset($_GET["nic-delete-js"])){delete();exit;}
if(isset($_POST["nic-delete-phys"])){delete_ghost_perform();exit;}
if(isset($_POST["nic-delete"])){delete_perform();exit;}
if(isset($_GET["nic-ipv6-js"])){interface_js();exit;}
if(isset($_GET["nic-ipv6-popup"])){interface_popup();exit;}

table_start();

// ─────────────────────────────────────────────────────
//  IPv6 Wizard
// ─────────────────────────────────────────────────────

function wizard_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=addslashes($_GET["eth"] ?? "");
    $addrType=addslashes($_GET["addr_type"] ?? "ula");
    $tpl->js_dialog5("IPv6 {wizard}","$page?ipv6-wizard-popup=$addrType&eth=$eth",700);
}

function wizard_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $addrType=$_GET["ipv6-wizard-popup"];
    $eth=$_GET["eth"] ?? "";

    switch($addrType){
        case "link-local": wizard_linklocal($tpl,$page,$eth); break;
        case "gua":        wizard_gua($tpl,$page,$eth);       break;
        default:           wizard_ula($tpl,$page,$eth);        break;
    }
}

function wizard_ula($tpl,$page,$eth){
    // Fetch a random Global ID from the backend
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/ip6gen/$eth/ula"));
    $suggestedIP=(is_object($data) && property_exists($data,"IpAddr")) ? $data->IpAddr : "";
    // Parse the suggested ULA to extract parts: fdXX:XXXX:XXXX::1
    $globalHex="";
    if(preg_match('/^fd([0-9a-f]{2}):([0-9a-f]{4}):([0-9a-f]{4})::(.+)$/i',$suggestedIP,$m)){
        $globalHex=$m[1].$m[2].$m[3]; // 10 hex chars = 40-bit Global ID
    }

    $html[]="<div style='padding:15px'>";

    // Header with explanation
    $html[]=$tpl->div_info("{ipv6_ula_wizard_explain}");

    // Visual structure
    $html[]="<div style='margin:20px 0;padding:15px;background:#f8f9fa;border-radius:6px;border:1px solid #e7eaec'>";
    $html[]="<div style='text-align:center;margin-bottom:12px;font-size:13px;color:#676a6c'>";
    $html[]="<strong>ULA {structure}</strong>: ";
    $html[]="<span style='font-family:monospace;font-size:14px'>";
    $html[]="<span style='color:#1ab394'>fd</span>";
    $html[]="<span style='color:#1c84c6' id='wz-global-preview'>XX:XXXX:XXXX</span>";
    $html[]=" : ";
    $html[]="<span style='color:#ab7df6' id='wz-subnet-preview'>YYYY</span>";
    $html[]=" : ";
    $html[]="<span style='color:#ed5565' id='wz-host-preview'>ZZZZ:ZZZZ:ZZZZ:ZZZZ</span>";
    $html[]=" / <span id='wz-prefix-preview'>48</span>";
    $html[]="</span>";
    $html[]="</div>";

    // Full address preview
    $html[]="<div style='text-align:center;padding:10px;background:#fff;border-radius:4px;border:1px solid #e7eaec'>";
    $html[]="<span style='font-family:monospace;font-size:18px;font-weight:bold' id='wz-full-preview'>...</span>";
    $html[]="</div>";
    $html[]="</div>";

    // Input fields in a table layout
    $html[]="<table class='table' style='margin-bottom:0'>";

    // Global ID row
    $html[]="<tr>";
    $html[]="<td style='width:180px;vertical-align:middle'><span class='badge' style='background:#1c84c6;color:#fff'>Global ID</span> (40-bit)</td>";
    $html[]="<td><div class='input-group'>";
    $html[]="<span class='input-group-addon' style='font-family:monospace'>fd</span>";
    $html[]="<input type='text' id='wz-global' class='form-control' style='font-family:monospace' maxlength='14' value='".htmlspecialchars($globalHex)."' placeholder='e.g. a1b2c3d4e5'>";
    $html[]="<span class='input-group-btn'><button class='btn btn-primary' type='button' id='wz-global-regen'><i class='fas fa-dice'></i> {randomize}</button></span>";
    $html[]="</div></td>";
    $html[]="</tr>";

    // Subnet ID row
    $html[]="<tr>";
    $html[]="<td style='vertical-align:middle'><span class='badge' style='background:#ab7df6;color:#fff'>Subnet ID</span> (16-bit)</td>";
    $html[]="<td><input type='text' id='wz-subnet' class='form-control' style='font-family:monospace;width:120px' maxlength='4' value='0000' placeholder='0000-ffff'></td>";
    $html[]="</tr>";

    // Host part row
    $html[]="<tr>";
    $html[]="<td style='vertical-align:middle'><span class='badge' style='background:#ed5565;color:#fff'>{interface} ID</span> (64-bit)</td>";
    $html[]="<td><div class='input-group'>";
    $html[]="<input type='text' id='wz-host' class='form-control' style='font-family:monospace' maxlength='19' value='0000:0000:0000:0001' placeholder='e.g. 0:0:0:1'>";
    $html[]="<span class='input-group-btn'><button class='btn btn-default' type='button' id='wz-host-regen'><i class='fas fa-dice'></i></button></span>";
    $html[]="</div></td>";
    $html[]="</tr>";

    // Prefix length row
    $html[]="<tr>";
    $html[]="<td style='vertical-align:middle'>{netmask}</td>";
    $html[]="<td>";
    $html[]="<select id='wz-prefix' class='form-control' style='width:120px'>";
    $html[]="<option value='48' selected>/48 ({network})</option>";
    $html[]="<option value='64'>/64 ({subnet})</option>";
    $html[]="</select>";
    $html[]="</td>";
    $html[]="</tr>";

    $html[]="</table>";

    // Use button
    $html[]="<div style='text-align:right;margin-top:15px'>";
    $html[]="<button class='btn btn-primary btn-lg' type='button' id='wz-use'><i class='fas fa-check'></i> {use_this_address}</button>";
    $html[]="</div>";
    $html[]="</div>";

    // JavaScript
    $js=[];
    $js[]="<script>";
    $js[]="(function(){";
    // Helpers
    $js[]="  function rndHex(n){ var s=''; for(var i=0;i<n;i++) s+='0123456789abcdef'.charAt(Math.floor(Math.random()*16)); return s; }";
    $js[]="  function formatGlobal(raw){";
    $js[]="    raw=raw.replace(/[^0-9a-fA-F]/g,'').substring(0,10);";
    $js[]="    while(raw.length<10) raw+='0';";
    $js[]="    return raw.substring(0,2)+':'+raw.substring(2,6)+':'+raw.substring(6,10);";
    $js[]="  }";
    $js[]="  function updatePreview(){";
    $js[]="    var g=formatGlobal(document.getElementById('wz-global').value);";
    $js[]="    var s=document.getElementById('wz-subnet').value.replace(/[^0-9a-fA-F]/g,'');";
    $js[]="    while(s.length<4) s='0'+s; s=s.substring(0,4);";
    $js[]="    var h=document.getElementById('wz-host').value.replace(/[^0-9a-fA-F:]/g,'');";
    $js[]="    var p=document.getElementById('wz-prefix').value;";
    $js[]="    document.getElementById('wz-global-preview').textContent=g;";
    $js[]="    document.getElementById('wz-subnet-preview').textContent=s;";
    $js[]="    document.getElementById('wz-host-preview').textContent=h||'::1';";
    $js[]="    document.getElementById('wz-prefix-preview').textContent=p;";
    $js[]="    var full='fd'+g+':'+s+':'+h;";
    $js[]="    document.getElementById('wz-full-preview').textContent=full+' / '+p;";
    $js[]="  }";
    // Event listeners
    $js[]="  document.getElementById('wz-global').oninput=updatePreview;";
    $js[]="  document.getElementById('wz-subnet').oninput=updatePreview;";
    $js[]="  document.getElementById('wz-host').oninput=updatePreview;";
    $js[]="  document.getElementById('wz-prefix').onchange=updatePreview;";
    // Randomize buttons
    $js[]="  document.getElementById('wz-global-regen').onclick=function(){ document.getElementById('wz-global').value=rndHex(10); updatePreview(); };";
    $js[]="  document.getElementById('wz-host-regen').onclick=function(){ var a=rndHex(4),b=rndHex(4),c=rndHex(4),d=rndHex(4); document.getElementById('wz-host').value=a+':'+b+':'+c+':'+d; updatePreview(); };";
    // Use button - fill parent form and close wizard
    // Parent form fields use name= not id=, so use getElementsByName
    $js[]="  function gn(n){return document.getElementsByName(n)[0];}";
    $js[]="  document.getElementById('wz-use').onclick=function(){";
    $js[]="    var g=formatGlobal(document.getElementById('wz-global').value);";
    $js[]="    var s=document.getElementById('wz-subnet').value.replace(/[^0-9a-fA-F]/g,'');";
    $js[]="    while(s.length<4) s='0'+s; s=s.substring(0,4);";
    $js[]="    var h=document.getElementById('wz-host').value.replace(/[^0-9a-fA-F:]/g,'')||'0000:0000:0000:0001';";
    $js[]="    var p=document.getElementById('wz-prefix').value;";
    $js[]="    var full='fd'+g+':'+s+':'+h;";
    $js[]="    gn('ipaddr').value=full;";
    $js[]="    gn('cdir').value=p;";
    $js[]="    dialogInstance5.close();";
    $js[]="  };";
    $js[]="  updatePreview();";
    $js[]="})();";
    $js[]="</script>";
    $html[]=implode("\n",$js);

    echo $tpl->_ENGINE_parse_body($html);
}

function wizard_linklocal($tpl,$page,$eth){
    // Generate link-local from MAC
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/ip6gen/$eth/link-local"));
    $linkLocal=(is_object($data) && property_exists($data,"IpAddr")) ? $data->IpAddr : "";

    // Get MAC address for display
    $statusData=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/nic/status/$eth"));
    $mac="";
    if(is_object($statusData) && property_exists($statusData,"Info")){
        $parts=explode(";",$statusData->Info);
        if(isset($parts[1])) $mac=$parts[1];
    }

    $html[]="<div style='padding:15px'>";

    $html[]=$tpl->div_info("{ipv6_linklocal_wizard_explain}");

    // Visual structure
    $html[]="<div style='margin:20px 0;padding:15px;background:#f8f9fa;border-radius:6px;border:1px solid #e7eaec'>";
    $html[]="<div style='text-align:center;margin-bottom:8px;font-size:13px;color:#676a6c'>";
    $html[]="<strong>Link-Local {structure}</strong>: ";
    $html[]="<span style='font-family:monospace;font-size:14px'>";
    $html[]="<span style='color:#1ab394'>fe80::</span>";
    $html[]="<span style='color:#1c84c6'>{EUI-64 from MAC}</span>";
    $html[]=" / 64</span>";
    $html[]="</div>";

    // MAC display
    if($mac!=""){
        $html[]="<div style='text-align:center;margin-bottom:8px'>";
        $html[]="<span class='badge' style='background:#676a6c;color:#fff'>MAC</span> ";
        $html[]="<span style='font-family:monospace;font-size:14px'>".htmlspecialchars($mac)."</span>";
        $html[]="</div>";
    }

    // Generated address display
    $html[]="<div style='text-align:center;padding:12px;background:#fff;border-radius:4px;border:1px solid #e7eaec'>";
    $html[]="<span style='font-family:monospace;font-size:20px;font-weight:bold;color:#1ab394' id='wz-ll-addr'>".htmlspecialchars($linkLocal)."</span>";
    $html[]="<span style='font-family:monospace;font-size:16px;color:#999'> / 64</span>";
    $html[]="</div>";
    $html[]="</div>";

    if($linkLocal==""){
        $html[]=$tpl->div_warning("{ipv6_linklocal_no_mac}");
    }

    // Regenerate + Use buttons
    $html[]="<div style='text-align:right;margin-top:20px'>";
    if($linkLocal!=""){
        $html[]="<button class='btn btn-primary btn-lg' type='button' id='wz-ll-use'><i class='fas fa-check'></i> {use_this_address}</button>";
    }
    $html[]="</div>";
    $html[]="</div>";

    // JavaScript
    $llSafe=addslashes($linkLocal);
    $js=[];
    $js[]="<script>";
    $js[]="(function(){";
    $js[]="  function gn(n){return document.getElementsByName(n)[0];}";
    $js[]="  var btn=document.getElementById('wz-ll-use');";
    $js[]="  if(btn){ btn.onclick=function(){";
    $js[]="    gn('ipaddr').value='$llSafe';";
    $js[]="    gn('cdir').value='64';";
    $js[]="    dialogInstance5.close();";
    $js[]="  };}";
    $js[]="})();";
    $js[]="</script>";
    $html[]=implode("\n",$js);

    echo $tpl->_ENGINE_parse_body($html);
}

function wizard_gua($tpl,$page,$eth){

    $html[]="<div style='padding:15px'>";

    $html[]=$tpl->div_info("{ipv6_gua_wizard_explain}");

    // Visual structure
    $html[]="<div style='margin:20px 0;padding:15px;background:#f8f9fa;border-radius:6px;border:1px solid #e7eaec'>";
    $html[]="<div style='text-align:center;margin-bottom:12px;font-size:13px;color:#676a6c'>";
    $html[]="<strong>GUA {structure}</strong>: ";
    $html[]="<span style='font-family:monospace;font-size:14px'>";
    $html[]="<span style='color:#1c84c6'>{ISP prefix}</span>";
    $html[]=" : ";
    $html[]="<span style='color:#ab7df6' id='wz-gua-subnet-preview'>YYYY</span>";
    $html[]=" : ";
    $html[]="<span style='color:#ed5565' id='wz-gua-host-preview'>ZZZZ:ZZZZ:ZZZZ:ZZZZ</span>";
    $html[]=" / <span id='wz-gua-prefix-preview'>64</span>";
    $html[]="</span>";
    $html[]="</div>";
    // Full preview
    $html[]="<div style='text-align:center;padding:10px;background:#fff;border-radius:4px;border:1px solid #e7eaec'>";
    $html[]="<span style='font-family:monospace;font-size:18px;font-weight:bold' id='wz-gua-full-preview'>...</span>";
    $html[]="</div>";
    $html[]="</div>";

    // Input fields
    $html[]="<table class='table' style='margin-bottom:0'>";

    // ISP prefix
    $html[]="<tr>";
    $html[]="<td style='width:180px;vertical-align:middle'><span class='badge' style='background:#1c84c6;color:#fff'>{ISP prefix}</span></td>";
    $html[]="<td><div class='input-group'>";
    $html[]="<input type='text' id='wz-gua-prefix-input' class='form-control' style='font-family:monospace' placeholder='2001:db8:abcd' value=''>";
    $html[]="<span class='input-group-addon'>::</span>";
    $html[]="</div>";
    $html[]="<span class='help-block' style='margin-bottom:0'>{ipv6_gua_prefix_help}</span>";
    $html[]="</td>";
    $html[]="</tr>";

    // Subnet ID
    $html[]="<tr>";
    $html[]="<td style='vertical-align:middle'><span class='badge' style='background:#ab7df6;color:#fff'>Subnet ID</span></td>";
    $html[]="<td><input type='text' id='wz-gua-subnet' class='form-control' style='font-family:monospace;width:120px' maxlength='4' value='0001' placeholder='0000-ffff'></td>";
    $html[]="</tr>";

    // Host part
    $html[]="<tr>";
    $html[]="<td style='vertical-align:middle'><span class='badge' style='background:#ed5565;color:#fff'>{interface} ID</span></td>";
    $html[]="<td><div class='input-group'>";
    $html[]="<input type='text' id='wz-gua-host' class='form-control' style='font-family:monospace' maxlength='19' value='0000:0000:0000:0001' placeholder='e.g. 0:0:0:1'>";
    $html[]="<span class='input-group-btn'><button class='btn btn-default' type='button' id='wz-gua-host-regen'><i class='fas fa-dice'></i></button></span>";
    $html[]="</div></td>";
    $html[]="</tr>";

    // Prefix length
    $html[]="<tr>";
    $html[]="<td style='vertical-align:middle'>{netmask}</td>";
    $html[]="<td><select id='wz-gua-pfx' class='form-control' style='width:120px'>";
    $html[]="<option value='48'>/48</option>";
    $html[]="<option value='56'>/56</option>";
    $html[]="<option value='64' selected>/64</option>";
    $html[]="<option value='128'>/128</option>";
    $html[]="</select></td>";
    $html[]="</tr>";

    $html[]="</table>";

    // Use button
    $html[]="<div style='text-align:right;margin-top:15px'>";
    $html[]="<button class='btn btn-primary btn-lg' type='button' id='wz-gua-use'><i class='fas fa-check'></i> {use_this_address}</button>";
    $html[]="</div>";
    $html[]="</div>";

    // JavaScript
    $js=[];
    $js[]="<script>";
    $js[]="(function(){";
    $js[]="  function rndHex(n){ var s=''; for(var i=0;i<n;i++) s+='0123456789abcdef'.charAt(Math.floor(Math.random()*16)); return s; }";
    $js[]="  function updateGUAPreview(){";
    $js[]="    var pfx=document.getElementById('wz-gua-prefix-input').value.replace(/\\s+/g,'');";
    $js[]="    var sub=document.getElementById('wz-gua-subnet').value.replace(/[^0-9a-fA-F]/g,'');";
    $js[]="    while(sub.length<4) sub='0'+sub; sub=sub.substring(0,4);";
    $js[]="    var h=document.getElementById('wz-gua-host').value.replace(/[^0-9a-fA-F:]/g,'')||'::1';";
    $js[]="    var p=document.getElementById('wz-gua-pfx').value;";
    $js[]="    document.getElementById('wz-gua-subnet-preview').textContent=sub;";
    $js[]="    document.getElementById('wz-gua-host-preview').textContent=h;";
    $js[]="    document.getElementById('wz-gua-prefix-preview').textContent=p;";
    $js[]="    var full=pfx+':'+sub+':'+h;";
    $js[]="    document.getElementById('wz-gua-full-preview').textContent=full+' / '+p;";
    $js[]="  }";
    $js[]="  document.getElementById('wz-gua-prefix-input').oninput=updateGUAPreview;";
    $js[]="  document.getElementById('wz-gua-subnet').oninput=updateGUAPreview;";
    $js[]="  document.getElementById('wz-gua-host').oninput=updateGUAPreview;";
    $js[]="  document.getElementById('wz-gua-pfx').onchange=updateGUAPreview;";
    $js[]="  document.getElementById('wz-gua-host-regen').onclick=function(){ var a=rndHex(4),b=rndHex(4),c=rndHex(4),d=rndHex(4); document.getElementById('wz-gua-host').value=a+':'+b+':'+c+':'+d; updateGUAPreview(); };";
    // Use button — parent form fields use name= not id=
    $js[]="  function gn(n){return document.getElementsByName(n)[0];}";
    $js[]="  document.getElementById('wz-gua-use').onclick=function(){";
    $js[]="    var pfx=document.getElementById('wz-gua-prefix-input').value.replace(/\\s+/g,'');";
    $js[]="    var sub=document.getElementById('wz-gua-subnet').value.replace(/[^0-9a-fA-F]/g,'');";
    $js[]="    while(sub.length<4) sub='0'+sub; sub=sub.substring(0,4);";
    $js[]="    var h=document.getElementById('wz-gua-host').value.replace(/[^0-9a-fA-F:]/g,'')||'0000:0000:0000:0001';";
    $js[]="    var p=document.getElementById('wz-gua-pfx').value;";
    $js[]="    var full=pfx+':'+sub+':'+h;";
    $js[]="    gn('ipaddr').value=full;";
    $js[]="    gn('cdir').value=p;";
    $js[]="    dialogInstance5.close();";
    $js[]="  };";
    $js[]="  updateGUAPreview();";
    $js[]="})();";
    $js[]="</script>";
    $html[]=implode("\n",$js);

    echo $tpl->_ENGINE_parse_body($html);
}

// ─────────────────────────────────────────────────────
//  Delete handlers
// ─────────────────────────────────────────────────────

function delete_ghost():bool{
    $tpl=new template_admin();
    $ipaddr=$_GET["nic-deleteGhost-js"];
    $eth=$_GET["eth"];
    $md=$_GET["md"];
    return $tpl->js_confirm_delete("$ipaddr","nic-delete-phys","$eth;$ipaddr","$('#$md').remove();");
}
function delete():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["nic-delete-js"]);
    $eth=$_GET["eth"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne=$q->mysqli_fetch_array("SELECT ipaddr FROM nics_ipv6 WHERE ID=$ID");
    $ipaddr=$ligne["ipaddr"] ?? "ID:$ID";
    return $tpl->js_confirm_delete("$ipaddr","nic-delete","$eth;$ID","$('#$md').remove();");
}
function delete_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode(";",$_POST["nic-delete"]);
    $eth=$tb[0];
    $ID=intval($tb[1]);
    if(strlen($eth)<3){
        echo "Interface corrupted\n";
        return false;
    }
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/system/network/ip6/$eth/$ID");
    $json=json_decode($data);
    if(is_object($json) && property_exists($json,"Status") && !$json->Status){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error)));
        return false;
    }
    return admin_tracks("Remove ipv6 addr ID=$ID from $eth");
}
function delete_ghost_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode(";",$_POST["nic-delete-phys"]);
    $eth=$tb[0];
    $ipaddr_with_mask=$tb[1];
    if(strlen($eth)<3){
        echo "Interface corrupted\n";
        return false;
    }
    $parts=explode("/",$ipaddr_with_mask);
    $ipaddr=urlencode($parts[0]);
    $mask=isset($parts[1]) ? intval($parts[1]) : 128;

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/system/network/ip6/ghost/$eth/$ipaddr/$mask");
    $json=json_decode($data);
    if(is_object($json) && property_exists($json,"Status") && !$json->Status){
        echo htmlspecialchars($json->Error);
    }
    return true;
}

// ─────────────────────────────────────────────────────
//  Main form popup + table
// ─────────────────────────────────────────────────────

function interface_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["eth"];
    $nicid=intval($_GET["nic-ipv6-js"]);
    $nicid_text="{new_interface}";
    if($nicid>0){
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $ligne=$q->mysqli_fetch_array("SELECT ipaddr FROM nics_ipv6 WHERE ID=$nicid");
        if(isset($ligne["ipaddr"])){$nicid_text=$ligne["ipaddr"];}
    }
    $tpl->js_dialog3($nicid_text, "$page?nic-ipv6-popup=$nicid&eth=$eth");
}
function table_start(){
    $eth=$_GET["nic"];
    $page=CurrentPageName();
    echo "<div id='ipv6Table$eth'></div>";
    echo "<script>LoadAjax('ipv6Table$eth','$page?table=$eth');</script>";
}
function addr_type_badge($type){
    switch($type){
        case "link-local": return "<span class='badge' style='background:#1ab394;color:#fff'>Link-Local</span>";
        case "gua":        return "<span class='badge' style='background:#1c84c6;color:#fff'>GUA</span>";
        case "ula":        return "<span class='badge' style='background:#ab7df6;color:#fff'>ULA</span>";
        default:           return "<span class='badge' style='background:#676a6c;color:#fff'>".htmlspecialchars($type ?: "unknown")."</span>";
    }
}
function table():bool{
    $tpl=new template_admin();
    $eth=$_GET["table"];
    $page=CurrentPageName();

    $topbuttons[] = array("Loadjs('$page?nic-ipv6-js=0&eth=$eth');", ico_plus, "{new_interface}");
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    $html[]="</div>";

    $html[]="<table id='table-$eth' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";

    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{tcp_address}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{gateway}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{netmask}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $FF=[];

    // DB-configured addresses
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT * FROM nics_ipv6 WHERE nic='".addslashes($eth)."'");
    foreach ($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd") { $TRCLASS = null; } else { $TRCLASS = "footable-odd"; }
        $ipaddr=$ligne["ipaddr"];
        $cdir=$ligne["cdir"];
        $FF[$ipaddr]=$ligne["ID"];
        $gateway=$ligne["gateway"];
        $addrType=$ligne["addr_type"] ?? "";
        $id = md5(serialize($ligne));
        $delete=$tpl->icon_delete("Loadjs('$page?nic-delete-js={$ligne["ID"]}&eth=$eth&md=$id')","AsSystemAdministrator");
        $ipaddrLink=$tpl->td_href($ipaddr,null,"Loadjs('$page?nic-ipv6-js={$ligne["ID"]}&eth=$eth');");
        $html[]="<tr class='$TRCLASS' id='$id'>";
        $html[]="<td>$ipaddrLink</td>";
        $html[]="<td>".addr_type_badge($addrType)."</td>";
        $html[]="<td>$gateway</td>";
        $html[]="<td>$cdir</td>";
        $html[]="<td><center>{$delete}</center></td>";
        $html[]="</tr>";
    }

    // Live kernel addresses (ghost entries)
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/ip6/$eth");
    $json=json_decode($data);
    if(is_array($json)){
        foreach ($json as $index=>$ipcl){
            if ($TRCLASS == "footable-odd") { $TRCLASS = null; } else { $TRCLASS = "footable-odd"; }
            $ipaddr=$ipcl->IpAddr;
            $cdir=$ipcl->NetMask;
            if(isset($FF[$ipaddr])){
                continue;
            }
            $ipaddrEnc=urlencode($ipaddr."/$cdir");
            $gateway=isset($ipcl->Gateway) ? $ipcl->Gateway : "";
            $addrType=isset($ipcl->AddrType) ? $ipcl->AddrType : "";
            $id = md5($ipaddr.$cdir);
            $delete=$tpl->icon_delete("Loadjs('$page?nic-deleteGhost-js=$ipaddrEnc&eth=$eth&md=$id')","AsSystemAdministrator");
            $html[]="<tr class='$TRCLASS' id='$id'>";
            $html[]="<td>$ipaddr <span class='badge' style='background:#f8ac59;color:#fff'>kernel</span></td>";
            $html[]="<td>".addr_type_badge($addrType)."</td>";
            $html[]="<td>$gateway</td>";
            $html[]="<td>$cdir</td>";
            $html[]="<td><center>{$delete}</center></td>";
            $html[]="</tr>";
        }
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr><td colspan='5'><ul class='pagination pull-right'></ul></td></tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
		<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('#table-$eth').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
		</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function interface_popup(){
    $tpl=new template_admin();
    $security="AsSystemAdministrator";
    $nicid=intval($_GET["nic-ipv6-popup"]);
    $eth=$_GET["eth"];
    $page=CurrentPageName();
    $btn="{add}";
    $gateway="";
    $ipaddr="";
    $cdir=64;
    $addrType="ula";

    if($nicid>0){
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM nics_ipv6 WHERE ID=$nicid");
        $btn="{apply}";
        $ipaddr=$ligne["ipaddr"] ?? "";
        $cdir=intval($ligne["cdir"] ?? 64);
        $gateway=$ligne["gateway"] ?? "";
        $addrType=$ligne["addr_type"] ?? "";
        if($addrType==""){
            $addrType=classify_ipv6_php($ipaddr);
        }
    }

    $afterJs="dialogInstance3.close();LoadAjax('ipv6Table$eth','$page?table=$eth')";

    // Address type selector
    $addrTypes=["ula"=>"ULA ({private})", "gua"=>"GUA ({global})", "link-local"=>"Link-Local"];
    $form[]=$tpl->field_hidden("ID",$nicid);
    $form[]=$tpl->field_hidden("eth",$eth);
    $form[]=$tpl->field_array_hash($addrTypes,"addr_type","IPv6 {type}",$addrType);

    // Wizard button row — use getElementsByName since field_array_hash uses md5 IDs
    $wizBtnJs="Loadjs('$page?ipv6-wizard-js=yes&eth=$eth&addr_type='+(document.getElementsByName('addr_type')[0]||{value:'ula'}).value)";
    $form[]="<div style='text-align:right;margin:-5px 0 10px 0'>";
    $form[]="<button type='button' class='btn btn-info btn-sm' onclick=\"$wizBtnJs\">";
    $form[]="<i class='fas fa-magic'></i> {wizard}";
    $form[]="</button>";
    $form[]="</div>";

    $form[]=$tpl->field_text("ipaddr","IPv6 {tcp_address}",$ipaddr);
    $form[]=$tpl->field_numeric("cdir","IPv6 {netmask}",$cdir);

    // Gateway row (hidden for link-local via JS)
    $gwStyle=($addrType=="link-local") ? "display:none" : "";
    $form[]="<div id='gateway-row' style='$gwStyle'>".$tpl->field_text("gateway","IPv6 {gateway}",$gateway)."</div>";

    $form[]=$tpl->field_checkbox("apply","{apply_now}",1);

    $html[]=$tpl->form_outside(null, $form,null,$btn,$afterJs,$security);

    // JavaScript for addr_type change behavior
    // field_array_hash generates id=md5(name+suffixid), so use getElementsByName
    $js=[];
    $js[]="<script>";
    $js[]="(function(){";
    $js[]="  function gn(n){return document.getElementsByName(n)[0];}";
    $js[]="  var sel=gn('addr_type');";
    $js[]="  if(sel){ sel.onchange=function(){";
    $js[]="    var t=this.value;";
    $js[]="    var gw=document.getElementById('gateway-row');";
    $js[]="    if(gw){ gw.style.display=(t=='link-local')?'none':''; }";
    $js[]="  };}";
    $js[]="})();";
    $js[]="</script>";
    $html[]=implode("\n",$js);

    echo $tpl->_ENGINE_parse_body($html);
}

// ─────────────────────────────────────────────────────
//  AJAX helpers
// ─────────────────────────────────────────────────────

function generate_ipv6(){
    header("content-type: application/json");
    $type=$_GET["generate-ipv6"];
    $eth=$_GET["eth"] ?? "";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/ip6gen/$eth/$type");
    echo $data;
}

// ─────────────────────────────────────────────────────
//  IPv6 utilities (PHP-side)
// ─────────────────────────────────────────────────────

function classify_ipv6_php($ip){
    if($ip==null || $ip==""){return "";}
    $parts=explode("/",$ip);
    $ip=$parts[0];
    $bin=inet_pton($ip);
    if($bin===false){return "";}
    $firstByte=ord($bin[0]);
    if($firstByte==0xfe && (ord($bin[1]) & 0xc0)==0x80){return "link-local";}
    if(($firstByte & 0xfe)==0xfc){return "ula";}
    if(($firstByte & 0xe0)==0x20){return "gua";}
    return "";
}

function validate_ipv6($addr){
    if($addr==null || trim($addr)==""){return false;}
    $parts=explode("/",trim($addr));
    $ip=$parts[0];
    if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
        return false;
    }
    return true;
}

// ─────────────────────────────────────────────────────
//  Save handler
// ─────────────────────────────────────────────────────

function interface_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $eth=trim($_POST["eth"] ?? "");
    $ipaddr=trim($_POST["ipaddr"] ?? "");
    $cdir=intval($_POST["cdir"] ?? 64);
    $gateway=trim($_POST["gateway"] ?? "");
    $addrType=trim($_POST["addr_type"] ?? "");
    $apply=intval($_POST["apply"] ?? 0);
    $ID=intval($_POST["ID"] ?? 0);

    // Validate IPv6 address
    if(!validate_ipv6($ipaddr)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars("$ipaddr: {not_valid_ipv6_address}")));
        return false;
    }

    // Strip prefix from ipaddr if user entered it
    if(strpos($ipaddr,"/")!==false){
        $parts=explode("/",$ipaddr);
        $ipaddr=$parts[0];
        $cdir=intval($parts[1]);
    }

    // Strip prefix from gateway if present
    if($gateway!="" && strpos($gateway,"/")!==false){
        $parts=explode("/",$gateway);
        $gateway=$parts[0];
    }

    // Validate prefix length
    if($cdir<1 || $cdir>128){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{netmask}: $cdir {not_valid} (1-128)"));
        return false;
    }

    // Validate gateway if provided (and not link-local)
    if($gateway!="" && $addrType!="link-local"){
        if(!validate_ipv6($gateway)){
            echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars("$gateway: {gateway} {not_valid_ipv6_address}")));
            return false;
        }
    }

    // Link-local addresses don't need a gateway
    if($addrType=="link-local"){
        $gateway="";
    }

    // Auto-classify if not provided
    if($addrType==""){
        $addrType=classify_ipv6_php($ipaddr);
    }

    $payload=[
        "ipaddr"    => $ipaddr,
        "cdir"      => $cdir,
        "gateway"   => $gateway,
        "addr_type" => $addrType,
        "apply"     => ($apply==1),
    ];

    if($ID==0){
        $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/network/ip6/$eth", $payload);
    }else{
        $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/system/network/ip6/$eth/$ID", $payload);
    }

    $json=json_decode($data);
    if(is_object($json) && property_exists($json,"Status") && !$json->Status){
        $error=property_exists($json,"Error") ? $json->Error : "Unknown error";
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($error)));
        return false;
    }

    return admin_tracks_post("Saving Ipv6 address $ipaddr/$cdir type=$addrType on $eth");
}
