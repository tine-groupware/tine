import { default as MessageBoxApp } from "./App.vue";
Ext.ns("Tine.ux.vue")

const ExtEventBusInjectKey = Symbol("ExtEventBusInjectKey");

const SymbolKeys = {
    ExtEventBusInjectKey,
}

export {
    SymbolKeys,
    MessageBoxApp
}