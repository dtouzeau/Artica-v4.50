<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
// Kit de tables awc_*. Inclusion INCONDITIONNELLE : dans le bootstrap
// if(!class_exists("sockets")) elle ne s'executerait jamais, et la page mourrait
// sur "Call to undefined function awc_table_style()" -- un spinner infini.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Pentest 2026-11-04 : field-id etait recopie sans encodage dans la page (XSS). C'est un id de DOM (md5).
if(isset($_GET["field-id"])){$_GET["field-id"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["field-id"]);}
if(isset($_GET["interfaces"])){content();exit;}
if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$label=$_GET["title"];
    $addon="";
    if(preg_match("#vs:(.+)#",$label,$re)){
        $label=$re[1];
        $addon="&vs=1";
    }
    $title=$tpl->javascript_parse_text("{interfaces}");
    if(strlen($label)>0) {
        // Pentest 2026-11-04 : title etait recopie sans encodage dans la page (XSS).
        // js_dialog6() repasse le titre par javascript_parse_text(), qui decode une fois les entites.
        $title = req_html(req_html($tpl->javascript_parse_text("$label")));
    }
	$tpl->js_dialog6($title, "$page?popup=yes&field-id={$_GET["field-id"]}$addon",560);
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();

    $addon="";
	if(isset($_GET["vs"])){
        $addon="&vs=1";
    }


	$html="<div id='choose-interfaces'></div>
	<script>
		var Currentinterfaces=encodeURIComponent(document.getElementById('{$_GET["field-id"]}').value);
		LoadAjax('choose-interfaces','$page?interfaces='+Currentinterfaces+'$addon&field-id={$_GET["field-id"]}');
	</script>
	";
	echo $html;
	
}


// ---------------------------------------------------------------------------
// Selecteur d'interfaces (maquette stitch_artica_select_interfaces, 2026-11-11).
// Un modele lu une fois (ifs_model), une fonction par widget. Le script ne lit que
// les cases de SON conteneur : la fenetre s'ouvre par-dessus un formulaire qui a
// lui-meme des cases a cocher.
// ---------------------------------------------------------------------------
function ifs_h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}

// 255.255.255.0 -> 24 ; un masque invalide ou absent donne -1.
function ifs_cidr($mask):int{
    $l=ip2long(trim((string)$mask));
    if($l===false){return -1;}
    $l=$l & 0xFFFFFFFF;
    $bits=0;
    while($l & 0x80000000){$bits++; $l=($l<<1) & 0xFFFFFFFF;}
    return $l==0?$bits:-1;
}

function ifs_model():array{
    $OnLyVS=isset($_GET["vs"]);
    $current=array();
    foreach(explode(",",(string)$_GET["interfaces"]) as $eth){
        $eth=trim($eth);
        if($eth==""){continue;}
        if(!preg_match('/^[A-Za-z0-9_.:@-]+$/',$eth)){continue;}
        $current[$eth]=true;
    }
    include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
    $nic=new networking();
    $nicZ=$nic->Local_interfaces();
    $rows=array();
    if(!$OnLyVS){
        $rows[]=array("dev"=>"lo","name"=>"{ifs_loopback}","ip"=>"127.0.0.1","cidr"=>8,"zone"=>"","mode"=>"","defroute"=>false,"kind"=>"lo");
    }
    foreach($nicZ as $yinter=>$line){
        $znic=new system_nic($yinter);
        if($znic->Bridged==1){continue;}
        if($znic->enabled==0){continue;}
        if(preg_match("/(gre|gretap|erspan|wccp)[0-9]+$/",$yinter)){continue;}
        if($OnLyVS){
            if(!preg_match("#^(.+?)h([0-9]+)$#",$yinter,$ri)){continue;}
            if(intval($ri[2])==0){continue;}
            if(preg_match("#^eth#",$ri[1])){continue;}
        }
        $ip=trim((string)$znic->IPADDR);
        $kind=(strpos($yinter,".")!==false || preg_match('/^vlan/i',$yinter))?"vlan":"eth";
        $rows[]=array("dev"=>$yinter,"name"=>trim((string)$znic->NICNAME),
            "ip"=>($ip=="0.0.0.0"?"":$ip),"cidr"=>ifs_cidr($znic->NETMASK),
            "zone"=>trim((string)$znic->netzone),
            "mode"=>(intval($znic->dhcp)==1?"dhcp":"static"),
            "defroute"=>intval($znic->defaultroute)==1,"kind"=>$kind);
    }
    $known=array();
    foreach($rows as $r){$known[$r["dev"]]=true;}
    // Selectionnees mais absentes de la liste : avant, elles disparaissaient sans un mot.
    $missing=array();
    foreach($current as $dev=>$x){ if(!isset($known[$dev])){$missing[]=$dev;} }
    return array("rows"=>$rows,"current"=>$current,"missing"=>$missing,"vs"=>$OnLyVS,
        "field"=>(string)$_GET["field-id"],"t"=>md5(microtime(true).rand()));
}

function ifs_toolbar(array $m):string{
    return "<div class='ifs-tools'>
<label class='ifs-search'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i><input type='text' class='ifs-q' placeholder=\"{ifs_filter_ph}\" aria-label=\"{ifs_filter_ph}\"></label>
<button type='button' class='ifs-link ifs-all'>{ifs_select_all}</button><span class='ifs-bar' aria-hidden='true'>|</span><button type='button' class='ifs-link ifs-none'>{ifs_clear}</button>
</div>";
}

function ifs_summary(array $m):string{
    return "<div class='ifs-sum' aria-live='polite'><span class='ifs-count'></span><span class='ifs-chips'></span></div>";
}

function ifs_missing(array $m,$tpl):string{
    $h=array();
    // Traduire AVANT de remplacer %s : sur le jeton brut, il n'y a rien a remplacer.
    $text=trim(str_replace(array("[br]","<br>"),array(" "," "),$tpl->_ENGINE_parse_body("{ifs_missing}")));
    foreach($m["missing"] as $dev){
        $d=ifs_h($dev);
        $h[]="<div class='ifs-miss' role='alert' data-dev='$d'><i class='fa-solid fa-triangle-exclamation ifs-miss-i' aria-hidden='true'></i>"
            ."<span class='ifs-miss-t'>".str_replace("%s","<strong class='ak-mono'>$d</strong>",$text)
            ." <button type='button' class='ifs-keep'>{ifs_keep}</button><span class='ifs-kept' hidden>{ifs_kept}</span></span>"
            ."<button type='button' class='ifs-x' aria-label=\"{ifs_dismiss}\"><i class='fa-solid fa-xmark' aria-hidden='true'></i></button></div>";
    }
    return @implode("",$h);
}

function ifs_row(array $r,bool $on):string{
    $dev=ifs_h($r["dev"]);
    $name=$r["name"]==""?$dev:($r["kind"]=="lo"?$r["name"]:ifs_h($r["name"]));
    $ico=array("lo"=>"fa-solid fa-rotate-left","vlan"=>"fa-solid fa-tags","eth"=>"fa-solid fa-ethernet");
    $meta=array();
    if($r["ip"]<>""){$meta[]="<span class='ak-mono'>".ifs_h($r["ip"]).($r["cidr"]>=0?"/".$r["cidr"]:"")."</span>";}
    if($r["zone"]<>""){$meta[]="{ifs_zone} ".ifs_h($r["zone"]);}
    if($r["mode"]=="dhcp"){$meta[]="{ifs_dhcp_client}";}elseif($r["mode"]=="static"){$meta[]="{ifs_static}";}
    $search=strtolower($r["dev"]." ".$r["name"]." ".$r["ip"]." ".$r["zone"]);
    $id="ifs-".md5($r["dev"].microtime(true).rand());
    $h="<label class='ifs-row".($on?" on":"")."' data-search='".ifs_h($search)."' for='$id'>";
    $h.="<input type='checkbox' class='ifs-cb' id='$id' value='$dev'".($on?" checked":"").">";
    $h.="<span class='ifs-ico ifs-ico-{$r["kind"]}'><i class='{$ico[$r["kind"]]}' aria-hidden='true'></i></span>";
    $h.="<span class='ifs-txt'><span class='ifs-l1'><span class='ifs-name' title='".strip_tags($name)."'>$name</span><span class='ifs-dev ak-mono'>$dev</span></span>";
    $h.="<span class='ifs-l2'>".(count($meta)?@implode(" <span class='ifs-dot' aria-hidden='true'>&middot;</span> ",$meta):"&nbsp;")."</span></span>";
    if($r["defroute"]){$h.="<span class='ifs-tag'><i class='fa-solid fa-route' aria-hidden='true'></i>{default_route}</span>";}
    $h.="</label>";
    return $h;
}

function ifs_list(array $m):string{
    if(count($m["rows"])==0){
        return "<div class='ifs-empty'><i class='fa-solid fa-network-wired' aria-hidden='true'></i><div>{ifs_no_virtual}</div></div>";
    }
    $h=array();
    $h[]="<div class='ifs-list' role='group' aria-label=\"{interfaces}\">";
    foreach($m["rows"] as $r){$h[]=ifs_row($r,isset($m["current"][$r["dev"]]));}
    $h[]="<div class='ifs-nomatch' hidden>{ifs_no_match}</div>";
    $h[]="</div>";
    return @implode("",$h);
}

function ifs_footer(array $m):string{
    return "<div class='ifs-foot'><span class='ifs-note'>{ifs_empty_note}</span><span class='ifs-acts'>"
        ."<button type='button' class='btn abt abt-o ifs-cancel'><span>{ifs_cancel}</span></button>"
        ."<button type='button' class='btn abt abt-p ifs-apply'><i class='fa-solid fa-check' aria-hidden='true'></i><span>{ifs_apply}</span></button></span></div>";
}

function ifs_style():string{
    if(isset($GLOBALS["IFS_STYLE"])){return "";}
    $GLOBALS["IFS_STYLE"]=true;
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488","accent_soft"=>"rgba(16,185,129,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette()); if(is_array($b)){$ac=array_merge($ac,$b);}
    }
    $fill=function_exists("abt_fill")?abt_fill($ac["accent"]):$ac["accent_dark"];
    $c=array("<style>");
    $c[]=".ifs{display:flex;flex-direction:column;gap:14px;color:#0f172a;font-size:13px}";
    $c[]=".ifs *{box-sizing:border-box}";
    $c[]=".ifs-tools{display:flex;align-items:center;gap:6px}";
    $c[]=".ifs-search{flex:1 1 auto;position:relative;margin:0;font-weight:400}";
    $c[]=".ifs-search>i,.ifs-search>svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px}";
    $c[]=".ifs-q{width:100%;height:38px;padding:0 12px 0 34px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;color:#0f172a;background:#fff}";
    $c[]=".ifs-q:focus{outline:0;border-color:{$ac["accent_dark"]};box-shadow:0 0 0 2px {$ac["accent_soft"]}}";
    $c[]=".ifs-link{border:0;background:transparent;color:{$ac["accent_dark"]};font-size:13px;font-weight:500;padding:6px 8px;border-radius:6px;cursor:pointer}";
    $c[]=".ifs-link:hover{background:{$ac["accent_soft"]}}";
    $c[]=".ifs-link:focus-visible,.ifs-keep:focus-visible,.ifs-x:focus-visible{outline:2px solid {$ac["accent_dark"]};outline-offset:2px}";
    $c[]=".ifs-bar{color:#cbd5e1}";
    $c[]=".ifs-sum{display:flex;align-items:center;flex-wrap:wrap;gap:6px;min-height:26px;color:#475569}";
    $c[]=".ifs-count{font-weight:500}";
    $c[]=".ifs-chips{display:inline-flex;flex-wrap:wrap;gap:6px}";
    $c[]=".ifs-chip{display:inline-block;padding:1px 8px;border:1px solid #e2e8f0;border-radius:6px;background:#f1f5f9;color:#334155;font-size:13px}";
    $c[]=".ifs-miss{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border:1px solid #fcd34d;border-radius:8px;background:#fef3c7;color:#92400e;line-height:1.45}";
    $c[]=".ifs-miss-i{color:#d97706;font-size:14px;margin-top:2px}";
    $c[]=".ifs-miss-t{flex:1 1 auto}";
    $c[]=".ifs-keep{border:0;background:transparent;padding:0;margin-left:4px;color:#b45309;font-weight:600;text-decoration:underline;cursor:pointer}";
    $c[]=".ifs-kept{margin-left:4px;font-weight:600;color:#166534}";
    $c[]=".ifs-x{border:0;background:transparent;color:#b45309;cursor:pointer;padding:2px 4px;border-radius:4px}";
    $c[]=".ifs-list{max-height:360px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:12px;background:#fff}";
    $c[]=".ifs-row{display:flex;align-items:center;gap:12px;min-height:60px;padding:10px 14px 10px 13px;margin:0;border-top:1px solid #f1f5f9;border-left:3px solid transparent;cursor:pointer;font-weight:400;transition:background-color .12s ease}";
    $c[]=".ifs-row:first-child{border-top:0}";
    $c[]=".ifs-row:hover{background:#f8fafc}";
    $c[]=".ifs-row.on{background:{$ac["accent_soft"]};border-left-color:$fill}";
    $c[]=".ifs-cb{flex:0 0 18px;width:18px;height:18px;margin:0!important;accent-color:$fill;cursor:pointer}";
    $c[]=".ifs-cb:focus-visible{outline:2px solid {$ac["accent_dark"]};outline-offset:2px}";
    $c[]=".ifs-ico{flex:0 0 32px;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;background:{$ac["accent_soft"]};color:{$ac["accent_dark"]}}";
    $c[]=".ifs-ico-lo{background:#e0f2fe;color:#0369a1}";
    $c[]=".ifs-ico-vlan{background:#e3f0f8;color:#3d4947}";
    $c[]=".ifs-txt{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:2px}";
    $c[]=".ifs-l1{display:flex;align-items:center;gap:8px;min-width:0}";
    $c[]=".ifs-name{font-size:14px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".ifs-dev{flex:0 0 auto;padding:0 6px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;color:#475569;font-size:13px}";
    $c[]=".ifs-l2{font-size:13px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".ifs-dot{color:#cbd5e1;margin:0 2px}";
    $c[]=".ifs-tag{flex:0 0 auto;display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;background:#e0f2fe;color:#0369a1;font-size:13px;font-weight:500}";
    $c[]=".ifs-nomatch,.ifs-empty{padding:28px 16px;text-align:center;color:#64748b}";
    $c[]=".ifs-empty{display:flex;flex-direction:column;align-items:center;gap:8px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc}";
    $c[]=".ifs-empty>i,.ifs-empty>svg{font-size:32px;color:#94a3b8}";
    $c[]=".ifs-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:14px;border-top:1px solid #e2e8f0}";
    $c[]=".ifs-note{color:#64748b}";
    $c[]=".ifs-acts{display:inline-flex;gap:8px}";
    $c[]="@media (prefers-reduced-motion:reduce){.ifs-row{transition:none}}";
    $c[]="</style>";
    return @implode("",$c);
}

function ifs_js(array $m,$tpl):string{
    // Les libelles du compteur viennent des attributs data-* du conteneur, deja traduits :
    // javascript_parse_text() retirerait les accents ("selectionnees").
    $field=$m["field"];
    return "<script>
(function(){
  var root=document.getElementById('ifs-{$m["t"]}'); if(!root){return;}
  var cbs=function(){return root.querySelectorAll('.ifs-cb');};
  var kept={};
  var esc=function(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;};
  var sync=function(){
    var all=cbs(), sel=[];
    for(var i=0;i<all.length;i++){ var r=all[i].closest('.ifs-row'); if(all[i].checked){sel.push(all[i].value); r.classList.add('on');}else{r.classList.remove('on');} }
    for(var k in kept){ if(kept[k]){sel.push(k);} }
    var c=root.querySelector('.ifs-count'), ch=root.querySelector('.ifs-chips');
    if(sel.length==0){ c.textContent=root.getAttribute('data-none')||''; ch.innerHTML=''; return sel; }
    c.textContent=(root.getAttribute('data-count')||'').replace('%n',sel.length).replace('%t',all.length+Object.keys(kept).filter(function(k){return kept[k];}).length);
    ch.innerHTML=sel.map(function(s){return \"<span class='ifs-chip ak-mono'>\"+esc(s)+'</span>';}).join('');
    return sel;
  };
  root.addEventListener('change',function(e){ if(e.target.classList.contains('ifs-cb')){sync();} });
  var q=root.querySelector('.ifs-q');
  if(q){ q.addEventListener('input',function(){
    var v=q.value.toLowerCase().trim(), rows=root.querySelectorAll('.ifs-row'), shown=0;
    for(var i=0;i<rows.length;i++){ var ok=(v==''||rows[i].getAttribute('data-search').indexOf(v)>=0); rows[i].style.display=ok?'':'none'; if(ok){shown++;} }
    var nm=root.querySelector('.ifs-nomatch'); if(nm){nm.hidden=(shown>0);}
  }); setTimeout(function(){try{q.focus();}catch(e){}},300); }
  var setAll=function(on){ var all=cbs(); for(var i=0;i<all.length;i++){ if(all[i].closest('.ifs-row').style.display!='none'){all[i].checked=on;} } sync(); };
  root.querySelector('.ifs-all').addEventListener('click',function(){setAll(true);});
  // Clear vide aussi les interfaces absentes qu'on avait choisi de garder.
  root.querySelector('.ifs-none').addEventListener('click',function(){
    kept={}; var ms=root.querySelectorAll('.ifs-miss');
    for(var i=0;i<ms.length;i++){ ms[i].querySelector('.ifs-keep').hidden=false; ms[i].querySelector('.ifs-kept').hidden=true; }
    setAll(false);
  });
  var ms=root.querySelectorAll('.ifs-miss');
  for(var i=0;i<ms.length;i++){ (function(box){
    var dev=box.getAttribute('data-dev');
    box.querySelector('.ifs-keep').addEventListener('click',function(){ kept[dev]=true; this.hidden=true; box.querySelector('.ifs-kept').hidden=false; sync(); });
    box.querySelector('.ifs-x').addEventListener('click',function(){ box.style.display='none'; });
  })(ms[i]); }
  var close=function(){ if(typeof dialogInstance6!=='undefined'){dialogInstance6.close();} };
  root.querySelector('.ifs-cancel').addEventListener('click',close);
  root.querySelector('.ifs-apply').addEventListener('click',function(){
    var f=document.getElementById('$field'); if(f){ f.value=sync().join(','); }
    close();
  });
  sync();
  if(typeof NoSpinner==='function'){NoSpinner();}
})();
</script>";
}

function content(){
    $tpl=new template_admin();
    $m=ifs_model();
    $h=array();
    $h[]="<div class='ifs' id='ifs-{$m["t"]}' data-count=\"{ifs_count}\" data-none=\"{ifs_none_selected}\">";
    $h[]=ifs_toolbar($m);
    $h[]=ifs_summary($m);
    $h[]=ifs_missing($m,$tpl);
    $h[]=ifs_list($m);
    $h[]=ifs_footer($m);
    $h[]="</div>";
    echo ifs_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).ifs_js($m,$tpl);
}
