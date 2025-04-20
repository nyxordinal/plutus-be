# Use the official PHP image with FPM and Alpine
FROM php:7.4-fpm-alpine

# Install required packages and PHP extensions
RUN apk add --no-cache \
    curl \
    bash \
    unzip \
    && docker-php-ext-install pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Set working directory and copy app files
WORKDIR /app
COPY . .

# Install PHP dependencies and prepare environment
RUN cp .env.example .env \
    && composer install --no-dev --optimize-autoloader

# Expose port for PHP built-in server
EXPOSE 8001

# Install New Relic PHP Agent
ARG NEW_RELIC_AGENT_VERSION
ENV NEW_RELIC_AGENT_VERSION=${NEW_RELIC_AGENT_VERSION:-10.12.0.9}

RUN curl -L https://download.newrelic.com/php_agent/archive/${NEW_RELIC_AGENT_VERSION}/newrelic-php5-${NEW_RELIC_AGENT_VERSION}-linux.tar.gz | tar -C /tmp -zx \
    && export NR_INSTALL_USE_CP_NOT_LN=1 \
    && export NR_INSTALL_SILENT=1 \
    && /tmp/newrelic-php5-${NEW_RELIC_AGENT_VERSION}-linux/newrelic-install install \
    && rm -rf /tmp/newrelic-php5-* /tmp/nrinstall*

# Ensure the PHP extension is enabled
RUN echo "extension = newrelic.so" > /usr/local/etc/php/conf.d/newrelic.ini

# Configure New Relic via ENV
ENV NEW_RELIC_LICENSE_KEY=changeme \
    NEW_RELIC_APPNAME=LumenApp \
    NEW_RELIC_DAEMON_ADDRESS=newrelic-daemon:31339

# Inject New Relic config into INI
RUN sed -i \
    -e "s/^newrelic.license.*/newrelic.license = ${NEW_RELIC_LICENSE_KEY}/" \
    -e "s/^newrelic.appname.*/newrelic.appname = ${NEW_RELIC_APPNAME}/" \
    -e "\$a newrelic.daemon.address=${NEW_RELIC_DAEMON_ADDRESS}" \
    /usr/local/etc/php/conf.d/newrelic.ini

# CMD to run Lumen app and queue worker
CMD ["sh", "-c", "php artisan key:generate && php -S 0.0.0.0:8001 -t public & php artisan queue:listen --tries=3"]
