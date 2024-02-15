# description:
#   This image is used to run tests in the ci pipeline.
#
# build:
#   $ docker build [...] --build-arg='BASE_IMAGE=base-tag' .
#
# ARGS:
#   BASE_IMAGE=base

ARG BASE_IMAGE=base

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BASE_IMAGE} as test
ARG ALPINE_PHP_PACKAGE=php7

RUN apk add mysql-client jq rsync coreutils git build-base
RUN if [ "${ALPINE_PHP_PACKAGE}" == "php81" ] || [ "${ALPINE_PHP_PACKAGE}" == "php82" ]; then \
        # install composer 2.7.1
        curl https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer \
        | php -- --quiet --install-dir=/usr/bin --filename=composer; \
    else \
      apk add --no-cache composer; \
    fi
RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.12/main/ npm=12.22.12-r0 nodejs=12.22.12-r0
COPY etc /config
COPY phpstan.neon ${TINE20ROOT}/phpstan.neon
COPY phpstan-baseline.neon ${TINE20ROOT}/phpstan-baseline.neon
COPY etc/php/30_opcache.ini /etc/php/conf.d/30_opcache.ini