<!--
 * ModalInfo - Renderiza infolist em modal
 *
 * Componente especializado para exibir informações detalhadas em modal dialog
 -->
<template>
  <div>
    <InfoListRenderer :columns="columns" :value="value" :name="name" />
  </div>
</template>

<script setup lang="ts">
import InfoListRenderer from '../../infolist/InfoListRenderer.vue'

interface Props {
  columns: any[]
  value?: Record<string, any>
  name?: string
}

withDefaults(defineProps<Props>(), {
  value: () => ({})
})
</script>
