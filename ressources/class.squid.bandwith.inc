<?php
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.cron.inc');
include_once(dirname(__FILE__).'/class.tcpip.inc');
class squid_bandwith_builder{
	var $rules_array=array();
	var $delay_pools=array();
	var $delay_class=array();
	var $delay_parameters=array();
	var $rules_name=array();
	var $acls=array();
	var $delay_access=array();
	var $rules_explain=array();
	var $delay_is_acls=array();
	var $additionalsAcls=array();
	var $array_squid_quotas=array();
	private $array_squid_qotas_lines=array();
	private $ERRORS=array();
	private $ok=false;
	private $SquidDisableAllFilters=0;
	private $SquidUrgency=0;
	private $EnableSquidQuotasBandwidth=0;
	private $InfluxSyslogRemote=0;
	private $InfluxUseRemote=0;
	private $EnableKerbAuth=0;
	private $EnableMacAddressFilter=0;
	private $LockActiveDirectoryToKerberos=0;
    private $ActiveDirectoryError=null;
    private $isSquid5       = false;



	var $AS_ROOT=false;
	
	function __construct($noload=false){
		$this->AS_ROOT=$GLOBALS["AS_ROOT"];
        $SquidVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
        if(preg_match("#^(5|6|7)\.#",$SquidVersion)){$this->isSquid5=true;}



        if(!$noload){

			$this->SquidDisableAllFilters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters"));
			$this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
			$this->EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
			$this->EnableMacAddressFilter=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMacAddressFilter"));
            $this->SQUIDACLsEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsEnabled"));
            $this->LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
            $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
            if($HaClusterClient==1){$this->LockActiveDirectoryToKerberos=1;}

            if( $this->LockActiveDirectoryToKerberos==1) {
                if (!is_file("/etc/squid3/krb5.keytab")) {
                    $this->LockActiveDirectoryToKerberos = 0;
                    $this->ActiveDirectoryError="# /etc/squid3/krb5.keytab missing";
                }
                if(is_file("/etc/squid3/krb5.keytab")){
                    $this->LockActiveDirectoryToKerberos = 1;
                    $this->ActiveDirectoryError=null;
                }
            }

            if($this->LockActiveDirectoryToKerberos==1){$this->EnableKerbAuth=1;}
			

			$this->InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
			if($this->InfluxUseRemote==1){
				$this->InfluxSyslogRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxSyslogRemote"));
				if($this->InfluxSyslogRemote==1){$this->EnableSquidQuotasBandwidth=0;}
			}
			

			
			if($this->SquidUrgency==1){return;}
			if($this->SquidDisableAllFilters==1){return;}
			
			$this->loadRules();
		}
		
		
	}
	
	private function build_progress_bandwidth($text,$pourc){
		$echotext=$text;
		$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
		echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
		$cachefile="/usr/share/artica-postfix/ressources/logs/squid.bandwww.progress";
		$array["POURC"]=$pourc;
		$array["TEXT"]=$text;
		@file_put_contents($cachefile, serialize($array));
		@chmod($cachefile,0755);
	}
	
	private function loadrules(){
		if(function_exists("build_progress_bandwidth")){build_progress_bandwidth("{limit_rate} {reconfigure}",51);}


        if($this->SQUIDACLsEnabled==0){
            $conf[]="# WAF module is not enabled, SKIP bandwidth rules";
            @file_put_contents("/etc/squid3/acls_bandwidth.conf", @implode("\n", $conf));
            return;
        }

        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql="SELECT ID FROM squid_http_bandwidth_acls WHERE enabled=1 ORDER BY zorder";
        $results = $q->QUERY_SQL($sql);
        if(count($results)==0){
            $conf[]="# No Bandwidth Rule, SKIP";
            @file_put_contents("/etc/squid3/acls_bandwidth.conf", @implode("\n", $conf));
            return;
        }
        $c=1;
        foreach($results as $index=>$ligne) {
            $q->QUERY_SQL("UPDATE squid_http_bandwidth_acls SET delay_pool_number=$c WHERE `ID`={$ligne["ID"]}");
            if(!$q->ok){echo $q->mysql_error_html(true);return;}
            $c++;
        }
        if(function_exists("build_progress_bandwidth")){build_progress_bandwidth("{limit_rate} {reconfigure}",55);}

        $sql="SELECT * FROM squid_http_bandwidth_acls WHERE enabled=1 ORDER BY zorder";
        $results = $q->QUERY_SQL($sql);

        $conf[]="# ". $this->ActiveDirectoryError;
        $conf[]="# Delay Pools, initialize";
        $conf[]="delay_pool_uses_indirect_client on";
        $conf[]="delay_pools ".count($results);
        $conf[]="delay_initial_bucket_level 90";
        $squid_acls_groups=new squid_acls_groups();
        $conf[]="";
        $conf[]="# Add a delay class for the Artica websites (SP 271 )";
        $conf[]="# delay_access 0 allow ToArticaWWW";
        $conf[]="# delay_access 0 deny all";

        foreach($results as $index=>$ligne) {
            $rulename=$ligne["rulename"];
            $limit_network_final=null;
            $limit_client_final=null;
            $delay_pool_number=$ligne["delay_pool_number"];
            $limit_network=intval($ligne["limit_network"]);
            $network_max_size=intval($ligne["network_max_size"]);
            $network_bandwidth=intval($ligne["network_bandwidth"]);
            $limit_client=intval($ligne["limit_client"]);
            $client_maxsize=intval($ligne["client_maxsize"]);
            $client_bandwidth=intval($ligne["client_bandwidth"]);
            $delay_pool_class=1;
            // Calcultation = (64/8)*1000

            $conf[]="# $rulename: network_max_size = $network_max_size | network_bandwidth = $network_bandwidth";
            $conf[]="# $rulename: Limit client = $limit_client client_bandwidth = $client_bandwidth";

            if($limit_network==0){
                $limit_network_final="none";
            }else{
                if($network_bandwidth>0) {
                    $network_bandwidth = $network_bandwidth * 1024;
                    $limit_network_final = "$network_bandwidth/$network_bandwidth";
                }else{
                    $limit_network_final="none";
                }
            }

            if($limit_client==0){
                $limit_client_final="none";
            }else{
                $delay_pool_class=3;
                if($client_bandwidth>0) {
                    $client_bandwidth = $client_bandwidth * 1024;
                    $client_bandwidth = round($client_bandwidth);
                    $limit_client_final = "$client_bandwidth/$client_bandwidth";
                }else{
                    $limit_client_final="none";
                }
            }



            $conf[]="";
            $aclid=$ligne["ID"];
            $acls_array=$squid_acls_groups->buildacls_bytype_items($aclid,false,"squid_http_bandwidth_link");
            $conf[]="#-- [$aclid] $rulename ". count($acls_array). " items--------------------------------";

            $conf[]="delay_class $delay_pool_number $delay_pool_class";
            if($delay_pool_class==3) {
                $conf[] = "delay_parameters $delay_pool_number\t$limit_network_final\tnone\t$limit_client_final";
            }
            if($delay_pool_class==1) {
                $conf[] = "delay_parameters $delay_pool_number\t$limit_network_final\t$limit_client_final";
            }
            if(count($acls_array)==0){
                $conf[]="delay_access $delay_pool_number deny all";
                continue;
            }

            $annotate_transaction=null;
            if($this->isSquid5) {
                $annotate_transaction = " AnnotateBand{$aclid}";
                $conf[] = "acl $annotate_transaction annotate_transaction bandwidth=$aclid";
            }




            $conf[]="delay_access $delay_pool_number allow ".@implode(" ",$acls_array).$annotate_transaction;
            $conf[]="delay_access $delay_pool_number deny all";

        }


        if(function_exists("build_progress_bandwidth")){build_progress_bandwidth("{limit_rate} {reconfigure}",60);}
        @file_put_contents("/etc/squid3/acls_bandwidth.conf", @implode("\n", $conf));
        return;

		

	}
	

	
	public function compile(){
        $this->loadrules();
        return true;
	}

	
	
	
}

?>