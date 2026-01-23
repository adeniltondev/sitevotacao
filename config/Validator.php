<?php
/**
 * Classe de Validação de Dados
 * Validação centralizada para todo o sistema
 */

class Validator
{
    private $errors = [];
    private $data = [];

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * Valida campo obrigatório
     */
    public function required($field, $message = null)
    {
        $value = $this->data[$field] ?? null;

        if (empty($value) && $value !== '0') {
            $this->errors[$field][] = $message ?? "O campo é obrigatório";
        }

        return $this;
    }

    /**
     * Valida email
     */
    public function email($field, $message = null)
    {
        $value = $this->data[$field] ?? null;

        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? "Email inválido";
        }

        return $this;
    }

    /**
     * Valida CPF
     */
    public function cpf($field, $message = null)
    {
        $value = $this->data[$field] ?? null;

        if (!empty($value) && !$this->isValidCPF($value)) {
            $this->errors[$field][] = $message ?? "CPF inválido";
        }

        return $this;
    }

    /**
     * Valida tamanho mínimo
     */
    public function minLength($field, $min, $message = null)
    {
        $value = $this->data[$field] ?? '';

        if (!empty($value) && strlen($value) < $min) {
            $this->errors[$field][] = $message ?? "Mínimo de {$min} caracteres";
        }

        return $this;
    }

    /**
     * Valida tamanho máximo
     */
    public function maxLength($field, $max, $message = null)
    {
        $value = $this->data[$field] ?? '';

        if (strlen($value) > $max) {
            $this->errors[$field][] = $message ?? "Máximo de {$max} caracteres";
        }

        return $this;
    }

    /**
     * Valida se é numérico
     */
    public function numeric($field, $message = null)
    {
        $value = $this->data[$field] ?? null;

        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field][] = $message ?? "Deve ser um número";
        }

        return $this;
    }

    /**
     * Valida data
     */
    public function date($field, $format = 'Y-m-d', $message = null)
    {
        $value = $this->data[$field] ?? null;

        if (!empty($value)) {
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->errors[$field][] = $message ?? "Data inválida";
            }
        }

        return $this;
    }

    /**
     * Valida se valor está em uma lista
     */
    public function in($field, $allowed, $message = null)
    {
        $value = $this->data[$field] ?? null;

        if (!empty($value) && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = $message ?? "Valor não permitido";
        }

        return $this;
    }

    /**
     * Valida arquivo
     */
    public function file($field, $options = [], $message = null)
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return $this;
        }

        $file = $_FILES[$field];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[$field][] = $message ?? "Erro no upload do arquivo";
            return $this;
        }

        // Validar tipo
        if (isset($options['types'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $options['types'])) {
                $this->errors[$field][] = "Tipo de arquivo não permitido";
            }
        }

        // Validar tamanho
        if (isset($options['maxSize']) && $file['size'] > $options['maxSize']) {
            $maxMB = round($options['maxSize'] / 1024 / 1024, 1);
            $this->errors[$field][] = "Arquivo muito grande. Máximo {$maxMB}MB";
        }

        return $this;
    }

    /**
     * Valida se dois campos são iguais
     */
    public function match($field, $matchField, $message = null)
    {
        $value = $this->data[$field] ?? null;
        $matchValue = $this->data[$matchField] ?? null;

        if ($value !== $matchValue) {
            $this->errors[$field][] = $message ?? "Os campos não coincidem";
        }

        return $this;
    }

    /**
     * Validação customizada
     */
    public function custom($field, $callback, $message = null)
    {
        $value = $this->data[$field] ?? null;

        if (!$callback($value)) {
            $this->errors[$field][] = $message ?? "Validação falhou";
        }

        return $this;
    }

    /**
     * Verifica se passou na validação
     */
    public function passes()
    {
        return empty($this->errors);
    }

    /**
     * Verifica se falhou na validação
     */
    public function fails()
    {
        return !$this->passes();
    }

    /**
     * Retorna todos os erros
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Retorna primeiro erro de um campo
     */
    public function getFirstError($field)
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Retorna todos os erros como array plano
     */
    public function getAllErrors()
    {
        $allErrors = [];
        foreach ($this->errors as $field => $errors) {
            foreach ($errors as $error) {
                $allErrors[] = $error;
            }
        }
        return $allErrors;
    }

    /**
     * Validação interna de CPF
     */
    private function isValidCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }

        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }
}
