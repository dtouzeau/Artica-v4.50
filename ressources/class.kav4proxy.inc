<?php
include_once(dirname(__FILE__). '/class.ini.inc');
include_once(dirname(__FILE__). '/class.mysql.inc');
include_once(dirname(__FILE__). '/class.artica.inc');
class Kav4Proxy{
	private $q;
	public $main_array=array();
	public $VERSION=0;
	public $templates=array();
	public $templates_data=array();
	
	function __construct(){
		$users=new usersMenus();
		if(!$GLOBALS["AS_ROOT"]){
			$this->VERSION=$users->KAV4PROXY_VERSION;
		}else{
			$this->VERSION=$this->GET_ROOT_VERSION();
		}
        $this->WriteDefault();
		$this->fill_templates();
		$this->Build_defaults();
		$this->LoadTemplates();
	}
	
	public function GET($key,$value){

	}
	
	private function fill_templates(){
		$this->templates["bases_error"]=True;
		$this->templates["license_error"]=True;
		$this->templates["object_corrupted"]=True;
		$this->templates["object_cured"]=True;
		$this->templates["object_error"]=True;
		$this->templates["object_infected"]=True;
		$this->templates["object_protected"]=True;
		$this->templates["object_suspicious"]=True;
		$this->templates["object_warning"]=True;		
		
	}
	
	public function WriteDefault(){

	    $q=new lib_sqlite("/home/artica/SQLITE/kav4proxy.db");
	    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `ExcludeMimeType` (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`pattern` text UNIQUE)");

        $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `ExcludeURL` (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`pattern` text UNIQUE)");

        if($q->COUNT_ROWS("ExcludeMimeType")==0){
            $ExcludeMimeTypes[]="audio/mpeg";
            $ExcludeMimeTypes[]="video/x-msvideo";
            $ExcludeMimeTypes[]="image/gif";
            $ExcludeMimeTypes[]="image/jpeg";
            $ExcludeMimeTypes[]="application/x-msmediaview";
            $ExcludeMimeTypes[]="video/mpeg";
            $ExcludeMimeTypes[]="audio/x-pn-realaudio";
            $ExcludeMimeTypes[]="application/vnd.ms.wms-hdr.asfv";
            $ExcludeMimeTypes[]="video/mp4";
            $ExcludeMimeTypes[]="video/mpeg";
            $ExcludeMimeTypes[]="video/mpeg4";
            $ExcludeMimeTypes[]="video/mpg";
            $ExcludeMimeTypes[]="video/quicktime";
            $ExcludeMimeTypes[]="video/vnd.rn-realvideo";
            $ExcludeMimeTypes[]="video/x-dv";
            $ExcludeMimeTypes[]="video/x-la-asf";
            $ExcludeMimeTypes[]="video/x-mpeg";
            $ExcludeMimeTypes[]="video/x-ms-asf";
            $ExcludeMimeTypes[]="video/x-ms-wmv";
            $ExcludeMimeTypes[]="video/x-ms-wmx";
            $ExcludeMimeTypes[]="video/x-ms-wvx";
            $ExcludeMimeTypes[]="video/mp4";
            $ExcludeMimeTypes[]="video/mpeg";
            $ExcludeMimeTypes[]="video/mpeg4";
            $ExcludeMimeTypes[]="video/mpg";
            $ExcludeMimeTypes[]="video/quicktime";
            $ExcludeMimeTypes[]="video/vnd.rn-realvideo";
            $ExcludeMimeTypes[]="video/x-dv";
            $ExcludeMimeTypes[]="video/x-la-asf";
            $ExcludeMimeTypes[]="video/x-mpeg";
            $ExcludeMimeTypes[]="video/x-ms-asf";
            $ExcludeMimeTypes[]="video/x-ms-wmv";
            $ExcludeMimeTypes[]="video/x-ms-wmx";
            $ExcludeMimeTypes[]="video/x-ms-wvx";
            $ExcludeMimeTypes[]="application/x-mms-framed";
            $ExcludeMimeTypes[]="application/vnd.rn-realmedia";
            $ExcludeMimeTypes[]="application/x-troff-msvideo";
            $ExcludeMimeTypes[]="application/x-rtsp-tunnelled";
            $ExcludeMimeTypes[]="application/application/x-pncmd";
            $ExcludeMimeTypes[]="application/x-dvi";
            $ExcludeMimeTypes[]="application/x-isoview";
            $ExcludeMimeTypes[]="video/3gpp";
            $ExcludeMimeTypes[]="video/dv";
            $ExcludeMimeTypes[]="video/isivideo";
            $ExcludeMimeTypes[]="video/mpeg";
            $ExcludeMimeTypes[]="video/quicktime";
            $ExcludeMimeTypes[]="video/vivo";
            $ExcludeMimeTypes[]="video/vnd.rn-realvideo";
            $ExcludeMimeTypes[]="video/wavelet";
            $ExcludeMimeTypes[]="video/x-3gpp2";
            $ExcludeMimeTypes[]="video/x-anim";
            $ExcludeMimeTypes[]="video/x-avi";
            $ExcludeMimeTypes[]="video/x-flic";
            $ExcludeMimeTypes[]="video/x-mng";
            $ExcludeMimeTypes[]="video/x-ms-asf";
            $ExcludeMimeTypes[]="video/x-ms-wmv";
            $ExcludeMimeTypes[]="video/x-msvideo";
            $ExcludeMimeTypes[]="video/x-nsv";
            $ExcludeMimeTypes[]="video/x-real-video";
            $ExcludeMimeTypes[]="video/x-sgi-movie";
            $ExcludeMimeTypes[]="video/flv";
            $ExcludeMimeTypes[]="video/x-flv";
            $ExcludeMimeTypes[]="video/divx";
            $ExcludeMimeTypes[]="video/x-matroska";
            $ExcludeMimeTypes[]="video/x-quicktime";
            $ExcludeMimeTypes[]="video/mp4";
            $ExcludeMimeTypes[]="flv-application/octet-stream";
            $ExcludeMimeTypes[]="application/x-shockwave-flash";
            $ExcludeMimeTypes[]="text/plain";

            foreach ($ExcludeMimeTypes as $mime){
                $q->QUERY_SQL("INSERT OR IGNORE INTO ExcludeMimeType (pattern) VALUES ('$mime')");
            }


        }


        if($q->COUNT_ROWS("ExcludeURL")==0) {
            $ExcludeURL[] = "\.deb([$|\?])";
            $ExcludeURL[] = "\.rpm([$|\?])";
            $ExcludeURL[] = "\.iso([$|\?])";
            $ExcludeURL[] = "\.img([$|\?])";
            $ExcludeURL[] = "\.nrg([$|\?])";
            $ExcludeURL[] = "\.cue([$|\?])";
            $ExcludeURL[] = "\.img([$|\?])";
            $ExcludeURL[] = "\.jpg([$|\?])";
            $ExcludeURL[] = "\.gif([$|\?])";
            $ExcludeURL[] = "\.jpeg([$|\?])";
            $ExcludeURL[] = "\.css([$|\?])";
            $ExcludeURL[] = "\.png([$|\?])";
            $ExcludeURL[] = "\.ico([$|\?])";
            foreach ($ExcludeURL as $mime) {
                $q->QUERY_SQL("INSERT OR IGNORE INTO ExcludeURL (pattern) VALUES ('$mime')");
            }
        }
		


		
		}
	
	public function SET($key,$value,$data){
	}
	public function MOD($key,$value,$data){

	}	
	
	private function ExcludeMimeType(){
        $q=new lib_sqlite("/home/artica/SQLITE/kav4proxy.db");
        $results=$q->QUERY_SQL("SELECT * FROM ExcludeMimeType ORDER BY pattern");
        foreach ($results as $index=>$ligne){
			$f[]="ExcludeMimeType=^{$ligne["pattern"]}$";
		}
		return implode("\n",$f);
		
	}
	
	private function ExcludeURL(){
        $q=new lib_sqlite("/home/artica/SQLITE/kav4proxy.db");
        $results=$q->QUERY_SQL("SELECT * FROM ExcludeURL ORDER BY pattern");
		foreach ($results as $index=>$ligne){
			$f[]="ExcludeURL={$ligne["pattern"]}";
		}
		return implode("\n",$f);
		
	}	
	
	private function GET_ROOT_VERSION(){
		exec("/opt/kaspersky/kav4proxy/sbin/kav4proxy-kavicapserver -v 2>&1",$results);
		foreach ($results as $num=>$ligne){
		if(preg_match("#version ([0-9\.]+)\/RELEASE build#", $ligne,$re)){return $re[1];}
		}
		
	}
	
	
function Build_defaults(){
		$artica=new artica_general();
		if($artica->ArticaProxyServerEnabled=='yes'){
			$this->main_array["UseProxy"]='yes';
			$this->main_array["ProxyAddress"]=$artica->ArticaCompiledProxyUri;
		}
		
		$this->main_array["MaxReqLength"]=$this->GET("icapserver.filter","MaxReqLength");
		$this->main_array["PreviewSize"]=$this->GET("icapserver.protocol","PreviewSize");	
		$this->main_array["MaxChildren"]=$this->GET("icapserver.process","MaxChildren");	
		$this->main_array["IdleChildren"]=$this->GET("icapserver.process","IdleChildren");	
		$this->main_array["MaxReqsPerChild"]=$this->GET("icapserver.process","MaxReqsPerChild");	
		$this->main_array["MaxEnginesPerChild"]=$this->GET("icapserver.process","MaxEnginesPerChild");	
		$this->main_array["PreviewSize"]=$this->GET("icapserver.protocol","PreviewSize");
		$this->main_array["ListenAddress"]=$this->GET("icapserver.network","ListenAddress");
		
		
		$Rev88Opts["MaxNestingLevel"]=8;
		$Rev88Opts["DetectVirware"]=1;
		$Rev88Opts["DetectTrojware"]=1;
		$Rev88Opts["DetectMalware"]=1;
		$Rev88Opts["DetectAdware"]=1;
		$Rev88Opts["DetectPornware"]=1;
		$Rev88Opts["DetectRiskware"]=1;
		$Rev88Opts["DetectXfiles"]=0;
		$Rev88Opts["DetectSoftware"]=0;
		
		while (list ($fieldname, $default) = each ($Rev88Opts) ){
			$this->main_array["$fieldname"]=$this->GET("icapserver.engine.options",$fieldname);
			if($this->main_array["$fieldname"]==null){$this->main_array["$fieldname"]=$default;}
		}
					
		if(!isset($this->main_array["Timeout"])){$this->main_array["Timeout"]=null;}
		if(!isset($this->main_array["AnswerMode"])){$this->main_array["AnswerMode"]=null;}
		if(!isset($this->main_array["MaxSendDelayTime"])){$this->main_array["MaxSendDelayTime"]=null;}
		if(!isset($this->main_array["MaxConnections"])){$this->main_array["MaxConnections"]=null;}
		if(!isset($this->main_array["Allow204"])){$this->main_array["Allow204"]=null;}
		
		
		if($this->main_array["Timeout"]==null){$this->main_array["Timeout"]=5;}
		if($this->main_array["MaxChildren"]==null){$this->main_array["MaxChildren"]=10;}
		if($this->main_array["IdleChildren"]==null){$this->main_array["IdleChildren"]=1;}
		if($this->main_array["MaxReqsPerChild"]==null){$this->main_array["MaxReqsPerChild"]="15000";}
		if($this->main_array["MaxEnginesPerChild"]==null){$this->main_array["MaxEnginesPerChild"]=10;}
		if($this->main_array["AnswerMode"]==null){$this->main_array["AnswerMode"]="partial";}
		if($this->main_array["MaxSendDelayTime"]==null){$this->main_array["MaxSendDelayTime"]="10";}
		if($this->main_array["PreviewSize"]==null){$this->main_array["PreviewSize"]="128";}
		if($this->main_array["MaxConnections"]==null){$this->main_array["MaxConnections"]="5000";}
		if($this->main_array["Allow204"]==null){$this->main_array["Allow204"]="yes";}
		
		if(!isset($this->main_array["ScanMailBases"])){$this->main_array["ScanMailBases"]=null;}
		if(!isset($this->main_array["ScanMailPlain"])){$this->main_array["ScanMailPlain"]=null;}
		if(!isset($this->main_array["UseHeuristic"])){$this->main_array["UseHeuristic"]=null;}
		if(!isset($this->main_array["Cure"])){$this->main_array["Cure"]=null;}
		if(!isset($this->main_array["UseAVbasesSet"])){$this->main_array["UseAVbasesSet"]=null;}
		if(!isset($this->main_array["MaxScanTime"])){$this->main_array["MaxScanTime"]=null;}
		if(!isset($this->main_array["UseUpdateServerUrl"])){$this->main_array["UseUpdateServerUrl"]=null;}
		if(!isset($this->main_array["UseUpdateServerUrlOnly"])){$this->main_array["UseUpdateServerUrlOnly"]=null;}
		if(!isset($this->main_array["RegionSettings"])){$this->main_array["RegionSettings"]=null;}
		if(!isset($this->main_array["UseProxy"])){$this->main_array["UseProxy"]=null;}
		if(!isset($this->main_array["ListenAddress"])){$this->main_array["ListenAddress"]=null;}
		if(!isset($this->main_array["ScanPacked"])){$this->main_array["ScanPacked"]=null;}
		if(!isset($this->main_array["ScanArchives"])){$this->main_array["ScanArchives"]=null;}
		if(!isset($this->main_array["UpdateServerUrl"])){$this->main_array["UpdateServerUrl"]=null;}
		if(!isset($this->main_array["MaxReqLengthAction"])){$this->main_array["MaxReqLengthAction"]=null;}
		if(!isset($this->main_array["PartialResponseAction"])){$this->main_array["PartialResponseAction"]=null;}
		if(!isset($this->main_array["PartialRequestAction"])){$this->main_array["PartialRequestAction"]=null;}								
		
		if($this->main_array["ScanPacked"]==null){$this->main_array["ScanPacked"]="yes";}
		if($this->main_array["ScanArchives"]==null){$this->main_array["ScanArchives"]="yes";}
		if($this->main_array["ScanMailBases"]==null){$this->main_array["ScanMailBases"]="yes";}
		if($this->main_array["ScanMailPlain"]==null){$this->main_array["ScanMailPlain"]="yes";}
		if($this->main_array["UseHeuristic"]==null){$this->main_array["UseHeuristic"]="yes";}
		if($this->main_array["Cure"]==null){$this->main_array["Cure"]="no";}
		if($this->main_array["UseAVbasesSet"]==null){$this->main_array["UseAVbasesSet"]="extended";}
		if($this->main_array["MaxScanTime"]==null){$this->main_array["MaxScanTime"]="300";}
		if($this->main_array["UseUpdateServerUrl"]==null){$this->main_array["UseUpdateServerUrl"]="no";}
		if($this->main_array["UpdateServerUrl"]==null){$this->main_array["UpdateServerUrl"]="ftp://downloads1.kaspersky-labs.com";}
		if($this->main_array["UseUpdateServerUrlOnly"]==null){$this->main_array["UseUpdateServerUrlOnly"]="no";}
		if($this->main_array["RegionSettings"]==null){$this->main_array["RegionSettings"]="Europe";}
		if($this->main_array["UseProxy"]==null){$this->main_array["UseProxy"]="no";}
		if($this->main_array["ListenAddress"]==null){$this->main_array["ListenAddress"]="127.0.0.1:1344";}
		if($this->main_array["MaxReqLengthAction"]==null){$this->main_array["MaxReqLengthAction"]="skip";}
		if($this->main_array["PartialResponseAction"]==null){$this->main_array["PartialResponseAction"]="check";}
		if($this->main_array["PartialRequestAction"]==null){$this->main_array["PartialRequestAction"]="check";} 

		
		

		
	}
	
	public function is_tmpfs_mounted(){
		$unix=new unix();
		$mount=$unix->find_program("mount");
		exec("$mount 2>&1",$results);
		foreach ($results as $num=>$ligne){if(preg_match("#^tmpfs.+?Kav4proxy.+?tmpfs#", $ligne)){return true;}}
		return false;
		
	}
	
	
	function build_config(){
		$sock=new sockets();
		$TempPath="/tmp";
		if($GLOBALS["AS_ROOT"]){
			$unix=new unix();
			$mount=$unix->find_program("mount");
			$umount=$unix->find_program("umount");
			$Kav4ProxyTMPFS=$sock->GET_INFO("Kav4ProxyTMPFS");
			if(!is_numeric($Kav4ProxyTMPFS)){$Kav4ProxyTMPFS=0;}
			$Kav4ProxyTMPFSMB=$sock->GET_INFO("Kav4ProxyTMPFSMB");
			if(!is_numeric($Kav4ProxyTMPFSMB)){$Kav4ProxyTMPFSMB=512;}	
			if($Kav4ProxyTMPFS==1){
				echo "Starting......: ".date("H:i:s")." Kav4proxy tmpfs is enabled for {$Kav4ProxyTMPFSMB}MB\n";
				if(!$this->is_tmpfs_mounted()){
					if(!is_dir("/tmp/Kav4proxy")){@mkdir("/tmp/Kav4proxy",0755,true);}
					$cmd="$mount -t tmpfs -o size={$Kav4ProxyTMPFSMB}M tmpfs /tmp/Kav4proxy";
					if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
					shell_exec($cmd);
					
					if($this->is_tmpfs_mounted()){
						echo "Starting......: ".date("H:i:s")." Kav4proxy tmpfs mounted...\n";
						$TempPath="/tmp/Kav4proxy";
					}else{
						echo "Starting......: ".date("H:i:s")." Kav4proxy tmpfs failed to mount return back to disk\n";
					}
					
				}else{
					echo "Starting......: ".date("H:i:s")." Kav4proxy tmpfs mounted...\n";
					$TempPath="/tmp/Kav4proxy";	
					
				}
			
			}else{
				echo "Starting......: ".date("H:i:s")." Kav4proxy tmpfs is disabled\n";
				if($this->is_tmpfs_mounted()){
					echo "Starting......: ".date("H:i:s")." Kav4proxy unmounting filesystem\n";
					shell_exec("$umount -l /tmp/Kav4proxy");
				}
			}
			
		}
		
		
				if($GLOBALS["AS_ROOT"]){
					if(preg_match("#([0-9])\.([0-9]).([0-9]+)#",  $this->VERSION,$re)){
						$rev=$re[3];
					}
					echo "Starting......: ".date("H:i:s")." Kav4proxy current version is $this->VERSION (revision $rev)\n";
				}
				$sock=new sockets();
				$Kav4ProxyDatabasePath=$sock->GET_INFO("Kav4ProxyDatabasePath");
				if($Kav4ProxyDatabasePath==null){$Kav4ProxyDatabasePath="/home/artica/squid/kav4proxy/bases";}

                $Kav4ProxInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Kav4ProxInterface"));
                if($Kav4ProxInterface==null){$Kav4ProxInterface="lo";}

                $Kav4ProxyMaxChildren=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Kav4ProxyMaxChildren"));
                if($Kav4ProxyMaxChildren==0){$Kav4ProxyMaxChildren=10;}

                $Kav4ProxyIdleChildren=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Kav4ProxyIdleChildren"));
                if($Kav4ProxyIdleChildren==0){$Kav4ProxyIdleChildren=1;}

                $Kav4ProxyMaxReqsPerChild=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Kav4ProxyMaxReqsPerChild"));
                if($Kav4ProxyMaxReqsPerChild==0){$Kav4ProxyMaxReqsPerChild=15000;}

                $Kav4ProxyMaxEnginesPerChild=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Kav4ProxyMaxEnginesPerChild"));
                if($Kav4ProxyMaxEnginesPerChild==0){$Kav4ProxyMaxEnginesPerChild=10;}

                $KavProxyMaxSendDelayTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KavProxyMaxSendDelayTime"));
                if($KavProxyMaxSendDelayTime==0){$KavProxyMaxSendDelayTime=10;}

                $KavProxyMaxConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KavProxyMaxConnections"));
                if($KavProxyMaxConnections==0){$KavProxyMaxConnections=5000;}

        $Kav4ProxyListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Kav4ProxyListenPort"));
        if($Kav4ProxyListenPort==0){$Kav4ProxyListenPort=1344;}


        $KavProxyMaxScanTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KavProxyMaxScanTime"));
        if($KavProxyMaxScanTime==0){$KavProxyMaxScanTime=300;}

        $Kav4ProxyMaxReqLength=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Kav4ProxyMaxReqLength"));
        if($Kav4ProxyMaxReqLength==0){$Kav4ProxyMaxReqLength=5;}

        if(!isset($this->main_array["ProxyAddress"])){$this->main_array["ProxyAddress"]=null;}

                $ListenAddress=null;
        echo "Starting......: ".date("H:i:s")." Kav4proxy buildind defaults...\n";
				$this->Build_defaults();
				$conf[]="[path]\n";
				$conf[]="BasesPath=$Kav4ProxyDatabasePath";
				$conf[]="LicensePath=/var/opt/kaspersky/kav4proxy/licenses";
				$conf[]="TempPath=/tmp";
				$conf[]="KLPluginsPath=/opt/kaspersky/kav4proxy/lib/ppl";
				if($rev>=79){
					if($Kav4ProxyTMPFS==1){
						$conf[]="FastTempPath=/tmp/Kav4proxy";
						@mkdir("/tmp/Kav4proxy",0777);
						@chmod("/tmp/Kav4proxy", 0777);
						@chown("/tmp/Kav4Proxy", "kluser");
						@chgrp("/tmp/Kav4Proxy", "kluser");						
					}
				}
				
				$conf[]="";				
				$conf[]="[options]";
				$conf[]="User=kluser";
				$conf[]="Group=klusers";

				if($Kav4ProxInterface=="lo"){$ListenAddress="127.0.0.1";}
				$unix=new unix();
                $ListenAddress=$unix->InterfaceToIPv4($Kav4ProxInterface);
                if($ListenAddress==null){$ListenAddress="127.0.0.1";}
				 
				
				$conf[]="";
				$conf[]="[locale]";
				$conf[]="DateFormat=%d-%m-%Y";
				$conf[]="TimeFormat=%H:%M:%S";
				$conf[]="";
				$conf[]="[icapserver.network]";
				$conf[]="ListenAddress=$ListenAddress:$Kav4ProxyListenPort";
				$conf[]="Timeout={$this->main_array["Timeout"]}";
				$conf[]="";
				$conf[]="[icapserver.process]";
				$conf[]="MaxChildren=$Kav4ProxyMaxChildren";
				$conf[]="IdleChildren=$Kav4ProxyIdleChildren";
				$conf[]="MaxReqsPerChild=$Kav4ProxyMaxReqsPerChild";
				$conf[]="MaxEnginesPerChild=$Kav4ProxyMaxEnginesPerChild";
				
				$conf[]="";
				$conf[]="[icapserver.protocol]";
				$conf[]="AnswerMode=partial";
				$conf[]="MaxSendDelayTime=$KavProxyMaxSendDelayTime";
				$conf[]="PreviewSize=1024";
				$conf[]="MaxConnections=$KavProxyMaxConnections";
				$conf[]="Allow204=yes";
				$conf[]="SendAVScanResult=false";
				$conf[]="ReqModeServiceUrl=av/reqmod";
				$conf[]="RespModeServiceUrl=av/respmod";

		
				
				
				$conf[]="SendAVScanResult=no";
				$conf[]="HTTPClientIpICAPHeader=X-Client-IP";
				$conf[]="HTTPUserNameICAPHeader=X-Client-Username";				
				$conf[]="";
				$conf[]="[icapserver.path]";
				$conf[]="PidFile=/var/run/kav4proxy/kavicapserver.pid";
				$conf[]="CorePath=";
				$conf[]="[icapserver.groups]";
				$conf[]="Priority=0";
				$conf[]="ClientIP=.*";
				$conf[]="URL=.*";
				
				$conf[]="";
				$conf[]="[icapserver.engine.options]";
				$conf[]="ScanPacked={$this->main_array["ScanPacked"]}";
				$conf[]="ScanArchives={$this->main_array["ScanArchives"]}";
				$conf[]="ScanMailBases={$this->main_array["ScanMailBases"]}";
				$conf[]="ScanMailPlain={$this->main_array["ScanMailPlain"]}";
				$conf[]="Cure={$this->main_array["Cure"]}";
				$conf[]="UseAnalyzer=yes";
				$conf[]="HeuristicLevel=recommended";
				$conf[]="ScanSFXArchives=yes";

				if($rev>=87){
					$Rev88Opts["MaxNestingLevel"]=8;
					$Rev88Opts["DetectVirware"]=1;
					$Rev88Opts["DetectTrojware"]=1;
					$Rev88Opts["DetectMalware"]=1;
					$Rev88Opts["DetectAdware"]=1;
					$Rev88Opts["DetectPornware"]=1;
					$Rev88Opts["DetectRiskware"]=1;
					$Rev88Opts["DetectXfiles"]=0;
					$Rev88Opts["DetectSoftware"]=0;
					while (list ($fieldname, $default) = each ($Rev88Opts) ){
						if($this->main_array["$fieldname"]==null){$this->main_array["$fieldname"]=$default;}
						$conf[]="$fieldname=".$this->main_array["$fieldname"];
					}
				
				}
                $Kav4ProxyMaxReqLength=$Kav4ProxyMaxReqLength*1024;
                $Kav4ProxyMaxReqLength=$Kav4ProxyMaxReqLength*1024;

				$conf[]="MaxScanTime=$KavProxyMaxScanTime";
				$conf[]="";
				$conf[]="[icapserver.filter]";
				$conf[]=$this->ExcludeMimeType();
				$conf[]=$this->ExcludeURL();
				$conf[]="MaxReqLength=$Kav4ProxyMaxReqLength";
				
				$conf[]="";
				$conf[]="[icapserver.actions]";
				$conf[]="InfectedAction=deny";
				$conf[]="SuspiciousAction=deny";
				$conf[]="WarningAction=deny";
				$conf[]="ErrorAction=skip";
				$conf[]="ProtectedAction=skip";
				$conf[]="CorruptedAction=skip";
				$conf[]="CuredAction=skip";
				$conf[]="LicenseErrorAction=skip";
				$conf[]="BasesErrorAction=skip";
                $conf[]="MaxReqLengthAction=skip";
				if($this->main_array["MaxReqLengthAction"]=="check"){$this->main_array["MaxReqLengthAction"]="skip";}
					$conf[]="MaxReqLengthAction={$this->main_array["MaxReqLengthAction"]}";
					$conf[]="PartialResponseAction={$this->main_array["PartialResponseAction"]}";
					$conf[]="PartialRequestAction={$this->main_array["PartialRequestAction"]}";

				
				$conf[]="";
				$conf[]="[icapserver.notify]";
				$conf[]="NotifyTemplateDir=/opt/kaspersky/kav4proxy/share/notify";
				$conf[]="NotifyScript=/opt/kaspersky/kav4proxy/share/examples/notify.sh";
				$conf[]="[icapserver.statistics]";
				$conf[]="CounterStatisticsFile=/var/log/kaspersky/kav4proxy/counter.stats";
				$conf[]="AVStatisticsFile=/var/log/kaspersky/kav4proxy/av.stats";
				$conf[]="AVStatisticsAddress=";
				
				$conf[]="";
				$conf[]="[icapserver.report]";
				$conf[]="ReportFileName=/var/log/kaspersky/kav4proxy/kavicapserver.log";
				$conf[]="Buffered=no";
				$conf[]="ReportLevel=4";
				$conf[]="ShowOk=yes";
				$conf[]="Append=yes";
				
				$conf[]="";
				$conf[]="[updater.path]";
				$conf[]="BackUpPath=/var/opt/kaspersky/kav4proxy/bases.backup";
				$conf[]="AVBasesTestPath=/opt/kaspersky/kav4proxy/lib/bin/avbasestest";
				
				$conf[]="";
				$conf[]="[updater.options]";
				$conf[]="KeepSilent=no";
				$conf[]="UseUpdateServerUrl={$this->main_array["UseUpdateServerUrl"]}";
				$conf[]="UseUpdateServerUrlOnly={$this->main_array["UseUpdateServerUrlOnly"]}";
				$conf[]="PostUpdateCmd=/etc/init.d/kav4proxy reload_avbase";
				$conf[]="RegionSettings={$this->main_array["RegionSettings"]}";
				$conf[]="ConnectTimeout=30";
				$conf[]="ProxyAddress={$this->main_array["ProxyAddress"]}";
				$conf[]="UseProxy={$this->main_array["UseProxy"]}";
				$conf[]="PassiveFtp=yes";
				
				
				$conf[]="";
				$conf[]="[updater.report]";
				$conf[]="ReportFileName=/var/log/kaspersky/kav4proxy/keepup2date.log";
				$conf[]="ReportLevel=4";
				$conf[]="Append=true";
				$conf[]="";
				

				


                echo "Starting......: ".date("H:i:s")." Kav4proxy buildind kav4proxy.conf...\n";
				@file_put_contents("/etc/opt/kaspersky/kav4proxy.conf",implode("\n",$conf));
        $f=array();$percent="%";
        $f[]="#!/bin/sh";
        $f[]="action=\"{$percent}ACTION%\"";
        $f[]="verdict=\"{$percent}VERDICT%\"";
        $f[]="URL=\"{$percent}URL%\"";
        $f[]="IP=\"{$percent}CLIENT_ADDR%\"";
        $f[]="virus=\"{$percent}VIRUS_LIST%\"";
        $f[]="Cured=\"{$percent}CURED_LIST%\"";
        $f[]="suspic=\"{$percent}SUSP_LIST%\"";
        $f[]="warn=\"{$percent}WARN_LIST%\"";
        $f[]="";
        $f[]="";
        $f[]="/usr/bin/php /usr/share/artica-postfix/exec.Kav4ProxyEvents.php \$action \$verdict \$URL \$IP \$virus \$Cured \$suspic \$warn";
        $f[]="";

		@file_put_contents("/opt/kaspersky/kav4proxy/share/examples/notify.sh",@implode("\n",$f));
		@chmod("/opt/kaspersky/kav4proxy/share/examples/notify.sh",0755);
        echo "Starting......: ".date("H:i:s")." Kav4proxy buildind notify.sh done...\n";
	}


	
	
	function LoadTemplates(){
		//%PRODUCT%
		$REBUILD_TEMPLATES=false;
		$objetcs["object_infected"]["TITLE"]="Access Denied Virus found";
		$objetcs["object_infected"]["SUBTTITLE"]="Virus found in this web page:<br>%VERDICT% detected";
		$objetcs["object_infected"]["TR1"]="Virus";
		$objetcs["object_infected"]["TR2"]="%VIRUS_LIST%";
		$objetcs["object_infected"]["TR3"]="Page";
		$objetcs["object_infected"]["TR4"]="<div class=\"break-word\">%URL%</div>";
		
		$objetcs["bases_error"]["TITLE"]="Access Denied Databases issue";
		$objetcs["bases_error"]["SUBTTITLE"]="Antivirus databases were not loaded";
		$objetcs["bases_error"]["TR1"]="Generic error";
		$objetcs["bases_error"]["TR2"]="<div class=\"break-word\">%URL%</div>";

		$objetcs["license_error"]["TITLE"]="Access Denied License error";
		$objetcs["license_error"]["SUBTTITLE"]="License information was not loaded";
		$objetcs["license_error"]["TR1"]="Generic error";
		$objetcs["license_error"]["TR2"]="<div class=\"break-word\">%URL%</div>";
		
		
		$objetcs["object_corrupted"]["TITLE"]="Access Denied corrupted object";
		$objetcs["object_corrupted"]["SUBTTITLE"]="Corrupted file";
		$objetcs["object_corrupted"]["TR1"]="Generic error";
		$objetcs["object_corrupted"]["TR2"]="<div class=\"break-word\">%URL%</div>";			
		
		$objetcs["object_cured"]["TITLE"]="Access Denied Virus found (cured)";
		$objetcs["object_cured"]["SUBTTITLE"]="Virus found in this web page:<br>%VERDICT% detected";
		$objetcs["object_cured"]["TR1"]="Virus";
		$objetcs["object_cured"]["TR2"]="%VIRUS_CURED% were cured";
		$objetcs["object_cured"]["TR3"]="Page";
		$objetcs["object_cured"]["TR4"]="<div class=\"break-word\">%URL%</div>";
		
		
		$objetcs["object_error"]["TITLE"]="Access Denied scanning error";
		$objetcs["object_error"]["SUBTTITLE"]="Antivirus engine error occured";
		$objetcs["object_error"]["TR1"]="Error on file";
		$objetcs["object_error"]["TR2"]="<div class=\"break-word\">%URL%</div>";
		
		$objetcs["object_protected"]["TITLE"]="Access Denied protected object";
		$objetcs["object_protected"]["SUBTTITLE"]="Antivirus engine error occured";
		$objetcs["object_protected"]["TR1"]="Protected file";
		$objetcs["object_protected"]["TR2"]="<div class=\"break-word\">%URL%</div>";
		
		
		$objetcs["object_suspicious"]["TITLE"]="Access Denied Suspicious Virus found";
		$objetcs["object_suspicious"]["SUBTTITLE"]="Virus found in this web page:<br>%VERDICT% detected";
		$objetcs["object_suspicious"]["TR1"]="Virus";
		$objetcs["object_suspicious"]["TR2"]="%SUSP_LIST% were suspicious";
		$objetcs["object_suspicious"]["TR3"]="Page";
		$objetcs["object_suspicious"]["TR4"]="<div class=\"break-word\">%URL%</div>";
		
		
		$objetcs["object_warning"]["TITLE"]="Access Denied Warning!";
		$objetcs["object_warning"]["SUBTTITLE"]="{warning} found in this web page:<br>%VERDICT% detected";
		$objetcs["object_warning"]["TR1"]="Virus";
		$objetcs["object_warning"]["TR2"]="%WARN_LIST% were in warning detection";
		$objetcs["object_warning"]["TR3"]="Page";
		$objetcs["object_warning"]["TR4"]="<div class=\"break-word\">%URL%</div>";
		
		
		while (list ($templateName, $ARRAYTPL) = each ($objetcs) ){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building template $templateName\n";}
			$CONTENT=$this->parseTemplate_build_main($ARRAYTPL);
			if(is_array($CONTENT)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building template $templateName ARRAY RETURNED!!\n";}
			}
			$this->templates_data[$templateName]=$CONTENT;
			$ARRAY_TPLS[$templateName]=$CONTENT;
		}
		
		
		if(!is_file("/etc/artica-postfix/KAV4PROXY_TEMPLATESv2")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Rebuilding template = TRUE\n";}
			$REBUILD_TEMPLATES=true;
		}
					
		if(!$GLOBALS["CORP_LICENSE"]){if($GLOBALS["OUTPUT"]){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} No Artica license, keep defaults\n";}
			return;
		}
			
		$q=new lib_sqlite("/home/artica/SQLITE/kav4proxy.db");
		$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS kav4proxy_templates (`template` TEXT NOT NULL PRIMARY KEY,`content` TEXT NOT NULL)");
		if(!$q->ok){echo $q->mysql_error;}
		$this->templates_data=array();
			foreach ($ARRAY_TPLS as $templateName => $TemplateContent){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Rebuilding template $templateName Default:". strlen($TemplateContent)." bytes\n";}
				if(is_array($TemplateContent)){echo "$templateName: $TemplateContent =  Array!\n";return;}
				if(strlen($TemplateContent)<100){echo "$templateName: $TemplateContent =  No lenght supported!\n";return; }	
					
				$ligne["content"]=null;
				$ligne["template"]=null;
				if(!$REBUILD_TEMPLATES){
					$ligne=$q->mysqli_fetch_array("SELECT `template`,`content` FROM kav4proxy_templates WHERE `template`='$templateName'");
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Rebuilding template $templateName From MySQL:". strlen($ligne["content"])." bytes\n";}
				}

				
				
				if(strlen($ligne["content"])<100){
					$d=$q->sqlite_escape_string2($TemplateContent);
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $templateName: Inserting ". strlen($d)." Into MySQL\n";}
					$q->QUERY_SQL("DELETE FROM kav4proxy_templates WHERE `template`='$templateName'","artica_backup");
					if(!$q->ok){echo $q->mysql_error;}
					$q->QUERY_SQL("INSERT OR IGNORE INTO kav4proxy_templates (`template`,`content`) VALUES ('$templateName','$d')");
					if(!$q->ok){echo $q->mysql_error;}
					$this->templates_data[$templateName]=$TemplateContent;
					continue;
				}
					
					
					
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $templateName: Get template of ". strlen($ligne["content"])." bytes\n";}
				$this->templates_data[$templateName]=$ligne["content"];
			}
			
			@touch("/etc/artica-postfix/KAV4PROXY_TEMPLATESv2");
			
				
	}
	
	function set_template($tpl,$data){
        $q=new lib_sqlite("/home/artica/SQLITE/kav4proxy.db");
		$data=$q->sqlite_escape_string2($data);
		$q->QUERY_SQL("UPDATE kav4proxy_templates SET `content`='$data' WHERE `template`='$tpl'");
		if(!$q->ok){echo $q->mysql_error;return false;}		
		$sock=new sockets();
		$sock->getFrameWork("squid.php?kav4proxy-templates=yes");
		return true;
	}
	
	
	function parseTemplate_build_main($ARRAY){
		$sock=new sockets();
		$sock->BuildTemplatesConfig();
		$FOOTER=null;
		if(!$GLOBALS["CORP_LICENSE"]){
			$FOOTER="
			<table style='width:75%;border-top:1px solid {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};margin-top:15px'>
			<tr><td colspan=2>&nbsp;</td></tr>
			<tr>
			<td width=64px><img src='img/logo-artica-64.png'></td>
			<td style='font-size:14px;padding-left:10px' width=99%>
			You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.<br>
			<i>Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Entreprise Edition License</i>
			</td>
			</tr>
			</table>
			</div>";
		}
	
		
		$f[]="<!DOCTYPE HTML>";
		$f[]="<html>";
		$f[]="<head>";
		$f[]="<title>{$ARRAY["TITLE"]}</title>";
		$f[]="<script type=\"text/javascript\">";
		$f[]="    function checkIfTopMostWindow()";
		$f[]="    {";
		$f[]="        if (window.top != window.self) ";
		$f[]="        {  ";
		$f[]="            document.body.style.opacity    = \"0.0\";";
		$f[]="            document.body.style.background = \"#FFFFFF\";";
		$f[]="        }";
		$f[]="        else";
		$f[]="        {";
		$f[]="            document.body.style.opacity    = \"1.0\";";
		$f[]="            document.body.style.background = \"{$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]}\";";
		$f[]="        } ";
		$f[]="    }";
		$f[]="</script>";
		$f[]="<style type=\"text/css\">";
		$f[]="    body {";
		$f[]="        color:            {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
		$f[]="        background-color: #FFFFFF; ";
		$f[]="        font-family:      {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
		$f[]="        font-weight:      lighter;";
		$f[]="        font-size:        14pt; ";
		$f[]="        ";
		$f[]="        opacity:            0.0;";
		$f[]="        transition:         opacity 2s;";
		$f[]="        -webkit-transition: opacity 2s;";
		$f[]="        -moz-transition:    opacity 2s;";
		$f[]="        -o-transition:      opacity 2s;";
		$f[]="        -ms-transition:     opacity 2s;    ";
		$f[]="    }";
		$f[]="    h1 {";
		$f[]="        font-size: 72pt; ";
		$f[]="        margin-bottom: 0; ";
		$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
		$f[]="        margin-top: 0 ;";
		$f[]="    }    ";
		$f[]=".bad{ font-size: 110px; float:left; margin-right:30px; }";
		$f[]=".bad:before{ content: \"\\2639\";}";
		$f[]="    h2 {";
		$f[]="        font-size: 22pt; ";
		$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
		$f[]="        font-weight: lighter;";
		$f[]="    }   ";
		$f[]="    h3 {";
		$f[]="        font-size: 18pt; ";
		$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
		$f[]="        font-weight: lighter;";
		$f[]="        margin-bottom: 0 ;";
		$f[]="    }   ";
		$f[]="    #wrapper {";
		$f[]="        width: 700px ;";
		$f[]="        margin-left: auto ;";
		$f[]="        margin-right: auto ;";
		$f[]="    }    ";
		$f[]="    #info {";
		$f[]="        width: 600px ;";
		$f[]="        margin-left: auto ;";
		$f[]="        margin-right: auto ;";
		$f[]="    }    ";
		$f[]="    td.info_title {    ";
		$f[]="        text-align: right;";
		$f[]="        font-size:  12pt;  ";
		$f[]="        min-width: 100px;";
		$f[]="    }";
		$f[]="    td.info_content {";
		$f[]="        text-align: left;";
		$f[]="        padding-left: 10pt ;";
		$f[]="        font-size:  12pt;  ";
		$f[]="    }";
		$f[]="    .break-word {";
		$f[]="        width: 500px;";
		$f[]="        word-wrap: break-word;";
		$f[]="    }    ";
		$f[]="    a {";
		$f[]="        text-decoration: underline;";
		$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
		$f[]="    }";
		$f[]="    a:visited{";
		$f[]="        text-decoration: underline;";
		$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
		$f[]="    }";
		$f[]="</style>";
		$f[]="</head>";
		$f[]="<body onLoad='checkIfTopMostWindow()'>";
		$f[]="<div id=\"wrapper\">";
		$f[]="    <h1 class=bad></h1>";
		$f[]="    <h2>{$ARRAY["SUBTTITLE"]}</h2>    ";
		
		$f[]="    ";
		$f[]="    <div id=\"info\">";
		$f[]="    <table width='100%'>";

		if(isset($ARRAY["TR1"])){
			$f[]="        <tr><td class=\"info_title\">{$ARRAY["TR1"]}</td><td class=\"info_content\">{$ARRAY["TR2"]}</td></tr>";
		}
		
		if(isset($ARRAY["TR3"])){
			$f[]="        <tr><td class=\"info_title\">{$ARRAY["TR3"]}</td><td class=\"info_content\">{$ARRAY["TR4"]}</td></tr>";
		}		
		
		if($GLOBALS["UfdbGuardHTTP"]["NoVersion"]==0){
			$f[]="        <tr><td class=\"info_title\">Artica</td><td class=\"info_content\">Artica Proxy, version {$GLOBALS["ARTICA_VERSION"]}</td></tr>";
			$f[]="        <tr><td class=\"info_title\">Scanner</td><td class=\"info_content\">%PRODUCT%</td></tr>";
		}
		$f[]="    </table>";
		$f[]="    </div>    $FOOTER";
		$f[]="</div>";
		$f[]="</body>";
		$f[]="<!-- ";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		$f[]="-->";
		$f[]="</html>";
		
		return @implode("\n", $f);
	}
	 
	
}

?>