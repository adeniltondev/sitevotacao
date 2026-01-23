/**
 * Sistema de Notificações Toast
 * Exibe mensagens elegantes para o usuário
 */

const Notifications = {
    container: null,
    
    /**
     * Inicializa o sistema de notificações
     */
    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'fixed top-4 right-4 z-[9999] space-y-3';
            document.body.appendChild(this.container);
        }
    },
    
    /**
     * Exibe notificação
     */
    show(message, type = 'info', duration = 5000) {
        this.init();
        
        const toast = this.createToast(message, type);
        this.container.appendChild(toast);
        
        // Animação de entrada
        setTimeout(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
        }, 10);
        
        // Auto-remover
        if (duration > 0) {
            setTimeout(() => {
                this.remove(toast);
            }, duration);
        }
        
        return toast;
    },
    
    /**
     * Cria elemento toast
     */
    createToast(message, type) {
        const toast = document.createElement('div');
        toast.className = 'transform translate-x-full opacity-0 transition-all duration-300 ease-out max-w-sm w-full';
        
        const colors = {
            success: {
                bg: 'bg-gradient-to-r from-green-500 to-emerald-600',
                icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`
            },
            error: {
                bg: 'bg-gradient-to-r from-red-500 to-rose-600',
                icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`
            },
            warning: {
                bg: 'bg-gradient-to-r from-yellow-500 to-orange-600',
                icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>`
            },
            info: {
                bg: 'bg-gradient-to-r from-blue-500 to-indigo-600',
                icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`
            }
        };
        
        const config = colors[type] || colors.info;
        
        toast.innerHTML = `
            <div class="${config.bg} text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-4">
                <div class="flex-shrink-0">
                    ${config.icon}
                </div>
                <div class="flex-1 text-sm font-medium">
                    ${message}
                </div>
                <button onclick="Notifications.remove(this.closest('.transform'))" class="flex-shrink-0 p-1 hover:bg-white/20 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        return toast;
    },
    
    /**
     * Remove toast
     */
    remove(toast) {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            toast.remove();
        }, 300);
    },
    
    /**
     * Atalhos para tipos específicos
     */
    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    },
    
    error(message, duration = 7000) {
        return this.show(message, 'error', duration);
    },
    
    warning(message, duration = 6000) {
        return this.show(message, 'warning', duration);
    },
    
    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }
};

// Expor globalmente
window.Notifications = Notifications;

// Auto-inicializar
document.addEventListener('DOMContentLoaded', () => {
    Notifications.init();
});
