<?php

namespace Fintech\Reload\Http\Controllers;
use Exception;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Resources\WalletToWalletResource;
use Fintech\Reload\Http\Resources\WalletToWalletCollection;
use Fintech\Reload\Http\Requests\ImportWalletToWalletRequest;
use Fintech\Reload\Http\Requests\StoreWalletToWalletRequest;
use Fintech\Reload\Http\Requests\UpdateWalletToWalletRequest;
use Fintech\Reload\Http\Requests\IndexWalletToWalletRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class WalletToWalletController
 * @package Fintech\Reload\Http\Controllers
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to WalletToWallet
 * @lrd:end
 *
 */

class WalletToWalletController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *WalletToWallet* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     * @lrd:end
     *
     * @param IndexWalletToWalletRequest $request
     * @return WalletToWalletCollection|JsonResponse
     */
    public function index(IndexWalletToWalletRequest $request): WalletToWalletCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToWalletPaginate = Reload::walletToWallet()->list($inputs);

            return new WalletToWalletCollection($walletToWalletPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *WalletToWallet* resource in storage.
     * @lrd:end
     *
     * @param StoreWalletToWalletRequest $request
     * @return JsonResponse
     * @throws StoreOperationException
     */
    public function store(StoreWalletToWalletRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToWallet = Reload::walletToWallet()->create($inputs);

            if (!$walletToWallet) {
                throw (new StoreOperationException)->setModel(config('fintech.reload.wallet_to_wallet_model'));
            }

            return $this->created([
                'message' => __('core::messages.resource.created', ['model' => 'Wallet To Wallet']),
                'id' => $walletToWallet->id
             ]);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *WalletToWallet* resource found by id.
     * @lrd:end
     *
     * @param string|int $id
     * @return WalletToWalletResource|JsonResponse
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): WalletToWalletResource|JsonResponse
    {
        try {

            $walletToWallet = Reload::walletToWallet()->find($id);

            if (!$walletToWallet) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            return new WalletToWalletResource($walletToWallet);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *WalletToWallet* resource using id.
     * @lrd:end
     *
     * @param UpdateWalletToWalletRequest $request
     * @param string|int $id
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateWalletToWalletRequest $request, string|int $id): JsonResponse
    {
        try {

            $walletToWallet = Reload::walletToWallet()->find($id);

            if (!$walletToWallet) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            $inputs = $request->validated();

            if (!Reload::walletToWallet()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Wallet To Wallet']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *WalletToWallet* resource using id.
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

            $walletToWallet = Reload::walletToWallet()->find($id);

            if (!$walletToWallet) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            if (!Reload::walletToWallet()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Wallet To Wallet']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *WalletToWallet* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     * @lrd:end
     *
     * @param string|int $id
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $walletToWallet = Reload::walletToWallet()->find($id, true);

            if (!$walletToWallet) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            if (!Reload::walletToWallet()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Wallet To Wallet']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletToWallet* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param IndexWalletToWalletRequest $request
     * @return JsonResponse
     */
    public function export(IndexWalletToWalletRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToWalletPaginate = Reload::walletToWallet()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Wallet To Wallet']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletToWallet* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param ImportWalletToWalletRequest $request
     * @return WalletToWalletCollection|JsonResponse
     */
    public function import(ImportWalletToWalletRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToWalletPaginate = Reload::walletToWallet()->list($inputs);

            return new WalletToWalletCollection($walletToWalletPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}
