<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
class Monedero_virtualValidationModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = true;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'ps_monedero_virtual') {
                $authorized = true;
                break;
            }
       
            
        if (!$authorized) {
            die($this->trans('This payment method is not available.', array(), 'Modules.Monedero_virtual.Shop'));
        }

        $customer = new Customer($cart->id_customer);
        
        //Variables para hacer condicion
        $customer_id = $customer->id;
        
        $suma_monedero = Db::getInstance()->getValue(
            sprintf(
                'SELECT SUM(cantidad) FROM ps_monedero_virtual WHERE customer_id = %d',
                (int)pSQL($customer_id)
                )
            );
        $suma_monedero_sd = number_format($suma_monedero, 2, ',', ' ');

       

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
       
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        if($total < $suma_monedero)
        {
            //Guarda el movimiento en la bd, con el valor del pedido restando
            $insert = array(
                'customer_id' => $customer_id,
                //'id_order' => $id_order,
                'cantidad' => - $total, //valor del pedido
                //'current_state' => $current_state_order,
            );
            
            Db::getInstance()->insert('monedero_virtual', $insert); 
            
            //El estado del pedido cambia a pago aceptado
        $this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_PAYMENT'), $total, $this->module->displayName, null, array(), (int)$currency->id, false, $customer->secure_key);
        Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
           
        } else {
            $this->trans("Ups! Todavía no puedes pagar con tu monedero! Tienes $suma_monedero_sd € acumulados");
            Tools::redirect('index.php?controller=order&step=1');
        }
        
        
        //$this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_CHEQUE'), $total, $this->module->displayName, null, (int)$currency->id, false, $customer->secure_key);
        //Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);

    }








        //PRUEBA QUE NO IBA
        //OJO, TIENE LOS CONDICIONALES QUE NECESITAS
        /*
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $customer = new Customer($cart->id_customer);
        $customer_id = $customer->id;


        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $customer_id = $customer->id;
        

        $suma_monedero = Db::getInstance()->getValue(
            sprintf(
                'SELECT SUM(cantidad) FROM ps_monedero_virtual WHERE customer_id = %d',
                (int)pSQL($customer_id)
                )
            );
        
        $suma_monedero_sd = number_format($suma_monedero, 2, ',', ' ');
        
        if($total < $suma_monedero)
        { 
            dump($this);
            //Guarda el movimiento en la bd, con el valor del pedido restando
            $insert = array(
                'customer_id' => $customer_id,
                //'id_order' => $id_order,
                'cantidad' => - $total, //valor del pedido
                //'current_state' => $current_state_order,
            );
            
            Db::getInstance()->insert('monedero_virtual', $insert); 
            
            //El estado del pedido cambia a pago aceptado
            $this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_PAYMENT'), $total, $this->module->displayName, null, array(), (int)$currency->id, false, $customer->secure_key);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
           }
        else 
        {
            $option2 = $this->l("Ups! Todavía no puedes pagar con tu monedero! Tienes $suma_monedero_sd € acumulados");
            
            
            return [$option2];
        }
       
        */
      
        /*TODO LO QUE VENIA EN EL MODULO
        // If the module is not active anymore, no need to process anything.
        
        if ($this->module->active == false) {
            die;
        }

        //You'll have to get the correct values :)hay que cambiarlo
        
        $cart_id = 1;
        $customer_id = 1;
        $amount = 100.00;

        //Restore the context from the $cart_id & the $customer_id to process the validation properly.
        Context::getContext()->cart = new Cart((int) $cart_id);
        Context::getContext()->customer = new Customer((int) $customer_id);
        Context::getContext()->currency = new Currency((int) Context::getContext()->cart->id_currency);
        Context::getContext()->language = new Language((int) Context::getContext()->customer->id_lang);

        $secure_key = Context::getContext()->customer->secure_key;

        if ($this->isValidOrder() === true) {
            $payment_status = Configuration::get('PS_OS_PAYMENT');
            $message = null;
        } else {
            $payment_status = Configuration::get('PS_OS_ERROR');

            // Add a message to explain why the order has not been validated
            
            $message = $this->module->l('An error occurred while processing payment');
        }

        $module_name = $this->module->displayName;
        $currency_id = (int) Context::getContext()->currency->id;

        return $this->module->validateOrder($cart_id, $payment_status, $amount, $module_name, $message, array(), $currency_id, false, $secure_key);
        FIN INFOR ORIGINAL */
    }

    protected function isValidOrder()
    {
        /*
         * Add your checks right there
         */
        return true;
    }
}
