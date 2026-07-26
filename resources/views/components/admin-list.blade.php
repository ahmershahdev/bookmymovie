@props([
    'items' => [], 
    'action' => 'Manage',
    'emptyText' => 'No records found.'
])

<!-- 
  Upgraded to a semantic <ul> list. 
  Added a subtle backdrop blur and refined the border/shadow styling. 
-->
<ul class="mt-6 divide-y divide-white/5 overflow-hidden rounded-xl border border-white/10 bg-gray-950 shadow-xl ring-1 ring-black/50 backdrop-blur-sm">
    @forelse($items as $item)
        <!-- 
          Added 'group' for row-level hover effects. 
          Smooth transition on background color to indicate interactivity. 
        -->
        <li class="group flex flex-col gap-4 p-4 transition-colors duration-200 hover:bg-white/[0.02] sm:flex-row sm:items-center sm:justify-between">
            
            <!-- Typography refined: font-black is usually too harsh, font-medium looks cleaner -->
            <div class="flex items-center gap-3">
                <p class="text-sm font-medium text-gray-300 transition-colors group-hover:text-white">
                    {{ $item }}
                </p>
            </div>

            <!-- 
              Button upgraded: 
              - Added inline-flex and active:scale-95 for a tactile click feel
              - Added proper focus rings for accessibility
              - Smoothed out the hover transition colors 
            -->
            <button 
                type="button"
                class="inline-flex items-center justify-center rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-gray-300 transition-all duration-200 ease-in-out hover:border-red-500/50 hover:bg-red-500/10 hover:text-red-400 focus:outline-none focus:ring-2 focus:ring-red-500/40 focus:ring-offset-2 focus:ring-offset-gray-950 active:scale-95"
            >
                {{ $action }}
            </button>
        </li>
    @empty
        <!-- 
          Premium empty state: 
          Added generous padding, a muted hero icon, and better text hierarchy. 
        -->
        <li class="flex flex-col items-center justify-center px-4 py-12 text-center">
            <svg class="mb-3 h-10 w-10 text-gray-600/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
            <p class="text-sm font-medium text-gray-400">{{ $emptyText }}</p>
        </li>
    @endforelse
</ul>