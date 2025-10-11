<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.sqstats.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["host"])){group_by_hosts();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["group-by-host"])){group_by_hosts();exit;}
if(isset($_GET["group-by-username"])){group_by_usernames();exit;}
if(isset($_GET["url-js"])){url_js();exit;}
if(isset($_GET["url-popup"])){url_popup();exit;}
if(isset($_GET["ip-js"])){ip_js();exit;}
if(isset($_GET["ip-popup"])){ip_popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
js();


function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{active_requests}";
    $dbfile         = "/home/artica/SQLITE_TEMP/ProxCons.db";
    $q              = new lib_sqlite($dbfile);
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as cnx, AVG(ztime) as ztime, SUM(size) as size FROM connections");

    $requests=$tpl->FormatNumber($ligne["cnx"]);
    $ztime=$tpl->FormatSeconds($ligne["ztime"]);
    $size=FormatBytes($ligne["size"]/1024);
    $title="$title $requests {connections}, $size, $ztime";
    return $tpl->js_dialog9($title,"$page?start=yes",990);
}
function url_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $url=$_GET["url-js"];
    $urlencoded=urlencode($url);
    return $tpl->js_dialog10("{active_requests}: $url","$page?url-popup=$urlencoded",750);
}
function ip_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $url=$_GET["ip-js"];
    $urlencoded=urlencode($url);
    return $tpl->js_dialog11("{active_requests}: $url","$page?ip-popup=$urlencoded",990);
}

function start():bool{
    $page=CurrentPageName();
    echo "<div id='active-requests-progress'></div>";
    echo "<script>LoadAjax('active-requests-progress','$page?tabs=yes');</script>";
    return true;
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $group["{websites}"]="$page?group-by-host=yes";
    $group["{members}"]="$page?group-by-username=yes";
    echo $tpl->tabs_default($group);
    return true;
}
function ip_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t              = time();
    $dbfile         = "/home/artica/SQLITE_TEMP/ProxCons.db";
    $q              = new lib_sqlite($dbfile);
    $ipaddr            = $_GET["ip-popup"];

    $resolve=false;
    $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
    if($resolveIP2HOST==1){
        $resolve=true;
    }



    $results=$q->QUERY_SQL("SELECT url,SUM(size) as size,AVG(ztime) as ztime,COUNT(*) as cnx FROM connections WHERE ipaddr='$ipaddr' GROUP BY url ORDER by size desc");
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{websites}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>{connexions}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $url=$ligne["url"];
        $cnx=$tpl->FormatNumber($ligne["cnx"]);

        $size=FormatBytes(intval($ligne["size"])/1024);
        $ztime=$ligne["ztime"];
        $ztime=$tpl->FormatSeconds($ztime);
        $url=getLinkURL($url);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><i class='".ico_earth."'></i></td>";
        $html[]="<td style='width:99%'><strong>$url</strong></td>";
        $html[]="<td width='1%' style='text-align:right'>$cnx</td>";
        $html[]="<td width='1%' style='text-align:right'>$size</td>";
        $html[]="<td style='width:1%' nowrap>>$ztime</td>";

        $html[]="</tr>";

    }
    $html[]="</table>
    <script>
        NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
        
    </script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function url_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t              = time();
    $dbfile         = "/home/artica/SQLITE_TEMP/ProxCons.db";
    $q              = new lib_sqlite($dbfile);
    $url            = $_GET["url-popup"];

    $resolve=false;
    $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
    if($resolveIP2HOST==1){
        $resolve=true;
    }



    $results=$q->QUERY_SQL("SELECT username,ipaddr,SUM(size) as size,AVG(ztime) as ztime,COUNT(*) as cnx FROM connections WHERE url='$url' GROUP BY username,ipaddr ORDER by size desc");
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>{connexions}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $url=$ligne["url"];
        $cnx=$tpl->FormatNumber($ligne["cnx"]);

        $size=FormatBytes(intval($ligne["size"])/1024);
        $ztime=$ligne["ztime"];
        $ztime=$tpl->FormatSeconds($ztime);
        $LinkMember=getLinkMember($username,$ipaddr,$resolve);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><i class='".ico_member."'></i></td>";
        $html[]="<td style='width:99%'><strong>$username</strong></td>";
        $html[]="<td style='width:1%' nowrap>>$LinkMember</td>";
        $html[]="<td width='1%' style='text-align:right'>$cnx</td>";
        $html[]="<td width='1%' style='text-align:right'>$size</td>";
        $html[]="<td style='width:1%' nowrap>>$ztime</td>";

        $html[]="</tr>";

    }
    $html[]="</table>
    <script>
        NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
        
    </script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function getLinkMember($Member,$ipaddr,$resolve):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ipaddr_text=$ipaddr;
    if($resolve){
        if(!isset($_SESSION["resolved"][$ipaddr])){
            $_SESSION["resolved"][$ipaddr]=gethostbyaddr($ipaddr);
        }
        $ipaddr_text= $_SESSION["resolved"][$ipaddr];
    }
    if($Member<>"-"){
        $ipaddr_text="$ipaddr_text&nbsp;($Member)";
    }
    return $tpl->td_href($ipaddr_text,null,"Loadjs('$page?ip-js=".urlencode($ipaddr)."')");

}

function group_by_hosts():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t              = time();
    $dbfile         = "/home/artica/SQLITE_TEMP/ProxCons.db";
    $q              = new lib_sqlite($dbfile);
    $results=$q->QUERY_SQL("SELECT url,count(ipaddr) as ipaddr,SUM(size) as size,AVG(ztime) as ztime FROM connections GROUP BY url ORDER by size desc");


    $js=$tpl->framework_buildjs("/proxy/metrics/activeconnections",
        "active-requests.progress","active-requests.logs",
         "active-requests-progress",
         "LoadAjax('active-requests-progress','$page?tabs=yes');");

    $buton=$tpl->button_tooltip("{rebuild}",$js,ico_refresh);

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>$buton</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}/{connections}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $ipaddr=$tpl->FormatNumber($ligne["ipaddr"]);
        $url=$ligne["url"];
        $LinkUrl=getLinkURL($url);

        $size=FormatBytes(intval($ligne["size"])/1024);
        $ztime=$tpl->FormatSeconds($ligne["ztime"]);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><i class='fas fa-globe'></i></td>";
        $html[]="<td style='width:99%'><strong>$LinkUrl</strong></td>";
        $html[]="<td style='width:1%;text-align:right'>$ipaddr</td>";
        $html[]="<td style='width:1%'>$size</td>";
        $html[]="<td style='width:1%' nowrap>>$ztime</td>";
        $html[]="</tr>";

    }
    $html[]="</table>
    <script>
        NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
        
    </script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function getLinkURL($url){
    $urlText=$url;
    if(preg_match("#(.+?):([0-9]+)#",$urlText,$re)){
        $urlText=$re[1];
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->td_href($urlText,null,"Loadjs('$page?url-js=".urlencode($url)."')");
}
function group_by_usernames():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t              = time();
    $dbfile         = "/home/artica/SQLITE_TEMP/ProxCons.db";
    $q              = new lib_sqlite($dbfile);
    $results=$q->QUERY_SQL("SELECT count(url) as url,ipaddr,username,SUM(size) as size FROM connections GROUP BY ipaddr,username ORDER by size desc");

    $resolve=false;
    $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
    if($resolveIP2HOST==1){
        $resolve=true;
    }

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{connections}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $url=$tpl->FormatNumber($ligne["url"]);
        $size=FormatBytes(intval($ligne["size"])/1024);
        $ztime=$ligne["ztime"];
        $LinkMember=getLinkMember($username,$ipaddr,$resolve);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><i class='".ico_member."'></i></td>";
        $html[]="<td style='width:99%'><strong>$LinkMember</strong></td>";
        $html[]="<td style='width:1%;text-align:right'>$url</td>";
        $html[]="<td style='width:1%'>$size</td>";

        $html[]="</tr>";

    }
    $html[]="</table>
    <script>
        NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
        
    </script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}


function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $resolve=false;
    $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
    if($resolveIP2HOST==1){
        $resolve=true;
    }
    $squidstat=new squidstat();
    if(!$squidstat->connect()){
        echo $tpl->FATAL_ERROR_SHOW_128("{connection failed}");
    }
    $data=$squidstat->makeQuery();
    echo $tpl->_ENGINE_parse_body($squidstat->makeHtmlReport($data,$resolve,$hosts_array=array(),$_GET["filter"]));

}