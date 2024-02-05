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
        EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"; \
        php -r "copy('https://getcomposer.org/installer', '/composer-setup.php');"; \
        ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', '/composer-setup.php');")"; \
        if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then \
            >&2 echo 'ERROR: Invalid installer checksum'; \
            rm /composer-setup.php; \
            exit 1; \
        fi; \
        php /composer-setup.php --install-dir=/usr/bin --filename=composer; \
        RESULT=$?; \
        rm /composer-setup.php; \
        exit $RESULT; \
    else \
      apk add --no-cache composer; \
    fi
RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.12/main/ npm=12.22.12-r0 nodejs=12.22.12-r0
COPY etc /config
COPY phpstan.neon ${TINE20ROOT}/phpstan.neon
COPY phpstan-baseline.neon ${TINE20ROOT}/phpstan-baseline.neon
