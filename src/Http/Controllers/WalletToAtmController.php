<?php

namespace Fintech\Reload\Http\Controllers;
use Exception;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Resources\WalletToAtmResource;
use Fintech\Reload\Http\Resources\WalletToAtmCollection;
use Fintech\Reload\Http\Requests\ImportWalletToAtmRequest;
use Fintech\Reload\Http\Requests\StoreWalletToAtmRequest;
use Fintech\Reload\Http\Requests\UpdateWalletToAtmRequest;
use Fintech\Reload\Http\Requests\IndexWalletToAtmRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class WalletToAtmController
 * @package Fintech\Reload\Http\Controllers
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to WalletToAtm
 * @lrd:end
 *
 */

class WalletToAtmController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *WalletToAtm* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     * @lrd:end
     *
     * @param IndexWalletToAtmRequest $request
     * @return WalletToAtmCollection|JsonResponse
     */
    public function index(IndexWalletToAtmRequest $request): WalletToAtmCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToAtmPaginate = Reload::walletToAtm()->list($inputs);

            return new WalletToAtmCollection($walletToAtmPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *WalletToAtm* resource in storage.
     * @lrd:end
     *
     * @param StoreWalletToAtmRequest $request
     * @return JsonResponse
     * @throws StoreOperationException
     */
    public function store(StoreWalletToAtmRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToAtm = Reload::walletToAtm()->create($inputs);

            if (!$walletToAtm) {
                throw (new StoreOperationException)->setModel(config('fintech.reload.wallet_to_atm_model'));
            }

            return $this->created([
                'message' => __('core::messages.resource.created', ['model' => 'Wallet To Atm']),
                'id' => $walletToAtm->id
             ]);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *WalletToAtm* resource found by id.
     * @lrd:end
     *
     * @param string|int $id
     * @return WalletToAtmResource|JsonResponse
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): WalletToAtmResource|JsonResponse
    {
        try {

            $walletToAtm = Reload::walletToAtm()->find($id);

            if (!$walletToAtm) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_atm_model'), $id);
            }

            return new WalletToAtmResource($walletToAtm);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *WalletToAtm* resource using id.
     * @lrd:end
     *
     * @param UpdateWalletToAtmRequest $request
     * @param string|int $id
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateWalletToAtmRequest $request, string|int $id): JsonResponse
    {
        try {

            $walletToAtm = Reload::walletToAtm()->find($id);

            if (!$walletToAtm) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_atm_model'), $id);
            }

            $inputs = $request->validated();

            if (!Reload::walletToAtm()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.reload.wallet_to_atm_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Wallet To Atm']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *WalletToAtm* resource using id.
     * @lrd:end
     *
     * @param string|int $id
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws DeleteOperationException
     */
    public function destroy(string|int $id)
    {
        try {

            $walletToAtm = Reload::walletToAtm()->find($id);

            if (!$walletToAtm) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_atm_model'), $id);
            }

            if (!Reload::walletToAtm()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.reload.wallet_to_atm_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Wallet To Atm']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *WalletToAtm* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     * @lrd:end
     *
     * @param string|int $id
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $walletToAtm = Reload::walletToAtm()->find($id, true);

            if (!$walletToAtm) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_atm_model'), $id);
            }

            if (!Reload::walletToAtm()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.reload.wallet_to_atm_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Wallet To Atm']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletToAtm* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param IndexWalletToAtmRequest $request
     * @return JsonResponse
     */
    public function export(IndexWalletToAtmRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToAtmPaginate = Reload::walletToAtm()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Wallet To Atm']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletToAtm* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param ImportWalletToAtmRequest $request
     * @return WalletToAtmCollection|JsonResponse
     */
    public function import(ImportWalletToAtmRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToAtmPaginate = Reload::walletToAtm()->list($inputs);

            return new WalletToAtmCollection($walletToAtmPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}
