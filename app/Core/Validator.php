<?php declare(strict_types=1);

namespace App\Core;

/**
 * Class Validator
 *
 * Form Doğrulama (Validation) Motoru.
 * `required`, `email`, `numeric`, `integer`, `min`, `max`, `in`, `confirmed`
 * gibi zengin kurallarla girdi güvenliği sağlar.
 *
 * @package App\Core
 */
class Validator
{
    private array $errors = [];

    public static function validate(array $data, array $rules, array $labels = []): array
    {
        $validator = new self();
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $rulesList = explode('|', $ruleString);
            $label = $labels[$field] ?? $field;
            foreach ($rulesList as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $validator->apply($field, $label, $value, $name, $param, $data);
            }
        }
        return $validator->errors;
    }

    private function apply(string $field, string $label, $value, string $rule, ?string $param, array $data): void
    {
        if (isset($this->errors[$field])) {
            return;
        }
        $stringValue = is_scalar($value) ? trim((string) $value) : '';
        $isEmpty = $value === null || $stringValue === '';

        if ($rule === 'required' && $isEmpty) {
            $this->errors[$field] = $label . ' alanı zorunludur.';
            return;
        }
        if ($isEmpty) {
            return;
        }

        switch ($rule) {
            case 'email':
                if (!filter_var($stringValue, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = 'Geçerli bir e-posta adresi giriniz.';
                }
                break;
            case 'numeric':
                if (!is_numeric($stringValue)) {
                    $this->errors[$field] = $label . ' sayısal olmalıdır.';
                }
                break;
            case 'integer':
                if (filter_var($stringValue, FILTER_VALIDATE_INT) === false) {
                    $this->errors[$field] = $label . ' tam sayı olmalıdır.';
                }
                break;
            case 'min':
                if (mb_strlen($stringValue) < (int) $param) {
                    $this->errors[$field] = $label . ' en az ' . $param . ' karakter olmalıdır.';
                }
                break;
            case 'max':
                if (mb_strlen($stringValue) > (int) $param) {
                    $this->errors[$field] = $label . ' en fazla ' . $param . ' karakter olabilir.';
                }
                break;
            case 'in':
                $allowed = explode(',', (string) $param);
                if (!in_array($stringValue, $allowed, true)) {
                    $this->errors[$field] = $label . ' geçersiz bir değer içeriyor.';
                }
                break;
            case 'confirmed':
                if (($data[$field . '_confirmation'] ?? null) !== $value) {
                    $this->errors[$field] = $label . ' tekrarı eşleşmiyor.';
                }
                break;
            case 'date':
                if (strtotime($stringValue) === false) {
                    $this->errors[$field] = $label . ' geçerli bir tarih olmalıdır.';
                }
                break;
            case 'phone':
                if (!preg_match('/^[0-9+()\s-]{7,30}$/', $stringValue)) {
                    $this->errors[$field] = 'Geçerli bir telefon numarası giriniz.';
                }
                break;
            case 'tax_number':
                if (!preg_match('/^[0-9]{10,11}$/', preg_replace('/\s+/', '', $stringValue))) {
                    $this->errors[$field] = 'Vergi / TC kimlik numarası 10 veya 11 haneli olmalıdır.';
                }
                break;
            case 'unique':
                [$table, $column] = array_pad(explode(',', (string) $param), 2, 'id');
                try {
                    $count = (int) Database::fetchColumn(
                        sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = :value', $table, $column),
                        [':value' => $value]
                    );
                    if ($count > 0) {
                        $this->errors[$field] = $label . ' zaten kullanılmaktadır.';
                    }
                } catch (\Throwable $e) {
                    // ignore uniqueness check failure
                }
                break;
        }
    }

    public static function persistErrors(array $errors): void
    {
        Session::set('_errors', $errors);
    }
}
