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
from urlparse import urlparse
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
        self.pidfile_path = '/var/run/hypercache-tail.pid'
        self.pidfile_timeout = 5
        self.RequestNumber=0
        self.UsersHash={}
        self.CountTotal=0
        self.debug=True
        self.Counters={}
        

    def run(self):
        logger.info('/var/log/hypercache-service/hypercachetail.debug')
        pgsql=Postgres()
        pgsql.log=logger
        
        catz=Categories()
        catz.Debug=False
        catz.log=logger
        self.RotateTime=int(time.time())
        file_put_contents("/etc/artica-postfix/settings/Daemons/HyperCacheTailfsize",str( os.path.getsize('/var/log/hypercache-service/access.log')))

        for line in tailer.follow(open('/var/log/hypercache-service/access.log')):
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
        HITS["HIT"]=True;
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
        
        matches=re.search('([0-9\.]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+([A-Z]+)\/([0-9]+)\s+([0-9]+)\s+([A-Z]+)\s+"(.+?)"',line)
        if not matches: return
        
        Logtime=matches.group(1)
        HIT=self.IfCached(matches.group(4))
        HTTP_CODE=matches.group(5)
        bytes_sent=int(matches.group(6))
        proto=matches.group(7)
        uri=matches.group(8)

        parsed = urlparse(uri)
        sitename=parsed.hostname
        if sitename.find(":") > 0:
            zsitename=sitename.split(":")
            sitename=zsitename[0]
        
        sitename=sitename.lower()
        
        if self.debug: logger.info(sitename+" "+str(bytes_sent)+"Bytes cached="+str(cached))
        
        if sitename =='127.0.0.1': return
        if sitename =='-': sitename=''
        if sitename == "": return     
        familysite=sitename
        
        if not is_valid_ip(sitename):
            ext = tldextract.extract(uri)
            familysite=ext.domain+'.'+ext.suffix
        
        xtime=datetime.datetime.now()
        SUFFIX_DATE=datetime.datetime.now().strftime("%Y%m%d%H")
        logzdate=datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        zyear=datetime.datetime.now().strftime("%Y")
        zday=datetime.datetime.now().strftime("%d")
        zmonth=datetime.datetime.now().strftime("%m")
        zhour=datetime.datetime.now().strftime("%H")
        microsecond = datetime.datetime.now().microsecond

        category=catz.GET_CATEGORY(sitename)
        
        tablename="temphypercache_"+SUFFIX_DATE
        
        
        sql='INSERT INTO "'+tablename+'" (zdate,category,familysite,cached,size,rqs)'
        sql=sql+"VALUES ('"+logzdate+"','"+category+"',"
        sql=sql+"'"+familysite+"','"+str(HIT)+"','"+str(bytes_sent)+"','1');"
               
        TableCreate='CREATE TABLE IF NOT EXISTS "'+tablename+'" (zdate timestamp,cached INT,category VARCHAR(64) NULL,familysite VARCHAR(128) NULL,size BIGINT,RQS BIGINT)'
        if pgsql.connect():
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

            pgsql.disconnect()
        else:
            logger.info('Failed to connect to PostGresSQL server')
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
handler = logging.FileHandler("/var/log/hypercache-service/hypercachetail.debug")
handler.setFormatter(formatter)
logger.addHandler(handler)
daemon_runner = runner.DaemonRunner(app)
#This ensures that the logger file handle does not get closed during daemonization
daemon_runner.daemon_context.files_preserve=[handler.stream]
daemon_runner.do_action()