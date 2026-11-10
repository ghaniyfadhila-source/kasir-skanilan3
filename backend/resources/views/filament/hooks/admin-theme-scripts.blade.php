<script>
    // Filament persists the mobile drawer state and can reopen it after a
    // Livewire navigation. Keep the sidebar closed below the desktop breakpoint
    // so phones get full content width on first paint.
    const closeAdminSidebar = () => {
        if (window.innerWidth < 1024) {
            window.Alpine?.store('sidebar')?.close()
        }
    }

    document.addEventListener('alpine:initialized', () => {
        closeAdminSidebar()
        setTimeout(closeAdminSidebar, 250)
    })
    document.addEventListener('livewire:navigated', closeAdminSidebar)
</script>