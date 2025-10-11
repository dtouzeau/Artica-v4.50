#!/usr/bin/env python
import sys
reload(sys)  
sys.setdefaultencoding('utf-8')
import io
import os
import logging
import datetime
import smtplib
from random import randint
from email.MIMEMultipart import MIMEMultipart
from email.mime.text import MIMEText
sys.path.append('/usr/share/artica-postfix/ressources')
import cherrypy
from cherrypy.lib.httputil import parse_query_string
from cherrypy.process.plugins import Daemonizer
from cherrypy.lib.static import serve_file
from cherrypy.process.plugins import PIDFile
from netaddr import IPNetwork, IPAddress
from validate_email import validate_email
from articaevents import *
import traceback as tb
import hashlib
import os
import re
from unix import *
from mysqlclass import *
from ldapclass import *
from activedirectoryclass import *
import uuid


class RootServer:
    
    def __init__(self):
        cherrypy.config.update({'tools.proxy.on':True})
        cherrypy.config.update({'tools.proxy.local' : "X-Forwarded-Host"})
        cherrypy.config.update({'tools.proxy.remote' : "X-Forwarded-For" })
        levelLOG=logging.INFO
        self.debug=False
        HotSpotArticaDebug=GET_INFO_INT("HotSpotArticaDebug")
        if HotSpotArticaDebug==1: levelLOG=logging.DEBUG
        logging.basicConfig(format='%(asctime)s [%(levelname)s] %(message)s',filename='/var/log/hotspot.debug',  filemode='a',level=levelLOG)
        logging.raiseExceptions = False
        self.http_port=GET_INFO_INT("ArticaSplashHotSpotPort")
        self.q=MYSQLENGINE(logging)
        self.ArticaHotSpotNowPassword=0
        self.hotspot_error=""
        self.AutoRegisterSMTP=0
        self.logging=logging
        self.gw_address=""
        self.referer=""
        self.UserAgent=""
        self.mobile=""
        self.gw_port=0
        self.Token=""
        self.incoming=0
        self.outgoing=0
        self.voucher=0
        self.passphrase=""
        self.choose_method=""
        self.bypassTerms=0
        
        self.uid=""
        self.ttl=0
        self.gw_id=""
        self.enabled=0
        self.ruleid=1
        self.url=""
        self.ip=""
        self.mac=''
        self.username=''
        self.password=''
        self.adminsql=ArticaEvents(logging)
        self.localldap=CLLDAP(logging)
        if self.http_port == 0:self.http_port=16080
        if HotSpotArticaDebug==1: self.debug=True
        if not os.path.exists("/usr/share/hotspot/net.array"):
            cherrypy.log("Building rules with exec.hotspot.build.php","LOGIN")
            os.system("/usr/bin/php /usr/share/artica-postfix/exec.hotspot.build.php")
            
        

    
    
    @cherrypy.expose
    def index(self, **keywords):
        return "it works! index "

    @cherrypy.expose
    def ping(self, **keywords):
        print keywords
        return "Pong\r\n"
    
    
    def CleanValues(self):
        self.hotspot_error=""
        
    
    @cherrypy.expose
    def login(self, **keywords):
        self.CleanValues()
        if not "url" in keywords: return self.portal()
        self.url=keywords["url"]
        self.ip=keywords["ip"]
        self.mac=keywords["mac"]
        self.gw_address=keywords["gw_address"]
        self.gw_port=keywords["gw_port"]
        self.username=""
        self.bypassTerms=0
        if "gw_id" in keywords: self.gw_id=keywords["gw_id"]
        if "method" in keywords: self.choose_method=keywords["method"]
        if "dropdown" in keywords: self.bypassTerms=1
        if "username" in keywords:
            self.username=keywords["username"]
            self.uid=keywords["username"]
        
        if self.BadAgents():
            if self.debug: cherrypy.log("["+self.UserAgent+"]: BAD USERAGENT -> die","LOGIN")
            return ""
                    
        self.GetRuleID()
        ALL_LOGINS=self.GET_INT("ALL_LOGINS")
        USE_TERMS=self.GET_INT("USE_TERMS")
        SMS_REGISTER=self.GET_INT("SMS_REGISTER")
        ENABLED_AUTO_LOGIN=self.GET_INT("ENABLED_AUTO_LOGIN")
        USE_VOUCHER=self.GET_INT("USE_VOUCHER")
        if ALL_LOGINS==1:
            SMS_REGISTER=0
            USE_VOUCHER=0
        
        if self.bypassTerms==1:USE_TERMS=0
        if self.debug: cherrypy.log("["+self.UserAgent+"]: Rule:" + str(self.ruleid)+" USE_TERMS="+str(USE_TERMS)+" SMS_REGISTER="+str(SMS_REGISTER),"LOGIN")
        
        if USE_TERMS ==1:
            if not "wifidog-terms" in keywords:
                filename="/usr/share/hotspot/"+str(self.ruleid)+"/terms.html"
                if self.debug: cherrypy.log("open" + filename,"LOGIN")
                content=self.read_file("terms.html")
                if self.debug: cherrypy.log("terms.html" + str(len(content))+"bytes length","LOGIN")
                content=self.replace_words(content)
                content=content.replace("%HIDDENFIELDS%",self.hidden_fields())
                content=content.replace('img/','images?picture=')
                content=content.replace("'img?","'images?picture=")
                return content
            
        
        filename="/usr/share/hotspot/"+str(self.ruleid)+"/login.html"
        
        if USE_VOUCHER==1: filename="/usr/share/hotspot/"+str(self.ruleid)+"/voucher.html"
        if SMS_REGISTER==1: filename="/usr/share/hotspot/"+str(self.ruleid)+"/sms.html"
        if ENABLED_AUTO_LOGIN==1: filename="/usr/share/hotspot/"+str(self.ruleid)+"/register.html"
        if self.debug: cherrypy.log("open" + filename,"LOGIN")
        content=file_get_contents(filename)
        content=self.replace_words(content)
        return content
    
    @cherrypy.expose
    def sms_login(self, **keywords):
        self.CleanValues()
        self.mobile=""
        self.url=keywords["url"]
        self.ip=keywords["ip"]
        self.mac=keywords["mac"]
        self.gw_address=keywords["gw_address"]
        self.gw_port=keywords["gw_port"]
        if "gw_id" in keywords: self.gw_id=keywords["gw_id"]
        if "method" in keywords: self.choose_method=keywords["method"]
        if "mobile" in keywords: self.mobile=keywords["mobile"]
        
        if self.BadAgents():
            if self.debug: cherrypy.log("["+self.UserAgent+"]: Microsoft NCSI -> die","LOGIN")
            return ""
                    
        self.GetRuleID()
       
        USE_TERMS=self.GET_INT("USE_TERMS")
        
        if USE_TERMS ==1:
            if not "wifidog-terms" in keywords:
                filename="/usr/share/hotspot/"+str(self.ruleid)+"/terms.html"
                if self.debug: cherrypy.log("open" + filename,"LOGIN")
                content=self.read_file("terms.html")
                content=self.replace_words(content)
                content=content.replace("%HIDDENFIELDS%",self.hidden_fields())
                content=content.replace('img/','images?picture=')
                content=content.replace("'img?","'images?picture=")
                return content
            
        
        filename="/usr/share/hotspot/"+str(self.ruleid)+"/sms.html"
        if self.debug: cherrypy.log("open" + filename,"LOGIN")
        content=file_get_contents(filename)
        content=self.replace_words(content)
        return content        
        


    
    @cherrypy.expose
    def auth(self, **keywords):
        self.CleanValues()
        # /auth?stage=counters&ip=10.28.0.35&mac=00:0c:29:e1:50:40&token=1900ab22-41d1-11e6-b109-000c29c616d6&incoming=0&outgoing=0&gw_id=000C29C616E0
        if self.debug: cherrypy.log("Parsing parameters","AUTH")
        self.voucher=0
        self.ip=keywords["ip"]
        self.mac=keywords["mac"]
        self.password=""
        if "method" in keywords: self.choose_method=keywords["method"]
        if "voucher" in keywords: self.voucher=1
            
        
        if "ruleid" in keywords:
            self.ruleid=keywords["ruleid"]
        else:
            self.GetRuleID()
            
        
        if "stage" in keywords:
            if self.debug: cherrypy.log("Receive Stage="+keywords["stage"]+" For "+self.ip,"STAGE")
            self.Token=keywords["token"]
            self.incoming=keywords["incoming"]
            self.outgoing=keywords["outgoing"]
            return self.CheckStages()
            
            
        
        self.url=keywords["url"]
        self.ip=keywords["ip"]
        self.mac=keywords["mac"]
        self.gw_address=keywords["gw_address"]
        self.gw_port=keywords["gw_port"]
        self.ruleid=keywords["ruleid"]
        self.username=keywords["username"]
        self.uid=keywords["username"]
        if "password" in keywords: self.password=keywords["password"]
        if "passphrase" in keywords: self.passphrase=keywords["passphrase"]
        
        if self.bounce():
            raise cherrypy.HTTPRedirect(["http://"+self.gw_address+":"+self.gw_port+"/wifidog/auth?token="+ self.Token], 302)

        if not self.authenticate():
            if self.debug: cherrypy.log("User Authenticated [FAILED]","AUTH")
            return self.authfailed()
        
        if self.debug: cherrypy.log("User Authenticated [OK]","AUTH")
        if self.debug: cherrypy.log("Redirect Token: "+self.Token+" to http://"+self.gw_address+":"+ str(self.gw_port)+"/wifidog/auth?token="+self.Token+"&ruleid="+self.ruleid,"AUTH")
        raise cherrypy.HTTPRedirect(["http://"+self.gw_address+":"+self.gw_port+"/wifidog/auth?token="+ self.Token], 302)
        pass
    
    def BadAgents(self):
        self.UserAgent=""
        try:
            self.UserAgent = cherrypy.request.headers["User-Agent"]
        except:
            return False
        
        if len(self.UserAgent)==0: return False
        
        matches=re.search("(AppleWebKit|IEMobile)\/[0-9]+",self.UserAgent)
        if matches: return False
        
        if self.UserAgent =="Microsoft NCSI": return True
                
        matches=re.search("(Microsoft-CryptoAPI|Skype WISPr|server-bag|HappyChefIOS|Musical\.ly)",self.UserAgent)
        if matches: return True
              
        matches=re.search("(Instagram|BTWebClient|CaptiveNetworkSupport-|TorrentMac|vente-privee|DownloadXML-VAIOImprovement|Java\/|Puzzle\/|gamed\/)",self.UserAgent)
        if matches: return True
        
        matches=re.search("Le.*?Monde",self.UserAgent)
        if matches: return True
        
        matches=re.search("Microsoft Outlook [0-9]+",self.UserAgent)
        if matches: return True
        
        matches=re.search("FBPN\/com\.facebook\.orca",self.UserAgent)
        if matches: return True
        
        matches=re.search("Facebook\/[0-9]+",self.UserAgent)
        if matches: return True

        matches=re.search("(iPhone|iPad)[0-9,]+",self.UserAgent)
        if matches: return False

        
        UserAgentMD5=hashlib.md5(self.UserAgent).hexdigest()
        if not os.path.exists("/home/HotSpot-UserAgents"): mkdir("/home/HotSpot-UserAgents",0755)
        if not os.path.exists("/home/HotSpot-UserAgents/"+UserAgentMD5+".UA"):file_put_contents("/home/HotSpot-UserAgents/"+UserAgentMD5+".UA", self.UserAgent);
                
        
        return False
        pass
    
    
    def bounce(self):
        self.CleanValues()
        BOUNCE_AUTH=self.GET_INT("BOUNCE_AUTH")
        if BOUNCE_AUTH==0: return False
        sql="SELECT uid FROM hotspot_members WHERE MAC='"+self.mac+"'";
        rows=self.q.QUERY_SQL(sql)
        if len(rows)==0: return False
        self.uid=rows[0][0]
        self.username=self.uid
        uid=uuid.uuid1()
        uid_str = uid.urn
        self.Token = uid_str[9:]
        if not self.build_session(self): return False
        self.adminsql.hotspot_admin_sql(2,"[BOUNCE]: Bounce registered user "+self.username,self.q.mysql_error,"bounce()")
        return True
        pass
    
   
    @cherrypy.expose
    def voucher_auth(self, **keywords):
        self.CleanValues()        
        self.hotspot_error=""
        self.url=keywords["url"]
        self.ip=keywords["ip"]
        self.mac=keywords["mac"]
        self.gw_address=keywords["gw_address"]
        self.gw_port=keywords["gw_port"]
        if "method" in keywords: self.choose_method=keywords["method"]
        if not "ruleid" in keywords: self.GetRuleID()
        if "ruleid" in keywords: self.ruleid=keywords["ruleid"]
        USE_VOUCHER=self.GET_INT("USE_VOUCHER")
        if USE_VOUCHER == 0: return ""
        filename="/usr/share/hotspot/"+str(self.ruleid)+"/voucher.html"
        if self.debug: cherrypy.log("open" + filename,"VOUCHER")
        content=file_get_contents(filename)
        content=self.replace_words(content)
        return content        
        
        
    
    @cherrypy.expose
    def sms_register(self, **keywords):
        self.CleanValues()
        self.mobile=""
        self.url=keywords["url"]
        self.ip=keywords["ip"]
        self.mac=keywords["mac"]
        self.gw_address=keywords["gw_address"]
        self.gw_port=keywords["gw_port"]
        if "method" in keywords: self.choose_method=keywords["method"]
        if not "ruleid" in keywords: self.GetRuleID()
        if "ruleid" in keywords: self.ruleid=keywords["ruleid"]
        
        SMS_CODE=""
        if "mobile" in keywords: self.mobile=str(keywords["mobile"])
        if "SMS_CODE" in keywords: SMS_CODE=keywords["SMS_CODE"]
        if self.debug: cherrypy.log("MOBILE.........: "+str(self.mobile),"SMS")
        
        matches=re.match('([0-9\.\+\s]+)',self.mobile)
        if not matches:
            if self.debug: cherrypy.log("FATAL!!!: ([0-9\.\+\s]+) no matches "+self.mobile,"SMS")
            self.hotspot_error="<p class=text-error>Wrong Phone Number</p>"
            filename="/usr/share/hotspot/"+str(self.ruleid)+"/sms.html"
            if self.debug: cherrypy.log("open" + filename,"SMS")
            content=file_get_contents(filename)
            content=self.replace_words(content)
            return content
            
        
        
        if self.debug: cherrypy.log("Confirmed Code.: "+str(SMS_CODE),"SMS")
        newdate=0
        FinalDate=0
        ArticaSplashHotSpotCacheAuth=self.GET_INT("ArticaSplashHotSpotCacheAuth")
        self.ttl=ArticaSplashHotSpotCacheAuth
        self.uid=self.mobile
        
        uid=uuid.uuid1()
        uid_str = uid.urn
        self.Token = uid_str[9:]
       
        autocreate=0
        autocreate_confirmed=0
        

        
        sql="SELECT password FROM hotspot_members WHERE `uid`='"+self.mobile+"' LIMIT 0,1"
        if self.debug: cherrypy.log("Token("+str(self.Token)+"): SQL QUERY: "+str(sql),"SMS")
        rows=self.q.QUERY_SQL(sql)
        if len(rows)==0:
            if self.debug: cherrypy.log("hotspot_members: NOT FOUND","SMS")
            password=str(self.random_with_N_digits(4))

            if not self.CreateMember(autocreate,autocreate_confirmed,password):
                filename="/usr/share/hotspot/"+str(self.ruleid)+"/sms.html"
                if self.debug: cherrypy.log("open" + filename,"SMS")
                content=file_get_contents(filename)
                content=self.replace_words(content)
                return content
            
            self.SendToMeta()
        
            if not self.sms_smtp(password):
                self.hotspot_error="<p class=text-error>SMTP Error</p>"
                filename="/usr/share/hotspot/"+str(self.ruleid)+"/sms.html"
                if self.debug: cherrypy.log("open" + filename,"SMS")
                content=file_get_contents(filename)
                content=self.replace_words(content)
                return content
            
            filename="/usr/share/hotspot/"+str(self.ruleid)+"/sms-posted.html"
            if self.debug: cherrypy.log("open" + filename,"SMS")
            content=file_get_contents(filename)
            content=self.replace_words(content)
            return content
        
        self.uid=self.mobile
        password=rows[0][0]
        if self.debug: cherrypy.log("Saved Code.....: "+str(password),"SMS")
       
        if SMS_CODE!=password:
            if self.debug: cherrypy.log("FATAL!!!: SMS_CODE `"+str(SMS_CODE)+"` no match `"+password+"`","SMS")
            if not self.sms_smtp(password):
                self.hotspot_error="<p class=text-error>"+self.mobile+": Wrong number<br>SMTP Error</p>"
                filename="/usr/share/hotspot/"+str(self.ruleid)+"/sms-posted.html"
                if self.debug: cherrypy.log("open" + filename,"SMS")
                content=file_get_contents(filename)
                content=self.replace_words(content)
                return content
            
            self.hotspot_error="<p class=text-error>"+self.mobile+": Wrong number </p>"
            filename="/usr/share/hotspot/"+str(self.ruleid)+"/sms-posted.html"
            if self.debug: cherrypy.log("open" + filename,"SMS")
            content=file_get_contents(filename)
            content=self.replace_words(content)
            return content
        
        
        if not self.build_session():
            self.hotspot_error="<p class=text-error>"+self.mobile+": Session failed </p>"
            filename="/usr/share/hotspot/"+str(self.ruleid)+"/sms-posted.html"
            if self.debug: cherrypy.log("open" + filename,"SMS")
            content=file_get_contents(filename)
            content=self.replace_words(content)
            return content            
            
        self.CreateMember(autocreate,autocreate_confirmed,password)
        raise cherrypy.HTTPRedirect(["http://"+self.gw_address+":"+self.gw_port+"/wifidog/auth?token="+ self.Token], 302)
        

        pass


    
    def sms_smtp(self,password):
        self.CleanValues()
        smtp_server_name=self.read_file('sms_smtp_server_name')
        smtp_auth_user=self.read_file('sms_smtp_auth_user')
        smtp_auth_passwd=self.read_file('sms_smtp_auth_passwd')
        fromaddr=self.read_file('sms_smtp_sender')
        sms_smtp_server_port=self.GET_INT('sms_smtp_server_port')
        tls_enabled=self.GET_INT('sms_tls_enabled')
        dest=self.read_file('sms_smtp_recipient')
        message=self.read_file("SMS_SMTP_BODY")
        SMS_SMTP_SUBJECT=self.read_file("SMS_SMTP_SUBJECT")
        if sms_smtp_server_port==0:sms_smtp_server_port=25
        now = datetime.datetime.now()
        CurrentTime=int(now.strftime("%s"))
        SMS_SMTP_SUBJECT=SMS_SMTP_SUBJECT.strip()
        SMS_SMTP_SUBJECT=SMS_SMTP_SUBJECT.replace("%MOBILE%", self.mobile)
        SMS_SMTP_SUBJECT=SMS_SMTP_SUBJECT.replace("%CODE%", str(password))
        SMS_SMTP_SUBJECT=SMS_SMTP_SUBJECT.replace("%TIME%", str(CurrentTime))
        if SMS_SMTP_SUBJECT=="EMPTY": SMS_SMTP_SUBJECT=""
        message=message.replace("%MOBILE%", self.mobile)
        message=message.replace("%CODE%", str(password))
        message=message.replace("%TIME%", str(CurrentTime))
        message=message.replace("\n", "\r\n")        
        
        msg = "From: "+fromaddr+"\r\n"
        msg = msg+"To: "+dest+"\r\n"
        msg = msg+"Subject: "+SMS_SMTP_SUBJECT+"\r\n"
        msg = msg+"MIME-Version: 1.0\r\n"
        msg = msg+"Content-type: text/plain; charset=\"utf-8\"\r\n"
        msg = msg+"Content-Transfer-Encoding: 8bit\r\n"
        msg = msg+"\r\n"+message
        
     
        try:
            server = smtplib.SMTP(smtp_server_name,sms_smtp_server_port)
        except socket.error as e:
            cherrypy.log(tb.format_exc,"SMS")
            self.adminsql.hotspot_admin_sql(0,"SMTP error failed "+smtp_server_name,tb.format_exc(),"sms_smtp()")
            return False
        server.set_debuglevel(0)
    
    
        try:
            if tls_enabled ==1:server.starttls()
            if len(smtp_auth_user)>2: server.login(smtp_auth_user, str(smtp_auth_passwd))
            server.sendmail(fromaddr, dest, msg)
        except:
            self.adminsql.hotspot_admin_sql(0,"SMTP error failed to "+dest+" by "+smtp_server_name,tb.format_exc(),"sms_smtp()")
            server.quit()
            return
        finally:
            if server != None: server.quit()
        return True
        pass
    
    
    @cherrypy.expose
    def authfailed(self):
        filename="/usr/share/hotspot/"+str(self.ruleid)+"/authfailed.html"
        self.logging.debug("authfailed: open "+filename)
        content= file_get_contents(filename)
        content=self.replace_words(content)
        return content
        pass
    
    @cherrypy.expose
    def css(self, **keywords):
        filename="/usr/share/hotspot/"+str(keywords["ruleid"])+"/index.css"
        if self.debug: cherrypy.log("Open: " + str(filename),"CSS")
        content= file_get_contents("/usr/share/hotspot/"+str(keywords["ruleid"])+"/index.css")
        content=content.replace('img/','images?picture=')
        content=content.replace("'img?","'images?picture=")
        cherrypy.response.headers['Content-Type']= 'text/css'
        return content
        pass
    
    @cherrypy.expose
    def resource(self, **keywords):
        filename="/usr/share/hotspot/"+str(keywords["ruleid"])+"/files/"+keywords["fname"]
        filename_type="/usr/share/hotspot/"+str(keywords["ruleid"])+"/files/"+keywords["fname"]+".type"
        
        if self.debug: cherrypy.log("Open: " + str(filename),"RESOURCE")
        if self.debug: cherrypy.log("Open: " + str(filename_type),"RESOURCE")
        content_type= file_get_contents(filename_type)
        content= file_get_contents(filename)
        if self.debug: cherrypy.log("Content-Type: " + str(content_type),"RESOURCE")
        cherrypy.response.headers['Content-Type']= content_type
        return serve_file(filename, '', "",keywords["fname"])
        pass
    
    
    def random_with_N_digits(self,n):
        range_start = 10**(n-1)
        range_end = (10**n)-1
        return randint(range_start, range_end)
    
    @cherrypy.expose
    def confirm(self, **keywords):
        self.CleanValues()
        
        if "ruleid" in keywords:
            self.ruleid=keywords["ruleid"]
        else:
            self.GetRuleID()
        
        if not "token"  in keywords:
            if self.debug: cherrypy.log("Token missing in query","CONFIRM")
            self.hotspot_error="Token missing in query..."
            return self.authfailed()
        
        ref = cherrypy.request.headers.elements('Referer')
        if ref:
            self.referer=ref
        if self.debug: cherrypy.log("Token("+str(self.Token)+"): REFERER: "+str(self.referer),"CONFIRM")
        
        self.Token=keywords["token"]
        FinalDate=0
        sql="SELECT uid,finaltime,ruleid,firsturl FROM hotspot_sessions WHERE `md5`='"+self.Token+"' LIMIT 0,1"
        if self.debug: cherrypy.log("Token("+str(self.Token)+"): SQL QUERY: "+str(sql),"CONFIRM")
        rows=self.q.QUERY_SQL(sql)
        if len(rows)==0:
            if self.debug: cherrypy.log("* * * * NO ROW in MySQL ! * * * *","CONFIRM")
            self.hotspot_error="Session removed or Database Error"
            return self.authfailed()
        
        self.uid=rows[0][0]
        finaltime=rows[0][1]
        self.ruleid=rows[0][2]
        firsturl=rows[0][3]
        if len(firsturl)>3: self.url=firsturl
        ArticaSplashHotSpotCacheAuth=self.GET_INT("ArticaSplashHotSpotCacheAuth")
        if self.debug: cherrypy.log("Token("+str(self.Token)+"): User:"+self.uid+", rule "+str(self.ruleid)+" Add new time session for "+str(ArticaSplashHotSpotCacheAuth)+"Mn","CONFIRM")

        now = datetime.datetime.now()
        logintime=int(now.strftime("%s"))
        if ArticaSplashHotSpotCacheAuth>0:
            newdate=now + timedelta(minutes = self.ttl)
            FinalDate=int(newdate.strftime("%s"))
            
        diff=FinalDate-logintime
        sql="UPDATE hotspot_sessions SET logintime='"+str(logintime)+"',finaltime='"+str(FinalDate)+"' WHERE `md5`='"+self.Token+"'"
        self.q.QUERY_SQL(sql)
        if not self.q.ok:
            self.adminsql.hotspot_admin_sql(0,"[CONFIRM]: MySQL error",self.q.mysql_error,"confirm()")
            cherrypy.log("MySQL error!!!","CONFIRM")
            self.hotspot_error=self.q.mysql_error
            return self.authfailed()
        
        sql="UPDATE hotspot_members SET autocreate_confirmed=1 WHERE uid='"+self.uid+"'"
        self.q.QUERY_SQL(sql)
        if not self.q.ok:
            self.adminsql.hotspot_admin_sql(0,"[CONFIRM]: MySQL error",self.q.mysql_error,"confirm()")
            cherrypy.log("MySQL error!!!","CONFIRM")
            self.hotspot_error=self.q.mysql_error
            return self.authfailed()
            
        
        
        LANDING_PAGE=self.read_file("LANDING_PAGE")
        if self.debug: cherrypy.log("LANDING PAGE == "+LANDING_PAGE,"CONFIRM")
        if len(LANDING_PAGE)>3: firsturl=LANDING_PAGE
        self.adminsql.hotspot_admin_sql(2,"[CONFIRM]: Member "+self.uid+": confirmed the new session","Redirected to: "+firsturl,"build_session()")
        if len(firsturl)>2: raise cherrypy.HTTPRedirect([firsturl], 302)
        return self.portal()
        

    @cherrypy.expose
    def register(self, **keywords):
        self.CleanValues()
        password=""
        ENABLED_SMTP=self.GET_INT("ENABLED_SMTP")
        USE_ACTIVEDIRECTORY=self.GET_INT("USE_ACTIVEDIRECTORY")
        REGISTER_GENERIC_PASSWORD=self.GET_INT("REGISTER_GENERIC_PASSWORD")
        ArticaHotSpotNowPassword=self.GET_INT("ArticaHotSpotNowPassword")
        content= self.read_file("register.html")
        
        if "method" in keywords: self.choose_method=keywords["method"]
        if "ip" in keywords: self.ip=keywords["ip"]
        if "mac" in keywords: self.mac=keywords["mac"]
        if "gw_address" in keywords: self.gw_address=keywords["gw_address"]
        if "gw_port" in keywords: self.gw_port=keywords["gw_port"]
        if "url" in keywords: self.url=keywords["url"]
        if "passphrase" in keywords: self.passphrase=keywords["passphrase"]
       
        
        if "ruleid" in keywords:
            self.ruleid=keywords["ruleid"]
        else:
            self.GetRuleID()
            
            
        if self.debug: cherrypy.log("("+str(self.ruleid)+"): Register IP: "+self.ip+" MAC="+self.mac+", REGISTER_GENERIC_PASSWORD="+str(REGISTER_GENERIC_PASSWORD)+", ENABLED_SMTP="+str(ENABLED_SMTP),"REGISTER")
        
        if "register-member" in keywords:
            self.username=keywords["email"]
            self.uid=self.username
            
            if self.debug: cherrypy.log("Active Directory Enabled == "+str(USE_ACTIVEDIRECTORY),"REGISTER")
            
            if USE_ACTIVEDIRECTORY==1:
                if self.debug: cherrypy.log("--> self.authenticate()","REGISTER")
                if self.authenticate():
                    if self.debug: cherrypy.log("User Authenticated [OK]","REGISTER")
                    if self.debug: cherrypy.log("Redirect Token: "+self.Token+" to http://"+self.gw_address+":"+ str(self.gw_port)+"/wifidog/auth?token="+self.Token+"&ruleid="+self.ruleid,"AUTH")
                    raise cherrypy.HTTPRedirect(["http://"+self.gw_address+":"+self.gw_port+"/wifidog/auth?token="+ self.Token], 302)
                    return True
                if self.debug: cherrypy.log("--> self.authenticate() --> FAILED","REGISTER")
            
            if self.debug: cherrypy.log("("+str(self.ruleid)+"): Register member: "+self.username+" MAC="+self.mac,"REGISTER")
            if ENABLED_SMTP==1:
                if not validate_email(self.username,check_mx=False):
                    self.adminsql.hotspot_admin_sql(1,self.username+" wrong email address from "+self.ip+" MAC="+self.mac,"","register()")
                    ErrorInvalidMail=self.read_file("ErrorInvalidMail")
                    self.hotspot_error='<p class=text-error>'+ErrorInvalidMail+'</p>'
                    content=self.replace_words(content)
                    return content
                    
            
            if self.debug: cherrypy.log("("+str(self.ruleid)+"): Register ruleid: "+str(self.ruleid),"REGISTER")
            rows=self.q.QUERY_SQL("SELECT uid,autocreate FROM hotspot_members WHERE `uid`='"+self.username+"'")
            if len(rows)>0:
                autocreate=rows[0][1]
                if autocreate ==1:
                    self.q.QUERY_SQL("DELETE FROM hotspot_members WHERE `uid`='"+self.username+"'")
                else:
                    self.hotspot_error="<p class=text-error>"+self.read_file("ErrorThisAccountExists")+"</p>"
                    content=self.replace_words(content)
                    return content
                    
          
            
            uid=uuid.uuid1()
            uid_str = uid.urn
            self.Token = uid_str[9:]
            if self.debug: cherrypy.log("("+str(self.ruleid)+"): Register Token: "+str(self.Token),"REGISTER")
            
            #passphrase
            if "password" in keywords:
                password=keywords["password"]
                password2=keywords["password2"]
                if password != password2:
                    PasswordMismatch=self.read_file("PasswordMismatch")
                    if len(PasswordMismatch)==0: PasswordMismatch="Password mismatch!"
                    content=content.replace('%ERROR%','<p class=text-error>'+PasswordMismatch+'</span>')
                    return content
            
           
                
            autocreate=1
            autocreate_confirmed=1
            if ENABLED_SMTP==1: autocreate_confirmed=0
            now = datetime.datetime.now()
            CurrentTime=int(now.strftime("%s"))
            

            if not self.CreateMember(autocreate,autocreate_confirmed,password ):
                content=self.replace_words(content)
                return content

            
            if ENABLED_SMTP==0:
                if not self.build_session():
                     self.hotspot_error="<p class=text-error>Create Session failed!</p>"
                     content=self.replace_words(content)
                     return content
                raise cherrypy.HTTPRedirect(["http://"+self.gw_address+":"+self.gw_port+"/wifidog/auth?token="+ self.Token], 302)
                    
 
            if ENABLED_SMTP==1:
                if REGISTER_GENERIC_PASSWORD==1:
                    REGISTER_GENERIC_PASSTXT=self.read_file("REGISTER_GENERIC_PASSTXT")
                    if REGISTER_GENERIC_PASSTXT != self.passphrase:
                        if self.debug: cherrypy.log("("+str(self.ruleid)+"): "+str(self.passphrase)+" no match "+str(REGISTER_GENERIC_PASSTXT),"REGISTER")
                        REGISTER_GENERIC_PASSERR=self.read_file("REGISTER_GENERIC_PASSERR")
                        self.hotspot_error="<p class=text-error>"+REGISTER_GENERIC_PASSERR+"</p>"
                        content=self.replace_words(content)
                        return content
                        
                
                self.AutoRegisterSMTP=self.GET_INT("REGISTER_MAX_TIME")
                if self.AutoRegisterSMTP==0: self.AutoRegisterSMTP=5
                if self.debug: cherrypy.log("("+str(self.ruleid)+"): REGISTER_MAX_TIME: "+str(self.AutoRegisterSMTP),"REGISTER")
                if not self.build_session():
                    self.hotspot_error="<p class=text-error>Create Session failed!</p>"
                    content=self.replace_words(content)
                    return content
                
                if not self.send_smtp_notif(self.username):
                    self.hotspot_error="<p class=text-error>SMTP Session failed</p>"
                    content=self.replace_words(content)
                    return content
                
                raise cherrypy.HTTPRedirect(["http://"+self.gw_address+":"+self.gw_port+"/wifidog/auth?token="+ self.Token], 302)
                
                

        content=self.replace_words(content)
        return content
        pass   

    
    @cherrypy.expose
    def images(self, **keywords):
        filename=keywords["picture"]
        filename=filename.replace('../','')
        return serve_file("/usr/share/artica-postfix/img/"+filename, '', "attachment",filename)
        pass
    @cherrypy.expose
    def js(self, **keywords):
        if not "script" in keywords: return False
        filename=keywords["script"]
        filename=filename.replace('../','')
        return serve_file("/usr/share/artica-postfix/js/"+filename, '', "attachment",filename)
        pass    
    @cherrypy.expose
    def img(self, **keywords):
        for filename in keywords:
            if os.path.exists("/usr/share/artica-postfix/img/"+filename):
                return serve_file("/usr/share/artica-postfix/img/"+filename, '', "attachment",filename)
        pass
    @cherrypy.expose
    
    def hotspot_php(self,**keywords):
        if "imgload" in keywords:
            filename=keywords["imgload"]
            filename=filename.replace('../','')
            return serve_file("/usr/share/artica-postfix/img/"+filename, '', "attachment",filename)
        
        
    
    
    @cherrypy.expose
    def portal(self, **keywords):
        self.CleanValues()
        if "ruleid" in keywords:
            if self.debug: cherrypy.log("RULE NUMBER" + str(self.ruleid),"PORTAL")
            self.ruleid=keywords["ruleid"]
        else:
            self.GetRuleID()
        
        data=self.read_file("none.html")
        if self.debug: cherrypy.log("open: " + str(len(data))+" Bytes","PORTAL")
        data=self.replace_words(data);
        if self.debug: cherrypy.log("Replace/send: " + str(len(data))+" Bytes","PORTAL")
        return data
    
    @cherrypy.expose
    def gw_message_php(self, **keywords):
        message=keywords["message"]
        filename="/usr/share/hotspot/"+str(self.ruleid)+"/none.html"
        if self.debug: cherrypy.log("open" + filename,"ERROR")
        return serve_file(filename)
    
    def read_file(self,filename):
        filename="/usr/share/hotspot/"+str(self.ruleid)+"/"+filename
        if self.debug: cherrypy.log("open: " + filename,"PORTAL")
        if not os.path.exists(filename): return ""
        with io.open(filename, "r", encoding="ISO-8859-1") as my_file: return my_file.read()
        pass
    
    
    def GetRuleID(self):
        filename="/usr/share/hotspot/net.array"
        if not os.path.exists(filename):
            self.ruleid=1
            return 1
        
        if len(self.ip)==0: self.ip=self.FindMyIp()
        
        with open(filename,"r") as f:
            for txt in f :
                txt=txt.rstrip('\n')
                if len(txt)<5: next
                matches=re.search('(.+):([0-9]+)',txt)
                if not matches: next
                Network=matches.group(1)
                RuleID=int(matches.group(2))
                if self.debug: cherrypy.log("Testing: '" + Network+"'","GETRULE")
                if IPAddress(self.ip) in IPNetwork(Network):
                    if self.IsBrowser(RuleID):
                        if self.debug: cherrypy.log("* * * * * Choose Rule '" + str(RuleID)+"' * * * * * ","GETRULE")
                        self.ruleid=RuleID
                        return RuleID
        
        self.ruleid=1
        return 1
        pass
    
    
    def IsBrowser(self,ruleid):
        self.BadAgents()
        if len(self.UserAgent)==0:
            if self.debug: cherrypy.log("* * * * * UserAgent Unable to get User-Agent, assume ALL * * * * * ","GETRULE")
            return True
        
        filename="/usr/share/hotspot/"+str(ruleid)+"/browsers"
        if not os.path.exists(filename):
            if self.debug: cherrypy.log("* * * * * UserAgent '" + filename +"' --> No such file Assume ALL * * * * * ","GETRULE")
            return True
        Count=0
        with open(filename,"r") as f:
            for txt in f :
                txt=txt.rstrip('\n')
                if len(txt)<3: next
                Count=Count+1
                if self.debug: cherrypy.log("* * * * * UserAgent '" + txt +"' --> ["+self.UserAgent+"]* * * * * ","GETRULE")
                matches=re.search(txt,self.UserAgent)
                if matches:
                    if self.debug: cherrypy.log("* * * * * UserAgent '" + txt +"' --> [TRUE]* * * * * ","GETRULE")
                    return True
        
        if Count == 0:
            if self.debug: cherrypy.log("* * * * * UserAgent 0 rule --> Assume ALL * * * * * ","GETRULE")
            return True
        
        if self.debug: cherrypy.log("* * * * * UserAgent "+str(Count)+ " rules --> False * * * * * ","GETRULE")
        return False
        pass

    def FindMyIp(self):
        return cherrypy.request.remote.ip
        
        
    def GetLanguage(self):
        lang='en-us'
        try:
            header = self.parse_accept_header(cherrypy.request.headers.get("Accept-Language"))
        except:
            lang='en-us'
        
        try:
            if len(header[0])>1: lang=header[0]
        except:
            self.lang=lang
        
            
        self.lang=lang
        pass
    
    def CreateMember(self,autocreate,autocreate_confirmed,password ):
        if len(self.username)==0: self.username=self.uid
        sql="SELECT token FROM hotspot_members WHERE `uid`='"+self.uid+"' LIMIT 0,1"
        
        rows=self.q.QUERY_SQL(sql)
        if len(rows)>0:
            sql="UPDATE hotspot_members SET "
            if len(password)>0: sql=sql+" password='"+str(password)+"',"
            if len(self.mac)>0: sql=sql+" MAC='"+str(self.mac)+"',"
            if len(self.ip)>0:  sql=sql+" ipaddr='"+str(self.ip)+"',"
            if len(self.Token)>0: sql=sql+" token='"+str(self.Token)+"',"
            sql=sql+" ruleid='"+str(self.ruleid)+"',"
            sql=sql+" autocreate_confirmed='"+str(autocreate_confirmed)+"',"
            sql=sql+" autocreate='"+str(autocreate_confirmed)+" WHERE `uid`='"+self.uid+"'"
            if not self.q.ok:
                cherrypy.log("("+str(self.ruleid)+"): SQL Failed: "+str(sql),"MEMBER")
                self.adminsql.hotspot_admin_sql(0,"MySQL error",self.q.mysql_error,"register()")
                self.hotspot_error="<p class=text-error>MySQL Error!</p>"
                return False
             
        
        now = datetime.datetime.now()
        CurrentTime=int(now.strftime("%s"))
        ArticaSplashHotSpotEndTime=self.GET_INT("ArticaSplashHotSpotEndTime")
        sql="INSERT IGNORE INTO hotspot_members"
        sql=sql+"(uid,username,token,ruleid,ttl,sessiontime,"
        sql=sql+"password,enabled,creationtime,autocreate,autocreate_confirmed,"
        sql=sql+"autocreate_maxttl,sessionkey,MAC,hostname,ipaddr) VALUES"
        sql=sql+"('"+self.username+"','"+self.username+"','"+self.Token+"','"+str(self.ruleid)+"','"+str(ArticaSplashHotSpotEndTime)+"','0',"
        sql=sql+"'"+password+"',1,"+str(CurrentTime)+","+str(autocreate)+","+str(autocreate_confirmed)+",1,'"+self.Token+"','"+self.mac+"','','"+self.ip+"')"
        
        self.adminsql.hotspot_admin_sql(2,"Create new member Rule:"+str(self.ruleid)+" ["+self.username+"] session life for "+str(ArticaSplashHotSpotEndTime)+" min","","register()")
        
        self.q.QUERY_SQL(sql)
        if not self.q.ok:
            cherrypy.log("("+str(self.ruleid)+"): SQL Failed: "+str(sql),"MEMBER")
            self.adminsql.hotspot_admin_sql(0,"MySQL error",self.q.mysql_error,"register()")
            self.hotspot_error="<p class=text-error>MySQL Error!</p>"
            return False
            
        self.SendToMeta()
        return True
        pass
    
    
    def hidden_fields(self):
        form=[]
        form.append("<input type='hidden' name='url' value='"+self.url+"'>")
        form.append("<input type='hidden' name='ip' value='"+self.ip+"'>")
        form.append("<input type='hidden' name='mac' value='"+self.mac+"'>")
        form.append("<input type='hidden' name='gw_address' value='"+self.gw_address+"'>")
        form.append("<input type='hidden' name='gw_port' value='"+str(self.gw_port)+"'>")
        return "\n".join(form)
        pass

        
    
    def replace_words(self,content):
        self.GetLanguage()
        if len(self.url)==0: self.url=self.read_file("LOST_LANDING_PAGE")
        if len(self.url)==0: self.url="http://artica-proxy.com"
        content=content.replace('img/','images?picture=')
        content=content.replace("'img?","'images?picture=")
        content=content.replace('%URL%',self.url)
        content=content.replace("%HIDDENFIELDS%",self.hidden_fields())
        content=content.replace("%RETURNLOGIN%",self.uri_return_login())
        content=content.replace("%URILINK%",self.uri_return_alllinks())
        content=content.replace("%SMSPHONE%",self.mobile)
        content=content.replace("%HOTSPOT_ERROR%",self.hotspot_error)
        content=content.replace("%CHOOSE_METHOD%", self.choose_method)
        content=content.replace("%USERNAME%", self.username)
        
        if self.lang=='fr':
            content=content.replace('{username}','Utilisateur')
            content=content.replace('{password}','Mot de passe')
            content=content.replace('{connection}','Connexion')
            return content
        
        content=content.replace('{username}','User name')
        content=content.replace('{password}','Password')
        content=content.replace('{connection}','Connection')

        return content
        pass
    
    
    def uri_return_login(self):
        uri="login?gw_address="+self.gw_address+"&gw_port="+str(self.gw_port)
        uri=uri+"&gw_id="+self.gw_id+"&ip="+self.ip
        uri=uri+"&mac="+self.mac
        uri=uri+"&url="+self.url
        uri=uri+"&ruleid="+str(self.ruleid)
        return uri
    
    def uri_return_alllinks(self):
        uri="gw_address="+self.gw_address+"&gw_port="+str(self.gw_port)
        uri=uri+"&gw_id="+self.gw_id+"&ip="+self.ip
        uri=uri+"&mac="+self.mac
        uri=uri+"&url="+self.url
        uri=uri+"&ruleid="+str(self.ruleid)
        return uri        
    
    def CheckStages(self):
       
        now = datetime.datetime.now()
        CurrentTime=int(now.strftime("%s"))
       
        sql="SELECT uid,finaltime,ruleid,logintime FROM hotspot_sessions WHERE `md5`='"+self.Token+"' LIMIT 0,1"
        if self.debug: cherrypy.log("Token("+str(self.Token)+"): SQL QUERY: "+str(sql),"STAGE")
        rows=self.q.QUERY_SQL(sql)
        if len(rows)==0:
            self.adminsql.hotspot_admin_sql(1,"[DESTROY]: "+self.Token+" Session not found in MySQL database","","CheckStages()")
            cherrypy.log("Token("+str(self.Token)+"): User not found in MySQL database","STAGE")
            return "Auth: 0\nMessages: No session saved\n"
        
        self.uid=rows[0][0]
        finaltime=rows[0][1]
        self.ruleid=rows[0][2]
        logintime=int(rows[0][3])
        ArticaSplashHotSpotCacheAuth=self.GET_INT("ArticaSplashHotSpotCacheAuth")
        ArticaSplashHotSpotEndTime=self.GET_INT("ArticaSplashHotSpotEndTime")
        ArticaSplashHotSpotRemoveAccount=self.GET_INT("ArticaSplashHotSpotRemoveAccount")
        cherrypy.log("Rule("+str(self.ruleid)+"): "+self.uid+" rule "+str(self.ruleid)+" re-authenticate each "+str(ArticaSplashHotSpotCacheAuth)+"mn","STAGE")
        
        TOTALBYTES=0
        
        LIMIT_BY_SIZE=self.GET_INT("LIMIT_BY_SIZE")
        if LIMIT_BY_SIZE>0:
            TOTALBYTES=int(self.incoming)+int(self.outgoing)
            TOTALKB=TOTALBYTES/1024
            TOTALMB=TOTALKB/1024
            if TOTALMB>LIMIT_BY_SIZE:
                self.adminsql.hotspot_admin_sql(1,"[DESTROY]: "+self.uid+" disconnected size "+str(TOTALMB)+"MB exceed "+str(LIMIT_BY_SIZE)+"MB","","CheckStages()")
                cherrypy.log("Rule("+str(self.ruleid)+"): "+self.uid+" disconnect size "+str(TOTALMB)+"MB exceed "+str(LIMIT_BY_SIZE)+"MB","STAGE")
                self.q.QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='"+self.Token+"'")
                self.q.QUERY_SQL("DELETE FROM hotspot_sessions WHERE `MAC`='"+self.mac+"'")
                return "Auth: 0\nMessages: Disconnected\n"
                
                
        
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): "+self.uid+" disconnect at "+str(finaltime)+" Current:"+str(CurrentTime),"STAGE")
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): "+self.uid+" Final session   = "+str(ArticaSplashHotSpotCacheAuth),"STAGE")
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): "+self.uid+" Disable account = "+str(ArticaSplashHotSpotEndTime),"STAGE")
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): "+self.uid+" Remove account  = "+str(ArticaSplashHotSpotRemoveAccount),"STAGE")
        
        
        if finaltime==0:
            if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): "+self.uid+" no final time (assume unlimited)","STAGE")
            self.q.QUERY_SQL("UPDATE hotspot_sessions set incoming='"+self.incoming+"',outgoing='"+self.outgoing+"' WHERE `md5`='"+self.Token+"'")      
            return "Auth: 1\nMessages: OK\n"
            
        
        if finaltime<CurrentTime:
            # If Auto-register ?
            sql="SELECT autocreate,autocreate_confirmed,creationtime,enabled,ttl FROM hotspot_members WHERE uid='"+self.uid+"' LIMIT 0,1"
            rows=self.q.QUERY_SQL(sql)
            if not self.q.ok: self.adminsql.hotspot_admin_sql(0,"[ERROR]: "+self.uid+" "+str(self.q.mysql_error)+" ","","CheckStages()")
            autocreate=int(rows[0][0])
            autocreate_confirmed=int(rows[0][1])
            creationtime=int(rows[0][2])
            enabled=int(rows[0][3])
            ttl=int(rows[0][3])
            if ttl > ArticaSplashHotSpotEndTime: ArticaSplashHotSpotEndTime=ttl
            
            
            if enabled==0:
                self.adminsql.hotspot_admin_sql(1,"[DESTROY]: "+self.uid+" session expired and is disabled","","CheckStages()")
                self.destroySession()
                return "Auth: 0\nMessages: Disconnected\n"
            
            if autocreate == 1:
                if autocreate_confirmed == 0:
                    self.adminsql.hotspot_admin_sql(1,"[DESTROY]: "+self.uid+" session expired and not confirmed","","CheckStages()")
                    self.q.QUERY_SQL("DELETE FROM hotspot_members WHERE uid='"+self.uid+"'")
                    self.destroySession()
                    return "Auth: 0\nMessages: Disconnected\n"      
            
            if ArticaSplashHotSpotEndTime>0:
                ArticaSplashHotSpotEndTime=ArticaSplashHotSpotEndTime*60
                DisableTime=creationtime+ArticaSplashHotSpotEndTime
                if ttl < ArticaSplashHotSpotEndTime: self.q.QUERY_SQL("UPDATE hotspot_members SET ttl="+str(ArticaSplashHotSpotEndTime)+" WHERE uid='"+self.uid+"'")
                   
                    
                if DisableTime < CurrentTime:
                    self.adminsql.hotspot_admin_sql(1,"[DESTROY]: "+self.uid+" session expired and disable account","","CheckStages()")
                    self.q.QUERY_SQL("UPDATE hotspot_members SET enabled=0 WHERE uid='"+self.uid+"'")
                    if not self.q.ok: self.adminsql.hotspot_admin_sql(0,"[ERROR]: "+self.uid+" "+str(self.q.mysql_error)+" ","","CheckStages()")
                    self.destroySession()
                    return "Auth: 0\nMessages: Disconnected\n"      
                    
            if ArticaSplashHotSpotRemoveAccount >0:
                ArticaSplashHotSpotRemoveAccount=ArticaSplashHotSpotRemoveAccount*60
                RemoveTime=creationtime+ArticaSplashHotSpotRemoveAccount
                if RemoveTime < CurrentTime:
                    self.adminsql.hotspot_admin_sql(1,"[DESTROY]: "+self.uid+" session expired and remove account","","CheckStages()")
                    self.q.QUERY_SQL("DELETE FROM hotspot_members WHERE uid='"+self.uid+"'")
                    self.destroySession()
                    return "Auth: 0\nMessages: Disconnected\n"
            
            if ArticaSplashHotSpotCacheAuth == 0:
                finaltime=finaltime+3600
                Diff=finaltime-CurrentTime
                self.q.QUERY_SQL("UPDATE hotspot_sessions set nextcheck='"+str(finaltime)+"', finaltime='"+str(finaltime)+"', incoming='"+self.incoming+"',outgoing='"+self.outgoing+"' WHERE `md5`='"+self.Token+"'")
                if not self.q.ok: self.adminsql.hotspot_admin_sql(0,"[ERROR]: "+self.uid+" "+str(self.q.mysql_error)+" ","","CheckStages()")
                if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): "+self.uid+" OK PASS disconnect in ["+str(Diff)+"s]","STAGE")
                return "Auth: 1\nMessages: OK\n"
                    
            if ArticaSplashHotSpotCacheAuth > 0:
                zArticaSplashHotSpotCacheAuth=ArticaSplashHotSpotCacheAuth*60
                NextStage=logintime+zArticaSplashHotSpotCacheAuth
                
                if NextStage < CurrentTime:
                    self.adminsql.hotspot_admin_sql(1,"[DESTROY]: "+self.uid+" session expired after "+str(ArticaSplashHotSpotCacheAuth)+" minutes","","CheckStages()")
                    self.destroySession()
                    return "Auth: 0\nMessages: Disconnected\n"
                
                Diff=NextStage-CurrentTime
                self.q.QUERY_SQL("UPDATE hotspot_sessions set nextcheck='"+str(NextStage)+"',finaltime='"+str(NextStage)+"', incoming='"+self.incoming+"',outgoing='"+self.outgoing+"' WHERE `md5`='"+self.Token+"'")
                if not self.q.ok: self.adminsql.hotspot_admin_sql(0,"[ERROR]: "+self.uid+" "+str(self.q.mysql_error)+" ","","CheckStages()")
                if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): "+self.uid+" OK PASS disconnect in ["+str(Diff)+"s]","STAGE")
                return "Auth: 1\nMessages: OK\n"
            
            
            Diff=finaltime-CurrentTime
            self.adminsql.hotspot_admin_sql(1,"[DESTROY]: "+self.uid+" session expired","","CheckStages()")
            cherrypy.log("Rule("+str(self.ruleid)+"): "+self.uid+" * * * Disconnect ("+str(Diff)+" secondes diff) * * *","STAGE")
            return "Auth: 0\nMessages: Disconnected\n"
            
        Diff=finaltime-CurrentTime
        self.q.QUERY_SQL("UPDATE hotspot_sessions set incoming='"+self.incoming+"',outgoing='"+self.outgoing+"' WHERE `md5`='"+self.Token+"'")
        if not self.q.ok: self.adminsql.hotspot_admin_sql(0,"[ERROR]: "+self.uid+" "+str(self.q.mysql_error)+" ","","CheckStages()")
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): "+self.uid+" OK PASS disconnect in ["+str(Diff)+"s]","STAGE")
        return "Auth: 1\nMessages: OK\n"
    
    
    
    def destroySession(self):
        self.q.QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='"+self.Token+"'")
        self.q.QUERY_SQL("DELETE FROM hotspot_sessions WHERE `MAC`='"+self.mac+"'")
        pass
           
    
    
    def authenticate_ldap(self):
        if self.ArticaHotSpotNowPassword==1: return False
        if self.voucher==1: return False
        ldap_password=self.localldap.GetUserPassword(self.username)
        if len(self.password)==0: return False
        if self.ArticaHotSpotNowPassword==1: return True
        if self.password==ldap_password: return self.build_session()
        return False
        pass
    
        
        
    def authenticate(self):
        USE_MYSQL=self.GET_INT("USE_MYSQL")
        USE_ACTIVEDIRECTORY=self.GET_INT("USE_ACTIVEDIRECTORY")
        self.ArticaHotSpotNowPassword=self.GET_INT("ArticaHotSpotNowPassword")
        if self.voucher==1:
            USE_MYSQL=1
            USE_ACTIVEDIRECTORY=0
            
        if self.debug: cherrypy.log("USE_MYSQL: "+str(USE_MYSQL)+" Check Password="+str(self.ArticaHotSpotNowPassword),"AUTH")
        md5Password=hashlib.md5(self.password).hexdigest()
        uid=uuid.uuid1()
        uid_str = uid.urn
        self.Token = uid_str[9:]
        
        if USE_ACTIVEDIRECTORY==1:
            if self.authenticate_ad(): return self.build_session()
        
        if USE_MYSQL==1:
            sql="SELECT uid,password,creationtime,ttl,enabled FROM hotspot_members WHERE uid='"+self.username+"' LIMIT 0,1"
            rows=self.q.QUERY_SQL(sql)
            if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): SQL QUERY: "+str(sql),"AUTH")
            if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): SQL ROWS: "+str(len(rows)),"AUTH")
            if len(rows)==0:
                if self.voucher==1: return False
                if self.debug: cherrypy.log(self.username+" user not found in MySQL database","AUTH")
                return self.authenticate_ldap()
            
            if self.voucher==1: return True
            if self.ArticaHotSpotNowPassword==1: return True
            
            self.uid=rows[0][0]
            password=rows[0][1]
            creationtime=rows[0][2]
            self.ttl=rows[0][3]
            self.enabled=rows[0][4]
            if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): SQL Results: "+str(self.uid)+" Enabled="+str(self.enabled)+":"+str(password)+"/"+md5Password,"AUTH")
            
            if self.enabled==0:
                if self.debug: cherrypy.log("User is not Enabled","AUTH")
                return False
                
            
            if password == md5Password:
                if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Password match [OK]","AUTH")
                return self.build_session()
            if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Password not match","AUTH")
        pass
    
    
    def authenticate_ad(self):
        HotSpotArticaDebug=GET_INFO_INT("HotSpotArticaDebug")
        if HotSpotArticaDebug==1: self.debug=True
        content=self.read_file("ActiveDirectories.array")
        if len(content)==0: return False
        array=content.split("\n")
        AD=ActiveDirectory(self.logging)
        for line in array:
            GroupToCheck=""
            if line=="": continue
            AD.ldap_server=line
            matches=re.match("^(.+?):(.+)",line)
            if matches:
                GroupToCheck=matches.group(2)
                GroupToCheck=GroupToCheck.decode('utf-8').lower()
                AD.ldap_server=matches.group(1)
               
            if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): CREDS:"+ self.username+" Server:"+AD.ldap_server,"ACTIVEDIRECTORY")
            AD.username=self.username
            AD.password=self.password
            if GroupToCheck=='*': GroupToCheck=""
            if GroupToCheck=='everyone': GroupToCheck=""
            if GroupToCheck=='tout le monde': GroupToCheck=""
            
            if not AD.TestBind():
                if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): BIND:"+ self.username+" Error:"+AD.ldap_error+" [BAD]","ACTIVEDIRECTORY")
                self.adminsql.hotspot_admin_sql(1,"[authenticate_ad]: Failed to bind as "+self.username+"@"+AD.ldap_server,"","authenticate_ad()")
                continue
            
            if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): BIND:"+ self.username+":"+AD.ldap_error+" [LOGON SUCCESS]","ACTIVEDIRECTORY")
            if len(GroupToCheck)==0:
                self.username=AD.sAMAccountName
                self.uid=AD.sAMAccountName
                return True
            
            zGroups=AD.GetUserGroups(AD.username)
            if zGroups==None:
                if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Check:"+ self.username+":"+AD.ldap_error+" [NO GROUP FOUND]","ACTIVEDIRECTORY")
                self.adminsql.hotspot_admin_sql(1,"[authenticate_ad]: Failed to find Active Directory groups for "+self.username+"@"+AD.ldap_server,"","authenticate_ad()")
                continue
            
            if GroupToCheck in zGroups:
                if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Check:"+ self.username+" Success, is a member of "+GroupToCheck,"ACTIVEDIRECTORY")
                self.adminsql.hotspot_admin_sql(2,"[authenticate_ad]: Success logon "+self.username+" to the AD server "+AD.ldap_server,"","authenticate_ad()")
                self.username=AD.sAMAccountName
                self.uid=AD.sAMAccountName
                return True
            
            if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Check:"+ self.username+" failed, Not a member of "+GroupToCheck,"ACTIVEDIRECTORY")
            
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Check:"+ self.username+" failed, authentication failed","ACTIVEDIRECTORY")
        return False
        
        pass
    
    
    def macwhite(self):
        MACWHITE=self.GET_INT("MACWHITE")
        if MACWHITE == 0: return False
        sql="INSERT IGNORE INTO hotspot_whitemacs (`MAC`,`enabled`,`username`)  VALUES ('"+self.mac+"','1','"+self.username+"');";
        self.q.QUERY_SQL(sql)
        ArticaHotSpotInterface=GET_INFO_STR("ArticaHotSpotInterface")
        cmd="/sbin/iptables -t mangle -I WiFiDog_"+ArticaHotSpotInterface+"_Trusted -m mac --mac-source "+self.mac+" -j MARK --set-xmark 0x2/0xffffffff";
        execute(cmd)
        pass
    
            
    def build_session(self):
        FinalDate=0
        autocreate=0
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Remove old sessions","SESSION")
        self.q.QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='"+self.Token+"'");
        self.q.QUERY_SQL("DELETE FROM hotspot_sessions WHERE ipaddr='"+self.ip+"'");
        self.q.QUERY_SQL("DELETE FROM hotspot_sessions WHERE MAC='"+self.mac+"'");
        self.q.QUERY_SQL("DELETE FROM hotspot_sessions WHERE uid='"+self.uid+"'");
        ArticaSplashHotSpotCacheAuth=self.GET_INT("ArticaSplashHotSpotCacheAuth")
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Calculate Next session...","SESSION")
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Force user to re-authenticate each "+str(self.ttl)+"Mn from user","SESSION")
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Force local user to re-authenticate each "+str(self.ttl)+"Mn From rule","SESSION")
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Force registered user to re-authenticate each "+str(self.AutoRegisterSMTP)+"Mn From rule","SESSION")
        
        if self.ttl == 0: self.ttl=ArticaSplashHotSpotCacheAuth
        if self.AutoRegisterSMTP>0:
                self.ttl=self.AutoRegisterSMTP
                autocreate=1
                
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Force user to re-authenticate each *** "+str(self.ttl)+"Mn ***","SESSION")
        
        if len(self.uid)==0: self.uid=self.username
        

        now = datetime.datetime.now()
        logintime=int(now.strftime("%s"))
        if self.ttl>0:
            newdate=now + timedelta(minutes = self.ttl)
            FinalDate=int(newdate.strftime("%s"))
            
        diff=FinalDate-logintime
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Force user to re-authenticate AT *** "+str(FinalDate)+" TIMESTAMP ( in "+str(diff)+" seconds ) ***","SESSION")
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): MAC.......: "+self.mac,"SESSION")
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Member....: "+self.uid,"SESSION")
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): Ip address: "+self.ip,"SESSION")
        sql="INSERT IGNORE INTO hotspot_sessions (`md5`,logintime, maxtime,finaltime,nextcheck,username,uid,MAC,hostname,ipaddr,autocreate,ruleid,firsturl)"
        sql=sql+" VALUES('"+self.Token+"',"+str(logintime)+","+str(self.ttl)+","+str(FinalDate)+","+str(FinalDate)+",'"+str(self.uid)+"','"+str(self.uid)+"',"
        sql=sql+"'"+self.mac+"','"+self.ip+"','"+self.ip+"','"+str(autocreate)+"','"+str(self.ruleid)+"','"+str(self.url)+"')"
        
        if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): "+sql,"SESSION")
        self.q.QUERY_SQL(sql)
        if not self.q.ok:
            self.adminsql.hotspot_admin_sql(0,"[SESSION]: MySQL error !!",self.q.mysql_error,"build_session()")
            cherrypy.log("Rule("+str(self.ruleid)+"): MySQL Failed!!!","SESSION")
            cherrypy.log("Rule("+str(self.ruleid)+"): "+self.q.mysql_error,"SESSION")
            return False
        
        self.adminsql.hotspot_admin_sql(2,"[SESSION]: ("+self.ip+"/"+self.mac+") Member "+self.uid+" Create new session TTL:"+str(self.ttl)+"mn","","build_session()")
        os.system("/usr/sbin/notify-hotspot-sessions.sh")
        return True

        pass
    
    def send_smtp_notif(self,recipient):
        ENABLED_SMTP=self.GET_INT('ENABLED_SMTP')
        if ENABLED_SMTP == 0: return False
        host =self.gw_address
   
            
        link="http://"+host+":"+str(self.http_port)+"/confirm?token="+self.Token
        REGISTER_MESSAGE=self.read_file('REGISTER_MESSAGE')
        REGISTER_SUBJECT=self.read_file('REGISTER_SUBJECT')
        smtp_server_name=self.read_file('smtp_server_name')
        smtp_auth_user=self.read_file('smtp_auth_user')
        smtp_auth_passwd=self.read_file('smtp_auth_passwd')
        fromaddr=self.read_file('smtp_sender')
        tls_enabled=self.GET_INT('tls_enabled')
        smtp_port=self.GET_INT('smtp_server_port')
        smtp_ssl=self.GET_INT('smtp_ssl')
        if smtp_port==0:
            if tls_enabled ==1: smtp_port=587
            if smtp_ssl==1: smtp_port=465
            if smtp_port==0: smtp_port=25
            
        dest=fromaddr
        msg = MIMEMultipart()
        msg['From'] =fromaddr
        msg['To'] = recipient
        msg['Subject'] = REGISTER_SUBJECT
        
        message = REGISTER_MESSAGE+"\r\n"+link
        msg.attach(MIMEText(message))
        text = msg.as_string()
        try:
            if smtp_ssl==0: server = smtplib.SMTP(smtp_server_name,smtp_port)
            if smtp_ssl==1: server = smtplib.SMTP_SSL(smtp_server_name,smtp_port)
            
        except socket.error as e:
            self.adminsql.hotspot_admin_sql(0,"SMTP error failed connect to "+smtp_server_name+":"+str(smtp_port),tb.format_exc(),"send_smtp_notif()")
            return False
        
        
        server.set_debuglevel(0)
        if self.debug: server.set_debuglevel(1)
        
        try:
            if tls_enabled ==1: server.starttls()
            if len(smtp_auth_user)>2: server.ehlo()
            if len(smtp_auth_user)>2: server.login(smtp_auth_user, str(smtp_auth_passwd))
            server.sendmail(fromaddr, recipient, text)
        except:
            self.adminsql.hotspot_admin_sql(0,"SMTP error failed to "+recipient+" by "+smtp_server_name+":"+str(smtp_port)+" TLS:"+str(tls_enabled),tb.format_exc(),"send_smtp_notif()")
            server.quit()
            return False
        finally:
            try:
                if server != None: server.quit()
            except:
                self.adminsql.hotspot_admin_sql(1,"SMTP error while sending QUIT command",tb.format_exc(),"send_smtp_notif()")
                
        
        self.adminsql.hotspot_admin_sql(2,"SMTP success to "+recipient+" by "+smtp_server_name,"","send_smtp_notif()")    
        return True
        pass
    
    
    def GET_INT(self,key):
        filename="/usr/share/hotspot/"+str(self.ruleid)+"/"+key
        if not os.path.exists(filename):
            file_put_contents(filename,0)
            return 0
        data=file_get_contents(filename)
        testdata=unicode(data,'utf-8')
        if data == '':
            return 0
        
        if testdata.isnumeric():
            return int(data)
        return 0
        pass
    
    def SendToMeta(self):
        ENABLED_META_LOGIN=self.GET_INT("ENABLED_META_LOGIN")
        if ENABLED_META_LOGIN==0: return False
        now = datetime.datetime.now()
        logintime=int(now.strftime("%s"))
        sql="INSERT IGNORE INTO `hotspot_members_meta` (uid,creationtime) VALUES ('"+self.uid+"','"+str(logintime)+"')"
        self.q.QUERY_SQL(sql)
        

    def parse_accept_header(self,accept_header):
        accepts = []
        for line in accept_header.replace(' ','').split(','):
            values = line.split(';q=')
            if len(values) > 1:
                try:
                    prio = float(values[1])
                except (ValueError, TypeError):
                    prio = 0.0
            else:
                prio = 1.0
            accepts.append((values[0], prio))
        accepts.sort(key=lambda (l, s): s, reverse=True)
        accept_headers = [l[0] for l in accepts]
        return accept_headers
    
    
    
    

if __name__ == '__main__':
    
    certificate_path='/etc/ssl/certs/apache/server.crt'
    private_key_path='/etc/ssl/certs/apache/server.key'
    http_port=GET_INFO_INT("ArticaSplashHotSpotPort")
    ssl_port=GET_INFO_INT("ArticaSplashHotSpotPortSSL")
    HotSpotThreads=GET_INFO_INT("HotSpotThreads")
    RemoveFile("/var/log/hotspot.debug")
    if HotSpotThreads==0: HotSpotThreads=100
    
    if http_port == 0:
        http_port=16080
        
    if ssl_port == 0:
        ssl_port=16443

    cherrypy.tree.mount(RootServer())
    cherrypy.server.unsubscribe()
    PIDFile(cherrypy.engine, "/var/run/hotspot-web.pid").subscribe()

    server1 = cherrypy._cpserver.Server()
    server1.socket_port=ssl_port
    server1._socket_host='0.0.0.0'
    server1.thread_pool=HotSpotThreads
    server1.ssl_module = 'pyopenssl'
    server1.ssl_certificate = certificate_path
    server1.ssl_private_key = private_key_path

    #server1.ssl_certificate_chain = '/home/ubuntu/gd_bundle.crt'
    server1.subscribe()

    server2 = cherrypy._cpserver.Server()
    server2.socket_port=http_port
    server2._socket_host="0.0.0.0"
    server2.thread_pool=HotSpotThreads
    server2.subscribe()

    cherrypy.engine.start()
    cherrypy.engine.block()