import {default as vPersonaContainer} from './PersonaContainer.vue'
Ext.ns("Tine.ux.vue")
Ext.ns("Tine.personas")

export const Personas = Object.freeze({
    INFO : 'info_default',
    INFO_INSTRUCTION: 'info_instruction',
    INFO_FAILURE: 'info_failure',
    INFO_SUCCESS: 'info_success',
    INFO_WAIT: 'info_wait',

    WARNING : 'warning',

    QUESTION : 'question_default',
    QUESTION_INPUT: 'question_input',
    QUESTION_OPTION: 'question_option',
    QUESTION_CONFIRM: 'question_confirm',
    QUESTION_WARN: 'question_warn',

    ERROR : 'error_default',
    ERROR_MILD: 'error_mild',
    ERROR_SEVERE: 'error_severe',
})

export const PersonaContainer = Ext.extend(Ext.BoxComponent, {
    width: 100,
    height: 200,
    persona: Personas.INFO,

    onRender: async function (ct, position){
        Ext.BoxComponent.superclass.onRender.call(this, ct, position)
        this.app = window.vue.createApp(
            vPersonaContainer,
            {
                iconName: this.initialConfig.persona,
                skinColor: this.initialConfig.skinColor
            })
        this.app.mount(`#${this.id}`)
    },
})

Tine.personas = Personas
Tine.ux.vue.PersonaContainer = PersonaContainer
Ext.reg('vpersona', Tine.ux.vue.PersonaContainer)
