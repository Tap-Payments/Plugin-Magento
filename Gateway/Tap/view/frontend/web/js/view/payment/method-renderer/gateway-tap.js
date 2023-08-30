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
		$(document).on('change', 'input[name="payment_type"]', function() {
			let selected_payment_method = $(this).val();
        		if (selected_payment_method == 'CC')
		        {
		            $('button.checkout').attr('disabled', 'disabled');
		        }
	    });
	
		console.log(window.checkoutConfig);
		var active_pk = window.checkoutConfig.payment.tap.active_pk;
		var token_returned = document.getElementById('token');
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
		var config_trans_mode = window.checkoutConfig.payment.tap.transaction_mode;
		var knet = window.checkoutConfig.payment.tap.knet;
		var guest_customerdata = customerData.get('checkout-data')();

			var response_url = ''; 
            var post_url = '';
            var firstname = '';
            var lastname = '';
            var email = '';
            var phone = '';
            var currency_code = '' ;
            var amount = '';
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
		var total = qoute.getTotals()();
		console.log()
		var total = qoute.getTotals()();
		console.log(total);
		var qoute_total_amount = total.grand_total;
		var orderId = window.checkoutConfig.payment.tap.orderId;
		var amount = window.checkoutConfig.totalsData.base_grand_total;
		var currency_code = window.checkoutConfig.quoteData.quote_currency_code;
		var total_amount = qoute_total_amount;

		return Component.extend({
			reloadPayment: function() {
            var self = this;

            fullScreenLoader.startLoader();
            jQuery.ajax({
                    url: URL,
                    type: "POST",
                    data: {
                        checked: self.creditValue(),
                        quote_id: quoteId
                     },
                    success: function(response) {
                        if (response) {
                            var deferred = jQuery.Deferred();
                            getTotalsAction([], deferred);
                             fullScreenLoader.stopLoader();
                            getPaymentInformationAction(deferred);
                            jQuery.when(deferred).done(function () {
                                isApplied(false);
                                totals.isLoading(false);
                            });
                            messageContainer.addSuccessMessage({
                                'message': message
                            });
                           totals.isLoading(true);


                        }
                    }
            	});
        	},
			initialize: function () {
            				this._super();
            				return this;
        				},
      			
			defaults: {
				template: template_path
			},

			navigate: function () {
                    var self = this;
                    getPaymentInformation().done(function () {
                        self.isVisible(true);
                    });
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
			
			getfinalAmount: function() {
				return total_amount;
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
        	getApplepayAtCheckout : function () {
        		if (window.checkoutConfig.payment.tap.applepay == 0) {
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
        	getapplepay : function () {
        		return 'APPLE PAY \n (Note:  Apple Pay will work only in Safari Browser with active Apple Pay wallet.)';
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
									if (event.status=='invalid' || event.status===undefined){
										$('button.checkout').attr('disabled', 'disabled');
									} 
									if (event.status=='success') {
										$('button.checkout').removeAttr('disabled');
									}
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
			    if (ui_mode == 'popup')  {
				    var self = this;
				    var AjaxDataResponse;
                    $.ajax({
                        type: 'POST',
                        url: window.checkoutConfig.payment.tap.redirectUrl,
                        async: false,
                        data: {
                            email: 'test@gmail.com',
                        },

                        /**
                         * Success callback
                        * @param {Object} response
                        */
                        success: function (response) {


                        AjaxDataResponse = response;

                    }
                });

                console.log("----our variables----");
				console.log(AjaxDataResponse);
				var country_code = '965';
				response_url = AjaxDataResponse.redirect.url;
               	post_url = AjaxDataResponse.post.url;
               	firstname = AjaxDataResponse.customer.first_name;
               	lastname = AjaxDataResponse.customer.last_name;
               	email = AjaxDataResponse.customer.email;
               	phone = AjaxDataResponse.customer.phone.number;
               	currency_code = AjaxDataResponse.currency;
               	amount = AjaxDataResponse.amount;
               	orderId = AjaxDataResponse.reference.order;
               	console.log(country_code);
               	console.log(post_url);
                console.log(firstname);
                console.log(phone);
                console.log(email);
                console.log(response_url);
                console.log(currency_code);
                console.log(amount);
                console.log(orderId);
                console.log(config_trans_mode);
				if (config_trans_mode == 'capture') {
					var object_trans = {
						mode: 'charge',
						charge:{
							saveCard: false,
							threeDSecure: true,
							description: orderId,
							statement_descriptor: "Sample",
							reference:{
									transaction: "txn_0001",
									order: orderId
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
		            threeDSecure: true,
		            description: "description",
		            statement_descriptor:"statement_descriptor",
		            reference:{
		              	transaction: "txn_0001",
		              	order: orderId
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

		}

		if (ui_mode == 'popup' || ui_mode == 'redirect')  {
            require(["goSellJs"],
				function(goSell) {
								goSell.config({
			  						gateway:{
										publicKey:active_pk,
										language:"en",
										contactInfo:true,
										supportedCurrencies:"all",
										supportedPaymentMethods: "all",
										saveCardOption:true,
										customerCards: true,
										notifications:'standard',
								        callback: (response) => {
  									        console.log("response", response);
							        	},
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

					require(["goSellJs"],
						function(goSell) {
						    console.log(object_trans);
						    
							var payment_type_mode = jQuery("input[name='payment_type']:checked").val();
							    console.log(ui_mode);
								if (ui_mode == 'redirect' && payment_type_mode == 'CC') {
								    $.mage.redirect(window.checkoutConfig.payment.tap.redirectUrl+'?'+'redirect=redirect');
								}
								else if (ui_mode == 'popup' && payment_type_mode == 'CC') {
								    console.log('opppppp');
									goSell.openLightBox();
								}
					            if ( ui_mode == 'token'){
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
								
								if ( payment_type_mode == 'applepay') {
									$.mage.redirect(window.checkoutConfig.payment.tap.redirectUrl+'?'+'applepay=applepay');
								}
					

								if ( ui_mode == 'token'  && payment_type_mode == 'CC') { 
									var checkList = setInterval(function () {
                    							console.log("IN TIMEOUT : " + token);
		                    					if (token) {
		                        					$.mage.redirect(window.checkoutConfig.payment.tap.redirectUrl + '?' + 'token=' + token);
		                        					clearInterval(checkList);
		                    					}
                						}, 1000);
								}
						}
					)

				}
			})
		})
		