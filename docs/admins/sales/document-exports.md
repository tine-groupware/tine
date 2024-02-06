Sales Document Exports
=

This are the pdf exports which are executed for the "Create Paperslip" action.

Template Lookup
---

The template locations are configured in the corresponding export definitions named with the schema `document_<type>_pdf` (e.g. `document_incoice_pdf`) with the xml tag `template`.

~~~xml
<plugin>Sales_Export_DocumentPdf</plugin>
<template>tine20:///Tinebase/folders/shared/export/templates/Sales/sales_document_order.docx</template>
~~~

It is possible to place special templates with different file names in the same directory.

Input Values
----

Slashes (`/`) in input values are replaced with an empty strings

- LANG = document_language.key
- CATEGORY = document_category.name (recursive lookup, split name by /)
- DIVISION = document_category.division_id.title


Filename Syntax:
----
`[DIVISON-<division name>--][CATEGORY-<category parts>--][LANG-<language key>--]<definitionFileName>`


Recursive Category Parts Lookup
----

given the `document_category` "foo/bar/baz"

lookup searches in this order

- `CATEGORY-foobarbaz--<definitionFileName>`
- `CATEGORY-foobar--<definitionFileName>`
- `CATEGORY-foo--<definitionFileName>`
- `<definitionFileName>`


Examples
----
- `DIVISION-acme--CATEGORY-foo--LANG-en--invoice.docx`
- `DIVISION-acme--LANG-de--invoice.docx`
- `DIVISION-acme--invoice.docx`
- `LANG-fr--delivery.docx`

The strongest match is chosen


