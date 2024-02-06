# docu / mkdocs

## technical quickstart
The docs setup is included in our default docker dev setup (see `docs` in `pullup.json`)
You just need to navigate your browser to (http://localhost:4030)

To serve the docs stand-alone use
``` sh
// change to main src dir (containing mkdocs.yml)
cd tine.git

// Serve docs using docker
docker run --rm -it -p 8000:8000 -v ${PWD}:/docs dockerregistry.metaways.net/tine20/tine20/mkdocs:latest$(if uname -a | grep -q arm64; then echo "-arm64"; fi)
```
visit http://localhost:8000


## build and push docs
``` sh
cd tine.git
docker run --rm -it -p 8000:8000 -v ${PWD}:/docs dockerregistry.metaways.net/tine20/tine20/mkdocs:latest$(if uname -a | grep -q arm64; then echo "-arm64"; fi) build
s3cmd  --acl-public --delete-removed sync ./site/  s3://tine-docu.s3web.rz1.metaways.net
```

## create our custom mkdocs docker image
``` sh
cd tine.git/docs
docker build -t dockerregistry.metaways.net/tine20/tine20/mkdocs:latest$(if uname -a | grep -q arm64; then echo "-arm64"; fi) .
```

## push to registry
``` sh
docker push dockerregistry.metaways.net/tine20/tine20/mkdocs:latest$(if uname -a | grep -q arm64; then echo "-arm64"; fi)
```

## generate php api docs
``` sh
cd tine.git/tine20
vendor/bin/phpdoc-md
```

