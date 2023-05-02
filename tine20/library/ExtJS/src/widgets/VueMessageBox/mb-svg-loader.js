const fs = require('fs')
const path = require('path')

const theme = 'tine'
const baseSVGDir = path.resolve(__dirname, `../../../../../images/dialog-personas/${theme}/`)

const svgMap = {}
const DEFAULT_PATTERN = "_DEFAULT"
fs.readdirSync(baseSVGDir).forEach(function(iconType){
    let iconTypeDir = `${baseSVGDir}/${iconType}`
    try{
        fs.readdirSync(iconTypeDir).forEach(function(spIconType){
            let _spIconType = spIconType
            let markAsDef = false
            if (spIconType.includes(DEFAULT_PATTERN)){
                markAsDef = true
                _spIconType = spIconType.replace(DEFAULT_PATTERN, '')
            }
            let spIconTypDir = `${iconTypeDir}/${spIconType}`
            const svgOpts = []
            // svgOpts.push(fileName)
            try{
                fs.readdirSync(spIconTypDir).forEach(function(svgName){
                    let fileName = `${theme}/${iconType}/${spIconType}/${svgName}`
                    svgOpts.push(fileName.replace(/\.[^/.]+$/, ""))
                })
                svgMap[`${iconType}_${_spIconType}`] = svgOpts
                if ( markAsDef ) svgMap[`${iconType}_default`] = svgOpts
            } catch(e){
                if (e.path && e.path.endsWith(".svg")){
                    _spIconType = spIconType.replace(/\.[^/.]+$/, "")
                    let fileName = `${theme}/${iconType}/${_spIconType}`
                    if(svgMap[iconType]){
                        svgMap[iconType].push(fileName)
                    } else {
                        svgMap[iconType] = [fileName]
                    }
                }
            }
        })
    } catch (e){
    }
})

module.exports = function() {
    this.cacheable()
    return `module.exports = { map: ${JSON.stringify(svgMap)}}`
}