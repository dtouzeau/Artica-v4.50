#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import os
import logging
import tailer
import hashlib
import re
from daemon import runner
import tldextract
import urllib
from postgressql import *
from categories import *
from unix import *
import datetime
import time
global DEBUG


class App():

    def __init__(self):
        self.stdin_path = '/dev/null'
        self.stdout_path = '/dev/null'
        self.stderr_path = '/dev/null'
        self.pidfile_path = '/var/run/squid-tail.pid'
        self.pidfile_timeout = 5
        self.RequestNumber=0
        self.UsersHash={}
        self.CountTotal=0
        self.debug=False
        self.Counters={}
        self.MinSizeToLog=0
        self.MinSizeToLogEnable=0
        self.UserAgentInStats=0
        

    def run(self):
        logger.info('/var/log/squid/squidtail.debug')
        pgsql=Postgres()
        pgsql.log=logger
        
        catz=Categories()
        catz.Debug=False
        catz.log=logger
        self.RotateTime=int(time.time())
        self.UserAgentInStats=GET_INFO_INT("UserAgentInStats")
        self.MinSizeToLog=GET_INFO_INT("MinSizeToLog")
        self.MinSizeToLogEnable=GET_INFO_INT("MinSizeToLogEnable")
        file_put_contents("/etc/artica-postfix/settings/Daemons/SquidTailfsize",str( os.path.getsize('/var/log/squid/squidtail.log')))
        logger.info('Starting.....: UserAgent in stats: '+str(self.UserAgentInStats))
        if self.MinSizeToLog==0: self.MinSizeToLog=2097152

        for line in tailer.follow(open('/var/log/squid/squidtail.log')):
            try:
                self.parseline(line,pgsql,catz)
            except Exception as error:
                logger.info('Crash while parsing line')
                logger.exception(error)
            
            
            
    def parseline(self,line,pgsql,catz):
        line = line.strip()

        if self.Dustbin(line):
            return
        
        if self.debug:
            logger.info('Receive:'+line+'"')
        
        self.Explode(line,pgsql,catz)
        pass
    
    def IfCached(self,PROTO):
        if PROTO.find(':')>0:
            zPROTO=PROTO.split(':')
            PROTO=zPROTO[0]
            
        HITS={}
        HITS["TCP_ASYNC_HIT"]=True;
        HITS["TCP_DENIED"]=True;
        HITS["TCP_HIT"]=True;
        HITS["TCP_IMS_HIT"]=True;
        HITS["TCP_MEM_HIT"]=True;
        HITS["TCP_MISS_ABORTED"]=True;
        HITS["TCP_OFFLINE_HIT"]=True;
        HITS["TCP_REDIRECT"]=True;
        HITS["TCP_REFRESH_FAIL_HIT"]=True;
        HITS["TCP_REFRESH_HIT"]=True;
        HITS["TCP_REFRESH_MISS"]=True;
        HITS["TCP_REFRESH_MODIFIED"]=True;
        HITS["TCP_REFRESH_UNMODIFIED"]=True;
        HITS["TAG_NONE"]=True;
        HITS["TCP_STALE_HIT"]=True;
        HITS["UDP_HIT"]=True;
        HITS["UDP_DENIED"]=True;
        HITS["UDP_INVALID"]=True;
        
        if HITS.has_key(PROTO):
            return 1
        return 0
        pass
    
    
    
    def Explode(self,line,pgsql,catz):
        array=line.split(":::")
        mac=array[0].strip()
        ipaddr=array[1].strip()
        uid=array[2].strip()
        uid2=array[3].strip()
        zdate=array[4].strip()
        proto=array[5].strip()
        uri=array[6].strip()
        sqlF=''
        sqlE=''
        
        if len(array)<9:
            logger.info('Error: index out of range:"'+line+'"')
            return
            
        code_error=array[8].strip()
        size=array[9].strip()
        squid_code=array[10].strip()
        UserAgent=urllib.unquote_plus(array[11])
        Forwarded=array[12].strip()
        sitename=array[13].strip()
        hostname=array[14].strip()
        response_time=array[15].strip()
        MimeType=array[16].strip()
        sni=array[17].strip()
        proxyname=array[18].strip()
        try:
            ougroup=array[19].strip()
        except:
            logger.info('Error: index out of range: ougroup --> "'+line+'"')
            ougroup=""
            
            
        size=int(size)
        
        if int(code_error) == 302:
            return
        
        if int(code_error) == 301:
            return
        
        if size == 0: return
        
        MinutesDiff=difference_minutes(self.RotateTime)
        sitename=sitename.lower()
        if sitename =='127.0.0.1': return
        if sitename =='-': sitename=''
        if sni == '-': sni=''
        
        if len(sitename)==0 :
            if len(sni)>0 :sitename=sni
            
        if len(sitename)==0 : return
        
        if mac.find("):") > 0:
            matches=re.search('([0-9a-z]+):([0-9a-z]+):([0-9a-z]+):([0-9a-z]+):([0-9a-z]+):([0-9a-z]+)$',mac)
            if matches: mac=matches.group(1)+':'+matches.group(2)+':'+matches.group(3)+':'+matches.group(4)+':'+matches.group(5)+':'+matches.group(6)        
        

            
        
        mac=mac.lower()
        ipaddr=ipaddr.lower()
        uid=uid.lower()
        uid2=uid2.lower()
        mac=mac.replace('-',':')
        
        if Forwarded=="unknown": Forwarded=''
        if Forwarded=="-": Forwarded=''
        if Forwarded=='0.0.0.0': Forwarded=''
        if Forwarded=='255.255.255.255': Forwarded=''
        if mac =='-': mac='00:00:00:00:00:00'
        if uid =='-': uid=''
        if uid2 =='-': uid2=''
        if is_valid_ip(uid2): uid2=''

        if uid == '':
            if uid2 and len(uid2) > 0:
                uid=uid2

        if proxyname.find('=') > 0:
            zproxyname=proxyname.split("=")
            proxyname=zproxyname[1]
            
        if sitename.find(":") > 0:
            zsitename=sitename.split(":")
            sitename=zsitename[0]
            
            
        self.RequestNumber=self.RequestNumber+1
        
        if len(uid)>0:
            uid=urllib.unquote(uid)
            uid=uid.replace('$','')
            
            
        familysite=sitename   
        xtime=datetime.datetime.now()
        SUFFIX_DATE=datetime.datetime.now().strftime("%Y%m%d%H")
        logzdate=datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        zyear=datetime.datetime.now().strftime("%Y")
        zday=datetime.datetime.now().strftime("%d")
        zmonth=datetime.datetime.now().strftime("%m")
        zhour=datetime.datetime.now().strftime("%H")
        microsecond = datetime.datetime.now().microsecond
        
        Forwarded=Forwarded.replace("%25", "")    
        cached=self.IfCached(squid_code)
        KEY_USER=''
        
        
        if len(Forwarded)>4:
            ipaddr=Forwarded
            mac='00:00:00:00:00:00'
            
            
        MAC_KEY=mac
        
        if MAC_KEY == '00:00:00:00:00:00':
            MAC_KEY=''
            
        if proto =='CONNECT':
            uri='https://'+uri
            
        if not is_valid_ip(ipaddr):
             logger.info('Invalid ip addr '+ipaddr+' "'+line+'"')
             return None
            
            
        if not is_valid_ip(sitename):
            ext = tldextract.extract(uri)
            familysite=ext.domain+'.'+ext.suffix
            
        category=catz.GET_CATEGORY(sitename)
        if self.debug:
            logger.info('mac........: "'+mac+'"')
            logger.info('uid........: "'+uid+'"')
            logger.info('ipaddr.....: "'+ipaddr+'"')
            logger.info('sitename...: "'+sitename+'/'+familysite+'"')
            logger.info('size.......: "'+str(size)+' bytes"')
            logger.info('proto......: "'+proto+'"')
            logger.info('SUFFIX_DATE: "'+SUFFIX_DATE+'"')
            logger.info('logzdate...: "'+logzdate+'"')
            logger.info('ProxyName..: "'+proxyname+'"')
            logger.info('Cached.....: "'+str(cached)+'/'+squid_code+'"')
            logger.info('Category...: "'+category+'"')
            logger.info('Schedule...: "'+str(MinutesDiff)+'mn"')
        
        tablename="access_"+SUFFIX_DATE
        tableagent="useragenttemp_"+SUFFIX_DATE
        TableCreate='CREATE TABLE IF NOT EXISTS "'+tablename+'" (zdate timestamp,mac macaddr,ipaddr INET,proxyname VARCHAR(128) NOT NULL,category VARCHAR(64) NULL,sitename VARCHAR(128) NULL,FAMILYSITE VARCHAR(128) NULL,USERID VARCHAR(64) NULL,SIZE BIGINT,RQS BIGINT)'
        TableAgent='CREATE TABLE IF NOT EXISTS "'+tableagent+'" (zdate timestamp,mac macaddr,proxyname VARCHAR(128) NOT NULL,familysite VARCHAR(128) NULL,userid VARCHAR(64) NULL,useragent VARCHAR(128) NULL,size BIGINT,rqs BIGINT)'
        TableUsers='CREATE TABLE IF NOT EXISTS "access_users" (zdate timestamp,userid VARCHAR(64) NULL,size BIGINT,rqs BIGINT)'
        
        sqlA=""
        sqlE=""
        sqlF=""
        sqlG=""
       
        
        sql='INSERT INTO "'+tablename+'" (zdate,mac,ipaddr,proxyname,category,sitename,familysite,userid,size,rqs)'
        sql=sql+"VALUES ('"+logzdate+"','"+mac+"','"+ipaddr+"','"+proxyname+"','"+category+"',"
        sql=sql+"'"+sitename+"','"+familysite+"','"+uid+"','"+str(size)+"','1');"
        
        access_big="CREATE TABLE IF NOT EXISTS access_big (zDate timestamp,zmd5 VARCHAR(32),mac macaddr,ipaddr INET,proxyname VARCHAR(128) NOT NULL,category VARCHAR(64) NULL, url VARCHAR(512) NULL,familysite VARCHAR(128) NULL,userid VARCHAR(64) NULL, size BIGINT )";
        
        if self.UserAgentInStats==1:
            sqlA='INSERT INTO "'+tableagent+'" (zdate,mac,proxyname,userid,useragent,familysite,size,rqs)'
            sqlA=sqlA+"VALUES ('"+logzdate+"','"+mac+"','"+proxyname+"',"
            sqlA=sqlA+"'"+uid+"','"+UserAgent+"','"+familysite+"','"+str(size)+"','1');"
            
        
        
        if len(uid)>2:
            KEY_USER=uid
            
        if len(KEY_USER) == 0:
            if len(MAC_KEY)>4:
                KEY_USER=MAC_KEY
        
        if len(KEY_USER) == 0:
            KEY_USER=ipaddr
    
        if self.debug: logger.info('User choose...: "'+KEY_USER+'"')
        if not self.UsersHash.has_key(KEY_USER): self.UsersHash[KEY_USER]=1
        
        sqlG='INSERT INTO "access_users" (zdate,userid,size,rqs)'
        sqlG=sqlG+"VALUES ('"+logzdate+"','"+KEY_USER+"',"+str(size)+",1)"
            
        self.CountTotal=self.CountTotal+size
        
        if self.MinSizeToLogEnable==1:
            if size>self.MinSizeToLog:
                zmd5=hashlib.md5( str(logzdate)+str(uid)+str(size)+uri+ipaddr+mac).hexdigest()
                sqlF='INSERT INTO "access_big" (zdate,zmd5,mac,ipaddr,proxyname,category,url,familysite,userid,size)'
                sqlF=sqlF+"VALUES ('"+logzdate+"','"+zmd5+"','"+mac+"','"+ipaddr+"','"+proxyname+"','"+category+"',"
                sqlF=sqlF+"'"+uri+"','"+familysite+"','"+uid+"','"+str(size)+"');"
                
        
        zmd5=hashlib.md5( str(xtime)+str(cached) +str(microsecond)+str(size)).hexdigest()
        sqlB="INSERT INTO rttable_icache (zmd5,zday,zmonth,zyear,zhour,cached,size) VALUES('"+zmd5+"','"+str(zday)+"','"+str(zmonth)+"','"+str(zyear)+"','"+str(zhour)+"','"+str(cached)+"','"+str(size)+"')"
        
        zmd5=hashlib.md5( str(xtime)+str(size) +str(microsecond)+str(size)+ipaddr+uid+mac).hexdigest()
        sqlC="INSERT INTO rttable_users (zmd5,zday,zmonth,zyear,zhour,ipaddr,userid,MAC,size) VALUES ('"+zmd5+"','"+str(zday)+"','"+str(zmonth)+"','"+str(zyear)+"',"+str(zhour)+",'"+ipaddr+"','"+uid+"','"+mac+"','"+str(size)+"')"
        
        zmd5=hashlib.md5( str(xtime)+str(size) +str(microsecond)+str(size)+familysite+category).hexdigest()
        sqlD="INSERT INTO rttable_domains (zmd5,zday,zmonth,zyear,zhour,domain,category,size) VALUES ('"+zmd5+"','"+str(zday)+"','"+str(zmonth)+"','"+str(zyear)+"',"+str(zhour)+",'"+familysite+"','"+category+"','"+str(size)+"')"        
                    
                    
        if MinutesDiff > 0:
            file_put_contents("/etc/artica-postfix/settings/Daemons/SquidTailPing",xtime)
            logger.info('Requests: '+str(self.RequestNumber)+' Size:'+str(self.CountTotal)+' Users:'+str(len(self.UsersHash)))
            sqlE="INSERT INTO squidtail (zdate, proxyname,size,rqs,users) VALUES ('"+logzdate+"','"+proxyname+"','"+str(self.CountTotal)+"','"+str(self.RequestNumber)+"','"+str(len(self.UsersHash))+"')"
            self.CountTotal=0
            self.RequestNumber=0
            self.UsersHash={}
            self.RotateTime=int(time.time())                    
        
        if not pgsql.connect():
            logger.info('Failed to connect to PostGresSQL server')
            self.failed_request(TableCreate)
            self.failed_request(sql)
            self.failed_request(sqlB)
            self.failed_request(sqlC)
            self.failed_request(sqlD)
            self.failed_request(sqlG)
            if len(sqlE)>5: self.failed_request(sqlE)
            if len(sqlF)>10: self.failed_request(sqlF)
            
            if self.UserAgentInStats==1:
                self.failed_request(TableAgent)
                self.failed_request(sqlA)
            return None
            
        pgsql.QUERY_SQL(sql)
        if not pgsql.ok:
            logger.info('Catch Error "'+pgsql.sql_error+'"')
            matches=re.search('relation.*?does not exist',pgsql.sql_error)
            if matches:
                logger.info('CREATE TABLE "'+tablename+'"')
                pgsql.QUERY_SQL(TableCreate)
                pgsql.QUERY_SQL(sql)
            
            if not pgsql.ok:
                self.failed_request(TableCreate)
                self.failed_request(sql)

        if self.UserAgentInStats==1:
            pgsql.QUERY_SQL(sqlA)
            if not pgsql.ok:
                matches=re.search('relation.*?does not exist',pgsql.sql_error)
                if matches:
                    pgsql.QUERY_SQL(TableAgent)
                    pgsql.QUERY_SQL(sqlA)
                
            if not pgsql.ok:
                self.failed_request(TableAgent)
                self.failed_request(sqlA)                
                


        pgsql.QUERY_SQL(sqlB)
        if not pgsql.ok: self.failed_request(sqlB)
            
        pgsql.QUERY_SQL(sqlC)
        if not pgsql.ok: self.failed_request(sqlC)
            

        pgsql.QUERY_SQL(sqlD)
        if not pgsql.ok: self.failed_request(sqlD)
            
        if len(sqlE)>5:
            pgsql.QUERY_SQL(sqlE)
            if not pgsql.ok: self.failed_request(sqlE)
            
        if len(sqlF)>10:
            pgsql.QUERY_SQL(sqlF)
            if not pgsql.ok:
                 matches=re.search('relation.*?does not exist',pgsql.sql_error)
                 if matches:
                    pgsql.QUERY_SQL(access_big)
                    pgsql.QUERY_SQL(sqlF)
            
            if not pgsql.ok: self.failed_request(sqlF)

        
        pgsql.QUERY_SQL(sqlG)
        if not pgsql.ok:
            matches=re.search('relation.*?does not exist',pgsql.sql_error)
            if matches:
                pgsql.QUERY_SQL(TableUsers)
                pgsql.QUERY_SQL(sqlG)
        if not pgsql.ok: self.failed_request(sqlG)
                
            
      
            
        pgsql.disconnect()

        pass
    
    def failed_request(self, sql):
        filename=hashlib.md5(sql).hexdigest()
        filename=filename+".error"
        mkdir("/home/squid/PostgreSQL-failed",0755)
        filepath="/home/squid/PostgreSQL-failed/"+filename
        try:
            io=open(filepath, "a")
        except:
            return
        io.write(sql+"\n")
        io.close()
        
        pass
    


    def Dustbin(self,line):
        if line =='':
            return True
        if line.find(":::HEAD:::") > 0:
            return True
        if line.find("NONE:HIER_NONE") > 0:
            return True
        if line.find("error:invalid-request") > 0:
            return True
        if line.find("NONE error:") > 0:
            return True
        if line.find("GET cache_object") > 0:
            return True    
        if line.find("cache_object://") > 0:
            return True     
    
        pass
            

          
app = App()
app.debug=False
logger = logging.getLogger("squidtail")
logger.setLevel(logging.INFO)
formatter = logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s")
handler = logging.FileHandler("/var/log/squid/squidtail.debug")
handler.setFormatter(formatter)
logger.addHandler(handler)
daemon_runner = runner.DaemonRunner(app)
#This ensures that the logger file handle does not get closed during daemonization
daemon_runner.daemon_context.files_preserve=[handler.stream]
daemon_runner.do_action()