import common from "common";


describe('common functions', () => {
    describe('html2text', () => {
        it('ignores empty divs', () => {
            expect(common.html2text( '<blockquote class="felamimail-body-blockquote"><div>Hello,</div><div><br></div><div>...</div></blockquote>'))
                .toEqual("\nHello,\n\n...")
        })

        it('copes with styled div tags', () => {
            expect(common.html2text( '<font face="arial, tahoma, helvetica, sans-serif" style="font-size: 11px; font-family: arial, tahoma, helvetica, sans-serif;"><span style="font-size: 11px;">​<font color="#808080">Dipl.-Phys. Cornelius Weiss</font></span></font><div style="font-size: 11px; font-family: arial, tahoma, helvetica, sans-serif;"><font face="arial, tahoma, helvetica, sans-serif" color="#808080"><span style="font-size: 11px;">Team Leader Software Engineering</span></font></div>'))
                .toEqual("​Dipl.-Phys. Cornelius Weiss\nTeam Leader Software Engineering")
        })

        it('copes with nested blocks', () => {
            expect(common.html2text('<div><div><span><font><br></font></span></div></div>'))
                .toEqual("\n")
        })
    })

    describe('colorRenderer', () => {
        it('renders a color swatch', () => {
            const swatch = '<span class="tb-color-swatch dark-reverse" style="background-color: #FF6600"></span>'
            expect(common.colorRenderer('#FF6600')).toEqual(swatch)
            expect(common.colorRenderer('FF6600')).toEqual(swatch)
        })

        it('renders nothing for empty colors', () => {
            expect(common.colorRenderer(null)).toEqual('')
            expect(common.colorRenderer(undefined)).toEqual('')
            expect(common.colorRenderer('')).toEqual('')
            expect(common.colorRenderer('#null')).toEqual('')
        })

        it('renders nothing for invalid colors', () => {
            expect(common.colorRenderer('"><img src=x onerror=alert(1)>')).toEqual('')
            expect(common.colorRenderer('FF66')).toEqual('')
            expect(common.colorRenderer('GGGGGG')).toEqual('')
            expect(common.colorRenderer('FF6600; background-image: url(x)')).toEqual('')
        })
    })

})
