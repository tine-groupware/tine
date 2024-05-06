/**
 * returns matching size class
 * @param {Number} px
 * @param columns
 */
const getLayoutClassByWidth = (px, columns) => {
    const config = getDefaultLayoutClasses(columns);
    let result = config[config.length - 1];
    config.some((config) => {
        if (px <= config.width) {
            result = config;
            return true;
        }
    })
    return result;
}

const getLayoutClassByMode = (mode, columns) => {
    const config = getDefaultLayoutClasses(columns);
    let result = config[config.length - 1];
    config.some((config) => {
        if (mode === config.name) {
            result = config;
            return true;
        }
    })
    return result;
}

const getDefaultLayoutClasses = (columns = []) => {
    const defaultConfigs = [
        {level: 0, name: 'oneColumn', width: 400},
        {level: 1, name: 'small', width: 600},
        {level: 2, name: 'medium', width: 1000},
        {level: 3, name: 'big', width: 1800},
        {level: 4, name: 'large', width: Infinity},
    ];
    const supportLevels = ['oneColumn', 'big'].concat([...new Set(columns.map((col) => col?.responsiveLevel).filter(Boolean))]);
    return defaultConfigs.filter(config => supportLevels.includes(config.name));
}

export {
    getLayoutClassByWidth,
    getLayoutClassByMode,
}
