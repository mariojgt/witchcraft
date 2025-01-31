<template>
    <div class="bg-gray-900/95 backdrop-blur border-[1px] border-indigo-500/30 rounded-xl p-4 min-w-[300px] relative text-gray-100">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4 pb-2 border-b border-indigo-500/30">
            <div class="flex items-center gap-2">
                <TerminalIcon class="w-5 h-5 text-indigo-400" />
                <h3 class="font-bold text-indigo-400">Artisan Command</h3>
            </div>
            <button
                @click="$emit('delete')"
                class="hover:bg-red-500/20 p-1 rounded transition-colors"
            >
                <XIcon class="w-4 h-4 text-red-400" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-indigo-400">Command</label>
                <input
                    v-model="data.command"
                    placeholder="Enter artisan command (e.g., migrate, queue:work)"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-indigo-500 text-gray-100"
                />
            </div>

            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-indigo-400">Arguments</label>
                <textarea
                    v-model="data.arguments"
                    placeholder="Enter command arguments (one per line)"
                    rows="3"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-indigo-500 text-gray-100 resize-none"
                ></textarea>
            </div>

            <div class="space-y-2">
                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        v-model="data.saveOutput"
                        class="rounded border-0 bg-gray-700 text-indigo-500 focus:ring-indigo-500"
                    />
                    <span class="text-xs uppercase tracking-wider text-indigo-400">
                        Save Command Output
                    </span>
                </label>
            </div>

            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-indigo-400">Output Key</label>
                <input
                    v-model="data.outputKey"
                    placeholder="Variable to store output"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-indigo-500 text-gray-100"
                />
            </div>

            <!-- Command Preview -->
            <div class="space-y-2 bg-gray-800 p-3 rounded-lg">
                <label class="text-xs uppercase tracking-wider text-indigo-400">Command Preview</label>
                <pre class="text-sm text-gray-300 overflow-x-auto">
{{ commandPreview }}
                </pre>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-3 w-3 h-6 bg-gray-800 rounded-l-lg border-l-2 border-y-2 border-indigo-500/50">
            <Handle type="target" position="left" />
        </div>
        <div class="absolute top-1/2 -right-3 w-3 h-6 bg-gray-800 rounded-r-lg border-r-2 border-y-2 border-indigo-500/50">
            <Handle type="source" position="right" class="!bg-indigo-500" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Handle } from "@vue-flow/core";
import { TerminalIcon, XIcon } from "lucide-vue-next";
import { defineOptions } from "vue";

const props = defineProps(["data"]);
const emit = defineEmits(["delete"]);

// Compute command preview
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
</script>
