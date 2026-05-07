/*
 * tine Groupware
 * 
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 * 
 * TODO         play sound / vibrate?
 */
 
Ext.ns('Ext.ux.Notification');
    
Ext.ux.Notification = function(){
    return {
        show: function(title, text){
            // webkit notifications
            if (window.webkitNotifications !== undefined && window.webkitNotifications.checkPermission() == 0) { // 0 is PERMISSION_ALLOWED
                var notification = window.webkitNotifications.createNotification(Tine.logo, title, text);
                notification.show();
                setTimeout(function () {
                    notification.cancel();
                }, 15000);

            // Notification (see https://notifications.spec.whatwg.org/)
            } else if (window.Notification && window.Notification.permission == 'granted') {
                var notification = new window.Notification(title, {
                    icon: Tine.logo,
                    body: text
                });
            // default behaviour

            } else {
                Ext.ux.MessageBox.msg(title, Ext.util.Format.nl2br(text));
            }
        }
    };
}();
