<template>
  <div
    class="sticker-block"
    :class="[data.colorClass, { 'is-sticky': data.isSticky, 'is-pulsing': data.isPulsing }]"
    :style="{
      width: data.size + 'px',
      height: data.size + 'px',
      left: data.x + 'px',
      top: data.y + 'px',
      zIndex: data.zIndex ?? 0,
      transform: `rotate(${data.rotation || 0}deg) scale(${data.scale || 1})`,
    }"
  >
    <!-- Main Emoji Display -->
    <div
      class="sticker-emoji"
      :style="{
        fontSize: (data.size * 0.6) + 'px',
        lineHeight: data.size + 'px'
      }"
      @dblclick="toggleSettings"
    >
      {{ data.selectedEmoji || '😀' }}
    </div>

    <!-- Quick Controls (show on hover) -->
    <div class="sticker-controls">
      <button
        class="control-btn"
        @click="toggleSettings"
        title="Customize Sticker"
      >
        <SettingsIcon :size="12" />
      </button>

      <button
        class="control-btn delete-btn"
        @click="deleteSticker"
        title="Delete Sticker"
      >
        <XIcon :size="12" />
      </button>
    </div>

    <!-- Settings Panel -->
    <div v-if="showSettings" class="sticker-settings nodrag nopan" @click.stop>
      <!-- Emoji Selection -->
      <div class="settings-section">
        <h4 class="section-title">
          <SmileIcon :size="14" />
          Choose Emoji
        </h4>
        <div class="emoji-categories">
          <button
            v-for="category in emojiCategories"
            :key="category.name"
            :class="['category-btn', { 'active': activeCategory === category.name }]"
            @click="activeCategory = category.name"
            :title="category.name"
          >
            {{ category.icon }}
          </button>
        </div>
        <div class="emoji-grid">
          <button
            v-for="emoji in getCurrentEmojis()"
            :key="emoji"
            :class="['emoji-option', { 'selected': data.selectedEmoji === emoji }]"
            @click="selectEmoji(emoji)"
          >
            {{ emoji }}
          </button>
        </div>
      </div>

      <!-- Size Control -->
      <div class="settings-section">
        <h4 class="section-title">
          <ExpandIcon :size="14" />
          Size: {{ data.size }}px
        </h4>
        <input
          type="range"
          :value="data.size || 60"
          @input="changeSize($event.target.value)"
          min="30"
          max="200"
          step="5"
          class="size-slider"
        />
        <div class="size-presets">
          <button
            v-for="size in [40, 60, 80, 120, 160]"
            :key="size"
            @click="changeSize(size)"
            :class="['preset-btn', { 'active': (data.size || 60) === size }]"
          >
            {{ size }}
          </button>
        </div>
      </div>

      <!-- Rotation Control -->
      <div class="settings-section">
        <h4 class="section-title">
          <RotateCwIcon :size="14" />
          Rotation: {{ data.rotation || 0 }}°
        </h4>
        <input
          type="range"
          :value="data.rotation || 0"
          @input="changeRotation($event.target.value)"
          min="-180"
          max="180"
          step="15"
          class="rotation-slider"
        />
        <div class="rotation-presets">
          <button
            v-for="angle in [-45, 0, 45, 90, 135, 180]"
            :key="angle"
            @click="changeRotation(angle)"
            :class="['preset-btn', { 'active': (data.rotation || 0) === angle }]"
          >
            {{ angle }}°
          </button>
        </div>
      </div>

      <!-- Scale Control -->
      <div class="settings-section">
        <h4 class="section-title">
          <ZoomInIcon :size="14" />
          Scale: {{ Math.round((data.scale || 1) * 100) }}%
        </h4>
        <input
          type="range"
          :value="data.scale || 1"
          @input="changeScale($event.target.value)"
          min="0.5"
          max="2"
          step="0.1"
          class="scale-slider"
        />
        <div class="scale-presets">
          <button
            v-for="scale in [0.5, 0.8, 1, 1.2, 1.5, 2]"
            :key="scale"
            @click="changeScale(scale)"
            :class="['preset-btn', { 'active': (data.scale || 1) === scale }]"
          >
            {{ Math.round(scale * 100) }}%
          </button>
        </div>
      </div>

      <!-- Effects -->
      <div class="settings-section">
        <h4 class="section-title">
          <SparklesIcon :size="14" />
          Effects
        </h4>
        <div class="effect-options">
          <button
            :class="['effect-btn', { 'active': data.isPulsing }]"
            @click="togglePulsing"
          >
            <ZapIcon :size="14" />
            Pulse
          </button>

          <button
            :class="['effect-btn', { 'active': data.isSticky }]"
            @click="toggleSticky"
          >
            <PinIcon :size="14" />
            Pin
          </button>
        </div>
      </div>

      <!-- Background -->
      <div class="settings-section">
        <h4 class="section-title">
          <PaletteIcon :size="14" />
          Background
        </h4>
        <div class="background-options">
          <button
            v-for="bg in backgroundOptions"
            :key="bg.class"
            :class="['bg-option', bg.class, { 'selected': data.colorClass === bg.class }]"
            @click="selectBackground(bg.class)"
            :title="bg.name"
          >
            {{ bg.name === 'None' ? '○' : '' }}
          </button>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="settings-section">
        <h4 class="section-title">
          <ZapIcon :size="14" />
          Quick Actions
        </h4>
        <div class="quick-actions">
          <button class="action-btn" @click="randomEmoji">
            <ShuffleIcon :size="14" />
            Random
          </button>
          <button class="action-btn" @click="resetSettings">
            <RotateCcwIcon :size="14" />
            Reset
          </button>
          <button class="action-btn" @click="duplicateSticker">
            <CopyIcon :size="14" />
            Duplicate
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineOptions, ref, computed } from 'vue'
import {
  SettingsIcon, XIcon, SmileIcon, ExpandIcon, RotateCwIcon,
  ZoomInIcon, SparklesIcon, ZapIcon, PinIcon, PaletteIcon,
  ShuffleIcon, RotateCcwIcon, CopyIcon
} from 'lucide-vue-next'

defineOptions({
  nodeMetadata: {
    category: 'Layout',
    icon: SmileIcon,
    label: 'Sticker',
    description: 'Decorative emoji stickers for your workflow',
    initialData: {
      selectedEmoji: '😀',
      size: 60,
      x: 0,
      y: 0,
      zIndex: 100,
      rotation: 0,
      scale: 1,
      colorClass: 'bg-none',
      isSticky: false,
      isPulsing: false,
    },
  },
})

const props = defineProps(['data'])
const emit = defineEmits(['delete', 'duplicate'])

// Reactive state
const showSettings = ref(false)
const activeCategory = ref('smileys')

// Emoji categories and collections
const emojiCategories = [
  { name: 'smileys', icon: '😀', emojis: ['😀', '😃', '😄', '😁', '😅', '😂', '🤣', '🥲', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🥸', '🤩', '🥳'] },
  { name: 'gestures', icon: '👍', emojis: ['👍', '👎', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👋', '🤚', '🖐️', '✋', '🖖', '👏', '🙌', '🤲', '🤝', '🙏'] },
  { name: 'arrows', icon: '➡️', emojis: ['⬆️', '↗️', '➡️', '↘️', '⬇️', '↙️', '⬅️', '↖️', '↕️', '↔️', '↩️', '↪️', '⤴️', '⤵️', '🔄', '🔃', '🔁', '🔂', '▶️', '⏸️', '⏹️', '⏺️', '⏯️', '⏮️', '⏭️', '⏪', '⏩', '🔀', '🔁', '🔂', '🔼', '🔽'] },
  { name: 'hearts', icon: '❤️', emojis: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟'] },
  { name: 'symbols', icon: '⭐', emojis: ['⭐', '🌟', '✨', '💫', '🔥', '💥', '💯', '💢', '💨', '💦', '💤', '💭', '🗯️', '💬', '👁️‍🗨️', '🗨️', '💌', '💍', '💎', '🔑', '🏆', '🎯', '🎪', '🎨'] },
  { name: 'nature', icon: '🌱', emojis: ['🌱', '🌿', '🍀', '🌸', '🌺', '🌻', '🌷', '🌹', '🌵', '🌳', '🌲', '🌴', '☀️', '🌙', '⭐', '🌈', '☁️', '⛅', '🌤️', '🌦️', '🌧️', '⛈️', '🌩️', '❄️'] },
  { name: 'food', icon: '🍕', emojis: ['🍎', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🥬', '🥒', '🌶️', '🫑', '🌽', '🥕', '🍕', '🍔', '🌭', '🥪', '🌮', '🌯', '🫔', '🥙'] },
  { name: 'objects', icon: '💻', emojis: ['💻', '🖥️', '📱', '⌚', '📷', '📹', '🎥', '💾', '💿', '📀', '🖨️', '⌨️', '🖱️', '🖲️', '💡', '🔦', '🕯️', '🪔', '🔋', '🔌', '📞', '☎️', '📟', '📠', '💵', '💴', '💶', '💷', '🪙', '💰', '💳', '💎'] }
]

const backgroundOptions = [
  { name: 'None', class: 'bg-none' },
  { name: 'Circle', class: 'bg-circle' },
  { name: 'Square', class: 'bg-square' },
  { name: 'Blue', class: 'bg-blue' },
  { name: 'Purple', class: 'bg-purple' },
  { name: 'Green', class: 'bg-green' },
  { name: 'Red', class: 'bg-red' },
  { name: 'Yellow', class: 'bg-yellow' },
  { name: 'Pink', class: 'bg-pink' }
]

// Methods
function getCurrentEmojis() {
  const category = emojiCategories.find(cat => cat.name === activeCategory.value)
  return category ? category.emojis : emojiCategories[0].emojis
}

function selectEmoji(emoji) {
  props.data.selectedEmoji = emoji
  showSettings.value = false
}

function changeSize(newSize) {
  props.data.size = parseInt(newSize)
}

function changeRotation(newRotation) {
  props.data.rotation = parseInt(newRotation)
}

function changeScale(newScale) {
  props.data.scale = parseFloat(newScale)
}

function togglePulsing() {
  props.data.isPulsing = !props.data.isPulsing
}

function toggleSticky() {
  props.data.isSticky = !props.data.isSticky
  props.data.zIndex = props.data.isSticky ? 1000 : 100
}

function selectBackground(bgClass) {
  props.data.colorClass = bgClass
}

function toggleSettings() {
  showSettings.value = !showSettings.value
}

function randomEmoji() {
  const allEmojis = emojiCategories.flatMap(cat => cat.emojis)
  const randomIndex = Math.floor(Math.random() * allEmojis.length)
  props.data.selectedEmoji = allEmojis[randomIndex]
}

function resetSettings() {
  props.data.size = 60
  props.data.rotation = 0
  props.data.scale = 1
  props.data.colorClass = 'bg-none'
  props.data.isPulsing = false
  props.data.isSticky = false
  props.data.zIndex = 100
}

function duplicateSticker() {
  emit('duplicate', {
    ...props.data,
    x: props.data.x + 20,
    y: props.data.y + 20
  })
  showSettings.value = false
}

function deleteSticker() {
  if (confirm('Delete this sticker?')) {
    emit('delete')
  }
}
</script>

<script>
// Export node metadata for the node library
export const nodeMetadata = {
  category: 'Layout',
  icon: 'SmileIcon',
  label: 'Sticker',
  description: 'Decorative emoji stickers for your workflow',
  initialData: {
    selectedEmoji: '😀',
    size: 60,
    rotation: 0,
    scale: 1,
    colorClass: 'bg-none'
  }
}
</script>

<style scoped>
.sticker-block {
  position: absolute;
  cursor: grab;
  user-select: none;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.3s ease;
  pointer-events: auto;
}

.sticker-block:hover {
  filter: brightness(1.1);
}

.sticker-block.is-sticky {
  box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
}

.sticker-block.is-pulsing {
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(var(--base-scale, 1)); }
  50% { transform: scale(calc(var(--base-scale, 1) * 1.1)); }
}

.sticker-emoji {
  text-align: center;
  cursor: pointer;
  transition: transform 0.2s ease;
  z-index: 2;
}

.sticker-emoji:hover {
  transform: scale(1.05);
}

.sticker-controls {
  position: absolute;
  top: -8px;
  right: -8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.2s ease;
  z-index: 10;
}

.sticker-block:hover .sticker-controls {
  opacity: 1;
}

.control-btn {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: none;
  background: rgba(17, 24, 39, 0.9);
  color: rgba(255, 255, 255, 0.8);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(8px);
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.control-btn:hover {
  background: rgba(59, 130, 246, 0.8);
  color: white;
  transform: scale(1.1);
}

.control-btn.delete-btn:hover {
  background: rgba(239, 68, 68, 0.8);
}

.sticker-settings {
  position: absolute;
  top: calc(100% + 12px);
  left: 50%;
  transform: translateX(-50%);
  background: rgba(17, 24, 39, 0.95);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
  z-index: 1000;
  min-width: 300px;
  max-width: 400px;
  max-height: 80vh;
  overflow-y: auto;
  backdrop-filter: blur(12px);
  animation: slideUp 0.2s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

.settings-section {
  margin-bottom: 1.5rem;
}

.settings-section:last-child {
  margin-bottom: 0;
}

.section-title {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  margin-bottom: 0.75rem;
  letter-spacing: 0.05em;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.emoji-categories {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

.category-btn {
  width: 32px;
  height: 32px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.05);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  transition: all 0.2s ease;
}

.category-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.category-btn.active {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.4);
}

.emoji-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 0.25rem;
  max-height: 160px;
  overflow-y: auto;
}

.emoji-option {
  width: 32px;
  height: 32px;
  border: 1px solid transparent;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.05);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  transition: all 0.2s ease;
}

.emoji-option:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
  transform: scale(1.1);
}

.emoji-option.selected {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.4);
}

.size-slider, .rotation-slider, .scale-slider {
  width: 100%;
  margin: 0.5rem 0;
  appearance: none;
  height: 6px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  outline: none;
}

.size-slider::-webkit-slider-thumb,
.rotation-slider::-webkit-slider-thumb,
.scale-slider::-webkit-slider-thumb {
  appearance: none;
  width: 18px;
  height: 18px;
  background: rgba(59, 130, 246, 0.8);
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}

.size-slider::-webkit-slider-thumb:hover,
.rotation-slider::-webkit-slider-thumb:hover,
.scale-slider::-webkit-slider-thumb:hover {
  background: rgba(37, 99, 235, 1);
  transform: scale(1.1);
}

.size-presets, .rotation-presets, .scale-presets {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.25rem;
  margin-top: 0.5rem;
}

.preset-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  padding: 0.375rem;
  font-size: 0.75rem;
  transition: all 0.2s ease;
}

.preset-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.preset-btn.active {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.4);
  color: rgba(147, 197, 253, 1);
}

.effect-options {
  display: flex;
  gap: 0.5rem;
}

.effect-btn {
  flex: 1;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  padding: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.effect-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.effect-btn.active {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.4);
  color: rgba(147, 197, 253, 1);
}

.background-options {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
}

.bg-option {
  height: 40px;
  border: 2px solid transparent;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.8);
  font-weight: 500;
  transition: all 0.2s ease;
  font-size: 0.75rem;
}

.bg-option:hover {
  border-color: rgba(255, 255, 255, 0.3);
  transform: scale(1.05);
}

.bg-option.selected {
  border-color: rgba(255, 255, 255, 0.8);
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
}

.bg-option.bg-none { background: rgba(255, 255, 255, 0.1); }
.bg-option.bg-circle { background: radial-gradient(circle, rgba(255, 255, 255, 0.2), transparent); }
.bg-option.bg-square { background: rgba(255, 255, 255, 0.15); border-radius: 4px; }
.bg-option.bg-blue { background: linear-gradient(135deg, rgba(59, 130, 246, 0.3), rgba(29, 78, 216, 0.3)); }
.bg-option.bg-purple { background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(124, 58, 237, 0.3)); }
.bg-option.bg-green { background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(5, 150, 105, 0.3)); }
.bg-option.bg-red { background: linear-gradient(135deg, rgba(239, 68, 68, 0.3), rgba(220, 38, 38, 0.3)); }
.bg-option.bg-yellow { background: linear-gradient(135deg, rgba(245, 158, 11, 0.3), rgba(217, 119, 6, 0.3)); }
.bg-option.bg-pink { background: linear-gradient(135deg, rgba(236, 72, 153, 0.3), rgba(219, 39, 119, 0.3)); }

.quick-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  flex: 1;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  padding: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  font-size: 0.75rem;
  transition: all 0.2s ease;
}

.action-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.9);
}

/* Background styles for sticker */
.sticker-block.bg-circle {
  background: radial-gradient(circle, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.05));
  backdrop-filter: blur(8px);
}

.sticker-block.bg-square {
  border-radius: 15%;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(8px);
}

.sticker-block.bg-blue {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(29, 78, 216, 0.2));
  backdrop-filter: blur(8px);
}

.sticker-block.bg-purple {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(124, 58, 237, 0.2));
  backdrop-filter: blur(8px);
}

.sticker-block.bg-green {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.2));
  backdrop-filter: blur(8px);
}

.sticker-block.bg-red {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));
  backdrop-filter: blur(8px);
}

.sticker-block.bg-yellow {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(217, 119, 6, 0.2));
  backdrop-filter: blur(8px);
}

.sticker-block.bg-pink {
  background: linear-gradient(135deg, rgba(236, 72, 153, 0.2), rgba(219, 39, 119, 0.2));
  backdrop-filter: blur(8px);
}

/* Scrollbar styling for settings panel */
.sticker-settings::-webkit-scrollbar {
  width: 6px;
}

.sticker-settings::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 3px;
}

.sticker-settings::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

.sticker-settings::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* Firefox slider styling */
.size-slider::-moz-range-thumb,
.rotation-slider::-moz-range-thumb,
.scale-slider::-moz-range-thumb {
  width: 18px;
  height: 18px;
  background: rgba(59, 130, 246, 0.8);
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.size-slider::-moz-range-track,
.rotation-slider::-moz-range-track,
.scale-slider::-moz-range-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  height: 6px;
}

/* Animation for pulse effect */
.sticker-block.is-pulsing .sticker-emoji {
  animation: pulseEmoji 2s ease-in-out infinite;
}

@keyframes pulseEmoji {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .sticker-settings {
    min-width: 280px;
    max-width: 90vw;
  }

  .emoji-grid {
    grid-template-columns: repeat(6, 1fr);
  }

  .background-options {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Accessibility improvements */
.control-btn:focus,
.category-btn:focus,
.emoji-option:focus,
.preset-btn:focus,
.effect-btn:focus,
.bg-option:focus,
.action-btn:focus {
  outline: 2px solid rgba(59, 130, 246, 0.6);
  outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .sticker-settings {
    background: rgba(0, 0, 0, 0.95);
    border: 2px solid rgba(255, 255, 255, 0.8);
  }

  .control-btn {
    background: rgba(0, 0, 0, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.6);
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .sticker-block,
  .sticker-emoji,
  .control-btn,
  .sticker-settings {
    transition: none;
  }

  .sticker-block.is-pulsing,
  .sticker-block.is-pulsing .sticker-emoji {
    animation: none;
  }
}
</style>
