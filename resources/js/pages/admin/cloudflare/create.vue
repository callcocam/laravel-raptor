<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import ResourceLayout from '~/layouts/ResourceLayout.vue'
import FormFieldCloudflareDns from '~/components/form/fields/FormFieldCloudflareDns.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ArrowLeft, Cloud } from 'lucide-vue-next'
import type { BackendBreadcrumb } from '@/composables/useBreadcrumbs'

interface Props {
  title?: string
  message?: string
  breadcrumbs?: BackendBreadcrumb[]
  /** URL para o botão Voltar (ex: dashboard) */
  backUrl?: string
  /** Base da API Cloudflare (ex: /cloudflare) */
  apiBaseUrl?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Cloudflare DNS',
  message: 'Gerencie registros DNS: crie ou remova domínios e subdomínios nas suas zones.',
  backUrl: '/dashboard',
  apiBaseUrl: '/cloudflare',
})

const cloudflareColumn = {
  name: 'cloudflare_dns',
  label: 'Zones e registros',
  helpText: 'Selecione uma zone, crie registros (A, CNAME, TXT, etc.) ou remova registros existentes.',
  apiBaseUrl: props.apiBaseUrl,
}
</script>

<template>
  <Head :title="title" />

  <ResourceLayout
    :message="message"
    :resource-label="title"
    :breadcrumbs="breadcrumbs"
    :title="title"
  >
    <template #header>
      <div class="border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight flex items-center gap-2">
              <Cloud class="h-7 w-7 text-primary" />
              {{ title }}
            </h1>
            <p class="text-sm text-muted-foreground max-w-2xl">
              {{ message }}
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <Button variant="outline" size="sm" as-child>
              <Link :href="backUrl" class="inline-flex items-center gap-2">
                <ArrowLeft class="h-4 w-4" />
                Voltar
              </Link>
            </Button>
          </div>
        </div>
      </div>
    </template>

    <template #content>
      <div class="space-y-6">
        <Card class="overflow-hidden">
          <CardHeader class="border-b bg-muted/30">
            <CardTitle class="text-lg">Zones e registros DNS</CardTitle>
            <CardDescription>
              Liste as zones da sua conta, crie novos registros (A, AAAA, CNAME, TXT, etc.) ou remova registros existentes.
              As alterações são feitas diretamente na Cloudflare.
            </CardDescription>
          </CardHeader>
          <CardContent class="p-6">
            <FormFieldCloudflareDns
              :column="cloudflareColumn"
              :model-value="null"
            />
          </CardContent>
        </Card>
      </div>
    </template>
  </ResourceLayout>
</template>
