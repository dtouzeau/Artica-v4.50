<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.manager.inc');
$GLOBALS["OUTPUT"]=true;
$GLOBALS["TITLENAME"]="Tiny Proxy Server";


if($argv[1]=="--reload"){reload();exit();}
if($argv[1]=="--rotate"){rotate();exit();}
if($argv[1]=="--templates"){templates();exit;}
if($argv[1]=="--build"){build();exit;}


function install(){
	$unix=new unix();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("Enable3Proxy",1);
	build_progress(20,"{installing}");
	install_service();
	build_progress(50,"{installing}");
	squid_admin_mysql(0,"Installing Universal Proxy",null,__FILE__,__LINE__);

	UNIX_RESTART_CRON();
	
	build_progress(50,"{starting_service}");
	build();
	start(true);
	build_progress(100,"{done}");
}

function rotate(){
	$unix=new unix();
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	
	$files=$unix->DirFiles("/var/log/3proxy");
	
	$BackupMaxDaysDir="$BackupMaxDaysDir/proxy";
	
	$year=date("Y");
	$month=date("m");
	$day=date("d");
	
	$curfile="log.$year.$month.$day";
	
	foreach ($files as $filename=>$none){
		if($filename==$curfile){
			echo "$filename -> SKIP";
			continue;
		}
		
		$filepath="/var/log/3proxy/$filename";
		if(!preg_match("#^log\.([0-9]+)\.([0-9]+)\.([0-9]+)#", $filename,$re)){continue;}
		
		$TargetDirectory="$BackupMaxDaysDir/{$re[1]}/{$re[2]}/{$re[3]}";
		if(!is_dir($TargetDirectory)){@mkdir($TargetDirectory,0755,true);}
		
		$TargetFileName="$TargetDirectory/UniversalProxy-{$re[1]}-{$re[2]}-{$re[3]}.access.gz";
		if(!preg_match("#\.gz$#", $filename)){
			if(!$unix->compress($filepath, $TargetFileName)){
				squid_admin_mysql(0, "Unable to compress $filename", "wants $filepath to $TargetFileName",__FILE__,__LINE__);
				@unlink($TargetFileName);
				continue;
			}
			echo "$filepath -> $TargetFileName [OK]\n";
			@unlink($filepath);
			continue;
		}
		
		if(!@copy($filepath, $TargetFileName)){
			squid_admin_mysql(0, "Unable to move $filename", "wants $filepath to $TargetFileName",__FILE__,__LINE__);
			@unlink($TargetFileName);
			continue;
		}
		echo "$filepath -> $TargetFileName [OK]\n";
		@unlink($filepath);
	
	}
	squid_admin_mysql(2, "Restarting Universal Proxy after logs rotation",__FILE__,__LINE__);
	system("/etc/init.d/3proxy restart");
	
}

function reload(){
	build_progress(50,"{reconfiguring}");
	build();
	$unix=new unix();
	$pid=PID_NUM();
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		build_progress(90,"{reloading}");
		system("$kill -s USR1 $pid");
		build_progress(100,"{reloading} {success}");
		return;
	}
	
	build_progress(90,"{starting_service}");
	if(!start(true)){
		build_progress(110,"{starting_service} {failed}");
		return;
	}
	build_progress(100,"{starting_service} {success}");
	
}

function restart(){
	build_progress(20,"{stopping_service}");
	if(!stop(true)){
		build_progress(110,"{stopping_service} {failed}");
		return;
	}
	
	build_progress(50,"{reconfiguring}");
	build();
	build_progress(80,"{starting_service}");
	if(!start(true)){
		build_progress(110,"{starting_service} {failed}");
		return;
	}
	build_progress(100,"{restarting_service} {success}");
}

function uninstall(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("Enable3Proxy",0);
	build_progress(20,"{uninstalling}");
	@unlink("/etc/monit/conf.d/APP_3PROXY.monitrc");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	
	@unlink("/etc/cron.d/3proxy-schedule");
	UNIX_RESTART_CRON();
	
	build_progress(30,"{uninstalling}");
	remove_service("/etc/init.d/3proxy");
	build_progress(100,"{uninstalling} {done}");
	
}


function build_progress($pourc,$text){
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/3proxy.progress";
	echo "$date: [{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/3proxy/3proxy.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("3proxy");
	return $unix->PIDOF($Masterbin);
}








function build(){
    if($GLOBALS["VERBOSE"]){
        echo __FUNCTION__.".".__LINE__."\n";
    }
	$unix=new unix();
    $unix->Popuplate_cron_make("3proxy-schedule","1 0 * * *",basename(__FILE__)." --rotate");
    if($GLOBALS["VERBOSE"]){
        echo __FUNCTION__.".".__LINE__."\n";
    }
    $TRANSPARENT_REQUIRED=TRANSPARENT_REQUIRED();
	$SOCKSIFY_REQUIRED=SOCKSIFY_REQUIRED();
    if($GLOBALS["VERBOSE"]){
        echo __FUNCTION__.".".__LINE__."\n";
    }
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $PowerDNSEnableRecursor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableRecursor"));
	
    if($GLOBALS["VERBOSE"]){
        echo __FUNCTION__.".".__LINE__."\n";
    }
	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$sql="SELECT * FROM `3proxy_services` WHERE enabled=1 ORDER BY zorder";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "*** FATAL $q->mysql_error\n";}
	$ipClass=new IP();
	
	$sZtypes[100]="proxy";
	$sZtypes[1]="ftppr";
	$sZtypes[2]="socks";
	$sZtypes[3]="smtpp";
	$sZtypes[4]="tcppm";
	$sZtypes[5]="udppm";
	$sZtypes[6]="dnspr";
	$sZtypes[7]="pop3p";
	
	$GLOBALS["ZTYPES"][100]="HTTP/HTTPS";
	$GLOBALS["ZTYPES"][1]="FTP";
	$GLOBALS["ZTYPES"][2]="SOCKS 4/5";
	$GLOBALS["ZTYPES"][3]="SMTP";
	$GLOBALS["ZTYPES"][4]="TCP";
	$GLOBALS["ZTYPES"][5]="UDP";
	$GLOBALS["ZTYPES"][6]="DNS";
	$GLOBALS["ZTYPES"][7]="pop3";
    if($GLOBALS["VERBOSE"]){
        echo __FUNCTION__.".".__LINE__."\n";
    }
	
	foreach ($results as $index=>$ligne){
		$cmd=array();
		$ID=$ligne["ID"];
		$servicename=$ligne["servicename"];
		$listen_port=intval($ligne["listen_port"]);
		$outgoing_interface=$ligne["outgoing_interface"];
		$listen_interface=$ligne["listen_interface"];
		$transparent=intval($ligne["transparent"]);
		$redsocks=intval($ligne["redsocks"]);
		$maxconn=intval($ligne["maxconn"]);
		$type=$ligne["service_type"];
		if($maxconn==0){$maxconn=100;}
		if($redsocks==1){$transparent=0;}
		
		$options=unserialize(base64_decode($ligne["options"]));
		if($type==6){
			if($UnboundEnabled==1){
				$conf[]="# Type 6 ($servicename) DNS Cache service conflict";
				continue;
			}
			
			if($PowerDNSEnableRecursor==1){
				$conf[]="# Type 6 ($servicename) PowerDNS Recursor conflict";
				continue;				
			}
			if(intval($options["DNS_CACHE"])<5){$options["DNS_CACHE"]=65535;}
			$conf[]="nscache {$options["DNS_CACHE"]}";
			if($options["DNS1"]<>null){$conf[]="nserver {$options["DNS1"]}";}
			if($options["DNS2"]<>null){$conf[]="nserver {$options["DNS2"]}";}
			if($options["DNS3"]<>null){$conf[]="nserver {$options["DNS3"]}";}
			if($options["DNS4"]<>null){$conf[]="nserver {$options["DNS4"]}";}
			
			
		}
        if($GLOBALS["VERBOSE"]){
            echo __FUNCTION__.".".__LINE__."\n";
        }
		
		
		$listen_ipaddr=$unix->InterfaceToIPv4($listen_interface);
		//$outgoing_ipaddr=$unix->InterfaceToIPv4($outgoing_ipaddr);
		$cmd[]=$sZtypes[$type];
		
		
		if($listen_ipaddr<>null){$cmd[]="-i$listen_ipaddr";}
		//if($outgoing_ipaddr<>null){$cmd[]="-e$outgoing_ipaddr";}
		
		if( ($type==100) OR ($type==1) OR ($type==2) OR ($type==3) OR ($type==6) ){
			if($listen_port>0){$cmd[]="-p{$listen_port}";}		
		}
		
		if(isset($options["default_destination"])){
			if($options["default_destination"]<>null){
				$cmd[]="-h{$options["default_destination"]}";
			}
		}
		
		if($type==4){
			$dst_addr=$options["dst_addr"];
			$dst_port=$options["dst_port"];
			if($dst_addr==null){
				$conf[]="# Type 4 ($servicename) need a destination address....";
				continue;
			}
			
			if(!$ipClass->isValid($dst_addr)){
				$conf[]="# Type 4 ($servicename) need a valid address ($dst_addr)....";
				continue;
			}
			$cmd[]="$listen_port $dst_addr $dst_port";
			
		}
		
		if($type==5){
            $moins="";

			$dst_addr=$options["dst_addr"];
			$dst_port=$options["dst_port"];
            $conf[]="# Type 5 --> $dst_port";
			if($dst_addr==null){
				$conf[]="# Type 5 ($servicename) need a destination address....";
				continue;
			}
				
			if(!$ipClass->isValid($dst_addr)){
				$conf[]="# Type 5 ($servicename) need a valid address ($dst_addr)....";
				continue;
			}
            if ($dst_port==53){
                $moins="-s ";
            }

			$cmd[]="$moins$listen_port $dst_addr $dst_port";
				
		}

        if($GLOBALS["VERBOSE"]){
            echo __FUNCTION__.".".__LINE__."\n";
        }
		$conf[]="# ------------------------------------------------------------------------";
		$conf[]="#$servicename {$GLOBALS["ZTYPES"][$type]}($sZtypes[$type] - $type ) From $listen_interface:$listen_port to $outgoing_interface";
		$conf[]="# Transparent mode ?: $transparent, transparent enabled ?:$TRANSPARENT_REQUIRED";
		$conf[]="flush";
        if($GLOBALS["VERBOSE"]){
            echo __FUNCTION__.".".__LINE__."\n";
        }
		$conf[]=BUILD_ACLS($ID);
		$conf[]="maxconn $maxconn";
		$conf[]=@implode(" ", $cmd);
		if($TRANSPARENT_REQUIRED){
			if($transparent==1){$conf[]="transparent";}
			if($transparent==0){$conf[]="notransparent";}
		}
		
		$conf[]="";
	}

    if($GLOBALS["VERBOSE"]){
        echo __FUNCTION__.".".__LINE__."\n";
    }

	$conf[]="";
    if($GLOBALS["VERBOSE"]){
        echo __FUNCTION__.".".__LINE__." -> /etc/3proxy/3proxy-php.cfg\n";
    }

	@file_put_contents("/etc/3proxy/3proxy-php.cfg", @implode("\n", $conf));
	templates();
}

function TRANSPARENT_REQUIRED(){
	if(isset($GLOBALS["TRANSPARENT_REQUIRED"])){return $GLOBALS["TRANSPARENT_REQUIRED"];}
	$FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
	if($FireHolEnable==0){
		$GLOBALS["TRANSPARENT_REQUIRED"]=false;
		return false;
	}
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$sql="SELECT count(*) as tcount FROM `3proxy_services` WHERE enabled=1 AND transparent=1 ORDER BY zorder";
	$ligne=$q->mysqli_fetch_array($sql);
	if(intval($ligne["tcount"])>0){
		$GLOBALS["TRANSPARENT_REQUIRED"]=true;
		return true;
	}
	$GLOBALS["TRANSPARENT_REQUIRED"]=false;
	return false;
	
}

function SOCKSIFY_REQUIRED(){
	if(isset($GLOBALS["SOCKSIFY_REQUIRED"])){return $GLOBALS["SOCKSIFY_REQUIRED"];}
	$FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
	if($FireHolEnable==0){
		$GLOBALS["SOCKSIFY_REQUIRED"]=false;
		return false;
	}
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$sql="SELECT count(*) as tcount FROM `3proxy_services` WHERE enabled=1 AND transparent=1 AND redsocks=1 ORDER BY zorder";
	$ligne=$q->mysqli_fetch_array($sql);
	if(intval($ligne["tcount"])>0){
		$GLOBALS["SOCKSIFY_REQUIRED"]=true;
		return true;
	}
	$GLOBALS["SOCKSIFY_REQUIRED"]=false;
	return false;

}



function BUILD_ACLS($ID){
	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$sql="SELECT * FROM `3proxy_acls_rules` WHERE serviceid='$ID' AND enabled=1 ORDER BY zorder";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";}
	if(count($results)==0){return "auth none";}
	$lmets=array();
	
	$allowdeny[0]="allow";
	$allowdeny[1]="deny";
	$allowdeny[2]="allow";
	$allowdeny[3]="bandlimin";
	$allowdeny[4]="bandlimout";
	$allowdeny[5]="nobandlimin";
	$allowdeny[6]="nobandlimout";
	$allowdeny[7]="connlim";
	$allowdeny[8]="noconnlim";
	$allowdeny[9]="countin";
	$allowdeny[10]="countout";
	$allowdeny[11]="nocountin";
	$allowdeny[12]="nocountout";
	
	$ALL_LINES4=array();
	$ALL_LINES3=array();
	$ALL_LINES2=array();
	$ALL_LINES=array();
	
	$BANDLIMIT[3]=true;
	$BANDLIMIT[4]=true;
	$BANDLIMIT[5]=true;
	$BANDLIMIT[6]=true;
	
	$CONNLIMIT[7]=true;
	$CONNLIMIT[8]=true;
	
	$QUOTAS[9]=true;
	$QUOTAS[10]=true;
	$QUOTAS[11]=true;
	$QUOTAS[12]=true;
	
	foreach ($results as $index=>$ligne){
		
		$zline=array();
		$NOPERIOD=false;
		$ID=intval($ligne["ID"]);
		$ALLOWDENY=intval($ligne["allowdeny"]);
		$bandlimin=intval($ligne["bandlimin"]);
		$bandlimout=intval($ligne["bandlimout"]);
		$connlim=trim($ligne["connlim"]);
		$countout=trim($ligne["countout"]);
		$countin=trim($ligne["countin"]);
		
		if($GLOBALS["VERBOSE"]){$ALL_LINES[]="# Rule ID $ID type {$ligne["allowdeny"]}";}

		
		if($ALLOWDENY==2){
			$prxies=BUILD_ACLS_PROXIES($ID);
			if($prxies==null){continue;}
		}


		
		
		$zline[]=$allowdeny[$ligne["allowdeny"]];
		
		if($ALLOWDENY==3){
			if($bandlimin==0){continue;}
			$zline[]=$bandlimin*1024;
		}
		if($ALLOWDENY==4){
			if($bandlimout==0){continue;}
			$zline[]=$bandlimout*1024;
		}	
		if($ALLOWDENY==7){
			if(!preg_match("#([0-9]+)\/([0-9]+)#", $connlim,$re)){$re[1]=100;$re[2]=60;}
			$zline[]="{$re[1]} {$re[2]}";
		}	

		if($ALLOWDENY==9){
			if(!preg_match("#([0-9]+)\/([0-9]+)#", $countin,$re)){$re[1]=100;$re[2]="H";}
			$zline[]="$ID {$re[2]} {$re[1]} ";
		}
		if($ALLOWDENY==10){
			if(!preg_match("#([0-9]+)\/([0-9]+)#", $countout,$re)){$re[1]=100;$re[2]="H";}
			$zline[]="$ID {$re[2]} {$re[1]} ";
		}				
		
		$sourcelist=BUILD_ACLS_CONVERT($ligne["userlist"]);
		if(count($sourcelist)==0){$zline[]="*";}
		
		if(count($sourcelist)>0){
			$auth[]="useronly";
			$zline[]=@implode(",", $sourcelist);
		}
		
		if($GLOBALS["VERBOSE"]){$ALL_LINES[]="# sourcelist ".strlen($ligne["sourcelist"])." bytes";}
		$sourcelist=BUILD_ACLS_CONVERT($ligne["sourcelist"]);
		if(count($sourcelist)==0){$zline[]="*";}
		if(count($sourcelist)>0){
			$auth[]="iponly";
			$zline[]=@implode(",", $sourcelist);
		}
		
		$sourcelist=BUILD_ACLS_CONVERT($ligne["targetlist"]);
		if(count($sourcelist)==0){$zline[]="*";}
		if(count($sourcelist)>0){$zline[]=@implode(",", $sourcelist);}		
		
		$sourcelist=BUILD_ACLS_CONVERT($ligne["targetportlist"]);
		if(count($sourcelist)==0){$zline[]="*";}
		if(count($sourcelist)>0){$zline[]=@implode(",", $sourcelist);}		
			
		$sourcelist=BUILD_ACLS_CONVERT($ligne["commandlist"]);
		if(count($sourcelist)==0){$zline[]="*";}
		if(count($sourcelist)>0){$zline[]=@implode(",", $sourcelist);}	

		if(!$NOPERIOD){
			$sourcelist=BUILD_ACLS_CONVERT($ligne["weekdaylist"]);
			if(count($sourcelist)==0){$zline[]="*";}
			if(count($sourcelist)>0){$zline[]=@implode(",", $sourcelist);}	
	
			$sourcelist=BUILD_ACLS_CONVERT($ligne["timeperiodlist"]);
			if(count($sourcelist)==0){$zline[]="*";}
			if(count($sourcelist)>0){$zline[]=@implode(",", $sourcelist);}		
		}
		
		if(isset($BANDLIMIT[$ALLOWDENY])){
			$ALL_LINES2[]=@implode("\t", $zline);
			continue;
		}
		
		if(isset($CONNLIMIT[$ALLOWDENY])){
			$ALL_LINES3[]=@implode("\t", $zline);
			continue;
		}
		if(isset($QUOTAS[$ALLOWDENY])){
			$ALL_LINES4[]=@implode("\t", $zline);
			continue;
		}		
		
		$ALL_LINES[]=@implode("\t", $zline);
		if($ALLOWDENY==2){
			$ALL_LINES[]=$prxies;
		}
		
		$ALL_LINES[]="";
		
	}
	
	foreach ($auth as $methods){
		$sZmethods[$methods]=$methods;
	}
	foreach ($sZmethods as $newmethods=>$none){
		$lmets[]=$newmethods;
	}
	
	if(count($lmets)==0){$lmets[]="iponly";}
	
	$conf[]="auth ".@implode(" ", $lmets);
	$conf[]=@implode("\n", $ALL_LINES);
	if(count($ALL_LINES2)>0){$conf[]=@implode("\n", $ALL_LINES2);}
	if(count($ALL_LINES3)>0){$conf[]=@implode("\n", $ALL_LINES3);}
	if(count($ALL_LINES4)>0){$conf[]=@implode("\n", $ALL_LINES4);}
	return @implode("\n", $conf);
}

function BUILD_ACLS_CONVERT($linedata){
	$sourcelist=array();
	$data=explode("\n",base64_decode($linedata));
	foreach ($data as $line){
		$line=trim($line);
		if($line==null){continue;}
		
		if(preg_match("#^\##", $line)){continue;}
		if($GLOBALS["VERBOSE"]){$ALL_LINES[]="# Item $line";}
		$sourcelist[]=$line;
	}

	return $sourcelist;
	
}







function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function templates(){
	$f[]="[--admin--]";
	$f[]="HTTP/1.0 401 Authentication Required\\n";
	$f[]="WWW-Authenticate: Basic realm=\"proxy\", encoding=\"utf-8\"\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]="<html><head><title>401 Authentication Required</title></head>\\n";
	$f[]="<body><h2>401 Authentication Required</h2>";
	$f[]="<h3>Access to requested resource disallowed by administrator or you need valid username/password to use this resource<br><hr>";
	$f[]="Доступ запрещен администратором или Вы ввели неправильное имя/пароль.";
	$f[]="</h3></body></html>\\n";
	$f[]="[end]";
	$f[]="HTTP/1.0 200 OK\\n";
	$f[]="Connection: close\\n";
	$f[]="Expires: Thu, 01 Dec 1994 16:00:00 GMT\\n";
	$f[]="Cache-Control: no-cache\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]="<http><head><title>%s Страница конфигурации</title></head>\\n";
	$f[]="<table width='100%%' border='0'>\\n";
	$f[]="<tr><td width='150' valign='top'>\\n";
	$f[]="<h2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$f[]="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</h2>\\n";
	$f[]="<A HREF='/C'>Счетчики</A><br><br>\\n";
	$f[]="<A HREF='/R'>Перезагрузка конфигурации сервера</A><br><br>\\n";
	$f[]="<A HREF='/S'>Запущенные сервисы</A><br><br>\\n";
	$f[]="<A HREF='/F'>Настройка сервера</A>\\n";
	$f[]="</td><td>";
	$f[]="<h2>%s %s Конфигурация</h2>";
	$f[]="[end]";
	$f[]="HTTP/1.0 200 OK\\n";
	$f[]="Connection: close\\n";
	$f[]="Cache-Control: no-cache\\n";
	$f[]="Content-type: text/xml; charset=utf-8 \\n";
	$f[]="\\n";
	$f[]="<?xml version=\"1.0\"?>\\n";
	$f[]="<?xml-stylesheet href=\"/SX\" type=\"text/css\"?>\\n";
	$f[]="<services>\\n";
	$f[]="<description>Текущие запущенные сервисы и подключившиеся клиенты</description>\\n";
	$f[]="[end]";
	$f[]="</services>\\n";
	$f[]="[end]";
	$f[]="HTTP/1.0 200 OK\\n";
	$f[]="Connection: close\\n";
	$f[]="Cache-Control: no-cache\\n";
	$f[]="Content-type: text/css\\n";
	$f[]="\\n";
	$f[]="services {\\n";
	$f[]="	display: block;\\n";
	$f[]="	margin: 10px auto 10px auto;\\n";
	$f[]="	width: 80%;\\n";
	$f[]="	background: black;\\n\"";
	$f[]="	font-family: sans-serif;\\n";
	$f[]="	font-size: small;\\n";
	$f[]="	color: silver;\\n";
	$f[]="	}\\n";
	$f[]="item {\\n";
	$f[]="	display: block;\\n";
	$f[]="	margin-bottom: 10px;\\n";
	$f[]="	border: 2px solid #CCC;\\n";
	$f[]="	padding: 10px;\\n";
	$f[]="	spacing: 2px;\\n";
	$f[]="	}\\n";
	$f[]="parameter {\\n";
	$f[]="	display: block;\\n";
	$f[]="	padding: 2px;\\n";
	$f[]="	margin-top: 10px;\\n";
	$f[]="	border: 1px solid grey;\\n";
	$f[]="	background: #EEE;\\n";
	$f[]="	color: black;\\n";
	$f[]="	}\\n";
	$f[]="name {\\n";
	$f[]="	display: inline;\\n";
	$f[]="	float: left;\\n";
	$f[]="	margin-right: 5px;\\n";
	$f[]="	font-weight: bold;\\n";
	$f[]="	}\\n";
	$f[]="type {\\n";
	$f[]="	display: inline;\\n";
	$f[]="	font-size: x-small;\\n";
	$f[]="	margin-right: 5px;\\n";
	$f[]="	color: #666;\\n";
	$f[]="	white-space: nowrap;\\n";
	$f[]="	font-style: italic;\\n";
	$f[]="	}\\n";
	$f[]="description {\\n";
	$f[]="	display: inline;\\n";
	$f[]="	margin-right: 5px;\\n";
	$f[]="	white-space: nowrap;\\n";
	$f[]="	}\\n";
	$f[]="value {\\n";
	$f[]="	display: block;\\n";
	$f[]="	margin-right: 5px;\\n";
	$f[]="	}\\n";
	$f[]="[end]";
	$f[]="<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />\\n";
	$f[]="<pre><font size='-2'><b>";
	$f[]="(c)3APA3A, Владимир Дубровин и <A href='http://3proxy.ru/'>3proxy.ru</A>\\n";
	$f[]="</b></font>\\n";
	$f[]="</td></tr></table></body></html>";
	$f[]="[end]";
	$f[]="<h3>Счетчики</h3>\\n";
	$f[]="<table border = '1'>\\n";
	$f[]="<tr align='center'><td>Описание</td><td>Активный</td>";
	$f[]="<td>Пользователи</td><td>Адрес источника</td><td>Адрес назначения</td>";
	$f[]="<td>Порты</td>";
	$f[]="<td>Лимит</td><td>Ед.</td><td>Значение</td>";
	$f[]="<td>Дата сброса</td><td>Дата обновения</td><td>Номер</td></tr>\\n";
	$f[]="[end]";
	$f[]="</table>\\n";
	$f[]="[end]";
	$f[]="[/--admin--]";
    $f[]="[--proxy--]";
	$f[]="HTTP/1.0 400 Bad Request\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(1)."\\n";
	$f[]="[end]";
	$f[]="HTTP/1.0 502 Bad Gateway\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(2)."\\n";
	$f[]="[end]";
	//ID ->2
	$f[]="HTTP/1.0 503 Service Unavailable\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(3)."\\n";
	$f[]="[end]";
	//iD -> 3
	
	$f[]="HTTP/1.0 503 Service Unavailable\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(4)."\\n";
	$f[]="[end]";
	// ID->4
	

	
	$f[]="HTTP/1.0 501 Not Implemented\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(5)."\\n";
	$f[]="[end]";
	//ID ->5

	
	
	$f[]="HTTP/1.0 502 Bad Gateway\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(6)."\\n";
	$f[]="[end]";
	//ID=>6
	
	
	$f[]="HTTP/1.0 500 Internal Error\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(7)."\\n";
	$f[]="[end]";
	//ID=>7
	
	
	$f[]="HTTP/1.0 407 Proxy Authentication Required\\n";
	$f[]="Proxy-Authenticate: Basic realm=\"proxy\", encoding=\"utf-8\"\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(8)."\\n";
	$f[]="[end]";
	//ID->8
	
	
	$f[]="HTTP/1.0 200 Connection established\\n\\n";
	$f[]="[end]";
	
	$f[]="HTTP/1.0 200 Connection established\\n";
	$f[]="Content-Type: text/html\\n\\n";
	$f[]="[end]";
	
	$f[]="HTTP/1.0 404 Not Found\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(9)."\\n";
	$f[]="[end]	";
	//9
	

	
	
	$f[]="HTTP/1.0 403 Forbidden\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(10)."\\n";
	$f[]="[end]";
	//10
	
	
	
	
	$f[]="HTTP/1.0 407 Proxy Authentication Required\\n";
	$f[]="Proxy-Authenticate: NTLM\\n";
	$f[]="Proxy-Authenticate: basic realm=\"proxy\", encoding=\"utf-8\"\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(8)."\\n";
	$f[]="[end]";
	//8
	

	
	
	
	$f[]="HTTP/1.0 407 Proxy Authentication Required\\n";
	$f[]="Connection: keep-alive\\n";
	$f[]="Content-Length: 0\\n";
	$f[]="Proxy-Authenticate: NTLM ";
	$f[]="[end]";
	
	$f[]="HTTP/1.0 403 Forbidden\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=us-ascii\\n";
	$f[]="\\n";
	$f[]="<pre>";
	$f[]="[end]";
	
	
	$f[]="HTTP/1.0 503 Service Unavailable\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(11)."\\n";
	$f[]="[end]";
	//11
	

	
	
	$f[]="HTTP/1.0 401 Authentication Required\\n";
	$f[]="WWW-Authenticate: basic realm=\"FTP Server\", encoding=\"utf-8\"\\n";
	$f[]="Connection: close\\n";
	$f[]="Content-type: text/html; charset=utf-8\\n";
	$f[]="\\n";
	$f[]=BUILD_TEMPLATE(12)."\\n";
	$f[]="[end]";
	//12
	

	
	
	$f[]="HTTP/1.1 100 Continue\\n";
	$f[]="\\n";
	$f[]="[end]";
	
	$f[]="[/--proxy--]\n";
	
	
	@file_put_contents("/etc/3proxy/3proxy.ps", @implode("\n", $f));
	
	
}

function BUILD_TEMPLATE($tplid){
	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM `3proxy_acls_templates` WHERE ID=$tplid");
	$templateid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("3ProxyTemplateID"));
	$sock=new sockets();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$templateid=1;}
	if($templateid==0){$templateid=1;}
	$title=$ligne["title"];
	$content=$ligne["content"];
	
	$tpl=new templates_manager($templateid);
	$CssContent=$tpl->CssContent;
	$headContent=$tpl->headContent;
	$BodyContent=$tpl->BodyContent;
	
	$headContent=str_replace("%TITLE_HEAD%", $title, $headContent);
	$headContent=str_replace("%CSS%", "<style>$CssContent</style>", $headContent);
	$headContent=str_replace("%JQUERY%", "", $headContent);
	$headContent=str_replace("\n", "\\n", $headContent);
	$html="<H2>$title</H2><div id=\"content\"><hr>$content<hr></div>";
	
	$BodyContent=str_replace("%TITLE_HEAD%", $title, $BodyContent);
	$BodyContent=str_replace("%DYNAMIC_CONTENT%", $html, $BodyContent);
	$BodyContent=str_replace("\n", "\\n", $BodyContent);
	if(!stripos($BodyContent, "</body>")){$BodyContent=$BodyContent."</body>";}
	if(!stripos($BodyContent, "</html>")){$BodyContent=$BodyContent."</html>";}
	
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		$VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
		$footer="</p></h2></div><p>&nbsp;</p><div style='width:100%;border-top:1px solid white;background-color:#8C1919;color:white'><center >Artica  - Community Edition</center><center>visit http://wwww.articatech.com</center></div>";
		$BodyContent=str_ireplace("</body>", "$footer</body>", $BodyContent);
	}
	
	return $headContent.$BodyContent;

}

