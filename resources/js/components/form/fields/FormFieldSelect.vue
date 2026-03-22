<!--
 * FormFieldSelect - Select input field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnSelect with improved accessibility
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

        <div class="relative">
            <Select
                v-model="internalValue"
                :required="column.required"
                :disabled="column.disabled"
            >
                <SelectTrigger
                    class="h-9 w-full"
                    :class="hasError ? 'border-destructive' : ''"
                    :aria-invalid="hasError"
                >
                    <SelectValue
                        :placeholder="column.placeholder || 'Selecione...'"
                    />
                </SelectTrigger>
                <SelectContent class="w-full">
                    <SelectItem
                        v-for="option in options"
                        :key="getOptionValue(option)"
                        :value="getOptionValue(option)"
                        :label="getOptionLabel(option)"
                    >
                        {{ getOptionLabel(option) }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <!-- Clear button -->
            <button
                v-if="internalValue"
                type="button"
                @click="clearSelection"
                class="absolute top-1/2 right-10 -translate-y-1/2 p-1 text-muted-foreground transition-colors hover:text-foreground"
                aria-label="Limpar seleção"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <FieldDescription v-if="column.helpText">
            {{ column.helpText }}
        </FieldDescription>

        <FieldError :errors="errorArray" />
    </Field>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, inject, onMounted, type Ref } from 'vue';
import { X } from 'lucide-vue-next';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '~/components/ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '~/components/ui/select';
import HintRenderer from '../HintRenderer.vue';
import {
    createMultiFieldUpdate,
    type FieldEmitValue,
} from '../../../types/form';

interface SelectOption {
    label?: string;
    value?: string | number;
    data?: Record<string, any>;
    [key: string]: any;
}

interface FormColumn {
    name: string;
    label?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    readonly?: boolean;
    options?: SelectOption[] | Record<string, string>;
    optionsData?: Record<string, any>;
    tooltip?: string;
    helpText?: string;
    hint?: string | any[];
    default?: string | number | null;
    reload?: boolean;
    autoComplete?: {
        enabled: boolean;
        fields: Array<{
            source: string | number | boolean | null;
            target: string;
            isFixedValue?: boolean;
            onlyIfEmpty?: boolean;
        }>;
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
    (e: 'update:modelValue', value: FieldEmitValue): void;
}>();

const hasError = computed(() => !!props.error);
const formData = inject<Ref<Record<string, any>> | undefined>(
    'formData',
    undefined,
);

const errorArray = computed(() => {
    if (!props.error) return [];
    if (Array.isArray(props.error)) {
        return props.error.map((msg) => ({ message: msg }));
    }
    return [{ message: props.error }];
});

const options = computed(() => {
    if (!props.column.options) return [];

    // Comportamento padrão - normaliza options para formato consistente
    if (!Array.isArray(props.column.options)) {
        return Object.entries(props.column.options).map(([value, label]) => ({
            value,
            label,
        }));
    }

    return props.column.options;
});

// Computed para optionsData
const optionsData = computed(() => {
    const data = props.column.optionsData || {};
    // Garantir que seja um objeto, não um array
    return Array.isArray(data) ? {} : data;
});

const getOptionValue = (option: SelectOption | string): string => {
    if (typeof option === 'string') return option;
    return String(option.value ?? option.label ?? '');
};

const getOptionLabel = (option: SelectOption | string): string => {
    if (typeof option === 'string') return option;
    return option.label ?? String(option.value) ?? '';
};

const isEmptyValue = (value: unknown): boolean => {
    if (value === null || value === undefined || value === '') {
        return true;
    }

    if (Array.isArray(value)) {
        return value.length === 0;
    }

    return false;
};

const buildAutoCompleteFields = (value: string | number | null) => {
    console.log('buildAutoCompleteFields called with value:', value);
    if (
        !value ||
        !props.column.autoComplete?.enabled ||
        !props.column.autoComplete.fields?.length
    ) {
        return null;
    }

    const selectedData = optionsData.value[String(value)];

    if (!selectedData) {
        return null;
    }

    const fields: Record<string, any> = {
        [props.column.name]: value,
    };

    props.column.autoComplete.fields.forEach((field) => {
        if (
            field.onlyIfEmpty &&
            formData &&
            !isEmptyValue(formData.value?.[field.target])
        ) {
            return;
        }

        const sourceValue = field.isFixedValue
            ? field.source
            : selectedData[field.source as string];

        if (sourceValue !== undefined && sourceValue !== null) {
            fields[field.target] = sourceValue;
        }
    });

    return Object.keys(fields).length > 1 ? fields : null;
};

const internalValue = computed({
    get: () =>
        props.modelValue
            ? String(props.modelValue)
            : (props.column?.default ?? undefined),
    set: (value) => {
        const normalizedValue = value || null;
        const autoCompleteFields = buildAutoCompleteFields(normalizedValue);

        if (autoCompleteFields) {
            emit('update:modelValue', createMultiFieldUpdate(autoCompleteFields));
        } else {
            emit('update:modelValue', normalizedValue);
        }

        triggerReloadIfNeeded();
    },
});

const clearSelection = () => {
    emit('update:modelValue', null);
    triggerReloadIfNeeded();
};

function triggerReloadIfNeeded() {
    if (props.column.reload) {
        router.reload();
    }
}

onMounted(() => {
    if (props.modelValue === null && props.column.default) {
        emit('update:modelValue', props.column.default);
    }
});
</script>
