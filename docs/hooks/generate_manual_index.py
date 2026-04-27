"""
MkDocs Hook: generate_manual_index.py

1. Overrides extra.icon_url to serve icons locally from assets/icons/.
2. Scans all docs for {{icon_url}}xxx.svg references and copies those
   SVGs from tine20/images/icon-set/ to docs/assets/icons/.
3. Generates docs/users/manual/index.md with a 3-column tile grid.
   Icons come from <AppName>IconCls in tine20/<App>/styles/*.scss,
   or from an explicit <!-- icon: name.svg --> comment in the page.
"""

import re
import logging
from pathlib import Path
from urllib.parse import urlparse

log = logging.getLogger('mkdocs.hooks.generate_manual_index')

_TITLE_RE    = re.compile(r'^#\s+(.+?)(?:\s*\{[^}]*\})?\s*$', re.MULTILINE)
_CTX_RE      = re.compile(r'data-ctx="/([^/"]+)')
_ICON_RE     = re.compile(r'<!--\s*icon:\s*(\S+)\s*-->')
_ICON_URL_RE = re.compile(r'\{\{icon_url\}\}(\S+\.svg)')
_URL_RE      = re.compile(r'background-image\s*:\s*url\(([^)]+)\)')


def on_config(config):
    base_path = urlparse(config.get('site_url', '')).path.rstrip('/')
    config['extra']['icon_url'] = f'{base_path}/assets/icons/'
    return config


def on_pre_build(config) -> None:
    docs_dir    = Path(config['docs_dir'])
    tine20_dir  = docs_dir.parent / 'tine20'
    manual_dir  = docs_dir / 'users' / 'manual'
    icons_dir   = docs_dir / 'assets' / 'icons'
    icons_dir.mkdir(parents=True, exist_ok=True)
    base_path   = urlparse(config.get('site_url', '')).path.rstrip('/')
    icon_set    = tine20_dir / 'images' / 'icon-set'

    # Copy all icons referenced via {{icon_url}} in any doc
    for name in _collect_icon_url_refs(docs_dir):
        src = icon_set / name
        if src.is_file():
            _copy_if_changed(src, icons_dir / name)
        else:
            log.warning(f'[manual-index] {{{{icon_url}}}}{name} not found in icon-set')

    # Build manual index tiles
    pages = sorted(
        p for p in manual_dir.glob('*.md')
        if p.name != 'index.md'
    )

    tiles = []
    for page_path in pages:
        content = page_path.read_text(encoding='utf-8')

        m = _TITLE_RE.search(content)
        title = m.group(1).strip() if m else page_path.stem

        m = _CTX_RE.search(content)
        app_name = m.group(1) if m else None

        # Explicit <!-- icon: ... --> overrides SCSS lookup
        m = _ICON_RE.search(content)
        icon_ref = _resolve_icon_comment(m.group(1), icon_set, icons_dir, base_path) if m else None

        if icon_ref is None and app_name:
            icon_src = _find_scss_icon(tine20_dir, app_name)
            if icon_src:
                _copy_if_changed(icon_src, icons_dir / icon_src.name)
                icon_ref = f'{base_path}/assets/icons/{icon_src.name}'

        tiles.append({'href': page_path.stem + '/', 'title': title, 'icon': icon_ref})

    _write_index(manual_dir / 'index.md', tiles)
    log.info(f'[manual-index] {len(tiles)} tiles written')


def _collect_icon_url_refs(docs_dir: Path) -> set:
    refs = set()
    for md_file in docs_dir.rglob('*.md'):
        try:
            content = md_file.read_text(encoding='utf-8', errors='ignore')
        except OSError:
            continue
        refs.update(_ICON_URL_RE.findall(content))
    return refs


def _resolve_icon_comment(value: str, icon_set: Path, icons_dir: Path, base_path: str) -> 'str | None':
    if '/' not in value:
        src = icon_set / value
        if src.is_file():
            _copy_if_changed(src, icons_dir / value)
            return f'{base_path}/assets/icons/{value}'
        log.warning(f'[manual-index] icon comment: {value!r} not found in icon-set')
        return None
    return value


def _find_scss_icon(tine20_dir: Path, app_name: str) -> 'Path | None':
    scss_dir = tine20_dir / app_name / 'styles'
    if not scss_dir.is_dir():
        return None

    cls_pat = re.compile(rf'\b{re.escape(app_name)}IconCls\b')

    for scss_file in sorted(scss_dir.glob('*.scss')):
        try:
            lines = scss_file.read_text(encoding='utf-8', errors='ignore').splitlines()
        except OSError:
            continue

        for i, line in enumerate(lines):
            if not cls_pat.search(line):
                continue
            for j in range(i, min(i + 15, len(lines))):
                url_m = _URL_RE.search(lines[j])
                if url_m:
                    raw      = url_m.group(1).strip('\'"')
                    resolved = (scss_file.parent / raw).resolve()
                    if resolved.suffix.lower() == '.svg' and resolved.is_file():
                        return resolved
                    break
                if j > i and '}' in lines[j] and '{' not in lines[j]:
                    break

    return None


def _copy_if_changed(src: Path, dest: Path) -> None:
    if dest.is_file() and dest.read_bytes() == src.read_bytes():
        return
    dest.write_bytes(src.read_bytes())


def _write_index(index_path: Path, tiles: list) -> None:
    lines = [
        '---',
        'hide:',
        '  - toc',
        '---',
        '',
        '# Benutzerhandbuch',
        '',
        '<div class="manual-tiles">',
    ]

    for tile in tiles:
        lines.append(f'  <a class="manual-tile" href="{tile["href"]}">')
        if tile['icon']:
            lines.append(f'    <img class="manual-tile-icon" src="{tile["icon"]}" alt="">')
        lines.append(f'    <span class="manual-tile-label">{tile["title"]}</span>')
        lines.append('  </a>')

    lines += ['</div>', '']
    content = '\n'.join(lines)

    if index_path.is_file() and index_path.read_text(encoding='utf-8') == content:
        return
    index_path.write_text(content, encoding='utf-8')
