<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");

// Selecteur d'UNE interface (maquette stitch_artica_dhcp_server_interface, 2026-11-11),
// ouvert par field_interfaces() (104 pages) et fw.nginx.ports.php. Contrat inchange :
// la valeur choisie va dans #<id>, le libelle "NOM (IP)" dans #btnlabel-<id>.
// Pentest : id est un id de DOM (md5), name un nom de champ ; tout le reste est filtre.
foreach(array("id","name","suffix-form") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET[$xk]);}}
if(isset($_GET["value"])){$_GET["value"]=preg_replace('/[^A-Za-z0-9_.:@-]/','',(string)$_GET["value"]);}

if(isset($_GET["popup"])){popup();exit;}

js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $qs="popup=yes&name=".urlencode((string)$_GET["name"])
        ."&nics=".urlencode((string)$_GET["nics"])
        ."&id=".urlencode((string)$_GET["id"])
        ."&value=".urlencode((string)$_GET["value"])
        ."&suffix-form=".urlencode((string)$_GET["suffix-form"]);
    $tpl->js_dialog13("{interfaces}","$page?$qs",560);
}

function fif_h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}

// 255.255.255.0 -> 24 ; un masque invalide ou absent donne -1.
function fif_cidr($mask):int{
    $l=ip2long(trim((string)$mask));
    if($l===false){return -1;}
    $l=$l & 0xFFFFFFFF; $bits=0;
    while($l & 0x80000000){$bits++; $l=($l<<1) & 0xFFFFFFFF;}
    return $l==0?$bits:-1;
}

// La liste vient de l'appelant (filtres de field_interfaces) : on n'en garde que les
// CLES, verifiees contre les interfaces reelles, et l'affichage vient de system_nic.
function fif_model($tpl):array{
    $hash=$GLOBALS["CLASS_SOCKETS"]->unserializeb64((string)$_GET["nics"]);
    $real=$tpl->list_interfaces(false,array());
    $special=array(); $rows=array();
    foreach($hash as $key=>$label){
        $key=(string)$key;
        if($key===""){
            $none=(trim((string)$label)=="{none}");
            $special[]=array("dev"=>"","kind"=>$none?"none":"all","name"=>$none?"{none}":"{all_interfaces}",
                "sub"=>$none?"{fif_none_explain}":"{fif_all_explain}","ip"=>"","cidr"=>-1,"zone"=>"","mode"=>"","defroute"=>false);
            continue;
        }
        if($key==="lo"){
            $special[]=array("dev"=>"lo","kind"=>"lo","name"=>"{ifs_loopback}","sub"=>"","ip"=>"127.0.0.1","cidr"=>8,"zone"=>"","mode"=>"","defroute"=>false,"label"=>"Loopback (127.0.0.1)");
            continue;
        }
        if(!isset($real[$key])){continue;}
        $n=new system_nic($key);
        $ip=trim((string)$n->IPADDR);
        $rows[]=array("dev"=>$key,"kind"=>((strpos($key,".")!==false||preg_match('/^vlan/i',$key))?"vlan":"eth"),
            "name"=>trim((string)$n->NICNAME),"sub"=>"","ip"=>($ip=="0.0.0.0"?"":$ip),"cidr"=>fif_cidr($n->NETMASK),
            "zone"=>trim((string)$n->netzone),"mode"=>(intval($n->dhcp)==1?"dhcp":"static"),
            "defroute"=>intval($n->defaultroute)==1,"label"=>trim((string)$n->NICNAME)." (".$ip.")");
    }
    return array("special"=>$special,"rows"=>$rows,"value"=>(string)$_GET["value"],
        "field"=>(string)$_GET["id"],"t"=>md5(microtime(true).rand()));
}

function fif_meta(array $r):string{
    if($r["sub"]<>""){return $r["sub"];}
    $meta=array();
    if($r["ip"]<>""){$meta[]="<span class='ak-mono'>".fif_h($r["ip"]).($r["cidr"]>=0?"/".$r["cidr"]:"")."</span>";}
    if($r["zone"]<>""){$meta[]="{ifs_zone} ".fif_h($r["zone"]);}
    if($r["mode"]=="dhcp"){$meta[]="{ifs_dhcp_client}";}elseif($r["mode"]=="static"){$meta[]="{ifs_static}";}
    return count($meta)?@implode(" <span class='fif-dot' aria-hidden='true'>&middot;</span> ",$meta):"&nbsp;";
}

function fif_ident(array $r):string{
    $ico=array("all"=>"fa-solid fa-globe","none"=>"fa-solid fa-ban","lo"=>"fa-solid fa-rotate-left","vlan"=>"fa-solid fa-tags","eth"=>"fa-solid fa-ethernet");
    $name=in_array($r["kind"],array("all","none","lo"))?$r["name"]:($r["name"]==""?fif_h($r["dev"]):fif_h($r["name"]));
    $h="<span class='fif-ico fif-ico-{$r["kind"]}'><i class='{$ico[$r["kind"]]}' aria-hidden='true'></i></span>";
    $h.="<span class='fif-txt'><span class='fif-l1'><span class='fif-name' title='".strip_tags($name)."'>$name</span>";
    if($r["dev"]<>""){$h.="<span class='fif-dev ak-mono'>".fif_h($r["dev"])."</span>";}
    $h.="</span><span class='fif-l2'>".fif_meta($r)."</span></span>";
    return $h;
}

function fif_current(array $m):string{
    $cur=null;
    foreach(array_merge($m["special"],$m["rows"]) as $r){ if($r["dev"]===$m["value"]){$cur=$r;break;} }
    $h="<div class='fif-cur'><span class='fif-cur-k'>{fif_current}</span>";
    if($cur===null){
        $h.=$m["value"]<>""?"<span class='fif-dev ak-mono'>".fif_h($m["value"])."</span>":"<span class='fif-muted'>{fif_not_set}</span>";
    }else{
        $h.="<span class='fif-cur-v'>".fif_ident($cur)."</span>";
    }
    return $h."</div>";
}

function fif_row(array $r,string $value):string{
    $on=($r["dev"]===$value);
    // Libelle reecrit dans le champ : "NOM (IP)" pour une interface (fw.nginx.ports.php
    // en tire l'IP), le texte de l'option sinon. Avant, "All interfaces" donnait " ()".
    $label=isset($r["label"])?fif_h($r["label"]):$r["name"];
    $search=strtolower($r["dev"]." ".strip_tags((string)$r["name"])." ".$r["ip"]." ".$r["zone"]);
    $h="<button type='button' role='radio' aria-checked='".($on?"true":"false")."' class='fif-row".($on?" on":"")."'"
        ." data-dev='".fif_h($r["dev"])."' data-label=\"$label\" data-search='".fif_h($search)."'>";
    $h.="<span class='fif-radio' aria-hidden='true'></span>";
    $h.=fif_ident($r);
    if($r["defroute"]){$h.="<span class='fif-tag fif-tag-route'><i class='fa-solid fa-route' aria-hidden='true'></i>{default_route}</span>";}
    if($on){$h.="<span class='fif-tag fif-tag-cur'>{fif_current}</span>";}
    $h.="<i class='fa-solid fa-chevron-right fif-chev' aria-hidden='true'></i></button>";
    return $h;
}

function fif_lists(array $m):string{
    if(count($m["special"])+count($m["rows"])==0){
        return "<div class='fif-empty'><i class='fa-solid fa-network-wired' aria-hidden='true'></i><div>{fif_no_iface}</div></div>";
    }
    $h=array();
    $h[]="<div class='fif-groups' role='radiogroup' aria-label=\"{interfaces}\">";
    if(count($m["special"])){
        $h[]="<div class='fif-list fif-list-sp'>";
        foreach($m["special"] as $r){$h[]=fif_row($r,$m["value"]);}
        $h[]="</div>";
    }
    if(count($m["rows"])){
        $h[]="<div class='fif-list fif-list-if'>";
        foreach($m["rows"] as $r){$h[]=fif_row($r,$m["value"]);}
        $h[]="</div>";
    }
    $h[]="<div class='fif-nomatch' hidden>{ifs_no_match}</div>";
    $h[]="</div>";
    return @implode("",$h);
}

function fif_style():string{
    if(isset($GLOBALS["FIF_STYLE"])){return "";}
    $GLOBALS["FIF_STYLE"]=true;
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488","accent_soft"=>"rgba(16,185,129,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette()); if(is_array($b)){$ac=array_merge($ac,$b);}
    }
    $fill=function_exists("abt_fill")?abt_fill($ac["accent"]):$ac["accent_dark"];
    $c=array("<style>");
    $c[]=".fif{display:flex;flex-direction:column;gap:14px;color:#0f172a;font-size:13px}";
    $c[]=".fif *{box-sizing:border-box}";
    $c[]=".fif-cur{display:flex;align-items:center;gap:10px;min-height:48px;padding:6px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc}";
    $c[]=".fif-cur-k{color:#64748b;font-weight:500;flex:0 0 auto}";
    $c[]=".fif-cur-v{display:flex;align-items:center;gap:10px;min-width:0;flex:1 1 auto}";
    $c[]=".fif-cur .fif-ico{flex-basis:26px;width:26px;height:26px;font-size:12px}";
    $c[]=".fif-muted{color:#64748b}";
    $c[]=".fif-search{position:relative;margin:0;font-weight:400}";
    $c[]=".fif-search>i,.fif-search>svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px}";
    $c[]=".fif-q{width:100%;height:38px;padding:0 12px 0 34px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;color:#0f172a;background:#fff}";
    $c[]=".fif-q:focus{outline:0;border-color:{$ac["accent_dark"]};box-shadow:0 0 0 2px {$ac["accent_soft"]}}";
    $c[]=".fif-groups{display:flex;flex-direction:column;gap:8px}";
    $c[]=".fif-list{border:1px solid #e2e8f0;border-radius:12px;background:#fff;overflow:hidden}";
    $c[]=".fif-list-if{max-height:380px;overflow-y:auto}";
    $c[]=".fif-row{display:flex;align-items:center;gap:12px;width:100%;min-height:56px;padding:8px 14px 8px 13px;margin:0;border:0;border-top:1px solid #f1f5f9;border-left:3px solid transparent;background:#fff;text-align:left;cursor:pointer;font:inherit;color:inherit;transition:background-color .12s ease}";
    $c[]=".fif-row:first-child{border-top:0}";
    $c[]=".fif-row:hover{background:#f8fafc}";
    $c[]=".fif-row:focus-visible{outline:2px solid {$ac["accent_dark"]};outline-offset:-2px}";
    $c[]=".fif-row.on{background:{$ac["accent_soft"]};border-left-color:$fill}";
    $c[]=".fif-radio{flex:0 0 18px;width:18px;height:18px;border-radius:50%;border:2px solid #cbd5e1;background:#fff;position:relative}";
    $c[]=".fif-row.on .fif-radio{border-color:$fill}";
    $c[]=".fif-row.on .fif-radio::after{content:'';position:absolute;top:3px;left:3px;width:8px;height:8px;border-radius:50%;background:$fill}";
    $c[]=".fif-ico{flex:0 0 32px;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;background:{$ac["accent_soft"]};color:{$ac["accent_dark"]}}";
    $c[]=".fif-ico-all,.fif-ico-lo{background:#e0f2fe;color:#0369a1}";
    $c[]=".fif-ico-none{background:#f1f5f9;color:#64748b}";
    $c[]=".fif-ico-vlan{background:#e3f0f8;color:#3d4947}";
    $c[]=".fif-txt{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:2px}";
    $c[]=".fif-l1{display:flex;align-items:center;gap:8px;min-width:0}";
    $c[]=".fif-name{font-size:14px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".fif-dev{flex:0 0 auto;padding:0 6px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;color:#475569;font-size:13px}";
    $c[]=".fif-l2{font-size:13px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".fif-dot{color:#cbd5e1;margin:0 2px}";
    $c[]=".fif-tag{flex:0 0 auto;display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:13px;font-weight:500}";
    $c[]=".fif-tag-route{background:#e0f2fe;color:#0369a1}";
    $c[]=".fif-tag-cur{background:{$ac["accent_soft"]};color:{$ac["accent_dark"]}}";
    $c[]=".fif-chev{flex:0 0 auto;color:#94a3b8;font-size:11px;opacity:0;transition:opacity .12s ease}";
    $c[]=".fif-row:hover .fif-chev,.fif-row:focus-visible .fif-chev{opacity:1}";
    $c[]=".fif-nomatch,.fif-empty{padding:24px 16px;text-align:center;color:#64748b}";
    $c[]=".fif-empty{display:flex;flex-direction:column;align-items:center;gap:8px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc}";
    $c[]=".fif-empty>i,.fif-empty>svg{font-size:32px;color:#94a3b8}";
    $c[]=".fif-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:12px;border-top:1px solid #e2e8f0;color:#64748b}";
    $c[]=".fif-kbd{display:inline-block;padding:0 6px;border:1px solid #e2e8f0;border-radius:4px;background:#f8fafc;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#475569}";
    $c[]="@media (prefers-reduced-motion:reduce){.fif-row,.fif-chev{transition:none}}";
    $c[]="</style>";
    return @implode("",$c);
}

function fif_js(array $m):string{
    return "<script>
(function(){
  var root=document.getElementById('fif-{$m["t"]}'); if(!root){return;}
  var rows=function(){return [].slice.call(root.querySelectorAll('.fif-row'));};
  var close=function(){ if(typeof dialogInstance13!=='undefined'){dialogInstance13.close();} };
  var choose=function(r){
    var f=document.getElementById('{$m["field"]}'); if(!f){return;}
    f.value=r.getAttribute('data-dev');
    var l=document.getElementById('btnlabel-{$m["field"]}'); if(l){ l.textContent=r.getAttribute('data-label'); }
    try{ f.dispatchEvent(new Event('change',{bubbles:true})); }catch(e){}
    close();
  };
  rows().forEach(function(r){ r.addEventListener('click',function(){choose(r);}); });
  var q=root.querySelector('.fif-q');
  if(q){ q.addEventListener('input',function(){
    var v=q.value.toLowerCase().trim(), shown=0;
    rows().forEach(function(r){ var ok=(v==''||r.getAttribute('data-search').indexOf(v)>=0); r.style.display=ok?'':'none'; if(ok){shown++;} });
    [].slice.call(root.querySelectorAll('.fif-list')).forEach(function(l){ var any=[].slice.call(l.querySelectorAll('.fif-row')).some(function(r){return r.style.display!='none';}); l.style.display=any?'':'none'; });
    var nm=root.querySelector('.fif-nomatch'); if(nm){nm.hidden=(shown>0);}
  }); setTimeout(function(){try{q.focus();}catch(e){}},300); }
  root.addEventListener('keydown',function(e){
    if(e.key==='Escape'){ e.preventDefault(); close(); return; }
    if(e.key!=='ArrowDown'&&e.key!=='ArrowUp'){return;}
    var vis=rows().filter(function(r){return r.style.display!='none';}); if(!vis.length){return;}
    var i=vis.indexOf(document.activeElement); e.preventDefault();
    i=(e.key==='ArrowDown')?Math.min(vis.length-1,i+1):Math.max(0,i-1);
    vis[i<0?0:i].focus();
  });
  if(typeof NoSpinner==='function'){NoSpinner();}
})();
</script>";
}

function popup(){
    $tpl=new template_admin();
    $m=fif_model($tpl);
    $h=array();
    $h[]="<div class='fif' id='fif-{$m["t"]}'>";
    $h[]=fif_current($m);
    $h[]="<label class='fif-search'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i><input type='text' class='fif-q' placeholder=\"{ifs_filter_ph}\" aria-label=\"{ifs_filter_ph}\"></label>";
    $h[]=fif_lists($m);
    $h[]="<div class='fif-foot'><span>{fif_hint}</span><span><span class='fif-kbd'>Esc</span> {fif_to_cancel}</span></div>";
    $h[]="</div>";
    echo fif_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).fif_js($m);
}
