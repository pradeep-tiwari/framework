<?php

namespace Lightpack\Database\Lucid;

/**
 * ResourceQuery — HTTP request query parameter abstraction for Lucid models.
 *
 * Reads query string parameters from the current HTTP request and
 * translates them into ORM Builder operations.
 *
 * An optional Transformer layer (via paginateAndTransform(), all()->transform()) can shape
 * the response output when a Transformer is defined on the model.
 *
 * Supported query parameters:
 *
 *   Filters:  ?filter[status]=active&filter[role][]=admin&filter[role][]=editor
 *   Sort:     ?sort=-created_at,name   (- prefix = DESC, bare = ASC)
 *   Includes: ?include=profile,posts,posts.comments
 *   Fields:   ?fields=name,email       (root model)
 *             ?fields[profile]=bio,avatar  (relation)
 *   Page:     ?page=2&per_page=20
 *
 * Security: ALL parameters are opt-in via allow*() methods. Unrecognized
 * or unallowed parameters are silently ignored.
 *
 * Example:
 *
 *   $query = ResourceQuery::for(User::class)
 *       ->allowFilters(['status', 'role', 'search'])
 *       ->allowSorts(['name', 'email', 'created_at'])
 *       ->allowIncludes(['profile', 'posts', 'posts.comments'])
 *       ->allowFields(['name', 'email', 'created_at'])
 *       ->defaultSort('-created_at');
 *
 *   $pagination = $query->paginate();
 */
class ResourceQuery
{
    private string $modelClass;
    private ?Builder $builder = null;
    private array $allowedFilters = [];
    private array $allowedSorts = [];
    private array $allowedIncludes = [];
    private array $allowedFields = [];
    private ?string $defaultSort = null;
    private array $defaultIncludes = [];
    private array $allowedCounts = [];
    private array $allowedSums = [];
    private array $allowedAvgs = [];
    private array $allowedMins = [];
    private array $allowedMaxs = [];
    private int $maxPerPage = 100;
    private int $perPage = 15;

    private function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Create a ResourceQuery for the given model class.
     *
     * @param string $modelClass Fully-qualified model class name
     */
    public static function for(string $modelClass): self
    {
        return new self($modelClass);
    }

    /**
     * Whitelist filter keys that map to scope methods on the model.
     *
     * ?filter[status]=active  →  $model->scopeStatus($builder, 'active')
     * ?filter[role][]=a&filter[role][]=b  →  $model->scopeRole($builder, ['a','b'])
     */
    public function allowFilters(array $filters): self
    {
        $this->allowedFilters = $filters;

        return $this;
    }

    /**
     * Whitelist column names that clients may sort by.
     *
     * ?sort=-created_at,name  →  orderBy('created_at', 'DESC'), orderBy('name', 'ASC')
     */
    public function allowSorts(array $sorts): self
    {
        $this->allowedSorts = $sorts;

        return $this;
    }

    /**
     * Whitelist relation names (dot-notation for nested) that clients may include.
     *
     * ?include=profile,posts.comments  →  with('profile', 'posts.comments')
     */
    public function allowIncludes(array $includes): self
    {
        $this->allowedIncludes = $includes;

        return $this;
    }

    /**
     * Whitelist root-model field names that clients may request.
     *
     * This ONLY has an effect when the model has a Transformer defined and you
     * call paginateAndTransform(), all()->transform(), or manually pass the
     * output of transformOptions() to a transformer. It does NOT affect the SQL
     * query, nor does it limit the fields returned by paginate(), all(), or
     * one() when no transformer is involved.
     *
     * ?fields=name,email  →  transformer fields(['self' => ['name', 'email']])
     */
    public function allowFields(array $fields): self
    {
        $this->allowedFields = $fields;

        return $this;
    }

    /**
     * Default sort to apply when the client provides no ?sort parameter.
     *
     * Use - prefix for descending: defaultSort('-created_at')
     */
    public function defaultSort(string $sort): self
    {
        $this->defaultSort = $sort;

        return $this;
    }

    /**
     * Relations to always eager load regardless of ?include parameter.
     */
    public function defaultIncludes(array $includes): self
    {
        $this->defaultIncludes = $includes;

        return $this;
    }

    /**
     * Whitelist relation names whose row counts the client may request.
     *
     * ?count=comments,likes  →  withCount(['comments', 'likes'])
     *
     * Adds a `relation_count` attribute to each model in the result.
     * Only relations in the allowlist are counted.
     */
    public function allowCounts(array $relations): self
    {
        $this->allowedCounts = $relations;

        return $this;
    }

    /**
     * Whitelist relation aggregate operations the client may request.
     *
     * ?sum=products.price,orders.total  →  withSum('products', 'price') + withSum('orders', 'total')
     *
     * Each entry maps a relation name to an array of allowed columns.
     * Only relation.column pairs in the allowlist are executed.
     */
    public function allowSum(array $relations): self
    {
        $this->allowedSums = $relations;

        return $this;
    }

    public function allowAvg(array $relations): self
    {
        $this->allowedAvgs = $relations;

        return $this;
    }

    public function allowMin(array $relations): self
    {
        $this->allowedMins = $relations;

        return $this;
    }

    public function allowMax(array $relations): self
    {
        $this->allowedMaxs = $relations;

        return $this;
    }

    /**
     * Hard cap on ?per_page to prevent abuse. Default: 100.
     */
    public function maxPerPage(int $max): self
    {
        $this->maxPerPage = $max;

        return $this;
    }

    /**
     * Default page size when the client sends no ?per_page parameter.
     * Overrides the framework default of 15.
     */
    public function perPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Execute the query and return a paginated Pagination object.
     *
     * Per-page is read from ?per_page in the request, falling back to ?limit,
     * then to the framework default of 15. Capped at maxPerPage.
     */
    public function paginate(): Pagination
    {
        $this->build();

        return $this->builder->paginate($this->resolvePerPage());
    }

    /**
     * Execute the query and return a paginated, transformed array.
     *
     * Convenience method that combines paginate() and transformOptions().
     */
    public function paginateAndTransform(): array
    {
        $pagination = $this->paginate();

        return $pagination->transform($this->transformOptions());
    }

    /**
     * Execute the query and return a Collection.
     */
    public function all(): Collection
    {
        $this->build();

        return $this->builder->all();
    }

    /**
     * Execute the query and return a single Model or null.
     */
    public function one(): ?Model
    {
        $this->build();

        return $this->builder->one();
    }

    /**
     * Return the fully-configured Builder for further manual modification.
     */
    public function getBuilder(): Builder
    {
        $this->build();

        return $this->builder;
    }

    /**
     * Return the options array to pass directly to Pagination::transform() or
     * Collection::transform() / Model::transform().
     *
     * Contains 'fields' and 'includes' resolved from the request query params.
     *
     * Usage:
     *   $pagination->transform($query->transformOptions())
     *
     * @internal Prefer paginateAndTransform() or all()->transform().
     */
    public function transformOptions(): array
    {
        $options = [];

        $includes = $this->parsedIncludes();
        if (! empty($includes)) {
            $options['includes'] = $includes;
        }

        $fields = $this->parsedFields();
        if (! empty($fields)) {
            $options['fields'] = $fields;
        }

        return $options;
    }

    /**
     * Build the query by applying all parsed request parameters.
     * Idempotent — safe to call multiple times.
     */
    private function build(): void
    {
        if ($this->builder !== null) {
            return;
        }

        $modelClass = $this->modelClass;
        $this->builder = $modelClass::query();

        $this->applyFilters();
        $this->applySorts();
        $this->applyIncludes();
        $this->applyCounts();
        $this->applySums();
        $this->applyAvgs();
        $this->applyMins();
        $this->applyMaxs();
    }

    /**
     * Read ?filter[key]=value from request and call the matching scope method.
     */
    private function applyFilters(): void
    {
        $filters = request()->query('filter', []);

        if (! is_array($filters) || empty($filters)) {
            return;
        }

        $model = $this->builder->getModel();
        $modelClass = get_class($model);

        foreach ($filters as $key => $value) {
            if (! in_array($key, $this->allowedFilters)) {
                continue;
            }

            $method = 'scope' . str()->camelize($key);

            if (method_exists($model, $method)) {
                $builder = $this->builder;
                \Closure::bind(
                    function () use ($method, $builder, $value) {
                        $this->{$method}($builder, $value);
                    },
                    $model,
                    $modelClass
                )();
            }
        }
    }

    /**
     * Read ?sort=col,-other from request and apply orderBy clauses.
     *
     * Prefix a column with - for descending order. Multiple columns are
     * separated by commas: ?sort=-created_at,name
     */
    private function applySorts(): void
    {
        $sort = request()->query('sort');

        if (empty($sort)) {
            $sort = $this->defaultSort;
        }

        if (empty($sort)) {
            return;
        }

        $segments = explode(',', $sort);

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if (str_starts_with($segment, '-')) {
                $column = substr($segment, 1);
                $direction = 'DESC';
            } else {
                $column = $segment;
                $direction = 'ASC';
            }

            if (in_array($column, $this->allowedSorts)) {
                $this->builder->orderBy($column, $direction);
            }
        }
    }

    /**
     * Read ?count=a,b from request and call Builder::withCount().
     * Delegates to parsedCounts() for the actual parsing.
     */
    private function applyCounts(): void
    {
        $counts = $this->parsedCounts();

        if (! empty($counts)) {
            $this->builder->withCount($counts);
        }
    }

    /**
     * Parse ?count=comments,likes from request.
     *
     * Only allowedCounts pass through.
     * Safe to call without a DB connection — does not touch the Builder.
     */
    private function parsedCounts(): array
    {
        $requested = request()->query('count', '');

        if (empty($requested) || ! is_string($requested)) {
            return [];
        }

        $counts = [];

        foreach (explode(',', $requested) as $item) {
            $item = trim($item);

            if ($item !== '' && in_array($item, $this->allowedCounts)) {
                $counts[] = $item;
            }
        }

        return $counts;
    }

    /**
     * Read ?include=a,b,a.b from request and call Builder::with().
     * Delegates to parsedIncludes() for the actual parsing.
     */
    private function applyIncludes(): void
    {
        $includes = $this->parsedIncludes();

        if (! empty($includes)) {
            $this->builder->with($includes);
        }
    }

    /**
     * Read aggregate query parameters and apply matching Builder methods.
     *
     * URL format: ?sum=products.price,orders.total&avg=reviews.rating
     * Only relation.column pairs listed in the allowlist pass through.
     */
    private function applySums(): void
    {
        $this->applyAggregate('sum', $this->allowedSums, 'withSum');
    }

    private function applyAvgs(): void
    {
        $this->applyAggregate('avg', $this->allowedAvgs, 'withAvg');
    }

    private function applyMins(): void
    {
        $this->applyAggregate('min', $this->allowedMins, 'withMin');
    }

    private function applyMaxs(): void
    {
        $this->applyAggregate('max', $this->allowedMaxs, 'withMax');
    }

    private function applyAggregate(string $param, array $allowed, string $builderMethod): void
    {
        $raw = request()->query($param, '');

        if (empty($raw) || ! is_string($raw)) {
            return;
        }

        foreach (explode(',', $raw) as $item) {
            $item = trim($item);

            if ($item === '') {
                continue;
            }

            $parts = explode('.', $item, 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$relation, $column] = $parts;

            if (! array_key_exists($relation, $allowed)) {
                continue;
            }

            $allowedColumns = $allowed[$relation];

            if (is_string($allowedColumns)) {
                $allowedColumns = [$allowedColumns];
            }

            if (in_array($column, $allowedColumns)) {
                $this->builder->{$builderMethod}($relation, $column);
            }
        }
    }

    /**
     * Parse ?include=a,b,a.b from request.
     *
     * Merges with defaultIncludes. Only allowedIncludes pass through.
     * Safe to call without a DB connection — does not touch the Builder.
     */
    private function parsedIncludes(): array
    {
        $includes = $this->defaultIncludes;
        $requested = request()->query('include', '');

        if (! empty($requested) && is_string($requested)) {
            foreach (explode(',', $requested) as $item) {
                $item = trim($item);
                if (in_array($item, $this->allowedIncludes)) {
                    $includes[] = $item;
                }
            }
        }

        return array_values(array_unique($includes));
    }

    /**
     * Parse ?fields=a,b and ?fields[relation]=c,d for transformer output.
     *
     * Root model fields:   ?fields=name,email
     *   → ['self' => ['name', 'email']]
     *
     * Bracketed fields:    ?fields[users]=name,email&fields[profile]=bio
     *   → ['self' => ['name', 'email'], 'profile' => ['bio']]
     *   (brackets matching the model's table name are mapped to 'self')
     *
     * Only fields in allowedFields are accepted for the root model.
     * Relation fields are accepted for any allowed include.
     * Safe to call without a DB connection — does not touch the Builder.
     */
    private function parsedFields(): array
    {
        $raw = request()->query('fields', null);

        if (empty($raw)) {
            return [];
        }

        $parsed = [];
        $modelClass = $this->modelClass;
        $tableName = (new $modelClass)->getTableName();

        if (is_string($raw)) {
            // ?fields=name,email  — applies to root model
            $valid = $this->filterAllowedFields(explode(',', $raw));

            if (! empty($valid)) {
                $parsed['self'] = $valid;
            }
        } elseif (is_array($raw)) {
            foreach ($raw as $key => $value) {
                $fieldList = array_map('trim', explode(',', $value));

                if ($key === 'self' || $key === $tableName) {
                    // Root model fields
                    $valid = $this->filterAllowedFields($fieldList);

                    if (! empty($valid)) {
                        $parsed['self'] = $valid;
                    }
                } elseif ($this->isAllowedRelation($key)) {
                    // Relation fields — accept any non-empty field names
                    $valid = array_values(array_filter($fieldList));

                    if (! empty($valid)) {
                        $parsed[$key] = $valid;
                    }
                }
            }
        }

        return $parsed;
    }

    /**
     * Intersect requested field names with the allowed fields whitelist.
     */
    private function filterAllowedFields(array $fields): array
    {
        return array_values(
            array_intersect(
                array_map('trim', $fields),
                $this->allowedFields
            )
        );
    }

    /**
     * Check if a key is an allowed include relation (including parent segments
     * of nested relations, e.g. 'posts' is valid when 'posts.comments' is allowed).
     */
    private function isAllowedRelation(string $key): bool
    {
        foreach ($this->allowedIncludes as $include) {
            if ($include === $key || str_starts_with($include, $key . '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the per-page value from the request, capped at maxPerPage.
     */
    private function resolvePerPage(): int
    {
        $perPage = (int) (request()->query('per_page') ?? request()->query('limit', $this->perPage));

        return min(max(1, $perPage), $this->maxPerPage);
    }
}
