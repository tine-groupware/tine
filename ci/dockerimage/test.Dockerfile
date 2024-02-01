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

RUN apk add mysql-client jq rsync coreutils

COPY etc /config
COPY phpstan.neon ${TINE20ROOT}/phpstan.neon
COPY phpstan-baseline.neon ${TINE20ROOT}/phpstan-baseline.neon
COPY etc/php/30_opcache.ini /etc/php/conf.d/30_opcache.ini