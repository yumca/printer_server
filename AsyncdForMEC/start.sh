#!/bin/bash
/usr/local/php7.1.16/bin/php /home/www/print/asyncdForMEC/asyncdForMEC.php > /home/www/print/asyncdForMEC/log/output.msg 2>&1
cat /home/www/print/asyncdForMEC/log/output.msg
echo "start asyncdForMEC ok"
