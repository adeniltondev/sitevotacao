/**
 * Validação de Formulários em Tempo Real
 * Valida campos enquanto o usuário digita
 */

const FormValidator = {
    /**
     * Inicializa validação em um formulário
     */
    init(formSelector) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        // Adicionar validação em todos os campos
        const fields = form.querySelectorAll('input, textarea, select');
        fields.forEach(field => {
            // Validar ao perder foco
            field.addEventListener('blur', () => this.validateField(field));

            // Validar ao digitar (com debounce)
            if (field.tagName !== 'SELECT') {
                let timeout;
                field.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => this.validateField(field), 500);
                });
            }
        });

        // Prevenir submit se houver erros
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
                Notifications.error('Por favor, corrija os erros no formulário');
            }
        });
    },

    /**
     * Valida um campo individual
     */
    validateField(field) {
        const errors = [];
        const value = field.value.trim();

        // Campo obrigatório
        if (field.hasAttribute('required') && !value) {
            errors.push('Este campo é obrigatório');
        }

        // Validação de email
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                errors.push('Email inválido');
            }
        }

        // Validação de CPF
        if (field.dataset.validate === 'cpf' && value) {
            if (!this.isValidCPF(value)) {
                errors.push('CPF inválido');
            }
        }

        // Tamanho mínimo
        if (field.minLength && value.length < field.minLength && value.length > 0) {
            errors.push(`Mínimo de ${field.minLength} caracteres`);
        }

        // Tamanho máximo
        if (field.maxLength && value.length > field.maxLength) {
            errors.push(`Máximo de ${field.maxLength} caracteres`);
        }

        // Número
        if (field.type === 'number' && value) {
            if (isNaN(value)) {
                errors.push('Deve ser um número');
            }
        }

        // Confirmação de senha
        if (field.dataset.match) {
            const matchField = document.querySelector(field.dataset.match);
            if (matchField && value !== matchField.value) {
                errors.push('Os campos não coincidem');
            }
        }

        // Exibir ou remover erros
        if (errors.length > 0) {
            this.showFieldError(field, errors[0]);
        } else {
            this.removeFieldError(field);
        }

        return errors.length === 0;
    },

    /**
     * Valida formulário completo
     */
    validateForm(form) {
        const fields = form.querySelectorAll('input, textarea, select');
        let isValid = true;

        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    },

    /**
     * Mostra erro no campo
     */
    showFieldError(field, message) {
        // Adicionar classe de erro
        field.classList.add('border-red-500', 'border-2');
        field.classList.remove('border-green-500', 'border-gray-200', 'border-gray-300');

        // Remover mensagem antiga se existir
        this.removeFieldError(field, false);

        // Adicionar mensagem de erro
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-red-600 text-xs mt-1 flex items-center gap-1';
        errorDiv.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>${message}</span>
        `;

        field.parentElement.appendChild(errorDiv);
    },

    /**
     * Remove erro do campo
     */
    removeFieldError(field, showSuccess = true) {
        // Remover classes de erro
        field.classList.remove('border-red-500', 'border-2');

        // Adicionar classe de sucesso se o campo estiver preenchido
        if (showSuccess && field.value.trim()) {
            field.classList.add('border-green-500', 'border-2');
            field.classList.remove('border-gray-200', 'border-gray-300');
        } else {
            field.classList.remove('border-green-500');
        }

        // Remover mensagem de erro
        const errorDiv = field.parentElement.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    },

    /**
     * Valida CPF
     */
    isValidCPF(cpf) {
        cpf = cpf.replace(/[^\d]/g, '');

        if (cpf.length !== 11) return false;
        if (/^(\d)\1{10}$/.test(cpf)) return false;

        let sum = 0;
        let remainder;

        for (let i = 1; i <= 9; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }

        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(9, 10))) return false;

        sum = 0;
        for (let i = 1; i <= 10; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }

        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(10, 11))) return false;

        return true;
    }
};

// Expor globalmente
window.FormValidator = FormValidator;

// Auto-inicializar em formulários com data-validate
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        FormValidator.init(`#${form.id}`);
    });
});
