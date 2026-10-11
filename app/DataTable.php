<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DataTable
{
    /**
     * @param  array<int, string|Builder>  $orderColumns
     * @param  list<string>  $searchColumns
     */
    public function response(Request $request, Builder $query, array $orderColumns, array $searchColumns, string $rowView, string $rowVariable): JsonResponse
    {
        $validated = $request->validate([
            'draw' => ['required', 'integer', 'min:0'],
            'start' => ['required', 'integer', 'min:0'],
            'length' => ['required', 'integer', Rule::in([10, 15, 25, 50, 100])],
            'table_search' => ['nullable', 'string', 'max:100'],
            'order' => ['sometimes', 'array', 'max:10'],
            'order.*.column' => ['required', 'integer', Rule::in(array_keys($orderColumns))],
            'order.*.dir' => ['required', Rule::in(['asc', 'desc'])],
        ]);
        $total = (clone $query)->count();
        $search = trim($validated['table_search'] ?? '');

        if ($search !== '') {
            $query->where(function (Builder $query) use ($searchColumns, $search): void {
                foreach ($searchColumns as $column) {
                    if (str_contains($column, '.')) {
                        $relation = substr($column, 0, strrpos($column, '.'));
                        $attribute = substr($column, strrpos($column, '.') + 1);
                        $query->orWhereHas($relation, fn (Builder $related) => $related->where($attribute, 'like', '%'.$search.'%'));
                    } else {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                }
            });
        }

        $filtered = (clone $query)->count();

        if (! empty($validated['order'])) {
            $query->reorder();

            foreach ($validated['order'] as $order) {
                $query->orderBy($orderColumns[$order['column']], $order['dir']);
            }
        }

        $rows = $query->orderBy($query->getModel()->qualifyColumn('id'))
            ->offset((int) $validated['start'])
            ->limit((int) $validated['length'])
            ->get();

        return response()->json([
            'draw' => (int) $validated['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map(fn (Model $row): string => view($rowView, [$rowVariable => $row])->render())->all(),
        ])->header('Cache-Control', 'no-store, private');
    }
}
