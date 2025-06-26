<template>
    <div class="bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl hover:shadow-green-500/10 transition-all duration-300 hover:border-green-500/30">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-green-500/20 to-green-600/20 rounded-lg flex items-center justify-center border border-green-500/20">
                    <CodeIcon class="w-5 h-5 text-green-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">JSON Extract</h3>
                    <p class="text-xs text-gray-400 leading-none mt-0.5">Parse and extract JSON data</p>
                </div>
            </div>
            <button @click="$emit('delete')" class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Source Variable</label>
                <input v-model="data.sourceVariable" placeholder="modelEvent" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">JSON Path</label>
                <input v-model="data.jsonPath" placeholder="user.name or data[0].value" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Output Key</label>
                <input v-model="data.outputKey" placeholder="extractedValue" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
            </div>

            <!-- Path helper -->
            <div class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400">
                    <span class="font-medium text-gray-300">Examples:</span>
                    <span class="text-green-400">user.name, data[0], items.length</span>
                </div>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-green-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" class="!w-4 !h-4 !bg-green-500 !border-2 !border-gray-800 hover:!bg-green-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
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
