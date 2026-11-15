<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentRequest;
use App\Models\MediaAsset;
use App\Models\OrderedContent;
use App\Services\InlineImageUploads;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    public function __construct(private InlineImageUploads $images) {}

    public function index(Request $request, string $module): View
    {
        $definition = $this->definition($module);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:all,visible,hidden,archived']]);
        $status = $filters['status'] ?? 'all';
        $search = $filters['search'] ?? '';
        $class = $definition['model'];
        $query = $status === 'archived' ? $class::onlyTrashed() : $class::query();
        if (in_array($status, ['visible', 'hidden'], true)) {
            $query->where('is_active', $status === 'visible');
        }
        if ($search !== '') {
            $query->where(function ($query) use ($definition, $search): void {
                foreach ($definition['search'] as $field) {
                    $query->orWhere($field, 'like', '%'.$search.'%');
                }
            });
        }
        if (isset($definition['image_column'])) {
            $query->with($definition['image_column'] === 'thumbnail_image_id' ? 'thumbnail' : 'image');
        }

        return view('admin.content.index', [
            'moduleKey' => $module, 'definition' => $definition, 'search' => $search, 'status' => $status,
            'records' => $query->ordered()->paginate(15)->withQueryString(),
        ]);
    }

    public function create(string $module): View
    {
        $definition = $this->definition($module);
        $class = $definition['model'];
        $record = new $class;
        $record->sort_order = min(1000000, ((int) $class::max('sort_order')) + 10);
        $record->is_active = false;
        foreach ($definition['fields'] as $name => $field) {
            if (array_key_exists('default', $field)) {
                $record->$name = $field['default'];
            }
        }

        return $this->form($module, $record);
    }

    public function store(ContentRequest $request, string $module): RedirectResponse
    {
        $definition = $this->definition($module);
        $class = $definition['model'];
        $record = $this->save($request, $module, new $class);

        return to_route('admin.content.edit', [$module, $record->id])->with('status', ucfirst($definition['singular']).' created.');
    }

    public function edit(string $module, string $record): View
    {
        $class = $this->definition($module)['model'];

        return $this->form($module, $class::findOrFail($record));
    }

    public function update(ContentRequest $request, string $module, string $record): RedirectResponse
    {
        $definition = $this->definition($module);
        $class = $definition['model'];
        $this->save($request, $module, $class::findOrFail($record));

        return to_route('admin.content.edit', [$module, $record])->with('status', ucfirst($definition['singular']).' saved.');
    }

    public function visibility(Request $request, string $module, string $record): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $class = $this->definition($module)['model'];
        $class::findOrFail($record)->update($data);

        return to_route('admin.content.index', $module)->with('status', 'Visibility updated.');
    }

    public function destroy(string $module, string $record): RedirectResponse
    {
        $class = $this->definition($module)['model'];
        DB::transaction(function () use ($class, $record): void {
            $item = $class::query()->lockForUpdate()->findOrFail($record);
            $item->update(['is_active' => false]);
            $item->delete();
        });

        return to_route('admin.content.index', $module)->with('status', 'Content archived. It can be restored from the Archived filter.');
    }

    public function restore(string $module, string $record): RedirectResponse
    {
        $class = $this->definition($module)['model'];
        DB::transaction(function () use ($class, $record): void {
            $item = $class::onlyTrashed()->lockForUpdate()->findOrFail($record);
            $item->is_active = false;
            $item->restore();
        });

        return to_route('admin.content.index', $module)->with('status', 'Content restored as hidden. Review it before making it visible.');
    }

    public function forceDestroy(string $module, string $record): RedirectResponse
    {
        $class = $this->definition($module)['model'];
        $class::onlyTrashed()->findOrFail($record)->forceDelete();

        return to_route('admin.content.index', ['module' => $module, 'status' => 'archived'])->with('status', 'Archived content permanently deleted. Library images were kept.');
    }

    private function definition(string $module): array
    {
        $editor = config('content_editor.modules')[$module] ?? null;
        $admin = config('admin.modules')[$module] ?? null;
        abort_unless($editor && $admin && is_subclass_of($admin['model'], OrderedContent::class), 404);

        return array_merge($admin, $editor);
    }

    private function form(string $module, Model $record): View
    {
        if ($module === 'chamber' && $record->exists) {
            $record->load('schedules');
        }

        return view('admin.content.form', [
            'moduleKey' => $module, 'definition' => $this->definition($module), 'record' => $record,
            'mediaOptions' => MediaAsset::where('disk', 'public')->latest('id')->get(),
        ]);
    }

    private function save(ContentRequest $request, string $module, Model $record): Model
    {
        return $this->images->save($request, $request->validated(), InlineImageUploads::contentFields($module), function (array $data) use ($module, $record): Model {
            $schedules = $data['schedules'] ?? null;
            unset($data['schedules'], $data['schedules_present']);
            if ($module === 'chamber' && array_key_exists('additional_phones_text', $data)) {
                $data['additional_phones'] = array_values(array_filter(array_map('trim', preg_split('/\\r\\n|\\r|\\n/', $data['additional_phones_text'] ?? '')), fn (string $line): bool => $line !== ''));
                unset($data['additional_phones_text']);
            }
            $record->fill($data)->save();
            if ($module === 'chamber' && $schedules !== null) {
                $record->schedules()->delete();
                $record->schedules()->createMany($schedules);
            }

            return $record;
        });
    }
}
