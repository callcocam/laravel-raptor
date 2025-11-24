<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import ResourceLayout from "~/layouts/ResourceLayout.vue";
import FormRenderer from "~/components/form/FormRenderer.vue";
import FormActions from "~/components/form/FormActions.vue";
import BreadcrumbRenderer from "~/components/breadcrumbs/BreadcrumbRenderer.vue";
import HeaderActions from "~/components/table/HeaderActions.vue";
import type { BackendBreadcrumb } from '~/composables/useBreadcrumbs'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

interface FormColumn {
  name: string;
  label?: string;
  component?: string;
  required?: boolean;
  [key: string]: any;
}

interface Props {
  message?: string;
  resourceLabel?: string;
  breadcrumbs?: BackendBreadcrumb[];
  model?: any;
  form?: {
    columns: FormColumn[];
    model?: Record<string, any>;
    formActions?: any[];
  };
  pageHeaderActions?: any[];
  action?: string;
}

const props = defineProps<Props>();

const layoutProps = {
  message: props.message,
  resourceLabel: props.resourceLabel,
  breadcrumbs: props.breadcrumbs,
};

// Inicializa o formulário Inertia com os valores do modelo
const initialData = props.form?.model || props.model || {};

// Garantir que todos os campos das colunas do formulário existam
// Isso é necessário para o Inertia form rastrear corretamente
if (props.form?.columns) {
  props.form.columns.forEach((column: any) => {
    if (!(column.name in initialData)) {
      initialData[column.name] = null;
    }
  });
}

const formData = useForm(initialData);
</script>

<template>
  <ResourceLayout v-bind="layoutProps" title="Editar">
    <template #header>
      <!-- Breadcrumbs com Header Actions -->
      <div v-if="breadcrumbs && breadcrumbs.length > 0" class="border-b bg-background">
        <div class="w-full flex items-center pb-4">
          <BreadcrumbRenderer
            :items="breadcrumbs"
            :config="{
              component: 'breadcrumb-page-header',
              resourceLabel: `Editar ${resourceLabel}`,
              message: message,
            }"
          >
            <!-- Header Actions renderizadas ao lado dos breadcrumbs -->
            <HeaderActions
              v-if="pageHeaderActions && pageHeaderActions.length"
              :actions="pageHeaderActions"
            />
          </BreadcrumbRenderer>
        </div>
      </div>
    </template>
    <template #content>
      <div class="space-y-4">
        <Card class="rounded">
          <CardContent>
            <FormRenderer
              v-if="form?.columns"
              :columns="form.columns"
              v-model="formData"
              :errors="formData.errors"
              :action="action"
              :method="'put'"
            >
              <template #actions>
                <FormActions
                  :actions="form?.formActions"
                  :processing="formData.processing"
                />
              </template>
            </FormRenderer>
          </CardContent>
        </Card>
      </div>
    </template>
  </ResourceLayout>
</template>
