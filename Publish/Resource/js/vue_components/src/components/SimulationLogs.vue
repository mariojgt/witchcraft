<!-- SimulationLogs.vue -->
<template>
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-full opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-full opacity-0"
    >
      <Panel v-show="isVisible" position="bottom-right" class="w-96 max-w-full m-4">
        <div class="bg-gray-800/95 backdrop-blur text-white rounded-lg shadow-xl">
          <!-- Header -->
          <div class="flex items-center justify-between p-4 border-b border-gray-700">
            <div class="flex items-center space-x-2">
              <ActivityIcon class="w-5 h-5 text-blue-400" />
              <h3 class="font-semibold">Simulation Logs</h3>
              <span
                class="px-2 py-0.5 text-xs rounded-full"
                :class="simulationMode ? 'bg-green-500/20 text-green-300' : 'bg-gray-500/20 text-gray-300'"
              >
                {{ simulationMode ? 'Running' : 'Idle' }}
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="clearLogs"
                class="p-1 hover:bg-gray-700 rounded transition-colors"
                title="Clear logs"
              >
                <TrashIcon class="w-4 h-4 text-gray-400 hover:text-white" />
              </button>
              <button
                @click="toggleLogs"
                class="p-1 hover:bg-gray-700 rounded transition-colors"
                title="Toggle logs"
              >
                <MinimizeIcon class="w-4 h-4 text-gray-400 hover:text-white" />
              </button>
            </div>
          </div>

          <!-- Logs Container -->
          <div class="max-h-96 overflow-y-auto p-4 space-y-2 scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-transparent">
            <TransitionGroup
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition duration-200 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <div
                v-for="(log, index) in logs"
                :key="index"
                class="flex items-start space-x-3 p-3 rounded-lg"
                :class="getLogClass(log.type)"
              >
                <component
                  :is="getLogIcon(log.type)"
                  class="w-5 h-5 mt-0.5"
                  :class="getLogIconClass(log.type)"
                />
                <div class="flex-1 min-w-0">
                  <p class="text-sm break-words">{{ log.message }}</p>
                  <span class="text-xs opacity-60">{{ formatTime(log.timestamp) }}</span>
                </div>
              </div>
            </TransitionGroup>
          </div>

          <!-- Footer with stats -->
          <div class="border-t border-gray-700 p-4">
            <div class="flex justify-between text-xs text-gray-400">
              <span>Total logs: {{ logs.length }}</span>
              <span>{{ getLogStats }}</span>
            </div>
          </div>
        </div>
      </Panel>
    </Transition>
  </template>

  <script setup>
  import { ref, computed } from 'vue'
  import { Panel } from '@vue-flow/core'
  import {
    ActivityIcon,
    AlertCircleIcon,
    CheckCircleIcon,
    InfoIcon,
    MinimizeIcon,
    TrashIcon,
    AlertTriangleIcon
  } from 'lucide-vue-next'

  const props = defineProps({
    logs: {
      type: Array,
      required: true
    },
    simulationMode: {
      type: Boolean,
      default: false
    }
  })

  const emit = defineEmits(['clear'])

  const isVisible = ref(true)

  function toggleLogs() {
    isVisible.value = !isVisible.value
  }

  function clearLogs() {
    emit('clear')
  }

  function getLogClass(type) {
    const classes = {
      error: 'bg-red-500/10 text-red-200',
      success: 'bg-green-500/10 text-green-200',
      warning: 'bg-yellow-500/10 text-yellow-200',
      info: 'bg-blue-500/10 text-blue-200'
    }
    return classes[type] || classes.info
  }

  function getLogIcon(type) {
    const icons = {
      error: AlertCircleIcon,
      success: CheckCircleIcon,
      warning: AlertTriangleIcon,
      info: InfoIcon
    }
    return icons[type] || InfoIcon
  }

  function getLogIconClass(type) {
    const classes = {
      error: 'text-red-400',
      success: 'text-green-400',
      warning: 'text-yellow-400',
      info: 'text-blue-400'
    }
    return classes[type] || classes.info
  }

  function formatTime(date) {
    return new Date(date).toLocaleTimeString()
  }

  const getLogStats = computed(() => {
    const stats = props.logs.reduce((acc, log) => {
      acc[log.type] = (acc[log.type] || 0) + 1
      return acc
    }, {})

    return Object.entries(stats)
      .map(([type, count]) => `${type}: ${count}`)
      .join(' | ')
  })
  </script>

  <style scoped>
  .scrollbar-thin {
    scrollbar-width: thin;
  }

  .scrollbar-thin::-webkit-scrollbar {
    width: 6px;
  }

  .scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
  }

  .scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: rgb(75, 85, 99);
    border-radius: 3px;
  }

  .scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background-color: rgb(107, 114, 128);
  }
  </style>
