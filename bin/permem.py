#!/usr/bin/env python
import psutil

ram = psutil.virtual_memory()
print ram.percent

