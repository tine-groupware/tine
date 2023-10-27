/**
 * returns matching size class
 * @param {Number} px
 * @param {Object} clss {clsName: px}
 */
const getCls = (px, clss) => {
    const clsNames = Object.keys(clss)
    for (let idx=0; idx<=clsNames.length; idx++) {
        if (px <= clss[clsNames[idx]]) break
    }
    return clsNames[idx]
}

export {
    getCls
}