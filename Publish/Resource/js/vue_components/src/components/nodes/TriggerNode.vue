<template>
    <div class="bg-gray-900/95 backdrop-blur border-[1px] border-blue-500/30 rounded-xl p-4 min-w-[300px] relative text-gray-100">
      <!-- Header -->
      <div class="flex justify-between items-center mb-4 pb-2 border-b border-blue-500/30">
        <div class="flex items-center gap-2">
          <DatabaseIcon class="w-5 h-5 text-blue-400" />
          <h3 class="font-bold text-blue-400">Trigger</h3>
        </div>
        <button @click="$emit('delete')" class="hover:bg-red-500/20 p-1 rounded transition-colors">
          <XIcon class="w-4 h-4 text-red-400" />
        </button>
      </div>

      <!-- Content -->
      <div class="space-y-4">
        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wider text-blue-400">Variable Name</label>
          <input
            v-model="data.variableName"
            placeholder="Enter variable name"
            class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-100"
          />
        </div>

        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wider text-blue-400">Initial Value</label>
          <input
            v-model="data.initialValue"
            placeholder="Enter initial value"
            class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-100"
          />
        </div>

        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wider text-blue-400">Output Key</label>
          <input
            v-model="data.outputKey"
            placeholder="Variable output key"
            class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-100"
          />
        </div>
      </div>

      <!-- Connection Point -->
      <div class="absolute top-1/2 -right-3 w-3 h-6 bg-gray-800 rounded-r-lg border-r-2 border-y-2 border-blue-500/50">
        <Handle type="source" position="right" class="!bg-blue-500" />
      </div>
    </div>
   </template>

<script setup>
import { Handle } from '@vue-flow/core'
import { XIcon, DatabaseIcon } from 'lucide-vue-next'
import { defineOptions } from 'vue'

defineOptions({
    nodeMetadata: {
        category: 'Trigger',
        icon: DatabaseIcon,
        label: 'Trigger',
        initialData: {
            variableName: 'userStatus',
            initialValue: 'active',
            outputKey: 'status'
        }
    }
})

defineProps(['data'])
defineEmits(['delete'])
</script>
