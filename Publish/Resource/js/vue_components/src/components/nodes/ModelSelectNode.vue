<template>
    <div class="bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl hover:shadow-purple-500/10 transition-all duration-300 hover:border-purple-500/30">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-purple-500/20 to-purple-600/20 rounded-lg flex items-center justify-center border border-purple-500/20">
                    <BellIcon class="w-5 h-5 text-purple-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">Model Events</h3>
                    <p class="text-xs text-gray-400 leading-none mt-0.5">Listen to database model events</p>
                </div>
            </div>
            <button @click="$emit('delete')" class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Model</label>
                <select v-model="data.modelName" @change="fetchFields" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none">
                    <option v-for="model in availableModels" :key="model" :value="model">{{ model }}</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Event Type</label>
                <select v-model="data.eventType" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none">
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                    <option value="restored">Restored</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Watch Fields</label>
                <div class="bg-white/5 border border-white/10 rounded-lg p-3 space-y-2 max-h-32 overflow-y-auto">
                    <label v-for="field in availableFields" :key="field" class="flex items-center gap-3 cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" :value="field" v-model="data.watchFields" class="sr-only" />
                            <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-purple-400/50 transition-colors flex items-center justify-center" :class="data.watchFields.includes(field) ? 'bg-purple-500 border-purple-500' : ''">
                                <CheckIcon v-if="data.watchFields.includes(field)" class="w-3 h-3 text-white" />
                            </div>
                        </div>
                        <span class="text-sm text-gray-300 group-hover:text-white transition-colors">{{ field }}</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Output Key</label>
                    <input v-model="data.outputKey" placeholder="Variable output key" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Output Value</label>
                    <input v-model="data.outputValue" :placeholder="getOutputPlaceholder" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.testMode" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-purple-400/50 transition-colors flex items-center justify-center" :class="data.testMode ? 'bg-purple-500 border-purple-500' : ''">
                            <CheckIcon v-if="data.testMode" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Test Mode</span>
                </label>
            </div>
        </div>

        <!-- Connection Point -->
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" id="event" class="!w-4 !h-4 !bg-purple-500 !border-2 !border-gray-800 hover:!bg-purple-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { BellIcon, XIcon, CheckIcon } from 'lucide-vue-next';
import { Handle } from '@vue-flow/core';

const props = defineProps(['data']);
const emit = defineEmits(['delete']);

const availableModels = ref([]);
const availableFields = ref([]);

onMounted(() => {
  fetchModels();
});

const fetchModels = async () => {
  try {
    const response = await fetch('/api/witchcraft/tables');
    availableModels.value = await response.json();
  } catch (error) {
    console.error('Failed to fetch models:', error);
  }
};

const fetchFields = async () => {
  try {
    const response = await fetch(`/api/witchcraft/tables/${props.data.modelName}/columns`);
    availableFields.value = await response.json();
  } catch (error) {
    console.error('Failed to fetch fields:', error);
  }
};

defineOptions({
  nodeMetadata: {
    category: 'Start',
    icon: BellIcon,
    label: 'Model Events',
    initialData: {
      modelName: '',
      eventType: 'updated',
      watchFields: [],
      outputKey: 'modelEvent',
      outputValue: '',
      testMode: false
    },
  },
});
</script>
