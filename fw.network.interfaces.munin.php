<?php
	include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');

	if(isset($_GET["stats"])){stats();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$interface=$_GET["interface"];
	$MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
	$EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
	if($MUNIN_CLIENT_INSTALLED==1){
		if($EnableMunin==1){
			$tpl->js_dialog1("{interface} $interface", "$page?stats=$interface");
			return;
		}
	}
	
	
}

function stats(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$prefix="if_{$_GET["stats"]}";

	
	$f[]="$prefix-day.png";
	$f[]="$prefix-week.png";	
	$f[]="$prefix-month.png";
	$f[]="$prefix-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}else{
			$tt[]="$path/$image no such file";
		}
		
		
	}
	
	if(!$OUTPUT){
		$tpl=new template_admin();
		echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}<br>".@implode("<br>", $tt)."</div>");
	}
	
	
}






