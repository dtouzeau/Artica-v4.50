#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')

import os
import logging
import hashlib
import re
from daemon import runner
import urllib
from postgressql import *
from unix import *
import datetime
import time
import pycurl

# --------------------------------------------------------------------------------------------------------
class App():

    def __init__(self):
        self.stdin_path = '/dev/null'
        self.stdout_path = '/dev/null'
        self.stderr_path = '/dev/null'
        self.pidfile_path = '/var/run/wsus-http/scheduler.pid'
        self.logger=object
        self.pidfile_timeout = 5
        self.RequestNumber=0
        self.UsersHash={}
        self.zHeads={}
        self.CountTotal=0
        self.TempMD5=''
        self.debug=False
        self.POSTGRES=Postgres()
        self.POSTGRES.log=self.logger
        self.listen_interface=''
        self.SquidMgrListenPort=GET_INFO_INT("SquidMgrListenPort")
        self.WindowsUpdateMaxToPartialQueue=GET_INFO_INT("WindowsUpdateMaxToPartialQueue")
        self.WindowsUpdateBandwidthPartial=GET_INFO_INT("WindowsUpdateBandwidthPartial")
        self.WindowsUpdateBandwidth=GET_INFO_INT("WindowsUpdateBandwidth")
        self.www_root=GET_INFO_STR("WindowsUpdateCachingDir")
        self.WindowsUpdateMaxRetentionTime=GET_INFO_INT("WindowsUpdateMaxRetentionTime")
        self.WindowsUpdateBandwidthMaxFailed=GET_INFO_INT("WindowsUpdateBandwidthMaxFailed")
        self.WindowsUpdatePartitionPercent=GET_INFO_INT("WindowsUpdatePartitionPercent")
        self.WindowsUpdateMaxPartition=GET_INFO_INT("WindowsUpdateMaxPartition")
        if self.WindowsUpdateMaxToPartialQueue==0:
            self.WindowsUpdateMaxToPartialQueue=300
        if self.WindowsUpdateBandwidthPartial==0:
            self.WindowsUpdateBandwidthPartial=512
        if self.WindowsUpdateBandwidthMaxFailed==0:
            self.WindowsUpdateBandwidthMaxFailed=5
            
        if len(self.www_root)==0:
            self.www_root="/home/squid/WindowsUpdate"
            
        WindowsUpdateInterface=GET_INFO_STR("WindowsUpdateInterface")
        
        if len(WindowsUpdateInterface)>2:
            interfaces=all_interfaces()
            if WindowsUpdateInterface in interfaces:
                self.listen_interface=interfaces[WindowsUpdateInterface]
        
        pass
    
# --------------------------------------------------------------------------------------------------------
    def run(self):
       
        while True:
            self.parse_queue()
            time.sleep(30)
            
        
        pass
    
# --------------------------------------------------------------------------------------------------------
    def curl_progress(self,download_size, downloaded_size, upload_t, upload_d):
        global previousProgress
        if int(download_size) == 0:
            previousProgress=0
            return
        
        progress = round( downloaded_size * 100 / download_size ,0);
        
        if progress >previousProgress:
            previousProgress=progress
            if progress == 1:
                self.logger.info(self.BaseName+" Downloading: 1%")              
            if progress == 5:
                self.logger.info(self.BaseName+" Downloading: 5%")            
            if progress == 10:
                self.logger.info(self.BaseName+" Downloading: 10%")
            if progress == 15:
                self.logger.info(self.BaseName+" Downloading: 15%")                
            if progress == 20:
                self.logger.info(self.BaseName+" Downloading: 20%")
            if progress == 25:
                self.logger.info(self.BaseName+" Downloading: 25%")                  
            if progress == 50:
                self.logger.info(self.BaseName+" Downloading: 50%")
            if progress == 60:
                self.logger.info(self.BaseName+" Downloading: 60%")
            if progress == 70:
                self.logger.info(self.BaseName+" Downloading: 70%")
            if progress == 80:
                self.logger.info(self.BaseName+" Downloading: 80%")                 
            if progress == 90:
                self.logger.info(self.BaseName+" Downloading: 90%")                
            if progress == 95:
                self.logger.info(self.BaseName+" Downloading: 95%")
            if progress == 96:
                self.logger.info(self.BaseName+" Downloading: 96%")
            if progress == 97:
                self.logger.info(self.BaseName+" Downloading: 97%")                   
            if progress == 98:
                self.logger.info(self.BaseName+" Downloading: 98%")                
            if progress == 99:
                self.logger.info(self.BaseName+" Downloading: 99%")
            if progress == 100:
                self.logger.info(self.BaseName+" Downloading: 100%")                 
            sql="UPDATE wsus SET download_progress='"+str(progress)+"' WHERE zmd5='"+self.TempMD5+"'"
            self.POSTGRES.QUERY_SQL(sql)
            
    pass
# --------------------------------------------------------------------------------------------------------
    def parse_queue(self):
        
        if self.WindowsUpdatePartitionPercent>self.WindowsUpdateMaxPartition:
            self.logger.info("ABORTING: Max Partition "+str(self.WindowsUpdateMaxPartition)+"% reached, current "+self.WindowsUpdatePartitionPercent+"%")
            return
            
        
        header_size=0
        sql="SELECT domain,sourceurl,zmd5,header_size,header_type,path FROM wsus WHERE downloaded=0"
        rows=self.POSTGRES.QUERY_SQL(sql)
        self.logger.debug("Checking wsus table: "+str(len(rows))+' orders')
        if len(rows)==0:
            return
        
        self.logger.info("Queue table: "+str( len(rows) )+" orders")
        
        for row in rows:
            domain=row[0]
            sourceurl=row[1]
            zmd5=row[2]
            self.TempMD5=zmd5
            header_size=row[3]
            header_type=row[4]
            path=row[5]
            header_sizeM=header_size/1024
            header_sizeM=header_sizeM/1024
            header_sizeM=round(header_sizeM)
            self.BaseName=str(os.path.basename(sourceurl))
            BaseName=str(os.path.basename(path))
            anotherTime=0
            timestamp=0
            self.logger.info(BaseName+" ("+str(header_sizeM)+" MB):"+sourceurl)
            HeaderData=header_type+';'+str(header_size)+';'+BaseName
            HeaderFilePath=self.www_root+'/'+domain+'/'+zmd5+'.head'
            FileDestination=self.www_root+'/'+domain+'/'+zmd5+'.data'
            FailedFilePath=self.www_root+'/'+domain+'/'+zmd5+'.failed'
            LockFilePath=self.www_root+'/'+domain+'/'+zmd5+'.lock'
            
            if os.path.exists(LockFilePath):
                RemoveFile(LockFilePath)
                
            
            file_put_contents(HeaderFilePath,HeaderData)
            if self.WindowsUpdateMaxRetentionTime>0:
                timeNow = datetime.datetime.now()
                anotherTime = timeNow + datetime.timedelta(days=self.WindowsUpdateMaxRetentionTime)
                timestamp=int(time.mktime(anotherTime.timetuple()))
            
            if not self.download(sourceurl,FileDestination,header_size,zmd5):
                CurrentFailed=file_get_contents(FailedFilePath)
                testdata=unicode(CurrentFailed,'utf-8')
                if testdata =='':
                    CurFailed=0
                else:
                    if testdata.isnumeric():
                        CurFailed=int(testdata)
                
                if CurFailed >= self.WindowsUpdateBandwidthMaxFailed:
                    self.logger.info(sourceurl +" download failed -> Aborting")
                    sql="UPDATE wsus SET download_progress='0', downloaded=3,finaltime='"+str(timestamp)+"' WHERE zmd5='"+self.TempMD5+"'"
                    self.POSTGRES.QUERY_SQL(sql)
                    file_put_contents(LockFilePath,str(1))
                    RemoveFile(HeaderFilePath)
                    RemoveFile(HeaderData)
                    continue
                
                CurFailed=CurFailed+1
                file_put_contents(FailedFilePath,str(CurFailed))
                self.logger.info(sourceurl +" download failed "+str(CurFailed)+"/"+str(self.WindowsUpdateBandwidthMaxFailed))
                continue
            sql="UPDATE wsus SET download_progress='100', downloaded=1,finaltime='"+str(timestamp)+"' WHERE zmd5='"+self.TempMD5+"'"
            self.POSTGRES.QUERY_SQL(sql)
            if not self.POSTGRES.ok:
                self.logger.info(self.BaseName +": Fatal PostGreSQL error '"+self.POSTGRES.sql_error+"'")
                
    pass
# --------------------------------------------------------------------------------------------------------
    def download(self,url,destination,size,md5):
        self.logger.info(self.BaseName +": Destination:"+ destination)
        if os.path.exists(destination):
            self.logger.info(self.BaseName +": Destination already exists")
            Filesize=os.path.getsize(destination)
            if Filesize == size:
                 self.logger.info( self.BaseName +": Same requested size, assume True")
                 return True
            self.logger.info( self.BaseName +": requested size:"+str(size)+" is different than stored:"+str(Filesize)+", removing")
            RemoveFile(destination)
                
        
        header_sizeM=size/1024
        header_sizeM=header_sizeM/1024
        header_sizeM=round(header_sizeM)
        Directory=dirname(destination)
        BaseName=str(os.path.basename(destination))
        mkdir(Directory,0755)
        c = pycurl.Curl()
        
        if self.WindowsUpdateBandwidth>0:
            self.logger.info(BaseName +": Limit to "+str(self.WindowsUpdateBandwidth)+" KB\s")
            c.setopt(pycurl.MAX_RECV_SPEED_LARGE, self.WindowsUpdateBandwidth*1024)
        
        c.setopt(pycurl.USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0')
        c.setopt(pycurl.FOLLOWLOCATION, 1)
        c.setopt(pycurl.MAXREDIRS, 5)
        c.setopt(pycurl.PROXY, "127.0.0.1")
        c.setopt(pycurl.PROXYPORT,self.SquidMgrListenPort)
        PyCurlProxyString="127.0.0.1:"+str(self.SquidMgrListenPort)
        
        
        if len(self.listen_interface)>3:
            c.setopt(pycurl.INTERFACE, self.listen_interface)
        
        if header_sizeM > self.WindowsUpdateBandwidthPartial:
            self.logger.info(BaseName +": Limit to "+str(self.WindowsUpdateBandwidthPartial)+" KB\s")
            c.setopt(pycurl.MAX_RECV_SPEED_LARGE, self.WindowsUpdateBandwidthPartial*1024)
              
        file_id = open(destination, "wb")
        
        c.setopt(pycurl.URL, url)
        c.setopt(pycurl.HTTPHEADER, ['Expect:', 'Keep-Alive: 300', 'Connection: Keep-Alive'])
        c.setopt(pycurl.WRITEDATA, file_id)
        c.setopt(pycurl.NOPROGRESS, 0)
        c.setopt(pycurl.PROGRESSFUNCTION, self.curl_progress)
        try:
            c.perform()
            code = c.getinfo(pycurl.HTTP_CODE)
        except pycurl.error, error:
            errno, errstr = error
            self.logger.info( self.BaseName +": (proxy "+PyCurlProxyString+") Fatal exception CODE:"+str(errno)+" '"+errstr+"' on download()")
            RemoveFile(destination)
            c.close()
            return False
        c.close()
        if code == 200:
            self.logger.info( self.BaseName +": download success ( "+str(header_sizeM)+"MB )")
            return True
        self.logger.info(self.BaseName +": Download Failed (proxy "+PyCurlProxyString+") with Error code:"+str(code))
        RemoveFile(destination)
        pass
# --------------------------------------------------------------------------------------------------------
    

app = App()
app.debug=False


logger = logging.getLogger("DaemonLog")
logger.setLevel(logging.INFO)
formatter = logging.Formatter("[%(asctime)s]: %(message)s")
handler = logging.FileHandler("/var/log/wsus-http/scheduler.log")
handler.setFormatter(formatter)
logger.addHandler(handler)
app.logger=logger

daemon_runner = runner.DaemonRunner(app)
#This ensures that the logger file handle does not get closed during daemonization
daemon_runner.daemon_context.files_preserve=[handler.stream]
daemon_runner.do_action()

# --------------------------------------------------------------------------------------------------------