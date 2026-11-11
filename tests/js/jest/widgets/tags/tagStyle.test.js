import { rowTint } from "widgets/tags/tagStyle";

describe('widgets/tags/tagStyle rowTint', () => {
    it('is empty without tags', () => {
        expect(rowTint([])).toBe('');
        expect(rowTint(null)).toBe('');
    });

    it('uses one color for one tag', () => {
        expect(rowTint([{ id: '1', color: '#ff0000' }]))
            .toBe('linear-gradient(to right, color-mix(in srgb, #ff0000 14%, transparent), color-mix(in srgb, #ff0000 14%, transparent))');
    });

    it('uses n colors for n tags (in order)', () => {
        const tint = rowTint([{ id: '1', color: '#ff0000' }, { id: '2', color: '00ff00' }, { id: '3', color: '#0000ff' }], 20);
        expect(tint).toBe('linear-gradient(to right, color-mix(in srgb, #ff0000 20%, transparent), '
            + 'color-mix(in srgb, #00ff00 20%, transparent), color-mix(in srgb, #0000ff 20%, transparent))');
    });

    it('does not inject invalid colors', () => {
        expect(rowTint([{ id: '1', color: 'red;background:url(x)' }])).not.toContain('url');
    });
});
