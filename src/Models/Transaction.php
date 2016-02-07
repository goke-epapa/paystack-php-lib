<?php
/**
 * Created by Malik Abiola.
 * Date: 07/02/2016
 * Time: 15:10
 * IDE: PhpStorm
 */

namespace Paystack\Models;

use Paystack\Contracts\TransactionInterface;
use Paystack\Helpers\Utils;
use Paystack\Resources\CustomerResource;
use Paystack\Resources\TransactionResource;

class Transaction extends Model implements TransactionInterface
{
    use Utils;

    private $transactionResource;
    private $customerResource;

    private $transactionType;

    private $customer = [];
    private $authorization = [];
    private $id;

    private $transactionRef;
    private $email;
    private $amount;
    private $transactionPlan;
    private $authorizationCode;

    private $status;
    private $currency;
    private $transaction_number;
    private $paid_at;


    public function __construct(TransactionResource $transactionResource, CustomerResource $customerResource)
    {
        $this->transactionResource = $transactionResource;
        $this->customerResource = $customerResource;
    }

    public function make($transactionType, $transactionData)
    {
        $this->setTransactionType($transactionType);
        $this->transactionRef = $this->generateTransactionRef();
        $this->amount = $transactionData['amount'];
        $this->email = $transactionData['email'];
        $this->transactionPlan = $transactionData['plan'] ?: null;
        $this->authorizationCode = $transactionData['authorization_code'] ?: null;

        return $this;
    }

    public function charge()
    {
        if (!is_null($this->transactionRef))
        {
            switch ($this->transactionType) {
                case TransactionInterface::TRANSACTION_TYPE_RETURNING:
                    return $this->transactionResource->chargeAuthorization($this->_getPayload());
                case TransactionInterface::TRANSACTION_TYPE_NEW:
                    return $this->transactionResource->initialize($this->_getPayload());
                default:
                    return new \Exception(); //@todo: replace with proper error code (invlaid transaction exception
            }
        }

        return new \Exception(); //@todo: replace with proper error code (transaction could not be created)
    }

    public function verifyTransaction($transactionRef)
    {
        $transactionData = $this->transactionResource->verify($transactionRef);

        if ($$transactionData['status'] == TransactionInterface::TRANSACTION_STATUS_SUCCESS) {
            $this->authorization = $$transactionData['authorization'];
            $this->customer = $transactionData['customer'];
            $this->amount = $transactionData['amount'];
            $this->transactionPlan = $transactionData['plan'];

            return $this;
        }

        return false;
    }

    /** @todo
     * @param $transactionCode
     */
    public function getTransactionDetails($transactionCode)
    {

    }

    /**
     * @return string
     */
    private function _getPayload()
    {
        $payload = [
            'amount'    => $this->amount,
            'reference' => $this->transactionRef,
            'email'     => $this->email
        ];
        if (!empty($this->transactionPlan)) {
            $payload['plan'] = $this->transactionPlan;
        }

        switch($this->transactionType) {
            case TransactionInterface::TRANSACTION_TYPE_RETURNING:
                $payload['authorization_code'] =  $this->authorizationCode;
                break;
        }

        return $this->toJson($payload);
    }

    //@todo properly implement this
    public function transform($transformMode)
    {
        return $this->objectToArray($this);
    }

    /**
     * @param $attributes
     * @return $this
     * @throws \Exception
     */
    public function __setAttributes($attributes)
    {
        if(is_array($attributes) && !empty($attributes)) {
            foreach($attributes as $attribute => $value) {
                $this->{$attribute} = $value;
            }

            return $this;
        }

        //@todo: put real exception here cos exception' gon be thrown either ways, so put one that makes sense
        //or something else that has more meaning
        throw new \Exception();
    }

    /**
     * @return mixed
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }

    /**
     * @param mixed $transactionType
     */
    public function setTransactionType($transactionType)
    {
        $this->transactionType = $transactionType;
    }
}
