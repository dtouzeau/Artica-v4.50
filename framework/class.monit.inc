<?php
class monit_unix{
	private $monitbin;
	private $rmbin;
	private $state;
	public $start_cmdline;
	public $stop_cmdline;
	public $monitor_all_cmdline;
	private $nohup=null;
	private $EVENTS=array();

	function __construct(){

	    if(!class_exists("unix")){include_once(dirname(__FILE__)."/class.unix.inc");}

		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
		if(!isset($GLOBALS["COUNT_DE_STOPPED"])){$GLOBALS["COUNT_DE_STOPPED"]=array();}
		
		
		$this->monitbin=$GLOBALS["CLASS_UNIX"]->find_program("monit");
		$this->rmbin=$GLOBALS["CLASS_UNIX"]->find_program("rm");
		$this->nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
		$this->state="-c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state";
		$this->start_cmdline="$this->monitbin $this->state";
		$this->stop_cmdline="$this->monitbin $this->state quit";
		$this->monitor_all_cmdline="$this->monitbin $this->state monitor all";
	}


	public function COUNT_DE_STOPPED(){
		
		$date=date("YmdHi");
		if(isset($GLOBALS["COUNT_DE_STOPPED"][$date])){return $GLOBALS["COUNT_DE_STOPPED"][$date];}	
		if(!is_file($this->monitbin)){return;}
		if(!$this->IS_RUN()){
			$this->mlogs("monit did not running, start it..");
			shell_exec("/etc/init.d/monit start");}
		$this->mlogs("Checking summary");
		exec("$this->monitbin $this->state summary 2>&1",$results);
		$ARRAY=array();
		foreach ($results as $index=>$line){
			if(!preg_match("#^(.+)\s+'(.*?)'\s+Not monitored#i",$line,$re)){continue;}
			
			$APP_KEY=$re[2];
			if($APP_KEY=="APP_NGINX"){
				if($GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableNginx')==0){continue;}
			}
			
			$this->ToSyslog("COUNT_DE_STOPPED(): {$re[2]} is not monitored");
			$ARRAY[$re[2]]=true;	
		}
		
		if(count($GLOBALS["COUNT_DE_STOPPED"])>5){unset($GLOBALS["COUNT_DE_STOPPED"]);}
		$GLOBALS["COUNT_DE_STOPPED"][$date]=count($ARRAY);
		return count($ARRAY);
	}

	public function RELOAD(){
		if(!is_file($this->monitbin)){return;}
		$this->mlogs("Reload MONIT");
		shell_exec("$this->monitbin -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
		$this->WAKEUP();
	
	}
	
	public function WAKEUP(){
		$count=$this->COUNT_DE_STOPPED();
		if($this->COUNT_DE_STOPPED()>0){
			$this->mlogs("Wake up MONIT ( $count unmonitored services )");
			shell_exec("$this->nohup $this->monitor_all_cmdline >/dev/null 2>&1 &");
		}
	
	}
	
	private function mlogs($text){
		if(!function_exists("syslog")){return;}
		$this->ToSyslog($text);
	}
	
	public function MONITOR($servicename){
		$this->mlogs("Wake up MONITOR ( $servicename )");
		shell_exec("$this->nohup $this->monitbin $this->state monitor $servicename >/dev/null 2>&1 &");
	}
	
	public function IS_RUN(){
		if($GLOBALS["CLASS_UNIX"]->process_exists($this->PID_NUM())){return true;}
		return false;
	}
	
	
	public function PID_NUM(){
		if(!is_file($this->monitbin)){return;}
		if(is_file("/var/run/monit/monit.pid")) {
            $master_pid = trim(@file_get_contents("/var/run/monit/monit.pid"));
            if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
                return $master_pid;
            }
        }
		return $GLOBALS["CLASS_UNIX"]->PIDOF($this->monitbin);
		
	}

	private function logs($text){
	    $this->EVENTS[]=$text;
    }

    public function getlogs(){
	    $results=@implode("\n",$this->EVENTS);
	    unset($this->EVENTS);
	    return $results;

    }
	
	public function all_status():array{
        $product        = null;
		$cmdline        = "$this->monitbin $this->state status 2>&1";
        $ARR            = array();

        if(!isset($GLOBALS["AS_ROOT"])){
            if(function_exists("posix_getuid")){
                if(posix_getuid()==0){
                    $GLOBALS["AS_ROOT"]=true;
                }
            }
        }

		if(!$GLOBALS["AS_ROOT"]){
		    $sock=new sockets();
		    $data = $sock->REST_API("/system/monit/allstatus");
            $json = json_decode($data);
            if (json_last_error() > JSON_ERROR_NONE) {
                return $ARR;
            }
            if(!$json->Status){
                return $ARR;
            }
            $array=explode("\n",base64_decode($json->Data));
        }else{
            if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
            $this->logs($cmdline);
            exec($cmdline,$array);
        }


		if(!is_array($array)){
		    $this->logs("all_status():: Not an array");
			if($GLOBALS["VERBOSE"]){echo "all_status():: Not an array\n";}
			return array();
		}
        $this->logs("all_status():: ".count($array)." lines");
		foreach ($array as $num=>$ligne){
			if($ligne==null){continue;}
			
			if(preg_match("#Process\s+'(.+)'#",$ligne,$re)){
				if(trim($re[1])==null){
					if($GLOBALS["VERBOSE"]){echo "Found NO Process!! '$ligne'\n";}
					continue;
				}
				$product=$re[1];
				$this->logs("Found Process $product '$ligne'");
				if($GLOBALS["VERBOSE"]){echo "Found Process $product '$ligne'\n";}
				$ARR[$product]["monit"]=true;
				continue;
			}
		
			if(preg_match("#status\s+(.+)#",$ligne,$re)){
				$ARR[$product]["status"]=$re[1];
				continue;
			}
		
		
			if(preg_match("#monitoring status\s+(.+)#",$ligne,$re)){
				$ARR[$product]["monitoring status"]=$re[1];
				continue;
			}
		
			if(preg_match("#uptime\s+(.+)#",$ligne,$re)){
				$ARR[$product]["uptime"]=$re[1];
				continue;
			}
			if(preg_match("#children\s+(.+)#",$ligne,$re)){
				$ARR[$product]["children"]=$re[1];
				continue;
			}
		
		
			if(preg_match("#memory kilobytes total\s+([0-9\.]+)\s+([a-zA-Z]+)#",$ligne,$re)){
				if(strtolower($re[2])=="mb"){$re[1]=$re[1]*1024;}
				if(strtolower($re[2])=="gb"){$re[1]=$re[1]*1024;$re[1]=$re[1]*1024;}
				$ARR[$product]["memory kilobytes total"]=$re[1];
				continue;
			}
			if(preg_match("#memory kilobytes total\s+([0-9\.]+)$#",$ligne,$re)){
				$ARR[$product]["memory kilobytes total"]=$re[1];
				continue;
			}
			
			if(preg_match("#memory total\s+.*?\[([0-9\.]+)\s+([a-zA-Z]+)\]#",$ligne,$re)){
				if(strtolower($re[2])=="mb"){$re[1]=$re[1]*1024;}
				if(strtolower($re[2])=="gb"){$re[1]=$re[1]*1024;$re[1]=$re[1]*1024;}
				$ARR[$product]["memory kilobytes total"]=$re[1];
				continue;
			}
			
			if(preg_match("#memory\s+.*?%\s+\[([0-9\.]+)\s+([a-zA-Z]+)\]#",$ligne,$re)){
				if($GLOBALS["VERBOSE"]){echo "Found Process $product Memory {$re[1]} '$ligne'\n";}
				if(strtolower($re[2])=="mb"){$re[1]=$re[1]*1024;}
				if(strtolower($re[2])=="gb"){$re[1]=$re[1]*1024;$re[1]=$re[1]*1024;}
				if($GLOBALS["VERBOSE"]){echo "Found Process $product Memory {$re[1]}KB '$ligne'\n";}
				$ARR[$product]["memory kilobytes total"]=$re[1];
				continue;
			}
			
		
			if(preg_match("#^pid\s+([0-9]+)$#",trim($ligne),$re)){
				$ARR[$product]["pid"]=$re[1];
				continue;
			}
		
			if(preg_match("#memory percent total\s+(.+)#",$ligne,$re)){
				
				$ARR[$product]["memory percent total"]=$re[1]."%";
				continue;
			}
			if(preg_match("#cpu percent total\s+([0-9\.,]+)#",$ligne,$re)){
				if($GLOBALS["VERBOSE"]){echo "Found Process $product cpu percent total {$re[1]}% '$ligne'\n";}
				$ARR[$product]["cpu percent total"]=$re[1];
				continue;
			}
			
			if(preg_match("#cpu total\s+([0-9\.,]+)#",$ligne,$re)){
				if($GLOBALS["VERBOSE"]){echo "Found Process $product cpu percent total {$re[1]}% '$ligne'\n";}
				$ARR[$product]["cpu percent total"]=$re[1]."%";
				continue;
			}

            if(preg_match("#^cpu\s+([0-9\.,]+)#",trim($ligne),$re)){
                if($GLOBALS["VERBOSE"]){echo "Found Process $product cpu {$re[1]}% '$ligne'\n";}
                $ARR[$product]["CPU"]=$re[1]."%";
                continue;
            }

			$this->logs("No match '$ligne'");
			if($GLOBALS["VERBOSE"]){echo "No match '$ligne'\n";}
			
		}
		if($GLOBALS["VERBOSE"]){print_r($ARR);}
		return $ARR;		

	}
	
	
	public function CheckFolders(){
		if(!is_file($this->monitbin)){return;}
		$unix=new unix();

		@mkdir("/var/run/monit",0600,true);
		@mkdir("/var/monit",0600,true);
		@mkdir("/etc/monit/conf.d",0600,true);
		$GLOBALS["CLASS_UNIX"]->chmod_func(0600, "/etc/monit/monitrc/*");
		shell_exec("$this->rmbin -rf /var/monit/* >/dev/null 2>&1");
		
		
	}
	
	function ToSyslog($text,$error=false){
		
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog("monit", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}

}