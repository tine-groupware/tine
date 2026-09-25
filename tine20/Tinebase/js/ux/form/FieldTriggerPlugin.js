/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import '../../../styles/ux/form/FieldTriggerPlugin.scss'

class FieldTriggerPlugin {
    triggerClass = 'x-form-trigger'
    visible = true
    qtip = null
    hideOnEmptyValue = false
    hideOnInvalidValue = false
    #trigger
    
    constructor(config) {
        _.assign(this, config)
    }
    
    async init (field) {
        this.field = field

        if (field.initKeyEvents) {
            field.initKeyEvents();
        }
        field.setValue = field.setValue.createSequence(_.bind(this.assertState, this))
        field.clearValue = field.clearValue?.createSequence(_.bind(this.assertState, this))
        field.setReadOnly = field.setReadOnly.createSequence(_.bind(this.assertState, this))
        field.setDisabled = field.setDisabled.createSequence(_.bind(this.assertState, this))
        field.reset = field.reset?.createSequence(_.bind(this.assertState, this))
        if (field.setHideTrigger) {
            field.setHideTrigger = field.setHideTrigger.createSequence(_.bind(this.assertState, this))
        }
        field.on('keydown', this.assertState, this, { buffer: 50 })

        await field.afterIsRendered()

        const wrap = field.el.parent('.x-form-field-wrap') ||
            field.el.parent('.tw-relpickercombocmp') ||
            field.el.parent('.x-form-element') ||
            field.el.parent('.x-grid-editor');

        if (wrap) {
            this.#trigger = wrap.createChild(this.triggerConfig ||
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger x-form-trigger-plugin " + this.triggerClass})
            this.setVisible(this.visible)
            if (this.qtip) {
                this.setQtip(this.qtip)
            }

            // prevent focus loss
            field.mon(this.#trigger, 'mousedown', Ext.emptyFn, null, { preventDefault: true });
            if (this.onTriggerClick) {
                field.mon(this.#trigger, 'click', _.bind(this.onTriggerClick,this.scope || this, this, _), this, { preventDefault:true });
            }

            this.#trigger.addClassOnOver('x-form-trigger-over');
            this.#trigger.addClassOnClick('x-form-trigger-click');

            wrap.addClass('x-form-trigger-plugin-wrap')
            field.el.autoBoxAdjust = false;
            field.onResize(wrap.getWidth(), wrap.getHeight());
        }

        this.assertState()
    }

    assertState() {
        this.setVisible((!this.hideOnEmptyValue || !!this.field.getValue()) && (!this.hideOnInvalidValue || this.field.isValid()));

        const visibleTriggerPlugins = _.filter(this.field.plugins, plugin => plugin instanceof FieldTriggerPlugin && plugin.visible)
        const pos = _.indexOf(visibleTriggerPlugins, this)
        this.field.el?.setStyle({
            'padding-right': visibleTriggerPlugins.length * 18 /* trigger width width */ + (this.field.getTriggerWidth?.() || 0) + 9 + 'px'
        })
        this.#trigger?.setStyle({
            right: pos * 18 /* trigger width width */ + (this.field.getTriggerWidth?.() || 0) + 9 /* field padding w.o. trigger */ + 'px'
        })
    }

    setTriggerClass(triggerClass) {
        if (this.#trigger) {
            this.#trigger.removeClass(this.triggerClass);
            this.#trigger.addClass(triggerClass);
            this.triggerClass = triggerClass;
        }
    }

    setVisible(visible) {
        this.visible = visible
        if (this.#trigger) {
            this.#trigger.setVisible(visible);
        }
    }

    setQtip(qtip) {
        this.qtip = qtip
        if (this.#trigger) {
            this.#trigger.set({ 'ext:qtip': Tine.Tinebase.common.doubleEncode(qtip) });
        }
    }

    update(html) {
        if (this.#trigger) {
            this.#trigger.update(html);
        }
    }
}
export default FieldTriggerPlugin


