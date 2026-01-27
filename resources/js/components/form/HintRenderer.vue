<!--
 * HintRenderer - Renderiza hint como texto ou array de actions
 *
 * Componente reutilizável para exibir hints em campos de formulário
 -->
<template>
    <div v-if="hint" class="flex items-center gap-x-1">
        <!-- Hint como texto -->
        <span v-if="isString" class="text-xs text-muted-foreground">
            {{ hint }}
        </span>
        <!-- Hint como actions -->
        <template v-else-if="isArray">
            <ActionRenderer v-for="(action, index) in hint" :key="index" :action="action" />
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import ActionRenderer from '~/components/actions/ActionRenderer.vue'

interface Props {
    hint?: string | any[]
}

const props = defineProps<Props>()

console.log(props.hint);
const isString = computed(() => typeof props.hint === 'string')
const isArray = computed(() => Array.isArray(props.hint))
</script>
