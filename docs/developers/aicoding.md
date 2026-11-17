# AI coding

AI coding clients (e.g. Claude Code) run as containers of tine-dev without docker access: they control the
stack via the MCP server devctl, which offers a fixed set of operations (change branch, start containers, run
tests, ...). See the README of tine-dev for details.

``` mermaid
---
title: The client reaches docker only via the fixed tools of devctl
---
flowchart TB
    client["<b>Claude Code (client)</b><br/>no docker access<br/>can't see tine-dev"]
    devctl["<b>devctl</b><br/>MCP server, fixed tools<br/>runs ./console (tine-dev)"]
    daemon["<b>Docker daemon</b><br/>starts containers<br/>subpath without symlinks"]

    subgraph stack["Stack containers: run tine code"]
        direction LR
        web[web]
        webpack[webpack]
        e2etests[e2etests]
    end

    checkout["<b>tine checkout (volume tine20-src)</b><br/>client: writable, but .git config, hooks, commondir read-only<br/>stack: subpath mounts only, no symlinks"]

    client -- MCP --> devctl
    devctl -- socket --> daemon
    daemon -- creates --> stack
    client -- writes code --> checkout
    stack -- subpath mounts only --> checkout

    classDef accent fill:#2f6fd11f,stroke:#2f6fd1,stroke-width:2px
    class devctl accent
```
