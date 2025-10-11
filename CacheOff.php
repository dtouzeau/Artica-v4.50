<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
session_start();
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');

$GLOBALS["langs"]=array("fr","en","po","es","it");




if(function_exists("apc_clear_cache")){
	
	$apc_cache_info=apc_cache_info();
	$date=date('M d D H:i:s',$apc_cache_info["start_time"]);
	$cache_mb=FormatBytes(($apc_cache_info["mem_size"]/1024));
	$files=count($apc_cache_info["cache_list"]);	
	$text="{cached_files_number}:$files\n";
	$text=$text."{start_time}:$date\n";
	$text=$text."{mem_size}:$cache_mb\n";
	
	apc_clear_cache("user");
	apc_clear_cache();
}
		if(!isset($_SESSION["detected_lang"])){$_SESSION["detected_lang"]=$_COOKIE["artica-language"];}			
		$sock=new sockets();
		$sock->getFrameWork("squid.php?clean-catz-cache=yes");
	echo "\n";
	$cc=0;
	foreach ($_SESSION as $num=>$val){
		if(preg_match("#\/.+?\.php$#", $num)){$cc++;unset($_SESSION[$num]);}
		
	}
	
	foreach (glob(PROGRESS_DIR."/*.cache") as $filename) {
		@unlink($filename);
		
	}
	$sock->DeleteCache();	
	$sock->getFrameWork("system.php?process1=yes");
	

	
	
	foreach ($GLOBALS["langs"] as $num=>$val){
		$datas=$sock->LANGUAGE_DUMP($val);
		$bb=strlen(serialize($datas));
		$a=$a+$bb;
		$bb=str_replace("&nbsp;"," ",FormatBytes($bb/1024));
		$tt[]="\tDumping language $val $bb";
	}	
	
	
			$dataSess=strlen(serialize($_SESSION));

			
			$text=$text."Session Cache.....................: ".str_replace("&nbsp;"," ",FormatBytes($dataSess/1024))."\n";
			$text=$text."Session Page Cache................: $cc page(s)\n";
			
			
			$bytes=$a;
			$text=$text."Language Cache....................: ".str_replace("&nbsp;"," ",FormatBytes($bytes/1024))."/". str_replace("&nbsp;"," ",FormatBytes($sock->semaphore_memory/1024))."\n";
			$text=$text.implode("\n",$tt)."\n";
			$text=$text."Console Cache.....................: ".str_replace("&nbsp;"," ",FormatBytes(REMOVE_CACHED()))."\n";
			
			
			
			
			$text=$text."\n\n{cache_cleaned}\n";
			$text=$text."language : {$_SESSION["detected_lang"]}\n";
			$text=$text."icons cache : ".count($_SESSION["ICON_MYSQL_CACHE"])."\n";

			
		
			writelogs("Clean cache, language was {$_SESSION["detected_lang"]}",__FUNCTION__,__FILE__,__LINE__);	
			unset($_SESSION["CACHE_PAGE"]);			
			unset($_SESSION["APC"]);
			unset($_SESSION["cached-pages"]);
			unset($_SESSION["translation-en"]);
			unset($_SESSION["translation"]);
			unset($_SESSION["privileges"]);
			unset($_SESSION["qaliases"]);
			unset($_SERVER['PHP_AUTH_USER']);
			unset($_SESSION["ARTICA_HEAD_TEMPLATE"]);
			unset($_SESSION['smartsieve']['authz']);
			unset($_SESSION["passwd"]);
			unset($_SESSION["LANG_FILES"]);
			unset($_SESSION["TRANSLATE"]);
			unset($_SESSION["__CLASS-USER-MENUS"]);
			unset($_SESSION["translation"]);
			unset($_SESSION["ICON_MYSQL_CACHE"]);
			unset($_SESSION["SETTINGS_FILES"]);
			unset($_SESSION["FONT_CSS"]);
			
			
			unset($_SESSION["translation_en"]);
			unset($_SESSION["translation_fr"]);
			unset($_SESSION["translation_br"]);
			unset($_SESSION["translation_it"]);
			unset($_SESSION["translation_po"]);
			unset($_SESSION["translation_pol"]);
			unset($_SESSION["translation_es"]);
			unset($_SESSION["translation_de"]);
			
			unset($_SESSION[md5("statusPostfix_satus")]);
			unset($_SESSION["EnableWebPageDebugging"]);
			unset($_SESSION["quicklinks_proxy_action"]);
			unset($_SESSION["webfilters_sqgroups_iptables"]);
			@unlink("ressources/logs/postfix.status.html");
            if(is_file("/etc/artica-postfix/CPU_NUMBER")) {
                @unlink("/etc/artica-postfix/CPU_NUMBER");
            }

			$workdir="/usr/share/artica-postfix/ressources/logs/web";
			
			
			
			
			$ToDelete["admin.index.tabs.html"]=true;
			$ToDelete["admin.index.memory.html"]=true;
			$ToDelete["admin.index.notify.html"]=true;
			$ToDelete["admin.index.quicklinks.html"]=true;
			$ToDelete["admin.index.status.html"]=true;
			$ToDelete["admin.index.status-infos.php.left_menus_services"]=true;
			$ToDelete["admin.index.status-infos.php.page"]=true;
			$ToDelete["admin.index.status-infos.php.left_menus_actions"]=true;
			if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
			@unlink(PROGRESS_DIR."/ufdb.rules_toolbox_left.html");
			
			$ToDelete["logon.html"]=true;
			$ToDelete["traffic.statistics.html"]=true;
			foreach ($ToDelete as $filename=>$val){@unlink("$workdir/$filename");}
			
			$BaseWorkDir=$workdir;
			
			
			
			$handle = opendir($BaseWorkDir);
			if($handle){
				while (false !== ($filename = readdir($handle))) {
					if($filename=="."){continue;}
					if($filename==".."){continue;}
					$targetFile="$BaseWorkDir/$filename";
					if(is_dir($targetFile)){continue;}
					if(preg_match("#\.html$#", $filename)){@unlink($targetFile);}
				}
		    }
			
			
			$BaseWorkDir=PROGRESS_DIR."/cache";
				
			$handle = opendir($BaseWorkDir);if($handle){
			
				while (false !== ($filename = readdir($handle))) {
					if($filename=="."){continue;}
					if($filename==".."){continue;}
					$targetFile="$BaseWorkDir/$filename";
					if(is_dir($targetFile)){continue;}
					@unlink($targetFile);
				}
			
			
			}
			
			
			$BaseWorkDir=PROGRESS_DIR."/help";
			$handle = opendir($BaseWorkDir);if($handle){
					
				while (false !== ($filename = readdir($handle))) {
					if($filename=="."){continue;}
					if($filename==".."){continue;}
					$targetFile="$BaseWorkDir/$filename";
					if(is_dir($targetFile)){continue;}
					@unlink($targetFile);
				}
					
					
			}

			
			$BaseWorkDir="/usr/share/artica-postfix/ressources/interface-cache";
			$handle = opendir($BaseWorkDir);if($handle){
					
				while (false !== ($filename = readdir($handle))) {
					if($filename=="."){continue;}
					if($filename==".."){continue;}
					$targetFile="$BaseWorkDir/$filename";
					if(is_dir($targetFile)){continue;}
					@unlink($targetFile);
				}
					
					
			}
			

			include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
			$q=new mysql_squid_builder();
			$q->QUERY_SQL("DROP TABLE webfilters_categories_caches");
			$q->create_webfilters_categories_caches();
			$q=new mysql();
			$q->QUERY_SQL("UPDATE setup_center SET CODE_NAME_STRING='',CODE_NAME_ABOUT=''",'artica_backup');


			$tpl=new templates();
			$html=$tpl->javascript_parse_text($text,1);
			$html=str_replace("\n", "<br>", $html);
			echo "<div class=explain style='font-size:14px'>".$html."</div>";
			$sock=new sockets();
			$sock->getFrameWork("services.php?cache-pages=yes");
			
		

?>