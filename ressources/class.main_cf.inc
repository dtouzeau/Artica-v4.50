<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}



$classes[]="class.ldap.inc";
$classes[]="class.ssl.certificate.inc";
$classes[]="class.artica.inc";
$classes[]="class.mimedefang.inc";
$classes[]="class.tcpip.inc";
$classes[]="class.maincf.multi.inc";

if(isset($GLOBALS["CLASS_SOCKETS"])){
    $EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
    if($EnablePostfix==0){$classes=array();}
}

foreach ($classes as $filename){
    if(is_file(dirname(__FILE__) . "/$filename")){include_once dirname(__FILE__) . "/$filename";}
}

class main_cf{
    var $myorigin;
    var $main_array;
    var $main_full_array;
    var $array_mynetworks;
    var $filepath;
    var $main_cf_datas;
    var $array_hashs;
    var $array_regexp;
    var $mail_version;
    var $bounce_templates=array();
    var $error=false;
    var $PostfixMiltersBehavior;
    var $broken_sasl_auth_clients="yes";
    var $smtpd_sasl_security_options="noanonymous";
    var $smtpd_sasl_local_domain=null;
    var $smtpd_sasl_authenticated_header="yes";
    var $smtpd_tls_security_level="may";
    var $smtpd_tls_auth_only="no";
    var $smtpd_tls_received_header="yes";
    var $EnableOpenLDAP=0;
    private $instance_id=0;

    function __construct($noload=0,$instance_id=0){
        $this->EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
        $this->instance_id=$instance_id;

        if(isset($GLOBALS["__CLASS-MAIN-CF"])){
            writelogs("Loading class postfix in cache",__CLASS__.'/'.__FUNCTION__,__FILE__);
            foreach (get_object_vars($GLOBALS["__CLASS-MAIN-CF"]) as $key => $value){$this->$key = $value;}return null;
        }

        if($noload==0){
            writepostfixlogs('**** initialize postfix class',__CLASS__.'/'.__FUNCTION__,__FILE__);
            $this->LoadSaslOptions();
            writepostfixlogs('**** parse_conf',__CLASS__.'/'.__FUNCTION__,__FILE__);
            $this->parse_conf();
            writepostfixlogs('**** Parse_hashs',__CLASS__.'/'.__FUNCTION__,__FILE__);
            $this->Parse_hashs();
            writepostfixlogs('**** FillDefaults',__CLASS__.'/'.__FUNCTION__,__FILE__);
            $this->FillDefaults();
            writepostfixlogs('**** parse_my_networks',__CLASS__.'/'.__FUNCTION__,__FILE__);
            $this->parse_my_networks();
            writepostfixlogs('**** initialize postfix end...',__CLASS__.'/'.__FUNCTION__,__FILE__);
            $GLOBALS["__CLASS-MAIN-CF"]=$this;


        }
    }


    private function LoadSaslOptions(){
        include_once(dirname(__FILE__)."/class.maincf.multi.inc");
        $sock=new maincf_multi($this->instance_id);
        $this->broken_sasl_auth_clients=$sock->GET_INFO("broken_sasl_auth_clients");
        $this->smtpd_sasl_security_options=$sock->GET_INFO("smtpd_sasl_security_options");
        $this->smtpd_sasl_local_domain=$sock->GET_INFO("smtpd_sasl_local_domain");
        $this->smtpd_sasl_authenticated_header=$sock->GET_INFO("smtpd_sasl_authenticated_header");
        $this->smtpd_tls_security_level=$sock->GET_INFO("smtpd_tls_security_level");
        $this->smtpd_tls_auth_only=$sock->GET_INFO("smtpd_tls_auth_only");
        $this->smtpd_tls_received_header=$sock->GET_INFO("smtpd_tls_received_header");
        if($this->smtpd_sasl_security_options==null){$this->smtpd_sasl_security_options="noanonymous";}
        if($this->broken_sasl_auth_clients==null){$this->broken_sasl_auth_clients="yes";}
        if($this->smtpd_sasl_authenticated_header==null){$this->smtpd_sasl_authenticated_header="yes";}
        if($this->smtpd_tls_security_level==null){$this->smtpd_tls_security_level="may";}
        if($this->smtpd_tls_auth_only==null){$this->smtpd_tls_auth_only="no";}
        if($this->smtpd_tls_received_header==null){$this->smtpd_tls_received_header="yes";}

    }



    function fix_relayhost(){
        $relay=trim($this->main_array["relayhost"]);
        if($relay==null){return null;}
        if(preg_match('#^(smtp|relay):(.+)#',$relay,$re)){
            $this->main_array["relayhost"]=$re[2];
        }

        if(preg_match("#(.+?):(.+)$#",$this->main_array["relayhost"],$re)){
            if(strpos($this->main_array["relayhost"],']')==0){
                $this->main_array["relayhost"]="[{$re[1]}]:{$re[2]}";
            }
        }

    }





    function save_bounce_templates(){
        if(!is_array($this->bounce_templates)){
            unset($this->main_array["bounce_template_file"]);
            return null;
        }
        $text=null;
        foreach ($this->bounce_templates as $num=>$val){
            $text=$text . "$num = <<EOF\n$val\nEOF\n";
        }
        $root=$this->postfix_root();

        $this->main_array["bounce_template_file"]="$root/bounce.template.cf";
        return $text;
    }

    function add_my_networks($network,$desc=null,$instance_id=0):bool{
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        @chmod("/home/artica/SQLITE/postfix.db", 0777);
        if($instance_id>0){
            $this->instance_id=$instance_id;
        }
        $q->QUERY_SQL("INSERT OR IGNORE INTO mynetworks (addr,description,instance_id) VALUES ('$network','$desc',$this->instance_id)");
        if(!$q->ok){echo $q->mysql_error;return false;}
        return true;
    }





    public function parse_my_networks(){

        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        if(!$q->IF_TABLE_EXISTS("mynetworks")){
            $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS mynetworks (addr varchar(256) PRIMARY KEY,description text) ");
            $this->build_default_my_networks();
            @chmod("/home/artica/SQLITE/postfix.db", 0777);
        }

        if(!$q->IF_TABLE_EXISTS("mynetworks")){return array();}

        if(!$q->FIELD_EXISTS("mynetworks", "instance_id")){
            $q->QUERY_SQL("ALTER TABLE mynetworks add instance_id INTEGER NOT NULL DEFAULT 0");
        }


        $results=$q->QUERY_SQL("SELECT * FROM mynetworks WHERE instance_id=$this->instance_id");

        foreach ($results as $num=>$ligne){
            if(trim($ligne["addr"])==null){continue;}
            $this->array_mynetworks[]=trim($ligne["addr"]);

        }

        return $this->array_mynetworks;

    }

    private function build_default_my_networks(){


        include_once(dirname(__FILE__).'/class.system.network.inc');
        writepostfixlogs("Get current interfaces...",__CLASS__.'/'.__FUNCTION__,__FILE__);
        $interfaces=new networking();
        if(!is_array($interfaces->array_TCP)){
            writepostfixlogs("There is a real problem getting interfaces eth0,eth1... using articcgi...",__CLASS__.'/'.__FUNCTION__,__FILE__);
            writepostfixlogs("is framework web server is loaded ???",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return null;
        }

        $ip=new IP();
        foreach ($interfaces->array_TCP as $num=>$val){
            if(!preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$val,$re)){continue;}
            $ipfirst="{$re[1]}.{$re[2]}.{$re[3]}.0";
            $cdir=$ip->ip2cidr($ipfirst,"{$re[1]}.{$re[2]}.{$re[3]}.255");
            writepostfixlogs("Scanning $num for $val ($cdir)",__CLASS__.'/'.__FUNCTION__,__FILE__);
            if($cdir){
                $this->add_my_networks($cdir);
                $newCDIR[]=$cdir;
            }

        }
        if(is_array($newCDIR)){
            $newCDIR[]="127.0.0.0/8";
            $hash[0]["mynetworks"]["count"]=count($newCDIR);
            foreach ($newCDIR as $num=>$val){
                $hash[0]["mynetworks"][]=$val;
            }

            return $hash;
        }


    }



    function parse_conf(){
        $this->LoadSourceConf();
        $datas=$this->main_cf_datas;
        $conf=explode("\n",$datas);
        foreach ($conf as $val){
            if(preg_match('#^\##',$val)){continue;}
            if(preg_match('#(.+?)=(.+)#',$val,$regs)){
                $this->main_array[trim($regs[1])]=trim($regs[2]);
                $this->main_full_array[trim($regs[1])]=trim($regs[2]);
            }
        }
    }

    function FillDefaults(){

        if(!isset($this->main_array["smtpd_client_event_limit_exceptions"])){$this->main_array["smtpd_client_event_limit_exceptions"]=null;}
        if(!isset($this->main_array["smtpd_authorized_xforward_hosts"])){$this->main_array["smtpd_authorized_xforward_hosts"]=null;}
        if(!isset($this->main_array["bounce_service_name"])){$this->main_array["bounce_service_name"]=null;}
        if(!isset($this->main_array["bounce_size_limit"])){$this->main_array["bounce_size_limit"]=null;}
        if(!isset($this->main_array["bounce_notice_recipient"])){$this->main_array["bounce_notice_recipient"]=null;}
        if(!isset($this->main_array["header_address_token_limit"])){$this->main_array["header_address_token_limit"]=null;}
        if(!isset($this->main_array["qmgr_message_recipient_minimum"])){$this->main_array["qmgr_message_recipient_minimum"]=null;}
        if(!isset($this->main_array["smtpd_timeout"])){$this->main_array["smtpd_timeout"]=null;}
        if(!isset($this->main_array["smtpd_tls_ask_ccert"])){$this->main_array["smtpd_tls_ask_ccert"]=null;}
        if(!isset($this->main_array["smtpd_tls_req_ccert"])){$this->main_array["smtpd_tls_req_ccert"]=null;}
        if(!isset($this->main_array["smtp_tls_mandatory_protocols"])){$this->main_array["smtp_tls_mandatory_protocols"]=null;}
        if(!isset($this->main_array["smtp_send_xforward_command"])){$this->main_array["smtp_send_xforward_command"]=null;}
        if(!isset($this->main_array["smtpd_sasl_path"])){$this->main_array["smtpd_sasl_path"]=null;}
        if(!isset($this->main_array["smtpd_tls_session_cache_timeout"])){$this->main_array["smtpd_tls_session_cache_timeout"]=null;}
        if(!isset($this->main_array["virtual_uid_maps"])){$this->main_array["virtual_uid_maps"]=null;}
        if(!isset($this->main_array["virtual_uid_maps"])){$this->main_array["virtual_uid_maps"]=null;}
        if(!isset($this->main_array["relayhost"])){$this->main_array["relayhost"]=null;}
        if(!isset($this->main_array["message_size_limit"])){$this->main_array["message_size_limit"]='10240000';}
        if(!isset($this->main_array["mime_nesting_limit"])){$this->main_array["mime_nesting_limit"]='100';}
        if(!isset($this->main_array["header_address_token_limit"])){$this->main_array["header_address_token_limit"]='10240';}
        if(!isset($this->main_array["smtp_connection_cache_on_demand"])){$this->main_array["smtp_connection_cache_on_demand"]="yes";}
        if(!isset($this->main_array["smtp_connection_cache_time_limit"])){$this->main_array["smtp_connection_cache_time_limit"]="2s";}
        if(!isset($this->main_array["smtp_connection_reuse_time_limit"])){$this->main_array["smtp_connection_reuse_time_limit"]="300s";}
        if(!isset($this->main_array["connection_cache_ttl_limit"])){$this->main_array["connection_cache_ttl_limit"]="2s";}
        if(!isset($this->main_array["connection_cache_status_update_time"])){$this->main_array["connection_cache_status_update_time"]="600s";}
        if(!isset($this->main_array["address_verify_sender"])){$this->main_array["address_verify_sender"]=$this->main_array["double_bounce_sender"];}
        if(!isset($this->main_array["address_verify_negative_cache"])){$this->main_array["address_verify_negative_cache"]="yes";}
        if(!isset($this->main_array["address_verify_negative_expire_time"])){$this->main_array["address_verify_negative_expire_time"]="3d";}
        if(!isset($this->main_array["address_verify_negative_refresh_time"])){$this->main_array["address_verify_negative_refresh_time"]="3h";}
        if(!isset($this->main_array["address_verify_poll_count"])){$this->main_array["address_verify_poll_count"]="3";}
        if(!isset($this->main_array["address_verify_poll_delay"])){$this->main_array["address_verify_poll_delay"]="3s";}
        if(!isset($this->main_array["address_verify_positive_expire_time"])){$this->main_array["address_verify_positive_expire_time"]="31d";}
        if(!isset($this->main_array["address_verify_positive_refresh_time"])){$this->main_array["address_verify_positive_refresh_time"]="7d";}
        if(!isset($this->main_array["virtual_mailbox_limit"])){$this->main_array["virtual_mailbox_limit"]=$this->main_array["message_size_limit"];}

        if($this->main_array["bounce_service_name"]==null){$this->main_array["bounce_service_name"]='bounce';}
        if($this->main_array["bounce_size_limit"]==null){$this->main_array["bounce_size_limit"]='50000';}
        if($this->main_array["bounce_notice_recipient"]==null){$this->main_array["bounce_notice_recipient"]='postmaster';}
        if($this->main_array["double_bounce_sender"]==null){$this->main_array["double_bounce_sender"]='double-bounce';}
        if($this->main_array["message_size_limit"]==null){$this->main_array["message_size_limit"]='10240000';}
        if($this->main_array["mime_nesting_limit"]==null){$this->main_array["mime_nesting_limit"]='100';}
        if($this->main_array["header_address_token_limit"]==null){$this->main_array["header_address_token_limit"]='10240';}

        if($this->main_array["smtp_connection_cache_on_demand"]==null){$this->main_array["smtp_connection_cache_on_demand"]="yes";}
        if($this->main_array["smtp_connection_cache_time_limit"]==null){$this->main_array["smtp_connection_cache_time_limit"]="2s";}
        if($this->main_array["smtp_connection_reuse_time_limit"]==null){$this->main_array["smtp_connection_reuse_time_limit"]="300s";}
        if($this->main_array["connection_cache_ttl_limit"]==null){$this->main_array["connection_cache_ttl_limit"]="2s";}
        if($this->main_array["connection_cache_status_update_time"]==null){$this->main_array["connection_cache_status_update_time"]="600s";}
        if($this->main_array["address_verify_sender"]==null){$this->main_array["address_verify_sender"]=$this->main_array["double_bounce_sender"];}
        if($this->main_array["address_verify_negative_cache"]==null){$this->main_array["address_verify_negative_cache"]="yes";}
        if($this->main_array["address_verify_negative_expire_time"]==null){$this->main_array["address_verify_negative_expire_time"]="3d";}
        if($this->main_array["address_verify_negative_refresh_time"]==null){$this->main_array["address_verify_negative_refresh_time"]="3h";}
        if($this->main_array["address_verify_poll_count"]==null){$this->main_array["address_verify_poll_count"]="3";}
        if($this->main_array["address_verify_poll_delay"]==null){$this->main_array["address_verify_poll_delay"]="3s";}
        if($this->main_array["address_verify_positive_expire_time"]==null){$this->main_array["address_verify_positive_expire_time"]="31d";}
        if($this->main_array["address_verify_positive_refresh_time"]==null){$this->main_array["address_verify_positive_refresh_time"]="7d";}

        if($this->main_array["virtual_mailbox_limit"]==null){$this->main_array["virtual_mailbox_limit"]=$this->main_array["message_size_limit"];}

        //Postfix Performance Tuning

        if($this->main_array["smtpd_error_sleep_time"]==null){$this->main_array["smtpd_error_sleep_time"]="1s";}
        if($this->main_array["smtpd_soft_error_limit"]==null){$this->main_array["smtpd_soft_error_limit"]="10";}
        if($this->main_array["smtpd_hard_error_limit"]==null){$this->main_array["smtpd_hard_error_limit"]="20";}
        if($this->main_array["smtpd_client_connection_count_limit"]==null){$this->main_array["smtpd_client_connection_count_limit"]="50";}
        if($this->main_array["smtpd_client_connection_rate_limit"]==null){$this->main_array["smtpd_client_connection_rate_limit"]="0";}
        if($this->main_array["smtpd_client_message_rate_limit"]==null){$this->main_array["smtpd_client_message_rate_limit"]="0";}
        if($this->main_array["smtpd_client_recipient_rate_limit"]==null){$this->main_array["smtpd_client_recipient_rate_limit"]="0";}
        if($this->main_array["smtpd_client_new_tls_session_rate_limit"]==null){$this->main_array["smtpd_client_new_tls_session_rate_limit"]="0";}
        if($this->main_array["smtpd_client_event_limit_exceptions"]==null){$this->main_array["smtpd_client_event_limit_exceptions"]='$mynetworks';}
        if($this->main_array["in_flow_delay"]==null){$this->main_array["in_flow_delay"]='1s';}
        if($this->main_array["smtp_helo_timeout"]==null){$this->main_array["smtp_helo_timeout"]='300s';}
        if($this->main_array["smtp_connect_timeout"]==null){$this->main_array["smtp_connect_timeout"]='30s';}
        if($this->main_array["default_destination_recipient_limit"]==null){$this->main_array["default_destination_recipient_limit"]='50';}
        if($this->main_array["smtpd_recipient_limit"]==null){$this->main_array["smtpd_recipient_limit"]='1000';}

        if($this->main_array["queue_run_delay"]==null){$this->main_array["queue_run_delay"]='300s';}
        if($this->main_array["minimal_backoff_time"]==null){$this->main_array["minimal_backoff_time"]='300s';}
        if($this->main_array["maximal_backoff_time"]==null){$this->main_array["maximal_backoff_time"]='4000s';}
        if($this->main_array["maximal_queue_lifetime"]==null){$this->main_array["maximal_queue_lifetime"]='5d';}
        if($this->main_array["bounce_queue_lifetime"]==null){$this->main_array["bounce_queue_lifetime"]='5d';}
        if($this->main_array["qmgr_message_recipient_limit"]==null){$this->main_array["qmgr_message_recipient_limit"]='20000';}
        if($this->main_array["qmgr_message_recipient_minimum"]==null){$this->main_array["qmgr_message_recipient_minimum"]='10';}
        if($this->main_array["default_process_limit"]==null){$this->main_array["default_process_limit"]='100';}
        if($this->main_array["myorigin"]==null){$this->main_array["myorigin"]='$mydomain';}
        if($this->main_array["smtpd_timeout"]==null){$this->main_array["smtpd_timeout"]='300';}
         if($this->main_array["ignore_mx_lookup_error"]==null){$this->main_array["ignore_mx_lookup_error"]='no';}
        if($this->main_array["disable_dns_lookups"]==null){$this->main_array["disable_dns_lookups"]='no';}
        if($this->main_array["smtpd_tls_ask_ccert"]==null){$this->main_array["smtpd_tls_ask_ccert"]='no';}
        if($this->main_array["smtpd_tls_security_level"]==null){$this->main_array["smtpd_tls_security_level"]='none';}
        if($this->main_array["smtpd_tls_req_ccert"]==null){$this->main_array["smtpd_tls_req_ccert"]='no';}
        if($this->main_array["smtpd_tls_received_header"]==null){$this->main_array["smtpd_tls_received_header"]='no';}
        if($this->main_array["initial_destination_concurrency"]==null){$this->main_array["initial_destination_concurrency"]='5';}
        if($this->main_array["default_destination_concurrency_limit"]==null){$this->main_array["default_destination_concurrency_limit"]='20';}
        if($this->main_array["local_destination_concurrency_limit"]==null){$this->main_array["local_destination_concurrency_limit"]='2';}
        if($this->main_array["smtp_destination_concurrency_limit"]==null){$this->main_array["smtp_destination_concurrency_limit"]='$default_destination_concurrency_limit';}
        if($this->main_array["smtp_tls_mandatory_protocols"]==null){$this->main_array["smtp_tls_mandatory_protocols"]='SSLv3,TLSv1';}
        if($this->main_array["mailbox_size_limit"]<$this->main_array["message_size_limit"]){$this->main_array["mailbox_size_limit"]=$this->main_array["message_size_limit"];}
        if(!isset($this->main_array["virtual_gid_maps"])){$this->main_array["virtual_gid_maps"]='static:5000';}
        $this->main_array["virtual_mailbox_limit"]=$this->main_array["message_size_limit"];
        if($this->main_array["double_bounce_sender"]==null){$this->main_array["double_bounce_sender"]='double-bounce';}
        if($this->main_array["2bounce_notice_recipient"]==null){$this->main_array["2bounce_notice_recipient"]='postmaster';}
        if($this->main_array["error_notice_recipient"]==null){$this->main_array["error_notice_recipient"]='postmaster';}
        if($this->main_array["delay_notice_recipient"]==null){$this->main_array["delay_notice_recipient"]='postmaster';}
        if($this->main_array["address_verify_sender"]==null){$this->main_array["address_verify_sender"]='double-bounce';}
        if($this->main_array["empty_address_recipient"]==null){$this->main_array["empty_address_recipient"]='MAILER-DAEMON';}
        if($this->main_array["smtp_send_xforward_command"]==null){$this->main_array["smtp_send_xforward_command"]='yes';}
        if($this->main_array["smtpd_authorized_xforward_hosts"]==null){$this->main_array["smtpd_authorized_xforward_hosts"]='$mynetworks';}
        if($this->main_array["smtpd_sasl_path"]==null){$this->main_array["smtpd_sasl_path"]="smtpd";}
        if(strlen($this->main_array["smtpd_tls_session_cache_timeout"]>8)){$this->main_array["smtpd_tls_session_cache_timeout"]="3600s";}
        $this->main_array["smtp_tls_session_cache_database"]="btree:".'/var/lib/postfix/smtpd_tls_session_cache';
        if($this->main_array["virtual_uid_maps"]==null){$this->main_array["virtual_uid_maps"]='static:5000';}
        if($this->main_array["virtual_gid_maps"]==null){$this->main_array["virtual_gid_maps"]='static:5000';}
        if($this->main_array["myhostname"]==null){
            writepostfixlogs("Get default hostname for myhostname value",__CLASS__.'/'.__FUNCTION__,__FILE__);
            include_once(dirname(__FILE__).'/class.system.network.inc');
            $nic=new networking();
            $this->main_array["myhostname"]=$nic->hostname;
            writepostfixlogs("myhostname=$nic->hostname",__CLASS__.'/'.__FUNCTION__,__FILE__);
        }


        $sock=new sockets();
        if($this->main_array["relayhost"]==null){
            $this->main_array["relayhost"]=$sock->GET_INFO("PostfixRelayHost");
        }

        include_once(dirname(__FILE__)."/class.maincf.multi.inc");
        $main=new maincf_multi($this->instance_id);
        $in_flow_delay=$main->GET("in_flow_delay");
        if($in_flow_delay<>null){$this->main_array["in_flow_delay"]=$in_flow_delay;}

    }





    function Parse_hashs(){

        if(strlen($this->main_cf_datas)==0){
            writepostfixlogs("WARNING: main_cf_datas is empty !!",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return null;
        }

        if(preg_match_all('#([a-z_]+)[\s=]+regexp:([a-z0-9A-Z\/_\.]+)#is',$this->main_cf_datas,$regs)){
            foreach ($regs[1] as $num=>$val){
                $this->array_regexp[$val]=$regs[2][$num];
            }
        }

    }


    private function CleanMyNetworks(){
        if(!is_array($this->array_mynetworks)){return null;}
        reset($this->array_mynetworks);
        foreach ($this->array_mynetworks as $val){
            $res[$val]=$val;

        }
        unset($this->array_mynetworks);

        foreach ($res as $num=>$val){
            $this->array_mynetworks[]=$num;

        }
    }


    function relay_clientcerts_conf(){
        $c=new relay_clientcerts();
        $hash=$c->ParseTable();
        if(count($hash)==0){
            writepostfixlogs("relay_clientcerts: no datas.. ",__CLASS__ . '/' . __FUNCTION__,__FILE__);
            return null;
        }
        $conf=null;
        $ldap=new clladp();
        writepostfixlogs("relay_clientcerts: Building LDAP settings... ",__CLASS__ . '/' . __FUNCTION__,__FILE__);
        $conf=$conf. "\n#LDAP relay_clientcerts --------------------------------------------------------------------\n";
        $conf=$conf . "relay_clientcerts = ldap:ldaprelcert\n\n";
        $conf=$conf . "ldaprelcert_server_host = $ldap->ldap_host\n";
        $conf=$conf . "ldaprelcert_server_port = $ldap->ldap_port\n";
        $conf=$conf . "ldaprelcert_bind = yes\n";
        $conf=$conf . "ldaprelcert_bind_dn =cn=$ldap->ldap_admin,$ldap->suffix\n";
        $conf=$conf . "ldaprelcert_bind_pw =$ldap->ldap_password\n";
        $conf=$conf . "ldaprelcert_timeout = 10\n";
        $conf=$conf . "ldaprelcert_search_base = cn=relay_clientcerts_maps,cn=artica,$ldap->suffix\n";
        $conf=$conf . "ldaprelcert_query_filter = (&(objectclass=PostfixRelayClientCerts)(cn=%s))\n";
        $conf=$conf . "ldaprelcert_result_attribute = relayClientcert\n";
        $conf=$conf . "ldaprelcert_version =3\n\n";
        $conf=$conf. "#-------------------------------------------------------------------------------------------\n\n";
        return $conf;

    }

    function CleanArray(){
        unset($this->main_array["relay_clientcerts"]);
        unset($this->main_array["smtp_connection_cache_destinations"]);
        unset($this->main_array["relais_domain"]);
        unset($this->main_array["virtual_mailbox_maps"]);
        unset($this->main_array["alias_maps"]);
        unset($this->main_array["relay_recipient_maps"]);
        unset($this->main_array["mydestination"]);
        unset($this->main_array["sender_canonical_maps"]);
        unset($this->main_array["smtp_generic_maps"]);
        unset($this->main_array["transport_maps"]);

        unset($this->main_array["artica_destination_recipient_limit"]);
        unset($this->main_array["smtpd_helo_restrictions"]);
        unset($this->main_array["smtpd_recipient_restrictions"]);
        unset($this->main_array["smtpd_client_restrictions"]);
        unset($this->main_array["virtual_transport_maps"]);
        unset($this->main_array["virtual_alias_domains"]);
        unset($this->main_array["sender_dependent_relayhost_maps"]);
    }

    private function LoadSourceConf(){
        $main=new maincf_multi($this->instance_id);
        $PostfixMainCfFile=$main->GET("PostfixMainCfFile");
        if($PostfixMainCfFile==null){
            $PostfixMainCfFile=@file_get_contents($this->postfix_root()."/main.cf");
            $this->main_cf_datas=$PostfixMainCfFile;
        }
    }

    function save_conf(){
        writepostfixlogs("Save main.cf ############################################");



        $this->CleanMyNetworks();
        if(is_array($this->array_mynetworks)){
            $nets=implode(',',$this->array_mynetworks);
            writepostfixlogs("mynetworks=$nets",__CLASS__.'/'.__FUNCTION__,__FILE__);
            $this->main_array["mynetworks"]=implode(',',$this->array_mynetworks);
        }




        $PostFixBounceTemplate=$this->save_bounce_templates();
        $this->mailbox_transport();
        $this->FillDefaults();
        $this->CleanArray();
        $this->cyrus_transport();
        $this->fix_relayhost();

        $main=new maincf_multi($this->instance_id);
        $this->main_array["content_filter"]="";
        $this->main_array["receive_override_options"] = "";

        if(intval($main->GET_INFO("EnablePostfixAntispamPack")==1)){
            $this->main_array["smtpd_error_sleep_time"]="1s";
            $this->main_array["smtpd_soft_error_limit"]="10";
            $this->main_array["smtpd_hard_error_limit"]="20";
            $this->main_array["disable_vrfy_command"]="yes";
            $this->main_array["smtpd_delay_reject"]="yes";
            $this->main_array["smtpd_helo_required"]="yes";
        }



        writepostfixlogs("main array count " . count($this->main_array) . ' rows',__CLASS__.'/'.__FUNCTION__,__FILE__);
        reset($this->main_array);
        foreach ($this->main_array as $num=>$val){
            $conf=$conf . "$num=$val\n";
        }

        $this->main_cf_datas=$conf;
        $this->_SaveMainCf($conf);


        if($PostFixBounceTemplate<>null){
            $sock=new sockets();
            $sock->SaveConfigFile($PostFixBounceTemplate,'PostfixBounceTemplateFile');
            writepostfixlogs("PostFixBounceTemplate " . strlen($PostFixBounceTemplate) . " lenght",__CLASS__.'/'.__FUNCTION__,__FILE__);
        }

        writepostfixlogs("Save Postfix settings into Artica file storage",__CLASS__.'/'.__FUNCTION__,__FILE__);



    }




    function cyrus_transport(){
        $user=new usersMenus();
        $main=new maincf_multi($this->instance_id);
        $mailbox_transport=trim($main->GET("mailbox_transport"));
        if($mailbox_transport<>null){
            $this->main_array["mailbox_transport"]=$mailbox_transport;
            $this->main_array["virtual_transport"]="\$mailbox_transport";
            return;
        }
        if($user->cyrus_imapd_installed){
            if(!class_exists("unix")){include_once('/usr/share/artica-postfix/framework/class.unix.inc');}
            $unix=new unix();

            if($user->cyrus_lmtp_path==null){
                $queue_directory=$unix->POSTCONF_GET("queue_directory");
                $user->cyrus_lmtp_path="$queue_directory/var/run/cyrus/socket/lmtp";
            }

            $this->main_array["mailbox_transport"]="lmtp:unix:$user->cyrus_lmtp_path";
            $this->main_array["virtual_transport"]="\$mailbox_transport";
        }

    }


    function mailbox_transport(){
        $user=new usersMenus();
        $main=new maincf_multi($this->instance_id);
        $mailbox_transport=trim($main->GET("mailbox_transport"));
        if($mailbox_transport<>null){
            $this->main_array["mailbox_transport"]=$mailbox_transport;
            $this->main_array["virtual_transport"]="\$mailbox_transport";
            return;
        }

        if($user->procmail_installed==true){
            $this->main_array["mailbox_transport"]="procmail";
            $this->main_array["virtual_transport"]="procmail";
            $this->main_array["mailbox_command"]="/usr/bin/procmail -t -a \"$"."EXTENSION\"";
        }else{
            unset($this->main_array["mailbox_command"]);
            if($user->cyrus_imapd_installed==true){
                $this->main_array["mailbox_transport"]="lmtp:unix:$user->cyrus_lmtp_path";
                $this->main_array["virtual_transport"]="\$mailbox_transport";
            }
        }
    }

    private function postfix_root():string{
        $instance_id=$this->instance_id;
        if($instance_id==0){
            return "/etc/postfix";
        }

        return "/etc/postfix-instance{$instance_id}";
    }


    function save_conf_to_server($NoRestartDaemon=0){
        writepostfixlogs("##################### SAVE CONF TO SERVER #######################",__CLASS__.'/'.__FUNCTION__,__FILE__);
        $this->FillDefaults();
        $this->save_conf();


        $sock=new maincf_multi($this->instance_id);
        $user=new usersMenus();
        $user->LoadModulesEnabled();
        $conf=$this->main_cf_datas;

        if(strlen($conf)==0){
            return false;
        }

        $tbl=explode("\n",$conf);

        foreach ($tbl as $num=>$val){
            if(substr($val,0,1)=="#"){unset($tbl[$num]);}
            if(preg_match("#mynetworks#",$val)){unset($tbl[$num]);}
            if(preg_match("#virtual_mailbox_domains#",$val)){unset($tbl[$num]);}
            if(preg_match("#smtpd_milters#",$val)){unset($tbl[$num]);}
            if(preg_match("#milter_connect_macros#",$val)){unset($tbl[$num]);}
            if(preg_match("#milter_helo_macros#",$val)){unset($tbl[$num]);}
            if(preg_match("#milter_mail_macros#",$val)){unset($tbl[$num]);}
            if(preg_match("#milter_rcpt_macros#",$val)){unset($tbl[$num]);}
            if(preg_match("#milter_default_action#",$val)){unset($tbl[$num]);}
            if(preg_match("#non_smtpd_milters#",$val)){unset($tbl[$num]);}
            if(preg_match("#milter_protocol#",$val)){unset($tbl[$num]);}
            if(preg_match("#milter_connect_timeout#",$val)){unset($tbl[$num]);}
            if(preg_match("#milter_command_timeout#",$val)){unset($tbl[$num]);}
            if(preg_match("#milter_content_timeout#",$val)){unset($tbl[$num]);}
        }


        $conf=implode("\n",$tbl);



        if(strlen($conf)==0){
            writepostfixlogs("Warning, it seems there are no values after clean configuration : " .count($tbl) . " master table rows",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return false;
        }
        $conf=$conf . $this->relay_clientcerts_conf();

        if(is_array($this->array_mynetworks)){
            reset($this->array_mynetworks);
            $nets=implode(',',$this->array_mynetworks);
            writepostfixlogs("mynetworks=$nets",__CLASS__."/".__FUNCTION__,__FILE__);
            $conf=$conf . "mynetworks=$nets\n";
        }else{
            writepostfixlogs("no array so mynetworks=127.0.0.0/8",__CLASS__."/".__FUNCTION__,__FILE__);
            $conf=$conf . "mynetworks=127.0.0.0/8\n";
        }
        $conf=$conf . "artica_destination_recipient_limit = 1\n";



        $newtable=explode("\n",$conf);
        foreach ($newtable as $val){
            $new_arr[$val]=$num;
        }
        $conf='';
        foreach ($new_arr as $num=>$val){
            $conf=$conf .$num ."\n";
        }


        $this->_FixTree();

        $tmp=new bounces_templates();
        $tmp->Save();

        $sock=new sockets();
        writepostfixlogs("Save configuration file into Artica storage...",__CLASS__.'/'.__FUNCTION__,__FILE__);
        $this->main_cf_datas=$conf;
        $sock->SaveConfigFile($conf,"PostfixMainCfFile");


        if($NoRestartDaemon==0){
            $datas=$sock->getFrameWork('cmd.php?SaveMaincf=yes');
            writepostfixlogs("Send order to restart daemon done...");
        }

        $sock->DeleteCache();
        writepostfixlogs("###################### END ##############################",__CLASS__.'/'.__FUNCTION__,__FILE__);
        return true;
    }




    /**
     * Ensure that the tree is corrected builded to prevent errors.
     * warning: dict_ldap_lookup: ...
     * @access public
     * @param  none
     * @return void
     */

    function _SaveMainCf($conf){

        $sp=explode("\n",$conf);
        if(is_array($sp)){
            $conf='';
            writepostfixlogs("cleaning main.cf builder",__CLASS__.'/'.__FUNCTION__,__FILE__);
            foreach ($sp as $val){
                if(trim($val)==null){continue;}
                if(preg_match('#^\##',$val)){continue;}
                $conf=$conf."$val\n";

            }
        }

        writepostfixlogs("main.cf after cleaning " . strlen($conf) . " lenght,PostfixMiltersBehavior=$this->PostfixMiltersBehavior",__CLASS__.'/'.__FUNCTION__,__FILE__);
        $main=new maincf_multi($this->instance_id);
        $main->SET_INFO($conf,"PostfixMainCfFile");
        $main->SET_INFO("PostfixMiltersBehavior",$this->PostfixMiltersBehavior);
    }

    private function _FixTree(){

    }




}


class master_cf{
    var $filepath;
    var $master_array;
    var $PostfixMasterCfFile;
    var $ldap_error;
    var $error;
    var $dn;
    var $array_services=array();
    var $array_full=array();
    var $PostfixEnableMasterCfSSL;
    var $hostname;
    var $MasterCFUseDefaults=1;
    var $noService=false;

    var $array_type=array("fifo"=>"FIFO","inet"=>"INET","unix"=>"UNIX","pass"=>"PASS");

    var $standard_services=array(
        "pickup"=>true,
        "smtp"=>true,
        "cleanup"=>true,
        "qmgr"=>true,
        "tlsmgr"=>true,
        "rewrite"=>true,
        "bounce"=>true,
        "defer"=>true,
        "trace"=>true,
        "verify"=>true,
        "flush"=>true,
        "proxymap"=>true,
        "relay"=>true,
        "showq"=>true,
        "error"=>true,
        "discard"=>true,
        "local"=>true,
        "virtual"=>true,
        "lmtp"=>true,
        "anvil"=>true,
        "scache"=>true,
        "maildrop"=>true,
        "retry"=>true

    );

    function __construct($noload=0,$instance_id=0){
        $this->instance_id=$instance_id;
        $main=new maincf_multi($instance_id);
        $this->PostfixEnableMasterCfSSL=intval($main->GET_INFO("PostfixEnableMasterCfSSL"));
        if(!$main->TokenExists("MasterCFUseDefaults")){
            $main->SET_VALUE("MasterCFUseDefaults",1);
        }
        $this->MasterCFUseDefaults=$main->GET_INFO("MasterCFUseDefaults");



    }


    function GetArray(){
        if($this->MasterCFUseDefaults==1){return array();}
        $sql="SELECT * FROM master_cf WHERE hostname='$this->hostname'";
        $q=new mysql();
        $results=$q->QUERY_SQL($sql,"artica_backup");
        if(mysqli_num_rows($results)==0){return array();}
        if(!$q->ok){echo "$q->mysql_error\n";}
        while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
            $KEY=$ligne["zmd5"];
            $SERVICE=$ligne["SERVICE_NAME"];
            $TYPE=$ligne["TYPE"];
            $options=array();
            $optionsTXT=null;
            $MAXPROC=$ligne["MAXPROC"];
            $PRIVATE=$ligne["PRIVATE"];
            $UNIPRIV=$ligne["UNIPRIV"];
            $CHROOT=$ligne["CHROOT"];
            $WAKEUP=$ligne["WAKEUP"];

            if($MAXPROC==null){$MAXPROC="-";}
            if($PRIVATE==null){$PRIVATE="-";}
            if($UNIPRIV==null){$UNIPRIV="-";}
            if($CHROOT==null){$CHROOT="-";}
            if($WAKEUP==null){$WAKEUP="-";}

            $options=unserialize($ligne["COMMAND"]);
            $optionsF=array();
            $count=0;
            if (is_array($options)){
                foreach ($options as $index=>$opt){
                    if(trim($opt)==null){continue;}
                    if(trim($opt)=="Array"){continue;}
                    if(is_array($opt)){continue;}
                    $count++;
                    $optionsF[]=$opt;
                }
            }else{continue;}

            if(count($optionsF)==0){continue;}

            $array[$SERVICE]=array(
                "TYPE"=>$TYPE,
                "MAXPROC"=>$MAXPROC,
                "PRIVATE"=>$PRIVATE,
                "UNIPRIV"=>$UNIPRIV,
                "CHROOT"=>$CHROOT,
                "WAKEUP"=>$WAKEUP,
                "COMMAND"=>@implode(" ", $optionsF));



        }
        return $array;
    }





    //calibri
    private function add_defaults(){
        $this->noService=true;
        $this->edit_service("smtp    ","inet","n","-","n","-",   "-",array("smtpd"));
        $this->edit_service("pickup  ","fifo","n","-","n","60",   "1",array("pickup"));
        $this->edit_service("cleanup ","unix","n","-","n","-",    "0",array("cleanup"));
        $this->edit_service("qmgr    ","fifo","n","-","n","300",  "1",array("qmgr"));
        $this->edit_service("tlsmgr  ","unix","-","-","n","1000?","1",array("tlsmgr"));
        $this->edit_service("rewrite ","unix","-","-","n",    "-","-",array("trivial-rewrite"));
        $this->edit_service("bounce  ","unix","-","-","n",    "-","0",array("bounce"));
        $this->edit_service("defer   ","unix","-","-","n",    "-","0",array("bounce"));
        $this->edit_service("trace   ","unix","-","-","n",    "-","0",array("bounce"));
        $this->edit_service("verify  ","unix","-","-","n",    "-","1",array("verify"));
        $this->edit_service("flush   ","unix","n","-","n",    "1000?","0",array("flush"));
        $this->edit_service("proxymap","unix","-","-","n",        "-","-",array("proxymap"));
        $this->edit_service("proxywrite","unix","-","-","n",        "-","1",array("proxymap"));
        $this->edit_service("smtp    ","unix","-","-","n",        "-","-",array("smtp"));
        $this->edit_service("relay   ","unix","-","-","n",        "-","-",array("smtp","-o fallback_relay="));
        $this->edit_service("showq   ","unix","n","-","n",        "-","-",array("showq"));
        $this->edit_service("error   ","unix","-","-","n",        "-","-",array("error"));
        $this->edit_service("discard ","unix","-","-","n",        "-","-",array("discard"));
        $this->edit_service("local   ","unix","-","n","n",        "-","-",array("local"));
        $this->edit_service("virtual ","unix","-","n","n",        "-","-",array("virtual"));
        $this->edit_service("lmtp    ","unix","-","-","n",        "-","-",array("lmtp"));
        $this->edit_service("anvil   ","unix","-","-","n",        "-","1",array("anvil"));
        $this->edit_service("scache  ","unix","-","-","n",        "-","1",array("scache"));
        $this->edit_service("scan    ","unix","-","-","n",        "-","10",array("smtp"));
        $this->edit_service("maildrop","unix","-","n","n",        "-","-",array("pipe"));
        $this->edit_service("retry   ","unix","-","-","n",        "-","-",array("error"));
        $this->edit_service("uucp    ","unix","-","n","n",        "-","-",array("pipe",'flags=Fqhu user=uucp argv=uux -r -n -z -a$sender - $nexthop!rmail ($recipient)'));
        $this->edit_service("ifmail  ","unix","-","n","n",        "-","-",array("pipe",'flags=F user=ftn argv=/usr/lib/ifmail/ifmail -r $nexthop ($recipient)'));
        $this->edit_service("bsmtp   ","unix","-","n","n",        "-","-",array("pipe",'flags=Fq. user=bsmtp argv=/usr/lib/bsmtp/bsmtp -t$nexthop -f$sender $recipient'));
        $this->edit_service("scalemail-backend","unix","-","n","n","-","2",array("pipe",
            'flags=R user=scalemail argv=/usr/lib/scalemail/bin/scalemail-store ${nexthop} ${user} ${extension}'));
        $sock=new sockets();
        $sock->getFrameWork("cmd.php?postfix-throttle=yes&instance=$this->hostname");


    }


    function edit_service($service_name,$type,$private,$unpriv,$chroot,$wakeup,$maxproc,$command,$key=null){

        $service_name=trim(strtolower($service_name));

        if($key==null){$key=md5($service_name.$type.$this->hostname);}
        $q=new mysql();
        $edit=false;
        $sql="SELECT zmd5,COMMAND FROM master_cf WHERE zmd5='$key'";
        $ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
        if($ligne["zmd5"]<>null){$edit=true;$commandz=unserialize($ligne["COMMAND"]);$command_text=addslashes(serialize($commandz));}
        writelogs("edit=$edit $service_name type=$type",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);

        if(is_array($command)){
            $command_text=addslashes(serialize($command));
        }

        $sql="INSERT IGNORE INTO master_cf (`hostname`,`SERVICE_NAME`,`TYPE`,`PRIVATE`,`UNIPRIV`,`CHROOT`,`WAKEUP`,`MAXPROC`,`COMMAND`,`zmd5`)
		VALUES('$this->hostname','$service_name','$type','$private','$unpriv','$chroot','$wakeup','$maxproc','$command_text','$key')
		";

        $sql_upd="UPDATE master_cf SET `PRIVATE`='$private',
		`TYPE`='$type',
		`UNIPRIV`='$unpriv',
		`CHROOT`='$chroot',
		`WAKEUP`='$wakeup',
		`MAXPROC`='$maxproc',
		`COMMAND`='$command_text'
		WHERE zmd5='$key'
		";

        if($edit){$sql=$sql_upd;}
        $q->QUERY_SQL($sql,"artica_backup");
        if(!$q->ok){echo __CLASS__.'/'.__FUNCTION__ ." (".__LINE__.") failed\n$q->mysql_error\n<H2>$sql</H2>";return false;}
        if(!$this->noService){
            $sock=new sockets();
            $sock->getFrameWork("cmd.php?postfix-throttle=yes&instance=$this->hostname");
        }
        return true;
    }

    function add_command_options($zmd5,$option){
        $q=new mysql();
        $sql="SELECT zmd5,COMMAND FROM master_cf WHERE zmd5='$zmd5'";

        $ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
        writelogs("$sql={$ligne["zmd5"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        $commandz=array();
        $array=unserialize($ligne["COMMAND"]);

        if(is_array($array)){
            $commandz=$array;
            $commandz[]=$option;
        }else{
            $commandz[]=$option;
        }
        $options=addslashes(serialize($commandz));
        $sql="UPDATE master_cf SET `COMMAND`='$options' WHERE zmd5='$zmd5'";
        $q->QUERY_SQL($sql,"artica_backup");
        if(!$q->ok){echo __CLASS__.'/'.__FUNCTION__ ." (".__LINE__.") failed\n$q->mysql_error\n<H2>$sql</H2>";return false;}
        $sock=new sockets();
        $sock->getFrameWork("cmd.php?postfix-throttle=yes&instance=$this->hostname");
        return true;
    }
    function del_command_options($zmd5,$index){
        $q=new mysql();
        $sql="SELECT zmd5,COMMAND FROM master_cf WHERE zmd5='$zmd5'";

        $ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
        writelogs("$sql={$ligne["zmd5"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        $commandz=array();
        $array=unserialize($ligne["COMMAND"]);
        if(is_array($array)){
            unset($array[$index]);
            $commandz=$array;

        }else{
            return;
        }
        $options=addslashes(serialize($commandz));
        $sql="UPDATE master_cf SET `COMMAND`='$options'";
        $q->QUERY_SQL($sql,"artica_backup");
        if(!$q->ok){echo __CLASS__.'/'.__FUNCTION__ ." (".__LINE__.") failed\n$q->mysql_error\n<H2>$sql</H2>";return false;}
        $sock=new sockets();
        $sock->getFrameWork("cmd.php?postfix-throttle=yes&instance=$this->hostname");
        return true;
    }

    function delete_service($zdm5){
        $q=new mysql();
        $sql="DELETE FROM master_cf WHERE zmd5='$zdm5' AND `hostname`='$this->hostname'";
        writelogs("$sql=$zdm5",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        $q->QUERY_SQL($sql,"artica_backup");
        if(!$q->ok){echo __CLASS__.'/'.__FUNCTION__ ." (".__LINE__.") failed\n$q->mysql_error\n<H2>$sql</H2>";return false;}
        $sock=new sockets();
        $sock->getFrameWork("cmd.php?postfix-throttle=yes&instance=$this->hostname");
        return true;
    }




    function LoadMastercfInDisk(){
        $sock=new sockets();
        return $sock->getFrameWork('master.cf');
    }

    function StripLines(){
        $datas=explode("\n",$this->PostfixMasterCfFile);
        foreach ($datas as $num=>$val){
            if(trim($val)==null){
                unset($datas[$num]);
            }
        }

        $this->PostfixMasterCfFile=implode("\n",$datas);

    }

    function _readfileArray(){
        $sock=new sockets();
        $datas=explode("\n",$this->PostfixMasterCfFile);
        return $datas;
    }


    function SaveToLdap($NoRestartDaemon=0){
        $ldap=new clladp();
        if(!$ldap->ExistsDN($this->dn)){
            $upd["objectClass"][]='top';
            $upd["objectClass"][]='PostfixStoreFiles';
            $upd["cn"][]='PostfixFilesStorage';
            $upd["PostfixMasterCfFile"][]=$this->PostfixMasterCfFile;
            $upd["PostfixTimeCode"][]=date('YmdhIs');
            if(!$ldap->ldap_add($this->dn,$ldap->suffix,$upd)){
                echo __CLASS__.'/'.__FUNCTION__ ." (".__LINE__.") failed\n$ldap->ldap_last_error\n";
                echo $ldap->ldap_last_error;
                return false;
            }
        }else{
            $upd["PostfixMasterCfFile"][]=$this->PostfixMasterCfFile;
            $upd["PostfixTimeCode"][]=date('YmdhIs');
            if(!$ldap->Ldap_modify($this->dn,$upd)){
                echo __CLASS__.'/'.__FUNCTION__ ." (".__LINE__.") failed\n$ldap->ldap_last_error\n";
                echo $ldap->ldap_last_error;
                return false;
            }
        }

        $sock=new sockets();
        $sock->SET_INFO("MasterCFEnabled",1);
        if($NoRestartDaemon==0){$datas=$sock->getFrameWork('cmd.php?SaveMaincf=yes');}
        $tpl=new templates();

        return true;


    }


}

class systeminfos{
    var $error;
    var $array_datas;
    var $array_ip;
    var $array_tcp_addr;
    var $array_tcp_addr_num;
    var $array_interfaces;
    var $array_dns_servers;
    var $array_applis;
    var $array_applis_line;
    var $memory_total;
    var $memory_used;
    var $memory_free;
    var $memory_shared;
    var $memory_buffer;
    var $memory_cached;
    var $swap_total;
    var $swap_used;
    var $swap_free;
    var $hostname;
    function __construct($hostname=null){
        if($hostname==null){
            if(class_exists("usersMenus")){
                $users=new usersMenus();
                $this->hostname=$users->hostname;
            }else{
                if(class_exists("settings_inc")){
                    $users=new settings_inc();
                    $this->hostname=$users->hostname;
                }
            }


        }else{
            $this->hostname=$hostname;
        }

        $this->ParseIP();
        $this->ParseResolv_conf();
        $this->LoadMemory();

    }

    function ParseIP():bool{
        $array=array();
        if(!isset($GLOBALS["ARRAY_IP"])){$GLOBALS["ARRAY_IP"]=array();}
        if(count($GLOBALS["ARRAY_IP"])>0){$this->array_ip=$GLOBALS["ARRAY_IP"];return true;}
        $sock=new sockets();
        $datas=array();
        if($GLOBALS["AS_ROOT"]){
            if(!class_exists("unix")){include_once('/usr/share/artica-postfix/framework/class.unix.inc');}
                $unix=new unix();
                $cmd=$unix->find_program("ifconfig")." -a 2>&1";
                exec($cmd,$datas);

        }

        if(count($datas)==0){
            $data=$sock->REST_API("/system/network/ifconfigall");
            $json=json_decode($data);
            if (json_last_error()> JSON_ERROR_NONE) {
                return false;
            }
            if ($json->Status) {
                $datas = explode("\n", base64_decode($json->Data));
            }
        }
        
        if(!is_array($datas)){$datas=array();}
        
        $this->array_datas=$datas;
        foreach ($datas as $val){
            $filter="#^([a-zA-Z\:\-0-9]+)#is";
            if(preg_match($filter,$val,$regs)){
                $this->array_interfaces[$regs[1]]=$regs[1];
                $array[$regs[1]]=$this->_ParseInterface($regs[1]);
            }
        }

        $this->array_ip=$array;
        $GLOBALS["ARRAY_IP"]=$this->array_ip;
        return true;
    }

    function ParseResolv_conf():bool{
        $sock=new sockets();
        $datas=array();
        if($GLOBALS["AS_ROOT"]){
            $datas=explode("\n",@file_get_contents("/etc/resolv.conf"));
        }
        if(count($datas)==0){
            $data=$sock->REST_API("/system/network/resolvconf");
            $json=json_decode($data);
            if (json_last_error()> JSON_ERROR_NONE) {
                return false;
            }
            if ($json->Status) {
                $datas = explode("\n", base64_decode($json->Data));
            }
        }
        foreach ($datas as $val){
            if(preg_match('#^nameserver\s+([a-zA-Z0-9\.\_]+)#',$val,$re)){
                $this->array_dns_servers[$re[1]]=$re[1];
            }

        }
        return true;
    }

    function lspci(){
        $sock=new sockets();
        $tpl=new templates();
        $tbr=unserialize(base64_decode($sock->getFrameWork("cmd.php?lspci=yes")));
        foreach ($tbr as $num=>$val){
            if(preg_match('#([0-9a-z\:\.]+)\s+([a-zA-Z0-9\s]+):(.+)#',$val,$re)){
                $res[$re[3]]=$re[2];
            }

        }
        return $res;
    }

    function LoadMemory():bool{
        $sock=new sockets();
        $tbr=array();
        $array=array();
        if($GLOBALS["AS_ROOT"]){
            if(!class_exists("unix")){include_once('/usr/share/artica-postfix/framework/class.unix.inc');}
                $unix=new unix();
                $prog=$unix->find_program("free");
                if(strlen($prog)>3){
                    exec("$prog -m -o 2>&1",$results);
                    $tbr=$results;
                }

        }

        if(count($tbr)==0){
            $data = $sock->REST_API("/system/memory/freemem");
            $json = json_decode($data);
            if (json_last_error() > JSON_ERROR_NONE) {
                return false;
            }
            if(!$json->Status){
                return false;
            }
            $tbr=explode("\n",base64_decode($json->Data));
        }

        if(!is_array($tbr)){return false;}
        if(count($tbr)==0){return false;}
        foreach ($tbr as $val){if($val<>null){$array[]=$val;}}
        if(count($array)>0){
            foreach ($array as $val){
                if(preg_match('#Mem:\s+([0-9]+)\s+([0-9]+)\s+([0-9+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#',$val,$reg)){
                    //total       used       free     shared    buffers     cached
                    $this->memory_total=$reg[1];
                    $this->memory_used=$reg[2];
                    $this->memory_free=$reg[3];
                    $this->memory_shared=$reg[4];
                    $this->memory_buffer=$reg[5];
                    $this->memory_cached=$reg[6];

                }

                if(preg_match('#Swap:\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#',$val,$reg)){
                    $this->swap_total=$reg[1];
                    $this->swap_used=$reg[2];
                    $this->swap_free=$reg[3];
                }
            }
        }
        return true;
    }




    function DiskUsages(){
        $sock=new sockets();
        $tbr=unserialize(base64_decode($sock->getFrameWork("cmd.php?dfmoinsh=yes")));
        if(!is_array($tbr)){return null;}
        foreach ($tbr as $num=>$val){if($val<>null){$array[]=$val;}}
        if(is_array($array)){

            foreach ($array as $num=>$val){
                if(preg_match('#([a-zA-Z0-9\/\.\-\_]+)\s+([0-9A-Z]+)\s+([0-9A-Z]+)\s+([0-9A-Z]+)\s+([0-9\%]+)\s+([a-z0-9\/\.\-\_]+)#',$val,$reg)){
                    $arr[]=array($reg[1],$reg[2],$reg[2],$reg[3],$reg[4],$reg[5],$reg[6]);
                }
            }
        }

        return $arr;
    }

    function environements(){
        $sock=new sockets();
        $datas= unserialize(base64_decode($sock->getFrameWork('cmd.php?printenv=yes')));
        return $datas;

    }

    function ParseAPPs(){
        $sock=new sockets();
        $datas=$sock->getFrameWork('cmd.php?Global-Applications-Status=yes');


        if(preg_match_all('#<(.+?)>(.+?)</(.+?)>#is',$datas,$regs)){
            foreach ($regs as $a=>$b){
                $title=$regs[1][$a];
                $table=explode("\n",$regs[2][$a]);
                if(is_array($table)){
                    foreach ($table as $val){
                        if(preg_match('#\[(.+)\]\s+#',$val,$reg)){
                            if(preg_match('#"(.+)"#',$val,$reg1)){$version=$reg1[1];}else{$version=null;}
                            $this->array_applis[$title][$reg[1]]=$version;
                            $this->array_applis_line[$reg[1]]=$version;

                        }
                    }
                }
            }
        }
    }


    function _ParseInterface($interface){

        if(preg_match("#^[0-9]+\.[0-9]+#", $interface)){return;}
        if(!preg_match("#^[a-z0-9]+$#", $interface)){return;}
        $array=array();
        if($GLOBALS["AS_ROOT"]){
            if(!class_exists("unix")){include_once('/usr/share/artica-postfix/framework/class.unix.inc');}
            $unix=new unix();
            $ifconfig=$unix->find_program("ifconfig");
            exec("ifconfig $interface 2>&1",$datas);
        }else{
            $sock=new sockets();
            $datas=unserialize(base64_decode($sock->getFrameWork("network.php?ifconfig=$interface")));
        }

        foreach ($datas as $num=>$val){
            if(preg_match('#HWaddr\s+([0-9A-Z]{1,2}:[0-9A-Z]{1,2}:[0-9A-Z]{1,2}:[0-9A-Z]{1,2}:[0-9A-Z]{1,2}:[0-9A-Z]{1,2})#i',$val,$regs)){
                $array["MAC"]=$regs[1];
            }



            if(preg_match('#(Masque|Mask):([0-9\.]+)#i',$val,$regs)){
                $array["NETMASK"]=$regs[2];
            }

            if(preg_match('#inet (adr|addr):([0-9\.]+)#i',$val,$regs)){
                $array["IP"]=$regs[2];
                $this->array_tcp_addr[$regs[2]]=$regs[2];
                $this->array_tcp_addr_num[]=$regs[2];
            }



        }
        if(!isset($array["IP"])){$array["IP"]=null;}
        if($array["IP"]==null){$array["IP"]="0.0.0.0";}
        return $array;

    }



}

class smtpd_restrictions{

    var $smtpd_client_restrictions_table;
    var $smtpd_sender_restrictions_table;
    var $smtpd_recipient_restrictions_table;
    var $smtpd_helo_restrictions_table;
    var $smtpd_all_restrictions_table;
    var $smtpd_hash_restrictions_table;
    var $main_array=array();

    var $standard_classes_array=array(
        "smtpd_client_restrictions"=>"smtpd_client_restrictions",
        "smtpd_sender_restrictions"=>"smtpd_sender_restrictions",
        "smtpd_helo_restrictions"=>"smtpd_helo_restrictions",
        "smtpd_recipient_restrictions"=>"smtpd_recipient_restrictions"
    );

    var $restriction_rules_key=array(
        "check_recipient_access"=>"yes",
        "check_recipient_mx_access"=>"yes",
        "check_recipient_ns_access"=>"yes",
        "check_helo_access"=>"yes",
        "check_helo_mx_access"=>"yes",
        "check_helo_ns_access"=>"yes",
        "check_sender_access"=>"yes",
        "check_sender_mx_access"=>"yes",
        "check_sender_ns_access"=>"yes"
    );

    var $dnsrbl_database;


    function __construct(){
    }


    function HashGetHeadersList(){
        $datas=file_get_contents(dirname(__FILE__).'/databases/db.headers.txt');
        return explode("\n",$datas);
    }


    function DeleteKey($keyname,$class_name,$value=null){

    }

    function AddKey($keyname,$class_name,$value=null){


    }




    function Build(){
        return  "";
    }




}

class PostFixLogs{

    function __construct(){


    }




    function GetErrorLogs($max=10){

        $sock=new sockets();
        $datas=explode("\n",$sock->getfile('PostfixErrorsLogs'));
        if (!is_array($datas)){
            writepostfixlogs("no datas receive from framework",__FUNCTION__,__FILE__);
            return null;}

        array_reverse($datas);
        $count=count($datas);
        writepostfixlogs($count . ' lines',__FUNCTION__,__FILE__);
        if($count<5){return "<H5>{no_errors}</H5>";}
        if($count>$max){$count=$max;}else{$count=count($datas);}

        return "<H5>{postfix_last_errors}</H5><br>" . $this->ParsePostfixLogs($datas,$count);

    }

    function ParsePostfixLogs($array,$count,$justdiv=0){

        for($i=0;$i<$count+1;$i++){
            $line=$array[$i];

            if(preg_match('#^(.+?)\s+([0-9+\:]+)\s+(.+?)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#',$line,$reg)){
                $lines=$lines+1;
                $reg[7]=htmlentities($reg[7]);
                if(preg_match('#([A-Z0-9]+):#',$reg[7],$re)){
                    $message_id=$re[1];
                    $md=md5($reg[7].$i);
                    $reg[7]=str_replace($message_id,texttooltip($message_id,"{more_infos}<br><strong>$message_id</strong>","LoadPostfixHistoryMsgID('$message_id','$md')"),$reg[7]);
                }

                $reg[3]=texttooltip($reg[3],"{more_infos}<br><strong>{$reg[3]}</strong>","LoadPostfixHistoryMsgID('{$reg[3]}','$md')");

                if($justdiv==1){
                    $html=$html ."<div style='padding:2px;padding-left:10px;width:95%'><strong>{$reg[5]}</strong> {$reg[3]} {$reg[7]}</div><div id='$md'></div>";
                }else{
                    $html=$html . RoundedLightGrey("
			<table style='width:100%'>
			<tr>
			<td width=1%  valign='top'><img src='img/icon_mini_off.jpg'></td>
			<td valign='top' >{$reg[5]}</td>
			<td valign='top' >{$reg[3]}</td>
			<td valign='top' style='font-size:10px;text-align:justify'>{$reg[7]}</td>
			</tr>
			</table>
			<div id='$md'></div>") . "<br>";
                }}else{
                writepostfixlogs('not match=>'.$line,__FUNCTION__,__FILE__);
            }
        }


        if($lines==0){return null;}

        return $html;
    }


}



class relay_clientcerts{
    var $dn;


    function __construct(){
        $this->verifyBranch();

    }

    function ParseTable():array{
        $ldap=new clladp();
        $pattern="(objectclass=PostfixRelayClientCerts)";
        if(!$ldap->ldap_connection){
            return array();
        }
        $sr =@ldap_search($ldap->ldap_connection,$this->dn,$pattern,array());
        if(!$sr){return array();}
        $res=array();
        $hash=ldap_get_entries($ldap->ldap_connection,$sr);
        for ($i=0;$i<$hash["count"];$i++){
            $res[$hash[$i]["cn"][0]]=$hash[$i]["relayclientcert"][0];
        }

        return $res;

    }


    function verifyBranch(){
        $ldap=new clladp();
        $this->dn="cn=relay_clientcerts_maps,cn=artica,$ldap->suffix";
        if(!$ldap->ExistsDN($this->dn)){
            $upd['objectClass'][]='top';
            $upd['objectClass'][]='PostFixStructuralClass';
            $upd["cn"][]="relay_clientcerts_maps";
            $ldap->ldap_add($this->dn,$upd);
        }
    }

    function add($fingerprint,$host){
        $tpl=new templates();
        $ldap=new clladp();
        $cn=md5("$fingerprint$host");
        $dn="cn=" . $fingerprint .",$this->dn";
        if(!$ldap->ExistsDN($dn)){
            $upd['objectClass'][]='PostfixRelayClientCerts';
            $upd['objectClass'][]='top';
            $upd['relayClientcert'][]=$host;
            $upd['relayClientcertFingerprint'][]=$fingerprint;
            $upd["cn"][]="$fingerprint";
            if($ldap->ldap_add($dn,$upd)){
                echo $tpl->_parse_body("{success} Add fingerprint;\n");
            }else{echo $ldap->ldap_last_error;}

        }

    }

    function del($cn){
        $ldap=new clladp();
        $dn="cn=" . $cn .",$this->dn";
        $ldap->ldap_delete($dn,true);
    }



}

class maincf_reports{
    var $maincf;
    var $main_array=array();
    var $must2Fields=array();


    function __construct(){
        $sock=new sockets();
        $this->maincf=$sock->getfile('main.cf');
        $this->ParseConfig();

//virtual_alias_maps
        $this->must2Fields["virtual_alias_maps"]=true;
        $this->must2Fields["virtual_mailbox_maps"]=true;
        $this->must2Fields["alias_maps"]=true;
        $this->must2Fields["sender_canonical_maps"]=true;
        $this->must2Fields["recipient_canonical_maps"]=true;
        $this->must2Fields["transport_maps"]=true;
        $this->must2Fields["relay_recipient_maps"]=true;
        $this->must2Fields["local_recipient_maps"]=true;
        $this->must2Fields["relay_clientcerts"]=true;




    }

    function ParseConfig(){
        $tbl=explode("\n",$this->maincf);
        foreach ($tbl as $num=>$val){
            if(trim($val)==null){continue;}
            if(preg_match("#(.+?)[\s+=]+(.+)#",$val,$re)){
                $this->main_array[trim($re[1])]=$re[2];
            }
        }

    }

    function ParseInfos($key){
        if($this->main_array[$key]==null){return "<strong style='color:#d32d2d'>No datas</strong>";}
        $tbl=explode(",",$this->main_array[$key]);
        foreach ($tbl as $num=>$val){
            if(trim($val)==null){continue;}

            if(preg_match("#ldap:(.+)#",$val,$re)){
                $val=$this->ParseLdap($re[1],$key);
            }
            $res[]=$val;

        }

        return implode(',',$res);

    }




    function ParseLdap($key,$field){
        $host=$this->main_array["{$key}_server_host"];
        $port=$this->main_array["{$key}_server_port"];
        $adm=$this->main_array["{$key}_bind_dn"];
        $pass=$this->main_array["{$key}_bind_pw"];
        $suffix=$this->main_array["{$key}_search_base"];
        $query=$this->main_array["{$key}_query_filter"];
        $query=str_replace('%s',"*",$query);
        $query=str_replace('%u',"*",$query);
        $query=str_replace('%d',"*",$query);
        $return=$this->main_array["{$key}_result_attribute"];

        if($this->must2Fields[$field]){
            if(preg_match("#\(([a-zA-Z]+)=\*\)#",$query,$r)){
                //$res[]="$query<br>";
                $first_field=$r[1];
                $attr[]=$return;
                $attr[]=$first_field;
                $res[]="<table width=1%>
					<tr style='background-color:#CCCCCC'>
						<td nowrap><strong>$first_field</strong></td>
						<td>&nbsp;</td>
						<td nowrap><strong>$return</strong>
						</td>
					</tr>";
            }
        }else{
            $attr[]=$return;
        }




        $ldap_connection=ldap_connect($host,$port) ;
        ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
        ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
        $ldapbind=ldap_bind($ldap_connection, $adm, $pass);
        $sr =@ldap_search($ldap_connection,$suffix,$query,$attr);
        if(!$sr){
            return "failed $query=>$return";
        }

        $hash=ldap_get_entries($ldap_connection,$sr);
        if($hash["count"]==0){return null;}
        for($i=0;$i<$hash["count"];$i++){

            if($this->must2Fields[$field]){
                $res[]=MultipleFields($first_field,$return,$hash[$i]);
            }else{

                for($z=0;$z<$hash[$i][strtolower($return)]["count"];$z++){
                    $res[]=$hash[$i][strtolower($return)][$z];
                }
            }

        }
        if($this->must2Fields[$field]){
            $res[]="</table>";
            return implode("\n",$res);
        }
        return implode(',',$res);

    }





}


function MultipleFields($firstField,$secondfield,$array){

    $first="<table style='width:100%'>";
    for($i=0;$i<$array[strtolower($firstField)]["count"];$i++){
        $first=$first."
		<tr>
		<td nowrap>{$array[strtolower($firstField)][$i]}</td>
		</tr>";

    }

    $first=$first."</table>";

    $html="<tr>
	<td nowrap style='border-bottom:1px solid #CCCCCC' valign='top'>$first</td>
	<td width=1% style='border-bottom:1px solid #CCCCCC' valign='top'>&nbsp;</td>
	<td nowrap style='border-bottom:1px solid #CCCCCC' valign='top'>
		<table style='width:100%'>";

    if($array[strtolower($secondfield)]["count"]==0){return null;}
    for($i=0;$i<$array[strtolower($secondfield)]["count"];$i++){
        $html=$html . "<tr>
		<td width=1%><img src='img/fw_bold.gif'></td>
		<td nowrap>{$array[strtolower($secondfield)][$i]}</td></tr>";

    }
    $html=$html."</table>";

    return $html;

}


class main_perso{
    var $main_array=array();
    var $dn;
    var $list;

    function __construct(){
        if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}
        $this->load_array();
    }




    function add($key,$value,$type){
        $q=new mysql();
        $key=strtolower(trim($key));
        $sql="SELECT `postfix_key` FROM postfix_perso_settings WHERE postfix_key='$key'";
        $ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
        if($ligne["postfix_key"]<>null){
            $sql="UDPATE postfix_perso_settings SET postfix_value='$value',postfix_type='$type' WHERE postfix_key='$key'";
        }else{
            $sql="INSERT INTO postfix_perso_settings (postfix_key,postfix_type,postfix_value) VALUES('$key','$type','$value')";
        }

        $q->QUERY_SQL($sql,"artica_backup");
        if(!$q->ok){echo $q->mysql_error."\nfile: ".__FILE__."\nline:".__LINE__;return;}
        $sock=new sockets();
        $sock->getFrameWork("cmd.php?postfix-perso-settings=yes");


    }


    function load_array(){
        $res=array();
        $sql="SELECT * FROM postfix_perso_settings ORDER BY postfix_key";
        $q=new mysql();
        $results=$q->QUERY_SQL($sql,"artica_backup");
        if($results) {
            while ($ligne = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
                $res[$ligne["postfix_key"]] = array("VALUE" => $ligne["postfix_value"], "TYPE" => $ligne["postfix_type"]);

            }
        }

        $this->main_array=$res;


    }

    function Delete($key){
        $sql="DELETE FROM postfix_perso_settings WHERE postfix_key='$key'";
        $q=new mysql();
        $q->QUERY_SQL($sql,"artica_backup");
        if(!$q->ok){echo $q->mysql_error."\nfile: ".__FILE__."\nline:".__LINE__;return;}
        $sock=new sockets();
        $sock->getFrameWork("cmd.php?postfix-perso-settings=yes");

    }

    function replace_conf($conf){
        if(!is_array($this->main_array)){
            if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Postfix [PERSO]: No array, no config to replace, aborting\n";}
            writepostfixlogs("MAINPERSO:: No array, no config to replace, aborting");
            return $conf;}

        if(count($this->main_array)==0){
            if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Postfix [PERSO]: No array, no config to replace, aborting\n";}
            return;
        }

        if(!isset($GLOBALS["postconf"])){
            if(!class_exists("unix")){include_once('/usr/share/artica-postfix/framework/class.unix.inc');}
            $unix=new unix();
            $GLOBALS["postconf"]=$unix->find_program("postconf");
        }

        if(is_array($this->main_array)){
            if(count($this->main_array)>0){
                reset($this->main_array);
                foreach ($this->main_array as $num=>$val){
                    if(isset($ALREADY[$num])){continue;}
                    $ALREADY[$num]=true;
                    if($val["VALUE"]==null){
                        if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Postfix [PERSO]: No value for $num, skip\n";}
                        continue;
                    }

                    if($num=="inet_interfaces"){continue;}
                    if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Postfix [PERSO]: $num = {$val["VALUE"]}\n";}
                    system("{$GLOBALS["postconf"]} -e \"$num={$val["VALUE"]}\" >/dev/null 2>&1");
                }
            }
        }
    }

}

function bouce_template_file_default(){}


class bounces_templates{
    var $templates_array;


    function __construct(){
        $sock=new sockets();
        $ini=new Bs_IniHandler();
        $ini->loadString(@file_get_contents('/etc/artica-postfix/settings/Daemons/PostfixBounceTemplatesIniConf'));
        $this->templates_array=$ini->_params;
        $this->templates_array["failure_template"]["Body"]=$sock->GET_INFO("postfix_failure_template");
        $this->templates_array["delay_template"]["Body"]=$sock->GET_INFO("postfix_delay_template");
        $this->templates_array["success_template"]["Body"]=$sock->GET_INFO("postfix_success_template");
        $this->templates_array["verify_template"]["Body"]=$sock->GET_INFO("postfix_verify_template");
        $this->BuildDefaults();


    }


    function BuildDefaults(){
        if($this->templates_array["failure_template"]["Charset"]==null){$this->templates_array["failure_template"]["Charset"]="ISO-8859-1";}
        if($this->templates_array["failure_template"]["From"]==null){$this->templates_array["failure_template"]["From"]="MAILER-DAEMON (Mail Delivery System)";}
        if($this->templates_array["failure_template"]["Subject"]==null){$this->templates_array["failure_template"]["Subject"]="Undelivered Mail Returned to Sender";}
        if($this->templates_array["failure_template"]["Postmaster-Subject"]==null){$this->templates_array["failure_template"]["Postmaster-Subject"]=$this->templates_array["failure_template"]["Subject"];}


        if($this->templates_array["failure_template"]["Body"]==null){
            $this->templates_array["failure_template"]["Body"]=
                "This is the mail system at host \$myhostname.\n\nI'm sorry to have to inform you that your message could not\nbe delivered to one or more recipients. It's attached below.\n\nFor further assistance, please send mail to postmaster.\n\nIf you do so, please include this problem report. You can\ndelete your own text from the attached returned message.\n\n";
        }



        if($this->templates_array["delay_template"]["Charset"]==null){$this->templates_array["delay_template"]["Charset"]="ISO-8859-1";}
        if($this->templates_array["delay_template"]["From"]==null){$this->templates_array["delay_template"]["From"]="MAILER-DAEMON (Mail Delivery System)";}
        if($this->templates_array["delay_template"]["Subject"]==null){$this->templates_array["delay_template"]["Subject"]="Delayed Mail (still being retried)";}
        if($this->templates_array["delay_template"]["Postmaster-Subject"]==null){$this->templates_array["delay_template"]["Postmaster-Subject"]=$this->templates_array["failure_template"]["Subject"];}


        if($this->templates_array["delay_template"]["Body"]==null){
            $this->templates_array["delay_template"]["Body"]=
                "\nThis is the mail system at host \$myhostname.\n\n####################################################################\n# THIS IS A WARNING ONLY.  YOU DO NOT NEED TO RESEND YOUR MESSAGE. #\n####################################################################\n\nYour message could not be delivered for more than \$delay_warning_time_hours hour(s).\nIt will be retried until it is \$maximal_queue_lifetime_days day(s) old.\n\nFor further assistance, please send mail to postmaster.\n\nIf you do so, please include this problem report. You can\ndelete your own text from the attached returned message.\n\n";
        }


        if($this->templates_array["success_template"]["Charset"]==null){$this->templates_array["success_template"]["Charset"]="ISO-8859-1";}
        if($this->templates_array["success_template"]["From"]==null){$this->templates_array["success_template"]["From"]="MAILER-DAEMON (Mail Delivery System)";}
        if($this->templates_array["success_template"]["Subject"]==null){$this->templates_array["success_template"]["Subject"]="Successful Mail Delivery Report";}
        if($this->templates_array["success_template"]["Postmaster-Subject"]==null){$this->templates_array["success_template"]["Postmaster-Subject"]=$this->templates_array["failure_template"]["Subject"];}


        if($this->templates_array["success_template"]["Body"]==null){
            $this->templates_array["success_template"]["Body"]=
                "\nThis is the mail system at host \$myhostname.\n\nYour message was successfully delivered to the destination(s)\nlisted below. If the message was delivered to mailbox you will\nreceive no further notifications. Otherwise you may still receive\nnotifications of mail delivery errors from other systems.\n\n                   The mail system\n;";
        }

        if($this->templates_array["verify_template"]["Charset"]==null){$this->templates_array["verify_template"]["Charset"]="ISO-8859-1";}
        if($this->templates_array["verify_template"]["From"]==null){$this->templates_array["verify_template"]["From"]="MAILER-DAEMON (Mail Delivery System)";}
        if($this->templates_array["verify_template"]["Subject"]==null){$this->templates_array["verify_template"]["Subject"]="Mail Delivery Status Report";}
        if($this->templates_array["verify_template"]["Postmaster-Subject"]==null){$this->templates_array["verify_template"]["Postmaster-Subject"]=$this->templates_array["failure_template"]["Subject"];}


        if($this->templates_array["verify_template"]["Body"]==null){
            $this->templates_array["verify_template"]["Body"]=
                "\nThis is the mail system at host \$myhostname.\n\nEnclosed is the mail delivery report that you requested.\n\n                   The mail system\n";
        }


    }


    function Save(){
        $sock=new sockets();
        $conf="#Bounce templates for postfix saved ".date('Y-m-d H:i:s')."\n";
        foreach ($this->templates_array as $num=>$ligne){
            $tp=explode("\n",$ligne["Body"]);
            $Body=null;
            foreach ($tp as $line){
                if(trim($line)==null){continue;}
                $Body=$Body.$line."\n";
            }

            $sock->SaveConfigFile($Body,"postfix_$num");
            $conf=$conf ."\n$num = <<EOF\n";
            $conf=$conf ."Charset: {$ligne["Charset"]}\n";
            $conf=$conf ."From:  {$ligne["From"]}\n";
            $conf=$conf ."Subject: {$ligne["Subject"]}\n";
            $conf=$conf ."\n";
            $conf=$conf ."$Body";
            $conf=$conf ."\n\n";
            $conf=$conf ."EOF\n";
            unset($this->templates_array[$num]["Body"]);
        }


        reset($this->templates_array);
        $ini=new Bs_IniHandler();
        $ini->_params=$this->templates_array;
        $PostfixBounceTemplatesIniConf=$ini->toString();
        $sock->SaveConfigFile($PostfixBounceTemplatesIniConf,"PostfixBounceTemplatesIniConf");
        $sock->SaveConfigFile($conf,"PostfixBounceTemplatesFileContent");


    }


}

function array_postfix_status(){
    $array[]="POSTFIX";

    $array[]="FETCHMAIL";
    $array[]="ASSP";
    $array[]="AMAVISD";
    $array[]="AMAVISD_MILTER";
    $array[]="SPAMASSASSIN";
    $array[]="SPAMASS_MILTER";
    $array[]="DNSMASQ";
    $array[]="APP_CLUEBRINGER";
    $array[]="MIMEDEFANG";
    $array[]="MIMEDEFANGX";
    $array[]="DKIM_FILTER";
    $array[]="SPFMILTER";
    $array[]="CLAMAV";
    $array[]="FRESHCLAM";
    $array[]="MAILSPY";
    $array[]="KAVMILTER";
    $array[]="KAS_MILTER";
    $array[]="KAS3";
    $array[]="MAILARCHIVER";
    $array[]="BOGOM";
    $array[]="MILTER_GREYLIST";
    $array[]="POLICYD_WEIGHT";
    $array[]="MAILMAN";
    $array[]="APP_CYRUS_IMAP";
    $array[]="MAILARCHIVER";
    $array[]="APP_OPENDKIM";
    $array[]="APP_MILTER_DKIM";
    $array[]="APP_ZARAFA";
    $array[]="APP_ZARAFA_SPOOLER";
    $array[]="APP_ZARAFA_GATEWAY";
    $array[]="APP_ZARAFA_MONITOR";
    $array[]="APP_ZARAFA_DAGENT";
    $array[]="APP_ZARAFA_ICAL";
    $array[]="APP_ZARAFA_LICENSED";
    return $array;

}