<template>
    <div class="bg-gray-900/95 backdrop-blur border-[1px] border-blue-500/30 rounded-xl p-4 min-w-[300px] relative text-gray-100">
      <!-- Header -->
      <div class="flex justify-between items-center mb-4 pb-2 border-b border-blue-500/30">
        <div class="flex items-center gap-2">
          <ScanEye class="w-5 h-5 text-blue-400" />
          <h3 class="font-bold text-blue-400">Condition</h3>
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
          <label class="text-xs uppercase tracking-wider text-blue-400">Condition Type</label>
          <select
            v-model="data.conditionType"
            class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-100"
          >
            <option value="equals">Equals</option>
            <option value="notEquals">Not Equals</option>
            <option value="contains">Contains</option>
            <option value="changed">Changed</option>
          </select>
        </div>

        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wider text-blue-400">Expected Value</label>
          <input
            v-model="data.expectedValue"
            placeholder="Enter expected value"
            class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-100"
          />
        </div>

        <div v-if="data.conditionType === 'changed'" class="space-y-2">
          <label class="text-xs uppercase tracking-wider text-blue-400">Previous Value</label>
          <input
            v-model="data.previousValue"
            placeholder="Enter previous value"
            class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-100"
          />
        </div>
      </div>

      <!-- Connection Points -->
      <div
        class="absolute top-1/2 -left-3 w-3 h-6 bg-gray-800 rounded-l-lg border-l-2 border-y-2 border-blue-500/50"
      >
        <Handle type="target" position="left" />
      </div>

      <div
        class="absolute top-1/2 -right-3 w-3 h-6 bg-gray-800 rounded-r-lg border-r-2 border-y-2 border-green-500/50"
      >
        <Handle type="source" position="right" id="true" class="!bg-green-500" />
      </div>

      <div
        class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 h-3 w-6 bg-gray-800 rounded-b-lg border-b-2 border-x-2 border-red-500/50"
      >
        <Handle type="source" position="bottom" id="false" class="!bg-red-500" />
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
