# description:
#   This image is used to run tests in the ci pipeline.
#
# build:
#   $ docker build [...] --build-arg='SOURCE_IMAGE=source-tag' .
#
# ARGS:
#   SOURCE_IMAGE=source
#   TINE20ROOT=/usr/share

ARG DEPENDENCY_IMAGE=dependency

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${DEPENDENCY_IMAGE} as test-dependency
ARG TINE20ROOT=/usr/share

RUN apk add mysql-client jq rsync

COPY etc /config
COPY phpstan.neon ${TINE20ROOT}/phpstan.neon
COPY phpstan-baseline.neon ${TINE20ROOT}/phpstan-baseline.neon
