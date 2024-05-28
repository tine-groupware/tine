/**
 * Adjusts colors of html element
 * @param {Element} element
 */

const contrastColors = {
  lightFg: '#FFFFFF',
  darkFg: '#171717',
  lightBg: '#F3F6F7',
  darkBg: '#171717',
  darkMode: false,

  findBackground: (element, brightnesses = [], isRoot = true) => {
    if (element.innerHTML === '') {
      return;
    }

    _.forEach(element.children, (c) => {
      if (c.classList.contains('felamimail-body-signature-current')) {
        return;
      }
      if (c.classList.contains('felamimail-body-blockquote') || c.classList.contains('felamimail-body-forwarded')) {
        contrastColors.findBackground(c)
      } else {
        contrastColors.findBackground(c, brightnesses, false)
      }
    })

    let bgColor = element.style.getPropertyValue('background-color'),
        fgColor = element.style.getPropertyValue('color')

    if (element.tagName === 'FONT' && fgColor === '') {
      fgColor = element.getAttribute('color') || ''
    }

    if (bgColor === '' && fgColor !== '') {
      brightnesses.push(contrastColors.getBrightness(fgColor))
    }

    if (isRoot) {
      if (brightnesses.length === 0) {
        return
      }

      let sum = brightnesses.reduce((a, current) => {
        return a + current
      }, 0)

      let computedBg = getComputedStyle(element).backgroundColor

      let brightness = sum / brightnesses.length;
      if (brightness > 160) {
        if (contrastColors.getBrightness(computedBg) > 160
          || computedBg === 'rgba(0, 0, 0, 0)')
        {
          element.style.backgroundColor = contrastColors.darkBg
          element.style.color = contrastColors.lightFg
          computedBg = element.style.backgroundColor
        }
      } else if (brightness < 95) {
        if (contrastColors.getBrightness(computedBg) < 95) {
          element.style.backgroundColor = contrastColors.lightBg
          element.style.color = contrastColors.darkFg
          computedBg = element.style.backgroundColor
        }
      }

      if (contrastColors.darkMode) {
        contrastColors.adaptFg(element, computedBg)
      }
    }
  },

  adaptFg: (element, outerBg = '') => {
    let bgColor = element.style.getPropertyValue('background-color')
    if (bgColor !== '' && bgColor !== 'transparent') {
      outerBg = bgColor
    }

    _.forEach(element.children, (c) => {
      contrastColors.adaptFg(c, outerBg)
    })

    let hasText = false
    _.forEach(element.childNodes, (n) => {
      if (n.nodeType === Node.TEXT_NODE && n.nodeValue !== '') {
        hasText = true
      }
    })
    if (!hasText) {
      return
    }
    let computedBg = element.style.getPropertyValue('background-color')
    if (computedBg === '') {
      computedBg = outerBg
    }

    let fgColor = element.style.getPropertyValue('color')
    if (element.tagName === 'FONT' && fgColor === '') {
      fgColor = element.getAttribute('color') || ''
    }
    if (fgColor === '') {
      fgColor = contrastColors.darkMode ? contrastColors.lightBg : contrastColors.darkFg
    }
    if (fgColor !== '') {
      let brightness = contrastColors.getBrightness(fgColor)
      let brightnessBg = contrastColors.getBrightness(computedBg)
      if (brightness < 95 && brightnessBg < 95) {
        element.style.color = contrastColors.lightFg
      }
      if (brightness > 160 && brightnessBg > 160) {
        element.style.color = contrastColors.darkFg
      }
    }
  },

  getBrightness: (color) => {
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
    return ((r * 299) + (g * 587) + (b * 114)) / 1000
  }
}

export { contrastColors }
