/*
 * Tine 2.0
 * 
 * plugins: [new FieldMaximizePlugin({
 *       maxPanelConfig: {
 *           title: this.app.i18n._('Job Title'),
 *           padding: 5
 *       }
 *   })],
 * 
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import FieldTriggerPlugin from "./FieldTriggerPlugin"

class FieldMaximizePlugin extends FieldTriggerPlugin {
    triggerClass = 'maximize'

    async init (field) {
        await super.init(field)
    }

    async onTriggerClick () {
        if (this.isMaximized) {
            this.itemEl.appendChild(this.wrap);
            this.field.getEl().setSize(this.fieldSize);
            Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(Tine.Tinebase.viewport.tineViewportMaincardpanel, this.mainCard, true);
            this.setTriggerClass('maximize');

            this.isMaximized = false;
        } else {
            if (! this.maxPanel) {
                this.maxPanel = new Ext.Panel(Object.assign({
                    layout: 'fit',
                    height: '100%'
                }, this.maxPanelConfig || {}));
            }
            this.fieldSize = this.field.getEl().getSize();
            
            this.mainCard = Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(Tine.Tinebase.viewport.tineViewportMaincardpanel, this.maxPanel, true);
            this.mainCard.keep = true;
            await this.maxPanel.afterIsRendered();
            this.itemEl = this.field.getEl().up('.x-form-item');
            this.wrap = this.field.getEl().up('.x-form-trigger-plugin-wrap');
            this.wrap.setSize('100%', '100%');
            this.maxPanel.body.appendChild(this.wrap);
            this.field.getEl().setSize('100%', '100%');
            this.setTriggerClass('minimize');
            this.isMaximized = true;
           
        }
    }
}

Ext.preg('ux.fieldmaximizeplugin', FieldMaximizePlugin);

export default FieldMaximizePlugin