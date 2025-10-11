<?php
include_once(dirname(__FILE__)."/class.compile.ufdbguard.inc");

class squid_url_rewrite{
	private $SquidClientParams=array();
	private $SquidUrgency=0;
	private $APP_UFDBGUARD_INSTALLED=false;
	private $EnableUfdbGuard=0;
	private $ufdbgclient_path=null;
	private $SquidEnforceRules=0;
	private $SquidDisableAllFilters=0;
	private $LOGS=array();
	function __construct(){
		$users=new usersMenus();
		$sock=new sockets();
		$this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
		$this->SquidDisableAllFilters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters"));
		$this->SquidClientParams=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
		$this->SquidEnforceRules=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnforceRules"));
		$this->APP_UFDBGUARD_INSTALLED=$users->APP_UFDBGUARD_INSTALLED;
		$this->ufdbgclient_path=$users->ufdbgclient_path;
		$this->EnableUfdbGuard=intval($sock->EnableUfdbGuard());
		if(!isset($this->SquidClientParams["url_rewrite_children"])){$this->SquidClientParams["url_rewrite_children"]=10;}
		if(!isset($this->SquidClientParams["url_rewrite_startup"])){$this->SquidClientParams["url_rewrite_startup"]=1;}
		if(!isset($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}
		if(!isset($this->SquidClientParams["url_rewrite_concurrency"])){$this->SquidClientParams["url_rewrite_concurrency"]=4;}
		if(!isset($this->SquidClientParams["url_rewrite_bypass"])){$this->SquidClientParams["url_rewrite_bypass"]=1;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_children"])){$this->SquidClientParams["url_rewrite_children"]=10;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_startup"])){$this->SquidClientParams["url_rewrite_startup"]=1;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_concurrency"])){$this->SquidClientParams["url_rewrite_concurrency"]=4;}
		if(!isset($this->SquidClientParams["url_rewrite_children"])){$this->SquidClientParams["url_rewrite_children"]=10;}
		if(!isset($this->SquidClientParams["url_rewrite_startup"])){$this->SquidClientParams["url_rewrite_startup"]=2;}
		if(!isset($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}
		if(!isset($this->SquidClientParams["url_rewrite_concurrency"])){$this->SquidClientParams["url_rewrite_concurrency"]=0;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_children"])){$this->SquidClientParams["url_rewrite_children"]=10;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_startup"])){$this->SquidClientParams["url_rewrite_startup"]=2;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_concurrency"])){$this->SquidClientParams["url_rewrite_concurrency"]=4;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_bypass"])){$this->SquidClientParams["url_rewrite_bypass"]=1;}
		if($this->SquidClientParams["url_rewrite_concurrency"]<2){$this->SquidClientParams["url_rewrite_concurrency"]=2;}

		
		
	}



    public function build_native(){
        $helper="/usr/bin/ufdbgclient";
        $UfdbGuardIpAddr="127.0.0.1";


        $RedirectorsArray=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

        if(!isset($RedirectorsArray["url_rewrite_reset_sp723"])){
            $RedirectorsArray["url_rewrite_children"]=10;
            $RedirectorsArray["url_rewrite_startup"]=5;
            $RedirectorsArray["url_rewrite_idle"]=1;
            $RedirectorsArray["url_rewrite_concurrency"]=4;
        }
        if(!isset($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=10;}
        if(!isset($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
        if(!isset($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
        if(!isset($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=4;}

        if($RedirectorsArray["url_rewrite_concurrency"]>32){$RedirectorsArray["url_rewrite_concurrency"]=32;}
        $concurrency=$RedirectorsArray["url_rewrite_concurrency"];

        $UfdbGuardInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardInterface"));
        $UfdbGuardPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardPort"));if($UfdbGuardPort==0){$UfdbGuardPort=3977;}
        $UfdbgClientDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbgClientDebug"));
        $RemoteUfdbguardAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteUfdbguardAddr"));
        $RemoteUfdbguardPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteUfdbguardPort"));
        $UseRemoteUfdbguardService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));
        $UfdbgclientSockTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbgclientSockTimeOut"));
        if($UfdbgclientSockTimeOut==0){$UfdbgclientSockTimeOut=21;}
        $moinsd=null;
        if($UfdbgClientDebug==1){
            $moinsd=" -d";
        }
        if($UseRemoteUfdbguardService==0) {
            if ($UfdbGuardInterface <> null) {
                $unix = new unix();
                $UfdbGuardIpAddr = $unix->InterfaceToIPv4($UfdbGuardInterface);
            }
            if ($UfdbGuardIpAddr == null) { $UfdbGuardIpAddr = "127.0.0.1"; }
        }
        if($UseRemoteUfdbguardService==1) {
            $UfdbGuardIpAddr=$RemoteUfdbguardAddr;
            $UfdbGuardPort=$RemoteUfdbguardPort;
        }


        if($concurrency>32){$concurrency=32;}
        if($concurrency<2){$concurrency=2;}


        $Childs[]="url_rewrite_children ".$RedirectorsArray["url_rewrite_children"];
        $Childs[]="startup=" . $RedirectorsArray["url_rewrite_startup"];
        $Childs[]="idle=". $RedirectorsArray["url_rewrite_idle"];
        $Childs[]="concurrency=$concurrency";
        $Childs[]="queue-size=64";


        $f[] = "url_rewrite_program $helper$moinsd -t $UfdbgclientSockTimeOut -S $UfdbGuardIpAddr -p $UfdbGuardPort -m $concurrency -l /var/log/squid";
        $f[] = "url_rewrite_bypass on";
        $f[]='url_rewrite_extras "%>a/%>A %un %>rm bump_mode=%ssl::bump_mode sni=%ssl::>sni referer=%{Referer}>h"';
        $f[]=@implode(" ",$Childs);
        $f[]="";
        return @implode("\n",$f);
    }
	
	public function build($ForceEnabled=false){
        $go_shield_connector_src       = "/usr/share/artica-postfix/bin/go-shield/client/external_acl_firts/bin/go-shield-connector";
        $UfdbGuardDisabledTemp=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardDisabledTemp"));
        $UfdbGuardDisabledRedirectors=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardDisabledRedirectors"));
        $SquidUFDBUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUFDBUrgency"));
        $SquidDisableAllFilters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters"));
        $SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
        $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
        $EnableITChart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableITChart"));
        $GoShieldConnectorEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldConnectorEmergency"));
        $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
        if($ForceEnabled==1){$EnableUfdbGuard=1;}
		$f[]="#Emergency                    = $SquidUrgency";
		$f[]="#SquidDisableAllFilters       = $SquidDisableAllFilters";
        $f[]="#Emergency (Web filtering)    = $SquidUFDBUrgency";
        $f[]="#UfdbGuardDisabledRedirectors = $UfdbGuardDisabledRedirectors";
        $f[]="#UfdbGuardDisabledTemp        = $UfdbGuardDisabledTemp";
        $f[]="#EnableUfdbGuard              = $EnableUfdbGuard";
        $f[]="#EnableITChart                = $EnableITChart";
        $f[]="#EnableSquidMicroHotSpot     = $EnableSquidMicroHotSpot";

        if($EnableITChart==1){
            $UfdbGuardDisabledRedirectors=0;
            $EnableUfdbGuard=1;
            $UfdbGuardDisabledTemp=0;
        }
        if($EnableITChart==1){
            $UfdbGuardDisabledRedirectors=0;
            $EnableUfdbGuard=1;
            $UfdbGuardDisabledTemp=0;
        }

        if($EnableSquidMicroHotSpot==1){
            $UfdbGuardDisabledRedirectors=0;
            $EnableUfdbGuard=1;
            $UfdbGuardDisabledTemp=0;
        }

        if($UfdbGuardDisabledRedirectors==1){
            @file_put_contents("/etc/squid3/ufdbgclient.conf", "# UfdbGuardDisabledRedirectors =1");
            return true;
        }

        if($EnableUfdbGuard==0){
            $f[]="#WebFiltering is disabled\n";
            @file_put_contents("/etc/squid3/ufdbgclient.conf", @implode("\n", $f));
            return true;
        }


		if($UfdbGuardDisabledTemp==1){
			@file_put_contents("/etc/squid3/ufdbgclient.conf", "# UfdbGuardDisabledTemp =1");
			return true;
		}
		
		if($SquidDisableAllFilters==1){
			@file_put_contents("/etc/squid3/ufdbgclient.conf","# SquidDisableAllFilters =1");
			return true;
		}
		
		if($SquidUrgency==1){
			@file_put_contents("/etc/squid3/ufdbgclient.conf", "# Proxy is in Emergency mode");
			return true;
		}


        if($GoShieldConnectorEmergency==1){
            $f[]="#Go Shield Connector is in Emergency = True";
            @file_put_contents("/etc/squid3/ufdbgclient.conf", @implode("\n", $f));
            return true;
        }


        $f[]="#Build Native                = FALSE";
        $helper="/lib/squid3/go-shield-connector";
        $this->check_go_shield_connector_updates();
        if(!is_file($helper)){
            if(!is_file($go_shield_connector_src)){
                $f[]="#Go Shield Connector is not installed !!";
                @file_put_contents("/etc/squid3/ufdbgclient.conf", @implode("\n", $f));
                return true;
            }
        }


		$f[]= "url_rewrite_program $helper";
		$f[] = "url_rewrite_bypass on";
        $f[]=$this->url_rewrite_children();
        $f[]='url_rewrite_extras "%>a/%>A %un %>rm myip=%la myport=%lp mac=%>eui sni=%ssl::>sni referer=%{Referer}>h tag=%note bump_mode=%ssl::bump_mode forwardedfor=%>ha{X-Forwarded-For} domain=%DST webfilter-acl=%ACL"';
        $f[]="";
		@file_put_contents("/etc/squid3/ufdbgclient.conf", @implode("\n", $f));
        return true;
		
	}
    private function check_go_shield_connector_updates():bool{
        $Root="/usr/share/artica-postfix/bin/go-shield";
        $go_shield_connector_src       = "$Root/client/external_acl_first/bin/go-shield-connector";
        $go_shield_connector_dst       = "/lib/squid3/go-shield-connector";
        if(!is_dir("/lib/squid3")){@mkdir("/lib/squid3",0755,true);}

        if(is_file($go_shield_connector_src)) {
            $md5src=md5_file($go_shield_connector_src);
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_CONNECTOR_SRC",$md5src );
            if (!is_file($go_shield_connector_dst)) {
                @copy($go_shield_connector_src, $go_shield_connector_dst);
            }
        }

        if (!is_file($go_shield_connector_dst)) {return false;}
        @chmod($go_shield_connector_dst,0755);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_CONNECTOR_DST",md5_file($go_shield_connector_dst));
        return true;
    }

    private function url_rewrite_children($mttl=0,$negttl=0,$cached_items=0):string{
        $external_concurrency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("external_concurrency"));
        if($external_concurrency==0){$external_concurrency=100;}
        $SquidClientParams=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_DAEMONS"));
        if(!isset($SquidClientParams["ksrn_param_daemon_children"])){$SquidClientParams["ksrn_param_daemon_children"]=10;}
        if(!isset($SquidClientParams["ksrn_param_daemon_startup"])){$SquidClientParams["ksrn_param_daemon_startup"]=2;}
        if(!isset($SquidClientParams["ksrn_param_daemon_idle"])){$SquidClientParams["ksrn_param_daemon_idle"]=1;}
        if(!isset($SquidClientParams["ksrn_param_daemon_concurrency"])){$SquidClientParams["ksrn_param_daemon_concurrency"]=500;}
        if($SquidClientParams["ksrn_param_daemon_concurrency"]<100){$SquidClientParams["ksrn_param_daemon_concurrency"]=100;}
        if(!isset($SquidClientParams["ksrn_param_daemon_negative_ttl"])){$SquidClientParams["ksrn_param_daemon_negative_ttl"]=360;}
        if(!isset($SquidClientParams["ksrn_param_daemon_positive_ttl"])){$SquidClientParams["ksrn_param_daemon_positive_ttl"]=360;}
        if(!is_numeric($SquidClientParams["ksrn_param_daemon_children"])){$SquidClientParams["ksrn_param_daemon_children"]=10;}
        if(!is_numeric($SquidClientParams["ksrn_param_daemon_startup"])){$SquidClientParams["ksrn_param_daemon_startup"]=2;}
        if(!is_numeric($SquidClientParams["ksrn_param_daemon_idle"])){$SquidClientParams["ksrn_param_daemon_idle"]=1;}
        if(!is_numeric($SquidClientParams["ksrn_param_daemon_negative_ttl"])){$SquidClientParams["ksrn_param_daemon_negative_ttl"]=360;}
        if(!is_numeric($SquidClientParams["ksrn_param_daemon_positive_ttl"])){$SquidClientParams["ksrn_param_daemon_positive_ttl"]=360;}

        if($negttl>0){
            $SquidClientParams["ksrn_param_daemon_negative_ttl"]=$negttl;
        }
        if($mttl>0){
            $SquidClientParams["ksrn_param_daemon_positive_ttl"]=$mttl;
        }
        $concurrency=$SquidClientParams["ksrn_param_daemon_concurrency"];
        $Childs[]="url_rewrite_children ".$SquidClientParams["ksrn_param_daemon_children"];
        $Childs[]="startup=" . $SquidClientParams["ksrn_param_daemon_startup"];
        $Childs[]="idle=". $SquidClientParams["ksrn_param_daemon_idle"];;
        $Childs[]="concurrency=$concurrency";
        if($cached_items>0){ $Childs[]="queue-size=$cached_items";}

        return @implode(" ",$Childs);

    }

    public function url_external_children():string{
        $external_concurrency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("external_concurrency"));
        if($external_concurrency==0){$external_concurrency=100;}
        $SquidClientParams=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_DAEMONS"));
        if(!isset($SquidClientParams["ksrn_param_daemon_children"])){$SquidClientParams["ksrn_param_daemon_children"]=10;}
        if(!isset($SquidClientParams["ksrn_param_daemon_startup"])){$SquidClientParams["ksrn_param_daemon_startup"]=2;}
        if(!isset($SquidClientParams["ksrn_param_daemon_idle"])){$SquidClientParams["ksrn_param_daemon_idle"]=1;}

        if(!isset($SquidClientParams["ksrn_param_daemon_concurrency"])){$SquidClientParams["ksrn_param_daemon_concurrency"]=500;}

        if($SquidClientParams["ksrn_param_daemon_concurrency"]<100){$SquidClientParams["ksrn_param_daemon_concurrency"]=100;}



        if(!isset($SquidClientParams["ksrn_param_daemon_negative_ttl"])){$SquidClientParams["ksrn_param_daemon_negative_ttl"]=360;}
        if(!isset($SquidClientParams["ksrn_param_daemon_positive_ttl"])){$SquidClientParams["ksrn_param_daemon_positive_ttl"]=360;}

        if(!is_numeric($SquidClientParams["ksrn_param_daemon_children"])){$SquidClientParams["ksrn_param_daemon_children"]=10;}
        if(!is_numeric($SquidClientParams["ksrn_param_daemon_startup"])){$SquidClientParams["ksrn_param_daemon_startup"]=2;}
        if(!is_numeric($SquidClientParams["ksrn_param_daemon_idle"])){$SquidClientParams["ksrn_param_daemon_idle"]=1;}

        if(!is_numeric($SquidClientParams["ksrn_param_daemon_negative_ttl"])){$SquidClientParams["ksrn_param_daemon_negative_ttl"]=360;}
        if(!is_numeric($SquidClientParams["ksrn_param_daemon_positive_ttl"])){$SquidClientParams["ksrn_param_daemon_positive_ttl"]=360;}

        $Childs[]="ttl=".$SquidClientParams["ksrn_param_daemon_positive_ttl"];
        $Childs[]="negative_ttl=".$SquidClientParams["ksrn_param_daemon_negative_ttl"];
        $Childs[]="children-startup=".$SquidClientParams["ksrn_param_daemon_startup"];
        $Childs[]="children-idle=".$SquidClientParams["ksrn_param_daemon_idle"];
        $Childs[]="children-max=".$SquidClientParams["ksrn_param_daemon_children"];
        $Childs[]="concurrency=".$SquidClientParams["ksrn_param_daemon_concurrency"];
        $Childs[]="cache=2621440";

        return @implode(" ",$Childs);

    }

	



	
	
}