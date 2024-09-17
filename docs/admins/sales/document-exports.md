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
`[DIVISION-<division name>--][CATEGORY-<category parts>--][LANG-<language key>--]<definitionFileName>`


Recursive Category Parts Lookup
----

given the `document_category` "foo/bar/baz"

lookup searches in this order

- `CATEGORY-FooBarBaz--<definitionFileName>`
- `CATEGORY-FooBar--<definitionFileName>`
- `CATEGORY-Foo--<definitionFileName>`
- `<definitionFileName>`


Examples
----
- `DIVISION-Acme--CATEGORY-Foo--LANG-en--sales_document_order.docx`
- `DIVISION-Acme--LANG-de--sales_document_order.docx`
- `DIVISION-Acme--sales_document_order.docx`
- `LANG-fr--sales_document_delivery.docx`

The strongest match is chosen


Template: grouped positions
---

The datasource "POSITIONS" will be grouped by the position property grouping.
Within the group the positions are sorted by the property sorting.

In the group footer sums of all positions within the group are available:
export.groupcontext.sum_net_price
export.groupcontext.sum_gross_price
export.groupcontext.sum_sales_tax