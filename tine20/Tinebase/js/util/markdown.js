import '../../css/util/markdown.less'

let marked
let DOMPurify

const getMarked = async () => {
    if (!marked) {
        marked = await import(/* webpackChunkName: "Tinebase/js/marked" */ 'marked')
        marked.use({
            async: true,
            pedantic: false,
            gfm: true,
        })
    }

    return marked
}

const getDOMPurify = async() => {
    if (!DOMPurify) {
        DOMPurify = (await import(/* webpackChunkName: "Tinebase/js/dompurify" */ 'dompurify')).default
    }

    return DOMPurify
}

const wrap = async (html) => {
    return `<div class="tb-markdown">${await html}</div>`
}
const parse = async (markdownString ,options) => {
    return wrap((await getMarked()).parse(markdownString, options))
}

const parsePurified = async (markdownString ,options) => {
    return (await getDOMPurify()).sanitize(await parse(markdownString, options))
}
const parseInline = async (markdownString ,options) => {
    return wrap((await getMarked()).parseInline(markdownString, options))
}

const parseInlinePurified = async (markdownString ,options) => {
    return (await getDOMPurify()).sanitize(await parseInline(markdownString, options))
}

const escapeMarkdown = (text) => {
    return text.replace(/[\\`*_{}\[\]()#+\-!.>|]/g, '\\$&');
}

export {
    parse,
    parsePurified,
    parseInline,
    parseInlinePurified,
    escapeMarkdown
}