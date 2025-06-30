import { isString, isFunction } from 'lodash'

const asString = async (m) => {
    if (m === null || m === undefined) return ''
    if (isString(m)) return m
    if (isFunction(m?.toString)) return m.toString()
    if (isFunction(m?.asString)) return m.asString()

}

export default asString