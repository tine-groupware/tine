// grr this pollutes global array object! how to cope whith it?
require('ux/Array.js');

const testArr = ["green", "red", "blue", "red"];

test('Array.diff', () => {
    expect(testArr.diff(["green", "yellow", "red"]).length).toBe(1);
    expect(testArr.diff(["green"], "yellow", "red", "blue").length).toBe(0);
    expect(testArr.diff(["green", "yellow", "red"])).toStrictEqual(['blue']);
});

test('Array.intersect', () => {
    expect(testArr.intersect(["blue", "green"]).length).toBe(2);
    expect(testArr.intersect(["blue", "green"])).toStrictEqual(['blue', 'green']);
});

test('Array.containsAny', () => {
    expect(testArr.containsAny(["apple", "green"])).toBe(true);
    expect(testArr.containsAny(["apple", "banana"])).toBe(false);
})