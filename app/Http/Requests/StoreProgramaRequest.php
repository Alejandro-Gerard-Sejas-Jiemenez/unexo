<?php

namespace App\Http\Requests;

use App\Enums\Certificado;
use App\Enums\EstadoComercial;
use App\Models\Programa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramaRequest extends FormRequest
{
    private const MENSAJE_PORTADA = 'La portada debe ser una imagen JPG, PNG o WebP de hasta 2 MB.';

    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware rol: en routes/web.php
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:'.config('limites.programas.codigo'), Rule::unique('programas', 'codigo')->ignore($this->programaActual())],
            'nombre' => [
                'required', 'string', 'max:'.config('limites.programas.nombre'),
                Rule::unique('programas', 'nombre')
                    ->where(fn ($q) => $q->where('version', $this->input('version')))
                    ->ignore($this->programaActual()),
            ],
            'version' => ['required', 'string', 'max:'.config('limites.programas.version')],
            'descripcion' => ['nullable', 'string', 'max:'.config('limites.programas.descripcion')],
            'estado_comercial' => ['required', Rule::enum(EstadoComercial::class)],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'imagen_portada_url' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.config('limites.programas.portada_kb')],
            'requisitos_ingreso' => ['nullable', 'string'],
            'certificado_otorga' => ['nullable', Rule::enum(Certificado::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'imagen_portada_url.mimetypes' => self::MENSAJE_PORTADA,
            'imagen_portada_url.max' => self::MENSAJE_PORTADA,
            'imagen_portada_url.file' => self::MENSAJE_PORTADA,
            'imagen_portada_url.uploaded' => self::MENSAJE_PORTADA,
            'certificado_otorga.enum' => 'Elige una opción de certificado de la lista.',
        ];
    }

    protected function programaActual(): ?Programa
    {
        return null;
    }
}
