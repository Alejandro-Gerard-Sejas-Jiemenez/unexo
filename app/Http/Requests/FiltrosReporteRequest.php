<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base de los filtros de reportes. Autoriza solo al Administrador (con el texto exacto de RF-02)
 * y, si un filtro es inválido, nunca calcula el reporte: la pantalla responde 422 con los errores
 * y las descargas vuelven a la pantalla con la misma consulta.
 */
abstract class FiltrosReporteRequest extends FormRequest
{
    public const MENSAJE_ACCESO = 'No tienes permiso para ver los reportes.';

    public const MENSAJE_RANGO = 'La fecha final no puede ser anterior a la fecha inicial.';

    public const MENSAJE_FECHA = 'La fecha ingresada no es válida.';

    public const MENSAJE_INEXISTENTE = 'El programa o la cohorte elegidos no existen.';

    /** Vista de la pantalla del reporte. */
    abstract protected function vistaPantalla(): string;

    public function authorize(): bool
    {
        return $this->user()?->rol === Rol::Administrador;
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(self::MENSAJE_ACCESO);
    }

    public function messages(): array
    {
        return [
            'desde.string' => self::MENSAJE_FECHA,
            'desde.date_format' => self::MENSAJE_FECHA,
            'hasta.string' => self::MENSAJE_FECHA,
            'hasta.date_format' => self::MENSAJE_FECHA,
            'programa_id.uuid' => self::MENSAJE_INEXISTENTE,
            'programa_id.exists' => self::MENSAJE_INEXISTENTE,
            'cohorte_id.uuid' => self::MENSAJE_INEXISTENTE,
            'cohorte_id.exists' => self::MENSAJE_INEXISTENTE,
        ];
    }

    /** Filtros validados y sin los vacíos. */
    public function filtros(): array
    {
        return array_filter($this->validated(), fn ($valor) => $valor !== null && $valor !== '');
    }

    /** Datos extra que necesita la pantalla (por ejemplo, las listas de los selectores). */
    public function datosFormulario(): array
    {
        return [];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->esDescarga()) {
            throw new HttpResponseException(redirect()->route($this->rutaPantalla(), $this->query()));
        }

        $vista = view($this->vistaPantalla(), $this->datosFormulario() + ['filtros' => $this->query(), 'reporte' => null])
            ->withErrors($validator->errors());

        throw new HttpResponseException(response($vista, 422));
    }

    private function esDescarga(): bool
    {
        return str_ends_with((string) $this->route()?->getName(), '.pdf')
            || str_ends_with((string) $this->route()?->getName(), '.excel');
    }

    private function rutaPantalla(): string
    {
        return preg_replace('/\.(pdf|excel)$/', '', (string) $this->route()?->getName());
    }
}
