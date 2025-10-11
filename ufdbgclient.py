#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import socket
import logging
import string
import re
import pwd
import traceback as tb
from urlparse import urlparse
from unix import *
from ufdbclass import *
import socket
from phpserialize import serialize, unserialize

global Channel
global OutputChannel
global PROXY_URL
global CLIENT_IP
global PROXY_USER
global PROXY_PROTO
global PROXY_IP
global PROXY_PORT
global EXTEND_1
global EXTEND_2
global CLIENT_MAC
global SNI_DOMAIN
global URL_DOMAIN
global DEBUG_CLIENT
global WindowsUpdateCaching
global EnableITChart
global ManagerPort
global MEM_SNI
global SquidGuardClientEnableMemory
global SquidGuardClientMaxMemoryItems
global SquidMgrListenPort
global WhiteListDict
global UfdbResolvSSLCertificates

logger = logging.getLogger(__name__)
myuid = pwd.getpwuid( os.getuid() ).pw_name

def log_exception_hook(type, value, traceback):  
    logger.error(''.join(tb.format_tb(traceback)))
    logger.error('{0} - {1}'.format(type, value))


levelLOG=logging.INFO
DEBUG_CLIENT=False
UfdbgClientDebug=GET_INFO_INT("UfdbgClientDebug")
EnableITChart=GET_INFO_INT("EnableITChart")
PythonpyOpenSSLMissing=GET_INFO_INT("PythonpyOpenSSLMissing")
SquidGuardClientEnableMemory=GET_INFO_INT("SquidGuardClientEnableMemory")
SquidGuardClientMaxMemoryItems=GET_INFO_INT("SquidGuardClientMaxMemoryItems")
if SquidGuardClientMaxMemoryItems==0: SquidGuardClientMaxMemoryItems=100000

MEM_SNI={}

if myuid=='root':UfdbgClientDebug=1

# UfdbgClientDebug=1/

if UfdbgClientDebug ==1:
    levelLOG=logging.DEBUG
    DEBUG_CLIENT=True
   
logging.basicConfig(format='%(asctime)s [%(levelname)s] %(message)s',filename='/var/log/squid/ufdbgclient.debug',  filemode='a',level=levelLOG)
logging.raiseExceptions = False
logging.info('[CLIENT]['+myuid+'] Starting Thread.....Debug='+str(UfdbgClientDebug))
EnableUfdbErrorPage=GET_INFO_INT("EnableUfdbErrorPage")
SquidMgrListenPort=GET_INFO_INT("SquidMgrListenPort")
UfdbResolvSSLCertificates=GET_INFO_INT("UfdbResolvSSLCertificates")
if EnableUfdbErrorPage == 0: EnableITChart=0
ufdbclass=UFDB(logging,DEBUG_CLIENT)
sys.excepthook = log_exception_hook
KSRNRemote          = GET_INFO_INT("KSRNRemote")
TheShieldsIP        = GET_INFO_STR("TheShieldsIP")
TheShieldsPORT      = GET_INFO_INT("TheShieldsPORT")
KSRNRemotePort      = GET_INFO_INT("KSRNRemotePort")
KSRNClientTimeOut   = GET_INFO_INT("KSRNClientTimeOut")
KSRNRemoteAddr      = GET_INFO_STR("KSRNRemoteAddr")

if KSRNClientTimeOut == 0: KSRNClientTimeOut=5

if TheShieldsPORT == 0: TheShieldsPORT = 2004
if len(TheShieldsIP) == 0: TheShieldsIP = "127.0.0.1"

if KSRNRemote == 1:
    TheShieldsIP = KSRNRemoteAddr
    TheShieldsPORT = KSRNRemotePort

if PythonpyOpenSSLMissing==0:
    from OpenSSL import crypto
    from OpenSSL import SSL
    import ssl
    ssl.PROTOCOL_SSLv23 = ssl.PROTOCOL_TLSv1
    logging.info("[CLIENT]: SSL version: "+ssl.OPENSSL_VERSION)

# ------------------------------------------------------------------------------------------------------------------------------------------------------------------
def verify_cb(conn, cert, errun, depth, ok):
        return True
# ------------------------------------------------------------------------------------------------------------------------------------------------------------------
def LoadWhiteList():
    global WhiteListDict
    WhiteListDict=[]
    filename="/etc/squid3/acls_whitelist.dstdomain.conf"
    if not os.path.exists(filename): return None
    with open(filename,"r") as f:
        for txt in f :
            txt=txt.rstrip('\n')
            if len(txt)<3: continue
            txt=txt.replace(".","\.")
            txt=txt.replace("*",".*?")
            logging.debug("[CLIENT]: Whitelist: '"+txt+"'")
            WhiteListDict.append(txt)
pass
# ------------------------------------------------------------------------------------------------------------------------------------------------------------------     
         
def ifIsInWhitelist(host):
    global WhiteListDict
    for pattern in WhiteListDict:
        if len(pattern) < 4: continue
        matches=re.search(pattern,host)
        if matches: return True
        
    return False
pass
# ------------------------------------------------------------------------------------------------------------------------------------------------------------------        
def SendSocket(remote_ip,remote_port,timeout,query,logging):
    global debug
    logssrv="%s:%s" % (remote_ip,remote_port)
    if timeout == 0: timeout = 5
    try:
        logging.debug('[SOCKET]: Using Connecting to %s:%s' % (remote_ip,remote_port))
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(timeout)
        sock.connect((remote_ip, remote_port))
    except socket.error as msg:
        try:
            messages="%s - %s" % (msg[0],msg[1])
        except:
            messages=tb.format_exc()
        logging.info("TheShields Connecting to %s error %s" % (logssrv, messages))
        logging.debug('[SOCKET]: Connection Error: Unable to connect %s' % messages)
        return ''

    try:
        sock.settimeout(timeout)
        sock.send(query)
    except socket.error as msg:
        try:
            messages="%s - %s" % (msg[0],msg[1])
        except:
            messages=tb.format_exc()
        sock.close()
        logging.info("TheShields Sending Data to %s error %s" % (logssrv, messages))
        logging.debug('[SOCKET]: Connection Error: Unable to send data %s' % messages)
        return ''

    try:
        sock.settimeout(timeout)
        response = sock.recv(2048)
    except socket.error as msg:
        try:
            messages="%s - %s" % (msg[0],msg[1])
        except:
            messages=tb.format_exc()
        sock.close()
        logging.info("TheShields Receiving Data from %s error %s" % (logssrv, messages))
        logging.debug('[SOCKET]: Connection Error: Unable to receive  data %s' % messages)
        return ''

    response = response.strip()
    sock.close()
    if len(response) == 0:
        logging.info("TheShields Receive no data from %s" % (logssrv))
    logging.debug('[SOCKET]: RESPONSE: "%s"' % response)
    return response
# ------------------------------------------------------------------------------------------------------------------------------------------------------------------
def GetSSLCertificateConnect(CONNECT,PROXY_ADDR,key,logging):
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.connect(PROXY_ADDR)
        
    except socket.timeout:
        logging.debug("[CLIENT]:GetSSLCertificateConnect:: Timed OUT")
        return None
    
    except:
        logging.info(tb.format_exc())
        return None
    
    try:
        s.send(CONNECT)
        dummy=s.recv(4096) 
        ctx = SSL.Context(SSL.SSLv23_METHOD)
        ctx.set_verify(SSL.VERIFY_PEER, verify_cb)
        ss = SSL.Connection(ctx, s)
        ss.set_connect_state()
        
    except:
        logging.debug("[CLIENT]:GetSSLCertificateConnect:: SNI-> "+url+" ERROR RECEIVE CNX" )     
        logging.debug(tb.format_exc())

        if SquidGuardClientEnableMemory==1:
            MEM_BLOCK_LEN=len(MEM_SNI)
            if MEM_BLOCK_LEN> SquidGuardClientMaxMemoryItems: MEM_SNI.clear()
            MEM_SNI[key]="" 
        return None
    
    try:
        ss.do_handshake()
    except SSL.WantReadError:
        logging.debug("[CLIENT]:GetSSLCertificateConnect:: SSL.WantReadError")
        return ss
    except:
        logging.debug(tb.format_exc())
        ss.shutdown()
        ss.close()
        logging.debug("[CLIENT]:GetSSLCertificateConnect:: Failed")
        logging.debug("[CLIENT]:GetSSLCertificateConnect::do_handshake()::SNI-> ERROR" )     
        
        if SquidGuardClientEnableMemory==1:
            MEM_BLOCK_LEN=len(MEM_SNI)
            if MEM_BLOCK_LEN> SquidGuardClientMaxMemoryItems: MEM_SNI.clear()
            MEM_SNI[key]=""     
        return None
    
    return ss
def GetSSLCertificate(url,port,logging):
    if UfdbResolvSSLCertificates==0:
        logging.debug("[CLIENT]:GetSSLCertificate:: UfdbResolvSSLCertificates ==0")
        return ""
        
    key=url+str(port)
    if SquidMgrListenPort==0:
        logging.debug("[CLIENT]:GetSSLCertificate:: Unable to obtain SquidMgrListenPort!")
        return ""
        
    if SquidGuardClientEnableMemory==1:
        if key in MEM_SNI:
            logging.debug("[CLIENT]:GetSSLCertificate:: SNI-> MEMORY "+url+" issued_to:"+MEM_SNI[key] )    
            return MEM_SNI[key]
    
    PROXY_ADDR = ("127.0.0.1",SquidMgrListenPort)
    CONNECT = "CONNECT %s:%s HTTP/1.0\r\nConnection: close\r\n\r\n" % (url, port)
    ss=GetSSLCertificateConnect(CONNECT,PROXY_ADDR,key,logging)
    if ss==None: return ""

    cert = ss.get_peer_certificate()
    if cert==None:
        logging.debug("[CLIENT]:GetSSLCertificate:: SNI-> "+url+"cert is none!" )
        return ""
        
    subject=cert.get_subject()
    issued_to = subject.CN
    issuer = cert.get_issuer()
    issued_by = issuer.CN
    logging.debug("[CLIENT]:GetSSLCertificate:: SNI-> "+url+" issued_to:"+issued_to+" issued_by:"+issued_by )        
    ss.shutdown()
    ss.close()
    
    if len(issued_to)>0:
        issued_to=issued_to.replace("*.","")
        if issued_to=="AnyNet Relay": issued_to="anydesk.com"
        
        if SquidGuardClientEnableMemory==1:
            MEM_BLOCK_LEN=len(MEM_SNI)
            if MEM_BLOCK_LEN> SquidGuardClientMaxMemoryItems: MEM_SNI.clear()
            MEM_SNI[key]=issued_to
        
        
        return issued_to
    return ""
def FindKeyAccount(username,ipaddr,mac):
    if username=="-": username=""
    if ipaddr=="-":ipaddr=""
    if ipaddr == "127.0.0.1": ipaddr = ""
    if mac=="-":mac=""
    if mac=="00:00:00:00:00:00": mac=""
    if len(username)>3: return username
    if len(mac)>3: return mac
    if len(ipaddr) > 3: return ipaddr

LoadWhiteList()
CountSleep=0
while True:
    NEW_DOMAIN=''
    REFERER=''
    EXTEND_1=''
    EXTEND_2=''
    EXTEND_3=''
    EXTEND_4=''
    WEBFILTERING_OK=''
    STOP=False
    line = sys.stdin.readline()
    line = line.strip()
    size = len(line)
    
    if line =='': STOP = True
    if size < 40  : STOP = True
    if STOP:
        logging.debug("[CLIENT] Sleeping 1s "+str(CountSleep)+"/5")
        time.sleep( 1 )
        CountSleep=CountSleep+1
        if CountSleep>2:
            logging.info("[CLIENT] Die() maxcount > 2 ("+str(CountSleep)+") -> Exit(0)")
            sys.exit(0)
        continue

    logging.debug('[CLIENT]')
    logging.debug('[CLIENT] --------------------------------------------------------------')
    logging.debug('[CLIENT] Receive: size:'+str(size)+' "'+line+'"')
    
    array=line.split(" ")
    
    logging.debug('[CLIENT] Channel: '+array[0])
    Channel=int(array[0])
    OutputChannel=1
    CLIENT_MAC=''
    SNI_DOMAIN=''
    PROXY_URL=array[1]
    SOURCE_URL=PROXY_URL
    CLIENT_IP=array[2]
    PROXY_USER=array[3]
    SOURCE_UID=array[3]
    PROXY_PROTO=array[4]
    PROXY_IP=array[5]
    PROXY_PORT=array[6]
    CLIENTZ=CLIENT_IP.split("/")
    CLIENT_IP=CLIENTZ[0]
    CLIENT_HOSTNAME=CLIENTZ[1]
    REQUEST_PROTO=0
    REQUEST_ALLOWED_HOTSPOT=0
    ADDTAG1=''
    URL_DOMAIN_PORT=80
    CDIR_TO_CHECK=''
    ToUfdbCdir=''
    REFERER=''
    bump_mode=''
    HTTPS=False
    ISSSNI=False
    results=''
    WHITE=False
    TOKENS=[]


    try:
        matches=re.search('myip=(.*)',PROXY_IP)
        if matches: PROXY_IP=matches.group(1)
        matches=re.search('myport=([0-9]+)',PROXY_PORT)
        if matches: PROXY_PORT=matches.group(1)
    except:
        pass


    
    matches=re.search('^(ftp|ftps):\/\/(.*)',PROXY_URL)
    if matches:
        logging.debug('[CLIENT] FTP PROTOCOL in '+PROXY_URL+" change to HTTP")
        PROXY_URL="http://"+matches.group(2)

    matches = re.search('bump_mode=([a-z\-]+)', line)
    if matches: bump_mode = matches.group(1)
        
    
    matches=re.search('^(http|https):\/\/',PROXY_URL)
    if not matches: PROXY_URL='http://'+PROXY_URL

    if matches:
        if matches.group(1)== 'https':
            logging.debug('[CLIENT] HTTPS PROTOCOL in ' + PROXY_URL)
            HTTPS=True

    if PROXY_PROTO == "CONNECT": HTTPS=True


            

    if len(array)>7:
        EXTEND_1=array[7]
        EXTEND_2=array[8]
        
    if len(array)>8:
        EXTEND_3=array[9]
        
    if len(array)>9:
        EXTEND_4=array[10]
        EXTEND_4=EXTEND_4.replace('%0D%0A',' ')
        EXTEND_4=EXTEND_4.replace('%20',' ')
        logging.debug('[CLIENT] R-EXTEND_4 = '+EXTEND_4)

    if PROXY_USER == '-': PROXY_USER=''
    if len(PROXY_USER)>0:
        matches=re.search('^(.*?)@',PROXY_USER)
        if matches: PROXY_USER=matches.group(1)
        matches=re.search('^.*?\/(.+)',PROXY_USER)
        if matches: PROXY_USER=matches.group(1)
        
    if len(SOURCE_UID)>1:
        matches=re.search('^(.*?)@',SOURCE_UID)
        if matches: SOURCE_UID=matches.group(1)
        matches=re.search('^.*?\/(.+)',SOURCE_UID)
        if matches: SOURCE_UID=matches.group(1)        
    
    
    matches=re.search('mac=(.*)',EXTEND_1)
    if matches: CLIENT_MAC=matches.group(1)

    matches=re.search('sni=(.+)',EXTEND_2)
    if matches: SNI_DOMAIN=matches.group(1)

    matches=re.search('referer=(.*)',EXTEND_3)
    if matches: REFERER=matches.group(1)
    
     
    matches=re.search('webfiltering.*?PASS',EXTEND_4)
    if matches:
        logging.debug('[CLIENT] ------------------------ P A S S ------------------------')
        print(str(Channel)+' ERR webfiltering=pass')
        sys.stdout.flush()
        continue

    if len(SNI_DOMAIN) > 1: ISSSNI=True
    if SNI_DOMAIN == '-': SNI_DOMAIN=''
    if REFERER == '-': REFERER=''
        
    parsed = urlparse(PROXY_URL)
    URL_DOMAIN=parsed.hostname
    URL_DOMAIN_PORT=parsed.port
    NEW_DOMAIN=""
    
    if len(REFERER)>3:
        REFERER_parsed = urlparse(REFERER)
        REFERER=REFERER_parsed.hostname
    
    
    if is_valid_ip(URL_DOMAIN):
        
        if len(SNI_DOMAIN) == 0:
            try:
                if PROXY_PROTO=="CONNECT":
                    if PythonpyOpenSSLMissing==0:
                        SNI_DOMAIN=GetSSLCertificate(URL_DOMAIN,URL_DOMAIN_PORT,logging)
                        logging.debug("[CLIENT]:SNI-> "+URL_DOMAIN+":"+str(URL_DOMAIN_PORT)+'='+SNI_DOMAIN)
            except:
                logging.info("[CLIENT]: SNI-> ERROR")
                logging.info(tb.format_exc())
                
                    
        
        if SNI_DOMAIN and len(SNI_DOMAIN) > 0: NEW_DOMAIN=SNI_DOMAIN
            

    if len(NEW_DOMAIN)>2:
        PROXY_URL=PROXY_URL.replace(URL_DOMAIN,NEW_DOMAIN)
        URL_DOMAIN=NEW_DOMAIN

    prepare_data = {}
    prepare_data["ACTION"] = "THESHIELDS"
    prepare_data["CHOOSE"] = FindKeyAccount(PROXY_USER,CLIENT_IP,CLIENT_MAC)
    prepare_data["USERNAME"] = PROXY_USER
    prepare_data["ipaddr"] = CLIENT_IP
    prepare_data["mac"] = CLIENT_MAC
    prepare_data["sitename"] = URL_DOMAIN
    prepare_data["method"] = PROXY_PROTO
    prepare_data_text = serialize(prepare_data)

    try:
        results = SendSocket(TheShieldsIP, TheShieldsPORT, KSRNClientTimeOut, prepare_data_text,logging)
        if len(results) > 5:
            try:
                result = unserialize(results)
                error = result["error"]
                CATEGORY = int(result["categoy_id"])
                CATEGORY_NAME = result["categoy_name"]
                ACTION = result["ACTION"]
                VIRTUAL_USER = result["VIRTUAL_USER"]
                CountryCode = result["COUNTRY_CODE"]

                if ACTION == "WHITELIST":
                    TOKENS.append("rblpass=yes")
                    TOKENS.append("webfiltering=pass")
                    WHITE = True

                if ACTION == "WHITE":
                    TOKENS.append("srn=WHITE rblpass=yes")
                    TOKENS.append("webfiltering=pass")
                    WHITE = True

                if CATEGORY > 0:
                    CATEGORY_NAME = CATEGORY_NAME.replace(" ", "_")
                    CATEGORY_NAME = CATEGORY_NAME.replace("/", "_")
                    TOKENS.append("category=%s category-name=%s clog=cinfo:%s-%s; " % (
                    CATEGORY, CATEGORY_NAME, CATEGORY, CATEGORY_NAME))
                else:
                    TOKENS.append("category=0 category-name=Unknown clog=cinfo:0-unknown;")

            except:
                logging.info(tb.format_exc())




    except:
        logging.info(tb.format_exc())



        
    if not WHITE:
        if ifIsInWhitelist(URL_DOMAIN):
            TOKENS.append("webfiltering=pass")
            continue

    if WHITE:
        logging.debug("[CLIENT]: " + URL_DOMAIN + ": IS WHITE-LISTED --> PASS")
        Out="%s ERR %s" % (Channel,"".join(TOKENS) )
        print(Out)
        sys.stdout.flush()
        continue
        
            

        
    






    
    REFERER=str(REFERER)
    SNI_DOMAIN=str(SNI_DOMAIN)
    EXTEND_1=str(EXTEND_1)
    EXTEND_2=str(EXTEND_2)
    
    logging.debug('[CLIENT] Receive: PROXY_URL...:'+PROXY_URL)
    logging.debug('[CLIENT] Receive: URL_DOMAIN..:'+str(URL_DOMAIN))
    logging.debug('[CLIENT] Receive: CLIENT_IP...:'+CLIENT_IP)
    logging.debug('[CLIENT] Receive: PROXY_USER..:'+PROXY_USER)
    logging.debug('[CLIENT] Receive: PROXY_PROTO.:'+PROXY_PROTO)
    logging.debug('[CLIENT] Receive: PROXY_IP....:'+PROXY_IP)
    logging.debug('[CLIENT] Receive: PROXY_PORT..:'+PROXY_PORT)
    logging.debug('[CLIENT] Receive: SNI_DOMAIN..:'+SNI_DOMAIN)
    logging.debug('[CLIENT] Receive: Referer.....:'+str(REFERER))
    logging.debug('[CLIENT] Receive: TAG.........:'+WEBFILTERING_OK)
    logging.debug('[CLIENT] Receive: EXTEND_1....:'+EXTEND_1)
    logging.debug('[CLIENT] Receive: EXTEND_2....:'+EXTEND_2)
    logging.debug('[CLIENT] Receive: BUMP MODE...:' + bump_mode)
    if PROXY_PROTO=='GET':
        REQUEST_PROTO=1
        REQUEST_ALLOWED_HOTSPOT=1
    if PROXY_PROTO=='CONNECT': REQUEST_PROTO=1
        

    if len(ToUfdbCdir)>0:
        logging.debug('[CLIENT] Pass to Web-Filtering service (CDIR)')
        try:
            ufdbclass.PROXY_PROTO=PROXY_PROTO
            ufdbclass.Referer=REFERER
            ufdbclass.CLIENT_MAC=CLIENT_MAC
            ufdbclass.HTTPS=HTTPS
            ufdbclass.ISSSNI=ISSSNI
            if ufdbclass.SendToUfdb(ToUfdbCdir,Channel,sys,PROXY_URL,CLIENT_IP,PROXY_USER,CDIR_TO_CHECK):
                logging.debug('[CLIENT] Class ufdbclass -> BLOCK, aborting')
                continue
        except Exception as e:
            logging.info(tb.format_exc())
            logging.info('FATAL! Exception while requesting CDIR to Web-Filtering Engine service')        
        
    logging.debug('[CLIENT] Pass to Web-Filtering service')
    
    try:
        ufdbclass.PROXY_PROTO=PROXY_PROTO
        ufdbclass.Referer=REFERER
        ufdbclass.CLIENT_MAC=CLIENT_MAC
        ufdbclass.HTTPS=HTTPS
        ufdbclass.ISSSNI=ISSSNI
        if ufdbclass.SendToUfdb(ToUfdb,Channel,sys,PROXY_URL,CLIENT_IP,PROXY_USER,URL_DOMAIN):
            logging.debug('[CLIENT] Class ufdbclass -> BLOCK, aborting')
            continue
    except Exception as e:
        logging.info(tb.format_exc())
        logging.info('FATAL! Exception while requesting Web-Filtering Engine service')
        
#-----------------------------------------------------------------------------------------------------

    logging.debug('[CLIENT] FINAL = OK -> Continue')
    print(str(Channel)+' ERR webfiltering=pass')
    sys.stdout.flush()
    

