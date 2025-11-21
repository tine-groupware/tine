/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.GridDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 * 
 * <p>Message Grid Details Panel</p>
 * <p>the details panel (shows message content)</p>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.GridDetailsPanel
 */
 Tine.Felamimail.GridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    /**
     * config
     * @private
     */
    defaultHeight: 350,
    currentId: null,
    record: null,
    app: null,
    i18n: null, 
     cls: 'x-ux-display',

    
    fetchBodyTransactionId: null,
    
    /**
     * init
     * @private
     */
    initComponent: function() {
        this.initDefaultTemplate();
        // this.initTopToolbar();

        Tine.Felamimail.GridDetailsPanel.superclass.initComponent.call(this);
        
        if (this.editRecordAction) {
            this.editRecordAction.setText('');
            this.editRecordAction.setIconClass('action_menu');
        }
    },

    /**
     * use default Tpl for default and multi view
     */
    initDefaultTemplate: function() {
        this.defaultTpl = new Ext.XTemplate(
            '<div class="preview-panel-felamimail">',
                '<div class="preview-panel-felamimail-preparedPart"></div>',
                '{[values?.msg ? `<div class="preview-panel-felamimail-body">${values.msg}</div>` : ""]}',
            '</div>'
        );
    },
    
    /**
     * init bottom toolbar (needed for event invitations atm)
     * 
     * TODO add buttons (show header, add to addressbook, create filter, show images ...) here
     */
//    initTopToolbar: function() {
//        this.tbar = new Ext.Toolbar({
//            hidden: true,
//            items: []
//        });
//    },
    
    /**
     * get panel for single record details
     * 
     * @return {Ext.Panel}
     */
    getSingleRecordPanel: function() {
        if (! this.singleRecordPanel) {
            this.singleRecordPanel = new Tine.Felamimail.MailDetailsPanel({
                hasTopToolbar: false,
                grid: this.grid,
            });
        }
        return this.singleRecordPanel;
    },
     
     onEdit(e) {
         if (!this.gridpanel) return;
         this.gridpanel.contextMenu.showAt(e.el.getXY());
     },

    /**
     * (on) update details
     * 
     * @param {Tine.Felamimail.Model.Message} record
     * @private
     */
    updateDetails: function(record) {
        if (record.id === this.currentId) {
            // nothing to do
        } else if (! record.bodyIsFetched()) {
            this.waitForContent(record);
        } else if (record === this.record) {
            this.setTemplateContent(record);
        }
    },

     /**
      * show template for multiple rows
      *
      * @param {Ext.grid.RowSelectionModel} sm
      * @param {Mixed} body
      */
     showMulti: function(sm, body) {
         let lastActive = this.grid.store.getAt(sm.lastActive);
         if (!lastActive) {
             lastActive = sm.selections.itemAt(sm.selections.length - 1);
         }
         if(this.layout && Ext.isFunction(this.layout.setActiveItem)) {
             this.layout.setActiveItem(this.getSingleRecordPanel());
         }
         if (!lastActive.bodyIsFetched()) {
             this.record = lastActive;
             this.waitForContent(lastActive);
         } else {
             this.setTemplateContent(lastActive);
         }
     },

     /**
      * wait for body content
      *
      * @param {Tine.Felamimail.Model.Message} record
      *
      * @todo move to Tine.Felamimail.MailDetailsPanel?
      */
     waitForContent: function(record) {
         if (! this.grid || this.grid.getSelectionModel().getCount() > 0) {
             this.refetchBody(record, {
                 success: this.updateDetails.createDelegate(this, [record]),
                 failure: function (exception) {
                     Tine.log.debug(exception);
                     this.getLoadMask().hide();
                     if (exception.code == 404) {
                         this.defaultTpl.overwrite(this.singleRecordPanel.getTemplateBody(), {msg: this.app.i18n._('Message not available.')});
                     } else {
                         Tine.Felamimail.messageBackend.handleRequestException(exception);
                     }
                 },
                 scope: this
             });
             this.defaultTpl.overwrite(this.singleRecordPanel.getTemplateBody(), {msg: ''});
             this.getLoadMask().show();
         } else {
             this.getLoadMask().hide();
         }
     },
    
    /**
     * refetch message body
     * 
     * @param {Tine.Felamimail.Model.Message} record
     * @param {Function} callback
     */
    refetchBody: function(record, callback) {
        // cancel old request first
        if (this.fetchBodyTransactionId && ! Tine.Felamimail.messageBackend.isLoading(this.fetchBodyTransactionId)) {
            Tine.log.debug('Tine.Felamimail.GridDetailsPanel::refetchBody -> cancelling current fetchBody request.');
            Tine.Felamimail.messageBackend.abort(this.fetchBodyTransactionId);
        }
        Tine.log.debug('Tine.Felamimail.GridDetailsPanel::refetchBody -> calling fetchBody');
        const displayFormat =  Tine.Felamimail.messageBackend.getFormatConfig('display_format', record);
        this.fetchBodyTransactionId = Tine.Felamimail.messageBackend.fetchBody(record, displayFormat, callback);
    },
    
    /**
     * overwrite template with content
     * 
     * @param {Tine.Felamimail.Model.Message} record
     */
    setTemplateContent: function(record) {
        this.currentId = record.id;
        this.getLoadMask().hide();

        this.singleRecordPanel.loadRecord(record);

        this.getEl().down('div').down('div').scrollTo('top', 0, false);

        if (this.record.get('preparedParts') && this.record.get('preparedParts').length > 0) {
            Tine.log.debug('Tine.Felamimail.GridDetailsPanel::setTemplateContent about to handle preparedParts');
            this.handlePreparedParts(record);
        }
    },
    
    /**
     * handle invitation messages (show top + bottom panels)
     * 
     * @param {Tine.Felamimail.Model.Message} record
     */
    handlePreparedParts: function(record) {
        var firstPreparedPart = this.record.get('preparedParts')[0],
            mimeType = String(firstPreparedPart.contentType).split(/[ ;]/)[0],
            mainType = Tine.Felamimail.MimeDisplayManager.getMainType(mimeType);
            
        if (! mainType) {
            Tine.log.info('Tine.Felamimail.GridDetailsPanel::handlePreparedParts nothing found to handle ' + mimeType);
            return;
        }
        
        var bodyEl = this.singleRecordPanel.getMessageRecordPanel().getEl().query('div[class=preview-panel-felamimail-preparedPart]')[0],
            detailsPanel = Tine.Felamimail.MimeDisplayManager.create(mainType, {
                detailsPanel: this,
                preparedPart: firstPreparedPart,
                messageRecord: this.record,
            });
            
        Ext.fly(bodyEl).update('');
        detailsPanel.render(bodyEl);
    }
});
