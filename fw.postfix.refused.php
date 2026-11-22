<?php
// Post SP10 - 13/10/2026
// fw.postfix.refused.php — SMTP refused messages dashboard (Chart.js refactor).
// KPI cards + doughnut/bar of the top refusal reasons + week trend line, fed by the
// cached aggregates PIE_REFUSED_SMTP / GRAPH_REFUSED_SMTP_WEEK. Drill-down to
// fw.postfix.transactions.php per reason. PHP 7.3 floor: no arrow functions, no
// typed properties, no `??=`, no spread-in-array.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsPostfixAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["tabs"]))                  { tabs();                  exit; }
if(isset($_GET["statistics"]))            { statistics();            exit; }
if(isset($_GET["chart-data"]))            { chart_data();            exit; }
if(isset($_GET["postfix-refused-table"])) { postfix_refused_table(); exit; }
page();

// ---------------------------------------------------------------------------
// page shell — standard page_header + tabs content
// ---------------------------------------------------------------------------
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header(
        "{rejected_blocked_messages}", "fas fa-ban", "{rejected_blocked_explain}",
        "$page?tabs=yes",                    // content URL (auto-LoadAjax)
        "postfix-refused",                   // status key
        "progress-postfix-refused", false,   // progress div, no searchbox
        "postfix-refused-messages");         // loading div id
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{rejected_blocked_messages}",$html);
        echo $tpl->build_firewall();return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array=array();
    $array["{statistics}"]="$page?statistics=yes";
    $array["{events}"]="fw.postfix.transactions.php?refused=yes";
    echo $tpl->tabs_default($array);
}

// ---------------------------------------------------------------------------
// data helpers
// ---------------------------------------------------------------------------
function refused_pie(){
    $p=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PIE_REFUSED_SMTP")));
    if(!is_array($p)){return array();}
    arsort($p);
    return $p;
}
function refused_week(){
    $w=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GRAPH_REFUSED_SMTP_WEEK")));
    if(!is_array($w)||!isset($w["xdata"])||!is_array($w["xdata"])){return array("xdata"=>array(),"ydata"=>array());}
    if(!isset($w["ydata"])||!is_array($w["ydata"])){$w["ydata"]=array();}
    return $w;
}

// ---------------------------------------------------------------------------
// compaction des raisons de refus
// ---------------------------------------------------------------------------
/**
 * Compacte une raison de refus brute (colonne smtplog.reason) en un libelle lisible et en une
 * cle de regroupement.
 *
 * Postfix journalise le refus distant tel qu'il l'a recu: "host X[ip] said: <reponse SMTP>". Une
 * reponse SMTP multi-ligne REPETE son code sur chaque ligne de continuation ("550-5.7.25 ..."),
 * et Google/Microsoft y ajoutent un identifiant de session unique. Le texte fait alors plus de
 * 600 caracteres dont l'essentiel est du protocole, et deux refus identiques portent deux chaines
 * differentes: illisible ET ingroupable.
 *
 * Retour: host, domain, code, text, command, full (debruite mais fidele), short (libelle),
 * key (identite de groupe).
 */
function pfxref_compact($raw){
    $out=array("host"=>"","domain"=>"","code"=>"","text"=>"","command"=>"","full"=>"","short"=>"","key"=>"");
    $s=trim(preg_replace('/\s+/u'," ",(string)$raw));
    if($s===""){ return $out; }

    // 1. "host mx.example.com[192.0.2.1] said:" — le serveur qui a refuse.
    $m=array();
    if(preg_match('/^host\s+([^\s\[]+)(?:\[[^\]]*\])?\s+(?:said|refused to talk to me)\s*:\s*(.*)$/is',$s,$m)){
        $out["host"]=strtolower($m[1]);
        $s=trim($m[2]);
    }

    // 2. "(in reply to end of DATA command)" — l'etape SMTP, gardee a part.
    if(preg_match('/\(in reply to ([^)]*)\)\s*$/i',$s,$m)){
        $out["command"]=trim($m[1]);
        $s=trim(preg_replace('/\s*\(in reply to [^)]*\)\s*$/i',"",$s));
    }

    // 3. Le code de tete: "550-5.7.25", "451 4.7.1" ou "550".
    $base="";
    if(preg_match('/^(\d{3})[-\s]\s*(\d\.\d{1,3}\.\d{1,3})?\s*/',$s,$m)){
        $base=$m[1];
        $out["code"]=trim($m[1]." ".(isset($m[2])?$m[2]:""));
        $s=trim(substr($s,strlen($m[0])));
    }

    // 4. Chaque ligne de continuation rappelle le code: on le retire pour recoller la phrase.
    //    Seul le code DE TETE est efface, pas n'importe quel groupe de trois chiffres suivi d'un
    //    tiret: une reponse SMTP multi-ligne repete toujours le meme code, alors qu'un texte
    //    libre peut contenir « port 587-590 » — que la forme generique amputait en « port 590 ».
    //    Les lignes sont rejointes par une ESPACE et rien d'autre: un serveur coupe a la fin
    //    d'une phrase (Wortmann), un autre au milieu d'une ligne de 78 colonnes (Gmail), et rien
    //    dans le texte ne permet de les distinguer — ponctuer ici inventerait une phrase.
    if($base!==""){
        $s=preg_replace('/\s*\b'.$base.'[-\s]\s*\d\.\d{1,3}\.\d{1,3}\s*/'," ",$s);
        $s=preg_replace('/\s*\b'.$base.'-\s*/'," ",$s);
    }

    // 5. L'identifiant de session colle en fin de reponse ("...84 - gsmtp"): unique par message,
    //    donc a la fois illisible et fatal au regroupement.
    $s=preg_replace('/\s+[A-Za-z0-9._-]{8,}\s+-\s+[a-z]*smtp\b\.?\s*$/i',"",$s);

    $s=trim(preg_replace('/\s+/u'," ",$s));
    $s=preg_replace('/\s+([.,;])/','$1',$s);
    $out["text"]=$s;

    // Le domaine, pas la machine: mail.wortmann.de et mail2.wortmann.de sont deux MX qui opposent
    // le meme refus. La question posee a cette page est « qui refuse mes messages », et la reponse
    // est le domaine; le nom exact de la machine reste dans le libelle complet.
    if($out["host"]!==""){
        $labels=explode(".",$out["host"]);
        $n=count($labels);
        $out["domain"]=($n>=2)?$labels[$n-2].".".$labels[$n-1]:$out["host"];
    }

    $full=$s;
    if($out["code"]!==""){ $full=$out["code"]." ".$full; }
    if($out["host"]!==""){ $full=$out["host"].": ".$full; }
    $out["full"]=trim($full);

    $short=$s;
    if($out["code"]!==""){ $short=$out["code"]." ".$short; }
    if($out["domain"]!==""){ $short=$out["domain"].": ".$short; }
    $out["short"]=trim($short);

    // La cle de groupe est plus agressive que l'affichage, et le code SMTP en est EXCLU: Postfix
    // journalise la meme cause tantot avec, tantot sans (constate sur une appliance: « Syntax
    // error in parameters or arguments » et « 501 5.5.2 Syntax error in parameters or arguments »
    // sont deux lignes du meme refus).
    $k=strtolower($s);
    $k=preg_replace('#https?://\S+#'," ",$k);                    // les URL d'aide varient par code
    $k=preg_replace('/\[[0-9a-f:.]+\]/'," ",$k);                 // les adresses citees
    $k=preg_replace('/\b(?=[a-z0-9._-]*[0-9])(?=[a-z0-9._-]*[a-z])[a-z0-9._-]{12,}\b/'," ",$k); // les identifiants
    $k=trim(preg_replace('/\s+/'," ",preg_replace('/[^a-z ]+/'," ",$k)));
    if($k===""){ return $out; }                                   // pas de cle => raison intacte
    $out["key"]=($out["domain"]!==""?$out["domain"]:"")."|".substr($k,0,110);
    return $out;
}

/**
 * Les categories que le daemon (postfix/maillog/RefusalCategory.go) sait produire. Liste FERMEE:
 * elle est ce qui distingue une cle « 550 no_ptr » d'une raison brute qui lui ressemblerait, et
 * c'est aussi la liste des jetons {smtp_refusal_<slug>} a declarer dans en/c.langage.txt.
 */
function pfxref_category_slugs(){
    return array("no_ptr","auth_policy","rbl","policy","spam","virus","greylisting",
        "user_unknown","mailbox_full","rate_limit","relay_denied","auth_required","tls_policy",
        "size_exceeded","sender_rejected","helo_rejected","syntax_error","host_unreachable",
        "mail_loop","service_unavailable","blacklist_acl","milter_discard","rejected","other");
}

/**
 * Reconnait une cle de categorie « <code> <slug> » ou « <slug> ». Retourne array(code,slug) ou
 * null quand la cle est une raison brute — ce qui arrive tant que le daemon de l'appliance est
 * plus ancien que cette page, et que la page doit continuer d'afficher correctement.
 */
function pfxref_parse_category($key){
    $m=array();
    if(!preg_match('/^(?:(\d{3})\s+)?([a-z][a-z0-9_]*)$/',(string)$key,$m)){return null;}
    if(!in_array($m[2],pfxref_category_slugs(),true)){return null;}
    return array("code"=>$m[1],"slug"=>$m[2]);
}

/**
 * Regroupe le camembert brut par cause. Une cause = une cle de pfxref_compact(); ses hits sont
 * la somme de ses formulations, et ses raisons brutes sont conservees pour le drill-down.
 */
function refused_groups(){
    $groups=array();
    foreach(refused_pie() as $raw=>$hits){
        $raw=(string)$raw;
        $hits=intval($hits);

        // Le daemon a deja fait le classement: la cle EST la cause. Il n'y a alors rien a
        // compacter ni a regrouper, et le drill-down interroge la colonne, pas le texte.
        $cat=pfxref_parse_category($raw);
        if($cat!==null){
            $label=trim($cat["code"]." {smtp_refusal_".$cat["slug"]."}");
            if(!isset($groups[$raw])){
                $groups[$raw]=array("hits"=>0,"top"=>-1,"short"=>$label,"full"=>$label,
                    "domain"=>"","hosts"=>array(),"raw"=>array(),"variants"=>1,"category"=>$raw);
            }
            $groups[$raw]["hits"]+=$hits;
            continue;
        }

        $c=pfxref_compact($raw);
        $key=($c["key"]!=="")?$c["key"]:strtolower(trim($raw));
        if($key===""){continue;}
        if(!isset($groups[$key])){
            $groups[$key]=array("hits"=>0,"top"=>-1,"short"=>$c["short"],"full"=>$c["full"],
                "domain"=>$c["domain"],"hosts"=>array(),"raw"=>array(),"variants"=>0,"category"=>"");
        }
        $groups[$key]["hits"]+=$hits;
        $groups[$key]["raw"][]=$raw;
        $groups[$key]["variants"]++;
        if($c["host"]!==""){ $groups[$key]["hosts"][$c["host"]]=true; }
        // Le libelle montre est celui de la formulation la plus frequente: si la majorite des
        // lignes porte le code SMTP, il doit rester visible.
        if($hits>$groups[$key]["top"]){
            $groups[$key]["top"]=$hits;
            $groups[$key]["short"]=($c["short"]!=="")?$c["short"]:$raw;
            $groups[$key]["full"]=($c["full"]!=="")?$c["full"]:$raw;
        }
    }
    $out=array_values($groups);
    usort($out,"pfxref_cmp_hits");
    return $out;
}
function pfxref_cmp_hits($a,$b){
    if($a["hits"]==$b["hits"]){return 0;}
    return ($a["hits"]<$b["hits"])?1:-1;
}

/** Tronque sur une frontiere de mot; le libelle complet reste dans le title/tooltip. */
function pfxref_ellipsis($s,$max){
    $s=(string)$s;
    if(strlen($s)<=$max){return $s;}
    $cut=substr($s,0,$max);
    $sp=strrpos($cut," ");
    if($sp!==false && $sp>intval($max*0.6)){$cut=substr($cut,0,$sp);}
    return rtrim($cut,",;: ")."…";
}

/**
 * La clause de drill-down. Une cause classee par le daemon interroge la colonne de categorie —
 * c'est le seul moyen exact, puisque la page ne connait plus les textes qui la composent. Une
 * raison brute (daemon plus ancien) interroge toutes les formulations du groupe, pas seulement
 * la majoritaire.
 */
function pfxref_where($group){
    if(isset($group["category"]) && $group["category"]!==""){
        return "WHERE reason_category='".str_replace("'","''",$group["category"])."'";
    }
    $in=array();
    foreach($group["raw"] as $r){ $in[]="'".str_replace("'","''",$r)."'"; }
    if(count($in)==0){return "";}
    return "WHERE reason IN (".@implode(",",$in).")";
}

// chart-data — one JSON payload for the whole dashboard (charts + KPIs).
function chart_data(){
    header("Content-Type: application/json;charset=UTF-8");
    $groups=refused_groups();
    $week=refused_week();
    $tpl=new template_admin();
    $total=0;$reasons=array();
    foreach($groups as $g){
        $total=$total+$g["hits"];
        // Ce JSON ne passe PAS par _ENGINE_parse_body: un libelle de categorie y arriverait en
        // {smtp_refusal_no_ptr} dans la legende du graphique. Il est resolu ici, et ses entites
        // HTML decodees — un canvas n'en decode aucune.
        $short=html_entity_decode($tpl->_ENGINE_parse_body($g["short"]),ENT_QUOTES);
        $full=html_entity_decode($tpl->_ENGINE_parse_body($g["full"]),ENT_QUOTES);
        // « reason » reste la cle du libelle affiche (legendes, cartes); « full » porte le
        // message debruite complet, que le tooltip montre au survol.
        $reasons[]=array("reason"=>$short,"full"=>$full,"hits"=>$g["hits"]);
    }
    echo json_encode(array(
        "total"=>$total,
        "distinct"=>count($groups),
        "reasons"=>$reasons,
        "week"=>array("x"=>array_values($week["xdata"]),"y"=>array_values($week["ydata"])),
    ));
}

// ---------------------------------------------------------------------------
// statistics tab — cards + charts skeleton + table + TINY + JS
// ---------------------------------------------------------------------------
function statistics(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=array();
    $html[]="<div style='margin:10px 0'>";

    // KPI cards.
    $html[]="<div id='refused_cards'>";
    foreach(array("total"=>"{total}","distinct"=>"{reason}","top_hits"=>"TOP {hits}") as $id=>$label){
        $html[]="<div class='refused-card'><div class='v' id='refcard_$id'>-</div><div class='l'>".$tpl->_ENGINE_parse_body($label)."</div></div>";
    }
    $html[]="<div class='refused-card' style='min-width:320px'><div class='v' id='refcard_top_reason' style='font-size:13px;line-height:1.3'>-</div><div class='l'>TOP ".$tpl->_ENGINE_parse_body("{reason}")."</div></div>";
    $html[]="</div>";

    // Charts — FIXED-HEIGHT position:relative wrappers (Chart.js responsive contract:
    // without them, maintainAspectRatio:false grows the canvas forever).
    $html[]="<div class='refused-charts'>";
    $html[]=refused_chartbox("{messages} ({over_time})","refused_week_line",240,"98%");
    $html[]=refused_chartbox("TOP {refused}","refused_doughnut",280,"38%");
    $html[]=refused_chartbox("TOP {refused} / {hits}","refused_bar",280,"57%");
    $html[]="</div>";

    // Reasons table (server-rendered fragment: tokens + drill-down links).
    $html[]="<div class='refused-tablebox'><div id='postfix-refused-table'></div></div>";

    $html[]="<style>
      .refused-card{display:inline-block;min-width:130px;margin:6px;padding:12px 16px;border:1px solid #e5e5e5;border-radius:6px;background:#fafafa;vertical-align:top}
      .refused-card .v{font-size:22px;font-weight:bold;color:#c0392b}
      .refused-card .l{font-size:10px;color:#777;text-transform:uppercase}
      .refused-charts{margin-top:10px}
      .refused-charts .chartbox{display:inline-block;vertical-align:top;margin:1%;padding:10px;border:1px solid #eee;border-radius:6px;background:#fff}
      .refused-charts .chartbox h4{margin:0 0 8px;font-size:13px;color:#555}
      .refused-tablebox{margin:1%;padding:10px;border:1px solid #eee;border-radius:6px;background:#fff}
    </style>";
    $html[]="</div>";

    // TINY (mandatory) — header title/icon/buttons.
    $topbuttons=array();
    $topbuttons[]=array("RefusedStatsLoad();", ico_refresh, "{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{rejected_blocked_messages}";
    $TINY["ICO"]="fas fa-ban";
    $TINY["EXPL"]="{rejected_blocked_explain}";
    $TINY["URL"]="postfix-refused";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
    $html[]="<script>$tiny</script>";

    $html[]=refused_js($page);
    echo $tpl->_ENGINE_parse_body($html);
}

function refused_chartbox($title,$canvas,$height,$width){
    $tpl=new template_admin();
    return "<div class='chartbox' style='width:$width'><h4>".$tpl->_ENGINE_parse_body($title)."</h4><div style='position:relative;height:{$height}px;width:100%'><canvas id='$canvas'></canvas></div></div>";
}

// ---------------------------------------------------------------------------
// reasons table — footable, width discipline, per-reason drill-down
// ---------------------------------------------------------------------------
function postfix_refused_table(){
    $tpl=new template_admin();
    $groups=refused_groups();
    $t=time();

    // La barre est relative au motif le plus frequent: une colonne de nombres ne
    // montre pas ou se concentrent les refus.
    $max=0;
    foreach($groups as $g){ if($g["hits"]>$max){$max=$g["hits"];} }

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{reason}</th>";
    $html[]="<th>{hits}</th>";
    $html[]="</tr></thead><tbody id='pfxref-rows'>";
    if(count($groups)==0){
        $html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
    }
    foreach($groups as $g){
        $html[]=pfxref_row($tpl,$g,$max);
    }
    $shown=count($groups);
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $shown / $shown</span>";
    $html[]="<span class='awc-pg' id='pfxref-pager'></span>";
    $html[]="</div></div></div>";

    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js(25,"pfxref-rows","pfxref-pager");
}

/** Une cause de refus: libelle compacte cliquable vers les messages concernes, et son poids. */
function pfxref_row($tpl,$group,$max):string{
    $hits=intval($group["hits"]);
    $w=($max>0)?round(($hits/$max)*100):0;

    // La requete voyage encodee en base64 DANS une URL: sans urlencode, un « + » du base64
    // arrive comme une espace et la clause est detruite en silence.
    // Pentest 2026-11-04 : la clause second-query etait executee telle quelle ; elle est signee et la valeur echappee.
    $js2="Loadjs('fw.postfix.transactions.php?second-query=".signed_html_token(pfxref_where($group))
        ."&title=".urlencode("{see_messages_with}: ".pfxref_ellipsis($group["short"],60))."')";

    // Ce que la compaction a mis de cote reste accessible: le libelle complet au survol, les
    // machines et le nombre de formulations sur la ligne de detail.
    $meta=array();
    if(count($group["hosts"])>0){
        $hosts=array_keys($group["hosts"]);
        sort($hosts);
        $meta[]=htmlspecialchars(@implode(", ",array_slice($hosts,0,3)))
            .((count($hosts)>3)?" +".(count($hosts)-3):"");
    }
    if($group["variants"]>1){ $meta[]=$group["variants"]." {variants}"; }

    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico awc-ico-off'><i class='fas fa-circle-xmark'></i></span>";
    $h.="<span><span class='awc-name' title=\"".htmlspecialchars($group["full"],ENT_QUOTES)."\">"
        .$tpl->td_href(htmlspecialchars(pfxref_ellipsis($group["short"],150)),"{see_messages_with}",$js2)."</span>";
    if(count($meta)>0){ $h.="<div class='awc-meta'>".@implode(" &middot; ",$meta)."</div>"; }
    $h.="</span>";
    $h.="</div></td>";
    $h.="<td style='min-width:200px'><div style='display:flex;align-items:center;gap:10px'>";
    $h.="<div style='flex:1 1 auto;min-width:70px;height:8px;border-radius:4px;background:rgba(38,50,56,.10);overflow:hidden'>";
    $h.="<div style='width:$w%;height:100%;background:#ba1a1a'></div></div>";
    $h.="<span class='awc-seen'>".number_format($hits,0," "," ")."</span>";
    $h.="</div></td>";
    $h.="</tr>";
    return $h;
}

// ---------------------------------------------------------------------------
// front-end JS — Chart.js (dynamic load), destroy-before-recreate, decoded labels
// ---------------------------------------------------------------------------
function refused_js($page){
    $tpl=new template_admin();
    // Labels handed to Chart.js canvas legends: decode HTML entities PHP-side
    // (canvas text never decodes them).
    $L=array();
    foreach(array("messages","refused","hits","reason","no_data","loading") as $k){
        $L[$k]=html_entity_decode($tpl->_ENGINE_parse_body("{".$k."}"),ENT_QUOTES);
    }
    $LJSON=json_encode($L);

    $js="<script>
var REFUSED_L=$LJSON;
var refusedCharts={};
function refusedEnsureChart(cb){ if(typeof Chart!=='undefined'){cb();return;} var s=document.createElement('script'); s.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js'; s.onload=function(){cb();}; document.head.appendChild(s); }
function RefusedDestroyCharts(){ for(var k in refusedCharts){ if(refusedCharts[k]){ try{refusedCharts[k].destroy();}catch(e){} } } refusedCharts={}; }
function refusedTrim(s,n){ s=String(s||''); return s.length>n?s.substring(0,n)+'…':s; }
function refusedColors(){ return ['#c0392b','#e0902f','#8e44ad','#1f7ac4','#2e9e5b','#16a085','#7f8c8d','#d35400','#2c3e50','#27ae60']; }

function RefusedStatsLoad(){
    $.get('$page?chart-data=yes',function(raw){
        var d; try{d=(typeof raw==='object')?raw:JSON.parse(raw);}catch(e){return;}
        refusedEnsureChart(function(){
            RefusedDestroyCharts();
            RefusedRenderCards(d);
            RefusedRenderWeek(d.week||{});
            RefusedRenderTop(d.reasons||[]);
        });
    });
    LoadAjaxSilent('postfix-refused-table','$page?postfix-refused-table=yes');
}

function RefusedRenderCards(d){
    function set(id,v){ var e=document.getElementById(id); if(e){e.innerHTML=v;} }
    set('refcard_total',(d.total||0).toLocaleString());
    set('refcard_distinct',d.distinct||0);
    var top=(d.reasons&&d.reasons.length)?d.reasons[0]:null;
    set('refcard_top_hits',top?top.hits.toLocaleString():'0');
    var el=document.getElementById('refcard_top_reason');
    // Le libelle est deja compacte cote serveur; le title porte le message complet debruite.
    if(el){ el.textContent=top?refusedTrim(top.reason,90):REFUSED_L.no_data; el.title=top?(top.full||top.reason):''; }
}

function RefusedRenderWeek(w){
    var el=document.getElementById('refused_week_line'); if(!el)return;
    refusedCharts['week']=new Chart(el,{type:'line',
        data:{labels:w.x||[],datasets:[{label:REFUSED_L.messages,data:w.y||[],borderColor:'#c0392b',backgroundColor:'rgba(192,57,43,.10)',fill:true,tension:.25,pointRadius:2}]},
        options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
                 plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
}

function RefusedRenderTop(reasons){
    var top=reasons.slice(0,10);
    var labels=top.map(function(r){return refusedTrim(r.reason,42);});
    var full=top.map(function(r){return r.full||r.reason;});
    var data=top.map(function(r){return r.hits;});
    var tt={callbacks:{title:function(items){ return full[items[0].dataIndex]; }}};
    var d1=document.getElementById('refused_doughnut');
    if(d1){ refusedCharts['doughnut']=new Chart(d1,{type:'doughnut',
        data:{labels:labels,datasets:[{data:data,backgroundColor:refusedColors()}]},
        options:{responsive:true,maintainAspectRatio:false,
                 plugins:{legend:{position:'right',labels:{boxWidth:10,font:{size:10}}},tooltip:tt}}}); }
    var d2=document.getElementById('refused_bar');
    if(d2){ refusedCharts['bar']=new Chart(d2,{type:'bar',
        data:{labels:labels,datasets:[{data:data,backgroundColor:'#c0392b'}]},
        options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',
                 plugins:{legend:{display:false},tooltip:tt},scales:{x:{beginAtZero:true}}}}); }
}

$(document).ready(function(){ setTimeout(RefusedStatsLoad,300); });
</script>";
    return $js;
}
?>
