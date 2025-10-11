#!/usr/bin/env python
import sys
import os
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
import traceback as tb
import syslog
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
from time import time
import cPickle as pickle
import os
from phpserialize import serialize, unserialize

class TheShieldsService():

    def __init__(self,CategoriesClass=None):
        global TheShieldClass
        global CategorizeClass
        global ITChart
        self.stdin_path = '/dev/null'
        self.stdout_path = '/dev/null'
        self.stderr_path = '/dev/null'
        self.pidfile_path = '/var/run/theshields.pid'
        self.pidfile_timeout = 5
        self.buffer = 2048
        self.HOST="127.0.0.1"
        self.PORT = 2004
        self.debug = False
        if CategoriesClass is None: self.zcat=categorize()
        if CategoriesClass is not None: self.zcat=CategoriesClass
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
        self.ksrn_debug=GET_INFO_INT("KRSN_DEBUG")
        self.WatchdogInterfacesCount=0
        self.TotalofQueriesHITS=0
        self.TheShieldDebug = GET_INFO_INT("TheShieldDebug")
        self.krsn_debug =  GET_INFO_INT("KRSN_DEBUG")
        self.TotalofQueries=0
        KSRNEmergency = GET_INFO_INT("KSRNEmergency")
        if KSRNEmergency == 1: self.KSRNEnable = 0
        TheShieldsIP = GET_INFO_STR("TheShieldsIP")
        TheShieldsPORT = GET_INFO_INT("TheShieldsPORT")
        if len(TheShieldsIP)>3: self.HOST=TheShieldsIP
        if TheShieldsPORT>0 : self.PORT = TheShieldsPORT
        self.cache_time = GET_INFO_INT("TheShieldServiceCacheTime")
        self.MaxItemsInMemory = GET_INFO_INT("TheShieldMaxItemsInMemory")
        self.TheShieldsLogTimes= GET_INFO_INT("TheShieldsLogTimes")
        if self.TheShieldDebug ==1: self.debug=True
        self.version = "30.0"
        self.local_cache ={}
        self.mac=""
        self.virtual_user_cache={}
        self.local_cache_path ="/home/artica/SQLITE_TEMP/theshields.tmp.db"
        self.DatabaseCounter=0
        SET_INFO("THE_SHIELD_SERVICE_VERSION",self.version)
        self.ITChartLog=syslog
        self.ITChartLog.openlog("ItCharter", syslog.LOG_PID)
        if self.TheShieldsLogTimes==1: self.zcat.TheShieldsLogTimes=1
        self.shield = theshields(self.zcat)
        if self.debug: self.StandardLog("Initialize The Shields Service class v%s" % self.version)
        if self.TheShieldLogsQueries==1: self.shield.TheShieldLogsQueries=1
        self.EnableUfdbGuard=GET_INFO_INT("EnableUfdbGuard")
        self.ksrn_liceense = GET_INFO_INT("KSRN_LICENSE")

        try:
            self.ITChart = ITCHARTENGINE()
        except:
            self.ITChartLog.syslog(syslog.LOG_INFO,tb.format_exc())

        self.QueryDomain=""
        if self.cache_time==0: self.cache_time=84600
        if self.MaxItemsInMemory == 0 :self.MaxItemsInMemory = 20000

        if self.debug: self.StandardLog("Starting The Shields thread in debug mode, please carrefull the server load!")

    def VirtualUser(self):
        key="%s.%s" % (self.mac,self.src_ip)
        if key in self.virtual_user_cache: return self.virtual_user_cache[key]
        ipStrongSwan=""
        if self.EnableStrongswanServer == 1:ipStrongSwan=self.src_ip
        sresult = self.mem.UserAliases(self.mac,self.src_ip,ipStrongSwan)
        if sresult is None:
            self.virtual_user_cache[key]=""
            return ""
        self.virtual_user_cache[key]=sresult
        return sresult


    def GetCacheItem(self,smd5):
        if smd5 in  self.local_cache:
            return self.local_cache

        smd5 = "SHIELD.serv.%s" % smd5
        sData=self.mem.ksrncache_get(smd5)
        if sData is None: return None
        if len(sData)<5: return None
        return sData


    def SaveCacheItem(self,smd5,sValue):
        smd5 = "SHIELD.serv.%s" % smd5
        self.local_cache[smd5]=sValue
        if len(self.local_cache)> self.MaxItemsInMemory: self.reset()


        try:
            newsValue=str(sValue)
            if not self.mem.ksrncache_set(smd5,newsValue,self.cache_time):
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

    def rblpass(self,sitename):
        if sitename is None: return False
        if self.mac is None: return False
        if self.src_ip is None: return False

        if len(sitename)>3:
            scache = "DOMWHITE:%s" % sitename
            sf = self.GetCacheItem(scache)
            if sf is not None: return True
            if self.zcat.admin_whitelist(sitename):
                self.SaveCacheItem(scache,True)
                return True
        if len(self.mac)>3:
            if self.zcat.admin_whitelist_mac(self.mac): return True
        if len(self.src_ip)>3:
            if self.zcat.admin_whitelist_src(self.src_ip): return True
        return False


    def response(self,buffer):
        CHOOSE=""
        USERNAME=""
        ITCHART_ACTION=""
        ITCHART_INFO=""
        result = {}
        self.src_ip="127.0.0.1"
        self.mac="00:00:00:00:00:00"
        sitename=""
        ACTION=""
        duration = 0
        self.DatabaseCounter=self.DatabaseCounter+1
        CloseDebug=False
        force_debug=False

        WHITE=False
        self.TotalofQueries=self.TotalofQueries+1
        if self.debug: self.TheShieldLogsQueries=1;
        zrblpass = False
        method="GET"
        starttime=time()
        LOG_QUERY=0
        if self.TheShieldsLogTimes==1: self.zcat.TheShieldsLogTimes=1

        try:
            MAIN = unserialize(buffer)
        except:
            result["error"] = "Unserialize Error"
            result["categoy_id"] = 0
            result["categoy_name"] = "Unknown"
            result["ACTION"] = "PASS"
            sValue = serialize(result)
            syslog.syslog(syslog.LOG_INFO, "Failed to unserialize [%s] content.. L.194" % buffer)
            return sValue


        result["VIRTUAL_USER"] = ""
        result["COUNTRY_CODE"] = ""

        if not "ipaddr" in MAIN:
            self.StandardLog("No ipaddr in array [%s] assume 127.0.0.1" % buffer)
            MAIN["self.src_ip"]="127.0.0.1"

        sitenames=[]
        if "CHOOSE" in MAIN: CHOOSE=MAIN["CHOOSE"]
        if "USERNAME" in MAIN: USERNAME = MAIN["USERNAME"]
        if "ipaddr" in MAIN: self.src_ip = MAIN["ipaddr"]
        if "mac" in MAIN: self.mac = MAIN["mac"]
        if "sitename" in MAIN: sitename = MAIN["sitename"]

        if "method" in MAIN: method = MAIN["method"]
        if "ACTION" in MAIN: ACTION=MAIN["ACTION"]
        if "LOG_QUERY" in MAIN: LOG_QUERY=MAIN["LOG_QUERY"]

        if "sitenames" in MAIN:
            result["sitenames"] = {}
            sitenames = MAIN["sitenames"]

            for index in sitenames:
                hostname=str(sitenames[index])
                categoy_id = self.zcat.get_category(hostname)
                result["sitenames"][hostname]=categoy_id
                result["STATUS"] = 1
                result["ACTION"] = "BULK"
            sValue = serialize(result)
            return sValue




        if "DEBUG" in MAIN:
            CloseDebug=True
            self.sendlogs("%s: [%s] DEBUG IN QUERY == TRUE" % (self.src_ip, sitename))
            self.debug=True
            self.krsn_debug=1

        if LOG_QUERY == 1:
            self.TheShieldLogsQueries=1
            self.shield.TheShieldLogsQueries=1

        result["ACTION_RECEIVED"] = ACTION
        if ACTION == "WHITETHIS":
            scache = "isWhite:%s" % sitename
            self.SaveCacheItem(scache, True)
            result["STATUS"] = 1
            result["ACTION"] = "% is temporaly whitelisted" % sitename
            self.sendlogs("%s: [%s] ACTION=%s OK L.216" % (self.src_ip, sitename, ACTION))
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
                self.StandardLog("Success reseting the memory L.240")
                return "Success refreshing memory databases"
            except:
                error=tb.format_exc()
                self.StandardLog("[ERROR]: reseting the memory %s" % error)
                return error


        if ACTION=="STATS":
            self.StandardLog("%s L.249" % "Running stats...")
            try:
                CORP_LICENSE=0
                gold = isGoldkey()
                if gold.is_corp_license(): CORP_LICENSE = 1
                starttime=time()
                result["STATUS"]=1
                result["THE_SHIELD_CACHE"]=len(self.local_cache)
                result["CATEGORIES_CACHE"] = len(self.zcat.local_cache)
                result["MEMCACHE_KSRN"] = GET_INFO_INT("MEMCACHE_KSRN")
                result["QUERIES"] = self.TotalofQueries
                result["HITS"] = self.TotalofQueriesHITS
                result["DEBUG"] = self.debug
                result["VERSION"] = self.version
                result["EnableUfdbGuard"]=self.EnableUfdbGuard
                result["KSRN_LICENSE"]=self.ksrn_liceense
                result["CORP_LICENSE"] = CORP_LICENSE
                result["KSRNEnable"]=self.KSRNEnable

                sValue = serialize(result)
                stime=self.mem.TimeExec(starttime)
                syslog.syslog(syslog.LOG_INFO, "Running Statistics Done took %s.. L.261" % stime)
                return sValue
            except:
                self.sendlogs(tb.format_exc())
                return tb.format_exc()

        try:
            self.shield.CountUsers()
        except:
            self.StandardLog("FATAL shield.CountUsers: %s L.271" % tb.format_exc())

        smd5 = self.mem.skey_shields_fullcache("none","127.0.0.1","00:00:00:00",sitename,"GET")
        self.QueryDomain=sitename
        if self.debug:
            stime=self.mem.TimeExec(starttime)
            self.sendlogs("[%s] from %s ACTION=%s counter=%s (%s seconds) L.277" % (sitename,self.src_ip,ACTION,self.DatabaseCounter,stime))

        if self.DatabaseCounter>100:
            if self.debug: self.sendlogs("Save Cache database L.280")
            self.DatabaseCounter=0


        sValue=self.GetCacheItem(smd5)
        if sValue is not None:
            result = unserialize(sValue)
            if self.TheShieldLogsQueries==1 : self.StandardLog("%s: [%s] HIT L.304" % (sitename, self.src_ip))
            self.TotalofQueriesHITS = self.TotalofQueriesHITS + 1
            if self.EnableITChart == 1:
                result["ITCHART"]=ITCHART_ACTION
                result["ITCHART_INFO"]=ITCHART_INFO

            sValue = serialize(result)

            result["CACHED_TIME"] = self.mem.TimeExec(starttime)
            if self.TheShieldLogsQueries==1: self.StandardLog("%s %s L.313" % (sitename,result["CACHED_TIME"]))
            result["CACHED_SERVICE"] = 1
            return sValue

        if self.debug: self.sendlogs("%s: MISS L.330" % (smd5))


        if self.EnableGeoipUpdate==1:
            if len(self.src_ip)>0:
                try:
                    if self.geoipC.operate(self.src_ip):
                        CountryCode = geoipC.iso_code
                        result["COUNTRY_CODE"]=CountryCode
                except:
                    self.sendlogs("Error geoipC.operate(%s) %s" % (self.src_ip, tb.format_exc()))

        VirtualUser=self.VirtualUser()
        if VirtualUser is not None: result["VIRTUAL_USER"]=VirtualUser

        if self.debug: self.sendlogs("%s: rblpass? %s %s" % (sitename,self.mac,self.src_ip))


        try:
            zrblpass=self.rblpass(sitename)
        except:
            self.sendlogs("self.rblpass(%s,%s,%s) error %s" % (sitename,self.mac,self.src_ip,tb.format_exc()))

        if zrblpass:
            categoy_id = self.zcat.get_category(sitename)
            result["error"] = ""
            result["categoy_id"] = categoy_id
            result["categoy_name"] = self.zcat.category_int_to_string(categoy_id)
            result["ACTION"] = "WHITE"
            sValue = serialize(result)
            result["CACHED"]=1
            sValue2 = serialize(result)
            self.SaveCacheItem(smd5,sValue2)
            if self.ksrn_debug==1: self.sendlogs("%s: ! zrblpass ! L.365" % (sitename))
            return sValue



        if ACTION=="THESHIELDS":
            if self.ksrn_debug==1:self.sendlogs("%s: THESHIELDS %s %s L.371" % (sitename,self.mac, self.src_ip))
            if self.KSRNEnable==1:
                ACTIONSPASS = ["WHITELIST", "PASS", "WHITE", "ERROR"]
                self.shield.debug=False
                if self.debug: self.shield.debug=True
                if self.krsn_debug==1: self.shield.debug=True
                self.shield.src_ip=self.src_ip
                start_time = datetime.now()
                if self.TheShieldLogsQueries == 1:
                        self.StandardLog("%s: [%s] SERVICE MISS" % (sitename, self.src_ip))
                        self.shield.TheShieldLogsQueries=1
                if self.ksrn_debug==1: self.sendlogs("%s: operate() L.379" % (sitename))
                self.shield.operate(sitename)
                result["SHIELD_TIMES"] = self.shield.duration_text
                try:
                    end_time = datetime.now()
                    time_diff = (end_time - start_time)
                    duration = time_diff.total_seconds() * 1000
                    result["SHIELD_DURATION"] = duration
                    if self.TheShieldLogsQueries == 1: self.StandardLog("%s SHIELD_DURATION:%s L.378" % (sitename, result["CACHED_TIME"]))
                    if self.debug: self.sendlogs("%s: duration=%s L.379" % (sitename, duration))
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
                if self.TheShieldLogsQueries == 1: self.StandardLog("%s: [%s] SERVICE %s (%s) hit:%s" % (sitename, self.src_ip,self.shield.ACTION,categoryname,hit))

                if not self.shield.ACTION in ACTIONSPASS:
                    try:
                        self.StandardLog("[THREAT_DETECTED]: cached=%s site=%s addr=%s username=%s mac=%s category=%s/%s scanner=%s duration=%s" % (hit,sitename,self.src_ip,self.username,self.mac,self.shield.categoy_id,categoryname,self.shield.ACTION,duration))
                        if not self.shield.writestats(USERNAME,self.src_ip,self.mac,self.shield.categoy_id,sitename,self.shield.ACTION,duration): self.ilog_populate(self.shield.INTERNAL_LOGS)
                    except:
                        self.sendlogs("ERROR: self.shield.writestats %s" % tb.format_exc())

                sValue=serialize(result)
                if self.shield.DNSError: return sValue
                result["CACHED"]=1
                result["TOTAL_DURATION"]=self.mem.TimeExec(starttime)
                if self.TheShieldsLogTimes == 1: self.StandardLog("%s TOTAL_DURATION:%s" % (sitename, result["TOTAL_DURATION"]))
                result["SHIELD_TIMES"] = self.shield.duration_text
                sValue2 = serialize(result)
                if self.debug: self.sendlogs("%s: SaveCacheItem=[%s,%s]" % (sitename, smd5,sValue))
                self.SaveCacheItem(smd5, sValue2)
                return sValue
            else:
                if self.krsn_debug==1: self.sendlogs("%s: THESHIELDS %s KSRNEnable==0 L.361" % (sitename))


            categoy_id = self.zcat.get_category(sitename)
            if self.debug: self.ilog_populate(self.zcat.INTERNAL_LOGS)
            result["error"] = ""
            result["categoy_id"] = categoy_id
            result["categoy_name"] = self.zcat.category_int_to_string(categoy_id)
            result["ACTION"] = "PASS"
            result["TOTAL_DURATION"] = self.mem.TimeExec(starttime)
            sValue = serialize(result)
            result["CACHED"] = 1
            result["SHIELD_TIMES"] = self.shield.duration_text
            sValue2 = serialize(result)
            self.SaveCacheItem(smd5, sValue2)

            return sValue




        if ACTION == "ARTICA":
            if self.debug: self.zcat.debug=True
            if self.debug: self.StandardLog("%s --> Artica category" % str(sitename))
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
        return sValue

    def ilog_populate(self,the_dic,force=0):
        if force ==0:
            if not self.debug: return False
        for line in the_dic:
            self.StandardLog("[%s] ilog_populate %s" % (self.QueryDomain,str(line)))



    def sendlogs(self,text):
        if not self.debug: return True
        try:
            sysDaemon = syslog
            sysDaemon.openlog("ksrn", syslog.LOG_PID)
            sysDaemon.syslog(syslog.LOG_INFO, "[SERVICE]: [DEBUG] %s" % text)
        except:
            print("sendlogs: %s" % tb.format_exc())

    def StandardLog(self,text):
        sysDaemon=syslog
        sysDaemon.openlog("ksrn", syslog.LOG_PID)
        sysDaemon.syslog(syslog.LOG_INFO, "[SERVICE]: %s" % text)




