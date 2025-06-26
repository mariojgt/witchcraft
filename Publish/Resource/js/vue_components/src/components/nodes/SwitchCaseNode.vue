<template>
    <div class="bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl hover:shadow-purple-500/10 transition-all duration-300 hover:border-purple-500/30">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-purple-500/20 to-purple-600/20 rounded-lg flex items-center justify-center border border-purple-500/20">
                    <GitBranch class="w-5 h-5 text-purple-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">Switch Case</h3>
                    <p class="text-xs text-gray-400 leading-none mt-0.5">Multi-branch conditional logic</p>
                </div>
            </div>
            <button @click="$emit('delete')" class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <!-- Switch Expression Input -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Switch Expression</label>
                <input v-model="data.switchExpression" placeholder="Enter expression to evaluate" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
            </div>

            <!-- Cases Section -->
            <div class="space-y-2">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Cases</label>
                    <button @click="addCase" class="text-purple-400 hover:text-purple-300 text-sm px-2 py-1 rounded hover:bg-purple-500/20 flex items-center gap-1 transition-all duration-200">
                        <PlusCircle class="w-4 h-4" />
                        Add Case
                    </button>
                </div>

                <!-- Cases Container -->
                <div class="space-y-3 max-h-48 overflow-y-auto">
                    <div v-for="(caseItem, index) in data.cases" :key="index" class="relative group">
                        <!-- Case Input Container -->
                        <div class="flex items-center p-3 bg-white/5 border border-white/10 rounded-lg transition-all duration-200 hover:bg-white/10">
                            <!-- Case Label -->
                            <div class="w-16 text-xs text-purple-400 font-medium">
                                {{ index === data.cases.length - 1 ? 'Default' : `Case ${index + 1}` }}
                            </div>

                            <!-- Case Input -->
                            <div class="flex-1 relative">
                                <input v-model="caseItem.value" :placeholder="index === data.cases.length - 1 ? 'default' : 'Enter case value'" :disabled="index === data.cases.length - 1" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none disabled:opacity-50" />

                                <!-- Delete Button -->
                                <button v-if="index !== data.cases.length - 1" @click="removeCase(index)" class="absolute right-2 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity text-red-400 hover:text-red-300 p-1 rounded hover:bg-red-500/20">
                                    <XIcon class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Handle for this case -->
                            <div class="absolute top-1/2 -right-2 -translate-y-1/2">
                                <Handle type="source" position="right" :id="String(index)" :class="index === data.cases.length - 1 ? '!w-4 !h-4 !bg-orange-500 !border-2 !border-gray-800 hover:!bg-orange-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg' : '!w-4 !h-4 !bg-purple-500 !border-2 !border-gray-800 hover:!bg-purple-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg'" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Handle -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-purple-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { Handle } from "@vue-flow/core";
import { XIcon, GitBranch, PlusCircle } from "lucide-vue-next";
import { defineOptions } from "vue";

defineOptions({
    nodeMetadata: {
        category: "Logic",
        icon: GitBranch,
        label: "Switch Case",
        initialData: {
            switchExpression: "",
            cases: [
                { value: "" },
                { value: "default" } // Default case is always present
            ],
        },
    },
});

const props = defineProps(["data"]);
const emit = defineEmits(["delete"]);

const addCase = () => {
    // Add new case before the default case
    props.data.cases.splice(props.data.cases.length - 1, 0, { value: "" });
};

const removeCase = (index) => {
    props.data.cases.splice(index, 1);
};
</script>
