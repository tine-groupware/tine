import { filterUsableTags } from "widgets/tags/TagsCache";

const OFFER = 'Sales_Model_DocumentPosition_Offer';
const PRODUCT = 'Sales_Model_Product';

const tags = [
    { id: 'any', type: 'shared', usage_contexts: [{ application_id: '0', model: '' }] },
    { id: 'sales', type: 'shared', usage_contexts: [{ application_id: 'sales-id', model: '' }] },
    { id: 'offer', type: 'shared', usage_contexts: [{ application_id: 'sales-id', model: OFFER }] },
    { id: 'crm', type: 'shared', usage_contexts: [{ application_id: 'crm-id', model: '' }] },
    { id: 'personal', type: 'personal', usage_contexts: [{ application_id: '0', model: '' }] },
    { id: 'none', type: 'shared', usage_contexts: [] },
];

const ids = (context) => filterUsableTags(tags, context).map((tag) => tag.id);

describe('widgets/tags/TagsCache filterUsableTags', () => {
    it('filters by application and model', () => {
        expect(ids({ appId: 'sales-id', model: OFFER, personalTags: true })).toEqual(['any', 'sales', 'offer', 'personal']);
        expect(ids({ appId: 'sales-id', model: PRODUCT, personalTags: true })).toEqual(['any', 'sales', 'personal']);
        expect(ids({ appId: 'crm-id', model: 'Crm_Model_Lead', personalTags: true })).toEqual(['any', 'crm', 'personal']);
    });

    it('filters personal tags without right', () => {
        expect(ids({ appId: 'sales-id', model: PRODUCT, personalTags: false })).toEqual(['any', 'sales']);
    });

    it('handles missing data', () => {
        expect(filterUsableTags(null, { appId: 'sales-id', model: PRODUCT })).toEqual([]);
    });
});
