#!/usr/bin/env python
import sys
import os
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
import traceback as tb
import syslog
import logging
import re
from unix import *
from goldlic import *
from classartmem import *
from categorizeclass import *
from classgeoip2free import *
from theshieldsclass import *
from itchartclass import *
from dnsblclass import *
from datetime import datetime
import cPickle as pickle
import socket
import thread
import os
from daemon import runner
from socket import *
import thread
from phpserialize import serialize, unserialize

class App():

    def __init__(self):
        global TheShieldClass
        global CategorizeClass
        global ITChart
        self.stdin_path = '/dev/null'
        self.stdout_path = '/dev/null'
        self.stderr_path = '/dev/null'
        self.pidfile_path = '/var/run/theshields.pid'
        self.logger = None
        self.pidfile_timeout = 5

        self.buffer = 2048
        self.HOST="127.0.0.1"
        self.PORT = 2004
        self.debug = False
        self.ITChart=ITChart
        self.shield=TheShieldClass
        self.zcat=CategorizeClass
        self.geoipC = geoip2free(self.debug)
        self.mem = art_memcache()
        self.hostname=GET_INFO_STR("myhostname")
        self.InfluxUseRemote=GET_INFO_INT("InfluxUseRemote")
        self.InfluxSyslogRemote=GET_INFO_INT("InfluxSyslogRemote")
        self.KSRNEnable = GET_INFO_INT("KSRNEnable")
        self.EnableStrongswanServer = GET_INFO_INT("EnableStrongswanServer")
        self.EnableGeoipUpdate = GET_INFO_INT("EnableGeoipUpdate")
        self.TheShieldLogsQueries = GET_INFO_INT("TheShieldLogsQueries")
        self.EnableITChart = GET_INFO_INT("EnableITChart")
        self.ITChartVerbose= GET_INFO_INT("ITChartVerbose")
        self.WatchdogInterfacesCount=0
        self.TotalofQueriesHITS=0
        self.TheShieldDebug = GET_INFO_INT("TheShieldDebug")
        self.krsn_debug = self.TheShieldDebug
        self.TotalofQueries=0
        KSRNEmergency = GET_INFO_INT("KSRNEmergency")
        if KSRNEmergency == 1: self.KSRNEnable = 1
        TheShieldsIP = GET_INFO_STR("TheShieldsIP")
        TheShieldsPORT = GET_INFO_INT("TheShieldsPORT")
        if len(TheShieldsIP)>3: self.HOST=TheShieldsIP
        if TheShieldsPORT>0 : self.PORT = TheShieldsPORT
        self.cache_time = GET_INFO_INT("TheShieldServiceCacheTime")
        self.MaxItemsInMemory = GET_INFO_INT("TheShieldMaxItemsInMemory")
        if self.TheShieldDebug ==1: self.debug=True
        self.version = "28.0"
        self.local_cache ={}
        self.virtual_user_cache={}
        self.local_cache_path ="/home/artica/SQLITE_TEMP/theshields.tmp.db"
        self.DatabaseCounter=0
        SET_INFO("THE_SHIELD_SERVICE_VERSION",self.version)
        self.ITChartLog=syslog
        self.ITChartLog.openlog("ItCharter", syslog.LOG_PID)

        self.QueryDomain=""
        if self.cache_time==0: self.cache_time=84600
        if self.MaxItemsInMemory == 0 :self.MaxItemsInMemory = 20000

        if self.debug:
            syslog.openlog("TheShields", syslog.LOG_PID)
            syslog.syslog(syslog.LOG_INFO,"[SERVICE]: Starting The Shields thread in debug mode, please carrefull the server load!")

    def run(self):
        sloglisten=""
        try:
            ADDR = (self.HOST, self.PORT)
            sloglisten="%s:%s" % (self.HOST, self.PORT)
            self.StandardLog('Open TCP sockets %s' % sloglisten)
            serversock = socket(AF_INET, SOCK_STREAM)
            serversock.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1)
            serversock.bind(ADDR)
            serversock.listen(5)
        except:
            self.StandardLog("ServerSock error %s" % tb.format_exc())

        while 1:
            client_addr=""
            if self.debug: self.StandardLog('Waiting for connection... listening on port %s' % sloglisten)
            clientsock, addr = serversock.accept()
            try:
                client_addr=str(addr[0])
            except:
                client_addr="sock"
            if self.debug: self.StandardLog("Connected from: %s" % client_addr)
            thread.start_new_thread(self.handler, (clientsock, addr))


    def VirtualUser(self,mac,ipaddr):
        key="%s.%s" % (mac,ipaddr)
        if key in self.virtual_user_cache: return self.virtual_user_cache[key]
        ipStrongSwan=""
        if self.EnableStrongswanServer == 1:ipStrongSwan=ipaddr
        sresult = self.mem.UserAliases(mac,ipaddr,ipStrongSwan)
        if sresult is None:
            self.virtual_user_cache[key]=""
            return ""
        self.virtual_user_cache[key]=sresult
        return sresult


    def GetCacheItem(self,smd5):
        if smd5 in  self.local_cache:
            return self.local_cache

        smd5 = "SHIELD.serv.%s" % smd5
        sData=self.mem.memcache_get(smd5)
        if sData is None: return None
        if len(sData)<5: return None
        return sData


    def SaveCacheItem(self,smd5,sValue):
        smd5 = "SHIELD.serv.%s" % smd5
        self.local_cache[smd5]=sValue
        if len(self.local_cache)> self.MaxItemsInMemory: self.reset()


        try:
            newsValue=str(sValue)
            if not self.mem.memcache_set(smd5,newsValue,self.cache_time):
                self.StandardLog("Error while writing in memcache %s %s" % (smd5,self.mem.error))
                return False
        except:
            self.StandardLog("Error while writing in memcache %s=[%s]" % (smd5,sValue))
            return False

        return True



    def reset(self):
        self.sendlogs("Reseting local cache of %s items" % len(self.local_cache))
        self.local_cache = {}
        self.StandardLog("[INFO]: Reloading memory categories caches")
        self.zcat.reload_dbs()
        self.zcat.local_cache = {}
        self.StandardLog("[INFO]: Reloading The Shields memory cache")
        self.shield.local_cache = {}
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.ksrn.php --clean-cache")
        self.debug=False

    def rblpass(self,sitename,mac,ipaddr):
        if len(sitename)>3:
            scache = "DOMWHITE:%s" % sitename
            sf = self.GetCacheItem(scache)
            if sf is not None: return True
            if self.zcat.admin_whitelist(sitename):
                self.SaveCacheItem(scache,True)
                return True
        if len(mac)>3:
            if self.zcat.admin_whitelist_mac(mac): return True
        if len(ipaddr)>3:
            if self.zcat.admin_whitelist_src(ipaddr): return True
        return False


    def response(self,buffer):
        CHOOSE=""
        USERNAME=""
        ITCHART_ACTION=""
        ITCHART_INFO=""
        result = {}
        ipaddr="127.0.0.1"
        mac="00:00:00:00:00:00"
        sitename=""
        ACTION=""
        duration = 0
        self.DatabaseCounter=self.DatabaseCounter+1
        CloseDebug=False
        force_debug=False
        MAIN=unserialize(buffer)
        WHITE=False
        self.TotalofQueries=self.TotalofQueries+1
        if self.debug: self.TheShieldLogsQueries=1;
        if self.logger is None: self.init_logger()
        self.shield.logger = self.logger
        self.zcat.logger = self.logger
        method="GET"

        result["VIRTUAL_USER"] = ""
        result["COUNTRY_CODE"] = ""
        if "CHOOSE" in MAIN: CHOOSE=MAIN["CHOOSE"]
        if "USERNAME" in MAIN: USERNAME = MAIN["USERNAME"]
        if "ipaddr" in MAIN: ipaddr = MAIN["ipaddr"]
        if "mac" in MAIN: mac = MAIN["mac"]
        if "sitename" in MAIN: sitename = MAIN["sitename"]
        if "method" in MAIN: method = MAIN["method"]
        if "ACTION" in MAIN: ACTION=MAIN["ACTION"]
        if "DEBUG" in MAIN:
            CloseDebug=True
            self.sendlogs("%s: [%s] DEBUG IN QUERY == TRUE" % (ipaddr, sitename))
            self.debug=True
            self.krsn_debug=1

        if ACTION == "WHITETHIS":
            scache = "isWhite:%s" % sitename
            self.SaveCacheItem(scache, True)
            result["STATUS"] = 1
            result["ACTION"] = "% is temporaly whitelisted" % sitename
            self.sendlogs("%s: [%s] ACTION=%s OK" % (ipaddr, sitename, ACTION))
            sValue = serialize(result)
            return sValue

        if ACTION=="DEBUG":
            if self.debug:
                self.debug=False
            else:
                self.debug=True
            return "Success Debug mode to %s" % self.debug

        if ACTION=="LOG-QUERIES":
            if self.zcat.logqueries==1:
                self.zcat.logqueries=0
            else:
                self.zcat.logqueries=1
            return "Success logs queries to %s" % self.zcat.logqueries


        if ACTION=="RESET":
            try:
                self.TotalofQueries=0
                self.TotalofQueriesHITS=0
                self.reset()
                self.StandardLog("[INFO]: Success reseting the memory")
                return "Success refreshing memory databases"
            except:
                error=tb.format_exc()
                self.StandardLog("[ERROR]: reseting the memory %s" % error)
                return error


        if ACTION=="STATS":
            try:
                result["STATUS"]=1
                result["THE_SHIELD_CACHE"]=len(self.local_cache)
                result["CATEGORIES_CACHE"] = len(self.zcat.local_cache)
                result["MEMCACHE_KSRN"] = GET_INFO_INT("MEMCACHE_KSRN")
                result["QUERIES"] = self.TotalofQueries
                result["HITS"] = self.TotalofQueriesHITS
                result["DEBUG"] = self.debug
                result["VERSION"] = self.version
                sValue = serialize(result)
                return sValue
            except:
                self.sendlogs(tb.format_exc())
                return tb.format_exc()

        try:
            self.shield.CountUsers(ipaddr,mac,USERNAME)
        except:
            self.StandardLog("FATAL shield.CountUsers: %s" % tb.format_exc())

        smd5 = hashlib.md5(sitename).hexdigest()
        self.QueryDomain=sitename
        if self.debug: self.sendlogs("%s: [%s] ACTION=%s counter=%s " % (ipaddr, sitename,ACTION,self.DatabaseCounter))

        if self.DatabaseCounter>100:
            if self.debug: self.sendlogs("Save Cache database")
            self.DatabaseCounter=0



        if self.EnableITChart==1:
            try:
                if not self.ITChart.ChartThis(ipaddr,mac,USERNAME,method,sitename):
                    result["ITCHART"] = "PASS"
                    ITCHART_ACTION="PASS"
                    ITCHART_INFO=""
                else:
                    result["ITCHART"] = "BLOCK"
                    result["ITCHART_INFO"] = self.ITChart.message
                    ITCHART_ACTION = "BLOCK"
                    ITCHART_INFO=self.ITChart.message
            except:
                result["ITCHART"] = "ERROR"
                ITCHART_ACTION="ERROR"
                self.sendlogs(tb.format_exc())

        sValue=self.GetCacheItem(smd5)
        if sValue is not None:
            if self.TheShieldLogsQueries==1 : self.StandardLog("%s: [%s] HIT" % (sitename, ipaddr))
            self.TotalofQueriesHITS = self.TotalofQueriesHITS + 1
            if self.EnableITChart == 1:
                result=unserialize(sValue)
                result["ITCHART"]=ITCHART_ACTION
                result["ITCHART_INFO"]=ITCHART_INFO
                sValue=serialize(result)

            return sValue
        else:
            if self.debug: self.sendlogs("%s: MISS" % (smd5))


        if self.EnableGeoipUpdate==1:
            if len(ipaddr)>0:
                try:
                    if self.geoipC.operate(ipaddr):
                        CountryCode = geoipC.iso_code
                        result["COUNTRY_CODE"]=CountryCode
                except:
                    self.sendlogs("Error geoipC.operate(%s) %s" % (ipaddr, tb.format_exc()))

        VirtualUser=self.VirtualUser(mac,ipaddr)
        if VirtualUser is not None: result["VIRTUAL_USER"]=VirtualUser

        if self.debug: self.sendlogs("%s: rblpass? %s %s" % (sitename,mac,ipaddr))
        if self.rblpass(sitename,mac,ipaddr):
            categoy_id = self.zcat.get_category(sitename)
            result["error"] = ""
            result["categoy_id"] = categoy_id
            result["categoy_name"] = self.zcat.category_int_to_string(categoy_id)
            result["ACTION"] = "WHITE"
            sValue = serialize(result)
            result["CACHED"]=1
            sValue2 = serialize(result)
            self.SaveCacheItem(smd5,sValue2)
            if CloseDebug:
                self.debug = False
                self.krsn_debug = 0
                self.shield.debug = False

            return sValue



        if ACTION=="THESHIELDS":
            if self.debug: self.sendlogs("%s:[DEBUG] THESHIELDS %s %s" % (sitename, mac, ipaddr))
            if self.KSRNEnable==1:
                ACTIONSPASS = ["WHITELIST", "PASS", "WHITE", "ERROR"]
                self.shield.debug=False
                if self.debug: self.shield.debug=True
                if self.krsn_debug==1: self.shield.debug=True
                self.shield.src_ip=ipaddr
                start_time = datetime.now()
                if self.TheShieldLogsQueries == 1:
                        self.StandardLog("%s: [%s] SERVICE MISS" % (sitename, ipaddr))
                        self.shield.TheShieldLogsQueries=1
                        self.shield.LoggerQuery=self.logger

                self.shield.operate(sitename)

                try:
                    end_time = datetime.now()
                    time_diff = (end_time - start_time)
                    duration = time_diff.total_seconds() * 1000
                    if self.debug: self.sendlogs("%s:[DEBUG] THESHIELDS duration=%s" % (sitename, duration))
                except:
                    self.sendlogs("Error duration %s" % tb.format_exc())


                if self.debug: self.ilog_populate(self.shield.INTERNAL_LOGS,1)
                hit=self.shield.HIT
                if len(self.shield.ACTION)==0:
                    if self.TheShieldLogsQueries == 1: self.StandardLog("%s: !!! ACTION IS NULL !!" % sitename)
                    self.shield.ACTION="PASS"

                result["error"]=self.shield.error
                result["categoy_id"] = self.shield.categoy_id
                categoryname=self.zcat.category_int_to_string(self.shield.categoy_id)
                result["categoy_name"]=categoryname
                result["ACTION"] = self.shield.ACTION
                if self.TheShieldLogsQueries == 1: self.StandardLog("%s: [%s] SERVICE %s (%s) hit:%s" % (sitename, ipaddr,self.shield.ACTION,categoryname,hit))

                if not self.shield.ACTION in ACTIONSPASS:
                    try:
                        syslog.openlog("TheShields", syslog.LOG_PID)
                        syslog.syslog(syslog.LOG_INFO, "[THREAT_DETECTED]: cached=%s site=%s addr=%s username=%s mac=%s category=%s/%s scanner=%s duration=%s" % (hit,sitename,ipaddr,USERNAME,mac,self.shield.categoy_id,categoryname,self.shield.ACTION,duration))
                        if not self.shield.writestats(USERNAME,ipaddr,mac,self.shield.categoy_id,sitename,self.shield.ACTION,duration): self.ilog_populate(self.shield.INTERNAL_LOGS)
                    except:
                        self.sendlogs("ERROR: self.shield.writestats %s" % tb.format_exc())

                sValue=serialize(result)
                if self.shield.DNSError: return sValue
                result["CACHED"]=1
                sValue2 = serialize(result)
                if self.debug: self.sendlogs("%s: THESHIELDS SaveCacheItem=[%s,%s]" % (sitename, smd5,sValue))
                self.SaveCacheItem(smd5, sValue2)
                if CloseDebug:
                    self.debug = False
                    self.krsn_debug = 0
                    self.shield.debug = False
                return sValue

            categoy_id = self.zcat.get_category(sitename)
            if self.debug: self.ilog_populate(self.zcat.INTERNAL_LOGS)
            result["error"] = ""
            result["categoy_id"] = categoy_id
            result["categoy_name"] = self.zcat.category_int_to_string(categoy_id)
            result["ACTION"] = "PASS"
            sValue = serialize(result)
            result["CACHED"] = 1
            sValue2 = serialize(result)
            self.SaveCacheItem(smd5, sValue2)
            if CloseDebug:
                self.debug = False
                self.krsn_debug = 0
                self.shield.debug = False
            return sValue




        if ACTION == "ARTICA":
            if self.debug: self.zcat.debug=True
            if self.debug: self.StandardLog("[DEBUG]: Artica category %s" % str(sitename))
            categoy_id = self.zcat.get_category(sitename)
            if self.debug: self.ilog_populate(self.zcat.INTERNAL_LOGS)
            result["error"] = ""
            result["categoy_id"] = categoy_id
            result["categoy_name"] = self.zcat.category_int_to_string(categoy_id)
            result["ACTION"] = "PASS"

        if ACTION == "CGUARD":
            categoy_id = self.zcat.get_category_cguard(sitename)
            if self.debug: self.ilog_populate(self.zcat.INTERNAL_LOGS)
            result["error"] = ""
            result["categoy_id"] = categoy_id
            result["categoy_name"] = self.zcat.category_int_to_string(categoy_id)
            result["ACTION"] = "PASS"

        sValue = serialize(result)
        result["CACHED"] = 1
        sValue2 = serialize(result)
        self.SaveCacheItem(smd5, sValue2)
        if CloseDebug:
            self.debug = False
            self.krsn_debug = 0
            self.shield.debug = False
        return sValue

    def ilog_populate(self,the_dic,force=0):
        if force ==0:
            if not self.debug: return False
        for line in the_dic:
            self.StandardLog("[DEBUG]:[%s] %s" % (self.QueryDomain,str(line)))

    def handler(self,clientsock,addr):
        try:
            client_addr = str(addr[0])
        except:
            client_addr = "socks"
        while 1:
            data = clientsock.recv(self.buffer)
            if not data: break
            final_data=""
            if self.debug: self.StandardLog("[DEBUG]: %s recv: %s"% (client_addr,data))
            try:
                final_data=self.response(data)
            except:
                self.StandardLog(tb.format_exc())

            clientsock.send(final_data)
            if self.debug: self.StandardLog(repr(addr) + ' sent:' + repr(self.response(data)))
            break


        clientsock.close()
        if self.debug: self.sendlogs("[DEBUG]: %s - closed connection" % client_addr)

    def sendlogs(self,text):
        if not self.debug: return True
        try:
            self.logger.info(text)
        except:
            print("sendlogs: %s" % tb.format_exc())

    def StandardLog(self,text):
        try:
            self.logger.info(text)
        except:
            print("sendlogs: %s" % tb.format_exc())

    def init_logger(self):
        try:
            self.logger = logging.getLogger("theshields-daemon")
            self.logger.setLevel(logging.INFO)
            formatter = logging.Formatter("%(asctime)s [%(process)d]: %(message)s")
            handler = logging.FileHandler("/var/log/theshields-daemon.log")
            handler.setFormatter(formatter)
            self.logger.addHandler(handler)
        except:
            print("Initialize logger failed!")



def start_daemon():
    global TheShieldClass
    global CategorizeClass
    global ITChart
    CategorizeClass = categorize()
    TheShieldClass  =theshields(CategorizeClass)
    try:
        ITChart = ITCHARTENGINE()
    except:
        print(tb.format_exc())

    app = App()
    app.shield = TheShieldClass
    app.zcat = CategorizeClass
    local_cache="/home/artica/SQLITE_TEMP/theshields.tmp.db"
    logger = logging.getLogger("theshields-daemon")
    logger.setLevel(logging.INFO)
    formatter = logging.Formatter("%(asctime)s [%(process)d]: %(message)s")
    handler = logging.FileHandler("/var/log/theshields-daemon.log")
    handler.setFormatter(formatter)
    logger.addHandler(handler)
    app.logger=logger
#app.run()
#sys.exit(0)
    if os.path.exists(local_cache): os.unlink(local_cache)
    daemon_runner = runner.DaemonRunner(app)
    daemon_runner.daemon_context.files_preserve=[handler.stream]
    try:
        logger.info("[INFO]: Starting Daemon Debug mode: %s..." % app.debug)
        daemon_runner.do_action()
        logger.info("[INFO]: Stopping Daemon...")
    except:
        logger.info("[ERROR]: %s",tb.format_exc())
        print(tb.format_exc())



if __name__ == '__main__':
