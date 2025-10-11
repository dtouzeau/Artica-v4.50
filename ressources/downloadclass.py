#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
from unix import *
import pycurl,re,syslog
from StringIO import StringIO



class ccurl:
    def __init__(self):
        self.url = ""
        self.destfile=""
        self.curlobj=None
        self.UseProxy=False
        self.ProxyAuth=False
        self.ProxyAddress=""
        self.ProxyPort=0
        self.ProxyUser=""
        self.ProxyPass=""
        self.CurlAgent=""
        self.BindIP=""
        self.GetProxy()
        self.downloaded_size = 0
        self.RESPONSE_CODE=0
        self.error=""


    def GetProxy(self):
        SQUIDEnable         = GET_INFO_INT("SQUIDEnable")
        ArticaProxySettings = GET_INFO_STR("ArticaProxySettings")
        SquidMgrListenPort  = GET_INFO_INT("SquidMgrListenPort")
        ProxySettings={}
        tbl=ArticaProxySettings.split('\n')
        for line in tbl:
            if line.find('=')>0:
                sx=line.split('=')
                key=sx[0].strip()
                value=sx[1].strip()
                ProxySettings[key]=value


        if not "ArticaProxyServerEnabled" in ProxySettings: ProxySettings["ArticaProxyServerEnabled"]=0
        if not "ArticaProxyServerName" in ProxySettings: ProxySettings["ArticaProxyServerName"] = ""
        if not "ArticaProxyServerPort" in ProxySettings: ProxySettings["ArticaProxyServerPort"] = 3128
        if not "ArticaProxyServerUsername" in ProxySettings: ProxySettings["ArticaProxyServerUsername"] = ""
        if not "ArticaProxyServerUserPassword" in ProxySettings: ProxySettings["ArticaProxyServerUserPassword"] = ""
        if not "NoCheckSquid" in ProxySettings: ProxySettings["NoCheckSquid"] = 0
        if not "WgetBindIpAddress" in ProxySettings: ProxySettings["WgetBindIpAddress"] = ""
        if not "CurlUserAgent" in ProxySettings: ProxySettings["CurlUserAgent"] = "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0"

        self.CurlAgent = ProxySettings["CurlUserAgent"]
        self.BindIP    = ProxySettings["WgetBindIpAddress"]

        try:
            ArticaProxyServerEnabled=int(ProxySettings["ArticaProxyServerEnabled"])
        except:
            ArticaProxyServerEnabled=0

        try:
            ArticaProxyServerPort = int(ProxySettings["ArticaProxyServerPort"])
        except:
            ArticaProxyServerPort = 3128

        try:
            NoCheckSquid = int(ProxySettings["NoCheckSquid"])
        except:
            NoCheckSquid = 0

        if SQUIDEnable == 0: NoCheckSquid = 1
        if NoCheckSquid == 1: return self.default_proxy(ProxySettings)

        self.UseProxy = True
        self.ProxyAddress="127.0.0.1"
        self.ProxyPort = SquidMgrListenPort

    def default_proxy(self,ProxySettings):
        if ProxySettings["ArticaProxyServerEnabled"]==0: return True
        if len(ProxySettings["ArticaProxyServerName"])==0: return True
        self.UseProxy = True
        self.ProxyAddress = ProxySettings["ArticaProxyServerName"]
        self.ProxyPort = ProxySettings["ArticaProxyServerPort"]
        if len(ProxySettings["ArticaProxyServerUsername"]) == 0: return True
        self.ProxyAuth = True
        self.ProxyUser=ProxySettings["ArticaProxyServerUsername"]
        self.ProxyPass=ProxySettings["ArticaProxyServerUserPassword"]

    def xsyslog(self,text):
        syslog.openlog("HTTP_ENGINE", syslog.LOG_PID)
        syslog.syslog(syslog.LOG_INFO,"%s" % text)


    def DownloadFile(self,url,destfile):
        self.url = url
        self.destfile= destfile
        data = None
        SSL = False
        self.downloaded_size =0
        self.RESPONSE_CODE=0

        CurlTimeOut=GET_INFO_INT("CurlTimeOut")
        CurlBandwith=GET_INFO_INT("CurlBandwith")
        if CurlTimeOut == 0: CurlTimeOut=3600

        if os.path.exists(destfile): os.unlink(destfile)
        matches=re.search('^https:',url)
        if matches: SSL = True

        fPointer = open(destfile, 'wb')
        curl = pycurl.Curl()
        curl.setopt(curl.WRITEFUNCTION, fPointer.write)
        curl.setopt(pycurl.URL, url)
        curl.setopt(pycurl.FOLLOWLOCATION, 1)
        curl.setopt(pycurl.CONNECTTIMEOUT, 5)
        curl.setopt(pycurl.TIMEOUT, 300)
        if CurlBandwith > 50: curl.setopt(pycurl.MAX_RECV_SPEED_LARGE, CurlBandwith)
        curl.setopt(pycurl.COOKIEFILE, '')
        curl.setopt(pycurl.PROXY, "")
        if SSL:
            curl.setopt(pycurl.SSL_VERIFYPEER, 0)
            curl.setopt(pycurl.SSL_VERIFYHOST, 0)

        if len(self.BindIP) > 8: curl.setopt(pycurl.INTERFACE, self.BindIP)
        if len(self.CurlAgent) > 3: curl.setopt(pycurl.USERAGENT, self.CurlAgent)

        if self.UseProxy:
            curl.setopt(pycurl.PROXY, "%s:%s" % (self.ProxyAddress, self.ProxyPort ))
            if self.ProxyAuth:
                curl.setopt(pycurl.PROXYUSERPWD, "%s:%s" % (self.ProxyUser, self.ProxyPass))

        try:
            curl.perform()
            self.RESPONSE_CODE = int(curl.getinfo(pycurl.HTTP_CODE))
            totaltime=curl.getinfo(curl.TOTAL_TIME)
            speed="%.2f bytes/second"  % curl.getinfo(curl.SPEED_DOWNLOAD)
            size=curl.getinfo(curl.SIZE_DOWNLOAD)
            self.downloaded_size=size
            # Response code,Total time,Speed, size
            self.xsyslog("[%s] RC: %s T:%s S:%s size=[%s bytes]" % (url,self.RESPONSE_CODE,totaltime,speed,size))
        except pycurl.error as ex:
            (code, message) = ex
            self.error="%s - %s" % (code,message)
            self.xsyslog("[%s] Error:  %s - %s" %(url,code,message))
            return False

        curl.close()
        if self.RESPONSE_CODE == 200: return True







