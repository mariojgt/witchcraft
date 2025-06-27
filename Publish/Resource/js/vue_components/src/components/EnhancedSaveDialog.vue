<template>
    <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-[#111] border border-gray-700 rounded-xl w-[500px] max-h-[90vh] overflow-hidden mx-4">
            <!-- Header - Fixed -->
            <div class="p-6 border-b border-gray-800 flex-shrink-0">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-white">
                        {{ isUpdate ? 'Update Workflow' : 'Save Workflow' }}
                        <span v-if="hasChanges" class="ml-2 px-2 py-1 bg-yellow-600 text-yellow-100 text-xs rounded-full">
                            Unsaved Changes
                        </span>
                    </h2>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-white">
                        <XIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto max-h-[60vh]">
                <div class="p-6 space-y-4">
                    <!-- Version Info for Updates -->
                    <div v-if="isUpdate" class="p-3 bg-blue-900/20 border border-blue-700/30 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <HistoryIcon class="w-4 h-4 text-blue-400" />
                            <span class="text-sm font-medium text-blue-300">Version Information</span>
                        </div>
                        <p class="text-xs text-blue-200">
                            Current: v{{ currentVersion }} •
                            Saving will create v{{ currentVersion + 1 }}
                        </p>
                    </div>

                    <!-- Name Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Workflow Name *
                        </label>
                        <input
                            v-model="formData.name"
                            type="text"
                            placeholder="Enter workflow name"
                            class="w-full px-3 py-2 bg-[#1a1a1a] border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                            :class="{ 'border-red-500': errors.name }"
                            @input="clearError('name')"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-400">{{ errors.name }}</p>
                    </div>

                    <!-- Description Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea
                            v-model="formData.description"
                            placeholder="Describe what this workflow does..."
                            rows="2"
                            class="w-full px-3 py-2 bg-[#1a1a1a] border border-gray-700 rounded-lg text-white placeholder-gray-400 resize-none focus:border-blue-500 focus:outline-none"
                        ></textarea>
                    </div>

                    <!-- Version Notes (for updates) -->
                    <div v-if="isUpdate && hasChanges">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Version Notes
                            <span class="text-gray-500 text-xs ml-1">(what changed?)</span>
                        </label>
                        <textarea
                            v-model="formData.version_notes"
                            placeholder="Describe the changes made..."
                            rows="2"
                            class="w-full px-3 py-2 bg-[#1a1a1a] border border-gray-700 rounded-lg text-white placeholder-gray-400 resize-none focus:border-blue-500 focus:outline-none"
                        ></textarea>
                    </div>

                    <!-- Category and Icon Row -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Category Field -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Category
                            </label>
                            <input
                                v-model="formData.category"
                                type="text"
                                placeholder="General"
                                list="categories"
                                class="w-full px-3 py-2 bg-[#1a1a1a] border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                            />
                            <datalist id="categories">
                                <option v-for="category in predefinedCategories" :key="category" :value="category" />
                            </datalist>
                        </div>

                        <!-- Icon Field -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Icon
                            </label>
                            <select
                                v-model="formData.icon"
                                class="w-full px-3 py-2 bg-[#1a1a1a] border border-gray-700 rounded-lg text-white focus:border-blue-500 focus:outline-none"
                            >
                                <option v-for="(label, iconKey) in availableIcons" :key="iconKey" :value="iconKey">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Protection Settings -->
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-300">
                            Settings
                        </label>

                        <div class="flex items-center justify-between p-3 bg-[#1a1a1a] border border-gray-700 rounded-lg">
                            <div class="flex items-center gap-3">
                                <ShieldIcon class="w-4 h-4 text-gray-400" />
                                <div>
                                    <p class="text-white text-sm font-medium">Allow Deletion</p>
                                    <p class="text-gray-400 text-xs">Protect from accidental deletion</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    v-model="formData.is_deletable"
                                    type="checkbox"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Trigger Code Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Trigger Code
                            <span class="text-gray-500 text-xs ml-1">(auto-generated)</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="formData.trigger_code"
                                type="text"
                                placeholder="AUTO_GENERATED_CODE"
                                class="w-full px-3 py-2 pr-10 bg-[#1a1a1a] border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none font-mono text-sm"
                                :class="{ 'border-red-500': errors.trigger_code }"
                                @input="clearError('trigger_code')"
                            />
                            <button
                                v-if="formData.trigger_code || generatedTriggerCode"
                                @click="copyTriggerCode"
                                class="absolute right-2 top-2 p-1 text-gray-400 hover:text-blue-400 rounded"
                                title="Copy trigger code"
                            >
                                <CopyIcon class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="errors.trigger_code" class="mt-1 text-sm text-red-400">{{ errors.trigger_code }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            For programmatic execution
                        </p>
                    </div>

                    <!-- Compact Preview Section -->
                    <div v-if="formData.name" class="p-3 bg-[#1a1a1a] border border-gray-700 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-300 mb-2">Preview</h4>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                                <component
                                    :is="getIcon(formData.icon || 'WorkflowIcon')"
                                    class="w-4 h-4 text-white"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-medium text-white text-sm truncate">{{ formData.name }}</h3>
                                <div class="flex items-center gap-1 mt-1 flex-wrap">
                                    <span v-if="formData.category"
                                          class="px-1.5 py-0.5 bg-gray-800 text-gray-300 text-xs rounded">
                                        {{ formData.category }}
                                    </span>
                                    <span v-if="!formData.is_deletable"
                                          class="px-1.5 py-0.5 bg-red-900/30 text-red-400 text-xs rounded flex items-center gap-1">
                                        <ShieldIcon class="w-2 h-2" />
                                        Protected
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer - Fixed -->
            <div class="p-6 border-t border-gray-800 flex justify-end gap-3 flex-shrink-0">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors"
                >
                    Cancel
                </button>
                <button
                    @click="handleSave"
                    :disabled="!formData.name || loading"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <div v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    {{ isUpdate ? (hasChanges ? `Save as v${currentVersion + 1}` : 'Update') : 'Save' }}
                </button>
            </div>
        </div>

        <!-- Toast Notification -->
        <div
            v-if="showToast"
            class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg transition-all duration-300"
        >
            <div class="flex items-center gap-2">
                <component :is="getIcon('CheckIcon')" class="w-4 h-4" />
                {{ toastMessage }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import * as LucideIcons from 'lucide-vue-next';
import { XIcon, CopyIcon, HistoryIcon, ShieldIcon } from 'lucide-vue-next';

// Props
const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    initialData: {
        type: Object,
        default: () => ({})
    },
    isUpdate: {
        type: Boolean,
        default: false
    },
    hasChanges: {
        type: Boolean,
        default: false
    },
    currentVersion: {
        type: Number,
        default: 1
    },
    availableIcons: {
        type: Object,
        default: () => ({
            'WorkflowIcon': 'Workflow',
            'DatabaseIcon': 'Database',
            'ApiIcon': 'API',
            'CpuIcon': 'Process',
            'BellIcon': 'Notification',
            'ClockIcon': 'Schedule',
            'GlobeIcon': 'Web',
            'FileIcon': 'File',
            'MailIcon': 'Email',
            'SettingsIcon': 'Settings',
            'ZapIcon': 'Action',
            'FilterIcon': 'Filter',
            'RepeatIcon': 'Loop',
            'BranchesIcon': 'Branch',
            'CheckCircleIcon': 'Validation',
            'AlertTriangleIcon': 'Warning',
            'InfoIcon': 'Information',
            'LockIcon': 'Security',
            'KeyIcon': 'Authentication',
            'CloudIcon': 'Cloud'
        })
    }
});

// Emits
const emit = defineEmits(['close', 'save']);

// State
const loading = ref(false);
const errors = ref({});
const showToast = ref(false);
const toastMessage = ref('');

const formData = ref({
    name: '',
    description: '',
    category: 'General',
    icon: 'WorkflowIcon',
    trigger_code: '',
    is_deletable: true,
    version_notes: ''
});

const predefinedCategories = [
    'General',
    'API',
    'Database',
    'Process',
    'Notification',
    'Schedule',
    'Data',
    'Integration',
    'Automation',
    'Workflow'
];

// Computed
const generatedTriggerCode = computed(() => {
    if (!formData.value.name || formData.value.trigger_code) return '';

    const name = formData.value.name.replace(/[^a-zA-Z0-9]/g, '_').toUpperCase();
    return `FLOW_${name}`;
});

// Methods
function getIcon(iconName) {
    const iconMap = {
        'WorkflowIcon': 'GitBranchIcon',
        'DatabaseIcon': 'DatabaseIcon',
        'ApiIcon': 'GlobeIcon',
        'CpuIcon': 'CpuIcon',
        'BellIcon': 'BellIcon',
        'ClockIcon': 'ClockIcon',
        'GlobeIcon': 'GlobeIcon',
        'FileIcon': 'FileIcon',
        'MailIcon': 'MailIcon',
        'SettingsIcon': 'SettingsIcon',
        'ZapIcon': 'ZapIcon',
        'FilterIcon': 'FilterIcon',
        'RepeatIcon': 'RepeatIcon',
        'BranchesIcon': 'GitBranchIcon',
        'CheckCircleIcon': 'CheckCircleIcon',
        'AlertTriangleIcon': 'AlertTriangleIcon',
        'InfoIcon': 'InfoIcon',
        'LockIcon': 'LockIcon',
        'KeyIcon': 'KeyIcon',
        'CloudIcon': 'CloudIcon',
        'CheckIcon': 'CheckIcon',
        'HistoryIcon': 'HistoryIcon',
        'ShieldIcon': 'ShieldIcon'
    };

    const mappedIcon = iconMap[iconName] || iconName;
    return LucideIcons[mappedIcon] || LucideIcons.WorkflowIcon || LucideIcons.BoxIcon;
}

function clearError(field) {
    if (errors.value[field]) {
        delete errors.value[field];
    }
}

function copyTriggerCode() {
    const code = formData.value.trigger_code || generatedTriggerCode.value;
    if (!code) return;

    navigator.clipboard.writeText(code).then(() => {
        showToast.value = true;
        toastMessage.value = 'Trigger code copied!';
        setTimeout(() => {
            showToast.value = false;
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}

async function handleSave() {
    // Clear previous errors
    errors.value = {};

    // Basic validation
    if (!formData.value.name.trim()) {
        errors.value.name = 'Workflow name is required';
        return;
    }

    loading.value = true;

    try {
        // Prepare data for saving
        const saveData = {
            ...formData.value,
            name: formData.value.name.trim(),
            description: formData.value.description?.trim() || '',
            category: formData.value.category || 'General',
            icon: formData.value.icon || 'WorkflowIcon',
            trigger_code: formData.value.trigger_code?.trim() || null,
            is_deletable: formData.value.is_deletable,
            version_notes: formData.value.version_notes?.trim() || null
        };

        emit('save', saveData);
    } catch (error) {
        console.error('Save error:', error);
        errors.value.general = 'Failed to save workflow';
    } finally {
        loading.value = false;
    }
}

// Watch for initial data changes
watch(() => props.initialData, (newData) => {
    if (newData && Object.keys(newData).length > 0) {
        formData.value = {
            name: newData.name || '',
            description: newData.description || '',
            category: newData.category || 'General',
            icon: newData.icon || 'WorkflowIcon',
            trigger_code: newData.trigger_code || '',
            is_deletable: newData.is_deletable !== undefined ? newData.is_deletable : true,
            version_notes: ''
        };
    }
}, { immediate: true, deep: true });

// Reset form when dialog closes
watch(() => props.show, (show) => {
    if (!show) {
        errors.value = {};
        if (!props.isUpdate) {
            formData.value = {
                name: '',
                description: '',
                category: 'General',
                icon: 'WorkflowIcon',
                trigger_code: '',
                is_deletable: true,
                version_notes: ''
            };
        }
    }
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
