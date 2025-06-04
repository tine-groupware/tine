/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { TwingEnvironment, TwingLoaderArray, TwingFilter, TwingFunction } from 'twing'
import { TwingExtensionIntl } from 'twing-intl'
import transliterate from 'util/transliterate'

let twingEnv

/**
 * Expression is a string that does not get quoted in htmlEncode (@see Ext.util.Format.htmlEncode)
 */
class Expression extends String {
  constructor (s, id) {
    super(s)
    this.isExpression = true
  }
}

let proxyId = 0
const replaceProxyFns = {}
const proxyDocuments = []
if (typeof window !== 'undefined') {
  proxyDocuments.push(document)
}

const proxyPromisesCollections = []

/**
 * HTMLProxy - html proxy snipped that will be replaced with the real content later
 *
 * useful in situations where you need to return html directly which gets produces async
 */
class HTMLProxy extends Expression {
  constructor (renderPromise, config = {}) {
    const id = config.id || `html-proxy-${++proxyId}`
    const cls = config.cls || 'html-proxy'
    const tag = config.tag || 'em'
    const html = config.html || ''
    super(`<${tag} id="${id}" class="${cls}">${html}</${tag}>`)
    this.id = id
    this.cls = cls
    this.tag = tag
    Object.assign(this, config)
    if (renderPromise) {
      this.setRenderer(renderPromise)
    }
    this.isHTMLProxy = true
  }

  /**
   * set the render/producer function this HTMLProxy proxies
   *
   * @param renderPromise
   * @returns {Promise}
   */
  setRenderer (renderPromise) {
    this.renderPromise = renderPromise

    const sleep = ms => new Promise(resolve => setTimeout(resolve, ms))
    const proxiedPromise = renderPromise.then(async (output) => {
      for (let i = 0; i < 10; i++) {
        await sleep(i * 100)
        if (await this.replaceProxy(output)) break
      }
    })

    proxyPromisesCollections.forEach((proxyPromisesCollection) => {
      proxyPromisesCollection.push(proxiedPromise)
    })

    return proxiedPromise
  }

  /**
   * default implementation to replace proxy in dom
   *
   * @param html
   * @private
   */
  replaceDomProxy (html) {
    let isReplaced = false
    proxyDocuments.forEach((doc) => {
      const el = doc.getElementById(this.id)
      if (el) {
        el.outerHTML = html
        isReplaced = true
        return true
      }
    })
    return isReplaced
  }

  /**
   * replace proxy by given html
   * @param html
   * @returns {Promise<boolean|void>}
   */
  async replaceProxy (html) {
    // @TODO retry n times, replaceProxy might be registered late!
    if (replaceProxyFns[this.id]) {
      if (replaceProxyFns[this.id](html, this.id)) {
        delete replaceProxyFns[this.id]
        return true
      }
    } else {
      html = Ext.util.Format.htmlEncode(html)
      return this.replaceDomProxy(html)
    }
  }

  /**
   * register a custom replacer method for this proxy
   * NOTE: replacer needs to return bool true to mark a successfully replacement
   * @param {Function} fn
   */
  registerReplacer (fn) {
    replaceProxyFns[this.id] = fn
  }

  /**
   * get a Promise which resolves with the final content
   * @returns {Promise<String>}
   */
  asString () {
    return new Promise(resolve => {
      this.registerReplacer((text) => {
        resolve(text)
        return true
      })
    })
  }
}

// add static functions
Object.assign(HTMLProxy, {
  addProxyDocument: function (doc) {
    proxyDocuments.unshift(doc)
  },

  removeProxyDocument: function (doc) {
    const idx = proxyDocuments.indexOf(doc)
    if (idx >= 0) {
      proxyDocuments.splice(idx, 1)
    }
  },

  addProxyPromisesCollection: function (collection) {
    proxyPromisesCollections.push(collection)
  },

  removeProxyPromisesCollection: function (collection) {
    const idx = proxyPromisesCollections.indexOf(collection)
    if (idx >= 0) {
      proxyPromisesCollections.splice(idx, 1)
    }
  }
})

const getTwingEnv = function () {
  if (!twingEnv) {
    const loader = new TwingLoaderArray([])

    twingEnv = new TwingEnvironment(loader, {
      autoescape: false
    })

    twingEnv.addGlobal('app', {
      branding: _.filter(Tine.Tinebase.registry.getAll(), function (v, k) { return k.match(/^branding/) }),
      user: {
        locale: Tine.Tinebase.registry.get('locale').locale || 'en',
        timezone: Tine.Tinebase.registry.get('timeZone') || 'UTC'
      }
    })

    twingEnv.addExtension(new TwingExtensionIntl())

    twingEnv.addFilter(new TwingFilter('removeSpace', function (string) {
      return Promise.resolve(string.replaceAll(' ', ''))
    }))

    twingEnv.addFilter(new TwingFilter('transliterate', function (string) {
      return Promise.resolve(transliterate(string))
    }))

    twingEnv.addFilter(new TwingFilter('accountLoginChars', function (str) {
      return Promise.resolve(String(str).replace(/[^\w\-_.@\d+]/u, ''))
    }))

    twingEnv.addFunction(new TwingFunction('keyField', function (appName, keyFieldName, id) {
      return Promise.resolve(Tine.Tinebase.widgets.keyfield.Renderer.render(appName, keyFieldName, id, 'text'))
    }))

    twingEnv.addFunction(new TwingFunction('renderModel', function (modelName) {
      return Promise.resolve(Tine.Tinebase.data.RecordMgr.get(modelName)?.getRecordName())
    }))

    twingEnv.addFunction(new TwingFunction('renderTitle', function (recordData, modelName) {
      const title = Tine.Tinebase.data.Record.setFromJson(recordData, modelName)?.getTitle()
      return Promise.resolve(title.asString ? title.asString() : title)
    }))

    /**
     * render proxy which gets replaced after with rendered content
     *
     * @param context
     * @param buffer
     * @returns {HTMLProxy}
     */
    twingEnv.renderProxy = (context, buffer) => {
      return new HTMLProxy(twingEnv.render(context, buffer))
    }
  }

  return twingEnv
}

export { getTwingEnv as default, Expression, HTMLProxy }
