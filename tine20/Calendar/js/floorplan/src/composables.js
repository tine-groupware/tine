import {reactive, computed, ref} from "vue"
import {toValue} from '@vueuse/core'

const generateUID = function(length) {
    length = length || 40;

    const s = '0123456789abcdef',
        uuid = new Array(length);
    for(let i=0; i<length; i++) {
        uuid[i] = s.charAt(Math.ceil(Math.random() *15));
    }
    return uuid.join('');
};

/**
 *
 * @param currentConfig
 * @param selectedDate
 * @param currentFloor
 * @returns {{reservationData: *, fetchReservations: ((function(): Promise<void>)|*), fetchingData: *, reserveTable: ((function(*): Promise<void>)|*), deleteReservation: ((function(*): Promise<void>)|*)}}
 */
const useReservationOperations = (currentConfig, selectedDate, currentFloor) => {
    /**
     * reactive obj to store reservation Data for `selectedDate` on `currentFloor`
     */
    const reservationData = reactive({})

    const fetchingData = ref(false)

    const baseUrl = window.location.protocol + '//' + window.location.host + '/' + 'index.php'
    const jsonKey = computed(() => currentConfig.value.jsonKey)
    const {jsonRPC} = useTineJsonRPC(baseUrl, jsonKey)

    /**
     * `computed` obj with `resourceName`:`id` of reservables
     */
    const resourceNameToObjMap = computed(() => {
        const m = {}
        currentFloor.value.resources.forEach(table => {
            const res = currentConfig.value.resources?.find(el => el.name === table.resourceName)
            m[table.resourceName] = res ? {model: res, config: table} : null
        })
        return m
    })

    const nextDay = function (date) {
        return new Date(new Date(date).getTime() + 24 * 60 * 60 * 1000).toLocaleDateString("sv")
    }

    const updateReservationData = (data) => {
        Object.keys(reservationData).forEach(key => delete reservationData[key])
        data.result.results.forEach(r => {
            const name = r.attendee.find(el => el.user_type === "resource")?.user_id?.name
            if (!name) return
            const reservedBy = r.attendee.find(el => el.user_type === "user")?.user_id
            if (!reservedBy) return
            const deletable = reservedBy.id === currentConfig.value.currentContact.id
            reservationData[name] = {event: r, deletable, eventId: r.id, reservedBy}
        })
    }

    /**
     * Fetches reservation data for all reservables on the `currentFloor` for the `selectedDate`
     * and sets the @reservationData reactiveObj
     *
     * @returns {Promise<void>}
     */
    const fetchReservations = async function (uiBlocking=true) {
        const values = Object.values(resourceNameToObjMap.value).map(element => element.model.id)
        const v = values
            .map(el => {
                return {user_type: "resource", user_id: el}
            })
            .filter(el => el.user_id !== null)
        if (v.length === 0 && values.length !== 0) throw new Error("No model specified for the reservables in Floor:", currentFloor.value.name)
        const params = [[
            {
                field: "period",
                operator: "within",
                value: {from: selectedDate.value, until: nextDay(selectedDate.value)}
            },
            {field: "attender", operator: 'in', value: v}
        ]]
        fetchingData.value = true && uiBlocking
        await jsonRPC("Calendar.searchEvents", params)
            .then(data => {
                updateReservationData(data)
            }).finally(() => {
                fetchingData.value = false && uiBlocking
            })
    }

    const _deleteReservationById = async function (ids) {
        return await jsonRPC("Calendar.deleteEvents", ids)
    }

    /**
     * Deletes calendar event related to the user and resourceName corresponding `reservableName`
     * @param reservableName resourceName of the Reservable Resource
     * @returns {Promise<void>}
     */
    const deleteReservation = async function (reservableName) {
        const d = reservationData[reservableName]
        if (d && d.deletable) {
            const params = [d.eventId]
            await _deleteReservationById(params)
            await fetchReservations()
        }
    }

    /**
     * Creates a Calendar Event with current user and the resource corresponding to `reservableName` if available
     * @param reservableName
     * @returns {Promise<void>}
     */
    const reserveTable = async function (reservableName) {
        const resObj = resourceNameToObjMap.value[reservableName].model
        if (!resObj) {
            console.error("No model specified for the ", reservableName, " in server")
            return
        }
        const id = resObj.id
        const saveLocation = resourceNameToObjMap.value[reservableName].config.eventSaveLocation
        let container_id;
        switch (saveLocation) {
            case "RESOURCE_CAL":
                container_id = resObj.container_id
                break
            default:
                container_id = null
        }
        const _id = generateUID()
        console.log(_id)
        const params = {
            recordData: {
                id: _id,
                summary: `Resource Reservation: ${reservableName}`,
                attendee: [
                    {
                        user_id: {
                            id: currentConfig.value.currentContact.id
                        },
                        user_type: "user",
                        status: "ACCEPTED",
                        transp: "TRANSPARENT"
                    },
                    {
                        user_id: {
                            id: id
                        },
                        user_type: "resource",
                        transp: "OPAQUE"
                    }
                ],
                // class: "PUBLIC",
                container_id: container_id ? {id: container_id} : null,
                dtstart: `${selectedDate.value} 00:00:00`,
                is_all_day_event: true,
                // transp: "TRANSPARENT"
            },
            // checkBusyConflicts: 1,
        }
        await jsonRPC("Calendar.saveEvent", params)
            .then(async data => {
                if (data.result) {
                    // check if the status on resource type attendee is "ACCEPTED"
                    // if not, delete the calendar event created
                    const res_status = data.result.attendee.find(el => el.user_type === "resource").status
                    if (res_status === "DECLINED") {
                        await _deleteReservationById([data.result.id])
                        console.error(`${reservableName} already reserved for given date`)
                    }
                } else {
                    console.error("Server Response Structure Error")
                    throw new Error(data)
                }
            })
        await fetchReservations()
    }

    return {
        fetchingData,
        reservationData,
        resourceNameToObjMap,
        reserveTable,
        deleteReservation,
        fetchReservations,
        jsonRPC
    }
}

const useTineJsonRPC = (url, jsonKey) => {
    let fetchId = 0
    const jsonRPC = async (method, params) => {
        const body = {
            jsonrpc: "2.0",
            method: method,
            id: ++fetchId,
            params: params
        }
        const u = `${url}?transactionid=${generateUID()}`
        return await fetch(u, {
            method: "POST",
            headers: {
                "content-type": "application/json",
                "X-Tine20-Request-Type" : 'JSON',
                "x-tine20-jsonkey": toValue(jsonKey)
            },
            body: JSON.stringify(body)
        }).then(res => res.json())
    }

    return {
       jsonRPC
    }
}

export {
    useReservationOperations,
    useTineJsonRPC
}