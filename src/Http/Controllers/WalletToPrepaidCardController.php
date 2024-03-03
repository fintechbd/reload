<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Requests\ImportWalletToPrepaidCardRequest;
use Fintech\Reload\Http\Requests\IndexWalletToPrepaidCardRequest;
use Fintech\Reload\Http\Requests\StoreWalletToPrepaidCardRequest;
use Fintech\Reload\Http\Requests\UpdateWalletToPrepaidCardRequest;
use Fintech\Reload\Http\Resources\WalletToPrepaidCardCollection;
use Fintech\Reload\Http\Resources\WalletToPrepaidCardResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class WalletToPrepaidCardController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to WalletToPrepaidCard
 *
 * @lrd:end
 */
class WalletToPrepaidCardController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *WalletToPrepaidCard* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexWalletToPrepaidCardRequest $request): WalletToPrepaidCardCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToPrepaidCardPaginate = Reload::walletToPrepaidCard()->list($inputs);

            return new WalletToPrepaidCardCollection($walletToPrepaidCardPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *WalletToPrepaidCard* resource in storage.
     *
     * @lrd:end
     *
     * @throws StoreOperationException
     */
    public function store(StoreWalletToPrepaidCardRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToPrepaidCard = Reload::walletToPrepaidCard()->create($inputs);

            if (! $walletToPrepaidCard) {
                throw (new StoreOperationException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'));
            }

            return $this->created([
                'message' => __('core::messages.resource.created', ['model' => 'Wallet To Prepaid Card']),
                'id' => $walletToPrepaidCard->id,
            ]);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *WalletToPrepaidCard* resource found by id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): WalletToPrepaidCardResource|JsonResponse
    {
        try {

            $walletToPrepaidCard = Reload::walletToPrepaidCard()->find($id);

            if (! $walletToPrepaidCard) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            return new WalletToPrepaidCardResource($walletToPrepaidCard);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *WalletToPrepaidCard* resource using id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateWalletToPrepaidCardRequest $request, string|int $id): JsonResponse
    {
        try {

            $walletToPrepaidCard = Reload::walletToPrepaidCard()->find($id);

            if (! $walletToPrepaidCard) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            $inputs = $request->validated();

            if (! Reload::walletToPrepaidCard()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Wallet To Prepaid Card']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *WalletToPrepaidCard* resource using id.
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

            $walletToPrepaidCard = Reload::walletToPrepaidCard()->find($id);

            if (! $walletToPrepaidCard) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            if (! Reload::walletToPrepaidCard()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Wallet To Prepaid Card']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *WalletToPrepaidCard* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     *
     * @lrd:end
     *
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $walletToPrepaidCard = Reload::walletToPrepaidCard()->find($id, true);

            if (! $walletToPrepaidCard) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            if (! Reload::walletToPrepaidCard()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Wallet To Prepaid Card']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletToPrepaidCard* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function export(IndexWalletToPrepaidCardRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToPrepaidCardPaginate = Reload::walletToPrepaidCard()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Wallet To Prepaid Card']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletToPrepaidCard* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @return WalletToPrepaidCardCollection|JsonResponse
     */
    public function import(ImportWalletToPrepaidCardRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToPrepaidCardPaginate = Reload::walletToPrepaidCard()->list($inputs);

            return new WalletToPrepaidCardCollection($walletToPrepaidCardPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}
