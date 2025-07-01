<template>
  <Transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="translate-y-full opacity-0 scale-95"
    enter-to-class="translate-y-0 opacity-100 scale-100"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100 scale-100"
    leave-to-class="translate-y-full opacity-0 scale-95"
  >
    <Panel v-show="isVisible" position="bottom-left" class="w-96 max-w-[90vw] m-6">
      <div class="bg-gray-950/90 backdrop-blur-xl border border-gray-800/50 rounded-xl shadow-2xl overflow-hidden">
        <!-- Enhanced Header with Tabs -->
        <div class="border-b border-gray-800/50">
          <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" v-if="simulationMode"></div>
              <div class="w-2 h-2 rounded-full bg-gray-600" v-else></div>
              <h3 class="text-sm font-medium text-gray-100">Simulation Monitor</h3>
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

          <!-- Tab Navigation -->
          <div class="flex border-b border-gray-800/30">
            <button
              @click="activeTab = 'logs'"
              :class="`px-4 py-2 text-sm font-medium transition-colors relative ${
                activeTab === 'logs'
                  ? 'text-blue-400 bg-blue-500/10'
                  : 'text-gray-400 hover:text-gray-300 hover:bg-gray-800/30'
              }`"
            >
              <TerminalIcon class="w-4 h-4 inline mr-2" />
              Logs
              <span v-if="logs.length > 0" class="ml-2 text-xs bg-gray-700 text-gray-300 px-1.5 py-0.5 rounded-full">
                {{ logs.length }}
              </span>
              <div v-if="activeTab === 'logs'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-400"></div>
            </button>

            <button
              @click="activeTab = 'variables'"
              :class="`px-4 py-2 text-sm font-medium transition-colors relative ${
                activeTab === 'variables'
                  ? 'text-purple-400 bg-purple-500/10'
                  : 'text-gray-400 hover:text-gray-300 hover:bg-gray-800/30'
              }`"
            >
              <DatabaseIcon class="w-4 h-4 inline mr-2" />
              Variables
              <span v-if="variableCount > 0" class="ml-2 text-xs bg-gray-700 text-gray-300 px-1.5 py-0.5 rounded-full">
                {{ variableCount }}
              </span>
              <div v-if="activeTab === 'variables'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-purple-400"></div>
            </button>
          </div>
        </div>

        <!-- Tab Content -->
        <div class="max-h-80 overflow-y-auto">

          <!-- Logs Tab -->
          <div v-if="activeTab === 'logs'" class="p-1">
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

            <!-- Empty State for Logs -->
            <div v-if="logs.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
              <div class="w-12 h-12 rounded-full bg-gray-800/50 flex items-center justify-center mb-3">
                <ActivityIcon class="w-6 h-6 text-gray-600" />
              </div>
              <p class="text-sm text-gray-500">No simulation logs yet</p>
              <p class="text-xs text-gray-600 mt-1">Logs will appear when simulation runs</p>
            </div>
          </div>

          <!-- Variables Tab -->
          <div v-if="activeTab === 'variables'" class="p-4">
            <div class="space-y-3">
              <!-- Variable Search -->
              <div class="relative" v-if="variableCount > 3">
                <SearchIcon class="absolute left-3 top-2.5 w-4 h-4 text-gray-500" />
                <input
                  v-model="variableSearch"
                  placeholder="Search variables (e.g., product_select.uuid)..."
                  class="w-full bg-gray-800/50 border border-gray-700/50 rounded-lg pl-10 pr-4 py-2 text-sm text-gray-300 placeholder-gray-500 focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/20 focus:outline-none transition-all"
                />
              </div>

              <!-- Variables List -->
              <div class="space-y-2 max-h-64 overflow-y-auto">
                <TransitionGroup
                  enter-active-class="transition-all duration-200 ease-out"
                  enter-from-class="opacity-0 translate-y-2"
                  enter-to-class="opacity-100 translate-y-0"
                >
                  <div
                    v-for="variable in filteredVariablesList"
                    :key="variable.path"
                    class="group bg-gray-800/30 hover:bg-gray-800/50 border border-gray-700/30 rounded-lg p-3 transition-all duration-200 cursor-pointer"
                    @click="toggleVariableExpansion(variable.path)"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-2 flex-1 min-w-0">
                        <ChevronRightIcon
                          v-if="variable.hasChildren"
                          :class="`w-4 h-4 text-gray-500 transition-transform duration-200 ${
                            expandedVariables.has(variable.path) ? 'rotate-90' : ''
                          }`"
                        />
                        <div v-else class="w-4 h-4"></div>

                        <!-- Path with dot notation highlighting -->
                        <code class="text-purple-300 text-sm font-medium truncate">
                          <span v-for="(part, index) in variable.pathParts" :key="index">
                            <span v-if="index > 0" class="text-gray-500">.</span>
                            <span :class="index === variable.pathParts.length - 1 ? 'text-purple-300' : 'text-purple-400/70'">
                              {{ part }}
                            </span>
                          </span>
                        </code>

                        <!-- Nesting indicator -->
                        <div v-if="variable.depth > 0" class="flex items-center">
                          <span class="text-xs text-gray-500 bg-gray-700/50 px-1.5 py-0.5 rounded-full ml-2">
                            L{{ variable.depth }}
                          </span>
                        </div>
                      </div>

                      <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 bg-gray-700/50 px-2 py-1 rounded-full">
                          {{ getValueType(variable.value) }}
                        </span>
                        <button
                          @click.stop="copyVariablePath(variable.path)"
                          class="opacity-0 group-hover:opacity-100 w-6 h-6 flex items-center justify-center rounded hover:bg-gray-700/50 transition-all"
                          :title="`Copy path: ${variable.path}`"
                        >
                          <CopyIcon class="w-3 h-3 text-gray-400 hover:text-gray-300" />
                        </button>
                      </div>
                    </div>

                    <!-- Variable Value Preview -->
                    <div class="mt-2">
                      <div class="text-xs text-gray-400 truncate">
                        {{ formatValuePreview(variable.value) }}
                      </div>
                    </div>

                    <!-- Expanded Variable Details -->
                    <Transition
                      enter-active-class="transition-all duration-200 ease-out"
                      enter-from-class="opacity-0 max-h-0"
                      enter-to-class="opacity-100 max-h-96"
                      leave-active-class="transition-all duration-150 ease-in"
                      leave-from-class="opacity-100 max-h-96"
                      leave-to-class="opacity-0 max-h-0"
                    >
                      <div v-if="expandedVariables.has(variable.path)" class="mt-3 border-t border-gray-700/30 pt-3">
                        <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700/30">
                          <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                              <span class="text-xs font-medium text-gray-300">Raw Value:</span>
                              <span class="text-xs text-purple-400 bg-purple-500/20 px-2 py-0.5 rounded-full">
                                {{ variable.path }}
                              </span>
                            </div>
                            <div class="flex items-center gap-2">
                              <button
                                @click="copyVariableValue(variable.value)"
                                class="text-xs text-purple-400 hover:text-purple-300 flex items-center gap-1 transition-colors"
                              >
                                <CopyIcon class="w-3 h-3" />
                                Value
                              </button>
                              <button
                                @click="copyVariablePath(variable.path)"
                                class="text-xs text-blue-400 hover:text-blue-300 flex items-center gap-1 transition-colors"
                              >
                                <CopyIcon class="w-3 h-3" />
                                Path
                              </button>
                            </div>
                          </div>
                          <pre class="text-xs text-gray-300 whitespace-pre-wrap break-all max-h-32 overflow-y-auto bg-gray-950/50 rounded p-2 border border-gray-800/50">{{ formatVariableValue(variable.value) }}</pre>
                        </div>

                        <!-- Show children if object/array -->
                        <div v-if="variable.hasChildren && variable.children.length > 0" class="mt-3">
                          <div class="text-xs text-gray-400 mb-2 flex items-center gap-2">
                            <FolderIcon class="w-3 h-3" />
                            Properties ({{ variable.children.length }}):
                          </div>
                          <div class="space-y-1 pl-4 border-l-2 border-gray-700/30">
                            <div
                              v-for="child in variable.children.slice(0, 5)"
                              :key="child.key"
                              @click.stop="selectChildVariable(variable.path, child.key)"
                              class="flex items-center justify-between p-2 bg-gray-800/30 rounded cursor-pointer hover:bg-gray-800/50 transition-colors"
                            >
                              <div class="flex items-center gap-2">
                                <code class="text-sm text-gray-300">{{ child.key }}</code>
                                <span class="text-xs text-gray-500">{{ getValueType(child.value) }}</span>
                              </div>
                              <div class="text-xs text-gray-400 truncate max-w-32">
                                {{ formatValuePreview(child.value) }}
                              </div>
                            </div>
                            <div v-if="variable.children.length > 5" class="text-xs text-gray-500 text-center py-1">
                              ... and {{ variable.children.length - 5 }} more properties
                            </div>
                          </div>
                        </div>
                      </div>
                    </Transition>
                  </div>
                </TransitionGroup>
              </div>

              <!-- Path Builder -->
              <div v-if="variableCount > 0" class="bg-gray-800/20 border border-gray-700/30 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-2 flex items-center gap-2">
                  <CodeIcon class="w-3 h-3" />
                  Path Builder:
                </div>
                <div class="flex items-center gap-2">
                  <input
                    v-model="pathBuilder"
                    placeholder="Type path (e.g., product_select.uuid.name)"
                    class="flex-1 bg-gray-900/50 border border-gray-700/50 rounded px-3 py-2 text-sm text-gray-300 placeholder-gray-500 focus:border-purple-500/50 focus:outline-none"
                    @keyup.enter="navigateToPath"
                  />
                  <button
                    @click="navigateToPath"
                    :disabled="!pathBuilder.trim()"
                    class="px-3 py-2 bg-purple-500/20 text-purple-400 hover:bg-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed rounded transition-colors text-sm"
                  >
                    Go
                  </button>
                </div>
                <div v-if="pathResult" class="mt-2 p-2 bg-gray-900/50 rounded border border-gray-700/50">
                  <div class="text-xs text-green-400 mb-1">Found:</div>
                  <pre class="text-xs text-gray-300">{{ formatVariableValue(pathResult) }}</pre>
                </div>
                <div v-if="pathError" class="mt-2 p-2 bg-red-900/20 border border-red-700/30 rounded">
                  <div class="text-xs text-red-400">{{ pathError }}</div>
                </div>
              </div>

              <!-- Empty State for Variables -->
              <div v-if="variableCount === 0" class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-12 h-12 rounded-full bg-gray-800/50 flex items-center justify-center mb-3">
                  <DatabaseIcon class="w-6 h-6 text-gray-600" />
                </div>
                <p class="text-sm text-gray-500">No variables available</p>
                <p class="text-xs text-gray-600 mt-1">Variables will appear during simulation</p>
              </div>

              <!-- No Search Results -->
              <div v-if="variableCount > 0 && filteredVariablesList.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                <SearchIcon class="w-8 h-8 text-gray-600 mb-2" />
                <p class="text-sm text-gray-500">No variables match your search</p>
                <button
                  @click="variableSearch = ''"
                  class="text-xs text-purple-400 hover:text-purple-300 mt-2 transition-colors"
                >
                  Clear search
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Footer with Stats -->
        <div class="border-t border-gray-800/50 px-6 py-3 bg-gray-900/30">
          <div v-if="activeTab === 'logs'" class="flex items-center justify-between text-xs">
            <span class="text-gray-500">{{ logs.length }} logs</span>
            <div class="flex items-center gap-4" v-if="logs.length > 0">
              <span v-for="(count, type) in logStats" :key="type" class="flex items-center gap-1.5">
                <div class="w-1.5 h-1.5 rounded-full" :class="getLogDotClass(type)"></div>
                <span class="text-gray-500">{{ count }}</span>
              </span>
            </div>
          </div>

          <div v-if="activeTab === 'variables'" class="flex items-center justify-between text-xs">
            <span class="text-gray-500">{{ variableCount }} variables</span>
            <div class="flex items-center gap-2" v-if="simulationMode">
              <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></div>
              <span class="text-emerald-400">Live updating</span>
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
  TrashIcon,
  TerminalIcon,
  DatabaseIcon,
  SearchIcon,
  ChevronRightIcon,
  CopyIcon,
  FolderIcon,
  CodeIcon
} from 'lucide-vue-next'

const props = defineProps({
  logs: {
    type: Array,
    required: true
  },
  simulationMode: {
    type: Boolean,
    default: false
  },
  variables: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['clear'])

// UI State
const isVisible = ref(true)
const activeTab = ref('logs')
const variableSearch = ref('')
const expandedVariables = ref(new Set())
const pathBuilder = ref('')
const pathResult = ref(null)
const pathError = ref('')

// Computed Properties
const variableCount = computed(() => Object.keys(props.variables).length)

// Enhanced: Flatten variables with dot notation
const flattenedVariables = computed(() => {
  const result = []

  function flatten(obj, prefix = '') {
    Object.entries(obj).forEach(([key, value]) => {
      const path = prefix ? `${prefix}.${key}` : key
      const pathParts = path.split('.')
      const depth = pathParts.length - 1

      // Always add the current path
      result.push({
        path,
        pathParts,
        depth,
        value,
        hasChildren: value && typeof value === 'object' && !Array.isArray(value),
        children: value && typeof value === 'object' && !Array.isArray(value)
          ? Object.entries(value).map(([k, v]) => ({ key: k, value: v }))
          : []
      })

      // Recursively flatten nested objects
      if (value && typeof value === 'object' && !Array.isArray(value)) {
        flatten(value, path)
      }
    })
  }

  flatten(props.variables)
  return result.sort((a, b) => {
    // Sort by depth first, then alphabetically
    if (a.depth !== b.depth) return a.depth - b.depth
    return a.path.localeCompare(b.path)
  })
})

const filteredVariablesList = computed(() => {
  if (!variableSearch.value) return flattenedVariables.value

  const search = variableSearch.value.toLowerCase()
  return flattenedVariables.value.filter(variable => {
    return variable.path.toLowerCase().includes(search) ||
           String(variable.value).toLowerCase().includes(search)
  })
})

const filteredVariables = computed(() => {
  if (!variableSearch.value) return props.variables

  const search = variableSearch.value.toLowerCase()
  return Object.fromEntries(
    Object.entries(props.variables).filter(([key, value]) => {
      return key.toLowerCase().includes(search) ||
             String(value).toLowerCase().includes(search)
    })
  )
})

const logStats = computed(() => {
  return props.logs.reduce((acc, log) => {
    acc[log.type] = (acc[log.type] || 0) + 1
    return acc
  }, {})
})

// Functions
function toggleLogs() {
  isVisible.value = !isVisible.value
}

function clearLogs() {
  emit('clear')
}

function toggleVariableExpansion(key) {
  if (expandedVariables.value.has(key)) {
    expandedVariables.value.delete(key)
  } else {
    expandedVariables.value.add(key)
  }
  // Trigger reactivity
  expandedVariables.value = new Set(expandedVariables.value)
}

function copyVariable(key, value) {
  const textToCopy = JSON.stringify(value, null, 2)
  navigator.clipboard.writeText(textToCopy).then(() => {
    console.log(`Copied variable "${key}" to clipboard`)
  }).catch(err => {
    console.error('Failed to copy variable:', err)
  })
}

// Enhanced: Copy variable path for dot notation
function copyVariablePath(path) {
  navigator.clipboard.writeText(path).then(() => {
    console.log(`Copied path "${path}" to clipboard`)
  }).catch(err => {
    console.error('Failed to copy path:', err)
  })
}

// Enhanced: Copy variable value
function copyVariableValue(value) {
  const textToCopy = JSON.stringify(value, null, 2)
  navigator.clipboard.writeText(textToCopy).then(() => {
    console.log('Copied variable value to clipboard')
  }).catch(err => {
    console.error('Failed to copy value:', err)
  })
}

// Enhanced: Navigate to specific path
function navigateToPath() {
  if (!pathBuilder.value.trim()) return

  pathError.value = ''
  pathResult.value = null

  try {
    const path = pathBuilder.value.trim()
    const result = getValueAtPath(props.variables, path)

    if (result.found) {
      pathResult.value = result.value
      // Auto-expand the found variable
      expandedVariables.value.add(path)
      expandedVariables.value = new Set(expandedVariables.value)
    } else {
      pathError.value = `Path "${path}" not found`
    }
  } catch (error) {
    pathError.value = `Invalid path: ${error.message}`
  }
}

// Enhanced: Get value at dot notation path
function getValueAtPath(obj, path) {
  const parts = path.split('.')
  let current = obj
  let currentPath = ''

  for (let i = 0; i < parts.length; i++) {
    const part = parts[i]
    currentPath = currentPath ? `${currentPath}.${part}` : part

    if (current && typeof current === 'object' && part in current) {
      current = current[part]
    } else {
      return { found: false, value: null, path: currentPath }
    }
  }

  return { found: true, value: current, path }
}

// Enhanced: Select child variable from expanded view
function selectChildVariable(parentPath, childKey) {
  const newPath = `${parentPath}.${childKey}`
  pathBuilder.value = newPath
  navigateToPath()
}

function getValueType(value) {
  if (value === null) return 'null'
  if (value === undefined) return 'undefined'
  if (Array.isArray(value)) return `array[${value.length}]`
  if (typeof value === 'object') return 'object'
  if (typeof value === 'string') return 'string'
  if (typeof value === 'number') return 'number'
  if (typeof value === 'boolean') return 'boolean'
  return typeof value
}

function formatValuePreview(value) {
  if (value === null) return 'null'
  if (value === undefined) return 'undefined'
  if (typeof value === 'string') {
    return value.length > 50 ? `"${value.substring(0, 50)}..."` : `"${value}"`
  }
  if (typeof value === 'object') {
    if (Array.isArray(value)) {
      return `[${value.length} items]`
    }
    return `{${Object.keys(value).length} keys}`
  }
  return String(value)
}

function formatVariableValue(value) {
  try {
    return JSON.stringify(value, null, 2)
  } catch (error) {
    return String(value)
  }
}

function formatTime(date) {
  return new Date(date).toLocaleTimeString('en-US', {
    hour12: false,
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

// Log styling functions (unchanged)
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

/* Enhanced transitions */
.max-h-0 {
  max-height: 0;
}

.max-h-96 {
  max-height: 24rem;
}
</style>
