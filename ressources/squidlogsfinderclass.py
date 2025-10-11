#!/usr/bin/env python
# -*- coding: utf-8 -*-

from unix import *
import re
import os
import sys
import datetime
import shutil
from glob import glob
from dateutil import parser
sys.path.append('/usr/share/artica-postfix/ressources')
from backupnasclass import *


class SourceLogsFinder():
    
    def __init__(self):
        self.Debug=False
        self.skipped=0
        self.ArrayFiles=[]
        pass
    
    def GetFiles(self,FromDateInt,ToDateInt):
        BackupMaxDaysDir=GET_INFO_STR("BackupMaxDaysDir")
        BackupSquidLogsUseNas=GET_INFO_INT("BackupSquidLogsUseNas")
        BackupSquidLogsNASFolder2=GET_INFO_STR("BackupSquidLogsNASFolder2")
        if len(BackupSquidLogsNASFolder2)<2: BackupSquidLogsNASFolder2="artica-backup-syslog"
        if len(BackupMaxDaysDir)<4: BackupMaxDaysDir="/home/logrotate_backup"
        skipped=0
        NAS=BackupToNas()
        if BackupSquidLogsUseNas==1:
            
            print("[INFO]: Mouting to NAS system")
            mkdir("/mnt/BackupSquidLogsUseNas",755)
            if not NAS.ConnectToNAS():
                print "[ERR.]: Unable to connect to NAS system"
                return False
            
            BackupMaxDaysDir=NAS.mountPoint+"/"+BackupSquidLogsNASFolder2
        
        print "[INFO]: Scanning Directory "+BackupMaxDaysDir
        ScanFiles = [y for x in os.walk(BackupMaxDaysDir) for y in glob(os.path.join(x[0], '*.gz'))]
        print "[INFO]: In "+BackupMaxDaysDir+" Found "+ str(len(ScanFiles))+" files"
        self.skipped=0
        for filepath in ScanFiles:
            matches=re.search("\/cache-",filepath)
            if matches:
                if self.Debug: "[SKIP]: "+zBaseName+" -> /cache-*"
                skipped=skipped+1
                continue
            
            matches=re.search("\/access-tail\.",filepath)
            if matches:
                if self.Debug: "[SKIP]: "+zBaseName+" -> /access-tail.*"
                skipped=skipped+1
                continue
            
            matches=re.match("\/.*?.([0-9\-]+)_([0-9\-]+)--([0-9\-]+)_([0-9\-]+)\.gz",filepath)
            if not matches:
                if self.Debug: "[SKIP]: "+zBaseName+" -> regex not matches"
                skipped=skipped+1
                continue
            
            zTime1=matches.group(2)
            zTime2=matches.group(4)
            zTime1=zTime1.replace("-",":")
            zTime2=zTime2.replace("-",":")
            zDay1=matches.group(1)+" "+zTime1
            zDay2=matches.group(3)+" "+zTime2
            
            dt = parser.parse(zDay1)
            zDay1Int=int(dt.strftime("%s"))
        
            dt = parser.parse(zDay2)
            zDay2Int=int(dt.strftime("%s"))
            
            if zDay1Int<FromDateInt:
                skipped=skipped+1
                if self.Debug: "[SKIP]: "+zBaseName+" Dates not matches"
                continue
            
            self.ArrayFiles.append(filepath)
            
        if BackupSquidLogsUseNas==1: NAS.DisconnectFromNas()     
        pass
        
        
        
        
            
            