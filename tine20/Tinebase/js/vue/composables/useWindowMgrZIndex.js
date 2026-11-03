/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import { getCurrentInstance, onUnmounted, ref } from 'vue'

let seq = 0

/**
 * let Ext.WindowMgr manage the z-index of a floating vue element (e.g. popover teleported to body)
 * so it's shown above Ext windows
 *
 * call register when the element gets shown and unregister when it got hidden
 *
 * @param {object} options
 * @param {function} [options.hide] hides the element (e.g. Ext.WindowMgr.hideAll)
 * @param {object} [options.windowMgr] defaults to Ext.WindowMgr
 */
export const useWindowMgrZIndex = ({ hide = () => {}, windowMgr = Ext.WindowMgr } = {}) => {
  const zIndex = ref()

  const winMgrProxy = {
    id: `vue-floating-window-proxy-${++seq}`,
    setZIndex: (index) => {
      zIndex.value = index
    },
    setActive: () => {},
    isVisible: () => !!winMgrProxy.manager,
    hide,
    on: () => {},
    un: () => {}
  }

  const register = () => {
    windowMgr.register(winMgrProxy)
    windowMgr.bringToFront(winMgrProxy)
  }

  const unregister = () => {
    if (winMgrProxy.manager) {
      winMgrProxy.manager.unregister(winMgrProxy)
    }
  }

  if (getCurrentInstance()) {
    onUnmounted(unregister)
  }

  return { zIndex, register, unregister }
}

export default useWindowMgrZIndex
