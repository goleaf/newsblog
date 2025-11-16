{{--
    Header Actions Component
    
    Contains search, dark mode toggle, notifications, user menu, and mobile menu button.
--}}

<div class="flex items-center gap-3">
    {{-- Search Button --}}
    <x-navigation.search-button />

    {{-- Dark Mode Toggle --}}
    <x-ui.dark-mode-toggle />

    {{-- Notifications (Authenticated Users Only) --}}
    @auth
        <div class="hidden lg:block">
            <x-notifications.dropdown :unreadCount="auth()->user()->notifications()->unread()->count()" />
        </div>
    @endauth

    {{-- User Menu (Desktop) --}}
    <div class="hidden lg:block">
        <x-navigation.user-menu />
    </div>

    {{-- Mobile Menu Button --}}
    <x-navigation.mobile-menu-button />
</div>
