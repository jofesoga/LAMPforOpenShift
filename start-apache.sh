#!/bin/bash

# Get the user ID from OpenShift
USER_ID=$(id -u)

# If we're running as a random user, change www-data to have the same UID
if [ $USER_ID -ne 0 ]; then
    usermod -u $USER_ID www-data
    groupmod -g 0 www-data
fi

# Fix permissions for OpenShift
chgrp -R 0 /var/www /var/run/apache2 /var/lock/apache2 /var/log/apache2
chmod -R g=u /var/www /var/run/apache2 /var/lock/apache2 /var/log/apache2
chmod -R a+rwx /var/run/apache2

# Start Apache
exec /usr/sbin/apache2ctl -D FOREGROUND