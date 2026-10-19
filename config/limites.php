<?php

/*
 * Límites de datos: una sola fuente para el backend (reglas de validación y consultas)
 * y el frontend (maxlength, max y contadores de los formularios).
 * Si cambias un valor aquí, validación y formulario cambian juntos.
 */
return [
    // Filas por página en los listados.
    'por_pagina' => 10,

    // Máximo de opciones que se cargan en un <select>; si hay más, el formulario avisa.
    'opciones_select' => 100,

    'usuarios' => [
        'nombres' => 120,
        'apellidos' => 120,
        'documento_identidad' => 30,
        'email' => 255,
        'correo_institucional' => 255,
        'telefono' => 30,
        'password' => 100,
    ],
    'programas' => ['codigo' => 30, 'nombre' => 255, 'version' => 20, 'descripcion' => 2000, 'portada_kb' => 2048],
    'modulos' => ['codigo' => 30, 'nombre' => 255, 'duracion_horas' => 1000, 'prerrequisito' => 500],
    'cohortes' => ['nombre' => 150],
    'grupos' => ['enlace_videoconferencia' => 255, 'cupo_minimo' => 1000, 'horarios_max' => 14],
    'aulas' => ['nombre' => 80, 'capacidad' => 1000],
];
