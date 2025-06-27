<template>
  <div
    class="comment-block"
    :class="data.colorClass"
    :style="{
      width: data.width + 'px',
      height: data.height + 'px',
      left: data.x + 'px',
      top: data.y + 'px',
      zIndex: data.zIndex ?? 0,
    }"
  >
    <div class="comment-drag-handle">
      <component :is="getIconComponent(data.iconClass)" :size="16" class="title-icon" />

      <input
        v-model="data.customTitle"
        class="comment-title-input nodrag nopan"
        placeholder="Comment title"
      />
      <div class="settings-wrapper nodrag nopan">
        <button class="settings-button" @click="toggleSettings">
          <SettingsIcon :size="16" class="settings-icon" />
        </button>

        <div v-if="showSettings" class="settings-palette nodrag nopan">
          <div class="palette-section">
            <h4 class="section-title">Color</h4>
            <div class="color-options">
              <div
                v-for="color in availableColors"
                :key="color.class"
                :class="['color-swatch', color.class]"
                @click="selectColor(color.class)"
                :title="color.name"
              ></div>
            </div>
          </div>

          <div class="palette-section">
            <h4 class="section-title">Icon</h4>
            <div class="icon-options">
              <button
                v-for="icon in availableIcons"
                :key="icon.class"
                :class="['icon-option', { 'is-active': data.iconClass === icon.class }]"
                @click="selectIcon(icon.class)"
                :title="icon.name"
              >
                <component :is="getIconComponent(icon.class)" :size="20" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-for="handle in resizeHandles"
      :key="handle.class"
      :class="['resize-handle', handle.class, 'nodrag', 'nopan']"
      @mousedown="startResize($event, handle.direction)"
    ></div>

    <div class="comment-textarea-wrapper">
      <textarea
        v-model="data.comment"
        class="comment-textarea nodrag nopan"
        placeholder="Write comment..."
      ></textarea>
    </div>
  </div>
</template>

<script setup>
import { defineOptions, ref } from 'vue'
// Import a wide range of Lucide Icons for more choices
import {
  SettingsIcon,
  MessageSquareIcon,
  XIcon,
  CalendarDays,
  AlarmClockOff,
  BellRing,
  Book,
  Briefcase,
  Bug,
  Camera,
  Cloud,
  Code,
  Coffee,
  Database,
  Diamond,
  Droplet,
  Feather,
  Flower2,
  Gift,
  Globe,
  Headphones,
  Heart,
  Lightbulb,
  MapPin,
  Music,
  Palette,
  PieChart,
  Plug,
  Rocket,
  ShieldQuestion,
  Star,
  Sunrise,
  Target,
  Tractor,
  Umbrella,
  Zap,
  ZoomIn,
} from 'lucide-vue-next'

defineOptions({
  nodeMetadata: {
    category: 'Layout',
    icon: MessageSquareIcon, // Icon for Vue Flow's internal UI/palette
    label: 'Comment Block',
    description: 'Lightweight comment box for organizing flow layout',
    initialData: {
      customTitle: '',
      comment: '',
      width: 300,
      height: 200,
      x: 0,
      y: 0,
      zIndex: 0,
      colorClass: 'node-gray',
      iconClass: 'message-square', // Default icon for the comment box
    },
  },
})

const props = defineProps(['data'])

const showSettings = ref(false)

const availableColors = [
  { name: 'Gray', class: 'node-gray' },
  { name: 'Blue', class: 'node-blue' },
  { name: 'Purple', class: 'node-purple' },
  { name: 'Green', class: 'node-green' },
  { name: 'Red', class: 'node-red' },
  { name: 'Yellow', class: 'node-yellow' },
  { name: 'Pink', class: 'node-pink' },
]

// Map icon class names to their actual imported components
const iconComponentsMap = {
  'message-square': MessageSquareIcon,
  'x-icon': XIcon,
  'calendar-days': CalendarDays,
  'alarm-clock-off': AlarmClockOff,
  'bell-ring': BellRing,
  'book': Book,
  'briefcase': Briefcase,
  'bug': Bug,
  'camera': Camera,
  'cloud': Cloud,
  'code': Code,
  'coffee': Coffee,
  'database': Database,
  'diamond': Diamond,
  'droplet': Droplet,
  'feather': Feather,
  'flower-2': Flower2,
  'gift': Gift,
  'globe': Globe,
  'headphones': Headphones,
  'heart': Heart,
  'lightbulb': Lightbulb,
  'map-pin': MapPin,
  'music': Music,
  'palette': Palette,
  'pie-chart': PieChart,
  'plug': Plug,
  'rocket': Rocket,
  'shield-question': ShieldQuestion,
  'star': Star,
  'sunrise': Sunrise,
  'target': Target,
  'tractor': Tractor,
  'umbrella': Umbrella,
  'zap': Zap,
  'zoom-in': ZoomIn,
  // Add other icons here if you import them
}

const availableIcons = [
  { name: 'Message', class: 'message-square' },
  { name: 'Close', class: 'x-icon' },
  { name: 'Calendar', class: 'calendar-days' },
  { name: 'Alarm Off', class: 'alarm-clock-off' },
  { name: 'Bell Ring', class: 'bell-ring' },
  { name: 'Book', class: 'book' },
  { name: 'Briefcase', class: 'briefcase' },
  { name: 'Bug', class: 'bug' },
  { name: 'Camera', class: 'camera' },
  { name: 'Cloud', class: 'cloud' },
  { name: 'Code', class: 'code' },
  { name: 'Coffee', class: 'coffee' },
  { name: 'Database', class: 'database' },
  { name: 'Diamond', class: 'diamond' },
  { name: 'Droplet', class: 'droplet' },
  { name: 'Feather', class: 'feather' },
  { name: 'Flower', class: 'flower-2' },
  { name: 'Gift', class: 'gift' },
  { name: 'Globe', class: 'globe' },
  { name: 'Headphones', class: 'headphones' },
  { name: 'Heart', class: 'heart' },
  { name: 'Lightbulb', class: 'lightbulb' },
  { name: 'Map Pin', class: 'map-pin' },
  { name: 'Music', class: 'music' },
  { name: 'Palette', class: 'palette' },
  { name: 'Pie Chart', class: 'pie-chart' },
  { name: 'Plug', class: 'plug' },
  { name: 'Rocket', class: 'rocket' },
  { name: 'Shield Question', class: 'shield-question' },
  { name: 'Star', class: 'star' },
  { name: 'Sunrise', class: 'sunrise' },
  { name: 'Target', class: 'target' },
  { name: 'Tractor', class: 'tractor' },
  { name: 'Umbrella', class: 'umbrella' },
  { name: 'Zap', class: 'zap' },
  { name: 'Zoom In', class: 'zoom-in' },
]

function getIconComponent(iconClassName) {
  return iconComponentsMap[iconClassName] || MessageSquareIcon // Fallback
}

function toggleSettings() {
  showSettings.value = !showSettings.value
}

function selectColor(colorClass) {
  props.data.colorClass = colorClass
}

function selectIcon(iconClass) {
  props.data.iconClass = iconClass
}

// --- Resize Logic (unchanged) ---
const resizeHandles = [
  { class: 'handle-top-left', direction: 'nw' },
  { class: 'handle-top-right', direction: 'ne' },
  { class: 'handle-bottom-left', direction: 'sw' },
  { class: 'handle-bottom-right', direction: 'se' },
  { class: 'handle-top', direction: 'n' },
  { class: 'handle-bottom', direction: 's' },
  { class: 'handle-left', direction: 'w' },
  { class: 'handle-right', direction: 'e' },
]

let initialMouseX = 0
let initialMouseY = 0
let initialWidth = 0
let initialHeight = 0
let initialNodeX = 0
let initialNodeY = 0
let currentDirection = ''

const MIN_WIDTH = 150
const MIN_HEIGHT = 100

function startResize(event, direction) {
  event.preventDefault()
  event.stopPropagation()

  initialMouseX = event.clientX
  initialMouseY = event.clientY
  initialWidth = props.data.width
  initialHeight = props.data.height
  initialNodeX = props.data.x
  initialNodeY = props.data.y
  currentDirection = direction

  document.addEventListener('mousemove', doResize)
  document.addEventListener('mouseup', stopResize)

  document.body.style.cursor = getCursor(direction)
  document.body.style.userSelect = 'none'
  document.body.style.webkitUserSelect = 'none'
  document.body.style.mozUserSelect = 'none'
  document.body.style.msUserSelect = 'none'
}

function doResize(event) {
  const dx = event.clientX - initialMouseX
  const dy = event.clientY - initialMouseY

  let newWidth = initialWidth
  let newHeight = initialHeight
  let newX = initialNodeX
  let newY = initialNodeY

  switch (currentDirection) {
    case 'e':
      newWidth = Math.max(MIN_WIDTH, initialWidth + dx)
      break
    case 's':
      newHeight = Math.max(MIN_HEIGHT, initialHeight + dy)
      break
    case 'w':
      newWidth = Math.max(MIN_WIDTH, initialWidth - dx)
      newX = initialNodeX + (initialWidth - newWidth)
      break
    case 'n':
      newHeight = Math.max(MIN_HEIGHT, initialHeight - dy)
      newY = initialNodeY + (initialHeight - newHeight)
      break
    case 'ne':
      newWidth = Math.max(MIN_WIDTH, initialWidth + dx)
      newHeight = Math.max(MIN_HEIGHT, initialHeight - dy)
      newY = initialNodeY + (initialHeight - newHeight)
      break
    case 'nw':
      newWidth = Math.max(MIN_WIDTH, initialWidth - dx)
      newX = initialNodeX + (initialWidth - newWidth)
      newHeight = Math.max(MIN_HEIGHT, initialHeight - dy)
      newY = initialNodeY + (initialHeight - newHeight)
      break
    case 'se':
      newWidth = Math.max(MIN_WIDTH, initialWidth + dx)
      newHeight = Math.max(MIN_HEIGHT, initialHeight + dy)
      break
    case 'sw':
      newWidth = Math.max(MIN_WIDTH, initialWidth - dx)
      newX = initialNodeX + (initialWidth - newWidth)
      newHeight = Math.max(MIN_HEIGHT, initialHeight + dy)
      break
  }

  props.data.width = newWidth
  props.data.height = newHeight
  props.data.x = newX
  props.data.y = newY
}

function stopResize() {
  document.removeEventListener('mousemove', doResize)
  document.removeEventListener('mouseup', stopResize)

  document.body.style.cursor = ''
  document.body.style.userSelect = ''
  document.body.style.webkitUserSelect = ''
  document.body.style.mozUserSelect = ''
  document.body.style.msUserSelect = ''
}

function getCursor(direction) {
  switch (direction) {
    case 'n':
    case 's':
      return 'ns-resize'
    case 'e':
    case 'w':
      return 'ew-resize'
    case 'nw':
    case 'se':
      return 'nwse-resize'
    case 'ne':
    case 'sw':
      return 'nesw-resize'
    default:
      return 'auto'
  }
}
</script>

<style scoped>
.comment-block {
  position: absolute;
  min-width: 150px;
  min-height: 100px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: inset 0 0 12px rgba(0, 0, 0, 0.2);
  box-sizing: border-box;
  pointer-events: auto;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* --- Scrollbar Customization --- */
.comment-textarea-wrapper::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
.comment-textarea-wrapper::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 10px;
}
.comment-textarea-wrapper::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  border: 2px solid transparent;
  background-clip: padding-box;
}
.comment-textarea-wrapper::-webkit-scrollbar-thumb:hover {
  background-color: rgba(255, 255, 255, 0.3);
}
.comment-textarea-wrapper {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.2) rgba(0, 0, 0, 0.1);
}

/* --- Color Classes (unchanged, add yours here if not already present) --- */
/* Your existing color classes will go here, like: */
.comment-block.node-blue { background: rgba(59, 130, 246, 0.05); border-color: rgba(59, 130, 246, 0.2); }
.comment-block.node-blue .comment-drag-handle { border-bottom-color: rgba(59, 130, 246, 0.25); }
.comment-block.node-blue .settings-icon { color: rgba(59, 130, 246, 0.7); }
.comment-block.node-blue .comment-title-input { color: rgba(59, 130, 246, 0.9); }
.comment-block.node-blue .comment-textarea { color: rgba(59, 130, 246, 0.8); }
.comment-block.node-blue:hover { box-shadow: 0 25px 50px -12px rgba(59, 130, 246, 0.1), inset 0 0 12px rgba(0, 0, 0, 0.2); border-color: rgba(59, 130, 246, 0.3); }

/* ... (all other color classes like purple, green, red, yellow, pink, gray should be here) ... */
.comment-block.node-purple { /* ... */ }
.comment-block.node-green { /* ... */ }
.comment-block.node-red { /* ... */ }
.comment-block.node-yellow { /* ... */ }
.comment-block.node-pink { /* ... */ }
.comment-block.node-gray { /* ... */ }


/* General Styles */
.comment-drag-handle {
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.05);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  cursor: grab;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
}

.title-icon {
  margin-right: 0.5rem;
  color: white; /* Default color, overridden by theme */
  flex-shrink: 0;
}

.settings-wrapper {
  position: relative;
  height: 100%;
  display: flex;
  align-items: center;
  margin-left: auto;
}

.settings-button {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: background-color 0.15s ease;
}

.settings-button:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.settings-icon {
  display: block;
  color: white;
}

.settings-palette {
  position: absolute;
  top: calc(100% + 5px);
  right: 0;
  background: rgba(0, 0, 0, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 0.75rem;
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
  z-index: 100;
  min-width: 200px; /* Increased min-width to accommodate more icons */
  max-height: 300px; /* Limit height to prevent palette from becoming too large */
  overflow-y: auto; /* Allow vertical scrolling within the palette if many icons */
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.palette-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.section-title {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 0.25rem;
  letter-spacing: 0.05em;
}

.color-options {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
}

.color-swatch {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid transparent;
  transition: border-color 0.15s ease;
}

.color-swatch:hover {
  border-color: white;
}

/* Specific color swatch backgrounds (unchanged) */
.color-swatch.node-blue { background-color: rgba(59, 130, 246, 0.8); }
.color-swatch.node-purple { background-color: rgba(139, 92, 246, 0.8); }
.color-swatch.node-green { background-color: rgba(16, 185, 129, 0.8); }
.color-swatch.node-red { background-color: rgba(239, 68, 68, 0.8); }
.color-swatch.node-yellow { background-color: rgba(245, 158, 11, 0.8); }
.color-swatch.node-pink { background-color: rgba(236, 72, 153, 0.8); }
.color-swatch.node-gray { background-color: rgba(107, 114, 128, 0.8); }

.icon-options {
  display: grid; /* Changed to grid for more controlled layout */
  grid-template-columns: repeat(auto-fit, minmax(40px, 1fr)); /* Responsive grid for icons */
  gap: 0.5rem;
}

.icon-option {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  cursor: pointer;
  padding: 0.4rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.7);
  transition: background-color 0.15s ease, border-color 0.15s ease;
  aspect-ratio: 1 / 1; /* Keep icons square */
}

.icon-option:hover {
  background-color: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.2);
}

.icon-option.is-active {
  background-color: rgba(59, 130, 246, 0.3);
  border-color: rgba(59, 130, 246, 0.5);
  color: white;
}


/* Base styles for all resize handles (unchanged) */
.resize-handle {
  position: absolute;
  background-color: rgba(0, 150, 255, 0.5);
  opacity: 0;
  transition: opacity 0.1s ease;
  z-index: 10;
}

.comment-block:hover .resize-handle {
  opacity: 1;
}

/* Specific resize handle positions (unchanged) */
.handle-top-left { width: 15px; height: 15px; top: -7.5px; left: -7.5px; cursor: nwse-resize; border-radius: 50%; }
.handle-top-right { width: 15px; height: 15px; top: -7.5px; right: -7.5px; cursor: nesw-resize; border-radius: 50%; }
.handle-bottom-left { width: 15px; height: 15px; bottom: -7.5px; left: -7.5px; cursor: nesw-resize; border-radius: 50%; }
.handle-bottom-right { width: 15px; height: 15px; bottom: -7.5px; right: -7.5px; cursor: nwse-resize; border-radius: 50%; }
.handle-top { width: calc(100% - 30px); height: 10px; top: -5px; left: 15px; right: 15px; cursor: ns-resize; }
.handle-bottom { width: calc(100% - 30px); height: 10px; bottom: -5px; left: 15px; right: 15px; cursor: ns-resize; }
.handle-left { width: 10px; height: calc(100% - 30px); left: -5px; top: 15px; bottom: 15px; cursor: ew-resize; }
.handle-right { width: 10px; height: calc(100% - 30px); right: -5px; top: 15px; bottom: 15px; cursor: ew-resize; }

/* Main Content Area (below drag handle) */
.comment-textarea-wrapper {
  flex: 1;
  padding: 1rem;
  overflow: auto;
  pointer-events: auto;
  display: flex;
}

.comment-title-input {
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
  background-color: transparent;
  outline: none;
  width: 100%;
  border: none;
  pointer-events: auto;
  flex-grow: 1;
}

.comment-textarea {
  width: 100%;
  height: 100%;
  background-color: transparent;
  color: white;
  font-size: 0.875rem;
  outline: none;
  resize: none;
  border: none;
  pointer-events: auto;
}
</style>
