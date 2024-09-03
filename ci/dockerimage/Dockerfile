ARG PHP_IMAGE=php8.1
ARG BASE_IMAGE=base
ARG DEPENDENCY_IMAGE=dependency
ARG SOURCE_IMAGE=source
ARG JSDEPENDENCY_IMAGE=jsdependency
ARG JSBUILD_IMAGE=jsbuild
ARG BUILD_IMAGE=build
ARG BUILT_IMAGE=built
ARG PACKAGING_IMAGE=packaging




FROM docker.io/library/ubuntu:jammy as php8.1
RUN apt-get update \
 && DEBIAN_FRONTEND=noninteractive TZ=Etc/UTC apt-get install -y \
    php \
    php-apcu \
    php-bcmath \
    php-curl \
    php-fpm \
    php-gd \
    php-igbinary \
    php-imagick \
    php-intl \
    php-ldap \
    php-mbstring \
    php-mysql \
    php-phpdbg \
    php-redis \
    php-soap \
    php-xml \
    php-yaml \
    php-zip \
 && rm -rf /var/lib/apt/lists/*
RUN ln -s /etc/php/8.1 /etc/php/default
RUN ln -s /usr/sbin/php-fpm8.1 /usr/sbin/php-fpm




FROM docker.io/library/ubuntu:noble as php8.2
RUN apt-get update \
 && apt-get install -y software-properties-common \
 && add-apt-repository -y ppa:ondrej/php \
 && DEBIAN_FRONTEND=noninteractive TZ=Etc/UTC apt-get install -y \
    php8.2 \
    php8.2-apcu \
    php8.2-bcmath \
    php8.2-curl \
    php8.2-fpm \
    php8.2-gd \
    php8.2-igbinary \
    php8.2-imagick \
    php8.2-intl \
    php8.2-ldap \
    php8.2-mbstring \
    php8.2-mysql \
    php8.2-phpdbg \
    php8.2-redis \
    php8.2-soap \
    php8.2-xml \
    php8.2-yaml \
    php8.2-zip \
 && rm -rf /var/lib/apt/lists/*
RUN ln -s /etc/php/8.2 /etc/php/default
RUN ln -s /usr/sbin/php-fpm8.2 /usr/sbin/php-fpm




FROM docker.io/library/ubuntu:noble as php8.3
RUN apt-get update \
 && DEBIAN_FRONTEND=noninteractive TZ=Etc/UTC apt-get install -y \
    php \
    php-apcu \
    php-bcmath \
    php-curl \
    php-fpm \
    php-gd \
    php-igbinary \
    php-imagick \
    php-intl \
    php-ldap \
    php-mbstring \
    php-mysql \
    php-phpdbg \
    php-redis \
    php-soap \
    php-xml \
    php-yaml \
    php-zip \
 && rm -rf /var/lib/apt/lists/*
RUN ln -s /etc/php/8.3 /etc/php/default
RUN ln -s /usr/sbin/php-fpm8.3 /usr/sbin/php-fpm




FROM hairyhenderson/gomplate:stable-alpine as gomplate

FROM ${PHP_IMAGE} as base
ARG TINE20ROOT=/usr/share
ENV TINE20ROOT=$TINE20ROOT
# todo: install nginx brotli
RUN apt-get update \
 && apt-get install -y \
    busybox \
    default-jre \
    gettext \
    jq \
    mysql-client \
    netcat-openbsd \
    nginx \
    supervisor \
    tnef \
    gnupg \
 && rm -rf /var/lib/apt/lists/*
COPY --from=gomplate /bin/gomplate /usr/bin/gomplate
RUN mkdir /usr/local/busybox \
 && busybox --install /usr/local/busybox
ENV PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/local/busybox
# todo: fix tika download url
RUN wget -O /usr/local/bin/tika.jar https://packages.tine20.com/maintenance/tika/tika-app-2.6.0.jar
RUN addgroup --system -gid 150 tine20 && \
    adduser --system --no-create-home --disabled-password --shell /bin/bash --gecos "tine20 user" --ingroup tine20 --uid 150 tine20 && \
    mkdir -p /etc/tine20/conf.d && \
    mkdir -p /etc/gomplate && \
    mkdir -p /etc/supervisor.d && \
    mkdir -p /var/log/tine20 && \
    mkdir -p /var/lib/tine20/files && \
    mkdir -p /var/lib/tine20/tmp && \
    mkdir -p /var/lib/tine20/caching && \
    mkdir -p /var/lib/tine20/sessions && \
    mkdir -p /var/run/tine20 && \
    mkdir -p /run/nginx && \
    # todo: use sites-enabled instead of http. # rm -r /etc/nginx/http.d && \
    rm /etc/nginx/nginx.conf && \
    mkdir -p /etc/nginx/conf.d/ && \
    mkdir -p /etc/nginx/http.d/ && \
    mkdir -p /etc/nginx/snippets/ && \
    mkdir -p ${TINE20ROOT}/tine20 && \
    touch /var/log/tine20/tine20.log && \
    chown tine20:tine20 /var/log/tine20 && \
    chown tine20:tine20 /var/lib/tine20/files && \
    chown tine20:tine20 /var/lib/tine20/caching && \
    chown tine20:tine20 /var/lib/tine20/sessions && \
    chown tine20:tine20 /var/lib/tine20/tmp && \
    chown tine20:tine20 /var/lib/nginx && \
    chown tine20:tine20 /var/log/tine20/tine20.log
COPY ci/dockerimage/gomplate/config.yaml /etc/gomplate/config.yaml
COPY ci/dockerimage/gomplate/templates/ /etc/gomplate/templates
COPY etc/tine20/config.inc.php.mpl /etc/gomplate/templates/config.inc.php.tmpl
COPY etc/nginx/sites-available/tine20.conf.mpl /etc/gomplate/templates/nginx-vhost.conf.tmpl
COPY etc/tine20/actionQueue.ini.tmpl /etc/gomplate/templates/actionQueue.ini.tmpl
COPY etc/tine20/actionQueueLR.ini /etc/tine20/actionQueueLR.ini
COPY etc/nginx/conf.d/ /etc/nginx/conf.d
COPY etc/nginx/snippets /etc/nginx/snippets
COPY ci/dockerimage/supervisor.d/supervisord.conf /etc/supervisor/supervisord.conf
COPY ci/dockerimage/supervisor.d/nginx.ini /etc/supervisor/conf.d/
COPY ci/dockerimage/supervisor.d/crond.ini /etc/supervisor/conf.d/
COPY ci/dockerimage/supervisor.d/php-fpm.ini /etc/supervisor/conf.d/
COPY ci/dockerimage/supervisor.d/worker.ini /etc/supervisor/conf.d/
COPY ci/dockerimage/supervisor.d/workerLR.ini /etc/supervisord/conf.d/
COPY ci/dockerimage/scripts/* /usr/local/bin/
WORKDIR ${TINE20ROOT}
ENV TINE20ROOT=${TINE20ROOT}
ENV TINE20_ACTIONQUEUE=true
ENV TINE20_ACTIONQUEUE_LONG_RUNNING_QUEUE=false
CMD ["/usr/local/bin/entrypoint"]




FROM ${BASE_IMAGE} as test
# todo: install coreutils and build-base
RUN apt-get update \
 && apt-get install -y \
    rsync \
    composer \
    npm \
    nodejs \
 && rm -rf /var/lib/apt/lists/*
COPY etc /config
COPY phpstan.neon ${TINE20ROOT}/phpstan.neon
COPY phpstan-baseline.neon ${TINE20ROOT}/phpstan-baseline.neon
COPY etc/php/30_opcache.ini /etc/php/conf.d/30_opcache.ini




FROM ${BASE_IMAGE} as dev
RUN apt-get update \
 && apt-get install -y \
    composer \
    php-xdebug \
 && rm -rf /var/lib/apt/lists/*




FROM ${BASE_IMAGE} as dependency
# todo: install coreutils and build-base
RUN apt-get update \
 && apt-get install -y \
    composer \
 && rm -rf /var/lib/apt/lists/*
# used to inject http auth credentials for git repos
COPY ci/dockerimage/utility/.gitconfig /root/.gitconfig
COPY tine20/library ${TINE20ROOT}/tine20/library
COPY tine20/composer.json ${TINE20ROOT}/tine20/composer.json
COPY tine20/composer.lock ${TINE20ROOT}/tine20/composer.lock
RUN cd ${TINE20ROOT}/tine20 && composer install --no-scripts --no-ansi --no-progress --no-suggest




FROM node:18.9.0-alpine as jsdependency
ARG TINE20ROOT=/usr/share
RUN apk add git
COPY tine20/Tinebase/js/package.json ${TINE20ROOT}/tine20/Tinebase/js/package.json
COPY tine20/Tinebase/js/npm-shrinkwrap.json ${TINE20ROOT}/tine20/Tinebase/js/npm-shrinkwrap.json
WORKDIR ${TINE20ROOT}/tine20/Tinebase/js
RUN npm --prefix ${TINE20ROOT}/tine20/Tinebase/js/ install --no-optional --ignore-scripts

FROM ${JSDEPENDENCY_IMAGE} AS jsdependency-copy




FROM ${DEPENDENCY_IMAGE} as icon-set-provider
# .git changes with every commit and broke caching form source downwards. So it needs to be excluded and only the result is used
COPY .git ${TINE20ROOT}/.git
RUN git submodule update --init

FROM ${DEPENDENCY_IMAGE} as source
COPY tine20 ${TINE20ROOT}/tine20/
COPY tests ${TINE20ROOT}/tests/
COPY scripts ${TINE20ROOT}/scripts/
COPY .git ${TINE20ROOT}/.git
RUN cd ${TINE20ROOT}/tine20 && composer install --no-ansi --no-progress --no-suggest
COPY --from=icon-set-provider ${TINE20ROOT}/tine20/images/icon-set/ ${TINE20ROOT}/tine20/images/icon-set

FROM ${SOURCE_IMAGE} AS source-copy




FROM node:18.9.0-alpine as jsbuild
ARG TINE20ROOT=/usr/share
ARG REVISION=local
WORKDIR ${TINE20ROOT}/tine20/Tinebase/js
COPY --from=jsdependency-copy ${TINE20ROOT}/tine20/Tinebase/js/node_modules ${TINE20ROOT}/tine20/Tinebase/js/node_modules
COPY --from=source-copy ${TINE20ROOT}/tine20 ${TINE20ROOT}/tine20
RUN export BUILD_DATE=$(date -u "+%F %T") && BUILD_REVISION=${REVISION} node --max_old_space_size=4096 ./node_modules/webpack/bin/webpack.js --progress --config webpack.docker.js

FROM ${JSBUILD_IMAGE} as jsbuild-copy




FROM ${SOURCE_IMAGE} as build
ARG RELEASE=local
ARG CODENAME=local
ARG REVISION=local
ARG RELEASE_TYPE=""
COPY ci/dockerimage/build/build_script.sh /build_script.sh
RUN rm -rf "${TINE20ROOT}/tine20/ExampleApplication"
RUN if [ "$RELEASE_TYPE" == be ]; then \
        rm -f "${TINE20ROOT}/tine20/Tinebase/License/cacert.pem"; \
        rm -f "${TINE20ROOT}/tine20/Tinebase/License/cacert20240311.pem"; \
    fi
RUN bash -c "source /build_script.sh && activateReleaseMode"
RUN bash -c "source /build_script.sh && buildLangStats"
RUN bash -c "source /build_script.sh && cleanupJs"
RUN bash -c "source /build_script.sh && buildTranslations"
RUN bash -c "source /build_script.sh && removeComposerDevDependencies"
RUN bash -c "source /build_script.sh && moveCustomapps"
COPY --from=jsbuild-copy /out/ ${TINE20ROOT}/
RUN bash -c "source /build_script.sh && cleanup"
RUN bash -c "source /build_script.sh && fixFilePermissions"

FROM ${BUILD_IMAGE} as build-copy




FROM ${BASE_IMAGE} as built
COPY --from=build-copy ${TINE20ROOT}/tine20 ${TINE20ROOT}/tine20
COPY etc/crontabs/tine20 /etc/crontabs/tine20
HEALTHCHECK --timeout=120s CMD curl --silent --fail http://127.0.0.1:80/health




FROM ${BUILT_IMAGE} as test-built
ARG TINE20ROOT=/usr/share
ARG ALPINE_PHP_PACKAGE=php7
# todo: install coreutils and build-base
RUN apt-get update \
 && apt-get install -y \
    rsync \
    composer \
    npm \
    nodejs \
 && rm -rf /var/lib/apt/lists/*
COPY etc /config
COPY --from=source-copy ${TINE20ROOT}/tests ${TINE20ROOT}/tests
COPY --from=source-copy ${TINE20ROOT}/scripts ${TINE20ROOT}/scripts
COPY --from=source-copy ${TINE20ROOT}/.git ${TINE20ROOT}/.git
COPY --from=source-copy ${TINE20ROOT}/tine20/vendor ${TINE20ROOT}/tine20/vendor
COPY --from=source-copy ${TINE20ROOT}/tine20/library/ExtJS/src/locale ${TINE20ROOT}/tine20/library/ExtJS/src/locale
COPY --from=source-copy ${TINE20ROOT}/tine20/composer.json ${TINE20ROOT}/tine20/composer.json
COPY --from=source-copy ${TINE20ROOT}/tine20/composer.lock ${TINE20ROOT}/tine20/composer.lock
COPY --from=source-copy ${TINE20ROOT}/tine20/Tinebase/js/Locale/static ${TINE20ROOT}/tine20/Tinebase/js/Locale/static
RUN cd ${TINE20ROOT}/tine20 && composer install --no-ansi --no-progress --no-suggest




FROM ${BUILT_IMAGE} as packaging
ARG TINE20PACKAGES=/root/packages
ARG RELEASE=local
ARG CODENAME=local
ARG REVISION=local
ARG ZIP_PACKAGES="false"
COPY ci/dockerimage/build/build_script.sh /build_script.sh
RUN bash -c "source /build_script.sh && createArchives"
RUN bash -c "source /build_script.sh && createSpecialArchives"
RUN bash -c "source /build_script.sh && packageTranslations"
RUN bash -c "source /build_script.sh && buildChecksum"

FROM ${PACKAGING_IMAGE} as packaging-copy




FROM scratch as packages
ARG TINE20PACKAGES=/root/packages/source
COPY --from=packaging-copy ${TINE20PACKAGES} /
