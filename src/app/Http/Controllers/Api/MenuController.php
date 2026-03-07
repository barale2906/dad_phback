<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class MenuController extends Controller
{
    /**
     * Devuelve el menú del sistema filtrado por el rol del usuario autenticado.
     * Pensado para consumir desde el frontend (SPA).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $rol = $user->rol ?? '';
        $items = Config::get('menu.items', []);
        $filtered = $this->filterByRole($items, $rol);
        $filtered = $this->sortItems($filtered);
        $tree = $this->buildTree($filtered);

        return response()->json([
            'menu' => $tree,
            'rol' => $rol,
        ]);
    }

    /**
     * Filtra ítems por rol. roles vacío = todos los autenticados.
     */
    private function filterByRole(array $items, string $rol): array
    {
        $out = [];
        foreach ($items as $item) {
            $roles = $item['roles'] ?? [];
            if (! is_array($roles)) {
                $roles = [];
            }
            if (count($roles) > 0 && ! in_array($rol, $roles, true)) {
                continue;
            }
            $clone = $item;
            if (isset($clone['children'])) {
                $clone['children'] = $this->filterByRole($clone['children'], $rol);
                if (count($clone['children']) === 0 && empty($clone['route'])) {
                    continue;
                }
            }
            unset($clone['roles']);
            $out[] = $clone;
        }
        return $out;
    }

    private function sortItems(array $items): array
    {
        usort($items, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        foreach ($items as &$item) {
            if (isset($item['children'])) {
                $item['children'] = $this->sortItems($item['children']);
            }
        }
        return $items;
    }

    /**
     * Agrupa ítems con 'parent' bajo su padre (por key). Construye el árbol de forma recursiva.
     */
    private function buildTree(array $items): array
    {
        $orderMap = array_flip(array_column($items, 'key'));

        // Solo ítems raíz (sin parent o cuyo parent no está en la lista)
        $rootItems = [];
        foreach ($items as $item) {
            $parent = $item['parent'] ?? null;
            if ($parent === null || $parent === '') {
                $rootItems[] = $item;
            }
        }

        usort($rootItems, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        $root = [];
        foreach ($rootItems as $item) {
            $root[] = $this->buildTreeNode($item['key'] ?? '', $items);
        }

        return $root;
    }

    /**
     * Construye un nodo del menú con sus hijos (recursivo).
     */
    private function buildTreeNode(string $key, array $items): array
    {
        $item = $this->findItemByKey($key, $items);
        if (! $item) {
            return ['key' => $key, 'label' => '', 'route' => '', 'children' => []];
        }

        $node = [
            'key' => $item['key'],
            'label' => $item['label'],
            'route' => $item['route'] ?? '',
            'icon' => $item['icon'] ?? null,
            'api' => $item['api'] ?? null,
            'children' => [],
        ];

        $children = [];
        foreach ($items as $i) {
            if (($i['parent'] ?? null) === $key) {
                $children[] = $i;
            }
        }
        usort($children, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        foreach ($children as $child) {
            $node['children'][] = $this->buildTreeNode($child['key'] ?? '', $items);
        }

        return $node;
    }

    private function findItemByKey(string $key, array $items): ?array
    {
        foreach ($items as $item) {
            if (($item['key'] ?? null) === $key) {
                return $item;
            }
        }
        return null;
    }
}
