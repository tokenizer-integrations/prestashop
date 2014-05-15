<?php

if (!defined('_PS_VERSION_'))
  exit;

// require_once '/opt/lampp/htdocs/prestashop/controllers/front/AuthController.php';

class AuthController extends AuthControllerCore { 
	//AuthControllerCore
	public function init() {
		// throw new Exception("sdsad");
		parent::init();
	}

	public function initContent(){
		parent::initContent();
		$this->setTemplate(_PS_THEME_DIR_.'../tokenizer/authentication.tpl');
	}

	public function preProcess()
	{
		parent::preProcess();
	}

	public function postProcess(){
		if (Tools::isSubmit('SubmitTokenizer'))
			$this->processTokenizerSubmitLogin();
	}

	protected function processTokenizerSubmitLogin(){
		$cfg = array(
            'id'    => '2',
            'key'   => '9f457e29b8254a9c24bc7415e853cfd5',
            'host'  => 'http://api.dev.tokenizer.com/',
            'format'=> 'json',
        );
		$customer = new Customer();
		$authentication = $customer->getByEmail($_POST['email']);
		if (!$authentication || !$customer->id)
			$this->errors[] = Tools::displayError('Authentication failed.');
		else {
			$id = $this->createAuth($cfg, $_POST['email']);
	        while($this->getAuth($cfg, $id) == 'pending') sleep(1);
	        if($this->getAuth($cfg, $id) == 'accepted'){
	        	
				$this->context->cookie->id_compare = isset($this->context->cookie->id_compare) ? $this->context->cookie->id_compare: CompareProduct::getIdCompareByIdCustomer($customer->id);
				$this->context->cookie->id_customer = (int)($customer->id);
				$this->context->cookie->customer_lastname = $customer->lastname;
				$this->context->cookie->customer_firstname = $customer->firstname;
				$this->context->cookie->logged = 1;
				$customer->logged = 1;
				$this->context->cookie->is_guest = $customer->isGuest();
				$this->context->cookie->passwd = $customer->passwd;
				$this->context->cookie->email = $customer->email;
				
				// Add customer to the context
				$this->context->customer = $customer;
				
				if (Configuration::get('PS_CART_FOLLOWING') && (empty($this->context->cookie->id_cart) || Cart::getNbProducts($this->context->cookie->id_cart) == 0) && $id_cart = (int)Cart::lastNoneOrderedCart($this->context->customer->id))
					$this->context->cart = new Cart($id_cart);
				else
				{
					$this->context->cart->id_carrier = 0;
					$this->context->cart->setDeliveryOption(null);
					$this->context->cart->id_address_delivery = Address::getFirstCustomerAddressId((int)($customer->id));
					$this->context->cart->id_address_invoice = Address::getFirstCustomerAddressId((int)($customer->id));
				}
				$this->context->cart->id_customer = (int)$customer->id;
				$this->context->cart->secure_key = $customer->secure_key;
				$this->context->cart->save();
				$this->context->cookie->id_cart = (int)$this->context->cart->id;
				$this->context->cookie->write();
				$this->context->cart->autosetProductAddress();

				Hook::exec('actionAuthentication');

				// Login information have changed, so we check if the cart rules still apply
				CartRule::autoRemoveFromCart($this->context);
				CartRule::autoAddToCart($this->context);

				if (!$this->ajax)
				{
					if (($back = Tools::getValue('back')) && $back == Tools::secureReferrer($back))
						Tools::redirect(html_entity_decode($back));
					Tools::redirect('index.php?controller='.(($this->authRedirection !== false) ? urlencode($this->authRedirection) : 'my-account'));
				}
	        }

			$return = array(
				'hasError' => !empty($this->errors),
				'errors' => $this->errors,
			);
			
		}

		
		// var_dump($_POST['email']);
	}
	function createAuth($cfg, $email, $url='%sv1/authentications.%s') {
            $data = http_build_query(array(
                    'app_id'    => $cfg['id'],
                    'app_key'   => $cfg['key'],
                    'usr_email' => $email,
                ));
            $options = array('http' => array(
                    'header'    => 'Content-type: application/x-www-form-urlencoded',
                    'method'    => 'POST',
                    'content'   => $data,
                ));
            $url = sprintf($url, $cfg['host'], $cfg['format']);

            $context = stream_context_create($options);
            $content = file_get_contents($url, false, $context);
            return json_decode($content)->id;
        }

    function getAuth($cfg, $id, $url='%sv1/authentication/%d.%s?%s') {
        $data = http_build_query(array(
                'app_id'    => $cfg['id'],
                'app_key'   => $cfg['key'],
            ));
        $options = array('http' => array('method' => 'GET'));
        $url = sprintf($url, $cfg['host'], $id, $cfg['format'], $data);

        $context = stream_context_create($options);
        $content = file_get_contents($url, false, $context);
        return json_decode($content)->state;
    }
}