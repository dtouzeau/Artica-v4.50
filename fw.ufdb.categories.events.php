<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.ipinfo.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsDnsAdministrator){exit();}
if(isset($_GET["search"])){search();exit;}
form();

function form(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:15px'>";
    echo $tpl->search_block($page,null);
    echo "</div>";
}


function search(){
    $tpl        = new template_admin();
    $MAIN       = $tpl->format_search_protocol($_GET["search"]);
    $line       = base64_encode(serialize($MAIN));
    $tfile      = PROGRESS_DIR."/webfiltering-categories.log";
    $pat        = PROGRESS_DIR."/webfiltering-categories.pattern";

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("categories.php?search-in-logs=$line");
    $data=explode("\n",@file_get_contents($tfile));
    krsort($data);
    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th style='width:1%' nowrap>{date}</th>
        	<th style='width:1%' nowrap>PID</th>
        	<th style='width:1%' nowrap>{method}</th>
        	<th style='width:1%' nowrap>{events}</th>
        </tr>
  	</thead>
	<tbody>
";
    $COLORMETH["ERROR"]="label-danger";
    $COLORMETH["MODIFY"]="label-danger";
    $COLORMETH["INFO"]="label-info";
    $COLORMETH["CREATE"]="label-info";
    $COLORMETH["DEFAULT"]="label-default";
    $COLORMETH["COMPILE"]="label-info";
    $COLORMETH["CHANGE"]="label-warning";
    $COLORMETH["WARN"]="label-warning";
    $COLORMETH["REMOVE"]="label-info";
    $COLORMETH["SUCCESS"]="label-primary";
    $COLORMETH["RPZ"]="label-primary";
    $COLORMETH["STATS"]="label-success";

    $COLORMETH[null]=null;

    foreach ($data as $line){

        if(!preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)#",$line,$re)){
            echo "<span style='color:red'>$line</span><br>";
            continue;
        }

        $class=null;
        $Month=$re[1];
        $Day=$re[2];
        $time=$re[3];
        $pid=$re[4];
        $event=$re[5];
        $function=null;
        $MOD="DEFAULT";



        if(preg_match("#^(.+?)\(\)\{([0-9]+)\}\s+\[(.+?)\]:(.+)#",$event,$ri)){
            $function=$ri[1];
            $MOD=$ri[3];
            $line=$ri[2];
            $event=trim($ri[4]);
            $event="$event <small>($function/$line)</small>";
        }

        if(preg_match("#\[SUCCESS\]:\s+(.+)#",$event,$ri)){
            $MOD="SUCCESS";
            $event=$ri[1];
        }
        if(preg_match("#\[INFO\]:\s+(.+)#",$event,$ri)){
            $MOD="INFO";
            $event=$ri[1];
        }

        if(preg_match("#\[WARNING]:\s+(.+)#",$event,$ri)){
            $MOD="WARN";
            $event=$ri[1];
        }

        if(preg_match("#\[COMPILE-CATEGORIES\]:\s+(.+)#",$event,$ri)){
            $MOD="COMPILE";
            $event=$ri[1];
        }
        if(preg_match("#\[COMPILE-CATEGORY\]:\s+(.+)#",$event,$ri)){
            $MOD="COMPILE";
            $event=$ri[1];
        }


        if(preg_match("#\[ERROR\]:\s+(.+)#",$event,$ri)){
            $MOD="ERROR";
            $event=$ri[1];
        }
        if(preg_match("#\[INFO\]\s+(.+)#",$event,$ri)){
            $MOD="INFO";
            $event=$ri[1];
        }
        if(preg_match("#\[INFO\]:\s+(.+)#",$event,$ri)){
            $MOD="INFO";
            $event=$ri[1];
        }
        if(preg_match("#\[RPZWEB\]:\s+(.+)#",$event,$ri)){
            $MOD="RPZ";
            $event=$ri[1];
        }
        if(preg_match("#\[STATS\]:\s+(.+)#",$event,$ri)){
            $MOD="STATS";
            $event=$ri[1];
        }
        $event=str_replace("httpclient.go","",$event);
        $event=str_replace("categoriesserv/","",$event);
        $event=str_replace("RemoteCategoriesService.go","",$event);
        $event=str_replace("Compile.go","",$event);
        $event=str_replace("UFDBRepository.go","",$event);
        $event=str_replace("categories/","",$event);

        if(strpos("  $event","[COMPILE]:")>0){
            $event=str_replace("[COMPILE]:","",$event);
            $event=$event."&nbsp;<span class='label label-warning'>{compile}</span>";
        }
        if(strpos("  $event","[PROXY]:")>0){
            $event=str_replace("[PROXY]:","",$event);
            $event=$event."&nbsp;<span class='label label-success'>{APP_SQUID}</span>";
        }
        if(strpos("  $event","[WWW]:")>0){
            $event=str_replace("[WWW]:","",$event);
            $event=$event."&nbsp;<span class='label label-info'>{repositories}</span>";
        }
        if(strpos("  $event","[DNS]:")>0){
            $event=str_replace("[DNS]:","",$event);
            $event=$event."&nbsp;<span class='label label-primary'>{dns_server}</span>";
        }
        if(strpos("  $event","[PROXY_CLIENT]")>0){
            $event=str_replace("[PROXY_CLIENT]","",$event);
            $event=$event."&nbsp;<span class='label label-info'>{APP_PROXY} {client}</span>";
        }




        $method="<span class='label $COLORMETH[$MOD]'>$MOD</span>";
        if($MOD=="INFO"){
            $method="<span class='label $COLORMETH[$MOD]'>&nbsp;&nbsp;&nbsp;INFO&nbsp;&nbsp;&nbsp;</span>";
        }
        if($MOD=="WARN"){
            $method="<span class='label $COLORMETH[$MOD]'>&nbsp;&nbsp;&nbsp;WARN&nbsp;&nbsp;</span>";
        }
        if($MOD=="ERROR"){
            $method="<span class='label $COLORMETH[$MOD]'>&nbsp;&nbsp;ERROR&nbsp;&nbsp;</span>";
        }
        $html[]="<tr>
				<td style='width:1%;' nowrap class='$class'>$Month $Day $time</td>
				<td style='width:1%;' nowrap class='$class'>$pid</td>
				<td style='width:1%;' nowrap class='$class'>$method</td>
				<td style='width:99%' nowrap class='$class'>$event</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents($pat)."</i></div>";
    $TINY_ARRAY["TITLE"]="{your_categories}: {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{personal_categories_explain}";
    $TINY_ARRAY["URL"]=null;
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
}


