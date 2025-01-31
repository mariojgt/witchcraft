<template>
    <div class="bg-gray-900/95 backdrop-blur border-[1px] border-cyan-500/30 rounded-xl p-4 min-w-[300px] relative text-gray-100">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4 pb-2 border-b border-cyan-500/30">
            <div class="flex items-center gap-2">
                <DatabaseIcon class="w-5 h-5 text-cyan-400" />
                <h3 class="font-bold text-cyan-400">API Request</h3>
            </div>
            <button @click="$emit('delete')" class="hover:bg-red-500/20 p-1 rounded transition-colors">
                <XIcon class="w-4 h-4 text-red-400" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-cyan-400">Request Type</label>
                <select
                    v-model="data.requestType"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100"
                >
                    <option value="external">External API</option>
                    <option value="internal">Internal Route</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-cyan-400">Method</label>
                <select
                    v-model="data.method"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100"
                >
                    <option v-for="method in ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']"
                            :key="method"
                            :value="method"
                            class="bg-gray-800"
                    >
                        {{ method }}
                    </option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-cyan-400">
                    {{ data.requestType === 'external' ? 'Endpoint URL' : 'Route Name' }}
                </label>
                <input
                    v-model="data.url"
                    :placeholder="data.requestType === 'external' ? 'https://api.example.com/endpoint' : 'users.show'"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100"
                />
            </div>

            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-cyan-400">Request Parameters</label>
                <textarea
                    v-model="data.params"
                    placeholder="JSON parameters (query params or route parameters)"
                    rows="2"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100 resize-none"
                ></textarea>
            </div>

            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-cyan-400">Request Body</label>
                <textarea
                    v-model="data.body"
                    placeholder="JSON payload"
                    rows="3"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100 resize-none"
                ></textarea>
            </div>

            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-cyan-400">Headers</label>
                <textarea
                    v-model="data.headers"
                    placeholder="JSON headers"
                    rows="2"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100 resize-none"
                ></textarea>
            </div>

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-cyan-400 cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="data.saveResponse"
                        class="rounded border-0 bg-gray-700 text-cyan-500 focus:ring-cyan-500"
                    />
                    <span class="text-sm">Save Response</span>
                </label>

                <label class="flex items-center gap-2 text-cyan-400 cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="data.authenticatedRequest"
                        class="rounded border-0 bg-gray-700 text-cyan-500 focus:ring-cyan-500"
                    />
                    <span class="text-sm">Authenticated Request</span>
                </label>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-3 w-3 h-6 bg-gray-800 rounded-l-lg border-l-2 border-y-2 border-cyan-500/50">
            <Handle type="target" position="left" />
        </div>
        <div class="absolute top-1/2 -right-3 w-3 h-6 bg-gray-800 rounded-r-lg border-r-2 border-y-2 border-cyan-500/50">
            <Handle type="source" position="right" class="!bg-cyan-500" />
        </div>
    </div>
</template>

<script setup>
import { Handle } from '@vue-flow/core'
import { XIcon, DatabaseIcon } from 'lucide-vue-next'
import { defineOptions } from 'vue'

defineOptions({
    nodeMetadata: {
        category: 'Data',
        icon: DatabaseIcon,
        label: 'API Request',
        initialData: {
            requestType: 'external',
            method: 'GET',
            url: '',
            params: '{}',
            body: '{}',
            headers: '{}',
            saveResponse: true,
            authenticatedRequest: false
        }
    }
})

defineProps(['data'])
defineEmits(['delete'])
</script>
