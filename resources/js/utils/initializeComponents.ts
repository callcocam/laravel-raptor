/**
 * initializeComponents - Registra componentes padrão do Raptor
 *
 * Registra componentes de InfoList, Table e Breadcrumb no ComponentRegistry
 * Deve ser chamado durante a inicialização da aplicação
 */

import ComponentRegistry from './ComponentRegistry'
import BreadcrumbRegistry from './BreadcrumbRegistry'

// Componentes de InfoList
import InfolistBoolean from '../components/infolist/columns/InfolistBoolean.vue'
import InfolistCard from '../components/infolist/columns/InfolistCard.vue'
import InfolistDate from '../components/infolist/columns/InfolistDate.vue'
import InfolistEmail from '../components/infolist/columns/InfolistEmail.vue'
import InfolistPhone from '../components/infolist/columns/InfolistPhone.vue'
import InfolistStatus from '../components/infolist/columns/InfolistStatus.vue'
import InfolistText from '../components/infolist/columns/InfolistText.vue'

// Componentes de Table
import TableBoolean from '../components/table/columns/TableBoolean.vue'
import TableDate from '../components/table/columns/TableDate.vue'
import TableEmail from '../components/table/columns/TableEmail.vue'
import TablePhone from '../components/table/columns/TablePhone.vue'
import TableStatus from '../components/table/columns/TableStatus.vue'
import TableStatusEditable from '../components/table/columns/editable/TableStatus.vue'
import TableTextEditable from '../components/table/columns/editable/TableText.vue'
import TableBooleanEditable from '../components/table/columns/editable/TableBoolean.vue'
import TableDateEditable from '../components/table/columns/editable/TableDate.vue'
import TableEmailEditable from '../components/table/columns/editable/TableEmail.vue'
import TablePhoneEditable from '../components/table/columns/editable/TablePhone.vue'
import TableImageEditable from '../components/table/columns/editable/TableImage.vue'
import TableText from '../components/table/columns/TableText.vue'

// Componentes de Breadcrumb
import DefaultBreadcrumb from '../components/breadcrumbs/DefaultBreadcrumb.vue'
import PageHeaderBreadcrumb from '../components/breadcrumbs/PageHeaderBreadcrumb.vue'

/**
 * Inicializa todos os componentes padrão
 */
export function initializeComponents(): void {
  if (ComponentRegistry.isInitialized()) {
    console.warn('ComponentRegistry already initialized')
    return
  }

  // Registra componentes de InfoList
  ComponentRegistry.registerBulk({
    'info-column-boolean': InfolistBoolean,
    'info-column-card': InfolistCard,
    'info-column-date': InfolistDate,
    'info-column-email': InfolistEmail,
    'info-column-phone': InfolistPhone,
    'info-column-status': InfolistStatus,
    'info-column-text': InfolistText,
  })

  // Registra componentes de Table
  ComponentRegistry.registerBulk({
    'table-column-boolean': TableBoolean,
    'table-column-boolean-editable': TableBooleanEditable,
    'table-column-date': TableDate,
    'table-column-date-editable': TableDateEditable,
    'table-column-email': TableEmail,
    'table-column-email-editable': TableEmailEditable,
    'table-column-phone': TablePhone,
    'table-column-phone-editable': TablePhoneEditable,
    'table-column-status': TableStatus,
    'table-column-status-editable': TableStatusEditable,
    'table-column-text': TableText,
    'table-column-text-editable': TableTextEditable,
    'table-column-image-editable': TableImageEditable,
  })

  // Registra componentes de Breadcrumb
  BreadcrumbRegistry.registerBulk({
    'breadcrumb-default': DefaultBreadcrumb,
    'breadcrumb-page-header': PageHeaderBreadcrumb,
  })

  ComponentRegistry.markAsInitialized()
  BreadcrumbRegistry.markAsInitialized()

  if (import.meta.env.DEV) {
    console.log('ComponentRegistry initialized with components:', ComponentRegistry.getStats())
    console.log('BreadcrumbRegistry initialized with components:', BreadcrumbRegistry.getStats())
  }
}

/**
 * Export default para compatibilidade
 */
export default initializeComponents
