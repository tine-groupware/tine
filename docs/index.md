# Welcome to MkDocs

For full documentation visit [mkdocs.org](https://www.mkdocs.org).

## create tine/mkdocs image

* `cd tine.git/docs`
* `docker build -t dockerregistry.metaways.net/tine20/tine20/tine/mkdocs:latest-arm64 .`

## quickstart
* `cd tine.git` - change to main src dir (containing mkdocs.yml)
* `docker run --rm -it -p 8000:8000 -v ${PWD}:/docs dockerregistry.metaways.net/tine20/tine20/tine/mkdocs:latest-arm64` - Serve docs using docker
* visit `http://localhost:8000`

## when changing docker image
* `docker image tag tine/mkdocs:latest dockerregistry.metaways.net/tine20/tine20/tine/mkdocs:latest-arm64`
* `docker push dockerregistry.metaways.net/tine20/tine20/tine/mkdocs:latest-arm64`