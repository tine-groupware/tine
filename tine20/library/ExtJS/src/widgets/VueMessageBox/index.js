import { default as MessageBoxApp } from "./App.vue";

const ExtEventBusInjectKey = Symbol("ExtEventBusInjectKey");

const SymbolKeys = {
    ExtEventBusInjectKey,
}

export {
    SymbolKeys,
    MessageBoxApp
}