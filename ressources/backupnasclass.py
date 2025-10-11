#!/usr/bin/env python
from unix import *
import re
import os

class BackupToNas():
    
    def __init__(self):
        self.Debug=False
        self.mountPoint="/mnt/BackupSquidLogsUseNas"
        self.BackupSquidLogsUseNas=GET_INFO_INT("BackupSquidLogsUseNas")
        pass
    
    def ConnectToNAS(self):
        if not os.path.exists("/sbin/mount.cifs"):
            print "[ERR.]: /sbin/mount.cifs no such binary"
            return False
        password=",password="
        zcredentials=""
        BackupSquidLogsNASIpaddr=GET_INFO_STR("BackupSquidLogsNASIpaddr");
        BackupSquidLogsNASFolder=GET_INFO_STR("BackupSquidLogsNASFolder");
        BackupSquidLogsNASUser=shellEscapeChars(GET_INFO_STR("BackupSquidLogsNASUser"))
        BackupSquidLogsNASPassword=shellEscapeChars(GET_INFO_STR("BackupSquidLogsNASPassword"))
        
        if ismounted(self.mountPoint): return True
        
        if len(BackupSquidLogsNASPassword)>1:
            password=",password="+BackupSquidLogsNASPassword
                
        if len(BackupSquidLogsNASUser)>1:
            credentials=" -o username="+BackupSquidLogsNASUser+password+" "
                    
        if len(BackupSquidLogsNASUser)==0:
            credentials=" -o user=,password= "
        
        zcmd="/sbin/mount.cifs //"+BackupSquidLogsNASIpaddr+"/"+BackupSquidLogsNASFolder+" "+self.mountPoint+credentials
        print "/sbin/mount.cifs //"+BackupSquidLogsNASIpaddr+"/"+BackupSquidLogsNASFolder+" "+self.mountPoint+" -o username="+BackupSquidLogsNASUser+",password=xxxx"
        os.system(zcmd)
        if not ismounted(self.mountPoint): return False
        return True
        pass
    
    
    def DisconnectFromNas(self):
        self.mountPoint="/mnt/BackupSquidLogsUseNas"
        if not ismounted(self.mountPoint): return False
        print "[INFO]: Disconnect from "+self.mountPoint
        os.system("/bin/umount -l "+self.mountPoint)
        pass
    
    
        
        
        
    