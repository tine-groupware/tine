import { isString, isFunction } from 'lodash'

const asString = async (m) => {
    if (m === null || m === undefined) return ''
    if (isFunction(m?.asString)) return m.asString()
    if (isFunction(m?.toString)) return m.toString()
    if (isString(m)) return m
}

export default asString