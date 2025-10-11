<?php
include_once(dirname(__FILE__)."/class.products-ip-ranges.inc");
include_once(dirname(__FILE__)."/class.firehole.rules.inc");
include_once(dirname(__FILE__)."/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/class.webconsole.params.inc");
include_once(dirname(__FILE__)."/class.sqlite.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
class firehol{
    private $DEBUG_COMPILE=false;
    private $EnablenDPI=0;
    private $dump_object_ev = array();
	public $services_array;
	private $services_available=array();
	private $InterfaceSet=array();
	private $ip_forward=0;

	private $ipset_installed=false;
	private $BUILD_INTERFACE_SERVER_LOGS=array();
	private $EnableDHCPServer=0;
	private $EnableOpenVPNServer=0;
	private $FireHoleLogAllEvents=0;
	private $DHCPNic=null;
	private $EnableOPenVPNServerMode=0;
	private $OpenVPNTunInterface=null;
	private $OpenVPNProto="udp";
	private $OpenVPnPort=0;
	private $OpenVPNeth=null;
	private $OpenVPNForwardEth=null;
	private $TrustedNetworks=array();
	private $POSTFIX=false;
	private $POSTFIX_INTERFACES=array();
	private $IPSETS=array();
    private $ADMIN_RULES=array();
    private $TFILTER=array();
    private $SYSCTL=array();
    private $TFILERS_ETH=array();
    private $SNAT_FINAL=array();

	public function __construct(){
		$EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
        $this->FireHoleLogAllEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleLogAllEvents"));
        $this->EnablenDPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablenDPI"));
		if($EnablePostfix==1){$this->POSTFIX=true;}
		$this->fillServices();
		$this->SMTP_INTERFACES();
		$this->LoadLocalDHCP();
		$this->LoadOPenVPN();



	}

	private function LoadLocalDHCP(){
		$this->EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
		$EnableArticaAsGateway=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway"));
		$this->FireHoleLogAllEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleLogAllEvents"));
		$ini=new Bs_IniHandler();
		$ini->loadString($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaOpenVPNSettings"));
		if(intval($ini->_params["GLOBAL"]["ENABLE_SERVER"])==1){$this->EnableOpenVPNServer=1;}

		if($this->EnableDHCPServer==0){return;}
		include_once(dirname(__FILE__)."/class.dhcpd.inc");
		$dhcp=new dhcpd();
		$this->DHCPNic=$dhcp->listen_nic;
		if($EnableArticaAsGateway==1){$this->ip_forward=1;}

	}





	public function LoadOPenVPN(){

		$ini=new Bs_IniHandler();
		$ini->loadString(@file_get_contents('/etc/artica-postfix/settings/Daemons/ArticaOpenVPNSettings'));
		$this->EnableOPenVPNServerMode=intval($ini->_params["GLOBAL"]["ENABLE_SERVER"]);

		if($this->EnableOPenVPNServerMode==0){return;}
		$this->OpenVPNTunInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNTunInterface");
		$this->OpenVPNProto=$ini->_params["GLOBAL"]["LISTEN_PROTO"];
		$this->OpenVPnPort=intval($ini->_params["GLOBAL"]["LISTEN_PORT"]);
		$this->OpenVPNeth=$ini->_params["GLOBAL"]["LOCAL_BIND"];
		$this->OpenVPNForwardEth=$ini->_params["GLOBAL"]["IPTABLES_ETH"];

		if($this->OpenVPNProto==null){$this->OpenVPNProto="udp";}
		if($this->OpenVPNTunInterface==null){$this->OpenVPNTunInterface="tun0";}
		if($this->OpenVPNeth==null){$this->OpenVPNeth="eth0";}
		if($this->OpenVPnPort==null){$this->OpenVPnPort=1194;}
		if($this->OpenVPNForwardEth<>null){$this->ip_forward=1;}


	}
    private function link_balancer(){

	    $EnableLinkBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));
	    if($EnableLinkBalancer==0){
            $CONF[]="# Link Balancer feature is disabled";
            return @implode("\n",$CONF);
        }
	    $unix=new unix();
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $results=$q->QUERY_SQL("SELECT * FROM link_balance WHERE enabled=1");
        $CONFA=array();
        $c=0;
        foreach ($results as $index=>$ligne){
            $Interface=$ligne["Interface"];
            $custom=null;
            if(!$unix->is_interface_available($Interface)){
                $CONF[]="# $Interface is not available";
                continue;
            }

            $nic=new system_nic($Interface);
            if($nic->enabled==0){
                $CONF[]="# $Interface is disabled";
                continue;
            }

            $probability=$ligne["probability"];
            if($probability>0){
                $probability=$probability/100;
                $probability=str_replace(",",".",$probability);
                $custom=" custom \"-m statistic --mode random --probability $probability\"";
            }


            $c++;
            $CONFA[]="connmark4 0x{$ligne["mark"]} interface $Interface{$custom}";


        }

        if(count($CONF)==1){
            $CONF[]="# Only one interface, disable connmark";
            return @implode("\n",$CONF);
        }
        $fw=new firehol_rules();


        $returned[]=@implode("\n",$CONF);
        $returned[]=@implode("\n",$CONFA);
        $returned[]=$fw->link_balancer();


        return @implode("\n",$returned);

    }

    public function group_srcip($groupname,$dir="src"){
	    return "-m set --match-set $groupname $dir";
    }




	public function build(){
			$this->CheckDirs();
			$unix=new unix();
			if(!is_file("/usr/sbin/firewall-builder.sh")){@touch("/usr/sbin/firewall-builder.sh");}
			patch_firewall_tables();
            $EnableArticaAsGateway          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway"));
            $SQUIDEnable                    =intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
            $iptables                       = $unix->find_program("iptables");
            $ipset                          = $unix->find_program("ipset");


			$this->IPSET_ALL_IPS_LIST();
            $BUILD_INTERFACES=$this->BUILD_INTERFACES();
            $build_routers=$this->build_routers();


			$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
			if(!$q->TABLE_EXISTS("firehol_services")){
				squid_admin_mysql(1, "Aborting firewall configuration firehol_services not exists", $q->mysql_error,__FILE__,__LINE__);
				return;
			}
            $CONF[]="#!/bin/sh";
            $CONF[]="# Builded on ".date("Y-m-d H:i:s");



            $sql="SELECT ID FROM iptables_main WHERE enabled=1 ORDER BY zOrder";
            $results=$q->QUERY_SQL($sql);
            foreach ($results as $index=>$ligne){
                $this->ADMIN_RULES[]=$this->build_rule($ligne["ID"]);
            }


            $CONF[]="$ipset create crowdsec-blacklists hash:ip timeout 0 maxelem 150000 >/dev/null 2>&1 || true";
            $CONF[]="$ipset create crowdsec6-blacklists hash:ip timeout 0 family inet6 maxelem 150000  >/dev/null 2>&1 || true";
            $CONF[]="#ipv4 ipset create MyIPs hash:ip";
            $CONF[]="#ipv4 ipset addfile MyIPs ips MyIPs";
            $CONF[]="#ipv4 ipset create SMTPDeny hash:ip";
            $CONF[]="#ipv4 ipset create SMTPDenyCDIR hash:net";
            $CONF[]="#ipv4 ipset addfile SMTPDeny \"/home/artica/firewall/SMTPDeny.txt\"";
            $CONF[]="#ipv4 ipset addfile SMTPDenyCDIR \"/home/artica/firewall/SMTPDenyCDIR.txt\"";
			if(!is_dir("/home/artica/firewall")){@mkdir("/home/artica/firewall");}
            if(!is_file("/home/artica/firewall/trustednet.txt")){@touch("/home/artica/firewall/trustednet.txt");}
			if(!is_file("/home/artica/firewall/SMTPDenyCDIR.txt")){@touch("/home/artica/firewall/SMTPDenyCDIR.txt");}
			if(!is_file("/home/artica/firewall/SMTPDeny.txt")){@touch("/home/artica/firewall/SMTPDeny.txt");}





        $CONF[]="# Creating chain 'in_crowdsec' ";
        $CONF[]="/usr/sbin/artica-phpfpm-service -crowdsec-firewall-check";


        $CONF[]="$iptables -t filter -A INPUT -j in_ALL || true";

        $CONF[]="# Creating chain 'in_ALL' under 'INPUT' in table 'filter'";
        $CONF[]="$iptables -t filter -N in_ALL || true";
        $CONF[]="$iptables -t filter -A INPUT -j in_ALL || true";
        $CONF[]="";
        $CONF[]="# Creating chain 'out_ALL' under 'OUTPUT' in table 'filter'";
        $CONF[]="$iptables -t filter -N out_ALL || true";
        $CONF[]="$iptables -t filter -A OUTPUT -j out_ALL || true";
        $CONF[]="";

        foreach ($this->TFILTER as $line) {
            $CONF[]=$line;
        }

        $CONF[]="#\tBuild Smart reject rule";
        $CONF[]="/usr/sbin/artica-phpfpm-service -firewall-smart-reject";
        $CONF[]="";
        $CONF[]="/home/artica/firewall/antiddos.sh";
        $CONF[]="";
        $CONF[]="";
        $CONF[]="# --------------- Groups for rules";
        foreach ($this->IPSETS as $line){
            $CONF[]=$line;
        }
        $CONF[]="";
        $CONF[]=$this->build_client();
        if(function_exists("build_progress")){
            build_progress("{building_rules} [".__LINE__."]",15);
        }


            $CONF[]="";
            $CONF[]="# * * * * NAT Main rules * * * *";
            $CONF[]="echo \"Building NAT rules...\"";
            $CONF[]=$this->build_nat();
            $CONF[]="";
            $CONF[]="# * * * * Bridges/MASQUERADE Rules * * * *";
            $CONF[]="echo \"Building bridges and masquerade rules...\"";
            $CONF[]=$build_routers;
            $CONF[]="";
            $CONF[]="# * * * *   LINK BALANCER   * * * *";
            $CONF[]=$this->link_balancer();
            $CONF[]="echo \"DNS amplification rules (if enabled)...\"";
            $CONF[]="# * * * *   DNS Amplification   * * * *";
            $CONF[]=$this->dns_amplification();
			$CONF[]=$this->transparent_3proxy();
			$CONF[]=$this->transparent_redsocks();
			$CONF[]=$this->transparent_proxy_redsocks();
            if($this->EnablenDPI==1) {
                $CONF[]="$iptables -t mangle -I PREROUTING -m ndpi ! --error -j NDPI --flow-info || true";
            }


        if(function_exists("build_progress")){
            build_progress("{building_rules} [".__LINE__."]",15);
        }

			$CONF[]="# * * * * MARK Rules * * * *";
			$CONF[]=$this->BUILD_MARK();
        if(function_exists("build_progress")){
            build_progress("{building_rules} [".__LINE__."]",15);
        }

        if(function_exists("build_progress")){
            build_progress("{building_rules} [".__LINE__."]",15);
        }
        $CONF[]="";
        $CONF[]="# * * * * Build Firewall rules defined by administrators * * * *";
        foreach ($this->ADMIN_RULES as $line){
            $CONF[]=$line;
        }
        $CONF[]="# * * * * Build Automatic Reverse-Proxy rules * * * *";

        if($this->FireHoleLogAllEvents==1){
            $CONF[]="$iptables -A REVERSEPROXY -m limit --limit 1/sec -j LOG --log-prefix \"FIREHOL:ALLOW_REVERSEPROXY\" || true";
        }


        if(count($this->SNAT_FINAL)>0){
            $CONF[]="# * * * * Finally NAT after rules. * * * *";
            foreach ($this->SNAT_FINAL as $line){
                $CONF[]=$line;
            }
        }

        if(function_exists("build_progress")){
            build_progress("{building_rules} [".__LINE__."]",15);
        }

        $CONF[]="";
        $CONF[]="";
        $CONF[]="# * * * * Build Firewall rules (".count($BUILD_INTERFACES).") defined in interfaces * * * *";
        $CONF[]="echo \"Building Interfaces rules\"";
        foreach ($BUILD_INTERFACES as $line) {
            $CONF[] = $line;
        }
         if(function_exists("build_progress")){build_progress("{building_rules} [".__LINE__."]",15);}
         $CONF[]="/usr/sbin/artica-phpfpm-service -iptables-routers";
         if(function_exists("build_progress")){build_progress("{building_rules} [".__LINE__."]",15);}

			$CONF[]="";
            if(is_file("/etc/init.d/fail2ban")) {
                $fail2banClient=$unix->find_program("fail2ban-client");
                $CONF[]="$fail2banClient v -p /var/run/fail2ban/fail2ban.pid -s /var/run/fail2ban/fail2ban.sock reload || true";
            }

        if(function_exists("build_progress")){build_progress("{building_rules} [".__LINE__."]",15);}
        if(!is_dir("/usr/local/etc/firehol")){mkdir("/usr/local/etc/firehol",0755,true);}
        @file_put_contents("/etc/firehol/firehol.conf", @implode("\n", $CONF));
        @file_put_contents("/usr/local/etc/firehol/firehol.conf", @implode("\n", $CONF));
        @file_put_contents("/usr/sbin/firewall-builder.sh",@implode("\n", $CONF));
        @chmod("/usr/sbin/firewall-builder.sh",0755);
        echo "/usr/sbin/firewall-builder.sh [OK]\n";


	}

	private function ipsetGroupNameFromFile($filepath){
        if(!$fp = @fopen($filepath,'r')) {return null;}
        while(!feof($fp)) {
                $content= fgets ($fp,4096);
                if(preg_match("#^create\s+(.+?)\s+#",$content,$re)){
                    fclose($fp);
                    return $re[1];
                }
            }
        fclose($fp);
    }





    private function build_client(){
        $unix=new unix();
        $IPT=$unix->find_program("iptables");
        $slogP=$this->RuleLogs("CLIPING_ALLOW");

        $tfil= "-t filter -A";
        $stne2 = "-m state --state NEW,ESTABLISHED,RELATED";
        $stne3 = "-m state --state ESTABLISHED,RELATED";
	    $tolog=$this->FireHoleLogAllEvents;
        $CONF[] = "";


        foreach ($this->TFILERS_ETH as $th => $none) {

            $CONF[] = "echo \"Allow outgoing icmp connections (pings,...) from $th\"";
            if ($tolog == 1) {$CONF[] = "$IPT $tfil out_{$th} -p icmp $stne2 $slogP";}
            $CONF[] = "$IPT $tfil out_{$th} -p icmp $stne2 -j ACCEPT";
            if ($tolog == 1) {$CONF[] = "$IPT $tfil in_{$th}  -p icmp $stne3 $slogP";}
            $CONF[] = "$IPT $tfil in_{$th}  -p icmp $stne3 -j ACCEPT";
            $CONF[] = "";

        }


        $CONF[] = "echo \"Build client done...\"";
	    return @implode("\n",$CONF);
    }






	public function build_rule($ruleid){
        $sNAT_TYPE[0]   ="DNAT";
        $sNAT_TYPE[1]   ="SNAT";
        $sNAT_TYPE[2]   ="RNAT";
        $isNat          = false;
	    $unix           = new unix();
	    $FINAL          = null;
        $RULES          = array();
        $FINAL_RULES    = array();
        $SUBFINAL       = array();
        $this->dump_object_ev=array();
        $mratelim       = "-m ratelimit --ratelimit-set";
        $iptables       = $unix->find_program("iptables");
        $ipset          = $unix->find_program("ipset");
        $q              = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne          = $q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ruleid'");
        $ForwardTo      = trim($ligne["ForwardTo"]);
        $Interface      = trim($ligne["eth"]);
        $isClient       = intval($ligne["isClient"]);
        $MainService    = $ligne["service"];
        $TimeEnabled    = intval($ligne["enablet"]);
        $jlog           = intval($ligne["jlog"]);
        $accepttype     = $ligne["accepttype"]; // DROP  ACCEPT  MARK
        $MOD            = $ligne["MOD"];
        $xprefix        = "$iptables -t filter -A";
        $stitle         = array();
        $crchain        = "Creating chain";
        $log_contract   = null;
        $xt_ratelimit       = intval($ligne["xt_ratelimit"]);
        $xt_ratelimit_dir   = trim($ligne["xt_ratelimit_dir"]);



        if($accepttype=="DROP"){
            $log_contract="-m conntrack --ctstate NEW,ESTABLISHED";
            $FINAL="-m conntrack --ctstate NEW,ESTABLISHED -j SMART_REJECT";
        }

        if($xt_ratelimit==1){
            $accepttype="DROP";
            $log_contract="$mratelim rule{$ruleid} --ratelimit-mode $xt_ratelimit_dir";
            $FINAL="$mratelim rule{$ruleid} --ratelimit-mode $xt_ratelimit_dir -j DROP";
        }

        if($accepttype=="ACCEPT"){$FINAL="-j ACCEPT";}
        if($Interface==null){$Interface="ALL";}



        if($MOD=="IPFEED"){
            $EnableFireholIPSets            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFireholIPSets"));
            if(!$unix->CORP_LICENSE()){$EnableFireholIPSets=0;}
            $ligne["rulename"]="Cybercrime IP Feeds";

            $FINAL="$log_contract -j SMART_REJECT";
            $accepttype="DROP";
            $isClient=0;
            if($EnableFireholIPSets==0){
                $stitle[]       = "# Rule ($ruleid) Interface $Interface {$ligne["rulename"]}";
                $stitle[]       = "Service: \"$MainService\" Outgoing: $isClient Time: $TimeEnabled";
                $stitle[]       = "License Error";
                return @implode(" ",$stitle);
            }
        }

        if($GLOBALS["VERBOSE"]){echo "extract_ports($MainService,$ruleid) in line ".__LINE__."\n";}
        $services=$this->extract_ports($MainService,$ruleid);

        $stitle[]       = "# Rule ($ruleid) Interface $Interface {$ligne["rulename"]}";
        $stitle[]       = "Service: \"$MainService\" Outgoing: $isClient Time: $TimeEnabled CountOfService=".count($services);



        if($this->FireHoleLogAllEvents==1){$jlog=1;}

        if($accepttype=="TPROXY"){
            $stitle[]       = " TPROXY ";
            $isClient   = 0;
            $isNat      = true;
            $TMAng      = "-t mangle";
            $ipbin      = $unix->find_program("ip");
            $EnableipV6 = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
            $this->ip_forward=1;



            if($ruleid<10) {
                $MARK = "50{$ruleid}";
                $TABLEMARK="150{$ruleid}";
            }else{
                $MARK = "5{$ruleid}";
                $TABLEMARK="15{$ruleid}";
            }
            if($ligne["ForwardToPort"]==0){$ligne["ForwardToPort"]=8080;}
            $address="{$ligne["ForwardTo"]}:{$ligne["ForwardToPort"]}";
            if(!preg_match("#^[0-9\.]+:[1-9][0-9]+#",$address)){
                $stitle[]=" * * TPROXY ERR * *";
                $this->dump_object_ev[]="# Corrupted remote address '$address'";
                return @implode(" ",$this->dump_object_ev)."\n".@implode(" ",$stitle);
            }
            $this->TFILTER[]="";
            $this->TFILTER[]="# Create specific routing rules";
            $this->TFILTER[]="$ipbin -f inet rule del lookup $TABLEMARK";
            $this->TFILTER[]="$ipbin -f inet route flush table $TABLEMARK";
            $this->TFILTER[]="$ipbin -f inet rule add from all fwmark $MARK lookup $TABLEMARK";
            if($Interface<>"ALL") {
                $this->TFILTER[]="$ipbin -f inet route add default via $ForwardTo dev $Interface table $TABLEMARK";

            }else {
                $this->TFILTER[] = "$ipbin -f inet route add default via $ForwardTo table $TABLEMARK";
            }
            if($EnableipV6==1) {
                $this->TFILTER[] = "$ipbin -f inet6 rule del lookup $TABLEMARK";
                $this->TFILTER[] = "$ipbin -f inet6 route flush table $TABLEMARK";
                $this->TFILTER[] = "$ipbin -f inet6 rule add from all fwmark $MARK lookup $TABLEMARK";
                $this->TFILTER[] = "$ipbin -f inet6 route add default via $ForwardTo table $TABLEMARK";
            }


            //iptables -t mangle -A PREROUTING -j ACCEPT -p tcp --dport 80 -s squid-box
            //iptables -t mangle -A PREROUTING -j ACCEPT -p tcp --dport 80 -s squid-box
            //iptables -t mangle -A PREROUTING -j MARK --set-mark 3 -p tcp --dport 80
            $this->TFILTER[]="";
            $this->TFILTER[]="#$crchain 'in_tproxy.$ruleid' under 'PREROUTING' in table 'mangle'";
            $this->TFILTER[]="$iptables $TMAng -N in_tproxy.$ruleid";
            $this->TFILTER[]="";
            $this->TFILTER[]="";
           // $this->TFILTER[]="$iptables $TMAng -A in_tproxy.$ruleid -p tcp -m tos --tos 0x20 ".$this->RuleLogs("TOS_ABORT");
            $this->TFILTER[]="$iptables $TMAng -A in_tproxy.$ruleid -p tcp -m tos --tos 0x20 -j RETURN";
            $this->TFILTER[]="$iptables $TMAng -A in_tproxy.$ruleid -s $ForwardTo -j RETURN";
           // $this->TFILTER[]="$iptables $TMAng -A in_tproxy.$ruleid -p tcp -m tos --tos 0x0 ". $this->RuleLogs("MARK_ADD");
            $this->TFILTER[]="$iptables $TMAng -A in_tproxy.$ruleid -p tcp -m tos --tos 0x0 -j MARK --set-mark $MARK";

           $this->TFILTER[]="";
            $xprefix = "$iptables $TMAng -A PREROUTING";
            if($Interface<>"ALL") {
                $xprefix = "$xprefix -i $Interface";
            }
            $FINAL="-j in_tproxy.$ruleid";

        }

        if(preg_match("#MASQ:([0-9]+)#",$Interface,$re)) {
            $stitle[]       = " Masquerade ID {$re[1]}";
            $isClient   = 0;
            $isNat      = true;
            $accepttype = "MASQUERADE";
            $RuleInterface=$this->Masquerade2Interface($re[1]);
            $stitle[]       = " Interface $RuleInterface";
            if($this->Masquerade2Enabled($re[1])==0){
                $stitle[]=" * * MASQUERADE {$re[1]} DISABLED * *";
                return @implode(" ",$this->dump_object_ev)."\n".@implode(" ",$stitle);
            }
            $xprefix = "$iptables -t nat -A POSTROUTING";
            if($RuleInterface<>null) {
                $xprefix = "$iptables -t nat -A POSTROUTING -o $RuleInterface";
            }
            $FINAL="-m conntrack --ctstate NEW -j MASQUERADE";
        }
        if(preg_match("#([A-Z])NAT:([0-9]+)#",$Interface,$re)) {
            $stitle[]       = " NAT ID {$re[2]} type {$re[1]}";
            $NatID      = intval($re[2]);
            $isClient   = 0;
            $isNat      = true;
            $MainType   = $this->Nat2Type($re[2]);

            $ServiceEnabled=$this->Nat2Enable($re[2]);
            if($ServiceEnabled==0){
                $stitle[]=" * * NAT DISABLED * *";
                return @implode(" ",$stitle);
            }
            if($jlog==0){$jlog=$this->Nat2Log($re[2]);}
            $RuleInterface=$this->Nat2Interface($re[2]);
            $destination=$this->Nat2Destination($re[2]);
            $stitle[]       = " Interface $RuleInterface";
            if($MainType==0) {
                $accepttype = "NAT";
                $xprefix = "$iptables -t nat -A PREROUTING";
                if($RuleInterface<>null) {
                    $xprefix = "$iptables -t nat -A PREROUTING -i $RuleInterface";
                }
                $FINAL = "-m conntrack --ctstate NEW -j DNAT --to-destination $destination";
            }
            if($MainType==1) {
                $accepttype     = $ligne["accepttype"];
                $Group          = "SNAT2{$NatID}";
                $xprefix        = "$iptables -t nat -A POSTROUTING";
                if($RuleInterface<>null) {
                    $xprefix = "$iptables -t nat -A POSTROUTING -o $RuleInterface";
                }
                if($accepttype=="ACCEPT") {
                    $FINAL = "-m conntrack --ctstate NEW -j $Group";
                }else{
                    $FINAL = "-m conntrack --ctstate NEW -j RETURN";
                }
            }

            if($MainType==2){
                $xprefix = "$iptables -t nat -A PREROUTING";
                if($RuleInterface<>null) {
                    $xprefix = "$iptables -t nat -A PREROUTING -i $RuleInterface";
                }
                $FINAL   = "-m conntrack --ctstate NEW -j REDIRECT --to-ports $destination";
            }

            if($MainType==3){
                $xprefix="$iptables -t mangle -A PREROUTING";
                if($RuleInterface<>null) {
                    $xprefix = "$iptables -t mangle -A PREROUTING -i $RuleInterface";
                }
                $MARKID=200+$NatID;
                $FINAL   = "-j MARK --set-mark $MARKID";
            }

        }


        $FINAL="-m comment --comment \"RULE.$ruleid\" $FINAL";
        $FINAL_LOG="$log_contract -m comment --comment \"LOG.$ruleid\" ".
            $this->RuleLogs("{$accepttype}_$ruleid");

        $zPrefix=array();
        $zPrefix[]=$xprefix;
        if($isClient==1) {
            $zPrefix[] = "out_{$Interface}";
        }else{
            $zPrefix[] = "in_{$Interface}";
        }

        if($isNat){
            if($GLOBALS["VERBOSE"]){echo "This Rule is a NAT Rule\n";}
            $zPrefix=array();
            $zPrefix[]=$xprefix;
        }




        if($TimeEnabled==1){
            $Ttime=$this->dump_time($ligne["time_restriction"]);
            if($Ttime<>null){
                $zPrefix[]=$Ttime;
            }
        }

        $nDPI=$this->dump_ndpi($ruleid);
        if($nDPI<>null){
            $zPrefix[] = $nDPI;
        }
        $OUT=array();
        $prefix=@implode(" ",$zPrefix);



        if(count($services)>0){
            foreach ($services as $part_rule){
                $RULES[]="$prefix $part_rule";
            }
        }else{
            $RULES[]="$prefix";
        }


        $sources=$this->build_rule_sources_dest($ruleid);
        if(count($this->dump_object_ev)>0) {
            $OUT[] = @implode("\n", $this->dump_object_ev);
        }
        $RULESSRC=array();
        if(count($sources)>0){
            foreach ($sources as $ipsetFile=>$part_rule){
                $IpsetName=$this->ipsetGroupNameFromFile($ipsetFile);
                $this->IPSETS[]="# ($ruleid) Create ipset $IpsetName";
                $this->IPSETS[]="$ipset destroy $IpsetName >/dev/null 2>&1";
                $this->IPSETS[]="$ipset restore -! < $ipsetFile";
                foreach ($RULES as $prefix) {
                    //$OUT[]="# ($ruleid/ipset): $prefix $part_rule [".__LINE__."]";
                    $RULESSRC[] = "$prefix $part_rule";
                }
            }

        }

        if(count($RULESSRC)==0){
            $RULESSRC=$RULES;
        }

        $RULES1=array();
        $dests=$this->build_rule_sources_dest($ruleid,1);
        if(count($dests)>0){
            foreach ($dests as $ipsetFile=>$part_rule){
                $IpsetName=$this->ipsetGroupNameFromFile($ipsetFile);
                $this->IPSETS[]="# ($ruleid) Create ipset $IpsetName [".__LINE__."]";
                $this->IPSETS[]="echo \"Restoring $IpsetName Group...\"";
                $this->IPSETS[]="$ipset destroy $IpsetName >/dev/null 2>&1";
                $this->IPSETS[]="$ipset restore -! < $ipsetFile";
                foreach ($RULESSRC as $prefix) {
                    $RULES1[] = "$prefix $part_rule";
                }
            }

        }else{
            $RULES1=$RULESSRC;
        }
        if($jlog==1){
            foreach ($RULES1 as $zrule){ $OUT[]=$zrule." $FINAL_LOG"; }
        }
        foreach ($RULES1 as $zrule){ $OUT[]=$zrule." $FINAL"; }


        $stitle[]       = "[".__LINE__."]";
        $title          = @implode(" ",$stitle);
        $FINAL_RULES[]="";
        $FINAL_RULES[]=$title;
        if(count($OUT)>0) {
            $FINAL_RULES[] = @implode("\n", $OUT);
        }


        return @implode("\n",$FINAL_RULES);


    }

    private function dump_ndpi($ruleid){
	    if($this->EnablenDPI==0){return null;}
	    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
        $results=$q->QUERY_SQL("SELECT * FROM firehol_ndpi WHERE ruleid=$ruleid");
        if(count($results)==0){return null;}
        $MAIN=array();
        foreach ($results as $index=>$ligne){
            $ndpiname=trim($ligne["ndpiname"]);
            if($ndpiname==null){continue;}
            $MAIN[$ndpiname]=$ndpiname;
        }

        if(count($MAIN)==0){return null;}
        $TT=array();
        foreach ($MAIN as $ndpiname=>$nothing){
            $TT[]=$ndpiname;
        }
        if(count($TT)==0){return null;}
        return "-m ndpi --proto ".@implode(",",$TT);

    }

    private function Rule2MOD($ID){
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne = $q->mysqli_fetch_array("SELECT MOD FROM iptables_main WHERE ID='$ID'");
        if(!$q->ok){return null;}
        return trim($ligne["MOD"]);
    }

    private function build_rule_sources_dest($ruleid,$direction=0){
        $RETURNED_ARRAY=array();
        $IPFEED_PATH="/home/artica/firewall/CyberCrimeNet.txt";

	    if($direction==0){
            if($this->Rule2MOD($ruleid)=="IPFEED"){
                if($this->DEBUG_COMPILE){$this->dump_object_ev[]="# CyberCrime Detected [".__LINE__."]";}
                if(!is_file($IPFEED_PATH)){
                    $f[]="create CyberCrimeIP hash:net family inet hashsize 1024 maxelem 1500000\n";
                    @file_put_contents($IPFEED_PATH,@implode("\n",$f));
                }
                $RETURNED_ARRAY[$IPFEED_PATH]=$this->group_srcip("CyberCrimeIP","src");
            }
        }


        $direction_name[0]="inbound";
        $direction_name[1]="outbound";
        $unix                   = new unix();
        $rm                     = $unix->find_program("rm");
        $IpSetDestDir           = "/home/artica/firewall/groups/rule.$ruleid/{$direction_name[$direction]}"; $IpSetDestDir           = "/home/artica/firewall/groups/rule.$ruleid/{$direction_name[$direction]}";
        $qProxy                 = new mysql_squid_builder(true);
        $sql                    = "SELECT firewallfilter_sqacllinks.gpid,
                                  firewallfilter_sqacllinks.negation,
                                  firewallfilter_sqacllinks.zOrder,firewallfilter_sqacllinks.zmd5 
                                  as mkey, webfilters_sqgroups.* FROM firewallfilter_sqacllinks,
                                  webfilters_sqgroups WHERE 
                                  firewallfilter_sqacllinks.gpid=webfilters_sqgroups.ID
                                  AND firewallfilter_sqacllinks.aclid=$ruleid
                                  AND firewallfilter_sqacllinks.direction='$direction'
                                  ORDER BY firewallfilter_sqacllinks.zOrder";

        $q      = new lib_sqlite("/home/artica/SQLITE/acls.db");

        if(is_dir($IpSetDestDir)){
            shell_exec("$rm -rf $IpSetDestDir");

        }

        @mkdir($IpSetDestDir,0755,true);


        $results = $q->QUERY_SQL($sql);
        $TRCLASS=null;
        $acl_GroupType_Firewall=$qProxy->acl_GroupType_Firewall_in;
        if($direction==1){
            $acl_GroupType_Firewall=$qProxy->acl_GroupType_Firewall_out;
        }

        $NameGroupTypeS[0]="src";
        $NameGroupTypeS[1]="dst";

        foreach ($results as $index=>$ligne) {
            $negation   = intval($ligne["negation"]);
            $gpid       = intval($ligne["gpid"]);
            $dump_object= $this->dump_object($gpid);
            $GroupName  = $dump_object["GroupName"];
            $GroupType  = $dump_object["GroupType"];
            $enabled    = $dump_object["enabled"];
            $NameGroupType=$GroupType;

            if($enabled==0){
                $this->dump_object_ev[]="# $GroupName Not enabled [".__LINE__."]";
                continue;
            }

            if(!isset($acl_GroupType_Firewall[$GroupType])){
                $this->dump_object_ev[]="# $GroupName Type $GroupType incompatible [".__LINE__."]";
                continue;
            }

            if($GroupType=="office365"){$NameGroupType=$NameGroupTypeS[$direction];}
            if($GroupType=="facebook"){$NameGroupType=$NameGroupTypeS[$direction];}
            if($GroupType=="whatsapp"){$NameGroupType=$NameGroupTypeS[$direction];}
            if($GroupType=="teamviewer"){$NameGroupType=$NameGroupTypeS[$direction];}
            if($GroupType=="skype"){$NameGroupType=$NameGroupTypeS[$direction];}
            if($GroupType=="google"){$NameGroupType=$NameGroupTypeS[$direction];}
            if($GroupType=="localnet"){$NameGroupType=$NameGroupTypeS[$direction];}


            $IpsetName="$NameGroupType-$ruleid-$gpid";
            $IpsetFile="$IpSetDestDir/$NameGroupType$gpid.db";
            if(!isset($MAIN[$IpsetName])){
                $MAIN[$IpsetName]["FILE"]=$IpsetFile;
                $MAIN[$IpsetName]["TYPE"]=$GroupType;
                $MAIN[$IpsetName]["ITEMS"]=array();
            }

            if($this->DEBUG_COMPILE){$this->dump_object_ev[]="# Dump Group id $gpid neg=$negation [".__LINE__."]";}
            $array=$this->dump_items($IpsetName,$gpid,$negation);

            $MAIN[$IpsetName]["ITEMS"]=array_merge($MAIN[$IpsetName]["ITEMS"],$array);
    }


    $direction_Type[0]="src";
    $direction_Type[1]="dst";

    $GroupToDirection["office365"]=true;
    $GroupToDirection["facebook"]=true;
    $GroupToDirection["whatsapp"]=true;
    $GroupToDirection["teamviewer"]=true;
    $GroupToDirection["skype"]=true;
    $GroupToDirection["google"]=true;
    $GroupToDirection["fwgeo"]=true;
    $GroupToDirection["localnet"]=true;
    $maxelemDef=150000;

    foreach ($MAIN as $ipsetName=>$zitems){
            $f=array();
            $ipset_type = null;
            $FileName   = $zitems["FILE"];
            $GroupType  = $zitems["TYPE"];
            $items      = $zitems["ITEMS"];
            if($GroupType=="src"){$ipset_type="src";}

            if(isset($GroupToDirection[$GroupType])){
                $ipset_type=$direction_Type[$direction];
                $GroupType=$direction_Type[$direction];
            }

            $maxelem=count($items)+1;
            if( ($GroupType=="src") OR ($GroupType=="dst") ){
                if($this->DEBUG_COMPILE){$this->dump_object_ev[]="# $ipsetName [".__LINE__."]";}
                if($maxelem>$maxelemDef){
                    $maxelemDef=$maxelem;
                }
                $f[] = "create $ipsetName hash:net family inet hashsize 1024 maxelem $maxelemDef";
            }
            if($GroupType=="arp"){
                $ipset_type="src";
                if($maxelem>$maxelemDef){
                    $maxelemDef=$maxelem;
                }
                if($this->DEBUG_COMPILE){$this->dump_object_ev[]="# $ipsetName [".__LINE__."]";}
                $f[] = "create $ipsetName hash:mac hashsize 1024 maxelem $maxelemDef";
            }

            $f[]=@implode("\n",$items);
            @file_put_contents($FileName,@implode("\n",$f));
            $RETURNED_ARRAY[$FileName]=$this->group_srcip($ipsetName,$ipset_type);

    }

    return $RETURNED_ARRAY;

    }

    private function dump_fwgeo($Country){
        $q=new postgres_sql();
        $results=$q->QUERY_SQL("SELECT pattern FROM ipdeny_geo WHERE country='$Country'");
        $array=array();
        while($ligne=@pg_fetch_assoc($results)){
            $array[]=$ligne["pattern"];
        }
        return $array;
    }

    private function dump_items($IpsetName,$gpid,$direction){
        $f          = array();
        $option     = " -exist";
        $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql        = "SELECT GroupType FROM webfilters_sqgroups WHERE ID=$gpid";
        $ligne      = $q->mysqli_fetch_array($sql);
        $GroupType  = $ligne["GroupType"];

        if($direction==1){$option=" nomatch";}
        if($this->DEBUG_COMPILE){$this->dump_object_ev[]="# $IpsetName for Group:$gpid Type: $GroupType [".__LINE__."]";}

        if($GroupType=="teamviewer") {
            include_once(dirname(__FILE__) . "/ressources/class.products-ip-ranges.inc");
            $products_ip_ranges = new products_ip_ranges();
            $array = $products_ip_ranges->teamviewer_networks();
            foreach ($array as $pattern){
                $f[] = "add $IpsetName $pattern{$option}";
            }
            if($this->DEBUG_COMPILE){$this->dump_object_ev[]="# Group $gpid exported: ".count($f)." elements [".__LINE__."]";}
            return $f;
        }

        if($GroupType=="whatsapp") {
            include_once(dirname(__FILE__) . "/ressources/class.products-ip-ranges.inc");
            $products_ip_ranges = new products_ip_ranges();
            $array = $products_ip_ranges->whatsapp_networks();
            foreach ($array as $pattern){
                $f[] = "add $IpsetName $pattern{$option}";
            }
            if($this->DEBUG_COMPILE){$this->dump_object_ev[]="# $gpid = ".count($f)." elements";}
            return $f;
        }
        if($GroupType=="office365") {
            include_once(dirname(__FILE__) . "/ressources/class.products-ip-ranges.inc");
            $products_ip_ranges = new products_ip_ranges();
            $array = $products_ip_ranges->office365_networks();
            foreach ($array as $pattern){
                $f[] = "add $IpsetName $pattern{$option}";
            }
            if($this->DEBUG_COMPILE){ $this->dump_object_ev[]="# $gpid = ".count($f)." elements";}
            return $f;
        }
        if($GroupType=="facebook") {
            include_once(dirname(__FILE__) . "/ressources/class.products-ip-ranges.inc");
            $products_ip_ranges = new products_ip_ranges();
            $array = $products_ip_ranges->facebook_networks();
            foreach ($array as $pattern){
                $f[] = "add $IpsetName $pattern{$option}";
            }
            if($this->DEBUG_COMPILE){ $this->dump_object_ev[]="# $gpid = ".count($f)." elements";}
            return $f;
        }
        if($GroupType=="skype") {
            include_once(dirname(__FILE__) . "/ressources/class.products-ip-ranges.inc");
            $products_ip_ranges = new products_ip_ranges();
            $array = $products_ip_ranges->skype_networks();
            foreach ($array as $pattern){
                $f[] = "add $IpsetName $pattern{$option}";
            }
            if($this->DEBUG_COMPILE){ $this->dump_object_ev[]="# $gpid = ".count($f)." elements";}
            return $f;
        }
        if($GroupType=="google") {
            include_once(dirname(__FILE__) . "/ressources/class.products-ip-ranges.inc");
            $products_ip_ranges = new products_ip_ranges();
            $array = $products_ip_ranges->google_networks();
            foreach ($array as $pattern){
                $f[] = "add $IpsetName $pattern{$option}";
            }
            if($this->DEBUG_COMPILE){ $this->dump_object_ev[]="# $gpid = ".count($f)." elements";}
            return $f;
        }

        if($GroupType=="localnet"){
            $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
            $results=$q->QUERY_SQL("SELECT ipaddr FROM networks_infos WHERE enabled=1");
            foreach ($results as $index=>$ligne){
                $pattern=$ligne["ipaddr"];
                $pattern=$this->CleanCDIR($pattern);
                $f[] = "add $IpsetName $pattern{$option}";
            }

            return $f;

        }


        $sql        = "SELECT * FROM webfilters_sqitems WHERE gpid=$gpid ORDER BY ID";
        $results    = $q->QUERY_SQL($sql);

        foreach ($results as $index=>$ligne){
            $pattern=$ligne["pattern"];
            if($GroupType=="fwgeo"){
                $subarray=$this->dump_fwgeo($pattern);
                if(count($subarray)>0){
                    foreach ($subarray as $newp){
                        $f[] = "add $IpsetName $newp{$option}";
                    }
                }
            continue;
            }

            if($GroupType=="src") {
                $pattern = $this->CleanCDIR($pattern);
            }
            if($GroupType=="dst") {
                $pattern = $this->CleanCDIR($pattern);
            }
            $f[] = "add $IpsetName $pattern{$option}";
        }
        if($this->DEBUG_COMPILE){ $this->dump_object_ev[]="# $gpid = ".count($f)." elements";}
        return $f;

    }

    private function dump_time($content=null){
            if($content==null){return null;}
            $TTIME      = unserialize($content);
            $array_days = array(1=>"monday",2=>"tuesday",3=>"wednesday",4=>"thursday",
                         5=>"friday",6=>"saturday",7=>"sunday");
            $DDS        = array();

            if(!is_array($TTIME)){return null;}

            foreach ($array_days as $num=>$maks){
                if($TTIME["D{$num}"]==1){$DDS[]=$num;}
            }

            if(count($DDS)>0){
                $f[]="--weekdays ".@implode(",", $DDS);
            }

            if( (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ftime"])) AND  (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ttime"]))  ){
                $f[]="--timestart {$TTIME["ftime"]} --timestop {$TTIME["ttime"]}";
            }

            if(count($f)==0){return null;}


            return "-m time ".@implode(" ", $f);

        }


    private function dump_object($groupid){
        $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne      = $q->mysqli_fetch_array("SELECT * 
                    FROM webfilters_sqgroups WHERE ID='$groupid'");


        $GroupName  = $ligne["GroupName"];
        $GroupType  = $ligne["GroupType"];
        $enabled    = intval($ligne["enabled"]);

        $ligne  = $q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilters_sqitems 
                    WHERE gpid='$groupid'");

        $Items=intval($ligne["tcount"]);

        return array("GroupName"=>$GroupName,"GroupType"=>$GroupType,"enabled"=>$enabled,"items"=>$Items);

    }

	private function dns_amplification(){
        $iptables="iptables";
        $DNSAmplificationProtection=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSAmplificationProtection"));
        $cmd[]="$iptables -A INPUT -m set --match-set trustednet src -p udp --dport 53 -j ACCEPT";
        if($DNSAmplificationProtection==0){return @implode("\n",$cmd);}

        $cmd[]="$iptables -N  DNS-RATE-LIMIT || true";
        $cmd[]="$iptables -A INPUT -m set ! --match-set trustednet src  -p udp -m udp --dport 53 -j DNS-RATE-LIMIT || true";
        $cmd[]="$iptables -A DNS-RATE-LIMIT -m hashlimit --hashlimit-above 10/sec --hashlimit-burst 20 --hashlimit-mode srcip --hashlimit-name RL-DNS-GLOBL-v4 --hashlimit-srcmask 24 -j DROP || true";
        $cmd[]="$iptables -I DNS-RATE-LIMIT -m string --algo bm --hex-string \"|0000ff0001|\" --from 28 -m hashlimit --hashlimit-above 1/sec --hashlimit-burst 2 --hashlimit-mode srcip --hashlimit-name RL-DNS-ANY-v4 --hashlimit-srcmask 24 -j DROP || true";
        $cmd[]="$iptables -A DNS-RATE-LIMIT -m u32 --u32 \"0x0>>0x16&0x3c@0x14&0xffffff00=0xff00\" -m hashlimit --hashlimit-above 1/sec --hashlimit-burst 2 --hashlimit-mode srcip --hashlimit-name RL-DNS --hashlimit-srcmask 24 -j DROP || true";
        $cmd[]="$iptables -A DNS-RATE-LIMIT -m u32 --u32 \"0x0>>0x16&0x3c@0x14&0xffdfdfdf=0x3495343&&0x0>>0x16&0x3c@0x18&0xffdfdfdf=0x34f5247&&0x0>>0x16&0x3c@0x1c&0xffffff00=0xff00\" -m hashlimit --hashlimit-above 1/sec --hashlimit-burst 2 --hashlimit-mode srcip --hashlimit-name RL-DNS --hashlimit-srcmask 24 -j DROP || true";
        $cmd[]="$iptables -A DNS-RATE-LIMIT -m hashlimit --hashlimit-above 10/sec --hashlimit-burst 20 --hashlimit-mode srcip --hashlimit-name RL-DNS --hashlimit-srcmask 24 -j DROP || true";
        return @implode("\n",$cmd);
    }


	private function ipset_compatible(){
		$ipset_version=$this->ipset_version();
		$V=explode(".",$ipset_version);
		$major=$V[0];
		$minor=$V[1];
		if($major>0){
			if($minor>=19){return true;}
		}
		return false;
	}


	private function build_nat_dest($ipaddr,$port=null){
		$port=intval($port);
		$IP=new IP();
		if(!$IP->isIPAddress($ipaddr)){$ipaddr=gethostbyaddr($ipaddr);}
		if($port==0){return $ipaddr;}
		return "$ipaddr:$port";

	}

	private function ipset_version(){
		$unix=new unix();
		$ipset=$unix->find_program("ipset");
		if(!is_file($ipset)){$GLOBALS["ipset_version"]=0;return $GLOBALS["ipset_version"];}
		$results=exec("$ipset -v 2>&1");
		if(preg_match("#v([0-9]+)\.([0-9]+)#", $results,$re)){
			$GLOBALS["ipset_version"]="{$re[1]}.{$re[2]}";
			return $GLOBALS["ipset_version"];
		}
	}

	private function Masquerade2CountOfRules($ID){
	    $eth="MASQ:$ID";
        $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM 
                iptables_main WHERE eth='$eth' AND enabled=1");
        return intval($ligne["tcount"]);
    }

	private function Masquerade2Enabled($ID){
        $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne=$q->mysqli_fetch_array("SELECT enabled FROM firehol_masquerade WHERE ID='$ID'");
        return intval($ligne["enabled"]);
    }

	private function Masquerade2Interface($ID){
        $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne=$q->mysqli_fetch_array("SELECT nic FROM firehol_masquerade WHERE ID='$ID'");
        return $ligne["nic"];
    }

	private function Nat2Interface($ID){
        $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne      = $q->mysqli_fetch_array("SELECT nic FROM pnic_nat WHERE ID='$ID'");
        if(!$q->ok){$this->dump_object_ev[]="#$q->mysql_error";}
        $eth        = $ligne["nic"];
        return $eth;
    }
    private function Nat2Log($ID){
        $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        if(!$q->FIELD_EXISTS("pnic_nat","jlog")){$q->QUERY_SQL("ALTER TABLE pnic_nat 
                                                ADD jlog INTEGER NOT NULL DEFAULT 0");}
        $ligne      = $q->mysqli_fetch_array("SELECT jlog FROM pnic_nat WHERE ID='$ID'");
        if(!$q->ok){$this->dump_object_ev[]="#$q->mysql_error";}
        return        intval($ligne["jlog"]);

    }

    private function Nat2Enable($ID){
        $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne      = $q->mysqli_fetch_array("SELECT enabled FROM pnic_nat WHERE ID='$ID'");
        return intval($ligne["enabled"]);
    }

    private function Nat2Type($ID){
        $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne      = $q->mysqli_fetch_array("SELECT NAT_TYPE FROM pnic_nat WHERE ID='$ID'");
        return intval($ligne["NAT_TYPE"]);
    }

    private function Nat2Destination($ID){
        $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne      = $q->mysqli_fetch_array("SELECT NAT_TYPE,dstaddr,dstaddrport FROM pnic_nat 
                                                WHERE ID='$ID'");
        $NAT_TYPE   = $ligne["NAT_TYPE"];
        $dstaddr    = $ligne["dstaddr"];
        $dstaddrport= intval($ligne["dstaddrport"]);
        if($NAT_TYPE==2){return $dstaddrport;}
        return $this->build_nat_dest($dstaddr,$dstaddrport);
    }

    private function CountOfRulesByeth($eth){
        $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $sql="SELECT count(*) as tcount FROM iptables_main WHERE eth='$eth' and enabled=1";
        $ligne=$q->mysqli_fetch_array($sql);
        return intval($ligne["tcount"]);
    }

	private function build_nat(){
		$q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$sql        = "SELECT * FROM `pnic_nat`  WHERE enabled=1";
		$results    = $q->QUERY_SQL($sql);
		$unix       = new unix();
		$iptables   = $unix->find_program("iptables");
        $ipbin      = $unix->find_program("ip")." -f inet";
		$iptprefix  ="$iptables -t nat";
		$iptmangle  ="$iptables -t mangle";
        $CONF       = array();
        $sNAT_TYPE[0]="DNAT";
        $sNAT_TYPE[1]="SNAT";
        $sNAT_TYPE[2]="RNAT";
        $sNAT_TYPE[3]="XNAT";
		if(!$q->ok){return null;}

		foreach ($results as $index=>$ligne){
		    $ID         = $ligne["ID"];
			$NAT_TYPE   = $ligne["NAT_TYPE"];
			$nic        = trim($ligne["nic"]);
            $dstaddr    = $ligne["dstaddr"];
            $jlog       = intval($ligne["jlog"]);
            $dstaddrport= intval($ligne["dstaddrport"]);
            $destination= $this->build_nat_dest($dstaddr,$dstaddrport);
            $Comment    = "-m comment --comment \"NAT.$ID\"";
            $CONF[]="";
			$CONF[]="# NAT {$ligne["ID"]} [eth:{$nic}] To $destination LOG:$jlog";

			$GroupName=$sNAT_TYPE[$NAT_TYPE]."2{$ID}";
			if($NAT_TYPE==0){
			    $CONF[]="$iptprefix -N $GroupName $Comment";

			}

            if($NAT_TYPE==3){
                $MARKID=200+$ligne["ID"];
                $TABLEMARK=800+$ligne["ID"];
                $CountOfRulesByeth=$this->CountOfRulesByeth("XNAT:{$ligne["ID"]}");
                $CONF[]="# xNAT MARK $MARKID [eth:{$nic}] rules XNAT:{$ligne["ID"]}= $CountOfRulesByeth";
                if($CountOfRulesByeth==0) {continue;}
                $CONF[]="$iptmangle -A PREROUTING -s $dstaddr -j RETURN $Comment";
                $this->SNAT_FINAL[] = "# Redirect MARK $MARKID to $dstaddr";
                $this->SNAT_FINAL[] = "$ipbin rule del lookup $TABLEMARK || true";
                $this->SNAT_FINAL[] = "$ipbin route flush table $TABLEMARK || true";
                $this->SNAT_FINAL[] = "$ipbin rule add from all fwmark $MARKID lookup $TABLEMARK";
                if($nic<>null) {$this->SNAT_FINAL[]="$ipbin route add default via $dstaddr dev $nic table $TABLEMARK";
            }else {
                $this->SNAT_FINAL[] = "$ipbin route add default via $destination table $TABLEMARK";
            }
            }

			if($NAT_TYPE==1){
                $CountOfRulesByeth=$this->CountOfRulesByeth("SNAT:{$ligne["ID"]}");
                $CONF[]="# sNAT {$ligne["ID"]} [eth:{$nic}] rules = $CountOfRulesByeth";
                $CONF[]="$iptprefix -N $GroupName $Comment";
                if($nic<>null){$nic=" -o $nic";}
                $Comment    = "-m comment --comment \"NAT.$ID\"";

                if($CountOfRulesByeth==0) {
                    $CONF[]="$iptprefix -A POSTROUTING{$nic} -m conntrack --ctstate NEW -j $GroupName";
                    if ($jlog == 1) {
                        $CONF[] = "$iptprefix -A $GroupName " . $this->RuleLogs("NAT_$ID");
                    }
                    $CONF[] = "$iptprefix -A $GroupName $Comment -j SNAT --to-source $destination";
                }else{
                    if ($jlog == 1) {
                        $this->SNAT_FINAL[] = "$iptprefix -A $GroupName " . $this->RuleLogs("NAT_$ID");
                    }
                        $this->SNAT_FINAL[]= "$iptprefix -A $GroupName $Comment -j SNAT --to-source $destination";
                }

            }

        }
        return @implode("\n", $CONF);
    }

	private function iptables_ports($PortArray,$protocol){
		if(count($PortArray)==0){return null;}
		if(count($PortArray)==1){return "-p $protocol --dport ".@implode("",$PortArray);}
		return "-p $protocol -m multiport --destination-ports ".@implode(",", $PortArray);

	}




	public function BUILD_MARK(){
		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$sql="SELECT * FROM iptables_main WHERE enabled=1 AND accepttype='MARK' ORDER by zOrder";
		$results=$q->QUERY_SQL($sql);
		$firehol_rules=new firehol_rules();
		$CONF=array();
		$ipClass=new IP();

		if(count($results)==0){return;}

		foreach ($results as $index=>$ligne){
			$DIR="PREROUTING";
			$IPTABLES_OUTFACE=null;
			$nic=trim($ligne["eth"]);
			$isClient=intval($ligne["isClient"]);
			if($isClient==1){$DIR="OUTPUT";}
			$FWMARK=intval($ligne["MARK"]);
			$ACTION=null;
			$service=$ligne["service"];
			$ID=$ligne["ID"];
			$TimeEnabled=$ligne["enablet"];
			$IPTABLES_RESTRICTIONS=null;
			$jlog=intval($ligne["jlog"]);
			if($this->FireHoleLogAllEvents==1){$jlog=1;}
			$IPTABLES_LOG=$this->RuleLogs("RULE.$ID");
			$GetTcpPortsFromService=$this->GetTcpPortsFromService($service);
			$CONF[]="# Rule {$ligne["ID"]} [eth:{$nic}] MARK $FWMARK Service: $service (".count($GetTcpPortsFromService)." protocols)";
			if($FWMARK==0){continue;}
			if($TimeEnabled==1){$IPTABLES_RESTRICTIONS=$firehol_rules->parse_time_iptables($ligne["time_restriction"]);}
			$inbound_objects=$firehol_rules->objects($ID,0);
			$outbound_objects=$firehol_rules->objects($ID,1);
			$ForwardTo=trim($ligne["ForwardTo"]);
			if(!$ipClass->isValid($ForwardTo)){$ForwardTo=null;}

			$ForwardNIC=$ligne["ForwardNIC"];
			if($ForwardNIC<>$nic){$IPTABLES_OUTFACE=" -o $ForwardNIC";}


			if(isset($GetTcpPortsFromService["tcp"])){
				$IPTABLES_PORTS=$this->iptables_ports($GetTcpPortsFromService["tcp"],"tcp");
				if($ForwardTo<>null){
					$CONF[]="iptables -t mangle -A PREROUTING -i $nic{$IPTABLES_OUTFACE} $IPTABLES_PORTS -s $ForwardTo -j ACCEPT";
				}

				if($jlog==1){
					$MASTER_RULE="iptables -t mangle -A PREROUTING -i $nic{$IPTABLES_OUTFACE} $IPTABLES_PORTS %%s %%d{$IPTABLES_RESTRICTIONS} {$IPTABLES_LOG}";
					$CONF=$firehol_rules->iptables_inbound_objects($ID, $MASTER_RULE,$CONF);
				}

				$MASTER_RULE="iptables -t mangle -A PREROUTING -i $nic{$IPTABLES_OUTFACE} $IPTABLES_PORTS %%s %%d{$IPTABLES_RESTRICTIONS} -j MARK --set-mark $FWMARK";
				if($GLOBALS["VERBOSE"]){echo "TCP: $MASTER_RULE\n";}
				if($GLOBALS["VERBOSE"]){echo "TCP: iptables_inbound_objects($ID,....)\n";}
				$CONF=$firehol_rules->iptables_inbound_objects($ID, $MASTER_RULE,$CONF);
			}

			if(isset($GetTcpPortsFromService["udp"])){
				$IPTABLES_PORTS=$this->iptables_ports($GetTcpPortsFromService["udp"],"udp");

				if($jlog==1){
					$MASTER_RULE="iptables -t mangle -A PREROUTING -i $nic{$IPTABLES_OUTFACE} $IPTABLES_PORTS %%s %%d{$IPTABLES_RESTRICTIONS} {$IPTABLES_LOG}";
					$CONF=$firehol_rules->iptables_inbound_objects($ID, $MASTER_RULE,$CONF);
				}

				$MASTER_RULE="iptables -t mangle -A PREROUTING -i $nic{$IPTABLES_OUTFACE} $IPTABLES_PORTS %%s %%d{$IPTABLES_RESTRICTIONS} -j MARK --set-mark $FWMARK";
				$CONF=$firehol_rules->iptables_inbound_objects($ID, $MASTER_RULE,$CONF);
			}


			$CONF[]="iptables -t mangle -A PREROUTING -m mark --mark $FWMARK -j ACCEPT";

		}




		return @implode("\n", $CONF);

	}
	private function GetTcpPortsFromService($servicename):array{

        if($servicename=="RustDesk"){
            $MAIN["tcp"]=array(21115, 21116, 21117, 21118, 21119);
            $MAIN["udp"]=array(21116);
            return $MAIN;
        }


		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$sql="SELECT server_port FROM firehol_services_def WHERE enabled=1 AND service='$servicename'";
		$ligne=$q->mysqli_fetch_array($sql);
		$MAIN=array();
		$server_port=trim(str_replace("\\n", "\n", $ligne["server_port"]));
		if($server_port==null){
            return array();
        }
		$xservices=explode(" ",$server_port);
		foreach ($xservices as $line){
			$line=trim(strtolower($line));
			if(!preg_match("#([a-z]+)\/([0-9]+)#", $line,$re)){continue;}
			$MAIN[$re[1]][]=$re[2];

		}

		if(count($MAIN)==0){return array();}
		return $MAIN;
	}

	private function RuleLogs($text):string{

	    return "-m limit --limit 1/s --limit-burst 5 -j LOG --log-level warning --log-prefix=\"FIREHOL: $text: \"";

    }
    private function CleanCDIR($pattern){
	    if(strpos($pattern,"/")==0){
	        return $pattern."/32";
        }
        $ip_arr = explode("/", $pattern);
        if(strpos($ip_arr[1],".")==0){return $pattern;}
        $IPClass=new IP();
        $ip_arr[1]=$IPClass->mask2cdr($ip_arr[1]);
        return "{$ip_arr[0]}/{$ip_arr[1]}";
    }

    private function explode_cdir($ip_addr_cidr,$ipsetname,$link,$nomatch=false){
        $ip_arr = explode("/", $ip_addr_cidr);
        $nomatch_text=null;

        if(strpos($ip_arr[1],".")>0){
            $IPClass=new IP();
            $ip_arr[1]=$IPClass->mask2cdr($ip_arr[1]);
            $ip_addr_cidr="{$ip_arr[0]}/{$ip_arr[1]}";
        }
        $cdir=intval($ip_arr[1]);
        if($nomatch){$nomatch_text=" nomatch";}
        if($cdir<16){
            fwrite($link,"add $ipsetname $ip_addr_cidr{$nomatch_text}\n");
            return;
        }
        if($cdir==32){
            fwrite($link,"add $ipsetname $ip_addr_cidr {$nomatch_text}\n");
            return;
        }

        $bin = "";

        for($i=1;$i<=32;$i++) {
            $bin .= $ip_arr[1] >= $i ? '1' : '0';
        }

        $ip_arr[1] = bindec($bin);

        $ip = ip2long($ip_arr[0]);
        $nm = $ip_arr[1];
        $nw = ($ip & $nm);
        $bc = $nw | ~$nm;
        $bc_long = ip2long(long2ip($bc));

        for($zm=1;($nw + $zm)<=($bc_long - 1);$zm++) {
            fwrite($link,"add $ipsetname ".long2ip($nw + $zm)."{$nomatch_text}\n");
        }

    }

    private function GroupBuildNet($GroupName,$array=array()):string{
        $unix=new unix();
        $explodecdir=false;
        $ipset=$unix->find_program("ipset");
        $destinationfile="/home/artica/firewall/$GroupName.txt";
        $nomatch=false;
        if($GroupName=="trustednet"){
            $explodecdir=true;

        }

        if(is_file($destinationfile)){@unlink($destinationfile);}

        $f[]="create $GroupName hash:net family inet hashsize 1024 maxelem 255000\n";
        @file_put_contents($destinationfile,@implode("\n",$f));
        $tmpf = @fopen($destinationfile, "a");
        foreach ($array as $cdir) {
            $cdir=trim($cdir);
            if($cdir==null){continue;}
            if($explodecdir){
                $this->explode_cdir($cdir,$GroupName,$tmpf,$nomatch);
                continue;
            }
            fwrite($tmpf,"add $GroupName $cdir\n");
        }
        @fclose($tmpf);

        return "echo \"Restoring ipset group $GroupName\"\n$ipset restore -! < /home/artica/firewall/$GroupName.txt";
    }

	private function BUILD_INTERFACES(){
    }






	private function router_services($routerid,$RouterLog){

		if($this->FireHoleLogAllEvents==1){
			$loglimit_pass=" loglimit \"PASS-{$RouterLog}\"";
			$loglimit_deny=" loglimit \"DENY-{$RouterLog}\"";
		}

		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$sql="SELECT firehol_services_routers.service,firehol_services_routers.allow,firehol_services_routers.zOrder,
		firehol_services_def.enabled
		  FROM `firehol_services_routers`,`firehol_services_def` WHERE firehol_services_routers.routerid={$routerid} 
		  AND firehol_services_def.service=firehol_services_routers.service 
		  AND `firehol_services_def`.enabled=1
		  ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql);
		if(count($results)==0){return null;}

		$allow_type[0]="reject";
		$allow_type[1]="accept";

		$rrlog[0]=$loglimit_deny;
		$rrlog[1]=$loglimit_pass;

		foreach ($results as $index=>$ligne){
			$logging_text=null;
			if($ligne["enabled"]==0){
				$CONF[]="# {$ligne["service"]} Disabled...";
				continue;
			}

			$CONF[]="# {$ligne["service"]} Log All:{$this->FireHoleLogAllEvents}...";
			$whitelisted_destination_networks=$this->router_services_excludes($ligne["service"],$routerid,1);
			$whitelisted_src_networks=$this->router_services_excludes($ligne["service"],$routerid,0);

			if($this->FireHoleLogAllEvents==1){$logging_text=$rrlog[$ligne["allow"]];}

			$CONF[]="\tserver {$ligne["service"]} {$allow_type[$ligne["allow"]]}{$whitelisted_destination_networks}{$whitelisted_src_networks}{$logging_text}";
		}

		if(count($CONF)==0){return null;}
		return @implode("\n",$CONF);
	}

	private function router_services_excludes($service,$routerid,$destination){
		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$sql="SELECT pattern  FROM `firehol_routers_exclude` WHERE destination=$destination AND service='$service' AND routerid='$routerid'";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(count($results)==0){return;}

		foreach ($results as $index=>$ligne){
			$f[]=$ligne["pattern"];
		}

		if(count($f)==0){return null;}
		if($destination==1){ return " dst not \"".@implode(" ", $f)."\" "; }
		return " src not \"".@implode(" ", $f)."\" ";
	}



	private function CheckDirs(){
		$dirs[]="/home/artica/firewall";
        foreach ($dirs as $dr) {
			if(!is_dir($dr)){
				@mkdir($dr,0755,true);
			}

		}

	}

	public function interface_count_local_services($Interface){
		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$sql="SELECT COUNT(`service`) as tcount FROM `firehol_client_services` WHERE `enabled`=1 AND `interface`='$Interface'";
		$ligne=$q->mysqli_fetch_array($sql);
		return $ligne["tcount"];

	}

	public function interface_count_allowed_services($Interface){
		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$sql="SELECT COUNT(`service`) as tcount FROM `firehol_services` WHERE `enabled`=1 AND `interface`='$Interface'";
		$ligne=$q->mysqli_fetch_array($sql);
		return $ligne["tcount"];

	}

	public function checkTables(){
		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$CountOFfirehol_services_def=$q->COUNT_ROWS("firehol_services_def");
        if($GLOBALS["VERBOSE"]){echo "firehol_services_def -> $CountOFfirehol_services_def item(s)\n";}
		if($CountOFfirehol_services_def>50){return true;}
		$data=base64_decode(@file_get_contents("/usr/share/artica-postfix/ressources/databases/firehol.services.db"));
		$services=unserialize($data);
		if(count($services)<2){
			echo "<H2 style='color:#d32d2d'>FATAL no services database</H2><hr>$data";
			return false;
		}

        foreach ($services as $service=>$array){
			$helper=null;
			$server_port=$q->sqlite_escape_string2($array["server"]["ports"]);
			$client_port=$q->sqlite_escape_string2($array["client"]["ports"]);
			if(isset($array["helper"])){$helper=mysql_escape_string2($array["helper"]);}
			$f[]="('$service','$server_port','$client_port','$helper',1)";
		}
		$sql="INSERT INTO `firehol_services_def` (service,server_port,client_port,helper,enabled) VALUES ". @implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."<hr>\n$sql\n";return false;}

        return true;

		}





	private function isSquid(){
		if(isset($GLOBALS["isSquid"])){return $GLOBALS["isSquid"];}
		$unix=new unix();
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(!is_file($squidbin)){$GLOBALS["isSquid"]=false;return $GLOBALS["isSquid"];}
		$sock=new sockets();


		$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($SQUIDEnable==0){$GLOBALS["isSquid"]=false;return $GLOBALS["isSquid"];}

		$GLOBALS["isSquid"]=true;
		return $GLOBALS["isSquid"];

	}

	private function isUfdbguardHTTP(){

	}

	private function secure_gateway_defaults(){
		$q=new mysql();

		$q->QUERY_SQL("INSERT IGNORE INTO `gateway_secure` (portname,dport,dproto,enabled)
			VALUES
			('HTTP service','80','0','1'),
			('HTTPs service','443','0','1'),
			('SMTP service','25','0','1'),
			('SMTP service','587','0','1'),
			('SMTPs service','465','0','1'),
			('IMAP service','143','0','1'),
			('IMAPs service','995','0','1'),
			('POP3 service','110','0','1'),
			('POP3s service','995','0','1'),
			('FTP service','21','0','1'),
			('FTP service -2','20','0','1'),
			('SSH service','22','0','1'),
			('Telnet service','23','0','1'),
			('DNS service','53','0','1'),
			('DNS service','53','1','1'),
			('NNTP service','119','0','1'),
			('NETBIOS service','137','0','1'),
			('NETBIOS service','138','0','1'),
			('NETBIOS service','139','0','1'),
			('TSE','3389','0','1'),
			('iTunes','3689','0','1'),
			('iTunes','3689','1','1')","artica_backup");

	}


	private function secure_gateway(){
		if(isset($GLOBALS["secure_gateway"])){return $GLOBALS["secure_gateway"];}
		$q=new mysql();
		$sql="CREATE TABLE IF NOT EXISTS `gateway_secure`
	(  ID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	 `dport` INT(10) NOT NULL, `portname` varchar(256) NOT NULL, `dproto` smallint(1) NOT NULL, `enabled` smallint(1) NOT NULL, KEY `portname` (`portname`), KEY `enabled` (`enabled`) ) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_backup");
		if($q->COUNT_ROWS("gateway_secure", "artica_backup")==0){$this->secure_gateway_defaults();}

		@mkdir("/etc/firehol/services",0755,true);
		@mkdir("/usr/local/etc/firehol/services",0755,true);

		$sql="SELECT * FROM `gateway_secure` WHERE enabled=1";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		$PROTO[0]="tcp";
		$PROTO[1]="udp";
		$EX[]="\tserver ICMP accept";
		while ($ligne = mysqli_fetch_assoc($results)) {
			$dport=$ligne["dport"];
			$proto=$PROTO[$ligne["dproto"]];
			$service="Service{$proto}{$dport}";

			$EX[]="\troute {$service} accept";
			$f[]="#FHVER: 1:213";
			$f[]="server_{$service}_ports=\"$proto/$dport\"";
			$f[]="client_{$service}_ports=\"default\"";
			$f[]="";
			@file_put_contents("/etc/firehol/services/{$service}.conf",@implode("\n", $f));
			@file_put_contents("/usr/local/etc/firehol/services/{$service}.conf",@implode("\n", $f));
			$f=array();
		}
		$GLOBALS["secure_gateway"]=@implode("\n",$EX);
		return $GLOBALS["secure_gateway"];
	}

	function build_routers_final(){
        $unix       = new unix();
        $f[]="# finally for bridges [".__LINE__."]";
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $results=$q->QUERY_SQL("SELECT * FROM pnic_bridges WHERE enabled=1");
        $BEHA[1]="-j SMART_REJECT";
        $BEHA[0]="-j ACCEPT";

        $BEHA1[1]="Reject";
        $BEHA2[0]="Accept";

        $iptables=$unix->find_program("iptables");
        $ConntrackNew="-m conntrack --ctstate NEW";
        $ctstateNew="-m conntrack --ctstate NEW,ESTABLISHED";
        $ctstate="-m state --state RELATED,ESTABLISHED";
        if(count($results)==0){
            $f[]="# No bridge defined [".__LINE__."]";
            return @implode("\n",$f);
        }
        foreach ($results as $index=>$ligne) {
            $nicfrom = $ligne["nic_from"];
            $nicto = $ligne["nic_to"];
            $jlog = intval($ligne["jlog"]);
            $ID = $ligne["ID"];
            $RouterName = "{$nicfrom}2{$nicto}";
            $NoFirewall = intval($ligne["NoFirewall"]);
            $IN = "in_{$RouterName}";
            $Comment = "-m comment --comment \"ROUTER.$ID\"";
            $tfilterA = "$iptables -t filter -A";
            $policy = intval($ligne["policy"]);

            if($NoFirewall==0) {
                $f[] = "";
                $f[] = "# Finally {$BEHA1["$policy"]} all from $nicfrom and to $nicto..";
                if ($jlog == 1) {
                    $f[] = "$tfilterA $IN $Comment " .
                        $this->RuleLogs("FORWARD_$ID");
                }
                $f[] = "$tfilterA $IN $ctstate $Comment {$BEHA[$policy]}";
                $f[] = "$tfilterA $IN $Comment {$BEHA[$policy]}";
            }

        }

        return @implode("\n",$f);


    }


    function build_routers(){
        $unix=new unix();
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $results=$q->QUERY_SQL("SELECT * FROM pnic_bridges WHERE enabled=1");
        $f=array();
        if(!$q->ok){
            $f[]="#  SQL ERROR [".__LINE__."] $q->mysql_error";
            echo $q->mysql_error."\n";
            return @implode("\n",$f);
        }
        if(count($results)==0){
            $f[]="# No bridge defined [".__LINE__."]";
            return @implode("\n",$f);
        }

        $BEHA[1]="-j SMART_REJECT";
        $BEHA[0]="-j ACCEPT";

        $iptables=$unix->find_program("iptables");
        $ConntrackNew="-m conntrack --ctstate NEW";
        $ctstateNew="-m conntrack --ctstate NEW,ESTABLISHED";
        $ctstate="-m state --state RELATED,ESTABLISHED";
        foreach ($results as $index=>$ligne){
            $nicfrom    = $ligne["nic_from"];
            $nicto      = $ligne["nic_to"];
            $masquerade = $ligne["masquerading"];
            $jlog       = intval($ligne["jlog"]);
            $ID         = $ligne["ID"];
            $DenyDHCP   = intval($ligne["DenyDHCP"]);
            $RouterName = "{$nicfrom}2{$nicto}";
            $NoFirewall = intval($ligne["NoFirewall"]);
            $IN         = "in_{$RouterName}";
            $Comment    = "-m comment --comment \"ROUTER.$ID\"";
            $tfilterA   = "$iptables -t filter -A";


            if($NoFirewall==1){$DenyDHCP=0;}
            if($this->FireHoleLogAllEvents==1){$jlog=1;}
            $f[]="";
            $this->ip_forward=1;
            $this->TFILTER[]="";
            $this->TFILTER[]="# Creating chain '$IN' under 'FORWARD' in table 'filter' LOG:{$jlog} [".__LINE__."]";
            $this->TFILTER[]="$iptables -t filter -N $IN";
            $this->TFILTER[]="";
            if($jlog==1){$f[]="$tfilterA $IN ".$this->RuleLogs("FORWARD_$ID")." || true";}
            $f[]="$tfilterA FORWARD -i $nicfrom -o $nicto $Comment -j $IN ";
            $f[]="";

            if($masquerade==1) {
                $f[]="";
                $f[]="# Masquerade packets outgoing $nicto";
                if($jlog==1){$f[]="$iptables -t nat -A POSTROUTING -o $nicto $ConntrackNew $Comment  ".$this->RuleLogs("MASQUERADE_$ID");}
                $f[] = "$iptables -t nat -A POSTROUTING -o $nicto $ConntrackNew $Comment -j MASQUERADE";
            }

            if($DenyDHCP==1) {
                $f[] = "";
                $f[] = "# Deny incoming DHCP.";
                if($jlog==1){
                    $f[] = "$tfilterA $IN -p udp --sport 68 --dport 67 $ctstateNew $Comment ".
                        $this->RuleLogs("FDENY_$ID");
                }
                $f[] = "$tfilterA $IN -p udp --sport 68 --dport 67 $ctstateNew $Comment -j SMART_REJECT";
            }


        }



        return @implode("\n",$f);


    }







	private function fillServices(){
		$this->services_array["AH"]=true;
		$this->services_array["artica"]=true;
		$this->services_array["all"]=true;
		$this->services_array["amanda"]=true;
		$this->services_array["any"]=true;
		$this->services_array["anystateless"]=true;
		$this->services_array["apcupsd"]=true;
		$this->services_array["apcupsdnis"]=true;
		$this->services_array["aptproxy"]=true;
		$this->services_array["asterisk"]=true;
		$this->services_array["echo"]=true;
		$this->services_array["emule"]=true;
		$this->services_array["eserver"]=true;
		$this->services_array["ESP"]=true;
		$this->services_array["iax"]=true;
		$this->services_array["iax2"]=true;
		$this->services_array["icmp"]=true;
		$this->services_array["ICMP"]=true;
		$this->services_array["icmpv6"]=true;
		$this->services_array["ICMPV6"]=true;
		$this->services_array["icp"]=true;
		$this->services_array["ident"]=true;
		$this->services_array["imap"]=true;
		$this->services_array["imaps"]=true;
		$this->services_array["ipsecnatt"]=true;
		$this->services_array["ipv6error"]=true;
		$this->services_array["ipv6neigh"]=true;
		$this->services_array["ipv6router"]=true;
		$this->services_array["irc"]=true;
		$this->services_array["isakmp"]=true;
		$this->services_array["microsoftds"]=true;
		$this->services_array["mms"]=true;
		$this->services_array["msn"]=true;
		$this->services_array["msnp"]=true;
		$this->services_array["msds"]=true;
		$this->services_array["multicast"]=true;
		$this->services_array["mysql"]=true;
		$this->services_array["upnp"]=true;
		$this->services_array["uucp"]=true;
		$this->services_array["finger"]=true;
		$this->services_array["jabber"]=true;
		$this->services_array["jabberd"]=true;
		$this->services_array["netbackup"]=true;
		$this->services_array["netbiosdgm"]=true;
		$this->services_array["netbiosns"]=true;
		$this->services_array["netbiosssn"]=true;
		$this->services_array["nfs"]=true;
		$this->services_array["nis"]=true;
		$this->services_array["nntp"]=true;
		$this->services_array["nntps"]=true;
		$this->services_array["nrpe"]=true;
		$this->services_array["ntp"]=true;
		$this->services_array["nut"]=true;
		$this->services_array["nxserver"]=true;
		$this->services_array["radius"]=true;
		$this->services_array["radiusold"]=true;
		$this->services_array["radiusoldproxy"]=true;
		$this->services_array["radiusproxy"]=true;
		$this->services_array["rdp"]=true;
		$this->services_array["rndc"]=true;
		$this->services_array["rsync"]=true;
		$this->services_array["rtp"]=true;
		$this->services_array["vmware"]=true;
		$this->services_array["vmwareauth"]=true;
		$this->services_array["vmwareweb"]=true;
		$this->services_array["vnc"]=true;
		$this->services_array["cups"]=true;
		$this->services_array["custom"]=true;
		$this->services_array["gift"]=true;
		$this->services_array["giftui"]=true;
		$this->services_array["gkrellmd"]=true;
		$this->services_array["GRE"]=true;
		$this->services_array["cvspserver"]=true;
		$this->services_array["openvpn"]=true;
		$this->services_array["oracle"]=true;
		$this->services_array["OSPF"]=true;
		$this->services_array["samba"]=true;
		$this->services_array["sane"]=true;
		$this->services_array["sip"]=true;
		$this->services_array["smtp"]=true;
		$this->services_array["smtps"]=true;
		$this->services_array["snmp"]=true;
		$this->services_array["snmptrap"]=true;
		$this->services_array["socks"]=true;
		$this->services_array["squid"]=true;
		$this->services_array["ssh"]=true;
		$this->services_array["stun"]=true;
		$this->services_array["submission"]=true;
		$this->services_array["sunrpc"]=true;
		$this->services_array["swat"]=true;
		$this->services_array["syslog"]=true;
		$this->services_array["webcache"]=true;
		$this->services_array["webmin"]=true;
		$this->services_array["whois"]=true;
		$this->services_array["darkstat"]=true;
		$this->services_array["daytime"]=true;
		$this->services_array["dcc"]=true;
		$this->services_array["dcpp"]=true;
		$this->services_array["dhcp"]=true;
		$this->services_array["dhcprelay"]=true;
		$this->services_array["dhcpv6"]=true;
		$this->services_array["dict"]=true;
		$this->services_array["distcc"]=true;
		$this->services_array["dns"]=true;
		$this->services_array["h323"]=true;
		$this->services_array["heartbeat"]=true;
		$this->services_array["http"]=true;
		$this->services_array["httpalt"]=true;
		$this->services_array["https"]=true;
		$this->services_array["hylafax"]=true;
		$this->services_array["l2tp"]=true;
		$this->services_array["ldap"]=true;
		$this->services_array["ldaps"]=true;
		$this->services_array["lpd"]=true;
		$this->services_array["ping"]=true;
		$this->services_array["pop3"]=true;
		$this->services_array["pop3s"]=true;
		$this->services_array["portmap"]=true;
		$this->services_array["postgres"]=true;
		$this->services_array["pptp"]=true;
		$this->services_array["privoxy"]=true;
		$this->services_array["telnet"]=true;
		$this->services_array["tftp"]=true;
		$this->services_array["time"]=true;
		$this->services_array["timestamp"]=true;
		$this->services_array["tomcat"]=true;
		$this->services_array["xbox"]=true;
		$this->services_array["xdmcp"]=true;
		$this->services_array["ntopng"]=true;
		$this->services_array["ss5"]=true;

		ksort($this->services_array);
		}



	private function IPSET_ALL_IPS_LIST(){
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){$unix=new unix();
		$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}
		$IPADDRS=$GLOBALS["NETWORK_ALL_INTERFACES"];
        $FF=array();
        $FF_1=array();
		foreach ($IPADDRS as $interface=>$dr){
			$IPADDRZ=$dr["IPADDR"];
            $FF[$IPADDRZ]=$IPADDRZ;
		}


        foreach ($FF as $net=>$none){
			$FF_1[]=$net;

		}

		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ".count($FF_1)." MyIPs items.\n";
		@file_put_contents("/etc/firehol/MyIPs", @implode("\n", $FF_1));
		@file_put_contents("/usr/local/etc/firehol/MyIPs",@implode("\n", $FF_1));


	}

	private function transparent_proxy_redsocks(){
		$EnableRedSocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedSocks"));
		if($EnableRedSocks==0){return null;}
		$unix=new unix();
		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$sql="SELECT * FROM `redsocks` WHERE enabled=1 AND transparentmethod=1 ORDER BY zorder";
		$results=$q->QUERY_SQL($sql);
		$CONF=array();

		$CONF[]="# Tproxy for redsosk ".count($results)." service(s)";

		foreach ($results as $index=>$ligne){
			$ID=$ligne["ID"];

			$transparentin=$ligne["transparentin"];
			$transparentout=$ligne["transparentout"];


			$dest_port=intval($ligne["redsocks_port"]);

			$inface=$ligne["listen_interface"];
			$transparentport=trim(base64_decode($ligne["transparentport"]));
			$transparentin=trim(base64_decode($ligne["transparentin"]));
			$excludetransparentin=trim(base64_decode($ligne["excludetransparentin"]));

			$transparentout=trim(base64_decode($ligne["transparentout"]));
			$excludetransparentout=trim(base64_decode($ligne["excludetransparentout"]));


			if($transparentport==null){continue;}
			$sZdestinationsports=explode("\n",$transparentport);
			$sZdestinationsportsTXT=trim(@implode(" ", $sZdestinationsports));

			$sZtransparentin=explode("\n",$transparentin);
			$sZtransparentinTXT=trim(@implode(" ", $sZtransparentin));

			$sZexcludetransparentin=explode("\n",$excludetransparentin);
			$sZexcludetransparentinTXT=trim(@implode(" ", $sZexcludetransparentin));

			$sZtransparentout=explode("\n",$transparentout);
			$sZtransparentoutTXT=trim(@implode(" ", $sZtransparentout));

			$sZexcludetransparentout=explode("\n",$excludetransparentout);
			$sZexcludetransparentoutTXT=trim(@implode(" ", $sZexcludetransparentout));

			$cmd=array();
			if($sZdestinationsportsTXT==null){continue;}

			$inface_ip=$unix->InterfaceToIPv4($inface);


			$cmd[]="tproxy";
			$cmd[]="\"".@implode(" ", $sZdestinationsports)."\"";
			$cmd[]="port $dest_port";
			if($inface_ip<>null){$cmd[]="ip $inface_ip";}else{$cmd[]="ip 127.0.0.1";}


			if($inface<>null){$cmd[]="inface $inface";}

			if($sZtransparentinTXT<>null){
				$cmd[]="src4 \"$sZtransparentinTXT\"";
			}

			if($sZexcludetransparentinTXT<>null){
				$cmd[]="src4 not \"$sZexcludetransparentinTXT\"";
			}

			if($sZtransparentoutTXT<>null){
				$cmd[]="dst4 \"$sZtransparentoutTXT\"";
			}
			if($sZexcludetransparentoutTXT<>null){
				$cmd[]="dst4 not \"$sZexcludetransparentoutTXT\"";
			}

			$CONF[]=@implode(" ", $cmd);
		}

		return @implode("\n", $CONF);




	}

	private function transparent_redsocks(){
		$EnableRedSocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedSocks"));
		if($EnableRedSocks==0){return null;}
		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$sql="SELECT * FROM `redsocks` WHERE enabled=1 AND transparentmethod=0 ORDER BY zorder";
		$results=$q->QUERY_SQL($sql);
		$CONF=array();


		foreach ($results as $index=>$ligne){
			$dest_port=intval($ligne["redsocks_port"]);
			$inface=$ligne["listen_interface"];
			$transparentport=trim(base64_decode($ligne["transparentport"]));
			$transparentin=trim(base64_decode($ligne["transparentin"]));
			$excludetransparentin=trim(base64_decode($ligne["excludetransparentin"]));

			$transparentout=trim(base64_decode($ligne["transparentout"]));
			$excludetransparentout=trim(base64_decode($ligne["excludetransparentout"]));


			if($transparentport==null){continue;}
			$sZdestinationsports=explode("\n",$transparentport);
			$sZdestinationsportsTXT=trim(@implode(" ", $sZdestinationsports));

			$sZtransparentin=explode("\n",$transparentin);
			$sZtransparentinTXT=trim(@implode(" ", $sZtransparentin));

			$sZexcludetransparentin=explode("\n",$excludetransparentin);
			$sZexcludetransparentinTXT=trim(@implode(" ", $sZexcludetransparentin));

			$sZtransparentout=explode("\n",$transparentout);
			$sZtransparentoutTXT=trim(@implode(" ", $sZtransparentout));

			$sZexcludetransparentout=explode("\n",$excludetransparentout);
			$sZexcludetransparentoutTXT=trim(@implode(" ", $sZexcludetransparentout));

			$cmd=array();
			if($sZdestinationsportsTXT==null){continue;}


			$cmd[]="transparent_proxy4";
			$cmd[]="\"".@implode(" ", $sZdestinationsports)."\"";
			$cmd[]="$dest_port";
			$cmd[]="\"root\"";


			if($inface<>null){$cmd[]="inface $inface";}

			if($sZtransparentinTXT<>null){
				$cmd[]="src4 \"$sZtransparentinTXT\"";
			}

			if($sZexcludetransparentinTXT<>null){
				$cmd[]="src4 not \"$sZexcludetransparentinTXT\"";
			}

			if($sZtransparentoutTXT<>null){
				$cmd[]="dst4 \"$sZtransparentoutTXT\"";
			}
			if($sZexcludetransparentoutTXT<>null){
				$cmd[]="dst4 not \"$sZexcludetransparentoutTXT\"";
			}

			$CONF[]=@implode(" ", $cmd);
		}

		return @implode("\n", $CONF);

	}



	private function transparent_3proxy(){
		$Enable3Proxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enable3Proxy"));
		if($Enable3Proxy==0){return null;}
		$unix=new unix();
		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$sql="SELECT * FROM `3proxy_services` WHERE enabled=1 AND transparent=1 ORDER BY zorder";
		$results=$q->QUERY_SQL($sql);
		$CONF=array();

		foreach ($results as $index=>$ligne){
			$ID=$ligne["ID"];
			$redsocks=intval($ligne["redsocks"]);
			$transparentin=$ligne["transparentin"];
			$transparentout=$ligne["transparentout"];
			$ipaddr_text=" ip 127.0.0.1";
			$dest_port=intval($ligne["listen_port"]);
			$redsocks_port=intval($ligne["redsocks_port"]);
			$inface=$ligne["listen_interface"];
			$transparentport=trim(base64_decode($ligne["transparentport"]));
			$transparentin=trim(base64_decode($ligne["transparentin"]));
			$excludetransparentin=trim(base64_decode($ligne["excludetransparentin"]));

			$transparentout=trim(base64_decode($ligne["transparentout"]));
			$excludetransparentout=trim(base64_decode($ligne["excludetransparentout"]));

			if($redsocks==1){$dest_port=$redsocks_port;}



			if($transparentport==null){continue;}
			$sZdestinationsports=explode("\n",$transparentport);
			$sZdestinationsportsTXT=trim(@implode(" ", $sZdestinationsports));

			$sZtransparentin=explode("\n",$transparentin);
			$sZtransparentinTXT=trim(@implode(" ", $sZtransparentin));

			$sZexcludetransparentin=explode("\n",$excludetransparentin);
			$sZexcludetransparentinTXT=trim(@implode(" ", $sZexcludetransparentin));

			$sZtransparentout=explode("\n",$transparentout);
			$sZtransparentoutTXT=trim(@implode(" ", $sZtransparentout));

			$sZexcludetransparentout=explode("\n",$excludetransparentout);
			$sZexcludetransparentoutTXT=trim(@implode(" ", $sZexcludetransparentout));

			$cmd=array();
			if($sZdestinationsportsTXT==null){continue;}


			$cmd[]="transparent_proxy4";
			$cmd[]="\"".@implode(" ", $sZdestinationsports)."\"";
			$cmd[]="$dest_port";
			$cmd[]="\"root\"";


			if($inface<>null){$cmd[]="inface $inface";}

			if($sZtransparentinTXT<>null){
				$cmd[]="src4 \"$sZtransparentinTXT\"";
			}

			if($sZexcludetransparentinTXT<>null){
				$cmd[]="src4 not \"$sZexcludetransparentinTXT\"";
			}

			if($sZtransparentoutTXT<>null){
				$cmd[]="dst4 \"$sZtransparentoutTXT\"";
			}
			if($sZexcludetransparentoutTXT<>null){
				$cmd[]="dst4 not \"$sZexcludetransparentoutTXT\"";
			}

			$CONF[]=@implode(" ", $cmd);
		}

		return @implode("\n", $CONF);

	}











	private function APP_RSYNCD($Interface){
		$EnableRsyncDaemon=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/EnableRsyncDaemon'));
		if($EnableRsyncDaemon==0){return false;}
		$RsyncInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncInterface"));
		if($RsyncInterface==null){return true;}
		if($RsyncInterface==$Interface){return true;}
		return false;
	}
	private function APP_SSHD($Interface){
		$EnableOpenSSH=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/EnableOpenSSH'));
		if($EnableOpenSSH==0){return false;}
		$SSHDInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDInterface"));
		if($SSHDInterface==null){return true;}
		if($SSHDInterface==$Interface){return true;}
		return false;
	}




	private function APP_SAMBA($Interface){
		$EnableSamba=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSamba"));
		if($EnableSamba==0){return false;}
		$SambaInterfaces=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaInterfaces"));
		if(trim($SambaInterfaces)==null){return true;}
		if(strpos(" $SambaInterfaces ", $Interface)>0){return true;}
	}


    public function extract_ports($servicename=null,$ruleid=0):array{
        $FINAL_RULES=array();
        $services=array();

        if($GLOBALS["VERBOSE"]){echo "extract_ports($servicename,$ruleid)\n";}
        $q = new lib_sqlite("/home/artica/SQLITE/firewall.db");

        if ($servicename <> null) {
            $ligne = $q->mysqli_fetch_array("SELECT enabled,server_port 
                     FROM firehol_services_def WHERE service='$servicename'");

            if(!$q->ok){
                if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";}
            }
            $enabled = intval($ligne["enabled"]);
            $log="# ($ruleid) $servicename {$ligne["server_port"]} Enabled:$enabled [" . __LINE__ . "]";
            if($GLOBALS["VERBOSE"]){echo "$log\n";}

            if ($this->DEBUG_COMPILE) {
                $this->dump_object_ev[] = $log;
            }
            if ($enabled == 1) {
                $services = explode(" ", $ligne["server_port"]);
            }

        }

        if ($ruleid > 0) {

            $ligne = $q->mysqli_fetch_array("SELECT services_container FROM 
                                              iptables_main WHERE ID='$ruleid'");
            $zMAIN = unserialize(base64_decode($ligne["services_container"]));
            if($GLOBALS["VERBOSE"]){echo "services_container = ".count($zMAIN)." items in line ".__LINE__."\n";}
            foreach ($zMAIN as $servicename => $none) {
                if($GLOBALS["VERBOSE"]){echo "Found service: ".$servicename." in line ".__LINE__."\n";};
                $servicename = trim($servicename);
                if ($servicename == null) {
                    continue;
                }
                $ligne = $q->mysqli_fetch_array("SELECT enabled,server_port 
                     FROM firehol_services_def WHERE service='$servicename'");
                $enabled = intval($ligne["enabled"]);
                if ($enabled == 1) {
                    if ($this->DEBUG_COMPILE) {
                        $this->dump_object_ev[] = "# ($ruleid) $servicename {$ligne["server_port"]} Enabled:$enabled";
                    }
                    if($GLOBALS["VERBOSE"]){echo "Found ports: ".$ligne["server_port"]." in line ".__LINE__."\n";};
                    $services2 = explode(" ", $ligne["server_port"]);
                    if (count($services2) > 0) {
                        if($GLOBALS["VERBOSE"]){echo "Merge ports: ".$ligne["server_port"]." in line ".__LINE__."\n";};
                        $services = array_merge($services, $services2);
                    }else{
                        $services[]=$ligne["server_port"];
                    }
                }
            }

        }
        if($GLOBALS["VERBOSE"]){echo "Parsing: ".count($services)." services in line ".__LINE__."\n";};


        foreach ($services as $line){
            $line=trim($line);
            if($line==null){continue;}
            if(!preg_match("#^(.+?)\/(.+)#",$line,$re)){
                if($GLOBALS["VERBOSE"]){echo "$line did not matches in line ".__LINE__."\n";};
                continue;}

            $PROTO=strtolower($re[1]);
            $PORTS=trim(strtolower($re[2]));

            if(is_numeric($PROTO)){
                $old_proto=$PROTO;
                $PROTO=$PORTS;
                $PORTS=$old_proto;
            }

            $PORTS=str_replace("any","1:65535",$PORTS);
            if($GLOBALS["VERBOSE"]){echo "Found service: $PROTO --> $PORTS in line ".__LINE__."\n";};
            $MAIN[$PROTO][$PORTS]=true;
        }

        foreach ($MAIN as $protos=>$dports){
            $trule=array();
            if($protos<>"any"){
                $trule[]="-p $protos";
            }
            if($protos=="icmp"){
                $FINAL_RULES[]=@implode(" ",$trule);
                continue;
            }

            if(count($dports)>1){
                $trule[]="-m multiport --destination-ports";
            }else{
                $trule[]="--dport";
            }
            $xports=array();
            foreach ($dports as $pNumber=>$none){$xports[]=$pNumber;}

            $trule[]=@implode(",",$xports);

            $FINAL_RULES[]=@implode(" ",$trule);

        }



        return $FINAL_RULES;

    }

	private function SMTP_INTERFACES(){
		$EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
		if($EnablePostfix==1){$this->POSTFIX=true;}else{return;}
		$PostfixBinInterfaces=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixBinInterfaces"));
		if($PostfixBinInterfaces<>null){
			$Interfaces=explode(",",$PostfixBinInterfaces);
			foreach ($Interfaces as $nic){
				$this->POSTFIX_INTERFACES[$nic]=true;
			}
			return;
		}

		$this->POSTFIX_INTERFACES["ALL"]=true;


	}
}