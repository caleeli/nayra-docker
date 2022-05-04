FROM dockette/debian:bullseye

LABEL maintainer="david.callizaya@processmaker.com"

# PHP
ENV PHP_MODS_DIR=/etc/php/8.1/mods-available
ENV PHP_CLI_DIR=/etc/php/8.1/cli
ENV PHP_CLI_CONF_DIR=${PHP_CLI_DIR}/conf.d
ENV PHP_CGI_DIR=/etc/php/8.1/cgi
ENV PHP_CGI_CONF_DIR=${PHP_CGI_DIR}/conf.d
ENV TZ=America/La_Paz

# INSTALLATION
RUN apt update && apt dist-upgrade -y && \
    # DEPENDENCIES #############################################################
    apt install -y wget curl apt-transport-https ca-certificates git unzip && \
    # PHP DEB.SURY.CZ ##########################################################
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ buster main" > /etc/apt/sources.list.d/php.list && \
    apt update && \
    apt install -y --no-install-recommends \
        php8.1-apc \
        php8.1-apcu \
        php8.1-bcmath \
        php8.1-bz2 \
        php8.1-calendar \
        php8.1-cgi \
        php8.1-cli \
        php8.1-ctype \
        php8.1-curl \
        php8.1-gettext \
        php8.1-imap \
        php8.1-ldap \
        php8.1-mbstring \
        php8.1-memcached \
        php8.1-mongo \
        php8.1-mysql \
        php8.1-pdo \
        php8.1-pgsql \
        php8.1-redis \
        php8.1-soap \
        php8.1-sqlite3 \
        php8.1-zip \
        php8.1-xmlrpc \
        php8.1-xsl && \
    # COMPOSER #################################################################
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --2 && \
    # PHP MOD(s) ###############################################################
    ln -s ${PHP_MODS_DIR}/custom.ini ${PHP_CLI_CONF_DIR}/999-custom.ini && \
    ln -s ${PHP_MODS_DIR}/custom.ini ${PHP_CGI_CONF_DIR}/999-custom.ini && \
    ## # INSTALL node, npm and bpmn-to-image
    ## curl -sL https://deb.nodesource.com/setup_16.x | bash - && \
    ## apt-get install -y nodejs && \
    ## npm install -g bpmn-to-image && \
    # CLEAN UP #################################################################
    apt-get clean -y && \
    apt-get autoclean -y && \
    apt-get remove -y wget curl && \
    apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/lib/log/* /tmp/* /var/tmp/*

# FILES (it overrides originals)
ADD conf.d/custom.ini ${PHP_MODS_DIR}/custom.ini


# Add application files
ADD start.php /srv/start.php
ADD composer.lock /srv/composer.lock
ADD welcome.php /srv/welcome.php
ADD src /srv/src
ADD vendor /srv/vendor
ADD bpmn /srv/bpmn

# WORKDIR
WORKDIR /srv

# Expose container port 3000
EXPOSE 3000

# COMMAND
CMD ["php", "start.php", "start"]