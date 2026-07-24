<?php

namespace Lightpack\Database\Lucid;

class AttributeHandler
{
    /**
     * @var \stdClass Attributes container
     */
    protected $data;

    /**
     * @var array Attributes to be hidden
     */
    protected $hidden = [];

    /**
     * @var bool Enable timestamps
     */
    protected $timestamps = false;

    /**
     * @var array Cast definitions
     */
    protected array $casts = [];

    /**
     * @var CastHandler
     */
    protected CastHandler $castHandler;

    /**
     * @var array Track modified attributes
     */
    protected array $dirty = [];

    /**
     * @var array Virtual attributes (not real DB columns, e.g. withCount)
     */
    protected array $virtual = [];

    public function __construct()
    {
        $this->data = new \stdClass;
        $this->castHandler = new CastHandler;
    }

    /**
     * Get attribute with casting.
     */
    public function get(string $key, $default = null)
    {
        if (! property_exists($this->data, $key)) {
            return $default;
        }

        $value = $this->data->{$key};

        // Return null as is
        if ($value === null) {
            return null;
        }

        if ($castType = $this->getCastType($key)) {
            return $this->castHandler->cast($value, $castType);
        }

        return $value;
    }

    /**
     * Set attribute with casting.
     */
    public function set(string $key, $value): void
    {
        if ($value !== null && $castType = $this->getCastType($key)) {
            $value = $this->castHandler->uncast($value, $castType);
        }

        // Track modification only if value actually changes
        if (! isset($this->data->{$key}) || $this->data->{$key} !== $value) {
            $this->dirty[$key] = true;
        }

        $this->data->{$key} = $value;
    }

    /**
     * Set raw attribute from database without casting.
     */
    public function setRaw(string $key, $value): void
    {
        if ($value !== null && $castType = $this->getCastType($key)) {
            $value = $this->castHandler->cast($value, $castType);
        }

        $this->data->{$key} = $value;
        unset($this->dirty[$key]); // Clear modification flag for raw sets
    }

    /**
     * Set a virtual attribute that is not a real DB column.
     * These are excluded from toDatabaseArray().
     */
    public function setVirtual(string $key, $value): void
    {
        $this->data->{$key} = $value;
        $this->virtual[$key] = true;
        unset($this->dirty[$key]);
    }

    /**
     * Check if attribute exists.
     */
    public function has(string $key): bool
    {
        return property_exists($this->data, $key);
    }

    /**
     * Fill attributes.
     */
    public function fill(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Fill attributes from database.
     */
    public function fillRaw(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->setRaw($key, $value);
        }
        $this->clearDirty(); // Clear modified state after database load
    }

    /**
     * Get all attributes.
     */
    public function all(): \stdClass
    {
        return $this->data;
    }

    /**
     * Get attributes as array, respecting hidden fields.
     */
    public function toArray(): array
    {
        $data = (array) $this->data;

        return array_diff_key($data, array_flip($this->hidden));
    }

    /**
     * Get all attributes as array for database operations.
     * Values loaded via setRaw() are in PHP-cast format (e.g. bool false,
     * array, DateTime), while values set via set() are already uncasted
     * to DB format (e.g. int 0, JSON string, formatted date string).
     * We inspect the actual PHP type to determine which path set the value
     * and only transform values that are still in PHP-cast format.
     */
    public function toDatabaseArray(): array
    {
        $data = (array) $this->data;
        $result = [];

        foreach ($data as $key => $value) {
            // Skip eager-loaded relations (Model or Collection objects)
            if ($value instanceof Model || $value instanceof Collection) {
                continue;
            }

            // Skip virtual attributes (withCount/withSum etc.)
            if (isset($this->virtual[$key])) {
                continue;
            }

            if ($value === null) {
                $result[$key] = null;
            } elseif (is_bool($value)) {
                // setRaw() casts DB int to PHP bool; set() already stores int
                $result[$key] = $value ? 1 : 0;
            } elseif (is_array($value)) {
                // setRaw() casts DB JSON string to PHP array; set() already stores JSON string
                $result[$key] = json_encode($value);
            } elseif ($value instanceof \DateTimeInterface) {
                // setRaw() casts DB string to DateTime; set() already stores formatted string
                $castType = $this->getCastType($key);
                $result[$key] = match($castType) {
                    'date' => $value->format('Y-m-d'),
                    'timestamp' => (string) $value->getTimestamp(),
                    default => $value->format('Y-m-d H:i:s'),
                };
            } else {
                // Already in DB format (set() uncasted it, or no cast defined)
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Set hidden attributes.
     */
    public function setHidden(array $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * Set timestamps flag.
     */
    public function setTimestamps(bool $timestamps): void
    {
        $this->timestamps = $timestamps;
    }

    /**
     * Set cast definitions.
     */
    public function setCasts(array $casts): void
    {
        $this->casts = $casts;
    }

    /**
     * Get cast type for attribute.
     */
    protected function getCastType(string $key): ?string
    {
        return $this->casts[$key] ?? null;
    }

    /**
     * Update timestamps.
     * Uses set() method to respect casting if timestamps are in $casts array.
     */
    public function updateTimestamps(bool $updating = true): void
    {
        if (! $this->timestamps) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        if ($updating) {
            $this->set('updated_at', $now);

            return;
        }

        $this->set('created_at', $now);
    }

    /**
     * Get attributes in dirty state.
     */
    public function getDirty(): array
    {
        return array_keys($this->dirty);
    }

    /**
     * Check if model or specific attributes are in dirty state.
     */
    public function isDirty(?string $key = null): bool
    {
        if ($key === null) {
            return ! empty($this->dirty);
        }

        return isset($this->dirty[$key]);
    }

    /**
     * Clear modified attributes tracking.
     */
    public function clearDirty(): void
    {
        $this->dirty = [];
    }

    /**
     * Check if an attribute is virtual (not a real DB column).
     */
    public function isVirtual(string $key): bool
    {
        return isset($this->virtual[$key]);
    }
}
