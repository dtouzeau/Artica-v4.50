#!/usr/bin/env python
import socket
from unix import *
import os.path
import urllib
import hashlib
import syslog
import traceback as tb
import re
from classartmem import *
from categorizeclass import *
from urlparse import urlparse
class UFDB:
    
    def __init__(self,debug=False):
        self.PythonEnableUfdbEnable=GET_INFO_INT("PythonEnableUfdbEnable")
        self.UseRemoteUfdbguardService=GET_INFO_INT("UseRemoteUfdbguardService")
        self.remote_port=GET_INFO_INT("PythonUfdbPort")
        self.remote_ip=GET_INFO_STR("PythonUfdbServer")
        self.SquidGuardRedirectHTTPCode=GET_INFO_INT("SquidGuardRedirectHTTPCode")
        self.SquidGuardWebExternalUri=GET_INFO_STR('SquidGuardWebExternalUri')
        self.SquidGuardWebExternalUriSSL=GET_INFO_STR('SquidGuardWebExternalUriSSL')
        self.SquidGuardWebUseExternalUri=GET_INFO_INT('SquidGuardWebUseExternalUri')
        self.UfdbgclientSockTimeOut=GET_INFO_INT('UfdbgclientSockTimeOut')
        self.SquidGuardApacheSSLPort=GET_INFO_INT('SquidGuardApacheSSLPort')
        self.SquidGuardApachePort=GET_INFO_INT('SquidGuardApachePort')
        self.SquidGuardServerName=GET_INFO_STR('SquidGuardServerName')
        self.SquidGuardRedirectBehavior=GET_INFO_STR('SquidGuardRedirectBehavior')
        self.PDSNInUfdb=GET_INFO_INT("PDSNInUfdb");
        self.EnableUfdbGuard=GET_INFO_INT("EnableUfdbGuard");
        self.SquidGuardWebSSLtoSSL=GET_INFO_INT("SquidGuardWebSSLtoSSL")
        self.UfdbGuardMaxUrisize = GET_INFO_INT("UfdbGuardMaxUrisize")
        self.ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")
        self.UfdbGuardWebFilteringCacheTime = GET_INFO_INT("UfdbGuardWebFilteringCacheTime")
        self.InactiveService=False
        self.catz = categorize()
        self.WriteSocketsToLog=False
        self.CLIENT_MAC=""
        self.CATEGORY_NAME=""
        self.PROXY_PROTO='GET'
        self.MIMIK=False
        self.bump_mode=''
        self.HTTPS=False
        self.mem = art_memcache()
        self.ISSSNI=False
        self.final_redirdect_code=302
        self.final_redirect_url=""
        self.redirect_key=""
        self.category=0
        self.webfiltering_token=""
        self.debug=debug
        self.NoOutput=False
        self.LineToSend=""
        self.RedirectURI=""
        self.Referer=""
        self.MEM_PASS={}
        self.MEM_BLOCK={}
        self.WEBFILTER_RULE_NAME=""
        self.TOKEN=""
        self.RULE_ID=0
        self.Cached=False

        if self.UfdbgclientSockTimeOut==0: self.UfdbgclientSockTimeOut=2
        if self.UfdbGuardWebFilteringCacheTime == 0: self.UfdbGuardWebFilteringCacheTime = 300
        if self.SquidGuardRedirectHTTPCode<300: self.SquidGuardRedirectHTTPCode=302
        if self.PDSNInUfdb==1: self.PythonEnableUfdbEnable=1
        if self.EnableUfdbGuard==1: self.PythonEnableUfdbEnable=1
        if self.SquidGuardApacheSSLPort==0: self.SquidGuardApacheSSLPort=9025
        if self.SquidGuardApachePort==0: self.SquidGuardApachePort=9020
        if len(self.SquidGuardServerName)==0: self.SquidGuardServerName='localhost'
        if len(self.SquidGuardRedirectBehavior)==0: self.SquidGuardRedirectBehavior="url"
        if self.remote_ip=="all": self.remote_ip="127.0.0.1"
        if self.UfdbGuardMaxUrisize == 0: self.UfdbGuardMaxUrisize = 640
        self.is_bumped()
        if self.ExternalAclFirstDebug==1: self.debug=True
        try:
            self.webpage=unserialize(GET_INFO_STR("UfdbWebSerialized"))
        except:
            self.webpage={}
                
        if self.debug: self.xsyslog('[UFDB_CLASS]: Use remote service.........: '+str(self.UseRemoteUfdbguardService))
        
        if self.UseRemoteUfdbguardService==0:
            self.CheckLocalConfig()
                
            if self.remote_port==0:
                self.xsyslog("[UFDB_CLASS]: Warning, unable to found the remote port config, assume 3977 port")
                self.remote_port=3977
                
            if len(self.remote_ip)==0:
                self.xsyslog("[UFDB_CLASS]: Warning, unable to found the remote TCP Addr config, assume 127.0.0.1")
                self.remote_ip="127.0.0.1"
                
        
        if self.remote_port>0:
            if self.debug: self.xsyslog('[UFDB_CLASS]: Redirect Code..............: '+str(self.SquidGuardRedirectHTTPCode))
            if self.debug: self.xsyslog('[UFDB_CLASS]: Connect to.................: ufdb://'+self.remote_ip+':'+str(self.remote_port))
            if self.debug: self.xsyslog('[UFDB_CLASS]: SquidGuardWebUseExternalUri: '+str(self.SquidGuardWebUseExternalUri))
            if self.debug: self.xsyslog('[UFDB_CLASS]: SquidGuardWebExternalUri...: '+self.SquidGuardWebExternalUri)
            if self.debug: self.xsyslog('[UFDB_CLASS]: SquidGuardWebExternalUriSSL: '+self.SquidGuardWebExternalUriSSL)
            if self.debug: self.xsyslog('[UFDB_CLASS]: Listen port: '+self.remote_ip+":"+str(self.remote_port))
            if self.MIMIK:
                if self.debug: self.xsyslog('[UFDB_CLASS]:MIMIK = True')
        pass


    def Process(self,PROXY_URL,URL_DOMAIN,CLIENT_IP,CLIENT_HOSTNAME,USERNAME,CLIENT_MAC,PROXY_IP,PROXY_PORT,catz):
        SOURCE_URL=PROXY_URL
        self.TOKEN=""
        proto=""
        TOKENS=[]
        matches = re.search("\/ufdbguard\.php\?rule-id=[0-9]+", PROXY_URL)
        category_name = "Unknown"
        ToUfdbCdir = ''
        ToUfdb  = ''
        self.CATEGORY_NAME=category_name
        if len(USERNAME)==0: USERNAME="-"
        if len(CLIENT_HOSTNAME)==0: CLIENT_HOSTNAME="-"
        if len(PROXY_IP) == 0: PROXY_IP = "127.0.0.1"
        if PROXY_PORT == 0: PROXY_PORT = 3128

        if matches:
            if self.debug: self.xsyslog('[UFDB_CLASS]: [CLIENT]: Loop to Web-filtering error page')
            return False


        if is_valid_ip(URL_DOMAIN):
            matches = re.search('^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)', URL_DOMAIN)
            if matches:
                CDIR_TO_CHECK = matches.group(1) + '.' + matches.group(2) + '.' + matches.group(3) + '.cdir'
                ToUfdbCdir = "http://%s %s/%s %s GET myip=%s myport=%s\n" % (CDIR_TO_CHECK,CLIENT_IP ,
                                                                             CLIENT_HOSTNAME ,
                                                    USERNAME , PROXY_IP , PROXY_PORT )

            NEW_DOMAIN = str(ip2long(URL_DOMAIN)) + '.addr'
            PROXY_URL = PROXY_URL.replace(URL_DOMAIN, NEW_DOMAIN)
            if self.debug: self.xsyslog('[UFDB_CLASS]: [CLIENT] replace [' + URL_DOMAIN + '] to [' + NEW_DOMAIN + ']:')
            PROXY_PROTO = "GET"

        PROXY_URL = PROXY_URL.replace('https', 'http')
        PROXY_URL = PROXY_URL.replace(':443', '')
        if PROXY_URL.find('http://')==-1: proto="http://"

        if self.UfdbGuardMaxUrisize == 0: self.UfdbGuardMaxUrisize = 640

        if len(PROXY_URL) > self.UfdbGuardMaxUrisize:
            if self.debug: self.xsyslog("[UFDB_CLASS]: [CLIENT] ALERT!...: URL %s exceed %s bytes, cut it!" % (URL_DOMAIN,self.UfdbGuardMaxUrisize))
            PROXY_URL = PROXY_URL[0:self.UfdbGuardMaxUrisize] + "..."



        if len(ToUfdbCdir) > 0:
            if self.debug: self.xsyslog('[UFDB_CLASS]: [CLIENT] Pass to Web-Filtering service (CDIR)')
            try:
                self.PROXY_PROTO = PROXY_PROTO
                self.CLIENT_MAC = CLIENT_MAC
                if self.SendToUfdb(ToUfdbCdir, SOURCE_URL, CLIENT_IP, USERNAME, CDIR_TO_CHECK):
                    category_name = "Unknown"
                    final_redirdect_code = self.final_redirdect_code
                    final_redirect_url = self.final_redirect_url
                    final_redirect_key = self.redirect_key
                    CATEGORY = self.category
                    if CATEGORY > 0:
                        category_name = self.catz.category_int_to_string(CATEGORY)
                        self.CATEGORY_NAME = category_name

                    if self.debug: self.xsyslog("[UFDB_CLASS]: Category %s Name: %s L.143" % (CATEGORY, category_name))
                    if self.debug: self.xsyslog('[UFDB_CLASS]: [CLIENT] CATEGORY=%s' % self.category)
                    TOKENS.append("status=%s %s=%s" % (final_redirdect_code, final_redirect_key, final_redirect_url))
                    TOKENS.append("shieldsblock=yes")
                    TOKENS.append(self.webfiltering_token)
                    TOKENS.append("category=%s category-name=%s clog=cinfo:%s-%s;" % (category, category_name, CATEGORY, category_name))
                    self.TOKEN=" ".join(TOKENS)
                    return True
                return False
            except Exception as e:
                self.xsyslog(tb.format_exc())
                self.xsyslog('[UFDB_CLASS]: FATAL! Exception while requesting CDIR to Web-Filtering Engine service')

        ToUfdb ="%s%s %s/%s %s GET myip=%s myport=%s\n" % (proto,PROXY_URL,CLIENT_IP,CLIENT_HOSTNAME,USERNAME,PROXY_IP,PROXY_PORT)
        try:
            if self.SendToUfdb(ToUfdb,SOURCE_URL, CLIENT_IP, USERNAME, CLIENT_HOSTNAME):
                final_redirdect_code = self.final_redirdect_code
                final_redirect_url = self.final_redirect_url
                final_redirect_key = self.redirect_key
                CATEGORY = self.category
                if self.debug: self.xsyslog('[UFDB_CLASS]: [CLIENT] CATEGORY=%s' % self.category)
                if CATEGORY > 0:
                    category_name = self.catz.category_int_to_string(CATEGORY)
                    self.CATEGORY_NAME=category_name
                if self.debug: self.xsyslog("[UFDB_CLASS]: Category [%s] Name: [%s] L.167" % (CATEGORY,category_name))

                return True
            return False
        except Exception as e:
            self.xsyslog(tb.format_exc())
            self.xsyslog('[UFDB_CLASS]: FATAL! Exception while requesting Web-Filtering Engine service')

        return False





    
    def CheckLocalConfig(self):
        if not os.path.exists("/etc/squid3/ufdbGuard.conf"):
            self.xsyslog("[UFDB_CLASS]: /etc/squid3/ufdbGuard.conf not found!")
            return
        
        self.xsyslog("[UFDB_CLASS]: Open /etc/squid3/ufdbGuard.conf")
        for line in open('/etc/squid3/ufdbGuard.conf','r').readlines():
            matches=re.search("^interface\s+(.+)",line)
            if matches:
                self.xsyslog("[UFDB_CLASS]: Found Interface "+str(matches.group(1))+" in ufdbGuard.conf")
                self.remote_ip=str(matches.group(1))
                if self.remote_ip=="all": self.remote_ip="127.0.0.1"
            
            matches=re.search("^port\s+([0-9]+)",line)
            if matches:
                self.xsyslog("[UFDB_CLASS]: Found Port "+str(matches.group(1))+" in ufdbGuard.conf")
                self.remote_port=int(matches.group(1))
        pass


    def is_bumped(self):
        if not os.path.exists("/etc/squid3/listen_ports.conf"):
            self.xsyslog("[UFDB_CLASS]:MIMIK /etc/squid3/listen_ports.conf not found!")
            return
        self.xsyslog("[UFDB_CLASS]:MIMIK Open /etc/squid3/listen_ports.conf")
        for line in open('/etc/squid3/listen_ports.conf', 'r').readlines():
            matches = re.search("^http_port.*?ssl-bump", line)
            if matches:
                self.xsyslog("[UFDB_CLASS]:MIMIK Found SSL port in proxy configuration")
                self.MIMIK=True
                return

        if self.debug: self.xsyslog("[UFDB_CLASS]:MIMIK * not * Found SSL port in proxy configuration")
        pass


    def xsyslog(self,text):
        sysDaemon=syslog
        sysDaemon.openlog("ksrn", syslog.LOG_PID)
        sysDaemon.syslog(syslog.LOG_INFO, "[CLIENT]: %s" % text)


    def SendSocket(self,query):
        self.InactiveService=False
        if self.remote_port==0:
            self.xsyslog('[UFDB_CLASS]: Configuration Error, no port set... Aborting!')
            return ''

        if self.debug: self.xsyslog('[UFDB_CLASS]: Send to service "'+query+"'")

        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(self.UfdbgclientSockTimeOut)

        try:
            sock.connect((self.remote_ip,self.remote_port))
        except socket.error as msg:
            self.xsyslog('[UFDB_CLASS]: Connection Error: Unable to connect to '+str(self.remote_ip)+':'+str(self.remote_port)+' ! - '+ str(msg[0]))
            return ''

        try:
            sock.send(query)
        except socket.error as msg:
            sock.close()
            self.xsyslog('[UFDB_CLASS]: Connection Error:  Unable to send data to '+str(self.remote_ip)+':'+str(self.remote_port)+' ! - '+ str(msg[0]))
            return ''

        try:
            response = sock.recv(1024)
        except socket.error as msg:
            sock.close()
            self.xsyslog('[UFDB_CLASS]: Connection Error:  Unable to receive data from '+str(self.remote_ip)+':'+str(self.remote_port)+' ! - '+ str(msg[0]))
            return ''

        response=response.strip()
        sock.close()

        if self.debug: self.xsyslog('[UFDB_CLASS]: RESPONSE: "'+response+'"')
        if response.find("?loading-database=yes")>0: self.InactiveService=True
        if response.find("?fatalerror=yes")>0: self.InactiveService=True
        if self.InactiveService: self.xsyslog("[UFDB_CLASS]:FATAL Error Load-database or Web-Filtering error!!")
        return response

    def SendToUfdb(self,query,sourceurl,clientip,uid,hostname):
        CONNECT=False
        redirection=""
        KEY='url'
        CategoryFound=""
        src_redirection=""
        self.WEBFILTER_RULE_NAME=""
        RULE_ID=0
        md5=""
        TOKENS=[]
        response=""

        if(re.search('\s+CONNECT\s+',query)): CONNECT=True
        if self.PROXY_PROTO=="CONNECT": CONNECT=True
        matches=re.search('^([0-9\.]+)$',hostname)
        if not matches: self.ISSSNI=True

        if self.MIMIK:
            if self.HTTPS:
                if not self.ISSSNI:
                    if self.debug: self.xsyslog("[UFDB_CLASS]: [" + hostname + "]: MIMIK but SNI not set, return false")
                    if self.debug: self.xsyslog('[UFDB_CLASS]: OK: "PASS"')
                    return False


        if CONNECT:
            if self.MIMIK:
                matches = re.search('^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])$', hostname)
                if matches:
                    if self.debug: self.xsyslog("[UFDB_CLASS]: ["+hostname+"]:IPv4 Connect received, but mimiked proxy, waiting bumped session..")
                    if self.debug: self.xsyslog('[UFDB_CLASS]: OK: "PASS"')
                    return False

                matches=re.search('(:(:([0-9A-Fa-f]{1,4})){1,7}|([0-9A-Fa-f]{1,4})(:{1,2}([0-9A-Fa-f]{1,4})|::$){1,7})',hostname)
                if matches:
                    if self.debug: self.xsyslog("[UFDB_CLASS]: ["+hostname+"]:IPv6 Connect received, but mimiked proxy, waiting bumped session..")
                    if self.debug: self.xsyslog('[UFDB_CLASS]: OK: "PASS"')
                    return False

        if self.debug: self.xsyslog('[UFDB_CLASS][222]: Query: ['+query.strip()+']')
        smd5 = "UFDBCACHE_%s" % self.mem.StrToMD5(query.strip())
        self.Cached = False
        if self.UfdbGuardWebFilteringCacheTime>1:
            response=self.mem.ksrncache_get(smd5)
            if response is not None:
                self.Cached=True
                if self.debug: self.xsyslog('[UFDB_CLASS]: response %s * * * HIT * * *' % smd5)



        if not self.Cached: response=self.SendSocket(query)
        if self.debug: self.xsyslog('[UFDB_CLASS][360]: response: ['+response+']')
        if self.InactiveService: return False

        if response =="OK":
            if self.debug: self.xsyslog('[UFDB_CLASS]: OK: "PASS" L.318')
            if not self.Cached:
                if self.UfdbGuardWebFilteringCacheTime > 1: self.mem.ksrncache_set(smd5,response,self.UfdbGuardWebFilteringCacheTime)
            return False

        try:
            if len(response)==0:
                if self.debug: self.xsyslog('[UFDB_CLASS][238]: UNKNOWN: "PASS" L.322')
                return False
        except:
            if self.debug: self.xsyslog('[UFDB_CLASS][349]: FATAL EXCEPTION UNKNOWN PASS L.349')
            return False

        if not self.Cached:
            if self.UfdbGuardWebFilteringCacheTime > 1: self.mem.ksrncache_set(smd5, response,self.UfdbGuardWebFilteringCacheTime)

        matches=re.search('rewrite-url="(.*?)"',response)
        KEY='rewrite-url'

        if not matches:
            matches=re.search('url="(.*?)"',response)
            KEY='url'

        if matches:
            redirection=matches.group(1)
            src_redirection=redirection


        if len(redirection)==0: redirection=response
        redirection=redirection.replace('??','?')

        if self.debug: self.xsyslog('[UFDB_CLASS][297]: redirection = "'+redirection+'" (299)')
        redirection_source=redirection

        matches=re.search('rule-id=([0-9]+).*?targetgroup=(.+?)&',redirection_source)
        if matches:
            CategoryFound=matches.group(2)
            CategoryFound=CategoryFound.replace('P',"")
            self.RULE_ID=matches.group(1)

        matches = re.search('clientgroup=(.+?)&',redirection_source)
        if matches: self.WEBFILTER_RULE_NAME=matches.group(1)




        if self.debug: self.xsyslog('[UFDB_CLASS]: CategoryFound = %s ruleid=%s' % (CategoryFound,self.RULE_ID ))

        if redirection.find("=%a")>0:
            redirection=redirection.replace('clientaddr=%a','clientaddr='+clientip)

        if redirection.find("=%i")>0:
            redirection=redirection.replace('clientuser=%i','clientuser='+uid)

        if redirection.find("=%u")>0:
            redirection=redirection.replace('url=%u','url='+urllib.quote_plus(sourceurl))

        if self.debug: self.xsyslog('[UFDB_CLASS]: Redirection "%s" L.360'% redirection)

        redirection=redirection.replace('"','')
        self.final_redirect_url = redirection
        self.redirect_key=KEY
        self.category=CategoryFound
        self.webfiltering_token="webfiltering=block,%s,%s  srcurl=\"%s\"" % (self.RULE_ID,CategoryFound,urllib.quote_plus(sourceurl))
        return True
        pass

    def urlencode(self, sourceurl):
        try:
            return urllib.quote_plus(sourceurl)
        except:
            return sourceurl
        pass