<template>
    <div class="bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl hover:shadow-yellow-500/10 transition-all duration-300 hover:border-yellow-500/30">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-yellow-500/20 to-yellow-600/20 rounded-lg flex items-center justify-center border border-yellow-500/20">
                    <DownloadIcon class="w-5 h-5 text-yellow-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">Get Variable</h3>
                    <p class="text-xs text-gray-400 leading-none mt-0.5">Retrieve stored data</p>
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
                <input v-model="data.variableName" placeholder="myVariable" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-yellow-500/50 focus:ring-2 focus:ring-yellow-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Output Variable</label>
                <input v-model="data.outputVariable" placeholder="retrievedValue" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-yellow-500/50 focus:ring-2 focus:ring-yellow-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Default Value</label>
                <input v-model="data.defaultValue" placeholder="Fallback value" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-yellow-500/50 focus:ring-2 focus:ring-yellow-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.fromCache" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-yellow-400/50 transition-colors flex items-center justify-center" :class="data.fromCache ? 'bg-yellow-500 border-yellow-500' : ''">
                            <CheckIcon v-if="data.fromCache" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">From cache</span>
                </label>

                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.failIfNotFound" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-yellow-400/50 transition-colors flex items-center justify-center" :class="data.failIfNotFound ? 'bg-yellow-500 border-yellow-500' : ''">
                            <CheckIcon v-if="data.failIfNotFound" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Fail if not found</span>
                </label>
            </div>

            <!-- Current value preview -->
            <div v-if="data.variableName" class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400">
                    <span class="text-gray-300 font-medium">Preview:</span>
                    <span class="text-yellow-400">{{ currentValuePreview }}</span>
                </div>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-yellow-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" class="!w-4 !h-4 !bg-yellow-500 !border-2 !border-gray-800 hover:!bg-yellow-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Handle } from '@vue-flow/core'
import { XIcon, DownloadIcon, CheckIcon } from 'lucide-vue-next'
import { defineOptions } from 'vue'

const props = defineProps(['data', 'variables'])

const currentValuePreview = computed(() => {
    if (!props.data.variableName) return 'No variable selected'

    if (props.variables && props.variables[props.data.variableName]) {
        return String(props.variables[props.data.variableName]).substring(0, 30) + '...'
    }

    return props.data.defaultValue || 'Variable not found'
})

defineOptions({
    nodeMetadata: {
        category: 'Data',
        icon: DownloadIcon,
        label: 'Get Variable',
        initialData: {
            variableName: 'myVariable',
            outputVariable: 'retrievedValue',
            defaultValue: '',
            fromCache: false,
            failIfNotFound: false
        }
    }
})

defineEmits(['delete'])
</script>
