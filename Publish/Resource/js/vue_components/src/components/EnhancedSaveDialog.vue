<template>
    <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-[#111] border border-gray-700 rounded-xl w-[500px] mx-4">
            <div class="p-6 border-b border-gray-800">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-white">
                        {{ isUpdate ? 'Update Workflow' : 'Save Workflow' }}
                    </h2>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-white">
                        <XIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-4">
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
                        rows="3"
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
                        <div class="relative">
                            <input
                                v-model="formData.category"
                                type="text"
                                placeholder="e.g. API, Database, Process"
                                list="categories"
                                class="w-full px-3 py-2 bg-[#1a1a1a] border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                            />
                            <datalist id="categories">
                                <option v-for="category in predefinedCategories" :key="category" :value="category" />
                            </datalist>
                        </div>
                    </div>

                    <!-- Icon Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Icon
                        </label>
                        <div class="relative">
                            <input
                                v-model="formData.icon"
                                type="text"
                                placeholder="Lucide icon name"
                                class="w-full px-3 py-2 pl-10 bg-[#1a1a1a] border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                            />
                            <div class="absolute left-3 top-3">
                                <component
                                    :is="getIcon(formData.icon || 'WorkflowIcon')"
                                    class="w-4 h-4 text-gray-400"
                                />
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Visit <a href="https://lucide.dev" target="_blank" class="text-blue-400 hover:underline">lucide.dev</a> for icon names
                        </p>
                    </div>
                </div>

                <!-- Trigger Code Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Trigger Code
                        <span class="text-gray-500 text-xs ml-1">(auto-generated if empty)</span>
                    </label>
                    <div class="relative">
                        <input
                            v-model="formData.trigger_code"
                            type="text"
                            placeholder="AUTO_GENERATED_CODE"
                            class="w-full px-3 py-2 pr-20 bg-[#1a1a1a] border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none font-mono text-sm"
                            :class="{ 'border-red-500': errors.trigger_code }"
                            @input="clearError('trigger_code')"
                        />
                        <button
                            v-if="formData.trigger_code"
                            @click="copyTriggerCode"
                            class="absolute right-2 top-2 p-1 text-gray-400 hover:text-blue-400 rounded"
                            title="Copy trigger code"
                        >
                            <CopyIcon class="w-4 h-4" />
                        </button>
                    </div>
                    <p v-if="errors.trigger_code" class="mt-1 text-sm text-red-400">{{ errors.trigger_code }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        Use this code to trigger the workflow programmatically
                    </p>
                </div>

                <!-- Preview Section -->
                <div v-if="formData.name" class="mt-6 p-4 bg-[#1a1a1a] border border-gray-700 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-300 mb-2">Preview</h4>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <component
                                :is="getIcon(formData.icon || 'WorkflowIcon')"
                                class="w-6 h-6 text-white"
                            />
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium text-white">{{ formData.name }}</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span v-if="formData.category"
                                      class="px-2 py-0.5 bg-gray-800 text-gray-300 text-xs rounded-full">
                                    {{ formData.category }}
                                </span>
                                <span v-if="formData.trigger_code || generatedTriggerCode"
                                      class="px-2 py-0.5 bg-green-900/30 text-green-400 text-xs rounded-full font-mono">
                                    {{ formData.trigger_code || generatedTriggerCode }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <p v-if="formData.description" class="text-sm text-gray-400 mt-2">
                        {{ formData.description }}
                    </p>
                </div>
            </div>

            <div class="p-6 border-t border-gray-800 flex justify-end gap-3">
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
                    {{ isUpdate ? 'Update' : 'Save' }}
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
import { XIcon, CopyIcon } from 'lucide-vue-next';

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
    trigger_code: ''
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
        'ProcessIcon': 'CpuIcon',
        'NotificationIcon': 'BellIcon',
        'ScheduleIcon': 'ClockIcon',
        'CheckIcon': 'CheckIcon'
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
            trigger_code: formData.value.trigger_code?.trim() || null
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
            trigger_code: newData.trigger_code || ''
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
                trigger_code: ''
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
