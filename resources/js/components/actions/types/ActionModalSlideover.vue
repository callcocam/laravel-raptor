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
      :size="size"
      class="gap-1 h-7 px-2.5"
      @click="openSlideover"
    >
      <component v-if="iconComponent" :is="iconComponent" class="h-3 w-3" />
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
        :errors="formErrors"
        :is-submitting="isSubmitting"
        :confirm-text="action.confirm?.confirmButtonText || 'Confirmar'"
        @submit="handleSubmit"
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
import * as LucideIcons from "lucide-vue-next";
import SlideoverBase from "../slideover/SlideoverBase.vue";
import SlideoverForm from "../slideover/SlideoverForm.vue";
import SlideoverTable from "../slideover/SlideoverTable.vue";
import SlideoverInfo from "../slideover/SlideoverInfo.vue";
import { useAction } from "~/composables/useAction";
import type { TableAction } from "~/types/table";

// Composable para executar actions
const actionComposable = useAction();

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
const isSubmitting = ref(false);

// Dados do formulário
const formData = ref<Record<string, any>>(props.record || {});

// Erros de validação
const formErrors = ref<Record<string, string | string[]>>({});

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

// Verifica se há colunas
const hasFormColumns = computed(() => {
  return formColumns.value.length > 0;
});

// Mapeia cor para variant do shadcn
const variant = computed(() => {
  const colorMap: Record<string, any> = {
    green: "default",
    blue: "default",
    red: "destructive",
    yellow: "warning",
    gray: "secondary",
    default: "default",
  };

  return colorMap[props.action.color || "default"] || "default";
});

// Componente do ícone dinâmico
const iconComponent = computed(() => {
  if (!props.action.icon) return null;

  const IconComponent = (LucideIcons as any)[props.action.icon];

  if (!IconComponent) {
    console.warn(`Icon "${props.action.icon}" not found in lucide-vue-next`);
    return null;
  }

  return h(IconComponent);
});

// Abre o slideover
const openSlideover = () => {
  // Para infolist e table, sempre inicializar com os dados do record
  if (columnType.value === 'infolist' || columnType.value === 'table') {
    formData.value = { ...props.record };
  }
  isOpen.value = true;
};

// Fecha o slideover
const closeSlideover = () => {
  isOpen.value = false;
};

// Watch para emitir eventos quando o slideover abre/fecha e limpar erros
watch(isOpen, (newValue) => {
  if (newValue) {
    emit("open");
  } else {
    emit("close");
    // Limpa erros ao fechar
    formErrors.value = {};
  }
});

// Handler para submit do formulário
const handleSubmit = async () => {
  if (columnType.value === "form" && hasFormColumns.value) {
    isSubmitting.value = true;
    formErrors.value = {}; // Limpa erros anteriores

    try {
      // Executa a action com os dados do formulário
      await actionComposable.execute(
        {
          url: props.action.url,
          method: props.action.method as any,
          successMessage: "",
          onSuccess: (data) => {
            emit("submit", data);
            emit("success", data);
            closeSlideover();
          },
          onError: (error) => {
            // Captura erros de validação do Inertia
            if (error && typeof error === "object") {
              const validationErrors: Record<string, string | string[]> = {};
              Object.keys(error).forEach((key) => {
                const errorValue = error[key];
                validationErrors[key] = Array.isArray(errorValue)
                  ? errorValue[0]
                  : errorValue;
              });
              formErrors.value = validationErrors;
            }

            emit("error", error);
          },
        },
        formData.value
      );

      emit("click", formData.value);
    } finally {
      isSubmitting.value = false;
    }
  } else {
    emit("click");
  }
};

// Expõe métodos para controle externo
defineExpose({
  open: openSlideover,
  close: closeSlideover,
  isOpen,
  formData,
});
</script>
