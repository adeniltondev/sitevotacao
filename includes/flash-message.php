<?php
/**
 * Componente de Exibição de Mensagens Flash
 * Exibe mensagens de sucesso, erro, aviso e informação
 */

$flash = getFlashMessage();
if ($flash):
    $types = [
        'success' => [
            'bg' => 'bg-green-50 dark:bg-green-900/20',
            'border' => 'border-green-500',
            'text' => 'text-green-800 dark:text-green-300',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
        ],
        'error' => [
            'bg' => 'bg-red-50 dark:bg-red-900/20',
            'border' => 'border-red-500',
            'text' => 'text-red-800 dark:text-red-300',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
        ],
        'warning' => [
            'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
            'border' => 'border-yellow-500',
            'text' => 'text-yellow-800 dark:text-yellow-300',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>'
        ],
        'info' => [
            'bg' => 'bg-blue-50 dark:bg-blue-900/20',
            'border' => 'border-blue-500',
            'text' => 'text-blue-800 dark:text-blue-300',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
        ]
    ];

    $type = $flash['type'] ?? 'info';
    $config = $types[$type] ?? $types['info'];
    ?>
    <div class="flash-message mb-6 <?= $config['bg'] ?> border-l-4 <?= $config['border'] ?> p-4 rounded-lg shadow-sm animate-fade-in"
        role="alert">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 <?= $config['text'] ?>">
                <?= $config['icon'] ?>
            </div>
            <div class="flex-1">
                <p class="<?= $config['text'] ?> font-medium text-sm">
                    <?= htmlspecialchars($flash['message']) ?>
                </p>
            </div>
            <button onclick="this.closest('.flash-message').remove()"
                class="flex-shrink-0 <?= $config['text'] ?> hover:opacity-70 transition-opacity">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
    </style>
<?php endif; ?>