<?php
	include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["DisableSWAPP"])){save();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["stats"])){stats();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{load}:{statistics}", "$page?stats=yes");
	
}

function stats(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="load-day.png";
	$f[]="load-week.png";	
	$f[]="load-month.png";
	$f[]="load-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
		
		if(!$OUTPUT){
			$tpl=new template_admin();
			echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
		}	
	}
	
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();

	$array["{statistics}"]="$page?stats=yes";
	$array["{memory}:{parameters}"]="$page?popup=yes";
	echo $tpl->tabs_default($array);

}


function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	//$sock->getFrameWork("system.php?ps-mem=yes");
	$SwapOffOn=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SwapOffOn"));
	$DisableSWAPP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSWAPP"));
	if(!is_numeric($SwapOffOn["SwapEnabled"])){$SwapOffOn["SwapEnabled"]=1;}
	if(!is_numeric($SwapOffOn["SwapMaxPourc"])){$SwapOffOn["SwapMaxPourc"]=20;}
	if(!is_numeric($SwapOffOn["SwapMaxMB"])){$SwapOffOn["SwapMaxMB"]=0;}
	if(!is_numeric($DisableSWAPP)){$DisableSWAPP=0;}
	if(!is_numeric($SwapOffOn["SwapTimeOut"])){$SwapOffOn["SwapTimeOut"]=60;}
	
	if(!is_numeric($SwapOffOn["AutoMemWatchdog"])){$SwapOffOn["AutoMemWatchdog"]=1;}
	if(!is_numeric($SwapOffOn["AutoMemPerc"])){$SwapOffOn["AutoMemPerc"]=90;}
	if(!is_numeric($SwapOffOn["AutoMemInterval"])){$SwapOffOn["AutoMemInterval"]=180;}
    $swappiness=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("vm.swappiness"));

	for($i=1;$i<100;$i++){
		$val=100-$i;
		$arraySWP[$val]="{$i}%";
	
	}
	
	for($i=1;$i<100;$i++){
		$array2[$val]="{$i}%";
	}
	
	$explain="{swap_usage_explain}";
	if($DisableSWAPP==1){$explain="{swap_is_disabled}";}
	$form[]=$tpl->field_section("{swap_usage}",$explain);
	$form[]=$tpl->field_checkbox("DisableSWAPP","{DisableSWAPP}",$DisableSWAPP,"swappiness,SwapEnabled");
	
	if($DisableSWAPP==0){
	$form[]=$tpl->field_array_hash($arraySWP, "swappiness", "{use_swap_when_memory_exceed}", $swappiness);
	
	$form[]=$tpl->field_section("{automatic_swap_cleaning}","{automatic_swap_cleaning_explain}");
	
	
	
	
	$form[]=$tpl->field_checkbox("SwapEnabled","{enable}",$SwapOffOn["SwapEnabled"],"SwapTimeOut,SwapMaxMB");
	$form[]=$tpl->field_numeric("SwapTimeOut","{xtimeout}",$SwapOffOn["SwapTimeOut"]);
	$form[]=$tpl->field_array_hash($array2, "SwapMaxPourc", "{MaxDiskUsage}", $SwapOffOn["SwapMaxPourc"]);
	$form[]=$tpl->field_numeric("SwapMaxMB","{maxsize}",$SwapOffOn["SwapMaxMB"]);

	$form[]=$tpl->field_section("{automatic_memory_cleaning}","{automatic_memory_cleaning_explain}");
	$form[]=$tpl->field_checkbox("AutoMemWatchdog","{enable}",$SwapOffOn["AutoMemWatchdog"]);
	$form[]=$tpl->field_array_hash($array2, "AutoMemPerc", "{max_usage}", $SwapOffOn["AutoMemPerc"]);
	$form[]=$tpl->field_numeric("AutoMemInterval","{interval}",$SwapOffOn["AutoMemInterval"]);
	
	
		$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.memory.emptyswap";
		$ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.memory.emptyswap.php.log";
		$ARRAY["CMD"]="system.php?empty-swap=yes";
		$ARRAY["TITLE"]="{empty_swap}";
		$ARRAY["AFTER"]="LoadAjaxSilent('MainContent','fw.index.php?content=yes');";
		$prgress=base64_encode(serialize($ARRAY));
		$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-mem-restart')";
		$tpl->form_add_button("{empty_swap}", $jsrestart);
	
	}
	echo "<div class='row'><div id='progress-mem-restart' class='white-bg'></div>";
	echo $tpl->form_outside("{memory}", @implode("\n", $form),null,"{apply}","LoadAjaxSilent('MainContent','fw.index.php?content=yes');","AsSystemAdministrator");


}

function save(){
	$sock=new sockets();
	$ARRAY=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kernel_values"));
	$SwapOffOn=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SwapOffOn"));
	$sock->SET_INFO("DisableSWAPP", $_POST["DisableSWAPP"]);
	
	
	if(isset($_POST["swapiness"])){
        $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET("vm.swappiness",$_POST["swapiness"]);
        $ARRAY["swappiness"]=$_POST["swapiness"];
	}

    foreach ($_POST as $num=>$line){
		$SwapOffOn[$num]=$line;
	}
	
	if(!is_numeric($SwapOffOn["SwapEnabled"])){$SwapOffOn["SwapEnabled"]=1;}
	if(!is_numeric($SwapOffOn["SwapMaxPourc"])){$SwapOffOn["SwapMaxPourc"]=20;}
	if(!is_numeric($SwapOffOn["SwapMaxMB"])){$SwapOffOn["SwapMaxMB"]=0;}
	if(!is_numeric($SwapOffOn["SwapTimeOut"])){$SwapOffOn["SwapTimeOut"]=60;}
	
	if(!is_numeric($SwapOffOn["AutoMemWatchdog"])){$SwapOffOn["AutoMemWatchdog"]=1;}
	if(!is_numeric($SwapOffOn["AutoMemPerc"])){$SwapOffOn["AutoMemPerc"]=90;}
	if(!is_numeric($SwapOffOn["AutoMemInterval"])){$SwapOffOn["AutoMemInterval"]=180;}
	
	$sock->SaveConfigFile(base64_encode(serialize($SwapOffOn)),"SwapOffOn");
	$sock->SaveConfigFile(base64_encode(serialize($ARRAY)),"kernel_values");
	
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	$sock->getFrameWork("system.php?swap-init=yes");
}