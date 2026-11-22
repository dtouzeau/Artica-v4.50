<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["addprivs"])){switchsitename();exit;}
if(isset($_GET["gpid"])){table();exit;}
if(isset($_GET["id-js"])){id_js();exit;}
if(isset($_GET["id-popup"])){id_popup();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["ID"])){id_save();exit;}
if(isset($_POST["delete"])){delete();exit;}
table_start();

function table_start():bool{
	$page=CurrentPageName();
	$ID=$_GET["service"];
	echo "<div id='adminprivs-$ID' style='margin-top: 10px'></div>
	<script>LoadAjax('adminprivs-$ID','$page?gpid=$ID')</script>";
    return true;
}


function switchsitename():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $serverid=intval($_GET["addprivs"]);
    $group=$_GET["gpid"];
    $rows=fetch_adminprivs_for_group($group);
    $ID=0;
    foreach($rows as $r){
        if(intval($r["serviceid"])===$serverid){$ID=intval($r["ID"]);break;}
    }
    if($ID>0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"adminprivs","where"=>["ID"=>$ID]]);
        return true;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"insert","table"=>"adminprivs","values"=>["serviceid"=>$serverid,"item"=>$group]]);
    return admin_tracks("Add $group privileges for service $serverid");

}
function delete():bool{
    $ID=$_POST["delete"];
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"adminprivs","where"=>["ID"=>$ID]]);
    return true;
}


function id_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$ID=$_GET["id-js"];
	$serviceid=$_GET["serviceid"];
	$md5=$_GET["md5"];
	$title="{rule}: $ID";
	if($ID==0){$title="{new_rule}";}
	return $tpl->js_dialog3($title, "$page?id-popup=$ID&serviceid=$serviceid&md5=$md5");
}


function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $gpid=$_GET["gpid"];
    $gpidMd5=md5($gpid);
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{websites}</th>";
	$html[]="<th class='center'>{allow}</th>";
	$html[]="</tr></thead><tbody id='priv-rows'>";

	

    // Daemon owns the adminprivs schema (nginxsites/DBConf.go FixTables).
    $services=array();
    $rows=fetch_adminprivs_for_group($gpid);
    foreach ($rows as $ligne){
        $services[intval($ligne["serviceid"])]=$ligne["ID"];
    }

    $gpidEnc=urlencode($gpid);
    // Daemon's /nginx-db/list endpoint accepts ?order=col for ORDER BY.
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_services?order=zorder");
    $data=json_decode($resp,true);
    $results=array();
    if(is_array($data) && !empty($data["Status"]) && isset($data["rows"]) && is_array($data["rows"])){
        $results=$data["rows"];
    }
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		$TRCLASS=null;
		$md5=md5(serialize($ligne));
		$ID=$ligne["ID"];
        $Enabled=0;
        if(isset($services[$ID])){
            $Enabled=1;
        }
        $serversnames=extract_hosts($ligne["hosts"]);
        $servicename=$ligne["servicename"];

        $check=$tpl->icon_check($Enabled,"Loadjs('$page?addprivs=$ID&gpid=$gpidEnc')",null,"AsWebMaster");

		// La cellule d'icone ouvrait un <td> qu'elle ne fermait JAMAIS -- le
		// navigateur avalait la cellule suivante dans la meme colonne.
		$html[]="<tr id='$md5'>"
		    ."<td><div class='awc-ident'>"
		    ."<span class='awc-ico'><i class='".ico_earth."'></i></span>"
		    ."<span><div class='awc-name'>".htmlspecialchars((string)$servicename)."</div>"
		    ."<div class='awc-meta'>$serversnames</div></span>"
		    ."</div></td>"
		    ."<td class='center awc-nowrap'>$check</td>";
		$html[]="</tr>";
	}

	// L'ancien pied donnait colspan='3' a un tableau de DEUX colonnes.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} ".count($results)."</span>"
	       ."<span class='awc-pg' id='priv-pager'></span></div>";
	$html[]="</div></div>";

	// awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n",$html))
	    ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>"
	    .awc_pager_js(100,"priv-rows","priv-pager");
    return true;

}
function extract_hosts($hosts):string{
    $f=array();

    $Zhosts=explode("||",$hosts);

    foreach ($Zhosts as $servername){
        $catch_all=null;
        $servername=trim($servername);
        if($servername==null){continue;}
        if($servername=="*"){
            $servername=".*";
            $catch_all="&nbsp;&nbsp;<span class='label label-warning'>{catch_all}</span>";
        }
        $f[]="<div style='margin-top:2px;margin-left:10px'><small><i class='".ico_link."'></i>&nbsp;$servername</small>$catch_all</div>";
        }


    return @implode("", $f);
}