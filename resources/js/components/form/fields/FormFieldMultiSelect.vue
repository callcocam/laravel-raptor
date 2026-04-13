<!--
 * FormFieldMultiSelect - Multi-select field component
 *
 * Componente nativo sem dependências shadcn/vue.
 * Suporte a múltiplas seleções, busca, API e autoComplete.
 -->
<template>
    <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
        <div class="flex w-full items-center justify-between">
            <FieldLabel v-if="column.label" :for="column.name">
                {{ column.label }}
                <span v-if="column.required" class="text-destructive">*</span>
            </FieldLabel>
        </div>

        <!-- Loading state -->
        <div
            v-if="loading"
            class="flex items-center gap-2 rounded-md border border-input bg-background px-3 py-2"
        >
            <Loader2 class="h-4 w-4 animate-spin text-muted-foreground" />
            <span class="text-sm text-muted-foreground">Carregando opções...</span>
        </div>

        <!-- Multi-Select nativo -->
        <div v-else class="relative" ref="containerRef">
            <!-- Trigger -->
            <button
                type="button"
                :class="[
                    'flex h-9 w-full items-center justify-between rounded-md border bg-background px-3 py-2 text-sm',
                    'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
                    hasError ? 'border-destructive' : 'border-input',
                    !selectedValues.length ? 'text-muted-foreground' : 'text-foreground',
                ]"
                @click="toggleOpen"
            >
                <span v-if="selectedValues.length === 0">
                    {{ column.placeholder || 'Selecionar...' }}
                </span>
                <span v-else class="text-foreground">
                    {{ selectedValues.length }} selecionado(s)
                </span>
                <div class="flex items-center gap-1">
                    <button
                        v-if="selectedValues.length > 0"
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        @click.stop="clearAll"
                        aria-label="Limpar seleção"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                    <ChevronDown class="h-4 w-4 opacity-50" />
                </div>
            </button>

            <!-- Dropdown -->
            <Transition
                enter-active-class="transition-all duration-150 ease-out"
                leave-active-class="transition-all duration-100 ease-in"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="isOpen"
                    class="absolute z-50 mt-1 w-full rounded-md border bg-popover text-popover-foreground shadow-md"
                >
                    <div class="p-2 space-y-2">
                        <!-- Search input -->
                        <input
                            v-if="column.searchable"
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar..."
                            class="flex h-8 w-full rounded-sm border border-input bg-background px-2 py-1 text-sm outline-none placeholder:text-muted-foreground focus:ring-1 focus:ring-ring"
                            @input="handleSearch"
                            @keydown.escape="close"
                        />

                        <!-- Selected badges -->
                        <div
                            v-if="selectedValues.length > 0"
                            class="flex flex-wrap gap-1 rounded-md bg-muted/50 p-2"
                        >
                            <span
                                v-for="value in selectedValues"
                                :key="value"
                                class="inline-flex items-center gap-1 rounded-full border border-transparent bg-secondary text-secondary-foreground px-2.5 py-0.5 text-xs font-semibold"
                            >
                                {{ getLabelForValue(value) }}
                                <button
                                    type="button"
                                    class="ml-0.5 hover:text-foreground"
                                    @click.stop="removeValue(value)"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </span>
                        </div>

                        <!-- Options list -->
                        <div class="max-h-48 overflow-y-auto rounded-md border border-border">
                            <div
                                v-if="availableOptions.length === 0"
                                class="py-6 text-center text-sm text-muted-foreground"
                            >
                                {{
                                    searchQuery
                                        ? 'Nenhuma opção encontrada'
                                        : 'Nenhuma opção disponível'
                                }}
                            </div>
                            <button
                                v-for="option in availableOptions"
                                :key="getOptionValue(option)"
                                type="button"
                                class="flex w-full items-center gap-2 px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:outline-none"
                                @click.stop="toggleValue(option)"
                            >
                                <!-- Native checkbox visual -->
                                <span
                                    :class="[
                                        'inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-sm border border-primary',
                                        isSelected(option)
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-background',
                                    ]"
                                >
                                    <Check v-if="isSelected(option)" class="h-3 w-3 stroke-[3]" />
                                </span>
                                <span class="flex-1 text-left">{{ getOptionLabel(option) }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <FieldDescription v-if="column.helperText">
            {{ column.helperText }}
        </FieldDescription>

        <FieldError :errors="errorArray" />
    </Field>
</template>

<script setup lang="ts">
import { Check, ChevronDown, Loader2, X } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { onClickOutside } from '@vueuse/core';
import { Field, FieldDescription, FieldError, FieldLabel } from '~/components/ui/field';

interface FormColumn {
    name: string;
    label?: string;
    helperText?: string;
    required?: boolean;
    placeholder?: string;
    searchable?: boolean;
    options?: Record<string, string> | Array<{ id: string; name: string }>;
    apiEndpoint?: string;
    table?: string;
    labelColumn?: string;
    valueColumn?: string;
    autoComplete?: {
        enabled: boolean;
        fields: Array<{
            source: string;
            target: string;
            isFixedValue: boolean;
        }>;
        optionValueKey?: string;
        optionLabelKey?: string;
        returnFullObject?: boolean;
    };
}

interface Props {
    column: FormColumn;
    modelValue?: string[];
    error?: string | string[];
    optionsData?: Record<string, any>;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    error: undefined,
    optionsData: () => ({}),
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
    (e: 'autoComplete', data: { source: string; target: string; value: any }): void;
}>();

const loading = ref(false);
const searchQuery = ref('');
const localOptions = ref<any[]>([]);
const isOpen = ref(false);
const containerRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);

onClickOutside(containerRef, () => close());

const selectedValues = computed({
    get: () => (Array.isArray(props.modelValue) ? props.modelValue : []),
    set: (value) => emit('update:modelValue', value),
});

const availableOptions = computed(() => {
    let options = localOptions.value;
    if (searchQuery.value && props.column.searchable) {
        const query = searchQuery.value.toLowerCase();
        options = options.filter((opt) => getOptionLabel(opt).toLowerCase().includes(query));
    }
    return options;
});

const hasError = computed(
    () => props.error && (Array.isArray(props.error) ? props.error.length > 0 : true),
);

const errorArray = computed(() => {
    if (!props.error) return [];
    const errors = Array.isArray(props.error) ? props.error : [props.error];
    return errors.map((error) => (typeof error === 'string' ? { message: error } : error));
});

const getOptionValue = (option: any): string => {
    if (typeof option === 'string' || typeof option === 'number') return String(option);
    if ('value' in option) return String(option.value);
    const valueKey = props.column.autoComplete?.optionValueKey || props.column.valueColumn || 'id';
    return String(option[valueKey] ?? option.id);
};

const getOptionLabel = (option: any): string => {
    if (typeof option === 'string') return option;
    if (typeof option === 'number') return String(option);
    if ('label' in option && option.label) return String(option.label);
    const labelKey = props.column.autoComplete?.optionLabelKey || props.column.labelColumn || 'name';
    return String(option[labelKey] ?? option.name ?? option.id);
};

const getLabelForValue = (value: string): string => {
    const option = localOptions.value.find((opt) => getOptionValue(opt) === value);
    return option ? getOptionLabel(option) : value;
};

const isSelected = (option: any): boolean => selectedValues.value.includes(getOptionValue(option));

const toggleValue = (option: any) => {
    const value = getOptionValue(option);
    if (isSelected(option)) {
        selectedValues.value = selectedValues.value.filter((v) => v !== value);
    } else {
        selectedValues.value = [...selectedValues.value, value];
        if (props.column.autoComplete?.enabled && props.column.autoComplete.fields.length > 0) {
            const optionData = props.optionsData[value] || option;
            props.column.autoComplete.fields.forEach((field) => {
                emit('autoComplete', {
                    source: field.source,
                    target: field.target,
                    value: optionData[field.source] ?? optionData,
                });
            });
        }
    }
};

const removeValue = (value: string) => {
    selectedValues.value = selectedValues.value.filter((v) => v !== value);
};

const clearAll = () => {
    selectedValues.value = [];
};

function toggleOpen() {
    isOpen.value = !isOpen.value;
    if (isOpen.value && props.column.searchable) {
        nextTick(() => searchInputRef.value?.focus());
    }
}

function close() {
    isOpen.value = false;
    searchQuery.value = '';
}

const handleSearch = (() => {
    let timeout: ReturnType<typeof setTimeout> | null = null;
    return () => {
        if (timeout) clearTimeout(timeout);
        if (!props.column.searchable) return;
        timeout = setTimeout(() => {
            if (props.column.apiEndpoint && searchQuery.value.length > 0) {
                loadFromApi();
            }
        }, 300);
    };
})();

const loadOptions = async () => {
    loading.value = true;
    try {
        if (props.column.options) {
            const opts = props.column.options;
            if (Array.isArray(opts)) {
                localOptions.value = opts;
            } else {
                localOptions.value = Object.entries(opts).map(([value, label]) => ({
                    id: value,
                    name: label,
                }));
            }
        } else if (props.column.apiEndpoint) {
            await loadFromApi();
        } else if (props.column.table) {
            await loadFromTable();
        }
    } finally {
        loading.value = false;
    }
};

const loadFromApi = async () => {
    if (!props.column.apiEndpoint) return;
    try {
        const url = new URL(props.column.apiEndpoint, window.location.origin);
        if (searchQuery.value) url.searchParams.append('search', searchQuery.value);
        const response = await fetch(url.toString());
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        localOptions.value = Array.isArray(data) ? data : data.data || data.options || [];
    } catch (error) {
        console.error('Error loading options from API:', error);
        localOptions.value = [];
    }
};

const loadFromTable = async () => {
    if (!props.column.table) return;
    try {
        const endpoint = `/api/${props.column.table}`;
        const response = await fetch(endpoint);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        localOptions.value = Array.isArray(data) ? data : data.data || [];
    } catch (error) {
        console.error('Error loading options from table:', error);
        localOptions.value = [];
    }
};

onMounted(() => loadOptions());

watch(
    () => props.column.apiEndpoint,
    () => {
        if (props.column.apiEndpoint) loadOptions();
    },
);
</script>
