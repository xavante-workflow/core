FROM alpine:3.20
# Set the maintainer label:
LABEL maintainer="Eduardo Luz <eduardo.luz@eduardo-luz.com>"

# Set working directory:
WORKDIR /app

RUN apk update \
    && apk upgrade \
    && apk add  oniguruma-dev curl gcc make  \
       g++ zlib-dev git autoconf automake gawk build-base \
       php82-cli php82-dev php82-phar php82-xml \
       php82-iconv php82-soap php82-simplexml php82-xmlwriter \
       php82-tokenizer php82-dom php82-fileinfo php82-mbstring \
        php82-json  php82-curl \
       php82-ctype php82-openssl \
       php82-sodium libsodium libsodium-dev \
    && rm -rf /var/cache/apk/* \
    && rm -rf /tmp/*

RUN sed -i 's/variables_order = "GPCS"/variables_order = "EGPCS"/g' /etc/php82/php.ini
RUN ln -s /usr/bin/php82 /usr/bin/php

# Create apache user home directory:
RUN mkdir -p /app

# Copy application files:
ADD . /app
RUN chmod 755 /app/docker/run.sh

# Install Composer:
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer 

# Set Composer environment variables for better temp space handling
ENV COMPOSER_HOME=/tmp/composer
ENV COMPOSER_CACHE_DIR=/tmp/composer/cache

RUN composer install --no-scripts --no-dev --optimize-autoloader \
    && rm -rf /tmp/composer/cache/* \
    && rm -rf /root/.composer/cache/*


# Run as user as apache:
USER 1000

CMD ["sh","/app/docker/run.sh"]    