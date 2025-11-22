<!--
 * ModalTable - Renderiza tabela em modal
 *
 * Componente especializado para exibir listas de dados relacionados em modal dialog
 -->
<template>
  <div>
    <TableOnlyRenderer :columns="columns" :data="data" />
  </div>
</template>

<script setup lang="ts">
import TableOnlyRenderer from '../../table/TableOnlyRenderer.vue'

interface Props {
  columns: any[]
  data: any[]
}

withDefaults(defineProps<Props>(), {
  data: () => []
})
</script>
