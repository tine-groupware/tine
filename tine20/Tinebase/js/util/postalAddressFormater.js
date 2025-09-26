let formatAddress
let importPromise
const countryNamesCache = {}

import { find } from 'lodash'

const getFormater = async () => {
    if (! formatAddress) {
        if (! importPromise) {
            importPromise = import(/* webpackChunkName: "Tinebase/js/localized-address-format" */  'localized-address-format')
        }
        const lib = await importPromise;
        formatAddress = lib.formatAddress
    }
    return formatAddress
}

const getOriginCountry = () => {
    const userContact = Tine.Tinebase.registry.get('userContact')
    return String(userContact[(userContact.preferred_address || 'adr_one') + '_countryname'] || Tine.Tinebase.registry.get('locale').locale || 'DE').toUpperCase()
}

const format = async (address, prefix='') => {
    const fa = await getFormater()

    if (!prefix && address.data) {
        prefix = (address.get('preferred_address') || 'adr_one') + '_'
    }
    if (address.data) {
        address = address.data
    }

    const originCountry = getOriginCountry()
    const adrData = {
        postalCountry: String(address[`${prefix}countryname`] || originCountry).toUpperCase(),
        administrativeArea : address[`${prefix}region`],
        locality: address[`${prefix}locality`],
        //dependentLocality: '',
        postalCode: address[`${prefix}postalcode`],
        //sortingCode: '',
        organization: address.org_name,
        name: address.n_fn,
        addressLines : [address[`${prefix}street`], address[`${prefix}street2`]]
    }

    const aStruct = fa(adrData)

    if (originCountry !== adrData.postalCountry) {
        if (! countryNamesCache[adrData.postalCountry]) {
            countryNamesCache[adrData.postalCountry] = Tine.Tinebase.getCountryList(adrData.postalCountry)
        }
        const dict = (await countryNamesCache[adrData.postalCountry]).results
        aStruct.push(find(dict, {shortName: adrData.postalCountry}).translatedName)
    }

    return aStruct
}

export default format