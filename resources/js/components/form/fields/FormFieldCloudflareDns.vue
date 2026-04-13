<!--
 * FormFieldCloudflareDns - Gerenciar registros DNS na Cloudflare (criar/apagar domínio ou subdomínio)
 * Chama a API do backend (CloudflareController) que usa o token configurado no servidor.
 -->
<template>
  <FieldSet orientation="vertical" class="gap-y-4">
    <FieldLegend v-if="column.label">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLegend>
    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <div v-if="!configured" class="rounded-md border border-amber-500/50 bg-amber-500/10 px-3 py-2 text-sm text-amber-800 dark:text-amber-200">
      {{ notConfiguredMessage }}
    </div>

    <template v-else>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-12">
        <div class="sm:col-span-6">
          <label class="mb-1 block text-sm font-medium">Zone (domínio)</label>
          <Select v-model="selectedZoneId" @update:model-value="onZoneChange">
            <SelectTrigger class="w-full" :disabled="loadingZones">
              <SelectValue :placeholder="loadingZones ? 'Carregando...' : 'Selecione a zone'" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="z in zones"
                :key="z.id"
                :value="z.id"
                :label="`${z.name} (${z.status})`"
              >
                {{ z.name }} ({{ z.status }})
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div v-if="selectedZoneId" class="space-y-4 rounded-md border p-4">
        <h4 class="text-sm font-medium">Criar registro DNS</h4>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-12">
          <div class="sm:col-span-4">
            <label class="mb-1 block text-sm font-medium">Nome (subdomínio ou FQDN)</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="www ou sub.seudominio.com"
              class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
            />
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium">Tipo</label>
            <Select v-model="form.type">
              <SelectTrigger class="w-full">
                <SelectValue placeholder="Tipo" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="t in dnsTypes" :key="t" :value="t" :label="t">{{ t }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="sm:col-span-4">
            <label class="mb-1 block text-sm font-medium">Conteúdo</label>
            <input
              v-model="form.content"
              type="text"
              placeholder="IP ou valor do registro"
              class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
            />
          </div>
          <div class="sm:col-span-2 flex items-end gap-2">
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="form.proxied" class="h-4 w-4 rounded border-primary accent-primary" />
              Proxied
            </label>
            <button
              type="button"
              :disabled="!canCreate || creating"
              class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-3 text-sm text-primary-foreground hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50 transition-colors"
              @click="createRecord"
            >
              {{ creating ? 'Criando...' : 'Criar' }}
            </button>
          </div>
        </div>
        <p v-if="createError" class="text-sm text-destructive">{{ createError }}</p>
        <p v-if="createSuccess" class="text-sm text-green-600 dark:text-green-400">{{ createSuccess }}</p>
      </div>

      <div v-if="selectedZoneId" class="space-y-2">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-medium">Registros da zone</h4>
          <button
            type="button"
            :disabled="loadingRecords"
            class="inline-flex h-8 items-center rounded-md px-2 text-sm text-muted-foreground hover:bg-accent hover:text-accent-foreground disabled:opacity-50 transition-colors"
            @click="fetchRecords"
          >
            {{ loadingRecords ? 'Atualizando...' : 'Atualizar' }}
          </button>
        </div>
        <div v-if="loadingRecords && records.length === 0" class="text-sm text-muted-foreground">
          Carregando registros...
        </div>
        <ul v-else-if="records.length === 0" class="text-sm text-muted-foreground">
          Nenhum registro ou zone sem registros.
        </ul>
        <ul v-else class="divide-y rounded-md border">
          <li
            v-for="r in records"
            :key="r.id"
            class="flex items-center justify-between px-3 py-2 text-sm"
          >
            <span>{{ r.type }} {{ r.name }} → {{ r.content }}</span>
            <button
              type="button"
              :disabled="deletingId === r.id"
              class="inline-flex h-7 items-center rounded px-2 text-xs text-destructive hover:bg-destructive/10 disabled:opacity-50 transition-colors"
              @click="deleteRecord(r)"
            >
              {{ deletingId === r.id ? 'Removendo...' : 'Remover' }}
            </button>
          </li>
        </ul>
      </div>
    </template>
  </FieldSet>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import {
  FieldSet,
  FieldLegend,
  FieldDescription,
} from '~/components/ui/field'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '~/components/ui/select'

interface FormColumn {
  name: string
  label?: string
  helpText?: string
  hint?: string
  tooltip?: string
  required?: boolean
  apiBaseUrl?: string
}

interface Props {
  column: FormColumn
  modelValue?: { zone_id?: string | null; records?: unknown[] } | null
  error?: string | string[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => null,
  error: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: { zone_id: string | null; records: unknown[] }): void
}>()

const apiBase = computed(() => props.column.apiBaseUrl ?? '/cloudflare')
const configured = ref(true)
const notConfiguredMessage = ref('API Cloudflare não configurada (token ausente).')
const zones = ref<{ id: string; name: string; status: string }[]>([])
const loadingZones = ref(false)
const selectedZoneId = ref<string | null>(null)
const records = ref<{ id: string; type: string; name: string; content: string }[]>([])
const loadingRecords = ref(false)
const creating = ref(false)
const createError = ref('')
const createSuccess = ref('')
const deletingId = ref<string | null>(null)

const dnsTypes = ['A', 'AAAA', 'CNAME', 'TXT', 'MX', 'NS', 'SRV', 'CAA']

const form = ref({
  name: '',
  type: 'A',
  content: '',
  proxied: false,
  ttl: 1,
})

const canCreate = computed(() => {
  return form.value.name.trim() !== '' && form.value.content.trim() !== ''
})

function fetchZones() {
  loadingZones.value = true
  axios.get(`${apiBase.value}/zones`).then((res) => {
    if (res.data?.success && Array.isArray(res.data.zones)) {
      zones.value = res.data.zones
      if (zones.value.length > 0 && !selectedZoneId.value) {
        selectedZoneId.value = zones.value[0].id
      }
    } else if (res.status === 503 || res.data?.message) {
      configured.value = false
      notConfiguredMessage.value = res.data?.message ?? 'Cloudflare não configurado.'
    }
  }).catch((err) => {
    configured.value = false
    notConfiguredMessage.value = err.response?.data?.message ?? 'Erro ao carregar zones.'
  }).finally(() => {
    loadingZones.value = false
  })
}

function fetchRecords() {
  if (!selectedZoneId.value) return
  loadingRecords.value = true
  axios.get(`${apiBase.value}/zones/${selectedZoneId.value}/records`).then((res) => {
    if (res.data?.success && Array.isArray(res.data.records)) {
      records.value = res.data.records
    }
  }).finally(() => {
    loadingRecords.value = false
  })
}

function onZoneChange() {
  records.value = []
  createError.value = ''
  createSuccess.value = ''
  if (selectedZoneId.value) fetchRecords()
  emitValue()
}

function createRecord() {
  if (!selectedZoneId.value || !canCreate.value) return
  creating.value = true
  createError.value = ''
  createSuccess.value = ''
  axios.post(`${apiBase.value}/records`, {
    zone_id: selectedZoneId.value,
    type: form.value.type,
    name: form.value.name.trim(),
    content: form.value.content.trim(),
    ttl: form.value.ttl,
    proxied: form.value.proxied,
  }).then((res) => {
    if (res.data?.success) {
      createSuccess.value = 'Registro criado.'
      form.value.name = ''
      form.value.content = ''
      fetchRecords()
      emitValue()
    } else {
      createError.value = res.data?.errors?.[0]?.message ?? 'Erro ao criar registro.'
    }
  }).catch((err) => {
    createError.value = err.response?.data?.errors?.[0]?.message ?? err.response?.data?.message ?? 'Erro ao criar registro.'
  }).finally(() => {
    creating.value = false
  })
}

function deleteRecord(r: { id: string; type: string; name: string; content: string }) {
  if (!selectedZoneId.value) return
  deletingId.value = r.id
  axios.delete(`${apiBase.value}/zones/${selectedZoneId.value}/records/${r.id}`).then((res) => {
    if (res.data?.success) {
      records.value = records.value.filter((x) => x.id !== r.id)
      emitValue()
    }
  }).finally(() => {
    deletingId.value = null
  })
}

function emitValue() {
  emit('update:modelValue', {
    zone_id: selectedZoneId.value ?? null,
    records: records.value,
  })
}

onMounted(() => {
  fetchZones()
  const mv = props.modelValue
  if (mv?.zone_id) selectedZoneId.value = mv.zone_id
})

watch(selectedZoneId, () => emitValue())
watch(records, () => emitValue(), { deep: true })
</script>
