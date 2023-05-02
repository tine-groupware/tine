import { inject } from "vue";
import { default as MessageBoxApp } from "./App.vue";

const ExtEventBusInjectKey = Symbol("ExtEventBusInjectKey");

function useExtEventBus(){
    return inject(ExtEventBusInjectKey);
}

const SymbolKeys = {
    ExtEventBusInjectKey,
}

export {
    useExtEventBus,
    SymbolKeys,
    MessageBoxApp
}