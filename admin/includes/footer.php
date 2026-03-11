<!-- BeriBot AI Assistant - Chatwoot Style -->
<button id="beriBotButton" 
    class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-br from-primary-600 to-secondary-500 text-white rounded-full shadow-2xl flex items-center justify-center ripple-container floating z-40 group hover:scale-110 transition-transform duration-300">
    <div class="relative">
        <i class="fas fa-robot text-lg"></i>
        <div class="absolute -top-1 -right-1 w-4 h-4 bg-secondary-500 rounded-full border-2 border-white dark:border-slate-900"></div>
    </div>
    <div class="absolute inset-0 rounded-full border-4 border-primary-500/20 animate-ping"></div>
</button>

<!-- BeriBot AI Chatbot Modal - Chatwoot Inspired -->
<div id="beriBotModal" 
    class="fixed bottom-24 right-6 w-96 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl hidden z-50 animate-slide-up border border-primary-200 dark:border-primary-700 overflow-hidden">
    
    <!-- Header -->
    <div class="bg-gradient-to-r from-primary-600 to-secondary-500 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-robot text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-white">BeriBot AI Assistant</h3>
                    <p class="text-xs text-white/80">Online · Typically replies instantly</p>
                </div>
            </div>
            <button id="beriBotClose" class="p-2 hover:bg-white/10 rounded-xl transition-colors">
                <i class="fas fa-times text-white"></i>
            </button>
        </div>
    </div>

    <!-- Messages Area -->
    <div id="beriBotMessages" class="h-96 p-4 overflow-y-auto hide-scrollbar bg-slate-50 dark:bg-slate-800/50">
        <!-- Welcome Message -->
        <div class="flex items-start space-x-2 mb-4">
            <div class="w-8 h-8 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-robot text-white text-sm"></i>
            </div>
            <div class="flex-1">
                <div class="bg-white dark:bg-slate-700 rounded-2xl rounded-tl-none p-3 shadow-sm">
                    <p class="text-sm text-slate-800 dark:text-slate-200">
                        👋 Hello! I'm BeriBot, your AI assistant for BeriBakes Bakery. I can help you with:
                    </p>
                    <ul class="text-xs text-slate-600 dark:text-slate-400 mt-2 space-y-1 list-disc list-inside">
                        <li>Product management</li>
                        <li>Order tracking</li>
                        <li>Sales reports</li>
                        <li>Inventory alerts</li>
                        <li>Customer insights</li>
                    </ul>
                </div>
                <span class="text-2xs text-slate-500 dark:text-slate-400 mt-1 ml-1">Just now</span>
            </div>
        </div>

        <!-- Feature announcement message -->
        <div class="flex items-start space-x-2 mb-4">
            <div class="w-8 h-8 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-robot text-white text-sm"></i>
            </div>
            <div class="flex-1">
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl rounded-tl-none p-3 shadow-sm">
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        ✨ Full AI capabilities coming in the next update! 
                    </p>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        Soon you'll be able to chat with me about all bakery operations.
                    </p>
                </div>
                <span class="text-2xs text-slate-500 dark:text-slate-400 mt-1 ml-1">Just now</span>
            </div>
        </div>
    </div>

    <!-- Input Area -->
    <div class="border-t border-primary-200 dark:border-primary-700 p-4 bg-white dark:bg-slate-800">
        <div class="flex items-center space-x-2">
            <div class="flex-1 relative">
                <input type="text" id="beriBotInput" 
                    placeholder="Type your message..." 
                    class="w-full border border-primary-300 dark:border-primary-600 bg-white dark:bg-slate-900 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all opacity-50"
                    disabled>
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                    <i class="fas fa-lock text-xs text-slate-400"></i>
                </div>
            </div>
            <button id="sendBeriBotMessage" 
                class="px-4 py-3 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl opacity-50 cursor-not-allowed flex items-center justify-center">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        <p class="text-center text-2xs text-slate-500 dark:text-slate-400 mt-3">
            🚀 AI Assistant will be fully available in the next update
        </p>
    </div>

    <!-- Typing Indicator (Hidden by default) -->
    <div id="typingIndicator" class="hidden px-4 pb-4">
        <div class="flex items-center space-x-2">
            <div class="w-8 h-8 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-xl flex items-center justify-center">
                <i class="fas fa-robot text-white text-sm"></i>
            </div>
            <div class="bg-slate-100 dark:bg-slate-700 rounded-2xl p-3">
                <div class="flex space-x-1">
                    <div class="w-2 h-2 bg-primary-600 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                    <div class="w-2 h-2 bg-primary-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    <div class="w-2 h-2 bg-primary-600 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hide loading screen
        setTimeout(() => {
            const loadingScreen = document.getElementById('loadingScreen');
            if (loadingScreen) {
                loadingScreen.style.opacity = '0';
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                }, 300);
            }
        }, 800);

        // Sidebar Toggle - Fix the margin/spacing
        const sidebar = document.getElementById('sidebar');
        const toggleSidebar = document.getElementById('toggleSidebar');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.querySelector('.flex-1');

        if (toggleSidebar && sidebar) {
            toggleSidebar.addEventListener('click', () => {
                sidebar.classList.remove('-translate-x-full');
                if (sidebarOverlay) sidebarOverlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            });
        }

        if (closeSidebar && sidebar) {
            closeSidebar.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                if (sidebarOverlay) sidebarOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            });
        }

        if (sidebarOverlay && sidebar) {
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            });
        }

        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const themeIcon = themeToggle.querySelector('i');
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
            const savedTheme = localStorage.getItem('theme');

            if (savedTheme === 'dark' || (!savedTheme && prefersDarkScheme.matches)) {
                document.documentElement.classList.add('dark');
                if (themeIcon) {
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                }
            }

            themeToggle.addEventListener('click', () => {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                    if (themeIcon) {
                        themeIcon.classList.remove('fa-sun');
                        themeIcon.classList.add('fa-moon');
                    }
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                    if (themeIcon) {
                        themeIcon.classList.remove('fa-moon');
                        themeIcon.classList.add('fa-sun');
                    }
                }
            });
        }

        // Profile Dropdown
        const profileToggle = document.getElementById('profileToggle');
        const profileDropdown = document.getElementById('profileDropdown');

        if (profileToggle && profileDropdown) {
            profileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (profileToggle && !profileToggle.contains(e.target) && 
                    profileDropdown && !profileDropdown.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }

        // BeriBot AI Assistant - Chatwoot style interactions
        const beriBotButton = document.getElementById('beriBotButton');
        const beriBotModal = document.getElementById('beriBotModal');
        const beriBotClose = document.getElementById('beriBotClose');

        if (beriBotButton && beriBotModal) {
            beriBotButton.addEventListener('click', () => {
                beriBotModal.classList.toggle('hidden');
                if (!beriBotModal.classList.contains('hidden')) {
                    // Scroll to bottom of messages
                    const messagesArea = document.getElementById('beriBotMessages');
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                }
            });
        }

        if (beriBotClose && beriBotModal) {
            beriBotClose.addEventListener('click', () => {
                beriBotModal.classList.add('hidden');
            });
        }

        // Click outside to close modal
        document.addEventListener('click', (e) => {
            if (beriBotModal && !beriBotModal.contains(e.target) && 
                beriBotButton && !beriBotButton.contains(e.target) &&
                !beriBotModal.classList.contains('hidden')) {
                beriBotModal.classList.add('hidden');
            }
        });

        // Keyboard Shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl + B: Open BeriBot
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                if (beriBotModal) {
                    beriBotModal.classList.toggle('hidden');
                }
            }

            // Escape: Close modals
            if (e.key === 'Escape') {
                if (beriBotModal) beriBotModal.classList.add('hidden');
                if (profileDropdown) profileDropdown.classList.add('hidden');
            }
        });

        console.log('BeriBakes Bakery Management System initialized successfully!');
    });
</script>

<!-- Animation styles for chatbot -->
<style>
    /* Chatwoot-style animations */
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-slide-up {
        animation: slideUp 0.3s ease-out;
    }

    /* Custom scrollbar for chat */
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }

    /* Typing animation */
    .animate-bounce {
        animation: bounce 1s infinite;
    }

    @keyframes bounce {
        0%, 60%, 100% {
            transform: translateY(0);
        }
        30% {
            transform: translateY(-4px);
        }
    }

    /* Loading dots */
    .loading-dots {
        display: inline-flex;
        align-items: center;
    }

    .loading-dots span {
        animation: loading 1.4s infinite both;
        background-color: currentColor;
        border-radius: 50%;
        display: inline-block;
        height: 4px;
        margin: 0 1px;
        width: 4px;
    }

    .loading-dots span:nth-child(1) {
        animation-delay: -0.32s;
    }

    .loading-dots span:nth-child(2) {
        animation-delay: -0.16s;
    }

    @keyframes loading {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }

    /* Notification badge */
    .notification-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 18px;
        height: 18px;
        padding: 0 4px;
        background: linear-gradient(135deg, #b45309, #f5b56c);
        color: white;
        font-size: 0.5rem;
        font-weight: 700;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    .dark .notification-badge {
        border-color: #0f172a;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }
</style>
</body>
</html>