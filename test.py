#!/usr/bin/env python
import sys
import resource

string="15 pagead2.googlesyndication.com:443 192.168.1.11/192.168.1.11 david-pc CONNECT myip=192.168.1.190 myport=3128 mac=68:54:5a:94:e7:56 sni=- referer=- tag=shieldsblock:%20yes%0D%0Asrn:%20ARTICA%0D%0Acategory:%205%0D%0Acategory-name:%20Advertising%0D%0Aclog:%20cinfo:5-Advertising;%0D%0Auser:%20david-pc%0D%0Aptime:%200.0460360050201%0D%0A bump_mode=- forwardedfor=- domain=pagead2.googlesyndication.com webfilter-acl=MacToUid_acl"

tabs=string.split()
ntab=[]
tabs.pop(0)
for index in tabs:

    if index.find('myip=')>-1:break
    print(index,index.find('myip='))
    ntab.append(index)
print "".join(ntab)



