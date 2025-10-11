<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsSystemAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["insidetab"])){insidetab();exit;}
page();


function insidetab(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["MYSQLD_SEARCH"])){$_SESSION["MYSQLD_SEARCH"]="today this hour 50 events";}

    //$topbuttons[] = array($add, ico_plus, "{new_member}");
    //$topbuttons[] = array($add, ico_plus, "{new_member}");
    $APP_MYSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_MYSQL} v$APP_MYSQL_VERSION {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_MYSQL_ABOUT}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons(array());
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<div class=\"input-group\" style='margin-top:10px'>
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["MYSQLD_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>";
	$html[]="<div id='table-loader'></div>";
	
	$html[]="<script>
	$.address.state('/');
	$.address.value('/mysql-events');
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
        $jstiny;
		Start$t();
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["MYSQLD_SEARCH"])){$_SESSION["MYSQLD_SEARCH"]="today this hour 50 events";}

    //$topbuttons[] = array($add, ico_plus, "{new_member}");
    //$topbuttons[] = array($add, ico_plus, "{new_member}");
    $APP_MYSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_MYSQL} v$APP_MYSQL_VERSION {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_MYSQL_ABOUT}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons(array());
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>

    </div>
</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/mysql-events');
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
        $jstiny;
		Start$t();
	</script>";

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_MYSQL}",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$MAIN=$tpl->format_search_protocol($_GET["search"],false,true);
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("mysql.php?syslog=$line");
	$filename=PROGRESS_DIR."/mysql.syslog";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
    		<th>{status}</th>
        	<th>$date_text</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["MYSQLD_SEARCH"]=$_GET["search"];}
	rsort($data);
	
	$ins["note"]="<span class='label label-info'>Note</span>";
	$ins["warning"]="<span class='label label-warning'>{warning}</span>";
    $ins["ERROR"]="<span class='label label-danger'>{error}</span>";
	
	foreach ($data as $line){
		$line=trim($line);
		if(!preg_match("/^(.*?)\s+[0-9]+\s+\[(.+?)\]\s+(.+?)$/", $line,$re)){
			echo "<strong style='color:red'>$line</strong><br>"; 
			continue;
        }
		$date="{$re[1]}";
		$xtime=strtotime($date);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));
		$line=$re[3];

		
		

		
		$html[]="<tr>
				<td width=1% nowrap>{$ins[strtolower($re[2])]}</td>
				<td width=1% nowrap>$FTime</td>
				<td>$line</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/mysql.syslog.pattern")."</i></div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}
