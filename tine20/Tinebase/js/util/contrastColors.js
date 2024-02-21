/**
 * Adjusts colors of html element
 * @param {Element} element
 */

const contrastColors = {
  findBackground: (element, brightnesses = [], isRoot = true) => {
    if (element.innerHTML === '') {
      return;
    }

    _.forEach(element.children, (c) => {
      if (c.classList.contains('felamimail-body-signature-current')) {
        return;
      }
      if (c.classList.contains('felamimail-body-blockquote') || c.classList.contains('felamimail-body-forwarded')) {
        // quoted email gets its own background
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

      let count = brightnesses.length
      let sum = brightnesses.reduce((a, current) => {
        return a + current
      }, 0)

      let brightness = sum / count;
      if (brightness > 160) {
        if (contrastColors.getBrightness(getComputedStyle(element).backgroundColor) > 128
          || getComputedStyle(element).backgroundColor === 'rgba(0, 0, 0, 0)')
        {
          element.style.backgroundColor = '#171717'
          element.style.color = '#ffffff'
        }
      } else if (brightness < 95) {
        if (contrastColors.getBrightness(getComputedStyle(element).backgroundColor) <= 128) {
          element.style.backgroundColor = '#F3F6F7'
          element.style.color = '#171717'
        }
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
