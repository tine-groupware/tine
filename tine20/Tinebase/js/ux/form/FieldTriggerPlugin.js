/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import '../../../css/ux/form/FieldTriggerPlugin.css'

class FieldTriggerPlugin {
    triggerClass = 'x-form-trigger'
    visible = true
    qtip = null
    preserveElStyle = false
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

            field.mon(this.#trigger, 'click', this.onTriggerClick, this, {preventDefault:true});
            this.#trigger.addClassOnOver('x-form-trigger-over');
            this.#trigger.addClassOnClick('x-form-trigger-click');

            // preserve space for triggers
            if (!this.preserveElStyle) {
                wrap.addClass('x-form-trigger-plugin-wrap')
                const paddingRight = Number(String(field.el.getStyle('padding-right')).replace('px', ''));
                field.el.setStyle({
                    'box-sizing': 'border-box',
                    'padding-right': paddingRight+17 + 'px',
                    'width' : '100%',
                    'height': field.getHeight() + 'px'
                });
            }
            field.el.autoBoxAdjust = false;
            field.onResize(wrap.getWidth(), wrap.getHeight());
        }

        this.assertState()
    }

    assertState() {
        this.#trigger?.setStyle({
            right: _.transform(this.field.plugins, (pos, plugin) => {
                if (plugin === this) return false
                if (plugin instanceof FieldTriggerPlugin && plugin.visible) pos.push(plugin)
            }, []).length * 16 + (this.field.getTriggerWidth?.() || 0) + 'px'
        })

        this.setVisible((!this.hideOnEmptyValue || this.field.getValue()) && (!this.hideOnInvalidValue || this.field.isValid()));
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


