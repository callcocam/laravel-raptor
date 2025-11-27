<!--
 * RepeaterItemCompact - Versão compacta de item do repeater
 *
 * Sem header, com botão de exclusão no final e espaçamento reduzido
 -->
<template>
  <div
    class="relative border rounded-md bg-card transition-all duration-200 hover:shadow-sm"
    :class="{
      'border-primary/50': isDragging,
      'opacity-50': isDragging,
    }"
  >
    <!-- Drag Handle (se ordenável) -->
    <div v-if="orderable" class="absolute left-0 top-0 flex items-center justify-center">
      <button
        type="button"
        class="drag-handle cursor-grab active:cursor-grabbing text-muted-foreground hover:text-foreground transition-colors p-1"
        title="Arrastar para reordenar"
      >
        <GripVertical class="h-4 w-4" />
      </button>
    </div>
    <!-- Item Fields - Grid compacto -->
    <div class="p-4 grid grid-cols-12 gap-3">
      <!-- Campos do formulário -->
      <FieldRenderer
        v-for="field in fields"
        :key="`${itemId}-${field.name}`"
        :column="field"
        :modelValue="item[field.name]"
        :error="getFieldError(field.name)"
        @update:modelValue="(value: any) => $emit('updateField', field.name, value)"
        :class="getFieldColumnClass(field, orderable)"
      />
    </div>

    <!-- Botão de remover no final -->
    <div v-if="canRemove" class="absolute right-0 top-0 flex items-center justify-center">
      <Button
        type="button"
        variant="ghost"
        size="icon"
        class="h-9 w-9 text-destructive hover:text-destructive hover:bg-destructive/10"
        @click.stop="$emit('remove', index)"
      >
        <Trash2 class="h-4 w-4" />
      </Button>
    </div>
    <!-- Indicador de erro discreto -->
    <div
      v-if="hasErrors"
      class="absolute top-0 right-0 h-2 w-2 bg-destructive rounded-full m-1"
      title="Este item contém erros"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, provide, toRef } from "vue";
import { Button } from "@/components/ui/button";
import { GripVertical, Trash2 } from "lucide-vue-next";
import FieldRenderer from "../../columns/FieldRenderer.vue";

interface FormColumn {
  name: string;
  label?: string;
  component?: string;
  columnSpan?: string;
  [key: string]: any;
}

interface Props {
  item: any;
  itemId: string;
  index: number;
  isLast: boolean;
  fields: FormColumn[];
  errors?: Record<string, any>;
  orderable?: boolean;
  canRemove?: boolean;
  isDragging?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  errors: () => ({}),
  orderable: false,
  canRemove: true,
  isDragging: false,
});

defineEmits<{
  (e: "updateField", fieldName: string, value: any): void;
  (e: "remove", index: number): void;
}>();

// Provê o item atual como formData para os campos poderem fazer cálculos
provide("formData", toRef(props, "item"));

const hasErrors = computed(() => {
  if (!props.errors) return false;
  return Object.keys(props.errors).some((key) => key.startsWith(`${props.index}.`));
});

const getFieldColumnClass = (field: FormColumn, hasOrderable: boolean) => {
   const span = field.columnSpan || 'full'
  
  if (span === 'full') {
    return 'col-span-12'
  }
  
  return `col-span-12 md:col-span-${span}`
};

const getFieldError = (fieldName: string) => {
  if (!props.errors) return undefined;

  const errorKey = `${props.index}.${fieldName}`;
  if (errorKey in props.errors) {
    return props.errors[errorKey];
  }

  return undefined;
};
</script>
