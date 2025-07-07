import FieldTriggerPlugin from "ux/form/FieldTriggerPlugin"
import asString from "../../Tinebase/js/ux/asString"

const OrganizerCombo = Ext.extend(Tine.Addressbook.ContactSearchCombo, {
    name: 'organizer',
    userOnly: true,
    noEditPlugin: true,
    forceSelection: false,
    onOrganizerSelect: Ext.emptyFn,

    initComponent: function() {
        this.plugins = this.plugins || [];
        this.plugins.push(new FieldTriggerPlugin({
            id: 'orga-type',
            triggerClass: 'cal-organizer-type-email'
        }));

        this.supr().initComponent.call(this);
    },
    getValue: function() {
        // NOTE: when entering a plain mail, getValue is called (from onBlur or onSpecialkey) (no set value!!!)
        //       so we can't set correct values in containing record here!
        if (this.el.dom) {
            var raw = _.get(this, 'el.dom') ? this.getRawValue() : Ext.value(this.value, '');
            if (Ext.form.VTypes.email(raw)) {
                this.value = null;
                this.onOrganizerSelect({
                    organizer: null,
                    organizer_type: 'email',
                    organizer_email: raw,
                    organizer_displayname: ''
                });
            }
        }

        var id = Tine.Addressbook.SearchCombo.prototype.getValue.apply(this, arguments),
            record = this.store.getById(id);

        return record ? record.data : id;
    },
    onSelect : async function(record, index, noCollapse) {
        const displayName = await asString(record.getTitle());

        this.onOrganizerSelect({
            organizer: record.id,
            organizer_type: 'contact',
            organizer_email: record.getPreferredEmail(),
            organizer_displayname: displayName
        });

        this.supr().onSelect.apply(this, arguments)
    },
    setValue(value, record) {
        const orgaType = record?.get('organizer_type') ?? 'contact';
        const plugin = _.find(this.plugins, {id: 'orga-type'});
        plugin.setTriggerClass(`cal-organizer-type-${orgaType}`);
        plugin.setQtip(orgaType === 'email' ?
            this.app.i18n._('External organizer') :
            this.app.i18n._('Internal organizer')
        );
        if (record && orgaType === 'email') {
            this.setRawValue(`${record.get('organizer_displayname')} <${record.get('organizer_email')}>`);
            this.el.removeClass(this.emptyClass);
            return;
        }
        this.supr().setValue.call(this, value, record);
    }
})

Ext.reg('calendar-event-organizer-combo', OrganizerCombo)
Tine.widgets.form.FieldManager.register('Calendar', 'Event', 'organizer', {
    xtype: 'calendar-event-organizer-combo'
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)

export default OrganizerCombo