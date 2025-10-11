#!/usr/bin/env python
import sys
if sys.version_info.major == 3: unicode = str
try:
    from urllib.request import urlopen
except ImportError:
    from urllib2 import urlopen

try:
    from urllib import quote_plus
except ImportError:
    from urllib.parse import quote_plus

import re

import os.path
from datetime import date, timedelta, datetime
from pipes import quote
import time
import socket
import fcntl
import struct
import array
import inspect
import subprocess
import pwd
import grp
import hashlib
import base64
global phpserialize_available


try:
    from phpserialize import serialize, unserialize
    phpserialize_available=True
except ImportError:
    phpserialize_available=False
        
import memcache

is_array = lambda var: isinstance(var,(list,tuple))


# ---------------------------------------------------------------------------------------------------
def file_as_bytes(file):
    with file:
        return file.read()

def md5_file(path):
    if not is_file(path): return ""
    return hashlib.md5(file_as_bytes(open(path, 'rb'))).hexdigest()

def urlencode(str):
    return quote_plus(str)

def execute(command):
    array=[]
    zreturn=[]
    if strfound(" ",command):
        array=command.split(" ")
    else:
        array[0]=command
    p = subprocess.Popen(array,stdout=subprocess.PIPE,stderr=subprocess.STDOUT)
    results=iter(p.stdout.readline, b'')
    for line in results:
        line=line.rstrip()
        if len(line)==0:next
        zreturn.append(line)
     
    return zreturn
# ---------------------------------------------------------------------------------------------------
def roundTime(dt=None, dateDelta=timedelta(minutes=1)):
    roundTo = dateDelta.total_seconds()
    if dt == None : dt = datetime.now()
    seconds = (dt - dt.min).seconds
    rounding = (seconds+roundTo/2) // roundTo * roundTo
    return dt + timedelta(0,rounding-seconds,-dt.microsecond)

def filename_10_minutes():
    return roundTime(datetime.now(),timedelta(minutes=10)).strftime('%Y%m%d%H%M')

def sql_time_10_minutes():
    return roundTime(datetime.now(), timedelta(minutes=10)).strftime('%Y-%m-%d %H:%M:%S')





def difference_minutes(ztimestamp):
    xnow = datetime.now()
    from_time=datetime.fromtimestamp(float(int(ztimestamp)))
    d1_ts = time.mktime(from_time.timetuple())
    d2_ts = time.mktime(xnow.timetuple())
    return int(d2_ts-d1_ts) / 60

# ---------------------------------------------------------------------------------------------------    
def RemoveFile(path):
    if not os.path.exists(path):
        return
    if os.path.isdir(path):
        return
    try:
        os.unlink(path)
    finally:
        return
# ---------------------------------------------------------------------------------------------------
def CountLinesOfFiles(path):
    if not os.path.exists(path): return 0
    file = open(path, "r")
    line_count = 0
    for line in file:
        if line != "\n": line_count += 1
    file.close()
    return line_count
# ---------------------------------------------------------------------------------------------------


def ip2long(i):
    s = i.split('.')
    return (int(s[0]) << 24 | int(s[1]) << 16 | int(s[2]) << 8 | int(s[3]))
 # ---------------------------------------------------------------------------------------------------   
def is_valid_ip(ip):
    try:
        if len(filter(lambda x: 0 <= int(x) <= 255, ip.split('.'))) == 4:
            return True
    except:
       return False
# ---------------------------------------------------------------------------------------------------
def GET_INFO_STR(key):
    try:
        mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        key_to_find=str("SET:"+str(key))
        Value=mc.get(key_to_find)
        if Value is not None:
            if len(Value) > 0: return Value
    except:
        key_to_find=key

        
    filename="/etc/artica-postfix/settings/Daemons/"+key
    if not os.path.exists(filename): return ''
    return file_get_contents(filename)
# ---------------------------------------------------------------------------------------------------
def strtoint(string):
    if string is None: return 0
    string=string.strip()
    if string=='':
        return 0

    try:
        testdata=unicode(string,'utf-8')
    except:
        testdata=string

    if testdata.isnumeric():
        return int(string)
    return 0
# ---------------------------------------------------------------------------------------------------
def GET_INFO_INT(key):
    try:
        mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        key_to_find=str("SET:"+str(key))
        Value=mc.get(key_to_find)
        if Value is not None:
            if len(Value)>0: return strtoint(Value)
    except:
        key_to_find=key
        

    filename="/etc/artica-postfix/settings/Daemons/"+key
    if not os.path.exists(filename):
        file_put_contents(filename,0)
        return 0
    data=file_get_contents(filename)
    return strtoint(data)
# ---------------------------------------------------------------------------------------------------
def read_list_from_path(path):
    xlist=set()
    if not os.path.exists(path): return xlist
    try:
        with open(path, "r") as f:
            for line in f:
                xlist.add(line.rstrip())
    except IOError:
        return xlist

    return xlist
# ---------------------------------------------------------------------------------------------------



def SET_INFO(key,value):
    try:
        mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        mc.set(key,value)
    except:
        print("Set Info "+key+" error")

    filename = "/etc/artica-postfix/settings/Daemons/" + key
    file_put_contents(filename, value)


# ---------------------------------------------------------------------------------------------------

def is_file(path):
    if not os.path.exists(path): return False
    return True
# ---------------------------------------------------------------------------------------------------
def strfound(search,zstring):
    try:
        if zstring.index(search)>0:
            return True
    except:
        return False
    return False
# ---------------------------------------------------------------------------------------------------
def shellEscapeChars(zstring):
    zstring=str(zstring)
    zstring=zstring.replace(" ","\ ")
    zstring=zstring.replace('$','\$')
    zstring=zstring.replace("&","\&")
    zstring=zstring.replace("?","\?")
    zstring=zstring.replace("#","\#")
    zstring=zstring.replace("[","\[")
    zstring=zstring.replace("]","\]")
    zstring=zstring.replace("{","\{")
    zstring=zstring.replace("}","\}")
    zstring=zstring.replace('"','\\"')
    zstring=zstring.replace("'","\\'")
    zstring=zstring.replace("(","\(")
    zstring=zstring.replace(")","\)")
    zstring=zstring.replace("<","\<")
    zstring=zstring.replace(">","\>")
    zstring=zstring.replace("!","\!")
    zstring=zstring.replace("+","\+")
    zstring=zstring.replace(";","\;")
    zstring=zstring.replace("|","\|")
    zstring=zstring.replace("%","\%")
    return zstring
# ---------------------------------------------------------------------------------------------------
def StringToRegex(zstring):
    RULE_PATTERN=str(zstring)
    RULE_PATTERN=RULE_PATTERN.replace(".","\.")
    RULE_PATTERN=RULE_PATTERN.replace("?","\?")
    RULE_PATTERN=RULE_PATTERN.replace("*",".*?")
    RULE_PATTERN=RULE_PATTERN.replace("(","\(")
    RULE_PATTERN=RULE_PATTERN.replace(")","\)")
    RULE_PATTERN=RULE_PATTERN.replace("[","\[")
    RULE_PATTERN=RULE_PATTERN.replace("]","\]")
    RULE_PATTERN=RULE_PATTERN.replace("|","\|")
    RULE_PATTERN=RULE_PATTERN.replace("$","\$")
    RULE_PATTERN=RULE_PATTERN.replace("!","\!")
    RULE_PATTERN=RULE_PATTERN.replace("{","\{")
    RULE_PATTERN=RULE_PATTERN.replace("}","\}")
    return RULE_PATTERN
# ---------------------------------------------------------------------------------------------------
def str2bool(v):
    return v.lower() in ("yes", "true", "t", "1","oui","si")

def file_get_contents(filename, use_include_path = 0, context = None, offset = -1, maxlen = -1):
    
    if (filename.find('://') > 0):
        ret = urllib2.urlopen(filename).read()
        if (offset > 0):
            ret = ret[offset:]
        if (maxlen > 0):
            ret = ret[:maxlen]
        return ret
    else:
        if not os.path.exists(filename):
            return ''
        
        fp = open(filename,'rb')
        try:
            if (offset > 0):
                fp.seek(offset)
            ret = fp.read(maxlen)
            return ret.strip()
        finally:
            fp.close( )
    pass
# ---------------------------------------------------------------------------------------------------------------
def file_put_contents(filename,data):
    try:
        f = open(filename, 'w')
        f.write(str(data))
        f.close()
    except:
        return
# ---------------------------------------------------------------------------------------------------------------
def mkdir(path,chmod):
    if os.path.exists(path):
        return True
    try:
        os.makedirs(path, chmod)
    except:
        return False
    
# ---------------------------------------------------------------------------------------------------------------
def mkchown(path,username,groupname):
    if not os.path.exists(path):
        return True
    try:
        uid = pwd.getpwnam(username).pw_uid
        gid = grp.getgrnam(groupname).gr_gid
        os.chown(path, uid, gid)
    except:
        return False
    
# ---------------------------------------------------------------------------------------------------------------



def dirname(path):
    return os.path.dirname(path.rstrip(os.pathsep)) or '.'
# ---------------------------------------------------------------------------------------------------------------
def hostname_g():
    results=GET_INFO_STR("myhostname")
    if len(results)>3: return results
    return socket.getfqdn()
# ---------------------------------------------------------------------------------------------------------------
def IpToMac(ipaddr):
    with open('/proc/net/arp',"r") as f:
        for txt in f :
                txt=txt.rstrip('\n')
                if len(txt)<5: continue
                l=txt.split()
                if not ipaddr in l: continue
                return l[3]
    return None
    pass
# ---------------------------------------------------------------------------------------------------------------


def ismounted(mount_point):
    mount_point=mount_point.replace("/",'\/')
    mount_point=mount_point.replace(".",'\.')
    mount_point=mount_point.replace("$",'\$')
    
    with open('/proc/mounts') as f:
        for line in f:
            matches=re.search(mount_point,line)
            if not matches: continue
            try:
                f.close()
            except:
                return True
            return True
        
    return False
# ---------------------------------------------------------------------------------------------------------------

def unquote(url):
  return re.compile('%([0-9a-fA-F]{2})',re.M).sub(lambda m: chr(int(m.group(1),16)), url)
# ---------------------------------------------------------------------------------------------------------------
def file_time_min(path):
    if not os.path.exists(path):
        return 100000
    last_modified = os.path.getmtime(path)
    data2 = int(time.time())
    difference = (data2 - last_modified)
    return round(difference/60);	 
# ---------------------------------------------------------------------------------------------------------------
def all_interfaces():
    max_possible = 128  # arbitrary. raise if needed.
    bytes = max_possible * 32
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    names = array.array('B', '\0' * bytes)
    outbytes = struct.unpack('iL', fcntl.ioctl(
        s.fileno(),
        0x8912,  # SIOCGIFCONF
        struct.pack('iL', bytes, names.buffer_info()[0])
    ))[0]
    namestr = names.tostring()
    lst = []
    for i in range(0, outbytes, 40):
        name = namestr[i:i+16].split('\0', 1)[0]
        ip   = namestr[i+20:i+24]
        lst.append((name, ip))
    return lst
# ---------------------------------------------------------------------------------------------------------------
def __LINE__():
    return inspect.currentframe().f_back.f_lineno
# ---------------------------------------------------------------------------------------------------------------
def squid_admin_mysql(severity, subject, text,function,filename,line):
    import hashlib
    ArticaLogDir=GET_INFO_STR("ArticaLogDir")
    if len(ArticaLogDir) < 3: ArticaLogDir="/var/log/artica-postfix"
    array={}    
    array["zdate"]=datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    array["subject"]=subject
    array["text"]=text
    array["severity"]=severity
    array["function"]=function
    array["file"]=filename
    array["line"]=line
    array["pid"]=os.getpid()
    array["TASKID"]=0
    serialized=serialize(array)
    zmd5=hashlib.md5(serialize(array)).hexdigest()
    file_put_contents(ArticaLogDir+"/squid_admin_mysql/"+zmd5+".log", serialized)

# ---------------------------------------------------------------------------------------------------------------
def base64_decode(str):
    if str is None: return ""
    if len(str)==0: return ""
    try:
        return base64.b64decode(str)
    except:
        return ""

def base64_encode(str):
    if str is None: return ""
    if len(str)==0: return ""
    try:
        return base64.b64encode(str)
    except:
        return ""

def md5_string(TheString):
    return hashlib.md5(TheString).hexdigest()

def format_ip(addr):
    return str(ord(addr[0])) + '.' + \
           str(ord(addr[1])) + '.' + \
           str(ord(addr[2])) + '.' + \
           str(ord(addr[3]))

# ---------------------------------------------------------------------------------------------------------------
def strtotime(string, format_string = "%Y-%m-%d %H:%M"):
    tuple = time.strptime(string, format_string)
    return int(time.mktime(tuple))
# ---------------------------------------------------------------------------------------------------------------
def current_time_stamp():
    return int(time.time())
# ---------------------------------------------------------------------------------------------------------------
def is_running_from_pidpath(pidpath):
    if not is_file(pidpath): return False
    pid=strtoint(file_get_contents(pidpath))
    if pid==0: return False
    if os.path.isdir('/proc/{}'.format(pid)): return True
    return False
# ---------------------------------------------------------------------------------------------------------------