<x-user-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        @include('profile/partials.navuser')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            {{ __("Bienvenido al Panel de Usuario") }}
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-user-layout>
