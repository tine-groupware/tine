/**
 * Adjusts colors of html element
 * @param {Element} element
 */

const contrastColors = {
  adjustColors: (element) => {
    _.forEach(element.children, (c) => {
      contrastColors.adjustColors(c)
    })

    let bgColor = element.style.getPropertyValue('background-color'),
      fgColor = element.style.getPropertyValue('color')

    if (element.tagName === 'FONT' && fgColor === '') {
      fgColor = element.getAttribute('color')
    }

    if (bgColor === '' && fgColor !== '') {
      let realFg = window.getComputedStyle(element).getPropertyValue('color'),
        newBg = contrastColors.getContrastColor(realFg)
      if (newBg !== '') {
        element.style.backgroundColor = newBg
      }
      return
    }

    if (fgColor === '' && bgColor !== '') {
      let realBg = window.getComputedStyle(element).getPropertyValue('background-color'),
        newFg = contrastColors.getContrastColor(realBg)
      if (newFg !== '') {
        element.style.color = newFg
      }
    }
  },

  getContrastColor: (color) => {
    let r, g, b
    if (color.startsWith('#')) {
      let m = color.substr(1).match(color.length === 7 ? /(\S{2})/g : /(\S{1})/g);
      if (!m) return ''
      r = parseInt(m[0], 16)
      g = parseInt(m[1], 16)
      b = parseInt(m[2], 16)
    } else {
      let m = color.match(/(\d+)/g);
      if (!m) return ''
      r = m[0]
      g = m[1]
      b = m[2]
    }
    let brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000
    if (brightness > 128) {
      return '#000000'
    } else {
      return '#FFFFFF'
    }
  }
}

export { contrastColors }
