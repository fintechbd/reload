<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Auth\Facades\Auth;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Requests\ImportRequestMoneyRequest;
use Fintech\Reload\Http\Requests\IndexRequestMoneyRequest;
use Fintech\Reload\Http\Requests\StoreRequestMoneyRequest;
use Fintech\Reload\Http\Requests\UpdateRequestMoneyRequest;
use Fintech\Reload\Http\Resources\RequestMoneyCollection;
use Fintech\Reload\Http\Resources\RequestMoneyResource;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class RequestMoneyController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to RequestMoney
 *
 * @lrd:end
 */
class RequestMoneyController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *RequestMoney* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexRequestMoneyRequest $request): RequestMoneyCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();
            //$inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'request_money'])->first()->getKey();
            $inputs['transaction_form_code'] = 'request_money';
            //$inputs['service_id'] = Business::serviceType()->list(['service_type_slug'=>'request_money']);
            //$inputs['service_type_slug'] = 'request_money';

            $requestMoneyPaginate = Reload::requestMoney()->list($inputs);

            return new RequestMoneyCollection($requestMoneyPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *RequestMoney* resource in storage.
     *
     * @lrd:end
     *
     */
    public function store(StoreRequestMoneyRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();
            if ($request->input('user_id') > 0) {
                $user_id = $request->input('user_id');
                $depositor = Auth::user()->find($request->input('user_id'));
            } else {
                $depositor = $request->user('sanctum');
            }
            if (Transaction::orderQueue()->addToQueueUserWise(($user_id ?? $depositor->getKey())) > 0) {

                $requestMoney = Reload::requestMoney()->create($inputs);

                if (!$requestMoney) {
                    throw (new StoreOperationException)->setModel(config('fintech.reload.request_money_model'));
                }

                return $this->created([
                    'message' => __('core::messages.resource.created', ['model' => 'Request Money']),
                    'id' => $requestMoney->id,
                ]);
            }else {
                throw new Exception('Your another order is in process...!');
            }
        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *RequestMoney* resource found by id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): RequestMoneyResource|JsonResponse
    {
        try {

            $requestMoney = Reload::requestMoney()->find($id);

            if (! $requestMoney) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.request_money_model'), $id);
            }

            return new RequestMoneyResource($requestMoney);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *RequestMoney* resource using id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function update(UpdateRequestMoneyRequest $request, string|int $id): JsonResponse
    {
        try {

            $requestMoney = Reload::requestMoney()->find($id);

            if (! $requestMoney) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.request_money_model'), $id);
            }

            $inputs = $request->validated();

            if (! Reload::requestMoney()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.reload.request_money_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Request Money']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *RequestMoney* resource using id.
     *
     * @lrd:end
     *
     * @param string|int $id
     * @return JsonResponse
     *
     */
    public function destroy(string|int $id): JsonResponse
    {
        try {

            $requestMoney = Reload::requestMoney()->find($id);

            if (! $requestMoney) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.request_money_model'), $id);
            }

            if (! Reload::requestMoney()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.reload.request_money_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Request Money']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *RequestMoney* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     *
     * @lrd:end
     *
     * @param string|int $id
     * @return JsonResponse
     */
    public function restore(string|int $id): JsonResponse
    {
        try {

            $requestMoney = Reload::requestMoney()->find($id, true);

            if (! $requestMoney) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.request_money_model'), $id);
            }

            if (! Reload::requestMoney()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.reload.request_money_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Request Money']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create an exportable list of the *RequestMoney* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function export(IndexRequestMoneyRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            //$requestMoneyPaginate = Reload::requestMoney()->export($inputs);
            Reload::requestMoney()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Request Money']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create an exportable list of the *RequestMoney* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param ImportRequestMoneyRequest $request
     * @return RequestMoneyCollection|JsonResponse
     */
    public function import(ImportRequestMoneyRequest $request): RequestMoneyCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $requestMoneyPaginate = Reload::requestMoney()->list($inputs);

            return new RequestMoneyCollection($requestMoneyPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}
