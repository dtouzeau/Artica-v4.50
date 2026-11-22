<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

$GLOBALS["CLASS_SOCKETS"]=new sockets();

// Pentest 2026-11-04 : www-tabs/siteid etaient recopies sans encodage dans la page (XSS). Ce sont des ID de site.
foreach(array("www-tabs","siteid") as $k){if(isset($_GET[$k])){$_GET[$k]=intval($_GET[$k]);}}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["download-build"])){download_js();exit;}
if(isset($_GET["download"])){download_perform();exit;}

www_js();

function download_perform(){

    $siteid=intval($_GET["download"]);
    $target_file=PROGRESS_DIR."/$siteid.debug.gz";
    $content_type="application/x-gzip";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"debug.gz\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = filesize($target_file);
    admin_tracks("Download debug file $siteid.debug.gz ( $fsize Bytes )");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($target_file);
    @unlink($target_file);
}
function download_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $siteid=intval($_GET["download-build"]);
    echo $tpl->framework_buildjs("nginx.php?debug-prepare=$siteid",
        "nginx.debug.$siteid.progress",
        "nginx.debug.$siteid.log",
        "download-build-$siteid",
        "document.getElementById('download-build-$siteid').innerHTML='';document.location.href='/$page?download=$siteid';"
    );

}


function www_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["www-tabs"];
    $array["Web Application Firewall"]="$page?www-parameters=$ID";
    $array["{whitelists}"]="$page?www-whitelists=$ID";
    echo $tpl->tabs_default($array);
}
function www_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["siteid"];
    $ngixSock=new socksngix($ID);


    $servicename=$ngixSock->GetServiceName();
    $tpl->js_dialog2("#$ID - $servicename -{debug}", "$page?popup=$ID",1200);
}
function popup(){
    //<i class="fa-sharp fa-solid fa-download"></i>
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["popup"]);
    $html[]="<div id='download-build-$ID'></div>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-bottom: 10px;margin-top: 10px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?download-build=$ID');\"><i class='fa-solid fa-download'></i> {download} </label>";
    $html[]="</div>";
    $html[]=$tpl->search_block($page,null,null,null,"&siteid=$ID");
    echo $tpl->_ENGINE_parse_body($html);
}
function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["siteid"]);
    if(isset($_GET["search"])){$_GET["search"]=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
    $MAIN=$tpl->format_search_protocol($_GET["search"],false,false,false,true);
    $RFile=PROGRESS_DIR."/$ID.syslog";
    $PFile=PROGRESS_DIR."/$ID.pattern";
    $line=base64_encode(serialize($MAIN));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?nginx-debug=$line&siteid=$ID");

    $data=explode("\n",@file_get_contents($RFile));
    krsort($data);

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th class='center'>{date}</th>";
    $html[]="<th class='center'>PID</th>";
    $html[]="<th>{event}</th>";
    $html[]="</tr></thead><tbody id='dbg-rows'>";
    foreach ($data as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^(.+?)\s+([0-9:]+)\s+\[(.+?)\]\s+([0-9]+)\#[0-9]+:(.+)#",$line,$re)){continue;}
        $date=$re[1]." ".$re[2];
        $pid=$re[4];
        $line=$re[5];
        $class=null;
        if(preg_match("#failed#i",$line)){$class="text-danger";}
        // La cellule affichait "$strlen:$line" -- la LONGUEUR de la ligne
        // prefixee au message, reste de mise au point. Elle portait de plus un
        // nowrap sur un texte deja coupe tous les 165 caracteres, ce qui
        // annulait la coupure.
        $line=wordwrap(htmlspecialchars($line),165,"<br>",true);
        $html[]="<tr>"
            ."<td class='center awc-nowrap awc-mono'>".htmlspecialchars($date)."</td>"
            ."<td class='center awc-nowrap awc-mono'>".htmlspecialchars($pid)."</td>"
            ."<td class='$class'>$line</td>"
            ."</tr>";
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='dbg-pager'></span></div>";
    $html[]="</div></div>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js(100,"dbg-rows","dbg-pager");
}