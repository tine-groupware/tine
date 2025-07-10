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

    it('supports lazy init', () => {
        const LogEntry = Record.create([], {
            appName: 'Tinebase',
            modelName: 'LogEntry',
        });

        // Tine.Tinebase.registry.get('models').LogEntry
        LogEntry.setModelConfiguration({
            "containerProperty": null,
            "extendsContainer": null,
            "containersName": null,
            "containerName": null,
            "grantsModel": "Tinebase_Model_Grants",
            "defaultSortInfo": null,
            "fieldKeys": [
                "transaction_id",
                "request_id",
                "user",
                "timestamp",
                "logdifftime",
                "logruntime",
                "priority",
                "priorityName",
                "message",
                "id"
            ],
            "filterModel": {
                "transaction_id": {
                    "filter": "Tinebase_Model_Filter_Text"
                },
                "request_id": {
                    "filter": "Tinebase_Model_Filter_Text"
                },
                "user": {
                    "filter": "Tinebase_Model_Filter_User"
                },
                "timestamp": {
                    "filter": "Tinebase_Model_Filter_DateTime"
                },
                "logdifftime": {
                    "filter": "Tinebase_Model_Filter_Text"
                },
                "logruntime": {
                    "filter": "Tinebase_Model_Filter_Text"
                },
                "priority": {
                    "filter": "Tinebase_Model_Filter_Int"
                },
                "priorityName": {
                    "filter": "Tinebase_Model_Filter_Text"
                },
                "message": {
                    "filter": "Tinebase_Model_Filter_Text"
                },
                "id": {
                    "filter": "Tinebase_Model_Filter_Id",
                    "options": {
                        "idProperty": "id",
                        "modelName": "Tinebase_Model_LogEntry"
                    }
                },
                "query": {
                    "label": "Quick Search",
                    "field": "query",
                    "filter": "Tinebase_Model_Filter_Query",
                    "useGlobalTranslation": true,
                    "options": {
                        "fields": [
                            "priorityName",
                            "message"
                        ],
                        "modelName": "Tinebase_Model_LogEntry"
                    }
                }
            },
            "defaultFilter": "query",
            "requiredRight": null,
            "singularContainerMode": true,
            "fields": {
                "transaction_id": {
                    "type": "string",
                    "length": 255,
                    "nullable": true,
                    "validators": {
                        "allowEmpty": true
                    },
                    "label": "Client Requestid",
                    "fieldName": "transaction_id",
                    "key": "transaction_id"
                },
                "request_id": {
                    "type": "string",
                    "length": 255,
                    "nullable": true,
                    "validators": {
                        "allowEmpty": true
                    },
                    "label": "Server Requestid",
                    "fieldName": "request_id",
                    "key": "request_id"
                },
                "user": {
                    "label": "User",
                    "type": "user",
                    "validators": {
                        "allowEmpty": true
                    },
                    "fieldName": "user",
                    "key": "user",
                    "length": 40,
                    "config": {
                        "appName": "Tinebase",
                        "modelName": "User",
                        "type": "record",
                        "recordClassName": "Tinebase_Model_User",
                        "controllerClassName": "Tinebase_User",
                        "filterClassName": "Tinebase_Model_FullUserFilter"
                    }
                },
                "timestamp": {
                    "label": "Logtime",
                    "validators": {
                        "allowEmpty": true
                    },
                    "type": "datetime",
                    "fieldName": "timestamp",
                    "key": "timestamp"
                },
                "logdifftime": {
                    "label": "Difftime",
                    "validators": {
                        "allowEmpty": true
                    },
                    "type": "string",
                    "fieldName": "logdifftime",
                    "key": "logdifftime"
                },
                "logruntime": {
                    "label": "Runtime",
                    "validators": {
                        "allowEmpty": true
                    },
                    "type": "string",
                    "fieldName": "logruntime",
                    "key": "logruntime"
                },
                "priority": {
                    "type": "integer",
                    "nullable": false,
                    "validators": {
                        "allowEmpty": true
                    },
                    "fieldName": "priority",
                    "key": "priority"
                },
                "priorityName": {
                    "type": "string",
                    "nullable": false,
                    "validators": {
                        "allowEmpty": true
                    },
                    "label": "Loglevel",
                    "queryFilter": true,
                    "allowCamelCase": true,
                    "fieldName": "priorityName",
                    "key": "priorityName"
                },
                "message": {
                    "type": "text",
                    "nullable": true,
                    "validators": {
                        "allowEmpty": true
                    },
                    "label": "Logstring",
                    "queryFilter": true,
                    "fieldName": "message",
                    "key": "message"
                },
                "id": {
                    "id": true,
                    "label": "ID",
                    "validators": {
                        "allowEmpty": true
                    },
                    "length": 40,
                    "shy": true,
                    "filterDefinition": {
                        "filter": "Tinebase_Model_Filter_Id",
                        "options": {
                            "idProperty": "id",
                            "modelName": "Tinebase_Model_LogEntry"
                        }
                    },
                    "fieldName": "id",
                    "type": "string",
                    "key": "id"
                }
            },
            "defaultData": [],
            "titleProperty": "transaction_id",
            "multipleEdit": null,
            "multipleEditRequiredRight": null,
            "languagesAvailable": null,
            "uiconfig": null,
            "delegateAclField": null,
            "copyEditAction": null,
            "copyOmitFields": null,
            "recordName": "LogEntry",
            "recordsName": "LogEntries",
            "appName": "Tinebase",
            "modelName": "LogEntry",
            "createModule": false,
            "moduleName": null,
            "isDependent": false,
            "hasCustomFields": false,
            "hasSystemCustomFields": null,
            "modlogActive": false,
            "hasNotes": false,
            "hasAttachments": false,
            "hasAlarms": null,
            "idProperty": "id",
            "splitButton": false,
            "attributeConfig": null,
            "hasPersonalContainer": true,
            "import": null,
            "export": null,
            "virtualFields": [],
            "group": null,
            "copyNoAppendTitle": null,
            "denormalizationOf": null,
            "isMetadataModelFor": null
        })

        // lazy init
        expect(LogEntry.getPhpClassName()).toEqual('Tinebase_Model_LogEntry')


        const timestampField = LogEntry.getField('timestamp')

        // mc converted to ext field
        expect(timestampField).toHaveProperty('type', 'date')
        expect(timestampField).toHaveProperty('dateFormat', 'Y-m-d H:i:s')
        expect(timestampField).toHaveProperty('label', 'Logtime')
    })


});