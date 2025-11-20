
import ComponentRegistry from './utils/ComponentRegistry'
// import BreadcrumbRegistry from './utils/BreadcrumbRegistry'

/**
 * Auto-registro de componentes padrão do InfoList
 *
 * Estes componentes são registrados automaticamente e podem ser
 * sobrescritos pela aplicação se necessário.
 */
ComponentRegistry.registerBulk({
    'info-column-text': defineAsyncComponent(() => import('~/components/infolist/columns/InfolistText.vue')),
    'info-column-email': defineAsyncComponent(() => import('~/components/infolist/columns/InfolistEmail.vue')),
    'info-column-date': defineAsyncComponent(() => import('~/components/infolist/columns/InfolistDate.vue')),
    'info-column-phone': defineAsyncComponent(() => import('~/components/infolist/columns/InfolistPhone.vue')),
    'info-column-status': defineAsyncComponent(() => import('~/components/infolist/columns/InfolistStatus.vue')),
    'info-column-boolean': defineAsyncComponent(() => import('~/components/infolist/columns/InfolistBoolean.vue')),
    'info-column-card': defineAsyncComponent(() => import('~/components/infolist/columns/InfolistCard.vue')),
})

/**
 * Auto-registro de componentes padrão de Table
 *
 * Estes componentes são registrados automaticamente e podem ser
 * sobrescritos pela aplicação se necessário.
 */
ComponentRegistry.registerBulk({
    'table-column-text': defineAsyncComponent(() => import('~/components/table/columns/TableText.vue')),
    'table-column-email': defineAsyncComponent(() => import('~/components/table/columns/TableEmail.vue')),
    'table-column-date': defineAsyncComponent(() => import('~/components/table/columns/TableDate.vue')),
    'table-column-phone': defineAsyncComponent(() => import('~/components/table/columns/TablePhone.vue')),
    'table-column-status': defineAsyncComponent(() => import('~/components/table/columns/TableStatus.vue')),
    'table-column-boolean': defineAsyncComponent(() => import('~/components/table/columns/TableBoolean.vue')),
})

/**
 * Auto-registro de componentes de formulário
 *
 * Estes componentes são usados em formulários e modais de ações
 */

// New Field-based components (recommended)
ComponentRegistry.registerBulk({
    'form-field-text': defineAsyncComponent(() => import('~/components/form/fields/FormFieldText.vue')),
    'form-field-textarea': defineAsyncComponent(() => import('~/components/form/fields/FormFieldTextarea.vue')),
    'form-field-select': defineAsyncComponent(() => import('~/components/form/fields/FormFieldSelect.vue')),
    'form-field-checkbox': defineAsyncComponent(() => import('~/components/form/fields/FormFieldCheckbox.vue')),
    'form-field-date': defineAsyncComponent(() => import('~/components/form/fields/FormFieldDate.vue')),
    'form-field-number': defineAsyncComponent(() => import('~/components/form/fields/FormFieldNumber.vue')),
    'form-field-email': defineAsyncComponent(() => import('~/components/form/fields/FormFieldEmail.vue')),
    'form-field-password': defineAsyncComponent(() => import('~/components/form/fields/FormFieldPassword.vue')),
    'form-field-hidden': defineAsyncComponent(() => import('~/components/form/fields/FormFieldHidden.vue')),
    'form-field-file-upload': defineAsyncComponent(() => import('~/components/form/fields/FormFieldFileUpload.vue')),
})

// Legacy components (backwards compatibility)
ComponentRegistry.registerBulk({
    'form-column-text': defineAsyncComponent(() => import('~/components/form/columns/FormColumnText.vue')),
    'form-column-textarea': defineAsyncComponent(() => import('~/components/form/columns/FormColumnTextarea.vue')),
    'form-column-select': defineAsyncComponent(() => import('~/components/form/columns/FormColumnSelect.vue')),
    'form-column-checkbox': defineAsyncComponent(() => import('~/components/form/columns/FormColumnCheckbox.vue')),
    'form-column-date': defineAsyncComponent(() => import('~/components/form/columns/FormColumnDate.vue')),
    'form-column-number': defineAsyncComponent(() => import('~/components/form/columns/FormColumnNumber.vue')),
    'form-column-email': defineAsyncComponent(() => import('~/components/form/columns/FormColumnEmail.vue')),
    'form-column-password': defineAsyncComponent(() => import('~/components/form/columns/FormColumnPassword.vue')),
    'form-column-hidden': defineAsyncComponent(() => import('~/components/form/columns/FormColumnHidden.vue')),
    'form-column-file-upload': defineAsyncComponent(() => import('~/components/form/columns/FormColumnFileUpload.vue')),
})

ComponentRegistry.markAsInitialized()

/**
 * Auto-registro de componentes padrão do Breadcrumb
 *
 * Estes componentes são registrados automaticamente e podem ser
 * sobrescritos pela aplicação se necessário.
 */
// BreadcrumbRegistry.registerBulk({
//   'breadcrumb-default': defineAsyncComponent(() => import('./components/breadcrumbs/DefaultBreadcrumb.vue')),
// })

// BreadcrumbRegistry.markAsInitialized()

/**
 * Auto-registro de componentes padrão da Table
 *
 * Estes componentes são registrados automaticamente e podem ser
 * sobrescritos pela aplicação se necessário.
 */
import TableRegistry from './utils/TableRegistry'

TableRegistry.registerBulk({
    'table-default': defineAsyncComponent(() => import('~/components/table/DefaultTable.vue')),
})

TableRegistry.markAsInitialized()

/**
 * Auto-registro de componentes padrão de Actions
 *
 * Estes componentes são registrados automaticamente e podem ser
 * sobrescritos pela aplicação se necessário.
 */
import ActionRegistry from './utils/ActionRegistry'

ActionRegistry.registerBulk({
    'action-button': defineAsyncComponent(() => import('~/components/actions/types/ActionButton.vue')),
    'action-link': defineAsyncComponent(() => import('~/components/actions/types/ActionLink.vue')),
    'action-button-link': defineAsyncComponent(() => import('~/components/actions/types/ActionButtonLink.vue')),
    'action-link-confirm': defineAsyncComponent(() => import('~/components/actions/types/ActionLinkConfirm.vue')),
    'action-a-link': defineAsyncComponent(() => import('~/components/actions/types/ActionALink.vue')),
    'action-dropdown': defineAsyncComponent(() => import('~/components/actions/types/ActionDropdown.vue')),
    'action-confirm': defineAsyncComponent(() => import('~/components/actions/types/ActionConfirm.vue')),
    'action-modal': defineAsyncComponent(() => import('~/components/actions/types/ActionModalForm.vue')),
    'action-modal-form': defineAsyncComponent(() => import('~/components/actions/types/ActionModalForm.vue')),
    'action-callback': defineAsyncComponent(() => import('~/components/actions/types/ActionCallback.vue')),
    'LinkButton': defineAsyncComponent(() => import('~/components/actions/types/ActionButton.vue')),
})

ActionRegistry.markAsInitialized()

/**
 * Auto-registro de componentes padrão de Filters
 *
 * Estes componentes são registrados automaticamente e podem ser
 * sobrescritos pela aplicação se necessário.
 */
import FilterRegistry from './utils/FilterRegistry'
import { App, Component, defineAsyncComponent } from 'vue'

FilterRegistry.registerBulk({
    'filter-text': defineAsyncComponent(() => import('./components/filters/types/FilterText.vue')),
    'filter-select': defineAsyncComponent(() => import('./components/filters/types/FilterSelect.vue')),
    'filter-multi-select': defineAsyncComponent(() => import('./components/filters/types/FilterMultiSelect.vue')),
    'filter-date': defineAsyncComponent(() => import('./components/filters/types/FilterDate.vue')),
    'filter-date-range': defineAsyncComponent(() => import('./components/filters/types/FilterDateRange.vue')),
    'filter-trashed': defineAsyncComponent(() => import('./components/filters/types/FilterTrashed.vue')),
})

FilterRegistry.markAsInitialized()

/**
 * Opções do RaptorPlugin
 */
export interface RaptorPluginOptions {
    customFormatters?: Record<string, Function>
    customComponents?: Record<string, Component>
    overrideComponents?: {
        actions?: Record<string, Component>
        filters?: Record<string, Component>
        tables?: Record<string, Component>
        infolist?: Record<string, Component>
        forms?: Record<string, Component>
    }
}

/**
 * RaptorPlugin - Plugin Vue para Laravel Raptor
 *
 * Registra todos os componentes padrão e permite customização
 * via overrideComponents e customComponents
 */
const install = (app: App, options: RaptorPluginOptions = {}): void => {
    // Permite override de componentes específicos dos registries
    if (options.overrideComponents) {
        if (options.overrideComponents.actions) {
            Object.entries(options.overrideComponents.actions).forEach(([name, component]) => {
                ActionRegistry.register(name, component)
            })
        }

        if (options.overrideComponents.filters) {
            Object.entries(options.overrideComponents.filters).forEach(([name, component]) => {
                FilterRegistry.register(name, component)
            })
        }

        if (options.overrideComponents.tables) {
            Object.entries(options.overrideComponents.tables).forEach(([name, component]) => {
                TableRegistry.register(name, component)
            })
        }

        if (options.overrideComponents.infolist) {
            Object.entries(options.overrideComponents.infolist).forEach(([name, component]) => {
                ComponentRegistry.register(name, component)
            })
        }

        if (options.overrideComponents.forms) {
            Object.entries(options.overrideComponents.forms).forEach(([name, component]) => {
                ComponentRegistry.register(name, component)
            })
        }
    }

    // Registra componentes customizados globalmente no app
    if (options.customComponents) {
        Object.entries(options.customComponents).forEach(([name, component]) => {
            app.component(name, component)
        })
    }

    // Registra formatadores personalizados
    if (options.customFormatters) {
        Object.entries(options.customFormatters).forEach(([name, func]) => {
            // Futuro: FormattersRegistry.register(name, func);
        })
    }

    // Provide registries globally para uso em composables
    app.provide('actionRegistry', ActionRegistry)
    app.provide('filterRegistry', FilterRegistry)
    app.provide('tableRegistry', TableRegistry)
    app.provide('componentRegistry', ComponentRegistry)
}

/**
 * Export do plugin
 */
export const RaptorPlugin = {
    install
}

/**
 * Export dos registries para uso direto
 */
export {
    ActionRegistry,
    FilterRegistry,
    TableRegistry,
    ComponentRegistry,
}

/**
 * Export de composables
 */
export { useAction } from './composables/useAction'
export { useInertiaTable } from './composables/useInertiaTable'

/**
 * Export default para compatibilidade
 */
export default RaptorPlugin