<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'unique' => 'El valor de :attribute ya está registrado.',
    'exists' => 'El valor seleccionado en :attribute no existe.',
    'uuid' => 'El campo :attribute no es un identificador válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'boolean' => 'El campo :attribute no es válido.',
    'enum' => 'El valor seleccionado en :attribute no es válido.',
    'max' => [
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
    ],
    'attributes' => [
        'rol' => 'rol',
        'nombres' => 'nombres',
        'apellidos' => 'apellidos',
        'documento_identidad' => 'documento de identidad',
        'email' => 'correo personal',
        'correo_institucional' => 'correo institucional',
        'telefono' => 'teléfono',
        'password' => 'contraseña',
        'codigo' => 'código',
        'nombre' => 'nombre',
        'version' => 'versión',
        'descripcion' => 'descripción',
        'estado_comercial' => 'estado comercial',
        'duracion_horas' => 'duración en horas',
        'modulo_id' => 'módulo',
        'usuario' => 'usuario',
    ],
];
