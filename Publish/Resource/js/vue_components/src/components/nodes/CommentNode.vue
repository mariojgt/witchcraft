<template>
  <div
    class="comment-block"
    :class="[
      data.colorClass,
      data.category,
      {
        'is-minimized': data.isMinimized,
        'is-sticky': data.isSticky,
        'is-overdue': isOverdue,
        'has-urgent-tasks': hasUrgentTasks,
        'is-archived': data.isArchived
      }
    ]"
    :style="{
      width: data.width + 'px',
      height: data.isMinimized ? '50px' : data.height + 'px',
      left: data.x + 'px',
      top: data.y + 'px',
      zIndex: data.zIndex ?? 0,
    }"
  >
    <!-- Header/Drag Handle -->
    <div class="comment-drag-handle">
      <div class="header-left">
        <div class="category-indicator" :class="data.category" :title="getCategoryName(data.category)">
          <component :is="getCategoryIcon(data.category)" :size="16" />
        </div>

        <input
          v-model="data.customTitle"
          class="comment-title-input nodrag nopan"
          :placeholder="data.isMinimized ? getShortPlaceholder() : 'Enter title...'"
          maxlength="100"
          @blur="updateTimestamp"
        />

        <div class="header-badges">
          <!-- Progress indicator -->
          <div v-if="data.tasks && data.tasks.length > 0"
               class="progress-badge"
               :title="`${completedTasks}/${data.tasks.length} tasks completed`">
            <CheckCircleIcon :size="12" />
            {{ completedTasks }}/{{ data.tasks.length }}
          </div>

          <!-- Deadline indicator -->
          <div v-if="data.deadline"
               class="deadline-badge"
               :class="{ 'overdue': isOverdue, 'due-soon': isDueSoon }"
               :title="`Deadline: ${formatDate(data.deadline)}`">
            <ClockIcon :size="12" />
            {{ getDeadlineText() }}
          </div>

          <!-- Assignee indicator -->
          <div v-if="data.assignedTo && data.assignedTo.length > 0"
               class="assignee-badge"
               :title="`Assigned to: ${data.assignedTo.join(', ')}`">
            <UsersIcon :size="12" />
            {{ data.assignedTo.length }}
          </div>
        </div>
      </div>

      <div class="header-actions nodrag nopan">
        <!-- Status indicator -->
        <div class="status-indicator" :class="data.status" :title="`Status: ${data.status}`">
          <component :is="getStatusIcon(data.status)" :size="14" />
        </div>

        <!-- Priority indicator -->
        <div v-if="data.priority && data.priority !== 'normal'"
             :class="`priority-indicator priority-${data.priority}`"
             :title="`${data.priority} priority`">
          <component :is="getPriorityIcon(data.priority)" :size="14" />
        </div>

        <!-- Quick actions -->
        <button class="action-button" @click="toggleComplete" :title="data.isCompleted ? 'Mark incomplete' : 'Mark complete'">
          <component :is="data.isCompleted ? CheckCircle2Icon : CircleIcon" :size="14" :class="{ 'completed': data.isCompleted }" />
        </button>

        <!-- Archive -->
        <button class="action-button" @click="toggleArchive" :title="data.isArchived ? 'Unarchive' : 'Archive'">
          <ArchiveIcon :size="14" :class="{ 'archived': data.isArchived }" />
        </button>

        <!-- Minimize/Maximize -->
        <button class="action-button" @click="toggleMinimize" :title="data.isMinimized ? 'Expand' : 'Minimize'">
          <component :is="data.isMinimized ? MaximizeIcon : MinimizeIcon" :size="14" />
        </button>

        <!-- Pin/Unpin -->
        <button class="action-button" @click="toggleSticky" :title="data.isSticky ? 'Unpin' : 'Pin to top'">
          <PinIcon :size="14" :class="{ 'is-pinned': data.isSticky }" />
        </button>

        <!-- Settings -->
        <button class="action-button" @click="toggleSettings" :title="'Settings'">
          <SettingsIcon :size="14" />
        </button>

        <!-- Delete -->
        <button class="action-button delete-btn" @click="deleteComment" :title="'Delete'">
          <XIcon :size="14" />
        </button>
      </div>

      <!-- Advanced Settings Panel -->
      <div v-if="showSettings" class="settings-panel nodrag nopan" @click.stop>
        <div class="settings-tabs">
          <button
            v-for="tab in settingsTabs"
            :key="tab.id"
            :class="['tab-btn', { active: activeTab === tab.id }]"
            @click="activeTab = tab.id"
          >
            <component :is="tab.icon" :size="14" />
            {{ tab.label }}
          </button>
        </div>

        <div class="settings-content">
          <!-- General Tab -->
          <div v-if="activeTab === 'general'" class="settings-section">
            <!-- Category -->
            <div class="setting-group">
              <label class="setting-label">
                <FolderIcon :size="14" />
                Category
              </label>
              <div class="category-options">
                <button
                  v-for="category in categories"
                  :key="category.id"
                  :class="['category-option', category.id, { active: data.category === category.id }]"
                  @click="selectCategory(category.id)"
                  :title="category.description"
                >
                  <component :is="category.icon" :size="16" />
                  {{ category.name }}
                </button>
              </div>
            </div>

            <!-- Status -->
            <div class="setting-group">
              <label class="setting-label">
                <ActivityIcon :size="14" />
                Status
              </label>
              <div class="status-options">
                <button
                  v-for="status in statuses"
                  :key="status.id"
                  :class="['status-option', status.id, { active: data.status === status.id }]"
                  @click="selectStatus(status.id)"
                >
                  <component :is="status.icon" :size="16" />
                  {{ status.name }}
                </button>
              </div>
            </div>

            <!-- Priority -->
            <div class="setting-group">
              <label class="setting-label">
                <AlertTriangleIcon :size="14" />
                Priority
              </label>
              <div class="priority-options">
                <button
                  v-for="priority in priorities"
                  :key="priority.id"
                  :class="['priority-option', `priority-${priority.id}`, { active: data.priority === priority.id }]"
                  @click="selectPriority(priority.id)"
                >
                  <component :is="priority.icon" :size="16" />
                  {{ priority.name }}
                </button>
              </div>
            </div>
          </div>

          <!-- Tasks Tab -->
          <div v-if="activeTab === 'tasks'" class="settings-section">
            <div class="setting-group">
              <label class="setting-label">
                <CheckSquareIcon :size="14" />
                Task List
              </label>

              <!-- Add task input -->
              <div class="task-input-wrapper">
                <input
                  v-model="newTaskText"
                  @keydown.enter="addTask"
                  placeholder="Add new task..."
                  class="task-input"
                  maxlength="200"
                />
                <button @click="addTask" class="add-task-btn" :disabled="!newTaskText.trim()">
                  <PlusIcon :size="14" />
                </button>
              </div>

              <!-- Task list -->
              <div v-if="data.tasks && data.tasks.length > 0" class="tasks-list">
                <div
                  v-for="(task, index) in data.tasks"
                  :key="index"
                  class="task-item"
                  :class="{ completed: task.completed }"
                >
                  <button @click="toggleTask(index)" class="task-checkbox">
                    <component :is="task.completed ? CheckSquareIcon : SquareIcon" :size="14" />
                  </button>
                  <input
                    v-model="task.text"
                    class="task-text"
                    :class="{ completed: task.completed }"
                    @blur="updateTimestamp"
                  />
                  <button @click="removeTask(index)" class="remove-task">
                    <XIcon :size="12" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Schedule Tab -->
          <div v-if="activeTab === 'schedule'" class="settings-section">
            <div class="setting-group">
              <label class="setting-label">
                <CalendarIcon :size="14" />
                Deadline
              </label>
              <input
                type="datetime-local"
                v-model="data.deadline"
                class="date-input"
                @change="updateTimestamp"
              />
              <button v-if="data.deadline" @click="clearDeadline" class="clear-btn">
                Clear deadline
              </button>
            </div>

            <div class="setting-group">
              <label class="setting-label">
                <UsersIcon :size="14" />
                Assign To
              </label>
              <div class="assignee-input-wrapper">
                <input
                  v-model="newAssignee"
                  @keydown.enter="addAssignee"
                  @keydown.comma.prevent="addAssignee"
                  placeholder="Add person..."
                  class="assignee-input"
                  maxlength="50"
                />
                <button @click="addAssignee" class="add-assignee-btn" :disabled="!newAssignee.trim()">
                  <UserPlusIcon :size="14" />
                </button>
              </div>

              <div v-if="data.assignedTo && data.assignedTo.length > 0" class="assignees-list">
                <span
                  v-for="(person, index) in data.assignedTo"
                  :key="index"
                  class="assignee-tag"
                >
                  {{ person }}
                  <button @click="removeAssignee(index)" class="remove-assignee">
                    <XIcon :size="12" />
                  </button>
                </span>
              </div>
            </div>

            <div class="setting-group">
              <label class="setting-label">
                <BellIcon :size="14" />
                Reminders
              </label>
              <div class="reminder-options">
                <button
                  v-for="reminder in reminderOptions"
                  :key="reminder.value"
                  :class="['reminder-option', { active: data.reminder === reminder.value }]"
                  @click="setReminder(reminder.value)"
                >
                  {{ reminder.label }}
                </button>
              </div>
            </div>
          </div>

          <!-- Style Tab -->
          <div v-if="activeTab === 'style'" class="settings-section">
            <div class="setting-group">
              <label class="setting-label">
                <PaletteIcon :size="14" />
                Color Theme
              </label>
              <div class="color-options">
                <div
                  v-for="color in colorThemes"
                  :key="color.class"
                  :class="['color-swatch', color.class, { active: data.colorClass === color.class }]"
                  @click="selectColor(color.class)"
                  :title="color.name"
                ></div>
              </div>
            </div>

            <div class="setting-group">
              <label class="setting-label">
                <TypeIcon :size="14" />
                Font Size
              </label>
              <div class="font-size-control">
                <input
                  type="range"
                  :value="data.fontSize || 14"
                  @input="changeFontSize($event.target.value)"
                  min="10"
                  max="220"
                  step="1"
                  class="font-size-slider"
                />
                <span class="font-size-display">{{ data.fontSize || 14 }}px</span>
              </div>
            </div>

            <div class="setting-group">
              <label class="setting-label">
                <EyeIcon :size="14" />
                Display Options
              </label>
              <div class="display-options">
                <label class="checkbox-option">
                  <input type="checkbox" v-model="data.showTimestamps" @change="updateTimestamp">
                  <span class="checkbox-label">Show timestamps</span>
                </label>
                <label class="checkbox-option">
                  <input type="checkbox" v-model="data.showProgress" @change="updateTimestamp">
                  <span class="checkbox-label">Show progress bar</span>
                </label>
                <label class="checkbox-option">
                  <input type="checkbox" v-model="data.enableMarkdown" @change="updateTimestamp">
                  <span class="checkbox-label">Enable markdown</span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Area -->
    <div v-if="!data.isMinimized" class="comment-content">
      <!-- Progress bar -->
      <div v-if="data.showProgress && data.tasks && data.tasks.length > 0" class="progress-bar-container">
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: progressPercentage + '%' }"></div>
        </div>
        <span class="progress-text">{{ Math.round(progressPercentage) }}% Complete</span>
      </div>

      <!-- Main content area -->
      <div class="comment-textarea-wrapper">
        <textarea
          v-model="data.comment"
          class="comment-textarea nodrag nopan"
          :placeholder="getContentPlaceholder()"
          @input="updateTimestamp"
          :style="fontSizeStyle"
          ref="textarea"
        ></textarea>
      </div>

      <!-- Footer with metadata -->
      <div class="comment-footer">
        <div class="footer-left">
          <span v-if="data.showTimestamps && data.createdAt" class="timestamp">
            Created {{ formatRelativeTime(data.createdAt) }}
          </span>
          <span v-if="data.showTimestamps && data.lastModified && data.lastModified !== data.createdAt" class="timestamp">
            Modified {{ formatRelativeTime(data.lastModified) }}
          </span>
          <span class="word-count">{{ wordCount }} words</span>
        </div>
        <div class="footer-right">
          <span v-if="data.assignedTo && data.assignedTo.length > 0" class="assignees">
            Assigned to {{ data.assignedTo.slice(0, 2).join(', ') }}
            <span v-if="data.assignedTo.length > 2">+{{ data.assignedTo.length - 2 }}</span>
          </span>
        </div>
      </div>
    </div>

    <!-- Resize Handles -->
    <div
      v-if="!data.isMinimized"
      v-for="handle in resizeHandles"
      :key="handle.class"
      :class="['resize-handle', handle.class, 'nodrag', 'nopan']"
      @mousedown="startResize($event, handle.direction)"
    ></div>
  </div>
</template>

<script setup>
import { defineOptions, ref, computed, nextTick, watch } from 'vue'
import {
  // Core icons
  SettingsIcon, MessageSquareIcon, XIcon, PaletteIcon, AlertTriangleIcon,
  CheckCircleIcon, ClockIcon, UsersIcon, PinIcon, MaximizeIcon, MinimizeIcon,
  CheckCircle2Icon, CircleIcon, ArchiveIcon, CheckSquareIcon, SquareIcon,
  PlusIcon, UserPlusIcon, BellIcon, CalendarIcon, TypeIcon, EyeIcon,

  // Category icons
  FolderIcon, LightbulbIcon, BugIcon, TargetIcon, FileTextIcon,
  AlertCircleIcon, BookmarkIcon, RocketIcon, HeartIcon,

  // Status icons
  ActivityIcon, PlayIcon, PauseIcon, StopCircleIcon, CheckIcon,

  // Priority icons
  ArrowUpIcon, ArrowDownIcon, FlameIcon, ZapIcon,

  // Other
  HashIcon
} from 'lucide-vue-next'

defineOptions({
  nodeMetadata: {
    category: 'Layout',
    icon: MessageSquareIcon,
    label: 'Professional Comment',
    description: 'Advanced comment block with task management, deadlines, and collaboration features',
    initialData: {
      customTitle: '',
      comment: '',
      width: 400,
      height: 300,
      x: 0,
      y: 0,
      zIndex: 0,
      colorClass: 'theme-blue',
      category: 'general',
      status: 'active',
      priority: 'normal',
      isMinimized: false,
      isSticky: false,
      isCompleted: false,
      isArchived: false,
      tasks: [],
      assignedTo: [],
      deadline: null,
      reminder: null,
      createdAt: new Date().toISOString(),
      lastModified: new Date().toISOString(),
      fontSize: 14,
      showTimestamps: true,
      showProgress: true,
      enableMarkdown: false,
    },
  },
})

const props = defineProps(['data'])
const emit = defineEmits(['delete'])

// Reactive state
const showSettings = ref(false)
const activeTab = ref('general')
const newTaskText = ref('')
const newAssignee = ref('')
const textarea = ref(null)
const wordCount = ref(0)

// Initialize missing properties
if (!props.data.createdAt) props.data.createdAt = new Date().toISOString()
if (!props.data.lastModified) props.data.lastModified = props.data.createdAt
if (!props.data.tasks) props.data.tasks = []
if (!props.data.assignedTo) props.data.assignedTo = []

// Settings tabs
const settingsTabs = [
  { id: 'general', label: 'General', icon: SettingsIcon },
  { id: 'tasks', label: 'Tasks', icon: CheckSquareIcon },
  { id: 'schedule', label: 'Schedule', icon: CalendarIcon },
  { id: 'style', label: 'Style', icon: PaletteIcon }
]

// Categories
const categories = [
  { id: 'general', name: 'General', icon: MessageSquareIcon, description: 'General notes and comments' },
  { id: 'idea', name: 'Idea', icon: LightbulbIcon, description: 'Ideas and suggestions' },
  { id: 'bug', name: 'Bug', icon: BugIcon, description: 'Bug reports and issues' },
  { id: 'task', name: 'Task', icon: CheckSquareIcon, description: 'Tasks and todos' },
  { id: 'decision', name: 'Decision', icon: TargetIcon, description: 'Decisions and approvals' },
  { id: 'documentation', name: 'Docs', icon: FileTextIcon, description: 'Documentation notes' },
  { id: 'warning', name: 'Warning', icon: AlertCircleIcon, description: 'Warnings and alerts' },
  { id: 'bookmark', name: 'Bookmark', icon: BookmarkIcon, description: 'Important references' },
  { id: 'feature', name: 'Feature', icon: RocketIcon, description: 'Feature requests' },
  { id: 'feedback', name: 'Feedback', icon: HeartIcon, description: 'User feedback' }
]

// Statuses
const statuses = [
  { id: 'active', name: 'Active', icon: PlayIcon },
  { id: 'paused', name: 'Paused', icon: PauseIcon },
  { id: 'completed', name: 'Completed', icon: CheckIcon },
  { id: 'cancelled', name: 'Cancelled', icon: StopCircleIcon }
]

// Priorities
const priorities = [
  { id: 'low', name: 'Low', icon: ArrowDownIcon },
  { id: 'normal', name: 'Normal', icon: CircleIcon },
  { id: 'high', name: 'High', icon: ArrowUpIcon },
  { id: 'urgent', name: 'Urgent', icon: FlameIcon },
  { id: 'critical', name: 'Critical', icon: ZapIcon }
]

// Color themes
const colorThemes = [
  { name: 'Blue', class: 'theme-blue' },
  { name: 'Purple', class: 'theme-purple' },
  { name: 'Green', class: 'theme-green' },
  { name: 'Red', class: 'theme-red' },
  { name: 'Orange', class: 'theme-orange' },
  { name: 'Yellow', class: 'theme-yellow' },
  { name: 'Pink', class: 'theme-pink' },
  { name: 'Gray', class: 'theme-gray' }
]

// Reminder options
const reminderOptions = [
  { label: 'None', value: null },
  { label: '15 min before', value: 15 },
  { label: '1 hour before', value: 60 },
  { label: '1 day before', value: 1440 },
  { label: '1 week before', value: 10080 }
]

// Computed properties
const completedTasks = computed(() => {
  return props.data.tasks ? props.data.tasks.filter(task => task.completed).length : 0
})

const progressPercentage = computed(() => {
  if (!props.data.tasks || props.data.tasks.length === 0) return 0
  return (completedTasks.value / props.data.tasks.length) * 100
})

const hasUrgentTasks = computed(() => {
  return props.data.priority === 'urgent' || props.data.priority === 'critical'
})

const isOverdue = computed(() => {
  if (!props.data.deadline) return false
  return new Date(props.data.deadline) < new Date()
})

const isDueSoon = computed(() => {
  if (!props.data.deadline) return false
  const deadline = new Date(props.data.deadline)
  const now = new Date()
  const diffHours = (deadline - now) / (1000 * 60 * 60)
  return diffHours <= 24 && diffHours > 0
})

const fontSizeStyle = computed(() => ({
  fontSize: `${props.data.fontSize || 14}px`,
  lineHeight: `${(props.data.fontSize || 14) * 1.4}px`
}))

// Functions
function getCategoryName(category) {
  const cat = categories.find(c => c.id === category)
  return cat ? cat.name : 'General'
}

function getCategoryIcon(category) {
  const cat = categories.find(c => c.id === category)
  return cat ? cat.icon : MessageSquareIcon
}

function getStatusIcon(status) {
  const stat = statuses.find(s => s.id === status)
  return stat ? stat.icon : PlayIcon
}

function getPriorityIcon(priority) {
  const prio = priorities.find(p => p.id === priority)
  return prio ? prio.icon : CircleIcon
}

function getShortPlaceholder() {
  const category = getCategoryName(props.data.category)
  return `${category}...`
}

function getContentPlaceholder() {
  const placeholders = {
    idea: 'Describe your idea...',
    bug: 'Describe the bug, steps to reproduce, and expected behavior...',
    task: 'List the tasks that need to be completed...',
    decision: 'Document the decision, context, and rationale...',
    documentation: 'Add documentation notes...',
    warning: 'Describe the warning or concern...',
    bookmark: 'Add important reference information...',
    feature: 'Describe the feature request...',
    feedback: 'Share feedback and suggestions...',
    general: 'Write your comment here...'
  }
  return placeholders[props.data.category] || placeholders.general
}

function getDeadlineText() {
  if (!props.data.deadline) return ''
  const deadline = new Date(props.data.deadline)
  const now = new Date()
  const diffMs = deadline - now
  const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24))

  if (diffMs < 0) return 'Overdue'
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return 'Tomorrow'
  if (diffDays <= 7) return `${diffDays} days`
  return formatDate(props.data.deadline)
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString()
}

function formatRelativeTime(dateString) {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString()
}

function updateTimestamp() {
  props.data.lastModified = new Date().toISOString()
  updateWordCount()
}

function updateWordCount() {
  const text = props.data.comment || ''
  wordCount.value = text.trim() ? text.trim().split(/\s+/).length : 0
}

// Action functions
function toggleSettings() {
  showSettings.value = !showSettings.value
}

function toggleMinimize() {
  props.data.isMinimized = !props.data.isMinimized
  if (!props.data.isMinimized) {
    nextTick(() => {
      textarea.value?.focus()
    })
  }
}

function toggleSticky() {
  props.data.isSticky = !props.data.isSticky
  props.data.zIndex = props.data.isSticky ? 1000 : 0
}

function toggleComplete() {
  props.data.isCompleted = !props.data.isCompleted
  props.data.status = props.data.isCompleted ? 'completed' : 'active'
  updateTimestamp()
}

function toggleArchive() {
  props.data.isArchived = !props.data.isArchived
  updateTimestamp()
}

function selectCategory(category) {
  props.data.category = category
  updateTimestamp()
}

function selectStatus(status) {
  props.data.status = status
  if (status === 'completed') {
    props.data.isCompleted = true
  } else {
    props.data.isCompleted = false
  }
  updateTimestamp()
}

function selectPriority(priority) {
  props.data.priority = priority
  updateTimestamp()
}

function selectColor(colorClass) {
  props.data.colorClass = colorClass
  updateTimestamp()
}

function changeFontSize(newSize) {
  props.data.fontSize = parseInt(newSize)
  updateTimestamp()
}

// Task management
function addTask() {
  const text = newTaskText.value.trim()
  if (text) {
    if (!props.data.tasks) props.data.tasks = []
    props.data.tasks.push({
      text: text,
      completed: false,
      createdAt: new Date().toISOString()
    })
    newTaskText.value = ''
    updateTimestamp()
  }
}

function toggleTask(index) {
  if (props.data.tasks && props.data.tasks[index]) {
    props.data.tasks[index].completed = !props.data.tasks[index].completed
    updateTimestamp()
  }
}

function removeTask(index) {
  if (props.data.tasks) {
    props.data.tasks.splice(index, 1)
    updateTimestamp()
  }
}

// Assignment management
function addAssignee() {
  const person = newAssignee.value.trim()
  if (person && !props.data.assignedTo?.includes(person)) {
    if (!props.data.assignedTo) props.data.assignedTo = []
    props.data.assignedTo.push(person)
    newAssignee.value = ''
    updateTimestamp()
  }
}

function removeAssignee(index) {
  if (props.data.assignedTo) {
    props.data.assignedTo.splice(index, 1)
    updateTimestamp()
  }
}

function clearDeadline() {
  props.data.deadline = null
  updateTimestamp()
}

function setReminder(value) {
  props.data.reminder = value
  updateTimestamp()
}

function deleteComment() {
  if (confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
    emit('delete')
  }
}

// Initialize
updateWordCount()
watch(() => props.data.comment, updateWordCount)

// Resize functionality
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

let initialMouseX = 0, initialMouseY = 0, initialWidth = 0, initialHeight = 0
let initialNodeX = 0, initialNodeY = 0, currentDirection = ''
const MIN_WIDTH = 300, MIN_HEIGHT = 200

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
}

function doResize(event) {
  const dx = event.clientX - initialMouseX
  const dy = event.clientY - initialMouseY

  let newWidth = initialWidth, newHeight = initialHeight
  let newX = initialNodeX, newY = initialNodeY

  switch (currentDirection) {
    case 'e': newWidth = Math.max(MIN_WIDTH, initialWidth + dx); break
    case 's': newHeight = Math.max(MIN_HEIGHT, initialHeight + dy); break
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
}

function getCursor(direction) {
  const cursors = {
    'n': 'ns-resize', 's': 'ns-resize', 'e': 'ew-resize', 'w': 'ew-resize',
    'nw': 'nwse-resize', 'se': 'nwse-resize', 'ne': 'nesw-resize', 'sw': 'nesw-resize'
  }
  return cursors[direction] || 'auto'
}
</script>

<style scoped>
.comment-block {
  position: absolute;
  min-width: 300px;
  min-height: 200px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.02);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  box-sizing: border-box;
  pointer-events: auto;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.comment-block.is-minimized {
  min-height: 50px;
  overflow: visible;
}

.comment-block.is-sticky {
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 0 2px rgba(59, 130, 246, 0.4);
  z-index: 1000 !important;
}

.comment-block.is-overdue {
  border-color: rgba(239, 68, 68, 0.5);
  box-shadow: 0 20px 40px rgba(239, 68, 68, 0.2);
}

.comment-block.has-urgent-tasks {
  animation: urgentPulse 2s ease-in-out infinite;
}

.comment-block.is-archived {
  opacity: 0.6;
  filter: grayscale(0.8);
}

@keyframes urgentPulse {
  0%, 100% {
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  }
  50% {
    box-shadow: 0 20px 40px rgba(239, 68, 68, 0.3);
  }
}

/* Header */
.comment-drag-handle {
  padding: 1rem;
  background: rgba(255, 255, 255, 0.04);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  cursor: grab;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  min-height: 50px;
}

.header-left {
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 0;
  gap: 0.75rem;
}

.category-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.category-indicator.idea { background: rgba(251, 191, 36, 0.15); color: rgba(252, 211, 77, 1); }
.category-indicator.bug { background: rgba(239, 68, 68, 0.15); color: rgba(248, 113, 113, 1); }
.category-indicator.task { background: rgba(59, 130, 246, 0.15); color: rgba(147, 197, 253, 1); }
.category-indicator.decision { background: rgba(139, 92, 246, 0.15); color: rgba(196, 181, 253, 1); }
.category-indicator.documentation { background: rgba(16, 185, 129, 0.15); color: rgba(110, 231, 183, 1); }
.category-indicator.warning { background: rgba(245, 158, 11, 0.15); color: rgba(252, 211, 77, 1); }
.category-indicator.bookmark { background: rgba(236, 72, 153, 0.15); color: rgba(251, 207, 232, 1); }
.category-indicator.feature { background: rgba(168, 85, 247, 0.15); color: rgba(196, 181, 253, 1); }
.category-indicator.feedback { background: rgba(244, 63, 94, 0.15); color: rgba(251, 113, 133, 1); }
.category-indicator.general { background: rgba(107, 114, 128, 0.15); color: rgba(209, 213, 219, 1); }

.comment-title-input {
  color: rgba(255, 255, 255, 0.95);
  font-weight: 600;
  font-size: 1rem;
  background-color: transparent;
  outline: none;
  border: none;
  pointer-events: auto;
  flex: 1;
  min-width: 0;
  padding: 0.5rem;
  border-radius: 8px;
  transition: background-color 0.2s ease;
}

.comment-title-input:focus {
  background-color: rgba(255, 255, 255, 0.05);
}

.header-badges {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-left: 0.5rem;
}

.progress-badge, .deadline-badge, .assignee-badge {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 500;
  backdrop-filter: blur(8px);
}

.progress-badge {
  background: rgba(59, 130, 246, 0.15);
  color: rgba(147, 197, 253, 1);
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.deadline-badge {
  background: rgba(16, 185, 129, 0.15);
  color: rgba(110, 231, 183, 1);
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.deadline-badge.due-soon {
  background: rgba(245, 158, 11, 0.15);
  color: rgba(252, 211, 77, 1);
  border: 1px solid rgba(245, 158, 11, 0.2);
}

.deadline-badge.overdue {
  background: rgba(239, 68, 68, 0.15);
  color: rgba(248, 113, 113, 1);
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.assignee-badge {
  background: rgba(139, 92, 246, 0.15);
  color: rgba(196, 181, 253, 1);
  border: 1px solid rgba(139, 92, 246, 0.2);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-left: 1rem;
}

.status-indicator {
  display: flex;
  align-items: center;
  padding: 0.375rem;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.status-indicator.active { color: rgba(34, 197, 94, 0.8); }
.status-indicator.paused { color: rgba(251, 191, 36, 0.8); }
.status-indicator.completed { color: rgba(59, 130, 246, 0.8); }
.status-indicator.cancelled { color: rgba(239, 68, 68, 0.8); }

.priority-indicator {
  display: flex;
  align-items: center;
  padding: 0.375rem;
  border-radius: 8px;
}

.priority-indicator.priority-low { color: rgba(34, 197, 94, 0.8); }
.priority-indicator.priority-high { color: rgba(251, 191, 36, 0.8); }
.priority-indicator.priority-urgent { color: rgba(249, 115, 22, 0.8); }
.priority-indicator.priority-critical {
  color: rgba(239, 68, 68, 0.8);
  animation: criticalBlink 1.5s ease-in-out infinite;
}

@keyframes criticalBlink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.action-button {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.6);
  cursor: pointer;
  padding: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: all 0.2s ease;
  position: relative;
}

.action-button:hover {
  background-color: rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.9);
  transform: scale(1.05);
}

.action-button.delete-btn:hover {
  background-color: rgba(239, 68, 68, 0.15);
  color: rgba(248, 113, 113, 1);
}

.completed {
  color: rgba(34, 197, 94, 0.8) !important;
}

.archived {
  color: rgba(107, 114, 128, 0.8) !important;
}

.is-pinned {
  color: rgba(59, 130, 246, 0.8) !important;
  transform: rotate(45deg);
}

/* Settings Panel */
.settings-panel {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  background: rgba(17, 24, 39, 0.98);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6);
  z-index: 1000;
  min-width: 350px;
  max-width: 450px;
  max-height: 70vh;
  overflow: hidden;
  backdrop-filter: blur(20px);
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.settings-tabs {
  display: flex;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.02);
}

.tab-btn {
  flex: 1;
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.6);
  padding: 1rem 0.75rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s ease;
  border-bottom: 2px solid transparent;
}

.tab-btn:hover {
  background-color: rgba(255, 255, 255, 0.05);
  color: rgba(255, 255, 255, 0.8);
}

.tab-btn.active {
  color: rgba(59, 130, 246, 1);
  border-bottom-color: rgba(59, 130, 246, 0.8);
  background-color: rgba(59, 130, 246, 0.05);
}

.settings-content {
  padding: 1.5rem;
  max-height: 50vh;
  overflow-y: auto;
}

.settings-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.setting-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.setting-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

/* Category Options */
.category-options {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

.category-option {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  cursor: pointer;
  padding: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: rgba(255, 255, 255, 0.7);
  transition: all 0.2s ease;
  font-size: 0.875rem;
  text-align: left;
}

.category-option:hover {
  background-color: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.15);
  transform: translateY(-1px);
}

.category-option.active {
  border-color: rgba(59, 130, 246, 0.4);
  background-color: rgba(59, 130, 246, 0.08);
  color: rgba(147, 197, 253, 1);
}

/* Status and Priority Options */
.status-options, .priority-options {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.status-option, .priority-option {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  cursor: pointer;
  padding: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: rgba(255, 255, 255, 0.7);
  transition: all 0.2s ease;
  font-size: 0.875rem;
}

.status-option:hover, .priority-option:hover {
  background-color: rgba(255, 255, 255, 0.08);
  transform: translateX(4px);
}

.status-option.active, .priority-option.active {
  border-color: rgba(59, 130, 246, 0.4);
  background-color: rgba(59, 130, 246, 0.08);
  color: rgba(147, 197, 253, 1);
}

.priority-option.priority-urgent.active {
  border-color: rgba(249, 115, 22, 0.4);
  background-color: rgba(249, 115, 22, 0.08);
  color: rgba(251, 146, 60, 1);
}

.priority-option.priority-critical.active {
  border-color: rgba(239, 68, 68, 0.4);
  background-color: rgba(239, 68, 68, 0.08);
  color: rgba(248, 113, 113, 1);
}

/* Task Management */
.task-input-wrapper {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.task-input {
  flex: 1;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  padding: 0.75rem;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.875rem;
  outline: none;
  transition: all 0.2s ease;
}

.task-input:focus {
  border-color: rgba(59, 130, 246, 0.4);
  background-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.add-task-btn {
  background: rgba(59, 130, 246, 0.15);
  border: 1px solid rgba(59, 130, 246, 0.3);
  border-radius: 10px;
  color: rgba(147, 197, 253, 1);
  cursor: pointer;
  padding: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.add-task-btn:hover:not(:disabled) {
  background-color: rgba(59, 130, 246, 0.25);
  transform: scale(1.05);
}

.add-task-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.tasks-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.task-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  transition: all 0.2s ease;
}

.task-item:hover {
  background-color: rgba(255, 255, 255, 0.06);
}

.task-item.completed {
  opacity: 0.6;
}

.task-checkbox {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.6);
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  transition: all 0.2s ease;
}

.task-checkbox:hover {
  color: rgba(59, 130, 246, 0.8);
  transform: scale(1.1);
}

.task-text {
  flex: 1;
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.875rem;
  outline: none;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.task-text:focus {
  background-color: rgba(255, 255, 255, 0.05);
}

.task-text.completed {
  text-decoration: line-through;
  color: rgba(255, 255, 255, 0.5);
}

.remove-task {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.4);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.remove-task:hover {
  color: rgba(239, 68, 68, 0.8);
  background-color: rgba(239, 68, 68, 0.1);
}

/* Assignment Management */
.assignee-input-wrapper {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.assignee-input {
  flex: 1;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  padding: 0.75rem;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.875rem;
  outline: none;
  transition: all 0.2s ease;
}

.assignee-input:focus {
  border-color: rgba(139, 92, 246, 0.4);
  background-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.add-assignee-btn {
  background: rgba(139, 92, 246, 0.15);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 10px;
  color: rgba(196, 181, 253, 1);
  cursor: pointer;
  padding: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.add-assignee-btn:hover:not(:disabled) {
  background-color: rgba(139, 92, 246, 0.25);
  transform: scale(1.05);
}

.assignees-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.assignee-tag {
  background: rgba(139, 92, 246, 0.15);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 20px;
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  color: rgba(196, 181, 253, 1);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
}

.remove-assignee {
  background: none;
  border: none;
  color: rgba(196, 181, 253, 0.7);
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  border-radius: 50%;
  transition: color 0.2s ease;
}

.remove-assignee:hover {
  color: rgba(248, 113, 113, 1);
}

/* Date and Reminder Controls */
.date-input {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  padding: 0.75rem;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.875rem;
  outline: none;
  width: 100%;
  transition: all 0.2s ease;
}

.date-input:focus {
  border-color: rgba(16, 185, 129, 0.4);
  background-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.clear-btn {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 8px;
  color: rgba(248, 113, 113, 1);
  cursor: pointer;
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
  margin-top: 0.5rem;
  transition: all 0.2s ease;
}

.clear-btn:hover {
  background-color: rgba(239, 68, 68, 0.25);
}

.reminder-options {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.reminder-option {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  cursor: pointer;
  padding: 0.5rem 0.75rem;
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.75rem;
  transition: all 0.2s ease;
}

.reminder-option:hover {
  background-color: rgba(255, 255, 255, 0.08);
}

.reminder-option.active {
  background-color: rgba(251, 191, 36, 0.15);
  border-color: rgba(251, 191, 36, 0.3);
  color: rgba(252, 211, 77, 1);
}

/* Color Options */
.color-options {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.75rem;
}

.color-swatch {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.2s ease;
  position: relative;
}

.color-swatch:hover {
  transform: scale(1.1);
  border-color: rgba(255, 255, 255, 0.3);
}

.color-swatch.active {
  border-color: rgba(255, 255, 255, 0.8);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
}

.color-swatch.theme-blue { background: linear-gradient(135deg, rgba(59, 130, 246, 0.8), rgba(29, 78, 216, 0.8)); }
.color-swatch.theme-purple { background: linear-gradient(135deg, rgba(139, 92, 246, 0.8), rgba(124, 58, 237, 0.8)); }
.color-swatch.theme-green { background: linear-gradient(135deg, rgba(16, 185, 129, 0.8), rgba(5, 150, 105, 0.8)); }
.color-swatch.theme-red { background: linear-gradient(135deg, rgba(239, 68, 68, 0.8), rgba(220, 38, 38, 0.8)); }
.color-swatch.theme-orange { background: linear-gradient(135deg, rgba(249, 115, 22, 0.8), rgba(234, 88, 12, 0.8)); }
.color-swatch.theme-yellow { background: linear-gradient(135deg, rgba(245, 158, 11, 0.8), rgba(217, 119, 6, 0.8)); }
.color-swatch.theme-pink { background: linear-gradient(135deg, rgba(236, 72, 153, 0.8), rgba(219, 39, 119, 0.8)); }
.color-swatch.theme-gray { background: linear-gradient(135deg, rgba(107, 114, 128, 0.8), rgba(75, 85, 99, 0.8)); }

/* Font Size Control */
.font-size-control {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.font-size-slider {
  flex: 1;
  appearance: none;
  height: 6px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  outline: none;
  transition: background 0.2s ease;
}

.font-size-slider::-webkit-slider-thumb {
  appearance: none;
  width: 20px;
  height: 20px;
  background: rgba(59, 130, 246, 0.8);
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  transition: all 0.2s ease;
}

.font-size-slider::-webkit-slider-thumb:hover {
  background: rgba(37, 99, 235, 1);
  transform: scale(1.1);
}

.font-size-display {
  background: rgba(59, 130, 246, 0.15);
  color: rgba(147, 197, 253, 1);
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  border: 1px solid rgba(59, 130, 246, 0.3);
  min-width: 50px;
  text-align: center;
}

/* Display Options */
.display-options {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.checkbox-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.checkbox-option:hover {
  background-color: rgba(255, 255, 255, 0.05);
}

.checkbox-option input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: rgba(59, 130, 246, 0.8);
  cursor: pointer;
}

.checkbox-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
  cursor: pointer;
}

/* Content Area */
.comment-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.progress-bar-container {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, rgba(59, 130, 246, 0.8), rgba(37, 99, 235, 0.8));
  border-radius: 4px;
  transition: width 0.3s ease;
  box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
}

.progress-text {
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.8);
  font-weight: 500;
  min-width: 90px;
  text-align: right;
}

.comment-textarea-wrapper {
  flex: 1;
  padding: 1.5rem;
  overflow: auto;
}

.comment-textarea {
  width: 100%;
  height: 100%;
  background-color: transparent;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.875rem;
  line-height: 1.6;
  outline: none;
  resize: none;
  border: none;
  pointer-events: auto;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  transition: all 0.2s ease;
}

.comment-textarea::placeholder {
  color: rgba(255, 255, 255, 0.4);
  font-style: italic;
}

.comment-textarea:focus {
  color: rgba(255, 255, 255, 1);
}

.comment-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
  font-size: 0.75rem;
  min-height: 50px;
}

.footer-left {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: rgba(255, 255, 255, 0.5);
}

.footer-right {
  color: rgba(255, 255, 255, 0.6);
  font-weight: 500;
}

.timestamp {
  color: rgba(255, 255, 255, 0.4);
  font-size: 0.75rem;
}

.word-count {
  color: rgba(255, 255, 255, 0.6);
  font-weight: 500;
}

.assignees {
  color: rgba(139, 92, 246, 0.8);
  font-weight: 500;
}

/* Theme Styles */
.comment-block.theme-blue {
  background: rgba(59, 130, 246, 0.03);
  border-color: rgba(59, 130, 246, 0.15);
}
.comment-block.theme-blue:hover {
  box-shadow: 0 25px 50px rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.25);
}

.comment-block.theme-purple {
  background: rgba(139, 92, 246, 0.03);
  border-color: rgba(139, 92, 246, 0.15);
}
.comment-block.theme-purple:hover {
  box-shadow: 0 25px 50px rgba(139, 92, 246, 0.2);
  border-color: rgba(139, 92, 246, 0.25);
}

.comment-block.theme-green {
  background: rgba(16, 185, 129, 0.03);
  border-color: rgba(16, 185, 129, 0.15);
}
.comment-block.theme-green:hover {
  box-shadow: 0 25px 50px rgba(16, 185, 129, 0.2);
  border-color: rgba(16, 185, 129, 0.25);
}

.comment-block.theme-red {
  background: rgba(239, 68, 68, 0.03);
  border-color: rgba(239, 68, 68, 0.15);
}
.comment-block.theme-red:hover {
  box-shadow: 0 25px 50px rgba(239, 68, 68, 0.2);
  border-color: rgba(239, 68, 68, 0.25);
}

.comment-block.theme-orange {
  background: rgba(249, 115, 22, 0.03);
  border-color: rgba(249, 115, 22, 0.15);
}
.comment-block.theme-orange:hover {
  box-shadow: 0 25px 50px rgba(249, 115, 22, 0.2);
  border-color: rgba(249, 115, 22, 0.25);
}

.comment-block.theme-yellow {
  background: rgba(245, 158, 11, 0.03);
  border-color: rgba(245, 158, 11, 0.15);
}
.comment-block.theme-yellow:hover {
  box-shadow: 0 25px 50px rgba(245, 158, 11, 0.2);
  border-color: rgba(245, 158, 11, 0.25);
}

.comment-block.theme-pink {
  background: rgba(236, 72, 153, 0.03);
  border-color: rgba(236, 72, 153, 0.15);
}
.comment-block.theme-pink:hover {
  box-shadow: 0 25px 50px rgba(236, 72, 153, 0.2);
  border-color: rgba(236, 72, 153, 0.25);
}

.comment-block.theme-gray {
  background: rgba(107, 114, 128, 0.03);
  border-color: rgba(107, 114, 128, 0.15);
}
.comment-block.theme-gray:hover {
  box-shadow: 0 25px 50px rgba(107, 114, 128, 0.2);
  border-color: rgba(107, 114, 128, 0.25);
}

/* Resize Handles */
.resize-handle {
  position: absolute;
  background-color: rgba(59, 130, 246, 0.6);
  opacity: 0;
  transition: opacity 0.2s ease;
  z-index: 10;
}

.comment-block:hover .resize-handle {
  opacity: 1;
}

.handle-top-left, .handle-top-right, .handle-bottom-left, .handle-bottom-right {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.8);
}

.handle-top-left { top: -7px; left: -7px; cursor: nwse-resize; }
.handle-top-right { top: -7px; right: -7px; cursor: nesw-resize; }
.handle-bottom-left { bottom: -7px; left: -7px; cursor: nesw-resize; }
.handle-bottom-right { bottom: -7px; right: -7px; cursor: nwse-resize; }

.handle-top { width: calc(100% - 28px); height: 8px; top: -4px; left: 14px; cursor: ns-resize; border-radius: 4px; }
.handle-bottom { width: calc(100% - 28px); height: 8px; bottom: -4px; left: 14px; cursor: ns-resize; border-radius: 4px; }
.handle-left { width: 8px; height: calc(100% - 28px); left: -4px; top: 14px; cursor: ew-resize; border-radius: 4px; }
.handle-right { width: 8px; height: calc(100% - 28px); right: -4px; top: 14px; cursor: ew-resize; border-radius: 4px; }

/* Scrollbars */
.settings-content::-webkit-scrollbar,
.comment-textarea-wrapper::-webkit-scrollbar {
  width: 6px;
}

.settings-content::-webkit-scrollbar-track,
.comment-textarea-wrapper::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 3px;
}

.settings-content::-webkit-scrollbar-thumb,
.comment-textarea-wrapper::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

.settings-content::-webkit-scrollbar-thumb:hover,
.comment-textarea-wrapper::-webkit-scrollbar-thumb:hover {
  background-color: rgba(255, 255, 255, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
  .settings-panel {
    right: -1rem;
    left: -1rem;
    min-width: auto;
    max-width: none;
  }

  .category-options {
    grid-template-columns: 1fr;
  }

  .color-options {
    grid-template-columns: repeat(3, 1fr);
  }

  .header-badges {
    display: none;
  }

  .comment-block {
    min-width: 280px;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .comment-block,
  .action-button,
  .category-option,
  .settings-panel {
    transition: none;
    animation: none;
  }

  .comment-block.has-urgent-tasks {
    animation: none;
  }

  .priority-indicator.priority-critical {
    animation: none;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .comment-block {
    background: rgba(0, 0, 0, 0.9);
    border: 2px solid rgba(255, 255, 255, 0.8);
  }

  .settings-panel {
    background: rgba(0, 0, 0, 0.95);
    border: 2px solid rgba(255, 255, 255, 0.8);
  }

  .action-button {
    border: 1px solid rgba(255, 255, 255, 0.5);
  }
}

/* Print styles */
@media print {
  .comment-block {
    background: white !important;
    color: black !important;
    border: 1px solid black !important;
    box-shadow: none !important;
    backdrop-filter: none !important;
  }

  .action-button,
  .settings-panel,
  .resize-handle {
    display: none !important;
  }

  .comment-textarea,
  .comment-title-input,
  .task-text {
    color: black !important;
  }
}
</style>
