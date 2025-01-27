<a href="{{ $route }}" {{ $new ?? 'false' == "true" ? 'target="_blank"' : '' }} class="transition duration-200 inline-block px-6 py-2 text-xs font-medium leading-6 text-center text-black dark:text-white bg-white dark:bg-black uppercase transition bg-transparent border-2 border-black dark:border-white rounded ripple hover:bg-black hover:text-white hover:border-white
dark:hover:bg-white dark:hover:text-black dark:hover:border-black
focus:outline-none waves-effect">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        class="w-4 h-4 inline-block align-text-top">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
    </svg>
    <span class="inline-block ml-1">{{ $name }}</span>
</a>
