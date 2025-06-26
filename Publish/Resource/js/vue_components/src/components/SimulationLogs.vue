<!-- SimulationLogs.vue -->
<template>
  <Transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="translate-y-full opacity-0 scale-95"
    enter-to-class="translate-y-0 opacity-100 scale-100"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100 scale-100"
    leave-to-class="translate-y-full opacity-0 scale-95"
  >
    <Panel v-show="isVisible" position="bottom-right" class="w-80 max-w-[90vw] m-6">
      <div class="bg-gray-950/90 backdrop-blur-xl border border-gray-800/50 rounded-xl shadow-2xl overflow-hidden">
        <!-- Minimal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800/50">
          <div class="flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" v-if="simulationMode"></div>
            <div class="w-2 h-2 rounded-full bg-gray-600" v-else></div>
            <h3 class="text-sm font-medium text-gray-100">Simulation</h3>
          </div>

          <div class="flex items-center gap-1">
            <button
              @click="clearLogs"
              class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-800/60 transition-colors group"
              title="Clear logs"
            >
              <TrashIcon class="w-4 h-4 text-gray-500 group-hover:text-gray-300" />
            </button>
            <button
              @click="toggleLogs"
              class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-800/60 transition-colors group"
              title="Minimize"
            >
              <MinimizeIcon class="w-4 h-4 text-gray-500 group-hover:text-gray-300" />
            </button>
          </div>
        </div>

        <!-- Clean Logs Container -->
        <div class="max-h-80 overflow-y-auto">
          <div class="p-1">
            <TransitionGroup
              enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 translate-x-4"
              enter-to-class="opacity-100 translate-x-0"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0 scale-95"
            >
              <div
                v-for="(log, index) in logs"
                :key="index"
                class="mx-2 mb-1 rounded-lg transition-all duration-150"
                :class="getLogClass(log.type)"
              >
                <div class="flex items-start gap-3 px-4 py-3">
                  <div class="flex-shrink-0 mt-0.5">
                    <div
                      class="w-1.5 h-1.5 rounded-full"
                      :class="getLogDotClass(log.type)"
                    ></div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm leading-relaxed" :class="getLogTextClass(log.type)">
                      {{ log.message }}
                    </p>
                    <span class="text-xs text-gray-500 mt-1 block">
                      {{ formatTime(log.timestamp) }}
                    </span>
                  </div>
                </div>
              </div>
            </TransitionGroup>

            <!-- Empty State -->
            <div v-if="logs.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
              <div class="w-12 h-12 rounded-full bg-gray-800/50 flex items-center justify-center mb-3">
                <ActivityIcon class="w-6 h-6 text-gray-600" />
              </div>
              <p class="text-sm text-gray-500">No simulation logs yet</p>
            </div>
          </div>
        </div>

        <!-- Minimal Footer -->
        <div class="border-t border-gray-800/50 px-6 py-3 bg-gray-900/30">
          <div class="flex items-center justify-between text-xs">
            <span class="text-gray-500">{{ logs.length }} logs</span>
            <div class="flex items-center gap-4" v-if="logs.length > 0">
              <span v-for="(count, type) in logStats" :key="type" class="flex items-center gap-1.5">
                <div class="w-1.5 h-1.5 rounded-full" :class="getLogDotClass(type)"></div>
                <span class="text-gray-500">{{ count }}</span>
              </span>
            </div>
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
  MinimizeIcon,
  TrashIcon
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
    error: 'hover:bg-red-500/5',
    success: 'hover:bg-emerald-500/5',
    warning: 'hover:bg-amber-500/5',
    info: 'hover:bg-blue-500/5'
  }
  return classes[type] || classes.info
}

function getLogDotClass(type) {
  const classes = {
    error: 'bg-red-400',
    success: 'bg-emerald-400',
    warning: 'bg-amber-400',
    info: 'bg-blue-400'
  }
  return classes[type] || classes.info
}

function getLogTextClass(type) {
  const classes = {
    error: 'text-red-200',
    success: 'text-emerald-200',
    warning: 'text-amber-200',
    info: 'text-blue-200'
  }
  return classes[type] || 'text-gray-300'
}

function formatTime(date) {
  return new Date(date).toLocaleTimeString('en-US', {
    hour12: false,
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const logStats = computed(() => {
  return props.logs.reduce((acc, log) => {
    acc[log.type] = (acc[log.type] || 0) + 1
    return acc
  }, {})
})
</script>

<style scoped>
/* Custom scrollbar for webkit browsers */
::-webkit-scrollbar {
  width: 4px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: rgba(75, 85, 99, 0.5);
  border-radius: 2px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(75, 85, 99, 0.7);
}

/* Firefox scrollbar */
* {
  scrollbar-width: thin;
  scrollbar-color: rgba(75, 85, 99, 0.5) transparent;
}
</style>
