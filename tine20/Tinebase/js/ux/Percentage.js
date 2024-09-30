/*
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * increase depth of cell selector depth for event delegation (defaults to 4) 
 */
if (Ext.grid.GridView.prototype.cellSelectorDepth < 6) {
    Ext.grid.GridView.prototype.cellSelectorDepth = 6;
}

Ext.ns('Ext.ux');

/**
 * Percentage select combo box
 * 
 * @namespace   Ext.ux
 * @class       Ext.ux.PercentCombo
 * @extends     Ext.form.ComboBox
 */
Ext.ux.PercentCombo = Ext.extend(Ext.form.ComboBox, {
    /**
     * @cfg {bool} autoExpand Autoexpand comboBox on focus.
     */
    autoExpand: false,
    /**
     * @cfg {bool} blurOnSelect blurs combobox when item gets selected
     */
    blurOnSelect: false,
    
    displayField: 'value',
    valueField: 'key',
    mode: 'local',
    triggerAction: 'all',
    emptyText: 'percent ...',
    lazyInit: false,
    forceSelection: true,
    itemSelector: 'div.search-item',
    editable: false,

    //private
    initComponent: function(){
        Ext.ux.PercentCombo.superclass.initComponent.call(this);
        // allways set a default
        if(!this.value) {
            this.value = 0;
        }
        
        this.initTemplate();

        this.store = new Ext.data.SimpleStore({
            fields: ['key','value'],
            data: [
                    ['0',    '0%'],
                    ['10',  '10%'],
                    ['20',  '20%'],
                    ['30',  '30%'],
                    ['40',  '40%'],
                    ['50',  '50%'],
                    ['60',  '60%'],
                    ['70',  '70%'],
                    ['80',  '80%'],
                    ['90',  '90%'],
                    ['100','100%']
                ]
        });
        
        if (this.autoExpand) {
            this.lazyInit = false;
            this.on('focus', function(){
                this.selectByValue(this.getValue());
                this.onTriggerClick();
            });
        }
        
        if (this.blurOnSelect){
            this.on('select', function(){
                this.fireEvent('blur', this);
            }, this);
        }
    },
    
    setValue: function(value) {
        value = value ? value : 0;
        Ext.ux.PercentCombo.superclass.setValue.call(this, value);
    },
    
    /**
     * init template
     * @private
     */
    initTemplate: function() {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for=".">',
                    '<div class="search-item">',
                        '<div class="x-progress-wrap PercentRenderer">',
                            '<div class="x-progress-inner PercentRenderer">',
                                '<div class="x-progress-bar PercentRenderer {[this.getColorClass(values)]}" style="width:{[this.getValue(values)]}%">',
                                    '<div class="PercentRendererText PercentRenderer">',
                                        '<div>{[this.getValue(values)]}%</div>',
                                    '</div>',
                                '</div>',
                            '</div>',
                        '</div>',
                    '</div>',
                '</tpl>',
                {
                    getValue: function(values) {
                        return values.key;
                    },
                    
                    getColorClass: function(values) {
                        return 'PercentRenderer-progress-bar' + values.key;
                    }
                }
            );
        }
    }
});
Ext.reg('extuxpercentcombo', Ext.ux.PercentCombo);

/**
 * Renders a percentage value to a percentage bar
 * @constructor
 */
Ext.ux.PercentRenderer = function(percent) {
    if (! Ext.ux.PercentRenderer.template) {
        Ext.ux.PercentRenderer.template = new Ext.XTemplate(
            '<div class="x-progress-wrap PercentRenderer"<tpl if="qtitle">ext:qtitle="{qtitle}"</tpl><tpl if "qtip">ext:qtip="{qtip}"></tpl>',
            '<div class="x-progress-inner PercentRenderer">',
                '<div class="x-progress-bar PercentRenderer {colorClass}" style="width:{percent}%">',
                    '<div class="PercentRendererText PercentRenderer">',
                        '<div>{percent}%</div>',
                    '</div>',
                '</div>',
                '<div class="x-progress-text x-progress-text-back PercentRenderer">',
                    '<div>&#160;</div>',
                '</div>',
            '</div>',
        '</div>'
        ).compile();
    }

    const data = _.isObject(percent) ? percent : { percent };
    data.qtitle = data.qtitle ?? null;
    data.qtip = data.qtip ?? null;
    if (! data.colorClass) {
        // this will enable a color scheme for each percentage on the progress bar
        data.colorClass = `PercentRenderer-progress-bar${Math.round(Math.min(Math.max(data.percent,0),100)/10)}0`
    }

    return Ext.ux.PercentRenderer.template.apply(data);
};

/**
 * Renders a percentage value to a percentage bar / uploadrow
 * @constructor
 */
Ext.ux.PercentRendererWithName = function(value, metadata, record) {
  
    var metaStyle = '',
        dataSafeEnabled = !!Tine.Tinebase.areaLocks.getLocks(Tine.Tinebase.areaLocks.dataSafeAreaName).length;

    if(record.fileRecord) {
        record = record.fileRecord;
    }

    metadata.css = 'x-grid-mimeicon';

    if(record.get('type') === 'folder') {
        metadata.css += record.id === '..' ? ' action_filemanager_folder_up' : ' mime-icon-folder';
        
        if (dataSafeEnabled && !!record.get('pin_protected_node')) {
            metadata.css += ' x-type-data-safe'
        }

    } else {
        metadata.css += ' mime-icon-file';

        var contenttype =  record.get('contenttype');
        if(contenttype) {
            metadata.css += ' ' + Tine.Tinebase.common.getMimeIconCls(contenttype);
        }
    }

    if (Tine.Tinebase.common.hasRight('run', 'Filemanager')) {
        metadata.css += ' ' + Tine.Filemanager.Model.Node.getStyles(record).join(' ');
    }
    
    // all browsers should support chunk upload
    /*
    if (!Ext.ux.file.Upload.isHtml5ChunkedUpload()) {
        var fileName = value;
        if (typeof value == 'object') {
            fileName = value.name;
        } 
    
        if(record.get('status') == 'uploading') {
            metadata.css += ' x-tinebase-uploadrow';
        }
        
        return Ext.util.Format.htmlEncode(fileName);
    }
    */
    
    if (! Ext.ux.PercentRendererWithName.template) {
        Ext.ux.PercentRendererWithName.template = new Ext.XTemplate(
            '<div class="x-progress-wrap PercentRenderer" style="{display}">',
            '<div class="x-progress-inner PercentRenderer">',
                '<div class="x-progress-bar PercentRenderer" style="width:{percent}%;{additionalStyle}">',
                    '<div class="PercentRendererText PercentRenderer">',
                         '{fileName}',
                    '</div>',
                '</div>',
                '<div class="x-progress-text x-progress-text-back PercentRenderer">',
                    '<div>&#160;</div>',
                '</div>',
            '</div>',
        '</div>'
        ).compile();
    }
    
    if(value == undefined) {
        return '';
    }              
    
    var fileName = value;

    if (typeof value == 'object') {
        fileName = value.name;
    }
    fileName = Ext.util.Format.htmlEncode(fileName);
    
    if (_.get(record, 'data.type', _.get(record, 'type')) === 'link') {
        fileName = '<div class="mime-icon-overlay mime-icon-link"></div>' + fileName;
    }
    
    if(record.get('is_quarantined') === '1') {
        const warningText = i18n._('This file might potentially harm your computer. Therefore it got quarantined and cannot be edited or downloaded.');
        fileName = '<div class="mime-icon-overlay x-tinebase-virus" ext:qtip="'+ Tine.Tinebase.common.doubleEncode(warningText) +'"></div>' + fileName + ' (' + i18n._('quarantined') + ')';
    }

    if (_.get(record, 'data.type', _.get(record, 'type')) === 'link') {
        fileName = '<div class="mime-icon-overlay mime-icon-link"></div>' + fileName;
    }

    // check contenttype and get info , update ui based on it
    
    let percent = -1;
    
    if (String(record.get('contenttype')).match(/^vnd\.adobe\.partial-upload.*/)) {
        const progress = parseInt(_.last(_.split(record.get('contenttype'), ';')).replace('progress=', ''));
        if(progress > -1) {
            percent = progress;
        }
    } else {
        percent = record.get('progress');
    }
    
    var additionalStyle = '';
    if(record.get('status') === 'paused' && percent < 100) {
        fileName = i18n._('(paused)') + '&#160;&#160;' + fileName;
        additionalStyle = 'background-image: url(\'styles/images/tine20/progress/progress-bg-y.gif\') !important;';
    }

    var display = 'width:0px';
    if(percent > -1 && percent < 100) {
        display = '';
        var renderedField = Ext.ux.PercentRendererWithName.template.apply({percent: percent, display: display, fileName: fileName
            , additionalStyle: additionalStyle}) ;
        return renderedField;
    }
    else {
        return fileName;
    }
    

};
