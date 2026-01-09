#!/bin/bash

# http
php -S 127.0.0.1:80 &
PID1=$!

# socket
php -S socket/server.php &
PID2=$!

echo "NONHOST SERVERS :"
echo "- HTTP : http://127.0.0.1:80"
echo "- SOCKET : http://0.0.0.0:50058"
echo "PIDs : $PID1 $PID2"

wait $PID1 $PID2
