<template>
    <div class="bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl hover:shadow-blue-500/10 transition-all duration-300 hover:border-blue-500/30">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-blue-500/20 to-blue-600/20 rounded-lg flex items-center justify-center border border-blue-500/20">
                    <DatabaseIcon class="w-5 h-5 text-blue-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">API Request</h3>
                    <p class="text-xs text-gray-400 leading-none mt-0.5">HTTP client for external APIs</p>
                </div>
            </div>
            <button @click="$emit('delete')" class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Method</label>
                    <select v-model="data.method" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white focus:bg-white/10 focus:border-blue-500/50 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 outline-none">
                        <option v-for="method in ['GET', 'POST', 'PUT', 'DELETE']" :key="method" :value="method">{{ method }}</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" v-model="data.saveResponse" class="sr-only" />
                            <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-blue-400/50 transition-colors flex items-center justify-center" :class="data.saveResponse ? 'bg-blue-500 border-blue-500' : ''">
                                <CheckIcon v-if="data.saveResponse" class="w-3 h-3 text-white" />
                            </div>
                        </div>
                        <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Save response</span>
                    </label>
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">URL</label>
                <input v-model="data.url" placeholder="https://api.example.com/users" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-blue-500/50 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Request Body</label>
                <textarea v-model="data.body" placeholder='{"key": "value"}' rows="2" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 resize-none focus:bg-white/10 focus:border-blue-500/50 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 outline-none"></textarea>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-blue-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" class="!w-4 !h-4 !bg-blue-500 !border-2 !border-gray-800 hover:!bg-blue-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { Handle } from '@vue-flow/core'
import { XIcon, DatabaseIcon, CheckIcon } from 'lucide-vue-next'
import { defineOptions } from 'vue'

defineOptions({
    nodeMetadata: {
        category: 'Data',
        icon: DatabaseIcon,
        label: 'API Request',
        initialData: {
            method: 'GET',
            url: '',
            body: '{}',
            saveResponse: true
        }
    }
})

defineProps(['data'])
defineEmits(['delete'])
</script>
