<template>
    <div class="bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl hover:shadow-green-500/10 transition-all duration-300 hover:border-green-500/30">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-green-500/20 to-green-600/20 rounded-lg flex items-center justify-center border border-green-500/20">
                    <VariableIcon class="w-5 h-5 text-green-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">Set Variable</h3>
                    <p class="text-xs text-gray-400 leading-none mt-0.5">Store and manage data</p>
                </div>
            </div>
            <button @click="$emit('delete')" class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Variable Name</label>
                <input v-model="data.variableName" placeholder="myVariable" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
            </div>

            <!-- Use Extracted Value Option -->
            <div class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.useExtractedValue" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-green-400/50 transition-colors flex items-center justify-center" :class="data.useExtractedValue ? 'bg-green-500 border-green-500' : ''">
                            <CheckIcon v-if="data.useExtractedValue" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Use Extracted Value</span>
                </label>
                <div class="text-xs text-green-400/70 mt-1 ml-7">Use value from previous node (extractedValue) instead of manual input</div>
            </div>

            <!-- Variable Value Input (hidden when using extracted value) -->
            <div v-if="!data.useExtractedValue" class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Variable Value</label>
                <textarea v-model="data.variableValue" placeholder="Enter value or use {{otherVariable}} for dynamic values" rows="3" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 resize-none focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none"></textarea>
                <div class="text-xs text-green-400/70">Use {{variableName}} to reference other variables</div>
            </div>

            <!-- Extracted Value Source (shown when using extracted value) -->
            <div v-if="data.useExtractedValue" class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Source Variable</label>
                <input v-model="data.sourceVariable" placeholder="extractedValue" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
                <div class="text-xs text-green-400/70">Variable name to extract value from (defaults to 'extractedValue')</div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Type</label>
                    <select v-model="data.valueType" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none">
                        <option value="string">String</option>
                        <option value="number">Number</option>
                        <option value="boolean">Boolean</option>
                        <option value="json">JSON Object</option>
                        <option value="array">Array</option>
                    </select>
                </div>
                <div v-if="data.persistent" class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Cache (min)</label>
                    <input v-model="data.cacheExpiry" type="number" placeholder="60" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.persistent" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-green-400/50 transition-colors flex items-center justify-center" :class="data.persistent ? 'bg-green-500 border-green-500' : ''">
                            <CheckIcon v-if="data.persistent" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Persistent (Cache)</span>
                </label>
                <div class="text-xs text-green-400/70 mt-1 ml-7">Persistent variables survive workflow restarts</div>
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
import { XIcon, VariableIcon, CheckIcon } from 'lucide-vue-next'
import { defineOptions } from 'vue'

defineOptions({
    nodeMetadata: {
        category: 'Data',
        icon: VariableIcon,
        label: 'Set Variable',
        initialData: {
            variableName: 'myVariable',
            variableValue: 'Hello World',
            valueType: 'string',
            persistent: false,
            cacheExpiry: '',
            useExtractedValue: true, // Default enabled as requested
            sourceVariable: 'extractedValue'
        }
    }
})

defineProps(['data'])
defineEmits(['delete'])
</script>
