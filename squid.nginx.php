<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.syslogs.inc');
	

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["enable-nginx"])){enable_nginx();exit;}
	if(isset($_GET["help"])){help();exit;}
	if(isset($_POST["EnableNginx"])){EnableNginx();exit;}


js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{squid_reverse_proxy}");
	$html="YahooWin2(689,'$page?tabs=yes','$title')";
	echo $html;
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["enable-nginx"]='{parameters}';
	$array["help"]='{online_help}';
	
	
	$fontsize=16;
	
	$t=time();
	foreach ($array as $num=>$ligne){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_nginx_squid_tabs");
}

function enable_nginx(){
	$sock=new sockets();
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$p=Paragraphe_switch_img("{enable_nginx}","{enable_nginx_text}",
			"EnableNginx",$EnableNginx,null,500);
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td>$p</td>
	</tr>
	<tr>
		<td align='right'><hr>". button("{apply}","Save$t()",18)."</td>
	</tr>		
	</table>		
	</div>
<script>
	var xSave$t=function (obj) {
			var results=obj.responseText;
			RefreshTab('squid_main_svc');
			RefreshTab('main_nginx_squid_tabs');
			ExecuteByClassName('SearchFunction');
		}	
		
		function Save$t(){
			var XHR = new XHRConnection();
    		XHR.appendData('EnableNginx',document.getElementById('EnableNginx').value);
    		document.getElementById('img_EnableNginx').src='img/wait_verybig.gif';
    		XHR.sendAndLoad('$page', 'POST',xSave$t);
			
		}					
</script>				
				
";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
}

function EnableNginx(){
	$sock=new sockets();
	$sock->SET_INFO("EnableNginx",$_POST["EnableNginx"]);
	$sock->getFrameWork("nginx.php?restart=yes&enable={$_POST["EnableNginx"]}");
	$sock->getFrameWork("services.php?restart-phpfpm=yes");
}


function help(){

	$tr[]=Paragraphe("youtube-play-64.png", "Video",
			"Reverse Proxy Playlist","http://www.youtube.com/playlist?list=PL6GqpiBEyv4rh3g8rlwddwu8l65V6FaJe",null,250);

	$tr[]=Paragraphe("youtube-play-64.png", "Video",
			"How to access to the reverse Proxy web interface and basic settings",
			"http://www.youtube.com/watch?v=hx9zGJ8bcxM&list=PL6GqpiBEyv4rh3g8rlwddwu8l65V6FaJe&index=2",null,250);



	echo "<center style='width:80%'>".CompileTr3($tr)."</center>";

}
