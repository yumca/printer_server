#!/bin/bash
#set -x
pidfile="var/asyncd.pid"
#rootDir=`dirname $0`
rootDir=`pwd`/`dirname $0`
cd $rootDir

function usage() {
    echo "usage: ./asyncd.sh start | stop | restart"
}

function waitStop()
{
    pidcnt=`ps -ef | grep 'php asyncdForMEC.php' | grep -v 'grep' | wc -l`;
    if (( $pidcnt > 0 || $# == 1 )); then
        echo -n "Asyncd is quitting..";
        while [[ 1 -eq 1 ]]; do
            pidcnt=`ps -ef | grep 'php asyncdForMEC.php' | grep -v 'grep' | wc -l`;
            echo -n ".";
            if (( $pidcnt == 0 )); then
                echo "OK"
				return
            fi
            sleep 1
        done
    fi;
	echo "Asyncd is not running"
}

function stop() {
	if [ "`id | grep "uid=0" | wc -l`" == "1" ]; then
		echo "Do not run this script with root user(usually use www user)";	
		exit;
	fi

    if [ -e $pidfile ]; then
        pid=`cat $pidfile`
	else
		pid=`ps ajxwww | grep "php asyncdForMEC.php" | grep -v "grep" | grep "Ss" | awk '{print $2}'`	
	fi
	kill -TERM $pid
	[ $? -eq 0 ] && rm -f $pidfile
	waitStop $pid;
	return
}

function start() {
	if [ "`id | grep "uid=0" | wc -l`" == "1" ]; then
		echo "Do not run this script with root user(usually use www user)";	
		exit;
	fi
    pidcnt=`ps -ef | grep 'php asyncdForMEC.php' | grep -v 'grep' | wc -l`
    if [ $pidcnt -ne 0 ]; then
        if [ -e $pidfile ]; then
                pid=`cat $pidfile`
                echo "Asyncd($pid) is already running"
				return
        fi  
        echo "Asyncd is running without pid file"
		return
    fi
    php asyncdForMEC.php > log/output.msg 2>&1
    [ $? -eq 0 ] && echo "Asyncd started...OK";

}


if [ $# -lt 1 ]
then
        usage
        exit
fi

if [ $1 = 'stop' ]
then
        stop
        exit
elif [ $1 = 'start' ]
then
        start
        exit
elif [ $1 = 'restart' ]; then
        stop
        sleep 1
        start
        exit
fi
