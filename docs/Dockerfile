FROM squidfunk/mkdocs-material
RUN pip install mkdocs-static-i18n
RUN pip install mkdocs-awesome-pages-plugin
RUN pip install pymdown-extensions
RUN pip install mkdocs-macros-plugin

# for phpdoc-md
RUN apk add php composer s3cmd
RUN mkdir /scripts && \
    echo "Creating /scripts/phpdoc.sh ..." && \
    echo -e "#!/bin/sh\n# only run if tine repo available\nif [ -f /docs/tine20/vendor/bin/phpdoc-md ]; then\n  echo \"INFO    - Building tine php api docs with phpdoc-md\" && cd /docs/tine20/ && vendor/bin/phpdoc-md\nelse\n  echo \"NOTICE   - Could not find vendor/bin/phpdoc-md\"\nfi" > /scripts/phpdoc.sh
RUN chmod +x /scripts/phpdoc.sh

ENTRYPOINT ["sh","-c", "/scripts/phpdoc.sh; mkdocs serve --dev-addr=0.0.0.0:8000"]
