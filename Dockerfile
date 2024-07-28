# Use the official PHP image with FPM and Alpine
FROM php:7.4-fpm-alpine

# Install required packages and PHP extensions
RUN apk add --no-cache \
    && docker-php-ext-install pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Set up application
WORKDIR /app
COPY . .

# Install dependencies and set up environment
RUN cp .env.example .env \
    && composer install --no-dev --optimize-autoloader

# Expose the port
EXPOSE 8001

# Define the CMD command
CMD ["sh", "-c", "php artisan key:generate && php -S 0.0.0.0:8001 -t public & php artisan queue:listen --tries=3"]

# Arguments for New Relic
ARG NEW_RELIC_AGENT_VERSION
ARG NEW_RELIC_LICENSE_KEY
ARG NEW_RELIC_APPNAME

# Download and install New Relic agent
RUN curl -L https://download.newrelic.com/php_agent/archive/${NEW_RELIC_AGENT_VERSION}/newrelic-php5-${NEW_RELIC_AGENT_VERSION}-linux.tar.gz | tar -C /tmp -zx \
    && export NR_INSTALL_USE_CP_NOT_LN=1 \
    && export NR_INSTALL_SILENT=1 \
    && /tmp/newrelic-php5-${NEW_RELIC_AGENT_VERSION}-linux/newrelic-install install \
    && rm -rf /tmp/newrelic-php5-* /tmp/nrinstall*

# Configure New Relic
RUN sed -i \
    -e "s/newrelic.license[[:space:]]*=[[:space:]]*.*/newrelic.license = ${NEW_RELIC_LICENSE_KEY}/" \
    -e "s/newrelic.appname[[:space:]]*=[[:space:]]*.*/newrelic.appname = ${NEW_RELIC_APPNAME}/" \
    -e "\$a newrelic.daemon.address=newrelic-php-daemon:31339" \
    /usr/local/etc/php/conf.d/newrelic.ini
