<!--
 * SlideoverTable - Renderiza tabela em slideover
 *
 * Componente especializado para exibir listas de dados relacionados em painel lateral
 -->
<template>
  <div class="flex-1 overflow-y-auto px-6 py-6">
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
