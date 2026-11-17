# docu / mkdocs

## technical quickstart
The docs setup is included in our default docker dev setup (see `docs` in `pullup.json`)
You just need to navigate your browser to https://docs.local.tine-dev.de/ (live reload on changes)

To serve the docs stand-alone use
``` sh
// change to main src dir (containing mkdocs.yml)
cd tine.git

// Serve docs using docker
docker run --rm -it -p 8000:8000 -v ${PWD}:/docs dockerregistry.metaways.net/tine20/tine20/mkdocs:latest$(if uname -a | grep -q arm64; then echo "-arm64"; fi)
```
visit http://localhost:8000


## diagrams
Draw diagrams with [Mermaid](https://mermaid.js.org/) in a `mermaid` code block, not as images: the source
stays text (readable diffs, editable without a drawing tool), and GitLab/GitHub render it as well. Write them in
English like the rest of the docs. Example (see [AI coding](aicoding.md)):

````markdown
``` mermaid
flowchart LR
    client["AI coding client"] -- MCP --> devctl
    devctl -- docker socket --> daemon["Docker daemon"]
```
````

The docs must not load anything from external servers: mermaid is self-hosted in `docs/assets/vendor/mermaid`
(see `README.txt` there for updates) and included via `extra_javascript` in `mkdocs.yml` - without it the
Material theme would load mermaid from unpkg.

Try diagrams in the [Mermaid live editor](https://mermaid.live/).

## no external requests
The docs must not load anything from external servers (privacy). Everything the theme would fetch is self-hosted:

* mermaid: `docs/assets/vendor/mermaid` (`extra_javascript`)
* fonts: `docs/assets/vendor/fonts` (`extra_css`) with `theme.font: false` - Material loads them from Google Fonts
  otherwise
* no `repo_url`: Material fetches repo facts from api.github.com in the browser; the repo is linked in the footer
  (`extra.social`)

The Material privacy plugin, which would do this automatically, doesn't work with our setup: it ignores
`assets_include`/`assets_exclude` (downloads all screenshots) and breaks the screenshot links, probably together with
the i18n plugin. Known exception: the manual screenshots are loaded from our s3 (`extra.img_url_*`).

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

