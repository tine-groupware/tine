# description:
#   This image is used to run test in the ci.
#
# build:
#   $ docker build [...] --build-arg='BUILT_IMAGE=built-tag' --build-arg='SOURCE_IMAGE=source-tag' .
#
# ARGS:
#   BUILT_IMAGE=built
#   SOURCE_IMAGE=source
#   NPM_INSTALL_COMMAND="npm --no-optional install" - used set npm proxy in the ci

ARG SOURCE_IMAGE=source
ARG BUILT_IMAGE=built

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${SOURCE_IMAGE} as source-copy
# COPY --from can not use build args

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${BUILT_IMAGE} as test-built
ARG TINE20ROOT=/usr/share
ARG ALPINE_PHP_PACKAGE=php7

RUN apk add --update --no-cache git mysql-client jq rsync build-base
RUN if [ "${ALPINE_PHP_PACKAGE}" == "php81" ] || [ "${ALPINE_PHP_PACKAGE}" == "php82" ] || [ "${ALPINE_PHP_PACKAGE}" == "php83" ]; then \
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

COPY etc /config

COPY --from=source-copy ${TINE20ROOT}/tests ${TINE20ROOT}/tests
COPY --from=source-copy ${TINE20ROOT}/scripts ${TINE20ROOT}/scripts
COPY --from=source-copy ${TINE20ROOT}/.git ${TINE20ROOT}/.git
COPY --from=source-copy ${TINE20ROOT}/tine20/vendor ${TINE20ROOT}/tine20/vendor
COPY --from=source-copy ${TINE20ROOT}/tine20/library/ExtJS/src/locale ${TINE20ROOT}/tine20/library/ExtJS/src/locale
COPY --from=source-copy ${TINE20ROOT}/tine20/composer.json ${TINE20ROOT}/tine20/composer.json
COPY --from=source-copy ${TINE20ROOT}/tine20/composer.lock ${TINE20ROOT}/tine20/composer.lock
COPY --from=source-copy ${TINE20ROOT}/tine20/Tinebase/js/Locale/static ${TINE20ROOT}/tine20/Tinebase/js/Locale/static

RUN if [ "COMPOSER_LOCK_REWRITE" == "true" ]; then \
        php ${TINE20ROOT}/scripts/packaging/composer/composerLockRewrite.php ${TINE20ROOT}/tine20/composer.lock satis.default.svc.cluster.local; \
    fi
RUN cd ${TINE20ROOT}/tine20 && composer install --no-ansi --no-progress --no-suggest