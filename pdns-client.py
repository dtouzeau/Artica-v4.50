#!/usr/bin/python -u

import sys, os, time
import random
sys.path.append('/usr/share/artica-postfix/ressources')
import socket
import logging
import string
import re
import traceback as tb
import tld
from urlparse import urlparse
from unix import *
from ufdbclass import *
from postgressql import *
from netaddr import IPNetwork, IPAddress
global DEBUG_CLIENT
global MEMFILTER
global MEMHOST


class DNSLookup(object):
    """Handle PowerDNS pipe-backend domain name lookups."""
    ttl = 30


    def __init__(self, query,ufdb,MEM,PDSNInUfdbWebsite,PDNSUseHostsTable,postgres,PDNSFilterUsername,PDNSLocalDomains,PDNSWpad,WPADLIST,PDSNInUfdb,Features):
        self.results = []
        global MEMFILTER
        global MEMHOST
        if len(query)==6: (_type, qname, qclass, qtype, _id, ip) = query
        if len(query)==7: (_type, qname, qclass, qtype, _id, ip,ip2) = query
            
        self.has_result = False
        self.PDSNInUfdbWebsite=PDSNInUfdbWebsite
        self.PDNSUseHostsTable=PDNSUseHostsTable
        self.WEBFILTERING_MEM=MEM
        self.PDNSFilterUsername=PDNSFilterUsername
        self.PDNSLocalDomains=PDNSLocalDomains
        self.PDNSWpad=PDNSWpad
        self.WPADLIST=WPADLIST
        self.PDSNInUfdb=PDSNInUfdb
        self.q=postgres
        self.AllowSquidSkype=int(Features["AllowSquidSkype"])
        self.PDNSClientTTL=int(Features["PDNSClientTTL"])
        INTERNAL_MEMORY_LOCAL={}
        if self.PDNSClientTTL<1: self.PDNSClientTTL=3600
        
        
        if  self.PDNSFilterUsername=="": self.PDNSFilterUsername="dns_service"

        mainsite="localhost.localdomain"
        qname_lower = qname.lower()
        ufdb.NoOutput=True
        
        if qname_lower=="in-addr.arpa": return None
        if qname_lower=="arpa": return None
        if len(qname_lower)==0: return None
       
        logging.debug("[CLIENT] ---------------------------------------------------------")
        logging.debug("[CLIENT] [DNSLookup()]: Type.........:"+str(_type))
        logging.debug("[CLIENT] [DNSLookup()]: QName........:"+str(qname))
        logging.debug("[CLIENT] [DNSLookup()]: QClass.......:"+str(qclass))
        logging.debug("[CLIENT] [DNSLookup()]: QType........:"+str(qtype))
        logging.debug("[CLIENT] [DNSLookup()]: ID...........:"+str(_id))
        logging.debug("[CLIENT] [DNSLookup()]: Client addr..:"+str(ip))
        logging.debug("[CLIENT] [DNSLookup()]: Allow Skype..:"+str(self.AllowSquidSkype))
        
        if is_valid_ip(qname_lower):
            logging.debug("[CLIENT] Ip addr sent from query:"+str(qname_lower)+" --> ABorting")
            return None
        
        if self.PDNSWpad==1:
            matches=re.search('^wpad',qname_lower)
            if matches:
                for Line in self.WPADLIST:
                    Line=Line.strip()
                    if Line=="": continue
                    logging.debug("PDNSWpad Type:'"+str(qtype)+" "+str(Line)+"'")
                    vals=Line.split("|")
                    if len(vals)==0: continue
                    check_network=vals[0]
                    return_ip=vals[1]
                    logging.info("PDNSWpad Type:'"+str(qtype)+" "+str(check_network)+" -->"+str(ip))
                    if IPAddress(ip) not in IPNetwork(check_network):
                        logging.debug("PDNSWpad Type:'"+str(qtype)+" "+str(check_network)+" -->"+str(ip)+" FALSE")
                        continue
                    
                    logging.debug("PDNSWpad Type:'"+str(qtype)+" "+str(ip)+" -->"+str(return_ip)+" TRUE")
                    if qtype == 'SOA':
                        self.has_result = True
                        self.results.append('DATA\t%s\t%s\t%s\t3600'+str(self.PDNSClientTTL)+'\t1\tns1.localhost.localdomain.\tadmin.test.soa\t2014032110\t10800\t3600\t604800\t'+str(self.PDNSClientTTL) % (mainsite, qclass, qtype))
                        return None
                    if qtype == 'ANY':
                        self.has_result = True
                        self.results.append("DATA\t%s\t%s\t%s\t%d\t%d\t%s" % (qname, 'IN', 'A', self.PDNSClientTTL, int(_id), return_ip))
                        return None
                    if qtype == 'A':
                        self.has_result = True
                        self.results.append("DATA\t%s\t%s\t%s\t%d\t%d\t%s" % (qname, 'IN', 'A', self.PDNSClientTTL, int(_id), return_ip))
                        return None                    
                    
                        
        
                
            
        
        
        if qname_lower in self.PDNSLocalDomains:
            logging.debug("[CLIENT] LOCAL DOMAIN:"+str(qname_lower)+" --> ABorting")
            return None



        mainsite=self.get_familysite(qname_lower)
        logging.debug("[CLIENT] [DNSLookup()]: Master Domain: %s" % mainsite)
        KeyMem=str(qname_lower)+str(ip)
        
        if mainsite in self.PDNSLocalDomains:
            logging.debug("[CLIENT] LOCAL DOMAIN:"+str(mainsite)+" --> ABorting")
            return None
        
        
        if len(INTERNAL_MEMORY_LOCAL)>100000: INTERNAL_MEMORY_LOCAL={}
        if not qname_lower in INTERNAL_MEMORY_LOCAL:
            matches=re.search('^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)\.in-addr\.arpa',qname_lower)
            if matches:
                logging.debug("[CLIENT] ----------------------ARPA QUERY---------------------")
                ipaddr=matches.group(4)+"."+matches.group(3)+"."+matches.group(2)+"."+matches.group(1)
                sql="SELECT fullhostname FROM hostsnet WHERE ipaddr='"+ipaddr+"' LIMIT 1"
                logging.debug('[CLIENT] [PDNSUseHostsTable] =="'+str(sql)+'" --> QUERY')
                zrows=self.q.QUERY_SQL(sql)
                if len(zrows)==0: INTERNAL_MEMORY_LOCAL[qname_lower]=True
                if len(zrows)>0:
                    fullhostname=zrows[0][0]
                    logging.debug("[CLIENT] ARPA QUERY "+ipaddr+" --> [FOUND]")
                    if qtype == 'SOA':
                        self.has_result = True
                        self.results.append('DATA\t%s\t%s\t%s\t3600\t1\tns1.localhost.localdomain.\tadmin.test.soa\t2014032110\t10800\t3600\t604800\t3600' % ("in-addr.arpa", qclass, qtype))
                        return None
                    if qtype == 'ANY':
                        self.has_result = True
                        self.results.append("DATA\t%s\t%s\t%s\t%d\t%d\t%s" % (qname, 'IN', 'PTR', DNSLookup.ttl, int(_id), fullhostname))
                        return None
                    if qtype == 'A':
                        self.has_result = True
                        self.results.append("DATA\t%s\t%s\t%s\t%d\t%d\t%s" % (qname, 'IN', 'PTR', DNSLookup.ttl, int(_id), fullhostname))
                        return None           
            
            
        #101.56.168.192.in-addr.arpa. 86400 IN   PTR     srv.example.com.
        
        matches=re.search('\.in-addr\.arpa',qname_lower)
        if matches:
            logging.debug('[CLIENT] [PDNSUseHostsTable] ==in-addr.arpa--> [SKIP]')
            return None
        
        
        WebFiltering=False
        if self.PDSNInUfdb==1:
            if KeyMem in MEMFILTER:
                logging.info('[CLIENT] ['+qname_lower+'] FOUND IN MEMORY')
                self.has_result = True
                WebFiltering=True
                
                
                
# -------------------------------------------------------------------------------------------------------------------SKYPE WHITELIST------------        
        if self.PDSNInUfdb==1:
            if self.AllowSquidSkype==1:
                CHECKSKYPE=False
                matches=re.search('\*\.(skype|cloudapp|akadns)\.(com|net)$',qname_lower)
                if matches:
                    logging.debug('[CLIENT] [AllowSquidSkype] == "'+qname_lower+'" --> [SKIP]')
                    self.PDSNInUfdb=0
                    CHECKSKYPE=False
                
                
                matches=re.search('(^|\.)(skype|cloudapp|akadns)\.(com|net)$',qname_lower)
                if matches: CHECKSKYPE=True          
                logging.debug('[CLIENT] [AllowSquidSkype] == "'+qname_lower+'" --> CHECKSKYPE=['+str(CHECKSKYPE)+']')    
                    
                if CHECKSKYPE:    
                    if qname_lower=="skypedata.akadns.net":
                        logging.debug('[CLIENT] [AllowSquidSkype] == "'+qname_lower+'" --> [SKIP]')
                        self.PDSNInUfdb=0                  
                    matches=re.search('\.skype\.(com|net)$',qname_lower)
                    if matches:
                        logging.debug('[CLIENT] [AllowSquidSkype] == "'+qname_lower+'" --> [SKIP]')
                        self.PDSNInUfdb=0  
                    matches=re.search('-skype\.(trafficmanager|cloudapp)\.net$',qname_lower)
                    if matches:
                        logging.debug('[CLIENT] [AllowSquidSkype] == "'+qname_lower+'" --> [SKIP]')
                        self.PDSNInUfdb=0  
                    matches=re.search('\.skypedata\.akadns\.net$',qname_lower)
                    if matches:
                        logging.debug('[CLIENT] [AllowSquidSkype] == "'+qname_lower+'" --> [SKIP]')
                        self.PDSNInUfdb=0  
                    matches=re.search('.skype-(.*?)\.akadns\.net$',qname_lower)
                    if matches:
                        logging.debug('[CLIENT] [AllowSquidSkype] == "'+qname_lower+'" --> [SKIP]')
                        self.PDSNInUfdb=0  
                    matches=re.search('^skype-(.*?)\.(cloudapp|akadns)\.net$',qname_lower)
                    if matches:
                        logging.debug('[CLIENT] [AllowSquidSkype] == "'+qname_lower+'" --> [SKIP]')
                        self.PDSNInUfdb=0  
                    matches=re.search('^skype[a-z]+-(.*?)\.(trafficmanager|cloudapp)\.net$',qname_lower)
                    if matches:
                        logging.debug('[CLIENT] [AllowSquidSkype] == "'+qname_lower+'" --> [SKIP]')
                        self.PDSNInUfdb=0        
# -------------------------------------------------------------------------------------------------------------------SKYPE WHITELIST------------                    
        logging.info('[CLIENT] ['+qname_lower+'] Must run Web-filtering ? ['+str(self.PDSNInUfdb)+']')        
        
        
        if self.PDNSUseHostsTable==1:
            if not WebFiltering:
                if len(INTERNAL_MEMORY_LOCAL)>100000: INTERNAL_MEMORY_LOCAL={}
                if not qname_lower in INTERNAL_MEMORY_LOCAL:
                    sql="SELECT ipaddr,fullhostname,hostname,hostalias1,hostalias2,hostalias3,hostalias4 FROM hostsnet WHERE "
                    sql=sql+" (fullhostname = '"+qname_lower+"')"
                    sql=sql+" OR (hostname = '"+qname_lower+"')"
                    sql=sql+" OR (hostalias1 = '"+qname_lower+"')"
                    sql=sql+" OR (hostalias2 = '"+qname_lower+"')"
                    sql=sql+" OR (hostalias3 = '"+qname_lower+"')"
                    sql=sql+" OR (hostalias4 = '"+qname_lower+"') LIMIT 1"
                
                
                    logging.debug('[CLIENT] [PDNSUseHostsTable] =="'+str(sql)+'" --> QUERY')
                    zrows=self.q.QUERY_SQL(sql)
                    
                    if len(zrows)==0: INTERNAL_MEMORY_LOCAL[qname_lower]=True
                    
                    if len(zrows)>0:
                        ipadrr=zrows[0][0]
                        fullhostname=zrows[0][1]
                        hostname=zrows[0][2]
                        hostalias1=zrows[0][3]
                        hostalias2=zrows[0][4]
                        hostalias3=zrows[0][5]
                        hostalias4=zrows[0][6]
                        logging.debug('[CLIENT] [PDNSUseHostsTable] =="'+str(ipadrr)+'" --> "'+mainsite+'" [FOUND]')
                        if qtype == 'SOA':
                            self.has_result = True
                            self.results.append('DATA\t%s\t%s\t%s\t3600\t1\tns1.localhost.localdomain.\tadmin.test.soa\t2014032110\t10800\t3600\t604800\t3600' % (mainsite, qclass, qtype))
                            return None
                        if qtype == 'ANY':
                            self.has_result = True
                            self.results.append("DATA\t%s\t%s\t%s\t%d\t%d\t%s" % (qname, 'IN', 'A', DNSLookup.ttl, int(_id), ipadrr))
                            return None
                        if qtype == 'A':
                            self.has_result = True
                            self.results.append("DATA\t%s\t%s\t%s\t%d\t%d\t%s" % (qname, 'IN', 'A', DNSLookup.ttl, int(_id), ipadrr))
                            return None                
                

        
        if self.PDSNInUfdb==0:
            logging.debug('[CLIENT] Web-Filtering DNS is OFF --> Aborting Web-Filtering')
            return None
        
        
        if not "." in qname_lower:
            logging.debug('[CLIENT] Not an fqdn ' +qname_lower+'--> Aborting Web-Filtering')
            return None
        
                      
        ToUfdb="http://"+qname_lower+" "+ip+"/"+ip+" "+self.PDNSFilterUsername+" GET myip=127.0.0.1 myport=3128\n"
        mainsite=self.get_familysite(qname_lower)
        

            
        if not WebFiltering:
            try:
                ufdb.NoOutput=True
                
                logging.debug('[CLIENT] ['+qname_lower+'] Pass to Web-Filtering service "'+ToUfdb+'"')
                if ufdb.SendToUfdb(ToUfdb,0,"","http://"+qname_lower,ip,self.PDNSFilterUsername,qname_lower):
                    if ufdb.InactiveService:
                        logging.debug('[CLIENT] ['+qname_lower+'] FATAL ERROR by Web-Filtering service')
                        return None
                        
                        
                    logging.debug('[CLIENT] ['+qname_lower+'] BLOCKED by Web-Filtering service')
                    WebFiltering=True
                    self.has_result = True
                    MEMFILTER.append(KeyMem)
                    logging.debug('[CLIENT] ['+qname_lower+'] '+str(len(MEMFILTER))+" items in memory")
                
            except Exception as e:
                logging.info(tb.format_exc())
                logging.info('FATAL! Exception while requesting Web-Filtering Engine service')
                return None
        
        if not self.has_result :
            logging.debug('[CLIENT] ['+qname_lower+'] has_result (FALSE), finish')
            return None
        
        
        if WebFiltering:
            PDSNInUfdbWebsite=GET_INFO_STR("PDSNInUfdbWebsite")
            if PDSNInUfdbWebsite=="": PDSNInUfdbWebsite="127.0.0.1"
            if not is_valid_ip(PDSNInUfdbWebsite): PDSNInUfdbWebsite="127.0.0.1"
            logging.debug('[CLIENT] ['+qname_lower+'] BLOCKED by Web-Filtering service --> '+PDSNInUfdbWebsite)
            
            if qtype == 'SOA':
                self.results.append('DATA\t%s\t%s\t%s\t3600\t1\tns1.localhost.localdomain.\tadmin.test.soa\t2014032110\t10800\t3600\t604800\t3600' % (mainsite, qclass, qtype))
                return None
              
            if qtype == 'ANY':
                self.results.append("DATA\t%s\t%s\t%s\t%d\t%d\t%s" % (qname, 'IN', 'A', DNSLookup.ttl, int(_id), PDSNInUfdbWebsite))
                return None
                            
            if qtype == 'A':
                self.results.append("DATA\t%s\t%s\t%s\t%d\t%d\t%s" % (qname, 'IN', 'A', DNSLookup.ttl, int(_id), PDSNInUfdbWebsite))
                return None

    def extract_root_domain(self,domain):
        domain = domain.rstrip("0123456789").rstrip(":").strip(".")
        temp = domain.split('.')
        is_level2_tld = len(temp[-1]) <= 3 and temp[-2] in ('com', 'net', 'org', 'co', 'edu', 'mil', 'gov', 'ac')

        if len(temp) <= 2 or len(temp) == 3 and is_level2_tld:
            return domain, ""
        elif is_level2_tld:
            return ".".join(temp[-3:]), ".".join(temp[:-3])
        else:
            return '.'.join(temp[-2:]), ".".join(temp[:-2])

    def get_familysite(self,domain):
        try:
            return tld.get_fld(domain, fix_protocol=True)
        except tld.exceptions.TldDomainNotFound:
            toplevel, topdom = self.extract_root_domain(domain)
            return toplevel

    def str_result(self):
        if self.has_result:
            return '\n'.join(self.results)
        else:
            return ''

class PowerDNSbackend(object):
    """The main PowerDNS pipe backend process."""

    def __init__(self, filein, fileout):
        self.filein = filein
        self.fileout = fileout
        PDNSClientDebug=GET_INFO_INT("PDNSClientDebug")
        self.PDSNInUfdbWebsite=GET_INFO_INT("PDSNInUfdbWebsite")
        self.PDNSFilterUsername=GET_INFO_STR("PDNSFilterUsername")
        self.PDNSUseHostsTable=GET_INFO_INT("PDNSUseHostsTable")
        self.PDNSEnableStatistics=GET_INFO_INT("PDNSEnableStatistics")
        self.PowerDNSLogsQueries=GET_INFO_INT("PowerDNSLogsQueries")
        self.PDNSWpad=GET_INFO_INT("PDNSWpad")
        self.PDSNInUfdb=GET_INFO_INT("PDSNInUfdb");
        self.EnableUfdbGuard=GET_INFO_INT("EnableUfdbGuard");
        self.WEBFILTERING_MEM={}
        self.q=Postgres()
        self.q.log=logging
        self.PDNSLocalDomains=[]
        self.WPADLIST=[]
        self.Features={}
        DEBUG_CLIENT=False
        
        self.Features["AllowSquidSkype"]=GET_INFO_INT("AllowSquidSkype")
        if self.EnableUfdbGuard ==0: self.PDSNInUfdb=0
        PDNSClientTTL=GET_INFO_INT("PDNSClientTTL")
        if PDNSClientTTL<1: PDNSClientTTL=3600
        self.Features["PDNSClientTTL"]=PDNSClientTTL
        
        
        logging.info('Use DNS Filtering ..........: '+str(self.PDSNInUfdb))
        
        if self.PDSNInUfdb==1:
            if self.PDNSFilterUsername=="":self.PDNSFilterUsername="dns_service"
            logging.info('Use DNS Filtering IP.........: '+str(self.PDSNInUfdbWebsite))
            logging.info('DNS Filtering Virtual user...: '+str(self.PDNSFilterUsername))
            logging.info('DNS Filtering TTL............: '+str(PDNSClientTTL))
            logging.info('Debug Mode...................: '+str(PDNSClientDebug))
        
        if PDNSClientDebug ==1:
            levelLOG=logging.DEBUG
            DEBUG_CLIENT=True
            
            
        if os.path.exists("/etc/powerdns/domains.lst"):
            f = open("/etc/powerdns/domains.lst", 'r')
            self.PDNSLocalDomains = f.readlines()
            f.close()
            
        if self.PDNSWpad==1:
            if os.path.exists("/etc/powerdns/wpad.lst"):
                f = open("/etc/powerdns/wpad.lst", 'r')
                self.WPADLIST = f.readlines()
                f.close()
                
                
        
        self.ufdbclass=UFDB(logging,DEBUG_CLIENT)
        self._process_requests()   # main program loop

    def _process_requests(self):
        """main program loop"""
        first_time = True
        while 1:
            rawline = self.filein.readline()
            if rawline == '':
                logging.debug('EOF')
                return  # EOF detected
            line = rawline.rstrip()

            logging.debug('received from pdns:%s' % line)

            if first_time:
                queryHelo = line.split('\t')
                if queryHelo[0] == 'HELO':
                    self._fprint('OK\tArtica DNS Backend firing up')
                else:
                    self._fprint('FAIL')
                    logging.debug('[CLIENT] HELO input not received - execution aborted')
                    rawline = self.filein.readline()  # as per docs - read another line before aborting
                    logging.debug('[CLIENT] calling sys.exit()')
                    sys.exit(1)
                first_time = False
            else:
                query = line.split('\t')
                
                if len(query) < 6:
                    self._fprint('LOG\tPowerDNS sent unparseable line')
                    self._fprint('FAIL')
                else:
                    
                    
                    if len(query)==6: (_type, qname, qclass, qtype, _id, ip) = query
                    if len(query)==7: (_type, qname, qclass, qtype, _id, ip,ip2) = query
                        
                    if self.PDNSEnableStatistics==1: logging.info('ARTSTATS|'+str(qname)+'|'+str(qtype)+'|'+str(ip))
                
                    lookup = DNSLookup(query,self.ufdbclass,self.WEBFILTERING_MEM,self.PDSNInUfdbWebsite,self.PDNSUseHostsTable,self.q,self.PDNSFilterUsername,self.PDNSLocalDomains,self.PDNSWpad,self.WPADLIST,self.PDSNInUfdb,self.Features)
                    if lookup.has_result:
                        self.WEBFILTERING_MEM=lookup.WEBFILTERING_MEM
                        pdns_result = lookup.str_result()
                        self._fprint(pdns_result)
                        logging.debug('[CLIENT] DNSLookup result(%s)' % pdns_result)
                    self._fprint('END')

    def _fprint(self, message):
        """Print the given message with newline and flushing."""
        self.fileout.write(message + '\n')
        self.fileout.flush()
        logging.debug('[CLIENT] sent to pdns:%s' % message)

if __name__ == '__main__':
    
    MEMFILTER=[]
    MEMHOST=[]
    PDNSClientDebug=GET_INFO_INT("PDNSClientDebug")
    levelLOG=logging.INFO
    if PDNSClientDebug==1: levelLOG=logging.DEBUG 
    logger = logging.getLogger(__name__)
    logging.basicConfig(format='%(asctime)s [%(levelname)s] [%(process)d] %(message)s',filename='/var/log/pdns-client.debug',  filemode='a',level=levelLOG)
    logging.raiseExceptions = False
    logging.info('[CLIENT] Starting Thread.....debug = '+str(PDNSClientDebug))
    if not os.path.exists("/etc/artica-postfix/settings/Daemons/PDNSUseHostsTable"): file_put_contents("/etc/artica-postfix/settings/Daemons/PDNSUseHostsTable","1")
    
    infile = sys.stdin
    #sys.stdout.close()
    #outfile = os.fdopen(1, 'w', 1)
    outfile = sys.stdout
    try:
        PowerDNSbackend(infile, outfile)
    except:
        logging.info(tb.format_exc())
        logging.info('[CLIENT] execution failure:' + str(sys.exc_info()[0]))
        raise