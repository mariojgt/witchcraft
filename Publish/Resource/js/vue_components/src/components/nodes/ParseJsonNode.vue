<template>
    <div class="bg-gray-900/95 backdrop-blur border-[1px] border-green-500/30 rounded-xl p-4 min-w-[300px] relative text-gray-100">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4 pb-2 border-b border-green-500/30">
            <div class="flex items-center gap-2">
                <CodeIcon class="w-5 h-5 text-green-400" />
                <h3 class="font-bold text-green-400">JSON Extract</h3>
            </div>
            <button @click="$emit('delete')" class="hover:bg-red-500/20 p-1 rounded transition-colors">
                <XIcon class="w-4 h-4 text-red-400" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-green-400">Source Variable</label>
                <input
                    v-model="data.sourceVariable"
                    placeholder="Enter source variable name"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-green-500 text-gray-100"
                />
            </div>

            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-green-400">JSON Path</label>
                <input
                    v-model="data.jsonPath"
                    placeholder="e.g. name.first"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-green-500 text-gray-100"
                />
            </div>

            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-green-400">Output Key</label>
                <input
                    v-model="data.outputKey"
                    placeholder="Variable output key"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-green-500 text-gray-100"
                />
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-3 w-3 h-6 bg-gray-800 rounded-l-lg border-l-2 border-y-2 border-green-500/50">
            <Handle type="target" position="left" />
        </div>
        <div class="absolute top-1/2 -right-3 w-3 h-6 bg-gray-800 rounded-r-lg border-r-2 border-y-2 border-green-500/50">
            <Handle type="source" position="right" class="!bg-green-500" />
        </div>
    </div>
</template>

<script setup>
import { Handle } from '@vue-flow/core'
import { XIcon, CodeIcon } from 'lucide-vue-next'
import { defineOptions } from 'vue'

defineOptions({
    nodeMetadata: {
        category: 'Data',
        icon: CodeIcon,
        label: 'JSON Extract',
        initialData: {
            sourceVariable: 'modelEvent',
            jsonPath: 'name',
            outputKey: 'extractedValue'
        }
    }
})

defineProps(['data'])
defineEmits(['delete'])
</script>
