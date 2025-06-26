<template>
    <div class="bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl hover:shadow-red-500/10 transition-all duration-300 hover:border-red-500/30">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-red-500/20 to-red-600/20 rounded-lg flex items-center justify-center border border-red-500/20">
                    <ZapIcon class="w-5 h-5 text-red-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">Trigger Webhook</h3>
                    <p class="text-xs text-gray-400 leading-none mt-0.5">Send HTTP requests to external services</p>
                </div>
            </div>
            <button @click="$emit('delete')" class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Webhook URL</label>
                <input v-model="data.webhookUrl" placeholder="https://example.com/webhook" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-red-500/50 focus:ring-2 focus:ring-red-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">HTTP Method</label>
                <select v-model="data.method" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white focus:bg-white/10 focus:border-red-500/50 focus:ring-2 focus:ring-red-500/20 transition-all duration-200 outline-none">
                    <option value="POST">POST</option>
                    <option value="GET">GET</option>
                    <option value="PUT">PUT</option>
                    <option value="PATCH">PATCH</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Payload</label>
                <textarea v-model="data.payload" placeholder='{"message": "{{variableName}}", "status": "triggered"}' rows="3" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 resize-none focus:bg-white/10 focus:border-red-500/50 focus:ring-2 focus:ring-red-500/20 transition-all duration-200 outline-none"></textarea>
                <div class="text-xs text-red-400/70">Use {{variableName}} to include variables</div>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Headers</label>
                <textarea v-model="data.headers" placeholder='{"Content-Type": "application/json", "Authorization": "Bearer token"}' rows="2" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 resize-none focus:bg-white/10 focus:border-red-500/50 focus:ring-2 focus:ring-red-500/20 transition-all duration-200 outline-none"></textarea>
            </div>

            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.waitForResponse" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-red-400/50 transition-colors flex items-center justify-center" :class="data.waitForResponse ? 'bg-red-500 border-red-500' : ''">
                            <CheckIcon v-if="data.waitForResponse" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Wait for Response</span>
                </label>

                <div v-if="data.waitForResponse" class="ml-7 space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Response Variable</label>
                    <input v-model="data.responseVariable" placeholder="webhookResponse" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-red-500/50 focus:ring-2 focus:ring-red-500/20 transition-all duration-200 outline-none" />
                </div>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-red-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" class="!w-4 !h-4 !bg-red-500 !border-2 !border-gray-800 hover:!bg-red-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { Handle } from '@vue-flow/core'
import { XIcon, ZapIcon, CheckIcon } from 'lucide-vue-next'
import { defineOptions } from 'vue'

defineOptions({
    nodeMetadata: {
        category: 'Actions',
        icon: ZapIcon,
        label: 'Trigger Webhook',
        initialData: {
            webhookUrl: '',
            method: 'POST',
            payload: '{"message": "Workflow triggered", "timestamp": "{{timestamp}}"}',
            headers: '{"Content-Type": "application/json"}',
            waitForResponse: false,
            responseVariable: 'webhookResponse'
        }
    }
})

defineProps(['data'])
defineEmits(['delete'])
</script>
