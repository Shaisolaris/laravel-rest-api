<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $projects = Project::forTenant($request->user()->tenant_id)
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($projects);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $project = Project::forTenant($request->user()->tenant_id)->find($id);
        if (! $project) return $this->notFound('Project');
        return $this->success($project);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'metadata'    => 'nullable|array',
        ]);

        $project = Project::create([
            ...$data,
            'tenant_id' => $request->user()->tenant_id,
            'status'    => 'active',
            'metadata'  => $data['metadata'] ?? [],
        ]);

        return $this->created($project);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $project = Project::forTenant($request->user()->tenant_id)->find($id);
        if (! $project) return $this->notFound('Project');

        $data = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'status'      => 'sometimes|in:active,archived',
            'metadata'    => 'nullable|array',
        ]);

        $project->update($data);
        return $this->success($project->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $project = Project::forTenant($request->user()->tenant_id)->find($id);
        if (! $project) return $this->notFound('Project');

        $project->delete();
        return $this->noContent();
    }
}
