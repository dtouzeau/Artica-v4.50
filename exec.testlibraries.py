#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *


try:
    import pycurl
    print "[TRUE]: pycurl"
    file_put_contents("/etc/artica-postfix/settings/Daemons/PythonPyCurlMissing",0)
    
except:
    print "[FALSE]: pycurl not installed/Enabled!"
    file_put_contents("/etc/artica-postfix/settings/Daemons/PythonPyCurlMissing",1)
    
    
try:
    from OpenSSL import crypto
    print "[TRUE]: pyOpenSSL"
    file_put_contents("/etc/artica-postfix/settings/Daemons/PythonpyOpenSSLMissing",0) 
except:
    print "[FALSE]: pyOpenSSL not installed/Enabled!"
    file_put_contents("/etc/artica-postfix/settings/Daemons/PythonpyOpenSSLMissing",1)    
    