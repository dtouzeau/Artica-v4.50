#!/usr/bin/env python
import requests,argparse,sys
import os.path
from pprint import pprint
from requests.packages.urllib3.exceptions import InsecureRequestWarning




parser = argparse.ArgumentParser(description='Artica Patcher client')
parser.add_argument('--key',default="AAAA-BBB-CCC-DDD",help='API KEY (default: AAAA-BBB-CCC-DDD)')
parser.add_argument('--filename',default="",help='Path of the patch package (in tgz format)')
parser.add_argument('--hostname',default="localhost",help='Address of the Artica server (default localhost)')
parser.add_argument('--port',default=9000,help='Port of the target Artica server (default:9000)')
MyParser = parser.parse_args()



if len(MyParser.key)==0:
    print("Please, the API KEY or type -h for help")
    sys.exit(1)

if len(MyParser.filename)==0:
    print("Please, provide package path or type -h for help")
    sys.exit(1)



if not os.path.exists(MyParser.filename):
    print("The patch package '"+MyParser.filename+"' does not exists")
    sys.exit(1)

basename = os.path.basename(MyParser.filename)
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)
uri="https://"+MyParser.hostname+":"+str(MyParser.port)+"/api/rest/system/"

print("Send To "+uri)

headers = {'ArticaKey': MyParser.key}
payload = {'patch-artica': 'yes'}

files = {"file": (basename, open(MyParser.filename, 'rb'), 'multipart/form-data')}
resp = requests.post(uri, files=files,data=payload,verify=False,headers=headers)

ReturnCode=resp.status_code



if ReturnCode>200:
    try:
        print("Request failed with status code " + str(ReturnCode))
        jsonResponse = resp.json()
        print("Returned result:")
        for key in jsonResponse:
            print("\t" + key + " -> " + str(jsonResponse[key]))

        sys.exit(0)
    except ValueError:
        if resp.text:
            print(resp.text)
            sys.exit(0)







jsonResponse = resp.json()
print("Returned result:")
for key in jsonResponse:
    print("\t" + key+" -> " +str(jsonResponse[key]))




sys.exit(0)











