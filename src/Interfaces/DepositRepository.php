<?php

namespace Fintech\Reload\Interfaces;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use MongoDB\Laravel\Eloquent\Model as MongodbModel;

/**
 * Interface DepositRepository
 */
interface DepositRepository
{
    /**
     * return a list or pagination of items from
     * filtered options
     *
     * @return Paginator|Collection
     */
    public function list(array $filters = []);

    /**
     * Create a new entry resource
     *
     * @return EloquentModel|MongodbModel|null
     */
    public function create(array $attributes = []);

    /**
     * find and update a resource attributes
     *
     * @return EloquentModel|MongodbModel|null
     */
    public function update(int|string $id, array $attributes = []);

    /**
     * find and delete a entry from records
     *
     * @param bool $onlyTrashed
     * @return EloquentModel|MongodbModel|null
     */
    public function find(int|string $id, $onlyTrashed = false);

    /**
     * find and delete a entry from records
     */
    public function delete(int|string $id);

    /**
     * find and restore a entry from records
     *
     * @throws InvalidArgumentException
     */
    public function restore(int|string $id);
}
