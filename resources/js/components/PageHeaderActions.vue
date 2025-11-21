<script setup lang="ts">
import ActionRenderer from "~/components/actions/ActionRenderer.vue";
import type { TableAction } from "~/types/table";

interface Props {
  actions?: TableAction[];
  modelId?: string;
}
withDefaults(defineProps<Props>(), {
  actions: () => [],
});
</script>

<template>
  <div v-if="actions.length > 0" class="flex items-center gap-2">
    <ActionRenderer
      v-for="action in actions"
      :key="action.name"
      :action="action"
      :record="{ id: modelId }"
    />
  </div>
</template>
