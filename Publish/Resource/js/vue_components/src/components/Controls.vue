<template>
    <Panel position="top-right">
      <div class="bg-gray-900 p-4 rounded-2xl shadow-2xl max-w-md">
        <div class="space-y-4">
          <!-- Search Bar -->
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search nodes..."
              class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
            <SearchIcon class="absolute right-3 top-2.5 w-5 h-5 text-gray-400" />
          </div>

          <!-- Save/Load buttons -->
          <div class="flex gap-3">
            <button
              @click="$emit('save')"
              class="bg-gradient-to-r from-green-500 to-green-700 text-white px-4 py-2 rounded-lg shadow hover:from-green-600 hover:to-green-800 transition-colors flex items-center gap-2 w-full justify-center">
              <SaveIcon class="w-5 h-5" />
              Save
            </button>
            <button
              @click="$emit('load')"
              class="bg-gradient-to-r from-blue-500 to-blue-700 text-white px-4 py-2 rounded-lg shadow hover:from-blue-600 hover:to-blue-800 transition-colors flex items-center gap-2 w-full justify-center">
              <FolderIcon class="w-5 h-5" />
              Load
            </button>
          </div>

          <!-- Simulate and Export/Import buttons -->
          <div class="flex flex-col gap-3">
            <button
              @click="$emit('simulate')"
              class="bg-gradient-to-r from-indigo-500 to-indigo-700 text-white px-4 py-2 rounded-lg shadow hover:from-indigo-600 hover:to-indigo-800 transition-colors flex items-center gap-2 justify-center">
              <PlayIcon class="w-5 h-5" />
              Simulate Flow
            </button>
            <div class="flex gap-3">
              <button
                @click="$emit('export')"
                class="bg-gradient-to-r from-emerald-500 to-emerald-700 text-white px-4 py-2 rounded-lg shadow hover:from-emerald-600 hover:to-emerald-800 transition-colors flex items-center gap-2 justify-center flex-1">
                <DownloadIcon class="w-5 h-5" />
                Export
              </button>
              <button
                @click="$emit('import')"
                class="bg-gradient-to-r from-yellow-500 to-yellow-700 text-white px-4 py-2 rounded-lg shadow hover:from-yellow-600 hover:to-yellow-800 transition-colors flex items-center gap-2 justify-center flex-1">
                <UploadIcon class="w-5 h-5" />
                Import
              </button>
            </div>
          </div>

          <!-- Utility buttons -->
          <div class="flex gap-3">
            <button
              @click="$emit('toggle-logs')"
              class="bg-gradient-to-r from-gray-600 to-gray-800 text-white px-4 py-2 rounded-lg shadow hover:from-gray-500 hover:to-gray-700 transition-colors flex items-center gap-2 justify-center flex-1">
              <ListIcon class="w-5 h-5" />
              Toggle Logs
            </button>
            <button
              @click="$emit('clear')"
              class="bg-gradient-to-r from-red-500 to-red-700 text-white px-4 py-2 rounded-lg shadow hover:from-red-600 hover:to-red-800 transition-colors flex items-center gap-2 justify-center flex-1">
              <TrashIcon class="w-5 h-5" />
              Clear Log
            </button>
          </div>

          <!-- Categories Section -->
          <div v-for="(category, categoryName) in filteredNodes" :key="categoryName" class="border-t border-gray-700 pt-3">
            <div
              @click="toggleCategory(categoryName)"
              class="flex justify-between items-center cursor-pointer py-2 px-3 hover:bg-gray-800 transition-colors rounded-lg">
              <h3 class="text-sm font-medium text-gray-300">{{ categoryName }}</h3>
              <ChevronDownIcon
                :class="`w-4 h-4 transform transition-transform ${expandedCategories[categoryName] ? 'rotate-180' : ''}`" />
            </div>
            <div v-if="expandedCategories[categoryName]" class="grid grid-cols-2 gap-2 p-2">
              <div
                v-for="type in category"
                :key="type.id"
                draggable="true"
                @dragstart="onDragStart($event, type)"
                @dblclick="$emit('add-node', type)"
                class="bg-gray-800 hover:bg-indigo-600 text-white p-3 rounded-lg shadow-md flex flex-col items-center justify-center space-y-1 cursor-move">
                <!-- Dynamic Icon Rendering -->
                <div class="w-6 h-6 flex items-center justify-center">
                  <template v-if="isLucideIcon(type.icon)">
                    <component :is="getLucideIcon(type.icon)" class="w-6 h-6" />
                  </template>
                  <template v-else-if="isSvgString(type.icon)">
                    <div v-html="sanitizeSvg(type.icon)" class="w-6 h-6"></div>
                  </template>
                  <template v-else>
                    <BoxIcon class="w-6 h-6" />
                  </template>
                </div>
                <span class="text-xs">{{ type.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Panel>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { Panel } from '@vue-flow/core'
import * as LucideIcons from 'lucide-vue-next'
import {
  ChevronDownIcon,
  PlayIcon,
  DownloadIcon,
  UploadIcon,
  ListIcon,
  TrashIcon,
  SaveIcon,
  FolderIcon,
  SearchIcon,
  BoxIcon
} from 'lucide-vue-next'

const nodeTypes = reactive([])
const expandedCategories = reactive({})
const searchQuery = ref('')

defineEmits([
  'simulate',
  'export',
  'import',
  'clear',
  'toggle-logs',
  'save',
  'load',
  'add-node'
])

// Icon Helper Functions
function isLucideIcon(icon) {
  if (!icon) return false
  return typeof icon === 'string' && icon.endsWith('Icon') && icon in LucideIcons
}

function isSvgString(icon) {
  if (!icon) return false
  return typeof icon === 'string' && icon.trim().startsWith('<svg')
}

function getLucideIcon(iconName) {
  return LucideIcons[iconName] || LucideIcons.BoxIcon
}

function sanitizeSvg(svg) {
  if (!svg) return ''

  // Remove scripts
  svg = svg.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')

  // Remove event handlers
  svg = svg.replace(/\son\w+="[^"]*"/g, '')

  // Add sizing class
  if (!svg.includes('class="')) {
    svg = svg.replace('<svg', '<svg class="w-6 h-6"')
  }

  // Ensure currentColor is used for strokes
  if (!svg.includes('stroke="currentColor"')) {
    svg = svg.replace(/stroke="[^"]*"/g, 'stroke="currentColor"')
    if (!svg.includes('stroke=')) {
      svg = svg.replace('<svg', '<svg stroke="currentColor"')
    }
  }

  return svg
}

onMounted(async () => {
  try {
    // Load both default nodes and custom nodes
    const defaultNodeFiles = import.meta.glob('./nodes/*.vue')

    // Process nodes and handle different icon types
    const processNodeIcon = (node) => {
      const icon = node.icon

      if (!icon) {
        return 'BoxIcon'
      }

      if (typeof icon === 'string' && icon.endsWith('Icon')) {
        return icon
      }

      if (typeof icon === 'string' && icon.startsWith('<svg')) {
        return icon
      }

      return 'BoxIcon'
    }

    // Process default nodes
    for (const path in defaultNodeFiles) {
      const fileName = path.split('/').pop().replace('Node.vue', '')
      const nodeType = fileName.toLowerCase()
      const module = await defaultNodeFiles[path]()
      nodeTypes.push({
        id: nodeType,
        ...(module.default?.nodeMetadata || module.nodeMetadata || {
          category: 'Other',
          icon: 'BoxIcon',
          label: nodeType.replace(/^\w/, c => c.toUpperCase()),
          initialData: { label: 'New Node' }
        }),
        icon: processNodeIcon(module.default?.nodeMetadata || module.nodeMetadata || {})
      })
    }

    // Set up categories
    const categories = nodeTypes.reduce((cats, node) => {
      cats[node.category] = true
      return cats
    }, {})
    Object.assign(expandedCategories, categories)

  } catch (error) {
    console.error('Failed to load nodes:', error)
  }
})

const filteredNodes = computed(() => {
  const query = searchQuery.value.toLowerCase()
  return nodeTypes.reduce((categories, node) => {
    if (
      !query ||
      node.label.toLowerCase().includes(query) ||
      node.category.toLowerCase().includes(query)
    ) {
      if (!categories[node.category]) {
        categories[node.category] = []
      }
      categories[node.category].push(node)
    }
    return categories
  }, {})
})

function toggleCategory(categoryName) {
  expandedCategories[categoryName] = !expandedCategories[categoryName]
}

function onDragStart(event, type) {
  event.dataTransfer.setData('application/nodeType', JSON.stringify({
    type: type.id,
    initialData: type.initialData
  }))
  event.dataTransfer.effectAllowed = 'copy'
}
</script>

<style>
.vue-flow__node {
  transition: all 0.2s ease;
}

.vue-flow__node:hover {
  z-index: 1000;
}

.simulation-active {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}
</style>
