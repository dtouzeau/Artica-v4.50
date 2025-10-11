<?php


class dhcp_builder{
    private $conf       = array();
    private $subnet     = null;
    private $range1     = null;
    private $range2     = null;
    private $broadcast  = null;
    private $listen_nic = null;
    private $Default_interface_name=null;
    private $netmask    = null;
    private $ntp_server = null;
    private $pxe_enable = 0;
    private $pxe_file   = null;
    private $ping_check = 0;
    private $gateway    = null;
    private $routers    = null;
    private $DNS_1      = null;
    private $DNS_2      = null;
    private $WINS       = null;
    private $local_pac_server = null;
    private $AvailableClassesList   = null;
    public $EnableVLANs=0;
    public $vlan_id=0;

    private $deny_unkown_clients = 0;
    private $browser_portal_page = null;
    private $ddns_domainname = null;
    private $max_lease_time = 0;
    private $authoritative = 0;
    private $get_lease_hostnames = 0;
    private $do_no_verify_range = 0;
    private $InterfacesToBuild=array();


    public function __construct(){
    }



    public function Build(){

        if(!class_exists("unix")){
            if($GLOBALS["VERBOSE"]){
                echo "Class unix doesn't exists\n";return;
            }
        }

        if(!is_file("/var/lib/dhcp3/dhcpd.other")){@file_put_contents("/var/lib/dhcp3/dhcpd.other","");}
        if(is_file("/usr/share/artica-postfix/bin/dhcpdclient.sh")){@chmod("/usr/share/artica-postfix/bin/dhcpdclient.sh",0755);}
        @chmod("/var/lib/dhcp3/dhcpd.other",0755);




        $Directories[]="/home/artica/dhcpd/queue";
        $Directories[]="/var/lib/dhcp3";

        foreach ($Directories as $path) {
            if(!is_dir($path)) {@mkdir($path,0755,true);}
            if(is_dir($path)) {@chmod($path,0755);}

        }
        $this->GetWorkingInterfaces();
        $this->build_defaults();
        $this->PowerDNS();
        $this->build_interface("default");
        foreach ($this->InterfacesToBuild as $eth=>$none){
            if($eth==$this->Default_interface_name){continue;}
            $this->conf[]="# ************* Interface $eth *************";
            $this->build_interface($eth);

        }





        $this->conf[]="include \"/etc/dhcpd-reservations.conf\";";
        $this->conf[]="include \"/var/lib/dhcp3/dhcpd.other\";";

        if(!is_file("/var/lib/dhcp3/dhcpd.other")){
            @touch("/var/lib/dhcp3/dhcpd.other");
        }
        if(!is_file("/etc/dhcpd-reservations.conf")){
            @touch("/etc/dhcpd-reservations.conf");
        }

        return @implode("\n",$this->conf)."\n";


    }

    private function GetWorkingInterfaces(){

        $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
        $results=$q->QUERY_SQL("SELECT * FROM `dhcpd` WHERE `key`='listen_nic'");

        foreach ($results as $index=>$ligne){
            $Interface=$ligne["interface"];
            $listen_nic=$ligne["val"];

            if($Interface=="default"){continue;}

            $dhcpsocks=new dhcp_socks($Interface);
            $service_enabled=$dhcpsocks->GET("service_enabled");
            $eth=new system_nic($listen_nic);
            $interface_enabled=intval($eth->enabled);
            $this->conf[]="# Available Interface $Interface/$listen_nic Enabled:$service_enabled Interface Enabled:$interface_enabled";
            if($service_enabled==0){
                $this->conf[]="# $listen_nic -> Service is disabled";
                continue;}
            if($interface_enabled==0){
                $this->conf[]="# $listen_nic -> Interface is disabled";
                continue;}
            $this->conf[]="# $listen_nic -> added";
            $this->InterfacesToBuild[$listen_nic]=True;
        }

    }


    private function Loadinfos($interface="default"){
        $dhcp_socks=new dhcp_socks($interface);
        $this->ddns_domainname=$dhcp_socks->GET("ddns_domainname");
        $this->max_lease_time=$dhcp_socks->GET("max_lease_time");
        $this->listen_nic=$dhcp_socks->GET("listen_nic");
        $this->netmask=$dhcp_socks->GET("netmask");
        $this->range1=$dhcp_socks->GET("range1");
        $this->range2=$dhcp_socks->GET("range2");
        $this->EnableArticaAsDNSFirst=intval($dhcp_socks->GET("EnableArticaAsDNSFirst"));
        $this->gateway=$dhcp_socks->GET("gateway");
        $this->DNS_1=$dhcp_socks->GET("DNS_1");
        $this->DNS_2=$dhcp_socks->GET("DNS_2");
        $this->pxe_server=$dhcp_socks->GET("pxe_server");
        $this->pxe_file=$dhcp_socks->GET("pxe_file");
        $this->pxe_enable=$dhcp_socks->GET("pxe_enable");
        $this->ntp_server=$dhcp_socks->GET("ntp_server");
        $this->subnet=$dhcp_socks->GET("subnet");
        $this->broadcast=$dhcp_socks->GET("broadcast");
        $this->WINS=$dhcp_socks->GET("WINS");
        $this->authoritative=$dhcp_socks->GET("authoritative");
        $this->local_pac_server=$dhcp_socks->GET("local_pac_server");
        $this->get_lease_hostnames=$dhcp_socks->GET("get_lease_hostnames");
        $this->deny_unkown_clients=intval($dhcp_socks->GET("deny_unkown_clients"));
        $this->browser_portal_page=$dhcp_socks->GET("browser_portal_page");
        $this->do_no_verify_range=intval($dhcp_socks->GET("do_no_verify_range"));
        $this->ping_check=trim($dhcp_socks->GET("ping_check"));
        $this->service_enabled=intval($dhcp_socks->GET("service_enabled"));
        $this->routers=trim($dhcp_socks->GET("routers"));
        if(!is_numeric($this->pxe_enable)){$this->pxe_enable=0;}
        $this->EnableVLANs=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVLANs");
        if($this->EnableVLANs==1){
            $this->vlan_id=$dhcp_socks->GET("vlan_id");
        }
        $SUBNET_1=null;
        $SUBNET_2=null;
        $SUBNET_3=null;

        if($this->broadcast==null){
            if(class_exists("unix")) {
                $unix = new unix();
                $eth_IP = $unix->InterfaceToIPv4($this->listen_nic);
                preg_match("#^([0-9\.]+)\.([0-9]+)$#", $eth_IP, $re);
                $this->broadcast = "{$re[1]}.255";
            }

        }
        if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->subnet),$re)) {
            $SUBNET_1 = intval($re[1]);
            $SUBNET_2 = intval($re[2]);
            $SUBNET_3 = intval($re[3]);
        }



        if($this->do_no_verify_range==0){
            if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->range1,$re)){
                $RANGE_1=$re[1];
                $RANGE_2=$re[2];
                $RANGE_3=$re[3];
                $RANGE_4=$re[4];
                if(is_numeric($SUBNET_1)){
                    if($SUBNET_1<>$RANGE_1){$RANGE_1=$SUBNET_1;}
                    if($SUBNET_2<>$RANGE_2){$RANGE_2=$SUBNET_2;}
                    if($SUBNET_3<>$RANGE_3){$RANGE_3=$SUBNET_3;}
                    $this->range1="$RANGE_1.$RANGE_2.$RANGE_3.$RANGE_4";
                }

            }

            if(!preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->range2,$re)){
                $this->range2="$SUBNET_1.$SUBNET_2.$SUBNET_3.254";
            }else{
                $RANGE_1=$re[1];
                $RANGE_2=$re[2];
                $RANGE_3=$re[3];
                $RANGE_4=$re[4];
                if(is_numeric($SUBNET_1)){
                    if($SUBNET_1<>$RANGE_1){$RANGE_1=$SUBNET_1;}
                    if($SUBNET_2<>$RANGE_2){$RANGE_2=$SUBNET_2;}
                    if($SUBNET_3<>$RANGE_3){$RANGE_3=$SUBNET_3;}
                    $this->range2="$RANGE_1.$RANGE_2.$RANGE_3.$RANGE_4";
                }
            }

        }


    }


    private function build_defaults(){
        $this->conf[]="option rfc3442-classless-static-routes code 121 = array of integer 8;";
        $this->conf[]="option ms-classless-static-routes code 249 = array of integer 8;";
        //$this->conf[]="option classless-routes code 121 = array of unsigned integer 8;";
        //$this->conf[]="option classless-routes-win code 249 = array of unsigned integer 8;";

        if($this->pxe_enable==1){
            $this->conf[] = "option option-128 code 128 = string;";
            $this->conf[] = "option option-129 code 129 = text;";

        }

        $DHCPDOmApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDOmApi"));
        if(strlen($DHCPDOmApi)>5){
            $this->conf[]="omapi-port 7911;";
            $this->conf[]="key omapi_key {";
            $this->conf[]="\talgorithm HMAC-MD5;";
            $this->conf[]="\tsecret \"$DHCPDOmApi\";";
            $this->conf[]="}";
            $this->conf[]="";
            $this->conf[]="omapi-key omapi_key;";
            $this->conf[]="";
        }

        $this->BuildClasses();


    }

    private function KeyToMAc($MainKey){

        if(!preg_match("#^([A-Za-z0-9]{2})([A-Za-z0-9]{2})([A-Za-z0-9]{2})#",$MainKey,$re)){
            $this->conf[]="# $MainKey no matches, skip";
            return null;
        }
        return strtolower("{$re[1]}:{$re[2]}:{$re[3]}");
    }

    private function prefixMac($MainKey){

        if(!preg_match("#^([A-Za-z0-9]{2})([A-Za-z0-9]{2})([A-Za-z0-9]{2})#",strtolower($MainKey),$re)){
            $this->conf[]="# $MainKey no matches, skip";
           return null;
        }

        if(preg_match("#^[0-9]+$#",$re[1])){$re[1]=intval($re[1]);}
        if(preg_match("#^[0-9]+$#",$re[2])){$re[2]=intval($re[2]);}
        if(preg_match("#^[0-9]+$#",$re[3])){$re[3]=intval($re[3]);}

        if(preg_match("#^0([a-z]+)$#",$re[1],$ri)){$re[1]=$ri[1];}
        if(preg_match("#^0([a-z]+)$#",$re[2],$ri)){$re[2]=$ri[1];}
        if(preg_match("#^0([a-z]+)$#",$re[3],$ri)){$re[3]=$ri[1];}

        return "{$re[1]}:{$re[2]}:{$re[3]}";
    }


    private function BuildClasses_hardware_items($hardware_id){
        $q              = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
        $results=$q->QUERY_SQL("SELECT dhcpd_item_id FROM dhcpd_hardware_list WHERE dhcpd_hardware_id=$hardware_id");
        $f=array();

        $TT=array();
        foreach ($results as $index=>$ligne){
            $macprefix          = null;
            $dhcpd_item_id      = intval($ligne["dhcpd_item_id"]);
            $ligne2             = $q->mysqli_fetch_array("SELECT * FROM dhcpd_MacsList WHERE ID=$dhcpd_item_id");
            $dhcpd_class        = trim($ligne2["MacID"]);

            if($dhcpd_class==null){
                $this->conf[]="# $dhcpd_item_id no Key, aborting";
                continue;
            }

            //$KeyToMAc=$this->KeyToMAc($dhcpd_class);
            $macprefix=$this->prefixMac($dhcpd_class);
            $TT[]="\t\t( binary-to-ascii (16,8,\":\",substring(hardware, 0, 4)) = \"1:{$macprefix}\" )";


        }

        $this->conf[]="# Class $hardware_id ".count($TT)." hardwares in collection";
        if(count($TT)==0){return array();}
        $f[]="match if ".@implode(" or\n",$TT).";";
        return $f;

    }


    private function BuildClasses(){
        $q              = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
        $results        =$q->QUERY_SQL("SELECT ID,`PooName` FROM dhcpd_hardware WHERE enabled=1");
        $this->conf[]="# Hardwares ".count($results)." rules.";


        foreach ($results as $index=>$ligne){
            $PoolID=$ligne["ID"];
            $Items=$this->BuildClasses_hardware_items($PoolID);
            $CountOfItems=count($Items);
            $this->conf[]="\n# Class for hardware '{$ligne["PooName"]}' BuildClasses_hardware_items report $CountOfItems item(s)";
            if($CountOfItems==0){
                $q->QUERY_SQL("UPDATE dhcpd_hardware SET enabled=0 WHERE ID=$PoolID");
                continue;
            }
            $this->AvailableClassesList["Pool{$PoolID}"]=true;

            $this->conf[]="class \"Pool{$PoolID}\" {";
            //$this->conf[]="\tlog(info, binary-to-ascii (16,8,\":\",substring(hardware, 0, 4)));";
            $this->conf[]=@implode("\n",$Items);
            $this->conf[]="}\n";
            
        }
    }

    function build_subclasses($interface){
        $dhp_socks      = new dhcp_socks();
        $q              = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
        $results        = $q->QUERY_SQL("SELECT * FROM dhcpd_hardware WHERE interface='$interface' AND enabled=1");
        if(!$q->ok){$this->conf[]="# dhcpd_hardware SQL Error;";}
        $this->conf[]="# ".count($results)." SubClass(es) for $interface";
        if(count($results)==0){
            $this->conf[]="# No SubClass for $interface";
            return false;}


        foreach ($results as $index=>$ligne){
            $PooName        = $ligne["PooName"];
            $this->conf[]="# $PooName SubClass(es) in $interface";
            $PoolID         = $ligne["ID"];
            $range_from     = $ligne["range_from"];
            $range_to       = $ligne["range_to"];
            $maxleasetime   = intval($ligne["max-lease-time"]);
            $DNS1           = $ligne["DNS1"];
            $DNS2           = $ligne["DNS2"];
            $ntp_server     = trim($ligne["ntp_server"]);

            $namesServers=array();
            if(preg_match("#^([0-9\.]+)#",$DNS1)){$namesServers[]=$DNS1;}
            if(preg_match("#^([0-9\.]+)#",$DNS2)){$namesServers[]=$DNS2;}

            $namesServers_final=$dhp_socks->CleanIpaddr1(@implode(",",$namesServers));

            $this->conf[]="\tpool {";

            $this->conf[]="\t\trange $range_from $range_to;";
            if($maxleasetime>5) {
                $this->conf[] = "\t\tmax-lease-time $maxleasetime;";
            }
            if($ntp_server<>null){
                $this->conf[] = "\t\toption ntp-servers $ntp_server;";
            }
            if($namesServers_final<>null){
                $this->conf[] = "\t\toption domain-name-servers $namesServers_final;";

            }

            $this->conf[]="\t\tallow members of \"Pool{$PoolID}\";";
            $this->conf[]="\tlog(info, \"Matches $PooName\");";
            $this->conf[]="\t}";

	}



    }



    private function build_interface($Interface){

        $this->Loadinfos($Interface);
        $dhp_socks=new dhcp_socks($Interface);


        $this->conf[]="# Interface $Interface";
        $this->conf[]="# VlanEnabled $this->EnableVLANs";
        $this->conf[]="# Choosed VLAN Interface [$this->vlan_id]";
        $this->conf[] = "#";
        $this->conf[] = "#\tPortal page \"$this->browser_portal_page\" For $Interface";
        if(preg_match("#http.*:\/\/.*#", $this->browser_portal_page)){
            // 114 remplace 226
            $this->conf[] = "option browser-portal-page code 114 = string;";
            $this->conf[] = "option browser-portal-page  \"$this->browser_portal_page\";";
            $this->conf[] = "option default-url \"$this->browser_portal_page\";";

        }

        if(preg_match("#http.*:\/\/.*#", $this->local_pac_server)){
            $this->conf[] = "\toption local-pac-server code 252 = text;";
            $this->conf[] = "\toption local-pac-server \"$this->local_pac_server\";";
        }


        $this->conf[]="subnet $this->subnet netmask $this->netmask {";

        $this->Default_interface_name=$this->listen_nic;
        if($this->EnableVLANs==1 && $this->vlan_id>0){
            $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
            $sql="SELECT * FROM nics_vlan WHERE ID=$this->vlan_id";
            $results=$q->mysqli_fetch_array($sql);
            $vlanid=$results["vlanid"];
            $nic_src=$results["nic"];
            $this->conf[]="# VLAN ID $vlanid Source Interface $nic_src";
            if($nic_src==$this->listen_nic) {
                $this->listen_nic = "vlan{$results["ID"]}";
            }

        }

        $this->conf[]="\tinterface $this->listen_nic;";
        $this->conf[]="\tmax-lease-time $this->max_lease_time;";
        $this->conf[]="\tdefault-lease-time $this->max_lease_time;";

        if($this->ddns_domainname<>null){
            $this->conf[]="\tddns-domainname \"$this->ddns_domainname\";";
        }

        if($this->authoritative==1){
            $this->conf[]="\tauthoritative;";
        }else{
            $this->conf[]="\tnot authoritative;";
        }


        if($this->get_lease_hostnames==1){
            $this->conf[]="\tget-lease-hostnames true;";
        }else{
            $this->conf[]="\tget-lease-hostnames false;";
        }

        if($this->ping_check==1){
            $this->conf[]="\tping-check = 1;";
        }else{
            $this->conf[]="\tping-check = 0;";
        }






        $this->conf[]="\trange $this->range1 $this->range2;";

        if($this->broadcast<>"0.0.0.0") {
            if (preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#', trim($this->broadcast), $re)) {
                $this->conf[] = "\toption broadcast-address {$re[1]}.{$re[2]}.{$re[3]}.{$re[4]};";
            }
        }

        if($this->ntp_server<>null){
            $this->conf[] = "\toption ntp-servers $this->ntp_server;";

        }

        if($this->deny_unkown_clients==1){
            $this->conf[] = "\tdeny unknown-clients;";
        }else{
            $this->conf[] = "\tallow unknown-clients;";
        }

        if($this->pxe_enable==1){
            $this->conf[] = "\tallow booting;";
            $this->conf[] = "\tallow bootp;";


            if($this->pxe_file<>null){
                $this->conf[] = "\tfilename \"$this->pxe_file\";";
                if($this->pxe_server<>null){
                    $this->conf[] = "\tnext-server $this->pxe_server;";
                }
            }
        }

        $routers[]=$this->gateway;
        $routers[]=$this->routers;

        $namesServers[]=$this->DNS_1;
        $namesServers[]=$this->DNS_2;
        $namesServers_final=$dhp_socks->CleanIpaddr1(@implode(",",$namesServers));
        $routers_final=$dhp_socks->CleanIpaddr1(@implode(",",$routers));
        if($routers_final<>null) {
            $this->conf[] = "\toption routers $routers_final;";
        }
        if($namesServers_final<>null){
            $this->conf[] = "\toption domain-name-servers $namesServers_final;";

        }

        if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->WINS)){
            $this->conf[] = "\toption netbios-name-servers $this->WINS;\n";
        }





        $this->build_routing_interface($Interface);
        $this->build_subclasses($Interface);




        $this->conf[]="\ton commit {";
        $this->conf[]="\t\tset clip = binary-to-ascii(10, 8, \".\", leased-address);";
        $this->conf[]="\t\tset clhw = binary-to-ascii(16, 8, \":\", substring(hardware, 1, 6));";
        $this->conf[]="\t\tset ClientName = pick-first-value(option fqdn.hostname,option host-name,config-option host-name, noname);";
        $this->conf[]="\t\texecute(\"/usr/share/artica-postfix/bin/dhcpdclient.sh\", \"commit\", clip, clhw, ClientName);";
        $this->conf[]="\t}";
        $this->conf[]="\ton release {";
        $this->conf[]="\t\tset clip = binary-to-ascii(10, 8, \".\", leased-address);";
        $this->conf[]="\t\tset clhw = binary-to-ascii(16, 8, \":\", substring(hardware, 1, 6));";
        $this->conf[]="\t\tset ClientName = pick-first-value(option fqdn.hostname,option host-name,config-option host-name, noname);";
        $this->conf[]="\t\texecute(\"/usr/share/artica-postfix/bin/dhcpdclient.sh\", \"release\", clip, clhw, ClientName);";
        $this->conf[]="\t}";

        $this->conf[]="}";




    }

    private function build_routing_interface($interface=null){
        $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");

        if(!$q->FIELD_EXISTS("dhcpd_routes","zOrder")){
            $q->QUERY_SQL("ALTER TABLE dhcpd_routes ADD zOrder INTEGER NOT NULL DEFAULT 1");
        }

        $C=0;
        if($interface==null){$interface="default";}

        $sql="SELECT *  FROM `dhcpd_routes` WHERE interface='$interface' ORDER BY zOrder";
        $results = $q->QUERY_SQL($sql);
        if(count($results)==0){return null;}
        $TRCLASS=null;$ligne=null;
        $ppatrn=array();
        foreach ($results as $index=>$ligne){


            $network=$ligne["network"];
            $gateway=$ligne["gateway"];

            if(!preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#',$gateway,$re)){
                $this->conf[] = "# Routing: gateway: $gateway bad format";
                continue;
            }

            $gatewayvirg="{$re[1]}, {$re[2]}, {$re[3]}, {$re[4]}";


            if(!preg_match("#([0-9\.]+)\/([0-9]+)#",$network,$re)){
                $this->conf[] = "# Routing: $network bad format";
                continue;
            }

            $NetMaskIP=trim($re[1]);
            $CDIR=intval($re[2]);
            $C++;

            if(!preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#',$NetMaskIP,$re)){
                $this->conf[] = "# Routing: $NetMaskIP bad format";
                continue;
            }

            if( ($CDIR==24) OR ($CDIR==23) OR ($CDIR==22) ){
                $line="$CDIR, {$re[1]}, {$re[2]}, {$re[3]}, $gatewayvirg";
                $ppatrn[$line]=$line;
                continue;

            }

            if($CDIR==16){
                $line="$CDIR, {$re[1]}, {$re[2]}, $gatewayvirg";
                $ppatrn[$line]=$line;
                continue;

            }
            if($CDIR==8){
                $line="$CDIR, {$re[1]}, $gatewayvirg ";
                $ppatrn[$line]=$line;
                continue;

            }
            if($CDIR==0){
                $line="$CDIR, $gatewayvirg ";
                $ppatrn[$line]=$line;
                continue;

            }


            if($CDIR==32){
                $line="$CDIR, {$re[1]}, {$re[2]}, {$re[3]}, {$re[4]}";
                $ppatrn[$line]=$line;
                continue;

            }

            $this->conf[] = "# Routing: CDIR: $CDIR Not supported";

        }

        if(count($ppatrn)==0){return;}
        if(!preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#',$this->gateway,$re)){
             $line="0, {$re[1]}, {$re[2]}, {$re[3]}, {$re[4]}";
             $ppatrn[$line]=$line;
        }

        $final=array();
        foreach ($ppatrn as $net=>$none){
            $net=trim($net);
            if($net==null){continue;}
            $final[]=$net;

        }

        if(count($final)==null){return null;}
        $zImplode=implode(",  ",$final);
        $this->conf[]="\toption rfc3442-classless-static-routes $zImplode;";
        $this->conf[]="\toption ms-classless-static-routes $zImplode;";

    }



    private function PowerDNS(){
        $DHCPDInPowerDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInPowerDNS"));
        if($DHCPDInPowerDNS==0){
            $this->conf[]="ddns-update-style none;";
            return;
        }
        $DHCPDDDNSKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDDDNSKey"));
        if(strlen($DHCPDDDNSKey)<5){
            $this->conf[]="# DHCPDDDNSKey wrong key";
            $this->conf[]="ddns-update-style none;";
            return;
        }

        $dhcp_sock=new dhcp_socks("default");
        if(trim($dhcp_sock->ddns_domainname)==null){
            $dhcp_sock->ddns_domainname="lan.local";
        }


        $re=$this->ExplodeMyRange();

        $this->conf[]="ddns-rev-domainname \"in-addr.arpa.\";";
        $this->conf[]="ddns-updates on;";
        $this->conf[]="ddns-update-style interim;";
        $this->conf[]="update-static-leases on;";
        $this->conf[]="";
        $this->conf[]="key \"dhcpdupdate\" {";
        $this->conf[]="\talgorithm hmac-md5;";
        $this->conf[]="\tsecret \"$DHCPDDDNSKey\";";
        $this->conf[]="};";
        $this->conf[]="";
        $this->conf[]="zone $dhcp_sock->ddns_domainname {";
        $this->conf[]="\tprimary 127.0.0.154;";
        $this->conf[]="\tkey dhcpdupdate;";
        $this->conf[]="}";
        $this->conf[]="";
        $this->conf[]="zone {$re[3]}.{$re[2]}.{$re[1]}.in-addr.arpa. {";
        $this->conf[]="\tprimary 127.0.0.154;";
        $this->conf[]="\tkey dhcpdupdate;";
        $this->conf[]="}";


    }

    private function ExplodeMyRange($interface="default"){

        if($interface<>"default") {
            $dhcp_sock = new dhcp_socks($interface);
            $this->subnet = $dhcp_sock->GET("subnet");
            $this->range1 = $dhcp_sock->GET("range1");
            $this->broadcast = $dhcp_sock->GET("broadcast");
        }

        if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->subnet),$re)){return $re;}
        if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->range1),$re)){return $re;}
        if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->broadcast),$re)){return $re;}
    }

}
