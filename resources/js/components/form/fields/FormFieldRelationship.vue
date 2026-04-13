<!--
 * FormFieldRelationship - Combobox com busca dinâmica para relacionamentos
 *
 * Componente nativo sem dependências shadcn/vue.
 * Suporta busca com debounce via Inertia + autoComplete.
 -->
<template>
    <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
        <div class="flex items-center justify-between w-full">
            <FieldLabel v-if="column.label" :for="column.name">
                {{ column.label }}
                <span v-if="column.required" class="text-destructive">*</span>
            </FieldLabel>
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
                            v-model="searchQuery"
                            type="text"
                            :placeholder="`Buscar ${column.label?.toLowerCase() || 'item'}...`"
                            class="flex h-8 w-full rounded-sm bg-transparent px-2 py-1 text-sm outline-none placeholder:text-muted-foreground"
                            @keydown.escape="close"
                            @keydown.enter.prevent="selectFirst"
                        />
                    </div>
                    <div class="border-t border-border" />

                    <!-- Options list -->
                    <div class="max-h-60 overflow-y-auto p-1">
                        <div
                            v-if="isSearching"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            Buscando...
                        </div>
                        <div
                            v-else-if="filteredOptions.length === 0"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            Nenhum resultado encontrado.
                        </div>
                        <button
                            v-else
                            v-for="option in filteredOptions"
                            :key="String(option.value)"
                            type="button"
                            :class="[
                                'flex w-full items-center rounded-sm px-2 py-1.5 text-sm cursor-default',
                                'hover:bg-accent hover:text-accent-foreground',
                                String(internalValue) === String(option.value)
                                    ? 'bg-accent text-accent-foreground'
                                    : '',
                            ]"
                            @click="handleSelect(option)"
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

        <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
            {{ column.helpText || column.hint || column.tooltip }}
        </FieldDescription>

        <FieldError :errors="errorArray" />
    </Field>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, X } from 'lucide-vue-next';
import { onClickOutside, useDebounceFn } from '@vueuse/core';
import { Field, FieldDescription, FieldError, FieldLabel } from '~/components/ui/field';
import { useAutoComplete } from '../../../composables/useAutoComplete';

interface RelationshipOption {
    label: string;
    value: string | number;
    data?: Record<string, any>;
}

interface FormColumn {
    name: string;
    label?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    readonly?: boolean;
    relationship?: string;
    searchable?: boolean;
    multiple?: boolean;
    preload?: boolean;
    searchMinLength?: number;
    searchDebounce?: number;
    titleAttribute?: string;
    keyAttribute?: string;
    options?: RelationshipOption[] | Record<string, string>;
    optionsData?: Record<string, any>;
    tooltip?: string;
    helpText?: string;
    hint?: string;
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
const searchQuery = ref('');
const isSearching = ref(false);
const searchResults = ref<RelationshipOption[]>([]);
const containerRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);

onClickOutside(containerRef, () => close());

const hasError = computed(() => !!props.error);

const errorArray = computed(() => {
    if (!props.error) return [];
    if (Array.isArray(props.error)) {
        return props.error.map((msg) => ({ message: msg }));
    }
    return [{ message: props.error }];
});

const initialOptions = computed(() => {
    if (!props.column.options) return [];
    if (!Array.isArray(props.column.options)) {
        return Object.entries(props.column.options).map(([value, label]) => ({
            value,
            label: String(label),
        }));
    }
    return props.column.options;
});

const filteredOptions = computed(() => {
    if (searchQuery.value && searchResults.value.length > 0) {
        return searchResults.value;
    }
    return initialOptions.value;
});

const selectedOption = computed(() =>
    filteredOptions.value.find((opt) => String(opt.value) === String(internalValue.value ?? '')),
);

const optionsData = computed(() => {
    const data = props.column.optionsData || {};
    return Array.isArray(data) ? {} : data;
});

useAutoComplete(props.column.name, props.column.autoComplete, optionsData);

const internalValue = computed({
    get: () => (props.modelValue ? String(props.modelValue) : undefined),
    set: (value) => emit('update:modelValue', value || null),
});

function toggleOpen() {
    if (props.column.disabled) return;
    open.value = !open.value;
    if (open.value) {
        searchQuery.value = '';
        nextTick(() => searchInputRef.value?.focus());
    }
}

function close() {
    open.value = false;
    searchQuery.value = '';
    searchResults.value = [];
}

function handleSelect(option: RelationshipOption) {
    internalValue.value = String(option.value);
    close();
}

function clearSelection() {
    internalValue.value = undefined;
}

function selectFirst() {
    if (filteredOptions.value.length > 0) {
        handleSelect(filteredOptions.value[0]);
    }
}

const performSearch = useDebounceFn((query: string) => {
    if (!props.column.searchable || !props.column.relationship) {
        return;
    }

    const minLength = props.column.searchMinLength || 2;
    if (query.length < minLength) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;

    const currentUrl = new URL(window.location.href);
    const searchParams = new URLSearchParams(currentUrl.search);
    searchParams.set(`search_${props.column.name}`, query);
    searchParams.set('relationship', props.column.relationship);

    router.reload({
        data: Object.fromEntries(searchParams),
        only: [props.column.name + '_search_results'],
        onSuccess: (pageResponse) => {
            const results = (pageResponse.props as any)[props.column.name + '_search_results'];
            if (results && Array.isArray(results)) {
                searchResults.value = results;
            }
            isSearching.value = false;
        },
        onError: () => {
            isSearching.value = false;
        },
    });
}, props.column.searchDebounce || 300);

watch(searchQuery, (newQuery) => {
    if (newQuery && props.column.searchable) {
        performSearch(newQuery);
    } else {
        searchResults.value = [];
    }
});

watch(open, (isOpen) => {
    if (!isOpen) {
        searchQuery.value = '';
    }
});
</script>
