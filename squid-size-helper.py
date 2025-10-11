#!/usr/bin/env python
import sys
import os
sys.path.append('/usr/share/artica-postfix/ressources')
import logging
import string
import tldextract
import re
import traceback as tb
import time
import datetime
from categories import *
from unix import *

LOG_LEVEL=logging.INFO
SquidQuotaSizeDebug=GET_INFO_INT("SquidQuotaSizeDebug")
if SquidQuotaSizeDebug == 1: LOG_LEVEL=logging.DEBUG

logging.basicConfig(format='%(asctime)s [%(levelname)s] [%(process)d] %(message)s',filename='/var/log/squid/size-helper.debug',  filemode='a',level=LOG_LEVEL)
logging.raiseExceptions = False
logging.info('[CLIENT] Starting Thread.....')
CountSleep=0

 # ---------------------------------------------------------------------------------------------------
def GET_INT(fullpath):
    if not os.path.exists(fullpath): return 0
    data=file_get_contents(fullpath)
    return strtoint(data)
# ---------------------------------------------------------------------------------------------------
def MinutesToTen(min):
    if min<=10: return 0
    if min<=20: return 10
    if min<=30: return 20
    if min<=40: return 30
    if min<=50: return 40
    if min<=60: return 50
# ---------------------------------------------------------------------------------------------------


   # VOLUME_DETECT : 0 -> user, 1 website, 2 category , 3 user and website, 4 user and category
    # VOLUME_SCHE : 0 -> 10mn, 1 Hour, 2 day, 3 Month

VOLUME_DETECT_EXPL=[]
VOLUME_DETECT_EXPL.append("User")
VOLUME_DETECT_EXPL.append("Website")
VOLUME_DETECT_EXPL.append("Category")
VOLUME_DETECT_EXPL.append("User and website")
VOLUME_DETECT_EXPL.append("User and category")

VOLUME_SCHE_EXPL=[]
VOLUME_SCHE_EXPL.append("10mn")
VOLUME_SCHE_EXPL.append("Hour")
VOLUME_SCHE_EXPL.append("Day")
VOLUME_SCHE_EXPL.append("Month")

while True:
    STOP=False
    try:
        line = sys.stdin.readline()
        line = line.strip()
    except:
        logging.info("[CLIENT] I/O Error on readline...")
        line=""
        sys.exit()
        
    
    size = len(line)
    logging.debug("[CLIENT] Receiving '"+line+"' "+str(size)+" bytes")
    connexion_index=0
    CurrentPath=""
    
    if line =='':STOP=True
    if size<40: STOP=True
    
    if STOP:
        logging.debug("[CLIENT] (STOP) Sleeping 1s ["+str(CountSleep)+"s/2s]")
        time.sleep( 1 )
        CountSleep=CountSleep+1
        if CountSleep>2:
            logging.info("[CLIENT] TERMINATE....")
            sys.exit(0)
            break
        
        continue

            
    
    MainArray=line.split(" ")
    connexion_index=MainArray[0]
    try:
        uid=MainArray[1]
        ipaddr=MainArray[2]
        mac=MainArray[3]
        ipaddr2=MainArray[4]
        Website=MainArray[5]
        Rules=MainArray[6]
    except:
        logging.info("[CLIENT] Broken pipe....'"+line+"'")
        time.sleep( 1 )
        continue
        
    
    uid=uid.replace('%25',"")
    ipaddr=ipaddr.replace('%25',"")
    mac=mac.replace('%25',"")
    Website=Website.replace('%25',"")
    
    uid=uid.replace('%25',"")
    uid=uid.replace('%20'," ")
    ipaddr2=ipaddr2.replace("-","")
    if mac =='00:00:00:00:00:00': mac=""
    if ipaddr2>6: ipaddr=ipaddr2
    
    if Website.find(":") > 0:
        zsitename=Website.split(":")
        Website=zsitename[0]
    
    if not is_valid_ip(Website):
        ext = tldextract.extract(Website)
        Website=ext.domain+'.'+ext.suffix
    
    MainRules=Rules.split(":")
    GroupID=int(MainRules[0])
    VOLUME_DETECT=int(MainRules[1])
    VOLUME_SCHE=int(MainRules[2])
    VOLUME=int(MainRules[3])
    SUBFOLDER_USER=""
    category="cat_unknown"
    
    KEY_USER=""
    if len(uid)>1:
        KEY_USER=uid
        SUBFOLDER_USER=uid
    
    if KEY_USER=='':
        if len(mac)>3:
            KEY_USER=mac
            SUBFOLDER_USER=mac
            
    if KEY_USER=='':
        if len(ipaddr)>3:
            KEY_USER=ipaddr
            SUBFOLDER_USER=ipaddr
        
    zyear=int(datetime.now().strftime("%Y"))
    zday=int(datetime.now().strftime("%d"))
    zmonth=int(datetime.now().strftime("%m"))
    zhour=int(datetime.now().strftime("%H"))
    zMin=MinutesToTen(int(datetime.now().strftime("%M")))
    
    MinDir="/home/squid/tail/"+str(zyear)+"/"+str(zmonth)+"/" +str(zday)+"/"+ str(zhour)+"/"+str(zMin)
    HourDir="/home/squid/tail/"+str(zyear)+"/"+str(zmonth)+"/" +str(zday)+"/"+ str(zhour)
    DayDir="/home/squid/tail/"+str(zyear)+"/"+str(zmonth)+"/" +str(zday)
    MonthDir="/home/squid/tail/"+str(zyear)+"/"+str(zmonth)
 
    if VOLUME_SCHE==0: CurrentPath=MinDir
    if VOLUME_SCHE==1: CurrentPath=HourDir
    if VOLUME_SCHE==2: CurrentPath=DayDir
    if VOLUME_SCHE==3: CurrentPath=MonthDir
    
    if VOLUME_DETECT==0: FilePath=CurrentPath+"/"+SUBFOLDER_USER+"/TOT"
    if VOLUME_DETECT==1: FilePath=CurrentPath+"/"+Website+"/TOT"
    if VOLUME_DETECT==2:
        catz=Categories()
        catz.Debug=False
        catz.log=logger
        category=catz.GET_CATEGORY(Website)
        if len(category)<3: category="cat_unknown"
        category=category.replace("/","_")
        FilePath=CurrentPath+"/"+category+"/TOT"
        
    if VOLUME_DETECT==3: FilePath=CurrentPath+"/"+SUBFOLDER_USER+"/"+Website
    if VOLUME_DETECT==4: FilePath=CurrentPath+"/"+SUBFOLDER_USER+"/"+category    
   
 
    logging.debug("[CLIENT] [GRP:"+str(GroupID)+"] User: "+KEY_USER+" Find "+VOLUME_DETECT_EXPL[VOLUME_DETECT]+" each "+VOLUME_SCHE_EXPL[VOLUME_SCHE]+" For max "+str(VOLUME)+"Mo")
    
    if not os.path.exists(FilePath):
        logging.debug("[CLIENT] File: "+str(FilePath)+" Doesn't exists assume 0 MB")
        sys.stdout.write(connexion_index+" OK\n")
        sys.stdout.flush()
        continue
    
    
    size=float(GET_INT(FilePath))
    logging.debug("[CLIENT] [GRP:"+str(GroupID)+"] File: "+str(FilePath)+" == "+str(size)+"bytes")
    KB=size/1024
    MB=KB/1024
    logging.debug("[CLIENT] [GRP:"+str(GroupID)+"] Size: "+str(size)+" -  "+str(KB)+"Ko "+str(MB)+" Mo")
    if MB>VOLUME:
        logging.info("[CLIENT] [GRP:"+str(GroupID)+"]: "+KEY_USER+" ("+ Website+"/"+category+") Size:"+str(MB)+"M TRUE."+str(connexion_index))
        sys.stdout.write(str(connexion_index)+" OK tag=SIZE:"+str(GroupID)+"\n")
        sys.stdout.flush()
        continue
        
    logging.info("[CLIENT] [GRP:"+str(GroupID)+"]: "+KEY_USER+" ("+ Website+"/"+category+") Size:"+str(MB)+"M FALSE."+str(connexion_index))
    sys.stdout.write(str(connexion_index)+" OK\n")
    sys.stdout.flush()
    continue
    
# ---------------------------------------------------------------------------------------------------