/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * computes the max width all tags have to share so that their sum fits into available.
 * tags narrower than the cap keep their natural width ("water filling")
 *
 * @param {number[]} widths natural widths
 * @param {number} available
 * @return {number} cap (Infinity if all tags fit)
 */
const getCap = (widths, available) => {
  const sorted = [...widths].sort((a, b) => a - b)
  let remaining = available
  for (let i = 0; i < sorted.length; i++) {
    const share = remaining / (sorted.length - i)
    if (sorted[i] > share) return share
    remaining -= sorted[i]
  }
  return Infinity
}

/**
 * layout single line tags: show tags with natural width if possible,
 * shrink the widest tags evenly if space gets short and only move tags into overflow (ellipsis)
 * if tags would get narrower than minWidth
 *
 * @param {number[]} widths natural (outer) widths of the tags in display order
 * @param {number} available available width
 * @param {object} options
 * @param {number} options.minWidth min (outer) width of a shrunk tag
 * @param {number} options.overflowWidth (outer) width of the overflow indicator
 * @return {{visibleCount: number, cap: number}} number of tags to show and max (outer) width per tag
 */
export const fitTags = (widths, available, { minWidth = 0, overflowWidth = 0 } = {}) => {
  for (let count = widths.length; count > 0; count--) {
    const cap = getCap(widths.slice(0, count), available - (count < widths.length ? overflowWidth : 0))
    if (cap >= minWidth) {
      return { visibleCount: count, cap }
    }
  }

  // not even one tag fits with minWidth -> show first tag as wide as possible
  return {
    visibleCount: Math.min(1, widths.length),
    cap: Math.max(0, available - (widths.length > 1 ? overflowWidth : 0))
  }
}

export default fitTags
