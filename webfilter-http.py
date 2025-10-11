#!/usr/bin/env python
import sys
import logging
import io
sys.path.append('/usr/share/artica-postfix/ressources')
import cherrypy
from cherrypy.lib.httputil import parse_query_string
from cherrypy.process.plugins import Daemonizer
from cherrypy.lib.static import serve_file
from cherrypy.process.plugins import PIDFile
from datetime import datetime
from urlparse import urlparse
from netaddr import IPNetwork, IPAddress
import tldextract
import time
import hashlib
import pwd
import grp
import os
import re
import base64
import types
import urllib
from unix import *
from mysqlclass import *
from ldapclass import *
from articaevents import *

def http_error_404_hander(status, message, traceback, version):
    root=RootServer()
    return root.index()
    

class RootServer:
    
    def __init__(self):
        logging.basicConfig(format='%(asctime)s [%(levelname)s] %(message)s',filename='/var/log/ufdb-http.log',  filemode='a',level=logging.DEBUG)
        logging.raiseExceptions = False
        self.logging=logging
        self.debug=False
        self.SquidMicroHotSpotSSLLanding=GET_INFO_STR("SquidMicroHotSpotSSLLanding")
        self.CachePath='/home/artica/microhotspot/Caches'
        self.SubFolder="default"
        self.visible_hostname=GET_INFO_STR("visible_hostname")
        self.artica_version=file_get_contents("/usr/share/artica-postfix/VERSION")
        self.cache_mgr_user=GET_INFO_STR("cache_mgr_user")
        self.category_type="(Personal database)"
        self.screen_error=""
        self.familysite=""
        self.addTocat=""
        self.notify=0
        self.ipaddr=""
        self.uid=""
        self.mac=""
        self.hostname=""
        self.Duration=" Unlimited time "
        self.SquidGuardIPWeb=""
        self.ChartContent=""
        self.rulename=""
        self.WEBPATH=""
        self.url=""
        self.q=object
        self.debug=True
        self.CATEGORYNAMES={}
        self.GetCategoriesNames()
        
        
        
        pass
    
    
    
    @cherrypy.expose
    def itchart_pdf(self,**keywords):
        if "Host" in cherrypy.request.headers: self.url="https://"+cherrypy.request.headers["Host"]
        self.ipaddr="0.0.0.0"
        if "Remote-Addr" in cherrypy.request.headers: self.ipaddr=cherrypy.request.headers["Remote-Addr"]
        
        self.GetSubFolder()
        
        if not "chart-id" in keywords:
            self.screen_error="Missing chart-id in GET parameters!"
            return self.DisplayError()
        if not "ruleid" in keywords:
            self.screen_error="Missing ruleid in GET parameters!"
            return self.DisplayError()
        
        chartid=keywords["chart-id"]
        self.ruleid=keywords["ruleid"]
        self.GetSubFolder()
        
        filename="/home/ufdb-templates/"+str(self.SubFolder)+"/PDF."+str(chartid)+".pdf"
        if not os.path.exists(filename):
            self.screen_error="Missing PDF."+str(chartid)+".pdf File!"
            return self.DisplayError()
            
        return serve_file(filename)
        
        
    
    @cherrypy.expose
    def AcceptChart(self,**keywords):
        self.ruleid=0
        if "rule-id" in keywords: self.ruleid=keywords["rule-id"]
        self.category="itchart"
        if not "AcceptChartContent" in keywords:
            self.screen_error="Missing AcceptChartContent in POST parameters!"
            return self.DisplayError()
            
        self.ChartContent=keywords["AcceptChartContent"]
                
        if "Host" in cherrypy.request.headers: self.url="https://"+cherrypy.request.headers["Host"]
        self.ipaddr="0.0.0.0"
        if "Remote-Addr" in cherrypy.request.headers: self.ipaddr=cherrypy.request.headers["Remote-Addr"]
        tempRequest=self.ChartContent.decode('base64')
        MAIN_ARRAY=unserialize(tempRequest)
        ChartID=int(MAIN_ARRAY["ChartID"])
        self.url=str(MAIN_ARRAY["src"])
        self.uid=str(MAIN_ARRAY["LOGIN"])
        self.ipaddr=str(MAIN_ARRAY["IPADDR"])
        self.mac=str(MAIN_ARRAY["MAC"])
        parsed = urlparse( self.url )
        familysite=parsed.hostname
        if not is_valid_ip(parsed.hostname):
            ext = tldextract.extract(self.url)
            familysite=ext.domain+'.'+ext.suffix
        
        self.familysite=familysite
        
        if "WEBPATH" in MAIN_ARRAY:
            self.WEBPATH=str(MAIN_ARRAY["WEBPATH"])
        else:
             self.WEBPATH=self.GetWebPath()
             
        self.GetSubFolder()
    
        sql="INSERT IGNORE INTO `itchartlog` (`chartid`,`uid`,`ipaddr`,`MAC`,`zDate`) "
        sql=sql+"VALUES ('"+str(ChartID)+"','"+self.uid+"','"+self.ipaddr+"','"+self.mac+"',NOW())"
        self.q=MYSQLENGINE(logging)
        self.q.QUERY_SQL(sql)
        if not self.q.ok:
            self.screen_error="Database Error: "+self.q.mysql_error
            evsql=ArticaEvents(logging)
            evsql.QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES (NOW(),'ITCharter/Confirm: MySQL Error','"+self.screen_error+"','AcceptChart','webfilter-http','94','0')")
            return self.DisplayError()
        
        
        indexContent=self.read_file("redirect.html")
        indexContent=self.replace_tokens(indexContent)
        return indexContent
        
        
#--------------------------------------------------------------------------------------------------------------------------------------------------------    
    @cherrypy.expose
    def itchart_php(self,**keywords):
        if self.BadAgents():
            if self.debug: cherrypy.log("BAD AGENT " + self.UserAgent + " [return null]","ITCHART")
            return ""
        
        self.ruleid=0
        if "Host" in cherrypy.request.headers: self.url="https://"+cherrypy.request.headers["Host"]
        self.ipaddr="0.0.0.0"
        if "Remote-Addr" in cherrypy.request.headers: self.ipaddr=cherrypy.request.headers["Remote-Addr"]
        self.category="itchart"
        self.ChartContent=keywords["request"]
        tempRequest=self.ChartContent.decode('base64')
        MAIN_ARRAY=unserialize(tempRequest)
        ChartID=int(MAIN_ARRAY["ChartID"])
        src=str(MAIN_ARRAY["src"])
        self.uid=str(MAIN_ARRAY["LOGIN"])
        self.ipaddr=str(MAIN_ARRAY["IPADDR"])
        self.mac=str(MAIN_ARRAY["MAC"])
        if "WEBPATH" in MAIN_ARRAY:
            self.WEBPATH=str(MAIN_ARRAY["WEBPATH"])
        else:
             self.WEBPATH=self.GetWebPath()
             
        self.GetSubFolder()
        indexContent=self.read_file("chart."+str(ChartID)+".html")
        indexContent=self.replace_tokens(indexContent)
        return indexContent
 
#--------------------------------------------------------------------------------------------------------------------------------------------------------         
    @cherrypy.expose 
    def root(self, **keywords):
        cherrypy.log("Landed root","WEBFILTER")
        return  index(keywords)
        
#--------------------------------------------------------------------------------------------------------------------------------------------------------         
    @cherrypy.expose     
    def index(self,**keywords):
        if self.BadAgents():
            if self.debug: cherrypy.log("BAD AGENT, return null","WEBFILTER")
            return ""
        
        self.ruleid=0
        if "Host" in cherrypy.request.headers: self.url="https://"+cherrypy.request.headers["Host"]
        self.ipaddr="0.0.0.0"
        if "Remote-Addr" in cherrypy.request.headers: self.ipaddr=cherrypy.request.headers["Remote-Addr"]
        self.category_type=""
        self.screen_error="Blocked trough SSL"
        self.category=""
        parsed = urlparse( self.url )
        len_path=len(parsed.path)
        self.domain=parsed.hostname
        self.GetSubFolder()
        indexContent=self.read_file("index.html")
        indexContent=self.replace_tokens(indexContent)
        return indexContent
    
    
    def loading_database(self):
        self.url=""
        if "Host" in cherrypy.request.headers: self.url="https://"+cherrypy.request.headers["Host"]
        self.ipaddr="0.0.0.0"
        if "Remote-Addr" in cherrypy.request.headers: self.ipaddr=cherrypy.request.headers["Remote-Addr"]        
        self.ruleid=0
        self.screen_error="Loading database (please retry)"
        self.category=""
        parsed = urlparse( self.url )
        len_path=len(parsed.path)
        self.domain=parsed.hostname
        self.GetSubFolder()
        indexContent=self.read_file("index.html")
        indexContent=self.replace_tokens(indexContent)
        return indexContent
        
         
    @cherrypy.expose
    def ufdbguardd_php(self, **keywords):
        self.screen_error=""
        self.category=""
        self.url=""
        self.ruleid=0
        self.domain=""
        len_path=0
        
        if self.BadAgents():
            if self.debug: cherrypy.log("BAD AGENT, return null","WEBFILTER")
            return ""
        
        if "loading-database" in keywords: return self.loading_database()
        if "release-ticket" in keywords: return self.release_ticket(keywords["serialize"])      
        if "rule-id" in keywords: self.ruleid=keywords["rule-id"]
        if "SquidGuardIPWeb" in keywords: self.SquidGuardIPWeb=keywords["SquidGuardIPWeb"]
        if "clientaddr" in keywords: self.ipaddr=keywords["clientaddr"]
        if "clientname" in keywords: self.hostname=keywords["clientname"]
        if "clientuser" in keywords: self.uid=keywords["clientuser"]
        if "clientgroup" in keywords: self.rulename=keywords["clientgroup"]
        if "targetgroup" in keywords: self.category=keywords["targetgroup"]
        if "url" in keywords: self.url=keywords["url"]
        if "addTocat" in keywords: self.addTocat=keywords["addTocat"]
        
        if type(self.ipaddr) is list: self.ipaddr=self.ipaddr[0]
        if type(self.uid) is list: self.uid=self.uid[0]
        if type(self.rulename) is list: self.rulename=self.rulename[0]
        if type(self.category) is list: self.category=self.category[0]
        if type(self.hostname) is list: self.hostname=self.hostname[0]
        if type(self.ruleid) is list: self.ruleid=self.ruleid[0]
        if type(self.url) is list: self.url=self.url[0]
        if type(self.SquidGuardIPWeb) is list: self.SquidGuardIPWeb=self.SquidGuardIPWeb[0]
        
        cherrypy.log("ufdbguardd_php(): Rule Number:"+str(self.ruleid)+" IP:"+self.ipaddr+" Member:"+str(self.uid)+" category:"+str(self.category),"WEBFILTER")
        
        self.CategoryCodeToCatName()
        self.GetSubFolder()
        
        if len(self.url)>0:
            parsed = urlparse( self.url )
            len_path=len(parsed.path)
            if self.debug: cherrypy.log(parsed.hostname+" Path:("+str(len_path)+") '" + parsed.path+"'","WEBFILTER")
            if parsed.path=='/pixel': return serve_file("/usr/share/artica-postfix/img/1x1.gif")
            if parsed.path=='/controltag': return serve_file("/home/ufdb-templates/fakes/fake.js")
            self.domain=parsed.hostname
            
        if self.url.find("1x1_pixel_")>1: return serve_file("/usr/share/artica-postfix/img/1x1.gif")
        if self.url.find(".doubleclick.net")>1: return serve_file("/usr/share/artica-postfix/img/1x1.gif")
        if self.url.find("exelator.com")>1: return serve_file("/usr/share/artica-postfix/img/1x1.gif")
        if self.url.find(".lijit.com")>1: return serve_file("/usr/share/artica-postfix/img/fake.js")
        if self.url.find(".adnxs.com")>1: return serve_file("/usr/share/artica-postfix/img/fake.js")
        if self.url.find(".adtechus.com/pubapim")>1: return serve_file("/usr/share/artica-postfix/img/fake.js")
        
        if len_path>1:
            extension=os.path.splitext(parsed.path)[1][1:].strip().lower()
            if len(extension)>1:
                if self.debug: cherrypy.log(parsed.hostname+" Extension: " + extension,"WEBFILTER")
                if extension=="js": return serve_file("/home/ufdb-templates/fakes/fake.js")
                if extension=="json": return serve_file("/home/ufdb-templates/fakes/fake.js")
                if extension=="xiti": return serve_file("/home/ufdb-templates/fakes/fake.js")
                if extension=="css": return serve_file("/home/ufdb-templates/fakes/fake.css")
                if extension=="gif": return serve_file("/usr/share/artica-postfix/img/1x1.gif")
                if extension=="jpg": return serve_file("/usr/share/artica-postfix/img/1x1.jpg")
                if extension=="jpeg": return serve_file("/usr/share/artica-postfix/img/1x1.jpg")
                if extension=="png": return serve_file("/usr/share/artica-postfix/img/1x1.png")
                
        indexContent=self.read_file("index.html")
        indexContent=self.replace_tokens(indexContent)
        
        return indexContent
#--------------------------------------------------------------------------------------------------------------------------------------------------------
    def release_ticket(self,xserialize):
     
        main=unserialize(base64.b64decode(xserialize))
        if self.debug:
            for key in main:
                cherrypy.log("resource(): Server "+key +"("+main[key]+")","WEBFILTER")
        self.addTocat=""        
        self.ruleid=main["rule-id"]
        self.SquidGuardIPWeb=main["SquidGuardIPWeb"]
        self.ipaddr=main["clientaddr"]
        self.hostname=main["clientname"]
        self.uid=main["clientuser"]
        self.rulename=main["clientgroup"]
        self.category=main["targetgroup"]
        self.url=main["url"]
        self.SubFolder=main["SubFolder"]
        self.maxtime=int(main["maxtime"])
        self.notify=int(main["notify"])
        if "addTocat" in main: self.addTocat=main["addTocat"]
        self.q=MYSQLENGINE(logging)
        FinalDate=0
        now = datetime.now()
        CurrentTime=int(now.strftime("%s"))
       
        parsed = urlparse( self.url )
        familysite=parsed.hostname
        if not is_valid_ip(parsed.hostname):
            ext = tldextract.extract(self.url)
            familysite=ext.domain+'.'+ext.suffix
        
        self.familysite=familysite
        if self.maxtime == 0: self.maxtime = 5256000
        newdate=now + timedelta(minutes = self.maxtime)
        FinalDate=int(newdate.strftime("%s"))
        self.Duration=str(self.maxtime)+" minutes"
            
        zmd5=hashlib.md5(parsed.hostname+self.ipaddr+self.ruleid).hexdigest()    
        self.q.QUERY_SQL("INSERT IGNORE INTO `ufdbunlock` (`md5`,`logintime`,`finaltime`,`uid`,`www`,`ipaddr`) VALUES('"+zmd5+"','"+str(CurrentTime)+"','"+str(FinalDate)+"','"+self.uid+"','"+familysite+"','"+self.ipaddr+"')");
        if not self.q.ok:
            self.screen_error="Database Error: "+self.q.mysql_error
            evsql=ArticaEvents(logging)
            evsql.QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES (NOW(),'Web error page MySQL Error','"+self.screen_error+"','unlock','webfilter-http','138','0')")
            return self.DisplayError()
        
        
        self.addTocat=self.addTocat.strip()
        if len(self.addTocat)>2:
            SYSTEMID=GET_INFO_STR("SYSTEMID")
            xcat=self.CategoryToTableName(self.addTocat)
            ymd5=hashlib.md5(self.addTocat+familysite).hexdigest()
            self.q.QUERY_SQL("INSERT IGNORE INTO "+xcat+" (zmd5,zDate,category,pattern,uuid) VALUES('"+ymd5+"',NOW(),'"+str(self.addTocat)+"','"+familysite+"','"+SYSTEMID+"')")
            if not self.q.ok:
                self.screen_error="Database Error: "+self.q.mysql_error
                evsql=ArticaEvents(logging)
                evsql.QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES (NOW(),'Web error page MySQL Error','"+self.screen_error+"','unlock','webfilter-http','138','0')")
            
        
            
        
        if  self.notify==1:
            evsql=ArticaEvents(logging)
            evsql.QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES (NOW(),'UFDB-HTTP: release "+familysite+" From Ticket','Unlock "+familysite+" For "+self.ipaddr+" categorized as "+self.category+"','unlock','webfilter-http','142','0')")
        
        os.system('/usr/bin/php /usr/share/artica-postfix/exec.ufdb.queue.release.php --force')
        indexContent=self.read_file("ticketok_success.html")
        indexContent=self.replace_tokens(indexContent)
        return indexContent
        
        
        pass
    
#--------------------------------------------------------------------------------------------------------------------------------------------------------    
    
    @cherrypy.expose
    def ticketform2(self, **keywords):
        self.ruleid=keywords["rule-id"]
        self.SquidGuardIPWeb=keywords["SquidGuardIPWeb"]
        self.ipaddr=keywords["clientaddr"]
        self.hostname=keywords["clientname"]
        self.uid=keywords["clientuser"]
        self.rulename=keywords["clientgroup"]
        self.category=keywords["targetgroup"]
        self.url=keywords["url"]
        self.maxtime=int(keywords["maxtime"])
        self.notify=keywords["notify"]
        self.SubFolder=keywords["SubFolder"]
        if "addTocat" in keywords: self.addTocat=keywords["addTocat"]    
      
        rulename=file_get_contents("/home/ufdb-templates/rule_"+str(self.ruleid))
        if len(rulename)==0: rulename="Unknown"
        
        parsed = urlparse( self.url )
        evsql=ArticaEvents(logging)
        familysite=parsed.hostname
        if not is_valid_ip(parsed.hostname):
            ext = tldextract.extract(self.url)
            familysite=ext.domain+'.'+ext.suffix
        
        
        self.familysite=familysite
        indexContent=self.read_file("ticketconfirm.html")
        indexContent=self.replace_tokens(indexContent)
        return indexContent
    
#--------------------------------------------------------------------------------------------------------------------------------------------------------    
    
    @cherrypy.expose
    def ticketform(self, **keywords):
        self.ruleid=keywords["rule-id"]
        self.SquidGuardIPWeb=keywords["SquidGuardIPWeb"]
        self.ipaddr=keywords["clientaddr"]
        self.hostname=keywords["clientname"]
        self.uid=keywords["clientuser"]
        self.rulename=keywords["clientgroup"]
        self.category=keywords["targetgroup"]
        self.url=keywords["url"]
        self.maxtime=int(keywords["maxtime"])
        self.notify=keywords["notify"]
        self.SubFolder=keywords["SubFolder"]
        if "addTocat" in keywords: self.addTocat=keywords["addTocat"]
        self.q=MYSQLENGINE(logging)
        now = datetime.now()
        rulename=file_get_contents("/home/ufdb-templates/rule_"+str(self.ruleid))
        if len(rulename)==0: rulename="Unknown"
        
        parsed = urlparse( self.url )
        evsql=ArticaEvents(logging)
        familysite=parsed.hostname
        if not is_valid_ip(parsed.hostname):
            ext = tldextract.extract(self.url)
            familysite=ext.domain+'.'+ext.suffix
        
        
        REASONGIVEN="Web-Filtering blocked by category "+self.category+" Rule: "+rulename
        self.familysite=familysite
        if self.maxtime == 0: self.maxtime = 5256000
        newdate=now + timedelta(minutes = self.maxtime)
        FinalDate=int(newdate.strftime("%s"))
        zmd5=hashlib.md5(parsed.hostname+self.ipaddr+self.ruleid).hexdigest()
        subject="Web-Filtering Ticket request: "+parsed.hostname+"/"+familysite+" from "+self.uid+"/"+self.ipaddr
        self.q.QUERY_SQL("INSERT IGNORE INTO webfilters_usersasks (zmd5,ipaddr,sitename,uid) VALUES ('"+zmd5+"','"+self.ipaddr+"','"+self.familysite+"','"+self.uid+"')")
        if not self.q.ok:
            self.screen_error="Database Error: "+self.q.mysql_error
            evsql.QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES (NOW(),'Web error page MySQL Error','"+self.screen_error+"','unlock','webfilter-http','138','0')")
            return self.DisplayError()
            
        
        if self.notify==1:
            sql="INSERT IGNORE INTO `squid_admin_mysql`"
            sql=sql+" (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES"
            sql=sql+"(NOW(),'','"+subject+"','ticketform','webfilter-http','140','1','')";
            evsql.QUERY_SQL(sql)

        body=[]    
        body.append("Request...........: "+self.url)
        body.append("Client IP address.: "+self.ipaddr)
        body.append("Client Hostname...: "+self.hostname)
        body.append("Client Username...: "+self.uid)
        body.append("Rule ID...........: "+self.ruleid)
        body.append("rule name.........: "+self.rulename)
        body.append("Category..........: "+self.category)

        text="||".join(body)
        main_array=base64.b64encode(serialize(keywords))
        
        sql="CREATE TABLE IF NOT EXISTS `ufdb_smtp` (`zmd5` varchar(90) NOT NULL,`zDate` datetime NOT NULL,`Subject` varchar(255) NOT NULL,`content` varchar(255) NOT NULL,`main_array` TEXT,`URL` varchar(255) NOT NULL,`REASONGIVEN` varchar(255) NOT NULL,`sender` varchar(128) NOT NULL,`retrytime` smallint(1) NOT NULL,`ticket` smallint(1) NOT NULL,`SquidGuardIPWeb` varchar(255),PRIMARY KEY (`zmd5`),KEY `zDate` (`zDate`),KEY `Subject` (`Subject`),KEY `sender` (`sender`),KEY `ticket` (`ticket`),KEY `retrytime` (`retrytime`)) ENGINE=MYISAM;"
        self.q.QUERY_SQL(sql)

        sql="INSERT IGNORE INTO ufdb_smtp (`zmd5`,`zDate`,`Subject`,`content`,`sender`,`URL`,"
        sql=sql+"`REASONGIVEN`,`retrytime`,`SquidGuardIPWeb`,`ticket`,`main_array`) VALUES"
        sql=sql+" ('"+zmd5+"',NOW(),'"+subject+"','"+text+"','','"+self.url+"','"+REASONGIVEN+"','0','"+self.SquidGuardIPWeb+"','1','"+main_array+"')"

        self.q.QUERY_SQL(sql)
        if not self.q.ok:
            self.screen_error="Database Error: "+self.q.mysql_error
            evsql=ArticaEvents(logging)
            evsql.QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES (NOW(),'Web error page MySQL Error','"+self.screen_error+"','unlock','webfilter-http','138','0')")
            return self.DisplayError()
           
        
                
        os.system('/usr/bin/php /usr/share/artica-postfix/exec.squidguard.smtp.php --smtp --force')
        indexContent=self.read_file("ticket_success.html")
        indexContent=self.replace_tokens(indexContent)
        return indexContent
        
        
        

#--------------------------------------------------------------------------------------------------------------------------------------------------------    
    @cherrypy.expose
    def unlock(self, **keywords):
        self.addTocat=''
        self.ruleid=keywords["rule-id"]
        self.SquidGuardIPWeb=keywords["SquidGuardIPWeb"]
        self.ipaddr=keywords["clientaddr"]
        self.hostname=keywords["clientname"]
        self.uid=urllib.unquote(keywords["clientuser"]).decode('utf8') 
        self.rulename=keywords["clientgroup"]
        self.category=keywords["targetgroup"]
        self.url=keywords["url"]
        self.maxtime=int(keywords["maxtime"])
        self.notify=keywords["notify"]
        self.SubFolder=keywords["SubFolder"]
        if "addTocat" in keywords: self.addTocat=keywords["addTocat"]
        
        self.q=MYSQLENGINE(logging)
        FinalDate=0
        now = datetime.now()
        CurrentTime=int(now.strftime("%s"))
       
        parsed = urlparse( self.url )
        familysite=parsed.hostname
        if not is_valid_ip(parsed.hostname):
            ext = tldextract.extract(self.url)
            familysite=ext.domain+'.'+ext.suffix
        
        self.familysite=familysite
        if self.maxtime == 0: self.maxtime = 5256000
        newdate=now + timedelta(minutes = self.maxtime)
        FinalDate=int(newdate.strftime("%s"))
            
        zmd5=hashlib.md5(parsed.hostname+self.ipaddr+self.ruleid).hexdigest()    
        self.q.QUERY_SQL("INSERT IGNORE INTO `ufdbunlock` (`md5`,`logintime`,`finaltime`,`uid`,`www`,`ipaddr`) VALUES('"+zmd5+"','"+str(CurrentTime)+"','"+str(FinalDate)+"','"+self.uid+"','"+familysite+"','"+self.ipaddr+"')");
        if not self.q.ok:
            self.screen_error="Database Error: "+self.q.mysql_error
            evsql=ArticaEvents(logging)
            evsql.QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES (NOW(),'Web error page MySQL Error','"+self.screen_error+"','unlock','webfilter-http','138','0')")
            return self.DisplayError()
        
        self.addTocat=self.addTocat.strip()
        if len(self.addTocat)>2:
            SYSTEMID=GET_INFO_STR("SYSTEMID")
            xcat=self.CategoryToTableName(self.addTocat)
            ymd5=hashlib.md5(self.addTocat+familysite).hexdigest()
            self.q.QUERY_SQL("INSERT IGNORE INTO "+xcat+" (zmd5,zDate,category,pattern,uuid) VALUES('"+ymd5+"',NOW(),'"+self.addTocat+"','"+familysite+"','"+SYSTEMID+"')")
            if not self.q.ok:
                self.screen_error="Database Error: "+self.q.mysql_error
                evsql=ArticaEvents(logging)
                evsql.QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES (NOW(),'Web error page MySQL Error','"+self.screen_error+"','unlock','webfilter-http','138','0')")
            
            
        
        if  self.notify==1:
            evsql=ArticaEvents(logging)
            evsql.QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES (NOW(),'UFDB-HTTP service report','Unlock "+familysite+" For "+self.ipaddr+" categorized as "+self.category+"','unlock','webfilter-http','142','0')")
        
        os.system('/usr/bin/php /usr/share/artica-postfix/exec.ufdb.queue.release.php --force')
        indexContent=self.read_file("redirect.html")
        indexContent=self.replace_tokens(indexContent)
        return indexContent
        
        pass
           
#-------------------------------------------------------------------------------------------------------------------------------------      
    def DisplayError(self):
        indexContent=self.read_file("error.html")
        indexContent=self.replace_tokens(indexContent)
        return indexContent
        pass
#-------------------------------------------------------------------------------------------------------------------------------------      
    def CategoryToTableName(self,cat):
        cat=cat.replace("-","_")
        cat=cat.replace("/","_")
        return "category_"+cat
        pass
 #-------------------------------------------------------------------------------------------------------------------------------------                        
    
    
    @cherrypy.expose
    def css(self, **keywords):
        self.SubFolder=keywords["zmd5"]
        content= self.read_resource("index.css")
        return content
        pass
    
    @cherrypy.expose
    def images(self, **keywords):
        filename=keywords["picture"]
        return serve_file("/usr/share/artica-postfix/img/"+filename, '', "attachment",filename)
        pass
    
    @cherrypy.expose
    def favicon_ico(self, **keywords):
        return serve_file("/usr/share/artica-postfix/img/favicon-webf.ico")
        pass
    
        
        
    
    @cherrypy.expose
    def resource(self, **keywords):
        SubFolder=keywords["zmd5"]
        fname=keywords["fname"]
        fcontent=file_get_contents("/home/ufdb-templates/"+SubFolder+"/files/"+fname+".type")
        if self.debug: cherrypy.log("resource(): Server "+fname +"("+fcontent+")","WEBFILTER")
        if self.debug: cherrypy.log("resource(): Server "+fname +"("+fcontent+")","WEBFILTER")
        return serve_file("/home/ufdb-templates/"+SubFolder+"/files/"+fname, fcontent, "attachment",fname)
        pass
    
    
    @cherrypy.expose
    def js(self, **keywords):
        script=keywords["script"]
        return serve_file("/usr/share/artica-postfix/js/"+script)
        pass
    
   
    def hidden_fields(self):
        form=[]
        form.append("<input type='hidden' name='clientname' value='"+self.hostname+"'>")
        form.append("<input type='hidden' name='rule-id' value='"+str(self.ruleid)+"'>")
        form.append("<input type='hidden' name='clientaddr' value='"+self.ipaddr+"'>")
        form.append("<input type='hidden' name='clientgroup' value='"+self.rulename+"'>")
        form.append("<input type='hidden' name='targetgroup' value='"+self.category+"'>")
        form.append("<input type='hidden' name='clientuser' value='"+self.uid+"'>")
        form.append("<input type='hidden' name='SquidGuardIPWeb' value='"+self.SquidGuardIPWeb+"'>")
        form.append("<input type='hidden' name='url' value='"+self.url+"'>")
        form.append("<input type='hidden' name='SubFolder' value='"+self.SubFolder+"'>")
        if len(str(self.addTocat)) > 0 : form.append("<input type='hidden' name='addTocat' value='"+str(self.addTocat)+"'>")
        return "\n".join(form)
        pass


#-------------------------------------------------------------------------------------------------------------------------------------
    def GetCategoriesNames(self):
        if not os.path.exists("/home/ufdb-templates/CATEGORIES_NAMES"):
            cherrypy.log("GetCategoriesNames(): /home/ufdb-templates/CATEGORIES_NAMES no such file","WEBFILTER")
            return False
        
        with open("/home/ufdb-templates/CATEGORIES_NAMES","r") as f:
            for txt in f :
                txt=txt.rstrip('\n')
                if len(txt)<5: next
                matches=txt.split("|")
                try:
                    self.CATEGORYNAMES[matches[0]]=matches[1]
                except:
                    cherrypy.log("GetCategoriesNames(): Error reading CATEGORIES_NAMES file","WEBFILTER")
                    continue
#-------------------------------------------------------------------------------------------------------------------------------------        

    def GetSubFolder(self):
        
        if not os.path.exists("/home/ufdb-templates/NETWORKS"):
            if self.debug: cherrypy.log("GetSubFolder(): /home/ufdb-templates/NETWORKS no such file","WEBFILTER")
            return False
        
        if os.path.exists("/home/ufdb-templates/NETWORKS"):
            with open("/home/ufdb-templates/NETWORKS","r") as f:
                for txt in f :
                    txt=txt.rstrip('\n')
                    if len(txt)<5: next
                    #$NETS[]="$network|$category|$webruleid|$username|$adgroup|{$ligne["zmd5"]}";
                    matches=txt.split("|")
                    try:
                        Network=matches[0]
                        category=matches[1]
                        webruleid=int(matches[2])
                        match_user=matches[3]
                        match_group=matches[4]
                        zmd5=matches[5]
                    except:
                        continue
                    

                    
                    
                    if self.debug: cherrypy.log("GetSubFolder(): Rule ID.: "+str(webruleid)+" / Current:" +str(self.ruleid),"WEBFILTER")
                    try:
                        if self.debug: cherrypy.log("GetSubFolder(): Network.: "+Network+" / " +self.ipaddr,"WEBFILTER")
                    except:
                        if self.debug: cherrypy.log("GetSubFolder(): Fatal error on 633 line")
                    try:
                        if self.debug: cherrypy.log("GetSubFolder(): category: "+category +" / "+self.category,"WEBFILTER")
                    except:
                        if self.debug: cherrypy.log("GetSubFolder(): Fatal error on 636 line")                        
                    try:
                        if self.debug: cherrypy.log("GetSubFolder(): Username: "+match_user +" / "+self.uid,"WEBFILTER")
                    except:
                        if self.debug: cherrypy.log("GetSubFolder(): Fatal error on 640 line")                    
                    try:
                        if self.debug: cherrypy.log("GetSubFolder(): Group...: "+match_group +" / "+self.uid,"WEBFILTER")
                    except:
                        if self.debug: cherrypy.log("GetSubFolder(): Fatal error on 644 line")                    
                    try:
                        if self.debug: cherrypy.log("GetSubFolder(): SubFolder: "+zmd5,"WEBFILTER")
                    except:
                        if self.debug: cherrypy.log("GetSubFolder(): Fatal error on 648 line")                    
                    
                    if webruleid == 0: webruleid=self.ruleid
                    if category =="*":category = self.category
                    if match_user=="*":match_user=self.uid
                    if match_user=="":match_user=self.uid
                    if match_group=="*":match_group=""
                    
                    UidLog = self.safe_str(self.uid)
                              
                    
                    if self.debug: cherrypy.log("GetSubFolder(): IF MATCH "+ str(self.ruleid) +" --> "+str(webruleid),"WEBFILTER")
                    if webruleid == self.ruleid:
                        if self.debug: cherrypy.log("GetSubFolder(): MATCH "+ str(self.ruleid),"WEBFILTER")
                        try:
                            if IPAddress(self.ipaddr) in IPNetwork(Network):
                                if self.debug: cherrypy.log("GetSubFolder(): MATCH "+ str(self.ipaddr),"WEBFILTER")
                                
                                if len(match_group)>1:
                                    match_user=self.uid
                                    if not self.checkgroup(self.uid,match_group): return False
                                        
                                
                                if match_user==self.uid:
                                    if self.debug: cherrypy.log("GetSubFolder(): MATCH "+ str(UidLog),"WEBFILTER")
                                    if category == self.category:
                                        self.SubFolder=zmd5
                                        if self.debug: cherrypy.log("GetSubFolder(): matches "+zmd5,"WEBFILTER")
                                        return True
                        except:
                            cherrypy.log("GetSubFolder(): FATAL ERROR ON LINE 655")
                            return True
                        
        if self.debug: cherrypy.log("GetSubFolder(): return default","WEBFILTER")
        self.SubFolder="default"
        pass
#-------------------------------------------------------------------------------------------------------------------------------------
    def safe_str(self,obj):
        try:
            return str(obj)
        except UnicodeEncodeError:
            return unicode(obj).encode('unicode_escape')
    pass
    
    def checkgroup(self,uid,groupname):
        if not os.path.exists("/home/ufdb-templates/GROUPS"): return False
        uid=uid.lower()
        groupname=groupname.lower()
        
        with open("/home/ufdb-templates/GROUPS","r") as f:
            for txt in f :
                txt=txt.rstrip('\n')
                if txt.find("|")==0: continue
                try:
                    matches=txt.split("|")
                    username=matches[0]
                    group=matches[1]
                except:
                    continue
                if username==uid:
                    if groupname==group: return True
        return False
        pass
#-------------------------------------------------------------------------------------------------------------------------------------    
                
    def GetWebPath(self):
        hostname=cherrypy.request.local.name
        currentport=cherrypy.request.local.port
        scheme=cherrypy.request.scheme
        return scheme+"://"+hostname+":"+str(currentport)+"/"

    def read_file(self,filename):
        filename="/home/ufdb-templates/"+str(self.SubFolder)+"/"+filename
        if self.debug: cherrypy.log("open: " + filename,"WEBFILTER")
        if not os.path.exists(filename): return ""
        with io.open(filename, "r", encoding="ISO-8859-1") as my_file: return my_file.read()
        pass
    
    def read_resource(self,filename):
        filename="/home/ufdb-templates/"+str(self.SubFolder)+"/files/"+filename
        if self.debug: cherrypy.log("open: " + filename,"WEBFILTER")
        if not os.path.exists(filename): return ""
        with io.open(filename, "r", encoding="ISO-8859-1") as my_file: return my_file.read()
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
 #-------------------------------------------------------------------------------------------------------------------------------------   
    def BadAgents(self):
        if self.debug: cherrypy.log("BAD AGENT starting...","WEBFILTER")
        self.UserAgent=""
        try:
            self.UserAgent = cherrypy.request.headers["User-Agent"]
        except:
            if self.debug: cherrypy.log("BadAgents() exception...","WEBFILTER")
            return False
        
        if len(self.UserAgent)==0:
            if self.debug: cherrypy.log("BAD AGENT " + self.UserAgent + " [length ==0]","USERAGENT")
            return False
        if self.UserAgent =="Microsoft NCSI":
            if self.debug: cherrypy.log("BAD AGENT Microsoft NCSI ","USERAGENT")
            return True
        matches=re.search("Firefox\/",self.UserAgent)
        if matches: return False
        matches=re.search("Chrome\/",self.UserAgent)
        if matches: return False
        matches=re.search("Safari\/",self.UserAgent)
        if matches: return False        
        matches=re.search("Edge\/",self.UserAgent)
        if matches: return False
        matches=re.search("OPR\/",self.UserAgent)
        if matches: return False
        matches=re.search("MSIE [0-9]+",self.UserAgent)
        if matches: return False
        matches=re.search("Trident\/",self.UserAgent)
        if matches: return False
                
        if self.debug: cherrypy.log("BAD AGENT "+self.UserAgent+" not detected","USERAGENT")
        return True
        pass
#-------------------------------------------------------------------------------------------------------------------------------------    
    def replace_tokens(self,content):
        now = datetime.now()
        CurrentTime=int(now.strftime("%s"))
        content=content.replace("%URL%",self.url)
        content=content.replace("%CATEGORY%",self.category)
        content=content.replace("%CATEGORYTYPE%",self.category_type)
        content=content.replace("%VISIBLEHOSTNAME%",self.visible_hostname)
        content=content.replace("%ARTICAVER%",self.artica_version)
        content=content.replace("%cache_mgr_user%",self.cache_mgr_user)
        content=content.replace("%MEMBER%",self.uid +" "+self.ipaddr+"("+self.hostname+")")
        content=content.replace("%HIDDENFIELDS%",self.hidden_fields())
        content=content.replace("%FATAL_ERROR%",self.screen_error)
        content=content.replace("%TIME%",str(CurrentTime))
        content=content.replace("%FAMILIYSITE%",self.familysite)
        content=content.replace("%WEBPATH%",self.WEBPATH)
        content=content.replace("%CALCID%",str(self.ruleid))
        content=content.replace("%CHARTCONTENT%",str(self.ChartContent))
        content=content.replace("{free_edition}","Free edition")
        
        
        rulename=file_get_contents("/home/ufdb-templates/rule_"+str(self.ruleid))
        if len(rulename)==0: rulename="Unknown"
        if self.debug: cherrypy.log("replace_tokens(): /home/ufdb-templates/rule_"+str(self.ruleid)+" == "+rulename,"WEBFILTER")
        if len(self.screen_error)>2: rulename=rulename+"<br><strong style='font-size:22px'>"+self.screen_error+"</strong>"
        content=content.replace("%RULENAME%",rulename)
              
        return content
        pass
#-------------------------------------------------------------------------------------------------------------------------------------
        
    def CategoryCodeToCatName(self):
        CatInt=0
        self.category_type="&nbsp;"
        if len(self.category)==0:
            self.category="Unknown"
            self.category_type="(Specific Rule)"
            return True
        
        if type(self.category) is list: self.category=' '.join(self.category)
        
        
        matches=re.search("^P([0-9]+)",self.category)
        if matches:
            CatInt=matches.group(1)
            if CatInt in self.CATEGORYNAMES:
                self.category=self.CATEGORYNAMES[CatInt]
        
        if CatInt==0: self.category="Unknown"
       
        pass
    

         
        

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
#-------------------------------------------------------------------------------------------------------------------------------------
if __name__ == '__main__':
    

    http_port=GET_INFO_INT("SquidGuardApachePort")
    ssl_port=GET_INFO_INT("SquidGuardApacheSSLPort")
    SquidGuardDenySSL=GET_INFO_INT("SquidGuardDenySSL")
    SQUIDEnable=GET_INFO_INT("SQUIDEnable")
    PDSNInUfdb=GET_INFO_INT("PDSNInUfdb")
    SquidGuardApacheThreads=GET_INFO_INT("SquidGuardApacheThreads")
    SquidGuardApacheIP=GET_INFO_STR("SquidGuardApacheIP")
    if len(SquidGuardApacheIP)==0:SquidGuardApacheIP="0.0.0.0"
    
    SSLCertificateFile=file_get_contents("/home/ufdb-templates/SSLCertificateFile")
    SSLCertificateKeyFile=file_get_contents("/home/ufdb-templates/SSLCertificateKeyFile")
    SSLCertificateChainFile=file_get_contents("/home/ufdb-templates/SSLCertificateChainFile")

    if SquidGuardApacheThreads == 0: SquidGuardApacheThreads=30
    if http_port == 0: http_port=9020
    if ssl_port == 0: ssl_port=9025
    if not os.path.exists("/etc/artica-postfix/STATS_APPLIANCE"):
        if SQUIDEnable==0:
            if PDSNInUfdb==1:
                SquidGuardDenySSL=0
                http_port=80
                ssl_port=443
                print "Proxy is disabled but DNS Filter is enabled, use 80/443"
            

    cherrypy.tree.mount(RootServer())
    cherrypy.server.unsubscribe()
    PIDFile(cherrypy.engine, "/var/run/webfilter-http.pid").subscribe()
  

    if SquidGuardDenySSL==0:
        print "SSL is enabled "+SSLCertificateFile+" - "+SSLCertificateKeyFile
        server1 = cherrypy._cpserver.Server()
        server1.socket_port=ssl_port
        server1._socket_host=SquidGuardApacheIP
        server1.thread_pool=SquidGuardApacheThreads
        server1.ssl_module = 'pyopenssl'
        server1.ssl_certificate = SSLCertificateFile
        server1.ssl_private_key = SSLCertificateKeyFile
        if len(SSLCertificateChainFile)>0: server1.ssl_certificate_chain=SSLCertificateChainFile
        cherrypy.config.update({'error_page.404': http_error_404_hander,})
        #server1.ssl_certificate_chain = '/home/ubuntu/gd_bundle.crt'
        server1.subscribe()

    server2 = cherrypy._cpserver.Server()
    server2.socket_port=http_port
    server2._socket_host=SquidGuardApacheIP
    server2.thread_pool=SquidGuardApacheThreads
    server2.subscribe()

    cherrypy.engine.start()
    cherrypy.engine.block()