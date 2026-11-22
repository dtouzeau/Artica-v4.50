<?php
/**
 * Visionneuse des rapports SquidAnalyzer.
 *
 * Cette page n'ENGENDRE pas de tableaux: elle sert un fichier HTML produit par
 * SquidAnalyzer sous /home/artica/squidanalyzer/proxyreport/ et se contente de
 * reetiqueter son balisage pour qu'il adopte le style de la console. Les
 * tableaux restent donc ceux de l'outil externe -- ils n'ont ni cellule
 * d'identite ni colonne d'actions, et les convertir en tableaux d'entites
 * demanderait de reecrire a l'aveugle une structure que nous ne produisons pas.
 * Seules les classes sont alignees sur la feuille partagee.
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style(): feuille de style partagee des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.features.inc");

define("PROXYREPORT_ROOT","/home/artica/squidanalyzer/proxyreport");

if(isset($_GET["query"])){page();exit;}


/**
 * Le chemin reel du rapport demande, ou null s'il sort du dossier des rapports.
 *
 * "query" et "baseroot" arrivaient BRUTS de l'URL et etaient concatenes au
 * chemin passe a file_get_contents(): "?query=../../../../etc/passwd" faisait
 * lire -- et afficher -- un fichier arbitraire avec les droits du serveur web.
 * realpath() resout les "..", les liens symboliques et les doubles barres; on
 * verifie ensuite que le resultat est bien SOUS le dossier des rapports.
 */
function proxyreport_path($query,$baseroot){
    $root=realpath(PROXYREPORT_ROOT);
    if($root===false){return null;}

    $rel=trim((string)$query);
    if($rel===""){return null;}
    $base=trim((string)$baseroot);
    if($base!=="" && $base!==".."){$rel="$base/$rel";}

    $full=realpath("$root/$rel");
    if($full===false){return null;}
    // Le separateur final evite qu'un dossier voisin nomme "proxyreport-old"
    // passe le test de prefixe.
    if($full!==$root && strpos($full,$root.DIRECTORY_SEPARATOR)!==0){return null;}
    if(!is_file($full)){return null;}
    return $full;
}

function page(){

    $tpl=new template_admin();

    $query=(string)$_GET["query"];
    // isset() manquait: "baseroot" absent declenchait un avertissement.
    $baseroot=isset($_GET["baseroot"])?(string)$_GET["baseroot"]:"";

    if($baseroot===""){
        if(strpos($query,"/")>0){
            $baseroot=dirname($query);
            $query=basename($query);
        }
    }

    $fullpath=proxyreport_path($query,$baseroot);
    if($fullpath===null){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{files_not_found}"));
        return;
    }

    $f=(string)@file_get_contents($fullpath);

    if(preg_match("#\.js$#",$query)){
        header("content-type: application/x-javascript");
        echo $f;
        return;
    }

    // $body n'etait defini que si l'expression trouvait un <body>: sinon
    // _ENGINE_parse_body() recevait null (avertissement sous PHP 8).
    $body="";
    if(preg_match("#<body.*?>(.+?)</body>#is",$f,$re)){
        $body=replaces($re[1],$baseroot);
    }
    if($body===""){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data}"));
        return;
    }

    // Pentest 2026-11-04 : le rapport recopie des donnees du journal Squid (URL,
    // utilisateurs, domaines) et etait insere tel quel dans la console (XSS
    // stockee). Il est maintenant rendu dans une iframe sandbox SANS
    // allow-same-origin : ses scripts (tri, graphiques) tournent dans une
    // origine opaque, sans acces a la session ni au DOM de la console. Les
    // liens entre rapports remontent a la page par postMessage.
    $t=time().rand(100,999);
    $relbase=trim((string)$baseroot,"/");
    $base="/proxyreport/".($relbase!==""?implode("/",array_map("rawurlencode",explode("/",$relbase)))."/":"");
    $doc=array();
    $doc[]="<!DOCTYPE html><html><head><meta charset='utf-8'>";
    $doc[]="<base href=\"".req_html($base)."\" target=\"_self\">";
    $doc[]=awc_table_style();
    // Origine opaque : une requete de l'iframe partirait sans le cookie de session.
    // Les deux scripts de SquidAnalyzer sont donc inclus depuis le disque.
    foreach(array("sorttable.js","flotr2.js") as $js){
        $jsf=@file_get_contents(PROXYREPORT_ROOT."/$js");
        if($jsf===false){continue;}
        $doc[]="<script>".str_ireplace("</script","<\\/script",$jsf)."</script>";
    }
    $doc[]="</head><body style='font-family:sans-serif;font-size:13px'>";
    $doc[]=$tpl->_ENGINE_parse_body($body);
    $doc[]="<script>";
    $doc[]="document.addEventListener('click',function(e){var a=e.target.closest?e.target.closest('a[data-pr]'):null;if(!a){return;}e.preventDefault();parent.postMessage({proxyreport:a.getAttribute('data-pr')},'*');});";
    $doc[]="function prh(){parent.postMessage({proxyreportHeight:document.documentElement.scrollHeight},'*');}window.addEventListener('load',prh);setTimeout(prh,1500);";
    $doc[]="</script></body></html>";
    $srcdoc=req_html(implode("\n",$doc));
    $rootjs=req_js((string)$baseroot);

    echo "<iframe id='proxyreport-$t' sandbox='allow-scripts' referrerpolicy='no-referrer' style='width:100%;min-height:75vh;border:0' srcdoc=\"$srcdoc\"></iframe>";
    echo "<script>(function(){var fr=document.getElementById('proxyreport-$t');";
    echo "function onm(e){if(!document.body.contains(fr)){window.removeEventListener('message',onm);return;}";
    echo "if(e.source!==fr.contentWindow||!e.data||typeof e.data!=='object'){return;}";
    echo "if(typeof e.data.proxyreportHeight==='number'){fr.style.height=Math.min(Math.max(e.data.proxyreportHeight+20,300),20000)+'px';return;}";
    echo "if(typeof e.data.proxyreport==='string'&&e.data.proxyreport.length<1024){window.removeEventListener('message',onm);";
    echo "LoadAjax('MainContent','fw.proxy.report.php?query='+encodeURIComponent(e.data.proxyreport)+'&baseroot='+encodeURIComponent('$rootjs'));}}";
    echo "window.addEventListener('message',onm);})();</script>";

}

function replaces($body,$baseRoot=null){

    // Les liens du rapport sont reecrits pour rester dans le cadre AJAX de la
    // console. La substitution portait sur le corps ENTIER, pas sur l'attribut:
    // une URL courte contenue dans une plus longue etait remplacee a
    // l'interieur de celle-ci. On remplace desormais l'attribut complet, et on
    // traite les URL les plus longues d'abord.
    if(preg_match_all("#<a href=\"(.+?)\"#",$body,$re)){
        $uris=array_unique($re[1]);
        usort($uris,function($a,$b){ return strlen($b)-strlen($a); });
        foreach ($uris as $uri){
            // Le rapport est rendu dans une iframe sandbox : le clic remonte a la
            // console par postMessage (voir page()), qui recharge le rapport cible.
            $body=str_replace("<a href=\"$uri\"",
                "<a href=\"#\" data-pr=\"".req_html(html_entity_decode($uri,ENT_QUOTES,"UTF-8"))."\"",
                $body);
        }
    }

    // Reetiquetage vers la feuille partagee. Les tableaux de SquidAnalyzer
    // gardent leur structure (voir l'entete du fichier); seule leur apparence
    // est alignee. La variante compacte convient a des tableaux de chiffres
    // sans cellule d'identite.
    $body=str_replace('<table class="sortable stata','<table class="awc-tbl awc-tbl-compact sortable stata',$body);
    $body=str_replace('<table class="graphs"','<table class="awc-tbl awc-tbl-compact graphs"',$body);
    $body=str_replace('<table class="stata"','<table class="awc-tbl awc-tbl-compact"',$body);
    $body=str_replace('<table>','<table class="awc-tbl awc-tbl-compact">',$body);
    $body=str_replace('blockquote class="notification"',"blockquote class='small'",$body);
    $body=str_replace("squidanalyzer.darold.net","articatech.net",$body);
    $body=str_replace("SquidAnalyzer","{APP_SQUIDANALYZER}",$body);
    return $body;

}
