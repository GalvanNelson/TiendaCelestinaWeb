<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepara los datos para validación.
     * Limpia valores inválidos como 'NaN', 'undefined', o strings vacíos.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'categoria_codigo' => $this->sanitizeValue($this->categoria_codigo),
            'unidad_codigo' => $this->sanitizeValue($this->unidad_codigo),
        ]);
    }

    /**
     * Limpia y convierte valores a enteros válidos o null.
     */
    private function sanitizeValue($value)
    {
        // Si es null, 'null', vacío, 'undefined', o 'NaN', retornar null
        if (
            is_null($value) ||
            $value === '' ||
            $value === 'null' ||
            $value === 'undefined' ||
            $value === 'NaN' ||
            strtolower($value) === 'nan'
        ) {
            return null;
        }

        // Si es numérico, convertir a entero
        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'], // Agregado para descripción
            'imagen' => ['nullable', 'image', 'max:2048'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'categoria_codigo' => ['nullable', 'integer', 'exists:categorias,codigo_categoria'],
            'unidad_codigo' => ['nullable', 'integer', 'exists:unidad_medidas,codigo_medida'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.max' => 'La imagen no puede exceder los 2MB.',
            'precio_unitario.required' => 'El precio unitario es obligatorio.',
            'precio_unitario.numeric' => 'El precio unitario debe ser un número.',
            'precio_unitario.min' => 'El precio unitario debe ser mayor o igual a 0.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock debe ser mayor o igual a 0.',
            'categoria_codigo.integer' => 'La categoría debe ser un valor válido.',
            'categoria_codigo.exists' => 'La categoría seleccionada no existe.',
            'unidad_codigo.integer' => 'La unidad de medida debe ser un valor válido.',
            'unidad_codigo.exists' => 'La unidad de medida seleccionada no existe.',
        ];
    }
}
