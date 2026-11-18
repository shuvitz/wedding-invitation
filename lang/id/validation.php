<?php

// Only the rules guests can hit through a public form block
// (BlockRegistry::submissionRules). Anything missing falls back to
// Laravel's English messages (APP_FALLBACK_LOCALE=en).
return [
    'between' => [
        'numeric' => ':Attribute harus di antara :min dan :max.',
    ],
    'in' => 'Pilihan :attribute tidak valid.',
    'max' => [
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'numeric' => ':Attribute harus berupa angka.',
    'required' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',
];
