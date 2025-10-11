import re
import logging
from netaddr import IPNetwork, IPAddress
global DEBUG

try:
    import dns.resolver
    resolver = dns.resolver.Resolver()
    resolver.nameservers = ['1.1.1.1','8.8.8.8']
    resolver.timeout = 0.90
    resolver.lifetime = 0.90
except:
    print('Error : Unable To Load dns Module.')
    print('For python3 : pip3 install dnspython3')
    print('For python2 : pip install dnspython3')
    sys.exit(0)

whitelist_src = set()
whitelist_dst = set()

def rblcheck(searchIp):

    rblDict = {'b.barracudacentral.org': 'b.barracudacentral.org',
               'bl.spamcop.net': 'bl.spamcop.net',
               'zen.spamhaus.org': 'zen.spamhaus.org',
               'dnsbl.cobion.com': 'dnsbl.cobion.com',
               'hostkarma.junkemailfilter.com':'hostkarma.junkemailfilter.com',
               'bl.suomispam.net':'bl.suomispam.net',
               'bl.drmx.org':'bl.drmx.org',
               'spam.spamrats.com':'spam.spamrats.com',
               'bl.nosolicitado.org':' bl.nosolicitado.org',
               'dnsbl-1.uceprotect.net':'dnsbl-1.uceprotect.net'
               }


    for rblOrg in rblDict:

        ipRev = '.'.join(searchIp.split('.')[::-1])
        searchQuery = ipRev + '.' + rblOrg
        try:
            resolver.query(searchQuery, 'A')
            log_info( "[ FOUND ] %s in %s" % (searchIp, rblOrg))
            return True
        except:
            continue

    return False


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
            return False;
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
    read_list("/etc/unbound/whitelist_src.db", whitelist_src)
    read_list("/etc/unbound/whitelist_dst.db", whitelist_dst)    
    return True

def deinit(id): return True

def inform_super(id, qstate, superqstate, qdata): return True

def get_remote_ip(qstate):
    try:
        reply_list = qstate.mesh_info.reply_list

        while reply_list:
            if reply_list.query_reply: return reply_list.query_reply.addr
            reply_list = reply_list.next
    except:
         log_info("DNS Filter FATAL: From: get_remote_ip(qstate)")
        
    
    return "0.0.0.0"

def operate(id, event, qstate, qdata):
    
    if (event == MODULE_EVENT_NEW) or (event == MODULE_EVENT_PASS):
        global FILTERCLASS
        global DEBUG
        
        domainname = qstate.qinfo.qname_str.rstrip('.')
        SourceIPAddr=get_remote_ip(qstate)
        
        if DEBUG: log_info("From <"+SourceIPAddr+"> Q=<"+domainname+">")
        
        if(SourceIPAddr=="127.0.0.1"):
            if DEBUG: log_info("From <"+SourceIPAddr+"> SKIP FILTER")
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            return True
        
        if (check_name(domainname, whitelist_dst)):
            if DEBUG: log_info("To <"+domainname+"> In White list destination, SKIP FILTER")
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            return True
        
        if(CheckSources(SourceIPAddr)):
            if DEBUG: log_info("From <"+SourceIPAddr+"> In White list Source Address, SKIP FILTER")
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            return True            
            
        
        if not rblcheck(SourceIPAddr):
            if DEBUG: log_info("DNSBL <"+SourceIPAddr+"> response PASS")
            qstate.ext_state[id] = MODULE_WAIT_MODULE
            return True

        if DEBUG: log_info("DNSBL <" + SourceIPAddr + "> response BLOCK")
        
        msg = DNSMessage(qstate.qinfo.qname_str, RR_TYPE_A, RR_CLASS_IN, PKT_QR | PKT_RA | PKT_AA)
        if (qstate.qinfo.qtype == RR_TYPE_A) or (qstate.qinfo.qtype == RR_TYPE_ANY):
            if DEBUG: log_info("FILTERCLASS response to <127.0.0.1>")
            redirect_ttl=2880
            redirect_ip="127.0.0.1"
            msg.answer.append("%s %d IN A %s" % (qstate.qinfo.qname_str, int(redirect_ttl), redirect_ip))
    
            if not msg.set_return_msg(qstate):
                if DEBUG: log_info("MODULE_ERROR")
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
        qstate.ext_state[id] = MODULE_FINISHED 
        return True
      
    log_err("pythonmod: bad event")
    qstate.ext_state[id] = MODULE_ERROR
    return True