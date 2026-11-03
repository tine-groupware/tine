import {
  colorSchemata,
  compare,
  getColorField,
  getCustomSchema,
  getSchema,
  getTextColor,
  gray,
  normalizeColor,
  shadeColor,
  str2dec,
  stringToColour
} from 'util/colors'

const HEX_COLOR = /^#[0-9a-fA-F]{6}$/

const createRecordClass = (fieldNames, fieldDefs) => ({
  getFieldNames: () => fieldNames,
  ...(fieldDefs ? { getModelConfiguration: () => ({ fields: fieldDefs }) } : {})
})

describe('util/colors', () => {
  describe('normalizeColor', () => {
    it('strips the leading #', () => {
      expect(normalizeColor('#3366FF')).toBe('3366FF')
      expect(normalizeColor('3366ff')).toBe('3366ff')
    })

    it('returns null for invalid colors', () => {
      [null, undefined, '', '#null', 'FF66', 'GGGGGG', '#3366FF0', 'red'].forEach((color) => {
        expect(normalizeColor(color)).toBeNull()
      })
    })
  })

  describe('str2dec', () => {
    it('converts hex colors into rgb values', () => {
      expect(str2dec('#3366FF')).toStrictEqual([51, 102, 255])
      expect(str2dec('000000')).toStrictEqual([0, 0, 0])
    })

    it('returns undefined for invalid colors', () => {
      expect(str2dec('xyz')).toBeUndefined()
    })
  })

  describe('compare', () => {
    it('computes the difference of two colors', () => {
      expect(compare('#FFFFFF', '#000000')).toStrictEqual([255, 255, 255])
      expect(compare([10, 20, 30], '#000000', true)).toBe(60)
    })

    it('returns undefined for invalid colors', () => {
      expect(compare('xyz', '#000000')).toBeUndefined()
    })
  })

  describe('getTextColor', () => {
    it('returns black for bright colors', () => {
      ['#FFFFFF', '#FFFF00', '#C0C0C0'].forEach((color) => {
        expect(getTextColor(color)).toBe('#000000')
      })
    })

    it('returns white for dark colors', () => {
      ['#000000', '#000080', '#12AB34'].forEach((color) => {
        expect(getTextColor(color)).toBe('#FFFFFF')
      })
    })

    it('switches at brightness 128', () => {
      expect(getTextColor('#808080')).toBe('#FFFFFF')
      expect(getTextColor('#818181')).toBe('#000000')
    })
  })

  describe('shadeColor', () => {
    it('lightens colors', () => {
      const light = shadeColor(0.4, '#000000')
      expect(light).toMatch(HEX_COLOR)

      const [r, g, b] = str2dec(light)
      expect(r).toBeGreaterThan(0)
      expect(r).toBe(g)
      expect(g).toBe(b)
    })
  })

  describe('stringToColour', () => {
    it('returns a valid hex color', () => {
      ['', 'TA', 'CW', 'Cornelius Weiß'].forEach((str) => {
        expect(stringToColour(str)).toMatch(HEX_COLOR)
      })
    })

    it('is deterministic', () => {
      expect(stringToColour('TA')).toBe(stringToColour('TA'))
      expect(stringToColour('TA')).not.toBe(stringToColour('CW'))
    })
  })

  describe('getSchema', () => {
    it('returns predefined color sets of the palette', () => {
      expect(getSchema('#FF6600')).toBe(colorSchemata.FF6600)
      expect(getSchema('FF6600')).toBe(colorSchemata.FF6600)
    })

    it('computes color sets for custom colors', () => {
      const schema = getSchema('#12AB34')
      expect(schema.color).toBe('#12AB34')
      expect(schema.text).toBe('#FFFFFF')
      expect(schema.light).toMatch(HEX_COLOR)
      expect(schema.lightText).toMatch(/^#(000000|FFFFFF)$/)
    })

    it('passes normalized custom colors to getCustom', () => {
      const getCustom = jest.fn(() => 'custom')
      expect(getSchema('#12AB34', { getCustom })).toBe('custom')
      expect(getCustom).toHaveBeenCalledWith('12AB34')
    })

    it('returns gray for invalid colors', () => {
      [null, undefined, '', '#null', 'FF66', 'GGGGGG'].forEach((color) => {
        expect(getSchema(color)).toBe(gray)
      })
    })
  })

  describe('getCustomSchema', () => {
    it('computes all properties of a color set', () => {
      expect(Object.keys(getCustomSchema('12AB34')).sort()).toStrictEqual(['color', 'light', 'lightText', 'text'])
    })
  })

  describe('getColorField', () => {
    it('finds a field named color', () => {
      expect(getColorField(createRecordClass(['name', 'color'], { name: { type: 'string' }, color: { type: 'string' } }))).toBe('color')
    })

    it('finds a field of type hexcolor', () => {
      expect(getColorField(createRecordClass(['name', 'foo'], { foo: { type: 'hexcolor' } }))).toBe('foo')
    })

    it('counts a field named color of type hexcolor once', () => {
      expect(getColorField(createRecordClass(['color'], { color: { type: 'hexcolor' } }))).toBe('color')
    })

    it('returns null if the color field is ambiguous', () => {
      expect(getColorField(createRecordClass(['color', 'bg'], { color: { type: 'hexcolor' }, bg: { type: 'hexcolor' } }))).toBeNull()
    })

    it('returns null if there is no color field', () => {
      expect(getColorField(createRecordClass(['name'], { name: { type: 'string' } }))).toBeNull()
      expect(getColorField(null)).toBeNull()
    })

    it('copes with record classes without modelconfig', () => {
      expect(getColorField(createRecordClass(['name', 'color']))).toBe('color')
    })
  })
})
