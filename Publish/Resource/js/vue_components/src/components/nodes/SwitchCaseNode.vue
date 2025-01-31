<template>
    <div class="bg-gray-900/95 backdrop-blur border-[1px] border-purple-500/30 rounded-xl p-4 min-w-[300px] relative text-gray-100">
      <!-- Header -->
      <div class="flex justify-between items-center mb-4 pb-2 border-b border-purple-500/30">
        <div class="flex items-center gap-2">
          <GitBranch class="w-5 h-5 text-purple-400" />
          <h3 class="font-bold text-purple-400">Switch Case</h3>
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
        <!-- Switch Expression Input -->
        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wider text-purple-400">Switch Expression</label>
          <input
            v-model="data.switchExpression"
            placeholder="Enter expression to evaluate"
            class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-purple-500 text-gray-100"
          />
        </div>

        <!-- Cases Section -->
        <div class="space-y-2">
          <div class="flex justify-between items-center mb-2">
            <label class="text-xs uppercase tracking-wider text-purple-400">Cases</label>
            <button
              @click="addCase"
              class="text-purple-400 hover:text-purple-300 text-sm px-2 py-1 rounded hover:bg-purple-500/20 flex items-center gap-1"
            >
              <PlusCircle class="w-4 h-4" />
              Add Case
            </button>
          </div>

          <!-- Cases Container -->
          <div class="space-y-3">
            <div
              v-for="(caseItem, index) in data.cases"
              :key="index"
              class="relative group"
            >
              <!-- Case Input Container -->
              <div
                class="flex items-center p-2 bg-gray-800/50 rounded-lg transition-colors"
                :class="{'hover:bg-gray-800/70': index !== data.cases.length - 1}"
              >
                <!-- Case Label -->
                <div class="w-16 text-xs text-purple-400 uppercase">
                  {{ index === data.cases.length - 1 ? 'Default' : `Case ${index + 1}` }}
                </div>

                <!-- Case Input -->
                <div class="flex-1 relative">
                  <input
                    v-model="caseItem.value"
                    :placeholder="index === data.cases.length - 1 ? 'default' : 'Enter case value'"
                    :disabled="index === data.cases.length - 1"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-purple-500 text-gray-100 px-3 py-1"
                  />

                  <!-- Delete Button -->
                  <button
                    v-if="index !== data.cases.length - 1"
                    @click="removeCase(index)"
                    class="absolute right-2 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity text-red-400 hover:text-red-300 p-1 rounded hover:bg-red-500/20"
                  >
                    <XIcon class="w-4 h-4" />
                  </button>
                </div>

                <!-- Handle for this case -->
                <div class="absolute top-1/2 -right-3 -translate-y-1/2">
                  <div class="w-3 h-6 bg-gray-800 rounded-r-lg border-r-2 border-y-2 border-purple-500/50">
                    <Handle
                      type="source"
                      position="right"
                      :id="String(index)"
                      :class="index === data.cases.length - 1 ? '!bg-orange-500' : '!bg-purple-500'"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Input Handle -->
      <div class="absolute top-1/2 -left-3 w-3 h-6 bg-gray-800 rounded-l-lg border-l-2 border-y-2 border-purple-500/50">
        <Handle type="target" position="left" />
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
