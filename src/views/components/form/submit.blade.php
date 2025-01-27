{{-- <button type="submit" class="transition duration-200 bg-black dark:bg-white hover:bg-gray-600 dark:hover:bg-yellow-600 dark:hover:text-white focus:bg-gray-700 dark:focus:bg-gray-100 focus:shadow-sm focus:ring-4 focus:ring-gray-500 dark:focus:ring-black focus:ring-opacity-50 text-white dark:text-black w-full py-2.5 rounded-lg text-sm shadow-sm hover:shadow-md font-semibold text-center inline-block">
    <span class="inline-block mr-2">{{ $name ?? 'Login' }}</span>
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
    class="w-4 h-4 inline-block">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
</svg>
</button> --}}

<button class="transition duration-200 inline-block px-6 py-2 text-xs font-medium leading-6 text-center text-black dark:text-white bg-white dark:bg-black uppercase transition bg-transparent border-2 border-black dark:border-white rounded ripple hover:bg-black hover:text-white hover:border-white
dark:hover:bg-white dark:hover:text-black dark:hover:border-black
focus:outline-none waves-effect w-full">
    <span class="inline-block mr-2">{{ $name ?? 'Login' }}</span>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        class="w-4 h-4 inline-block">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
    </svg>
</button>
