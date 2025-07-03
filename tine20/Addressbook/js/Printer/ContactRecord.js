Ext.ns('Tine.Addressbook.Printer');

Tine.Addressbook.Printer.ContactRenderer = Ext.extend(Ext.ux.Printer.EditDialogRenderer, {
    stylesheetPath: 'Tinebase/css/widgets/print.css',

    generateBody: function(component, data) {
        var i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n;

        return new Promise(function (fulfill, reject) {
            var bodyTpl = new Ext.XTemplate(
                '<div class="rp-print-single">',
                '{[Tine.widgets.printer.headerRenderer()]}',
                '<table><tr><td>',
                '<div class="rp-print-single-image">',
                '<img src="{[values.jpegphoto || \'\']}">',
                '</div>',
                '</td><td>',
                '<div class="rp-print-single-block">',
                '<div class="rp-print-single-details-row">',
                '<span class="rp-print-single-value">{salutation} {n_prefix}</span>',
                '</div>',
                '<div class="rp-print-single-summary">',
                '<span class="adb-print-single-value">{n_fn}</span>',
                '</div>',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.org_name, "Company")]}',
                '</div>',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.org_unit, "Unit")]}',
                '</div>',
                '<tpl if="this.featureIndustry()">',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.industry, "Industry")]}',
                '</div>',
                '</tpl>',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.title, "Job Title")]}',
                '</div>',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.bday, "Birthday")]}',
                '</div>',
                '</div>',
                '</td></tr></table></br>',
                new Ext.ux.Printer.TagsRenderer().generateBody(component.getForm().findField('tags').tagsPanel),
                '<div class="cal-print-single-block-heading">', i18n._('Description'), '</div>',
                '<div class="rp-print-single-block">',
                '{[this.encode(values.note)]}',
                '</div>',
                '</br>',
                '<div class="cal-print-single-block-heading">', i18n._('Contact Information'), '</div>',
                '<div class="rp-print-single-block">',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.tel_work, "Phone")]}',
                '</div>',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.tel_cell, "Mobile")]}',
                '</div>',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.tel_home, "Phone (private)")]}',
                '</div>',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.tel_cell_private, "Mobile (private)")]}',
                '</div>',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.email, "E-Mail")]}',
                '</div>',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.email_home, "E-Mail (private)")]}',
                '</div>',
                '<div class="rp-print-single-details-row">',
                '{[this.fieldRenderer(values.url, "Web")]}',
                '</div>',
                '</div>',
                '</br>',
                '<div class="cal-print-single-block-heading">', i18n._('Company Address'), '{[values.preferred_address == "0" ? (" (" + Tine.Tinebase.appMgr.get("Addressbook").i18n._("Preferred Address") + ")") : ""]}</div>',
                '<div class="rp-print-single-block">',
                "{[this.addressRenderer({'street': 'adr_one_street', 'street2': 'adr_one_street2', 'postalcode': 'adr_one_postalcode', 'locality': 'adr_one_locality', 'region': 'adr_one_region','country': 'adr_one_countryname'})]}",
                '</div>',
                '</br>',
                '<div class="cal-print-single-block-heading">', i18n._('Private Address'), '{[values.preferred_address == "1" ? (" (" + Tine.Tinebase.appMgr.get("Addressbook").i18n._("Preferred Address") + ")") : ""]}</div>',
                '<div class="rp-print-single-block">',
                "{[this.addressRenderer({'street': 'adr_two_street', 'street2': 'adr_two_street2', 'postalcode': 'adr_two_postalcode', 'locality': 'adr_two_locality', 'region': 'adr_two_region','country': 'adr_two_countryname'})]}",
                '</div>',
                '</br>',
                '{[this.customFieldRenderer(values.customfields)]}',
                '</br>',
                '<div class="cal-print-single-block-heading">', i18n._('Related to'), '</div>',
                '<div class="rp-print-single-block">',
                '{[this.relationRenderer(values.relations)]}',
                '</div>',
                '</div>',

                {
                    customFieldRenderer: function (values) {
                        return Tine.widgets.customfields.Renderer.renderAll('Addressbook', Tine.Addressbook.Model.Contact, values);
                    },
                    fieldRenderer: function (fieldValue, label) {
                        return Tine.widgets.printer.fieldRenderer('Addressbook', Tine.Addressbook.Model.Contact, fieldValue, label);
                    },
                    addressRenderer: function (config) {
                        var renderer = Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'addressblock', 'displayPanel');
                        return renderer(null, null, data, null, null, null, config);
                    },
                    relationRenderer: function (values) {
                        return Tine.widgets.relation.Renderer.renderAll(values);

                    },
                    featureIndustry: function() {
                        return Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureIndustry');
                    }

                });
            fulfill(bodyTpl.apply(data));
        })
    }
});
