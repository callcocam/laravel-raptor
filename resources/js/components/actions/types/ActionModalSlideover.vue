<!--
 * ActionModalSlideover - Componente de ação com painel lateral fixo (slideover)
 *
 * Exibe um botão que, ao clicar, abre um painel lateral com:
 * - SlideoverForm: Formulários editáveis
 * - SlideoverTable: Visualização de listas
 * - SlideoverInfo: Visualização de detalhes
 -->
<template>
  <div>
    <!-- Botão trigger -->
    <Button
      :variant="variant"
      :size="computedSize"
      class="gap-1.5"
      @click="openSlideover"
    >
      <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
      <span class="text-xs">{{ action.label }}</span>
    </Button>

    <!-- Slideover Base -->
    <SlideoverBase
      :is-open="isOpen"
      :title="action.modalTitle || action.label"
      :description="action.modalDescription"
      :position="action.slideoverPosition || 'right'"
      @close="closeSlideover"
    >
      <!-- Form Mode -->
      <SlideoverForm
        v-if="columnType === 'form' && hasFormColumns"
        v-model="formData"
        :columns="formColumns"
        :action="{
          ...action,
          confirm: action.confirm ?? undefined
        }"
        :confirm-text="action.confirm?.confirmButtonText || 'Confirmar'"
        ref="slideoverFormRef"
        @success="handleSuccess"
        @error="handleError"
        @cancel="closeSlideover"
      />

      <!-- Table Mode -->
      <SlideoverTable
        v-else-if="columnType === 'table'"
        :columns="formColumns"
        :data="tableData"
      />

      <!-- InfoList Mode -->
      <SlideoverInfo
        v-else-if="columnType === 'infolist'"
        :columns="formColumns"
        :value="formData"
        :name="props.action.name"
      />
    </SlideoverBase>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, h, watch } from "vue";
import { Button } from "@/components/ui/button";
import SlideoverBase from "../slideover/SlideoverBase.vue";
import SlideoverForm from "../slideover/SlideoverForm.vue";
import SlideoverTable from "../slideover/SlideoverTable.vue";
import SlideoverInfo from "../slideover/SlideoverInfo.vue";
import { useActionConfig } from "~/composables/useActionConfig";
import { useActionUI } from "~/composables/useActionUI";
import type { TableAction } from "~/types/table";

interface FormColumn {
  name: string;
  label?: string;
  component?: string;
  required?: boolean;
  [key: string]: any;
}

interface Props {
  action: TableAction & {
    modalTitle?: string;
    modalDescription?: string;
    modalContent?: string;
    slideoverPosition?: "right" | "left";
    columns?: FormColumn[];
    columnType?: "form" | "table" | "infolist";
    tableData?: any[];
  };
  size?: "default" | "sm" | "lg" | "icon";
  record?: Record<string, any>;
}

const props = withDefaults(defineProps<Props>(), {
  size: "sm",
});

const emit = defineEmits<{
  (e: "click", formData?: Record<string, any>): void;
  (e: "open"): void;
  (e: "close"): void;
  (e: "submit", formData: Record<string, any>): void;
  (e: "success", data: any): void;
  (e: "error", error: any): void;
}>();

// Estado do slideover
const isOpen = ref(false);

// Dados iniciais do formulário
const formData = ref<Record<string, any>>(props.record || {});

// Referência ao SlideoverForm (para limpar erros, etc)
const slideoverFormRef = ref<InstanceType<typeof SlideoverForm> | null>(null);

// Tipo de coluna (form, table, ou infolist)
const columnType = computed(() => {
  return props.action.columnType || "form";
});

// Colunas (pode ser form, table ou infolist)
const formColumns = computed(() => {
  return props.action.columns || [];
});

// Dados para tabela (se columnType === 'table')
const tableData = computed(() => {
  return props.action.tableData || [];
});

// Usa o composable para configurações comuns
const {
  hasFormColumns,
} = useActionConfig({
  action: props.action,
  columns: formColumns
});

// Usa composable para UI (variant, iconComponent, etc)
const { variant, size: computedSize, iconComponent, iconClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
}); 

// Abre o slideover
const openSlideover = () => {
  // Para infolist e table, sempre inicializar com os dados do record
  if (columnType.value === 'infolist' || columnType.value === 'table') {
    formData.value = { ...props.record };
  }
  isOpen.value = true;
};

// Handler para sucesso do formulário (vem do SlideoverForm)
const handleSuccess = (page: any) => {
  emit("submit", formData.value);
  emit("success", page);

  // Fecha o slideover apenas se closeModalOnSuccess for true (padrão)
  if (props.action.confirm?.closeModalOnSuccess ?? true) {
    closeSlideover();
  }

  // Emite evento de click para compatibilidade
  emit("click", formData.value);
};

// Handler para erro do formulário (vem do SlideoverForm)
const handleError = (errors: any) => {
  emit("error", errors);
};

// Fecha o slideover
const closeSlideover = () => {
  isOpen.value = false;
  // Limpa erros do SlideoverForm se existir
  if (slideoverFormRef.value) {
    slideoverFormRef.value.clearErrors();
  }
};

// Watch para emitir eventos quando o slideover abre/fecha
watch(isOpen, (newValue) => {
  if (newValue) {
    emit("open");
  } else {
    emit("close");
    // Limpa erros ao fechar
    if (slideoverFormRef.value) {
      slideoverFormRef.value.clearErrors();
    }
  }
});

// Expõe métodos para controle externo
defineExpose({
  open: openSlideover,
  close: closeSlideover,
  isOpen,
  formData, // Dados iniciais do formulário
  slideoverFormRef, // Referência ao SlideoverForm (para acesso ao form do Inertia se necessário)
});
</script>
