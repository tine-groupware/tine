Tine.Felamimail.PGPDetailsPanel = Ext.extend(Ext.Component, {
    render: function(el) {
        var me = this,
            body = Ext.get(el),
            height = me.detailsPanel.el.getHeight() - (body.getXY()[1] - me.detailsPanel.el.getXY()[1]);

        me.detailsPanel.on('resize', function() {
            body.setHeight(me.detailsPanel.el.getHeight() - (body.getXY()[1] - me.detailsPanel.el.getXY()[1]));
        }, me);

        me.detailsPanel.getLoadMask().show();
        body.dom.id = Ext.id();
        body.setHeight(height);

        Tine.Felamimail.mailvelopeHelper.getKeyring().then(function (keyring) {
            var armoredMsg = me.preparedPart.preparedData;
            mailvelope.createDisplayContainer('#' + body.dom.id, armoredMsg, keyring).then(function() {
                me.detailsPanel.getLoadMask().hide();
            })['catch'](function(error) {
                Tine.log.warn(arguments);
                var app = Tine.Tinebase.appMgr.get('Felamimail'),
                    msg = app.i18n._('Mailvelope Decryption Error [{0}]');

                me.detailsPanel.getLoadMask().hide();
                Ext.Msg.alert(String.format(msg, error.code), error.message, function() {
                    me.detailsPanel.record.set('preparedParts', '');
                    me.detailsPanel.currentId = '';
                    me.detailsPanel.updateDetails(me.detailsPanel.record, body);
                }, this);
            });
        })['catch'](function() {
            var app = Tine.Tinebase.appMgr.get('Felamimail'),
                msg = app.i18n._('To decrypt this message, please install {0} with API support enabled');

            msg = String.format(msg, '<a href="https://www.mailvelope.com" target="_blank">Mailvelope</a>');
            Ext.Msg.alert(app.i18n._('PGP Encrypted Message'), msg);

            me.detailsPanel.getLoadMask().hide();
        });
    }
});
Tine.Felamimail.MimeDisplayManager.register('application/pgp-encrypted', Tine.Felamimail.PGPDetailsPanel);
