<template>
    <div class="bg-gray-900/95 border border-gray-700 rounded-lg p-4 min-w-[280px] relative shadow-lg hover:shadow-xl transition-shadow backdrop-blur">
        <!-- Header -->
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 bg-indigo-500/20 rounded-md flex items-center justify-center">
                    <TerminalIcon class="w-4 h-4 text-indigo-400" />
                </div>
                <h3 class="font-medium text-white text-sm">Artisan Command</h3>
            </div>
            <button @click="$emit('delete')" class="p-1 rounded-md hover:bg-red-500/20 text-gray-400 hover:text-red-400 transition-colors">
                <XIcon class="w-4 h-4" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1">Command</label>
                <input
                    v-model="data.command"
                    placeholder="migrate, queue:work, etc."
                    class="w-full text-sm border-0 bg-gray-800 rounded-md px-2 py-1.5 text-white placeholder-gray-500 focus:bg-gray-700 focus:ring-1 focus:ring-indigo-500 transition-colors"
                />
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1">Arguments</label>
                <textarea
                    v-model="data.arguments"
                    placeholder="--force&#10;--seed"
                    rows="2"
                    class="w-full text-sm border-0 bg-gray-800 rounded-md px-2 py-1.5 text-white placeholder-gray-500 resize-none focus:bg-gray-700 focus:ring-1 focus:ring-indigo-500 transition-colors"
                ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-300 mb-1">Output Key</label>
                    <input
                        v-model="data.outputKey"
                        placeholder="artisanOutput"
                        class="w-full text-sm border-0 bg-gray-800 rounded-md px-2 py-1.5 text-white placeholder-gray-500 focus:bg-gray-700 focus:ring-1 focus:ring-indigo-500 transition-colors"
                    />
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-1.5 cursor-pointer group">
                        <input
                            type="checkbox"
                            v-model="data.saveOutput"
                            class="w-3.5 h-3.5 rounded border-gray-600 bg-gray-800 text-indigo-500 focus:ring-indigo-500 focus:ring-1"
                        />
                        <span class="text-xs text-gray-400 group-hover:text-gray-300">Save output</span>
                    </label>
                </div>
            </div>

            <!-- Command Preview -->
            <div class="text-xs text-gray-400 bg-gray-800 rounded-md p-2 font-mono">
                {{ commandPreview }}
            </div>
        </div>

        <!-- Connection Points -->
        <Handle type="target" position="left" class="!w-3 !h-3 !bg-gray-600 !border-2 !border-gray-900 hover:!bg-indigo-500 transition-colors" />
        <Handle type="source" position="right" class="!w-3 !h-3 !bg-indigo-500 !border-2 !border-gray-900 hover:!bg-indigo-400 transition-colors" />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Handle } from '@vue-flow/core'
import { XIcon, TerminalIcon } from 'lucide-vue-next'
import { defineOptions } from 'vue'

const props = defineProps(['data'])

const commandPreview = computed(() => {
    let cmd = "php artisan " + (props.data.command || '');

    if (props.data.arguments) {
        const args = props.data.arguments.split('\n')
            .filter(arg => arg.trim() !== '')
            .map(arg => arg.trim());

        cmd += args.length > 0 ? " " + args.join(" ") : '';
    }

    return cmd;
});

defineOptions({
    nodeMetadata: {
        category: 'System',
        icon: TerminalIcon,
        label: 'Artisan Command',
        initialData: {
            command: '',
            arguments: '',
            saveOutput: true,
            outputKey: 'artisanOutput'
        },
    },
});

defineEmits(['delete'])
</script>
