<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["worldmap"])){worldmap();exit;}
if(isset($_GET["map-data"])){worldmap_data();exit;}
if(isset($_GET["map-table"])){worldmap_table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["top-status"])){top_status();exit;}
if(isset($_GET["top-usersagents-1"])){UsersAgentsWidgets();exit;}
if(isset($_GET["top-htons-1"])){HtonsWidgets();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_POST["UseAbusesIPReputationKey"])){parameters_save();exit;}
page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $title="{APP_NGINX} {statistics}";
    $html=$tpl->page_header($title,ico_dashboard,"{statistics_nginx_explain}","$page?tabs=yes",
        "nginx-statistics",
        "progress-nginx-statistics-restart",false,
        "table-nginx-statistics"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {APP_NGINX} {statistics}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{parameters}","$page?parameters-popup=yes");
}
function parameters_popup():bool{
    $tpl=new template_admin();
    $UseAbusesIPReputationKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseAbusesIPReputationKey"));

    $form[]= $tpl->field_text("UseAbusesIPReputationKey","AbuseIP {API_KEY}",$UseAbusesIPReputationKey);
    $html[]= $tpl->form_outside("",$form,null,"{apply}", "dialogInstance2.close();", "AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function parameters_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reputation/abuseipdb");
    return admin_tracks_post("Save Reverse-Proxy statistics global settings");
}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
   // $array["{worldmap}"]="$page?worldmap=yes";
    $array["{status}"]="$page?table=yes";
    echo $tpl->tabs_default($array);
}

function HtonsWidgets(){
    $tpl=new template_admin();
    $NginxHtonsToday=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHtonsToday"));
    $NginxHtonsTotal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHtonsTotal"));

    $usaico=ico_computer;
    $widget_AbuseIP=$tpl->widget_style1("gray-bg",ico_shield_disabled,"{reputation}","{inactive}");

    $widget_today=$tpl->widget_style1("gray-bg",$usaico,"{client_source_ip_address} {today}",0);
    $widget_total=$tpl->widget_style1("gray-bg",$usaico,"{client_source_ip_address} {total}",0);
    if($NginxHtonsToday>0){
        $widget_today=$tpl->widget_style1("navy-bg",$usaico,"{client_source_ip_address} {today}",$tpl->FormatNumber($NginxHtonsToday));
    }
    if($NginxHtonsTotal>0){
        $widget_total=$tpl->widget_style1("navy-bg",$usaico,"{client_source_ip_address} {total}",$tpl->FormatNumber($NginxHtonsTotal));
    }
    $UseAbuseIPReputationKey=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseAbusesIPReputationKey");
    if(strlen($UseAbuseIPReputationKey)>10){
        $widget_AbuseIP=$tpl->widget_style1("navy-bg",ico_shield,"{reputation} ","{active2}");

        $q=new postgres_sql();
        $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM hotstinfos WHERE abuseipdb=1");
        $THreats=intval($ligne["tcount"]);
        if($THreats>0){
            $widget_AbuseIP=$tpl->widget_style1("yellow-bg",ico_shield,"{reputation} ",$tpl->FormatNumber($THreats));
        }
    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_today</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_total</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_AbuseIP</td>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function UsersAgentsWidgets():bool{
    $cloudShower="fa-solid fa-cloud-showers";
    $tpl=new template_admin();
    $NginxUserAgentsToday=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxUserAgentsToday"));
    $NginxUserAgentsTotal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxUserAgentsTotal"));
    $NginxHtonsToday=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHtonsToday"));

    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT SUM(hits) as hits FROM hotstinfos_days");
    $hits=intval($ligne["hits"]);

   $usaico="fa-regular fa-user-robot";
   $widget_rqs_users=$tpl->widget_style1("gray-bg",$cloudShower,"{requests}/{ipaddr}",0);
    if (($hits>0) AND ($NginxHtonsToday>0)){
        $thits=round($hits/$NginxHtonsToday,2);
        if($thits>100) {
            $thits = $tpl->FormatNumber($thits);
        }
        $widget_rqs_users=$tpl->widget_style1("navy-bg",$cloudShower,"{requests}/{ipaddr}","$thits");
    }




    $widget_today=$tpl->widget_style1("gray-bg",$usaico,"UserAgents {today}",0);
   $widget_total=$tpl->widget_style1("gray-bg",$usaico,"UserAgents {total}",0);
   if($NginxUserAgentsToday>0){
       $widget_today=$tpl->widget_style1("navy-bg",$usaico,"UserAgents {today}",$tpl->FormatNumber($NginxUserAgentsToday));
   }
   if($NginxUserAgentsTotal>0){
       $widget_total=$tpl->widget_style1("navy-bg",$usaico,"UserAgents {total}",$tpl->FormatNumber($NginxUserAgentsTotal));
   }




   $html[]="<table style='width:100%'>";
   $html[]="<tr>";
   $html[]="<td style='width:33%'>$widget_today</td>";
   $html[]="<td style='width:33%;padding-left:5px'>$widget_total</td>";
   $html[]="<td style='width:33%;padding-left:5px'>$widget_rqs_users</td>";
   $html[]="</table>";

   $page=CurrentPageName();
   $html[]="<script>";
   $html[]="LoadAjaxSilent('top-htons-1','$page?top-htons-1=yes');";
   $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function top_status_err($error){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $widget_rqs=$tpl->widget_style1("red-bg","fa-duotone fa-solid fa-raindrops","{requests}",$error);
    $widget_Cnxs=$tpl->widget_style1("red-bg","fa-duotone fa-solid fa-circle-nodes","{active_connections}",$error);
    $widget_events=$tpl->widget_style1("red-bg","fa-duotone fa-solid fa-circle-nodes","{events}/{second}",$error);
    $title="{APP_NGINX} {statistics}";

    $topbuttons[]=array("Loadjs('$page?parameters-js=yes');",ico_params,"{parameters}");
    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]=ico_dashboard;
    $TINY_ARRAY["EXPL"]="{statistics_nginx_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_rqs</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_Cnxs</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_events</td>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('top-usersagents-1','$page?top-usersagents-1=yes');";
    $html[]=$headsjs;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function top_status():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $page=CurrentPageName();
    $data=$sock->REST_API_NGINX("/10mins/stats");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        top_status_err("{error} #1");
        return false;
    }
    if(!$json->Status){
        top_status_err("{error} #2");
        return false;
    }
    if(!property_exists($json,"Stats")){
        top_status_err("{error} #3");
        return false;
    }
    $jsStats=$json->Stats;
    $widget_rqs=$tpl->widget_style1("gray-bg","fa-duotone fa-solid fa-raindrops","{requests}",0);
    $widget_Cnxs=$tpl->widget_style1("gray-bg",ico_earth,"{active_websites}",0);
    $widget_events=$tpl->widget_style1("gray-bg",ico_computer,"{clients}",0);


    if($jsStats->totalQuery>0){
        $totalQuery=$tpl->FormatNumber($jsStats->totalQuery);
        $widget_rqs=$tpl->widget_style1("green-bg","fa-duotone fa-solid fa-raindrops","{requests} {last_10_minutes}",$totalQuery);

    }

    if($jsStats->numberOfSites>0){
        $totalQuery=$tpl->FormatNumber($jsStats->numberOfSites);
        $widget_Cnxs=$tpl->widget_style1("navy-bg",ico_earth,"{active_websites} {last_10_minutes}",$totalQuery);
    }

    if($jsStats->numberOfClient>0){
        $totalQuery=$tpl->FormatNumber($jsStats->numberOfClient);
        $widget_events=$tpl->widget_style1("navy-bg",ico_computer,"{clients} {last_10_minutes}",$totalQuery);
    }






    $title="{APP_NGINX} {statistics}";

    $topbuttons[]=array("Loadjs('$page?parameters-js=yes');",ico_params,"{parameters}");
    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]=ico_dashboard;
    $TINY_ARRAY["EXPL"]="{statistics_nginx_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_rqs</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_Cnxs</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_events</td>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('top-usersagents-1','$page?top-usersagents-1=yes');";
    $html[]=$headsjs;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $js=$tpl->RefreshInterval_js("top-status",$page,"top-status=yes");
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;'><div id='top-status'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;'><div id='top-usersagents-1'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;'><div id='top-htons-1'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="$js";
    $html[]="</script>";
    echo implode("",$html);

}
function worldmap(){
$tpl=new template_admin();
$html[]="<table style='width:100%;margin-top:30px'>";
$html[]="<tr>";
$html[]="<td style='vertical-align:top;'><div id='world-map-container' style='width:1024px;height:400px'></div></td>";
$html[]="</tr>";
$html[]="<tr>";
$html[]="<td style='vertical-align:top;'><div id='world-map-data'></div></td>";
$html[]="</tr>";
$html[]="</table>";
$html[]=renderWorldMap();
echo $tpl->_ENGINE_parse_body($html);

}

function renderWorldMap($divId = 'world-map-container'):string{

    $page=CurrentPageName();

return "<script>
async function loadWorldMap(dataUrl) {
  try {
    // 1) Load jQuery + jVectorMap assets
    
    await loadCSS('/js/jvectormap/jquery-jvectormap-2.0.5.css');
    await loadJS('/js/jvectormap/jquery-jvectormap-2.0.5.min.js');
    await loadJS('/js/jvectormap/jquery-jvectormap-world-mill.min.js');

    // 2) Prepare inner container
    const container = document.getElementById('$divId');
    container.innerHTML ='<div id=\"$divId-inner\" style=\"width:100%; height:100%;\"></div>';

    // 3) Fetch your map-data payload
    const resp = await fetch(dataUrl);
    if (!resp.ok) throw new Error('Data load failed: ' + resp.statusText);
    const { values, links } = await resp.json();
    console.log('OK HERE');
    // 4) Render the vector map
    $('#$divId-inner').vectorMap({
      map: 'world_mill',
      backgroundColor: 'transparent',
      regionStyle: {
        initial: {
          fill: '#FFFFFF',
          stroke: '#2c2e38',
          'stroke-width': 1
        }
      },
      series: {
        regions: [{
          values: values,
          scale: [ '#6fe7e1','#005447'],  // low→high turquoise→green
          normalizeFunction: 'polynomial'
        }]
      },
      onRegionTipShow: function(e, el, code){
        // show value in tooltip if you like
        const v = values[code] || 0;
        el.html(el.html() + ' (value: ' + v + ')');
      },
      onRegionClick: function(event, code){
        if (links[code]) {
          window.open(links[code], '_blank');
        }
      }
    });

  } catch (err) {
    console.error(err);
  }
}
console.log('--> document.addEventListener');  
  loadWorldMap( '$page?map-data=yes');
  LoadAJaxSilent('worldmap-country','$page?worldmap-country=yes');
</script>";

}
function worldmap_data(){
    header('Content-Type: application/json');

    $query = "SELECT SUM(hits) as hits,hotstinfos.country 
    FROM hotstinfos_realtime,hotstinfos 
    WHERE hotstinfos_realtime.hton=hotstinfos.hton GROUP by hotstinfos.country ORDER by hits DESC";

    $q = new postgres_sql();
    $results = $q->QUERY_SQL($query);
    if (!$q->ok) {
        writelogs("$query $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }

    $page=CurrentPageName();

    while ($ligne = @pg_fetch_assoc($results)) {
        $hits = $ligne['hits'];
        $country_name = $ligne['country'];
        $values[$country_name] = $hits;
        $links[$country_name] = "Loadjs('$page?worldmap-country=$country_name');";
    }

    echo json_encode(['values'=>$values,'actions'=>$links]);
}