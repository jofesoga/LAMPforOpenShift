# Use Ubuntu base image
FROM ubuntu:22.04

# Set environment variables to avoid interactive prompts
ENV DEBIAN_FRONTEND=noninteractive \
    APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    APACHE_PID_FILE=/var/run/apache2/apache2.pid \
    APACHE_RUN_DIR=/var/run/apache2 \
    APACHE_LOCK_DIR=/var/lock/apache2 \
    APACHE_LOG_DIR=/var/log/apache2

# Set labels for OpenShift
LABEL maintainer="your-email@example.com"
LABEL io.openshift.tags="lamp,apache,php,mysql"
LABEL io.openshift.expose-services="8080:http"

# Install Apache, PHP, MySQL client and other necessary packages
RUN apt-get update && \
    apt-get install -y \
    apache2 \
    php \
    php-mysql \
    php-cli \
    php-json \
    php-common \
    php-zip \
    php-gd \
    php-mbstring \
    php-curl \
    php-xml \
    php-bcmath \
    php-intl \
    php-soap \
    mariadb-client \
    curl \
    wget \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy the startup script
COPY start-apache.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/start-apache.sh

# Change Apache configuration to use custom ports for OpenShift
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:8080>/' /etc/apache2/sites-available/000-default.conf && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Modify Apache environment variables
RUN echo 'export APACHE_RUN_USER=www-data' >> /etc/apache2/envvars && \
    echo 'export APACHE_RUN_GROUP=www-data' >> /etc/apache2/envvars && \
    echo 'export APACHE_PID_FILE=/var/run/apache2/apache2.pid' >> /etc/apache2/envvars && \
    echo 'export APACHE_RUN_DIR=/var/run/apache2' >> /etc/apache2/envvars && \
    echo 'export APACHE_LOCK_DIR=/var/lock/apache2' >> /etc/apache2/envvars && \
    echo 'export APACHE_LOG_DIR=/var/log/apache2' >> /etc/apache2/envvars

# Create necessary directories and set permissions for OpenShift
RUN mkdir -p /var/run/apache2 && \
    mkdir -p /var/lock/apache2 && \
    chgrp -R 0 /var/www /var/run/apache2 /var/lock/apache2 /var/log/apache2 && \
    chmod -R g=u /var/www /var/run/apache2 /var/lock/apache2 /var/log/apache2 && \
    chmod -R a+rwx /var/run/apache2

# Copy a simple PHP info script for testing
COPY index.php /var/www/html/
RUN chown www-data:www-data /var/www/html/index.php && \
    chmod g+rw /var/www/html/index.php

# Expose port 8080 (OpenShift uses random ports, but we need to listen on 8080)
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

# Start Apache in foreground
CMD ["/usr/local/bin/start-apache.sh"]