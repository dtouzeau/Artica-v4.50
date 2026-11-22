<?php
// Post SP10 - 13/10/2026 -- refonte Stitch 2026-11-19 (partage de connexion Internet)
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.confirm.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
// Verrou de la politique Artica Meta "firewall" : masquerade tenu = lecture seule.
include_once(dirname(__FILE__)."/ressources/class.firewall.meta.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ifname-js"])){ifname_js();exit;}
if(isset($_GET["ifname-tabs"])){ifname_tabs();exit;}
if(firewall_meta_holds("masquerade")){
    if(isset($_GET["ifname-enable"]) || isset($_GET["ifname-confirm-js"])){firewall_meta_refuse_js();exit;}
    if(isset($_POST["src-net"]) || isset($_POST["dst-net"])){echo firewall_meta_refuse_post();exit;}
}
if(isset($_GET["ifname-confirm-js"])){ifname_confirm_js();exit;}
if(isset($_GET["ifname-enable"])){ifname_enable();exit;}
if(isset($_POST["src-net"])){src_nets_save();exit;}
if(isset($_GET["src-nets"])){src_nets();exit;}
if(isset($_GET["dst-nets"])){dst_nets();exit;}
if(isset($_POST["dst-net"])){dst_nets_save();exit;}
page();

// Un nom d'interface est un identifiant systeme : liste blanche a l'entree.
function masq_ifname($key):string{
    $v=isset($_GET[$key])?(string)$_GET[$key]:"";
    return preg_replace('#[^A-Za-z0-9_.:@-]#','',$v);
}

// Rend l'ID du masquerade de l'interface ; le daemon le cree desactive s'il
// n'existe pas encore.
function masq_ensure_id($ifname,$ID){
    if($ID>0){return $ID;}
    $data=fwapi_data(fwapi_send("POST","masquerade",array("nic"=>$ifname)));
    return is_array($data)?intval($data["id"] ?? 0):0;
}

function msq_t($token,$a="",$b=""):string{
    if(!isset($GLOBALS["MSQ_T"])){$GLOBALS["MSQ_T"]=array();}
    if(!isset($GLOBALS["MSQ_T"][$token])){
        $tpl=new template_admin();
        $GLOBALS["MSQ_T"][$token]=$tpl->_ENGINE_parse_body("{"."$token}");
    }
    return str_replace(array("%1","%2"),array($a,$b),$GLOBALS["MSQ_T"][$token]);
}

function msq_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

function msq_held():bool{
    return firewall_meta_holds("masquerade");
}

function ifname_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ifname=masq_ifname("ifname-js");
    if($ifname===""){return;}
    // Masquerade tenu par Artica Meta : pas de creation de ligne (POST /masquerade).
    if(msq_held() && intval($_GET["id"] ?? 0)<1){firewall_meta_refuse_js();return;}
    $ID=masq_ensure_id($ifname,intval($_GET["id"] ?? 0));
    if($ID===0){$tpl->js_error("{failed}");return;}
    $tpl->js_dialog(msq_t("msq_dialog_title",msq_h($ifname)),"$page?ifname-tabs=$ID");
}

function ifname_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ifname-tabs"]);
    $array=array();
    $array["{msq_tab_src}"]="$page?src-nets=$ID";
    $array["{msq_tab_dst}"]="$page?dst-nets=$ID";
    echo $tpl->tabs_default($array);
}

// Confirmation avant la bascule : la consequence est dite avant l'action.
function ifname_confirm_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $ifname=masq_ifname("ifname-confirm-js");
    if($ifname===""){return;}
    $ID=intval($_GET["id"] ?? 0);
    $want=intval($_GET["want"] ?? 0)==1?1:0;
    $znic=new system_nic($ifname);
    $ip=trim((string)$znic->IPADDR);
    if($want==1){
        $title=msq_t("msq_confirm_on_title",$ifname);
        $text=$ip!==""?msq_t("msq_confirm_on",msq_h($ip),msq_h($ifname)):msq_t("msq_confirm_on_noip",msq_h($ifname));
        $btn=msq_t("msq_turn_on");$theme="white";
    }else{
        $title=msq_t("msq_confirm_off_title",$ifname);
        $text=msq_t("msq_confirm_off",msq_h($ifname));
        $btn=msq_t("msq_turn_off");$theme="red";
    }
    $text=str_replace("[br]","<br>",$text);
    $cancel=$tpl->javascript_parse_text("{cancel}");
    $url="$page?ifname-enable=".rawurlencode($ifname)."&id=$ID&want=$want";
    echo "$.confirm({theme:'$theme-theme',containerFluid:true,icon:'fas fa-shuffle',"
        ."title:".json_encode(strip_tags($title)).",content:".json_encode($text).","
        ."buttons:{ok:{text:".json_encode($btn).",action:function(){Loadjs('$url');}},cancel:{text:".json_encode($cancel)."}}});";
}

function ifname_enable(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $ifname=masq_ifname("ifname-enable");
    if($ifname===""){return;}
    $ID=masq_ensure_id($ifname,intval($_GET["id"] ?? 0));
    if($ID===0){$tpl->js_error("{failed}");return;}
    $m=fwapi_data(fwapi_get("masquerade/$ID"));
    if(!is_array($m)){$tpl->js_error("{fw_masquerade_not_found}");return;}
    // L'etat voulu voyage avec la demande : deux clics croises ne s'annulent plus.
    $want=isset($_GET["want"])?(intval($_GET["want"])==1?1:0):(intval($m["enabled"] ?? 0)==1?0:1);
    $e=fwapi_error(fwapi_send("POST","masquerade/$ID/enable",array("enabled"=>$want)));
    if($e!=""){$tpl->js_error($e);return;}
    admin_tracks("Masquerade: interface $ifname set to enabled=$want");
    echo "LoadAjaxSilent('table-masquerade','$page?table=yes');\n";
}

// Le champ ID cache est dans $form : sans lui l'enregistrement visait l'ID 0.
function masq_nets_form($tpl,$field,$explain,$ID,$values,$note=""):string{
    $page=CurrentPageName();
    $form=array();
    $form[]=$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_textareacode($field,null,@implode("\n",$values));
    $metaBanner=firewall_meta_banner("masquerade",$tpl);
    if($metaBanner!==""){firewall_meta_lock_form($tpl);}
    return $metaBanner.$note.$tpl->_ENGINE_parse_body(
        $tpl->form_outside(null,@implode("\n",$form),$explain,"{apply}",
            "LoadAjaxSilent('table-masquerade','$page?table=yes');","AsFirewallManager"));
}

// Une entree du daemon (/firewall/config/masquerade/{id}) : listes deja decodees.
function masq_entry($ID):array{
    $m=fwapi_data(fwapi_get("masquerade/".intval($ID)));
    return is_array($m)?$m:array();
}

// Les regles du pare-feu rattachees (MASQ:<id>) remplacent les listes quand le
// pare-feu est actif (afirewall masqueradeBuild).
function masq_rules_note($ID):string{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"))!=1){return "";}
    $names=msq_rules_by_masq();
    if(!isset($names[intval($ID)])){return "";}
    return "<div class='alert alert-warning' style='margin-bottom:10px'><i class='fas fa-list-check'></i>&nbsp;"
        .msq_t("msq_rules_override",msq_h(implode(", ",$names[intval($ID)])))."</div>";
}

// Toutes les lignes sont validees AVANT la moindre ecriture.
function masq_nets_save($tpl,$column,$field){
    $COLS=array("include_src"=>true,"exclude_dst"=>true);
    if(!isset($COLS[$column])){return;}
    $tpl->CLEAN_POST();
    $IP=new IP();
    $T=array();
    $bad=array();
    $raw=isset($_POST[$field])?(string)$_POST[$field]:"";
    foreach(explode("\n",$raw) as $line){
        $line=trim($line);
        if($line===""){continue;}
        if(!$IP->isIPAddressOrRange($line)){$bad[]=$line;continue;}
        $T[]=$line;
    }
    if(count($bad)>0){
        echo $tpl->post_error("{invalid_ip_address} ".@implode(", ",array_slice($bad,0,10)));
        return;
    }
    $ID=intval($_POST["ID"] ?? 0);
    if($ID<1){ echo $tpl->post_error("{failed}"); return; }
    $e=fwapi_error(fwapi_send("PUT","masquerade/$ID",array($column=>array_values($T))));
    if($e!=""){ echo $tpl->post_error($e); return; }
    admin_tracks("Masquerade: saved $column for rule $ID");
}

function src_nets(){
    $ID=intval($_GET["src-nets"]);
    $tpl=new template_admin();
    $m=masq_entry($ID);
    $l=is_array($m["include_src"] ?? null)?$m["include_src"]:array();
    echo masq_nets_form($tpl,"src-net","{msq_src_help}",$ID,$l,masq_rules_note($ID));
}
function dst_nets(){
    $ID=intval($_GET["dst-nets"]);
    $tpl=new template_admin();
    $m=masq_entry($ID);
    $l=is_array($m["exclude_dst"] ?? null)?$m["exclude_dst"]:array();
    echo masq_nets_form($tpl,"dst-net","{msq_dst_help}",$ID,$l,masq_rules_note($ID));
}
function dst_nets_save(){
    masq_nets_save(new template_admin(),"exclude_dst","dst-net");
}
function src_nets_save(){
    masq_nets_save(new template_admin(),"include_src","src-net");
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{msq_page_title}","fas fa-shuffle","{msq_page_explain}",
        "$page?table=yes","masquerade","progress-masquerade-restart",false,"table-masquerade");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{msq_page_title}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Modele : lu une seule fois, alimente tous les widgets ───────────────────

// Noms des regles actives rattachees a chaque entree (eth = MASQ:<id>).
function msq_rules_by_masq():array{
    if(isset($GLOBALS["MSQ_RULES"])){return $GLOBALS["MSQ_RULES"];}
    $out=array();
    $rules=fwapi_data(fwapi_get("rules"));
    if(is_array($rules)){
        foreach($rules as $r){
            if(!is_array($r) || intval($r["enabled"] ?? 0)!=1){continue;}
            if(!preg_match('#^MASQ:([0-9]+)$#',(string)($r["eth"] ?? ""),$re)){continue;}
            $out[intval($re[1])][]=(string)($r["rulename"] ?? "");
        }
    }
    $GLOBALS["MSQ_RULES"]=$out;
    return $out;
}

function msq_model():array{
    $m=array("ifaces"=>array(),"sharing"=>0,"error"=>"","known"=>true,"held"=>msq_held());
    $m["firewall_on"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"))==1;

    $entries=array();
    $resp=fwapi_get("masquerade");
    $list=fwapi_data($resp);
    if(!is_array($list)){
        $m["error"]=fwapi_error($resp);
        $list=array();
    }
    $m["known"]=$m["error"]==="";
    foreach($list as $e){
        if(!is_array($e)){continue;}
        $entries[(string)($e["nic"] ?? "")]=$e;
    }
    $bridges=array();
    $bl=fwapi_data(fwapi_get("bridges"));
    if(is_array($bl)){
        foreach($bl as $b){
            if(!is_array($b)){continue;}
            if(intval($b["masquerading"] ?? 0)!=1 || intval($b["enabled"] ?? 0)!=1){continue;}
            $bridges[(string)($b["nic_to"] ?? "")]=intval($b["id"] ?? 0);
        }
    }
    $rules=msq_rules_by_masq();

    $vlans=msq_vlans();
    $nic=new networking();
    $nicZ=$nic->Local_interfaces();
    if(!is_array($nicZ)){$nicZ=array();}
    foreach($nicZ as $ifname=>$ipaddress){
        // Un VLAN a sa propre ligne (son reglage vit dans nics_vlan, pas dans nics).
        if(isset($vlans[(string)$ifname])){continue;}
        $znic=new system_nic($ifname);
        if($znic->Bridged==1){continue;}
        if($znic->enabled==0){continue;}
        $e=$entries[$ifname] ?? array();
        $id=intval($e["id"] ?? 0);
        $i=array(
            "name"=>(string)$ifname,
            "label"=>trim((string)$znic->NICNAME),
            "ip"=>trim((string)$znic->IPADDR),
            "id"=>$id,
            "enabled"=>intval($e["enabled"] ?? 0)==1,
            "src"=>is_array($e["include_src"] ?? null)?array_values($e["include_src"]):array(),
            "dst"=>is_array($e["exclude_dst"] ?? null)?array_values($e["exclude_dst"]):array(),
            "rules"=>($id>0 && isset($rules[$id]))?$rules[$id]:array(),
            "forced"=>"",
            "bridge_id"=>0,
        );
        if(intval($znic->firewall_masquerade)==1){$i["forced"]="nic";}
        elseif(isset($bridges[$ifname])){$i["forced"]="bridge";$i["bridge_id"]=$bridges[$ifname];}
        $i["sharing"]=$i["forced"]!=="" || $i["enabled"];
        if($i["sharing"]){$m["sharing"]++;}
        $m["ifaces"][]=$i;
    }
    foreach($vlans as $name=>$v){
        $e=$entries[$name] ?? array();
        $id=intval($e["id"] ?? 0);
        $i=msq_vlan_iface($name,$v,$e,($id>0 && isset($rules[$id]))?$rules[$id]:array());
        if($i["sharing"]){$m["sharing"]++;}
        $m["ifaces"][]=$i;
    }
    return $m;
}

// VLAN actifs (EnableVLANs=1), indexes par le nom que pose le noyau : vlan<ID>.
// Masquerades.go applique leur propre case « masquerade » (nics_vlan).
function msq_vlans():array{
    $out=array();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVLANs"))!=1){return $out;}
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/vlan/list"),true);
    if(!is_array($j) || empty($j["Status"]) || !is_array($j["Interfaces"] ?? null)){return $out;}
    foreach($j["Interfaces"] as $v){
        if(!is_array($v) || intval($v["enabled"] ?? 0)!=1){continue;}
        $id=intval($v["ID"] ?? 0);
        if($id<1){continue;}
        $out["vlan$id"]=$v;
    }
    return $out;
}

// Une ligne VLAN : meme forme qu'une interface, reglage impose si la case
// masquerade du VLAN est cochee.
function msq_vlan_iface($name,array $v,array $e,array $rules):array{
    $id=intval($e["id"] ?? 0);
    $i=array(
        "name"=>(string)$name,
        "label"=>msq_t("msq_vlan_label",(string)intval($v["VLANid"] ?? 0),msq_h((string)($v["eth"] ?? ""))),
        "label_html"=>true,
        "ip"=>trim((string)($v["Ipaddr"] ?? "")),
        "id"=>$id,
        "enabled"=>intval($e["enabled"] ?? 0)==1,
        "src"=>is_array($e["include_src"] ?? null)?array_values($e["include_src"]):array(),
        "dst"=>is_array($e["exclude_dst"] ?? null)?array_values($e["exclude_dst"]):array(),
        "rules"=>$rules,
        "forced"=>"",
        "bridge_id"=>0,
        "vlan_id"=>intval($v["ID"] ?? 0),
        "vlan_eth"=>(string)($v["eth"] ?? ""),
    );
    if(intval($v["masquerade"] ?? 0)==1){$i["forced"]="vlan";}
    $i["sharing"]=$i["forced"]!=="" || $i["enabled"];
    return $i;
}

// ── Widgets (une fonction chacun) ──────────────────────────────────────────

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=msq_model();
    $h=array();
    // Modification du pare-feu en attente de confirmation (fwhistory) et verrou Meta.
    $h[]=firewall_confirm_banner();
    $h[]=firewall_meta_banner("masquerade",$tpl);
    $h[]="<div class='msq'>";
    $h[]=msq_hero();
    if($m["error"]!==""){
        $h[]="<div class='msq-alert'><i class='fas fa-circle-exclamation'></i><div>".msq_h(strip_tags($m["error"]))."</div></div>";
    }
    $h[]=msq_card($m);
    $h[]="</div>";

    $topbuttons=array();
    $topbuttons[]=array("LoadAjax('table-masquerade','$page?table=yes');",ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{msq_page_title}";
    $TINY["ICO"]="fas fa-shuffle";
    $TINY["EXPL"]="{msq_page_explain}";
    $TINY["URL"]="masquerade";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
    echo msq_style().$tpl->_ENGINE_parse_body(@implode("\n",$h))."<script>NoSpinner();$jstiny</script>";
}

function msq_hero():string{
    return "<div class='msq-hero'><div class='msq-hero-ico'><i class='fas fa-shuffle'></i></div><div class='msq-hero-body'>"
        ."<h3>{msq_hero_title} <span class='msq-hero-sub'>{msq_hero_sub}</span></h3><p>{msq_hero_text}</p><div class='msq-chips'>"
        ."<span class='msq-chip'><i class='fas fa-globe'></i> {msq_chip_one}</span>"
        ."<span class='msq-chip'><i class='fas fa-eye-slash'></i> {msq_chip_hidden}</span>"
        ."<span class='msq-chip'><i class='fas fa-ethernet'></i> {msq_chip_per_iface}</span>"
        ."<span class='msq-chip'><i class='fas fa-route'></i> {msq_chip_vpn}</span>"
        ."</div></div></div>";
}

function msq_tag($ico,$text,$cls="",$title=""):string{
    $t=$title!==""?" title=\"".msq_h($title)."\"":"";
    return "<span class='msq-tag $cls'$t><i class='$ico'></i><span class='msq-tag-t'>$text</span></span>";
}

// Liste courte : 3 entrees puis « +N more » (la liste complete en info-bulle).
function msq_short(array $l):array{
    $shown=array_slice($l,0,3);
    $more=count($l)-count($shown);
    return array(msq_h(implode(", ",$shown)),$more,implode(", ",$l));
}

// Ligne « ce qui est partage » : depend des sources reelles de la regle posee.
function msq_share_line(array $i,array $m):string{
    $c=array();
    // Daemon injoignable : l'etat de l'entree est inconnu, jamais affiche « Off ».
    if(!$m["known"] && $i["forced"]===""){
        return "<div class='msq-share'><span class='msq-none'>&mdash;</span></div>";
    }
    if(!$i["sharing"]){
        return "<div class='msq-share'><span class='msq-none'>{msq_not_shared}</span></div>";
    }
    if($i["forced"]!==""){
        $c[]=msq_tag("fas fa-users","{msq_all_computers}","");
        $c[]=msq_tag("fas fa-globe","{msq_all_destinations}","msq-tag-muted");
        return "<div class='msq-share'>".implode("",$c)."</div>";
    }
    if($m["firewall_on"] && count($i["rules"])>0){
        list($txt,$more,$all)=msq_short($i["rules"]);
        $label=msq_t("msq_by_rules",$txt).($more>0?" ".msq_t("msq_more",(string)$more):"");
        $c[]=msq_tag("fas fa-list-check",$label,"msq-tag-info",$all);
        return "<div class='msq-share'>".implode("",$c)."</div>";
    }
    if(count($i["src"])==0){
        $c[]=msq_tag("fas fa-users","{msq_all_computers}","");
    }else{
        list($txt,$more,$all)=msq_short($i["src"]);
        $c[]=msq_tag("fas fa-users",msq_t("msq_from",$txt).($more>0?" ".msq_t("msq_more",(string)$more):""),"",$all);
    }
    if(count($i["dst"])==0){
        $c[]=msq_tag("fas fa-globe","{msq_all_destinations}","msq-tag-muted");
    }else{
        list($txt,$more,$all)=msq_short($i["dst"]);
        $c[]=msq_tag("fas fa-route",msq_t("msq_except",$txt).($more>0?" ".msq_t("msq_more",(string)$more):""),"msq-tag-warn",$all);
    }
    return "<div class='msq-share'>".implode("",$c)."</div>";
}

// Bloc d'etat : interrupteur, ou etat impose ailleurs avec le lien qui le regle.
function msq_state(array $i,array $m):string{
    $page=CurrentPageName();
    if($i["forced"]=="nic"){
        $link="Loadjs('fw.settings.php?nic-form-settings-build=".rawurlencode($i["name"])."');";
        return "<div class='msq-state'><span class='msq-badge'><i class='fas fa-lock'></i> {msq_forced_nic}</span>"
            ."<a href='javascript:void(0)' class='msq-link' OnClick=\"$link\">{msq_open_iface}</a></div>";
    }
    if($i["forced"]=="vlan"){
        $link="Loadjs('fw.network.interfaces.php?nic-vlan-js=".intval($i["vlan_id"])."&eth=".rawurlencode($i["vlan_eth"])."');";
        return "<div class='msq-state'><span class='msq-badge'><i class='fas fa-lock'></i> {msq_forced_vlan}</span>"
            ."<a href='javascript:void(0)' class='msq-link' OnClick=\"$link\">{msq_open_vlan}</a></div>";
    }
    if($i["forced"]=="bridge"){
        $link="Loadjs('fw.bridges.php?ruleid-js=".intval($i["bridge_id"])."');";
        return "<div class='msq-state'><span class='msq-badge'><i class='fas fa-lock'></i> {msq_forced_bridge}</span>"
            ."<a href='javascript:void(0)' class='msq-link' OnClick=\"$link\">{msq_open_connector}</a></div>";
    }
    if(!$m["known"]){
        return "<div class='msq-state'><span class='msq-badge'><i class='fas fa-circle-question'></i> {msq_unknown}</span></div>";
    }
    $on=$i["enabled"];
    $word=$on?"{msq_on}":"{msq_off}";
    if($m["held"]){
        return "<div class='msq-state msq-state-row'><span class='msq-swl'>$word</span><span class='msq-badge'><i class='fas fa-lock'></i> {msq_meta_locked}</span></div>";
    }
    $want=$on?0:1;
    $js="Loadjs('$page?ifname-confirm-js=".rawurlencode($i["name"])."&id=".intval($i["id"])."&want=$want');";
    return "<div class='msq-state msq-state-row'><button type='button' class='msq-switch' role='switch' aria-checked='".($on?"true":"false")."'"
        ." aria-label=\"".msq_h(msq_t("msq_switch_aria",$i["name"]))."\" OnClick=\"$js\"><span class='msq-knob'></span></button>"
        ."<span class='msq-swl'>$word</span></div>";
}

function msq_row(array $i,array $m):string{
    $page=CurrentPageName();
    $lbl=!empty($i["label_html"])?$i["label"]:msq_h($i["label"]);
    $label=$i["label"]!==""?"<span class='msq-label'>$lbl</span>":"";
    $ico=isset($i["vlan_id"])?"fas fa-sitemap":"fas fa-ethernet";
    $ip=$i["ip"]!==""?"<span class='msq-ip'>".msq_h($i["ip"])."</span>":"<span class='msq-noip'>{msq_no_ip}</span>";
    $edit="";
    // Les listes ne servent pas quand le partage est impose par la carte ou un connecteur.
    if($i["forced"]==="" && $m["known"] && !($m["held"] && $i["id"]==0)){
        $js="Loadjs('$page?ifname-js=".rawurlencode($i["name"])."&id=".intval($i["id"])."');";
        $edit="<button type='button' class='msq-btn' aria-label=\"".msq_h(msq_t("msq_edit_aria",$i["name"]))."\" OnClick=\"$js\"><i class='fas fa-pen'></i> {msq_edit}</button>";
    }
    $off=$i["sharing"]?"":" msq-row-off";
    return "<div class='msq-row$off' id='masq-nic-".msq_h($i["name"])."'>"
        ."<span class='msq-ico'><i class='$ico'></i></span>"
        ."<div class='msq-id'><div class='msq-name'>".msq_h($i["name"])." $label</div><div>$ip</div></div>"
        ."<div class='msq-main'>".msq_share_line($i,$m)."</div>"
        ."<div class='msq-side'>".msq_state($i,$m)."<div class='msq-acts'>$edit</div></div>"
        ."</div>";
}

function msq_empty():string{
    return "<div class='msq-empty'><div class='msq-empty-ico'><i class='fas fa-ethernet'></i></div><h4>{msq_empty_title}</h4><p>{msq_empty_text}</p>"
        ."<button type='button' class='msq-btn msq-btn-primary' OnClick=\"document.location.href='/interfaces';\"><i class='fas fa-network-wired'></i> {msq_open_ifaces}</button></div>";
}

function msq_card(array $m):string{
    $page=CurrentPageName();
    $n=count($m["ifaces"]);
    $rows=array();
    foreach($m["ifaces"] as $i){$rows[]=msq_row($i,$m);}
    $count="";
    if($n>0){
        $sh=$m["known"]?number_format($m["sharing"]):"&mdash;";
        $count=$n==1?msq_t("msq_count1",$sh):msq_t("msq_count",number_format($n),$sh);
        $count="<span class='msq-count'>$count</span>";
    }
    $head="<div class='msq-card-h'><div><h3>{msq_your_ifaces} $count</h3><div class='msq-hhelp'>{msq_ifaces_help}</div></div>"
        ."<button type='button' class='msq-btn' OnClick=\"LoadAjax('table-masquerade','$page?table=yes');\"><i class='fas fa-arrows-rotate'></i> {refresh}</button></div>";
    $body=$n==0?msq_empty():"<div class='msq-list'>".implode("",$rows)."</div>";
    return "<div class='msq-card'>$head$body<div class='msq-foot'><i class='fas fa-circle-info'></i> {msq_foot}</div></div>";
}

function msq_style():string{
    if(isset($GLOBALS["MSQ_STYLE"])){return "";}
    $GLOBALS["MSQ_STYLE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.msq{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px;margin-top:8px}
.msq-hero{position:relative;display:flex;gap:18px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:22px 24px 20px 28px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.msq-hero:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:$accent}
.msq-hero-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:20px}
.msq-hero-body{flex:1;min-width:0}
.msq-hero h3{margin:0 0 6px 0;font-size:18px;font-weight:600}
.msq-hero-sub{display:inline-block;margin-left:8px;font-size:12px;font-weight:500;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:1px 8px;vertical-align:middle;white-space:nowrap}
.msq-hero p{margin:0;font-size:14px;line-height:1.6;color:#475569;max-width:960px}
.msq-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.msq-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid $line;background:$soft;border-radius:999px;padding:3px 11px;font-size:12px;color:#334155;white-space:nowrap}
.msq-chip i,.msq-chip svg{color:$accent}
.msq-alert{display:flex;gap:12px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;color:#b91c1c}
.msq-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.msq-card-h{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:16px 22px;border-bottom:1px solid #e2e8f0}
.msq-card-h h3{margin:0;font-size:17px;font-weight:600;display:flex;align-items:center;gap:10px}
.msq-hhelp{margin-top:4px;font-size:13px;color:#64748b}
.msq-count{border:1px solid #e2e8f0;background:#f8fafc;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600;color:#334155;white-space:nowrap}
.msq-list{display:flex;flex-direction:column;gap:12px;padding:18px 22px 0 22px}
.msq-row{display:flex;align-items:center;gap:18px;padding:16px 18px;border:1px solid #e2e8f0;border-radius:12px;background:#fff}
.msq-row:hover{border-color:#cbd5e1}
.msq-row-off .msq-ico,.msq-row-off .msq-id{opacity:.75}
.msq-ico{flex:0 0 40px;height:40px;border-radius:50%;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center}
.msq-id{flex:0 0 250px;min-width:0}
.msq-name{font-size:16px;font-weight:600;margin-bottom:4px;overflow-wrap:anywhere}
.msq-label{font-size:13px;font-weight:400;color:#475569;margin-left:6px}
.msq-ip{display:inline-block;font-family:monospace;font-size:13px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:1px 8px;color:#0f172a}
.msq-noip{font-size:12px;color:#94a3b8}
.msq-main{flex:1;min-width:0}
.msq-share{display:flex;flex-wrap:wrap;gap:6px 8px;align-items:center}
.msq-none{font-size:13px;color:#94a3b8;font-style:italic}
.msq-tag{display:inline-flex;align-items:center;gap:6px;max-width:380px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;border-radius:6px;padding:2px 9px;font-size:13px;font-weight:500;white-space:nowrap}
.msq-tag-t{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.msq-tag i,.msq-tag svg{flex:0 0 auto}
.msq-tag-muted{background:#f1f5f9;border-color:#e2e8f0;color:#334155}
.msq-tag-warn{background:#fffbeb;border-color:#fde68a;color:#b45309}
.msq-tag-info{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.msq-side{display:flex;align-items:center;gap:16px;flex:0 0 auto}
.msq-state{display:flex;flex-direction:column;align-items:flex-end;gap:4px;min-width:96px}
.msq-state-row{flex-direction:row;align-items:center;gap:8px}
.msq-badge{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:2px 9px;font-size:13px;font-weight:500;color:#334155;white-space:nowrap}
.msq-link{font-size:13px;color:#1d4ed8;font-weight:600;text-decoration:none;white-space:nowrap}
.msq-link:hover{text-decoration:underline}
.msq-switch{position:relative;width:46px;height:26px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0;transition:background .15s}
.msq-switch[aria-checked=true]{background:$accent}
.msq-knob{position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.msq-switch[aria-checked=true] .msq-knob{left:23px}
.msq-switch:focus-visible,.msq-btn:focus-visible,.msq-link:focus-visible{outline:2px solid $accent;outline-offset:2px}
.msq-swl{font-size:13px;font-weight:600;color:#475569;min-width:24px}
.msq-acts{display:flex;border-left:1px solid #e2e8f0;padding-left:14px;min-width:96px}
.msq-btn{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:7px 14px;font-size:14px;font-weight:500;color:#334155;cursor:pointer;white-space:nowrap}
.msq-btn:hover{background:#f8fafc}
.msq-btn-primary{background:$accent;border-color:$accent;color:#fff;font-weight:600}
.msq-btn-primary:hover{background:$dark;border-color:$dark}
.msq-foot{display:flex;align-items:center;gap:8px;margin:16px 22px 18px 22px;padding-top:14px;border-top:1px solid #f1f5f9;font-size:12px;color:#64748b}
.msq-empty{display:flex;flex-direction:column;align-items:center;text-align:center;padding:36px 24px 10px 24px;gap:10px}
.msq-empty-ico{width:68px;height:68px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:26px}
.msq-empty h4{margin:4px 0 0 0;font-size:17px;font-weight:600}
.msq-empty p{margin:0;color:#475569;max-width:560px;line-height:1.6}
@media (max-width:1200px){.msq-row{flex-wrap:wrap}.msq-main{flex-basis:100%;order:3}.msq-side{margin-left:auto}}
@media (prefers-reduced-motion:reduce){.msq-switch,.msq-knob{transition:none}}
</style>";
}
