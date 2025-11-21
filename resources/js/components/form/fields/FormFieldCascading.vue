<!--
 * FormFieldCascading - Container for cascading select fields
 *
 * Manages state for all cascading fields and provides context via provide/inject
 * Fields reload via Inertia.js when values change, maintaining scroll position and state
 -->
<script setup lang="ts">
import {
  FieldDescription,
  FieldGroup,
  FieldLegend,
  FieldSet,
} from "@/components/ui/field";

import { computed, provide, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import FormFieldCascadingItem from "./FormFieldCascadingItem.vue";

interface FormColumn {
  name: string;
  label?: string;
  type?: string;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  dependsOn?: string | null;
  tooltip?: string;
  helpText?: string;
  hint?: string;
  default?: string | number;
  prepend?: string;
  append?: string;
  prefix?: string;
  suffix?: string;
  options?: Array<{ value: string | number; label: string }> | Record<string, string>;
}

interface FormCascadingColumn {
  name: string;
  label?: string;
  helpText?: string;
  required?: boolean;
  fields?: FormColumn[];
}

interface Props {
  column: FormCascadingColumn;
  modelValue?: Record<string, any>;
  error?: string | string[];
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => ({}),
  error: undefined,
});

const emit = defineEmits<{
  (e: "update:modelValue", value: Record<string, any>): void;
}>();

const page = usePage();

const fields = computed(() => {
  return props.column.fields || [];
});

// Provide fields immediately so child components can access it
provide("cascadingFields", fields);

// Internal state for all cascading field values
const cascadingValues = ref<Record<string, any>>({});

/**
 * Initialize cascading values from URL query parameters
 * This ensures the fields retain their values after Inertia reload
 */
const initializeFromQuery = () => {
  const url = new URL(page.url, window.location.origin);
  const params = Object.fromEntries(new URLSearchParams(url.search));

  fields.value.forEach((field) => {
    const queryValue = params[field.name];
    if (queryValue !== undefined && queryValue !== null && queryValue !== "") {
      cascadingValues.value[field.name] = queryValue;
    }
  });
};

// Initialize on mount
initializeFromQuery();

/**
 * Watch for URL changes (e.g., browser back/forward)
 * Re-initialize values when URL changes
 */
watch(
  () => page.url,
  () => {
    initializeFromQuery();
  }
);

/**
 * Get current values of all cascading fields
 * Provided to child components via inject
 */
const getCascadingValues = () => {
  return { ...cascadingValues.value };
};

/**
 * Update a specific cascading field value
 */
const updateCascadingValue = (fieldName: string, value: any) => {
  if (value === null || value === undefined || value === "") {
    delete cascadingValues.value[fieldName];
  } else {
    cascadingValues.value[fieldName] = value;
  }

  // Emit the complete values object to parent
  emit("update:modelValue", { ...cascadingValues.value });
};

/**
 * Get the modelValue for a specific field
 */
const getFieldValue = (fieldName: string) => {
  return cascadingValues.value[fieldName] || null;
};

// Provide getCascadingValues function to child components
provide("getCascadingValues", getCascadingValues);
</script>

<template>
  <FieldSet>
    <FieldLegend>{{ column.label }}</FieldLegend>
    <FieldDescription v-if="column.helpText"> {{ column.helpText }} </FieldDescription>
    <FieldGroup class="grid grid-cols-12 gap-4">
      <FormFieldCascadingItem
        v-for="field in fields"
        :key="field.name"
        :column="field"
        :modelValue="getFieldValue(field.name)"
        :error="error"
        @update:modelValue="(value: any) => updateCascadingValue(field.name, value)"
      />
    </FieldGroup>
  </FieldSet>
</template>
