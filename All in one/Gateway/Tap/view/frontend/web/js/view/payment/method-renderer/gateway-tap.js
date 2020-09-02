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
		console.log(window.checkoutConfig);
		var active_pk = window.checkoutConfig.payment.tap.active_pk;
		var token_returned = document.getElementById('token');
					console.log('token_returned');
		var post_url = window.checkoutConfig.payment.tap.post_url;
		var ui_mode = window.checkoutConfig.payment.tap.uimode;
		console.log(ui_mode);
		if ( ui_mode == 'token'){
			var template_path = 'Gateway_Tap/payment/gateway';
		}
		else {
			template_path ='Gateway_Tap/payment/gateway2';
		}
		var response_url = window.checkoutConfig.payment.tap.responseUrl;
		var transaction_mode = window.checkoutConfig.payment.tap.transaction_mode;
		var knet = window.checkoutConfig.payment.tap.knet;
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
		var config_trans_mode = 'capture';

		var total = qoute.getTotals()();
		var total_amount = total.grand_total;
		var currency_code = window.checkoutConfig.quoteData.base_currency_code;
		var amount = window.checkoutConfig.totalsData.base_grand_total;
		console.log(amount);
		if (ui_mode == 'popup' || ui_mode == 'redirect') {

			if (config_trans_mode == 'capture') {
				var object_trans = {
					mode: 'charge',
					charge:{
							saveCard: false,
							threeDSecure: true,
							description: "Test Description",
							statement_descriptor: "Sample",
							reference:{
									transaction: "txn_0001",
									order: "ord_0001"
									},
							metadata:{},
							receipt:{
								email: false,
								sms: true
								},
							redirect: response_url,
							post: post_url
					}
				}
			}

			if (config_trans_mode == 'authorize') {
					var object_trans = {
						mode :'authorize',
						authorize:{
		            			auto:{
		              				type:'VOID', 
		              				time: 100
		            			},
		            	saveCard: false,
		            	threeDSecure: false,
		            	description: "description",
		            	statement_descriptor:"statement_descriptor",
		            	reference:{
		              							transaction: "txn_0001",
		              							order: "ord_0001"
		            						},
		            					metadata:{},
		            					receipt:{
		              					email: false,
		              					sms: true
		            					},
		            				redirect: response_url,
		            				post: null

										}
								}
			}

		}
		if (ui_mode == 'popup' || ui_mode == 'redirect')  {
            require(["goSellJs"],
				function(goSell) {
								goSell.config({
			  						gateway:{
										publicKey:active_pk,
										language:"ar",
										contactInfo:true,
										supportedCurrencies:"BHD,KD,SAR",
										supportedPaymentMethods: "all",
										saveCardOption:false,
										customerCards: true,
										notifications:'standard',
										labels:{
											cardNumber:"Card Number",
											expirationDate:"MM/YY",
											cvv:"CVV",
											cardHolder:"Name on Card",
											actionButton:"Pay"
										},
										style: {
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
					  							color: 'red',
					  							iconColor: '#fa755a '
											}
										}
			  						},
			  						customer:{
										id:"",
										first_name: firstname,
										middle_name: middlename,
										last_name: lastname,
										email: email,
										phone: {
											country_code: country_code,
											number: phone
										}
			  						},
			  						order:{
										amount: total_amount,
										currency:currency_code,
										items:tap_args,
										shipping:null,
										taxes: null
			  						},

									transaction: object_trans
								});
								
							}
						)
                    }

		return Component.extend({
			initialize: function () {
            				this._super();
            				return this;
        				},

      			
			defaults: {
				template: template_path
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


        	getPaymentAcceptanceMarkSrc : function () {
                return window.checkoutConfig.payment.tap.paymentAcceptanceMarkSrc;
            },

        	getKnetAtCheckout : function () {
        		if (window.checkoutConfig.payment.tap.knet == 0) {
        			return false;
        		}
        		else {
        			return true;
        		}
        	},
        	getBenefitAtCheckout : function () {
        		if (window.checkoutConfig.payment.tap.benefit == 0) {
        			return false;
        		}
        		else {
        			return true;
        		}
        	},

        	getKnet : function () {
        		return 'KNET';
        	},

        	getbenefit : function () {
        		return 'BENEFIT';
        	},

        	getbutton: function() {
        		if ( ui_mode == 'token') {
        			return true;
        		}  
        		else {
        			return false;
        		}
        	},

        	getNaps : function () {
        		return 'NAPs';
        	},

        	getcss : function () {

        		if (ui_mode == 'token') {
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
  									currencyCode:["KWD","USD","SAR"],
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
        		}
        		else {
        			return false;
        		}
        	},



        

			afterPlaceOrder : function () {
					require(["goSellJs"],
						function(goSell) {
							var payment_type_mode = jQuery("input[name='payment_type']:checked").val();
								if (ui_mode == 'redirect' && payment_type_mode == 'CC') {
									goSell.openPaymentPage();	
								}
								else if (ui_mode == 'popup' && payment_type_mode == 'CC') {
									goSell.openLightBox();
								}
					            if ( ui_mode == 'token'){
					            	//;
								var config_trans_mode = '';
								console.log(ui_mode);
								var token = document.getElementById("token").innerHTML;
								console.log(token);
							    }
								
								console.log(payment_type_mode);
								if ( payment_type_mode == 'charge_knet') {
									$.mage.redirect(window.checkoutConfig.payment.tap.redirectUrl+'?'+'knet=knet');
								}

								if ( payment_type_mode == 'benefit') {
									$.mage.redirect(window.checkoutConfig.payment.tap.redirectUrl+'?'+'benefit=benefit');
								}
					

								if ( ui_mode == 'token'  && payment_type_mode == 'CC') { 
									$.mage.redirect(window.checkoutConfig.payment.tap.redirectUrl+'?'+'token='+token);
								}
						}
					)

				}
			})
		})
		