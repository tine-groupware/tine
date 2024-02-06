const iconPathMap = require("./mb-svg-loader!app-loader.js")
const DEFAULT = "tine/others/Mann_Lupe"

function getIconPath(iconName){
    const pathList = iconPathMap.map[iconName]
    const idx = Math.floor(Math.random() * pathList.length)
    return pathList.length ? pathList[idx] : DEFAULT
}

export default getIconPath