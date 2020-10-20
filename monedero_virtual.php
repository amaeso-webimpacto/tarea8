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

use DoctrineExtensions\Query\Sqlite\Round;
use PhpParser\Node\Expr\Isset_;
use Symfony\Component\Validator\Constraints\NotNull;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Monedero_virtual extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'monedero_virtual';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'yomisma';
        $this->need_instance = 0;
        $this->controllers = array('payment', 'validation');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Monedero Virtual');
        $this->description = $this->l('monedero virtual (tarea 8 )');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('actionOrderDetail') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('actionOrderStatusUpdate') &&
            $this->registerHook('actionPaymentConfirmation') &&
            $this->registerHook('actionUpdateQuantity') &&
            $this->registerHook('actionValidateOrder') &&
            $this->registerHook('displayOrderConfirmation') &&
            $this->registerHook('displayPayment');
    }

    public function uninstall()
    {

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitMonedero_virtualModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMonedero_virtualModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-euro"></i>',
                        'desc' => $this->l('Conversión euro pagado - euro monedero'),
                        'name' => 'MONEDERO_VIRTUAL_VALOR_EURO',
                        'label' => $this->l('Valor euro'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(

            'MONEDERO_VIRTUAL_VALOR_EURO' => Configuration::get('MONEDERO_VIRTUAL_VALOR_EURO'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */
    public function hookPayment($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

        if (in_array($currency->iso_code, $this->limited_currencies) == false)
            return false;

        $this->smarty->assign('module_dir', $this->_path);

        
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        /*if ($this->active == false)
            return;

        $order = $params['objOrder'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR'))
            $this->smarty->assign('status', 'ok');

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'total' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
        ));*/
        $state = $params['order']->getCurrentState();
        if (in_array($state, array(Configuration::get('PS_OS_CHEQUE'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID')))) {
            $this->smarty->assign(array(
                'total_to_pay' => Tools::displayPrice(
                    $params['order']->getOrdersTotalPaid(),
                    new Currency($params['order']->id_currency),
                    false
                ),
                'shop_name' => $this->context->shop->name,
                'checkName' => $this->checkName,
                'checkAddress' => Tools::nl2br($this->address),
                'status' => 'ok',
                'id_order' => $params['order']->id
            ));
            if (isset($params['order']->reference) && !empty($params['order']->reference)) {
                $this->smarty->assign('reference', $params['order']->reference);
            }
        } else {
            $this->smarty->assign('status', 'failed');
        }

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hookActionOrderDetail()
    {
        //detalles del pedido
    }

    public function hookActionOrderStatusUpdate($params)
    {
    }

    //Llamado DESPUÉS de que cambia el estado de un pedido
    public function hookActionOrderStatusPostUpdate($params)
    {
       
        //definicion variables 
        $id_order = $params['id_order'];
        $customer_id = $params['cart']->id_customer;

        /*ESTADO DEL PEDIDO*/
        $current_state_order  = Db::getInstance()->getValue(
            sprintf(
                'SELECT (current_state) FROM ps_orders WHERE id_order = %d',
                (int)pSQL($id_order)
            )
        );

        /*lo que ha pagado el cliente POR ESE PEDIDO 
        SOLO cuando el pago ha sido efectuado*/
        $total_paid_real = Db::getInstance()->getValue(
            sprintf(
                'SELECT (total_paid_real) FROM ps_orders WHERE id_order = %d',
                (int)pSQL($id_order)
            )
        );

        /*lo que cuesta CADA PEDIDO*/
        $total_paid  = Db::getInstance()->getValue(
            sprintf(
                'SELECT (total_paid) FROM ps_orders WHERE id_order = %d',
                (int)pSQL($id_order)
            )
        );

        //variables que vienen el input en configuracion del modulo
        $valor_euro = Configuration::get('MONEDERO_VIRTUAL_VALOR_EURO');
        

        $cantidad_recompensa = $valor_euro * $total_paid;

        //comprobamos que no existe ningun movimiento de este pedido en la bd monedero_virtual:
        //1º declaramos la var para que la busque en la bd
        $id_order_bd = Db::getInstance()->getValue(
            sprintf(
                'SELECT (id_order) FROM ps_monedero_virtual WHERE id_order = %d',
                (int)pSQL($id_order)
            )
        );

        //2º si existe, que no haga nada
        //SI PAGO ACEPTADO
        if(empty($id_order_bd) && $current_state_order == 2)      
        {
                $insert = array(
                    'customer_id' => $customer_id,
                    'id_order' => $id_order,
                    'cantidad' => $cantidad_recompensa, //los euritos gastados
                    'current_state' => $current_state_order,
            );
            //guardar bd
            Db::getInstance()->insert('monedero_virtual', $insert); 
        }


        //SI PEDIDO CANCELADO
        if($current_state_order == 6) {
   
            //borrar de la bd
            Db::getInstance()->getValue(
                sprintf(
                    'DELETE FROM ps_monedero_virtual WHERE id_order = %d',
                    (int)pSQL($id_order)
                )
            );

        }
    }
        
    
    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $customer_id = $params['cart']->id_customer;
        
        //calculo valor pedido en curso
        $id_currency = intval(Configuration::get('PS_CURRENCY_DEFAULT'));			
		$currency = new Currency(intval($id_currency));
		/* @Cart.php function getOrderTotal($withTaxes = true, $type = 3)
		 *  $withTaxes true == con impuestos incluidos 
		 *  $type 3 == total del carrito, productos y descuentos
		*/ 
		$valor_pedido_en_curso = number_format(
			Tools::convertPrice($params['cart']->getOrderTotal(true, 3), $currency), 
			2, '.', ''
        );

        //cantidad de recompensa de un cliente (acumulado en monedero)
        $suma_monedero = Db::getInstance()->getValue(
            sprintf(
                'SELECT SUM(cantidad) FROM ps_monedero_virtual WHERE customer_id = %d',
                (int)pSQL($customer_id)
                )
            );

        $suma_monedero_sd = number_format($suma_monedero, 2, ',', ' ');
      
        $variables = array(
            'customer_id' => $customer_id,
            'cantidad' => - $valor_pedido_en_curso, //los euritos gastados
            'suma_monedero' => $suma_monedero,
            'suma_monedero_sd' => $suma_monedero_sd
        );

        //llamada a pago con monedero en checkout
        //Option 0 = muestra si o si la opción de pagar con el monedero y la cantidad
        $option = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $option->setCallToActionText($this->l("Paga con tu monedero virtual, llevas acumulados $suma_monedero_sd €"))
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true));
            
            return [
            $option
        ];

        /*
        if($valor_pedido_en_curso < $suma_monedero){

        //Opcion 1 = pedido menor a monedero
        $option1 = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $option1->setCallToActionText($this->l("Paga con tu monedero virtual, llevas acumulados $suma_monedero_sd €"))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($this->l("Enhorabuena, llevas acumulados $suma_monedero_sd €"));

            return [
            $option1
        ];

       } else {

           //Opcion 2 = pedido mayor a monedero
        $option2 = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $option2->setCallToActionText($this->l("Paga con tu monedero virtual, llevas acumulados $suma_monedero_sd €"))     
        
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($this->l("Ups! Todavía no puedes pagar con tu monedero! Tienes $suma_monedero_sd € acumulados"));
           return  [
            $option2
        ];
       }
       */

    }


    public function hookActionPaymentConfirmation($params)
    {
    }

    public function hookActionUpdateQuantity()
    {
        //NO NECESARIO DE MOMENTO
        //Después de actualizar la cantidad de un producto
        //La cantidad se actualiza solo cuando un cliente realiza su pedido de manera efectiva
    }

    public function hookActionValidateOrder($params)
    {/*
        $customer_id = $params['cart']->id_customer;
        $id_order = $params['id_order'];
        $current_state_order  = Db::getInstance()->getValue(
            sprintf(
                'SELECT (current_state) FROM ps_orders WHERE id_order = %d',
                (int)pSQL($id_order)
            )
        );
        $suma_monedero = Db::getInstance()->getValue(
            sprintf(
                'SELECT SUM(cantidad) FROM ps_monedero_virtual WHERE customer_id = %d',
                (int)pSQL($customer_id)
                )
            );
        //calculo valor pedido en curso
        $id_currency = intval(Configuration::get('PS_CURRENCY_DEFAULT'));			
		$currency = new Currency(intval($id_currency));
		/* @Cart.php function getOrderTotal($withTaxes = true, $type = 3)
		 *  $withTaxes true == con impuestos incluidos 
		 *  $type 3 == total del carrito, productos y descuentos
		
		$valor_pedido_en_curso = number_format(
			Tools::convertPrice($params['cart']->getOrderTotal(true, 3), $currency), 
			2, '.', ''
        );

        if($valor_pedido_en_curso < $suma_monedero){
            $insert_conditional = array(
                'customer_id' => $customer_id,
                'id_order' => $id_order,
                'cantidad' => - $valor_pedido_en_curso, //valor del pedido
                'current_state' => $current_state_order,
            );
            //guardar bd
            Db::getInstance()->insert('monedero_virtual', $insert_conditional); 

        }


*/
    }


    public function hookDisplayOrderConfirmation()
    {
        /* Place your code here. */
    }

    public function hookDisplayPayment()
    {

    }
}

