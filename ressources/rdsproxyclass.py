#!/usr/bin/env python
import re
import random
import os
import signal
import traceback
import sys
from datetime import datetime
from datetime import timedelta
from time import mktime
from time import gmtime
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *
from adclass import *
import ldap
import logging
import sqlite3
import hashlib
import syslog
import socket
import DNS

import traceback as tb
from netaddr    import IPNetwork, IPAddress
from struct     import unpack
from struct     import pack
from select     import select


from phpserialize import serialize, unserialize


class rdsrdp:
    def __init__(self,DEBUG):
        self.DEBUG=DEBUG
        self.rule_matched=0
        self.object_id=0
        self.userid=0
        self.ClientIP=""
        self.username=""
        self.password=""
        self.LDAP_SUFFIX=""
        self.version="1.4"
        syslog.openlog("rdpproxy-auth", syslog.LOG_PID)
        self.AuthorizeTSElogin              = GET_INFO_INT("AuthorizeTSElogin")
        self.ForwardCredentials             = GET_INFO_INT("ForwardCredentials")
        self.EnableActiveDirectoryFeature   = GET_INFO_INT("EnableActiveDirectoryFeature")
        self.isArticaLicense                = GET_INFO_INT("isArticaLicense")
        self.kv                             = None
        self.unserstand_ruleid              = 0
        self.unserstand_targetid            = 0
        self.unserstand_computername        = ""
        self.unserstand_isAD                = 0
        self.rules_number                   = 0
        SET_INFO("RDPPROXY_AUTH_LIB_VERSION", self.version)
        self.LICENSE = ""

    pass

    def memcache_get(self,key):
        try:
            mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        except:
            return None

        value = mc.get(key)
        if value is None: return None
        if len(value) > 0:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: dnsfw::memcache_get %s [%s]" % (key,value))
            return value
        return None

    def memcache_set(self,key,value,timeout=3600):
        try:
            mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        except:
            return False

        try:
            mc.set(key, str(value),timeout)
        except:
            return False

        return True



    def database_maitenance(self):
        self.sqliteExec(
            "CREATE TABLE IF NOT EXISTS events ( zdate INTEGER, username TEXT, ipclient TEXT, stype INTEGER, subject TEXT)")
        self.sqliteExec("ALTER TABLE rdpproxy_sessions add username TEXT NULL", True)
        self.sqliteExec("DELETE FROM rdpproxy_sessions")

    def auto_tse_login(self,Username,Password,IPAddr,destination):
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::auto_tse_login %s AuthorizeTSElogin:%s ForwardCredentials:%s" % (Username,self.AuthorizeTSElogin,self.ForwardCredentials))
        if self.AuthorizeTSElogin == 0: return 0
        if self.ForwardCredentials==1: return self.auto_tse_login_free(IPAddr,destination)
        return self.auto_tse_db(Username,Password,IPAddr,destination)

    def auto_tse_db(self,Username,Password,IPAddr,destination):
        self.password=Password
        return self.IsRules(Username, IPAddr, destination, True)


    def BuildConnection(self,ruleid,targetid,userid,CompName,AD):
        ligne=self.QUERY_SQL("SELECT session_time FROM groups WHERE ID = '%s'" % ruleid,True)

        if ligne[0]==None:
            session_time_params=0
        else:
            if ligne[0]=='':
                session_time_params=0
            else:
                session_time_params=int(ligne[0])

        strdecon = u"2099-12-31 23:59:59"
        DefaultEndTime=self.strtime2TimeStamp(strdecon)
        EndTime=self.RuleEndTime(ruleid)
        EndTimeUser=self.UserEndTime(userid)

        if DefaultEndTime<EndTime:
            decon = datetime.datetime.fromtimestamp(EndTime)
            strdecon = datetime.datetime.strftime(decon, "%Y-%m-%d %H:%M:%S")


        if session_time_params>0:
            decon = datetime.datetime.now() + timedelta(minutes=session_time_params)
            strdecon = datetime.datetime.strftime(decon, "%Y-%m-%d %H:%M:%S")
            CutSession=self.strtime2TimeStamp(strdecon)
            if CutSession>EndTime:
                decon=datetime.datetime.fromtimestamp(EndTime)
                strdecon = datetime.datetime.strftime(decon, "%Y-%m-%d %H:%M:%S")


        CalculatedStamp=self.strtime2TimeStamp(strdecon)
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::BuildConnection Target: %s Normally disconnect at <%s>, End of Life <%s>" % (targetid,CalculatedStamp, EndTimeUser))
        if EndTimeUser<CalculatedStamp:
            strdecon = datetime.datetime.strftime(datetime.datetime.fromtimestamp(EndTimeUser), "%Y-%m-%d %H:%M:%S")

        syslog.syslog(syslog.LOG_INFO, "[INFO]: Target: %s Deconnection time: %s" % (targetid,strdecon))
        tt = datetime.datetime.strptime(strdecon, "%Y-%m-%d %H:%M:%S").timetuple()

        ligne=self.QUERY_SQL("SELECT target_login,target_password,target_host,target_device,target_port,proto_dest,mode_console,session_probe FROM `targets` WHERE ID = '%s'" % targetid,True);

        if AD==0:
            target_login=ligne[0]
            target_password=ligne[1]
            target_host=ligne[2]
            target_device=ligne[3]
            target_port=int(ligne[4])
            proto_dest=ligne[5]
        else:
            target_login=self.username
            target_password=self.password
            target_host=CompName
            target_device = CompName
            target_port=int(ligne[4])
            proto_dest=ligne[5]


        if ligne[6]==None:
            mode_console=0
        else:
            mode_console = int(ligne[6])

        if ligne[7] == None:
            session_probe=0
        else:
            session_probe=int(ligne[7])

        kv = {}

        matches=re.search('^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$',target_device)
        if matches:
            if target_device =="0.0.0.0":
                target_device=""
            else:
                target_host=target_device
                target_device=""

        timestamp0 = self.CurrentTime()
        session_id=datetime.datetime.now().strftime("%Y-%m-%d-%H-%M")+":"+str(ruleid)+"-"+str(targetid)+"-"+str(userid)
        is_rec=self.IsRuleRec(ruleid)
        if is_rec==1:
            kv[u'is_rec'] = u'1'
            kv['record_filebase'] = str(ruleid)+"-"+str(targetid)+"-"+str(userid)
            kv['record_subdirectory'] = str(timestamp0)

        if is_rec==0:
            kv[u'is_rec'] = u'0'
        log_filename = "session-log-%s.log" % session_id
        kv[u'login'] = target_login
        kv[u'proto_dest'] = proto_dest
        kv[u'target_port'] = target_port
        kv[u'session_id'] = session_id
        kv[u'module'] = proto_dest
        if mode_console == 1: kv[u'mode_console'] = u"allow"
        if mode_console == 0: kv[u'mode_console'] = u"forbid"
        kv[u'timeclose'] = int(mktime(tt))
        kv[u'target_password']=target_password
        kv[u'target_login'] = target_login
        kv[u'target_host']=target_host
        kv[u'target_device'] = target_device
        kv[u'session_log_path'] = log_filename
        #kv[u'session_log_path']=None
        #kv[u'session_log_path'] = u'/var/log/rdpproxy'
        if session_probe == 0: kv[u'session_probe'] = u'0'
        if session_probe == 1: kv[u'session_probe'] = u'1'

        return kv


    def UnderstandSelector(self,selected_device,selector,MAGICASK):
        self.unserstand_isAD=0
        self.unserstand_computername =""
        if selected_device == MAGICASK: return False
        matches = re.search('^([0-9]+)\.([0-9]+)\)', selected_device)
        if matches:
            self.unserstand_ruleid = int(matches.group(1))
            self.unserstand_targetid = int(matches.group(2))
            return True



        matches = re.search('^([0-9]+)\.([0-9]+)\.(.+?)\)', selected_device)
        if matches:
            self.unserstand_ruleid = int(matches.group(1))
            self.unserstand_targetid = int(matches.group(2))
            self.unserstand_computername = matches.group(3)
            self.unserstand_isAD = 1
            return True

        if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: selected_device: rdsrdp::UnderstandSelector [%s] selector: [%s] [FALSE]" % (selected_device, selector))
        return False



    def BuildSelector(self,Rulez):
        target_login=[]
        target_device=[]
        proto_dest=[]
        Already={}


        for ruleid in Rulez:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::BuildSelector building for rule ID [%s]" % ruleid )
            try:
                list_gpids=self.QUERY_SQL("SELECT targetid FROM link_target WHERE gpid = '%s'" % ruleid)
                if list_gpids is None: continue

                for row in list_gpids:
                    targetid=row[0]
                    if targetid in Already: continue
                    Already[targetid]=True
                    if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::BuildSelector open target id %s" % targetid)
                    ligne = self.QUERY_SQL("SELECT alias,designation,proto_dest,enabled,target_port,DontResolve FROM targets WHERE ID = '%s'" % targetid,True);
                    alias = ligne[0]
                    designation = ligne[1]
                    RdpORVNC = ligne[2]
                    enabled=ligne[3]
                    target_port=ligne[4]
                    DontResolve=ligne[5]

                    enabled = int(enabled)
                    if enabled == None: enabled = 0
                    if enabled == 0: continue
                    if designation == None: designation="designation"
                    if alias == None: alias="Alias"

                    matches = re.match('(CN|OU|DC)=.*?,', alias)
                    if matches:
                        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::BuildSelector Found Active Directory DN an alias [%s]" % alias)
                        ad=ADLDAP("rdpproxy-auth")
                        List=ad.ListComputerFromGroupDN(alias)
                        if List is None: continue
                        for tempComp in List:
                            matches=re.match('(.*?)@(.*)',tempComp)
                            CompName=matches.group(1)
                            CompDesc=matches.group(2)
                            prefix=''
                            if DontResolve == 0:
                                CompIP=self.ip_resolveComputer(CompName ,ad.LDAP_SERVER)
                                if CompIP is None: continue
                                if CompIP =='127.0.0.1': continue
                                if not self.ip_CheckRDPPort(CompIP,target_port): continue
                                prefix = u'%s.%s.%s' % (ruleid, targetid, CompIP)


                            if len(prefix) ==0:  prefix = u'%s.%s.%s' % (ruleid, targetid,CompName)
                            proto_dest.append(RdpORVNC)
                            target_login.append(prefix + ") " + CompName)
                            target_device.append(CompDesc)
                        continue

                    prefix=u'%s.%s' % (ruleid,targetid)
                    proto_dest.append(RdpORVNC)
                    if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::BuildSelector open target %s proto %s" % (alias,ligne[2]))
                    target_login.append(prefix+") "+alias)
                    target_device.append(designation)
            except Exception as e:
                syslog.syslog(syslog.LOG_INFO, "[ERROR]: Exception in loop: %s" % e)
                syslog.syslog(syslog.LOG_INFO, "[ERROR]: %s" % tb.format_exc())

        self.rules_number=len(target_login)
        implode_target_login=u"\x01".join(target_login)
        implode_target_device=u"\x01".join(target_device)
        implode_proto_dest=u"\x01".join(proto_dest)
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::BuildSelector implode_target_device = [%s]" % implode_target_device)



        selector_data = {
            u'target_login': implode_target_login,
            u'target_device': implode_target_device,
            u'proto_dest': implode_proto_dest,
            u'selector_number_of_pages': u"0",
            # No lines sent, reset filters
            u'selector_group_filter': u"",
            u'selector_device_filter': u"",
            u'selector_proto_filter': u"",
            u'ip_client': self.ClientIP,
        }
        self.kv=selector_data
        return selector_data

    def ip_resolvebyDNS(self,computername,dnsserver=""):
        if len(computername)<3: return None
        reqobj = DNS.Request(server=dnsserver, timeout=2)
        try:
            x = reqobj.req(computername, qtype=DNS.Type.A)
        except:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: rdsrdp::ip_resolvebyDNS %s [ns:%s] DNS Error" % (computername,dnsserver))
            return None

        status = x.header['status']
        if status == "NXDOMAIN":return None
        if len(x.answers) == 0: return None
        return x.answers[0]['data']

    def ip_resolveComputer(self,computername,dnsserver=""):

        if len(dnsserver)>0:
            try:
                CompIP = self.ip_resolvebyDNS(computername,dnsserver)
                if CompIP is not None: return CompIP
                syslog.syslog(syslog.LOG_INFO, "[ERROR]: rdsrdp::ip_resolveComputer %s cannot resolve %s" % (dnsserver,computername))
            except:
                syslog.syslog(syslog.LOG_INFO, "[ERROR]: rdsrdp::ip_resolveComputer %s" % tb.format_exc())

        try:
            CompIP = socket.gethostbyname(computername)
            return CompIP
        except Exception as e:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: Error resolving DNS for {}: {}".format(computername, e))
            return None

    def check_rules(self,Username,Password,IPAddr):
        zRules      = []
        self.userid = self.login_ok(Username,Password,IPAddr)
        if self.userid==0:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::check_rules %s login_ok return 0 [FALSE]" % Username)
            return zRules

        return self.GetRules(Username, IPAddr)

    def GetRules(self,userid,IPAddr):
        zRules      = []
        rules       = self.RulesFromIP(IPAddr)
        if len(rules) == 0:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::GetRules %s RulesFromIP return 0 rule [FALSE]" % IPAddr)
            return zRules

        for ruleid in rules:

            if not self.IsRuleMatchesUserid(ruleid, self.userid):
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::GetRules userid:%s not in policy [%s]", (userid,ruleid))
                continue


            if not self.IsRuleMatchesTime(ruleid):
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::GetRules Policy %s did not matches Time" % ruleid)
                continue

            if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::GetRules Policy %s matches all criterias" % ruleid)
            zRules.append(ruleid)

        if self.DEBUG:
            CountOfRules=len(zRules)
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::GetRules %s Policies matches all criterias" % CountOfRules)

        return zRules




    def auto_tse_login_free(self, IPAddr,destination):
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::auto_tse_login find rule(s) that matches src:%s to %s" % (IPAddr,destination))
        rules=self.RulesFromIP(IPAddr)
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::auto_tse_login %s Rule(s)" % len(rules))
        if len(rules)==0: return 0

        for ruleid in rules:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::auto_tse_login is Rule %s matches Time ?" % ruleid)
            if not self.IsRuleMatchesTime(ruleid): continue
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::auto_tse_login is Rule %s matches Time [OK] lets Check %s" % (ruleid,destination))
            desintation_id=self.IsRuleMatchesDestination(ruleid,destination)
            if desintation_id > 0:
                self.rule_matched = ruleid
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::auto_tse_login is Rule %s matches all filters for destination %s" % (ruleid,desintation_id))
                return desintation_id


        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::auto_tse_login no rule matches filters")
        return 0

    def IsRules(self,Username,IPAddr,destination,return_computerid=False):
        Password        = self.password
        self.userid     = self.login_ok(Username, Password, IPAddr)
        target          = destination.lower()
        if self.userid==0:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::IsRules Closing connection because login_ok(%s,***) return 0" % Username)
            return 0

        rules = self.RulesFromIP(IPAddr)
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRules %s Rule(s)" % len(rules))
        if len(rules) == 0: return 0
        for ruleid in rules:

            if not self.IsRuleMatchesUserid(ruleid, self.userid):
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::IsRules Check: userid:%s not in policy [%s]", (userid,ruleid))
                continue

            if not self.IsRuleMatchesTime(ruleid):
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::IsRules Check: policy [%s] dot not matches time" % ruleid)
                continue

            computer_id=self.IsRuleMatchesDestination(ruleid,target)
            if computer_id==0:
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::IsRules Check: policy [%s] dot not matches computer %s" % (ruleid,target))
                continue

            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRules Check: policy [%s] matches all criterias" % ruleid)
            self.rule_matched=ruleid
            if return_computerid: return computer_id
            return ruleid

        if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::IsRules Check: no policy matches criterias")
        return 0





    def RulesFromIP(self,ip_client):
        zRules=[]
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        for row in c.execute('SELECT gpid,pattern FROM networks'):
            ruleid=row[0]
            Network=row[1]

            if Network=="0.0.0.0/0":
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::RulesFromIP 0.0.0.0/0 matches all for rule %s" % ruleid)
                zRules.append(ruleid)
                continue

            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::RulesFromIP checking IP %s against %s" % (ip_client,Network))

            if  IPAddress(ip_client) in IPNetwork(Network):
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::RulesFromIP %s matches rule %s" % (Network,ruleid))
                zRules.append(ruleid)
                continue

        if conn: conn.close()
        return zRules

    def RuleEndTime(self,ruleid):
        thisXMas = datetime.datetime.now()
        CurrentDay=thisXMas.weekday()
        timestamp0 = self.CurrentTime()
        rows=self.QUERY_SQL("SELECT tday,thour1,thour2 FROM timeline WHERE gpid = '%s'" % ruleid)

        for row in rows:
            tday=row[0]
            thour1=row[1]
            thour2=row[2]
            if tday!=CurrentDay:continue

            timestamp1 =self.HourToTimeStamp(thour1)
            timestamp2 =self.HourToTimeStamp(thour2)
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO,'[DEBUG]: rdsrdp::RuleEndTime %s < %s > %s' % (timestamp1, timestamp0, timestamp2))

            if timestamp0 <  timestamp1: continue;
            if timestamp0 >  timestamp2: continue;

            return timestamp2

        return 99999999999
        pass

    def ip_CheckRDPPort(self,ipaddr,target_port):
        a_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        a_socket.settimeout(1.0)
        location = (ipaddr, target_port)
        try:
            result_of_check = a_socket.connect_ex(location)
        except:
            self.logging.error('Error connection {}: {}'.format(ipaddr, e))
            return False


        if result_of_check == 0:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO,'[DEBUG]: rdsrdp::ip_CheckRDPPort [{}]: Port {} is Open [OK]'.format(ipaddr,target_port))
            a_socket.close()
            return True

        if self.DEBUG: syslog.syslog(syslog.LOG_INFO,'[DEBUG]: rdsrdp::ip_CheckRDPPort ERROR [{}]: Port {} is Closed'.format(ipaddr,target_port))
        a_socket.close()
        return False


    def UserExists(self,username,ip_client):
        timestamp0 = self.CurrentTime()
        results=self.QUERY_SQL("SELECT ID,password,endoflife FROM members WHERE username = '%s'" % username,True)
        self.username=username

        if results==None:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::UserExists " + username + " [" + ip_client + "] no such member (None)")
            if self.is_activedirectory():
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::UserExists Active Directory Enabled, try to found  " + username)
                ad=ADLDAP("rdpproxy-auth")
                if ad.UserExistsInAll(username):
                    if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::UserExists " + username+" [OK]")
                    return True

            syslog.syslog(syslog.LOG_INFO,'[ERROR]: rdsrdp::UserExists [AUTH]: ' + username + ' [' + ip_client + '] no such member (None)')
            self.historySave("No such member", username, ip_client, 0)
            return False

        ID=int(results[0])
        if ID==0:
            if self.is_activedirectory():
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::UserExists Active Directory Enabled, try to found  " + username)
                ad=ADLDAP("rdpproxy-auth")
                if ad.UserExistsInAll(username):
                    if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::UserExists " + username+" [OK]")
                    return True

            syslog.syslog(syslog.LOG_INFO, '[AUTH]: '+username +' ['+ip_client+'] no such member (Index)')
            self.historySave("No such member", username, ip_client, 0)
            return False

        endoflife=results[2]
        if timestamp0 > endoflife:
            syslog.syslog(syslog.LOG_INFO, '[AUTH]: ' + username + ' [' + ip_client + '] End of life')
            self.historySave("End of life", username, ip_client, 0)
            return False

        return True


    def UserEndTime(self,userid):
        results = self.QUERY_SQL("SELECT endoflife FROM members WHERE ID = '%s'" % userid,True)
        if results is None: return 0
        endoflife=int(results[0])
        return endoflife

    def is_activedirectory(self):
        if self.EnableActiveDirectoryFeature==0:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::is_activedirectory EnableActiveDirectoryFeature == 0")
            return False
        if self.NumberOfAdGroups()==0:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::is_activedirectory NumberOfAdGroups == 0")
            return False
        return True


    def IsRuleRec(self,ruleid):
        results=self.QUERY_SQL("SELECT user_rec FROM groups WHERE ID = '%s'" % ruleid,True)
        if results is None: return 0
        return int(results[0])


    def session_check(self,rdp_instance):
        FOUND_STATUS = False
        userid=self.userid
        reporting = rdp_instance.shared.get(u'reporting')
        session_id = rdp_instance.shared.get(u'session_id')
        UserName = rdp_instance.shared.get(u'login')
        psid = rdp_instance.shared.get(u'psid')
        ip_client = rdp_instance.shared.get(u'ip_client')
        target_login = rdp_instance.shared.get(u'target_login')
        disconnect_reason_ack = rdp_instance.shared.get(u'disconnect_reason_ack')
        module = rdp_instance.shared.get(u'module')
        database_id=0;
        pkill=0

        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: " + UserName + " reporting <%s>" % reporting)
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: " + UserName + " session_id <%s>" % session_id)
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: " + UserName + " psid <%s>" % psid)
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: " + UserName + " ip_client <%s>" % ip_client)
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: " + UserName + " target_login <%s>" % target_login)
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s disconnect_reason_ack <%s>" % (UserName,disconnect_reason_ack))
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s module <%s>" % (UserName,module))
        self.ClientIP=ip_client
        self.username=UserName

        matches = re.search("OPEN_SESSION_SUCCESSFUL", reporting)
        if matches:
            try:
                database_id, pkill = self.get_database_id(psid)
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: session %s for user %s pkill = <%s> database ID=%s" % (session_id,UserName,pkill,database_id))
            except:
                syslog.syslog(syslog.LOG_INFO, "[ERROR] 210 %s " % tb.format_exc())

            if self.isArticaLicense == 0:
                connexions = self.session_count()
                if connexions > 5:
                    syslog.syslog(syslog.LOG_INFO, "[ERROR] Community edition limited to 5 connections max")
                    self.historySave("License exceed in community mode", self.username, ip_client, 1)
                    pkill=1


            if pkill == 1:
                syslog.syslog(syslog.LOG_INFO,"[INFO] Got order to kill Session %s/%s" % (psid, database_id))
                self.session_delete(database_id, psid)
                # 999 == self.proxy_conx.close()
                return 999

            if pkill == 0:
                strtime = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                intTime = self.strtime2TimeStamp(strtime)
                if database_id > 0:
                    self.session_update(psid, intTime, database_id)
                    return 0

                if database_id == 0:
                    syslog.syslog(syslog.LOG_INFO, "[INFO] Create new session session_id=%s psid=%s"  % (session_id,psid))
                    self.session_create(intTime, session_id, psid, ip_client, userid, target_login,UserName)
                    return 0

            matches = re.search("CLOSE_SESSION_SUCCESSFUL", reporting)
            if matches:
                database_id, pkill = self.get_database_id(psid)
                self.historySave("Close RDP session", UserName, ip_client, 2)
                self.session_delete(database_id, psid)
                syslog.syslog(syslog.LOG_INFO, "[INFO] Session %s/%s was stopped" % (psid, database_id))
                return 0

            syslog.syslog(syslog.LOG_INFO, "[ERROR] %s is not understood !" % reporting)
            return 0

    def session_count(self):
        sql="SELECT count(*) as tcount from rdpproxy_sessions"
        results = self.QUERY_SQL(sql,true)
        if results is None: return 99999999
        return int(results[0])


    def session_create(self,intTime, session_id, psid, ip_client, userid, target_login,username=''):
        try:
            prefix="INSERT INTO rdpproxy_sessions (created,xtime,sessionid,psid,ip_client,userid,target_login,pkill,username)";
            sql="%s VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s')" % (prefix,intTime, intTime, session_id, psid, ip_client, userid, target_login, 0,username)
        except:
            syslog.syslog(syslog.LOG_INFO, "[ERROR] session_create %s " % tb.format_exc())
            self.historySave("Fatal while Create Session", self.username, ip_client, 1)
            return False

        if self.sqliteExec(sql):
            self.historySave("Open RDP session", self.username, ip_client, 1)
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::session_create %s" % sql)
        else:
            self.historySave("Create Session failed", self.username, ip_client, 1)

    def session_delete(self,database_id,psid):
        self.sqliteExec("DELETE FROM rdpproxy_sessions WHERE psid ='%s'" % psid)
        self.sqliteExec("DELETE FROM rdpproxy_sessions WHERE ID ='%s'" % database_id)
        self.historySave("KILL RDP session", self.username, self.ClientIP, 1)

    def session_update(self,psid,intTime,database_id):
        self.sqliteExec("UPDATE rdpproxy_sessions SET psid ='%s', xtime ='%s' WHERE ID='%s'" % (psid, intTime, database_id))



    def login_to_ad(self,username,password):
        if not self.is_activedirectory(): return 0

        if not self.is_auth_to_ad(username, password):
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::login_to_ad is_auth_to_ad(%s,***) return [FALSE]" % username);
            return 0


        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::login_to_ad return object id [%s]" % self.object_id);
        return self.object_id

    def is_auth_to_ad(self,username,password):
        ActiveDirectoryConnections = unserialize(GET_INFO_STR("ActiveDirectoryConnections"))
        ActiveDirectoryConnectionsCount=len(ActiveDirectoryConnections)
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::is_auth_to_ad [%s] Connection(s) defined" % ActiveDirectoryConnectionsCount);

        if ActiveDirectoryConnectionsCount == 0: return False

        try:
            for Index in ActiveDirectoryConnections:
                ad = ADLDAP("rdpproxy-auth")
                if self.DEBUG: ad.DEBUG=True
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::is_auth_to_ad %s Testing connection index [%s]" % (username,Index))
                if not ad.TestAuthenticate(Index,username, password): continue
                userdn=ad.get_user_dn(username,Index)
                self.LDAP_SUFFIX=ad.ldap_suffix
                self.LdapIndex=Index
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::is_auth_to_ad Authenticated [OK] %s DN:[%s]" % (username,userdn))

                if not self.GetObjectsFromSuffix(userdn):
                    if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::is_auth_to_ad Get Groups from ACLs with [%s] [FAILED]" % self.LDAP_SUFFIX)
                    continue

                self.username=username
                self.password=password
                return True
        except:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::is_auth_to_a Crash while iterate connections...%s" % tb.format_exc())
        return False


    def login_ok(self,username,password,ip_client):
        sql="SELECT ID,password,endoflife FROM members WHERE username = '%s'" % username
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::login_ok Authenticate user:%s with ip %s" % (username,ip_client))
        results=self.QUERY_SQL(sql,True)

        if results is None:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::login_ok %s Not a local user trying Active Directory" % username)
            ID=self.login_to_ad(username,password)
            if ID > 0:
                if self.DEBUG:syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::login_ok(1) %s [%s] Active Directory return %s" % (username, ip_client,ID))
                return ID

            syslog.syslog(syslog.LOG_INFO, "[ERROR]: [AUTH]: %s [%s] no such member (result None)" % (username,ip_client))
            self.historySave("No such member",username,ip_client,0)
            return 0

        ID=int(results[0])
        if ID==0:
            ID=self.login_to_ad(username,password)
            if ID > 0:
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]:rdsrdp::login_ok(2) %s [%s] Active Directory return %s" % (username, ip_client, ID))
                return ID
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: [AUTH]: %s [%s] no such member (result 0)" % (username,ip_client))
            self.historySave("No such member", username, ip_client, 0)
            return 0

        endoflife   =results[2]
        timestamp0  = self.CurrentTime()

        if timestamp0 > endoflife:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: [AUTH]: %s [%s] End of life" % (username, ip_client))
            self.historySave("Member End of Life", username, ip_client, 0)
            return 0


        password2=results[1]
        password1 = hashlib.md5(password.encode('utf8')).hexdigest()

        if password1==password2:return ID
        self.historySave("Bad password", username, ip_client, 0)
        syslog.syslog(syslog.LOG_INFO, "[ERROR]: [AUTH]: %s [%s] Bad password" % (username, ip_client))
        return 0

    def get_database_id(self,psid):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        results=self.QUERY_SQL("SELECT ID,pkill FROM rdpproxy_sessions WHERE psid ='%s'" % psid,True)
        if results is None: return 0,0
        try:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: get_database_id PSID %s row ID %s" % (psid,results[0]))
            database_id = int(results[0])
        except:
            return 0,0

        try:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO,
                                         "[DEBUG]: get_database_id PSID %s row pkill [%s]" % (psid, results[1]))
            pkill = int(results[1])
        except:
            return 0,0

        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: get_database_id PSID %s row ID %s killed=%s" % (psid,database_id,pkill))
        return database_id,pkill

    def QUERY_SQL(self,sql,fetchone=False):
        rows=None
        try:
            conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
            conn.text_factory = lambda b: b.decode(errors='ignore')
        except:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: SQL %s" %  tb.format_exc())
            return None

        cur = conn.cursor()
        try:
            cur.execute(sql)
            if fetchone: rows = cur.fetchone()
            if not fetchone: rows = cur.fetchall()

        except Error as e:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: SQL %s" % e)
            conn.close()
            return None

        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::QUERY_SQL fetchone = %s [%s] [OK]" % (fetchone,sql))
        conn.close()
        return rows


    def IsRuleMatchesDestination(self,ruleid,target):

        s_target    = target.lower()
        results= self.QUERY_SQL("SELECT targetid FROM link_target WHERE gpid='%s'" % ruleid)
        if results is None: return 0

        for row in results:
            targetid        = row[0]
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRuleMatchesDestination checks target %s" % targetid)
            alias,target_host,target_device,enabled,port = self.TargetInfos(targetid)
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRuleMatchesDestination checks %s against enable=%s, %s,%s,%s in the order listed" % (enabled,s_target,target_device,target_host,alias))
            if enabled == 0: continue
            if s_target == target_device:
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRuleMatchesDestination %s matches %s" % (s_target,target_device))
                return targetid
            if s_target == target_host:
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::IsRuleMatchesDestination %s matches %s" % (s_target, target_host))
                return targetid
            if s_target == alias:
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::IsRuleMatchesDestination %s matches %s" % (s_target, alias))
                return targetid
        return 0

    def ResolveComputer(self,target_id):
        alias, target_host, target_device, enabled, port = self.TargetInfos(target_id)
        if enabled == 0: return None,None,0
        matches=re.search('^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$',target_device)
        if matches:
            if target_device =="0.0.0.0":
                target_device=""
            else:
                target_host=target_device
                target_device=""

        return target_host,target_device,port

    def GetSessionTime(self,ruleid):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor();
        c.execute("SELECT session_time FROM groups WHERE ID = '%s'" % ruleid);
        ligne = c.fetchone()
        if ligne[0] == None:
            session_time_params = 0
        else:
            if ligne[0] == '':
                session_time_params = 0
            else:
                session_time_params = int(ligne[0])

        return session_time_params


    def BuildConnectionMatched(self,targetid,userid=0,ForceUsername=None,ForcePassword=None):
        ruleid=self.rule_matched
        list_params = []
        kv          = {}
        AD          = 0
        session_time_params = self.GetSessionTime(ruleid)
        strdecon = u"2099-12-31 23:59:59"
        DefaultEndTime=self.strtime2TimeStamp(strdecon)+60
        EndTimeUser = DefaultEndTime
        EndTime=self.RuleEndTime(ruleid)
        if userid>0:
            EndTimeUser=self.UserEndTime(userid)

        if DefaultEndTime<EndTime:
            decon = datetime.datetime.fromtimestamp(EndTime)
            strdecon = datetime.datetime.strftime(decon, "%Y-%m-%d %H:%M:%S")


        if session_time_params>0:
            decon = datetime.datetime.now() + timedelta(minutes=session_time_params)
            strdecon = datetime.datetime.strftime(decon, "%Y-%m-%d %H:%M:%S")
            CutSession=self.strtime2TimeStamp(strdecon)
            if CutSession>EndTime:
                decon=datetime.datetime.fromtimestamp(EndTime)
                strdecon = datetime.datetime.strftime(decon, "%Y-%m-%d %H:%M:%S")


        CalculatedStamp=self.strtime2TimeStamp(strdecon)
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::BuildConnectionMatched %s Normally disconnect at <%s>, End of Life <%s>" % (targetid,CalculatedStamp, EndTimeUser))
        if EndTimeUser<CalculatedStamp:
            strdecon = datetime.datetime.strftime(datetime.datetime.fromtimestamp(EndTimeUser), "%Y-%m-%d %H:%M:%S")

        if self.DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: rdsrdp::BuildConnectionMatchedTarget: %s Deconnection time: %s" % (targetid,strdecon))
        tt = datetime.datetime.strptime(strdecon, "%Y-%m-%d %H:%M:%S").timetuple()
        sql="SELECT target_login,target_password,target_host,target_device,target_port,proto_dest,mode_console,session_probe FROM `targets` WHERE ID = '%s'" % targetid
        ligne=self.QUERY_SQL(sql,True)
        if ligne is None:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: rdsrdp::BuildConnectionMatchedTarget unable to retreive information from target id %s" % targetid)
            return kv



        if AD==0:
            target_login=ligne[0]
            target_password=ligne[1]
            target_host=ligne[2]
            target_device=ligne[3]
            target_port=int(ligne[4])
            proto_dest=ligne[5]
        else:
            target_login=self.username
            target_password=self.password
            target_host=CompName
            target_device = CompName
            target_port=int(ligne[4])
            proto_dest=ligne[5]


        if ligne[6]==None:
            mode_console=0
        else:
            mode_console = int(ligne[6])

        if ligne[7] == None:
            session_probe=0
        else:
            session_probe=int(ligne[7])



        matches=re.search('^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$',target_device)
        if matches:
            if target_device =="0.0.0.0":
                target_device=""
            else:
                target_host=target_device
                target_device=""

        timestamp0 = self.CurrentTime()
        session_id=datetime.datetime.now().strftime("%Y-%m-%d-%H-%M")+":"+str(ruleid)+"-"+str(targetid)+"-"+str(userid)
        is_rec=self.IsRuleRec(ruleid)
        if is_rec==1:
            kv[u'is_rec'] = u'1'
            kv['record_filebase'] = str(ruleid)+"-"+str(targetid)+"-"+str(userid)
            kv['record_subdirectory'] = str(timestamp0)

        if is_rec==0:
            kv[u'is_rec'] = u'0'

        if ForceUsername is not None: target_login = ForceUsername
        if ForcePassword is not None: target_password = ForcePassword
        log_filename = "session-log-%s.log"% session_id

        kv[u'login'] = target_login
        kv[u'proto_dest'] = proto_dest
        kv[u'target_port'] = target_port
        kv[u'session_id'] = session_id
        kv[u'module'] = proto_dest
        if mode_console == 1: kv[u'mode_console'] = u"allow"
        if mode_console == 0: kv[u'mode_console'] = u"forbid"
        kv[u'timeclose'] = int(mktime(tt))
        kv[u'target_password']=target_password
        kv[u'target_login'] = target_login
        kv[u'target_host']=target_host
        kv[u'target_device'] = target_device
        kv[u'real_target_device']=target_device
        kv[u'session_log_path'] = log_filename
        if session_probe == 0: kv[u'session_probe'] = u'0'
        if session_probe == 1: kv[u'session_probe'] = u'1'
        if len(list_params) > 3:
            kv[u'alternate_shell'] = list_params[1]
            kv[u'shell_working_directory'] = list_params[2]
            kv[u'target_application'] = list_params[1]
            kv[u'shell_arguments'] = list_params[3]

        return kv

    def TargetInfos(self,target_id):
        sql="SELECT alias,target_host,target_device,enabled,target_port FROM targets WHERE ID = '%s'" % target_id
        try:
            row             = self.QUERY_SQL(sql,True)
            alias           = row[0].lower()
            target_host     = row[1].lower()
            target_device   = row[2].lower()
            enabled         = int(row[3])
            target_port     = int(row[4])
        except:
            syslog.syslog(syslog.LOG_INFO,"[ERROR]: rdsrdp::TargetInfos %s" %  tb.format_exc())
            conn.close()
            return None,None,None,None,None

        if target_port == 0: target_port = 3389
        return alias,target_host,target_device,enabled,target_port


    def IsRuleMatchesUserid(self,ruleid,userid):
        results = self.QUERY_SQL("SELECT ID FROM link_members WHERE userid = '%s' AND gpid = '%s'" % (userid,ruleid),True)
        if results == None:
            return False
        return True
        pass

    def IsRuleMatchesTime(self,ruleid):
        weekDays = ("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday")
        thisXMas = datetime.datetime.now()
        CurrentDay=thisXMas.weekday()
        CurrentDayText=weekDays[CurrentDay]
        if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRuleMatchesTime Current day %s" % CurrentDayText)
        RulesCount=0
        FinalResult=False
        timestamp0 = self.CurrentTime()

        results = self.QUERY_SQL("SELECT tday,thour1,thour2 FROM timeline WHERE gpid = '%s'" % ruleid)
        if results is None: return True

        for row in results:
            RulesCount+=1
            tday=row[0]
            thour1=row[1]
            thour2=row[2]
            tdayText=weekDays[tday]
            if tday!=CurrentDay:
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRuleMatchesTime FAILED: Current day %s <> %s" % (CurrentDayText,tdayText))
                continue

            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRuleMatchesTime OK day %s == %s" % (CurrentDayText, tdayText))
            timestamp1 =self.HourToTimeStamp(thour1)
            timestamp2 =self.HourToTimeStamp(thour2)
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRuleMatchesTime %s < %s > %s" % (timestamp1, timestamp0, timestamp2))

            if timestamp0 <  timestamp1:
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRuleMatchesTime FAILED:%s < %s" % (timestamp0,timestamp1))
                continue
            if timestamp0 >  timestamp2:
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRuleMatchesTime FAILED: %s > %s" % (timestamp0, timestamp2))
                continue;
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRuleMatchesTime  SUCCESS !")
            return True

        if RulesCount==0:
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::IsRuleMatchesTime no time object defined, assume True")
            return True
        return FinalResult
        pass

    def GetObjectsFromSuffix(self,userdn):

        suffix=self.LDAP_SUFFIX
        results=self.QUERY_SQL("SELECT ID,username,endoflife FROM members WHERE username LIKE '%"+suffix+"'")
        if results is None: return False

        adldap=ADLDAP("rdpproxy-auth")
        adldap.DEBUG=self.DEBUG
        timestamp0 = self.CurrentTime()
        for line in results:
            ID=line[0]
            username=line[1]
            endoflife=line[2]
            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::GetObjectsFromSuffix Found "+username)

            if timestamp0 > endoflife:
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::GetObjectsFromSuffix " + username+ " End of life, continue")
                continue

            if not adldap.dngroupMatchesDN(self.LdapIndex,username,userdn):
                if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::GetObjectsFromSuffix: [%s] not inside [%s]" % (username,userdn))
                continue

            if self.DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: rdsrdp::GetObjectsFromSuffix: [OK] Found Group ID %s" % ID)
            self.object_id=ID
            return True

        self.object_id =0
        return False

    def NumberOfAdGroups(self):
        results = self.QUERY_SQL("SELECT count(*) as tcount FROM members WHERE ADGROUP = '1'",True)
        if results is None: return 0
        return int(results[0])

    def sqliteExec(self,sql,NoError=False):
        try:
            conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        except Error as e:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: sqliteExec SQL %s" % e)
            return False

        c = conn.cursor()
        try:
            if self.DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: sqliteExec [%s]" % sql)
            c.execute(sql)
            conn.commit()
        except:
            if self.DEBUG:
                syslog.syslog(syslog.LOG_INFO, "[ERROR]: sqliteExec SQL %s" % tb.format_exc())
                if conn: conn.close()
                return False

            if not NoError: syslog.syslog(syslog.LOG_INFO, "[ERROR]: sqliteExec SQL %s" % tb.format_exc())
            if conn: conn.close()
            return False

        if conn: conn.close()
        return True

    def CurrentTime(self):
        thisXMas = datetime.datetime.now()
        strtime = datetime.datetime.strftime(thisXMas, "%Y-%m-%d %H:%M:%S")
        return self.strtime2TimeStamp(strtime)

    def HourToTimeStamp(self,strhour):
        thisXMas = datetime.datetime.now()
        TimePrefix = datetime.datetime.strftime(thisXMas, "%Y-%m-%d")
        strtime = TimePrefix + " " + strhour
        return self.strtime2TimeStamp(strtime)

    def strtime2TimeStamp(self,strtime):
        tt = datetime.datetime.strptime(strtime, "%Y-%m-%d %H:%M:%S").timetuple()
        return int(mktime(tt))


    def historySave(self,text,username,ipclient,level):
        timestamp0 = self.CurrentTime()
        sql="INSERT INTO events (zdate,username,ipclient,stype,subject) VALUES ("+str(timestamp0)+",'"+username+"','"+ipclient+"',"+str(level)+",'"+text+"')"
        self.sqliteExec(sql)