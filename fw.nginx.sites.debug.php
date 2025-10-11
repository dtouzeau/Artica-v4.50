<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

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
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
    $servicename=$ligne["servicename"];
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

    $html[]="<table id='table-webfilter-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='number' style='width:1%'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>PID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{event}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody class='tbody'>";
    foreach ($data as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^(.+?)\s+([0-9:]+)\s+\[(.+?)\]\s+([0-9]+)\#[0-9]+:(.+)#",$line,$re)){continue;}
        $date=$re[1]." ".$re[2];
        $pid=$re[4];
        $line=$re[5];
        $class=null;
        $strlen=strlen($line);
        if(preg_match("#failed#i",$line)){$class="text-danger";}
        $line=wordwrap($line,165,"<br>");
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap>$date</td>";
        $html[]="<td style='width:1%' nowrap>$pid</td>";
        $html[]="<td style='width:99%' class='$class' nowrap>$strlen:$line</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}