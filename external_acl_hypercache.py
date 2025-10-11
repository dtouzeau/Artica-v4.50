#!/usr/bin/python
import sys
import os
import time
sys.path.append('/usr/share/artica-postfix/ressources')
import traceback as tb
import logging
from unix import *

pid = os.getpid()
levelLOG=logging.INFO
#levelLOG=logging.DEBUG
        
logging.basicConfig(format='%(asctime)s [%(process)d] [%(levelname)s] %(message)s',filename='/var/log/squid/hypercache-extension.log',  filemode='a',level=levelLOG)
logging.raiseExceptions = False
logging.info("Starting new helper...")
CountSleep=0
HashMem={}

if os.path.exists("/etc/squid3/hypercache-client.conf"):
    with open("/etc/squid3/hypercache-client.conf","r") as f:
        for txt in f :
            txt=txt.rstrip('\n')
            if len(txt)==0: continue
            logging.debug("Rule: "+str(txt))
            HashMem[txt]=txt
            


while True:
    logging.debug("LOOP....")
    line = sys.stdin.readline().strip()
    logging.debug("Receive '"+line+"'")
    if len(line)<2:
        logging.debug("Sleeping 1s "+str(CountSleep)+"/5")
        time.sleep( 1 )
        CountSleep=CountSleep+1
        if CountSleep>5:
            logging.info("Die() maxcount >5 -> raise SystemExit(0)")
            raise SystemExit(0)
            continue
        continue
        
    MainArray=line.split(" ")
    userid=''
    clt_conn_tag=''
    try:
      
        Concurrency=MainArray[0]
        URI=MainArray[1]
        
        matches=re.search('cache_object:',URI)
        if matches:
            logging.debug("SKIP cache_object")
            sys.stdout.write(str(Concurrency)+" OK hypercache=0\n")
            sys.stdout.flush()
            continue
        
        matches=re.search('^\.msftncsi\.com',URI)
        if matches:
            logging.debug("SKIP msftncsi")
            sys.stdout.write(str(Concurrency)+" OK hypercache=0\n")
            sys.stdout.flush()
            continue
        
        
        
        matches=re.search('^http.*?:\/\/',URI)
        if not matches:
            logging.debug("SKIP "+URI)
            sys.stdout.write(str(Concurrency)+" OK hypercache=0\n")
            sys.stdout.flush()
            continue
        
        CountSleep=0
        logging.debug("Concurrency "+ str(Concurrency)+" URL:"+URI)
        
        MATCHED=False
        
        for Rule in HashMem:
            logging.debug("Cheking rule "+Rule)
            matches=re.search(Rule,URI)
            if matches:
                logging.debug("OK FOR --- > "+URI)
                sys.stdout.write(str(Concurrency)+" OK hypercache=1\n")
                sys.stdout.flush()
                MATCHED=True
                break
        
        if not MATCHED:
            logging.debug("OUTPUT NOTHING FOR "+URI)
            sys.stdout.write(str(Concurrency)+" OK hypercache=0\n")
            sys.stdout.flush()            
                     
    except:
        logging.info(tb.format_exc())
        sys.stdout.write(str(Concurrency)+" OK hypercache=0\n")
        sys.stdout.flush()
        continue