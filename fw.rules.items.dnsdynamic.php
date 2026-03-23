<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();
if(!$users->AsFirewallManager){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    exit();
}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["buttons"])){table_buttons();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["add-js"])){add_js();exit;}
if(isset($_GET["add-popup"])){add_popup();exit;}
if(isset($_GET["edit-js"])){edit_js();exit;}
if(isset($_GET["edit-popup"])){edit_popup();exit;}
if(isset($_POST["save-rule"])){save_rule();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete-rule"])){delete_perform();exit;}
if(isset($_GET["enable-toggle"])){enable_toggle();exit;}
build_page();

function _ensure_table(){
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS cognixsys_rules (
        ID INTEGER PRIMARY KEY AUTOINCREMENT,
        gpid INTEGER NOT NULL DEFAULT 0,
        rulename TEXT NOT NULL DEFAULT 'New Rule',
        enabled INTEGER NOT NULL DEFAULT 1,
        params TEXT NOT NULL DEFAULT '{}',
        created TEXT,
        modified TEXT
    )");
    return $q;
}

function _default_params():string{
    $cfg=array(
        "log_file"=>"/var/log/cognix-systems/ipset_ssh_v4.log",
        "zones"=>array(
            array(
                "zone_name"=>"ssh.zone",
                "dns_servers"=>array(
                    array("address"=>"","label"=>"master"),
                    array("address"=>"","label"=>"slave")
                ),
                "domains"=>array(),
                "ssh_allow_file"=>"/etc/cs_sshallow"
            )
        )
    );
    return json_encode($cfg,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
}

function build_page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $f=array();
    $groupid=$_GET["gpid"];
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $RefreshFunction=$_GET["RefreshFunction"];
    $f[]="<div id='buttons-cognixsys-$groupid' style='margin-top:10px;margin-bottom: 10px'></div>";
    $f[]=$tpl->search_block($page,"","","","&gpid=$groupid&js-after=$jsafter&function1=$function&RefreshFunction=$RefreshFunction");
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
}

function table_buttons(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $jsafter=$_GET["js-after"];
    $function1=$_GET["function1"];
    $RefreshFunction=$_GET["RefreshFunction"];
    $gpid=$_GET["buttons"];
    $topbuttons[] = array("Loadjs('$page?add-js=$gpid&function=$function&js-after=$jsafter&function1=$function1&RefreshFunction=$RefreshFunction&function=$function')",ico_plus,"{new_rule}");
    echo $tpl->th_buttons($topbuttons);
}

function search(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=_ensure_table();
    $groupid=intval($_GET["gpid"]);
    $jsafter=$_GET["js-after"];
    $function1=$_GET["function1"];
    $function=$_GET["function"];

    $RefreshFunction=$_GET["RefreshFunction"];
    $results=$q->QUERY_SQL("SELECT * FROM cognixsys_rules WHERE gpid=$groupid ORDER BY ID");
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($q->mysql_error)));
        return;
    }

    $t=time();
    $html=array();
    $html[]="<div id='fetchrules-list'></div>";
    $html[]="<table id='table-$t' class='table table-stripped' data-page-size='100'>";
    $html[]="<thead><tr>";
    $html[]="<th data-sortable=true data-type='text'>ID</th>";
    $html[]="<th data-sortable=true data-type='text'>{rule_name}</th>";
    $html[]="<th data-sortable=false>{status}</th>";
    $html[]="<th data-sortable=false>{edit}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr></thead><tbody>";

    $TRCLASS=null;
    $count=0;
    foreach($results as $ligne){
        if(!is_array($ligne)){continue;}
        $ID=intval($ligne["ID"]);
        if($ID==0){continue;}
        $count++;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $rulename=htmlspecialchars($ligne["rulename"]);
        $enabled=intval($ligne["enabled"]);

        $params=json_decode($ligne["params"],true);
        $zone_count=0;
        $zone_names=array();
        if(is_array($params) && isset($params["zones"]) && is_array($params["zones"])){
            $zone_count=count($params["zones"]);
            foreach($params["zones"] as $z){
                if(isset($z["zone_name"])){$zone_names[]=htmlspecialchars($z["zone_name"]);}
            }
        }
        $zones_text=$zone_count>0 ? implode(", ",$zone_names)." ($zone_count)" : "-";

        if($enabled==1){
            $enable_badge="<span class='label' style='background:#1ab394;color:#fff'>{enabled}</span>";
        }else{
            $enable_badge="<span class='label' style='background:#676a6c;color:#fff'>{disabled}</span>";
        }
        $enable_click="Loadjs('$page?enable-toggle=$ID&function=$function');";

        $md=md5(serialize($ligne));
        $edit=$tpl->icon_edit_field("Loadjs('$page?edit-js=$ID');","AsFirewallManager");
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md');","AsFirewallManager");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$ID</td>";
        $html[]="<td>".$tpl->td_href("<strong>$rulename</strong>","{edit}","Loadjs('$page?edit-js=$ID&groupid=$groupid&js-after=$jsafter&function1=$function1&RefreshFunction=$RefreshFunction&function=$function');")."</td>";
        $html[]="<td style='cursor:pointer;width:1%' OnClick=\"$enable_click\" nowrap>$enable_badge</td>";
        $html[]="<td style='width:1%' nowrap>$edit</td>";
        $html[]="<td style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";
    }

    if($count==0){
        $html[]="<tr><td colspan='6' class='text-center text-muted'><em>{no_data}</em></td></tr>";
    }

    $html[]="</tbody><tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot></table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="LoadAjaxSilent('buttons-cognixsys-$groupid','$page?buttons=$groupid&js-after=$jsafter&function1=$function1&RefreshFunction=$RefreshFunction&function=$function');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}

function add_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=$_GET["add-js"];
    $function=$_GET["function"];
    $jsafter=$_GET["js-after"];
    $function1=$_GET["function1"];
    $RefreshFunction=$_GET["RefreshFunction"];
    $tpl->js_dialog5("{new_rule}","$page?add-popup=$gpid&function=$function&js-after=$jsafter&function1=$function1&RefreshFunction=$RefreshFunction&function=$function",750);
}

function edit_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["edit-js"]);
    $groupid=$_GET["groupid"];
    $function=$_GET["function"];
    $jsafter=$_GET["js-after"];
    $function1=$_GET["function1"];
    $RefreshFunction=$_GET["RefreshFunction"];
    $tpl->js_dialog5("{edit} {rule} #$ID","$page?edit-popup=$ID&groupid=$groupid&js-after=$jsafter&function1=$function1&RefreshFunction=$RefreshFunction&function=$function",750);
}

function _build_form(string $rulename, int $enabled, array $params, int $ID=0):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(isset($_GET["add-popup"])){
        $gpid=$_GET["add-popup"];
    }
    if(isset($_GET["groupid"])){
        $gpid=$_GET["groupid"];
    }


    $function=$_GET["function"];
    $jsafter=$_GET["js-after"];
    $function1=$_GET["function1"];
    $RefreshFunction=$_GET["RefreshFunction"];
    $zname="Group$gpid";

    $log_file=isset($params["log_file"]) ? $params["log_file"] : "/var/log/cognix-systems/ipset_ssh_v4.log";
    $zones=isset($params["zones"]) && is_array($params["zones"]) ? $params["zones"] : array();
    if(count($zones)==0){
        $zones[]=array(
            "zone_name"=>"ssh.zone",
            "dns_servers"=>array(
                array("address"=>"","label"=>"master"),
                array("address"=>"","label"=>"slave")
            ),
            "domains"=>array(),
            "ssh_allow_file"=>"/etc/cs_sshallow"
        );
    }

    $f=array();
    $f[]="<input type='hidden' id='fr-id' value='$ID'>";
    $f[]="<input type='hidden' id='fr-logfile' value='/var/log/cognix-systems/ipset_ssh_v4.log'>";
    $f[]="<div class='row'><div class='col-lg-12'>";

    // Rule metadata
    $f[]="<div class='ibox'><div class='ibox-title'><h5>{parameters}</h5></div><div class='ibox-content'>";
    $f[]="<div class='form-group'><label>{rule_name}</label>";
    $f[]="<input type='text' class='form-control' id='fr-rulename' value='".htmlspecialchars($rulename)."' placeholder='{rule_name}'></div>";
    $f[]="<div class='form-group'><label>{enabled}</label><div>";
    $checked=$enabled==1 ? "checked" : "";
    $f[]="<label class='switch'><input type='checkbox' id='fr-enabled' $checked><span class='slider round'></span></label>";
    $f[]="</div></div>";
    $f[]="</div>";

    // Zones
    $f[]="<div id='fr-zones-container'>";
    foreach($zones as $zi=>$zone){
        $f[]=_build_zone_panel($zi,$zone);
    }
    $f[]="</div>";
    $f[]="<button class='btn btn-sm btn-default' style='margin-bottom:15px' OnClick=\"frAddZone();\"><i class='fas fa-plus'></i> {add} Zone</button>";

    $f[]="</div></div>";

    // Save button + JS
    $save_label=$ID>0 ? "{apply}" : "{add}";
    $f[]="<div class='text-right' style='margin-top:10px'>";
    $f[]="<button class='btn btn-primary' OnClick=\"frSave();\"><i class='fas fa-save'></i> $save_label</button></div>";
    $f[]="</div>";
    $f[]="<script>";
    $f[]="var frZoneIndex=".count($zones).";";
    $f[]="function frAddZone(){";
    $f[]="  var html=frZonePanelHtml(frZoneIndex,'','','','','','');";
    $f[]="  document.getElementById('fr-zones-container').insertAdjacentHTML('beforeend',html);";
    $f[]="  frZoneIndex++;";
    $f[]="};";
    $f[]="function frRemoveZone(idx){ var el=document.getElementById('fr-zone-'+idx); if(el) el.remove(); };";
    $f[]="function frAddDns(zi){";
    $f[]="  var c=document.getElementById('fr-dns-list-'+zi);";
    $f[]="  var idx=c.children.length;";
    $f[]="  var html='<div class=\"form-inline\" style=\"margin-bottom:5px\">';";
    $f[]="  html+='<input type=\"text\" class=\"form-control\" style=\"width:200px;margin-right:5px\" placeholder=\"IP address\" data-dns-addr=\"'+zi+'\">';";
    $f[]="  html+='<input type=\"text\" class=\"form-control\" style=\"width:150px;margin-right:5px\" placeholder=\"Label\" data-dns-label=\"'+zi+'\">';";
    $f[]="  html+='<button class=\"btn btn-xs btn-danger\" onclick=\"this.parentNode.remove();\"><i class=\"fas fa-times\"></i></button>';";
    $f[]="  html+='</div>';";
    $f[]="  c.insertAdjacentHTML('beforeend',html);";
    $f[]="};";
    $f[]="function frZonePanelHtml(idx,zname,dnsHtml,domainsStr,sshFile,dnsList){";
    $f[]="  if(!domainsStr) domainsStr='';";
    $f[]="  if(!sshFile) sshFile='/etc/cs_sshallow';";
    $f[]="  var h=\"<div class='ibox' id='fr-zone-\"+idx+\"'>\";";
    $f[]="  h+=\"<div class='ibox-title'><h5>Zone #\"+(idx+1)+\"</h5>\";";
    $f[]="  h+=\"<div class='ibox-tools'><button class='btn btn-xs btn-danger' onclick='frRemoveZone(\"+idx+\");'><i class='fas fa-trash'></i></button></div></div>\";";
    $f[]="  h+=\"<div class='ibox-content'>\";";
    $f[]="  h+=\"<input type='hidden' id='fr-zname-\"+idx+\"' value='$zname'>\";";
    $f[]="  h+=\"<div class='form-group'><label>{dns_servers}</label>\";";
    $f[]="  h+=\"<div id='fr-dns-list-\"+idx+\"'>\";";
    $f[]="  if(dnsList){ h+=dnsList; }";
    $f[]="  h+=\"</div>\";";
    $f[]="  h+=\"<button class='btn btn-xs btn-default' style='margin-top:5px' onclick='frAddDns(\"+idx+\");'><i class='fas fa-plus'></i> DNS Server</button></div>\";";
    $f[]="  h+=\"<div class='form-group'><label>{domains} ({one_per_line})</label>\";";
    $f[]="  h+=\"<textarea class='form-control' id='fr-domains-\"+idx+\"' rows='4' placeholder='e.g. bradbury.&#10;lovecraft.'>\"+domainsStr+\"</textarea></div>\";";
    $f[]="  h+=\"<input type='hidden' id='fr-sshfile-\"+idx+\"' value='\"+sshFile+\"'>\";";
    $f[]="  h+=\"</div></div>\";";
    $f[]="  return h;";
    $f[]="};";

    $js[]="$function();";
    if(strlen($function1)>2){
        $js[]="$function1();";
    }
    if(strlen($jsafter)>2){
        $js[]="$jsafter";
    }
    if(strlen($RefreshFunction)>2){
        $js[]=base64_decode($RefreshFunction)."();";
    }
    $tjs=@implode(";", $js);

    $f[]="function frSave(){";
    $f[]="  var id=document.getElementById('fr-id').value;";
    $f[]="  var rulename=document.getElementById('fr-rulename').value;";
    $f[]="  if(!rulename||rulename.trim().length==0){ swal({title:'{error}',text:'{rule_name} {required}',type:'error'}); return; }";
    $f[]="  var enabled=document.getElementById('fr-enabled').checked?1:0;";
    $f[]="  var logfile=document.getElementById('fr-logfile').value;";
    $f[]="  var zones=[];";
    $f[]="  var panels=document.getElementById('fr-zones-container').children;";
    $f[]="  for(var i=0;i<panels.length;i++){";
    $f[]="    var p=panels[i]; var idx=p.id.replace('fr-zone-','');";
    $f[]="    var zn=document.getElementById('fr-zname-'+idx);";
    $f[]="    if(!zn) continue;";
    $f[]="    var zone_name=zn.value;";
    $f[]="    if(!zone_name||zone_name.trim().length==0) continue;";
    $f[]="    var dns_servers=[];";
    $f[]="    var addrs=p.querySelectorAll('[data-dns-addr=\"'+idx+'\"]');";
    $f[]="    var labels=p.querySelectorAll('[data-dns-label=\"'+idx+'\"]');";
    $f[]="    for(var d=0;d<addrs.length;d++){";
    $f[]="      var addr=addrs[d].value.trim(); var lbl=labels[d]?labels[d].value.trim():'';";
    $f[]="      if(addr.length>0){ dns_servers.push({address:addr,label:lbl}); }";
    $f[]="    }";
    $f[]="    var domTA=document.getElementById('fr-domains-'+idx);";
    $f[]="    var domains=domTA?domTA.value.split('\\n').map(function(s){return s.trim();}).filter(function(s){return s.length>0;}):[];";
    $f[]="    var sshFile=document.getElementById('fr-sshfile-'+idx);";
    $f[]="    zones.push({zone_name:zone_name,dns_servers:dns_servers,domains:domains,ssh_allow_file:sshFile?sshFile.value:''});";
    $f[]="  }";
    $f[]="  if(zones.length==0){ swal({title:'{error}',text:'At least one zone is required',type:'error'}); return; }";
    $f[]="  var params=JSON.stringify({log_file:logfile,zones:zones});";
    $f[]="  $.post('$page',{'save-rule':1,id:id,rulename:rulename,enabled:enabled,params:params,gpid:$gpid},function(data){";
    $f[]="    try{ var r=JSON.parse(data); }catch(e){ document.getElementById('fetchrules-list').innerHTML=data; return; }";
    $f[]="    if(r.success){ dialogInstance5.close(); $tjs; }";
    $f[]="    else{ swal({title:'{error}',text:r.error||'Unknown error',type:'error'}); }";
    $f[]="  });";
    $f[]="};";
    $f[]="</script>";

    // Toggle switch CSS
    $f[]="<style>";
    $f[]=".switch{position:relative;display:inline-block;width:50px;height:26px}";
    $f[]=".switch input{opacity:0;width:0;height:0}";
    $f[]=".slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:#ccc;transition:.3s;border-radius:26px}";
    $f[]=".slider:before{position:absolute;content:'';height:20px;width:20px;left:3px;bottom:3px;background-color:#fff;transition:.3s;border-radius:50%}";
    $f[]="input:checked+.slider{background-color:#1ab394}";
    $f[]="input:checked+.slider:before{transform:translateX(24px)}";
    $f[]="</style>";

    return implode("\n",$f);
}

function _build_zone_panel(int $zi, array $zone):string{

    if(isset($_GET["add-popup"])){
        $gpid=$_GET["add-popup"];
    }
    if(isset($_GET["groupid"])){
        $gpid=$_GET["groupid"];
    }
    $zone_name="Group$gpid";


    $dns_servers=isset($zone["dns_servers"]) && is_array($zone["dns_servers"]) ? $zone["dns_servers"] : array();
    $domains=isset($zone["domains"]) && is_array($zone["domains"]) ? $zone["domains"] : array();
    $ssh_allow_file=isset($zone["ssh_allow_file"]) ? htmlspecialchars($zone["ssh_allow_file"]) : "/etc/cs_sshallow";
    $domains_str=htmlspecialchars(implode("\n",$domains));

    $h=array();
    $h[]="<div class='ibox' id='fr-zone-$zi'>";
    $h[]="<div class='ibox-title'><h5>Zone #".($zi+1)."</h5>";
    $h[]="<div class='ibox-tools'><button class='btn btn-xs btn-danger' onclick='frRemoveZone($zi);'><i class='fas fa-trash'></i></button></div></div>";
    $h[]="<div class='ibox-content'>";
    $h[]="<input type='hidden' class='form-control' id='fr-zname-$zi' value='$zone_name'>";
    $h[]="<div class='form-group'><label>DNS Servers</label>";
    $h[]="<div id='fr-dns-list-$zi'>";
    if(count($dns_servers)==0){
        $dns_servers[]=array("address"=>"","label"=>"master");
        $dns_servers[]=array("address"=>"","label"=>"slave");
    }
    foreach($dns_servers as $di=>$srv){
        $addr=htmlspecialchars($srv["address"] ?? "");
        $label=htmlspecialchars($srv["label"] ?? "");
        $h[]="<div class='form-inline' style='margin-bottom:5px'>";
        $h[]="<input type='text' class='form-control' style='width:200px;margin-right:5px' placeholder='IP address' data-dns-addr='$zi' value='$addr'>";
        $h[]="<input type='text' class='form-control' style='width:150px;margin-right:5px' placeholder='Label' data-dns-label='$zi' value='$label'>";
        $h[]="<button class='btn btn-xs btn-danger' onclick='this.parentNode.remove();'><i class='fas fa-times'></i></button>";
        $h[]="</div>";
    }
    $h[]="</div>";
    $h[]="<button class='btn btn-xs btn-default' style='margin-top:5px' onclick='frAddDns($zi);'><i class='fas fa-plus'></i> DNS Server</button></div>";

    $h[]="<div class='form-group'><label>{domains} ({one_per_line})</label>";
    $h[]="<textarea class='form-control' id='fr-domains-$zi' rows='4' placeholder='e.g. bradbury.&#10;lovecraft.'>$domains_str</textarea></div>";
    $h[]="<input type='hidden' class='form-control' id='fr-sshfile-$zi' value='$ssh_allow_file'>";
    $h[]="</div></div>";
    return implode("\n",$h);
}

function add_popup():bool{
    $tpl=new template_admin();
    $params=json_decode(_default_params(),true);
    $html=_build_form("{new_rule}",1,$params,0);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function edit_popup():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["edit-popup"]);
    if($ID==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("ID == 0"));
        return false;
    }
    $q=_ensure_table();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM cognixsys_rules WHERE ID=$ID");
    if(!is_array($ligne) || intval($ligne["ID"])==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{rule} #$ID {not_found}"));
        return false;
    }
    $params=json_decode($ligne["params"],true);
    if(!is_array($params)){$params=json_decode(_default_params(),true);}
    $html=_build_form($ligne["rulename"],intval($ligne["enabled"]),$params,$ID);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function save_rule(){
    header("Content-Type: application/json");
    $q=_ensure_table();
    $ID=intval($_POST["id"] ?? 0);
    $rulename=trim($_POST["rulename"] ?? "");
    $enabled=intval($_POST["enabled"] ?? 1);
    $params_raw=$_POST["params"] ?? "{}";
    $gpid=intval($_POST["gpid"] ?? 0);
    if(strlen($gpid)==0){
        echo json_encode(array("success"=>false,"error"=>"Group ID is required"));
        return;
    }
    if(strlen($rulename)==0){
        echo json_encode(array("success"=>false,"error"=>"Rule name is required"));
        return;
    }

    $params=json_decode($params_raw,true);
    if(!is_array($params)){
        echo json_encode(array("success"=>false,"error"=>"Invalid JSON in params"));
        return;
    }
    $params_json=json_encode($params,JSON_UNESCAPED_SLASHES);
    $now=date("Y-m-d H:i:s");
    $rulename_esc=SQLite3::escapeString($rulename);
    $params_esc=SQLite3::escapeString($params_json);

    if($ID>0){
        $q->QUERY_SQL("UPDATE cognixsys_rules SET rulename='$rulename_esc', enabled=$enabled, params='$params_esc', modified='$now' WHERE ID=$ID");
    }else{
        $q->QUERY_SQL("INSERT INTO cognixsys_rules (gpid,rulename,enabled,params,created,modified) VALUES ($gpid,'$rulename_esc',$enabled,'$params_esc','$now','$now')");
    }

    if(!$q->ok){
        echo json_encode(array("success"=>false,"error"=>$q->mysql_error));
        return;
    }

    $action=$ID>0 ? "Edit" : "Create";
    admin_tracks("$action CognixSys fetch rule: $rulename for group #$gpid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/cognixsys/build");
    echo json_encode(array("success"=>true));
}

function delete_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["delete-js"]);
    $md=$_GET["md"];
    $q=_ensure_table();
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM cognixsys_rules WHERE ID=$ID");
    $name=htmlspecialchars($ligne["rulename"] ?? "#$ID");
    return $tpl->js_confirm_delete($name,"delete-rule",base64_encode($ID),
        "$('#$md').remove();");
}

function delete_perform():bool{
    $tpl=new template_admin();
    $ID=intval(base64_decode($_POST["delete-rule"]));
    if($ID==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("ID == 0"));
        return false;
    }
    $q=_ensure_table();
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM cognixsys_rules WHERE ID=$ID");
    $q->QUERY_SQL("DELETE FROM cognixsys_rules WHERE ID=$ID");
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($q->mysql_error)));
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/cognixsys/build");
    return admin_tracks("Delete CognixSys fetch rule: ".($ligne["rulename"] ?? "#$ID"));
}

function enable_toggle():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["enable-toggle"]);
    $function=$_GET["function"];
    if($ID==0){
        return $tpl->js_error("ID == 0");
    }
    $q=_ensure_table();
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM cognixsys_rules WHERE ID=$ID");
    $current=intval($ligne["enabled"]);
    $new=$current==1 ? 0 : 1;
    $q->QUERY_SQL("UPDATE cognixsys_rules SET enabled=$new, modified='".date("Y-m-d H:i:s")."' WHERE ID=$ID");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $state=$new==1 ? "enabled" : "disabled";

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/cognixsys/build");
    header("content-type: application/x-javascript");
    echo "$function();\n";
    return admin_tracks("CognixSys fetch rule #$ID $state");
}
