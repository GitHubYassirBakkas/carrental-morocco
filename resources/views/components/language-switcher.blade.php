<div class="relative inline-block text-left" x-data="{ open: false }">
    <div>
        <button @click="open = !open" 
                type="button" 
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition">
            <!-- Current Language Flag -->
            @if(app()->getLocale() == 'en')
                <span class="text-lg">🇬🇧</span>
                <span>English</span>
            @elseif(app()->getLocale() == 'fr')
                <span class="text-lg">🇫🇷</span>
                <span>Français</span>
            @else
                <span class="text-lg">🇲🇦</span>
                <span>العربية</span>
            @endif
            
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
    </div>

    <!-- Dropdown -->
    <div x-show="open" 
         @click.away="open = false"
         x-transition
         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50">
        
        <a href="{{ route('language.switch', 'en') }}" 
           class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition {{ app()->getLocale() == 'en' ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
            <span class="text-lg">🇬🇧</span>
            <span class="text-gray-800 dark:text-white">English</span>
        </a>

        <a href="{{ route('language.switch', 'fr') }}" 
           class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition {{ app()->getLocale() == 'fr' ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
            <span class="text-lg">🇫🇷</span>
            <span class="text-gray-800 dark:text-white">Français</span>
        </a>

        <a href="{{ route('language.switch', 'ar') }}" 
           class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition {{ app()->getLocale() == 'ar' ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
            <span class="text-lg">🇲🇦</span>
            <span class="text-gray-800 dark:text-white">العربية</span>
        </a>
    </div>
</div>