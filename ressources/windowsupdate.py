#!/usr/bin/env python
import socket
from unix import *
import os
import datetime
import hashlib
import re
import tldextract
from urlparse import urlparse
from postgressql import *
import pycurl

class WSUS:
    
    def __init__(self,logging,debug):
        self.WindowsUpdateCaching=GET_INFO_INT("WindowsUpdateCaching")
        self.WindowsUpdateDenyIfNotExists=GET_INFO_INT("WindowsUpdateDenyIfNotExists")
        self.SquidMgrListenPort=GET_INFO_INT("SquidMgrListenPort")
        self.WindowsUpdateMinimalSize=GET_INFO_INT("WindowsUpdateMinimalSize")
        self.WindowsUpdateHTTPPort=GET_INFO_INT("WindowsUpdateHTTPPort")
        self.www_root=GET_INFO_STR("WindowsUpdateCachingDir")
        self.debug=debug
        self.logging=logging
        self.MEM={}
        self.POSTGRES=Postgres()
        self.zHeads={}
        self.whitelists={}
        
        if self.WindowsUpdateMinimalSize==0:
            self.WindowsUpdateMinimalSize=100
        if self.WindowsUpdateHTTPPort==0:
            self.WindowsUpdateHTTPPort=18816
            
        self.WindowsUpdateMinimalSize=self.WindowsUpdateMinimalSize*1024
        
        if len(self.www_root)==0:
            self.www_root="/home/squid/WindowsUpdate"
        
        self.logging.info('[WSUS] WindowsUpdateCaching.......: '+str(self.WindowsUpdateCaching))
        self.logging.info('[WSUS] Deny if not exists.........: '+str(self.WindowsUpdateDenyIfNotExists))
        self.logging.info('[WSUS] Minimal size...............: '+str(self.WindowsUpdateMinimalSize)+' Bytes')
        self.logging.info('[WSUS] Root directory.............: '+str(self.www_root))
        if os.path.exists('/etc/squid3/windowsupdate.whitelist.db'):
            data=file_get_contents('/etc/squid3/windowsupdate.whitelist.db')
            array=data.split("\n")
            for num in array:
                num=num.strip()
                if len(num)==0:
                    continue
                self.whitelists[num]=True
        
        
        pass
    
    
    def parse_uri(self,uri,Channel,sys,ipaddr,mac,uid):
        header_size=0
        content_type=''
        
        if self.WindowsUpdateCaching==0:
            self.logging.debug('[WSUS] WindowsUpdateCaching.......: Disabled, SKIP')
            return False
        
        if self.whitelists.has_key(ipaddr):
            self.logging.debug('[WSUS] WindowsUpdateCaching.......: '+ipaddr+' whitelisted, SKIP')
            return False
        
        NOTNESSCAB={}
        NOTNESSCAB["disallowedcertstl"]=True
        NOTNESSCAB["pinrulesstl"]=True
        NOTNESSCAB["wsus3setup"]=True
        NOTNESSCAB["authrootstl"]=True
        NOTNESSCAB["muv4wuredir"]=True
        NOTNESSCAB["WuSetupHandler"]=True
        NOTNESSCAB["v6-win7sp1-wuredir"]=True
        NOTNESSCAB["WUClient-SelfUpdate"]=True
        NOTNESSCAB["cleanupwindowsdefendertasks"]=True
        NOTNESSCAB["NrPolicy.cab"]=True
        NOTNESSCAB["filestreamingservice"]=True
        
        xnotincab='|'.join(NOTNESSCAB)
        self.logging.debug('[WSUS] Regex ('+xnotincab+') -> Checking') 
        matches=re.search('('+xnotincab+')',uri)
        if matches:
            self.logging.info('[WSUS] SKIP:NOT_IN_CAB '+uri)
            return False
          
        parsed = urlparse(uri)
        DOMAIN=parsed.hostname
        PATH=parsed.path
        
        if len(PATH)==0:
            return False
        
        
        GlobalPattern='(\.dl\.delivery\.mp\.microsoft.com|\.update\.microsoft\.com|\.windowsupdate\.com|\.apps\.microsoft.com|\.ws\.microsoft\.com|download\.microsoft\.com|\.gvt[0-9]+\.com)'
        
        matches=re.search(GlobalPattern,DOMAIN)
        if not matches:
            self.logging.debug('[WSUS]  '+uri+' Checking...') 
            self.logging.debug('[WSUS]  '+GlobalPattern+' No match') 
            return False

        
        BaseName=str(os.path.basename(PATH))
        extension=BaseName.split(".")[-1]
        
        if extension == "png":
            self.logging.info('[WSUS] SKIP: PNG extension')
            return False
        if extension == "gif":
            self.logging.info('[WSUS] SKIP: GIF extension')
            return False        
        if extension == "jpeg":
            self.logging.info('[WSUS] SKIP: JPEG extension')
            return False         
        
        
        ext = tldextract.extract(uri)
        familysite=ext.domain+'.'+ext.suffix
        
        matches=re.search('^[0-9]+_[a-z0-9]+\.cab$',BaseName)
        if matches:
            self.logging.info('[WSUS] SKIP:FILE_RULE1 '+BaseName)
            return False
        
        
        self.logging.debug('[WSUS] URI.......: '+str(uri))
        self.logging.debug('[WSUS] URL_DOMAIN: '+str(DOMAIN))
        self.logging.debug('[WSUS] Familysite: '+str(familysite))
        
        self.logging.debug('[WSUS] BaseName..: '+str(BaseName))
        self.logging.debug('[WSUS] extension.: '+str(extension))
        self.logging.debug('[WSUS] Path......: '+str(PATH))
        self.logging.debug('[WSUS] mac.......: '+str(mac))
        self.logging.debug('[WSUS] Deny if no: '+str(self.WindowsUpdateDenyIfNotExists))
        md5=hashlib.md5(familysite+PATH).hexdigest()
        LockFilePath=self.www_root+'/'+familysite+'/'+md5+'.lock'
        self.logging.debug('[WSUS] md5.......: '+md5)
        self.logging.debug('[WSUS] lock file.: '+LockFilePath)
        
        
        
        if os.path.exists(LockFilePath):
            self.logging.debug("[WSUS] "+LockFilePath+": LOCKED -> SKIP ")
            return False
            
        
        if self.MEM.has_key(md5):
            if len(self.MEM[md5]) == 0:
                return False
            LineToSend=self.MEM[md5]
            if Channel>0:
                LineToSend=str(Channel)+' '+LineToSend
                self.logging.debug('[WSUS] OUTPUT FROM MEMORY: "'+LineToSend+'"')
                sys.stdout.write(LineToSend+'\n')
                sys.stdout.flush()
                return True
            self.logging.debug('[WSUS] OUTPUT FROM MEMORY: "'+LineToSend+'"')
            sys.stdout.write(LineToSend+'\n')
            sys.stdout.flush()
            return True
            
        
        
        sql="SELECT downloaded,zmd5,header_size FROM wsus WHERE zmd5='"+md5+"'"
        self.logging.debug('[WSUS] "'+sql+'"')
        rows=self.POSTGRES.QUERY_SQL(sql)
        Downloaded=0
        Added=0
        DINT=0
        
        if not self.POSTGRES.ok:
            self.logging.info('WSUS: PostGreSQL failed')
            return False
        
        self.logging.debug('[WSUS]  QUERY -> '+str(len(rows))+' element(s)')
        if len(rows)>0:
            Downloaded=rows[0][0]
            DINT=Downloaded
            header_size=rows[0][2]
            self.logging.debug('[WSUS]  downloaded '+str(Downloaded)+', header_size='+str(header_size)+' md5='+rows[0][1])
            Added=1
            
        if header_size==0:
            self.logging.debug('[WSUS]  GetHeaders()')
            if not self.GetHeaders(uri):
                self.logging.info("[WSUS]  Fatal unable to retrieve headers from "+uri)
                return False
                
            if self.zHeads.has_key('content-length'):
                header_size=int(self.zHeads['content-length'])
                
                
            if self.zHeads.has_key('content-type'):
                content_type=self.zHeads['content-type']
                content_type=content_type.strip()
                
                if content_type=="application/x-x509-ca-cert":
                    self.MEM[md5]=''
                    self.logging.info('[WSUS] SKIP: Certificate content type:'+str(content_type))
                    return False
                    
                
                matches=re.search("^image\/",content_type)
                if matches:
                    self.MEM[md5]=''
                    self.logging.info('[WSUS] SKIP: content type:'+str(content_type))
                    return False
                
                matches=re.search("^text\/",content_type)
                if matches:
                    self.MEM[md5]=''
                    self.logging.info('[WSUS] SKIP: content type:'+str(content_type))
                    return False
                    
                
            if header_size < self.WindowsUpdateMinimalSize:
                self.MEM[md5]=''
                DINT=2 
            

        if Added == 0:
            sqladd="INSERT INTO wsus (zdate,zmd5,downloaded,path,domain,sourceurl,header_size,header_type) VALUES"
            sqladd=sqladd+" (now(),'"+md5+"','"+str(DINT)+"','"+PATH+"','"+familysite+"','"+uri+"','"+str(header_size)+"','"+content_type+"') ON CONFLICT DO NOTHING"
            self.logging.debug('[WSUS]  "'+sqladd+'"')
            self.logging.debug('[WSUS]  Send Query...')
            self.POSTGRES.QUERY_SQL(sqladd)
            if self.WindowsUpdateDenyIfNotExists==0:
                return False
        
        if DINT==0:
             if self.WindowsUpdateDenyIfNotExists==0:
                return False
            
        
        if DINT>1:
            self.MEM[md5]=''
            return False
        
        if mac=='':
            mac='00:00:00:00:00:00'
        if uid=='':
            uid='-'
            
        LineToSend='OK status=302 rewrite-url="http://127.0.0.1:'+str(self.WindowsUpdateHTTPPort)+'/index?domain='+familysite+'&md5='+md5+'&sourceip='+ipaddr+'&uid='+uid+'&mac='+mac+'"'
        self.MEM[md5]=LineToSend
        
        if Channel>0:
            LineToSend=str(Channel)+' '+LineToSend
            self.logging.debug('[WSUS] OUTPUT: "'+LineToSend+'"')
            sys.stdout.write(LineToSend+'\n')
            sys.stdout.flush()
            return True
            
        self.logging.debug('[WSUS] OUTPUT: "'+LineToSend+'"')    
        sys.stdout.write(LineToSend+'\n')
        sys.stdout.flush()
        return True        
        pass
    
# --------------------------------------------------------------------------------------------------------     
    def getHeads(self,buffer):
        matches=re.search('(.*?):(.+)',buffer);
        if not matches:
            return
        Key=matches.group(1)
        Value=matches.group(2)
        Key=Key.lower()
        Value=Value.strip()
        self.logging.debug("Key = "+Key+"    "+Value+"= ("+ Value+")")
        self.zHeads[Key]=Value
        pass
# --------------------------------------------------------------------------------------------------------     
    def GetHeaders(self,url):
        code=0
        self.zHeads={}
        self.logging.debug("[WSUS]  using Proxy 127.0.0.1:"+str(self.SquidMgrListenPort)) 
        c = pycurl.Curl()
        devnull = open('/dev/null', 'w')
        c.setopt(pycurl.URL, url)
        c.setopt(pycurl.HEADER, 1)
        c.setopt(pycurl.NOBODY, 1) # header only, no body
        c.setopt(pycurl.NOSIGNAL, 1)
        c.setopt(pycurl.WRITEFUNCTION, devnull.write)
        c.setopt(pycurl.USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0')
        c.setopt(pycurl.FOLLOWLOCATION, 1)
        c.setopt(pycurl.MAXREDIRS, 5)
        c.setopt(pycurl.HEADERFUNCTION, self.getHeads)
        c.setopt(pycurl.PROXY, "127.0.0.1")
        c.setopt(pycurl.PROXYPORT, self.SquidMgrListenPort)
        
        try:
            self.logging.debug("[WSUS]  Get headers...") 
            c.perform()
            code = c.getinfo(c.HTTP_CODE)
        except:
            self.logging.info("[WSUS]  Fatal GetHeaders -> using Proxy 127.0.0.1:"+str(self.SquidMgrListenPort)+" Exception") 
            c.close()
            return False
        
        c.close()
        if code == 0:
            self.logging.info("[WSUS]  Fatal GetHeaders -> using Proxy 127.0.0.1:"+str(self.SquidMgrListenPort)+" response code 0")   
            return False
        if code == 200:
            return True
        
        self.logging.info("[WSUS]  Fatal GetHeaders -> using Proxy 127.0.0.1:"+str(self.SquidMgrListenPort)+" response code "+str(code ))    
    pass
# --------------------------------------------------------------------------------------------------------       
    

class reg(object):
    def __init__(self, cursor, row):
        for (attr, val) in zip((d[0] for d in cursor.description), row) :
            setattr(self, attr, val)

    