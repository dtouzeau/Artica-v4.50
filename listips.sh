#!/bin/bash
/bin/grep -Po "\d+\.\d+\.\d+\.\d+" /proc/net/fib_trie | /bin/grep -Pv "\.(0|255)$" | /bin/grep -v "127.0.0.1" | sort | uniq
