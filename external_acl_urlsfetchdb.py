#!/usr/bin/env python
import sys,getopt,ConfigParser
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *
import os,shutil
import traceback as tb
import logging
import re
import pycurl
import anydbm
import hashlib
import logging
from urlparse import urlparse


def GetProxy():
    NoCheckSquid = GET_INFO_INT("NoCheckSquid")
    SQUIDEnable  = GET_INFO_INT("SQUIDEnable")
    SquidMgrListenPort = GET_INFO_INT("SquidMgrListenPort")
    if SQUIDEnable==0: NoCheckSquid=1;

    if NoCheckSquid==0:
        return "127.0.0.1",SquidMgrListenPort,"",""

    if NoCheckSquid==1:
        config = ConfigParser.ConfigParser()
        config.readfp(open('/etc/artica-postfix/settings/Daemons/ArticaProxySettings'))
        ArticaProxyServerEnabled = config.getint('PROXY', 'ArticaProxyServerEnabled')
        if ArticaProxyServerEnabled==0: return "",0,"",""
        ArticaProxyServerName = config.get('PROXY', 'ArticaProxyServerName')
        ArticaProxyServerPort =  config.getint('PROXY', 'ArticaProxyServerPort')
        ArticaProxyServerUsername = config.get('PROXY', 'ArticaProxyServerUsername')
        ArticaProxyServerUserPassword = config.get('PROXY', 'ArticaProxyServerUserPassword')
        if len(ArticaProxyServerUsername)>1:
            return ArticaProxyServerName,ArticaProxyServerPort,ArticaProxyServerUsername,ArticaProxyServerUserPassword

        return ArticaProxyServerName,ArticaProxyServerPort,"",""





def DownloadFile(url,destination,logging):
    WgetBindIpAddress = GET_INFO_STR("WgetBindIpAddress")
    CurlUserAgent     = GET_INFO_STR("CurlUserAgent")
    isProxy,isProxyPort,isProxyUser,isProxyPassword=GetProxy()

    if is_file(destination): RemoveFile(destination)

    #print("PycURL %s (compiled against 0x%x)" % (pycurl.version, pycurl.COMPILE_LIBCURL_VERSION_NUM))
    c = pycurl.Curl()
    c.setopt(pycurl.URL, url)
    if len(WgetBindIpAddress)>0:
        logging.info("Bind local ip:'" + WgetBindIpAddress + "'")
        c.setopt(pycurl.INTERFACE, WgetBindIpAddress)

    fPointer = open(destination, 'wb')
    c.setopt(c.WRITEFUNCTION, fPointer.write)
    c.setopt(pycurl.FOLLOWLOCATION, 1)
    c.setopt(pycurl.MAXREDIRS, 5)
    c.setopt(pycurl.CONNECTTIMEOUT, 30)
    c.setopt(pycurl.TIMEOUT, 300)
    c.setopt(pycurl.NOSIGNAL, 1)
    c.setopt(pycurl.SSL_VERIFYPEER, 0)
    c.setopt(pycurl.SSL_VERIFYHOST, 0)

    if len(CurlUserAgent)>2: c.setopt(pycurl.USERAGENT,CurlUserAgent)

    if len(isProxy)>2:
        c.setopt(pycurl.PROXY, "%s:%s" % (isProxy, isProxyPort))
        if len(isProxyUser)>2:
            logging.info("isProxy: " + isProxy+":"+str(isProxyPort) )
            c.setopt(pycurl.PROXYUSERPWD,  "%s:%s" % (isProxyUser, isProxyPassword))
    else:
        c.setopt(pycurl.PROXY, "")

    try:
        c.perform()
        RESPONSE_CODE=int(c.getinfo(pycurl.HTTP_CODE))
        logging.info("Response code: "+str(RESPONSE_CODE))
        logging.info("Total-time: " + str(c.getinfo(c.TOTAL_TIME)))
        logging.info("Download speed: %.2f bytes/second" % (c.getinfo(c.SPEED_DOWNLOAD)))
        logging.info("Document size: %d bytes" % (c.getinfo(c.SIZE_DOWNLOAD)))
    except pycurl.error as ex:
        (code, message) = ex
        logging.info("Error: " + str(code)+" "+message)
        sys.stderr.flush()
        return False

    c.close()

    if RESPONSE_CODE==200:
        logging.info("Downloading Success")
        return True

    return False




def readfile(filename):
    offset = -1
    maxlen = -1
    if not os.path.exists(filename):
        print(filename+" no such file")
        return ''


    fp = open(filename, 'rb')
    try:
        if offset > 0:fp.seek(offset)
        ret = fp.read(maxlen)
        return ret.strip()
    finally:
        fp.close()

def ReturnMD5(xstring):
    try:
        md5=hashlib.md5(xstring.encode('utf-8')).hexdigest()
        return md5
    except:
        return ""

def uri_validator(x):
    try:
        result = urlparse(x)
        return all([result.scheme, result.netloc, result.path])
    except:
        return False





def main(arg):

    try:
        groupid=int(arg[0])
    except:
        print("No argument ( Group ID ) defined !!??")
        raise SystemExit(0)

    try:
        fileuploaded=str(arg[1])
    except:
        fileuploaded = ''

    url=''
    md5_source='not calculated'
    md5_new='not calculated'
    do_not_delete_tmp=False
    calculate_source_md5=True

    WorkPath = "/etc/squid3/acls/urlsdb/" + str(groupid)
    logpath = readfile(WorkPath + "/LOG")
    levelLOG = logging.INFO
    logging.basicConfig(format='%(asctime)s [%(process)d] [%(levelname)s] %(message)s',
                        filename=logpath, filemode='a', level=levelLOG)

    if not os.path.exists(WorkPath): mkdir(WorkPath,0o755)

    logging.info("Checking ACL group id..: "+ str(groupid))
    logging.info("Checking File uploaded.: "+ str(fileuploaded))

    if is_file(WorkPath+"/URL"): url=readfile(WorkPath+"/URL" )
    sourcefile=WorkPath+"/SOURCE"
    tempfile=WorkPath+"/FTEMP"
    databasePath=WorkPath+"/urls.db"
    file_put_contents(WorkPath + "/CHECK", str(current_time_stamp()))
    if is_file(WorkPath+"/CHECK"): os.chmod(WorkPath + "/CHECK", 0o755)


    if len(url)>7:
        if not DownloadFile(url,tempfile,logging):
            raise SystemExit(0)

    if len(fileuploaded)>3:
        calculate_source_md5=False
        if os.path.exists(fileuploaded):
            do_not_delete_tmp=True
            tempfile=fileuploaded
        else:
            tempfile='/usr/share/artica-postfix/ressources/conf/upload/'+fileuploaded

        if not is_file(tempfile):
            print(tempfile+' no such file')
            raise SystemExit(0)



    logging.info("Tempfi: " + tempfile)
    logging.info("Source: " + sourcefile)
    md5_source = md5_file(sourcefile)
    if calculate_source_md5: md5_new=md5_file(tempfile)

    logging.info("Current file: " + md5_source)

    if md5_source == md5_new:
        logging.info("No changes, aborting")
        raise SystemExit(0)


    if os.path.exists(sourcefile):
        logging.info("Remove file: " + sourcefile+" before moving")
        RemoveFile(sourcefile)

    logging.info("Move file: " + tempfile)
    try:
        shutil.copy(tempfile, sourcefile)
        if not do_not_delete_tmp: RemoveFile(tempfile)
    except:
        logging.info("Move file: " + tempfile +" to "+ sourcefile+" Failed")
        raise SystemExit(0)


    logging.info("New updated file: with md5: "+md5_new)

    if os.path.exists(databasePath):
        logging.info("Remove database: " + databasePath)
        RemoveFile(databasePath)

    logging.info("Creating DB: "+databasePath)
    db = anydbm.open(databasePath, 'c')

    logging.info("Get number of lines of : " + sourcefile)
    Max = CountLinesOfFiles(sourcefile)
    logging.info(str(Max)+" lines to scan")

    with open(sourcefile) as fp:
        line = fp.readline()
        cnt = 1
        while line:
            url=line.strip()
            url=url.rstrip()
            if not uri_validator(url): url="https://"+url
            print(str(cnt) + "/" + str(Max) + " [" + url + "]")
            md5=ReturnMD5(url)
            if len(md5) > 0: db[md5]="1"
            line = fp.readline()
            cnt += 1


    db.close()
    logging.info("Success, "+str(cnt)+" rows added in database")
    file_put_contents(WorkPath+"/COUNT",str(cnt))
    file_put_contents(WorkPath+"/TIME",str(current_time_stamp()))
    execute("/usr/sbin/artica-phpfpm-service -reload-proxy")

    os.chmod(WorkPath +"/COUNT", 0o755)
    os.chmod(WorkPath + "/TIME", 0o755)
    os.chmod(WorkPath + "/SOURCE", 0o755)
    os.chmod(WorkPath , 0o755)


if __name__ == "__main__": main(sys.argv[1:])