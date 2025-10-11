<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";exit();}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="Network traffic probe";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit();}
if($argv[1]=="--clean"){$GLOBALS["OUTPUT"]=true;cleanstorage();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--cmdlines"){$GLOBALS["VERBOSE"]=true;GetCommandLines();exit();}
if($argv[1]=="--redis-restart"){$GLOBALS["VERBOSE"]=true;redis_restart();exit();}


function build_progress($text,$pourc):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"disable-ntopng.progress");
}
function build_progress_restart($text,$pourc):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"restart-ntopng.progress");
}


function redis_restart():bool{
	$unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["CLASS_UNIX"]=$unix;
	build_progress_restart("{stopping_service}",10);
	system("/usr/sbin/artica-phpfpm-service -restart-redis");
	build_progress_restart("{starting_service}",60);

	$redis_pid=redis_pid();
	if($unix->process_exists($redis_pid)){
		return build_progress_restart("{starting_service} {success}",100);
	}
	return build_progress_restart("{starting_service} {failed}",110);

	
}


function restart($nopid=false):bool{
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
	}
	@file_put_contents($pidfile, getmypid());
	build_progress_restart("{stopping_service}",10);
	stop(true);
    build_progress_restart("{stopping_service}",20);
    redis_restart();
	build_progress_restart("{reconfigure}",50);
	build();
	build_progress_restart("{starting_service}",60);
	if(!start(true)){
		build_progress_restart("{starting_service} {failed}",110);
		return false;
	}
	return build_progress_restart("{starting_service} {success}",100);
}

function reload($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());

	$sock=new sockets();
	$Enablentopng=$sock->GET_INFO("Enablentopng");
	if(!is_numeric($Enablentopng)){$Enablentopng=0;}
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	if($EnableIntelCeleron==1){$Enablentopng=0;}
	
	if($Enablentopng==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see Enablentopng )...\n";}
		stop();
		return;		
	}
	
	
	build();
	$masterbin=$unix->find_program("ntopng");
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} not installed\n";}
		return;
	}
	$pid=ntopng_pid();
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} Service running since {$time}Mn...\n";}
		unix_system_HUP($pid);
		return;
	}
	start(true);
}

function GET_ALL_NETS(){
	$unix=new unix();
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	foreach ($NETWORK_ALL_INTERFACES as $Interface=>$ligne){
		if(!isset($ligne["IPADDR"])){continue;}
		if($ligne["IPADDR"]=="0.0.0.0"){continue;}
		$tb=explode(".",$ligne["IPADDR"]);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Choose net(2) {$tb[0]}.{$tb[1]}.{$tb[2]}.0/24\n";}
		$NETS[]="{$tb[0]}.{$tb[1]}.{$tb[2]}.0/24";
		
	}
	return $NETS;
}

function NETWORK_ALL_INTERFACES():array{
	if(isset($GLOBALS["NETWORK_ALL_INTERFACES"])){return $GLOBALS["NETWORK_ALL_INTERFACES"];}
	$unix=new unix();
	$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES(true);
	unset($GLOBALS["NETWORK_ALL_INTERFACES"]["127.0.0.1"]);
    return $GLOBALS["NETWORK_ALL_INTERFACES"];
}


function build(){
	CheckFilesAndSecurity();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]}  done\n";}


}

function all_interfaces($choosen):string{
	$unix=new unix();
	$CacheFile="/etc/artica-postfix/settings/Daemons/NTOPNG_INTERFACES";
	$ConfFile=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTOPNG_INTERFACES_SET"));
	if(!is_array($ConfFile)){$ConfFile["any"]=true;}
	if(count($ConfFile)==0){$ConfFile["any"]=true;}
	
	$masterbin=$unix->find_program("ntopng");
	exec("$masterbin -h 2>&1",$results);
	foreach ($results as $index=>$ligne){
		if(preg_match("#\s+([0-9])\.\s+(.+)#", $ligne,$re)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Interface {$re[2]} = {$re[1]}\n";}
			$arrayINT[trim($re[2])]=$re[1];
		}
		
	}
	
	@file_put_contents($CacheFile, serialize($arrayINT));
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Interface choosen: $choosen\n";}
	
	$myInts=explode(",",$choosen);
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$NUMBERS=array();
	foreach ($myInts as $eth){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Interface To Num: $eth {$arrayINT[$eth]}\n";}
		if(isset($arrayINT[$eth])){
			$NUMBERS[]=$arrayINT[$eth];
		}
		
	}
	
	if(count($NUMBERS)>0){
		return " -i ".@implode(" ", $NUMBERS);
	}
	
		
	
	
	
	if(isset($ConfFile["any"])){
		return " -i any";
	}

	foreach ($ConfFile as $Interface=>$ligne){
		if($NETWORK_ALL_INTERFACES[$Interface]["IPADDR"]=="0.0.0.0"){continue;}
		$TRA[$Interface]=$Interface;
	}
    $b=array();
	foreach ($TRA as $Interface=>$ligne){
		$num=$arrayINT[$Interface];
		if(!is_numeric($num)){continue;}
		$b[]="-i $Interface";
	}
	return @implode(" ", $b);
	
}

function GetCommandLines(){
	$unix=new unix();
	$masterbin=$unix->find_program("ntopng");
    $ARRAY=array();
	exec("$masterbin --help 2>&1",$results);
	foreach ($results as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#\[(.*?)[\|<$\]]#", $line,$re)){continue;}
		$re[1]=trim($re[1]);
		if(trim($re[1])==null){continue;}
		$ARRAY[$re[1]]=true;
		
	}
	if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
	return $ARRAY;
}

function start($nopid=false):bool{
	$sock=new sockets();
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
	}
	
	
	
	$pid=ntopng_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already running since {$time}Mn...\n";}
		return true;
	}
	
	$Enablentopng=$sock->GET_INFO("Enablentopng");
	if(!is_numeric($Enablentopng)){$Enablentopng=0;}

	if($Enablentopng==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see Enablentopng )...\n";}
		return false;	
	}
	$masterbin=$unix->find_program("ntopng");
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed...\n";}
		return false;
	}
	
	$redis_pid=redis_pid();
	if(!$unix->process_exists($redis_pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting redis-server\n";}
	}
	$redis_pid=redis_pid();
	if(!$unix->process_exists($redis_pid)){
		$php=$unix->LOCATE_PHP5_BIN();
		build_progress_restart("{starting_service}",61);
		system("/usr/sbin/artica-phpfpm-service -start-redis");
		
	}
	$redis_pid=redis_pid();
	if(!$unix->process_exists($redis_pid)){
		if($GLOBALS["OUTPUT"]){
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed, unable to start redis-server\n";
        }
		return false;
	}
	
	build_progress_restart("{starting_service}",65);
	CheckFilesAndSecurity();
	$version=ntopng_version();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service v$version\n";}
	
	$ethtool=$unix->find_program("ethtool");
	$net=new networkscanner();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT ipaddr FROM networks_infos WHERE enabled=1");
    $hash=array();
    $MASKZ=array();

    foreach ($results as $index=>$ligne){
        $maks=trim($ligne["ipaddr"]);
		if(trim($maks)==null){continue;}
		if(isset($net->Networks_disabled[$maks])){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Analyze $maks\n";}
		$hash[$maks]=$maks;
	}
	foreach ($hash as $a){ $MASKZ[]=$a; }
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ". count($MASKZ)." network(s)\n";}
	if(count($MASKZ)==0){$MASKZ=GET_ALL_NETS();}
	
	$arrayConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ntopng")));
	if(!isset($arrayConf["INTERFACE"])){$arrayConf["INTERFACE"]=null;}
    if(!isset($arrayConf["HTTP_PORT"])){$arrayConf["HTTP_PORT"]=3000;}
    if(!isset($arrayConf["ENABLE_LOGIN"])){$arrayConf["ENABLE_LOGIN"]=0;}
    if(!isset($arrayConf["INTERFACE"])){$arrayConf["INTERFACE"]=null;}
    if(!is_numeric($arrayConf["HTTP_PORT"])){$arrayConf["HTTP_PORT"]=3000;}

	
	if(intval($arrayConf["ENABLE_LOGIN"])==1){
		$ldap=new clladp();
		$rediscli=$unix->find_program("redis-cli");
		shell_exec("$rediscli SET ntopng.user.$ldap->ldap_admin.full_name $ldap->ldap_admin");
		shell_exec("$rediscli SET ntopng.user.$ldap->ldap_admin.group administrator");
		shell_exec("$rediscli SET ntopng.user.$ldap->ldap_admin.password ".md5($ldap->ldap_password));
		
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} listen Interface {$arrayConf["INTERFACE"]}\n";} 
	if(!isset($arrayConf["INTERFACES"])){$arrayConf["INTERFACES"]="eth0";}
	
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	if($arrayConf["INTERFACE"]<>null){
		$ip_listen=$NETWORK_ALL_INTERFACES[$arrayConf["INTERFACE"]]["IPADDR"];
		if($ip_listen="0.0.0.0"){$ip_listen=null;}
	}
	
	foreach ($NETWORK_ALL_INTERFACES as $a=>$b){
		if($a=="lo"){continue;}
		if(preg_match("#dummy#", $a)){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $a gro off gso off tso off\n";}
		system("$ethtool -K $a gro off gso off tso off");
	}


    for($i=0;$i<10;$i++){
        $pid=$unix->PIDOF_BY_PORT($arrayConf["HTTP_PORT"]);
        if(!$unix->process_exists($pid)){break;}
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Killing PID $pid TCP port {$arrayConf["HTTP_PORT"]} used!\n";
        $unix->KILL_PROCESS($pid,9);
        sleep(1);
    }

	
	$TOKENS=GetCommandLines();
	
	$f[]=$masterbin;
	$f[]="--daemon";
	//$f[]="--verbose";
	if(isset($TOKENS["--community"])){$f[]="--community";}
	$f[]="--dns-mode 1";
	$f[]="--http-port 127.0.0.1:{$arrayConf["HTTP_PORT"]}";
	$f[]="--http-prefix /ntopng";
	
	if(intval($arrayConf["ENABLE_LOGIN"])==0){
		$f[]="--disable-login 1";
	}
	$f[]="--local-networks \"".@implode(",", $MASKZ)."\"";
	$f[]="--user root";
	$f[]="--data-dir /home/ntopng";
	$f[]="--install-dir /usr/local/share/ntopng";
	$f[]="--httpdocs-dir /usr/local/share/ntopng/httpdocs";
    $f[]="--scripts-dir /usr/local/share/ntopng/scripts";
    $f[]="--callbacks-dir /usr/local/share/ntopng/scripts/callbacks";
    $f[]="--redis /var/run/redis/redis.sock";
    $f[]="--pid /var/run/ntopng/ntopng.pid";
	$f[]=all_interfaces($arrayConf["INTERFACES"]);

	$cmd=@implode(" ", $f);
	if(!$unix->go_exec($cmd)){shell_exec($cmd);}
	build_progress_restart("{starting_service}",66);
	$c=1;
	$c2=66;
	sleep(1);
	for($i=0;$i<10;$i++){
		$c2++;
		sleep(1);
		build_progress_restart("{starting_service}",$c2);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service waiting $c/10\n";}
		$pid=ntopng_pid();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success PID $pid\n";}
			break;
		}
		$c++;
	}
	
	$pid=ntopng_pid();
	if(!$unix->process_exists($pid)){
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $cmd\n";}
		return false;
	}
	
	build_progress_restart("{starting_service}",70);
	return true;
}

function CheckFilesAndSecurity(){
	$unix=new unix();
	$f[]="/var/run/ntopng";
	$f[]="/var/log/ntopng";
	$f[]="/home/ntopng";
	$f[]="/var/tmp/ntopng";


    foreach ($f as $val){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} checking \"$val\"\n";}
		if(!is_dir($val)){@mkdir($val,0755,true);}
		//$unix->chown_func("redis","redis","$val/*");
	}
	
	chown("/var/tmp/ntopng", "nobody");
	
}

function stop(){

	$unix=new unix();
	$sock=new sockets();
	$masterbin=$unix->find_program("ntopng");
	
	
	
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed\n";}
		return false;
		
	}
	
	
	$pid=ntopng_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already stopped...\n";}
		return true;
	}

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	


	

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		build_progress_restart("{stopping_service}",$i*10);
		$pid=ntopng_pid();
		if(!$unix->process_exists($pid)){break;}
		unix_system_kill($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=ntopng_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return true;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		build_progress_restart("{stopping_service}",$i*10);
		$pid=ntopng_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		unix_system_kill_force($pid);
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success stopped...\n";}
		return true;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}

function ntopng_version(){
	$unix=new unix();
	if(isset($GLOBALS["ntopng_version"])){return $GLOBALS["ntopng_version"];}
	$masterbin=$unix->find_program("ntopng");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -h 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#ntopng.*?v\.([0-9\.]+)#", $val,$re)){
			$GLOBALS["ntopng_version"]=trim($re[1]);
			return $GLOBALS["ntopng_version"];
		}
	}
}

function ntopng_pid(){
	$unix=new unix();
	$masterbin=$unix->find_program("ntopng");
	$pid=$unix->get_pid_from_file('/var/run/ntopng/ntopng.pid');
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($masterbin);
}
function redis_pid(){
	$unix=new unix();
	$masterbin=$unix->find_program("redis-server");
	$pid=$unix->get_pid_from_file('/var/run/redis/redis-server.pid');
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN($masterbin." -f /etc/redis/redis.conf");
}

function cleanstorage(){
	
	$sock=new sockets();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	$CacheFile="/etc/artica-postfix/settings/Daemons/NTOPNgSize";
	if(!is_file($pidfile)){@touch($pidfile);}
	$pid=file_get_contents("$pidfile");
	if($GLOBALS["VERBOSE"]){echo "$timefile\n";}
	
	if(system_is_overloaded(basename(__FILE__))){exit();}
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timeMin=$unix->PROCCESS_TIME_MIN($pid);
		if($timeMin>240){
			squid_admin_mysql(2, "Too many TTL, $pid will be killed",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
		}else{
			exit();
		}
	}
	if(is_file($CacheFile)){
		if(!$GLOBALS["FORCE"]){
			$TimeExec=$unix->file_time_min($timefile);
			if($TimeExec<1880){return;}
		}
	}
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	
	$sock=new sockets();
	$arrayConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ntopng")));
	
	$Enablentopng=$sock->GET_INFO("Enablentopng");
	if(!is_numeric($Enablentopng)){$Enablentopng=0;}
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	if($EnableIntelCeleron==1){$Enablentopng=0;}

    if(!isset($arrayConf["HTTP_PORT"])){$arrayConf["HTTP_PORT"]=3000;}
    if(!isset($arrayConf["ENABLE_LOGIN"])){$arrayConf["ENABLE_LOGIN"]=0;}
    if(!isset($arrayConf["MAX_DAYS"])){$arrayConf["MAX_DAYS"]=30;}
    if(!isset($arrayConf["MAX_SIZE"])){$arrayConf["MAX_SIZE"]=5000;}

	if(!is_numeric($arrayConf["HTTP_PORT"])){$arrayConf["HTTP_PORT"]=3000;}
	if(!is_numeric($arrayConf["ENABLE_LOGIN"])){$arrayConf["ENABLE_LOGIN"]=0;}
	if(!is_numeric($arrayConf["MAX_DAYS"])){$arrayConf["MAX_DAYS"]=30;}
	if(!is_numeric($arrayConf["MAX_SIZE"])){$arrayConf["MAX_SIZE"]=5000;}
	
	$rm=$unix->find_program("rm");
	$size=$unix->DIRSIZE_MB("/home/ntopng");
	
	
	if($size>$arrayConf["MAX_SIZE"]){
		shell_exec("$rm -rf /home/ntopng");
		$redis=$unix->find_program("redis-cli");
		shell_exec("$redis flushall");
		squid_admin_mysql(1, "Removing NTOP NG directory {$size}MB, exceed {$arrayConf["MAX_SIZE"]}MB", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/ntopng restart");
	}
	
	$ThisYear=date("y");
	$directory="/home/ntopng/db";
	
	if(!is_dir($directory)){return;}
	
	$unix=new unix();
	
	
	
	if(is_dir("/home/ntopng/db/{$ThisYear}")){
		echo "Scanning /home/ntopng/db/{$ThisYear}\n";
		$directory="/home/ntopng/db/{$ThisYear}";
		$thisMonth=date("m");
		if(strlen($thisMonth)==1){$thisMonth="0{$thisMonth}";}
		if(!is_dir($directory)){return;}
		
		echo "Skip /home/ntopng/db/{$ThisYear}/{$thisMonth}\n";
		$dirs=$unix->dirdir($directory);
		
		foreach ($dirs as $scanneddir=>$line){
			$month=basename($scanneddir);
			if($month==$thisMonth){
				echo "Skip $thisMonth\n";
				continue;
			}
			
			echo "Remove $scanneddir\n";
			shell_exec("$rm -rf $scanneddir");
		}
			
		if($arrayConf["MAX_DAYS"]==30){return;}
	
		echo "/home/ntopng/db/{$ThisYear}/{$thisMonth}";
		$dirs=$unix->dirdir("/home/ntopng/db/{$ThisYear}/{$thisMonth}");
		if($dirs<$arrayConf["MAX_DAYS"]){return;}
        foreach ($dirs as $scanneddir=>$line){
			$basename=basename($scanneddir);
			$T[$basename]=$scanneddir;
			
		}
		
		ksort($T);
		print_r($T);
			
		$CurrentDays=count($T);
		$Tokeep=$CurrentDays-$arrayConf["MAX_DAYS"];
		if($Tokeep<1){return;}
		
		echo "Keeping $Tokeep days\n";
		$c=0;
        foreach ($T as $dir=>$path){
			echo "Remove $path\n";
			shell_exec("$rm -rf $path");
			$c++;
			if($c>=$Tokeep){break;}
		}
	}
	

	
}

