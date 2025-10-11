#!/usr/bin/python  
#apt-get install python-imaging  
import os, glob, sys  
from PIL import Image  
  
def get_skin_ratio(im):  
    im = im.crop((int(im.size[0]*0.2), int(im.size[1]*0.2), im.size[0]-int(im.size[0]*0.2), im.size[1]-int(im.size[1]*0.2)))  
    skin = sum([count for count, rgb in im.getcolors(im.size[0]*im.size[1]) if rgb[0]>60 and rgb[1]<(rgb[0]*0.85) and rgb[2]<(rgb[0]*0.7) and rgb[1]>(rgb[0]*0.4) and rgb[2]>(rgb[0]*0.2)])  
    return float(skin)/float(im.size[0]*im.size[1])  
  
skin_percent = get_skin_ratio(Image.open(sys.argv[1])) * 100  
print "%d" % skin_percent   
