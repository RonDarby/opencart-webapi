<?php

class ControllerFeedWebApi extends Controller {

	# Use print_r($json) instead json_encode($json)
	private $debug = false;


	public function cart_add() {
		$this->load->language('api/cart');

		$json = array();


		if (isset($this->request->get['product_id'])) {
			$this->load->model('catalog/product');

			$product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);

			if ($product_info) {
				if (isset($this->request->get['quantity'])) {
					$quantity = $this->request->get['quantity'];
				} else {
					$quantity = 1;
				}

				if (isset($this->request->get['option'])) {
					$option = array_filter($this->request->get['option']);
				} else {
					$option = array();	
				}

				if (!isset($this->request->get['override']) || !$this->request->get['override']) {
					$product_options = $this->model_catalog_product->getProductOptions($this->request->get['product_id']);

					foreach ($product_options as $product_option) {
						if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
							$json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
						}
					}
				}

				if (!isset($json['error']['option'])) {
					$this->cart->add($this->request->get['product_id'], $quantity, $option);

					$json['success'] = $this->language->get('text_success');

					unset($this->session->data['shipping_method']);
					unset($this->session->data['shipping_methods']);
					unset($this->session->data['payment_method']);
					unset($this->session->data['payment_methods']);					
				}
			} else {
				$json['error']['store'] = $this->language->get('error_store');
			}

			// Stock
			if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
				$json['error']['stock'] = $this->language->get('error_stock');
			}				
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}	


	public function cart_products() {
		$this->load->language('api/cart');

		$json = array();

		// Products
		$json['product'] = array();

		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}	

			if ($product['minimum'] > $product_total) {
				$json['error']['product']['minimum'][] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
			}	

			$option_data = array();

			foreach ($product['option'] as $option) {
				$option_data[] = array(
					'product_option_id'       => $option['product_option_id'],
					'product_option_value_id' => $option['product_option_value_id'],
					'name'                    => $option['name'],
					'value'                   => $option['value'],
					'type'                    => $option['type']
				);
			}

			$json['product'][] = array(
				'product_id' => $product['product_id'],
				'name'       => $product['name'],
				'model'      => $product['model'], 
				'option'     => $option_data,
				'quantity'   => $product['quantity'],
				'stock'      => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
				'price'      => $product['price'],	
				'total'      => $product['total'],	
				'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
				'reward'     => $product['reward']				
			);
		}

		// Voucher
		$json['vouchers'] = array();

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $key => $voucher) {
				$json['voucher'][] = array(
					'code'             => $voucher['code'],
					'description'      => $voucher['description'],
					'code'             => $voucher['code'],
					'from_name'        => $voucher['from_name'],
					'from_email'       => $voucher['from_email'],
					'to_name'          => $voucher['to_name'],
					'to_email'         => $voucher['to_email'],
					'voucher_theme_id' => $voucher['voucher_theme_id'], 
					'message'          => $voucher['message'],
					'amount'           => $voucher['amount']    
				);
			}
		}

	$this->response->addHeader('Content-Type: application/json');
	$this->response->setOutput(json_encode($json));		
	}


	public function random() {
		$this->init();
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$json = array('success' => true, 'products' => array());


		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['limit'])) {
			$limit = $this->request->get['limit'];
		} else {
			$limit = 4;
		}

		# -- End $_GET params --------------------------

		$products = $this->model_catalog_product->getProducts(array(
			'sort'  => 'RAND()',
			'limit' => $limit
		));

		foreach ($products as $product) {

			if ($product['image']) {
				$image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'));
			} else {
				$image = false;
			}

			if ((float)$product['special']) {
				$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')));
			} else {
				$special = false;
			}

			$json['products'][] = array(
				'id'                    => $product['product_id'],
				'name'                  => $product['name'],
				'description'           => $product['description'],
				'pirce'                 => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),
				'href'                  => $this->url->link('product/product', 'product_id=' . $product['product_id']),
				'thumb'                 => $image,
				'special'               => $special,
				'rating'                => $product['rating']
			);
		}

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}



	public function categories() {
		$this->init();
		$this->load->model('catalog/category');
		$json = array('success' => true);

		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['parent'])) {
			$parent = $this->request->get['parent'];
		} else {
			$parent = 0;
		}

		if (isset($this->request->get['level'])) {
			$level = $this->request->get['level'];
		} else {
			$level = 1;
		}

		# -- End $_GET params --------------------------


		$json['categories'] = $this->getCategoriesTree($parent, $level);

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}

	public function category() {
		$this->init();
		$this->load->model('catalog/category');
		$this->load->model('tool/image');

		$json = array('success' => true);

		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['id'])) {
			$category_id = $this->request->get['id'];
		} else {
			$category_id = 0;
		}

		# -- End $_GET params --------------------------

		$category = $this->model_catalog_category->getCategory($category_id);
		
		$json['category'] = array(
			'id'                    => $category['category_id'],
			'name'                  => $category['name'],
			'description'           => $category['description'],
			'href'                  => $this->url->link('product/category', 'category_id=' . $category['category_id'])
		);

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}


	public function products() {
		$this->init();
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$json = array('success' => true, 'products' => array());


		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['category'])) {
			$category_id = $this->request->get['category'];
		} else {
			$category_id = 0;
		}

		# -- End $_GET params --------------------------

		$products = $this->model_catalog_product->getProducts(array(
			'filter_category_id'	=> $category_id
		));

		foreach ($products as $product) {

			if ($product['image']) {
				$image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'));
			} else {
				$image = false;
			}

			if ((float)$product['special']) {
				$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')));
			} else {
				$special = false;
			}

			$json['products'][] = array(
				'id'                    => $product['product_id'],
				'name'                  => $product['name'],
				'description'           => $product['description'],
				'pirce'                 => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),
				'href'                  => $this->url->link('product/product', 'product_id=' . $product['product_id']),
				'thumb'                 => $image,
				'special'               => $special,
				'rating'                => $product['rating']
			);
		}

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}



	function cart_totals() {
		$json = array();		

		// Totals
		$this->load->model('setting/extension');

		$total_data = array();
		$total = 0;
		$taxes = $this->cart->getTaxes();

		$sort_order = array();

		$results = $this->model_setting_extension->getExtensions('total');

		foreach ($results as $key => $value) {
			$sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
		}

		array_multisort($sort_order, SORT_ASC, $results);

		foreach ($results as $result) {
			if ($this->config->get($result['code'] . '_status')) {
				$this->load->model('total/' . $result['code']);

				$this->{'model_total_' . $result['code']}->getTotal($total_data, $total, $taxes);
			}
		}

		$sort_order = array();

		foreach ($total_data as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}

		array_multisort($sort_order, SORT_ASC, $total_data);

		$json['total'] = array();

		foreach ($total_data as $total) {
			$json['total'][] = array(
				'title' => $total['title'],
				'text'  => $this->currency->format($total['value'])
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}



	public function cart_update() {
		$this->load->language('api/cart');

		$json = array();

		$this->cart->update($this->request->get['product_key'], $this->request->get['quantity']);

		$json['success'] = $this->language->get('text_success');

		unset($this->session->data['shipping_method']);
		unset($this->session->data['shipping_methods']);
		unset($this->session->data['payment_method']);
		unset($this->session->data['payment_methods']);
		unset($this->session->data['reward']);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}


	public function cart_remove() {
		$this->load->language('api/cart');

		$json = array();

		// Remove
		if (isset($this->request->get['product_key'])) {
			$this->cart->remove($this->request->get['product_key']);

			$json['success'] = $this->language->get('text_success');

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}


	public function product() {
		$this->init();
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$json = array('success' => true);

		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['id'])) {
			$product_id = $this->request->get['id'];
		} else {
			$product_id = 0;
		}

		# -- End $_GET params --------------------------

		$product = $this->model_catalog_product->getProduct($product_id);

		# product image
		if ($product['image']) {
			$image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height'));
		} else {
			$image = '';
		}

		#additional images
		$additional_images = $this->model_catalog_product->getProductImages($product['product_id']);
		$images = array();

		foreach ($additional_images as $additional_image) {
			$images[] = $this->model_tool_image->resize($additional_image['image'], $this->config->get('config_image_additional_width'), $this->config->get('config_image_additional_height'));
		}

		#specal
		if ((float)$product['special']) {
			$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')));
		} else {
			$special = false;
		}

		#discounts
		$discounts = array();
		$data_discounts =  $this->model_catalog_product->getProductDiscounts($product['product_id']);

		foreach ($data_discounts as $discount) {
			$discounts[] = array(
				'quantity' => $discount['quantity'],
				'price'    => $this->currency->format($this->tax->calculate($discount['price'], $product['tax_class_id'], $this->config->get('config_tax')))
			);
		}

		#options
		$options = array();

		foreach ($this->model_catalog_product->getProductOptions($product['product_id']) as $option) { 
			if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox' || $option['type'] == 'image') { 
				$option_value_data = array();
				
				foreach ($option['option_value'] as $option_value) {
					if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
						if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
							$price = $this->currency->format($this->tax->calculate($option_value['price'], $product['tax_class_id'], $this->config->get('config_tax')));
						} else {
							$price = false;
						}
						
						$option_value_data[] = array(
							'product_option_value_id' => $option_value['product_option_value_id'],
							'option_value_id'         => $option_value['option_value_id'],
							'name'                    => $option_value['name'],
							'image'                   => $this->model_tool_image->resize($option_value['image'], 50, 50),
							'price'                   => $price,
							'price_prefix'            => $option_value['price_prefix']
						);
					}
				}
				
				$options[] = array(
					'product_option_id' => $option['product_option_id'],
					'option_id'         => $option['option_id'],
					'name'              => $option['name'],
					'type'              => $option['type'],
					'option_value'      => $option_value_data,
					'required'          => $option['required']
				);					
			} elseif ($option['type'] == 'text' || $option['type'] == 'textarea' || $option['type'] == 'file' || $option['type'] == 'date' || $option['type'] == 'datetime' || $option['type'] == 'time') {
				$options[] = array(
					'product_option_id' => $option['product_option_id'],
					'option_id'         => $option['option_id'],
					'name'              => $option['name'],
					'type'              => $option['type'],
					'option_value'      => $option['option_value'],
					'required'          => $option['required']
				);						
			}
		}

		#minimum
		if ($product['minimum']) {
			$minimum = $product['minimum'];
		} else {
			$minimum = 1;
		}

		$json['product'] = array(
			'id'                            => $product['product_id'],
			'seo_h1'                        => $product['seo_h1'],
			'name'                          => $product['name'],
			'manufacturer'                  => $product['manufacturer'],
			'model'                         => $product['model'],
			'reward'                        => $product['reward'],
			'points'                        => $product['points'],
			'image'                         => $image,
			'images'                        => $images,
			'price'                         => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),
			'special'                       => $special,
			'discounts'                     => $discounts,
			'options'                       => $options,
			'minimum'                       => $minimum,
			'rating'                        => (int)$product['rating'],
			'description'                   => html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'),
			'attribute_groups'              => $this->model_catalog_product->getProductAttributes($product['product_id'])
		);


		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}


	/**
	 * Generation of category tree
	 * 
	 * @param  int    $parent  Prarent category id
	 * @param  int    $level   Depth level
	 * @return array           Tree
	 */
	private function getCategoriesTree($parent = 0, $level = 1) {
		$this->load->model('catalog/category');
		$this->load->model('tool/image');
		
		$result = array();

		$categories = $this->model_catalog_category->getCategories($parent);

		if ($categories && $level > 0) {
			$level--;

			foreach ($categories as $category) {

				if ($category['image']) {
					$image = $this->model_tool_image->resize($category['image'], $this->config->get('config_image_category_width'), $this->config->get('config_image_category_height'));
				} else {
					$image = false;
				}

				$result[] = array(
					'category_id'   => $category['category_id'],
					'parent_id'     => $category['parent_id'],
					'name'          => $category['name'],
					'image'         => $image,
					'href'          => $this->url->link('product/category', 'category_id=' . $category['category_id']),
					'categories'    => $this->getCategoriesTree($category['category_id'], $level)
				);
			}

			return $result;
		}
	}

	/**
	 * 
	 */
	private function init() {

		$this->response->addHeader('Content-Type: application/json');

		if (!$this->config->get('web_api_status')) {
			$this->error(10, 'API is disabled');
		}

		if ($this->config->get('web_api_key') && (!isset($this->request->get['key']) || $this->request->get['key'] != $this->config->get('web_api_key'))) {
			$this->error(20, 'Invalid secret key');
		}
	}

	/**
	 * Error message responser
	 *
	 * @param string $message  Error message
	 */
	private function error($code = 0, $message = '') {

		# setOutput() is not called, set headers manually
		header('Content-Type: application/json');

		$json = array(
			'success'       => false,
			'code'          => $code,
			'message'       => $message
		);

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			echo json_encode($json);
		}
		
		exit();
	}

}
