# Use Ubuntu base image
FROM ubuntu:22.04

# Set environment variables to avoid interactive prompts
ENV DEBIAN_FRONTEND=noninteractive

# Set labels for OpenShift
LABEL maintainer="your-email@example.com"
LABEL io.openshift.tags="lamp,apache,php,mysql"

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

# Create a non-root user for OpenShift security compliance
RUN useradd -u 1001 -r -g 0 -d /var/www/html -s /sbin/nologin \
    -c "Apache User" apacheuser

# Change Apache configuration to use non-root user and custom ports
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:8080>/' /etc/apache2/sites-available/000-default.conf && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Change Apache user/group to match OpenShift requirements
RUN sed -i 's/export APACHE_RUN_USER=www-data/export APACHE_RUN_USER=1001/' /etc/apache2/envvars && \
    sed -i 's/export APACHE_RUN_GROUP=www-data/export APACHE_RUN_GROUP=0/' /etc/apache2/envvars

# Create necessary directories and set permissions
RUN mkdir -p /var/run/apache2 && \
    mkdir -p /var/lock/apache2 && \
    chown -R 1001:0 /var/www/html /var/run/apache2 /var/lock/apache2 /var/log/apache2 && \
    chmod -R g+rw /var/www/html /var/run/apache2 /var/lock/apache2 /var/log/apache2

# Copy a simple PHP info script for testing
COPY index.php /var/www/html/
RUN chown 1001:0 /var/www/html/index.php && \
    chmod g+rw /var/www/html/index.php

# Expose port 8080 (OpenShift uses random ports, but we need to listen on 8080)
EXPOSE 8080

# Switch to non-root user
USER 1001

# Health check
HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

# Start Apache in foreground
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]