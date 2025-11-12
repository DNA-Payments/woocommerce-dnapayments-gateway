/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./client/blocks/components/credit-card-fields.js":
/*!********************************************************!*\
  !*** ./client/blocks/components/credit-card-fields.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DnapaymentsCreditCardFields: () => (/* binding */ DnapaymentsCreditCardFields)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @woocommerce/blocks-checkout */ "@woocommerce/blocks-checkout");
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _common_card_scheme__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/card-scheme */ "./client/common/card-scheme.js");
/* harmony import */ var _common_create_hosted_fields__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/create-hosted-fields */ "./client/common/create-hosted-fields.js");
/* harmony import */ var _common_create_modal__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/create-modal */ "./client/common/create-modal.js");
/* harmony import */ var _common_log__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/log */ "./client/common/log.js");
/* harmony import */ var _common_constants__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/constants */ "./client/common/constants.js");
/* harmony import */ var _utils_get_settings__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/get-settings */ "./client/blocks/utils/get-settings.js");
/* harmony import */ var _utils_place_order_button__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/place-order-button */ "./client/blocks/utils/place-order-button.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */








/**
 * Render the credit card fields.
 *
 * @param {Object} props Incoming props
 */

const DnapaymentsCreditCardFields = ({
  props,
  isLoaded = false,
  hostedFieldsInstance = null,
  onLoad = () => {}
}) => {
  const {
    components: {
      LoadingMask
    },
    token = null
  } = props;
  const {
    cardSchemeIconPath
  } = _utils_get_settings__WEBPACK_IMPORTED_MODULE_8__.dnaPaymentsSettingsData;
  const mounted = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(false);
  const threeDSRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)();
  const [isCvvTokenVisible, setIsCvvTokenVisible] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [cardScheme, setCardScheme] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    name: '',
    number: '',
    expirationDate: '',
    cvv: ''
  });
  const setupIntegration = async () => {
    const {
      isTestMode,
      tempToken,
      cards,
      sendCallbackEveryFailedAttempt,
      availableSchemes
    } = _utils_get_settings__WEBPACK_IMPORTED_MODULE_8__.dnaPaymentsSettingsData;
    const selectedCard = cards.find(c => String(c.id) === String(token));
    (0,_utils_place_order_button__WEBPACK_IMPORTED_MODULE_9__.setPlaceOrderButtonDisabled)(true);
    threeDSRef.current = (0,_common_create_modal__WEBPACK_IMPORTED_MODULE_5__.createModal)(_common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.threeDS);
    hostedFieldsInstance = await (0,_common_create_hosted_fields__WEBPACK_IMPORTED_MODULE_4__.createHostedFields)({
      isTestMode: isTestMode,
      accessToken: tempToken,
      threeDSModal: threeDSRef.current,
      domElements: {
        number: document.getElementById(_common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.number),
        name: document.getElementById(_common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.name),
        expDate: document.getElementById(_common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.expDate),
        cvv: document.getElementById(_common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.cvv),
        cvvToken: document.getElementById(_common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.cvvToken)
      },
      sendCallbackEveryFailedAttempt,
      showPlaceholderOnlyOnFocus: true
    });
    hostedFieldsInstance.on('change', () => {
      const state = hostedFieldsInstance.getState();
      const scheme = (0,_common_card_scheme__WEBPACK_IMPORTED_MODULE_3__.normalizeCardSchemeName)(state.cardInfo?.type);
      setCardScheme(availableSchemes.includes(scheme) ? scheme : '');
      (0,_common_log__WEBPACK_IMPORTED_MODULE_6__.logData)('card scheme:', scheme);
    });
    if (selectedCard) {
      const cvvState = hostedFieldsInstance.getTokenizedCardCvvState(selectedCard);
      setIsCvvTokenVisible(cvvState === 'required');
      hostedFieldsInstance.selectCard(selectedCard);
    }
    (0,_utils_place_order_button__WEBPACK_IMPORTED_MODULE_9__.setPlaceOrderButtonDisabled)(false);
    onLoad(hostedFieldsInstance);
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    const {
      cards
    } = _utils_get_settings__WEBPACK_IMPORTED_MODULE_8__.dnaPaymentsSettingsData;
    if (hostedFieldsInstance) {
      const selectedCard = token && cards.find(c => String(c.id) === String(token));
      if (selectedCard) {
        const cvvState = hostedFieldsInstance.getTokenizedCardCvvState(selectedCard);
        setIsCvvTokenVisible(cvvState === 'required');
        hostedFieldsInstance.selectCard(selectedCard);
      } else {
        hostedFieldsInstance.selectCard(null);
      }
    }
  }, [token]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    mounted.current = true;
    setTimeout(() => {
      if (mounted.current) {
        setupIntegration();
      }
    }, 100);
    return () => {
      mounted.current = false;
      if (hostedFieldsInstance) {
        hostedFieldsInstance.destroy();
      }
      if (threeDSRef.current) {
        threeDSRef.current.remove();
        threeDSRef.current = null;
      }
      onLoad(null);
    };
  }, []);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)(LoadingMask, {
    isLoading: !isLoaded,
    showSpinner: true,
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)("div", {
      className: "wc-block-dnapayments-card-elements",
      style: {
        display: !token ? 'flex' : 'none'
      },
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)("div", {
        className: "wc-block-gateway-container",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("div", {
          id: _common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.number,
          className: `wc-block-gateway-input empty`
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("label", {
          htmlFor: _common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.number,
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Card number', _common_constants__WEBPACK_IMPORTED_MODULE_7__.TEXT_DOMAIN)
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("img", {
          className: "wc-dnapayments-card-selected",
          src: `${cardSchemeIconPath}/${cardScheme || 'none'}.svg`
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2__.ValidationInputError, {
          errorMessage: error.number
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)("div", {
        className: "wc-block-gateway-container",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("div", {
          id: _common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.name,
          className: `wc-block-gateway-input empty`
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("label", {
          htmlFor: _common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.name,
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Cardholder name', _common_constants__WEBPACK_IMPORTED_MODULE_7__.TEXT_DOMAIN)
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2__.ValidationInputError, {
          errorMessage: error.name
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)("div", {
        className: "wc-block-gateway-container wc-block-dnapayments-card-element-small",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("div", {
          id: _common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.expDate,
          className: "wc-block-gateway-input empty"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("label", {
          htmlFor: _common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.expDate,
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Expiry date (MMYY)', _common_constants__WEBPACK_IMPORTED_MODULE_7__.TEXT_DOMAIN)
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2__.ValidationInputError, {
          errorMessage: error.expirationDate
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)("div", {
        className: "wc-block-gateway-container wc-block-dnapayments-card-element-small",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("div", {
          id: _common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.cvv,
          className: "wc-block-gateway-input empty"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("label", {
          htmlFor: _common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.cvv,
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Card code (CVC)', _common_constants__WEBPACK_IMPORTED_MODULE_7__.TEXT_DOMAIN)
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2__.ValidationInputError, {
          errorMessage: error.cvv
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("div", {
      className: "wc-block-dnapayments-card-elements",
      style: {
        display: isCvvTokenVisible ? 'flex' : 'none'
      },
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)("div", {
        className: "wc-block-gateway-container wc-block-dnapayments-card-element-small",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("div", {
          id: _common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.cvvToken,
          className: "wc-block-gateway-input empty"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("label", {
          htmlFor: _common_constants__WEBPACK_IMPORTED_MODULE_7__.HOSTED_FIELD_IDS.cvvToken,
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Card code (CVC)', _common_constants__WEBPACK_IMPORTED_MODULE_7__.TEXT_DOMAIN)
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2__.ValidationInputError, {
          errorMessage: error.cvv
        })]
      })
    })]
  });
};

/***/ }),

/***/ "./client/blocks/hooks/use-checkout-update.js":
/*!****************************************************!*\
  !*** ./client/blocks/hooks/use-checkout-update.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useCheckoutUpdate: () => (/* binding */ useCheckoutUpdate)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

function useCheckoutUpdate(onUpdate) {
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const target = document.querySelector('.wc-block-checkout__form');
    if (!target) return;
    const observer = new MutationObserver(mutationsList => {
      onUpdate && onUpdate(mutationsList);
    });
    observer.observe(target, {
      childList: true,
      // Watch for added/removed child elements
      // attributes: true, // Watch for attribute changes
      subtree: true // Watch all descendants
    });
    return () => observer.disconnect(); // Clean up when component unmounts
  }, []);
}

/***/ }),

/***/ "./client/blocks/hooks/use-payment-form.js":
/*!*************************************************!*\
  !*** ./client/blocks/hooks/use-payment-form.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   usePaymentForm: () => (/* binding */ usePaymentForm)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _common_try_parse__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/try-parse */ "./client/common/try-parse.js");
/* harmony import */ var _common_pay_hosted_fields__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/pay-hosted-fields */ "./client/common/pay-hosted-fields.js");
/* harmony import */ var _common_errors__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/errors */ "./client/common/errors.js");
/* harmony import */ var _common_complete_payment__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/complete-payment */ "./client/common/complete-payment.js");
/* harmony import */ var _common_validater__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/validater */ "./client/common/validater.js");
/* harmony import */ var _common_utils__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/utils */ "./client/common/utils.js");
/* harmony import */ var _common_constants__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/constants */ "./client/common/constants.js");
/* harmony import */ var _utils_get_settings__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/get-settings */ "./client/blocks/utils/get-settings.js");
/* harmony import */ var _utils_validator__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../utils/validator */ "./client/blocks/utils/validator.js");
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */









const usePaymentForm = ({
  props,
  hostedFieldsInstance,
  gatewayId
}) => {
  const {
    setExpressPaymentError,
    emitResponse: {
      responseTypes,
      noticeContexts
    },
    eventRegistration: {
      onCheckoutSuccess,
      onPaymentSetup,
      onCheckoutValidation
    },
    shouldSavePayment
  } = props;
  const {
    isTestMode,
    integrationType,
    allowSavingCards,
    cards,
    terminalConfig
  } = _utils_get_settings__WEBPACK_IMPORTED_MODULE_9__.dnaPaymentsSettingsData;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    const handler = () => {
      const errorMessage = (0,_utils_validator__WEBPACK_IMPORTED_MODULE_10__.getValidationErrors)();
      if (errorMessage.length) {
        setExpressPaymentError(errorMessage);
      }
      return !errorMessage.length;
    };
    return onCheckoutValidation(handler);
  }, [onCheckoutValidation]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    const handler = async () => {
      if (integrationType === 'seamless') {
        const {
          isValid
        } = await hostedFieldsInstance.validate();
        if (!isValid) {
          return {
            type: responseTypes.ERROR,
            message: _common_errors__WEBPACK_IMPORTED_MODULE_4__["default"].CARD_DETAILS_INVALID.message,
            messageContext: noticeContexts.PAYMENTS
          };
        }
      }
      return true;
    };
    return onPaymentSetup(handler);
  }, [onPaymentSetup, hostedFieldsInstance, responseTypes, noticeContexts]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    const handler = ({
      processingResponse: {
        paymentDetails
      }
    }) => new Promise(resolve => {
      const paymentData = (0,_common_try_parse__WEBPACK_IMPORTED_MODULE_2__.tryParse)(paymentDetails.paymentData);
      const auth = (0,_common_try_parse__WEBPACK_IMPORTED_MODULE_2__.tryParse)(paymentDetails.auth);
      if ((0,_common_validater__WEBPACK_IMPORTED_MODULE_6__.shouldHideOrderLines)(terminalConfig) && paymentData?.orderLines) {
        delete paymentData.orderLines;
      }
      const successResponse = {
        type: responseTypes.SUCCESS,
        messageContext: noticeContexts.PAYMENTS
      };
      const failedResponse = {
        type: responseTypes.ERROR,
        messageContext: noticeContexts.PAYMENTS
      };
      paymentData.merchantCustomData = (0,_common_utils__WEBPACK_IMPORTED_MODULE_7__.addGatewayId)(paymentData.merchantCustomData, gatewayId);
      switch (integrationType) {
        case 'seamless':
          {
            window.DNAPayments.configure({
              isTestMode,
              cards,
              allowSavingCards
            });
            (0,_common_pay_hosted_fields__WEBPACK_IMPORTED_MODULE_3__.payHostedFields)(hostedFieldsInstance, {
              ...paymentData,
              merchantCustomData: JSON.stringify({
                ...((0,_common_try_parse__WEBPACK_IMPORTED_MODULE_2__.tryParse)(paymentData.merchantCustomData) || {}),
                storeCardOnFile: shouldSavePayment
              })
            }, auth).then(result => {
              (0,_common_complete_payment__WEBPACK_IMPORTED_MODULE_5__.completePayment)({
                paymentResult: result.data,
                redirect: result.redirect
              }).finally(() => {
                resolve(!result.error ? successResponse : {
                  ...failedResponse,
                  message: result.error
                });
                if (result.redirect) {
                  window.location.href = result.redirect;
                }
              });
            });
            break;
          }
        case 'embedded':
          {
            window.DNAPayments.configure({
              isTestMode,
              cards,
              allowSavingCards,
              events: {
                cancelled: () => resolve({
                  ...failedResponse,
                  message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)(_common_errors__WEBPACK_IMPORTED_MODULE_4__["default"].CARD_PAYMENT_CANCEL.message, _common_constants__WEBPACK_IMPORTED_MODULE_8__.TEXT_DOMAIN)
                }),
                paid: () => resolve(successResponse),
                declined: () => resolve({
                  ...failedResponse,
                  message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)(_common_errors__WEBPACK_IMPORTED_MODULE_4__["default"].CARD_PAYMENT_FAIL.message, _common_constants__WEBPACK_IMPORTED_MODULE_8__.TEXT_DOMAIN)
                })
              }
            });
            window.DNAPayments.openPaymentIframeWidget({
              ...paymentData,
              auth
            });
            break;
          }
        default:
          {
            window.DNAPayments.configure({
              isTestMode,
              cards,
              allowSavingCards
            });
            window.DNAPayments.openPaymentPage({
              ...paymentData,
              auth
            });
          }
      }
    });
    return onCheckoutSuccess(handler);
  }, [onCheckoutSuccess, hostedFieldsInstance, responseTypes, noticeContexts, shouldSavePayment]);
};

/***/ }),

/***/ "./client/blocks/utils/get-settings.js":
/*!*********************************************!*\
  !*** ./client/blocks/utils/get-settings.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   dnaPaymentsSettingsData: () => (/* binding */ dnaPaymentsSettingsData)
/* harmony export */ });
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @woocommerce/settings */ "@woocommerce/settings");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _common_constants__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/constants */ "./client/common/constants.js");


const settings = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_0__.getPaymentMethodData)(_common_constants__WEBPACK_IMPORTED_MODULE_1__.GATEWAY_ID, {});
const dnaPaymentsSettingsData = {
  isTestMode: settings.is_test_mode,
  integrationType: settings.integration_type,
  tempToken: settings.temp_token,
  terminalId: settings.terminal_id,
  allowSavingCards: settings.allow_saving_cards,
  sendCallbackEveryFailedAttempt: Number(settings.send_callback_every_failed_attempt),
  cards: settings.allow_saving_cards ? settings.cards : [],
  cardSchemeIconPath: settings.card_scheme_icon_path,
  terminalConfig: settings.terminal_config,
  availableSchemes: settings.available_schemes || [],
  nonces: settings.nonces || {}
};

/***/ }),

/***/ "./client/blocks/utils/place-order-button.js":
/*!***************************************************!*\
  !*** ./client/blocks/utils/place-order-button.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getPlaceOrderButton: () => (/* binding */ getPlaceOrderButton),
/* harmony export */   isPlaceOrderButtonDisabled: () => (/* binding */ isPlaceOrderButtonDisabled),
/* harmony export */   setPlaceOrderButtonDisabled: () => (/* binding */ setPlaceOrderButtonDisabled),
/* harmony export */   triggerPlaceOrderButtonClick: () => (/* binding */ triggerPlaceOrderButtonClick),
/* harmony export */   useTogglePlaceOrderButtonDisabled: () => (/* binding */ useTogglePlaceOrderButtonDisabled)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _common_constants__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/constants */ "./client/common/constants.js");
/* harmony import */ var _hooks_use_checkout_update__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../hooks/use-checkout-update */ "./client/blocks/hooks/use-checkout-update.js");



function isPlaceOrderButtonDisabled(activePaymentMethod) {
  return [_common_constants__WEBPACK_IMPORTED_MODULE_1__.GATEWAY_ID_GOOGLE_PAY, _common_constants__WEBPACK_IMPORTED_MODULE_1__.GATEWAY_ID_APPLE_PAY].includes(activePaymentMethod);
}
function setPlaceOrderButtonDisabled(isDisabled, button) {
  const placeOrderButton = button || getPlaceOrderButton();
  if (!placeOrderButton) {
    return;
  }
  if (isDisabled) {
    placeOrderButton.setAttribute('disabled', 'disabled');
  } else {
    placeOrderButton.removeAttribute('disabled');
  }
}
function triggerPlaceOrderButtonClick() {
  const placeOrderButton = getPlaceOrderButton();
  if (placeOrderButton) {
    placeOrderButton.removeAttribute('disabled');
    placeOrderButton.click();
  }
}
function getPlaceOrderButton() {
  return document.querySelector('button.wc-block-components-checkout-place-order-button');
}
function useTogglePlaceOrderButtonDisabled(activePaymentMethod) {
  const refActivePaymentMethod = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(activePaymentMethod);
  const update = () => {
    const isDisabled = isPlaceOrderButtonDisabled(refActivePaymentMethod.current);
    const button = getPlaceOrderButton();
    setTimeout(() => setPlaceOrderButtonDisabled(isDisabled, button), 100);
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    refActivePaymentMethod.current = activePaymentMethod;
  }, [activePaymentMethod]);
  (0,_hooks_use_checkout_update__WEBPACK_IMPORTED_MODULE_2__.useCheckoutUpdate)(update);
  update();
}

/***/ }),

/***/ "./client/blocks/utils/validator.js":
/*!******************************************!*\
  !*** ./client/blocks/utils/validator.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getValidationErrors: () => (/* binding */ getValidationErrors)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);


const fieldIds = [
// Billing fields
'billing_country', 'billing_city', 'billing_state', 'billing_address_1', 'billing_email', 'billing_last_name', 'billing_first_name', 'billing_postcode', 'billing_phone',
// Shipping fields
'shipping_country', 'shipping_city', 'shipping_state', 'shipping_address_1', 'shipping_last_name', 'shipping_first_name', 'shipping_postcode', 'shipping_phone'];
const getValidationErrors = () => {
  const errorMessages = [];
  const validationStore = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_1__.select)(window.wc.wcBlocksData.VALIDATION_STORE_KEY);
  if (validationStore.hasValidationErrors()) {
    fieldIds.forEach(fieldId => {
      const validationError = validationStore.getValidationError(fieldId);
      if (validationError) {
        errorMessages.push(validationError.message);
      }
    });
    if (!errorMessages.length) {
      errorMessages.push((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('A validation error occurred. Please check all fields and try again.', TEXT_DOMAIN));
    }
  }
  return errorMessages;
};

/***/ }),

/***/ "./client/common/api/request.js":
/*!**************************************!*\
  !*** ./client/common/api/request.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   request: () => (/* binding */ request)
/* harmony export */ });
/* harmony import */ var _errors__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../errors */ "./client/common/errors.js");

async function request(input, init = {}) {
  var _init$credentials;
  const opts = {
    credentials: (_init$credentials = init.credentials) !== null && _init$credentials !== void 0 ? _init$credentials : 'same-origin',
    ...init
  };
  try {
    const response = await fetch(input, opts);
    return await response.json();
  } catch (err) {
    return {
      success: false,
      data: {
        errors: [_errors__WEBPACK_IMPORTED_MODULE_0__["default"].CRITICAL_WEBSITE_ERROR.message]
      }
    };
  }
}

/***/ }),

/***/ "./client/common/api/update-order-status.js":
/*!**************************************************!*\
  !*** ./client/common/api/update-order-status.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   updateOrderStatus: () => (/* binding */ updateOrderStatus)
/* harmony export */ });
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../constants */ "./client/common/constants.js");
/* harmony import */ var _request__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./request */ "./client/common/api/request.js");


async function updateOrderStatus(orderId, paymentResult) {
  const formData = new FormData();
  formData.append('order_id', orderId);
  formData.append('wc-dnapayments-result', JSON.stringify(paymentResult));
  formData.append('_dna_nonce', getNonce());
  return await (0,_request__WEBPACK_IMPORTED_MODULE_1__.request)('/wp-admin/admin-ajax.php?action=' + _constants__WEBPACK_IMPORTED_MODULE_0__.GATEWAY_ID + '_update_order_status', {
    method: 'POST',
    body: formData
  });
}
function getNonce() {
  return window.wc_dna_params?.nonces?.update_order_status || '';
}

/***/ }),

/***/ "./client/common/card-scheme.js":
/*!**************************************!*\
  !*** ./client/common/card-scheme.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   normalizeCardSchemeName: () => (/* binding */ normalizeCardSchemeName)
/* harmony export */ });
function normalizeCardSchemeName(cardScheme) {
  if (!cardScheme) return null;
  const normalized = cardScheme.toLowerCase().trim();
  switch (normalized) {
    case 'amex':
    case 'amexcard':
    case 'americanexpress':
    case 'american express':
    case 'american-express':
      return 'amex';
    case 'dci':
    case 'diners':
    case 'dinersclub':
    case 'diners club':
    case 'diners-club':
      return 'diners';
    case 'mc':
    case 'mastercard':
    case 'master card':
    case 'master-card':
      return 'mastercard';
    case 'upi':
    case 'unionpay':
    case 'union pay':
      return 'unionpay';
    case 'visa':
    case 'visacard':
    case 'visa card':
    case 'visa-card':
      return 'visa';
    case 'maestro':
    case 'maestrocard':
    case 'maestro card':
    case 'maestro-card':
      return 'maestro';
    case 'discover':
    case 'discovercard':
    case 'discover card':
    case 'discover-card':
      return 'discover';
    default:
      return normalized;
  }
}

/***/ }),

/***/ "./client/common/complete-payment.js":
/*!*******************************************!*\
  !*** ./client/common/complete-payment.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   completePayment: () => (/* binding */ completePayment),
/* harmony export */   getOrderIdFromPaymentData: () => (/* binding */ getOrderIdFromPaymentData)
/* harmony export */ });
/* harmony import */ var _api_update_order_status__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./api/update-order-status */ "./client/common/api/update-order-status.js");
/* harmony import */ var _try_parse__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./try-parse */ "./client/common/try-parse.js");



/**
 * Completes the payment process and handles redirection
 * Keeps loading state active during page navigation
 */
async function completePayment({
  paymentResult,
  redirect,
  setLoading = () => {},
  setErrors = () => {}
}) {
  // Helper function to handle redirects while keeping loading state active
  const handleRedirect = url => {
    setLoading(true);

    // Use setTimeout to ensure the loading state remains active during the entire redirection process
    // This addresses the 4-5 second gap some merchants experience during redirection
    setTimeout(() => {
      window.location.href = url;
      // We don't call setLoading(false) here because the page will be unloaded anyway
      // and we want to keep the loading indicator visible during the entire navigation process
    }, 100);
  };
  if (paymentResult) {
    setLoading(true);
    try {
      const orderId = getOrderIdFromPaymentData(paymentResult);
      const {
        success,
        data
      } = await (0,_api_update_order_status__WEBPACK_IMPORTED_MODULE_0__.updateOrderStatus)(orderId, paymentResult);
      if (success) {
        handleRedirect(data.redirect);
        return;
      } else {
        setErrors(data.errors);
        setLoading(false);
      }
    } catch (err) {
      setErrors([err.message]);
      setLoading(false);
    }
  }
  if (redirect) {
    handleRedirect(redirect);
  }
}

/**
 * Extracts order ID from payment data
 */
function getOrderIdFromPaymentData(data) {
  const customData = (0,_try_parse__WEBPACK_IMPORTED_MODULE_1__.tryParse)(data?.merchantCustomData);
  return customData?.orderId || null;
}

/***/ }),

/***/ "./client/common/constants.js":
/*!************************************!*\
  !*** ./client/common/constants.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CONTAINER_IDS: () => (/* binding */ CONTAINER_IDS),
/* harmony export */   GATEWAY_ID: () => (/* binding */ GATEWAY_ID),
/* harmony export */   GATEWAY_ID_APPLE_PAY: () => (/* binding */ GATEWAY_ID_APPLE_PAY),
/* harmony export */   GATEWAY_ID_GOOGLE_PAY: () => (/* binding */ GATEWAY_ID_GOOGLE_PAY),
/* harmony export */   HOSTED_FIELD_IDS: () => (/* binding */ HOSTED_FIELD_IDS),
/* harmony export */   TEXT_DOMAIN: () => (/* binding */ TEXT_DOMAIN)
/* harmony export */ });
const GATEWAY_ID = 'dnapayments';
const GATEWAY_ID_GOOGLE_PAY = 'dnapayments_google_pay';
const GATEWAY_ID_APPLE_PAY = 'dnapayments_apple_pay';
const TEXT_DOMAIN = 'woocommerce-gateway-dna';
const HOSTED_FIELD_IDS = {
  number: `wc-${GATEWAY_ID}-card-number-hosted`,
  name: `wc-${GATEWAY_ID}-card-name-hosted`,
  expDate: `wc-${GATEWAY_ID}-expiry-hosted`,
  cvv: `wc-${GATEWAY_ID}-csc-hosted`,
  cvvToken: `wc-${GATEWAY_ID}-csc-token-hosted`,
  threeDS: 'three-d-secure'
};
const CONTAINER_IDS = {
  googlepay: GATEWAY_ID_GOOGLE_PAY + '_container',
  applepay: GATEWAY_ID_APPLE_PAY + '_container'
};

/***/ }),

/***/ "./client/common/create-hosted-fields.js":
/*!***********************************************!*\
  !*** ./client/common/create-hosted-fields.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createHostedFields: () => (/* binding */ createHostedFields)
/* harmony export */ });
/* harmony import */ var _models_DnaPaymentsError__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./models/DnaPaymentsError */ "./client/common/models/DnaPaymentsError.js");
/* harmony import */ var _errors__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./errors */ "./client/common/errors.js");
/* harmony import */ var _log__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./log */ "./client/common/log.js");



async function createHostedFields({
  isTestMode,
  accessToken,
  threeDSModal,
  domElements: {
    number,
    name,
    expDate,
    cvv,
    cvvToken
  },
  sendCallbackEveryFailedAttempt = 0,
  showPlaceholderOnlyOnFocus = false
}) {
  const fields = {
    cardholderName: {
      container: name,
      placeholder: 'ABC'
    },
    cardNumber: {
      container: number,
      placeholder: '1234 1234 1234 1234'
    },
    expirationDate: {
      container: expDate,
      placeholder: 'MM / YY'
    },
    cvv: {
      container: cvv,
      placeholder: 'CVC'
    },
    tokenizedCardCvv: {
      container: cvvToken,
      placeholder: 'CVC'
    }
  };
  let styles = {
    input: {
      'font-size': '16px',
      'font-family': 'Open Sans'
    }
  };
  if (showPlaceholderOnlyOnFocus) {
    styles = {
      ...styles,
      '::placeholder': {
        opacity: '0'
      },
      'input:focus::placeholder': {
        opacity: '0.5'
      }
    };
  } else {
    styles = {
      ...styles,
      '::placeholder': {
        opacity: '0.5'
      }
    };
  }
  const options = {
    isTestMode,
    accessToken,
    styles,
    styleConfig: {
      containerClasses: {
        FOCUSED: 'focused',
        INVALID: 'has-error'
      }
    },
    fontNames: ['Open Sans'],
    threeDSecure: {
      container: threeDSModal.body
    },
    fields,
    sendCallbackEveryFailedAttempt
  };
  try {
    const hostedFieldsInstance = await window.dnaPayments.hostedFields.create(options);
    hostedFieldsInstance.on('blur', function ({
      fieldKey,
      fieldsState
    }) {
      const fieldContainer = fields[fieldKey]?.container;
      const isEmpty = fieldsState[fieldKey]?.isEmpty;
      if (fieldContainer) {
        fieldContainer.classList.toggle('empty', isEmpty);
      }
    });
    hostedFieldsInstance.on('clear', function () {
      const containers = [number, name, expDate, cvv, cvvToken];
      containers.forEach(fieldContainer => {
        if (fieldContainer) {
          fieldContainer.classList.toggle('empty', true);
        }
      });
    });
    hostedFieldsInstance.on('dna-payments-three-d-secure-show', data => {
      if (threeDSModal) {
        threeDSModal.show();
      }
    });
    hostedFieldsInstance.on('dna-payments-three-d-secure-hide', () => {
      if (threeDSModal) {
        threeDSModal.hide();
      }
    });
    return hostedFieldsInstance;
  } catch (err) {
    (0,_log__WEBPACK_IMPORTED_MODULE_2__.logError)(err);
    throw new _models_DnaPaymentsError__WEBPACK_IMPORTED_MODULE_0__.DnaPaymentsError(_errors__WEBPACK_IMPORTED_MODULE_1__["default"].HOSTED_FIELDS_INIT_FAIL);
  }
}

/***/ }),

/***/ "./client/common/create-modal.js":
/*!***************************************!*\
  !*** ./client/common/create-modal.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createModal: () => (/* binding */ createModal)
/* harmony export */ });
function createModal(id) {
  let modalContainer = document.getElementById(id);
  if (modalContainer) {
    return {
      show: () => modalContainer.classList.add('open'),
      hide: () => modalContainer.classList.remove('open'),
      remove: () => modalContainer.remove(),
      body: modalContainer.querySelector('.dna-modal-body')
    };
  }

  // Create modal elements
  modalContainer = document.createElement('div');
  modalContainer.className = 'dna-modal-container';
  modalContainer.id = id;
  const modal = document.createElement('div');
  modal.className = 'dna-modal';
  const modalBody = document.createElement('div');
  modalBody.className = 'dna-modal-body';

  // Append elements
  modal.appendChild(modalBody);
  modalContainer.appendChild(modal);
  document.body.appendChild(modalContainer);
  return {
    show: () => modalContainer.classList.add('open'),
    hide: () => modalContainer.classList.remove('open'),
    remove: () => modalContainer.remove(),
    body: modalBody
  };
}

/***/ }),

/***/ "./client/common/errors.js":
/*!*********************************!*\
  !*** ./client/common/errors.js ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
const SERVER_TIMEOUT = {
  code: 'SERVER_TIMEOUT',
  message: 'The server took too long to respond. Please check your connection and try again.'
};
const UNKNOWN_ERROR = {
  code: 'UNKNOWN_ERROR',
  message: 'Something went wrong. Please contact our support team for assistance.'
};
const CRITICAL_WEBSITE_ERROR = {
  code: 'CRITICAL_WEBSITE_ERROR',
  message: 'A critical error occurred. Please try again later or contact support if the issue continues.'
};
const HOSTED_FIELDS_INIT_FAIL = {
  code: 'HOSTED_FIELDS_INIT_FAIL',
  message: 'We could not initialise the card payment. Please reload the page and try again.'
};
const CARD_PAYMENT_FAIL = {
  code: 'CARD_PAYMENT_FAIL',
  message: 'Your card was not authorised. Please check your details and try again, contact your bank if the issue continues.'
};
const CARD_PAYMENT_CANCEL = {
  code: 'CARD_PAYMENT_CANCEL',
  message: 'You have cancelled the payment process. Please try again if you wish to complete the order.'
};
const CARD_DETAILS_INVALID = {
  code: 'CARD_DETAILS_INVALID',
  message: 'Some card details are incorrect. Please double-check the card number, expiry date, and CVV, then try again.'
};
const APPLE_PAY_INIT_FAIL = {
  code: 'APPLE_PAY_INIT_FAIL',
  message: 'Apple Pay payments are not supported in your current browser. We would advise you to use the latest version of Safari, on a compatible Apple device to complete your transaction'
};
const GOOGLE_PAY_INIT_FAIL = {
  code: 'GOOGLE_PAY_INIT_FAIL',
  message: 'Google Pay is not supported in your current browser.'
};
const TERMS_NOT_ACCEPTED = {
  code: 'TERMS_NOT_ACCEPTED',
  message: 'Please accept the terms and conditions to continue.'
};
const BILLING_COUNTRY_REQUIRED = {
  code: 'BILLING_COUNTRY_REQUIRED',
  message: 'Please enter your billing country.'
};
const BILLING_CITY_REQUIRED = {
  code: 'BILLING_CITY_REQUIRED',
  message: 'Please enter your billing city.'
};
const BILLING_ADDRESS_REQUIRED = {
  code: 'BILLING_ADDRESS_REQUIRED',
  message: 'Please enter your billing address.'
};
const BILLING_EMAIL_REQUIRED = {
  code: 'BILLING_EMAIL_REQUIRED',
  message: 'Please enter your billing email address.'
};
const BILLING_EMAIL_INVALID = {
  code: 'BILLING_EMAIL_INVALID',
  message: 'Please enter a valid billing email address.'
};
const BILLING_LAST_NAME_REQUIRED = {
  code: 'BILLING_LAST_NAME_REQUIRED',
  message: 'Please enter your billing last name.'
};
const BILLING_FIRST_NAME_REQUIRED = {
  code: 'BILLING_FIRST_NAME_REQUIRED',
  message: 'Please enter your billing first name.'
};
const BILLING_POSTCODE_REQUIRED = {
  code: 'BILLING_POSTCODE_REQUIRED',
  message: 'Please enter your billing postcode.'
};
const SHIPPING_COUNTRY_REQUIRED = {
  code: 'SHIPPING_COUNTRY_REQUIRED',
  message: 'Please enter your shipping country.'
};
const SHIPPING_CITY_REQUIRED = {
  code: 'SHIPPING_CITY_REQUIRED',
  message: 'Please enter your shipping city.'
};
const SHIPPING_ADDRESS_REQUIRED = {
  code: 'SHIPPING_ADDRESS_REQUIRED',
  message: 'Please enter your shipping address.'
};
const SHIPPING_EMAIL_INVALID = {
  code: 'SHIPPING_EMAIL_INVALID',
  message: 'Please enter a valid shipping email address.'
};
const SHIPPING_LAST_NAME_REQUIRED = {
  code: 'SHIPPING_LAST_NAME_REQUIRED',
  message: 'Please enter your shipping last name.'
};
const SHIPPING_FIRST_NAME_REQUIRED = {
  code: 'SHIPPING_FIRST_NAME_REQUIRED',
  message: 'Please enter your shipping first name.'
};
const SHIPPING_POSTCODE_REQUIRED = {
  code: 'SHIPPING_POSTCODE_REQUIRED',
  message: 'Please enter your shipping postcode.'
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  SERVER_TIMEOUT,
  UNKNOWN_ERROR,
  APPLE_PAY_INIT_FAIL,
  GOOGLE_PAY_INIT_FAIL,
  HOSTED_FIELDS_INIT_FAIL,
  CARD_PAYMENT_FAIL,
  CARD_PAYMENT_CANCEL,
  CARD_DETAILS_INVALID,
  CRITICAL_WEBSITE_ERROR,
  TERMS_NOT_ACCEPTED,
  BILLING_COUNTRY_REQUIRED,
  BILLING_CITY_REQUIRED,
  BILLING_ADDRESS_REQUIRED,
  BILLING_EMAIL_REQUIRED,
  BILLING_EMAIL_INVALID,
  BILLING_LAST_NAME_REQUIRED,
  BILLING_FIRST_NAME_REQUIRED,
  BILLING_POSTCODE_REQUIRED,
  SHIPPING_COUNTRY_REQUIRED,
  SHIPPING_CITY_REQUIRED,
  SHIPPING_ADDRESS_REQUIRED,
  SHIPPING_EMAIL_INVALID,
  SHIPPING_LAST_NAME_REQUIRED,
  SHIPPING_FIRST_NAME_REQUIRED,
  SHIPPING_POSTCODE_REQUIRED
});

/***/ }),

/***/ "./client/common/log.js":
/*!******************************!*\
  !*** ./client/common/log.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   logData: () => (/* binding */ logData),
/* harmony export */   logError: () => (/* binding */ logError)
/* harmony export */ });
function logError(err, title = '') {
  console.error('CODE', err.code, 'MESSAGE', err.message);
  console.error(title, err);
}
function logData(...args) {
  console.log(...args);
}

/***/ }),

/***/ "./client/common/models/DnaPaymentsError.js":
/*!**************************************************!*\
  !*** ./client/common/models/DnaPaymentsError.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DnaPaymentsError: () => (/* binding */ DnaPaymentsError)
/* harmony export */ });
class DnaPaymentsError extends Error {
  constructor(options) {
    super(options.message);
    Object.setPrototypeOf(this, DnaPaymentsError.prototype);
    this.name = 'DnaPaymentsError';
    this.code = options.code;
    this.data = options.data;
    this.stack = Error().stack;
  }
  toJSON() {
    return {
      ...this,
      message: this.message
    };
  }
}

/***/ }),

/***/ "./client/common/pay-hosted-fields.js":
/*!********************************************!*\
  !*** ./client/common/pay-hosted-fields.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   payHostedFields: () => (/* binding */ payHostedFields)
/* harmony export */ });
/* harmony import */ var _errors__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./errors */ "./client/common/errors.js");
/* harmony import */ var _log__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./log */ "./client/common/log.js");


async function payHostedFields(hostedFieldsInstance, paymentData, auth) {
  const {
    returnUrl,
    failureReturnUrl
  } = paymentData.paymentSettings;
  try {
    const {
      data
    } = await hostedFieldsInstance.submit({
      paymentData,
      token: auth.access_token
    });
    return {
      data,
      redirect: returnUrl
    };
  } catch (err) {
    (0,_log__WEBPACK_IMPORTED_MODULE_1__.logError)(err);
    if (err.code === 'NOT_VALID_CARD_DATA') {
      return {
        error: _errors__WEBPACK_IMPORTED_MODULE_0__["default"].CARD_DETAILS_INVALID.message
      };
    }
    hostedFieldsInstance.clear();
    let error = err.message || _errors__WEBPACK_IMPORTED_MODULE_0__["default"].CARD_PAYMENT_FAIL.message;
    if (String(err.code).includes('CLOSE_TRANSACTION')) {
      return {
        error,
        redirect: failureReturnUrl
      };
    }
    return {
      error
    };
  }
}

/***/ }),

/***/ "./client/common/try-parse.js":
/*!************************************!*\
  !*** ./client/common/try-parse.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   tryParse: () => (/* binding */ tryParse)
/* harmony export */ });
/* harmony import */ var _log__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./log */ "./client/common/log.js");

const tryParse = str => {
  if (!str) return null;
  if (typeof str !== 'string') return str;
  try {
    return JSON.parse(str);
  } catch (err) {
    (0,_log__WEBPACK_IMPORTED_MODULE_0__.logError)(err, 'JSON parse error');
    return null;
  }
};

/***/ }),

/***/ "./client/common/utils.js":
/*!********************************!*\
  !*** ./client/common/utils.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   addGatewayId: () => (/* binding */ addGatewayId),
/* harmony export */   removeNonLatin1: () => (/* binding */ removeNonLatin1)
/* harmony export */ });
/* harmony import */ var _try_parse__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./try-parse */ "./client/common/try-parse.js");

function removeNonLatin1(str) {
  // Keep only Latin-1 (ISO-8859-1) characters: 0x00–0xFF
  // This removes emojis, smart quotes, special Unicode symbols, etc.
  return str?.replace(/[^\x00-\xFF]/g, '').trim() || '';
}
function addGatewayId(merchantCustomData, gatewayId) {
  const customData = (0,_try_parse__WEBPACK_IMPORTED_MODULE_0__.tryParse)(merchantCustomData) || {};
  customData.gatewayId = gatewayId;
  return JSON.stringify(customData);
}

/***/ }),

/***/ "./client/common/validater.js":
/*!************************************!*\
  !*** ./client/common/validater.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   checkApplePayAvailability: () => (/* binding */ checkApplePayAvailability),
/* harmony export */   getApplePaySession: () => (/* binding */ getApplePaySession),
/* harmony export */   isApplePayAvailable: () => (/* binding */ isApplePayAvailable),
/* harmony export */   isEmpty: () => (/* binding */ isEmpty),
/* harmony export */   isValidEmail: () => (/* binding */ isValidEmail),
/* harmony export */   loadApplePaySDK: () => (/* binding */ loadApplePaySDK),
/* harmony export */   shouldHideOrderLines: () => (/* binding */ shouldHideOrderLines),
/* harmony export */   validateAddress: () => (/* binding */ validateAddress),
/* harmony export */   validatePaymentData: () => (/* binding */ validatePaymentData),
/* harmony export */   validateTermsAndConditions: () => (/* binding */ validateTermsAndConditions)
/* harmony export */ });
/* harmony import */ var _errors__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./errors */ "./client/common/errors.js");

function isValidEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}
function isEmpty(value) {
  return !value || value.toString().trim() === '';
}
function validateAddress(address, section) {
  if (!address) {
    return [section === 'shipping' ? _errors__WEBPACK_IMPORTED_MODULE_0__["default"].SHIPPING_ADDRESS_REQUIRED : _errors__WEBPACK_IMPORTED_MODULE_0__["default"].BILLING_ADDRESS_REQUIRED];
  }
  const errorsMap = {
    firstName: section === 'shipping' ? _errors__WEBPACK_IMPORTED_MODULE_0__["default"].SHIPPING_FIRST_NAME_REQUIRED : _errors__WEBPACK_IMPORTED_MODULE_0__["default"].BILLING_FIRST_NAME_REQUIRED,
    lastName: section === 'shipping' ? _errors__WEBPACK_IMPORTED_MODULE_0__["default"].SHIPPING_LAST_NAME_REQUIRED : _errors__WEBPACK_IMPORTED_MODULE_0__["default"].BILLING_LAST_NAME_REQUIRED,
    addressLine1: section === 'shipping' ? _errors__WEBPACK_IMPORTED_MODULE_0__["default"].SHIPPING_ADDRESS_REQUIRED : _errors__WEBPACK_IMPORTED_MODULE_0__["default"].BILLING_ADDRESS_REQUIRED,
    city: section === 'shipping' ? _errors__WEBPACK_IMPORTED_MODULE_0__["default"].SHIPPING_CITY_REQUIRED : _errors__WEBPACK_IMPORTED_MODULE_0__["default"].BILLING_CITY_REQUIRED,
    postalCode: section === 'shipping' ? _errors__WEBPACK_IMPORTED_MODULE_0__["default"].SHIPPING_POSTCODE_REQUIRED : _errors__WEBPACK_IMPORTED_MODULE_0__["default"].BILLING_POSTCODE_REQUIRED
  };
  return Object.keys(errorsMap).filter(key => isEmpty(address[key])).map(key => errorsMap[key].message);
}
function validatePaymentData(paymentData) {
  const messages = [];
  const email = paymentData?.customerDetails?.email;
  if (isEmpty(email)) {
    messages.push(_errors__WEBPACK_IMPORTED_MODULE_0__["default"].BILLING_EMAIL_REQUIRED.message);
  } else if (!isValidEmail(email)) {
    messages.push(_errors__WEBPACK_IMPORTED_MODULE_0__["default"].BILLING_EMAIL_INVALID.message);
  }
  messages.push(...validateAddress(paymentData?.customerDetails?.billingAddress, 'billing'));
  messages.push(...validateAddress(paymentData?.customerDetails?.deliveryDetails?.deliveryAddress, 'shipping'));
  messages.push(...validateTermsAndConditions());
  return messages;
}
function validateTermsAndConditions(isClassic = false) {
  const checkbox = document.getElementById('terms-and-conditions');
  if (checkbox && !checkbox.checked) {
    return [_errors__WEBPACK_IMPORTED_MODULE_0__["default"].TERMS_NOT_ACCEPTED.message];
  }
  return [];
}
function shouldHideOrderLines(terminalConfig) {
  const settings = terminalConfig?.paymentMethodsSettings;
  if (!settings || typeof settings !== 'object') return false;
  const isPayPalOrKlarnaActive = ['paypal', 'klarna'].some(method => settings[method]?.status === 'active');
  return !isPayPalOrKlarnaActive;
}
const isApplePayAvailable = () => {
  try {
    const ApplePaySession = window.ApplePaySession; // it is declared as a class
    return Boolean(ApplePaySession?.canMakePayments());
  } catch (e) {
    console.error('Error in isApplePayAvailable', e);
    return false;
  }
};

// @ts-ignore
const getApplePaySession = () => window.ApplePaySession;
function loadApplePaySDK() {
  return new Promise((resolve, reject) => {
    if (document.getElementById('apple-pay-sdk')) {
      return resolve();
    }
    const script = document.createElement('script');
    script.id = 'apple-pay-sdk';
    script.src = 'https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js';
    script.async = true;
    script.setAttribute('crossorigin', 'anonymous');
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Failed to load Apple Pay SDK.'));
    document.head.appendChild(script);
  });
}
const checkApplePayAvailability = async terminalConfig => {
  const merchantId = terminalConfig?.merchantId;
  if (merchantId && typeof window.DNAPayments?.ApplePayComponent?.isAvailable === 'function') {
    try {
      await loadApplePaySDK();
      return await window.DNAPayments.ApplePayComponent.isAvailable(merchantId);
    } catch (err) {
      console.error('Error in checkApplePayAvailability', err);
      return false;
    }
  }
  return isApplePayAvailable();
};

/***/ }),

/***/ "./node_modules/react/cjs/react-jsx-runtime.development.js":
/*!*****************************************************************!*\
  !*** ./node_modules/react/cjs/react-jsx-runtime.development.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

/**
 * @license React
 * react-jsx-runtime.development.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */



if (true) {
  (function() {
'use strict';

var React = __webpack_require__(/*! react */ "react");

// ATTENTION
// When adding new symbols to this file,
// Please consider also adding to 'react-devtools-shared/src/backend/ReactSymbols'
// The Symbol used to tag the ReactElement-like types.
var REACT_ELEMENT_TYPE = Symbol.for('react.element');
var REACT_PORTAL_TYPE = Symbol.for('react.portal');
var REACT_FRAGMENT_TYPE = Symbol.for('react.fragment');
var REACT_STRICT_MODE_TYPE = Symbol.for('react.strict_mode');
var REACT_PROFILER_TYPE = Symbol.for('react.profiler');
var REACT_PROVIDER_TYPE = Symbol.for('react.provider');
var REACT_CONTEXT_TYPE = Symbol.for('react.context');
var REACT_FORWARD_REF_TYPE = Symbol.for('react.forward_ref');
var REACT_SUSPENSE_TYPE = Symbol.for('react.suspense');
var REACT_SUSPENSE_LIST_TYPE = Symbol.for('react.suspense_list');
var REACT_MEMO_TYPE = Symbol.for('react.memo');
var REACT_LAZY_TYPE = Symbol.for('react.lazy');
var REACT_OFFSCREEN_TYPE = Symbol.for('react.offscreen');
var MAYBE_ITERATOR_SYMBOL = Symbol.iterator;
var FAUX_ITERATOR_SYMBOL = '@@iterator';
function getIteratorFn(maybeIterable) {
  if (maybeIterable === null || typeof maybeIterable !== 'object') {
    return null;
  }

  var maybeIterator = MAYBE_ITERATOR_SYMBOL && maybeIterable[MAYBE_ITERATOR_SYMBOL] || maybeIterable[FAUX_ITERATOR_SYMBOL];

  if (typeof maybeIterator === 'function') {
    return maybeIterator;
  }

  return null;
}

var ReactSharedInternals = React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED;

function error(format) {
  {
    {
      for (var _len2 = arguments.length, args = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
        args[_key2 - 1] = arguments[_key2];
      }

      printWarning('error', format, args);
    }
  }
}

function printWarning(level, format, args) {
  // When changing this logic, you might want to also
  // update consoleWithStackDev.www.js as well.
  {
    var ReactDebugCurrentFrame = ReactSharedInternals.ReactDebugCurrentFrame;
    var stack = ReactDebugCurrentFrame.getStackAddendum();

    if (stack !== '') {
      format += '%s';
      args = args.concat([stack]);
    } // eslint-disable-next-line react-internal/safe-string-coercion


    var argsWithFormat = args.map(function (item) {
      return String(item);
    }); // Careful: RN currently depends on this prefix

    argsWithFormat.unshift('Warning: ' + format); // We intentionally don't use spread (or .apply) directly because it
    // breaks IE9: https://github.com/facebook/react/issues/13610
    // eslint-disable-next-line react-internal/no-production-logging

    Function.prototype.apply.call(console[level], console, argsWithFormat);
  }
}

// -----------------------------------------------------------------------------

var enableScopeAPI = false; // Experimental Create Event Handle API.
var enableCacheElement = false;
var enableTransitionTracing = false; // No known bugs, but needs performance testing

var enableLegacyHidden = false; // Enables unstable_avoidThisFallback feature in Fiber
// stuff. Intended to enable React core members to more easily debug scheduling
// issues in DEV builds.

var enableDebugTracing = false; // Track which Fiber(s) schedule render work.

var REACT_MODULE_REFERENCE;

{
  REACT_MODULE_REFERENCE = Symbol.for('react.module.reference');
}

function isValidElementType(type) {
  if (typeof type === 'string' || typeof type === 'function') {
    return true;
  } // Note: typeof might be other than 'symbol' or 'number' (e.g. if it's a polyfill).


  if (type === REACT_FRAGMENT_TYPE || type === REACT_PROFILER_TYPE || enableDebugTracing  || type === REACT_STRICT_MODE_TYPE || type === REACT_SUSPENSE_TYPE || type === REACT_SUSPENSE_LIST_TYPE || enableLegacyHidden  || type === REACT_OFFSCREEN_TYPE || enableScopeAPI  || enableCacheElement  || enableTransitionTracing ) {
    return true;
  }

  if (typeof type === 'object' && type !== null) {
    if (type.$$typeof === REACT_LAZY_TYPE || type.$$typeof === REACT_MEMO_TYPE || type.$$typeof === REACT_PROVIDER_TYPE || type.$$typeof === REACT_CONTEXT_TYPE || type.$$typeof === REACT_FORWARD_REF_TYPE || // This needs to include all possible module reference object
    // types supported by any Flight configuration anywhere since
    // we don't know which Flight build this will end up being used
    // with.
    type.$$typeof === REACT_MODULE_REFERENCE || type.getModuleId !== undefined) {
      return true;
    }
  }

  return false;
}

function getWrappedName(outerType, innerType, wrapperName) {
  var displayName = outerType.displayName;

  if (displayName) {
    return displayName;
  }

  var functionName = innerType.displayName || innerType.name || '';
  return functionName !== '' ? wrapperName + "(" + functionName + ")" : wrapperName;
} // Keep in sync with react-reconciler/getComponentNameFromFiber


function getContextName(type) {
  return type.displayName || 'Context';
} // Note that the reconciler package should generally prefer to use getComponentNameFromFiber() instead.


function getComponentNameFromType(type) {
  if (type == null) {
    // Host root, text node or just invalid type.
    return null;
  }

  {
    if (typeof type.tag === 'number') {
      error('Received an unexpected object in getComponentNameFromType(). ' + 'This is likely a bug in React. Please file an issue.');
    }
  }

  if (typeof type === 'function') {
    return type.displayName || type.name || null;
  }

  if (typeof type === 'string') {
    return type;
  }

  switch (type) {
    case REACT_FRAGMENT_TYPE:
      return 'Fragment';

    case REACT_PORTAL_TYPE:
      return 'Portal';

    case REACT_PROFILER_TYPE:
      return 'Profiler';

    case REACT_STRICT_MODE_TYPE:
      return 'StrictMode';

    case REACT_SUSPENSE_TYPE:
      return 'Suspense';

    case REACT_SUSPENSE_LIST_TYPE:
      return 'SuspenseList';

  }

  if (typeof type === 'object') {
    switch (type.$$typeof) {
      case REACT_CONTEXT_TYPE:
        var context = type;
        return getContextName(context) + '.Consumer';

      case REACT_PROVIDER_TYPE:
        var provider = type;
        return getContextName(provider._context) + '.Provider';

      case REACT_FORWARD_REF_TYPE:
        return getWrappedName(type, type.render, 'ForwardRef');

      case REACT_MEMO_TYPE:
        var outerName = type.displayName || null;

        if (outerName !== null) {
          return outerName;
        }

        return getComponentNameFromType(type.type) || 'Memo';

      case REACT_LAZY_TYPE:
        {
          var lazyComponent = type;
          var payload = lazyComponent._payload;
          var init = lazyComponent._init;

          try {
            return getComponentNameFromType(init(payload));
          } catch (x) {
            return null;
          }
        }

      // eslint-disable-next-line no-fallthrough
    }
  }

  return null;
}

var assign = Object.assign;

// Helpers to patch console.logs to avoid logging during side-effect free
// replaying on render function. This currently only patches the object
// lazily which won't cover if the log function was extracted eagerly.
// We could also eagerly patch the method.
var disabledDepth = 0;
var prevLog;
var prevInfo;
var prevWarn;
var prevError;
var prevGroup;
var prevGroupCollapsed;
var prevGroupEnd;

function disabledLog() {}

disabledLog.__reactDisabledLog = true;
function disableLogs() {
  {
    if (disabledDepth === 0) {
      /* eslint-disable react-internal/no-production-logging */
      prevLog = console.log;
      prevInfo = console.info;
      prevWarn = console.warn;
      prevError = console.error;
      prevGroup = console.group;
      prevGroupCollapsed = console.groupCollapsed;
      prevGroupEnd = console.groupEnd; // https://github.com/facebook/react/issues/19099

      var props = {
        configurable: true,
        enumerable: true,
        value: disabledLog,
        writable: true
      }; // $FlowFixMe Flow thinks console is immutable.

      Object.defineProperties(console, {
        info: props,
        log: props,
        warn: props,
        error: props,
        group: props,
        groupCollapsed: props,
        groupEnd: props
      });
      /* eslint-enable react-internal/no-production-logging */
    }

    disabledDepth++;
  }
}
function reenableLogs() {
  {
    disabledDepth--;

    if (disabledDepth === 0) {
      /* eslint-disable react-internal/no-production-logging */
      var props = {
        configurable: true,
        enumerable: true,
        writable: true
      }; // $FlowFixMe Flow thinks console is immutable.

      Object.defineProperties(console, {
        log: assign({}, props, {
          value: prevLog
        }),
        info: assign({}, props, {
          value: prevInfo
        }),
        warn: assign({}, props, {
          value: prevWarn
        }),
        error: assign({}, props, {
          value: prevError
        }),
        group: assign({}, props, {
          value: prevGroup
        }),
        groupCollapsed: assign({}, props, {
          value: prevGroupCollapsed
        }),
        groupEnd: assign({}, props, {
          value: prevGroupEnd
        })
      });
      /* eslint-enable react-internal/no-production-logging */
    }

    if (disabledDepth < 0) {
      error('disabledDepth fell below zero. ' + 'This is a bug in React. Please file an issue.');
    }
  }
}

var ReactCurrentDispatcher = ReactSharedInternals.ReactCurrentDispatcher;
var prefix;
function describeBuiltInComponentFrame(name, source, ownerFn) {
  {
    if (prefix === undefined) {
      // Extract the VM specific prefix used by each line.
      try {
        throw Error();
      } catch (x) {
        var match = x.stack.trim().match(/\n( *(at )?)/);
        prefix = match && match[1] || '';
      }
    } // We use the prefix to ensure our stacks line up with native stack frames.


    return '\n' + prefix + name;
  }
}
var reentry = false;
var componentFrameCache;

{
  var PossiblyWeakMap = typeof WeakMap === 'function' ? WeakMap : Map;
  componentFrameCache = new PossiblyWeakMap();
}

function describeNativeComponentFrame(fn, construct) {
  // If something asked for a stack inside a fake render, it should get ignored.
  if ( !fn || reentry) {
    return '';
  }

  {
    var frame = componentFrameCache.get(fn);

    if (frame !== undefined) {
      return frame;
    }
  }

  var control;
  reentry = true;
  var previousPrepareStackTrace = Error.prepareStackTrace; // $FlowFixMe It does accept undefined.

  Error.prepareStackTrace = undefined;
  var previousDispatcher;

  {
    previousDispatcher = ReactCurrentDispatcher.current; // Set the dispatcher in DEV because this might be call in the render function
    // for warnings.

    ReactCurrentDispatcher.current = null;
    disableLogs();
  }

  try {
    // This should throw.
    if (construct) {
      // Something should be setting the props in the constructor.
      var Fake = function () {
        throw Error();
      }; // $FlowFixMe


      Object.defineProperty(Fake.prototype, 'props', {
        set: function () {
          // We use a throwing setter instead of frozen or non-writable props
          // because that won't throw in a non-strict mode function.
          throw Error();
        }
      });

      if (typeof Reflect === 'object' && Reflect.construct) {
        // We construct a different control for this case to include any extra
        // frames added by the construct call.
        try {
          Reflect.construct(Fake, []);
        } catch (x) {
          control = x;
        }

        Reflect.construct(fn, [], Fake);
      } else {
        try {
          Fake.call();
        } catch (x) {
          control = x;
        }

        fn.call(Fake.prototype);
      }
    } else {
      try {
        throw Error();
      } catch (x) {
        control = x;
      }

      fn();
    }
  } catch (sample) {
    // This is inlined manually because closure doesn't do it for us.
    if (sample && control && typeof sample.stack === 'string') {
      // This extracts the first frame from the sample that isn't also in the control.
      // Skipping one frame that we assume is the frame that calls the two.
      var sampleLines = sample.stack.split('\n');
      var controlLines = control.stack.split('\n');
      var s = sampleLines.length - 1;
      var c = controlLines.length - 1;

      while (s >= 1 && c >= 0 && sampleLines[s] !== controlLines[c]) {
        // We expect at least one stack frame to be shared.
        // Typically this will be the root most one. However, stack frames may be
        // cut off due to maximum stack limits. In this case, one maybe cut off
        // earlier than the other. We assume that the sample is longer or the same
        // and there for cut off earlier. So we should find the root most frame in
        // the sample somewhere in the control.
        c--;
      }

      for (; s >= 1 && c >= 0; s--, c--) {
        // Next we find the first one that isn't the same which should be the
        // frame that called our sample function and the control.
        if (sampleLines[s] !== controlLines[c]) {
          // In V8, the first line is describing the message but other VMs don't.
          // If we're about to return the first line, and the control is also on the same
          // line, that's a pretty good indicator that our sample threw at same line as
          // the control. I.e. before we entered the sample frame. So we ignore this result.
          // This can happen if you passed a class to function component, or non-function.
          if (s !== 1 || c !== 1) {
            do {
              s--;
              c--; // We may still have similar intermediate frames from the construct call.
              // The next one that isn't the same should be our match though.

              if (c < 0 || sampleLines[s] !== controlLines[c]) {
                // V8 adds a "new" prefix for native classes. Let's remove it to make it prettier.
                var _frame = '\n' + sampleLines[s].replace(' at new ', ' at '); // If our component frame is labeled "<anonymous>"
                // but we have a user-provided "displayName"
                // splice it in to make the stack more readable.


                if (fn.displayName && _frame.includes('<anonymous>')) {
                  _frame = _frame.replace('<anonymous>', fn.displayName);
                }

                {
                  if (typeof fn === 'function') {
                    componentFrameCache.set(fn, _frame);
                  }
                } // Return the line we found.


                return _frame;
              }
            } while (s >= 1 && c >= 0);
          }

          break;
        }
      }
    }
  } finally {
    reentry = false;

    {
      ReactCurrentDispatcher.current = previousDispatcher;
      reenableLogs();
    }

    Error.prepareStackTrace = previousPrepareStackTrace;
  } // Fallback to just using the name if we couldn't make it throw.


  var name = fn ? fn.displayName || fn.name : '';
  var syntheticFrame = name ? describeBuiltInComponentFrame(name) : '';

  {
    if (typeof fn === 'function') {
      componentFrameCache.set(fn, syntheticFrame);
    }
  }

  return syntheticFrame;
}
function describeFunctionComponentFrame(fn, source, ownerFn) {
  {
    return describeNativeComponentFrame(fn, false);
  }
}

function shouldConstruct(Component) {
  var prototype = Component.prototype;
  return !!(prototype && prototype.isReactComponent);
}

function describeUnknownElementTypeFrameInDEV(type, source, ownerFn) {

  if (type == null) {
    return '';
  }

  if (typeof type === 'function') {
    {
      return describeNativeComponentFrame(type, shouldConstruct(type));
    }
  }

  if (typeof type === 'string') {
    return describeBuiltInComponentFrame(type);
  }

  switch (type) {
    case REACT_SUSPENSE_TYPE:
      return describeBuiltInComponentFrame('Suspense');

    case REACT_SUSPENSE_LIST_TYPE:
      return describeBuiltInComponentFrame('SuspenseList');
  }

  if (typeof type === 'object') {
    switch (type.$$typeof) {
      case REACT_FORWARD_REF_TYPE:
        return describeFunctionComponentFrame(type.render);

      case REACT_MEMO_TYPE:
        // Memo may contain any component type so we recursively resolve it.
        return describeUnknownElementTypeFrameInDEV(type.type, source, ownerFn);

      case REACT_LAZY_TYPE:
        {
          var lazyComponent = type;
          var payload = lazyComponent._payload;
          var init = lazyComponent._init;

          try {
            // Lazy may contain any component type so we recursively resolve it.
            return describeUnknownElementTypeFrameInDEV(init(payload), source, ownerFn);
          } catch (x) {}
        }
    }
  }

  return '';
}

var hasOwnProperty = Object.prototype.hasOwnProperty;

var loggedTypeFailures = {};
var ReactDebugCurrentFrame = ReactSharedInternals.ReactDebugCurrentFrame;

function setCurrentlyValidatingElement(element) {
  {
    if (element) {
      var owner = element._owner;
      var stack = describeUnknownElementTypeFrameInDEV(element.type, element._source, owner ? owner.type : null);
      ReactDebugCurrentFrame.setExtraStackFrame(stack);
    } else {
      ReactDebugCurrentFrame.setExtraStackFrame(null);
    }
  }
}

function checkPropTypes(typeSpecs, values, location, componentName, element) {
  {
    // $FlowFixMe This is okay but Flow doesn't know it.
    var has = Function.call.bind(hasOwnProperty);

    for (var typeSpecName in typeSpecs) {
      if (has(typeSpecs, typeSpecName)) {
        var error$1 = void 0; // Prop type validation may throw. In case they do, we don't want to
        // fail the render phase where it didn't fail before. So we log it.
        // After these have been cleaned up, we'll let them throw.

        try {
          // This is intentionally an invariant that gets caught. It's the same
          // behavior as without this statement except with a better message.
          if (typeof typeSpecs[typeSpecName] !== 'function') {
            // eslint-disable-next-line react-internal/prod-error-codes
            var err = Error((componentName || 'React class') + ': ' + location + ' type `' + typeSpecName + '` is invalid; ' + 'it must be a function, usually from the `prop-types` package, but received `' + typeof typeSpecs[typeSpecName] + '`.' + 'This often happens because of typos such as `PropTypes.function` instead of `PropTypes.func`.');
            err.name = 'Invariant Violation';
            throw err;
          }

          error$1 = typeSpecs[typeSpecName](values, typeSpecName, componentName, location, null, 'SECRET_DO_NOT_PASS_THIS_OR_YOU_WILL_BE_FIRED');
        } catch (ex) {
          error$1 = ex;
        }

        if (error$1 && !(error$1 instanceof Error)) {
          setCurrentlyValidatingElement(element);

          error('%s: type specification of %s' + ' `%s` is invalid; the type checker ' + 'function must return `null` or an `Error` but returned a %s. ' + 'You may have forgotten to pass an argument to the type checker ' + 'creator (arrayOf, instanceOf, objectOf, oneOf, oneOfType, and ' + 'shape all require an argument).', componentName || 'React class', location, typeSpecName, typeof error$1);

          setCurrentlyValidatingElement(null);
        }

        if (error$1 instanceof Error && !(error$1.message in loggedTypeFailures)) {
          // Only monitor this failure once because there tends to be a lot of the
          // same error.
          loggedTypeFailures[error$1.message] = true;
          setCurrentlyValidatingElement(element);

          error('Failed %s type: %s', location, error$1.message);

          setCurrentlyValidatingElement(null);
        }
      }
    }
  }
}

var isArrayImpl = Array.isArray; // eslint-disable-next-line no-redeclare

function isArray(a) {
  return isArrayImpl(a);
}

/*
 * The `'' + value` pattern (used in in perf-sensitive code) throws for Symbol
 * and Temporal.* types. See https://github.com/facebook/react/pull/22064.
 *
 * The functions in this module will throw an easier-to-understand,
 * easier-to-debug exception with a clear errors message message explaining the
 * problem. (Instead of a confusing exception thrown inside the implementation
 * of the `value` object).
 */
// $FlowFixMe only called in DEV, so void return is not possible.
function typeName(value) {
  {
    // toStringTag is needed for namespaced types like Temporal.Instant
    var hasToStringTag = typeof Symbol === 'function' && Symbol.toStringTag;
    var type = hasToStringTag && value[Symbol.toStringTag] || value.constructor.name || 'Object';
    return type;
  }
} // $FlowFixMe only called in DEV, so void return is not possible.


function willCoercionThrow(value) {
  {
    try {
      testStringCoercion(value);
      return false;
    } catch (e) {
      return true;
    }
  }
}

function testStringCoercion(value) {
  // If you ended up here by following an exception call stack, here's what's
  // happened: you supplied an object or symbol value to React (as a prop, key,
  // DOM attribute, CSS property, string ref, etc.) and when React tried to
  // coerce it to a string using `'' + value`, an exception was thrown.
  //
  // The most common types that will cause this exception are `Symbol` instances
  // and Temporal objects like `Temporal.Instant`. But any object that has a
  // `valueOf` or `[Symbol.toPrimitive]` method that throws will also cause this
  // exception. (Library authors do this to prevent users from using built-in
  // numeric operators like `+` or comparison operators like `>=` because custom
  // methods are needed to perform accurate arithmetic or comparison.)
  //
  // To fix the problem, coerce this object or symbol value to a string before
  // passing it to React. The most reliable way is usually `String(value)`.
  //
  // To find which value is throwing, check the browser or debugger console.
  // Before this exception was thrown, there should be `console.error` output
  // that shows the type (Symbol, Temporal.PlainDate, etc.) that caused the
  // problem and how that type was used: key, atrribute, input value prop, etc.
  // In most cases, this console output also shows the component and its
  // ancestor components where the exception happened.
  //
  // eslint-disable-next-line react-internal/safe-string-coercion
  return '' + value;
}
function checkKeyStringCoercion(value) {
  {
    if (willCoercionThrow(value)) {
      error('The provided key is an unsupported type %s.' + ' This value must be coerced to a string before before using it here.', typeName(value));

      return testStringCoercion(value); // throw (to help callers find troubleshooting comments)
    }
  }
}

var ReactCurrentOwner = ReactSharedInternals.ReactCurrentOwner;
var RESERVED_PROPS = {
  key: true,
  ref: true,
  __self: true,
  __source: true
};
var specialPropKeyWarningShown;
var specialPropRefWarningShown;
var didWarnAboutStringRefs;

{
  didWarnAboutStringRefs = {};
}

function hasValidRef(config) {
  {
    if (hasOwnProperty.call(config, 'ref')) {
      var getter = Object.getOwnPropertyDescriptor(config, 'ref').get;

      if (getter && getter.isReactWarning) {
        return false;
      }
    }
  }

  return config.ref !== undefined;
}

function hasValidKey(config) {
  {
    if (hasOwnProperty.call(config, 'key')) {
      var getter = Object.getOwnPropertyDescriptor(config, 'key').get;

      if (getter && getter.isReactWarning) {
        return false;
      }
    }
  }

  return config.key !== undefined;
}

function warnIfStringRefCannotBeAutoConverted(config, self) {
  {
    if (typeof config.ref === 'string' && ReactCurrentOwner.current && self && ReactCurrentOwner.current.stateNode !== self) {
      var componentName = getComponentNameFromType(ReactCurrentOwner.current.type);

      if (!didWarnAboutStringRefs[componentName]) {
        error('Component "%s" contains the string ref "%s". ' + 'Support for string refs will be removed in a future major release. ' + 'This case cannot be automatically converted to an arrow function. ' + 'We ask you to manually fix this case by using useRef() or createRef() instead. ' + 'Learn more about using refs safely here: ' + 'https://reactjs.org/link/strict-mode-string-ref', getComponentNameFromType(ReactCurrentOwner.current.type), config.ref);

        didWarnAboutStringRefs[componentName] = true;
      }
    }
  }
}

function defineKeyPropWarningGetter(props, displayName) {
  {
    var warnAboutAccessingKey = function () {
      if (!specialPropKeyWarningShown) {
        specialPropKeyWarningShown = true;

        error('%s: `key` is not a prop. Trying to access it will result ' + 'in `undefined` being returned. If you need to access the same ' + 'value within the child component, you should pass it as a different ' + 'prop. (https://reactjs.org/link/special-props)', displayName);
      }
    };

    warnAboutAccessingKey.isReactWarning = true;
    Object.defineProperty(props, 'key', {
      get: warnAboutAccessingKey,
      configurable: true
    });
  }
}

function defineRefPropWarningGetter(props, displayName) {
  {
    var warnAboutAccessingRef = function () {
      if (!specialPropRefWarningShown) {
        specialPropRefWarningShown = true;

        error('%s: `ref` is not a prop. Trying to access it will result ' + 'in `undefined` being returned. If you need to access the same ' + 'value within the child component, you should pass it as a different ' + 'prop. (https://reactjs.org/link/special-props)', displayName);
      }
    };

    warnAboutAccessingRef.isReactWarning = true;
    Object.defineProperty(props, 'ref', {
      get: warnAboutAccessingRef,
      configurable: true
    });
  }
}
/**
 * Factory method to create a new React element. This no longer adheres to
 * the class pattern, so do not use new to call it. Also, instanceof check
 * will not work. Instead test $$typeof field against Symbol.for('react.element') to check
 * if something is a React Element.
 *
 * @param {*} type
 * @param {*} props
 * @param {*} key
 * @param {string|object} ref
 * @param {*} owner
 * @param {*} self A *temporary* helper to detect places where `this` is
 * different from the `owner` when React.createElement is called, so that we
 * can warn. We want to get rid of owner and replace string `ref`s with arrow
 * functions, and as long as `this` and owner are the same, there will be no
 * change in behavior.
 * @param {*} source An annotation object (added by a transpiler or otherwise)
 * indicating filename, line number, and/or other information.
 * @internal
 */


var ReactElement = function (type, key, ref, self, source, owner, props) {
  var element = {
    // This tag allows us to uniquely identify this as a React Element
    $$typeof: REACT_ELEMENT_TYPE,
    // Built-in properties that belong on the element
    type: type,
    key: key,
    ref: ref,
    props: props,
    // Record the component responsible for creating this element.
    _owner: owner
  };

  {
    // The validation flag is currently mutative. We put it on
    // an external backing store so that we can freeze the whole object.
    // This can be replaced with a WeakMap once they are implemented in
    // commonly used development environments.
    element._store = {}; // To make comparing ReactElements easier for testing purposes, we make
    // the validation flag non-enumerable (where possible, which should
    // include every environment we run tests in), so the test framework
    // ignores it.

    Object.defineProperty(element._store, 'validated', {
      configurable: false,
      enumerable: false,
      writable: true,
      value: false
    }); // self and source are DEV only properties.

    Object.defineProperty(element, '_self', {
      configurable: false,
      enumerable: false,
      writable: false,
      value: self
    }); // Two elements created in two different places should be considered
    // equal for testing purposes and therefore we hide it from enumeration.

    Object.defineProperty(element, '_source', {
      configurable: false,
      enumerable: false,
      writable: false,
      value: source
    });

    if (Object.freeze) {
      Object.freeze(element.props);
      Object.freeze(element);
    }
  }

  return element;
};
/**
 * https://github.com/reactjs/rfcs/pull/107
 * @param {*} type
 * @param {object} props
 * @param {string} key
 */

function jsxDEV(type, config, maybeKey, source, self) {
  {
    var propName; // Reserved names are extracted

    var props = {};
    var key = null;
    var ref = null; // Currently, key can be spread in as a prop. This causes a potential
    // issue if key is also explicitly declared (ie. <div {...props} key="Hi" />
    // or <div key="Hi" {...props} /> ). We want to deprecate key spread,
    // but as an intermediary step, we will use jsxDEV for everything except
    // <div {...props} key="Hi" />, because we aren't currently able to tell if
    // key is explicitly declared to be undefined or not.

    if (maybeKey !== undefined) {
      {
        checkKeyStringCoercion(maybeKey);
      }

      key = '' + maybeKey;
    }

    if (hasValidKey(config)) {
      {
        checkKeyStringCoercion(config.key);
      }

      key = '' + config.key;
    }

    if (hasValidRef(config)) {
      ref = config.ref;
      warnIfStringRefCannotBeAutoConverted(config, self);
    } // Remaining properties are added to a new props object


    for (propName in config) {
      if (hasOwnProperty.call(config, propName) && !RESERVED_PROPS.hasOwnProperty(propName)) {
        props[propName] = config[propName];
      }
    } // Resolve default props


    if (type && type.defaultProps) {
      var defaultProps = type.defaultProps;

      for (propName in defaultProps) {
        if (props[propName] === undefined) {
          props[propName] = defaultProps[propName];
        }
      }
    }

    if (key || ref) {
      var displayName = typeof type === 'function' ? type.displayName || type.name || 'Unknown' : type;

      if (key) {
        defineKeyPropWarningGetter(props, displayName);
      }

      if (ref) {
        defineRefPropWarningGetter(props, displayName);
      }
    }

    return ReactElement(type, key, ref, self, source, ReactCurrentOwner.current, props);
  }
}

var ReactCurrentOwner$1 = ReactSharedInternals.ReactCurrentOwner;
var ReactDebugCurrentFrame$1 = ReactSharedInternals.ReactDebugCurrentFrame;

function setCurrentlyValidatingElement$1(element) {
  {
    if (element) {
      var owner = element._owner;
      var stack = describeUnknownElementTypeFrameInDEV(element.type, element._source, owner ? owner.type : null);
      ReactDebugCurrentFrame$1.setExtraStackFrame(stack);
    } else {
      ReactDebugCurrentFrame$1.setExtraStackFrame(null);
    }
  }
}

var propTypesMisspellWarningShown;

{
  propTypesMisspellWarningShown = false;
}
/**
 * Verifies the object is a ReactElement.
 * See https://reactjs.org/docs/react-api.html#isvalidelement
 * @param {?object} object
 * @return {boolean} True if `object` is a ReactElement.
 * @final
 */


function isValidElement(object) {
  {
    return typeof object === 'object' && object !== null && object.$$typeof === REACT_ELEMENT_TYPE;
  }
}

function getDeclarationErrorAddendum() {
  {
    if (ReactCurrentOwner$1.current) {
      var name = getComponentNameFromType(ReactCurrentOwner$1.current.type);

      if (name) {
        return '\n\nCheck the render method of `' + name + '`.';
      }
    }

    return '';
  }
}

function getSourceInfoErrorAddendum(source) {
  {
    if (source !== undefined) {
      var fileName = source.fileName.replace(/^.*[\\\/]/, '');
      var lineNumber = source.lineNumber;
      return '\n\nCheck your code at ' + fileName + ':' + lineNumber + '.';
    }

    return '';
  }
}
/**
 * Warn if there's no key explicitly set on dynamic arrays of children or
 * object keys are not valid. This allows us to keep track of children between
 * updates.
 */


var ownerHasKeyUseWarning = {};

function getCurrentComponentErrorInfo(parentType) {
  {
    var info = getDeclarationErrorAddendum();

    if (!info) {
      var parentName = typeof parentType === 'string' ? parentType : parentType.displayName || parentType.name;

      if (parentName) {
        info = "\n\nCheck the top-level render call using <" + parentName + ">.";
      }
    }

    return info;
  }
}
/**
 * Warn if the element doesn't have an explicit key assigned to it.
 * This element is in an array. The array could grow and shrink or be
 * reordered. All children that haven't already been validated are required to
 * have a "key" property assigned to it. Error statuses are cached so a warning
 * will only be shown once.
 *
 * @internal
 * @param {ReactElement} element Element that requires a key.
 * @param {*} parentType element's parent's type.
 */


function validateExplicitKey(element, parentType) {
  {
    if (!element._store || element._store.validated || element.key != null) {
      return;
    }

    element._store.validated = true;
    var currentComponentErrorInfo = getCurrentComponentErrorInfo(parentType);

    if (ownerHasKeyUseWarning[currentComponentErrorInfo]) {
      return;
    }

    ownerHasKeyUseWarning[currentComponentErrorInfo] = true; // Usually the current owner is the offender, but if it accepts children as a
    // property, it may be the creator of the child that's responsible for
    // assigning it a key.

    var childOwner = '';

    if (element && element._owner && element._owner !== ReactCurrentOwner$1.current) {
      // Give the component that originally created this child.
      childOwner = " It was passed a child from " + getComponentNameFromType(element._owner.type) + ".";
    }

    setCurrentlyValidatingElement$1(element);

    error('Each child in a list should have a unique "key" prop.' + '%s%s See https://reactjs.org/link/warning-keys for more information.', currentComponentErrorInfo, childOwner);

    setCurrentlyValidatingElement$1(null);
  }
}
/**
 * Ensure that every element either is passed in a static location, in an
 * array with an explicit keys property defined, or in an object literal
 * with valid key property.
 *
 * @internal
 * @param {ReactNode} node Statically passed child of any type.
 * @param {*} parentType node's parent's type.
 */


function validateChildKeys(node, parentType) {
  {
    if (typeof node !== 'object') {
      return;
    }

    if (isArray(node)) {
      for (var i = 0; i < node.length; i++) {
        var child = node[i];

        if (isValidElement(child)) {
          validateExplicitKey(child, parentType);
        }
      }
    } else if (isValidElement(node)) {
      // This element was passed in a valid location.
      if (node._store) {
        node._store.validated = true;
      }
    } else if (node) {
      var iteratorFn = getIteratorFn(node);

      if (typeof iteratorFn === 'function') {
        // Entry iterators used to provide implicit keys,
        // but now we print a separate warning for them later.
        if (iteratorFn !== node.entries) {
          var iterator = iteratorFn.call(node);
          var step;

          while (!(step = iterator.next()).done) {
            if (isValidElement(step.value)) {
              validateExplicitKey(step.value, parentType);
            }
          }
        }
      }
    }
  }
}
/**
 * Given an element, validate that its props follow the propTypes definition,
 * provided by the type.
 *
 * @param {ReactElement} element
 */


function validatePropTypes(element) {
  {
    var type = element.type;

    if (type === null || type === undefined || typeof type === 'string') {
      return;
    }

    var propTypes;

    if (typeof type === 'function') {
      propTypes = type.propTypes;
    } else if (typeof type === 'object' && (type.$$typeof === REACT_FORWARD_REF_TYPE || // Note: Memo only checks outer props here.
    // Inner props are checked in the reconciler.
    type.$$typeof === REACT_MEMO_TYPE)) {
      propTypes = type.propTypes;
    } else {
      return;
    }

    if (propTypes) {
      // Intentionally inside to avoid triggering lazy initializers:
      var name = getComponentNameFromType(type);
      checkPropTypes(propTypes, element.props, 'prop', name, element);
    } else if (type.PropTypes !== undefined && !propTypesMisspellWarningShown) {
      propTypesMisspellWarningShown = true; // Intentionally inside to avoid triggering lazy initializers:

      var _name = getComponentNameFromType(type);

      error('Component %s declared `PropTypes` instead of `propTypes`. Did you misspell the property assignment?', _name || 'Unknown');
    }

    if (typeof type.getDefaultProps === 'function' && !type.getDefaultProps.isReactClassApproved) {
      error('getDefaultProps is only used on classic React.createClass ' + 'definitions. Use a static property named `defaultProps` instead.');
    }
  }
}
/**
 * Given a fragment, validate that it can only be provided with fragment props
 * @param {ReactElement} fragment
 */


function validateFragmentProps(fragment) {
  {
    var keys = Object.keys(fragment.props);

    for (var i = 0; i < keys.length; i++) {
      var key = keys[i];

      if (key !== 'children' && key !== 'key') {
        setCurrentlyValidatingElement$1(fragment);

        error('Invalid prop `%s` supplied to `React.Fragment`. ' + 'React.Fragment can only have `key` and `children` props.', key);

        setCurrentlyValidatingElement$1(null);
        break;
      }
    }

    if (fragment.ref !== null) {
      setCurrentlyValidatingElement$1(fragment);

      error('Invalid attribute `ref` supplied to `React.Fragment`.');

      setCurrentlyValidatingElement$1(null);
    }
  }
}

var didWarnAboutKeySpread = {};
function jsxWithValidation(type, props, key, isStaticChildren, source, self) {
  {
    var validType = isValidElementType(type); // We warn in this case but don't throw. We expect the element creation to
    // succeed and there will likely be errors in render.

    if (!validType) {
      var info = '';

      if (type === undefined || typeof type === 'object' && type !== null && Object.keys(type).length === 0) {
        info += ' You likely forgot to export your component from the file ' + "it's defined in, or you might have mixed up default and named imports.";
      }

      var sourceInfo = getSourceInfoErrorAddendum(source);

      if (sourceInfo) {
        info += sourceInfo;
      } else {
        info += getDeclarationErrorAddendum();
      }

      var typeString;

      if (type === null) {
        typeString = 'null';
      } else if (isArray(type)) {
        typeString = 'array';
      } else if (type !== undefined && type.$$typeof === REACT_ELEMENT_TYPE) {
        typeString = "<" + (getComponentNameFromType(type.type) || 'Unknown') + " />";
        info = ' Did you accidentally export a JSX literal instead of a component?';
      } else {
        typeString = typeof type;
      }

      error('React.jsx: type is invalid -- expected a string (for ' + 'built-in components) or a class/function (for composite ' + 'components) but got: %s.%s', typeString, info);
    }

    var element = jsxDEV(type, props, key, source, self); // The result can be nullish if a mock or a custom function is used.
    // TODO: Drop this when these are no longer allowed as the type argument.

    if (element == null) {
      return element;
    } // Skip key warning if the type isn't valid since our key validation logic
    // doesn't expect a non-string/function type and can throw confusing errors.
    // We don't want exception behavior to differ between dev and prod.
    // (Rendering will throw with a helpful message and as soon as the type is
    // fixed, the key warnings will appear.)


    if (validType) {
      var children = props.children;

      if (children !== undefined) {
        if (isStaticChildren) {
          if (isArray(children)) {
            for (var i = 0; i < children.length; i++) {
              validateChildKeys(children[i], type);
            }

            if (Object.freeze) {
              Object.freeze(children);
            }
          } else {
            error('React.jsx: Static children should always be an array. ' + 'You are likely explicitly calling React.jsxs or React.jsxDEV. ' + 'Use the Babel transform instead.');
          }
        } else {
          validateChildKeys(children, type);
        }
      }
    }

    {
      if (hasOwnProperty.call(props, 'key')) {
        var componentName = getComponentNameFromType(type);
        var keys = Object.keys(props).filter(function (k) {
          return k !== 'key';
        });
        var beforeExample = keys.length > 0 ? '{key: someKey, ' + keys.join(': ..., ') + ': ...}' : '{key: someKey}';

        if (!didWarnAboutKeySpread[componentName + beforeExample]) {
          var afterExample = keys.length > 0 ? '{' + keys.join(': ..., ') + ': ...}' : '{}';

          error('A props object containing a "key" prop is being spread into JSX:\n' + '  let props = %s;\n' + '  <%s {...props} />\n' + 'React keys must be passed directly to JSX without using spread:\n' + '  let props = %s;\n' + '  <%s key={someKey} {...props} />', beforeExample, componentName, afterExample, componentName);

          didWarnAboutKeySpread[componentName + beforeExample] = true;
        }
      }
    }

    if (type === REACT_FRAGMENT_TYPE) {
      validateFragmentProps(element);
    } else {
      validatePropTypes(element);
    }

    return element;
  }
} // These two functions exist to still get child warnings in dev
// even with the prod transform. This means that jsxDEV is purely
// opt-in behavior for better messages but that we won't stop
// giving you warnings if you use production apis.

function jsxWithValidationStatic(type, props, key) {
  {
    return jsxWithValidation(type, props, key, true);
  }
}
function jsxWithValidationDynamic(type, props, key) {
  {
    return jsxWithValidation(type, props, key, false);
  }
}

var jsx =  jsxWithValidationDynamic ; // we may want to special case jsxs internally to take advantage of static children.
// for now we can ship identical prod functions

var jsxs =  jsxWithValidationStatic ;

exports.Fragment = REACT_FRAGMENT_TYPE;
exports.jsx = jsx;
exports.jsxs = jsxs;
  })();
}


/***/ }),

/***/ "./node_modules/react/jsx-runtime.js":
/*!*******************************************!*\
  !*** ./node_modules/react/jsx-runtime.js ***!
  \*******************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



if (false) // removed by dead control flow
{} else {
  module.exports = __webpack_require__(/*! ./cjs/react-jsx-runtime.development.js */ "./node_modules/react/cjs/react-jsx-runtime.development.js");
}


/***/ }),

/***/ "@woocommerce/blocks-checkout":
/*!****************************************!*\
  !*** external ["wc","blocksCheckout"] ***!
  \****************************************/
/***/ ((module) => {

module.exports = window["wc"]["blocksCheckout"];

/***/ }),

/***/ "@woocommerce/blocks-registry":
/*!******************************************!*\
  !*** external ["wc","wcBlocksRegistry"] ***!
  \******************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcBlocksRegistry"];

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcSettings"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/html-entities":
/*!**************************************!*\
  !*** external ["wp","htmlEntities"] ***!
  \**************************************/
/***/ ((module) => {

module.exports = window["wp"]["htmlEntities"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!********************************!*\
  !*** ./client/blocks/index.js ***!
  \********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @woocommerce/blocks-registry */ "@woocommerce/blocks-registry");
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/settings */ "@woocommerce/settings");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _common_constants__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../common/constants */ "./client/common/constants.js");
/* harmony import */ var _components_credit_card_fields__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/credit-card-fields */ "./client/blocks/components/credit-card-fields.js");
/* harmony import */ var _hooks_use_payment_form__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./hooks/use-payment-form */ "./client/blocks/hooks/use-payment-form.js");
/* harmony import */ var _utils_place_order_button__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./utils/place-order-button */ "./client/blocks/utils/place-order-button.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");
var _settings$supports;
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */





const settings = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__.getPaymentMethodData)(_common_constants__WEBPACK_IMPORTED_MODULE_6__.GATEWAY_ID, {});
const allowSavingCards = settings.allow_saving_cards;
const isHostedFields = settings.integration_type === 'seamless';
const customPlaceOrder = (settings?.placeOrderButtonText || '').trim();
const defaultLabel = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('DNA Payments', _common_constants__WEBPACK_IMPORTED_MODULE_6__.TEXT_DOMAIN);
const label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__.decodeEntities)(settings?.title || '') || defaultLabel;
const icon = settings?.icon;
const icons = settings?.icons;
const globalError = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)(`Authentication failed. Please check that your credentials are correct. If you are using the Hosted Fields integration, make sure it is enabled for your account by your payment provider.`, _common_constants__WEBPACK_IMPORTED_MODULE_6__.TEXT_DOMAIN);

/**
 * Content component
 */
const RawContent = props => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.RawHTML, {
    children: (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__.decodeEntities)(settings.description || '')
  });
};

/**
 * Content component
 */
const Content = props => {
  const [isLoaded, setLoaded] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(false);
  const [hostedFieldsInstance, setHostedFieldsInstance] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(null);
  (0,_hooks_use_payment_form__WEBPACK_IMPORTED_MODULE_8__.usePaymentForm)({
    props,
    hostedFieldsInstance,
    gatewayId: _common_constants__WEBPACK_IMPORTED_MODULE_6__.GATEWAY_ID
  });
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    if (!settings.temp_token) {
      props.setExpressPaymentError(globalError);
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    if (!isHostedFields) {
      (0,_utils_place_order_button__WEBPACK_IMPORTED_MODULE_9__.setPlaceOrderButtonDisabled)(false);
    }
  }, []);
  const isEditor = !!(0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.select)('core/editor');
  // Don't render anything if we're in the editor.
  if (isEditor) {
    return null;
  }
  if (isHostedFields) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_components_credit_card_fields__WEBPACK_IMPORTED_MODULE_7__.DnapaymentsCreditCardFields, {
      props: props,
      isLoaded: isLoaded,
      hostedFieldsInstance: hostedFieldsInstance,
      onLoad: instance => {
        setHostedFieldsInstance(instance);
        setLoaded(true);
      }
    });
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.RawHTML, {
    children: (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__.decodeEntities)(settings.description || '')
  });
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = props => {
  const {
    PaymentMethodLabel
  } = props.components;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)("span", {
    style: {
      width: '100%'
    },
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(PaymentMethodLabel, {
      text: label
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("span", {
      style: {
        float: 'right',
        marginRight: 20,
        display: 'inline-flex'
      },
      children: icons ? icons.map((iconUrl, index) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("img", {
        src: iconUrl,
        style: {
          marginRight: index === icons.length - 1 ? 0 : 2
        }
      }, index)) : icon && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("img", {
        src: icon
      })
    })]
  });
};

/**
 * DNA Payments payment method config object.
 */
const dnapaymentsPaymentMethod = {
  name: _common_constants__WEBPACK_IMPORTED_MODULE_6__.GATEWAY_ID,
  label: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(Label, {}),
  content: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(Content, {}),
  edit: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(RawContent, {}),
  canMakePayment: () => true,
  savedTokenComponent: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(Content, {}),
  ariaLabel: label,
  supports: {
    showSavedCards: allowSavingCards && isHostedFields,
    showSaveOption: allowSavingCards && isHostedFields,
    features: (_settings$supports = settings?.supports) !== null && _settings$supports !== void 0 ? _settings$supports : []
  },
  placeOrderButtonLabel: customPlaceOrder || undefined
};
(0,_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_0__.registerPaymentMethod)(dnapaymentsPaymentMethod);
})();

/******/ })()
;
//# sourceMappingURL=dnapayments.js.map