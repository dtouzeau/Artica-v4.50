#!/usr/bin/env python
from unix import *
import os.path
import logging
import memcache
import hashlib
import datetime
import re
from netaddr import IPNetwork, IPAddress, IPRange
from postgressql import *

class PROXYPAC:
    
    def __init__(self):
        self.ProxyPacLockScript=GET_INFO_INT("ProxyPacLockScript")
        self.RootDir="/home/squid/proxy_pac_rules"
        self.cherrypy=object
        self.debug=False
        self.ProxyPACSQL=0
        self.q=object
        self.ProxyPACSQL=GET_INFO_INT("ProxyPACSQL")
        self.fqdn_hostname=GET_INFO_STR("fqdn_hostname")
        if self.ProxyPACSQL==1: self.q=Postgres()
        if GET_INFO_INT("ProxyPacDebug") == 1: self.debug=True
        pass
    
    
    def PackThis(self,UserAgent,IPfrom):
        RQS=0
        now = datetime.now()
        filename = now.strftime('history_%m%d%H%M.log')
        mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        t = int(time.time())
        try:
            with open("/var/log/proxy-pac/"+filename, "a") as f:
                f.write(str(t)+'|||'+UserAgent+'|||'+IPfrom+'|||\n')
                f.close()
        except:
            self.cherrypy.log("[" + IPfrom + "][FATAL]: Unable to write /var/log/proxy-pac/"+filename, "PackThis")

        Value = mc.get("PROXYPAC_RQS")
        if Value is not None:RQS=Value
        RQS=RQS+1
        mc.set("PROXYPAC_RQS", RQS, 2880)

        if self.ProxyPacLockScript==1:
            self.cherrypy.log("["+IPfrom+"][END]: Return Locked script (no rule)", "PackThis")
            if self.ProxyPACSQL==1: self.q.QUERY_SQL("INSERT INTO proxypac (zdate,ipaddr,useragent,rule) VALUES (now(),'"+IPfrom+"','"+UserAgent+"',0)")
            return file_get_contents("/etc/artica-postfix/settings/Daemons/ProxyPacLockScriptContent")
        
        if not os.path.exists("/home/squid/proxy_pac_rules/rules.conf"):
            self.cherrypy.log("[" + IPfrom + "][ALERT]: No rule is builded", "PackThis")
            if self.debug: self.cherrypy.log("/home/squid/proxy_pac_rules/rules.conf no such file", "PackThis")
            return ""

        if self.debug: self.cherrypy.log("[" + IPfrom + "][DEBUG]: OPEN /home/squid/proxy_pac_rules/rules.conf", "PackThis")
        
        with open("/home/squid/proxy_pac_rules/rules.conf","r") as f:
            for txt in f :
                txt=txt.rstrip('\n')
                if len(txt)<5: continue
                matches=re.search('([0-9]+)\|(.+)',txt)
                if not matches: continue
                RuleName=matches.group(2)
                RuleID=matches.group(1)
                FileName="/home/squid/proxy_pac_rules/"+str(RuleID)+"/proxy.pac"

                if not os.path.exists(FileName):
                    self.cherrypy.log("[" + IPfrom + "][ALERT]: "+FileName+" no such file", "PackThis")
                    continue

                if self.MatchesException(UserAgent,IPfrom,RuleID):
                    if self.debug: self.cherrypy.log("[" + IPfrom + "][DEBUG]: " + RuleName + " Exception MATCH, SKIP","PackThis")
                    continue


                if not self.MatchesSrcProxy(RuleID):
                    if self.debug: self.cherrypy.log("[" + IPfrom + "][DEBUG]: "+str(RuleID)+"] "+RuleName +" MatchesSrcProxy return false -> Next", "PackThis")
                    continue


                if self.MatchesSrc(UserAgent,IPfrom,RuleID):
                    if self.MatchesSrcNot(UserAgent,IPfrom,RuleID):
                        self.cherrypy.log("[" + IPfrom + "][END]: MATCHED RULE:"+str(RuleID), "PackThis")
                        if self.ProxyPACSQL==1: self.q.QUERY_SQL("INSERT INTO proxypac (zdate,ipaddr,useragent,rule) VALUES (now(),'"+IPfrom+"','"+UserAgent+"',"+str(RuleID)+")")
                        if self.debug: self.cherrypy.log("[" + IPfrom + "][DEBUG]: Return "+FileName,"MatchesSrc")
                        return file_get_contents(FileName)
                
        if self.ProxyPACSQL==1: self.q.QUERY_SQL("INSERT INTO proxypac (zdate,ipaddr,useragent,rule) VALUES (now(),'"+IPfrom+"','"+UserAgent+"',-1)")        
        self.cherrypy.log("[" + IPfrom + "][END]: NO RULE return Default", "PackThis")
        return file_get_contents("/home/squid/proxy_pac_rules/default.pac")
        pass


    def MatchesSrcProxy(self,RuleID):
        CountOfRules    = 0
        FileName        = "/home/squid/proxy_pac_rules/" + str(RuleID) + "/Sources.rules"
        zType           = "srcproxy"

        if not os.path.exists(FileName):
            if self.debug: self.cherrypy.log("[127.0.0.1][ALERT]: "+FileName + " No such file [FALSE]", "MatchesSrcProxy")
            return False

        with open(FileName, "r") as f:
            for txt in f:
                txt = txt.rstrip('\n')
                if len(txt) < 5: next
                matches = re.search('srcproxy\s+(.+?)\s+(.+)', txt)
                if not matches:
                    if self.debug: self.cherrypy.log("[127.0.0.1][DEBUG]: "+txt + " Not matches??? ", "MatchesSrcProxy")
                    continue

                zOpe         = matches.group(1)
                zPattern     = matches.group(2)
                CountOfRules = CountOfRules + 1


                if self.debug:
                    self.cherrypy.log("[127.0.0.1][DEBUG]: "+str(RuleID)+"] Type:"+ zType+" Operator:"+ zOpe+" Pattern:"+ zPattern,"MatchesSrcProxy")

                if self.debug: self.cherrypy.log("[127.0.0.1][DEBUG]: Checks '"+zPattern+"' against '"+self.fqdn_hostname+"'", "MatchesSrcProxy")
                matches = re.search(zPattern, self.fqdn_hostname)


                if zOpe == "Yes":
                    if matches:
                        if self.debug: self.cherrypy.log("[127.0.0.1][DEBUG]: "+str(RuleID) + "] TRUE: " + zPattern+ " matches "+self.fqdn_hostname, "MatchesSrcProxy")
                        return True

                if zOpe == "No":
                    if not matches:
                        if self.debug: self.cherrypy.log("[127.0.0.1][DEBUG]: "+str(RuleID) + "] TRUE: " + zPattern + " NOT match " + self.fqdn_hostname,"MatchesSrcProxy")
                        return True

                if self.MatchesMyIps(zPattern,zOpe):
                    if self.debug: self.cherrypy.log("[127.0.0.1][DEBUG]: "+str(RuleID) + "] TRUE: " + zPattern + " Matches local IPs ","MatchesSrcProxy")
                    return True

        if CountOfRules==0:
            if self.debug: self.cherrypy.log("[127.0.0.1][DEBUG]: "+str(RuleID) + "] No rule as been defined for src proxy","MatchesSrcProxy")
            return True


        return False
    pass


    def MatchesMyIps(self,pattern,zOpe):

        FileName="/home/squid/proxy_pac_rules/myips"
        if not os.path.exists(FileName):
            if self.debug: self.cherrypy.log("["+pattern+"][ALERT]: " + FileName + " not exists","MatchesMyIps")
            return False

        with open(FileName, "r") as f:
            for txt in f:
                txt = txt.rstrip('\n')
                if len(txt) < 5: continue
                if self.debug: self.cherrypy.log("["+pattern+"][DEBUG]: CHECKS: " + pattern + " against "+txt, "MatchesMyIps")
                matches = re.search(pattern, txt)

                if zOpe == "Yes":
                    if matches:
                        if self.debug: self.cherrypy.log("["+pattern+"][DEBUG]: TRUE: " + pattern + " matches " + txt,"MatchesMyIps")
                        return True

                if zOpe == "No":
                    if not matches:
                        if self.debug: self.cherrypy.log("["+pattern+"][DEBUG]: TRUE: " + pattern + " NOT match " + txt,"MatchesMyIps")
                        return True

        return False

    pass

    def MatchesException(self, UserAgent, IPfrom, RuleID):
        FileName = "/home/squid/proxy_pac_rules/" + str(RuleID) + "/Except.rules"
        if not os.path.exists("/home/squid/proxy_pac_rules/rules.conf"):
            if self.debug: self.cherrypy.log(
                "[" + IPfrom + "][ALERT]: /home/squid/proxy_pac_rules/rules.conf No such file [FALSE]", "MatchesException")
            return False

        if not os.path.exists(FileName):
            if self.debug: self.cherrypy.log("[" + IPfrom + "][INFO]: " + FileName + " No such file [FALSE]","MatchesException")
            return False

        if self.debug:self.cherrypy.log("[127.0.0.1][DEBUG]: " + str(RuleID) + "] UserAgent:" + UserAgent + " From:" + IPfrom,"MatchesException")


        with open(FileName, "r") as f:
            for txt in f:
                txt = txt.rstrip('\n')
                if len(txt) < 5: next
                matches = re.search('(.+?)\s+(.+?)\s+(.+)', txt)
                if not matches:
                    if self.debug: self.cherrypy.log("[" + IPfrom + "][ALERT]: " + txt + " Wrong pattern l.175 ","MatchesException")
                    next
                zType = matches.group(1)
                zOpe = matches.group(2)
                zPattern = matches.group(3)
                if zOpe == "No": continue

                if self.debug:
                    self.cherrypy.log("[127.0.0.1][DEBUG]: " + str(RuleID) + "] Type:" + zType + " Operator:" + zOpe + " Pattern:" + zPattern,"MatchesException")

                if zType == "all":
                    if self.debug: self.cherrypy.log("[" + IPfrom + "][DEBUG]: ALL matches everywhere, everytime [OK]","MatchesException")
                    return True
                # ------------------------------------------------------------------------------------------------------------------
                if zType == "dstdomain":
                    if self.debug: self.cherrypy.log(
                        "[" + IPfrom + "][DEBUG]: dstdomain no sense here, only src can be set [CONTINUE]",
                        "MatchesException")
                    continue

                # ------------------------------------------------------------------------------------------------------------------
                if zType == "src":
                    matches = re.search('([0-9\.\/]+)-([0-9\.\/]+)', zPattern)

                    if matches:
                        zRange = list(IPRange(matches.group(1), matches.group(2)))
                        if IPAddress(IPfrom) in zRange:
                            self.cherrypy.log("[" + IPfrom + "][INFO]: Match " + zPattern + " [OK]", "MatchesException")
                            return True

                    if IPAddress(IPfrom) in IPNetwork(zPattern):
                        self.cherrypy.log("[" + IPfrom + "][INFO]: Match " + zPattern + " [OK]", "MatchesException")
                        return True

                # ------------------------------------------------------------------------------------------------------------------
                if zType == "browser":
                    matches = re.search(zPattern, UserAgent, re.IGNORECASE)
                    if matches:
                        self.cherrypy.log("[" + IPfrom + "][INFO]: " + UserAgent + " Match " + zPattern + " [OK]", "MatchesException")
                        return True

        return False
        pass

    def MatchesSrc(self,UserAgent,IPfrom,RuleID):
        FileName="/home/squid/proxy_pac_rules/"+str(RuleID)+"/Sources.rules"
        if not os.path.exists("/home/squid/proxy_pac_rules/rules.conf"):
            if self.debug: self.cherrypy.log("["+IPfrom+"][ALERT]: /home/squid/proxy_pac_rules/rules.conf No such file [FALSE]","MatchesSrc")
            return False
        
        if not os.path.exists(FileName):
            if self.debug: self.cherrypy.log("["+IPfrom+"][ALERT]: "+FileName+" No such file [FALSE]","MatchesSrc")
            return False
            
        if self.debug:
            self.cherrypy.log("["+IPfrom+"][DEBUG]: "+str(RuleID)+"] UserAgent:"+ UserAgent+" IP:"+IPfrom,"MatchesSrc")

        with open(FileName,"r") as f:
             for txt in f :
                txt=txt.rstrip('\n')
                if len(txt)<5: next
                matches=re.search('(.+?)\s+(.+?)\s+(.+)',txt)
                if not matches:
                    if self.debug: self.cherrypy.log("["+IPfrom+"][ALERT]: "+txt+" Wrong pattern l.175 ","MatchesSrc")
                    next
                zType=matches.group(1)
                zOpe=matches.group(2)
                zPattern=matches.group(3)
                if zOpe == "No": continue
                
                if self.debug:
                    self.cherrypy.log("[127.0.0.1][DEBUG]: " + str(RuleID) + "] Type:" + zType + " Operator:" + zOpe + " Pattern:" + zPattern,"MatchesSrc")

                if zType == "all":
                    if self.debug: self.cherrypy.log("["+IPfrom+"][DEBUG]: ALL matches everywhere, everytime [OK]","MatchesSrc")
                    return True
        #------------------------------------------------------------------------------------------------------------------                    
                if zType == "dstdomain":
                    if self.debug: self.cherrypy.log("["+IPfrom+"][DEBUG]: dstdomain no sense here, only src can be set [CONTINUE]","MatchesSrc")
                    continue
                        
        #------------------------------------------------------------------------------------------------------------------
                if zType == "src":
                    matches = re.search('([0-9\.\/]+)-([0-9\.\/]+)', zPattern)
                    if matches:
                        zRange = list(IPRange(matches.group(1), matches.group(2)))
                        if IPAddress(IPfrom) in zRange:
                            self.cherrypy.log("[" + IPfrom + "][INFO]: Match " + zPattern + " [OK]", "MatchesSrc")
                            return True

                    if IPAddress(IPfrom) in IPNetwork(zPattern):
                        self.cherrypy.log("["+IPfrom+"][INFO]: Match "+zPattern+" [OK]","MatchesSrc")
                        return True

        #------------------------------------------------------------------------------------------------------------------                    
                if zType=="browser":
                    matches=re.search(zPattern,UserAgent,re.IGNORECASE)
                    if matches:
                        self.cherrypy.log("["+IPfrom+"][INFO]: "+UserAgent+" Match "+zPattern+" [OK]","MatchesSrc")
                        return True


        return False
        pass

    def MatchesSrcNot(self, UserAgent, IPfrom, RuleID):
        FileName = "/home/squid/proxy_pac_rules/" + str(RuleID) + "/Sources.rules"
        CountOfRules=0
        if not os.path.exists("/home/squid/proxy_pac_rules/rules.conf"):
            if self.debug: self.cherrypy.log(
                "[" + IPfrom + "][ALERT]: /home/squid/proxy_pac_rules/rules.conf No such file [FALSE]", "MatchesSrcNot")
            return False

        if not os.path.exists(FileName):
            if self.debug: self.cherrypy.log("[" + IPfrom + "][ALERT]: " + FileName + " No such file [FALSE]","MatchesSrcNot")
            return False

        if self.debug:
            self.cherrypy.log("[" + IPfrom + "][DEBUG]: " + str(RuleID) + "] UserAgent:" + UserAgent+" IP:"+IPfrom, "MatchesSrcNot")
            self.cherrypy.log("[" + IPfrom + "][DEBUG]: " + str(RuleID) + "] Open " + FileName, "MatchesSrcNot")

        with open(FileName, "r") as f:
            for txt in f:
                txt = txt.rstrip('\n')
                if len(txt) < 5: next
                matches = re.search('(.+?)\s+(.+?)\s+(.+)', txt)
                if not matches:
                    if self.debug: self.cherrypy.log("[" + IPfrom + "][ALERT]: " + txt + " Wrong pattern l.243 ","MatchesSrcNot")
                    next
                zType = matches.group(1)
                zOpe = matches.group(2)
                zPattern = matches.group(3)
                if zOpe == "Yes": continue
                CountOfRules=CountOfRules+1

                if self.debug:
                    self.cherrypy.log("[127.0.0.1][DEBUG]: " + str(RuleID) + "] Type:" + zType + " Operator:" + zOpe + " Pattern:" + zPattern,"MatchesSrcNot")

                if zType == "all":
                    if self.debug: self.cherrypy.log("[" + IPfrom + "][ALERT]: ALL matches everywhere in NOT - not make sense","MatchesSrcNot")
                    continue
                # ------------------------------------------------------------------------------------------------------------------
                if zType == "dstdomain":
                    if self.debug: self.cherrypy.log(
                        "[" + IPfrom + "][DEBUG]: dstdomain no sense here, only src can be set [CONTINUE]","MatchesSrcNot")
                    continue

                # ------------------------------------------------------------------------------------------------------------------
                if zType == "src":
                    matches = re.search('([0-9\.\/]+)-([0-9\.\/]+)', zPattern)


                    if matches:
                        zRange = list(IPRange(matches.group(1), matches.group(2)))
                        if not IPAddress(IPfrom) in zRange:
                            self.cherrypy.log("[" + IPfrom + "][INFO]: NOT Match " + zPattern + " [OK]","MatchesSrcNot")
                            return True

                    if IPAddress(IPfrom) in IPNetwork(zPattern):
                        self.cherrypy.log("[" + IPfrom + "][INFO]: Match " + zPattern + " [OK]", "MatchesSrcNot")
                        return True
                    continue

                # ------------------------------------------------------------------------------------------------------------------
                if zType == "browser":
                    matches = re.search(zPattern, UserAgent, re.IGNORECASE)
                    if matches:
                        self.cherrypy.log("[" + IPfrom + "][INFO]: " + UserAgent + " Match " + zPattern + " [OK]","MatchesSrcNot")
                        return True

        if CountOfRules == 0:
            if self.debug: self.cherrypy.log("[" + IPfrom + "][INFO]: No negative object","MatchesSrc")
            return True

        if CountOfRules > 0:
            if self.debug: self.cherrypy.log("[" + IPfrom + "][INFO] "+str(CountOfRules)+" negative rule(s) but nothing detected return False")
            return False

        return True
        pass