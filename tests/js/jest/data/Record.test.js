import Record from "data/Record";

let RecordCls;

describe('data/Record', () => {
    beforeAll(() => {
        RecordCls = Record.create([
            {name: 'name'},
            {name: 'date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
        ], {
            appName: 'Tinebase',
            modelName: 'Test',
            idProperty: 'id',
            titleProperty: 'name',
            recordName: 'TestRecord',
            recordsName: 'TestRecords'
        });
    })

    it('creates records by constructor', () => {
        expect(RecordCls).toBeInstanceOf(Function)
        const record = new RecordCls({id: 'test-id'})
        expect(record).toBeInstanceOf(Record)
        expect(record.id).toEqual('test-id')

    })

    it('assigns auto ids', () => {
        expect(RecordCls).toBeInstanceOf(Function)
        expect(typeof new RecordCls({}).id).toBe('number')
    })

    it('creates records statically by setFromJson', () => {
        // @TODO deal with Date prototype overwrites
        const testRecord = Record.setFromJson(JSON.stringify({id: 'abc', name: 'testname'/*, date: '2024-12-11 17:50:32'*/}), RecordCls)
        expect(testRecord).toBeInstanceOf(Record)
        expect(testRecord.id).toEqual('abc')
    })

    it('can track modifications', () => {
        const testRecord = Record.setFromJson(JSON.stringify({id: 'abc', name: 'testname'/*, date: '2024-12-11 17:50:32'*/}), RecordCls)
        expect(testRecord.dirty).toEqual(false)
        expect(testRecord.editing).toEqual(false)
        // expect(testRecord.phantom).toEqual(true)
        expect(testRecord.isModified('name')).toEqual(false)

        testRecord.set('name', 'update')
        expect(testRecord.get('name')).toEqual('update')
        expect(testRecord.isModified('name')).toEqual(true)
        expect(testRecord.dirty).toEqual(true)
        expect(testRecord.getChanges()).toEqual({ name: 'update' })
        expect(testRecord.modified).toEqual({ name: 'testname' })

        const recordData = testRecord.getData()
        expect(recordData.__meta.dirty).toEqual(true)
        expect(recordData.__meta.modified.name).toEqual('testname')
        expect(recordData.name).toEqual('update')
    })

    it('copes with magic customfield names', () => {
        const record = new RecordCls({})
        record.set('#cftest', 'value')
        expect(record.get('#cftest')).toEqual('value')
        expect(JSON.stringify(record.data.customfields)).toEqual(JSON.stringify({cftest: 'value'}))
    })

    it('generates unique  UIDs in different length', () => {
        expect(Record.generateUID().length).toBe(40)
        expect(Record.generateUID(5).length).toBe(5)
        expect(Record.generateUID()).not.toEqual(Record.generateUID())
    })
});