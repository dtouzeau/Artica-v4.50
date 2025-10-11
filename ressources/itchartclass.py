#!/usr/bin/env python
from unix import *
import os.path
import datetime
import syslog
from classartmem import *
from activedirectoryclass import *
from goldlic import *
from netaddr import IPNetwork, IPAddress
from phpserialize import serialize, unserialize

class ITCHARTENGINE:
    
    def __init__(self):
        self.SquidGuardIPWeb=''
        self.SquidGuardIPWebSSL=''
        self.memoryRules=[]
        self.logging=object
        self.output=''
        self.MemoryUsers={}
        self.PROXY_IP=''
        self.sitename=''
        self.ITChartVerbose = GET_INFO_INT("ITChartVerbose")
        self.mem = art_memcache("/var/run/itcharter.sock")
        gold = isGoldkey()
        if gold.is_corp_license(): self.license = True
        self.count_of_lic = 0
        self.count_sleep = 0
        self.NetworksExcludeLine = GET_INFO_STR("ITChartNetworkExclude")
        self.RedirectPage = GET_INFO_STR("ITChartRedirectURL")
        self.RedirectPage = self.RedirectPage.lower()
        self.UseSSL = GET_INFO_INT("ITChartAllowSSL")
        if self.ITChartVerbose==1: self.sendlogs("ITChart Class Loading verbose=%s" %  self.ITChartVerbose)
        self.InScreen= False

    pass

    def isNetExcluded(self,NetworksExcludeLine, ipaddr, mac):
        if NetworksExcludeLine is None:
            if self.ITChartVerbose == 1: self.sendlogs("%s: [DEBUG]: isNetExcluded None, aborting" % self.sitename)
            return False
        if len(NetworksExcludeLine) < 3:
            if self.ITChartVerbose == 1: self.sendlogs("%s: [DEBUG]: isNetExcluded Not configured, aborting" % self.sitename)
            return False
        sNets = NetworksExcludeLine.splitlines()
        for cdir in sNets:
            if self.ITChartVerbose == 1: self.sendlogs("%s: [DEBUG]: isNetExcluded checking %s against %s %s" (self.sitename,cdir,ipaddr,mac))
            if mac == cdir: return True
            if ipaddr == cdir: return True
            try:
                if IPAddress(ipaddr) in IPNetwork(cdir):
                    if self.ITChartVerbose == 1: self.sendlogs("%s: [DEBUG]: isNetExcluded checking %s matches %s" % (self.sitename,cdir, ipaddr))
                    return True
            except:
                continue
        return False

    def sendlogs(self,text):
        ItChartLog=syslog
        ItChartLog.openlog("ItCharter", syslog.LOG_PID)
        ItChartLog.syslog(syslog.LOG_INFO, text)

    
    def ChartThis(self,ipaddr,mac,username,method,sitename):
        self.sitename=sitename
        if self.RedirectPage.find(sitename) > -1:
            if self.ITChartVerbose==1:self.sendlogs("%s: [DEBUG]: <%s> is the redirected page, no sense to block it L.68" % (ipaddr,sitename))
            return False

        if method=="CONNECT":
            if self.ITChartVerbose==1:self.sendlogs("%s: %s: [DEBUG] [%s] method excluded, PASS L.72" % (ipaddr,sitename,method))
            return False

        if self.isNetExcluded(self.NetworksExcludeLine, ipaddr, mac):
            if self.ITChartVerbose==1:self.sendlogs("%s: [DEBUG] isNetExcluded report True, PASS L.76" % ipaddr)
            return False

        KeyAccount=self.FindKeyAccount(username,ipaddr,mac)
        if self.ITChartVerbose==1:self.sendlogs("%s: [DEBUG] [%s]: username=%s ipaddr=%s mac=%s sitename=%s method=%s ->get_itcharts_ids()" % (sitename,KeyAccount,username, ipaddr, mac, sitename, method))
        itcharts_ids = self.get_itcharts_ids()
        if len(itcharts_ids)==0:
            if self.ITChartVerbose==1:self.sendlogs("%s: [DEBUG] [%s] PASS: No IT Chart available in configuration" % (sitename,KeyAccount))
            return False


        for id in itcharts_ids:
            keymem = "%s|%s" % (KeyAccount, id)
            key_ad = "itchart.activedirtectory.%s" % id
            keyToError = base64_encode("%s|%s|%s|%s" % (KeyAccount, id, method, sitename))
            timestamp = strtoint(self.mem.redis_get(keymem))
            if self.ITChartVerbose==1:self.sendlogs("%s: [DEBUG] %s Checking ITCharter id [%s] = %s" % (sitename,id, KeyAccount, timestamp))
            if timestamp > 0: continue

            if len(username) > 0:
                ad_filters = self.mem.redis_get(key_ad)
                if ad_filters is None: ad_filters = ""

                if len(ad_filters) > 0:
                    if self.ITChartVerbose==1:self.sendlogs("%s: [DEBUG] Checking ITCharter AD filters [%s] = %s" % (KeyAccount, key_ad, len(ad_filters)))
                    if not self.check_ad_filters(username, ad_filters):
                        if self.ITChartVerbose==1:self.sendlogs("%s: [DEBUG] Checking  ITCharter id [%s] AD filters NONE, NEXT" % (KeyAccount, id))
                        continue
                    if self.ITChartVerbose==1:self.sendlogs("%s: [DEBUG] Checking ITCharter id [%s] = Active Directory -> ASK %s" % (id, username, keyToError))

            if self.ITChartVerbose==1:self.sendlogs("%s: [DEBUG] Checking ITCharter id [%s] = ASK %s" % (id, KeyAccount, keyToError))
            self.message="%s" % (keyToError)
            return True

        if self.ITChartVerbose == 1: self.sendlogs("%s: [DEBUG] [%s] = PASS [OK]" % (sitename, KeyAccount))
        return False
        pass

    def FindKeyAccount(self,username, ipaddr, mac):
        if username == "-": username = ""
        if ipaddr == "-": ipaddr = ""
        if ipaddr == "127.0.0.1": ipaddr = ""
        if mac == "-": mac = ""
        if mac == "00:00:00:00:00:00": mac = ""
        if len(username) > 3: return username
        if len(mac) > 3: return mac
        if len(ipaddr) > 3: return ipaddr

    def get_itcharts_ids(self):
        zarray = {}
        data = base64_decode(self.mem.redis_get("itcharts.ids"))
        if len(data) < 3:
            if self.ITChartVerbose==1: self.sendlogs("%s: [DEBUG]: get_itcharts_ids no more data [SKIP]" % self.sitename)
            return zarray
        try:
            zarray = unserialize(data)
        except:
            if self.ITChartVerbose==1: self.sendlogs("%s: [DEBUG] ERROR <%s>" % (self.sitename,tb.format_exc))
            return zarray

        if self.ITChartVerbose == 1: self.sendlogs("%s: [DEBUG]: get_itcharts_ids array of %s items" % (self.sitename,len(zarray)))
        return zarray

    def check_ad_filters(self,username, ad_filters):
        global debug
        AD = ActiveDirectory()
        AD.PrintScreen = True
        username = username.lower()

        if len(ad_filters) == 0: return False
        dn_lists = ad_filters.split("|||")

        for dn in dn_lists:
            if debug: sendlogs("%s: [%s] [DEBUG] Checking ITCharter AD group [%s]" % (self.sitename,username, dn))
            connection_id = AD.GetConnectioIDFromDn(dn)

            if connection_id is None:
                if debug: sendlogs("%s: [%s] [DEBUG] Error, no Connection ID for [%s]" % (self.sitename,username, dn))
                continue

            if debug: sendlogs("%s: [%s] [DEBUG] connection id [%s] " % (self.sitename,username, connection_id))
            Users = AD.GetUsersFromDNGroup(dn, connection_id)
            if len(Users) == 0:
                if debug: sendlogs("%s: [%s] [DEBUG] [%s] have no user" % (self.sitename,username, dn))
                continue

            if username in Users:
                if debug: sendlogs("%s: [%s] [DEBUG] Success!! is a member of [%s]" % (self.sitename,username, dn))
                AD.Close()
                return True

        AD.Close()
    
