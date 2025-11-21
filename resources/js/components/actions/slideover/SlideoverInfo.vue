<!--
 * SlideoverInfo - Renderiza infolist em slideover
 *
 * Componente especializado para exibir informações detalhadas em painel lateral
 -->
<template>
  <div class="flex-1 overflow-y-auto px-6 py-6">
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
