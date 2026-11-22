<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

// Pentest 2026-11-04 : aucune action de la page (synchro, suppression, effacement,
// lecture des copies) ne verifiait de droit. Meme droit que les autres pages du reverse proxy.
$users=new usersMenus();
if(!$users->AsWebMaster){exit();}
// Budgets de l'apercu en ligne : definis AVANT l'aiguillage (?view= sort par exit plus bas).
define("WEBCOPY_INLINE_MAX_FILE",2*1024*1024);
define("WEBCOPY_INLINE_MAX_TOTAL",12*1024*1024);

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["list"])){list_start();exit;}
if(isset($_GET["list-table"])){list_table();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["sync-js"])){sync_js();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search"])){events_search();exit;}
if(isset($_POST["delete"])){delete_perform();exit;}
if(isset($_POST["sync"])){sync_perform();exit;}
if(isset($_GET["rule-id"])){rule_id_js();exit();}
if(isset($_GET["view"])){view();exit;}
if(isset($_GET["view2"])){view2();exit;}
if(isset($_GET["erase-js"])){erase_js();exit;}
if(isset($_GET["not-found-js"])){not_found_js();exit;}
if(isset($_GET["not-found-popup"])){not_found_popup();exit;}
if(isset($_POST["erase"])){erase_perform();exit;}

page();

// Pentest 2026-11-04 : resout un chemin sous /home/artica/WebCopy (realpath, liens compris) ; "" s'il en sort ou n'existe pas.
function webcopy_safe_path($file):string{
    $root=realpath("/home/artica/WebCopy");
    $real=realpath((string)$file);
    if($root===false || $real===false){return "";}
    if(strpos($real,$root."/")!==0){return "";}
    return $real;
}
// Pentest 2026-11-04 : une copie de site est du contenu ETRANGER (pages, scripts, SVG
// aspires sur Internet). Elle etait servie telle quelle sur l'origine de la console :
// ses scripts tournaient avec la session de l'administrateur (XSS stockee).
// Elle est maintenant rendue dans une iframe sandbox SANS allow-same-origin : origine
// opaque, aucun acces a la session ni au DOM de la console. Une origine opaque
// n'envoie pas le cookie de session, donc les JS/CSS sont inclus dans le document et
// les images passees en data: ; les liens entre pages remontent par postMessage.

// Chemin local d'une ressource de la copie, ou "" s'il sort de /home/artica/WebCopy.
function webcopy_file($encoded_path,$fname):string{
    $fname=(string)$fname;
    if(strpos($fname,"?")!==false){$fname=substr($fname,0,strpos($fname,"?"));}
    if(strpos($fname,"#")!==false){$fname=substr($fname,0,strpos($fname,"#"));}
    $path=base64_decode((string)$encoded_path);
    $file=str_replace("//","/","/home/artica/WebCopy/$path/$fname");
    $file=webcopy_safe_path($file);
    if($file==="" || !is_file($file)){return "";}
    return $file;
}

// Traduit un lien de la page copiee en (chemin encode, nom de fichier) dans la copie.
// Meme logique que l'ancienne reecriture : "../<site>/<url>" vise un autre site de la
// meme copie (httrack), le reste est relatif a la racine du site courant.
function webcopy_resolve($encoded_path,$href){
    $href=trim((string)$href);
    if($href=="" || $href[0]=="#"){return null;}
    if(preg_match("#^[a-z][a-z0-9+.-]*:#i",$href)){return null;}   // http:, data:, javascript:, mailto: ...
    if(substr($href,0,2)=="//"){return null;}
    if(preg_match("#^\.\./([^/]+)/(.+)#",$href,$re)){
        preg_match("#^([0-9]+)/#",base64_decode((string)$encoded_path),$rs);
        if(!isset($rs[1])){return null;}
        $xst=parse_url("http://{$re[1]}/{$re[2]}");
        if(!isset($xst["path"])){return null;}
        return array(base64_encode("{$rs[1]}/{$re[1]}"),$xst["path"]);
    }
    $xst=parse_url("http://www.data.com/$href");
    if(!isset($xst["path"])){return null;}
    return array((string)$encoded_path,$xst["path"]);
}

function webcopy_inline_read($file,&$budget){
    $size=@filesize($file);
    if($size===false || $size>WEBCOPY_INLINE_MAX_FILE || $size>$budget){return null;}
    $data=@file_get_contents($file);
    if($data===false){return null;}
    $budget-=strlen($data);
    return $data;
}

// Document HTML autonome a placer dans le srcdoc de l'iframe.
function webcopy_inline_doc($content,$encoded_path):string{
    $content=(string)$content;
    if(!mb_check_encoding($content,"UTF-8")){$content=mb_convert_encoding($content,"UTF-8","ISO-8859-1");}
    $budget=WEBCOPY_INLINE_MAX_TOTAL;
    $doc=new DOMDocument();
    $prev=libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">'.$content);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    foreach (iterator_to_array($doc->childNodes) as $child){
        if($child->nodeType==XML_PI_NODE){$doc->removeChild($child);}
    }
    $xpath=new DOMXpath($doc);

    foreach (iterator_to_array($xpath->query("//script[@src]")) as $node){
        $res=webcopy_resolve($encoded_path,$node->getAttribute("src"));
        if($res===null){continue;}
        $node->removeAttribute("src");
        $file=webcopy_file($res[0],$res[1]);
        if($file===""){continue;}
        $js=webcopy_inline_read($file,$budget);
        if($js===null){continue;}
        $node->appendChild($doc->createTextNode(str_ireplace("</script","<\\/script",$js)));
    }

    foreach (iterator_to_array($xpath->query("//link[@href]")) as $node){
        if(stripos($node->getAttribute("rel"),"stylesheet")===false){continue;}
        $res=webcopy_resolve($encoded_path,$node->getAttribute("href"));
        if($res===null){continue;}
        $file=webcopy_file($res[0],$res[1]);
        $css=($file==="")?null:webcopy_inline_read($file,$budget);
        if($css===null){$node->parentNode->removeChild($node);continue;}
        $style=$doc->createElement("style");
        $style->appendChild($doc->createTextNode(str_ireplace("</style","<\\/style",$css)));
        $node->parentNode->replaceChild($style,$node);
    }

    $mimes=array("png"=>"image/png","jpg"=>"image/jpeg","jpeg"=>"image/jpeg","gif"=>"image/gif",
        "webp"=>"image/webp","svg"=>"image/svg+xml","ico"=>"image/x-icon","bmp"=>"image/bmp");
    foreach (iterator_to_array($xpath->query("//img[@src]")) as $node){
        $res=webcopy_resolve($encoded_path,$node->getAttribute("src"));
        if($res===null){continue;}
        $node->removeAttribute("srcset");
        $ext=strtolower(pathinfo($res[1],PATHINFO_EXTENSION));
        $file=webcopy_file($res[0],$res[1]);
        $data=($file==="" || !isset($mimes[$ext]))?null:webcopy_inline_read($file,$budget);
        if($data===null){$node->removeAttribute("src");continue;}
        $node->setAttribute("src","data:{$mimes[$ext]};base64,".base64_encode($data));
    }

    foreach (iterator_to_array($xpath->query("//a[@href]")) as $node){
        $res=webcopy_resolve($encoded_path,$node->getAttribute("href"));
        if($res===null){continue;}
        if(!preg_match("#\.(html?|txt)$#i",$res[1])){continue;}
        $node->setAttribute("href","#");
        $node->setAttribute("data-wc-e",$res[0]);
        $node->setAttribute("data-wc-f",base64_encode($res[1]));
    }

    $nav="document.addEventListener('click',function(e){var a=e.target.closest?e.target.closest('a[data-wc-e]'):null;if(!a){return;}e.preventDefault();parent.postMessage({wcE:a.getAttribute('data-wc-e'),wcF:a.getAttribute('data-wc-f')},'*');});";
    $script=$doc->createElement("script");
    $script->appendChild($doc->createTextNode($nav));
    $body=$doc->getElementsByTagName("body")->item(0);
    if($body===null){$body=$doc->documentElement;}
    if($body!==null){$body->appendChild($script);}
    return (string)$doc->saveHTML();
}

// Page de la console : une iframe sandbox plein ecran, et la navigation entre pages.
function webcopy_frame_page($content,$encoded_path,$title):bool{
    $page=CurrentPageName();
    $srcdoc=req_html(webcopy_inline_doc($content,$encoded_path));
    $pagejs=req_js($page);
    header("X-Content-Type-Options: nosniff");
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>".req_html($title)."</title>";
    echo "<style>html,body{margin:0;height:100%;overflow:hidden}iframe{border:0;width:100%;height:100%;display:block}</style></head><body>";
    echo "<iframe id='webcopy-frame' sandbox='allow-scripts' referrerpolicy='no-referrer' srcdoc=\"$srcdoc\"></iframe>";
    echo "<script>(function(){var fr=document.getElementById('webcopy-frame');";
    echo "window.addEventListener('message',function(e){if(e.source!==fr.contentWindow||!e.data||typeof e.data!=='object'){return;}";
    echo "var d=e.data;if(typeof d.wcE!=='string'||typeof d.wcF!=='string'||d.wcE.length>2048||d.wcF.length>2048){return;}";
    echo "location.href='$pagejs?view2='+encodeURIComponent(d.wcE)+'&fname='+encodeURIComponent(d.wcF);});})();</script>";
    echo "</body></html>";
    return true;
}

function view2(){
    $encoded_path=(string)$_GET["view2"];
    $fname=base64_decode((string)$_GET["fname"]);
    $addon=isset($_GET["addon"])?str_replace("..","",(string)$_GET["addon"]):"";
    $fname=str_replace("..","",$fname);
    if($addon<>null){
        if($fname=="/index.html"){$fname="/$addon";}
    }
    $index_file=webcopy_file($encoded_path,$fname);   // Pentest 2026-11-04 : chemin et nom venaient de la requete (lecture de n'importe quel fichier)
    if($index_file===""){
        http_response_code(404);
        echo "<html><head></head><body><H1>404 Not Found</H1></body></html>";
        return false;
    }
    return webcopy_frame_page(@file_get_contents($index_file),$encoded_path,basename($fname));
}

function view(){

    $ID=intval($_GET["view"]);
    $hostname=null;
    $enforceuri=base64_decode($_GET["src"]);
    if(preg_match("#^http.*?\/\/#",$enforceuri)){
        $hh=parse_url($enforceuri);
        $hostname=$hh["host"];
    }
    if($hostname==null){
        // Pentest 2026-11-04 : src etait recopie sans encodage dans la page (XSS).
        echo "<html><head></head><body><H1>(".req_html($enforceuri).") 404 Not Found</H1></body></html>";
        return false;
    }

    $path="$ID/$hostname";
    $encoded_path=base64_encode($path);
    $index_file=webcopy_safe_path("/home/artica/WebCopy/$path/index.html");   // Pentest 2026-11-04 : hostname vient de src
    if($index_file==="" || !is_file($index_file)){
        echo "<html><head>".req_html($hostname)."</head><body><H1>404 Not Found</H1></body></html>";
        return false;

    }
    return webcopy_frame_page(@file_get_contents($index_file),$encoded_path,$hostname);
}
function not_found_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["not-found-js"]);
    $ligne=fetch_httrack_site($ID);
    $enforceuri=isset($ligne["enforceuri"])?$ligne["enforceuri"]:"";
    $tpl->js_dialog1("$enforceuri {files_not_found}","$page?not-found-popup=$ID");
}
function not_found_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["not-found-popup"]);
    $ligne=fetch_httrack_site($ID);
    $t=time();
    // unserialize() rend FALSE sur une valeur absente ou corrompue: le foreach
    // qui suit levait alors une TypeError en PHP 8.
    $notfound=@unserialize(base64_decode((string)($ligne["notfound"] ?? "")));
    if(!is_array($notfound)){$notfound=array();}

    // Le tableau n'ouvrait AUCUN <tbody>.
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{urls}</th>";
    $html[]="</tr></thead><tbody>";
    // La premiere cellule portait DEUX attributs style (le second ignore) et la
    // seconde un width=99% NON QUOTE contenant un %.
    foreach ($notfound as $url=>$none){
        $html[]="<tr id='".md5((string)$url)."'>"
            ."<td><div class='awc-ident'>"
            ."<span class='awc-ico'><i class='fa-solid fa-bug'></i></span>"
            ."<span class='awc-name awc-mono' style='word-break:break-all'>"
            .htmlspecialchars((string)$url)."</span>"
            ."</div></td></tr>";
    }
    if(count($notfound)==0){
        $html[]="<tr><td class='awc-empty'>{no_data}</td></tr>";
    }
    $html[]="</tbody></table></div></div></div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));

}

function enable_js():bool{
    $ID=intval($_GET["enable-js"]);
    $tpl=new template_admin();
    $ligne=fetch_httrack_site($ID);
    $enforceuri=isset($ligne["enforceuri"])?$ligne["enforceuri"]:"";
    if(intval(isset($ligne["enabled"])?$ligne["enabled"]:0)==0){$enable=1;}else{$enable=0;}

    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"update","table"=>"httrack_sites","set"=>["enabled"=>$enable],"where"=>["ID"=>$ID]]);
    admin_tracks("Set WebCopy enable=$enable for $enforceuri reversed website");
    return true;

}
function sync_js(){
    $function=$_GET["function"];
    $ID=intval($_GET["sync-js"]);
    $tpl=new template_admin();
    $ligne=fetch_httrack_site($ID);

    if(intval(isset($ligne["enabled"])?$ligne["enabled"]:0)==0){
        $tpl->js_error("{this_feature_is_disabled}");
        return false;
    }
    $enforceuri=isset($ligne["enforceuri"])?$ligne["enforceuri"]:"";

    $launch=$tpl->framework_buildjs(
        "nginx.php?webcopy-sync=$ID",
        "webcopy-$ID.progress",
        "webcopy-$ID.log",
        "webcopy-progress",
        "$function()"
    );

    echo $tpl->js_confirm_execute("$enforceuri: {synchronize_data}","sync",$ID,$launch);
    return true;
}

function sync_perform(){
    $ID=intval($_POST["sync"]);
    $ligne=fetch_httrack_site($ID);
    $enforceuri=isset($ligne["enforceuri"])?$ligne["enforceuri"]:"";
    admin_tracks("Running WebCopy synchronization for $enforceuri service");


}

function delete_js(){
    $ID=intval($_GET["delete-js"]);
    $tpl=new template_admin();
    $ligne=fetch_httrack_site($ID);
    $md=$_GET["md"];
    $servicename=isset($ligne["enforceuri"])?$ligne["enforceuri"]:"";
    $jsafter="$('#$md').remove();";
    $tpl->js_confirm_delete("WebCopy #$ID ( $servicename )","delete",$ID,$jsafter);
}
function erase_js(){
    $ID=intval($_GET["erase-js"]);
    $function=$_GET["function"];
    $tpl=new template_admin();
    $ligne=fetch_httrack_site($ID);
    $md=$_GET["md"];
    $servicename=isset($ligne["enforceuri"])?$ligne["enforceuri"]:"";

    $launch=$tpl->framework_buildjs(
        "nginx.php?erase-sync=$ID",
        "webcopy-$ID.progress",
        "webcopy-$ID.log",
        "webcopy-progress",
        "$function()"
    );

    $text=$tpl->_ENGINE_parse_body("{empty_content_ask}");
    $text=str_replace("%s","$ID ( $servicename )",$text);
    $tpl->js_confirm_execute("$text","erase",$ID,$launch);

}
function erase_perform(){
    $ID=intval($_POST["erase"]);
    $ligne=fetch_httrack_site($ID);
    // Daemon owns the httrack_sites.actiondel migration (DBConf.go FixTables);
    // PHP-side ALTER TABLE no longer needed.
    $serviceid=intval(isset($ligne["enforceuri"])?$ligne["enforceuri"]:0);
    admin_tracks("$serviceid mirrored website content cleaned");
}

function delete_perform(){
    $ID=intval($_POST["delete"]);
    $ligne=fetch_httrack_site($ID);
    // Daemon owns the httrack_sites.actiondel migration (DBConf.go FixTables).
    $serviceid=intval(isset($ligne["enforceuri"])?$ligne["enforceuri"]:0);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?webcopy-delete=$ID");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"update","table"=>"httrack_sites","set"=>["actiondel"=>"1"],"where"=>["ID"=>$ID]]);
    admin_tracks("$serviceid mirrored website removed");
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("WebCopy","fad fa-copy","{webcopy_explain}","$page?tabs=yes","webcopy-status",
        "webcopy-progress");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: WebCopy {status}",$html);
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body($html);

}

function events(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,null);
}
function events_search(){
    $time=null;
    $sock=new sockets();
    $tpl=new template_admin();
    $date=null;
    $MAIN=$tpl->format_search_protocol($_GET["search"],false,true);
    $line=base64_encode(serialize($MAIN));
    $sock->getFrameWork("nginx.php?webcopy-events=$line");
    $filename=PROGRESS_DIR."/webcopy.syslog";
    $date_text=$tpl->_ENGINE_parse_body("{date}");
    $events=$tpl->_ENGINE_parse_body("{events}");
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
        ."<table class='awc-tbl awc-tbl-compact'><thead><tr>"
        ."<th class='center'>$date_text</th>"
        ."<th class='center'>PID</th>"
        ."<th>{websites}</th>"
        ."<th>$events</th>"
        ."</tr></thead><tbody id='wcev-rows'>";
    $data=explode("\n",@file_get_contents($filename));
    if(count($data)>3){$_SESSION["SMTP_NOTIFS_SEARCH"]=$_GET["search"];}
    krsort($data);


    foreach ($data as $line){
        $line=trim($line);
        $rulename=null;
        $ACTION=null;
        if(!preg_match("#(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)]:\s+\[service:([0-9]+)\]:(.+)#",$line,$re)){continue;}

        $date=$re[1]." ".$re[2]." ".$re[3];

        $pid=$re[4];
        $serviceid=$re[5];
        $line=$re[6];


        if(preg_match("#(Failed|Invalid|error|Traceback)#i", $line)){
            $line="<span class='text-danger'>$line</span>";
        }
        if(preg_match("#Accepted password#i", $line)){
            $line="<span class='text-success'>$line</span>";
        }
        if(preg_match("#(Server listening|Accepted keyboard-interactive)#i", $line)){
            $line="<span class='text-success'>$line</span>";
        }

        if(preg_match("#(warning|restarting|disabled|removed|authentication failures|authentication failure|Did not receive identification string)#i", $line)){
            $line="<span class='text-warning'>$line</span>";
        }


        if(!isset($SERVICES[$serviceid])) {
            $ligne=fetch_httrack_site($serviceid);
            $enforceuri=isset($ligne["enforceuri"])?$ligne["enforceuri"]:"";
            $urls=parse_url($enforceuri);
            $SERVICES[$serviceid]=isset($urls["host"])?$urls["host"]:"";
        }
        $www=$SERVICES[$serviceid];
        // "==" au lieu de "=": la comparaison etait evaluee puis jetee, le repli
        // {unknown} ne s'appliquait donc JAMAIS.
        if($www==null){$www="{unknown}";}

        $html[]="<tr>"
            ."<td class='center awc-nowrap awc-mono'>".htmlspecialchars($date)."</td>"
            ."<td class='center awc-nowrap awc-mono'>".htmlspecialchars($pid)."</td>"
            ."<td class='awc-nowrap'>".htmlspecialchars($www)."</td>"
            ."<td>$line</td>"
            ."</tr>";

    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='wcev-pager'></span></div>";
    $html[]="</div></div>";
    // Le motif relu du fichier partait BRUT dans la page.
    $html[]="<div><i>".htmlspecialchars((string)@file_get_contents(PROGRESS_DIR."/smtpd.syslog.pattern"))."</i></div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js(100,"wcev-rows","wcev-pager");



}

function list_start(){
    $page = CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&list-table=yes");

}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{status}"]="$page?table=yes";
	$array["{mirrored_websites}"]="$page?list=yes";
    $array["{events}"]="$page?events=yes";
	echo $tpl->tabs_default($array);
}



function list_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $function=$_GET["function"];

    $sync=$tpl->framework_buildjs(
        "nginx.php?webcopy-syncall=yes",
        "webcopy.synchronize.progress",
        "webcopy.synchronize.log",
        "webcopy-progress","$function()"

    );

    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.nginx.httrack.php?ruleid=0&function=$function');\"><i class='fa fa-plus'></i> {new_mirror_site} </label>";

    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"$sync\"><i class='fal fa-sync-alt'></i> {synchronize} </label>";

    $bts[]="</div>";
    // Les quatre colonnes d'action -- consulter, synchroniser, activer, vider,
    // supprimer -- tiennent desormais dans une seule colonne d'actions.
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{websites}</th>";
    $html[]="<th class='center'>{size}</th>";
    $html[]="<th class='center'>{last_update}</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='wc-rows'>";


    $results = fetch_httrack_sites_all();

    $TRCLASS=null;


    foreach($results as $index=>$ligne) {
        $md=md5(serialize($ligne));
        $time_text=$tpl->icon_nothing();
        $TRCLASS=null;
        $ID=$ligne["ID"];

        $serviceid=intval($ligne["serviceid"]);
        $NGInxligne=fetch_nginx_service_row($serviceid);
        $servicename=isset($NGInxligne["servicename"])?$NGInxligne["servicename"]:"";
        $enforceuri=$ligne["enforceuri"];
        $enforceuri_enc=base64_encode($enforceuri);
        $js="Loadjs('fw.nginx.httrack.php?ruleid=$ID&function=$function')";
        $size=FormatBytes($ligne["size"]/1024);
        $lasttime=intval($ligne["lasttime"]);
        if($lasttime>0){$time_text=$tpl->time_to_date($lasttime,true);}
        $actiondel=intval($ligne["actiondel"]);

        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')");
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md&function=$function')");
        $icon_erase=$tpl->icon_erase("Loadjs('$page?erase-js=$ID&md=$md&function=$function')");
        $sync=$tpl->icon_run("Loadjs('$page?sync-js=$ID&md=$md&function=$function')");
        $enforceuri=$tpl->td_href($enforceuri,"",$js);

        $view=$tpl->icon_loupe(true,"s_PopUpFull('$page?view=$ID&src=$enforceuri_enc','1024','900');");
        if($actiondel==1){
            $enabled=$tpl->icon_nothing();
            $view=$tpl->icon_nothing();
            $sync=$tpl->icon_nothing();
            $delete=$tpl->icon_nothing();
        }
        $notfound_ico=null;
        $notfound=@unserialize(base64_decode((string)($ligne["notfound"] ?? "")));
        if(!is_array($notfound)){$notfound=array();}
        $CountOfNotFound=count($notfound);
        if($CountOfNotFound>0){
            $notfound_ico="&nbsp;<small class='text-warning'><i class='text-warning fa-solid fa-light-emergency-on'></i> $CountOfNotFound {files_not_found}</small>";
            $notfound_ico=$tpl->td_href($notfound_ico,null,"Loadjs('$page?not-found-js=$ID')");
        }
        $downloaded_status=$ligne["downloaded_status"];
        if($downloaded_status<>null){
            $downloaded_status="<br><small><i class='fa-solid fa-file-circle-info'></i>&nbsp;$downloaded_status</small>";
        }

        // SEPT des huit cellules portaient DEUX attributs style, le second etant
        // silencieusement ignore par le navigateur -- donc les largeurs 1%
        // n'ont jamais rien fait. La premiere avait un width=99% non quote.
        $html[]="<tr id='$md'>"
            ."<td><div class='awc-ident'>"
            ."<span class='awc-ico'><i class='fad fa-copy'></i></span>"
            ."<span><div class='awc-name'>$enforceuri</div>"
            ."<div class='awc-meta'>$notfound_ico$downloaded_status</div></span>"
            ."</div></td>"
            ."<td class='center awc-nowrap awc-mono'>$size</td>"
            ."<td class='center awc-nowrap awc-mono'>$time_text</td>"
            ."<td><div class='awc-acts'>$view$sync$enabled$icon_erase$delete</div></td>"
            ."</tr>";
    }





    $TINY_ARRAY["TITLE"]="WebCopy";
    $TINY_ARRAY["ICO"]="fad fa-copy";
    $TINY_ARRAY["EXPL"]="{webcopy_explain}";
    $TINY_ARRAY["URL"]="webcopy-status";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($results)."</span>"
           ."<span class='awc-pg' id='wc-pager'></span></div>";
    $html[]="</div></div>";
    $html[]="<script>";
    $html[]="$jstiny;\nNoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js(100,"wc-rows","wc-pager");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?webcopy-syncall=yes");

}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $search=$_GET["search"];

    $search="*{$_GET["search"]}*";
    $search=str_replace("**","*",$search);
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);

    // Daemon API gives all httrack_sites rows; we count enabled and SUM(size)
    // PHP-side because /nginx-db/list has no aggregate functions.
    $rows=fetch_httrack_sites_all();
    $tcount=0;$tsize=0;
    foreach($rows as $r){
        if(intval(isset($r["enabled"])?$r["enabled"]:0)===1){$tcount++;}
        $tsize += intval(isset($r["size"])?$r["size"]:0);
    }

    $html[]="<div style='margin-top:20px'>&nbsp;</div>";
    $html[]="<table style='width:50%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%' nowrap>";
    if($tcount>0){
        $html[]=$tpl->widget_vert("{websites}",$tcount);
    }else{
        $html[]=$tpl->widget_grey("{websites}","{none}");
    }
    $html[]="</td>";

    $html[]="<td style='width:33%;padding-left: 15px' nowrap>";
    if($tsize>0){
        $html[]=$tpl->widget_vert("{size_on_disk}",FormatBytes($tsize/1024));
    }else{
        $html[]=$tpl->widget_grey("{size_on_disk}","{none}");
    }
    $html[]="</td>";

    $html[]="</tr>";
    $html[]="</table>";

echo $tpl->_ENGINE_parse_body($html);

	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}