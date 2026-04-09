#!/bin/sh

if [ -d /custom ]; then
    rsync -rv /custom/ /docs/docs/
    mkdocs build
fi

caddy file-server --root /docs/site --listen :8080