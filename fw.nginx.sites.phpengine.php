<?php
// Post SP10 - 13/10/2026
//Artica Service Pack 8 - Sep 2026
// Affectation nginx_services.phpengine = phpreverse.ID pour un site type 1.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

// search_block poste ?search=…&serviceid=… — pas ?table=
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_POST["serviceid"]) && isset($_POST["phpengine"])){Save();exit;}

js();

function js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $ID=intval($_GET["serviceid"]);
    return $tpl->js_dialog5("{php_engine}","$page?table-start=$ID",900);
}

function table_start():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["table-start"]);
    $tpl=new template_admin();
    echo $tpl->search_block($page,"","","","&serviceid=$ID");
    return true;
}

/**
 * phpengine réel en base (nginx_services). Ne pas se fier à GET /site :
 * le JSON cache (php_engine) peut rester à 0 si HeadSites/Configs n'a pas
 * rechargé la colonne (binaire ancien ou Single() saute mainParams quand Hosts est déjà rempli).
 */
function site_phpengine_id_db($serviceid):int{
    $serviceid=intval($serviceid);
    if($serviceid<1){return 0;}
    $resp=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$serviceid"),true);
    if(!is_array($resp) || empty($resp["Status"]) || !isset($resp["rows"][0]) || !is_array($resp["rows"][0])){
        return 0;
    }
    $row=$resp["rows"][0];
    if(isset($row["phpengine"])){return intval($row["phpengine"]);}
    if(isset($row["php_engine"])){return intval($row["php_engine"]);}
    return 0;
}

function table():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $serviceid=intval(isset($_GET["serviceid"])?$_GET["serviceid"]:0);
    if($serviceid<1){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return true;
    }

    $current=site_phpengine_id_db($serviceid);
    $term=trim($tpl->CLEAN_BAD_XSS(isset($_GET["search"])?$_GET["search"]:""));

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-dockers/engines");
    $json=json_decode($raw,true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["Data"]) || !is_array($json["Data"])){
        $err=(is_array($json) && isset($json["Error"]) && strlen($json["Error"])>0)?$json["Error"]:"{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return true;
    }

    $html=array();
    $html[]="<div class='awc-wrap'>";
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{instance}</th>";
    $html[]="<th class='center'>{type}</th>";
    $html[]="<th class='center'>{runtime}</th>";
    $html[]="<th>{image}</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody>";

    $n=0;
    foreach($json["Data"] as $rec){
        if(!is_array($rec)){continue;}
        $id=intval(isset($rec["id"])?$rec["id"]:0);
        if($id<1){continue;}
        $name=(string)(isset($rec["instancename"])?$rec["instancename"]:"");
        $app=(string)(isset($rec["app_type"])?$rec["app_type"]:"php");
        if($app!=="wordpress"){$app="php";}
        $runtime=(string)(isset($rec["runtime_type"])?$rec["runtime_type"]:"local");
        if($runtime===""){$runtime="local";}
        $image=(string)(isset($rec["docker_image"])?$rec["docker_image"]:"");
        $enabled=intval(isset($rec["enabled"])?$rec["enabled"]:0);

        if(strlen($term)>0 && $term!=="*"){
            $hay=$name." ".$app." ".$runtime." ".$image." ".$id;
            if(stripos($hay,$term)===false){continue;}
        }

        $n++;
        $typeBadge=$app==="wordpress"
            ? "<span class='awc-badge awc-badge-on'>wordpress</span>"
            : "<span class='awc-badge awc-badge-off'>php</span>";
        $rtBadge=$runtime==="docker"
            ? "<span class='awc-badge awc-badge-on'>docker</span>"
            : "<span class='awc-badge awc-badge-off'>local</span>";

        $selected=($id===$current);
        $nameCell=htmlspecialchars($name);
        if($selected){
            $nameCell.=" <span class='awc-badge awc-badge-on'>{selected}</span>";
        }
        if($enabled!==1){
            $nameCell.=" <span class='awc-badge awc-badge-off'>{disabled}</span>";
        }

        // Success = corps vide ; sinon alert. Ferme dialog5 + refresh fiche + rebuild.
        $js="\$.post('$page',{serviceid:$serviceid,phpengine:$id}).done(function(r){"
            ."r=(r||'').toString();if(r.length>0){alert(r);return;}"
            ."try{dialogInstance5.close();}catch(e){};"
            ."LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');"
            ."Loadjs('fw.nginx.apply.php?serviceid=$serviceid');"
            ."}).fail(function(x,t,e){alert(e||t);});";
        // Bouton tableau = forme awc-badge (pas button_autnonome / btn-*).
        $btn="<a class='awc-badge awc-badge-on' href=\"javascript:blur();\" OnClick=\"$js\">{select}</a>";

        $html[]="<tr>";
        $html[]="<td><div class='awc-ident'>"
            ."<span class='awc-ico'><i class='".($app==="wordpress"?"fa-brands fa-wordpress":"fa-brands fa-php")."'></i></span>"
            ."<span class='awc-name'>#$id · $nameCell</span></div></td>";
        $html[]="<td class='center awc-nowrap'>$typeBadge</td>";
        $html[]="<td class='center awc-nowrap'>$rtBadge</td>";
        $html[]="<td class='awc-mono'>".htmlspecialchars($image)."</td>";
        $html[]="<td class='center awc-nowrap'><div class='awc-acts' style='opacity:1;justify-content:center'>$btn</div></td>";
        $html[]="</tr>";
    }
    if($n==0){
        $html[]="<tr><td colspan='5' class='awc-empty'>{no_data}</td></tr>";
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n</span></div>";
    $html[]="</div></div>";

    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

// Echo nothing on success.
function Save():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $serviceid=intval(isset($_POST["serviceid"])?$_POST["serviceid"]:0);
    $engine=intval(isset($_POST["phpengine"])?$_POST["phpengine"]:0);
    if($serviceid<1 || $engine<1){
        echo htmlspecialchars($tpl->_ENGINE_parse_body("{error}"));
        return false;
    }

    $resp=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec",array(
        "action"=>"update",
        "table"=>"nginx_services",
        "set"=>array("phpengine"=>strval($engine)),
        "where"=>array("ID"=>strval($serviceid)),
    )),true);

    if(!is_array($resp) || empty($resp["Status"])){
        $err=(is_array($resp) && isset($resp["Error"]) && strlen($resp["Error"])>0)?$resp["Error"]:"{error}";
        echo htmlspecialchars($tpl->_ENGINE_parse_body($err));
        return false;
    }

    // nginx-db/exec ne reconstruit pas {CacheConfigDir}/{id}.json — sans flush,
    // GET /site/{id} (GetCache) garderait l'ancien php_engine.
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/flush/$serviceid");
    unset($GLOBALS["/site/$serviceid"]);
    admin_tracks_post("Assign PHP engine #$engine to site #$serviceid");
    return true;
}
