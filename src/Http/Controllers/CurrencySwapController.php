<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Events\CurrencySwapped;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Requests\ImportCurrencySwapRequest;
use Fintech\Reload\Http\Requests\IndexCurrencySwapRequest;
use Fintech\Reload\Http\Requests\StoreCurrencySwapRequest;
use Fintech\Reload\Http\Requests\UpdateCurrencySwapRequest;
use Fintech\Reload\Http\Resources\CurrencySwapCollection;
use Fintech\Reload\Http\Resources\CurrencySwapResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class CurrencySwapController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to CurrencySwap
 *
 * @lrd:end
 */
class CurrencySwapController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *CurrencySwap* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexCurrencySwapRequest $request): CurrencySwapCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $currencySwapPaginate = Reload::currencySwap()->list($inputs);

            return new CurrencySwapCollection($currencySwapPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *CurrencySwap* resource in storage.
     *
     * @lrd:end
     *
     * @throws StoreOperationException
     */
    public function store(StoreCurrencySwapRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $currencySwap = Reload::currencySwap()->create($inputs);

            if (! $currencySwap) {
                throw (new StoreOperationException)->setModel(config('fintech.reload.currency_swap_model'));
            }


            event(new CurrencySwapped($currencySwap));

            return $this->created([
                'message' => __('core::messages.resource.created', ['model' => 'Currency Swap']),
                'id' => $currencySwap->id,
            ]);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *CurrencySwap* resource found by id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): CurrencySwapResource|JsonResponse
    {
        try {

            $currencySwap = Reload::currencySwap()->find($id);

            if (! $currencySwap) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            return new CurrencySwapResource($currencySwap);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *CurrencySwap* resource using id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateCurrencySwapRequest $request, string|int $id): JsonResponse
    {
        try {

            $currencySwap = Reload::currencySwap()->find($id);

            if (! $currencySwap) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            $inputs = $request->validated();

            if (! Reload::currencySwap()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Currency Swap']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *CurrencySwap* resource using id.
     *
     * @lrd:end
     *
     * @return JsonResponse
     *
     * @throws ModelNotFoundException
     * @throws DeleteOperationException
     */
    public function destroy(string|int $id)
    {
        try {

            $currencySwap = Reload::currencySwap()->find($id);

            if (! $currencySwap) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            if (! Reload::currencySwap()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Currency Swap']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *CurrencySwap* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     *
     * @lrd:end
     *
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $currencySwap = Reload::currencySwap()->find($id, true);

            if (! $currencySwap) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            if (! Reload::currencySwap()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Currency Swap']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *CurrencySwap* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function export(IndexCurrencySwapRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $currencySwapPaginate = Reload::currencySwap()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Currency Swap']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *CurrencySwap* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @return CurrencySwapCollection|JsonResponse
     */
    public function import(ImportCurrencySwapRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $currencySwapPaginate = Reload::currencySwap()->list($inputs);

            return new CurrencySwapCollection($currencySwapPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}
