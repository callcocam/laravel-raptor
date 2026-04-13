<!--
 * FormFieldSearchCombobox - Combobox com busca server-side via Inertia
 *
 * Componente nativo sem dependências shadcn/vue.
 * Para filtragem client-side use FormFieldCombobox.
 -->
<template>
    <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
        <div class="flex items-center justify-between w-full">
            <FieldLabel v-if="column.label" :for="column.name">
                {{ column.label }}
                <span v-if="column.required" class="text-destructive">*</span>
            </FieldLabel>
            <HintRenderer v-if="column.hint" :hint="column.hint" class="ml-2" />
        </div>

        <div class="relative" ref="containerRef">
            <!-- Trigger -->
            <button
                type="button"
                :disabled="column.disabled"
                :aria-expanded="open"
                :aria-haspopup="true"
                :aria-invalid="hasError"
                :class="[
                    'flex h-9 w-full items-center justify-between rounded-md border bg-background px-3 py-2 text-sm',
                    'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    hasError ? 'border-destructive' : 'border-input',
                    !selectedOption ? 'text-muted-foreground' : 'text-foreground',
                ]"
                @click="toggleOpen"
            >
                <span class="truncate">
                    {{ selectedOption ? selectedOption.label : (column.placeholder || 'Selecione...') }}
                </span>
                <div class="flex items-center gap-1">
                    <button
                        v-if="internalValue"
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        @click.stop="clearSelection"
                        aria-label="Limpar seleção"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                    <ChevronsUpDown class="h-4 w-4 shrink-0 opacity-50" />
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
                    v-if="open"
                    class="absolute z-50 mt-1 w-full rounded-md border bg-popover text-popover-foreground shadow-md"
                >
                    <!-- Search input -->
                    <div class="p-1">
                        <input
                            ref="searchInputRef"
                            v-model="search"
                            type="text"
                            :placeholder="column.searchPlaceholder || 'Buscar...'"
                            class="flex h-8 w-full rounded-sm bg-transparent px-2 py-1 text-sm outline-none placeholder:text-muted-foreground"
                            @keydown.escape="close"
                            @keydown.enter.prevent="selectFirst"
                        />
                    </div>
                    <div class="border-t border-border" />

                    <!-- Options list -->
                    <div class="max-h-60 overflow-y-auto p-1">
                        <div
                            v-if="displayOptions.length === 0"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            {{ column.emptyText || 'Nenhum resultado encontrado.' }}
                        </div>
                        <button
                            v-for="option in displayOptions"
                            :key="String(option.value)"
                            type="button"
                            :class="[
                                'flex w-full items-center rounded-sm px-2 py-1.5 text-sm cursor-default',
                                'hover:bg-accent hover:text-accent-foreground',
                                String(internalValue) === String(option.value)
                                    ? 'bg-accent text-accent-foreground'
                                    : '',
                            ]"
                            @click="selectOption(String(option.value))"
                        >
                            <Check
                                class="mr-2 h-4 w-4 shrink-0"
                                :class="String(internalValue) === String(option.value) ? 'opacity-100' : 'opacity-0'"
                            />
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </Transition>
        </div>

        <FieldDescription v-if="column.helpText">
            {{ column.helpText }}
        </FieldDescription>

        <FieldError :errors="errorArray" />
    </Field>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { Check, ChevronsUpDown, X } from 'lucide-vue-next';
import { onClickOutside, useDebounceFn } from '@vueuse/core';
import { router } from '@inertiajs/vue3';
import { Field, FieldDescription, FieldError, FieldLabel } from '~/components/ui/field';
import HintRenderer from '../HintRenderer.vue';
import { useAutoComplete } from '../../../composables/useAutoComplete';

interface ComboboxOption {
    label: string;
    value: string | number;
    [key: string]: any;
}

interface FormColumn {
    name: string;
    label?: string;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    required?: boolean;
    disabled?: boolean;
    readonly?: boolean;
    options?: ComboboxOption[] | Record<string, string>;
    optionsData?: Record<string, any>;
    tooltip?: string;
    helpText?: string;
    hint?: string | any[];
    searchable?: boolean;
    searchDebounce?: number;
    autoComplete?: {
        enabled: boolean;
        fields: Array<{ source: string; target: string }>;
        optionValueKey: string | null;
        optionLabelKey: string | null;
        returnFullObject: boolean;
    };
}

interface Props {
    column: FormColumn;
    modelValue?: string | number | null;
    error?: string | string[];
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    error: undefined,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
}>();

const open = ref(false);
const search = ref('');
const containerRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);
const selectedOptionCache = ref<ComboboxOption | null>(null);

onClickOutside(containerRef, () => close());

const hasError = computed(() => !!props.error);

const errorArray = computed(() => {
    if (!props.error) return [];
    if (Array.isArray(props.error)) {
        return props.error.map((msg) => ({ message: msg }));
    }
    return [{ message: props.error }];
});

const normalizedOptions = computed(() => {
    if (!props.column.options) return [];
    if (Array.isArray(props.column.options)) {
        return props.column.options as ComboboxOption[];
    }
    return Object.entries(props.column.options).map(([value, label]) => ({
        value,
        label: String(label),
    }));
});

// Garante que o item selecionado está sempre visível, mesmo sem estar na lista atual
const displayOptions = computed(() => {
    const opts = normalizedOptions.value;
    if (internalValue.value && selectedOptionCache.value) {
        const found = opts.some((opt) => String(opt.value) === String(internalValue.value));
        if (!found) {
            return [selectedOptionCache.value, ...opts];
        }
    }
    return opts;
});

const optionsData = computed(() => {
    const data = props.column.optionsData || {};
    return Array.isArray(data) ? {} : data;
});

useAutoComplete(props.column.name, props.column.autoComplete, optionsData);

const internalValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const selectedOption = computed(() => {
    if (!internalValue.value) return null;

    const found = normalizedOptions.value.find(
        (opt) => String(opt.value) === String(internalValue.value),
    );

    if (found) {
        selectedOptionCache.value = found;
        return found;
    }

    return selectedOptionCache.value?.value === internalValue.value
        ? selectedOptionCache.value
        : null;
});

function toggleOpen() {
    if (props.column.disabled) return;
    open.value = !open.value;
    if (open.value) {
        search.value = '';
        nextTick(() => searchInputRef.value?.focus());
    }
}

function close() {
    open.value = false;
    search.value = '';
}

function selectOption(value: string) {
    const newValue = value === String(internalValue.value ?? '') ? null : value;

    if (newValue) {
        const option = displayOptions.value.find((opt) => String(opt.value) === value);
        if (option) {
            selectedOptionCache.value = option;
        }
    } else {
        selectedOptionCache.value = null;
    }

    internalValue.value = newValue;
    close();
}

function clearSelection() {
    selectedOptionCache.value = null;
    internalValue.value = null;
}

function selectFirst() {
    if (displayOptions.value.length > 0) {
        selectOption(String(displayOptions.value[0].value));
    }
}

function performSearch(query: string) {
    if (!props.column.searchable) return;

    const options = {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['form'],
        onFinish: () => {
            nextTick(() => searchInputRef.value?.focus());
        },
    };

    if (query.trim() === '') {
        router.get(window.location.pathname, {}, options);
    } else {
        router.get(window.location.pathname, { [props.column.name]: query }, options);
    }
}

const debouncedSearch = useDebounceFn(
    (query: string) => performSearch(query),
    props.column.searchDebounce || 300,
);

watch(open, (isOpen) => {
    if (!isOpen) {
        search.value = '';
    }
});

watch(search, (newValue) => {
    if (props.column.searchable) {
        debouncedSearch(newValue);
    }
});
</script>
