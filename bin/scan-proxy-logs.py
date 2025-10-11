#!/usr/bin/python -O
# -*- coding: utf-8 -*-
# SP 255
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import gzip
import os
import re
import time
import sqlite3
from unix import *
from datetime import datetime
import traceback as tb
import psutil



def first_time_access():
    if not os.path.isfile('/var/log/squid/access.log'): return 0
    with open('/var/log/squid/access.log') as fp:
        for line in fp:
            CurentTimeStamp=islineIsDate(line)
            if CurentTimeStamp is None:
                count_of_lines = count_of_lines + 1
                if count_of_lines > 10: return None
            return CurentTimeStamp


def timestart_timeend(fpath):

    matches=re.search('\.([0-9]+)-([0-9]+)-([0-9]+)_([0-9]+)-([0-9]+)-([0-9]+)--[0-9]+',fpath)
    if matches:
        date_time="%s-%s-%s %s:%s:%s" % (matches.group(1),matches.group(2),matches.group(3),matches.group(4),matches.group(5),matches.group(5))
        return date_time

    count_of_lines=0
    with gzip.open(fpath, 'rt') as f:
        for line in f:
            sf=islineIsDate(line)
            if sf is None:
                count_of_lines = count_of_lines + 1
                if count_of_lines>10:
                    return None
                continue
            timestamp = float(sf)
            dt_object = datetime.fromtimestamp(timestamp)
            date_time = dt_object.strftime("%Y-%m-%d %H:%M:%S")
            return date_time

def islineIsDate(theline):
    patterns=["\[[0-9]+\]:\s+([0-9]+)\.[0-9]+\s+[0-9]+",
              "^([0-9\.]+)\s+([\-0-9]+)\s+([0-9\.]+)",
              "^[A-Za-z]+\s+[0-9]+.*?\):\s+([0-9]+)\.[0-9]+\s+",
              "^[0-9\-]+T[0-9:]+.*?squid.*?\]:\s+([0-9]+)\.[0-9]+\s+"]

    for pattern in patterns:
        matches = re.search(pattern, theline)
        if matches: return matches.group(1)
    return None

def DateStringToTimeStamp(TheDate):
    xtime=datetime.strptime(TheDate, "%Y-%m-%d %H:%M:%S")
    return int(time.mktime(xtime.timetuple()))



def walk_dir(TargetDirectory):
    lost_of_files = list()
    for (dirpath, dirnames, filenames) in os.walk(TargetDirectory):
        lost_of_files += [os.path.join(dirpath, file) for file in filenames]
    return  lost_of_files

def DustBin(fullpath):
    patterns = ["snapshot\.[0-9]+","dns-cache.[0-9]+"]
    for pattern in patterns:
        matches = re.search(pattern, fullpath)
        if matches: return True
    return False

def scan_repo(TargetDirectory):
    list = walk_dir(TargetDirectory)
    list_filtered=[]
    for full_path in list:
        if os.path.isdir(full_path): continue
        if DustBin(full_path): continue;
        matches=re.search('\.gz$',full_path)
        if not matches: continue;
        list_filtered.append(full_path)

    return list_filtered

def timefile(filename):
    if not os.path.isfile(filename): return 0
    age=time.time() - os.path.getmtime(filename)
    return int(age) / 60  # 120 minutes



def ScanDirectory(BackupMaxDaysDir):
    TargetDirectory=BackupMaxDaysDir
    database        = "/home/artica/SQLITE_TEMP/access_backuped.db"

    print("Using target directory %s" % TargetDirectory)
    list_of_files=scan_repo(TargetDirectory)

    if os.path.isfile(database):
        minutes = timefile(database)
        if minutes < 1440: return True
        os.unlink(database)

    QUERY_SQL('create table access_logs (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,fullpath TEXT, firstdate TEXT,filesize INTEGER)',database)

    for fpath in list_of_files:
        start   = timestart_timeend(fpath)
        if start is None: continue
        size    = os.path.getsize(fpath)
        print("scanning %s [%s]" % (fpath,start))
        sql="INSERT INTO access_logs(fullpath,firstdate,filesize) VALUES ('%s','%s','%s')" % (fpath,start,size)
        QUERY_SQL(sql,database)


def SourceFiles(datestart):
    sfiles=[]



    db = "/home/artica/SQLITE_TEMP/access_backuped.db"
    sql="SELECT fullpath FROM access_logs WHERE DATE(firstdate) >= '%s' ORDER BY DATE(firstdate)" % datestart
    print(sql)
    rows = QUERY_SQL(sql,db)
    if rows is None: return None
    for item in rows:
        sfiles.append(item[0])

    if len(sfiles)==0: return None
    return sfiles


def QUERY_SQL(sql, db,fetchone=False):
    rows = None
    try:
        conn = sqlite3.connect(db)
        conn.text_factory = lambda b: b.decode(errors='ignore')
    except Error as e:
        print("[ERROR]: SQL %s" % e)
        return None

    cur = conn.cursor()
    try:
        cur.execute(sql)
        matches=re.search("(INSERT|insert|update|UPDATE)\s+",sql)
        if matches: conn.commit()
        if fetchone: rows = cur.fetchone()
        if not fetchone: rows = cur.fetchall()
    except:
        print("[ERROR]: SQL %s" % tb.format_exc())
        conn.close()
        return None

    conn.close()
    return rows

def progress(prc,ID):
    db = "/home/artica/SQLITE/proxy_search.db"
    QUERY_SQL("UPDATE proxy_search set percentage='%s' WHERE ID='%s'" % (prc,ID),db)

def ifregex(str):
    a = ['.*', '?', '{','}','(',')','[',']','.+','\\']
    a_match = [True for match in a if match in str]

    if True in a_match:
        return True
    else:
       return False

def strToRegex(str):
    str = str.replace('.','\.')
    str = str.replace('*', '.*?')
    return str

def preparepattern(username,ipsrc,ipdest,category,sitename,squidcode):
    pp=[]
    try:
        if len(squidcode) == 0: squidcode = 0
    except:
        print("Issue on squidcode %s" % squidcode)

    if len(ipsrc) > 0:
        if not ifregex(ipsrc):ipsrc=strToRegex(ipsrc)
        pp.append("[0-9]+\s+%s\s+" %ipsrc )


    if int(squidcode)>100:
        print("Add ----> SQUID CODE  \"%s\"" % squidcode)
        pattern="[A-Z_]+\/"+str(squidcode)+"\s+"
        pp.append(pattern)

    if len(sitename)>0:
        if not ifregex(sitename): sitename = ".*?"+strToRegex(sitename)+".*?"
        pp.append("\s+[A-Z_]+\s+%s\s+" % sitename)

    if len(username)>0:
        if not ifregex(username): username = strToRegex(username)
        pp.append("\s+%s\s+[A-Z_]+" % username)

    if len(ipdest)>0:
        if not ifregex(ipdest): ipdest = strToRegex(ipdest)
        pp.append("\s+[A-Z_]+\/"+ ipdest)

    if int(category) > 0:
        pp.append("cinfo:%s" % category)

    return '.*?'.join(pp)
#1633860094.075 109082 109.24.151.169 TCP_TUNNEL/200 10241 CONNECT lh4.googleusercontent.com:443 chloe.roussel HIER_DIRECT/142.251.36.33:443 - mac="00:00:00:00:00:00" category:%2017%0D%0Acategory-name:%20Google%0D%0Aclog:%20cinfo:17-Google;%0D%0A ua="com.apple.WebKit.Networking/8610.4.3.0.5 CFNetwork/1220.1 Darwin/20.3.0"
#1633860094.076 105534 109.24.151.169 TCP_TUNNEL/200 46817 CONNECT meta.wikimedia.org:443 chloe.roussel HIER_DIRECT/91.198.174.192:443 - mac="00:00:00:00:00:00" category:%2043%0D%0Acategory-name:%20Dictionaries%0D%0Aclog:%20cinfo:43-Dictionaries;%0D%0A ua="com.apple.WebKit.Networking/8610.4.3.0.5 CFNetwork/1220.1 Darwin/20.3.0"


def ScanSearch(ID,sfiles,date,timeto,username,ipsrc,ipdest,category,sitename,squidcode,lines):
    mainpath="/home/artica/squidsearchs"
    mkdir(mainpath,0o755)
    destfile="%s/%s.log" % (mainpath,ID)
    TimeString="%s %s:00" % (date,timeto)
    print("Prepare %s %s %s %s %s %s" % (username,ipsrc,ipdest,category,sitename,squidcode))
    TheRegex=preparepattern(username,ipsrc,ipdest,category,sitename,squidcode)
    DestinationTimeStamp=time.mktime(datetime.strptime(TimeString, "%Y-%m-%d %H:%M:%S").timetuple())
    db = "/home/artica/SQLITE/proxy_search.db"

    print("Search before [%s] with [%s]" % (DestinationTimeStamp,TheRegex))
    if os.path.isfile(destfile): os.unlink(destfile)
    try:
        fsource = open(destfile, 'w')
    except:
        progress(110, ID)
        print("[ERROR]: %s %s" % (destfile,tb.format_exc()))
        return False

    Max=len(sfiles)
    c=0
    added=0
    
    for logsource in sfiles:
        c=c+1
        prc=int((c/Max)*100)
        if prc>10: progress(prc,ID)
        print("OPEN [%s]" % logsource)
        matches=re.search("\.gz$",logsource)
        if matches:
            f = gzip.open(logsource, 'rt')
        else:
            f = open(logsource)

        for line in f:
            TimeOfLine=islineIsDate(line)
            if TimeOfLine is None: continue
            TimeOfLine=int(float(TimeOfLine))
            if TimeOfLine > DestinationTimeStamp: continue
            matches=re.search(TheRegex,line)
            if not matches: continue
            fsource.write(line)

            added=added+1
            if added>lines:
                progress(100, ID)
                fsource.close()
                f.close()
                file_info = os.stat(destfile)
                xsize = file_info.st_size
                QUERY_SQL("UPDATE proxy_search set executed='1', lines='%s',size=%s,logspath='%s' WHERE ID='%s'" % (added, xsize, destfile, ID), db)
                return True
        f.close()

    fsource.close()
    progress(100, ID)
    file_info = os.stat(destfile)
    xsize=file_info.st_size
    QUERY_SQL("UPDATE proxy_search set executed='1', lines='%s',size=%s,logspath='%s' WHERE ID='%s'" % (added,xsize,destfile,ID), db)
    return True


def get_pid():
    daemon_pid=0
    pidpath = "/var/run/squid-search.pid"
    if os.path.exists(pidpath):
        with open(pidpath, 'r') as fp:
            try:
                daemon_pid = int(fp.read())
            except:
                pass
        if daemon_pid >0:
            if psutil.pid_exists(daemon_pid): return daemon_pid
    return 0

def main(argv):
    pidpath = "/var/run/squid-search.pid"
    try:
        SpecificID=argv[0]
        if not SpecificID.isnumeric(): SpecificID=0
    except:
        SpecificID=0


    currPid=get_pid()
    if currPid>0:
        print("Already running with process id %s" % currPid)
        sys.exit(0)

    try:
        outputFile = open(pidpath, "w")
        pid = str(os.getpid())
        outputFile.write(pid)
        outputFile.close()
    except:
        print(tb.format_exc())
        sys.exit(0)




    BackupMaxDaysDir=GET_INFO_STR("BackupMaxDaysDir")
    BackupSquidLogsUseNas = GET_INFO_INT("BackupSquidLogsUseNas")
    if len(BackupMaxDaysDir)==0: BackupMaxDaysDir="/home/logrotate_backup"
    if BackupSquidLogsUseNas==1:
        # exec.squid.rotate.php --mount
        print("Not implemented yet, mount...")
        sys.exit(0)

    ScanDirectory(BackupMaxDaysDir)
    db="/home/artica/SQLITE/proxy_search.db"
    rows=QUERY_SQL("SELECT ID,datefrom,dateto,timefrom,timeto,username,ipsrc,ipdest,category,sitename,squidcode,maxlines FROM proxy_search WHERE enabled=1 AND executed=0",db)
    for item in rows:
        print(item)
        category    = 0
        ID      = item[0]
        if SpecificID>0:
            if SpecificID!=ID: continue
        datefrom = item[1]
        dateto = item[2]
        timefrom = item[3]
        timeto = item[4]
        username=item[5].strip()
        ipsrc = item[6].strip()
        ipdest=item[7].strip()
        categoryLine=item[8].strip()
        sitename=item[9].strip()
        squidcode=item[10]
        lines=int(item[11])
        sdate = "%s %s:00" % (datefrom, timefrom)
        sdate_search="%s 00:00:00" % datefrom
        if(len(categoryLine)>0):
            try:
                category=int(categoryLine)
            except:
                print("[ERROR]: category [%s] %s" % (categoryLine, tb.format_exc()))
                category=0

        print("Query[%s]: Search between %s - %s %s user=%s" % (ID,sdate,dateto,timeto,username))

        TimeStampToSearch=DateStringToTimeStamp(sdate)
        print("Find files starting with [%s] == %s" % (sdate,TimeStampToSearch))
        progress(10,ID)
        sfiles=SourceFiles(sdate_search)
        access_time=first_time_access()
        if access_time is not None:
            if access_time < TimeStampToSearch:
                sfiles.append("/var/log/squid/access.log")

        if sfiles is None:
            progress(110, ID)
            QUERY_SQL("UPDATE proxy_search set executed='1', lines='%s',size=%s,logspath='%s' WHERE ID='%s'" % (
                0, 0, '', ID), db)
            continue

        QUERY_SQL("UPDATE proxy_search set executed='0', lines='%s',size=%s,logspath='%s' WHERE ID='%s'" % (
        0, 0, '', ID), db)
        ScanSearch(ID,sfiles,dateto,timeto,username,ipsrc,ipdest,category,sitename,squidcode,lines)


    sys.exit(0)


























if __name__ == "__main__":
   main(sys.argv[1:])
