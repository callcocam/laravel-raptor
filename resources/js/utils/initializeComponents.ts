/**
 * initializeComponents - Registra componentes padrão do Raptor
 *
 * Registra componentes de InfoList e Table no ComponentRegistry
 * Deve ser chamado durante a inicialização da aplicação
 */

import ComponentRegistry from './ComponentRegistry'

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
import TableText from '../components/table/columns/TableText.vue'

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
    'table-column-date': TableDate,
    'table-column-email': TableEmail,
    'table-column-phone': TablePhone,
    'table-column-status': TableStatus,
    'table-column-text': TableText,
  })

  ComponentRegistry.markAsInitialized()

  if (import.meta.env.DEV) {
    console.log('ComponentRegistry initialized with components:', ComponentRegistry.getStats())
  }
}

/**
 * Export default para compatibilidade
 */
export default initializeComponents
