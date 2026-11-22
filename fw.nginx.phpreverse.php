<?php
// stamp 01/11/2026
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// Le slash manquait: dirname(__FILE__)."ressources/..." donnait
// "/usr/share/artica-postfixressources/class.sockets.inc", un chemin
// inexistant qui emettait un avertissement a chaque requete.
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
// awc_table_style(): feuille de style partagee des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["table-main"])){table_main();exit;}
if(isset($_GET["docker-status"])){docker_status();exit;}
if(isset($_POST["docker-op"])){docker_op();exit;}
if(isset($_GET["engine-create-js"])){engine_create_js();exit;}
if(isset($_GET["engine-create-popup"])){engine_create_popup();exit;}
if(isset($_GET["engine-images-tab"])){engine_images_tab();exit;}
if(isset($_GET["search"]) && isset($_GET["engine-images"])){engine_images_search();exit;}
if(isset($_GET["engine-name-js"])){engine_name_js();exit;}
if(isset($_POST["engine-create"])){engine_create_save();exit;}
if(isset($_GET["engine-sites-js"])){engine_sites_js();exit;}
if(isset($_GET["engine-sites-popup"])){engine_sites_popup();exit;}
if(isset($_GET["search"]) && isset($_GET["engine-sites"])){engine_sites_search();exit;}
if(isset($_GET["search"]) && isset($_GET["engines-table"])){engines_table_search();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $APP_DOCKER_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");

    $html=$tpl->page_header("{APP_PHP_REVERSE} {APP_DOCKER} v$APP_DOCKER_VERSION","fa-brands fa-php",
        "{APP_PHP_REVERSE_ABOUT}","$page?tabs=yes","phpreverse",
        "progress-phpreverse-restart",false,"table-loader-phpreverse-service");
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_PHP_REVERSE} v$APP_DOCKER_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array=array();

    $APP_DOCKER_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_INSTALLED"));
    if($APP_DOCKER_INSTALLED==0){
        $TINY=array();
        $TINY["TITLE"]="{application_images}";
        $TINY["ICO"]=ico_cd;
        $TINY["EXPL"]="{application_images_explain}";
        $TINY["URL"]="reverse-docker";
        $TINY["BUTTONS"]=$tpl->table_buttons(array());
        $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
        $jsinstall="Loadjs('fw.system.upgrade-software.php?product=APP_DOCKER&after-js=reload')";
        $button=$tpl->button_autnonome("{install} {APP_DOCKER}",$jsinstall,ico_cd,"AsSquidAdministrator",350,"btn-primary",80);
        echo $tpl->div_error("{APP_DOCKER}||{docker_not_installed}<div style='margin-right:20px;margin-top:20px;text-align:right'>$button</div>");
        echo "<script>$tiny</script>";
        return;
    }

    $array["{containers}"]="$page?table=yes";
    $array["{application_images}"]="fw.nginx.docker.php?tab-images=yes";
    // TINY apres les onglets (skill §3b) — les boutons sont emis par images_search().
    echo "<div style='margin-top:10px'>".$tpl->tabs_default($array)."</div>";
}

function table():bool{
	$tpl=new template_admin();
    $page=CurrentPageName();
    $html=array();



    $html[]="<div style='display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start;'>";
    $html[]="<div style='flex:0 0 335px;max-width:100%'><div id='phpreverse-docker' style='margin-top:10px'></div></div>";
    $html[]="<div style='flex:1 1 420px;min-width:0'><div id='phpreverse-table'></div></div>";
    $html[]="</div>";
    $script="<script>"
        ."LoadAjax('phpreverse-docker','$page?docker-status=yes');"
        ."LoadAjax('phpreverse-table','$page?table-main=yes');"
        ."</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
	return true;
}

function table_main(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Hôte search_block (skill §7) — le tableau + TINY sont dans engines_table_search().
    echo "<div style='margin-top:10px'>".$tpl->search_block($page,"","","","&engines-table=yes")."</div>";
}

function engines_table_search(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $term=trim($tpl->CLEAN_BAD_XSS(isset($_GET["search"])?$_GET["search"]:""));

    // Prefer ReverseDockers engines API (local+docker); fall back to nginx-db list.
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-dockers/engines"),true);
    $results=array();
    if(is_array($data) && !empty($data["Status"]) && isset($data["Data"]) && is_array($data["Data"])){
        $results=$data["Data"];
    }else{
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/phpreverse"),true);
        $results=(is_array($data) && !empty($data["Status"]) && isset($data["rows"]) && is_array($data["rows"]))
            ? $data["rows"] : array();
    }

    $html=array();
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{instance}</th>";
    $html[]="<th class='center'>{runtime}</th>";
    $html[]="<th class='center'>{type}</th>";
    $html[]="<th class='center'>{size}</th>";
    $html[]="<th class='center'>{max_children}</th>";
    $html[]="<th class='center'>{enabled}</th>";
    $html[]="<th class='center'>{actions}</th>";
    $html[]="</tr></thead><tbody>";

    $n=0;
    foreach($results as $ligne){
        if(!is_array($ligne)){continue;}
        $name=(string)($ligne["instancename"] ?? $ligne["InstanceName"] ?? "");
        $runtime=(string)($ligne["runtime_type"] ?? $ligne["RuntimeType"] ?? "local");
        $appType=(string)($ligne["app_type"] ?? $ligne["AppType"] ?? "php");
        $image=(string)($ligne["docker_image"] ?? $ligne["DockerImage"] ?? "");
        $id=intval($ligne["ID"] ?? $ligne["id"] ?? 0);
        $dbName=(string)($ligne["db_name"] ?? $ligne["DBName"] ?? "");
        if(strlen($term)>0 && $term!=="*"){
            $hay=$name." ".$runtime." ".$appType." ".$image." ".$dbName." ".$id;
            if(stripos($hay,$term)===false){continue;}
        }
        $n++;
        $html[]=phpreverse_instance_row($ligne,$page,$function);
    }
    if($n==0){
        $html[]="<tr><td colspan='7' class='awc-empty'>{no_data}</td></tr>";
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n</span></div>";
    $html[]="</div></div>";

    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?engine-create-js=yes&function=$function')",ico_plus,"{new} Docker / WordPress");
    if($function!==""){
        $topbuttons[]=array("$function()",ico_refresh,"{refresh}");
    }

    $EnableDockerd=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDockerd"));
    if($EnableDockerd==1) {
        $jsinstall = $tpl->framework_buildjs("/dockerd/uninstall",
            "dockerd.progress",
            "dockerd.progress.log",
            "progress-phpreverse-restart", "", null, "",
            "AsSystemAdministrator"
        );
        $topbuttons[] = array("$jsinstall", ico_trash, "{uninstall}");
    }

    $TINY=array();
    $APP_DOCKER_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");
    $TINY["TITLE"]="{APP_PHP_REVERSE} {APP_DOCKER} v$APP_DOCKER_VERSION";
    $TINY["ICO"]="fa-brands fa-php";
    $TINY["EXPL"]="{APP_PHP_REVERSE_ABOUT}";
    $TINY["URL"]="phpreverse";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>NoSpinner();$tiny</script>";
}

/**
 * Une instance PHP-FPM du reverse (local ou Docker).
 */
function phpreverse_instance_row(array $ligne,$page=null,$function=""):string{
    if($page===null){$page=CurrentPageName();}
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)$function);
    $name=(string)($ligne["instancename"] ?? $ligne["InstanceName"] ?? "");
    $pm=(string)($ligne["pm"] ?? $ligne["PM"] ?? "");
    $enabled=intval($ligne["enabled"] ?? $ligne["Enabled"] ?? 0);
    $id=intval($ligne["ID"] ?? $ligne["id"] ?? 0);
    $runtime=(string)($ligne["runtime_type"] ?? $ligne["RuntimeType"] ?? "local");
    if($runtime===""){$runtime="local";}
    $appType=(string)($ligne["app_type"] ?? $ligne["AppType"] ?? "php");
    if($appType!=="wordpress"){$appType="php";}
    $sites=intval($ligne["sites_count"] ?? $ligne["SitesCount"] ?? 0);
    $dbReady=intval($ligne["db_ready"] ?? $ligne["DBReady"] ?? 0);
    $badge=$enabled==1
        ? "<span class='awc-badge awc-badge-on'>{enabled}</span>"
        : "<span class='awc-badge awc-badge-off'>{disabled}</span>";
    // .awc-badge seul = texte blanc sans fond → invisible (awc_table_style).
    $rtBadge=$runtime==="docker"
        ? "<span class='awc-badge awc-badge-on'>docker</span>"
        : "<span class='awc-badge awc-badge-off'>local</span>";
    $typeBadge=$appType==="wordpress"
        ? "<span class='awc-badge awc-badge-on'>wordpress".($dbReady==1?" · db":"")."</span>"
        : "<span class='awc-badge awc-badge-off'>php</span>";

    $actions="";
    if($runtime==="docker" && $id>0){
        $after=$function!=="" ? "$function();" : "LoadAjax('phpreverse-table','$page?table-main=yes');";
        $btn=function($op,$ico) use ($page,$id,$after){
            // YahooWinPost n'existe pas : POST jQuery + refresh tableau (contrat docker_op).
            $js="\$.post('$page',{'docker-op':'$op',id:$id}).done(function(r){r=(r||'').toString();if(r.length>0){alert(r);}$after}).fail(function(x,t,e){alert(e||t);});";
            return "<a href=\"javascript:blur();\" OnClick=\"$js\" class='btn btn-xs btn-default'><i class='$ico'></i></a> ";
        };
        $actions.=$btn("start","fa fa-play");
        $actions.=$btn("stop","fa fa-stop");
        $actions.=$btn("restart","fa fa-sync");
        $actions.=$btn("reconcile","fa fa-heartbeat");
    }

    $ico=$appType==="wordpress" ? "fa-brands fa-wordpress" : "fa-brands fa-php";
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico".($enabled==1?"":" awc-ico-off")."'><i class='$ico'></i></span>";
    $h.="<span><div class='awc-name".($enabled==1?"":" awc-name-off")."'>".htmlspecialchars($name)."</div>";
    $h.="<div class='awc-meta awc-mono'>pm: ".htmlspecialchars($pm);
    if($sites>0){
        $jsName=rawurlencode($name);
        $h.=" · <a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?engine-sites-js=$id&name=$jsName')\" style='cursor:pointer;text-decoration:underline'>sites: $sites</a>";
    }else{
        $h.=" · sites: 0";
    }
    if($appType==="wordpress" && isset($ligne["db_name"]) && strlen((string)$ligne["db_name"])>0){
        $h.=" · db: ".htmlspecialchars((string)$ligne["db_name"]);
    }
    $h.="</div></span>";
    $h.="</div></td>";
    $workdirBytes=intval($ligne["workdir_bytes"] ?? $ligne["WorkDirBytes"] ?? 0);
    // FormatBytes() attend des KILO-octets.
    $sizeText=$workdirBytes>0 ? FormatBytes($workdirBytes/1024,true) : "—";

    $h.="<td class='center'>$rtBadge</td>";
    $h.="<td class='center'>$typeBadge</td>";
    $h.="<td class='center awc-nowrap awc-mono' title='/home/artica/phpreverse/".$id."'>".htmlspecialchars($sizeText)."</td>";
    $h.="<td class='center awc-nowrap awc-mono'>".intval($ligne["max_children"] ?? $ligne["MaxChildren"] ?? 0)."</td>";
    $h.="<td class='center awc-nowrap'>$badge</td>";
    $h.="<td class='center awc-nowrap'>$actions</td>";
    $h.="</tr>";
    return $h;
}

function engine_create_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{new} Docker / WordPress","$page?engine-create-popup=yes",900);
}

function engine_create_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array=array();
    $array["PHP"]="$page?engine-images-tab=yes&app_type=php";
    $array["WordPress"]="$page?engine-images-tab=yes&app_type=wordpress";
    echo $tpl->tabs_default($array);
}

function engine_images_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $appType=trim((string)(isset($_GET["app_type"])?$_GET["app_type"]:"php"));
    if($appType!=="wordpress"){$appType="php";}
    echo $tpl->search_block($page,"","","","&engine-images=yes&app_type=".rawurlencode($appType));
}

function engine_images_search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $appType=trim((string)(isset($_GET["app_type"])?$_GET["app_type"]:"php"));
    if($appType!=="wordpress"){$appType="php";}
    $term=trim($tpl->CLEAN_BAD_XSS(isset($_GET["search"])?$_GET["search"]:""));

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-dockers/images");
    $json=json_decode($raw);
    if(!is_object($json) || (isset($json->Status) && !$json->Status)){
        $err=(is_object($json) && isset($json->Error))?$json->Error:"{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }
    $records=array();
    if(isset($json->Data) && is_array($json->Data)){$records=$json->Data;}

    $html=array();
    $html[]="<div class='awc-wrap'>";
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{image}</th>";
    $html[]="<th class='center'>{php_version}</th>";
    $html[]="<th>{description}</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody>";

    $n=0;
    foreach($records as $rec){
        if(!is_object($rec) || empty($rec->enabled)){continue;}
        $name=(string)(isset($rec->name)?$rec->name:"");
        $tag=(string)(isset($rec->tag)?$rec->tag:"");
        $desc=(string)(isset($rec->description)?$rec->description:"");
        $php=(string)(isset($rec->php_version)?$rec->php_version:"");
        $rowApp=(string)(isset($rec->app_type)?$rec->app_type:"php");
        if($rowApp!=="wordpress"){$rowApp="php";}
        if($rowApp!==$appType){continue;}
        if($name===""){continue;}
        $ref=$name.($tag!==""?":".$tag:"");
        if(strlen($term)>0 && $term!=="*"){
            if(stripos($ref,$term)===false && stripos($desc,$term)===false){continue;}
        }
        $n++;
        $js="Loadjs('$page?engine-name-js=yes&app_type=".rawurlencode($appType)
            ."&image=".rawurlencode($ref)."')";
        // Bouton tableau = forme awc-badge (pas button_autnonome / btn-*).
        $btn="<a class='awc-badge awc-badge-on' href=\"javascript:blur();\" OnClick=\"$js\">{select}</a>";
        $html[]="<tr>";
        $html[]="<td class='awc-mono'>".htmlspecialchars($ref)."</td>";
        $html[]="<td class='center awc-nowrap awc-mono'>".htmlspecialchars($php)."</td>";
        $html[]="<td>".htmlspecialchars($desc)."</td>";
        $html[]="<td class='center awc-nowrap'><div class='awc-acts' style='opacity:1;justify-content:center'>$btn</div></td>";
        $html[]="</tr>";
    }
    if($n==0){
        $html[]="<tr><td colspan='4' class='awc-empty'>{no_data}</td></tr>";
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n</span></div>";
    $html[]="</div></div>";

    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function engine_name_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $appType=trim((string)(isset($_GET["app_type"])?$_GET["app_type"]:"php"));
    if($appType!=="wordpress"){$appType="php";}
    $image=trim((string)(isset($_GET["image"])?$_GET["image"]:""));
    if(strlen($image)<3){
        echo "alert('image');";
        return;
    }
    // KeyID porte app_type + image pour le POST js_prompt (champ = nom d'instance).
    $key=base64_encode($appType."\t".$image);
    $ico=$appType==="wordpress"?"fab fa-wordpress":"fa-brands fa-php";
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $refresh=$function!=="" ? "$function();" : "LoadAjax('phpreverse-table','$page?table-main=yes');";
    $jsafter="try{dialogInstance2.close();}catch(e){};$refresh";
    $tpl->js_prompt("{give_the_instance_name}",htmlspecialchars($image),$ico,$page,"engine-create",$jsafter,"",$key);
}

// Echo nothing on success.
function engine_create_save(){
    $tpl=new template_admin();
    $name=trim((string)(isset($_POST["engine-create"])?$_POST["engine-create"]:""));
    $key=isset($_POST["KeyID"])?base64_decode((string)$_POST["KeyID"]):"";
    $parts=explode("\t",$key,2);
    $appType=(isset($parts[0])?trim($parts[0]):"php");
    $image=(isset($parts[1])?trim($parts[1]):"");
    if($appType!=="wordpress"){$appType="php";}
    if(strlen($name)<1 || strlen($image)<3){
        echo htmlspecialchars($tpl->_ENGINE_parse_body("{error}"));
        return;
    }
    $payload=array(
        "instancename"=>$name,
        "enabled"=>1,
        "runtime_type"=>"docker",
        "app_type"=>$appType,
        "docker_image"=>$image,
        "docker_instances"=>1,
    );
    $resp=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/reverse-dockers/engines",$payload),true);
    if(!is_array($resp) || empty($resp["Status"])){
        $err=(is_array($resp) && isset($resp["Error"]) && strlen($resp["Error"])>0)?$resp["Error"]:"{error}";
        echo htmlspecialchars($tpl->_ENGINE_parse_body($err));
    }
}

function engine_sites_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval(isset($_GET["engine-sites-js"])?$_GET["engine-sites-js"]:0);
    if($id<1){
        echo "alert('engine');";
        return;
    }
    $title="{sites} · #$id";
    $name=trim($tpl->CLEAN_BAD_XSS(isset($_GET["name"])?$_GET["name"]:""));
    if(strlen($name)>0){
        $title="{sites} · $name";
    }
    $tpl->js_dialog2($title,"$page?engine-sites-popup=$id",900);
}

function engine_sites_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval(isset($_GET["engine-sites-popup"])?$_GET["engine-sites-popup"]:0);
    if($id<1){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    echo $tpl->search_block($page,"","","","&engine-sites=yes&engineid=$id");
}

function engine_sites_search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $engineid=intval(isset($_GET["engineid"])?$_GET["engineid"]:0);
    if($engineid<1){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    $term=trim($tpl->CLEAN_BAD_XSS(isset($_GET["search"])?$_GET["search"]:""));

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/phpengine/$engineid");
    $json=json_decode($raw,true);
    if(!is_array($json) || empty($json["Status"])){
        $err=(is_array($json) && isset($json["Error"]) && strlen($json["Error"])>0)?$json["Error"]:"{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }
    $rows=(isset($json["rows"]) && is_array($json["rows"]))?$json["rows"]:array();

    $html=array();
    $html[]="<div class='awc-wrap'>";
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{website}</th>";
    $html[]="<th class='center'>{type}</th>";
    $html[]="<th class='center'>{enabled}</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody>";

    $n=0;
    foreach($rows as $ligne){
        if(!is_array($ligne)){continue;}
        $sid=intval(isset($ligne["ID"])?$ligne["ID"]:0);
        if($sid<1){continue;}
        $sname=(string)(isset($ligne["servicename"])?$ligne["servicename"]:"");
        $hosts=(string)(isset($ligne["hosts"])?$ligne["hosts"]:"");
        $enabled=intval(isset($ligne["enabled"])?$ligne["enabled"]:0);
        $stype=intval(isset($ligne["type"])?$ligne["type"]:0);

        if(strlen($term)>0 && $term!=="*"){
            $hay=$sname." ".$hosts." ".$sid;
            if(stripos($hay,$term)===false){continue;}
        }

        $n++;
        $badge=$enabled==1
            ? "<span class='awc-badge awc-badge-on'>{enabled}</span>"
            : "<span class='awc-badge awc-badge-off'>{disabled}</span>";
        $js="Loadjs('fw.nginx.sites.php?www-js=$sid')";
        $nameLink=$tpl->td_href($sname!==""?$sname:"#$sid",null,$js);
        // Bouton tableau = forme awc-badge (skill).
        $btn="<a class='awc-badge awc-badge-on' href=\"javascript:blur();\" OnClick=\"$js\">{edit}</a>";

        $html[]="<tr>";
        $html[]="<td><div class='awc-ident'>"
            ."<span class='awc-ico".($enabled==1?"":" awc-ico-off")."'><i class='fa-brands fa-php'></i></span>"
            ."<span><div class='awc-name".($enabled==1?"":" awc-name-off")."'>#$sid · $nameLink</div>";
        if(strlen($hosts)>0){
            $html[]="<div class='awc-meta awc-mono'>".htmlspecialchars($hosts)."</div>";
        }
        $html[]="</span></div></td>";
        $html[]="<td class='center awc-nowrap awc-mono'>$stype</td>";
        $html[]="<td class='center awc-nowrap'>$badge</td>";
        $html[]="<td class='center awc-nowrap'><div class='awc-acts' style='opacity:1;justify-content:center'>$btn</div></td>";
        $html[]="</tr>";
    }
    if($n==0){
        $html[]="<tr><td colspan='4' class='awc-empty'>{no_data}</td></tr>";
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n</span></div>";
    $html[]="</div></div>";

    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function docker_status():bool{
    $tpl=new template_admin();

    $APP_DOCKER_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_INSTALLED"));
    if($APP_DOCKER_INSTALLED==0){
        $jsinstall="Loadjs('fw.system.upgrade-software.php?product=APP_DOCKER&after-js=reload')";
        $btn[0]["name"] = "{install}";
        $btn[0]["icon"] = ico_cd;
        $btn[0]["js"] = $jsinstall;

        echo $tpl->_ENGINE_parse_body($tpl->widget_jaune("{docker_not_installed}","{not_installed}",$btn));
        return true;
    }

    $EnableDockerd=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDockerd"));
    if($EnableDockerd==0){
        $jsinstall=$tpl->framework_buildjs("/dockerd/install",
            "dockerd.progress",
            "dockerd.progress.log",
            "progress-phpreverse-restart","",null,"",
            "AsSystemAdministrator"
        );
        $btn[0]["name"] = "{install}";
        $btn[0]["icon"] = ico_cd;
        $btn[0]["js"] = $jsinstall;

        echo $tpl->_ENGINE_parse_body($tpl->widget_grey("{APP_DOCKER}","{inactive2}",$btn));
        return true;
    }

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-dockers/status"),true);
    if(!is_array($data) || empty($data["Status"])){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("{unavailable}","{APP_DOCKER}"));
        return true;
    }
    $d=isset($data["Data"]) && is_array($data["Data"]) ? $data["Data"] : array();
    $ok=!empty($d["docker_available"]);
    $msg=$ok ? "{running}" : "{unavailable}";
    if($ok){
        echo $tpl->_ENGINE_parse_body($tpl->widget_vert($msg,"{APP_DOCKER}"));
    }else{
        $err=isset($d["docker_error"]) ? htmlspecialchars((string)$d["docker_error"]) : "";
        echo $tpl->_ENGINE_parse_body($tpl->widget_jaune($msg."<br>".$err,"{APP_DOCKER}"));
    }
    return true;
}

function docker_op(){
    // Success must echo nothing (WebConsole contract).
    $op=(string)($_POST["docker-op"] ?? "");
    $id=intval($_POST["id"] ?? 0);
    if($id<=0 || $op===""){echo "bad";return;}
    $allowed=array("start"=>1,"stop"=>1,"restart"=>1,"reconcile"=>1);
    if(!isset($allowed[$op])){echo "bad op";return;}
    $path="/reverse-dockers/engines/".$id."/".$op;
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON($path,array()),true);
    if(!is_array($data) || empty($data["Status"])){
        $err=is_array($data) && isset($data["Error"]) ? (string)$data["Error"] : "error";
        echo $err;
        return;
    }
}



function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}