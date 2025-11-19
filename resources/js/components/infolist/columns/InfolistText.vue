<template>
  <span>{{ column.text }}</span>
</template>
<script lang="ts" setup>
defineProps<{
  column: {
    text: string;
  };
}>();

</script>
