<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Consulta de procesos de la jurisdiccion contencioso-administrativa en SAMAI
 * (Consejo de Estado, tribunales y juzgados administrativos).
 *
 * Usa el buscador publico de SAMAI (JSON, sin captcha). El detalle del proceso
 * con sus actuaciones esta protegido con un captcha "No soy un robot", asi que
 * no se consulta automaticamente: se ofrece el enlace para verlo en SAMAI.
 */
class SamaiService
{
    public const BASE_URL = 'https://samai.consejodeestado.gov.co';

    /**
     * Corporaciones (codigo de 7 digitos => nombre). El codigo coincide con los
     * primeros 7 digitos del radicado.
     *
     * @return array<string, string>
     */
    public function corporations(): array
    {
        $cached = Cache::get('samai:corporaciones');

        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $response = $this->client()->get(self::BASE_URL.'/Vistas/Casos/Jprocesos.ashx/corporaciones');
        } catch (\Throwable) {
            return [];
        }

        if (! $response->successful() || ! $response->json('ok')) {
            return [];
        }

        $corporations = collect($response->json('data', []))
            ->filter(fn ($c): bool => is_array($c) && filled($c['value'] ?? null))
            ->mapWithKeys(fn (array $c): array => [(string) $c['value'] => (string) ($c['text'] ?? $c['value'])])
            ->all();

        // Solo se guarda una lista valida: si SAMAI falla, se reintenta en la siguiente consulta.
        if ($corporations !== []) {
            Cache::put('samai:corporaciones', $corporations, now()->addDay());
        }

        return $corporations;
    }

    /**
     * Busca un proceso por radicado de 23 digitos.
     *
     * @return array<string, mixed>|null
     */
    public function findByRadicado(string $radicado): ?array
    {
        $radicado = preg_replace('/\D/', '', $radicado);

        if (strlen($radicado) < 20) {
            return null;
        }

        $items = $this->search([
            'tipoBusqueda' => 'radicado',
            'criterio' => $radicado,
            'corporacion' => substr($radicado, 0, 7),
        ]);

        foreach ($items as $item) {
            if (str_starts_with($item['radicado'], substr($radicado, 0, 21))) {
                return $item;
            }
        }

        return $items[0] ?? null;
    }

    /**
     * Busca procesos donde aparece una parte (persona o entidad) en una corporacion.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchByParty(string $name, string $corporation, string $partyType = '', bool $onlyActive = false): array
    {
        $results = [];

        // SAMAI entrega maximo 10 resultados por pagina; se leen hasta 3 paginas.
        for ($page = 1; $page <= 3; $page++) {
            $items = $this->search([
                'tipoBusqueda' => 'parte',
                'criterio' => trim($name),
                'fraseExacta' => true,
                'corporacion' => $corporation,
                'tipoParte' => $partyType,
                'estado' => $onlyActive ? 'vigente' : '',
                'pagina' => $page,
                'tamanoPagina' => 10,
            ]);

            $results = array_merge($results, $items);

            if (count($items) < 10) {
                break;
            }
        }

        return $results;
    }

    public static function detailUrl(string $guid): string
    {
        return self::BASE_URL.'/Vistas/Casos/list_procesos.aspx?guid='.urlencode($guid);
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<int, array<string, mixed>>
     */
    private function search(array $criteria): array
    {
        $body = array_merge([
            'tipoBusqueda' => 'radicado',
            'criterio' => '',
            'fraseExacta' => false,
            'ambito' => 'corporacion',
            'corporacion' => '',
            'seccion' => '',
            'ponente' => '',
            'fechaDesde' => '',
            'fechaHasta' => '',
            'estado' => '',
            'tipoParte' => '',
            'pagina' => 1,
            'tamanoPagina' => 10,
        ], $criteria);

        try {
            $response = $this->client()->post(self::BASE_URL.'/Vistas/Casos/Jprocesos.ashx/buscar', $body);
        } catch (\Throwable $e) {
            Log::warning('SAMAI: error de conexion', ['error' => $e->getMessage()]);

            return [];
        }

        if (! $response->successful() || ! $response->json('ok')) {
            Log::info('SAMAI: busqueda sin resultado', [
                'status' => $response->status(),
                'error' => $response->json('error.message'),
                'tipo' => $body['tipoBusqueda'],
            ]);

            return [];
        }

        $corporations = $this->corporations();

        return collect($response->json('data.items', []))
            ->filter(fn ($item): bool => is_array($item) && filled($item['radicado'] ?? null))
            ->map(fn (array $item): array => $this->normalize($item, $corporations))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, string>  $corporations
     * @return array<string, mixed>
     */
    private function normalize(array $item, array $corporations): array
    {
        $corporation = (string) ($item['corporacion'] ?? substr((string) $item['radicado'], 0, 7));
        $guid = (string) ($item['guid'] ?? '');

        return [
            'fuente' => 'samai',
            'radicado' => (string) $item['radicado'],
            'corporacion' => $corporation,
            'corporacion_nombre' => $corporations[$corporation] ?? '',
            'guid' => $guid,
            'ponente' => trim((string) ($item['ponente'] ?? '')),
            'despacho' => trim((string) ($item['identidadDespacho']['officeName'] ?? $item['nombreDespachoOperativo'] ?? '')),
            'clase' => trim((string) ($item['clase'] ?? '')),
            'asunto' => trim((string) ($item['asunto'] ?? '')),
            'fecha_ingreso' => (string) ($item['fechaIngreso'] ?? ''),
            'vigente' => strtoupper((string) ($item['vigente'] ?? '')) === 'SI' ? 'Vigente' : 'Archivado',
            'demandante' => trim((string) ($item['demandante'] ?? '')),
            'demandado' => trim((string) ($item['demandado'] ?? '')),
            'es_privado' => (bool) ($item['esPrivado'] ?? false),
            'url' => $guid !== '' ? self::detailUrl($guid) : self::BASE_URL.'/Vistas/Casos/procesos.aspx',
        ];
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withUserAgent('Mozilla/5.0 (compatible; LegalWeb/1.0; +https://legalweb.com.co)')
            ->timeout(25)
            ->retry(2, 800, throw: false);
    }
}
