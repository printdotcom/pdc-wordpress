(function ($) {
  'use strict';

  const PLUGIN_NAME = PDC_POD_ADMIN.plugin_name;

  async function checkCredentials() {
    $(`#js-${PLUGIN_NAME}-auth-success`).hide();
    $(`#js-${PLUGIN_NAME}-auth-failed`).hide();

    const pdcPodApiKey = $(`#pdc_pod_api_key`).val();

    if (!pdcPodApiKey) {
      alert('No API Key entered');
      return;
    }

    if (formIsDirty) {
      alert('Please save the settings before verifying the API key');
      return;
    }

    try {
      $(`#js-${PLUGIN_NAME}-verify_key`).prop('disabled', true);
      $(`#js-${PLUGIN_NAME}-verify_loader`).addClass('is-active');

      const response = await fetch(`${PDC_POD_ADMIN.root}pdc/v1/verify`, {
        method: 'GET',
        headers: {
          'X-WP-Nonce': PDC_POD_ADMIN.nonce,
        },
      });
      if (response.status !== 200) {
        $(`#js-${PLUGIN_NAME}-auth-failed`).show();
        $(`#js-${PLUGIN_NAME}-auth-success`).hide();
        return;
      }
      $(`#js-${PLUGIN_NAME}-auth-failed`).hide();
      $(`#js-${PLUGIN_NAME}-auth-success`).show();
    } catch (err) {
      $(`#js-${PLUGIN_NAME}-auth-failed`).show();
    } finally {
      $(`#js-${PLUGIN_NAME}-verify_key`).prop('disabled', false);
      $(`#js-${PLUGIN_NAME}-verify_loader`).removeClass('is-active');
    }
  }

  // On order item detail page, will allow adding a
  // PDF file to the order item
  function orderItemAttachPdf(e) {
    e.preventDefault();
    const orderItemId = e.target.getAttribute('data-order-item-id');

    const frame = wp.media({
      title: 'Select or Upload a Custom File',
      button: {
        text: 'Use this file',
      },
      library: {
        type: 'document',
        post_mime_type: ['application/pdf'],
      },
      multiple: false,
    });

    frame.on('select', async function () {
      const attachment = frame.state().get('selection').first().toJSON();
      try {
        await $.ajax(
          {
            method: 'POST',
            url: `${PDC_POD_ADMIN.root}pdc/v1/order-items/${orderItemId}/attach-pdf`,
            beforeSend(xhr) {
              xhr.setRequestHeader('X-WP-Nonce', PDC_POD_ADMIN.nonce);
            },
            data: {
              orderItemId,
              pdfUrl: attachment.url,
            },
          },
          {}
        );
        refreshOrder();
      } catch (err) {
        $('#js-pdc-request-response').text(err.responseJSON.message);
      }
    });

    frame.open();
  }

  function refreshOrder(done) {
    $('#js-pdc-order-metabox').load(`${document.URL} #js-pdc-order-fieldset`, done);
  }

  async function purchaseOrderItem(e) {
    e.preventDefault();

    try {
      $('#js-pdc-order-fieldset').prop('disabled', true);
      $('#js-pdc-purchase-error').prop('hidden', true);
      const orderItemId = e.target.getAttribute('data-order-item-id');
      const purchaseState = e.target.getAttribute('data-purchase-state');
      let force = '';
      if (purchaseState) {
        // The item is held because an earlier purchase may have gone through.
        if (!window.confirm(PDC_POD_ADMIN.confirm_force_purchase)) {
          $('#js-pdc-order-fieldset').prop('disabled', false);
          return;
        }
        force = '?force=1';
      }
      const response = await fetch(`${PDC_POD_ADMIN.root}pdc/v1/order-items/${encodeURIComponent(orderItemId)}/purchase${force}`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': PDC_POD_ADMIN.nonce,
        },
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        const message = payload?.message || payload?.data?.message || 'Failed to place order.';
        throw new Error(message);
      }
      refreshOrder();
    } catch (err) {
      refreshOrder(() => showError('Failed to place order', err.message));
    } finally {
      $(e.currentTarget).prop('disabled', false);
    }
  }

  async function purchaseAll(e) {
    e.preventDefault();

    try {
      $('#js-pdc-order-fieldset').prop('disabled', true);
      $('#js-pdc-purchase-error').prop('hidden', true);
      const orderID = e.currentTarget.getAttribute('data-order-id');
      const response = await fetch(`${PDC_POD_ADMIN.root}pdc/v1/orders/${encodeURIComponent(orderID)}/purchase`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': PDC_POD_ADMIN.nonce,
        },
      });
      if (!response.ok) {
        const responseText = await response.text();
        throw new Error(responseText);
      }
      refreshOrder();
    } catch (err) {
      refreshOrder(() => showError('Failed to purchase all order items', err.message));
    } finally {
      $('#js-pdc-order-fieldset').prop('disabled', false);
    }
  }

  function showError(title, descr) {
    $('#js-pdc-purchase-error').css('visibility', 'visible');
    $('#js-pdc-purchase-error-title').text(title);
    $('#js-pdc-purchase-error-descr').text(descr);
  }

  async function downloadLogs(e) {
    e.preventDefault();
    const btn = $(`#js-${PLUGIN_NAME}-download-logs`);
    btn.prop('disabled', true);
    try {
      const response = await fetch(`${PDC_POD_ADMIN.root}pdc/v1/download-logs`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': PDC_POD_ADMIN.nonce,
        },
      });
      if (!response.ok) {
        throw new Error('Failed to download logs');
      }
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'pdc-pod-log.log';
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      a.remove();
    } catch (err) {
      alert('Failed to download logs: ' + err.message);
    } finally {
      btn.prop('disabled', false);
    }
  }

  let formIsDirty = false;
  function observeFormChanges(formID) {
    const formElement = $(formID);
    if (!formElement.length) return;

    $(`${formID} input, ${formID} select`).on('change', function () {
      formIsDirty = true;
    });
  }

  // Upload file button click event for simple products
  function openMediaDialogFromProduct(e) {
    openMediaDialog(e, function (attachment) {
      $(`#${e.target.dataset.pdcVariationFileField}`).val(attachment.url);
      $('.woocommerce_variation').addClass('variation-needs-update');
      $('button.cancel-variation-changes, button.save-variation-changes').prop('disabled', false);
      $('#variable_product_options').trigger('woocommerce_variations_input_changed');
    });
  }
  function openMediaDialogFromOrder(e) {
    openMediaDialog(e, function (attachment) {
      $('#_pdc_file_id').val(attachment.id);
      $('#_pdc-file_url').val(attachment.url);
    });
  }

  function openMediaDialog(e, onSelect) {
    e.preventDefault();
    const mediaUploadModal = wp.media({
      title: 'Select or Upload a PDF',
      button: {
        text: 'Select File',
      },
      library: {
        type: 'document',
        post_mime_type: ['application/pdf'],
      },
      multiple: false,
    });

    mediaUploadModal.on('select', function () {
      const attachment = mediaUploadModal.state().get('selection').first().toJSON();
      onSelect(attachment);
    });

    mediaUploadModal.open();
  }

  // rehook dom elements when variations are loaded
  $(document).on('woocommerce_variations_loaded', function onVariationsLoaded() {
    $('.js-pdc-product-selector').on('change', (e) => loadPresetsForSKU(e.target));
    $('.pdc-pod-js-upload-custom-file-btn').on('click', openMediaDialogFromProduct);
  });

  async function loadPresetsForSKU(target) {
    const sku = target.value;
    if (!sku) return;

    const productID = target.dataset.product_id;
    const presetTargets = document.querySelectorAll(`.js-pdc-preset-list-${productID}`);
    try {
      const response = await fetch(`${PDC_POD_ADMIN.root}pdc/v1/products/${encodeURIComponent(sku)}/presets`, {
        method: 'GET',
        headers: {
          'X-WP-Nonce': PDC_POD_ADMIN.nonce,
        },
      });
      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.message || 'Failed to load presets.');
      }
      const presetOptionsHTML = payload?.html || '';

      presetTargets.forEach((selectInput) => setSelectedValue(selectInput, presetOptionsHTML));
    } catch (err) {
      console.error('Failed to load presets', err);
    }
  }

  function setSelectedValue(selectInput, presetOptions) {
    const targetValue = selectInput.getAttribute('data-current-value') || selectInput.value;
    selectInput.innerHTML = presetOptions;
    selectInput.value = targetValue.trim();
  }

  $(document).ready(function () {
    $('#js-pdc-product-selector').on('change', (e) => loadPresetsForSKU(e.target));
    $('#pdc-product-file-upload').on('click', openMediaDialogFromOrder);
    $('.pdc-pod-js-upload-custom-file-btn').on('click', openMediaDialogFromProduct);
    $(document).on('click', '.js-pdc-file-upload', orderItemAttachPdf);
    $(document).on('click', '.js-pdc-purchase-orderitem', purchaseOrderItem);
    $(document).on('click', '#js-pdc-purchase-all', purchaseAll);
    $(`#js-${PLUGIN_NAME}-verify_key`).click(checkCredentials);
    $(`#js-${PLUGIN_NAME}-download-logs`).on('click', downloadLogs);
    observeFormChanges(`#js-${PLUGIN_NAME}-general-form`);
  });
})(jQuery);
