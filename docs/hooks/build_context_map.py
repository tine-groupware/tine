"""
MkDocs Hook: build_context_map.py

Scans rendered HTML for two kinds of context markers and builds
a context-map.json in the site output directory.

── Marker types ────────────────────────────────────────────────────────────────

1. Heading with attr_list  (preferred, anchors to the heading itself)

    ### Anhänge verwalten { #attachments data-ctx="/Addressbook/EditDialog/Contact/AttachmentsGrid" }

   Rendered as:
    <h3 id="attachments" data-ctx="/Addressbook/EditDialog/Contact/AttachmentsGrid">…</h3>

   The explicit id keeps the slug stable and independent of the context path.
   data-ctx carries the full slash-separated context path.

2. Inline anchor  (for mid-paragraph markers without a heading)

    <a id="ctx:Addressbook.EditDialog.Contact.AttachmentsGrid"></a>

   Convention:
   - Prefix:    "ctx:"
   - Separator: "."  (slashes are not valid in HTML ids)
   - The hook reconstructs the slash-separated path automatically.
   - No data-ctx attribute needed — all information is in the id.

── mkdocs.yml ──────────────────────────────────────────────────────────────────

    markdown_extensions:
      - attr_list

    hooks:
      - hooks/build_context_map.py
"""

import re
import json
from pathlib import Path

_context_map: dict[str, str] = {}

# Shared attribute extractors
_CTX_ATTR = re.compile(r'\bdata-ctx="([^"]+)"')
_ID_ATTR  = re.compile(r'\bid="([^"]+)"')

# 1. Any heading tag
_HEADING_TAG = re.compile(r"<h[1-6]\b([^>]*)>", re.IGNORECASE)

# 2. Inline anchor whose id starts with "ctx:"
_INLINE_CTX  = re.compile(r'<a\b[^>]*\bid="(ctx:[^"]+)"', re.IGNORECASE)


def on_page_content(html: str, page, config, **kwargs) -> str:
    """
    Called by MkDocs after each page is rendered to HTML.
    Collects all context markers from headings and inline anchors.
    """
    base_url = config.get("site_url", "").rstrip("/")
    page_url = f"{page.url}"

    # ── 1. Headings with data-ctx (attr_list variant) ──────────────────────
    for match in _HEADING_TAG.finditer(html):
        attrs = match.group(1)

        ctx_match = _CTX_ATTR.search(attrs)
        if not ctx_match:
            continue

        ctx_path = ctx_match.group(1)                   # e.g. "/Addressbook/EditDialog/Contact"
        id_match = _ID_ATTR.search(attrs)
        url      = f"{page_url}#{id_match.group(1)}" if id_match else page_url

        _context_map[ctx_path] = url

    # ── 2. Inline anchors with id="ctx:…" ──────────────────────────────────
    for match in _INLINE_CTX.finditer(html):
        raw      = match.group(1)                       # "ctx:Addressbook.EditDialog.Contact.AttachmentsGrid"
        dotted   = raw[4:]                              # strip "ctx:" → "Addressbook.EditDialog.Contact.AttachmentsGrid"
        ctx_path = "/" + dotted.replace(".", "/")       # → "/Addressbook/EditDialog/Contact/AttachmentsGrid"
        url      = f"{page_url}#{raw}"                  # fragment keeps the original id incl. "ctx:"

        _context_map[ctx_path] = url

    return html


def on_post_build(config, **kwargs) -> None:
    """
    Called by MkDocs once after the full build is complete.
    Writes context-map.json into the site output directory.
    """
    out_path = Path(config["site_dir"]) / "context-map.json"
    out_path.write_text(
        json.dumps(_context_map, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )
    print(f"[context-map] {len(_context_map)} entries written → {out_path}")
