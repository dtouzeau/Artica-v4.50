#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import glob,os
import getopt
import logging
import tailer
import hashlib
import re
import tldextract
import urllib
from postgressql import *
from categories import *
from unix import *
import datetime
import time
import gzip
import traceback as tb
global DEBUG
global CATEGORY
global TEMPLINES
global TOTALLINES




def main(argv):
    DirectoryToScan=""
    FileNameToScan=""
    DirectoryToScangz=""
    FileNameGZToScan=""
    NOHELP=False
    global DEBUG
    global CATEGORY
    global TEMPLINES
    DEBUG=False
    CATEGORY=False
    TEMPLINES=[]
    try:                                
        opts, args = getopt.getopt(argv, "hpfgz:dcjmx", ["help", "file=","path=","gzfile=","gzpath=","test-category="])
    except:
        print tb.format_exc()
        usage()                         
        sys.exit(2)
        
    for opt, arg in opts:
        if opt in ("-h", "--help"):
            usage()                     
            sys.exit()                  
        elif opt == '-d':                  
            DEBUG = True
            print "Switch to debug mode"
        elif opt == '-c':                  
            CATEGORY = True
            print "Switch to category resolve mode"
        elif opt == '-j':                  
            print "Empty intermediate table"
            EmptyTable()
            NOHELP=True
        elif opt == '-m':                  
            print "Merging intermediate table"
            CompressTable()
            sys.exit()         
        elif opt in ("-p", "--path"):
            DirectoryToScan = arg
        elif opt in ("-f", "--file"):
            FileNameToScan = arg
        elif opt in ("-g", "--gzfile"):
            FileNameGZToScan = arg             
        elif opt in ("-z", "--gzpath"):
            DirectoryToScangz = arg
        elif opt in ("-x", "--test-category"):
            TestCategory(arg)
            sys.exit()

    if len(DirectoryToScan)>0:
        ScanDirectory(DirectoryToScan,".log")
        sys.exit()
    if len(FileNameToScan)>0:
        print "Scanning file '"+FileNameToScan+"'"
        ScanFileName(FileNameToScan)
        sys.exit()
    if len(DirectoryToScangz)>0:
        ScanDirectory(DirectoryToScangz,"*.gz")
        sys.exit()
    if len(FileNameGZToScan)>0:
        print "Scanning Filename '"+FileNameGZToScan+"'"
        ScanFileGZName(FileNameGZToScan)
        sys.exit()
    
    if not NOHELP:
        print "type --help to see the usage of this tool..."
        print ""
        
    sys.exit()


    
def usage():
    print ""
    print ""
    print "Command-line tool to import access.log(gz) into PostGreySQL database"
    print "This tool is designed to import legal logs to a temporary table called 'import_table'"
    print "After importing data in this table, you will be able to export data from this table"
    print "in the working table called 'access_log' to generates graphs and charts trough Artica"
    print ""
    print "Usage:"
    print "-----------------------------------------------------------------------------"
    print "-p\t--path=[path]\t Directory where all access.log files are stored"
    print "\t\t\tput in this directory *.log files in order to scan them"
    print ""
    
    print "-pz\t--gzpath=[path]\t Directory where all access-xxx.gz files are stored"
    print "\t\t\tput in this directory *.gz files in order to scan them"
    print ""    
    
    
    print "-f\t--file=[path]\t access.log to scan instead a global directory"
    print "\t\t\t--file=/var/log/squid/access.log"
    print ""
    
    print "-g\t--gzfile=[path]\t access.gz to scan instead a global directory"
    print "\t\t\t--gzfile=/var/log/squid/access.gz"
    print ""     
    print "-j\tEmpty intermediate table"
    print "-m\ttransfert imported data to the main access_log table"
    print "-z\tResolve categories in requests"
    print "-d\tPut the program in verbose mode"
    print ""
    print "Procedure:"
    print "-----------------------------------------------------------------------------"
    print "1) init the temporary table by using the -j"
    print "2) import all files using switchs (--gzpath,--gzfile,--path,--file) - use '-c' if you want to resolve categories"
    print "3) Merge the temporary table to the access_log table using the -m switch"
    print "4) Empty the temporary table to free space using the -j switch"
    print "5) Compress and index database using the command-line:"
    print "   /usr/local/ArticaStats/bin/vacuumdb -f -z -v -h /var/run/ArticaStats --dbname=proxydb --username=ArticaStats"
    print ""
    print "Notice"
    print "-----------------------------------------------------------------------------"
    print "Manually connect on the PostgreSQL database:"
    print "/usr/local/ArticaStats/bin/psql -h /var/run/ArticaStats -U ArticaStats proxydb"
    print ""
    print ""
    
def EmptyTable():
    logger = GetLogger()
    pgsql=Postgres()
    pgsql.log=logger
    print "delete table import_table"
    pgsql.QUERY_SQL("DROP TABLE import_table;")
    if not pgsql.ok: print pgsql.sql_error
    print "Build import_table (if not exists)"
    TableCreate='CREATE TABLE IF NOT EXISTS "import_table" (zdate timestamp,mac macaddr,ipaddr INET,proxyname VARCHAR(128) NOT NULL,category VARCHAR(64) NULL,sitename VARCHAR(512) NULL,FAMILYSITE VARCHAR(512) NULL,USERID VARCHAR(64) NULL,SIZE BIGINT,RQS BIGINT)'
    pgsql.QUERY_SQL(TableCreate)
    if not pgsql.ok: print pgsql.sql_error
    
def CompressTable():
    TimeGroup="date_trunc('hour', zdate) as zdate";




    sqlA="SELECT SUM(size) as size,SUM(rqs) as rqs,"+TimeGroup+",proxyname,userid,ipaddr,mac,category,sitename,familysite FROM import_table GROUP BY date_trunc('hour', zdate),proxyname,userid,ipaddr,mac,category,sitename,familysite"
    sql="INSERT INTO access_log (size,rqs,zdate,proxyname,userid,ipaddr,mac,category,sitename,familysite) "+sqlA;
    logger = GetLogger()
    pgsql=Postgres()
    pgsql.log=logger
    pgsql.QUERY_SQL("ALTER TABLE access_log ALTER COLUMN sitename TYPE varchar(512);")


    print "Merging import_table to access_log table..."
    print "Should take time....."
    if DEBUG: print sql
    pgsql.QUERY_SQL(sql)
    if not pgsql.ok:
        print pgsql.sql_error
        sys.exit()
    
    print "Done..."
    print "Did no forget to empty the temporary table and do a vacuumdb for performance"
    print "see --help for more information"
    
def formatNumber(integer):
    myval="{:,.2f}".format(integer).replace(",", " ")
    myval=myval.replace(".00","")
    return myval
    
def ScanDirectory(TargetPath,extention):
    if not os.path.exists(TargetPath):
        print TargetPath,"no such directory"
        return False
    
    widthExt=extention.replace("*","")
    print "Scanning directory '"+TargetPath+"'(",extention,widthExt,")"
    
    for file in os.listdir(TargetPath):
        TargetFilePath=os.path.join(TargetPath, file)
        if DEBUG: print "Analyze file:",TargetFilePath
        if file.endswith(widthExt):
            print("Found "+TargetFilePath)
            if file.endswith(".log"): ScanFileName(TargetFilePath)
            if file.endswith(".gz"): ScanFileGZName(TargetFilePath)


def TestCategory(sitenmame):
    logger = logging.getLogger("squidtail")
    logger.setLevel(logging.INFO)
    catz=Categories()
    catz.Debug=True
    catz.OuputScreen=True
    catz.log=logger
    category=catz.GET_CATEGORY(sitenmame)
    print "%s = %s " %(sitenmame,category)
            
def ScanFileName(TargetFilePath):
    
    if not os.path.exists(TargetFilePath):
        print TargetFilePath,"no such file"
        return False
    
    global TOTALLINES
    TOTALLINES=0
    logger = logging.getLogger("squidtail")
    logger.setLevel(logging.INFO)
    catz=object
    if CATEGORY:
        catz=Categories()
        catz.Debug=False
        catz.log=logger
        
    global TEMPLINES
    TEMPLINES=[]
    myCount=0
    with open(TargetFilePath,"r") as f:
        for txt in f :
            txt=txt.rstrip('\n')
            if DEBUG: print('got line', txt)
            myCount=myCount+1
            ParseLine(txt,catz)
            if(len(TEMPLINES)>5000):
                TOTALLINES=TOTALLINES+len(TEMPLINES)
                percent=float(myCount)/SUM
                percent=percent*100
                percent=round(percent,2)
                print "Add",str(formatNumber(TOTALLINES)),"rows","/",formatNumber(SUM),"lines",percent,"%"
                sql='INSERT INTO "import_table" (zdate,mac,ipaddr,proxyname,category,sitename,familysite,userid,size,rqs) VALUES '+",".join(TEMPLINES)
                pgsql.QUERY_SQL(sql)
                if not pgsql.ok:
                    print pgsql.sql_error
                    sys.exit(0)
                TEMPLINES=[]
                
                
    if(len(TEMPLINES)>0):
        TOTALLINES=TOTALLINES+len(TEMPLINES)
        sql='INSERT INTO "import_table" (zdate,mac,ipaddr,proxyname,category,sitename,familysite,userid,size,rqs) VALUES '+",".join(TEMPLINES)
        pgsql.QUERY_SQL(sql)
        if not pgsql.ok:
            print pgsql.sql_error
            sys.exit(0)
            
    skipped=SUM-TOTALLINES
    print "Added",formatNumber(TOTALLINES),"rows in database against ",formatNumber(SUM),"lines",formatNumber(skipped),"skipped rows"
            
def rawcount(filename):
    f = open(filename, 'rb')
    lines = 0
    buf_size = 1024 * 1024
    read_f = f.raw.read

    buf = read_f(buf_size)
    while buf:
        lines += buf.count(b'\n')
        buf = read_f(buf_size)

    return lines
            
def CountGz(TargetFilePath):
    lines=0
    with gzip.open(TargetFilePath,'r') as fin:
        for txt in fin: lines=lines+1
        
    return lines

def ScanFileGZName(TargetFilePath):
    
    if not os.path.exists(TargetFilePath):
        print TargetFilePath,"no such file"
        return False

    matches = re.search("\/access-tail\.", TargetFilePath)
    if matches:
        print "Wrong file name %s" % TargetFilePath
        return False
        
    global TOTALLINES
    TOTALLINES=0
    SUM=0
    catz=object
    global TEMPLINES
    TEMPLINES=[]
    logger = GetLogger()
    pgsql=Postgres()
    pgsql.log=logger
    TableCreate='CREATE TABLE IF NOT EXISTS "import_table" (zdate timestamp,mac macaddr,ipaddr INET,proxyname VARCHAR(128) NOT NULL,category VARCHAR(64) NULL,sitename VARCHAR(512) NULL,FAMILYSITE VARCHAR(512) NULL,USERID VARCHAR(64) NULL,SIZE BIGINT,RQS BIGINT)'
    print "Building temporary table..."    
    pgsql.QUERY_SQL(TableCreate)
    if CATEGORY:
        catz=Categories()
        catz.Debug=False
        catz.log=logger
    
    print "Get the number of lines..."    
    SUM=CountGz(TargetFilePath)
    myCount=0
    print "Open the source file"    
    with gzip.open(TargetFilePath,'r') as fin:
        for txt in fin:
            txt=txt.rstrip('\n')
            myCount=myCount+1
            ParseLine(txt,catz)
            if(len(TEMPLINES)>5000):
                TOTALLINES=TOTALLINES+len(TEMPLINES)
                percent=float(myCount)/SUM
                percent=percent*100
                percent=round(percent,2)
                print "Add",str(formatNumber(TOTALLINES)),"rows","/",formatNumber(SUM),"lines",percent,"%"
                sql='INSERT INTO "import_table" (zdate,mac,ipaddr,proxyname,category,sitename,familysite,userid,size,rqs) VALUES '+",".join(TEMPLINES)
                pgsql.QUERY_SQL(sql)
                if not pgsql.ok:
                    print pgsql.sql_error
                    print sql
                    sys.exit(0)
                TEMPLINES=[]
                
                
    if(len(TEMPLINES)>0):
        TOTALLINES=TOTALLINES+len(TEMPLINES)
        sql='INSERT INTO "import_table" (zdate,mac,ipaddr,proxyname,category,sitename,familysite,userid,size,rqs) VALUES '+",".join(TEMPLINES)
        pgsql.QUERY_SQL(sql)
        if not pgsql.ok:
            print pgsql.sql_error
            print sql
            sys.exit(0)
            
    skipped=SUM-TOTALLINES
    print "Added",formatNumber(TOTALLINES),"rows in database against ",formatNumber(SUM),"lines",formatNumber(skipped),"skipped rows"
                    
                
        
def GetLogger():
    LOG_LEVEL=logging.INFO
    logging.basicConfig(format='%(asctime)s [%(levelname)s] [%(process)d] %(message)s',filename='/var/log/import-access.log',  filemode='a',level=LOG_LEVEL)
    logging.raiseExceptions = False
    return logging 
    
def ParseLine(line,catz):
    zcategory=""
    l = line.split()
    if len(l)<11:
        print "bad format array length:",len(l)," expected 11"
        print "Line was: '"+line+"'"
        return False
    try:
        xtime=float(l[0])
    except:
        print "bad format expected 'time' as integer..."
        return False
        
        
    s = "%s" % time.strftime("%Y-%m-%d %H:%M:%S", time.localtime(xtime))
    mac='00:00:00:00:00:00'
    try:
        elapsed=int(l[1])
    except:
        print "bad format  expected 'elapsed' as integer..."
        return False        
        
    ipaddr=l[2]
    status=l[3]
    try:
        size=int(l[4])
    except:
        print "bad format expected 'size' as integer..."
        return False
    
    zStatus=status.split("/")
    try:
        status_code=int(zStatus[1])
    except:
        print "bad format expected 'Status' as integer..."
        return False
    
    proto=l[5]
    uid=l[7]
    if status_code == 302: return
    if status_code == 301: return
    if status_code == 407: return
    if status_code == 0: return
    if size == 0: return
    
    ext = tldextract.extract(l[6])
    hostname='.'.join(ext[:2])+"."+ext.suffix
    familysite=ext.domain+"."+ext.suffix
    matches=re.match("^([0-9\.]+):[0-9]+",l[6])
    if matches:
        hostname=matches.group(1)
        familysite=matches.group(1)
    if hostname=="127.0.0.1":return
    proxyname=""
    if uid=="-":uid=""
    matches=re.match("^www\.(.+)",hostname)
    if matches: hostname=matches.group(1)

    if len(hostname)>256:
        hostname = hostname[0:250]+'...'
    
    if CATEGORY:
        zcategory=catz.GET_CATEGORY(hostname)
        if DEBUG: print "resolv category for ",hostname,"=",zcategory
    
    sql="('"+s+"','"+mac+"','"+ipaddr+"','"+proxyname+"','"+zcategory+"'," +"'"+hostname+"','"+familysite+"','"+uid+"','"+str(size)+"','1')"
    
    TEMPLINES.append(sql)
    

        
    
    
    
    
    
if __name__ == "__main__":
    main(sys.argv[1:])
    