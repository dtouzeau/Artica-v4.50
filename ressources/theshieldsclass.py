#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
from unix import *
import os.path
import datetime
import urllib
from unix import *
from categorizeclass import *
from dnsblclass import *
from goldlic import *
from phpserialize import serialize, unserialize
from netaddr import IPNetwork, IPAddress
from inspect import currentframe
import traceback as tb
from time import time
import dns.resolver
import syslog
from datetime import datetime
from time import time
import cPickle as pickle
import re
import logging

class theshields:
    def __init__(self,CategorizeClass,initLogger=None):
        self.INTERNAL_LOGS = []
        self.debug           = False
        self.KSRNEnable      = GET_INFO_INT("KSRNEnable")
        self.ksrn_dnsbl      = GET_INFO_INT("EnableKSRNDNSBL")
        self.krsn_debug      = GET_INFO_INT("KRSN_DEBUG")
        self.ksrn_liceense   = GET_INFO_INT("KSRN_LICENSE")
        self.ksrn_porn       = GET_INFO_INT("KsrnPornEnable")
        self.mixed_adult     = GET_INFO_INT("KsrnMixedAdultEnable")
        self.QueryIPAddr     = GET_INFO_INT("KsrnQueryIPAddr")
        self.BackupServer    = GET_INFO_INT("KsrnQueryUseBackup")
        self.DisableAdvert   = GET_INFO_INT("KsrnDisableAdverstising")
        self.TheShieldsCguard= GET_INFO_INT("TheShieldsCguard")
        self.TheShieldDebug  = GET_INFO_INT("TheShieldDebug")
        self.TheShieldDebugUsers = GET_INFO_INT("TheShieldDebugUsers")
        self.TheShieldLogDNSQ = GET_INFO_INT("TheShieldLogDNSQ")
        self.EnableITChart    = GET_INFO_INT("EnableITChart")
        self.KSRNIpaddrDebug  = GET_INFO_INT("KSRNIpaddrDebug")
        self.ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")
        self.hatred_and_discrimination = GET_INFO_INT("KsrnHatredEnable")
        self.KsrnDisableGoogleAdServices = GET_INFO_INT("KsrnDisableGoogleAdServices")
        self.KSRNEmergency   = GET_INFO_INT("KSRNEmergency")
        self.KSRNServerTimeOut = GET_INFO_INT("KSRNServerTimeOut")
        self.shields_cache_path = "/home/artica/SQLITE/theshield.service.db"
        self.TheShieldLogsQueries=0
        self.CountUsersTime=""
        self.LoggerQuery=""
        self.logger=None
        self.local_cache={}
        self.count_sleep = 0
        self.HIT=0
        self.license = False
        self.version = "42.0"
        self.trackers_dst = read_list_from_path("/usr/share/artica-postfix/ressources/databases/trackers.txt")
        self.dnsbl = query_dnsbl()
        self.corp_gold = False
        self.slicef = "/var/lib/squid/.srn.lic"
        self.catz = CategorizeClass
        self.memdb = art_memcache()
        self.ksrn_liceense = GET_INFO_INT("KSRN_LICENSE")
        self.whitelist_dst=set()
        self.catz.KsrnDisableGoogleAdServices=self.KsrnDisableGoogleAdServices
        self.error=""
        self.src_ip=""
        self.username=""
        self.mac=""
        self.start_time=None
        self.sitename=""
        self.cache_time=3600
        self.local_cache_count=0
        self.debug_cache=False
        self.cloud_direct=False
        self.LoggerCache=object
        self.DNSError=False
        self.OutScreen=False
        self.cloud_t1=False
        self.cloud_t2 = False
        self.cloud_t3 = False
        self.cloud_t4 = False
        self.StatsUsersDB={}
        self.duration_text=""
        if self.KSRNServerTimeOut==0: self.KSRNServerTimeOut=5
        self.TheShieldsLogTimes=0
        SET_INFO("KSRN_VERSION",self.version)
        self.memdb.ksrncache_set("KSRN_VERSION", self.version, 2590000 )
        if self.ExternalAclFirstDebug==1:self.TheShieldDebug=1
        if self.TheShieldDebug==1: self.debug=True
        self.catz.TheShieldLogDNSQ=self.TheShieldLogDNSQ
        if  self.debug:
            self.TheShieldLogsQueries=1
            self.ksrn_log("Starting The Shields class v%s L.97" % self.version)
            self.ksrn_log("TheShieldDebug:%s Starting The Shields class in debug mode, please carrefull the server load!" % self.TheShieldDebug)

        func="init"

        gold = isGoldkey()
        if gold.corp_gold():
            self.ksrn_liceense = 1
            if self.debug: self.ksrn_log( "[INFO]: License is Entreprise Gold License")


    def get_linenumber(self):
        cf = currentframe()
        return cf.f_back.f_lineno

    def set_logsqueries(self):
        if self.catz.logqueries==0:
            self.catz.logqueries =1
        else:
            self.catz.logqueries = 0

    def ilog(self,line,func,text):
        text="--------------: theshield:%s %s %s" % (line,func,text)
        if self.OutScreen: print(text)
        self.INTERNAL_LOGS.append(str(text))

    def str2bool(self,v):
        if v is None: return False
        return v.lower() in ("yes", "true", "t", "1", "oui", "si")

    def StrToMD5(self,value):
        value=str(value)
        value=value.encode('utf8')
        return str(hashlib.md5(value).hexdigest())

    def get_cache(self, domain):
        domain=str(domain)
        if domain in self.local_cache:
            try:
                return self.local_cache[domain]
            except:
                self.ksrn_log(tb.format_exc())

        smd5 = str("SHIELD.class.%s" % self.StrToMD5(domain))
        return self.memdb.ksrncache_get(smd5)


    def set_cache(self, domain, category_id):
        domain=str(domain)
        self.local_cache[domain] = category_id
        self.local_cache_count=self.local_cache_count+1
        smd5 = str("SHIELD.class.%s" % self.StrToMD5(domain))
        self.memdb.ksrncache_set(smd5,str(category_id),self.cache_time)

        if self.local_cache_count > 1500:
            self.local_cache={}
            self.local_cache_count = 0


    def ksrn_log(self,text):
        if self.OutScreen: print(text)
        sysDaemon=syslog
        sysDaemon.openlog("ksrn", syslog.LOG_PID)
        sysDaemon.syslog(syslog.LOG_INFO, "[SHIELD_CLASS]: %s" % text)


    def CountUsers(self):
        if len(self.src_ip)==0: return True
        if self.username is None: self.username=""
        if self.mac is None: self.mac=""
        if self.src_ip=="127.0.0.1": return True
        time_10mn = self.log_filename_time()
        time_10mn=time_10mn.replace(" ","_")
        skey="CountUsers.%s" % time_10mn
        if self.TheShieldDebugUsers == 1: self.ksrn_log("%s [CountUsers]: %s" % (time_10mn,skey))
        try:
            data=self.memdb.ksrncache_get(skey)
        except:
            self.ksrn_log("%s [CountUsers]: %s" % tb.format_exc)
            return True

        if data is None:
            ARRAY={}
        else:
            ARRAY=unserialize(data)


        if not "IPADDR" in ARRAY:
            if self.TheShieldDebugUsers == 1: self.ksrn_log("%s [CountUsers]: creating IPADDR key" % time_10mn)
            ARRAY["IPADDR"]={}

        if not "mac" in ARRAY:
            if self.TheShieldDebugUsers == 1: self.ksrn_log("%s [CountUsers]: creating self.mac key" % time_10mn)
            ARRAY["mac"]={}

        if not "username" in ARRAY:
            if self.TheShieldDebugUsers == 1: self.ksrn_log("%s [CountUsers]: creating self.username key" % time_10mn)
            ARRAY["username"]={}

        if not self.src_ip in ARRAY["IPADDR"]: ARRAY["IPADDR"][self.src_ip]=1
        cc=ARRAY["IPADDR"][self.src_ip]
        cc=cc+1
        if self.TheShieldDebugUsers == 1: self.ksrn_log("%s [CountUsers]: ipaddr=%s count=%s" % (time_10mn,self.src_ip,cc))
        ARRAY["IPADDR"][self.src_ip]=cc

        if len(self.username)>1:
            if not  self.username in ARRAY["username"]:
                ARRAY["username"][self.username]=1
            else:
                cc=ARRAY["username"][self.username]
                cc=cc+1
                ARRAY["username"][self.username]=cc


        if len(self.mac)>0:
            if not  self.mac in ARRAY["mac"]:
                ARRAY["mac"][self.mac]=1
            else:
                cc=ARRAY["mac"][self.mac]
                cc=cc+1
                if self.TheShieldDebugUsers == 1: self.ksrn_log("CountUsers: self.mac %s count=%s" % (self.mac, cc))
                ARRAY["mac"][self.mac]=cc

        try:
            if not self.memdb.ksrncache_set(skey,serialize(ARRAY)):
                if self.TheShieldDebugUsers == 1:
                    self.ksrn_log("%s [CountUsers]: ERROR REPORTED %s " % (self.memdb.error,skey))
                return False

        except:
            if self.TheShieldDebugUsers == 1: self.ksrn_log("%s [CountUsers]: SYSTEM ERROR Saving %s" % (time_10mn,tb.format_exc()))
            return False

        if self.TheShieldDebugUsers == 1: self.ksrn_log("%s [CountUsers]: %s SAVED SUCCESS % " % (time_10mn,skey))


    def operate(self,sitename):
        self.error=""
        self.categoy_id = 0
        self.ACTION = ""
        duration_array=[]
        starttime=time()
        func = "operate"
        self.start_time= datetime.now()
        self.sitename = sitename
        if self.catz.isArpa(sitename):
            self.sitename=self.catz.stripaddr
            if self.catz.is_ip_private(self.sitename):
                self.categoy_id=82
                return False


        full_cache  = "SRNRESULTS:%s" % sitename
        scache_key  = "SRN_CACHE_WHITE:%s" % sitename
        self.memdb.memcache_incr("KSRN_REQUESTS")

        if  self.krsn_debug==1:
            self.ksrn_log("* * * * * * * * * * * * * * * * O P E R A T E * * * * * * * * * * * * * L.255")
            self.ksrn_log("%s ANALYZE... L.253" % sitename)

        memwhite=self.get_cache(scache_key)
        if self.TheShieldLogsQueries==1:
            duration_array.append("275:%s" % self.memdb.TimeExec(starttime))

        if memwhite is not None:
            if  self.krsn_debug==1: self.ksrn_log("%s [DEBUG]: PASS  HIT whitelisted (Cache), aborting L.260" % sitename)
            self.error = ""
            self.ACTION = "WHITELIST"
            try:
                self.local_catz(sitename)
                self.categoy_id=self.catz.get_category(sitename)
            except:
                self.ksrn_log("%s [ERROR]: L.266 %" % (sitename,tb.format_exc()))
                self.categoy_id=0

            if  self.krsn_debug==1: self.ksrn_log("%s: [DEBUG] %s L.270"  % (sitename,self.memdb.TimeExec(starttime)))
            self.duration_text=" ".join(duration_array)
            return False

        try:
            if self.catz.admin_whitelist(sitename,False):
                if  self.krsn_debug==1: self.ksrn_log("%s: [DEBUG] MISS whitelisted, aborting L.276" % sitename)
                self.set_cache(scache_key,1)
                self.error = ""
                self.ACTION = "WHITELIST"
                self.local_catz(sitename)
                if self.categoy_id == 0: self.categoy_id = self.catz.get_category(sitename)
                if self.TheShieldLogsQueries == 1: duration_array.append("285:%s" % self.memdb.TimeExec(starttime))
                self.duration_text = " ".join(duration_array)
                return True
        except:
           self.ksrn_log("%s: ERROR L.286 --> %s" % (sitename, tb.format_exc()))


        if self.ksrn_liceense == 0:
            self.error="LICENSE_ERROR"
            self.ACTION = "PASS"
            self.local_catz(sitename)
            duration= self.memdb.TimeExec(starttime)
            if self.krsn_debug == 1: self.ksrn_log("%s: [DEBUG]: L.294 duration: %s" % (sitename,duration))
            if self.krsn_debug == 1: self.ksrn_log("%s: [ERROR]: L.295 Not a valid license" % sitename)
            self.duration_text = " ".join(duration_array)
            return False

        if self.KSRNEnable == 0:
            self.error="DISABLED"
            self.ACTION = "PASS"
            self.local_catz(sitename)
            duration = self.memdb.TimeExec(starttime)
            if self.krsn_debug == 1: self.ksrn_log("%s: [ERROR]: Module is Disabled (duration %s) L.304 "%  (sitename,duration))
            self.duration_text = " ".join(duration_array)
            return False



        if self.QueryIPAddr==0:
            matches=re.search('^[0-9\.]+$',sitename)
            if matches:
                self.error = "IPADDR"
                self.ACTION = "PASS"
                if self.debug: self.ksrn_log("%s: [DEBUG]: is an IP Address L.315" % sitename)
                self.local_catz(sitename)
                if self.debug: self.ksrn_log("%s: [DEBUG]:  (duration %s) L.316" % (sitename,self.memdb.TimeExec(starttime)))
                self.duration_text = " ".join(duration_array)
                return False

        if self.KSRNEmergency==1:
            self.error = "EMERGENCY"
            self.ACTION = "PASS"
            self.local_catz(sitename)
            duration = self.memdb.TimeExec(starttime)
            if self.debug: self.ksrn_log("%s: [DEBUG]: WARNING... Emergency Enabled (duration %s) L.326" % (sitename,duration))
            return False


        try:
            if self.catz.fixed_whitelist(sitename):
                self.set_cache(scache_key,1)
                self.error = ""
                self.ACTION = "PASS"
                self.local_catz(sitename)
                if self.categoy_id == 0: self.categoy_id = self.catz.get_category(sitename)
                duration = self.memdb.TimeExec(starttime)
                if self.debug: self.ksrn_log("%s: [DEBUG]: MISS whitelisted, aborting (duration %s) L.338" % (sitename,duration))
                return True
        except:
            self.ksrn_log("%s: [ERROR]: %s" % (sitename, tb.format_exc()))


        result_ip = self.get_cache(full_cache)
        if result_ip is not None:
            duration = self.memdb.TimeExec(starttime)
            if self.debug: self.ksrn_log("%s: [DEBUG]: get_cache(%s) HIT = %s (duration %s) L.347" % (sitename,full_cache,result_ip,duration))
        self.HIT=0
        start_time = time()
        if result_ip is None:
            category = self.local_catz(sitename)
            if self.debug: self.ksrn_log("%s MISS Local category=[%s] %s L.348" % (sitename, category,self.memdb.TimeExec(starttime)))
            if category > 0:
                result_ip = '127.12.%s.1' % self.categoy_id
                self.set_cache(full_cache, result_ip)
                results = self.undertand_ip(result_ip)
                self.run_stats(sitename)
                duration=self.memdb.TimeExec(start_time)
                if self.debug: self.ksrn_log("%s ENGINE=%s category[%s] %s %s after The Shields Query" % (sitename, self.ACTION, self.categoy_id, results, duration))


            start_time = time()
            if self.debug: self.ksrn_log("%s IP=%s" % (sitename,result_ip))
            if result_ip is None:
                result_ip = self.the_shield_query(sitename)
                if self.debug: self.ksrn_log("%s CLOUD --> the_shield_query(%s) = %s" % (sitename,sitename,result_ip))

            if result_ip is None:
                self.ACTION = "PASS"
                if self.debug: self.ksrn_log("%s result_ip is None --> PASS L.365" % sitename)
                self.duration_text = " ".join(duration_array)
                return False

            if self.debug: self.ksrn_log( "[DEBUG]: --> Save in memory %s" % result_ip)
            if not self.DNSError: self.set_cache(full_cache, result_ip)
        else:
            self.HIT=1
            if self.debug: self.ksrn_log("%s theshield.operate HIT [%s] L.379" % (sitename,result_ip))
        start_time=time()

        results=self.undertand_ip(result_ip)
        self.run_stats(sitename)
        if self.TheShieldLogsQueries == 1:
            duration_array.append("FINAL:%s" % self.memdb.TimeExec(start_time))
            duration_array.append("411:%s" % self.memdb.TimeExec(starttime))
        self.duration_text = " ".join(duration_array)
        return True

    def run_stats(self,sitename):
        sname = self.log_filename()
        provider = self.ACTION
        if provider == "REAFFECTED": return True
        if provider == "PASS": return True

        filename = "/var/log/squid/%s.ksrn" % sname
        time10mn = self.log_filename_time()
        try:
            category_int = int(self.categoy_id)
        except:
            category_int=self.catz.get_category(sitename)

        self.username =""
        self.mac=""
        end_time = datetime.now()
        time_diff = (end_time - self.start_time)
        duration = time_diff.total_seconds() * 1000



        try:
            filelogs = open(filename, 'a')
            filelogs.write("%s|%s|%s|%s|%s|%s|%s|%s\n" % (time10mn, self.username, self.src_ip, self.mac, category_int, sitename, provider, duration))
            filelogs.close()
        except:
            self.error="[ERROR]: While writing %s %s" % (filename,tb.format_exc())
            return False

        category_string = self.catz.category_int_to_string(category_int)
        self.memdb.memcache_incr("KSRN_DETECTED")
        self.ksrn_log("[DETECTED]: From %s [%s] (%s) category: %s (%s) to website %s " % (self.username, self.src_ip, self.mac, category_string, provider, sitename))

    def query_cguard(self,sitename):
        func="query_cguard"
        key="%s:%s" % (func,sitename)
        detects=[5026,5066,5113,5001,5058,5048,5035,5096,5019,5043,5045,5017,5010,5002,5036,5042,5005,5003,5029,5030,5024,5027,5107,5111,5093,5033,5104,5101,5114]
        category=self.get_cache(key)
        if category is not None:
            if self.TheShieldLogsQueries == 1: self.ksrn_log(
                "DNS %s category=%s HIT shield.query_cguard" % (sitename, category))
            if category == 0: return None
            if category in detects: return "127.96.0.%s" % category
            return None


        try:
            category=int(self.catz.get_category_cguard(sitename))
            if self.TheShieldLogsQueries == 1: self.ksrn_log("DNS %s category=%s MISS shield.query_cguard" % (sitename,category))
            self.set_cache(key,category)
            if self.debug:
                for sline in self.catz.INTERNAL_LOGS:
                    self.INTERNAL_LOGS.append(sline)
            if category == 0: return None
            if self.debug: self.ksrn_log( "[DEBUG]: --> %s = %s" % (sitename,category))
            if category in detects: return "127.96.0.%s" % category
            if self.debug: self.ksrn_log( "[DEBUG]: --> %s < == %s" % (sitename, "SKIP"))
            return None
        except:
            return None

    def get_host(self,sitename):
        resolver = dns.resolver.Resolver(configure=False)
        if len(self.catz.nameservers) == 0: self.catz.build_servernames()

        if self.TheShieldDebug==1:
            for nameserv in self.catz.nameservers: self.ksrn_log("%s: [DEBUG]: use NameServer %s L.456" % (sitename,nameserv))

        resolver = dns.resolver.Resolver(configure=False)
        resolver.timeout = self.KSRNServerTimeOut
        resolver.lifetime = self.KSRNServerTimeOut
        resolver.nameservers = self.catz.nameservers
        resolver_log=", ".join(resolver.nameservers)
        try:
            result = resolver.query(sitename, 'A')

        except dns.resolver.NoAnswer:
            if self.TheShieldDebug == 1: self.ksrn_log("%s: [ERROR]get_host: %s " % (sitename, "Reputation return no result"))
            return None

        except dns.name.LabelTooLong:
            if self.TheShieldDebug == 1: self.ksrn_log("%s: [ERROR]get_host: %s "  % (sitename, "LabelTooLong"))
            return None

        except dns.resolver.NoNameservers:
            if self.TheShieldDebug == 1: self.ksrn_log("%s: [ERROR]get_host: %s "  % (sitename, "NoNameservers"))
            return None

        except dns.resolver.NXDOMAIN:
            if self.TheShieldDebug == 1: self.ksrn_log("%s: [ERROR]get_host: %s "  % (sitename, "NXDOMAIN"))
            return None


        except dns.exception.Timeout:
            self.DNSError = True
            self.error = "Resolving Host [%s] reputation timed out (%s seconds max) DNS:[%s]" % (sitename,self.KSRNServerTimeOut,resolver_log)
            self.ksrn_log("%s: [ERROR]: Time Out Resolving host (%s seconds max) DNS:[%s]" % (sitename, resolver.lifetime,resolver_log))
            return None

        except:
            self.DNSError = True
            self.error = tb.format_exc()
            self.ksrn_log("%s: [ERROR]: %s" % (sitename, self.error))
            return None
        if self.TheShieldDebug == 1: self.ksrn_log("%s: * * * HOST Resolution: [%s] * * *" % (sitename, result[0]))
        return str(result[0])



    def the_shield_query(self,sitename):
        func="the_shield_query"
        start_time = time()

        self.DNSError=False
        if self.TheShieldsCguard==1:
            if self.debug: self.ksrn_log( "[DEBUG]: --> query_cguard(%s)" % sitename)
            self.incr_statsline()
            ResultsIP=self.query_cguard(sitename)
            if ResultsIP is not None: return ResultsIP

        encodepart = self.encrypt_upper(sitename)

        searchQuery = "%s.%s" % (encodepart, "crdf.artica.center")

        resolver = dns.resolver.Resolver(configure=False)
        if len(self.catz.nameservers) == 0: self.catz.build_servernames()

        if self.TheShieldDebug==1:
            for nameserv in self.catz.nameservers: self.ksrn_log("%s: [DEBUG]: use NameServer %s L.512" % (sitename,nameserv))


        resolver.nameservers = self.catz.nameservers
        if self.cloud_t1: resolver.nameservers = ['137.74.217.146']
        if self.cloud_t2: resolver.nameservers = ['137.74.217.147']
        if self.cloud_t3: resolver.nameservers = ['37.59.247.72']
        if self.cloud_t4: resolver.nameservers = ['37.59.247.71']
        resolver.timeout = self.KSRNServerTimeOut
        resolver.lifetime = self.KSRNServerTimeOut
        if self.TheShieldLogDNSQ==1:
            xnames=",".join(self.catz.nameservers)
            self.ksrn_log("%s Q [The Shield] (%s)" % (sitename,xnames))
        try:
            if self.debug: self.ksrn_log( "[DNS]: %s --> %s %s" % (sitename,searchQuery, "[Query]"))
            self.incr_statsline()
            result = resolver.query(searchQuery, 'A')

        except dns.resolver.NoAnswer:
            if self.debug: self.ksrn_log( "[ERROR]: %s %s" % (sitename, "Reputation return no result"))
            return None

        except dns.name.LabelTooLong:
            if self.debug: self.ksrn_log( "[ERROR]: %s %s" % (sitename, "LabelTooLong"))
            return None

        except dns.resolver.NoNameservers:
            if self.debug: self.ksrn_log( "[ERROR]: %s %s" % (sitename, "NoNameservers"))
            return None

        except dns.exception.Timeout:
            self.DNSError = True
            self.error="The Shields reputation timed out (%s seconds max)" % self.KSRNServerTimeOut
            self.ksrn_log("%s: [ERROR]: The Shields reputation timed out (%s seconds max)" % (sitename,resolver.lifetime))
            if self.debug: self.ksrn_log( "[ERROR]: %s The Shields reputation timed out (%s seconds max)" % (resolver.lifetime,sitename))
            return None

        except:
            self.DNSError = True
            self.error = tb.format_exc()
            self.ksrn_log("%s: [ERROR]: %s" % (sitename, self.error))
            return None

        if self.debug: self.ksrn_log( "%s: [DEBUG] %s" % (sitename, self.time_elaps(start_time)))
        if self.TheShieldLogsQueries == 1: self.ksrn_log(
            "DNS %s category=%s MISS shield.query_cloud" % (sitename, result[0]))
        return str(result[0])

    def incr_statsline(self):
        stats_key = "SRNSTATSLINE:%s" % self.log_filename()
        self.memdb.memcache_incr(stats_key)



    def log_filename_round(self,dt=None, dateDelta=timedelta(minutes=1)):
        roundTo = dateDelta.total_seconds()
        if dt is None: dt = datetime.now()
        seconds = (dt - dt.min).seconds
        rounding = (seconds + roundTo / 2) // roundTo * roundTo
        return dt + timedelta(0, rounding - seconds, -dt.microsecond)

    def log_filename(self):
        return self.log_filename_round(datetime.now(), timedelta(minutes=10)).strftime('%Y%m%d%H%M')

    def log_filename_time(self):
        return self.log_filename_round(datetime.now(), timedelta(minutes=10)).strftime('%Y-%m-%d %H:%M:%S')

    def undertand_ip(self,result_ip):
        func="undertand_ip"
        sitename=self.sitename
        if self.debug: self.ksrn_log("%s [%s]: Check Porn ?: %s " % (sitename,result_ip,self.ksrn_porn))

        # Cguard  ---------------------------------------------------------------------------------------------------
        matches = re.search("^127\.96\.0\.([0-9]+)", result_ip)
        if matches:
            if self.debug: self.ksrn_log("%s: [DEBUG] [%s] CGuard detected" % (sitename, result_ip))
            sporn=[5113,5001,5058,5048]
            shaines = [5033, 5104, 5101, 5114]
            result_cat=int(matches.group(1))
            self.categoy_id = result_cat

            if self.DisableAdvert == 1:
                if result_cat == 5026 or result_cat == 5066:
                    if self.debug: self.ksrn_log("%s: [DEBUG] [%s] Exclude (Privacy Shield disabled)" % (sitename, result_cat))
                    self.ACTION = "PASS"
                    return True

            if self.ksrn_porn == 0:
                if result_cat in sporn:
                    self.ACTION = "PASS"
                    if self.debug: self.ksrn_log("%s: [DEBUG] [%s] Exclude (Porn Shield disabled)" % (sitename, result_cat))
                    return True

            if self.hatred_and_discrimination==0:
                if result_cat in shaines:
                    self.ACTION = "PASS"
                    if self.debug: self.ksrn_log("%s: [DEBUG] [%s] Exclude (Hate and discrimination)" % (sitename, result_cat))
                    return True

            self.ACTION = "CGUARD"
            return True

        matches = re.search("127\.12\.([0-9]+)\.1", result_ip)
        if matches:
            self.ACTION = "PASS"
            BADCATZ=[6,7,10,72,92,105,111,135,132,109,5,143]
            hatred=[130,148,149,150,140]
            result_cat=int(matches.group(1))
            self.categoy_id = int(result_cat)

            if self.categoy_id == 0:
                self.categoy_id=self.catz.get_category(sitename)
                if self.debug: self.ksrn_log("%s Category==0 ??? with [%s] retreive it ! -> %s" % (sitename, result_ip,self.categoy_id))


            if self.krsn_debug == 1: self.ksrn_log("%s [%s]: ARTICA L.575" % (sitename,result_cat))
            if self.categoy_id > 0:
                if self.ksrn_porn == 0:
                    if result_cat == 109 or result_cat == 132:
                        if self.krsn_debug == 1: self.ksrn_log("%s [%s]: ARTICA PORN EXCLUDE L.630" % (sitename, result_cat))
                        self.ACTION = "PASS"
                        if self.debug: self.ksrn_log("%s: [DEBUG] [%s] Exclude (Porn Shield disabled)" % (sitename, result_cat))
                        return True

                if self.DisableAdvert==1:
                    if result_cat == 5 or  result_cat == 143:
                        if self.debug: self.ksrn_log( "%s: [DEBUG] [%s] Exclude (Privacy Shield disabled)" % (sitename, result_cat))
                        self.ACTION="PASS"
                        return True

                if self.hatred_and_discrimination == 0:
                    if result_cat in hatred:
                        self.ACTION = "PASS"
                        if self.debug: self.ksrn_log("%s: [DEBUG] [%s] Exclude (Hate and discrimination)" % (sitename, result_cat))
                        return True

                if result_cat in BADCATZ:
                    self.ACTION = "ARTICA"
                    if self.krsn_debug == 1: self.ksrn_log("%s [%s]: ARTICA DETECTED L.598" % (sitename, result_cat))
                    return True


            return True


# MalWareURL ---------------------------------------------------------------------------------------------------
        if result_ip == "127.10.1.0":
            self.categoy_id = 92
            self.ACTION = "MALWAREURL_MALWARES"
            return True

        if result_ip == "127.10.2.0":
            self.ACTION="MALWAREURL_PHISHING"
            self.categoy_id = 105

            return True
# ----------------------------------------------------------------------------------------------------------------
        matches = re.search("127\.10\.1\.([0-9]+)", result_ip)
        if matches:
            self.ACTION = "CLOUDFLARE"
            self.categoy_id = matches.group(1)
            return True

        matches = re.search("127\.10\.2\.([0-9]+)", result_ip)
        if matches:
            self.ACTION = "CLOUDFLARE"
            self.categoy_id = matches.group(1)
            return True
# ----------------------------------------------------------------------------------------------------------------
        if result_ip == "127.3.1.0":
            self.ACTION = "CLOUDFLARE"
            self.categoy_id = matches.group(1)
            return True

        if result_ip == "127.3.2.0":
            self.ACTION = "CLOUDFLARE"
            self.categoy_id = 112
            return True

        matches = re.search("127\.3\.1\.([0-9]+)", result_ip)
        if matches:
            self.ACTION = "CLOUDFLARE"
            self.categoy_id = matches.group(1)
            return True

        matches = re.search("127\.3\.2\.([0-9]+)", result_ip)
        if matches:
            self.ACTION = "REAFFECTED"
            self.categoy_id = matches.group(1)
            return True
# ----------------------------------------------------------------------------------------------------------------
        if result_ip == "127.4.0.0":
            self.ACTION = "GENERIC"
            self.categoy_id = 92
            return True
        matches = re.search("127\.4\.0\.([0-9]+)", result_ip)
        if matches:
            self.ACTION = "GENERIC"
            self.categoy_id = matches.group(1)
            return True
# Google ----------------------------------------------------------------------------------------------------------
        if result_ip == "127.2.0.0":
            self.ACTION = "GOOGLE"
            self.categoy_id = 92
            return True

        matches = re.search("127\.2\.0\.([0-9]+)", result_ip)
        if matches:
            self.ACTION = "GOOGLE"
            self.categoy_id = matches.group(1)
            return True
# Kaspersky ----------------------------------------------------------------------------------------------------
        matches = re.search("^127\.254\.0\.([0-9]+)$", result_ip)
        if matches:
            self.ACTION = "KASPERSKY"
            self.categoy_id = matches.group(1)
            return True
 # AdGuard  ----------------------------------------------------------------------------------------------------
        if result_ip == "127.5.0.0":
            if self.DisableAdvert==1:
                self.ACTION = "PASS"
                self.categoy_id =5
                return True
            self.ACTION = "ADGUARD"
            self.categoy_id =5
            return True

        matches = re.search("^127\.5\.0\.([0-9]+)$", result_ip)
        if matches:
            category = int(matches.group(1))
            if self.DisableAdvert==1:
                if category == 5 or category == 143:
                    self.ACTION = "PASS"
                    self.categoy_id = 5
                    return True

            self.ACTION = "ADGUARD"
            self.categoy_id = category
            return True

# Quad9  ----------------------------------------------------------------------------------------------------
        if result_ip == "127.253.0.0":
            self.ACTION = "QUAD9"
            self.categoy_id = 92
            return True

        matches = re.search("^127\.253\.0\.([0-9]+)$", result_ip)
        if matches:
            category = int(matches.group(1))
            self.ACTION = "ADGUARD"
            self.categoy_id = category
            return True



# Nothing  ----------------------------------------------------------------------------------------------------
        matches = re.search("^0\.0\.0\.([0-9]+)$", result_ip)
        if matches:
            category = int(matches.group(1))
            self.ACTION = "PASS"
            self.categoy_id = category
            return True

        self.ACTION = "PASS"
        return False

    def local_catz(self,sitename):
        start_time=time()
        if len(str(sitename))==0: return 0
        func="local_catz"
        self.local_cache_results = self.catz.get_category(sitename)
        if self.TheShieldLogsQueries == 1:
            duration = self.memdb.TimeExec(start_time)
            self.ksrn_log("%s: catz.get_category() category=%s (duration %s seconds) L.738" % (sitename,self.local_cache_results,duration))

        self.categoy_id = self.local_cache_results

        if self.categoy_id == 0:
            if self.TheShieldLogsQueries == 1:
                duration = self.memdb.TimeExec(start_time)
                self.ksrn_log("%s: UNKNOWN theshield.local_catz() (duration %s seconds) L.739" % (sitename,duration))
            return 0

        if self.TheShieldLogsQueries == 1:
            duration = self.memdb.TimeExec(start_time)
            self.ksrn_log("%s: local_catz [DETECTED] AS %s theshield.local_catz() (duration %s seconds) L.744" % (sitename,self.categoy_id,duration))
        return self.categoy_id

        if not self.catz.dangerous_category(self.local_cache_results):
            if self.TheShieldLogsQueries == 1: self.ksrn_log("%s get_category_fixed %s NOT A DANGEROUS CATEGORY" % (sitename,self.local_cache_results))
            if self.debug: self.ksrn_log( "%s: [DEBUG] %s not a dangerous category" % (sitename, self.local_cache_results))
            return True

        if self.ksrn_porn == 0:
            if self.local_cache_results == 109:
                if self.debug: self.ksrn_log("%s: [DEBUG] [%s] Exclude (Porn Shield disabled)" % (sitename, self.local_cache_results))
                return True
            if self.local_cache_results == 97:
                if self.debug: self.ksrn_log("%s: [DEBUG] [%s] Exclude (Porn Shield disabled)" % (sitename, self.local_cache_results))
                return True

        if self.DisableAdvert == 1:
            if self.local_cache_results == 5 or self.local_cache_results == 143:
                if self.debug: self.ksrn_log( "%s: [DEBUG] [%s] Exclude (Privacy Shield disabled)" % (sitename, self.local_cache_results))
                return True

        if self.TheShieldLogsQueries == 1:
            duration = self.memdb.TimeExec(start_time)
            self.ksrn_log("[%s] get_category_fixed [CLEAN] AS %s theshield.local_catz() (%s seconds)" % (sitename, duration))
        return False

    def time_elaps(self,start_time):
        elapsed_time_secs = time() - start_time
        text="took: %s secs" % timedelta(seconds=round(elapsed_time_secs))
        return text

    def encrypt_upper(self,text):
        shift = 3  # defining the shift count
        encryption = ""
        text = text.upper()
        text = text.replace('.', 'chr2')
        for c in text:
            if c.isupper():
                c_unicode = ord(c)
                c_index = ord(c) - ord("A")
                new_index = (c_index + shift) % 26
                new_unicode = new_index + ord("A")
                new_character = chr(new_unicode)
                encryption = encryption + new_character

            else:
                encryption += c

        return encryption.lower()

    def writestats(self,category_int,sitename,provider,duration):
        self.INTERNAL_LOGS=[]
        func="writestats"
        sname = self.log_filename()
        filename = "/var/log/squid/%s.ksrn" % sname
        time10mn = self.log_filename_time()


        try:
            filelogs = open(filename, 'a')
            filelogs.write("%s|%s|%s|%s|%s|%s|%s|%s\n" % (time10mn, self.username, self.src_ip, self.mac, category_int, sitename,provider,duration))
            filelogs.close()
            return True
        except:
            if self.debug: self.ksrn_log( "[ERROR]: While writing %s" % filename)
            if self.debug: self.ksrn_log( "[ERROR]: " + tb.format_exc())
            return False