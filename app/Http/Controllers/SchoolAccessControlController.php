<?php

namespace App\Http\Controllers;

use App\Models\SchoolRoleAccessBlock;
use App\Services\Access\SchoolRoleAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SchoolAccessControlController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->isFondateur() || $user?->isSuperAdmin(), 403);

        $etabId = (int) $user->ecoleActiveId();
        abort_unless($etabId, 403, 'Aucun établissement associé.');

        $catalogue = SchoolRoleAccessService::catalogue();
        $roles = SchoolRoleAccessService::managedRoles();

        $blocked = SchoolRoleAccessBlock::query()
            ->where('etablissement_id', $etabId)
            ->get()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('menu_key')->all())
            ->toArray();

        return view('access-control.index', compact('catalogue', 'roles', 'blocked'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->isFondateur() || $user?->isSuperAdmin(), 403);

        $etabId = (int) $user->ecoleActiveId();
        abort_unless($etabId, 403, 'Aucun établissement associé.');

        $roles = array_keys(SchoolRoleAccessService::managedRoles());
        $catalogueKeys = array_keys(SchoolRoleAccessService::catalogue());

        $validated = $request->validate([
            'blocks' => ['nullable', 'array'],
            'blocks.*' => ['array'],
            'blocks.*.*' => ['string'],
        ]);

        foreach ($roles as $role) {
            $menuKeys = collect($validated['blocks'][$role] ?? [])
                ->filter(fn ($key) => in_array($key, $catalogueKeys, true))
                ->values()
                ->all();

            SchoolRoleAccessService::replaceBlocks($etabId, $role, $menuKeys, $user->id);
            Cache::forget("school_access_blocks:{$etabId}:{$role}");
        }

        return redirect()->route('access-control.index')
            ->with('success', 'Accès mis à jour. Les menus et les routes sélectionnés sont maintenant bloqués.');
    }
}
