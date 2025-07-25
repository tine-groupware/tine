import './TwigTemplate/GridPanel'
import './TwigTemplate/DiffAction'

Tine.Admin.registerItem({
    text: 'Templates', // _('Templates')
    iconCls: 'admin-node-templates',
    pos: 900,
    dataPanelType: "Tine.Tinebase.TwigTemplateGridPanel",
    hidden: !Tine.Admin.showModule('templates')
});
