#!/usr/bin/env python
from unix import *
import os
import time
import logging
import re
from netaddr import IPNetwork, IPAddress


class SQUIDVIRTUSERS:
    
    def __init__(self,logging):
        self.UfdbgClientDebug=GET_INFO_INT("UfdbgClientDebug")
        self.EnableArticaHotSpot=GET_INFO_INT("EnableArticaHotSpot")
        self.EnableSquidMicroHotSpot=GET_INFO_INT("EnableSquidMicroHotSpot")
        pass


    def MicroHotSpotChecker(self,UserKey):
        HotSpotPath="/home/artica/microhotspot/Caches/"+UserKey
        if not os.path.exists(HotSpotPath):
            self.logging.debug(HotSpotPath+" No such file")
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
        self.logging.debug("Finish at "+str(FinishTime)+ " in " +str(Reste)+" seconds")
        if CurrentTime>FinalTime:
            self.logging.debug("Remove '"+HotSpotPath+"' and return Null")
            try:
                os.unlink(HotSpotPath)
            except:
                self.logging.info(tb.format_exc())
            return ""
        if len(UserName)>2: return UserName
        pass
    
    
    
    def TryToFindAnUserID(self,UserKey):
        userid=''
        if self.EnableSquidMicroHotSpot==1: userid=self.MicroHotSpotChecker(UserKey)
        if len(userid)>0: return userid
        if self.EnableArticaHotSpot==1:
            FilePath="/home/artica/UsersMac/Hotspots/"+UserKey
            if os.path.exists(FilePath):
                Conf=file_get_contents(FilePath)
                array=Conf.split("|")
                userid=array[0]
                if len(userid)>0: return userid
                
        FilePath="/home/artica/UsersMac/OpenVPN/"+UserKey
        if os.path.exists(FilePath):
            userid=file_get_contents(FilePath)
            if len(userid)>1: return userid
                
            
            
        FilePath="/home/artica/UsersMac/Caches/"+UserKey
        if os.path.exists(FilePath):
            Conf=file_get_contents(FilePath)
            array=Conf.split("|")
            userid=array[0]
            if len(userid)>0: return userid
        
        return ""
        pass