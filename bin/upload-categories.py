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
import ftplib
from ftplib import FTP
import shutil
from phpserialize import serialize, unserialize
import syslog
try:
    import  paramiko
except:
    xsyslog(tb.format_exc())




def xsyslog(text):
    print(text)
    syslog.openlog("categories-backup", syslog.LOG_PID)
    syslog.syslog(syslog.LOG_INFO,"%s (upload-engine)" % text)


def writepid():
    pidpath="/var/run/upload-category.pid"
    outputFile = open(pidpath, "w")
    pid = str(os.getpid())
    outputFile.write(pid)
    outputFile.close()


def main(argv):
    official=0
    CategoriesBackupCompiled=GET_INFO_STR("CategoriesBackupCompiled")
    if len(CategoriesBackupCompiled)==0:
        xsyslog("No rules as been defined")
        return False


    mainarray=unserialize(CategoriesBackupCompiled)

    for index in mainarray:
        row=mainarray[index]
        host=row["host"]
        protocol=row["protocol"]
        username=row["username"]
        password = row["password"]
        destpath=row["destpath"]
        dnsmethod= row["dnsmethod"]
        print("%s %s %s" % (host,protocol,username))
        if protocol==1:
            print("%s FTP" % host)
            upload_to_ftp(host,destpath,username,password,dnsmethod)

        if protocol == 0:
            print("%s SSH" % host)
            upload_to_ssh(host, destpath, username, password,dnsmethod)




def upload_to_ssh(UploadFTPserv,UploadFTPDir,UploadFTPusr,UploadFTPpass,dnsmethod):
    if dnsmethod==1: compile_root = "/home/artica/categories_rbl"
    UploadFTPDir = "/home/dtouzeau"
    try:
        print("SSH Connecting to the %s server using %s...." % (UploadFTPserv,UploadFTPusr))
        ssh_client = paramiko.SSHClient()
        ssh_client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh_client.connect(hostname=UploadFTPserv, username=UploadFTPusr, password=UploadFTPpass)

    except:
        xsyslog("Connecting to the %s ssh server failed")
        xsyslog(tb.format_exc())
        return False


    sftp = ssh_client.open_sftp()
    if(len(UploadFTPDir)>0): sftp.chdir(UploadFTPDir)

    trans = paramiko.Transport((UploadFTPserv, 22))
    trans.connect(username=UploadFTPusr, password=UploadFTPpass)
    sftp = paramiko.SFTPClient.from_transport(trans)

    #print("Listing current directory")
    #file_names = sftp.listdir()
    #for file_name in file_names:
    #    print(file_name)



    files = os.listdir(compile_root)
    for sfile in files:
        fullsrc = "%s/%s" % (compile_root, sfile)

        remotedest = "%s/%s" % (UploadFTPDir, sfile)
        if len(UploadFTPDir) == 0: remotedest = sfile
        local_size = os.path.getsize(fullsrc)
        try:
            print("Upload %s (%s bytes) -> %s" % (fullsrc,local_size, remotedest))
            sftp.remove(remotedest)
            sftp.put(fullsrc, remotedest)
        except:
            xsyslog(tb.format_exc())
            return False
        sftp.close
        return False

    sftp.close()



def upload_to_ftp(UploadFTPserv,UploadFTPDir,UploadFTPusr,UploadFTPpass,dnsmethod):
    compile_root = "/home/artica/categories_rbl"


    try:
        print("Connecting to the %s server...." % UploadFTPserv)
        ftp = FTP(UploadFTPserv, UploadFTPusr, UploadFTPpass)

    except ftplib.error_perm, reason:
        strcode=str(reason)[:3]
        xsyslog("Connecting to the %s server failed error code %s (%s)" % (UploadFTPserv, strcode, reason))
        return False

    except:
        print("Connecting <%s>" % tb.format_exc())
        xsyslog(tb.format_exc())
        return False

    ftp.set_pasv(True)

    if len(UploadFTPDir)>2:
        print("Going to %s ...." % UploadFTPDir)
        try:
            ftp.cwd(UploadFTPDir)
            ssdir= ftp.pwd()
            print("Current is %s ...." % ssdir)
        except ftplib.error_perm, reason:
            strcode = str(reason)[:3]
            xsyslog("Moving to the %s directory server:%s failed error code %s (%s)" % (UploadFTPDir,UploadFTPserv, strcode, reason))
            return False

        except:
            print("Moving <%s>" % tb.format_exc())
            xsyslog(tb.format_exc())
            return False

    ssdir = ftp.pwd()
    print("Current is %s ...." % ssdir)

    files = os.listdir(compile_root)
    ftp.encoding = "utf-8"
    for sfile in files:
        fullsrc = "%s/%s" % (compile_root, sfile)
        remotedest="%s/%s" % (UploadFTPDir,sfile)
        if len(UploadFTPDir)==0: remotedest=sfile
        local_size = os.path.getsize(fullsrc)
        try:
            remote_size = ftp.size(sfile);
        except:
            remote_size = 0
        print("[%s]: %s against remote [%s] %s"% (fullsrc,local_size,remotedest,remote_size))

        with open(fullsrc, "rb") as file:
            xsyslog("Uploading %s" % (fullsrc))
            ftp.storbinary("STOR %s" % sfile, file)
        return False


    print("Disconnecting...")
    ftp.quit()


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


