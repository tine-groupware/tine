import { useWindowMgrZIndex } from 'Tinebase/vue/composables/useWindowMgrZIndex'

// minimal Ext.WindowGroup
const createWindowMgr = () => {
  let zIndex = 9000
  return {
    windows: [],
    register (win) {
      if (win.manager) {
        win.manager.unregister(win)
      }
      win.manager = this
      this.windows.push(win)
    },
    unregister (win) {
      delete win.manager
      this.windows.splice(this.windows.indexOf(win), 1)
    },
    bringToFront (win) {
      win.setZIndex(zIndex += 10)
    }
  }
}

describe('vue/composables/useWindowMgrZIndex', () => {
  it('gets a z-index from the window manager on register', () => {
    const windowMgr = createWindowMgr()
    const { zIndex, register } = useWindowMgrZIndex({ windowMgr })

    expect(zIndex.value).toBeUndefined()
    register()
    expect(zIndex.value).toBe(9010)
    expect(windowMgr.windows).toHaveLength(1)
  })

  it('reports visibility while registered', () => {
    const windowMgr = createWindowMgr()
    const { register, unregister } = useWindowMgrZIndex({ windowMgr })

    register()
    const [proxy] = windowMgr.windows
    expect(proxy.isVisible()).toBe(true)

    unregister()
    expect(proxy.isVisible()).toBe(false)
    expect(windowMgr.windows).toHaveLength(0)
  })

  it('unregisters idempotent', () => {
    const windowMgr = createWindowMgr()
    const { register, unregister } = useWindowMgrZIndex({ windowMgr })

    expect(() => unregister()).not.toThrow()
    register()
    unregister()
    expect(() => unregister()).not.toThrow()
    expect(windowMgr.windows).toHaveLength(0)
  })

  it('uses own proxies for each instance', () => {
    const windowMgr = createWindowMgr()
    const first = useWindowMgrZIndex({ windowMgr })
    const second = useWindowMgrZIndex({ windowMgr })

    first.register()
    second.register()
    expect(windowMgr.windows).toHaveLength(2)
    expect(windowMgr.windows[0].id).not.toBe(windowMgr.windows[1].id)
    expect(second.zIndex.value).toBeGreaterThan(first.zIndex.value)

    first.unregister()
    expect(windowMgr.windows).toHaveLength(1)
  })

  it('passes hide to the window manager', () => {
    const windowMgr = createWindowMgr()
    const hide = jest.fn()
    const { register } = useWindowMgrZIndex({ windowMgr, hide })

    register()
    windowMgr.windows[0].hide()
    expect(hide).toHaveBeenCalled()
  })
})
