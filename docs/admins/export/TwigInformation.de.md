Twig Informationen
====

Twig Dokumentation
----
<https://twig.symfony.com/doc/>

Twig Syntax
----
Es gibt zwei verschiedene Twig Syntaxen.  
Im tine Kontext sprechen wir von alter und neuer Syntax.

**neu:**  

`{{record.dtstart.format('H:i')}}`
`{{record.location}}`

> *Hinweis:*  
> *Bitte in allen neuen `docx` Templates benutzen.*  

**alt:**  

`${twig:record.dtstart.format('H:i')}`
`${twig:record.location}`

> *Hinweis:*  
> *Diese Syntax wird in `xlms` Templates benutzt.*  
