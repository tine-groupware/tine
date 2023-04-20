# Welcome to MkDocs

For full documentation visit [mkdocs.org](https://www.mkdocs.org).

## create tine/mkdocs image

* `cd tine.git/docs`
* `docker build -t dockerregistry.metaways.net/tine20/tine20/mkdocs:latest$(if uname -a | grep -q arm64; then echo "-arm64"; fi) .`

## quickstart
* `cd tine.git` - change to main src dir (containing mkdocs.yml)
* `docker run --rm -it -p 8000:8000 -v ${PWD}:/docs dockerregistry.metaways.net/tine20/tine20/mkdocs:latest$(if uname -a | grep -q arm64; then echo "-arm64"; fi)` - Serve docs using docker
* visit http://localhost:8000

## when changing docker image
* `docker push dockerregistry.metaways.net/tine20/tine20/mkdocs:latest$(if uname -a | grep -q arm64; then echo "-arm64"; fi)`

## generate php api docs
* `cd tine.git/tine20`
* `vendor/bin/phpdoc-md`
