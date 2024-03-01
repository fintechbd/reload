<?php

namespace Fintech\Reload\Http\Controllers;
use Exception;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Resources\WalletToBankResource;
use Fintech\Reload\Http\Resources\WalletToBankCollection;
use Fintech\Reload\Http\Requests\ImportWalletToBankRequest;
use Fintech\Reload\Http\Requests\StoreWalletToBankRequest;
use Fintech\Reload\Http\Requests\UpdateWalletToBankRequest;
use Fintech\Reload\Http\Requests\IndexWalletToBankRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class WalletToBankController
 * @package Fintech\Reload\Http\Controllers
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to WalletToBank
 * @lrd:end
 *
 */

class WalletToBankController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *WalletToBank* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     * @lrd:end
     *
     * @param IndexWalletToBankRequest $request
     * @return WalletToBankCollection|JsonResponse
     */
    public function index(IndexWalletToBankRequest $request): WalletToBankCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToBankPaginate = Reload::walletToBank()->list($inputs);

            return new WalletToBankCollection($walletToBankPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *WalletToBank* resource in storage.
     * @lrd:end
     *
     * @param StoreWalletToBankRequest $request
     * @return JsonResponse
     * @throws StoreOperationException
     */
    public function store(StoreWalletToBankRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToBank = Reload::walletToBank()->create($inputs);

            if (!$walletToBank) {
                throw (new StoreOperationException)->setModel(config('fintech.reload.wallet_to_bank_model'));
            }

            return $this->created([
                'message' => __('core::messages.resource.created', ['model' => 'Wallet To Bank']),
                'id' => $walletToBank->id
             ]);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *WalletToBank* resource found by id.
     * @lrd:end
     *
     * @param string|int $id
     * @return WalletToBankResource|JsonResponse
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): WalletToBankResource|JsonResponse
    {
        try {

            $walletToBank = Reload::walletToBank()->find($id);

            if (!$walletToBank) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_bank_model'), $id);
            }

            return new WalletToBankResource($walletToBank);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *WalletToBank* resource using id.
     * @lrd:end
     *
     * @param UpdateWalletToBankRequest $request
     * @param string|int $id
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateWalletToBankRequest $request, string|int $id): JsonResponse
    {
        try {

            $walletToBank = Reload::walletToBank()->find($id);

            if (!$walletToBank) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_bank_model'), $id);
            }

            $inputs = $request->validated();

            if (!Reload::walletToBank()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.reload.wallet_to_bank_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Wallet To Bank']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *WalletToBank* resource using id.
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

            $walletToBank = Reload::walletToBank()->find($id);

            if (!$walletToBank) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_bank_model'), $id);
            }

            if (!Reload::walletToBank()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.reload.wallet_to_bank_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Wallet To Bank']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *WalletToBank* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     * @lrd:end
     *
     * @param string|int $id
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $walletToBank = Reload::walletToBank()->find($id, true);

            if (!$walletToBank) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_bank_model'), $id);
            }

            if (!Reload::walletToBank()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.reload.wallet_to_bank_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Wallet To Bank']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletToBank* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param IndexWalletToBankRequest $request
     * @return JsonResponse
     */
    public function export(IndexWalletToBankRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToBankPaginate = Reload::walletToBank()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Wallet To Bank']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletToBank* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param ImportWalletToBankRequest $request
     * @return WalletToBankCollection|JsonResponse
     */
    public function import(ImportWalletToBankRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToBankPaginate = Reload::walletToBank()->list($inputs);

            return new WalletToBankCollection($walletToBankPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}
