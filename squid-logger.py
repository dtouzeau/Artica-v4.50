#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import multiprocessing
import socket
from elasticsearch import Elasticsearch
import json
import logging
import signal
from daemon import runner
import urllib
from urlparse import urlparse
import hashlib
import tldextract
from categories import *
from unix import *
import traceback as tb
import datetime
import time
import redis

global MEMORY, RotateTime,MEMORY_USER,MEMORY_WEBF,MEMORY_UA,MEMORY_PROTOCOL,MEMORY_BLOCK,CATEGORIES_INT,RotateCategoryTime,NOT_CATEGORIZED,GLOBALCOUNT
from phpserialize import serialize, unserialize


def parseline(sline,features):
    global GLOBALCOUNT
    GLOBALCOUNT=GLOBALCOUNT+1
    if len(sline)<10: return
    MAIN=sline.split('|')
    logger.debug("["+str(GLOBALCOUNT)+"]: Parsing <"+sline+"> Array of "+str(len(MAIN))+" elements")
    if(len(MAIN)<13): return False
    familysite=""
    ssl_sni=""
    NOTE=""
    category=0
    webfiltering_rule=0
    webfiltering_category=0
    filtered_rule=""
    filtered=0
    category_string=""
    webfiltering=False
    global MEMORY
    global RotateTime
    global RotateCategoryTime
    global CATEGORIES_INT
    
    h=[]
    for (i, item) in enumerate(MAIN):
        h.append(str(i)+": "+item+";")
    logger.debug(";".join(h))

    if MAIN[13]=="-":MAIN[13]=0
    stimestamp=MAIN[0]
    try:
        response_time=int(MAIN[1])
    except:
        logger.info("Array of MAIN[1] is not integer: '"+sline+"'")
        return False
    
        
    mac=MAIN[3]
    ztimestamp=stimestamp.split(".")
    sitename=str(MAIN[8])
    if sitename =='-': sitename=''
    uid=str(MAIN[9])
    server_ip=str(MAIN[11])
    src_ip=str(MAIN[2])
    size=int(MAIN[6])
    totaltime=int(MAIN[13])
    if(len(MAIN)>13): proxyname=str(MAIN[14])
    try:
        if(len(MAIN)>14): ssl_sni=str(MAIN[15])
    except:
        logger.info(tb.format_exc())
      
    try:
        if(len(MAIN)>15): UserAgent=str(MAIN[16])
    except:
        logger.info(tb.format_exc())

    EnableRedisServer=int(features["EnableRedisServer"])
    if len(proxyname)==0: proxyname=features["SYSTEMID"]
    
    
    if mac=="00:00:00:00:00:00" : mac=""
    if ssl_sni=="-":ssl_sni=""
    if(len(ssl_sni)>1): sitename=ssl_sni
    
    if sitename =='127.0.0.1': return False
    
    
#----------------------------------------------------------------------------------
    if(len(MAIN)>16):
        NOTE=str(MAIN[17])
        NOTE=urllib.unquote(NOTE.encode("utf8"))
        zlines=NOTE.split('\n')
        for line in zlines:
            if len(line)==0: continue
            #logger.debug("EXTENSION: <"+line+">" )
            matches=re.search('category:\s+([0-9]+)',line)
            if matches: category=int(matches.group(1))
            
            matches=re.search('notracks:\s+([0-9]+)',line)
            if matches:
                webfiltering=True
                webfiltering_rule=0
                filtered_rule="notracks"
                webfiltering_category=143
                if category==0: category=143
                
            
            matches=re.search('webfiltering:\s+block,([0-9]+),P([0-9]+)',line)
            if matches:
                webfiltering=True
                webfiltering_rule=int(matches.group(1))
                webfiltering_category=int(matches.group(2))
                if category==0:category=webfiltering_category
                if webfiltering_rule==0: filtered_rule="default"
            
            if(len(sitename)<3):
                matches=re.search('rewrite-url:.*?url=(.+)',line)
                if matches:
                    o = urlparse(matches.group(1))
                    sitename=o.hostname
                
#----------------------------------------------------------------------------------

    

    if(len(sitename)==0):
        logger.debug("["+str(GLOBALCOUNT)+"]:ERROR: from "+src_ip +" to '"+sitename+"' [no sitename]" )
        return False
    
    
            
    if sitename.find(":") > 0:
        zsitename=sitename.split(":")
        sitename=zsitename[0]
                
    if len(uid)>0:
        uid=urllib.unquote(uid)
        uid=uid.replace('$','')
                
    if not is_valid_ip(sitename):
        ext = tldextract.extract(sitename)
        familysite=ext.domain+'.'+ext.suffix
                
    if not is_valid_ip(server_ip): server_ip="0.0.0.0"
    if not is_valid_ip(src_ip): src_ip="0.0.0.0"
            
    if len(sitename)==0:
        logger.debug("["+str(GLOBALCOUNT)+"]:ERROR: No sitename: aborting...")
        return False
    
    if is_valid_ip(sitename):
        szip=sitename.split(".")
        familysite=str(szip[0])+"."+str(szip[1])+"."+str(szip[2])+".0/24"
        
    KeyUser=get_keyuser(uid,mac,src_ip)
    if category==0: category=repasse_category(familysite)  
    if category in CATEGORIES_INT: category_string=CATEGORIES_INT[category] 
    
    if webfiltering==True:
        filtered=1
        if EnableRedisServer==1: set_memory_webf(KeyUser,sitename,webfiltering_rule,webfiltering_category,proxyname)
        if webfiltering_category in CATEGORIES_INT: category_string=CATEGORIES_INT[webfiltering_category]
        
            
    if len(category_string)==0:category_string="unknown"
    if not webfiltering: logger.debug("["+str(GLOBALCOUNT)+"]: PASS: ------- <"+sitename+"> "+str(category)+":"+str(category_string)+" ("+str(familysite)+") "+ str(size)+" Bytes [Code:"+str(MAIN[5])+"]")
    if webfiltering: logger.debug("["+str(GLOBALCOUNT)+"]: BLOCK: " +filtered_rule+"<"+sitename+"> "+str(category)+":"+str(category_string)+" ("+str(familysite)+") "+ str(size)+" Bytes [Code:"+str(MAIN[5])+"]")
    
    if int(MAIN[5])==0:
        logger.debug("["+str(GLOBALCOUNT)+"]: ERROR: status code = 0")
        return True
    
    MinutesDiff=difference_minutes(RotateCategoryTime)
    if MinutesDiff>2:
        logger.debug("["+str(GLOBALCOUNT)+"]: CATEGORIES: >>> RELOAD DATABASE")
        load_categories()
        RotateCategoryTime=int(time.time())
    
    if EnableRedisServer==1:
        set_memory_ua(UserAgent,size,proxyname)
        set_memory(KeyUser,sitename,size,category,proxyname,src_ip,mac)
        set_memory_protocol(size,MAIN[7],proxyname)
            
    
    
    if(features["SquidLoggerEnableElasticSearch"]==0):return True
    http_method = str(MAIN[7])
    if http_method == "GET": http_method ="HTTP"
    if http_method == "CONNECT": http_method = "HTTPS"
    
    mydata={}
    mydata["timestamp"]=int(ztimestamp[0])
    mydata["timestamp_ms"]=int(ztimestamp[1])
    mydata["response_time"]=response_time
    mydata["src_ip"]=src_ip
    mydata["mac"]=mac
    mydata["request_status"]=str(MAIN[4])
    mydata["status_code"]=str(MAIN[5])
    mydata["http_size"]=int(size)
    mydata["http_method"]=http_method
    mydata["website"]=sitename
    mydata["familysite"]=familysite
    mydata["category"]=category
    mydata["user"]=uid
    mydata["hierarchy"]=str(MAIN[10])
    mydata["server_ip"]=server_ip
    mydata["content_type"]=str(MAIN[12])
    mydata["catname"]=category_string
    mydata["filtered"]=filtered
    mydata["filtered_rule"]=filtered_rule
    mydata["proxyname"]=proxyname

    
    es = Elasticsearch([{'host': features["ElasticsearchAddr"], 'port': features["ElasticsearchBindPort"]}])
    logger.debug(src_ip+ "/"+mac+" -> "+sitename+" ("+familysite+") "+server_ip)
    q = json.dumps(mydata)
    try:
        results=es.index(index='proxy',doc_type="squid",body=q,pipeline="geoip")
        logger.debug(results)
    except:
        logger.info(tb.format_exc())
        return False
    
    return True

def timeround10():
    now = datetime.datetime.now()
    a, b = divmod(round(now.minute, -1), 60)
    min='%i-%02i' % ((now.hour + a) % 24, b)
    return now.strftime("%Y-%m-%d-")+min
        
def get_keyuser(uid,mac,src_ip):
    
    if len(uid)>2: return uid
    if len(mac)>2: return mac
    return src_ip

def load_categories():
    if not is_file("/etc/squid3/categories.db"): return False
    global CATEGORIES_INT
    if len(CATEGORIES_INT) > 20:
        if not is_file("/etc/squid3/reload-category.action"): return False
    
    RemoveFile("/etc/squid3/reload-category.action")
    
    try:
        CATEGORIES_INT=unserialize(file_get_contents("/etc/squid3/categories.db"))
    except:
        logger.info(tb.format_exc())
        
def repasse_category(familysite):
    MAIN={}
    MAIN["adlooxtracking.com"]=143
    MAIN["crashlytics.com"]=143
    MAIN["ensighten.com"]=143
    MAIN["amplitude.com"]=143
    MAIN["cedexis.com"]=143
    MAIN["helpshift.com"]=143
    
    MAIN["kaspersky.com"]=36
    MAIN["avast.com"]=36
    MAIN["onenote.net"]=151
    MAIN["microsoft.com"]=2
    MAIN["msedge.net"]=2
    MAIN["live.net"]=2
    MAIN["64.4.54.0/24"]=2
    MAIN["192.168.1.0/24"]=82
    MAIN["musical.ly"]=15
    MAIN["youtube.com"]=16
    MAIN["ytimg.com"]=16
    MAIN["googlevideo.com"]=16
    MAIN["google.com"]=17
    MAIN["googleapis.com"]=17
    MAIN["gstatic.com"]=17
    MAIN["googlecode.com"]=17
    MAIN["ggpht.com"]=17
    MAIN["googleusercontent.com"]=17
    MAIN["skype.com"]=22
    MAIN["snapchat.com"]=34
    MAIN["softonic.com"]=44
    MAIN["clubic.com"]=103
    MAIN["adsafeprotected.com"]=5
    MAIN["appsflyer.com"]=5
    MAIN["adjust.com"]=5
    MAIN["hawifallon.com"]=5
    MAIN["adalyser.com"]=5
    MAIN["sc-cdn.net"]=5
    MAIN["advizon.net"]=5
    MAIN["chartboost.com"]=5
    MAIN["bytedance.com"]=5
    MAIN["samsungads.com"]=5
    
    MAIN["firefoxusercontent.com"]=80
    MAIN["dslr.net"]=80
    
    MAIN["samsungcloudsolution.net"]=98
    MAIN["snssdk.com"]=98
    MAIN["smartthings.com"]=98
    MAIN["netflix.com"]=100
    MAIN["mozilla.com"]=126
    MAIN["articatech.net"]=126
    MAIN["articatech.com"]=126
    MAIN["artica.fr"]=126
    MAIN["roblox.com"]=58
    MAIN["unity3d.com"]=58
    MAIN["lastpass.com"]=104
    MAIN["msftncsi.com"]=147
    MAIN["samsungqbe.com"]=147
    MAIN["internetat.tv"]=147
    
    if familysite in MAIN: return MAIN[familysite]
    
    
def set_memory(KeyUser,sitename,size,category,proxyname,IPAddr,MacAddr):


    try:
        RedisCNX = redis.Redis(unix_socket_path='/var/run/redis/redis.sock')
    except:
        logger.debug("set_memory(): /var/run/redis/redis.sock connection issue")
        logger.debug(tb.format_exc())
        return False

    Synonym = ""
    if len(MacAddr)>0:Synonym=RedisCNX.get("usrmac:"+MacAddr)
    if Synonym is not None:KeyUser=Synonym



    KeyDate=timeround10()
    TotalHits= "WebStats:" + KeyDate + ":TotalHits"
    KeyTotalSize = "WebStats:" + KeyDate + ":TotalSize"
    KeyDomainsList = "WebStats:" + KeyDate + ":CurrentDomains"
    KeyCategoryList = "WebStats:" + KeyDate + ":CurrentCategories"
    KeyUsersList = "WebStats:" + KeyDate + ":CurrentUsers"
    KeyUserRQS="WebStats:"+KeyDate+":CurrentUser:RQS:"+KeyUser
    KeyUserSize="WebStats:"+KeyDate+":CurrentUser:Size:"+KeyUser
    KeyUserIP="WebStats:"+KeyDate+":CurrentUserIP:"+KeyUser
    KeyCategorySize="WebStats:"+KeyDate+":"+str(category)+":Size"
    KeyCategoryHits = "WebStats:" + KeyDate + ":" + str(category) + ":Hits"

    KeyDomainSize="WebStats:"+KeyDate+":Domains:"+str(sitename)+":Size"
    KeyDomainsHits = "WebStats:" + KeyDate + ":Domains:" + str(sitename) + ":Hits"
    KeyDomainsUsers = "WebStats:" + KeyDate + ":Domains:" + str(sitename) + ":Users"


    RedisCNX.sadd(KeyUsersList, KeyUser)
    RedisCNX.sadd(KeyDomainsList,sitename)
    RedisCNX.sadd(KeyCategoryList, category)
    RedisCNX.sadd(KeyDomainsUsers, KeyUser)

    RedisCNX.incr(TotalHits)
    RedisCNX.incr(KeyUserRQS)
    RedisCNX.incr(KeyCategoryHits)
    RedisCNX.incr(KeyDomainsHits)

    try:
        TotalSize=int(RedisCNX.get(KeyTotalSize))
    except:
        TotalSize=0


    try:
        UserSize=int(RedisCNX.get(KeyUserSize))
    except:
        UserSize=0

    try:
        DomainSize=int(RedisCNX.get(KeyDomainSize))
    except:
        DomainSize=0

    try:
        CategorySize = int(RedisCNX.get(KeyCategorySize))
    except:
        CategorySize=0

    UserTotalSize=int(UserSize)+int(size)
    DomainTotalSize=int(DomainSize)+int(size)
    CategoryTotalSize = int(CategorySize) + int(size)
    TotalSizeSum=int(TotalSize)+int(size)
    RedisCNX.set(KeyTotalSize, str(TotalSizeSum))

    logger.debug("set_memory(): %s -> %s %s + %s = %s" % (KeyUser,sitename,str(UserSize), str(size),str(UserTotalSize) )   )
    RedisCNX.set(KeyUserSize,str(UserTotalSize))
    RedisCNX.set(KeyCategorySize, str(CategoryTotalSize))
    RedisCNX.set(KeyDomainSize, str(DomainTotalSize))
    RedisCNX.set(KeyUserIP, str(IPAddr))



    
def set_memory_webf(KeyUser,sitename,rule,category,proxyname):

    try:
        RedisCNX = redis.Redis(unix_socket_path='/var/run/redis/redis.sock')
    except:
        logger.debug("set_memory(): /var/run/redis/redis.sock connection issue")
        logger.debug(tb.format_exc())
        return False

    KeyDate = timeround10()
    KeyRules = "WebFilter:"+KeyDate+":rules"
    KeyRuleHits="WebFilter:"+KeyDate+":rule:"+str(rule)+":hits"
    KeyRuleDomains="WebFilter:"+KeyDate+":rule:"+str(rule)+":domains"
    KeyRuleUsers = "WebFilter:" + KeyDate + ":rule:" + str(rule) + ":users"
    KeyDomains="WebFilter:"+KeyDate+":domains:"+str(sitename)
    KeyUser="WebFilter:"+KeyDate+":user:"+str(KeyUser)

    RedisCNX.sadd(KeyRules, str(rule))
    RedisCNX.sadd(KeyRuleDomains, sitename)
    RedisCNX.sadd(KeyRuleUsers, KeyUser)

    RedisCNX.incr(KeyRuleHits)
    RedisCNX.incr(KeyDomains)
    RedisCNX.incr(KeyUser)




    
def set_memory_protocol(size,protocol,proxyname):
    global MEMORY_PROTOCOL
    if protocol in MEMORY_PROTOCOL:
        MEMORY_PROTOCOL[protocol]=MEMORY_PROTOCOL[protocol]+size
        return True
    
    MEMORY_PROTOCOL[protocol]=size
    
def set_memory_ua(ua,size,proxyname):
    
    global MEMORY_UA
    logger.debug("MEMORY_UA: "+str(len(MEMORY_UA))+" rows")
    if ua in MEMORY_UA:
        temp=MEMORY_UA[ua]
        MAIN=temp.split('|')    
        rqs=int(MAIN[0])
        size=int(MAIN[1])
        rqs=rqs+1
        MEMORY_UA[ua]=str(rqs)+"|"+str(size)+"|"+proxyname
        return True
    
    MEMORY_UA[ua]="1|"+str(size)+"|"+proxyname

def handle(connection, address,myhostname):
    global receive
    Reqz=0
    receive=""
    features={}
    
    SquidLoggerEnableElasticSearch=GET_INFO_INT("SquidLoggerEnableElasticSearch")
    ElasticsearchBindPort=GET_INFO_INT("ElasticsearchBindPort")
    EnableRedisServer=GET_INFO_INT("EnableRedisServer")
    ElasticsearchAddr=GET_INFO_STR("ElasticsearchAddr")
    if(len(ElasticsearchAddr)==0): ElasticsearchAddr="127.0.0.1"
    if ElasticsearchBindPort==0: ElasticsearchBindPort=9200
    features["ElasticsearchBindPort"]=ElasticsearchBindPort
    features["ElasticsearchAddr"]=ElasticsearchAddr
    features["SquidLoggerEnableElasticSearch"]=SquidLoggerEnableElasticSearch
    features["EnableRedisServer"]=EnableRedisServer
    features["LOCATE_PHP5_BIN"]=GET_INFO_STR("LOCATE_PHP5_BIN")
    features["LOCATE_NOHUP"]=GET_INFO_STR("LOCATE_NOHUP")
    features["SYSTEMID"]=GET_INFO_STR("SYSTEMID")
    
    try:
        logger.debug("Connected %r at %r", connection, address)
        while True:
            try:
                receive = connection.recv(8192)
            except:
                logger.info("exception on receive communication, probably shutdown...")
                break
                
                       
            if receive == "":
                logger.debug("Socket closed remotely")
                break
            
            zlines=receive.split('\n')
            for line in zlines:
                if( parseline(line,features) ): Reqz=Reqz+1
        
    except:
        logger.exception("Problem handling request")
    finally:
        logger.debug("Closing socket after "+str(Reqz)+" request(s)")
        connection.close()

class Server(object):
    def __init__(self, hostname, port):
        self.hostname = hostname
        self.port = port

    def start(self):
        logger.debug("listening")
        self.socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        logger.debug("Binding "+self.hostname+" on port "+str(self.port))
        self.socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.socket.bind((self.hostname, self.port))
        self.socket.listen(1)

        while True:
            myhostname=socket.gethostname()
            conn, address = self.socket.accept()
            logger.debug("Got connection")
            try:
                process = multiprocessing.Process(target=handle, args=(conn, address,myhostname))
                process.daemon = True
                process.start()
                logger.debug("Started process %r", process)
            except:
                logger.debug("Got connection: Fatal Error")
                
            
            
class App():

    def __init__(self):
        self.port=1444
        self.stdin_path = '/dev/null'
        self.stdout_path = '/dev/null'
        self.stderr_path = '/dev/null'
        self.pidfile_path = '/var/run/squid-tail.pid'
        self.pidfile_timeout = 5
        self.server=object
        self.action=''
        self.IsRun=True
        self.port=GET_INFO_INT("SquidLoggerPort")
        self.bindIP=GET_INFO_STR("SquidLoggerAddr")

        if self.port==0: self.port=1444
        if len(self.bindIP)==0: self.bindIP="127.0.0.1"
        
        
    def run(self):
        logger.info("Sarting new socket "+self.bindIP+" on port "+str(self.port)+" Action="+self.action)
        if self.IsRun: self.server = Server(self.bindIP, self.port)
        try:
            if self.IsRun:
                logger.info("Server start, Listening...")
                try:
                    self.server.start()
                except:
                    logger.info("self.server.start() exception probably currently shutdown")
                    
                    
        except:
            logger.info("Unexpected exception (die)")
            logger.info(tb.format_exc())
        finally:
            logger.info("Shutting down")
            for process in multiprocessing.active_children():
                logger.info("Shutting down process %r", process)
                process.terminate()
                process.join()
        logger.info("All done")
        
    def SIGNAL_SIGTERM(self, signum, frame):
        logger.info("Received signal %(signum)r, stopping...", vars())
        for process in multiprocessing.active_children():
            logger.info("Shutting down process %r", process)
            process.terminate()
            try:
                process.join()
            finally:
                logger.info("join error on process %r", process)

        logger.info("All done (SIGNAL_SIGTERM)")
        sys.exit(0)
        

mkdir("/home/artica/squid-logger-queue",0755)
mkdir("/home/artica/squid-blocked-queue",0755)
mkdir("/home/artica/squid-ua-queue",0755)
mkdir("/home/artica/squid-protocol-queue",0755)
RotateTime=int(time.time())
RotateCategoryTime=int(time.time())
MEMORY={}
MEMORY_USER={}
MEMORY_WEBF={}
MEMORY_UA={}
MEMORY_PROTOCOL={}
MEMORY_BLOCK={}
CATEGORIES_INT={}
NOT_CATEGORIZED={}
GLOBALCOUNT=0

SquidTailDebug=GET_INFO_INT("SquidTailDebug")   
EnableRedisServer=GET_INFO_INT("EnableRedisServer")
action = sys.argv[1] 
app = App()
app.debug=False
app.action=action
logger = logging.getLogger("squidtail")
if SquidTailDebug==1: logger.setLevel(logging.DEBUG)
if SquidTailDebug==0: logger.setLevel(logging.INFO)
formatter = logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s")
handler = logging.FileHandler("/var/log/squid/squidtail.debug")
handler.setFormatter(formatter)
logger.addHandler(handler)
daemon_runner = runner.DaemonRunner(app)
#This ensures that the logger file handle does not get closed during daemonization
daemon_runner.daemon_context.files_preserve=[handler.stream]
daemon_runner.daemon_context.signal_map[signal.SIGTERM] = app.SIGNAL_SIGTERM
#daemon_runner.daemon_context.signal_map = { signal.SIGKILL: app.handle_exit} 
logger.info("Sarting new server instance with debug mode:"+str(SquidTailDebug)+"; EnableRedisServer="+str(EnableRedisServer))

if is_file("/etc/squid3/categories.db"):
    try:
        CATEGORIES_INT=unserialize(file_get_contents("/etc/squid3/categories.db"))
    except:
        logger.info(tb.format_exc())

daemon_runner.do_action()

logger.info("Shutting down...EnableRedisServer="+str(EnableRedisServer))
logger.info("Die()...")


