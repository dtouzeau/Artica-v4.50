#!/usr/bin/python

import sys,re,urllib


dat = {}
rqs = {}


def get_domain(url):
  u = urllib.urlsplit(url)
  return u.netloc

def get_top_domain(domain):
  matches=re.search('^[0-9\.]+$',domain)
  if matches: return domain

  domain_parts = domain.split('.')
  if len(domain_parts) < 2:
    return domain
  top_domain_parts = 2
  # if a domain's last part is 2 letter long, it must be country name
  if len(domain_parts[-1]) == 2:
    if domain_parts[-1] in ['uk', 'jp']:
      if domain_parts[-2] in ['co', 'ac', 'me', 'gov', 'org', 'net']:
        top_domain_parts = 3
    else:
      if domain_parts[-2] in ['com', 'org', 'net', 'edu', 'gov']:
        top_domain_parts = 3
  return '.'.join(domain_parts[-top_domain_parts:])

def sort_by_traffic(a,b):
  return cmp(a[1],b[1])

def report():
  c = []
  e = []
  totalBytes = float(0)
  totalHitBytes = float(0)
  for h in dat:
    cur = dat.get(h)
    hitBytes=cur[0]
    bytes=float(cur[1])
    if bytes>0:
      c.append([h, bytes, hitBytes/bytes*100])
    totalBytes += bytes
    totalHitBytes += hitBytes

  c=sorted(c, sort_by_traffic)
  c.reverse()

  for a in rqs:
      cur = rqs.get(a)
      e.append([cur,a])

  e=sorted(e)
  e.reverse()

  for h in e:
    print("%s %s" % (h[1],h[0]) )

  del c[25:]

  ratio = totalHitBytes/totalBytes*100
  if (totalHitBytes/1024 > 100000):
    totalHitBytes = "{:.2f}".format(totalHitBytes/1024/1024) + "M"
  else:
    totalHitBytes /= 1024
    totalHitBytes = "{:.2f}".format(totalHitBytes) + "K"
  if (totalBytes/1024 > 100000):
    totalBytes = "{:.2f}".format(totalBytes/1024/1024) + "M"
  else:
    totalBytes /= 1024
    totalBytes = "{:.2f}".format(totalBytes) + "K"

  print ("cache effect: %2.2f%%, save %s of %s" % (ratio, totalHitBytes, totalBytes))
  print("most accessed sites (by traffic)")
  print("%33s %8s  %s" % ("Host", "Kbytes", "Ratio"))
  for h in c:
    print ("%33s %8d%7.2f" % (h[0], h[1]/1024, h[2]))




def take_split(sl):
  shortURI = sl[6]
  isHit = 0
  hitBytes = 0
  bytes = int(sl[4])

  if shortURI[:7] == "http://":
    shortURI = shortURI[7:]
  elif shortURI[:6] == "ftp://":
    shortURI = shortURI[6:]
  if shortURI.find("/") != -1:
    shortURI = shortURI[:shortURI.find("/")]

  if sl[3].find("HIT") != -1 :hitBytes = bytes

  matches=re.search('^(.+?):[0-9]+',shortURI)
  if matches: shortURI=matches.group(1)
  shortURI=get_top_domain(shortURI)

  if rqs.has_key(shortURI):
      cur = rqs.get(shortURI)
      rqs[shortURI]=cur+1
  else:
      rqs[shortURI] =1


  if dat.has_key(shortURI):
    cur = dat.get(shortURI)
    dat[shortURI]=[cur[0]+hitBytes, cur[1]+bytes ]
  else:
    dat[shortURI]=[hitBytes, bytes]


##                               3       4     5     6
## Timestamp Elapsed Client Action/Code Size Method URI Ident Hierarchy/From Content
def take_line(s):
  sl = s.split(" ")
  sl = [f for f in sl if len(f)>0 ]
  if len(sl) > 10:
    take_split(sl)
  else:
    print ("wrong format [",s,"]")

def read_input(f):
	while True:
	  s = f.readline()
	  if not s:
		break
	  take_line(s)

if sys.stdin.isatty():
	read_input(open ("/var/log/squid3/access.log"))
else:
	read_input(sys.stdin)

report()


