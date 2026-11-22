<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

// Tous les points d'entree AJAX sont dispatches ICI, avant page(): un dispatch
// place plus bas laisserait page() emettre son propre corps avant le handler.
if(isset($_GET["search"])){list_ports();exit;}
if(isset($_GET["list"])){list_ports();exit;}
if(isset($_GET["js"])){js();exit;}
page();

function js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog10("{open_ports}","$page?byjs=yes",1200);
}

// Cette page a DEUX appelants et n'emet donc jamais de page_header :
//   - onglet "{open_ports}" de fw.network.interfaces.php (le parent tient l'entete)
//   - modale depuis fw.sidebar.php via ?js=yes -> ?byjs=yes
// En mode modale il n'y a aucun entete a repeindre, d'ou l'absence de TINY.
function page(){
    $page   = CurrentPageName();
    $t      = time();
    $addon="";
    if(isset($_GET["byjs"])){
        $addon="&byjs=yes";
    }
    $tpl=new template_admin();
    // search_block declenche lui-meme ?search=...&div-id=results-<hash> et rend
    // la table dans SON div. Ajouter un second LoadAjax vers ?list=yes affichait
    // la table DEUX fois (22 lignes pour 11 ports) -- le fragment etait correct,
    // c'est la page qui le demandait deux fois.
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->search_block($page,"","","","&t=$t$addon");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

// La feuille de style de la table d'entites. Emise a chaque rendu du fragment
// (le fragment peut arriver seul, hors du document initial).
function openports_table_style(){
    return "<style>
.awc-wrap{color:#111d23;background:#FFFFFF;padding:16px;border-radius:12px;margin:10px 0}
.awc-wrap *{box-sizing:border-box}
.awc-card{background:#fff;border:1px solid #bcc9c6;border-radius:12px;overflow:hidden}
.awc-scroll{overflow-x:auto}
.awc-tbl{width:100%;text-align:left;border-collapse:collapse}
.awc-tbl thead tr{border-bottom:1px solid #bcc9c6}
.awc-tbl thead th{padding:14px 20px;font-size:12px;font-weight:600;line-height:16px;letter-spacing:.06em;text-transform:uppercase;color:#3d4947;white-space:nowrap}
.awc-tbl tbody tr{border-top:1px solid rgba(188,201,198,.3);transition:background .15s}
.awc-tbl tbody tr:first-child{border-top:0}
.awc-tbl tbody tr:hover{background:#fbfeff}
.awc-tbl tbody td{padding:14px 20px;vertical-align:middle}
.awc-ident{display:flex;align-items:center;gap:14px}
.awc-ico{width:40px;height:40px;border-radius:8px;background:rgba(0,106,98,.1);color:#006a62;display:inline-flex;align-items:center;justify-content:center;font-size:17px;flex:0 0 auto}
.awc-ico-off{background:rgba(38,50,56,.08);color:#6d7a77}
.awc-name{font-size:16px;font-weight:700;line-height:22px;color:#111d23}
.awc-meta{font-size:12px;line-height:16px;color:#3d4947;margin-top:2px}
.awc-mono{font-size:13px;color:#3d4947}
.awc-badge{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;padding:3px 7px;border-radius:4px;color:#fff}
.awc-badge-on{background:#865300}
.awc-badge-off{background:#e8ecec;color:#3d4947}
.awc-chips{display:flex;flex-wrap:wrap;gap:8px 16px;align-items:center}
.awc-chip{display:inline-flex;align-items:center;gap:8px}
.awc-chip-ico{width:28px;height:28px;border-radius:8px;background:rgba(0,106,98,.1);color:#006a62;display:inline-flex;align-items:center;justify-content:center;font-size:13px;flex:0 0 auto}
.awc-chip span.awc-chip-tx{color:#111d23;font-size:14px}
.awc-none{color:#6d7a77;font-style:italic;font-size:14px}
.awc-empty{padding:28px 20px;text-align:center;color:#6d7a77;font-size:14px}
.awc-foot{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:14px 20px;border-top:1px solid rgba(188,201,198,.3);color:#3d4947;font-size:13px}
.awc-pg{display:flex;gap:6px;align-items:center}
.awc-pg a{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 9px;border:1px solid #bcc9c6;border-radius:4px;background:#fff;color:#111d23;text-decoration:none;font-size:13px;font-weight:600}
.awc-pg a:hover{border-color:#006a62;color:#006a62}
.awc-pg a.awc-pg-cur{background:#006a62;border-color:#006a62;color:#fff}
.awc-pg a.awc-pg-off{color:#bcc9c6;cursor:default}
.awc-pg a.awc-pg-off:hover{border-color:#bcc9c6;color:#bcc9c6}
@media(max-width:820px){.awc-tbl thead{display:none}
.awc-tbl tbody td{display:block;padding:6px 16px}
.awc-tbl tbody tr{display:block;padding:10px 0}}
</style>";
}

// Pagination cote client: les lignes sont deja dans le DOM, paginer revient a
// les montrer/cacher. Chaine en apostrophes simples -- en guillemets doubles PHP
// mangerait chaque $identifiant du JavaScript.
function openports_pager_js($pagesize){
    $js='
<script>
(function(){
 var tb=document.getElementById("awc-rows-ports"); if(!tb){return;}
 var box=document.getElementById("awc-pager-ports");
 var rows=tb.getElementsByTagName("tr");
 var size=__SIZE__, n=rows.length, cur=1;
 var pages=Math.ceil(n/size); if(pages<1){pages=1;}
 if(n<=size){ if(box){box.innerHTML="";} return; }
 function draw(){
   for(var i=0;i<n;i++){ rows[i].style.display=(i>=(cur-1)*size && i<cur*size)?"":"none"; }
   if(!box){return;}
   var h="";
   h+="<a href=\"javascript:blur();\" class=\""+(cur==1?"awc-pg-off":"")+"\" data-p=\""+(cur-1)+"\">&lsaquo;</a>";
   var end=Math.min(pages,cur+2); var start=Math.max(1,end-4); end=Math.min(pages,start+4);
   for(var p=start;p<=end;p++){
     h+="<a href=\"javascript:blur();\" class=\""+(p==cur?"awc-pg-cur":"")+"\" data-p=\""+p+"\">"+p+"</a>";
   }
   h+="<a href=\"javascript:blur();\" class=\""+(cur==pages?"awc-pg-off":"")+"\" data-p=\""+(cur+1)+"\">&rsaquo;</a>";
   box.innerHTML=h;
   var as=box.getElementsByTagName("a");
   for(var k=0;k<as.length;k++){
     as[k].onclick=function(){
       var p=parseInt(this.getAttribute("data-p"),10);
       if(p>=1 && p<=pages && p!=cur){ cur=p; draw(); }
       return false;
     };
   }
 }
 draw();
})();
</script>';
    return str_replace("__SIZE__",intval($pagesize),$js);
}

function list_ports(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tt         = isset($_GET["t"])?intval($_GET["t"]):time();
    $ByJs       = isset($_GET["byjs"]);
    // Liste blanche au point de LECTURE: cette valeur part telle quelle dans du
    // JavaScript (OnClick, $jsafter), un ?function=... force serait execute.
    $function   = preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $search     = trim((string)(isset($_GET["search"])?$_GET["search"]:""));
    if($search=="*"){$search="";}

    // Les ports ouverts sont lus par le demon dans /proc. L'ancien chemin
    // passait par framework/network.php open-ports=yes, qui lancait
    // `lsof -i -nPM` vers UN fichier partage que cette page relisait ensuite:
    // deux administrateurs rafraichissant en meme temps lisaient l'instantane
    // de l'autre.
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/openports"));
    if(!is_object($json)){
        echo $tpl->div_error("{open_ports}: no answer from the Artica daemon");
        return;
    }
    if(empty($json->Status)){
        echo $tpl->div_error("{open_ports}: ".htmlspecialchars((string)$json->Error));
        return;
    }

    $ports = property_exists($json,"ports") && is_array($json->ports) ? $json->ports : array();
    $total = count($ports);
    $rows  = array();

    foreach ($ports as $p){
        $port      = intval($p->port);
        $protocols = is_array($p->protocols)?$p->protocols:array();
        $addresses = is_array($p->addresses)?$p->addresses:array();
        $processes = is_array($p->processes)?$p->processes:array();

        // Le filtre porte sur ce que l'operateur voit: numero de port, protocole,
        // adresse, nom de processus, PID et utilisateur.
        if($search<>""){
            $haystack=$port." ".@implode(" ",$protocols)." ".@implode(" ",$addresses);
            foreach ($processes as $proc){
                $haystack.=" ".$proc->name." ".$proc->pid." ".$proc->user;
            }
            if(stripos($haystack,$search)===false){continue;}
        }

        // Ecouter sur 0.0.0.0 ou :: expose le service a tout le reseau; une
        // adresse precise le restreint. C'est l'information que l'on vient
        // chercher sur cette page.
        $exposed=false;
        $addrChips=array();
        foreach ($addresses as $a){
            $label=$a;
            if($a=="0.0.0.0" || $a=="::"){$exposed=true;$label="{all}";}
            $addrChips[]="<span class='awc-chip'><span class='awc-chip-ico'><i class='fas fa-ethernet'></i></span>"
                ."<span class='awc-chip-tx'>".htmlspecialchars($label)."</span></span>";
        }
        $addrCell=count($addrChips)?"<div class='awc-chips'>".@implode("",$addrChips)."</div>":"<span class='awc-none'>-</span>";

        $procChips=array();
        $users=array();
        foreach ($processes as $proc){
            $procChips[]="<span class='awc-chip'><span class='awc-chip-ico'><i class='fas fa-gear'></i></span>"
                ."<span class='awc-chip-tx'>".htmlspecialchars((string)$proc->name)." <span class='awc-mono'>(".intval($proc->pid).")</span></span></span>";
            if(trim((string)$proc->user)<>""){$users[(string)$proc->user]=true;}
        }
        // Un socket sans processus identifie n'est pas une anomalie: le
        // proprietaire peut appartenir a un espace de noms que l'on ne lit pas.
        $procCell=count($procChips)?"<div class='awc-chips'>".@implode("",$procChips)."</div>":"<span class='awc-none'>{unknown}</span>";
        $userCell=count($users)?htmlspecialchars(@implode(", ",array_keys($users))):"<span class='awc-none'>-</span>";

        $badge=$exposed
            ?"<span class='awc-badge awc-badge-on'>{publicly_exposed}</span>"
            :"<span class='awc-badge awc-badge-off'>{restricted_access}</span>";

        $h ="<tr>";
        $h.="<td><div class='awc-ident'>"
           ."<span class='awc-ico".($exposed?"":" awc-ico-off")."'><i class='fas fa-plug'></i></span>"
           ."<span><span class='awc-name'>".$port."</span>"
           ."<div class='awc-meta'>".htmlspecialchars(@implode(", ",$protocols))."</div>"
           ."</span></div></td>";
        $h.="<td>".$addrCell."</td>";
        $h.="<td>".$procCell."</td>";
        $h.="<td class='awc-mono'>".$userCell."</td>";
        $h.="<td class='center'>".$badge."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{listening_port}</th>";
    $html[]="<th>{listen_address}</th>";
    $html[]="<th>{processes}</th>";
    $html[]="<th>{running_as}</th>";
    $html[]="<th class='center'>{exposure}</th>";
    $html[]="</tr></thead><tbody id='awc-rows-ports'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='5'><div class='awc-empty'>{no_open_ports}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>".count($rows)." / $total {open_ports}</span>";
    $html[]="<span class='awc-pg' id='awc-pager-ports'></span>";
    $html[]="</div></div></div>";

    // Le TINY repeint l'entete de la page parente: il n'a pas lieu d'etre quand
    // le fragment est affiche dans une modale.
    $jstiny="";
    if(!$ByJs){
        $topbuttons=array();
        // $function est la fonction de rafraichissement generee par search_block:
        // l'appeler preserve le filtre en cours.
        $refresh=($function<>"")?"$function();":"";
        $topbuttons[]=array($refresh,ico_refresh,"{refresh}");
        $TINY_ARRAY=array();
        $TINY_ARRAY["TITLE"]="{network_interfaces}: {open_ports}";
        $TINY_ARRAY["ICO"]="fas fa-plug";
        $TINY_ARRAY["EXPL"]="{network_interfaces_explain}";
        $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
        $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    }

    // ICON_SCRIPTS_COMPILE (et non ->ICON_SCRIPTS) : il draine le global, donc
    // il rend les scripts d'icones quelle que soit l'instance qui a bati la ligne.
    $iconjs="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny</script>";

    // La CSS et le JS sont concatenes AUTOUR du parse, jamais dedans:
    // _ENGINE_parse_body lirait chaque bloc {...} comme un {token}.
    echo openports_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$iconjs
        .openports_pager_js(25);
}
