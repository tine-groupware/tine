/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Addressbook');

/**
 * Contact grid panel
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ContactGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Contact Grid Panel</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Addressbook.ContactGridPanel
 */
Tine.Addressbook.ContactGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Addressbook.Model.Contact} recordClass
     */
    recordClass: 'Addressbook.Model.Contact',

    /**
     * grid specific
     * @private
     */ 
    defaultSortInfo: {field: 'n_fileas', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'n_fileas',
        enableDragDrop: true,
        ddGroup: 'containerDDGroup'
    },
    copyEditAction: true,
    felamimail: false,
    multipleEdit: true,
    duplicateResolvable: true,
    
    /**
     * @cfg {Bool} hasDetailsPanel 
     */
    hasDetailsPanel: true,
    
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        // this.recordProxy = Tine.Addressbook.contactBackend;
        
        // check if felamimail is installed and user has run right and wants to use felamimail in adb
        if (Tine.Felamimail && Tine.Tinebase.common.hasRight('run', 'Felamimail') && Tine.Felamimail.registry.get('preferences').get('useInAdb')) {
            this.felamimail = (Tine.Felamimail.registry.get('preferences').get('useInAdb') == 1);
        }
        this.gridConfig.cm = this.getColumnModel();

        if (this.hasDetailsPanel) {
            this.detailsPanel = this.getDetailsPanel();
        }
        
        Tine.Addressbook.ContactGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * responsive content Renderer
     *
     * @param {String} folderId
     * @param {Object} metadata
     * @param {Folder|Account} record
     * @return {String}
     */
    oneColumnRenderer: function(folderId, metadata, record) {
        const block =  document.createElement('div');
        block.className = 'responsive-title';
        
        const iconEl = document.createElement('img');
        iconEl.src = (record.data.jpegphoto || '');
        iconEl.className = 'contact-image';
        
        // nameEl
        const nameEl = document.createElement('div');
        nameEl.innerText = record.data.n_fileas;
        nameEl.setAttribute('ext:qtip',  Ext.util.Format.htmlEncode(record.data.n_fileas));
        
        // companyEl
        const companyEl = document.createElement('div');
        companyEl.innerText = record.data.org_name;
        companyEl.setAttribute('ext:qtip',  Ext.util.Format.htmlEncode(record.data.org_name));
        
        const row1 =  document.createElement('div');
        const row1Left = document.createElement('div');
        const row1Right =  document.createElement('div');
        row1.className = 'responsive-grid-row ';
        row1Left.className = 'responsive-grid-row-left';
        row1Right.className = 'responsive-grid-row-right';
        nameEl.className = 'responsive-grid-text-medium';
        companyEl.className = 'responsive-grid-text-small';
        row1Left.appendChild(iconEl);
        row1Right.appendChild(nameEl);
        row1Right.appendChild(companyEl);
        
        row1.appendChild(row1Left);
        row1.appendChild(row1Right);
        block.appendChild(row1);
        return  block.outerHTML;
    },
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                hidden: true,
                resizable: true
            },
            columns: this.getColumns()
        });
    },
    
    /**
     * returns array with columns
     * 
     * @return {Array}
     */
    getColumns: function() {
        const cols =  Tine.Addressbook.ContactGridPanel.getBaseColumns(this.app.i18n)
            .concat(this.getModlogColumns().concat(this.getCustomfieldColumns()));
        return cols;
    },
    
    /**
     * @private
     */
    initActions: function() {
        this.actions_import = new Ext.Action({
            //requiredGrant: 'addGrant',
            text: this.app.i18n._('Import contacts'),
            disabled: false,
            handler: this.onImport,
            iconCls: 'action_import',
            scope: this,
            allowMultiple: true
        });
        
        // register actions in updater
        this.actionUpdater.addActions([
            this.actions_import
        ]);
        
        Tine.Addressbook.ContactGridPanel.superclass.initActions.call(this);
    },

    /**
     * get default / selected addressbook container
     *
     * @returns {Object|Tine.Tinebase.Model.Container}
     */
    getDefaultContainer: function() {
        return this.app.getMainScreen().getWestPanel().getContainerTreePanel().getDefaultContainer('defaultAddressbook');
    },
    
    /**
     * returns details panel
     * 
     * @private
     * @return {Tine.Addressbook.ContactGridDetailsPanel}
     */
    getDetailsPanel: function() {
        return new Tine.Addressbook.ContactGridDetailsPanel({
            recordClass: this.recordClass,
            gridpanel: this,
            il8n: this.app.i18n,
            felamimail: this.felamimail
        });
    }
});

// Static Methods

/**
 * tid renderer
 * 
 * @private
 * @return {String} HTML
 */
Tine.Addressbook.ContactGridPanel.contactTypeRenderer = function(data, cell, record) {
    const i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n;

    const hasAccount = ((record.get && record.get('account_id')) || record.account_id);
    const isEmailAccount = record.data?.type === 'email_account';
    
    const typeRenderer = hasAccount ? 'renderer_typeAccountIcon' : isEmailAccount ? 'renderer_typeEmailAccountIcon' : 'renderer_typeContactIcon';
    
    const cssClass = 'tine-grid-row-action-icon ' + typeRenderer;
    const qtipText = Tine.Tinebase.common.doubleEncode(hasAccount ? i18n._('Contact of a user account') : isEmailAccount ? i18n._('Email Account Contact') : i18n._('Contact'));
    
    return '<div ext:qtip="' + qtipText + '" style="background-position:0px;" class="' + cssClass + '">&#160</div>';
};

Tine.Addressbook.ContactGridPanel.displayNameRenderer = function(data) {
    var i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n;
    return data ?  Tine.Tinebase.EncodingHelper.encode(data) : ('<div class="renderer_displayNameRenderer_noName">' + i18n._('No name') + '</div>');
};

Tine.Addressbook.ContactGridPanel.countryRenderer = function(data) {
    data = Locale.getTranslationData('CountryList', data);
    return Ext.util.Format.htmlEncode(data);
};


/**
 * Column renderer adb preferred_address field
 * @param value
 * @return {*}
 */
Tine.Addressbook.ContactGridPanel.preferredAddressRenderer = function(value) {
    const i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n;
    switch (value) {
        case 'adr_one':
            return i18n._('Business');
        case 'adr_two':
            return i18n._('Private');
        default:
            return i18n._('Not set');
    }
};

/**
 * Column renderer adb preferred_email field
 * @return {*}
 * @param data
 * @param cell
 * @param record
 */
Tine.Addressbook.ContactGridPanel.preferredEmailRenderer = function(data, cell, record) {
    const field = record.get('preferred_email');
    return record.data[field] ?? '';
};

Tine.Addressbook.ContactGridPanel.languageRenderer = function(value) {
    var allLanguages = Locale.getTranslationList('Language');
    return allLanguages[value];
};

/**
 * Statically constructs the columns used to represent a contact. Reused by ListMemberGridPanel + ListMemberRoleGridPanel
 */
Tine.Addressbook.ContactGridPanel.getBaseColumns = function(i18n) {
    const columns = [
        { id: 'type', header: i18n._('Type'), renderer: Tine.Addressbook.ContactGridPanel.contactTypeRenderer.createDelegate(this), hidden: false, resizable: false, width: 30 },
        { id: 'jpegphoto', header: '<div class="action_image tine-grid-row-action-icon"></div>', tooltip: i18n._('Contact Image'), sortable: false, resizable: false, renderer: Tine.widgets.grid.imageRenderer, hidden: false, width: 30 },
        { id: 'attachments', sortable: false, hidden: false },
        { id: 'tags', header: i18n._('Tags'), renderer: Tine.Tinebase.common.tagsRenderer, hidden: false, fit: false },
        { id: 'salutation', header: i18n._('Salutation'), renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Addressbook', 'contactSalutation') },
        { id: 'container_id', header: Tine.Addressbook.Model.Contact.getContainerName(), width: 150, renderer: Tine.Tinebase.common.containerRenderer },
        { id: 'n_prefix', header: i18n._('Title'), width: 80},
        { id: 'n_middle', header: i18n._('Middle Name'), width: 80 },
        { id: 'n_family', header: i18n._('Last Name'), width: 80 },
        { id: 'n_given', header: i18n._('First Name'), width: 80 },
        { id: 'n_fn', header: i18n._('Full Name'), renderer: Tine.Addressbook.ContactGridPanel.displayNameRenderer },
        { id: 'n_fileas', header: i18n._('Display Name'), hidden: false, renderer: Tine.Addressbook.ContactGridPanel.displayNameRenderer },
        { id: 'org_name', header: i18n._('Company / Organisation'), width: 120, hidden: false },
        { id: 'org_unit', header: i18n._('Unit') },
        { id: 'title', header: i18n._('Job Title') },
        // { id: 'role', header: i18n._('Job Role'), dataIndex: 'role' },
        // { id: 'room', header: i18n._('Room'), dataIndex: 'room' },
        { id: 'adr_one_street', header: i18n._('Street') },
        { id: 'adr_one_locality', header: i18n._('City'), hidden: false },
        { id: 'adr_one_region', header: i18n._('Region') },
        { id: 'adr_one_postalcode', header: i18n._('Postalcode') },
        { id: 'adr_one_countryname', header: i18n._('Country'), renderer: Tine.Addressbook.ContactGridPanel.countryRenderer },
        { id: 'adr_two_street', header: i18n._('Street (private)') },
        { id: 'adr_two_locality', header: i18n._('City (private)') },
        { id: 'adr_two_region', header: i18n._('Region (private)') },
        { id: 'adr_two_postalcode', header: i18n._('Postalcode (private)') },
        { id: 'adr_two_countryname', header: i18n._('Country (private)'), renderer: Tine.Addressbook.ContactGridPanel.countryRenderer },
        { id: 'preferred_address', header: i18n._('Preferred Address'), renderer: Tine.Addressbook.ContactGridPanel.preferredAddressRenderer },
        { id: 'preferred_email', header: i18n._('Preferred E-Mail'), width: 150, hidden: false, renderer: Tine.Addressbook.ContactGridPanel.preferredEmailRenderer },
        { id: 'email', header: i18n._('Email'), width: 150},
        { id: 'tel_work', header: i18n._('Phone'), hidden: false },
        { id: 'tel_cell', header: i18n._('Mobile'), hidden: false },
        { id: 'tel_fax', header: i18n._('Fax') },
        { id: 'tel_car', header: i18n._('Car phone') },
        { id: 'tel_pager', header: i18n._('Pager') },
        { id: 'tel_home', header: i18n._('Phone (private)') },
        { id: 'tel_fax_home', header: i18n._('Fax (private)') },
        { id: 'tel_cell_private', header: i18n._('Mobile (private)') },
        { id: 'email_home', header: i18n._('Email (private)') },
        { id: 'url', header: i18n._('Web') },
        { id: 'url_home', header: i18n._('URL (private)') },
        { id: 'language', header: i18n._('Language'), renderer: Tine.Addressbook.ContactGridPanel.languageRenderer },
        { id: 'note', header: i18n._('Note') },
        { id: 'tz', header: i18n._('Timezone') },
        { id: 'geo', header: i18n._('Geo') },
        { id: 'bday', header: i18n._('Birthday'), renderer: Tine.Tinebase.common.dateRenderer },
        { id: 'color', header: i18n._('Color') }
    ];
    
    if (Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureIndustry')) {
        columns.push({ id: 'industry', header: i18n._('Industry'), renderer: Tine.Tinebase.common.foreignRecordRenderer});
    }
    if (Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureShortName')) {
        columns.push({ id: 'n_short', header: i18n._('Short Name'), width: 50 });
    }
    return columns;
};
