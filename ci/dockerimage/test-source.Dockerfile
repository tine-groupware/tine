# description:
#   This image is used to run tests in the ci pipeline.
#
# build:
#   $ docker build [...] --build-arg='SOURCE_IMAGE=source-tag' .
#
# ARGS:
#   SOURCE_IMAGE=source

ARG SOURCE_IMAGE=source

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${SOURCE_IMAGE} as test-source

RUN apk add mysql-client jq rsync coreutils

COPY etc /config
COPY phpstan.neon ${TINE20ROOT}/phpstan.neon
COPY phpstan-baseline.neon ${TINE20ROOT}/phpstan-baseline.neon
COPY etc/php/30_opcache.ini /etc/php/conf.d/30_opcache.ini