
const defaultConf = {
    fontFamily: 'monospace',
    fontSize: 12
}

let num = 0
const getRenderer = (mode) => {
    const config = Object.assign({...defaultConf}, mode)
    config.mode = `ace/mode/${mode.mode || mode}`
    return (string) => {
        const id = `ace-renderer-${num++}`
        import(/* webpackChunkName: "Tinebase/js/ace" */ 'widgets/ace').then(() => {
            const ed = ace.edit(id, config)
            ed.setValue(string)
            ed.setReadOnly(true)
            ed.clearSelection()
        })
        return `<div class="x-form-display-field" style="height: 100%; overflow: hidden; display: block;" id="${id}">${Ext.util.Format.htmlEncode(string)}</div>`
    }
}
export default getRenderer