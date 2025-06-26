<template>
    <div class="bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl hover:shadow-purple-500/10 transition-all duration-300 hover:border-purple-500/30">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-purple-500/20 to-purple-600/20 rounded-lg flex items-center justify-center border border-purple-500/20">
                    <ScanEye class="w-5 h-5 text-purple-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">Condition</h3>
                    <p class="text-xs text-gray-400 leading-none mt-0.5">Logic branching and decisions</p>
                </div>
            </div>
            <button @click="$emit('delete')" class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Condition Type</label>
                <select v-model="data.conditionType" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none">
                    <option value="equals">Equals</option>
                    <option value="notEquals">Not Equals</option>
                    <option value="contains">Contains</option>
                    <option value="changed">Changed</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Expected Value</label>
                <input v-model="data.expectedValue" placeholder="active" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
            </div>

            <!-- Output indicators -->
            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full shadow-lg"></div>
                    <span class="text-xs text-gray-400">True path</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full shadow-lg"></div>
                    <span class="text-xs text-gray-400">False path</span>
                </div>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-purple-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" id="true" class="!w-4 !h-4 !bg-green-500 !border-2 !border-gray-800 hover:!bg-green-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute bottom-0 left-1/2 transform translate-y-2 -translate-x-1/2">
            <Handle type="source" position="bottom" id="false" class="!w-4 !h-4 !bg-red-500 !border-2 !border-gray-800 hover:!bg-red-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { Handle } from "@vue-flow/core";
import { XIcon, ScanEye } from "lucide-vue-next";
import { defineOptions } from "vue";

defineOptions({
    nodeMetadata: {
        category: "Logic",
        icon: ScanEye,
        label: "Condition",
        initialData: {
            conditionType: "equals",
            expectedValue: "",
            previousValue: null,
        },
    },
});

defineProps(["data"]);
defineEmits(["delete"]);
</script>
