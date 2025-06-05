/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         use new filter syntax in onBeforeQuery when TagFilter is refactored and extends Tinebase_Model_Filter_FilterGroup 
 */

Ext.ns('Tine.widgets', 'Tine.widgets.tags');

import RecordEditFieldTriggerPlugin from "../form/RecordEditFieldTriggerPlugin";

/**
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagCombo
 * @extends     Ext.ux.form.ClearableComboBox
 */
Tine.widgets.tags.TagCombo = Ext.extend(Ext.ux.form.ClearableComboBox, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    
    /**
     * @cfg {Bool} findGlobalTags true to find global tags during search (default: true)
     */
    findGlobalTags: true,

    /**
     * @cfg {Bool} onlyUsableTags true to find only usable flags for the user (default: true)
     */
    onlyUsableTags: false,
    
    emptyText: null,
    mode: 'remote',
    triggerAction: 'all',
    displayField: 'name',
    valueField: 'id',
    
    /**
     * @private
     */
    initComponent: function() {
        this.emptyText = this.emptyText ? this.emptyText : i18n._('tag name');
        
        this.initStore();
        this.initTemplate();
        this.plugins = [new RecordEditFieldTriggerPlugin({
            qtip: i18n._('Add a new personal tag'),
            visible: Tine.Tinebase.common.hasRight('use_personal_tags', this.app.appName),
            scope: this,
            onTriggerClick: () => {
                Ext.Msg.prompt(i18n._('Add New Personal Tag'),
                    i18n._('Please note: You create a personal tag. Only you can see it!') + ' <br />' + i18n._('Enter tag name:'),
                    function(btn, text) {
                        if (btn === 'ok'){
                            if (text.length < 3) {
                                Ext.Msg.show({
                                    title: i18n._('Notice'),
                                    msg: i18n._('The minimum tag length is three.'),
                                    buttons: Ext.Msg.OK,
                                    animEl: 'elId',
                                    icon: Ext.MessageBox.INFO
                                });
                                return false;
                            }
                            const tagToAttach = new Tine.Tinebase.Model.Tag({
                                name: text,
                                type: 'personal',
                                description: '',
                                color: '#FFFFFF'
                            });
                            /*
                            @todo check if tag exist here
                            this.availableTagsStore.each(function(tag){
                                if(tag.data.name == tagName) {
                                    tagToAttach = tag;
                                }
                            }, this);*/

                            if (! Ext.isIE) {
                                this.el.mask();
                            }
                            Ext.Ajax.request({
                                params: {
                                    method: 'Tinebase.saveTag',
                                    tag: tagToAttach.data
                                },
                                success: function(_result, _request) {
                                    const tagData = Ext.util.JSON.decode(_result.responseText);
                                    const newTag = new Tine.Tinebase.Model.Tag(tagData, tagData.id);
                                    this.fireEvent('select', this, newTag);
                                    // reset avail tag store
                                    //this.availableTagsStore.lastOptions = null;
                                    this.lastQuery = null;
                                    this.el.unmask();
                                },
                                failure: function ( result, request) {
                                    Ext.MessageBox.alert(i18n._('Failed'), i18n._('Could not create tag.'));
                                    this.el.unmask();
                                },
                                scope: this
                            });
                        }
                    },
                    this, false, this.lastQuery);
            }
        })];

        Tine.widgets.tags.TagCombo.superclass.initComponent.call(this);
        
        this.on('select', this.onSelectRecord, this);
        this.on('beforequery', this.onBeforeQuery, this);
    },
    
    /**
     * hander of select event
     * NOTE: executed after native onSelect method
     */
    onSelectRecord: function(){
        const v = this.getValue();

        if(String(v) !== String(this.startValue)){
            this.fireEvent('change', this, v, this.startValue);
        }
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent){
        
        var filter = {
            name: (qevent.query && qevent.query != '') ? '%' + qevent.query + '%' : '',
            application: this.app ? this.app.appName : '',
            grant: (this.onlyUsableTags) ? 'use' : 'view' 
        };
        
        this.store.baseParams.filter = filter;
    },

    /**
     * set value
     * 
     * @param {} value
     */
    setValue: function(value) {
        
        if (typeof value === 'object' && Object.prototype.toString.call(value) === '[object Object]') {
            if (! this.store.getById(value.id)) {
                this.store.addSorted(new Tine.Tinebase.Model.Tag(value));
            }
            value = value.id;
        }
        
        Tine.widgets.tags.TagCombo.superclass.setValue.call(this, value);
        
    },
    
    /**
     * init store
     */
    initStore: function() {
        var baseParams = {
            method: 'Tinebase.searchTags',
            paging: {}
        };
        
        this.store = new Ext.data.JsonStore({
            id: 'id',
            root: 'results',
            totalProperty: 'totalCount',
            fields: Tine.Tinebase.Model.Tag,
            baseParams: baseParams
        });

    },
    
    /**
     * init template
     */
    initTemplate: function() {
        this.tpl = new Ext.XTemplate(
            '<tpl for=".">', 
                '<div class="x-combo-list-item">',
                    '<div class="tb-grid-tags dark-reverse" style="background-color:{values.color};">&#160;</div>',
                    '<div class="x-widget-tag-tagitem-text" ext:qtip="', 
                        '{[this.encode(values.name)]}', 
                        '<tpl if="type == \'personal\' ">&nbsp;<i>(' + i18n._('personal') + ')</i></tpl>',
                        '</i>&nbsp;[{occurrence}]',
                        '<tpl if="description != null && description.length &gt; 1"><hr>{[this.encode(values.description)]}</tpl>" >',
                        
                        '&nbsp;{[this.encode(values.name)]}',
                        '<tpl if="type == \'personal\' ">&nbsp;<i>(' + i18n._('personal') + ')</i></tpl>',
                    '</div>',
                '</div>', 
            '</tpl>',
            {
                encode: function(value) {
                     if (value) {
                        return Tine.Tinebase.common.doubleEncode(value);
                    } else {
                        return '';
                    }
                }
            }
        );
    }
});

Ext.reg('Tine.widgets.tags.TagCombo', Tine.widgets.tags.TagCombo);
Tine.widgets.form.RecordPickerManager.register('Tinebase', 'Tag', Tine.widgets.tags.TagCombo);

