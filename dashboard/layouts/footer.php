        </main>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

// Close sidebar when clicking on a link (mobile only)
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth < 768) {
        const sidebarLinks = document.querySelectorAll('#sidebar nav a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                toggleSidebar();
            });
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.add('hidden');
        } else {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.add('-translate-x-full');
        }
    });
});
</script>
</body>
</html>


