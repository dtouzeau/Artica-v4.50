<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["redis-top-status"])){redis_top_status();exit;}
if(isset($_POST["RedisBindInterface"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search"])){search();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $APP_REDIS_SERVER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_VERSION");


    $html=$tpl->page_header("{APP_REDIS_SERVER} v{$APP_REDIS_SERVER_VERSION}",
        "fas fa-database"
        ,"{APP_REDIS_SERVER_TEXT}"
        ,"$page?tabs=yes"
        ,"redis-database",
        "progress-redis-restart",
        false,
        "table-loader-redis-service"
    );
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_OPENLDAP}",$html);
		echo $tpl->build_firewall();
		return;
	}
	

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{events}"]="$page?events=yes";
    echo $tpl->tabs_default($array);

}

function redis_top_status(){
    $tpl=new template_admin();
    $RedisPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisPassword"));
        if(!class_exists("Redis")){
            echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger style='margin-top:20px'>{REDIS_PHP_EXTENSION_NOT_LOADED}</div>");
        }else{

            $Connect=true;
            $redis = new Redis();
            try{
                $redis->connect('/var/run/redis/redis.sock');
                if($RedisPassword<>null){
                    $redis->auth($RedisPassword);
                }
                $data=$redis->info();
            } catch (Exception $e) {
                $Connect=false;
                echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:20px;font-weight: bold'>Error{$e->getMessage()}</div>");
            }

        }
    if($Connect){
        $style="vertical-align: top;padding-left: 10px;width:50%";
        $uptime_in_seconds=$data["uptime_in_seconds"];
        $timeStart=time()-$uptime_in_seconds;
        $html[]="<table style='width:100%'>";
        $html[]="<tr>";
        $html[]="<td style='$style'>";
        $html[]=$tpl->widget_h("green","fa-database",$data["used_memory_human"],"{memory_database}");
        $html[]="</td>";
        $html[]="<td style='$style'>";
        $html[]=$tpl->widget_h("green","far fa-ethernet",$tpl->FormatNumber($data["total_connections_received"]),"{connections}");
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</table>";

    }
    echo $tpl->_ENGINE_parse_body($html);
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$IPClass=new IP();
	$sock=new sockets();
	$ldap=new clladp();
	
	$html[]="<table style='width:100%;margin-top:20px'>";
	$html[]="<tr>";
	$html[]="<td style='width:450px;vertical-align:top'>";
	$html[]="<div id='redis-status'></div>";
	$html[]="</td>";
	$html[]="<td style='width:100%;vertical-align:top;padding-left:20px'>";
    $html[]="<div id='redis-top-status'></div>";


	$security="AsSystemAdministrator";

    $RedisBindInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisBindInterface");
    if($RedisBindInterface==null){$RedisBindInterface="lo";}
    $RedisBindPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisBindPort"));
    $RedisMaxDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisMaxDB"));
    $RedisMaxmemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisMaxmemory"));
    $RedisPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisPassword"));
    if($RedisMaxDB==0){$RedisMaxDB=16;}
    if($RedisBindPort==0){$RedisBindPort=6379;}
    if($RedisBindInterface==null){$RedisBindInterface="lo";}
    if($RedisMaxmemory==0){$RedisMaxmemory=500;}

	$form[]=$tpl->field_interfaces("RedisBindInterface", "{listen_interface}", $RedisBindInterface);
    $form[]=$tpl->field_numeric("RedisBindPort","{listen_port}",$RedisBindPort);
    $form[]=$tpl->field_numeric("RedisMaxDB","{max_databases}",$RedisMaxDB);
    $form[]=$tpl->field_numeric("RedisMaxmemory","{max_records_in_memory} (MB)",$RedisMaxmemory);
    $form[]=$tpl->field_password("RedisPassword","{password}",$RedisPassword);


    $jsrestart=$tpl->framework_buildjs(
        "/redis/restart","redis.restart.progress","redis.restart.progress.logs",
        "progress-redis-restart","LoadAjaxTiny('redis-status','$page?status=yes');",
        "LoadAjaxTiny('redis-status','$page?status=yes');"

    );

	

	$html[]=$tpl->form_outside("{general_settings}", @implode("\n", $form),null,"{apply}",$jsrestart,$security);
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="<script>LoadAjaxTiny('redis-status','$page?status=yes');</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();	
	$tpl->SAVE_POSTs();
	
}

function status(){
	$sock=new sockets();
	$tpl=new template_admin();
    $APP_REDIS_SERVER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_VERSION");
	$page=CurrentPageName();
	$json=json_decode($sock->REST_API("/redis/status"));
	$ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $jsrestart=$tpl->framework_buildjs(
        "/redis/restart","redis.restart.progress","redis.restart.progress.logs",
        "progress-redis-restart","LoadAjaxTiny('redis-status','$page?status=yes');",
        "LoadAjaxTiny('redis-status','$page?status=yes');"

    );
	
	echo $tpl->SERVICE_STATUS($ini, "APP_REDIS_SERVER",$jsrestart);
	
	$EnableOpenLDAPRestFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAPRestFul"));
	
	$html[]="<div class=\"col-lg-13\">";
	
	if($EnableOpenLDAPRestFul==0){
		$html[]=$tpl->widget_style1("gray-bg","fa fa-thumbs-down","RESTful API","{disabled}");
	}else{
		$html[]=$tpl->widget_style1("navy-bg","fa fa-thumbs-up","RESTful API","{active2}");
	}
	
	$html[]="</div>";


    $TINY_ARRAY["TITLE"]="{APP_REDIS_SERVER} v{$APP_REDIS_SERVER_VERSION}";
    $TINY_ARRAY["ICO"]="fas fa-database";
    $TINY_ARRAY["EXPL"]="{APP_REDIS_SERVER_TEXT}";
    $TINY_ARRAY["URL"]="redis-database";
    $TINY_ARRAY["BUTTONS"]=null;

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="<script>$jstiny;LoadAjaxTiny('redis-top-status','$page?redis-top-status=yes');</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function events(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $TINY_ARRAY["TITLE"]="{APP_REDIS_SERVER} {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_REDIS_SERVER_TEXT}";
    $TINY_ARRAY["URL"]="redis-events";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<div style='margin-top:10px'></div>";
    echo $tpl->search_block($page);
    echo "<script>$jstiny</script>";
}
function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $targetfile=PROGRESS_DIR."/redis-server.log";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("serviceredis.php?search-in-syslog=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"]));

    $html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>PID</th>
			<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";


    $data=explode("\n",@file_get_contents($targetfile));
    krsort($data);
    $pattern="^([0-9]+):([A-Z]+)\s+(.+?)\.[0-9]+\s+(.*?)\s+(.+)";
    foreach ($data as $line) {
        if (!preg_match("#$pattern#", $line, $re)) {
            continue;
        }
        $md = md5($line);
        $color="black";
        $pid=$re[1];
        $date=$re[3];
        $event=trim($re[5]);
        $html[]="<tr id='$md'>
				<td style='color:$color;width:1%' nowrap>$date</td>
				<td style='color:$color;width:1%' nowrap>$pid</td>
				<td style='color:$color;width:99%'>$event</span></td>
                </tr>";

    }

    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body($html);

}

