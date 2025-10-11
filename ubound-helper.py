#!/usr/bin/python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
import re
import logging
import time
from unix import *
from dnsfilterclass import *
from dnsfirewallclass import *
from classartmem import *
from netaddr import IPNetwork, IPAddress
import traceback as tb
global FILTERCLASS
global DEBUG
global EnableUnboundLogQueries
global EnableDNSFirewall
global FIREWALLCLASS
global memdb
global EnableDNSFilterd

whitelist_src = set()
whitelist_dst = set()

def CheckLocalConfig():
    global DEBUG
    DEBUG=False
    if not os.path.exists("/etc/dnsfilterd/dnsfilterd.conf"):
        UnboundDebugFirewall=GET_INFO_INT("UnboundDebugFirewall")
        if UnboundDebugFirewall==1:
            log_info("Enter into debugging Firewall module")
            DEBUG=True
        return
    
    for line in open('/etc/dnsfilterd/dnsfilterd.conf','r').readlines():
        matches=re.search("^ufdb-debug-filter\s+on",line)
        if matches:DEBUG=True

    if not DEBUG:
        UnboundDebugFirewall=GET_INFO_INT("UnboundDebugFirewall")
        if UnboundDebugFirewall==1:
            log_info("Enter into debugging Firewall module")
            DEBUG=True


pass

def read_list(name, xlist):
    try:
        with open(name, "r") as f:
            for line in f:
                xlist.add(line.rstrip())
    except IOError:
        log_info("DNS Filter FATAL: Unable to open %s" % name)
        return False
    log_info("DNS Filter "+name+" "+str(len(xlist))+" item(s)")
        
        
def check_name(name, xlist):
    while True:
        if (name in xlist):
            return True
        elif (name.find('.') == -1):
            return False
        else:
            name = name[name.find('.')+1:]
            
def CheckSources(ipaddr):
    for line in whitelist_src:
        if ipaddr==line: return True
        if line.find('/')>0:
            if IPAddress(ipaddr) in IPNetwork(line): return True
    return False



def init(id, cfg):
    log_info("DNS Filter initialize..")
    global FILTERCLASS
    global FIREWALLCLASS
    global EnableUnboundLogQueries
    global EnableDNSFirewall
    global EnableDNSFilterd
    global memdb
    global DEBUG
    EnableUnboundLogQueries=GET_INFO_INT("EnableUnboundLogQueries")
    EnableDNSFirewall = GET_INFO_INT("EnableDNSFirewall")
    EnableDNSFilterd =  GET_INFO_INT("EnableDNSFilterd")
    CheckLocalConfig()
    memdb=art_memcache()
    log_info("MODULE_INIT: DNS Filter initialize DEBUG = %s" % DEBUG)
    FILTERCLASS=DNSFILTER(False)
    FIREWALLCLASS=dnsfw(DEBUG)
    FIREWALLCLASS.DEBUG = DEBUG
    FIREWALLCLASS.DNSMessage = DNSMessage
    FIREWALLCLASS.RR_CLASS_IN = RR_CLASS_IN
    FIREWALLCLASS.PKT_QR = PKT_QR
    FIREWALLCLASS.PKT_RA = PKT_RA
    FIREWALLCLASS.log_info = log_info

    read_list("/etc/unbound/whitelist_src.db", whitelist_src)
    read_list("/etc/unbound/whitelist_dst.db", whitelist_dst)    
    return True

def deinit(id): return True

def inform_super(id, qstate, superqstate, qdata): return True



def memcached_get(sourceip,domainname):
    KeyToFind = str("DNSFILTER:" + sourceip+":"+domainname)
    Value = memdb.memcache_get(KeyToFind)
    if Value is None: return 0
    return Value

def log_extract(zlogs):
    if len(zlogs) == 0:
        log_info(str("log_extract .. no row!"))
        return False

    for text in zlogs:log_info(text)



def memcached_set(value,sourceip,domainname):
    KeyToFind = str("DNSFILTER:" + sourceip + ":" + domainname)
    memdb.memcache_set(KeyToFind,value,300)


def memcached_hits():
    if not memdb.memcache_incr("DNSFILTERD_HITS"):
        log_info(str("ERROR DNSFILTERD_HITS %s" % memdb.error ))
    return True

def memcached_blocks():
    memdb.memcache_incr("DNSFILTERD_BLOCKS")
    return True


def extract_type(qstate, qinfo):
    q_type = ''
    if qstate and qstate.qinfo.qtype_str:
        q_type = qstate.qinfo.qtype_str
    elif qinfo and qinfo.qtype_str:
        q_type = qinfo.qtype_str
    return is_unknown(q_type)

def extract_ip(qstate):
    q_ip = ''

    try:
        if qstate and qstate.mesh_info.reply_list:
            reply_list = qstate.mesh_info.reply_list
            while reply_list:
                if reply_list.query_reply:
                    q_ip = reply_list.query_reply.addr
                    break
                reply_list = reply_list.next
    except Exception as e:
        log_info(str("extract_ip:: {}".format(e)))
        pass
    return is_unknown(q_ip)

def extract_name_qstate(qstate):
    q_name = ''
    try:
        if qstate and qstate.qinfo and qstate.qinfo.qname_str and qstate.qinfo.qname_str.strip():
            q_name = qstate.qinfo.qname_str.rstrip('.')
        elif qstate and qstate.return_msg and qstate.return_msg.qinfo and qstate.return_msg.qinfo.qname_str.strip():
            q_name = qstate.return_msg.qinfo.qname_str.rstrip('.')
    except Exception as e:
        log_info(str("extract_name_qstate:: Failed get_q_name_qstate: {}".format(e)))
        pass
    return is_unknown(q_name)

def is_unknown(x):
    try:
        if not x or x is None:
            return 'Unknown'
    except Exception as e:
        for a in e:
            log_info(str("is_unknown:: Failed is_unknown: {}".format(a)))
        pass
    return x


def operate(id, event, qstate, qdata):
    
    if (event == MODULE_EVENT_NEW) or (event == MODULE_EVENT_PASS):
        global FILTERCLASS
        global EnableDNSFirewall
        global EnableDNSFilterd
        global FIREWALLCLASS
        global DEBUG
        global EnableUnboundLogQueries
        domainname  =""
        qstate_valid = False
        try:
            if qstate is not None and qstate.qinfo.qtype is not None:
                qstate_valid = True
                q_type = qstate.qinfo.qtype
                domainname = extract_name_qstate(qstate)
        except Exception as e:
            log_info(str("OPERATE: ERROR: qstate_valid: {}: {}".format(event, e)))
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            return True
            pass


        QueryType       = extract_type(qstate, qstate.qinfo)
        SourceIPAddr    = extract_ip(qstate)
        prefix          = "[%s] %s:%s" % (SourceIPAddr,QueryType,domainname)

        if(SourceIPAddr=="127.0.0.1"):
            if DEBUG: log_info(str("OPERATE: %s SKIP FILTER" % prefix))
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            return True

        start_time = time.time()
        if DEBUG: log_info(str("OPERATE: framework version %s %s Firewall=%s DNSFilter=%s (old method)" % (sys.version_info,prefix,EnableDNSFirewall,EnableDNSFilterd) ) )
        memcached_hits()
        if EnableDNSFirewall == 1:
            FIREWALLCLASS.DEBUG=DEBUG
            firewallid=FIREWALLCLASS.operate(SourceIPAddr, QueryType,domainname,"IN")
            FIREWALLCLASS.DNSMessage  = DNSMessage
            FIREWALLCLASS.invalidateQueryInCache    = invalidateQueryInCache
            FIREWALLCLASS.storeQueryInCache = storeQueryInCache
            FIREWALLCLASS.RR_CLASS_IN = RR_CLASS_IN
            FIREWALLCLASS.PKT_QR      = PKT_QR
            FIREWALLCLASS.PKT_RA      = PKT_RA
            FIREWALLCLASS.RR_TYPE_A   = RR_TYPE_A
            if DEBUG: log_info(str("OPERATE: %s Firewall id=%s" % (prefix, firewallid)))
            if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
            zContinue=False

            if firewallid == 0:
                if FIREWALLCLASS.expose_extensions:
                    FIREWALLCLASS.final_syslog("PASS",0)
                    log_info(FIREWALLCLASS.unbound_log)

            if firewallid > 0 :
                try:
                    if DEBUG: log_info(str("OPERATE: %s Firewall --> generate_response " % prefix))
                    if FIREWALLCLASS.generate_response(qstate, domainname, QueryType, qstate.qinfo.qtype, firewallid):
                        log_info(FIREWALLCLASS.unbound_log)
                        if FIREWALLCLASS.MAIN_ACTION=="REFUSED":
                            if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                            qstate.return_rcode = RCODE_REFUSED
                            qstate.ext_state[id] = MODULE_FINISHED
                            return True

                        if FIREWALLCLASS.MAIN_ACTION == "NXDOMAIN":
                            if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                            qstate.return_rcode = RCODE_NXDOMAIN
                            qstate.ext_state[id] = MODULE_FINISHED
                            return True

                        if FIREWALLCLASS.MAIN_ACTION == "SERVFAIL":
                            if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                            qstate.return_rcode = RCODE_SERVFAIL
                            qstate.ext_state[id] = MODULE_FINISHED
                            return True

                        if FIREWALLCLASS.MAIN_ACTION == "ANSWER":
                            if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                            qstate=FIREWALLCLASS.qstate
                            qstate.return_rcode = RCODE_NOERROR
                            qstate.ext_state[id] = MODULE_FINISHED
                            return True

                        if FIREWALLCLASS.MAIN_ACTION == "PASS":
                            if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                            zContinue=True

                        if not zContinue:
                            if DEBUG: log_info(str("OPERATE: %s generate_response %s is not understood " % (prefix,FIREWALLCLASS.MAIN_ACTION)))
                            if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                            qstate.ext_state[id] = MODULE_WAIT_MODULE
                            return True

                    if not zContinue:
                        if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                        if DEBUG: log_info(str("OPERATE: %s Firewall --> generate_response return FALSE [SERVFAIL]" % prefix))
                        state.return_rcode = RCODE_SERVFAIL
                        qstate.ext_state[id] = MODULE_FINISHED
                        return True
                except:
                    if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                    log_info(str("OPERATE: ERROR %s %s" % (prefix, tb.format_exc())))
                    qstate.ext_state[id] = MODULE_WAIT_MODULE
                    return True

        if EnableDNSFilterd==0:
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            return True
            

        Cached=memcached_get("0.0.0.0",domainname)
        if Cached==2:
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            return True

        Cached = memcached_get(SourceIPAddr, "*")
        if Cached==2:
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            return True

        Cached = memcached_get(SourceIPAddr, domainname)
        if Cached==2:
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            return True


        if (check_name(domainname, whitelist_dst)):
            if DEBUG: log_info(str("To <%s> In White list destination, SKIP FILTER" % domainname))
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            memcached_set(2, "0.0.0.0", domainname)
            return True
        
        if(CheckSources(SourceIPAddr)):
            if DEBUG: log_info(str("From <%s> In White list Source Address, SKIP FILTER" % SourceIPAddr))
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            memcached_set(2, SourceIPAddr, "*")
            return True            
            
        
        if not FILTERCLASS.SendToUfdb(SourceIPAddr,domainname):
            if DEBUG: log_info(str("SendToUfdb response PASS"))
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            memcached_set(2, SourceIPAddr, domainname)
            return True
        
        msg = DNSMessage(qstate.qinfo.qname_str, RR_TYPE_A, RR_CLASS_IN, PKT_QR | PKT_RA | PKT_AA)
        if (qstate.qinfo.qtype == RR_TYPE_A) or (qstate.qinfo.qtype == RR_TYPE_ANY):
            if DEBUG: log_info(str("FILTERCLASS response to <%s>"  % FILTERCLASS.redirect_ip))
            memcached_blocks()
            msg.answer.append("%s %d IN A %s" % (qstate.qinfo.qname_str, int(FILTERCLASS.redirect_ttl), FILTERCLASS.redirect_ip))
            if EnableUnboundLogQueries==1: log_info(str("%s %s BLOCK IN" % (SourceIPAddr,domainname)))

    
            if not msg.set_return_msg(qstate):
                if DEBUG: log_info(str("MODULE_ERROR"))
                qstate.ext_state[id] = MODULE_ERROR 
                return True

            #we don't need validation, result is valid
            qstate.return_msg.rep.security = 2
            qstate.return_rcode = RCODE_NOERROR
            qstate.ext_state[id] = MODULE_FINISHED 
            return True
        else:
            qstate.ext_state[id] = MODULE_WAIT_MODULE 
            return True

    if event == MODULE_EVENT_MODDONE:
        msg = qstate.return_msg
        if not msg:
            qstate.ext_state[id] = MODULE_FINISHED
            return True

        QueryType = extract_type(qstate, qstate.qinfo)
        SourceIPAddr = extract_ip(qstate)

        if (SourceIPAddr == "127.0.0.1"):
            qstate.ext_state[id] = MODULE_FINISHED
            return True


        if EnableDNSFirewall == 0:
            qstate.ext_state[id] = MODULE_FINISHED
            return True


        rep = msg.rep
        if DEBUG: log_info(str("MODULE_EVENT_MODDONE: [%s] [%s] an_numrrsets = %s" % (SourceIPAddr, QueryType, rep.an_numrrsets)))

        if rep.an_numrrsets == 0:
            qstate.ext_state[id] = MODULE_FINISHED
            return True

        rc = rep.flags & 0xf


        if (rc == RCODE_NOERROR):
            domainname = extract_name_qstate(qstate)
            blockit=False

            for i in range(0, rep.an_numrrsets):
                rk = rep.rrsets[i].rk
                decoded_aswer = None
                ResponseType  = rk.type_str.upper()
                ResponseName  = rk.dname_str.rstrip('.').lower()
                prefix = "[%s] %s:%s" % (SourceIPAddr, QueryType, ResponseName)
                try:
                    data          = rep.rrsets[i].entry.data
                    for z in range(0, data.count):
                        answer = data.rr_data[z]
                        decoded_aswer = FIREWALLCLASS.decode_aswer(answer,ResponseType)
                        if DEBUG: log_info(str("MODULE_EVENT_MODDONE: [%s] [%s] %s -> %s (%s) [%s]" % (SourceIPAddr, domainname, QueryType, ResponseName, ResponseType,decoded_aswer)))
                        if decoded_aswer is not None:
                            firewallid = FIREWALLCLASS.operate(SourceIPAddr, QueryType, domainname, "OUT",decoded_aswer)
                            if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                            if firewallid == 0:
                                if FIREWALLCLASS.expose_extensions:
                                    FIREWALLCLASS.final_syslog("PASS", 0)
                                    log_info(FIREWALLCLASS.unbound_log)

                            if firewallid > 0:
                                if DEBUG: log_info(str("OUT: From <%s> Q=<%s> %s rule=%s" % (SourceIPAddr, domainname, decoded_aswer,firewallid)))
                                blockit = True
                                break


                    if blockit:break
                except:
                    if DEBUG: log_info(str("MODULE_EVENT_MODDONE: [%s]" % tb.format_exc()))
                    continue

            if not blockit:
                qstate.ext_state[id] = MODULE_FINISHED
                return True

            try:
                if DEBUG: log_info(str("OPERATE: %s Firewall [OUT] --> generate_response Ruleid %s " % (prefix,firewallid)))
                if FIREWALLCLASS.generate_response(qstate, domainname, QueryType, qstate.qinfo.qtype, firewallid):
                    log_info(FIREWALLCLASS.unbound_log)
                    zContinue=False
                    if FIREWALLCLASS.MAIN_ACTION == "REFUSED":
                        if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                        qstate.return_rcode = RCODE_REFUSED
                        qstate.ext_state[id] = MODULE_FINISHED
                        return True

                    if FIREWALLCLASS.MAIN_ACTION == "NXDOMAIN":
                        if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                        qstate.return_rcode = RCODE_NXDOMAIN
                        qstate.ext_state[id] = MODULE_FINISHED
                        return True

                    if FIREWALLCLASS.MAIN_ACTION == "SERVFAIL":
                        if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                        qstate.return_rcode = RCODE_SERVFAIL
                        qstate.ext_state[id] = MODULE_FINISHED
                        return True

                    if FIREWALLCLASS.MAIN_ACTION == "ANSWER":
                        if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                        qstate = FIREWALLCLASS.qstate
                        qstate.return_rcode = RCODE_NOERROR
                        qstate.ext_state[id] = MODULE_FINISHED
                        return True

                    if FIREWALLCLASS.MAIN_ACTION == "PASS":
                        if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                        zContinue = True

                    if not zContinue:
                        if DEBUG: log_info(str("OPERATE: %s generate_response %s is not understood " % (prefix, FIREWALLCLASS.MAIN_ACTION)))
                        if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                        qstate.ext_state[id] = MODULE_WAIT_MODULE
                        return True

                if not zContinue:
                    if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                    if DEBUG: log_info(str("OPERATE: %s Firewall [OUT] --> generate_response return FALSE [SERVFAIL]" % prefix))
                    state.return_rcode = RCODE_SERVFAIL
                    qstate.ext_state[id] = MODULE_FINISHED
                    return True
            except:
                if DEBUG: log_extract(FIREWALLCLASS.INTERNAL_LOGS)
                log_info(str("OPERATE: ERROR %s %s" % (prefix, tb.format_exc())))
                qstate.ext_state[id] = MODULE_FINISHED
                return True

        qstate.ext_state[id] = MODULE_FINISHED
        return True
      
    log_err("pythonmod: bad event")
    qstate.ext_state[id] = MODULE_ERROR
    return True