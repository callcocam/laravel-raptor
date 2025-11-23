<!--
 * FormFieldCheckboxGroup - Multiple checkbox selection with search and collapse
 *
 * Features:
 * - Multiple checkbox selection
 * - Search/filter functionality
 * - Collapsible container
 * - Grid layout support
 * - Inline layout support
 -->
<template>
  <Field :data-invalid="hasError" class="space-y-3">
    <div class="space-y-2">
      <FieldLabel>
        {{ column.label }}
        <span v-if="column.required" class="text-destructive">*</span>
      </FieldLabel>

      <FieldDescription
        v-if="column.description || column.helpText || column.hint || column.tooltip"
      >
        {{ column.description || column.helpText || column.hint || column.tooltip }}
      </FieldDescription>
    </div>

    <!-- Search Field -->
    <div v-if="hasSearch" class="relative">
      <Input v-model="searchQuery" type="text" placeholder="Buscar..." class="pl-9" />
      <Search
        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"
      />
    </div>

    <!-- Collapsible Container -->
    <Collapsible v-model:open="isOpen">
      <CollapsibleTrigger as-child>
        <Button
          variant="ghost"
          size="sm"
          class="w-full justify-between p-2 h-auto font-normal"
        >
          <span class="text-sm">
            {{ selectedCount }} de {{ filteredOptions.length }} selecionado(s)
          </span>
          <ChevronDown
            :class="['h-4 w-4 transition-transform duration-200', isOpen && 'rotate-180']"
          />
        </Button>
      </CollapsibleTrigger>

      <CollapsibleContent class="space-y-2 pt-2">
        <!-- Select All / Deselect All -->
        <div v-if="showSelectAll" class="flex items-center space-x-2 pb-2 border-b">
          <Checkbox
            :id="`${column.name}-select-all`"
            :model-value="isAllSelected"
            :indeterminate="isSomeSelected"
            :disabled="isSelectingAll"
            @update:model-value="handleSelectAllChange"
          />
          <FieldLabel
            :for="`${column.name}-select-all`"
            class="font-medium cursor-pointer flex items-center gap-2"
          >
            <span>{{ isAllSelected ? "Desmarcar todos" : "Selecionar todos" }}</span>
            <Loader2
              v-show="isSelectingAll"
              class="h-4 w-4 animate-spin text-muted-foreground"
            />
          </FieldLabel>
        </div>

        <!-- No results message -->
        <div
          v-if="filteredOptions.length === 0"
          class="text-sm text-muted-foreground py-4 text-center"
        >
          Nenhum resultado encontrado
        </div>

        <!-- Checkbox items -->
        <div
          v-else
          :class="[
            column.inline
              ? 'flex flex-wrap gap-4'
              : (column.columns || 0) > 1
              ? getGridClass()
              : 'space-y-2',
          ]"
        >
          <div
            v-for="option in filteredOptions"
            :key="option.value"
            class="flex items-center space-x-2"
          >
            <Checkbox
              :id="`${column.name}-${option.value}`"
              :name="`${column.name}[]`"
              :value="option.value"
              :model-value="isChecked(option.value)"
              @update:model-value="(checked: boolean | 'indeterminate') => toggleOption(option.value, checked === true)"
              :aria-invalid="hasError"
            />
            <FieldLabel
              :for="`${column.name}-${option.value}`"
              class="font-normal cursor-pointer"
            >
              {{ option.label }}
            </FieldLabel>
          </div>
        </div>
      </CollapsibleContent>
    </Collapsible>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Field, FieldLabel, FieldDescription, FieldError } from "@/components/ui/field";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  Collapsible,
  CollapsibleTrigger,
  CollapsibleContent,
} from "@/components/ui/collapsible";
import { Search, ChevronDown, Loader2 } from "lucide-vue-next";

interface CheckboxOption {
  label: string;
  value: string | number;
}

interface FormColumn {
  name: string;
  label?: string;
  required?: boolean;
  description?: string;
  tooltip?: string;
  helpText?: string;
  hint?: string;
  default?: string[];
  options?: CheckboxOption[];
  multiple?: boolean;
  layout?: "horizontal" | "vertical";
  inline?: boolean;
  columns?: number;
  searchable?: boolean;
  showSelectAll?: boolean;
}

interface Props {
  column: FormColumn;
  modelValue?: string[] | null | undefined;
  error?: string | string[];
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
});

const emit = defineEmits<{
  (e: "update:modelValue", value: (string | number)[]): void;
}>();

const searchQuery = ref("");
const isOpen = ref(true);
const isSelectingAll = ref(false);

// Estado interno para gerenciar valores selecionados
const selectedValues = ref<(string | number)[]>([]);

// Sincronizar modelValue -> selectedValues (quando props.modelValue mudar)
watch(() => props.modelValue, (newModelValue) => {
  selectedValues.value = Array.isArray(newModelValue) ? [...newModelValue] : [];
}, { immediate: true });

// Sincronizar selectedValues -> modelValue (quando selectedValues mudar)
watch(selectedValues, (newSelectedArray) => {
  const currentModelArray = Array.isArray(props.modelValue) ? props.modelValue : [];

  // Comparar se o conteúdo é diferente
  const isSameContent =
    newSelectedArray.length === currentModelArray.length &&
    newSelectedArray.every(value =>
      currentModelArray.some(v => String(v) === String(value))
    );

  if (!isSameContent) {
    emit("update:modelValue", newSelectedArray.length > 0 ? [...newSelectedArray] : []);
  }
}, { deep: true });

const hasError = computed(() => !!props.error);
const hasSearch = computed(
  () => props.column.searchable !== false && (props.column.options?.length ?? 0) > 5
);
const showSelectAll = computed(
  () => props.column.showSelectAll !== false && (props.column.options?.length ?? 0) > 3
);

const errorArray = computed(() => {
  if (!props.error) return [];
  if (Array.isArray(props.error)) {
    return props.error.map((msg) => ({ message: msg }));
  }
  return [{ message: props.error }];
});

const filteredOptions = computed(() => {
  const options = props.column.options || [];

  if (!searchQuery.value) {
    return options;
  }

  const query = searchQuery.value.toLowerCase();
  return options.filter((option) => option.label.toLowerCase().includes(query));
});

const selectedCount = computed(() => selectedValues.value.length);

const isAllSelected = computed(() => {
  if (filteredOptions.value.length === 0) return false;
  return filteredOptions.value.every((option) =>
    selectedValues.value.includes(String(option.value))
  );
});

const isSomeSelected = computed(() => {
  if (selectedValues.value.length === 0) return false;
  if (isAllSelected.value) return false;
  return filteredOptions.value.some((option) =>
    selectedValues.value.includes(String(option.value))
  );
});

const isChecked = (value: string | number) => {
  return selectedValues.value.some(v => String(v) === String(value));
};

const toggleOption = (value: string | number, checked: boolean) => {
  const stringValue = String(value);
  const currentSet = new Set(selectedValues.value.map(String));

  if (checked) {
    currentSet.add(stringValue);
  } else {
    currentSet.delete(stringValue);
  }

  // Atualizar selectedValues diretamente - o watch vai emitir
  selectedValues.value = Array.from(currentSet);
};

const handleSelectAllChange = (checked: boolean | "indeterminate") => {
  console.log('handleSelectAllChange - início', { checked, selectedValues: selectedValues.value });

  isSelectingAll.value = true;

  // Usa requestAnimationFrame para garantir que o navegador renderize o loading
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      if (checked === true) {
        // Selecionar todos os filtrados
        const allValues = filteredOptions.value.map((option) => String(option.value));
        selectedValues.value = [...new Set([...selectedValues.value.map(String), ...allValues])];
        console.log('Selecionando todos', { total: selectedValues.value.length });
      } else {
        // Desmarcar todos os filtrados
        const filteredValues = new Set(
          filteredOptions.value.map((option) => String(option.value))
        );
        selectedValues.value = selectedValues.value.filter((v) => !filteredValues.has(String(v)));
        console.log('Desmarcando todos', { total: selectedValues.value.length });
      }

      // Delay para garantir que o usuário veja o feedback
      setTimeout(() => {
        isSelectingAll.value = false;
      }, 500);
    });
  });
};

const getGridClass = () => {
  const cols = props.column.columns || 1;

  // Tailwind precisa das classes completas em tempo de build
  const gridClasses: Record<number, string> = {
    1: "grid grid-cols-1 gap-3",
    2: "grid grid-cols-2 gap-3",
    3: "grid grid-cols-3 gap-3",
    4: "grid grid-cols-4 gap-3",
  };

  return gridClasses[cols] || "grid grid-cols-1 gap-3";
};
</script>
