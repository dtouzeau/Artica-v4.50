#!/usr/bin/python -O
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import re
import os
import gzip
import traceback as tb
import csv
from unix import *
from datetime import datetime
from categorizeclass import *
from theshieldsclass import *
from phpserialize import serialize, unserialize
global debug
global TheDoms
global TheCatz
global catz
TheDoms={}
TheCatz={}
catz=categorize()


def progress(prc,text):
    array={}
    array["POURC"]=prc
    array["TEXT"]=text
    serial=serialize(array)
    print("%s - %s" % (prc,text))
    file_put_contents("/usr/share/artica-postfix/ressources/logs/web/access.log.parser",serial)

def percentage(part, whole):
  percentage = 100 * float(part)/float(whole)
  percentage=round(percentage)
  return percentage

def parseLine(TheLine):
    global debug
    global TheDoms
    global TheCatz
    global catz

    if TheLine.find('000 0 NONE')> 0 : return False
    if TheLine.find('error:invalid-request')> 0 : return False
    matches = re.search('(CONNECT|HEAD|GET|DELETE|POST|LIST|OPTIONS|PATCH|PUT)\s+(.+?)\s+', TheLine)
    if not matches:
        print("No matches %s" % TheLine)
        return False

    Domain=matches.group(2)
    matches = re.search('^http.*?://(.+)', Domain)
    if matches: Domain = matches.group(1)
    matches = re.search('(.*?)/', Domain)
    if matches: Domain = matches.group(1)



    matches = re.search('^(.+?):([0-9]+)', Domain)
    if matches:Domain=matches.group(1)
    if Domain in TheDoms:
        c=TheDoms[Domain]
        c=c+1
        TheDoms[Domain]=c
    else:
        TheDoms[Domain]=1

    if not Domain in TheCatz:
        catz.debug_cache=True
        category=catz.get_category_fixed(Domain)
        TheCatz[Domain]=catz.category_int_to_string(category)




def main(argv):
    global debug
    start_time = datetime.now()
    debug=False
    remove = True
    zcat = categorize()
    try:
        filename = argv[0]
    except:
        print("Please specify the file source")
        progress(110, "Please specify the file source")
        sys.exit(1)

    basepath = "/usr/share/artica-postfix"
    fullpath = "%s/ressources/conf/upload/%s" % (basepath,filename)
    target_file=fullpath
    asGz=False
    if not is_file(fullpath):
        if not is_file(filename):
            progress(110,"%s no such file" % filename)
            sys.exit(1)
        else:
            remove=False
            target_file=filename

    print("Open %s" % target_file)
    matches=re.search('\.gz$',target_file)
    clines=0
    if matches:
        asGz=True
        print("Open GZ %s" % target_file)
        with gzip.open(target_file, 'rt') as file_in:
            for line in file_in: clines=clines+1
    else:
        print("Open TEXT %s" % target_file)
        with open(target_file) as file_in:
            for line in file_in: clines=clines+1
    print("Parsing %s lines" % clines)

    cur=0
    outprc=0
    if asGz:
        with gzip.open(target_file, 'rt') as file_in:
            for line in file_in:
                cur=cur+1
                prc=percentage(cur,clines)
                if prc>90: prc=90
                if(prc>outprc):
                    progress(prc,"Analyze...")
                    outprc=prc
                parseLine(line)
    else:
        with open(target_file) as file_in:
            for line in file_in:
                cur = cur + 1
                prc = percentage(cur, clines)
                if prc > 90: prc = 90
                if (prc > outprc):
                    progress(prc, "Analyze...")
                    outprc = prc
                parseLine(line)

    if remove: RemoveFile(target_file)
    global TheDoms
    global TheCatz

    with open('/usr/share/artica-postfix/ressources/logs/web/access.log.csv', 'w') as csvfile:
        fieldnames = ['domain', 'hits','category']
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        writer.writeheader()
        for domain in TheDoms:
            category=""
            if domain in TheCatz: category=TheCatz[domain]
            sCount=TheDoms[domain]
            writer.writerow({'domain': domain, 'hits': sCount,'category':category })

    progress(100, "Done...")
    print("/usr/share/artica-postfix/ressources/logs/web/access.log.csv done")





if __name__ == "__main__":
   main(sys.argv[1:])