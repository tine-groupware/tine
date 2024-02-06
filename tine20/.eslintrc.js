// http://eslint.org/docs/user-guide/configuring

module.exports = {
  root: true,
  // TODO: `parserOptions` had to be set in webpack.common.js
  /*
  parserOptions: {
    sourceType: 'module',
    parser: 'babel-eslint'
  },
  */
  env: {
    browser: true
  },
  globals: {
    Tine: true,
    Ext: true,
    _: true
  },
  // TODO: `extends` had to be set in webpack.common.js
  // https://github.com/feross/standard/blob/master/RULES.md#javascript-standard-style
  /*
  extends: [
    './Tinebase/js/node_modules/eslint-config-standard',
    './Tinebase/js/node_modules/eslint-plugin-vue/lib/configs/essential.js'
  ],
  */
  // required to lint *.vue files
  // TODO: `plugins` had to be set in webpack.common.js
  /*
  plugins: [
    './Tinebase/js/node_modules/eslint-plugin-notice', require('./Tinebase/js/node_modules/eslint-plugin-vue')
  ],
  */
  // add your custom rules here
  'rules': {
    // allow paren-less arrow functions
    'arrow-parens': 0,
    // allow async-await
    'generator-star-spacing': 0,
    // allow debugger during development
    'no-debugger': process.env.NODE_ENV === 'production' ? 2 : 0,
    // allow unused variables starting with 'this'
    'no-unused-vars': ['error', { 'vars': 'all', 'varsIgnorePattern': '^this' }],
    'notice/notice':['error',
      {
        template:
        '/*\n' +
        ' * Tine 2.0\n' +
        ' *\n' +
        ' * @license     <%= LICENSE %>\n' +
        ' * @author      <%= AUTHOR %>\n' +
        ' * @copyright   Copyright (c) <%= YEAR %> Metaways Infosystems GmbH (http://www.metaways.de)\n' +
        ' */',
        templateVars:{
          LICENSE: 'http://www.gnu.org/licenses/agpl.html AGPL Version 3',
          AUTHOR: 'Given Last <e.mail@domain.org>',
          YEAR: 'creationyear-lasteditingyear'
        },
        varRegexps:{
          LICENSE: /(.*)(AGPL|BSD|MIT)(.*)/,
          AUTHOR: /(.+) <(.+)@(.+)\.(.+)>/,
          YEAR: /\d{4}(-\d{4})?/
        }
      }
    ],
    //@fixme
    "vue/no-use-v-if-with-v-for": ["error", {
      "allowUsingIterationVar": true
    }]
  }
}
