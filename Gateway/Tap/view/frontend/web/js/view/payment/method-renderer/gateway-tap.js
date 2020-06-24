define(
	[
		'ko',
		'jquery',
		'Magento_Checkout/js/view/payment/default',
		'Magento_Checkout/js/action/place-order',
		'Magento_Checkout/js/action/select-payment-method',
		'Magento_Customer/js/model/customer',
		'Magento_Checkout/js/checkout-data',
		'Magento_Checkout/js/model/payment/additional-validators',
		'Magento_Customer/js/customer-data',
		'Magento_Checkout/js/model/quote',
		'Magento_Checkout/js/model/totals',
		'mage/url'
	],
	function (ko, $, Component,  placeOrderAction, selectPaymentMethodAction, customer, checkoutData, additionalValidators, customerData, qoute, totals, urlBuilder) {
		'use strict';
		var savecard = false;
		var active_pk = window.checkoutConfig.payment.tap.active_pk;
		console.log(active_pk);
		var post_url = window.checkoutConfig.payment.tap.post_url;
		var ui_mode = window.checkoutConfig.payment.tap.uimode;
		var response_url = window.checkoutConfig.payment.tap.responseUrl;
		var transaction_mode = window.checkoutConfig.payment.tap.transaction_mode;
		if (window.checkoutConfig.payment.tap.save_card == '1') {
			savecard = true;
		}
		else {
			savecard = false;
		}
		var guest_customerdata = customerData.get('checkout-data')();
		if (!customer.isLoggedIn()) {
			var email =  guest_customerdata.inputFieldEmailValue;
			var firstname = guest_customerdata.shippingAddressFromData.firstname;
			var lastname = guest_customerdata.shippingAddressFromData.lastname;
			var phone = guest_customerdata.shippingAddressFromData.telephone;
		}
		else {
			var email = window.checkoutConfig.customerData.email;
			var firstname = window.checkoutConfig.customerData.firstname;
			var lastname = window.checkoutConfig.customerData.lastname;
			var phone = '';
		}
		var middlename = '';
		var country_code = '';
		var cart_items = window.checkoutConfig.quoteItemData;
		var tap_args = [];
		cart_items.forEach(function(sl_item){
	
		tap_args.push({
			id:sl_item.item_id,
			name:sl_item.name,
			description: sl_item.description,
			quantity:sl_item.qty,
			amount_per_unit:sl_item.base_price,
			discount: {
				type: 'P',
				value: '10%'
			},
			total_amount: sl_item.base_price
		})

	});



		return Component.extend({
				   	initialize: function () {
            		this._super();
            		return this;
        },
				defaults: {
					template: 'Gateway_Tap/payment/gateway'
				},
				placeOrder: function (data, event) {
					$('#tap-btn').click();
					console.log(data);
					if (event) {
						event.preventDefault();
					}
						
					var self = this,
						placeOrder,
						emailValidationResult = customer.isLoggedIn(),
						loginFormSelector = 'form[data-role=email-with-possible-login]';
					if (!customer.isLoggedIn()) {
						$(loginFormSelector).validation();
						emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
					}
					if (emailValidationResult && this.validate() && additionalValidators.validate()) {
						this.isPlaceOrderActionAllowed(false);
						placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

						$.when(placeOrder).fail(function () {
							self.isPlaceOrderActionAllowed(true);
						}).done(this.afterPlaceOrder.bind(this));
						return true;
					}
					return false;
				},
				getMailingAddress: function () {
					return window.checkoutConfig.payment.checkmo.mailingAddress;
				},

     //    		getPaymentType: function(){
     //    			$('#payment_type').on('change', function (e) {
    	// 				var optionSelected = $("option:selected", this);
    	// 				var valueSelected = this.value;
    	// 				return valueSelected;
					// });
     //    		},

        		getPaymentAcceptanceMarkSrc: function () {
                	return window.checkoutConfig.payment.tap.paymentAcceptanceMarkSrc;
            	},

            	getcss : function () {

        	
        			require(["goSell"],
						function(goSell) {
							var tap = Tapjsli(active_pk);
							var elements = tap.elements({});
							var style = {
  									base: {
    									color: '#535353',
    									lineHeight: '18px',
    									fontFamily: 'sans-serif',
    									fontSmoothing: 'antialiased',
    									fontSize: '16px',
    									'::placeholder': {
      										color: 'rgba(0, 0, 0, 0.26)',
      										fontSize:'15px'
    									}
  									},
  									invalid: {
    									color: 'red'
  									}
								};

							var labels = {
    								cardNumber:"Card Number",
    								expirationDate:"MM/YY",
    								cvv:"CVV",
    								cardHolder:"Card Holder Name"
  								};
								//payment options
							var paymentOptions = {
  									currencyCode:"all",
  									labels : labels,
  									TextDirection:'ltr'
								}
								//create element, pass style and payment options
							var card = elements.create('card', {style: style},paymentOptions);
								//mount element
								card.mount('#element-container');
								//card change event listener
								card.addEventListener('change', function(event) {
  									if (event.BIN) {
    									console.log(event.BIN)
  									}
  									if (event.loaded) {
    									console.log("UI loaded :"+event.loaded);
    									console.log("current currency is :"+card.getCurrency())
  									}
  									var displayError = document.getElementById('error-handler');
  									if (event.error) {
    									displayError.textContent = event.error.message;
  									} else {
    									displayError.textContent = '';
  									}
								});

							// Handle form submission
							var form = document.getElementById('form-container');
							form.addEventListener('submit', function(event) {
  								event.preventDefault();

  								tap.createToken(card).then(function(result) {
    								console.log(result);
    								if (result.error) {
      									// Inform the user if there was an error
      									var errorElement = document.getElementById('error-handler');
      									errorElement.textContent = result.error.message;
    								} else {
      									// Send the token to your server
      									var errorElement = document.getElementById('success');
      									errorElement.style.display = "block";
      									var tokenElement = document.getElementById('token');
      									tokenElement.textContent = result.id;
      									console.log(result.id);
    								//return result.id;
    								}
  								});
							});
						}
					)
        	},

        		
        


        

			afterPlaceOrder : function () {
				var returned_token = document.getElementById("token").innerHTML;
				$.mage.redirect(window.checkoutConfig.payment.tap.redirectUrl+'?'+'token='+returned_token);

			}
		})
	})
		