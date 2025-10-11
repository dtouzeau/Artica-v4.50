#!/usr/bin/python

import sys
import os
sys.path.append('/usr/share/artica-postfix/ressources')
import logging
import string
import re
import traceback as tb
import time
import datetime
import hashlib
import memcache
from unix import *

LOG_LEVEL=logging.INFO
StoreIDDebugClient=GET_INFO_INT("StoreIDDebugClient")
if StoreIDDebugClient == 1: LOG_LEVEL=logging.DEBUG

logging.basicConfig(format='%(asctime)s [%(levelname)s] [%(process)d] %(message)s',filename='/var/log/squid/storeid.log',  filemode='a',level=LOG_LEVEL)
logging.raiseExceptions = False

def ReadRules():
    R={}
    Banned={}
    
    Banned["947bcebb70d8aef6e64c44f07dda7bbc"]=True
    Banned["763168f61d5579d4525c3c584061d5ac"]=True
    Banned["fd158b2b95a052554b43dff6473c9810"]=True
    Banned["8d0e048eccc396002e019b611d8a85df"]=True
    Banned["d00049c0c1364b5c9f9cc1cd281375c5"]=True
    Banned["0171ee2dd003648d38214881bfd81c71"]=True
    Banned["85aee13c24e655547cbfdad886293cb7"]=True
    Banned["4ddcb5082786bb34fe33de214fb51c4a"]=True
    Banned["65358c0159c49f1356a276f4f87f25d2"]=True
    Banned["86fa023f5e61a43499470815a0909515"]=True
    Banned["b17559e3e5c25eefc8bc55dc8ab00139"]=True
    Banned["947bcebb70d8aef6e64c44f07dda7bbc"]=True
    Banned["8d0e048eccc396002e019b611d8a85df"]=True
    Banned["d00049c0c1364b5c9f9cc1cd281375c5"]=True
    Banned["0171ee2dd003648d38214881bfd81c71"]=True
    Banned["85aee13c24e655547cbfdad886293cb7"]=True
    Banned["4ddcb5082786bb34fe33de214fb51c4a"]=True
    Banned["65358c0159c49f1356a276f4f87f25d2"]=True
    Banned["86fa023f5e61a43499470815a0909515"]=True
    Banned["b17559e3e5c25eefc8bc55dc8ab00139"]=True
    Banned["2be6af6da2b3fd20e009e11c4f19de34"]=True
    Banned["56e848ec5a5265ac075d325a9df2dd8c"]=True
    Banned["ddf55ec24f5fdb91806324520cc4b090"]=True
    Banned["3d2cfb6153d1c5d5568177bb44199167"]=True
    Banned["3d9afc338de61a758e09498c9d5dadc8"]=True
    Banned["1dca4a5b1e3de5c50e356c523d519390"]=True
    Banned["aea46270655b6b396c9dec43c2fd5f63"]=True
    Banned["f265c9d9b69df00fb00c316d41317462"]=True
    
    if not os.path.exists("/etc/squid3/storeid_rewrite"): return R
    
    with open("/etc/squid3/storeid_rewrite","r") as f:
        for txt in f :
            txt=txt.rstrip('\n')
            matches=re.search('(.+?)\s+(.+)',txt)
            if not matches: continue
            zmd5=hashlib.md5(matches.group(1)).hexdigest()
            if zmd5 in Banned: continue
            logging.debug('[REGEX]: '+zmd5 +' ' +matches.group(1))
            R[matches.group(1)]=matches.group(2)
    
    return R
# ---------------------------------------------------------------------------------------------------

def ParseRules(Rules,url):
    t0 = time.time()
    for Rule in Rules:
        ReturnEx=Rules[Rule]
        matches=re.search(Rule,url)
        if not matches: continue
        logging.debug("[CLIENT] "+url)
        logging.debug("[CLIENT] Matches "+Rule)
        logging.debug("[CLIENT] Matches-Replace "+ReturnEx)
        CountOfGroups=len(matches.groups())
        ReturnEx.replace(".squid.local",".SQUIDINTERNAL")
        c=0
        for mgroup in matches.groups():
            c=c+1
            ReturnEx=ReturnEx.replace("$"+str(c), mgroup)
            if c>10:
                logging.info('[CLIENT] Too much loop  in '+str(Rule)+" -->" +str(CountOfGroups)+" groups")
                return None
                
                
            
        ExecuteTime=time.time() - t0      
            

    return None
# ---------------------------------------------------------------------------------------------------

Rules={}
Rules=ReadRules()
logging.info('[CLIENT] Starting Thread with '+str(len(Rules))+ ' rules')

try:
    if not os.path.exists("/etc/artica-postfix/settings/Daemons/AllowWindowsUpdates"): file_put_contents("/etc/artica-postfix/settings/Daemons/AllowWindowsUpdates","1")
except:
    logging.info(tb.format_exc())

AllowWindowsUpdates=GET_INFO_INT("AllowWindowsUpdates");
ForceWindowsUpdateCaching=GET_INFO_INT("ForceWindowsUpdateCaching");
EnableXCC=GET_INFO_INT("EnableXCC");
if AllowWindowsUpdates==0: ForceWindowsUpdateCaching=0
logging.info('[CLIENT] Starting Thread with AllowWindowsUpdates........:'+str(AllowWindowsUpdates))
logging.info('[CLIENT] Starting Thread with ForceWindowsUpdateCaching..:'+str(ForceWindowsUpdateCaching))

while True:
    Result=None
    try:
        line = sys.stdin.readline().strip()
    except:
        logging.info("[CLIENT] I/O Error on readline...")
        line=""
        sys.exit()
        
    
    size = len(line)
    logging.debug("[CLIENT] Receiving '"+line+"' "+str(size)+" bytes")
    connexion_index=0
        
    if size<40:
        logging.info("[CLIENT] TERMINATE....")
        sys.exit(0)
        
    if line =='':
        logging.info("[CLIENT] TERMINATE....")
        sys.exit(0)
        break
        
            
  
    MainArray=line.split(" ")
    connexion_index=int(MainArray[0])
    connexion_text=str(connexion_index)+" "
    

    
    try:
        url=MainArray[1]
        ipaddr=MainArray[2]
       
    except:
        logging.info("[CLIENT] Broken pipe....'"+line+"'")
        print(connexion_text+"ERR")
        sys.stdout.flush()        
        continue

    LineToSend=None
    zmd5 = hashlib.md5(url).hexdigest()
    mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
    LineToSend = mc.get("HYPERCACHE:" + zmd5)
    if LineToSend is not None:
        print(connexion_text + LineToSend)
        sys.stdout.flush()
        continue


    matches=re.search('\/\/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)\/',url)
    if matches:
        mc.set("HYPERCACHE:" + zmd5, "ERR", 14400)
        print(connexion_text+"ERR")
        sys.stdout.flush()
        continue
        
    matches=re.search('apple.com\/search',url)
    if matches:
        mc.set("HYPERCACHE:" + zmd5, "ERR", 14400)
        print(connexion_text+"ERR")
        sys.stdout.flush()
        continue
    matches=re.search('\/.*?(\.estat\.com)',url)
    if matches:
        mc.set("HYPERCACHE:" + zmd5, "ERR", 14400)
        print(connexion_text+"ERR")
        sys.stdout.flush()
        continue 
    
    matches=re.search('\/(wpad|proxy)\.(pac|dat)$',url)
    if matches:
        mc.set("HYPERCACHE:" + zmd5, "ERR", 14400)
        print(connexion_text+"ERR")
        sys.stdout.flush()
        continue    
    
    matches=re.search('(msftncsi|articatech|webmail\.)',url)
    if matches:
        mc.set("HYPERCACHE:" + zmd5, "ERR", 14400)
        print(connexion_text+"ERR")
        sys.stdout.flush()
        continue
    
    matches=re.search(':\/\/ads\.',url)
    if matches:
        mc.set("HYPERCACHE:" + zmd5, "ERR", 14400)
        print(connexion_text+"ERR")
        sys.stdout.flush()
        continue
    
# XCC1 --------------------------------------------------------------------------------------------------------------------------------
    XCCDomain="SQUIDINTERNAL/"
    if EnableXCC==1: XCCDomain="unveiltech.internal/id="
    matches=re.search('^http[s]?:\/\/.*?\.itunes\.apple\.com\/(.*?\.(ipa|m4p))',url)
    if matches:
        Ouput="OK store-id=http://appleitunes."+XCCDomain+hashlib.md5(matches.group(1)).hexdigest()
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text+Ouput)
        sys.stdout.flush()
        continue
       
    matches=re.search('^http[s]?:\/\/.*\.phobos\.apple\.com\/(.*\.(mp4|ipa|m4a|m4v)).*',url)
    if matches:
        Ouput="OK store-id=http://appleapps." + XCCDomain + hashlib.md5(matches.group(1)).hexdigest()
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text+Ouput)
        sys.stdout.flush()
        continue

    matches = re.search('graph\.facebook\.com\/\?callback=(.+?)&ids=(.+?)&', url)
    if matches:
        Ouput = "OK store-id=http://graph.facebook.SQUIDINTERNAL/" + matches.group(1) + "/" + matches.group(2);
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue

    matches = re.search('\/\/pixel\.wp\.com\/g\.gif\?', url)
    if matches:
        Ouput = "OK store-id=http://wordpress.pixel.tracker.SQUIDINTERNAL/g.gif"
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue

    matches = re.search('\/\/(.+?)\/wp-(includes|content).*?\/(js|build)\/(.+?)\.js', url)
    if matches:
        Ouput = "OK store-id=http://wordpress.javascript." + matches.group(1) + ".SQUIDINTERNAL/" + matches.group(2) + ".js"
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue


    matches=re.search('\/.*?download\.windowsupdate.com\/(.+?)\.(cab|dat|dsft|esd|exe|msi|psf|zip)',url)
    if matches:
        Ouput="OK store-id=http://windowsupdate."+XCCDomain+hashlib.md5(matches.group(1)+"."+matches.group(2)).hexdigest()+"."+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text+Ouput)
        sys.stdout.flush()
        continue


    matches=re.search('^http[s]?:\/\/.*\.(microsoft|windowsupdate)\.com\/.*\/((.+?)\.(cab|dat|dsft|esd|exe|msi|psf|zip))',url)
    if matches:
        Ouput="OK store-id=http://windowsupdate."+XCCDomain+hashlib.md5(matches.group(2)).hexdigest()
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text+Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('^http[s]?:\/\/.*\.microsoft\.com\/filestreamingservice\/(filestreamingservice\/.*)\?.*',url)
    if matches:
        Ouput="OK store-id=http://microsoftupdatewin10."+XCCDomain+hashlib.md5(matches.group(1)).hexdigest()
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text+Ouput)
        sys.stdout.flush()
        continue     
# XCC1 --------------------------------------------------------------------------------------------------------------------------------

    matches = re.search('http[s]?:\/\/(.*?)\.akamaihd\.net\/.*?([0-9]+)_.*?\.mp4.*?\/(.+?)\.ts',url)
    if matches:
        Ouput = "OK store-id=http://akamaihd.SQUIDINTERNAL/"+matches.group(1)+"_"+matches.group(2)+"_"+matches.group(3)+".ts"
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue

    matches=re.search("\/(.+?)\.akamaized\.net\/([a-z\/\-]+)\/(.+?)\?(.+?)f=([a-z]+)",url)
    if matches:
        zmd=hashlib.md5(matches.group(3)+matches.group(4)).hexdigest()
        Ouput = "OK store-id=http://akamaized.SQUIDINTERNAL/" + matches.group(1) + "/" + matches.group(2)+ "/" +zmd+"."+matches.group(5)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue

    matches = re.search("(.*)\.gvt1\.com\/.*?\/chromewebstore\ /.*?\/(.+)\.([a-z]+)\?",url)
    if matches:
        Ouput = "OK store-id=http://chromewebstore.SQUIDINTERNAL/" + matches.group(2) + "." + matches.group(3)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue

    
    matches=re.search('\.(steamcontent|steamstatic|steamusercontent)\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://steam.SQUIDINTERNAL/"+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    matches=re.search('\/\/(.+?)\..+?\.brightcove\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://"+matches.group(1)+".SQUIDINTERNAL/"+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue     
    
    
    matches=re.search('\.windowsupdate\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://windowsupdate.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    matches=re.search('\.microsoft\.[a-z]+\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://microsoft.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue     
    
    matches=re.search('\.googleapis\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://googleapis.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue     
        	
    matches=re.search('\.debian\.org\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://debian.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
            
            
    matches=re.search('\.geo.kaspersky\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://kaspersky.geo.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.sendibm[0-9]+\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://sendibm.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.nvidia\.[a-z]+\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://nvidia.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.speedtest\.[a-z]+\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://speedtest.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.as[0-9]+\.net\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://speedtest.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue

    matches=re.search('\.unity3d\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://unity3d.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue    
    
    matches=re.search('\.testdebit\.info+\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://speedtest.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('speedtest\..+?\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://speedtest.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue    
    
    matches=re.search('\.(.+?)-amazon.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://amazon.SQUIDINTERNAL/"+matches.group(1)+"/"+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    matches=re.search('\.(ebayimg|ebaystatic|ebay)\.([a-z]+)\/(.+?)($|\?)',url)
    if matches:
        Ouput = "OK store-id=http://"+matches.group(1)+"."+matches.group(2)+".SQUIDINTERNAL/"+matches.group(3)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('http:\/\/.+?.cdninstagram\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://instagram.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.foxitsoftware\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://foxitsoftware.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    matches=re.search('\.cloudfront\.[a-z]+\/(.+?)($|\?)',url)
    if matches:
        forward="http://cloudfront.SQUIDINTERNAL/"+matches.group(1)
        forward.replace('//','/')
        Ouput = "OK store-id="+forward
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    matches=re.search('\.nflxso\.net\/(.+?)($|\?)',url)
    if matches:
        forward="http://nflxso.SQUIDINTERNAL/"+matches.group(1)
        forward.replace('//','/')
        Ouput = "OK store-id="+forward
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue    
    
    matches=re.search('^https?:\/\/(.*?).gvt[0-9]\.com\/(.*?)\.*(zip|exe|msi)',url)
    if matches:
        Ouput = "OK store-id=http://google-installer.SQUIDINTERNAL/"+matches.group(2)+"."+matches.group(3)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.android\.clients\.google\.[a-z]+\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://androidupdates.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue    
   
    matches=re.search('\.dailymotion\.([a-z\.]+)\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://dailymotion.SQUIDINTERNAL/"+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue

    matches=re.search('^http:\/\/vid[0-9]+\.ak\.dmcdn\.net\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://vid.dmcdn.net.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    

    matches=re.search('^http:\/\/s[0-9]+\.dmcdn\.net\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://pic.dmcdn.net.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue    

        
    matches=re.search('^http:\/\/[1-4]\.bp\.blogspot\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://blog-cdn.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    extension_list="jpg|jpeg|gif|png|m4a|mp4|zip|ogg|js|css|json|exe|tar|tar\.gz|gz|gzip|tz|bin"
    
    matches=re.search('\.(akamaihd|deltacdn|wp|wordpress|rackcdn|amazonaws)\.([a-z]+)\/(.+?)\.('+extension_list+')($|\?)',url)
    if matches:
        Ouput = "OK store-id=http://"+matches.group(1)+".SQUIDINTERNAL/"+matches.group(3)+"."+ matches.group(4)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    matches=re.search('\.f1g\.fr/(.+?)\.('+extension_list+')($|\?)',url)
    if matches:
        Ouput = "OK store-id=http://f1g.fr.SQUIDINTERNAL/"+matches.group(1)+"."+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue    


    
    matches=re.search('appldnld\.apple\.com\/(.+?)\/.*?SoftwareUpdate\/(.+?)\.([a-z]+)$',url)
    if matches:
        Ouput = "OK store-id=http://appldnld-SoftwareUpdate.SQUIDINTERNAL/"+matches.group(1)+"/"+matches.group(2)+"."+matches.group(3)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('gspa[0-9]+\.[a-z]+\.apple\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://gspa-apple.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    
    matches=re.search('\.(.+?)static\.([a-z\.]+)\/(.+?)\.('+extension_list+')($|\?)',url)
    if matches:
        Ouput = "OK store-id=http://"+matches.group(1)+"static.SQUIDINTERNAL/"+matches.group(3)+"."+matches.group(4)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.(.+?)cdn\.([a-z\.]+)\/(.+?)\.('+extension_list+')($|\?)',url)
    if matches:
        Ouput = "OK store-id=http://"+matches.group(1)+"cdn.SQUIDINTERNAL/"+matches.group(3)+"."+matches.group(4)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.blogspot.([a-z\.]+)\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://blogspot.SQUIDINTERNAL/"+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue    
  
    	
    matches=re.search('clients[0-9]+\.google\.([a-z\.]+)\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://clients.google.SQUIDINTERNAL/"+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    matches=re.search('\.fbcdn\.net\/(.+?)\.('+extension_list+')[\?|$]',url)
    if matches:
        Ouput = "OK store-id=http://fbcdn.SQUIDINTERNAL/"+matches.group(1)+"."+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    
    matches=re.search('gspa[0-9]+\.apple\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://gspa-apple.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue     
    
    matches=re.search('\/jquery-([0-9\.]+)\.([a-z]+)\.js',url)
    if matches:
        Ouput = "OK store-id=http://jquery.SQUIDINTERNAL/jquery-"+matches.group(1)+"."+matches.group(2)+".js"
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('/jquery\.([a-z]+)\.js\?ver=([0-9a-z\.]+)',url)
    if matches:
        Ouput = "OK store-id=http://jquery.SQUIDINTERNAL/jquery"+matches.group(1)+"."+matches.group(2)+".js"
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue     
    
    matches=re.search('\/jquery(\.|-)([a-z]+)\.js',url)
    if matches:
        Ouput = "OK store-id=http://jquery.SQUIDINTERNAL/jquery"+matches.group(1)+matches.group(2)+".js"
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('^http:\/\/\.deezer.com/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://deezer.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    matches=re.search('^http:\/\/(.+?)\.edgesuite\.(.+?)\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://"+matches.group(1)+".SQUIDINTERNAL/"+matches.group(3)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('^http:\/\/.*?delivery\.mp\.microsoft\..+?\/(.+?)',url)
    if matches:
        Ouput = "OK store-id=http://windowsupdate.SQUIDINTERNAL/windows10/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('^http:\/\/.*?download\.windowsupdate\.com\/(.+?)',url)
    if matches:
        Ouput = "OK store-id=http://windowsupdate.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue    
        
    matches=re.search('^http:\/\/.+?\.(windowsupdate|microsoft)\.com\/(.+?)\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip|psf|appx|esd)',url)
    if matches:
        if ForceWindowsUpdateCaching==0:
            print(connexion_text+"ERR windowsupdate=0")
            try:
                sys.stdout.flush()
            except:
                logging.debug("[CLIENT] FATAL crashes while flush out")
            continue
            
        Ouput = "OK store-id=http://windowsupdate.SQUIDINTERNAL/"+matches.group(1)+"/"+matches.group(2)+"."+matches.group(3)+" store-matches=default"
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
  
    
    
    
    matches=re.search('fonts\.googleapis.com\/css.*?family=(.+)',url)
    if matches:
        zmd5=hashlib.md5(matches.group(1)).hexdigest()
        Ouput = "OK store-id=http://fonts.googleapis.com.SQUIDINTERNAL/"+zmd5+".woff"
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
   
    
    matches=re.search('^http:\/\/[^\.]+\.phobos\.apple\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://phobos.apple.com.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.(photobucket|pbsrc)\.com/(.+)$',url)
    if matches:
        Ouput = "OK store-id=http://photobucket.SQUIDINTERNAL/"+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.(ebayimg|ebaystatic)\.[a-z]+\/(.+)$',url)
    if matches:
        Ouput = "OK store-id=http://ebay.SQUIDINTERNAL/"+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('http:\/\/.*?\.mzstatic\.com/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://mzstatic.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue    
    
    matches=re.search('http:\/\/.+?\.(.+?)\.edgesuite\.net\/.+?\/.+?\/(.+?)\/.+?\/(.+)$',url)
    if matches:
        Ouput = "OK store-id=http://phobos.apple.com.SQUIDINTERNAL/"+matches.group(1)+"/"+matches.group(2)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.rutube\.ru\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://rutube.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.geo\.kaspersky\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://kaspersky.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    matches=re.search('\.gravatar\.com\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://gravatar.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    matches=re.search('\.itc\.cn\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://itc.cn.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
    
    
    
    matches=re.search('\.nflximg\.[a-z]+\/(.+)',url)
    if matches:
        Ouput = "OK store-id=http://netflix.SQUIDINTERNAL/"+matches.group(1)
        mc.set("HYPERCACHE:" + zmd5, Ouput, 14400)
        print(connexion_text + Ouput)
        sys.stdout.flush()
        continue
   
    try:
        Result=ParseRules(Rules,url)
    except:
        Result=None
        
    
    if Result==None:
        print(connexion_text+"ERR")
        sys.stdout.flush()
        continue
        
        
    print(connexion_text+"OK store-id="+Result)
    sys.stdout.flush()
    continue
    
# ---------------------------------------------------------------------------------------------------