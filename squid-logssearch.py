#!/usr/bin/env python
# -*- coding: utf-8 -*-
import gzip
import csv as CsvClass
import sys
import traceback as tb
import os
import re
import logging
import datetime
import shutil
from glob import glob
from dateutil import parser
from phpserialize import serialize, unserialize
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *
from postgressql import *
from backupnasclass import *
from squidlogsfinderclass import *
from squidlogsparseclass import *


def build_progress(percent,logger,ruleid):
    POSTGRES=Postgres()
    POSTGRES.log=logger
    POSTGRES.QUERY_SQL("UPDATE logsfinder SET status="+str(percent)+" where id="+str(ruleid))
    array={}
    array["POURC"]=percent;
    array["TEXT"]="{searching} Rule N."+str(ruleid);
    print "[INFO]: ["+str(percent)+"%]: Progress....";
    file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.logsfinder.progress", serialize(array));
    
    




def main(ruleid):
    mountPoint="/mnt/BackupSquidLogsUseNas/artica-backup-syslog/proxy";
    filelogs="/home/logsfinder.debug"
    RemoveFile(filelogs)
    logging.basicConfig(format='%(asctime)s [%(levelname)s] %(message)s',filename=filelogs,  filemode='a',level=logging.INFO)
    logging.raiseExceptions = False
    POSTGRES=Postgres()
    NAS=BackupToNas()
    finder=SourceLogsFinder()
    BackupMaxDaysDir=GET_INFO_STR("BackupMaxDaysDir")
    if len(BackupMaxDaysDir)<4: BackupMaxDaysDir="/home/logrotate_backup"
    export_filename="/home/artica/tmp/export."+str(ruleid)+".log"
    mkdir("/home/artica/tmp",0755)
    print "BackupMaxDaysDir = ["+BackupMaxDaysDir+"]"
    POSTGRES.log=logging
   
    if not os.path.exists(BackupMaxDaysDir):
        logging.info(BackupMaxDaysDir+" No such directory")
        print BackupMaxDaysDir+" No such directory"
        build_progress(110,logging,ruleid)
        return False
        
    build_progress(1,logging,ruleid)
    print "[INFO]: PostGreSQL Use id:"+str(ruleid)
    sql="SELECT fromdate,todate,sitename,username,uduniq,tocsv FROM logsfinder WHERE id="+str(ruleid)
    rows=POSTGRES.QUERY_SQL(sql)
    
    if not POSTGRES.ok:
        print "[ERR.]: PostGreSQL error"
        print sql
        build_progress(110,logging,ruleid)
        return
    
    if len(rows)==0:
        build_progress(110,logging,ruleid)
        print "[ERR.]: PostGreSQL No row"
        return
    
    POSTGRES.QUERY_SQL("DROP TABLE templogsparser"+str(ruleid))
    sql="CREATE table IF NOT EXISTS templogsparser"+str(ruleid)+" (zdate bigint,lines VARCHAR(4096))"
    POSTGRES.QUERY_SQL(sql)
    if not POSTGRES.ok:
        print "[ERR.]: PostGreSQL error "+POSTGRES.mysql_error
        print sql
        build_progress(110,logging,ruleid)
        return
    
    POSTGRES.QUERY_SQL("CREATE INDEX IF NOT EXISTS idate ON templogsparser"+str(ruleid)+" (zdate);")   
    
    fromdate=rows[0][0]
    todate=rows[0][1]
    sitename=rows[0][2]
    username=rows[0][3]
    uduniq=rows[0][4]
    tocsv=rows[0][5]
    compress_filename="/usr/share/artica-postfix/ressources/logs/web/logsfinder/"+str(uduniq)+".gz"
    csvpath = '/home/artica/logsfinder/'+uduniq+".csv"
    mkdir("/usr/share/artica-postfix/ressources/logs/web/logsfinder",0755)
    mkdir("/home/artica/logsfinder",0755)
    
    date1=int(fromdate.strftime("%s"))
    date2=int(todate.strftime("%s"))
    print "Query "+str(date1)+"->"+str(date2) +" for site:"+sitename+" and "+username+" Export to CSV:"+str(tocsv)
    xStop = len(sitename)+len(username)
    if tocsv==0:
        if xStop<3:
            POSTGRES.QUERY_SQL("DROP TABLE templogsparser"+str(ruleid))
            print "[ERR.]: You must specify at least a sitename and/or a username"
            print "[ERR.]: This make no sense to query these dates without a real search pattern"
            build_progress(110,logging,ruleid)
            return
    
    if tocsv==1: POSTGRES.QUERY_SQL("DROP TABLE templogsparser"+str(ruleid))
        
        
        

    finder.GetFiles(date1,date2)


    AllFiles=len(finder.ArrayFiles)
    print "[INFO]: Found "+str(AllFiles)+" files to scan"
    if AllFiles==0:
        build_progress(100,logging,ruleid)
        POSTGRES.QUERY_SQL("DROP TABLE templogsparser"+str(ruleid))
        return
    
    AnalyzedFiles=0
    
        
    if NAS.BackupSquidLogsUseNas==1:
        if not NAS.ConnectToNAS():
            print "[ERR.]: Connect to NAS Failed for "+str(AllFiles)+" files to scan"
            POSTGRES.QUERY_SQL("DROP TABLE templogsparser"+str(ruleid))
            build_progress(110,logging,ruleid)
            return
    
            
            
    CountOfFile=0
    finalRow=0
    for filepath in finder.ArrayFiles:
        zBaseName=os.path.basename(filepath)
        CountOfFile=CountOfFile+1
        
        percent=int(100 * float(CountOfFile)/float(AllFiles))
        print "Scanning "+zBaseName +" "+str(CountOfFile)+"/"+ str(AllFiles)+" -> "+str(percent)+"%"
        if percent>1:
            if percent<90:
                build_progress(percent,logging,ruleid)

        zrows=AnalyzeFile(filepath,date1,date2,sitename,username,POSTGRES,"templogsparser"+str(ruleid),tocsv,uduniq)
        finalRow=finalRow+zrows
        print "[INFO]: "+zBaseName+" (" +str(zrows)+" rows) Final: "+str(finalRow)+" rows"
    
    
   

    if NAS.BackupSquidLogsUseNas==1: NAS.DisconnectFromNas()
    with open("/var/log/squid/access.log", 'rb') as f_in, gzip.open("/var/log/squid/access.log.gz", 'wb') as f_out:
        shutil.copyfileobj(f_in, f_out)
    
    zrows=AnalyzeFile("/var/log/squid/access.log.gz",date1,date2,sitename,username,POSTGRES,"templogsparser"+str(ruleid),tocsv,uduniq)
    RemoveFile("/var/log/squid/access.log.gz")
    
    finalRow=finalRow+zrows
    print "[INFO]: "+str(finalRow)+" rows"
    logging.info(str(finalRow)+" rows")
    build_progress(70,logging,ruleid)
    
    if tocsv==1:
        print "[INFO]: Compress "+csvpath+""
        with open(csvpath, 'rb') as f_in, gzip.open(csvpath+".gz", 'wb') as f_out: shutil.copyfileobj(f_in, f_out)
        RemoveFile(csvpath)
        build_progress(100,logging,ruleid)
        return
    
    
    
    print str(export_filename)+" Saving"
    rows=POSTGRES.QUERY_SQL("SELECT lines from templogsparser"+str(ruleid) +" ORDER BY zdate")
    if len(rows)==0:
        print "[ERR.]: Now row in templogsparser"+str(ruleid)
        print "[ERR.]: "+str(export_filename)+" no data"
        print "[INFO]: DROP TABLE templogsparser"+str(ruleid)
        POSTGRES.QUERY_SQL("DROP TABLE templogsparser"+str(ruleid))
        build_progress(110,logging,ruleid)
        NAS.DisconnectFromNas()
        return
    
        
        f = open(export_filename,'w')
        for line in rows:
            f.write(line[0]+'\n')
        f.close()
    
        if not os.path.exists(export_filename):
            logging.info(str(export_filename)+" I/O error No such file")
            print "[ERR.]: "+str(export_filename)+" I/O error No such file"
            POSTGRES.QUERY_SQL("DROP TABLE templogsparser"+str(ruleid))
            build_progress(110,logging,ruleid)
            NAS.DisconnectFromNas()
            return
        
    
    build_progress(91,logging,ruleid)
    print "[INFO]: Compressing From....:"+export_filename
    print "[INFO]: Compressing To......:"+compress_filename
    
    if os.path.exists(compress_filename): RemoveFile(compress_filename)
    
    if not os.path.exists(export_filename):
        logging.info(str(export_filename)+" I/O error No such file")
        print "[ERR.]: "+str(export_filename)+" I/O error No such file"
        POSTGRES.QUERY_SQL("DROP TABLE templogsparser"+str(ruleid))
        build_progress(110,logging,ruleid)
        NAS.DisconnectFromNas()
        return
        
    
    try:
        with open(export_filename, 'rb') as f_in, gzip.open(compress_filename, 'wb') as f_out:
            shutil.copyfileobj(f_in, f_out)
    except:
        print "[ERR.]: Compressing failed!"
        print(tb.format_exc())
        print "[ERR.]: DROP TABLE templogsparser"+str(ruleid)
        POSTGRES.QUERY_SQL("DROP TABLE templogsparser"+str(ruleid))
        build_progress(110,logging,ruleid)
        RemoveFile(compress_filename)
        RemoveFile(export_filename)
        NAS.DisconnectFromNas()
        return False
        
        
         
    os.chmod(compress_filename,0755)
    print "[INFO]: Updating status..."
    build_progress(95,logging,ruleid)
    POSTGRES.QUERY_SQL("UPDATE logsfinder SET status=100,rqs="+str(finalRow)+" WHERE id="+str(ruleid))
    RemoveFile(export_filename)
    print "[INFO]: Remove TABLE templogsparser"+str(ruleid)+"..."
    print "[INFO]: * * * * This should take time, please wait * * * *"
    POSTGRES.QUERY_SQL("DROP TABLE templogsparser"+str(ruleid))
   
    if not POSTGRES.ok:
        print "[ERR.]: PostGreSQL error "+POSTGRES.sql_error
        RemoveFile(compress_filename)
        build_progress(110,logging,ruleid)
        return
    print "[INFO]: Evrything done..."
    build_progress(100,logging,ruleid)

    
    
    

    
def AnalyzeFile(filepath,date1,date2,sitename,username,POSTGRES,tablename,tocsv,uduniq):
        sitename=sitename.strip()
        username=username.strip()
        if sitename=="*":sitename=""
        if username=="*":username=""
        username=username.replace(".","\.")
        username=username.replace("*",".*?")
        sitename=sitename.replace(".","\.")
        sitename=sitename.replace("*",".*?")
        zline=0
        zBaseName=os.path.basename(filepath)
        
        csvpath = '/home/artica/logsfinder/'+uduniq+".csv"
        mode = 'a' if os.path.exists(csvpath) else 'w'

        if tocsv==1:
            resultFile = open(csvpath,mode)
            try:
                
                resultWriter= CsvClass.writer(resultFile, dialect='excel')
                log=SquidLog(filepath)
                if mode=='w': resultWriter.writerow(['Time', 'elapsed', 'remhost' , 'status','bytes','method','url','user','Peer','Content/Type'])

                for l in log:
                    if l.ts==None:continue
                    xtime=int(l.ts)
                    if xtime<date1: continue
                    if xtime>date2: continue
                    
                    if(len(sitename)>0):
                        matches=re.search(sitename,l.url)
                        if not matches:continue
                    if(len(username)>0):
                        matches=re.search(username,l.remhost+" "+l.rfc931)
                        if not matches:continue                    
                    zline=zline+1
                    zdate=datetime.fromtimestamp(xtime).strftime('%Y-%m-%d %H:%M:%S')
                    resultWriter.writerow([zdate,l.elapsed,l.remhost,l.status,l.bytes,l.method,l.url,l.rfc931,l.peerstatus,l.type])
                
               
                resultFile.close()
                return zline
            except:
                print "[ERR.] Line 266!!"
                print tb.format_exc()
                resultFile.close()
                return zline
            return zline

        print "[INFO]: Open :"+zBaseName
        xsql=[]
        with gzip.open(filepath, 'rb') as fin:
            for line in fin:
                line=line.strip()
                matches=re.match("^([0-9]+)\.",line)
                if not matches:continue;
                xTime=int(matches.group(1))
                if xTime<date1:continue
                if xTime>date2:continue;
                
                if len(sitename)>0:
                    matches=re.search(sitename,line)
                    if not matches:continue
                    
                if len(username)>0:
                    matches=re.search(username,line)
                    if not matches:continue                    
                
                if len(line)>4095: continue
                line=line.replace("'","`")
                
                xsql.append("("+str(xTime)+",'"+line+"')")

                    
                if len(xsql)>1000:
                    print "[INFO]: "+zBaseName+" Importing "+str(zline)
                    POSTGRES.QUERY_SQL("INSERT INTO "+tablename+" (zdate,lines) VALUES "+",".join(xsql))
                    xsql=[]
                    
               
                if not POSTGRES.ok:
                    matches=re.search("does not exist",POSTGRES.sql_error)
                    if matches:
                        POSTGRES.QUERY_SQL("CREATE table IF NOT EXISTS "+tablename+" (zdate bigint,lines VARCHAR(4096))")
                        POSTGRES.QUERY_SQL("INSERT INTO "+tablename+" (zdate,lines) VALUES "+",".join(xsql))
                        
                    if not POSTGRES.ok:
                        print "[ERR.]: Line 319, PostGreSQL error "+POSTGRES.sql_error
                        continue
                
                zline=zline+1
                
                
        if len(xsql)>0:
            POSTGRES.QUERY_SQL("INSERT INTO "+tablename+" (zdate,lines) VALUES "+",".join(xsql))
            if not POSTGRES.ok:
                print "[ERR.]: Line 330, PostGreSQL error "+POSTGRES.sql_error
                
            xsql=[]
            
                
        return zline
                    
            





if __name__ == "__main__":
    main(sys.argv[1])