/*
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jan Evers <j.evers@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Felamimail');

/**
 * @namespace Tine.Felamimail
 * @class     Tine.Felamimail.sieve.EditSieveScriptWindow
 * Edit sieve script window
 */
Tine.Felamimail.sieve.EditSieveScriptWindow = function(config) {
  Ext.apply(this, config);
}

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.sieve.EditSieveScriptWindow
 * @extends     Ext.Action
 */
Ext.extend(Tine.Felamimail.sieve.EditSieveScriptWindow, Ext.Action, {

  /**
   * Show custom sieve script window
   */
  showEditSieveCustomScriptWindow: async function (custom = true) {
    let result
    if (this.asAdminModule) {
      result = custom ? await Tine.Admin.getSieveCustomScript(this.accountId)
        : await Tine.Admin.getSieveScript(this.accountId)
    } else {
      result = await Tine.Felamimail.getSieveCustomScript(this.accountId)
    }

    this.script = result?.script;

    const accountId = this.accountId
    const windowTitle =  this.app.i18n._('Edit Sieve custom script');
    const notCustomError = this.app.i18n._('Only custom scripts can be modified.');
    let dialog = new Tine.Tinebase.dialog.Dialog({
      items: [{
        xtype: 'tw-acefield',
        mode: 'text',
        fieldLabel: 'custom script',
        id: 'sieve_custom_script',
        allowBlank: true,
        height: 200,
        value: this.script,
      }],
      isCustom: custom,
      initComponent: function() {
        this.fbar = [
          '->',
          {
            text: i18n._('Cancel'),
            minWidth: 70,
            ref: '../buttonApply',
            scope: this,
            handler: async () => {
              this.onButtonApply();
            },
            iconCls: 'action_cancel'
          },
          {
            text: i18n._('Ok'),
            minWidth: 70,
            ref: '../buttonApply',
            scope: this,
            handler: async () => {
              try {
                if (!this.isCustom) {
                  throw new Ext.Error(notCustomError);
                }
                let script = dialog.getForm().findField('sieve_custom_script').getValue();
                const sieveCustomScript = this.asAdminModule ? await Tine.Admin.saveSieveCustomScript(accountId, script)
                  : await Tine.Felamimail.saveSieveCustomScript(accountId, script);
                this.onButtonApply();
              } catch (e) {
                Ext.MessageBox.alert(i18n._('Errors'), e.message);
              }
            },
            iconCls: 'action_saveAndClose'
          }
        ];
        Tine.Tinebase.dialog.Dialog.superclass.initComponent.call(this);
      },

      openWindow: function (config) {
        if (this.window) return this.window;

        config = config || {};
        this.window = Tine.WindowFactory.getWindow(Ext.apply({
          resizable:false,
          title: windowTitle,
          closeAction: 'close',
          modal: true,
          width: 550 ,
          height: 400,
          items: [this],
          fbar: ['->']
        }, config));

        return this.window;
      },
    });

    dialog.openWindow();
  },


  /**
   * Show window for script reading
   */
  showSieveScriptWindow: async function () {
    const script = this.asAdminModule ? await Tine.Admin.getSieveScript(this.accountId)
        : await Tine.Felamimail.getSieveScript(this.accountId);
    const windowTitle = this.app.i18n._('Explore Sieve script');
    const dialog = new Tine.Tinebase.dialog.Dialog({
      items: [{
        cls: 'x-ux-display-background-border',
        xtype: 'ux.displaytextarea',
        type: 'code/folding/mixed',
        height: 300,
        value: script,
        listeners: {
          render: async (cmp) => {
            // wait ace editor
            await waitFor(() => {
              return cmp.el.child('.ace_content');
            });

            cmp.el.setStyle({'overflow': null});
          }
        },
      }],

      initComponent: function() {
        this.fbar = [
          '->',
          {
            text: i18n._('Ok'),
            minWidth: 70,
            ref: '../buttonApply',
            scope: this,
            handler: this.onButtonApply,
            iconCls: 'action_saveAndClose'
          }
        ];
        Tine.Tinebase.dialog.Dialog.superclass.initComponent.call(this);
      },

      openWindow: function (config) {
        if (this.window) {
          return this.window;
        }

        config = config || {};
        this.window = Tine.WindowFactory.getWindow(Ext.apply({
          resizable:false,
          title: windowTitle,
          closeAction: 'close',
          modal: true,
          width: 550 ,
          height: 400,
          items: [this],
          fbar: ['->']
        }, config));

        return this.window;
      },
    });

    dialog.openWindow();
  },

  checkAccountEditRight(account) {
    if (this.asAdminModule) {
      return Tine.Tinebase.common.hasRight('manage_emailaccounts', 'Admin');
    } else {
      if (account.data?.type === 'shared' || account.data?.type === 'adblist') {
        return account.data?.account_grants?.editGrant;
      }
      return true;
    }
  }
})

Ext.reg('Tine.Felamimail.sieve.EditSieveScriptWindow', Tine.Felamimail.sieve.EditSieveScriptWindow);
