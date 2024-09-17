/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Addressbook');

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ListEditDialogRoleGridPanel
 * @extends     Ext.grid.EditorGridPanel
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * NOTE: this is currently only used in ListEditDialog
 */
Tine.Addressbook.ListEditDialogRoleGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    usePagingToolbar: false,

    autoExpandColumn: 'name',
    memberroles: null,

    // the list record
    record: null,

    // deactivate some fns
    initActions: Ext.emptyFn,
    initFilterPanel: Ext.emptyFn,

    /**
     * init component
     */
    initComponent: function() {
        this.recordClass = Tine.Addressbook.Model.ListRole;
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Addressbook');

        this.gridConfig.cm = new Ext.grid.ColumnModel({
            defaults: {
                resizable: true
            },
            columns: this.getColumns()
        });

        this.title = this.app.i18n._('Group Functions')

        Tine.Addressbook.ListEditDialogRoleGridPanel.superclass.initComponent.call(this);
    },

    getColumns: function() {
        const columns = [
            { id: 'id', header: this.app.i18n._("ID"), width: 50, hidden: true }, 
            { id: 'name', header: this.app.i18n._("Name"), width: 100 }, 
            { id: 'members', header: this.app.i18n._("Members"), width: 150, renderer: this.memberRenderer}
        ];

        return columns;
    },

    /**
     * fill store with list roles and the members
     *
     * @param [] memberroles
     */
    setListRoles: function(memberroles) {
        Ext.each(memberroles, function(memberrole) {
            this.updateListRole(memberrole);
        }, this);
    },

    updateListRole: function(memberrole) {
        // check if already in store
        var listRoleId = memberrole.list_role_id.id;

        if (listRoleId) {
            var listRole = this.getStore().getById(listRoleId),
                insertNew = false,
                members = [],
                newMember = true;

            if (! listRole) {
                listRole = new this.recordClass(memberrole.list_role_id);
                insertNew = true;
                members = [memberrole.contact_id];
            } else {
                members = listRole.get('members');

                Ext.each(members, function(member) {
                    if (member.id == memberrole.contact_id.id) {
                        // already id
                        newMember = false;
                    }
                });

                if (! newMember) {
                    return;
                }

                members.push(memberrole.contact_id)
            }

            listRole.set('members', members);
            listRole.commit();

            if (insertNew) {
                this.getStore().insert(0, listRole);
            }
        }
    },

    /**
     * TODO implement removal of a memberrole OR reset role members after each update
     *
     * @param contact
     */
    setListRolesOfContact: function(contact) {
        Ext.each(contact.get('memberroles'), function(memberrole) {
            memberrole.contact_id = Ext.copyTo({}, contact.data, 'id,n_fn');
            this.updateListRole(memberrole);
        }, this);
    },

    memberRenderer: function(value) {
        if (Ext.isArray(value)) {
            var result = [];
            Ext.each(value, function(contact) {
                if (contact) {
                    result.push(contact.n_fn);
                }
            });
            return result.toString();
        }

        return '';
    }
});
