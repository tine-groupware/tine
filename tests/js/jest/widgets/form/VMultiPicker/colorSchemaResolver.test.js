import { createColorSchemaResolver } from 'widgets/form/VMultiPicker/colorSchemaResolver'

const createRecord = (data) => ({
  data,
  get: (field) => data[field]
})

const getRecordSchema = (record) => record.get('color') ? { color: record.get('color') } : null

describe('VMultiPicker/colorSchemaResolver', () => {
  it('returns the colorset of the record itself', () => {
    const resolve = createColorSchemaResolver({ toRecord: createRecord, getRecordSchema })

    expect(resolve(createRecord({ color: '#FF6600' }))).toStrictEqual({ color: '#FF6600' })
    expect(resolve(createRecord({}))).toBeNull()
  })

  it('returns the colorset of the mapped record of cross records', () => {
    const resolve = createColorSchemaResolver({ isMetadataModelFor: 'event_type', toRecord: createRecord, getRecordSchema })

    // mapped record as plain data
    expect(resolve(createRecord({ color: '#000000', event_type: { color: '#339966' } }))).toStrictEqual({ color: '#339966' })
    // mapped record as record
    expect(resolve(createRecord({ event_type: createRecord({ color: '#3366FF' }) }))).toStrictEqual({ color: '#3366FF' })
  })

  it('returns null if the mapped record is missing', () => {
    const resolve = createColorSchemaResolver({ isMetadataModelFor: 'event_type', toRecord: createRecord, getRecordSchema })

    expect(resolve(createRecord({ event_type: null }))).toBeNull()
  })
})
