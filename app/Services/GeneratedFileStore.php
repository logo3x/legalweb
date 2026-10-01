<?php

namespace App\Services;

/**
 * Ubicacion privada de los archivos generados (reportes PDF, borradores Word).
 *
 * Cada firma tiene su carpeta fuera del directorio publico, y la descarga solo
 * busca en la carpeta de la firma del usuario autenticado.
 */
class GeneratedFileStore
{
    /**
     * Ruta absoluta donde guardar un archivo generado para la firma. Crea la carpeta si no existe.
     */
    public function pathFor(int $firmId, string $fileName): string
    {
        $directory = $this->directoryFor($firmId);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory.DIRECTORY_SEPARATOR.$this->sanitize($fileName);
    }

    /**
     * Ruta de un archivo existente de la firma, o null si no existe.
     */
    public function find(int $firmId, string $fileName): ?string
    {
        $path = $this->directoryFor($firmId).DIRECTORY_SEPARATOR.$this->sanitize($fileName);

        return is_file($path) ? $path : null;
    }

    private function directoryFor(int $firmId): string
    {
        return storage_path('app/private/generated/'.$firmId);
    }

    /**
     * Deja solo el nombre del archivo, sin rutas ni caracteres raros.
     */
    private function sanitize(string $fileName): string
    {
        return preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($fileName));
    }
}
