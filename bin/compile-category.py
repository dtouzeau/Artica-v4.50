#!/usr/bin/env python
import sys
import os
import traceback as tb
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
from unix import *
from postgressql import *
from categorizeclass import *
from downloadclass import *
import cPickle as pickle
import hashlib
import anydbm
import pycurl
from ftplib import FTP
import shutil
from phpserialize import serialize, unserialize
import syslog
import re

def load_categories(official=0):
    sql= "SELECT category_id,categoryname,categorytable FROM personal_categories WHERE enabled=1 AND official_category=0 and free_category=0 order by categoryname"
    if official ==1 : sql = "SELECT category_id,categoryname,categorytable FROM personal_categories WHERE enabled=1 AND official_category=1 and free_category=0 order by categoryname"
    POSTGRES=Postgres()
    rows=POSTGRES.QUERY_SQL(sql)
    if not POSTGRES.ok:
        xsyslog(POSTGRES.sql_error)
        return None

    array={}

    for line in rows:
        category_id=line[0]
        categoryname=line[1]
        categorytable=line[2]
        if len(categorytable)<2:continue
        array[category_id]={}
        array[category_id]["TABLE"]=categorytable
        array[category_id]["NAME"] = categoryname
    return array

def main(argv):
    official=0
    offtemp=""
    try:
        action=argv[0]
    except:
        action="--compile"


    try:
        offtemp=argv[1]
    except:
        offtemp=""

    database_path="/home/artica/SQLITE/theshield.categories.db"
    dirpath = "/home/artica/categories_works"
    tables = None

    if not os.path.exists(dirpath): mkdir(dirpath, 0o755)
    mkchown(dirpath,"ArticaStats","ArticaStats")

    local_catz_names={}
    maindb={}

    if action == '--check':
        catz = categorize()
        category = argv[1]
        xsyslog("F:", category, "=", catz.local_categories[int(category)])

    if action=='--dump':
        catz=categorize()
        for index in catz.local_categories:
            xsyslog("F:",index,"=",catz.local_categories[index])


    if action=="--upload":
        ufdbcat_upload()
        return True

    if action =="--officials":
        try:
            compile_officials()
            return True
        except:
            xsyslog(tb.format_exc())
            squid_admin_mysql(1, "Error during categories compilation (74)", tb.format_exc(), "main","compile-category", 48)
            return False

    if action =="--update":
        print("Running databases updates...")
        ufdbcat_download()
        return True

    if action=="--to-redis":
        ufdbcat_to_redis()
        return True

    if action=="--compile":
        try:
            tables=load_categories(official)
        except:
            xsyslog(tb.format_exc())
            squid_admin_mysql(1, "Error during categories compilation (48)",tb.format_exc(), "main", "compile-category", 48)
            return False

        if len(tables)==0:
            xsyslog("No category to compile...")
            if os.path.exists("/etc/squid3/compiled-categories.db"):
                os.remove("/etc/squid3/compiled-categories.db")

            if os.path.exists("/etc/squid3/compiled-categorys.db"):
                os.remove("/etc/squid3/compiled-categorys.db")
            return False

        compiled_tables=0
        compiled_sites=0
        POSTGRES = Postgres()
        compile_root = "/home/artica/theshields_tmp"
        if not os.path.isdir(compile_root): os.makedirs(compile_root)
        if not os.path.isdir("/home/artica/categories_works"): os.makedirs("/home/artica/categories_works")
        os.chmod("/home/artica/categories_works",0o777)

        for category_id in tables:
            table=tables[category_id]["TABLE"]
            categoryname=tables[category_id]["NAME"]
            TempPath = "/home/artica/categories_works/%s.txt" % category_id
            xsyslog("Compiling [%s] %s for %s" % (category_id,table,categoryname))
            sql = "COPY %s TO '%s'" % (table,TempPath)
            maindb[category_id]=categoryname
            compiled_tables=compiled_tables+1
            POSTGRES.QUERY_SQL(sql)
            if not POSTGRES.ok:
                matches = re.search("relation.*?does not exist", POSTGRES.sql_error)
                if matches:
                    continue

                squid_admin_mysql(0,"Fatal PostGreSQL Error while compiling categories",
                    POSTGRES.sql_error,"main","compile-category",59)
                xsyslog(POSTGRES.sql_error)
                continue

            if not os.path.exists(TempPath): continue
            with open(TempPath, "r") as f:

                for line in f:
                    line=line.rstrip()
                    if len(line)<3: continue
                    hostname = line.strip()
                    hostname = hostname.lower()
                    if hostname.startswith('www.'): hostname = hostname.replace("www.", '', 1)
                    maindb[hostname]={}
                    maindb[hostname]["category_name"]=categoryname
                    maindb[hostname]["category_id"] = category_id
                    compiled_sites=compiled_sites+1
        try:
            xsyslog("%s categories compiled" % compiled_tables)
            xsyslog("%s websites compiled" % compiled_sites)
            file_put_contents("/etc/squid3/compiled-categories.db", serialize(maindb))
            xsyslog("/etc/squid3/compiled-categories.db done...")
            file_put_contents("/etc/squid3/compiled-categorys.db", serialize(maindb))
            xsyslog("/etc/squid3/compiled-categorys.db done...")
        except:
            xsyslog("Fatal I/O Error while saving categories")
            xsyslog(tb.format_exc())
            squid_admin_mysql(0, "Fatal I/O Error while saving categories", tb.format_exc(), "main", "compile-category", 84)
            sys.exit(0)

        category_title="category"
        item_title="item"
        if compiled_tables>1: category_title="categories"
        if compiled_sites >1: item_title="items"
        if compiled_tables > 1:
            squid_admin_mysql(2,"Success compiled %s %s with a total of %s %s" % (compiled_tables,category_title,compiled_sites,item_title),"","main","compile-category", 94)
            os.system("/usr/share/artica-postfix/bin/srnquery RESET")
            ufdbcat_to_redis()


def md5sum(filename, blocksize=65536):
    hash = hashlib.md5()
    with open(filename, "rb") as f:
        for block in iter(lambda: f.read(blocksize), b""):
            hash.update(block)
    return hash.hexdigest()

def compile_officials():
    tables = load_categories(1)
    if len(tables) == 0:
        xsyslog("No category to compile...")
        return False

    compiled_tables = 0
    compiled_sites = 0
    POSTGRES = Postgres()
    compile_root="/home/artica/theshields_tmp"
    FORCE_UPLOAD=[]
    if not os.path.isdir(compile_root): os.makedirs(compile_root)
    FORCE_UPLOAD.append("%s/index.dbm" % compile_root)
    dbindex = anydbm.open("%s/index.dbm" % compile_root, "c")
    DBFILES=[]
    for category_id in tables:
        table = tables[category_id]["TABLE"]
        categoryname = tables[category_id]["NAME"]
        md5sum_start=""
        md5sum_next=""
        md5sum_db=""
        TempPath = "/home/artica/categories_works/%s.txt" % category_id
        databasepath = "%s/%s.dbm" % (compile_root,category_id)
        if os.path.exists(TempPath): md5sum_start = md5sum(TempPath)

        sql = "COPY %s TO '%s'" % (table,TempPath)
        POSTGRES.QUERY_SQL(sql)

        if not POSTGRES.ok:
            matches = re.search("relation.*?does not exist", POSTGRES.sql_error)
            if matches:
                continue


            squid_admin_mysql(0,"Fatal PostGreSQL Error while compiling categories",POSTGRES.sql_error,"main","compile-category",59)
            xsyslog(POSTGRES.sql_error)
            if os.path.exists(databasepath): os.remove(databasepath)
            return False

        md5sum_next=md5sum(TempPath)
        if not os.path.exists(databasepath): md5sum_next=""
        if md5sum_next == md5sum_start:
            md5sum_db = md5sum(databasepath)
            catsites=CountLinesOfFile(TempPath)
            dbindex[str(category_id)] = "%s|%s" % (md5sum_db, catsites)
            continue
        xsyslog("Compiling [%s] %s for %s" % (category_id, table, categoryname))
        compiled_tables = compiled_tables + 1
        xsyslog("Creating [%s] for %s" % (databasepath, categoryname))
        if os.path.exists(databasepath): os.remove(databasepath)
        db = anydbm.open(databasepath, "c")



        catsites=0
        with open(TempPath, "r") as f:
            for line in f:
                line = line.rstrip()
                if len(line) < 3: continue
                hostname = line.lower()
                hostname = hostname.strip()
                if hostname.startswith('www.'): hostname = hostname.replace("www.", '', 1)
                if hostname.startswith('*.'): hostname = hostname.replace("*.", '', 1)
                skey=str(hashlib.md5(hostname).hexdigest())
                try:
                    db[skey]=str(category_id)
                    catsites=catsites+1
                except:
                    squid_admin_mysql(0, "Fatal DBM Error while compiling category %s" % category_id, tb.format_exc(),"main", "compile-category", 228)
                    xsyslog("Error db[%s (%s)]=%s" % (skey,hostname,category_id))
                    xsyslog(tb.format_exc())
                    db.close()
                    return False

                compiled_sites = compiled_sites + 1
        db.close()
        FORCE_UPLOAD.append(databasepath)
        md5sum_db=md5sum(databasepath)
        squid_admin_mysql(2, "Success Compiled category %s with %s sites" % (category_id,catsites),"","main", "compile-category", 236)
        xsyslog("Database %s %s compiled sites" % (databasepath,catsites))
        dbindex[str(category_id)]="%s|%s" % (md5sum_db,catsites)

    dbindex.close()
    xsyslog("%s Compiled sites" % compiled_sites)
    if compiled_sites > 0 : ufdbcat_upload(FORCE_UPLOAD)
    return True


def ufdbcat_download():
    TheShieldsUseLocalCats = GET_INFO_INT("TheShieldsUseLocalCats")
    if TheShieldsUseLocalCats == 0:
        print("TheShieldsUseLocalCats is disabled, Aborting")
        return False

    compile_root = "/home/artica/theshieldsdb"
    compile_temp = "/home/artica/theshields_tmp"
    try:
        if not os.path.isdir(compile_root): os.makedirs(compile_root)
        if not os.path.isdir(compile_temp): os.makedirs(compile_temp)
    except:
        pass

    curl = ccurl()
    dbindex_path = "%s/index.dbm" % compile_temp
    xsyslog("Get dbm index file (%s)" % dbindex_path)
    if not curl.DownloadFile("http://articatech.net/ufdbv4/index.dbm",dbindex_path):
        squid_admin_mysql(1, "Failed to download The Shields Categories index file err.%s %s" % (curl.RESPONSE_CODE,curl.error),
                          "", "main","compile-category", 249)
        xsyslog("[ERROR]: Failed to get dbm index file err.%s %s" % (curl.RESPONSE_CODE,curl.error))
        return False

    DBS={}
    THE_SHIELDS_DATABASES=GET_INFO_STR("THE_SHIELDS_DATABASES")
    if len(THE_SHIELDS_DATABASES)>2:DBS=unserialize(THE_SHIELDS_DATABASES)

    UPDATED_DB=0
    UPDATED_SIZE=0
    ERRORS=0
    SLOG=[]


    if not os.path.exists(dbindex_path):
        xsyslog("ERROR [%s] no such file HTTP CODE:%s..." % (dbindex_path,curl.RESPONSE_CODE))
        return False


    try:
        dbindex = anydbm.open(dbindex_path, "r")
    except:
        xsyslog("[ERROR]: [%s] Corrupted..." % dbindex_path)
        return False


    for category_id in dbindex:
        value=dbindex[category_id]
        if value.find('|')<1: continue
        tb=value.split('|')
        remote_md5 = tb[0]
        SitesNumber = tb[1]
        tmpfilename="%s/%s.dbm" % (compile_temp,category_id)
        finalfilename = "%s/%s.dbm" % (compile_root,category_id)
        Downloadable=False
        #xsyslog("category(%s) = %s [tmp=%s] md5:%s size:%s" % (category_id,value,tmpfilename,remote_md5,SitesNumber))
        if not os.path.exists(finalfilename): Downloadable = True
        if not Downloadable:
            local_md5=md5sum(finalfilename)
            if local_md5 != remote_md5: Downloadable=True

        if not Downloadable: continue

        url="http://articatech.net/ufdbv4/%s.dbm" % category_id
        xsyslog("Downloading [%s]" % url)
        if not curl.DownloadFile(url, tmpfilename):
            ERRORS=ERRORS+1
            SLOG.append("[ERROR]: Failed to download %s.dbm category database err.%s %s" % ( category_id,curl.RESPONSE_CODE,curl.error))
            xsyslog("[ERROR]: Failed to download %s.dbm category database err.%s %s" % ( category_id,curl.RESPONSE_CODE,curl.error))
            continue

        if not os.path.exists(tmpfilename):
            UPDATED_SIZE = UPDATED_SIZE + curl.downloaded_size
            xsyslog("[ERROR]: Downloading Failed HTTP Code: %s %s not existent %s downloaded bytes" % (curl.RESPONSE_CODE,tmpfilename,curl.downloaded_size))
            ERRORS = ERRORS + 1
            continue




        UPDATED_SIZE = UPDATED_SIZE + curl.downloaded_size

        temp_md5=md5sum(tmpfilename)
        if temp_md5 != remote_md5:
            ERRORS = ERRORS + 1
            if os.path.exists(tmpfilename): os.unlink(tmpfilename)
            SLOG.append("cat:%s Failed to download %s.dbm CORRUPTED %s!=%s" % (category_id, temp_md5,remote_md5))
            xsyslog("Failed to download %s.dbm CORRUPTED %s!=%s" % (category_id,temp_md5,remote_md5))
            continue

        if not xcopyf(tmpfilename,finalfilename):
            if os.path.exists(tmpfilename): os.unlink(tmpfilename)
            ERRORS = ERRORS + 1
            SLOG.append("cat:%s Failed to install %s.dbm WRITE FAILED to %s" % (category_id, category_id, finalfilename))
            xsyslog("Failed to install %s.dbm WRITE FAILED to %s" % (category_id, finalfilename))
            continue

        if os.path.exists(tmpfilename): os.unlink(tmpfilename)
        UPDATED_DB=UPDATED_DB+1
        timestamp=current_time_stamp()
        if not category_id in DBS : DBS[category_id]={}
        DBS[category_id]["SITES"]=SitesNumber
        DBS[category_id]["TIMESTAMP"] = timestamp





    if ERRORS > 0 :
        squid_admin_mysql(1, "%s The Shields databases failed to install" % ERRORS, "\n".join(SLOG), "main","compile-category", 304)

    if UPDATED_DB>0:
        dbm_cache = "/var/log/squid/categories.dbm"
        if os.path.exists(dbm_cache): os.unlink(dbm_cache)
        UPDATED_SIZE=round(UPDATED_SIZE/1024)
        UPDATED_DB_TEXT=serialize(DBS)
        SET_INFO("THE_SHIELDS_DATABASES",UPDATED_DB_TEXT)
        log="The Shields databases Success %s databases installed %s MB downloaded" % (UPDATED_DB,UPDATED_SIZE)
        xsyslog(log)
        squid_admin_mysql(2, log,"", "main", "compile-category",323)






def xcopyf(src,dest):
    try:
        if os.path.exists(dest): os.unlink(dest)
        shutil.copy(src, dest)
    except IOError as e:
        xsyslog("Unable to copy file. %s" % e)
        return False
    except:
        xsyslog("Unexpected error:", sys.exc_info())
        return False
    return True

def ufdbcat_upload(FORCE_UPLOAD=[]):
    uploaded=0
    compile_root = "/home/artica/theshields_tmp"
    UfdbCatsUpload = GET_INFO_INT("UfdbCatsUpload")
    if UfdbCatsUpload==0:
        xsyslog("Uploading to an FTP repository disabled")
        return False

    UfdbCatsUploadFTPserv   =   GET_INFO_STR("UfdbCatsUploadFTPserv")
    UfdbCatsUploadFTPusr    =   GET_INFO_STR("UfdbCatsUploadFTPusr")
    UfdbCatsUploadFTPpass   =	GET_INFO_STR("UfdbCatsUploadFTPpass")
    UfdbCatsUploadFTPDir    =	GET_INFO_STR("UfdbCatsUploadFTPDir")


    try:
        xsyslog("Connecting to the %s server...." % UfdbCatsUploadFTPserv)
        ftp = FTP(UfdbCatsUploadFTPserv,UfdbCatsUploadFTPusr,UfdbCatsUploadFTPpass)
    except:
        xsyslog(tb.format_exc())
        return False

    if len(UfdbCatsUploadFTPDir)>2:
        xsyslog("Going to %s ...." % UfdbCatsUploadFTPDir)
        ftp.cwd(UfdbCatsUploadFTPDir)
        ssdir= ftp.pwd()
        xsyslog("Current is %s ...." % ssdir)

    files = os.listdir(compile_root)
    ftp.encoding = "utf-8"
    for sfile in files:
        uploadit=False
        fullsrc="%s/%s" % (compile_root,sfile)
        local_size=os.path.getsize(fullsrc)
        try:
            remote_size = ftp.size(sfile);
        except:
            remote_size = 0

        if remote_size == 0: uploadit=True
        if remote_size != local_size: uploadit = True
        if fullsrc in FORCE_UPLOAD: uploadit=True
        if uploadit:
            uploaded=uploaded+1
            xsyslog("FTP %s = %s / %s" % (sfile, remote_size, local_size))
            with open(fullsrc, "rb") as file:
                xsyslog("Uploading %s" % (fullsrc))
                ftp.storbinary("STOR %s" % sfile, file)

    xsyslog("Disconnecting...")
    ftp.quit()
    if uploaded>0:
        squid_admin_mysql(2, "%s databases uploaded to repository" % uploaded, "", "main","compile-category", 430)
        ufdbcat_to_redis()


def xsyslog(text):
    print(text)
    syslog.openlog("ksrn", syslog.LOG_PID)
    syslog.syslog(syslog.LOG_INFO,"[COMPILE-CATEGORY]: %s" % text)


def writepid():
    pidpath="/var/run/compile-category.pid"
    outputFile = open(pidpath, "w")
    pid = str(os.getpid())
    outputFile.write(pid)
    outputFile.close()

def CountLinesOfFile(path):
    if not os.path.exists(path): return 0
    file = open(path, "r")
    line_count = 0
    for line in file:
        if line != "\n": line_count += 1
    file.close()
    return line_count

def redis_proto(line):
    result = "*%s\r\n$%s\r\n%s\r\n" % (str(len(line)), str(len(line[0])), line[0])
    for arg in line[1:]:
        result += "$%s\r\n%s\r\n" % (str(len(arg)), arg)
    return result

def ufdbcat_to_redis_progress(prc,text):
    arr={}
    maindir = "/usr/share/artica-postfix/ressources/logs/web";
    progressfile = "%s/ufdbcattoredis.progress" % maindir;
    arr["POURC"] =round(prc)
    arr["TEXT"] =text
    print("%s %s" % (prc,text))
    f = open(progressfile, 'wb')
    f.write(str(serialize(arr)))
    f.close()



def ufdbcat_to_redis():
    EnableCategoriesCache = GET_INFO_INT("EnableCategoriesCache")
    if EnableCategoriesCache == 0 : return True
    compile_root = "/home/artica/theshieldsdb"
    compile_temp = "/home/artica/theshieldsdb-tmpcat"
    CategoriesCacheMaxDBSize = GET_INFO_INT("CategoriesCacheMaxDBSize")
    if CategoriesCacheMaxDBSize == 0: CategoriesCacheMaxDBSize=400
    if not os.path.exists(compile_temp):
        print("%s not such directory" % compile_temp)
        return False

    ufdbcat_to_redis_progress(10, "{analyze}")
    files = os.listdir(compile_temp)
    for sfile in files:
        uploadit=False
        fullsrc="%s/%s" % (compile_temp,sfile)
        print("Removing %s" % fullsrc)



    files = os.listdir(compile_root)
    ct=len(files)
    c=0

    for sfile in files:
        uploadit=False
        c=c+1
        prc=c+10
        fullsrc="%s/%s" % (compile_root,sfile)
        matches=re.search('([0-9]+)\.dbm',sfile)
        category_id=matches.group(1)
        CurrentSize = os.path.getsize(fullsrc)
        CurrentSizeKo=CurrentSize/1024
        CurrentSizeMB=round(CurrentSizeKo/1024)
        if CurrentSizeMB > CategoriesCacheMaxDBSize: continue
        if prc > 90: prc=95
        if prc > 10: ufdbcat_to_redis_progress(prc, "{analyze} %s (%sMB)" % (sfile,CurrentSizeMB))

        print("Parsing %s %s %s/%s" % (fullsrc,CurrentSizeMB,c,ct))
        f = open("%s/%s.txt" % (compile_temp,category_id), 'w')
        dbindex = anydbm.open(fullsrc , "r")
        for k, v in dbindex.iteritems():
            set="SET %s %s" % (k,v)
            sline=redis_proto(set.split(' '))
            f.write(str(sline))
        dbindex.close()
        f.close()

    ufdbcat_to_redis_progress(100, "{analyze} {success}")
    phpbin="/usr/bin/php"
    nohup="/usr/bin/nohup"
    cmd="%s %s /usr/share/artica-postfix/exec.categories-cache.php --parse >/dev/null 2>&1 &" % (nohup,phpbin)
    os.system(cmd)





if __name__ == "__main__":
    try:
        writepid()
        zargv=[]
        if len(sys.argv)==0:
            zargv=['--compile']
        else:
            try:
                zargv=sys.argv[1:]
            except:
                zargv = ['--compile']

        main(zargv)
    except:
        xsyslog(tb.format_exc())
        squid_admin_mysql(0, "Fatal system Exception while compiling categories",tb.format_exc(), "main", "compile-category", 99)




