<?php
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
include_once(dirname(__FILE__) . '/class.mysql.inc');

class keepalived_global_settings
{
    public $log_detail = 0;

    function __construct()
    {
        $this->load();

    }

    private function load()
    {
        $this->log_detail = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("keepalived_log_detail"));

    }

    public function save()
    {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("keepalived_log_detail", $this->log_detail);
        $nodeAction = new nodeActions(0, 0, 0);
        $nodeAction->syncDebug();
        $this->CheckService();
        return true;
    }

    private function CheckService()
    {
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?restart=yes");
    }


}

class keepalives_primary_nodes
{
    public $state_array = array(
        "MASTER" => "MASTER",
        "BACKUP" => "BACKUP"
    );

    public $primary_node_id = null;
    public $primary_node_name = null;
    public $interface = null;
    public $primary_node_state = null;
    public $virtual_router_id = 1;
    public $priority = 100;
    public $advert_int = 1;
    public $auth_enable = 0;
    public $auth_pass = null;
    public $notifty_enable = 0;
    public $notifty = null;

    public $interval = 2;
    public $fall = 0;
    public $rise = 0;
    public $weight = 0;
    public $timeout = 0;
    public $nopreempt = 0;
    public $unicast_src_ip = 0;
    public $enable_peers_ttl = 0;
    public $max_peers_ttl = 0;
    public $min_peers_ttl = 0;
    public $enable = 0;
    public $last_id = null;
    public $isMaster = 0;
    public $primary_nodeIP = null;
    public $primary_nodePort = 0;
    public $primaryNodeID = 0;
    public $secondary_nodeIsDisconnected = 0;
    public $synckey = null;
    public $lastSync = null;

    public $testDom=null;
    public $testDomDNS= null;
    public $enableProxyCurl=1;
    public $disklimit=95;
    public $ramlimit=95;
    public $loadlimit=4;
    public $checkDisk=true;
    public $checkRam=true;
    public $checkLoad=true;
    public $dnstesttimeout=3;
    public $proxytesttimeout=3;
    public $checkDebugLevel=3;
    public $enableDnsResolver=true;
    public $use_vmac =0;
    public $vmac_xmit_base =0;


    function __construct($primary_node_id = null)
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","testDom")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD testDom TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","testDomDNS")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD testDomDNS TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","enableProxyCurl")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD enableProxyCurl TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","disklimit")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD disklimit TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","ramlimit")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD ramlimit TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","loadlimit")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD loadlimit TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","checkDisk")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD checkDisk TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","checkRam")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD checkRam TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","checkLoad")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD checkLoad TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","dnstesttimeout")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD dnstesttimeout TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","proxytesttimeout")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD proxytesttimeout TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","checkDebugLevel")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD checkDebugLevel TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","enableDnsResolver")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD enableDnsResolver TEXT");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","use_vmac")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD use_vmac INTEGER");
        }

        if(!$q->FIELD_EXISTS("keepalived_primary_nodes","vmac_xmit_base")){
            $q->QUERY_SQL("ALTER TABLE keepalived_primary_nodes ADD vmac_xmit_base INTEGER");
        }

        if ($primary_node_id <> null) {
            $this->primary_node_id = $primary_node_id;
            $this->load();
        }
    }

    public function lastSync()
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "SELECT last_sync from  keepalived_primary_nodes ORDER by ID DESC";
        $ligne = $q->mysqli_fetch_array($sql);
        return $ligne['last_sync'];
    }

    private function load()
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "SELECT * from keepalived_primary_nodes WHERE ID='$this->primary_node_id' ";
        $ligne = $q->mysqli_fetch_array($sql);

        $this->primary_node_id = intval($ligne['ID']);
        $this->primary_node_name = $ligne['primary_node_name'];
        $this->interface = $ligne['interface'];
        $this->primary_node_state = $ligne['state'];
        $this->virtual_router_id = intval($ligne['virtual_router_id']);
        $this->priority = intval($ligne['priority']);
        $this->advert_int = intval($ligne['advert_int']);
        $this->nopreempt = intval($ligne['nopreempt']);
        $this->unicast_src_ip = intval($ligne['unicast_src_ip']);
        $this->auth_enable = intval($ligne['auth_enable']);
        $this->auth_pass = $ligne['auth_pass'];
        $this->notifty_enable = intval($ligne['notifty_enable']);
        $this->notifty = $ligne['notifty'];
        $this->interval = intval($ligne['interval']);
        $this->fall = intval($ligne['fall']);
        $this->rise = intval($ligne['rise']);
        $this->weight = intval($ligne['weight']);
        $this->timeout = intval($ligne['timeout']);
        $this->enable = intval($ligne['enable']);
        $this->isPrimaryNode = intval($ligne['isPrimaryNode']);
        $this->primaryNodeIP = $ligne['primaryNodeIP'];
        $this->primaryNodePort = intval($ligne['primaryNodePort']);
        $this->primaryNodeID = intval($ligne['primaryNodeID']);
        $this->secondaryNodeIsDisconnected = intval($ligne['secondaryNodeIsDisconnected']);
        $this->synckey = $ligne['synckey'];
        $this->lastSync = $ligne['last_sync'];
        $this->enable_peers_ttl = intval($ligne['enable_peers_ttl']);
        $this->max_peers_ttl = intval($ligne['max_peers_ttl']);
        $this->min_peers_ttl = intval($ligne['min_peers_ttl']);

        if($ligne['testDom']==null){
            $ligne['testDom']="http://articatech.com";
        }
        $this->testDom=$ligne['testDom'];

        if($ligne['testDomDNS']==null){
            $ligne['testDomDNS']="cloudflare.com";
        }
        $this->testDomDNS=$ligne['testDomDNS'];
        $this->enableProxyCurl=intval($ligne['enableProxyCurl']);

        $this->disklimit=intval($ligne['disklimit']);
        $this->ramlimit=intval($ligne['ramlimit']);
        $this->loadlimit=intval($ligne['loadlimit']);
        $this->checkDisk=intval($ligne['checkDisk']);
        $this->checkRam=intval($ligne['checkRam']);
        $this->checkLoad=intval($ligne['checkLoad']);
        $this->dnstesttimeout=intval($ligne['dnstesttimeout']);
        $this->proxytesttimeout=intval($ligne['proxytesttimeout']);
        $this->checkDebugLevel=intval($ligne['checkDebugLevel']);
        $this->enableDnsResolver=intval($ligne['enableDnsResolver']);
        $this->use_vmac = intval($ligne["use_vmac"]);
        $this->vmac_xmit_base = intval($ligne["vmac_xmit_base"]);

    }

    public function save($reload = true, $updateMasterID = true)
    {
        $this->primary_node_name = trim(strtolower($this->primary_node_name));
        $this->primary_node_name = replace_accents($this->primary_node_name);
        $this->primary_node_name = str_replace(" ", "_", $this->primary_node_name);
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $t = time();
        if ($this->primary_node_id == 0) {

            $sql = "INSERT INTO keepalived_primary_nodes (primary_node_name,interface,state,virtual_router_id,priority,advert_int,nopreempt,unicast_src_ip,enable_peers_ttl,max_peers_ttl,min_peers_ttl,auth_enable,auth_type,auth_pass,notifty_enable,notifty,interval,fall,rise,weight,timeout,enable,isPrimaryNode,primaryNodeIP,primaryNodePort,secondaryNodeIsDisconnected,synckey,primaryNodeID,last_sync,testDom,testDomDNS,enableProxyCurl,disklimit,ramlimit,loadlimit,checkDisk,checkRam,checkLoad,dnstesttimeout,proxytesttimeout,checkDebugLevel,enableDnsResolver,use_vmac,vmac_xmit_base) 
			VALUES ('$this->primary_node_name','$this->interface','$this->primary_node_state','$this->virtual_router_id','$this->priority','$this->advert_int','$this->nopreempt','$this->unicast_src_ip','$this->enable_peers_ttl','$this->max_peers_ttl','$this->min_peers_ttl','$this->auth_enable','PASS','$this->auth_pass','$this->notifty_enable','$this->notifty','$this->interval','$this->fall','$this->rise','$this->weight','$this->timeout','1','$this->isPrimaryNode','$this->primaryNodeIP','$this->primaryNodePort','$this->secondaryNodeIsDisconnected','$this->synckey','$this->primaryNodeID','$t','$this->testDom','$this->testDomDNS','$this->enableProxyCurl','$this->disklimit','$this->ramlimit','$this->loadlimit','$this->checkDisk','$this->checkRam','$this->checkLoad','$this->dnstesttimeout','$this->proxytesttimeout','$this->checkDebugLevel','$this->enableDnsResolver','$this->use_vmac','$this->vmac_xmit_base')";

            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                return;
            }

            $this->last_id = $q->last_id;
            if ($updateMasterID) {
                $sql = "UPDATE keepalived_primary_nodes SET primaryNodeID='$this->last_id' where ID='$this->last_id'";
                $q->QUERY_SQL($sql);
                if (!$q->ok) {
                    echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                    return;
                }
            }
            if ($this->isPrimaryNode = 1) {
                $nodeAction = new nodeActions($this->last_id, 0, 0);
                $nodeAction->syncNodes();
            }

            if ($reload) {
                $this->CheckService();
            }
            return true;
        } else {

            $sql = "UPDATE keepalived_primary_nodes SET 
				primary_node_name='$this->primary_node_name',
				interface='$this->interface',
				virtual_router_id='$this->virtual_router_id',
				priority='$this->priority',
				advert_int='$this->advert_int',
                auth_enable='$this->auth_enable',
                nopreempt='$this->nopreempt',
                unicast_src_ip='$this->unicast_src_ip',
                enable_peers_ttl='$this->enable_peers_ttl',
                max_peers_ttl='$this->max_peers_ttl',
                min_peers_ttl='$this->min_peers_ttl',
                auth_pass='$this->auth_pass',
                notifty_enable='$this->notifty_enable',
                notifty='$this->notifty',
                interval='$this->interval',
                fall='$this->fall',
                rise='$this->rise',
                weight='$this->weight',
                timeout='$this->timeout',
                enable='$this->enable',
                isPrimaryNode='$this->isPrimaryNode',
                primaryNodeIP='$this->primaryNodeIP',
                primaryNodePort='$this->primaryNodePort',
                secondaryNodeIsDisconnected='$this->secondaryNodeIsDisconnected',
                last_sync='$t',
                testDom='$this->testDom',
                testDomDNS='$this->testDomDNS',
                enableProxyCurl='$this->enableProxyCurl',
                disklimit='$this->disklimit',
ramlimit='$this->ramlimit',
loadlimit='$this->loadlimit',
checkDisk='$this->checkDisk',
checkRam='$this->checkRam',
checkLoad='$this->checkLoad',
dnstesttimeout='$this->dnstesttimeout',
proxytesttimeout='$this->proxytesttimeout',
checkDebugLevel='$this->checkDebugLevel',
enableDnsResolver='$this->enableDnsResolver',
use_vmac='$this->use_vmac',
vmac_xmit_base='$this->vmac_xmit_base'
				WHERE ID='$this->primary_node_id'
				";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                return;
            }
            if ($this->isPrimaryNode = 1) {
                $nodeAction = new nodeActions($this->primary_node_id, 0, 0);
                $nodeAction->syncNodes();
            }

            if ($reload) {
                $this->CheckService();
            }
            return true;

        }


    }

    function delete($reload = true, $fromMaster = true)
    {
        if ($fromMaster) {
            $nodeAction = new nodeActions($this->primary_node_id, 0, 0);
            $nodeAction->delete_primary_node();
            sleep(3);
        }

        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "DELETE FROM keepalived_primary_nodes WHERE ID='$this->primary_node_id'";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
            return;
        }
        if ($reload) {
            $this->CheckService();
        }

        return true;
    }

    private function CheckService()
    {
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?reconfigure=yes");
    }
}

class keepalived_services
{
    public $service_id = 0;
    public $primary_node_id = 0;
    public $service = null;
    public $script = null;
    public $enable = 0;
    public $synckey = null;
    public $service_array = array(
        "Proxy" => "Proxy",
        "SMTP" => "SMTP",
        "HACluster" => "HACluster",
        "HAProxy" => "HAProxy",
        "Unbound" => "Unbound DNS",
        "PowerDNS" => "PowerDNS",
        "DNSFirewall" => "DNSFirewall",
        "DNSCache" => "DNSCache",
        "Nginx"=> "Nginx"

    );

    function __construct($primary_node_id = null, $service_id = null)
    {
        if ($primary_node_id <> null) {
            $this->primary_node_id = $primary_node_id;

            if ($service_id <> null) {
                $this->service_id = $service_id;
                $this->load();
            }
        }
    }

    private function load()
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "SELECT * from keepalived_services WHERE ID='$this->service_id' AND primary_node_id='$this->primary_node_id' ";
        $ligne = $q->mysqli_fetch_array($sql);

        $this->service_id = intval($ligne['ID']);
        $this->primary_node_id = intval($ligne['primary_node_id']);
        $this->service = $ligne['service'];
        $this->script = $ligne['script'];
        $this->enable = intval($ligne['enable']);
        $this->synckey = $ligne['synckey'];
    }

    public function duplicates($service)
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "SELECT COUNT(*) as tcount from  keepalived_services WHERE service='$service'";
        $ligne = $q->mysqli_fetch_array($sql);
        return intval($ligne['tcount']);
    }

    public function save($reload = true, $fromMaster = true)
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        if ($this->service_id == 0) {
            $script["Proxy"] = "/bin/pidof squid";
            $script["SMTP"] = "/bin/pidof master";
            $script["HACluster"] = "/bin/pidof hacluster";
            $script["HAProxy"] = "/bin/pidof haproxy";
            $script["Unbound"] = "/bin/pidof unbound";
            $script["PowerDNS"] = "/bin/pidof pdns_server";
            $script["DNSFirewall"] = "/bin/pidof dnsdist";
            $script["DNSCache"] = "/bin/pidof dnscache";
            $script["Nginx"] = "/bin/pidof nginx";

            $this->script = $script["{$this->service}"];
            $sql = "INSERT INTO keepalived_services (primary_node_id,service,script,enable,synckey) 
			VALUES ('$this->primary_node_id','$this->service','$this->script','1','$this->synckey')";
        } else {
            $script["Proxy"] = "/bin/pidof squid";
            $script["SMTP"] = "/bin/pidof master";
            $script["HACluster"] = "/bin/pidof hacluster";
            $script["HAProxy"] = "/bin/pidof haproxy";
            $script["Unbound"] = "/bin/pidof unbound";
            $script["PowerDNS"] = "/bin/pidof pdns_server";
            $script["DNSFirewall"] = "/bin/pidof dnsdist";
            $script["DNSCache"] = "/bin/pidof dnscache";
            $script["Nginx"] = "/bin/pidof nginx";
            $this->script = $script["{$this->service}"];
            $sql = "UPDATE keepalived_services SET 
				primary_node_id='$this->primary_node_id',
				service='$this->service',
				script='$this->script',
				enable='$this->enable'
				WHERE ID='$this->service_id'
				";

        }

        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
            return;
        }
        if ($fromMaster) {
            $action = new nodeActions($this->primary_node_id, 0, 0);
            $action->syncNodes();
        }

        if ($reload) {
            $this->CheckService();
        }
        return true;
    }

    public function delete($reload = true, $fromMaster = true)
    {
        if ($fromMaster) {
            $action = new nodeActions($this->primary_node_id, 0, $this->synckey);
            $action->delete_services();
            sleep(3);
        }

        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "DELETE FROM keepalived_services WHERE ID='$this->service_id' AND primary_node_id='$this->primary_node_id'";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
            return;
        }

        if ($reload) {
            $this->CheckService();
        }

        return true;
    }

    private function CheckService()
    {
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?reconfigure=yes");
    }

}

class keepalived_vips
{

    public $primary_node_id = null;
    public $virtualip_id = null;
    public $virtual_interface = null;
    public $virtual_interface_id = null;
    public $virtual_ip = null;
    public $netmask = null;
    public $dev = null;
    public $label = null;
    public $enable = 0;
    public $synckey = null;


    function __construct($primary_node_id = null, $virtualip_id = null)
    {
        if ($primary_node_id <> null) {
            $this->primary_node_id = $primary_node_id;

            if ($virtualip_id <> null) {
                $this->virtualip_id = $virtualip_id;
                $this->load();
            }
        }
    }

    private function load()
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "SELECT * from keepalived_virtual_interfaces WHERE ID='$this->virtualip_id' AND primary_node_id='$this->primary_node_id' ";
        $ligne = $q->mysqli_fetch_array($sql);

        $this->virtualip_id = intval($ligne['ID']);
        $this->primary_node_id = intval($ligne['primary_node_id']);
        $this->virtual_interface = $ligne['virtual_interface'];
        $this->virtual_interface_id = intval($ligne['virtual_interface_id']);
        $this->virtual_ip = $ligne['virtual_ip'];
        $this->netmask = $ligne['netmask'];
        $this->dev = $ligne['dev'];
        $this->label = $ligne['label'];
        $this->enable = intval($ligne['enable']);
        $this->synckey = $ligne["synckey"];



    }

    public function duplicates($ip)
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "SELECT COUNT(*) as tcount from  keepalived_virtual_interfaces WHERE virtual_ip='$ip'";
        $ligne = $q->mysqli_fetch_array($sql);
        return intval($ligne['tcount']);
    }

    public function vnicExist($vnic)
    {
        $x = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $sql = "SELECT COUNT(*) as tcount from  nics_virtuals WHERE ipaddr='$vnic'";
        $ligne = $x->mysqli_fetch_array($sql);
        return intval($ligne['tcount']);
    }

    public function save($reload = true, $fromMaster = true)
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");

        if ($this->virtualip_id == 0) {
            //INSERT VIP
            $sql = "INSERT INTO keepalived_virtual_interfaces (primary_node_id,virtual_ip,netmask,dev,enable,synckey) 
			VALUES ('$this->primary_node_id','$this->virtual_ip','$this->netmask','$this->dev','1','$this->synckey')";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                return;
            }
            $vip_last_id = $q->last_id;

            //CREATE VIRTUAL NIC
            $MASK["32"] = "255.255.255.255";
            $MASK["31"] = "255.255.255.254";
            $MASK["30"] = "255.255.255.252";
            $MASK["29"] = "255.255.255.248";
            $MASK["28"] = "255.255.255.240";
            $MASK["27"] = "255.255.255.224";
            $MASK["26"] = "255.255.255.192";
            $MASK["25"] = "255.255.255.128";
            $MASK["24"] = "255.255.255.0";
            $MASK["23"] = "255.255.254.0";
            $MASK["22"] = "255.255.252.0";
            $MASK["21"] = "255.255.248.0";
            $MASK["20"] = "255.255.240.0";
            $MASK["19"] = "255.255.224.0";
            $MASK["18"] = "255.255.192.0";
            $MASK["17"] = "255.255.128.0";
            $MASK["16"] = "255.255.0.0";
            $MASK["15"] = "255.254.0.0";
            $MASK["14"] = "255.252.0.0)";
            $MASK["13"] = "255.248.0.0";
            $MASK["12"] = "255.240.0.0";
            $MASK["11"] = "255.224.0.0";
            $MASK["10"] = "255.192.0.0";
            $MASK["9"] = "255.128.0.0";
            $MASK["8"] = "255.0.0.0";
            $MASK["7"] = "254.0.0.0";
            $MASK["6"] = "252.0.0.0)";
            $MASK["5"] = "248.0.0.0";
            $MASK["4"] = "240.0.0.0";
            $MASK["3"] = "224.0.0.0";
            $MASK["2"] = "192.0.0.0";
            $MASK["1"] = "128.0.0.0";
            $x = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
            $netmask = $MASK["$this->netmask"];

            $cdir = substr($this->virtual_ip, 0, strrpos($this->virtual_ip, '.')) . '.0/' . $this->netmask;
            $sql = "INSERT INTO nics_virtuals (nic,org,ipaddr,netmask,cdir,gateway,ForceGateway,failover,metric,openvpn_nic,ipv6,from_failover_service)
	VALUES('$this->dev','none','$this->virtual_ip','$netmask',
	'$cdir','','0','1','1','0','0','1');
	";
            $x->QUERY_SQL($sql);
            if (!$x->ok) {
                echo $x->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                return;
            }

            //UPDATE VIP
            $nic_last_id = $x->last_id;
            $label = $this->dev . ":" . $nic_last_id;
            $sql = "UPDATE keepalived_virtual_interfaces SET 
                label='$label',
                virtual_interface='$label',
                virtual_interface_id='$nic_last_id'                                   
				WHERE ID='$vip_last_id'
				";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                return;
            }

            if ($fromMaster) {
                $action = new nodeActions($this->primary_node_id, 0, 0);
                $action->syncNodes();
            }
            if ($reload) {
                $this->CheckService();
            }
            return true;
        } else {
            $sql = "UPDATE keepalived_virtual_interfaces SET 
				primary_node_id='$this->primary_node_id',
                virtual_ip='$this->virtual_ip',
                netmask='$this->netmask',
				virtual_interface='$this->virtual_interface',
				dev='$this->dev',
                label='$this->label'
				WHERE ID='$this->virtualip_id'
				";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                return;
            }


            //CREATE VIRTUAL NIC
            $MASK["32"] = "255.255.255.255";
            $MASK["31"] = "255.255.255.254";
            $MASK["30"] = "255.255.255.252";
            $MASK["29"] = "255.255.255.248)";
            $MASK["28"] = "255.255.255.240";
            $MASK["27"] = "255.255.255.224";
            $MASK["26"] = "255.255.255.192";
            $MASK["25"] = "255.255.255.128";
            $MASK["24"] = "255.255.255.0";
            $MASK["23"] = "255.255.254.0";
            $MASK["22"] = "255.255.252.0";
            $MASK["21"] = "255.255.248.0";
            $MASK["20"] = "255.255.240.0";
            $MASK["19"] = "255.255.224.0";
            $MASK["18"] = "255.255.192.0";
            $MASK["17"] = "255.255.128.0";
            $MASK["16"] = "255.255.0.0";
            $MASK["15"] = "255.254.0.0";
            $MASK["14"] = "255.252.0.0)";
            $MASK["13"] = "255.248.0.0";
            $MASK["12"] = "255.240.0.0";
            $MASK["11"] = "255.224.0.0";
            $MASK["10"] = "255.192.0.0";
            $MASK["9"] = "255.128.0.0";
            $MASK["8"] = "255.0.0.0";
            $MASK["7"] = "254.0.0.0";
            $MASK["6"] = "252.0.0.0)";
            $MASK["5"] = "248.0.0.0";
            $MASK["4"] = "240.0.0.0";
            $MASK["3"] = "224.0.0.0";
            $MASK["2"] = "192.0.0.0";
            $MASK["1"] = "128.0.0.0";
            $x = new lib_sqlite("/home/artica/SQLITE/interfaces.db");

            $netmask = $MASK["$this->netmask"];

            $cdir = substr($this->virtual_ip, 0, strrpos($this->virtual_ip, '.')) . '.0/' . $this->netmask;
            $lastNicID = $this->virtual_interface_id;
            $count_nic = $x->mysqli_fetch_array("select COUNT(*)as tcount, * from nics_virtuals WHERE ID='$this->virtual_interface_id'");
            if (intval($count_nic['tcount']) > 0) {
                $sql = "UPDATE nics_virtuals SET
            nic='$this->dev',
            ipaddr='$this->virtual_ip',
            netmask='$netmask',
            cdir='$cdir'
            WHERE ID='$this->virtual_interface_id'";
                $x->QUERY_SQL($sql);
                if (!$x->ok) {
                    echo $x->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                    return;
                }
            } else {
                $sql = "INSERT INTO nics_virtuals (nic,org,ipaddr,netmask,cdir,gateway,ForceGateway,failover,metric,openvpn_nic,ipv6,from_failover_service)
	VALUES('$this->dev','none','$this->virtual_ip','$netmask',
	'$cdir','','0','1','1','0','0','1');
	";
                $x->QUERY_SQL($sql);
                if (!$x->ok) {
                    echo $x->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                    return;
                }
                $lastNicID = $x->last_id;
            }

            //UPDATE VIP

            $label = $this->dev . ":" . $lastNicID;
            $sql = "UPDATE keepalived_virtual_interfaces SET 
                label='$label',
                virtual_interface='$label',
                virtual_interface_id='$lastNicID'                                   
				WHERE ID='$this->virtualip_id'
				";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                return;
            }

            $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
            if ($fromMaster) {
                $action = new nodeActions($this->primary_node_id, 0, 0);
                $action->syncNodes();
            }
            if ($reload) {
                $this->CheckService();
            }
            return true;
        }

    }

    public function delete($reload = true, $fromMaster = true)
    {

        if ($fromMaster) {
            $action = new nodeActions($this->primary_node_id, 0, $this->synckey);
            $action->delete_vips();
            sleep(3);
        }

        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "DELETE FROM keepalived_virtual_interfaces WHERE synckey='$this->synckey' ";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
            return;
        }
        $x = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $sql = "DELETE FROM nics_virtuals WHERE ID='$this->virtual_interface_id' ";
        $x->QUERY_SQL($sql);
        if (!$x->ok) {
            echo $x->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
            return;
        }
        if ($reload) {
            $this->CheckService();
        }
        return true;
    }

    private function CheckService()
    {
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?reconfigure=yes");
    }
}

class keepalived_trackinterfaces
{

    public $primary_node_id = null;
    public $trackinterfaces_id = null;
    public $interface = null;
    public $weight = null;
    public $enable = 0;
    public $synckey = null;


    function __construct($primary_node_id = null, $trackinterfaces_id = null)
    {

        if ($primary_node_id <> null) {
            $this->primary_node_id = $primary_node_id;

            if ($trackinterfaces_id <> null) {
                $this->trackinterfaces_id = $trackinterfaces_id;
                $this->load();
            }
        }
    }

    private function load()
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "SELECT * from keepalived_track_interfaces WHERE ID='$this->trackinterfaces_id' AND primary_node_id='$this->primary_node_id' ";
        $ligne = $q->mysqli_fetch_array($sql);

        $this->trackinterfaces_id = intval($ligne['ID']);
        $this->primary_node_id = intval($ligne['primary_node_id']);
        $this->interface = $ligne['interface'];
        $this->weight = intval($ligne['weight']);
        $this->enable = intval($ligne['enable']);
        $this->synckey = $ligne['synckey'];
    }

    public function duplicates($interface)
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "SELECT COUNT(*) as tcount from  keepalived_track_interfaces WHERE interface='$interface'";
        $ligne = $q->mysqli_fetch_array($sql);
        return intval($ligne['tcount']);
    }

    public function save($reload = true, $fromMaster = true)
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        if ($this->trackinterfaces_id == 0) {
            $sql = "INSERT INTO keepalived_track_interfaces (primary_node_id,interface,weight,enable,synckey) 
			VALUES ('$this->primary_node_id','$this->interface','$this->weight','1','$this->synckey')";
        } else {
            $sql = "UPDATE keepalived_track_interfaces SET 
				primary_node_id='$this->primary_node_id',
				interface='$this->interface',
                weight='$this->weight'
				WHERE ID='$this->trackinterfaces_id'
				";

        }
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
            return;
        }
        if ($fromMaster) {
            $action = new nodeActions($this->primary_node_id, 0, 0);
            $action->syncNodes();
        }

        if ($reload) {
            $this->CheckService();
        }
        return true;
    }

    public function delete($reload = true, $fromMaster = true)
    {
        if ($fromMaster) {
            $action = new nodeActions($this->primary_node_id, 0, $this->synckey);
            $action->delete_tracks();
            sleep(3);
        }

        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "DELETE FROM keepalived_track_interfaces WHERE ID='$this->trackinterfaces_id' AND primary_node_id='$this->primary_node_id'";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
            return;
        }
        if ($reload) {
            $this->CheckService();
        }
        return true;
    }

    private function CheckService()
    {
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?reconfigure=yes");
    }
}


class keepalived_secondary_nodes
{

    public $secondary_node_id = null;
    public $primary_node_id = null;
    public $secondary_node_ip = null;
    public $secondary_node_port = 9000;
    public $primary_node_ip = null;
    public $secondary_node_can_overwrite_settings = null;
    public $enable = 0;
    public $synckey = null;
    public $last_id = null;
    public $nopreempt = 0;
    public $priority;
    public $count_secondary_nodes = 0;
    public $lastSync = null;

    function __construct($primary_node_id = null, $secondary_node_id = null)
    {

        if ($primary_node_id <> null) {
            $this->primary_node_id = $primary_node_id;

            if ($secondary_node_id <> null) {
                $this->secondary_node_id = $secondary_node_id;
                $this->load();
            }
        }
    }

    private function load()
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "SELECT * from keepalived_secondary_nodes WHERE ID='$this->secondary_node_id' AND primary_node_id='$this->primary_node_id' ";
        $ligne = $q->mysqli_fetch_array($sql);

        $this->secondary_node_id = intval($ligne['ID']);
        $this->primary_node_id = intval($ligne['primary_node_id']);
        $this->secondary_node_ip = $ligne['secondary_node_ip'];
        $this->secondary_node_port = $ligne['secondary_node_port'];
        $this->primary_node_ip = $ligne['primary_node_ip'];
        $this->secondary_node_can_overwrite_settings = intval($ligne['secondary_node_can_overwrite_settings']);
        $this->enable = intval($ligne['enable']);
        $this->synckey = $ligne['synckey'];
        $this->nopreempt = intval($ligne['nopreempt']);
        $this->priority = intval($ligne['priority']);
        $this->hostname = intval($ligne['hostname']);
        $this->force_action = intval($ligne['force_action']);
        $this->lastSync = $ligne['last_sync'];

        $sql = "SELECT COUNT(*) as tcount from keepalived_secondary_nodes";
        $ligne = $q->mysqli_fetch_array($sql);
        $this->count_secondary_nodes = intval($ligne["tcount"]);
    }

    public function duplicates($ip)
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "SELECT COUNT(*) as tcount from  keepalived_secondary_nodes WHERE secondary_node_ip='$ip'";
        $ligne = $q->mysqli_fetch_array($sql);
        return intval($ligne['tcount']);
    }

    public function duplicatesPriority($primary_node, $priority)
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "SELECT COUNT(*) as tcount from  keepalived_secondary_nodes WHERE primary_node_id='$primary_node' AND priority='$priority'";
        $ligne = $q->mysqli_fetch_array($sql);
        return intval($ligne['tcount']);
    }

    public function save($reload = true)
    {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        if ($this->secondary_node_id == 0) {
            $sql = "INSERT INTO keepalived_secondary_nodes (primary_node_id,secondary_node_ip,secondary_node_port,primary_node_ip,secondary_node_can_overwrite_settings,enable,synckey,nopreempt,priority,last_sync) 
			VALUES ('$this->primary_node_id','$this->secondary_node_ip','$this->secondary_node_port','$this->primary_node_ip','$this->secondary_node_can_overwrite_settings','1','$this->synckey','$this->nopreempt','$this->priority',DateTime('now'))";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                return;
            }
            $this->last_id = $q->last_id;

        } else {
            $sql = "UPDATE keepalived_secondary_nodes SET 
                secondary_node_port='$this->secondary_node_port',             
                primary_node_ip='$this->primary_node_ip',
                secondary_node_can_overwrite_settings='$this->secondary_node_can_overwrite_settings',
                enable='$this->enable',
                nopreempt='$this->nopreempt',
                priority='$this->priority',
                last_sync=DateTime('now')
				WHERE ID='$this->secondary_node_id'
				";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
                return;
            }
            $this->last_id = $this->secondary_node_id;
        }
        $nodeAction = new nodeActions($this->primary_node_id, $this->last_id, 0);
        $nodeAction->syncNodes();
        if ($reload) {
            $this->CheckService();
        }
        return true;
    }

    public function delete($reload = true)
    {
        $action = new nodeActions($this->primary_node_id, $this->secondary_node_id, 0);
        $action->delete_nodes();
        sleep(3);
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $sql = "DELETE FROM keepalived_secondary_nodes WHERE ID='$this->secondary_node_id' AND primary_node_id='$this->primary_node_id'";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error . "<br>" . __CLASS__ . "<br>$sql<br>";
            return;
        }

        $primary_node_info = new keepalives_primary_nodes($this->primary_node_id);
        if (intval($primary_node_info->unicast_src_ip) == 1) {
            $action->syncNodes();
        }

        if ($reload) {
            $this->CheckService();
        }
        return true;
    }


    private function CheckService()
    {
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?reconfigure=yes");
    }
}

class nodeActions
{
    public $primary_node_id = 0;
    public $secondary_node_id = 0;
    public $synckey = 0;

    function __construct($primary_node_id = 0, $secondary_node_id = 0, $synckey = 0)
    {
        if ($primary_node_id <> 0) {
            $this->primary_node_id = $primary_node_id;
        }

        if ($secondary_node_id <> 0) {
            $this->secondary_node_id = $secondary_node_id;
        }

        if ($synckey <> 0) {
            $this->synckey = $synckey;
        }
    }

    public function syncNodes()
    {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nodeActions", "sync-nodes=yes&id=$this->primary_node_id");
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?sync-nodes=yes&id=$this->primary_node_id&secondary_node_id=$this->secondary_node_id");
    }

    public function delete_vips()
    {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nodeActions", "node-delete-vips=yes&primary_node_id=$this->primary_node_id&synckey=$this->synckey");
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?node-delete-vips=yes&primary_node_id=$this->primary_node_id&synckey=$this->synckey");
    }

    public function delete_tracks()
    {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nodeActions", "node-delete-tracks=yes&primary_node_id=$this->primary_node_id&synckey=$this->synckey");
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?node-delete-tracks=yes&primary_node_id=$this->primary_node_id&synckey=$this->synckey");
    }

    public function delete_services()
    {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nodeActions", "node-delete-services=yes&primary_node_id=$this->primary_node_id&synckey=$this->synckey");
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?node-delete-services=yes&primary_node_id=$this->primary_node_id&synckey=$this->synckey");
    }

    public function delete_nodes()
    {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nodeActions", "node-delete=yes&primary_node_id=$this->primary_node_id&secondary_node_id=$this->secondary_node_id");
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?node-delete=yes&primary_node_id=$this->primary_node_id&secondary_node_id=$this->secondary_node_id");
    }

    public function delete_primary_node()
    {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nodeActions", "primary-node-delete=yes&primary_node_id=$this->primary_node_id");
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?primary-node-delete=yes&primary_node_id=$this->primary_node_id");
    }

    public function syncDebug()
    {
        if (isset($GLOBALS["AS_ROOT"])) {
            if ($GLOBALS["AS_ROOT"]) {
                return;
            }
        }
        $sock = new sockets();
        $sock->getFrameWork("keepalived.php?sync-debug=yes");
    }

}