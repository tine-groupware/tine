/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.widgets');

import FieldInfoPlugin from "../../../Tinebase/js/ux/form/FieldInfoPlugin";

/**
 * Widget for preference selection
 *
 * @namespace   Tine.widgets
 * @class       Tine.widgets.PreferenceCombo
 * @extends     Ext.form.ComboBox
 */
Tine.widgets.PreferenceCombo = Ext.extend(Ext.form.ComboBox, {
    typeAhead: true,
    forceSelection: true,
    mode: 'local',
    triggerAction: 'all',
    selectOnFocus:  true,
    preferenceName: null,
    
    async initComponent() {
        const app = Tine.Tinebase.appMgr.get(this.appName);
        const pref = app.getRegistry().get('preferences').get(this.preferenceName);

        if (pref) {
            const currentAccountId = Tine.Tinebase.registry.get('currentAccount').accountId;
            const filter = [{
                field: 'account',
                operator: 'equals',
                value: {accountId: currentAccountId, accountType: 'user'}
            }];
            await Tine.Tinebase.searchPreferencesForApplication(this.appName, filter)
                .then(result => {
                    result.results.forEach((item) => {
                        if (item.name === this.preferenceName) {
                            const parentValue = item.options.filter((option) => {
                                return option[0] === item.value;
                            }).pop();

                            this.store = item.options.map((option) => {
                                if (option[0] === '_default_') {
                                    option[0] = 'follow_preference';
                                    option[1] = i18n._('Follow Preference') + ` - ${parentValue[1]}`;
                                }
                                return option.flat();
                            });

                            const isPreferenceLocked = item.locked || item.type === 'forced';

                            if (isPreferenceLocked) {
                                this.setDisabled(true);
                            }

                            this.plugins = [new FieldInfoPlugin({
                                qtip: i18n._('this config can not be set because its overwritten from a locked preference in admin mode.'),
                                visible: isPreferenceLocked,
                            })]
                        }
                    })
                    Tine.widgets.PreferenceCombo.superclass.initComponent.call(this);
                });
        }
    },
});

Ext.reg('widget-preferencecombo', Tine.widgets.PreferenceCombo);
