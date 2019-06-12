#!/bin/bash
ps -ef|grep asyncdForMEC.php| grep -v grep | awk '{print $2}' | xargs kill -9
echo "stop asyncdForMEC ok"
