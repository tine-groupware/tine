# description:
#
#   First stage of the tine20 built image. It is splite in to files, to be able to use cashing in the ci
#   This stage builds tine20. And cleans all unnessery files from ${TINE20ROOT}/tine20.
#
# build:
#   $ docker build [...] --build-arg='SOURCE_IMAGE=source-tag' .
#
# ARGS:
#   SOURCE_IMAGE=source
#   todo comment vars
#   RELEASE=local -
#   CODENAME=local -
#   REVISION=local -

ARG SOURCE_IMAGE=source
ARG JSBUILD_IMAGE=jsbuild

FROM ${JSBUILD_IMAGE} as jsbuild-copy

#  -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -    -
FROM ${SOURCE_IMAGE} as build
ARG RELEASE=local
ARG CODENAME=local
ARG REVISION=local

COPY ci/dockerimage/build/build_script.sh /build_script.sh

RUN rm -rf "${TINE20ROOT}/tine20/ExampleApplication"
RUN rm -f "${TINE20ROOT}/tine20/Tinebase/License/cacert.pem"
RUN rm -f "${TINE20ROOT}/tine20/Tinebase/License/cacert20240311.pem"
RUN bash -c "source /build_script.sh && activateReleaseMode"
RUN bash -c "source /build_script.sh && buildLangStats"
RUN bash -c "source /build_script.sh && cleanupJs"
RUN bash -c "source /build_script.sh && buildTranslations"
RUN bash -c "source /build_script.sh && removeComposerDevDependencies"
RUN bash -c "source /build_script.sh && moveCustomapps"
COPY --from=jsbuild-copy /out/ ${TINE20ROOT}/
RUN bash -c "source /build_script.sh && cleanup"
RUN bash -c "source /build_script.sh && fixFilePermissions"