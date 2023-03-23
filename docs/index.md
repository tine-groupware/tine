# Welcome to MkDocs

For full documentation visit [mkdocs.org](https://www.mkdocs.org).

## create tine/mkdocs image

* `cd tine.git/docs`
* `docker build -t tine/mkdocs:latest .`

## quickstart
* `cd tine.git` - change to main src dir (containing mkdocs.yml)
* `docker run --rm -it -p 8000:8000 -v ${PWD}:/docs tine/mkdocs` - Serve docs using docker
* visit `http://localhost:8000`
