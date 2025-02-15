<?php

class InputValidator {
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                $errors[$field] = "Field is required";
                continue;
            }

            $value = trim($data[$field]);

            switch ($rule) {
                case 'exam_type':
                    if (!preg_match('/^[A-Za-z0-9\s\-]+$/', $value)) {
                        $errors[$field] = "Invalid exam type format";
                    }
                    break;
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = "Invalid email format";
                    }
                    break;
                // Add more validation rules as needed
            }
        }

        return $errors;
    }
}
