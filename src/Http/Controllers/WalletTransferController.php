<?php

namespace Fintech\Reload\Http\Controllers;
use Exception;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Resources\WalletTransferResource;
use Fintech\Reload\Http\Resources\WalletTransferCollection;
use Fintech\Reload\Http\Requests\ImportWalletTransferRequest;
use Fintech\Reload\Http\Requests\StoreWalletTransferRequest;
use Fintech\Reload\Http\Requests\UpdateWalletTransferRequest;
use Fintech\Reload\Http\Requests\IndexWalletTransferRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class WalletTransferController
 * @package Fintech\Reload\Http\Controllers
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to WalletTransfer
 * @lrd:end
 *
 */

class WalletTransferController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *WalletTransfer* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     * @lrd:end
     *
     * @param IndexWalletTransferRequest $request
     * @return WalletTransferCollection|JsonResponse
     */
    public function index(IndexWalletTransferRequest $request): WalletTransferCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletTransferPaginate = Reload::walletTransfer()->list($inputs);

            return new WalletTransferCollection($walletTransferPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *WalletTransfer* resource in storage.
     * @lrd:end
     *
     * @param StoreWalletTransferRequest $request
     * @return JsonResponse
     * @throws StoreOperationException
     */
    public function store(StoreWalletTransferRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletTransfer = Reload::walletTransfer()->create($inputs);

            if (!$walletTransfer) {
                throw (new StoreOperationException)->setModel(config('fintech.reload.wallet_transfer_model'));
            }

            return $this->created([
                'message' => __('core::messages.resource.created', ['model' => 'Wallet Transfer']),
                'id' => $walletTransfer->id
             ]);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *WalletTransfer* resource found by id.
     * @lrd:end
     *
     * @param string|int $id
     * @return WalletTransferResource|JsonResponse
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): WalletTransferResource|JsonResponse
    {
        try {

            $walletTransfer = Reload::walletTransfer()->find($id);

            if (!$walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_transfer_model'), $id);
            }

            return new WalletTransferResource($walletTransfer);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *WalletTransfer* resource using id.
     * @lrd:end
     *
     * @param UpdateWalletTransferRequest $request
     * @param string|int $id
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateWalletTransferRequest $request, string|int $id): JsonResponse
    {
        try {

            $walletTransfer = Reload::walletTransfer()->find($id);

            if (!$walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_transfer_model'), $id);
            }

            $inputs = $request->validated();

            if (!Reload::walletTransfer()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.reload.wallet_transfer_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Wallet Transfer']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *WalletTransfer* resource using id.
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

            $walletTransfer = Reload::walletTransfer()->find($id);

            if (!$walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_transfer_model'), $id);
            }

            if (!Reload::walletTransfer()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.reload.wallet_transfer_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Wallet Transfer']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *WalletTransfer* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     * @lrd:end
     *
     * @param string|int $id
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $walletTransfer = Reload::walletTransfer()->find($id, true);

            if (!$walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_transfer_model'), $id);
            }

            if (!Reload::walletTransfer()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.reload.wallet_transfer_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Wallet Transfer']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletTransfer* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param IndexWalletTransferRequest $request
     * @return JsonResponse
     */
    public function export(IndexWalletTransferRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletTransferPaginate = Reload::walletTransfer()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Wallet Transfer']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletTransfer* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param ImportWalletTransferRequest $request
     * @return WalletTransferCollection|JsonResponse
     */
    public function import(ImportWalletTransferRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletTransferPaginate = Reload::walletTransfer()->list($inputs);

            return new WalletTransferCollection($walletTransferPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}
