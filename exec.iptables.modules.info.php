<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');

dump_modules();

function dump_modules(){
    $unix=new unix();
    $Modules["xt_fuzzy"]=true;
    $Modules["xt_geoip"]=true;
    $Modules["xt_iface"]=true;
    $Modules["xt_ipp2p"]=true;
    $Modules["xt_ipv4options"]=true;
    $Modules["xt_length2"]=true;
    $Modules["xt_lscan"]=true;
    $Modules["xt_psd"]=true;
    $Modules["xt_quota2"]=true;
    $Modules["xt_tls"]=true;
    $Modules["ip_tables"]=true;
    $Modules["x_tables"]=true;
    $Modules["ip6_tables"]=true;
    $Modules["ip_set"]=true;
    $Modules["nfnetlink_queue"]=true;
    $Modules["nfnetlink_log"]=true;
    $Modules["nfnetlink"]=true;
    $Modules["xt_log"]=true;
    $Modules["xt_addrtype"]=true;
    $Modules["xt_cluster"]=true;
    $Modules["xt_CONNSECMARK"]=true;
    $Modules["xt_dscp"]=true;
    $Modules["xt_hl"]=true;
    $Modules["xt_ipvs"]=true;
    $Modules["xt_mac"]=true;
    $Modules["xt_nfacct"]=true;
    $Modules["xt_pkttype"]=true;
    $Modules["xt_recent"]=true;
    $Modules["xt_state"]=true;
    $Modules["xt_tcpudp"]=true;
    $Modules["xt_AUDIT"]=true;
    $Modules["xt_comment"]=true;
    $Modules["xt_conntrack"]=true;
    $Modules["xt_DSCP"]=true;
    $Modules["xt_HL"]=true;
    $Modules["xt_l2tp"]=true;
    $Modules["xt_mark"]=true;
    $Modules["xt_NFLOG"]=true;
    $Modules["xt_policy"]=true;
    $Modules["xt_REDIRECT"]=true;
    $Modules["xt_statistic"]=true;
    $Modules["xt_TEE"]=true;
    $Modules["xt_bpf"]=true;
    $Modules["xt_connbytes"]=true;
    $Modules["xt_cpu"]=true;
    $Modules["xt_ecn"]=true;
    $Modules["xt_HMARK"]=true;
    $Modules["xt_LED"]=true;
    $Modules["xt_multiport"]=true;
    $Modules["xt_NFQUEUE"]=true;
    $Modules["xt_quota"]=true;
    $Modules["xt_sctp"]=true;
    $Modules["xt_string"]=true;
    $Modules["xt_time"]=true;
    $Modules["xt_cgroup"]=true;
    $Modules["xt_connlabel"]=true;
    $Modules["xt_CT"]=true;
    $Modules["xt_esp"]=true;
    $Modules["xt_IDLETIMER"]=true;
    $Modules["xt_length"]=true;
    $Modules["xt_nat"]=true;
    $Modules["xt_osf"]=true;
    $Modules["xt_rateest"]=true;
    $Modules["xt_SECMARK"]=true;
    $Modules["xt_tcpmss"]=true;
    $Modules["xt_TPROXY"]=true;
    $Modules["xt_CHECKSUM"]=true;
    $Modules["xt_connlimit"]=true;
    $Modules["xt_dccp"]=true;
    $Modules["xt_hashlimit"]=true;
    $Modules["xt_ipcomp"]=true;
    $Modules["xt_limit"]=true;
    $Modules["xt_ndpi"]=true;
    $Modules["xt_owner"]=true;
    $Modules["xt_RATEEST"]=true;
    $Modules["xt_set"]=true;
    $Modules["xt_TCPMSS"]=true;
    $Modules["xt_TRACE"]=true;
    $Modules["xt_CLASSIFY"]=true;
    $Modules["xt_connmark"]=true;
    $Modules["xt_devgroup"]=true;
    $Modules["xt_helper"]=true;
    $Modules["xt_iprange"]=true;
    $Modules["xt_LOG"]=true;
    $Modules["xt_NETMAP"]=true;
    $Modules["xt_physdev"]=true;
    $Modules["xt_realm"]=true;
    $Modules["xt_socket"]=true;
    $Modules["xt_TCPOPTSTRIP"]=true;
    $Modules["xt_u32"]=true;
    $Modules["nf_conncount"]=true;
    $Modules["nf_conntrack_netbios_ns"]=true;
    $Modules["nf_conntrack_tftp"]=true;
    $Modules["nf_flow_table_inet"]=true;
    $Modules["nf_log_ipv6"]=true;
    $Modules["nf_nat_ipv6"]=true;
    $Modules["nf_reject_ipv4"]=true;
    $Modules["nf_tproxy_ipv4"]=true;
    $Modules["nf_conntrack"]=true;
    $Modules["nf_conntrack_netlink"]=true;
    $Modules["nf_defrag_ipv4"]=true;
    $Modules["nf_flow_table_ipv4"]=true;
    $Modules["nf_log_netdev"]=true;
    $Modules["nf_nat_irc"]=true;
    $Modules["nf_reject_ipv6"]=true;
    $Modules["nf_tproxy_ipv6"]=true;
    $Modules["nf_conntrack_amanda"]=true;
    $Modules["nf_conntrack_pptp"]=true;
    $Modules["nf_defrag_ipv6"]=true;
    $Modules["nf_flow_table_ipv6"]=true;
    $Modules["nf_nat"]=true;
    $Modules["nf_nat_pptp"]=true;
    $Modules["nf_socket_ipv4"]=true;
    $Modules["nf_conntrack_broadcast"]=true;
    $Modules["nf_conntrack_proto_gre"]=true;
    $Modules["nf_dup_ipv4"]=true;
    $Modules["nf_log_arp"]=true;
    $Modules["nf_nat_amanda"]=true;
    $Modules["nf_nat_proto_gre"]=true;
    $Modules["nf_socket_ipv6"]=true;
    $Modules["nf_conntrack_ftp"]=true;
    $Modules["nf_conntrack_sane"]=true;
    $Modules["nf_dup_ipv6"]=true;
    $Modules["nf_log_bridge"]=true;
    $Modules["nf_nat_ftp"]=true;
    $Modules["nf_nat_sip"]=true;
    $Modules["nf_synproxy_core"]=true;
    $Modules["nf_conntrack_h323"]=true;
    $Modules["nf_conntrack_sip"]=true;
    $Modules["nf_dup_netdev"]=true;
    $Modules["nf_log_common"]=true;
    $Modules["nf_nat_h323"]=true;
    $Modules["nf_nat_snmp_basic"]=true;
    $Modules["nf_tables"]=true;
    $Modules["nf_conntrack_irc"]=true;
    $Modules["nf_conntrack_snmp"]=true;
    $Modules["nf_flow_table"]=true;
    $Modules["nf_log_ipv4"]=true;
    $Modules["nf_nat_ipv4"]=true;
    $Modules["nf_nat_tftp"]=true;
    $Modules["nf_tables_set"]=true;
    $Modules["nft_chain_nat_ipv4"]=true;
    $Modules["nft_connlimit"]=true;
    $Modules["nft_dup_netdev"]=true;
    $Modules["nft_fib_netdev"]=true;
    $Modules["nft_log"]=true;
    $Modules["nft_numgen"]=true;
    $Modules["nft_redir"]=true;
    $Modules["nft_reject_inet"]=true;
    $Modules["nft_tunnel"]=true;
    $Modules["nft_chain_nat_ipv6"]=true;
    $Modules["nft_counter"]=true;
    $Modules["nft_fib"]=true;
    $Modules["nft_flow_offload"]=true;
    $Modules["nft_masq"]=true;
    $Modules["nft_objref"]=true;
    $Modules["nft_redir_ipv4"]=true;
    $Modules["nft_reject_ipv4"]=true;
    $Modules["nft_chain_route_ipv4"]=true;
    $Modules["nft_ct"]=true;
    $Modules["nft_fib_inet"]=true;
    $Modules["nft_fwd_netdev"]=true;
    $Modules["nft_masq_ipv4"]=true;
    $Modules["nft_osf"]=true;
    $Modules["nft_redir_ipv6"]=true;
    $Modules["nft_reject_ipv6"]=true;
    $Modules["nft_chain_route_ipv6"]=true;
    $Modules["nft_dup_ipv4"]=true;
    $Modules["nft_fib_ipv4"]=true;
    $Modules["nft_hash"]=true;
    $Modules["nft_masq_ipv6"]=true;
    $Modules["nft_queue"]=true;
    $Modules["nft_reject"]=true;
    $Modules["nft_socket"]=true;
    $Modules["nft_compat"]=true;
    $Modules["nft_dup_ipv6"]=true;
    $Modules["nft_fib_ipv6"]=true;
    $Modules["nft_limit"]=true;
    $Modules["nft_nat"]=true;
    $Modules["nft_quota"]=true;
    $Modules["nft_reject_bridge"]=true;
    $Modules["nft_tproxy"]=true;
    ksort($Modules);
    $lsmod=$unix->find_program("lsmod");
    $modinfo=$unix->find_program("modinfo");

    exec("$lsmod 2>&1",$results);

    foreach ($results as $line){
        if(!preg_match("#^(.+?)\s+[0-9]+#",$line,$re)){continue;}
        $loaded=trim(strtolower($re[1]));
        $zLOADED[$loaded]=true;
        $Modules[$loaded]=true;
    }

    $FINAL=array();
    foreach ($Modules as $modulename=>$none){
        $modlower=trim(strtolower($modulename));
        $results=array();
        exec("$modinfo $modulename 2>&1",$results);

        foreach ($results as $line){
            $line=trim($line);
            if($line==null){continue;}

            if(preg_match("#ERROR: Module .*? not found#",$line)){
                $FINAL[$modulename]["INSTALLED"]=false;
                $FINAL[$modulename]["LOADED"]=0;
                continue;
            }

            if(preg_match("#alias:\s+(.+?)$#",$line,$re)){
                $FINAL[$modulename]["ALIASES"][]=trim($re[1]);
                continue;
            }
            if(preg_match("#description:\s+(.+?)$#",$line,$re)){
                $FINAL[$modulename]["DESC"]=trim($re[1]);
                continue;
            }
            if(preg_match("#filename:\s+(.+?)$#",$line,$re)){
                $FINAL[$modulename]["filename"]=trim($re[1]);
                continue;
            }

            if(preg_match("#depends:\s+(.+?)$#",$line,$re)){
                $FINAL[$modulename]["depends"]=trim($re[1]);
                continue;
            }
            if(preg_match("#depends:$#",$line,$re)){continue;}

            if(preg_match("#(parm|license|author|retpoline|intree|name|vermagic|sig_id|signer|sig_key|sig_hashalgo|signature):\s+(.+?)$#",$line,$re)){
                continue;
            }
            if(preg_match("#^[0-9A-Z:]+$#",$line)){
                continue;
            }

            echo "Not found \"$line\"\n";
        }

        if(!isset($FINAL[$modulename]["INSTALLED"])){
            if(isset($FINAL[$modulename]["filename"])) {
                $FINAL[$modulename]["INSTALLED"]=true;

                if(isset($zLOADED[$modlower])){
                    $FINAL[$modulename]["LOADED"]=1;
                }else{
                    $FINAL[$modulename]["LOADED"]=0;
                }
            }
        }
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IPTABLES_MODULES_INFO",serialize($FINAL));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IPTABLES_MODULES_INFO_TIME",time());






}
