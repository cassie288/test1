<?php
/**
 * @category    Mk
 * @package     Mk_Payment
 * @copyright  Copyright (c) 2019 
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mk_Payment_Model_Method_Mkpayment extends Mage_Payment_Model_Method_Abstract
{   
    /**
     * Can authorize flag
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Can capture flag
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Can be used in regular checkout
     * @var bool
     */
    protected $_canUseCheckout = false;

    /**
     * Payment code name
     *
     * @var string
     */
    protected $_code = 'mkpayment';

    /**
     * Check whether method is available
     *
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return $this->getConfigData('active', ($quote ? $quote->getStoreId() : null))
            && parent::isAvailable($quote);
    }

    /**
     * Get config payment action, do nothing if status is pending
     *
     * @return string|null
     */
    public function getConfigPaymentAction()
    {
        return $this->getConfigData('payment_action');
    }
    
    /**
     * Authorize payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            Mage::throwException(Mage::helper('payment')->__('Authorize action is not available.'));
        }

        $result = $this->_callApi($payment, $amount);
        
        if (!$result) {
            $error = Mage::helper('payment')->__('Error processing the request');
        } else {
            if ($result['status'] == 'Success') {
                $payment->setTransactionId($result['txn_ref']);
                $payment->setIsTransactionClosed(0);
                $payment->setTransactionAdditionalInfo(
                    Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $result
                );
            } else {
                $error = Mage::helper('payment')->__('Authorization failed');
            }
        }

        if ($error){
            Mage::throwException($error);
        }

        return $this;
    }

    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if (!$this->canCapture()) {
            Mage::throwException(Mage::helper('payment')->__('Capture action is not available.'));
        }

        $result = $this->_callApi($payment, $amount);
        
        if (!$result) {
            $error = Mage::helper('payment')->__('Error processing the request');
        } else {
            if ($result['status'] == 'Success') {
                $payment->setTransactionId($result['txn_ref']);
                $payment->setIsTransactionClosed(1);
                $payment->setTransactionAdditionalInfo(
                    Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $result
                );
            } else {
                $error = Mage::helper('payment')->__('Authorization and capture failed');
            }
        }

        if ($error){
            Mage::throwException($error);
        }

        return $this;
    }

    /**
     * Call Restful API endpoint
     * 
     * @param Varien_Object $payment
     * @param float @amount
     * 
     * @return array
     */
    private function _callApi(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();

        $data = array(
            'amount' => $amount,
            'customer_id' => $order->getCustomerId(),
            'reference' => $order->getQuoteId()
        );

        $dataEncoded = json_encode($data);

        $url = $this->getConfigData('gateway_url');

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataEncoded);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 

        //Execute the request
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }
}
