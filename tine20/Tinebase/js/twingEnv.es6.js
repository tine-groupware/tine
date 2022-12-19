/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { TwingEnvironment, TwingLoaderArray, TwingFilter } from 'twing'
import { TwingExtensionIntl } from 'twing-intl'
import transliterate from 'util/transliterate'

let twingEnv
let proxyId = 0;

class Expression extends String {
  constructor(s) {
    super(s);
    this.isExpression = true;
  }
}

let proxyDocuments = [
  document
];

const addProxyDocument = function (doc) {
  proxyDocuments.unshift(doc)
}

const removeProxyDocument = function(doc) {
  const idx = proxyDocuments.indexOf(doc);
  if (idx >= 0) {
    proxyDocuments.splice(idx, 1)
  }
}

let proxyPromisesCollections = [];

const addProxyPromisesCollection = function (collection) {
  proxyPromisesCollections.push(collection)
}

const removeProxyPromisesCollection = function(collection) {
  const idx = proxyPromisesCollections.indexOf(collection);
  if (idx >= 0) {
    proxyPromisesCollections.splice(idx, 1)
  }
}

const replaceProxy = function(id, content) {
  proxyDocuments.forEach((doc) => {
    const el = doc.getElementById(id)
    if (el) {
      el.outerHTML = content
    } else {
      // try again later?
    }
  })
}



const getTwingEnv = function() {
  if (!twingEnv) {
    let loader = new TwingLoaderArray([])

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

    /**
     * render proxy which gets replaced after with rendered content
     *
     * @param context
     * @param buffer
     * @returns {Expression}
     */
    twingEnv.renderProxy = (context, buffer) => {
      const id = `twing-proxy-${++proxyId}`
      const proxyPromise = twingEnv.render(context, buffer)
      proxyPromisesCollections.forEach((proxyPromisesCollection) => {
        proxyPromisesCollection.push(proxyPromise)
      })

      proxyPromise.then((output) => {
        replaceProxy(id, output)
      });

      return new Expression(`<em id="${id}"></em>`)
    }
  }

  return twingEnv
}

export { getTwingEnv as default, Expression, addProxyDocument, removeProxyDocument, addProxyPromisesCollection, removeProxyPromisesCollection };
