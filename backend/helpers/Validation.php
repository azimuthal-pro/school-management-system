<?php

class Validation {
    public static function validate($rules, $data) {
        $errors = [];

        foreach ($rules as $field => $checks) {
            $value = $data[$field] ?? null;

            foreach ($checks as $check) {
                if (is_string($check)) {
                    if ($check === 'required' && (is_null($value) || $value === '')) {
                        $errors[$field][] = 'The ' . $field . ' field is required.';
                    }
                    if ($check === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = 'The ' . $field . ' must be a valid email address.';
                    }
                } elseif (is_array($check)) {
                    $rule = $check[0];
                    $param = $check[1] ?? null;

                    if ($rule === 'min' && !is_null($value) && strlen($value) < $param) {
                        $errors[$field][] = 'The ' . $field . ' must be at least ' . $param . ' characters.';
                    }

                    if ($rule === 'max' && !is_null($value) && strlen($value) > $param) {
                        $errors[$field][] = 'The ' . $field . ' must not exceed ' . $param . ' characters.';
                    }

                    if ($rule === 'numeric' && !is_null($value) && !is_numeric($value)) {
                        $errors[$field][] = 'The ' . $field . ' must be a number.';
                    }

                    if ($rule === 'in' && !is_null($value) && !in_array($value, (array)$param)) {
                        $errors[$field][] = 'The ' . $field . ' must be one of: ' . implode(', ', (array)$param) . '.';
                    }

                    if ($rule === 'date_format' && !is_null($value)) {
                        $d = DateTime::createFromFormat($param, $value);
                        if (!($d && $d->format($param) === $value)) {
                            $errors[$field][] = 'The ' . $field . ' must match the format ' . $param . '.';
                        }
                    }

                    if ($rule === 'between' && !is_null($value) && is_numeric($value)) {
                        list($min, $max) = $param;
                        if ($value < $min || $value > $max) {
                            $errors[$field][] = 'The ' . $field . ' must be between ' . $min . ' and ' . $max . '.';
                        }
                    }
                }
            }
        }

        return $errors;
    }
}
?>