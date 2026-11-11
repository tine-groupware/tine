/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * html helpers for the common tag look (tag label and compact tag icons)
 * NOTE: styles are in Tinebase/styles/widgets/TagsPanel.scss
 */

const DEFAULT_COLOR = '#8d99a8';

const getData = (tag) => {
    return tag && typeof tag.beginEdit === 'function' ? tag.data : tag;
};

export const normalizeColor = (color) => {
    const hex = String(color || '').replace('#', '');
    return /^[0-9a-f]{6}$/i.test(hex) ? `#${hex}` : DEFAULT_COLOR;
};

/**
 * text color for text on given background color
 */
export const getTextColor = (color) => {
    const hex = normalizeColor(color).substring(1);
    const [r, g, b] = [0, 2, 4].map((i) => parseInt(hex.substring(i, i + 2), 16));
    // @see https://www.w3.org/TR/AERT/#color-contrast
    return (r * 299 + g * 587 + b * 114) / 1000 > 150 ? '#1d2330' : '#ffffff';
};

export const getStyle = (tag) => {
    const color = normalizeColor(getData(tag)?.color);
    return `--tw-tag-color:${color};--tw-tag-on:${getTextColor(color)};`;
};

/**
 * qtip (attribute value) of a tag
 */
export const getQtip = (tag) => {
    const data = getData(tag) || {};
    const encode = (v) => Tine.Tinebase.common.doubleEncode(v || '');
    return encode(data.name)
        + (data.type === 'personal' ? `&nbsp;<i>(${i18n._('personal')})</i>` : '')
        + (data.description ? `<hr>${encode(data.description)}` : '');
};

/**
 * tag label
 *
 * @param {Object|Tine.Tinebase.Model.Tag} tag
 * @param {Object} options
 *  - state {String} full|part|none (default full)
 *  - count {String} e.g. 2/3
 *  - cls {String} additional css classes
 *  - qtip {Boolean} (default true)
 */
export const label = (tag, options = {}) => {
    const data = getData(tag) || {};
    const state = options.state || 'full';
    const cls = ['tw-tag', 'dark-reverse']
        .concat(state === 'full' ? 'tw-tag-full' : (state === 'part' ? 'tw-tag-part' : []))
        .concat(data.type === 'personal' ? 'tw-tag-personal' : [])
        .concat(options.cls || [])
        .join(' ');
    const qtip = options.qtip === false ? '' : ` ext:qtip="${getQtip(data)}"`;
    const count = options.count ? `<span class="tw-tag-count">${options.count}</span>` : '';

    return `<span class="${cls}" style="${getStyle(data)}"${qtip}>`
        + `<span class="tw-tag-text">${Ext.util.Format.htmlEncode(data.name || '')}</span>${count}</span>`;
};

/**
 * compact tags (fanned tag icons) e.g. for grid cells
 *
 * @param {Array} tags
 * @param {Number} max number of icons
 */
export const compact = (tags, max = 3) => {
    const tagsData = (tags || []).map(getData).filter((tag) => tag && tag.name);
    if (!tagsData.length) {
        return '';
    }
    // NOTE: QuickTips only look at the hovered element -> qtip on the fan and on its icons
    const qtip = ` ext:qtip="${tagsData.map(getQtip).join('<br>')}"`;
    const icons = tagsData.slice(0, max).map((tag) => {
        return `<span class="tw-tag-icon dark-reverse" style="${getStyle(tag)}"${qtip}></span>`;
    }).join('');
    const more = tagsData.length > max ? `<span class="tw-tagfan-more"${qtip}>+${tagsData.length - max}</span>` : '';

    // NOTE: getQtip is already (double) encoded for attribute usage
    return `<span class="tw-tagfan"${qtip}>${icons}${more}</span>`;
};

/**
 * background of a row tinted with the colors of its tags (like a text marker)
 * - one tag: one color
 * - n tags: n colors side by side (horizontal), blended into each other
 *
 * @param {Array} tags
 * @param {Number} strength opacity of the tag colors in percent
 * @return {String} css background-image value, '' if there are no tags
 */
export const rowTint = (tags, strength = 14) => {
    const colors = (tags || []).map(getData).filter((tag) => tag && tag.id)
        .map((tag) => `color-mix(in srgb, ${normalizeColor(tag.color)} ${strength}%, transparent)`);
    if (!colors.length) {
        return '';
    }
    return `linear-gradient(to right, ${(colors.length > 1 ? colors : colors.concat(colors)).join(', ')})`;
};

export default { normalizeColor, getTextColor, getStyle, getQtip, label, compact, rowTint };
