import { fitTags } from 'widgets/form/VMultiPicker/fitTags'

const options = { minWidth: 50, overflowWidth: 20 }

describe('VMultiPicker/fitTags', () => {
  it('shows all tags in full width if there is enough space', () => {
    expect(fitTags([100, 200, 50], 400, options)).toStrictEqual({ visibleCount: 3, cap: Infinity })
    expect(fitTags([100, 200, 50], 350, options)).toStrictEqual({ visibleCount: 3, cap: Infinity })
  })

  it('shrinks the widest tags evenly', () => {
    // 50 and 100 keep their width, 200 gets the remaining 150
    expect(fitTags([100, 200, 50], 300, options)).toStrictEqual({ visibleCount: 3, cap: 150 })
    // 50 keeps its width, 150 and 200 share the remaining 250
    expect(fitTags([150, 200, 50], 300, options)).toStrictEqual({ visibleCount: 3, cap: 125 })
    // all tags get shrunk
    expect(fitTags([100, 200, 80], 180, options)).toStrictEqual({ visibleCount: 3, cap: 60 })
  })

  it('moves tags into overflow only if they would get narrower than minWidth', () => {
    // 3 tags would get 40 each -> 2 tags share 120 - 20 (overflow)
    expect(fitTags([100, 200, 80], 120, options)).toStrictEqual({ visibleCount: 2, cap: 50 })
    expect(fitTags([100, 200, 80], 100, options)).toStrictEqual({ visibleCount: 1, cap: 80 })
  })

  it('shrinks tags with equal widths evenly', () => {
    expect(fitTags([100, 100, 100], 240, options)).toStrictEqual({ visibleCount: 3, cap: 80 })
  })

  it('keeps all tags if they get exactly minWidth', () => {
    expect(fitTags([100, 100, 100], 150, options)).toStrictEqual({ visibleCount: 3, cap: 50 })
  })

  it('does not reserve overflow space if all tags fit', () => {
    expect(fitTags([100, 100], 200, options)).toStrictEqual({ visibleCount: 2, cap: Infinity })
  })

  it('moves the last tags into overflow, not the widest ones', () => {
    // the narrow last tag would fit, but tags are shown in display order
    expect(fitTags([200, 200, 30], 150, { minWidth: 70, overflowWidth: 20 })).toStrictEqual({ visibleCount: 1, cap: 130 })
  })

  it('never returns a negative cap', () => {
    expect(fitTags([100, 200], 0, options)).toStrictEqual({ visibleCount: 1, cap: 0 })
    expect(fitTags([100, 200], -10, options)).toStrictEqual({ visibleCount: 1, cap: 0 })
  })

  it('shows at least one tag', () => {
    expect(fitTags([100, 200], 40, options)).toStrictEqual({ visibleCount: 1, cap: 20 })
    expect(fitTags([100], 40, options)).toStrictEqual({ visibleCount: 1, cap: 40 })
    expect(fitTags([], 40, options)).toStrictEqual({ visibleCount: 0, cap: 40 })
  })
})
