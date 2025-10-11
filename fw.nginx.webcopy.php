<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

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
if(isset($_GET["script"])){view_script();exit;}
if(isset($_GET["img"])){view_img();exit;}
if(isset($_GET["erase-js"])){erase_js();exit;}
if(isset($_GET["not-found-js"])){not_found_js();exit;}
if(isset($_GET["not-found-popup"])){not_found_popup();exit;}
if(isset($_POST["erase"])){erase_perform();exit;}

page();

function view2(){
    $encoded_path=$_GET["view2"];
    $path=base64_decode($encoded_path);
    $fname=base64_decode($_GET["fname"]);
    $addon=$_GET["addon"];
    $addon=str_replace("..","",$addon);
    $fname=str_replace("..","",$fname);
    if($GLOBALS["VERBOSE"]){
        VERBOSE("$path -- $fname",__LINE__);

    }
    if($addon<>null){
        if($fname=="/index.html"){$fname="/$addon";}
    }

    if(strpos($fname,"?")>0){$ttr=explode("?",$fname);$fname=$ttr[0];}
    $index_file="/home/artica/WebCopy/$path/$fname";
    $index_file=str_replace("//","/",$index_file);
    if(!is_file($index_file)){
        if($GLOBALS["VERBOSE"]){
            VERBOSE("$index_file -- NOT FOUND",__LINE__);
        }
        http_response_code(404);
        return false;

    }
    $content= @file_get_contents($index_file);
    $content=view_parse($content,$encoded_path);
    echo $content;
    return true;
}
function view_script(){
    $encoded_path=$_GET["script"];
    $path=base64_decode($encoded_path);
    $fname=base64_decode($_GET["fname"]);
    if(strpos($fname,"?")>0){$ttr=explode("?",$fname);$fname=$ttr[0];}
    $index_file="/home/artica/WebCopy/$path/$fname";

    if(!is_file($index_file)){
        http_response_code(404);
        return false;

    }
    if(preg_match("#\.js$#",$fname)){
        header("content-type: application/x-javascript");
    }
    if(preg_match("#\.css$#",$fname)){
        header("content-type: text/css");
    }


    echo @file_get_contents($index_file);
    return true;
}
function view_img(){
    $encoded_path=$_GET["img"];
    $path=base64_decode($encoded_path);
    $fname=base64_decode($_GET["fname"]);
    if(strpos($fname,"?")>0){$ttr=explode("?",$fname);$fname=$ttr[0];}
    $index_file="/home/artica/WebCopy/$path/$fname";
    $index_file=str_replace("//","/",$index_file);

    if(!is_file($index_file)){
        http_response_code(404);
        return false;

    }
    $content_type=mime_content_type($index_file);
    header("content-type: $content_type");
    ob_clean();
    flush();
    readfile( $index_file );
    return true;
}

function view_replace_links($content,$encoded_path,$href,$type=null){
    if(isset($GLOBALS["ALREADY_URL"][$href])){return $content;}
    if($href==null){return $content;}
    if($href=="#"){return $content;}
    if(strpos(" $href","'")>0){return $content;}
    if(preg_match("#^http.*?:#",$href)){return $content;}
    $page=CurrentPageName();
    if(strpos(" $href",$page)>0){return $content;}
    $decoded_path=base64_decode($encoded_path);
    preg_match("#^([0-9]+)\/(.+)#",$decoded_path,$re);
    $siteid=$re[1];
    $orginalpath=$re[2];
    $href=trim($href);
    if(preg_match("#^\.\.\/(.+?)\/(.+)#",$href,$re)){
        $newsite=$re[1];
        $url=$re[2];
        $newEncodedPath=base64_encode("$siteid/$newsite");
        $simulkate_uri="http://$newsite/$url";
        $xst=parse_url($simulkate_uri);
        if(isset($xst["path"])){$url=$xst["path"];}

        $GLOBALS["ALREADY_URL"][$href]=true;
        if(preg_match("#\.(css|js)$#",$url)) {
            return str_replace($href, "$page?script=$newEncodedPath&fname=" . base64_encode($url)."&addon=", $content);
        }
        if(preg_match("#\.(jpeg|gif|png|jpg)$#",$url)) {
            return str_replace($href, "$page?img=$newEncodedPath&fname=" . base64_encode($url)."&addon=", $content);
        }
        if(preg_match("#\.(txt|html)$#",$url)) {
            return str_replace($href, "$page?view2=$newEncodedPath&fname=" . base64_encode($url)."&addon=", $content);
        }
        echo "\n\n\n$href --> script=$newEncodedPath&fname=$url FAILED\n";
        return $content;
    }

    $url=$href;
    $simulkate_uri="http://www.data.com/$href";
    $xst=parse_url($simulkate_uri);
    if(isset($xst["path"])){$url=$xst["path"];}

    if(preg_match("#\.(css|js)$#",$url)) {
        return str_replace($href, "$page?script=$encoded_path&fname=" . base64_encode($url)."&addon=", $content);
    }
    if(preg_match("#\.(jpeg|gif|png|jpg)$#",$url)) {
        return str_replace($href, "$page?img=$encoded_path&fname=" . base64_encode($url)."&addon=", $content);
    }
    if(preg_match("#\.(txt|html)$#",$url)) {
        return str_replace($href, "$page?view2=$encoded_path&fname=" . base64_encode($url)."&addon=", $content);
    }
    return $content;

}

function view_exploded_js($content,$encoded_path){

    $scontent=explode("\n",$content);
    foreach ($scontent as $line) {
        $line = trim($line);
        if (preg_match("#var\s+(STATIC_BASE|ASSETS_BASE).*?=.*?'(.+?)'#i", $line, $re)) {
            $content = view_replace_links($content, $encoded_path, $re[2]);
            continue;
        }
        if (preg_match('#channel-url="(.+?)"#', $line, $re)) {
            $content = view_replace_links($content, $encoded_path, $re[1]);
            continue;
        }
        if (preg_match("#var\s+[0-9a-zA-Z]+=\"(.+?)\"#", $line, $re)) {
            $content = view_replace_links($content, $encoded_path, $re[1]);
            continue;
        }
        if (preg_match("#prototype\..*?_path.*?=.*?\"(.+?)\"#", $line, $re)) {
            $content = view_replace_links($content, $encoded_path, $re[1]);
            continue;
        }
        if (preg_match("#background-image:.*?url.*?\((.+?)\)#i", $line, $re)) {
            $content = view_replace_links($content, $encoded_path, $re[1]);
            continue;
        }
    }
    return $content;
}

function view_parse($content,$encoded_path):string{
    $doc = new DOMDocument();
    $content=view_exploded_js($content,$encoded_path);
    $doc->loadHTML($content);
    $xpath = new DOMXpath($doc);
    $nodes = $xpath->query('//a');
    $nodes2= $xpath->query('//img');
    $nodes4=$xpath->query('//script');
    $stylesheets = $xpath->query("//*[name() = 'link' or name() = 'style']");

    foreach($stylesheets as $tag)  {
        $nodeName=$tag->nodeName;
            switch (strtolower($tag->nodeName)) {
                case "link":
                    $rel=trim($tag->getAttribute("rel"));
                    $type=trim($tag->getAttribute("type"));
                    $url = trim($tag->getAttribute("href"));
                    $content=view_replace_links($content,$encoded_path,$url,$type);
                    break;

                default:
                    $content=view_replace_links($content,$encoded_path,$url,$type);
            }
    }



    foreach($nodes4 as $nodes5) {
        $href=trim($nodes5->getAttribute('src'));
        $content=view_replace_links($content,$encoded_path,$href);
    }

    foreach($nodes2 as $nodes3) {
        $href=trim($nodes3->getAttribute('src'));
        $content=view_replace_links($content,$encoded_path,$href);

    }


    $ALREADY=array();
    foreach($nodes as $node) {
        $href=trim($node->getAttribute('href'));
        $content=view_replace_links($content,$encoded_path,$href);
    }

    return $content;

}

function view(){

    $ID=intval($_GET["view"]);
    $enforceuri=base64_decode($_GET["src"]);
    if(preg_match("#^http.*?\/\/#",$enforceuri)){
        $hh=parse_url($enforceuri);
        $hostname=$hh["host"];
    }
    if($hostname==null){
        echo "<html><head>$hostname</head><body><H1>($enforceuri) 404 Not Found</H1></body></html>";
        return false;
    }

    $path="$ID/$hostname";
    $encoded_path=base64_encode($path);
    $index_file="/home/artica/WebCopy/$path/index.html";
    if(!is_file($index_file)){
        echo "<html><head>$hostname</head><body><H1>404 Not Found</H1></body></html>";
        return false;

    }
    $content= @file_get_contents($index_file);
    $content=view_parse($content,$encoded_path);
    echo $content;
    return true;
}
function not_found_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["not-found-js"]);
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT enforceuri FROM httrack_sites WHERE ID='$ID'");
    $enforceuri=$ligne["enforceuri"];
    $tpl->js_dialog1("$enforceuri {files_not_found}","$page?not-found-popup=$ID");
}
function not_found_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["not-found-popup"]);
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT notfound FROM httrack_sites WHERE ID='$ID'");
    $t=time();
    $notfound=unserialize(base64_decode($ligne["notfound"]));

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{urls}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $TRCLASS=null;
    foreach ($notfound as $url=>$none){
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }

        $md=md5($url);


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='vertical-align:middle;' class='center' width=1% nowrap><i class='fa-solid fa-bug'></i></td>";
        $html[]="<td style='vertical-align:middle;' width=99% nowrap>$url</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

}

function enable_js():bool{
    $ID=intval($_GET["enable-js"]);
    $tpl=new template_admin();
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled,enforceuri FROM httrack_sites WHERE ID='$ID'");

    $enforceuri=$ligne["enforceuri"];
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
    if(intval($ligne["enabled"])==0){$enable=1;}else{$enable=0;}

    $sql="UPDATE httrack_sites SET enabled='$enable' WHERE ID='$ID'";
    writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
    $q->QUERY_SQL("UPDATE httrack_sites SET enabled='$enable' WHERE ID='$ID'");
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
    admin_tracks("Set WebCopy enable=$enable for $enforceuri reversed website");
    return true;

}
function sync_js(){
    $function=$_GET["function"];
    $ID=intval($_GET["sync-js"]);
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $tpl=new template_admin();
    $ligne=$q->mysqli_fetch_array("SELECT enabled,enforceuri FROM httrack_sites WHERE ID='$ID'");

    if($ligne["enabled"]==0){
        $tpl->js_error("{this_feature_is_disabled}");
        return false;
    }
    $enforceuri=$ligne["enforceuri"];

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
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled,enforceuri FROM httrack_sites WHERE ID='$ID'");
    $enforceuri=$ligne["enforceuri"];
    admin_tracks("Running WebCopy synchronization for $enforceuri service");


}

function delete_js(){
    $ID=intval($_GET["delete-js"]);
    $tpl=new template_admin();
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT enforceuri FROM httrack_sites WHERE ID='$ID'");
    $md=$_GET["md"];
    $servicename=$ligne["enforceuri"];
    $jsafter="$('#$md').remove();";
    $tpl->js_confirm_delete("WebCopy #$ID ( $servicename )","delete",$ID,$jsafter);
}
function erase_js(){
    $ID=intval($_GET["erase-js"]);
    $function=$_GET["function"];
    $tpl=new template_admin();
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT enforceuri FROM httrack_sites WHERE ID='$ID'");
    $md=$_GET["md"];
    $servicename=$ligne["enforceuri"];

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
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT enforceuri FROM httrack_sites WHERE ID='$ID'");

    if(!$q->FIELD_EXISTS("httrack_sites","actiondel")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD actiondel INTEGER NOT NULL DEFAULT 0");
    }
    $serviceid=intval($ligne["enforceuri"]);
    admin_tracks("$serviceid mirrored website content cleaned");
}

function delete_perform(){
    $ID=intval($_POST["delete"]);
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT enforceuri FROM httrack_sites WHERE ID='$ID'");

    if(!$q->FIELD_EXISTS("httrack_sites","actiondel")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD actiondel INTEGER NOT NULL DEFAULT 0");
    }

    $serviceid=intval($ligne["enforceuri"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?webcopy-delete=$ID");
    $q->QUERY_SQL("UPDATE httrack_sites SET actiondel=1 WHERE ID=$ID");
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
    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>PID</th>
        	<th nowrap>{websites}</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
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
            $ligne=$q->mysqli_fetch_array("SELECT enforceuri FROM httrack_sites WHERE ID=$serviceid");
            $enforceuri=$ligne["enforceuri"];
            $urls=parse_url($enforceuri);
            $SERVICES[$serviceid]=$urls["host"];
        }
        $www=$SERVICES[$serviceid];
        if($www==null){$www=="{unknown}";}

        $html[]="<tr>
				<td width=1% nowrap>$date</td>
				<td width=1% nowrap>$pid</td>
				<td width=1% nowrap>$www</td>
				<td>$line</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/smtpd.syslog.pattern")."</i></div>";
    echo $tpl->_ENGINE_parse_body($html);



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
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{websites}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{last_update}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{synchronize}</th>";
    $html[]="<th data-sortable=false>{enable}</th>";
    $html[]="<th data-sortable=false>{empty}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $results = $q->QUERY_SQL("SELECT * FROM httrack_sites ORDER BY ID DESC");

    $TRCLASS=null;


    foreach($results as $index=>$ligne) {
        $md=md5(serialize($ligne));
        $time_text=$tpl->icon_nothing();
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $ID=$ligne["ID"];

        $serviceid=intval($ligne["serviceid"]);
        $NGInxligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$serviceid");
        $servicename=$NGInxligne["servicename"];
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
        $notfound=unserialize(base64_decode($ligne["notfound"]));
        $CountOfNotFound=count($notfound);
        if($CountOfNotFound>0){
            $notfound_ico="&nbsp;<small class='text-warning'><i class='text-warning fa-solid fa-light-emergency-on'></i> $CountOfNotFound {files_not_found}</small>";
            $notfound_ico=$tpl->td_href($notfound_ico,null,"Loadjs('$page?not-found-js=$ID')");
        }
        $downloaded_status=$ligne["downloaded_status"];
        if($downloaded_status<>null){
            $downloaded_status="<br><small><i class='fa-solid fa-file-circle-info'></i>&nbsp;$downloaded_status</small>";
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='vertical-align:middle;' width=99% nowrap>$enforceuri$notfound_ico$downloaded_status</td>";
        $html[]="<td style='vertical-align:middle;' class='center' width=1% nowrap>$view</td>";
        $html[]="<td style='vertical-align:middle;' class='center' width=1% nowrap>$size</td>";
        $html[]="<td style='vertical-align:middle;' class='left' width=1% nowrap>$time_text</td>";
        $html[]="<td style='vertical-align:middle;' class='center' width=1% nowrap>$sync</td>";
        $html[]="<td style='vertical-align:middle;' class='center' width=1% nowrap>$enabled</td>";
        $html[]="<td style='vertical-align:middle;' class='center' width=1% nowrap>$icon_erase</td>";
        $html[]="<td style='vertical-align:middle;' class='center' width=1% nowrap>$delete</td>";
        $html[]="</tr>";
    }





    $TINY_ARRAY["TITLE"]="WebCopy";
    $TINY_ARRAY["ICO"]="fad fa-copy";
    $TINY_ARRAY["EXPL"]="{webcopy_explain}";
    $TINY_ARRAY["URL"]="webcopy-status";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="";
    $html[]="<script>";
    $html[]="$jstiny;\nNoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
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

    $q      = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne  = $q->mysqli_fetch_array("SELECT count(*) as tcount FROM httrack_sites WHERE enabled=1");
    $tcount = intval($ligne["tcount"]);

    $ligne  = $q->mysqli_fetch_array("SELECT SUM(size) as tsize FROM httrack_sites");
    $tsize = intval($ligne["tsize"]);

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