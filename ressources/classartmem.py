#!/usr/bin/env python
global module_redis
import sys
import os
sys.path.append('/usr/share/artica-postfix/ressources')
import memcache
import traceback as tb
from inspect import currentframe
from datetime import datetime
import hashlib
import time
from unix import *
try:
    import redis
    module_redis=True
except:
    module_redis=False



class art_memcache:
    def __init__(self,rediscnx=None):
        global module_redis
        self.debug=False
        self.error=""
        self.rediscnx=rediscnx
        self.category_cache_cnx=None
        self.mc=None
        self.mcCat=None
        self.INTERNAL_LOGS = []
        self.version="5.3"
        self.EnableCategoriesCache = GET_INFO_INT("EnableCategoriesCache")
        self.CategoryCacheUnix = True
        self.CategoriesCacheRemoteAddr=""
        self.CategoryCachgeUnixPath="/var/run/categories-cache/categories-cache.sock"
        self.CategoriesCacheRemote = GET_INFO_INT("CategoriesCacheRemote")
        if self.CategoriesCacheRemote == 1: self.EnableCategoriesCache = 1
        self.CategoriesCacheRemoteAddr = GET_INFO_STR("CategoriesCacheRemoteAddr")
        self.CategoriesCacheRemotePort = GET_INFO_INT("CategoriesCacheRemotePort")
        if self.CategoriesCacheRemotePort == 0: self.CategoriesCacheRemotePort=2214
        if not module_redis: self.EnableCategoriesCache=0




    def get_linenumber(self):
        cf = currentframe()
        return cf.f_back.f_lineno


    def ilog(self,line,func,text):
        text="--------------: art_memcache:%s %s %s" % (line,func,text)
        self.INTERNAL_LOGS.append(str(text))


    def category_cache_connect(self):
        if self.CategoriesCacheRemote == 1:
            try:
                self.mcCat=redis.Redis(host=self.CategoriesCacheRemoteAddr, port=self.CategoriesCacheRemotePort)
                return True
            except redis.ConnectionError as e:
                self.error = "Redis connection error: %s" % str(e)
                return False
            except:
                self.error = "Could not initalize %s:%s" % (self.CategoriesCacheRemoteAddr,self.CategoriesCacheRemotePort)
                self.xsyslog("[ERROR] [%s]" % self.error)
                return False

        if self.CategoriesCacheRemote == 0:
            try:
                self.mcCat = redis.Redis(unix_socket_path=self.CategoryCachgeUnixPath)
                return True
            except redis.ConnectionError as e:
                self.error = "Redis connection error: %s" % str(e)
                return False
            except:
                self.error = "Could not initalize %s" % (self.CategoryCachgeUnixPath)
                self.xsyslog("[ERROR] [%s] L.78" % self.error)
                return False




    def redis_cnx(self):
        global module_redis
        if not module_redis:
            self.error = "Redis module not found"
            return False

        func="redis_cnx"
        if self.rediscnx is not None:
            if self.rediscnx.find('.sock') > 0:
                try:
                    self.mc = redis.Redis(unix_socket_path=self.rediscnx)
                    return True
                except redis.ConnectionError as e:
                    self.error = "Redis connection error: %s" % str(e)
                    self.ilog(self.get_linenumber(), func, self.error)
                    return False
                except:
                    self.error= "Could not initalize %s" % self.rediscnx
                    self.ilog(self.get_linenumber(),func,"%s %s" % (self.error, tb.format_exc() ) )
                    return False

            if self.rediscnx.find(":")>0:
                splitted=self.rediscnx.split(':')
                try:
                    self.mc = redis.Redis(host=splitted[0], port=int(splitted[1]))
                    return True
                except redis.ConnectionError as e:
                    self.error = "Redis connection error: %s" % str(e)
                    self.ilog(self.get_linenumber(), func, self.error)
                    return False
                except:
                    self.error= "Could not initalize %s" % self.rediscnx
                    self.ilog(self.get_linenumber(), func,"%s %s" % (self.error,tb.format_exc()))
                    return False

        try:
            self.mc = redis.Redis(host="127.0.0.1", port=6123)
            return True
        except redis.ConnectionError as e:
            self.error = "Redis connection error: %s" % str(e)
            self.ilog(self.get_linenumber(), func,  self.error)
            return False
        except:
            self.error = "Could not initalize %s" % self.rediscnx
            self.ilog(self.get_linenumber(), func,"%s %s" % (self.error, tb.format_exc()))
            return False


    def redis_set(self,key,val,xtime=28800):
        self.INTERNAL_LOGS = []
        func = "redis_set"
        if not self.redis_cnx():
            self.ilog(self.get_linenumber(), func, "%s connection error")
            return False
        try:
            self.mc.set(key,val,xtime)
        except redis.ConnectionError as e:
            self.error = "Redis connection error: %s" % str(e)
            self.ilog(self.get_linenumber(), func, self.error)
            return False
        except:
            self.error = "Redis %s " % tb.format_exc()
            self.ilog(self.get_linenumber(), func,"%s" % tb.format_exc())
            return False
        return True

    def redis_hmset(self, key, dict):
        self.INTERNAL_LOGS = []
        func = "redis_hmset"
        if not self.redis_cnx():
            self.ilog(self.get_linenumber(), func, "%s connection error")
            return False
        try:
            self.mc.hmset(key,dict)
        except redis.ConnectionError as e:
            self.error = "Redis connection error: %s" % str(e)
            self.ilog(self.get_linenumber(), func, self.error)
            return False
        except:
            self.error = "Redis %s " % tb.format_exc()
            self.ilog(self.get_linenumber(), func, "%s" % tb.format_exc())
            return False
        return True

    def redis_hgetall(self, key):
        self.INTERNAL_LOGS = []
        func = "redis_hgetall"
        if not self.redis_cnx():
            self.ilog(self.get_linenumber(), func, "%s connection error")
            return False
        try:
            sValue = self.mc.redis_hgetall(key)
        except redis.ConnectionError as e:
            self.error = "Redis connection error: %s" % str(e)
            self.ilog(self.get_linenumber(), func, self.error)
            return None
        except:
            self.error = "Redis %s " % tb.format_exc()
            self.ilog(self.get_linenumber(), func, "%s" % tb.format_exc())
            return None
        return sValue

    def category_cache_set(self,key, value, timeout):
        if not self.category_cache_connect():
            return False
        try:
            self.mcCat.set(key,val,timeout)
        except redis.ConnectionError as e:
            self.error = "Redis connection error: %s" % str(e)
            return False
        except:
            self.error = "Redis %s " % tb.format_exc()
            return False
        return True



    def category_cache_get(self,key):
        if not self.category_cache_connect():
            self.ilog(self.get_linenumber(), func, "%s connection error")
            return None

        try:
            val = self.mcCat.get(key)
            return val
        except redis.ConnectionError as e:
            self.error = "Redis connection error: %s" % str(e)
            self.xsyslog("[ERROR] [%s] L.78" % self.error)
            return None
        except:
            self.error = tb.format_exc()
            self.xsyslog("[ERROR] [%s] L.78" % self.error)
            return None



    def redis_get(self,key):
        self.INTERNAL_LOGS = []
        func="redis_get"
        if not self.redis_cnx():
            self.ilog(self.get_linenumber(), func, "%s connection error")
            return None
        try:
            val = self.mc.get(key)

            if val is None:
                self.ilog(self.get_linenumber(), func, "%s === None" % key)
                return None
            self.ilog(self.get_linenumber(), func, "%s === %s" % (key, val))
            return val
        except redis.ConnectionError as e:
            self.error = "Redis connection error: %s" % str(e)
            self.ilog(self.get_linenumber(), func,  self.error)
            return None
        except:
            self.ilog(self.get_linenumber(), func,"%s" % tb.format_exc())
            return None

    def ksrncache_get(self, key):
        if self.EnableCategoriesCache==1: return self.category_cache_get(key)
        return self.memcache_get(key)

    def ksrncache_set(self, key, value, timeout=3600):
        if self.EnableCategoriesCache == 1: return self.category_cache_set(key, value, timeout)
        return self.memcache_set(key, value, timeout)


    def memcache_get(self,key):
        try:
            mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        except:
            return None

        try:
            value = mc.get(key)
        except (mc.MemcachedKeyTypeError, mc.MemcachedKeyNoneError,
                TypeError, mc.MemcachedKeyCharacterError,
                mc.MemcachedKeyError, mc.MemcachedKeyLengthError,
                mc.MemcachedStringEncodingError):
            self.error = "memcache.get(%s) Error"
            return None

        if value is None: return None

        if type(value) in (list, tuple, dict, str):
            if len(value) > 0:return value
            return None

        return value

    def TimeExec(self,FirstTime):
        sockststop = time.time()
        socksdifference = sockststop - FirstTime
        sock_time = "{} seconds".format(socksdifference)
        return sock_time


    def StrToMD5(self,TheValue):
        return str(hashlib.md5(TheValue).hexdigest())

    def skey_shields_fullcache(self,username,ipaddr,mac,sitename,method):
        if sitename is None: sitename=""
        if username is None: username = ""
        if method is None: method = ""
        sitename=sitename.lower()
        username=username.lower()
        method=method.lower()
        prepare_cache_s="%s|%s|%s|%s|%s" %  (username,ipaddr,mac,sitename,method)
        prepare_cache_s=prepare_cache_s.encode("utf-8")
        prepare_cache=self.StrToMD5(prepare_cache_s)
        smd5 = "SHIELD.%s" % prepare_cache
        return smd5

    def UserAliases(self,macAddr, ipaddr,ipstrongswan=""):
        if macAddr is None: macAddr=""
        if ipaddr is None: ipaddr=""
        if ipstrongswan is None: ipstrongswan=""
        
        macAddr=macAddr.lower()
        func="UserAliases"
        if len(macAddr)>5:
            key="%s:alias" %macAddr
            macpath = "/home/artica/UsersMac/Caches/%s" % macAddr
            value = self.memcache_get(key)
            if value is not None:
                if value == 'NONE': return None
                sfinl=value.split('|')
                if len(sfinl)>0: return str(sfinl[0])
                return str(value)


            if os.path.exists(macpath):
                value=file_get_contents(macpath)
                self.memcache_set(key,value,600)
                sfinl = value.split('|')
                if len(sfinl) > 0: return str(sfinl[0])
                return str(value)

            self.memcache_set(key, "NONE", 600)

        if len(ipstrongswan)>0:
            key = "%s:vpnalias" % ipaddr
            value = self.memcache_get(key)

            if value is not None:
                if value == 'NONE':  return None
                return value

            try:
                value = self.strongswan_alias(ipaddr)
                if value is None:
                    self.memcache_set(key, "NONE", 150)
                    return None

                self.memcache_set(key,value, 150)
                return value
            except:
                self.ilog(self.get_linenumber(), func,"%s" % tb.format_exc())


        key = "%s:alias" % ipaddr
        cachepath = "/home/artica/UsersMac/Caches/%s" % ipaddr
        value = self.memcache_get(key)
        if value is not None:
            if value == 'NONE':  return None
            sfinl = value.split('|')
            if len(sfinl) > 0: return sfinl[0]
            return value

        if os.path.exists(cachepath):
            value = file_get_contents(cachepath)
            self.memcache_set(key, value, 600)
            sfinl = value.split('|')
            if len(sfinl) > 0: return sfinl[0]
            return value

        self.memcache_set(key, "NONE", 600)
        return None

    def strongswan_alias(self,ipaddr):
        data = GET_INFO_STR("strongSwanClientsArray")

        try:
            array = unserialize(data)
        except:
            return None

        for main_index in array:
            subarray = array[main_index]
            for main_index2 in subarray:
                subarray2 = array[main_index][main_index2]
                if not "remote-eap-id" in subarray2: continue
                remote_name = array[main_index][main_index2]["remote-eap-id"]

                if "remote-vips" in subarray2:
                    vips=array[main_index][main_index2]["remote-vips"]
                    for sipar in vips:
                        zipaddr=array[main_index][main_index2]["remote-vips"][sipar]
                        if zipaddr == ipaddr: return remote_name

                if not "remote-id" in subarray2: continue
                remote_id = array[main_index][main_index2]["remote-id"]
                if ipaddr == remote_id: return remote_name

        return None

    def set_squid_user_hits(self,username):
        self.INTERNAL_LOGS=[]
        func = "set_squid_user_hits"
        MainArray={}
        SquidLicenseUsersMaxFound = 0
        serial = datetime.now().strftime('%Y%m%d%H')
        SquidLicenseUsers=self.memcache_get("SquidLicenseUsers")
        if SquidLicenseUsers is not None:
            try:
                MainArray=unserialize(SquidLicenseUsers)
            except:
                self.ilog(self.get_linenumber(), func, "%s" % tb.format_exc())
                MainArray={}

        if "SquidLicenseUsersMaxFound" in MainArray: SquidLicenseUsersMaxFound=MainArray["SquidLicenseUsersMaxFound"]
        if not serial in MainArray:
            MainArray[serial]={}
            MainArray[serial]["USERS"]={}

        if not username in MainArray[serial]["USERS"]:
            self.ilog(self.get_linenumber(), func, "Add user %s" % username)
            MainArray[serial]["USERS"][username]=1
        else:
            self.ilog(self.get_linenumber(), func, "User %s already exists in array" % username)



        NewArray={}
        self.ilog(self.get_linenumber(),func,"SquidLicenseUsersMaxFound: %s" % SquidLicenseUsersMaxFound)

        NewArray["SquidLicenseUsersMaxFound"]=SquidLicenseUsersMaxFound
        for old_serial in MainArray:
            if serial == old_serial:
                NewArray[serial]= MainArray[serial]

        CountOFUsers=len(NewArray[serial]["USERS"])
        self.ilog(self.get_linenumber(), func, "CountOFUsers: %s <> SquidLicenseUsersMaxFound %s" % (CountOFUsers,SquidLicenseUsersMaxFound))

        if CountOFUsers > SquidLicenseUsersMaxFound:
            NewArray["SquidLicenseUsersMaxFound"] = CountOFUsers


        NewArrayEncoded=serialize(NewArray)
        self.memcache_set("SquidLicenseUsers",NewArrayEncoded,3600)





    def memcache_incr(self,key):
        self.INTERNAL_LOGS = []
        value=self.memcache_get(key)
        if value is None:
            self.memcache_set(key,"1",36000)
            return True
        try:
            mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        except:
            self.error="/var/run/memcached.sock connection failed"
            return False

        try:
            mc.incr(key)
        except Exception as e:
            self.error="Error %s (%s)" % (e,tb.format_exc())
            return False

        return True

    def memcache_set(self,key,value,timeout=3600):
        self.INTERNAL_LOGS = []
        func="memcache_set"
        try:
            mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        except:
            self.error=tb.format_exc()
            self.ilog(self.get_linenumber(), func, "%s memcache.Client ERR %s" % (key,self.error))
            return False

        try:
            mc.set(key, str(value),timeout)
        except:
            self.error = tb.format_exc()
            self.ilog(self.get_linenumber(), func, "%s mc.set ERR %s" % (key, self.error))
            return False

        return True

    def xsyslog(self,text):
        sysDaemon=syslog
        sysDaemon.openlog("ksrn", syslog.LOG_PID)
        sysDaemon.syslog(syslog.LOG_INFO, "[MEMORY_CLASS]: %s" % text)