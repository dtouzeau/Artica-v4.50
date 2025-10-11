import sys
import os
global EnableITChart
global EnableUfdbGuard
global itchartclass_loaded
global ufdbclass_loaded
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
import traceback as tb
import logging
import re
import syslog
sysDaemon = syslog
sysDaemon.openlog("ksrn", syslog.LOG_PID)


from unix import *
EnableITChart          = GET_INFO_INT("EnableITChart")
EnableUfdbGuard        = GET_INFO_INT("EnableUfdbGuard")
from goldlic import *
from classartmem import *
from categorizeclass import *
from theshieldsclass import *
if EnableUfdbGuard==1:
    try:
        from ufdbclass import *
        ufdbclass_loaded=True
    except:
        pass

if EnableITChart==1:
    try:
        from itchartclass import *
        itchartclass_loaded=True
    except:
        pass

from time import time
import socket
from datetime import datetime
from phpserialize import serialize, unserialize


class TheShieldsClient():

    def __init__(self,CategoriesClass=None):
        global EnableUfdbGuard
        global ufdbclass_loaded
        self.ufdbguard = None
        self.version="90.0"
        self.whitelist_dst = set()
        self.ITChartVerbose=0
        self.sysDaemon = syslog
        self.log=None
        self.catz = None
        self.prepare_data_text=""
        self.ipaddr=""
        self.mac=""
        self.ksrn_license=False
        self.pid = os.getpid()
        self.debug = False
        self.InScreen = False
        self.sitename = ""
        self.concurrency=0
        self.method_proto=""
        self.sourceline= ""
        self.SquidUrgency = GET_INFO_INT("SquidUrgency")
        self.KSRNEmergency = GET_INFO_INT("KSRNEmergency")
        self.mem = art_memcache()
        self.web_pages = {}
        self.PROTOCOL="GET"
        self.license = False
        self.VIRTUAL_USER=""
        self.USERNAME=""
        gold = isGoldkey()
        if gold.is_corp_license(): self.license = True
        slicef = "/var/lib/squid/.srn.lic"
        self.SquidGuardClientMaxMemoryItems=GET_INFO_INT("SquidGuardClientMaxMemoryItems")
        self.ExternalAclFirstRequest = GET_INFO_INT("ExternalAclFirstRequest")
        self.EnableUfdbGuard        = EnableUfdbGuard
        self.SquidGuardClientEnableMemory=GET_INFO_INT("SquidGuardClientEnableMemory");
        self.SquidGuardClientMaxMemorySeconds=GET_INFO_INT("SquidGuardClientMaxMemorySeconds");
        if self.SquidGuardClientMaxMemoryItems == 0: self.SquidGuardClientMaxMemoryItems = 100000
        self.MaxItemsInMemory = GET_INFO_INT("TheShieldMaxItemsInMemory")
        self.cache_time = GET_INFO_INT("TheShieldServiceCacheTime")
        self.TOKEN_OUPUT="OK"
        self.resolved_hosts={}

        self.ksrn_liceense = GET_INFO_INT("KSRN_LICENSE")
        self.KSRNEnable = GET_INFO_INT("KSRNEnable")
        self.KSRNRemote = GET_INFO_INT("KSRNRemote")
        self.KSRNClientCacheTime = GET_INFO_INT("KSRNClientCacheTime")
        self.KSRNOnlyCategorization = GET_INFO_INT("KSRNOnlyCategorization")
        self.EnableStrongswanServer = GET_INFO_INT("EnableStrongswanServer")
        self.ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")
        self.KRSN_DEBUG = GET_INFO_INT("KRSN_DEBUG")
        self.WebErrorPagesCompiled=GET_INFO_STR("WebErrorPagesCompiled")
        self.WEBFILTER_RULE_NAME=""

        if self.KSRNClientCacheTime == 0: self.KSRNClientCacheTime = 300
        if self.cache_time == 0: self.cache_time = 84600
        SET_INFO("KsrnClientVersion", self.version)
        self.mem.memcache_set("ACL_FIRST_VERSION", self.version, 2600000)

        self.KSRNClientTimeOut = GET_INFO_INT("KSRNClientTimeOut")
        if self.KSRNClientTimeOut == 0: self.KSRNClientTimeOut = 5
        self.virtual_user_cache = {}

        if self.ExternalAclFirstDebug==1: self.debug=True




        if self.ksrn_liceense == 1: self.ksrn_license = True
        if os.path.exists(slicef): self.ksrn_license = True
        if not self.ksrn_license:
            self.xsyslog("THE_SHIELD]:ERROR The Shields license is invalid")
            self.KSRNEnable = 0

        if self.ksrn_license:  self.xsyslog("[THE_SHIELD]: SUCCESS The Shields license is valid")

        if self.EnableUfdbGuard==1:
            if ufdbclass_loaded: self.ufdbguard = UFDB(self.debug)

        self.catz=CategoriesClass
        self.shield = theshields(CategoriesClass)

        if self.debug:
            if self.KSRNEnable == 0 : self.xsyslog("[THE_SHIELD]: The Shields reputation engine is a disabled feature")
            if not self.license: self.xsyslog("[THE_SHIELD]: Not a Corporate license...")

        self.xsyslog("Loading ExternalAclFirstDebug = [%s]" % self.ExternalAclFirstDebug)
        self.xsyslog("Loading Client Engine v%s Debug:%s" % (self.version,self.debug))

        try:
            self.web_pages = unserialize(self.WebErrorPagesCompiled)
        except:
            self.xsyslog("[BUILD_PAGE]: Loading Client Engine Web error pages failed to unserialize L.103")

        global EnableITChart
        self.ITChartVerbose = GET_INFO_INT("ITChartVerbose")
        self.EnableITChart  = EnableITChart
        self.local_cache={}
        self.onlycatz_array={}


    def ParseLineACL(self,main_array,isDNS=False):
        # 4 MacToUid_acl - 192.168.1.55 68:54:5a:94:e7:56 - pollserver.lastpass.com - - - - - CONNECT -
        results={}
        results["ACL"]=1
        results["isDNS"] = 0
        if isDNS: results["isDNS"] =1
        results["concurrency"] = int(main_array[0])
        self.concurrency=results["concurrency"]
        results["USERNAME"] = main_array[2].strip()
        results["ipaddr"] = main_array[3].strip()
        results["mac"] = main_array[4].strip()
        results["forwardedfor"]= main_array[5].strip()
        xforward = main_array[5].strip()
        results["domain"] = main_array[6].strip()
        results["sni"] = main_array[7].strip()

        try:
            ssni=main_array[7].strip()
        except:
            self.xsyslog("FATAL [%s] %s" % (line, tb.format_exc()))
            for skey in main_array:
                self.xsyslog("GET [%s] %s" % (skey, main_array[skey]))
            return results


        user_cert=main_array[8].strip()
        notes=main_array[9].strip()
        server_ip=main_array[10].strip()
        server_fqdn=main_array[11].strip()
        results["proto"]=main_array[12].strip()
        self.PROTOCOL = results["proto"]
        if len(xforward)>3: results["ipaddr"]=xforward
        if len(ssni) > 3: results["domain"] = ssni
        try:
            results["myport"]=3128
            results["myip"]="127.0.0.1"
            results["hostname"]=""
            results["URL"] = main_array[6].strip()
        except:
            self.format_exc_log(tb.format_exc())

        return results

    def format_exc_log(self,zstr):
        sf = zstr.split('\n')
        self.xsyslog("[ERROR] ---------------------------------------------")
        for xline in sf:
            self.xsyslog("[ERROR] %s" % xline)
        self.xsyslog("[ERROR] ---------------------------------------------")

    def ParseLine(self,line):
        results={}
        main_array = line.split(" ")
        count_of_rows = len(main_array)
        c=0

        if self.debug: self.xsyslog("Parsing entity <%s>" % main_array[1])

        if main_array[1]=='MacToUid_acl':
            try:
                return self.ParseLineACL(main_array)
            except:
                self.xsyslog("FATAL Parsing MacToUid_acl [%s]" % line)
                self.format_exc_log(tb.format_exc())
                return results

        if main_array[1] == 'MacToUid_dns':
            try:
                return self.ParseLineACL(main_array,True)
            except:
                self.xsyslog("FATAL Parsing MacToUid_acl [%s]" % line)
                self.format_exc_log(tb.format_exc())
                return results


        results["ACL"] = 0
        results["concurrency"] = int(main_array[0])
        self.concurrency = results["concurrency"]
        results["URL"]=main_array[1].strip()
        zuserline=main_array[2]
        if zuserline.find("/") >0:
            chop=zuserline.split("/")
            results["ipaddr"]=chop[0]
            results["hostname"] = chop[1]
        else:
            results["ipaddr"] = zuserline
            results["hostname"] = ""

        try:
            results["USERNAME"]=main_array[3].strip()
        except:
            self.xsyslog("[WEBFILTERING]: USERNAME bound error 3 L.237")
            results["USERNAME"] ="-"

        # 2 contile.services.mozilla.com:443 192.168.1.55/192.168.1.55 - CONNECT myip=192.168.1.190
        try:
            if self.debug: self.xsyslog("[WEBFILTERING] - - - ParseLine - - - PROTO <%s> L.317" % main_array[4].strip())
            results["proto"]=main_array[4].strip()
        except:
            self.xsyslog("[WEBFILTERING]: USERNAME bound error 4 L.246")
            results["proto"] ="GET"

        self.PROTOCOL=results["proto"]
        for svalue in main_array:
            if svalue.find("=")>2:
                chop=svalue.split("=")
                results[chop[0]]=chop[1]
        return results

    def itchart_client(self):
        global itchartclass_loaded
        if not itchartclass_loaded:
            if self.debug: self.xsyslog("%s: [DEBUG][ITCHART] return NONE ITCharClass not loaded L.214" % self.sitename)

        if self.EnableITChart==0:
            if self.debug: self.xsyslog("%s: [DEBUG][ITCHART] return NONE L.203" % self.sitename)
            return ""
        ITChartRedirectURL=GET_INFO_STR("ITChartRedirectURL")
        if len(ITChartRedirectURL)==0:
            if self.debug: self.xsyslog("%s: [DEBUG][ITCHART] return NONE L.207" % self.sitename)
            return ""
        ITCharter=ITCHARTENGINE()
        ITChartRedirectURLArray=GET_INFO_STR("ITChartRedirectURLArray")

        if len(ITChartRedirectURL)==0:
            if self.debug: self.xsyslog("%s: [DEBUG][ITCHART] return NONE L.213" % self.sitename)
            ITCharter.sendlogs("[ERROR]: Redirect URL is not set, please add the redirect URL in configuration")
            return ""

        if len(ITChartRedirectURLArray)==0:
            if self.debug: self.xsyslog("%s: [DEBUG][ITCHART] return NONE L.218" % self.sitename)
            ITCharter.sendlogs("[ERROR]: Redirect URL cannot extracted, please save configuration again.")
            return ""

        try:
            parse_url=unserialize(ITChartRedirectURLArray)
        except:
            if self.debug: self.xsyslog("%s: [DEBUG][ITCHART] return NONE <%s> L.225" % (self.sitename,tb.format_exc()))
            ITCharter.sendlogs("[ERROR]: unable to deserialize Redirect URL extraction")
            return ""

        if self.debug:
            self.xsyslog("%s [ITCHART]: %s %s %s %s L.229" % (self.sitename,self.ipaddr,self.mac,self.USERNAME,self.PROTOCOL))
            ITCharter.ITChartVerbose=1

        try:
            if not ITCharter.ChartThis(self.ipaddr,self.mac,self.USERNAME,self.PROTOCOL,self.sitename):
                if self.debug: self.xsyslog("%s: [DEBUG][ITCHART] return NONE L.232" % self.sitename)
                return ""
            proto=[]
            proto.append('%s OK status=302' % self.concurrency)
            Token=ITCharter.message
            proto.append("url=%s?Token=%s" % (ITChartRedirectURL,Token))
            proto.append("itchart=ASK\n")
            return " ".join(proto)

        except:
            self.xsyslog("%s [ITCHART] Error itchart_client <%s> L.242" % (self.sitename,tb.format_exc()))
            return ""



    def ufdbguard_client(self,concurrency,line,PROXY_URL,sitename,CLIENT_IP,CLIENT_HOSTNAME,CLIENT_MAC,PROXY_IP,PROXY_PORT):
        ISBREAK = False
        if line.find('srn=WHITE') > 10: ISBREAK = True
        if line.find('rblpass=yes') > 10: ISBREAK = True
        if line.find('webfilter=pass') > 10: ISBREAK = True
        self.sitename=sitename
        self.ufdbguard.InactiveService=False
        CATEGORY=0
        CATEGORY_NAME=""

        if ISBREAK:
            if self.debug: self.xsyslog("%s [WEBFILTERING] Breakable! L.317" % sitename)
            return ""

        if self.debug:
            self.ufdbguard.debug=True
            self.xsyslog("%s [WEBFILTERING] rules with %s [%s] user=%s...L.314" % (sitename,PROXY_URL, sitename, self.USERNAME))
        try:
            log_text='sitename="%s" src="%s" host="%s" user="%s" mac="%s" proxy="%s:%s"' % (sitename,CLIENT_IP,CLIENT_HOSTNAME,self.USERNAME,CLIENT_MAC,PROXY_IP,PROXY_PORT)
            if self.ufdbguard.Process(PROXY_URL, sitename, CLIENT_IP, CLIENT_HOSTNAME, self.USERNAME, CLIENT_MAC,PROXY_IP, PROXY_PORT, self.catz):
                if self.ufdbguard.InactiveService:
                    log_text=""
                    if self.debug: self.xsyslog("%s [WEBFILTERING] INACTIVE SERVICE!" % sitename)
                    return ""
                self.WEBFILTER_RULE_NAME = self.ufdbguard.WEBFILTER_RULE_NAME

                if self.ufdbguard.category=="none":
                    CATEGORY=self.catz.get_category(sitename)
                    CATEGORY_NAME = self.ufdbguard.CATEGORY_NAME

                if CATEGORY == 0:
                    try:
                        CATEGORY = int(self.ufdbguard.category)
                        CATEGORY_NAME = self.ufdbguard.CATEGORY_NAME
                    except:
                        self.xsyslog("%s ERROR L.321 category is not an integer [%s]" % (sitename, self.ufdbguard.category))
                        CATEGORY = self.catz.get_category(sitename)
                        CATEGORY_NAME = self.ufdbguard.CATEGORY_NAME

                self.CATEGORY = CATEGORY
                self.CATEGORY_NAME=CATEGORY_NAME
                redirect=self.build_error_page()
                line_to_send = "%s %s %s ptime=\n" % (concurrency, self.TOKEN_OUPUT, redirect)
                if self.debug: self.xsyslog("%s [WEBFILTERING] OUT OF <%s>" % (sitename,line_to_send))
                if self.ufdbguard.Cached: self.write_ufdblog(self.WEBFILTER_RULE_NAME)
                self.ufdbgclient_log('[BLOCK]: rule="%s" categoryid="%s" categoryname="%s" %s' % (self.WEBFILTER_RULE_NAME,CATEGORY,CATEGORY_NAME, log_text))
                return line_to_send
            return ""
        except:
            self.xsyslog("%s ERROR L.330 %s" % (sitename, tb.format_exc()))
        return ""

    def build_error_page(self):
        TOKENS=[]
        sitename = self.sitename
        CATEGORY=0
        CATEGORY_NAME=""
        ufdbgparameters=""
        RULE_ID=0
        PROTOCOL=self.PROTOCOL
        http="http"
        if self.EnableUfdbGuard==1:
            try:
                CATEGORY = int(self.CATEGORY)
                CATEGORY_NAME = self.CATEGORY_NAME
            except:
                self.xsyslog("%s: [ERROR]: build_error_page in category=[%s] L.357 <%s>" % (self.sitename,self.CATEGORY, tb.format_exc()))

        try:
            RULE_ID = int(self.ufdbguard.RULE_ID)
        except:
            self.xsyslog("%s: [ERROR]: build_error_page in RULEID=[%s] L.362 <%s>" % (self.sitename,self.ufdbguard.RULE_ID, tb.format_exc()))


        if CATEGORY==0:
            matches=re.search("cinfo:([0-9]+)-(.+?);",self.sourceline)
            if matches:
                CATEGORY=int(matches.group(1))
                CATEGORY_NAME = matches.group(2)


        if PROTOCOL=="CONNECT":
            PROTOCOL_ID =1
            http="https"
        if PROTOCOL == "GET": PROTOCOL_ID =2
        if PROTOCOL == "POST": PROTOCOL_ID = 3

        srcurl=urlencode("%s://%s" % (http,self.sitename))
        paremeters="rule-id=%s&clientaddr=%s&clientname=%s&clientgroup=%s&targetgroup=%s&url=%s" % (
            RULE_ID,self.ipaddr,self.USERNAME,self.WEBFILTER_RULE_NAME,CATEGORY,srcurl)


        LenOfRules=len(self.web_pages)
        final_redirdect_code=302
        final_redirdect_type=0
        final_redirect_key="url"
        final_redirect_url = "http://articatech.net/block.html"
        if self.EnableUfdbGuard==1: ufdbgparameters=self.ufdbguard.final_redirect_url
        if self.debug: self.xsyslog("%s [DEBUG]: [BUILD_PAGE] final parameters <%s> L.256" % (sitename,ufdbgparameters))

        Matched=False
        Parsed=None
        # if self.SquidGuardRedirectBehavior=="rewrite-url": KEY="

        if LenOfRules > 0:
            for index in self.web_pages:
                r_category=0
                r_ruleid = 0
                r_redirtype=0
                r_url=""
                r_PARSED=None
                r_proto=0
                if self.web_pages[index].has_key("category"): r_category=int(self.web_pages[index]["category"])
                if self.web_pages[index].has_key("ruleid"): r_ruleid = int(self.web_pages[index]["ruleid"])
                if self.web_pages[index].has_key("redirtype"):  r_redirtype = int(self.web_pages[index]["redirtype"])
                if self.web_pages[index].has_key("PARSED"):  r_PARSED = self.web_pages[index]["PARSED"]
                if self.web_pages[index].has_key("url"): r_url = self.web_pages[index]["url"]
                if self.web_pages[index].has_key("protocol"): r_proto = int(self.web_pages[index]["protocol"])
                if self.debug:
                    slogs = []
                    slogs.append("Rule[%s]/%s" % (r_ruleid,RULE_ID))
                    slogs.append("protocol[%s]/%s" % (r_proto, PROTOCOL_ID))
                    slogs.append("category[%s]/%s" % (r_category, CATEGORY))
                    slogs.append("redirect[%s] type[%s]" % (r_url, r_redirtype))
                    slogs_text=", ".join(slogs)
                    self.xsyslog("%s [BUILD_PAGE]: index:%s must match %s" % (sitename,index,slogs_text))

                if r_ruleid == 0:
                    if r_category == 0:
                        if r_proto == 0:
                            if self.debug: self.xsyslog("%s [BUILD_PAGE]: !MATCHES! Detected url <%s>" % (sitename,r_url))
                            final_redirect_url = r_url
                            final_redirdect_type=r_redirtype
                            Parsed=r_PARSED
                            Matched=True
                            break
                        if r_proto == PROTOCOL_ID:
                            if self.debug: self.xsyslog("%s [BUILD_PAGE]: !MATCHES! Detected url <%s>" % (sitename,r_url))
                            final_redirect_url = r_url
                            final_redirdect_type=r_redirtype
                            Parsed=r_PARSED
                            Matched = True
                            break

                if r_ruleid == RULE_ID:
                    if r_category == 0:
                        if r_proto == 0:
                            if self.debug: self.xsyslog("%s [BUILD_PAGE]: !MATCHES! Detected url <%s> L.307" % (sitename,r_url))
                            final_redirect_url = r_url
                            final_redirdect_type=r_redirtype
                            Parsed=r_PARSED
                            Matched = True
                            break

                        if r_proto == PROTOCOL_ID:
                            if self.debug: self.xsyslog("%s [BUILD_PAGE]: !MATCHES! Detected url <%s> L.314" % (sitename, r_url))
                            final_redirect_url = r_url
                            final_redirdect_type = r_redirtype
                            Parsed = r_PARSED
                            Matched = True
                            break

                    if r_category == CATEGORY:
                        if r_proto == 0:
                            if self.debug: self.xsyslog("%s [BUILD_PAGE]: !MATCHES! Detected url <%s> L.322" % (sitename,r_url))
                            final_redirect_url = r_url
                            final_redirdect_type=r_redirtype
                            Parsed=r_PARSED
                            Matched = True
                            break

                        if r_proto == PROTOCOL_ID:
                            if self.debug: self.xsyslog("%s [BUILD_PAGE]: !MATCHES! Detected url <%s> L.329" % (sitename, r_url))
                            final_redirect_url = r_url
                            final_redirdect_type = r_redirtype
                            Parsed = r_PARSED
                            Matched = True
                            break
                else:
                    if self.debug: self.xsyslog("%s [BUILD_PAGE]: False for %s is not %s" % (sitename,r_ruleid, RULE_ID))

        if self.debug:
            if not Matched:
                self.xsyslog("%s [BUILD_PAGE]: NO_MATCHES!! rule[%s] category[%s] Proto[%s] (%s) L.345" % (sitename,RULE_ID,CATEGORY,PROTOCOL_ID,PROTOCOL))

        if final_redirdect_type == 0: final_redirdect_code=302
        if final_redirdect_type == 1: final_redirdect_code = 301
        matches=re.search("^(http|https:)",final_redirect_url)
        if matches: final_redirect_url="%s?%s" % (final_redirect_url,paremeters)
        tmpstr = "status=%s %s=%s" % (final_redirdect_code, final_redirect_key, final_redirect_url)

        if final_redirdect_type == 2: tmpstr = "rewrite-url=%s" % final_redirect_url
        if final_redirdect_type == 3: tmpstr = "status=%s %s=%s" % (302, "url", "http://artica.me")


        if len(tmpstr)>3: TOKENS.append(tmpstr)
        TOKENS.append("category=%s category-name=%s clog=cinfo:%s-%s;" % (CATEGORY, CATEGORY_NAME, CATEGORY, CATEGORY_NAME))
        if self.EnableUfdbGuard==1: TOKENS.append(self.ufdbguard.webfiltering_token)
        final=" ".join(TOKENS)
        return final


    def TimeExec(self,FirstTime):
        sockststop = time()
        socksdifference = sockststop - FirstTime
        return socksdifference


    def Process_the_shields(self):
        if self.KRSN_DEBUG==1: self.debug=True
        ACTIONSPASS = ["WHITELIST", "PASS", "WHITE", "ERROR"]
        if self.debug: self.xsyslog("%s: [DEBUG]: [THE_SHIELD] Ask To the shield L.452" % self.sitename)
        categoy_id = 0
        host = None
        ACTION = "PASS"
        ksrn_porn = self.shield.ksrn_porn
        DisableAdvert = self.shield.DisableAdvert
        hatred_and_discrimination= self.shield.hatred_and_discrimination
        categoryname=""

        categoy_id = int(self.catz.get_category_fixed(self.sitename))
        if categoy_id>0:
            if self.debug: self.xsyslog("%s: [DEBUG]: [THE_SHIELD] Fix category answering [%s]" % (self.sitename,categoy_id))
            categoryname = self.catz.category_int_to_string(categoy_id)
            BADCATZ = [6, 7, 10, 72, 92, 105, 111, 135, 132, 109, 5, 143]
            hatred = [130, 148, 149, 150, 140]
            sporn=[109,132]
            advert=[5,143]
            self.resolved_hosts[self.sitename]=1
            if  categoy_id in sporn:
                if ksrn_porn==1: ACTION="ARTICA"

            if categoy_id in advert:
                if DisableAdvert == 0: ACTION="ARTICA"

            if categoy_id in hatred:
                if hatred_and_discrimination ==1: ACTION="ARTICA"

            if categoy_id in BADCATZ: ACTION="ARTICA"
            if self.debug: self.xsyslog("%s: [DEBUG]: [THE_SHIELD] Shield result Scanner=%s" % (self.sitename,ACTION))


        if self.sitename in self.resolved_hosts:
            if self.resolved_hosts[self.sitename]==1: host=1

            if self.resolved_hosts[self.sitename]==0:
                self.resolved_hosts[self.sitename] = 0
                self.xsyslog("%s: [ERROR]: Unable to resolv host" % self.sitename)
                if categoy_id==0: categoy_id=112
                results_local = {}
                results_local["error"] = "UNKNOWN_HOST"
                results_local["categoy_id"] = categoy_id
                results_local["categoy_name"] = self.catz.category_int_to_string(categoy_id)
                results_local["ACTION"] = "PASS"
                results_local["TOTAL_DURATION"] = 0
                results_local["VIRTUAL_USER"] = self.VIRTUAL_USER
                results_local["COUNTRY_CODE"] = ""
                results_local["HOSTIP"] = ""
                self.CATEGORY = categoy_id
                self.CATEGORY_NAME = results_local["categoy_name"]
                if self.debug: self.xsyslog("[DEBUG]:---------------------------------------- L.356")
                results = serialize(results_local)
                return results

        if host is None:
            host=self.shield.get_host(self.sitename)
            if self.debug: self.xsyslog("%s: [DEBUG]: [THE_SHIELD] IP:<%s>" % (self.sitename, host))

        if host is None:
            self.resolved_hosts[self.sitename]=0
            self.xsyslog("%s: [ERROR]: Unable to resolv host" % self.sitename)
            results_local = {}
            categoy_id = self.catz.get_category(self.sitename)
            if categoy_id == 0:  categoy_id = 112
            results_local["error"] = "UNKNOWN_HOST"
            results_local["categoy_id"] = categoy_id
            results_local["categoy_name"] = self.catz.category_int_to_string(categoy_id)
            results_local["ACTION"] = "PASS"
            results_local["TOTAL_DURATION"] = 0
            results_local["VIRTUAL_USER"] = self.VIRTUAL_USER
            results_local["COUNTRY_CODE"] = ""
            results_local["HOSTIP"] = ""
            if self.debug: self.xsyslog("[DEBUG]:---------------------------------------- L.375")
            self.CATEGORY = categoy_id
            self.CATEGORY_NAME=results_local["categoy_name"]
            results = serialize(results_local)
            return results

        self.resolved_hosts[self.sitename] = 1
        if len(self.resolved_hosts)>5000: self.resolved_hosts={}

        self.shield.src_ip=self.ipaddr
        self.shield.username=self.USERNAME
        self.shield.mac = self.mac

        if categoy_id==0:
            self.shield.operate(self.sitename)
            categoy_id=self.shield.categoy_id
            ACTION=self.shield.ACTION
            categoryname = self.catz.category_int_to_string(categoy_id)

        results_local = {}
        results_local["error"] = self.shield.error
        results_local["categoy_id"] = categoy_id
        results_local["categoy_name"] = categoryname
        results_local["ACTION"] = ACTION
        results_local["TOTAL_DURATION"] = 0
        results_local["VIRTUAL_USER"] = self.VIRTUAL_USER
        results_local["COUNTRY_CODE"] = ""
        results_local["HOSTIP"]=host
        if self.debug: self.xsyslog("%s: [DEBUG]: [LOCAL] ACTION=%s ERROR=%s L.249" % (self.sitename, ACTION, self.shield.error))
        results = serialize(results_local)
        self.CATEGORY = categoy_id
        self.CATEGORY_NAME = categoryname

        if not ACTION in ACTIONSPASS:
            try:
                self.xsyslog("THREAT_DETECTED]: site=%s addr=%s self.USERNAME=%s mac=%s category=%s/%s scanner=%s" % (self.sitename, self.ipaddr, self.USERNAME, self.mac, categoy_id, categoryname, ACTION))
                self.shield.writestats(categoy_id, self.sitename, ACTION,0)
            except:
                self.xsyslog("%s: [ERROR]: self.shield.writestats %s" % (self.sitename, tb.format_exc()))


        if self.debug: self.xsyslog("[DEBUG]:---------------------------------------- L.430")
        return results


    def Process(self,line):
        self.sitename=""
        self.sourceline=line
        tstart = time()
        if self.debug: self.xsyslog("Receive <%s> L.346" % line)
        if line.find('webfilter:%20pass')>10:
            if self.debug: self.xsyslog("WEBFILTER = PASS")
            main_array = line.split(" ")
            concurrency=main_array[0]
            return "%s OK ptime=\n" % str(concurrency)

        if line.find('/squid-internal-dynamic/')>10:
            if self.debug: self.xsyslog("INTERNAL-DYNAMIC = PASS")
            main_array = line.split(" ")
            concurrency=main_array[0]
            return "%s OK ptime=\n" % str(concurrency)

        if line.find('/squid-internal-mgr/')>10:
            if self.debug: self.xsyslog("INTERNAL-DYNAMIC = PASS")
            main_array = line.split(" ")
            concurrency=main_array[0]
            return "%s OK ptime=\n" % str(concurrency)

        if line.find('cache_object:/')>0:
            if self.debug: self.xsyslog("INTERNAL-DYNAMIC = PASS")
            main_array = line.split(" ")
            concurrency=main_array[0]
            return "%s OK ptime=\n" % str(concurrency)


        self.TOKEN_OUPUT = "OK"
        sock_time="Sockets: None"

        try:
            parsed_array=self.ParseLine(line)
        except:
            concurrency=main_array[0]
            self.xsyslog("Error ParseLine array exception [%s] %s" % (line,tb.format_exc()))
            return "%s %s first=ERROR ptime=\n" % (str(concurrency),self.TOKEN_OUPUT)
        count_sleep = 0
        log_prefix = ""

        count_of_rows = len(parsed_array)

        if parsed_array["ACL"]==1: self.TOKEN_OUPUT="OK"
        TOKENS = []
        sni = ""
        USER_CERT = ""
        isDNS = 0
        CATEGORY_ORDER = True
        TOKENS_CATEGORY_ADDED = False
        rblpass = False
        CATEGORY_NAME = ""
        CATEGORY = 0
        method = "GET"
        CHOOSE = ""
        URL=""
        results = None
        blockit = False
        WHITE = False
        concurrency=0
        SaveCache = False
        CATEGORY = 0
        CATEGORY_NAME = ""
        ACTION = ""
        self.VIRTUAL_USER = ""
        CountryCode = ""
        ITCHART=""
        self.mac=""
        xforward=""
        sitename=""
        CLIENT_HOSTNAME=""
        ITCHART_INFO=""
        cache_message="MISS"
        CACHED_SERVICE=0
        PROXY_PORT=3128
        PROXY_IP="127.0.0.1"
        LOG_QUERY=[]
        CountOfInternalCache=len(self.local_cache)
        LOG_QUERY.append("Items in array: %s" % CountOfInternalCache)
        webfiltering_found=False
        webfiltering_checked=False
        as_acl=0
        ASK_TO_SHIELDS=True
        MODE_BACK=False
        self.method_proto=""
        try:
            concurrency = parsed_array["concurrency"]
            self.USERNAME = parsed_array["USERNAME"]
            ipaddr = parsed_array["ipaddr"]
            if "mac" in parsed_array: self.mac = parsed_array["mac"]
            if "forwardedfor" in parsed_array: xforward = parsed_array["forwardedfor"]
            if "sni" in parsed_array: sni = parsed_array["sni"]
            if "hostname" in parsed_array: CLIENT_HOSTNAME = parsed_array["hostname"]
            if "domain" in parsed_array: sitename = parsed_array["domain"]
            if "ACL" in parsed_array: as_acl=parsed_array["ACL"]
            if "proto" in parsed_array:method = parsed_array["proto"]
            if "isDNS" in parsed_array: isDNS=int(parsed_array["isDNS"])

            URL =parsed_array["URL"]
            try:
                PROXY_PORT=parsed_array["myport"]
            except:
                PROXY_PORT=3128

            try:
                PROXY_IP=parsed_array["myip"]
            except:
                PROXY_IP="127.0.0.1"

            if len(sitename)==0:
                if len(URL)>0: sitename=URL

            if sitename.find('://')>0: sitename=self.clean_sitename(sitename)
            PROXY_URL=URL
            SOURCE_URL=URL
            URL_DOMAIN=sitename
            CLIENT_IP=ipaddr
            self.ipaddr = ipaddr
            CLIENT_MAC=self.mac
            self.VIRTUAL_USER = self.VirtualUser()
            self.VIRTUAL_USER = str(self.VIRTUAL_USER)
        except:
            self.xsyslog("[ERROR] Parsing array exception L.261 [%s]" % tb.format_exc() )
            self.format_exc_log(tb.format_exc())
            return "%s %s first=ERROR ptime=\n" % (str(concurrency),self.TOKEN_OUPUT)


        sitename = sitename.lower()
        sitename = sitename.strip()
        self.sitename=sitename
        self.method_proto=method

        if self.mac == "00:00:00:00:00:00": self.mac = ""
        log_prefix = "%s %s %s %s %s" % (sitename, method, self.mac, self.ipaddr, self.USERNAME)
        LOG_QUERY.append(log_prefix)
        self.shield.src_ip=self.ipaddr
        self.shield.mac=self.mac
        self.shield.username=self.USERNAME
        self.shield.CountUsers()
        
        try:
            if len(self.VIRTUAL_USER) > 0: self.USERNAME = self.VIRTUAL_USER
        except:
            self.xsyslog("%s [%s] ERROR L.274" % (self.sitename,self.VIRTUAL_USER))
            self.format_exc_log(tb.format_exc())

        if xforward == "-": xforward = ""
        if len(xforward) > 0: ipaddr = xforward

        if len(self.USERNAME) < 3:
            if len(USER_CERT) > 2: self.USERNAME = USER_CERT

        if self.sitename == "127.0.0.1":
            line_to_send = "%s %s %s ptime=\n" % (concurrency,self.TOKEN_OUPUT, "first=NONE")
            return line_to_send

        if self.SquidUrgency == 1:
            if self.debug: self.xsyslog("WARNING... Emergency Enabled")
            line_to_send = "%s %s %s ptime=\n" % (concurrency,self.TOKEN_OUPUT, "first=EMERGENCY webfilter=pass")
            return line_to_send

        if as_acl==0:
            MODE_BACK=True
            ASK_TO_SHIELDS=False

        if isDNS:
            if self.is_whitelist(self.sitename, self.mac, self.ipaddr):
                if self.debug: self.xsyslog("%s: %s[%s] WHITELISTED" % (self.sitename, self.ipaddr, self.mac))
                CATEGORY = self.catz.get_category(self.sitename)
                CATEGORY_NAME = self.catz.category_int_to_string(CATEGORY)
                TOKENS.append("category=%s category-name=%s clog=cinfo:%s-%s; " % (
                CATEGORY, CATEGORY_NAME, CATEGORY, CATEGORY_NAME))
                TOKENS.append("srn=WHITE rblpass=yes webfilter=pass")
                line_to_send = "%s %s %s ptime=\n" % (concurrency, self.TOKEN_OUPUT, " ".join(TOKENS))
                return line_to_send

            if self.is_blacklist(self.sitename):
                if self.debug: self.xsyslog("%s: %s[%s] BLACKLISTED" % (self.sitename, self.ipaddr, self.mac))
                CATEGORY = self.catz.get_category(self.sitename)
                CATEGORY_NAME = self.catz.category_int_to_string(CATEGORY)
                TOKENS.append("category=%s category-name=%s clog=cinfo:%s-%s; " % (
                CATEGORY, CATEGORY_NAME, CATEGORY, CATEGORY_NAME))
                TOKENS.append("srn=BLACK shieldsblock=yes")
                line_to_send = "%s %s %s ptime=\n" % (concurrency, self.TOKEN_OUPUT, " ".join(TOKENS))
                self.write_ufdblog("global_blacklist", "DNS")
                return line_to_send


            if self.EnableUfdbGuard == 1:
                MODE_BACK = True
                ASK_TO_SHIELDS=True



        if MODE_BACK:
            if self.debug: self.xsyslog("%s [DEBUG]: [BACK_MODE] Web-Filtering:%s | ItCharter:%s" % (self.sitename,self.EnableUfdbGuard, self.EnableITChart))
            if self.sourceline.find("shieldsblock:%20yes")>10:
                self.WEBFILTER_RULE_NAME = 0
                self.CATEGORY = 999999999
                self.CATEGORY_NAME = "theshields"
                matches=re.search('cinfo:([0-9]+)-(.+?);',self.sourceline)
                if matches:
                    self.CATEGORY=matches.group(1)
                    self.CATEGORY_NAME=matches.group(2)

                redirect=self.build_error_page()
                line_to_send = "%s %s %s\n" % (concurrency, self.TOKEN_OUPUT, redirect)
                return line_to_send

            if self.EnableUfdbGuard==1:
                line_to_send=self.ufdbguard_client(concurrency,line,PROXY_URL,self.sitename,CLIENT_IP,CLIENT_HOSTNAME,CLIENT_MAC,PROXY_IP,PROXY_PORT)
                webfiltering_checked=True
                if self.debug: self.xsyslog("%s [DEBUG] return <%s>" % (self.sitename,line_to_send))
                if len(line_to_send)>0: return line_to_send
                TOKENS.append("webfilter=pass")

            if not isDNS:
                if self.EnableITChart==1:
                    if self.debug: self.xsyslog("%s [DEBUG][ITCHART] Ask to itchart_client()" % self.sitename)
                    line_to_send = self.itchart_client()
                    if len(line_to_send) > 0:
                        if self.debug: self.xsyslog("%s [DEBUG][ITCHART] return <%s>" % (self.sitename, line_to_send))
                        return line_to_send
                    TOKENS.append("itchart=PASS")

            line_to_send = "%s %s %s ptime=\n" % (concurrency, self.TOKEN_OUPUT, " ".join(TOKENS))
            if self.debug: self.xsyslog("%s [DEBUG] ----------------------------- FINAL L.435" % self.sitename)
            if self.debug: self.xsyslog("%s [DEBUG] return <%s>" % (self.sitename, line_to_send))
            if not isDNS: return line_to_send




        if not isDNS:
            if self.is_whitelist(self.sitename,self.mac,self.ipaddr):
                if self.debug: self.xsyslog("%s: %s[%s] WHITELISTED" % (self.sitename,self.ipaddr,self.mac))
                CATEGORY = self.catz.get_category(self.sitename)
                CATEGORY_NAME = self.catz.category_int_to_string(CATEGORY)
                TOKENS.append("category=%s category-name=%s clog=cinfo:%s-%s; " % (CATEGORY, CATEGORY_NAME, CATEGORY, CATEGORY_NAME))
                TOKENS.append("srn=WHITE rblpass=yes webfilter=pass")
                line_to_send = "%s %s %s ptime=\n" % (concurrency, self.TOKEN_OUPUT, " ".join(TOKENS))
                return line_to_send

            if self.is_blacklist(self.sitename):
                if self.debug: self.xsyslog("%s: %s[%s] BLACKLISTED" % (self.sitename, self.ipaddr, self.mac))
                CATEGORY = self.catz.get_category(self.sitename)
                CATEGORY_NAME = self.catz.category_int_to_string(CATEGORY)
                TOKENS.append("category=%s category-name=%s clog=cinfo:%s-%s; " % (
                CATEGORY, CATEGORY_NAME, CATEGORY, CATEGORY_NAME))
                TOKENS.append("srn=BLACK shieldsblock=yes")
                line_to_send = "%s %s %s ptime=\n" % (concurrency, self.TOKEN_OUPUT, " ".join(TOKENS))
                self.write_ufdblog("global_blacklist")
                return line_to_send





        CHOOSE = self.FindKeyAccount()
        prepare_data={}
        prepare_data["ACTION"] = "THESHIELDS"
        prepare_data["CHOOSE"]=CHOOSE
        prepare_data["USERNAME"] = self.USERNAME
        prepare_data["ipaddr"] = ipaddr
        prepare_data["mac"] = self.mac
        prepare_data["sitename"] = self.sitename
        prepare_data["method"]=method
        prepare_data["LOG_QUERY"]=0

        if self.ExternalAclFirstRequest == 1:
            LOG_QUERY.append("Log-query: Yes")
            prepare_data["LOG_QUERY"]=1

        self.prepare_data_text=serialize(prepare_data)
        smd5 = self.mem.skey_shields_fullcache(self.USERNAME,ipaddr,self.mac,self.sitename,method)

        if smd5 in self.local_cache:
            if self.debug: self.xsyslog("%s: HIT [%s] Client-array  L.458" % (self.sitename,smd5))
            results = self.local_cache[smd5]


        if results is None:
            results = self.mem.ksrncache_get(smd5)
            if results is not None:
                if self.debug: self.xsyslog("%s HIT [%s] Client-memcache L.465" % (self.sitename,smd5))
                self.local_cache[smd5] = results

        if results is not None:
            ASK_TO_SHIELDS=False
            LOG_QUERY.append("Client sock time: -")

        if results is None:
            if self.debug: self.xsyslog("%s MISS ARRAY [%s] L.354 KSRNOnlyCategorization=%s" % (self.sitename, smd5,self.KSRNOnlyCategorization))
            if self.KSRNOnlyCategorization == 1:
                if len(self.USERNAME) < 2:
                    if self.VIRTUAL_USER is None: self.VIRTUAL_USER = ""
                    if len(Vself.IRTUAL_USER) > 0:
                        self.USERNAME = self.VIRTUAL_USER
                        CHOOSE = self.FindKeyAccount()

                if len(self.USERNAME) > 1: TOKENS.append("user=%s" % urlencode(self.USERNAME))
                return self.only_categorization(concurrency,self.sitename,self.VIRTUAL_USER,TOKENS,CATEGORY,CATEGORY_NAME)

        if self.debug: self.xsyslog("[DEBUG]:ASK_TO_SHIELDS [%s] MODE_BACK [%s] ------------------------------- L.486" % (ASK_TO_SHIELDS,MODE_BACK))

        if ASK_TO_SHIELDS:
            try:
                results=self.Process_the_shields()
            except:
                serror=tb.format_exc()
                self.xsyslog("%s ERROR Process_the_shields [%s] L.662" % (self.sitename,serror))
                return "%s %s first=ERROR ptime=\n" % (str(concurrency),self.TOKEN_OUPUT)

        if len(results)< 5:
            if self.debug: self.xsyslog("%s ERROR LEN ARRAY" % log_prefix)
            return "%s %s first=ERROR ptime=\n" % (str(concurrency),self.TOKEN_OUPUT)

        if SaveCache:
            if self.ExternalAclFirstRequest == 1: self.xsyslog("[DEBUG]: [%s] [CACHE] SET %s" % (sitename,smd5))
            self.local_cache[smd5]=results
            if self.ExternalAclFirstRequest == 1: self.xsyslog("%s during %s seconds" % (sitename,sock_time))
            if not self.mem.ksrncache_set(smd5,results,self.KSRNClientCacheTime):self.xsyslog("memcache_set issue %s" % self.mem.error)
            if len(self.local_cache)>1500: self.local_cache={}


        if self.debug: self.xsyslog("%s [DEBUG]: Receive [%s] L.456" % (sitename,results))
        try:
            result = unserialize(results)
        except:
            self.xsyslog("%s: [ERROR]: Unserialize error L.509 " % sitename)
            self.format_exc_log(tb.format_exc())
            self.xsyslog("%s: [ERROR]: L.435 Data was [%s]" % (sitename,results))
            return False
        try:
            error = result["error"]
            if CATEGORY == 0: CATEGORY = int(result["categoy_id"])
            if len(CATEGORY_NAME) ==0: CATEGORY_NAME = result["categoy_name"]
            ACTION                  = result["ACTION"]
            self.VIRTUAL_USER       = result["VIRTUAL_USER"]
            CountryCode             = result["COUNTRY_CODE"]
            if "ITCHART" in result: ITCHART=result["ITCHART"]
            if "ITCHART_INFO" in result: ITCHART_INFO=result["ITCHART_INFO"]
            if "CACHED_SERVICE" in result: CACHED_SERVICE=int(result["CACHED_SERVICE"])

            if "CACHED_TIME" in result:
                LOG_QUERY.append("Service cached time: %s" % result["CACHED_TIME"])

            if "SHIELD_TIMES" in result:
                LOG_QUERY.append("Time details: [%s]" % result["SHIELD_TIMES"])

            if "SHIELD_DURATION" in result:
                LOG_QUERY.append("The Shield duration: %s" % result["SHIELD_DURATION"])

            if "TOTAL_DURATION" in result:
                LOG_QUERY.append("The Shield Total: %s" % result["TOTAL_DURATION"])
        except:
            self.xsyslog("[ERROR]: unserialize error %s" % tb.format_exc())
            if self.ExternalAclFirstRequest == 1: self.xsyslog("%s ERROR UNSERIALIZE" % log_prefix)
            return "%s %s first=ERROR ptime=\n" % (str(concurrency),self.TOKEN_OUPUT)

        if ACTION == "WHITELIST":
            TOKENS.append("srn=WHITE rblpass=yes")
            WHITE=True


        blockit = True
        if self.KRSN_DEBUG==1: self.xsyslog("%s The Shield, Answering [ -%s- ] [%s] L.1003" % (sitename,error, ACTION))

        if ACTION == "WHITELIST":
            TOKENS.append("srn=WHITE rblpass=yes")
            blockit=False

        if ACTION == "PASS":
            TOKENS.append("srn=PASS")
            blockit = False

        if ACTION == "WHITE":
            TOKENS.append("srn=WHITE rblpass=yes")
            blockit = False

        if len(ACTION)==0:
            if self.KRSN_DEBUG == 1: self.xsyslog(
                "%s The Shield, ERROR OCCURED NO ACTION L.1019" % (sitename, error, ACTION))
            TOKENS.append("srn=ERROR")
            blockit = False

        if blockit:
            if self.KRSN_DEBUG == 1: self.xsyslog(
                "%s The Shield, BLOCK [ -%s- ] [%s] L.1024" % (sitename, error, ACTION))
            TOKENS.append("shieldsblock=yes")
            TOKENS.append("srn=%s" % ACTION)
            self.CATEGORY=CATEGORY
            self.write_ufdblog("TheShields:%s" % ACTION)

        if CATEGORY > 0:
            CATEGORY_NAME=CATEGORY_NAME.replace(" ","_")
            CATEGORY_NAME=CATEGORY_NAME.replace("/","_")
            if not webfiltering_found: TOKENS.append("category=%s category-name=%s clog=cinfo:%s-%s; " % (CATEGORY,CATEGORY_NAME,CATEGORY,CATEGORY_NAME))
            LOG_QUERY.append("Category: %s" % CATEGORY_NAME)
        else:
            if not webfiltering_found: TOKENS.append("category=0 category-name=Unknown clog=cinfo:0-unknown;")
            LOG_QUERY.append("Category: Unknown")

        if self.debug: self.xsyslog("[DEBUG]: [%s]: - L.484 -" % sitename)
        if len(self.USERNAME) < 2:
            if self.VIRTUAL_USER is None: self.VIRTUAL_USER=""
            if len(self.VIRTUAL_USER)>0:
                self.USERNAME = self.VIRTUAL_USER
                CHOOSE = self.FindKeyAccount( )

        if len(self.USERNAME)>1: TOKENS.append("user=%s" % urlencode(self.USERNAME))
        if len(CountryCode) > 0: TOKENS.append("fromgeo=%s" % CountryCode)

        if blockit:
            line_to_send = "%s %s %s ptime=\n" % (concurrency,self.TOKEN_OUPUT, " ".join(TOKENS))
            if self.debug:
                tend = time()
                tdiff = tend - tstart
                ttext = "Total: {} seconds".format(tdiff)
                self.xsyslog("%s: [DEBUG]: [DENIED]: FINAL[%s] (processing %s) L.737 -" % (sitename, line_to_send,ttext))
            return line_to_send

        if self.debug: self.xsyslog("[DEBUG]: [%s]: self.TOKEN_OUPUT [%s]" % (sitename,self.TOKEN_OUPUT))
        line_to_send = "%s %s %s ptime=\n" % (concurrency,self.TOKEN_OUPUT, " ".join(TOKENS))
        if self.debug:
            tdiff=self.TimeExec(tstart)
            ttext="Total: {} seconds".format(tdiff)
            self.xsyslog("%s: [DEBUG] FINAL[%s] (processing %s) L.747 -" % (sitename,line_to_send,ttext))
        return line_to_send

    def xsyslog(self,text):
        sysDaemon=syslog
        sysDaemon.openlog("ksrn", syslog.LOG_PID)
        sysDaemon.syslog(syslog.LOG_INFO, "[CLIENT_CLASS]: %s" % text)

    def ufdbgclient_log(self,text):
        sysDaemon = syslog
        sysDaemon.openlog("webfiltering", syslog.LOG_PID)
        sysDaemon.syslog(syslog.LOG_INFO, text)

    def itcharlogs(self,text):
        ItChartLog=syslog
        ItChartLog.openlog("ItCharter", syslog.LOG_PID)
        ItChartLog.syslog(syslog.LOG_INFO, text)
        ItChartLog.closelog()

    def only_categorization(self,concurrency,sitename,VIRTUAL_USER,TOKENS,CATEGORY,CATEGORY_NAME):
        if CATEGORY == 0:
            if sitename in self.onlycatz_array:
                CATEGORY = self.onlycatz_array[sitename]
                if CATEGORY > 0: CATEGORY_NAME = self.catz.category_int_to_string(CATEGORY)
            else:
                CATEGORY = self.catz.get_category(sitename)
                self.onlycatz_array[sitename] = CATEGORY
                if CATEGORY > 0: CATEGORY_NAME = self.catz.category_int_to_string(CATEGORY)

        TOKENS.append("category=%s category-name=%s clog=cinfo:%s-%s; " % (CATEGORY, CATEGORY_NAME, CATEGORY, CATEGORY_NAME))
        if len(self.onlycatz_array) > 10000: self.onlycatz_array = {}
        line_to_send = "%s OK %s\n" % (concurrency,  " ".join(TOKENS))
        self.xsyslog("only_categorization [%s] L.626" % line_to_send )
        return line_to_send



    def get_only_catz(self,sitename, VIRTUAL_USER):
        result = {}
        ACTION = "PASS"
        CATEGORY_NAME = "unknown"
        if sitename in self.onlycatz_array:
            CATEGORY = self.onlycatz_array[sitename]
        else:
            CATEGORY = self.catz.get_category(sitename)
            self.onlycatz_array[sitename] = CATEGORY

        if len(self.onlycatz_array) > 10000: self.onlycatz_array = {}
        if self.catz.admin_whitelist(sitename, False): ACTION = "WHITELIST"
        if CATEGORY > 0: CATEGORY_NAME = self.catz.category_int_to_string(CATEGORY)
        result["error"] = ""
        result["categoy_id"] = CATEGORY
        result["categoy_name"] = CATEGORY_NAME
        result["ACTION"] = "PASS"
        result["VIRTUAL_USER"] = VIRTUAL_USER
        result["COUNTRY_CODE"] = ""
        result["categoy_name"] = CATEGORY_NAME
        result["ACTION"] = ACTION
        return serialize(result)


    def write_ufdblog(self,rulename="",force_proto=""):
        file_path = '/var/log/squid/ufdbguardd.log'
        now = datetime.now()
        date_time   = now.strftime("%Y-%m-%d %H:%M:%S")
        pid         = str(os.getpid())
        username    = self.USERNAME
        ipaddr      = self.ipaddr
        category    = str(self.CATEGORY)
        sitename    = self.sitename
        PROTO       = self.method_proto
        if len(force_proto)>2: PROTO=force_proto
        if len(username)<2: username="-"
        slog="%s [%s] BLOCK %s         %s    %s P%s            http://%s %s  ipv4-range\n" % (date_time,pid,username,ipaddr,rulename,category,sitename,PROTO)
        if not os.path.exists(file_path):
            try:
                f = open(file_path, 'w')
                f.write(slog)
                f.close()
                return True
            except:
                self.xsyslog("FATAL %s [%s] L.1110" %  (sitename,tb.format_exc()))
                return False

        try:
            f = open(file_path, 'a')
            f.write(slog)
            f.close()
        except:
            self.xsyslog("FATAL %s [%s] L.1118" % (sitename,tb.format_exc()))
            return False
        return True

    def FindKeyAccount(self):
        if self.USERNAME == "-": self.USERNAME = ""
        if self.ipaddr == "-": self.ipaddr = ""
        if self.ipaddr == "127.0.0.1": self.ipaddr = ""
        if self.mac == "-": self.mac = ""
        if self.mac == "00:00:00:00:00:00": mac = ""
        if len(self.USERNAME) > 3: return self.USERNAME
        if len(self.mac) > 3: return self.mac
        if len(self.ipaddr) > 3: return self.ipaddr

    def clean_sitename(self,sitename):
        from urlparse import urlparse
        parsed = urlparse(sitename)
        return parsed.hostname

    def VirtualUser(self):
        ipStrongSwan = ""
        if self.sourceline.find("user:%20") > 10:
            matches = re.search('user:%20(.+?)%0D%0A', self.sourceline)
            if matches:
                self.USERNAME = str(matches.group(1))
                return str(self.USERNAME)



        key = "%s.%s" % (self.mac, self.ipaddr)
        if len(self.virtual_user_cache) > 5000: self.virtual_user_cache = {}
        if key in self.virtual_user_cache: return str(self.virtual_user_cache[key])
        if self.EnableStrongswanServer == 1: ipStrongSwan = self.ipaddr
        sresult = self.mem.UserAliases(self.mac, self.ipaddr, ipStrongSwan)
        if sresult is None:
            self.virtual_user_cache[key]=""
            return ""

        self.virtual_user_cache[key] = str(sresult)
        return str(sresult)



    def is_blacklist(self,sitename):
        if len(sitename) < 3: return False
        if self.catz.admin_blacklist(sitename): return True
        return  False



    def is_whitelist(self,sitename,mac,ipaddr):
        if sitename is None: return False
        if mac is None: return False
        if ipaddr is None: return False

        if len(sitename)>3:
            scache = "DOMWHITE:%s" % sitename
            sf = self.GetCacheItem(scache)
            if sf is not None: return True
            if self.catz.admin_whitelist(sitename):
                self.SaveCacheItem(scache,True)
                return True
        if len(mac)>3:
            if self.catz.admin_whitelist_mac(mac): return True
        if len(ipaddr)>3:
            if self.catz.admin_whitelist_src(ipaddr): return True
        return False

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
        if len(self.local_cache)> self.MaxItemsInMemory: self.local_cache={}


        try:
            newsValue=str(sValue)
            if not self.mem.ksrncache_set(smd5,newsValue,self.cache_time):
                self.xsyslog("Error while writing in memcache %s %s" % (smd5,self.mem.error))
                return False
        except:
            self.xsyslog("Error while writing in memcache %s=[%s]" % (smd5,sValue))
            self.format_exc_log(tb.format_exc())
            return False

        return True