<!--
 * ActionModalSlideover - Componente de ação com painel lateral fixo (slideover)
 *
 * Exibe um botão que, ao clicar, abre um painel lateral fixo da direita ou esquerda
 * Útil para visualização de informações detalhadas ou formulários sem perder contexto da página
 *
 * Suporta conteúdo HTML ou formulário com columns
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

    <!-- Overlay (backdrop) -->
    <Transition
      enter-active-class="transition-opacity duration-300"
      leave-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div v-if="isOpen" class="fixed inset-0 bg-black/50 z-40" @click="closeSlideover" />
    </Transition>

    <!-- Painel Slideover -->
    <Transition
      :enter-active-class="`transition-transform duration-300 ease-out`"
      :leave-active-class="`transition-transform duration-300 ease-in`"
      :enter-from-class="slideoverEnterClass"
      :leave-to-class="slideoverLeaveClass"
    >
      <div
        v-if="isOpen"
        :class="[
          'fixed top-0 bottom-0 z-50 bg-background shadow-2xl',
          'w-full sm:max-w-md lg:max-w-lg',
          'flex flex-col',
          slideoverPositionClass,
        ]"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b px-6 py-4">
          <div class="flex-1">
            <h2 class="text-lg font-semibold">
              {{ action.modalTitle || action.label }}
            </h2>
            <p v-if="action.modalDescription" class="text-sm text-muted-foreground mt-1">
              {{ action.modalDescription }}
            </p>
          </div>
          <Button variant="ghost" size="icon" @click="closeSlideover" class="ml-4">
            <component :is="closeIcon" class="h-4 w-4" />
          </Button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto px-6 py-6">
          <slot name="content">
            <!-- Se houver colunas de formulário, renderiza o FormRenderer -->
            <div v-if="hasFormColumns">
              <FormRenderer
                :columns="formColumns"
                :errors="formErrors"
                v-model="formData"
                ref="formRef"
                @submit="handleSubmit"
              />
            </div>
          </slot>
        </div>

        <!-- Footer -->
        <div v-if="$slots.footer || hasFormColumns" class="border-t px-6 py-4">
          <slot name="footer">
            <!-- Botões padrão para formulário -->
            <div v-if="hasFormColumns" class="flex justify-end gap-3">
              <Button variant="outline" @click="closeSlideover"> Cancelar </Button>
              <Button @click="handleSubmit" :disabled="isSubmitting">
                {{
                  isSubmitting
                    ? "Processando..."
                    : action.confirm?.confirmButtonText || "Confirmar"
                }}
              </Button>
            </div>
          </slot>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, h, watch } from "vue";
import { Button } from "@/components/ui/button";
import { X } from "lucide-vue-next";
import * as LucideIcons from "lucide-vue-next";
import FormRenderer from "../../form/FormRenderer.vue";
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

// Referência ao FormRenderer
const formRef = ref<InstanceType<typeof FormRenderer> | null>(null);

// Dados do formulário (usando ref para permitir v-model)
const formData = ref<Record<string, any>>(props.record || {});

// Erros de validação
const formErrors = ref<Record<string, string | string[]>>({});

// Colunas do formulário
const formColumns = computed(() => {
  return props.action.columns || [];
});

// Verifica se há colunas de formulário
const hasFormColumns = computed(() => {
  return formColumns.value.length > 0;
});

// Ícone de fechar
const closeIcon = h(X);

// Posição do slideover
const slideoverPositionClass = computed(() => {
  return props.action.slideoverPosition === "left" ? "left-0" : "right-0";
});

const slideoverEnterClass = computed(() => {
  return props.action.slideoverPosition === "left"
    ? "-translate-x-full"
    : "translate-x-full";
});

const slideoverLeaveClass = computed(() => {
  return props.action.slideoverPosition === "left"
    ? "-translate-x-full"
    : "translate-x-full";
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
  if (hasFormColumns.value) {
    isSubmitting.value = true;
    formErrors.value = {}; // Limpa erros anteriores

    // Pega o formData do FormRenderer (se existir ref)
    const dataToSubmit = formRef.value?.formData || formData.value;

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
            // Captura erros de validação do Inertia (objeto com campo: mensagem)
            if (error && typeof error === "object") {
              // Converte para o formato esperado pelo FormRenderer
              const validationErrors: Record<string, string | string[]> = {};
              Object.keys(error).forEach((key) => {
                const errorValue = error[key];
                // Se for array, pega o primeiro erro
                validationErrors[key] = Array.isArray(errorValue)
                  ? errorValue[0]
                  : errorValue;
              });
              formErrors.value = validationErrors;
            }

            emit("error", error);
          },
        },
        dataToSubmit
      );
    } finally {
      isSubmitting.value = false;
    }
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
