Tine.widgets.form.FieldManager.register('Felamimail', 'Signature', 'signature', {
    xtype: 'htmleditor',
    name: 'signature',
    defaultFont: Tine.Felamimail.registry.get('preferences').get('defaultfont') || 'tahoma',
    height: 400,
    getDocMarkup: function(){
        let markup = '<html>'
        + '<head>'
        + '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
        + '<title></title>'
        + '<style type="text/css">'
        // standard css reset
        + "html,body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,p,blockquote,th,td{margin:0;padding:0;}img,body,html{border:0;}address,caption,cite,code,dfn,em,strong,th,var{font-style:normal;font-weight:normal;}ol,ul {list-style:none;}caption,th {text-align:left;}h1,h2,h3,h4,h5,h6{font-size:100%;}q:before,q:after{content:'';}"
        // small forms
        + "html,body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,p,blockquote,th,td{font-size: small;}"
        // lists
        + "ul {list-style:circle outside; margin-left: 20px;}"
        + "ol {list-style:decimal outside; margin-left: 20px;}"
        + "strong {font-weight: bold; font-style: inherit;}"
        + "em {font-style: italic; font-weight: inherit;}"
        + 'body.dark-mode {'
        + '  filter: invert(1) hue-rotate(180deg); '
        + '  color: #f1f1f1; '
        + '} '
        + 'body.dark-mode a {'
        + '  color: #6c9ad9'
        + '} '
        + 'body.dark-mode a:visited {'
        + '  color: #3c78c9'
        + '}'
        + '</style>'
        + '</head>'
        + '<body style="padding: 5px 0px 0px 5px; margin: 0px" class="' + String(Ext.getBody().dom.classList.value).trim() + '">'
        + '<span id="felamimail\-body\-signature">'
        + '</span>'
        + '</body></html>'
        return markup;
    },
    plugins: [
        new Ext.ux.form.HtmlEditor.RemoveFormat(),
        new Ext.ux.form.HtmlEditor.SelectImage()
    ]
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);