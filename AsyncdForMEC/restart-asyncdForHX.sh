ps -ef|grep asyncdForMEC.php| grep -v grep | awk '{print $2}' | xargs kill -9
sh ./asyncd.sh start
