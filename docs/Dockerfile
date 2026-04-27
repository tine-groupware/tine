FROM squidfunk/mkdocs-material AS base
RUN pip install mkdocs-static-i18n
RUN pip install mkdocs-awesome-pages-plugin
RUN pip install pymdown-extensions
RUN pip install mkdocs-macros-plugin
RUN pip install mkdocs-enumerate-headings-plugin


FROM base AS build
ARG SCREENSHOTS_S3_ACCESS_KEY
ARG SCREENSHOTS_S3_SECRET_KEY
ARG SCREENSHOTS_S3_HOST
ARG SCREENSHOTS_S3_BUCKET
# at the time of writing all version use screenshots from 2024.11
ARG SCREENSHOTS_TINE_VERSION=2024.11

RUN apk add php composer s3cmd
RUN s3cmd --access_key=${SCREENSHOTS_S3_ACCESS_KEY} --secret_key=${SCREENSHOTS_S3_SECRET_KEY} --host ${SCREENSHOTS_S3_HOST} --host-bucket "%(bucket)s.${SCREENSHOTS_S3_HOST}" sync s3://${SCREENSHOTS_S3_BUCKET}/${SCREENSHOTS_TINE_VERSION} /docs/docs/images/
COPY . /docs
RUN cd /docs/tine20/ \
 && composer install --ignore-platform-reqs \
 && vendor/bin/phpdoc-md
RUN sed 's%https://tine-screenshots.s3.rz1.metaways.net/%/images/%g' -i mkdocs.yml
RUN mkdocs build

FROM base AS manual

RUN apk add caddy rsync

COPY --from=build /docs/docs/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY --from=build --chown=1000 /docs/docs /docs/docs
COPY --from=build --chown=1000 /docs/site /docs/site
COPY --from=build --chown=1000 /docs/mkdocs.yml /docs/mkdocs.yml

USER 1000

ENTRYPOINT [ "/usr/local/bin/docker-entrypoint.sh" ]

FROM base AS dev
RUN apk add php composer s3cmd bash jq
RUN mkdir /scripts && \
    echo "Creating /scripts/phpdoc.sh ..." && \
    echo -e "#!/bin/sh\n# only run if tine repo available\nif [ -f /docs/tine20/vendor/bin/phpdoc-md ]; then\n  echo \"INFO    - Building tine php api docs with phpdoc-md\" && cd /docs/tine20/ && vendor/bin/phpdoc-md\nelse\n  echo \"NOTICE   - Could not find vendor/bin/phpdoc-md\"\nfi" > /scripts/phpdoc.sh
RUN chmod +x /scripts/phpdoc.sh

ENTRYPOINT ["sh","-c", "/scripts/phpdoc.sh; mkdocs serve --dev-addr=0.0.0.0:8000"]

