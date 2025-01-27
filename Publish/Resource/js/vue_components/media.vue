<!-- App.vue -->
<script setup>
import { ref, onMounted } from 'vue';
import {
 Folder, Home, File, ChevronRight, Plus, Download, Trash2,
 LayoutGrid, List, Search, Settings, Moon, Sun, FolderOpen
} from 'lucide-vue-next';
import axios from 'axios';
import addFolder from './add-folder.vue';
import sidebar from './side-bar.vue';
import pathmaker from './breadcrumb.vue';
import mediaContent from './media-content.vue';

const isDark = ref(false);
const searchQuery = ref('');
const sidebarWidth = ref(240);
const showPreview = ref(true);
const selectedFile = ref(null);
const selectedView = ref('grid');

let folders = $ref([]);
let breadcrumb = $ref([]);
let folder_target = $ref(null);
let folder_created_at = $ref('');

const toggleTheme = () => {
 isDark.value = !isDark.value;
 document.documentElement.classList.toggle('dark');
};

const loadFolder = async (id) => {
 try {
   const response = await axios.get(`/folder/load/${id}`);
   folders = response.data.children;
   breadcrumb = response.data.parent;
   folder_created_at = response.data.folder_info.created_at;
 } catch (error) {
   console.error('Error loading folder:', error);
 }
};

const loadParents = async () => {
 try {
   const response = await axios.get('folder/list');
   folders = response.data.data;
   breadcrumb = [];
   folder_target = null;
 } catch (error) {
   console.error('Error loading parents:', error);
 }
};

const reloadFolder = async (value) => {
 value?.id ? loadFolder(value.id) : loadParents();
};

const loadSelectedFolder = async (item) => {
 loadFolder(item.id);
 folder_target = item.id;
};

onMounted(() => {
 loadParents();
});
</script>

<template>
 <div :class="['h-screen flex flex-col bg-white dark:bg-gray-900', isDark ? 'dark' : '']">
   <!-- Toolbar -->
   <div class="h-12 bg-gray-100/80 dark:bg-gray-800/80 backdrop-blur-lg border-b
               border-gray-200 dark:border-gray-700 flex items-center px-4 gap-4">
     <!-- Navigation -->
     <div class="flex items-center gap-2">
       <button class="p-1.5 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700
                     transition-colors">
         <ChevronRight class="w-4 h-4 rotate-180" />
       </button>
       <button class="p-1.5 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700
                     transition-colors">
         <ChevronRight class="w-4 h-4" />
       </button>
     </div>

     <!-- Search -->
     <div class="flex-1 max-w-xl">
       <div class="relative">
         <Search class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" />
         <input v-model="searchQuery"
                type="text"
                placeholder="Search"
                class="w-full h-9 pl-9 pr-4 rounded-lg bg-gray-200 dark:bg-gray-700
                       text-gray-900 dark:text-gray-100 placeholder-gray-500
                       focus:outline-none focus:ring-2 focus:ring-blue-500" />
       </div>
     </div>

     <!-- View Controls -->
     <div class="flex items-center gap-2">
       <button class="p-1.5 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"
               @click="selectedView = 'grid'"
               :class="{ 'bg-gray-200 dark:bg-gray-700': selectedView === 'grid' }">
         <LayoutGrid class="w-4 h-4" />
       </button>
       <button class="p-1.5 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"
               @click="selectedView = 'list'"
               :class="{ 'bg-gray-200 dark:bg-gray-700': selectedView === 'list' }">
         <List class="w-4 h-4" />
       </button>
       <div class="w-px h-4 bg-gray-300 dark:bg-gray-600"></div>
       <button class="p-1.5 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"
               @click="toggleTheme">
         <component :is="isDark ? Sun : Moon" class="w-4 h-4" />
       </button>
       <button class="p-1.5 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
         <Settings class="w-4 h-4" />
       </button>
     </div>
   </div>

   <div class="flex-1 flex overflow-hidden">
     <!-- Sidebar -->
     <div :style="{ width: `${sidebarWidth}px` }"
          class="flex flex-col border-r border-gray-200 dark:border-gray-700">
       <!-- Favorites -->
       <div class="p-2 space-y-1">
         <div class="text-xs font-medium text-gray-500 dark:text-gray-400 px-2 py-1">
           Favorites
         </div>
         <button @click="loadParents"
                 class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg
                        hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
           <Home class="w-4 h-4 text-blue-500" />
           <span class="text-sm">Home</span>
         </button>
         <button class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg
                        hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
           <FolderOpen class="w-4 h-4 text-blue-500" />
           <span class="text-sm">Recent</span>
         </button>
       </div>

       <!-- Folders -->
       <div class="flex-1 overflow-y-auto p-2">
         <div class="text-xs font-medium text-gray-500 dark:text-gray-400 px-2 py-1">
           Folders
         </div>
         <sidebar v-for="(item, index) in folders"
                 :key="index"
                 :item="item"
                 @load_folder="reloadFolder"
                 @load_selected_folder="loadSelectedFolder" />
       </div>

       <!-- Add Folder -->
       <div class="p-2 border-t border-gray-200 dark:border-gray-700">
         <add-folder :parent_id="folder_target"
                    @load_folder="reloadFolder" />
       </div>
     </div>

     <!-- Main Content -->
     <div class="flex-1 flex flex-col min-w-0">
       <pathmaker @load_root="loadParents"
                 @load_selected_folder="loadSelectedFolder"
                 :breadcrumb="breadcrumb" />

       <media-content :parent_id="folder_target"
                     :view="selectedView"
                     @select-file="selectedFile = $event"
                     class="flex-1">
         <template #created>
           {{ folder_created_at }}
         </template>
       </media-content>
     </div>

     <!-- Preview Panel -->
     <div v-if="showPreview"
          class="w-80 border-l border-gray-200 dark:border-gray-700 bg-gray-50
                 dark:bg-gray-800/50">
       <div v-if="selectedFile" class="p-4">
         <div class="aspect-square mb-4 bg-white dark:bg-gray-800 rounded-lg
                     overflow-hidden">
           <img :src="selectedFile.url"
                :alt="selectedFile.name"
                class="w-full h-full object-cover" />
         </div>
         <div class="space-y-4">
           <div>
             <h3 class="font-medium">{{ selectedFile.name }}</h3>
             <p class="text-sm text-gray-500">{{ selectedFile.size }}</p>
           </div>
           <div class="flex gap-2">
             <button class="btn btn-primary btn-sm flex-1">Download</button>
             <button class="btn btn-outline btn-sm">Share</button>
           </div>
         </div>
       </div>
       <div v-else class="p-4 text-center text-gray-500">
         <File class="w-8 h-8 mx-auto mb-2" />
         <p class="text-sm">Select a file to preview</p>
       </div>
     </div>
   </div>
 </div>
</template>

<style>
:root {
 --background: 255 255 255;
 --foreground: 15 23 42;
}

:root.dark {
 --background: 15 23 42;
 --foreground: 241 245 249;
}

body {
 background-color: rgb(var(--background));
 color: rgb(var(--foreground));
}

.btn {
 @apply px-4 py-2 rounded-lg transition-colors;
}

.btn-primary {
 @apply bg-blue-500 text-white hover:bg-blue-600;
}

.btn-outline {
 @apply border border-gray-300 dark:border-gray-600
        hover:bg-gray-100 dark:hover:bg-gray-700;
}

.btn-sm {
 @apply px-3 py-1.5 text-sm;
}
</style>
