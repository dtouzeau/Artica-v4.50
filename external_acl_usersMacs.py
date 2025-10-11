#!/usr/bin/env python
import sys
import os
import time
sys.path.append('/usr/share/artica-postfix/ressources')
import traceback as tb
import logging
from unix import *


def MicroHotSpotChecker(UserKey):
    HotSpotPath="/home/artica/microhotspot/Caches/"+UserKey
    if not os.path.exists(HotSpotPath):
        logging.debug(HotSpotPath+" No such file")
        return ''
    
    Conf=file_get_contents(HotSpotPath)
    array=Conf.split("|")
    xTime=array[0]
    UpdatedTime=[1]
    incativityperiod=array[2]
    MaxPeriod=int(array[3])
    UserName=array[4]
    now = datetime.now()
    CurrentTime=int(now.strftime("%s"))    
    parsedXtime=datetime.strptime(str(xTime),"%Y-%m-%d %H:%M:%S.%f")
    logintime=int(parsedXtime.strftime("%s"))
    FinishTime=parsedXtime + timedelta(minutes = MaxPeriod)
    FinalTime=int(FinishTime.strftime("%s"))
    Reste=FinalTime-CurrentTime
    logging.debug("Finish at "+str(FinishTime)+ " in " +str(Reste)+" seconds")
    if CurrentTime>FinalTime:
        logging.debug("Remove '"+HotSpotPath+"' and return Null")
        try:
            os.unlink(HotSpotPath)
        except:
            logging.info(tb.format_exc())
        return ""
    if len(UserName)>2: return UserName
    
def License_users(UserKey):
    CurrentTime=datetime.now().strftime("%Y-%m-%d-%H")
    if not os.path.exists("/home/squid/licenses"): return
    TargetFile="/home/squid/licenses/"+CurrentTime+"."+UserKey
    if os.path.exists(TargetFile):return
    file_put_contents(TargetFile,"OK")


def main(arg):
    pid = os.getpid()
    UfdbgClientDebug=GET_INFO_INT("UfdbgClientDebug")
    EnableArticaHotSpot=GET_INFO_INT("EnableArticaHotSpot")
    EnableSquidMicroHotSpot=GET_INFO_INT("EnableSquidMicroHotSpot")
   

    levelLOG=logging.INFO
    if UfdbgClientDebug==1:
        levelLOG=logging.DEBUG
        
    logging.basicConfig(format='%(asctime)s [%(process)d] [%(levelname)s] %(message)s',filename='/var/log/squid/usersMacs.log',  filemode='a',level=levelLOG)
    logging.raiseExceptions = False
    
    logging.info("Starting new helper...")
    CountSleep=0
    HashMem={}
    HashUser={}
    while True:
        line = sys.stdin.readline().strip()
        logging.debug("Receive '"+line+"'")
        if len(line)<2:
            logging.debug("Sleeping 1s "+str(CountSleep)+"/3")
            time.sleep( 1 )
            CountSleep=CountSleep+1
            if CountSleep>3:
                logging.info("Die() maxcount >3 -> raise SystemExit(0)")
                raise SystemExit(0)
            continue
        
        MainArray=line.split(" ")
        UserKey=''
        userid=''
        clt_conn_tag=''
        username=''
        Concurrency=0
        AsConcurrency=False
        Concurrency_text=''
        FirstVal=unicode(MainArray[0],'utf-8')
        
        try:
            
            if FirstVal.isnumeric():
                Concurrency=int(MainArray[0])
                IPADDR=str(MainArray[2])
                MAC=str(MainArray[3])
                IPADDR2=MainArray[4]
                AsConcurrency=True
                Concurrency_text=str(Concurrency)+" "
            else:
                logging.debug("Old way to split()")
                IPADDR=MainArray[0]
                MAC=MainArray[1]
                IPADDR2=MainArray[2]
            
            IPADDR=IPADDR.strip()
            IPADDR2=IPADDR2.strip()
            MAC=MAC.strip()
            
            
            CountSleep=0
           
            if username=='-': username=''
            if IPADDR2=='-': IPADDR2=''
            if MAC=="00:00:00:00:00:00": MAC=''
            if len(IPADDR2)>5: IPADDR=IPADDR2
            if len(MAC)>5: UserKey=MAC
            if len(MAC) ==0: UserKey=IPADDR
                        
                
            logging.debug("username:"+username+" ipaddr:"+IPADDR+" mac:"+MAC+" Choose:"+UserKey+" EnableSquidMicroHotSpot="+str(EnableSquidMicroHotSpot))
            
            if len(username)>0:
                License_users(username)
                HashUser[UserKey]=username
                LineToSend=Concurrency_text+"OK user="+username+"\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue
            
            License_users(UserKey)
            
            if HashUser.has_key(UserKey):
                LineToSend=Concurrency_text+"OK user="+username+"\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue
                
            
            
            
            if EnableSquidMicroHotSpot==1:
                userid=MicroHotSpotChecker(UserKey)
                if len(userid)>1:
                    LineToSend=Concurrency_text+"OK user="+userid+"\n"
                    sys.stdout.write(LineToSend)
                    sys.stdout.flush()
                    continue
                    
                
            
            if HashMem.has_key(UserKey):
                logging.debug(UserKey+" -> MEMORY ["+str(HashMem[UserKey])+"]")
                sys.stdout.write(HashMem[UserKey])
                sys.stdout.flush()
                continue
            else:
                logging.debug(UserKey+" -> not in memory")
            
                
                
            if EnableArticaHotSpot==1:
                FilePath="/home/artica/UsersMac/Hotspots/"+UserKey
                if os.path.exists(FilePath):
                    Conf=file_get_contents(FilePath)
                    array=Conf.split("|")
                    userid=array[0]
                    group=array[1]
                    logging.debug("HotSpot: "+UserKey+" userid=<"+userid+">")
                    if len(group)>2: clt_conn_tag=" clt_conn_tag="+group+" log="+group+",none"
                    
                    if len(userid)>1:
                        LineToSend=Concurrency_text+"OK user="+userid+clt_conn_tag+"\n"
                        HashMem[UserKey]=LineToSend
                        sys.stdout.write(LineToSend)
                        sys.stdout.flush()
                    continue 
                logging.debug("HotSpot: "+FilePath+" Not such file, continue")    
                
                
            FilePath="/home/artica/UsersMac/OpenVPN/"+IPADDR
            if os.path.exists(FilePath):
                userid=file_get_contents(FilePath)
                if len(userid)>1:
                    LineToSend=Concurrency_text+"OK user="+userid+clt_conn_tag+"\n"
                    sys.stdout.write(LineToSend)
                    sys.stdout.flush()
                    continue
                    
                
                
            
                
            FilePath="/home/artica/UsersMac/Caches/"+UserKey
            if not os.path.exists(FilePath):
                logging.debug(""+FilePath+" No such file -> Send OK")
                LineToSend=Concurrency_text+"OK\n"
                HashMem[UserKey]=LineToSend
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue
            
            Conf=file_get_contents(FilePath)
            array=Conf.split("|")
            userid=array[0]
            group=array[1]
            logging.debug(""+UserKey+" userid=<"+userid+">")
            if len(group)>2: clt_conn_tag=" clt_conn_tag="+group+" log="+group+",none"
            
            if len(userid)>1:
                LineToSend=Concurrency_text+"OK user="+userid+clt_conn_tag+"\n"
                HashMem[UserKey]=LineToSend
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue    
                
            logging.debug("OUTPUT NOTHING FOR "+UserKey)
            LineToSend=Concurrency_text+"OK\n"
            HashMem[UserKey]=LineToSend
            sys.stdout.write(Concurrency_text+"OK\n")
            sys.stdout.flush()
            
        except:
            logging.info(tb.format_exc())
            sys.stdout.write(Concurrency_text+"OK\n")
            sys.stdout.flush()
            continue

if __name__ == '__main__':
    sys.exit(main(arg=sys.argv[1:]))

